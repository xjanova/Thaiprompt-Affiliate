/**
 * ทดสอบอายุ/เจ้าของของ "ไรเดอร์ที่ล็อกเรียกไว้" — ป้ายในหน้าไรเดอร์ใกล้ฉันใช้เกณฑ์เดียวกับหน้าชำระเงิน (M5)
 *
 * รัน: npx jest stores/__tests__/preferredRiderStore.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { PREFERRED_RIDER_MAX_AGE_MS, isPreferredChoiceValid, type PreferredRiderChoice } from '../preferredRiderStore';

const rider = {
  id: 12,
  display_name: 'สมชาย จ.',
  photo_url: null,
  vehicle_type: null,
  vehicle_label: null,
  plate_masked: null,
  hearts_total: 20,
  hearts_from_me: 12,
  can_lock: true,
};

const NOW = 1_800_000_000_000;
const choice = (chosenAt: number, userId = 7): PreferredRiderChoice => ({ userId, rider, chosenAt });

describe('isPreferredChoiceValid', () => {
  it('ของบัญชีนี้และยังไม่เกิน 2 ชั่วโมง = ใช้ได้', () => {
    expect(isPreferredChoiceValid(choice(NOW - 60_000), 7, NOW)).toBe(true);
    expect(isPreferredChoiceValid(choice(NOW - PREFERRED_RIDER_MAX_AGE_MS), 7, NOW)).toBe(true);
  });

  it('เกิน 2 ชั่วโมง = หมดอายุ (ป้ายต้องหายเหมือนหน้าชำระเงิน)', () => {
    expect(isPreferredChoiceValid(choice(NOW - PREFERRED_RIDER_MAX_AGE_MS - 1), 7, NOW)).toBe(false);
  });

  it('บัญชีอื่น / ไม่ได้เข้าสู่ระบบ / ไม่มีค่า = ใช้ไม่ได้', () => {
    expect(isPreferredChoiceValid(choice(NOW), 8, NOW)).toBe(false);
    expect(isPreferredChoiceValid(choice(NOW), null, NOW)).toBe(false);
    expect(isPreferredChoiceValid(null, 7, NOW)).toBe(false);
  });
});
