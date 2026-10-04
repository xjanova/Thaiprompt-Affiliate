/**
 * ทดสอบผลยืนยันตัวตนที่สรุปจากสถานะ/รหัส error + ตัวรอ server ตรวจคำขอก่อนหน้าให้เสร็จ
 *
 * รัน: npx jest services/ekyc/__tests__/outcome.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { normalizeEkycStatus, type EkycStatus } from '@/services/api/ekycApi';
import type { ApiResult } from '@/services/api/client';
import {
  decisionForCode,
  decisionFromStatus,
  isResultCode,
  isWaitCode,
  waitWhileProcessing,
} from '../outcome';

const ok = (raw: Record<string, unknown>): ApiResult<EkycStatus> => ({
  success: true,
  data: normalizeEkycStatus(raw),
  message: '',
  status: 200,
});

const fail = (code: string, status: number = 0): ApiResult<EkycStatus> => ({
  success: false,
  code,
  status,
  message: 'ผิดพลาด',
});

/** นาฬิกาปลอม: sleep เดินเวลาไปเท่าที่ขอ (ไม่รอจริง) */
const fakeClock = () => {
  let t = 0;
  const sleeps: number[] = [];
  return {
    now: () => t,
    sleep: async (ms: number) => {
      sleeps.push(ms);
      t += ms;
    },
    sleeps,
  };
};

describe('รหัส error', () => {
  it('รหัสที่พาไปหน้าผล / ต้องรอ', () => {
    expect(isResultCode('EKYC_PROCESSING')).toBe(true);
    expect(isResultCode('EKYC_SESSION_DONE')).toBe(true);
    expect(isResultCode('EKYC_ALREADY_VERIFIED')).toBe(true);
    expect(isResultCode('EKYC_PENDING_REVIEW')).toBe(true);
    expect(isResultCode('EKYC_TOO_MANY_ATTEMPTS')).toBe(true);
    expect(isResultCode('EKYC_SESSION_EXPIRED')).toBe(false);
    expect(isResultCode(undefined)).toBe(false);
    expect(isWaitCode('EKYC_PROCESSING')).toBe(true);
    expect(isWaitCode('EKYC_SESSION_DONE')).toBe(true);
    expect(isWaitCode('EKYC_PENDING_REVIEW')).toBe(false);
  });

  it('ยืนยันแล้ว = ผ่าน · รอเจ้าหน้าที่ = ส่งตรวจ · อื่นๆ = ไม่รู้ผล', () => {
    expect(decisionForCode('EKYC_ALREADY_VERIFIED')).toMatchObject({ decision: 'approved', kyc_status: 'approved', source: 'status' });
    expect(decisionForCode('EKYC_PENDING_REVIEW')).toMatchObject({ decision: 'review', kyc_status: 'pending', source: 'status' });
    expect(decisionForCode('EKYC_PROCESSING')).toBeNull();
    expect(decisionForCode(null)).toBeNull();
  });
});

describe('decisionFromStatus', () => {
  it('สรุปผลจากสถานะ พร้อมเหตุผล/ข้อความเจ้าหน้าที่', () => {
    expect(decisionFromStatus(normalizeEkycStatus({ verified: true }))?.decision).toBe('approved');
    const review = decisionFromStatus(
      normalizeEkycStatus({ kyc_status: 'pending', reasons: ['BORDERLINE_MATCH'], message: 'รอสักครู่', attempts_left: 1 })
    );
    expect(review).toMatchObject({ decision: 'review', message: 'รอสักครู่', attempts_left: 1, reasons: ['BORDERLINE_MATCH'] });
    expect(review?.reason_items[0]?.tone).toBe('warn');
    expect(decisionFromStatus(normalizeEkycStatus({ kyc_status: 'rejected' }))?.decision).toBe('rejected');
    expect(decisionFromStatus(normalizeEkycStatus({ kyc_status: 'none', last_decision: 'retake' }))?.decision).toBe('retake');
  });

  it('ยังไม่เคยมีผล / ยังตรวจอยู่ = null', () => {
    expect(decisionFromStatus(normalizeEkycStatus({ kyc_status: 'none' }))).toBeNull();
    expect(decisionFromStatus(normalizeEkycStatus({ kyc_status: 'pending', processing: true }))).toBeNull();
    expect(decisionFromStatus(null)).toBeNull();
  });
});

describe('waitWhileProcessing', () => {
  it('ถามทุก 3 วินาทีจน processing = false แล้วคืนผล', async () => {
    const clock = fakeClock();
    const answers = [
      ok({ kyc_status: 'none', processing: true }),
      ok({ kyc_status: 'none', processing: true }),
      ok({ kyc_status: 'pending', processing: false, reasons: ['LOW_MATCH'] }),
    ];
    let calls = 0;
    const outcome = await waitWhileProcessing({
      fetchStatus: async () => answers[calls++],
      sleep: clock.sleep,
      now: clock.now,
      isCancelled: () => false,
    });
    expect(calls).toBe(3);
    expect(clock.sleeps).toEqual([3000, 3000]);
    expect(outcome.kind).toBe('done');
    if (outcome.kind === 'done') {
      expect(outcome.status?.kyc_status).toBe('pending');
      expect(outcome.decision?.decision).toBe('review');
    }
  });

  it('เน็ตหลุดระหว่างถาม = ถามต่อ (ไม่ถือว่าจบ)', async () => {
    const clock = fakeClock();
    const answers = [fail('NETWORK_ERROR'), fail('TIMEOUT'), ok({ verified: true })];
    let calls = 0;
    const outcome = await waitWhileProcessing({
      fetchStatus: async () => answers[calls++],
      sleep: clock.sleep,
      now: clock.now,
      isCancelled: () => false,
    });
    expect(calls).toBe(3);
    expect(outcome.kind === 'done' && outcome.decision?.decision).toBe('approved');
  });

  it('ได้ EKYC_ALREADY_VERIFIED = จบทันทีเป็นผ่าน', async () => {
    const clock = fakeClock();
    const outcome = await waitWhileProcessing({
      fetchStatus: async () => fail('EKYC_ALREADY_VERIFIED', 409),
      sleep: clock.sleep,
      now: clock.now,
      isCancelled: () => false,
    });
    expect(outcome).toMatchObject({ kind: 'done', status: null });
    expect(outcome.kind === 'done' && outcome.decision?.decision).toBe('approved');
  });

  it('ครบ 60 วินาทียังตรวจไม่เสร็จ = timeout (คืนสถานะล่าสุด)', async () => {
    const clock = fakeClock();
    let calls = 0;
    const outcome = await waitWhileProcessing({
      fetchStatus: async () => {
        calls += 1;
        return ok({ kyc_status: 'none', processing: true });
      },
      sleep: clock.sleep,
      now: clock.now,
      isCancelled: () => false,
    });
    expect(outcome.kind).toBe('timeout');
    if (outcome.kind === 'timeout') expect(outcome.status?.processing).toBe(true);
    // ถาม ณ 0, 3, …, 60 วินาที แล้วหยุด
    expect(calls).toBe(21);
    expect(clock.now()).toBeLessThanOrEqual(60_000);
  });

  it('ออกจากหน้าระหว่างรอ = cancelled (ไม่ถามต่อ)', async () => {
    const clock = fakeClock();
    let cancelled = false;
    let calls = 0;
    const outcome = await waitWhileProcessing({
      fetchStatus: async () => {
        calls += 1;
        if (calls === 2) cancelled = true;
        return ok({ processing: true });
      },
      sleep: clock.sleep,
      now: clock.now,
      isCancelled: () => cancelled,
    });
    expect(outcome.kind).toBe('cancelled');
    expect(calls).toBe(2);
  });
});
