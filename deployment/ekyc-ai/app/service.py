# -*- coding: utf-8 -*-
"""
ประกอบ pipeline ของสอง endpoint — โหลดโมเดลครั้งเดียว (Engine) แล้วเรียกจากเธรดพูล

ห้าม log ภาพ / ข้อความ OCR / ชื่อ / เลขบัตร — log ได้แค่เวลา คะแนน และรหัสเหตุผล
"""
from __future__ import annotations

import hashlib
import json
import logging
import os
import time
from datetime import date, datetime, timedelta, timezone

import cv2
import numpy as np

from . import card as C
from .antispoof import AntiSpoof
from .config import SERVICE_VERSION, Settings
from .face import FaceEngine
from .imaging import BadImage, decode_image, probe_image
from .liveness import TURN_LABELS, FrameMeasure, evaluate, match_score, same_person_ok
from .ocr import OcrEngine
from .thai_id import extract_fields

log = logging.getLogger('ekyc')
BKK = timezone(timedelta(hours=7))   # ประเทศไทยไม่มี DST


def _h(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()[:16]


def today_bkk() -> date:
    return datetime.now(BKK).date()


def _incomplete(res: dict) -> bool:
    f = res['fields']
    return not (f['id_number'] and res['id_checksum_ok'] and f['name_th'] and f['birth_date']
                and (f['expiry_date'] or res['lifelong']))


class Engine:
    def __init__(self, s: Settings):
        self.s = s
        cv2.setNumThreads(max(1, s.torch_threads))
        t = time.perf_counter()
        self.face = FaceEngine(s.model_dir)
        self.spoof = AntiSpoof(s.model_dir)
        self.ocr = OcrEngine(s.model_dir, torch_threads=s.torch_threads,
                             det_width=int(os.environ.get('EKYC_OCR_DET_WIDTH', '640')))
        self.load_ms = int((time.perf_counter() - t) * 1000)
        manifest_path = os.path.join(s.model_dir, 'manifest.json')
        self.manifest = {}
        if os.path.isfile(manifest_path):
            with open(manifest_path, encoding='utf-8') as f:
                self.manifest = json.load(f)
        self.model_version = (f'ekyc-ai/{SERVICE_VERSION}+yunet2023mar+sface2021dec+mpfacelandmarker1'
                              f'+easyocr-th-g1+minifasnet-v2-v1se')

    def model_versions(self) -> dict:
        out = {k: v.get('version') for k, v in self.manifest.items()}
        out.setdefault('yunet', 'face_detection_yunet_2023mar')
        out.setdefault('sface', 'face_recognition_sface_2021dec')
        out['minifasnet_onnx'] = 'converted-from-pth@build'
        return out

    def warmup(self) -> None:
        """รันทุกโมเดลหนึ่งรอบตอนเริ่ม จะได้ไม่ช้าในคำขอแรก"""
        img = np.full((630, 1000, 3), 230, np.uint8)
        cv2.putText(img, '1 2345 67890 12 3', (300, 120), cv2.FONT_HERSHEY_SIMPLEX, 1.5, (20, 20, 20), 3)
        self.ocr.recognize(img, self.ocr.detect(img))
        self.face.detect(img)
        self.face.metrics(img)
        row = np.array([400, 200, 120, 150, 440, 250, 490, 250, 465, 280, 445, 310, 485, 310, 0.9], np.float32)
        self.face.embed(img, row)
        self.spoof.real_score(img, row)

    # ------------------------------------------------------------------ บัตร
    def _card_view(self, img: np.ndarray) -> C.CardView:
        view = C.locate_card(img)
        return C.orient_by_face(view, lambda im: self.face.detect(im, max_side=1000), self.s.card_face_score)

    def _unreadable_card(self, data: bytes, code: str) -> dict:
        """
        ภาพอ่านไม่ได้ (ไม่ใช่ภาพ/ไฟล์เสีย/ใหญ่เกิน 25 MP/เล็กเกิน) → ตอบ HTTP 200 + ok=false + NO_CARD
        (backend ถือว่า 4xx/5xx = AI ล่ม แล้วส่งแอดมินตรวจ — แต่กรณีนี้ผู้ใช้ควรถ่ายใหม่)
        """
        log.info('id_card unreadable image code=%s', code)
        none = {k: None for k in ('id_number', 'name_th', 'name_en', 'birth_date', 'expiry_date', 'issue_date')}
        return {
            'ok': False, 'error': 'BAD_IMAGE', 'error_detail': code, 'field': 'image',
            'card_detected': False,
            'quality': {'blur': 1.0, 'glare': 0.0, 'complete': False},
            'fields': none,
            'field_confidence': {k: 0.0 for k in none},
            'ocr_confidence': 0.0, 'id_checksum_ok': False, 'card_face_found': False, 'card_face_box': None,
            'card_real_score': 0.0, 'reasons': ['NO_CARD', 'NO_CARD_FACE'], 'model_version': self.model_version,
            'name_parts': {'th': None, 'en': None}, 'details': {'input_hash': _h(data)}, 'timing': {'total_ms': 0},
        }

    def id_card(self, data: bytes) -> dict:
        t0 = time.perf_counter()
        try:
            img = decode_image(data, self.s.max_pixels, max_side=None)   # ความละเอียดเต็ม (ลาย moiré ต้องใช้)
        except BadImage as e:
            return self._unreadable_card(data, e.code)
        view = self._card_view(img)
        del img
        cardimg = view.image
        H, W = cardimg.shape[:2]
        gray = cv2.cvtColor(cardimg, cv2.COLOR_BGR2GRAY)
        blur, lapvar = C.blur_score(gray)
        glare, glare_ratio = C.glare_score(cardimg)
        real, real_dbg = C.card_real_score(cardimg, glare, view.native_gray)
        t1 = time.perf_counter()

        boxes_all = self.ocr.detect(cardimg)
        card_like = view.quad_found or view.precropped
        now, later = self.ocr.split_priority(boxes_all, W, H, card_like)
        boxes = self.ocr.recognize(cardimg, now)
        res = extract_fields(boxes, W, H, today_bkk())
        second_pass = False
        if later and _incomplete(res):
            boxes += self.ocr.recognize(cardimg, later)
            res = extract_fields(boxes, W, H, today_bkk())
            second_pass = True
        t2 = time.perf_counter()

        f = res['fields']
        ocr_evidence = bool(f['id_number'] or f['name_th'] or f['birth_date'])
        card_detected = bool(view.quad_found or (view.precropped and (view.face is not None or ocr_evidence))
                             or (ocr_evidence and res['id_checksum_ok']))
        complete = bool(card_detected and view.complete)
        card_face_found = view.face is not None

        reasons: list[str] = []
        if not card_detected:
            reasons.append('NO_CARD')
        if blur >= self.s.blur_reason:
            reasons.append('BLURRY')
        if glare >= self.s.glare_reason:
            reasons.append('GLARE')
        if card_detected and not complete:
            reasons.append('CARD_INCOMPLETE')
        if res['ocr_confidence'] < self.s.ocr_low or not f['id_number']:
            reasons.append('OCR_LOW')
        reasons += res['reasons']
        if not card_face_found:
            reasons.append('NO_CARD_FACE')
        if card_detected and real < self.s.card_real_reason:
            reasons.append('SPOOF_SUSPECTED')

        timing = {'prep_ms': int((t1 - t0) * 1000), 'ocr_ms': int((t2 - t1) * 1000),
                  'total_ms': int((time.perf_counter() - t0) * 1000)}
        log.info('id_card total_ms=%d ocr_ms=%d boxes=%d second_pass=%s card=%s quad=%s blur=%.2f glare=%.2f '
                 'real=%.2f ocr=%.2f checksum=%s face=%s reasons=%s',
                 timing['total_ms'], timing['ocr_ms'], len(boxes), second_pass, card_detected, view.quad_found,
                 blur, glare, real, res['ocr_confidence'], res['id_checksum_ok'], card_face_found,
                 ','.join(reasons))
        return {
            'ok': True,
            'card_detected': card_detected,
            'quality': {'blur': round(blur, 3), 'glare': round(glare, 3), 'complete': complete},
            'fields': f,
            'field_confidence': res['field_confidence'],
            'ocr_confidence': res['ocr_confidence'],
            'id_checksum_ok': res['id_checksum_ok'],
            'card_face_found': card_face_found,
            # กรอบรูปหน้าบนบัตร [x, y, w, h] พิกเซลของ "ภาพที่ส่งมา" (ไม่ใช่ภาพที่ปรับมุมแล้ว) — ใช้ครอปเก็บให้แอดมิน
            'card_face_box': view.face_box_in_source(),
            'card_real_score': round(real, 3),
            'reasons': reasons,
            'model_version': self.model_version,
            'name_parts': res['name_parts'],
            'details': {
                'input_hash': _h(data),
                'quad_found': view.quad_found, 'precropped': view.precropped, 'rotation': view.rotation,
                'laplacian_var': round(lapvar, 1), 'glare_ratio': round(glare_ratio, 4), **real_dbg,
                'ocr_boxes': len(boxes), 'ocr_second_pass': second_pass,
            },
            'timing': timing,
        }

    # ------------------------------------------------------------ ใบหน้า
    def _card_face_embedding(self, data: bytes) -> tuple[np.ndarray | None, bool, list[int] | None]:
        """คืน (embedding ของรูปหน้าบนบัตร หรือ None, ภาพนี้ดูเป็นบัตรไหม, กรอบหน้า [x,y,w,h] บนภาพที่ส่งมา)"""
        ow, _oh = probe_image(data, self.s.max_pixels)
        img = decode_image(data, self.s.max_pixels, max_side=2400)
        back = ow / float(img.shape[1])          # สเกลกลับเป็นพิกเซลของภาพที่ส่งมา
        view = self._card_view(img)
        card_like = bool(view.quad_found or view.precropped)
        if view.face is not None:
            return self.face.embed(view.image, view.face), card_like, view.face_box_in_source(back)
        # สำรอง: หาหน้าที่ใหญ่สุดบนภาพเดิม (เผื่อหาขอบบัตรพลาด)
        k = min(1.0, 1600 / max(img.shape[:2]))
        im = img if k >= 1.0 else cv2.resize(img, None, fx=k, fy=k, interpolation=cv2.INTER_AREA)
        faces = self.face.detect(im, max_side=1600)
        faces = [fr for fr in faces if fr[14] >= self.s.card_face_score]
        if not faces:
            return None, card_like, None
        best = max(faces, key=lambda fr: fr[2] * fr[3])
        f = back / k
        box = [int(round(best[0] * f)), int(round(best[1] * f)), int(round(best[2] * f)), int(round(best[3] * f))]
        return self.face.embed(im, best), card_like, box

    def face_verify(self, card_data: bytes, frames: list[bytes], labels: list[str], mirrored: bool) -> dict:
        s = self.s
        t0 = time.perf_counter()
        # ภาพที่อ่านไม่ได้ไม่ทำให้ทั้งคำขอล้ม: บัตรเสีย = ไม่มีหน้าบนบัตร, เฟรมเสีย = เฟรมไม่มีหน้า
        # (ตอบ HTTP 200 + ok=false + reasons ให้ backend สั่งถ่ายใหม่ — 4xx/5xx = AI ล่ม = ส่งแอดมิน)
        bad_inputs: list[dict] = []
        card_emb, card_like, card_box = None, False, None
        try:
            card_emb, card_like, card_box = self._card_face_embedding(card_data)
        except BadImage as e:
            bad_inputs.append({'field': 'card_image', 'code': e.code})
        t1 = time.perf_counter()

        measures: list[FrameMeasure] = []
        embs: list[np.ndarray | None] = []
        for i, (data, label) in enumerate(zip(frames, labels)):
            try:
                img = decode_image(data, s.max_pixels, max_side=1280)
            except BadImage as e:
                bad_inputs.append({'field': f'frames[{i}]', 'code': e.code})
                measures.append(FrameMeasure(label=label, n_faces=0))
                embs.append(None)
                continue
            faces = self.face.detect(img, max_side=640)
            strong = [fr for fr in faces if fr[14] >= s.face_det_score]
            if strong:
                main = max(strong, key=lambda fr: fr[2] * fr[3])
                area = float(main[2] * main[3])
                n = sum(1 for fr in strong if fr[2] * fr[3] >= s.extra_face_min_rel * area)
            else:
                main, n = None, 0
            m = FrameMeasure(label=label, n_faces=n)
            emb = None
            if main is not None:
                fm = self.face.metrics(img)
                if fm.ok:
                    m.ok, m.ear, m.blink_bs, m.mouth_w = True, fm.ear, fm.blink_bs, fm.mouth_w
                    m.smile_bs, m.yaw, m.pitch = fm.smile_bs, fm.yaw, fm.pitch
                emb = self.face.embed(img, main)
                m.real = self.spoof.real_score(img, main)
            measures.append(m)
            embs.append(emb)
            del img
        t2 = time.perf_counter()

        # ความเหมือนกับเฟรม neutral
        e0 = embs[0]
        for i, m in enumerate(measures):
            if i > 0 and e0 is not None and embs[i] is not None:
                m.cos_to_neutral = round(self.face.cosine(e0, embs[i]), 4)

        reals = [m.real for m in measures if m.real is not None]
        # มัธยฐาน (ทนเฟรมผิดปกติเฟรมเดียว เช่นเฟรมหันข้าง/เบลอ) — ภาพจากจอ/รูปพิมพ์จะต่ำทุกเฟรมอยู่แล้ว
        real_score = float(np.median(reals)) if reals else 0.0
        lv = evaluate(measures, mirrored, s, real_score)

        # เฟรมที่ดีที่สุด (หน้าตรง ตาเปิด) สำหรับเทียบกับรูปบนบัตร
        best_idx = -1
        best_key = None
        ear0 = measures[0].ear if measures[0].ok else 0.0
        for i, m in enumerate(measures):
            if m.n_faces != 1 or embs[i] is None:
                continue
            eyes_open = (not m.ok) or ear0 <= 0 or m.ear >= 0.75 * ear0
            frontal = m.ok and abs(m.yaw) <= 20.0
            key = (1 if (frontal and eyes_open) else 0, 1 if m.label == 'neutral' else 0,
                   -abs(m.yaw) if m.ok else -90.0, -i)
            if best_key is None or key > best_key:
                best_key, best_idx = key, i
        cosine = self.face.cosine(card_emb, embs[best_idx]) if (best_idx >= 0 and card_emb is not None) else 0.0

        same_person = same_person_ok(measures, s)
        # คนละคน = มีหน้าให้เทียบ แต่ cosine กับเฟรม neutral ต่ำกว่าเกณฑ์ (แยกจากกรณีไม่เจอหน้า)
        mismatch = e0 is not None and any(
            m.cos_to_neutral is not None and m.cos_to_neutral < (
                s.same_person_cos_turn if m.label in TURN_LABELS else s.same_person_cos)
            for m in measures[1:])

        reasons: list[str] = []
        if card_emb is None:
            reasons.append('NO_CARD_FACE')
        reasons += lv.reasons
        if reals and real_score < s.real_threshold:
            reasons.append('SPOOF_SUSPECTED')
        if mismatch:
            reasons.append('DIFFERENT_PEOPLE')
        if card_emb is not None and best_idx >= 0 and cosine < s.match_cos:
            reasons.append('LOW_MATCH')

        faces_found = sum(1 for m in measures if m.n_faces == 1)
        timing = {'card_ms': int((t1 - t0) * 1000), 'frames_ms': int((t2 - t1) * 1000),
                  'total_ms': int((time.perf_counter() - t0) * 1000)}
        log.info('face_verify total_ms=%d frames=%d faces_found=%d same=%s live=%s live_score=%.2f real=%.2f '
                 'cos=%.3f best=%d mirrored=%s reasons=%s',
                 timing['total_ms'], len(frames), faces_found, same_person, lv.passed, lv.score, real_score,
                 cosine, best_idx, mirrored, ','.join(reasons))
        out = {
            'ok': not bad_inputs,
            'faces_found': faces_found,
            'same_person_across_frames': same_person,
            'liveness': {'passed': lv.passed, 'score': lv.score, 'challenges': lv.challenges},
            'anti_spoof': {'real_score': round(real_score, 3)},
            'match': {'cosine': round(float(cosine), 4), 'score': round(match_score(cosine), 3)},
            'best_frame_index': best_idx,
            'card_face_box': card_box,
            'reasons': reasons,
            'model_version': self.model_version,
            'mirrored': mirrored,
            'details': {
                'card_detected': card_like,
                # แฮชไบต์ของภาพ (16 ตัวแรกของ sha256) ให้ backend ใช้จับการส่งภาพชุดเดิมซ้ำข้ามเซสชัน
                'input_hashes': {'card_image': _h(card_data), 'frames': [_h(f) for f in frames]},
                'neutral_ok': lv.neutral_ok,
                'challenge_strength': lv.strengths,
                'frames': [{'label': m.label, 'faces': m.n_faces, 'landmarks': m.ok, 'yaw': round(m.yaw, 1),
                            'pitch': round(m.pitch, 1), 'ear': round(m.ear, 3), 'mouth_w': round(m.mouth_w, 3),
                            'blink_bs': round(m.blink_bs, 3), 'smile_bs': round(m.smile_bs, 3),
                            'real': None if m.real is None else round(m.real, 3),
                            'cos_to_neutral': m.cos_to_neutral} for m in measures],
            },
            'timing': timing,
        }
        if bad_inputs:
            out['error'] = 'BAD_IMAGE'
            out['bad_inputs'] = bad_inputs
        return out
