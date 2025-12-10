/**
 * Push Notification Service
 *
 * จัดการการตั้งค่าและรับ Push Notification
 * รองรับทั้ง Expo Push และ FCM
 */

import * as Notifications from 'expo-notifications';
import * as Device from 'expo-device';
import Constants from 'expo-constants';
import { Platform } from 'react-native';
import { registerPushToken, removePushToken } from './api';
import { APP_CONFIG } from '@/constants';
import { getDeviceId } from './deviceService';

// =====================================================
// Configuration
// =====================================================

// ตั้งค่าการแสดงผล notification เมื่อแอปเปิดอยู่
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: true,
  }),
});

// =====================================================
// Types
// =====================================================

export interface PushNotificationData {
  type?: string;
  ticketId?: number;
  orderId?: number;
  url?: string;
  [key: string]: unknown;
}

// =====================================================
// Functions
// =====================================================

/**
 * ตรวจสอบและขอสิทธิ์ Push Notification
 *
 * @returns Promise<boolean> - ได้รับอนุญาตหรือไม่
 */
export const requestNotificationPermission = async (): Promise<boolean> => {
  // ไม่รองรับบนอุปกรณ์ที่ไม่ใช่ physical device
  if (!Device.isDevice) {
    console.log('Push notifications are not supported on simulator/emulator');
    return false;
  }

  // ตรวจสอบสถานะปัจจุบัน
  const { status: existingStatus } = await Notifications.getPermissionsAsync();
  let finalStatus = existingStatus;

  // ขอสิทธิ์ถ้ายังไม่ได้รับ
  if (existingStatus !== 'granted') {
    const { status } = await Notifications.requestPermissionsAsync();
    finalStatus = status;
  }

  if (finalStatus !== 'granted') {
    console.log('Failed to get push notification permission');
    return false;
  }

  return true;
};

/**
 * ดึง Push Token และลงทะเบียนกับ server
 *
 * @returns Promise<string | null> - Push Token หรือ null ถ้าไม่สำเร็จ
 */
export const registerForPushNotifications = async (): Promise<string | null> => {
  try {
    // ขอสิทธิ์
    const hasPermission = await requestNotificationPermission();
    if (!hasPermission) {
      return null;
    }

    // ดึง Expo Push Token
    const tokenData = await Notifications.getExpoPushTokenAsync({
      projectId: Constants.expoConfig?.extra?.eas?.projectId,
    });

    const token = tokenData.data;
    console.log('Expo Push Token:', token);

    // ดึง device_id เพื่อเชื่อม push token กับเครื่อง
    const deviceId = await getDeviceId();

    // ลงทะเบียน token กับ server
    const deviceInfo = {
      token,
      platform: Platform.OS as 'android' | 'ios',
      device_id: deviceId,
      device_name: Device.modelName || undefined,
      app_version: APP_CONFIG.VERSION,
    };

    const response = await registerPushToken(deviceInfo);

    if (response.success) {
      console.log('Push token registered successfully');
    } else {
      console.error('Failed to register push token:', response.message);
    }

    // ตั้งค่า Android notification channel
    if (Platform.OS === 'android') {
      await setupAndroidChannels();
    }

    return token;
  } catch (error) {
    console.error('Register push notification error:', error);
    return null;
  }
};

/**
 * ยกเลิกการลงทะเบียน Push Token
 *
 * @param token - Token ที่ต้องการยกเลิก
 */
export const unregisterPushNotifications = async (token: string): Promise<void> => {
  try {
    await removePushToken(token);
    console.log('Push token unregistered successfully');
  } catch (error) {
    console.error('Unregister push notification error:', error);
  }
};

/**
 * ตั้งค่า Android Notification Channels
 */
const setupAndroidChannels = async (): Promise<void> => {
  // Default channel
  await Notifications.setNotificationChannelAsync('default', {
    name: 'ทั่วไป',
    description: 'การแจ้งเตือนทั่วไป',
    importance: Notifications.AndroidImportance.DEFAULT,
    vibrationPattern: [0, 250, 250, 250],
    lightColor: '#3B82F6',
  });

  // High priority channel for important notifications
  await Notifications.setNotificationChannelAsync('important', {
    name: 'สำคัญ',
    description: 'การแจ้งเตือนที่สำคัญ',
    importance: Notifications.AndroidImportance.HIGH,
    vibrationPattern: [0, 250, 250, 250],
    lightColor: '#EF4444',
    sound: 'default',
  });

  // Order/Rider channel
  await Notifications.setNotificationChannelAsync('orders', {
    name: 'ออเดอร์',
    description: 'การแจ้งเตือนเกี่ยวกับออเดอร์',
    importance: Notifications.AndroidImportance.HIGH,
    vibrationPattern: [0, 500, 250, 500],
    lightColor: '#10B981',
    sound: 'default',
  });

  // Messages/Tickets channel
  await Notifications.setNotificationChannelAsync('messages', {
    name: 'ข้อความ',
    description: 'การแจ้งเตือนข้อความใหม่',
    importance: Notifications.AndroidImportance.DEFAULT,
    vibrationPattern: [0, 250, 250, 250],
    lightColor: '#8B5CF6',
  });

  // Promotions channel
  await Notifications.setNotificationChannelAsync('promotions', {
    name: 'โปรโมชั่น',
    description: 'ข่าวสารและโปรโมชั่น',
    importance: Notifications.AndroidImportance.LOW,
    lightColor: '#F59E0B',
  });
};

/**
 * ฟังการรับ notification
 *
 * @param callback - Function ที่จะทำงานเมื่อได้รับ notification
 * @returns Subscription
 */
export const addNotificationReceivedListener = (
  callback: (notification: Notifications.Notification) => void
): Notifications.Subscription => {
  return Notifications.addNotificationReceivedListener(callback);
};

/**
 * ฟังการกด notification
 *
 * @param callback - Function ที่จะทำงานเมื่อกด notification
 * @returns Subscription
 */
export const addNotificationResponseListener = (
  callback: (response: Notifications.NotificationResponse) => void
): Notifications.Subscription => {
  return Notifications.addNotificationResponseReceivedListener(callback);
};

/**
 * ดึงข้อมูลจาก notification response
 *
 * @param response - Notification response
 * @returns PushNotificationData
 */
export const getNotificationData = (
  response: Notifications.NotificationResponse
): PushNotificationData => {
  return (response.notification.request.content.data as PushNotificationData) || {};
};

/**
 * ตั้งค่า badge count
 *
 * @param count - จำนวน badge
 */
export const setBadgeCount = async (count: number): Promise<void> => {
  await Notifications.setBadgeCountAsync(count);
};

/**
 * ดึง badge count
 *
 * @returns Promise<number>
 */
export const getBadgeCount = async (): Promise<number> => {
  return await Notifications.getBadgeCountAsync();
};

/**
 * ล้าง notification ทั้งหมด
 */
export const dismissAllNotifications = async (): Promise<void> => {
  await Notifications.dismissAllNotificationsAsync();
};

/**
 * ตรวจสอบว่าอุปกรณ์รองรับ Push Notification หรือไม่
 *
 * @returns boolean
 */
export const isPushNotificationSupported = (): boolean => {
  return Device.isDevice;
};

/**
 * ส่ง local notification (สำหรับทดสอบ)
 *
 * @param title - หัวข้อ
 * @param body - เนื้อหา
 * @param data - ข้อมูลเพิ่มเติม
 */
export const sendLocalNotification = async (
  title: string,
  body: string,
  data?: PushNotificationData
): Promise<void> => {
  await Notifications.scheduleNotificationAsync({
    content: {
      title,
      body,
      data,
      sound: 'default',
    },
    trigger: null, // ส่งทันที
  });
};
