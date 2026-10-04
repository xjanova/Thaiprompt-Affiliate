/**
 * ระบบอัปเดตแอปในตัว — ตัวห่อ native (ไฟล์ / ดาวน์โหลด / ตัวติดตั้ง Android)
 *
 * แยกไว้ไฟล์เดียวเพื่อให้ store ทดสอบด้วย jest ได้ (ส่งตัวปลอมแทนไฟล์นี้)
 * ทุกฟังก์ชันที่ "อ่าน/ลบ" เงียบเสมอ (ไม่ throw) · ดาวน์โหลด/ติดตั้งคืน error ให้ store ตัดสินใจ
 *
 * ทำไมใช้ expo-file-system/legacy: createDownloadResumable หยุด/ต่อได้ + getInfoAsync คำนวณ md5 ฝั่ง native
 * (ไม่ต้องอ่านไฟล์ 60 MB เข้า JS)
 *
 * พฤติกรรมฝั่ง Android ที่ store ต้องรู้ (expo-file-system legacy)
 *   - pauseAsync = ตัดการเชื่อมต่อ → downloadAsync ที่รออยู่คืน null
 *   - ต่อดาวน์โหลด = ส่ง Range: bytes=<ขนาดไฟล์>- แล้วเขียนต่อท้ายไฟล์เดิม (server ตอบ 206)
 *   - HTTP error (404/500) ไม่ throw — คืน status พร้อมเนื้อหา error ในไฟล์ (ต้องเช็ค status เอง)
 *   - เน็ตหลุด = throw (ไฟล์ครึ่งเดียวยังอยู่ ต่อได้)
 */

import { Linking, Platform } from 'react-native';
import * as Application from 'expo-application';
import * as FileSystem from 'expo-file-system/legacy';
import * as IntentLauncher from 'expo-intent-launcher';
import { APP_FEATURE_BUILD } from '@/config/appConfig';
import { UPDATE_DIR_NAME } from './core';

/** ผลดาวน์โหลด (null = ถูกหยุด/ยกเลิก) */
export interface DownloadOutcome {
  status: number;
}

export interface DownloadHandle {
  /** เริ่ม (หรือเริ่มต่อจาก resumeOffset) — throw เมื่อเน็ตหลุด */
  start: () => Promise<DownloadOutcome | null>;
  pause: () => Promise<void>;
  cancel: () => Promise<void>;
}

export interface FileDigest {
  size: number;
  md5: string | null;
}

export interface AppUpdateNative {
  /** เครื่องนี้ใช้ระบบอัปเดตในตัวได้ (Android เท่านั้น) */
  supported: boolean;
  /** versionCode จาก native (string) */
  nativeBuildVersion: string | null;
  /** ชื่อเวอร์ชันจาก native เช่น 3.387.0 */
  nativeVersionName: string | null;
  /** โฟลเดอร์เก็บไฟล์อัปเดต (ลงท้าย /) */
  updateDir: () => string | null;
  ensureDir: (dir: string) => Promise<void>;
  listDir: (dir: string) => Promise<string[]>;
  /** ขนาดไฟล์ (-1 = ไม่มีไฟล์) */
  fileSize: (uri: string) => Promise<number>;
  /** ขนาด + md5 คำนวณฝั่ง native (null = ไม่มีไฟล์/อ่านไม่ได้) */
  fileDigest: (uri: string) => Promise<FileDigest | null>;
  readText: (uri: string) => Promise<string | null>;
  writeText: (uri: string, text: string) => Promise<void>;
  /** ลบแบบเงียบ */
  deleteFile: (uri: string) => Promise<void>;
  /** พื้นที่ว่าง (null = ไม่ทราบ) */
  freeBytes: () => Promise<number | null>;
  createDownload: (
    url: string,
    uri: string,
    onProgress: (written: number, expected: number) => void,
    resumeOffset?: number
  ) => DownloadHandle;
  /** เปิดตัวติดตั้งของระบบ — resolve เมื่อผู้ใช้กลับจากตัวติดตั้ง (ยกเลิก/ติดตั้งไม่ผ่าน) · throw เมื่อเปิดไม่ได้ */
  launchInstaller: (uri: string) => Promise<void>;
  /** เปิดหน้าตั้งค่า "ติดตั้งแอปที่ไม่รู้จัก" ของแอปนี้ */
  openInstallSettings: () => Promise<void>;
}

/** Intent.FLAG_GRANT_READ_URI_PERMISSION */
const FLAG_GRANT_READ_URI_PERMISSION = 1;
const APK_MIME = 'application/vnd.android.package-archive';

export const nativeAppUpdate: AppUpdateNative = {
  supported: Platform.OS === 'android',
  nativeBuildVersion: Application.nativeBuildVersion ?? null,
  nativeVersionName: Application.nativeApplicationVersion ?? null,

  updateDir: () => (FileSystem.cacheDirectory ? `${FileSystem.cacheDirectory}${UPDATE_DIR_NAME}` : null),

  ensureDir: async (dir) => {
    const info = await FileSystem.getInfoAsync(dir);
    if (!info.exists) {
      await FileSystem.makeDirectoryAsync(dir, { intermediates: true });
    }
  },

  listDir: async (dir) => {
    try {
      const info = await FileSystem.getInfoAsync(dir);
      if (!info.exists || !info.isDirectory) return [];
      return await FileSystem.readDirectoryAsync(dir);
    } catch {
      return [];
    }
  },

  fileSize: async (uri) => {
    try {
      const info = await FileSystem.getInfoAsync(uri);
      return info.exists && !info.isDirectory ? Math.round(info.size ?? 0) : -1;
    } catch {
      return -1;
    }
  },

  fileDigest: async (uri) => {
    try {
      const info = await FileSystem.getInfoAsync(uri, { md5: true });
      if (!info.exists || info.isDirectory) return null;
      return { size: Math.round(info.size ?? 0), md5: info.md5 ? info.md5.toLowerCase() : null };
    } catch {
      return null;
    }
  },

  readText: async (uri) => {
    try {
      const info = await FileSystem.getInfoAsync(uri);
      if (!info.exists) return null;
      return await FileSystem.readAsStringAsync(uri);
    } catch {
      return null;
    }
  },

  writeText: async (uri, text) => {
    await FileSystem.writeAsStringAsync(uri, text);
  },

  deleteFile: async (uri) => {
    try {
      await FileSystem.deleteAsync(uri, { idempotent: true });
    } catch {
      // ลบไม่ได้ = ครั้งหน้าลบใหม่ตอนเก็บกวาด
    }
  },

  freeBytes: async () => {
    try {
      const free = await FileSystem.getFreeDiskStorageAsync();
      return Number.isFinite(free) && free > 0 ? free : null;
    } catch {
      return null;
    }
  },

  createDownload: (url, uri, onProgress, resumeOffset) => {
    const resumable = FileSystem.createDownloadResumable(
      url,
      uri,
      {
        // identity = ไม่ให้ตัวเชื่อมต่อขอแบบบีบอัด (ไบต์ที่นับได้ต้องตรงกับขนาดไฟล์จริง)
        headers: { 'Accept-Encoding': 'identity', 'X-App-Build': String(APP_FEATURE_BUILD) },
      },
      (progress) => onProgress(progress.totalBytesWritten, progress.totalBytesExpectedToWrite),
      resumeOffset && resumeOffset > 0 ? String(Math.floor(resumeOffset)) : undefined
    );
    return {
      start: async () => {
        const result = await resumable.downloadAsync();
        return result ? { status: result.status } : null;
      },
      pause: async () => {
        await resumable.pauseAsync();
      },
      cancel: async () => {
        await resumable.cancelAsync();
      },
    };
  },

  launchInstaller: async (uri) => {
    const contentUri = await FileSystem.getContentUriAsync(uri);
    await IntentLauncher.startActivityAsync('android.intent.action.VIEW', {
      data: contentUri,
      flags: FLAG_GRANT_READ_URI_PERMISSION,
      type: APK_MIME,
    });
  },

  openInstallSettings: async () => {
    const pkg = Application.applicationId;
    try {
      await IntentLauncher.startActivityAsync(IntentLauncher.ActivityAction.MANAGE_UNKNOWN_APP_SOURCES, {
        data: pkg ? `package:${pkg}` : undefined,
      });
      return;
    } catch {
      // บางยี่ห้อไม่รับแบบระบุแพ็กเกจ → เปิดหน้ารวม
    }
    try {
      await IntentLauncher.startActivityAsync(IntentLauncher.ActivityAction.MANAGE_UNKNOWN_APP_SOURCES);
      return;
    } catch {
      // ไม่มีหน้ารวม → หน้าตั้งค่าของแอป
    }
    await Linking.openSettings().catch(() => {});
  },
};
