/**
 * ตัวตัดสินพิกัด (services/locationFix.ts) — กันส่ง "ตำแหน่งเก่าของเครื่อง" เป็นตำแหน่งปัจจุบัน
 * และให้ด่านรัศมีจุดส่งของแอปตรงกับเซิร์ฟเวอร์
 *
 * รัน: npx jest services/__tests__/locationFix.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import {
  BROWSE_FIX_MAX_AGE_MS,
  chooseHandoverFix,
  FRESH_FIX_MAX_AGE_MS,
  fixAgeMs,
  isCoarseFix,
  pickFix,
  RECENT_FIX_MAX_AGE_MS,
  withinGeofence,
  type RawFix,
} from '../locationFix';

const NOW = 1_800_000_000_000;

const fix = (latitude: number, longitude: number, ageMs: number | null, accuracy: number | null = 12): RawFix => ({
  coords: { latitude, longitude, accuracy },
  timestamp: ageMs === null ? undefined : NOW - ageMs,
});

describe('pickFix — พิกัดที่ยอมใช้', () => {
  it('วัดใหม่ได้ → ใช้ค่าที่วัดใหม่ ไม่ปัดทศนิยม', () => {
    const picked = pickFix(fix(13.7291234, 100.5210987, 800), fix(1, 1, 0), NOW, FRESH_FIX_MAX_AGE_MS);
    expect(picked).toEqual({
      latitude: 13.7291234,
      longitude: 100.5210987,
      accuracy: 12,
      ageMs: 800,
      fromCache: false,
    });
  });

  it('วัดใหม่ไม่ได้ + ตำแหน่งล่าสุดยังสด → ใช้ตำแหน่งล่าสุด และบอกว่ามาจาก cache', () => {
    const picked = pickFix(null, fix(13.74, 100.53, 20_000), NOW, FRESH_FIX_MAX_AGE_MS);
    expect(picked).toMatchObject({ latitude: 13.74, longitude: 100.53, ageMs: 20_000, fromCache: true });
  });

  it('ตำแหน่งล่าสุดเก่าเกินกำหนด → null (ห้ามส่งพิกัดเก่าเป็นพิกัดตอนนี้)', () => {
    // 3 นาทีก่อน: ไรเดอร์ยังอยู่บนถนน ตอนนี้ยืนหน้าห้องลูกค้าแล้ว
    expect(pickFix(null, fix(13.74, 100.53, 180_000), NOW, FRESH_FIX_MAX_AGE_MS)).toBeNull();
    expect(pickFix(null, fix(13.74, 100.53, 180_000), NOW, RECENT_FIX_MAX_AGE_MS)).toBeNull();
    // งานดูรายการ/ค้นหาใกล้ตัว ยังใช้ได้ตามเดิม (5 นาที)
    expect(pickFix(null, fix(13.74, 100.53, 180_000), NOW, BROWSE_FIX_MAX_AGE_MS)).not.toBeNull();
  });

  it('ขอบเขตอายุ: เท่ากำหนดพอดีใช้ได้ เกิน 1 มิลลิวินาทีใช้ไม่ได้', () => {
    expect(pickFix(null, fix(13.74, 100.53, FRESH_FIX_MAX_AGE_MS), NOW, FRESH_FIX_MAX_AGE_MS)).not.toBeNull();
    expect(pickFix(null, fix(13.74, 100.53, FRESH_FIX_MAX_AGE_MS + 1), NOW, FRESH_FIX_MAX_AGE_MS)).toBeNull();
  });

  it('ตำแหน่งล่าสุดที่ไม่รู้เวลาที่วัด ถือว่าเก่า', () => {
    expect(pickFix(null, fix(13.74, 100.53, null), NOW, BROWSE_FIX_MAX_AGE_MS)).toBeNull();
  });

  it('พิกัด 0,0 / นอกช่วง / ไม่ใช่ตัวเลข ใช้ไม่ได้ทั้งค่าที่วัดใหม่และค่าล่าสุด', () => {
    expect(pickFix(fix(0, 0, 0), null, NOW, FRESH_FIX_MAX_AGE_MS)).toBeNull();
    expect(pickFix(fix(91, 100, 0), null, NOW, FRESH_FIX_MAX_AGE_MS)).toBeNull();
    expect(pickFix(fix(13, 181, 0), null, NOW, FRESH_FIX_MAX_AGE_MS)).toBeNull();
    expect(pickFix(fix(Number.NaN, 100, 0), null, NOW, FRESH_FIX_MAX_AGE_MS)).toBeNull();
    expect(pickFix(null, fix(0, 0, 0), NOW, FRESH_FIX_MAX_AGE_MS)).toBeNull();
    expect(pickFix(null, null, NOW, FRESH_FIX_MAX_AGE_MS)).toBeNull();
  });

  it('ค่าที่วัดใหม่เสีย → ถอยไปใช้ตำแหน่งล่าสุดที่ยังสด', () => {
    const picked = pickFix(fix(0, 0, 0), fix(13.74, 100.53, 5_000), NOW, FRESH_FIX_MAX_AGE_MS);
    expect(picked).toMatchObject({ latitude: 13.74, longitude: 100.53, fromCache: true });
  });

  it('accuracy ติดลบ/ไม่มี = ไม่ทราบ · เวลาเครื่องเพี้ยนจนอายุติดลบ = 0', () => {
    expect(pickFix(fix(13.74, 100.53, 0, -1), null, NOW, FRESH_FIX_MAX_AGE_MS)?.accuracy).toBeUndefined();
    expect(pickFix(fix(13.74, 100.53, 0, null), null, NOW, FRESH_FIX_MAX_AGE_MS)?.accuracy).toBeUndefined();
    expect(fixAgeMs(fix(13.74, 100.53, -5_000), NOW)).toBe(0);
  });
});

describe('isCoarseFix — GPS หยาบเกินจะปักหมุดโดยไม่เตือน', () => {
  it('เกิน 100 ม. = หยาบ · ไม่ทราบ = ไม่เตือน', () => {
    expect(isCoarseFix(100)).toBe(false);
    expect(isCoarseFix(100.5)).toBe(true);
    expect(isCoarseFix(1500)).toBe(true);
    expect(isCoarseFix(undefined)).toBe(false);
    expect(isCoarseFix(null)).toBe(false);
  });
});

describe('withinGeofence — กติกาเดียวกับเซิร์ฟเวอร์ (ห่าง > รัศมี = ไม่ผ่าน ไม่มีส่วนเผื่อ)', () => {
  it('ตัดที่รัศมีพอดี', () => {
    expect(withinGeofence(150, 150)).toBe(true);
    expect(withinGeofence(151, 150)).toBe(false);
    // เดิมแอปเผื่อให้อีก ≥30 ม. → 170 ม. ถ่ายรูปได้แล้วเซิร์ฟเวอร์ปฏิเสธ
    expect(withinGeofence(170, 150)).toBe(false);
  });

  it('งานไม่มีพิกัดจุดส่ง (ระยะ = null) → ไม่ขวาง ให้เซิร์ฟเวอร์ตัดสิน', () => {
    expect(withinGeofence(null, 150)).toBe(true);
    expect(withinGeofence(undefined, 150)).toBe(true);
  });
});

describe('chooseHandoverFix — พิกัดที่แนบกับรูปส่งมอบ', () => {
  const before = { id: 'before', distanceM: 120 };

  it('พิกัดหลังถ่ายยังอยู่ในรัศมี → ใช้ค่าหลังถ่าย', () => {
    expect(chooseHandoverFix(before, { id: 'after', distanceM: 90 }, 150).id).toBe('after');
    expect(chooseHandoverFix(before, { id: 'after', distanceM: 150 }, 150).id).toBe('after');
  });

  it('GPS แกว่งออกนอกรัศมีตอนยืนถ่าย → ใช้ค่าก่อนถ่ายที่ตรวจแล้ว (รูปไม่ถูกทิ้ง)', () => {
    expect(chooseHandoverFix(before, { id: 'after', distanceM: 190 }, 150).id).toBe('before');
  });

  it('หาพิกัดหลังถ่ายไม่ได้ → ใช้ค่าก่อนถ่าย', () => {
    expect(chooseHandoverFix(before, null, 150).id).toBe('before');
  });
});
