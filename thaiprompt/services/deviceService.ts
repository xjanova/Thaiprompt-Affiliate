/**
 * Device Service - จัดการการลงทะเบียนและติดตามเครื่อง
 *
 * สำหรับ Admin Analytics Dashboard
 * - ติดตามจำนวนเครื่องที่ลงทะเบียน
 * - Daily Active Users (DAU)
 * - Retention Rate
 */

import * as Device from 'expo-device';
import * as Application from 'expo-application';
import * as Localization from 'expo-localization';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Platform } from 'react-native';
import { registerDevice, sendDeviceHeartbeat } from './api';
import { APP_INFO } from '@/config/appConfig';

// Storage key สำหรับเก็บ device ID
const DEVICE_ID_KEY = '@device_id';
const LAST_HEARTBEAT_KEY = '@last_heartbeat';

// ระยะเวลาขั้นต่ำระหว่าง heartbeat (15 นาที)
const HEARTBEAT_INTERVAL = 15 * 60 * 1000;

/**
 * สร้าง unique device ID
 */
const generateDeviceId = (): string => {
  const timestamp = Date.now().toString(36);
  const random = Math.random().toString(36).substring(2, 15);
  return `${timestamp}-${random}`;
};

/**
 * ดึง Device ID (สร้างใหม่ถ้ายังไม่มี)
 */
export const getDeviceId = async (): Promise<string> => {
  try {
    let deviceId = await AsyncStorage.getItem(DEVICE_ID_KEY);

    if (!deviceId) {
      deviceId = generateDeviceId();
      await AsyncStorage.setItem(DEVICE_ID_KEY, deviceId);
    }

    return deviceId;
  } catch (error) {
    console.error('Get device ID error:', error);
    return generateDeviceId();
  }
};

/**
 * ดึงข้อมูลเครื่อง
 */
export const getDeviceInfo = async () => {
  const deviceId = await getDeviceId();

  return {
    deviceId,
    platform: Platform.OS as 'ios' | 'android',
    deviceModel: Device.modelName || 'Unknown',
    deviceBrand: Device.brand || 'Unknown',
    osVersion: Device.osVersion || 'Unknown',
    appVersion: APP_INFO.VERSION,
    locale: Localization.locale || 'th-TH',
    timezone: Localization.timezone || 'Asia/Bangkok',
  };
};

/**
 * ลงทะเบียนเครื่องกับเซิร์ฟเวอร์
 * เรียกใช้ตอนเปิดแอพครั้งแรก
 */
export const registerDeviceOnStart = async (pushToken?: string): Promise<void> => {
  try {
    const deviceInfo = await getDeviceInfo();

    const result = await registerDevice({
      ...deviceInfo,
      pushToken,
    });

    if (result.success) {
      console.log('Device registered:', result.data?.isNewDevice ? 'New device' : 'Existing device');
    }
  } catch (error) {
    // Silent fail - ไม่กระทบต่อการใช้งานแอพ
    console.log('Device registration error (non-critical):', error);
  }
};

/**
 * ส่ง heartbeat เพื่อบันทึกว่าแอพยังใช้งานอยู่
 * ใช้สำหรับคำนวณ DAU/MAU
 */
export const sendHeartbeat = async (): Promise<void> => {
  try {
    // เช็คว่าส่ง heartbeat ไปแล้วหรือยังในช่วง interval
    const lastHeartbeat = await AsyncStorage.getItem(LAST_HEARTBEAT_KEY);
    const now = Date.now();

    if (lastHeartbeat) {
      const lastTime = parseInt(lastHeartbeat, 10);
      if (now - lastTime < HEARTBEAT_INTERVAL) {
        // ยังไม่ถึงเวลาส่ง heartbeat
        return;
      }
    }

    const deviceId = await getDeviceId();
    const result = await sendDeviceHeartbeat(deviceId);

    if (result.success) {
      await AsyncStorage.setItem(LAST_HEARTBEAT_KEY, now.toString());
    }
  } catch (error) {
    // Silent fail
    console.log('Heartbeat error (non-critical):', error);
  }
};

/**
 * เริ่มต้น Device Tracking
 * เรียกใช้เมื่อแอพเปิดขึ้น
 */
export const initDeviceTracking = async (pushToken?: string): Promise<void> => {
  // ลงทะเบียนเครื่อง
  await registerDeviceOnStart(pushToken);

  // ส่ง heartbeat
  await sendHeartbeat();
};

export default {
  getDeviceId,
  getDeviceInfo,
  registerDeviceOnStart,
  sendHeartbeat,
  initDeviceTracking,
};
