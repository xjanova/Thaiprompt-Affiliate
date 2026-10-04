/**
 * ทดสอบตัวแปลงข้อมูล eKYC (สัญญา 2026-10-04) + ตัวช่วยวันที่ไทย/ชื่อ/เหตุผล
 *
 * รัน: npx jest services/api/__tests__/ekycApi.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import {
  buildReasonItems,
  cleanThaiName,
  formatThaiDate,
  framesMatchChallenges,
  isCardReason,
  isLivenessReason,
  isSessionUsable,
  matchReasonTone,
  normalizeEkycFace,
  normalizeEkycIdCard,
  normalizeEkycSession,
  normalizeEkycStatus,
  normalizeKycStatusValue,
  parseThaiBirthDate,
  reasonText,
  reasonTexts,
  reasonTone,
  retakeTarget,
  retryAfterSeconds,
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
      can_retake: false,
      processing: false,
      attempts_left: 2,
      last_decision: 'review',
      reasons: ['LOW_MATCH'],
      reason_items: [{ code: 'LOW_MATCH', text: 'ใบหน้าคล้ายรูปในบัตรน้อย', tone: 'fail' }],
      message: null,
      required_for: ['order', 'rider', 'seller'],
      name_th: null,
      id_number_masked: null,
    });
  });

  it('can_retake / processing / ข้อความไทยจาก server / ข้อความเจ้าหน้าที่', () => {
    const s = normalizeEkycStatus({
      kyc_status: 'pending',
      can_start: false,
      can_retake: true,
      processing: 1,
      reasons: ['BORDERLINE_MATCH', 'USER_CORRECTED'],
      reason_texts: ['หน้าคล้ายบัตรปานกลาง', 'คุณแก้ชื่อเอง'],
      message: '  ขอรูปที่สว่างกว่านี้ครับ  ',
    });
    expect(s.can_start).toBe(false);
    expect(s.can_retake).toBe(true);
    expect(s.processing).toBe(true);
    expect(s.reason_items.map((r) => r.text)).toEqual(['หน้าคล้ายบัตรปานกลาง', 'คุณแก้ชื่อเอง']);
    expect(s.reason_items.every((r) => r.tone === 'warn')).toBe(true);
    expect(s.message).toBe('ขอรูปที่สว่างกว่านี้ครับ');
  });

  it('ยืนยันแล้ว = ถ่ายใหม่ไม่ได้ ไม่มีงานค้าง', () => {
    const s = normalizeEkycStatus({ verified: true, can_retake: true, processing: true });
    expect(s.can_retake).toBe(false);
    expect(s.processing).toBe(false);
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
    // server รุ่นก่อนไม่ส่ง ai_available = ตัวอ่านทำงานปกติ
    expect(r.ai_available).toBe(true);
  });

  it('ผลตรวจบัตร 3 สถานะ: null/ไม่ส่ง = ไม่ทราบ (ไม่ใช่ไม่ผ่าน) · ตัวอ่านไม่พร้อม', () => {
    const r = normalizeEkycIdCard({
      status: 'ok',
      ai_available: false,
      checks: { checksum: null, not_expired: false, quality_ok: true },
      ocr_confidence: 0,
    });
    expect(r.checks.checksum).toBeNull();
    expect(r.checks.not_expired).toBe(false);
    expect(r.checks.card_real).toBeNull();
    expect(r.ai_available).toBe(false);
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

  it('ข้อความไทยจาก server + ข้อความเจ้าหน้าที่ · source = submit', () => {
    const r = normalizeEkycFace({
      decision: 'review',
      reasons: ['REPLAY_SUSPECTED', 'BORDERLINE_MATCH'],
      reason_texts: ['ภาพนี้เคยส่งมาแล้ว', 'หน้าคล้ายบัตรระดับกลาง'],
      message: null,
    });
    expect(r.source).toBe('submit');
    expect(r.message).toBeNull();
    expect(r.reason_items).toEqual([
      { code: 'REPLAY_SUSPECTED', text: 'ภาพนี้เคยส่งมาแล้ว', tone: 'fail' },
      { code: 'BORDERLINE_MATCH', text: 'หน้าคล้ายบัตรระดับกลาง', tone: 'warn' },
    ]);
  });
});

describe('buildReasonItems', () => {
  it('ข้อความของ server ก่อน · ไม่มี/ไม่ใช่ภาษาไทย → ข้อความของแอป · ไม่รู้จักทั้งคู่ = ไม่แสดง', () => {
    const items = buildReasonItems(
      ['LOW_MATCH', 'BORDERLINE_MATCH', 'BRAND_NEW_CODE', 'ANOTHER_NEW', 'bad code!'],
      ['', 'raw english', 'ระบบพบเหตุใหม่', null, 'ข้อความไทยของรหัสเพี้ยน']
    );
    expect(items).toEqual([
      { code: 'LOW_MATCH', text: 'ใบหน้าคล้ายรูปในบัตรน้อย', tone: 'fail' },
      { code: 'BORDERLINE_MATCH', text: 'ใบหน้าคล้ายรูปในบัตรระดับกลาง', tone: 'warn' },
      { code: 'BRAND_NEW_CODE', text: 'ระบบพบเหตุใหม่', tone: 'warn' },
      { code: null, text: 'ข้อความไทยของรหัสเพี้ยน', tone: 'warn' },
    ]);
  });

  it('ตัดข้อความซ้ำ · ไม่มีอะไรเลย = []', () => {
    expect(buildReasonItems(['BLURRY', 'BLURRY'], [])).toHaveLength(1);
    expect(buildReasonItems(undefined, undefined)).toEqual([]);
  });

  it('รหัสใหม่ทุกตัวมีข้อความสำรองภาษาไทย', () => {
    for (const code of [
      'REPLAY_SUSPECTED',
      'PRIOR_REJECTED',
      'FACES_INCONSISTENT',
      'BORDERLINE_MATCH',
      'LOW_LIVENESS',
      'LOW_REAL',
      'LOW_CARD_REAL',
      'ID_UNREADABLE',
      'EXPIRY_UNKNOWN',
      'NO_MATCH_SCORE',
      'ATTEMPTS_EXHAUSTED',
      'ADMIN_REJECTED',
      'ADMIN_RETAKE',
    ]) {
      expect(reasonText(code)).toMatch(/[\u0E00-\u0E7F]/);
    }
  });
});

describe('ระดับของเหตุผล', () => {
  it('fail = ไม่ผ่านจริง · warn = ระดับกลาง/เจ้าหน้าที่ดู (ไม่รู้จัก = warn)', () => {
    expect(reasonTone('LOW_MATCH')).toBe('fail');
    expect(reasonTone('CHALLENGE_FAILED:nod')).toBe('fail');
    expect(reasonTone('BORDERLINE_MATCH')).toBe('warn');
    expect(reasonTone('EXPIRY_UNKNOWN')).toBe('warn');
    expect(reasonTone('SOMETHING_NEW')).toBe('warn');
  });

  it('แถวเป็นคนจริง / ใบหน้าตรงบัตร', () => {
    expect(isLivenessReason('REPLAY_SUSPECTED')).toBe(true);
    expect(isLivenessReason('CHALLENGE_FAILED:smile')).toBe(true);
    expect(isLivenessReason('BORDERLINE_MATCH')).toBe(false);
    expect(matchReasonTone(['LOW_MATCH'])).toBe('fail');
    expect(matchReasonTone(['BORDERLINE_MATCH'])).toBe('warn');
    expect(matchReasonTone(['NO_MATCH_SCORE', 'USER_CORRECTED'])).toBe('warn');
    expect(matchReasonTone(['USER_CORRECTED'])).toBeNull();
  });
});

describe('ตัวช่วยรอบ', () => {
  it('รอบที่มีท่าที่แอปไม่รู้จัก = ใช้ไม่ได้', () => {
    expect(isSessionUsable(normalizeEkycSession({ session_id: 1, challenges: ['blink', 'nod'] }))).toBe(true);
    expect(isSessionUsable(normalizeEkycSession({ session_id: 1, challenges: ['blink', 'wink'] }))).toBe(false);
    expect(isSessionUsable(normalizeEkycSession({ session_id: 1, challenges: [] }))).toBe(false);
    expect(isSessionUsable(normalizeEkycSession({ challenges: ['blink'] }))).toBe(false);
    expect(isSessionUsable(null)).toBe(false);
  });

  it('เวลารอเมื่อระบบตรวจไม่ว่าง: 3–60 วินาที · ไม่ส่งมา = 10', () => {
    expect(retryAfterSeconds({ retry_after_seconds: 15 })).toBe(15);
    expect(retryAfterSeconds({ retry_after_seconds: '7' })).toBe(7);
    expect(retryAfterSeconds({ retry_after_seconds: 1 })).toBe(3);
    expect(retryAfterSeconds({ retry_after_seconds: 600 })).toBe(60);
    expect(retryAfterSeconds({})).toBe(10);
    expect(retryAfterSeconds(undefined)).toBe(10);
    expect(retryAfterSeconds({ retry_after_seconds: 'x' })).toBe(10);
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
    expect(retakeTarget(['ID_UNREADABLE'])).toBe('card');
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
