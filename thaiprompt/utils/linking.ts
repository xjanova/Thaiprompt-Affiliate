/**
 * ลิงก์ที่แอปยอมเปิด — ใช้ร่วมกันทุกจุด (deep link webview, push data.url, banner CTA)
 *
 * กติกา (PLAY-23):
 *   - URL ภายนอก: เปิดได้เฉพาะ https:// ที่ host = thaiprompt.online หรือ *.thaiprompt.online
 *   - path ภายในแอป: ต้องขึ้นต้นด้วย '/' (ไม่ใช่ '//') และอยู่ใน INTERNAL_ROUTE_PREFIXES
 *   - scheme อื่น (intent://, javascript:, file:// ฯลฯ) ปฏิเสธทั้งหมด
 */

import { APP_INFO } from '@/config/appConfig';

/** โดเมนหลักที่ไว้ใจ */
export const TRUSTED_ROOT_DOMAIN = 'thaiprompt.online';

/** ดึง host จาก https URL แบบไม่พึ่ง URL polyfill (RN บางรุ่นยังไม่รองรับ .hostname) */
const extractHttpsHost = (url: string): string | null => {
  const match = /^https:\/\/([^/?#@\s]+)(?:[/?#]|$)/i.exec(url.trim());
  if (!match) return null;
  // ตัด port ออก
  const host = match[1].toLowerCase().replace(/:\d+$/, '');
  return host || null;
};

/**
 * URL นี้เป็นเว็บของเราที่เปิดได้อย่างปลอดภัยหรือไม่
 *
 * @example isTrustedWebUrl('https://main.thaiprompt.online/taladsod') // true
 * @example isTrustedWebUrl('intent://evil') // false
 */
export const isTrustedWebUrl = (url: unknown): url is string => {
  if (typeof url !== 'string' || url.length > 2048) return false;
  const host = extractHttpsHost(url);
  if (!host) return false;
  return host === TRUSTED_ROOT_DOMAIN || host.endsWith(`.${TRUSTED_ROOT_DOMAIN}`);
};

/** สร้าง URL เว็บไซต์จาก path (ไม่มี session — ใช้เป็นทางสำรอง) */
export const buildWebsiteUrl = (path: string = '/'): string => {
  const clean = path.startsWith('/') ? path : `/${path}`;
  return `${APP_INFO.WEBSITE}${clean}`;
};

/**
 * path ภายในแอปที่ยอมให้ push / banner / deep link พาไป
 * (หน้า MLM/คริปโตถูกถอดออกจากแอปแล้ว จึงไม่อยู่ในรายการนี้)
 */
export const INTERNAL_ROUTE_PREFIXES = [
  '/(tabs)',
  '/shopping',
  '/stores',
  '/store',
  '/product',
  '/cart',
  '/checkout',
  '/addresses',
  '/orders',
  '/order',
  '/wallet',
  '/wallet-topup',
  '/wallet-withdraw',
  '/wallet-history',
  '/referral',
  '/notifications',
  '/support',
  '/settings',
  '/edit-profile',
  '/kyc',
  '/rider',
  '/rider-jobs',
  '/rider-job-detail',
  '/rider-documents',
  '/rider-earnings',
  '/taladsod',
  '/merchant',
  '/tarot',
  '/privacy',
  '/terms',
  '/agreement',
  '/login',
  '/register',
] as const;

/**
 * path นี้พาไปได้หรือไม่
 *
 * @example isAllowedInternalRoute('/order/15') // true
 * @example isAllowedInternalRoute('//evil.com') // false
 */
export const isAllowedInternalRoute = (path: unknown): path is string => {
  if (typeof path !== 'string' || path.length === 0 || path.length > 512) return false;
  if (!path.startsWith('/') || path.startsWith('//') || path.includes('..') || path.includes('\\')) {
    return false;
  }
  if (path === '/') return true;
  return INTERNAL_ROUTE_PREFIXES.some(
    (prefix) =>
      path === prefix ||
      path.startsWith(`${prefix}/`) ||
      path.startsWith(`${prefix}?`)
  );
};

/** prefix ที่ backend ยอมสร้าง web-session ให้ (ตรงกับ F-platform contract) */
export const WEB_SESSION_PREFIXES = ['/user', '/seller', '/taladsod', '/shop', '/storefront', '/wallet', '/account'] as const;

/** path นี้ขอ web-session ได้หรือไม่ */
export const isWebSessionPath = (path: string): boolean =>
  path.startsWith('/') &&
  !path.startsWith('//') &&
  WEB_SESSION_PREFIXES.some((prefix) => path === prefix || path.startsWith(`${prefix}/`) || path.startsWith(`${prefix}?`));
