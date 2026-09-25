/**
 * Tab Layout — แท็บล่าง 5 แท็บ ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * หน้าแรก | ช้อป | คำสั่งซื้อ | กระเป๋าเงิน | โปรไฟล์
 * (แท็บ "สายงาน" ถูกถอดออก — นโยบาย Google Play: ไม่มี MLM ในแอป)
 *
 * แท็บที่เลือก = เม็ดน้ำเงินกรมท่า + ไอคอนทองแบบทึบ (สปริงเบาๆ) · ไม่เลือก = ไอคอนเส้นสีเทา
 * แท็บโปรไฟล์ใช้รูปโปรไฟล์ถ้ามี (วงทองเมื่อเลือก)
 * การแจ้งเตือนอยู่ที่กระดิ่งบนหัวหน้าแรก
 */

import React, { useEffect } from 'react';
import { Tabs } from 'expo-router';
import { Image, StyleSheet, View } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '@/components/ui/Text';
import { Icon, type IconName } from '@/components/ui/Icon';
import { useAuthStore } from '@/stores/authStore';
import { useSyncStore, initSyncMonitor } from '@/stores/syncStore';
import { ErrorBoundary } from '@/components';
import { isFeatureEnabled } from '@/config/appConfig';
import { getAvatarUrl } from '@/utils/user';
import { useTheme, withAlpha, radii, spacing, FONT } from '@/theme';
import { selectionHaptic } from '@/components/ui/haptics';

const PILL_W = 56;
const PILL_H = 34;

/** ไอคอนแท็บ — เลือกอยู่ = เม็ดน้ำเงินไอคอนทอง, ไม่เลือก = ไอคอนเส้นจาง */
const TabIcon = ({ focused, icon, avatarUrl }: { focused: boolean; icon: IconName; avatarUrl?: string | null }) => {
  const { colors, gradients, isDark } = useTheme();
  const scale = useSharedValue(focused ? 1 : 0.9);

  useEffect(() => {
    scale.value = withSpring(focused ? 1 : 0.9, { damping: 14, stiffness: 240 });
  }, [focused, scale]);

  const anim = useAnimatedStyle(() => ({ transform: [{ scale: scale.value }] }));

  if (avatarUrl) {
    return (
      <Animated.View style={[styles.pillBox, anim]}>
        <View
          style={[
            styles.avatarRing,
            { borderColor: focused ? colors.gold : 'transparent', backgroundColor: focused ? colors.goldSoft : 'transparent' },
          ]}
        >
          <Image source={{ uri: avatarUrl }} style={styles.avatar} />
        </View>
      </Animated.View>
    );
  }

  if (!focused) {
    return (
      <Animated.View style={[styles.pillBox, anim]}>
        <Icon name={icon} size={24} color={colors.tabInactive} />
      </Animated.View>
    );
  }

  return (
    <Animated.View style={[styles.pillBox, anim]}>
      <LinearGradient
        colors={gradients.navy}
        start={{ x: 0, y: 0 }}
        end={{ x: 0, y: 1 }}
        style={[
          styles.pill,
          { boxShadow: `0px 8px 16px -8px ${withAlpha(isDark ? '#000000' : '#0C1A33', 0.85)}` },
        ]}
      >
        <View style={styles.pillHighlight} />
        <Icon name={icon} size={22} color={colors.goldLight} weight="fill" />
      </LinearGradient>
    </Animated.View>
  );
};

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
  const { colors, isDark } = useTheme();
  const insets = useSafeAreaInsets();
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

  const barHeight = 66 + Math.max(insets.bottom, spacing.sm);

  return (
    <ErrorBoundary>
      <View style={styles.root}>
        <Tabs
          screenListeners={{
            tabPress: () => selectionHaptic(),
          }}
          screenOptions={{
            headerShown: false,
            tabBarActiveTintColor: isDark ? colors.gold : colors.navy,
            tabBarInactiveTintColor: colors.tabInactive,
            tabBarLabelStyle: styles.label,
            tabBarStyle: {
              height: barHeight,
              paddingTop: spacing.sm,
              paddingBottom: Math.max(insets.bottom, spacing.sm),
              backgroundColor: isDark ? 'rgba(15,19,28,0.98)' : 'rgba(255,255,255,0.97)',
              borderTopWidth: 1,
              borderTopColor: colors.divider,
              boxShadow: `0px -18px 30px -24px ${withAlpha(isDark ? '#000000' : '#10223F', isDark ? 0.9 : 0.4)}`,
            },
            tabBarItemStyle: styles.item,
          }}
        >
          <Tabs.Screen
            name="index"
            options={{
              title: 'หน้าแรก',
              tabBarAccessibilityLabel: 'หน้าแรก',
              tabBarIcon: ({ focused }) => <TabIcon focused={focused} icon="house" />,
            }}
          />
          <Tabs.Screen
            name="shop"
            options={{
              title: 'ช้อป',
              tabBarAccessibilityLabel: 'ช้อป',
              href: isFeatureEnabled('SHOPPING_ENABLED') ? undefined : null,
              tabBarIcon: ({ focused }) => <TabIcon focused={focused} icon="storefront" />,
            }}
          />
          <Tabs.Screen
            name="orders"
            options={{
              title: 'คำสั่งซื้อ',
              tabBarAccessibilityLabel: 'คำสั่งซื้อ',
              tabBarIcon: ({ focused }) => <TabIcon focused={focused} icon="receipt" />,
            }}
          />
          <Tabs.Screen
            name="wallet"
            options={{
              title: 'กระเป๋าเงิน',
              tabBarAccessibilityLabel: 'กระเป๋าเงิน',
              href: isFeatureEnabled('WALLET_ENABLED') ? undefined : null,
              tabBarIcon: ({ focused }) => <TabIcon focused={focused} icon="wallet" />,
            }}
          />
          <Tabs.Screen
            name="profile"
            options={{
              title: 'โปรไฟล์',
              tabBarAccessibilityLabel: 'โปรไฟล์',
              tabBarIcon: ({ focused }) => <TabIcon focused={focused} icon="user-circle" avatarUrl={avatarUrl} />,
            }}
          />
        </Tabs>

        <OfflinePill />
      </View>
    </ErrorBoundary>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  item: {
    paddingTop: 0,
  },
  label: {
    fontFamily: FONT.semibold,
    fontSize: 11.5,
    marginTop: 3,
  },
  pillBox: {
    width: PILL_W,
    height: PILL_H,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pill: {
    width: PILL_W,
    height: PILL_H,
    borderRadius: 13,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  pillHighlight: {
    position: 'absolute',
    top: 0,
    left: 10,
    right: 10,
    height: 1,
    backgroundColor: 'rgba(255,255,255,0.16)',
  },
  avatarRing: {
    width: 32,
    height: 32,
    borderRadius: 16,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatar: {
    width: 26,
    height: 26,
    borderRadius: 13,
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
