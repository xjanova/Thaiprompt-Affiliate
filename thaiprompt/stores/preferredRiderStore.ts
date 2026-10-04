/**
 * Preferred Rider Store — ไรเดอร์ที่ผู้ซื้อกด "ล็อกเรียกคนนี้" ไว้ (ใช้ตอนชำระเงิน)
 *
 * - เก็บในหน่วยความจำเท่านั้น (ไม่ลงเครื่อง) และผูกกับบัญชีที่เลือก — เปลี่ยนบัญชี = ไม่ใช้ค่าของคนก่อน
 * - หมดอายุเองใน 2 ชั่วโมง (สิทธิ์ล็อกตรวจซ้ำที่ server ตอนสั่งเสมอ: 422 RIDER_LOCK_NOT_ALLOWED)
 *
 * @example
 * usePreferredRiderStore.getState().choose(userId, rider);
 * const choice = usePreferredRiderStore((s) => s.validFor(userId));
 */

import { create } from 'zustand';
import type { PersonCard } from '@/services/api/handoverApi';

/** อายุของการเลือก (2 ชั่วโมง) */
export const PREFERRED_RIDER_MAX_AGE_MS = 2 * 60 * 60 * 1000;

export interface PreferredRiderChoice {
  userId: number;
  rider: PersonCard;
  chosenAt: number;
}

/**
 * การเลือกนี้ยังใช้ได้กับบัญชีนี้ไหม (ของบัญชีนี้ + ไม่เกิน 2 ชั่วโมง) — ใช้ทุกจุดที่แสดง/ใช้ค่า (FIXES M5)
 * แยกเป็นฟังก์ชันเพื่อให้ selector ของ zustand / เทสต์ เรียกใช้ได้ตรงๆ
 */
export const isPreferredChoiceValid = (
  choice: PreferredRiderChoice | null | undefined,
  userId: number | null | undefined,
  now: number = Date.now()
): choice is PreferredRiderChoice =>
  !!choice && !!userId && choice.userId === userId && now - choice.chosenAt <= PREFERRED_RIDER_MAX_AGE_MS;

interface PreferredRiderState {
  choice: PreferredRiderChoice | null;
  /** เลือกไรเดอร์คนโปรดสำหรับออเดอร์ถัดไป */
  choose: (userId: number, rider: PersonCard) => void;
  clear: () => void;
  /** ค่าที่ยังใช้ได้ของบัญชีนี้ (ไม่ใช่ของคนอื่น/ไม่หมดอายุ) */
  validFor: (userId: number | null | undefined) => PreferredRiderChoice | null;
}

export const usePreferredRiderStore = create<PreferredRiderState>((set, get) => ({
  choice: null,
  choose: (userId, rider) => set({ choice: { userId, rider, chosenAt: Date.now() } }),
  clear: () => set({ choice: null }),
  validFor: (userId) => {
    const c = get().choice;
    return isPreferredChoiceValid(c, userId) ? c : null;
  },
}));
