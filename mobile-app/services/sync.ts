/**
 * Sync Service - จัดการ synchronization ระหว่าง local และ server
 */

import * as Storage from './storage';
import * as Cache from './cache';
import * as Network from './network';
import {
  getDashboardStats,
  getCommissions,
  getReferrals,
  getCurrentUser,
} from './api';

interface SyncStatus {
  lastSync: number | null;
  isSyncing: boolean;
  error: string | null;
}

let syncStatus: SyncStatus = {
  lastSync: null,
  isSyncing: false,
  error: null,
};

const syncListeners: Array<(status: SyncStatus) => void> = [];

/**
 * เพิ่ม listener สำหรับ sync status
 */
export const addSyncListener = (
  listener: (status: SyncStatus) => void
): (() => void) => {
  syncListeners.push(listener);
  return () => {
    const index = syncListeners.indexOf(listener);
    if (index > -1) {
      syncListeners.splice(index, 1);
    }
  };
};

/**
 * Notify listeners
 */
const notifyListeners = () => {
  syncListeners.forEach((listener) => listener(syncStatus));
};

/**
 * ดึง sync status
 */
export const getSyncStatus = (): SyncStatus => syncStatus;

/**
 * Sync ข้อมูลทั้งหมดจาก server
 */
export const syncAll = async (): Promise<boolean> => {
  if (!Network.isOnline()) {
    console.log('Cannot sync: offline');
    return false;
  }

  if (syncStatus.isSyncing) {
    console.log('Sync already in progress');
    return false;
  }

  try {
    syncStatus = { ...syncStatus, isSyncing: true, error: null };
    notifyListeners();

    console.log('Starting full sync...');

    // Sync ข้อมูลต่างๆ พร้อมกัน
    await Promise.all([
      syncDashboard(),
      syncCommissions(),
      syncReferrals(),
      syncUserProfile(),
    ]);

    // บันทึกเวลา sync
    const now = Date.now();
    await Storage.setItem(Storage.STORAGE_KEYS.LAST_SYNC, now);

    syncStatus = {
      lastSync: now,
      isSyncing: false,
      error: null,
    };
    notifyListeners();

    console.log('Full sync completed');
    return true;
  } catch (error) {
    console.error('Sync error:', error);
    syncStatus = {
      ...syncStatus,
      isSyncing: false,
      error: error instanceof Error ? error.message : 'Sync failed',
    };
    notifyListeners();
    return false;
  }
};

/**
 * Sync Dashboard data
 */
export const syncDashboard = async (): Promise<void> => {
  try {
    const stats = await getDashboardStats();
    if (stats) {
      await Cache.setCache(
        Cache.CACHE_KEYS.DASHBOARD_STATS,
        stats,
        Cache.OFFLINE_CACHE_DURATION
      );
    }
  } catch (error) {
    console.error('Sync dashboard error:', error);
    throw error;
  }
};

/**
 * Sync Commissions data
 */
export const syncCommissions = async (): Promise<void> => {
  try {
    const commissions = await getCommissions(1);
    if (commissions) {
      await Cache.setCache(
        Cache.CACHE_KEYS.COMMISSIONS_LIST,
        commissions,
        Cache.OFFLINE_CACHE_DURATION
      );
    }
  } catch (error) {
    console.error('Sync commissions error:', error);
    throw error;
  }
};

/**
 * Sync Referrals data
 */
export const syncReferrals = async (): Promise<void> => {
  try {
    const referrals = await getReferrals();
    if (referrals) {
      await Cache.setCache(
        Cache.CACHE_KEYS.REFERRALS,
        referrals,
        Cache.OFFLINE_CACHE_DURATION
      );
    }
  } catch (error) {
    console.error('Sync referrals error:', error);
    throw error;
  }
};

/**
 * Sync User Profile
 */
export const syncUserProfile = async (): Promise<void> => {
  try {
    const user = await getCurrentUser();
    if (user) {
      await Cache.setCache(
        Cache.CACHE_KEYS.USER_PROFILE,
        user,
        Cache.OFFLINE_CACHE_DURATION
      );
    }
  } catch (error) {
    console.error('Sync user profile error:', error);
    throw error;
  }
};

/**
 * โหลด last sync timestamp
 */
export const loadLastSyncTime = async (): Promise<number | null> => {
  const lastSync = await Storage.getItem<number>(Storage.STORAGE_KEYS.LAST_SYNC);
  syncStatus.lastSync = lastSync;
  return lastSync;
};

/**
 * ตรวจสอบว่าควร sync หรือยัง (ถ้าเกิน 5 นาที)
 */
export const shouldSync = async (): Promise<boolean> => {
  const lastSync = await loadLastSyncTime();
  if (!lastSync) return true;

  const fiveMinutes = 5 * 60 * 1000;
  return Date.now() - lastSync > fiveMinutes;
};

/**
 * Auto sync เมื่อกลับมา online
 */
export const setupAutoSync = (): (() => void) => {
  return Network.addNetworkListener(async (connected) => {
    if (connected) {
      const needsSync = await shouldSync();
      if (needsSync) {
        console.log('Auto syncing after coming online...');
        await syncAll();
      }
    }
  });
};

/**
 * Format last sync time สำหรับแสดงผล
 */
export const getLastSyncFormatted = (): string => {
  if (!syncStatus.lastSync) return 'ยังไม่เคย sync';

  const diff = Date.now() - syncStatus.lastSync;
  const minutes = Math.floor(diff / 60000);
  const hours = Math.floor(minutes / 60);

  if (minutes < 1) return 'เมื่อสักครู่';
  if (minutes < 60) return `${minutes} นาทีที่แล้ว`;
  if (hours < 24) return `${hours} ชั่วโมงที่แล้ว`;

  return new Date(syncStatus.lastSync).toLocaleDateString('th-TH', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
};
