/**
 * ตำแหน่งสดของร้านเคลื่อนที่ (รถเข็น / ตลาดนัด) — ส่ง POST /fresh-market/seller/location ทุก 30 วินาที
 *
 * กติกา
 *   - ส่งเฉพาะตอนแอปอยู่หน้าจอ (AppState active) เท่านั้น — ปิดแอป/สลับแอป = หยุดชั่วคราว กลับมา = ส่งต่อทันที
 *     (ไม่ขอสิทธิ์ตำแหน่งเบื้องหลัง · ถ้าไม่ได้รับตำแหน่งเกิน 30 นาที server ปิดร้านให้เอง)
 *   - ตัวส่งมีตัวเดียวทั้งแอป (module singleton) — เดินไปหน้าออเดอร์/หน้าอื่นก็ยังส่งต่อ
 *   - 409 SHOP_CLOSED / 422 NOT_MOBILE_SHOP / 403 NOT_SELLER / ออกจากระบบ / ไม่มีสิทธิ์ตำแหน่ง → หยุดเอง
 *   - 429 / เน็ตหลุด / GPS หาไม่เจอ → ข้ามรอบนั้น ไม่หยุด
 *
 * หน้าจอใช้ subscribeShopLiveSharing() เพื่อแสดง "ส่งล่าสุดเมื่อ..." และรู้ว่าตัวส่งหยุดเพราะอะไร
 */

import { AppState, type AppStateStatus, type NativeEventSubscription } from 'react-native';
import * as Location from 'expo-location';
import { useAuthStore } from '@/stores/authStore';
import { sendFmShopLocation } from '@/services/api/taladsodSellerApi';
import { getCurrentCoords } from '@/services/location';

export type ShopLiveStopReason = 'shop_closed' | 'not_mobile' | 'not_seller' | 'permission' | 'logout' | 'manual';

export interface ShopLiveState {
  /** ตัวส่งทำงานอยู่ (อาจหยุดชั่วคราวเพราะแอปอยู่เบื้องหลัง) */
  active: boolean;
  /** แอปอยู่เบื้องหลัง → ยังไม่ส่ง */
  paused: boolean;
  /** กำลังส่งรอบนี้อยู่ */
  sending: boolean;
  /** เวลาที่ server รับตำแหน่งล่าสุด (ms) */
  lastSentAt: number | null;
  /** ตำแหน่งล่าสุดที่ server รับแล้ว (ใช้ขยับหมุดบนแผนที่) */
  lastCoords: { latitude: number; longitude: number } | null;
  /** ข้อความไทยของรอบล่าสุดที่ส่งไม่สำเร็จ (null = ปกติ) */
  lastError: string | null;
  /** หยุดเพราะอะไร (null = ยังทำงาน / ยังไม่เคยเริ่ม) */
  stoppedReason: ShopLiveStopReason | null;
  /** ส่งทุกกี่มิลลิวินาที */
  intervalMs: number;
}

type Listener = (state: ShopLiveState) => void;

const DEFAULT_INTERVAL_MS = 30_000;
/** กลับมาจากเบื้องหลังแล้วส่งทันทีถ้ารอบล่าสุดเก่ากว่านี้ (กันชน throttle 6/นาทีของ server) */
const RESUME_MIN_GAP_MS = 20_000;

let state: ShopLiveState = {
  active: false,
  paused: false,
  sending: false,
  lastSentAt: null,
  lastCoords: null,
  lastError: null,
  stoppedReason: null,
  intervalMs: DEFAULT_INTERVAL_MS,
};

const listeners = new Set<Listener>();
let timer: ReturnType<typeof setInterval> | null = null;
let appStateSub: NativeEventSubscription | null = null;
let inFlight = false;

const emit = (patch: Partial<ShopLiveState>) => {
  state = { ...state, ...patch };
  listeners.forEach((listener) => {
    try {
      listener(state);
    } catch {
      // listener พังต้องไม่ทำให้ตัวส่งหยุด
    }
  });
};

const clearTimer = () => {
  if (timer) {
    clearInterval(timer);
    timer = null;
  }
};

const startTimer = () => {
  clearTimer();
  timer = setInterval(() => {
    sendNow().catch(() => {});
  }, state.intervalMs);
};

const handleAppState = (next: AppStateStatus) => {
  if (!state.active) return;
  if (next === 'active') {
    emit({ paused: false });
    const stale = !state.lastSentAt || Date.now() - state.lastSentAt >= RESUME_MIN_GAP_MS;
    if (stale) sendNow().catch(() => {});
    startTimer();
  } else {
    // เบื้องหลัง: หยุดส่ง (แอปไม่ขอสิทธิ์ตำแหน่งเบื้องหลังสำหรับร้าน)
    clearTimer();
    emit({ paused: true });
  }
};

/** ส่งตำแหน่งหนึ่งรอบ (เรียกซ้อนกันไม่ได้) */
const sendNow = async (): Promise<void> => {
  if (!state.active || inFlight || AppState.currentState !== 'active') return;

  if (!useAuthStore.getState().isAuthenticated) {
    stopShopLiveSharing('logout');
    return;
  }

  inFlight = true;
  emit({ sending: true });
  try {
    const permission = await Location.getForegroundPermissionsAsync().catch(() => null);
    if (!permission || permission.status !== 'granted') {
      stopShopLiveSharing('permission');
      return;
    }

    const coords = await getCurrentCoords({ accuracy: 'high', timeoutMs: 10_000 });
    if (!state.active) return;
    if (!coords) {
      emit({ lastError: 'ยังหาตำแหน่งไม่ได้ ลองเปิด GPS หรือออกไปที่โล่งนะ' });
      return;
    }

    const result = await sendFmShopLocation({
      latitude: coords.latitude,
      longitude: coords.longitude,
      ...(typeof coords.accuracy === 'number' ? { accuracy: Math.round(coords.accuracy) } : {}),
    });
    if (!state.active) return;

    if (result.success) {
      if (!result.data.is_open) {
        stopShopLiveSharing('shop_closed');
        return;
      }
      const nextMs = result.data.next_send_seconds * 1000;
      const changed = nextMs !== state.intervalMs;
      const point = result.data.location
        ? { latitude: result.data.location.latitude, longitude: result.data.location.longitude }
        : { latitude: coords.latitude, longitude: coords.longitude };
      emit({ lastSentAt: Date.now(), lastCoords: point, lastError: null, intervalMs: nextMs });
      if (changed && timer) startTimer();
      return;
    }

    switch (result.code) {
      case 'SHOP_CLOSED':
        stopShopLiveSharing('shop_closed');
        return;
      case 'NOT_MOBILE_SHOP':
        stopShopLiveSharing('not_mobile');
        return;
      case 'NOT_SELLER':
        stopShopLiveSharing('not_seller');
        return;
      case 'UNAUTHENTICATED':
        stopShopLiveSharing('logout');
        return;
      case 'TOO_MANY_REQUESTS':
        // ส่งถี่ไปนิด — รอบหน้าค่อยส่ง
        return;
      default:
        emit({ lastError: result.message });
    }
  } finally {
    inFlight = false;
    if (state.sending) emit({ sending: false });
  }
};

/**
 * เริ่มส่งตำแหน่งสด (เรียกซ้ำได้ ไม่เริ่มซ้อน) — ส่งรอบแรกทันที
 * ต้องได้สิทธิ์ตำแหน่ง "ขณะใช้แอป" มาก่อนแล้ว (หน้าจอแสดง ConsentSheet + ขอสิทธิ์เอง)
 */
export const startShopLiveSharing = (): void => {
  if (state.active) {
    // เริ่มอยู่แล้ว — ถ้ารอบล่าสุดเก่ามากให้ส่งเลย
    if (!state.lastSentAt || Date.now() - state.lastSentAt >= RESUME_MIN_GAP_MS) {
      sendNow().catch(() => {});
    }
    return;
  }
  emit({
    active: true,
    paused: AppState.currentState !== 'active',
    stoppedReason: null,
    lastError: null,
  });
  appStateSub?.remove();
  appStateSub = AppState.addEventListener('change', handleAppState);
  if (AppState.currentState === 'active') {
    sendNow().catch(() => {});
    startTimer();
  }
};

/** หยุดส่งตำแหน่งสด */
export const stopShopLiveSharing = (reason: ShopLiveStopReason = 'manual'): void => {
  clearTimer();
  appStateSub?.remove();
  appStateSub = null;
  if (!state.active && state.stoppedReason === reason) return;
  emit({ active: false, paused: false, sending: false, stoppedReason: reason });
};

/** ส่งตำแหน่งทันทีหนึ่งครั้ง (ปุ่ม "อัปเดตตำแหน่งตอนนี้") — ต้องเริ่มตัวส่งอยู่แล้ว */
export const pushShopLocationNow = (): Promise<void> => sendNow();

export const getShopLiveState = (): ShopLiveState => state;

/** ติดตามสถานะตัวส่ง — คืนฟังก์ชันยกเลิก */
export const subscribeShopLiveSharing = (listener: Listener): (() => void) => {
  listeners.add(listener);
  listener(state);
  return () => {
    listeners.delete(listener);
  };
};
