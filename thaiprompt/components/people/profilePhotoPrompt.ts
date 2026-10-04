/**
 * แจ้งเตือน "ถ่ายรูปโปรไฟล์ก่อน" เมื่อ server ตอบ 422 PROFILE_PHOTO_REQUIRED (FIXES U8)
 *
 * server บังคับรูปโปรไฟล์ถ่ายสดเฉพาะแอปที่ส่ง X-App-Build ≥ 43 ที่:
 *   สั่งซื้อ (ร้านค้า/ตลาดสด) · ไรเดอร์กดเริ่มรับงาน · ส่งคำขอเปิดร้าน
 * ทุกจุดใช้ Alert เดียวกัน: ปุ่ม "ถ่ายรูปเลย" → /profile-photo?from=... (ถ่ายเสร็จแล้วกลับมาทำต่อ)
 *
 * @example
 * if (isProfilePhotoRequired(res)) { promptProfilePhoto('rider'); return; }
 */

import { Alert } from 'react-native';
import { router } from 'expo-router';

/** หน้าที่ต้องการรูปโปรไฟล์ (ใช้เลือกข้อความ + ปุ่มกลับไปทำต่อในหน้าถ่ายรูป) */
export type ProfilePhotoContext = 'checkout' | 'rider' | 'seller';

const MESSAGE: Record<ProfilePhotoContext, string> = {
  checkout: 'ก่อนสั่งซื้อครั้งแรก ทุกบัญชีต้องมีรูปโปรไฟล์ถ่ายสดจากกล้อง ไรเดอร์จะได้รู้ว่าส่งของถึงมือใคร',
  rider: 'ก่อนเริ่มรับงาน ไรเดอร์ต้องมีรูปโปรไฟล์ถ่ายสดจากกล้อง ลูกค้าจะได้รู้ว่าใครมาส่งของ',
  seller: 'ก่อนส่งคำขอเปิดร้าน ต้องมีรูปโปรไฟล์ถ่ายสดจากกล้อง เพื่อความปลอดภัยของผู้ซื้อและไรเดอร์',
};

/** ผลจาก API นี้คือ "ต้องถ่ายรูปโปรไฟล์ก่อน" หรือไม่ */
export const isProfilePhotoRequired = (result: { success: boolean; code?: string } | null | undefined): boolean =>
  !!result && result.success === false && result.code === 'PROFILE_PHOTO_REQUIRED';

/** แสดง Alert พร้อมปุ่ม "ถ่ายรูปเลย" → หน้าถ่ายรูปโปรไฟล์ */
export const promptProfilePhoto = (context: ProfilePhotoContext): void => {
  Alert.alert('ถ่ายรูปโปรไฟล์ก่อนนะ', MESSAGE[context], [
    { text: 'ไว้ก่อน', style: 'cancel' },
    { text: 'ถ่ายรูปเลย', onPress: () => router.push(`/profile-photo?from=${context}` as never) },
  ]);
};
