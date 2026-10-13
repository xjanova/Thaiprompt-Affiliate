/**
 * ทดสอบค่าส่งในตะกร้าตลาดสด — ต้องเป็นค่าที่ผู้ซื้อจ่ายจริง (ตรงกับหน้าชำระเงิน/server)
 *
 * รัน: npx jest components/taladsod/__tests__/cartFee.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { cartFeeView } from '../cartFee';

describe('cartFeeView', () => {
  it('ร้านช่วยออกบางส่วน = แสดงที่ผู้ซื้อจ่าย + ส่วนที่ร้านออก', () => {
    expect(cartFeeView({ total_fee: 45, buyer_fee: 25, shop_subsidy: 20, free_delivery: false })).toEqual({
      buyerFee: 25,
      shopPays: 20,
      free: false,
    });
  });

  it('server ไม่บอก shop_subsidy = ใช้ส่วนต่างค่าส่งเต็ม − ที่ผู้ซื้อจ่าย', () => {
    expect(cartFeeView({ total_fee: 40, buyer_fee: 30, shop_subsidy: null, free_delivery: false })).toEqual({
      buyerFee: 30,
      shopPays: 10,
      free: false,
    });
  });

  it('ร้านออกให้ทั้งหมด = ส่งฟรี', () => {
    const v = cartFeeView({ total_fee: 35, buyer_fee: 0, shop_subsidy: 35, free_delivery: true });
    expect(v.buyerFee).toBe(0);
    expect(v.free).toBe(true);
    expect(v.shopPays).toBe(35);
  });

  it('ไม่มีร้านช่วย = ค่าส่งเต็ม', () => {
    expect(cartFeeView({ total_fee: 38, buyer_fee: 38, shop_subsidy: 0, free_delivery: false })).toEqual({
      buyerFee: 38,
      shopPays: 0,
      free: false,
    });
  });
});
