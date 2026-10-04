/**
 * ทดสอบค่าตอบแทนไรเดอร์ของร้าน — โบนัสต้องคงทศนิยม 2 ตำแหน่ง ไม่ถูกปัดแล้วเขียนทับเงียบๆ (M3)
 *
 * รัน: npx jest services/api/__tests__/riderPay.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { bonusValue, normalizeRiderPay, riderPayBody, riderPayPreviewParams } from '../sellerStoreApi';

describe('bonusValue', () => {
  it('คงทศนิยม 2 ตำแหน่ง · ตัดให้อยู่ช่วง 0–100', () => {
    expect(bonusValue(12.5)).toBe(12.5);
    expect(bonusValue('7.25')).toBe(7.25);
    expect(bonusValue(3.456)).toBe(3.46);
    expect(bonusValue(-5)).toBe(0);
    expect(bonusValue(250)).toBe(100);
    expect(bonusValue('abc')).toBe(0);
  });
});

describe('normalizeRiderPay / riderPayBody', () => {
  it('ค่าจาก server 12.50 ไม่ถูกปัดเป็น 13 → บันทึกค่าอื่นแล้วโบนัสเดิมยังเหมือนเดิม', () => {
    const pay = normalizeRiderPay({ settings: { rider_bonus: '12.50', rider_bonus_peak: 20, rider_free_delivery: false } });
    expect(pay.settings.rider_bonus).toBe(12.5);
    const body = riderPayBody({ ...pay.settings, rider_bonus_peak: 25 });
    expect(body).toEqual({ rider_bonus: 12.5, rider_bonus_peak: 25, rider_free_delivery: false });
    expect(riderPayPreviewParams({ bonus: 12.5, bonus_peak: 25, free_delivery: true })).toEqual({
      bonus: 12.5,
      bonus_peak: 25,
      free_delivery: 1,
    });
  });
});
