/**
 * Taladsod Seller Manage API — ของร้านตลาดสดที่เดิมต้องเปิดเว็บ (สมัครร้าน / ตั้งค่าร้าน / ลงขาย / แก้-ลบสินค้า / รายได้)
 *
 * ทุก endpoint อยู่ใต้ /api/v1/fresh-market (ตรวจข้อมูลชุดเดียวกับฟอร์มเว็บ · ข้อความผิดพลาดเป็นภาษาไทยจาก server)
 *   - สมัครร้าน:   POST /seller/register (409 SELLER_EXISTS = สมัครไว้แล้ว)
 *   - ตั้งค่าร้าน: GET|PUT /seller/profile
 *   - ฟอร์มสินค้า: GET /seller/listing-form (หมวดหมู่ หน่วย ความสด เพดานแคชแบ็ค อัตรา GP โควต้า)
 *   - ลงขาย:     POST /listings (multipart: รูป images[] + option_groups เป็น JSON)
 *   - แก้/ลบ:    PUT /listings/{id} · DELETE /listings/{id} (409 LISTING_HAS_ACTIVE_ORDERS)
 *   - รายได้:     GET /seller/earnings (ตัวเลขชุดเดียวกับหน้าเว็บ /taladsod/seller/earnings)
 *
 * แยกไฟล์จาก taladsodSellerApi.ts (สถานะร้าน/ออเดอร์/ตัวเลือก) — ใช้ตัวแปลงข้อมูลตัวเดียวกัน
 * แอปไม่คำนวณเงินเอง: ตัวเลขรายได้/GP มาจาก server ทั้งหมด (ตัวอย่าง "ร้านรับจริง" บนฟอร์มเป็นแค่การประมาณ)
 */

import {
  apiDelete,
  apiGet,
  apiPost,
  apiPut,
  apiUpload,
  fileFromUri,
  num,
  type ApiResult,
} from './client';
import {
  FM_SELLER_MESSAGES,
  normalizeListing,
  resetFreshMarketSellerCache,
  type FmGroupInput,
  type FmOwnerListing,
  type FmSellerProfile,
} from './taladsodSellerApi';

// =====================================================
// ชนิดข้อมูล
// =====================================================

/** ข้อมูลร้านที่กรอกได้ (สมัคร / ตั้งค่า) — ตรงกับฟอร์มเว็บ */
export interface FmShopInfoBody {
  shop_name: string;
  shop_description?: string | null;
  /** ตัวเลข + - เว้นวรรค 9–20 ตัว */
  phone: string;
  address: string;
  province?: string | null;
  district?: string | null;
  sub_district?: string | null;
  latitude: number;
  longitude: number;
}

export interface FmRegisterBody extends FmShopInfoBody {
  agree_terms: true;
}

/** สินค้าของร้าน + ช่องที่ฟอร์มแก้ไขต้องใช้เพิ่ม (อินทรีย์ / ความสด / เงินคืน) */
export interface FmOwnerListingFull extends FmOwnerListing {
  is_organic: boolean;
  freshness_level: string | null;
  cashback_percentage: number;
}

export interface FmCategoryOption {
  id: number;
  name: string;
  parent_id: number | null;
}

export interface FmListingForm {
  categories: FmCategoryOption[];
  units: string[];
  freshness_levels: string[];
  max_images: number;
  max_cashback_percent: number;
  /** อัตรา GP ตอนนี้ (%) */
  gp_rate: number;
  gp_free: boolean;
  can_create_listing: boolean;
  limit_message: string | null;
}

/** ช่องข้อมูลสินค้า (ลงขายใหม่ / แก้ไข) */
export interface FmListingDetailsBody {
  title?: string;
  description?: string | null;
  category_id?: number;
  price?: number;
  /** null = ไม่มีราคาก่อนลด (ต้องมากกว่าราคาขาย) */
  compare_at_price?: number | null;
  unit?: string;
  track_stock?: boolean;
  quantity_available?: number;
  is_available?: boolean;
  is_organic?: boolean;
  freshness_level?: string | null;
  cashback_percentage?: number | null;
}

export interface FmCreateListingInput extends FmListingDetailsBody {
  title: string;
  category_id: number;
  price: number;
  unit: string;
  /** uri รูปในเครื่อง (≤ 5 รูป, ≤ 5MB/รูป) — รูปแรก = รูปหลัก */
  imageUris: string[];
  optionGroups: FmGroupInput[];
}

export interface FmEarningsPeriod {
  label: string;
  orders: number;
  /** ยอดขายรวม (ไม่รวมค่าส่ง) */
  gross: number;
  /** ค่าธรรมเนียมแพลตฟอร์ม */
  gp: number;
  /** รายรับสุทธิของร้าน */
  net: number;
}

export interface FmEarnings {
  periods: Record<'today' | 'week' | 'month' | 'all', FmEarningsPeriod>;
  daily: Array<{ date: string; label: string; orders: number; net: number }>;
  pending: { orders: number; held_net: number; cod_to_collect: number };
  gp_debt: number;
  gp_rate: number;
  gp_free: boolean;
  gp_free_until: string | null;
  wallet_balance: number;
  payouts: Array<{ id: number; amount: number; description: string; order_id: number | null; created_at: string | null }>;
  recent_completed: Array<{
    id: number;
    order_number: string;
    title: string | null;
    items_count: number;
    total_amount: number;
    platform_fee: number;
    seller_earning: number;
    payment_method: string;
    payment_method_label: string;
    completed_at: string | null;
  }>;
}

// =====================================================
// ตัวช่วย
// =====================================================

const MANAGE_MESSAGES: Record<string, string> = {
  ...FM_SELLER_MESSAGES,
  SELLER_EXISTS: 'บัญชีนี้สมัครร้านตลาดสดไว้แล้ว',
  LISTING_LIMIT: 'ลงขายเพิ่มไม่ได้ในขณะนี้',
  SELLER_SUSPENDED: 'ร้านของคุณถูกระงับหรือปิดอยู่ ไม่สามารถลงขายได้',
};

/** server ส่งข้อความไทยมาเองเกือบทุกกรณี — เติมเฉพาะเมื่อไม่มีข้อความ */
const withMessage = async <T>(promise: Promise<ApiResult<T>>): Promise<ApiResult<T>> => {
  const result = await promise;
  if (!result.success && !result.message && MANAGE_MESSAGES[result.code]) {
    return { ...result, message: MANAGE_MESSAGES[result.code] };
  }
  return result;
};

const text = (value: unknown): string => (typeof value === 'string' ? value : '');

const normalizeEarnings = (raw: any): FmEarnings => {
  const period = (key: string, label: string): FmEarningsPeriod => {
    const p = raw?.periods?.[key] || {};
    return {
      label: text(p.label) || label,
      orders: num(p.orders),
      gross: num(p.gross),
      gp: num(p.gp),
      net: num(p.net),
    };
  };
  return {
    periods: {
      today: period('today', 'วันนี้'),
      week: period('week', '7 วันล่าสุด'),
      month: period('month', 'เดือนนี้'),
      all: period('all', 'ทั้งหมด'),
    },
    daily: (Array.isArray(raw?.daily) ? raw.daily : []).map((d: any) => ({
      date: text(d?.date),
      label: text(d?.label),
      orders: num(d?.orders),
      net: num(d?.net),
    })),
    pending: {
      orders: num(raw?.pending?.orders),
      held_net: num(raw?.pending?.held_net),
      cod_to_collect: num(raw?.pending?.cod_to_collect),
    },
    gp_debt: num(raw?.gp_debt),
    gp_rate: num(raw?.gp_rate),
    gp_free: raw?.gp_free === true,
    gp_free_until: text(raw?.gp_free_until) || null,
    wallet_balance: num(raw?.wallet_balance),
    payouts: (Array.isArray(raw?.payouts) ? raw.payouts : []).map((p: any) => ({
      id: num(p?.id),
      amount: num(p?.amount),
      description: text(p?.description),
      order_id: p?.order_id === null || p?.order_id === undefined ? null : num(p.order_id),
      created_at: text(p?.created_at) || null,
    })),
    recent_completed: (Array.isArray(raw?.recent_completed) ? raw.recent_completed : []).map((o: any) => ({
      id: num(o?.id),
      order_number: text(o?.order_number) || `#${num(o?.id)}`,
      title: text(o?.title) || null,
      items_count: num(o?.items_count, 1),
      total_amount: num(o?.total_amount),
      platform_fee: num(o?.platform_fee),
      seller_earning: num(o?.seller_earning),
      payment_method: text(o?.payment_method),
      payment_method_label: text(o?.payment_method_label),
      completed_at: text(o?.completed_at) || null,
    })),
  };
};

const normalizeListingFull = (raw: any): FmOwnerListingFull => ({
  ...normalizeListing(raw),
  is_organic: raw?.is_organic === true || raw?.is_organic === 1 || raw?.is_organic === '1',
  freshness_level: text(raw?.freshness_level) || null,
  cashback_percentage: Math.max(0, num(raw?.cashback_percentage)),
});

const normalizeForm = (raw: any): FmListingForm => ({
  categories: (Array.isArray(raw?.categories) ? raw.categories : [])
    .map((c: any) => ({
      id: num(c?.id),
      name: text(c?.name),
      parent_id: c?.parent_id === null || c?.parent_id === undefined ? null : num(c.parent_id),
    }))
    .filter((c: FmCategoryOption) => c.id > 0 && c.name !== ''),
  units: (Array.isArray(raw?.units) ? raw.units : []).filter((u: unknown) => typeof u === 'string' && u),
  freshness_levels: (Array.isArray(raw?.freshness_levels) ? raw.freshness_levels : []).filter(
    (u: unknown) => typeof u === 'string' && u
  ),
  max_images: Math.max(1, num(raw?.max_images, 5)),
  max_cashback_percent: Math.max(0, num(raw?.max_cashback_percent)),
  gp_rate: Math.max(0, num(raw?.gp_rate)),
  gp_free: raw?.gp_free === true,
  can_create_listing: raw?.can_create_listing !== false,
  limit_message: text(raw?.limit_message) || null,
});

// =====================================================
// ร้าน
// =====================================================

/** POST /fresh-market/seller/register — สำเร็จ = ล้าง cache "เป็นคนขายไหม" ให้เมนูร้านโผล่ทันที */
export const registerFmSeller = async (body: FmRegisterBody): Promise<ApiResult<FmSellerProfile>> => {
  const result = await withMessage(apiPost<FmSellerProfile>('/fresh-market/seller/register', body));
  if (result.success || result.code === 'SELLER_EXISTS') resetFreshMarketSellerCache();
  return result;
};

/** GET /fresh-market/seller/profile — 403 NOT_SELLER */
export const getFmSellerProfile = (): Promise<ApiResult<FmSellerProfile>> =>
  withMessage(apiGet<FmSellerProfile>('/fresh-market/seller/profile'));

/** PUT /fresh-market/seller/profile — ส่งช่องว่างเป็น null เพื่อลบค่าเดิม */
export const updateFmSellerProfile = (body: FmShopInfoBody): Promise<ApiResult<FmSellerProfile>> =>
  withMessage(apiPut<FmSellerProfile>('/fresh-market/seller/profile', body));

// =====================================================
// สินค้า
// =====================================================

/** GET /fresh-market/seller/listings/{id} — รวมช่องอินทรีย์/ความสด/เงินคืน สำหรับฟอร์มแก้ไข */
export const getFmSellerListingFull = async (listingId: number): Promise<ApiResult<FmOwnerListingFull>> => {
  const result = await withMessage(apiGet<any>(`/fresh-market/seller/listings/${listingId}`));
  return result.success ? { ...result, data: normalizeListingFull(result.data) } : result;
};

/** GET /fresh-market/seller/listing-form */
export const getFmListingForm = async (): Promise<ApiResult<FmListingForm>> => {
  const result = await withMessage(apiGet<any>('/fresh-market/seller/listing-form'));
  return result.success ? { ...result, data: normalizeForm(result.data) } : result;
};

/**
 * POST /fresh-market/listings (multipart) — สินค้า + รูป + กลุ่มตัวเลือกบันทึกพร้อมกัน
 * signal: ยกเลิกระหว่างอัปโหลด (server อาจบันทึกไปแล้ว — หน้าจอต้องเช็ครายการสินค้าก่อนให้ส่งซ้ำ)
 */
export const createFmListing = async (
  input: FmCreateListingInput,
  options: { signal?: AbortSignal } = {}
): Promise<ApiResult<FmOwnerListingFull>> => {
  const form = new FormData();
  const put = (key: string, value: unknown) => {
    if (value === undefined || value === null) return;
    form.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value));
  };
  put('title', input.title);
  put('description', input.description);
  put('category_id', input.category_id);
  put('price', input.price);
  put('compare_at_price', input.compare_at_price);
  put('unit', input.unit);
  put('track_stock', input.track_stock ?? false);
  if (input.track_stock) put('quantity_available', input.quantity_available);
  put('is_organic', input.is_organic ?? false);
  put('freshness_level', input.freshness_level);
  put('cashback_percentage', input.cashback_percentage);
  if (input.optionGroups.length > 0) form.append('option_groups', JSON.stringify(input.optionGroups));
  const stamp = Date.now();
  input.imageUris.forEach((uri, i) => {
    form.append('images[]', fileFromUri(uri, `listing-new-${stamp}-${i}`) as unknown as Blob);
  });

  const result = await withMessage(
    apiUpload<any>('/fresh-market/listings', form, {
      fallbackMessage: 'ลงขายไม่สำเร็จ ลองใหม่อีกครั้งนะ',
      signal: options.signal,
    })
  );
  return result.success ? { ...result, data: normalizeListingFull(result.data) } : result;
};

/** PUT /fresh-market/listings/{id} — ส่งเฉพาะช่องที่เปลี่ยน */
export const updateFmListingDetails = async (
  listingId: number,
  body: FmListingDetailsBody
): Promise<ApiResult<FmOwnerListingFull>> => {
  const result = await withMessage(apiPut<any>(`/fresh-market/listings/${listingId}`, body));
  return result.success ? { ...result, data: normalizeListingFull(result.data) } : result;
};

/** DELETE /fresh-market/listings/{id} — 409 LISTING_HAS_ACTIVE_ORDERS */
export const deleteFmListing = (listingId: number): Promise<ApiResult<{ id: number }>> =>
  withMessage(apiDelete<{ id: number }>(`/fresh-market/listings/${listingId}`));

// =====================================================
// รายได้
// =====================================================

/** GET /fresh-market/seller/earnings */
export const getFmSellerEarnings = async (): Promise<ApiResult<FmEarnings>> => {
  const result = await withMessage(apiGet<any>('/fresh-market/seller/earnings'));
  return result.success ? { ...result, data: normalizeEarnings(result.data) } : result;
};
