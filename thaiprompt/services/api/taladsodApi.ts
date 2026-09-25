/**
 * Taladsod API — ตลาดสดฝั่งผู้ซื้อ (ร้านใกล้คุณ / หน้าร้าน / เมนู+ตัวเลือก / ตะกร้า / สั่งซื้อ / ติดตามไรเดอร์)
 *
 * ทุกเส้นอยู่ใต้ /api/v1/fresh-market (ยกเว้นติดตามไรเดอร์ /api/v1/orders/{source}/{id}/...)
 * ตรวจกับ routes/api.php + FreshMarketApiController / FreshMarketShopApiController /
 * FreshMarketCartApiController / DeliveryTrackingApiController แล้ว (contract W2a)
 *
 * หลักการ
 *   - ทุกฟังก์ชันคืน ApiResult<T> ไม่ throw · message เป็นภาษาไทยพร้อมแสดง
 *   - ห้ามส่งราคาไปเอง server คำนวณทั้งหมด (แอปแสดงราคาพรีวิวได้อย่างเดียว)
 *   - ตะกร้า: ทุกคำสั่งคืน "ตะกร้าทั้งใบ" (FmCart) → แทน state ทั้งก้อน
 *   - รูปจาก server อาจเป็น path สัมพัทธ์ (/images/..., /storage/...) → ใช้ fmImageUri() ก่อนแสดงเสมอ
 */

import { APP_INFO } from '@/config/appConfig';
import {
  apiDelete,
  apiGet,
  apiPost,
  apiPut,
  num,
  THAI_ERROR_MESSAGES,
  type ApiFailure,
  type ApiResult,
  type Pagination,
} from './client';

const BASE = '/fresh-market';

// =====================================================
// ข้อความไทยของ error code ตลาดสด (server ส่งข้อความไทยมาเองเป็นส่วนใหญ่ — ใช้เมื่อไม่มี)
// =====================================================

export const TALADSOD_ERROR_MESSAGES: Record<string, string> = {
  SHOP_CLOSED: 'ร้านปิดอยู่ตอนนี้ กดติดตามไว้ ร้านเปิดเมื่อไหร่จะบอกทันที',
  SHOP_NOT_FOUND: 'ไม่พบร้านนี้ ร้านอาจปิดตัวไปแล้ว',
  SELLER_NOT_FOUND: 'ไม่พบร้านนี้ ร้านอาจปิดตัวไปแล้ว',
  SHOP_UNAVAILABLE: 'ร้านนี้ปิดรับออเดอร์ชั่วคราว',
  CANNOT_FOLLOW_OWN_SHOP: 'ติดตามร้านของตัวเองไม่ได้นะ',
  LISTING_NOT_FOUND: 'ไม่พบเมนูนี้ อาจปิดขายไปแล้ว',
  LISTING_UNAVAILABLE: 'เมนูนี้ปิดขายชั่วคราว',
  CART_CHANGED: 'ตะกร้าเพิ่งเปลี่ยน ตรวจรายการอีกครั้งแล้วค่อยสั่งนะ',
  MIXED_SELLERS: 'สั่งได้ทีละร้านนะ',
  TOO_MANY_ITEMS: 'ออเดอร์เดียวสั่งได้ไม่เกิน 30 รายการ',
  INVALID_ITEM: 'มีรายการที่สั่งไม่ได้ ตรวจตะกร้าอีกครั้งนะ',
  INVALID_QUANTITY: 'จำนวนไม่ถูกต้อง',
  INVALID_OPTION: 'ตัวเลือกไม่ถูกต้อง เลือกใหม่อีกครั้งนะ',
  OPTION_REQUIRED: 'ยังเลือกตัวเลือกที่จำเป็นไม่ครบ',
  OPTION_LIMIT: 'เลือกตัวเลือกเกินจำนวนที่กำหนด',
  OPTION_UNAVAILABLE: 'ตัวเลือกนี้หมดชั่วคราว ลองเลือกอย่างอื่นนะ',
  PAYMENT_METHOD_DISABLED: 'วิธีชำระเงินนี้ปิดอยู่ชั่วคราว เลือกวิธีอื่นนะ',
  JOB_NOT_ACTIVE: 'ตอนนี้ยังไม่มีไรเดอร์กำลังวิ่งงานนี้',
  CART_EMPTY: 'ตะกร้าของร้านนี้ว่างแล้ว',
};

/** แทนข้อความกลาง ("เกิดข้อผิดพลาด...") ด้วยข้อความเฉพาะของตลาดสด */
const withThai = async <T>(promise: Promise<ApiResult<T>>): Promise<ApiResult<T>> => {
  const result = await promise;
  if (result.success) return result;
  const local = TALADSOD_ERROR_MESSAGES[result.code];
  const generic =
    result.message === THAI_ERROR_MESSAGES.UNKNOWN_ERROR ||
    result.message === THAI_ERROR_MESSAGES.CONFLICT ||
    result.message === THAI_ERROR_MESSAGES.NOT_FOUND ||
    result.message === THAI_ERROR_MESSAGES.VALIDATION_ERROR;
  if (local && generic) {
    return { ...result, message: local } as ApiFailure;
  }
  return result;
};

// =====================================================
// ตัวช่วย
// =====================================================

/**
 * แปลง URL รูปจาก server ให้เปิดได้ในแอป
 * - '/images/..', '/storage/..' → https://main.thaiprompt.online/images/..
 * - https://... → ใช้ตามเดิม · http:// → บังคับ https (Android บล็อก cleartext)
 * - อื่นๆ → null
 */
export const fmImageUri = (url: unknown): string | null => {
  if (typeof url !== 'string') return null;
  const value = url.trim();
  if (!value || value.length > 2048) return null;
  if (/^https:\/\//i.test(value)) return value;
  if (/^http:\/\//i.test(value)) return `https://${value.slice(7)}`;
  if (value.startsWith('//')) return `https:${value}`;
  if (value.startsWith('/')) return `${APP_INFO.WEBSITE}${value}`;
  if (/^(storage|images)\//i.test(value)) return `${APP_INFO.WEBSITE}/${value}`;
  return null;
};

const str = (value: unknown): string | null => (typeof value === 'string' && value.length > 0 ? value : null);
const nullableNum = (value: unknown): number | null =>
  value === null || value === undefined || value === '' ? null : Number.isFinite(Number(value)) ? Number(value) : null;
const bool = (value: unknown): boolean => value === true || value === 1 || value === '1';

/** พิกัดที่ใช้ได้จริง (ไม่ใช่ null / 0,0 / นอกช่วง) */
export const isUsableCoord = (lat: unknown, lng: unknown): boolean => {
  const a = Number(lat);
  const b = Number(lng);
  return (
    lat !== null &&
    lng !== null &&
    Number.isFinite(a) &&
    Number.isFinite(b) &&
    Math.abs(a) <= 90 &&
    Math.abs(b) <= 180 &&
    !(a === 0 && b === 0)
  );
};

// =====================================================
// ชนิดข้อมูล: ร้าน + สถานะเปิด/ปิด
// =====================================================

export interface FmShopLocation {
  latitude: number;
  longitude: number;
  label: string | null;
  updated_at: string | null;
  is_live: boolean;
  source: 'live' | 'pinned' | 'fixed' | 'last_known' | string;
}

export interface FmPresence {
  is_mobile: boolean;
  is_open: boolean;
  status: 'open' | 'closed' | string;
  status_text: string;
  closed_message: string | null;
  can_order: boolean;
  opened_at: string | null;
  closes_at: string | null;
  location_label: string | null;
  live_location_sharing: boolean;
  location_updated_at: string | null;
  location: FmShopLocation | null;
}

export interface FmShopCard {
  id: number;
  shop_name: string;
  shop_image: string | null;
  rating_average: number;
  rating_count: number;
  is_verified: boolean;
  is_mobile: boolean;
  is_open: boolean;
  is_following: boolean;
  presence: FmPresence;
}

export interface FmTopItem {
  id: number;
  slug: string | null;
  title: string;
  price: number;
  unit: string | null;
  image_url: string | null;
}

export interface FmNearbyShop extends FmShopCard {
  distance_km: number;
  location: FmShopLocation | null;
  listings_count: number;
  top_items: FmTopItem[];
}

export interface FmShopListing {
  id: number;
  slug: string | null;
  title: string;
  description: string | null;
  price: number;
  compare_at_price: number | null;
  unit: string | null;
  main_image_url: string | null;
  is_organic: boolean;
  category: string | null;
  track_stock: boolean;
  max_order_quantity: number;
  has_options: boolean;
  can_order: boolean;
}

export interface FmShopDetail extends FmShopCard {
  shop_description: string | null;
  total_sales: number;
  province: string | null;
  district: string | null;
  address: string | null;
  latitude: number | null;
  longitude: number | null;
  followers_count: number;
  can_order: boolean;
  closed_message: string | null;
  location_poll_seconds: number;
  is_owner: boolean;
  listings: FmShopListing[];
}

export interface FmShopLocationPoll {
  shop_id: number;
  is_open: boolean;
  is_mobile: boolean;
  location: FmShopLocation | null;
  closes_at: string | null;
  closed_message: string | null;
  poll_interval_seconds: number;
  server_time: string | null;
}

export interface FmFollowResult {
  shop_id: number;
  is_following: boolean;
  notify?: boolean;
  followers_count: number;
}

export interface FmFollowedShop extends FmShopCard {
  notify: boolean;
  followed_at: string | null;
}

const normalizeLocation = (raw: any): FmShopLocation | null => {
  if (!raw || typeof raw !== 'object' || !isUsableCoord(raw.latitude, raw.longitude)) return null;
  return {
    latitude: Number(raw.latitude),
    longitude: Number(raw.longitude),
    label: str(raw.label),
    updated_at: str(raw.updated_at),
    is_live: bool(raw.is_live),
    source: str(raw.source) || 'fixed',
  };
};

const normalizePresence = (raw: any, fallbackOpen: boolean, fallbackMobile: boolean): FmPresence => {
  const p = raw && typeof raw === 'object' ? raw : {};
  const open = typeof p.is_open === 'boolean' ? p.is_open : fallbackOpen;
  return {
    is_mobile: typeof p.is_mobile === 'boolean' ? p.is_mobile : fallbackMobile,
    is_open: open,
    status: str(p.status) || (open ? 'open' : 'closed'),
    status_text: str(p.status_text) || (open ? 'เปิดอยู่' : 'ปิดอยู่'),
    closed_message: str(p.closed_message),
    can_order: typeof p.can_order === 'boolean' ? p.can_order : open,
    opened_at: str(p.opened_at),
    closes_at: str(p.closes_at),
    location_label: str(p.location_label),
    live_location_sharing: bool(p.live_location_sharing),
    location_updated_at: str(p.location_updated_at),
    location: normalizeLocation(p.location),
  };
};

export const normalizeShopCard = (raw: any): FmShopCard => {
  const isOpen = bool(raw?.is_open);
  const isMobile = bool(raw?.is_mobile);
  return {
    id: num(raw?.id),
    shop_name: str(raw?.shop_name) || 'ร้านตลาดสด',
    shop_image: str(raw?.shop_image),
    rating_average: num(raw?.rating_average),
    rating_count: num(raw?.rating_count),
    is_verified: bool(raw?.is_verified),
    is_mobile: isMobile,
    is_open: isOpen,
    is_following: bool(raw?.is_following),
    presence: normalizePresence(raw?.presence, isOpen, isMobile),
  };
};

const normalizeShopListing = (raw: any): FmShopListing => ({
  id: num(raw?.id),
  slug: str(raw?.slug),
  title: str(raw?.title) || 'เมนู',
  description: str(raw?.description),
  price: num(raw?.price),
  compare_at_price: nullableNum(raw?.compare_at_price),
  unit: str(raw?.unit),
  main_image_url: str(raw?.main_image_url),
  is_organic: bool(raw?.is_organic),
  category: typeof raw?.category === 'string' ? raw.category : str(raw?.category?.name),
  track_stock: bool(raw?.track_stock),
  max_order_quantity: Math.max(1, num(raw?.max_order_quantity, 99)),
  has_options: bool(raw?.has_options),
  can_order: raw?.can_order !== false,
});

// =====================================================
// ชนิดข้อมูล: เมนู + ตัวเลือก
// =====================================================

export interface FmCategory {
  id: number;
  name: string;
  slug: string;
  icon: string | null;
  image_url: string | null;
  children: Array<{ id: number; name: string; slug: string; icon: string | null }>;
}

export interface FmListingSeller {
  id: number;
  shop_name: string;
  rating_average: number;
  is_mobile: boolean;
  is_open: boolean | null;
  closed_message: string | null;
  location_label: string | null;
}

export interface FmListingSummary {
  id: number;
  slug: string | null;
  title: string;
  description: string | null;
  price: number;
  compare_at_price: number | null;
  unit: string | null;
  quantity_available: number;
  main_image_url: string | null;
  images: string[];
  is_organic: boolean;
  is_featured: boolean;
  freshness_level: string | null;
  latitude: number | null;
  longitude: number | null;
  distance_km: number | null;
  /** null = ไม่ทราบ (ไม่ได้โหลดข้อมูลร้าน) */
  shop_is_open: boolean | null;
  can_order: boolean | null;
  track_stock: boolean;
  max_order_quantity: number;
  /** null = ไม่ทราบ → เปิดหน้ารายละเอียด */
  has_options: boolean | null;
  seller: FmListingSeller | null;
  category: { id: number; name: string; icon: string | null } | null;
}

export interface FmOption {
  id: number;
  group_id: number;
  name: string;
  price_delta: number;
  image_url: string | null;
  is_available: boolean;
  sort_order: number;
}

export interface FmOptionGroup {
  id: number;
  name: string;
  selection_type: 'single' | 'multi';
  is_required: boolean;
  min_select: number;
  /** null = ไม่จำกัด */
  max_select: number | null;
  rule_label: string | null;
  sort_order: number;
  options: FmOption[];
}

export interface FmListingDetailSeller extends FmListingSeller {
  shop_description: string | null;
  shop_image: string | null;
  rating_count: number;
  total_sales: number;
  latitude: number | null;
  longitude: number | null;
  province: string | null;
  is_verified: boolean;
  user_id: number | null;
  presence: FmPresence;
}

export interface FmListingDetail extends Omit<FmListingSummary, 'seller'> {
  tags: string[];
  delivery_radius_km: number | null;
  is_available_for_purchase: boolean;
  option_groups: FmOptionGroup[];
  seller: FmListingDetailSeller | null;
}

export interface FmRelatedListing {
  id: number;
  slug: string | null;
  title: string;
  price: number;
  unit: string | null;
  main_image_url: string | null;
  shop_name: string | null;
}

const normalizeListingSeller = (raw: any): FmListingSeller | null => {
  if (!raw || typeof raw !== 'object') return null;
  return {
    id: num(raw.id),
    shop_name: str(raw.shop_name) || 'ร้านตลาดสด',
    rating_average: num(raw.rating_average),
    is_mobile: bool(raw.is_mobile),
    is_open: typeof raw.is_open === 'boolean' ? raw.is_open : null,
    closed_message: str(raw.closed_message),
    location_label: str(raw.location_label),
  };
};

export const normalizeListingSummary = (raw: any): FmListingSummary => ({
  id: num(raw?.id),
  slug: str(raw?.slug),
  title: str(raw?.title) || 'เมนู',
  description: str(raw?.description),
  price: num(raw?.price),
  compare_at_price: nullableNum(raw?.compare_at_price),
  unit: str(raw?.unit),
  quantity_available: num(raw?.quantity_available),
  main_image_url: str(raw?.main_image_url),
  images: Array.isArray(raw?.images) ? raw.images.filter((x: unknown) => typeof x === 'string' && x) : [],
  is_organic: bool(raw?.is_organic),
  is_featured: bool(raw?.is_featured),
  freshness_level: str(raw?.freshness_level),
  latitude: nullableNum(raw?.latitude),
  longitude: nullableNum(raw?.longitude),
  distance_km: nullableNum(raw?.distance_km),
  shop_is_open: typeof raw?.shop_is_open === 'boolean' ? raw.shop_is_open : null,
  can_order: typeof raw?.can_order === 'boolean' ? raw.can_order : null,
  track_stock: bool(raw?.track_stock),
  max_order_quantity: Math.max(1, num(raw?.max_order_quantity, 99)),
  has_options: typeof raw?.has_options === 'boolean' ? raw.has_options : null,
  seller: normalizeListingSeller(raw?.seller),
  category:
    raw?.category && typeof raw.category === 'object'
      ? { id: num(raw.category.id), name: str(raw.category.name) || '', icon: str(raw.category.icon) }
      : null,
});

const normalizeOptionGroup = (raw: any): FmOptionGroup => {
  const selection = raw?.selection_type === 'multi' ? 'multi' : 'single';
  const options: FmOption[] = (Array.isArray(raw?.options) ? raw.options : [])
    .map((o: any) => ({
      id: num(o?.id),
      group_id: num(o?.group_id, num(raw?.id)),
      name: str(o?.name) || 'ตัวเลือก',
      price_delta: num(o?.price_delta),
      image_url: str(o?.image_url),
      is_available: o?.is_available !== false,
      sort_order: num(o?.sort_order),
    }))
    .filter((o: FmOption) => o.id > 0)
    .sort((a: FmOption, b: FmOption) => a.sort_order - b.sort_order || a.id - b.id);
  const maxRaw = nullableNum(raw?.max_select);
  const isRequired = bool(raw?.is_required);
  return {
    id: num(raw?.id),
    name: str(raw?.name) || 'ตัวเลือก',
    selection_type: selection,
    is_required: isRequired,
    min_select: Math.max(num(raw?.min_select), isRequired ? 1 : 0),
    // single บังคับเลือกได้ 1 · 0/null = ไม่จำกัด
    max_select: selection === 'single' ? 1 : maxRaw && maxRaw > 0 ? maxRaw : null,
    rule_label: str(raw?.rule_label),
    sort_order: num(raw?.sort_order),
    options,
  };
};

const normalizeListingDetail = (raw: any): FmListingDetail => {
  const base = normalizeListingSummary(raw);
  const s = raw?.seller;
  const sellerBase = normalizeListingSeller(s);
  const presence = normalizePresence(s?.presence, sellerBase?.is_open === true, sellerBase?.is_mobile === true);
  return {
    ...base,
    tags: Array.isArray(raw?.tags) ? raw.tags.filter((t: unknown) => typeof t === 'string') : [],
    delivery_radius_km: nullableNum(raw?.delivery_radius_km),
    is_available_for_purchase: raw?.is_available_for_purchase !== false,
    option_groups: (Array.isArray(raw?.option_groups) ? raw.option_groups : [])
      .map(normalizeOptionGroup)
      .filter((g: FmOptionGroup) => g.id > 0 && g.options.length > 0)
      .sort((a: FmOptionGroup, b: FmOptionGroup) => a.sort_order - b.sort_order || a.id - b.id),
    seller: sellerBase
      ? {
          ...sellerBase,
          is_open: typeof s?.is_open === 'boolean' ? s.is_open : presence.is_open,
          location_label: sellerBase.location_label || presence.location_label,
          closed_message: sellerBase.closed_message || presence.closed_message,
          shop_description: str(s?.shop_description),
          shop_image: str(s?.shop_image),
          rating_count: num(s?.rating_count),
          total_sales: num(s?.total_sales),
          latitude: nullableNum(s?.latitude),
          longitude: nullableNum(s?.longitude),
          province: str(s?.province),
          is_verified: bool(s?.is_verified),
          user_id: nullableNum(s?.user_id),
          presence,
        }
      : null,
  };
};

// =====================================================
// ชนิดข้อมูล: ตะกร้า
// =====================================================

export interface FmSelectedOption {
  group_id: number | null;
  group_name: string | null;
  option_id: number | null;
  name: string;
  price_delta: number;
}

export interface FmCartLine {
  id: number;
  seller_id: number;
  listing_id: number;
  slug: string | null;
  title: string;
  unit: string | null;
  image_url: string | null;
  quantity: number;
  option_ids: number[];
  selected_options: FmSelectedOption[];
  options_label: string | null;
  note: string | null;
  base_price: number;
  options_price: number;
  unit_price: number;
  line_total: number;
  track_stock: boolean;
  max_order_quantity: number;
  is_valid: boolean;
  issue: { code: string; message: string } | null;
}

export interface FmCartShop {
  seller: {
    id: number;
    shop_name: string;
    shop_image: string | null;
    rating_average: number;
    is_available: boolean;
    is_mobile: boolean;
    is_open: boolean;
    closed_message: string | null;
  } | null;
  seller_id: number;
  items: FmCartLine[];
  lines_count: number;
  items_count: number;
  subtotal: number;
  issues_count: number;
  is_open: boolean;
  can_checkout: boolean;
}

export interface FmCart {
  shops: FmCartShop[];
  shops_count: number;
  lines_count: number;
  items_count: number;
  subtotal: number;
  has_issues: boolean;
}

export const EMPTY_FM_CART: FmCart = {
  shops: [],
  shops_count: 0,
  lines_count: 0,
  items_count: 0,
  subtotal: 0,
  has_issues: false,
};

const normalizeSelectedOptions = (raw: unknown): FmSelectedOption[] =>
  (Array.isArray(raw) ? raw : []).map((o: any) => ({
    group_id: nullableNum(o?.group_id),
    group_name: str(o?.group_name),
    option_id: nullableNum(o?.option_id),
    name: str(o?.name) || '',
    price_delta: num(o?.price_delta),
  }));

const normalizeCartLine = (raw: any): FmCartLine => ({
  id: num(raw?.id),
  seller_id: num(raw?.seller_id),
  listing_id: num(raw?.listing_id),
  slug: str(raw?.slug),
  title: str(raw?.title) || 'เมนู',
  unit: str(raw?.unit),
  image_url: str(raw?.image_url),
  quantity: Math.max(1, num(raw?.quantity, 1)),
  option_ids: Array.isArray(raw?.option_ids) ? raw.option_ids.map((x: unknown) => num(x)).filter((x: number) => x > 0) : [],
  selected_options: normalizeSelectedOptions(raw?.selected_options),
  options_label: str(raw?.options_label),
  note: str(raw?.note),
  base_price: num(raw?.base_price),
  options_price: num(raw?.options_price),
  unit_price: num(raw?.unit_price),
  line_total: num(raw?.line_total),
  track_stock: bool(raw?.track_stock),
  max_order_quantity: Math.max(1, num(raw?.max_order_quantity, 99)),
  is_valid: raw?.is_valid !== false,
  issue:
    raw?.issue && typeof raw.issue === 'object'
      ? { code: str(raw.issue.code) || 'INVALID_ITEM', message: str(raw.issue.message) || 'รายการนี้สั่งไม่ได้ตอนนี้' }
      : null,
});

const normalizeCartShop = (raw: any): FmCartShop => {
  const items = (Array.isArray(raw?.items) ? raw.items : []).map(normalizeCartLine);
  const s = raw?.seller;
  const isOpen = typeof raw?.is_open === 'boolean' ? raw.is_open : bool(s?.is_open);
  return {
    seller:
      s && typeof s === 'object'
        ? {
            id: num(s.id),
            shop_name: str(s.shop_name) || 'ร้านตลาดสด',
            shop_image: str(s.shop_image),
            rating_average: num(s.rating_average),
            is_available: s.is_available !== false,
            is_mobile: bool(s.is_mobile),
            is_open: typeof s.is_open === 'boolean' ? s.is_open : isOpen,
            closed_message: str(s.closed_message),
          }
        : null,
    seller_id: num(raw?.seller_id, num(s?.id)),
    items,
    lines_count: num(raw?.lines_count, items.length),
    items_count: num(raw?.items_count, items.reduce((sum: number, i: FmCartLine) => sum + i.quantity, 0)),
    subtotal: num(raw?.subtotal),
    issues_count: num(raw?.issues_count),
    is_open: isOpen,
    can_checkout: raw?.can_checkout === true,
  };
};

export const normalizeFmCart = (raw: any): FmCart => {
  if (!raw || typeof raw !== 'object') return EMPTY_FM_CART;
  const shops = (Array.isArray(raw.shops) ? raw.shops : []).map(normalizeCartShop).filter((s: FmCartShop) => s.items.length > 0);
  return {
    shops,
    shops_count: shops.length,
    lines_count: num(raw.lines_count, shops.reduce((sum: number, s: FmCartShop) => sum + s.lines_count, 0)),
    items_count: num(raw.items_count, shops.reduce((sum: number, s: FmCartShop) => sum + s.items_count, 0)),
    subtotal: num(raw.subtotal),
    has_issues: bool(raw.has_issues),
  };
};

export interface FmQuote {
  available: boolean;
  code: string | null;
  message: string | null;
  distance_km: number | null;
  total_fee: number;
  estimated_duration_minutes: number | null;
  max_distance_km: number | null;
  subtotal: number;
  grand_total: number;
  items_count: number;
}

const normalizeQuote = (raw: any): FmQuote => ({
  available: raw?.available === true,
  code: str(raw?.code),
  message: str(raw?.message),
  distance_km: nullableNum(raw?.distance_km),
  total_fee: num(raw?.total_fee),
  estimated_duration_minutes: nullableNum(raw?.estimated_duration_minutes),
  max_distance_km: nullableNum(raw?.max_distance_km),
  subtotal: num(raw?.subtotal),
  grand_total: num(raw?.grand_total),
  items_count: num(raw?.items_count),
});

// =====================================================
// ชนิดข้อมูล: ออเดอร์
// =====================================================

export type FmOrderStatus =
  | 'pending'
  | 'accepted'
  | 'preparing'
  | 'ready'
  | 'delivering'
  | 'delivered'
  | 'completed'
  | 'cancelled'
  | 'delivery_failed';

export type FmDeliveryType = 'pickup' | 'rider';
export type FmPaymentMethod = 'wallet' | 'cod';

export interface FmOrderItem {
  id: number;
  listing_id: number | null;
  title: string;
  unit: string | null;
  image_url: string | null;
  quantity: number;
  base_price: number;
  options_price: number;
  unit_price: number;
  line_total: number;
  selected_options: FmSelectedOption[];
  options_label: string | null;
  note: string | null;
}

export interface FmOrder {
  id: number;
  order_number: string;
  order_status: FmOrderStatus | string;
  status_label: string;
  payment_method: string;
  payment_method_label: string;
  payment_status: string;
  payment_status_label: string;
  delivery_type: FmDeliveryType;
  total_amount: number;
  subtotal: number;
  delivery_fee: number;
  grand_total: number;
  delivery_distance_km: number | null;
  delivery_address: string | null;
  delivery_notes: string | null;
  cancel_reason: string | null;
  cancelled_by: string | null;
  buyer_rating: number | null;
  buyer_review: string | null;
  can_review: boolean;
  allowed_actions: string[];
  items_count: number;
  items_summary: string | null;
  items: FmOrderItem[];
  seller: {
    id: number;
    shop_name: string;
    phone: string | null;
    address: string | null;
    latitude: number | null;
    longitude: number | null;
    is_mobile: boolean;
    is_open: boolean;
  } | null;
  rider_job: { id: number; status: string; rider_name: string | null; rider_phone: string | null; tracking_url: string | null } | null;
  buyer_latitude: number | null;
  buyer_longitude: number | null;
  created_at: string | null;
  accepted_at: string | null;
  ready_at: string | null;
  delivered_at: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
  /**
   * บทบาทของผู้ดูจาก GET /orders/{id} (showOrder) — 'seller' = เจ้าของร้านเปิดดู
   * (เช่น กด push delivery_update ที่ส่งหาทั้งผู้ซื้อและร้าน) · รายการออเดอร์ไม่มีค่านี้ = null
   */
  viewer_role: 'buyer' | 'seller' | null;
}

const normalizeOrderItem = (raw: any): FmOrderItem => ({
  id: num(raw?.id),
  listing_id: nullableNum(raw?.listing_id),
  title: str(raw?.title) || 'เมนู',
  unit: str(raw?.unit),
  image_url: str(raw?.image_url),
  quantity: Math.max(1, num(raw?.quantity, 1)),
  base_price: num(raw?.base_price),
  options_price: num(raw?.options_price),
  unit_price: num(raw?.unit_price),
  line_total: num(raw?.line_total),
  selected_options: normalizeSelectedOptions(raw?.selected_options),
  options_label: str(raw?.options_label),
  note: str(raw?.note),
});

export const normalizeFmOrder = (raw: any): FmOrder => {
  const items = (Array.isArray(raw?.items) ? raw.items : []).map(normalizeOrderItem);
  // ออเดอร์เก่ามีแค่ listing/quantity/unit_price → สร้างรายการเดียวให้แสดงได้
  if (items.length === 0 && raw?.listing && typeof raw.listing === 'object') {
    items.push({
      id: num(raw.listing.id),
      listing_id: nullableNum(raw.listing.id),
      title: str(raw.listing.title) || 'เมนู',
      unit: str(raw.listing.unit),
      image_url: str(raw.listing.image),
      quantity: Math.max(1, num(raw.quantity, 1)),
      base_price: num(raw.unit_price),
      options_price: 0,
      unit_price: num(raw.unit_price),
      line_total: num(raw.total_amount),
      selected_options: [],
      options_label: null,
      note: null,
    });
  }
  const s = raw?.seller;
  const j = raw?.rider_job;
  return {
    id: num(raw?.id),
    order_number: str(raw?.order_number) || '',
    order_status: str(raw?.order_status) || 'pending',
    status_label: str(raw?.status_label) || 'กำลังดำเนินการ',
    payment_method: str(raw?.payment_method) || '',
    payment_method_label: str(raw?.payment_method_label) || '',
    payment_status: str(raw?.payment_status) || 'pending',
    payment_status_label: str(raw?.payment_status_label) || '',
    delivery_type: raw?.delivery_type === 'rider' ? 'rider' : 'pickup',
    total_amount: num(raw?.total_amount),
    subtotal: num(raw?.subtotal, num(raw?.total_amount)),
    delivery_fee: num(raw?.delivery_fee),
    grand_total: num(raw?.grand_total, num(raw?.total_amount) + num(raw?.delivery_fee)),
    delivery_distance_km: nullableNum(raw?.delivery_distance_km),
    delivery_address: str(raw?.delivery_address),
    delivery_notes: str(raw?.delivery_notes),
    cancel_reason: str(raw?.cancel_reason),
    cancelled_by: str(raw?.cancelled_by),
    buyer_rating: nullableNum(raw?.buyer_rating),
    buyer_review: str(raw?.buyer_review),
    can_review: raw?.can_review === true,
    allowed_actions: Array.isArray(raw?.allowed_actions) ? raw.allowed_actions.filter((a: unknown) => typeof a === 'string') : [],
    items_count: num(raw?.items_count, items.reduce((sum: number, i: FmOrderItem) => sum + i.quantity, 0)),
    items_summary: str(raw?.items_summary),
    items,
    seller:
      s && typeof s === 'object'
        ? {
            id: num(s.id),
            shop_name: str(s.shop_name) || 'ร้านตลาดสด',
            phone: str(s.phone),
            address: str(s.address),
            latitude: nullableNum(s.latitude),
            longitude: nullableNum(s.longitude),
            is_mobile: bool(s.is_mobile),
            is_open: bool(s.is_open),
          }
        : null,
    rider_job:
      j && typeof j === 'object'
        ? {
            id: num(j.id),
            status: str(j.status) || '',
            rider_name: str(j.rider_name),
            rider_phone: str(j.rider_phone),
            tracking_url: str(j.tracking_url),
          }
        : null,
    buyer_latitude: nullableNum(raw?.buyer_latitude),
    buyer_longitude: nullableNum(raw?.buyer_longitude),
    created_at: str(raw?.created_at),
    accepted_at: str(raw?.accepted_at),
    ready_at: str(raw?.ready_at),
    delivered_at: str(raw?.delivered_at),
    completed_at: str(raw?.completed_at),
    cancelled_at: str(raw?.cancelled_at),
    viewer_role: raw?.viewer_role === 'seller' ? 'seller' : raw?.viewer_role === 'buyer' ? 'buyer' : null,
  };
};

export interface FmConfig {
  payment_methods: FmPaymentMethod[];
  rider_enabled: boolean;
  rider_max_distance_km: number;
  pending_expiry_minutes: number;
  auto_complete_hours: number;
  default_search_radius_km: number;
  max_search_radius_km: number;
  brand_name: string | null;
}

// =====================================================
// ชนิดข้อมูล: ติดตามไรเดอร์ (ออเดอร์ร้านค้า + ตลาดสด)
// =====================================================

export type DeliverySource = 'shop' | 'fresh-market';

export type RiderLocationReason =
  | 'no_rider_job'
  | 'waiting_for_rider'
  | 'job_not_active'
  | 'consent_missing'
  | 'no_gps_yet'
  | 'gps_stale'
  | string;

export interface CustomerSharing {
  enabled: boolean;
  last_shared_at: string | null;
  is_fresh: boolean;
  fresh_seconds: number;
}

export interface RiderLocationInfo {
  source: DeliverySource;
  order_id: number;
  order_number: string;
  poll_interval_seconds: number;
  customer_send_interval_seconds: number;
  has_rider_job: boolean;
  job: {
    id: number;
    job_number: string | null;
    status: string;
    status_text: string | null;
    is_active: boolean;
    accepted_at: string | null;
    picked_up_at: string | null;
    completed_at: string | null;
  } | null;
  rider: {
    name: string | null;
    vehicle_type: string | null;
    vehicle_type_text: string | null;
    vehicle_plate: string | null;
    phone: string | null;
    rating: number | null;
  } | null;
  rider_location: {
    latitude: number;
    longitude: number;
    updated_at: string | null;
    heading: number | null;
    speed: number | null;
    gps_active: boolean;
    is_stale: boolean;
  } | null;
  location_available: boolean;
  reason: RiderLocationReason | null;
  reason_text: string | null;
  pickup: { latitude: number; longitude: number; address: string | null } | null;
  dropoff: { latitude: number; longitude: number } | null;
  customer_sharing: CustomerSharing;
  can_share_location: boolean;
}

export interface ShareLocationResult {
  sharing: boolean;
  location_saved: boolean;
  customer_sharing: CustomerSharing;
  job_status: string | null;
}

const normalizeSharing = (raw: any): CustomerSharing => ({
  enabled: bool(raw?.enabled),
  last_shared_at: str(raw?.last_shared_at),
  is_fresh: bool(raw?.is_fresh),
  fresh_seconds: num(raw?.fresh_seconds, 120),
});

const normalizeRiderLocation = (raw: any, source: DeliverySource, orderId: number): RiderLocationInfo => {
  const job = raw?.job;
  const rider = raw?.rider;
  const loc = raw?.rider_location;
  const pickup = raw?.pickup;
  const dropoff = raw?.dropoff;
  return {
    source,
    order_id: num(raw?.order_id, orderId),
    order_number: str(raw?.order_number) || '',
    poll_interval_seconds: Math.max(10, num(raw?.poll_interval_seconds, 15)),
    customer_send_interval_seconds: Math.max(15, num(raw?.customer_send_interval_seconds, 30)),
    has_rider_job: bool(raw?.has_rider_job),
    job:
      job && typeof job === 'object'
        ? {
            id: num(job.id),
            job_number: str(job.job_number),
            status: str(job.status) || '',
            status_text: str(job.status_text),
            is_active: bool(job.is_active),
            accepted_at: str(job.accepted_at),
            picked_up_at: str(job.picked_up_at),
            completed_at: str(job.completed_at),
          }
        : null,
    rider:
      rider && typeof rider === 'object'
        ? {
            name: str(rider.name),
            vehicle_type: str(rider.vehicle_type),
            vehicle_type_text: str(rider.vehicle_type_text),
            vehicle_plate: str(rider.vehicle_plate),
            phone: str(rider.phone),
            rating: nullableNum(rider.rating),
          }
        : null,
    rider_location:
      loc && typeof loc === 'object' && isUsableCoord(loc.latitude, loc.longitude)
        ? {
            latitude: Number(loc.latitude),
            longitude: Number(loc.longitude),
            updated_at: str(loc.updated_at),
            heading: nullableNum(loc.heading),
            speed: nullableNum(loc.speed),
            gps_active: bool(loc.gps_active),
            is_stale: bool(loc.is_stale),
          }
        : null,
    location_available: bool(raw?.location_available),
    reason: str(raw?.reason),
    reason_text: str(raw?.reason_text),
    pickup:
      pickup && isUsableCoord(pickup.latitude, pickup.longitude)
        ? { latitude: Number(pickup.latitude), longitude: Number(pickup.longitude), address: str(pickup.address) }
        : null,
    dropoff:
      dropoff && isUsableCoord(dropoff.latitude, dropoff.longitude)
        ? { latitude: Number(dropoff.latitude), longitude: Number(dropoff.longitude) }
        : null,
    customer_sharing: normalizeSharing(raw?.customer_sharing),
    can_share_location: bool(raw?.can_share_location),
  };
};

// =====================================================
// ช่วยแปลงผลลัพธ์
// =====================================================

const mapResult = <A, B>(result: ApiResult<A>, fn: (data: A) => B): ApiResult<B> =>
  result.success ? { ...result, data: fn(result.data) } : result;

const toPagination = (meta: Record<string, unknown> | undefined): Pagination | null => {
  const m = meta?.meta as Record<string, unknown> | undefined;
  if (!m || typeof m !== 'object') return null;
  return {
    current_page: num(m.current_page, 1),
    last_page: num(m.last_page, 1),
    per_page: num(m.per_page, 20),
    total: num(m.total),
    has_more: num(m.current_page, 1) < num(m.last_page, 1),
  };
};

// =====================================================
// Public: ค่าตั้ง / หมวด / เมนู / ร้าน
// =====================================================

export const getTaladsodConfig = async (): Promise<ApiResult<FmConfig>> =>
  mapResult(await withThai(apiGet<any>(`${BASE}/config`)), (raw) => {
    const methods = (Array.isArray(raw?.payment_methods) ? raw.payment_methods : [])
      .map((m: unknown) => (m === 'escrow' ? 'wallet' : m))
      .filter((m: unknown): m is FmPaymentMethod => m === 'wallet' || m === 'cod');
    return {
      payment_methods: Array.from(new Set<FmPaymentMethod>(methods)),
      rider_enabled: bool(raw?.rider_enabled),
      rider_max_distance_km: num(raw?.rider_max_distance_km, 15),
      pending_expiry_minutes: num(raw?.pending_expiry_minutes, 30),
      auto_complete_hours: num(raw?.auto_complete_hours, 24),
      default_search_radius_km: num(raw?.default_search_radius_km, 10),
      max_search_radius_km: num(raw?.max_search_radius_km, 50),
      brand_name: str(raw?.brand_name),
    };
  });

export const getTaladsodCategories = async (): Promise<ApiResult<FmCategory[]>> =>
  mapResult(await withThai(apiGet<any[]>(`${BASE}/categories`)), (rows) =>
    (Array.isArray(rows) ? rows : []).map((c: any) => ({
      id: num(c?.id),
      name: str(c?.name) || 'หมวดหมู่',
      slug: str(c?.slug) || String(c?.id ?? ''),
      icon: str(c?.icon),
      image_url: str(c?.image_url),
      children: (Array.isArray(c?.children) ? c.children : []).map((ch: any) => ({
        id: num(ch?.id),
        name: str(ch?.name) || '',
        slug: str(ch?.slug) || '',
        icon: str(ch?.icon),
      })),
    }))
  );

export interface ListingQuery {
  lat?: number;
  lng?: number;
  radius?: number;
  q?: string;
  /** id หรือ slug */
  category_id?: number | string;
  sort?: 'newest' | 'price_asc' | 'price_desc' | 'popular' | 'distance';
  page?: number;
  per_page?: number;
}

/** GET /fresh-market/listings — data[] + meta */
export const getListings = async (
  query: ListingQuery = {}
): Promise<ApiResult<{ listings: FmListingSummary[]; pagination: Pagination | null }>> => {
  const result = await withThai(apiGet<any[]>(`${BASE}/listings`, query as Record<string, unknown>));
  return mapResult(result, (rows) => ({
    listings: (Array.isArray(rows) ? rows : []).map(normalizeListingSummary).filter((l) => l.id > 0),
    pagination: result.success ? toPagination(result.meta) : null,
  }));
};

/** GET /fresh-market/listings/{id} — 404 LISTING_NOT_FOUND · related อยู่ใน meta */
export const getListing = async (
  id: number
): Promise<ApiResult<{ listing: FmListingDetail; related: FmRelatedListing[] }>> => {
  const result = await withThai(apiGet<any>(`${BASE}/listings/${id}`));
  return mapResult(result, (raw) => {
    const relatedRaw = result.success ? (result.meta?.related as unknown) : null;
    return {
      listing: normalizeListingDetail(raw),
      related: (Array.isArray(relatedRaw) ? relatedRaw : []).map((r: any) => ({
        id: num(r?.id),
        slug: str(r?.slug),
        title: str(r?.title) || 'เมนู',
        price: num(r?.price),
        unit: str(r?.unit),
        main_image_url: str(r?.main_image_url),
        shop_name: str(r?.shop_name),
      })),
    };
  });
};

/** GET /fresh-market/shops/nearby — ร้านที่เปิดอยู่ใกล้พิกัด (ร้านปิดไม่ถูกส่งมา) */
export const getNearbyShops = async (
  lat: number,
  lng: number,
  radiusKm: number = 10
): Promise<ApiResult<{ shops: FmNearbyShop[]; radius_km: number }>> =>
  mapResult(
    await withThai(
      apiGet<any>(`${BASE}/shops/nearby`, {
        lat: Number(lat.toFixed(6)),
        lng: Number(lng.toFixed(6)),
        radius: Math.max(0.5, Math.min(50, radiusKm)),
      })
    ),
    (raw) => ({
      radius_km: num(raw?.radius_km, radiusKm),
      shops: (Array.isArray(raw?.shops) ? raw.shops : [])
        .map((s: any): FmNearbyShop => ({
          ...normalizeShopCard(s),
          distance_km: num(s?.distance_km),
          location: normalizeLocation(s?.location),
          listings_count: num(s?.listings_count),
          top_items: (Array.isArray(s?.top_items) ? s.top_items : []).map((t: any) => ({
            id: num(t?.id),
            slug: str(t?.slug),
            title: str(t?.title) || 'เมนู',
            price: num(t?.price),
            unit: str(t?.unit),
            image_url: str(t?.image_url),
          })),
        }))
        .filter((s: FmNearbyShop) => s.id > 0),
    })
  );

/** GET /fresh-market/shops/{id} — 404 SHOP_NOT_FOUND (ส่ง token ไปด้วยเพื่อให้รู้ is_following) */
export const getShop = async (id: number): Promise<ApiResult<FmShopDetail>> =>
  mapResult(await withThai(apiGet<any>(`${BASE}/shops/${id}`)), (raw) => ({
    ...normalizeShopCard(raw),
    shop_description: str(raw?.shop_description),
    total_sales: num(raw?.total_sales),
    province: str(raw?.province),
    district: str(raw?.district),
    address: str(raw?.address),
    latitude: nullableNum(raw?.latitude),
    longitude: nullableNum(raw?.longitude),
    followers_count: num(raw?.followers_count),
    can_order: raw?.can_order === true,
    closed_message: str(raw?.closed_message),
    location_poll_seconds: num(raw?.location_poll_seconds, 120),
    is_owner: bool(raw?.is_owner),
    listings: (Array.isArray(raw?.listings) ? raw.listings : []).map(normalizeShopListing).filter((l: FmShopListing) => l.id > 0),
  }));

/** GET /fresh-market/shops/{id}/location — ร้านปิด = 200 is_open:false location:null (หยุด poll) */
export const getShopLocation = async (id: number): Promise<ApiResult<FmShopLocationPoll>> =>
  mapResult(await withThai(apiGet<any>(`${BASE}/shops/${id}/location`)), (raw) => ({
    shop_id: num(raw?.shop_id, id),
    is_open: bool(raw?.is_open),
    is_mobile: bool(raw?.is_mobile),
    location: normalizeLocation(raw?.location),
    closes_at: str(raw?.closes_at),
    closed_message: str(raw?.closed_message),
    poll_interval_seconds: num(raw?.poll_interval_seconds, 30),
    server_time: str(raw?.server_time),
  }));

// =====================================================
// ติดตามร้าน (ต้องล็อกอิน)
// =====================================================

/** POST /shops/{id}/follow — กดซ้ำปลอดภัย · 422 CANNOT_FOLLOW_OWN_SHOP */
export const followShop = async (id: number): Promise<ApiResult<FmFollowResult>> =>
  mapResult(await withThai(apiPost<any>(`${BASE}/shops/${id}/follow`, {})), (raw) => ({
    shop_id: num(raw?.shop_id, id),
    is_following: raw?.is_following !== false,
    notify: raw?.notify !== false,
    followers_count: num(raw?.followers_count),
  }));

export const unfollowShop = async (id: number): Promise<ApiResult<FmFollowResult>> =>
  mapResult(await withThai(apiDelete<any>(`${BASE}/shops/${id}/follow`)), (raw) => ({
    shop_id: num(raw?.shop_id, id),
    is_following: raw?.is_following === true,
    followers_count: num(raw?.followers_count),
  }));

/** GET /me/followed-shops — ร้านที่เปิดอยู่ขึ้นก่อน */
export const getFollowedShops = async (
  page: number = 1,
  perPage: number = 20
): Promise<ApiResult<{ shops: FmFollowedShop[]; pagination: Pagination | null }>> => {
  const result = await withThai(apiGet<any[]>(`${BASE}/me/followed-shops`, { page, per_page: perPage }));
  return mapResult(result, (rows) => ({
    shops: (Array.isArray(rows) ? rows : [])
      .map((s: any) => ({ ...normalizeShopCard({ ...s, is_following: true }), notify: s?.notify !== false, followed_at: str(s?.followed_at) }))
      .filter((s: FmFollowedShop) => s.id > 0),
    pagination: result.success ? toPagination(result.meta) : null,
  }));
};

// =====================================================
// ตะกร้า (ต้องล็อกอิน) — ทุกคำสั่งคืนตะกร้าทั้งใบ
// =====================================================

export const getFmCart = async (sellerId?: number): Promise<ApiResult<FmCart>> =>
  mapResult(await withThai(apiGet<any>(`${BASE}/cart`, sellerId ? { seller_id: sellerId } : undefined)), normalizeFmCart);

/** POST /cart/items — เมนู+ตัวเลือก+หมายเหตุเดียวกัน = รวมเป็นบรรทัดเดียว (บวกจำนวน) · 60 ครั้ง/นาที */
export const addFmCartItem = async (input: {
  listing_id: number;
  quantity: number;
  option_ids: number[];
  note?: string | null;
}): Promise<ApiResult<FmCart & { item_id: number | null }>> => {
  const body: Record<string, unknown> = {
    listing_id: input.listing_id,
    quantity: Math.max(1, Math.min(999, Math.round(input.quantity))),
    option_ids: input.option_ids,
  };
  const note = (input.note || '').trim();
  if (note) body.note = note.slice(0, 255);
  const result = await withThai(apiPost<any>(`${BASE}/cart/items`, body));
  return mapResult(result, (raw) => ({
    ...normalizeFmCart(raw),
    item_id: result.success ? nullableNum(result.meta?.item_id) : null,
  }));
};

/** PUT /cart/items/{id} — quantity 0 = ลบ · ส่ง option_ids / note ได้ (ตรงกับบรรทัดอื่น = รวมกัน) */
export const updateFmCartItem = async (
  itemId: number,
  patch: { quantity?: number; option_ids?: number[]; note?: string | null }
): Promise<ApiResult<FmCart>> => {
  const body: Record<string, unknown> = {};
  if (typeof patch.quantity === 'number') body.quantity = Math.max(0, Math.min(999, Math.round(patch.quantity)));
  if (patch.option_ids) body.option_ids = patch.option_ids;
  if (patch.note !== undefined) {
    const note = (patch.note || '').trim();
    body.note = note ? note.slice(0, 255) : null;
  }
  return mapResult(await withThai(apiPut<any>(`${BASE}/cart/items/${itemId}`, body)), normalizeFmCart);
};

export const removeFmCartItem = async (itemId: number): Promise<ApiResult<FmCart>> =>
  mapResult(await withThai(apiDelete<any>(`${BASE}/cart/items/${itemId}`)), normalizeFmCart);

/** DELETE /cart?seller_id= (ไม่ส่งร้าน = ล้างทั้งตะกร้า) */
export const clearFmCart = async (sellerId?: number): Promise<ApiResult<FmCart>> =>
  mapResult(
    await withThai(apiDelete<any>(sellerId ? `${BASE}/cart?seller_id=${encodeURIComponent(String(sellerId))}` : `${BASE}/cart`)),
    normalizeFmCart
  );

/**
 * GET /cart/quote — ค่าส่งไรเดอร์ของตะกร้าร้านเดียว
 * ส่งไม่ได้ (นอกระยะ/ปิดไรเดอร์/ร้านปิด) = 422 แต่ยังมี data → คืนเป็น success พร้อม available:false
 */
export const getFmCartQuote = async (sellerId: number, lat: number, lng: number): Promise<ApiResult<FmQuote>> => {
  const result = await withThai(
    apiGet<any>(`${BASE}/cart/quote`, {
      seller_id: sellerId,
      latitude: Number(lat.toFixed(6)),
      longitude: Number(lng.toFixed(6)),
    })
  );
  if (!result.success && result.data && typeof result.data === 'object' && 'available' in result.data) {
    return {
      success: true,
      status: result.status,
      message: result.message,
      data: { ...normalizeQuote(result.data), available: false, code: result.code, message: result.message },
    };
  }
  return mapResult(result, normalizeQuote);
};

// =====================================================
// สั่งซื้อ + ออเดอร์ของฉัน
// =====================================================

export interface FmCheckoutInput {
  seller_id: number;
  delivery_type: FmDeliveryType;
  payment_method: FmPaymentMethod;
  delivery_address?: string;
  buyer_latitude?: number;
  buyer_longitude?: number;
  delivery_notes?: string;
}

/**
 * POST /fresh-market/orders แบบ "จากตะกร้า" — สั่งทุกบรรทัดของร้านนั้นแล้วล้างในธุรกรรมเดียว
 * กดซ้ำ = 422 CART_EMPTY (จึงปลอดภัยต่อการกดซ้ำ) · ตะกร้าเปลี่ยนกลางคัน = 409 CART_CHANGED
 */
export const placeFmOrderFromCart = async (input: FmCheckoutInput): Promise<ApiResult<FmOrder>> => {
  const body: Record<string, unknown> = {
    seller_id: input.seller_id,
    delivery_type: input.delivery_type,
    payment_method: input.payment_method,
  };
  if (input.delivery_type === 'rider') {
    body.delivery_address = (input.delivery_address || '').trim().slice(0, 500);
    body.buyer_latitude = input.buyer_latitude;
    body.buyer_longitude = input.buyer_longitude;
  }
  const notes = (input.delivery_notes || '').trim();
  if (notes) body.delivery_notes = notes.slice(0, 500);
  return mapResult(await withThai(apiPost<any>(`${BASE}/orders`, body, { timeout: 30000 })), normalizeFmOrder);
};

export const getFmOrders = async (
  params: { status?: FmOrderStatus | 'all'; page?: number } = {}
): Promise<ApiResult<{ orders: FmOrder[]; pagination: Pagination | null }>> => {
  const query: Record<string, unknown> = { page: params.page || 1 };
  if (params.status && params.status !== 'all') query.status = params.status;
  const result = await withThai(apiGet<any[]>(`${BASE}/orders`, query));
  return mapResult(result, (rows) => ({
    orders: (Array.isArray(rows) ? rows : []).map(normalizeFmOrder).filter((o) => o.id > 0),
    pagination: result.success ? toPagination(result.meta) : null,
  }));
};

/** GET /orders/{id} — ของคนอื่น = 404 ORDER_NOT_FOUND */
export const getFmOrder = async (id: number): Promise<ApiResult<FmOrder>> =>
  mapResult(await withThai(apiGet<any>(`${BASE}/orders/${id}`)), normalizeFmOrder);

/** ยกเลิกได้เฉพาะตอนร้านยังไม่รับ (allowed_actions มี cancel) */
export const cancelFmOrder = async (id: number, reason?: string): Promise<ApiResult<FmOrder>> =>
  mapResult(
    await withThai(apiPost<any>(`${BASE}/orders/${id}/cancel`, reason ? { reason: reason.slice(0, 500) } : {})),
    normalizeFmOrder
  );

export interface FmRatingInput {
  rating?: number;
  review?: string;
  rider_rating?: number;
  rider_review?: string;
}

const ratingBody = (input: FmRatingInput): Record<string, unknown> => {
  const body: Record<string, unknown> = {};
  if (input.rating && input.rating >= 1 && input.rating <= 5) body.rating = Math.round(input.rating);
  if (input.review && input.review.trim()) body.review = input.review.trim().slice(0, 1000);
  if (input.rider_rating && input.rider_rating >= 1 && input.rider_rating <= 5) body.rider_rating = Math.round(input.rider_rating);
  if (input.rider_review && input.rider_review.trim()) body.rider_review = input.rider_review.trim().slice(0, 1000);
  return body;
};

/** ยืนยันรับของ (+ ให้คะแนนพร้อมกันได้) → completed */
export const confirmFmOrder = async (id: number, input: FmRatingInput = {}): Promise<ApiResult<FmOrder>> =>
  mapResult(await withThai(apiPost<any>(`${BASE}/orders/${id}/confirm`, ratingBody(input))), normalizeFmOrder);

/** ให้คะแนนภายหลัง — 409 ALREADY_RATED / NOT_RATEABLE */
export const reviewFmOrder = async (id: number, input: FmRatingInput & { rating: number }): Promise<ApiResult<FmOrder>> =>
  mapResult(await withThai(apiPost<any>(`${BASE}/orders/${id}/review`, ratingBody(input))), normalizeFmOrder);

// =====================================================
// ติดตามไรเดอร์ + แชร์ตำแหน่งให้ไรเดอร์ (ออเดอร์ร้านค้า + ตลาดสด)
// =====================================================

/** GET /orders/{source}/{id}/rider-location — poll ทุก 15 วินาทีระหว่างงานวิ่ง (60 ครั้ง/นาที) */
export const getDeliveryRiderLocation = async (
  source: DeliverySource,
  orderId: number
): Promise<ApiResult<RiderLocationInfo>> =>
  mapResult(await withThai(apiGet<any>(`/orders/${source}/${orderId}/rider-location`)), (raw) =>
    normalizeRiderLocation(raw, source, orderId)
  );

/**
 * POST /orders/{source}/{id}/share-location
 * share:true + พิกัด ทุก ~30 วินาทีระหว่างเปิดหน้า · share:false = หยุดและลบพิกัดที่เก็บไว้
 * 409 JOB_NOT_ACTIVE = ยังไม่มีไรเดอร์/ส่งเสร็จแล้ว → หยุดส่ง
 */
export const shareDeliveryLocation = async (
  source: DeliverySource,
  orderId: number,
  share: boolean,
  coords?: { latitude: number; longitude: number } | null
): Promise<ApiResult<ShareLocationResult>> => {
  const body: Record<string, unknown> = { share };
  if (share && coords && isUsableCoord(coords.latitude, coords.longitude)) {
    body.latitude = Number(coords.latitude.toFixed(6));
    body.longitude = Number(coords.longitude.toFixed(6));
  }
  return mapResult(await withThai(apiPost<any>(`/orders/${source}/${orderId}/share-location`, body)), (raw) => ({
    sharing: bool(raw?.sharing),
    location_saved: bool(raw?.location_saved),
    customer_sharing: normalizeSharing(raw?.customer_sharing),
    job_status: str(raw?.job_status),
  }));
};

// =====================================================
// ป้ายสถานะออเดอร์ตลาดสด
// =====================================================

export const FM_ORDER_ACTIVE_STATUSES: string[] = ['pending', 'accepted', 'preparing', 'ready', 'delivering', 'delivered'];
export const FM_ORDER_TERMINAL_STATUSES: string[] = ['completed', 'cancelled', 'delivery_failed'];

export const FM_ORDER_STATUS_LABEL: Record<string, string> = {
  pending: 'รอร้านรับออเดอร์',
  accepted: 'ร้านรับออเดอร์แล้ว',
  preparing: 'ร้านกำลังทำ',
  ready: 'พร้อมแล้ว',
  delivering: 'ไรเดอร์กำลังไปส่ง',
  delivered: 'ส่งถึงแล้ว',
  completed: 'สำเร็จ',
  cancelled: 'ยกเลิกแล้ว',
  delivery_failed: 'ส่งไม่สำเร็จ',
};
