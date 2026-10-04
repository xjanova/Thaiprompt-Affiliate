/**
 * ทดสอบการแปลงข้อมูลส่งมอบของ (handover) + ตัวช่วยหัวใจ/ล็อกเรียก ตามสัญญาไรเดอร์รอบ 2
 *
 * รัน: npx jest services/api/__tests__/handoverApi.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import {
  isHandoverFinal,
  isHandoverSuccess,
  normalizeBuyerHandoverData,
  normalizePersonCard,
  sanitizeScannedToken,
} from '../handoverApi';
import { heartsToLock } from '../riderSocialApi';

const API = {
  handover: {
    required: true,
    status: 'rider_confirmed',
    method: null,
    rider_confirmed: true,
    buyer_confirmed: false,
    qr_token: 'hv1.abcDEF123.sig',
    qr_expires_at: '2026-10-04T12:45:00+07:00',
    code: '482916',
    wait_until: null,
    auto_release_at: null,
    arrival_photo_at: null,
    waited_photo_at: null,
    can_dispute: true,
    disputed_at: null,
    dispute_reason: null,
    completed_at: null,
  },
  rider: {
    id: 12,
    display_name: 'สมชาย จ.',
    photo_url: 'https://main.thaiprompt.online/media/profile-photo/12?signature=x',
    plate_masked: 'กข 7•••',
    hearts_total: 312,
    hearts_from_me: 14,
    can_lock: true,
  },
  job: { id: 99, status: 'delivering', distance_to_dropoff_m: 12 },
  settlement: null,
};

describe('normalizeBuyerHandoverData', () => {
  it('แปลงตามสัญญา', () => {
    const d = normalizeBuyerHandoverData(API);
    expect(d.handover.required).toBe(true);
    expect(d.handover.code).toBe('482916');
    expect(d.handover.rider_confirmed).toBe(true);
    expect(d.rider?.display_name).toBe('สมชาย จ.');
    expect(d.rider?.can_lock).toBe(true);
    expect(d.job?.distance_to_dropoff_m).toBe(12);
    expect(d.settlement).toBeNull();
  });

  it('รหัสที่ไม่ใช่ตัวเลข 6 หลักไม่ขึ้นจอ', () => {
    const d = normalizeBuyerHandoverData({ ...API, handover: { ...API.handover, code: '<b>1</b>' } });
    expect(d.handover.code).toBeNull();
  });

  it('รูปที่ไม่ใช่ https / path ของเว็บเรา ถูกตัดทิ้ง', () => {
    expect(normalizePersonCard({ id: 1, display_name: 'ก', photo_url: 'javascript:alert(1)' })?.photo_url).toBeNull();
    expect(normalizePersonCard({ id: 1, display_name: 'ก', photo_url: 'https://evil.example/x.jpg' })?.photo_url).toBeNull();
    expect(normalizePersonCard({ id: 1, display_name: 'ก', photo_url: '/media/p/1.jpg' })?.photo_url).toBe(
      'https://main.thaiprompt.online/media/p/1.jpg'
    );
    expect(normalizePersonCard({ id: 0 })).toBeNull();
  });

  it('สถานะจบงาน', () => {
    expect(isHandoverFinal({ status: 'completed' })).toBe(true);
    expect(isHandoverFinal({ status: 'released' })).toBe(true);
    expect(isHandoverFinal({ status: 'refunded' })).toBe(true);
    expect(isHandoverFinal({ status: 'fallback_pending_release' })).toBe(false);
    expect(isHandoverSuccess({ status: 'refunded' })).toBe(false);
  });

  it('การแบ่งเงิน: ตัดบรรทัดที่ไม่มีชื่อ + ตัวเลขเป็น number', () => {
    const d = normalizeBuyerHandoverData({
      ...API,
      settlement: {
        total_paid: '158.00',
        seller_amount: 104,
        rider_amount: 48,
        referrer_amount: 1.2,
        platform_amount: 4.8,
        lines: [
          { key: 'seller', label: 'ร้านครัวไทยพร้อม', amount: '104.00', note: 'ค่าสินค้า 120 − GP 5% − โบนัสไรเดอร์ 10' },
          { key: 'bad', label: '', amount: 1 },
        ],
      },
    });
    expect(d.settlement?.total_paid).toBe(158);
    expect(d.settlement?.lines).toHaveLength(1);
    expect(d.settlement?.lines[0].amount).toBe(104);
  });
});

describe('sanitizeScannedToken', () => {
  it('รับ token ปกติ · ปฏิเสธค่าว่าง/สั้น/ยาวเกิน/มีช่องว่าง', () => {
    expect(sanitizeScannedToken('  hv1.abcDEF123.sig ')).toBe('hv1.abcDEF123.sig');
    expect(sanitizeScannedToken('')).toBeNull();
    expect(sanitizeScannedToken('abc')).toBeNull();
    expect(sanitizeScannedToken('a'.repeat(2000))).toBeNull();
    expect(sanitizeScannedToken('hv1 abc def ghi')).toBeNull();
    expect(sanitizeScannedToken(42)).toBeNull();
  });
});

describe('heartsToLock', () => {
  it('จำนวนหัวใจที่ยังขาด', () => {
    expect(heartsToLock(8, 11)).toBe(3);
    expect(heartsToLock(11, 11)).toBe(0);
    expect(heartsToLock(14, 11)).toBe(0);
    expect(heartsToLock(null, 11)).toBe(11);
  });
});
