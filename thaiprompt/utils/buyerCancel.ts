/**
 * ปุ่มยกเลิกฝั่งผู้ซื้อ (ออเดอร์ร้านค้า + ตลาดสด) — ตัวช่วยล้วน ทดสอบด้วย jest ได้
 *
 * สัญญากับ backend: payload ออเดอร์มี
 *   - buyer_can_cancel  (bool)        true = ผู้ซื้อยกเลิกเองได้ตอนนี้ (รวมกรณีปกติ เช่น ร้านยังไม่รับ)
 *   - buyer_cancel_note (string|null) มีค่าเฉพาะเมื่อยกเลิกได้ "เพราะเกินเวลา"
 *     (ร้านเตรียมของช้าเกิน 45 นาที / หาไรเดอร์ไม่ได้เกิน 30 นาที) — null = ยกเลิกได้ตามปกติ ไม่ใช่ล่าช้า
 *   ⚠️ (2026-10-13) ห้ามถือว่า buyer_can_cancel = ล่าช้า — ออเดอร์ที่เพิ่งสั่งก็ true จะขึ้นแถบ "ล่าช้า" ผิดๆ
 * ไม่มีฟิลด์ (server รุ่นเก่า) = ทำงานเหมือนเดิมทุกอย่าง (ยกเลิกได้ตามสิทธิ์ปกติของแต่ละระบบเท่านั้น)
 */

export interface BuyerCancelView {
  /** แสดงปุ่มยกเลิก */
  show: boolean;
  /** ยกเลิกได้เพราะออเดอร์ล่าช้า (แสดงหมายเหตุ + ปุ่มเด่นขึ้น) */
  late: boolean;
  /** ข้อความอธิบาย (ภาษาไทยเสมอ) — null เมื่อไม่ใช่กรณีล่าช้า */
  note: string | null;
}

/** ข้อความสำรองเมื่อ server ไม่ส่งหมายเหตุ/ไม่ใช่ภาษาไทย */
export const LATE_CANCEL_FALLBACK_NOTE = 'ออเดอร์นี้ล่าช้ากว่าปกติ ถ้าไม่อยากรอต่อ ยกเลิกได้เลย';

/** เหตุผลยกเลิกที่เลือกให้ก่อนเมื่อยกเลิกเพราะล่าช้า (ผู้ซื้อเปลี่ยนเองได้) */
export const LATE_CANCEL_REASON = 'รอนานเกินไป';

const hasThai = (text: string): boolean => /[฀-๿]/.test(text);

/**
 * @param normalCancel สิทธิ์ยกเลิกปกติของระบบนั้น (ร้านค้า: can_cancel · ตลาดสด: allowed_actions มี cancel)
 * @param order        ออเดอร์ (อ่านเฉพาะ buyer_can_cancel / buyer_cancel_note)
 */
export const buyerCancelView = (
  normalCancel: boolean,
  order: { buyer_can_cancel?: unknown; buyer_cancel_note?: unknown } | null | undefined
): BuyerCancelView => {
  const serverAllows = order?.buyer_can_cancel === true;
  const raw = typeof order?.buyer_cancel_note === 'string' ? order.buyer_cancel_note.trim() : '';
  // ล่าช้า = เซิร์ฟเวอร์ให้ยกเลิก "และ" ส่งเหตุผลเกินเวลามา
  const late = serverAllows && raw.length > 0;
  if (!late) return { show: normalCancel || serverAllows, late: false, note: null };
  return { show: true, late: true, note: hasThai(raw) ? raw.slice(0, 300) : LATE_CANCEL_FALLBACK_NOTE };
};
