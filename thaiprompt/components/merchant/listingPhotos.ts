/**
 * เลือกรูปสินค้า (ถ่ายรูป / คลังรูป) สำหรับหน้าลงขายและแก้สินค้าตลาดสด
 *
 * - คลังรูป: ตัวเลือกรูปของระบบ (ไม่ต้องขอสิทธิ์อ่านรูปทั้งเครื่อง) เลือกได้หลายรูปตามที่เหลือ
 * - กล้อง: ขอสิทธิ์ตอนกดถ่ายเท่านั้น · ถูกปฏิเสธถาวร → พาไปตั้งค่าเครื่อง (แบบเดียวกับหน้า KYC)
 * - ตัดรูปที่ใหญ่เกิน 5MB ออก (server รับรูปละไม่เกิน 5MB) + บอกผู้ใช้ · บีบอัด quality 0.7
 */

import { Alert, Linking } from 'react-native';
import * as ImagePicker from 'expo-image-picker';

export const MAX_LISTING_IMAGE_BYTES = 5 * 1024 * 1024;

type Asset = ImagePicker.ImagePickerAsset;

/** รูปที่ใช้ได้ (ชนิดรูป + ไม่เกิน 5MB) — แจ้งเตือนถ้ามีรูปที่ถูกตัดออก */
const usableUris = (assets: Asset[], remaining: number): string[] => {
  const tooBig = assets.filter((a) => typeof a.fileSize === 'number' && a.fileSize > MAX_LISTING_IMAGE_BYTES);
  const notImage = assets.filter((a) => typeof a.mimeType === 'string' && !a.mimeType.startsWith('image/'));
  if (tooBig.length > 0 || notImage.length > 0) {
    Alert.alert('บางรูปใช้ไม่ได้', 'รูปต้องเป็นไฟล์รูปภาพและไม่เกิน 5MB ต่อรูป ระบบข้ามรูปเหล่านั้นให้แล้ว');
  }
  return assets
    .filter((a) => !tooBig.includes(a) && !notImage.includes(a) && !!a.uri)
    .map((a) => a.uri)
    .slice(0, Math.max(0, remaining));
};

export const pickListingPhotosFromLibrary = async (remaining: number): Promise<string[]> => {
  if (remaining <= 0) return [];
  try {
    const picked = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      quality: 0.7,
      allowsMultipleSelection: remaining > 1,
      selectionLimit: remaining,
      exif: false,
    });
    if (picked.canceled || !picked.assets?.length) return [];
    return usableUris(picked.assets, remaining);
  } catch {
    Alert.alert('เปิดคลังรูปไม่ได้', 'ลองใหม่อีกครั้งนะ');
    return [];
  }
};

export const takeListingPhoto = async (): Promise<string[]> => {
  try {
    const permission = await ImagePicker.requestCameraPermissionsAsync();
    if (permission.status !== 'granted') {
      if (permission.canAskAgain === false) {
        Alert.alert('เปิดสิทธิ์กล้องก่อนนะ', 'ไปที่การตั้งค่าเครื่อง แล้วอนุญาตให้ใช้กล้อง หรือเลือกรูปจากคลังแทนก็ได้', [
          { text: 'ไว้ก่อน', style: 'cancel' },
          { text: 'เปิดการตั้งค่า', onPress: () => Linking.openSettings().catch(() => {}) },
        ]);
      }
      return [];
    }
    const result = await ImagePicker.launchCameraAsync({ mediaTypes: ['images'], quality: 0.7, allowsEditing: false, exif: false });
    if (result.canceled || !result.assets?.length) return [];
    return usableUris(result.assets, 1);
  } catch {
    Alert.alert('เปิดกล้องไม่ได้', 'ลองใหม่อีกครั้ง หรือเลือกรูปจากคลังแทนนะ');
    return [];
  }
};

/** ถามว่าจะถ่ายรูปหรือเลือกจากคลัง แล้วคืนรูปที่ได้ (ยกเลิก = []) */
export const chooseListingPhotos = (remaining: number): Promise<string[]> =>
  new Promise((resolve) => {
    if (remaining <= 0) {
      Alert.alert('รูปครบแล้ว', 'สินค้ามีรูปได้สูงสุด 5 รูป ลบรูปเก่าก่อนนะ');
      resolve([]);
      return;
    }
    let settled = false;
    const done = (value: Promise<string[]> | string[]) => {
      if (settled) return;
      settled = true;
      Promise.resolve(value).then(resolve, () => resolve([]));
    };
    Alert.alert(
      'เพิ่มรูปสินค้า',
      `เพิ่มได้อีก ${remaining} รูป · รูปแรกคือรูปหลักที่ลูกค้าเห็นก่อน`,
      [
        { text: 'ถ่ายรูป', onPress: () => done(takeListingPhoto()) },
        { text: 'เลือกจากคลังรูป', onPress: () => done(pickListingPhotosFromLibrary(remaining)) },
        { text: 'ยกเลิก', style: 'cancel', onPress: () => done([]) },
      ],
      { cancelable: true, onDismiss: () => done([]) }
    );
  });
