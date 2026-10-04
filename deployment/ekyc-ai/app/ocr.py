# -*- coding: utf-8 -*-
"""
OCR ด้วย EasyOCR (Apache-2.0) ภาษา ['th', 'en'] — CRAFT detector + Thai gen1 recognizer
โหลดจากไฟล์ในเครื่องเท่านั้น (download_enabled=False) — runtime ไม่ต่อเน็ต
ห้าม log ข้อความที่อ่านได้

ต้นทุนบน CPU (วัดด้วย torch FlopCounter): recognizer ภาษาไทย gen1 (ResNet + BiLSTM ~54M พารามิเตอร์)
≈ 76 GFLOP ต่อกล่องขนาด 64×384 px และโตตาม "ความกว้างรวม" ของกล่อง (ทุกกล่องถูกปรับให้สูง 64 px)
ส่วน CRAFT ≈ 290 GFLOP ที่ 800×512 — จึงเร่งด้วย:
1. หาตำแหน่งข้อความ (CRAFT) บนภาพที่ย่อเหลือกว้าง det_width (ค่าเริ่ม 640 px) แล้วขยายพิกัดกลับ
2. อ่านรอบแรกเฉพาะกล่องใน "โซนข้อมูลสำคัญ" ของบัตร (KEY_ZONES) — service จะอ่านกล่องที่เหลือ
   ก็ต่อเมื่อรอบแรกยังได้ฟิลด์บังคับไม่ครบ (เลขบัตร+checksum, ชื่อไทย, วันเกิด, วันหมดอายุ/ตลอดชีพ)
3. อ่านเป็นกลุ่มตามความกว้าง (EasyOCR pad ทุกภาพในชุดให้กว้างเท่าภาพที่กว้างสุด)
"""
from __future__ import annotations

import os
import threading

import cv2
import numpy as np

from .thai_id import Box


def _cluster_lines(boxes: list[list[int]]) -> list[list[list[int]]]:
    """จัดกล่อง [x0, x1, y0, y1] เป็นบรรทัด (บน→ล่าง): บรรทัดเดียวกันเมื่อซ้อนกันแนวตั้ง ≥ 50% ของกล่องที่เตี้ยกว่า"""
    lines: list[list[list[int]]] = []
    for b in sorted(boxes, key=lambda b: (b[2] + b[3]) / 2):
        if lines:
            ln = lines[-1]
            top, bot = min(x[2] for x in ln), max(x[3] for x in ln)
            overlap = min(bot, b[3]) - max(top, b[2])
            if overlap >= 0.5 * min(max(1, b[3] - b[2]), max(1, bot - top)):
                ln.append(b)
                continue
        lines.append([b])
    return lines


class OcrEngine:
    def __init__(self, model_dir: str, torch_threads: int = 2, det_width: int = 640):
        import torch
        torch.set_num_threads(max(1, torch_threads))
        import easyocr
        store = os.path.join(model_dir, 'easyocr')
        self._reader = easyocr.Reader(['th', 'en'], gpu=False, model_storage_directory=store,
                                      user_network_directory=store, download_enabled=False, verbose=False)
        self._lock = threading.Lock()
        self.det_width = det_width

    def detect(self, bgr: np.ndarray) -> list[list[int]]:
        """คืนกล่องข้อความแนวนอน [x0, x1, y0, y1] ในพิกัดของ bgr"""
        H, W = bgr.shape[:2]
        s = min(1.0, self.det_width / float(W))
        small = cv2.resize(bgr, (round(W * s), round(H * s)), interpolation=cv2.INTER_AREA) if s < 1.0 else bgr
        rgb_small = cv2.cvtColor(small, cv2.COLOR_BGR2RGB)
        with self._lock:
            hl, fl = self._reader.detect(rgb_small, canvas_size=1280, mag_ratio=1.0, text_threshold=0.6,
                                         low_text=0.35, link_threshold=0.4, width_ths=0.7, add_margin=0.1)
        boxes = []
        for x0, x1, y0, y1 in (hl[0] if hl else []):
            boxes.append([max(0, int(x0 / s)), min(W, int(x1 / s) + 1), max(0, int(y0 / s)), min(H, int(y1 / s) + 1)])
        # กล่องเอียง (free_list) → ใช้กรอบสี่เหลี่ยมล้อมรอบแทน (บัตรผ่านการปรับมุมแล้ว แทบไม่มี)
        for poly in (fl[0] if fl else []):
            xs = [float(p[0]) / s for p in poly]
            ys = [float(p[1]) / s for p in poly]
            boxes.append([max(0, int(min(xs))), min(W, int(max(xs)) + 1), max(0, int(min(ys))), min(H, int(max(ys)) + 1)])
        return [b for b in boxes if (b[1] - b[0]) >= 8 and (b[3] - b[2]) >= 6]

    # โซนข้อมูลสำคัญบนบัตรด้านหน้าที่ปรับมุมแล้ว (สัดส่วน x0, x1, y0, y1 ของ "จุดกึ่งกลางกล่อง")
    # อ้างอิงแม่แบบบัตรจริง (ROI ของ ThaiPersonalCardExtract, Apache-2.0) + เผื่อขอบ
    # นอกโซน (หัวบัตร, ป้าย "เลขประจำตัวประชาชน", ป้ายชื่อ, ศาสนา, ที่อยู่, "เจ้าพนักงานออกบัตร") = เลื่อนไว้
    KEY_ZONES = (
        (0.38, 1.00, 0.08, 0.26),   # เลขประจำตัวประชาชน
        (0.22, 1.00, 0.19, 0.34),   # ชื่อ-สกุลภาษาไทย
        (0.28, 0.85, 0.28, 0.46),   # Name / Last name
        (0.30, 0.80, 0.40, 0.60),   # เกิดวันที่ / Date of Birth
    )
    # ช่องล่าง: แต่ละคอลัมน์มี 4 บรรทัด = วันที่ไทย / ป้ายไทย / วันที่อังกฤษ / ป้ายอังกฤษ
    # อ่านรอบแรกเฉพาะบรรทัดที่ 1 และ 3 (เลือกตามลำดับบรรทัด ไม่ใช่พิกัดตายตัว — ทนการปรับมุมที่เพี้ยนเล็กน้อย)
    BOTTOM_COLUMNS = (
        (0.00, 0.28, 0.765, 0.99),  # วันออกบัตร
        (0.48, 0.80, 0.765, 0.99),  # วันบัตรหมดอายุ
    )
    PHOTO_ZONE = (0.72, 1.00, 0.40, 1.00)   # รูปถ่าย — ไม่อ่านเลย

    @classmethod
    def split_priority(cls, boxes: list[list[int]], W: int, H: int, card_view: bool) -> tuple[list, list]:
        """แยกกล่องเป็น (อ่านรอบแรก, เลื่อนไว้) — ใช้เฉพาะเมื่อภาพเป็นบัตรที่ปรับมุมแล้ว"""
        if not card_view:
            return boxes, []

        def inside(z, b):
            cx, cy = (b[0] + b[1]) / 2, (b[2] + b[3]) / 2
            return z[0] * W <= cx <= z[1] * W and z[2] * H <= cy <= z[3] * H

        now, later = [], []
        for b in boxes:
            if inside(cls.PHOTO_ZONE, b):
                continue
            if any(inside(z, b) for z in cls.KEY_ZONES):
                now.append(b)
            elif not any(inside(z, b) for z in cls.BOTTOM_COLUMNS):
                later.append(b)
        for z in cls.BOTTOM_COLUMNS:
            col = [b for b in boxes if inside(z, b) and not any(inside(k, b) for k in cls.KEY_ZONES)
                   and not inside(cls.PHOTO_ZONE, b)]
            lines = _cluster_lines(col)
            # ข้ามป้ายเฉพาะเมื่อแยกได้ครบ 4 บรรทัดพอดี — ไม่งั้นอ่านทั้งคอลัมน์ (ช้าขึ้นแต่ไม่พลาด)
            for i, ln in enumerate(lines):
                (now if (len(lines) != 4 or i in (0, 2)) else later).extend(ln)
        return now, later

    def recognize(self, bgr: np.ndarray, boxes: list[list[int]]) -> list[Box]:
        if not boxes:
            return []
        grey = cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY)

        def ratio(b):
            return (b[1] - b[0]) / max(1.0, b[3] - b[2])
        ordered = sorted(boxes, key=ratio)
        groups: list[list] = []
        for b in ordered:
            if groups and ratio(b) <= max(2.0, ratio(groups[-1][0]) * 1.6):
                groups[-1].append(b)
            else:
                groups.append([b])
        results = []
        with self._lock:
            for g in groups:
                results += self._reader.recognize(grey, horizontal_list=g, free_list=[], decoder='greedy',
                                                  batch_size=16, detail=1, paragraph=False)
        out: list[Box] = []
        for pts, text, conf in results:
            xs = [float(p[0]) for p in pts]
            ys = [float(p[1]) for p in pts]
            out.append(Box(str(text), float(conf), min(xs), min(ys), max(xs), max(ys)))
        return out
