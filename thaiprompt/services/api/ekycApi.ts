/**
 * eKYC API — ยืนยันตัวตนด้วย AI (บัตรประชาชน + ใบหน้า) สัญญากลาง 2026-10-04
 *
 *   GET   /ekyc/status                         → สถานะยืนยันตัวตนของฉัน
 *   POST  /ekyc/sessions {consent, consent_version} → { session_id, challenges[3], expires_at, attempts_left }
 *   POST  /ekyc/sessions/{id}/id-card  multipart image + doc_type → ผลอ่านบัตร (status ok | retake)
 *   PATCH /ekyc/sessions/{id}/id-card  {name_th?, name_en?, birth_date?} → ผู้ใช้แก้ข้อมูลที่ AI อ่านผิด
 *
 * เอกสาร (2026-11-10): บัตรประชาชนไทย / ใบขับขี่ไทย / หนังสือเดินทางทุกประเทศ / บัตรต่างประเทศ
 *   server ส่งรายการที่รองรับมาใน GET /ekyc/status → documents[] · server รุ่นก่อน = บัตรประชาชนไทยอย่างเดียว
 *   POST  /ekyc/sessions/{id}/face     multipart frames[] + labels[] → ผลตัดสิน approved | review | retake | rejected
 *
 * error ที่หน้าจอต้องแยกจัดการ (ส่วนเพิ่ม 2026-10-04 รอบ 2)
 *   503 EKYC_AI_BUSY        (id-card / face) ระบบตรวจคิวเต็ม — data.retry_after_seconds · รอบไม่เปลี่ยน ส่งคำขอเดิมซ้ำได้
 *   429 EKYC_CARD_LIMIT     (id-card) ส่งรูปบัตรในรอบนี้ครบแล้ว → เริ่มรอบใหม่ (เหมือนรอบหมดเวลา)
 *   409 EKYC_PROCESSING     (face / sessions) ยังตรวจคำขอก่อนหน้าอยู่ → ห้ามเริ่มใหม่ ให้รอดูผลจาก GET /ekyc/status
 *   409 EKYC_SESSION_DONE   (face) รอบนี้ตัดสินไปแล้ว → ดูผลจาก GET /ekyc/status
 *   409 EKYC_PENDING_REVIEW (sessions) รอเจ้าหน้าที่ตรวจและถ่ายใหม่ไม่ได้ → หน้าผลแบบรอตรวจ
 *
 * หลักการ
 *   - ทุกฟังก์ชันคืน ApiResult (ไม่ throw) · ข้อความ error เป็นภาษาไทยจาก client.ts เสมอ
 *   - ข้อมูลจาก server ผ่านตัวแปลงที่นี่ก่อนถึงหน้าจอ (ชนิดแน่นอน ค่าแปลกปลอม = ค่าปลอดภัย)
 *   - รูปบัตร/ใบหน้าเป็นข้อมูลอ่อนไหว: ห้ามพิมพ์ uri/ข้อมูลบัตรลง log · ไฟล์ชั่วคราวลบหลังจบขั้นตอน (ekycStore)
 *   - ป้ายเฟรมใบหน้า: เฟรมแรก = neutral แล้วตามด้วย challenges ตามลำดับที่ server สุ่มให้ (ห้ามสลับ/เพิ่ม/ตัด)
 *   - เฟรมกล้องหน้าเป็นภาพกลับซ้าย-ขวาแบบกระจก (selfie) — turn_left = ผู้ใช้หันไปทาง "ซ้ายของตัวเอง"
 */

import { APP_CONFIG } from '@/constants';
import { apiGet, apiPatch, apiPost, apiUpload, fileFromUri, isThaiText, num, type ApiResult } from './client';

// =====================================================
// ค่าคงที่ / ชนิดข้อมูล
// =====================================================

/**
 * รุ่นข้อความยินยอม PDPA (ต้องตรงกับ server config ekyc.consent_version)
 * 2026-11-10 = ข้อมูลบนเอกสารยืนยันตัวตน (บัตรประชาชน / ใบขับขี่ / หนังสือเดินทาง) + ใบหน้า
 */
export const EKYC_CONSENT_VERSION = '2026-11-10';

/** คำสั่งท่าทางที่ server สุ่มได้ */
export type EkycChallenge = 'blink' | 'turn_left' | 'turn_right' | 'smile' | 'nod';
/** ป้ายของเฟรมใบหน้าที่ส่ง server */
export type EkycFaceLabel = 'neutral' | EkycChallenge;

export const EKYC_CHALLENGES: readonly EkycChallenge[] = ['blink', 'turn_left', 'turn_right', 'smile', 'nod'];

export type KycStatusValue = 'none' | 'pending' | 'approved' | 'rejected';
export type EkycDecision = 'approved' | 'review' | 'retake' | 'rejected';
export type KycMethod = 'manual' | 'ekyc';

/** เหตุผล 1 ข้อพร้อมข้อความไทยที่แสดงได้ */
export interface EkycReasonItem {
  /** รหัสเหตุผล (null = server ส่งข้อความมาแต่รหัสอ่านไม่ได้) */
  code: string | null;
  /** ข้อความไทย — ของ server ก่อน (reason_texts) ไม่มีค่อยใช้ของแอป */
  text: string;
  /** fail = ไม่ผ่านจริง · warn = ระดับกลาง/ให้เจ้าหน้าที่ดู (ห้ามแสดงเป็นติ๊กเขียว) */
  tone: 'fail' | 'warn';
}

/** ชนิดเอกสารยืนยันตัวตน (ต้องตรงกับ config/ekyc.php documents) */
export type EkycDocType = 'thai_id' | 'thai_dl' | 'passport' | 'foreign_id' | 'foreign_dl';

export const EKYC_DOC_TYPES: readonly EkycDocType[] = ['thai_id', 'thai_dl', 'passport', 'foreign_id', 'foreign_dl'];

/** เอกสาร 1 ชนิดที่เลือกได้ในแอป */
export interface EkycDocumentOption {
  type: EkycDocType;
  label: string;
  hint: string;
  /** true = อนุมัติอัตโนมัติได้ · 'mrz' = ได้ถ้ามีแถบ <<< · false = เจ้าหน้าที่ตรวจเสมอ */
  auto: boolean | 'mrz';
}

/** server รุ่นก่อน (ไม่ส่ง documents) รองรับบัตรประชาชนไทยอย่างเดียว */
export const DEFAULT_EKYC_DOCUMENTS: EkycDocumentOption[] = [
  { type: 'thai_id', label: 'บัตรประชาชนไทย', hint: 'ด้านหน้าบัตร ที่มีรูปและเลข 13 หลัก', auto: true },
];

/** เอกสารไทย (เลข 13 หลัก + ชื่อภาษาไทย) */
export const isThaiDocument = (type: EkycDocType | null | undefined): boolean =>
  !type || type === 'thai_id' || type === 'thai_dl';

/** ชื่อเอกสารสั้นๆ ใช้ในหัวข้อ/ปุ่ม */
export const EKYC_DOC_SHORT: Record<EkycDocType, string> = {
  thai_id: 'บัตรประชาชน',
  thai_dl: 'ใบขับขี่',
  passport: 'หนังสือเดินทาง',
  foreign_id: 'บัตรประชาชน',
  foreign_dl: 'ใบขับขี่',
};

export const isEkycDocType = (value: unknown): value is EkycDocType =>
  typeof value === 'string' && (EKYC_DOC_TYPES as readonly string[]).includes(value);

export interface EkycStatus {
  kyc_status: KycStatusValue;
  verified: boolean;
  verified_at: string | null;
  method: KycMethod | null;
  /** เริ่มรอบใหม่ได้หรือไม่ (ยืนยันแล้ว/ลองครบโควตาวันนี้/รอเจ้าหน้าที่ = false) */
  can_start: boolean;
  /** ผลล่าสุดรอเจ้าหน้าที่ตรวจ แต่ยังถ่ายใหม่ได้ (เริ่มรอบใหม่แล้ว server ยกเลิกคิวตรวจเดิมให้) */
  can_retake: boolean;
  /** server ยังตรวจคำขอก่อนหน้าอยู่ (ห้ามเริ่มรอบใหม่ — รอแล้วถามสถานะใหม่) */
  processing: boolean;
  attempts_left: number;
  last_decision: EkycDecision | null;
  reasons: string[];
  /** เหตุผลพร้อมข้อความไทย (ลำดับเดียวกับ reasons) */
  reason_items: EkycReasonItem[];
  /** ข้อความจากเจ้าหน้าที่ (ถ้ามี) */
  message: string | null;
  required_for: string[];
  /** ชื่อตามบัตร (ถ้า server ส่งมา) */
  name_th: string | null;
  /** เลขบัตรแบบปิดบัง (ถ้า server ส่งมา) */
  id_number_masked: string | null;
  /** เอกสารที่ใช้ยืนยันได้ (ลำดับ = ลำดับปุ่ม) */
  documents: EkycDocumentOption[];
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

/** ผลตรวจบัตร: true = ผ่าน · false = ไม่ผ่าน · null = ไม่ทราบ/อ่านไม่ได้ (เจ้าหน้าที่ตรวจให้ — ห้ามแสดงเป็นสีแดง) */
export interface EkycCardChecks {
  checksum: boolean | null;
  not_expired: boolean | null;
  card_real: boolean | null;
  quality_ok: boolean;
}

export interface EkycIdCardResult {
  status: 'ok' | 'retake';
  fields: EkycCardFields;
  checks: EkycCardChecks;
  /** 0–1 */
  ocr_confidence: number;
  /** false = ตัวอ่านบัตรอัตโนมัติไม่พร้อม (ผู้ใช้กรอกชื่อ/วันเกิดเอง เจ้าหน้าที่ตรวจให้) */
  ai_available: boolean;
  /** true = AI อ่านบัตรไม่ได้ซ้ำ ระบบให้ไปต่อแบบกรอกเอง แล้วเจ้าหน้าที่ตรวจจากรูปบัตร */
  manual_review: boolean;
  /** เอกสารที่ server ตรวจ (server รุ่นก่อน = บัตรประชาชนไทย) · country = ISO 3 ตัวอักษร */
  document: { type: EkycDocType; country: string | null };
  reasons: string[];
  reason_items: EkycReasonItem[];
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
  reason_items: EkycReasonItem[];
  /** ข้อความจากเจ้าหน้าที่ (ถ้ามี) */
  message: string | null;
  kyc_status: KycStatusValue;
  attempts_left: number;
  /** submit = คำตอบของ POST face จริง · status = สรุปจากสถานะ/รหัส error (ไม่มีคะแนน ไม่บอกเวลาที่ใช้) */
  source: 'submit' | 'status';
}

export interface EkycCorrections {
  name_th?: string;
  /** ชื่อภาษาอังกฤษตามเอกสาร (หนังสือเดินทาง/บัตรต่างประเทศ) */
  name_en?: string;
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

/** ผ่าน/ไม่ผ่าน/ไม่ทราบ — null/ไม่ส่งมา = ไม่ทราบ (null) */
const triFlag = (value: unknown): boolean | null =>
  value === null || value === undefined || value === '' ? null : passFlag(value);

const isoDate = (value: unknown): string | null => {
  const s = str(value);
  if (!s) return null;
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s);
  return m ? `${m[1]}-${m[2]}-${m[3]}` : null;
};

const REASON_CODE = /^[A-Z0-9_]+(:[a-z_]+)?$/;

const reasonCode = (value: unknown): string | null =>
  typeof value === 'string' && REASON_CODE.test(value.trim()) ? value.trim() : null;

const reasonList = (value: unknown): string[] =>
  (Array.isArray(value) ? value : [])
    .map(reasonCode)
    .filter((r): r is string => r !== null)
    .slice(0, 20);

/** ข้อความจากเจ้าหน้าที่: ข้อความสั้นๆ เท่านั้น (ยาวเกิน = ตัด) */
const staffMessage = (value: unknown): string | null => {
  const s = str(value);
  return s ? s.slice(0, 500) : null;
};

/**
 * จับคู่ reasons กับ reason_texts ของ server ตามลำดับ (index เดียวกัน)
 * - ข้อความของ server (ภาษาไทย) มาก่อนเสมอ · ไม่มี/ไม่ใช่ภาษาไทย → ข้อความของแอป · ไม่มีทั้งคู่ = ไม่แสดง (ไม่โชว์รหัสดิบ)
 * - ตัดข้อความซ้ำ · สูงสุด 20 ข้อ
 *
 * @example buildReasonItems(['BORDERLINE_MATCH'], ['ใบหน้าคล้ายรูปบนบัตรระดับกลาง'])
 * // [{ code: 'BORDERLINE_MATCH', text: 'ใบหน้าคล้ายรูปบนบัตรระดับกลาง', tone: 'warn' }]
 */
export const buildReasonItems = (rawReasons: unknown, rawTexts: unknown): EkycReasonItem[] => {
  const reasons: unknown[] = Array.isArray(rawReasons) ? rawReasons : [];
  const texts: unknown[] = Array.isArray(rawTexts) ? rawTexts : [];
  const out: EkycReasonItem[] = [];
  const count = Math.min(40, Math.max(reasons.length, texts.length));
  for (let i = 0; i < count && out.length < 20; i += 1) {
    const code = reasonCode(reasons[i]);
    const serverText = isThaiText(texts[i]) ? (texts[i] as string).trim() : null;
    const text = serverText ?? (code ? reasonText(code) : null);
    if (!text || out.some((item) => item.text === text)) continue;
    out.push({ code, text, tone: code ? reasonTone(code) : 'warn' });
  }
  return out;
};

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
    can_retake: verified ? false : bool(raw?.can_retake),
    processing: verified ? false : bool(raw?.processing),
    attempts_left: Math.max(0, Math.round(num(raw?.attempts_left, 0))),
    last_decision: decisionOf(raw?.last_decision),
    reasons: reasonList(raw?.reasons),
    reason_items: buildReasonItems(raw?.reasons, raw?.reason_texts),
    message: staffMessage(raw?.message),
    required_for: (Array.isArray(raw?.required_for) ? raw.required_for : []).filter(
      (v: unknown): v is string => typeof v === 'string'
    ),
    name_th: str(raw?.name_th),
    id_number_masked: str(raw?.id_number_masked),
    documents: documentList(raw?.documents),
  };
};

/** รายการเอกสารจาก server · ชนิดที่แอปรุ่นนี้ไม่รู้จักตัดทิ้ง · ว่าง = บัตรประชาชนไทยอย่างเดียว */
const documentList = (raw: unknown): EkycDocumentOption[] => {
  const out: EkycDocumentOption[] = [];
  for (const row of Array.isArray(raw) ? raw : []) {
    const type = (row as any)?.type;
    if (!isEkycDocType(type) || out.some((d) => d.type === type)) continue;
    const auto = (row as any)?.auto;
    out.push({
      type,
      label: str((row as any)?.label) ?? EKYC_DOC_SHORT[type],
      hint: str((row as any)?.hint) ?? '',
      auto: auto === 'mrz' ? 'mrz' : bool(auto),
    });
  }
  return out.length > 0 ? out : DEFAULT_EKYC_DOCUMENTS;
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
      checksum: triFlag(checks.checksum),
      not_expired: triFlag(checks.not_expired),
      card_real: triFlag(checks.card_real),
      quality_ok: passFlag(checks.quality_ok),
    },
    ocr_confidence: score01(raw?.ocr_confidence) ?? 0,
    // server รุ่นก่อนไม่ส่งช่องนี้ = ตัวอ่านบัตรทำงานปกติ
    ai_available: raw?.ai_available === undefined || raw?.ai_available === null ? true : bool(raw.ai_available),
    // server รุ่นก่อนไม่ส่งช่องนี้ = false
    manual_review: raw?.manual_review === true,
    document: {
      type: isEkycDocType(raw?.document?.type) ? raw.document.type : 'thai_id',
      country: str(raw?.document?.country),
    },
    reasons: reasonList(raw?.reasons),
    reason_items: buildReasonItems(raw?.reasons, raw?.reason_texts),
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
    reason_items: buildReasonItems(raw?.reasons, raw?.reason_texts),
    message: staffMessage(raw?.message),
    kyc_status: normalizeKycStatusValue(raw?.kyc_status),
    attempts_left: Math.max(0, Math.round(num(raw?.attempts_left, 0))),
    source: 'submit',
  };
};

/**
 * ข้อความเมื่อ server สุ่มท่าที่แอปรุ่นนี้ไม่รู้จัก
 * แอปแจกเป็น APK จากเว็บของเราเอง (ไม่มีหน้าในสโตร์) → ห้ามบอกให้ไปอัปเดตที่สโตร์ · อัปเดตได้ในแอป (หน้า /app-update)
 */
export const EKYC_APP_OUTDATED_MESSAGE =
  'แอปรุ่นนี้ยังทำขั้นตอนยืนยันตัวตนแบบใหม่ไม่ได้ อัปเดตแอปเป็นเวอร์ชันล่าสุดแล้วลองอีกครั้ง';

/**
 * รอบนี้แอปทำได้ครบหรือไม่ (มี session_id · มีคำสั่ง · ไม่มีคำสั่งที่แอปไม่รู้จัก)
 * ไม่ครบ = ห้ามไปถ่ายใบหน้า (ไม่งั้นส่งท่าไม่ครบ → CHALLENGE_MISMATCH → เริ่มรอบใหม่วนไม่จบ)
 */
export const isSessionUsable = (session: EkycSession | null | undefined): boolean =>
  !!session && !!session.session_id && session.challenges.length > 0 && session.unsupported.length === 0;

/** วินาทีที่ควรรอก่อนลองใหม่ เมื่อระบบตรวจไม่ว่าง (EKYC_AI_BUSY) — จำกัด 3–60 วินาที · ไม่ส่งมา = 10 */
export const retryAfterSeconds = (data: unknown): number => {
  const raw = (data as { retry_after_seconds?: unknown } | null | undefined)?.retry_after_seconds;
  const n = typeof raw === 'number' || typeof raw === 'string' ? Number(raw) : NaN;
  if (!Number.isFinite(n) || n <= 0) return 10;
  return Math.max(3, Math.min(60, Math.round(n)));
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
  REPLAY_SUSPECTED: 'ภาพชุดนี้ดูเหมือนเคยส่งมาแล้ว ต้องถ่ายใหม่จากหน้าจริงตอนนี้',
  DIFFERENT_PEOPLE: 'ใบหน้าในแต่ละภาพดูไม่ใช่คนเดียวกัน',
  FACES_INCONSISTENT: 'ใบหน้าในแต่ละท่าดูไม่ต่อเนื่องกัน',
  LOW_MATCH: 'ใบหน้าคล้ายรูปในบัตรน้อย',
  BORDERLINE_MATCH: 'ใบหน้าคล้ายรูปในบัตรระดับกลาง',
  NO_MATCH_SCORE: 'ระบบเทียบใบหน้ากับรูปในบัตรไม่ได้ เจ้าหน้าที่จะตรวจให้',
  LOW_LIVENESS: 'ระบบยังไม่มั่นใจว่าเป็นคนจริงตรงหน้ากล้อง',
  LOW_REAL: 'ภาพใบหน้าอาจไม่ใช่หน้าจริง',
  LOW_CARD_REAL: 'ยังไม่มั่นใจว่าเป็นบัตรตัวจริง เจ้าหน้าที่จะตรวจให้',
  ID_UNREADABLE: 'อ่านเลขบัตรประชาชนไม่ได้',
  EXPIRY_UNKNOWN: 'อ่านวันบัตรหมดอายุไม่ได้ เจ้าหน้าที่จะตรวจให้',
  PRIOR_REJECTED: 'บัญชีนี้เคยยืนยันตัวตนไม่ผ่าน เจ้าหน้าที่จะตรวจอีกครั้ง',
  ATTEMPTS_EXHAUSTED: 'วันนี้ลองครบจำนวนครั้งแล้ว ส่งให้เจ้าหน้าที่ตรวจแทน',
  ADMIN_REJECTED: 'เจ้าหน้าที่ตรวจแล้วยืนยันตัวตนไม่ผ่าน',
  ADMIN_RETAKE: 'เจ้าหน้าที่ขอให้ถ่ายบัตรและใบหน้าใหม่',
  DUPLICATE_ID: 'บัตรนี้เคยใช้ยืนยันกับบัญชีอื่นแล้ว เจ้าหน้าที่จะตรวจสอบให้',
  USER_CORRECTED: 'มีการแก้ข้อมูลบัตรเอง เจ้าหน้าที่จะตรวจทานอีกครั้ง',
  AI_UNAVAILABLE: 'ระบบ AI ไม่ว่างชั่วคราว ส่งให้เจ้าหน้าที่ตรวจแทนแล้ว',
  CARD_UNREADABLE: 'AI อ่านบัตรไม่ชัด เจ้าหน้าที่จะตรวจจากรูปบัตรให้',
  MRZ_CHECK_FAIL: 'อ่านแถบตัวอักษร <<< ด้านล่างได้ไม่ครบ',
  DOC_MANUAL_REVIEW: 'เอกสารชนิดนี้เจ้าหน้าที่จะตรวจให้',
};

/** เหตุผลที่แปลว่า "ไม่ผ่าน" จริง (ที่เหลือ = ระดับกลาง/ให้เจ้าหน้าที่ดู) */
const FAIL_REASONS = new Set([
  'NO_CARD',
  'BLURRY',
  'GLARE',
  'CARD_INCOMPLETE',
  'OCR_LOW',
  'ID_CHECKSUM_FAIL',
  'MRZ_CHECK_FAIL',
  'EXPIRED',
  'NO_CARD_FACE',
  'NO_FACE',
  'MULTIPLE_FACES',
  'SPOOF_SUSPECTED',
  'REPLAY_SUSPECTED',
  'DIFFERENT_PEOPLE',
  'FACES_INCONSISTENT',
  'LOW_MATCH',
  'LOW_LIVENESS',
  'LOW_REAL',
  'PRIOR_REJECTED',
  'ADMIN_REJECTED',
]);

/** ระดับของเหตุผล: fail = ไม่ผ่าน · warn = ระดับกลาง/เจ้าหน้าที่ตรวจ (ไม่รู้จัก = warn) */
export const reasonTone = (code: string): 'fail' | 'warn' =>
  code.startsWith('CHALLENGE_FAILED') || FAIL_REASONS.has(code) ? 'fail' : 'warn';

/** เหตุผลกลุ่ม "เป็นคนจริง" (ท่าทาง/หน้าจริง) */
const LIVENESS_REASONS = new Set([
  'SPOOF_SUSPECTED',
  'REPLAY_SUSPECTED',
  'NO_FACE',
  'MULTIPLE_FACES',
  'LOW_LIVENESS',
  'LOW_REAL',
  'FACES_INCONSISTENT',
]);

export const isLivenessReason = (code: string): boolean => code.startsWith('CHALLENGE_FAILED') || LIVENESS_REASONS.has(code);

/**
 * สถานะแถว "ใบหน้าตรงกับรูปในบัตร": fail = ไม่ตรง/คล้ายน้อย · warn = ระดับกลาง/เทียบไม่ได้ · null = ไม่มีเหตุผลเรื่องนี้
 */
export const matchReasonTone = (codes: string[]): 'fail' | 'warn' | null => {
  if (codes.some((c) => c === 'DIFFERENT_PEOPLE' || c === 'LOW_MATCH')) return 'fail';
  if (codes.some((c) => c === 'BORDERLINE_MATCH' || c === 'NO_MATCH_SCORE')) return 'warn';
  return null;
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
  'MRZ_CHECK_FAIL',
  'EXPIRED',
  'NO_CARD_FACE',
  'ID_UNREADABLE',
  'LOW_CARD_REAL',
  // เจ้าหน้าที่ขอถ่ายใหม่ = เริ่มจากบัตร (ถ่ายครบทั้งชุด)
  'ADMIN_RETAKE',
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

/**
 * ชื่อภาษาอังกฤษตามหนังสือเดินทาง/บัตรต่างประเทศ (ตัวอักษรละติน ช่องว่าง . ' -) · ไม่ถูกต้อง = null
 * ตรงกับ server: /^[\p{Latin}\s.'\-]+$/u (แอปใช้ช่วง Latin Extended แทน \p{} เพื่อให้ Hermes ทุกรุ่นอ่านได้)
 */
export const cleanLatinName = (value: string): string | null => {
  const name = value.replace(/\s+/g, ' ').trim();
  if (name.length < 2 || name.length > 100) return null;
  return /^[A-Za-z\u00C0-\u024F\s.'\-]+$/.test(name) && /[A-Za-z\u00C0-\u024F]/.test(name) ? name : null;
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

/** POST /ekyc/sessions/{id}/id-card — รูปเอกสารด้านหน้า (jpeg) + ชนิดเอกสาร */
export const uploadEkycIdCard = async (
  sessionId: string,
  uri: string,
  docType: EkycDocType = 'thai_id'
): Promise<ApiResult<EkycIdCardResult>> => {
  const form = new FormData();
  form.append('image', fileFromUri(uri, 'id-card') as unknown as Blob);
  form.append('doc_type', docType);
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
  if (corrections.name_en) body.name_en = corrections.name_en;
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
