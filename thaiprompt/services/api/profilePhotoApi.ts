/**
 * Profile Photo API — รูปโปรไฟล์ถ่ายสดจากกล้อง (ไรเดอร์รอบ 2)
 *
 *   GET  /me/profile-photo                → { has_photo, taken_at, photo_url, required }
 *   POST /me/profile-photo  multipart photo → เหมือน GET
 *
 * - รูปต้นฉบับเก็บแบบส่วนตัวบน server · photo_url = รูปพร้อมลายน้ำ (URL ลายเซ็นอายุสั้น)
 * - server บังคับรูปผ่าน middleware (422 PROFILE_PHOTO_REQUIRED) เฉพาะแอปที่ส่ง X-App-Build ≥ 43
 * - endpoint ยังไม่มีบน server (404) → ถือว่า "ไม่บังคับ" ไม่ขวางผู้ใช้
 */

import { apiGet, apiUpload, fileFromUri, type ApiResult } from './client';
import { personPhotoUri } from './handoverApi';

export interface ProfilePhotoStatus {
  has_photo: boolean;
  taken_at: string | null;
  photo_url: string | null;
  required: boolean;
}

/** ข้อความกลางเมื่อ server บอกว่าต้องมีรูปก่อน (ใช้ร่วมกันทุกหน้า) */
export const PROFILE_PHOTO_REQUIRED_MESSAGE = 'กรุณาถ่ายรูปโปรไฟล์ก่อนใช้งานส่วนนี้';

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
