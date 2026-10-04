/**
 * ขั้นตอนเริ่มรอบใหม่ของ eKYC (ถ่ายใหม่ / รอบหมดเวลา / ท่าทางไม่ตรงคำสั่ง)
 *
 * รอบ (session) ใหม่ = คำสั่งท่าทางสุ่มใหม่ และรูปบัตรต้องส่งเข้ารอบใหม่อีกครั้ง
 *   - ถ่ายใหม่เพราะใบหน้า + ยังมีรูปบัตรในเครื่อง → ส่งรูปบัตรเดิมเข้ารอบใหม่ให้เอง (ผู้ใช้ไม่ต้องถ่ายบัตรซ้ำ)
 *     ข้อมูลที่ผู้ใช้แก้ไว้ (ชื่อ/วันเกิด) ส่งซ้ำให้ด้วย
 *   - ถ่ายใหม่เพราะบัตร / ไม่มีรูปบัตร / ส่งรูปเดิมไม่ผ่าน → กลับไปหน้าถ่ายบัตร
 */

import { correctEkycIdCard, uploadEkycIdCard } from '@/services/api/ekycApi';
import { useEkycStore } from '@/stores/ekycStore';

export type RestartOutcome =
  | { kind: 'face' }
  | { kind: 'capture' }
  | { kind: 'error'; code: string; message: string };

export const restartEkycSession = async (target: 'card' | 'face'): Promise<RestartOutcome> => {
  const store = useEkycStore.getState();
  const keepCorrections = { ...store.corrections };
  const started = await store.startSession();
  if (!started.success) return { kind: 'error', code: started.code, message: started.message };

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
