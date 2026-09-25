/**
 * ทดสอบตัวช่วยโหมดคนขายตลาดสด (ลำดับปุ่ม / เวลาแบบไทย / เวลาปิดร้าน)
 *
 * รัน: npx jest components/merchant/__tests__/fmHelpers.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { fmActionLook, isFmOrderFilter, resolveClosingTime, sortFmActions, timeAgoTh, toHHMM } from '../fmHelpers';

describe('sortFmActions', () => {
  it('เรียงปุ่มหลักก่อน ยกเลิกท้ายสุด และทิ้ง action ที่แอปไม่รู้จัก', () => {
    expect(sortFmActions(['cancel', 'prepare', 'ready', 'confirm'])).toEqual(['prepare', 'ready', 'cancel']);
    expect(sortFmActions(['cancel', 'accept'])).toEqual(['accept', 'cancel']);
    expect(sortFmActions([])).toEqual([]);
  });
});

describe('fmActionLook', () => {
  it('ข้อความ "พร้อม" ต่างกันตามวิธีส่ง', () => {
    expect(fmActionLook('ready', 'rider').label).toContain('ไรเดอร์');
    expect(fmActionLook('ready', 'pickup').label).toContain('มารับ');
  });
});

describe('isFmOrderFilter', () => {
  it('รับเฉพาะแท็บที่มีจริง', () => {
    expect(isFmOrderFilter('pending')).toBe(true);
    expect(isFmOrderFilter('all')).toBe(true);
    expect(isFmOrderFilter('shipping')).toBe(false);
    expect(isFmOrderFilter(undefined)).toBe(false);
  });
});

describe('timeAgoTh', () => {
  const now = new Date('2026-09-25T12:00:00Z').getTime();
  it('ช่วงเวลาสั้นเป็นภาษาไทย', () => {
    expect(timeAgoTh(now - 10_000, now)).toBe('เมื่อสักครู่');
    expect(timeAgoTh(now - 5 * 60_000, now)).toBe('5 นาทีที่แล้ว');
    expect(timeAgoTh(new Date(now - 2 * 3600_000).toISOString(), now)).toBe('2 ชม.ที่แล้ว');
  });
  it('ค่าผิดรูปแบบ = ข้อความว่าง', () => {
    expect(timeAgoTh('ไม่ใช่วันที่', now)).toBe('');
    expect(timeAgoTh(null, now)).toBe('');
  });
});

describe('resolveClosingTime', () => {
  it('เวลาที่ผ่านไปแล้ววันนี้ = พรุ่งนี้ (ตรงกับกติกา server)', () => {
    const now = new Date(2026, 8, 25, 21, 0, 0);
    const later = resolveClosingTime('23:30', now);
    const passed = resolveClosingTime('08:00', now);
    expect(later?.getDate()).toBe(25);
    expect(passed?.getDate()).toBe(26);
    expect(toHHMM(passed as Date)).toBe('08:00');
  });
  it('รูปแบบผิด = null', () => {
    expect(resolveClosingTime('25:00')).toBeNull();
    expect(resolveClosingTime('8pm')).toBeNull();
  });
});
