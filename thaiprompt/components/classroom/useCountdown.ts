/**
 * useCountdownMs — มิลลิวินาทีที่เหลือถึงเวลา iso ของ server (อัปเดตทุกวินาที · ถึงเวลาแล้วหยุดเอง · ถอดหน้าจอแล้วล้าง timer)
 *
 * ใช้กับ: เวลารอทำแบบทดสอบใหม่ (retry_available_at) · เวลาหมดรอบทำแบบทดสอบ (expires_at)
 * แก้นาฬิกาเครื่องเพี้ยนด้วย offset ล่าสุดที่แอปรู้ (utils/serverClock)
 */

import { useEffect, useState } from 'react';
import { lastClockOffset } from '@/utils/serverClock';
import { cooldownRemainingMs } from './classroomLogic';

export const useCountdownMs = (iso: string | null | undefined): number => {
  const [left, setLeft] = useState(() => cooldownRemainingMs(iso, lastClockOffset()));
  useEffect(() => {
    setLeft(cooldownRemainingMs(iso, lastClockOffset()));
    if (!iso) return undefined;
    const timer = setInterval(() => {
      const next = cooldownRemainingMs(iso, lastClockOffset());
      setLeft(next);
      if (next <= 0) clearInterval(timer);
    }, 1000);
    return () => clearInterval(timer);
  }, [iso]);
  return left;
};
