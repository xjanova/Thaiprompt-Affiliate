/**
 * เข้าสู่ระบบแอปผ่านหน้าเว็บ (PKCE) ด้วย LINE / Facebook / Google — ตัวช่วยฝั่งแอป
 *
 * เส้นทาง: แอปขอ login_url (/api/v1/auth/mobile/init) → เปิดเบราว์เซอร์ในแอป → ล็อกอินบนเว็บ
 * → เว็บพากลับ thaiprompt://auth?code=..&state=.. → แอปแลก code + code_verifier เป็น token
 *
 * code เดียวกันอาจเข้ามาสองทางพร้อมกัน (Android: ทั้ง auth session และ deep link ที่เปิดหน้า /auth)
 * → ต้อง claimWebAuthCode() ก่อนแลกเสมอ ใครได้ก่อนคนนั้นแลก อีกทางเงียบ
 */

/** ผู้ให้บริการที่เซิร์ฟเวอร์รับ (ตรงกับ MobileAppLogin::PROVIDERS) */
export type SocialProvider = 'line' | 'facebook' | 'google';

/** URL ที่หน้าเว็บส่งกลับเข้าแอปหลังล็อกอินเสร็จ */
export const WEB_AUTH_REDIRECT_URL = 'thaiprompt://auth';

export interface WebAuthRedirect {
  code: string | null;
  state: string | null;
  error: string | null;
}

const safeDecode = (value: string): string => {
  try {
    return decodeURIComponent(value);
  } catch {
    return value;
  }
};

/**
 * แยก code / state / error จาก thaiprompt://auth?... (ไม่พึ่ง URL polyfill ของ RN)
 *
 * @example parseWebAuthRedirect('thaiprompt://auth?code=abc&state=xyz') // { code: 'abc', state: 'xyz', error: null }
 */
export const parseWebAuthRedirect = (url: string): WebAuthRedirect => {
  const query = (url.split('#')[0].split('?')[1] ?? '').trim();
  const params: Record<string, string> = {};

  for (const pair of query.split('&')) {
    if (!pair) continue;
    const eq = pair.indexOf('=');
    const rawKey = eq === -1 ? pair : pair.slice(0, eq);
    const rawValue = eq === -1 ? '' : pair.slice(eq + 1);
    // หน้าเว็บรุ่นเก่าส่ง "&amp;state=" มาในสคริปต์เด้งอัตโนมัติ → ถือเป็นคีย์ state ตัวเดียวกัน
    const key = safeDecode(rawKey).replace(/^amp;/, '');
    if (!(key in params)) params[key] = safeDecode(rawValue.replace(/\+/g, ' '));
  }

  return {
    code: params.code || null,
    state: params.state || null,
    error: params.error || null,
  };
};

/** code ที่ถูกส่งไปแลกแล้ว — ระดับโมดูล เพราะ deep link อาจเปิดหน้าใหม่ขณะหน้าเดิมก็ได้ code ตัวเดียวกัน */
const claimedCodes = new Set<string>();

/**
 * จองสิทธิ์แลก code นี้ (คืน false ถ้ามีคนแลกไปแล้ว — ผู้เรียกต้องเงียบ ไม่แสดง error)
 */
export const claimWebAuthCode = (code: string): boolean => {
  if (!code || claimedCodes.has(code)) return false;
  claimedCodes.add(code);
  return true;
};

/**
 * หน้าเข้าสู่ระบบกำลังรอผลจาก auth session อยู่ไหม
 *
 * Android: เว็บเด้ง thaiprompt://auth → แอปได้ทั้งผลของ auth session (หน้าเข้าสู่ระบบ)
 * และ deep link ที่เปิดหน้า /auth ซ้อนขึ้นมา → ถ้ามีคนรออยู่ หน้า /auth แค่ถอยกลับ ปล่อยให้หน้าเดิมแลก
 */
let authSessionPending = false;

export const setWebAuthSessionPending = (pending: boolean): void => {
  authSessionPending = pending;
};

export const isWebAuthSessionPending = (): boolean => authSessionPending;

/** ชื่อผู้ให้บริการสำหรับข้อความภาษาไทย */
export const SOCIAL_PROVIDER_LABEL: Record<SocialProvider, string> = {
  line: 'LINE',
  facebook: 'Facebook',
  google: 'Google',
};
