/**
 * ทดสอบ store ของระบบอัปเดตแอป (สถานะ ตรวจ → ดาวน์โหลด → หยุด/ต่อ → ตรวจไฟล์ → ติดตั้ง)
 * ใช้ native/API/ที่เก็บค่าปลอมทั้งหมด — ไม่แตะไฟล์หรือเครือข่ายจริง
 *
 * รัน: npx jest services/appUpdate/__tests__/store.test.ts
 */

import { beforeEach, describe, expect, it, jest } from '@jest/globals';
import type { ApiResult } from '@/services/api/client';
import { createAppUpdateStore, SNOOZE_STORAGE_KEY, updateStage, type KeyValueStorage } from '../store';
import type { AppUpdateNative, DownloadHandle, DownloadOutcome } from '../native';
import { makeSnooze, UPDATE_MESSAGES } from '../core';

const API = 'https://main.thaiprompt.online/api/v1';
const DIR = 'file:///cache/app-update/';
const MD5 = '0123456789abcdef0123456789abcdef';
const SIZE = 1000;
const APK = `${DIR}ThaiPrompt-APP-3.388.0.apk`;
const SIDE = `${APK}.json`;

const latestRaw = (over: Record<string, unknown> = {}) => ({
  version: '3.388.0',
  version_code: 45,
  size: SIZE,
  md5: MD5,
  sha256: null,
  download_url: 'https://main.thaiprompt.online/storage/app-downloads/ThaiPrompt-APP-3.388.0.apk?h=1',
  published_at: '2026-10-05T10:00:00+07:00',
  notes: ['ข้อใหม่'],
  ...over,
});

const ok = (data: unknown): ApiResult<unknown> => ({ success: true, data, message: '', status: 200 });
const updateData = (over: Record<string, unknown> = {}, latestOver: Record<string, unknown> = {}) => ({
  platform: 'android',
  current_build: 44,
  update_available: true,
  required: false,
  min_supported_build: 0,
  latest: latestRaw(latestOver),
  ...over,
});

/** รอให้ promise ที่ค้างทำงานจนนิ่ง */
const flush = async () => {
  for (let i = 0; i < 6; i++) await new Promise((resolve) => setTimeout(resolve, 0));
};

// =====================================================
// native ปลอม
// =====================================================

interface FakeFile {
  size: number;
  md5: string | null;
  text?: string;
}

class FakeDownload {
  resolve: ((v: DownloadOutcome | null) => void) | null = null;
  reject: ((e: Error) => void) | null = null;
  settled = false;
  constructor(
    public url: string,
    public uri: string,
    public onProgress: (written: number, expected: number) => void,
    public offset: number,
    private files: Map<string, FakeFile>
  ) {}

  handle: DownloadHandle = {
    start: () =>
      new Promise<DownloadOutcome | null>((resolve, reject) => {
        this.resolve = resolve;
        this.reject = reject;
        if (this.offset === 0) this.files.set(this.uri, { size: 0, md5: null });
      }),
    pause: async () => this.settle(null),
    cancel: async () => this.settle(null),
  };

  /** เขียนไฟล์ถึง totalWritten ไบต์ + ส่ง event */
  progress(totalWritten: number, expected: number = SIZE) {
    this.files.set(this.uri, { size: totalWritten, md5: null });
    this.onProgress(totalWritten, expected);
  }

  finish(status: number = this.offset > 0 ? 206 : 200, md5: string = MD5) {
    const f = this.files.get(this.uri);
    if (f) f.md5 = md5;
    this.settle({ status });
  }

  failNetwork() {
    if (this.settled) return;
    this.settled = true;
    this.reject?.(new Error('socket closed'));
  }

  private settle(v: DownloadOutcome | null) {
    if (this.settled) return;
    this.settled = true;
    this.resolve?.(v);
  }
}

const makeNative = (over: Partial<AppUpdateNative> = {}) => {
  const files = new Map<string, FakeFile>();
  const downloads: FakeDownload[] = [];
  const native: AppUpdateNative = {
    supported: true,
    nativeBuildVersion: '44',
    nativeVersionName: '3.387.0',
    updateDir: () => DIR,
    ensureDir: async () => {},
    listDir: async (dir) => Array.from(files.keys()).filter((k) => k.startsWith(dir)).map((k) => k.slice(dir.length)),
    fileSize: async (uri) => (files.has(uri) ? files.get(uri)!.size : -1),
    fileDigest: async (uri) => (files.has(uri) ? { size: files.get(uri)!.size, md5: files.get(uri)!.md5 } : null),
    readText: async (uri) => files.get(uri)?.text ?? null,
    writeText: async (uri, text) => {
      files.set(uri, { size: text.length, md5: null, text });
    },
    deleteFile: async (uri) => {
      files.delete(uri);
    },
    freeBytes: async () => 10 * 1024 * 1024 * 1024,
    createDownload: (url, uri, onProgress, offset = 0) => {
      const d = new FakeDownload(url, uri, onProgress, offset, files);
      downloads.push(d);
      return d.handle;
    },
    launchInstaller: jest.fn(async () => {}),
    openInstallSettings: jest.fn(async () => {}),
    ...over,
  };
  return { native, files, downloads };
};

const memoryStorage = (): KeyValueStorage & { data: Map<string, string> } => {
  const data = new Map<string, string>();
  return {
    data,
    getItem: async (k) => data.get(k) ?? null,
    setItem: async (k, v) => {
      data.set(k, v);
    },
  };
};

const setup = (
  opts: {
    response?: ApiResult<unknown>;
    native?: Partial<AppUpdateNative>;
    storage?: ReturnType<typeof memoryStorage>;
    now?: () => number;
  } = {}
) => {
  const { native, files, downloads } = makeNative(opts.native);
  const storage = opts.storage ?? memoryStorage();
  const fetchUpdate = jest.fn(async (_build: number) => opts.response ?? ok(updateData()));
  const store = createAppUpdateStore({
    native,
    fetchUpdate,
    storage,
    apiBaseUrl: API,
    fallbackBuild: 44,
    fallbackVersion: '3.387.0',
    now: opts.now,
    sleep: async () => {},
    verifyMinMs: 0,
  });
  return { store, native, files, downloads, storage, fetchUpdate };
};

const stageOf = (store: ReturnType<typeof createAppUpdateStore>) => updateStage(store.getState());

// =====================================================
// ตรวจเวอร์ชัน
// =====================================================

describe('ตรวจเวอร์ชัน', () => {
  it('เวอร์ชันล่าสุดแล้ว → up_to_date ไม่มีหน้าต่าง', async () => {
    const { store } = setup({ response: ok(updateData({ update_available: false }, { version_code: 44 })) });
    await store.getState().check();
    expect(stageOf(store)).toBe('up_to_date');
    expect(store.getState().promptVersionCode).toBeNull();
  });

  it('ยังไม่มีไฟล์เผยแพร่ (latest = null) → ไม่มีอัปเดต', async () => {
    const { store } = setup({ response: ok(updateData({ latest: null, update_available: false })) });
    await store.getState().check();
    expect(stageOf(store)).toBe('up_to_date');
    expect(store.getState().info?.latest).toBeNull();
  });

  it('ส่ง versionCode ที่ติดตั้งจาก native ไปให้ server · native อ่านไม่ได้ = ใช้ค่าสำรอง', async () => {
    const a = setup({ native: { nativeBuildVersion: '45' }, response: ok(updateData({}, { version_code: 45 })) });
    await a.store.getState().check();
    expect(a.fetchUpdate).toHaveBeenCalledWith(45);
    expect(a.store.getState().info?.updateAvailable).toBe(false);

    const b = setup({ native: { nativeBuildVersion: null, nativeVersionName: null } });
    await b.store.getState().check();
    expect(b.fetchUpdate).toHaveBeenCalledWith(44);
    expect(b.store.getState().installedVersion).toBe('3.387.0');
  });

  it('มีอัปเดตทั่วไป → ขึ้นหน้าต่าง · "ไว้ทีหลัง" เลื่อนเวอร์ชันนี้ 24 ชม. · เวอร์ชันใหม่กว่าขึ้นทันที', async () => {
    let now = 1_800_000_000_000;
    const storage = memoryStorage();
    const first = setup({ storage, now: () => now });
    await first.store.getState().check();
    expect(stageOf(first.store)).toBe('available');
    expect(first.store.getState().promptVersionCode).toBe(45);

    await first.store.getState().dismissPrompt('later');
    expect(first.store.getState().promptVersionCode).toBeNull();
    expect(JSON.parse(storage.data.get(SNOOZE_STORAGE_KEY)!)).toEqual(makeSnooze(45, now));

    // เปิดแอปใหม่ภายใน 24 ชม. → เวอร์ชันเดิมไม่ขึ้น
    now += 60 * 60 * 1000;
    const second = setup({ storage, now: () => now });
    await second.store.getState().check();
    expect(second.store.getState().promptVersionCode).toBeNull();

    // มีเวอร์ชันใหม่กว่า → ขึ้นทันที
    const third = setup({ storage, now: () => now, response: ok(updateData({}, { version_code: 46, version: '3.389.0' })) });
    await third.store.getState().check();
    expect(third.store.getState().promptVersionCode).toBe(46);

    // ครบ 24 ชม. → เวอร์ชันเดิมขึ้นอีก (ไม่ถาวร)
    now += 24 * 60 * 60 * 1000;
    const fourth = setup({ storage, now: () => now });
    await fourth.store.getState().check();
    expect(fourth.store.getState().promptVersionCode).toBe(45);
  });

  it('"อัปเดตเลย" → ไม่ขึ้นซ้ำในรอบการใช้งานนี้ แต่ไม่บันทึกการเลื่อน', async () => {
    const storage = memoryStorage();
    const { store } = setup({ storage });
    await store.getState().check();
    await store.getState().dismissPrompt('update');
    await store.getState().check();
    expect(store.getState().promptVersionCode).toBeNull();
    expect(storage.data.has(SNOOZE_STORAGE_KEY)).toBe(false);
  });

  it('บังคับอัปเดต → required และขึ้นแม้เคยกดไว้ทีหลัง', async () => {
    const storage = memoryStorage();
    storage.data.set(SNOOZE_STORAGE_KEY, JSON.stringify(makeSnooze(45, Date.now())));
    const { store } = setup({ storage, response: ok(updateData({ required: true, min_supported_build: 45 })) });
    await store.getState().check();
    expect(store.getState().info?.required).toBe(true);
    expect(store.getState().promptVersionCode).toBe(45);
  });

  it('ตรวจไม่สำเร็จ: อัตโนมัติไม่มีอะไรแสดง · ผู้ใช้สั่งเอง = check_error ภาษาไทย', async () => {
    const fail: ApiResult<unknown> = { success: false, code: 'NETWORK_ERROR', status: 0, message: 'เชื่อมต่อไม่ได้ ตรวจสอบอินเทอร์เน็ตแล้วลองใหม่นะ' };
    const { store } = setup({ response: fail });
    expect(await store.getState().check()).toBeNull();
    expect(store.getState().promptVersionCode).toBeNull();
    await store.getState().check({ manual: true });
    expect(stageOf(store)).toBe('check_error');
    expect(store.getState().checkError).toBe('เชื่อมต่อไม่ได้ ตรวจสอบอินเทอร์เน็ตแล้วลองใหม่นะ');

    const server = setup({ response: { success: false, code: 'SERVER_ERROR', status: 500, message: 'x' } });
    await server.store.getState().check({ manual: true });
    expect(server.store.getState().checkError).toBe(UPDATE_MESSAGES.checkFailed);
  });

  it('fetch โยน error = เงียบ ไม่ทำแอปล้ม', async () => {
    const { store } = setup();
    const broken = createAppUpdateStore({
      native: makeNative().native,
      fetchUpdate: async () => {
        throw new Error('boom');
      },
      storage: memoryStorage(),
      apiBaseUrl: API,
      fallbackBuild: 44,
      fallbackVersion: '3.387.0',
    });
    await expect(broken.getState().check()).resolves.toBeNull();
    expect(broken.getState().checking).toBe(false);
    expect(store.getState().checking).toBe(false);
  });

  it('ตรวจซ้อนกัน → เรียก server ครั้งเดียว', async () => {
    const { store, fetchUpdate } = setup();
    await Promise.all([store.getState().check(), store.getState().check(), store.getState().check({ manual: true })]);
    expect(fetchUpdate).toHaveBeenCalledTimes(1);
  });

  it('iOS/เว็บ → ไม่ทำอะไร', async () => {
    const { store, fetchUpdate } = setup({ native: { supported: false } });
    expect(await store.getState().check()).toBeNull();
    expect(fetchUpdate).not.toHaveBeenCalled();
    expect(stageOf(store)).toBe('unsupported');
  });
});

// =====================================================
// ดาวน์โหลด
// =====================================================

describe('ดาวน์โหลด → ตรวจไฟล์ → พร้อมติดตั้ง', () => {
  let ctx: ReturnType<typeof setup>;

  beforeEach(async () => {
    ctx = setup();
    await ctx.store.getState().check();
  });

  it('ทางปกติ: ดาวน์โหลด ความคืบหน้า ตรวจ md5 แล้วพร้อมติดตั้ง', async () => {
    const { store, downloads, files } = ctx;
    void store.getState().startDownload();
    expect(store.getState().phase).toBe('downloading');
    await flush();
    expect(downloads).toHaveLength(1);
    expect(downloads[0].offset).toBe(0);
    expect(JSON.parse(files.get(SIDE)!.text!)).toMatchObject({ versionCode: 45, md5: MD5, size: SIZE });

    downloads[0].progress(400);
    expect(store.getState().written).toBe(400);
    downloads[0].progress(SIZE);
    downloads[0].finish(200);
    await flush();
    expect(stageOf(store)).toBe('ready');
    expect(store.getState().written).toBe(SIZE);
  });

  it('กดเริ่มรัวๆ → เริ่มดาวน์โหลดรอบเดียว', async () => {
    const { store, downloads } = ctx;
    void store.getState().startDownload();
    void store.getState().startDownload();
    void store.getState().startDownload();
    await flush();
    void store.getState().startDownload();
    await flush();
    expect(downloads).toHaveLength(1);
  });

  it('หยุดชั่วคราว → ต่อจากขนาดไฟล์จริง (Range) → พร้อมติดตั้ง', async () => {
    const { store, downloads } = ctx;
    void store.getState().startDownload();
    await flush();
    downloads[0].progress(300);
    await store.getState().pauseDownload();
    expect(store.getState().phase).toBe('paused');
    // event ที่มาช้าหลังหยุด ต้องไม่ทำให้สถานะเพี้ยน
    downloads[0].onProgress(350, SIZE);
    expect(store.getState().written).toBe(300);

    void store.getState().resumeDownload();
    void store.getState().resumeDownload();
    await flush();
    expect(downloads).toHaveLength(2);
    expect(downloads[1].offset).toBe(300);
    downloads[1].progress(SIZE);
    downloads[1].finish(206);
    await flush();
    expect(stageOf(store)).toBe('ready');
  });

  it('เน็ตหลุดกลางทาง → error ต่อได้ → "ดาวน์โหลดต่อ" จากไฟล์เดิม', async () => {
    const { store, downloads } = ctx;
    void store.getState().startDownload();
    await flush();
    downloads[0].progress(600);
    downloads[0].failNetwork();
    await flush();
    expect(stageOf(store)).toBe('error');
    expect(store.getState().error).toEqual({ kind: 'network', message: UPDATE_MESSAGES.network, canResume: true });
    expect(store.getState().written).toBe(600);

    void store.getState().resumeDownload();
    await flush();
    expect(downloads[1].offset).toBe(600);
    downloads[1].progress(SIZE);
    downloads[1].finish(206);
    await flush();
    expect(stageOf(store)).toBe('ready');
  });

  it('ต่อไม่ได้ (server ไม่รับ Range ตอบ 200) → ลบแล้วดาวน์โหลดใหม่ตั้งแต่ต้นเอง', async () => {
    const { store, downloads } = ctx;
    void store.getState().startDownload();
    await flush();
    downloads[0].progress(500);
    await store.getState().pauseDownload();
    void store.getState().resumeDownload();
    await flush();
    expect(downloads[1].offset).toBe(500);
    downloads[1].finish(200);
    await flush();
    expect(downloads).toHaveLength(3);
    expect(downloads[2].offset).toBe(0);
    downloads[2].progress(SIZE);
    downloads[2].finish(200);
    await flush();
    expect(stageOf(store)).toBe('ready');
  });

  it('server ส่งทั้งไฟล์ซ้ำตอนต่อ (ไบต์เกินขนาด) → ยกเลิกรอบนั้น เริ่มใหม่ตั้งแต่ต้น', async () => {
    const { store, downloads } = ctx;
    void store.getState().startDownload();
    await flush();
    downloads[0].progress(500);
    await store.getState().pauseDownload();
    void store.getState().resumeDownload();
    await flush();
    downloads[1].onProgress(600, 500 + SIZE);
    await flush();
    expect(downloads).toHaveLength(3);
    expect(downloads[2].offset).toBe(0);
    expect(store.getState().phase).toBe('downloading');
  });

  it('ไบต์เกินขนาดที่ประกาศ (ดาวน์โหลดใหม่) → หยุด ลบไฟล์ แจ้งไฟล์เสีย', async () => {
    const { store, downloads, files } = ctx;
    void store.getState().startDownload();
    await flush();
    downloads[0].progress(SIZE + 1, SIZE + 1);
    await flush();
    expect(store.getState().error?.kind).toBe('corrupt');
    expect(store.getState().error?.message).toBe('ไฟล์เสียระหว่างดาวน์โหลด ดาวน์โหลดใหม่');
    expect(files.has(APK)).toBe(false);
  });

  it('md5 ไม่ตรง → ลบไฟล์ แจ้ง "ไฟล์เสียระหว่างดาวน์โหลด ดาวน์โหลดใหม่" → ดาวน์โหลดใหม่ได้', async () => {
    const { store, downloads, files } = ctx;
    void store.getState().startDownload();
    await flush();
    downloads[0].progress(SIZE);
    downloads[0].finish(200, 'f'.repeat(32));
    await flush();
    expect(store.getState().error).toEqual({ kind: 'corrupt', message: UPDATE_MESSAGES.corrupt, canResume: false });
    expect(files.has(APK)).toBe(false);
    expect(files.has(SIDE)).toBe(false);

    void store.getState().startDownload();
    await flush();
    expect(downloads).toHaveLength(2);
    expect(downloads[1].offset).toBe(0);
  });

  it('ขนาดไม่ตรง → ไฟล์เสีย', async () => {
    const { store, downloads } = ctx;
    void store.getState().startDownload();
    await flush();
    downloads[0].progress(SIZE - 10);
    downloads[0].finish(200);
    await flush();
    expect(store.getState().error?.kind).toBe('corrupt');
  });

  it('server ตอบ 404 / 500 → ข้อความไทยตามกรณี ไฟล์ error ถูกลบ', async () => {
    const { store, downloads, files } = ctx;
    void store.getState().startDownload();
    await flush();
    downloads[0].finish(404);
    await flush();
    expect(store.getState().error?.kind).toBe('not_found');
    expect(files.has(APK)).toBe(false);

    void store.getState().startDownload();
    await flush();
    downloads[1].finish(500);
    await flush();
    expect(store.getState().error).toEqual({ kind: 'server', message: UPDATE_MESSAGES.server, canResume: false });
  });

  it('ยกเลิกกลางทาง → กลับสู่เริ่มต้น ลบไฟล์ event ที่มาช้าไม่มีผล', async () => {
    const { store, downloads, files } = ctx;
    void store.getState().startDownload();
    await flush();
    downloads[0].progress(700);
    await store.getState().cancelDownload();
    expect(store.getState().phase).toBe('idle');
    expect(stageOf(store)).toBe('available');
    expect(files.has(APK)).toBe(false);
    expect(files.has(SIDE)).toBe(false);
    downloads[0].onProgress(800, SIZE);
    downloads[0].finish(200);
    await flush();
    expect(store.getState().phase).toBe('idle');
    expect(store.getState().written).toBe(0);
  });

  it('พื้นที่ไม่พอ → แจ้งเตือนก่อนเริ่ม ไม่ดาวน์โหลด', async () => {
    const low = setup({ native: { freeBytes: async () => 100 } });
    await low.store.getState().check();
    await low.store.getState().startDownload();
    expect(low.store.getState().error?.kind).toBe('storage');
    expect(low.store.getState().error?.message).toContain('พื้นที่ในเครื่องไม่พอ');
    expect(low.downloads).toHaveLength(0);
  });

  it('เคยดาวน์โหลดครบแล้ว (ไฟล์ + ข้อมูลประกอบตรง build) → ข้ามไปตรวจไฟล์เลย', async () => {
    const { store, downloads, files } = ctx;
    files.set(APK, { size: SIZE, md5: MD5 });
    files.set(SIDE, { size: 1, md5: null, text: JSON.stringify({ version: '3.388.0', versionCode: 45, md5: MD5, size: SIZE }) });
    await store.getState().startDownload();
    expect(downloads).toHaveLength(0);
    expect(stageOf(store)).toBe('ready');
  });

  it('ไฟล์ครึ่งเดียวจากครั้งก่อน (แอปถูกปิดกลางทาง) → ต่อจากเดิม · ถ้าเป็นของ build อื่น → ทิ้ง', async () => {
    const { store, downloads, files } = ctx;
    files.set(APK, { size: 400, md5: null });
    files.set(SIDE, { size: 1, md5: null, text: JSON.stringify({ version: '3.388.0', versionCode: 45, md5: MD5, size: SIZE }) });
    void store.getState().startDownload();
    await flush();
    expect(downloads[0].offset).toBe(400);

    const other = setup();
    await other.store.getState().check();
    other.files.set(APK, { size: 400, md5: null });
    other.files.set(SIDE, { size: 1, md5: null, text: JSON.stringify({ version: '3.388.0', versionCode: 45, md5: 'e'.repeat(32), size: SIZE }) });
    void other.store.getState().startDownload();
    await flush();
    expect(other.downloads[0].offset).toBe(0);
  });
});

// =====================================================
// ติดตั้ง
// =====================================================

describe('ติดตั้ง', () => {
  const readyStore = async (native: Partial<AppUpdateNative> = {}) => {
    const ctx = setup({ native });
    await ctx.store.getState().check();
    void ctx.store.getState().startDownload();
    await flush();
    ctx.downloads[0].progress(SIZE);
    ctx.downloads[0].finish(200);
    await flush();
    expect(stageOf(ctx.store)).toBe('ready');
    return ctx;
  };

  it('เปิดตัวติดตั้ง → กลับมา (ยกเลิก) ยังอยู่ "พร้อมติดตั้ง" กดใหม่ได้', async () => {
    const { store, native } = await readyStore();
    await store.getState().install();
    expect(native.launchInstaller).toHaveBeenCalledWith(APK);
    expect(store.getState().phase).toBe('ready');
    expect(store.getState().installReturned).toBe(true);
    await store.getState().install();
    expect(native.launchInstaller).toHaveBeenCalledTimes(2);
  });

  it('กดติดตั้งรัวๆ → เปิดตัวติดตั้งครั้งเดียว', async () => {
    let release: () => void = () => {};
    const { store, native } = await readyStore({
      launchInstaller: jest.fn(() => new Promise<void>((resolve) => (release = resolve))),
    });
    void store.getState().install();
    void store.getState().install();
    await flush();
    expect(native.launchInstaller).toHaveBeenCalledTimes(1);
    expect(store.getState().installing).toBe(true);
    release();
    await flush();
    expect(store.getState().installing).toBe(false);
  });

  it('เปิดตัวติดตั้งไม่ได้ → คำแนะนำ "ติดตั้งแอปที่ไม่รู้จัก"', async () => {
    const { store } = await readyStore({
      launchInstaller: jest.fn(async () => {
        throw new Error('ActivityNotFound');
      }),
    });
    await store.getState().install();
    expect(store.getState().phase).toBe('ready');
    expect(store.getState().installError).toBe(UPDATE_MESSAGES.install);
    await store.getState().openInstallSettings();
  });

  it('ไฟล์หายระหว่างรอติดตั้ง (ระบบล้าง cache) → แจ้งให้ดาวน์โหลดใหม่', async () => {
    const { store, files, native } = await readyStore();
    files.delete(APK);
    await store.getState().install();
    expect(native.launchInstaller).not.toHaveBeenCalled();
    expect(store.getState().error).toEqual({ kind: 'missing', message: UPDATE_MESSAGES.missing, canResume: false });
  });
});

// =====================================================
// เก็บกวาด
// =====================================================

describe('เก็บกวาดไฟล์เก่า', () => {
  it('ลบ APK ที่ version_code ≤ ที่ติดตั้ง และไฟล์ไม่มีข้อมูลประกอบ · เก็บเวอร์ชันล่าสุด', async () => {
    const { store, files } = setup();
    const side = (versionCode: number) => ({
      size: 1,
      md5: null,
      text: JSON.stringify({ version: 'x', versionCode, md5: MD5, size: SIZE }),
    });
    files.set(`${DIR}ThaiPrompt-APP-3.387.0.apk`, { size: SIZE, md5: MD5 });
    files.set(`${DIR}ThaiPrompt-APP-3.387.0.apk.json`, side(44));
    files.set(`${DIR}stray.apk`, { size: 5, md5: null });
    files.set(APK, { size: 300, md5: null });
    files.set(SIDE, side(45));
    await store.getState().check();
    await flush();
    expect(Array.from(files.keys()).sort()).toEqual([APK, SIDE].sort());
  });
});
