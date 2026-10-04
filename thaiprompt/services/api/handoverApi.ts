/**
 * Handover API — ส่งมอบของระหว่างไรเดอร์กับผู้ซื้อ (ไรเดอร์รอบ 2, 2026-10-04)
 *
 * ฝั่งผู้ซื้อ (ไฟล์นี้):
 *   GET  /orders/{source}/{id}/handover                   → { handover, rider, job, settlement, server_now }
 *   POST /orders/{source}/{id}/handover/scan              {token} | {code}  (ผู้ซื้อสแกน QR / กรอกรหัส 6 หลักของไรเดอร์)
 *   POST /orders/{source}/{id}/handover/confirm-received  (ได้รับของแล้ว — ใช้ได้เมื่อ can_confirm_received)
 *   POST /orders/{source}/{id}/handover/dispute           {reason, note?}   (แจ้งปัญหา ไม่ได้รับของ ฯลฯ)
 * ฝั่งไรเดอร์อยู่ใน riderApi.ts (เลน app-rider) — import ชนิดข้อมูลร่วม + ตัวแปลงข้อมูลจากไฟล์นี้
 *
 * หลักการ
 *   - เงินที่พักไว้ (escrow) ปลด/คืนโดย server เท่านั้น แอปแค่แสดงสถานะ
 *   - token ของ QR เป็นของใช้ครั้งเดียว อายุสั้น — แอปห้ามเก็บลงเครื่อง/ห้ามพิมพ์ลง log
 *   - ข้อความ error เป็นภาษาไทยจาก client.ts (THAI_ERROR_MESSAGES) เสมอ
 *   - เวลาทุกตัว (QR หมดอายุ / รอครบ / ปลดเงิน) ตัดสินด้วยนาฬิกา server → ใช้ clock_offset_ms จาก server_now (§A2)
 */

import { APP_INFO } from '@/config/appConfig';
import { isTrustedWebUrl } from '@/utils/linking';
import { clockOffsetFrom, rememberClockOffset } from '@/utils/serverClock';
import { apiGet, apiPost, num, type ApiResult } from './client';
import { fmImageUri } from './taladsodApi';

// =====================================================
// ชนิดข้อมูลร่วม (ใช้ทั้งฝั่งผู้ซื้อและไรเดอร์)
// =====================================================

/** แหล่งที่มาของออเดอร์ */
export type HandoverSource = 'shop' | 'fresh-market';

/** สถานะการส่งมอบ (ตรงกับ DeliveryHandover::STATUS_*) */
export type HandoverStatus =
  | 'waiting'
  | 'rider_confirmed'
  | 'buyer_confirmed'
  | 'completed'
  | 'fallback_waiting'
  | 'fallback_pending_release'
  | 'disputed'
  | 'released'
  | 'refunded';

/** สถานะที่เงินถูกปลด/คืนแล้ว แก้ต่อไม่ได้ */
export const HANDOVER_FINAL_STATUSES: string[] = ['completed', 'released', 'refunded'];

/** การ์ดคน (ไรเดอร์/ผู้ซื้อ) — รูปเป็นรูปลายน้ำจาก server เสมอ */
export interface PersonCard {
  id: number;
  /** ชื่อจริง + อักษรย่อนามสกุล */
  display_name: string;
  photo_url: string | null;
  vehicle_type: string | null;
  vehicle_label: string | null;
  plate_masked: string | null;
  hearts_total: number | null;
  hearts_from_me: number | null;
  can_lock: boolean;
}

export interface SettlementLine {
  key: string;
  label: string;
  amount: number;
  note: string | null;
}

/** การแบ่งเงินหลังปลดเงินพัก (มีเฉพาะตอนจบงาน/ปลดเงินแล้ว) */
export interface Settlement {
  total_paid: number;
  seller_amount: number;
  rider_amount: number;
  referrer_amount: number;
  platform_amount: number;
  lines: SettlementLine[];
}

/** ส่วนที่ทั้งสองฝั่งมีเหมือนกัน */
interface HandoverBase {
  required: boolean;
  status: HandoverStatus | string;
  method: string | null;
  rider_confirmed: boolean;
  buyer_confirmed: boolean;
  /** QR ของ "ฉัน" ให้อีกฝ่ายสแกน */
  qr_token: string | null;
  qr_expires_at: string | null;
  wait_until: string | null;
  auto_release_at: string | null;
  arrival_photo_at: string | null;
  waited_photo_at: string | null;
  completed_at: string | null;
}

export interface HandoverBuyer extends HandoverBase {
  /** รหัส 6 หลักของผู้ซื้อ (บอกไรเดอร์เมื่อกล้องไรเดอร์ใช้ไม่ได้) */
  code: string | null;
  can_dispute: boolean;
  /** กด "ได้รับของแล้ว" ได้ (ไรเดอร์ถ่ายรูปรอที่จุดส่ง / วางของไว้ให้แล้ว) — §A4 */
  can_confirm_received: boolean;
  disputed_at: string | null;
  dispute_reason: string | null;
}

export interface HandoverRider extends HandoverBase {
  /** รหัส 6 หลักของไรเดอร์ (บอกลูกค้าเมื่อกล้องลูกค้าใช้ไม่ได้) — §A3 */
  code: string | null;
  geofence_m: number;
  wait_seconds: number;
  can_arrival_photo: boolean;
  can_waited_photo: boolean;
}

export interface HandoverJobSummary {
  id: number;
  status: string;
  distance_to_dropoff_m: number | null;
}

/** ส่วนเวลาของ server ที่มากับทุก payload การส่งมอบ (§A2) */
export interface HandoverClock {
  /** เวลา server ตอนตอบ (ISO) — server รุ่นเก่าไม่ส่งมา = null */
  server_now: string | null;
  /** เวลา server − เวลาเครื่อง (มิลลิวินาที) · ไม่รู้ = null (ใช้เวลาเครื่อง) */
  clock_offset_ms: number | null;
}

/** ผลของ GET/POST handover ฝั่งผู้ซื้อ */
export interface BuyerHandoverData extends HandoverClock {
  handover: HandoverBuyer;
  rider: PersonCard | null;
  job: HandoverJobSummary | null;
  settlement: Settlement | null;
}

/** ผลของ GET/POST handover ฝั่งไรเดอร์ (riderApi.ts ใช้) */
export interface RiderHandoverData extends HandoverClock {
  handover: HandoverRider;
  buyer: PersonCard | null;
}

/** สถานะงานไรเดอร์ที่ยังไม่ถึงขั้นรับของ (ไรเดอร์ยังไม่ได้ของจากร้าน) — U4 */
export const HANDOVER_PRE_PICKUP_JOB_STATUSES: string[] = ['pending', 'accepted', 'picking_up'];

export type HandoverDisputeReason = 'not_received' | 'wrong_item' | 'damaged' | 'other';

export const HANDOVER_DISPUTE_REASONS: { key: HandoverDisputeReason; label: string; hint: string }[] = [
  { key: 'not_received', label: 'ยังไม่ได้รับของ', hint: 'ไรเดอร์ไม่ได้ส่งของถึงมือหรือหาไม่เจอ' },
  { key: 'wrong_item', label: 'ได้ของไม่ตรงกับที่สั่ง', hint: 'ของไม่ครบหรือผิดรายการ' },
  { key: 'damaged', label: 'ของเสียหาย', hint: 'หก แตก บุบ หรือเสียระหว่างทาง' },
  { key: 'other', label: 'ปัญหาอื่น', hint: 'เล่ารายละเอียดให้ทีมงานฟัง' },
];

// =====================================================
// แปลงข้อมูลจาก server ให้ชนิดแน่นอน
// =====================================================

const str = (value: unknown): string | null => (typeof value === 'string' && value.length > 0 ? value : null);
const bool = (value: unknown): boolean => value === true || value === 1 || value === '1';
const nullableNum = (value: unknown): number | null =>
  value === null || value === undefined || value === '' ? null : Number.isFinite(Number(value)) ? Number(value) : null;

/**
 * รูปคน — รับเฉพาะรูปจากเว็บของเรา (thaiprompt.online หรือ path ของเว็บ) เท่านั้น
 * รูปคนเป็นรูปลายน้ำ URL ลายเซ็นจาก server เสมอ → โดเมนอื่น/scheme อื่น = ไม่แสดง
 */
export const personPhotoUri = (value: unknown): string | null => {
  const uri = fmImageUri(value);
  if (!uri) return null;
  return uri.startsWith(`${APP_INFO.WEBSITE}/`) || isTrustedWebUrl(uri) ? uri : null;
};

export const normalizePersonCard = (raw: any): PersonCard | null => {
  if (!raw || typeof raw !== 'object') return null;
  const id = num(raw.id);
  if (!(id > 0)) return null;
  return {
    id,
    display_name: str(raw.display_name) || str(raw.name) || 'ไรเดอร์',
    photo_url: personPhotoUri(raw.photo_url),
    vehicle_type: str(raw.vehicle_type),
    vehicle_label: str(raw.vehicle_label),
    plate_masked: str(raw.plate_masked),
    hearts_total: nullableNum(raw.hearts_total),
    hearts_from_me: nullableNum(raw.hearts_from_me),
    can_lock: bool(raw.can_lock),
  };
};

export const normalizeSettlement = (raw: any): Settlement | null => {
  if (!raw || typeof raw !== 'object') return null;
  const lines: SettlementLine[] = (Array.isArray(raw.lines) ? raw.lines : [])
    .filter((l: unknown) => l && typeof l === 'object')
    .map((l: any, index: number) => ({
      key: str(l.key) || `line-${index}`,
      label: str(l.label) || '',
      amount: num(l.amount),
      note: str(l.note),
    }))
    .filter((l: SettlementLine) => l.label.length > 0);
  return {
    total_paid: num(raw.total_paid),
    seller_amount: num(raw.seller_amount),
    rider_amount: num(raw.rider_amount),
    referrer_amount: num(raw.referrer_amount),
    platform_amount: num(raw.platform_amount),
    lines,
  };
};

const normalizeBase = (raw: any): HandoverBase => ({
  required: bool(raw?.required),
  status: str(raw?.status) || 'waiting',
  method: str(raw?.method),
  rider_confirmed: bool(raw?.rider_confirmed),
  buyer_confirmed: bool(raw?.buyer_confirmed),
  qr_token: str(raw?.qr_token),
  qr_expires_at: str(raw?.qr_expires_at),
  wait_until: str(raw?.wait_until),
  auto_release_at: str(raw?.auto_release_at),
  arrival_photo_at: str(raw?.arrival_photo_at),
  waited_photo_at: str(raw?.waited_photo_at),
  completed_at: str(raw?.completed_at),
});

/** รหัสต้องเป็นตัวเลข 6 หลักเท่านั้น (กันข้อมูลแปลกปลอมขึ้นจอ) */
const sixDigits = (value: unknown): string | null => {
  const code = typeof value === 'number' ? String(value) : str(value);
  return code && /^\d{6}$/.test(code) ? code : null;
};

export const normalizeHandoverBuyer = (raw: any): HandoverBuyer => ({
  ...normalizeBase(raw),
  code: sixDigits(raw?.code),
  can_dispute: bool(raw?.can_dispute),
  can_confirm_received: bool(raw?.can_confirm_received),
  disputed_at: str(raw?.disputed_at),
  dispute_reason: str(raw?.dispute_reason),
});

export const normalizeHandoverRider = (raw: any): HandoverRider => ({
  ...normalizeBase(raw),
  code: sixDigits(raw?.code),
  geofence_m: num(raw?.geofence_m, 150),
  wait_seconds: num(raw?.wait_seconds, 180),
  can_arrival_photo: bool(raw?.can_arrival_photo),
  can_waited_photo: bool(raw?.can_waited_photo),
});

/**
 * เวลา server จาก payload
 * @param timing เวลาเครื่องตอนส่ง/ได้คำตอบ (ไม่ส่ง = ถือว่าได้คำตอบตอนนี้)
 */
const clockOf = (raw: any, timing?: { sentAt: number; receivedAt: number }): HandoverClock => {
  const serverNow = str(raw?.server_now);
  const offset = serverNow ? clockOffsetFrom(serverNow, timing?.sentAt ?? null, timing?.receivedAt ?? Date.now()) : null;
  return { server_now: serverNow, clock_offset_ms: offset };
};

export const normalizeBuyerHandoverData = (
  raw: any,
  timing?: { sentAt: number; receivedAt: number }
): BuyerHandoverData => ({
  handover: normalizeHandoverBuyer(raw?.handover),
  rider: normalizePersonCard(raw?.rider),
  job:
    raw?.job && typeof raw.job === 'object'
      ? {
          id: num(raw.job.id),
          status: str(raw.job.status) || '',
          distance_to_dropoff_m: nullableNum(raw.job.distance_to_dropoff_m),
        }
      : null,
  settlement: normalizeSettlement(raw?.settlement),
  ...clockOf(raw, timing),
});

export const normalizeRiderHandoverData = (
  raw: any,
  timing?: { sentAt: number; receivedAt: number }
): RiderHandoverData => ({
  handover: normalizeHandoverRider(raw?.handover),
  buyer: normalizePersonCard(raw?.buyer),
  ...clockOf(raw, timing),
});

const mapResult = <A, B>(result: ApiResult<A>, fn: (data: A) => B): ApiResult<B> =>
  result.success ? { ...result, data: fn(result.data) } : result;

/**
 * เรียก API การส่งมอบแล้วแปลงข้อมูล + จับเวลา server (จำ offset ล่าสุดไว้ใช้ร่วมกัน)
 * ใช้ทั้งฝั่งผู้ซื้อและไรเดอร์
 */
export const withHandoverClock = async <T extends HandoverClock>(
  call: () => Promise<ApiResult<any>>,
  normalize: (raw: any, timing: { sentAt: number; receivedAt: number }) => T
): Promise<ApiResult<T>> => {
  const sentAt = Date.now();
  const result = await call();
  const receivedAt = Date.now();
  const mapped = mapResult(result, (raw) => normalize(raw, { sentAt, receivedAt }));
  if (mapped.success) rememberClockOffset(mapped.data.clock_offset_ms);
  return mapped;
};

// =====================================================
// ตัวช่วยสถานะ
// =====================================================

/** เงินถูกปลด/คืนแล้ว (จบงาน) */
export const isHandoverFinal = (h: Pick<HandoverBase, 'status'> | null | undefined): boolean =>
  !!h && HANDOVER_FINAL_STATUSES.includes(String(h.status));

/** จบงานแบบ "ได้รับของแล้ว" (ไม่ใช่คืนเงิน) */
export const isHandoverSuccess = (h: Pick<HandoverBase, 'status'> | null | undefined): boolean =>
  !!h && (h.status === 'completed' || h.status === 'released');

/** QR/token ที่สแกนมา — ตัดของแปลกปลอมก่อนส่ง server (ยาวเกิน/มีช่องว่าง/ตัวควบคุม) */
export const sanitizeScannedToken = (data: unknown): string | null => {
  if (typeof data !== 'string') return null;
  const value = data.trim();
  if (value.length < 8 || value.length > 1024) return null;
  // eslint-disable-next-line no-control-regex
  if (/[\s\u0000-\u001f]/.test(value)) return null;
  return value;
};

// =====================================================
// API ฝั่งผู้ซื้อ
// =====================================================

const base = (source: HandoverSource, orderId: number) => `/orders/${source}/${orderId}/handover`;

/** GET /orders/{source}/{id}/handover — 409 HANDOVER_NOT_READY = ไรเดอร์ยังไม่รับของ */
export const getOrderHandover = (source: HandoverSource, orderId: number): Promise<ApiResult<BuyerHandoverData>> =>
  withHandoverClock(() => apiGet<any>(base(source, orderId)), normalizeBuyerHandoverData);

/**
 * POST .../handover/scan {token} — ผู้ซื้อสแกน QR ของไรเดอร์
 * 422 HANDOVER_TOKEN_INVALID / HANDOVER_TOKEN_EXPIRED · 409 HANDOVER_FINAL / HANDOVER_NOT_READY
 */
export const scanRiderHandoverQr = (
  source: HandoverSource,
  orderId: number,
  token: string
): Promise<ApiResult<BuyerHandoverData>> =>
  withHandoverClock(() => apiPost<any>(`${base(source, orderId)}/scan`, { token }), normalizeBuyerHandoverData);

/**
 * POST .../handover/scan {code} — ผู้ซื้อกรอกรหัส 6 หลักของไรเดอร์ (กล้องผู้ซื้อใช้ไม่ได้) §A3
 * 422 HANDOVER_CODE_INVALID (data.attempts_left) · 429 HANDOVER_CODE_LOCKED (data.locked_until)
 */
export const submitRiderHandoverCode = (
  source: HandoverSource,
  orderId: number,
  code: string
): Promise<ApiResult<BuyerHandoverData>> =>
  withHandoverClock(
    () => apiPost<any>(`${base(source, orderId)}/scan`, { code: String(code).replace(/\D/g, '').slice(0, 6) }),
    normalizeBuyerHandoverData
  );

/**
 * POST .../handover/confirm-received — ผู้ซื้อยืนยัน "ได้รับของแล้ว" (ไรเดอร์รอ/วางของไว้ให้) §A4
 * จบการส่งมอบทันที เงินถูกโอนให้ร้านและไรเดอร์ — ยกเลิกไม่ได้ (หน้าจอต้องถามยืนยันก่อนเรียก)
 */
export const confirmHandoverReceived = (source: HandoverSource, orderId: number): Promise<ApiResult<BuyerHandoverData>> =>
  withHandoverClock(() => apiPost<any>(`${base(source, orderId)}/confirm-received`, {}), normalizeBuyerHandoverData);

/** POST .../handover/dispute {reason, note?} — 409 DISPUTE_NOT_ALLOWED */
export const disputeOrderHandover = (
  source: HandoverSource,
  orderId: number,
  reason: HandoverDisputeReason,
  note?: string
): Promise<ApiResult<BuyerHandoverData>> => {
  const body: Record<string, unknown> = { reason };
  const trimmed = (note || '').trim();
  if (trimmed) body.note = trimmed.slice(0, 1000);
  return withHandoverClock(() => apiPost<any>(`${base(source, orderId)}/dispute`, body), normalizeBuyerHandoverData);
};
