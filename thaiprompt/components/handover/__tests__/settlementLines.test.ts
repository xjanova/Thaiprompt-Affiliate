/**
 * ทดสอบการคัดบรรทัด "เงินที่พักไว้ ถูกแบ่งแล้ว" — แสดงเฉพาะผู้รับเงิน (U6)
 *
 * รัน: npx jest components/handover/__tests__/settlementLines.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import type { Settlement } from '@/services/api/handoverApi';
import { payeeKindOf, settlementCashback, settlementPayeeLines } from '../settlementLines';

/** ตัวอย่างตามที่ HandoverService ส่งจริง (มีทั้งบรรทัดผู้จ่ายและผู้รับ) */
const SERVER_SETTLEMENT: Settlement = {
  total_paid: 158,
  seller_amount: 104,
  rider_amount: 48,
  referrer_amount: 1.2,
  platform_amount: 4.8,
  lines: [
    { key: 'items', label: 'ค่าสินค้า', amount: 120, note: null },
    { key: 'delivery', label: 'ค่าส่งที่คุณจ่าย', amount: 38, note: null },
    { key: 'platform', label: 'ค่าบริการแพลตฟอร์ม', amount: 4.8, note: 'รวมค่าธรรมเนียมและภาษี' },
    { key: 'rider', label: 'ไรเดอร์ได้รับ', amount: 48, note: 'รวมโบนัสจากร้าน ฿10.00' },
    { key: 'seller', label: 'ร้านค้าได้รับ', amount: 104, note: 'หลังหักค่าธรรมเนียม' },
    { key: 'referrer', label: 'ค่าแนะนำเพื่อน', amount: 1.2, note: null },
    { key: 'cashback', label: 'เงินคืนให้คุณ', amount: 2, note: 'คืนจากส่วนของแพลตฟอร์ม' },
  ],
};

describe('payeeKindOf', () => {
  it('จับกลุ่มผู้รับ · บรรทัดผู้จ่าย/เงินคืนไม่ใช่ผู้รับ', () => {
    expect(payeeKindOf('seller')).toBe('seller');
    expect(payeeKindOf('rider')).toBe('rider');
    expect(payeeKindOf('rider_bonus')).toBe('rider');
    expect(payeeKindOf('referrer')).toBe('referrer');
    expect(payeeKindOf('mlm_upline')).toBe('referrer');
    expect(payeeKindOf('platform')).toBe('platform');
    expect(payeeKindOf('items')).toBeNull();
    expect(payeeKindOf('delivery')).toBeNull();
    expect(payeeKindOf('delivery_fee')).toBeNull();
    expect(payeeKindOf('cashback')).toBeNull();
    expect(payeeKindOf('')).toBeNull();
    expect(payeeKindOf(undefined)).toBeNull();
  });
});

describe('settlementPayeeLines', () => {
  it('ตัดค่าสินค้า/ค่าส่งออก แล้วเรียง ร้าน → ไรเดอร์ → ผู้แนะนำ → แพลตฟอร์ม', () => {
    const lines = settlementPayeeLines(SERVER_SETTLEMENT);
    expect(lines.map((l) => l.kind)).toEqual(['seller', 'rider', 'referrer', 'platform']);
    expect(lines.map((l) => l.label)).toEqual(['ร้านค้าได้รับ', 'ไรเดอร์ได้รับ', 'ค่าแนะนำเพื่อน', 'ค่าบริการแพลตฟอร์ม']);
    expect(lines.find((l) => l.kind === 'rider')?.note).toBe('รวมโบนัสจากร้าน ฿10.00');
  });

  it('ผู้แนะนำ/แพลตฟอร์มยอด 0 ไม่แสดง', () => {
    const lines = settlementPayeeLines({
      ...SERVER_SETTLEMENT,
      lines: SERVER_SETTLEMENT.lines.map((l) => (l.key === 'referrer' ? { ...l, amount: 0 } : l)),
    });
    expect(lines.some((l) => l.kind === 'referrer')).toBe(false);
  });

  it('server เก่าไม่มี lines → สร้างจากยอดรวมแต่ละฝ่าย', () => {
    const lines = settlementPayeeLines({ ...SERVER_SETTLEMENT, referrer_amount: 0, lines: [] });
    expect(lines.map((l) => [l.kind, l.amount])).toEqual([
      ['seller', 104],
      ['rider', 48],
      ['platform', 4.8],
    ]);
  });

  it('มีแต่บรรทัดผู้จ่าย → ใช้ยอดรวมแต่ละฝ่ายแทน (ไม่แสดงการ์ดว่าง)', () => {
    const lines = settlementPayeeLines({
      ...SERVER_SETTLEMENT,
      lines: SERVER_SETTLEMENT.lines.filter((l) => l.key === 'items' || l.key === 'delivery'),
    });
    expect(lines.map((l) => l.kind)).toEqual(['seller', 'rider', 'referrer', 'platform']);
  });
});

describe('settlementCashback', () => {
  it('รวมเงินคืนให้ผู้ซื้อ (แยกจากผู้รับ)', () => {
    expect(settlementCashback(SERVER_SETTLEMENT)).toBe(2);
    expect(settlementCashback({ ...SERVER_SETTLEMENT, lines: [] })).toBe(0);
  });
});
