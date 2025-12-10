/**
 * File Service - จัดการไฟล์ในแอพมือถือ
 *
 * ใช้ expo-file-system สำหรับจัดการไฟล์ทั้งหมด
 * รวมถึงการ save, read, delete และจัดการ directories
 */

import * as FileSystem from 'expo-file-system';
import * as MediaLibrary from 'expo-media-library';
import { Alert, Platform } from 'react-native';

// =====================================================
// Constants
// =====================================================

/**
 * Directory paths ที่ใช้บ่อย
 */
export const DIRECTORIES = {
  // Document directory - ที่เก็บไฟล์หลักของแอพ
  DOCUMENTS: FileSystem.documentDirectory,

  // Cache directory - ที่เก็บไฟล์ชั่วคราว
  CACHE: FileSystem.cacheDirectory,

  // Sub-directories
  KYC: `${FileSystem.documentDirectory}kyc/`,
  PROFILE: `${FileSystem.documentDirectory}profile/`,
  TEMP: `${FileSystem.documentDirectory}temp/`,
  DOWNLOADS: `${FileSystem.documentDirectory}downloads/`,
} as const;

// =====================================================
// Directory Management
// =====================================================

/**
 * สร้าง directory ถ้ายังไม่มี
 *
 * @param path - path ของ directory ที่ต้องการสร้าง
 * @returns Promise<boolean> - true ถ้าสร้างสำเร็จหรือมีอยู่แล้ว
 */
export const ensureDirectoryExists = async (path: string): Promise<boolean> => {
  try {
    const info = await FileSystem.getInfoAsync(path);
    if (!info.exists) {
      await FileSystem.makeDirectoryAsync(path, { intermediates: true });
    }
    return true;
  } catch (error) {
    console.error('Error creating directory:', error);
    return false;
  }
};

/**
 * ลบ directory และไฟล์ทั้งหมดภายใน
 *
 * @param path - path ของ directory
 */
export const deleteDirectory = async (path: string): Promise<boolean> => {
  try {
    const info = await FileSystem.getInfoAsync(path);
    if (info.exists) {
      await FileSystem.deleteAsync(path, { idempotent: true });
    }
    return true;
  } catch (error) {
    console.error('Error deleting directory:', error);
    return false;
  }
};

/**
 * ดึงรายการไฟล์ใน directory
 *
 * @param path - path ของ directory
 * @returns รายการชื่อไฟล์
 */
export const listFiles = async (path: string): Promise<string[]> => {
  try {
    const info = await FileSystem.getInfoAsync(path);
    if (!info.exists || !info.isDirectory) {
      return [];
    }
    return await FileSystem.readDirectoryAsync(path);
  } catch (error) {
    console.error('Error listing files:', error);
    return [];
  }
};

// =====================================================
// File Operations
// =====================================================

/**
 * ตรวจสอบว่าไฟล์มีอยู่หรือไม่
 *
 * @param uri - URI ของไฟล์
 * @returns Promise<boolean>
 */
export const fileExists = async (uri: string): Promise<boolean> => {
  try {
    const info = await FileSystem.getInfoAsync(uri);
    return info.exists;
  } catch (error) {
    console.error('Error checking file:', error);
    return false;
  }
};

/**
 * ดึงข้อมูลไฟล์
 *
 * @param uri - URI ของไฟล์
 * @returns ข้อมูลไฟล์หรือ null
 */
export const getFileInfo = async (
  uri: string
): Promise<FileSystem.FileInfo | null> => {
  try {
    const info = await FileSystem.getInfoAsync(uri, { size: true });
    return info.exists ? info : null;
  } catch (error) {
    console.error('Error getting file info:', error);
    return null;
  }
};

/**
 * คัดลอกไฟล์ไปยังตำแหน่งใหม่
 *
 * @param from - URI ต้นทาง
 * @param to - URI ปลายทาง
 * @returns Promise<boolean>
 */
export const copyFile = async (from: string, to: string): Promise<boolean> => {
  try {
    // ตรวจสอบว่า directory ปลายทางมีอยู่
    const toDir = to.substring(0, to.lastIndexOf('/') + 1);
    await ensureDirectoryExists(toDir);

    await FileSystem.copyAsync({ from, to });
    return true;
  } catch (error) {
    console.error('Error copying file:', error);
    return false;
  }
};

/**
 * ย้ายไฟล์ไปยังตำแหน่งใหม่
 *
 * @param from - URI ต้นทาง
 * @param to - URI ปลายทาง
 * @returns Promise<boolean>
 */
export const moveFile = async (from: string, to: string): Promise<boolean> => {
  try {
    // ตรวจสอบว่า directory ปลายทางมีอยู่
    const toDir = to.substring(0, to.lastIndexOf('/') + 1);
    await ensureDirectoryExists(toDir);

    await FileSystem.moveAsync({ from, to });
    return true;
  } catch (error) {
    console.error('Error moving file:', error);
    return false;
  }
};

/**
 * ลบไฟล์
 *
 * @param uri - URI ของไฟล์
 * @returns Promise<boolean>
 */
export const deleteFile = async (uri: string): Promise<boolean> => {
  try {
    await FileSystem.deleteAsync(uri, { idempotent: true });
    return true;
  } catch (error) {
    console.error('Error deleting file:', error);
    return false;
  }
};

/**
 * อ่านไฟล์เป็น string
 *
 * @param uri - URI ของไฟล์
 * @param encoding - encoding ของไฟล์ (default: utf8)
 * @returns เนื้อหาไฟล์หรือ null
 */
export const readFileAsString = async (
  uri: string,
  encoding: FileSystem.EncodingType = FileSystem.EncodingType.UTF8
): Promise<string | null> => {
  try {
    return await FileSystem.readAsStringAsync(uri, { encoding });
  } catch (error) {
    console.error('Error reading file:', error);
    return null;
  }
};

/**
 * อ่านไฟล์เป็น Base64
 *
 * @param uri - URI ของไฟล์
 * @returns Base64 string หรือ null
 */
export const readFileAsBase64 = async (uri: string): Promise<string | null> => {
  try {
    return await FileSystem.readAsStringAsync(uri, {
      encoding: FileSystem.EncodingType.Base64,
    });
  } catch (error) {
    console.error('Error reading file as Base64:', error);
    return null;
  }
};

/**
 * เขียนไฟล์จาก string
 *
 * @param uri - URI ของไฟล์
 * @param content - เนื้อหาที่ต้องการเขียน
 * @param encoding - encoding ของไฟล์ (default: utf8)
 * @returns Promise<boolean>
 */
export const writeFile = async (
  uri: string,
  content: string,
  encoding: FileSystem.EncodingType = FileSystem.EncodingType.UTF8
): Promise<boolean> => {
  try {
    // ตรวจสอบว่า directory มีอยู่
    const dir = uri.substring(0, uri.lastIndexOf('/') + 1);
    await ensureDirectoryExists(dir);

    await FileSystem.writeAsStringAsync(uri, content, { encoding });
    return true;
  } catch (error) {
    console.error('Error writing file:', error);
    return false;
  }
};

// =====================================================
// Image-specific Operations
// =====================================================

/**
 * Save รูปภาพไปยัง document directory
 * ใช้สำหรับ KYC และ Profile images
 *
 * @param sourceUri - URI ของรูปต้นทาง (จากกล้องหรือ gallery)
 * @param directory - directory ปลายทาง (เช่น DIRECTORIES.KYC)
 * @param filename - ชื่อไฟล์ (ไม่รวม extension)
 * @returns URI ใหม่หรือ null
 */
export const saveImage = async (
  sourceUri: string,
  directory: string,
  filename: string
): Promise<string | null> => {
  try {
    // สร้าง directory ถ้ายังไม่มี
    const created = await ensureDirectoryExists(directory);
    if (!created) {
      throw new Error('ไม่สามารถสร้าง directory ได้');
    }

    // กำหนด extension
    const extension = getFileExtension(sourceUri) || 'jpg';
    const newUri = `${directory}${filename}.${extension}`;

    // คัดลอกไฟล์
    await FileSystem.copyAsync({
      from: sourceUri,
      to: newUri,
    });

    // ตรวจสอบว่าไฟล์ถูก save สำเร็จ
    const exists = await fileExists(newUri);
    if (!exists) {
      throw new Error('ไม่สามารถบันทึกรูปได้');
    }

    console.log('Image saved successfully:', newUri);
    return newUri;
  } catch (error) {
    console.error('Error saving image:', error);
    return null;
  }
};

/**
 * Save รูปภาพไปยัง Media Library (แกลเลอรี่ของเครื่อง)
 *
 * @param uri - URI ของรูป
 * @param albumName - ชื่ออัลบั้ม (optional)
 * @returns Promise<boolean>
 */
export const saveToMediaLibrary = async (
  uri: string,
  albumName?: string
): Promise<boolean> => {
  try {
    // ขอ permission
    const { status } = await MediaLibrary.requestPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert(
        'ต้องการสิทธิ์',
        'กรุณาอนุญาตให้แอพบันทึกรูปภาพลงในแกลเลอรี่'
      );
      return false;
    }

    // สร้าง asset
    const asset = await MediaLibrary.createAssetAsync(uri);

    // ถ้ามีชื่ออัลบั้ม ให้สร้างหรือเพิ่มเข้าอัลบั้ม
    if (albumName) {
      const album = await MediaLibrary.getAlbumAsync(albumName);
      if (album) {
        await MediaLibrary.addAssetsToAlbumAsync([asset], album, false);
      } else {
        await MediaLibrary.createAlbumAsync(albumName, asset, false);
      }
    }

    return true;
  } catch (error) {
    console.error('Error saving to media library:', error);
    return false;
  }
};

// =====================================================
// Download Operations
// =====================================================

/**
 * Download ไฟล์จาก URL
 *
 * @param url - URL ต้นทาง
 * @param filename - ชื่อไฟล์
 * @param directory - directory ปลายทาง (default: DIRECTORIES.DOWNLOADS)
 * @returns URI ของไฟล์ที่ download หรือ null
 */
export const downloadFile = async (
  url: string,
  filename: string,
  directory: string = DIRECTORIES.DOWNLOADS
): Promise<string | null> => {
  try {
    // สร้าง directory
    await ensureDirectoryExists(directory);

    const localUri = `${directory}${filename}`;

    // Download
    const result = await FileSystem.downloadAsync(url, localUri);

    if (result.status === 200) {
      console.log('File downloaded:', localUri);
      return localUri;
    } else {
      throw new Error(`Download failed with status: ${result.status}`);
    }
  } catch (error) {
    console.error('Error downloading file:', error);
    return null;
  }
};

/**
 * Download ไฟล์พร้อม progress callback
 *
 * @param url - URL ต้นทาง
 * @param filename - ชื่อไฟล์
 * @param onProgress - callback รับค่า progress (0-1)
 * @param directory - directory ปลายทาง
 * @returns URI ของไฟล์ที่ download หรือ null
 */
export const downloadWithProgress = async (
  url: string,
  filename: string,
  onProgress: (progress: number) => void,
  directory: string = DIRECTORIES.DOWNLOADS
): Promise<string | null> => {
  try {
    // สร้าง directory
    await ensureDirectoryExists(directory);

    const localUri = `${directory}${filename}`;

    // สร้าง download resumable
    const downloadResumable = FileSystem.createDownloadResumable(
      url,
      localUri,
      {},
      (downloadProgress) => {
        const progress =
          downloadProgress.totalBytesWritten /
          downloadProgress.totalBytesExpectedToWrite;
        onProgress(progress);
      }
    );

    const result = await downloadResumable.downloadAsync();

    if (result?.uri) {
      console.log('File downloaded:', result.uri);
      return result.uri;
    } else {
      throw new Error('Download failed');
    }
  } catch (error) {
    console.error('Error downloading file:', error);
    return null;
  }
};

// =====================================================
// Utility Functions
// =====================================================

/**
 * ดึง extension จาก URI หรือ filename
 *
 * @param uri - URI หรือ filename
 * @returns extension (ไม่รวม .) หรือ null
 */
export const getFileExtension = (uri: string): string | null => {
  const match = uri.match(/\.([^.]+)$/);
  return match ? match[1].toLowerCase() : null;
};

/**
 * ดึงชื่อไฟล์จาก URI
 *
 * @param uri - URI ของไฟล์
 * @returns ชื่อไฟล์
 */
export const getFileName = (uri: string): string => {
  return uri.substring(uri.lastIndexOf('/') + 1);
};

/**
 * ดึง MIME type จาก extension
 *
 * @param extension - extension ของไฟล์
 * @returns MIME type
 */
export const getMimeType = (extension: string): string => {
  const mimeTypes: Record<string, string> = {
    jpg: 'image/jpeg',
    jpeg: 'image/jpeg',
    png: 'image/png',
    gif: 'image/gif',
    webp: 'image/webp',
    pdf: 'application/pdf',
    doc: 'application/msword',
    docx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    xls: 'application/vnd.ms-excel',
    xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    txt: 'text/plain',
    json: 'application/json',
    mp4: 'video/mp4',
    mp3: 'audio/mpeg',
  };

  return mimeTypes[extension.toLowerCase()] || 'application/octet-stream';
};

/**
 * แปลง file size เป็น human readable
 *
 * @param bytes - ขนาดไฟล์ในหน่วย bytes
 * @returns string ที่อ่านง่าย (เช่น "1.5 MB")
 */
export const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';

  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

/**
 * สร้าง unique filename
 *
 * @param prefix - prefix ของชื่อไฟล์
 * @param extension - extension ของไฟล์
 * @returns unique filename
 */
export const generateUniqueFilename = (
  prefix: string = 'file',
  extension: string = 'jpg'
): string => {
  const timestamp = Date.now();
  const random = Math.random().toString(36).substring(2, 8);
  return `${prefix}_${timestamp}_${random}.${extension}`;
};

/**
 * ล้างไฟล์เก่าใน directory
 * ลบไฟล์ที่เก่ากว่า maxAgeMs
 *
 * @param directory - path ของ directory
 * @param maxAgeMs - อายุสูงสุดในหน่วย milliseconds
 * @returns จำนวนไฟล์ที่ลบ
 */
export const cleanOldFiles = async (
  directory: string,
  maxAgeMs: number
): Promise<number> => {
  try {
    const files = await listFiles(directory);
    const now = Date.now();
    let deletedCount = 0;

    for (const file of files) {
      const filePath = `${directory}${file}`;
      const info = await getFileInfo(filePath);

      if (info && info.modificationTime) {
        const fileAge = now - info.modificationTime * 1000;
        if (fileAge > maxAgeMs) {
          await deleteFile(filePath);
          deletedCount++;
        }
      }
    }

    console.log(`Cleaned ${deletedCount} old files from ${directory}`);
    return deletedCount;
  } catch (error) {
    console.error('Error cleaning old files:', error);
    return 0;
  }
};

/**
 * ดึงขนาดทั้งหมดของ directory
 *
 * @param directory - path ของ directory
 * @returns ขนาดรวมในหน่วย bytes
 */
export const getDirectorySize = async (directory: string): Promise<number> => {
  try {
    const files = await listFiles(directory);
    let totalSize = 0;

    for (const file of files) {
      const filePath = `${directory}${file}`;
      const info = await getFileInfo(filePath);
      if (info && info.size) {
        totalSize += info.size;
      }
    }

    return totalSize;
  } catch (error) {
    console.error('Error getting directory size:', error);
    return 0;
  }
};

// =====================================================
// Initialization
// =====================================================

/**
 * สร้าง directories ที่จำเป็นทั้งหมด
 * ควรเรียกตอนแอพเริ่มต้น
 */
export const initializeDirectories = async (): Promise<void> => {
  const directories = [
    DIRECTORIES.KYC,
    DIRECTORIES.PROFILE,
    DIRECTORIES.TEMP,
    DIRECTORIES.DOWNLOADS,
  ];

  for (const dir of directories) {
    if (dir) {
      await ensureDirectoryExists(dir);
    }
  }

  console.log('File directories initialized');
};

// Export default object
export default {
  // Directories
  DIRECTORIES,

  // Directory Management
  ensureDirectoryExists,
  deleteDirectory,
  listFiles,

  // File Operations
  fileExists,
  getFileInfo,
  copyFile,
  moveFile,
  deleteFile,
  readFileAsString,
  readFileAsBase64,
  writeFile,

  // Image Operations
  saveImage,
  saveToMediaLibrary,

  // Download Operations
  downloadFile,
  downloadWithProgress,

  // Utilities
  getFileExtension,
  getFileName,
  getMimeType,
  formatFileSize,
  generateUniqueFilename,
  cleanOldFiles,
  getDirectorySize,

  // Initialization
  initializeDirectories,
};
