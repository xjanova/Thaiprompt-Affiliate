/**
 * บรรทัดของการ์ด "เงินที่พักไว้ ถูกแบ่งแล้ว" — แสดงเฉพาะ "ผู้รับเงิน" (U6)
 *
 * server ส่ง lines มาทั้งฝั่งผู้จ่าย (items = ค่าสินค้า · delivery = ค่าส่งที่คุณจ่าย)
 * และฝั่งผู้รับ (seller · rider · referrer · platform) + cashback (เงินคืนให้ผู้ซื้อ)
 * การ์ดนี้ตอบคำถาม "เงินที่จ่ายไปถึงใครบ้าง" → แสดงเฉพาะผู้รับ 4 กลุ่ม เรียงตามลำดับคงที่
 * ส่วน cashback แยกไปแสดงใต้ยอดรวม (ไม่ปนกับผู้รับ)
 *
 * ไฟล์นี้ไม่มี React — ทดสอบด้วย jest ได้ตรงๆ
 */

import type { Settlement, SettlementLine } from '@/services/api/handoverApi';

/** กลุ่มผู้รับเงิน */
export type PayeeKind = 'seller' | 'rider' | 'referrer' | 'platform';

/** ลำดับแสดงผล */
export const PAYEE_ORDER: PayeeKind[] = ['seller', 'rider', 'referrer', 'platform'];

/** ป้ายสำรองเมื่อ server ไม่ส่ง lines มา (server รุ่นเก่า) */
const FALLBACK_LABEL: Record<PayeeKind, string> = {
  seller: 'ร้านค้าได้รับ',
  rider: 'ไรเดอร์ได้รับ',
  referrer: 'ค่าแนะนำเพื่อน',
  platform: 'ค่าบริการแพลตฟอร์ม',
};

/**
 * กลุ่มของบรรทัดจาก key (null = ไม่ใช่ผู้รับ เช่น items / delivery / cashback / refund)
 *
 * @example payeeKindOf('seller') // 'seller'
 * @example payeeKindOf('delivery') // null
 */
export const payeeKindOf = (key: string | null | undefined): PayeeKind | null => {
  const k = String(key || '').toLowerCase().trim();
  if (!k) return null;
  // ฝั่งผู้จ่าย / เงินคืน — ไม่ใช่ผู้รับ (เช็คก่อน เพราะ delivery_fee มีคำว่า fee)
  if (/^(items?|subtotal|delivery|shipping|discount|coupon|cashback|refund|buyer|total)/.test(k)) return null;
  if (k === 'seller' || k.startsWith('seller_') || k === 'store' || k === 'shop') return 'seller';
  if (k === 'rider' || k.startsWith('rider_')) return 'rider';
  if (k === 'referrer' || k.startsWith('referr') || k.startsWith('mlm') || k.startsWith('upline')) return 'referrer';
  if (k === 'platform' || k.startsWith('platform_') || k === 'gp') return 'platform';
  return null;
};

export interface PayeeLine extends SettlementLine {
  kind: PayeeKind;
}

/**
 * บรรทัดผู้รับเงินที่จะแสดง (เรียง ร้าน → ไรเดอร์ → ผู้แนะนำ → แพลตฟอร์ม · ตัดยอด 0 ของผู้แนะนำ)
 * server ไม่ส่ง lines / ไม่มีบรรทัดผู้รับเลย → สร้างจากยอดรวมแต่ละฝ่าย
 */
export const settlementPayeeLines = (settlement: Settlement): PayeeLine[] => {
  const fromLines: PayeeLine[] = [];
  for (const line of settlement.lines) {
    const kind = payeeKindOf(line.key);
    if (kind) fromLines.push({ ...line, kind });
  }

  const source: PayeeLine[] =
    fromLines.length > 0
      ? fromLines
      : PAYEE_ORDER.map((kind) => ({
          key: kind,
          kind,
          label: FALLBACK_LABEL[kind],
          amount:
            kind === 'seller'
              ? settlement.seller_amount
              : kind === 'rider'
                ? settlement.rider_amount
                : kind === 'referrer'
                  ? settlement.referrer_amount
                  : settlement.platform_amount,
          note: null,
        }));

  return source
    .filter((line) => Number.isFinite(line.amount) && (line.amount > 0 || line.kind === 'seller' || line.kind === 'rider'))
    .sort((a, b) => PAYEE_ORDER.indexOf(a.kind) - PAYEE_ORDER.indexOf(b.kind));
};

/** เงินคืนให้ผู้ซื้อ (cashback) รวมทุกบรรทัด — 0 = ไม่มี */
export const settlementCashback = (settlement: Settlement): number =>
  settlement.lines
    .filter((line) => /^cashback/i.test(String(line.key || '')))
    .reduce((sum, line) => sum + (Number.isFinite(line.amount) && line.amount > 0 ? line.amount : 0), 0);
