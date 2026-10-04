/**
 * useSensitiveScreen — กันแคปหน้าจอ/อัดหน้าจอ ระหว่างหน้าจอที่มีข้อมูลส่วนตัวอยู่ด้านหน้า
 * (Android = FLAG_SECURE: แคปไม่ได้ อัดจอเป็นสีดำ และภาพในหน้าสลับแอปเป็นสีดำ · iOS = บังตอนอัดจอ)
 *
 * - ทำงานเฉพาะตอนหน้าจอ "อยู่ด้านหน้า" (focus) — แท็บที่ค้างอยู่เบื้องหลังจะไม่ล็อกทั้งแอป
 * - build เก่าที่ยังไม่มี native module ExpoScreenCapture → ไม่ทำอะไรเลย (ห้ามล้มเด็ดขาด)
 *   ⚠️ ห้าม import 'expo-screen-capture' ตรงๆ ที่หัวไฟล์: โมดูลนั้นเรียก requireNativeModule ตอนโหลด
 *   ถ้า binary ไม่มี native module จะเป็น fatal error แม้อยู่ใน try/catch (Metro reportFatalError)
 *   จึงเช็ค requireOptionalNativeModule ก่อน แล้วค่อย require แบบ lazy
 * - แต่ละหน้าจอได้ key ไม่ซ้ำกัน (เปิดซ้อนหลายหน้า ปิดหน้าหนึ่งแล้วหน้าที่เหลือยังกันอยู่)
 * - ใช้ในหน้าจอ (screen ของ expo-router) เท่านั้น เพราะอาศัย useFocusEffect
 *
 * @param key ชื่อหน้าจอ (ใช้แยก key ของแต่ละหน้า) เช่น 'wallet', 'handover'
 *
 * @example
 * export default function WalletScreen() {
 *   useSensitiveScreen('wallet');
 *   ...
 * }
 */

import { useCallback, useRef } from 'react';
import { requireOptionalNativeModule } from 'expo';
import { useFocusEffect } from 'expo-router';

type ScreenCaptureModule = {
  preventScreenCaptureAsync: (key?: string) => Promise<void>;
  allowScreenCaptureAsync: (key?: string) => Promise<void>;
};

/** undefined = ยังไม่เคยโหลด · null = build นี้ไม่มี native module */
let screenCapture: ScreenCaptureModule | null | undefined;

/** โหลด expo-screen-capture ครั้งเดียว — ไม่มี native module = null */
const loadScreenCapture = (): ScreenCaptureModule | null => {
  if (screenCapture !== undefined) return screenCapture;
  try {
    if (!requireOptionalNativeModule('ExpoScreenCapture')) {
      screenCapture = null;
      return screenCapture;
    }
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    const mod = require('expo-screen-capture') as ScreenCaptureModule;
    screenCapture =
      mod && typeof mod.preventScreenCaptureAsync === 'function' && typeof mod.allowScreenCaptureAsync === 'function'
        ? mod
        : null;
  } catch {
    screenCapture = null;
  }
  return screenCapture;
};

/** เลขลำดับต่อหน้าจอ (ทำให้ key ไม่ซ้ำแม้ชื่อเดียวกันเปิดซ้อนกัน) */
let instanceSeq = 0;

export const useSensitiveScreen = (key: string = 'sensitive'): void => {
  const tagRef = useRef<string | null>(null);
  if (tagRef.current === null) {
    instanceSeq += 1;
    tagRef.current = `${key}:${instanceSeq}`;
  }

  useFocusEffect(
    useCallback(() => {
      const mod = loadScreenCapture();
      const tag = tagRef.current as string;
      if (!mod) return undefined;
      // native ตอบ error ได้ (เช่น iOS รุ่นเก่า) — กลืนไว้ ห้ามเป็น unhandled rejection
      mod.preventScreenCaptureAsync(tag).catch(() => {});
      return () => {
        mod.allowScreenCaptureAsync(tag).catch(() => {});
      };
    }, [])
  );
};

/** build นี้กันแคปหน้าจอได้จริงหรือไม่ (ใช้ซ่อน/แสดงป้าย "ป้องกันการแคปหน้าจอ") */
export const isScreenCaptureProtectionAvailable = (): boolean => loadScreenCapture() !== null;

export default useSensitiveScreen;
