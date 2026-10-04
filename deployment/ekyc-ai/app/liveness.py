# -*- coding: utf-8 -*-
"""
ตัดสิน challenge ของ liveness จากค่าวัดรายเฟรม (โค้ด Python ล้วน — ทดสอบได้โดยไม่ต้องโหลดโมเดล)

ทุก challenge เทียบกับเฟรม neutral (เฟรมแรก) ของคนเดียวกัน:
- blink      : EAR (eye aspect ratio) ลดลง ≥ 30% จาก neutral  หรือ  blendshape eyeBlink ≥ 0.5 และเพิ่มขึ้น ≥ 0.3
- smile      : ความกว้างปาก/ระยะหางตา เพิ่ม ≥ 10%            หรือ  blendshape mouthSmile ≥ 0.5 และเพิ่มขึ้น ≥ 0.25
- turn_left  : ผู้ใช้หันไปทาง "ซ้ายของตัวเอง" ≥ 15° (yaw)
- turn_right : ผู้ใช้หันไปทาง "ขวาของตัวเอง" ≥ 15°
- nod        : pitch เปลี่ยน ≥ 10° (ก้มหรือเงยก็ได้)

ทิศซ้าย/ขวา (mirror-aware):
  yaw_img ในเฟรม: < 0 = หน้าหันไปทาง "ซ้ายของภาพ"
  ภาพกล้องหน้าแบบกระจก (mirrored=True, ค่าเริ่มต้น — เหมือนส่องกระจก/พรีวิวเซลฟี):
      ผู้ใช้หันไปซ้ายของตัวเอง → หน้าในภาพหันไปซ้ายของภาพ → yaw_img ลดลง
  ภาพไม่กลับด้าน (mirrored=False — ภาพดิบจากเซนเซอร์):
      ผู้ใช้หันไปซ้ายของตัวเอง → หน้าในภาพหันไปขวาของภาพ → yaw_img เพิ่มขึ้น

ความแรง (strength) ต่อ challenge: วัดได้ / ค่าที่ต้องการ = x
  x < 1  → 0.8·x   (ยังไม่ผ่าน)
  x ≥ 1  → 0.8 + 0.2·min(1, x−1)   (ผ่านพอดี = 0.8, ทำได้ 2 เท่าของเกณฑ์ = 1.0)
liveness.score = ค่าเฉลี่ย strength × (1 ถ้าโครงสร้างผ่านทั้งหมด ไม่งั้น 0.5)
"""
from __future__ import annotations

from dataclasses import dataclass, field

CHALLENGES = ('blink', 'turn_left', 'turn_right', 'smile', 'nod')
VALID_LABELS = ('neutral',) + CHALLENGES
TURN_LABELS = ('turn_left', 'turn_right')


def strength(measured: float, required: float) -> float:
    if required <= 0:
        return 1.0
    x = measured / required
    if x < 1.0:
        return 0.8 * max(0.0, x)
    return min(1.0, 0.8 + 0.2 * min(1.0, x - 1.0))


@dataclass
class FrameMeasure:
    label: str
    n_faces: int                    # จำนวนหน้า (YuNet) ที่นับว่าเป็น "คน" ในเฟรม
    ok: bool = False                # MediaPipe ได้ landmark
    ear: float = 0.0
    blink_bs: float = 0.0
    mouth_w: float = 0.0
    smile_bs: float = 0.0
    yaw: float = 0.0
    pitch: float = 0.0
    real: float | None = None       # anti-spoof real prob
    cos_to_neutral: float | None = None


@dataclass
class LivenessResult:
    passed: bool
    score: float
    challenges: dict
    strengths: dict
    neutral_ok: bool
    reasons: list = field(default_factory=list)


def eval_challenge(label: str, m: FrameMeasure, m0: FrameMeasure, mirrored: bool, cfg) -> tuple[bool, float]:
    if not (m.ok and m0.ok):
        return False, 0.0
    if label == 'blink':
        drop = (1.0 - m.ear / m0.ear) if m0.ear > 1e-6 else 0.0
        need = 1.0 - cfg.blink_ear_ratio
        gain = m.blink_bs - m0.blink_bs
        bs_ok = m.blink_bs >= cfg.blink_blendshape and gain >= 0.30
        s = max(strength(drop, need), strength(gain, 0.30) if m.blink_bs >= cfg.blink_blendshape else 0.0)
        return (drop >= need) or bs_ok, s
    if label == 'smile':
        widen = (m.mouth_w / m0.mouth_w - 1.0) if m0.mouth_w > 1e-6 else 0.0
        need = cfg.smile_width_ratio - 1.0
        gain = m.smile_bs - m0.smile_bs
        bs_ok = m.smile_bs >= cfg.smile_blendshape and gain >= 0.25
        s = max(strength(widen, need), strength(gain, 0.25) if m.smile_bs >= cfg.smile_blendshape else 0.0)
        return (widen >= need) or bs_ok, s
    if label in TURN_LABELS:
        d_img = m.yaw - m0.yaw                       # > 0 = หันไปทางขวาของภาพมากขึ้น
        toward_user_left = -d_img if mirrored else d_img
        measured = toward_user_left if label == 'turn_left' else -toward_user_left
        return measured >= cfg.turn_deg, strength(measured, cfg.turn_deg)
    if label == 'nod':
        d = abs(m.pitch - m0.pitch)
        return d >= cfg.nod_deg, strength(d, cfg.nod_deg)
    return False, 0.0


def evaluate(frames: list[FrameMeasure], mirrored: bool, cfg, real_score: float) -> LivenessResult:
    reasons: list[str] = []
    m0 = frames[0]
    neutral_ok = bool(m0.label == 'neutral' and m0.n_faces == 1 and m0.ok
                      and abs(m0.yaw) <= cfg.neutral_max_angle and abs(m0.pitch) <= 40.0 and m0.ear >= 0.12)

    challenges: dict[str, bool] = {}
    strengths: dict[str, float] = {}
    for m in frames[1:]:
        if m.label == 'neutral':
            continue
        ok, s = (False, 0.0)
        if m.n_faces == 1 and neutral_ok:
            ok, s = eval_challenge(m.label, m, m0, mirrored, cfg)
        # label ซ้ำ: ผ่านถ้ามีเฟรมใดเฟรมหนึ่งผ่าน
        challenges[m.label] = challenges.get(m.label, False) or ok
        strengths[m.label] = max(strengths.get(m.label, 0.0), s)

    all_one_face = all(m.n_faces == 1 for m in frames)
    if any(m.n_faces == 0 for m in frames):
        reasons.append('NO_FACE')
    if any(m.n_faces > 1 for m in frames):
        reasons.append('MULTIPLE_FACES')
    for lab, ok in challenges.items():
        if not ok:
            reasons.append(f'CHALLENGE_FAILED:{lab}')

    same_person = same_person_ok(frames, cfg)
    structural = all_one_face and same_person and neutral_ok
    mean_s = (sum(strengths.values()) / len(strengths)) if strengths else 0.0
    score = mean_s * (1.0 if structural else 0.5)
    passed = bool(challenges) and all(challenges.values()) and structural and real_score >= cfg.real_threshold
    return LivenessResult(passed, round(score, 3), challenges, {k: round(v, 3) for k, v in strengths.items()},
                          neutral_ok, reasons)


def same_person_ok(frames: list[FrameMeasure], cfg) -> bool:
    """ทุกเฟรม (ยกเว้นเฟรมแรก) ต้องเป็นคนเดียวกับเฟรม neutral ตาม SFace cosine"""
    if frames[0].n_faces != 1:
        return False
    for m in frames[1:]:
        if m.n_faces != 1 or m.cos_to_neutral is None:
            return False
        need = cfg.same_person_cos_turn if m.label in TURN_LABELS else cfg.same_person_cos
        if m.cos_to_neutral < need:
            return False
    return True


def match_score(cosine: float) -> float:
    """
    แปลง SFace cosine → 0..1 ด้วย logistic ที่ศูนย์กลางเท่ากับ threshold แนะนำของ OpenCV (0.363):
        score = 1 / (1 + exp(−(cos − 0.363) / 0.06))
    cos 0.15 → 0.03 · 0.30 → 0.26 · 0.363 → 0.50 · 0.45 → 0.81 · 0.50 → 0.91 · 0.60 → 0.98
    (backend ตัดสินด้วยค่า cosine ดิบ — score มีไว้แสดงผล/เรียงลำดับ)
    """
    import math
    z = (cosine - 0.363) / 0.06
    z = max(-50.0, min(50.0, z))
    return 1.0 / (1.0 + math.exp(-z))
