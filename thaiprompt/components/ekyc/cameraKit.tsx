/**
 * ตัวช่วยหน้ากล้องของ eKYC
 *
 * - useAppActive        แอปอยู่หน้าจอหรือไม่ (พับแอป = ปิดกล้อง/หยุดลูปถ่าย)
 * - useTimers           setTimeout ที่ล้างให้เองตอนออกจากหน้า (ไม่มี setState หลังถอดหน้าจอ)
 * - CameraPermissionPanel  ขอสิทธิ์กล้องพร้อมเหตุผลภาษาไทย · ปฏิเสธถาวร → ปุ่มเปิดการตั้งค่า
 *   กลับมาจากการตั้งค่า → ตรวจสิทธิ์ใหม่เอง
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { AppState, Linking, StyleSheet, View } from 'react-native';
import type { PermissionResponse } from 'expo-camera';
import { Text } from '@/components/ui/Text';
import { Button3D, Icon } from '@/components/ui';
import { DARK_THEME, radii, spacing, typography, withAlpha } from '@/theme';

const CAM = DARK_THEME.colors;

export const useAppActive = (): boolean => {
  const [active, setActive] = useState(AppState.currentState === 'active');
  useEffect(() => {
    const sub = AppState.addEventListener('change', (state) => setActive(state === 'active'));
    return () => sub.remove();
  }, []);
  return active;
};

/** setTimeout/sleep ที่ถูกล้างทั้งหมดตอนถอดหน้าจอ */
export const useTimers = () => {
  const timers = useRef(new Set<ReturnType<typeof setTimeout>>());
  useEffect(() => {
    const set = timers.current;
    return () => {
      set.forEach((t) => clearTimeout(t));
      set.clear();
    };
  }, []);
  const after = useCallback((ms: number, fn: () => void) => {
    const t = setTimeout(() => {
      timers.current.delete(t);
      fn();
    }, ms);
    timers.current.add(t);
    return t;
  }, []);
  const sleep = useCallback((ms: number) => new Promise<void>((resolve) => after(ms, resolve)), [after]);
  const clearAll = useCallback(() => {
    timers.current.forEach((t) => clearTimeout(t));
    timers.current.clear();
  }, []);
  return { after, sleep, clearAll };
};

export interface CameraPermissionPanelProps {
  permission: PermissionResponse | null;
  request: () => Promise<PermissionResponse>;
  refresh: () => Promise<PermissionResponse>;
  /** เหตุผลที่ต้องใช้กล้อง (ภาษาไทย) */
  reason: string;
}

export const CameraPermissionPanel: React.FC<CameraPermissionPanelProps> = ({ permission, request, refresh, reason }) => {
  const appActive = useAppActive();
  const askingRef = useRef(false);
  const refreshRef = useRef(refresh);
  refreshRef.current = refresh;

  // กลับมาจากหน้าตั้งค่าของเครื่อง → ตรวจสิทธิ์ใหม่ (ผูกกับ appActive อย่างเดียว กันวนตรวจซ้ำทุก render)
  useEffect(() => {
    if (appActive) refreshRef.current().catch(() => {});
  }, [appActive]);

  const blocked = !!permission && !permission.granted && permission.canAskAgain === false;

  const ask = async () => {
    if (askingRef.current) return;
    askingRef.current = true;
    try {
      if (blocked) {
        await Linking.openSettings();
      } else {
        await request();
      }
    } catch {
      // เปิดการตั้งค่าไม่ได้ — ผู้ใช้ไปเปิดเองได้
    } finally {
      askingRef.current = false;
    }
  };

  return (
    <View style={[styles.panel, { backgroundColor: withAlpha(CAM.card, 0.95), borderColor: CAM.border }]}>
      <View style={[styles.icon, { backgroundColor: withAlpha(CAM.gold, 0.14) }]}>
        <Icon name="camera" size={30} color={CAM.gold} />
      </View>
      <Text style={[typography.h2, styles.center, { color: CAM.textStrong }]}>
        {blocked ? 'เปิดสิทธิ์กล้องในการตั้งค่า' : 'ขอใช้กล้องก่อนนะ'}
      </Text>
      <Text style={[typography.bodySm, styles.center, { color: CAM.textMuted }]}>{reason}</Text>
      {blocked && (
        <Text style={[typography.caption, styles.center, { color: CAM.textMuted }]}>
          ไปที่ การตั้งค่า › แอป › Thai Prompt APP › สิทธิ์ › กล้อง แล้วเลือก "อนุญาต" จากนั้นกลับมาที่หน้านี้
        </Text>
      )}
      <Button3D
        title={blocked ? 'เปิดการตั้งค่า' : 'อนุญาตใช้กล้อง'}
        icon={blocked ? 'gear-six' : 'camera'}
        size="lg"
        fullWidth
        onPress={ask}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  panel: {
    marginHorizontal: spacing.screen,
    borderRadius: radii.xxl,
    borderWidth: 1,
    padding: spacing.xl,
    gap: spacing.md,
    alignItems: 'center',
  },
  icon: {
    width: 64,
    height: 64,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  center: {
    textAlign: 'center',
  },
});
