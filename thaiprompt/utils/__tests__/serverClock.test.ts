/**
 * ทดสอบตัวแก้นาฬิกาเครื่องเพี้ยน (server_now → offset) — ไรเดอร์รอบ 2 §A2
 *
 * รัน: npx jest utils/__tests__/serverClock.test.ts
 */

import { afterEach, describe, expect, it } from '@jest/globals';
import {
  MAX_CLOCK_OFFSET_MS,
  clockOffsetFrom,
  deviceTimeFor,
  lastClockOffset,
  msUntil,
  parseIsoMs,
  rememberClockOffset,
  resetClockOffsetForTest,
  serverNowMs,
} from '../serverClock';

const SERVER_NOW = '2026-10-04T12:00:00.000Z';
const SERVER_MS = Date.parse(SERVER_NOW);

afterEach(() => resetClockOffsetForTest());

describe('clockOffsetFrom', () => {
  it('เครื่องช้ากว่า server 90 วินาที → offset +90000', () => {
    const device = SERVER_MS - 90_000;
    expect(clockOffsetFrom(SERVER_NOW, device, device)).toBe(90_000);
  });

  it('เครื่องเร็วกว่า server 2 นาที → offset ติดลบ', () => {
    const device = SERVER_MS + 120_000;
    expect(clockOffsetFrom(SERVER_NOW, device, device)).toBe(-120_000);
  });

  it('หักครึ่งหนึ่งของเวลาเดินทางไป-กลับ (server ตอบตอนกลางทาง)', () => {
    // ส่งตอน server−1000 ได้คำตอบตอน server+1000 (เครื่องตรงกับ server) → offset 0
    expect(clockOffsetFrom(SERVER_NOW, SERVER_MS - 1000, SERVER_MS + 1000)).toBe(0);
  });

  it('ไม่รู้เวลาส่ง → ใช้เวลาได้คำตอบ', () => {
    expect(clockOffsetFrom(SERVER_NOW, null, SERVER_MS - 5000)).toBe(5000);
  });

  it('ค่าใช้ไม่ได้ → null', () => {
    expect(clockOffsetFrom(null, 0, SERVER_MS)).toBeNull();
    expect(clockOffsetFrom('ไม่ใช่วันที่', 0, SERVER_MS)).toBeNull();
    expect(clockOffsetFrom(12345, 0, SERVER_MS)).toBeNull();
    // ห่างเกิน 2 วัน = ข้อมูลผิดปกติ
    expect(clockOffsetFrom(SERVER_NOW, null, SERVER_MS - MAX_CLOCK_OFFSET_MS - 1)).toBeNull();
  });
});

describe('เวลาที่แก้แล้ว', () => {
  it('serverNowMs บวก offset · offset ไม่รู้ = เวลาเครื่อง', () => {
    expect(serverNowMs(5000, 1_000_000)).toBe(1_005_000);
    expect(serverNowMs(null, 1_000_000)).toBe(1_000_000);
    expect(serverNowMs(Number.NaN, 1_000_000)).toBe(1_000_000);
  });

  it('msUntil นับตามเวลา server ไม่ใช่เวลาเครื่อง', () => {
    const waitUntil = new Date(SERVER_MS + 60_000).toISOString();
    // เครื่องช้ากว่า server 90 วิ: ถ้าไม่แก้จะคิดว่าเหลือ 150 วิ — ที่ถูกคือ 60 วิ
    const device = SERVER_MS - 90_000;
    expect(msUntil(waitUntil, 90_000, device)).toBe(60_000);
    expect(msUntil(waitUntil, null, device)).toBe(150_000);
    expect(msUntil(null, 0, device)).toBeNull();
  });

  it('deviceTimeFor แปลงเวลา server เป็นเวลาเครื่อง (ใช้ตั้งเวลาลองส่งรูปซ้ำ)', () => {
    expect(deviceTimeFor(SERVER_NOW, 90_000)).toBe(SERVER_MS - 90_000);
    expect(deviceTimeFor(SERVER_NOW, null)).toBe(SERVER_MS);
    expect(deviceTimeFor('x', 0)).toBeNull();
  });

  it('parseIsoMs รับเฉพาะสตริงวันที่', () => {
    expect(parseIsoMs(SERVER_NOW)).toBe(SERVER_MS);
    expect(parseIsoMs('')).toBeNull();
    expect(parseIsoMs(undefined)).toBeNull();
  });
});

describe('offset ล่าสุดที่จำไว้', () => {
  it('จำค่าที่ใช้ได้ · ไม่ทับด้วย null / ค่าแปลก', () => {
    expect(lastClockOffset()).toBe(0);
    rememberClockOffset(1500);
    expect(lastClockOffset()).toBe(1500);
    rememberClockOffset(null);
    rememberClockOffset(Number.POSITIVE_INFINITY);
    rememberClockOffset(MAX_CLOCK_OFFSET_MS * 10);
    expect(lastClockOffset()).toBe(1500);
  });
});
