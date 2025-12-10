/**
 * Push Notification Service
 *
 * จัดการการตั้งค่าและรับ Push Notification
 * รองรับทั้ง Expo Push และ FCM
 *
 * v1.6.4 - แก้ไข crash หลังอนุญาต push notification
 * - ย้าย setNotificationHandler ไปใน function
 * - เพิ่ม flag ป้องกันการเรียกซ้ำ
 * - เพิ่ม error handling ทุกขั้นตอน
 */

import * as Notifications from 'expo-notifications';
import * as Device from 'expo-device';
import Constants from 'expo-constants';
import { Platform } from 'react-native';
import { registerPushToken, removePushToken } from './api';
import { APP_INFO } from '@/config/appConfig';

// =====================================================
// State
// =====================================================

let isNotificationHandlerSet = false;
let isRegistering = false;

// =====================================================
// Configuration
// =====================================================

/**
 * ตั้งค่า Notification Handler อย่างปลอดภัย
 * เรียกครั้งเดียวตอนเริ่มแอพ
 */
export const setupNotificationHandler = (): void => {
  if (isNotificationHandlerSet) return;

  try {
    Notifications.setNotificationHandler({
      handleNotification: async () => ({
        shouldShowAlert: true,
        shouldPlaySound: true,
        shouldSetBadge: true,
      }),
    });
    isNotificationHandlerSet = true;
    console.log('✅ Notification handler set successfully');
  } catch (error) {
    console.warn('⚠️ Failed to set notification handler:', error);
  }
};

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
  try {
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
  } catch (error) {
    console.error('Request notification permission error:', error);
    return false;
  }
};

/**
 * ดึง Push Token และลงทะเบียนกับ server
 * ป้องกันการเรียกซ้ำซ้อน
 *
 * @returns Promise<string | null> - Push Token หรือ null ถ้าไม่สำเร็จ
 */
export const registerForPushNotifications = async (): Promise<string | null> => {
  // ป้องกันการเรียกซ้ำ
  if (isRegistering) {
    console.log('Push notification registration already in progress');
    return null;
  }

  isRegistering = true;

  try {
    // ตั้งค่า handler ก่อน (ถ้ายังไม่ได้ตั้ง)
    setupNotificationHandler();

    // ขอสิทธิ์
    const hasPermission = await requestNotificationPermission();
    if (!hasPermission) {
      isRegistering = false;
      return null;
    }

    // ดึง project ID
    const projectId = Constants.expoConfig?.extra?.eas?.projectId;
    if (!projectId) {
      console.warn('⚠️ No EAS project ID found, skipping push token registration');
      isRegistering = false;
      return null;
    }

    // ดึง Expo Push Token
    let token: string;
    try {
      const tokenData = await Notifications.getExpoPushTokenAsync({
        projectId,
      });
      token = tokenData.data;
      console.log('Expo Push Token:', token?.substring(0, 30) + '...');
    } catch (tokenError) {
      console.error('Failed to get Expo push token:', tokenError);
      isRegistering = false;
      return null;
    }

    // ลงทะเบียน token กับ server (non-blocking)
    try {
      const deviceInfo = {
        token,
        platform: Platform.OS as 'android' | 'ios',
        device_id: undefined, // จะดึงใน API
        device_name: Device.modelName || undefined,
        app_version: APP_INFO.VERSION,
      };

      const response = await registerPushToken(deviceInfo);

      if (response.success) {
        console.log('✅ Push token registered successfully');
      } else {
        console.warn('⚠️ Failed to register push token:', response.message);
      }
    } catch (registerError) {
      // ไม่ crash ถ้าลงทะเบียนไม่สำเร็จ
      console.warn('⚠️ Push token registration error (non-critical):', registerError);
    }

    // ตั้งค่า Android notification channel (non-blocking)
    if (Platform.OS === 'android') {
      setupAndroidChannels().catch((e) => {
        console.warn('⚠️ Android channels setup error (non-critical):', e);
      });
    }

    isRegistering = false;
    return token;
  } catch (error) {
    console.error('Register push notification error:', error);
    isRegistering = false;
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
  try {
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

    console.log('✅ Android notification channels set up');
  } catch (error) {
    console.error('Setup Android channels error:', error);
  }
};

/**
 * ฟังการรับ notification (พร้อม error handling)
 *
 * @param callback - Function ที่จะทำงานเมื่อได้รับ notification
 * @returns Subscription
 */
export const addNotificationReceivedListener = (
  callback: (notification: Notifications.Notification) => void
): Notifications.Subscription => {
  try {
    return Notifications.addNotificationReceivedListener((notification) => {
      try {
        callback(notification);
      } catch (e) {
        console.error('Notification received callback error:', e);
      }
    });
  } catch (e) {
    console.error('Add notification listener error:', e);
    // Return a dummy subscription that does nothing
    return { remove: () => {} } as Notifications.Subscription;
  }
};

/**
 * ฟังการกด notification (พร้อม error handling)
 *
 * @param callback - Function ที่จะทำงานเมื่อกด notification
 * @returns Subscription
 */
export const addNotificationResponseListener = (
  callback: (response: Notifications.NotificationResponse) => void
): Notifications.Subscription => {
  try {
    return Notifications.addNotificationResponseReceivedListener((response) => {
      try {
        callback(response);
      } catch (e) {
        console.error('Notification response callback error:', e);
      }
    });
  } catch (e) {
    console.error('Add notification response listener error:', e);
    // Return a dummy subscription that does nothing
    return { remove: () => {} } as Notifications.Subscription;
  }
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
  try {
    return (response.notification.request.content.data as PushNotificationData) || {};
  } catch (e) {
    console.error('Get notification data error:', e);
    return {};
  }
};

/**
 * ตั้งค่า badge count
 *
 * @param count - จำนวน badge
 */
export const setBadgeCount = async (count: number): Promise<void> => {
  try {
    await Notifications.setBadgeCountAsync(count);
  } catch (e) {
    console.warn('Set badge count error:', e);
  }
};

/**
 * ดึง badge count
 *
 * @returns Promise<number>
 */
export const getBadgeCount = async (): Promise<number> => {
  try {
    return await Notifications.getBadgeCountAsync();
  } catch (e) {
    console.warn('Get badge count error:', e);
    return 0;
  }
};

/**
 * ล้าง notification ทั้งหมด
 */
export const dismissAllNotifications = async (): Promise<void> => {
  try {
    await Notifications.dismissAllNotificationsAsync();
  } catch (e) {
    console.warn('Dismiss notifications error:', e);
  }
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
  try {
    await Notifications.scheduleNotificationAsync({
      content: {
        title,
        body,
        data,
        sound: 'default',
      },
      trigger: null, // ส่งทันที
    });
  } catch (e) {
    console.error('Send local notification error:', e);
  }
};
