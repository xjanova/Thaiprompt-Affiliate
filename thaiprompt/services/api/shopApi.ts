/**
 * Shop API — สินค้า / ร้าน / ตะกร้า / ชำระเงิน / ที่อยู่ / คำสั่งซื้อของผู้ซื้อ (contract D-shop)
 *
 * - server cart เป็นข้อมูลหลัก: ทุกคำสั่งของตะกร้าคืนตะกร้าทั้งใบ → แอปแทน state ทั้งก้อน
 * - checkout: ส่ง Idempotency-Key ต่อการกดหนึ่งครั้ง (newIdempotencyKey())
 * - ไม่มี pv / commission ในข้อมูลสินค้าแล้ว (นโยบาย Google Play)
 */

import {
  apiDelete,
  apiGet,
  apiPost,
  apiPut,
  apiUpload,
  fileFromUri,
  newIdempotencyKey,
  type ApiResult,
  type Pagination,
} from './client';

// =====================================================
// สินค้า / ร้าน
// =====================================================

export interface ProductStoreRef {
  id: number;
  name: string;
  slug: string | null;
  logo: string | null;
  is_verified: boolean;
  rider_delivery: boolean;
}

export interface ShopProduct {
  id: number;
  name: string;
  slug: string | null;
  description: string | null;
  image: string | null;
  images: string[];
  price: number;
  original_price: number | null;
  discount_price: number | null;
  discount_percent: number | null;
  category: string | null;
  category_id: number | null;
  rating: number;
  review_count: number;
  sales_count: number;
  is_featured: boolean;
  stock_status: string | null;
  stock: number | null;
  in_stock: boolean;
  brand: string | null;
  is_virtual: boolean;
  /** true = ซื้อที่ร้านต้นทาง (affiliate_url) ไม่ใช่ใส่ตะกร้า */
  is_affiliate: boolean;
  affiliate_url: string | null;
  external_platform: string | null;
  can_add_to_cart: boolean;
  seller_id: number | null;
  store: ProductStoreRef | null;
}

export interface ShopProductReview {
  id: number;
  rating: number;
  title: string | null;
  comment: string | null;
  reviewer: string;
  is_verified_purchase: boolean;
  seller_response: string | null;
  created_at: string;
}

export interface ShopProductDetail extends ShopProduct {
  description_full: string | null;
  attributes: Record<string, unknown> | unknown[] | null;
  weight: number | null;
  reviews: ShopProductReview[];
  store_detail: Record<string, unknown> | null;
  /** เหตุผลที่ซื้อไม่ได้ (null = ซื้อได้) */
  purchase_block_reason: string | null;
}

export interface ShopCategory {
  id: number;
  name: string;
  slug: string;
  icon: string | null;
  image: string | null;
  parent_id: number | null;
  products_count: number;
}

export interface ShopPagination extends Pagination {
  has_more: boolean;
}

export interface ProductListParams {
  category?: string | number;
  search?: string;
  store_id?: number;
  featured?: 1;
  sort?: 'created_at' | 'newest' | 'price' | 'name' | 'sales_count' | 'popular' | 'rating';
  order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface StoreListItem {
  id: string;
  name: string;
  logo: string | null;
  rating: number;
  rating_count: number;
  isOfficial: boolean;
  isFeatured: boolean;
  productCount: number;
  rider_delivery: boolean;
}

export interface StoreDetail {
  id: number | string;
  store_id: number;
  name: string;
  slug: string | null;
  description: string | null;
  logo: string | null;
  banner: string | null;
  rating: number;
  rating_count: number;
  is_verified: boolean;
  isOfficial: boolean;
  isFeatured: boolean;
  product_count: number;
  follower_count: number;
  joinedAt: string | null;
  rider_delivery: boolean;
  cod_available: boolean;
}

/** รายการสินค้า — data = ShopProduct[], meta.pagination */
export const getProducts = async (
  params: ProductListParams = {}
): Promise<ApiResult<{ products: ShopProduct[]; pagination: ShopPagination | null }>> => {
  const result = await apiGet<ShopProduct[]>('/products', params as Record<string, unknown>);
  if (!result.success) return result;
  return {
    ...result,
    data: {
      products: Array.isArray(result.data) ? result.data : [],
      pagination: (result.meta?.pagination as ShopPagination) || null,
    },
  };
};

/** รายละเอียดสินค้า — 404 PRODUCT_NOT_FOUND */
export const getProduct = (id: number | string): Promise<ApiResult<ShopProductDetail>> =>
  apiGet<ShopProductDetail>(`/products/${id}`);

export const getProductCategories = (): Promise<ApiResult<ShopCategory[]>> =>
  apiGet<ShopCategory[]>('/products/categories');

export const getOfficialStores = (): Promise<ApiResult<StoreListItem[]>> =>
  apiGet<StoreListItem[]>('/mobile/stores/official');

export const getFeaturedStores = (): Promise<ApiResult<StoreListItem[]>> =>
  apiGet<StoreListItem[]>('/mobile/stores/featured');

/** 404 STORE_NOT_FOUND */
export const getStore = (storeId: number | string): Promise<ApiResult<StoreDetail>> =>
  apiGet<StoreDetail>(`/mobile/stores/${storeId}`);

export const getStoreProducts = async (
  storeId: number | string,
  params: { page?: number; per_page?: number; search?: string; sort?: string; order?: 'asc' | 'desc' } = {}
): Promise<ApiResult<{ products: ShopProduct[]; pagination: ShopPagination | null }>> => {
  const result = await apiGet<ShopProduct[]>(`/mobile/stores/${storeId}/products`, params);
  if (!result.success) return result;
  return {
    ...result,
    data: {
      products: Array.isArray(result.data) ? result.data : [],
      pagination: (result.meta?.pagination as ShopPagination) || null,
    },
  };
};

// =====================================================
// ตะกร้า
// =====================================================

export type DeliveryMethod = 'parcel' | 'rider';
export type CheckoutPaymentMethod = 'wallet' | 'promptpay' | 'cod';

export interface CartItem {
  id: number;
  product_id: number;
  name: string;
  image: string | null;
  unit_price: number;
  original_price: number | null;
  quantity: number;
  line_total: number;
  attributes: Record<string, unknown> | null;
  stock: number | null;
  max_quantity: number;
  is_available: boolean;
  unavailable_reason: string | null;
  store: { id: number; name: string } | null;
}

export interface CartStoreGroup {
  key: string;
  store_id: number | null;
  store_name: string;
  items_count: number;
  subtotal: number;
  delivery_method: DeliveryMethod;
  shipping_fee: number;
  parcel_fee: number;
  discount: number;
  total: number;
  rider: { available: boolean; reason: string | null; fee: number | null; distance_km: number | null; estimated_minutes: number | null };
  cod: { available: boolean; reason: string | null; limit: number | null };
}

export interface Cart {
  cart_id: number | null;
  items: CartItem[];
  stores: CartStoreGroup[];
  address: { id: number; recipient_name: string; full_address: string; has_location: boolean } | null;
  delivery_method: DeliveryMethod;
  coupon: { code: string; store_id: number | null; discount_type: string; discount: number } | null;
  coupon_error: { code: string; message: string } | null;
  summary: {
    items_count: number;
    available_items_count: number;
    unavailable_count: number;
    subtotal: number;
    shipping_fee: number;
    discount: number;
    grand_total: number;
    free_shipping_threshold: number | null;
    amount_to_free_shipping: number | null;
    rider_available: boolean;
    cod_available: boolean;
  };
}

export interface CartQuery {
  address_id?: number;
  delivery_method?: DeliveryMethod;
  coupon_code?: string;
}

export const getCart = (query: CartQuery = {}): Promise<ApiResult<Cart>> =>
  apiGet<Cart>('/cart', query as Record<string, unknown>);

/** 409 PRODUCT_UNAVAILABLE / OUT_OF_STOCK (data.available), 422 AFFILIATE_PRODUCT */
export const addCartItem = (
  productId: number,
  quantity: number = 1,
  attributes?: Record<string, unknown>
): Promise<ApiResult<Cart>> =>
  apiPost<Cart>('/cart/items', { product_id: productId, quantity, ...(attributes ? { attributes } : {}) });

/** quantity 0 = ลบ */
export const updateCartItem = (itemId: number, quantity: number): Promise<ApiResult<Cart>> =>
  apiPut<Cart>(`/cart/items/${itemId}`, { quantity });

export const removeCartItem = (itemId: number): Promise<ApiResult<Cart>> =>
  apiDelete<Cart>(`/cart/items/${itemId}`);

export const clearCart = (): Promise<ApiResult<Cart>> => apiDelete<Cart>('/cart');

/** 422 COUPON_INVALID */
export const applyCartPromo = (
  code: string,
  options: { address_id?: number; delivery_method?: DeliveryMethod } = {}
): Promise<ApiResult<Cart>> => apiPost<Cart>('/cart/promo', { code, ...options });

// =====================================================
// Checkout + ชำระเงิน
// =====================================================

export interface CheckoutBody {
  payment_method: CheckoutPaymentMethod;
  /** ไม่ส่ง = ใช้ที่อยู่หลัก */
  address_id?: number;
  delivery_method?: DeliveryMethod;
  coupon_code?: string;
  note?: string;
}

export interface PaymentInstruction {
  transaction_id: string;
  order_id: number;
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled' | 'error' | string;
  /** ยอดมีเศษสตางค์เฉพาะตัว — ต้องแสดงตรงทุกหลัก (PriceText decimals={2}) */
  amount: number;
  currency: string;
  payment_method: string;
  expired_at: string | null;
  /** data URI ของ QR */
  qr_code: string | null;
  qr_code_url: string | null;
  ref_no: string | null;
  promptpay: { account_name: string; promptpay_id: string } | null;
  message?: string;
}

export interface CheckoutResult {
  checkout_id: string;
  orders: ShopOrder[];
  order_ids: number[];
  total_amount: number;
  payment_method: CheckoutPaymentMethod;
  payment_status: 'paid' | 'cod' | 'pending';
  payments: PaymentInstruction[];
  wallet_balance?: number;
}

/**
 * POST /cart/checkout — 1 ร้าน = 1 ออเดอร์
 * idempotencyKey: สร้างครั้งเดียวต่อการกด (เก็บใน useRef) แล้วส่งซ้ำตอน retry
 */
export const checkout = (body: CheckoutBody, idempotencyKey: string = newIdempotencyKey()): Promise<ApiResult<CheckoutResult>> =>
  apiPost<CheckoutResult>('/cart/checkout', body, { headers: { 'Idempotency-Key': idempotencyKey } });

export interface PaymentStatus {
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled' | 'expired' | string;
  status_label?: string;
  amount?: number;
  expired_at?: string | null;
  /** QR หมดอายุแล้ว (ขอ QR ใหม่ด้วย payOrder) */
  is_expired?: boolean;
  order?: { id: number; order_number: string; status: string; payment_status: string } | null;
  [key: string]: unknown;
}

/** poll สถานะการชำระเงิน */
export const getPaymentStatus = (transactionId: string): Promise<ApiResult<PaymentStatus>> =>
  apiGet<PaymentStatus>(`/payment/${encodeURIComponent(transactionId)}/status`);

/** วิธีชำระเงินที่ระบบเปิดใช้งาน (ใช้เช็คว่าพร้อมเพย์เปิดอยู่ไหม) */
export interface PaymentMethodInfo {
  id: string;
  name?: string;
  enabled?: boolean;
  [key: string]: unknown;
}

export const getPaymentMethods = (): Promise<ApiResult<{ methods: PaymentMethodInfo[] }>> =>
  apiGet<{ methods: PaymentMethodInfo[] }>('/payment/methods');

/** ขอ QR ใหม่ / เปลี่ยนไปจ่ายด้วยกระเป๋าเงิน — 409 ALREADY_PAID / COD_ORDER / ORDER_NOT_PAYABLE */
export const payOrder = (
  orderId: number,
  paymentMethod: 'promptpay' | 'wallet' | 'bank_transfer'
): Promise<ApiResult<PaymentInstruction & { order: { id: number; status: string; payment_status: string } }>> =>
  apiPost('/payment/order', { order_id: orderId, payment_method: paymentMethod });

// =====================================================
// ที่อยู่
// =====================================================

export interface Address {
  id: number;
  recipient_name: string;
  phone_number: string;
  address_line_1: string;
  address_line_2: string | null;
  sub_district: string | null;
  district: string | null;
  province: string;
  postal_code: string;
  country: string | null;
  latitude: number | null;
  longitude: number | null;
  /** ส่งด้วยไรเดอร์ต้อง true */
  has_location: boolean;
  notes: string | null;
  is_default: boolean;
  full_address: string;
  updated_at: string;
}

export interface AddressInput {
  recipient_name: string;
  phone_number: string;
  address_line_1: string;
  /** ช่องไม่บังคับ: ตอนแก้ไขส่ง null = ลบค่าเดิม (ไม่ส่งคีย์ = คงค่าเดิมไว้) */
  address_line_2?: string | null;
  sub_district?: string | null;
  district?: string | null;
  province: string;
  /** 5 หลัก */
  postal_code: string;
  country?: string;
  /** ต้องส่งคู่กับ longitude */
  latitude?: number;
  longitude?: number;
  notes?: string | null;
  is_default?: boolean;
}

export const getAddresses = (): Promise<ApiResult<Address[]>> => apiGet<Address[]>('/addresses');

/** 422 ADDRESS_LIMIT (20) */
export const createAddress = (input: AddressInput): Promise<ApiResult<Address>> => apiPost<Address>('/addresses', input);

export const updateAddress = (id: number, input: Partial<AddressInput>): Promise<ApiResult<Address>> =>
  apiPut<Address>(`/addresses/${id}`, input);

export const deleteAddress = (id: number): Promise<ApiResult<unknown>> => apiDelete(`/addresses/${id}`);

export const setDefaultAddress = (id: number): Promise<ApiResult<Address>> => apiPost<Address>(`/addresses/${id}/default`, {});

// =====================================================
// คำสั่งซื้อของผู้ซื้อ
// =====================================================

export type ShopOrderStatus =
  | 'pending'
  | 'paid'
  | 'processing'
  | 'shipped'
  | 'delivered'
  | 'completed'
  | 'cancelled'
  | 'refunded';

export interface ShopOrderListItem {
  id: number;
  order_number: string;
  status: ShopOrderStatus;
  status_label: string;
  payment_status: string;
  payment_method: string;
  delivery_method: DeliveryMethod | null;
  total_amount: number;
  items_count: number;
  first_item: { product_name: string; product_image: string | null } | null;
  has_unread_messages: boolean;
  last_message_at: string | null;
  created_at: string;
}

export interface ShopOrderItem {
  id: number;
  product_id: number;
  product_name: string;
  product_image: string | null;
  attributes: Record<string, unknown> | null;
  quantity: number;
  price: number;
  unit_price: number;
  subtotal: number;
  discount: number;
  total: number;
  status: string;
  can_review: boolean;
}

/**
 * สรุปงานไรเดอร์ของออเดอร์
 * ยังไม่เรียกไรเดอร์ = { status: 'not_requested', status_label } (ไม่มีคีย์อื่น)
 */
export interface ShopOrderRider {
  status: string;
  status_label: string;
  job_id?: number;
  job_number?: string | null;
  rider?: { name: string | null; vehicle_type: string | null; vehicle_plate: string | null; phone: string | null } | null;
  /** หน้าติดตามสด /taladsod/track/{token} — มีเฉพาะระหว่างงานยังไม่จบ */
  tracking_url?: string | null;
  delivered_at?: string | null;
}

export interface ShopOrder {
  id: number;
  order_number: string;
  checkout_group: string | null;
  status: ShopOrderStatus;
  status_label: string;
  payment_status: string;
  payment_status_label: string;
  payment_method: string;
  payment_method_label: string;
  delivery_method: DeliveryMethod | null;
  store: { id: number; name: string; logo?: string | null } | null;
  subtotal: number;
  shipping_fee: number;
  discount: number;
  total_amount: number;
  currency: string;
  shipping: {
    name: string;
    phone: string | null;
    address: string;
    address_line_2: string | null;
    subdistrict: string | null;
    district: string | null;
    province: string | null;
    postal_code: string | null;
    full_address: string;
    latitude: number | null;
    longitude: number | null;
    notes: string | null;
  } | null;
  items: ShopOrderItem[];
  note: string | null;
  tracking_number: string | null;
  shipping_provider: string | null;
  tracking_url: string | null;
  rider: ShopOrderRider | null;
  can_cancel: boolean;
  can_pay: boolean;
  can_confirm_received: boolean;
  cancellation_reason: string | null;
  created_at: string;
  paid_at: string | null;
  shipped_at: string | null;
  delivered_at: string | null;
  cancelled_at: string | null;
  /** QR ที่ยังรอชำระ (เฉพาะ GET /orders/{id}) */
  payment?: PaymentInstruction | null;
}

export interface ShopOrderTracking {
  order_number: string;
  status: ShopOrderStatus;
  status_label: string;
  delivery_method: DeliveryMethod | null;
  tracking_number: string | null;
  tracking_url: string | null;
  shipping_provider: string | null;
  rider: ShopOrderRider | null;
  estimated_delivery_at: string | null;
  shipped_at: string | null;
  delivered_at: string | null;
  history: Array<{
    id: number;
    status: string;
    title: string;
    description: string | null;
    location: string | null;
    tracking_number: string | null;
    shipping_provider: string | null;
    tracked_at: string;
  }>;
}

export const getMyOrders = (
  params: { status?: ShopOrderStatus | 'all'; page?: number; per_page?: number } = {}
): Promise<ApiResult<{ orders: ShopOrderListItem[]; pagination: ShopPagination }>> =>
  apiGet<{ orders: ShopOrderListItem[]; pagination: ShopPagination }>('/orders', params);

export const getMyOrder = (orderId: number): Promise<ApiResult<ShopOrder>> => apiGet<ShopOrder>(`/orders/${orderId}`);

/**
 * 409 ACTION_NOT_ALLOWED / REFUND_FAILED
 * result.meta.refunded = true เมื่อคืนเงินเข้ากระเป๋าแล้ว (result.message บอกผู้ใช้ได้เลย)
 */
export const cancelMyOrder = (orderId: number, reason?: string): Promise<ApiResult<ShopOrder>> =>
  apiPost<ShopOrder>(`/orders/${orderId}/cancel`, reason ? { reason } : {});

/** delivered → completed */
export const confirmOrderReceived = (orderId: number): Promise<ApiResult<ShopOrder>> =>
  apiPost<ShopOrder>(`/orders/${orderId}/confirm-received`, {});

export const getOrderTracking = (orderId: number): Promise<ApiResult<ShopOrderTracking>> =>
  apiGet<ShopOrderTracking>(`/orders/${orderId}/tracking`);

// =====================================================
// แชทกับร้านในออเดอร์
// =====================================================

export interface ShopOrderMessage {
  id: number;
  sender_type: 'customer' | 'seller' | 'admin' | string;
  sender_name: string | null;
  sender_avatar: string | null;
  message: string | null;
  attachment: string | null;
  attachment_type: string | null;
  is_system_message: boolean;
  is_mine: boolean;
  created_at: string;
}

/** ข้อความล่าสุดก่อน (เรียงใหม่ → เก่า) */
export const getOrderMessages = (
  orderId: number,
  params: { page?: number; per_page?: number } = {}
): Promise<ApiResult<{ messages: ShopOrderMessage[]; pagination: Pagination }>> =>
  apiGet<{ messages: ShopOrderMessage[]; pagination: Pagination }>(`/orders/${orderId}/messages`, params);

export const sendOrderMessage = (orderId: number, message: string): Promise<ApiResult<ShopOrderMessage>> => {
  const form = new FormData();
  form.append('message', message);
  return apiUpload<ShopOrderMessage>(`/orders/${orderId}/messages`, form);
};

/** รีวิวสินค้าในออเดอร์ (multipart) — 409 ALREADY_REVIEWED */
export const reviewOrderItem = (
  orderId: number,
  itemId: number,
  input: { rating: number; comment: string; title?: string; imageUris?: string[] }
): Promise<ApiResult<unknown>> => {
  const form = new FormData();
  form.append('rating', String(input.rating));
  form.append('comment', input.comment);
  if (input.title) form.append('title', input.title);
  (input.imageUris || []).slice(0, 5).forEach((uri, index) => {
    form.append('images[]', fileFromUri(uri, `review-${index + 1}`) as unknown as Blob);
  });
  return apiUpload(`/orders/${orderId}/items/${itemId}/review`, form);
};
