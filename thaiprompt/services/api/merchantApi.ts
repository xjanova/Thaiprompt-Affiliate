/**
 * Merchant API — ร้านค้าของฉัน (SellerOrderApiController, contract D-shop ส่วน merchant)
 *
 * - ไม่ใช่ร้าน = 403 NOT_A_SELLER · ร้านถูกระงับ/ปิด = 403 STORE_SUSPENDED (data.reason)
 * - ปุ่มที่แสดงได้ให้ดูจาก allowed_actions ของออเดอร์เสมอ
 * - ตั้งค่าไรเดอร์/หมุดจุดรับของของร้าน → เปิดเว็บ /seller/store/settings ผ่าน WebsiteButton
 */

import { apiGet, apiPost, type ApiResult } from './client';
import type { ShopPagination } from './shopApi';

export type SellerOrderFilter =
  | 'all'
  | 'to_confirm'
  | 'to_ship'
  | 'shipping'
  | 'delivered'
  | 'completed'
  | 'cancelled'
  | 'awaiting_payment'
  /** มีข้อความจากลูกค้าที่ร้านยังไม่อ่าน */
  | 'unread_chat';

export type SellerOrderAction = 'confirm' | 'request_rider' | 'ship' | 'deliver' | 'cancel';

export interface SellerSummary {
  /** null = บัญชีแอดมินที่ยังไม่มีร้าน */
  store: {
    id: number;
    name: string;
    slug: string | null;
    logo: string | null;
    is_verified: boolean;
    rider_delivery: boolean;
    status: string;
    is_active: boolean;
    has_pickup_location: boolean;
    rider_delivery_enabled: boolean;
  } | null;
  counts: {
    to_confirm: number;
    to_ship: number;
    shipping: number;
    awaiting_payment: number;
    rider_active: number;
  };
  sales: {
    today: number;
    month: number;
    month_net_earning: number;
    month_gp: number;
  };
}

export interface SellerOrderListItem {
  id: number;
  order_number: string;
  status: string;
  status_label: string;
  payment_status: string;
  payment_method: string;
  payment_method_label: string;
  delivery_method: 'parcel' | 'rider' | null;
  customer_name: string;
  items_count: number;
  first_item: { product_name: string; product_image: string | null } | null;
  seller_total: number;
  seller_earning: number;
  is_multi_seller: boolean;
  /** ข้อความจากลูกค้าที่ร้านยังไม่อ่าน (แชทออเดอร์) */
  unread_messages?: number;
  last_message_at?: string | null;
  created_at: string;
}

export interface SellerOrderDetail {
  order: {
    id: number;
    order_number: string;
    status: string;
    status_label: string;
    payment_status: string;
    payment_method: string;
    payment_method_label?: string;
    payment_status_label?: string;
    delivery_method: 'parcel' | 'rider' | null;
    /** มีสินค้าหลายร้าน — แสดงเฉพาะของร้านนี้ */
    is_multi_seller?: boolean;
    note: string | null;
    created_at: string;
    paid_at?: string | null;
    shipped_at?: string | null;
    delivered_at?: string | null;
    cancellation_reason?: string | null;
    [key: string]: unknown;
  };
  customer: { name: string };
  /** เบอร์ถูกซ่อนเมื่อออเดอร์จบเกิน 7 วัน */
  shipping: {
    name: string;
    phone: string | null;
    full_address: string;
    latitude?: number | null;
    longitude?: number | null;
    notes?: string | null;
    [key: string]: unknown;
  } | null;
  items: Array<{
    id: number;
    product_id: number;
    product_name: string;
    product_image: string | null;
    quantity: number;
    unit_price: number;
    total: number;
    gp_rate: number;
    gp_amount: number;
    seller_earning: number;
    [key: string]: unknown;
  }>;
  totals: {
    items_total: number;
    discount: number;
    gp_amount: number;
    seller_earning: number;
    /** null = ออเดอร์หลายร้าน (ไม่แสดงค่าส่ง/ยอดรวมทั้งออเดอร์) */
    shipping_fee: number | null;
    order_total: number | null;
  };
  tracking: {
    tracking_number: string | null;
    shipping_provider: string | null;
    tracking_url: string | null;
    estimated_delivery_at: string | null;
  };
  /** ยังไม่เรียกไรเดอร์ = { status: 'not_requested', status_label } */
  rider: {
    status: string;
    status_label: string;
    job_id?: number;
    job_number?: string | null;
    rider?: { name: string | null; vehicle_type: string | null; vehicle_plate: string | null; phone: string | null } | null;
    tracking_url?: string | null;
    delivered_at?: string | null;
  } | null;
  tracking_history: Array<{
    id: number;
    status: string;
    title: string;
    description: string | null;
    location: string | null;
    tracked_at: string;
  }>;
  allowed_actions: SellerOrderAction[];
  /** แชทกับลูกค้า — unread = ข้อความลูกค้าที่ร้านยังไม่อ่าน · can_send=false เมื่อออเดอร์ยกเลิก/คืนเงิน */
  chat?: { unread: number; can_send: boolean; last_message_at: string | null };
}

export interface SellerOrderActionBody {
  action: SellerOrderAction;
  /** ship */
  tracking_number?: string;
  /** ship — เลือกจาก getShippingProviders() */
  shipping_provider_id?: number;
  /** ship — หรือพิมพ์ชื่อขนส่งเอง */
  provider?: string;
  estimated_delivery_at?: string;
  /** cancel (บังคับ) */
  reason?: string;
}

export interface ShippingProvider {
  id: number;
  name: string;
  code?: string | null;
  logo?: string | null;
  tracking_url_template?: string | null;
  [key: string]: unknown;
}

/** GET /seller/summary — ใช้เช็คด้วยว่าเป็นร้านค้าหรือไม่ (403 NOT_A_SELLER) */
export const getSellerSummary = (): Promise<ApiResult<SellerSummary>> => apiGet<SellerSummary>('/seller/summary');

/** GET /seller/orders */
export const getSellerOrders = (
  params: { status?: SellerOrderFilter; page?: number; per_page?: number } = {}
): Promise<ApiResult<{ orders: SellerOrderListItem[]; pagination: ShopPagination; counts: Record<string, number> }>> =>
  apiGet('/seller/orders', params);

/** GET /seller/orders/{id} — ร้านอื่น = 404 ORDER_NOT_FOUND */
export const getSellerOrder = (orderId: number): Promise<ApiResult<SellerOrderDetail>> =>
  apiGet<SellerOrderDetail>(`/seller/orders/${orderId}`);

/**
 * POST /seller/orders/{id}/action → รายละเอียดออเดอร์ล่าสุด
 * 409 ACTION_NOT_ALLOWED (data.allowed_actions) · 422 ข้อความจากระบบไรเดอร์ (เช่น นอกพื้นที่)
 */
export const sellerOrderAction = (orderId: number, body: SellerOrderActionBody): Promise<ApiResult<SellerOrderDetail>> =>
  apiPost<SellerOrderDetail>(`/seller/orders/${orderId}/action`, body);

/** POST /seller/orders/{id}/tracking-history — อัปเดตสถานะพัสดุระหว่างทาง */
export const addSellerTrackingHistory = (
  orderId: number,
  body: { status: 'in_transit' | 'out_for_delivery'; description: string; location?: string }
): Promise<ApiResult<unknown>> => apiPost(`/seller/orders/${orderId}/tracking-history`, body);

/** GET /seller/shipping-providers */
export const getShippingProviders = (): Promise<ApiResult<ShippingProvider[]>> =>
  apiGet<ShippingProvider[]>('/seller/shipping-providers');

// =====================================================
// เช็คว่าเป็นร้านค้าหรือไม่ (cache ต่อ session)
// =====================================================

let sellerCache: { userId: number; isSeller: boolean; at: number } | null = null;
const SELLER_CACHE_MS = 5 * 60 * 1000;

/**
 * ผู้ใช้คนนี้มีร้านค้าหรือไม่ (ใช้ซ่อน/แสดงเมนู "ร้านของฉัน")
 * - 403 NOT_A_SELLER → false · STORE_SUSPENDED → true (ยังเป็นร้าน แค่ถูกระงับ)
 * - เน็ตหลุด → false (ไม่แสดงเมนู) แต่ไม่ cache เพื่อเช็คใหม่รอบหน้า
 */
export const checkIsSeller = async (userId: number, force: boolean = false): Promise<boolean> => {
  if (!force && sellerCache && sellerCache.userId === userId && Date.now() - sellerCache.at < SELLER_CACHE_MS) {
    return sellerCache.isSeller;
  }
  const result = await getSellerSummary();
  if (result.success) {
    sellerCache = { userId, isSeller: true, at: Date.now() };
    return true;
  }
  if (result.code === 'STORE_SUSPENDED') {
    sellerCache = { userId, isSeller: true, at: Date.now() };
    return true;
  }
  if (result.code === 'NOT_A_SELLER' || result.status === 403) {
    sellerCache = { userId, isSeller: false, at: Date.now() };
  }
  return false;
};

/** ล้าง cache (เรียกตอน logout) */
export const resetSellerCache = (): void => {
  sellerCache = null;
};
