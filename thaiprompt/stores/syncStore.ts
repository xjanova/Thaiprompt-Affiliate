/**
 * Sync Store - จัดการสถานะการ sync กับเซิร์ฟเวอร์
 * ใช้ Zustand สำหรับ state management
 */

import { create } from 'zustand';
import NetInfo from '@react-native-community/netinfo';

export type SyncStatus = 'online' | 'offline' | 'syncing' | 'error';

interface SyncState {
  // สถานะหลัก
  status: SyncStatus;
  isConnected: boolean;
  lastSyncTime: Date | null;
  pendingCount: number;
  errorMessage: string | null;

  // Actions
  setStatus: (status: SyncStatus) => void;
  setConnected: (connected: boolean) => void;
  setLastSyncTime: (time: Date) => void;
  setPendingCount: (count: number) => void;
  setError: (message: string | null) => void;
  startSync: () => void;
  completeSync: () => void;
  failSync: (error: string) => void;
  reset: () => void;
}

export const useSyncStore = create<SyncState>((set) => ({
  // Initial state
  status: 'online',
  isConnected: true,
  lastSyncTime: null,
  pendingCount: 0,
  errorMessage: null,

  // Actions
  setStatus: (status) => set({ status }),

  setConnected: (connected) =>
    set({
      isConnected: connected,
      status: connected ? 'online' : 'offline',
      errorMessage: connected ? null : 'ไม่มีการเชื่อมต่ออินเทอร์เน็ต',
    }),

  setLastSyncTime: (time) => set({ lastSyncTime: time }),

  setPendingCount: (count) => set({ pendingCount: count }),

  setError: (message) =>
    set({
      errorMessage: message,
      status: message ? 'error' : 'online',
    }),

  startSync: () =>
    set({
      status: 'syncing',
      errorMessage: null,
    }),

  completeSync: () =>
    set({
      status: 'online',
      lastSyncTime: new Date(),
      errorMessage: null,
      pendingCount: 0,
    }),

  failSync: (error) =>
    set({
      status: 'error',
      errorMessage: error,
    }),

  reset: () =>
    set({
      status: 'online',
      isConnected: true,
      lastSyncTime: null,
      pendingCount: 0,
      errorMessage: null,
    }),
}));

/**
 * Hook สำหรับเริ่มต้น network monitor และ sync status
 * ⭐ เพิ่ม error handling เพื่อป้องกัน crash
 */
export const initSyncMonitor = (): (() => void) => {
  const { setConnected, startSync, completeSync } = useSyncStore.getState();

  let unsubscribe: (() => void) | null = null;

  try {
    // Subscribe to network changes
    unsubscribe = NetInfo.addEventListener((state) => {
      try {
        const connected = state.isConnected ?? false;
        setConnected(connected);

        // ถ้ากลับมา online ให้ trigger sync
        if (connected) {
          startSync();
          // Simulate sync completion (ในการใช้งานจริงจะเรียก API)
          setTimeout(() => {
            completeSync();
          }, 1500);
        }
      } catch (error) {
        console.warn('Network state change handler error:', error);
      }
    });

    // Initial check
    NetInfo.fetch()
      .then((state) => {
        setConnected(state.isConnected ?? false);
      })
      .catch((error) => {
        console.warn('Initial network check error:', error);
        // ถือว่า online ถ้า check ไม่ได้
        setConnected(true);
      });
  } catch (error) {
    console.warn('initSyncMonitor error:', error);
    // ถ้า NetInfo ไม่ทำงาน ถือว่า online
    setConnected(true);
  }

  // Return cleanup function ที่ปลอดภัย
  return () => {
    try {
      if (unsubscribe) {
        unsubscribe();
      }
    } catch (error) {
      console.warn('Cleanup sync monitor error:', error);
    }
  };
};
