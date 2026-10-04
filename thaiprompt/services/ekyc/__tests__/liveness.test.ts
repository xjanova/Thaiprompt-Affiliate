/**
 * ทดสอบตัวตัดสินท่าทางยืนยันใบหน้า (state machine ล้วน ไม่แตะกล้อง)
 *
 * รัน: npx jest services/ekyc/__tests__/liveness.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import {
  advance,
  advanceGuided,
  createLivenessState,
  currentLabel,
  evaluateChallenge,
  facingDirection,
  framingHint,
  looksLikeCardPortrait,
  pickPictureSize,
  progressOf,
  sampleFromMlkit,
  LIVENESS_CONFIG,
  type FaceSample,
} from '../liveness';

/** ใบหน้ามองตรง กลางภาพ ขนาดพอดี */
const face = (over: Partial<FaceSample> = {}): FaceSample => ({
  faceCount: 1,
  box: { x: 0.3, y: 0.28, w: 0.4, h: 0.45 },
  yaw: 0,
  pitch: 0,
  roll: 0,
  leftEyeOpen: 0.95,
  rightEyeOpen: 0.93,
  smile: 0.1,
  noseOffset: 0,
  ...over,
});

describe('sampleFromMlkit', () => {
  it('เลือกหน้าที่ใหญ่สุด + คำนวณจมูกเทียบกึ่งกลางตา (landmark แบบ Android เป็นเลข)', () => {
    const s = sampleFromMlkit(
      [
        { frame: { origin: { x: 10, y: 10 }, size: { x: 50, y: 50 } } },
        {
          frame: { origin: { x: 300, y: 260 }, size: { x: 400, y: 460 } },
          headEulerAngleY: -22,
          headEulerAngleX: 3,
          smilingProbability: 0.8,
          leftEyeOpenProbability: 0.9,
          rightEyeOpenProbability: 0.88,
          landmarks: [
            { type: '4', position: { x: 420, y: 400 } },
            { type: '10', position: { x: 580, y: 400 } },
            { type: '6', position: { x: 450, y: 480 } },
          ],
        },
      ],
      1000,
      1000
    );
    expect(s.faceCount).toBe(2);
    expect(s.box).toEqual({ x: 0.3, y: 0.26, w: 0.4, h: 0.46 });
    expect(s.yaw).toBe(-22);
    expect(s.smile).toBe(0.8);
    // จมูก 450 · กึ่งกลางตา 500 · ระยะตา 160 → -0.3125 (ไปทางซ้ายของภาพ)
    expect(s.noseOffset).toBeCloseTo(-0.3125);
  });

  it('landmark แบบ iOS (ชื่อ) ก็อ่านได้', () => {
    const s = sampleFromMlkit(
      [
        {
          frame: { origin: { x: 0, y: 0 }, size: { x: 100, y: 100 } },
          landmarks: [
            { type: 'leftEye', position: { x: 30, y: 40 } },
            { type: 'rightEye', position: { x: 70, y: 40 } },
            { type: 'noseBase', position: { x: 60, y: 60 } },
          ],
        },
      ],
      200,
      200
    );
    expect(s.noseOffset).toBeCloseTo(0.25);
  });

  it('ไม่มีหน้า = faceCount 0', () => {
    expect(sampleFromMlkit([], 100, 100).faceCount).toBe(0);
    expect(sampleFromMlkit(null, 100, 100).box).toBeNull();
  });
});

describe('framingHint', () => {
  it('บอกปัญหาการจัดกรอบ', () => {
    expect(framingHint(face({ faceCount: 0 }))).toBe('NO_FACE');
    expect(framingHint(face({ faceCount: 2 }))).toBe('MULTIPLE_FACES');
    expect(framingHint(face({ box: { x: 0.45, y: 0.45, w: 0.1, h: 0.1 } }))).toBe('MOVE_CLOSER');
    expect(framingHint(face({ box: { x: 0, y: 0, w: 0.95, h: 0.95 } }))).toBe('MOVE_BACK');
    expect(framingHint(face({ box: { x: 0.0, y: 0.3, w: 0.3, h: 0.3 } }))).toBe('CENTER_FACE');
    expect(framingHint(face())).toBeNull();
  });
});

describe('ทิศหันหน้า (ภาพกระจก)', () => {
  const base = face();
  it('จมูกไปทางซ้ายของภาพ = หันซ้ายของผู้ใช้', () => {
    expect(facingDirection(face({ noseOffset: -0.35 }), base)).toBe(-1);
    expect(evaluateChallenge('turn_left', face({ noseOffset: -0.35, yaw: -25 }), base).ok).toBe(true);
    expect(evaluateChallenge('turn_right', face({ noseOffset: -0.35, yaw: -25 }), base)).toEqual({ ok: false, hint: 'WRONG_WAY' });
  });

  it('จมูกไปทางขวาของภาพ = หันขวาของผู้ใช้', () => {
    expect(evaluateChallenge('turn_right', face({ noseOffset: 0.3 }), base).ok).toBe(true);
  });

  it('ไม่มี landmark → ใช้ yaw (บวก = ขวาของภาพ)', () => {
    expect(evaluateChallenge('turn_right', face({ noseOffset: null, yaw: 24 }), base).ok).toBe(true);
    expect(evaluateChallenge('turn_left', face({ noseOffset: null, yaw: -24 }), base).ok).toBe(true);
  });

  it('หันน้อยไป → MORE', () => {
    expect(evaluateChallenge('turn_left', face({ noseOffset: -0.12 }), base)).toEqual({ ok: false, hint: 'MORE' });
  });

  it('ภาพไม่กลับด้าน (mirrored=false) ทิศสลับ', () => {
    const cfg = { ...LIVENESS_CONFIG, mirrored: false };
    expect(evaluateChallenge('turn_left', face({ noseOffset: 0.3 }), base, cfg).ok).toBe(true);
  });
});

describe('ท่าอื่นๆ', () => {
  const base = face();
  it('กระพริบตา = ตาปิดทั้งสองข้าง', () => {
    expect(evaluateChallenge('blink', face({ leftEyeOpen: 0.1, rightEyeOpen: 0.2 }), base).ok).toBe(true);
    expect(evaluateChallenge('blink', face({ leftEyeOpen: 0.1, rightEyeOpen: 0.8 }), base).ok).toBe(false);
  });

  it('ยิ้ม · มองตรงตอนแรกยิ้มอยู่แล้วต้องยิ้มกว้างขึ้น', () => {
    expect(evaluateChallenge('smile', face({ smile: 0.85 }), base).ok).toBe(true);
    expect(evaluateChallenge('smile', face({ smile: 0.5 }), base)).toEqual({ ok: false, hint: 'MORE' });
    const smiley = face({ smile: 0.8 });
    expect(evaluateChallenge('smile', face({ smile: 0.85 }), smiley).ok).toBe(false);
    expect(evaluateChallenge('smile', face({ smile: 0.95 }), smiley).ok).toBe(true);
  });

  it('พยักหน้า = มุมก้มเงยเปลี่ยน ≥ 12°', () => {
    expect(evaluateChallenge('nod', face({ pitch: -14 }), base).ok).toBe(true);
    expect(evaluateChallenge('nod', face({ pitch: -7 }), base)).toEqual({ ok: false, hint: 'MORE' });
  });

  it('หลายหน้า = ไม่ผ่านทุกท่า', () => {
    expect(evaluateChallenge('nod', face({ faceCount: 2, pitch: -20 }), base).hint).toBe('MULTIPLE_FACES');
  });
});

describe('state machine', () => {
  it('มองตรง → ท่าตามลำดับ → จบ', () => {
    let st = createLivenessState(['blink', 'turn_right', 'smile']);
    expect(currentLabel(st)).toBe('neutral');
    expect(progressOf(st)).toBe(0);

    // หันอยู่ = ยังไม่รับเป็นเฟรมมองตรง
    let r = advance(st, face({ yaw: 30, noseOffset: 0.3 }));
    expect(r.accept).toBe(false);
    expect(r.hint).toBe('LOOK_STRAIGHT');

    r = advance(st, face());
    expect(r.accept).toBe(true);
    expect(r.label).toBe('neutral');
    st = r.state;
    expect(currentLabel(st)).toBe('blink');

    // ทำท่าผิดลำดับ (ยิ้ม) ไม่นับ
    r = advance(st, face({ smile: 0.95 }));
    expect(r.accept).toBe(false);

    r = advance(st, face({ leftEyeOpen: 0.05, rightEyeOpen: 0.05 }));
    expect(r.accept).toBe(true);
    st = r.state;

    r = advance(st, face({ noseOffset: 0.32, yaw: 25 }));
    expect(r.accept).toBe(true);
    st = r.state;

    r = advance(st, face({ smile: 0.9 }));
    expect(r.accept).toBe(true);
    st = r.state;
    expect(st.done).toBe(true);
    expect(st.captured).toEqual(['neutral', 'blink', 'turn_right', 'smile']);
    expect(currentLabel(st)).toBeNull();
    expect(progressOf(st)).toBe(1);

    // ป้อนต่อหลังจบ = ไม่ทำอะไร
    expect(advance(st, face()).accept).toBe(false);
  });

  it('ไม่แก้ state เดิม (immutable)', () => {
    const st = createLivenessState(['nod']);
    advance(st, face());
    expect(st.step).toBe(0);
    expect(st.captured).toEqual([]);
  });

  it('โหมดนับถอยหลังรับทุกเฟรมตามลำดับ', () => {
    let st = createLivenessState(['nod', 'blink']);
    const labels: string[] = [];
    while (!st.done) {
      const r = advanceGuided(st);
      labels.push(r.label as string);
      st = r.state;
    }
    expect(labels).toEqual(['neutral', 'nod', 'blink']);
  });
});

describe('ตัวช่วยกล้อง', () => {
  it('เดารูปหน้าบนบัตร (หน้าเล็ก) ไม่นับหน้าคนจริงที่ใหญ่', () => {
    expect(looksLikeCardPortrait(face({ box: { x: 0.6, y: 0.4, w: 0.12, h: 0.16 } }))).toBe(true);
    expect(looksLikeCardPortrait(face())).toBe(false);
    expect(looksLikeCardPortrait(face({ faceCount: 0 }))).toBe(false);
  });

  it('เลือกขนาดภาพใกล้เป้าที่สุด', () => {
    const sizes = ['4000x3000', '1920x1080', '1280x960', '640x480'];
    expect(pickPictureSize(sizes, 1280)).toBe('1280x960');
    expect(pickPictureSize(sizes, 1800, 1280)).toBe('1920x1080');
    expect(pickPictureSize(['640x480'], 1920, 1280)).toBe('640x480');
    expect(pickPictureSize([], 1280)).toBeUndefined();
    expect(pickPictureSize(['bad'], 1280)).toBeUndefined();
  });
});
