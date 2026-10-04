# -*- coding: utf-8 -*-
"""
ตั้ง env ก่อน import แอป + fixture กลาง

รันบนเครื่อง dev:
    EKYC_MODEL_DIR=/path/to/models python -m pytest -q
ไม่มีโมเดล (ยังไม่ได้รัน scripts/download_models.py + convert_minifasnet.py)
→ เทสต์ที่ต้องใช้โมเดลจะ skip เอง เทสต์ตรรกะล้วนยังรันได้
"""
from __future__ import annotations

import os
import sys

import pytest

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
sys.path.insert(0, ROOT)
sys.path.insert(0, HERE)

TEST_KEY = 'test-key-0123456789abcdef'
os.environ['EKYC_AI_KEY'] = TEST_KEY
os.environ.setdefault('EKYC_MODEL_DIR', os.path.join(ROOT, 'models'))
os.environ.setdefault('EKYC_REQUEST_TIMEOUT', '120')   # เครื่อง dev อาจช้า

MODEL_DIR = os.environ['EKYC_MODEL_DIR']


def models_available() -> bool:
    need = ['manifest.json', 'opencv/face_detection_yunet_2023mar.onnx', 'opencv/face_recognition_sface_2021dec.onnx',
            'mediapipe/face_landmarker.task', 'easyocr/craft_mlt_25k.pth', 'easyocr/thai.pth',
            'minifasnet/2.7_80x80_MiniFASNetV2.onnx', 'minifasnet/4_0_0_80x80_MiniFASNetV1SE.onnx']
    return all(os.path.isfile(os.path.join(MODEL_DIR, p)) for p in need)


requires_models = pytest.mark.skipif(not models_available(), reason='models not downloaded (see README)')

AUTH = {'X-Ekyc-Key': TEST_KEY}


@pytest.fixture(scope='session')
def bare_client():
    """TestClient ที่ไม่รัน lifespan (ไม่โหลดโมเดล) — พอสำหรับเทสต์ auth / ขีดจำกัด / validation"""
    from fastapi.testclient import TestClient
    from app.main import app
    return TestClient(app)


@pytest.fixture(scope='session')
def client():
    """TestClient เต็ม (โหลดโมเดล + warmup ครั้งเดียวทั้ง session)"""
    if not models_available():
        pytest.skip('models not downloaded')
    from fastapi.testclient import TestClient
    from app.main import app
    with TestClient(app) as c:
        yield c


@pytest.fixture(scope='session')
def engine(client):
    return client.app.state.engine
