/**
 * Cart Store — แคชบางๆ ของ "ตะกร้าบน server" (SHOP-03 / SHOP-23)
 *
 * หลักการ
 *   - server เป็นข้อมูลหลักเสมอ: ทุกคำสั่ง (เพิ่ม/แก้จำนวน/ลบ/ล้าง) เรียก API แล้ว "แทนตะกร้าทั้งใบ" ด้วยค่าที่ server ตอบ
 *   - แอปไม่คำนวณราคา/ค่าส่ง/ส่วนลดเอง — ใช้ summary จาก server อย่างเดียว (ไม่มี PV/คอมมิชชั่น)
 *   - คำสั่งซ้อนกัน (กด + รัวๆ) → ใช้ผลของคำสั่งล่าสุดเท่านั้น (seq) กันตะกร้าเด้งกลับค่าเก่า
 *   - เปลี่ยนบัญชี/ออกจากระบบ → ล้างแคชทันที ไม่ให้เห็นตะกร้าของคนก่อน
 *
 * @example
 * const count = useCartStore((s) => s.count);
 * const result = await useCartStore.getState().add(productId, 2);
 * if (!result.success) Alert.alert('ใส่ตะกร้าไม่สำเร็จ', result.message);
 */

import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useAuthStore } from '@/stores/authStore';
import {
  addCartItem,
  clearCart as clearCartApi,
  getCart,
  removeCartItem,
  updateCartItem,
  type Cart,
  type CartItem,
  type CartQuery,
  type CartStoreGroup,
} from '@/services/api/shopApi';
import { num, type ApiResult } from '@/services/api/client';

export type { Cart, CartItem, CartStoreGroup };

/** คีย์ตะกร้าในเครื่องแบบเก่า (V2) — ลบทิ้งครั้งเดียว ไม่ใช้แล้ว */
const LEGACY_CART_STORAGE_KEY = '@thaiprompt_cart';

interface CartState {
  /** ตะกร้าล่าสุดจาก server (null = ยังไม่เคยโหลด) */
  cart: Cart | null;
  /** จำนวนชิ้นทั้งหมด (ใช้กับ badge) */
  count: number;
  /** กำลังโหลดครั้งแรก (ยังไม่มีข้อมูลเลย) */
  loading: boolean;
  /** ข้อความไทยของการโหลดล่าสุดที่ล้มเหลว */
  error: string | null;
  lastFetchedAt: number | null;

  /** โหลดตะกร้าใหม่จาก server */
  refresh: (query?: CartQuery) => Promise<ApiResult<Cart>>;
  /** โหลดเฉพาะเมื่อข้อมูลเก่ากว่า maxAgeMs (ใช้กับ badge) */
  ensureFresh: (maxAgeMs?: number) => Promise<void>;
  add: (productId: number, quantity?: number, attributes?: Record<string, unknown>) => Promise<ApiResult<Cart>>;
  /** quantity 0 = ลบ */
  setQuantity: (itemId: number, quantity: number) => Promise<ApiResult<Cart>>;
  remove: (itemId: number) => Promise<ApiResult<Cart>>;
  clear: () => Promise<ApiResult<Cart>>;
  /** แทนตะกร้าด้วยผลที่ได้จาก API อื่น (เช่น POST /cart/promo) */
  replace: (cart: Cart) => void;
  /** ล้างแคช (ออกจากระบบ / เปลี่ยนบัญชี / สั่งซื้อสำเร็จ) */
  reset: () => void;
}

// =====================================================
// แปลงข้อมูลจาก server ให้ปลอดภัย (ตัวเลขเป็น number เสมอ, array ไม่เป็น null)
// =====================================================

const normalizeItem = (raw: any): CartItem => ({
  id: num(raw?.id),
  product_id: num(raw?.product_id),
  name: typeof raw?.name === 'string' ? raw.name : 'สินค้า',
  image: typeof raw?.image === 'string' && raw.image ? raw.image : null,
  unit_price: num(raw?.unit_price),
  original_price: raw?.original_price === null || raw?.original_price === undefined ? null : num(raw.original_price),
  quantity: num(raw?.quantity, 1),
  line_total: num(raw?.line_total),
  attributes: raw?.attributes && typeof raw.attributes === 'object' ? raw.attributes : null,
  stock: raw?.stock === null || raw?.stock === undefined ? null : num(raw.stock),
  max_quantity: num(raw?.max_quantity, 99),
  is_available: raw?.is_available !== false,
  unavailable_reason: typeof raw?.unavailable_reason === 'string' ? raw.unavailable_reason : null,
  store: raw?.store && typeof raw.store === 'object' ? { id: num(raw.store.id), name: String(raw.store.name ?? '') } : null,
});

const normalizeGroup = (raw: any): CartStoreGroup => ({
  key: String(raw?.key ?? raw?.store_id ?? 'store'),
  store_id: raw?.store_id === null || raw?.store_id === undefined ? null : num(raw.store_id),
  store_name: typeof raw?.store_name === 'string' && raw.store_name ? raw.store_name : 'ร้านค้า',
  items_count: num(raw?.items_count),
  subtotal: num(raw?.subtotal),
  delivery_method: raw?.delivery_method === 'rider' ? 'rider' : 'parcel',
  shipping_fee: num(raw?.shipping_fee),
  parcel_fee: num(raw?.parcel_fee),
  discount: num(raw?.discount),
  total: num(raw?.total),
  rider: {
    available: raw?.rider?.available === true,
    reason: typeof raw?.rider?.reason === 'string' ? raw.rider.reason : null,
    fee: raw?.rider?.fee === null || raw?.rider?.fee === undefined ? null : num(raw.rider.fee),
    distance_km: raw?.rider?.distance_km === null || raw?.rider?.distance_km === undefined ? null : num(raw.rider.distance_km),
    estimated_minutes:
      raw?.rider?.estimated_minutes === null || raw?.rider?.estimated_minutes === undefined ? null : num(raw.rider.estimated_minutes),
  },
  cod: {
    available: raw?.cod?.available === true,
    reason: typeof raw?.cod?.reason === 'string' ? raw.cod.reason : null,
    limit: raw?.cod?.limit === null || raw?.cod?.limit === undefined ? null : num(raw.cod.limit),
  },
});

/** แปลงตะกร้าจาก server เป็นรูปแบบที่แอปใช้ได้แน่นอน */
export const normalizeCart = (raw: any): Cart => {
  const items = Array.isArray(raw?.items) ? raw.items.map(normalizeItem) : [];
  const stores = Array.isArray(raw?.stores) ? raw.stores.map(normalizeGroup) : [];
  const s = raw?.summary ?? {};
  return {
    cart_id: raw?.cart_id === null || raw?.cart_id === undefined ? null : num(raw.cart_id),
    items,
    stores,
    address:
      raw?.address && typeof raw.address === 'object'
        ? {
            id: num(raw.address.id),
            recipient_name: String(raw.address.recipient_name ?? ''),
            full_address: String(raw.address.full_address ?? ''),
            has_location: raw.address.has_location === true,
          }
        : null,
    delivery_method: raw?.delivery_method === 'rider' ? 'rider' : 'parcel',
    coupon:
      raw?.coupon && typeof raw.coupon === 'object'
        ? {
            code: String(raw.coupon.code ?? ''),
            store_id: raw.coupon.store_id === null || raw.coupon.store_id === undefined ? null : num(raw.coupon.store_id),
            discount_type: String(raw.coupon.discount_type ?? ''),
            discount: num(raw.coupon.discount),
          }
        : null,
    coupon_error:
      raw?.coupon_error && typeof raw.coupon_error === 'object'
        ? { code: String(raw.coupon_error.code ?? 'COUPON_INVALID'), message: String(raw.coupon_error.message ?? '') }
        : null,
    summary: {
      items_count: num(s.items_count, items.reduce((sum: number, i: CartItem) => sum + i.quantity, 0)),
      available_items_count: num(s.available_items_count),
      unavailable_count: num(s.unavailable_count),
      subtotal: num(s.subtotal),
      shipping_fee: num(s.shipping_fee),
      discount: num(s.discount),
      grand_total: num(s.grand_total),
      free_shipping_threshold: s.free_shipping_threshold === null || s.free_shipping_threshold === undefined ? null : num(s.free_shipping_threshold),
      amount_to_free_shipping: s.amount_to_free_shipping === null || s.amount_to_free_shipping === undefined ? null : num(s.amount_to_free_shipping),
      rider_available: s.rider_available === true,
      cod_available: s.cod_available === true,
    },
  };
};

// =====================================================
// ลำดับคำสั่ง (กันผลเก่าทับผลใหม่) + เจ้าของตะกร้า
// =====================================================

let issuedSeq = 0;
let appliedSeq = 0;

const currentUserId = (): number | null => {
  const { isAuthenticated, user } = useAuthStore.getState();
  if (!isAuthenticated) return null;
  const id = Number((user as { id?: unknown } | null)?.id);
  return Number.isFinite(id) ? id : null;
};

/** ผู้ใช้ที่เป็นเจ้าของแคชตอนนี้ */
let cacheOwner: number | null = null;

const notLoggedIn = (): ApiResult<Cart> => ({
  success: false,
  code: 'UNAUTHENTICATED',
  status: 401,
  message: 'เข้าสู่ระบบก่อนนะ',
});

let legacyCleaned = false;
const cleanLegacyCart = () => {
  if (legacyCleaned) return;
  legacyCleaned = true;
  AsyncStorage.removeItem(LEGACY_CART_STORAGE_KEY).catch(() => {});
};

export const useCartStore = create<CartState>((set, get) => {
  /**
   * เรียก API ที่คืนตะกร้าทั้งใบ แล้วแทน state ถ้าเป็นผลของคำสั่งล่าสุดและยังเป็นผู้ใช้คนเดิม
   */
  const run = async (call: () => Promise<ApiResult<Cart>>, isFetch: boolean): Promise<ApiResult<Cart>> => {
    const userId = currentUserId();
    if (userId === null) {
      return notLoggedIn();
    }
    cleanLegacyCart();

    const seq = ++issuedSeq;
    if (isFetch && get().cart === null) {
      set({ loading: true });
    }

    const result = await call();

    // ผู้ใช้เปลี่ยนระหว่างรอ → ทิ้งผล
    if (currentUserId() !== userId) {
      return result;
    }

    if (result.success) {
      const cart = normalizeCart(result.data);
      if (seq >= appliedSeq) {
        appliedSeq = seq;
        cacheOwner = userId;
        set({
          cart,
          count: cart.summary.items_count,
          loading: false,
          error: null,
          lastFetchedAt: Date.now(),
        });
      }
      return { ...result, data: cart };
    }

    set({ loading: false, error: isFetch ? result.message : get().error });
    return result;
  };

  return {
    cart: null,
    count: 0,
    loading: false,
    error: null,
    lastFetchedAt: null,

    refresh: (query = {}) => run(() => getCart(query), true),

    ensureFresh: async (maxAgeMs = 60_000) => {
      const { lastFetchedAt } = get();
      const userId = currentUserId();
      if (userId === null) return;
      if (cacheOwner === userId && lastFetchedAt && Date.now() - lastFetchedAt < maxAgeMs) return;
      await get().refresh();
    },

    add: (productId, quantity = 1, attributes) => run(() => addCartItem(productId, quantity, attributes), false),

    setQuantity: (itemId, quantity) =>
      quantity <= 0
        ? run(() => removeCartItem(itemId), false)
        : run(() => updateCartItem(itemId, quantity), false),

    remove: (itemId) => run(() => removeCartItem(itemId), false),

    clear: () => run(() => clearCartApi(), false),

    replace: (cart) => {
      const normalized = normalizeCart(cart);
      appliedSeq = ++issuedSeq;
      cacheOwner = currentUserId();
      set({ cart: normalized, count: normalized.summary.items_count, error: null, lastFetchedAt: Date.now() });
    },

    reset: () => {
      appliedSeq = ++issuedSeq;
      cacheOwner = null;
      set({ cart: null, count: 0, loading: false, error: null, lastFetchedAt: null });
    },
  };
});

/** id ผู้ใช้จาก state ของ authStore (ไม่มี/ไม่ใช่ตัวเลข = null) */
const userIdOf = (state: { isAuthenticated: boolean; user: unknown }): number | null => {
  if (!state.isAuthenticated) return null;
  const id = Number((state.user as { id?: unknown } | null)?.id);
  return Number.isFinite(id) ? id : null;
};

// ออกจากระบบ / เปลี่ยนบัญชี → ล้างแคชตะกร้าทันที
useAuthStore.subscribe((state, prev) => {
  if (userIdOf(state) !== userIdOf(prev)) {
    useCartStore.getState().reset();
  }
});

export default useCartStore;
