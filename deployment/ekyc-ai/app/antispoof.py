# -*- coding: utf-8 -*-
"""
Passive anti-spoof ด้วย MiniFASNet (Silent-Face-Anti-Spoofing, Minivision, Apache-2.0)

ใช้ 2 โมเดลตามต้นฉบับ แล้วเฉลี่ย softmax:
- 2.7_80x80_MiniFASNetV2   : ครอปกรอบหน้า ×2.7 → 80×80
- 4_0_0_80x80_MiniFASNetV1SE: ครอปกรอบหน้า ×4.0 → 80×80
อินพุต = BGR 0-255 float (ต้นฉบับไม่หาร 255), class 1 = ใบหน้าจริง
real_score = ค่าเฉลี่ยความน่าจะเป็น class 1 ของสองโมเดล (0..1)

ข้อต่างจากต้นฉบับ: ต้นฉบับใช้ตัวตรวจหน้า RetinaFace (Caffe) — ที่นี่ใช้กรอบจาก YuNet แทน
(ลองกับภาพตัวอย่างของ repo ต้นฉบับแล้วได้ผลตรง: ภาพจริง ≈1.00, ภาพปลอม 0.23 / 0.003)
"""
from __future__ import annotations

import os
import threading

import cv2
import numpy as np
import onnxruntime as ort

MODELS = (('2.7_80x80_MiniFASNetV2.onnx', 2.7), ('4_0_0_80x80_MiniFASNetV1SE.onnx', 4.0))


def _new_box(src_w: int, src_h: int, bbox, scale: float) -> tuple[int, int, int, int]:
    """ขยายกรอบหน้ารอบจุดกลาง (ตาม CropImage._get_new_box ของต้นฉบับ) แล้วเลื่อนให้อยู่ในภาพ"""
    x, y, box_w, box_h = [float(v) for v in bbox]
    box_w = max(box_w, 1.0)
    box_h = max(box_h, 1.0)
    scale = min((src_h - 1) / box_h, min((src_w - 1) / box_w, scale))
    new_w, new_h = box_w * scale, box_h * scale
    cx, cy = box_w / 2 + x, box_h / 2 + y
    l, t = cx - new_w / 2, cy - new_h / 2
    r, b = cx + new_w / 2, cy + new_h / 2
    if l < 0:
        r -= l
        l = 0
    if t < 0:
        b -= t
        t = 0
    if r > src_w - 1:
        l -= r - src_w + 1
        r = src_w - 1
    if b > src_h - 1:
        t -= b - src_h + 1
        b = src_h - 1
    return int(max(0, l)), int(max(0, t)), int(r), int(b)


class AntiSpoof:
    def __init__(self, model_dir: str):
        so = ort.SessionOptions()
        so.intra_op_num_threads = 1
        so.inter_op_num_threads = 1
        so.log_severity_level = 3
        self._sessions = []
        for name, scale in MODELS:
            path = os.path.join(model_dir, 'minifasnet', name)
            sess = ort.InferenceSession(path, sess_options=so, providers=['CPUExecutionProvider'])
            self._sessions.append((sess, scale))
        self._lock = threading.Lock()

    def real_score(self, img: np.ndarray, face_row: np.ndarray) -> float:
        h, w = img.shape[:2]
        bbox = face_row[:4]
        probs = np.zeros(3, dtype=np.float64)
        with self._lock:
            for sess, scale in self._sessions:
                l, t, r, b = _new_box(w, h, bbox, scale)
                patch = img[t:b + 1, l:r + 1]
                if patch.size == 0:
                    return 0.0
                patch = cv2.resize(patch, (80, 80))
                x = patch.transpose(2, 0, 1)[None].astype(np.float32)
                logits = sess.run(None, {'input': x})[0][0].astype(np.float64)
                e = np.exp(logits - logits.max())
                probs += e / e.sum()
        return float(probs[1] / len(self._sessions))
