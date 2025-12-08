/**
 * Cache Service - จัดการ Cache สำหรับ API responses
 * รองรับการทำงาน Offline
 */

import * as Storage from './storage';

interface CacheEntry<T> {
  data: T;
  timestamp: number;
  expiresAt: number;
}

// Default cache duration: 5 minutes
const DEFAULT_CACHE_DURATION = 5 * 60 * 1000;

// Long cache duration: 1 hour (สำหรับข้อมูลที่ไม่ค่อยเปลี่ยน)
const LONG_CACHE_DURATION = 60 * 60 * 1000;

// Offline cache duration: 24 hours
const OFFLINE_CACHE_DURATION = 24 * 60 * 60 * 1000;

/**
 * เก็บ cache
 */
export const setCache = async <T>(
  key: string,
  data: T,
  duration: number = DEFAULT_CACHE_DURATION
): Promise<void> => {
  const entry: CacheEntry<T> = {
    data,
    timestamp: Date.now(),
    expiresAt: Date.now() + duration,
  };
  await Storage.setItem(`cache:${key}`, entry);
};

/**
 * ดึง cache
 */
export const getCache = async <T>(
  key: string,
  ignoreExpiry: boolean = false
): Promise<T | null> => {
  try {
    const entry = await Storage.getItem<CacheEntry<T>>(`cache:${key}`);

    if (!entry) return null;

    // ตรวจสอบว่า cache หมดอายุหรือยัง
    if (!ignoreExpiry && Date.now() > entry.expiresAt) {
      // Cache หมดอายุ แต่ยังคืนค่าได้ถ้าต้องการ (สำหรับ offline mode)
      return null;
    }

    return entry.data;
  } catch (error) {
    console.error('Cache get error:', error);
    return null;
  }
};

/**
 * ดึง cache แม้หมดอายุ (สำหรับ offline mode)
 */
export const getCacheForOffline = async <T>(key: string): Promise<T | null> => {
  return getCache<T>(key, true);
};

/**
 * ลบ cache
 */
export const removeCache = async (key: string): Promise<void> => {
  await Storage.removeItem(`cache:${key}`);
};

/**
 * ลบ cache ทั้งหมด
 */
export const clearAllCache = async (): Promise<void> => {
  const keys = await Storage.getAllKeys();
  const cacheKeys = keys.filter((k) => k.startsWith('cache:'));
  for (const key of cacheKeys) {
    await Storage.removeItem(key);
  }
};

/**
 * ตรวจสอบว่า cache ยังใช้ได้หรือไม่
 */
export const isCacheValid = async (key: string): Promise<boolean> => {
  const entry = await Storage.getItem<CacheEntry<unknown>>(`cache:${key}`);
  return entry !== null && Date.now() <= entry.expiresAt;
};

/**
 * ดึงเวลาที่ cache ล่าสุด
 */
export const getCacheTimestamp = async (key: string): Promise<number | null> => {
  const entry = await Storage.getItem<CacheEntry<unknown>>(`cache:${key}`);
  return entry?.timestamp || null;
};

// =====================================================
// Pre-defined Cache Keys
// =====================================================

export const CACHE_KEYS = {
  DASHBOARD: 'dashboard',
  DASHBOARD_STATS: 'dashboard_stats',
  COMMISSIONS: 'commissions',
  COMMISSIONS_LIST: 'commissions_list',
  REFERRALS: 'referrals',
  REFERRAL_TREE: 'referral_tree',
  NETWORK: 'network',
  NETWORK_STATS: 'network_stats',
  PRODUCTS: 'products',
  PRODUCTS_CATEGORIES: 'products_categories',
  WALLET: 'wallet',
  WALLET_TRANSACTIONS: 'wallet_transactions',
  USER_PROFILE: 'user_profile',
  NOTIFICATIONS: 'notifications',
} as const;

// =====================================================
// Cache with API - Helper functions
// =====================================================

/**
 * ดึงข้อมูลจาก cache ก่อน ถ้าไม่มีค่อยเรียก API
 */
export const getCachedOrFetch = async <T>(
  key: string,
  fetchFn: () => Promise<T>,
  options?: {
    duration?: number;
    forceRefresh?: boolean;
    isOffline?: boolean;
  }
): Promise<T | null> => {
  const { duration = DEFAULT_CACHE_DURATION, forceRefresh = false, isOffline = false } =
    options || {};

  // ถ้าเป็น offline mode ให้ดึงจาก cache เท่านั้น
  if (isOffline) {
    return getCacheForOffline<T>(key);
  }

  // ถ้าไม่ force refresh ให้ลองดึงจาก cache ก่อน
  if (!forceRefresh) {
    const cached = await getCache<T>(key);
    if (cached !== null) {
      return cached;
    }
  }

  // เรียก API
  try {
    const data = await fetchFn();
    if (data !== null) {
      await setCache(key, data, duration);
    }
    return data;
  } catch (error) {
    console.error('Fetch error, trying cache:', error);
    // ถ้า API error ให้ลองดึงจาก cache (แม้หมดอายุ)
    return getCacheForOffline<T>(key);
  }
};

export { DEFAULT_CACHE_DURATION, LONG_CACHE_DURATION, OFFLINE_CACHE_DURATION };
