# -*- coding: utf-8 -*-
"""
ค่าตั้งของบริการ eKYC AI — อ่านจาก environment ครั้งเดียวตอนเริ่ม

ทุก threshold ปรับได้ผ่าน env โดยไม่ต้อง build image ใหม่ (docker run -e ...)
"""
from __future__ import annotations

import os
from dataclasses import dataclass, field

SERVICE_VERSION = '1.0.0'

# จำกัดจำนวนพิกเซลที่ OpenCV ยอมถอดรหัส (กัน decompression bomb) — ต้องตั้งก่อน import cv2
os.environ.setdefault('OPENCV_IO_MAX_IMAGE_PIXELS', str(25_000_000))


def _f(name: str, default: float) -> float:
    try:
        return float(os.environ.get(name, default))
    except ValueError:
        return float(default)


def _i(name: str, default: int) -> int:
    try:
        return int(os.environ.get(name, default))
    except ValueError:
        return int(default)


def _b(name: str, default: bool) -> bool:
    v = os.environ.get(name)
    if v is None:
        return default
    return v.strip().lower() in ('1', 'true', 'yes', 'on')


@dataclass(frozen=True)
class Settings:
    # --- ความปลอดภัย / ขีดจำกัด ---
    api_key: str = ''
    dev_mode: bool = False
    model_dir: str = '/opt/ekyc/models'
    max_image_bytes: int = 8 * 1024 * 1024
    max_frames: int = 8
    max_pixels: int = 25_000_000
    request_timeout_s: float = 23.0      # backend ตั้ง timeout 25 วิ — ตอบ 504 ก่อนเล็กน้อย
    workers: int = 2              # จำนวนเธรดประมวลผลพร้อมกัน
    max_queue: int = 4            # คิวรอเพิ่มได้อีกกี่งาน (เกิน = 503 BUSY)
    torch_threads: int = 2

    # --- กล้องหน้า: ภาพเป็นแบบกระจก (mirrored) หรือไม่ ---
    frames_mirrored: bool = True

    # --- liveness ---
    blink_ear_ratio: float = 0.70       # EAR ของเฟรมกะพริบ ≤ 0.70 × EAR ตอน neutral
    blink_blendshape: float = 0.50      # หรือ eyeBlink blendshape ≥ 0.5 (และเพิ่มจาก neutral ≥ 0.3)
    smile_width_ratio: float = 1.10     # ความกว้างปาก/ระยะตา เพิ่ม ≥ 10% จาก neutral
    smile_blendshape: float = 0.50      # หรือ mouthSmile blendshape ≥ 0.5 (และเพิ่มจาก neutral ≥ 0.25)
    turn_deg: float = 15.0              # หันซ้าย/ขวา: yaw เปลี่ยน ≥ 15° ในทิศที่ถูก
    nod_deg: float = 10.0               # พยักหน้า: pitch เปลี่ยน ≥ 10° (ก้มหรือเงย)
    neutral_max_angle: float = 25.0     # เฟรม neutral ต้องหันตรง |yaw|,|pitch| ≤ 25°
    same_person_cos: float = 0.45       # SFace cosine ระหว่างเฟรมกับเฟรม neutral
    same_person_cos_turn: float = 0.35  # ผ่อนให้เฟรมที่หันหน้า (มุมเปลี่ยน cosine ตก)
    real_threshold: float = 0.50        # anti-spoof real_score ขั้นต่ำให้ liveness ผ่าน
    face_det_score: float = 0.80        # YuNet score ขั้นต่ำของหน้าในเฟรมเซลฟี
    extra_face_min_rel: float = 0.15    # หน้าอื่นที่ใหญ่ ≥ 15% ของหน้าหลัก (พื้นที่) นับเป็น "หลายคน"

    # --- face match ---
    match_cos: float = 0.363            # threshold แนะนำของ OpenCV SFace (same identity)

    # --- บัตร ---
    card_face_score: float = 0.60
    ocr_low: float = 0.60               # ocr_confidence ต่ำกว่านี้ → OCR_LOW
    blur_reason: float = 0.70           # quality.blur ≥ ค่านี้ → BLURRY
    glare_reason: float = 0.50          # quality.glare ≥ ค่านี้ → GLARE
    card_real_reason: float = 0.50      # (ข้อมูล) card_real_score ต่ำกว่านี้ถือว่าน่าสงสัย

    extra: dict = field(default_factory=dict)


def load_settings() -> Settings:
    return Settings(
        api_key=os.environ.get('EKYC_AI_KEY', ''),
        dev_mode=_b('EKYC_AI_DEV', False),
        model_dir=os.environ.get('EKYC_MODEL_DIR', '/opt/ekyc/models'),
        max_image_bytes=_i('EKYC_MAX_IMAGE_BYTES', 8 * 1024 * 1024),
        max_frames=_i('EKYC_MAX_FRAMES', 8),
        max_pixels=_i('EKYC_MAX_PIXELS', 25_000_000),
        request_timeout_s=_f('EKYC_REQUEST_TIMEOUT', 23.0),
        workers=max(1, _i('EKYC_WORKERS', 2)),
        max_queue=max(0, _i('EKYC_MAX_QUEUE', 4)),
        torch_threads=max(1, _i('EKYC_TORCH_THREADS', 2)),
        frames_mirrored=_b('EKYC_FRAMES_MIRRORED', True),
        blink_ear_ratio=_f('EKYC_BLINK_EAR_RATIO', 0.70),
        blink_blendshape=_f('EKYC_BLINK_BLENDSHAPE', 0.50),
        smile_width_ratio=_f('EKYC_SMILE_WIDTH_RATIO', 1.10),
        smile_blendshape=_f('EKYC_SMILE_BLENDSHAPE', 0.50),
        turn_deg=_f('EKYC_TURN_DEG', 15.0),
        nod_deg=_f('EKYC_NOD_DEG', 10.0),
        neutral_max_angle=_f('EKYC_NEUTRAL_MAX_ANGLE', 25.0),
        same_person_cos=_f('EKYC_SAME_PERSON_COS', 0.45),
        same_person_cos_turn=_f('EKYC_SAME_PERSON_COS_TURN', 0.35),
        real_threshold=_f('EKYC_REAL_THRESHOLD', 0.50),
        face_det_score=_f('EKYC_FACE_DET_SCORE', 0.80),
        extra_face_min_rel=_f('EKYC_EXTRA_FACE_MIN_REL', 0.15),
        match_cos=_f('EKYC_MATCH_COS', 0.363),
        card_face_score=_f('EKYC_CARD_FACE_SCORE', 0.60),
        ocr_low=_f('EKYC_OCR_LOW', 0.60),
        blur_reason=_f('EKYC_BLUR_REASON', 0.70),
        glare_reason=_f('EKYC_GLARE_REASON', 0.50),
        card_real_reason=_f('EKYC_CARD_REAL_REASON', 0.50),
    )


def validate_settings(s: Settings) -> None:
    """ไม่มีคีย์ = ไม่ยอมเริ่ม (ยกเว้นโหมด dev) — กันเผลอเปิดบริการแบบไม่มีการยืนยันตัวตน"""
    if s.dev_mode:
        return
    if not s.api_key:
        raise SystemExit('EKYC_AI_KEY is required (set EKYC_AI_DEV=1 only for local development)')
    if len(s.api_key) < 16:
        raise SystemExit('EKYC_AI_KEY must be at least 16 characters')
