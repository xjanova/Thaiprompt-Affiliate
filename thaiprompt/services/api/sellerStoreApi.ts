/**
 * Seller Store API — สมัครเปิดร้าน + ตั้งค่าร้านในแอป
 * (SellerApplicationApiController / SellerStoreApiController)
 *
 * - สมัครเปิดร้าน: ตรรกะเดียวกับเว็บ /user/seller-apply → คำขอไปรอแอดมินที่หน้าเดียวกัน
 *   state: can_apply | pending | rejected | approved | seller | role_not_eligible
 * - ตั้งค่าร้าน: PUT แก้เฉพาะช่องที่ส่งมา · โลโก้/แบนเนอร์อัปโหลดแยก
 * - ทุกฟังก์ชันคืน ApiResult (ไม่ throw) ข้อความภาษาไทยเสมอ
 */

import { apiGet, apiPost, apiPut, apiUpload, fileFromUri, num, type ApiResult } from './client';

// =====================================================
// สมัครเปิดร้าน
// =====================================================

export type SellerApplicationState =
  | 'can_apply'
  | 'pending'
  | 'rejected'
  | 'approved'
  | 'seller'
  | 'role_not_eligible';

export type SellerBusinessType = 'individual' | 'company';

export interface SellerApplicationRecord {
  id: number;
  status: string;
  store_name: string;
  business_type: SellerBusinessType;
  store_phone: string | null;
  store_description: string | null;
  store_address: string | null;
  store_city: string | null;
  store_state: string | null;
  store_postal_code: string | null;
  company_name: string | null;
  tax_id: string | null;
  submitted_at: string | null;
}

export interface SellerApplicationStatus {
  state: SellerApplicationState;
  can_submit: boolean;
  kyc_approved: boolean;
  rejection_reason: string | null;
  application: SellerApplicationRecord | null;
  contact_email: string | null;
}

export interface SellerApplicationInput {
  store_name: string;
  business_type: SellerBusinessType;
  store_phone: string;
  store_description: string | null;
  store_address: string;
  store_city: string;
  store_state: string;
  store_postal_code: string;
  company_name: string | null;
  tax_id: string | null;
  accept_terms: boolean;
}

const text = (value: unknown): string | null =>
  typeof value === 'string' && value.trim() !== '' ? value : null;

const STATES: SellerApplicationState[] = ['can_apply', 'pending', 'rejected', 'approved', 'seller', 'role_not_eligible'];

const normalizeApplication = (raw: any): SellerApplicationStatus => {
  const app = raw?.application;
  return {
    state: STATES.includes(raw?.state) ? raw.state : 'can_apply',
    can_submit: raw?.can_submit === true,
    kyc_approved: raw?.kyc_approved === true,
    rejection_reason: text(raw?.rejection_reason),
    contact_email: text(raw?.contact_email),
    application:
      app && typeof app === 'object'
        ? {
            id: num(app.id),
            status: typeof app.status === 'string' ? app.status : '',
            store_name: typeof app.store_name === 'string' ? app.store_name : '',
            business_type: app.business_type === 'company' ? 'company' : 'individual',
            store_phone: text(app.store_phone),
            store_description: text(app.store_description),
            store_address: text(app.store_address),
            store_city: text(app.store_city),
            store_state: text(app.store_state),
            store_postal_code: text(app.store_postal_code),
            company_name: text(app.company_name),
            tax_id: text(app.tax_id),
            submitted_at: text(app.submitted_at),
          }
        : null,
  };
};

export const getSellerApplication = async (): Promise<ApiResult<SellerApplicationStatus>> => {
  const result = await apiGet<any>('/seller/application', undefined, {
    fallbackMessage: 'โหลดสถานะคำขอเปิดร้านไม่สำเร็จ ลองใหม่อีกครั้งนะ',
  });
  return result.success ? { ...result, data: normalizeApplication(result.data) } : result;
};

export const submitSellerApplication = async (input: SellerApplicationInput): Promise<ApiResult<SellerApplicationStatus>> => {
  const result = await apiPost<any>('/seller/application', input, {
    fallbackMessage: 'ส่งคำขอไม่สำเร็จ ลองใหม่อีกครั้งนะ',
  });
  if (result.success) return { ...result, data: normalizeApplication(result.data) };
  // 409 = สถานะเปลี่ยนไปแล้ว (เช่น ยื่นจากเว็บไปแล้ว) → แนบสถานะล่าสุดให้หน้าจออัปเดต
  if (result.data && typeof result.data === 'object' && 'state' in result.data) {
    return { ...result, data: normalizeApplication(result.data) };
  }
  return result;
};

// =====================================================
// ตั้งค่าร้าน
// =====================================================

export interface SellerStoreSettings {
  id: number;
  store_name: string;
  store_slug: string | null;
  store_description: string | null;
  store_email: string | null;
  store_phone: string | null;
  store_address: string | null;
  store_city: string | null;
  store_state: string | null;
  store_postal_code: string | null;
  business_type: SellerBusinessType;
  tax_id: string | null;
  company_name: string | null;
  facebook_url: string | null;
  line_oa_id: string | null;
  instagram_url: string | null;
  tiktok_url: string | null;
  minimum_order_amount: number;
  shipping_fee: number;
  free_shipping_threshold: number | null;
  enable_cod: boolean;
  enable_reviews: boolean;
  rider_delivery_enabled: boolean;
  pickup_address: string | null;
  pickup_latitude: number | null;
  pickup_longitude: number | null;
  has_pickup_location: boolean;
  vat_registered: boolean;
  logo_url: string | null;
  banner_url: string | null;
  status: string;
  is_active: boolean;
  is_verified: boolean;
  updated_at: string | null;
}

/** ช่องที่แก้ได้จากแอป (ส่งเฉพาะที่เปลี่ยน) */
export type SellerStorePatch = Partial<
  Pick<
    SellerStoreSettings,
    | 'store_name'
    | 'store_description'
    | 'store_email'
    | 'store_phone'
    | 'store_address'
    | 'store_city'
    | 'store_state'
    | 'store_postal_code'
    | 'business_type'
    | 'tax_id'
    | 'company_name'
    | 'facebook_url'
    | 'line_oa_id'
    | 'instagram_url'
    | 'tiktok_url'
    | 'free_shipping_threshold'
    | 'enable_cod'
    | 'enable_reviews'
    | 'rider_delivery_enabled'
    | 'pickup_address'
    | 'pickup_latitude'
    | 'pickup_longitude'
    | 'vat_registered'
  >
> & {
  minimum_order_amount?: number | null;
  shipping_fee?: number | null;
};

const coord = (value: unknown, limit: number): number | null => {
  if (value === null || value === undefined || value === '') return null;
  const n = Number(value);
  return Number.isFinite(n) && Math.abs(n) <= limit ? n : null;
};

const httpUrl = (value: unknown): string | null =>
  typeof value === 'string' && /^https?:\/\//i.test(value.trim()) ? value.trim() : null;

export const normalizeStoreSettings = (raw: any): SellerStoreSettings => ({
  id: num(raw?.id),
  store_name: typeof raw?.store_name === 'string' ? raw.store_name : '',
  store_slug: text(raw?.store_slug),
  store_description: text(raw?.store_description),
  store_email: text(raw?.store_email),
  store_phone: text(raw?.store_phone),
  store_address: text(raw?.store_address),
  store_city: text(raw?.store_city),
  store_state: text(raw?.store_state),
  store_postal_code: text(raw?.store_postal_code),
  business_type: raw?.business_type === 'company' ? 'company' : 'individual',
  tax_id: text(raw?.tax_id),
  company_name: text(raw?.company_name),
  facebook_url: text(raw?.facebook_url),
  line_oa_id: text(raw?.line_oa_id),
  instagram_url: text(raw?.instagram_url),
  tiktok_url: text(raw?.tiktok_url),
  minimum_order_amount: num(raw?.minimum_order_amount),
  shipping_fee: num(raw?.shipping_fee),
  free_shipping_threshold:
    raw?.free_shipping_threshold === null || raw?.free_shipping_threshold === undefined ? null : num(raw.free_shipping_threshold),
  enable_cod: raw?.enable_cod === true,
  enable_reviews: raw?.enable_reviews === true,
  rider_delivery_enabled: raw?.rider_delivery_enabled === true,
  pickup_address: text(raw?.pickup_address),
  pickup_latitude: coord(raw?.pickup_latitude, 90),
  pickup_longitude: coord(raw?.pickup_longitude, 180),
  has_pickup_location: raw?.has_pickup_location === true,
  vat_registered: raw?.vat_registered === true,
  logo_url: httpUrl(raw?.logo_url),
  banner_url: httpUrl(raw?.banner_url),
  status: typeof raw?.status === 'string' ? raw.status : '',
  is_active: raw?.is_active === true,
  is_verified: raw?.is_verified === true,
  updated_at: text(raw?.updated_at),
});

const mapStore = (result: ApiResult<any>): ApiResult<SellerStoreSettings> =>
  result.success ? { ...result, data: normalizeStoreSettings(result.data) } : result;

export const getSellerStore = async (): Promise<ApiResult<SellerStoreSettings>> =>
  mapStore(await apiGet<any>('/seller/store', undefined, { fallbackMessage: 'โหลดข้อมูลร้านไม่สำเร็จ ลองใหม่อีกครั้งนะ' }));

export const updateSellerStore = async (patch: SellerStorePatch): Promise<ApiResult<SellerStoreSettings>> =>
  mapStore(await apiPut<any>('/seller/store', patch, { fallbackMessage: 'บันทึกการตั้งค่าร้านไม่สำเร็จ ลองใหม่อีกครั้งนะ' }));

/** อัปโหลดโลโก้ (≤2MB) / แบนเนอร์ (≤4MB) */
export const uploadSellerStoreImage = async (
  kind: 'logo' | 'banner',
  uri: string,
  signal?: AbortSignal
): Promise<ApiResult<SellerStoreSettings>> => {
  const form = new FormData();
  const field = kind === 'logo' ? 'store_logo' : 'store_banner';
  form.append(field, fileFromUri(uri, `store-${kind}-${Date.now()}`) as unknown as Blob);
  return mapStore(
    await apiUpload<any>(`/seller/store/${kind}`, form, { signal, fallbackMessage: 'อัปโหลดรูปไม่สำเร็จ ลองใหม่อีกครั้งนะ' })
  );
};

/** ขนาดไฟล์สูงสุด (ไบต์) ของรูปร้านแต่ละแบบ — ตรงกับ server */
export const STORE_IMAGE_MAX_BYTES = { logo: 2 * 1024 * 1024, banner: 4 * 1024 * 1024 } as const;
