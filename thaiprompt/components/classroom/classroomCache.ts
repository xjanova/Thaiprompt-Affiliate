/**
 * แคชรายการหลักสูตรห้องเรียน (ต่อผู้ใช้ · อายุ 60 วิ) — ใช้ร่วมกันระหว่างการ์ดชวนอบรมบนแดชบอร์ดกับหน้าห้องเรียน
 *
 * - เปิดแดชบอร์ดไรเดอร์/ร้าน/โปรไฟล์ซ้ำๆ ไม่ยิง API ทุกครั้ง · หลายการ์ดขอพร้อมกัน = ยิงครั้งเดียว (แชร์ Promise)
 * - server ยังไม่มีห้องเรียน (404) → จำผลนั้นไว้ด้วย (ไม่ยิงซ้ำทุกครั้งที่เปิดโปรไฟล์) · เน็ตหลุด/5xx ไม่จำ
 * - สอบผ่าน / ดูวิดีโอจบ → invalidateClassroomCourses() ให้รอบหน้าโหลดใหม่
 * - เปลี่ยนบัญชี → แคชของบัญชีเดิมไม่ถูกใช้ (ผูก userId)
 */

import { getClassroomCourses, type ClassroomCourse } from '@/services/api/classroomApi';
import type { ApiResult } from '@/services/api/client';

const TTL_MS = 60_000;

let cache: { userId: number; at: number; result: ApiResult<ClassroomCourse[]> } | null = null;
let inflight: { userId: number; promise: Promise<ApiResult<ClassroomCourse[]>> } | null = null;

const fresh = (userId: number): boolean => !!cache && cache.userId === userId && Date.now() - cache.at < TTL_MS;

/** โหลดหลักสูตร (ใช้แคชถ้ายังไม่หมดอายุ · force = โหลดใหม่เสมอ) */
export const loadClassroomCourses = async (
  userId: number,
  options: { force?: boolean } = {}
): Promise<ApiResult<ClassroomCourse[]>> => {
  if (!options.force && cache && fresh(userId)) return cache.result;
  if (inflight && inflight.userId === userId) return inflight.promise;

  const promise = getClassroomCourses().then((result) => {
    if (result.success || result.status === 404) cache = { userId, at: Date.now(), result };
    return result;
  });
  inflight = { userId, promise };
  try {
    return await promise;
  } finally {
    if (inflight?.promise === promise) inflight = null;
  }
};

/** หลักสูตรในแคช (ไม่ยิง API) — ไม่มี/หมดอายุ/คนละบัญชี/server ไม่มีห้องเรียน = null */
export const peekClassroomCourses = (userId: number): ClassroomCourse[] | null =>
  cache && fresh(userId) && cache.result.success ? cache.result.data : null;

/** ล้างแคช (สอบผ่าน · ดูบทเรียนจบ · ออกจากระบบ) */
export const invalidateClassroomCourses = (): void => {
  cache = null;
};
