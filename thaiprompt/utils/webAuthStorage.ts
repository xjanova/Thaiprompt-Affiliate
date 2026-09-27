/**
 * เก็บ PKCE code_verifier + state ของการล็อกอินผ่านเว็บ (Facebook / Google / อีเมล) ไว้ใน SecureStore
 *
 * ทำไม: ระหว่างผู้ใช้อยู่ใน Custom Tab (พิมพ์รหัส Google / ยืนยัน 2 ขั้นตอน) Android อาจฆ่าแอปทิ้ง
 *   ค่าใน Zustand (หน่วยความจำ) หายหมด → พอเว็บเด้งกลับ thaiprompt://auth แอปเปิดใหม่แล้วแลก code ไม่ได้
 *   → เก็บลงเครื่องตอนเริ่ม อ่านคืนตอนแลก ลบทิ้งหลังใช้ · เก่ากว่า 15 นาทีไม่ใช้ (ตรงกับอายุ login token ฝั่งเซิร์ฟเวอร์)
 */

import * as SecureStore from 'expo-secure-store';

/** คีย์ใน SecureStore (อักษร a-z0-9._- เท่านั้น) */
const STORAGE_KEY = 'web_auth_pending';

/** อายุสูงสุดของข้อมูลที่เก็บไว้ (มิลลิวินาที) */
export const WEB_AUTH_PENDING_MAX_AGE_MS = 15 * 60 * 1000;

export interface PendingWebAuth {
  verifier: string;
  state: string;
  createdAt: number;
}

/** บันทึก verifier + state ตอนเริ่มล็อกอิน (เขียนไม่ได้ = ใช้ค่าในหน่วยความจำอย่างเดียวตามเดิม) */
export const savePendingWebAuth = async (verifier: string, state: string): Promise<void> => {
  try {
    const value: PendingWebAuth = { verifier, state, createdAt: Date.now() };
    await SecureStore.setItemAsync(STORAGE_KEY, JSON.stringify(value));
  } catch {
    // เก็บไม่ได้ไม่ขวางการล็อกอิน
  }
};

/**
 * อ่าน verifier ที่เก็บไว้ — ต้อง state ตรง และยังไม่เก่ากว่า 15 นาที ไม่งั้นคืน null (และลบทิ้ง)
 */
export const loadPendingWebAuth = async (state: string): Promise<PendingWebAuth | null> => {
  try {
    const raw = await SecureStore.getItemAsync(STORAGE_KEY);
    if (!raw) return null;

    const parsed = JSON.parse(raw) as Partial<PendingWebAuth>;
    const valid =
      typeof parsed.verifier === 'string' &&
      typeof parsed.state === 'string' &&
      typeof parsed.createdAt === 'number' &&
      parsed.state === state &&
      Date.now() - parsed.createdAt <= WEB_AUTH_PENDING_MAX_AGE_MS;

    if (!valid) {
      // หมดอายุ / ของคนละรอบ → ไม่ใช้ (ลบเฉพาะที่หมดอายุ ของรอบอื่นที่ยังใหม่เก็บไว้)
      if (typeof parsed.createdAt !== 'number' || Date.now() - parsed.createdAt > WEB_AUTH_PENDING_MAX_AGE_MS) {
        await clearPendingWebAuth();
      }
      return null;
    }

    return parsed as PendingWebAuth;
  } catch {
    return null;
  }
};

/** ลบทิ้งหลังแลก code แล้ว (สำเร็จหรือไม่ก็ตาม) */
export const clearPendingWebAuth = async (): Promise<void> => {
  try {
    await SecureStore.deleteItemAsync(STORAGE_KEY);
  } catch {
    // ลบไม่ได้ก็หมดอายุเองใน 15 นาที
  }
};
