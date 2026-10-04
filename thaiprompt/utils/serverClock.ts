/**
 * นาฬิกาของ server — แก้เวลาเครื่องเพี้ยน (clock skew) สำหรับตัวนับถอยหลังที่ server เป็นคนตัดสิน
 * (QR ส่งมอบหมดอายุ · เวลารอลูกค้าก่อนถ่ายรูปรอบ 2 · เวลาปลดเงินอัตโนมัติ)
 *
 * หลักการ
 *   - server ส่ง data.server_now (ISO-8601) มากับ payload การส่งมอบ
 *   - offset = เวลา server − เวลาเครื่อง ณ จุดกึ่งกลางของ request (หักครึ่งหนึ่งของเวลาเดินทางไป-กลับ)
 *   - "ตอนนี้ตามเวลา server" = Date.now() + offset
 *   - ค่าแปลก (ไม่ใช่วันที่ / ห่างกันเกิน 2 วัน) → ไม่เชื่อ ใช้ offset 0 แทน (ห้ามพาตัวนับไปไกล)
 *
 * @example
 * const sent = Date.now();
 * const res = await apiGet(...);
 * const offset = clockOffsetFrom(res.data.server_now, sent, Date.now());
 * const left = msUntil(handover.wait_until, offset); // มิลลิวินาทีที่เหลือตามเวลา server
 */

/** ต่างจากเวลาเครื่องเกินนี้ = ข้อมูลผิดปกติ ไม่ใช้ */
export const MAX_CLOCK_OFFSET_MS = 2 * 24 * 60 * 60 * 1000;

/** แปลง ISO → มิลลิวินาที (ไม่ใช่วันที่ = null) */
export const parseIsoMs = (iso: unknown): number | null => {
  if (typeof iso !== 'string' || iso.length < 10) return null;
  const t = Date.parse(iso);
  return Number.isFinite(t) ? t : null;
};

/**
 * คำนวณ offset (มิลลิวินาที) = เวลา server − เวลาเครื่อง
 *
 * @param serverNowIso data.server_now จาก server
 * @param sentAtMs เวลาเครื่องตอนส่ง request (ไม่ทราบ = ใช้ receivedAtMs)
 * @param receivedAtMs เวลาเครื่องตอนได้คำตอบ (ค่าเริ่มต้น Date.now())
 * @returns offset หรือ null เมื่อข้อมูลใช้ไม่ได้
 */
export const clockOffsetFrom = (
  serverNowIso: unknown,
  sentAtMs?: number | null,
  receivedAtMs: number = Date.now()
): number | null => {
  const server = parseIsoMs(serverNowIso);
  if (server === null || !Number.isFinite(receivedAtMs)) return null;
  const sent = typeof sentAtMs === 'number' && Number.isFinite(sentAtMs) && sentAtMs <= receivedAtMs ? sentAtMs : receivedAtMs;
  // server ตอบตอนประมาณกึ่งกลางของการเดินทางไป-กลับ
  const deviceAtServerNow = sent + (receivedAtMs - sent) / 2;
  const offset = Math.round(server - deviceAtServerNow);
  return Math.abs(offset) <= MAX_CLOCK_OFFSET_MS ? offset : null;
};

/** "ตอนนี้" ตามเวลา server (offset ไม่รู้ = เวลาเครื่อง) */
export const serverNowMs = (offsetMs?: number | null, deviceNowMs: number = Date.now()): number =>
  deviceNowMs + (typeof offsetMs === 'number' && Number.isFinite(offsetMs) ? offsetMs : 0);

/**
 * เหลืออีกกี่มิลลิวินาทีถึงเวลา iso (ตามเวลา server) — ติดลบ = เลยมาแล้ว · iso ใช้ไม่ได้ = null
 */
export const msUntil = (iso: unknown, offsetMs?: number | null, deviceNowMs: number = Date.now()): number | null => {
  const target = parseIsoMs(iso);
  if (target === null) return null;
  return target - serverNowMs(offsetMs, deviceNowMs);
};

/**
 * เวลาเครื่องที่ตรงกับเวลา iso ของ server (ใช้ตั้ง setTimeout ให้ตรงกับ server)
 * iso ใช้ไม่ได้ = null
 */
export const deviceTimeFor = (iso: unknown, offsetMs?: number | null): number | null => {
  const target = parseIsoMs(iso);
  if (target === null) return null;
  return target - (typeof offsetMs === 'number' && Number.isFinite(offsetMs) ? offsetMs : 0);
};

// =====================================================
// offset ล่าสุดที่รู้ (ใช้ร่วมกันทุกหน้าจอระหว่างที่หน้าใหม่ยังไม่ได้ server_now ของตัวเอง)
// =====================================================

let lastKnownOffsetMs: number | null = null;

/** จำ offset ล่าสุด (null = ไม่เปลี่ยนค่าเดิม) */
export const rememberClockOffset = (offsetMs: number | null | undefined): void => {
  if (typeof offsetMs === 'number' && Number.isFinite(offsetMs) && Math.abs(offsetMs) <= MAX_CLOCK_OFFSET_MS) {
    lastKnownOffsetMs = offsetMs;
  }
};

/** offset ล่าสุดที่รู้ (ยังไม่เคยได้ = 0) */
export const lastClockOffset = (): number => lastKnownOffsetMs ?? 0;

/** ล้างค่า (ใช้ในเทสต์) */
export const resetClockOffsetForTest = (): void => {
  lastKnownOffsetMs = null;
};
