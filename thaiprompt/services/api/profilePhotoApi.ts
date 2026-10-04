/**
 * Profile Photo API — รูปโปรไฟล์
 *
 *   GET  /me/profile-photo                → { has_photo, taken_at, photo_url, required } (photo_url = รูปที่คนอื่นเห็น)
 *   POST /me/profile-photo  multipart photo → เหมือน GET (รูปถ่ายสดของไรเดอร์รอบ 2 — แอป 3.387 ไม่ใช้แล้ว)
 *   POST /profile/avatar    multipart avatar → { avatarUrl, user } (รูปโปรไฟล์ = รูปอะไรก็ได้ จากคลังหรือกล้อง)
 *
 * - รูปโปรไฟล์ไม่ใช่หลักฐานตัวตนอีกต่อไป (2026-10-04) — ความน่าเชื่อถือมาจากป้ายทอง "ยืนยันตัวตนแล้ว" (eKYC)
 * - server ปิดการบังคับรูปแล้ว (required=false) · endpoint ยังไม่มีบน server (404) → ถือว่า "ไม่บังคับ"
 */

import { API_ENDPOINTS } from '@/constants';
import { apiGet, apiUpload, fileFromUri, type ApiResult } from './client';
import { personPhotoUri } from './handoverApi';

export interface ProfilePhotoStatus {
  has_photo: boolean;
  taken_at: string | null;
  photo_url: string | null;
  required: boolean;
}

/** ข้อความกลางเมื่อ server บอกว่าต้องมีรูปก่อน (ใช้ร่วมกันทุกหน้า) */
export const PROFILE_PHOTO_REQUIRED_MESSAGE = 'กรุณาเพิ่มรูปโปรไฟล์ก่อนใช้งานส่วนนี้';

const normalize = (raw: any): ProfilePhotoStatus => ({
  has_photo: raw?.has_photo === true || raw?.has_photo === 1,
  taken_at: typeof raw?.taken_at === 'string' ? raw.taken_at : null,
  photo_url: personPhotoUri(raw?.photo_url),
  required: raw?.required === true || raw?.required === 1,
});

const mapResult = <A, B>(result: ApiResult<A>, fn: (data: A) => B): ApiResult<B> =>
  result.success ? { ...result, data: fn(result.data) } : result;

/** GET /me/profile-photo */
export const getProfilePhoto = async (): Promise<ApiResult<ProfilePhotoStatus>> =>
  mapResult(await apiGet<any>('/me/profile-photo'), normalize);

/**
 * POST /me/profile-photo — อัปโหลดรูปที่ถ่ายจากกล้องหน้า (jpeg)
 * server ย่อ ≤1024px + ลบ EXIF + ทำรูปลายน้ำให้เอง
 */
export const uploadProfilePhoto = async (uri: string): Promise<ApiResult<ProfilePhotoStatus>> => {
  const form = new FormData();
  form.append('photo', fileFromUri(uri, 'profile-photo') as unknown as Blob);
  return mapResult(
    await apiUpload<any>('/me/profile-photo', form, { fallbackMessage: 'อัปโหลดรูปไม่สำเร็จ ลองถ่ายใหม่อีกครั้งนะ' }),
    normalize
  );
};

export interface AvatarUploadResult {
  avatarUrl?: string;
  user?: Record<string, unknown>;
}

/**
 * POST /profile/avatar — รูปโปรไฟล์ใหม่ (รูปอะไรก็ได้ที่ผู้ใช้เลือก) · server ย่อ/แปลงเป็น webp ให้เอง
 * ใช้ endpoint เดียวกับหน้าแก้ไขโปรไฟล์
 */
export const uploadProfileAvatar = (uri: string): Promise<ApiResult<AvatarUploadResult>> => {
  const form = new FormData();
  form.append('avatar', fileFromUri(uri, 'avatar') as unknown as Blob);
  return apiUpload<AvatarUploadResult>(API_ENDPOINTS.AVATAR_UPLOAD, form, {
    fallbackMessage: 'อัปโหลดรูปโปรไฟล์ไม่สำเร็จ ลองใหม่อีกครั้งนะ',
  });
};
