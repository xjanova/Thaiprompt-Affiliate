/**
 * ขีดจำกัดของร้านตลาดสดตาม server (FreshMarketOptionService / FreshMarketSellerListingApiController)
 * ไฟล์นี้ไม่ import อะไรเลย — ใช้ได้ทั้งในแอปและในเทสต์
 */

export const FM_LIMITS = {
  /** กลุ่มตัวเลือกต่อสินค้า */
  MAX_GROUPS: 10,
  /** ตัวเลือกต่อกลุ่ม */
  MAX_OPTIONS_PER_GROUP: 30,
  /** รูปต่อสินค้า */
  MAX_IMAGES: 5,
  /** ราคาเพิ่มของตัวเลือกสูงสุด (บาท) */
  MAX_PRICE_DELTA: 100000,
  /** ความยาวชื่อกลุ่ม/ตัวเลือก */
  NAME_MAX: 100,
  /** ความยาวชื่อจุดขาย */
  LABEL_MAX: 150,
} as const;
