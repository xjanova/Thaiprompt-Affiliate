# -*- coding: utf-8 -*-
"""
เทสต์ end-to-end ผ่าน HTTP กับโมเดลจริง (ต้องดาวน์โหลดโมเดลก่อน ไม่งั้น skip)

ภาพที่ใช้: บัตรสังเคราะห์ (tests/synth.py) + ภาพ astronaut ของ NASA (public domain, มากับ scikit-image)
ภาพใบหน้าจริงเพิ่มเติม (ไม่ commit): ตั้ง EKYC_TEST_FACES_DIR ไปยังโฟลเดอร์จาก scripts/fetch_test_faces.py
"""
import os

import cv2
import numpy as np
import pytest

import synth
from conftest import AUTH, requires_models

pytestmark = requires_models
needs_font = pytest.mark.skipif(synth.find_font() is None, reason='Thai font not found in repo')
needs_astro = pytest.mark.skipif(synth.astronaut_bgr() is None, reason='scikit-image astronaut not available')
FACES_DIR = os.environ.get('EKYC_TEST_FACES_DIR', '')
needs_faces = pytest.mark.skipif(not (FACES_DIR and os.path.isfile(os.path.join(FACES_DIR, 'image_T1.jpg'))),
                                 reason='EKYC_TEST_FACES_DIR not set (scripts/fetch_test_faces.py)')

ID_KEYS = {'ok', 'card_detected', 'quality', 'fields', 'field_confidence', 'ocr_confidence', 'id_checksum_ok',
           'card_face_found', 'card_real_score', 'reasons', 'model_version'}
FACE_KEYS = {'ok', 'faces_found', 'same_person_across_frames', 'liveness', 'anti_spoof', 'match',
             'best_frame_index', 'reasons', 'model_version'}


def post_card(client, img_bytes):
    return client.post('/v1/id-card', headers=AUTH, files={'image': ('card.jpg', img_bytes, 'image/jpeg')})


def post_face(client, card_bytes, frames, labels, mirrored=None):
    files = [('card_image', ('card.jpg', card_bytes, 'image/jpeg'))]
    files += [('frames[]', (f'f{i}.jpg', f, 'image/jpeg')) for i, f in enumerate(frames)]
    data = {'labels[]': labels}
    if mirrored is not None:
        data['mirrored'] = '1' if mirrored else '0'
    return client.post('/v1/face/verify', headers=AUTH, files=files, data=data)


@pytest.fixture(scope='module')
def scene_card_jpg():
    if synth.find_font() is None:
        pytest.skip('no Thai font')
    scene, _ = synth.place_in_scene(synth.render_card(), 1600, 1200)
    return synth.jpeg(scene, 92)


def _num01(x):
    return isinstance(x, (int, float)) and 0.0 <= x <= 1.0


# ------------------------------------------------------------------ /health
def test_health(client):
    r = client.get('/health', headers=AUTH)
    assert r.status_code == 200
    j = r.json()
    assert j['ok'] is True
    for k in ('yunet', 'sface', 'face_landmarker', 'easyocr_craft', 'easyocr_thai', 'minifasnet_v2',
              'minifasnet_v1se'):
        assert j['models'][k]


# ------------------------------------------------------------------ /v1/id-card
@needs_font
@needs_astro
def test_id_card_scene_full_schema_and_fields(client, scene_card_jpg):
    r = post_card(client, scene_card_jpg)
    assert r.status_code == 200, r.text
    j = r.json()
    assert ID_KEYS <= set(j)
    assert set(j['fields']) >= {'id_number', 'name_th', 'name_en', 'birth_date', 'expiry_date', 'issue_date'}
    assert set(j['field_confidence']) >= {'id_number', 'name_th', 'birth_date', 'expiry_date'}
    assert set(j['quality']) == {'blur', 'glare', 'complete'}
    assert all(_num01(v) for v in j['field_confidence'].values())
    assert _num01(j['ocr_confidence']) and _num01(j['card_real_score'])
    spec = synth.CardSpec()
    f = j['fields']
    assert j['card_detected'] and j['quality']['complete']
    assert f['id_number'] == spec.id13 and j['id_checksum_ok']
    assert f['birth_date'] == '1995-01-12'
    assert f['expiry_date'] == '2029-01-11'
    assert f['issue_date'] == '2020-01-01'
    assert f['name_th'] and 'สมชาย' in f['name_th']
    assert j['card_face_found']
    assert j['card_real_score'] >= 0.8
    assert j['ocr_confidence'] >= 0.8
    assert j['reasons'] == [], j['reasons']


@needs_font
def test_id_card_lifelong(client):
    spec = synth.CardSpec(expiry_th='ตลอดชีพ', expiry_en='LIFE LONG')
    r = post_card(client, synth.jpeg(synth.render_card(spec), 92))
    j = r.json()
    assert j['fields']['expiry_date'] is None
    assert 'EXPIRY_LIFELONG' in j['reasons'] and 'EXPIRED' not in j['reasons']


@needs_font
def test_id_card_expired_upside_down(client):
    spec = synth.CardSpec(issue_th='1 ม.ค. 2557', issue_en='1 Jan. 2014', expiry_th='11 ม.ค. 2565',
                          expiry_en='11 Jan. 2022')
    img = cv2.rotate(synth.render_card(spec), cv2.ROTATE_180)    # ถ่ายกลับหัว
    j = post_card(client, synth.jpeg(img, 92)).json()
    assert j['fields']['expiry_date'] == '2022-01-11'
    assert 'EXPIRED' in j['reasons']
    assert j['fields']['id_number'] == spec.id13


@needs_astro
def test_id_card_no_card(client):
    img = cv2.resize(synth.astronaut_bgr(), (900, 900))
    j = post_card(client, synth.jpeg(img)).json()
    assert j['ok'] and not j['card_detected'] and 'NO_CARD' in j['reasons']


@pytest.mark.parametrize('payload', [b'\xff\xd8\xff' + b'\x00' * 2000, b'', b'%PDF-1.4 not an image'])
def test_id_card_unreadable_image_is_200_retake(client, payload):
    # ตกลงกับ backend: ภาพเสียต้องเป็น HTTP 200 + ok=false + reasons (4xx/5xx = AI ล่ม → ส่งแอดมิน)
    r = post_card(client, payload)
    assert r.status_code == 200, r.text
    j = r.json()
    assert j['ok'] is False and j['error'] == 'BAD_IMAGE' and j['field'] == 'image'
    assert not j['card_detected'] and 'NO_CARD' in j['reasons']
    assert ID_KEYS <= set(j) and j['fields']['id_number'] is None and j['card_face_box'] is None


# ------------------------------------------------------------------ /v1/face/verify
def _astro_frame(scale=1.25):
    a = synth.astronaut_bgr()
    return cv2.resize(a, None, fx=scale, fy=scale, interpolation=cv2.INTER_CUBIC)


@needs_font
@needs_astro
def test_face_verify_static_photo_is_same_person_but_fails_liveness(client, scene_card_jpg):
    f = synth.jpeg(_astro_frame())
    r = post_face(client, scene_card_jpg, [f, f, f], ['neutral', 'blink', 'turn_left'])
    assert r.status_code == 200, r.text
    j = r.json()
    assert FACE_KEYS <= set(j)
    assert set(j['liveness']) == {'passed', 'score', 'challenges'}
    assert set(j['match']) == {'cosine', 'score'} and set(j['anti_spoof']) == {'real_score'}
    assert j['faces_found'] == 3 and j['same_person_across_frames'] is True
    assert j['liveness']['passed'] is False
    assert j['liveness']['challenges'] == {'blink': False, 'turn_left': False}
    assert 'CHALLENGE_FAILED:blink' in j['reasons'] and 'CHALLENGE_FAILED:turn_left' in j['reasons']
    # รูปบนบัตรมาจากภาพเดียวกัน → cosine สูงกว่าเกณฑ์ OpenCV (0.363)
    assert j['match']['cosine'] >= 0.363 and 'LOW_MATCH' not in j['reasons']
    assert j['best_frame_index'] == 0
    assert _num01(j['liveness']['score']) and _num01(j['anti_spoof']['real_score']) and _num01(j['match']['score'])


@needs_font
@needs_astro
def test_face_verify_no_face_and_multiple_faces(client, scene_card_jpg):
    a = _astro_frame(1.0)
    blank = synth.jpeg(np.full((640, 480, 3), 120, np.uint8))
    two = synth.jpeg(np.hstack([a, a]))
    j = post_face(client, scene_card_jpg, [synth.jpeg(a), blank, two], ['neutral', 'blink', 'smile']).json()
    assert 'NO_FACE' in j['reasons'] and 'MULTIPLE_FACES' in j['reasons']
    assert j['faces_found'] == 1
    assert j['liveness']['passed'] is False and j['same_person_across_frames'] is False


@needs_astro
def test_face_verify_card_without_face(client):
    card = synth.jpeg(np.full((630, 1000, 3), 200, np.uint8))
    f = synth.jpeg(_astro_frame())
    j = post_face(client, card, [f, f], ['neutral', 'smile']).json()
    assert 'NO_CARD_FACE' in j['reasons'] and j['match']['cosine'] == 0.0


def test_face_verify_corrupt_inputs_are_200_with_reasons(client):
    ok = synth.jpeg(np.full((240, 320, 3), 128, np.uint8))
    r = post_face(client, ok, [ok, b'\xff\xd8\xff' + b'\x00' * 100], ['neutral', 'blink'])
    assert r.status_code == 200, r.text
    j = r.json()
    assert j['ok'] is False and j['error'] == 'BAD_IMAGE'
    assert {'field': 'frames[1]', 'code': 'CORRUPT_IMAGE'} in j['bad_inputs']
    assert 'NO_FACE' in j['reasons'] and j['liveness']['passed'] is False and FACE_KEYS <= set(j)
    r = post_face(client, b'garbage-bytes', [ok, ok], ['neutral', 'blink'])
    j = r.json()
    assert r.status_code == 200 and 'NO_CARD_FACE' in j['reasons'] and j['card_face_box'] is None
    assert j['bad_inputs'][0]['field'] == 'card_image'


def _iou(a, b):
    ax0, ay0, aw, ah = a
    bx0, by0, bw, bh = b
    ix = max(0, min(ax0 + aw, bx0 + bw) - max(ax0, bx0))
    iy = max(0, min(ay0 + ah, by0 + bh) - max(ay0, by0))
    inter = ix * iy
    return inter / float(aw * ah + bw * bh - inter)


@needs_font
@needs_astro
def test_card_face_box_is_in_source_image_pixels(engine):
    """
    card_face_box ต้องเป็นพิกัดของภาพที่ส่งมา (backend ครอปจาก JPEG ของตัวเอง) — ทุกกรณี:
    บัตรวางบนโต๊ะ (perspective), กลับหัว, ครอปมาแล้ว, ครอปมาแนวตั้ง
    """
    card = synth.render_card()
    scene, _ = synth.place_in_scene(card)
    cases = {'scene': scene, 'scene_180': cv2.rotate(scene, cv2.ROTATE_180), 'precropped': card,
             'portrait': cv2.rotate(card, cv2.ROTATE_90_COUNTERCLOCKWISE)}
    for name, img in cases.items():
        src = cv2.imdecode(np.frombuffer(synth.jpeg(img), np.uint8), cv2.IMREAD_COLOR)
        box = engine._card_view(src).face_box_in_source()
        assert box is not None, name
        H, W = src.shape[:2]
        # คำตอบอ้างอิง: YuNet บนภาพที่หมุนให้หน้าตั้งตรง แล้วแปลงกรอบกลับมาเป็นพิกัดภาพเดิม
        if name == 'portrait':
            up = cv2.rotate(src, cv2.ROTATE_90_CLOCKWISE)          # (x, y) เดิม → (H-1-y, x) บนภาพนี้
            f = max(engine.face.detect(up, max_side=1600), key=lambda r: r[2] * r[3])
            exp = [int(f[1]), int(H - 1 - (f[0] + f[2])), int(f[3]), int(f[2])]
        elif name == 'scene_180':
            up = cv2.rotate(src, cv2.ROTATE_180)
            f = max(engine.face.detect(up, max_side=1600), key=lambda r: r[2] * r[3])
            exp = [int(W - 1 - (f[0] + f[2])), int(H - 1 - (f[1] + f[3])), int(f[2]), int(f[3])]
        else:
            f = max(engine.face.detect(src, max_side=1600), key=lambda r: r[2] * r[3])
            exp = [int(v) for v in f[:4]]
        assert _iou(box, exp) >= 0.6, (name, box, exp)


# ------------------------------------------------------------------ ภาพใบหน้าจริงเพิ่มเติม (ไม่ commit)
def _face(name):
    return cv2.imread(os.path.join(FACES_DIR, name))


@needs_faces
def test_yaw_sign_matches_geometry(engine):
    """yaw จากเมทริกซ์ MediaPipe ต้องมีเครื่องหมายเดียวกับ 'จมูกเลื่อนไปทางขวาของภาพ' และกลับด้านเมื่อพลิกภาพ"""
    t1 = _face('image_T1.jpg')
    m = engine.face.metrics(t1)
    mf = engine.face.metrics(cv2.flip(t1, 1))
    assert abs(m.yaw) > 10
    assert np.sign(m.yaw) == np.sign(m.nose_offset)
    assert np.sign(mf.yaw) == -np.sign(m.yaw)


@needs_faces
def test_turn_direction_end_to_end_with_mirror_flag(client, scene_card_jpg):
    """
    T1 หันไปทางขวาของภาพราว 21° ส่วนภาพที่พลิกหันไปทางซ้ายของภาพ
    neutral = ภาพพลิก, เฟรม challenge = ภาพเดิม → หน้าเคลื่อนไปทางขวาของภาพ ~43°
    mirrored=True  → ผู้ใช้หันไปทาง "ขวาของตัวเอง" → turn_right ผ่าน
    mirrored=False → ผู้ใช้หันไปทาง "ซ้ายของตัวเอง" → turn_left ผ่าน
    """
    t1 = _face('image_T1.jpg')
    neutral, turned = synth.jpeg(cv2.flip(t1, 1)), synth.jpeg(t1)
    j = post_face(client, scene_card_jpg, [neutral, turned], ['neutral', 'turn_right'], mirrored=True).json()
    assert j['liveness']['challenges'] == {'turn_right': True}
    j = post_face(client, scene_card_jpg, [neutral, turned], ['neutral', 'turn_left'], mirrored=True).json()
    assert j['liveness']['challenges'] == {'turn_left': False}
    j = post_face(client, scene_card_jpg, [neutral, turned], ['neutral', 'turn_left'], mirrored=False).json()
    assert j['liveness']['challenges'] == {'turn_left': True}
    assert j['same_person_across_frames'] is True


@needs_faces
@needs_astro
def test_anti_spoof_real_vs_recaptured(engine):
    def real_of(img):
        faces = engine.face.detect(img)
        return engine.spoof.real_score(img, max(faces, key=lambda f: f[2] * f[3]))
    assert real_of(_face('image_T1.jpg')) >= 0.8
    assert real_of(_face('image_F1.jpg')) < 0.5
    assert real_of(_face('image_F2.jpg')) < 0.5


@needs_faces
@needs_astro
def test_different_people_and_low_match(client, scene_card_jpg):
    a = synth.jpeg(_astro_frame())
    t1 = synth.jpeg(_face('image_T1.jpg'))
    j = post_face(client, scene_card_jpg, [a, t1], ['neutral', 'blink']).json()
    assert 'DIFFERENT_PEOPLE' in j['reasons'] and j['same_person_across_frames'] is False
    j = post_face(client, scene_card_jpg, [t1, t1], ['neutral', 'blink']).json()
    assert 'LOW_MATCH' in j['reasons'] and j['match']['cosine'] < 0.363
