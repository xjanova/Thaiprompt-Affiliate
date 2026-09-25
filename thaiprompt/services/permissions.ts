/**
 * Permission Service - จัดการ permissions ของแอพ
 *
 * รวมศูนย์การขอและตรวจสอบ permissions ทุกประเภท:
 * - กล้อง (Camera)
 * - แกลเลอรี่ / Media Library
 * - พื้นที่เก็บไฟล์ (File Storage)
 * - ตำแหน่ง (Location)
 * - ไมโครโฟน (Microphone)
 */

import { Alert, Linking, Platform } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import * as Location from 'expo-location';

// หมายเหตุ (อัปเกรด Expo SDK 57):
// - เลิกใช้ expo-av (ถูกถอดออกตั้งแต่ SDK 55) และบล็อก RECORD_AUDIO ใน app.json
//   แอปไม่มีฟีเจอร์อัดเสียง การโทรหาลูกค้าเปิดแอปโทรศัพท์ของเครื่อง (tel:) ซึ่งไม่ต้องใช้สิทธิ์ไมโครโฟน
// - เลิกใช้ expo-media-library (ต้องใช้สิทธิ์ READ_MEDIA_* ที่ Google Play จำกัด)
//   การบันทึกรูปใช้หน้าต่างแชร์ของระบบ (expo-sharing) แทน จึงไม่ต้องขอสิทธิ์

// =====================================================
// Types
// =====================================================

export type PermissionType = 'camera' | 'mediaLibrary' | 'saveMedia' | 'location' | 'locationBackground' | 'microphone';

export interface PermissionResult {
  granted: boolean;
  canAskAgain?: boolean;
}

// =====================================================
// Permission Check Functions
// =====================================================

/**
 * ตรวจสอบสถานะ permission กล้อง
 */
export const checkCameraPermission = async (): Promise<PermissionResult> => {
  const { status, canAskAgain } = await ImagePicker.getCameraPermissionsAsync();
  return {
    granted: status === 'granted',
    canAskAgain,
  };
};

/**
 * ตรวจสอบสถานะ permission แกลเลอรี่ (อ่านรูป)
 */
export const checkMediaLibraryPermission = async (): Promise<PermissionResult> => {
  const { status, canAskAgain } = await ImagePicker.getMediaLibraryPermissionsAsync();
  return {
    granted: status === 'granted',
    canAskAgain,
  };
};

/**
 * ตรวจสอบสถานะ permission บันทึกรูปลงแกลเลอรี่
 *
 * บันทึกรูปผ่านหน้าต่างแชร์ของระบบ (ผู้ใช้เลือกปลายทางเอง) จึงไม่ต้องขอสิทธิ์ → อนุญาตเสมอ
 */
export const checkSaveMediaPermission = async (): Promise<PermissionResult> => {
  return {
    granted: true,
    canAskAgain: true,
  };
};

/**
 * ตรวจสอบสถานะ permission ตำแหน่ง (foreground)
 */
export const checkLocationPermission = async (): Promise<PermissionResult> => {
  const { status, canAskAgain } = await Location.getForegroundPermissionsAsync();
  return {
    granted: status === 'granted',
    canAskAgain,
  };
};

/**
 * ตรวจสอบสถานะ permission ตำแหน่ง (background)
 */
export const checkBackgroundLocationPermission = async (): Promise<PermissionResult> => {
  const { status, canAskAgain } = await Location.getBackgroundPermissionsAsync();
  return {
    granted: status === 'granted',
    canAskAgain,
  };
};

/**
 * ข้อความอธิบายเมื่อมีการขอสิทธิ์ไมโครโฟน (แอปไม่ใช้ไมโครโฟนแล้ว)
 */
export const MICROPHONE_NOT_REQUIRED_MESSAGE =
  'แอปไม่ต้องใช้ไมโครโฟน การโทรหาลูกค้าจะเปิดแอปโทรศัพท์ของเครื่องโดยตรง';

/**
 * สถานะสิทธิ์ไมโครโฟนในรูปแบบเดียวกับ expo-av เดิม (ให้หน้าจอเดิมเรียกแทน Audio.requestPermissionsAsync ได้)
 *
 * RECORD_AUDIO ถูกบล็อกใน app.json → ตอบ denied เสมอ และไม่เปิดกล่องขอสิทธิ์ของระบบ
 */
export const getMicrophonePermissionStatusAsync = async (): Promise<{
  status: 'granted' | 'denied';
  granted: boolean;
  canAskAgain: boolean;
}> => {
  return {
    status: 'denied',
    granted: false,
    canAskAgain: false,
  };
};

/**
 * ตรวจสอบสถานะ permission ไมโครโฟน (ไม่ได้ใช้แล้ว → ไม่อนุญาตเสมอ)
 */
export const checkMicrophonePermission = async (): Promise<PermissionResult> => {
  const { granted, canAskAgain } = await getMicrophonePermissionStatusAsync();
  return {
    granted,
    canAskAgain,
  };
};

// =====================================================
// Permission Request Functions
// =====================================================

/**
 * ขอ permission กล้อง พร้อมแสดง Alert หากถูกปฏิเสธ
 */
export const requestCameraPermission = async (showAlert = true): Promise<boolean> => {
  try {
    const { status, canAskAgain } = await ImagePicker.requestCameraPermissionsAsync();

    if (status === 'granted') {
      return true;
    }

    if (showAlert) {
      if (!canAskAgain) {
        // ผู้ใช้เลือก "Don't ask again" ต้องไปตั้งค่าเอง
        showSettingsAlert('กล้อง', 'กรุณาไปที่ตั้งค่าเพื่ออนุญาตให้แอพใช้กล้อง');
      } else {
        Alert.alert(
          'ต้องการสิทธิ์กล้อง',
          'กรุณาอนุญาตให้แอพใช้กล้องเพื่อถ่ายรูป',
          [{ text: 'ตกลง' }]
        );
      }
    }

    return false;
  } catch (error) {
    console.error('Request camera permission error:', error);
    return false;
  }
};

/**
 * ขอ permission แกลเลอรี่ (อ่านรูป) พร้อมแสดง Alert หากถูกปฏิเสธ
 */
export const requestMediaLibraryPermission = async (showAlert = true): Promise<boolean> => {
  try {
    const { status, canAskAgain } = await ImagePicker.requestMediaLibraryPermissionsAsync();

    if (status === 'granted') {
      return true;
    }

    if (showAlert) {
      if (!canAskAgain) {
        showSettingsAlert('รูปภาพ', 'กรุณาไปที่ตั้งค่าเพื่ออนุญาตให้แอพเข้าถึงรูปภาพ');
      } else {
        Alert.alert(
          'ต้องการสิทธิ์เข้าถึงรูปภาพ',
          'กรุณาอนุญาตให้แอพเข้าถึงรูปภาพเพื่อเลือกเอกสาร',
          [{ text: 'ตกลง' }]
        );
      }
    }

    return false;
  } catch (error) {
    console.error('Request media library permission error:', error);
    return false;
  }
};

/**
 * ขอ permission บันทึกรูปลงแกลเลอรี่
 *
 * บันทึกรูปผ่านหน้าต่างแชร์ของระบบ (saveToMediaLibrary ใน fileService) จึงไม่ต้องขอสิทธิ์ → true เสมอ
 * คง signature เดิม (showAlert) ไว้ให้โค้ดที่เรียกอยู่ไม่ต้องแก้
 */
export const requestSaveMediaPermission = async (_showAlert = true): Promise<boolean> => {
  return true;
};

/**
 * ขอ permission ตำแหน่ง (foreground) พร้อมแสดง Alert หากถูกปฏิเสธ
 */
export const requestLocationPermission = async (showAlert = true): Promise<boolean> => {
  try {
    const { status, canAskAgain } = await Location.requestForegroundPermissionsAsync();

    if (status === 'granted') {
      return true;
    }

    if (showAlert) {
      if (!canAskAgain) {
        showSettingsAlert('ตำแหน่ง', 'กรุณาไปที่ตั้งค่าเพื่ออนุญาตให้แอพเข้าถึงตำแหน่ง');
      } else {
        Alert.alert(
          'ต้องการสิทธิ์ตำแหน่ง',
          'กรุณาอนุญาตให้แอพเข้าถึงตำแหน่งเพื่อใช้งานฟีเจอร์นี้',
          [{ text: 'ตกลง' }]
        );
      }
    }

    return false;
  } catch (error) {
    console.error('Request location permission error:', error);
    return false;
  }
};

/**
 * ขอ permission ตำแหน่ง (background) พร้อมแสดง Alert หากถูกปฏิเสธ
 * ต้องขอ foreground ก่อน
 */
export const requestBackgroundLocationPermission = async (showAlert = true): Promise<boolean> => {
  try {
    // ตรวจสอบว่าได้ foreground permission ก่อนหรือยัง
    const foregroundResult = await checkLocationPermission();
    if (!foregroundResult.granted) {
      const foregroundGranted = await requestLocationPermission(showAlert);
      if (!foregroundGranted) {
        return false;
      }
    }

    const { status, canAskAgain } = await Location.requestBackgroundPermissionsAsync();

    if (status === 'granted') {
      return true;
    }

    if (showAlert) {
      if (!canAskAgain) {
        showSettingsAlert(
          'ตำแหน่งในพื้นหลัง',
          'กรุณาไปที่ตั้งค่าและเลือก "อนุญาตตลอดเวลา" สำหรับตำแหน่ง'
        );
      } else {
        Alert.alert(
          'ต้องการสิทธิ์ตำแหน่งในพื้นหลัง',
          'กรุณาเลือก "อนุญาตตลอดเวลา" เพื่อให้แอพติดตามตำแหน่งขณะทำงานเป็นไรเดอร์',
          [{ text: 'ตกลง' }]
        );
      }
    }

    return false;
  } catch (error) {
    console.error('Request background location permission error:', error);
    return false;
  }
};

/**
 * ขอ permission ไมโครโฟน
 *
 * แอปไม่ใช้ไมโครโฟนแล้ว (RECORD_AUDIO ถูกบล็อก) → ไม่เปิดกล่องขอสิทธิ์ของระบบ คืน false เสมอ
 * ถ้า showAlert = true จะแจ้งผู้ใช้ว่าไม่ต้องอนุญาตสิทธิ์นี้
 */
export const requestMicrophonePermission = async (showAlert = true): Promise<boolean> => {
  if (showAlert) {
    Alert.alert('ไม่ต้องใช้ไมโครโฟน', MICROPHONE_NOT_REQUIRED_MESSAGE, [{ text: 'ตกลง' }]);
  }
  return false;
};

// =====================================================
// Utility Functions
// =====================================================

/**
 * แสดง Alert พร้อมปุ่มไปตั้งค่า
 */
const showSettingsAlert = (permissionName: string, message: string) => {
  Alert.alert(
    `ต้องการสิทธิ์${permissionName}`,
    message,
    [
      { text: 'ยกเลิก', style: 'cancel' },
      {
        text: 'ไปตั้งค่า',
        onPress: () => Linking.openSettings(),
      },
    ]
  );
};

/**
 * ขอ permission หลายอย่างพร้อมกัน
 */
export const requestMultiplePermissions = async (
  permissions: PermissionType[]
): Promise<Record<PermissionType, boolean>> => {
  const results: Partial<Record<PermissionType, boolean>> = {};

  for (const permission of permissions) {
    switch (permission) {
      case 'camera':
        results.camera = await requestCameraPermission(false);
        break;
      case 'mediaLibrary':
        results.mediaLibrary = await requestMediaLibraryPermission(false);
        break;
      case 'saveMedia':
        results.saveMedia = await requestSaveMediaPermission(false);
        break;
      case 'location':
        results.location = await requestLocationPermission(false);
        break;
      case 'locationBackground':
        results.locationBackground = await requestBackgroundLocationPermission(false);
        break;
      case 'microphone':
        results.microphone = await requestMicrophonePermission(false);
        break;
    }
  }

  return results as Record<PermissionType, boolean>;
};

/**
 * ตรวจสอบ permission หลายอย่างพร้อมกัน
 */
export const checkMultiplePermissions = async (
  permissions: PermissionType[]
): Promise<Record<PermissionType, PermissionResult>> => {
  const results: Partial<Record<PermissionType, PermissionResult>> = {};

  for (const permission of permissions) {
    switch (permission) {
      case 'camera':
        results.camera = await checkCameraPermission();
        break;
      case 'mediaLibrary':
        results.mediaLibrary = await checkMediaLibraryPermission();
        break;
      case 'saveMedia':
        results.saveMedia = await checkSaveMediaPermission();
        break;
      case 'location':
        results.location = await checkLocationPermission();
        break;
      case 'locationBackground':
        results.locationBackground = await checkBackgroundLocationPermission();
        break;
      case 'microphone':
        results.microphone = await checkMicrophonePermission();
        break;
    }
  }

  return results as Record<PermissionType, PermissionResult>;
};

/**
 * ขอ permission สำหรับกล้องและแกลเลอรี่พร้อมกัน (ใช้บ่อยในหน้า KYC, Profile)
 */
export const requestCameraAndGalleryPermissions = async (): Promise<{
  camera: boolean;
  mediaLibrary: boolean;
}> => {
  const camera = await requestCameraPermission(false);
  const mediaLibrary = await requestMediaLibraryPermission(false);

  // แสดง alert รวมหากทั้งสองไม่ได้รับอนุญาต
  if (!camera && !mediaLibrary) {
    Alert.alert(
      'ต้องการสิทธิ์',
      'กรุณาอนุญาตให้แอพใช้กล้องและเข้าถึงรูปภาพ',
      [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: 'ไปตั้งค่า', onPress: () => Linking.openSettings() },
      ]
    );
  }

  return { camera, mediaLibrary };
};

// PLAY-13: ถอด requestRiderPermissions (ขอตำแหน่งเบื้องหลังต่อกันโดยไม่มี prominent disclosure)
// หน้าไรเดอร์ต้องแสดง ConsentSheet อธิบายเหตุผลก่อนเรียก requestBackgroundLocationPermission เสมอ

export default {
  // Check functions
  checkCameraPermission,
  checkMediaLibraryPermission,
  checkSaveMediaPermission,
  checkLocationPermission,
  checkBackgroundLocationPermission,
  checkMicrophonePermission,
  checkMultiplePermissions,

  // Request functions
  requestCameraPermission,
  requestMediaLibraryPermission,
  requestSaveMediaPermission,
  requestLocationPermission,
  requestBackgroundLocationPermission,
  requestMicrophonePermission,
  requestMultiplePermissions,

  // Utility functions
  requestCameraAndGalleryPermissions,
};
