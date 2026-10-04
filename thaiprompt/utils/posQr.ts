/**
 * QR ชำระเงินจากเครื่อง POS ของร้าน (ส่งด้วยไรเดอร์ Thai Prompt)
 *
 * รูปแบบ: `TPPOS1.{token}` — token สุ่ม 40 ตัวอักษร (URL-safe) อายุ 15 นาที ใช้ได้ครั้งเดียว
 * ใช้ร่วมกันระหว่าง: ตัวสแกน (หน้า pos-pay / หน้าโอนเงิน) · push `pos_payment_request` · deep link `/pos-pay?token=`
 *
 * ไฟล์นี้ไม่มี dependency (ไม่ import services) เพื่อให้ utils/notificationRouting ใช้ได้และทดสอบด้วย jest ได้ง่าย
 */

export const POS_QR_PREFIX = 'TPPOS1.';

/** token ที่ยอมรับ: ตัวอักษร/ตัวเลข/ขีด/ขีดล่าง 16–128 ตัว (กันค่าแปลกปลอมหลุดเข้า URL/path ของ API) */
const TOKEN_PATTERN = /^[A-Za-z0-9_-]{16,128}$/;

/**
 * ข้อความที่สแกนได้ขึ้นต้นด้วย `TPPOS1.` หรือไม่ (ยังไม่ตรวจความถูกต้องของ token)
 *
 * @example isPosQr('TPPOS1.k3J9...') // true
 * @example isPosQr('0x1234...')      // false (wallet address)
 */
export const isPosQr = (raw: unknown): raw is string =>
  typeof raw === 'string' && raw.trim().toUpperCase().startsWith(POS_QR_PREFIX);

/**
 * ดึง token จากค่าที่สแกน / query param / push data — มีหรือไม่มี prefix `TPPOS1.` ก็ได้
 *
 * @returns token (ไม่มี prefix) หรือ null เมื่อรูปแบบไม่ถูกต้อง
 * @example parsePosToken('TPPOS1.abcDEF1234567890xyz') // 'abcDEF1234567890xyz'
 * @example parsePosToken(['abcDEF1234567890xyz'])       // 'abcDEF1234567890xyz' (param ของ expo-router เป็น array ได้)
 */
export const parsePosToken = (raw: unknown): string | null => {
  const first = Array.isArray(raw) ? raw[0] : raw;
  if (typeof first !== 'string') return null;
  let value = first.trim();
  if (value.toUpperCase().startsWith(POS_QR_PREFIX)) {
    value = value.slice(POS_QR_PREFIX.length);
  }
  return TOKEN_PATTERN.test(value) ? value : null;
};

/** path หน้าชำระเงินคำขอจากร้าน */
export const posPayPath = (token: string): string => `/pos-pay?token=${encodeURIComponent(token)}`;
