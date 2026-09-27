/**
 * คลิปแนะนำแอป (น้องพร้อม 2 นาที) — ขึ้นครั้งเดียวหลังล็อกอิน
 *
 * - ค่าคลิปมาจาก GET /api/v1/settings → data.intro_video { enabled, version, page_url }
 *   (เปลี่ยนคลิป/เปิด-ปิด ได้จากหลังบ้านโดยไม่ต้องออกแอปเวอร์ชันใหม่ · เปลี่ยน version = ขึ้นใหม่อีกรอบ)
 * - จำว่าดู version ไหนไปแล้วใน AsyncStorage (ไม่ใช่ข้อมูลลับ)
 * - ถามเซิร์ฟเวอร์ไม่ได้ / ปิดไว้ = ไม่แสดง (ไม่ขวางการเข้าแอป)
 */

import AsyncStorage from '@react-native-async-storage/async-storage';
import { apiClient } from '@/services/api';

const SEEN_KEY = 'intro_video_seen_version';

export interface IntroVideoConfig {
  enabled: boolean;
  version: string;
  pageUrl: string;
}

// เปิดหน้าคลิปไปแล้วในรอบการใช้งานนี้ (กันเด้งซ้ำตอนแท็บ mount ใหม่)
let openedThisSession = false;

/**
 * ดึงค่าคลิปจากเซิร์ฟเวอร์ — ได้ null ถ้าปิดไว้หรือค่าไม่ครบ
 */
export const fetchIntroVideoConfig = async (): Promise<IntroVideoConfig | null> => {
  const res = await apiClient.get<{
    success: boolean;
    data?: { intro_video?: { enabled?: boolean; version?: string; page_url?: string } };
  }>('/settings');
  const raw = res.data?.data?.intro_video;
  if (!raw || raw.enabled === false || !raw.page_url || !/^https:\/\//.test(raw.page_url)) {
    return null;
  }
  return { enabled: true, version: String(raw.version || 'v1'), pageUrl: raw.page_url };
};

/**
 * ควรเปิดคลิปให้ผู้ใช้คนนี้ไหม (ยังไม่เคยดู version ปัจจุบัน) — คืนค่าคลิปถ้าควรเปิด
 */
export const introVideoToShow = async (): Promise<IntroVideoConfig | null> => {
  if (openedThisSession) return null;
  try {
    const config = await fetchIntroVideoConfig();
    if (!config) return null;
    const seen = await AsyncStorage.getItem(SEEN_KEY);
    if (seen === config.version) return null;
    openedThisSession = true;
    return config;
  } catch {
    return null;
  }
};

/**
 * จำว่าดู (หรือกดข้าม) version นี้แล้ว — ครั้งหน้าไม่เด้งอีก
 */
export const markIntroVideoSeen = async (version: string): Promise<void> => {
  if (!version) return;
  try {
    await AsyncStorage.setItem(SEEN_KEY, version);
  } catch {
    // บันทึกไม่ได้ = อาจเห็นอีกครั้งรอบหน้า ไม่กระทบการใช้งาน
  }
};
