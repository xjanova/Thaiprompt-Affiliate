/**
 * Tab Layout — แท็บล่าง 5 แท็บ ธีมนวลทองคำ
 *
 * หน้าแรก | ช้อป | คำสั่งซื้อ | กระเป๋าเงิน | โปรไฟล์
 * (แท็บ "สายงาน" ถูกถอดออก — นโยบาย Google Play: ไม่มี MLM ในแอป)
 *
 * ไอคอนแท็บที่เลือก = ปุ่มทองนูนแบบ Button3D ย่อส่วน (ไล่เฉด + ขอบล่างเข้ม + ไฮไลต์บน)
 * การแจ้งเตือนย้ายไปเป็นกระดิ่งที่หัวหน้าแรก
 */

import React, { useEffect } from 'react';
import { Tabs } from 'expo-router';
import { Image, StyleSheet, Text, View } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { useSyncStore, initSyncMonitor } from '@/stores/syncStore';
import { ErrorBoundary } from '@/components';
import { isFeatureEnabled } from '@/config/appConfig';
import { getAvatarUrl, getAvatarInitial } from '@/utils/user';
import { useTheme, withAlpha, radii, spacing } from '@/theme';
import { selectionHaptic } from '@/components/ui/haptics';

const ICON_W = 46;
const ICON_H = 32;
const EDGE = 3;

/** ไอคอนแท็บ — เลือกอยู่ = ทองนูน, ไม่เลือก = จางลง */
const TabIcon = ({ focused, emoji, avatarUrl, initial }: {
  focused: boolean;
  emoji?: string;
  avatarUrl?: string | null;
  initial?: string;
}) => {
  const { colors, gradients, buttonEdges } = useTheme();
  const scale = useSharedValue(focused ? 1 : 0.92);

  useEffect(() => {
    scale.value = withSpring(focused ? 1 : 0.92, { damping: 14, stiffness: 220 });
  }, [focused, scale]);

  const anim = useAnimatedStyle(() => ({ transform: [{ scale: scale.value }] }));

  const content = avatarUrl ? (
    <Image source={{ uri: avatarUrl }} style={styles.avatar} />
  ) : emoji ? (
    <Text style={[styles.emoji, !focused && styles.emojiInactive]}>{emoji}</Text>
  ) : (
    <Text style={[styles.initial, { color: focused ? colors.textOnGold : colors.textMuted }]}>{initial || 'U'}</Text>
  );

  if (!focused) {
    return (
      <Animated.View style={[styles.iconBox, anim]}>
        {content}
      </Animated.View>
    );
  }

  return (
    <Animated.View style={[styles.iconWrap, anim]}>
      {/* ขอบล่าง = ความหนา */}
      <View style={[styles.iconEdge, { backgroundColor: buttonEdges.primary }]} />
      <LinearGradient
        colors={gradients.primary}
        start={{ x: 0, y: 0 }}
        end={{ x: 0.4, y: 1 }}
        style={[styles.iconBody, { boxShadow: `0px 4px 10px ${withAlpha(colors.amber, 0.35)}` }]}
      >
        <View style={styles.iconHighlight} />
        {content}
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
      <Text style={styles.offlineText}>ออฟไลน์ — แสดงข้อมูลล่าสุดที่บันทึกไว้</Text>
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
  let initial = 'U';
  try {
    avatarUrl = getAvatarUrl(user?.avatar);
    initial = getAvatarInitial(user?.name);
  } catch {
    avatarUrl = null;
  }

  const barHeight = 62 + Math.max(insets.bottom, spacing.sm);

  return (
    <ErrorBoundary>
      <View style={styles.root}>
        <Tabs
          screenListeners={{
            tabPress: () => selectionHaptic(),
          }}
          screenOptions={{
            headerShown: false,
            tabBarActiveTintColor: colors.goldDeep,
            tabBarInactiveTintColor: colors.tabInactive,
            tabBarLabelStyle: styles.label,
            tabBarStyle: {
              height: barHeight,
              paddingTop: spacing.sm,
              paddingBottom: Math.max(insets.bottom, spacing.sm),
              backgroundColor: colors.card,
              borderTopWidth: 1,
              borderTopColor: isDark ? colors.shadowLight : colors.shadowLight,
              boxShadow: `0px -6px 18px ${withAlpha(isDark ? '#000000' : '#8A7555', isDark ? 0.45 : 0.16)}`,
            },
            tabBarItemStyle: styles.item,
          }}
        >
          <Tabs.Screen
            name="index"
            options={{
              title: 'หน้าแรก',
              tabBarAccessibilityLabel: 'หน้าแรก',
              tabBarIcon: ({ focused }) => <TabIcon focused={focused} emoji="🏠" />,
            }}
          />
          <Tabs.Screen
            name="shop"
            options={{
              title: 'ช้อป',
              tabBarAccessibilityLabel: 'ช้อป',
              href: isFeatureEnabled('SHOPPING_ENABLED') ? undefined : null,
              tabBarIcon: ({ focused }) => <TabIcon focused={focused} emoji="🛍️" />,
            }}
          />
          <Tabs.Screen
            name="orders"
            options={{
              title: 'คำสั่งซื้อ',
              tabBarAccessibilityLabel: 'คำสั่งซื้อ',
              tabBarIcon: ({ focused }) => <TabIcon focused={focused} emoji="🧾" />,
            }}
          />
          <Tabs.Screen
            name="wallet"
            options={{
              title: 'กระเป๋าเงิน',
              tabBarAccessibilityLabel: 'กระเป๋าเงิน',
              href: isFeatureEnabled('WALLET_ENABLED') ? undefined : null,
              tabBarIcon: ({ focused }) => <TabIcon focused={focused} emoji="👛" />,
            }}
          />
          <Tabs.Screen
            name="profile"
            options={{
              title: 'โปรไฟล์',
              tabBarAccessibilityLabel: 'โปรไฟล์',
              tabBarIcon: ({ focused }) => (
                <TabIcon focused={focused} avatarUrl={avatarUrl} initial={initial} emoji={avatarUrl ? undefined : '👤'} />
              ),
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
    paddingTop: 2,
  },
  label: {
    fontSize: 11,
    fontWeight: '700',
    marginTop: 4,
  },
  iconBox: {
    width: ICON_W,
    height: ICON_H,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconWrap: {
    width: ICON_W,
    height: ICON_H + EDGE,
  },
  iconEdge: {
    position: 'absolute',
    left: 0,
    right: 0,
    top: EDGE,
    height: ICON_H,
    borderRadius: radii.md,
  },
  iconBody: {
    width: ICON_W,
    height: ICON_H,
    borderRadius: radii.md,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  iconHighlight: {
    position: 'absolute',
    top: 0,
    left: 8,
    right: 8,
    height: 1,
    backgroundColor: 'rgba(255,255,255,0.7)',
  },
  emoji: {
    fontSize: 18,
  },
  emojiInactive: {
    opacity: 0.55,
  },
  initial: {
    fontSize: 15,
    fontWeight: '800',
  },
  avatar: {
    width: 26,
    height: 26,
    borderRadius: 13,
    borderWidth: 1.5,
    borderColor: 'rgba(255,255,255,0.85)',
  },
  offline: {
    position: 'absolute',
    alignSelf: 'center',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
    borderRadius: radii.pill,
  },
  offlineText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '700',
  },
});
