/**
 * ProfilePhotoGate — บังคับถ่ายรูปโปรไฟล์หลังเข้าสู่ระบบ (วางครั้งเดียวใน app/_layout.tsx)
 *
 * - เข้าสู่ระบบแล้ว → GET /me/profile-photo ครั้งเดียวต่อบัญชีต่อการเปิดแอป
 *   has_photo=false และ required=true → พาไปหน้า /profile-photo?gate=1
 * - ไม่บล็อกหน้าเข้าสู่ระบบ/สมัคร/ข้อตกลง/นโยบาย/ช่วยเหลือ/คลิปแนะนำ (รอจนออกจากหน้าเหล่านั้นก่อน)
 * - server ยังไม่มี endpoint (404) / เน็ตหลุด → ไม่บังคับ (ลองใหม่ภายหลัง) — ห้ามขังผู้ใช้เพราะระบบเราเอง
 * - ถ่ายรูปสำเร็จ (markProfilePhotoDone) → เลิกบังคับทันที
 */

import { useCallback, useEffect, useRef, useState } from 'react';
import { AppState } from 'react-native';
import { router, useSegments } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { getProfilePhoto } from '@/services/api/profilePhotoApi';

/** หน้าที่ไม่บังคับ (segment แรกของ path) */
const EXEMPT_SEGMENTS = new Set([
  'login',
  'register',
  'auth',
  'agreement',
  'privacy',
  'terms',
  'intro-video',
  'profile-photo',
  'webview',
  'support',
]);

const RETRY_MS = 60_000;

// ---------- ถ่ายรูปสำเร็จ → แจ้งตัวบังคับ ----------
let doneListeners: Array<() => void> = [];

/** เรียกหลังอัปโหลดรูปสำเร็จ (หน้า profile-photo) */
export const markProfilePhotoDone = (): void => {
  doneListeners.forEach((fn) => {
    try {
      fn();
    } catch {
      // ตัวฟังพังก็ไม่เป็นไร
    }
  });
};

export const ProfilePhotoGate: React.FC = () => {
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const isInitialized = useAuthStore((s) => s.isInitialized);
  const userId = useAuthStore((s) => s.user?.id ?? null);
  const segments = useSegments() as string[];

  /** บัญชีที่ต้องถ่ายรูป (null = ไม่ต้อง/ยังไม่รู้) */
  const [needsFor, setNeedsFor] = useState<number | null>(null);
  const checkedForRef = useRef<number | null>(null);
  const lastTryRef = useRef(0);
  const busyRef = useRef(false);
  const lastPushRef = useRef(0);

  // ถ่ายรูปเสร็จ → เลิกบังคับ
  useEffect(() => {
    const fn = () => setNeedsFor(null);
    doneListeners.push(fn);
    return () => {
      doneListeners = doneListeners.filter((f) => f !== fn);
    };
  }, []);

  const check = useCallback(async () => {
    if (!isAuthenticated || !isInitialized || !userId) return;
    if (checkedForRef.current === userId || busyRef.current) return;
    if (Date.now() - lastTryRef.current < RETRY_MS && lastTryRef.current > 0) return;
    busyRef.current = true;
    lastTryRef.current = Date.now();
    const res = await getProfilePhoto();
    busyRef.current = false;
    // บัญชีเปลี่ยนระหว่างรอ → ทิ้งผลนี้
    if (useAuthStore.getState().user?.id !== userId) return;
    if (res.success) {
      checkedForRef.current = userId;
      setNeedsFor(!res.data.has_photo && res.data.required ? userId : null);
      return;
    }
    // 4xx ที่ไม่ใช่เน็ต (เช่น 404 = server ยังไม่มีระบบนี้) → ไม่บังคับในรอบนี้
    if (res.status >= 400 && res.status < 500) checkedForRef.current = userId;
  }, [isAuthenticated, isInitialized, userId]);

  // เปลี่ยนบัญชี/ออกจากระบบ → เริ่มใหม่
  useEffect(() => {
    if (!isAuthenticated || !userId) {
      checkedForRef.current = null;
      lastTryRef.current = 0;
      setNeedsFor(null);
      return;
    }
    if (checkedForRef.current !== userId) {
      lastTryRef.current = 0;
      check();
    }
  }, [isAuthenticated, userId, check]);

  // แอปกลับมาหน้าจอ → ลองตรวจอีกครั้ง (กรณีรอบก่อนเน็ตหลุด)
  useEffect(() => {
    const sub = AppState.addEventListener('change', (state) => {
      if (state === 'active') check();
    });
    return () => sub.remove();
  }, [check]);

  // ต้องถ่ายรูป + อยู่หน้าที่ไม่ยกเว้น → พาไปหน้าถ่ายรูป
  const first = segments[0] ?? '';
  useEffect(() => {
    if (!needsFor || needsFor !== userId || !isAuthenticated) return;
    if (segments.length === 0 || EXEMPT_SEGMENTS.has(first)) return;
    if (Date.now() - lastPushRef.current < 1500) return;
    lastPushRef.current = Date.now();
    try {
      router.push('/profile-photo?gate=1' as never);
    } catch {
      // router ยังไม่พร้อม — รอบหน้าค่อยพา
    }
  }, [needsFor, userId, isAuthenticated, first, segments.length]);

  return null;
};

export default ProfilePhotoGate;
