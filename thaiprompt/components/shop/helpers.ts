/**
 * ตัวช่วยของหน้าช้อป/ร้านค้า — วันที่ไทย, เปิดลิงก์อย่างปลอดภัย, โทรออก, ป้ายสถานะ
 */

import { Alert, Linking } from 'react-native';
import * as WebBrowser from 'expo-web-browser';
import { palette, type Tone } from '@/theme';

/** วันที่/เวลาแบบไทยสั้นๆ เช่น "25 ก.ย. 69 14:05" (ค่าไม่ถูกต้อง = '') */
export const formatThaiDateTime = (iso: string | null | undefined, withTime: boolean = true): string => {
  if (!iso) return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  try {
    return d.toLocaleDateString('th-TH', {
      day: 'numeric',
      month: 'short',
      year: '2-digit',
      ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    });
  } catch {
    return d.toISOString().slice(0, 16).replace('T', ' ');
  }
};

/** ลิงก์ https เท่านั้น (กัน javascript:/http:/intent:) */
export const isHttpsUrl = (url: unknown): url is string =>
  typeof url === 'string' && url.length <= 2048 && /^https:\/\/[^\s]+$/i.test(url);

/**
 * เปิดลิงก์ https ในเบราว์เซอร์ในแอป (ติดตามพัสดุ / ติดตามไรเดอร์)
 * แถบบนเป็นน้ำเงินกรมท่า + ปุ่มทองอ่อน ให้เข้ากับหัวหน้าจอรอยัลของแอป
 * ลิงก์ไม่ปลอดภัยหรือเปิดไม่ได้ → แจ้งข้อความไทย ไม่โยน error
 */
export const openHttpsLink = async (url: unknown, title: string = 'เปิดลิงก์'): Promise<void> => {
  if (!isHttpsUrl(url)) {
    Alert.alert(title, 'ลิงก์นี้ยังเปิดไม่ได้ ลองใหม่ภายหลังนะ');
    return;
  }
  try {
    await WebBrowser.openBrowserAsync(url, {
      toolbarColor: palette.navy800,
      controlsColor: palette.gold300,
      showTitle: true,
    });
  } catch {
    Alert.alert(title, 'เปิดลิงก์ไม่สำเร็จ ลองใหม่อีกครั้งนะ');
  }
};

/** โทรออก (เฉพาะตัวเลข/+/-) */
export const callPhone = async (phone: string | null | undefined): Promise<void> => {
  const clean = String(phone || '').replace(/[^0-9+]/g, '');
  if (clean.length < 9) {
    Alert.alert('โทรออก', 'ไม่มีเบอร์โทรที่ใช้ได้');
    return;
  }
  try {
    await Linking.openURL(`tel:${clean}`);
  } catch {
    Alert.alert('โทรออก', 'เปิดแอปโทรศัพท์ไม่สำเร็จ');
  }
};

/** โทนสีของสถานะออเดอร์ร้านค้า (enum จริงของ orders.status) */
export const ORDER_STATUS_TONE: Record<string, Tone> = {
  pending: 'warning',
  paid: 'info',
  processing: 'gold',
  shipped: 'info',
  delivered: 'success',
  completed: 'success',
  cancelled: 'danger',
  refunded: 'neutral',
};

/** ป้ายสถานะไทย (ใช้เมื่อ server ไม่ส่ง status_label) */
export const ORDER_STATUS_LABEL: Record<string, string> = {
  pending: 'รอดำเนินการ',
  paid: 'ชำระเงินแล้ว',
  processing: 'กำลังเตรียมสินค้า',
  shipped: 'กำลังจัดส่ง',
  delivered: 'ส่งถึงแล้ว',
  completed: 'สำเร็จ',
  cancelled: 'ยกเลิกแล้ว',
  refunded: 'คืนเงินแล้ว',
};

/** สถานะงานไรเดอร์ที่ยังวิ่งอยู่ (ควร poll ถี่ขึ้น) */
export const ACTIVE_RIDER_STATUSES = ['pending', 'accepted', 'picking_up', 'picked_up', 'delivering'];

/**
 * แปลงรายละเอียดสินค้า (อาจเป็น HTML จากหลังบ้าน) เป็นข้อความธรรมดา
 * - <br>, </p>, </li> → ขึ้นบรรทัดใหม่ · ตัดแท็กที่เหลือ · ถอด entity ที่พบบ่อย
 */
export const stripHtml = (html: string | null | undefined): string => {
  if (!html) return '';
  return String(html)
    .replace(/<\s*(script|style)[^>]*>[\s\S]*?<\s*\/\s*\1\s*>/gi, '')
    .replace(/<\s*br\s*\/?>/gi, '\n')
    .replace(/<\s*\/\s*(p|div|li|h[1-6]|tr)\s*>/gi, '\n')
    .replace(/<\s*li[^>]*>/gi, '• ')
    .replace(/<[^>]+>/g, '')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/&quot;/gi, '"')
    .replace(/&#39;/gi, "'")
    .replace(/[ \t]+\n/g, '\n')
    .replace(/\n{3,}/g, '\n\n')
    .trim();
};

/** ค่าตัวเลขที่ปลอดภัย */
export const toNumber = (value: unknown, fallback: number = 0): number => {
  const n = typeof value === 'number' ? value : Number(value);
  return Number.isFinite(n) ? n : fallback;
};
