/**
 * ทดสอบด่านยืนยันตัวตน (KYC_REQUIRED → เปิด sheet) + ตัวช่วยที่เกี่ยวข้อง
 *
 * รัน: npx jest services/ekyc/__tests__/kycGate.test.ts
 */

import { beforeEach, describe, expect, it } from '@jest/globals';
import {
  ekycPathFor,
  guardKycRequired,
  isKycGateContext,
  isKycRequired,
  kycGateRequestFrom,
  openKycGate,
  useKycGateStore,
} from '../kycGate';
import { isAllowedInternalRoute, isTrustedAvatarUrl } from '@/utils/linking';
import { shortDisplayName } from '@/utils/user';

describe('isKycRequired', () => {
  it('จับเฉพาะ KYC_REQUIRED ที่ล้มเหลว', () => {
    expect(isKycRequired({ success: false, code: 'KYC_REQUIRED' })).toBe(true);
    expect(isKycRequired({ success: false, code: 'PROFILE_PHOTO_REQUIRED' })).toBe(false);
    expect(isKycRequired({ success: true, code: 'KYC_REQUIRED' })).toBe(false);
    expect(isKycRequired(null)).toBe(false);
  });
});

describe('guardKycRequired', () => {
  beforeEach(() => useKycGateStore.getState().close());

  it('KYC_REQUIRED → เปิด sheet พร้อมบริบท/สถานะ/ข้อความ แล้วคืน true', () => {
    const handled = guardKycRequired(
      { success: false, code: 'KYC_REQUIRED', message: 'กรุณายืนยันตัวตนก่อนใช้งานส่วนนี้', data: { kyc_status: 'pending' } },
      'checkout'
    );
    expect(handled).toBe(true);
    expect(useKycGateStore.getState().request).toEqual({
      context: 'checkout',
      kycStatus: 'pending',
      message: 'กรุณายืนยันตัวตนก่อนใช้งานส่วนนี้',
    });
  });

  it('error อื่น → ไม่แตะ sheet และคืน false', () => {
    expect(guardKycRequired({ success: false, code: 'OUT_OF_STOCK' }, 'checkout')).toBe(false);
    expect(useKycGateStore.getState().request).toBeNull();
  });

  it('ไม่มี data → สถานะ none', () => {
    expect(kycGateRequestFrom({ success: false, code: 'KYC_REQUIRED' }, 'rider')).toEqual({
      context: 'rider',
      kycStatus: 'none',
      message: null,
    });
  });

  it('เปิดเอง / ปิด', () => {
    openKycGate('seller');
    expect(useKycGateStore.getState().request?.context).toBe('seller');
    useKycGateStore.getState().close();
    expect(useKycGateStore.getState().request).toBeNull();
  });
});

describe('เส้นทาง', () => {
  it('รอเจ้าหน้าที่ → หน้าผล · อื่นๆ → หน้าเริ่ม (ผ่าน allowlist)', () => {
    expect(ekycPathFor('rider', 'pending')).toBe('/ekyc/result?from=rider');
    expect(ekycPathFor('checkout', 'none')).toBe('/ekyc?from=checkout');
    expect(isAllowedInternalRoute(ekycPathFor('checkout', 'none'))).toBe(true);
    expect(isAllowedInternalRoute('/ekyc/result')).toBe(true);
  });

  it('รับเฉพาะบริบทที่รู้จัก', () => {
    expect(isKycGateContext('withdraw')).toBe(true);
    expect(isKycGateContext('evil')).toBe(false);
    expect(isKycGateContext(undefined)).toBe(false);
  });
});

describe('รูปโปรไฟล์จาก CDN ของบัญชีโซเชียล', () => {
  it('เว็บเรา + LINE/Google/Facebook (https เท่านั้น)', () => {
    expect(isTrustedAvatarUrl('https://main.thaiprompt.online/storage/avatars/a.webp')).toBe(true);
    expect(isTrustedAvatarUrl('https://profile.line-scdn.net/0hAbc')).toBe(true);
    expect(isTrustedAvatarUrl('https://lh3.googleusercontent.com/a/x=s96-c')).toBe(true);
    expect(isTrustedAvatarUrl('https://scontent.xx.fbcdn.net/v/t1.jpg')).toBe(true);
    expect(isTrustedAvatarUrl('https://platform-lookaside.fbsbx.com/platform/profilepic/?asid=1')).toBe(true);
  });

  it('โดเมนอื่น / http / หลอกด้วย suffix ไม่ผ่าน', () => {
    expect(isTrustedAvatarUrl('https://evil.example/a.jpg')).toBe(false);
    expect(isTrustedAvatarUrl('http://profile.line-scdn.net/0hAbc')).toBe(false);
    expect(isTrustedAvatarUrl('https://evilline-scdn.net/a.jpg')).toBe(false);
    expect(isTrustedAvatarUrl('https://line-scdn.net.evil.com/a.jpg')).toBe(false);
    expect(isTrustedAvatarUrl('javascript:alert(1)')).toBe(false);
  });
});

describe('shortDisplayName', () => {
  it('ชื่อ + อักษรแรกนามสกุล (ตัดคำนำหน้า/สระหน้า)', () => {
    expect(shortDisplayName('นาย ณัฐ ใจงาม')).toBe('ณัฐ จ.');
    expect(shortDisplayName('สมหญิง เมตตา')).toBe('สมหญิง ม.');
    expect(shortDisplayName('สมชาย')).toBe('สมชาย');
    expect(shortDisplayName('')).toBe('ผู้ใช้');
  });
});
