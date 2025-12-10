/**
 * Location Service - GPS Tracking สำหรับไรเดอร์ และ GPS Sharing
 *
 * ติดตามตำแหน่งเฉพาะเมื่อไรเดอร์รับงานเท่านั้น
 * ตามมาตรฐาน Google Play ที่ต้อง:
 * - แจ้งผู้ใช้อย่างชัดเจนว่าจะใช้ตำแหน่งเมื่อไหร่
 * - ไม่ติดตามตำแหน่งตลอดเวลา
 * - แสดง notification เมื่อกำลังติดตาม
 *
 * ⭐ GPS Sharing - แชร์ตำแหน่งให้ Admin ดู GPS Monitor
 * - เปิด/ปิดจากหน้าตั้งค่า
 * - ส่งตำแหน่งไปยัง server ทุก 30 วินาที
 */

import * as Location from 'expo-location';
import * as TaskManager from 'expo-task-manager';
import { Platform } from 'react-native';
import { updateRiderLocation, shareGpsLocation, stopGpsSharing as apiStopGpsSharing } from './api';
import * as Device from 'expo-device';

// ชื่อ Task สำหรับ background location
export const LOCATION_TASK_NAME = 'RIDER_LOCATION_TRACKING';
export const GPS_SHARING_TASK_NAME = 'GPS_SHARING_TRACKING';

// สถานะการติดตาม
let isTracking = false;
let trackingInterval: NodeJS.Timeout | null = null;
let currentJobId: number | null = null;

// ⭐ สถานะ GPS Sharing
let isGpsSharing = false;
let gpsSharingInterval: NodeJS.Timeout | null = null;

// =====================================================
// Task Manager Definition
// =====================================================

/**
 * กำหนด background task สำหรับติดตามตำแหน่ง
 * Task นี้จะทำงานแม้แอปไม่ได้อยู่หน้าจอ
 */
TaskManager.defineTask(LOCATION_TASK_NAME, async ({ data, error }) => {
  if (error) {
    console.error('Location task error:', error);
    return;
  }

  if (data) {
    const { locations } = data as { locations: Location.LocationObject[] };
    const location = locations[0];

    if (location) {
      try {
        // ส่งตำแหน่งไปยัง server
        await sendLocationToServer(location);
      } catch (err) {
        console.error('Error sending location:', err);
      }
    }
  }
});

// =====================================================
// Location Functions
// =====================================================

/**
 * ส่งตำแหน่งไปยัง server
 */
const sendLocationToServer = async (location: Location.LocationObject) => {
  try {
    const deviceModel = Device.modelName || 'Unknown';
    const osVersion = Platform.OS + ' ' + Platform.Version;

    await updateRiderLocation({
      latitude: location.coords.latitude,
      longitude: location.coords.longitude,
      altitude: location.coords.altitude ?? undefined,
      accuracy: location.coords.accuracy ?? undefined,
      speed: location.coords.speed
        ? location.coords.speed * 3.6 // m/s to km/h
        : undefined,
      heading: location.coords.heading ?? undefined,
      device_model: deviceModel,
      os_version: osVersion,
    });
  } catch (error) {
    console.error('Send location error:', error);
  }
};

/**
 * ตรวจสอบสิทธิ์การเข้าถึงตำแหน่ง
 */
export const checkLocationPermission = async (): Promise<{
  foreground: boolean;
  background: boolean;
}> => {
  const { status: foregroundStatus } =
    await Location.getForegroundPermissionsAsync();
  const { status: backgroundStatus } =
    await Location.getBackgroundPermissionsAsync();

  return {
    foreground: foregroundStatus === 'granted',
    background: backgroundStatus === 'granted',
  };
};

/**
 * ขอสิทธิ์การเข้าถึงตำแหน่ง
 */
export const requestLocationPermission = async (): Promise<{
  foreground: boolean;
  background: boolean;
}> => {
  // ขอสิทธิ์ foreground ก่อน
  const { status: foregroundStatus } =
    await Location.requestForegroundPermissionsAsync();

  if (foregroundStatus !== 'granted') {
    return { foreground: false, background: false };
  }

  // ขอสิทธิ์ background
  const { status: backgroundStatus } =
    await Location.requestBackgroundPermissionsAsync();

  return {
    foreground: true,
    background: backgroundStatus === 'granted',
  };
};

/**
 * เริ่มติดตามตำแหน่ง (เมื่อรับงาน)
 *
 * @param jobId รหัสงานที่รับ
 * @returns boolean สำเร็จหรือไม่
 */
export const startTracking = async (jobId: number): Promise<boolean> => {
  if (isTracking) {
    console.log('Already tracking');
    return true;
  }

  try {
    // ตรวจสอบสิทธิ์
    const permissions = await checkLocationPermission();

    if (!permissions.foreground) {
      console.error('Location permission not granted');
      return false;
    }

    currentJobId = jobId;
    isTracking = true;

    // ถ้ามี background permission ใช้ background tracking
    if (permissions.background) {
      await Location.startLocationUpdatesAsync(LOCATION_TASK_NAME, {
        accuracy: Location.Accuracy.High,
        timeInterval: 10000, // อัพเดททุก 10 วินาที
        distanceInterval: 20, // หรือเมื่อเคลื่อนที่ 20 เมตร
        foregroundService: {
          notificationTitle: 'กำลังติดตามตำแหน่ง',
          notificationBody: 'Thaiprompt กำลังติดตามตำแหน่งของคุณเพื่อแจ้งลูกค้า',
          notificationColor: '#3B82F6',
        },
        // Android: แสดงใน notification
        showsBackgroundLocationIndicator: true,
        // iOS: pause เมื่อไม่เคลื่อนที่
        pausesUpdatesAutomatically: false,
      });

      console.log('Background tracking started');
    } else {
      // ถ้าไม่มี background permission ใช้ foreground tracking
      trackingInterval = setInterval(async () => {
        try {
          const location = await Location.getCurrentPositionAsync({
            accuracy: Location.Accuracy.High,
          });
          await sendLocationToServer(location);
        } catch (error) {
          console.error('Foreground tracking error:', error);
        }
      }, 15000); // อัพเดททุก 15 วินาที

      console.log('Foreground tracking started');
    }

    // ส่งตำแหน่งแรกทันที
    const initialLocation = await Location.getCurrentPositionAsync({
      accuracy: Location.Accuracy.High,
    });
    await sendLocationToServer(initialLocation);

    return true;
  } catch (error) {
    console.error('Start tracking error:', error);
    isTracking = false;
    currentJobId = null;
    return false;
  }
};

/**
 * หยุดติดตามตำแหน่ง (เมื่องานเสร็จ)
 */
export const stopTracking = async (): Promise<void> => {
  if (!isTracking) {
    return;
  }

  try {
    // หยุด background tracking
    const hasStarted = await Location.hasStartedLocationUpdatesAsync(
      LOCATION_TASK_NAME
    );
    if (hasStarted) {
      await Location.stopLocationUpdatesAsync(LOCATION_TASK_NAME);
      console.log('Background tracking stopped');
    }

    // หยุด foreground tracking
    if (trackingInterval) {
      clearInterval(trackingInterval);
      trackingInterval = null;
      console.log('Foreground tracking stopped');
    }

    isTracking = false;
    currentJobId = null;
  } catch (error) {
    console.error('Stop tracking error:', error);
  }
};

/**
 * ตรวจสอบว่ากำลังติดตามอยู่หรือไม่
 */
export const isTrackingLocation = (): boolean => {
  return isTracking;
};

/**
 * ดึง Job ID ที่กำลังติดตามอยู่
 */
export const getTrackingJobId = (): number | null => {
  return currentJobId;
};

/**
 * ดึงตำแหน่งปัจจุบัน (ครั้งเดียว)
 */
export const getCurrentLocation = async (): Promise<{
  latitude: number;
  longitude: number;
  accuracy?: number;
} | null> => {
  try {
    const permissions = await checkLocationPermission();

    if (!permissions.foreground) {
      console.error('Location permission not granted');
      return null;
    }

    const location = await Location.getCurrentPositionAsync({
      accuracy: Location.Accuracy.Balanced,
    });

    return {
      latitude: location.coords.latitude,
      longitude: location.coords.longitude,
      accuracy: location.coords.accuracy ?? undefined,
    };
  } catch (error) {
    console.error('Get current location error:', error);
    return null;
  }
};

/**
 * คำนวณระยะทางระหว่างสองจุด (Haversine formula)
 *
 * @param lat1 Latitude จุดที่ 1
 * @param lon1 Longitude จุดที่ 1
 * @param lat2 Latitude จุดที่ 2
 * @param lon2 Longitude จุดที่ 2
 * @returns ระยะทางเป็น กม.
 */
export const calculateDistance = (
  lat1: number,
  lon1: number,
  lat2: number,
  lon2: number
): number => {
  const earthRadius = 6371; // กม.

  const toRad = (value: number) => (value * Math.PI) / 180;

  const latDiff = toRad(lat2 - lat1);
  const lonDiff = toRad(lon2 - lon1);

  const a =
    Math.sin(latDiff / 2) * Math.sin(latDiff / 2) +
    Math.cos(toRad(lat1)) *
      Math.cos(toRad(lat2)) *
      Math.sin(lonDiff / 2) *
      Math.sin(lonDiff / 2);

  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return earthRadius * c;
};

/**
 * แปลงระยะทางเป็นข้อความ
 */
export const formatDistance = (distanceKm: number): string => {
  if (distanceKm < 1) {
    return `${Math.round(distanceKm * 1000)} ม.`;
  }
  return `${distanceKm.toFixed(1)} กม.`;
};

/**
 * ประมาณเวลาเดินทาง
 *
 * @param distanceKm ระยะทาง กม.
 * @param vehicleType ประเภทยานพาหนะ
 * @returns เวลาเป็นนาที
 */
export const estimateTravelTime = (
  distanceKm: number,
  vehicleType: 'motorcycle' | 'car' | 'bicycle' | 'walk' = 'motorcycle'
): number => {
  // ความเร็วเฉลี่ยในเมือง (กม./ชม.)
  const avgSpeed = {
    motorcycle: 30,
    car: 25,
    bicycle: 15,
    walk: 5,
  };

  const speed = avgSpeed[vehicleType] || 25;
  return Math.ceil((distanceKm / speed) * 60);
};

/**
 * แปลงเวลาเป็นข้อความ
 */
export const formatDuration = (minutes: number): string => {
  if (minutes < 60) {
    return `${minutes} นาที`;
  }

  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;

  if (mins === 0) {
    return `${hours} ชั่วโมง`;
  }

  return `${hours} ชม. ${mins} นาที`;
};

// =====================================================
// ⭐ GPS Sharing Functions - แชร์ตำแหน่งให้ Admin
// =====================================================

/**
 * ส่งตำแหน่งไปยัง Admin GPS Monitor
 */
const sendGpsToAdmin = async (): Promise<void> => {
  try {
    const location = await Location.getCurrentPositionAsync({
      accuracy: Location.Accuracy.Balanced,
    });

    const deviceModel = Device.modelName || 'Unknown';
    const osVersion = Platform.OS + ' ' + Platform.Version;

    await shareGpsLocation({
      latitude: location.coords.latitude,
      longitude: location.coords.longitude,
      altitude: location.coords.altitude ?? undefined,
      accuracy: location.coords.accuracy ?? undefined,
      speed: location.coords.speed
        ? location.coords.speed * 3.6 // m/s to km/h
        : undefined,
      heading: location.coords.heading ?? undefined,
      device_model: deviceModel,
      os_version: osVersion,
      battery_level: undefined, // ไม่มี expo-battery ในตอนนี้
    });

    console.log('📍 GPS shared to admin');
  } catch (error) {
    console.error('Send GPS to admin error:', error);
  }
};

/**
 * เริ่ม GPS Sharing
 * ส่งตำแหน่งให้ Admin ทุก 30 วินาที
 *
 * @returns boolean สำเร็จหรือไม่
 */
export const startGpsSharing = async (): Promise<boolean> => {
  if (isGpsSharing) {
    console.log('GPS sharing already active');
    return true;
  }

  try {
    // ตรวจสอบสิทธิ์
    const permissions = await checkLocationPermission();

    if (!permissions.foreground) {
      console.error('Location permission not granted for GPS sharing');
      return false;
    }

    isGpsSharing = true;

    // ส่งตำแหน่งแรกทันที
    await sendGpsToAdmin();

    // ตั้ง interval ส่งทุก 30 วินาที
    gpsSharingInterval = setInterval(async () => {
      if (isGpsSharing) {
        await sendGpsToAdmin();
      }
    }, 30000); // 30 วินาที

    console.log('✅ GPS sharing started');
    return true;
  } catch (error) {
    console.error('Start GPS sharing error:', error);
    isGpsSharing = false;
    return false;
  }
};

/**
 * หยุด GPS Sharing
 */
export const stopGpsSharing = async (): Promise<void> => {
  if (!isGpsSharing) {
    return;
  }

  try {
    // หยุด interval
    if (gpsSharingInterval) {
      clearInterval(gpsSharingInterval);
      gpsSharingInterval = null;
    }

    // แจ้ง server ว่าหยุดแชร์
    await apiStopGpsSharing().catch(() => {});

    isGpsSharing = false;
    console.log('🛑 GPS sharing stopped');
  } catch (error) {
    console.error('Stop GPS sharing error:', error);
  }
};

/**
 * ตรวจสอบว่ากำลัง GPS Sharing อยู่หรือไม่
 */
export const isGpsSharingActive = (): boolean => {
  return isGpsSharing;
};
