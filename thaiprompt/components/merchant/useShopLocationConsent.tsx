/**
 * useShopLocationConsent — ขอสิทธิ์ตำแหน่งให้ร้านเคลื่อนที่แบบ "prominent disclosure" (Google Play)
 *
 * ร้านใช้ตำแหน่งต่างจากผู้ซื้อ: ปักจุดขายให้ลูกค้าเห็น + แชร์ตำแหน่งสดทุก 30 วินาที
 * สิทธิ์ตำแหน่งของเครื่องที่ได้มาจาก flow อื่น (เช่น หาร้านใกล้ตัว — "ไม่ส่งให้ร้าน") จึงนับเป็นความยินยอมของร้านไม่ได้
 *
 * ensure():
 *   1. เครื่องนี้ + บัญชีนี้เคยยอมรับคำอธิบายของร้านแล้ว และมีสิทธิ์ "ขณะใช้แอป" → true ทันที
 *   2. ยังไม่มีสิทธิ์ และระบบไม่ให้ถามซ้ำแล้ว → แจ้งให้ไปเปิดในตั้งค่าเครื่อง → false
 *   3. แสดง ConsentSheet ของร้าน (แม้เครื่องจะได้สิทธิ์อยู่แล้ว) → ยอมรับ → จำไว้ต่อเครื่องต่อบัญชี
 *      → มีสิทธิ์อยู่แล้ว = true · ยังไม่มี = กล่องขอสิทธิ์ของระบบ → ผลลัพธ์
 *      ไม่ยอมรับ/ปฏิเสธ → false (หน้าจอแจ้งต่อเองว่าเปิดร้านเคลื่อนที่ไม่ได้ถ้าไม่มีตำแหน่ง)
 *
 * hasShopLocationConsent(): เช็คเงียบๆ (ไม่แสดงอะไร) — ใช้ก่อนเริ่มแชร์ตำแหน่งสดเองอัตโนมัติ
 * เฉพาะเครื่องที่เคยยินยอมแล้วเท่านั้น (เช่น มือถือเครื่องที่สองที่ล็อกอินบัญชีเดียวกันต้องเห็นคำอธิบายก่อน)
 *
 * ไม่ขอสิทธิ์ตำแหน่งเบื้องหลัง — ร้านส่งตำแหน่งเฉพาะตอนเปิดแอปไว้
 * วาง `element` ไว้ใน JSX ของหน้าจอหนึ่งครั้ง
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, Linking } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Location from 'expo-location';
import { ConsentSheet, resultHaptic } from '@/components/ui';
import { useAuthStore } from '@/stores/authStore';

/** รอให้ Modal ปิดสนิทก่อนเปิดกล่องของระบบ/Modal ถัดไป (iOS) */
const MODAL_SETTLE_MS = 350;
const CONSENT_KEY_PREFIX = 'fm_shop_location_consent_v1';

const wait = (ms: number) => new Promise<void>((resolve) => setTimeout(resolve, ms));

/** key ต่อบัญชี (เก็บในเครื่อง = ต่อเครื่องอยู่แล้ว) */
const consentKey = (): string | null => {
  const userId = useAuthStore.getState().user?.id;
  return userId ? `${CONSENT_KEY_PREFIX}:${userId}` : null;
};

const readAcknowledged = async (): Promise<boolean> => {
  const key = consentKey();
  if (!key) return false;
  try {
    return (await AsyncStorage.getItem(key)) === '1';
  } catch {
    return false;
  }
};

const saveAcknowledged = async (): Promise<void> => {
  const key = consentKey();
  if (!key) return;
  try {
    await AsyncStorage.setItem(key, '1');
  } catch {
    // บันทึกไม่ได้ → ครั้งหน้าแสดงคำอธิบายอีกรอบ (ปลอดภัยกว่า)
  }
};

const isGranted = async (): Promise<boolean> => {
  try {
    return (await Location.getForegroundPermissionsAsync()).status === 'granted';
  } catch {
    return false;
  }
};

/**
 * เครื่องนี้ (บัญชีนี้) เคยยอมรับคำอธิบายของร้าน และยังมีสิทธิ์ตำแหน่งอยู่หรือไม่ — ไม่แสดงอะไร
 */
export const hasShopLocationConsent = async (): Promise<boolean> =>
  (await readAcknowledged()) && (await isGranted());

const openSettingsAlert = () =>
  Alert.alert(
    'เปิดสิทธิ์ตำแหน่งก่อนนะ',
    'ไปที่การตั้งค่าเครื่อง > ตำแหน่ง แล้วเลือก "ขณะใช้แอป" เพื่อบอกลูกค้าว่าร้านอยู่ตรงไหน',
    [
      { text: 'ไว้ก่อน', style: 'cancel' },
      { text: 'เปิดการตั้งค่า', onPress: () => Linking.openSettings().catch(() => {}) },
    ]
  );

export const useShopLocationConsent = () => {
  const [visible, setVisible] = useState(false);
  const resolverRef = useRef<((value: boolean) => void) | null>(null);
  /** ตอนเปิดคำอธิบาย เครื่องมีสิทธิ์ตำแหน่งอยู่แล้วหรือยัง (มีแล้ว = ยอมรับแล้วไม่ต้องขอระบบซ้ำ) */
  const alreadyGrantedRef = useRef(false);
  const mountedRef = useRef(true);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      resolverRef.current?.(false);
      resolverRef.current = null;
    };
  }, []);

  const finish = useCallback((value: boolean) => {
    const resolve = resolverRef.current;
    resolverRef.current = null;
    if (mountedRef.current) setVisible(false);
    resolve?.(value);
  }, []);

  const ensure = useCallback(async (): Promise<boolean> => {
    const acknowledged = await readAcknowledged();
    let current: Location.LocationPermissionResponse | null = null;
    try {
      current = await Location.getForegroundPermissionsAsync();
    } catch {
      // อ่านสิทธิ์ไม่ได้ → แสดงคำอธิบายแล้วลองขอ
    }
    const granted = current?.status === 'granted';
    if (granted && acknowledged) return true;
    if (!granted && current?.canAskAgain === false) {
      openSettingsAlert();
      return false;
    }
    if (!mountedRef.current) return false;
    resolverRef.current?.(false);
    alreadyGrantedRef.current = granted;
    return new Promise<boolean>((resolve) => {
      resolverRef.current = resolve;
      setVisible(true);
    });
  }, []);

  const accept = useCallback(async () => {
    if (mountedRef.current) setVisible(false);
    await saveAcknowledged();
    if (alreadyGrantedRef.current) {
      resultHaptic('success');
      finish(true);
      return;
    }
    await wait(MODAL_SETTLE_MS);
    try {
      const response = await Location.requestForegroundPermissionsAsync();
      const granted = response.status === 'granted';
      if (granted) {
        resultHaptic('success');
      } else if (response.canAskAgain === false) {
        openSettingsAlert();
      }
      finish(granted);
    } catch {
      finish(false);
    }
  }, [finish]);

  const element = (
    <ConsentSheet
      visible={visible}
      icon="📍"
      title="ขอใช้ตำแหน่งเพื่อเปิดร้าน"
      description="ร้านเคลื่อนที่ต้องบอกลูกค้าว่าวันนี้ร้านอยู่ตรงไหน"
      reasons={[
        { icon: '🛒', text: 'ใช้ตำแหน่งตอนคุณกดเปิดร้าน เพื่อปักหมุดจุดขายวันนี้' },
        { icon: '👀', text: 'ลูกค้าเห็นตำแหน่งร้านเฉพาะตอนร้านเปิดเท่านั้น ปิดร้านแล้วตำแหน่งถูกซ่อนทันที' },
        { icon: '📡', text: 'ถ้าคุณเปิด "แชร์ตำแหน่งสด" แอปส่งตำแหน่งทุก 30 วินาที เฉพาะตอนเปิดแอปไว้ ปิดแอปแล้วหยุดชั่วคราว' },
        { icon: '🏠', text: 'ไม่ใช้ตำแหน่งเบื้องหลัง และไม่แสดงที่อยู่บ้านของคุณให้ใครเห็น' },
      ]}
      acceptLabel="เข้าใจแล้ว ใช้ตำแหน่ง"
      declineLabel="ไม่ตอนนี้"
      acceptVariant="success"
      footnote='ถ้ามีกล่องขอสิทธิ์ถัดไป เลือก "ขณะใช้แอป" ได้เลย'
      onAccept={accept}
      onDecline={() => finish(false)}
    />
  );

  return { element, ensure };
};

export default useShopLocationConsent;
