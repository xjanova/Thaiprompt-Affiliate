/**
 * usePressGuard — กันกดซ้ำ (double-tap) + สถานะกำลังทำงานอัตโนมัติ
 *
 * - handler คืน Promise → ล็อกจนกว่า Promise จะจบ และ busy = true ระหว่างนั้น
 * - handler ธรรมดา → ล็อกสั้นๆ 350ms กันกดรัวจนเปิดหน้าซ้ำ
 * - component ถูกถอดระหว่างรอ → ไม่ setState (กัน warning/หน่วงความจำ)
 */

import { useCallback, useEffect, useRef, useState } from 'react';

const SYNC_LOCK_MS = 350;

export const usePressGuard = (
  handler: (() => unknown) | undefined,
  options: { disabled?: boolean } = {}
): { run: () => void; busy: boolean } => {
  const [busy, setBusy] = useState(false);
  const lockedRef = useRef(false);
  const mountedRef = useRef(true);
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
    };
  }, []);

  const run = useCallback(() => {
    if (!handler || options.disabled || lockedRef.current) {
      return;
    }
    lockedRef.current = true;

    const release = () => {
      lockedRef.current = false;
      if (mountedRef.current) {
        setBusy(false);
      }
    };

    let result: unknown;
    try {
      result = handler();
    } catch (error) {
      release();
      if (__DEV__) {
        console.warn('[usePressGuard] handler error', error);
      }
      return;
    }

    if (result && typeof (result as Promise<unknown>).then === 'function') {
      setBusy(true);
      (result as Promise<unknown>).then(release, (error) => {
        if (__DEV__) {
          console.warn('[usePressGuard] async handler rejected', error);
        }
        release();
      });
      return;
    }

    timerRef.current = setTimeout(() => {
      lockedRef.current = false;
    }, SYNC_LOCK_MS);
  }, [handler, options.disabled]);

  return { run, busy };
};
