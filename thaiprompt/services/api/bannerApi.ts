/**
 * Banner API — แบนเนอร์แคมเปญที่แอดมินจัดการ (app_banners)
 *
 * GET /api/v1/banners?placement=home|taladsod|rider|merchant
 *
 * - รูปแบนเนอร์ไม่มีตัวหนังสือ แอปวางข้อความไทยทับเอง (title / subtitle / cta_label)
 * - endpoint ยังไม่พร้อม / ล้ม / ไม่มีแบนเนอร์ → ใช้รูปที่แนบมากับแอป (assets/images/taladsod/banner-*.webp)
 * - ไม่ดึง /mobile/banners เดิมมาใช้แทน: ระบบเดิมอาจมีแบนเนอร์เนื้อหา MLM ที่ผิดนโยบาย Google Play
 */

import type { ImageSourcePropType } from 'react-native';
import { APP_INFO } from '@/config/appConfig';
import { apiGet } from './client';
import { resolveBannerCta, type BannerCtaType } from './bannerCta';

export type { BannerCtaType };
export type BannerPlacement = 'home' | 'taladsod' | 'rider' | 'merchant';

export interface AppBanner {
  id: number | string;
  title: string;
  subtitle: string | null;
  /** URL เต็ม หรือรูปในแอป (require) */
  image: string | ImageSourcePropType;
  cta_label: string | null;
  /** screen = path ในแอป (เช่น /taladsod) · url = ลิงก์เว็บของเรา */
  cta_type: BannerCtaType | null;
  /** screen = '/taladsod' (มี / นำหน้าเสมอ) · url = 'https://...' เต็ม — เปิดไม่ได้ = null (ไม่แสดงปุ่ม) */
  cta_value: string | null;
  placement: BannerPlacement;
  /** true = แบนเนอร์สำรองที่แนบมากับแอป */
  is_fallback: boolean;
}

// =====================================================
// แบนเนอร์สำรองในแอป
// =====================================================

const IMG_MARKET = require('@/assets/images/taladsod/banner-market.webp');
const IMG_RIDER = require('@/assets/images/taladsod/banner-rider.webp');
const IMG_MERCHANT = require('@/assets/images/taladsod/banner-merchant.webp');
const IMG_KRAPAO = require('@/assets/images/taladsod/krapao-hero.webp');

const fallback = (
  id: string,
  placement: BannerPlacement,
  image: ImageSourcePropType,
  title: string,
  subtitle: string,
  ctaLabel: string,
  ctaValue: string
): AppBanner => ({
  id,
  title,
  subtitle,
  image,
  cta_label: ctaLabel,
  cta_type: 'screen',
  cta_value: ctaValue,
  placement,
  is_fallback: true,
});

export const FALLBACK_BANNERS: Record<BannerPlacement, AppBanner[]> = {
  home: [
    fallback('fb-home-market', 'home', IMG_MARKET, 'ตลาดสดใกล้บ้าน', 'ของสด อาหารร้อนๆ ส่งถึงหน้าบ้าน', 'สั่งเลย', '/taladsod'),
    fallback('fb-home-rider', 'home', IMG_RIDER, 'มาเป็นไรเดอร์กับเรา', 'รับงานส่งใกล้บ้าน เลือกเวลาได้เอง', 'ดูรายละเอียด', '/rider'),
    fallback('fb-home-merchant', 'home', IMG_MERCHANT, 'เปิดร้านออนไลน์', 'ขายของง่าย มีไรเดอร์ช่วยส่ง', 'เริ่มต้นขาย', '/merchant'),
  ],
  taladsod: [
    fallback('fb-taladsod-krapao', 'taladsod', IMG_KRAPAO, 'ผัดกะเพราราดข้าว', 'เลือกเนื้อสัตว์ได้ เพิ่มไข่ดาวได้', 'สั่งเลย', '/taladsod'),
    fallback('fb-taladsod-market', 'taladsod', IMG_MARKET, 'ตลาดสดใกล้บ้าน', 'ของสดทุกวันจากร้านในชุมชน', 'เลือกร้าน', '/taladsod'),
  ],
  rider: [
    fallback('fb-rider', 'rider', IMG_RIDER, 'พร้อมออกวิ่งหรือยัง', 'เปิดรับงานแล้วรอออเดอร์ใกล้คุณ', 'ดูงาน', '/rider'),
  ],
  merchant: [
    fallback('fb-merchant', 'merchant', IMG_MERCHANT, 'ร้านของฉัน', 'ดูออเดอร์ใหม่และจัดส่งได้ในที่เดียว', 'ดูออเดอร์', '/merchant'),
  ],
};

// =====================================================
// ดึงจาก server + cache
// =====================================================

const CACHE_MS = 5 * 60 * 1000;
const cache = new Map<BannerPlacement, { at: number; items: AppBanner[] }>();
/** endpoint ยังไม่มีบน server (404) → ไม่ยิงซ้ำจนกว่าจะเปิดแอปใหม่ */
let endpointMissing = false;

const absoluteUrl = (value: unknown): string | null => {
  if (typeof value !== 'string' || value.trim() === '') return null;
  const v = value.trim();
  if (/^https:\/\//i.test(v)) return v;
  if (v.startsWith('/')) return `${APP_INFO.WEBSITE}${v}`;
  return null;
};

const text = (value: unknown): string | null =>
  typeof value === 'string' && value.trim() !== '' ? value.trim() : null;

/**
 * แปลงแถวจาก API → AppBanner (แถวที่ไม่มีรูปถูกตัดทิ้ง)
 *
 * CTA: server เก็บ screen แบบไม่มี '/' นำหน้า และส่ง URL เต็มของชนิด url มาในคีย์ cta_url
 * → resolveBannerCta แปลงให้ openBannerTarget เปิดได้จริง (เปิดไม่ได้ = ไม่มีปุ่ม)
 */
export const normalizeBannerRow = (row: any, placement: BannerPlacement): AppBanner | null => {
  if (!row || typeof row !== 'object') return null;
  const image = absoluteUrl(row.image_url ?? row.image);
  if (!image) return null;
  const cta = resolveBannerCta(row);
  return {
    id: row.id ?? `${placement}-${image}`,
    title: text(row.title) || '',
    subtitle: text(row.subtitle),
    image,
    cta_label: cta.value ? text(row.cta_label) : null,
    cta_type: cta.type,
    cta_value: cta.value,
    placement,
    is_fallback: false,
  };
};

/**
 * แบนเนอร์ของตำแหน่งนั้น (ไม่เคยว่าง — ล้มเหลวจะได้แบนเนอร์สำรอง)
 *
 * @param placement home | taladsod | rider | merchant
 * @param force ข้าม cache (ใช้ตอน pull-to-refresh)
 */
export const getBanners = async (placement: BannerPlacement, force: boolean = false): Promise<AppBanner[]> => {
  const cached = cache.get(placement);
  if (!force && cached && Date.now() - cached.at < CACHE_MS) {
    return cached.items;
  }

  if (!endpointMissing) {
    const result = await apiGet<unknown>('/banners', { placement });
    if (result.success) {
      const rows = Array.isArray(result.data) ? result.data : [];
      const items = rows
        .map((row) => normalizeBannerRow(row, placement))
        .filter((item): item is AppBanner => item !== null);
      if (items.length > 0) {
        cache.set(placement, { at: Date.now(), items });
        return items;
      }
    } else if (result.status === 404) {
      endpointMissing = true;
    }
  }

  const items = FALLBACK_BANNERS[placement];
  cache.set(placement, { at: Date.now(), items });
  return items;
};

/** ล้าง cache แบนเนอร์ (เช่น ตอน logout) */
export const clearBannerCache = (): void => {
  cache.clear();
};
