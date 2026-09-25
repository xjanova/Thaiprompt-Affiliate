/**
 * Taladsod Cart Store — แคชบางๆ ของ "ตะกร้าตลาดสดบน server" (แยกจากตะกร้าร้านค้า)
 *
 * หลักการเดียวกับ cartStore:
 *   - server เป็นข้อมูลหลัก: ทุกคำสั่งคืนตะกร้าทั้งใบ → แทน state ทั้งก้อน
 *   - คำสั่งซ้อนกัน → ใช้ผลของคำสั่งล่าสุดเท่านั้น (seq) กันตะกร้าเด้งกลับค่าเก่า
 *   - เปลี่ยนบัญชี/ออกจากระบบ → ไม่ให้เห็นตะกร้าของคนก่อน
 *
 * @example
 * const count = useTaladsodCartStore((s) => s.count);
 * const res = await useTaladsodCartStore.getState().add({ listing_id, quantity, option_ids, note });
 */

import { create } from 'zustand';
import { useAuthStore } from '@/stores/authStore';
import {
  addFmCartItem,
  clearFmCart,
  getFmCart,
  removeFmCartItem,
  updateFmCartItem,
  type FmCart,
} from '@/services/api/taladsodApi';
import type { ApiResult } from '@/services/api/client';

interface TaladsodCartState {
  cart: FmCart | null;
  /** จำนวนชิ้นรวม (badge) */
  count: number;
  loading: boolean;
  error: string | null;
  lastFetchedAt: number | null;

  refresh: () => Promise<ApiResult<FmCart>>;
  ensureFresh: (maxAgeMs?: number) => Promise<void>;
  add: (input: { listing_id: number; quantity: number; option_ids: number[]; note?: string | null }) => Promise<ApiResult<FmCart>>;
  update: (itemId: number, patch: { quantity?: number; option_ids?: number[]; note?: string | null }) => Promise<ApiResult<FmCart>>;
  remove: (itemId: number) => Promise<ApiResult<FmCart>>;
  clearShop: (sellerId: number) => Promise<ApiResult<FmCart>>;
  /** แทนด้วยตะกร้าที่ได้จากที่อื่น */
  replace: (cart: FmCart) => void;
  reset: () => void;
}

let issuedSeq = 0;
let appliedSeq = 0;
let cacheOwner: number | null = null;

const currentUserId = (): number | null => {
  const { isAuthenticated, user } = useAuthStore.getState();
  if (!isAuthenticated) return null;
  const id = Number((user as { id?: unknown } | null)?.id);
  return Number.isFinite(id) ? id : null;
};

const notLoggedIn = (): ApiResult<FmCart> => ({
  success: false,
  code: 'UNAUTHENTICATED',
  status: 401,
  message: 'เข้าสู่ระบบก่อนนะ',
});

export const useTaladsodCartStore = create<TaladsodCartState>((set, get) => {
  const run = async (call: () => Promise<ApiResult<FmCart>>, isFetch: boolean): Promise<ApiResult<FmCart>> => {
    const userId = currentUserId();
    if (userId === null) return notLoggedIn();

    // เปลี่ยนบัญชี → ล้างของคนก่อนทันที
    if (cacheOwner !== null && cacheOwner !== userId) {
      cacheOwner = null;
      set({ cart: null, count: 0, lastFetchedAt: null, error: null });
    }

    const seq = ++issuedSeq;
    if (isFetch && get().cart === null) set({ loading: true });

    const result = await call();

    if (currentUserId() !== userId) return result;

    if (result.success) {
      if (seq >= appliedSeq) {
        appliedSeq = seq;
        cacheOwner = userId;
        set({
          cart: result.data,
          count: result.data.items_count,
          loading: false,
          error: null,
          lastFetchedAt: Date.now(),
        });
      }
      return result;
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

    refresh: () => run(() => getFmCart(), true),

    ensureFresh: async (maxAgeMs = 60_000) => {
      const userId = currentUserId();
      if (userId === null) {
        if (get().cart !== null) get().reset();
        return;
      }
      const { lastFetchedAt } = get();
      if (cacheOwner === userId && lastFetchedAt && Date.now() - lastFetchedAt < maxAgeMs) return;
      await get().refresh();
    },

    add: (input) => run(() => addFmCartItem(input), false),

    update: (itemId, patch) =>
      typeof patch.quantity === 'number' && patch.quantity <= 0
        ? run(() => removeFmCartItem(itemId), false)
        : run(() => updateFmCartItem(itemId, patch), false),

    remove: (itemId) => run(() => removeFmCartItem(itemId), false),

    clearShop: (sellerId) => run(() => clearFmCart(sellerId), false),

    replace: (cart) => {
      const userId = currentUserId();
      if (userId === null) return;
      appliedSeq = ++issuedSeq;
      cacheOwner = userId;
      set({ cart, count: cart.items_count, error: null, lastFetchedAt: Date.now() });
    },

    reset: () => {
      appliedSeq = ++issuedSeq;
      cacheOwner = null;
      set({ cart: null, count: 0, loading: false, error: null, lastFetchedAt: null });
    },
  };
});

// ออกจากระบบ → ล้างตะกร้าตลาดสดในเครื่องทันที
useAuthStore.subscribe((state, prev) => {
  if (prev?.isAuthenticated && !state.isAuthenticated) {
    useTaladsodCartStore.getState().reset();
  }
});
