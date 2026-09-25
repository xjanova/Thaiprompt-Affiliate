/**
 * useRiderPermissionFlow — ขอสิทธิ์ตำแหน่ง/ความยินยอมของไรเดอร์แบบ "prominent disclosure" (PLAY-13)
 *
 * ทุกครั้งแสดง ConsentSheet อธิบายเหตุผลก่อน แล้วค่อยเรียกกล่องขอสิทธิ์ของระบบ
 *   1. ensureForeground()  — ตำแหน่ง "ขณะใช้แอป" (จำเป็นสำหรับออนไลน์รับงาน) → แจ้ง server gps=true
 *   2. offerBackground()   — ตำแหน่ง "ตลอดเวลา" (ไม่บังคับ) ใช้เฉพาะช่วงมีงาน → ข้ามได้
 *   3. requestConsent()    — ยินยอมให้ลูกค้าเห็นตำแหน่งระหว่างส่ง (ต้องทำครั้งเดียวก่อนรับงานแรก)
 *
 * ทุกฟังก์ชันคืน Promise<boolean> — true = ได้สิทธิ์/ยินยอมแล้ว
 * เอา `element` ไปวางในหน้าจอหนึ่งครั้ง
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, Linking } from 'react-native';
import * as Location from 'expo-location';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { ConsentSheet, resultHaptic } from '@/components/ui';
import { grantLocationConsent, updateRiderPermissions } from '@/services/api/riderApi';
import { getLocationPermissionState, type LocationPermissionState } from '@/services/location';

type SheetKind = 'foreground' | 'background' | 'consent';

const BG_PROMPTED_KEY = '@thaiprompt/rider_bg_prompted_v1';

/** รอให้ Modal ตัวก่อนปิดสนิทก่อนเปิดตัวถัดไป (iOS) */
const MODAL_SETTLE_MS = 450;

/** แจ้ง server ว่าได้สิทธิ์ตำแหน่งแล้ว ครั้งเดียวต่อการเปิดแอป */
let serverGpsSynced = false;

const wait = (ms: number) => new Promise<void>((resolve) => setTimeout(resolve, ms));

const openSettingsAlert = (title: string, message: string) => {
  Alert.alert(title, message, [
    { text: 'ไว้ก่อน', style: 'cancel' },
    { text: 'เปิดการตั้งค่า', onPress: () => Linking.openSettings().catch(() => {}) },
  ]);
};

export interface RiderPermissionFlow {
  /** วางไว้ใน JSX ของหน้าจอ */
  element: React.ReactNode;
  ensureForeground: () => Promise<boolean>;
  offerBackground: (options?: { force?: boolean }) => Promise<boolean>;
  requestConsent: () => Promise<boolean>;
  /** สิทธิ์ล่าสุดที่อ่านจากเครื่อง */
  permissions: LocationPermissionState | null;
  refreshPermissions: () => Promise<LocationPermissionState>;
}

export const useRiderPermissionFlow = (options: { onServerUpdated?: () => void } = {}): RiderPermissionFlow => {
  const [sheet, setSheet] = useState<SheetKind | null>(null);
  const [permissions, setPermissions] = useState<LocationPermissionState | null>(null);
  const resolverRef = useRef<((value: boolean) => void) | null>(null);
  const mountedRef = useRef(true);
  const onServerUpdatedRef = useRef(options.onServerUpdated);
  onServerUpdatedRef.current = options.onServerUpdated;

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      resolverRef.current?.(false);
      resolverRef.current = null;
    };
  }, []);

  const refreshPermissions = useCallback(async () => {
    const state = await getLocationPermissionState();
    if (mountedRef.current) setPermissions(state);
    return state;
  }, []);

  /** เวลาที่ sheet ล่าสุดปิด — iOS เปิด Modal ใหม่ระหว่างอันเก่ากำลังปิดไม่ได้ ต้องรอให้ปิดเสร็จ */
  const lastClosedAtRef = useRef(0);

  /** เปิด sheet แล้วรอผล */
  const openSheet = useCallback((kind: SheetKind): Promise<boolean> => {
    resolverRef.current?.(false);
    return new Promise<boolean>((resolve) => {
      resolverRef.current = resolve;
      const waitMs = Math.max(0, MODAL_SETTLE_MS - (Date.now() - lastClosedAtRef.current));
      setTimeout(() => {
        if (mountedRef.current && resolverRef.current === resolve) setSheet(kind);
        else if (!mountedRef.current) resolve(false);
      }, waitMs);
    });
  }, []);

  const finish = useCallback((value: boolean) => {
    const resolve = resolverRef.current;
    resolverRef.current = null;
    lastClosedAtRef.current = Date.now();
    if (mountedRef.current) setSheet(null);
    resolve?.(value);
  }, []);

  const syncServerGps = useCallback(async () => {
    if (serverGpsSynced) return;
    const result = await updateRiderPermissions({ gps: true });
    if (result.success) {
      serverGpsSynced = true;
      onServerUpdatedRef.current?.();
    }
  }, []);

  // ---------- 1) ตำแหน่งขณะใช้แอป ----------
  const ensureForeground = useCallback(async (): Promise<boolean> => {
    const state = await refreshPermissions();
    if (state.foreground) {
      syncServerGps().catch(() => {});
      return true;
    }
    if (!state.canAskForeground) {
      openSettingsAlert(
        'เปิดสิทธิ์ตำแหน่งก่อนนะ',
        'ไปที่การตั้งค่าเครื่อง > ตำแหน่ง แล้วเลือก "ขณะใช้แอป" เพื่อรับงานใกล้คุณ'
      );
      return false;
    }
    return openSheet('foreground');
  }, [openSheet, refreshPermissions, syncServerGps]);

  const acceptForeground = useCallback(async () => {
    if (mountedRef.current) setSheet(null);
    await wait(250);
    try {
      const response = await Location.requestForegroundPermissionsAsync();
      const granted = response.status === 'granted';
      await refreshPermissions();
      if (granted) {
        serverGpsSynced = false;
        await syncServerGps();
        resultHaptic('success');
      } else if (response.canAskAgain === false) {
        openSettingsAlert('ยังไม่ได้สิทธิ์ตำแหน่ง', 'เปิดสิทธิ์ตำแหน่งในการตั้งค่าเครื่องได้ทุกเมื่อ แล้วกลับมาเริ่มรับงานนะ');
      }
      finish(granted);
    } catch {
      finish(false);
    }
  }, [finish, refreshPermissions, syncServerGps]);

  // ---------- 2) ตำแหน่งตลอดเวลา (ไม่บังคับ) ----------
  const offerBackground = useCallback(
    async (opts: { force?: boolean } = {}): Promise<boolean> => {
      const state = await refreshPermissions();
      if (!state.foreground) return false;
      if (state.background) return true;

      if (!opts.force) {
        try {
          if (await AsyncStorage.getItem(BG_PROMPTED_KEY)) return false;
        } catch {
          // อ่านไม่ได้ก็ถามได้
        }
      }
      AsyncStorage.setItem(BG_PROMPTED_KEY, '1').catch(() => {});

      if (!state.canAskBackground && opts.force) {
        openSettingsAlert(
          'เปิดในตั้งค่าเครื่อง',
          'ไปที่การตั้งค่าเครื่อง > ตำแหน่ง แล้วเลือก "อนุญาตตลอดเวลา" (ใช้เฉพาะช่วงที่มีงานส่ง)'
        );
        return false;
      }
      return openSheet('background');
    },
    [openSheet, refreshPermissions]
  );

  const acceptBackground = useCallback(async () => {
    if (mountedRef.current) setSheet(null);
    await wait(250);
    try {
      const response = await Location.requestBackgroundPermissionsAsync();
      const granted = response.status === 'granted';
      await refreshPermissions();
      if (granted) {
        updateRiderPermissions({ gps: true, gps_background: true }).catch(() => {});
        resultHaptic('success');
      } else if (response.canAskAgain === false) {
        openSettingsAlert(
          'ยังไม่ได้เปิด "อนุญาตตลอดเวลา"',
          'ไม่เป็นไร รับงานได้ตามปกติ แต่ตำแหน่งจะอัปเดตเฉพาะตอนเปิดแอปไว้ เปิดภายหลังได้ในตั้งค่าเครื่อง'
        );
      }
      finish(granted);
    } catch {
      finish(false);
    }
  }, [finish, refreshPermissions]);

  // ---------- 3) ยินยอมแชร์ตำแหน่งให้ลูกค้า ----------
  const requestConsent = useCallback(() => openSheet('consent'), [openSheet]);

  const acceptConsent = useCallback(async () => {
    const result = await grantLocationConsent();
    if (!mountedRef.current) return;
    if (result.success) {
      resultHaptic('success');
      onServerUpdatedRef.current?.();
      finish(true);
    } else {
      resultHaptic('error');
      finish(false);
      const message = result.message;
      setTimeout(() => Alert.alert('บันทึกไม่สำเร็จ', message), MODAL_SETTLE_MS);
    }
  }, [finish]);

  const element = (
    <>
      <ConsentSheet
        visible={sheet === 'foreground'}
        icon="📍"
        title="ขอใช้ตำแหน่งของคุณ"
        description="เพื่อหางานส่งที่อยู่ใกล้คุณ และนำทางไปรับ-ส่งของ"
        reasons={[
          { icon: '🛵', text: 'ใช้ตำแหน่งเฉพาะตอนคุณออนไลน์รับงานหรือกำลังส่งงาน' },
          { icon: '🗺️', text: 'ใช้จัดลำดับงานที่ใกล้ที่สุดและคำนวณระยะทาง' },
          { icon: '⏸️', text: 'กดหยุดรับงานเมื่อไหร่ แอปก็หยุดใช้ตำแหน่งทันที' },
        ]}
        acceptLabel="อนุญาตตำแหน่ง"
        declineLabel="ไว้ทีหลัง"
        footnote='ในกล่องถัดไป เลือก "ขณะใช้แอป" ได้เลย'
        onAccept={acceptForeground}
        onDecline={() => finish(false)}
      />

      <ConsentSheet
        visible={sheet === 'background'}
        icon="🛰️"
        title="ติดตามต่อแม้ปิดหน้าจอ"
        description="ThaiPrompt เก็บข้อมูลตำแหน่งของคุณเพื่อแสดงตำแหน่งไรเดอร์ให้ลูกค้าและร้านค้าติดตามการจัดส่ง แม้ในขณะที่ปิดแอปหรือไม่ได้ใช้งานแอป"
        reasons={[
          { icon: '📦', text: 'เก็บตำแหน่งเฉพาะช่วงที่คุณมีงานส่งอยู่เท่านั้น' },
          { icon: '🔔', text: 'จะมีแจ้งเตือนค้างบนเครื่องตลอดเวลาที่กำลังติดตาม' },
          { icon: '✋', text: 'หยุดทันทีเมื่อส่งสำเร็จ ส่งไม่สำเร็จ คืนงาน หรืองานถูกยกเลิก' },
          { icon: '👌', text: 'ถ้าไม่อนุญาต ยังรับงานได้ แต่ตำแหน่งจะอัปเดตเฉพาะตอนเปิดแอปไว้' },
        ]}
        acceptLabel="ยอมรับและตั้งค่า"
        declineLabel="ข้ามไปก่อน"
        footnote='ในหน้าตั้งค่า เลือก "อนุญาตตลอดเวลา"'
        onAccept={acceptBackground}
        onDecline={() => finish(false)}
      />

      <ConsentSheet
        visible={sheet === 'consent'}
        icon="🤝"
        title="แชร์ตำแหน่งให้ลูกค้าระหว่างส่ง"
        description="ลูกค้าจะอุ่นใจเมื่อเห็นว่าของใกล้ถึงแล้ว"
        reasons={[
          { icon: '👀', text: 'ลูกค้าเห็นตำแหน่งคุณเฉพาะออเดอร์ที่คุณกำลังส่งเท่านั้น' },
          { icon: '▶️', text: 'เริ่มแชร์เมื่อรับงาน และหยุดเองเมื่อส่งเสร็จหรือยกเลิกงาน' },
          { icon: '🔒', text: 'ไม่แชร์ตอนออฟไลน์หรือตอนที่ไม่มีงาน' },
          { icon: '✅', text: 'ยอมรับครั้งเดียว ใช้ได้ทุกงาน' },
        ]}
        acceptLabel="ยอมรับ"
        declineLabel="ไม่ตอนนี้"
        acceptVariant="success"
        footnote="ต้องยอมรับข้อนี้ก่อนรับงานแรก"
        onAccept={acceptConsent}
        onDecline={() => finish(false)}
      />
    </>
  );

  return { element, ensureForeground, offerBackground, requestConsent, permissions, refreshPermissions };
};

export default useRiderPermissionFlow;
