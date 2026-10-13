/**
 * ทดสอบตัวแปลงตรา "ผ่านการอบรม" (ห้องเรียน 2026-10-13) — ค่าจาก server ต้อง null-safe เสมอ
 *
 * รัน: npx jest components/badges/__tests__/trainingBadge.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import {
  TRAINING_BADGE_LABEL,
  certifiedRoles,
  isTruthyFlag,
  parseTrainingBadge,
  parseTrainingBadges,
  parseTrainingRole,
  pickProfileBadge,
  trainingBadgeA11yLabel,
} from '../trainingBadge';

describe('parseTrainingRole', () => {
  it('รู้จักบทบาทตามสัญญา + คำพ้อง', () => {
    expect(parseTrainingRole('seller')).toBe('seller');
    expect(parseTrainingRole('merchant')).toBe('seller');
    expect(parseTrainingRole('RIDER')).toBe('rider');
    expect(parseTrainingRole(' buyer ')).toBe('buyer');
    expect(parseTrainingRole('customer')).toBe('buyer');
  });

  it('ไม่รู้จัก = null', () => {
    expect(parseTrainingRole('admin')).toBeNull();
    expect(parseTrainingRole(1)).toBeNull();
    expect(parseTrainingRole(null)).toBeNull();
  });
});

describe('isTruthyFlag', () => {
  it('true/1/"1"/"true" = จริง · อื่นๆ = เท็จ', () => {
    expect([true, 1, '1', 'true'].map(isTruthyFlag)).toEqual([true, true, true, true]);
    expect([false, 0, '0', 'false', null, undefined, 'yes'].map(isTruthyFlag)).toEqual([
      false,
      false,
      false,
      false,
      false,
      false,
      false,
    ]);
  });
});

describe('parseTrainingBadge', () => {
  it('รูปแบบตามสัญญา', () => {
    expect(
      parseTrainingBadge({ certified: true, role: 'rider', label: 'ผ่านการอบรมไรเดอร์', needs_refresh: false })
    ).toEqual({ certified: true, role: 'rider', label: 'ผ่านการอบรมไรเดอร์', needs_refresh: false });
  });

  it('ยังไม่ผ่าน / ไม่มีค่า / ชนิดแปลก = null (server รุ่นเก่า)', () => {
    expect(parseTrainingBadge(undefined)).toBeNull();
    expect(parseTrainingBadge(null)).toBeNull();
    expect(parseTrainingBadge('certified')).toBeNull();
    expect(parseTrainingBadge([{ certified: true }])).toBeNull();
    expect(parseTrainingBadge({ certified: false, role: 'rider' })).toBeNull();
  });

  it('ไม่มี role → ใช้บทบาทจากตำแหน่งที่วาง · ไม่มีทั้งคู่ = null', () => {
    expect(parseTrainingBadge({ certified: 1 }, 'seller')?.role).toBe('seller');
    expect(parseTrainingBadge({ certified: 1 })).toBeNull();
  });

  it('label ไม่ใช่ไทย/ว่าง/ยาวผิดปกติ → ข้อความมาตรฐานของบทบาท', () => {
    expect(parseTrainingBadge({ certified: true, role: 'buyer', label: 'Trained' })?.label).toBe(TRAINING_BADGE_LABEL.buyer);
    expect(parseTrainingBadge({ certified: true, role: 'buyer', label: '   ' })?.label).toBe(TRAINING_BADGE_LABEL.buyer);
    expect(parseTrainingBadge({ certified: true, role: 'buyer', label: 'ก'.repeat(200) })?.label).toBe(TRAINING_BADGE_LABEL.buyer);
  });

  it('needs_refresh รับค่า 1/"1"', () => {
    expect(parseTrainingBadge({ certified: '1', role: 'seller', needs_refresh: 1 })?.needs_refresh).toBe(true);
  });

  it('แปลงซ้ำได้ผลเหมือนเดิม (ส่งตราที่แปลงแล้วเข้า PersonAvatar ได้)', () => {
    const once = parseTrainingBadge({ certified: true, role: 'rider', needs_refresh: true });
    expect(parseTrainingBadge(once)).toEqual(once);
  });
});

describe('parseTrainingBadges + pickProfileBadge + certifiedRoles', () => {
  it('ไม่มี training_badges = null ทุกตัวช่วย', () => {
    expect(parseTrainingBadges(undefined)).toBeNull();
    expect(pickProfileBadge(null)).toBeNull();
    expect(certifiedRoles(null)).toEqual([]);
  });

  it('เลือกตราที่ยังไม่หมดอายุก่อน (ร้าน → ไรเดอร์ → ผู้ซื้อ)', () => {
    const badges = parseTrainingBadges({
      seller: { certified: true, role: 'seller', needs_refresh: true },
      rider: { certified: true, role: 'rider', needs_refresh: false },
      buyer: null,
    });
    expect(badges?.buyer).toBeNull();
    expect(pickProfileBadge(badges)?.role).toBe('rider');
    expect(certifiedRoles(badges)).toEqual(['rider']);
  });

  it('มีแต่ตราที่ต้องอบรมเพิ่ม → แสดงตราจางนั้น', () => {
    const badges = parseTrainingBadges({ buyer: { certified: true, needs_refresh: true } });
    expect(pickProfileBadge(badges)).toEqual({
      certified: true,
      role: 'buyer',
      label: TRAINING_BADGE_LABEL.buyer,
      needs_refresh: true,
    });
    expect(certifiedRoles(badges)).toEqual([]);
  });
});

describe('trainingBadgeA11yLabel', () => {
  it('ต่อท้าย "ต้องอบรมเพิ่ม" เมื่อหมดอายุ', () => {
    expect(trainingBadgeA11yLabel({ label: 'ผ่านการอบรม', needs_refresh: false })).toBe('ผ่านการอบรม');
    expect(trainingBadgeA11yLabel({ label: 'ผ่านการอบรม', needs_refresh: true })).toBe('ผ่านการอบรม · ต้องอบรมเพิ่ม');
  });
});
