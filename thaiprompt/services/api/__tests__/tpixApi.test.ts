/**
 * ทดสอบตัวช่วยเหรียญ TPIX — ป้ายราคา · ยอดรวมโดยประมาณ (ราคาคงที่ไม่แปลงจากบาท) · ใบเสนอยอด · เลขธุรกรรม
 *
 * รัน: npx jest services/api/__tests__/tpixApi.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import {
  estimateTpixTotal,
  formatTpix,
  isTpixReference,
  isValidTxHash,
  normalizeTpixPayment,
  normalizeTpixTag,
} from '../tpixApi';

const ADDRESS = '0x1111111111111111111111111111111111111111';

const rawPayment = (overrides: Record<string, unknown> = {}) => ({
  reference: 'TPXABCDEFGHJKLMNPQRSTUVW',
  purpose: 'order',
  order_id: 42,
  order_number: 'ORD-42',
  status: 'pending',
  status_label: 'รอโอนเหรียญ',
  amount_thb: 1000,
  amount_tpix: '307.69241234',
  amount_tpix_exact: '307.69241234',
  receive_address: ADDRESS,
  chain_id: 4289,
  eip681: `ethereum:${ADDRESS}@4289?value=307692412340000000000`,
  pay_url: 'https://main.thaiprompt.online/tpix-pay/TPXABCDEFGHJKLMNPQRSTUVW',
  seconds_left: 900,
  confirmations: 0,
  confirmations_required: 3,
  ...overrides,
});

describe('normalizeTpixTag', () => {
  it('รับป้ายที่ถูกต้อง · null/ข้อมูลไม่ครบ = ซ่อน', () => {
    const tag = normalizeTpixTag({ amount: '307.69', raw: '307.6924', fixed: false, rate_thb: '3.25', rate_display: '฿3.2500' });
    expect(tag?.amount).toBe('307.69');
    expect(tag?.raw).toBe('307.6924');
    expect(tag?.fixed).toBe(false);
    expect(normalizeTpixTag(null)).toBeNull();
    expect(normalizeTpixTag({ amount: '0', raw: '0' })).toBeNull();
    expect(normalizeTpixTag('307')).toBeNull();
  });
});

describe('estimateTpixTotal', () => {
  it('แปลงยอดบาททั้งหมดด้วยราคาปัจจุบัน', () => {
    expect(estimateTpixTotal(325, 3.25)).toBeCloseTo(100, 6);
  });

  it('รายการราคาเหรียญคงที่ใช้ราคาคงที่ × จำนวน ส่วนที่เหลือแปลงจากบาท', () => {
    const total = estimateTpixTotal(1000, 2, [
      { tpix: { amount: '50', raw: '50', fixed: true }, quantity: 2, thb: 400 },
      { tpix: { amount: '10', raw: '10', fixed: false, rate_thb: '2' }, quantity: 1, thb: 20 },
    ]);
    // ราคาคงที่ 50 × 2 = 100 · ที่เหลือ (1000 − 400) ÷ 2 = 300
    expect(total).toBeCloseTo(400, 6);
  });

  it('ไม่รู้ราคา หรือยอด ≤ 0 = null', () => {
    expect(estimateTpixTotal(100, null)).toBeNull();
    expect(estimateTpixTotal(0, 3.25)).toBeNull();
  });
});

describe('formatTpix', () => {
  it('≥ 100 แสดง 2 ตำแหน่ง · น้อยกว่านั้น 4 ตำแหน่ง', () => {
    expect(formatTpix(307.69241)).toBe('307.69');
    expect(formatTpix(12.5)).toBe('12.5000');
    expect(formatTpix(1234.5)).toBe('1,234.50');
  });
});

describe('normalizeTpixPayment', () => {
  it('ใบที่ถูกต้อง → คัดลอกยอดตรงตัว (ไม่มีจุลภาค)', () => {
    const p = normalizeTpixPayment(rawPayment({ amount_tpix: '1,307.69241234', amount_tpix_exact: '1307.69241234' }));
    expect(p?.reference).toBe('TPXABCDEFGHJKLMNPQRSTUVW');
    expect(p?.amount_tpix_exact).toBe('1307.69241234');
    expect(p?.order_id).toBe(42);
    expect(p?.pay_url).toContain('main.thaiprompt.online');
  });

  it('รหัสอ้างอิง/กระเป๋ารับผิดรูปแบบ = null', () => {
    expect(normalizeTpixPayment(rawPayment({ reference: 'BAD' }))).toBeNull();
    expect(normalizeTpixPayment(rawPayment({ receive_address: '0x123' }))).toBeNull();
    expect(normalizeTpixPayment(null)).toBeNull();
  });

  it('ลิงก์ ethereum: ที่ชี้ไปกระเป๋าอื่น / หน้าจ่ายนอกเว็บเรา = ไม่ใช้', () => {
    const p = normalizeTpixPayment(
      rawPayment({
        eip681: 'ethereum:0x2222222222222222222222222222222222222222@4289?value=1',
        pay_url: 'https://evil.example/pay',
      })
    );
    expect(p?.eip681).toBeNull();
    expect(p?.pay_url).toBeNull();
  });
});

describe('isValidTxHash / isTpixReference', () => {
  it('เลขธุรกรรม = 0x + 64 hex', () => {
    expect(isValidTxHash(`0x${'a'.repeat(64)}`)).toBe(true);
    expect(isValidTxHash(`0x${'a'.repeat(63)}`)).toBe(false);
    expect(isValidTxHash(`${'a'.repeat(66)}`)).toBe(false);
    expect(isValidTxHash(`0x${'g'.repeat(64)}`)).toBe(false);
  });

  it('รหัสอ้างอิง = TPX + 21 ตัว', () => {
    expect(isTpixReference('TPXABCDEFGHJKLMNPQRSTUVW')).toBe(true);
    expect(isTpixReference('TPX123')).toBe(false);
    expect(isTpixReference('../../etc')).toBe(false);
  });
});
