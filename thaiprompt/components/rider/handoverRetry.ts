/**
 * ตัวตั้งเวลาส่งรูปรอบ 2 ซ้ำอัตโนมัติ (ไรเดอร์รอบ 2 — แก้ B1) — ฟังก์ชันล้วน ไม่แตะ React/เครือข่าย ทดสอบได้
 *
 * ที่มา: server ตอบ WAIT_NOT_OVER → แอปเก็บรูปไว้แล้วส่งให้เองเมื่อครบเวลา
 * บั๊กเดิม: ส่งซ้ำแล้วเน็ตหลุด/5xx/429 → ไม่นับครั้ง ไม่เลื่อนเวลา → ยิง multipart วนถี่ + Alert ซ้อนกัน
 *
 * กติกา
 *   - ทุกครั้งที่ส่งไม่สำเร็จ (ยังไม่ครบเวลา / เน็ต / เซิร์ฟเวอร์ / ถูกจำกัดความถี่) → นับครั้ง + เลื่อนเวลาลองครั้งถัดไป
 *     แบบทวีคูณ 5 วิ → 15 วิ → 45 วิ → 2:15 → 5 นาที (เพดาน) — ไม่มีทางยิงซ้ำทันทีหลังล้ม
 *   - ถูกจำกัดความถี่ (429) พักอย่างน้อย 60 วิ
 *   - ล้มครบ AUTO_RETRY_MAX ครั้ง → หยุดส่งอัตโนมัติ ให้ไรเดอร์กด "ส่งรูปอีกครั้ง" เอง
 *   - ไม่ส่งก่อนเวลาครบกำหนดของ server (wait_until แปลงเป็นเวลาเครื่องแล้ว) + เผื่อเวลาเดินทางของ request
 *
 * @example
 * let state = INITIAL_AUTO_RETRY;
 * state = recordAutoRetryFailure(state, 'network', Date.now()); // ลองใหม่อีก 5 วิ
 * const at = nextAutoRetryAt(state, dueDeviceMs);                // null = หยุดส่งอัตโนมัติแล้ว
 */

/** ส่งอัตโนมัติไม่สำเร็จได้ไม่เกินกี่ครั้ง (เกินนี้ให้ไรเดอร์กด "ส่งรูปอีกครั้ง" เอง) */
export const AUTO_RETRY_MAX = 6;
/** พักครั้งแรกหลังส่งไม่สำเร็จ */
export const AUTO_RETRY_BASE_MS = 5_000;
/** ตัวคูณของเวลาพักแต่ละครั้ง */
export const AUTO_RETRY_FACTOR = 3;
/** เพดานเวลาพัก */
export const AUTO_RETRY_CAP_MS = 5 * 60_000;
/** ถูกจำกัดความถี่ (429) → พักอย่างน้อยเท่านี้ */
export const AUTO_RETRY_RATE_LIMIT_MS = 60_000;
/** ส่งหลังครบเวลาของ server + เผื่อเวลาเดินทางของ request */
export const AUTO_RETRY_SLACK_MS = 1_500;

/** สาเหตุที่ส่งไม่สำเร็จแบบที่ "ลองรูปเดิมซ้ำได้" */
export type AutoRetryFailure = 'wait_not_over' | 'network' | 'server' | 'rate_limited';

export interface AutoRetryState {
  /** ส่งไม่สำเร็จไปแล้วกี่ครั้ง (นับทุกสาเหตุที่ลองซ้ำได้) */
  attempts: number;
  /** ห้ามส่งอัตโนมัติก่อนเวลานี้ (เวลาเครื่อง ms) */
  notBefore: number;
  /** สาเหตุล่าสุด — ใช้แสดงสถานะในหน้า (ไม่เด้ง Alert ระหว่างส่งอัตโนมัติ) */
  lastFailure: AutoRetryFailure | null;
}

export const INITIAL_AUTO_RETRY: AutoRetryState = Object.freeze({ attempts: 0, notBefore: 0, lastFailure: null });

/**
 * แยกผลการส่งรูปที่ล้ม → สาเหตุที่ลองซ้ำได้ (null = รูปนี้ใช้ไม่ได้แล้ว ห้ามลองซ้ำ เช่น ห่างจุดส่ง/สถานะเปลี่ยน)
 *
 * @param result ผลจาก API (status 0 = ไม่ถึง server)
 * @param kind รูปรอบไหน — WAIT_NOT_OVER ลองซ้ำได้เฉพาะรูปรอบ 2
 */
export const classifyUploadFailure = (
  result: { status: number; code: string },
  kind: 'arrival' | 'waited'
): AutoRetryFailure | null => {
  if (result.status === 429 || result.code === 'TOO_MANY_REQUESTS') return 'rate_limited';
  if (result.status === 0) return 'network';
  if (result.status >= 500) return 'server';
  if (result.code === 'WAIT_NOT_OVER' && kind === 'waited') return 'wait_not_over';
  return null;
};

/** สาเหตุนี้คือปัญหาเน็ต/เซิร์ฟเวอร์ (ไม่ใช่แค่ยังไม่ครบเวลา) */
export const isTransportFailure = (failure: AutoRetryFailure | null): boolean =>
  failure === 'network' || failure === 'server' || failure === 'rate_limited';

/**
 * เวลาพักก่อนลองครั้งถัดไป หลังส่งไม่สำเร็จครั้งที่ attempts (เริ่มที่ 1)
 * 5 วิ, 15 วิ, 45 วิ, 135 วิ, 300 วิ (เพดาน) … · 429 อย่างน้อย 60 วิ
 */
export const autoRetryDelayMs = (attempts: number, failure: AutoRetryFailure): number => {
  const n = Number.isFinite(attempts) ? Math.max(1, Math.floor(attempts)) : 1;
  const backoff = Math.min(AUTO_RETRY_CAP_MS, AUTO_RETRY_BASE_MS * Math.pow(AUTO_RETRY_FACTOR, n - 1));
  return failure === 'rate_limited' ? Math.max(AUTO_RETRY_RATE_LIMIT_MS, backoff) : backoff;
};

/** บันทึกว่าส่งไม่สำเร็จอีกครั้ง → นับครั้ง + เลื่อนเวลาลองครั้งถัดไป (คืน state ใหม่ ไม่แก้ของเดิม) */
export const recordAutoRetryFailure = (
  state: AutoRetryState,
  failure: AutoRetryFailure,
  nowMs: number
): AutoRetryState => {
  const attempts = Math.max(0, state.attempts) + 1;
  return { attempts, notBefore: nowMs + autoRetryDelayMs(attempts, failure), lastFailure: failure };
};

/** ล้มครบโควตาแล้ว → หยุดส่งอัตโนมัติ */
export const autoRetryExhausted = (state: AutoRetryState): boolean => state.attempts >= AUTO_RETRY_MAX;

/**
 * เวลาเครื่องที่ควรส่งรูปที่รอไว้อัตโนมัติ
 *
 * @param dueDeviceMs เวลาเครื่องที่ตรงกับ wait_until ของ server (null = ไม่รู้ → ใช้แค่เวลาพัก)
 * @returns null = ไม่ส่งอัตโนมัติแล้ว (ล้มครบโควตา) ให้ไรเดอร์กดเอง
 */
export const nextAutoRetryAt = (
  state: AutoRetryState,
  dueDeviceMs: number | null,
  slackMs: number = AUTO_RETRY_SLACK_MS
): number | null => {
  if (autoRetryExhausted(state)) return null;
  const due = dueDeviceMs !== null && Number.isFinite(dueDeviceMs) ? dueDeviceMs + slackMs : 0;
  return Math.max(due, state.notBefore);
};

/** ข้อความสาเหตุภาษาไทย (สั้น ใช้ต่อหน้าประโยค) */
export const autoRetryReasonText = (failure: AutoRetryFailure | null): string => {
  switch (failure) {
    case 'network':
      return 'สัญญาณเน็ตไม่เสถียร';
    case 'server':
      return 'ระบบไม่ว่างชั่วคราว';
    case 'rate_limited':
      return 'ส่งถี่เกินไป ระบบให้รอสักครู่';
    case 'wait_not_over':
      return 'ระบบยังไม่ครบเวลารอลูกค้า';
    default:
      return '';
  }
};
