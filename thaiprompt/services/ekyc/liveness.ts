/**
 * ตัวตัดสินท่าทางยืนยันใบหน้า (liveness) บนเครื่อง — ฟังก์ชันล้วน ไม่แตะกล้อง/native (ทดสอบได้ใน jest)
 *
 * ขั้นตอน: เฟรมแรก "มองตรง" (neutral = ค่าอ้างอิง) → ทำท่าตามคำสั่งที่ server สุ่มให้ทีละข้อ
 * แต่ละเฟรมจากกล้อง (ภาพนิ่งที่ถ่ายถี่ๆ) ถูกแปลงเป็น FaceSample จากผล ML Kit แล้วส่งเข้า advance()
 *   - ผ่าน → เก็บเฟรมนั้นไว้ส่ง server พร้อมป้าย (เฟรมที่ตรวจ = เฟรมที่ส่ง ไม่ถ่ายซ้ำ)
 *   - ไม่ผ่าน → คืนคำใบ้ (hint) ให้หน้าจอบอกผู้ใช้
 * server ตรวจซ้ำทุกเฟรมเสมอ (ตัวนี้แค่ช่วยให้ถ่ายถูกจังหวะ ไม่ใช่ตัวตัดสินสุดท้าย)
 *
 * ทิศทางหันหน้า (สำคัญ)
 *   - เฟรมกล้องหน้าเป็นภาพกระจก (expo-camera mirror) → ผู้ใช้หัน "ซ้ายของตัวเอง" = จมูกเคลื่อนไปทาง "ซ้ายของภาพ"
 *   - ตัดสินทิศจากตำแหน่งจมูกเทียบกึ่งกลางตา (เรขาคณิต ไม่ขึ้นกับเครื่องหมายมุมของแต่ละแพลตฟอร์ม)
 *     ไม่มีจุด landmark → ใช้มุม yaw ของ ML Kit (บวก = ใบหน้าหันไปทางขวาของภาพ)
 *
 * เพดานท่าทาง (ตรงกับเกณฑ์ server — ไม่รับเฟรมที่ server จะตีกลับ)
 *   - server ต้องเห็นใบหน้าชัด (คะแนนตรวจหน้า ≥ 0.80) และยังคล้ายเฟรมมองตรง (cosine ≥ 0.35)
 *     → หันหน้าไม่เกิน ~40° · ก้ม/เงยไม่เกิน ~30° (มากกว่านั้นบอกให้ขยับกลับมา)
 *   - ยิ้ม: server ดู "เพิ่มขึ้นจากเฟรมมองตรง" (ปากกว้างขึ้น +10% หรือคะแนนยิ้ม +0.25)
 *     → ต้องยิ้มเพิ่มจากตอนมองตรง ไม่ใช่แค่ยิ้มเกินค่าคงที่ (คนที่ยิ้มอยู่แล้วตอนมองตรงต้องยิ้มกว้างขึ้นจริง)
 *   - กระพริบตา: ภาพนิ่งจับจังหวะตาปิดยาก → รับเฟรมที่ตาปิดสนิท หรือตาหรี่ลงชัดเจนเมื่อเทียบกับตอนมองตรง
 */

import type { EkycChallenge, EkycFaceLabel } from '@/services/api/ekycApi';

// =====================================================
// ชนิดข้อมูล
// =====================================================

export interface FaceBox {
  /** มุมซ้ายบน + ขนาด เป็นสัดส่วน 0–1 ของภาพ */
  x: number;
  y: number;
  w: number;
  h: number;
}

export interface FaceSample {
  faceCount: number;
  box: FaceBox | null;
  /** มุมหันซ้าย-ขวา (องศา, ML Kit headEulerAngleY) */
  yaw: number | null;
  /** มุมก้ม-เงย (องศา, ML Kit headEulerAngleX — บวก = เงย) */
  pitch: number | null;
  /** มุมเอียง (องศา) */
  roll: number | null;
  leftEyeOpen: number | null;
  rightEyeOpen: number | null;
  smile: number | null;
  /** (จมูก.x − กึ่งกลางตา.x) / ระยะห่างระหว่างตา — ลบ = จมูกไปทางซ้ายของภาพ */
  noseOffset: number | null;
  /** ความกว้างปาก (มุมปากซ้าย-ขวา) / ระยะห่างระหว่างตา — ใช้เทียบ "ยิ้มกว้างขึ้น" กับตอนมองตรง */
  mouthWidth: number | null;
}

/** คำใบ้ให้หน้าจอแปลงเป็นข้อความ */
export type LivenessHint =
  | 'NO_FACE'
  | 'MULTIPLE_FACES'
  | 'MOVE_CLOSER'
  | 'MOVE_BACK'
  | 'CENTER_FACE'
  | 'LOOK_STRAIGHT'
  | 'OPEN_EYES'
  | 'DO_ACTION'
  | 'MORE'
  | 'WRONG_WAY'
  /** หันหน้ามากเกิน (server มองหน้าไม่ชัด) */
  | 'TURN_LESS'
  /** ก้ม/เงยมากเกิน */
  | 'NOD_LESS'
  | 'GOOD';

export const LIVENESS_CONFIG = {
  /** เฟรมกล้องหน้าเป็นภาพกระจก */
  mirrored: true,
  /** ความกว้างใบหน้าขั้นต่ำ/สูงสุด (สัดส่วนของความกว้างภาพ) */
  minFaceWidth: 0.2,
  maxFaceWidth: 0.88,
  /** กึ่งกลางใบหน้าห่างจากกึ่งกลางภาพได้ไม่เกิน */
  maxCenterOffset: 0.24,
  /** มองตรง: |yaw| และ |pitch| ไม่เกิน (องศา) */
  neutralMaxYaw: 12,
  neutralMaxPitch: 15,
  neutralMaxNoseOffset: 0.14,
  /** ลืมตา (ความน่าจะเป็น) */
  eyesOpenMin: 0.5,
  /** หลับตา (กระพริบ) — ต้องปิดทั้งสองข้าง (ขยิบตาข้างเดียวไม่นับ) */
  blinkMaxEyeOpen: 0.35,
  /** หลับตาแบบเทียบตอนมองตรง: ตาทั้งสองข้างไม่เกินค่านี้ … */
  blinkRelaxedMaxEyeOpen: 0.5,
  /** … และเหลือไม่เกินสัดส่วนนี้ของตอนมองตรง (ตาหรี่ลงชัดเจน) */
  blinkMaxRatio: 0.45,
  /** ยิ้ม: คะแนนยิ้มขั้นต่ำ */
  smileMin: 0.7,
  /** ยิ้ม: คะแนนยิ้มต้องเพิ่มจากตอนมองตรงอย่างน้อย (server: +0.25) */
  smileMinGain: 0.25,
  /** ยิ้ม: หรือปากกว้างขึ้นจากตอนมองตรงอย่างน้อย (สัดส่วน · server: +10% เผื่อค่าคลาดเคลื่อนเป็น 12%) */
  smileMinWidthGain: 0.12,
  /** ท่าที่ไม่ใช่หันหน้า ต้องไม่หันเกิน (กันภาพหน้าข้าง) */
  frontalMaxYaw: 25,
  /** หันหน้า: มุมเปลี่ยนจากตอนมองตรงอย่างน้อย (องศา) */
  turnMinYawDelta: 18,
  /** หันหน้า: จมูกเลื่อนจากกึ่งกลางตาอย่างน้อย (สัดส่วนระยะตา) */
  turnMinNoseOffset: 0.2,
  /** หันหน้า: มุมไม่เกิน (องศา) — เกินนี้ server มองหน้าไม่ชัด/ไม่คล้ายเฟรมมองตรง */
  turnMaxYaw: 40,
  /** หันหน้า: จมูกเลื่อนไม่เกิน (ใช้เมื่อไม่มีมุม yaw · ราว 40°) */
  turnMaxNoseOffset: 0.6,
  /** พยักหน้า: มุมก้ม/เงยเปลี่ยนจากตอนมองตรงอย่างน้อย (องศา) */
  nodMinPitchDelta: 12,
  /** พยักหน้า: มุมก้ม/เงยไม่เกิน (องศา) */
  nodMaxPitch: 30,
} as const;

export type LivenessConfig = { mirrored: boolean } & Record<Exclude<keyof typeof LIVENESS_CONFIG, 'mirrored'>, number>;

export interface LivenessState {
  challenges: EkycChallenge[];
  /** 0 = มองตรง · 1..n = challenges[step-1] */
  step: number;
  /** ค่าอ้างอิงตอนมองตรง */
  baseline: FaceSample | null;
  /** ป้ายของเฟรมที่เก็บแล้ว (ตามลำดับ) */
  captured: EkycFaceLabel[];
  done: boolean;
}

export interface AdvanceResult {
  state: LivenessState;
  /** เฟรมนี้ผ่าน → เก็บไว้ส่ง server ด้วยป้าย label */
  accept: boolean;
  label: EkycFaceLabel | null;
  hint: LivenessHint;
}

// =====================================================
// แปลงผล ML Kit → FaceSample
// =====================================================

/** ผลใบหน้า 1 หน้าจาก RNMLKitFaceDetection (เฉพาะช่องที่ใช้) */
export interface MlkitFaceLike {
  frame?: { origin?: { x?: number; y?: number }; size?: { x?: number; y?: number } } | null;
  landmarks?: Array<{ type?: string | null; position?: { x?: number; y?: number } | null }> | null;
  headEulerAngleX?: number | null;
  headEulerAngleY?: number | null;
  headEulerAngleZ?: number | null;
  smilingProbability?: number | null;
  leftEyeOpenProbability?: number | null;
  rightEyeOpenProbability?: number | null;
}

/**
 * ชนิดจุด landmark — Android ส่งเป็นเลข (FaceLandmark.LEFT_EYE = 4 …) iOS ส่งเป็นชื่อ
 */
type LandmarkKind = 'leftEye' | 'rightEye' | 'nose' | 'mouthLeft' | 'mouthRight';

const LANDMARK_ALIASES: Record<string, LandmarkKind> = {
  '4': 'leftEye',
  leftEye: 'leftEye',
  '10': 'rightEye',
  rightEye: 'rightEye',
  '6': 'nose',
  noseBase: 'nose',
  // FaceLandmark.MOUTH_LEFT = 5 · MOUTH_RIGHT = 11
  '5': 'mouthLeft',
  mouthLeft: 'mouthLeft',
  '11': 'mouthRight',
  mouthRight: 'mouthRight',
};

const finite = (v: unknown): number | null => (typeof v === 'number' && Number.isFinite(v) ? v : null);
const prob = (v: unknown): number | null => {
  const n = finite(v);
  return n === null || n < 0 ? null : Math.min(1, n);
};

const faceArea = (f: MlkitFaceLike): number => (finite(f.frame?.size?.x) ?? 0) * (finite(f.frame?.size?.y) ?? 0);

/**
 * เลือกใบหน้าใหญ่สุดแล้วแปลงเป็น FaceSample
 * @param faces ผลจาก detectFaces
 * @param imageWidth ความกว้างภาพ (px) — ไม่รู้ = 0 (กรอบหน้าจะเป็น null)
 */
export const sampleFromMlkit = (
  faces: MlkitFaceLike[] | null | undefined,
  imageWidth: number,
  imageHeight: number
): FaceSample => {
  const list = Array.isArray(faces) ? faces : [];
  if (list.length === 0) {
    return {
      faceCount: 0,
      box: null,
      yaw: null,
      pitch: null,
      roll: null,
      leftEyeOpen: null,
      rightEyeOpen: null,
      smile: null,
      noseOffset: null,
      mouthWidth: null,
    };
  }
  const face = [...list].sort((a, b) => faceArea(b) - faceArea(a))[0];

  let box: FaceBox | null = null;
  const fx = finite(face.frame?.origin?.x);
  const fy = finite(face.frame?.origin?.y);
  const fw = finite(face.frame?.size?.x);
  const fh = finite(face.frame?.size?.y);
  if (imageWidth > 0 && imageHeight > 0 && fx !== null && fy !== null && fw !== null && fh !== null && fw > 0 && fh > 0) {
    box = { x: fx / imageWidth, y: fy / imageHeight, w: fw / imageWidth, h: fh / imageHeight };
  }

  const points: Partial<Record<LandmarkKind, { x: number; y: number }>> = {};
  for (const lm of face.landmarks || []) {
    const kind = LANDMARK_ALIASES[String(lm?.type ?? '')];
    const x = finite(lm?.position?.x);
    const y = finite(lm?.position?.y);
    if (kind && x !== null && y !== null) points[kind] = { x, y };
  }
  let noseOffset: number | null = null;
  let mouthWidth: number | null = null;
  if (points.leftEye && points.rightEye) {
    const eyeDist = Math.hypot(points.leftEye.x - points.rightEye.x, points.leftEye.y - points.rightEye.y);
    if (eyeDist > 1) {
      if (points.nose) {
        const midX = (points.leftEye.x + points.rightEye.x) / 2;
        noseOffset = (points.nose.x - midX) / eyeDist;
      }
      if (points.mouthLeft && points.mouthRight) {
        mouthWidth = Math.hypot(points.mouthLeft.x - points.mouthRight.x, points.mouthLeft.y - points.mouthRight.y) / eyeDist;
      }
    }
  }

  return {
    faceCount: list.length,
    box,
    yaw: finite(face.headEulerAngleY),
    pitch: finite(face.headEulerAngleX),
    roll: finite(face.headEulerAngleZ),
    leftEyeOpen: prob(face.leftEyeOpenProbability),
    rightEyeOpen: prob(face.rightEyeOpenProbability),
    smile: prob(face.smilingProbability),
    noseOffset,
    mouthWidth,
  };
};

// =====================================================
// ตรวจทีละเงื่อนไข
// =====================================================

/** ตรวจตำแหน่ง/ขนาดใบหน้าในกรอบ · null = ใช้ได้ */
export const framingHint = (s: FaceSample, cfg: LivenessConfig = LIVENESS_CONFIG): LivenessHint | null => {
  if (s.faceCount <= 0) return 'NO_FACE';
  if (s.faceCount > 1) return 'MULTIPLE_FACES';
  if (!s.box) return null;
  if (s.box.w < cfg.minFaceWidth) return 'MOVE_CLOSER';
  if (s.box.w > cfg.maxFaceWidth) return 'MOVE_BACK';
  const cx = s.box.x + s.box.w / 2;
  const cy = s.box.y + s.box.h / 2;
  if (Math.abs(cx - 0.5) > cfg.maxCenterOffset || Math.abs(cy - 0.5) > cfg.maxCenterOffset + 0.06) return 'CENTER_FACE';
  return null;
};

const minEye = (s: FaceSample): number | null =>
  s.leftEyeOpen === null || s.rightEyeOpen === null ? null : Math.min(s.leftEyeOpen, s.rightEyeOpen);
const maxEye = (s: FaceSample): number | null =>
  s.leftEyeOpen === null || s.rightEyeOpen === null ? null : Math.max(s.leftEyeOpen, s.rightEyeOpen);

/**
 * ทิศที่ใบหน้าหันไปในภาพ: -1 = ซ้ายของภาพ · +1 = ขวาของภาพ · 0 = ตรง/ไม่แน่ใจ
 * ใช้จมูกเทียบกึ่งกลางตาก่อน (เรขาคณิต) ไม่มีค่อยใช้ yaw
 */
export const facingDirection = (s: FaceSample, base: FaceSample | null, cfg: LivenessConfig = LIVENESS_CONFIG): -1 | 0 | 1 => {
  if (s.noseOffset !== null) {
    const delta = s.noseOffset - (base?.noseOffset ?? 0);
    if (Math.abs(delta) >= cfg.turnMinNoseOffset) return delta < 0 ? -1 : 1;
    return 0;
  }
  if (s.yaw !== null) {
    const delta = s.yaw - (base?.yaw ?? 0);
    if (Math.abs(delta) >= cfg.turnMinYawDelta) return delta > 0 ? 1 : -1;
  }
  return 0;
};

/** มองตรงพร้อมเป็นค่าอ้างอิงหรือยัง */
export const evaluateNeutral = (s: FaceSample, cfg: LivenessConfig = LIVENESS_CONFIG): { ok: boolean; hint: LivenessHint } => {
  const frame = framingHint(s, cfg);
  if (frame) return { ok: false, hint: frame };
  if (s.yaw !== null && Math.abs(s.yaw) > cfg.neutralMaxYaw) return { ok: false, hint: 'LOOK_STRAIGHT' };
  if (s.pitch !== null && Math.abs(s.pitch) > cfg.neutralMaxPitch) return { ok: false, hint: 'LOOK_STRAIGHT' };
  if (s.noseOffset !== null && Math.abs(s.noseOffset) > cfg.neutralMaxNoseOffset) return { ok: false, hint: 'LOOK_STRAIGHT' };
  const eye = minEye(s);
  if (eye !== null && eye < cfg.eyesOpenMin) return { ok: false, hint: 'OPEN_EYES' };
  return { ok: true, hint: 'GOOD' };
};

/** ทิศในภาพที่คำสั่งหันหน้าต้องการ (ภาพกระจก: ซ้ายของผู้ใช้ = ซ้ายของภาพ) */
const expectedDirection = (challenge: 'turn_left' | 'turn_right', mirrored: boolean): -1 | 1 => {
  const userLeftInImage: -1 | 1 = mirrored ? -1 : 1;
  return challenge === 'turn_left' ? userLeftInImage : (-userLeftInImage as -1 | 1);
};

/** ท่านี้ทำสำเร็จในเฟรมนี้หรือยัง */
export const evaluateChallenge = (
  challenge: EkycChallenge,
  s: FaceSample,
  base: FaceSample | null,
  cfg: LivenessConfig = LIVENESS_CONFIG
): { ok: boolean; hint: LivenessHint } => {
  // หันหน้า/พยักหน้า: กรอบหลวมได้ (หน้าเคลื่อน) แต่ต้องมีหน้าเดียว
  if (s.faceCount <= 0) return { ok: false, hint: 'NO_FACE' };
  if (s.faceCount > 1) return { ok: false, hint: 'MULTIPLE_FACES' };

  switch (challenge) {
    case 'blink': {
      const frame = framingHint(s, cfg);
      if (frame) return { ok: false, hint: frame };
      const eye = maxEye(s);
      if (eye === null) return { ok: false, hint: 'DO_ACTION' };
      if (s.yaw !== null && Math.abs(s.yaw) > cfg.frontalMaxYaw) return { ok: false, hint: 'LOOK_STRAIGHT' };
      if (eye <= cfg.blinkMaxEyeOpen) return { ok: true, hint: 'GOOD' };
      // ภาพนิ่งมักจับได้ตอนตากำลังปิด → รับถ้าตาหรี่ลงชัดเจนเมื่อเทียบกับตอนมองตรง (ทั้งสองข้าง)
      const baseEye = base ? minEye(base) : null;
      if (baseEye !== null && baseEye > 0 && eye <= cfg.blinkRelaxedMaxEyeOpen && eye <= baseEye * cfg.blinkMaxRatio) {
        return { ok: true, hint: 'GOOD' };
      }
      return { ok: false, hint: eye <= 0.6 ? 'MORE' : 'DO_ACTION' };
    }
    case 'smile': {
      const frame = framingHint(s, cfg);
      if (frame) return { ok: false, hint: frame };
      if (s.yaw !== null && Math.abs(s.yaw) > cfg.frontalMaxYaw) return { ok: false, hint: 'LOOK_STRAIGHT' };
      // ต้อง "ยิ้มเพิ่มจากตอนมองตรง": คะแนนยิ้มสูงพอและเพิ่ม ≥ 0.25 หรือปากกว้างขึ้น ≥ 12%
      const baseSmile = base?.smile ?? null;
      const byScore =
        s.smile !== null && s.smile >= cfg.smileMin && (baseSmile === null || s.smile - baseSmile >= cfg.smileMinGain);
      const widthGain = s.mouthWidth !== null && base?.mouthWidth ? s.mouthWidth / base.mouthWidth - 1 : null;
      const byWidth = widthGain !== null && widthGain >= cfg.smileMinWidthGain;
      if (byScore || byWidth) return { ok: true, hint: 'GOOD' };
      if (s.smile === null && widthGain === null) return { ok: false, hint: 'DO_ACTION' };
      const some = (s.smile !== null && s.smile >= 0.4) || (widthGain !== null && widthGain >= cfg.smileMinWidthGain / 2);
      return { ok: false, hint: some ? 'MORE' : 'DO_ACTION' };
    }
    case 'turn_left':
    case 'turn_right': {
      const dir = facingDirection(s, base, cfg);
      const want = expectedDirection(challenge, cfg.mirrored);
      if (dir === want) {
        // หันมากไป → server มองหน้าไม่ชัด/ไม่คล้ายเฟรมมองตรง (ใช้มุม yaw ก่อน ไม่มีค่อยใช้จมูก)
        const tooFar =
          s.yaw !== null
            ? Math.abs(s.yaw) > cfg.turnMaxYaw
            : s.noseOffset !== null && Math.abs(s.noseOffset) > cfg.turnMaxNoseOffset;
        return tooFar ? { ok: false, hint: 'TURN_LESS' } : { ok: true, hint: 'GOOD' };
      }
      if (dir === -want) return { ok: false, hint: 'WRONG_WAY' };
      // หันมาบ้างแล้วแต่ยังไม่พอ
      const partial =
        (s.noseOffset !== null && Math.sign(s.noseOffset - (base?.noseOffset ?? 0)) === want &&
          Math.abs(s.noseOffset - (base?.noseOffset ?? 0)) >= cfg.turnMinNoseOffset / 2) ||
        (s.noseOffset === null && s.yaw !== null && Math.abs(s.yaw - (base?.yaw ?? 0)) >= cfg.turnMinYawDelta / 2);
      return { ok: false, hint: partial ? 'MORE' : 'DO_ACTION' };
    }
    case 'nod': {
      if (s.pitch === null) return { ok: false, hint: 'DO_ACTION' };
      const delta = Math.abs(s.pitch - (base?.pitch ?? 0));
      if (delta >= cfg.nodMinPitchDelta) {
        // ก้ม/เงยมากไป → server มองหน้าไม่ชัด
        return Math.abs(s.pitch) > cfg.nodMaxPitch ? { ok: false, hint: 'NOD_LESS' } : { ok: true, hint: 'GOOD' };
      }
      return { ok: false, hint: delta >= cfg.nodMinPitchDelta / 2 ? 'MORE' : 'DO_ACTION' };
    }
    default:
      return { ok: false, hint: 'DO_ACTION' };
  }
};

// =====================================================
// state machine
// =====================================================

export const createLivenessState = (challenges: EkycChallenge[]): LivenessState => ({
  challenges: [...challenges],
  step: 0,
  baseline: null,
  captured: [],
  done: false,
});

/** ป้ายของขั้นปัจจุบัน (จบแล้ว = null) */
export const currentLabel = (state: LivenessState): EkycFaceLabel | null => {
  if (state.done) return null;
  if (state.step === 0) return 'neutral';
  return state.challenges[state.step - 1] ?? null;
};

/** จำนวนขั้นทั้งหมด (มองตรง + คำสั่ง) */
export const totalSteps = (state: LivenessState): number => state.challenges.length + 1;

/** สัดส่วนความคืบหน้า 0–1 */
export const progressOf = (state: LivenessState): number =>
  state.done ? 1 : Math.max(0, Math.min(1, state.step / totalSteps(state)));

/**
 * ป้อนเฟรมใหม่หนึ่งเฟรม (ไม่แก้ state เดิม)
 * จบครบทุกขั้น → done = true · ป้อนต่อหลังจบ = ไม่ทำอะไร
 */
export const advance = (
  state: LivenessState,
  sample: FaceSample,
  cfg: LivenessConfig = LIVENESS_CONFIG
): AdvanceResult => {
  const label = currentLabel(state);
  if (!label) return { state, accept: false, label: null, hint: 'GOOD' };

  const verdict = label === 'neutral' ? evaluateNeutral(sample, cfg) : evaluateChallenge(label, sample, state.baseline, cfg);
  if (!verdict.ok) return { state, accept: false, label, hint: verdict.hint };

  const step = state.step + 1;
  const next: LivenessState = {
    ...state,
    step,
    baseline: label === 'neutral' ? sample : state.baseline,
    captured: [...state.captured, label],
    done: step >= totalSteps(state),
  };
  return { state: next, accept: true, label, hint: 'GOOD' };
};

/**
 * โหมดนำทางเอง (เครื่องไม่มีตัวตรวจใบหน้า): ทุกขั้นถ่ายตามเวลา แล้วให้ server ตรวจ
 * คืน state ถัดไปเหมือน advance แต่ยอมรับเฟรมเสมอ
 */
export const advanceGuided = (state: LivenessState): AdvanceResult => {
  const label = currentLabel(state);
  if (!label) return { state, accept: false, label: null, hint: 'GOOD' };
  const step = state.step + 1;
  return {
    state: { ...state, step, captured: [...state.captured, label], done: step >= totalSteps(state) },
    accept: true,
    label,
    hint: 'GOOD',
  };
};

// =====================================================
// ภาพบัตร: เดาว่าบัตรอยู่ในกล้องจากรูปหน้าบนบัตร (โหมดกล้องสำรอง เมื่อไม่มีตัวสแกนเอกสาร)
// =====================================================

/**
 * มีรูปหน้าขนาดเล็ก (รูปบนบัตร) ในภาพหรือไม่ — ใช้ช่วยถ่ายอัตโนมัติ ไม่ใช่การตรวจบัตร
 * หน้าคนจริงที่ใหญ่ (เกิน 35% ของภาพ) ไม่นับ
 */
export const looksLikeCardPortrait = (s: FaceSample): boolean => {
  if (s.faceCount < 1 || !s.box) return false;
  if (s.box.w < 0.04 || s.box.w > 0.35) return false;
  const cx = s.box.x + s.box.w / 2;
  const cy = s.box.y + s.box.h / 2;
  return cx > 0.05 && cx < 0.95 && cy > 0.1 && cy < 0.9;
};

/**
 * เลือกขนาดภาพจากรายการของกล้อง ("1920x1080") ที่ด้านยาวใกล้ target ที่สุด (ไม่ต่ำกว่า minLongEdge ถ้ามี)
 * @returns ขนาดที่เลือก · ไม่มีรายการ = undefined (ใช้ค่าเริ่มต้นของกล้อง)
 */
export const pickPictureSize = (sizes: string[] | null | undefined, target: number, minLongEdge: number = 0): string | undefined => {
  const parsed = (sizes || [])
    .map((s) => {
      const m = /^(\d+)x(\d+)$/.exec(String(s).trim());
      return m ? { raw: `${m[1]}x${m[2]}`, long: Math.max(Number(m[1]), Number(m[2])) } : null;
    })
    .filter((s): s is { raw: string; long: number } => s !== null && s.long > 0);
  if (parsed.length === 0) return undefined;
  const usable = parsed.filter((s) => s.long >= minLongEdge);
  const pool = usable.length > 0 ? usable : parsed;
  return pool.sort((a, b) => Math.abs(a.long - target) - Math.abs(b.long - target) || b.long - a.long)[0].raw;
};
