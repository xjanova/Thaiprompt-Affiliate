/**
 * useAcceptJob — กดรับงานไรเดอร์ (ใช้ทั้งหน้ารายการงานและหน้ารายละเอียดงาน)
 *
 * - กันกดซ้ำด้วย ref (ไม่รอ re-render) — กดรับหลายงานพร้อมกันไม่ได้ (RIDER-APP-16)
 * - แนบพิกัดล่าสุดไปด้วยทุกครั้ง (server ใช้ตรวจระยะ + ความสดของตำแหน่ง)
 * - error ทุกแบบมีข้อความไทยที่บอกว่าต้องทำอะไรต่อ
 *   CONSENT_REQUIRED → เปิดหน้ายินยอมแล้วลองรับอีกครั้งให้เอง
 *   LOCATION_STALE   → ส่งตำแหน่งใหม่แล้วลองรับอีกครั้งให้เอง
 * - รับสำเร็จ → เริ่มติดตามตำแหน่งของงานนี้ทันที (services/location)
 */

import { useCallback, useEffect, useRef, useState } from 'react';
import { Alert } from 'react-native';
import { router } from 'expo-router';
import { resultHaptic, formatBaht } from '@/components/ui';
import { num } from '@/services/api/client';
import { acceptRiderJob, type RiderJobDetail } from '@/services/api/riderApi';
import { getCurrentCoords, pingRiderLocation, startJobTracking, type TrackingMode } from '@/services/location';
import type { RiderPermissionFlow } from './useRiderPermissionFlow';

export interface UseAcceptJobOptions {
  flow: RiderPermissionFlow;
  /** งานนี้ไม่มีแล้ว (มีคนรับไป/ถูกยกเลิก) → เอาออกจากรายการ */
  onGone?: (jobId: number) => void;
  /** รับสำเร็จ */
  onAccepted?: (job: RiderJobDetail, tracking: TrackingMode | 'none') => void;
}

export const useAcceptJob = ({ flow, onGone, onAccepted }: UseAcceptJobOptions) => {
  const busyRef = useRef(false);
  const mountedRef = useRef(true);
  const [acceptingId, setAcceptingId] = useState<number | null>(null);

  const onGoneRef = useRef(onGone);
  const onAcceptedRef = useRef(onAccepted);
  onGoneRef.current = onGone;
  onAcceptedRef.current = onAccepted;

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const attempt = useCallback(
    async (jobId: number, retry: number): Promise<void> => {
      const coords = await getCurrentCoords({ timeoutMs: 6000 });
      const result = await acceptRiderJob(
        jobId,
        coords ? { latitude: coords.latitude, longitude: coords.longitude } : undefined
      );

      if (result.success) {
        resultHaptic('success');
        const job = result.data.job;
        const tracking = await startJobTracking(job?.id ?? jobId);
        onAcceptedRef.current?.(job, tracking);
        return;
      }

      const code = result.code;
      const blockCode: string | undefined =
        typeof result.data?.block_code === 'string' ? result.data.block_code : undefined;
      const reason = blockCode || code;

      // ---------- แก้ให้อัตโนมัติแล้วลองอีกครั้ง ----------
      if (reason === 'CONSENT_REQUIRED') {
        if (retry === 0 && (await flow.requestConsent())) {
          return attempt(jobId, 1);
        }
        Alert.alert('ยังรับงานไม่ได้', 'ต้องยอมรับการแชร์ตำแหน่งให้ลูกค้าระหว่างส่งก่อนรับงานแรกนะ');
        return;
      }

      if (reason === 'LOCATION_STALE') {
        if (retry === 0 && (await flow.ensureForeground()) && (await pingRiderLocation({ force: true }))) {
          return attempt(jobId, 1);
        }
        resultHaptic('warning');
        Alert.alert('ยังไม่ได้ตำแหน่งล่าสุด', 'เปิด GPS ของเครื่องแล้วลองรับงานอีกครั้งนะ');
        return;
      }

      resultHaptic(code === 'JOB_TAKEN' ? 'warning' : 'error');

      switch (reason) {
        case 'JOB_TAKEN':
          Alert.alert('ช้าไปนิดเดียว!', 'งานนี้มีไรเดอร์คนอื่นรับไปแล้ว ลองงานถัดไปนะ 💪');
          onGoneRef.current?.(jobId);
          return;
        case 'JOB_NOT_FOUND':
          Alert.alert('งานนี้ไม่มีแล้ว', 'งานนี้อาจถูกยกเลิกไปแล้ว');
          onGoneRef.current?.(jobId);
          return;
        case 'HAS_ACTIVE_JOB': {
          const activeId = num(result.data?.active_job_id, 0);
          Alert.alert('ส่งงานเดิมให้เสร็จก่อนนะ', 'คุณมีงานที่ยังส่งไม่เสร็จ รับงานใหม่ได้หลังส่งงานนี้', [
            { text: 'ไว้ก่อน', style: 'cancel' },
            {
              text: 'ไปที่งานปัจจุบัน',
              onPress: () =>
                router.push((activeId > 0 ? `/rider-job-detail?id=${activeId}` : '/rider-job-detail') as never),
            },
          ]);
          return;
        }
        case 'INSUFFICIENT_COD_CREDIT': {
          if (result.data?.unsettled_cod) {
            Alert.alert(
              'ยังมียอดเงินสดค้างนำส่ง',
              'นำส่งยอดเก็บเงินปลายทางของงานก่อนให้เรียบร้อย แล้วค่อยรับงานเก็บเงินปลายทางใหม่นะ'
            );
            return;
          }
          const required = num(result.data?.required, 0);
          const available = num(result.data?.available, 0);
          const detail =
            required > 0
              ? `งานนี้เก็บเงินปลายทาง ต้องมียอดในกระเป๋าอย่างน้อย ${formatBaht(required)} (ตอนนี้มี ${formatBaht(available)})`
              : 'งานนี้เก็บเงินปลายทาง ยอดในกระเป๋ายังไม่พอค้ำประกัน';
          Alert.alert('ยอดในกระเป๋ายังไม่พอ', detail, [
            { text: 'ไว้ก่อน', style: 'cancel' },
            { text: 'เติมเงิน', onPress: () => router.push('/wallet-topup' as never) },
          ]);
          return;
        }
        case 'OFFLINE':
          Alert.alert('ยังไม่ได้เริ่มรับงาน', 'กด "เริ่มรับงาน" ก่อน แล้วค่อยรับงานนะ');
          return;
        case 'TOO_FAR':
          Alert.alert('งานนี้ไกลเกินไป', 'งานนี้อยู่ไกลจากตำแหน่งของคุณเกินระยะรับงาน ลองงานที่ใกล้กว่านะ');
          return;
        case 'DOCUMENTS_REVIEW_PENDING':
          Alert.alert(
            'เอกสารกำลังรอตรวจ',
            'เอกสารหรือยานพาหนะที่เปลี่ยนใหม่กำลังรอทีมงานตรวจสอบ ระหว่างนี้ยังรับงานไม่ได้นะ'
          );
          return;
        case 'SELF_ORDER':
          Alert.alert('รับงานนี้ไม่ได้', 'งานนี้เป็นออเดอร์ของคุณเอง');
          return;
        default:
          Alert.alert('รับงานไม่สำเร็จ', result.message);
      }
    },
    [flow]
  );

  /** กดรับงาน (กันกดซ้ำในตัว) */
  const accept = useCallback(
    async (jobId: number): Promise<void> => {
      if (busyRef.current) return;
      busyRef.current = true;
      setAcceptingId(jobId);
      try {
        await attempt(jobId, 0);
      } finally {
        busyRef.current = false;
        if (mountedRef.current) setAcceptingId(null);
      }
    },
    [attempt]
  );

  return { accept, acceptingId };
};

export default useAcceptJob;
