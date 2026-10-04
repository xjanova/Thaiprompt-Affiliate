/**
 * ตัวเชื่อม native ของ eKYC — Google ML Kit บนเครื่อง (ฟรี ไม่ส่งรูปออกนอกเครื่องในขั้นนี้)
 *
 *   - RNMLKitFaceDetection   (@infinitered/react-native-mlkit-face-detection) ตรวจใบหน้า/จุดตา-จมูก/ลืมตา/ยิ้ม/มุมหัว จากภาพนิ่ง
 *   - RNMLKitDocumentScanner (@infinitered/react-native-mlkit-document-scanner) ตัวสแกนเอกสารของ Google (Android)
 *     จับขอบบัตรอัตโนมัติ + ถ่ายเองเมื่อนิ่ง + ตัดภาพให้ตรง · ต้องมี Google Play services
 *
 * ⚠️ ห้าม import แพ็กเกจทั้งสองตรงๆ: JS ของแพ็กเกจเรียก requireNativeModule ตอนโหลด
 *    แอปรุ่นเก่า (binary ที่ยังไม่มี native module) จะล้มทันทีแม้อยู่ใน try/catch
 *    → เช็ค requireOptionalNativeModule ก่อน แล้วเรียก native module ตรงๆ (ไม่ผ่าน JS ของแพ็กเกจ)
 * ⚠️ ห้ามพิมพ์ uri ของรูป/ผลตรวจลง log (ข้อมูลชีวภาพ)
 */

import { Platform } from 'react-native';
import { requireOptionalNativeModule } from 'expo';
import type { MlkitFaceLike } from './liveness';

// =====================================================
// ตรวจใบหน้า
// =====================================================

interface FaceDetectionNative {
  initialize: (options: Record<string, unknown>) => Promise<void>;
  detectFaces: (imageUri: string) => Promise<{ faces?: MlkitFaceLike[] } | null | undefined>;
}

/** undefined = ยังไม่เคยโหลด · null = build นี้ไม่มี */
let faceNative: FaceDetectionNative | null | undefined;
let faceReady: Promise<boolean> | null = null;

const loadFaceNative = (): FaceDetectionNative | null => {
  if (faceNative !== undefined) return faceNative;
  try {
    const mod = requireOptionalNativeModule<FaceDetectionNative>('RNMLKitFaceDetection');
    faceNative =
      mod && typeof mod.initialize === 'function' && typeof mod.detectFaces === 'function' ? mod : null;
  } catch {
    faceNative = null;
  }
  return faceNative;
};

/** build นี้ตรวจใบหน้าบนเครื่องได้หรือไม่ */
export const isFaceDetectionAvailable = (): boolean => loadFaceNative() !== null;

/**
 * เตรียมตัวตรวจใบหน้า (ครั้งเดียวต่อการเปิดแอป) — ล้มเหลว = false (หน้าจอใช้โหมดนำทางเองแทน)
 * ตั้งค่า: accurate + จุด landmark (ตา/จมูก) + ความน่าจะเป็นลืมตา/ยิ้ม · ไม่ใช้ contour (ช้า)
 */
export const prepareFaceDetector = (): Promise<boolean> => {
  const mod = loadFaceNative();
  if (!mod) return Promise.resolve(false);
  if (!faceReady) {
    faceReady = mod
      .initialize({
        performanceMode: 'accurate',
        landmarkMode: true,
        contourMode: false,
        classificationMode: true,
        minFaceSize: 0.05,
        isTrackingEnabled: false,
      })
      .then(() => true)
      .catch(() => {
        faceReady = null;
        return false;
      });
  }
  return faceReady;
};

/**
 * ตรวจใบหน้าในภาพนิ่ง (file://)
 * @returns รายการใบหน้า · ตรวจไม่ได้ = null (ไม่ใช่ "ไม่มีหน้า")
 */
export const detectFacesInImage = async (uri: string): Promise<MlkitFaceLike[] | null> => {
  const mod = loadFaceNative();
  if (!mod) return null;
  const ready = await prepareFaceDetector();
  if (!ready) return null;
  try {
    const result = await mod.detectFaces(uri);
    return Array.isArray(result?.faces) ? result.faces : [];
  } catch {
    return null;
  }
};

// =====================================================
// สแกนบัตร (ตัวสแกนเอกสารของ Google — Android เท่านั้น)
// =====================================================

interface DocumentScannerNative {
  launchDocumentScannerAsync: (options: Record<string, unknown>) => Promise<{
    canceled?: boolean;
    pages?: string[] | null;
  } | null>;
}

let scannerNative: DocumentScannerNative | null | undefined;

const loadScannerNative = (): DocumentScannerNative | null => {
  if (scannerNative !== undefined) return scannerNative;
  if (Platform.OS !== 'android') {
    scannerNative = null;
    return scannerNative;
  }
  try {
    const mod = requireOptionalNativeModule<DocumentScannerNative>('RNMLKitDocumentScanner');
    scannerNative = mod && typeof mod.launchDocumentScannerAsync === 'function' ? mod : null;
  } catch {
    scannerNative = null;
  }
  return scannerNative;
};

/** build นี้มีตัวสแกนเอกสารหรือไม่ (มีแล้วก็ยังใช้ไม่ได้ถ้าเครื่องไม่มี Google Play services) */
export const isDocumentScannerAvailable = (): boolean => loadScannerNative() !== null;

export type ScanCardResult =
  | { kind: 'ok'; uri: string }
  | { kind: 'canceled' }
  /** เครื่องนี้ใช้ตัวสแกนไม่ได้ (ไม่มี Play services / ดาวน์โหลดโมดูลไม่สำเร็จ) → ใช้กล้องในแอปแทน */
  | { kind: 'unavailable' };

/**
 * เปิดตัวสแกนบัตรของ Google: จับขอบอัตโนมัติ ถ่ายเองเมื่อนิ่ง ตัดภาพให้ตรง
 * ห้ามนำเข้ารูปจากคลังภาพ (กันใช้รูปบัตรของคนอื่น) · 1 หน้า · ผลเป็น JPEG
 */
export const scanCardWithDocumentScanner = async (): Promise<ScanCardResult> => {
  const mod = loadScannerNative();
  if (!mod) return { kind: 'unavailable' };
  try {
    const result = await mod.launchDocumentScannerAsync({
      pageLimit: 1,
      galleryImportAllowed: false,
      scannerMode: 'base',
      resultFormats: 'jpeg',
    });
    if (!result || result.canceled) return { kind: 'canceled' };
    const uri = Array.isArray(result.pages) ? result.pages.find((p) => typeof p === 'string' && p.length > 0) : undefined;
    return uri ? { kind: 'ok', uri } : { kind: 'canceled' };
  } catch {
    return { kind: 'unavailable' };
  }
};
