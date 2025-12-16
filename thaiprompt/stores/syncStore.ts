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
 */
export const initSyncMonitor = (): (() => void) => {
  const { setConnected, startSync, completeSync } = useSyncStore.getState();

  // Subscribe to network changes
  const unsubscribe = NetInfo.addEventListener((state) => {
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
  });

  // Initial check
  NetInfo.fetch().then((state) => {
    setConnected(state.isConnected ?? false);
  });

  return unsubscribe;
};
