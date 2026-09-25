/**
 * WebsiteButton + openWebsite — เปิดเว็บไซต์ในเบราว์เซอร์แบบล็อกอินให้อัตโนมัติ
 *
 * ขั้นตอน: POST /web-session (token ใช้ครั้งเดียว อายุ 5 นาที) → WebBrowser.openBrowserAsync(url)
 * ถ้าขอ session ไม่ได้ (ยังไม่ล็อกอิน / path นอก whitelist / เน็ตหลุด) → เปิด URL เว็บธรรมดาแทน
 *
 * ⚠️ ห้ามใช้พาไปหน้าซื้อสินค้าดิจิทัล (เช่น แพ็กเกจดูดวง) — ผิดนโยบาย anti-steering ของ Google Play
 * ฟีเจอร์เครือข่าย/ค่าแนะนำหลายชั้นทั้งหมดอยู่บนเว็บ แอปมีแค่ปุ่มนี้ปุ่มเดียว
 *
 * @example
 * <WebsiteButton />                                   // "จัดการบนเว็บไซต์" → /user
 * <WebsiteButton path="/seller/store/settings" label="ตั้งค่าร้านบนเว็บ" />
 * await openWebsite('/taladsod');
 */

import React from 'react';
import { type StyleProp, type ViewStyle } from 'react-native';
import * as WebBrowser from 'expo-web-browser';
import { useAuthStore } from '@/stores/authStore';
import { createWebSession } from '@/services/api/accountApi';
import { buildWebsiteUrl, isTrustedWebUrl, isWebSessionPath } from '@/utils/linking';
import { palette } from '@/theme';
import { Button3D, type Button3DSize, type Button3DVariant } from './Button3D';

export type OpenWebsiteResult = 'session' | 'fallback' | 'failed';

/**
 * เปิดเว็บไซต์ (ล็อกอินให้ถ้าทำได้)
 *
 * @param path path บนเว็บ เช่น '/user', '/taladsod', '/seller/orders'
 * @param queryParams ค่าที่ server เก็บไว้ส่งต่อให้หน้าเว็บ (ไม่ต่อท้าย URL)
 * @returns session = เปิดแบบล็อกอินแล้ว · fallback = เปิดเว็บธรรมดา · failed = เปิดไม่ได้เลย
 */
export const openWebsite = async (
  path: string = '/user',
  queryParams?: Record<string, string | number | boolean>
): Promise<OpenWebsiteResult> => {
  const cleanPath = path.startsWith('/') ? path : `/${path}`;
  let url: string | null = null;
  let mode: OpenWebsiteResult = 'fallback';

  const isAuthenticated = useAuthStore.getState().isAuthenticated;
  if (isAuthenticated && isWebSessionPath(cleanPath)) {
    const session = await createWebSession(cleanPath, queryParams);
    if (session.success && isTrustedWebUrl(session.data?.url)) {
      url = session.data.url;
      mode = 'session';
    }
  }

  if (!url) {
    url = buildWebsiteUrl(cleanPath);
    mode = 'fallback';
  }

  try {
    await WebBrowser.openBrowserAsync(url, {
      toolbarColor: palette.clayLight,
      controlsColor: palette.gold600,
      showTitle: true,
      enableBarCollapsing: true,
    });
    return mode;
  } catch {
    return 'failed';
  }
};

export interface WebsiteButtonProps {
  /** path บนเว็บ (ค่าเริ่มต้น /user) */
  path?: string;
  label?: string;
  queryParams?: Record<string, string | number | boolean>;
  variant?: Button3DVariant;
  size?: Button3DSize;
  icon?: React.ReactNode;
  fullWidth?: boolean;
  style?: StyleProp<ViewStyle>;
  /** แจ้งผลหลังเปิด (เช่น แสดง toast ถ้า failed) */
  onResult?: (result: OpenWebsiteResult) => void;
  accessibilityHint?: string;
}

export const WebsiteButton: React.FC<WebsiteButtonProps> = ({
  path = '/user',
  label = 'จัดการบนเว็บไซต์',
  queryParams,
  variant = 'secondary',
  size = 'md',
  icon = 'globe',
  fullWidth = false,
  style,
  onResult,
  accessibilityHint = 'เปิดเว็บไซต์ในเบราว์เซอร์',
}) => (
  <Button3D
    title={label}
    icon={icon}
    variant={variant}
    size={size}
    fullWidth={fullWidth}
    style={style}
    accessibilityHint={accessibilityHint}
    loadingText="กำลังเปิดเว็บไซต์..."
    onPress={async () => {
      const result = await openWebsite(path, queryParams);
      onResult?.(result);
    }}
  />
);

export default WebsiteButton;
