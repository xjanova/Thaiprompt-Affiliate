/**
 * ทดสอบตัวตั้งเวลาส่งรูปรอบ 2 ซ้ำอัตโนมัติ (ไรเดอร์รอบ 2 — B1: ห้ามยิงวนถี่เมื่อเน็ตหลุด/5xx/429)
 *
 * รัน: npx jest components/rider/__tests__/handoverRetry.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import {
  AUTO_RETRY_CAP_MS,
  AUTO_RETRY_MAX,
  AUTO_RETRY_RATE_LIMIT_MS,
  AUTO_RETRY_SLACK_MS,
  INITIAL_AUTO_RETRY,
  autoRetryDelayMs,
  autoRetryExhausted,
  autoRetryReasonText,
  classifyUploadFailure,
  isTransportFailure,
  nextAutoRetryAt,
  recordAutoRetryFailure,
  type AutoRetryFailure,
  type AutoRetryState,
} from '../handoverRetry';

const NOW = Date.parse('2026-10-04T12:00:00.000Z');

describe('classifyUploadFailure', () => {
  it('เน็ตหลุด (status 0) = network', () => {
    expect(classifyUploadFailure({ status: 0, code: 'NETWORK_ERROR' }, 'waited')).toBe('network');
    expect(classifyUploadFailure({ status: 0, code: 'TIMEOUT' }, 'arrival')).toBe('network');
  });

  it('5xx = server', () => {
    expect(classifyUploadFailure({ status: 500, code: 'SERVER_ERROR' }, 'waited')).toBe('server');
    expect(classifyUploadFailure({ status: 503, code: 'SERVER_ERROR' }, 'waited')).toBe('server');
  });

  it('429 / TOO_MANY_REQUESTS = rate_limited', () => {
    expect(classifyUploadFailure({ status: 429, code: 'TOO_MANY_REQUESTS' }, 'waited')).toBe('rate_limited');
    expect(classifyUploadFailure({ status: 429, code: 'SOMETHING' }, 'waited')).toBe('rate_limited');
  });

  it('WAIT_NOT_OVER ลองซ้ำได้เฉพาะรูปรอบ 2', () => {
    expect(classifyUploadFailure({ status: 409, code: 'WAIT_NOT_OVER' }, 'waited')).toBe('wait_not_over');
    expect(classifyUploadFailure({ status: 409, code: 'WAIT_NOT_OVER' }, 'arrival')).toBeNull();
  });

  it('error ที่แปลว่ารูปใช้ไม่ได้แล้ว = null (ห้ามลองซ้ำ)', () => {
    expect(classifyUploadFailure({ status: 422, code: 'TOO_FAR_FROM_DROPOFF' }, 'waited')).toBeNull();
    expect(classifyUploadFailure({ status: 409, code: 'HANDOVER_FINAL' }, 'waited')).toBeNull();
    expect(classifyUploadFailure({ status: 403, code: 'NOT_YOUR_JOB' }, 'waited')).toBeNull();
  });

  it('isTransportFailure แยกปัญหาเน็ต/เซิร์ฟเวอร์ออกจาก "ยังไม่ครบเวลา"', () => {
    expect(isTransportFailure('network')).toBe(true);
    expect(isTransportFailure('server')).toBe(true);
    expect(isTransportFailure('rate_limited')).toBe(true);
    expect(isTransportFailure('wait_not_over')).toBe(false);
    expect(isTransportFailure(null)).toBe(false);
  });
});

describe('autoRetryDelayMs', () => {
  it('ทวีคูณ 5 วิ → 15 วิ → 45 วิ → 135 วิ แล้วติดเพดาน 5 นาที', () => {
    expect(autoRetryDelayMs(1, 'network')).toBe(5_000);
    expect(autoRetryDelayMs(2, 'network')).toBe(15_000);
    expect(autoRetryDelayMs(3, 'server')).toBe(45_000);
    expect(autoRetryDelayMs(4, 'server')).toBe(135_000);
    expect(autoRetryDelayMs(5, 'network')).toBe(AUTO_RETRY_CAP_MS);
    expect(autoRetryDelayMs(50, 'network')).toBe(AUTO_RETRY_CAP_MS);
  });

  it('429 พักอย่างน้อย 60 วิ', () => {
    expect(autoRetryDelayMs(1, 'rate_limited')).toBe(AUTO_RETRY_RATE_LIMIT_MS);
    expect(autoRetryDelayMs(3, 'rate_limited')).toBe(AUTO_RETRY_RATE_LIMIT_MS);
    expect(autoRetryDelayMs(4, 'rate_limited')).toBe(135_000);
  });

  it('ค่าแปลก (0 / ติดลบ / NaN) ไม่ทำให้พัก 0 วินาที', () => {
    expect(autoRetryDelayMs(0, 'network')).toBe(5_000);
    expect(autoRetryDelayMs(-3, 'network')).toBe(5_000);
    expect(autoRetryDelayMs(Number.NaN, 'network')).toBe(5_000);
  });
});

describe('recordAutoRetryFailure', () => {
  it('นับครั้ง + เลื่อนเวลา + จำสาเหตุ (ไม่แก้ state เดิม)', () => {
    const next = recordAutoRetryFailure(INITIAL_AUTO_RETRY, 'network', NOW);
    expect(next).toEqual({ attempts: 1, notBefore: NOW + 5_000, lastFailure: 'network' });
    expect(INITIAL_AUTO_RETRY).toEqual({ attempts: 0, notBefore: 0, lastFailure: null });
  });
});

describe('nextAutoRetryAt', () => {
  it('ยังไม่ครบเวลาของ server → รอถึงเวลาครบ + เผื่อเวลาเดินทาง', () => {
    const state = recordAutoRetryFailure(INITIAL_AUTO_RETRY, 'wait_not_over', NOW);
    const due = NOW + 60_000;
    expect(nextAutoRetryAt(state, due)).toBe(due + AUTO_RETRY_SLACK_MS);
  });

  it('ครบเวลาของ server แล้ว → ใช้เวลาพักหลังล้ม (ไม่ยิงทันที)', () => {
    const state = recordAutoRetryFailure(INITIAL_AUTO_RETRY, 'server', NOW);
    expect(nextAutoRetryAt(state, NOW - 10_000)).toBe(NOW + 5_000);
  });

  it('ไม่รู้เวลาครบกำหนด → ใช้เวลาพัก', () => {
    const state = recordAutoRetryFailure(INITIAL_AUTO_RETRY, 'network', NOW);
    expect(nextAutoRetryAt(state, null)).toBe(NOW + 5_000);
    expect(nextAutoRetryAt(state, Number.NaN)).toBe(NOW + 5_000);
  });

  it('ล้มครบโควตา → null (หยุดส่งอัตโนมัติ ให้ไรเดอร์กดเอง)', () => {
    let state: AutoRetryState = INITIAL_AUTO_RETRY;
    for (let i = 0; i < AUTO_RETRY_MAX; i += 1) state = recordAutoRetryFailure(state, 'network', NOW);
    expect(autoRetryExhausted(state)).toBe(true);
    expect(nextAutoRetryAt(state, NOW)).toBeNull();
  });
});

describe('จำลองบั๊กเดิม: เน็ตหลุดทุกครั้งหลังครบเวลารอ', () => {
  /**
   * จำลองตัวจับเวลาของแผงส่งมอบ: ยิงเมื่อถึง nextAutoRetryAt แล้วล้มทุกครั้ง
   * เดิม (ไม่นับครั้ง/ไม่เลื่อนเวลา) = ยิงซ้ำทันทีไม่รู้จบ → ต้องหยุดเองภายในโควตาและเว้นช่วงห่างขึ้นเรื่อยๆ
   */
  const simulate = (failure: AutoRetryFailure, dueMs: number) => {
    let clock = NOW;
    // ครั้งแรก: server ตอบ WAIT_NOT_OVER (เวลาเครื่องเร็วกว่า server) → เข้าโหมดส่งอัตโนมัติ
    let state = recordAutoRetryFailure(INITIAL_AUTO_RETRY, 'wait_not_over', clock);
    const fired: number[] = [];
    for (let guard = 0; guard < 1000; guard += 1) {
      const at = nextAutoRetryAt(state, dueMs);
      if (at === null) break;
      // ตัวจับเวลาไม่ยิงย้อนหลัง
      clock = Math.max(clock, at);
      fired.push(clock);
      state = recordAutoRetryFailure(state, failure, clock);
    }
    return { fired, state };
  };

  it('เน็ตหลุด: ยิงไม่เกินโควตา เว้นช่วงห่างขึ้นทุกครั้ง แล้วหยุด', () => {
    const due = NOW + 30_000;
    const { fired, state } = simulate('network', due);
    expect(fired.length).toBe(AUTO_RETRY_MAX - 1);
    expect(fired[0]).toBe(due + AUTO_RETRY_SLACK_MS);
    const gaps = fired.slice(1).map((t, i) => t - fired[i]);
    // ห่างจากครั้งก่อนตามเวลาพักทวีคูณของครั้งที่ล้ม (2 = ครั้งที่ 2 นับรวม WAIT_NOT_OVER แรก)
    gaps.forEach((gap, i) => expect(gap).toBe(autoRetryDelayMs(i + 2, 'network')));
    gaps.forEach((gap) => expect(gap).toBeGreaterThanOrEqual(5_000));
    expect(autoRetryExhausted(state)).toBe(true);
    expect(state.lastFailure).toBe('network');
  });

  it('ถูกจำกัดความถี่ (429): เว้นอย่างน้อย 60 วิ ทุกครั้ง', () => {
    const { fired } = simulate('rate_limited', NOW);
    const gaps = fired.slice(1).map((t, i) => t - fired[i]);
    expect(gaps.length).toBeGreaterThan(0);
    gaps.forEach((gap) => expect(gap).toBeGreaterThanOrEqual(AUTO_RETRY_RATE_LIMIT_MS));
  });

  it('หลังล้มทุกครั้ง เวลาลองครั้งถัดไปอยู่ในอนาคตเสมอ (ไม่มีการยิงซ้ำทันที)', () => {
    const failures: AutoRetryFailure[] = ['network', 'server', 'rate_limited', 'wait_not_over'];
    failures.forEach((failure) => {
      let state: AutoRetryState = INITIAL_AUTO_RETRY;
      for (let i = 0; i < AUTO_RETRY_MAX - 1; i += 1) {
        state = recordAutoRetryFailure(state, failure, NOW);
        const at = nextAutoRetryAt(state, NOW - 60_000);
        expect(at).not.toBeNull();
        expect(at as number).toBeGreaterThan(NOW);
      }
    });
  });
});

describe('autoRetryReasonText', () => {
  it('ข้อความภาษาไทยทุกสาเหตุ', () => {
    (['network', 'server', 'rate_limited', 'wait_not_over'] as AutoRetryFailure[]).forEach((f) => {
      expect(autoRetryReasonText(f)).toMatch(/[฀-๿]/);
    });
    expect(autoRetryReasonText(null)).toBe('');
  });
});
