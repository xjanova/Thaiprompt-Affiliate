/**
 * TPIX Price Store — แคชราคาเหรียญ TPIX ปัจจุบัน (GET /tpix/price) ใช้ร่วมกันทุกหน้า
 *
 * - โหลดเมื่อมีหน้าที่ต้องใช้ (useTpixSpot) · เก็บไว้ 60 วินาที (server แคช 30–60 วิ อยู่แล้ว)
 * - ใช้กับ: ยอดรวมโดยประมาณในตะกร้า/หน้าชำระเงิน · เช็คว่าเปิดรับชำระด้วย TPIX ไหม
 * - server เก่า (ไม่มีเส้นนี้) / ราคาไม่พร้อม → spot = null = ซ่อนทุกอย่างที่ต้องใช้ราคาสด
 *
 * @example
 * const { spot, paymentsEnabled } = useTpixSpot();
 */

import { useEffect } from 'react';
import { create } from 'zustand';
import { getTpixPrice, type TpixSpot } from '@/services/api/tpixApi';

const MAX_AGE_MS = 60_000;
/** โหลดไม่สำเร็จ → รอสักพักก่อนลองใหม่ (กันยิงถี่ตอนเปิดหลายหน้า) */
const RETRY_AFTER_MS = 30_000;

interface TpixPriceState {
  spot: TpixSpot | null;
  /** null = ยังไม่รู้ (ยังไม่เคยโหลดสำเร็จ) */
  paymentsEnabled: boolean | null;
  /** แอดมินเปิดแสดงราคา TPIX ข้างราคาบาท (ค่าเริ่มต้น true) */
  showPrices: boolean;
  /** เวลาที่โหลดสำเร็จล่าสุด */
  fetchedAt: number | null;
  /** เวลาที่ลองโหลดล่าสุด (สำเร็จหรือไม่ก็ตาม) */
  attemptedAt: number | null;
  loading: boolean;
  ensureFresh: (maxAgeMs?: number) => Promise<void>;
  refresh: () => Promise<void>;
}

let inflight: Promise<void> | null = null;

export const useTpixPriceStore = create<TpixPriceState>((set, get) => {
  const load = async (): Promise<void> => {
    if (inflight) return inflight;
    set({ loading: true });
    inflight = (async () => {
      try {
        const res = await getTpixPrice();
        if (res.success) {
          set({
            spot: res.data.spot,
            paymentsEnabled: res.data.payments_enabled,
            showPrices: res.data.show_prices,
            fetchedAt: Date.now(),
          });
        } else if (res.status === 404) {
          // server รุ่นที่ยังไม่มีระบบ TPIX
          set({ spot: null, paymentsEnabled: false });
        }
      } finally {
        set({ loading: false, attemptedAt: Date.now() });
        inflight = null;
      }
    })();
    return inflight;
  };

  return {
    spot: null,
    paymentsEnabled: null,
    showPrices: true,
    fetchedAt: null,
    attemptedAt: null,
    loading: false,

    ensureFresh: async (maxAgeMs = MAX_AGE_MS) => {
      const { fetchedAt, attemptedAt } = get();
      const now = Date.now();
      if (fetchedAt && now - fetchedAt < maxAgeMs) return;
      if (!fetchedAt && attemptedAt && now - attemptedAt < RETRY_AFTER_MS) return;
      await load();
    },

    refresh: () => load(),
  };
});

/**
 * ราคา TPIX ปัจจุบัน (โหลดให้อัตโนมัติเมื่อเก่ากว่า 60 วินาที)
 *
 * @param enabled false = อ่านค่าที่แคชไว้อย่างเดียว ไม่โหลด
 */
export const useTpixSpot = (enabled: boolean = true) => {
  const spot = useTpixPriceStore((s) => s.spot);
  const paymentsEnabled = useTpixPriceStore((s) => s.paymentsEnabled);
  const showPrices = useTpixPriceStore((s) => s.showPrices);
  const loading = useTpixPriceStore((s) => s.loading);

  useEffect(() => {
    if (enabled) useTpixPriceStore.getState().ensureFresh();
  }, [enabled]);

  return { spot, paymentsEnabled, showPrices, loading };
};
