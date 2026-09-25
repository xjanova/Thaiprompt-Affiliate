/**
 * ถ่ายรูป / เลือกรูป สำหรับเอกสารไรเดอร์และหลักฐานการส่ง
 *
 * - กล้อง: ขอสิทธิ์ตอนกดถ่ายเท่านั้น ถ้าถูกปฏิเสธถาวร → พาไปตั้งค่าเครื่อง
 * - คลังรูป: ใช้ตัวเลือกรูปของระบบ (ไม่ต้องขอสิทธิ์อ่านรูปทั้งเครื่อง)
 * - บีบอัดเหลือ quality 0.7 ให้อัปโหลดเร็วบนเน็ตมือถือ
 */

import { Alert, Linking } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { updateRiderPermissions } from '@/services/api/riderApi';

let cameraFlagSent = false;

export const takePhoto = async (options: { front?: boolean } = {}): Promise<string | null> => {
  try {
    const permission = await ImagePicker.requestCameraPermissionsAsync();
    if (permission.status !== 'granted') {
      if (permission.canAskAgain === false) {
        Alert.alert('เปิดสิทธิ์กล้องก่อนนะ', 'ไปที่การตั้งค่าเครื่อง แล้วอนุญาตให้ ThaiPrompt ใช้กล้อง', [
          { text: 'ไว้ก่อน', style: 'cancel' },
          { text: 'เปิดการตั้งค่า', onPress: () => Linking.openSettings().catch(() => {}) },
        ]);
      }
      return null;
    }
    if (!cameraFlagSent) {
      cameraFlagSent = true;
      updateRiderPermissions({ camera: true }).catch(() => {});
    }

    const result = await ImagePicker.launchCameraAsync({
      mediaTypes: ['images'],
      quality: 0.7,
      allowsEditing: false,
      exif: false,
      cameraType: options.front ? ImagePicker.CameraType.front : ImagePicker.CameraType.back,
    });
    if (result.canceled || !result.assets?.[0]?.uri) return null;
    return result.assets[0].uri;
  } catch {
    Alert.alert('เปิดกล้องไม่ได้', 'ลองใหม่อีกครั้ง หรือเลือกรูปจากคลังแทนนะ');
    return null;
  }
};

export const pickFromGallery = async (): Promise<string | null> => {
  try {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      quality: 0.7,
      allowsEditing: false,
      exif: false,
      selectionLimit: 1,
    });
    if (result.canceled || !result.assets?.[0]?.uri) return null;
    return result.assets[0].uri;
  } catch {
    Alert.alert('เปิดคลังรูปไม่ได้', 'ลองใหม่อีกครั้งนะ');
    return null;
  }
};
