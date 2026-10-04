/**
 * ที่เก็บรูปส่งมอบของไรเดอร์ในเครื่อง (ต่องาน) — AsyncStorage key `tp_rider_handover_v1:{jobId}`
 *
 * - pending = รูปที่ถ่ายแล้วแต่ยังส่งไม่สำเร็จ (เน็ตหลุด/แอปถูกปิด) → กด "ส่งรูปอีกครั้ง" ได้ไม่ต้องถ่ายใหม่
 *   waitedUntil = server ตอบ WAIT_NOT_OVER → ส่งรูปเดิมให้อัตโนมัติเมื่อครบเวลา (เวลา server)
 * - arrival = รูปรอบ 1 ที่ส่งสำเร็จแล้ว (แสดงรูปย่อระหว่างรอลูกค้า)
 * - ไฟล์รูปเป็นไฟล์ชั่วคราวของกล้อง (file://) — ลบทิ้งเมื่อไม่ใช้แล้ว (FIXES M1):
 *   ส่งรูปรอบ 2 สำเร็จ / งานจบ / ทิ้งรูป / งานไม่ได้อยู่ระหว่างส่งแล้ว (purgeHandoverCache)
 * - ข้อมูลในเครื่องเป็นแค่ความสะดวก — สถานะจริงอยู่ที่ server เสมอ · อ่าน/เขียน/ลบไม่ได้ = เงียบ ห้ามล้ม
 */

import AsyncStorage from '@react-native-async-storage/async-storage';
import * as FileSystem from 'expo-file-system/legacy';

export const HANDOVER_STORAGE_PREFIX = 'tp_rider_handover_v1:';

export type HandoverPhotoKind = 'arrival' | 'waited';

/** รูปที่ถ่ายแล้วแต่ยังส่งไม่สำเร็จ */
export interface PendingHandoverPhoto {
  kind: HandoverPhotoKind;
  uri: string;
  latitude: number;
  longitude: number;
  distanceM: number | null;
  takenAt: string;
  /** server บอกว่ายังไม่ครบเวลารอ (ISO ตามเวลา server) → ส่งรูปนี้อัตโนมัติเมื่อถึงเวลา */
  waitUntil?: string | null;
}

/** รูปรอบ 1 ที่ส่งสำเร็จ (โชว์รูปย่อ + ระยะตอนถ่าย) */
export interface ArrivalHandoverShot {
  uri: string;
  distanceM: number | null;
  takenAt: string;
}

export interface StoredHandoverState {
  pending?: PendingHandoverPhoto | null;
  arrival?: ArrivalHandoverShot | null;
}

const keyFor = (jobId: number): string => `${HANDOVER_STORAGE_PREFIX}${jobId}`;

/** ลบไฟล์รูปชั่วคราวในเครื่อง (เฉพาะ file:// · เงียบ) */
export const deleteLocalPhoto = (uri: string | null | undefined): void => {
  if (!uri || typeof uri !== 'string' || !uri.startsWith('file://')) return;
  FileSystem.deleteAsync(uri, { idempotent: true }).catch(() => {});
};

const urisOf = (state: StoredHandoverState | null | undefined): string[] =>
  [state?.pending?.uri, state?.arrival?.uri].filter((u): u is string => typeof u === 'string' && u.length > 0);

export const readHandoverCache = async (jobId: number): Promise<StoredHandoverState> => {
  try {
    const raw = await AsyncStorage.getItem(keyFor(jobId));
    if (!raw) return {};
    const parsed = JSON.parse(raw);
    return parsed && typeof parsed === 'object' ? (parsed as StoredHandoverState) : {};
  } catch {
    return {};
  }
};

export const writeHandoverCache = async (jobId: number, value: StoredHandoverState): Promise<void> => {
  try {
    if (!value.pending && !value.arrival) {
      await AsyncStorage.removeItem(keyFor(jobId));
    } else {
      await AsyncStorage.setItem(keyFor(jobId), JSON.stringify(value));
    }
  } catch {
    // เก็บไม่ได้ก็ไม่เป็นไร (แค่ความสะดวกตอนเน็ตหลุด)
  }
};

/**
 * ล้างของงานนี้ทั้งหมด (ข้อมูล + ไฟล์รูป) — งานจบ / วางของครบแล้ว
 * @param keepUris ไฟล์ที่ยังใช้อยู่ ห้ามลบ (เช่น รูปที่กำลังแสดง)
 */
export const clearHandoverCache = async (jobId: number, keepUris: string[] = []): Promise<void> => {
  const stored = await readHandoverCache(jobId);
  urisOf(stored)
    .filter((uri) => !keepUris.includes(uri))
    .forEach(deleteLocalPhoto);
  try {
    await AsyncStorage.removeItem(keyFor(jobId));
  } catch {
    // ลบไม่ได้ก็ไม่เป็นไร
  }
};

/** เลขงานจาก key (ไม่ใช่ key ของเรา = null) */
export const jobIdFromHandoverKey = (key: string): number | null => {
  if (!key.startsWith(HANDOVER_STORAGE_PREFIX)) return null;
  const id = Number(key.slice(HANDOVER_STORAGE_PREFIX.length));
  return Number.isInteger(id) && id > 0 ? id : null;
};

/**
 * ล้างของงานที่ไม่ได้อยู่ระหว่างส่งแล้ว (เก็บไว้เฉพาะงานที่ยังส่งอยู่) — เรียกตอนรู้งานปัจจุบันจาก server
 * @param activeJobId งานที่กำลังส่งอยู่ (null = ไม่มีงานค้าง → ล้างทั้งหมด)
 */
export const purgeHandoverCache = async (activeJobId: number | null | undefined): Promise<void> => {
  try {
    const keys = await AsyncStorage.getAllKeys();
    const stale = keys.filter((key) => {
      const id = jobIdFromHandoverKey(key);
      return id !== null && id !== activeJobId;
    });
    for (const key of stale) {
      const id = jobIdFromHandoverKey(key);
      if (id !== null) await clearHandoverCache(id);
    }
  } catch {
    // ล้างไม่ได้ก็ไม่เป็นไร — ลองใหม่รอบหน้า
  }
};
