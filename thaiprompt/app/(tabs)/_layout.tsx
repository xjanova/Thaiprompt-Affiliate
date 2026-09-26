/**
 * Tab Layout — แถบล่าง "ซุ้มกนก" (แบบ B เจ้าของเลือก 2026-09-26) ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * หน้าแรก | ช้อป | [เหรียญตรา ตลาดสด] | คำสั่งซื้อ | ฉัน
 * - เหรียญกลางเปิดหน้า /taladsod (ไม่ใช่แท็บ) · ปิดตลาดสด = แถบเรียบ 4 แท็บ ไม่มีซุ้ม
 * - กระเป๋าเงินไม่อยู่บนแถบแล้ว (ยังเปิดได้จากการ์ด TP Wallet และไทล์ในหน้าแรก) — route ยังอยู่ ซ่อนด้วย href: null
 * - (แท็บ "สายงาน" ถูกถอดออก — นโยบาย Google Play: ไม่มี MLM ในแอป)
 * หน้าตาและเรขาคณิตของแถบอยู่ใน components/ui/KanokTabBar.tsx
 * การแจ้งเตือนอยู่ที่กระดิ่งบนหัวหน้าแรก
 */

import React, { useEffect } from 'react';
import { Tabs, router } from 'expo-router';
import { StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '@/components/ui/Text';
import { Icon } from '@/components/ui/Icon';
import { KanokCrest, KanokTabBar, type KanokTab } from '@/components/ui/KanokTabBar';
import { useAuthStore } from '@/stores/authStore';
import { useSyncStore, initSyncMonitor } from '@/stores/syncStore';
import { ErrorBoundary } from '@/components';
import { isFeatureEnabled } from '@/config/appConfig';
import { getAvatarUrl } from '@/utils/user';
import { useTheme, radii, spacing } from '@/theme';

/** ป้ายออฟไลน์ (แสดงเฉพาะตอนไม่มีเน็ต) */
const OfflinePill = () => {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const isConnected = useSyncStore((state) => state?.isConnected ?? true);
  const status = useSyncStore((state) => state?.status ?? 'online');

  if (isConnected && status !== 'offline') return null;

  return (
    <View
      pointerEvents="none"
      style={[styles.offline, { top: insets.top + spacing.xs, backgroundColor: colors.danger }]}
      accessibilityLiveRegion="polite"
    >
      <Icon name="wifi-slash" size={14} color={colors.textOnAccent} />
      <Text style={[styles.offlineText, { color: colors.textOnAccent }]}>ออฟไลน์ — แสดงข้อมูลล่าสุดที่บันทึกไว้</Text>
    </View>
  );
};

export default function TabLayout() {
  const user = useAuthStore((state) => state.user);

  // เริ่มตัวตรวจสถานะเน็ต
  useEffect(() => {
    const unsubscribe = initSyncMonitor();
    return () => unsubscribe();
  }, []);

  let avatarUrl: string | null = null;
  try {
    avatarUrl = getAvatarUrl(user?.avatar);
  } catch {
    avatarUrl = null;
  }

  const shopOn = isFeatureEnabled('SHOPPING_ENABLED');
  const taladsodOn = isFeatureEnabled('TALADSOD_ENABLED');

  const left: KanokTab[] = [
    { name: 'index', label: 'หน้าแรก', icon: 'house' },
    ...(shopOn ? [{ name: 'shop', label: 'ช้อป', icon: 'storefront' as const }] : []),
  ];
  const center = taladsodOn
    ? {
        label: 'ตลาดสด',
        icon: 'basket' as const,
        accessibilityHint: 'เปิดตลาดสด ร้านและรถเข็นใกล้คุณ',
        onPress: () => router.push('/taladsod' as never),
      }
    : undefined;
  const right: KanokTab[] = [
    { name: 'orders', label: 'คำสั่งซื้อ', icon: 'receipt' },
    { name: 'profile', label: 'ฉัน', icon: 'user-circle', avatarUrl },
  ];

  return (
    <ErrorBoundary>
      <View style={styles.root}>
        <Tabs
          screenOptions={{ headerShown: false }}
          tabBar={(props) => (
            <KanokTabBar
              {...props}
              left={left}
              right={right}
              center={center}
            />
          )}
        >
          <Tabs.Screen name="index" options={{ title: 'หน้าแรก' }} />
          <Tabs.Screen name="shop" options={{ title: 'ช้อป', href: shopOn ? undefined : null }} />
          <Tabs.Screen name="orders" options={{ title: 'คำสั่งซื้อ' }} />
          {/* กระเป๋าเงิน: ยังเป็นแท็บ (ลิงก์ /(tabs)/wallet ใช้ได้เหมือนเดิม) แต่ไม่แสดงบนแถบ */}
          <Tabs.Screen name="wallet" options={{ title: 'กระเป๋าเงิน', href: null }} />
          <Tabs.Screen name="profile" options={{ title: 'ฉัน' }} />
        </Tabs>

        {/* ซุ้มกนก + เหรียญตลาดสด ลอยเหนือแถบ (อยู่ใน root ที่ครอบทั้งจอ — Android ไม่ตัดขอบ แตะเหรียญได้ทั้งวง) */}
        <KanokCrest center={center} />

        <OfflinePill />
      </View>
    </ErrorBoundary>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  offline: {
    position: 'absolute',
    alignSelf: 'center',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
    borderRadius: radii.pill,
  },
  offlineText: {
    fontSize: 12,
    fontWeight: '700',
  },
});
