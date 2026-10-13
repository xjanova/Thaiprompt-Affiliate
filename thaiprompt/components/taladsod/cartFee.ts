/**
 * ค่าส่งไรเดอร์ที่แสดงในตะกร้าตลาดสด — ตัวช่วยล้วน (ทดสอบด้วย jest ได้)
 *
 * ต้องแสดง "ค่าส่งที่ผู้ซื้อจ่ายจริง" (buyer_fee) ให้ตรงกับหน้าชำระเงิน (taladsod/checkout.tsx)
 * และยอดที่ server เก็บ (FreshMarketCartService::quote → grand_total = ค่าอาหาร + buyer_fee)
 * ไม่ใช่ total_fee (ค่าส่งเต็ม) — ส่วนที่ร้านช่วยออกบอกเป็นหมายเหตุแยก
 */

import type { FmQuote } from '@/services/api/taladsodApi';

export interface CartFeeView {
  /** ค่าส่งที่ผู้ซื้อจ่าย (0 = ส่งฟรี) */
  buyerFee: number;
  /** ส่วนที่ร้านช่วยออกให้ (0 = ไม่มี) */
  shopPays: number;
  /** ผู้ซื้อไม่ต้องจ่ายค่าส่ง (ร้านออกให้ทั้งหมด) */
  free: boolean;
}

const round2 = (n: number): number => Math.round(n * 100) / 100;

export const cartFeeView = (quote: Pick<FmQuote, 'buyer_fee' | 'total_fee' | 'shop_subsidy' | 'free_delivery'>): CartFeeView => {
  const full = Number.isFinite(quote.total_fee) ? Math.max(0, quote.total_fee) : 0;
  const buyerFee = quote.free_delivery ? 0 : round2(Math.max(0, Number.isFinite(quote.buyer_fee) ? quote.buyer_fee : full));
  // ส่วนที่ร้านออก: ใช้ค่าที่ server บอก (shop_subsidy) ถ้ามี ไม่งั้นส่วนต่างค่าส่งเต็ม − ที่ผู้ซื้อจ่าย
  const subsidy = quote.shop_subsidy !== null && Number.isFinite(quote.shop_subsidy) && quote.shop_subsidy > 0 ? quote.shop_subsidy : 0;
  const gap = Math.max(0, full - buyerFee);
  const shopPays = round2(subsidy > 0 ? subsidy : gap);
  return { buyerFee, shopPays, free: buyerFee === 0 };
};
