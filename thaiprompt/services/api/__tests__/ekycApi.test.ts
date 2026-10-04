/**
 * ทดสอบตัวแปลงข้อมูล eKYC (สัญญา 2026-10-04) + ตัวช่วยวันที่ไทย/ชื่อ/เหตุผล
 *
 * รัน: npx jest services/api/__tests__/ekycApi.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import {
  cleanThaiName,
  formatThaiDate,
  framesMatchChallenges,
  isCardReason,
  normalizeEkycFace,
  normalizeEkycIdCard,
  normalizeEkycSession,
  normalizeEkycStatus,
  normalizeKycStatusValue,
  parseThaiBirthDate,
  reasonText,
  reasonTexts,
  retakeTarget,
  score01,
} from '../ekycApi';

describe('normalizeEkycStatus', () => {
  it('รับรูปแบบตามสัญญาครบ', () => {
    const s = normalizeEkycStatus({
      kyc_status: 'pending',
      verified: false,
      verified_at: null,
      method: 'ekyc',
      can_start: true,
      attempts_left: 2,
      last_decision: 'review',
      reasons: ['LOW_MATCH'],
      required_for: ['order', 'rider', 'seller'],
    });
    expect(s).toEqual({
      kyc_status: 'pending',
      verified: false,
      verified_at: null,
      method: 'ekyc',
      can_start: true,
      attempts_left: 2,
      last_decision: 'review',
      reasons: ['LOW_MATCH'],
      required_for: ['order', 'rider', 'seller'],
      name_th: null,
      id_number_masked: null,
    });
  });

  it('verified = true บังคับสถานะ approved และเริ่มใหม่ไม่ได้', () => {
    const s = normalizeEkycStatus({ kyc_status: 'none', verified: 1, can_start: true });
    expect(s.kyc_status).toBe('approved');
    expect(s.verified).toBe(true);
    expect(s.can_start).toBe(false);
  });

  it('ค่าแปลกปลอม → ค่าปลอดภัย', () => {
    const s = normalizeEkycStatus({ kyc_status: 'weird', attempts_left: 'x', last_decision: 'hack', reasons: ['<script>', 42, 'BLURRY'], method: 'x' });
    expect(s.kyc_status).toBe('none');
    expect(s.attempts_left).toBe(0);
    expect(s.last_decision).toBeNull();
    expect(s.reasons).toEqual(['BLURRY']);
    expect(s.method).toBeNull();
    expect(normalizeEkycStatus(null).kyc_status).toBe('none');
  });

  it('สถานะชื่อเดิม (not_submitted / verified) แปลงให้', () => {
    expect(normalizeKycStatusValue('not_submitted')).toBe('none');
    expect(normalizeKycStatusValue('verified')).toBe('approved');
    expect(normalizeKycStatusValue('APPROVED')).toBe('approved');
  });

  it('ไม่ส่ง can_start → pending เริ่มไม่ได้ / none เริ่มได้', () => {
    expect(normalizeEkycStatus({ kyc_status: 'pending' }).can_start).toBe(false);
    expect(normalizeEkycStatus({ kyc_status: 'none' }).can_start).toBe(true);
  });
});

describe('normalizeEkycSession', () => {
  it('คงลำดับคำสั่งของ server · session_id เป็น string', () => {
    const s = normalizeEkycSession({ session_id: 77, challenges: ['smile', 'turn_left', 'blink'], expires_at: '2026-10-04T13:20:00Z', attempts_left: 3 });
    expect(s.session_id).toBe('77');
    expect(s.challenges).toEqual(['smile', 'turn_left', 'blink']);
    expect(s.unsupported).toEqual([]);
    expect(s.attempts_left).toBe(3);
  });

  it('คำสั่งที่แอปไม่รู้จัก → unsupported (หน้าจอบอกให้อัปเดตแอป)', () => {
    const s = normalizeEkycSession({ session_id: 'abc', challenges: ['blink', 'wink', 'nod'] });
    expect(s.challenges).toEqual(['blink', 'nod']);
    expect(s.unsupported).toEqual(['wink']);
  });

  it('ตัดคำสั่งซ้ำ', () => {
    expect(normalizeEkycSession({ session_id: 'a', challenges: ['nod', 'nod', 'smile'] }).challenges).toEqual(['nod', 'smile']);
  });
});

describe('normalizeEkycIdCard', () => {
  it('ผลอ่านบัตรตามสัญญา', () => {
    const r = normalizeEkycIdCard({
      status: 'ok',
      fields: {
        id_number_masked: '1 1037 •••••• 12 3',
        name_th: 'นาย ณัฐ ใจงาม',
        name_en: 'Mr. Nat Jaingam',
        birth_date: '1995-01-12',
        expiry_date: '2030-01-11T00:00:00Z',
      },
      checks: { checksum: true, not_expired: true, card_real: 0.91, quality_ok: 1 },
      ocr_confidence: 98,
      reasons: [],
    });
    expect(r.status).toBe('ok');
    expect(r.fields.expiry_date).toBe('2030-01-11');
    expect(r.checks).toEqual({ checksum: true, not_expired: true, card_real: true, quality_ok: true });
    expect(r.ocr_confidence).toBeCloseTo(0.98);
  });

  it('status อื่น = retake · คะแนนบัตรจริงต่ำ = ไม่ผ่าน', () => {
    const r = normalizeEkycIdCard({ status: 'weird', checks: { card_real: 0.2 }, reasons: ['GLARE'] });
    expect(r.status).toBe('retake');
    expect(r.checks.card_real).toBe(false);
    expect(r.fields.name_th).toBeNull();
    expect(r.reasons).toEqual(['GLARE']);
  });
});

describe('normalizeEkycFace', () => {
  it('ผลตัดสินตามสัญญา', () => {
    const r = normalizeEkycFace({
      decision: 'approved',
      scores: { face_match: 0.82, liveness: 0.97, ocr: 0.98, card_real: 0.9 },
      reasons: [],
      kyc_status: 'approved',
      attempts_left: 2,
    });
    expect(r.decision).toBe('approved');
    expect(r.scores.liveness).toBeCloseTo(0.97);
    expect(r.kyc_status).toBe('approved');
  });

  it('decision ไม่รู้จัก → review (ไม่บอกว่าผ่านเอง)', () => {
    expect(normalizeEkycFace({ decision: 'yes' }).decision).toBe('review');
    expect(normalizeEkycFace(undefined).decision).toBe('review');
  });

  it('เหตุผลแบบมีป้าย CHALLENGE_FAILED:<label>', () => {
    expect(normalizeEkycFace({ decision: 'retake', reasons: ['CHALLENGE_FAILED:blink'] }).reasons).toEqual(['CHALLENGE_FAILED:blink']);
  });
});

describe('score01', () => {
  it('รับ 0–1 และ 0–100', () => {
    expect(score01(0.5)).toBe(0.5);
    expect(score01(75)).toBeCloseTo(0.75);
    expect(score01('0.3')).toBeCloseTo(0.3);
    expect(score01(null)).toBeNull();
    expect(score01(-1)).toBeNull();
    expect(score01(true)).toBeNull();
  });
});

describe('เหตุผล → ข้อความไทย', () => {
  it('แปลรหัสที่รู้จัก · ไม่รู้จัก = ไม่แสดง', () => {
    expect(reasonText('BLURRY')).toBe('รูปบัตรไม่ชัด');
    expect(reasonText('CHALLENGE_FAILED:turn_left')).toContain('หันซ้าย');
    expect(reasonText('SOMETHING_NEW')).toBeNull();
    expect(reasonTexts(['BLURRY', 'BLURRY', 'X'])).toEqual(['รูปบัตรไม่ชัด']);
  });

  it('ถ่ายใหม่เริ่มที่บัตรเมื่อมีเหตุผลของบัตร', () => {
    expect(isCardReason('GLARE')).toBe(true);
    expect(retakeTarget(['LOW_MATCH', 'GLARE'])).toBe('card');
    expect(retakeTarget(['CHALLENGE_FAILED:blink'])).toBe('face');
    expect(retakeTarget([])).toBe('face');
  });
});

describe('วันที่ไทย', () => {
  it('ค.ศ. → พ.ศ. เต็ม/ย่อ', () => {
    expect(formatThaiDate('1995-01-12')).toBe('12 มกราคม 2538');
    expect(formatThaiDate('2030-01-11', true)).toBe('11 ม.ค. 2573');
    expect(formatThaiDate('nope')).toBeNull();
  });

  it('วันเกิดที่ผู้ใช้กรอก (ปี พ.ศ.)', () => {
    const now = new Date(2026, 9, 4);
    expect(parseThaiBirthDate('12', '1', '2538', now)).toBe('1995-01-12');
    expect(parseThaiBirthDate('29', '2', '2543', now)).toBe('2000-02-29');
    expect(parseThaiBirthDate('29', '2', '2544', now)).toBeNull();
    expect(parseThaiBirthDate('1', '13', '2538', now)).toBeNull();
    expect(parseThaiBirthDate('1', '1', '2600', now)).toBeNull();
    expect(parseThaiBirthDate('1', '1', '2400', now)).toBeNull();
    expect(parseThaiBirthDate('x', '1', '2538', now)).toBeNull();
  });

  it('ชื่อไทยที่แก้เอง', () => {
    expect(cleanThaiName('  นาย   ณัฐ  ใจงาม ')).toBe('นาย ณัฐ ใจงาม');
    expect(cleanThaiName('John')).toBeNull();
    expect(cleanThaiName('ก')).toBeNull();
  });
});

describe('framesMatchChallenges', () => {
  it('ต้องเป็น neutral ตามด้วยคำสั่งตามลำดับเป๊ะ', () => {
    const ch = ['blink', 'turn_left', 'smile'] as const;
    const ok = [
      { uri: 'file:///a', label: 'neutral' as const },
      { uri: 'file:///b', label: 'blink' as const },
      { uri: 'file:///c', label: 'turn_left' as const },
      { uri: 'file:///d', label: 'smile' as const },
    ];
    expect(framesMatchChallenges(ok, [...ch])).toBe(true);
    expect(framesMatchChallenges(ok.slice(1), [...ch])).toBe(false);
    expect(framesMatchChallenges([ok[0], ok[2], ok[1], ok[3]], [...ch])).toBe(false);
  });
});
