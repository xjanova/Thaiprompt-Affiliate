/**
 * ระบบอัปเดตแอปในตัว (Android) — จุด import เดียว
 *
 *   import { useAppUpdateStore, isAppUpdateSupported } from '@/services/appUpdate';
 *
 * ไฟล์มาจากเซิร์ฟเวอร์ Thai Prompt โดยตรง (โดเมนเดียวกับ API) · ตรวจขนาด + md5 ก่อนติดตั้ง
 * Android ปฏิเสธ APK ที่เซ็นด้วยกุญแจอื่นอยู่แล้ว md5 มีไว้กันไฟล์เสียระหว่างทาง
 */

import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL } from '@/constants';
import { APP_FEATURE_BUILD, APP_INFO } from '@/config/appConfig';
import { fetchAppUpdate } from './api';
import { nativeAppUpdate } from './native';
import { createAppUpdateStore } from './store';

export const useAppUpdateStore = createAppUpdateStore({
  native: nativeAppUpdate,
  fetchUpdate: fetchAppUpdate,
  storage: AsyncStorage,
  apiBaseUrl: API_BASE_URL,
  fallbackBuild: APP_FEATURE_BUILD,
  fallbackVersion: APP_INFO.VERSION,
});

/** เครื่องนี้ใช้ระบบอัปเดตในตัวได้หรือไม่ (Android เท่านั้น) */
export const isAppUpdateSupported = (): boolean => nativeAppUpdate.supported;

/** path ของหน้าอัปเดต */
export const APP_UPDATE_ROUTE = '/app-update';

export * from './core';
export { updateStage, SNOOZE_STORAGE_KEY } from './store';
export type { AppUpdateState, DownloadPhase, DownloadError, DownloadErrorKind, UpdateStage } from './store';
