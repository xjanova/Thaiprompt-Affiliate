/**
 * ระบบอัปเดตแอปในตัว — ตัวช่วยแบบฟังก์ชันล้วน (ไม่แตะ native) ทดสอบได้ด้วย jest
 *
 * สัญญากับ server: GET /app/update?platform=android&build=<versionCode ที่ติดตั้งอยู่>
 *   data = { platform, current_build, update_available, required, min_supported_build,
 *            latest: { version, version_code, size, md5, sha256, download_url, published_at, notes[] } | null }
 *
 * หลักการ
 *   - เทียบเวอร์ชันเองเสมอ (latest.version_code > ที่ติดตั้ง) — update_available ของ server เป็นแค่คำใบ้
 *   - ลิงก์ดาวน์โหลดต้องเป็นโดเมนเดียวกับ API (scheme + host + port ตรงกัน ห้ามมี user:pass@) ไม่งั้นถือว่าไม่มีอัปเดต
 *   - "ไว้ทีหลัง" เลื่อนเฉพาะ version_code นั้น 24 ชั่วโมง (ไม่ถาวร · เวอร์ชันใหม่กว่าขึ้นทันที)
 *   - ข้อความทุกอย่างที่ผู้ใช้เห็นเป็นภาษาไทย ไม่มีชื่อบริการภายนอก
 */

// =====================================================
// ค่าคงที่
// =====================================================

/** ตรวจอัตโนมัติซ้ำได้เมื่อห่างจากครั้งที่สำเร็จล่าสุดเกินนี้ (6 ชั่วโมง) */
export const AUTO_CHECK_INTERVAL_MS = 6 * 60 * 60 * 1000;
/** ตรวจอัตโนมัติไม่สำเร็จ → เว้นอย่างน้อยเท่านี้ก่อนลองใหม่ตอนกลับเข้าแอป */
export const AUTO_RETRY_AFTER_FAIL_MS = 15 * 60 * 1000;
/** "ไว้ทีหลัง" เลื่อนได้นานสุด 24 ชั่วโมง */
export const SNOOZE_MS = 24 * 60 * 60 * 1000;
/** bullet สูงสุดใน bottom sheet */
export const MAX_PROMPT_NOTES = 4;
/** bullet สูงสุดในหน้าอัปเดต */
export const MAX_NOTES = 10;
/** ความยาวสูงสุดต่อ bullet */
const MAX_NOTE_LENGTH = 160;
/** ขนาดไฟล์ที่ยอมรับ (กันค่าผิดปกติจาก server) */
const MAX_APK_BYTES = 500 * 1024 * 1024;
/** โฟลเดอร์ใน cacheDirectory */
export const UPDATE_DIR_NAME = 'app-update/';

const MB = 1024 * 1024;
const KB = 1024;

// =====================================================
// ชนิดข้อมูล
// =====================================================

export interface AppUpdateLatest {
  /** ชื่อเวอร์ชัน เช่น 3.388.0 */
  version: string;
  /** versionCode ของ Android */
  versionCode: number;
  /** ขนาดไฟล์ (ไบต์) > 0 เสมอ */
  size: number;
  /** md5 ตัวพิมพ์เล็ก 32 ตัว */
  md5: string;
  sha256: string | null;
  /** ผ่านการตรวจโดเมนแล้ว */
  downloadUrl: string;
  publishedAt: string | null;
  notes: string[];
}

export interface AppUpdateInfo {
  installedBuild: number;
  /** null = ยังไม่มีไฟล์ให้ดาวน์โหลด (ไม่ได้เผยแพร่/ปิดดาวน์โหลด/ข้อมูลไม่ครบ) */
  latest: AppUpdateLatest | null;
  updateAvailable: boolean;
  /** ต้องอัปเดตก่อนใช้งานต่อ (ที่ติดตั้งต่ำกว่า min_supported_build) */
  required: boolean;
  minSupportedBuild: number;
}

// =====================================================
// เวอร์ชันที่ติดตั้งอยู่
// =====================================================

/**
 * versionCode ที่ติดตั้งอยู่ — ใช้ค่าจาก native (Application.nativeBuildVersion) ถ้าเป็นจำนวนเต็มบวก
 * ไม่งั้นใช้ค่าสำรอง (APP_FEATURE_BUILD)
 *
 * @example resolveInstalledBuild('45', 44) // 45
 * @example resolveInstalledBuild(null, 44) // 44
 */
export const resolveInstalledBuild = (native: string | number | null | undefined, fallback: number): number => {
  const text = typeof native === 'number' ? String(native) : typeof native === 'string' ? native.trim() : '';
  if (/^\d{1,9}$/.test(text)) {
    const n = Number(text);
    if (Number.isSafeInteger(n) && n > 0) return n;
  }
  return fallback;
};

/** มีอัปเดตจริงหรือไม่ (เทียบเลข versionCode) */
export const isNewerBuild = (latestCode: number | null | undefined, installedBuild: number): boolean =>
  typeof latestCode === 'number' && Number.isSafeInteger(latestCode) && latestCode > installedBuild;

// =====================================================
// ตรวจลิงก์ดาวน์โหลด
// =====================================================

export interface ParsedHttpUrl {
  scheme: 'http' | 'https';
  host: string;
  port: number;
  hasUserInfo: boolean;
  path: string;
}

const URL_RE = /^([a-zA-Z][a-zA-Z0-9+.-]*):\/\/([^/?#]*)([^?#]*)(\?[^#]*)?(#.*)?$/;
/** ช่องว่าง / ตัวควบคุม / backslash — ไม่ยอมรับเลย (กันหลอก parser) */
const UNSAFE_URL_CHARS = /[\s\\\u0000-\u001f\u007f]/;
const HOST_RE = /^[a-z0-9.-]+$/;

/**
 * แยกส่วนของ URL แบบ http/https เอง (ไม่พึ่ง URL ของ runtime ที่ทำงานต่างกันในแต่ละเครื่อง)
 * คืน null ถ้าไม่ใช่ http(s) หรือรูปแบบผิด
 */
export const parseHttpUrl = (url: unknown): ParsedHttpUrl | null => {
  if (typeof url !== 'string' || url.length === 0 || url.length > 2048) return null;
  if (UNSAFE_URL_CHARS.test(url)) return null;
  const m = URL_RE.exec(url);
  if (!m) return null;
  const scheme = m[1].toLowerCase();
  if (scheme !== 'http' && scheme !== 'https') return null;

  let authority = m[2];
  const at = authority.lastIndexOf('@');
  const hasUserInfo = at >= 0;
  if (hasUserInfo) authority = authority.slice(at + 1);

  let host: string;
  let portText: string | undefined;
  if (authority.startsWith('[')) {
    const v6 = /^\[([0-9a-fA-F:.]+)\](?::(\d*))?$/.exec(authority);
    if (!v6) return null;
    host = `[${v6[1].toLowerCase()}]`;
    portText = v6[2];
  } else {
    const v4 = /^([^:]+)(?::(\d*))?$/.exec(authority);
    if (!v4) return null;
    host = v4[1].toLowerCase();
    portText = v4[2];
    if (!HOST_RE.test(host)) return null;
  }
  if (!host) return null;

  const defaultPort = scheme === 'https' ? 443 : 80;
  let port = defaultPort;
  if (portText !== undefined && portText !== '') {
    if (!/^\d{1,5}$/.test(portText)) return null;
    port = Number(portText);
    if (port < 1 || port > 65535) return null;
  }

  return { scheme, host, port, hasUserInfo, path: m[3] || '/' };
};

/**
 * ลิงก์ดาวน์โหลดเชื่อถือได้หรือไม่: scheme + host + port ต้องตรงกับ API base URL และไม่มี user:pass@
 *
 * @example isTrustedDownloadUrl('https://main.thaiprompt.online/storage/a.apk', 'https://main.thaiprompt.online/api/v1') // true
 * @example isTrustedDownloadUrl('http://main.thaiprompt.online/a.apk', 'https://main.thaiprompt.online/api/v1') // false
 */
export const isTrustedDownloadUrl = (url: unknown, apiBaseUrl: string): boolean => {
  const target = parseHttpUrl(url);
  const base = parseHttpUrl(apiBaseUrl);
  if (!target || !base) return false;
  if (target.hasUserInfo) return false;
  return target.scheme === base.scheme && target.host === base.host && target.port === base.port;
};

// =====================================================
// แปลงข้อมูลจาก server
// =====================================================

/** ตัดอีโมจิ/สัญลักษณ์ภาพออก (กติกาแอป: ไม่แสดงอีโมจิ) */
const stripPictographs = (text: string): string =>
  text
    .replace(/[\u{1F000}-\u{1FAFF}\u{2600}-\u{27BF}\u{2B00}-\u{2BFF}\u{FE0F}\u{200D}\u{20E3}]/gu, '')
    .replace(/\s{2,}/g, ' ')
    .trim();

/** แปลงรายการ "มีอะไรใหม่" ให้ปลอดภัย (ข้อความ · ไม่ว่าง · ไม่ยาวเกิน · ไม่เกิน MAX_NOTES) */
export const normalizeNotes = (raw: unknown): string[] => {
  if (!Array.isArray(raw)) return [];
  const out: string[] = [];
  for (const item of raw) {
    if (typeof item !== 'string') continue;
    let text = stripPictographs(item.replace(/^[\s\-•*·]+/, ''));
    if (!text) continue;
    if (text.length > MAX_NOTE_LENGTH) text = `${text.slice(0, MAX_NOTE_LENGTH - 1).trimEnd()}…`;
    out.push(text);
    if (out.length >= MAX_NOTES) break;
  }
  return out;
};

const toInt = (value: unknown): number | null => {
  const n = typeof value === 'number' ? value : typeof value === 'string' && value.trim() !== '' ? Number(value) : NaN;
  return Number.isSafeInteger(n) ? n : null;
};

/**
 * แปลง latest จาก server → AppUpdateLatest (ข้อมูลไม่ครบ/ลิงก์ไม่น่าเชื่อถือ = null)
 */
export const normalizeLatest = (raw: unknown, apiBaseUrl: string): AppUpdateLatest | null => {
  if (!raw || typeof raw !== 'object') return null;
  const r = raw as Record<string, unknown>;

  const version = typeof r.version === 'string' ? r.version.trim() : '';
  if (!/^[0-9A-Za-z][0-9A-Za-z.+_-]{0,31}$/.test(version)) return null;

  const versionCode = toInt(r.version_code);
  if (versionCode === null || versionCode <= 0) return null;

  const size = toInt(r.size);
  if (size === null || size <= 0 || size > MAX_APK_BYTES) return null;

  const md5 = typeof r.md5 === 'string' ? r.md5.trim().toLowerCase() : '';
  if (!/^[0-9a-f]{32}$/.test(md5)) return null;

  const sha256Raw = typeof r.sha256 === 'string' ? r.sha256.trim().toLowerCase() : '';
  const sha256 = /^[0-9a-f]{64}$/.test(sha256Raw) ? sha256Raw : null;

  const downloadUrl = typeof r.download_url === 'string' ? r.download_url.trim() : '';
  if (!isTrustedDownloadUrl(downloadUrl, apiBaseUrl)) return null;

  const publishedAt = typeof r.published_at === 'string' && r.published_at.trim() ? r.published_at.trim() : null;

  return { version, versionCode, size, md5, sha256, downloadUrl, publishedAt, notes: normalizeNotes(r.notes) };
};

/**
 * แปลงคำตอบ GET /app/update (ส่วน data) → AppUpdateInfo
 * - มีอัปเดต = latest ใช้ได้ และ version_code มากกว่าที่ติดตั้ง (คำนวณเอง ไม่เชื่อ update_available อย่างเดียว)
 * - บังคับอัปเดต = มีอัปเดต และ (server บอก required หรือ ที่ติดตั้งต่ำกว่า min_supported_build)
 *   ไม่มีไฟล์ให้ดาวน์โหลด = ไม่บังคับ (ไม่งั้นผู้ใช้ติดอยู่หน้าบังคับโดยไม่มีทางออก)
 */
export const normalizeUpdateResponse = (raw: unknown, installedBuild: number, apiBaseUrl: string): AppUpdateInfo => {
  const r = raw && typeof raw === 'object' ? (raw as Record<string, unknown>) : {};
  const latest = normalizeLatest(r.latest, apiBaseUrl);
  const minSupportedBuild = Math.max(0, toInt(r.min_supported_build) ?? 0);
  const updateAvailable = !!latest && isNewerBuild(latest.versionCode, installedBuild);
  const required = updateAvailable && (r.required === true || installedBuild < minSupportedBuild);
  return { installedBuild, latest, updateAvailable, required, minSupportedBuild };
};

// =====================================================
// "ไว้ทีหลัง" (เลื่อนเฉพาะเวอร์ชันนั้น 24 ชั่วโมง)
// =====================================================

export interface SnoozeRecord {
  versionCode: number;
  /** เวลาที่หมดการเลื่อน (ms) */
  until: number;
}

export const makeSnooze = (versionCode: number, now: number): SnoozeRecord => ({ versionCode, until: now + SNOOZE_MS });

/** อ่านค่าที่บันทึกไว้ (เสีย/ไม่มี = null) */
export const parseSnooze = (text: string | null | undefined): SnoozeRecord | null => {
  if (!text) return null;
  try {
    const v = JSON.parse(text) as Partial<SnoozeRecord>;
    const versionCode = toInt(v?.versionCode);
    const until = typeof v?.until === 'number' && Number.isFinite(v.until) ? v.until : null;
    if (versionCode === null || until === null) return null;
    return { versionCode, until };
  } catch {
    return null;
  }
};

/**
 * เวอร์ชันนี้ยังอยู่ในช่วง "ไว้ทีหลัง" หรือไม่
 * - ต้องเป็น version_code เดียวกัน (เวอร์ชันใหม่กว่า = ขึ้นทันที)
 * - หมดเวลาแล้ว = ไม่เลื่อน · ค่าที่ไกลเกิน 24 ชม. (นาฬิกาเครื่องถูกย้อน/ค่าถูกแก้) = ไม่เลื่อน
 */
export const isSnoozed = (record: SnoozeRecord | null | undefined, versionCode: number, now: number): boolean =>
  !!record && record.versionCode === versionCode && now < record.until && record.until - now <= SNOOZE_MS;

// =====================================================
// จังหวะตรวจอัตโนมัติ / หน้าที่ห้ามขึ้นหน้าต่าง
// =====================================================

/**
 * ควรตรวจอัตโนมัติตอนกลับเข้าแอปหรือยัง
 * @param lastSuccessAt เวลาตรวจสำเร็จล่าสุด (0 = ยังไม่เคย)
 * @param lastAttemptAt เวลาที่ลองตรวจล่าสุด (0 = ยังไม่เคย)
 */
export const shouldAutoCheck = (now: number, lastSuccessAt: number, lastAttemptAt: number): boolean => {
  const due = lastSuccessAt <= 0 || now < lastSuccessAt || now - lastSuccessAt >= AUTO_CHECK_INTERVAL_MS;
  const cooled = lastAttemptAt <= 0 || now < lastAttemptAt || now - lastAttemptAt >= AUTO_RETRY_AFTER_FAIL_MS;
  return due && cooled;
};

/**
 * หน้าที่ไม่ควรมีหน้าต่างอัปเดตเด้งทับ (กล้องยืนยันตัวตน · ชำระเงิน · สแกนส่งมอบ · หน้าอัปเดตเอง)
 * — รอผู้ใช้ไปหน้าอื่นก่อนค่อยแสดง
 */
export const PROMPT_DEFER_PREFIXES: readonly string[] = [
  '/app-update',
  '/ekyc',
  '/kyc',
  '/profile-photo',
  '/checkout',
  '/taladsod/checkout',
  '/order',
  '/taladsod/order',
  '/wallet-topup',
  '/wallet-withdraw',
  '/wallet-transfer',
  '/merchant/rider-pay',
  '/handover',
  '/rider-job-detail',
  '/intro-video',
  '/auth',
];

/** หน้านี้แสดงหน้าต่างอัปเดตได้หรือไม่ */
export const isCalmRoute = (pathname: string | null | undefined): boolean => {
  if (!pathname) return false;
  const path = pathname.split('?')[0];
  return !PROMPT_DEFER_PREFIXES.some((prefix) => path === prefix || path.startsWith(`${prefix}/`));
};

// =====================================================
// แสดงผล (ขนาด / ความเร็ว / เวลาที่เหลือ / วันที่)
// =====================================================

const oneDecimal = (value: number): string => {
  const rounded = Math.round(value * 10) / 10;
  return rounded.toFixed(1);
};

/** ไบต์ → "57.7 MB" */
export const formatMB = (bytes: number): string => {
  if (!Number.isFinite(bytes) || bytes <= 0) return '0 MB';
  return `${oneDecimal(Math.max(bytes / MB, 0.1))} MB`;
};

/** "12.4 / 57.7 MB" */
export const formatProgressMB = (written: number, total: number): string => {
  const w = Number.isFinite(written) && written > 0 ? written : 0;
  const t = Number.isFinite(total) && total > 0 ? total : 0;
  return `${oneDecimal(Math.min(w, t || w) / MB)} / ${oneDecimal(t / MB)} MB`;
};

/** ไบต์ต่อวินาที → "2.1 MB/s" · "850 KB/s" · ไม่รู้ = '' */
export const formatSpeed = (bytesPerSecond: number | null | undefined): string => {
  if (bytesPerSecond == null || !Number.isFinite(bytesPerSecond) || bytesPerSecond < 0) return '';
  if (bytesPerSecond >= MB) return `${oneDecimal(bytesPerSecond / MB)} MB/s`;
  return `${Math.max(0, Math.round(bytesPerSecond / KB))} KB/s`;
};

/** วินาทีที่เหลือ → ข้อความไทย */
export const formatEta = (seconds: number | null | undefined): string => {
  if (seconds == null || !Number.isFinite(seconds) || seconds < 0) return 'กำลังคำนวณเวลาที่เหลือ';
  if (seconds < 5) return 'อีกไม่กี่วินาที';
  if (seconds < 55) return `เหลือประมาณ ${Math.ceil(seconds / 5) * 5} วินาที`;
  if (seconds < 3600) return `เหลือประมาณ ${Math.max(1, Math.ceil(seconds / 60))} นาที`;
  return 'เหลือมากกว่า 1 ชั่วโมง';
};

/** เปอร์เซ็นต์ (ปัดลง — 100 เฉพาะเมื่อครบจริง) */
export const progressPercent = (written: number, total: number): number => {
  if (!Number.isFinite(written) || !Number.isFinite(total) || total <= 0 || written <= 0) return 0;
  return Math.max(0, Math.min(100, Math.floor((written / total) * 100)));
};

/** สัดส่วน 0–1 */
export const progressFraction = (written: number, total: number): number => {
  if (!Number.isFinite(written) || !Number.isFinite(total) || total <= 0 || written <= 0) return 0;
  return Math.max(0, Math.min(1, written / total));
};

const TH_MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

/** "2026-10-05T10:00:00+07:00" → "5 ต.ค. 2569" (อ่านวันที่ตามที่ server เขียน ไม่แปลงเขตเวลา) */
export const formatThaiDate = (iso: string | null | undefined): string => {
  if (!iso) return '';
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso);
  if (!m) return '';
  const month = Number(m[2]);
  const day = Number(m[3]);
  if (month < 1 || month > 12 || day < 1 || day > 31) return '';
  return `${day} ${TH_MONTHS[month - 1]} ${Number(m[1]) + 543}`;
};

// =====================================================
// วัดความเร็วแบบเฉลี่ยลื่น (EMA) — ตัวเลขไม่กระโดดตามจังหวะ event
// =====================================================

export interface SpeedMeter {
  lastBytes: number;
  lastAt: number;
  /** ไบต์ต่อวินาที (null = ยังวัดไม่ได้) */
  bps: number | null;
  samples: number;
}

/** ช่วงเวลาขั้นต่ำระหว่างตัวอย่าง (สะสมไว้ก่อนถ้ายังไม่ถึง) */
export const SPEED_SAMPLE_MIN_MS = 400;
/** น้ำหนักของตัวอย่างใหม่ */
export const SPEED_ALPHA = 0.3;

export const startSpeedMeter = (bytes: number, now: number): SpeedMeter => ({ lastBytes: bytes, lastAt: now, bps: null, samples: 0 });

/**
 * ใส่ตัวอย่างใหม่ → ความเร็วเฉลี่ยลื่น
 * - ไบต์ลดลง/เวลาย้อน = เริ่มวัดใหม่ (ดาวน์โหลดใหม่ตั้งแต่ต้น)
 * - ห่างจากตัวอย่างก่อนน้อยกว่า SPEED_SAMPLE_MIN_MS = ยังไม่คิด (กันตัวเลขแกว่ง)
 */
export const nextSpeedMeter = (meter: SpeedMeter | null, bytes: number, now: number, alpha: number = SPEED_ALPHA): SpeedMeter => {
  if (!meter || bytes < meter.lastBytes || now < meter.lastAt) return startSpeedMeter(bytes, now);
  const dt = now - meter.lastAt;
  if (dt < SPEED_SAMPLE_MIN_MS) return meter;
  const instant = ((bytes - meter.lastBytes) * 1000) / dt;
  const bps = meter.bps == null ? instant : meter.bps + alpha * (instant - meter.bps);
  return { lastBytes: bytes, lastAt: now, bps, samples: meter.samples + 1 };
};

/** เวลาที่เหลือ (วินาที) จากความเร็วเฉลี่ย — วัดไม่ได้ = null · ครบแล้ว = 0 */
export const etaSeconds = (written: number, total: number, bps: number | null | undefined): number | null => {
  if (total > 0 && written >= total) return 0;
  if (bps == null || !Number.isFinite(bps) || bps < 1 || total <= 0) return null;
  return (total - written) / bps;
};

// =====================================================
// ไฟล์ในเครื่อง
// =====================================================

/** ชื่อไฟล์ APK ของเวอร์ชัน (ตัดอักขระแปลกออก) */
export const apkFileName = (version: string): string => `ThaiPrompt-APP-${version.replace(/[^0-9A-Za-z._-]/g, '_')}.apk`;

/** ไฟล์ข้อมูลประกอบ (เก็บ version_code / md5 / size ของ APK ไฟล์นั้น) */
export const sidecarUriFor = (apkUri: string): string => `${apkUri}.json`;

export interface ApkSidecar {
  version: string;
  versionCode: number;
  md5: string;
  size: number;
}

export const sidecarFor = (latest: AppUpdateLatest): ApkSidecar => ({
  version: latest.version,
  versionCode: latest.versionCode,
  md5: latest.md5,
  size: latest.size,
});

export const parseSidecar = (text: string | null | undefined): ApkSidecar | null => {
  if (!text) return null;
  try {
    const v = JSON.parse(text) as Partial<ApkSidecar>;
    const versionCode = toInt(v?.versionCode);
    const size = toInt(v?.size);
    const md5 = typeof v?.md5 === 'string' ? v.md5.toLowerCase() : '';
    if (versionCode === null || size === null || !/^[0-9a-f]{32}$/.test(md5)) return null;
    return { version: typeof v.version === 'string' ? v.version : '', versionCode, md5, size };
  } catch {
    return null;
  }
};

/** ไฟล์ในเครื่องเป็นของ build เดียวกับ latest หรือไม่ (ต่อดาวน์โหลด/ใช้ซ้ำได้) */
export const sidecarMatches = (sidecar: ApkSidecar | null, latest: AppUpdateLatest): boolean =>
  !!sidecar && sidecar.versionCode === latest.versionCode && sidecar.md5 === latest.md5 && sidecar.size === latest.size;

/**
 * เลือกไฟล์ที่ควรลบในโฟลเดอร์อัปเดต
 * - APK ที่ version_code ≤ ที่ติดตั้งอยู่ (ติดตั้งไปแล้ว/เก่ากว่า) · ไม่มีข้อมูลประกอบ · ไม่ใช่เวอร์ชันล่าสุดที่รู้
 * - ไฟล์ข้อมูลประกอบที่ไม่มี APK คู่ · ไฟล์อื่นที่ไม่รู้จัก
 * - ไม่แตะไฟล์ที่กำลังใช้งาน (activeName และไฟล์ประกอบของมัน)
 *
 * @param names ชื่อไฟล์ในโฟลเดอร์
 * @param sidecars ข้อมูลประกอบของ APK แต่ละไฟล์ (key = ชื่อ APK)
 */
export const planHousekeeping = (
  names: readonly string[],
  sidecars: Readonly<Record<string, ApkSidecar | null>>,
  installedBuild: number,
  latestCode: number | null,
  activeName: string | null
): string[] => {
  const apks = new Set(names.filter((n) => n.endsWith('.apk')));
  const out: string[] = [];
  for (const name of names) {
    if (activeName && (name === activeName || name === `${activeName}.json`)) continue;
    if (name.endsWith('.apk')) {
      const meta = sidecars[name] ?? null;
      const stale =
        !meta || meta.versionCode <= installedBuild || (latestCode !== null && meta.versionCode !== latestCode);
      if (stale) {
        out.push(name);
        if (names.includes(`${name}.json`)) out.push(`${name}.json`);
      }
      continue;
    }
    if (name.endsWith('.apk.json')) {
      if (!apks.has(name.slice(0, -'.json'.length))) out.push(name);
      continue;
    }
    out.push(name);
  }
  return Array.from(new Set(out));
};

// =====================================================
// ข้อความแจ้งผู้ใช้
// =====================================================

export const UPDATE_MESSAGES = {
  checkFailed: 'ตรวจสอบเวอร์ชันไม่สำเร็จ ตรวจสอบอินเทอร์เน็ตแล้วลองใหม่อีกครั้ง',
  network: 'การเชื่อมต่อขาดหาย กด "ดาวน์โหลดต่อ" เพื่อทำต่อจากเดิม',
  corrupt: 'ไฟล์เสียระหว่างดาวน์โหลด ดาวน์โหลดใหม่',
  server: 'เซิร์ฟเวอร์ยังส่งไฟล์อัปเดตไม่ได้ ลองใหม่อีกครั้งในอีกสักครู่',
  notFound: 'ไม่พบไฟล์อัปเดตนี้แล้ว ตรวจสอบเวอร์ชันล่าสุดอีกครั้ง',
  untrusted: 'ลิงก์ไฟล์อัปเดตไม่ถูกต้อง ลองตรวจสอบอีกครั้งภายหลัง',
  missing: 'ไฟล์อัปเดตหายจากเครื่อง ดาวน์โหลดใหม่อีกครั้ง',
  unknown: 'ดาวน์โหลดไม่สำเร็จ ลองใหม่อีกครั้ง',
  install: 'เปิดตัวติดตั้งไม่สำเร็จ อนุญาต "ติดตั้งแอปที่ไม่รู้จัก" ให้ Thai Prompt APP แล้วกดติดตั้งอีกครั้ง',
  storage: (needBytes: number) =>
    `พื้นที่ในเครื่องไม่พอ ต้องว่างอย่างน้อย ${formatMB(needBytes)} ลบไฟล์ที่ไม่ใช้แล้วลองอีกครั้ง`,
} as const;
