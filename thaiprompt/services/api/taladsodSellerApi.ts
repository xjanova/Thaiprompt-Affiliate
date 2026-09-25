/**
 * Taladsod Seller API — โหมดคนขายในตลาดสด (ร้านรถเข็น / ตลาดนัด / ร้านประจำ)
 *
 * ทุก endpoint อยู่ใต้ /api/v1/fresh-market และต้องเป็นเจ้าของร้าน (ไม่ใช่ = 403 NOT_SELLER)
 *   - สถานะร้าน: GET /seller/presence · POST /seller/open · POST /seller/location · POST /seller/close
 *   - ภาพรวมร้าน: GET /seller/dashboard (โปรไฟล์ + สถิติ + presence)
 *   - ออเดอร์: GET /seller/orders?status= · GET /seller/orders/{id} · POST /seller/orders/{id}/action
 *   - สินค้า: GET /seller/listings · GET /seller/listings/{id} · PUT /listings/{id}
 *             POST|DELETE /listings/{id}/images · POST /listings/{id}/images/main
 *             PUT /listings/{id}/option-groups (ส่งทั้งชุด: มี id = แก้, ไม่มี id = สร้าง, ไม่ได้ส่ง = ลบ)
 *
 * แอปไม่ส่งราคาที่คำนวณเองขึ้นไป — server คำนวณยอดทั้งหมด
 * ร้านค้าออนไลน์ (e-commerce) เป็นอีกระบบ ดู merchantApi.ts
 */

import { APP_INFO } from '@/config/appConfig';
import {
  THAI_ERROR_MESSAGES,
  apiGet,
  apiPost,
  apiPut,
  apiUpload,
  apiDelete,
  fileFromUri,
  num,
  type ApiResult,
  type Pagination,
} from './client';

// =====================================================
// ข้อความภาษาไทยของ code เฉพาะโหมดคนขาย
// (server ตลาดสดส่งข้อความไทยมาเองเกือบทุกกรณี — ใช้ชุดนี้เมื่อข้อความที่ได้เป็นข้อความกลาง)
// =====================================================

export const FM_SELLER_MESSAGES: Record<string, string> = {
  NOT_SELLER: 'บัญชีนี้ยังไม่ได้เปิดร้านในตลาดสด',
  SHOP_UNAVAILABLE: 'ร้านถูกระงับชั่วคราว ติดต่อทีมงานได้ที่หน้าช่วยเหลือ',
  SHOP_CLOSED: 'ร้านปิดอยู่ กดเปิดร้านก่อนนะ',
  NOT_MOBILE_SHOP: 'ร้านนี้เป็นร้านประจำที่ ไม่ต้องส่งตำแหน่งสด',
  LOCATION_REQUIRED: 'ร้านเคลื่อนที่ต้องใช้ตำแหน่งตอนเปิดร้าน เปิด GPS แล้วลองใหม่นะ',
  INVALID_LOCATION: 'ตำแหน่งไม่ถูกต้อง เปิด GPS แล้วลองใหม่นะ',
  INVALID_CLOSING_TIME: 'เวลาปิดร้านต้องห่างจากตอนนี้อย่างน้อย 5 นาที และไม่เกิน 24 ชั่วโมง',
  ORDER_NOT_FOUND: 'ไม่พบออเดอร์นี้',
  INVALID_TRANSITION: 'สถานะออเดอร์เปลี่ยนไปแล้ว รีเฟรชแล้วลองใหม่นะ',
  LISTING_NOT_FOUND: 'ไม่พบสินค้านี้ หรือสินค้าไม่ใช่ของร้านคุณ',
  LISTING_HAS_ACTIVE_ORDERS: 'สินค้านี้ยังมีออเดอร์ที่ทำไม่เสร็จ',
  TOO_MANY_IMAGES: 'รูปสินค้ารวมกันได้ไม่เกิน 5 รูป',
  IMAGE_NOT_FOUND: 'ไม่พบรูปนี้ในสินค้า รีเฟรชแล้วลองใหม่นะ',
  TOO_MANY_OPTION_GROUPS: 'ตั้งกลุ่มตัวเลือกได้ไม่เกิน 10 กลุ่ม',
  TOO_MANY_OPTIONS: 'แต่ละกลุ่มมีตัวเลือกได้ไม่เกิน 30 อย่าง',
  OPTION_GROUP_NOT_FOUND: 'ไม่พบกลุ่มตัวเลือกนี้ รีเฟรชแล้วลองใหม่นะ',
  OPTION_NOT_FOUND: 'ไม่พบตัวเลือกนี้ รีเฟรชแล้วลองใหม่นะ',
  LAST_OPTION: 'กลุ่มต้องมีตัวเลือกอย่างน้อย 1 อย่าง',
};

const GENERIC_MESSAGES = new Set<string>([
  THAI_ERROR_MESSAGES.UNKNOWN_ERROR,
  THAI_ERROR_MESSAGES.FORBIDDEN,
  THAI_ERROR_MESSAGES.NOT_FOUND,
  THAI_ERROR_MESSAGES.CONFLICT,
]);

/** แทนข้อความกลางด้วยข้อความเฉพาะของ code (ข้อความไทยจาก server เก็บไว้ตามเดิม) */
const withSellerMessage = async <T>(promise: Promise<ApiResult<T>>): Promise<ApiResult<T>> => {
  const result = await promise;
  if (!result.success && FM_SELLER_MESSAGES[result.code] && GENERIC_MESSAGES.has(result.message)) {
    return { ...result, message: FM_SELLER_MESSAGES[result.code] };
  }
  return result;
};

// =====================================================
// ชนิดข้อมูล — สถานะร้าน
// =====================================================

export interface ShopLocation {
  latitude: number;
  longitude: number;
  label: string | null;
  updated_at: string | null;
  is_live: boolean;
  /** last_known มีเฉพาะมุมมองเจ้าของร้าน (ร้านปิดแล้ว) */
  source: 'live' | 'pinned' | 'fixed' | 'last_known';
}

export interface ShopPresence {
  is_mobile: boolean;
  is_open: boolean;
  status: 'open' | 'closed';
  status_text: string;
  closed_message: string | null;
  can_order: boolean;
  opened_at: string | null;
  closes_at: string | null;
  location_label: string | null;
  live_location_sharing: boolean;
  location_updated_at: string | null;
  location: ShopLocation | null;
}

export interface OwnerPresence extends ShopPresence {
  shop_id: number;
  shop_name: string;
  has_fixed_location: boolean;
  followers_count: number;
  /** ส่งตำแหน่งสดทุกกี่วินาที (30) */
  live_send_interval_seconds: number;
  /** ไม่ได้รับตำแหน่งเกินกี่นาที server ปิดร้านให้ (30) */
  live_stale_minutes: number;
}

export interface OpenShopBody {
  /** ร้านเคลื่อนที่ต้องส่งทั้งคู่ · ร้านประจำที่ server ไม่ใช้ */
  latitude?: number;
  longitude?: number;
  /** ≤ 150 ตัวอักษร */
  location_label?: string;
  /** "HH:MM" = วันนี้ (ผ่านไปแล้ว = พรุ่งนี้) หรือ ISO · ห่างจากตอนนี้ 5 นาที–24 ชม. */
  closes_at?: string;
  live_location_sharing?: boolean;
}

export interface OpenShopResult extends OwnerPresence {
  /** true = เพิ่งเปิด (ผู้ติดตามได้แจ้งเตือน) · false = อัปเดตร้านที่เปิดอยู่แล้ว */
  just_opened: boolean;
  pickups_updated: number;
}

export interface CloseShopResult extends OwnerPresence {
  was_open: boolean;
  /** ออเดอร์ที่ยังทำไม่เสร็จ (ยังทำต่อได้หลังปิดร้าน) */
  active_orders: number;
}

export interface ShopLocationBody {
  latitude: number;
  longitude: number;
  location_label?: string;
  accuracy?: number;
}

export interface ShopLocationResult {
  is_open: boolean;
  location: ShopLocation | null;
  location_updated_at: string | null;
  pickups_updated: number;
  next_send_seconds: number;
}

// =====================================================
// ชนิดข้อมูล — ร้าน / ภาพรวม
// =====================================================

export interface FmSellerProfile {
  id: number;
  shop_name: string;
  shop_description: string | null;
  shop_image: string | null;
  phone: string | null;
  address: string | null;
  province: string | null;
  district: string | null;
  sub_district: string | null;
  latitude: number | null;
  longitude: number | null;
  has_pickup_location: boolean;
  status: string;
  status_label: string;
  is_verified: boolean;
  is_visible_to_buyers: boolean;
  subscription_type: string | null;
  subscription_expires_at: string | null;
  has_paid_subscription: boolean;
  can_create_listing: boolean;
  /** ค่า GP ที่ค้างชำระ (จากออเดอร์เก็บเงินปลายทาง) */
  outstanding_gp_debt: number;
  is_mobile: boolean;
  is_open: boolean;
  presence: ShopPresence;
}

export interface FmSellerStats {
  total_listings: number;
  total_sales: number;
  /** รายรับสุทธิของออเดอร์ที่สำเร็จแล้ว */
  total_revenue: number;
  rating_average: number;
  rating_count: number;
  pending_orders: number;
  orders_by_status: Record<string, number>;
}

export interface FmSellerDashboard extends FmSellerProfile {
  stats: FmSellerStats;
}

// =====================================================
// ชนิดข้อมูล — ออเดอร์
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

export type FmOrderFilter = FmOrderStatus | 'all';

/** ปุ่มที่ร้านกดได้ — แสดงตาม allowed_actions ของออเดอร์เท่านั้น */
export type FmSellerAction = 'accept' | 'prepare' | 'ready' | 'handover' | 'cancel';

export interface FmSelectedOption {
  group_id: number;
  group_name: string;
  option_id: number;
  name: string;
  price_delta: number;
}

export interface FmOrderItem {
  id: number;
  listing_id: number;
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

export interface FmSellerOrder {
  id: number;
  order_number: string;
  order_status: FmOrderStatus;
  status_label: string;
  payment_method: string;
  payment_method_label: string;
  payment_status: string;
  payment_status_label: string;
  delivery_type: 'pickup' | 'rider';
  total_amount: number;
  delivery_fee: number;
  grand_total: number;
  delivery_distance_km: number | null;
  delivery_address: string | null;
  delivery_notes: string | null;
  cancel_reason: string | null;
  cancelled_by: string | null;
  allowed_actions: string[];
  subtotal: number;
  items_count: number;
  items: FmOrderItem[];
  items_summary: string | null;
  buyer: { id: number; name: string; phone: string | null } | null;
  rider_job: {
    id: number;
    status: string;
    rider_name: string | null;
    rider_phone: string | null;
    tracking_url: string | null;
  } | null;
  gp_rate: number | null;
  platform_fee: number;
  seller_earning: number;
  created_at: string | null;
  accepted_at: string | null;
  ready_at: string | null;
  delivered_at: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
}

// =====================================================
// ชนิดข้อมูล — สินค้า + ตัวเลือก
// =====================================================

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

export type FmListingStatus = 'active' | 'sold_out' | 'draft' | 'expired' | 'suspended';

export interface FmOwnerListing {
  id: number;
  slug: string | null;
  title: string;
  description: string | null;
  price: number;
  compare_at_price: number | null;
  unit: string | null;
  quantity_available: number;
  /** path บน server (/storage/...) — แสดงผลผ่าน fmImageUrl() */
  main_image_url: string | null;
  images: string[];
  track_stock: boolean;
  max_order_quantity: number;
  has_options: boolean | null;
  status: FmListingStatus | string;
  is_available: boolean;
  category_id: number | null;
  category: { id: number; name: string; icon: string | null } | null;
  view_count: number;
  order_count: number;
  option_groups: FmOptionGroup[];
  updated_at: string | null;
}

export interface FmListingUpdateBody {
  title?: string;
  price?: number;
  quantity_available?: number;
  is_available?: boolean;
  track_stock?: boolean;
}

export interface FmOptionInput {
  id?: number;
  name: string;
  price_delta: number;
  is_available?: boolean;
  sort_order?: number;
}

export interface FmGroupInput {
  id?: number;
  name: string;
  selection_type: 'single' | 'multi';
  is_required: boolean;
  min_select?: number | null;
  /** null / 0 = ไม่จำกัด · single ถูกบังคับเป็น 1 ที่ server */
  max_select?: number | null;
  sort_order?: number;
  options: FmOptionInput[];
}

/** ขีดจำกัดตาม server (แยกไฟล์ให้โค้ดที่ไม่มี network ใช้ได้ เช่น ตัวตรวจร่างตัวเลือก) */
export { FM_LIMITS } from './fmLimits';

// =====================================================
// ตัวช่วย
// =====================================================

/** path รูปจาก server (/storage/...) → URL เต็มที่แสดงได้ (ไม่ใช่ https = null) */
export const fmImageUrl = (path: string | null | undefined): string | null => {
  if (typeof path !== 'string' || path.trim() === '') return null;
  const value = path.trim();
  if (/^https:\/\//i.test(value)) return value;
  if (value.startsWith('/') && !value.startsWith('//')) return `${APP_INFO.WEBSITE}${value}`;
  return null;
};

const toBool = (value: unknown): boolean => value === true || value === 1 || value === '1';

const toText = (value: unknown): string | null =>
  typeof value === 'string' && value.trim() !== '' ? value : null;

const normalizeLocation = (raw: any): ShopLocation | null => {
  if (!raw || typeof raw !== 'object') return null;
  const latitude = num(raw.latitude, NaN);
  const longitude = num(raw.longitude, NaN);
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;
  return {
    latitude,
    longitude,
    label: toText(raw.label),
    updated_at: toText(raw.updated_at),
    is_live: toBool(raw.is_live),
    source: raw.source === 'live' || raw.source === 'pinned' || raw.source === 'fixed' ? raw.source : 'last_known',
  };
};

/** กันคีย์หาย/ชนิดผิด — ใช้กับ presence ทุกจุด */
export const normalizePresence = (raw: any): ShopPresence => ({
  is_mobile: toBool(raw?.is_mobile),
  is_open: toBool(raw?.is_open),
  status: raw?.status === 'open' ? 'open' : 'closed',
  status_text: toText(raw?.status_text) || (toBool(raw?.is_open) ? 'เปิดอยู่' : 'ปิดอยู่'),
  closed_message: toText(raw?.closed_message),
  can_order: toBool(raw?.can_order),
  opened_at: toText(raw?.opened_at),
  closes_at: toText(raw?.closes_at),
  location_label: toText(raw?.location_label),
  live_location_sharing: toBool(raw?.live_location_sharing),
  location_updated_at: toText(raw?.location_updated_at),
  location: normalizeLocation(raw?.location),
});

export const normalizeOwnerPresence = (raw: any): OwnerPresence => ({
  ...normalizePresence(raw),
  shop_id: num(raw?.shop_id),
  shop_name: toText(raw?.shop_name) || 'ร้านของฉัน',
  has_fixed_location: toBool(raw?.has_fixed_location),
  followers_count: num(raw?.followers_count),
  live_send_interval_seconds: Math.max(15, num(raw?.live_send_interval_seconds, 30)),
  live_stale_minutes: Math.max(1, num(raw?.live_stale_minutes, 30)),
});

const normalizeDashboard = (raw: any): FmSellerDashboard => {
  const stats = raw?.stats || {};
  const byStatus: Record<string, number> = {};
  if (stats.orders_by_status && typeof stats.orders_by_status === 'object') {
    for (const [key, value] of Object.entries(stats.orders_by_status as Record<string, unknown>)) {
      byStatus[key] = num(value);
    }
  }
  return {
    ...(raw as FmSellerProfile),
    shop_name: toText(raw?.shop_name) || 'ร้านของฉัน',
    is_visible_to_buyers: raw?.is_visible_to_buyers !== false,
    outstanding_gp_debt: num(raw?.outstanding_gp_debt),
    is_mobile: toBool(raw?.is_mobile),
    is_open: toBool(raw?.is_open),
    has_pickup_location: toBool(raw?.has_pickup_location),
    presence: normalizePresence(raw?.presence),
    stats: {
      total_listings: num(stats.total_listings),
      total_sales: num(stats.total_sales),
      total_revenue: num(stats.total_revenue),
      rating_average: num(stats.rating_average),
      rating_count: num(stats.rating_count),
      pending_orders: num(stats.pending_orders),
      orders_by_status: byStatus,
    },
  };
};

const normalizeOption = (raw: any): FmOption => ({
  id: num(raw?.id),
  group_id: num(raw?.group_id),
  name: toText(raw?.name) || '',
  price_delta: num(raw?.price_delta),
  image_url: toText(raw?.image_url),
  is_available: raw?.is_available !== false,
  sort_order: num(raw?.sort_order),
});

export const normalizeOptionGroups = (raw: unknown): FmOptionGroup[] =>
  (Array.isArray(raw) ? raw : []).map((g: any) => ({
    id: num(g?.id),
    name: toText(g?.name) || '',
    selection_type: g?.selection_type === 'multi' ? 'multi' : 'single',
    is_required: toBool(g?.is_required),
    min_select: num(g?.min_select),
    max_select: g?.max_select === null || g?.max_select === undefined ? null : num(g.max_select),
    rule_label: toText(g?.rule_label),
    sort_order: num(g?.sort_order),
    options: (Array.isArray(g?.options) ? g.options : []).map(normalizeOption),
  }));

export const normalizeListing = (raw: any): FmOwnerListing => ({
  id: num(raw?.id),
  slug: toText(raw?.slug),
  title: toText(raw?.title) || 'สินค้า',
  description: toText(raw?.description),
  price: num(raw?.price),
  compare_at_price: raw?.compare_at_price === null || raw?.compare_at_price === undefined ? null : num(raw.compare_at_price),
  unit: toText(raw?.unit),
  quantity_available: num(raw?.quantity_available),
  main_image_url: toText(raw?.main_image_url),
  images: Array.isArray(raw?.images) ? raw.images.filter((u: unknown) => typeof u === 'string' && u) : [],
  track_stock: raw?.track_stock !== false,
  max_order_quantity: num(raw?.max_order_quantity, 999),
  has_options: raw?.has_options === null || raw?.has_options === undefined ? null : toBool(raw.has_options),
  status: toText(raw?.status) || 'active',
  is_available: raw?.is_available !== false,
  category_id: raw?.category_id === null || raw?.category_id === undefined ? null : num(raw.category_id),
  category: raw?.category && typeof raw.category === 'object'
    ? { id: num(raw.category.id), name: toText(raw.category.name) || '', icon: toText(raw.category.icon) }
    : null,
  view_count: num(raw?.view_count),
  order_count: num(raw?.order_count),
  option_groups: normalizeOptionGroups(raw?.option_groups),
  updated_at: toText(raw?.updated_at),
});

export const normalizeOrder = (raw: any): FmSellerOrder => ({
  ...(raw as FmSellerOrder),
  id: num(raw?.id),
  order_number: toText(raw?.order_number) || `#${num(raw?.id)}`,
  order_status: (toText(raw?.order_status) || 'pending') as FmOrderStatus,
  status_label: toText(raw?.status_label) || '',
  payment_method_label: toText(raw?.payment_method_label) || '',
  payment_status_label: toText(raw?.payment_status_label) || '',
  delivery_type: raw?.delivery_type === 'rider' ? 'rider' : 'pickup',
  total_amount: num(raw?.total_amount),
  delivery_fee: num(raw?.delivery_fee),
  grand_total: num(raw?.grand_total),
  subtotal: num(raw?.subtotal, num(raw?.total_amount)),
  items_count: num(raw?.items_count),
  platform_fee: num(raw?.platform_fee),
  seller_earning: num(raw?.seller_earning),
  allowed_actions: Array.isArray(raw?.allowed_actions) ? raw.allowed_actions.filter((a: unknown) => typeof a === 'string') : [],
  items: (Array.isArray(raw?.items) ? raw.items : []).map((item: any) => ({
    ...(item as FmOrderItem),
    id: num(item?.id),
    title: toText(item?.title) || 'สินค้า',
    quantity: num(item?.quantity, 1),
    unit_price: num(item?.unit_price),
    line_total: num(item?.line_total),
    options_label: toText(item?.options_label),
    note: toText(item?.note),
    selected_options: Array.isArray(item?.selected_options) ? item.selected_options : [],
  })),
});

const withPagination = (meta: Record<string, unknown> | undefined): Pagination => {
  const m = (meta?.meta as Record<string, unknown>) || {};
  return {
    current_page: num(m.current_page, 1),
    last_page: num(m.last_page, 1),
    per_page: num(m.per_page, 15),
    total: num(m.total),
  };
};

// =====================================================
// ร้าน / สถานะร้าน
// =====================================================

/** GET /fresh-market/seller/dashboard — 403 NOT_SELLER = ยังไม่มีร้านตลาดสด */
export const getFmSellerDashboard = async (): Promise<ApiResult<FmSellerDashboard>> => {
  const result = await withSellerMessage(apiGet<any>('/fresh-market/seller/dashboard'));
  return result.success ? { ...result, data: normalizeDashboard(result.data) } : result;
};

/** GET /fresh-market/seller/presence */
export const getFmSellerPresence = async (): Promise<ApiResult<OwnerPresence>> => {
  const result = await withSellerMessage(apiGet<any>('/fresh-market/seller/presence'));
  return result.success ? { ...result, data: normalizeOwnerPresence(result.data) } : result;
};

/**
 * POST /fresh-market/seller/open — "เปิดร้านที่นี่วันนี้" (เรียกซ้ำตอนร้านเปิดอยู่ = อัปเดตตำแหน่ง/เวลาปิด/การแชร์)
 * error: 422 LOCATION_REQUIRED | INVALID_LOCATION | INVALID_CLOSING_TIME | VALIDATION_ERROR · 403 NOT_SELLER | SHOP_UNAVAILABLE
 */
export const openFmShop = async (body: OpenShopBody): Promise<ApiResult<OpenShopResult>> => {
  const result = await withSellerMessage(apiPost<any>('/fresh-market/seller/open', body));
  if (!result.success) return result;
  return {
    ...result,
    data: {
      ...normalizeOwnerPresence(result.data),
      just_opened: toBool(result.data?.just_opened),
      pickups_updated: num(result.data?.pickups_updated),
    },
  };
};

/**
 * POST /fresh-market/seller/location — ตำแหน่งสดของร้านเคลื่อนที่ (ทุก 30 วินาที, throttle 6/นาที)
 * error: 409 SHOP_CLOSED (หยุดส่ง) · 422 NOT_MOBILE_SHOP | INVALID_LOCATION · 429 TOO_MANY_REQUESTS
 */
export const sendFmShopLocation = async (body: ShopLocationBody): Promise<ApiResult<ShopLocationResult>> => {
  const result = await withSellerMessage(apiPost<any>('/fresh-market/seller/location', body, { timeout: 15000 }));
  if (!result.success) return result;
  return {
    ...result,
    data: {
      is_open: toBool(result.data?.is_open),
      location: normalizeLocation(result.data?.location),
      location_updated_at: toText(result.data?.location_updated_at),
      pickups_updated: num(result.data?.pickups_updated),
      next_send_seconds: Math.max(15, num(result.data?.next_send_seconds, 30)),
    },
  };
};

/** POST /fresh-market/seller/close — ออเดอร์ที่รับไว้แล้วยังทำต่อได้ (active_orders) */
export const closeFmShop = async (): Promise<ApiResult<CloseShopResult>> => {
  const result = await withSellerMessage(apiPost<any>('/fresh-market/seller/close'));
  if (!result.success) return result;
  return {
    ...result,
    data: {
      ...normalizeOwnerPresence(result.data),
      was_open: toBool(result.data?.was_open),
      active_orders: num(result.data?.active_orders),
    },
  };
};

/**
 * PUT /fresh-market/seller/profile {is_mobile} — สลับเป็นร้านเคลื่อนที่ (รถเข็น/ตลาดนัด) หรือร้านประจำที่
 * สลับเป็นร้านเคลื่อนที่ = ร้านปิดจนกว่าจะกดเปิดใหม่ · กลับเป็นร้านประจำ = เปิดที่ที่อยู่ร้าน
 */
export const setFmShopMobile = (isMobile: boolean): Promise<ApiResult<FmSellerProfile>> =>
  withSellerMessage(apiPut<FmSellerProfile>('/fresh-market/seller/profile', { is_mobile: isMobile }));

// =====================================================
// ออเดอร์
// =====================================================

/** GET /fresh-market/seller/orders?status=&page= (หน้าละ 15 รายการ ใหม่สุดก่อน) */
export const getFmSellerOrders = async (
  params: { status?: FmOrderFilter; page?: number } = {}
): Promise<ApiResult<{ orders: FmSellerOrder[]; pagination: Pagination }>> => {
  const query: Record<string, unknown> = { page: params.page || 1 };
  if (params.status && params.status !== 'all') query.status = params.status;
  const result = await withSellerMessage(apiGet<any>('/fresh-market/seller/orders', query));
  if (!result.success) return result;
  const rows = Array.isArray(result.data) ? result.data : [];
  return {
    ...result,
    data: { orders: rows.map(normalizeOrder), pagination: withPagination(result.meta) },
  };
};

/** GET /fresh-market/seller/orders/{id} */
export const getFmSellerOrder = async (orderId: number): Promise<ApiResult<FmSellerOrder>> => {
  const result = await withSellerMessage(apiGet<any>(`/fresh-market/seller/orders/${orderId}`));
  return result.success ? { ...result, data: normalizeOrder(result.data) } : result;
};

/** POST /fresh-market/seller/orders/{id}/action — cancel ต้องมีเหตุผล · คืนออเดอร์ล่าสุด */
export const fmSellerOrderAction = async (
  orderId: number,
  action: FmSellerAction,
  reason?: string
): Promise<ApiResult<FmSellerOrder>> => {
  const body: Record<string, unknown> = { action };
  if (action === 'cancel') body.reason = (reason || '').trim();
  const result = await withSellerMessage(apiPost<any>(`/fresh-market/seller/orders/${orderId}/action`, body));
  return result.success ? { ...result, data: normalizeOrder(result.data) } : result;
};

// =====================================================
// สินค้า
// =====================================================

/** GET /fresh-market/seller/listings?status=&q=&page=&per_page= */
export const getFmSellerListings = async (
  params: { status?: FmListingStatus; q?: string; page?: number; per_page?: number } = {}
): Promise<ApiResult<{ listings: FmOwnerListing[]; pagination: Pagination }>> => {
  const query: Record<string, unknown> = { page: params.page || 1, per_page: params.per_page || 20 };
  if (params.status) query.status = params.status;
  if (params.q && params.q.trim()) query.q = params.q.trim().slice(0, 100);
  const result = await withSellerMessage(apiGet<any>('/fresh-market/seller/listings', query));
  if (!result.success) return result;
  const rows = Array.isArray(result.data) ? result.data : [];
  return {
    ...result,
    data: { listings: rows.map(normalizeListing), pagination: withPagination(result.meta) },
  };
};

/** GET /fresh-market/seller/listings/{id} */
export const getFmSellerListing = async (listingId: number): Promise<ApiResult<FmOwnerListing>> => {
  const result = await withSellerMessage(apiGet<any>(`/fresh-market/seller/listings/${listingId}`));
  return result.success ? { ...result, data: normalizeListing(result.data) } : result;
};

/**
 * PUT /fresh-market/listings/{id} — ราคา / จำนวน / เปิด-ปิดขาย (ไม่ส่ง option_groups = ไม่แตะตัวเลือก)
 * สินค้าถูกระงับ (suspended) server ไม่เปลี่ยน is_available ให้
 */
export const updateFmListing = async (listingId: number, body: FmListingUpdateBody): Promise<ApiResult<FmOwnerListing>> => {
  const result = await withSellerMessage(apiPut<any>(`/fresh-market/listings/${listingId}`, body));
  return result.success ? { ...result, data: normalizeListing(result.data) } : result;
};

/** POST /fresh-market/listings/{id}/images (multipart images[] ≤ 5MB/รูป · รวมไม่เกิน 5 รูป) */
export const addFmListingImages = async (listingId: number, uris: string[]): Promise<ApiResult<FmOwnerListing>> => {
  const form = new FormData();
  uris.forEach((uri, i) => {
    form.append('images[]', fileFromUri(uri, `listing-${listingId}-${Date.now()}-${i}`) as unknown as Blob);
  });
  const result = await withSellerMessage(
    apiUpload<any>(`/fresh-market/listings/${listingId}/images`, form, {
      fallbackMessage: 'อัปโหลดรูปไม่สำเร็จ ลองใหม่อีกครั้งนะ',
    })
  );
  return result.success ? { ...result, data: normalizeListing(result.data) } : result;
};

/** DELETE /fresh-market/listings/{id}/images {url} — url = path เดิมจาก server */
export const removeFmListingImage = async (listingId: number, url: string): Promise<ApiResult<FmOwnerListing>> => {
  const result = await withSellerMessage(apiDelete<any>(`/fresh-market/listings/${listingId}/images`, { url }));
  return result.success ? { ...result, data: normalizeListing(result.data) } : result;
};

/** POST /fresh-market/listings/{id}/images/main {url} */
export const setFmListingMainImage = async (listingId: number, url: string): Promise<ApiResult<FmOwnerListing>> => {
  const result = await withSellerMessage(apiPost<any>(`/fresh-market/listings/${listingId}/images/main`, { url }));
  return result.success ? { ...result, data: normalizeListing(result.data) } : result;
};

/**
 * PUT /fresh-market/listings/{id}/option-groups — ส่งกลุ่มตัวเลือกทั้งชุด
 * มี id = แก้ · ไม่มี id = สร้างใหม่ · กลุ่ม/ตัวเลือกที่ไม่ได้ส่ง = ลบ · [] = ลบทั้งหมด
 */
export const saveFmOptionGroups = async (
  listingId: number,
  groups: FmGroupInput[]
): Promise<ApiResult<{ listing_id: number; option_groups: FmOptionGroup[] }>> => {
  const result = await withSellerMessage(
    apiPut<any>(`/fresh-market/listings/${listingId}/option-groups`, { option_groups: groups })
  );
  if (!result.success) return result;
  return {
    ...result,
    data: { listing_id: num(result.data?.listing_id, listingId), option_groups: normalizeOptionGroups(result.data?.option_groups) },
  };
};

// =====================================================
// เช็คว่าเป็นคนขายในตลาดสดหรือไม่ (cache ต่อ session)
// =====================================================

let fmSellerCache: { userId: number; isSeller: boolean; at: number } | null = null;
const FM_SELLER_CACHE_MS = 5 * 60 * 1000;

/**
 * ผู้ใช้คนนี้มีร้านในตลาดสดหรือไม่ (ใช้แสดงทางเข้า "ร้านของฉัน")
 * - 403 NOT_SELLER → false · เน็ตหลุด → false แต่ไม่ cache (เช็คใหม่รอบหน้า)
 */
export const checkIsFreshMarketSeller = async (userId: number, force: boolean = false): Promise<boolean> => {
  if (!force && fmSellerCache && fmSellerCache.userId === userId && Date.now() - fmSellerCache.at < FM_SELLER_CACHE_MS) {
    return fmSellerCache.isSeller;
  }
  const result = await apiGet<unknown>('/fresh-market/seller/profile');
  if (result.success) {
    fmSellerCache = { userId, isSeller: true, at: Date.now() };
    return true;
  }
  if (result.code === 'NOT_SELLER' || result.status === 403) {
    fmSellerCache = { userId, isSeller: false, at: Date.now() };
  }
  return false;
};

/** ล้าง cache (เรียกตอน logout / เพิ่งสมัครร้าน) */
export const resetFreshMarketSellerCache = (): void => {
  fmSellerCache = null;
};
