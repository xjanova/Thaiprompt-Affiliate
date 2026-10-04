/**
 * ด่านยืนยันตัวตน — เมื่อ API ตอบ 422 KYC_REQUIRED (แอป build ≥ 44 เท่านั้น)
 *
 * จุดที่ server บังคับ: สั่งซื้อ (ร้านค้า + ตลาดสด) · ไรเดอร์กดออนไลน์/สมัครไรเดอร์ · ส่งคำขอเปิดร้าน/สมัครร้านตลาดสด
 * (ถอนเงินใช้ code เดียวกันมาแต่เดิม — ใช้ด่านนี้ได้เหมือนกัน)
 *
 * การทำงาน: หน้าจอเรียก guardKycRequired(result, 'checkout')
 *   → ถ้าเป็น KYC_REQUIRED: เปิด bottom sheet "ยืนยันตัวตนก่อน…" (KycGateHost วางไว้ที่ app/_layout ครั้งเดียว) แล้วคืน true
 *   → ผู้ใช้กด "เริ่มยืนยันตัวตน" → /ekyc?from=checkout → ยืนยันเสร็จกด "กลับไป…" → ย้อนกลับมาหน้าเดิม (ข้อมูลที่กรอกยังอยู่)
 *
 * @example
 * const res = await checkout(body);
 * if (guardKycRequired(res, 'checkout')) return;
 */

import { create } from 'zustand';
import { normalizeKycStatusValue, type KycStatusValue } from '@/services/api/ekycApi';

/** การกระทำที่ถูกด่านกั้น */
export type KycGateContext = 'checkout' | 'rider' | 'seller' | 'withdraw' | 'profile';

export const KYC_GATE_CONTEXTS: readonly KycGateContext[] = ['checkout', 'rider', 'seller', 'withdraw', 'profile'];

export const isKycGateContext = (value: unknown): value is KycGateContext =>
  typeof value === 'string' && (KYC_GATE_CONTEXTS as readonly string[]).includes(value);

/** ข้อความของ sheet + ปุ่มกลับไปทำต่อ ตามการกระทำ */
export const KYC_GATE_COPY: Record<KycGateContext, { title: string; action: string; returnLabel: string }> = {
  checkout: { title: 'ยืนยันตัวตนก่อนสั่งซื้อ', action: 'สั่งซื้อ', returnLabel: 'กลับไปสั่งซื้อต่อ' },
  rider: { title: 'ยืนยันตัวตนก่อนเริ่มรับงาน', action: 'รับงานไรเดอร์', returnLabel: 'กลับไปเริ่มรับงาน' },
  seller: { title: 'ยืนยันตัวตนก่อนเปิดร้าน', action: 'เปิดร้าน', returnLabel: 'กลับไปส่งคำขอเปิดร้าน' },
  withdraw: { title: 'ยืนยันตัวตนก่อนถอนเงิน', action: 'ถอนเงิน', returnLabel: 'กลับไปถอนเงิน' },
  profile: { title: 'ยืนยันตัวตน', action: 'ใช้งาน', returnLabel: 'เสร็จแล้ว' },
};

/** ผลจาก API นี้คือ "ต้องยืนยันตัวตนก่อน" หรือไม่ */
export const isKycRequired = (result: { success: boolean; code?: string } | null | undefined): boolean =>
  !!result && result.success === false && result.code === 'KYC_REQUIRED';

export interface KycGateRequest {
  context: KycGateContext;
  /** สถานะจาก data.kyc_status ของ 422 (pending = รอเจ้าหน้าที่ตรวจอยู่แล้ว) */
  kycStatus: KycStatusValue;
  /** ข้อความไทยจาก server (ถ้ามี) */
  message: string | null;
}

interface KycGateStore {
  request: KycGateRequest | null;
  open: (request: KycGateRequest) => void;
  close: () => void;
}

export const useKycGateStore = create<KycGateStore>((set) => ({
  request: null,
  open: (request) => set({ request }),
  close: () => set({ request: null }),
}));

/** สร้างคำขอเปิด sheet จากผล API */
export const kycGateRequestFrom = (
  result: { success: boolean; code?: string; message?: string; data?: any },
  context: KycGateContext
): KycGateRequest => ({
  context,
  kycStatus: normalizeKycStatusValue(result?.data?.kyc_status),
  message: typeof result?.message === 'string' && result.message.trim() ? result.message : null,
});

/**
 * ตรวจผล API แล้วเปิด sheet ถ้าต้องยืนยันตัวตน
 * @returns true = จัดการแล้ว (หน้าจอไม่ต้องแสดง error เอง)
 */
export const guardKycRequired = (
  result: { success: boolean; code?: string; message?: string; data?: any } | null | undefined,
  context: KycGateContext
): boolean => {
  if (!result || !isKycRequired(result)) return false;
  useKycGateStore.getState().open(kycGateRequestFrom(result, context));
  return true;
};

/** เปิด sheet เอง (เช่น ปุ่มในหน้าโปรไฟล์) */
export const openKycGate = (context: KycGateContext, kycStatus: KycStatusValue = 'none'): void => {
  useKycGateStore.getState().open({ context, kycStatus, message: null });
};

/** path ของขั้นตอนยืนยันตัวตนสำหรับการกระทำนี้ */
export const ekycPathFor = (context: KycGateContext, kycStatus: KycStatusValue): string =>
  kycStatus === 'pending' ? `/ekyc/result?from=${context}` : `/ekyc?from=${context}`;

/** hook สำหรับหน้าจอ (คืนฟังก์ชันเดียวกัน — ใช้แบบไหนก็ได้) */
export const useKycGate = () => ({ guard: guardKycRequired, open: openKycGate });
