/**
 * เปิดแอปจันทรา (ดูดวงเชิงลึก) จากแอปนี้
 *
 * ทำไมเปิด "แอป" ไม่ใช่เว็บจันทรา.online:
 *   จันทราขายดูดวงแบบจ่ายเงิน — นโยบาย Google Play ห้ามแอปพาผู้ใช้ไปจ่ายเงินซื้อของดิจิทัลนอก Play
 *   แอปจันทราเก็บเงินผ่าน Play Billing ของตัวเอง จึงส่งต่อไปที่แอปได้โดยไม่ผิดนโยบาย (เจ้าของเคาะ 2026-09-26)
 *
 * ลำดับ: เปิดแอปจันทราที่ติดตั้งไว้ → หน้า Play Store ของจันทรา (แอป Play) → หน้า Play Store บนเว็บ
 * Android เท่านั้น — iOS ยังไม่มีแอปจันทรา (การ์ดซ่อนตัวเองบน iOS)
 *
 * ⚠️ openApplication ต้องประกาศ <queries><package android:name="com.xjanova.juntra"/></queries>
 *    (ใส่ให้แล้วใน app.config.js → withJuntraPackageQuery) ไม่งั้น Android 11+ มองไม่เห็นแอปจันทรา
 */

import { Linking, Platform } from 'react-native';
import { requireOptionalNativeModule } from 'expo';

/** แพ็กเกจแอปจันทราบน Google Play */
export const JUNTRA_ANDROID_PACKAGE = 'com.xjanova.juntra';

const PLAY_STORE_APP_URL = `market://details?id=${JUNTRA_ANDROID_PACKAGE}`;
const PLAY_STORE_WEB_URL = `https://play.google.com/store/apps/details?id=${JUNTRA_ANDROID_PACKAGE}`;

/** ผลการเปิด: เปิดแอปได้ / ไปหน้าร้านค้า / เปิดไม่ได้เลย */
export type JuntraOpenResult = 'app' | 'store' | 'unavailable';

/** แสดงการ์ดจันทราได้ไหม (Android เท่านั้น) */
export const canOfferJuntra = (): boolean => Platform.OS === 'android';

/**
 * เปิดแอปจันทรา ถ้าไม่มีให้ไปหน้า Play Store
 *
 * @returns ผลการเปิด — 'unavailable' = ไม่มีทางเปิดได้เลย (ผู้เรียกแจ้งผู้ใช้เอง)
 */
export async function openJuntraApp(): Promise<JuntraOpenResult> {
  if (!canOfferJuntra()) {
    return 'unavailable';
  }

  // เช็คก่อนว่า build นี้มี native module ไหม — ห้าม require('expo-intent-launcher') ตรงๆ บน build ที่ไม่มี:
  // Metro รายงาน error ตอนโหลดโมดูลเป็น fatal (reportFatalError) แม้อยู่ใน try → release build ปิดแอปได้
  // (เจอจริงบนเครื่องทดสอบ 2026-09-26: dev client เก่าขึ้นกล่องแดง "Cannot find native module")
  if (requireOptionalNativeModule('ExpoIntentLauncher')) {
    try {
      // eslint-disable-next-line @typescript-eslint/no-require-imports
      const IntentLauncher = require('expo-intent-launcher') as typeof import('expo-intent-launcher');
      IntentLauncher.openApplication(JUNTRA_ANDROID_PACKAGE);
      return 'app';
    } catch {
      // ยังไม่ได้ติดตั้งแอปจันทรา (PackageNotFoundException) → ไปหน้าร้านค้า
    }
  }

  for (const url of [PLAY_STORE_APP_URL, PLAY_STORE_WEB_URL]) {
    try {
      await Linking.openURL(url);
      return 'store';
    } catch {
      // เครื่องไม่มีแอป Play Store → ลองลิงก์เว็บต่อ
    }
  }

  return 'unavailable';
}
