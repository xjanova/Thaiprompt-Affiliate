# -*- coding: utf-8 -*-
"""
ด่านความปลอดภัยของ API: คีย์, ขนาด, จำนวนเฟรม, label — ทั้งหมดเกิดก่อนโหลด/เรียกโมเดล
(ใช้ TestClient ที่ไม่รัน lifespan จึงไม่ต้องมีโมเดล)
"""
import os
import subprocess
import sys

import numpy as np
import pytest

import synth
from conftest import AUTH, ROOT

JPG = synth.jpeg(np.full((120, 160, 3), 128, np.uint8))


def test_missing_key_rejected_everywhere(bare_client):
    for method, path in (('get', '/health'), ('post', '/v1/id-card'), ('post', '/v1/face/verify'),
                         ('get', '/nope')):
        r = getattr(bare_client, method)(path)
        assert r.status_code == 401, path
        assert r.json() == {'ok': False, 'error': 'UNAUTHORIZED', 'message': 'missing or invalid X-Ekyc-Key'}


def test_wrong_key_rejected(bare_client):
    r = bare_client.get('/health', headers={'X-Ekyc-Key': AUTH['X-Ekyc-Key'] + 'x'})
    assert r.status_code == 401
    r = bare_client.get('/health', headers={'X-Ekyc-Key': ''})
    assert r.status_code == 401


def test_docs_disabled(bare_client):
    for p in ('/docs', '/openapi.json', '/redoc'):
        assert bare_client.get(p, headers=AUTH).status_code == 404


def test_content_length_over_limit_rejected_before_reading(bare_client):
    from app.main import MAX_BODY
    r = bare_client.post('/v1/id-card', headers={**AUTH, 'Content-Length': str(MAX_BODY + 1),
                                                 'Content-Type': 'multipart/form-data; boundary=x'},
                         content=b'')
    assert r.status_code == 413 and r.json()['error'] == 'PAYLOAD_TOO_LARGE'


def test_image_over_8mb_rejected(bare_client):
    big = b'\xff\xd8\xff' + os.urandom(8 * 1024 * 1024)
    r = bare_client.post('/v1/id-card', headers=AUTH, files={'image': ('a.jpg', big, 'image/jpeg')})
    assert r.status_code == 413
    assert r.json()['error'] == 'IMAGE_TOO_LARGE' and r.json()['field'] == 'image'


def test_id_card_requires_image_file(bare_client):
    r = bare_client.post('/v1/id-card', headers=AUTH, data={'image': 'not-a-file'})
    assert r.status_code == 422 and r.json()['error'] == 'MISSING_FILE'


def _face_req(client, frames, labels, card=JPG, extra=None):
    files = [('card_image', ('card.jpg', card, 'image/jpeg'))]
    files += [('frames[]', (f'f{i}.jpg', f, 'image/jpeg')) for i, f in enumerate(frames)]
    data = {'labels[]': labels}
    if extra:
        data.update(extra)
    return client.post('/v1/face/verify', headers=AUTH, files=files, data=data)


@pytest.mark.parametrize('frames,labels,code', [
    ([JPG] * 9, ['neutral'] + ['blink'] * 8, 'TOO_MANY_FRAMES'),
    ([JPG] * 3, ['neutral', 'blink'], 'LABELS_MISMATCH'),
    ([JPG] * 2, ['neutral', 'wink'], 'BAD_LABEL'),
    ([JPG] * 2, ['blink', 'neutral'], 'FIRST_FRAME_NOT_NEUTRAL'),
    ([JPG] * 2, ['neutral', 'neutral'], 'NO_CHALLENGE_FRAMES'),
    ([], [], 'MISSING_FRAMES'),
])
def test_face_verify_validation(bare_client, frames, labels, code):
    # คำขอผิดรูป = 422 (ตกลงกับ backend: 4xx/5xx อื่นถือว่า AI ล่ม)
    r = _face_req(bare_client, frames, labels)
    assert r.status_code == 422, r.text
    assert r.json()['error'] == code


def test_backend_client_wire_format_is_accepted(bare_client):
    """
    รูปแบบเดียวกับ app/Services/Ekyc/EkycAiClient.php: attach('card_image'), attach('frames[]') ทีละเฟรม,
    และ part ชื่อ 'labels[]' ซ้ำทีละตัว — ต้องผ่านด่าน validation (จงใจให้ label ผิดเพื่อไม่ต้องโหลดโมเดล)
    """
    files = [('card_image', ('card.jpg', JPG, 'image/jpeg')), ('frames[]', ('frame0.jpg', JPG, 'image/jpeg')),
             ('frames[]', ('frame1.jpg', JPG, 'image/jpeg')), ('frames[]', ('frame2.jpg', JPG, 'image/jpeg'))]
    r = bare_client.post('/v1/face/verify', headers=AUTH, files=files,
                         data={'labels[]': ['blink', 'neutral', 'smile']})
    assert r.status_code == 422 and r.json()['error'] == 'FIRST_FRAME_NOT_NEUTRAL'
    # 3 เฟรม แต่ 2 label → ต้องนับครบทุก part (ไม่ทับกันเหลือตัวเดียว)
    r = bare_client.post('/v1/face/verify', headers=AUTH, files=files, data={'labels[]': ['neutral', 'smile']})
    assert r.status_code == 422 and r.json()['error'] == 'LABELS_MISMATCH'


def test_face_verify_accepts_indexed_field_names(bare_client):
    # frames[0], frames[1] / labels[0], labels[1] ต้องถูกอ่านตามลำดับดัชนี (ไม่ใช่ MISSING_FRAMES)
    files = [('card_image', ('c.jpg', JPG, 'image/jpeg')), ('frames[1]', ('b.jpg', JPG, 'image/jpeg')),
             ('frames[0]', ('a.jpg', JPG, 'image/jpeg'))]
    r = bare_client.post('/v1/face/verify', headers=AUTH, files=files,
                         data={'labels[1]': 'neutral', 'labels[0]': 'blink'})
    assert r.status_code == 422 and r.json()['error'] == 'FIRST_FRAME_NOT_NEUTRAL'


def test_face_verify_requires_card(bare_client):
    files = [('frames[]', ('f.jpg', JPG, 'image/jpeg')), ('frames[]', ('g.jpg', JPG, 'image/jpeg'))]
    r = bare_client.post('/v1/face/verify', headers=AUTH, files=files, data={'labels[]': ['neutral', 'blink']})
    assert r.status_code == 422 and r.json()['error'] == 'MISSING_FILE' and r.json()['field'] == 'card_image'


def test_frame_over_8mb_rejected(bare_client):
    big = b'\xff\xd8\xff' + os.urandom(8 * 1024 * 1024)
    r = _face_req(bare_client, [JPG, big], ['neutral', 'blink'])
    assert r.status_code == 413 and r.json()['field'] == 'frames[1]'


def _start_without(env_over):
    env = {k: v for k, v in os.environ.items() if not k.startswith('EKYC_')}
    env.update(env_over)
    env['PYTHONUTF8'] = '1'
    return subprocess.run([sys.executable, '-c', 'import app.main'], cwd=ROOT, env=env,
                          capture_output=True, text=True, timeout=300)


def test_refuses_to_start_without_key():
    p = _start_without({})
    assert p.returncode != 0 and 'EKYC_AI_KEY is required' in (p.stderr + p.stdout)


def test_refuses_short_key():
    p = _start_without({'EKYC_AI_KEY': 'short'})
    assert p.returncode != 0 and 'at least 16' in (p.stderr + p.stdout)


def test_dev_mode_allows_start_without_key():
    p = _start_without({'EKYC_AI_DEV': '1'})
    assert p.returncode == 0, p.stderr
