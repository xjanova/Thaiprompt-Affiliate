/**
 * ทดสอบปุ่มยกเลิกเมื่อออเดอร์ล่าช้า (buyer_can_cancel / buyer_cancel_note)
 *
 * รัน: npx jest utils/__tests__/buyerCancel.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { LATE_CANCEL_FALLBACK_NOTE, buyerCancelView } from '../buyerCancel';

describe('buyerCancelView', () => {
  it('ไม่มีฟิลด์ใหม่ (server เก่า) = เหมือนเดิมทุกอย่าง', () => {
    expect(buyerCancelView(true, {})).toEqual({ show: true, late: false, note: null });
    expect(buyerCancelView(false, {})).toEqual({ show: false, late: false, note: null });
    expect(buyerCancelView(false, null)).toEqual({ show: false, late: false, note: null });
  });

  it('buyer_can_cancel = false ไม่เปิดปุ่มเพิ่ม', () => {
    expect(buyerCancelView(false, { buyer_can_cancel: false, buyer_cancel_note: 'ร้านช้า' })).toEqual({
      show: false,
      late: false,
      note: null,
    });
  });

  it('ล่าช้า = แสดงปุ่ม + หมายเหตุจาก server', () => {
    expect(
      buyerCancelView(false, { buyer_can_cancel: true, buyer_cancel_note: 'ร้านยังเตรียมของไม่เสร็จเกิน 45 นาที' })
    ).toEqual({ show: true, late: true, note: 'ร้านยังเตรียมของไม่เสร็จเกิน 45 นาที' });
  });

  it('ยกเลิกได้ตามปกติ (ไม่มีหมายเหตุ) = มีปุ่ม แต่ไม่ขึ้นแถบล่าช้า', () => {
    // 🐛 (2026-10-13) เดิมออเดอร์ที่เพิ่งสั่ง (ร้านยังไม่รับ) ขึ้น "ออเดอร์นี้ล่าช้ากว่าปกติ" ทันที
    expect(buyerCancelView(false, { buyer_can_cancel: true })).toEqual({ show: true, late: false, note: null });
    expect(buyerCancelView(true, { buyer_can_cancel: true, buyer_cancel_note: null })).toEqual({
      show: true,
      late: false,
      note: null,
    });
    expect(buyerCancelView(true, { buyer_can_cancel: true, buyer_cancel_note: '   ' }).late).toBe(false);
  });

  it('ล่าช้าแต่หมายเหตุไม่ใช่ภาษาไทย = ข้อความสำรอง', () => {
    expect(buyerCancelView(false, { buyer_can_cancel: true, buyer_cancel_note: 'NO_RIDER_30M' })).toEqual({
      show: true,
      late: true,
      note: LATE_CANCEL_FALLBACK_NOTE,
    });
  });

  it('ค่าที่ไม่ใช่ true จริง (เช่น "1") ไม่นับ', () => {
    expect(buyerCancelView(false, { buyer_can_cancel: '1' }).show).toBe(false);
  });
});
