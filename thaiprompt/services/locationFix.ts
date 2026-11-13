/**
 * ตัวตัดสิน "พิกัดนี้ใช้ได้ไหม" (ฟังก์ชันล้วน ไม่แตะ expo — ทดสอบด้วย jest ได้)
 *
 * ทำไมต้องมี: เครื่องหา GPS ไม่ทันเวลา แอปจะถอยไปใช้ "ตำแหน่งล่าสุดของเครื่อง" ซึ่งอาจเก่าหลายนาที
 * (ไรเดอร์ขี่มาอีก 2 กม. แล้ว / ร้านรถเข็นย้ายจุดแล้ว) ถ้าส่งพิกัดเก่าเป็นพิกัดปัจจุบัน:
 *   - ไรเดอร์ยืนหน้าห้องลูกค้า แต่ถูกปฏิเสธว่า "ห่างจุดส่ง 600 ม."
 *   - หมุดร้าน/หมุดที่อยู่ไปตกคนละซอย → ค่าส่งผิด ไรเดอร์ไปรับของผิดที่
 * งานที่ผูกกับเงิน/หมุดจึงต้องระบุอายุพิกัดที่ยอมรับ (maxAgeMs) เสมอ
 */

/** พิกัดสำหรับ "ปักหมุด / ส่งมอบของ / ส่งสำเร็จ" — ต้องเป็นตำแหน่งตอนนี้จริงๆ */
export const FRESH_FIX_MAX_AGE_MS = 30_000;

/** พิกัดสำหรับ "รับงาน / เปิดรับงาน" — เซิร์ฟเวอร์ใช้ตัดสินว่าอยู่ใกล้จุดรับของไหม */
export const RECENT_FIX_MAX_AGE_MS = 60_000;

/** ค่าเดิมของแอป: งานที่แค่ดูรายการ/ค้นหาใกล้ตัว ใช้ตำแหน่งล่าสุดของเครื่องได้ถึง 5 นาที */
export const BROWSE_FIX_MAX_AGE_MS = 5 * 60_000;

/** ความแม่นยำ (เมตร) ที่เกินนี้ถือว่า "หยาบ" — ควรเตือนก่อนใช้ปักหมุด */
export const COARSE_ACCURACY_M = 100;

/** รูปย่อของ LocationObject (expo-location) เท่าที่ใช้ */
export interface RawFix {
  coords: { latitude: number; longitude: number; accuracy?: number | null };
  /** เวลาที่วัด (มิลลิวินาทีนับจาก epoch) */
  timestamp?: number;
}

export interface PickedFix {
  latitude: number;
  longitude: number;
  accuracy?: number;
  /** อายุของพิกัด (มิลลิวินาที) — ไม่รู้เวลาที่วัด = undefined */
  ageMs?: number;
  /** true = มาจากตำแหน่งล่าสุดของเครื่อง (ไม่ได้วัดใหม่) */
  fromCache: boolean;
}

const usable = (fix: RawFix | null | undefined): fix is RawFix => {
  if (!fix || !fix.coords) return false;
  const { latitude, longitude } = fix.coords;
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return false;
  if (Math.abs(latitude) > 90 || Math.abs(longitude) > 180) return false;
  // 0,0 = ค่าว่างที่หลุดมา (กลางทะเล) ไม่ใช่พิกัดจริงในไทย
  return !(latitude === 0 && longitude === 0);
};

/** อายุของพิกัด ณ เวลา nowMs (เวลาเครื่องเพี้ยนจนติดลบ = 0) */
export const fixAgeMs = (fix: RawFix, nowMs: number): number | undefined =>
  typeof fix.timestamp === 'number' && Number.isFinite(fix.timestamp) && fix.timestamp > 0
    ? Math.max(0, nowMs - fix.timestamp)
    : undefined;

/**
 * เลือกพิกัดที่จะใช้: วัดใหม่ได้ → ใช้เลย · ไม่ได้ → ตำแหน่งล่าสุดของเครื่อง "เฉพาะที่ไม่เก่าเกิน maxAgeMs"
 * ตำแหน่งล่าสุดที่ไม่รู้เวลาที่วัด ถือว่าเก่า (ใช้ไม่ได้)
 */
export const pickFix = (
  fresh: RawFix | null | undefined,
  lastKnown: RawFix | null | undefined,
  nowMs: number,
  maxAgeMs: number
): PickedFix | null => {
  if (usable(fresh)) {
    const accuracy = fresh.coords.accuracy;
    return {
      latitude: fresh.coords.latitude,
      longitude: fresh.coords.longitude,
      accuracy: typeof accuracy === 'number' && accuracy >= 0 ? accuracy : undefined,
      ageMs: fixAgeMs(fresh, nowMs),
      fromCache: false,
    };
  }

  if (!usable(lastKnown)) return null;
  const age = fixAgeMs(lastKnown, nowMs);
  if (age === undefined || age > maxAgeMs) return null;

  const accuracy = lastKnown.coords.accuracy;
  return {
    latitude: lastKnown.coords.latitude,
    longitude: lastKnown.coords.longitude,
    accuracy: typeof accuracy === 'number' && accuracy >= 0 ? accuracy : undefined,
    ageMs: age,
    fromCache: true,
  };
};

/** ความแม่นยำหยาบเกินจะใช้ปักหมุดโดยไม่เตือน */
export const isCoarseFix = (accuracy: number | null | undefined): boolean =>
  typeof accuracy === 'number' && accuracy > COARSE_ACCURACY_M;

/**
 * อยู่ในรัศมีจุดส่งหรือไม่ — กติกาเดียวกับเซิร์ฟเวอร์ (HandoverService::assertWithinGeofence: ห่าง > รัศมี = ไม่ผ่าน)
 * ไม่มีส่วนเผื่อความคลาดเคลื่อน: เซิร์ฟเวอร์ตัดที่รัศมีพอดี แอปเผื่อให้ = ถ่ายรูปเสร็จแล้วโดนปฏิเสธ ต้องถ่ายใหม่
 */
export const withinGeofence = (distanceM: number | null | undefined, geofenceM: number): boolean =>
  distanceM === null || distanceM === undefined || !Number.isFinite(distanceM) || distanceM <= geofenceM;

/**
 * พิกัดที่จะแนบไปกับรูปส่งมอบ: ใช้พิกัดหลังถ่ายถ้ายังอยู่ในรัศมี ไม่งั้นใช้พิกัดก่อนถ่าย (ที่ตรวจแล้วว่าอยู่ในรัศมี)
 * ทั้งสองค่าเป็นพิกัดที่เครื่องวัดจริงในช่วงส่งมอบ — กัน GPS แกว่งตอนยืนถ่ายรูปแล้วรูปถูกทิ้ง
 */
export const chooseHandoverFix = <T extends { distanceM: number | null }>(before: T, after: T | null, geofenceM: number): T => {
  if (!after) return before;
  return withinGeofence(after.distanceM, geofenceM) ? after : before;
};
