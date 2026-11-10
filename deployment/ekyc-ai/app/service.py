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
from .config import DOC_TYPES, SERVICE_VERSION, Settings
from .face import FaceEngine
from .imaging import BadImage, decode_image, probe_image
from .liveness import TURN_LABELS, FrameMeasure, evaluate, match_score, same_person_ok
from .mrz import find_mrz
from .ocr import OcrEngine
from .tess import Tesseract, prep
from .thai_id import Box, complete, extract_fields, merge_results

log = logging.getLogger('ekyc')
BKK = timezone(timedelta(hours=7))   # ประเทศไทยไม่มี DST


def _h(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()[:16]


def today_bkk() -> date:
    return datetime.now(BKK).date()


FACE_SIDE = {'thai_id': 'right', 'thai_dl': 'left', 'passport': 'left'}
_EMPTY_FIELDS = ('id_number', 'name_th', 'name_en', 'birth_date', 'expiry_date', 'issue_date')


def _empty_result() -> dict:
    return {'fields': {k: None for k in _EMPTY_FIELDS}, 'license_no': None,
            'field_confidence': {k: 0.0 for k in _EMPTY_FIELDS}, 'ocr_confidence': 0.0, 'id_checksum_ok': False,
            'lifelong': False, 'name_parts': {'th': None, 'en': None}, 'reasons': []}


def _has_any(res: dict) -> bool:
    f = res['fields']
    return bool(f['id_number'] or f['name_th'] or f['birth_date'])


class Engine:
    def __init__(self, s: Settings):
        self.s = s
        cv2.setNumThreads(max(1, s.torch_threads))
        t = time.perf_counter()
        self.face = FaceEngine(s.model_dir)
        self.spoof = AntiSpoof(s.model_dir)
        self.ocr = OcrEngine(s.model_dir, torch_threads=s.torch_threads,
                             det_width=int(os.environ.get('EKYC_OCR_DET_WIDTH', '640')))
        self.tess = Tesseract(threads=s.tess_threads, timeout=s.tess_timeout_s)
        self.load_ms = int((time.perf_counter() - t) * 1000)
        manifest_path = os.path.join(s.model_dir, 'manifest.json')
        self.manifest = {}
        if os.path.isfile(manifest_path):
            with open(manifest_path, encoding='utf-8') as f:
                self.manifest = json.load(f)
        self.model_version = (f'ekyc-ai/{SERVICE_VERSION}+yunet2023mar+sface2021dec+mpfacelandmarker1'
                              f'+easyocr-th-g1+tesseract5-tha-eng+minifasnet-v2-v1se')

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
        if self.tess.available:
            self.tess.lines(prep(img)[0])
        else:
            log.warning('tesseract not available — OCR falls back to EasyOCR only')
        self.face.detect(img)
        self.face.metrics(img)
        row = np.array([400, 200, 120, 150, 440, 250, 490, 250, 465, 280, 445, 310, 485, 310, 0.9], np.float32)
        self.face.embed(img, row)
        self.spoof.real_score(img, row)

    # ------------------------------------------------------------------ บัตร
    def _card_view(self, img: np.ndarray, face_side: str = 'right') -> C.CardView:
        detect = lambda im: self.face.detect(im, max_side=1000)  # noqa: E731
        view = C.orient_by_face(C.locate_card(img), detect, self.s.card_face_score, face_side)
        if view.quad_found or view.precropped:
            return view
        # หาขอบบัตรไม่เจอในรูปแนวตั้ง → ลองตัดตามกรอบนำสายตาของแอป ใช้อันแรกที่เจอรูปหน้าบนบัตรครึ่งขวา
        # (ไม่งั้น OCR ทั้งรูป ตัวหนังสือบนบัตรเล็กเกิน + ตำแหน่งช่องข้อมูลเพี้ยน → อ่านได้ 0%)
        for cand in C.guide_crops(img):
            g = C.orient_by_face(cand, detect, self.s.card_face_score, face_side)
            if g.face is None:
                continue
            cx = (float(g.face[0]) + float(g.face[2]) / 2) / g.image.shape[1]
            fw = float(g.face[2]) / g.image.shape[1]
            side_ok = face_side == 'any' or (cx > 0.5 if face_side == 'right' else cx < 0.5)
            if side_ok and 0.04 <= fw <= 0.40:
                return g
        return view

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

    # ------------------------------------------------------------------ อ่านข้อความ
    # โซนเลขประจำตัวประชาชนบนบัตรประชาชนด้านหน้า (x0, x1, y0, y1) — อ่านซ้ำแบบตัวเลขล้วนเมื่อ checksum ไม่ผ่าน
    ID_ZONE = (0.36, 1.00, 0.06, 0.27)
    MRZ_WHITELIST = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<'

    def _read_thai(self, cardimg: np.ndarray, layout: str, card_like: bool) -> tuple[dict, dict]:
        """
        อ่านบัตรไทย: Tesseract ก่อน (เร็ว อ่านไทยได้ดีกว่า) → ครบแล้วจบ
        ไม่ครบ → อ่านเลขบัตรซ้ำแบบตัวเลขล้วน + EasyOCR แล้วรวมผลรายช่อง (เลือกค่าที่ตรวจสอบได้)
        """
        H, W = cardimg.shape[:2]
        today = today_bkk()
        stats = {'engines': [], 'ocr_boxes': 0, 'ocr_second_pass': False}
        results: list[dict] = []
        if self.tess.available:
            g, sc = prep(cardimg)
            tboxes = self.tess.lines(g, 'tha+eng', 11, scale=sc)
            results.append(extract_fields(tboxes, W, H, today, layout))
            stats['engines'].append('tesseract')
            stats['ocr_boxes'] += len(tboxes)
            if layout == 'thai_id' and not results[-1]['id_checksum_ok']:
                x0, x1, y0, y1 = self.ID_ZONE
                zone = cardimg[int(y0 * H):int(y1 * H), int(x0 * W):int(x1 * W)]
                zg, _ = prep(zone, target_h=110)
                text, conf = self.tess.line_text(zg, 'eng', whitelist='0123456789 ', psm=7)
                if text:
                    # ใส่เป็นกล่องในโซนเลขบัตร แล้วให้ตัวแยกข้อมูลเลือกเลขที่ checksum ผ่าน
                    extra = Box(text, conf, x0 * W, y0 * H, x1 * W, y1 * H)
                    results.append(extract_fields(tboxes + [extra], W, H, today, layout))
        merged = merge_results(results, today) if results else _empty_result()
        if complete(merged):
            return merged, stats

        boxes_all = self.ocr.detect(cardimg)
        now, later = self.ocr.split_priority(boxes_all, W, H, card_like and layout == 'thai_id')
        eboxes = self.ocr.recognize(cardimg, now)
        eres = extract_fields(eboxes, W, H, today, layout)
        if later and not complete(merge_results(results + [eres], today)):
            eboxes += self.ocr.recognize(cardimg, later)
            eres = extract_fields(eboxes, W, H, today, layout)
            stats['ocr_second_pass'] = True
        stats['engines'].append('easyocr')
        stats['ocr_boxes'] += len(eboxes)
        return merge_results(results + [eres], today), stats

    def _mrz_lines(self, im: np.ndarray) -> list[str]:
        g, _ = prep(im, target_h=max(300, min(1400, im.shape[0] * 2)))
        tsv = self.tess.run_tsv(g, 'eng', 6, ['-c', f'tessedit_char_whitelist={self.MRZ_WHITELIST}'])
        return [r[0] for r in sorted(self.tess.parse_tsv(tsv), key=lambda r: r[3])]

    def _read_mrz(self, cardimg: np.ndarray, full: np.ndarray | None) -> tuple[dict, dict | None]:
        """
        หา MRZ: ครึ่งล่างของหน้าเอกสารที่ปรับมุมแล้ว → ทั้งหน้า → ภาพเต็ม (เผื่อหาขอบเอกสารพลาด) และหมุน 180°
        คืน (ผลรูปแบบเดียวกับบัตรไทย, รายละเอียด MRZ หรือ None)
        """
        res = _empty_result()
        if not self.tess.available:
            return res, None
        today = today_bkk()
        best = None
        tries = [cardimg[int(cardimg.shape[0] * 0.55):], cardimg]
        if full is not None:
            tries.append(full[int(full.shape[0] * 0.35):])
        for im in tries:
            for rot in (False, True):
                m = find_mrz(self._mrz_lines(cv2.rotate(im, cv2.ROTATE_180) if rot else im), today)
                if m and (best is None or (m.valid, m.score) > (best.valid, best.score)):
                    best = m
                if best is not None and best.valid:
                    break
            if best is not None and best.valid:
                break
        if best is None:
            return res, None
        name = ' '.join(x for x in (best.given_names, best.surname) if x) or None
        # check digit ผ่านครบ = มั่นใจเต็ม (ข้อมูลถูกยืนยันด้วยคณิตศาสตร์ ไม่ใช่แค่ OCR มั่นใจ)
        conf = 1.0 if best.valid else round(best.score * 0.6, 3)
        res['fields'].update({
            'id_number': best.document_number or None,
            'name_en': name,
            'birth_date': best.birth_date.isoformat() if best.birth_date else None,
            'expiry_date': best.expiry_date.isoformat() if best.expiry_date else None,
        })
        res['field_confidence'].update({'id_number': conf, 'name_en': conf, 'birth_date': conf,
                                        'expiry_date': conf, 'name_th': conf})
        res['ocr_confidence'] = conf
        res['id_checksum_ok'] = best.valid
        res['name_parts'] = {'th': None, 'en': {'title': None, 'first': best.given_names, 'last': best.surname}}
        if not best.valid:
            res['reasons'].append('MRZ_CHECK_FAIL')
        elif best.expiry_date and best.expiry_date < today:
            res['reasons'].append('EXPIRED')
        return res, best.to_dict()

    def id_card(self, data: bytes, doc_type: str = 'thai_id') -> dict:
        t0 = time.perf_counter()
        doc_type = doc_type if doc_type in DOC_TYPES else 'thai_id'
        try:
            img = decode_image(data, self.s.max_pixels, max_side=None)   # ความละเอียดเต็ม (ลาย moiré ต้องใช้)
        except BadImage as e:
            return self._unreadable_card(data, e.code)
        view = self._card_view(img, FACE_SIDE.get(doc_type, 'any'))
        full = None
        if doc_type in ('passport', 'foreign_id') and not view.quad_found:
            k = min(1.0, 2000 / max(img.shape[:2]))
            full = img if k >= 1.0 else cv2.resize(img, None, fx=k, fy=k, interpolation=cv2.INTER_AREA)
        del img
        cardimg = view.image
        gray = cv2.cvtColor(cardimg, cv2.COLOR_BGR2GRAY)
        blur, lapvar = C.blur_score(gray)
        glare, glare_ratio = C.glare_score(cardimg)
        real, real_dbg = C.card_real_score(cardimg, glare, view.native_gray)
        t1 = time.perf_counter()

        card_like = view.quad_found or view.precropped
        mrz = None
        stats = {'engines': [], 'ocr_boxes': 0, 'ocr_second_pass': False}
        if doc_type in ('thai_id', 'thai_dl'):
            res, stats = self._read_thai(cardimg, doc_type, card_like)
        elif doc_type in ('passport', 'foreign_id'):
            res, mrz = self._read_mrz(cardimg, full)
            stats['engines'].append('tesseract-mrz')
        else:
            res = _empty_result()
        del full
        t2 = time.perf_counter()

        f = res['fields']
        ocr_evidence = _has_any(res) or bool(mrz)
        card_detected = bool(view.quad_found or (view.precropped and (view.face is not None or ocr_evidence))
                             or (ocr_evidence and res['id_checksum_ok']))
        complete_ = bool(card_detected and view.complete)
        card_face_found = view.face is not None

        reasons: list[str] = []
        if not card_detected:
            reasons.append('NO_CARD')
        if blur >= self.s.blur_reason:
            reasons.append('BLURRY')
        if glare >= self.s.glare_reason:
            reasons.append('GLARE')
        if card_detected and not complete_:
            reasons.append('CARD_INCOMPLETE')
        if doc_type in ('foreign_dl', 'other') or (doc_type == 'foreign_id' and not (mrz and mrz['valid'])):
            # ยังไม่มีตัวอ่านอัตโนมัติสำหรับเอกสารนี้ → ผู้ใช้กรอกเอง + แอดมินตรวจจากรูป (ไม่ใช่ให้ถ่ายใหม่)
            reasons.append('DOC_MANUAL_REVIEW')
        elif res['ocr_confidence'] < self.s.ocr_low or not f['id_number']:
            reasons.append('OCR_LOW')
        reasons += res['reasons']
        if not card_face_found:
            reasons.append('NO_CARD_FACE')
        if card_detected and real < self.s.card_real_reason:
            reasons.append('SPOOF_SUSPECTED')

        timing = {'prep_ms': int((t1 - t0) * 1000), 'ocr_ms': int((t2 - t1) * 1000),
                  'total_ms': int((time.perf_counter() - t0) * 1000)}
        log.info('id_card doc=%s total_ms=%d ocr_ms=%d engines=%s boxes=%d card=%s quad=%s blur=%.2f glare=%.2f '
                 'real=%.2f ocr=%.2f checksum=%s face=%s reasons=%s',
                 doc_type, timing['total_ms'], timing['ocr_ms'], '+'.join(stats['engines']), stats['ocr_boxes'],
                 card_detected, view.quad_found, blur, glare, real, res['ocr_confidence'], res['id_checksum_ok'],
                 card_face_found, ','.join(reasons))
        document = {'type': doc_type, 'country': 'THA' if doc_type in ('thai_id', 'thai_dl') else None,
                    'number': f['id_number'], 'license_no': res.get('license_no'), 'nationality': None,
                    'sex': None, 'mrz': mrz}
        if mrz:
            document.update({'country': mrz['issuing_country'] or None, 'nationality': mrz['nationality'] or None,
                             'sex': mrz['sex']})
        return {
            'ok': True,
            'doc_type': doc_type,
            'document': document,
            'card_detected': card_detected,
            'quality': {'blur': round(blur, 3), 'glare': round(glare, 3), 'complete': complete_},
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
                'ocr_boxes': stats['ocr_boxes'], 'ocr_second_pass': stats['ocr_second_pass'],
                'ocr_engines': stats['engines'],
            },
            'timing': timing,
        }

    # ------------------------------------------------------------ ใบหน้า
    def _card_face_embedding(self, data: bytes) -> tuple[np.ndarray | None, bool, list[int] | None]:
        """คืน (embedding ของรูปหน้าบนบัตร หรือ None, ภาพนี้ดูเป็นบัตรไหม, กรอบหน้า [x,y,w,h] บนภาพที่ส่งมา)"""
        ow, _oh = probe_image(data, self.s.max_pixels)
        img = decode_image(data, self.s.max_pixels, max_side=2400)
        back = ow / float(img.shape[1])          # สเกลกลับเป็นพิกเซลของภาพที่ส่งมา
        view = self._card_view(img, 'any')
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
