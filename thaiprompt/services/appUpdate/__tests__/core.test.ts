/**
 * ทดสอบตัวช่วยแบบฟังก์ชันล้วนของระบบอัปเดตแอปในตัว
 *
 * รัน: npx jest services/appUpdate/__tests__/core.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import {
  apkFileName,
  AUTO_CHECK_INTERVAL_MS,
  AUTO_RETRY_AFTER_FAIL_MS,
  etaSeconds,
  formatEta,
  formatMB,
  formatProgressMB,
  formatSpeed,
  formatThaiDate,
  isCalmRoute,
  isNewerBuild,
  isSnoozed,
  isTrustedDownloadUrl,
  makeSnooze,
  MAX_NOTES,
  nextSpeedMeter,
  normalizeNotes,
  normalizeUpdateResponse,
  parseHttpUrl,
  parseSidecar,
  parseSnooze,
  planHousekeeping,
  progressFraction,
  progressPercent,
  resolveInstalledBuild,
  shouldAutoCheck,
  sidecarMatches,
  SNOOZE_MS,
  SPEED_SAMPLE_MIN_MS,
  startSpeedMeter,
  type AppUpdateLatest,
} from '../core';

const API = 'https://main.thaiprompt.online/api/v1';
const MD5 = '0123456789abcdef0123456789abcdef';
const MB = 1024 * 1024;

const rawLatest = (over: Record<string, unknown> = {}) => ({
  version: '3.388.0',
  version_code: 45,
  size: 60542518,
  md5: MD5.toUpperCase(),
  sha256: 'a'.repeat(64),
  download_url: 'https://main.thaiprompt.online/storage/app-downloads/ThaiPrompt-APP-3.388.0.apk?h=82e617392f49',
  published_at: '2026-10-05T10:00:00+07:00',
  notes: ['แก้ปัญหาเล็กน้อย', 'เพิ่มระบบอัปเดตในแอป'],
  ...over,
});

const rawData = (over: Record<string, unknown> = {}, latestOver: Record<string, unknown> = {}) => ({
  platform: 'android',
  current_build: 44,
  update_available: true,
  required: false,
  min_supported_build: 0,
  latest: rawLatest(latestOver),
  ...over,
});

describe('resolveInstalledBuild — versionCode ที่ติดตั้งอยู่', () => {
  it('ใช้ค่าจาก native เมื่อเป็นจำนวนเต็มบวก', () => {
    expect(resolveInstalledBuild('45', 44)).toBe(45);
    expect(resolveInstalledBuild(' 46 ', 44)).toBe(46);
    expect(resolveInstalledBuild(47, 44)).toBe(47);
  });

  it('ค่าไม่ใช่จำนวนเต็มบวก = ใช้ค่าสำรอง (APP_FEATURE_BUILD)', () => {
    for (const bad of [null, undefined, '', '0', '-3', 'abc', '45.1', '1e3', '9999999999']) {
      expect(resolveInstalledBuild(bad as string | null | undefined, 44)).toBe(44);
    }
    expect(resolveInstalledBuild(0, 44)).toBe(44);
  });

  it('isNewerBuild เทียบเลข versionCode', () => {
    expect(isNewerBuild(45, 44)).toBe(true);
    expect(isNewerBuild(44, 44)).toBe(false);
    expect(isNewerBuild(43, 44)).toBe(false);
    expect(isNewerBuild(null, 44)).toBe(false);
  });
});

describe('normalizeUpdateResponse — แปลงคำตอบ server', () => {
  it('มีอัปเดตทั่วไป', () => {
    const info = normalizeUpdateResponse(rawData(), 44, API);
    expect(info.updateAvailable).toBe(true);
    expect(info.required).toBe(false);
    expect(info.latest).toMatchObject({ version: '3.388.0', versionCode: 45, size: 60542518, md5: MD5 });
    expect(info.latest?.notes).toEqual(['แก้ปัญหาเล็กน้อย', 'เพิ่มระบบอัปเดตในแอป']);
  });

  it('คำนวณเอง: server บอก update_available=false แต่ version_code ใหม่กว่า = มีอัปเดต · กลับกันก็ไม่เชื่อ', () => {
    expect(normalizeUpdateResponse(rawData({ update_available: false }), 44, API).updateAvailable).toBe(true);
    expect(normalizeUpdateResponse(rawData({ update_available: true }), 45, API).updateAvailable).toBe(false);
    expect(normalizeUpdateResponse(rawData({ update_available: true }), 46, API).updateAvailable).toBe(false);
  });

  it('ไม่มีไฟล์เผยแพร่ (latest = null) = ไม่มีอัปเดต และไม่บังคับแม้ server บอก required', () => {
    const info = normalizeUpdateResponse(rawData({ latest: null, required: true, min_supported_build: 99 }), 44, API);
    expect(info).toMatchObject({ latest: null, updateAvailable: false, required: false });
  });

  it('บังคับอัปเดตเมื่อที่ติดตั้งต่ำกว่า min_supported_build หรือ server บอก required', () => {
    expect(normalizeUpdateResponse(rawData({ min_supported_build: 45 }), 44, API).required).toBe(true);
    expect(normalizeUpdateResponse(rawData({ required: true }), 44, API).required).toBe(true);
    expect(normalizeUpdateResponse(rawData({ min_supported_build: 44 }), 44, API).required).toBe(false);
  });

  it('ข้อมูลไม่ครบ/ผิดรูป = ถือว่าไม่มีอัปเดต', () => {
    const bads: Record<string, unknown>[] = [
      { size: 0 },
      { size: -5 },
      { size: 'abc' },
      { md5: 'xyz' },
      { md5: '' },
      { version_code: 0 },
      { version_code: '4a' },
      { version: '' },
      { version: '../../evil' },
      { download_url: 'https://evil.example.com/a.apk' },
      { download_url: 'http://main.thaiprompt.online/a.apk' },
    ];
    for (const bad of bads) {
      const info = normalizeUpdateResponse(rawData({}, bad), 44, API);
      expect(info.updateAvailable).toBe(false);
      expect(info.latest).toBeNull();
    }
    expect(normalizeUpdateResponse(null, 44, API)).toMatchObject({ latest: null, updateAvailable: false });
    expect(normalizeUpdateResponse('oops', 44, API)).toMatchObject({ latest: null, updateAvailable: false });
  });

  it('sha256 ผิดรูป = null (ไม่ทำให้ทั้งรายการเสีย)', () => {
    expect(normalizeUpdateResponse(rawData({}, { sha256: 'nope' }), 44, API).latest?.sha256).toBeNull();
  });

  it('รายการ "มีอะไรใหม่": ตัดอีโมจิ ข้ามค่าที่ไม่ใช่ข้อความ ตัดความยาว จำกัดจำนวน', () => {
    expect(normalizeNotes(['🎉 ใหม่!', 42, '   ', '- จุดนำหน้า', null])).toEqual(['ใหม่!', 'จุดนำหน้า']);
    const long = normalizeNotes(['ก'.repeat(500)]);
    expect(long[0].length).toBe(160);
    expect(long[0].endsWith('…')).toBe(true);
    expect(normalizeNotes(Array.from({ length: 30 }, (_, i) => `ข้อ ${i}`))).toHaveLength(MAX_NOTES);
    expect(normalizeNotes('ไม่ใช่ array')).toEqual([]);
  });
});

describe('isTrustedDownloadUrl — ลิงก์ต้องโดเมนเดียวกับ API', () => {
  it('scheme + host + port ตรงกัน = ผ่าน (ตัวพิมพ์ใหญ่/พอร์ตปกติก็ผ่าน)', () => {
    expect(isTrustedDownloadUrl('https://main.thaiprompt.online/storage/a.apk?h=1', API)).toBe(true);
    expect(isTrustedDownloadUrl('https://MAIN.Thaiprompt.Online/storage/a.apk', API)).toBe(true);
    expect(isTrustedDownloadUrl('https://main.thaiprompt.online:443/storage/a.apk', API)).toBe(true);
    expect(isTrustedDownloadUrl('HTTPS://main.thaiprompt.online/a.apk', API)).toBe(true);
  });

  it('http ขณะที่ API เป็น https = ไม่ผ่าน', () => {
    expect(isTrustedDownloadUrl('http://main.thaiprompt.online/storage/a.apk', API)).toBe(false);
  });

  it('โฮสต์อื่น / โดเมนหลอก / มี user:pass@ / พอร์ตอื่น = ไม่ผ่าน', () => {
    for (const url of [
      'https://evil.example.com/a.apk',
      'https://main.thaiprompt.online.evil.com/a.apk',
      'https://evilmain.thaiprompt.online/a.apk',
      'https://thaiprompt.online/a.apk',
      'https://main.thaiprompt.online@evil.com/a.apk',
      'https://user:pass@main.thaiprompt.online/a.apk',
      'https://user@main.thaiprompt.online/a.apk',
      'https://main.thaiprompt.online:8443/a.apk',
      'https://main.thaiprompt.online./a.apk',
    ]) {
      expect([url, isTrustedDownloadUrl(url, API)]).toEqual([url, false]);
    }
  });

  it('รูปแบบแปลก = ไม่ผ่าน', () => {
    for (const url of [
      '',
      'javascript:alert(1)',
      'file:///sdcard/a.apk',
      '//main.thaiprompt.online/a.apk',
      'https://main.thaiprompt.online\\@evil.com/a.apk',
      'https://main.thaiprompt.online /a.apk',
      'https://main.thaiprompt.online%2eevil.com/a.apk',
      'https://main.thaiprompt.online:99999/a.apk',
      'ftp://main.thaiprompt.online/a.apk',
      null,
      42,
    ]) {
      expect(isTrustedDownloadUrl(url, API)).toBe(false);
    }
  });

  it('API ชี้เครื่องทดสอบแบบ http + พอร์ต = ต้องตรงพอร์ตด้วย', () => {
    const local = 'http://10.0.2.2:8765/api/v1';
    expect(isTrustedDownloadUrl('http://10.0.2.2:8765/storage/a.apk', local)).toBe(true);
    expect(isTrustedDownloadUrl('http://10.0.2.2:8766/storage/a.apk', local)).toBe(false);
    expect(isTrustedDownloadUrl('http://10.0.2.2/storage/a.apk', local)).toBe(false);
  });

  it('parseHttpUrl แยก IPv6 และพอร์ตได้', () => {
    expect(parseHttpUrl('http://[::1]:8080/x')).toMatchObject({ host: '[::1]', port: 8080, scheme: 'http' });
    expect(parseHttpUrl('https://a.b/x')).toMatchObject({ host: 'a.b', port: 443, hasUserInfo: false, path: '/x' });
    expect(parseHttpUrl('https://u:p@a.b')).toMatchObject({ host: 'a.b', hasUserInfo: true, path: '/' });
  });
});

describe('"ไว้ทีหลัง" — เลื่อนเฉพาะเวอร์ชันนั้น 24 ชั่วโมง', () => {
  const NOW = 1_800_000_000_000;

  it('เวอร์ชันเดียวกันภายใน 24 ชม. = เลื่อนอยู่ · ครบเวลา = ขึ้นอีก', () => {
    const rec = makeSnooze(45, NOW);
    expect(rec.until).toBe(NOW + SNOOZE_MS);
    expect(isSnoozed(rec, 45, NOW + 1)).toBe(true);
    expect(isSnoozed(rec, 45, NOW + SNOOZE_MS - 1)).toBe(true);
    expect(isSnoozed(rec, 45, NOW + SNOOZE_MS)).toBe(false);
  });

  it('เวอร์ชันใหม่กว่า = ขึ้นทันที', () => {
    expect(isSnoozed(makeSnooze(45, NOW), 46, NOW + 1000)).toBe(false);
  });

  it('ไม่ถาวร: ค่าที่ไกลเกิน 24 ชม. (นาฬิกาเครื่องถูกย้อน/ถูกแก้) = ไม่เลื่อน', () => {
    expect(isSnoozed({ versionCode: 45, until: NOW + SNOOZE_MS * 30 }, 45, NOW)).toBe(false);
    expect(isSnoozed(makeSnooze(45, NOW), 45, NOW - 2 * SNOOZE_MS)).toBe(false);
  });

  it('อ่านค่าที่บันทึกไว้: เสีย/ไม่มี = null', () => {
    expect(parseSnooze(JSON.stringify(makeSnooze(45, NOW)))).toEqual({ versionCode: 45, until: NOW + SNOOZE_MS });
    expect(parseSnooze(null)).toBeNull();
    expect(parseSnooze('{bad json')).toBeNull();
    expect(parseSnooze(JSON.stringify({ versionCode: 'x', until: 1 }))).toBeNull();
    expect(isSnoozed(parseSnooze('garbage'), 45, NOW)).toBe(false);
  });
});

describe('จังหวะตรวจอัตโนมัติ / หน้าที่ห้ามเด้ง', () => {
  const NOW = 1_800_000_000_000;

  it('ตรวจใหม่เมื่อห่างจากครั้งที่สำเร็จเกิน 6 ชม. · พลาดล่าสุดไม่ถึง 15 นาที = รอก่อน', () => {
    expect(shouldAutoCheck(NOW, 0, 0)).toBe(true);
    expect(shouldAutoCheck(NOW, NOW - AUTO_CHECK_INTERVAL_MS + 1000, NOW - AUTO_CHECK_INTERVAL_MS + 1000)).toBe(false);
    expect(shouldAutoCheck(NOW, NOW - AUTO_CHECK_INTERVAL_MS, NOW - AUTO_CHECK_INTERVAL_MS)).toBe(true);
    expect(shouldAutoCheck(NOW, 0, NOW - 60_000)).toBe(false);
    expect(shouldAutoCheck(NOW, 0, NOW - AUTO_RETRY_AFTER_FAIL_MS)).toBe(true);
    // นาฬิกาเครื่องถูกย้อน = ถือว่าถึงเวลา
    expect(shouldAutoCheck(NOW, NOW + 10_000, NOW + 10_000)).toBe(true);
  });

  it('ไม่เด้งทับกล้องยืนยันตัวตน / ชำระเงิน / ส่งมอบ / หน้าอัปเดต', () => {
    for (const path of ['/ekyc', '/ekyc/face', '/ekyc/processing', '/checkout', '/taladsod/checkout', '/order/12', '/taladsod/order/5', '/handover/abc', '/app-update', '/intro-video', '/wallet-topup']) {
      expect([path, isCalmRoute(path)]).toEqual([path, false]);
    }
  });

  it('หน้าทั่วไปแสดงได้ (ไม่จับคำนำหน้าคล้ายกันผิด)', () => {
    for (const path of ['/', '/settings', '/orders', '/profile', '/taladsod', '/checkout-guide', '/ordering']) {
      expect([path, isCalmRoute(path)]).toEqual([path, true]);
    }
    expect(isCalmRoute(null)).toBe(false);
    expect(isCalmRoute('/checkout?x=1')).toBe(false);
  });
});

describe('การแสดงผลขนาด / ความเร็ว / เวลาที่เหลือ', () => {
  it('ขนาด MB ทศนิยม 1 ตำแหน่ง', () => {
    expect(formatMB(60542518)).toBe('57.7 MB');
    expect(formatMB(0)).toBe('0 MB');
    expect(formatMB(1000)).toBe('0.1 MB');
    expect(formatProgressMB(13002342, 60542518)).toBe('12.4 / 57.7 MB');
    expect(formatProgressMB(0, 60542518)).toBe('0.0 / 57.7 MB');
    expect(formatProgressMB(99999999, 60542518)).toBe('57.7 / 57.7 MB');
  });

  it('ความเร็ว MB/s · KB/s · ไม่รู้ = ว่าง', () => {
    expect(formatSpeed(2.1 * MB)).toBe('2.1 MB/s');
    expect(formatSpeed(850 * 1024)).toBe('850 KB/s');
    expect(formatSpeed(0)).toBe('0 KB/s');
    expect(formatSpeed(null)).toBe('');
    expect(formatSpeed(NaN)).toBe('');
  });

  it('เวลาที่เหลือเป็นภาษาไทย', () => {
    expect(formatEta(18)).toBe('เหลือประมาณ 20 วินาที');
    expect(formatEta(20)).toBe('เหลือประมาณ 20 วินาที');
    expect(formatEta(3)).toBe('อีกไม่กี่วินาที');
    expect(formatEta(58)).toBe('เหลือประมาณ 1 นาที');
    expect(formatEta(125)).toBe('เหลือประมาณ 3 นาที');
    expect(formatEta(4000)).toBe('เหลือมากกว่า 1 ชั่วโมง');
    expect(formatEta(null)).toBe('กำลังคำนวณเวลาที่เหลือ');
    expect(formatEta(-1)).toBe('กำลังคำนวณเวลาที่เหลือ');
  });

  it('เปอร์เซ็นต์ปัดลง (100 เฉพาะเมื่อครบจริง)', () => {
    expect(progressPercent(999, 1000)).toBe(99);
    expect(progressPercent(1000, 1000)).toBe(100);
    expect(progressPercent(5000, 1000)).toBe(100);
    expect(progressPercent(10, 0)).toBe(0);
    expect(progressFraction(250, 1000)).toBe(0.25);
    expect(progressFraction(-1, 1000)).toBe(0);
  });

  it('วันที่ไทย (พ.ศ.) ตามที่ server เขียน', () => {
    expect(formatThaiDate('2026-10-05T10:00:00+07:00')).toBe('5 ต.ค. 2569');
    expect(formatThaiDate('2026-01-31')).toBe('31 ม.ค. 2569');
    expect(formatThaiDate('nope')).toBe('');
    expect(formatThaiDate(null)).toBe('');
  });
});

describe('ความเร็วเฉลี่ยลื่น (ตัวเลขไม่กระโดด)', () => {
  it('ตัวอย่างถี่เกินไปยังไม่คิด · คิดแล้วเฉลี่ยแบบ EMA', () => {
    let m = startSpeedMeter(0, 0);
    m = nextSpeedMeter(m, 100_000, SPEED_SAMPLE_MIN_MS - 1);
    expect(m.bps).toBeNull();
    m = nextSpeedMeter(m, 1_000_000, 1000);
    expect(m.bps).toBe(1_000_000);
    m = nextSpeedMeter(m, 4_000_000, 2000); // ทันที 3 MB/s → EMA 0.3
    expect(m.bps).toBeCloseTo(1_000_000 + 0.3 * (3_000_000 - 1_000_000));
    expect(m.samples).toBe(2);
  });

  it('ไบต์ลดลง (เริ่มใหม่) / เวลาย้อน = เริ่มวัดใหม่', () => {
    const m = nextSpeedMeter(startSpeedMeter(0, 0), 5_000_000, 1000);
    expect(nextSpeedMeter(m, 10, 2000)).toEqual(startSpeedMeter(10, 2000));
    expect(nextSpeedMeter(m, 6_000_000, 500)).toEqual(startSpeedMeter(6_000_000, 500));
    expect(nextSpeedMeter(null, 7, 9)).toEqual(startSpeedMeter(7, 9));
  });

  it('เวลาที่เหลือจากความเร็วเฉลี่ย', () => {
    expect(etaSeconds(40 * MB, 60 * MB, 2 * MB)).toBe(10);
    expect(etaSeconds(60 * MB, 60 * MB, null)).toBe(0);
    expect(etaSeconds(10, 100, null)).toBeNull();
    expect(etaSeconds(10, 100, 0)).toBeNull();
  });
});

describe('ไฟล์ในเครื่อง', () => {
  const latest: AppUpdateLatest = {
    version: '3.388.0',
    versionCode: 45,
    size: 100,
    md5: MD5,
    sha256: null,
    downloadUrl: 'https://main.thaiprompt.online/a.apk',
    publishedAt: null,
    notes: [],
  };

  it('ชื่อไฟล์ตัดอักขระแปลก', () => {
    expect(apkFileName('3.388.0')).toBe('ThaiPrompt-APP-3.388.0.apk');
    expect(apkFileName('3.388/../x')).toBe('ThaiPrompt-APP-3.388_.._x.apk');
  });

  it('ข้อมูลประกอบต้องตรง build จึงต่อไฟล์เดิมได้', () => {
    const side = parseSidecar(JSON.stringify({ version: '3.388.0', versionCode: 45, md5: MD5, size: 100 }));
    expect(sidecarMatches(side, latest)).toBe(true);
    expect(sidecarMatches(side, { ...latest, md5: 'f'.repeat(32) })).toBe(false);
    expect(sidecarMatches(side, { ...latest, versionCode: 46 })).toBe(false);
    expect(sidecarMatches(parseSidecar('bad'), latest)).toBe(false);
  });

  it('เก็บกวาด: ลบ APK ที่ติดตั้งไปแล้ว/เก่า/ไม่มีข้อมูลประกอบ · เก็บเวอร์ชันล่าสุดและไฟล์ที่กำลังใช้', () => {
    const names = [
      'ThaiPrompt-APP-3.386.0.apk',
      'ThaiPrompt-APP-3.386.0.apk.json',
      'ThaiPrompt-APP-3.387.0.apk',
      'ThaiPrompt-APP-3.387.0.apk.json',
      'ThaiPrompt-APP-3.388.0.apk',
      'ThaiPrompt-APP-3.388.0.apk.json',
      'orphan.apk',
      'ThaiPrompt-APP-3.380.0.apk.json',
      'junk.tmp',
    ];
    const meta = (versionCode: number) => ({ version: '', versionCode, md5: MD5, size: 1 });
    const sidecars = {
      'ThaiPrompt-APP-3.386.0.apk': meta(43),
      'ThaiPrompt-APP-3.387.0.apk': meta(44),
      'ThaiPrompt-APP-3.388.0.apk': meta(45),
      'orphan.apk': null,
    };
    const doomed = planHousekeeping(names, sidecars, 44, 45, null).sort();
    expect(doomed).toEqual(
      [
        'ThaiPrompt-APP-3.386.0.apk',
        'ThaiPrompt-APP-3.386.0.apk.json',
        'ThaiPrompt-APP-3.387.0.apk',
        'ThaiPrompt-APP-3.387.0.apk.json',
        'orphan.apk',
        'ThaiPrompt-APP-3.380.0.apk.json',
        'junk.tmp',
      ].sort()
    );
    // ไฟล์ที่กำลังใช้ไม่ถูกแตะ แม้จะดูเก่า
    expect(planHousekeeping(names, sidecars, 44, 45, 'ThaiPrompt-APP-3.387.0.apk')).not.toContain('ThaiPrompt-APP-3.387.0.apk');
    // มีเวอร์ชันใหม่กว่า (46) → ไฟล์ 45 ที่ค้างไว้ไม่ใช้แล้ว
    expect(planHousekeeping(names, sidecars, 44, 46, null)).toContain('ThaiPrompt-APP-3.388.0.apk');
    // ไม่รู้เวอร์ชันล่าสุด → ลบเฉพาะที่ ≤ ที่ติดตั้ง
    expect(planHousekeeping(names, sidecars, 44, null, null)).not.toContain('ThaiPrompt-APP-3.388.0.apk');
  });
});
