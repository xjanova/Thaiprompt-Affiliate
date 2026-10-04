/**
 * KycGateHost — bottom sheet "ยืนยันตัวตนก่อน…" (วางครั้งเดียวใน app/_layout.tsx)
 *
 * เปิดเมื่อหน้าจอเรียก guardKycRequired(result, context) แล้ว API ตอบ KYC_REQUIRED
 *   - ยังไม่เคยยืนยัน → อธิบายสั้นๆ (ใช้เวลาราว 1 นาที · ทำครั้งเดียว · รูปเก็บเข้ารหัสบนเซิร์ฟเวอร์ไทยพร้อม)
 *     ปุ่ม "เริ่มยืนยันตัวตน" → /ekyc?from=context (จบแล้วกลับมาหน้าเดิม)
 *   - รอเจ้าหน้าที่ตรวจอยู่ (pending) → บอกสถานะ + ปุ่ม "ดูสถานะ"
 * กันกดซ้ำ: ปิด sheet ก่อนแล้วค่อยนำทาง (กดรัวไม่เปิดหน้าซ้อน)
 */

import React, { useRef } from 'react';
import { router } from 'expo-router';
import { ConsentSheet } from '@/components/ui';
import { useAuthStore } from '@/stores/authStore';
import { ekycPathFor, KYC_GATE_COPY, useKycGateStore } from '@/services/ekyc/kycGate';

export const KycGateHost: React.FC = () => {
  const request = useKycGateStore((s) => s.request);
  const close = useKycGateStore((s) => s.close);
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const lastNavRef = useRef(0);

  if (!request || !isAuthenticated) return null;

  const copy = KYC_GATE_COPY[request.context];
  const pending = request.kycStatus === 'pending';

  const start = () => {
    const now = Date.now();
    if (now - lastNavRef.current < 1200) return;
    lastNavRef.current = now;
    const path = ekycPathFor(request.context, request.kycStatus);
    close();
    try {
      router.push(path as never);
    } catch {
      // router ยังไม่พร้อม — ผู้ใช้กดใหม่ได้
    }
  };

  return (
    <ConsentSheet
      visible
      icon="seal-check"
      title={pending ? 'กำลังตรวจสอบตัวตนของคุณ' : copy.title}
      description={
        pending
          ? `เจ้าหน้าที่กำลังตรวจข้อมูลยืนยันตัวตน ตรวจเสร็จแล้วจะแจ้งเตือนทันที จากนั้น${copy.action}ได้เลย`
          : request.message || 'กรุณายืนยันตัวตนก่อนใช้งานส่วนนี้ ใช้เวลาประมาณ 1 นาที'
      }
      reasons={
        pending
          ? [{ icon: 'hourglass', text: 'ปกติไม่เกิน 1 ชั่วโมงในเวลาทำการ' }]
          : [
              { icon: 'identification-card', text: 'ถ่ายบัตรประชาชน แล้วถ่ายใบหน้าตามคำสั่งสั้นๆ' },
              { icon: 'seal-check', text: 'ทำครั้งเดียว ใช้ได้ทั้งสั่งของ รับงานไรเดอร์ และเปิดร้าน' },
              { icon: 'lock', text: 'รูปบัตรและใบหน้าเข้ารหัสเก็บบนเซิร์ฟเวอร์ไทยพร้อม ไม่ส่งให้บริษัทอื่น' },
            ]
      }
      acceptLabel={pending ? 'ดูสถานะ' : 'เริ่มยืนยันตัวตน'}
      declineLabel="ไว้ก่อน"
      onAccept={start}
      onDecline={close}
    />
  );
};

export default KycGateHost;
