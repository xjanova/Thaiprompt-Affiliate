# -*- coding: utf-8 -*-
"""
ใบหน้า: YuNet (ตรวจหน้า, MIT) + SFace (embedding 128 มิติ, Apache-2.0) ผ่าน OpenCV
และ MediaPipe Face Landmarker (478 จุด + blendshape + เมทริกซ์ท่าศีรษะ, Apache-2.0)

ทุกโมเดลโหลดครั้งเดียว ใช้ร่วมกันหลายเธรดได้ด้วย lock ต่อโมเดล
(FaceDetectorYN มี state ขนาดภาพ, FaceLandmarker ห้ามเรียกพร้อมกัน)

ทิศทางท่าศีรษะ (สำคัญ): yaw_img < 0 = จมูก/หน้าหันไปทาง "ซ้ายของภาพ"
ซึ่งเป็นพิกัดของภาพตามที่ได้รับ (ไม่สนว่าภาพกลับด้านแบบกระจกหรือไม่) — การแปลงเป็น
ซ้าย/ขวาของ "ผู้ใช้" ทำใน liveness.py ตามค่า mirrored
"""
from __future__ import annotations

import math
import os
import threading
from dataclasses import dataclass

import cv2
import numpy as np

# ดัชนี landmark ของ MediaPipe Face Mesh (478 จุด)
# "R_" = ตาขวาของเจ้าของหน้า (อยู่ซ้ายของภาพเมื่อภาพไม่กลับด้าน)
R_EYE = (33, 160, 158, 133, 153, 144)     # p1..p6 สำหรับ EAR
L_EYE = (362, 385, 387, 263, 373, 380)
MOUTH_L, MOUTH_R = 61, 291                # มุมปาก
LIP_TOP, LIP_BOTTOM = 13, 14              # ริมฝีปากด้านใน
EYE_OUTER_R, EYE_OUTER_L = 33, 263
NOSE_TIP = 1
CHEEK_R, CHEEK_L = 234, 454               # ขอบหน้าซ้าย/ขวาของภาพ (ภาพไม่กลับด้าน)


@dataclass
class FaceMetrics:
    ok: bool                       # landmarker เจอหน้า
    n_faces_mp: int = 0
    ear: float = 0.0               # eye aspect ratio เฉลี่ยสองตา
    blink_bs: float = 0.0          # blendshape eyeBlink เฉลี่ย
    mouth_w: float = 0.0           # ความกว้างปาก / ระยะหางตาสองข้าง
    mouth_h: float = 0.0           # ความสูงปากด้านใน / ระยะหางตา
    smile_bs: float = 0.0          # blendshape mouthSmile เฉลี่ย
    yaw: float = 0.0               # องศา, < 0 = หันไปซ้ายของภาพ
    pitch: float = 0.0             # องศา (ก้ม/เงย — ใช้แค่ขนาดการเปลี่ยน)
    roll: float = 0.0
    nose_offset: float = 0.0       # ตัวชี้วัดเชิงเรขาคณิตสำรอง (-0.5..0.5), < 0 = จมูกไปซ้ายของภาพ


def _dist(a, b) -> float:
    return float(math.hypot(a[0] - b[0], a[1] - b[1]))


def _ear(p) -> float:
    p1, p2, p3, p4, p5, p6 = p
    den = 2.0 * _dist(p1, p4)
    return (_dist(p2, p6) + _dist(p3, p5)) / den if den > 1e-6 else 0.0


def rotation_to_euler(r: np.ndarray) -> tuple[float, float, float]:
    """เมทริกซ์หมุน 3×3 → (yaw, pitch, roll) องศา แบบ R = Ry(yaw)·Rx(pitch)·Rz(roll)"""
    yaw = math.degrees(math.atan2(r[0, 2], r[2, 2]))
    pitch = math.degrees(math.asin(max(-1.0, min(1.0, -r[1, 2]))))
    roll = math.degrees(math.atan2(r[1, 0], r[1, 1]))
    return yaw, pitch, roll


class FaceEngine:
    # เครื่องหมาย yaw ของเมทริกซ์ MediaPipe เทียบกับ "ซ้ายของภาพ" — ยืนยันด้วยเทสต์
    # (tests/test_face_pipeline.py::test_yaw_sign_matches_geometry) บนภาพจริงที่พลิกซ้ายขวา
    YAW_SIGN = 1.0

    def __init__(self, model_dir: str):
        self.yunet_path = os.path.join(model_dir, 'opencv', 'face_detection_yunet_2023mar.onnx')
        self.sface_path = os.path.join(model_dir, 'opencv', 'face_recognition_sface_2021dec.onnx')
        self.lm_path = os.path.join(model_dir, 'mediapipe', 'face_landmarker.task')
        self._det = cv2.FaceDetectorYN.create(self.yunet_path, '', (320, 320), 0.5, 0.3, 5000)
        self._rec = cv2.FaceRecognizerSF.create(self.sface_path, '')
        self._det_lock = threading.Lock()
        self._rec_lock = threading.Lock()
        self._lm_lock = threading.Lock()

        from mediapipe.tasks.python import BaseOptions
        from mediapipe.tasks.python.vision import FaceLandmarker, FaceLandmarkerOptions, RunningMode
        import mediapipe as mp
        self._mp = mp
        with open(self.lm_path, 'rb') as f:
            buf = f.read()
        opts = FaceLandmarkerOptions(
            base_options=BaseOptions(model_asset_buffer=buf),
            running_mode=RunningMode.IMAGE,
            num_faces=2,
            min_face_detection_confidence=0.5,
            min_face_presence_confidence=0.5,
            output_face_blendshapes=True,
            output_facial_transformation_matrixes=True,
        )
        self._lm = FaceLandmarker.create_from_options(opts)

    # ------------------------------------------------------------------ YuNet
    def detect(self, img: np.ndarray, max_side: int = 640) -> np.ndarray:
        """คืน array (N, 15): x, y, w, h, 5 จุด landmark (x,y), score — พิกัดของ img ที่ส่งเข้ามา"""
        h, w = img.shape[:2]
        s = min(1.0, max_side / float(max(h, w)))
        im = cv2.resize(img, (max(1, round(w * s)), max(1, round(h * s))), interpolation=cv2.INTER_AREA) \
            if s < 1.0 else img
        with self._det_lock:
            self._det.setInputSize((im.shape[1], im.shape[0]))
            _, faces = self._det.detect(im)
        if faces is None or len(faces) == 0:
            return np.zeros((0, 15), dtype=np.float32)
        faces = faces.astype(np.float32).copy()
        if s < 1.0:
            faces[:, :14] /= s
        return faces

    # ------------------------------------------------------------------ SFace
    def embed(self, img: np.ndarray, face_row: np.ndarray) -> np.ndarray:
        with self._rec_lock:
            crop = self._rec.alignCrop(img, face_row)
            feat = self._rec.feature(crop)
        v = feat.astype(np.float32).ravel()
        n = float(np.linalg.norm(v))
        return v / n if n > 0 else v

    @staticmethod
    def cosine(a: np.ndarray | None, b: np.ndarray | None) -> float:
        if a is None or b is None:
            return 0.0
        return float(np.dot(a, b))

    # ------------------------------------------------------------- MediaPipe
    def metrics(self, img: np.ndarray) -> FaceMetrics:
        rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
        mp_img = self._mp.Image(image_format=self._mp.ImageFormat.SRGB, data=np.ascontiguousarray(rgb))
        with self._lm_lock:
            res = self._lm.detect(mp_img)
        if not res.face_landmarks:
            return FaceMetrics(ok=False)
        h, w = img.shape[:2]
        # ถ้ามีหลายหน้า ใช้หน้าที่ใหญ่สุด
        idx = 0
        if len(res.face_landmarks) > 1:
            def span(lms):
                xs = [p.x for p in lms]
                ys = [p.y for p in lms]
                return (max(xs) - min(xs)) * (max(ys) - min(ys))
            idx = max(range(len(res.face_landmarks)), key=lambda i: span(res.face_landmarks[i]))
        lms = res.face_landmarks[idx]
        P = [(p.x * w, p.y * h) for p in lms]

        ear = (_ear([P[i] for i in R_EYE]) + _ear([P[i] for i in L_EYE])) / 2.0
        iod = _dist(P[EYE_OUTER_R], P[EYE_OUTER_L]) or 1.0
        mouth_w = _dist(P[MOUTH_L], P[MOUTH_R]) / iod
        mouth_h = _dist(P[LIP_TOP], P[LIP_BOTTOM]) / iod

        bs = {}
        if res.face_blendshapes and len(res.face_blendshapes) > idx:
            bs = {c.category_name: float(c.score) for c in res.face_blendshapes[idx]}
        blink_bs = (bs.get('eyeBlinkLeft', 0.0) + bs.get('eyeBlinkRight', 0.0)) / 2.0
        smile_bs = (bs.get('mouthSmileLeft', 0.0) + bs.get('mouthSmileRight', 0.0)) / 2.0

        yaw = pitch = roll = 0.0
        if res.facial_transformation_matrixes and len(res.facial_transformation_matrixes) > idx:
            m = np.asarray(res.facial_transformation_matrixes[idx], dtype=np.float64).reshape(4, 4)
            yaw, pitch, roll = rotation_to_euler(m[:3, :3])
            yaw *= self.YAW_SIGN

        cr, cl = P[CHEEK_R], P[CHEEK_L]
        span_x = (cl[0] - cr[0]) or 1.0
        nose_offset = (P[NOSE_TIP][0] - (cr[0] + cl[0]) / 2.0) / span_x
        return FaceMetrics(ok=True, n_faces_mp=len(res.face_landmarks), ear=ear, blink_bs=blink_bs,
                           mouth_w=mouth_w, mouth_h=mouth_h, smile_bs=smile_bs, yaw=yaw, pitch=pitch,
                           roll=roll, nose_offset=float(nose_offset))

    def close(self) -> None:
        try:
            self._lm.close()
        except Exception:  # noqa: BLE001
            pass
