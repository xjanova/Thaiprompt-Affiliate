# -*- coding: utf-8 -*-
"""
ถอดรหัสรูปอย่างปลอดภัย + ย่อขนาด

- รับเฉพาะ JPEG / PNG / WebP (เช็ค magic bytes ก่อน)
- อ่านขนาดจาก header ด้วย Pillow (ยังไม่ถอดรหัสพิกเซล) → เกิน max_pixels ปฏิเสธทันที
- ถอดรหัสจริงด้วย OpenCV (IMREAD_COLOR หมุนตาม EXIF orientation ให้อัตโนมัติ)
- ไม่มีการ log เนื้อหารูปเด็ดขาด
"""
from __future__ import annotations

import io

from . import config  # noqa: F401  (ตั้ง OPENCV_IO_MAX_IMAGE_PIXELS ก่อน import cv2)
import cv2
import numpy as np
from PIL import Image

# Pillow มีเกราะ decompression bomb ของตัวเอง — ตั้งให้สูงกว่าขีดของเรานิดหน่อย เราเช็คเองอยู่แล้ว
Image.MAX_IMAGE_PIXELS = 40_000_000


class BadImage(Exception):
    """รูปใช้ไม่ได้ — code เป็นรหัสสั้นๆ ที่ปลอดภัยต่อการส่งกลับ (ไม่มีข้อมูลรูปปน)"""

    def __init__(self, code: str):
        super().__init__(code)
        self.code = code


def _sniff(data: bytes) -> str | None:
    if data[:3] == b'\xff\xd8\xff':
        return 'jpeg'
    if data[:8] == b'\x89PNG\r\n\x1a\n':
        return 'png'
    if data[:4] == b'RIFF' and data[8:12] == b'WEBP':
        return 'webp'
    return None


def probe_image(data: bytes, max_pixels: int) -> tuple[int, int]:
    """ตรวจชนิดไฟล์ + ขนาดจาก header (ไม่ถอดรหัสพิกเซล) คืน (w, h)"""
    if not data:
        raise BadImage('EMPTY_IMAGE')
    if _sniff(data) is None:
        raise BadImage('UNSUPPORTED_FORMAT')
    try:
        with Image.open(io.BytesIO(data)) as im:
            w, h = im.size
    except Exception:  # noqa: BLE001 — header เสีย
        raise BadImage('CORRUPT_IMAGE') from None
    if w <= 0 or h <= 0:
        raise BadImage('CORRUPT_IMAGE')
    if w * h > max_pixels:
        raise BadImage('IMAGE_TOO_MANY_PIXELS')
    if min(w, h) < 64:
        raise BadImage('IMAGE_TOO_SMALL')
    return w, h


def decode_image(data: bytes, max_pixels: int, max_side: int | None = None) -> np.ndarray:
    """bytes → ภาพ BGR uint8 (H, W, 3) — ย่อให้ด้านยาวไม่เกิน max_side ถ้าระบุ"""
    probe_image(data, max_pixels)
    buf = np.frombuffer(data, dtype=np.uint8)
    try:
        img = cv2.imdecode(buf, cv2.IMREAD_COLOR)
    except cv2.error:
        img = None
    if img is None or img.ndim != 3 or img.shape[2] != 3:
        raise BadImage('CORRUPT_IMAGE')
    if max_side:
        img = resize_max(img, max_side)
    return img


def resize_max(img: np.ndarray, max_side: int) -> np.ndarray:
    h, w = img.shape[:2]
    m = max(h, w)
    if m <= max_side:
        return img
    s = max_side / float(m)
    return cv2.resize(img, (max(1, round(w * s)), max(1, round(h * s))), interpolation=cv2.INTER_AREA)
