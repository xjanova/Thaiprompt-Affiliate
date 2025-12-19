/**
 * User Utilities - ฟังก์ชันช่วยเหลือเกี่ยวกับข้อมูลผู้ใช้
 * ใช้ทั่วทั้งแอพเพื่อความสอดคล้อง
 * ⭐ เพิ่ม try-catch เพื่อป้องกัน crash
 */

import { API_BASE_URL } from '@/constants';

/**
 * สร้าง URL สำหรับ avatar จาก path ที่ได้จาก API
 *
 * รองรับหลาย format:
 * - Full URL: https://example.com/avatar.jpg
 * - Relative path: /storage/avatars/xxx.jpg
 * - Short path: avatars/xxx.jpg
 *
 * @param avatarPath path หรือ URL ของ avatar จาก API
 * @returns Full URL ของ avatar หรือ null ถ้าไม่มี
 */
export const getAvatarUrl = (avatarPath: string | null | undefined): string | null => {
  try {
    // ตรวจสอบว่ามีค่าและเป็น string
    if (!avatarPath || typeof avatarPath !== 'string') {
      return null;
    }

    // ถ้าเป็น full URL ใช้เลย
    if (avatarPath.startsWith('http')) {
      return avatarPath;
    }

    // สร้าง base URL จาก API_BASE_URL (ลบ /api/v1 ออก)
    const baseUrl = (API_BASE_URL || 'https://main.thaiprompt.online/api/v1')
      .replace(/\/api\/v\d+$/, '')
      .replace(/\/api$/, '');

    let path = avatarPath;

    // ถ้า path ไม่ขึ้นต้นด้วย / ให้เพิ่ม
    if (!path.startsWith('/')) {
      path = '/' + path;
    }

    // ถ้า path ยังไม่ขึ้นต้นด้วย /storage ให้เพิ่ม
    if (!path.startsWith('/storage')) {
      path = '/storage' + path;
    }

    return `${baseUrl}${path}`;
  } catch (error) {
    console.error('getAvatarUrl error:', error);
    return null;
  }
};

/**
 * สร้างตัวอักษรย่อจากชื่อผู้ใช้ (สำหรับแสดงเป็น avatar placeholder)
 *
 * @param name ชื่อผู้ใช้
 * @returns ตัวอักษรตัวแรกเป็นตัวพิมพ์ใหญ่
 */
export const getAvatarInitial = (name: string | null | undefined): string => {
  try {
    if (!name || typeof name !== 'string') return 'U';
    return name.charAt(0).toUpperCase();
  } catch {
    return 'U';
  }
};

/**
 * Format Member ID เป็นรูปแบบที่อ่านง่าย
 * เช่น 123 -> TP000123
 *
 * @param id User ID
 * @returns Formatted member ID
 */
export const formatMemberId = (id: number | null | undefined): string => {
  if (!id) return '';
  return `TP${id.toString().padStart(6, '0')}`;
};
