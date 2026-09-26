/**
 * Seller Product API — จัดการสินค้าของร้านในแอป (SellerProductApiController)
 *
 * กติกาเดียวกับหลังร้านเว็บ /seller/products:
 * - ด่านผู้ขาย: 403 NOT_A_SELLER / STORE_PENDING / SELLER_KYC_REQUIRED / STORE_SUSPENDED / PACKAGE_REQUIRED
 *   → แสดงด้วย <SellerGateNotice> (components/seller)
 * - สินค้าของร้านอื่น = 404 PRODUCT_NOT_FOUND
 * - สินค้าถูกระงับ / มีตัวเลือกย่อย = 423 (PRODUCT_BLOCKED / VARIANTS_WEB_ONLY) → อ่านอย่างเดียว + ปุ่มแก้บนเว็บ
 * - สร้างสินค้าส่ง Idempotency-Key ต่อ "หนึ่งฟอร์ม" (ส่งซ้ำตอนเน็ตหลุด = ได้สินค้าเดิม ไม่ซ้อน)
 * - ทุกฟังก์ชันคืน ApiResult (ไม่ throw) ข้อความภาษาไทยเสมอ
 */

import { apiDelete, apiGet, apiPost, apiPut, apiUpload, fileFromUri, num, type ApiResult, type Pagination } from './client';

// =====================================================
// ชนิดข้อมูล
// =====================================================

export type SellerProductFilter = 'all' | 'active' | 'hidden' | 'out_of_stock' | 'low_stock' | 'blocked';

export type SellerShippingMethod = 'store_default' | 'free' | 'flat_rate' | 'weight_based';

export interface SellerProductListItem {
  id: number;
  name: string;
  sku: string | null;
  price: number;
  compare_at_price: number | null;
  stock_quantity: number;
  track_inventory: boolean;
  stock_status: string;
  is_out_of_stock: boolean;
  is_low_stock: boolean;
  is_active: boolean;
  /** ทีมงานซ่อนจากหน้าร้าน */
  is_hidden: boolean;
  is_blocked: boolean;
  block_reason: string | null;
  /** ผ่านการอนุมัติขึ้นตลาดสมาชิก (แอดมินอนุมัติ — แสดงอย่างเดียว) */
  is_public_approved: boolean;
  has_variants: boolean;
  /** แก้จากแอปไม่ได้ (ถูกระงับ / มีตัวเลือกย่อย) */
  read_only: boolean;
  read_only_code: string | null;
  read_only_reason: string | null;
  /** URL รูปหลัก (absolute) */
  main_image: string | null;
  image_count: number;
  category: { id: number; name: string } | null;
  sales_count: number;
  updated_at: string | null;
}

export interface SellerProductImage {
  /** 0 = รูปหลัก */
  id: number;
  url: string;
  is_main: boolean;
}

export interface SellerProductDetail extends SellerProductListItem {
  category_id: number | null;
  cost_price: number | null;
  short_description: string | null;
  description: string | null;
  brand: string | null;
  weight: number | null;
  dimensions: string | null;
  shipping_method: SellerShippingMethod;
  shipping_fee: number;
  shipping_weight_kg: number | null;
  free_shipping_min_amount: number | null;
  low_stock_threshold: number;
  gp_rate: number | null;
  view_count: number;
  images: SellerProductImage[];
  web_edit_path: string;
  created_at: string | null;
}

export type SellerProductCounts = Record<SellerProductFilter, number>;

export interface SellerProductList {
  products: SellerProductListItem[];
  pagination: Pagination;
  counts: SellerProductCounts;
}

export interface SellerCategory {
  id: number;
  name: string;
  parent_id: number | null;
}

export interface SellerProductMeta {
  categories: SellerCategory[];
  gp: { rate: number | null; label: string; source: string; promo_active: boolean };
  vat_registered: boolean;
  shipping_methods: { value: SellerShippingMethod; label: string }[];
  limits: {
    max_price: number;
    max_shipping_fee: number;
    max_gallery_images: number;
    max_image_mb: number;
    image_types: string[];
  };
}

export interface SellerProductQuote {
  unit_price: number;
  gp_rate: number;
  gp_amount: number;
  vat_amount: number;
  payment_fee: number;
  seller_net: number;
  profit: number | null;
  margin_percent: number | null;
  is_loss: boolean;
  lines: { key: string; label: string; amount: number; kind: string }[];
  warnings: string[];
  gp_label: string;
  gp_promo_active: boolean;
}

/** ข้อมูลฟอร์มสินค้า (ช่องว่าง = null) */
export interface SellerProductInput {
  name: string;
  category_id: number;
  price: number;
  compare_at_price: number | null;
  cost_price: number | null;
  stock_quantity: number;
  track_inventory: boolean;
  short_description: string | null;
  description: string | null;
  brand: string | null;
  weight: number | null;
  dimensions: string | null;
  sku: string | null;
  shipping_method: SellerShippingMethod;
  shipping_fee: number | null;
  shipping_weight_kg: number | null;
  free_shipping_min_amount: number | null;
}

/** รหัสด่านผู้ขาย (403) — ใช้ตัดสินใจแสดง SellerGateNotice */
export const SELLER_GATE_CODES = [
  'NOT_A_SELLER',
  'STORE_PENDING',
  'SELLER_KYC_REQUIRED',
  'STORE_SUSPENDED',
  'PACKAGE_REQUIRED',
] as const;

export type SellerGateCode = (typeof SELLER_GATE_CODES)[number];

export const isSellerGateCode = (code: unknown): code is SellerGateCode =>
  typeof code === 'string' && (SELLER_GATE_CODES as readonly string[]).includes(code);

// =====================================================
// แปลงข้อมูลจาก server (กันคีย์หาย/ชนิดผิด)
// =====================================================

const text = (value: unknown): string | null =>
  typeof value === 'string' && value.trim() !== '' ? value : null;

const numOrNull = (value: unknown): number | null => {
  if (value === null || value === undefined || value === '') return null;
  const n = Number(value);
  return Number.isFinite(n) ? n : null;
};

/** รับเฉพาะ URL http(s) (กัน scheme แปลกจากข้อมูลเสีย) */
const imageUrl = (value: unknown): string | null =>
  typeof value === 'string' && /^https?:\/\//i.test(value.trim()) ? value.trim() : null;

const SHIPPING_METHODS: SellerShippingMethod[] = ['store_default', 'free', 'flat_rate', 'weight_based'];

const normalizeListItem = (raw: any): SellerProductListItem => ({
  id: num(raw?.id),
  name: typeof raw?.name === 'string' ? raw.name : '',
  sku: text(raw?.sku),
  price: num(raw?.price),
  compare_at_price: numOrNull(raw?.compare_at_price),
  stock_quantity: num(raw?.stock_quantity),
  track_inventory: raw?.track_inventory !== false,
  stock_status: typeof raw?.stock_status === 'string' ? raw.stock_status : 'in_stock',
  is_out_of_stock: raw?.is_out_of_stock === true,
  is_low_stock: raw?.is_low_stock === true,
  is_active: raw?.is_active === true,
  is_hidden: raw?.is_hidden === true,
  is_blocked: raw?.is_blocked === true,
  block_reason: text(raw?.block_reason),
  is_public_approved: raw?.is_public_approved === true,
  has_variants: raw?.has_variants === true,
  read_only: raw?.read_only === true,
  read_only_code: text(raw?.read_only_code),
  read_only_reason: text(raw?.read_only_reason),
  main_image: imageUrl(raw?.main_image),
  image_count: num(raw?.image_count),
  category:
    raw?.category && typeof raw.category === 'object'
      ? { id: num(raw.category.id), name: typeof raw.category.name === 'string' ? raw.category.name : '' }
      : null,
  sales_count: num(raw?.sales_count),
  updated_at: text(raw?.updated_at),
});

export const normalizeSellerProduct = (raw: any): SellerProductDetail => ({
  ...normalizeListItem(raw),
  category_id: numOrNull(raw?.category_id),
  cost_price: numOrNull(raw?.cost_price),
  short_description: text(raw?.short_description),
  description: text(raw?.description),
  brand: text(raw?.brand),
  weight: numOrNull(raw?.weight),
  dimensions: text(raw?.dimensions),
  shipping_method: SHIPPING_METHODS.includes(raw?.shipping_method) ? raw.shipping_method : 'store_default',
  shipping_fee: num(raw?.shipping_fee),
  shipping_weight_kg: numOrNull(raw?.shipping_weight_kg),
  free_shipping_min_amount: numOrNull(raw?.free_shipping_min_amount),
  low_stock_threshold: num(raw?.low_stock_threshold),
  gp_rate: numOrNull(raw?.gp_rate),
  view_count: num(raw?.view_count),
  images: (Array.isArray(raw?.images) ? raw.images : [])
    .map((img: any) => ({ id: num(img?.id), url: imageUrl(img?.url) || '', is_main: img?.is_main === true }))
    .filter((img: SellerProductImage) => img.url !== ''),
  web_edit_path: typeof raw?.web_edit_path === 'string' && raw.web_edit_path.startsWith('/seller/')
    ? raw.web_edit_path
    : '/seller/products',
  created_at: text(raw?.created_at),
});

const emptyCounts = (): SellerProductCounts => ({
  all: 0,
  active: 0,
  hidden: 0,
  out_of_stock: 0,
  low_stock: 0,
  blocked: 0,
});

const normalizeMeta = (raw: any): SellerProductMeta => ({
  categories: (Array.isArray(raw?.categories) ? raw.categories : [])
    .map((c: any) => ({ id: num(c?.id), name: typeof c?.name === 'string' ? c.name : '', parent_id: numOrNull(c?.parent_id) }))
    .filter((c: SellerCategory) => c.id > 0 && c.name !== ''),
  gp: {
    rate: numOrNull(raw?.gp?.rate),
    label: typeof raw?.gp?.label === 'string' ? raw.gp.label : '',
    source: typeof raw?.gp?.source === 'string' ? raw.gp.source : 'unknown',
    promo_active: raw?.gp?.promo_active === true,
  },
  vat_registered: raw?.vat_registered === true,
  shipping_methods: (Array.isArray(raw?.shipping_methods) ? raw.shipping_methods : [])
    .filter((m: any) => SHIPPING_METHODS.includes(m?.value))
    .map((m: any) => ({ value: m.value as SellerShippingMethod, label: typeof m.label === 'string' ? m.label : m.value })),
  limits: {
    max_price: num(raw?.limits?.max_price, 10_000_000),
    max_shipping_fee: num(raw?.limits?.max_shipping_fee, 5000),
    max_gallery_images: num(raw?.limits?.max_gallery_images, 10),
    max_image_mb: num(raw?.limits?.max_image_mb, 5),
    image_types: Array.isArray(raw?.limits?.image_types) ? raw.limits.image_types : ['jpg', 'png', 'webp', 'gif'],
  },
});

const mapDetail = (result: ApiResult<any>): ApiResult<SellerProductDetail> =>
  result.success ? { ...result, data: normalizeSellerProduct(result.data) } : result;

// =====================================================
// ตัวเรียก API
// =====================================================

export const getSellerProducts = async (
  params: { filter?: SellerProductFilter; search?: string; page?: number; per_page?: number } = {},
  signal?: AbortSignal
): Promise<ApiResult<SellerProductList>> => {
  const result = await apiGet<any>(
    '/seller/products',
    {
      filter: params.filter && params.filter !== 'all' ? params.filter : undefined,
      search: params.search?.trim() ? params.search.trim().slice(0, 100) : undefined,
      page: params.page ?? 1,
      per_page: params.per_page ?? 20,
    },
    { signal, fallbackMessage: 'โหลดรายการสินค้าไม่สำเร็จ ลองใหม่อีกครั้งนะ' }
  );
  if (!result.success) return result;
  const p = result.data?.pagination ?? {};
  return {
    ...result,
    data: {
      products: (Array.isArray(result.data?.products) ? result.data.products : []).map(normalizeListItem),
      pagination: {
        current_page: num(p.current_page, 1),
        last_page: num(p.last_page, 1),
        per_page: num(p.per_page, 20),
        total: num(p.total),
        has_more: p.has_more === true,
      },
      counts: { ...emptyCounts(), ...(result.data?.counts ?? {}) },
    },
  };
};

export const getSellerProductMeta = async (): Promise<ApiResult<SellerProductMeta>> => {
  const result = await apiGet<any>('/seller/products/meta', undefined, {
    fallbackMessage: 'โหลดข้อมูลฟอร์มสินค้าไม่สำเร็จ ลองใหม่อีกครั้งนะ',
  });
  return result.success ? { ...result, data: normalizeMeta(result.data) } : result;
};

export const getSellerProduct = async (id: number): Promise<ApiResult<SellerProductDetail>> =>
  mapDetail(await apiGet<any>(`/seller/products/${id}`, undefined, { fallbackMessage: 'โหลดสินค้าไม่สำเร็จ ลองใหม่อีกครั้งนะ' }));

export const quoteSellerProduct = async (
  body: { price: number; cost?: number | null; product_id?: number | null },
  signal?: AbortSignal
): Promise<ApiResult<SellerProductQuote>> => {
  const result = await apiPost<any>(
    '/seller/products/quote',
    {
      price: body.price,
      ...(body.cost ? { cost: body.cost } : {}),
      ...(body.product_id ? { product_id: body.product_id } : {}),
    },
    { signal, fallbackMessage: 'คำนวณรายได้ไม่สำเร็จ' }
  );
  if (!result.success) return result;
  const d = result.data ?? {};
  return {
    ...result,
    data: {
      unit_price: num(d.unit_price),
      gp_rate: num(d.gp_rate),
      gp_amount: num(d.gp_amount),
      vat_amount: num(d.vat_amount),
      payment_fee: num(d.payment_fee),
      seller_net: num(d.seller_net),
      profit: numOrNull(d.profit),
      margin_percent: numOrNull(d.margin_percent),
      is_loss: d.is_loss === true,
      lines: (Array.isArray(d.lines) ? d.lines : [])
        .filter((l: any) => typeof l?.label === 'string' && l.label !== '')
        .map((l: any) => ({ key: String(l.key ?? ''), label: l.label, amount: num(l.amount), kind: String(l.kind ?? '') })),
      warnings: (Array.isArray(d.warnings) ? d.warnings : []).filter((w: unknown) => typeof w === 'string' && w !== ''),
      gp_label: typeof d.gp_label === 'string' ? d.gp_label : '',
      gp_promo_active: d.gp_promo_active === true,
    },
  };
};

/** ใส่ช่องข้อมูลลง FormData (ค่าว่างไม่ส่ง — server ใช้ค่าเริ่มต้นแบบเดียวกับเว็บ) */
const appendInput = (form: FormData, input: SellerProductInput) => {
  (Object.keys(input) as (keyof SellerProductInput)[]).forEach((key) => {
    const value = input[key];
    if (value === null || value === undefined || value === '') return;
    if (typeof value === 'boolean') {
      form.append(key, value ? '1' : '0');
      return;
    }
    form.append(key, String(value));
  });
};

/**
 * สร้างสินค้า (multipart) — รูปหลัก + รูปเพิ่มเติม
 * @param idempotencyKey key เดียวต่อหนึ่งฟอร์ม (กดส่งซ้ำ/เน็ตหลุดแล้วกดใหม่ = ได้สินค้าเดิม)
 */
export const createSellerProduct = async (
  input: SellerProductInput,
  images: { mainUri: string | null; galleryUris: string[] },
  idempotencyKey: string,
  signal?: AbortSignal
): Promise<ApiResult<SellerProductDetail>> => {
  const form = new FormData();
  appendInput(form, input);
  const stamp = Date.now();
  if (images.mainUri) {
    form.append('main_image', fileFromUri(images.mainUri, `product-main-${stamp}`) as unknown as Blob);
  }
  images.galleryUris.forEach((uri, i) => {
    form.append('images[]', fileFromUri(uri, `product-${stamp}-${i}`) as unknown as Blob);
  });
  return mapDetail(
    await apiUpload<any>('/seller/products', form, {
      signal,
      headers: { 'Idempotency-Key': idempotencyKey },
      fallbackMessage: 'บันทึกสินค้าไม่สำเร็จ ลองใหม่อีกครั้งนะ',
    })
  );
};

/** แก้ข้อมูลสินค้า (ส่งครบทุกช่องของฟอร์ม — ช่องว่างส่ง null เพื่อล้างค่า) */
export const updateSellerProduct = async (id: number, input: SellerProductInput): Promise<ApiResult<SellerProductDetail>> =>
  mapDetail(await apiPut<any>(`/seller/products/${id}`, input, { fallbackMessage: 'บันทึกสินค้าไม่สำเร็จ ลองใหม่อีกครั้งนะ' }));

export const setSellerProductActive = async (id: number, isActive: boolean): Promise<ApiResult<SellerProductDetail>> =>
  mapDetail(await apiPost<any>(`/seller/products/${id}/active`, { is_active: isActive }, { fallbackMessage: 'เปลี่ยนสถานะการขายไม่สำเร็จ' }));

export const setSellerProductStock = async (id: number, quantity: number): Promise<ApiResult<SellerProductDetail>> =>
  mapDetail(await apiPost<any>(`/seller/products/${id}/stock`, { stock_quantity: quantity }, { fallbackMessage: 'อัปเดตสต็อกไม่สำเร็จ' }));

export const deleteSellerProduct = (id: number): Promise<ApiResult<{ id: number }>> =>
  apiDelete<{ id: number }>(`/seller/products/${id}`, undefined, { fallbackMessage: 'ลบสินค้าไม่สำเร็จ ลองใหม่อีกครั้งนะ' });

export const addSellerProductImages = async (
  id: number,
  uris: string[],
  signal?: AbortSignal
): Promise<ApiResult<SellerProductDetail>> => {
  const form = new FormData();
  const stamp = Date.now();
  uris.forEach((uri, i) => form.append('images[]', fileFromUri(uri, `product-${id}-${stamp}-${i}`) as unknown as Blob));
  return mapDetail(
    await apiUpload<any>(`/seller/products/${id}/images`, form, { signal, fallbackMessage: 'อัปโหลดรูปไม่สำเร็จ ลองใหม่อีกครั้งนะ' })
  );
};

/** ลบรูป (imageId 0 = รูปหลัก) */
export const deleteSellerProductImage = async (id: number, imageId: number): Promise<ApiResult<SellerProductDetail>> =>
  mapDetail(await apiDelete<any>(`/seller/products/${id}/images/${imageId}`, undefined, { fallbackMessage: 'ลบรูปไม่สำเร็จ' }));

export const setSellerProductMainImage = async (id: number, imageId: number): Promise<ApiResult<SellerProductDetail>> =>
  mapDetail(await apiPost<any>(`/seller/products/${id}/images/main`, { image_id: imageId }, { fallbackMessage: 'ตั้งรูปหลักไม่สำเร็จ' }));

/** เรียงรูปเพิ่มเติม (ต้องส่ง id ครบทุกรูป ไม่รวมรูปหลัก) */
export const reorderSellerProductImages = async (id: number, order: number[]): Promise<ApiResult<SellerProductDetail>> =>
  mapDetail(await apiPost<any>(`/seller/products/${id}/images/order`, { order }, { fallbackMessage: 'เรียงรูปไม่สำเร็จ' }));
