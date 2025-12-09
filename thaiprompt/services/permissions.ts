/**
 * Permission Service - จัดการ permissions ของแอพ
 *
 * รวมศูนย์การขอและตรวจสอบ permissions ทุกประเภท:
 * - กล้อง (Camera)
 * - แกลเลอรี่ / Media Library
 * - ตำแหน่ง (Location)
 * - ไมโครโฟน (Microphone)
 */

import { Alert, Linking, Platform } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import * as Location from 'expo-location';
import { Audio } from 'expo-av';

// =====================================================
// Types
// =====================================================

export type PermissionType = 'camera' | 'mediaLibrary' | 'location' | 'locationBackground' | 'microphone';

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
 * ตรวจสอบสถานะ permission แกลเลอรี่
 */
export const checkMediaLibraryPermission = async (): Promise<PermissionResult> => {
  const { status, canAskAgain } = await ImagePicker.getMediaLibraryPermissionsAsync();
  return {
    granted: status === 'granted',
    canAskAgain,
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
 * ตรวจสอบสถานะ permission ไมโครโฟน
 */
export const checkMicrophonePermission = async (): Promise<PermissionResult> => {
  const { status, canAskAgain } = await Audio.getPermissionsAsync();
  return {
    granted: status === 'granted',
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
 * ขอ permission แกลเลอรี่ พร้อมแสดง Alert หากถูกปฏิเสธ
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
 * ขอ permission ไมโครโฟน พร้อมแสดง Alert หากถูกปฏิเสธ
 */
export const requestMicrophonePermission = async (showAlert = true): Promise<boolean> => {
  try {
    const { status, canAskAgain } = await Audio.requestPermissionsAsync();

    if (status === 'granted') {
      return true;
    }

    if (showAlert) {
      if (!canAskAgain) {
        showSettingsAlert('ไมโครโฟน', 'กรุณาไปที่ตั้งค่าเพื่ออนุญาตให้แอพใช้ไมโครโฟน');
      } else {
        Alert.alert(
          'ต้องการสิทธิ์ไมโครโฟน',
          'กรุณาอนุญาตให้แอพใช้ไมโครโฟนเพื่อโทรหาลูกค้า',
          [{ text: 'ตกลง' }]
        );
      }
    }

    return false;
  } catch (error) {
    console.error('Request microphone permission error:', error);
    return false;
  }
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

/**
 * ขอ permission สำหรับไรเดอร์ (ตำแหน่ง + กล้อง + ไมโครโฟน)
 */
export const requestRiderPermissions = async (): Promise<{
  location: boolean;
  locationBackground: boolean;
  camera: boolean;
  microphone: boolean;
}> => {
  const location = await requestLocationPermission(false);
  const locationBackground = location ? await requestBackgroundLocationPermission(false) : false;
  const camera = await requestCameraPermission(false);
  const microphone = await requestMicrophonePermission(false);

  return {
    location,
    locationBackground,
    camera,
    microphone,
  };
};

export default {
  // Check functions
  checkCameraPermission,
  checkMediaLibraryPermission,
  checkLocationPermission,
  checkBackgroundLocationPermission,
  checkMicrophonePermission,
  checkMultiplePermissions,

  // Request functions
  requestCameraPermission,
  requestMediaLibraryPermission,
  requestLocationPermission,
  requestBackgroundLocationPermission,
  requestMicrophonePermission,
  requestMultiplePermissions,

  // Utility functions
  requestCameraAndGalleryPermissions,
  requestRiderPermissions,
};
