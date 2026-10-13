/**
 * ความสดของยอดในหน้าชำระเงินร้านค้า (checkout.tsx) — ตัวช่วยล้วน ทดสอบด้วย jest ได้
 *
 * ยอด (quote) มาจาก server ตามชุด "ที่อยู่ · วิธีส่ง · คูปอง" ที่ใช้ขอ
 *   - เปลี่ยนตัวเลือกแล้วยังไม่ได้ยอดใหม่ = ยอดเก่า (stale) → ห้ามใช้ยืนยัน/ตัดเงิน
 *   - ขอยอดใหม่ไม่สำเร็จ = failed → ปุ่มสั่งซื้อกลายเป็น "คำนวณยอดใหม่"
 * กันเคส: โชว์ค่าส่งพัสดุแต่ server คิดค่าส่งไรเดอร์ (ยอดในกล่องยืนยันไม่ตรงกับที่ตัดเงินจริง)
 */

export interface QuoteParams {
  addressId: number | null;
  delivery: string;
  coupon: string | null;
}

/** กุญแจของชุดตัวเลือกที่ใช้ขอยอด — ยอดที่ได้ต้องมีกุญแจตรงกับตัวเลือกปัจจุบันถึงจะใช้ได้ */
export const quoteParamsKey = (p: QuoteParams): string =>
  JSON.stringify([p.addressId ?? null, p.delivery, p.coupon ?? null]);

/**
 * - fresh   ยอดตรงกับตัวเลือกปัจจุบัน สั่งซื้อได้
 * - loading กำลังขอยอด
 * - stale   ตัวเลือกเปลี่ยนแล้ว ยอดบนจอยังเป็นของชุดเดิม (รอขอใหม่)
 * - failed  ขอยอดล่าสุดไม่สำเร็จ ต้องกดคำนวณใหม่
 */
export type QuoteFreshness = 'fresh' | 'loading' | 'stale' | 'failed';

export interface QuoteFreshnessInput {
  hasQuote: boolean;
  loading: boolean;
  error: string | null;
  /** กุญแจของชุดตัวเลือกที่ได้ยอดบนจอมา (null = ยังไม่เคยได้ยอด) */
  quoteKey: string | null;
  /** กุญแจของตัวเลือกปัจจุบัน */
  currentKey: string;
}

export const quoteFreshness = ({ hasQuote, loading, error, quoteKey, currentKey }: QuoteFreshnessInput): QuoteFreshness => {
  if (loading) return 'loading';
  if (error) return 'failed';
  if (!hasQuote) return 'loading';
  return quoteKey === currentKey ? 'fresh' : 'stale';
};

/** สั่งซื้อ/ยืนยันตัดเงินได้เฉพาะยอดที่สดเท่านั้น */
export const canConfirmWithQuote = (state: QuoteFreshness): boolean => state === 'fresh';
