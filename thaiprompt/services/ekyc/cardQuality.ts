/**
 * ตัวตรวจคุณภาพภาพบัตรประชาชนบนเครื่อง ก่อนถ่ายอัตโนมัติ — ฟังก์ชันล้วน ไม่แตะกล้อง/native (ทดสอบได้ใน jest)
 *
 * ทำไมต้องมี: เดิมถ่ายทันทีที่ "เห็นรูปหน้าบนบัตร" → บัตรเล็กในภาพ / ยังไม่โฟกัส / มีแสงสะท้อนบนรูปหน้า ก็ถ่าย
 *   server ปรับบัตรเป็น 1000×630 ก่อน OCR — บัตรในภาพเล็กกว่านั้นหรือเบลอ = ตัวหนังสือไทยแตก อ่านชื่อ/เลขผิด
 *
 * ขั้นตอนต่อ 1 ภาพ (cardAnalyzer.ts เป็นคนป้อนพิกเซล)
 *   1. ตำแหน่งบัตร: หาขอบบัตรจริงจากภาพย่อรอบกรอบ (findCardRect) → ไม่เจอใช้ตำแหน่งที่เดาจากรูปหน้าบนบัตร (cardFromFace)
 *      เทียบกับกรอบที่วาดบนจอ (frameInPhoto) → ไกลไป / ใกล้ไป / ไม่อยู่กลางกรอบ / เอียง
 *   2. แสง: บัตรมืด / จ้าเกิน / แสงสะท้อนบนรูปหน้า / แสงสะท้อนบนตัวหนังสือ (regionStats)
 *   3. ความคม: วัดบนแถบเลขประจำตัว + ชื่อไทย ที่สเกลเดียวกับ server (บัตรกว้าง 1000 px) ด้วย blurEffect
 *   ผ่านครบ → ถ่ายภาพนี้เลย (ภาพที่ตรวจ = ภาพที่ส่ง)
 *
 * พิกัดทั้งหมดเป็นพิกเซล (Rect) ของภาพที่ระบุในแต่ละฟังก์ชัน
 */

import { looksLikeCardPortrait, sampleFromMlkit, type MlkitFaceLike } from './liveness';

// =====================================================
// ชนิดข้อมูล
// =====================================================

export interface Rect {
  x: number;
  y: number;
  w: number;
  h: number;
}

export interface Size {
  w: number;
  h: number;
}

/** ภาพสี RGB 3 ช่องต่อพิกเซล (jpeg-js formatAsRGBA: false) */
export interface RgbImage {
  width: number;
  height: number;
  data: Uint8Array;
}

/** ภาพขาวดำ 1 ช่องต่อพิกเซล */
export interface GrayImage {
  width: number;
  height: number;
  data: Uint8Array;
}

/** สัดส่วนบัตรประชาชน (ISO ID-1: 85.6 × 54 มม.) */
export const CARD_RATIO = 85.6 / 54;

/**
 * แม่แบบบัตรด้านหน้า (สัดส่วนของความกว้าง/สูงบัตร) — ตรงกับ server (ocr.PHOTO_ZONE / KEY_ZONES, tests/synth.py)
 *   รูปติดบัตร x 0.745–0.97, y 0.47–0.93 · กรอบใบหน้า (ML Kit) ≈ 2/3 ของความกว้างรูป
 */
export const CARD_LAYOUT = {
  faceCx: 0.858,
  faceCy: 0.67,
  /** ความกว้างกรอบใบหน้า / ความกว้างบัตร */
  faceW: 0.15,
  /** แถบเลขประจำตัวประชาชน + ชื่อ-สกุลไทย (ข้อมูลที่ OCR ต้องอ่านให้ถูกที่สุด) — ใช้วัดแสงสะท้อน */
  textStrip: { x0: 0.3, x1: 0.97, y0: 0.08, y1: 0.36 },
  /** ส่วนกลางของแถบเดียวกัน — ใช้วัดความคม (ถอดรหัสภาพในเครื่องน้อยลง บัตรแบนราบ โฟกัสเท่ากันทั้งใบ) */
  sharpStrip: { x0: 0.34, x1: 0.8, y0: 0.08, y1: 0.36 },
} as const;

/** ความกว้างบัตรที่ server ใช้อ่าน (px) — วัดความคมที่สเกลนี้ */
export const SERVER_CARD_WIDTH = 1000;

export const CARD_QUALITY_CONFIG = {
  // ---------- ตำแหน่ง/ระยะ ----------
  /** เจอขอบบัตรจริง: ความกว้างบัตร / ความกว้างกรอบ ที่รับได้ */
  edgeMinFill: 0.8,
  edgeMaxFill: 1.06,
  /** เดาจากรูปหน้า (ไม่เจอขอบ): เผื่อความคลาดเคลื่อนของขนาดใบหน้า */
  faceMinFill: 0.78,
  faceMaxFill: 1.22,
  /** กึ่งกลางบัตรห่างจากกึ่งกลางกรอบได้ไม่เกิน (สัดส่วนความกว้าง/สูงกรอบ) */
  edgeMaxOffsetX: 0.09,
  edgeMaxOffsetY: 0.14,
  faceMaxOffsetX: 0.15,
  faceMaxOffsetY: 0.22,
  /** บัตรเอียง (มุมเอียงของรูปหน้าบนบัตร, องศา) */
  maxRoll: 9,
  /** ห่างจากกรอบมากเกินนี้ ไม่ต้องเสียเวลาอ่านพิกเซล (บอกให้ขยับก่อน) */
  quickMinFill: 0.55,
  quickMaxFill: 1.5,

  // ---------- แสง ----------
  /** ความสว่างเฉลี่ยบนบัตร (0–255) ต่ำกว่านี้ = มืด (บัตรสีอ่อน แสงพอดีอยู่ราว 150–220) */
  minCardMean: 72,
  /** พิกเซลขาวจ้า (≥ 250) เกินสัดส่วนนี้ของบัตร = สว่างจ้าทั้งใบ */
  maxCardClipped: 0.3,
  /** แสงสะท้อนบนรูปหน้า (สัดส่วนพื้นที่ใบหน้า) */
  maxFaceGlare: 0.015,
  /** รูปหน้าซีดจากแสงสะท้อน: ช่วงความสว่าง p95−p5 แคบกว่านี้ และสว่างเฉลี่ยเกิน faceHazeMean */
  minFaceRange: 45,
  faceHazeMean: 185,
  /** แสงสะท้อนบนแถบตัวหนังสือ / ทั้งบัตร */
  maxTextGlare: 0.025,
  maxCardGlare: 0.05,

  // ---------- ความคม ----------
  /** blurEffect บนแถบตัวหนังสือ (0 = คม, 1 = เบลอ) เกินนี้ = ยังไม่คมพอให้ OCR อ่านภาษาไทย */
  maxBlur: 0.4,
} as const;

export type CardQualityConfig = Record<keyof typeof CARD_QUALITY_CONFIG, number>;

/** คำใบ้ให้หน้าจอแปลงเป็นข้อความ */
export type CardHint =
  | 'NO_CARD'
  | 'TILTED'
  | 'TOO_FAR'
  | 'TOO_CLOSE'
  | 'OFF_CENTER'
  | 'TOO_DARK'
  | 'TOO_BRIGHT'
  | 'GLARE_FACE'
  | 'GLARE'
  | 'BLURRY'
  | 'GOOD';

// =====================================================
// เรขาคณิต
// =====================================================

export const rectCenter = (r: Rect): { x: number; y: number } => ({ x: r.x + r.w / 2, y: r.y + r.h / 2 });

/** ขยาย (หรือหด ถ้าติดลบ) รอบด้านตามสัดส่วนขนาดเดิม */
export const expandRect = (r: Rect, fx: number, fy: number = fx): Rect => ({
  x: r.x - r.w * fx,
  y: r.y - r.h * fy,
  w: r.w * (1 + 2 * fx),
  h: r.h * (1 + 2 * fy),
});

/** ตัดให้อยู่ในภาพ + ปัดเป็นจำนวนเต็ม (ใช้ครอปจริง) · ไม่เหลือพื้นที่ = null */
export const clampRect = (r: Rect, size: Size): Rect | null => {
  const x0 = Math.max(0, Math.floor(r.x));
  const y0 = Math.max(0, Math.floor(r.y));
  const x1 = Math.min(Math.floor(size.w), Math.ceil(r.x + r.w));
  const y1 = Math.min(Math.floor(size.h), Math.ceil(r.y + r.h));
  if (x1 - x0 < 2 || y1 - y0 < 2) return null;
  return { x: x0, y: y0, w: x1 - x0, h: y1 - y0 };
};

/** ย้ายพิกัดจากภาพหนึ่งไปอีกภาพที่เป็นส่วนครอป (origin) แล้วย่อ/ขยายด้วย scale */
export const mapRect = (r: Rect, origin: { x: number; y: number }, scale: number): Rect => ({
  x: (r.x - origin.x) * scale,
  y: (r.y - origin.y) * scale,
  w: r.w * scale,
  h: r.h * scale,
});

/**
 * กรอบบนจอ → พิกัดพิกเซลของภาพถ่าย
 * พรีวิวกล้องแสดงภาพแบบเต็มพื้นที่ตัดขอบ (Android PreviewView FILL_CENTER / iOS aspectFill)
 * และพรีวิวใช้สัดส่วนเดียวกับภาพถ่าย (expo-camera ใช้ ResolutionSelector ชุดเดียวกัน)
 */
export const frameInPhoto = (frame: Rect, stage: Size, photo: Size): Rect => {
  const scale = Math.max(stage.w / photo.w, stage.h / photo.h);
  const offX = (stage.w - photo.w * scale) / 2;
  const offY = (stage.h - photo.h * scale) / 2;
  return { x: (frame.x - offX) / scale, y: (frame.y - offY) / scale, w: frame.w / scale, h: frame.h / scale };
};

/**
 * เดาตำแหน่งบัตรจากกรอบใบหน้าบนบัตร (แม่แบบ CARD_LAYOUT)
 * @param ratio ความกว้างใบหน้า/ความกว้างบัตร ที่วัดได้จริงจากภาพก่อนหน้าที่เจอขอบบัตร (ไม่มี = ค่าแม่แบบ)
 */
export const cardFromFace = (face: Rect, ratio: number | null = null): Rect => {
  const r = ratio !== null && ratio >= 0.08 && ratio <= 0.26 ? ratio : CARD_LAYOUT.faceW;
  const w = face.w / r;
  const h = w / CARD_RATIO;
  const c = rectCenter(face);
  return { x: c.x - CARD_LAYOUT.faceCx * w, y: c.y - CARD_LAYOUT.faceCy * h, w, h };
};

/** ส่วนของบัตรตามแม่แบบ (สัดส่วน) → พิกัดเดียวกับบัตร */
const zoneOf = (card: Rect, z: { x0: number; x1: number; y0: number; y1: number }): Rect => ({
  x: card.x + z.x0 * card.w,
  y: card.y + z.y0 * card.h,
  w: (z.x1 - z.x0) * card.w,
  h: (z.y1 - z.y0) * card.h,
});

/** แถบเลขประจำตัว + ชื่อไทย ในพิกัดเดียวกับบัตร */
export const textStripOf = (card: Rect): Rect => zoneOf(card, CARD_LAYOUT.textStrip);

/** ส่วนที่ใช้วัดความคม ในพิกัดเดียวกับบัตร */
export const sharpStripOf = (card: Rect): Rect => zoneOf(card, CARD_LAYOUT.sharpStrip);

/**
 * ขอบบัตรที่เจอสอดคล้องกับรูปหน้าบนบัตรหรือไม่ (กันจับขอบผิด เช่น ขอบโต๊ะ/ขอบรูปติดบัตร)
 * รูปหน้าต้องอยู่มุมขวาล่างของบัตร และขนาดเข้ากับบัตร
 */
export const edgesAgreeWithFace = (card: Rect, face: Rect): boolean => {
  const ratio = card.w / Math.max(1, card.h);
  if (ratio < 1.4 || ratio > 1.8) return false;
  const c = rectCenter(face);
  const u = (c.x - card.x) / card.w;
  const v = (c.y - card.y) / card.h;
  const fw = face.w / card.w;
  return u >= 0.68 && u <= 0.98 && v >= 0.42 && v <= 0.92 && fw >= 0.08 && fw <= 0.26;
};

// =====================================================
// พิกเซล
// =====================================================

/** RGB → ความสว่าง (Rec.601) */
export const toGray = (img: RgbImage): GrayImage => {
  const n = img.width * img.height;
  const out = new Uint8Array(n);
  const d = img.data;
  for (let i = 0, j = 0; i < n; i += 1, j += 3) {
    out[i] = (d[j] * 299 + d[j + 1] * 587 + d[j + 2] * 114 + 500) / 1000;
  }
  return { width: img.width, height: img.height, data: out };
};

const smooth1d = (src: Float64Array, radius: number): Float64Array => {
  const n = src.length;
  const out = new Float64Array(n);
  for (let i = 0; i < n; i += 1) {
    let sum = 0;
    let cnt = 0;
    for (let k = Math.max(0, i - radius); k <= Math.min(n - 1, i + radius); k += 1) {
      sum += src[k];
      cnt += 1;
    }
    out[i] = sum / cnt;
  }
  return out;
};

const median = (values: number[]): number => {
  if (values.length === 0) return 0;
  const s = [...values].sort((a, b) => a - b);
  return s[Math.floor(s.length / 2)];
};

/**
 * หาขั้นบันไดความสว่างที่แรงที่สุดในช่วง [from, to] ของโปรไฟล์ (ค่าเฉลี่ยต่อคอลัมน์/แถว)
 * @returns ตำแหน่ง + ค่าต่าง (มีเครื่องหมาย: บวก = สว่างขึ้นเมื่อไปทางขวา/ลง)
 */
const strongestStep = (d: Float64Array, from: number, to: number): { at: number; step: number } | null => {
  let best: { at: number; step: number } | null = null;
  for (let i = Math.max(0, from); i <= Math.min(d.length - 1, to); i += 1) {
    if (!best || Math.abs(d[i]) > Math.abs(best.step)) best = { at: i, step: d[i] };
  }
  return best;
};

/** ขอบสองด้าน (ซ้าย-ขวา หรือ บน-ล่าง) จากโปรไฟล์ · ขอบต้องเปลี่ยนความสว่างกลับทิศกัน (พื้น→บัตร→พื้น) */
const findPair = (
  profile: Float64Array,
  lowZone: [number, number],
  highZone: [number, number],
  span: number
): { lo: number; hi: number; strength: number } | null => {
  const n = profile.length;
  const p = smooth1d(profile, 1);
  const d = new Float64Array(n);
  for (let i = 0; i < n; i += 1) d[i] = p[Math.min(n - 1, i + span)] - p[Math.max(0, i - span)];
  const lo = strongestStep(d, lowZone[0], lowZone[1]);
  const hi = strongestStep(d, highZone[0], highZone[1]);
  if (!lo || !hi || lo.at >= hi.at) return null;
  // พื้น→บัตร กับ บัตร→พื้น ต้องกลับทิศกัน
  if (lo.step * hi.step >= 0) return null;
  const strength = Math.min(Math.abs(lo.step), Math.abs(hi.step));
  const noise = median(Array.from(d, (v) => Math.abs(v)));
  if (strength < 18 || strength < noise * 3) return null;
  return { lo: lo.at, hi: hi.at, strength };
};

/**
 * หาขอบบัตรจริงในภาพย่อรอบกรอบ — ใช้โปรไฟล์ความสว่างเฉลี่ยต่อคอลัมน์/แถว (ตัวหนังสือบนบัตรไม่ทำให้เกิดขั้นบันได
 * ข้ามทั้งแถบเหมือนขอบบัตรที่ตัดกับพื้นหลัง) · พื้นหลังกลืนกับบัตร = ไม่เจอ (ผู้เรียกใช้ตำแหน่งจากรูปหน้าแทน)
 * @param gray ภาพย่อ (พิกัดเดียวกับ guess)
 * @param guess ตำแหน่งที่คาดว่าบัตรอยู่ (กรอบบนจอ)
 */
export const findCardRect = (gray: GrayImage, guess: Rect): Rect | null => {
  const { width: W, height: H, data } = gray;
  if (W < 16 || H < 16 || guess.w < 8 || guess.h < 8) return null;

  // คอลัมน์: เฉลี่ยเฉพาะแถวกลางบัตร (กันขอบบน-ล่าง) · แถว: เฉลี่ยเฉพาะคอลัมน์กลางบัตร
  const rowA = Math.max(0, Math.round(guess.y + guess.h * 0.22));
  const rowB = Math.min(H - 1, Math.round(guess.y + guess.h * 0.78));
  const colA = Math.max(0, Math.round(guess.x + guess.w * 0.22));
  const colB = Math.min(W - 1, Math.round(guess.x + guess.w * 0.78));
  if (rowB - rowA < 4 || colB - colA < 4) return null;

  const colProfile = new Float64Array(W);
  for (let y = rowA; y <= rowB; y += 1) {
    const base = y * W;
    for (let x = 0; x < W; x += 1) colProfile[x] += data[base + x];
  }
  for (let x = 0; x < W; x += 1) colProfile[x] /= rowB - rowA + 1;

  const rowProfile = new Float64Array(H);
  for (let y = 0; y < H; y += 1) {
    const base = y * W;
    let sum = 0;
    for (let x = colA; x <= colB; x += 1) sum += data[base + x];
    rowProfile[y] = sum / (colB - colA + 1);
  }

  const spanX = Math.max(2, Math.round(W * 0.008));
  const spanY = Math.max(2, Math.round(H * 0.008));
  const lr = findPair(
    colProfile,
    [0, Math.round(guess.x + guess.w * 0.35)],
    [Math.round(guess.x + guess.w * 0.65), W - 1],
    spanX
  );
  const tb = findPair(
    rowProfile,
    [0, Math.round(guess.y + guess.h * 0.35)],
    [Math.round(guess.y + guess.h * 0.65), H - 1],
    spanY
  );
  if (!lr || !tb) return null;
  return { x: lr.lo, y: tb.lo, w: lr.hi - lr.lo, h: tb.hi - tb.lo };
};

export interface RegionStats {
  /** ความสว่างเฉลี่ย 0–255 */
  mean: number;
  p5: number;
  p95: number;
  /** สัดส่วนพิกเซลขาวจ้า (ความสว่าง ≥ 250) */
  clipped: number;
  /** สัดส่วนแสงสะท้อน: ขาวจ้า (สีสว่างสุด ≥ 245) สีซีด (ต่างสี ≤ 45) และเพื่อนบ้าน 4 ทิศเป็นแสงสะท้อนด้วย (ไม่นับจุดเดี่ยว) */
  glare: number;
}

const isGlarePx = (d: Uint8Array, j: number): boolean => {
  const r = d[j];
  const g = d[j + 1];
  const b = d[j + 2];
  const mx = r > g ? (r > b ? r : b) : g > b ? g : b;
  if (mx < 245) return false;
  const mn = r < g ? (r < b ? r : b) : g < b ? g : b;
  return mx - mn <= 45;
};

/** สถิติแสงในพื้นที่ roi ของภาพ (ตัดให้อยู่ในภาพเอง) · ไม่มีพื้นที่ = null */
export const regionStats = (img: RgbImage, roi: Rect): RegionStats | null => {
  const r = clampRect(roi, { w: img.width, h: img.height });
  if (!r) return null;
  const W = img.width;
  const H = img.height;
  const d = img.data;
  const hist = new Uint32Array(256);
  let sum = 0;
  let clipped = 0;
  let glare = 0;
  for (let y = r.y; y < r.y + r.h; y += 1) {
    for (let x = r.x; x < r.x + r.w; x += 1) {
      const j = (y * W + x) * 3;
      const l = (d[j] * 299 + d[j + 1] * 587 + d[j + 2] * 114 + 500) / 1000;
      const li = l | 0;
      hist[li] += 1;
      sum += l;
      if (li >= 250) clipped += 1;
      if (
        isGlarePx(d, j) &&
        x > 0 &&
        x < W - 1 &&
        y > 0 &&
        y < H - 1 &&
        isGlarePx(d, j - 3) &&
        isGlarePx(d, j + 3) &&
        isGlarePx(d, j - W * 3) &&
        isGlarePx(d, j + W * 3)
      ) {
        glare += 1;
      }
    }
  }
  const n = r.w * r.h;
  const pct = (q: number): number => {
    const target = q * n;
    let acc = 0;
    for (let i = 0; i < 256; i += 1) {
      acc += hist[i];
      if (acc >= target) return i;
    }
    return 255;
  };
  return { mean: sum / n, p5: pct(0.05), p95: pct(0.95), clipped: clipped / n, glare: glare / n };
};

/**
 * ค่าความเบลอแบบไม่ต้องมีภาพอ้างอิง (Crété-Roffet et al. 2007 — สูตรเดียวกับ skimage.measure.blur_effect, h_size 11)
 * ทำให้ภาพเบลอซ้ำแล้วดูว่าความต่างระหว่างพิกเซลข้างเคียงหายไปเท่าไร: ภาพที่เบลออยู่แล้วแทบไม่เปลี่ยน
 * ไม่ขึ้นกับความเข้มของคอนทราสต์มากเท่า Laplacian variance
 * @returns 0 (คม) – 1 (เบลอ) · ค่าที่มากกว่าระหว่างแนวตั้งกับแนวนอน
 */
export const blurEffect = (gray: GrayImage, hSize: number = 11): number => {
  const { width: W, height: H, data } = gray;
  if (W < 8 || H < 8) return 1;
  // ขนาดหน้าต่างคี่เท่านั้น (11 ตาม skimage)
  const size = hSize % 2 === 1 ? hSize : hSize + 1;
  const half = (size - 1) / 2;
  const n = W * H;

  // uniform_filter1d (mode 'reflect' แบบ scipy: d c b a | a b c d | d c b a) ด้วยผลรวมสะสม — O(N)
  // axis 0 = ตามแนวตั้ง (stride W) · axis 1 = ตามแนวนอน (stride 1)
  const uniform = (axis: 0 | 1): Float64Array => {
    const out = new Float64Array(n);
    const len = axis === 0 ? H : W;
    const lines = axis === 0 ? W : H;
    const step = axis === 0 ? W : 1;
    const lineStep = axis === 0 ? 1 : W;
    const at = (i: number): number => (i < 0 ? -i - 1 : i >= len ? 2 * len - i - 1 : i);
    for (let l = 0; l < lines; l += 1) {
      const base = l * lineStep;
      let s = 0;
      for (let k = -half; k <= half; k += 1) s += data[base + at(k) * step];
      out[base] = s / size;
      for (let i = 1; i < len; i += 1) {
        s += data[base + at(i + half) * step] - data[base + at(i - half - 1) * step];
        out[base + i * step] = s / size;
      }
    }
    return out;
  };

  let worst = 0;
  for (const axis of [0, 1] as const) {
    const filt = uniform(axis);
    let m1 = 0;
    let m2 = 0;
    // รวมเฉพาะ slice(2, s − 1) ตาม skimage — เพื่อนบ้าน ±1 อยู่ในภาพเสมอ ไม่ต้อง reflect
    // |sobel| ตามแกน: อนุพันธ์ [1,0,-1] ตามแกน × ปรับเรียบ [1,2,1] ตั้งฉาก (ค่าคงที่ไม่มีผลกับอัตราส่วน)
    for (let y = 2; y < H - 1; y += 1) {
      const up = (y - 1) * W;
      const mid = y * W;
      const dn = (y + 1) * W;
      for (let x = 2; x < W - 1; x += 1) {
        let a: number;
        let b: number;
        if (axis === 0) {
          a = data[dn + x - 1] + 2 * data[dn + x] + data[dn + x + 1] - data[up + x - 1] - 2 * data[up + x] - data[up + x + 1];
          b = filt[dn + x - 1] + 2 * filt[dn + x] + filt[dn + x + 1] - filt[up + x - 1] - 2 * filt[up + x] - filt[up + x + 1];
        } else {
          a = data[up + x + 1] + 2 * data[mid + x + 1] + data[dn + x + 1] - data[up + x - 1] - 2 * data[mid + x - 1] - data[dn + x - 1];
          b = filt[up + x + 1] + 2 * filt[mid + x + 1] + filt[dn + x + 1] - filt[up + x - 1] - 2 * filt[mid + x - 1] - filt[dn + x - 1];
        }
        const sa = a < 0 ? -a : a;
        const sb = b < 0 ? -b : b;
        m1 += sa;
        if (sa > sb) m2 += sa - sb;
      }
    }
    const r = m1 > 0 ? Math.abs(m1 - m2) / m1 : 1;
    if (r > worst) worst = r;
  }
  return worst;
};

// =====================================================
// ตัดสิน
// =====================================================

export interface CardFaceInput {
  /** กรอบใบหน้าบนบัตร (พิกเซลภาพถ่าย) */
  box: Rect;
  /** มุมเอียงของใบหน้า (องศา, ML Kit headEulerAngleZ) */
  roll: number | null;
  /** ความกว้างใบหน้า/ความกว้างบัตร ที่เรียนรู้จากภาพก่อนหน้า (ใช้ตอนไม่เจอขอบบัตร) */
  ratio?: number | null;
}

export interface GeometryResult {
  hint: CardHint | null;
  /** ตำแหน่งบัตรที่ใช้ (พิกเซลภาพถ่าย) */
  card: Rect | null;
  source: 'edges' | 'face' | null;
}

/**
 * ตำแหน่ง/ระยะของบัตรเทียบกรอบ · hint null = เต็มกรอบพอดี
 * @param frame กรอบบนจอในพิกัดภาพถ่าย (frameInPhoto)
 * @param edges ขอบบัตรจริง (พิกัดภาพถ่าย) — null = ไม่เจอ/ยังไม่ได้หา
 */
export const judgeGeometry = (
  frame: Rect,
  face: CardFaceInput | null,
  edges: Rect | null,
  cfg: CardQualityConfig = CARD_QUALITY_CONFIG
): GeometryResult => {
  if (!face) return { hint: 'NO_CARD', card: null, source: null };
  const useEdges = !!edges && edgesAgreeWithFace(edges, face.box);
  const card = useEdges && edges ? edges : cardFromFace(face.box, face.ratio ?? null);
  const source = useEdges ? 'edges' : 'face';
  if (face.roll !== null && Math.abs(face.roll) > cfg.maxRoll) return { hint: 'TILTED', card, source };

  const fill = card.w / frame.w;
  const minFill = useEdges ? cfg.edgeMinFill : cfg.faceMinFill;
  const maxFill = useEdges ? cfg.edgeMaxFill : cfg.faceMaxFill;
  if (fill < minFill) return { hint: 'TOO_FAR', card, source };
  if (fill > maxFill) return { hint: 'TOO_CLOSE', card, source };

  const c = rectCenter(card);
  const f = rectCenter(frame);
  const dx = Math.abs(c.x - f.x) / frame.w;
  const dy = Math.abs(c.y - f.y) / frame.h;
  if (dx > (useEdges ? cfg.edgeMaxOffsetX : cfg.faceMaxOffsetX) || dy > (useEdges ? cfg.edgeMaxOffsetY : cfg.faceMaxOffsetY)) {
    return { hint: 'OFF_CENTER', card, source };
  }
  return { hint: null, card, source };
};

/**
 * เช็คเร็วจากรูปหน้าอย่างเดียว ก่อนเสียเวลาอ่านพิกเซล — คืน hint เมื่อห่างจากกรอบมากจนไม่ต้องดูต่อ
 */
export const quickGeometryHint = (
  frame: Rect,
  face: CardFaceInput | null,
  cfg: CardQualityConfig = CARD_QUALITY_CONFIG
): CardHint | null => {
  if (!face) return 'NO_CARD';
  if (face.roll !== null && Math.abs(face.roll) > cfg.maxRoll + 6) return 'TILTED';
  const fill = cardFromFace(face.box, face.ratio ?? null).w / frame.w;
  if (fill < cfg.quickMinFill) return 'TOO_FAR';
  if (fill > cfg.quickMaxFill) return 'TOO_CLOSE';
  return null;
};

export interface LightInput {
  card: RegionStats | null;
  face: RegionStats | null;
  text: RegionStats | null;
}

/** แสงบนบัตร · null = ใช้ได้ */
export const judgeLight = (light: LightInput, cfg: CardQualityConfig = CARD_QUALITY_CONFIG): CardHint | null => {
  const { card, face, text } = light;
  if (card && card.mean < cfg.minCardMean) return 'TOO_DARK';
  if (card && card.clipped > cfg.maxCardClipped) return 'TOO_BRIGHT';
  if (face && (face.glare > cfg.maxFaceGlare || (face.p95 - face.p5 < cfg.minFaceRange && face.mean > cfg.faceHazeMean))) {
    return 'GLARE_FACE';
  }
  if ((text && text.glare > cfg.maxTextGlare) || (card && card.glare > cfg.maxCardGlare)) return 'GLARE';
  return null;
};

export interface CardVerdict {
  hint: CardHint;
  /** บัตรเต็มกรอบ ตรงตำแหน่ง */
  fit: boolean;
  /** แสงพอดี ไม่มีแสงสะท้อน · null = ยังไม่ได้ตรวจ */
  light: boolean | null;
  /** คมพอให้ OCR · null = ยังไม่ได้ตรวจ */
  sharp: boolean | null;
  card: Rect | null;
  source: 'edges' | 'face' | null;
  /** ค่าดิบ (ไว้ดูตอนปรับเกณฑ์ — ห้ามส่งออกนอกเครื่อง) */
  blur: number | null;
  stats: LightInput | null;
  /** ความกว้างใบหน้า/ความกว้างบัตร เมื่อภาพนี้เจอขอบบัตรจริง — ส่งกลับมาใน CardShotInput.faceRatio ภาพถัดไป */
  faceRatio: number | null;
}

/** รวมผลตามลำดับความสำคัญ: ตำแหน่ง → แสง → ความคม */
export const judgeCard = (
  geometry: GeometryResult,
  light: LightInput | null,
  blur: number | null,
  cfg: CardQualityConfig = CARD_QUALITY_CONFIG,
  faceRatio: number | null = null
): CardVerdict => {
  const fit = geometry.hint === null;
  const lightHint = light ? judgeLight(light, cfg) : null;
  const lightOk = light ? lightHint === null : null;
  const sharp = blur === null ? null : blur <= cfg.maxBlur;
  const base = { fit, light: lightOk, sharp, card: geometry.card, source: geometry.source, blur, stats: light, faceRatio };
  if (geometry.hint) return { hint: geometry.hint, ...base };
  if (lightHint) return { hint: lightHint, ...base };
  if (sharp === false) return { hint: 'BLURRY', ...base };
  return { hint: 'GOOD', ...base };
};

/** ภาพนี้ใช้ส่งได้เลย (ตรวจครบทุกด้านและผ่าน) */
export const isCardShotGood = (v: CardVerdict): boolean =>
  v.hint === 'GOOD' && v.fit && v.light === true && v.sharp === true;

// =====================================================
// ตรวจ 1 ภาพ (ผู้เรียกป้อนตัวครอป — บนเครื่องใช้ expo-image-manipulator, ในเทสต์ใช้ sharp)
// =====================================================

/** ครอปส่วน rect (พิกเซลภาพถ่าย, จำนวนเต็มอยู่ในภาพแล้ว) แล้วย่อให้กว้าง outWidth → พิกเซล RGB · ทำไม่ได้ = null */
export type CardCropFn = (rect: Rect, outWidth: number) => Promise<RgbImage | null>;

export interface CardShotInput {
  /** ขนาดภาพถ่าย (px) */
  width: number;
  height: number;
  /** ผลตรวจใบหน้าของภาพนี้ (null = ตรวจไม่ได้) */
  faces: MlkitFaceLike[] | null;
  /** กรอบบนจอ + ขนาดพื้นที่กล้อง (dp) */
  frame: Rect;
  stage: Size;
  /** CardVerdict.faceRatio ล่าสุดที่ได้ (ไม่มี = ใช้แม่แบบ) */
  faceRatio?: number | null;
}

/** ความกว้างภาพย่อที่ใช้หาขอบ/วัดแสง (px) — ถอดรหัส JPEG ด้วย JS บน Hermes ช้า ใช้เท่าที่จำเป็น */
const SEARCH_WIDTH = 320;

/** รูปหน้าบนบัตร (ขนาดเล็ก ไม่ใช่หน้าคนจริงที่ใหญ่) → พิกัดพิกเซล · ไม่มี = null */
export const cardFaceOf = (faces: MlkitFaceLike[] | null | undefined, photo: Size): CardFaceInput | null => {
  const s = sampleFromMlkit(faces, photo.w, photo.h);
  if (!s.box || !looksLikeCardPortrait(s)) return null;
  return { box: { x: s.box.x * photo.w, y: s.box.y * photo.h, w: s.box.w * photo.w, h: s.box.h * photo.h }, roll: s.roll };
};

/**
 * ตรวจภาพบัตร 1 ภาพ: ตำแหน่ง → แสง → ความคม (หยุดที่ด่านแรกที่ไม่ผ่าน ไม่ครอปเกินจำเป็น)
 * @param crop null = เครื่องนี้อ่านพิกเซลไม่ได้ → ตัดสินจากรูปหน้าอย่างเดียว (light/sharp = null)
 */
export const analyzeCardShot = async (
  input: CardShotInput,
  crop: CardCropFn | null,
  cfg: CardQualityConfig = CARD_QUALITY_CONFIG
): Promise<CardVerdict> => {
  const photo = { w: input.width, h: input.height };
  if (photo.w <= 0 || photo.h <= 0 || input.stage.w <= 0 || input.stage.h <= 0) {
    return judgeCard({ hint: 'NO_CARD', card: null, source: null }, null, null, cfg);
  }
  const frame = frameInPhoto(input.frame, input.stage, photo);
  const found = cardFaceOf(input.faces, photo);
  const face = found ? { ...found, ratio: input.faceRatio ?? null } : null;
  const quick = quickGeometryHint(frame, face, cfg);
  if (quick) {
    return judgeCard({ hint: quick, card: face ? cardFromFace(face.box, face.ratio ?? null) : null, source: face ? 'face' : null }, null, null, cfg);
  }
  if (!crop || !face) return judgeCard(judgeGeometry(frame, face, null, cfg), null, null, cfg);

  // ภาพย่อรอบกรอบ (เผื่อบน-ล่างมากกว่า เพราะกรอบแนวนอน) → หาขอบบัตรจริง + วัดแสง
  const search = clampRect(expandRect(frame, 0.12, 0.3), photo);
  const thumb = search ? await crop(search, Math.min(SEARCH_WIDTH, search.w)) : null;
  if (!search || !thumb) return judgeCard(judgeGeometry(frame, face, null, cfg), null, null, cfg);
  const s = thumb.width / search.w;
  const edgesInThumb = findCardRect(toGray(thumb), mapRect(frame, search, s));
  const edges = edgesInThumb
    ? { x: edgesInThumb.x / s + search.x, y: edgesInThumb.y / s + search.y, w: edgesInThumb.w / s, h: edgesInThumb.h / s }
    : null;
  const geometry = judgeGeometry(frame, face, edges, cfg);
  const ratio = geometry.source === 'edges' && geometry.card ? face.box.w / geometry.card.w : null;
  if (geometry.hint || !geometry.card) return judgeCard(geometry, null, null, cfg, ratio);

  const light: LightInput = {
    card: regionStats(thumb, expandRect(mapRect(geometry.card, search, s), -0.04)),
    face: regionStats(thumb, expandRect(mapRect(face.box, search, s), 0.05)),
    text: regionStats(thumb, mapRect(textStripOf(geometry.card), search, s)),
  };
  if (judgeLight(light, cfg)) return judgeCard(geometry, light, null, cfg, ratio);

  // ความคม: แถบตัวหนังสือที่สเกลเดียวกับ server (ย่อเท่านั้น ไม่ขยาย)
  const strip = clampRect(sharpStripOf(geometry.card), photo);
  const k = Math.min(1, SERVER_CARD_WIDTH / geometry.card.w);
  const stripImg = strip ? await crop(strip, Math.max(8, Math.round(strip.w * k))) : null;
  return judgeCard(geometry, light, stripImg ? blurEffect(toGray(stripImg)) : null, cfg, ratio);
};

// =====================================================
// base64 → ไบต์ (ผลครอปจาก expo-image-manipulator)
// =====================================================

const B64_LOOKUP = (() => {
  const table = new Uint8Array(128).fill(255);
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
  for (let i = 0; i < alphabet.length; i += 1) table[alphabet.charCodeAt(i)] = i;
  // base64url
  table[45] = 62;
  table[95] = 63;
  return table;
})();

/** ถอด base64 (ข้ามขึ้นบรรทัด/ช่องว่าง/=) — ไม่พึ่ง atob/Buffer */
export const base64ToBytes = (b64: string): Uint8Array => {
  const out = new Uint8Array(Math.floor((b64.length * 3) / 4));
  let acc = 0;
  let bits = 0;
  let n = 0;
  for (let i = 0; i < b64.length; i += 1) {
    const c = b64.charCodeAt(i);
    const v = c < 128 ? B64_LOOKUP[c] : 255;
    if (v === 255) continue;
    acc = (acc << 6) | v;
    bits += 6;
    if (bits >= 8) {
      bits -= 8;
      out[n] = (acc >> bits) & 0xff;
      n += 1;
    }
  }
  return out.subarray(0, n);
};

// =====================================================
// ขนาดภาพถ่าย
// =====================================================

/**
 * เลือกขนาดภาพถ่ายบัตร: ด้านสั้น (= ความกว้างบัตรในแนวตั้ง) ต้องพอให้บัตรกว้าง ≥ 1000 px หลังเต็มกรอบ
 * เลือกสัดส่วน 4:3 (Android จับคู่พรีวิว 4:3 เป็นค่าเริ่มต้น) ด้านยาวใกล้ target ไม่เกิน maxLongEdge (ไฟล์ ≤ 8 MB, ถ่ายไม่ช้าเกิน)
 * @returns ขนาด "WxH" · ไม่มีขนาดที่ใช้ได้ = undefined (ผู้เรียกใช้ตัวเลือกเดิม)
 */
export const pickCardPictureSize = (
  sizes: string[] | null | undefined,
  target: number = 2600,
  minLongEdge: number = 1900,
  maxLongEdge: number = 3300
): string | undefined => {
  const parsed = (sizes || [])
    .map((s) => {
      const m = /^(\d+)x(\d+)$/.exec(String(s).trim());
      if (!m) return null;
      const a = Number(m[1]);
      const b = Number(m[2]);
      const long = Math.max(a, b);
      const short = Math.min(a, b);
      return short > 0 ? { raw: `${m[1]}x${m[2]}`, long, ratio: long / short } : null;
    })
    .filter((s): s is { raw: string; long: number; ratio: number } => s !== null)
    .filter((s) => s.long >= minLongEdge && s.long <= maxLongEdge && Math.abs(s.ratio - 4 / 3) < 0.03);
  if (parsed.length === 0) return undefined;
  return parsed.sort((a, b) => Math.abs(a.long - target) - Math.abs(b.long - target) || b.long - a.long)[0].raw;
};
