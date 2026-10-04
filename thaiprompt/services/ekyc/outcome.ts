/**
 * ผลยืนยันตัวตนที่ไม่ได้มาจากคำตอบของ POST face โดยตรง — ฟังก์ชันล้วน (ทดสอบได้ใน jest)
 *
 * ใช้เมื่อ
 *   - ส่งใบหน้าซ้ำหลังเน็ตหลุด แล้ว server ตอบ 409 EKYC_PROCESSING / EKYC_SESSION_DONE
 *     → ห้ามเริ่มรอบใหม่ (จะทิ้งคำขอที่ server กำลังตรวจ) ให้ถาม GET /ekyc/status ทุก ~3 วินาที จนตรวจเสร็จ
 *   - ได้รหัส EKYC_ALREADY_VERIFIED / EKYC_PENDING_REVIEW → รู้ผลเลยโดยไม่ต้องรอ
 * ผลที่สรุปจากสถานะมี source = 'status' (ไม่มีคะแนน ไม่บอกเวลาที่ใช้)
 */

import type { ApiResult } from '@/services/api/client';
import type { EkycDecision, EkycFaceResult, EkycStatus } from '@/services/api/ekycApi';

/** รหัส error ที่หมายถึง "ไปดูผลที่หน้าผล" แทนการเริ่มรอบใหม่ */
export const RESULT_CODES = [
  'EKYC_ALREADY_VERIFIED',
  'EKYC_PENDING_REVIEW',
  'EKYC_PROCESSING',
  'EKYC_SESSION_DONE',
  'EKYC_TOO_MANY_ATTEMPTS',
] as const;

export const isResultCode = (code: string | null | undefined): boolean =>
  !!code && (RESULT_CODES as readonly string[]).includes(code);

/** รหัสที่ต้องรอ server ตรวจให้เสร็จก่อน (ถามสถานะซ้ำ) */
export const isWaitCode = (code: string | null | undefined): boolean =>
  code === 'EKYC_PROCESSING' || code === 'EKYC_SESSION_DONE';

const summary = (decision: EkycDecision, base: Partial<EkycFaceResult> = {}): EkycFaceResult => ({
  decision,
  scores: { face_match: null, liveness: null, ocr: null, card_real: null },
  reasons: [],
  reason_items: [],
  message: null,
  kyc_status: decision === 'approved' ? 'approved' : decision === 'review' ? 'pending' : decision === 'rejected' ? 'rejected' : 'none',
  attempts_left: 0,
  ...base,
  source: 'status',
});

/**
 * รหัส error ที่บอกผลได้ทันที → ผลสรุป · อื่นๆ = null
 * @example decisionForCode('EKYC_ALREADY_VERIFIED')?.decision // 'approved'
 */
export const decisionForCode = (code: string | null | undefined): EkycFaceResult | null => {
  if (code === 'EKYC_ALREADY_VERIFIED') return summary('approved');
  if (code === 'EKYC_PENDING_REVIEW') return summary('review');
  return null;
};

/**
 * สถานะล่าสุด → ผลสรุป (ยังไม่มีผล/ยังตรวจอยู่ = null)
 * ลำดับ: ยืนยันแล้ว → รอเจ้าหน้าที่ → ไม่ผ่าน → ถ่ายใหม่
 */
export const decisionFromStatus = (status: EkycStatus | null | undefined): EkycFaceResult | null => {
  if (!status || status.processing) return null;
  let decision: EkycDecision | null = null;
  if (status.verified) decision = 'approved';
  else if (status.kyc_status === 'pending') decision = 'review';
  else if (status.kyc_status === 'rejected') decision = 'rejected';
  else if (status.last_decision === 'retake' || status.last_decision === 'rejected') decision = status.last_decision;
  if (!decision) return null;
  return summary(decision, {
    reasons: status.reasons,
    reason_items: status.reason_items,
    message: status.message,
    kyc_status: status.kyc_status,
    attempts_left: status.attempts_left,
  });
};

export type WaitOutcome =
  /** ตรวจเสร็จแล้ว: status = สถานะล่าสุด (null = รู้ผลจากรหัส error) · decision = ผลสรุป (null = ยังไม่เคยมีผล) */
  | { kind: 'done'; status: EkycStatus | null; decision: EkycFaceResult | null }
  /** ครบเวลาแล้วยังตรวจไม่เสร็จ / ถามสถานะไม่สำเร็จตลอด */
  | { kind: 'timeout'; status: EkycStatus | null }
  /** หน้าจอถูกปิดระหว่างรอ */
  | { kind: 'cancelled' };

export interface WaitOptions {
  fetchStatus: () => Promise<ApiResult<EkycStatus>>;
  /** รอ ms (หน้าจอส่งตัวที่ถูกล้างตอนออกจากหน้ามา) */
  sleep: (ms: number) => Promise<void>;
  /** true = เลิกรอ (ออกจากหน้าแล้ว) */
  isCancelled: () => boolean;
  /** ถามสถานะทุกกี่ ms (ค่าเริ่มต้น 3 วินาที) */
  intervalMs?: number;
  /** รอนานสุดกี่ ms (ค่าเริ่มต้น 60 วินาที) */
  timeoutMs?: number;
  now?: () => number;
}

/**
 * ถาม GET /ekyc/status ซ้ำจนกว่า processing = false (หรือครบเวลา/ออกจากหน้า)
 * ถามไม่สำเร็จ (เน็ตหลุด) = ลองใหม่รอบถัดไป · ได้ EKYC_ALREADY_VERIFIED / EKYC_PENDING_REVIEW = จบทันที
 */
export const waitWhileProcessing = async ({
  fetchStatus,
  sleep,
  isCancelled,
  intervalMs = 3000,
  timeoutMs = 60_000,
  now = Date.now,
}: WaitOptions): Promise<WaitOutcome> => {
  const deadline = now() + timeoutMs;
  let last: EkycStatus | null = null;
  for (;;) {
    if (isCancelled()) return { kind: 'cancelled' };
    const res = await fetchStatus();
    if (isCancelled()) return { kind: 'cancelled' };
    if (res.success) {
      last = res.data;
      if (!res.data.processing) return { kind: 'done', status: res.data, decision: decisionFromStatus(res.data) };
    } else {
      const known = decisionForCode(res.code);
      if (known) return { kind: 'done', status: null, decision: known };
    }
    if (now() + intervalMs > deadline) return { kind: 'timeout', status: last };
    await sleep(intervalMs);
  }
};
