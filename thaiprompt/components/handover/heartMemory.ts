/**
 * จำว่าออเดอร์ไหนให้หัวใจไรเดอร์ไปแล้ว (1 ดวงต่อออเดอร์) — กันการ์ดชวนให้หัวใจซ้ำเมื่อกลับมาเปิดหน้า (FIXES M6)
 *
 * - server ไม่บอกใน PersonCard ว่าออเดอร์นี้ให้หัวใจแล้วหรือยัง → แอปจำเองหลังกดสำเร็จ / server ตอบ already
 * - จำในหน่วยความจำ (ใช้ทันที) + AsyncStorage (ข้ามการเปิดแอป) · เก็บล่าสุดไม่เกิน 200 ออเดอร์
 * - เก็บแค่ "ให้แล้ว" + ตัวเลขล่าสุด ไม่มีข้อมูลส่วนตัว · อ่าน/เขียนไม่ได้ = เงียบ (แย่สุดคือการ์ดชวนซ้ำ แล้ว server ตอบ already)
 */

import AsyncStorage from '@react-native-async-storage/async-storage';

const STORAGE_KEY = 'tp_heart_given_v1';
const MAX_ENTRIES = 200;

export interface HeartMemoryEntry {
  hearts_from_me: number;
  can_lock: boolean;
  at: number;
}

type HeartMemory = Record<string, HeartMemoryEntry>;

let memory: HeartMemory | null = null;
let loading: Promise<HeartMemory> | null = null;

export const heartMemoryKey = (source: string, orderId: number): string => `${source}:${orderId}`;

const load = (): Promise<HeartMemory> => {
  if (memory) return Promise.resolve(memory);
  if (!loading) {
    loading = AsyncStorage.getItem(STORAGE_KEY)
      .then((raw) => {
        const parsed = raw ? JSON.parse(raw) : {};
        memory = { ...(parsed && typeof parsed === 'object' ? (parsed as HeartMemory) : {}), ...(memory || {}) };
        return memory;
      })
      .catch(() => {
        memory = memory || {};
        return memory;
      });
  }
  return loading;
};

/**
 * โหลดจากเครื่องเสร็จแล้วหรือยัง — true = peekHeartGiven เชื่อถือได้ทันที (ไม่ต้องรอ AsyncStorage)
 * ใช้กันการ์ดกระพริบปุ่ม "ให้หัวใจ" ก่อนรู้ว่าเคยให้แล้ว (B11)
 */
export const isHeartMemoryLoaded = (): boolean => memory !== null;

/** อ่านทันที (มีในหน่วยความจำแล้วเท่านั้น) */
export const peekHeartGiven = (source: string, orderId: number): HeartMemoryEntry | null =>
  memory?.[heartMemoryKey(source, orderId)] ?? null;

/** อ่านแบบรอ (โหลดจากเครื่องถ้ายังไม่เคยโหลด) */
export const readHeartGiven = async (source: string, orderId: number): Promise<HeartMemoryEntry | null> => {
  const all = await load();
  return all[heartMemoryKey(source, orderId)] ?? null;
};

/** จำว่าออเดอร์นี้ให้หัวใจแล้ว */
export const rememberHeartGiven = async (
  source: string,
  orderId: number,
  value: { hearts_from_me: number; can_lock: boolean }
): Promise<void> => {
  const all = await load();
  all[heartMemoryKey(source, orderId)] = { ...value, at: Date.now() };
  // เก็บเฉพาะล่าสุด MAX_ENTRIES รายการ
  const keys = Object.keys(all);
  if (keys.length > MAX_ENTRIES) {
    keys
      .sort((a, b) => all[a].at - all[b].at)
      .slice(0, keys.length - MAX_ENTRIES)
      .forEach((k) => delete all[k]);
  }
  try {
    await AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(all));
  } catch {
    // เก็บไม่ได้ก็ยังจำในหน่วยความจำ
  }
};
