/**
 * ป้าย/สรุปเวอร์ชันสำหรับแถวเมนู (ตั้งค่า · โปรไฟล์)
 *
 * - useAppUpdateSummary(): เวอร์ชันที่ติดตั้ง · มีอัปเดตไหม · ข้อความสั้น "v3.388.0 · ล่าสุดแล้ว" · open() เปิดหน้าอัปเดต
 * - AppUpdateBadge: ป้ายทอง "มีอัปเดต"
 * - PauseGlyph: ไอคอนหยุดชั่วคราว (ชุดไอคอนของแอปไม่มี) วาดด้วย svg
 */

import React, { useCallback } from 'react';
import { router } from 'expo-router';
import Svg, { Rect } from 'react-native-svg';
import { Pill, usePressGuard } from '@/components/ui';
import { APP_UPDATE_ROUTE, useAppUpdateStore } from '@/services/appUpdate';

export interface AppUpdateSummary {
  /** Android เท่านั้น */
  supported: boolean;
  installedVersion: string;
  hasUpdate: boolean;
  /** ตรวจแล้วและเป็นเวอร์ชันล่าสุด */
  upToDate: boolean;
  latestVersion: string | null;
  /** ข้อความสั้นใต้/ข้างแถวเมนู */
  label: string;
  /** เปิดหน้าอัปเดต (กันกดซ้ำ — แตะรัวไม่เปิดหน้าซ้อน) */
  open: () => void;
}

export const useAppUpdateSummary = (): AppUpdateSummary => {
  const supported = useAppUpdateStore((s) => s.supported);
  const installedVersion = useAppUpdateStore((s) => s.installedVersion);
  const hasUpdate = useAppUpdateStore((s) => !!s.info?.updateAvailable && !!s.info.latest);
  const checked = useAppUpdateStore((s) => s.info !== null);
  const latestVersion = useAppUpdateStore((s) => s.info?.latest?.version ?? null);
  const upToDate = supported && checked && !hasUpdate;
  const push = useCallback(() => {
    router.push(APP_UPDATE_ROUTE as never);
  }, []);
  const { run: open } = usePressGuard(push);

  const label = hasUpdate && latestVersion
    ? `v${installedVersion} · มีเวอร์ชัน ${latestVersion}`
    : upToDate
      ? `v${installedVersion} · ล่าสุดแล้ว`
      : `v${installedVersion}`;

  return { supported, installedVersion, hasUpdate, upToDate, latestVersion, label, open };
};

/** ป้ายทอง "มีอัปเดต" */
export const AppUpdateBadge: React.FC = () => <Pill label="มีอัปเดต" tone="gold" solid icon="sparkle" />;

/** ไอคอนหยุดชั่วคราว (สองแท่งมน) */
export const PauseGlyph: React.FC<{ size?: number; color: string }> = ({ size = 19, color }) => (
  <Svg width={size} height={size} viewBox="0 0 24 24">
    <Rect x={5.5} y={4} width={4.6} height={16} rx={1.6} fill={color} />
    <Rect x={13.9} y={4} width={4.6} height={16} rx={1.6} fill={color} />
  </Svg>
);
