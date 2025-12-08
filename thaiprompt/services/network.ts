/**
 * Network Service - ตรวจสอบสถานะ Internet
 * และจัดการ Offline Queue
 */

import NetInfo, { NetInfoState } from '@react-native-community/netinfo';
import * as Storage from './storage';

// =====================================================
// Network Status
// =====================================================

let isConnected = true;
let connectionType: string | null = null;
const listeners: Array<(connected: boolean) => void> = [];

/**
 * เริ่มต้น Network Monitor
 */
export const initNetworkMonitor = (): (() => void) => {
  const unsubscribe = NetInfo.addEventListener((state: NetInfoState) => {
    const wasConnected = isConnected;
    isConnected = state.isConnected ?? false;
    connectionType = state.type;

    // Notify listeners ถ้าสถานะเปลี่ยน
    if (wasConnected !== isConnected) {
      listeners.forEach((listener) => listener(isConnected));

      // ถ้ากลับมา online ให้ process queue
      if (isConnected) {
        processOfflineQueue();
      }
    }
  });

  return unsubscribe;
};

/**
 * ตรวจสอบว่า online หรือไม่
 */
export const isOnline = (): boolean => isConnected;

/**
 * ดึงประเภทการเชื่อมต่อ
 */
export const getConnectionType = (): string | null => connectionType;

/**
 * เพิ่ม listener สำหรับ network status change
 */
export const addNetworkListener = (
  listener: (connected: boolean) => void
): (() => void) => {
  listeners.push(listener);
  return () => {
    const index = listeners.indexOf(listener);
    if (index > -1) {
      listeners.splice(index, 1);
    }
  };
};

/**
 * ตรวจสอบสถานะ network แบบ manual
 */
export const checkNetworkStatus = async (): Promise<boolean> => {
  const state = await NetInfo.fetch();
  isConnected = state.isConnected ?? false;
  connectionType = state.type;
  return isConnected;
};

// =====================================================
// Offline Queue - เก็บ actions ที่ทำตอน offline
// =====================================================

interface QueueItem {
  id: string;
  type: string;
  action: string;
  data: unknown;
  timestamp: number;
  retries: number;
}

const MAX_RETRIES = 3;

/**
 * เพิ่ม action ลง offline queue
 */
export const addToOfflineQueue = async (
  type: string,
  action: string,
  data: unknown
): Promise<void> => {
  const queue = await getOfflineQueue();

  const item: QueueItem = {
    id: `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
    type,
    action,
    data,
    timestamp: Date.now(),
    retries: 0,
  };

  queue.push(item);
  await Storage.setItem(Storage.STORAGE_KEYS.OFFLINE_QUEUE, queue);
};

/**
 * ดึง offline queue
 */
export const getOfflineQueue = async (): Promise<QueueItem[]> => {
  const queue = await Storage.getItem<QueueItem[]>(
    Storage.STORAGE_KEYS.OFFLINE_QUEUE
  );
  return queue || [];
};

/**
 * ลบ item จาก queue
 */
export const removeFromQueue = async (id: string): Promise<void> => {
  const queue = await getOfflineQueue();
  const filtered = queue.filter((item) => item.id !== id);
  await Storage.setItem(Storage.STORAGE_KEYS.OFFLINE_QUEUE, filtered);
};

/**
 * Process offline queue เมื่อกลับมา online
 */
export const processOfflineQueue = async (): Promise<void> => {
  if (!isConnected) return;

  const queue = await getOfflineQueue();
  if (queue.length === 0) return;

  console.log(`Processing ${queue.length} offline queue items...`);

  for (const item of queue) {
    try {
      // Process item based on type
      await processQueueItem(item);
      await removeFromQueue(item.id);
      console.log(`Queue item ${item.id} processed successfully`);
    } catch (error) {
      console.error(`Queue item ${item.id} failed:`, error);

      // Increment retry count
      item.retries++;

      if (item.retries >= MAX_RETRIES) {
        // ลบออกถ้า retry เกิน limit
        await removeFromQueue(item.id);
        console.log(`Queue item ${item.id} removed after ${MAX_RETRIES} retries`);
      } else {
        // อัพเดท retry count
        const updatedQueue = await getOfflineQueue();
        const index = updatedQueue.findIndex((q) => q.id === item.id);
        if (index > -1) {
          updatedQueue[index] = item;
          await Storage.setItem(Storage.STORAGE_KEYS.OFFLINE_QUEUE, updatedQueue);
        }
      }
    }
  }
};

/**
 * Process queue item - ประมวลผล offline queue item ตาม type
 *
 * @param item รายการที่รอดำเนินการ
 */
const processQueueItem = async (item: QueueItem): Promise<void> => {
  // Import API functions dynamically to avoid circular dependency
  const api = await import('./api');

  switch (item.type) {
    case 'commission':
      // Commission actions ไม่มี offline action (read-only)
      console.log('Commission item processed (no action needed):', item.id);
      break;

    case 'order':
      // Order actions - ส่ง pending order ไปยัง server
      // TODO: เพิ่ม API endpoint สำหรับ sync order เมื่อพร้อม
      console.log('Order sync not yet implemented:', item.id);
      break;

    case 'profile':
      // Profile updates - อัพเดทข้อมูลโปรไฟล์
      if (item.action === 'update' && item.data) {
        const profileData = item.data as {
          name?: string;
          phone?: string;
          address?: string;
        };
        const result = await api.updateProfile(profileData);
        if (!result.success) {
          throw new Error(result.message || 'Profile update failed');
        }
        console.log('Profile updated from offline queue:', item.id);
      }
      break;

    case 'location':
      // Location updates - ส่งตำแหน่งที่ค้างไว้
      if (item.data) {
        const locationData = item.data as {
          latitude: number;
          longitude: number;
          [key: string]: unknown;
        };
        await api.updateRiderLocation(locationData);
        console.log('Location sent from offline queue:', item.id);
      }
      break;

    default:
      console.warn(`Unknown queue item type: ${item.type}`, item);
  }
};

/**
 * ดึงจำนวน items ใน queue
 */
export const getQueueCount = async (): Promise<number> => {
  const queue = await getOfflineQueue();
  return queue.length;
};

/**
 * Clear offline queue
 */
export const clearOfflineQueue = async (): Promise<void> => {
  await Storage.setItem(Storage.STORAGE_KEYS.OFFLINE_QUEUE, []);
};
