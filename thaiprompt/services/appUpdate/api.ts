/**
 * ระบบอัปเดตแอปในตัว — เรียก API
 *
 *   GET /app/update?platform=android&build=<versionCode ที่ติดตั้งอยู่>
 *   ใช้ได้ทั้งล็อกอินและไม่ล็อกอิน · header X-App-Build ติดไปจาก apiClient อยู่แล้ว
 *
 * คืนข้อมูลดิบ (ส่วน data) — store แปลงด้วย normalizeUpdateResponse() อีกชั้น
 */

import { apiGet, type ApiResult } from '@/services/api/client';

/** ตรวจเวอร์ชันล่าสุด (timeout สั้นกว่าปกติ — ตรวจเบื้องหลังห้ามค้างนาน) */
export const fetchAppUpdate = (installedBuild: number): Promise<ApiResult<unknown>> =>
  apiGet<unknown>(
    '/app/update',
    { platform: 'android', build: installedBuild },
    { timeout: 15000, fallbackMessage: 'ตรวจสอบเวอร์ชันไม่สำเร็จ ตรวจสอบอินเทอร์เน็ตแล้วลองใหม่อีกครั้ง' }
  );
