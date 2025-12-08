/**
 * Push Notification Service - จัดการ Push Notifications
 *
 * รองรับ:
 * 1. การยืนยันว่าได้รับ notification
 * 2. การดึง pending notifications เมื่อ device กลับมา online
 * 3. การติดตามสถานะการส่ง
 * 4. Retry mechanism เมื่อ device offline
 */

import NetInfo from '@react-native-community/netinfo';
import { api } from './api';
import { API_ENDPOINTS } from '../constants';
import * as SecureStore from 'expo-secure-store';

// =====================================================
// Types
// =====================================================

export interface PendingNotification {
  id: number;
  deliveryId: number;
  title: string;
  body: string;
  image?: string;
  data?: Record<string, any>;
  createdAt: string;
}

export interface PushDeliveryConfirmation {
  deviceId: string;
  notificationId: number;
}

export interface PushBulkConfirmation {
  deviceId: string;
  deliveryIds: number[];
}

// =====================================================
// Storage Keys
// =====================================================

const PENDING_CONFIRMATIONS_KEY = 'pending_push_confirmations';
const DEVICE_ID_KEY = 'device_id';

// =====================================================
// Push Notification Service
// =====================================================

class PushNotificationService {
  private isInitialized: boolean = false;
  private unsubscribeNetInfo: (() => void) | null = null;

  /**
   * เริ่มต้นระบบ Push Notification
   * เรียกเมื่อแอพเปิด
   */
  async initialize(): Promise<void> {
    if (this.isInitialized) return;

    // ฟัง network state change
    this.unsubscribeNetInfo = NetInfo.addEventListener(async (state) => {
      if (state.isConnected && state.isInternetReachable) {
        // Device กลับมา online - ส่ง confirmations ที่ค้างอยู่
        await this.processPendingConfirmations();
        // ดึง notifications ที่ยังไม่ได้รับ
        await this.fetchPendingNotifications();
      }
    });

    this.isInitialized = true;
    console.log('Push Notification Service initialized');
  }

  /**
   * ทำลายการเชื่อมต่อเมื่อแอพปิด
   */
  destroy(): void {
    if (this.unsubscribeNetInfo) {
      this.unsubscribeNetInfo();
      this.unsubscribeNetInfo = null;
    }
    this.isInitialized = false;
  }

  /**
   * ยืนยันว่าได้รับ notification แล้ว
   * เรียกเมื่อ device ได้รับ push notification
   *
   * @param notificationId ID ของ notification
   */
  async confirmDelivery(notificationId: number): Promise<boolean> {
    const deviceId = await this.getDeviceId();
    if (!deviceId) return false;

    // ตรวจสอบว่า online หรือไม่
    const netInfo = await NetInfo.fetch();
    if (!netInfo.isConnected || !netInfo.isInternetReachable) {
      // Offline - เก็บไว้ส่งทีหลัง
      await this.savePendingConfirmation(notificationId);
      return false;
    }

    try {
      const response = await api.post(API_ENDPOINTS.PUSH_CONFIRM, {
        deviceId,
        notificationId,
      });

      return response.data?.success ?? false;
    } catch (error) {
      // บันทึกไว้ส่งทีหลัง
      await this.savePendingConfirmation(notificationId);
      console.error('Failed to confirm push delivery:', error);
      return false;
    }
  }

  /**
   * ดึง pending notifications จาก server
   * เรียกเมื่อ device กลับมา online
   */
  async fetchPendingNotifications(): Promise<PendingNotification[]> {
    const deviceId = await this.getDeviceId();
    if (!deviceId) return [];

    try {
      const response = await api.get(API_ENDPOINTS.PUSH_PENDING, {
        params: { deviceId },
      });

      if (response.data?.success) {
        const notifications = response.data.data?.notifications ?? [];

        // Process แต่ละ notification
        for (const notification of notifications) {
          // แสดง local notification (ถ้าต้องการ)
          await this.showLocalNotification(notification);
        }

        // Bulk confirm ว่าได้รับแล้ว
        if (notifications.length > 0) {
          const deliveryIds = notifications.map((n: PendingNotification) => n.deliveryId);
          await this.bulkConfirmDelivery(deliveryIds);
        }

        return notifications;
      }

      return [];
    } catch (error) {
      console.error('Failed to fetch pending notifications:', error);
      return [];
    }
  }

  /**
   * Bulk confirm หลาย notifications พร้อมกัน
   *
   * @param deliveryIds รายการ delivery IDs
   */
  async bulkConfirmDelivery(deliveryIds: number[]): Promise<boolean> {
    const deviceId = await this.getDeviceId();
    if (!deviceId || deliveryIds.length === 0) return false;

    try {
      const response = await api.post(API_ENDPOINTS.PUSH_BULK_CONFIRM, {
        deviceId,
        deliveryIds,
      });

      return response.data?.success ?? false;
    } catch (error) {
      console.error('Failed to bulk confirm deliveries:', error);
      return false;
    }
  }

  /**
   * ส่ง confirmations ที่ค้างอยู่เมื่อ offline
   */
  private async processPendingConfirmations(): Promise<void> {
    try {
      const pending = await this.getPendingConfirmations();
      if (pending.length === 0) return;

      const deviceId = await this.getDeviceId();
      if (!deviceId) return;

      // ลองส่งทีละอัน
      const successful: number[] = [];
      for (const notificationId of pending) {
        try {
          const response = await api.post(API_ENDPOINTS.PUSH_CONFIRM, {
            deviceId,
            notificationId,
          });

          if (response.data?.success) {
            successful.push(notificationId);
          }
        } catch {
          // ข้ามไปก่อน จะลองใหม่ครั้งหน้า
        }
      }

      // ลบออกจาก pending list
      if (successful.length > 0) {
        const remaining = pending.filter((id) => !successful.includes(id));
        await this.savePendingConfirmations(remaining);
      }

      console.log(`Processed ${successful.length}/${pending.length} pending confirmations`);
    } catch (error) {
      console.error('Failed to process pending confirmations:', error);
    }
  }

  /**
   * แสดง local notification (สำหรับ notifications ที่ค้างอยู่)
   */
  private async showLocalNotification(notification: PendingNotification): Promise<void> {
    // TODO: Implement using expo-notifications
    // สำหรับตอนนี้แค่ log ไว้ก่อน
    console.log('Show local notification:', notification.title);
  }

  // =====================================================
  // Storage Helpers
  // =====================================================

  private async getDeviceId(): Promise<string | null> {
    try {
      return await SecureStore.getItemAsync(DEVICE_ID_KEY);
    } catch {
      return null;
    }
  }

  private async getPendingConfirmations(): Promise<number[]> {
    try {
      const data = await SecureStore.getItemAsync(PENDING_CONFIRMATIONS_KEY);
      return data ? JSON.parse(data) : [];
    } catch {
      return [];
    }
  }

  private async savePendingConfirmation(notificationId: number): Promise<void> {
    try {
      const pending = await this.getPendingConfirmations();
      if (!pending.includes(notificationId)) {
        pending.push(notificationId);
        await SecureStore.setItemAsync(PENDING_CONFIRMATIONS_KEY, JSON.stringify(pending));
      }
    } catch (error) {
      console.error('Failed to save pending confirmation:', error);
    }
  }

  private async savePendingConfirmations(ids: number[]): Promise<void> {
    try {
      await SecureStore.setItemAsync(PENDING_CONFIRMATIONS_KEY, JSON.stringify(ids));
    } catch (error) {
      console.error('Failed to save pending confirmations:', error);
    }
  }
}

// Export singleton instance
export const pushNotificationService = new PushNotificationService();

// Export helper functions
export const initPushNotificationService = () => pushNotificationService.initialize();
export const confirmPushDelivery = (notificationId: number) =>
  pushNotificationService.confirmDelivery(notificationId);
export const fetchPendingNotifications = () =>
  pushNotificationService.fetchPendingNotifications();
