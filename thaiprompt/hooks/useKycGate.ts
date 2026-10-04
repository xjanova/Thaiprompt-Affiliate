/**
 * useKycGate — ด่าน "ยืนยันตัวตนก่อน…" สำหรับหน้าจอ (ตัวจริงอยู่ที่ services/ekyc/kycGate.ts)
 *
 * @example
 * const { guard } = useKycGate();
 * const res = await checkout(body);
 * if (guard(res, 'checkout')) return; // API ตอบ KYC_REQUIRED → เปิด sheet ยืนยันตัวตนแล้ว
 */

export { useKycGate, guardKycRequired, openKycGate, isKycRequired } from '@/services/ekyc/kycGate';
export type { KycGateContext } from '@/services/ekyc/kycGate';
export { useKycGate as default } from '@/services/ekyc/kycGate';
