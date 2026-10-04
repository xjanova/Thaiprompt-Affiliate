/**
 * eKYC API — ยืนยันตัวตนด้วย AI (บัตรประชาชน + ใบหน้า) สัญญากลาง 2026-10-04
 *
 *   GET   /ekyc/status                         → สถานะยืนยันตัวตนของฉัน
 *   POST  /ekyc/sessions {consent, consent_version} → { session_id, challenges[3], expires_at, attempts_left }
 *   POST  /ekyc/sessions/{id}/id-card  multipart image → ผลอ่านบัตร (status ok | retake)
 *   PATCH /ekyc/sessions/{id}/id-card  {name_th?, birth_date?} → ผู้ใช้แก้ข้อมูลที่ AI อ่านผิด (ผลสุดท้ายอย่างน้อย = ส่งเจ้าหน้าที่)
 *   POST  /ekyc/sessions/{id}/face     multipart frames[] + labels[] → ผลตัดสิน approved | review | retake | rejected
 *
 * หลักการ
 *   - ทุกฟังก์ชันคืน ApiResult (ไม่ throw) · ข้อความ error เป็นภาษาไทยจาก client.ts เสมอ
 *   - ข้อมูลจาก server ผ่านตัวแปลงที่นี่ก่อนถึงหน้าจอ (ชนิดแน่นอน ค่าแปลกปลอม = ค่าปลอดภัย)
 *   - รูปบัตร/ใบหน้าเป็นข้อมูลอ่อนไหว: ห้ามพิมพ์ uri/ข้อมูลบัตรลง log · ไฟล์ชั่วคราวลบหลังจบขั้นตอน (ekycStore)
 *   - ป้ายเฟรมใบหน้า: เฟรมแรก = neutral แล้วตามด้วย challenges ตามลำดับที่ server สุ่มให้ (ห้ามสลับ/เพิ่ม/ตัด)
 *   - เฟรมกล้องหน้าเป็นภาพกลับซ้าย-ขวาแบบกระจก (selfie) — turn_left = ผู้ใช้หันไปทาง "ซ้ายของตัวเอง"
 */

import { APP_CONFIG } from '@/constants';
import { apiGet, apiPatch, apiPost, apiUpload, fileFromUri, num, type ApiResult } from './client';

// =====================================================
// ค่าคงที่ / ชนิดข้อมูล
// =====================================================

/** รุ่นข้อความยินยอม PDPA (ต้องตรงกับ server) */
export const EKYC_CONSENT_VERSION = '2026-10-04';

/** คำสั่งท่าทางที่ server สุ่มได้ */
export type EkycChallenge = 'blink' | 'turn_left' | 'turn_right' | 'smile' | 'nod';
/** ป้ายของเฟรมใบหน้าที่ส่ง server */
export type EkycFaceLabel = 'neutral' | EkycChallenge;

export const EKYC_CHALLENGES: readonly EkycChallenge[] = ['blink', 'turn_left', 'turn_right', 'smile', 'nod'];

export type KycStatusValue = 'none' | 'pending' | 'approved' | 'rejected';
export type EkycDecision = 'approved' | 'review' | 'retake' | 'rejected';
export type KycMethod = 'manual' | 'ekyc';

export interface EkycStatus {
  kyc_status: KycStatusValue;
  verified: boolean;
  verified_at: string | null;
  method: KycMethod | null;
  /** เริ่มรอบใหม่ได้หรือไม่ (ยืนยันแล้ว/ลองครบโควตาวันนี้ = false) */
  can_start: boolean;
  attempts_left: number;
  last_decision: EkycDecision | null;
  reasons: string[];
  required_for: string[];
  /** ชื่อตามบัตร (ถ้า server ส่งมา) */
  name_th: string | null;
  /** เลขบัตรแบบปิดบัง (ถ้า server ส่งมา) */
  id_number_masked: string | null;
}

export interface EkycSession {
  session_id: string;
  /** คำสั่งที่แอปทำได้ ตามลำดับของ server */
  challenges: EkycChallenge[];
  /** คำสั่งที่แอปรุ่นนี้ไม่รู้จัก (มี = ต้องอัปเดตแอป) */
  unsupported: string[];
  expires_at: string | null;
  attempts_left: number;
}

export interface EkycCardFields {
  id_number_masked: string | null;
  name_th: string | null;
  name_en: string | null;
  /** YYYY-MM-DD (ค.ศ.) */
  birth_date: string | null;
  /** YYYY-MM-DD (ค.ศ.) · null = ตลอดชีพ/อ่านไม่ได้ */
  expiry_date: string | null;
}

export interface EkycCardChecks {
  checksum: boolean;
  not_expired: boolean;
  card_real: boolean;
  quality_ok: boolean;
}

export interface EkycIdCardResult {
  status: 'ok' | 'retake';
  fields: EkycCardFields;
  checks: EkycCardChecks;
  /** 0–1 */
  ocr_confidence: number;
  reasons: string[];
}

export interface EkycScores {
  face_match: number | null;
  liveness: number | null;
  ocr: number | null;
  card_real: number | null;
}

export interface EkycFaceResult {
  decision: EkycDecision;
  scores: EkycScores;
  reasons: string[];
  kyc_status: KycStatusValue;
  attempts_left: number;
}

export interface EkycCorrections {
  name_th?: string;
  /** YYYY-MM-DD (ค.ศ.) */
  birth_date?: string;
}

export interface EkycFrame {
  uri: string;
  label: EkycFaceLabel;
}

// =====================================================
// ตัวแปลงค่า
// =====================================================

const str = (value: unknown): string | null =>
  typeof value === 'string' && value.trim().length > 0 ? value.trim() : null;

const bool = (value: unknown): boolean => value === true || value === 1 || value === '1' || value === 'true';

/** คะแนน 0–1 (รับ 0–100 แล้วหารให้) · ไม่ใช่ตัวเลข = null */
export const score01 = (value: unknown): number | null => {
  if (value === null || value === undefined || value === '' || typeof value === 'boolean') return null;
  const n = Number(value);
  if (!Number.isFinite(n) || n < 0) return null;
  const v = n > 1 ? n / 100 : n;
  return Math.max(0, Math.min(1, v));
};

/** ผ่าน/ไม่ผ่าน จาก boolean หรือคะแนน (คะแนน ≥ 0.5 = ผ่าน) */
const passFlag = (value: unknown): boolean => {
  if (typeof value === 'number' || (typeof value === 'string' && /^\d+(\.\d+)?$/.test(value))) {
    const s = score01(value);
    return s !== null && s >= 0.5;
  }
  return bool(value);
};

const isoDate = (value: unknown): string | null => {
  const s = str(value);
  if (!s) return null;
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s);
  return m ? `${m[1]}-${m[2]}-${m[3]}` : null;
};

const reasonList = (value: unknown): string[] =>
  (Array.isArray(value) ? value : [])
    .filter((r): r is string => typeof r === 'string' && /^[A-Z0-9_]+(:[a-z_]+)?$/.test(r.trim()))
    .map((r) => r.trim())
    .slice(0, 20);

const KYC_STATUS_ALIASES: Record<string, KycStatusValue> = {
  none: 'none',
  not_submitted: 'none',
  unverified: 'none',
  '': 'none',
  pending: 'pending',
  review: 'pending',
  approved: 'approved',
  verified: 'approved',
  rejected: 'rejected',
};

export const normalizeKycStatusValue = (value: unknown): KycStatusValue =>
  KYC_STATUS_ALIASES[typeof value === 'string' ? value.trim().toLowerCase() : ''] ?? 'none';

const DECISIONS: EkycDecision[] = ['approved', 'review', 'retake', 'rejected'];

const decisionOf = (value: unknown): EkycDecision | null =>
  typeof value === 'string' && (DECISIONS as string[]).includes(value) ? (value as EkycDecision) : null;

export const isEkycChallenge = (value: unknown): value is EkycChallenge =>
  typeof value === 'string' && (EKYC_CHALLENGES as readonly string[]).includes(value);

export const normalizeEkycStatus = (raw: any): EkycStatus => {
  const kyc_status = normalizeKycStatusValue(raw?.kyc_status ?? raw?.status);
  const verified = bool(raw?.verified) || kyc_status === 'approved';
  const method = raw?.method === 'ekyc' || raw?.method === 'manual' ? raw.method : null;
  return {
    kyc_status: verified ? 'approved' : kyc_status,
    verified,
    verified_at: str(raw?.verified_at),
    method,
    can_start: verified ? false : raw?.can_start === undefined ? kyc_status !== 'pending' : bool(raw?.can_start),
    attempts_left: Math.max(0, Math.round(num(raw?.attempts_left, 0))),
    last_decision: decisionOf(raw?.last_decision),
    reasons: reasonList(raw?.reasons),
    required_for: (Array.isArray(raw?.required_for) ? raw.required_for : []).filter(
      (v: unknown): v is string => typeof v === 'string'
    ),
    name_th: str(raw?.name_th),
    id_number_masked: str(raw?.id_number_masked),
  };
};

export const normalizeEkycSession = (raw: any): EkycSession => {
  const list: unknown[] = Array.isArray(raw?.challenges) ? raw.challenges : [];
  const challenges: EkycChallenge[] = [];
  const unsupported: string[] = [];
  for (const item of list) {
    if (isEkycChallenge(item)) {
      if (!challenges.includes(item)) challenges.push(item);
    } else {
      unsupported.push(String(item));
    }
  }
  const id = raw?.session_id ?? raw?.id;
  return {
    session_id: typeof id === 'number' || typeof id === 'string' ? String(id) : '',
    challenges,
    unsupported,
    expires_at: str(raw?.expires_at),
    attempts_left: Math.max(0, Math.round(num(raw?.attempts_left, 0))),
  };
};

export const normalizeEkycIdCard = (raw: any): EkycIdCardResult => {
  const fields = raw?.fields && typeof raw.fields === 'object' ? raw.fields : {};
  const checks = raw?.checks && typeof raw.checks === 'object' ? raw.checks : {};
  return {
    status: raw?.status === 'ok' ? 'ok' : 'retake',
    fields: {
      id_number_masked: str(fields.id_number_masked),
      name_th: str(fields.name_th),
      name_en: str(fields.name_en),
      birth_date: isoDate(fields.birth_date),
      expiry_date: isoDate(fields.expiry_date),
    },
    checks: {
      checksum: passFlag(checks.checksum),
      not_expired: passFlag(checks.not_expired),
      card_real: passFlag(checks.card_real),
      quality_ok: passFlag(checks.quality_ok),
    },
    ocr_confidence: score01(raw?.ocr_confidence) ?? 0,
    reasons: reasonList(raw?.reasons),
  };
};

export const normalizeEkycFace = (raw: any): EkycFaceResult => {
  const scores = raw?.scores && typeof raw.scores === 'object' ? raw.scores : {};
  return {
    // ค่าที่ไม่รู้จัก = ส่งเจ้าหน้าที่ (ไม่บอกผู้ใช้ว่าผ่านโดยที่ server ไม่ได้บอก)
    decision: decisionOf(raw?.decision) ?? 'review',
    scores: {
      face_match: score01(scores.face_match),
      liveness: score01(scores.liveness),
      ocr: score01(scores.ocr),
      card_real: score01(scores.card_real),
    },
    reasons: reasonList(raw?.reasons),
    kyc_status: normalizeKycStatusValue(raw?.kyc_status),
    attempts_left: Math.max(0, Math.round(num(raw?.attempts_left, 0))),
  };
};

const mapResult = <A, B>(result: ApiResult<A>, fn: (data: A) => B): ApiResult<B> =>
  result.success ? { ...result, data: fn(result.data) } : result;

// =====================================================
// เหตุผลจาก AI → ข้อความไทย
// =====================================================

/** ชื่อท่าทางภาษาไทย (สั้น ใช้บนชิป) */
export const CHALLENGE_LABEL: Record<EkycFaceLabel, string> = {
  neutral: 'มองตรง',
  blink: 'กระพริบตา',
  turn_left: 'หันซ้าย',
  turn_right: 'หันขวา',
  smile: 'ยิ้ม',
  nod: 'พยักหน้า',
};

const REASON_TEXT: Record<string, string> = {
  NO_CARD: 'ไม่พบบัตรประชาชนในรูป',
  BLURRY: 'รูปบัตรไม่ชัด',
  GLARE: 'มีแสงสะท้อนบนบัตร',
  CARD_INCOMPLETE: 'บัตรอยู่ในกรอบไม่ครบทั้งใบ',
  OCR_LOW: 'อ่านตัวอักษรบนบัตรได้ไม่ชัด',
  ID_CHECKSUM_FAIL: 'เลขบัตรประชาชนไม่ถูกต้องตามหลักตรวจสอบ',
  EXPIRED: 'บัตรประชาชนหมดอายุแล้ว',
  EXPIRY_LIFELONG: 'บัตรแบบใช้ได้ตลอดชีพ',
  NO_CARD_FACE: 'มองไม่เห็นรูปหน้าบนบัตร',
  NO_FACE: 'ไม่พบใบหน้าในกล้อง',
  MULTIPLE_FACES: 'มีมากกว่าหนึ่งใบหน้าในกล้อง',
  SPOOF_SUSPECTED: 'ภาพอาจมาจากหน้าจอหรือรูปถ่าย ไม่ใช่หน้าจริง',
  DIFFERENT_PEOPLE: 'ใบหน้าในแต่ละภาพดูไม่ใช่คนเดียวกัน',
  LOW_MATCH: 'ใบหน้าคล้ายรูปในบัตรระดับกลาง',
  DUPLICATE_ID: 'บัตรนี้เคยใช้ยืนยันกับบัญชีอื่นแล้ว เจ้าหน้าที่จะตรวจสอบให้',
  USER_CORRECTED: 'มีการแก้ข้อมูลบัตรเอง เจ้าหน้าที่จะตรวจทานอีกครั้ง',
  AI_UNAVAILABLE: 'ระบบ AI ไม่ว่างชั่วคราว ส่งให้เจ้าหน้าที่ตรวจแทนแล้ว',
};

/** ข้อความไทยของเหตุผล 1 ข้อ · ไม่รู้จัก = null (ไม่แสดงรหัสดิบให้ผู้ใช้) */
export const reasonText = (code: string): string | null => {
  if (code.startsWith('CHALLENGE_FAILED:')) {
    const label = code.slice('CHALLENGE_FAILED:'.length);
    return isEkycChallenge(label) ? `ระบบไม่เห็นท่า "${CHALLENGE_LABEL[label]}" ชัดพอ` : 'ทำท่าตามคำสั่งไม่ครบ';
  }
  return REASON_TEXT[code] ?? null;
};

/** ข้อความไทยของเหตุผลทั้งหมด (ตัดซ้ำ/ตัดที่ไม่รู้จัก) */
export const reasonTexts = (codes: string[]): string[] => {
  const out: string[] = [];
  for (const code of codes) {
    const text = reasonText(code);
    if (text && !out.includes(text)) out.push(text);
  }
  return out;
};

const CARD_REASONS = new Set([
  'NO_CARD',
  'BLURRY',
  'GLARE',
  'CARD_INCOMPLETE',
  'OCR_LOW',
  'ID_CHECKSUM_FAIL',
  'EXPIRED',
  'NO_CARD_FACE',
]);

/** เหตุผลข้อนี้เป็นปัญหาของรูปบัตรหรือไม่ */
export const isCardReason = (code: string): boolean => CARD_REASONS.has(code);

/** ถ่ายใหม่ควรเริ่มที่บัตรหรือใบหน้า */
export const retakeTarget = (reasons: string[]): 'card' | 'face' =>
  reasons.some(isCardReason) ? 'card' : 'face';

// =====================================================
// วันที่ไทย (บัตรประชาชนพิมพ์ปี พ.ศ.)
// =====================================================

export const THAI_MONTHS = [
  'มกราคม',
  'กุมภาพันธ์',
  'มีนาคม',
  'เมษายน',
  'พฤษภาคม',
  'มิถุนายน',
  'กรกฎาคม',
  'สิงหาคม',
  'กันยายน',
  'ตุลาคม',
  'พฤศจิกายน',
  'ธันวาคม',
];

export const THAI_MONTHS_SHORT = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

/**
 * YYYY-MM-DD (ค.ศ.) → "12 มกราคม 2538" (พ.ศ.)
 * @example formatThaiDate('1995-01-12') // '12 มกราคม 2538'
 */
export const formatThaiDate = (iso: string | null | undefined, short: boolean = false): string | null => {
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso || '');
  if (!m) return null;
  const month = Number(m[2]);
  const day = Number(m[3]);
  if (month < 1 || month > 12 || day < 1 || day > 31) return null;
  return `${day} ${(short ? THAI_MONTHS_SHORT : THAI_MONTHS)[month - 1]} ${Number(m[1]) + 543}`;
};

/**
 * วัน/เดือน/ปี พ.ศ. ที่ผู้ใช้กรอก → YYYY-MM-DD (ค.ศ.) · วันไม่มีจริง/อนาคต/อายุเกิน 120 ปี = null
 * @example parseThaiBirthDate('12', '1', '2538') // '1995-01-12'
 */
export const parseThaiBirthDate = (
  day: string | number,
  month: string | number,
  yearBE: string | number,
  now: Date = new Date()
): string | null => {
  const d = Number(String(day).trim());
  const mo = Number(String(month).trim());
  const yBE = Number(String(yearBE).trim());
  if (!Number.isInteger(d) || !Number.isInteger(mo) || !Number.isInteger(yBE)) return null;
  const y = yBE - 543;
  if (mo < 1 || mo > 12 || d < 1) return null;
  const daysInMonth = new Date(Date.UTC(y, mo, 0)).getUTCDate();
  if (d > daysInMonth) return null;
  const thisYear = now.getFullYear();
  if (y > thisYear || y < thisYear - 120) return null;
  const iso = `${String(y).padStart(4, '0')}-${String(mo).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
  const today = `${thisYear}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
  return iso > today ? null : iso;
};

/** ชื่อภาษาไทยที่ผู้ใช้แก้: ตัดช่องว่างซ้ำ · ต้องมีอักษรไทย 2–100 ตัว */
export const cleanThaiName = (value: string): string | null => {
  const name = value.replace(/\s+/g, ' ').trim();
  if (name.length < 2 || name.length > 100) return null;
  return /[฀-๿]/.test(name) ? name : null;
};

// =====================================================
// API
// =====================================================

const BASE = '/ekyc';

/** อัปโหลดรูปบัตร/ใบหน้าใช้เวลานาน (AI ประมวลผลบนเซิร์ฟเวอร์สูงสุดราว 25 วินาที) */
const EKYC_UPLOAD_TIMEOUT = Math.max(APP_CONFIG.FILE_UPLOAD_TIMEOUT, 90_000);

/** GET /ekyc/status */
export const getEkycStatus = async (): Promise<ApiResult<EkycStatus>> =>
  mapResult(await apiGet<any>(`${BASE}/status`), normalizeEkycStatus);

/** POST /ekyc/sessions — ต้องกดยินยอม (PDPA) ก่อนเสมอ */
export const createEkycSession = async (): Promise<ApiResult<EkycSession>> =>
  mapResult(
    await apiPost<any>(`${BASE}/sessions`, { consent: true, consent_version: EKYC_CONSENT_VERSION }, {
      fallbackMessage: 'เริ่มยืนยันตัวตนไม่สำเร็จ ลองใหม่อีกครั้งนะ',
    }),
    normalizeEkycSession
  );

/** POST /ekyc/sessions/{id}/id-card — รูปบัตรด้านหน้า (jpeg) */
export const uploadEkycIdCard = async (sessionId: string, uri: string): Promise<ApiResult<EkycIdCardResult>> => {
  const form = new FormData();
  form.append('image', fileFromUri(uri, 'id-card') as unknown as Blob);
  return mapResult(
    await apiUpload<any>(`${BASE}/sessions/${encodeURIComponent(sessionId)}/id-card`, form, {
      timeout: EKYC_UPLOAD_TIMEOUT,
      fallbackMessage: 'ส่งรูปบัตรไม่สำเร็จ ลองใหม่อีกครั้งนะ',
    }),
    normalizeEkycIdCard
  );
};

/** PATCH /ekyc/sessions/{id}/id-card — แก้เฉพาะช่องที่ผู้ใช้เปลี่ยน */
export const correctEkycIdCard = async (
  sessionId: string,
  corrections: EkycCorrections
): Promise<ApiResult<EkycIdCardResult | null>> => {
  const body: Record<string, string> = {};
  if (corrections.name_th) body.name_th = corrections.name_th;
  if (corrections.birth_date) body.birth_date = corrections.birth_date;
  const result = await apiPatch<any>(`${BASE}/sessions/${encodeURIComponent(sessionId)}/id-card`, body, {
    fallbackMessage: 'บันทึกการแก้ไขไม่สำเร็จ ลองใหม่อีกครั้งนะ',
  });
  return mapResult(result, (raw) => (raw && typeof raw === 'object' && raw.fields ? normalizeEkycIdCard(raw) : null));
};

/**
 * ตรวจลำดับเฟรมก่อนส่ง: neutral ตามด้วย challenges ตามลำดับ server เป๊ะๆ
 * @returns true = ส่งได้
 */
export const framesMatchChallenges = (frames: EkycFrame[], challenges: EkycChallenge[]): boolean =>
  frames.length === challenges.length + 1 &&
  frames[0]?.label === 'neutral' &&
  challenges.every((c, i) => frames[i + 1]?.label === c);

/** POST /ekyc/sessions/{id}/face — เฟรม jpeg + ป้ายตามลำดับ */
export const submitEkycFace = async (sessionId: string, frames: EkycFrame[]): Promise<ApiResult<EkycFaceResult>> => {
  const form = new FormData();
  frames.forEach((frame, index) => {
    form.append('frames[]', fileFromUri(frame.uri, `face-${index}-${frame.label}`) as unknown as Blob);
    form.append('labels[]', frame.label);
  });
  return mapResult(
    await apiUpload<any>(`${BASE}/sessions/${encodeURIComponent(sessionId)}/face`, form, {
      timeout: EKYC_UPLOAD_TIMEOUT,
      fallbackMessage: 'ส่งรูปใบหน้าไม่สำเร็จ ลองใหม่อีกครั้งนะ',
    }),
    normalizeEkycFace
  );
};
