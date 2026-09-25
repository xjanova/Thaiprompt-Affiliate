/**
 * hooks ของหน้าตลาดสด
 *
 * - useMountedRef: กัน setState หลัง component ถูกถอด (หลัง await ทุกครั้ง)
 * - useFocusedInterval: ทำงานซ้ำเฉพาะตอนหน้าจออยู่ด้านหน้า + แอปเปิดอยู่ (ออกจากหน้า/พับแอป = หยุด)
 */

import { useCallback, useEffect, useRef } from 'react';
import { AppState } from 'react-native';
import { useFocusEffect } from 'expo-router';

export const useMountedRef = () => {
  const mountedRef = useRef(true);
  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);
  return mountedRef;
};

/**
 * เรียก callback ทุก intervalMs ระหว่างหน้าจอ focus และแอปอยู่ foreground
 * - งานรอบก่อนยังไม่จบ → ข้ามรอบนี้ (ไม่ยิงซ้อน)
 * - แอปกลับมา foreground → เรียกทันทีหนึ่งครั้ง
 *
 * @param immediate เรียกทันทีตอนเริ่ม (ค่าเริ่มต้น false)
 */
export const useFocusedInterval = (
  callback: () => unknown,
  intervalMs: number,
  enabled: boolean,
  immediate: boolean = false
): void => {
  const callbackRef = useRef(callback);
  callbackRef.current = callback;

  useFocusEffect(
    useCallback(() => {
      if (!enabled || !(intervalMs > 0)) return undefined;
      let appActive = AppState.currentState === 'active';
      let running = false;
      let stopped = false;

      const tick = async () => {
        if (stopped || !appActive || running) return;
        running = true;
        try {
          await callbackRef.current();
        } catch {
          // งานรอบนี้ล้ม — รอรอบถัดไป
        } finally {
          running = false;
        }
      };

      if (immediate) tick();
      const timer = setInterval(tick, intervalMs);
      const sub = AppState.addEventListener('change', (state) => {
        const wasActive = appActive;
        appActive = state === 'active';
        if (appActive && !wasActive) tick();
      });

      return () => {
        stopped = true;
        clearInterval(timer);
        sub.remove();
      };
    }, [enabled, intervalMs, immediate])
  );
};
