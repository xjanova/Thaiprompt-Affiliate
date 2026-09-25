/**
 * หน้าแรก — ธีมนวลทองคำ
 *
 * - แบนเนอร์แคมเปญ (placement home, สำรองด้วยรูปในแอป)
 * - การ์ดกระเป๋าเงิน: ยอดจริงจาก GET /wallet (โหลดไม่ได้ = บอกให้ลองใหม่ ไม่โชว์ ฿0 ปลอม — PLAY-19)
 * - ทางเข้าหลัก: ตลาดสด · ช้อป · ไรเดอร์ · ร้านของฉัน (เฉพาะร้านค้า) · ชวนเพื่อน
 * - ไม่มีเมนู MLM / คริปโต / ดูคลิปได้เงิน (นโยบาย Google Play)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StatusBar, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { router, useFocusEffect } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO, isFeatureEnabled } from '@/config/appConfig';
import { getWallet } from '@/services/api';
import { checkIsSeller } from '@/services/api/merchantApi';
import NotificationBell from '@/components/NotificationBell';
import { ErrorBoundary } from '@/components';
import {
  BannerSlider,
  Button3D,
  Card3D,
  Chip,
  PriceText,
  SectionHeader,
} from '@/components/ui';
import { useTheme, spacing, radii, typography, clayShadowStyle } from '@/theme';
import { getAvatarUrl, getAvatarInitial } from '@/utils/user';

interface QuickEntry {
  id: string;
  title: string;
  subtitle: string;
  icon: string;
  route: string;
  requiresLogin: boolean;
}

const getGreeting = (): string => {
  const hour = new Date().getHours();
  if (hour < 12) return 'สวัสดีตอนเช้า';
  if (hour < 17) return 'สวัสดีตอนบ่าย';
  return 'สวัสดีตอนเย็น';
};

/** กระเบื้องทางเข้าบริการ */
const EntryTile = ({ entry, onPress }: { entry: QuickEntry; onPress: () => void }) => {
  const { colors } = useTheme();
  return (
    <Card3D
      onPress={onPress}
      padding={spacing.md}
      radius={radii.lg}
      shadow="sm"
      style={styles.tile}
      accessibilityLabel={`${entry.title} ${entry.subtitle}`}
    >
      <View style={[styles.tileIcon, { backgroundColor: colors.goldSoft }]}>
        <Text style={styles.tileEmoji}>{entry.icon}</Text>
      </View>
      <Text numberOfLines={1} style={[typography.h3, { color: colors.textStrong }]}>
        {entry.title}
      </Text>
      <Text numberOfLines={2} style={[typography.caption, { color: colors.textMuted }]}>
        {entry.subtitle}
      </Text>
    </Card3D>
  );
};

export default function HomeScreen() {
  const { colors, gradients, isDark } = useTheme();
  const insets = useSafeAreaInsets();
  const user = useAuthStore((state) => state.user);
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

  const [greeting, setGreeting] = useState(getGreeting());
  const [balance, setBalance] = useState<number | string | null>(null);
  const [walletState, setWalletState] = useState<'idle' | 'loading' | 'ready' | 'error'>('idle');
  const [isSeller, setIsSeller] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [bannerKey, setBannerKey] = useState(0);
  const mountedRef = useRef(true);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // ---------- ข้อมูล ----------
  const loadWallet = useCallback(async () => {
    if (!isAuthenticated || !isFeatureEnabled('WALLET_ENABLED')) return;
    setWalletState((s) => (s === 'ready' ? 'ready' : 'loading'));
    const requestedFor = useAuthStore.getState().user?.id ?? null;
    const result = await getWallet();
    // ออกจากระบบ/สลับบัญชีระหว่างรอ → ทิ้งผลลัพธ์ (กันยอดของบัญชีเก่าโผล่)
    if (!mountedRef.current || !useAuthStore.getState().isAuthenticated) return;
    if ((useAuthStore.getState().user?.id ?? null) !== requestedFor) return;
    if (result?.success && result.data) {
      setBalance(result.data.balance);
      setWalletState('ready');
    } else {
      setWalletState((s) => (s === 'ready' ? 'ready' : 'error'));
    }
  }, [isAuthenticated]);

  const loadSeller = useCallback(
    async (force: boolean = false) => {
      if (!isAuthenticated || !user?.id || !isFeatureEnabled('MERCHANT_ENABLED')) {
        setIsSeller(false);
        return;
      }
      const requestedFor = user.id;
      const seller = await checkIsSeller(requestedFor, force);
      if (mountedRef.current && useAuthStore.getState().user?.id === requestedFor) setIsSeller(seller);
    },
    [isAuthenticated, user?.id]
  );

  useEffect(() => {
    if (!isAuthenticated) {
      setBalance(null);
      setWalletState('idle');
      setIsSeller(false);
    }
  }, [isAuthenticated]);

  useFocusEffect(
    useCallback(() => {
      setGreeting(getGreeting());
      loadWallet();
      loadSeller();
    }, [loadWallet, loadSeller])
  );

  const onRefresh = async () => {
    setRefreshing(true);
    setBannerKey((k) => k + 1);
    await Promise.all([loadWallet(), loadSeller(true)]);
    if (mountedRef.current) setRefreshing(false);
  };

  // ---------- ทางเข้าบริการ ----------
  const entries: QuickEntry[] = [
    isFeatureEnabled('TALADSOD_ENABLED') && {
      id: 'taladsod', title: 'ตลาดสด', subtitle: 'ของสด อาหารร้อนๆ ใกล้บ้าน', icon: '🥬', route: '/taladsod', requiresLogin: false,
    },
    isFeatureEnabled('SHOPPING_ENABLED') && {
      id: 'shop', title: 'ช้อป', subtitle: 'สินค้าจากร้านค้าในระบบ', icon: '🛍️', route: '/(tabs)/shop', requiresLogin: false,
    },
    isFeatureEnabled('RIDER_ENABLED') && {
      id: 'rider', title: 'ไรเดอร์', subtitle: 'รับงานส่งของใกล้บ้าน', icon: '🛵', route: '/rider', requiresLogin: true,
    },
    isFeatureEnabled('MERCHANT_ENABLED') && isSeller && {
      id: 'merchant', title: 'ร้านของฉัน', subtitle: 'ออเดอร์ใหม่และการจัดส่ง', icon: '🏪', route: '/merchant', requiresLogin: true,
    },
    isFeatureEnabled('REFERRAL_ENABLED') && {
      id: 'referral', title: 'ชวนเพื่อน', subtitle: 'แชร์รหัสให้เพื่อนสมัคร', icon: '🤝', route: '/referral', requiresLogin: true,
    },
  ].filter(Boolean) as QuickEntry[];

  const openEntry = (entry: QuickEntry) => {
    if (entry.requiresLogin && !isAuthenticated) {
      router.push('/login');
      return;
    }
    router.push(entry.route as never);
  };

  let avatarUrl: string | null = null;
  let initial = 'U';
  try {
    avatarUrl = getAvatarUrl(user?.avatar);
    initial = getAvatarInitial(user?.name);
  } catch {
    avatarUrl = null;
  }

  return (
    <ErrorBoundary>
      <View style={[styles.root, { backgroundColor: colors.background }]}>
        <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={colors.background} />

        {/* ไล่เฉดทองจางๆ ด้านบน ให้หน้าแรกมีมิติ */}
        <View pointerEvents="none" style={[styles.topGlow, { backgroundColor: gradients.hero[0], opacity: isDark ? 0.08 : 0.35 }]} />

        <ScrollView
          contentContainerStyle={{ paddingTop: insets.top + spacing.sm, paddingBottom: spacing.xxxl }}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              tintColor={colors.gold}
              colors={[colors.gold]}
              progressBackgroundColor={colors.card}
            />
          }
        >
          {/* ---------- หัว ---------- */}
          <View style={styles.header}>
            <View style={styles.flex}>
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>{greeting} 👋</Text>
              <Text numberOfLines={1} style={[typography.h1, { color: colors.textStrong }]}>
                {isAuthenticated ? user?.name || 'ผู้ใช้' : 'ยินดีต้อนรับ'}
              </Text>
            </View>

            {isAuthenticated && (
              <View
                style={[
                  styles.circleButton,
                  { backgroundColor: colors.card },
                  clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
                ]}
              >
                <NotificationBell size={20} color={colors.goldDeep} />
              </View>
            )}

            <Pressable
              onPress={() => (isAuthenticated ? router.push('/(tabs)/profile' as never) : router.push('/login'))}
              accessibilityRole="button"
              accessibilityLabel={isAuthenticated ? 'โปรไฟล์' : 'เข้าสู่ระบบ'}
              style={[
                styles.circleButton,
                { backgroundColor: colors.goldSoft },
                clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
              ]}
            >
              {avatarUrl ? (
                <Image source={{ uri: avatarUrl }} style={styles.avatar} contentFit="cover" />
              ) : (
                <Text style={[typography.h3, { color: colors.goldDeep }]}>{isAuthenticated ? initial : '🔑'}</Text>
              )}
            </Pressable>
          </View>

          {/* ---------- แบนเนอร์ ---------- */}
          <BannerSlider placement="home" refreshKey={bannerKey} style={styles.banner} />

          {/* ---------- กระเป๋าเงิน / เข้าสู่ระบบ ---------- */}
          <View style={styles.section}>
            {isAuthenticated ? (
              isFeatureEnabled('WALLET_ENABLED') && (
                <Card3D gradientBorder padding={spacing.lg} accessibilityLabel="กระเป๋าเงิน">
                  <View style={styles.walletTop}>
                    <View style={[styles.walletIcon, { backgroundColor: colors.goldSoft }]}>
                      <Text style={styles.walletEmoji}>👛</Text>
                    </View>
                    <Text style={[typography.bodyStrong, styles.flex, { color: colors.text }]}>ยอดในกระเป๋าเงิน</Text>
                    <Pressable
                      onPress={() => router.push('/wallet-history')}
                      hitSlop={8}
                      accessibilityRole="button"
                      accessibilityLabel="ดูประวัติธุรกรรม"
                    >
                      <Text style={[typography.caption, { color: colors.goldDeep, fontWeight: '700' }]}>ประวัติ ›</Text>
                    </Pressable>
                  </View>

                  {walletState === 'error' ? (
                    <Pressable onPress={loadWallet} accessibilityRole="button" style={styles.walletError}>
                      <Text style={[typography.bodySm, { color: colors.danger }]}>โหลดยอดไม่สำเร็จ แตะเพื่อลองใหม่</Text>
                    </Pressable>
                  ) : walletState === 'ready' ? (
                    <PriceText amount={balance} size="xl" tone="strong" decimals={2} style={styles.balance} />
                  ) : (
                    <View style={[styles.balanceSkeleton, { backgroundColor: colors.inset }]} />
                  )}

                  <View style={styles.walletActions}>
                    <Button3D
                      title="เติมเงิน"
                      icon="➕"
                      size="sm"
                      onPress={() => router.push('/wallet-topup')}
                      style={styles.flex}
                    />
                    <Button3D
                      title="ถอนเงิน"
                      icon="🏦"
                      size="sm"
                      variant="secondary"
                      onPress={() => router.push('/wallet-withdraw')}
                      style={styles.flex}
                    />
                  </View>
                </Card3D>
              )
            ) : (
              <Card3D gradientBorder padding={spacing.lg}>
                <Text style={[typography.h2, { color: colors.textStrong }]}>เข้าสู่ระบบเพื่อเริ่มสั่งของ</Text>
                <Text style={[typography.bodySm, styles.loginHint, { color: colors.textMuted }]}>
                  ติดตามออเดอร์ จ่ายด้วยกระเป๋าเงิน และสมัครเป็นไรเดอร์ได้ในแอปเดียว
                </Text>
                <View style={styles.walletActions}>
                  <Button3D title="เข้าสู่ระบบ" icon="🔓" onPress={() => router.push('/login')} style={styles.flex} />
                  <Button3D title="สมัครสมาชิก" variant="secondary" onPress={() => router.push('/register')} style={styles.flex} />
                </View>
              </Card3D>
            )}
          </View>

          {/* ---------- บริการ ---------- */}
          <View style={styles.section}>
            <SectionHeader title="บริการ" subtitle="ทุกอย่างใกล้บ้าน ในแอปเดียว" />
            <View style={styles.grid}>
              {entries.map((entry) => (
                <EntryTile key={entry.id} entry={entry} onPress={() => openEntry(entry)} />
              ))}
            </View>
          </View>

          {/* ---------- ทางลัดเล็ก ---------- */}
          <View style={styles.section}>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
              {isFeatureEnabled('TAROT_ENABLED') && (
                <Chip label="ดูดวงไพ่ทาโรต์ฟรี" icon="🔮" onPress={() => router.push('/tarot' as never)} />
              )}
              <Chip label="ช่วยเหลือ" icon="💬" onPress={() => router.push('/support')} />
              <Chip label="คู่มือการใช้งาน" icon="📘" onPress={() => router.push('/wiki')} />
              <Chip label="ตั้งค่า" icon="⚙️" onPress={() => router.push('/settings')} />
            </ScrollView>
          </View>

          <Text style={[typography.micro, styles.version, { color: colors.textFaint }]}>
            {APP_INFO.NAME} v{APP_INFO.VERSION}
          </Text>
        </ScrollView>

      </View>
    </ErrorBoundary>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.screen,
    marginBottom: spacing.lg,
    gap: spacing.sm,
  },
  circleButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  avatar: {
    width: 44,
    height: 44,
  },
  banner: {
    marginBottom: spacing.lg,
  },
  section: {
    paddingHorizontal: spacing.screen,
    marginBottom: spacing.xl,
  },
  walletTop: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  walletIcon: {
    width: 36,
    height: 36,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  walletEmoji: {
    fontSize: 18,
  },
  balance: {
    marginTop: spacing.md,
  },
  balanceSkeleton: {
    marginTop: spacing.md,
    height: 38,
    width: '55%',
    borderRadius: radii.sm,
  },
  walletError: {
    marginTop: spacing.md,
    minHeight: 38,
    justifyContent: 'center',
  },
  walletActions: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.lg,
  },
  loginHint: {
    marginTop: spacing.xs,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    rowGap: spacing.md,
  },
  tile: {
    width: '48%',
  },
  tileIcon: {
    width: 44,
    height: 44,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm,
  },
  tileEmoji: {
    fontSize: 24,
  },
  chips: {
    gap: spacing.sm,
    paddingVertical: spacing.xs,
  },
  version: {
    textAlign: 'center',
    marginTop: spacing.sm,
  },
  topGlow: {
    position: 'absolute',
    top: -120,
    left: -60,
    right: -60,
    height: 240,
    borderBottomLeftRadius: 240,
    borderBottomRightRadius: 240,
  },
});
