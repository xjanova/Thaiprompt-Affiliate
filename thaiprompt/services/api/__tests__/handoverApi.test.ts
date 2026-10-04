/**
 * ทดสอบการแปลงข้อมูลส่งมอบของ (handover) + ตัวช่วยหัวใจ/ล็อกเรียก ตามสัญญาไรเดอร์รอบ 2
 *
 * รัน: npx jest services/api/__tests__/handoverApi.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import {
  isHandoverFinal,
  isHandoverJobEnded,
  isHandoverSuccess,
  normalizeBuyerHandoverData,
  normalizePersonCard,
  normalizeRiderHandoverData,
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

describe('สัญญารอบแก้ไข §A2–§A4', () => {
  it('server_now → clock_offset_ms (หักครึ่งเวลาเดินทางไป-กลับ) · ไม่มี server_now = null', () => {
    const serverMs = Date.parse('2026-10-04T12:00:00.000Z');
    const d = normalizeBuyerHandoverData(
      { ...API, server_now: '2026-10-04T12:00:00.000Z' },
      { sentAt: serverMs - 61_000, receivedAt: serverMs - 59_000 }
    );
    expect(d.server_now).toBe('2026-10-04T12:00:00.000Z');
    expect(d.clock_offset_ms).toBe(60_000);
    expect(normalizeBuyerHandoverData(API).clock_offset_ms).toBeNull();
  });

  it('ผู้ซื้อ: can_confirm_received', () => {
    expect(normalizeBuyerHandoverData(API).handover.can_confirm_received).toBe(false);
    const d = normalizeBuyerHandoverData({
      ...API,
      handover: { ...API.handover, status: 'fallback_pending_release', can_confirm_received: true },
    });
    expect(d.handover.can_confirm_received).toBe(true);
  });

  it('ไรเดอร์: รหัส 6 หลักของตัวเอง + รูปลูกค้าผ่าน allowlist เดียวกับฝั่งผู้ซื้อ', () => {
    const d = normalizeRiderHandoverData({
      handover: { required: true, status: 'waiting', code: '039217', can_waited_photo: '1', geofence_m: 150, wait_seconds: 180 },
      buyer: { id: 5, display_name: 'นิด ส.', photo_url: 'https://evil.example/face.jpg' },
      server_now: '2026-10-04T12:00:00+07:00',
    });
    expect(d.handover.code).toBe('039217');
    expect(d.handover.can_waited_photo).toBe(true);
    expect(d.buyer?.photo_url).toBeNull();
    expect(d.server_now).toBe('2026-10-04T12:00:00+07:00');
    expect(typeof d.clock_offset_ms).toBe('number');
    expect(normalizeRiderHandoverData({ handover: { code: '12ab56' } }).handover.code).toBeNull();
  });
});

describe('isHandoverJobEnded (B6: งานไรเดอร์จบแต่การรับของยังไม่จบ)', () => {
  const job = (status: string) => ({ id: 1, status, distance_to_dropoff_m: null });

  it('ไรเดอร์ส่งไม่สำเร็จ / งานถูกยกเลิก ระหว่างรอรับของ = จบ (หยุดถามซ้ำ)', () => {
    expect(isHandoverJobEnded({ handover: { status: 'waiting' }, job: job('failed') })).toBe(true);
    expect(isHandoverJobEnded({ handover: { status: 'rider_confirmed' }, job: job('cancelled') })).toBe(true);
    expect(isHandoverJobEnded({ handover: { status: 'not_required' }, job: job('cancelled') })).toBe(true);
  });

  it('การรับของจบแล้ว (ปลด/คืนเงิน) → ใช้หน้าจบงานตามเดิม ไม่ใช่หน้านี้', () => {
    expect(isHandoverJobEnded({ handover: { status: 'refunded' }, job: job('cancelled') })).toBe(false);
    expect(isHandoverJobEnded({ handover: { status: 'released' }, job: job('failed') })).toBe(false);
  });

  it('ผู้ซื้อร้องเรียนค้างอยู่ → ยังแสดงการ์ดร้องเรียน (ทีมงานยังไม่ตัดสิน)', () => {
    expect(isHandoverJobEnded({ handover: { status: 'disputed' }, job: job('failed') })).toBe(false);
  });

  it('งานยังเดินอยู่ / ไม่มีข้อมูล = ไม่จบ', () => {
    expect(isHandoverJobEnded({ handover: { status: 'waiting' }, job: job('delivering') })).toBe(false);
    expect(isHandoverJobEnded({ handover: { status: 'fallback_pending_release' }, job: job('awaiting_release') })).toBe(false);
    expect(isHandoverJobEnded({ handover: { status: 'waiting' }, job: null })).toBe(false);
    expect(isHandoverJobEnded(null)).toBe(false);
  });
});
