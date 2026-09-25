/**
 * useRiderAvailability — เริ่มรับงาน / หยุดรับงาน (ใช้ในหน้าไรเดอร์และหน้ารายการงาน)
 *
 * เริ่มรับงาน:
 *   1. ขอตำแหน่ง "ขณะใช้แอป" (แสดงคำอธิบายก่อนเสมอ) — PLAY-14: ไม่บังคับ "ตลอดเวลา"
 *   2. ส่งพิกัดปัจจุบันไปพร้อมคำขอ → server รับงานให้ได้ทันที
 *   3. ยังไม่เคยยินยอมแชร์ตำแหน่งให้ลูกค้า → ถามต่อ (ข้ามได้ แต่ต้องยอมรับก่อนรับงานแรก)
 *   4. เสนอ "ติดตามต่อแม้ปิดหน้าจอ" ครั้งเดียว (ข้ามได้)
 */

import { useCallback } from 'react';
import { Alert } from 'react-native';
import { router } from 'expo-router';
import { resultHaptic } from '@/components/ui';
import { num } from '@/services/api/client';
import { setRiderAvailability, updateRiderPermissions } from '@/services/api/riderApi';
import { getCurrentCoords } from '@/services/location';
import type { RiderPermissionFlow } from './useRiderPermissionFlow';

export const useRiderAvailability = (flow: RiderPermissionFlow) => {
  const goOnline = useCallback(
    async (options: { hasConsent: boolean }): Promise<boolean> => {
      const tryOnce = async (retry: number): Promise<boolean> => {
        const granted = await flow.ensureForeground();
        if (!granted) {
          return false;
        }

        const coords = await getCurrentCoords({ accuracy: 'high', timeoutMs: 8000 });
        const result = await setRiderAvailability(
          'online',
          coords ? { latitude: coords.latitude, longitude: coords.longitude } : undefined
        );

        if (result.success) {
          resultHaptic('success');
          if (!options.hasConsent) {
            await flow.requestConsent();
          }
          await flow.offerBackground();
          return true;
        }

        const blockCode: string | undefined =
          typeof result.data?.block_code === 'string' ? result.data.block_code : undefined;

        if (result.code === 'GPS_REQUIRED' && retry === 0) {
          // เครื่องให้สิทธิ์แล้วแต่ server ยังไม่รู้ → แจ้งแล้วลองใหม่หนึ่งครั้ง
          await updateRiderPermissions({ gps: true });
          return tryOnce(1);
        }

        resultHaptic('error');

        if (result.code === 'HAS_ACTIVE_JOB') {
          const activeId = num(result.data?.active_job_id, 0);
          Alert.alert('คุณมีงานที่กำลังส่งอยู่', 'ส่งงานนี้ให้เสร็จก่อน แล้วระบบจะเปิดรับงานให้เอง', [
            { text: 'ไว้ก่อน', style: 'cancel' },
            {
              text: 'ไปที่งาน',
              onPress: () =>
                router.push((activeId > 0 ? `/rider-job-detail?id=${activeId}` : '/rider-job-detail') as never),
            },
          ]);
          return false;
        }

        if (blockCode === 'DOCUMENTS_REVIEW_PENDING') {
          Alert.alert(
            'เอกสารกำลังรอตรวจ',
            'เอกสารหรือยานพาหนะที่เปลี่ยนใหม่กำลังรอทีมงานตรวจสอบ ตรวจเสร็จแล้วจะเริ่มรับงานได้ทันที'
          );
          return false;
        }

        if (result.code === 'GPS_REQUIRED') {
          Alert.alert('ยังไม่ได้ตำแหน่งของคุณ', 'เปิด GPS ของเครื่องแล้วลองเริ่มรับงานอีกครั้งนะ');
          return false;
        }

        Alert.alert(
          result.code === 'NOT_ELIGIBLE' ? 'ยังเริ่มรับงานไม่ได้' : 'เริ่มรับงานไม่สำเร็จ',
          result.message
        );
        return false;
      };

      return tryOnce(0);
    },
    [flow]
  );

  const goOffline = useCallback(async (): Promise<boolean> => {
    const result = await setRiderAvailability('offline');
    if (result.success) {
      resultHaptic('success');
      return true;
    }
    resultHaptic('error');
    if (result.code === 'HAS_ACTIVE_JOB') {
      Alert.alert('ยังหยุดรับงานไม่ได้', 'ส่งงานที่ค้างอยู่ให้เสร็จก่อนนะ');
    } else {
      Alert.alert('หยุดรับงานไม่สำเร็จ', result.message);
    }
    return false;
  }, []);

  return { goOnline, goOffline };
};

export default useRiderAvailability;
