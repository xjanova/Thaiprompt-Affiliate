/**
 * ทดสอบตัวตรวจคุณภาพภาพบัตรประชาชนบนเครื่อง (ฟังก์ชันล้วน ไม่แตะกล้อง/native)
 *
 * รัน: npx jest services/ekyc/__tests__/cardQuality.test.ts
 *
 * เกณฑ์ใน CARD_QUALITY_CONFIG ตั้งจากภาพจำลองบัตร (deployment/ekyc-ai/tests/synth.py) วางบนโต๊ะในภาพ 1944×2592
 * แล้วทำให้เบลอ/สั่น/ไกล/ใกล้/มืด/จ้า/มีแสงสะท้อน — blurEffect ตรงกับ skimage.measure.blur_effect (ต่างไม่เกิน ~0.015)
 *   sigma (px ที่สเกล server 1000 px) 0.64 → 0.29 · 0.96 → 0.34 · 1.28 → 0.39 · 1.6 → 0.45 · 1.9 → 0.50
 */

import { describe, expect, it } from '@jest/globals';
import {
  analyzeCardShot,
  base64ToBytes,
  blurEffect,
  cardFromFace,
  findCardRect,
  frameInPhoto,
  glareFloorOf,
  isCardShotGood,
  isRotatedAgainstStage,
  judgeCard,
  judgeGeometry,
  judgeLight,
  largestGlareBlob,
  pickCardPictureSize,
  quickGeometryHint,
  regionStats,
  CARD_QUALITY_CONFIG,
  CARD_RATIO,
  type CardCropFn,
  type GrayImage,
  type Rect,
  type RegionStats,
  type RgbImage,
} from '../cardQuality';

// =====================================================
// ภาพจำลอง
// =====================================================

/** ตัวสุ่มที่ได้ค่าเดิมทุกครั้ง */
const lcg = (seed: number) => {
  let s = seed >>> 0;
  return () => {
    s = (s * 1664525 + 1013904223) >>> 0;
    return s / 4294967296;
  };
};

const makeRgb = (w: number, h: number, rgb: [number, number, number]): RgbImage => {
  const data = new Uint8Array(w * h * 3);
  for (let i = 0; i < w * h; i += 1) {
    data[i * 3] = rgb[0];
    data[i * 3 + 1] = rgb[1];
    data[i * 3 + 2] = rgb[2];
  }
  return { width: w, height: h, data };
};

const fillRect = (img: RgbImage, r: Rect, rgb: [number, number, number]): void => {
  const x0 = Math.max(0, Math.round(r.x));
  const y0 = Math.max(0, Math.round(r.y));
  const x1 = Math.min(img.width, Math.round(r.x + r.w));
  const y1 = Math.min(img.height, Math.round(r.y + r.h));
  for (let y = y0; y < y1; y += 1) {
    for (let x = x0; x < x1; x += 1) {
      const j = (y * img.width + x) * 3;
      img.data[j] = rgb[0];
      img.data[j + 1] = rgb[1];
      img.data[j + 2] = rgb[2];
    }
  }
};

const fillEllipse = (img: RgbImage, cx: number, cy: number, rx: number, ry: number, rgb: [number, number, number]): void => {
  for (let y = Math.floor(cy - ry); y <= Math.ceil(cy + ry); y += 1) {
    for (let x = Math.floor(cx - rx); x <= Math.ceil(cx + rx); x += 1) {
      if (x < 0 || y < 0 || x >= img.width || y >= img.height) continue;
      if (((x - cx) / rx) ** 2 + ((y - cy) / ry) ** 2 > 1) continue;
      const j = (y * img.width + x) * 3;
      img.data[j] = rgb[0];
      img.data[j + 1] = rgb[1];
      img.data[j + 2] = rgb[2];
    }
  }
};

/** "ตัวหนังสือ": ขีดตั้งสั้นๆ สุ่มระยะ ในแถบ r */
const drawText = (img: RgbImage, r: Rect, rand: () => number): void => {
  const lineH = Math.max(6, r.h * 0.6);
  let x = r.x;
  while (x < r.x + r.w) {
    const sw = 1 + Math.round(rand() * 2);
    const h = lineH * (0.5 + rand() * 0.5);
    fillRect(img, { x, y: r.y + (lineH - h), w: sw, h }, [40, 40, 60]);
    if (rand() < 0.4) fillRect(img, { x, y: r.y + lineH * 0.45, w: 4 + rand() * 5, h: 2 }, [40, 40, 60]);
    x += sw + 2 + Math.round(rand() * 5);
  }
};

/** บัตรด้านหน้าแบบง่าย (พื้นพาสเทล + ตัวหนังสือตามแม่แบบ + รูปติดบัตร) */
const drawCard = (img: RgbImage, c: Rect, seed = 7): void => {
  const rand = lcg(seed);
  fillRect(img, c, [214, 204, 210]);
  const rows: Array<[number, number, number]> = [
    [0.3, 0.1, 0.62],
    [0.25, 0.19, 0.7],
    [0.3, 0.3, 0.55],
    [0.32, 0.44, 0.5],
    [0.05, 0.64, 0.6],
    [0.05, 0.8, 0.25],
    [0.5, 0.8, 0.25],
  ];
  for (const [u, v, len] of rows) drawText(img, { x: c.x + u * c.w, y: c.y + v * c.h, w: len * c.w, h: 0.05 * c.h }, rand);
  // รูปติดบัตร: พื้นฟ้าอ่อน + หน้า (โทนผิว) + ผม
  fillRect(img, { x: c.x + 0.745 * c.w, y: c.y + 0.47 * c.h, w: 0.225 * c.w, h: 0.46 * c.h }, [170, 190, 215]);
  fillEllipse(img, c.x + 0.858 * c.w, c.y + 0.69 * c.h, 0.07 * c.w, 0.16 * c.h, [190, 150, 125]);
  fillEllipse(img, c.x + 0.858 * c.w, c.y + 0.56 * c.h, 0.075 * c.w, 0.07 * c.h, [35, 30, 30]);
  fillEllipse(img, c.x + 0.83 * c.w, c.y + 0.66 * c.h, 0.01 * c.w, 0.01 * c.h, [40, 30, 30]);
  fillEllipse(img, c.x + 0.886 * c.w, c.y + 0.66 * c.h, 0.01 * c.w, 0.01 * c.h, [40, 30, 30]);
};

const addNoise = (img: RgbImage, amp: number, seed = 3): void => {
  const rand = lcg(seed);
  for (let i = 0; i < img.data.length; i += 1) {
    img.data[i] = Math.max(0, Math.min(255, img.data[i] + Math.round((rand() - 0.5) * 2 * amp)));
  }
};

/** box blur แยกแกน รัศมี r (ประมาณ sigma ≈ r × 0.6) */
const boxBlur = (img: RgbImage, r: number): RgbImage => {
  const { width: W, height: H } = img;
  // ผลรวมสะสมตามแนว (ขอบภาพใช้ค่าขอบซ้ำ)
  const pass = (src: Uint8Array, horizontal: boolean): Uint8Array => {
    const out = new Uint8Array(src.length);
    const len = horizontal ? W : H;
    const lines = horizontal ? H : W;
    const idx = (line: number, i: number) => (horizontal ? line * W + i : i * W + line) * 3;
    for (let line = 0; line < lines; line += 1) {
      for (let ch = 0; ch < 3; ch += 1) {
        const at = (i: number) => src[idx(line, Math.min(len - 1, Math.max(0, i))) + ch];
        let s = 0;
        for (let k = -r; k <= r; k += 1) s += at(k);
        for (let i = 0; i < len; i += 1) {
          out[idx(line, i) + ch] = Math.round(s / (2 * r + 1));
          s += at(i + r + 1) - at(i - r);
        }
      }
    }
    return out;
  };
  return { width: W, height: H, data: pass(pass(img.data, true), false) };
};

/** ตัวครอปจำลอง: ครอป + ย่อแบบเฉลี่ยพื้นที่ (แทน expo-image-manipulator) */
const cropperOf = (photo: RgbImage): CardCropFn => async (r, outWidth) => {
  const ow = Math.max(1, Math.min(r.w, Math.round(outWidth)));
  const k = r.w / ow;
  const oh = Math.max(1, Math.round(r.h / k));
  const out = new Uint8Array(ow * oh * 3);
  for (let y = 0; y < oh; y += 1) {
    for (let x = 0; x < ow; x += 1) {
      const sx0 = Math.floor(r.x + x * k);
      const sy0 = Math.floor(r.y + y * k);
      const sx1 = Math.max(sx0 + 1, Math.floor(r.x + (x + 1) * k));
      const sy1 = Math.max(sy0 + 1, Math.floor(r.y + (y + 1) * k));
      for (let ch = 0; ch < 3; ch += 1) {
        let s = 0;
        let n = 0;
        for (let yy = sy0; yy < Math.min(sy1, photo.height); yy += 1) {
          for (let xx = sx0; xx < Math.min(sx1, photo.width); xx += 1) {
            s += photo.data[(yy * photo.width + xx) * 3 + ch];
            n += 1;
          }
        }
        out[(y * ow + x) * 3 + ch] = n ? Math.round(s / n) : 0;
      }
    }
  }
  return { width: ow, height: oh, data: out };
};

// ภาพถ่ายแนวตั้ง 3:4 + จอแบบโทรศัพท์ทั่วไป (กรอบเหมือน capture.tsx)
const PHOTO = { w: 600, h: 800 };
const STAGE = { w: 390, h: 520 };
const FRAME_W = STAGE.w - 48;
const FRAME_H = FRAME_W / CARD_RATIO;
const FRAME = { x: 24, y: STAGE.h * 0.4 - FRAME_H / 2, w: FRAME_W, h: FRAME_H };
const FRAME_P = frameInPhoto(FRAME, STAGE, PHOTO);
/** ภาพจำลองย่อขนาดลง (บัตรกว้างราว 500 px) — ปิดเกณฑ์ความละเอียดขั้นต่ำ (ทดสอบแยกด้วยกรอบขนาดจริง) */
const SCALED = { ...CARD_QUALITY_CONFIG, minCardPx: 0, relaxedMinCardPx: 0 };

/** บัตรขนาด fill เท่าของกรอบ วางกลางกรอบ (เลื่อนได้) */
const cardAt = (fill: number, dx = 0, dy = 0): Rect => {
  const w = FRAME_P.w * fill;
  const h = w / CARD_RATIO;
  return {
    x: FRAME_P.x + (FRAME_P.w - w) / 2 + dx * FRAME_P.w,
    y: FRAME_P.y + (FRAME_P.h - h) / 2 + dy * FRAME_P.h,
    w,
    h,
  };
};

/** กรอบใบหน้าตามแม่แบบ (แบบที่ ML Kit คืนมา) */
const faceOn = (c: Rect) => {
  const fw = 0.15 * c.w;
  return {
    frame: { origin: { x: c.x + 0.858 * c.w - fw / 2, y: c.y + 0.67 * c.h - fw * 0.6 }, size: { x: fw, y: fw * 1.2 } },
    headEulerAngleZ: 0,
  };
};

const scene = (card: Rect, bg: [number, number, number] = [38, 42, 48]): RgbImage => {
  const img = makeRgb(PHOTO.w, PHOTO.h, bg);
  drawCard(img, card);
  addNoise(img, 3);
  return img;
};

const input = (card: Rect, faces?: unknown[]) => ({
  width: PHOTO.w,
  height: PHOTO.h,
  faces: (faces ?? [faceOn(card)]) as never,
  frame: FRAME,
  stage: STAGE,
});

const stats = (over: Partial<RegionStats> = {}): RegionStats => ({ mean: 190, p5: 40, p50: 200, p95: 230, clipped: 0, glare: 0, ...over });

// =====================================================
// เรขาคณิต
// =====================================================

describe('frameInPhoto', () => {
  it('จอกับภาพสัดส่วนเดียวกัน = ย่อ/ขยายตรงๆ', () => {
    const f = frameInPhoto({ x: 24, y: 100, w: 342, h: 216 }, { w: 390, h: 520 }, { w: 1944, h: 2592 });
    expect(f.x).toBeCloseTo(24 * (1944 / 390), 3);
    expect(f.w).toBeCloseTo(342 * (1944 / 390), 3);
    expect(f.y).toBeCloseTo(100 * (1944 / 390), 3);
  });

  it('จอสูงกว่าภาพ = พรีวิวตัดขอบซ้าย-ขวา (FILL_CENTER)', () => {
    const f = frameInPhoto({ x: 24, y: 100, w: 342, h: 216 }, { w: 390, h: 600 }, { w: 1944, h: 2592 });
    const scale = 600 / 2592;
    const offX = (390 - 1944 * scale) / 2;
    expect(offX).toBeLessThan(0);
    expect(f.x).toBeCloseTo((24 - offX) / scale, 3);
    expect(f.w).toBeCloseTo(342 / scale, 3);
    expect(f.y).toBeCloseTo(100 / scale, 3);
  });
});

describe('cardFromFace', () => {
  it('เดาบัตรกลับจากกรอบใบหน้าตามแม่แบบได้ตรง', () => {
    const card = { x: 100, y: 200, w: 1000, h: 1000 / CARD_RATIO };
    const f = faceOn(card).frame;
    const est = cardFromFace({ x: f.origin.x, y: f.origin.y, w: f.size.x, h: f.size.y });
    expect(est.x).toBeCloseTo(card.x, 0);
    expect(est.y).toBeCloseTo(card.y, 0);
    expect(est.w).toBeCloseTo(card.w, 0);
  });

  it('ใช้สัดส่วนใบหน้าที่เรียนรู้ได้ · ค่าเพี้ยนเกินช่วงใช้แม่แบบ', () => {
    const face = { x: 0, y: 0, w: 150, h: 180 };
    expect(cardFromFace(face, 0.2).w).toBeCloseTo(750, 3);
    expect(cardFromFace(face, 0.5).w).toBeCloseTo(1000, 3);
  });
});

describe('findCardRect', () => {
  const thumbOf = (bg: [number, number, number]) => {
    const img = makeRgb(320, 300, bg);
    drawCard(img, { x: 40, y: 75, w: 240, h: 240 / CARD_RATIO });
    addNoise(img, 3);
    const gray = new Uint8Array(320 * 300);
    for (let i = 0; i < gray.length; i += 1) gray[i] = img.data[i * 3 + 1];
    return { width: 320, height: 300, data: gray } as GrayImage;
  };

  it('พื้นเข้ม: เจอขอบบัตรจริงแม่นภายใน 3 px แม้กรอบที่คาดไว้ใหญ่กว่า', () => {
    const r = findCardRect(thumbOf([35, 40, 45]), { x: 25, y: 65, w: 270, h: 170 });
    expect(r).not.toBeNull();
    expect(Math.abs(r!.x - 40)).toBeLessThanOrEqual(3);
    expect(Math.abs(r!.x + r!.w - 280)).toBeLessThanOrEqual(3);
    expect(Math.abs(r!.y - 75)).toBeLessThanOrEqual(3);
    expect(Math.abs(r!.y + r!.h - (75 + 240 / CARD_RATIO))).toBeLessThanOrEqual(3);
  });

  it('ภาพเรียบไม่มีบัตร = ไม่เจอ', () => {
    const flat = { width: 200, height: 200, data: new Uint8Array(200 * 200).fill(120) };
    expect(findCardRect(flat, { x: 20, y: 40, w: 160, h: 100 })).toBeNull();
  });
});

// =====================================================
// พิกเซล
// =====================================================

describe('regionStats', () => {
  it('ดวงแสงสะท้อนเป็นก้อน = นับ · จุดขาวเดี่ยวๆ ไม่นับเป็นแสงสะท้อน', () => {
    const img = makeRgb(100, 100, [120, 110, 100]);
    fillRect(img, { x: 40, y: 40, w: 20, h: 20 }, [255, 255, 255]);
    const s = regionStats(img, { x: 0, y: 0, w: 100, h: 100 })!;
    expect(s.clipped).toBeCloseTo(0.04, 3);
    expect(s.glare).toBeCloseTo((18 * 18) / 10000, 3);

    const specks = makeRgb(100, 100, [120, 110, 100]);
    for (let i = 5; i < 95; i += 7) fillRect(specks, { x: i, y: i, w: 1, h: 1 }, [255, 255, 255]);
    const t = regionStats(specks, { x: 0, y: 0, w: 100, h: 100 })!;
    expect(t.clipped).toBeGreaterThan(0);
    expect(t.glare).toBe(0);
  });

  it('พื้นบัตรสีอ่อนที่สว่างเกิน (ไม่ถึงขาวจ้า) ไม่นับเป็นแสงสะท้อนเมื่อใช้เกณฑ์เทียบพื้นบัตร', () => {
    const pale = makeRgb(100, 100, [246, 244, 247]);
    const raw = regionStats(pale, { x: 0, y: 0, w: 100, h: 100 })!;
    expect(raw.glare).toBeGreaterThan(0.9); // เกณฑ์ตายตัว 245 = นับผิด
    expect(raw.p50).toBe(245);
    expect(glareFloorOf(raw.p50)).toBe(252);
    expect(regionStats(pale, { x: 0, y: 0, w: 100, h: 100 }, glareFloorOf(raw.p50))!.glare).toBe(0);
    expect(glareFloorOf(180)).toBe(245);
    expect(glareFloorOf(null)).toBe(245);
  });

  it('สีสดจ้า (ไม่ใช่ขาว) ไม่ใช่แสงสะท้อน · ครอปเลยขอบภาพตัดให้เอง', () => {
    const img = makeRgb(50, 50, [255, 200, 40]);
    const s = regionStats(img, { x: -10, y: -10, w: 80, h: 80 })!;
    expect(s.glare).toBe(0);
    expect(regionStats(img, { x: 60, y: 60, w: 10, h: 10 })).toBeNull();
  });
});

describe('largestGlareBlob', () => {
  it('นับดวงที่ใหญ่สุด (ต่อกัน 4 ทิศ) ไม่ใช่ผลรวมจุดกระจาย · เกณฑ์เทียบค่ากลางของภาพ', () => {
    const img = makeRgb(200, 60, [200, 190, 195]);
    fillRect(img, { x: 20, y: 10, w: 12, h: 10 }, [255, 255, 255]);
    fillRect(img, { x: 100, y: 10, w: 5, h: 5 }, [255, 255, 255]);
    for (let x = 150; x < 190; x += 4) fillRect(img, { x, y: 40, w: 1, h: 1 }, [255, 255, 255]);
    expect(largestGlareBlob(img)).toBe(120);
    // ภาพสว่างทั้งภาพ (ค่ากลาง ≥ 220) ต้องสว่างกว่าค่ากลาง +35 → 250 ไม่ถึง 254
    const bright = makeRgb(50, 50, [250, 250, 250]);
    expect(largestGlareBlob(bright)).toBe(0);
  });
});

describe('blurEffect', () => {
  const textPatch = (): RgbImage => {
    const img = makeRgb(400, 120, [214, 204, 210]);
    const rand = lcg(11);
    drawText(img, { x: 10, y: 10, w: 380, h: 30 }, rand);
    drawText(img, { x: 10, y: 60, w: 380, h: 30 }, rand);
    return img;
  };
  const gray = (img: RgbImage): GrayImage => {
    const g = new Uint8Array(img.width * img.height);
    for (let i = 0; i < g.length; i += 1) g[i] = img.data[i * 3 + 1];
    return { width: img.width, height: img.height, data: g };
  };

  it('ยิ่งเบลอค่ายิ่งสูง (0 = คม, 1 = เบลอ)', () => {
    const sharp = blurEffect(gray(textPatch()));
    const soft = blurEffect(gray(boxBlur(textPatch(), 2)));
    const blurry = blurEffect(gray(boxBlur(textPatch(), 4)));
    expect(sharp).toBeLessThan(0.3);
    expect(soft).toBeGreaterThan(sharp + 0.05);
    expect(blurry).toBeGreaterThan(soft + 0.05);
    expect(blurry).toBeLessThanOrEqual(1);
  });

  it('ไม่ขึ้นกับความเข้มของคอนทราสต์', () => {
    const a = gray(textPatch());
    const half = { ...a, data: a.data.map((v) => Math.round(v / 2)) };
    expect(Math.abs(blurEffect(a) - blurEffect(half))).toBeLessThan(0.03);
  });

  it('ภาพเรียบ/เล็กเกิน = ถือว่าเบลอ', () => {
    expect(blurEffect({ width: 50, height: 50, data: new Uint8Array(2500).fill(128) })).toBe(1);
    expect(blurEffect({ width: 4, height: 4, data: new Uint8Array(16) })).toBe(1);
  });
});

// =====================================================
// ตัดสิน
// =====================================================

describe('judgeGeometry', () => {
  const faceIn = (c: Rect, roll = 0) => {
    const f = faceOn(c).frame;
    return { box: { x: f.origin.x, y: f.origin.y, w: f.size.x, h: f.size.y }, roll };
  };

  it('ไม่เห็นรูปหน้าบนบัตร = NO_CARD', () => {
    expect(judgeGeometry(FRAME_P, null, null, SCALED).hint).toBe('NO_CARD');
  });

  it('เดาจากรูปหน้า: ไกล / ใกล้ / ไม่อยู่กลาง / เอียง / พอดี', () => {
    expect(judgeGeometry(FRAME_P, faceIn(cardAt(0.6)), null, SCALED).hint).toBe('TOO_FAR');
    expect(judgeGeometry(FRAME_P, faceIn(cardAt(1.35)), null, SCALED).hint).toBe('TOO_CLOSE');
    expect(judgeGeometry(FRAME_P, faceIn(cardAt(0.95, 0.2)), null, SCALED).hint).toBe('OFF_CENTER');
    expect(judgeGeometry(FRAME_P, faceIn(cardAt(0.95), 14), null, SCALED).hint).toBe('TILTED');
    const ok = judgeGeometry(FRAME_P, faceIn(cardAt(0.95)), null, SCALED);
    expect(ok.hint).toBeNull();
    expect(ok.source).toBe('face');
  });

  it('เจอขอบบัตรที่เข้ากับรูปหน้า = ใช้ขอบจริง (เกณฑ์แคบกว่า)', () => {
    const c = cardAt(0.79);
    const r = judgeGeometry(FRAME_P, faceIn(c), c, SCALED);
    expect(r.source).toBe('edges');
    expect(r.hint).toBe('TOO_FAR');
    expect(judgeGeometry(FRAME_P, faceIn(cardAt(0.9)), cardAt(0.9), SCALED).hint).toBeNull();
    expect(judgeGeometry(FRAME_P, faceIn(cardAt(1.1)), cardAt(1.1), SCALED).hint).toBe('TOO_CLOSE');
  });

  it('โหมดผ่อนระยะ: บัตรเล็กกว่ากรอบได้ถ้ายังกว้าง ≥ 1050 px ในภาพจริง', () => {
    // กรอบในภาพ 1700 px → ปกติต้อง ≥ 0.8 (1360 px) · ผ่อนแล้ว ≥ max(0.55, 1050/1700 = 0.62)
    const frame = { x: 100, y: 800, w: 1700, h: 1700 / CARD_RATIO };
    const at = (fill: number) => {
      const w = frame.w * fill;
      const h = w / CARD_RATIO;
      return { x: frame.x + (frame.w - w) / 2, y: frame.y + (frame.h - h) / 2, w, h };
    };
    const c = at(0.66);
    expect(judgeGeometry(frame, faceIn(c), c).hint).toBe('TOO_FAR');
    expect(judgeGeometry(frame, faceIn(c), c, undefined, true).hint).toBeNull();
    const tiny = at(0.58);
    expect(judgeGeometry(frame, faceIn(tiny), tiny, undefined, true).hint).toBe('TOO_FAR');
  });

  it('ภาพละเอียดต่ำ: บัตรต้องกว้าง ≥ 950 px แม้เกณฑ์สัดส่วนผ่าน', () => {
    const frame = { x: 50, y: 300, w: 1000, h: 1000 / CARD_RATIO };
    const w = 900;
    const c = { x: frame.x + 50, y: frame.y + 10, w, h: w / CARD_RATIO };
    expect(judgeGeometry(frame, faceIn(c), c).hint).toBe('TOO_FAR');
  });

  it('ขอบที่ไม่เข้ากับรูปหน้า (เช่น จับขอบรูปติดบัตร/ขอบโต๊ะ) = ไม่ใช้', () => {
    const c = cardAt(0.9);
    const wrong = { x: c.x, y: c.y, w: c.w * 0.6, h: c.h }; // สัดส่วนไม่ใช่บัตร
    expect(judgeGeometry(FRAME_P, faceIn(c), wrong, SCALED).source).toBe('face');
  });

  it('เช็คเร็ว: ห่างกรอบมากบอกเลย ไม่ต้องอ่านพิกเซล', () => {
    expect(quickGeometryHint(FRAME_P, faceIn(cardAt(0.4)))).toBe('TOO_FAR');
    expect(quickGeometryHint(FRAME_P, faceIn(cardAt(1.7)))).toBe('TOO_CLOSE');
    expect(quickGeometryHint(FRAME_P, faceIn(cardAt(0.75)))).toBeNull();
    expect(quickGeometryHint(FRAME_P, null)).toBe('NO_CARD');
  });
});

describe('judgeLight', () => {
  it('ลำดับ: มืด → จ้า → สะท้อนบนรูปหน้า → สะท้อนบนตัวหนังสือ', () => {
    expect(judgeLight({ card: stats({ mean: 50 }), face: stats({ glare: 0.2 }), text: null })).toBe('TOO_DARK');
    expect(judgeLight({ card: stats({ clipped: 0.5 }), face: null, text: null })).toBe('TOO_BRIGHT');
    expect(judgeLight({ card: stats(), face: stats({ glare: 0.03 }), text: stats({ glare: 0.1 }) })).toBe('GLARE_FACE');
    expect(judgeLight({ card: stats(), face: stats(), text: stats({ glare: 0.05 }) })).toBe('GLARE');
    expect(judgeLight({ card: stats(), face: stats(), text: stats() })).toBeNull();
  });

  it('รูปหน้าซีดจากแสงสะท้อน (คอนทราสต์หาย + สว่าง) = GLARE_FACE · หน้าคอนทราสต์ต่ำแต่ไม่สว่างไม่นับ', () => {
    expect(judgeLight({ card: stats(), face: stats({ mean: 210, p5: 190, p95: 225 }), text: stats() })).toBe('GLARE_FACE');
    expect(judgeLight({ card: stats(), face: stats({ mean: 120, p5: 100, p95: 135 }), text: stats() })).toBeNull();
  });
});

describe('judgeCard / isCardShotGood', () => {
  const geom = { hint: null, card: { x: 0, y: 0, w: 1000, h: 630 }, source: 'edges' as const };

  it('ผ่านครบ = GOOD · ยังไม่ได้วัดความคม = ไม่ถ่ายอัตโนมัติ', () => {
    const light = { card: stats(), face: stats(), text: stats() };
    expect(isCardShotGood(judgeCard(geom, light, 0.3))).toBe(true);
    const unmeasured = judgeCard(geom, light, null);
    expect(unmeasured.hint).toBe('GOOD');
    expect(isCardShotGood(unmeasured)).toBe(false);
    expect(isCardShotGood(judgeCard(geom, null, null))).toBe(false);
  });

  it('เบลอ = BLURRY · ตำแหน่งมาก่อนแสง แสงมาก่อนความคม', () => {
    const light = { card: stats(), face: stats(), text: stats() };
    expect(judgeCard(geom, light, 0.55).hint).toBe('BLURRY');
    expect(judgeCard(geom, { ...light, face: stats({ glare: 0.1 }) }, 0.55).hint).toBe('GLARE_FACE');
    expect(judgeCard({ ...geom, hint: 'TOO_FAR' }, { ...light, face: stats({ glare: 0.1 }) }, 0.55).hint).toBe('TOO_FAR');
  });
});

// =====================================================
// ทั้งสาย (ภาพจำลอง + ตัวครอปจำลอง)
// =====================================================

describe('analyzeCardShot', () => {
  it('บัตรเต็มกรอบ คม แสงพอดี = GOOD จากขอบบัตรจริง + เรียนรู้สัดส่วนใบหน้า', async () => {
    const card = cardAt(0.92);
    const v = await analyzeCardShot(input(card), cropperOf(scene(card)), SCALED);
    expect(v.hint).toBe('GOOD');
    expect(v.source).toBe('edges');
    expect(isCardShotGood(v)).toBe(true);
    expect(v.card!.w / card.w).toBeGreaterThan(0.97);
    expect(v.card!.w / card.w).toBeLessThan(1.03);
    expect(v.faceRatio).toBeCloseTo(0.15, 2);
  });

  it('ภาพเบลอ = BLURRY (ตำแหน่ง/แสงผ่าน)', async () => {
    const card = cardAt(0.92);
    const v = await analyzeCardShot(input(card), cropperOf(boxBlur(scene(card), 3)), SCALED);
    expect(v.fit).toBe(true);
    expect(v.light).toBe(true);
    expect(v.hint).toBe('BLURRY');
    expect(isCardShotGood(v)).toBe(false);
  });

  it('แสงสะท้อนบนรูปหน้า = GLARE_FACE', async () => {
    const card = cardAt(0.92);
    const img = scene(card);
    fillEllipse(img, card.x + 0.85 * card.w, card.y + 0.66 * card.h, 0.05 * card.w, 0.06 * card.h, [255, 255, 255]);
    const v = await analyzeCardShot(input(card), cropperOf(img), SCALED);
    expect(v.hint).toBe('GLARE_FACE');
  });

  it('ดวงแสงสะท้อนเล็กทับเลขบัตรไม่กี่ตัว (ภาพย่อมองไม่เห็น) = GLARE จากแถบละเอียด', async () => {
    const card = cardAt(0.92);
    const img = scene(card);
    fillEllipse(img, card.x + 0.5 * card.w, card.y + 0.13 * card.h, 0.035 * card.w, 0.035 * card.h, [255, 255, 255]);
    const v = await analyzeCardShot(input(card), cropperOf(img), SCALED);
    expect(v.hint).toBe('GLARE');
    expect(v.light).toBe(false);
  });

  it('ภาพหมุนต่างจากจอ = ROTATE (ไม่เทียบกรอบผิด)', async () => {
    const card = cardAt(0.92);
    const v = await analyzeCardShot({ ...input(card), width: PHOTO.h, height: PHOTO.w }, null);
    expect(v.hint).toBe('ROTATE');
    expect(isRotatedAgainstStage({ w: 4, h: 3 }, { w: 390, h: 520 })).toBe(true);
    expect(isRotatedAgainstStage({ w: 3, h: 4 }, { w: 390, h: 520 })).toBe(false);
  });

  it('บัตรเล็กในกรอบ = TOO_FAR · ไม่มีรูปหน้า = NO_CARD (ไม่ครอปเลย)', async () => {
    const small = cardAt(0.7);
    expect((await analyzeCardShot(input(small), cropperOf(scene(small)), SCALED)).hint).toBe('TOO_FAR');
    let crops = 0;
    const counting: CardCropFn = async () => {
      crops += 1;
      return null;
    };
    expect((await analyzeCardShot(input(small, []), counting, SCALED)).hint).toBe('NO_CARD');
    expect(crops).toBe(0);
  });

  it('ไม่มีตัวครอป (build ไม่มี native) = ตัดสินจากรูปหน้าอย่างเดียว ไม่ถือว่าพร้อมถ่าย', async () => {
    const card = cardAt(0.92);
    const v = await analyzeCardShot(input(card), null, SCALED);
    expect(v.fit).toBe(true);
    expect(v.light).toBeNull();
    expect(v.sharp).toBeNull();
    expect(isCardShotGood(v)).toBe(false);
  });

  it('ขนาดภาพ/จอยังไม่รู้ = NO_CARD', async () => {
    const card = cardAt(0.92);
    expect((await analyzeCardShot({ ...input(card), stage: { w: 0, h: 0 } }, null, SCALED)).hint).toBe('NO_CARD');
  });
});

// =====================================================
// อื่นๆ
// =====================================================

describe('base64ToBytes', () => {
  it('ตรงกับ Buffer ทั้งแบบมี padding / ขึ้นบรรทัด', () => {
    const rand = lcg(5);
    for (const n of [0, 1, 2, 3, 4, 100, 1001]) {
      const bytes = Uint8Array.from({ length: n }, () => Math.floor(rand() * 256));
      const b64 = Buffer.from(bytes).toString('base64');
      expect(Array.from(base64ToBytes(b64))).toEqual(Array.from(bytes));
      expect(Array.from(base64ToBytes(b64.replace(/(.{76})/g, '$1\n')))).toEqual(Array.from(bytes));
    }
  });
});

describe('pickCardPictureSize', () => {
  it('เลือก 4:3 ที่ด้านยาวใกล้ 2600 ไม่เกิน 3300', () => {
    expect(pickCardPictureSize(['4000x3000', '3264x2448', '2592x1944', '1920x1080', '2560x1440', '1280x960'])).toBe('2592x1944');
    expect(pickCardPictureSize(['4000x3000', '1920x1440', '1280x960'])).toBe('1920x1440');
  });

  it('ไม่มีขนาดที่ใช้ได้ (preset ของ iOS / เล็กไป / 16:9 ล้วน) = undefined', () => {
    expect(pickCardPictureSize(['Photo', 'High', 'Medium'])).toBeUndefined();
    expect(pickCardPictureSize(['1280x960', '640x480'])).toBeUndefined();
    expect(pickCardPictureSize(['3840x2160', '1920x1080'])).toBeUndefined();
    expect(pickCardPictureSize(null)).toBeUndefined();
  });
});
