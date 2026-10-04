/**
 * ขั้นตอนเริ่มรอบใหม่ของ eKYC (ถ่ายใหม่ / รอบหมดเวลา / ท่าทางไม่ตรงคำสั่ง / ส่งรูปบัตรครบโควตารอบ)
 *
 * รอบ (session) ใหม่ = คำสั่งท่าทางสุ่มใหม่ และรูปบัตรต้องส่งเข้ารอบใหม่อีกครั้ง
 *   - ถ่ายใหม่เพราะใบหน้า + ยังมีรูปบัตรในเครื่อง → ส่งรูปบัตรเดิมเข้ารอบใหม่ให้เอง (ผู้ใช้ไม่ต้องถ่ายบัตรซ้ำ)
 *     ข้อมูลที่ผู้ใช้แก้ไว้ (ชื่อ/วันเกิด) ส่งซ้ำให้ด้วย
 *   - ถ่ายใหม่เพราะบัตร / ไม่มีรูปบัตร / ส่งรูปเดิมไม่ผ่าน → กลับไปหน้าถ่ายบัตร
 *   - รอบใหม่มีท่าที่แอปรุ่นนี้ไม่รู้จัก → error EKYC_APP_OUTDATED (ห้ามไปถ่ายใบหน้า ไม่งั้นวนเริ่มรอบใหม่ไม่จบ)
 *
 * รหัสที่แปลว่า "ไปดูผล" (ยืนยันแล้ว / รอเจ้าหน้าที่ / ยังตรวจอยู่ / ตรวจเสร็จแล้ว / ลองครบวันนี้)
 *   → goResultForCode() พาไปหน้าผล (หน้าผลถามสถานะซ้ำเองถ้ายังตรวจอยู่) — ห้ามเริ่มรอบใหม่ทับ
 */

import { router } from 'expo-router';
import {
  correctEkycIdCard,
  EKYC_APP_OUTDATED_MESSAGE,
  isSessionUsable,
  uploadEkycIdCard,
} from '@/services/api/ekycApi';
import { useAuthStore } from '@/stores/authStore';
import { useEkycStore } from '@/stores/ekycStore';
import { decisionForCode, isResultCode, isWaitCode } from './outcome';

export type RestartOutcome =
  | { kind: 'face' }
  | { kind: 'capture' }
  | { kind: 'error'; code: string; message: string };

export const restartEkycSession = async (target: 'card' | 'face'): Promise<RestartOutcome> => {
  const store = useEkycStore.getState();
  const keepCorrections = { ...store.corrections };
  const started = await store.startSession();
  if (!started.success) return { kind: 'error', code: started.code, message: started.message };
  if (!isSessionUsable(started.data)) return { kind: 'error', code: 'EKYC_APP_OUTDATED', message: EKYC_APP_OUTDATED_MESSAGE };

  const card = useEkycStore.getState().card;
  if (target === 'card' || !card) return { kind: 'capture' };

  const sessionId = started.data.session_id;
  const uploaded = await uploadEkycIdCard(sessionId, card.uri);
  if (!uploaded.success || uploaded.data.status !== 'ok') return { kind: 'capture' };
  useEkycStore.getState().setCardResult(uploaded.data, sessionId);

  if (keepCorrections.name_th || keepCorrections.birth_date) {
    const fixed = await correctEkycIdCard(sessionId, keepCorrections);
    if (!fixed.success) return { kind: 'capture' };
    useEkycStore.getState().setCorrections(keepCorrections);
  }
  return { kind: 'face' };
};

/**
 * รหัสนี้แปลว่า "ไปดูผล" หรือไม่ → ใช่ = ตั้งผลที่รู้แล้ว แล้วพาไปหน้าผล (แทนที่หน้าปัจจุบัน) คืน true
 *   EKYC_ALREADY_VERIFIED → ผ่านแล้ว · EKYC_PENDING_REVIEW → รอเจ้าหน้าที่
 *   EKYC_PROCESSING / EKYC_SESSION_DONE / EKYC_TOO_MANY_ATTEMPTS → หน้าผลโหลดสถานะเอง (ยังตรวจอยู่ = ถามซ้ำจนเสร็จ)
 * ไฟล์เฟรมใบหน้าในเครื่องถูกลบ (ส่งไปแล้ว/ใช้ต่อไม่ได้) · รูปบัตรเก็บไว้ (ถ่ายหน้าใหม่ได้โดยไม่ต้องถ่ายบัตรซ้ำ)
 */
export const goResultForCode = (code: string | null | undefined): boolean => {
  if (!isResultCode(code)) return false;
  const store = useEkycStore.getState();
  store.setDecision(decisionForCode(code));
  store.clearFrames();
  // ยังตรวจอยู่ → ให้หน้าผลขึ้น "กำลังตรวจ" ทันที (ไม่กระพริบเป็นผลเก่า) แล้วถามสถานะจริงต่อเอง
  if (isWaitCode(code) && store.status) store.setStatus({ ...store.status, processing: true });
  store.loadStatus(true).catch(() => {});
  if (code === 'EKYC_ALREADY_VERIFIED') useAuthStore.getState().refreshUser().catch(() => {});
  router.replace('/ekyc/result' as never);
  return true;
};
