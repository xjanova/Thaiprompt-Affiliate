/**
 * ปลายทาง deep link thaiprompt://auth?code=..&state=.. (หลังเข้าสู่ระบบบนเว็บด้วยอีเมล / LINE / Facebook / Google)
 *
 * - iOS: auth session รับ URL ไปเองทั้งหมด หน้านี้ไม่ถูกเปิด
 * - Android: เว็บเด้งเข้าแอป → auth session ของหน้าเข้าสู่ระบบได้ผล "และ" หน้านี้ถูกเปิดซ้อนขึ้นมาพร้อมกัน
 *     หน้าเข้าสู่ระบบรออยู่ → หน้านี้ถอยกลับทันที ให้หน้าเดิมแลก code (ไม่ให้แลกซ้ำ ไม่ให้เห็นหน้า "ไม่พบหน้า")
 *     ไม่มีใครรอ (ผู้ใช้ปิดหน้าต่างไปก่อน แล้วค่อยกด "เปิดแอพ" บนเว็บ) → หน้านี้แลก code เอง
 */

import React, { useEffect, useRef } from 'react';
import { ActivityIndicator, Alert, StyleSheet, View } from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { useAuthStore } from '@/stores/authStore';
import { claimWebAuthCode, isWebAuthSessionPending } from '@/utils/webAuth';
import { useTheme, spacing, typography } from '@/theme';

const first = (value: string | string[] | undefined): string =>
  (Array.isArray(value) ? value[0] : value) ?? '';

export default function AuthRedirectScreen() {
  const { colors } = useTheme();
  const handleWebAuthCallback = useAuthStore((state) => state.handleWebAuthCallback);
  const params = useLocalSearchParams<{ code?: string; state?: string; error?: string }>();
  const handledRef = useRef(false);
  const mountedRef = useRef(true);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  useEffect(() => {
    // code ใช้ได้ครั้งเดียว — เอฟเฟกต์รันซ้ำ (dev) ห้ามแลกซ้ำ
    if (handledRef.current) return;
    handledRef.current = true;

    // ออกจากหน้านี้: สำเร็จ → หน้าแรก · ไม่สำเร็จ → กลับหน้าเดิม (หรือหน้าเข้าสู่ระบบ)
    const leave = (signedIn: boolean) => {
      if (!mountedRef.current) return;
      if (signedIn) {
        router.replace('/(tabs)');
      } else if (router.canGoBack()) {
        router.back();
      } else {
        router.replace('/login');
      }
    };

    const code = first(params.code);
    const state = first(params.state);
    const error = first(params.error);

    // หน้าเข้าสู่ระบบรอผลอยู่ → ให้หน้านั้นจัดการ (รวมถึงแจ้งเตือนกรณียกเลิก)
    if (isWebAuthSessionPending()) {
      leave(false);
      return;
    }

    if (code && state) {
      if (!claimWebAuthCode(code)) {
        // อีกทางแลกไปแล้ว
        leave(useAuthStore.getState().isAuthenticated);
      } else {
        handleWebAuthCallback(code, state)
          .then((ok) => leave(ok))
          .catch(() => leave(false));
      }
    } else {
      if (error && error !== 'access_denied') {
        Alert.alert('เข้าสู่ระบบไม่สำเร็จ', 'ลองใหม่อีกครั้งนะ');
      }
      leave(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <View style={[styles.root, { backgroundColor: colors.background }]}>
      <ActivityIndicator size="large" color={colors.gold} />
      <Text style={[typography.body, styles.label, { color: colors.textMuted }]}>กำลังเข้าสู่ระบบ...</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.xl,
  },
  label: {
    marginTop: spacing.md,
  },
});
