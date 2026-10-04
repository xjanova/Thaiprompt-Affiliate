/**
 * แจ้งเตือน "เพิ่มรูปโปรไฟล์ก่อน" เมื่อ server ตอบ 422 PROFILE_PHOTO_REQUIRED (FIXES U8)
 *
 * ตอนนี้ server ปิดการบังคับรูปถ่ายสดแล้ว (PROFILE_PHOTO_REQUIRED=false) — ความน่าเชื่อถือมาจากป้ายทอง "ยืนยันตัวตนแล้ว" (eKYC)
 * เก็บตัวจัดการนี้ไว้เผื่อเปิดกลับ: ปุ่ม "เลือกรูป" → /profile-photo?from=... (รูปอะไรก็ได้ จากคลังหรือกล้อง)
 *
 * @example
 * if (isProfilePhotoRequired(res)) { promptProfilePhoto('rider'); return; }
 */

import { Alert } from 'react-native';
import { router } from 'expo-router';

/** หน้าที่ต้องการรูปโปรไฟล์ (ใช้เลือกข้อความ + ปุ่มกลับไปทำต่อในหน้าถ่ายรูป) */
export type ProfilePhotoContext = 'checkout' | 'rider' | 'seller';

const MESSAGE: Record<ProfilePhotoContext, string> = {
  checkout: 'ก่อนสั่งซื้อครั้งแรก เพิ่มรูปโปรไฟล์ก่อนนะ เลือกรูปอะไรก็ได้จากคลังรูปหรือถ่ายใหม่',
  rider: 'ก่อนเริ่มรับงาน เพิ่มรูปโปรไฟล์ก่อนนะ เลือกรูปอะไรก็ได้จากคลังรูปหรือถ่ายใหม่',
  seller: 'ก่อนส่งคำขอเปิดร้าน เพิ่มรูปโปรไฟล์ก่อนนะ เลือกรูปอะไรก็ได้จากคลังรูปหรือถ่ายใหม่',
};

/** ผลจาก API นี้คือ "ต้องถ่ายรูปโปรไฟล์ก่อน" หรือไม่ */
export const isProfilePhotoRequired = (result: { success: boolean; code?: string } | null | undefined): boolean =>
  !!result && result.success === false && result.code === 'PROFILE_PHOTO_REQUIRED';

/** แสดง Alert พร้อมปุ่ม "เลือกรูป" → หน้าเปลี่ยนรูปโปรไฟล์ */
export const promptProfilePhoto = (context: ProfilePhotoContext): void => {
  Alert.alert('เพิ่มรูปโปรไฟล์ก่อนนะ', MESSAGE[context], [
    { text: 'ไว้ก่อน', style: 'cancel' },
    { text: 'เลือกรูป', onPress: () => router.push(`/profile-photo?from=${context}` as never) },
  ]);
};
