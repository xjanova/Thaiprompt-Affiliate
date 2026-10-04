# -*- coding: utf-8 -*-
"""ตรรกะ liveness ล้วน (ค่าวัดสังเคราะห์) — ทิศซ้าย/ขวาแบบ mirror, เกณฑ์ challenge, คะแนน"""
import pytest

from app.config import Settings
from app.liveness import FrameMeasure, evaluate, match_score, strength

CFG = Settings(api_key='x' * 16)


def neutral(**kw):
    base = dict(label='neutral', n_faces=1, ok=True, ear=0.30, blink_bs=0.05, mouth_w=0.70, smile_bs=0.05,
                yaw=0.0, pitch=10.0, real=0.95, cos_to_neutral=None)
    base.update(kw)
    return FrameMeasure(**base)


def frame(label, **kw):
    m = neutral(label=label, cos_to_neutral=0.8)
    for k, v in kw.items():
        setattr(m, k, v)
    return m


def run(frames, mirrored=True, real=0.95):
    return evaluate(frames, mirrored, CFG, real)


def test_all_challenges_pass_mirrored():
    frames = [neutral(),
              frame('blink', ear=0.12, blink_bs=0.8),
              frame('turn_left', yaw=-25.0, cos_to_neutral=0.5),     # mirrored: ซ้ายผู้ใช้ = ซ้ายภาพ (yaw ลด)
              frame('smile', mouth_w=0.85, smile_bs=0.8)]
    r = run(frames)
    assert r.passed and r.challenges == {'blink': True, 'turn_left': True, 'smile': True}
    assert r.score >= 0.8 and r.reasons == []


def test_turn_direction_depends_on_mirror_flag():
    left_in_image = [neutral(), frame('turn_left', yaw=-25.0, cos_to_neutral=0.5)]
    assert run(left_in_image, mirrored=True).challenges['turn_left'] is True
    # ภาพไม่กลับด้าน: หน้าที่หันไปซ้ายของภาพ = ผู้ใช้หันไปทางขวาของตัวเอง
    assert run(left_in_image, mirrored=False).challenges['turn_left'] is False
    right_req = [neutral(), frame('turn_right', yaw=-25.0, cos_to_neutral=0.5)]
    assert run(right_req, mirrored=False).challenges['turn_right'] is True
    assert run(right_req, mirrored=True).challenges['turn_right'] is False


def test_turn_relative_to_neutral_not_absolute():
    # neutral เอียงอยู่แล้ว -10° → ต้องเปลี่ยนเพิ่มอีก ≥ 15°
    r = run([neutral(yaw=-10.0), frame('turn_left', yaw=-20.0, cos_to_neutral=0.5)])
    assert r.challenges['turn_left'] is False
    r = run([neutral(yaw=-10.0), frame('turn_left', yaw=-26.0, cos_to_neutral=0.5)])
    assert r.challenges['turn_left'] is True


def test_static_photo_replay_fails_every_challenge():
    frames = [neutral()] + [frame(lb) for lb in ('blink', 'smile', 'nod')]
    r = run(frames)
    assert not r.passed
    assert set(r.reasons) == {'CHALLENGE_FAILED:blink', 'CHALLENGE_FAILED:smile', 'CHALLENGE_FAILED:nod'}
    assert r.score < 0.5


def test_nod_either_direction():
    assert run([neutral(), frame('nod', pitch=-2.0)]).challenges['nod'] is True     # ก้ม/เงย 12°
    assert run([neutral(), frame('nod', pitch=22.0)]).challenges['nod'] is True
    assert run([neutral(), frame('nod', pitch=15.0)]).challenges['nod'] is False


def test_blink_by_blendshape_only():
    r = run([neutral(), frame('blink', ear=0.26, blink_bs=0.7)])
    assert r.challenges['blink'] is True


def test_no_face_and_multiple_faces_block_pass():
    r = run([neutral(), frame('blink', ear=0.1, n_faces=0)])
    assert not r.passed and 'NO_FACE' in r.reasons
    r = run([neutral(), frame('blink', ear=0.1, n_faces=2)])
    assert not r.passed and 'MULTIPLE_FACES' in r.reasons


def test_different_person_blocks_pass():
    r = run([neutral(), frame('blink', ear=0.1, cos_to_neutral=0.1)])
    assert r.challenges['blink'] is True and not r.passed


def test_spoof_score_blocks_pass():
    frames = [neutral(), frame('blink', ear=0.1)]
    assert run(frames, real=0.95).passed
    assert not run(frames, real=0.2).passed


def test_neutral_must_be_frontal_with_open_eyes():
    r = run([neutral(yaw=40.0), frame('blink', ear=0.1, yaw=40.0)])
    assert not r.passed and not r.neutral_ok
    r = run([neutral(ear=0.05), frame('blink', ear=0.01)])
    assert not r.passed


@pytest.mark.parametrize('cos,lo,hi', [(0.15, 0.0, 0.05), (0.363, 0.49, 0.51), (0.5, 0.89, 0.93), (0.7, 0.99, 1.0)])
def test_match_score_mapping(cos, lo, hi):
    assert lo <= match_score(cos) <= hi


def test_strength_curve():
    assert strength(0, 10) == 0
    assert strength(5, 10) == pytest.approx(0.4)
    assert strength(10, 10) == pytest.approx(0.8)
    assert strength(20, 10) == pytest.approx(1.0)
    assert strength(50, 10) == pytest.approx(1.0)
