/**
 * ทดสอบตัวช่วยเลขเวอร์ชัน/เลข build (X-App-Build ต้องเป็นเลขจริง แต่ไม่ต่ำกว่าชุดฟีเจอร์ของโค้ด)
 *
 * รัน: npx jest config/__tests__/appBuild.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { parseNativeBuild, resolveBuildNumber, resolveRequestBuild, resolveVersionName } from '../appBuild';

describe('parseNativeBuild', () => {
  it('รับเลข build จาก native ทั้ง string และ number', () => {
    expect(parseNativeBuild('46')).toBe(46);
    expect(parseNativeBuild(' 47 ')).toBe(47);
    expect(parseNativeBuild(48)).toBe(48);
  });
  it('ค่าผิดรูป/ศูนย์/ติดลบ = null', () => {
    expect(parseNativeBuild(null)).toBeNull();
    expect(parseNativeBuild(undefined)).toBeNull();
    expect(parseNativeBuild('')).toBeNull();
    expect(parseNativeBuild('0')).toBeNull();
    expect(parseNativeBuild('-3')).toBeNull();
    expect(parseNativeBuild('46.1')).toBeNull();
    expect(parseNativeBuild('abc')).toBeNull();
  });
});

describe('resolveBuildNumber / resolveVersionName', () => {
  it('ใช้ค่าจาก native เมื่ออ่านได้ ไม่งั้นค่าสำรอง', () => {
    expect(resolveBuildNumber('47', 46)).toBe(47);
    expect(resolveBuildNumber(null, 46)).toBe(46);
    expect(resolveVersionName('3.390.0', '3.389.0')).toBe('3.390.0');
    expect(resolveVersionName(null, '3.389.0')).toBe('3.389.0');
    expect(resolveVersionName('dev-build', '3.389.0')).toBe('3.389.0');
  });
});

describe('resolveRequestBuild (header X-App-Build)', () => {
  it('ส่งเลข build จริงเมื่อสูงกว่าชุดฟีเจอร์', () => {
    expect(resolveRequestBuild(46, 44)).toBe(46);
    expect(resolveRequestBuild(52, 44)).toBe(52);
  });
  it('เลขจริงต่ำผิดปกติ/อ่านไม่ได้ = ไม่ต่ำกว่าชุดฟีเจอร์ (ไม่ตกด่านไรเดอร์/eKYC)', () => {
    expect(resolveRequestBuild(12, 44)).toBe(44);
    expect(resolveRequestBuild(null, 44)).toBe(44);
    expect(resolveRequestBuild(Number.NaN, 44)).toBe(44);
  });
});
