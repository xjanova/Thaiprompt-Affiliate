/**
 * WebView Screen - เปิดลิงก์เว็บของเราในเบราว์เซอร์ในแอป (Custom Tabs)
 *
 * PLAY-23: deep link thaiprompt://webview?url= รับเฉพาะ https://*.thaiprompt.online
 * scheme/โดเมนอื่น (intent://, javascript:, เว็บภายนอก) ปฏิเสธทั้งหมด
 *
 * หน้าตา: หัวน้ำเงินกรมท่า (Screen) + ไอคอนในวงกลมขาว + ตัวหมุนสีทอง ระหว่างรอเบราว์เซอร์เปิด
 * พารามิเตอร์ icon (อีโมจิเดิม/ชื่อไอคอน) แปลงเป็นไอคอนเส้น — ไม่แสดงอีโมจิ
 */

import React, { useEffect, useRef } from 'react';
import {
  View,
  ActivityIndicator,
  StyleSheet,
} from 'react-native';
import { Text } from '@/components/ui/Text';
import { router, useLocalSearchParams } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import { isTrustedWebUrl } from '@/utils/linking';
import { EmptyState, Icon, Screen, iconFromLegacy, type IconName } from '@/components/ui';
import { useTheme, shadowStyle, spacing, typography } from '@/theme';

const safeDecode = (value?: string): string | null => {
  if (!value) return null;
  try {
    return decodeURIComponent(value);
  } catch {
    return null;
  }
};

const goBackSafely = () => {
  if (router.canGoBack()) {
    router.back();
  } else {
    router.replace('/(tabs)' as never);
  }
};

/** ชื่อโดเมนของลิงก์ (แสดงใต้ข้อความโหลด ให้ผู้ใช้เห็นว่ากำลังไปเว็บไหน) */
const hostOf = (value: string): string => {
  const match = /^https:\/\/([^/?#@\s]+)/i.exec(value);
  return match ? match[1].toLowerCase() : '';
};

export default function WebViewScreen() {
  const { colors } = useTheme();
  const params = useLocalSearchParams<{
    url?: string;
    title?: string;
    icon?: string;
  }>();

  // Decode URL
  const rawUrl = safeDecode(params.url);
  const url = isTrustedWebUrl(rawUrl) ? rawUrl : null;
  const title = safeDecode(params.title) || 'เว็บไซต์';
  // ไอคอนจากผู้เรียก (อีโมจิเดิมหรือชื่อไอคอน) → ไอคอนเส้น · ไม่รู้จัก = ลูกโลก
  const icon: IconName = iconFromLegacy(safeDecode(params.icon)) ?? 'globe';
  const openedRef = useRef(false);

  // เปิด URL ในเบราว์เซอร์แล้วกลับหน้าเดิม (เปิดครั้งเดียว)
  useEffect(() => {
    if (!url || openedRef.current) return;
    openedRef.current = true;
    WebBrowser.openBrowserAsync(url)
      .catch(() => {
        // เปิดไม่ได้ก็กลับหน้าเดิม
      })
      .finally(() => {
        goBackSafely();
      });
  }, [url]);

  // ไม่มี URL หรือเป็นลิงก์ที่ไม่อนุญาต
  if (!url) {
    return (
      <Screen title={title} scroll={false} onBack={goBackSafely}>
        <EmptyState
          icon={rawUrl ? 'lock-key' : 'link'}
          title={rawUrl ? 'ลิงก์นี้เปิดจากแอปไม่ได้เพื่อความปลอดภัยของคุณ' : 'ไม่พบลิงก์ที่ต้องการเปิด'}
          actionLabel="กลับ"
          onAction={goBackSafely}
        />
      </Screen>
    );
  }

  const host = hostOf(url);

  // ไม่มีปุ่มย้อนกลับระหว่างรอเบราว์เซอร์เปิด — หน้านี้ย้อนกลับเองเมื่อปิดเบราว์เซอร์ (กันย้อนกลับซ้อนสองครั้ง)
  return (
    <Screen title={title} scroll={false} showBack={false}>
      <View style={styles.loadingContainer}>
        <View
          style={[
            styles.iconCircle,
            { backgroundColor: colors.card, borderColor: colors.border },
            shadowStyle('md', colors.shadowDark),
          ]}
        >
          <Icon name={icon} size={38} color={colors.goldDeep} />
        </View>
        <ActivityIndicator size="large" color={colors.gold} style={styles.spinner} />
        <Text style={[typography.h2, styles.center, { color: colors.textStrong }]}>กำลังเปิด {title}...</Text>
        {!!host && (
          <View style={[styles.hostPill, { backgroundColor: colors.card, borderColor: colors.border }]}>
            <Icon name="lock" size={13} color={colors.success} weight="fill" />
            <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
              {host}
            </Text>
          </View>
        )}
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  center: {
    textAlign: 'center',
  },
  loadingContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.xxl,
    paddingBottom: spacing.xxxl * 2,
  },
  iconCircle: {
    width: 88,
    height: 88,
    borderRadius: 44,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  spinner: {
    marginTop: spacing.xxl,
    marginBottom: spacing.lg,
  },
  hostPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
    borderRadius: 999,
    borderWidth: 1,
    maxWidth: '100%',
  },
});
