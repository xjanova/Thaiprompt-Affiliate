/**
 * แปลงตัวเลขจากช่องกรอกของฟอร์มหลังร้าน (รองรับจุลภาค/ช่องว่าง/เครื่องหมาย ฿)
 *
 * ใช้ตรวจแบบ inline ก่อนส่ง — server ตรวจซ้ำอีกชั้นเสมอ
 */

/** เงินบาท ทศนิยมไม่เกิน 2 ตำแหน่ง · ว่าง = null · ไม่ถูกต้อง = NaN */
export const parseMoney = (textValue: string): number | null => {
  const clean = textValue.replace(/[,\s฿]/g, '');
  if (clean === '') return null;
  if (!/^\d+(\.\d{1,2})?$/.test(clean)) return Number.NaN;
  const n = Number(clean);
  return Number.isFinite(n) ? n : Number.NaN;
};

/** จำนวนเต็มไม่ติดลบ · ว่าง = null · ไม่ถูกต้อง = NaN */
export const parseInteger = (textValue: string): number | null => {
  const clean = textValue.replace(/[,\s]/g, '');
  if (clean === '') return null;
  if (!/^\d+$/.test(clean)) return Number.NaN;
  const n = Number(clean);
  return Number.isSafeInteger(n) ? n : Number.NaN;
};

/** ตัวเลขจาก server → ข้อความในช่องกรอก (ไม่มีค่า = ว่าง · ไม่ใส่ทศนิยม .00 ที่ไม่จำเป็น) */
export const formatInputNumber = (value: number | null | undefined): string => {
  if (value === null || value === undefined || !Number.isFinite(value)) return '';
  return Number.isInteger(value) ? String(value) : String(Math.round(value * 100) / 100);
};
