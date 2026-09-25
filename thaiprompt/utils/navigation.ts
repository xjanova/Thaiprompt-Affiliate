/**
 * Navigation Helpers - เปิด URL ใน WebView แทน external browser
 */

import { router } from 'expo-router';
import { Linking } from 'react-native';
import { isTrustedWebUrl } from './linking';

/**
 * เปิด URL ใน WebView ภายในแอพ
 *
 * @param url URL ที่ต้องการเปิด
 * @param title ชื่อหน้า (แสดงใน header)
 * @param icon ไอคอน emoji (แสดงใน header)
 */
export const openInWebView = (url: string, title?: string, icon?: string) => {
  // ใช้ encodeURIComponent เพื่อส่ง URL เป็น query parameter
  const encodedUrl = encodeURIComponent(url);
  const encodedTitle = title ? encodeURIComponent(title) : '';
  const encodedIcon = icon ? encodeURIComponent(icon) : '';

  router.push(
    `/webview?url=${encodedUrl}&title=${encodedTitle}&icon=${encodedIcon}` as any
  );
};

/**
 * เปิดหน้าลงทะเบียนใน WebView พร้อม referral code
 *
 * @param referralCode รหัสแนะนำ
 */
export const openRegisterPage = (referralCode: string) => {
  const registerUrl = `https://main.thaiprompt.online/register?ref=${referralCode}`;
  openInWebView(registerUrl, 'ลงทะเบียน', '📝');
};

/**
 * เปิด URL (ถ้าเป็น internal link ให้เปิดใน WebView, ถ้าเป็น external ให้ถามก่อน)
 *
 * @param url URL ที่ต้องการเปิด
 * @param title ชื่อหน้า (ถ้าเปิดใน WebView)
 * @param icon ไอคอน (ถ้าเปิดใน WebView)
 * @param forceWebView บังคับเปิดใน WebView (ไม่ถาม)
 */
export const openUrl = async (
  url: string,
  title?: string,
  icon?: string,
  forceWebView: boolean = false
) => {
  // URLs พิเศษที่เปิดด้วยแอประบบ (โทร / อีเมล / SMS / ตั้งค่า)
  const specialUrls = [
    'tel:',    // โทรศัพท์
    'mailto:', // อีเมล
    'sms:',    // SMS
    'app-settings:', // Settings
  ];

  const isSpecialUrl = specialUrls.some(prefix => url.startsWith(prefix));

  if (isSpecialUrl) {
    // เปิดใน external app
    const canOpen = await Linking.canOpenURL(url);
    if (canOpen) {
      await Linking.openURL(url);
    }
    return;
  }

  // PLAY-23: เปิดได้เฉพาะเว็บของเรา (https://*.thaiprompt.online) — forceWebView ไม่ข้ามกติกานี้
  if (isTrustedWebUrl(url)) {
    openInWebView(url, title, icon);
  }
  void forceWebView;
};
