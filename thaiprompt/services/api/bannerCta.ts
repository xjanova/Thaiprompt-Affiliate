/**
 * แปลงปลายทาง (CTA) ของแบนเนอร์จาก GET /api/v1/banners → ค่าที่แอปเปิดได้จริง
 *
 * รูปแบบที่ backend ส่งมา (MobileBanner::toAppApi)
 *   - cta_type 'screen' → cta_value เป็น path ในแอป "ไม่มี / นำหน้า" เช่น 'taladsod', 'rider', 'product/12'
 *   - cta_type 'url'    → cta_value อาจเป็น path สัมพัทธ์ ('/taladsod/start/seller') หรือ URL เต็ม
 *                         และมี cta_url (URL เต็มที่ server สร้างให้) แยกมาอีกคีย์
 *
 * ผลลัพธ์
 *   - screen → '/taladsod' (ผ่าน allowlist ของแอปเท่านั้น ไม่ผ่าน = null → ไม่แสดงปุ่ม)
 *   - url    → 'https://main.thaiprompt.online/...' (เฉพาะโดเมนของเรา ไม่ผ่าน = null)
 *
 * ไฟล์นี้ไม่มี side effect (ไม่เรียก API) — ทดสอบได้ตรงๆ
 */

import { APP_INFO } from '@/config/appConfig';
import { isAllowedInternalRoute, isTrustedWebUrl } from '@/utils/linking';

export type BannerCtaType = 'screen' | 'url';

/** scheme ใดๆ เช่น https:, intent:, javascript: */
const SCHEME = /^[a-z][a-z0-9+.-]*:/i;

const cleanText = (value: unknown): string | null =>
  typeof value === 'string' && value.trim() !== '' ? value.trim() : null;

/**
 * path ในแอปจาก cta_value ชนิด screen
 *
 * @example normalizeScreenCta('taladsod') // '/taladsod'
 * @example normalizeScreenCta('/rider') // '/rider'
 * @example normalizeScreenCta('javascript:alert(1)') // null
 */
export const normalizeScreenCta = (value: unknown): string | null => {
  const v = cleanText(value);
  if (!v || SCHEME.test(v)) return null;
  const path = `/${v.replace(/^\/+/, '')}`;
  return isAllowedInternalRoute(path) ? path : null;
};

/**
 * URL เว็บของเราจาก cta_url (ใช้ก่อน) หรือ cta_value ชนิด url
 *
 * @example normalizeUrlCta(null, '/taladsod/start/seller') // 'https://main.thaiprompt.online/taladsod/start/seller'
 * @example normalizeUrlCta('https://main.thaiprompt.online/taladsod', '/taladsod') // 'https://main.thaiprompt.online/taladsod'
 * @example normalizeUrlCta(null, 'https://evil.example') // null
 */
export const normalizeUrlCta = (ctaUrl: unknown, ctaValue: unknown): string | null => {
  for (const candidate of [ctaUrl, ctaValue]) {
    const v = cleanText(candidate);
    if (!v) continue;

    let url: string | null = null;
    if (/^https:\/\//i.test(v)) {
      url = v;
    } else if (/^http:\/\//i.test(v)) {
      // server หลัง proxy อาจสร้าง url() เป็น http — บังคับ https (ยังต้องผ่านเช็คโดเมนด้านล่าง)
      url = `https://${v.slice('http://'.length)}`;
    } else if (!v.startsWith('//') && !SCHEME.test(v)) {
      // path สัมพัทธ์ ('/taladsod/...' หรือ 'taladsod/...') → เว็บไซต์หลัก
      url = `${APP_INFO.WEBSITE}/${v.replace(/^\/+/, '')}`;
    }

    if (url && isTrustedWebUrl(url)) return url;
  }
  return null;
};

/**
 * CTA ของแถวแบนเนอร์ → { type, value } ที่แอปเปิดได้ (เปิดไม่ได้ = ทั้งคู่เป็น null → ไม่มีปุ่ม)
 */
export const resolveBannerCta = (row: {
  cta_type?: unknown;
  cta_value?: unknown;
  cta_url?: unknown;
}): { type: BannerCtaType | null; value: string | null } => {
  if (row.cta_type === 'screen') {
    const value = normalizeScreenCta(row.cta_value);
    return value ? { type: 'screen', value } : { type: null, value: null };
  }
  if (row.cta_type === 'url') {
    const value = normalizeUrlCta(row.cta_url, row.cta_value);
    return value ? { type: 'url', value } : { type: null, value: null };
  }
  return { type: null, value: null };
};
