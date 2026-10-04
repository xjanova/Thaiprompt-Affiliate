/**
 * ลบไฟล์รูปชั่วคราวของ eKYC (รูปบัตร/ใบหน้าที่ถ่ายในเครื่อง) — เงียบเสมอ ห้ามทำให้หน้าจอล้ม
 * ลบเฉพาะ file:// ในเครื่อง (ไม่แตะ content:// ของแอปอื่น)
 */

import * as FileSystem from 'expo-file-system/legacy';

export const dropTempFile = (uri: string | null | undefined): void => {
  if (!uri || !uri.startsWith('file://')) return;
  FileSystem.deleteAsync(uri, { idempotent: true }).catch(() => {});
};

export const dropTempFiles = (uris: Array<string | null | undefined>): void => {
  uris.forEach(dropTempFile);
};
