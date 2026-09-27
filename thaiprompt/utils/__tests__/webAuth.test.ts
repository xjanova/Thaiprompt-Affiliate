/**
 * ตัวช่วยล็อกอินผ่านเว็บ (Facebook / Google / อีเมล) — แยก code จาก deep link + กันแลก code ซ้ำ
 */

import { describe, expect, it } from '@jest/globals';
import { claimWebAuthCode, parseWebAuthRedirect } from '../webAuth';

describe('parseWebAuthRedirect', () => {
  it('อ่าน code และ state จาก thaiprompt://auth', () => {
    expect(parseWebAuthRedirect('thaiprompt://auth?code=abc123&state=XyZ')).toEqual({
      code: 'abc123',
      state: 'XyZ',
      error: null,
    });
  });

  it('รับหน้าเว็บรุ่นเก่าที่ส่ง &amp;state มา', () => {
    expect(parseWebAuthRedirect('thaiprompt://auth?code=abc123&amp;state=XyZ').state).toBe('XyZ');
  });

  it('อ่าน error เมื่อผู้ใช้ปฏิเสธ', () => {
    expect(parseWebAuthRedirect('thaiprompt://auth?error=access_denied&state=s')).toEqual({
      code: null,
      state: 's',
      error: 'access_denied',
    });
  });

  it('ไม่มี query = ไม่มีอะไร', () => {
    expect(parseWebAuthRedirect('thaiprompt://auth')).toEqual({ code: null, state: null, error: null });
  });
});

describe('claimWebAuthCode', () => {
  it('code เดียวกันแลกได้ครั้งเดียว (auth session + deep link มาพร้อมกัน)', () => {
    expect(claimWebAuthCode('code-1')).toBe(true);
    expect(claimWebAuthCode('code-1')).toBe(false);
    expect(claimWebAuthCode('code-2')).toBe(true);
    expect(claimWebAuthCode('')).toBe(false);
  });
});
