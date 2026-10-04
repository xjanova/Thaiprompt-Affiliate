/**
 * ระบบอัปเดตแอปในตัว — store กลาง (zustand)
 *
 * อยู่ระดับโมดูล → ดาวน์โหลดที่กำลังวิ่งอยู่ไม่หายเมื่อออกจากหน้าอัปเดต กลับมาเห็นความคืบหน้าต่อทันที
 * สร้างผ่าน createAppUpdateStore(deps) เพื่อให้ทดสอบได้ (ส่ง native/API/ที่เก็บค่าปลอม) — ตัวจริงอยู่ใน index.ts
 *
 * สถานะดาวน์โหลด
 *   idle → downloading ⇄ paused → verifying → ready
 *                ↘ error (เน็ตหลุด = ต่อได้ · ไฟล์เสีย/เซิร์ฟเวอร์ = เริ่มใหม่)
 *
 * กันกดซ้ำ/สลับสถานะชนกัน
 *   - busy: ล็อกระหว่างเตรียมเริ่ม/หยุด/ต่อ (กดรัวไม่เริ่มสองรอบ)
 *   - runId: ทุกครั้งที่เริ่ม/หยุด/ยกเลิก เลขรอบเปลี่ยน → callback/ผลของรอบเก่าถูกทิ้ง
 */

import { create } from 'zustand';
import type { ApiResult } from '@/services/api/client';
import {
  apkFileName,
  etaSeconds,
  isSnoozed,
  isTrustedDownloadUrl,
  makeSnooze,
  nextSpeedMeter,
  normalizeUpdateResponse,
  parseSidecar,
  parseSnooze,
  planHousekeeping,
  resolveInstalledBuild,
  sidecarFor,
  sidecarMatches,
  sidecarUriFor,
  startSpeedMeter,
  UPDATE_MESSAGES,
  type ApkSidecar,
  type AppUpdateInfo,
  type AppUpdateLatest,
  type SpeedMeter,
} from './core';
import type { AppUpdateNative, DownloadHandle, DownloadOutcome } from './native';

// =====================================================
// ชนิดข้อมูล
// =====================================================

export type DownloadPhase = 'idle' | 'downloading' | 'paused' | 'verifying' | 'ready' | 'error';

export type DownloadErrorKind =
  | 'network'
  | 'corrupt'
  | 'server'
  | 'not_found'
  | 'storage'
  | 'untrusted'
  | 'missing'
  | 'unknown';

export interface DownloadError {
  kind: DownloadErrorKind;
  /** ข้อความไทยพร้อมแสดง */
  message: string;
  /** ต่อจากไฟล์เดิมได้ (เน็ตหลุด) */
  canResume: boolean;
}

export interface AppUpdateState {
  /** Android เท่านั้น (iOS/เว็บ = ซ่อนทุกอย่าง) */
  supported: boolean;
  installedBuild: number;
  installedVersion: string;

  // ---------- ตรวจเวอร์ชัน ----------
  info: AppUpdateInfo | null;
  checking: boolean;
  checkError: string | null;
  lastSuccessAt: number;
  lastAttemptAt: number;
  /** version_code ที่ควรขึ้น bottom sheet ชวนอัปเดต (null = ไม่ต้องขึ้น) */
  promptVersionCode: number | null;

  // ---------- ดาวน์โหลด / ติดตั้ง ----------
  phase: DownloadPhase;
  /** เวอร์ชันที่กำลังดาวน์โหลด (คงไว้แม้ผลตรวจรอบใหม่เปลี่ยน) */
  target: AppUpdateLatest | null;
  fileUri: string | null;
  written: number;
  speedBps: number | null;
  etaSeconds: number | null;
  error: DownloadError | null;
  installing: boolean;
  /** กลับจากตัวติดตั้งแล้วอย่างน้อยหนึ่งครั้ง (ผู้ใช้ยกเลิก/เครื่องไม่อนุญาต) */
  installReturned: boolean;
  /** เปิดตัวติดตั้งไม่ได้ */
  installError: string | null;

  /** ตรวจเวอร์ชัน — manual = ผู้ใช้สั่งเอง (แสดง error) · อัตโนมัติ = เงียบเมื่อพลาด */
  check: (options?: { manual?: boolean }) => Promise<AppUpdateInfo | null>;
  /** ปิด bottom sheet: later = เลื่อนเวอร์ชันนี้ 24 ชม. · update = ไปหน้าอัปเดต */
  dismissPrompt: (action: 'later' | 'update') => Promise<void>;
  startDownload: () => Promise<void>;
  pauseDownload: () => Promise<void>;
  resumeDownload: () => Promise<void>;
  cancelDownload: () => Promise<void>;
  install: () => Promise<void>;
  openInstallSettings: () => Promise<void>;
  /** ลบไฟล์อัปเดตเก่า/ที่ติดตั้งไปแล้ว */
  housekeep: () => Promise<void>;
}

export interface KeyValueStorage {
  getItem: (key: string) => Promise<string | null>;
  setItem: (key: string, value: string) => Promise<void>;
}

export interface AppUpdateDeps {
  native: AppUpdateNative;
  fetchUpdate: (installedBuild: number) => Promise<ApiResult<unknown>>;
  storage: KeyValueStorage;
  /** API base URL — ลิงก์ดาวน์โหลดต้องโดเมนเดียวกัน */
  apiBaseUrl: string;
  /** versionCode สำรองเมื่ออ่านจาก native ไม่ได้ (APP_FEATURE_BUILD) */
  fallbackBuild: number;
  /** ชื่อเวอร์ชันสำรอง (APP_INFO.VERSION) */
  fallbackVersion: string;
  now?: () => number;
  sleep?: (ms: number) => Promise<void>;
  /** เวลาขั้นต่ำที่แสดง "กำลังตรวจไฟล์" (กันกระพริบ) */
  verifyMinMs?: number;
  /** เวลาขั้นต่ำที่แสดง "กำลังตรวจสอบ" เมื่อผู้ใช้สั่งตรวจเอง */
  manualCheckMinMs?: number;
  /** พื้นที่เผื่อนอกเหนือขนาดไฟล์ */
  spareBytes?: number;
}

/** ที่เก็บค่า "ไว้ทีหลัง" ใน AsyncStorage */
export const SNOOZE_STORAGE_KEY = 'app_update_snooze_v1';

/** ขั้นที่หน้าจอแสดง */
export type UpdateStage =
  | 'unsupported'
  | 'checking'
  | 'check_error'
  | 'up_to_date'
  | 'available'
  | 'downloading'
  | 'paused'
  | 'verifying'
  | 'ready'
  | 'error';

/** สรุปขั้นที่ต้องแสดงจากสถานะ store */
export const updateStage = (
  s: Pick<AppUpdateState, 'supported' | 'phase' | 'info' | 'checking' | 'checkError'>
): UpdateStage => {
  if (!s.supported) return 'unsupported';
  if (s.phase !== 'idle') return s.phase;
  if (s.info?.updateAvailable && s.info.latest) return 'available';
  if (s.checking) return 'checking';
  if (s.checkError) return 'check_error';
  if (s.info) return 'up_to_date';
  return 'checking';
};

type NextStep = { kind: 'verify' } | { kind: 'download'; offset: number } | null;

const IDLE_DOWNLOAD = {
  phase: 'idle' as DownloadPhase,
  target: null,
  fileUri: null,
  written: 0,
  speedBps: null,
  etaSeconds: null,
  error: null,
  installing: false,
  installReturned: false,
  installError: null,
};

// =====================================================
// สร้าง store
// =====================================================

export const createAppUpdateStore = (deps: AppUpdateDeps) => {
  const { native } = deps;
  const now = deps.now ?? (() => Date.now());
  const sleep = deps.sleep ?? ((ms: number) => new Promise<void>((resolve) => setTimeout(resolve, ms)));
  const verifyMinMs = deps.verifyMinMs ?? 700;
  const manualCheckMinMs = deps.manualCheckMinMs ?? 600;
  const spareBytes = deps.spareBytes ?? 20 * 1024 * 1024;
  const installedBuild = resolveInstalledBuild(native.nativeBuildVersion, deps.fallbackBuild);
  const installedVersion = (native.nativeVersionName || '').trim() || deps.fallbackVersion;

  let task: DownloadHandle | null = null;
  let runId = 0;
  let meter: SpeedMeter | null = null;
  let busy = false;
  let checkInFlight: Promise<AppUpdateInfo | null> | null = null;
  /** version_code ที่ผู้ใช้ตอบ bottom sheet แล้วในรอบการใช้งานนี้ */
  const handledPrompts = new Set<number>();

  return create<AppUpdateState>((set, get) => {
    const fail = (kind: DownloadErrorKind, message: string, canResume: boolean = false) => {
      set({ phase: 'error', error: { kind, message, canResume }, speedBps: null, etaSeconds: null });
    };

    const removeFiles = async (uri: string | null) => {
      if (!uri) return;
      await native.deleteFile(uri);
      await native.deleteFile(sidecarUriFor(uri));
    };

    // ---------- ตรวจไฟล์ (ขนาด + md5 ฝั่ง native) ----------
    const verify = async (myRun: number) => {
      const { target, fileUri } = get();
      if (!target || !fileUri || myRun !== runId) return;
      set({ phase: 'verifying', written: target.size, speedBps: null, etaSeconds: null, error: null });
      const startedAt = now();
      const digest = await native.fileDigest(fileUri);
      const wait = verifyMinMs - (now() - startedAt);
      if (wait > 0) await sleep(wait);
      if (myRun !== runId) return;
      const ok = !!digest && digest.size === target.size && digest.md5 === target.md5;
      if (!ok) {
        await removeFiles(fileUri);
        if (myRun !== runId) return;
        set({ written: 0 });
        fail('corrupt', UPDATE_MESSAGES.corrupt);
        return;
      }
      set({ phase: 'ready' });
    };

    // ---------- ความคืบหน้า ----------
    const onProgress = (myRun: number, offset: number, written: number, expected: number) => {
      if (myRun !== runId) return;
      const { target, phase } = get();
      if (!target || phase !== 'downloading') return;
      // ได้ไบต์เกินขนาดที่ประกาศ (หรือ server ส่งทั้งไฟล์มาซ้ำตอนต่อดาวน์โหลด) → ทิ้งรอบนี้
      if (written > target.size || (expected > 0 && expected > target.size)) {
        void overflow(myRun, offset);
        return;
      }
      meter = nextSpeedMeter(meter, written, now());
      const bps = meter.samples > 0 ? meter.bps : null;
      set({
        written,
        speedBps: bps,
        etaSeconds: meter.samples > 1 ? etaSeconds(written, target.size, bps) : null,
      });
    };

    const overflow = async (myRun: number, offset: number) => {
      if (myRun !== runId) return;
      const next = ++runId;
      const handle = task;
      task = null;
      const { fileUri } = get();
      await handle?.cancel().catch(() => {});
      if (fileUri) await native.deleteFile(fileUri);
      if (next !== runId) return;
      if (offset > 0) {
        // ต่อจากเดิมไม่ได้ → เริ่มใหม่ตั้งแต่ต้นหนึ่งครั้ง
        await runDownload(next, 0, false);
        return;
      }
      set({ written: 0 });
      fail('corrupt', UPDATE_MESSAGES.corrupt);
    };

    // ---------- ดาวน์โหลดหนึ่งรอบ ----------
    const runDownload = async (myRun: number, offset: number, allowFreshFallback: boolean): Promise<void> => {
      const { target, fileUri } = get();
      if (!target || !fileUri || myRun !== runId) return;
      meter = startSpeedMeter(offset, now());
      set({ phase: 'downloading', written: offset, speedBps: null, etaSeconds: null, error: null });

      let handle: DownloadHandle;
      try {
        handle = native.createDownload(
          target.downloadUrl,
          fileUri,
          (written, expected) => onProgress(myRun, offset, written, expected),
          offset
        );
      } catch {
        fail('unknown', UPDATE_MESSAGES.unknown);
        return;
      }
      task = handle;

      let outcome: DownloadOutcome | null;
      try {
        outcome = await handle.start();
      } catch {
        // ถูกหยุด/ยกเลิก/เริ่มรอบใหม่ระหว่างนั้น = ไม่ใช่ error
        if (myRun !== runId) return;
        task = null;
        fail('network', UPDATE_MESSAGES.network, true);
        return;
      }
      if (myRun !== runId) return;
      task = null;

      if (!outcome) {
        set({ phase: 'paused', speedBps: null, etaSeconds: null });
        return;
      }

      const ok = offset > 0 ? outcome.status === 206 : outcome.status >= 200 && outcome.status < 300 && outcome.status !== 206;
      if (!ok) {
        // ไฟล์มีเนื้อหา error/ต่อท้ายผิด → ลบทิ้งเสมอ
        await native.deleteFile(fileUri);
        if (myRun !== runId) return;
        if (offset > 0 && allowFreshFallback) {
          const next = ++runId;
          await runDownload(next, 0, false);
          return;
        }
        set({ written: 0 });
        if (outcome.status === 404 || outcome.status === 410) fail('not_found', UPDATE_MESSAGES.notFound);
        else fail('server', UPDATE_MESSAGES.server);
        return;
      }

      await verify(myRun);
    };

    const proceed = async (myRun: number, next: NextStep, allowFreshFallback: boolean) => {
      if (!next || myRun !== runId) return;
      if (next.kind === 'verify') await verify(myRun);
      else await runDownload(myRun, next.offset, allowFreshFallback);
    };

    // ---------- bottom sheet ชวนอัปเดต ----------
    const evaluatePrompt = async (info: AppUpdateInfo) => {
      const code = info.updateAvailable && info.latest ? info.latest.versionCode : null;
      if (code === null) {
        set({ promptVersionCode: null });
        return;
      }
      if (info.required) {
        set({ promptVersionCode: code });
        return;
      }
      if (handledPrompts.has(code)) {
        set({ promptVersionCode: null });
        return;
      }
      let snoozed = false;
      try {
        snoozed = isSnoozed(parseSnooze(await deps.storage.getItem(SNOOZE_STORAGE_KEY)), code, now());
      } catch {
        snoozed = false;
      }
      set({ promptVersionCode: snoozed ? null : code });
    };

    return {
      supported: native.supported,
      installedBuild,
      installedVersion,

      info: null,
      checking: false,
      checkError: null,
      lastSuccessAt: 0,
      lastAttemptAt: 0,
      promptVersionCode: null,

      ...IDLE_DOWNLOAD,

      check: async ({ manual = false } = {}) => {
        if (!native.supported) return null;
        if (checkInFlight) {
          if (manual) set({ checkError: null });
          return checkInFlight;
        }
        const startedAt = now();
        set({ checking: true, checkError: manual ? null : get().checkError, lastAttemptAt: startedAt });
        checkInFlight = (async () => {
          let res: ApiResult<unknown>;
          try {
            res = await deps.fetchUpdate(installedBuild);
          } catch {
            res = { success: false, code: 'UNKNOWN_ERROR', status: 0, message: UPDATE_MESSAGES.checkFailed };
          }
          // ผู้ใช้สั่งเอง: แสดง "กำลังตรวจสอบ" อย่างน้อยครู่หนึ่ง (เน็ตเร็วมากก็ไม่กระพริบ)
          if (manual) {
            const wait = manualCheckMinMs - (now() - startedAt);
            if (wait > 0) await sleep(wait);
          }
          if (!res.success) {
            const offline = res.code === 'NETWORK_ERROR' || res.code === 'TIMEOUT';
            set({ checking: false, checkError: offline && res.message ? res.message : UPDATE_MESSAGES.checkFailed });
            return null;
          }
          const info = normalizeUpdateResponse(res.data, installedBuild, deps.apiBaseUrl);
          set({ checking: false, info, checkError: null, lastSuccessAt: now() });
          await evaluatePrompt(info);
          get().housekeep().catch(() => {});
          return info;
        })();
        try {
          return await checkInFlight;
        } finally {
          checkInFlight = null;
        }
      },

      dismissPrompt: async (action) => {
        const code = get().promptVersionCode;
        if (code === null) return;
        handledPrompts.add(code);
        set({ promptVersionCode: null });
        if (action !== 'later') return;
        try {
          await deps.storage.setItem(SNOOZE_STORAGE_KEY, JSON.stringify(makeSnooze(code, now())));
        } catch {
          // บันทึกไม่ได้ = รอบหน้าอาจขึ้นอีก ไม่กระทบการใช้งาน
        }
      },

      startDownload: async () => {
        if (!native.supported || busy) return;
        const s = get();
        if (s.phase !== 'idle' && s.phase !== 'error') return;
        const latest = s.info?.latest;
        if (!s.info?.updateAvailable || !latest) return;

        busy = true;
        const myRun = ++runId;
        let next: NextStep = null;
        try {
          if (!isTrustedDownloadUrl(latest.downloadUrl, deps.apiBaseUrl)) {
            set({ ...IDLE_DOWNLOAD, target: latest });
            fail('untrusted', UPDATE_MESSAGES.untrusted);
            return;
          }
          const dir = native.updateDir();
          if (!dir) {
            set({ ...IDLE_DOWNLOAD, target: latest });
            fail('unknown', UPDATE_MESSAGES.unknown);
            return;
          }
          const fileUri = `${dir}${apkFileName(latest.version)}`;
          set({ ...IDLE_DOWNLOAD, phase: 'downloading', target: latest, fileUri });

          await native.ensureDir(dir);
          const sidecar = parseSidecar(await native.readText(sidecarUriFor(fileUri)));
          let existing = await native.fileSize(fileUri);
          if (myRun !== runId) return;

          // ไฟล์เดิมเป็นของ build อื่น/ใหญ่เกิน → ทิ้ง
          const same = sidecarMatches(sidecar, latest);
          if (existing >= 0 && (!same || existing > latest.size)) {
            await native.deleteFile(fileUri);
            existing = -1;
          }
          if (same && existing === latest.size) {
            // เคยดาวน์โหลดครบแล้ว (เช่น ยกเลิกตัวติดตั้งแล้วปิดแอป) → ตรวจไฟล์เลย ไม่ต้องโหลดซ้ำ
            next = { kind: 'verify' };
          } else {
            const offset = same && existing > 0 ? existing : 0;
            const free = await native.freeBytes();
            if (myRun !== runId) return;
            const need = latest.size - offset + spareBytes;
            if (free !== null && free < need) {
              fail('storage', UPDATE_MESSAGES.storage(need));
              return;
            }
            await native.writeText(sidecarUriFor(fileUri), JSON.stringify(sidecarFor(latest)));
            if (myRun !== runId) return;
            next = { kind: 'download', offset };
          }
        } catch {
          if (myRun === runId) fail('unknown', UPDATE_MESSAGES.unknown);
          return;
        } finally {
          busy = false;
        }
        await proceed(myRun, next, true);
      },

      pauseDownload: async () => {
        if (busy || get().phase !== 'downloading') return;
        busy = true;
        runId += 1;
        const handle = task;
        task = null;
        set({ phase: 'paused', speedBps: null, etaSeconds: null });
        try {
          await handle?.pause();
        } catch {
          await handle?.cancel().catch(() => {});
        } finally {
          busy = false;
        }
      },

      resumeDownload: async () => {
        if (busy) return;
        const s = get();
        const resumable = s.phase === 'paused' || (s.phase === 'error' && !!s.error?.canResume);
        if (!resumable || !s.target || !s.fileUri) return;

        busy = true;
        const myRun = ++runId;
        let next: NextStep = null;
        try {
          set({ phase: 'downloading', error: null, speedBps: null, etaSeconds: null });
          // ต่อจากขนาดไฟล์จริงบนเครื่องเสมอ (แม่นกว่าเลขจาก event สุดท้าย)
          const size = await native.fileSize(s.fileUri);
          if (myRun !== runId) return;
          if (size === s.target.size) {
            next = { kind: 'verify' };
          } else if (size > 0 && size < s.target.size) {
            next = { kind: 'download', offset: size };
          } else {
            if (size > s.target.size) await native.deleteFile(s.fileUri);
            next = { kind: 'download', offset: 0 };
          }
        } finally {
          busy = false;
        }
        await proceed(myRun, next, true);
      },

      cancelDownload: async () => {
        const s = get();
        if (s.phase === 'idle') return;
        runId += 1;
        const handle = task;
        task = null;
        meter = null;
        set({ ...IDLE_DOWNLOAD });
        await handle?.cancel().catch(() => {});
        await removeFiles(s.fileUri);
      },

      install: async () => {
        const s = get();
        if (s.installing || s.phase !== 'ready' || !s.fileUri || !s.target) return;
        set({ installing: true, installError: null });
        try {
          // ไฟล์ใน cache อาจถูกระบบล้างระหว่างรอ
          const size = await native.fileSize(s.fileUri);
          if (size !== s.target.size) {
            await removeFiles(s.fileUri);
            set({ installing: false, written: 0 });
            fail('missing', UPDATE_MESSAGES.missing);
            return;
          }
          await native.launchInstaller(s.fileUri);
          // กลับมาที่แอป = ยังไม่ได้ติดตั้ง (ยกเลิก/เครื่องไม่อนุญาต) → อยู่ที่ "พร้อมติดตั้ง" ให้กดใหม่ได้
          set({ installing: false, installReturned: true });
        } catch {
          set({ installing: false, installError: UPDATE_MESSAGES.install });
        }
      },

      openInstallSettings: async () => {
        try {
          await native.openInstallSettings();
        } catch {
          // เปิดไม่ได้ก็ไม่เป็นไร ผู้ใช้เข้าไปตั้งเองได้
        }
      },

      housekeep: async () => {
        if (!native.supported) return;
        const dir = native.updateDir();
        if (!dir) return;
        const names = await native.listDir(dir);
        if (names.length === 0) return;
        const sidecars: Record<string, ApkSidecar | null> = {};
        for (const name of names) {
          if (name.endsWith('.apk')) sidecars[name] = parseSidecar(await native.readText(`${dir}${name}.json`));
        }
        const s = get();
        const activeName = s.phase !== 'idle' && s.fileUri?.startsWith(dir) ? s.fileUri.slice(dir.length) : null;
        const doomed = planHousekeeping(names, sidecars, installedBuild, s.info?.latest?.versionCode ?? null, activeName);
        for (const name of doomed) {
          const uri = `${dir}${name}`;
          const cur = get();
          // เริ่มดาวน์โหลดไฟล์นี้ระหว่างเก็บกวาด → ข้าม
          if (cur.phase !== 'idle' && cur.fileUri && (uri === cur.fileUri || uri === sidecarUriFor(cur.fileUri))) continue;
          await native.deleteFile(uri);
        }
      },
    };
  });
};

export type AppUpdateStore = ReturnType<typeof createAppUpdateStore>;
