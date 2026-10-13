/**
 * ทดสอบตัวกันยืนยันสั่งซื้อด้วยยอดเก่า (หน้าชำระเงินร้านค้า)
 *
 * รัน: npx jest utils/__tests__/checkoutQuote.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { canConfirmWithQuote, quoteFreshness, quoteParamsKey } from '../checkoutQuote';

const parcel = quoteParamsKey({ addressId: 5, delivery: 'parcel', coupon: null });
const rider = quoteParamsKey({ addressId: 5, delivery: 'rider', coupon: null });

describe('quoteParamsKey', () => {
  it('ตัวเลือกต่างกัน = กุญแจต่างกัน', () => {
    expect(parcel).not.toBe(rider);
    expect(quoteParamsKey({ addressId: 5, delivery: 'parcel', coupon: 'SALE' })).not.toBe(parcel);
    expect(quoteParamsKey({ addressId: 6, delivery: 'parcel', coupon: null })).not.toBe(parcel);
    expect(quoteParamsKey({ addressId: 5, delivery: 'parcel', coupon: null })).toBe(parcel);
  });
});

describe('quoteFreshness', () => {
  it('ยอดตรงกับตัวเลือกปัจจุบัน = สั่งได้', () => {
    const state = quoteFreshness({ hasQuote: true, loading: false, error: null, quoteKey: parcel, currentKey: parcel });
    expect(state).toBe('fresh');
    expect(canConfirmWithQuote(state)).toBe(true);
  });

  it('เปลี่ยนเป็นไรเดอร์แล้วขอยอดใหม่ไม่สำเร็จ = failed (ห้ามยืนยันด้วยยอดพัสดุเดิม)', () => {
    const state = quoteFreshness({
      hasQuote: true,
      loading: false,
      error: 'เชื่อมต่อไม่ได้',
      quoteKey: parcel,
      currentKey: rider,
    });
    expect(state).toBe('failed');
    expect(canConfirmWithQuote(state)).toBe(false);
  });

  it('ขอยอดซ้ำ (กลับมาที่หน้า) ไม่สำเร็จ แม้ตัวเลือกเดิม = failed', () => {
    expect(quoteFreshness({ hasQuote: true, loading: false, error: 'x', quoteKey: parcel, currentKey: parcel })).toBe('failed');
  });

  it('ตัวเลือกเปลี่ยนแล้วแต่ยังไม่ได้ยอดใหม่ = stale', () => {
    const state = quoteFreshness({ hasQuote: true, loading: false, error: null, quoteKey: parcel, currentKey: rider });
    expect(state).toBe('stale');
    expect(canConfirmWithQuote(state)).toBe(false);
  });

  it('กำลังโหลด / ยังไม่มียอด = loading', () => {
    expect(quoteFreshness({ hasQuote: true, loading: true, error: 'x', quoteKey: parcel, currentKey: parcel })).toBe('loading');
    expect(quoteFreshness({ hasQuote: false, loading: false, error: null, quoteKey: null, currentKey: parcel })).toBe('loading');
    expect(canConfirmWithQuote('loading')).toBe(false);
  });
});
