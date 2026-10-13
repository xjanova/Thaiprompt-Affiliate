/**
 * TPIX API — ราคาเหรียญ TPIX (เชน 4289) + ใบเสนอยอดชำระด้วยเหรียญ
 *
 * เส้นที่ใช้
 *   GET  /tpix/price                         ราคาจริงตอนนี้ (public) — 503 = ยังรู้ราคาไม่ได้
 *   GET  /tpix/payments/{reference}          สถานะใบเสนอยอด (server ตรวจบนเชนให้ทุกครั้งที่เรียก)
 *   POST /tpix/payments/{reference}/submit   ส่งเลขธุรกรรม (tx hash) ให้ตรวจทันที
 *
 * การ "เริ่มจ่าย" ใช้เส้นเดิม: POST /cart/checkout · /payment/order · /wallet/topup ด้วย payment_method = 'tpix'
 * → ผลลัพธ์มีก้อน tpix (ใช้ normalizeTpixPayment แปลงก่อนใช้เสมอ)
 *
 * หลักการ
 *   - ทุกฟิลด์ใหม่เป็น optional — server รุ่นเก่าไม่ส่ง = ซ่อน ไม่พัง
 *   - ราคา/ยอดเหรียญมาจาก server ทั้งหมด แอปคำนวณเองแค่ "ยอดประมาณ" สำหรับแสดงผล (ขึ้นต้นด้วย ≈ เสมอ)
 *   - ยอดที่ต้องโอนจริงต้องตรงทุกหลัก (เลขท้าย 8 ตำแหน่งเฉพาะใบ) → ใช้ amount_tpix_exact ตอนคัดลอก
 */

import { isTrustedWebUrl } from '@/utils/linking';
import { apiGet, apiPost, num, type ApiResult } from './client';

// =====================================================
// ป้ายราคา TPIX ของสินค้า (price_tpix + tpix)
// =====================================================

/**
 * ราคา TPIX ของสินค้า 1 ชิ้น (ก้อน `tpix` ที่ server แนบมากับสินค้า/รายการในตะกร้า/เมนูตลาดสด)
 * null = ไม่รู้ราคา หรือแอดมินปิดการแสดง → ซ่อน
 */
export interface TpixPriceTag {
  /** ยอดเหรียญที่จัดรูปแบบแล้ว เช่น "307.69" */
  amount: string;
  /** ยอดเหรียญแบบตัวเลขล้วน เช่น "307.6924" */
  raw: string;
  /** true = ราคาเหรียญคงที่ของสินค้า (Official Shop) — ไม่ขึ้นกับราคาตลาด */
  fixed: boolean;
  /** ราคา 1 TPIX เป็นบาท เช่น "3.25" */
  rate_thb: string | null;
  /** ราคา 1 TPIX แบบแสดงผล เช่น "฿3.2500" */
  rate_display: string | null;
  /** เวลาที่อ่านราคาล่าสุด (ISO) */
  updated_at: string | null;
}

const str = (value: unknown): string | null => (typeof value === 'string' && value.trim().length > 0 ? value.trim() : null);

/** ตัวเลขทศนิยมแบบสตริง (ไม่มีจุลภาค) ที่ใช้ได้จริงและมากกว่า 0 */
const positiveNumber = (value: unknown): number | null => {
  if (value === null || value === undefined || value === '') return null;
  const n = typeof value === 'number' ? value : Number(String(value).replace(/,/g, ''));
  return Number.isFinite(n) && n > 0 ? n : null;
};

/**
 * แปลงก้อน tpix จาก server เป็น TpixPriceTag (ข้อมูลไม่ครบ/ไม่ถูกต้อง = null → ซ่อน)
 *
 * @example normalizeTpixTag(product.tpix)?.amount // "307.69"
 */
export const normalizeTpixTag = (raw: unknown): TpixPriceTag | null => {
  if (!raw || typeof raw !== 'object') return null;
  const t = raw as Record<string, unknown>;
  const rawAmount = positiveNumber(t.raw) ?? positiveNumber(t.amount);
  if (rawAmount === null) return null;
  const rawText = str(t.raw) ?? String(rawAmount);
  return {
    amount: str(t.amount) ?? formatTpix(rawAmount),
    raw: rawText.replace(/,/g, ''),
    fixed: t.fixed === true || t.fixed === 1 || t.fixed === '1',
    rate_thb: positiveNumber(t.rate_thb) !== null ? String(t.rate_thb).replace(/,/g, '') : null,
    rate_display: str(t.rate_display),
    updated_at: str(t.updated_at),
  };
};

/** ราคา 1 TPIX (บาท) จากป้าย — ไม่มี = null */
export const tagRateThb = (tag: TpixPriceTag | null | undefined): number | null => positiveNumber(tag?.rate_thb);

// =====================================================
// จัดรูปแบบตัวเลขเหรียญ
// =====================================================

/**
 * จัดรูปแบบยอดเหรียญสำหรับแสดงผล (ตรงกับฝั่งเว็บ: ≥ 100 แสดง 2 ตำแหน่ง · น้อยกว่านั้น 4 ตำแหน่ง)
 *
 * @example formatTpix(307.69241) // "307.69"
 * @example formatTpix(12.5) // "12.5000"
 */
export const formatTpix = (value: number, decimals?: number): string => {
  if (!Number.isFinite(value)) return '—';
  const digits = decimals ?? (Math.abs(value) >= 100 ? 2 : 4);
  try {
    return value.toLocaleString('en-US', { minimumFractionDigits: digits, maximumFractionDigits: digits });
  } catch {
    const fixed = value.toFixed(digits);
    const [intPart, fracPart] = fixed.split('.');
    return intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (fracPart ? `.${fracPart}` : '');
  }
};

/** ราคา 1 TPIX เป็นบาทแบบแสดงผล (≥ 1 บาท = 2 ตำแหน่ง · น้อยกว่า = 4 ตำแหน่ง) */
export const formatTpixRate = (rateThb: number): string => {
  if (!Number.isFinite(rateThb) || rateThb <= 0) return '—';
  const digits = rateThb >= 1 ? 2 : 4;
  try {
    return `฿${rateThb.toLocaleString('en-US', { minimumFractionDigits: digits, maximumFractionDigits: digits })}`;
  } catch {
    return `฿${rateThb.toFixed(digits)}`;
  }
};

/** เวลา HH:mm (เวลาเครื่อง) จาก ISO — ค่าไม่ถูกต้อง = null */
export const formatTpixTime = (iso: string | null | undefined): string | null => {
  if (!iso) return null;
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return null;
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};

// =====================================================
// คำนวณยอดประมาณ (แสดงผลเท่านั้น — ยอดจริงออกโดย server ตอนสั่งซื้อ)
// =====================================================

export interface TpixEstimateLine {
  /** ก้อน tpix ของรายการ (ราคาต่อชิ้น) */
  tpix?: unknown;
  quantity: number;
  /** ยอดบาทของรายการนี้ (หลังส่วนลดของรายการ) */
  thb: number;
}

/**
 * ยอดเหรียญโดยประมาณของยอดบาททั้งก้อน — วิธีเดียวกับ server (orderTpixTotal):
 * รายการราคาคงที่ใช้ราคาคงที่ × จำนวน · ที่เหลือ (รวมค่าส่ง/ส่วนลด) แปลงจากบาทด้วยราคาปัจจุบัน
 *
 * @returns null เมื่อยอด ≤ 0 หรือไม่รู้ราคา
 */
export const estimateTpixTotal = (
  totalThb: number,
  rateThb: number | null,
  lines: TpixEstimateLine[] = []
): number | null => {
  if (!Number.isFinite(totalThb) || totalThb <= 0) return null;
  let fixedTpix = 0;
  let convertThb = totalThb;
  for (const line of lines) {
    const tag = normalizeTpixTag(line.tpix);
    if (!tag?.fixed) continue;
    const unit = positiveNumber(tag.raw);
    if (unit === null) continue;
    fixedTpix += unit * Math.max(1, line.quantity || 1);
    convertThb -= Math.max(0, line.thb || 0);
  }
  convertThb = Math.max(0, convertThb);
  if (convertThb > 0 && (!rateThb || rateThb <= 0)) return null;
  const converted = convertThb > 0 && rateThb ? convertThb / rateThb : 0;
  const total = fixedTpix + converted;
  return total > 0 ? total : null;
};

// =====================================================
// ราคาปัจจุบัน — GET /tpix/price
// =====================================================

export interface TpixSpot {
  price_usd: string | null;
  /** ราคา 1 TPIX เป็นบาท (ตัวเลขล้วน) */
  price_thb: number;
  price_thb_display: string;
  usd_thb: string | null;
  source: string | null;
  updated_at: string | null;
  chain_id: number;
  payments_enabled: boolean;
  show_prices: boolean;
}

export interface TpixPriceResult {
  spot: TpixSpot | null;
  /** แอดมินเปิดรับชำระด้วย TPIX (ราคาไม่พร้อม = false) */
  payments_enabled: boolean;
  /** แอดมินเปิดแสดงราคา TPIX ข้างราคาบาท */
  show_prices: boolean;
}

const normalizeSpot = (raw: any): TpixSpot | null => {
  const priceThb = positiveNumber(raw?.price_thb);
  if (!raw || typeof raw !== 'object' || priceThb === null) return null;
  return {
    price_usd: str(raw.price_usd),
    price_thb: priceThb,
    price_thb_display: str(raw.price_thb_display) ?? formatTpixRate(priceThb),
    usd_thb: str(raw.usd_thb),
    source: str(raw.source),
    updated_at: str(raw.updated_at),
    chain_id: num(raw.chain_id, 4289),
    payments_enabled: raw.payments_enabled === true,
    show_prices: raw.show_prices !== false,
  };
};

/**
 * ราคา TPIX ปัจจุบัน — ไม่ throw
 * 503 (ยังรู้ราคาไม่ได้) → success:true แต่ spot = null และ payments_enabled = false
 * server เก่า (ไม่มีเส้นนี้) → success:false (ซ่อนทุกอย่างเกี่ยวกับ TPIX)
 */
export const getTpixPrice = async (): Promise<ApiResult<TpixPriceResult>> => {
  const res = await apiGet<any>('/tpix/price');
  if (res.success) {
    const spot = normalizeSpot(res.data);
    return {
      ...res,
      data: {
        spot,
        payments_enabled: !!spot && res.data?.payments_enabled === true,
        show_prices: res.data?.show_prices !== false,
      },
    };
  }
  if (res.status === 503) {
    return {
      success: true,
      status: 503,
      message: res.message,
      data: { spot: null, payments_enabled: false, show_prices: res.data?.show_prices !== false },
    };
  }
  return res;
};

// =====================================================
// ใบเสนอยอดชำระด้วยเหรียญ
// =====================================================

export type TpixPaymentStatus =
  | 'pending'
  | 'confirming'
  | 'paid'
  | 'credited_wallet'
  | 'late'
  | 'expired'
  | 'cancelled'
  | 'failed';

/** สถานะที่ยังต้องเช็คต่อ (ยังไม่จบ) */
export const TPIX_ACTIVE_STATUSES: readonly string[] = ['pending', 'confirming', 'late'];

export interface TpixPayment {
  reference: string;
  /** order | wallet_topup */
  purpose: string | null;
  order_id: number | null;
  order_number: string | null;
  status: TpixPaymentStatus | string;
  status_label: string;
  is_settled: boolean;
  amount_thb: number;
  /** ยอดเหรียญที่ต้องโอน (แสดงผล 8 ตำแหน่ง อาจมีจุลภาค) */
  amount_tpix: string;
  /** ยอดเหรียญตรงตัว (ไม่มีจุลภาค) — ใช้ตอนคัดลอก */
  amount_tpix_exact: string;
  amount_wei: string | null;
  amount_wei_hex: string | null;
  rate_thb: string | null;
  rate_display: string | null;
  /** spot | twap ... */
  rate_basis: string | null;
  price_usd: string | null;
  usd_thb: string | null;
  fixed_price_lines: number;
  receive_address: string;
  chain_id: number;
  chain_id_hex: string | null;
  chain_name: string | null;
  rpc_url: string | null;
  explorer_url: string | null;
  /** ลิงก์ ethereum: สำหรับเปิดกระเป๋า (MetaMask / Trust ฯลฯ) และทำ QR */
  eip681: string | null;
  /** หน้าจ่ายบนเว็บ (เปิดได้โดยไม่ต้องล็อกอิน) */
  pay_url: string | null;
  expires_at: string | null;
  seconds_left: number | null;
  confirmations: number;
  confirmations_required: number;
  tx_hash: string | null;
  explorer_tx_url: string | null;
  failure_reason: string | null;
  paid_at: string | null;
}

const REFERENCE_RE = /^TPX[A-Z0-9]{21}$/;
const ADDRESS_RE = /^0x[0-9a-fA-F]{40}$/;
const TX_HASH_RE = /^0x[0-9a-fA-F]{64}$/;

/** รหัสอ้างอิงใบเสนอยอดถูกรูปแบบหรือไม่ (TPX + 21 ตัว) */
export const isTpixReference = (value: unknown): value is string => typeof value === 'string' && REFERENCE_RE.test(value);

/** เลขธุรกรรม (tx hash) ถูกรูปแบบหรือไม่ — 0x + 64 hex */
export const isValidTxHash = (value: unknown): value is string => typeof value === 'string' && TX_HASH_RE.test(value.trim());

const httpsOrNull = (value: unknown): string | null => {
  const s = str(value);
  return s && s.length <= 2048 && /^https:\/\/[^\s]+$/i.test(s) ? s : null;
};

/**
 * แปลงก้อน tpix ของใบเสนอยอดจาก server — ข้อมูลสำคัญไม่ครบ (รหัสอ้างอิง/กระเป๋ารับ/ยอด) = null
 * ลิงก์หน้าจ่ายรับเฉพาะเว็บของเรา · ลิงก์ ethereum: ต้องชี้ไปกระเป๋ารับเงินเดียวกับที่แสดง
 */
export const normalizeTpixPayment = (raw: unknown): TpixPayment | null => {
  if (!raw || typeof raw !== 'object') return null;
  const p = raw as Record<string, unknown>;
  const reference = str(p.reference);
  const address = str(p.receive_address);
  const exact = str(p.amount_tpix_exact) ?? str(p.amount_tpix);
  if (!isTpixReference(reference) || !address || !ADDRESS_RE.test(address) || !exact) return null;
  const exactClean = exact.replace(/,/g, '');
  if (positiveNumber(exactClean) === null) return null;

  const eip681Raw = str(p.eip681);
  const eip681 =
    eip681Raw && eip681Raw.length <= 512 && eip681Raw.toLowerCase().startsWith(`ethereum:${address.toLowerCase()}`)
      ? eip681Raw
      : null;
  const payUrl = str(p.pay_url);
  const secondsLeft = p.seconds_left === null || p.seconds_left === undefined ? null : Math.max(0, num(p.seconds_left));
  const orderId = num(p.order_id, 0);

  return {
    reference,
    purpose: str(p.purpose),
    order_id: orderId > 0 ? orderId : null,
    order_number: str(p.order_number),
    status: str(p.status) ?? 'pending',
    status_label: str(p.status_label) ?? '',
    is_settled: p.is_settled === true,
    amount_thb: num(p.amount_thb),
    amount_tpix: str(p.amount_tpix) ?? exactClean,
    amount_tpix_exact: exactClean,
    amount_wei: str(p.amount_wei),
    amount_wei_hex: str(p.amount_wei_hex),
    rate_thb: str(p.rate_thb),
    rate_display: str(p.rate_display),
    rate_basis: str(p.rate_basis),
    price_usd: str(p.price_usd),
    usd_thb: str(p.usd_thb),
    fixed_price_lines: Math.max(0, num(p.fixed_price_lines)),
    receive_address: address,
    chain_id: num(p.chain_id, 4289),
    chain_id_hex: str(p.chain_id_hex),
    chain_name: str(p.chain_name),
    rpc_url: httpsOrNull(p.rpc_url),
    explorer_url: httpsOrNull(p.explorer_url),
    eip681,
    pay_url: isTrustedWebUrl(payUrl) ? payUrl : null,
    expires_at: str(p.expires_at),
    seconds_left: secondsLeft,
    confirmations: Math.max(0, num(p.confirmations)),
    confirmations_required: Math.max(1, num(p.confirmations_required, 1)),
    tx_hash: isValidTxHash(p.tx_hash) ? String(p.tx_hash) : null,
    explorer_tx_url: httpsOrNull(p.explorer_tx_url),
    failure_reason: str(p.failure_reason),
    paid_at: str(p.paid_at),
  };
};

/** สถานะใบเสนอยอด (server ตรวจบนเชนให้ทุกครั้ง — เรียกได้ทุก 5 วินาทีขณะเปิดหน้าจ่าย) */
export const getTpixPayment = async (reference: string): Promise<ApiResult<TpixPayment>> => {
  if (!isTpixReference(reference)) {
    return { success: false, code: 'NOT_FOUND', status: 404, message: 'ไม่พบรายการชำระเงิน' };
  }
  const res = await apiGet<unknown>(`/tpix/payments/${reference}`, undefined, { fallbackMessage: 'เช็คสถานะไม่สำเร็จ ลองใหม่อีกครั้งนะ' });
  if (!res.success) return res;
  const payment = normalizeTpixPayment(res.data);
  if (!payment) {
    return { success: false, code: 'INVALID_RESPONSE', status: res.status, message: 'ข้อมูลรายการชำระเงินไม่ครบ ลองใหม่อีกครั้งนะ' };
  }
  return { ...res, data: payment };
};

/** ผลการส่งเลขธุรกรรม */
export type TpixSubmitOutcome = 'paid' | 'confirming' | 'pending' | 'rejected' | 'credited_wallet' | 'late' | string;

export interface TpixSubmitResult {
  result: TpixSubmitOutcome;
  /** ข้อความภาษาไทยจาก server */
  message: string;
  payment: TpixPayment | null;
}

/**
 * ส่งเลขธุรกรรมให้ server ตรวจทันที
 * ถูกปฏิเสธ (422) → success:false + message ภาษาไทย + data = ใบเสนอยอดล่าสุด (ถ้ามี)
 */
export const submitTpixTx = async (reference: string, txHash: string): Promise<ApiResult<TpixSubmitResult>> => {
  if (!isTpixReference(reference)) {
    return { success: false, code: 'NOT_FOUND', status: 404, message: 'ไม่พบรายการชำระเงิน' };
  }
  const hash = txHash.trim();
  if (!isValidTxHash(hash)) {
    return { success: false, code: 'VALIDATION_ERROR', status: 422, message: 'เลขธุรกรรมต้องขึ้นต้นด้วย 0x ตามด้วยตัวอักษร 64 ตัว' };
  }
  const res = await apiPost<unknown>(`/tpix/payments/${reference}/submit`, { tx_hash: hash }, {
    fallbackMessage: 'ส่งเลขธุรกรรมไม่สำเร็จ ลองใหม่อีกครั้งนะ',
  });
  if (!res.success) {
    return { ...res, data: normalizeTpixPayment(res.data) };
  }
  const result = typeof res.meta?.result === 'string' ? res.meta.result : 'pending';
  return {
    ...res,
    data: {
      result,
      message: res.message,
      payment: normalizeTpixPayment(res.data),
    },
  };
};

// =====================================================
// แคชในหน่วยความจำ — ส่งใบเสนอยอดจากหน้าชำระเงินไปหน้าจ่ายทันที (ไม่ต้องรอโหลดซ้ำ)
// =====================================================

/** รายการในชุดคำสั่งซื้อเดียวกัน (สั่งหลายร้าน = หลายใบเสนอยอด) */
export interface TpixGroupEntry {
  order_id: number;
  order_number: string | null;
  store_name: string | null;
  amount_thb: number;
  /** null = ออกยอดไม่สำเร็จ (กดจ่ายใหม่ได้จากหน้าคำสั่งซื้อ) */
  reference: string | null;
  message: string | null;
}

const paymentCache = new Map<string, TpixPayment>();
const groupCache = new Map<string, TpixGroupEntry[]>();

export const rememberTpixPayment = (payment: TpixPayment): void => {
  paymentCache.set(payment.reference, payment);
  // กันแคชโตไม่จำกัด (เก็บแค่ 30 ใบล่าสุด)
  if (paymentCache.size > 30) {
    const oldest = paymentCache.keys().next().value;
    if (oldest) paymentCache.delete(oldest);
  }
};

export const peekTpixPayment = (reference: string | null | undefined): TpixPayment | null =>
  reference ? paymentCache.get(reference) ?? null : null;

export const rememberTpixGroup = (groupId: string, entries: TpixGroupEntry[]): void => {
  groupCache.set(groupId, entries);
  if (groupCache.size > 10) {
    const oldest = groupCache.keys().next().value;
    if (oldest) groupCache.delete(oldest);
  }
};

export const peekTpixGroup = (groupId: string | null | undefined): TpixGroupEntry[] =>
  groupId ? groupCache.get(groupId) ?? [] : [];

/** แทนรหัสอ้างอิงเดิมในชุดด้วยใบใหม่ (ออกยอดใหม่หลังหมดเวลา) */
export const replaceTpixGroupReference = (groupId: string | null | undefined, orderId: number, reference: string): void => {
  if (!groupId) return;
  const entries = groupCache.get(groupId);
  if (!entries) return;
  groupCache.set(
    groupId,
    entries.map((e) => (e.order_id === orderId ? { ...e, reference, message: null } : e))
  );
};
