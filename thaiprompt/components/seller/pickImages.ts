/**
 * เลือกรูปสำหรับหลังร้าน (รูปสินค้า / โลโก้ / แบนเนอร์)
 *
 * - ถามก่อนว่าจะ "ถ่ายรูป" หรือ "เลือกจากคลังรูป"
 * - คลังรูป: ใช้ตัวเลือกของระบบ ไม่ต้องขอสิทธิ์อ่านรูปทั้งเครื่อง (เหมือนหน้าแก้สินค้าตลาดสด)
 * - กล้อง: ขอสิทธิ์ก่อน · ปฏิเสธถาวร → ชวนไปเปิดในการตั้งค่าเครื่อง (เหมือนหน้า KYC)
 * - iOS แปลง HEIC เป็น JPEG ให้ (server รับ JPG/PNG/WebP/GIF) · ข้ามรูปที่ใหญ่เกินกำหนดพร้อมแจ้งเตือน
 */

import { Alert, Linking, Platform } from 'react-native';
import * as ImagePicker from 'expo-image-picker';

export type PhotoSource = 'camera' | 'library';

export interface PickedImages {
  uris: string[];
  /** จำนวนรูปที่ข้ามเพราะไฟล์ใหญ่เกิน */
  skipped: number;
}

/** ถามแหล่งรูป (คืน null = ยกเลิก) */
export const choosePhotoSource = (title: string = 'เพิ่มรูป'): Promise<PhotoSource | null> =>
  new Promise((resolve) => {
    Alert.alert(
      title,
      undefined,
      [
        { text: 'ถ่ายรูป', onPress: () => resolve('camera') },
        { text: 'เลือกจากคลังรูป', onPress: () => resolve('library') },
        { text: 'ยกเลิก', style: 'cancel', onPress: () => resolve(null) },
      ],
      { cancelable: true, onDismiss: () => resolve(null) }
    );
  });

const ensureCameraPermission = async (): Promise<boolean> => {
  try {
    const current = await ImagePicker.getCameraPermissionsAsync();
    if (current.granted) return true;
    const asked = await ImagePicker.requestCameraPermissionsAsync();
    if (asked.granted) return true;
    if (asked.canAskAgain === false) {
      Alert.alert('เปิดสิทธิ์กล้องก่อนนะ', 'ไปที่การตั้งค่าเครื่อง แล้วอนุญาตให้ใช้กล้อง หรือเลือกรูปจากคลังแทนก็ได้', [
        { text: 'ไว้ก่อน', style: 'cancel' },
        { text: 'เปิดการตั้งค่า', onPress: () => Linking.openSettings().catch(() => {}) },
      ]);
    }
    return false;
  } catch {
    Alert.alert('เปิดกล้องไม่ได้', 'ลองเลือกรูปจากคลังแทนนะ');
    return false;
  }
};

/**
 * เปิดกล้อง/คลังรูปแล้วคืน uri ของรูปที่ใช้ได้
 * @param max จำนวนรูปสูงสุดที่รับรอบนี้
 * @param maxBytes ขนาดไฟล์สูงสุดต่อรูป
 * @returns null = ผู้ใช้ยกเลิก/เปิดไม่ได้
 */
export const pickImages = async (options: {
  source: PhotoSource;
  max: number;
  maxBytes: number;
  aspect?: [number, number];
}): Promise<PickedImages | null> => {
  const { source, max, maxBytes, aspect } = options;
  if (max <= 0) return null;

  const common: ImagePicker.ImagePickerOptions = {
    mediaTypes: ['images'],
    quality: 0.8,
    exif: false,
    ...(Platform.OS === 'ios'
      ? { preferredAssetRepresentationMode: ImagePicker.UIImagePickerPreferredAssetRepresentationMode.Compatible }
      : {}),
  };

  let result: ImagePicker.ImagePickerResult;
  try {
    if (source === 'camera') {
      if (!(await ensureCameraPermission())) return null;
      result = await ImagePicker.launchCameraAsync({ ...common, allowsEditing: !!aspect, aspect });
    } else {
      // ครอปได้เฉพาะตอนเลือกรูปเดียว (ระบบไม่รองรับครอปหลายรูป)
      const single = max === 1;
      result = await ImagePicker.launchImageLibraryAsync({
        ...common,
        allowsMultipleSelection: !single,
        selectionLimit: single ? 1 : max,
        allowsEditing: single && !!aspect,
        aspect: single ? aspect : undefined,
      });
    }
  } catch {
    Alert.alert(source === 'camera' ? 'เปิดกล้องไม่ได้' : 'เปิดคลังรูปไม่ได้', 'ลองใหม่อีกครั้งนะ');
    return null;
  }

  if (result.canceled || !result.assets?.length) return null;

  const tooBig = (a: ImagePicker.ImagePickerAsset) => typeof a.fileSize === 'number' && a.fileSize > maxBytes;
  const ok = result.assets.filter((a) => !!a.uri && !tooBig(a)).map((a) => a.uri);
  const skipped = result.assets.length - ok.length;

  if (skipped > 0) {
    const mb = Math.round(maxBytes / (1024 * 1024));
    Alert.alert('บางรูปใหญ่เกินไป', `รูปต้องไม่เกิน ${mb}MB ต่อรูป ระบบข้ามรูปที่ใหญ่เกินให้แล้ว`);
  }

  return { uris: ok.slice(0, max), skipped };
};
