/**
 * Banner Cache Service - จัดการ Cache สำหรับ Banner โฆษณา
 *
 * Features:
 * - โหลดรูปภาพ banner มาเก็บไว้ใน device
 * - ใช้รูปจาก cache แทนการโหลดใหม่ทุกครั้ง
 * - ตรวจสอบ banner ใหม่เฉพาะครั้งแรกของวัน
 * - ถ้ามี banner ใหม่จึงโหลดใหม่
 */

import * as FileSystem from 'expo-file-system';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { getBanners, type Banner } from './api';

// =====================================================
// Constants
// =====================================================

// Directory สำหรับเก็บรูป banner
const BANNER_DIRECTORY = `${FileSystem.cacheDirectory}banners/`;

// Keys สำหรับ AsyncStorage
const STORAGE_KEYS = {
  BANNER_DATA: 'banner_cache_data',
  LAST_CHECK_DATE: 'banner_last_check_date',
  BANNER_HASH: 'banner_cache_hash',
} as const;

// =====================================================
// Types
// =====================================================

interface CachedBanner extends Banner {
  // URI ของรูปที่ cache ไว้ใน device
  cachedImageUri?: string;
}

interface BannerCacheData {
  banners: CachedBanner[];
  position: string;
  timestamp: number;
}

// =====================================================
// Helper Functions
// =====================================================

/**
 * สร้าง hash จาก banner array เพื่อเช็คว่ามีการเปลี่ยนแปลงหรือไม่
 */
const generateBannerHash = (banners: Banner[]): string => {
  if (!banners || banners.length === 0) return '';

  // สร้าง hash จาก id + image URLs ทั้งหมด
  const hashSource = banners
    .map((b) => `${b.id}:${b.image || ''}`)
    .join('|');

  // Simple hash function
  let hash = 0;
  for (let i = 0; i < hashSource.length; i++) {
    const char = hashSource.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash; // Convert to 32bit integer
  }
  return hash.toString(16);
};

/**
 * ดึงวันที่ปัจจุบัน (เฉพาะวัน ไม่รวมเวลา)
 */
const getTodayDateString = (): string => {
  const now = new Date();
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
};

/**
 * สร้าง directory สำหรับ banner ถ้ายังไม่มี
 */
const ensureBannerDirectory = async (): Promise<boolean> => {
  try {
    const info = await FileSystem.getInfoAsync(BANNER_DIRECTORY);
    if (!info.exists) {
      await FileSystem.makeDirectoryAsync(BANNER_DIRECTORY, { intermediates: true });
    }
    return true;
  } catch (error) {
    console.error('Error creating banner directory:', error);
    return false;
  }
};

/**
 * ดึงชื่อไฟล์จาก URL
 */
const getFilenameFromUrl = (url: string, bannerId: number): string => {
  try {
    const urlObj = new URL(url);
    const pathname = urlObj.pathname;
    const extension = pathname.split('.').pop() || 'jpg';
    return `banner_${bannerId}.${extension}`;
  } catch {
    return `banner_${bannerId}.jpg`;
  }
};

/**
 * Download รูป banner และเก็บไว้ใน cache
 */
const downloadBannerImage = async (
  imageUrl: string,
  bannerId: number
): Promise<string | null> => {
  try {
    if (!imageUrl) return null;

    await ensureBannerDirectory();

    const filename = getFilenameFromUrl(imageUrl, bannerId);
    const localUri = `${BANNER_DIRECTORY}${filename}`;

    // ตรวจสอบว่ารูปมีอยู่แล้วหรือไม่
    const existingFile = await FileSystem.getInfoAsync(localUri);
    if (existingFile.exists) {
      console.log(`Banner image already cached: ${filename}`);
      return localUri;
    }

    // Download รูปใหม่
    console.log(`Downloading banner image: ${imageUrl}`);
    const result = await FileSystem.downloadAsync(imageUrl, localUri);

    if (result.status === 200) {
      console.log(`Banner image downloaded: ${filename}`);
      return localUri;
    } else {
      console.error(`Failed to download banner: ${result.status}`);
      return null;
    }
  } catch (error) {
    console.error('Error downloading banner image:', error);
    return null;
  }
};

// =====================================================
// Main Functions
// =====================================================

/**
 * ตรวจสอบว่าต้องโหลด banner ใหม่หรือไม่
 * จะ return true ถ้า:
 * - เป็นครั้งแรกของวัน
 * - ยังไม่เคย cache
 */
export const shouldRefreshBanners = async (): Promise<boolean> => {
  try {
    const lastCheckDate = await AsyncStorage.getItem(STORAGE_KEYS.LAST_CHECK_DATE);
    const today = getTodayDateString();

    // ถ้าเป็นวันใหม่หรือยังไม่เคยตรวจสอบ
    if (lastCheckDate !== today) {
      console.log('New day - should refresh banners');
      return true;
    }

    // ตรวจสอบว่ามี cache อยู่หรือไม่
    const cachedData = await AsyncStorage.getItem(STORAGE_KEYS.BANNER_DATA);
    if (!cachedData) {
      console.log('No cached banners - should refresh');
      return true;
    }

    return false;
  } catch (error) {
    console.error('Error checking banner refresh:', error);
    return true;
  }
};

/**
 * ดึง banners จาก cache
 */
export const getCachedBanners = async (
  position: 'top' | 'middle' | 'bottom' = 'top'
): Promise<CachedBanner[] | null> => {
  try {
    const cachedDataStr = await AsyncStorage.getItem(STORAGE_KEYS.BANNER_DATA);
    if (!cachedDataStr) return null;

    const allCachedData: Record<string, BannerCacheData> = JSON.parse(cachedDataStr);
    const positionData = allCachedData[position];

    if (!positionData || !positionData.banners) return null;

    // ตรวจสอบว่ารูปที่ cache ยังมีอยู่หรือไม่
    const validBanners: CachedBanner[] = [];
    for (const banner of positionData.banners) {
      if (banner.cachedImageUri) {
        const fileInfo = await FileSystem.getInfoAsync(banner.cachedImageUri);
        if (fileInfo.exists) {
          validBanners.push(banner);
        } else {
          // ถ้ารูปหายไป ใช้ URL เดิมแทน
          validBanners.push({ ...banner, cachedImageUri: undefined });
        }
      } else {
        validBanners.push(banner);
      }
    }

    return validBanners;
  } catch (error) {
    console.error('Error getting cached banners:', error);
    return null;
  }
};

/**
 * โหลด banners จาก API และ cache รูปภาพ
 */
export const loadAndCacheBanners = async (
  position: 'top' | 'middle' | 'bottom' = 'top'
): Promise<CachedBanner[]> => {
  try {
    console.log(`Loading banners from API for position: ${position}`);

    // ดึงข้อมูลจาก API
    const result = await getBanners(position);

    if (!result?.success || !Array.isArray(result.data)) {
      console.log('No banners from API');
      return [];
    }

    const banners = result.data.filter((b) => b && b.id != null);

    if (banners.length === 0) {
      return [];
    }

    // ตรวจสอบว่า banners มีการเปลี่ยนแปลงหรือไม่
    const newHash = generateBannerHash(banners);
    const savedHashStr = await AsyncStorage.getItem(STORAGE_KEYS.BANNER_HASH);
    const savedHashes: Record<string, string> = savedHashStr ? JSON.parse(savedHashStr) : {};
    const oldHash = savedHashes[position];

    // ถ้า hash เหมือนเดิม ใช้ cache เดิม
    if (newHash === oldHash) {
      console.log('Banners unchanged, using cache');
      const cached = await getCachedBanners(position);
      if (cached && cached.length > 0) {
        // อัพเดทวันที่ตรวจสอบ
        await AsyncStorage.setItem(STORAGE_KEYS.LAST_CHECK_DATE, getTodayDateString());
        return cached;
      }
    }

    console.log('Banners changed, downloading new images');

    // Download รูปภาพทั้งหมด
    const cachedBanners: CachedBanner[] = [];
    for (const banner of banners) {
      const cachedImageUri = banner.image
        ? await downloadBannerImage(banner.image, banner.id)
        : null;

      cachedBanners.push({
        ...banner,
        cachedImageUri: cachedImageUri || undefined,
      });
    }

    // บันทึก cache
    const cachedDataStr = await AsyncStorage.getItem(STORAGE_KEYS.BANNER_DATA);
    const allCachedData: Record<string, BannerCacheData> = cachedDataStr
      ? JSON.parse(cachedDataStr)
      : {};

    allCachedData[position] = {
      banners: cachedBanners,
      position,
      timestamp: Date.now(),
    };

    await AsyncStorage.setItem(STORAGE_KEYS.BANNER_DATA, JSON.stringify(allCachedData));

    // บันทึก hash
    savedHashes[position] = newHash;
    await AsyncStorage.setItem(STORAGE_KEYS.BANNER_HASH, JSON.stringify(savedHashes));

    // บันทึกวันที่ตรวจสอบ
    await AsyncStorage.setItem(STORAGE_KEYS.LAST_CHECK_DATE, getTodayDateString());

    console.log(`Cached ${cachedBanners.length} banners for position: ${position}`);

    return cachedBanners;
  } catch (error) {
    console.error('Error loading and caching banners:', error);
    return [];
  }
};

/**
 * ดึง banners โดยใช้ cache หรือโหลดใหม่ตามความเหมาะสม
 * - ถ้าเป็นครั้งแรกของวัน: ตรวจสอบกับ server
 * - ถ้าไม่ใช่: ใช้ cache
 */
export const getBannersWithCache = async (
  position: 'top' | 'middle' | 'bottom' = 'top',
  forceRefresh: boolean = false
): Promise<CachedBanner[]> => {
  try {
    const shouldRefresh = forceRefresh || await shouldRefreshBanners();

    if (shouldRefresh) {
      // ตรวจสอบและโหลดใหม่ถ้าจำเป็น
      const banners = await loadAndCacheBanners(position);
      return banners;
    } else {
      // ใช้ cache
      const cached = await getCachedBanners(position);
      if (cached && cached.length > 0) {
        console.log(`Using cached banners for position: ${position}`);
        return cached;
      }

      // ถ้าไม่มี cache ก็โหลดใหม่
      return await loadAndCacheBanners(position);
    }
  } catch (error) {
    console.error('Error getting banners with cache:', error);
    return [];
  }
};

/**
 * ล้าง cache ทั้งหมด
 */
export const clearBannerCache = async (): Promise<void> => {
  try {
    // ลบไฟล์รูปทั้งหมด
    const dirInfo = await FileSystem.getInfoAsync(BANNER_DIRECTORY);
    if (dirInfo.exists) {
      await FileSystem.deleteAsync(BANNER_DIRECTORY, { idempotent: true });
    }

    // ลบ data ใน AsyncStorage
    await AsyncStorage.removeItem(STORAGE_KEYS.BANNER_DATA);
    await AsyncStorage.removeItem(STORAGE_KEYS.LAST_CHECK_DATE);
    await AsyncStorage.removeItem(STORAGE_KEYS.BANNER_HASH);

    console.log('Banner cache cleared');
  } catch (error) {
    console.error('Error clearing banner cache:', error);
  }
};

/**
 * ดึงขนาด cache ทั้งหมด
 */
export const getBannerCacheSize = async (): Promise<number> => {
  try {
    const dirInfo = await FileSystem.getInfoAsync(BANNER_DIRECTORY);
    if (!dirInfo.exists) return 0;

    const files = await FileSystem.readDirectoryAsync(BANNER_DIRECTORY);
    let totalSize = 0;

    for (const file of files) {
      const fileInfo = await FileSystem.getInfoAsync(`${BANNER_DIRECTORY}${file}`, { size: true });
      if (fileInfo.exists && fileInfo.size) {
        totalSize += fileInfo.size;
      }
    }

    return totalSize;
  } catch (error) {
    console.error('Error getting banner cache size:', error);
    return 0;
  }
};

// Export types
export type { CachedBanner, BannerCacheData };
