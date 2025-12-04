/**
 * Storage Service - จัดการ Local Storage
 * ใช้ AsyncStorage สำหรับเก็บข้อมูล offline
 */

import AsyncStorage from '@react-native-async-storage/async-storage';

const STORAGE_PREFIX = '@thaiprompt:';

/**
 * เก็บข้อมูลลง Storage
 */
export const setItem = async <T>(key: string, value: T): Promise<void> => {
  try {
    const jsonValue = JSON.stringify(value);
    await AsyncStorage.setItem(STORAGE_PREFIX + key, jsonValue);
  } catch (error) {
    console.error('Storage setItem error:', error);
    throw error;
  }
};

/**
 * ดึงข้อมูลจาก Storage
 */
export const getItem = async <T>(key: string): Promise<T | null> => {
  try {
    const jsonValue = await AsyncStorage.getItem(STORAGE_PREFIX + key);
    return jsonValue != null ? JSON.parse(jsonValue) : null;
  } catch (error) {
    console.error('Storage getItem error:', error);
    return null;
  }
};

/**
 * ลบข้อมูลจาก Storage
 */
export const removeItem = async (key: string): Promise<void> => {
  try {
    await AsyncStorage.removeItem(STORAGE_PREFIX + key);
  } catch (error) {
    console.error('Storage removeItem error:', error);
  }
};

/**
 * ลบข้อมูลทั้งหมด
 */
export const clearAll = async (): Promise<void> => {
  try {
    const keys = await AsyncStorage.getAllKeys();
    const appKeys = keys.filter((k) => k.startsWith(STORAGE_PREFIX));
    await AsyncStorage.multiRemove(appKeys);
  } catch (error) {
    console.error('Storage clearAll error:', error);
  }
};

/**
 * ดึง keys ทั้งหมด
 */
export const getAllKeys = async (): Promise<string[]> => {
  try {
    const keys = await AsyncStorage.getAllKeys();
    return keys
      .filter((k) => k.startsWith(STORAGE_PREFIX))
      .map((k) => k.replace(STORAGE_PREFIX, ''));
  } catch (error) {
    console.error('Storage getAllKeys error:', error);
    return [];
  }
};

/**
 * เก็บข้อมูลหลายรายการ
 */
export const multiSet = async (
  items: Array<{ key: string; value: unknown }>
): Promise<void> => {
  try {
    const pairs: [string, string][] = items.map((item) => [
      STORAGE_PREFIX + item.key,
      JSON.stringify(item.value),
    ]);
    await AsyncStorage.multiSet(pairs);
  } catch (error) {
    console.error('Storage multiSet error:', error);
  }
};

/**
 * ดึงข้อมูลหลายรายการ
 */
export const multiGet = async <T>(
  keys: string[]
): Promise<Record<string, T | null>> => {
  try {
    const prefixedKeys = keys.map((k) => STORAGE_PREFIX + k);
    const pairs = await AsyncStorage.multiGet(prefixedKeys);
    const result: Record<string, T | null> = {};
    pairs.forEach(([key, value]) => {
      const cleanKey = key.replace(STORAGE_PREFIX, '');
      result[cleanKey] = value ? JSON.parse(value) : null;
    });
    return result;
  } catch (error) {
    console.error('Storage multiGet error:', error);
    return {};
  }
};

// Storage Keys
export const STORAGE_KEYS = {
  // User Data
  USER_PROFILE: 'user_profile',
  USER_SETTINGS: 'user_settings',

  // Cache Data
  CACHE_DASHBOARD: 'cache_dashboard',
  CACHE_COMMISSIONS: 'cache_commissions',
  CACHE_REFERRALS: 'cache_referrals',
  CACHE_NETWORK: 'cache_network',
  CACHE_PRODUCTS: 'cache_products',
  CACHE_WALLET: 'cache_wallet',

  // Offline Queue
  OFFLINE_QUEUE: 'offline_queue',

  // Last Sync
  LAST_SYNC: 'last_sync',

  // Theme & Settings
  THEME_MODE: 'theme_mode',
  LANGUAGE: 'language',
} as const;
