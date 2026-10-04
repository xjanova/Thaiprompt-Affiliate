/**
 * POS Pay API — ลูกค้าจ่ายคำขอจากเครื่อง POS ของร้าน (ส่งด้วยไรเดอร์ Thai Prompt)
 *
 * ลำดับ: ร้านแสดง QR `TPPOS1.{token}` → ลูกค้าสแกน → GET ใบเสนอราคา (เลือกที่อยู่ที่ปักหมุด → ค่าส่งไรเดอร์จาก server)
 *        → ใส่ PIN → POST จ่ายจากกระเป๋าเงิน → ไปหน้าติดตามคำสั่งซื้อ (track_path)
 *
 * - จ่ายด้วยกระเป๋าเงินเท่านั้น (ไม่มีเก็บเงินปลายทาง)
 * - ราคาสินค้า/ค่าส่ง/ยอดรวม มาจาก server ทั้งหมด — แอปแค่แสดง
 * - wallet.balance_ok เป็น boolean อย่างเดียว (server ไม่ส่งยอดเงินในคำขอนี้)
 * - POST ต้องมี Idempotency-Key (สร้างครั้งเดียวต่อการเปิดหน้า แล้วส่งซ้ำตอนลองใหม่ = ไม่ตัดเงินซ้ำ)
 *
 * error code ที่ต้องรู้จัก
 *   GET : REQUEST_NOT_FOUND (404) · REQUEST_EXPIRED (410) · REQUEST_ALREADY_PAID (409 คนอื่นจ่ายแล้ว) · REQUEST_CANCELLED (409)
 *         (จ่ายแล้วโดยผู้ใช้คนนี้ = 200 + status "paid" + order_id)
 *   POST: เหมือน GET + INVALID_PIN (data.attempts_remaining) · WALLET_LOCKED (data.locked_until) · PIN_NOT_SET
 *         · INSUFFICIENT_BALANCE (data.shortfall) · RIDER_NOT_AVAILABLE · ADDRESS_LOCATION_REQUIRED · OUT_OF_STOCK ฯลฯ
 */

import { apiGet, apiPost, newIdempotencyKey, num, type ApiResult } from './client';

// =====================================================
// ชนิดข้อมูล
// =====================================================

export type PosRequestStatus = 'pending' | 'paid' | 'expired' | 'cancelled';

export interface PosRequestItem {
  product_id: number;
  name: string;
  qty: number;
  price: number;
  line_total: number;
  image_url: string | null;
}

/** ที่อยู่ที่ server ใช้คิดค่าส่ง (null = ยังไม่มีที่อยู่ที่เลือกได้) */
export interface PosRequestAddress {
  id: number;
  label: string | null;
  line: string | null;
  /** false = ยังไม่ได้ปักหมุด → ส่งด้วยไรเดอร์ไม่ได้ */
  has_location: boolean;
}

export interface PosRequestRider {
  available: boolean;
  fee: number | null;
  distance_km: number | null;
  /** เหตุผลภาษาไทยเมื่อส่งไม่ได้ (เช่น "ที่อยู่นี้ยังไม่ได้ปักหมุด") */
  message: string | null;
}

export interface PosRequestQuote {
  token: string;
  status: PosRequestStatus;
  expires_at: string | null;
  store: { id: number; name: string; logo_url: string | null } | null;
  items: PosRequestItem[];
  note: string | null;
  subtotal: number;
  address: PosRequestAddress | null;
  rider: PosRequestRider;
  /** ยอดรวม (สินค้า + ค่าส่ง) — null เมื่อยังคิดค่าส่งไม่ได้ */
  total: number | null;
  wallet: { balance_ok: boolean };
  /** มีเมื่อ status = paid โดยผู้ใช้คนนี้ */
  order_id: number | null;
}

export interface PosPayBody {
  address_id: number;
  /** PIN กระเป๋าเงิน 6 หลัก */
  pin: string;
}

export interface PosPayResult {
  order_id: number;
  order_number: string;
  total: number;
  /** path หน้าติดตาม เช่น /order/901 */
  track_path: string;
}

// =====================================================
// แปลงข้อมูลจาก server ให้ชนิดถูกต้องเสมอ (กันค่า null/string ตัวเลข)
// =====================================================

const STATUSES: PosRequestStatus[] = ['pending', 'paid', 'expired', 'cancelled'];

const textOrNull = (value: unknown): string | null =>
  typeof value === 'string' && value.trim().length > 0 ? value : null;

const numOrNull = (value: unknown): number | null => {
  if (value === null || value === undefined || value === '') return null;
  const n = Number(value);
  return Number.isFinite(n) ? n : null;
};

const idOrNull = (value: unknown): number | null => {
  const n = Number(value);
  return Number.isInteger(n) && n > 0 ? n : null;
};

/** แปลง data ของ GET /pos-requests/{token} */
export const normalizePosQuote = (raw: any): PosRequestQuote => {
  const data = raw && typeof raw === 'object' ? raw : {};
  const status = STATUSES.includes(data.status) ? (data.status as PosRequestStatus) : 'pending';
  const storeId = idOrNull(data.store?.id);
  const addressId = idOrNull(data.address?.id);

  return {
    token: typeof data.token === 'string' ? data.token : '',
    status,
    expires_at: textOrNull(data.expires_at),
    store: data.store
      ? { id: storeId ?? 0, name: textOrNull(data.store.name) ?? 'ร้านค้า', logo_url: textOrNull(data.store.logo_url) }
      : null,
    items: (Array.isArray(data.items) ? data.items : []).map((item: any, index: number) => ({
      product_id: idOrNull(item?.product_id) ?? -(index + 1),
      name: textOrNull(item?.name) ?? 'สินค้า',
      qty: Math.max(0, Math.round(num(item?.qty))),
      price: num(item?.price),
      line_total: num(item?.line_total, num(item?.price) * num(item?.qty)),
      image_url: textOrNull(item?.image_url),
    })),
    note: textOrNull(data.note),
    subtotal: num(data.subtotal),
    address: addressId
      ? {
          id: addressId,
          label: textOrNull(data.address.label),
          line: textOrNull(data.address.line),
          has_location: data.address.has_location === true,
        }
      : null,
    rider: {
      available: data.rider?.available === true,
      fee: numOrNull(data.rider?.fee),
      distance_km: numOrNull(data.rider?.distance_km),
      message: textOrNull(data.rider?.message),
    },
    total: numOrNull(data.total),
    wallet: { balance_ok: data.wallet?.balance_ok === true },
    order_id: idOrNull(data.order_id),
  };
};

// =====================================================
// เรียก API
// =====================================================

/** ส่ง token แบบไม่มี prefix (server รับได้ทั้งสองแบบ) */
const tokenPath = (token: string) => `/pos-requests/${encodeURIComponent(token)}`;

/**
 * GET /pos-requests/{token}?address_id= — รายการ ราคา ค่าส่งไรเดอร์ ยอดรวม
 * ไม่ส่ง addressId = server ใช้ที่อยู่หลักของผู้ใช้
 */
export const getPosRequest = async (token: string, addressId?: number | null): Promise<ApiResult<PosRequestQuote>> => {
  const res = await apiGet<unknown>(tokenPath(token), addressId ? { address_id: addressId } : undefined);
  return res.success ? { ...res, data: normalizePosQuote(res.data) } : res;
};

/**
 * POST /pos-requests/{token}/pay — ตัดเงินกระเป๋า (สินค้า + ค่าส่ง) แล้วสร้างคำสั่งซื้อส่งด้วยไรเดอร์
 * idempotencyKey: สร้างครั้งเดียวต่อการเปิดหน้า (เก็บใน ref) แล้วส่งซ้ำตอนลองใหม่
 */
export const payPosRequest = async (
  token: string,
  body: PosPayBody,
  idempotencyKey: string = newIdempotencyKey()
): Promise<ApiResult<PosPayResult>> => {
  const res = await apiPost<any>(`${tokenPath(token)}/pay`, body, { headers: { 'Idempotency-Key': idempotencyKey } });
  if (!res.success) return res;
  const data = res.data && typeof res.data === 'object' ? res.data : {};
  return {
    ...res,
    data: {
      order_id: idOrNull(data.order_id) ?? 0,
      order_number: textOrNull(data.order_number) ?? '',
      total: num(data.total),
      track_path: textOrNull(data.track_path) ?? '',
    },
  };
};
