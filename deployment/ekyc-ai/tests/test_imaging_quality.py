# -*- coding: utf-8 -*-
"""
ถอดรหัสรูปอย่างปลอดภัย + หาบัตร/ปรับมุม + คุณภาพ (blur/glare) + heuristic บัตรจริง (moiré/สำเนา)
ใช้ภาพบัตรสังเคราะห์ (ต้องมีฟอนต์ไทยจาก repo) — ไม่ต้องใช้โมเดล AI
"""
import io

import cv2
import numpy as np
import pytest
from PIL import Image

import synth
from app import card as C
from app.imaging import BadImage, decode_image

needs_font = pytest.mark.skipif(synth.find_font() is None, reason='Thai font not found in repo')


def J(img, q=90):
    return cv2.imdecode(np.frombuffer(synth.jpeg(img, q), np.uint8), cv2.IMREAD_COLOR)


# ------------------------------------------------------------------ decode
def test_decode_rejects_non_image():
    with pytest.raises(BadImage) as e:
        decode_image(b'%PDF-1.4 not an image' * 10, 25_000_000)
    assert e.value.code == 'UNSUPPORTED_FORMAT'


def test_decode_rejects_corrupt_jpeg_header():
    with pytest.raises(BadImage) as e:
        decode_image(b'\xff\xd8\xff' + b'\x00' * 500, 25_000_000)
    assert e.value.code == 'CORRUPT_IMAGE'


def test_decode_rejects_too_many_pixels_without_decoding():
    # PNG 6000×5000 สีเดียว (30 MP) ไฟล์เล็กมาก แต่ถ้าถอดรหัสจะกิน RAM ~90 MB
    buf = io.BytesIO()
    Image.new('L', (6000, 5000), 0).save(buf, format='PNG', optimize=True)
    data = buf.getvalue()
    assert len(data) < 1_000_000
    with pytest.raises(BadImage) as e:
        decode_image(data, 25_000_000)
    assert e.value.code == 'IMAGE_TOO_MANY_PIXELS'


def test_decode_rejects_tiny():
    with pytest.raises(BadImage) as e:
        decode_image(synth.jpeg(np.zeros((32, 32, 3), np.uint8)), 25_000_000)
    assert e.value.code == 'IMAGE_TOO_SMALL'


def test_decode_resizes_and_applies_exif_orientation():
    img = np.zeros((200, 400, 3), np.uint8)
    img[:, :200] = 255                       # ครึ่งซ้ายขาว
    pil = Image.fromarray(img)
    exif = pil.getexif()
    exif[0x0112] = 6                          # Orientation = หมุน 90° CW
    buf = io.BytesIO()
    pil.save(buf, format='JPEG', exif=exif.tobytes())
    out = decode_image(buf.getvalue(), 25_000_000)
    assert out.shape[:2] == (400, 200)       # ถูกหมุนตั้งตามแท็ก EXIF แล้ว
    small = decode_image(synth.jpeg(np.zeros((1000, 3000, 3), np.uint8)), 25_000_000, max_side=1280)
    assert max(small.shape[:2]) == 1280


# ------------------------------------------------------------------ หา/ปรับมุมบัตร
@needs_font
def test_locate_card_in_scene_and_warp():
    card = synth.render_card()
    scene, true_quad = synth.place_in_scene(card)
    view = C.locate_card(J(scene))
    assert view.quad_found and view.complete and not view.precropped
    assert view.image.shape[:2] == (C.CARD_H, C.CARD_W)
    # เทียบกับบัตรต้นฉบับที่ย่อเท่ากัน: ภาพหลัง warp ต้องคล้ายกันมาก
    ref = cv2.resize(card, (C.CARD_W, C.CARD_H), interpolation=cv2.INTER_AREA)
    g1 = cv2.cvtColor(view.image, cv2.COLOR_BGR2GRAY).astype(np.float32)
    g2 = cv2.cvtColor(ref, cv2.COLOR_BGR2GRAY).astype(np.float32)
    corr = np.corrcoef(cv2.GaussianBlur(g1, (0, 0), 2).ravel(), cv2.GaussianBlur(g2, (0, 0), 2).ravel())[0, 1]
    assert corr > 0.75
    # มุมบัตรที่หาได้ (แปลงกลับด้วย to_src) ต้องใกล้มุมจริงในฉาก ไม่เกิน 3% ของความกว้างบัตร
    corners = np.array([[0, 0, 1], [C.CARD_W - 1, 0, 1], [C.CARD_W - 1, C.CARD_H - 1, 1], [0, C.CARD_H - 1, 1]],
                       dtype=np.float64).T
    src = view.to_src @ corners
    src = (src[:2] / src[2]).T
    card_w = float(np.linalg.norm(true_quad[1] - true_quad[0]))
    err = np.linalg.norm(src - true_quad, axis=1).max()
    assert err < 0.03 * card_w, (err, src, true_quad)


@needs_font
def test_precropped_card_and_portrait_card():
    card = synth.render_card()
    v = C.locate_card(J(card))
    assert v.precropped and v.complete and v.image.shape[:2] == (C.CARD_H, C.CARD_W)
    vp = C.locate_card(J(cv2.rotate(card, cv2.ROTATE_90_COUNTERCLOCKWISE)))
    assert vp.precropped and vp.image.shape[:2] == (C.CARD_H, C.CARD_W)


@needs_font
def test_card_cut_by_frame_is_incomplete():
    card = synth.render_card()
    scene, _ = synth.place_in_scene(card, fill=0.62)
    cut = scene[:, 380:]                       # ตัดด้านซ้ายของบัตรออก
    v = C.locate_card(J(cut))
    assert not (v.quad_found and v.complete)


def test_no_card_in_plain_photo():
    rng = np.random.default_rng(3)
    img = cv2.GaussianBlur(rng.integers(0, 255, (900, 900, 3), dtype=np.uint8), (0, 0), 6)
    v = C.locate_card(J(img))
    assert not v.quad_found and not v.precropped


# ------------------------------------------------------------------ คุณภาพ
@needs_font
def test_blur_metric():
    card = cv2.resize(synth.render_card(), (C.CARD_W, C.CARD_H), interpolation=cv2.INTER_AREA)
    sharp, _ = C.blur_score(cv2.cvtColor(J(card), cv2.COLOR_BGR2GRAY))
    blurry, _ = C.blur_score(cv2.cvtColor(J(cv2.GaussianBlur(card, (0, 0), 3)), cv2.COLOR_BGR2GRAY))
    assert sharp < 0.2 and blurry >= 0.7


@needs_font
def test_glare_metric():
    card = cv2.resize(synth.render_card(), (C.CARD_W, C.CARD_H), interpolation=cv2.INTER_AREA)
    clean, _ = C.glare_score(J(card))
    glare, _ = C.glare_score(J(synth.add_glare(card)))
    assert clean < 0.1 and glare >= 0.5
    # สำเนาขาวดำ: พื้นขาวทั้งใบไม่ใช่แสงสะท้อน
    copy, _ = C.glare_score(J(synth.photocopy(card)))
    assert copy < 0.5


# ------------------------------------------------------------------ บัตรจริง (heuristic)
def _real(img):
    v = C.locate_card(J(img))
    g, _ = C.glare_score(v.image)
    return C.card_real_score(v.image, g, v.native_gray)


@needs_font
def test_card_real_score_real_vs_photocopy_vs_screen():
    card = synth.render_card(width=2000)
    scene = lambda x: synth.place_in_scene(x, 2400, 1800)[0]  # noqa: E731
    real, dbg = _real(scene(card))
    assert real >= 0.9, dbg
    copy, dbg = _real(scene(synth.photocopy(card)))
    assert copy <= 0.3, dbg
    for period, angle in ((3.1, 8.0), (4.5, 20.0), (6.0, 30.0)):
        screen, dbg = _real(scene(synth.screen_recapture(card, period, angle)))
        assert screen <= 0.4, (period, dbg)


@needs_font
def test_card_real_score_survives_jpeg_and_noise():
    card = synth.render_card(width=2000)
    sc, _ = synth.place_in_scene(card, 2400, 1800)
    noisy = np.clip(sc + np.random.default_rng(0).normal(0, 6, sc.shape), 0, 255).astype(np.uint8)
    for img in (noisy, J(sc, 70)):
        real, dbg = _real(img)
        assert real >= 0.9, dbg
