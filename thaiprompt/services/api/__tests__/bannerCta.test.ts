/**
 * ทดสอบการแปลง CTA ของแบนเนอร์ด้วยแถวข้อมูลจริงจาก GET /api/v1/banners
 * (รูปแบบเดียวกับ MobileBanner::toAppApi + แบนเนอร์เปิดตัว 4 อันที่ migration seed ไว้)
 *
 * รัน: npx jest services/api/__tests__/bannerCta.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { normalizeScreenCta, normalizeUrlCta, resolveBannerCta } from '../bannerCta';

const WEB = 'https://main.thaiprompt.online';

/** แถวจริงจาก GET /api/v1/banners?placement=... (seed_launch_app_campaign_banners) */
const API_ROWS = {
  home: {
    id: 11,
    title: 'เปิดตัวตลาดสดไทยพร้อม',
    subtitle: 'ผัดกะเพราร้อนๆ ส่งถึงมือ สั่งเลย',
    image_url: `${WEB}/images/taladsod/banner-market.webp`,
    cta_label: 'สั่งเลย',
    cta_type: 'screen',
    cta_value: 'taladsod',
    cta_url: null,
    placement: 'home',
    audience: 'all',
    starts_at: null,
    ends_at: null,
    sort: 1,
  },
  merchant: {
    id: 13,
    title: 'เปิดร้านฟรี ไม่มีค่า GP ช่วงเปิดตัว',
    subtitle: 'รถเข็น ตลาดนัด ร้านเล็กก็ขายได้',
    image_url: `${WEB}/images/taladsod/banner-merchant.webp`,
    cta_label: 'เปิดร้านเลย',
    cta_type: 'url',
    cta_value: '/taladsod/start/seller',
    cta_url: `${WEB}/taladsod/start/seller`,
    placement: 'merchant',
    audience: 'all',
    starts_at: null,
    ends_at: null,
    sort: 1,
  },
  rider: {
    id: 14,
    title: 'มาเป็นไรเดอร์ รับงานใกล้บ้าน',
    subtitle: 'ค่าส่งเข้ากระเป๋าทันทีที่ส่งสำเร็จ',
    image_url: `${WEB}/images/taladsod/banner-rider.webp`,
    cta_label: 'สมัครไรเดอร์',
    cta_type: 'screen',
    cta_value: 'rider',
    cta_url: null,
    placement: 'rider',
    audience: 'all',
    starts_at: null,
    ends_at: null,
    sort: 1,
  },
} as const;

describe('resolveBannerCta — แถวจริงจาก server', () => {
  it('screen ไม่มี / นำหน้า → path ในแอปที่เปิดได้', () => {
    expect(resolveBannerCta(API_ROWS.home)).toEqual({ type: 'screen', value: '/taladsod' });
    expect(resolveBannerCta(API_ROWS.rider)).toEqual({ type: 'screen', value: '/rider' });
  });

  it('url ใช้ cta_url (URL เต็ม) ก่อน cta_value', () => {
    expect(resolveBannerCta(API_ROWS.merchant)).toEqual({ type: 'url', value: `${WEB}/taladsod/start/seller` });
  });

  it('url ไม่มี cta_url → เติมเว็บไซต์หลักให้ path สัมพัทธ์', () => {
    expect(resolveBannerCta({ ...API_ROWS.merchant, cta_url: null })).toEqual({
      type: 'url',
      value: `${WEB}/taladsod/start/seller`,
    });
  });

  it('แบนเนอร์ไม่มี CTA → ไม่มีปุ่ม', () => {
    expect(resolveBannerCta({ cta_type: null, cta_value: null, cta_url: null })).toEqual({ type: null, value: null });
  });
});

describe('normalizeScreenCta', () => {
  it('รับได้ทั้งแบบมีและไม่มี / นำหน้า', () => {
    expect(normalizeScreenCta('taladsod')).toBe('/taladsod');
    expect(normalizeScreenCta('/taladsod')).toBe('/taladsod');
    expect(normalizeScreenCta('product/12')).toBe('/product/12');
  });

  it('หน้าที่ไม่อยู่ใน allowlist หรือเป็น scheme อื่น → null', () => {
    expect(normalizeScreenCta('mlm/genealogy')).toBeNull();
    expect(normalizeScreenCta('javascript:alert(1)')).toBeNull();
    expect(normalizeScreenCta('//evil.example')).toBeNull();
    expect(normalizeScreenCta('')).toBeNull();
    expect(normalizeScreenCta(null)).toBeNull();
  });
});

describe('normalizeUrlCta', () => {
  it('เปิดได้เฉพาะโดเมนของเรา', () => {
    expect(normalizeUrlCta('https://evil.example/x', null)).toBeNull();
    expect(normalizeUrlCta(null, 'https://evil.example/x')).toBeNull();
    expect(normalizeUrlCta(null, 'intent://scan')).toBeNull();
    expect(normalizeUrlCta(null, '//evil.example')).toBeNull();
  });

  it('http ของโดเมนเรา → บังคับ https', () => {
    expect(normalizeUrlCta('http://main.thaiprompt.online/taladsod', null)).toBe(`${WEB}/taladsod`);
  });

  it('cta_url ใช้ไม่ได้ → ลอง cta_value ต่อ', () => {
    expect(normalizeUrlCta('https://evil.example/x', '/taladsod')).toBe(`${WEB}/taladsod`);
  });
});
