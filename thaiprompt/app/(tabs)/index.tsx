/**
 * หน้าแรก — ธีมรอยัล น้ำเงินกรมท่า-ทอง (แบบ A ที่เจ้าของเลือก 2026-09-26)
 *
 * - หัวน้ำเงินลายกนก: ที่อยู่จัดส่ง · กระดิ่ง · รูปโปรไฟล์ · คำทักทาย (ชื่อฟอนต์มีเชิง)
 * - การ์ดกระเป๋าเงินกระจกขอบทอง: ยอดจริงจาก GET /wallet (โหลดไม่ได้ = บอกให้ลองใหม่ ไม่โชว์ ฿0 ปลอม — PLAY-19)
 *   แตะรูปตาเพื่อซ่อน/แสดงยอด (ไม่บันทึกข้ามการเปิดแอป)
 * - บริการ = ไอคอน 3D ประจำแบรนด์: ตลาดสด · ช้อป · ไรเดอร์ · ร้าน · รถเข็นใกล้ฉัน · กระเป๋าเงิน · ชวนเพื่อน · ดูดวง
 * - แบนเนอร์แคมเปญ (placement home) · ร้านเปิดอยู่ใกล้คุณ (เฉพาะคนที่เคยอนุญาตตำแหน่ง/เลือกจังหวัดไว้ — หน้าแรกไม่ขอสิทธิ์เอง)
 * - ร้านที่ติดตาม · เมนูยอดนิยมในตลาดสด
 * - ไม่มีเมนู MLM / คริปโต / ดูคลิปได้เงิน (นโยบาย Google Play)
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { FlatList, Pressable, RefreshControl, ScrollView, StatusBar, StyleSheet, View, useWindowDimensions } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useFocusEffect } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '@/components/ui/Text';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO, isFeatureEnabled } from '@/config/appConfig';
import { getWallet } from '@/services/api';
import { getAddresses } from '@/services/api/shopApi';
import { checkIsSeller } from '@/services/api/merchantApi';
import { checkIsFreshMarketSeller } from '@/services/api/taladsodSellerApi';
import {
  getFollowedShops,
  getListings,
  getNearbyShops,
  type FmFollowedShop,
  type FmListingSummary,
  type FmNearbyShop,
} from '@/services/api/taladsodApi';
import NotificationBell from '@/components/NotificationBell';
import { ErrorBoundary } from '@/components';
import {
  BannerSlider,
  BrandArt,
  Button3D,
  Card3D,
  Icon,
  OnHeaderProvider,
  RoyalHeader,
  SectionHeader,
  formatBaht,
  tapHaptic,
  usePressGuard,
  type BrandArtName,
  type IconName,
} from '@/components/ui';
import {
  FollowedShopBubble,
  ListingCard,
  NearbyShopCard,
  PROVINCES,
  PROVINCE_SEARCH_RADIUS_KM,
  useBuyerLocation,
} from '@/components/taladsod';
import { useTheme, spacing, radii, typography, shadowStyle } from '@/theme';
import { getAvatarUrl, getAvatarInitial } from '@/utils/user';
import { KANOK_CREST_CLEARANCE } from '@/components/ui/KanokTabBar';

/** คีย์เดียวกับหน้าตลาดสด (พื้นที่ที่ผู้ใช้เลือกไว้) */
const TALADSOD_AREA_KEY = '@thaiprompt/taladsod_area_v1';
const NEARBY_RADIUS_KM = 10;

interface ServiceTile {
  id: string;
  title: string;
  art: BrandArtName;
  route: string;
  requiresLogin: boolean;
}

interface QuickLink {
  id: string;
  title: string;
  icon: IconName;
  route: string;
}

const getGreeting = (): string => {
  const hour = new Date().getHours();
  if (hour < 12) return 'สวัสดีตอนเช้า';
  if (hour < 17) return 'สวัสดีตอนบ่าย';
  return 'สวัสดีตอนเย็น';
};

/** ป้ายสั้นของที่อยู่จัดส่ง เช่น "เมืองเชียงใหม่ · เชียงใหม่" */
const shortAddress = (a: { district: string | null; province: string }): string =>
  [a.district, a.province].filter(Boolean).join(' · ');

// =====================================================
// ชิ้นส่วนย่อย
// =====================================================

/** กระเบื้องบริการ: ภาพ 3D ลอยพ้นขอบการ์ดขาว */
const ServiceTileView = ({ tile, onPress }: { tile: ServiceTile; onPress: () => void }) => {
  const { colors } = useTheme();
  const { run } = usePressGuard(onPress);
  return (
    <Pressable
      onPress={run}
      accessibilityRole="button"
      accessibilityLabel={tile.title}
      style={({ pressed }) => [styles.tile, { transform: [{ scale: pressed ? 0.95 : 1 }] }]}
    >
      <View style={[styles.tileCard, { backgroundColor: colors.card }, shadowStyle('md', colors.shadowDark)]}>
        <BrandArt name={tile.art} size={74} style={styles.tileArt} />
      </View>
      <Text numberOfLines={1} style={[styles.tileLabel, { color: colors.textStrong }]}>
        {tile.title}
      </Text>
    </Pressable>
  );
};

/** ปุ่มวงกลมในการ์ดกระเป๋าเงิน (บนหัวน้ำเงิน) */
const WalletAction = ({ icon, label, onPress }: { icon: IconName; label: string; onPress: () => void }) => {
  const { colors } = useTheme();
  const { run } = usePressGuard(() => {
    tapHaptic();
    onPress();
  });
  return (
    <Pressable
      onPress={run}
      accessibilityRole="button"
      accessibilityLabel={label}
      style={({ pressed }) => [styles.walletAction, { opacity: pressed ? 0.7 : 1 }]}
    >
      <View style={styles.walletActionIcon}>
        <Icon name={icon} size={21} color={colors.goldLight} />
      </View>
      <Text style={[styles.walletActionText, { color: colors.onHeader }]}>{label}</Text>
    </Pressable>
  );
};

// =====================================================
// หน้าจอ
// =====================================================

export default function HomeScreen() {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const { width } = useWindowDimensions();
  const user = useAuthStore((state) => state.user);
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const location = useBuyerLocation();

  const [greeting, setGreeting] = useState(getGreeting());
  const [balance, setBalance] = useState<number | string | null>(null);
  const [walletState, setWalletState] = useState<'idle' | 'loading' | 'ready' | 'error'>('idle');
  const [balanceHidden, setBalanceHidden] = useState(false);
  const [isSeller, setIsSeller] = useState(false);
  /** มีแค่ร้านตลาดสด → ทางเข้า "ร้านของฉัน" พาไปหน้าร้านตลาดสดเลย */
  const [merchantRoute, setMerchantRoute] = useState('/merchant');
  const [addressLabel, setAddressLabel] = useState<string | null>(null);
  const [nearby, setNearby] = useState<FmNearbyShop[]>([]);
  const [nearbyRadius, setNearbyRadius] = useState(NEARBY_RADIUS_KM);
  const [areaKnown, setAreaKnown] = useState<boolean | null>(null);
  const [followed, setFollowed] = useState<FmFollowedShop[]>([]);
  const [popular, setPopular] = useState<FmListingSummary[]>([]);
  const [refreshing, setRefreshing] = useState(false);
  const [bannerKey, setBannerKey] = useState(0);
  const mountedRef = useRef(true);

  const taladsodOn = isFeatureEnabled('TALADSOD_ENABLED');

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // ---------- ข้อมูลบัญชี ----------
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
      const [seller, fmSeller] = await Promise.all([
        checkIsSeller(requestedFor, force),
        checkIsFreshMarketSeller(requestedFor, force),
      ]);
      if (mountedRef.current && useAuthStore.getState().user?.id === requestedFor) {
        setIsSeller(seller || fmSeller);
        setMerchantRoute(!seller && fmSeller ? '/merchant/taladsod' : '/merchant');
      }
    },
    [isAuthenticated, user?.id]
  );

  const loadAddress = useCallback(async () => {
    if (!isAuthenticated) {
      setAddressLabel(null);
      return;
    }
    const requestedFor = useAuthStore.getState().user?.id ?? null;
    const res = await getAddresses();
    if (!mountedRef.current || (useAuthStore.getState().user?.id ?? null) !== requestedFor) return;
    if (res.success && res.data.length > 0) {
      const main = res.data.find((a) => a.is_default) || res.data[0];
      setAddressLabel(shortAddress(main));
    } else if (res.success) {
      setAddressLabel(null);
    }
  }, [isAuthenticated]);

  // ---------- ตลาดสด ----------
  /** พื้นที่ค้นหาร้านโดยไม่ถามสิทธิ์: จังหวัดที่เลือกไว้ในหน้าตลาดสด → GPS ถ้าเคยอนุญาตแล้ว */
  const resolveArea = useCallback(async (): Promise<{ lat: number; lng: number; radius: number } | null> => {
    try {
      const raw = await AsyncStorage.getItem(TALADSOD_AREA_KEY);
      const saved = raw ? (JSON.parse(raw) as { kind?: string; name?: string }) : null;
      if (saved?.kind === 'province') {
        const p = PROVINCES.find((x) => x.name === saved.name);
        if (p) return { lat: p.latitude, lng: p.longitude, radius: PROVINCE_SEARCH_RADIUS_KM };
      }
    } catch {
      // อ่านไม่ได้ก็ไปลอง GPS ต่อ
    }
    const coords = await location.peek();
    return coords ? { lat: coords.latitude, lng: coords.longitude, radius: NEARBY_RADIUS_KM } : null;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const loadNearby = useCallback(async () => {
    if (!taladsodOn) return;
    const area = await resolveArea();
    if (!mountedRef.current) return;
    setAreaKnown(!!area);
    if (!area) {
      setNearby([]);
      return;
    }
    const res = await getNearbyShops(area.lat, area.lng, area.radius);
    if (!mountedRef.current) return;
    if (res.success) {
      setNearby(res.data.shops.filter((s) => s.is_open).slice(0, 10));
      setNearbyRadius(area.radius);
    }
  }, [resolveArea, taladsodOn]);

  const loadFollowed = useCallback(async () => {
    if (!taladsodOn || !isAuthenticated) {
      setFollowed([]);
      return;
    }
    const res = await getFollowedShops(1, 12);
    if (mountedRef.current && res.success) setFollowed(res.data.shops);
  }, [isAuthenticated, taladsodOn]);

  const loadPopular = useCallback(async () => {
    if (!taladsodOn) return;
    const res = await getListings({ sort: 'popular', page: 1, per_page: 6 });
    if (!mountedRef.current || !res.success) return;
    const sorted = [...res.data.listings].sort((a, b) => Number(b.is_featured) - Number(a.is_featured));
    setPopular(sorted.slice(0, 4));
  }, [taladsodOn]);

  useEffect(() => {
    if (!isAuthenticated) {
      setBalance(null);
      setWalletState('idle');
      setIsSeller(false);
      setAddressLabel(null);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    loadNearby();
    loadPopular();
  }, [loadNearby, loadPopular]);

  useFocusEffect(
    useCallback(() => {
      setGreeting(getGreeting());
      loadWallet();
      loadSeller();
      loadAddress();
      loadFollowed();
    }, [loadWallet, loadSeller, loadAddress, loadFollowed])
  );

  const onRefresh = async () => {
    setRefreshing(true);
    setBannerKey((k) => k + 1);
    await Promise.all([loadWallet(), loadSeller(true), loadAddress(), loadNearby(), loadFollowed(), loadPopular()]);
    if (mountedRef.current) setRefreshing(false);
  };

  // ---------- บริการ ----------
  const tiles = useMemo(
    () =>
      [
        taladsodOn && { id: 'taladsod', title: 'ตลาดสด', art: 'basket', route: '/taladsod', requiresLogin: false },
        isFeatureEnabled('SHOPPING_ENABLED') && { id: 'shop', title: 'ช้อปปิ้ง', art: 'bag', route: '/(tabs)/shop', requiresLogin: false },
        isFeatureEnabled('RIDER_ENABLED') && { id: 'rider', title: 'ไรเดอร์', art: 'scooter', route: '/rider', requiresLogin: true },
        isFeatureEnabled('MERCHANT_ENABLED') && isSeller
          ? { id: 'merchant', title: 'ร้านของฉัน', art: 'store', route: merchantRoute, requiresLogin: true }
          : { id: 'stores', title: 'ร้านค้า', art: 'store', route: '/stores', requiresLogin: false },
        // ไทล์นี้หาตำแหน่งให้ทันทีแล้วโชว์ร้าน/รถเข็นที่เปิดอยู่ใกล้ๆ (ต่างจากไทล์ตลาดสดที่เปิดหน้ารวม)
        taladsodOn && { id: 'carts', title: 'รถเข็นใกล้ฉัน', art: 'cart', route: '/taladsod?nearby=1', requiresLogin: false },
        isFeatureEnabled('WALLET_ENABLED') && { id: 'wallet', title: 'กระเป๋าเงิน', art: 'wallet', route: '/(tabs)/wallet', requiresLogin: true },
        isFeatureEnabled('REFERRAL_ENABLED') && { id: 'referral', title: 'ชวนเพื่อน', art: 'gift', route: '/referral', requiresLogin: true },
        isFeatureEnabled('TAROT_ENABLED') && { id: 'tarot', title: 'ดูดวงฟรี', art: 'tarot', route: '/tarot', requiresLogin: false },
      ].filter(Boolean) as ServiceTile[],
    [isSeller, merchantRoute, taladsodOn]
  );

  const quickLinks: QuickLink[] = [
    { id: 'support', title: 'ช่วยเหลือ', icon: 'headset', route: '/support' },
    { id: 'wiki', title: 'คู่มือ', icon: 'book-open', route: '/wiki' },
    { id: 'settings', title: 'ตั้งค่า', icon: 'gear-six', route: '/settings' },
  ];

  const go = (route: string, requiresLogin: boolean) => {
    tapHaptic();
    if (requiresLogin && !isAuthenticated) {
      router.push('/login');
      return;
    }
    router.push(route as never);
  };

  // ---------- ผู้ใช้ ----------
  let avatarUrl: string | null = null;
  let initial = 'U';
  try {
    avatarUrl = getAvatarUrl(user?.avatar);
    initial = getAvatarInitial(user?.name);
  } catch {
    avatarUrl = null;
  }
  const nameParts = (user?.name || '').trim().split(/\s+/).filter(Boolean);
  const firstName = nameParts[0] || 'ผู้ใช้';
  const restName = nameParts.slice(1).join(' ');

  const walletP2P = isFeatureEnabled('P2P_TRANSFER_ENABLED');
  const gridGap = spacing.md;
  const cardWidth = Math.floor((width - spacing.screen * 2 - gridGap) / 2);
  const shopCardWidth = Math.min(280, Math.max(236, width * 0.66));

  // ---------- การ์ดกระเป๋าเงิน / เข้าสู่ระบบ (บนหัวน้ำเงิน) ----------
  const renderWalletCard = () => {
    if (!isAuthenticated) {
      return (
        <LinearGradient colors={['rgba(255,255,255,0.11)', 'rgba(255,255,255,0.03)']} style={styles.glassCard}>
          <Text style={[typography.serif, { color: colors.onHeader }]}>เข้าสู่ระบบเพื่อเริ่มสั่งของ</Text>
          <Text style={[typography.bodySm, styles.loginHint, { color: colors.onHeaderMuted }]}>
            ติดตามออเดอร์ จ่ายด้วยกระเป๋าเงิน และสมัครเป็นไรเดอร์ได้ในแอปเดียว
          </Text>
          <OnHeaderProvider value>
            <View style={styles.loginActions}>
              <Button3D title="เข้าสู่ระบบ" icon="sign-in" onPress={() => router.push('/login')} style={styles.flex} />
              <Button3D title="สมัครสมาชิก" variant="secondary" onPress={() => router.push('/register')} style={styles.flex} />
            </View>
          </OnHeaderProvider>
        </LinearGradient>
      );
    }
    if (!isFeatureEnabled('WALLET_ENABLED')) return null;

    return (
      <LinearGradient
        colors={['rgba(255,255,255,0.12)', 'rgba(255,255,255,0.035)']}
        start={{ x: 0, y: 0 }}
        end={{ x: 0.8, y: 1 }}
        style={styles.glassCard}
      >
        <View style={styles.walletTop}>
          <View style={styles.walletBrand}>
            <Icon name="wallet" size={16} color={colors.gold} weight="fill" />
            <Text style={[styles.walletBrandText, { color: colors.goldLight }]}>TP Wallet</Text>
          </View>
          <Pressable
            onPress={() => {
              tapHaptic();
              setBalanceHidden((v) => !v);
            }}
            hitSlop={10}
            accessibilityRole="button"
            accessibilityLabel={balanceHidden ? 'แสดงยอดเงิน' : 'ซ่อนยอดเงิน'}
            style={styles.eye}
          >
            <Icon name={balanceHidden ? 'eye-slash' : 'eye'} size={16} color={colors.onHeaderMuted} />
            <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>ยอดพร้อมใช้</Text>
          </Pressable>
        </View>

        {walletState === 'error' ? (
          <Pressable onPress={loadWallet} accessibilityRole="button" style={styles.walletError}>
            <Icon name="arrows-clockwise" size={16} color={colors.goldLight} />
            <Text style={[typography.bodySm, { color: colors.goldLight }]}>โหลดยอดไม่สำเร็จ แตะเพื่อลองใหม่</Text>
          </Pressable>
        ) : walletState === 'ready' ? (
          <Text
            style={[typography.moneyLg, styles.balance, { color: colors.goldLight }]}
            accessibilityLabel={balanceHidden ? 'ยอดเงินถูกซ่อนอยู่' : `ยอดเงิน ${formatBaht(balance, { decimals: 2 })}`}
          >
            {balanceHidden ? '฿ • • • • •' : formatBaht(balance, { decimals: 2 })}
          </Text>
        ) : (
          <View style={styles.balanceSkeleton} />
        )}

        <View style={styles.walletActions}>
          <WalletAction icon="plus" label="เติมเงิน" onPress={() => router.push('/wallet-topup')} />
          {walletP2P ? (
            <WalletAction icon="paper-plane-tilt" label="โอนเงิน" onPress={() => router.push('/wallet-transfer' as never)} />
          ) : (
            <WalletAction icon="bank" label="ถอนเงิน" onPress={() => router.push('/wallet-withdraw')} />
          )}
          <WalletAction icon="clock-counter-clockwise" label="ประวัติ" onPress={() => router.push('/wallet-history')} />
          <WalletAction icon="squares-four" label="ทั้งหมด" onPress={() => router.push('/(tabs)/wallet' as never)} />
        </View>
      </LinearGradient>
    );
  };

  return (
    <ErrorBoundary>
      <View style={[styles.root, { backgroundColor: colors.background }]}>
        <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />

        <ScrollView
          // ยอดซุ้มกนกของแถบล่างยื่นขึ้นมาทับท้ายรายการ — เผื่อที่ให้เลื่อนพ้นซุ้ม
          contentContainerStyle={{ paddingBottom: spacing.xxxl + KANOK_CREST_CLEARANCE }}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              tintColor={colors.gold}
              colors={[colors.gold]}
              progressBackgroundColor={colors.card}
              progressViewOffset={insets.top}
            />
          }
        >
          {/* ---------- หัวน้ำเงินกรมท่า ---------- */}
          <RoyalHeader ornamentTop={insets.top - 16} ornamentWidth={260} style={{ paddingTop: insets.top + spacing.xs, paddingBottom: 58 }}>
            <OnHeaderProvider value>
              <View style={styles.topRow}>
                {isAuthenticated ? (
                  <Pressable
                    onPress={() => {
                      tapHaptic();
                      router.push('/addresses' as never);
                    }}
                    accessibilityRole="button"
                    accessibilityLabel={addressLabel ? `ที่อยู่จัดส่ง ${addressLabel}` : 'เพิ่มที่อยู่จัดส่ง'}
                    style={({ pressed }) => [
                      styles.locationPill,
                      { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder, opacity: pressed ? 0.75 : 1 },
                    ]}
                  >
                    <Icon name="map-pin" size={18} color={colors.gold} weight="fill" />
                    <View style={styles.flexShrink}>
                      <Text style={[typography.micro, { color: colors.onHeaderMuted }]}>ส่งที่</Text>
                      <Text numberOfLines={1} style={[styles.locationText, { color: colors.onHeader }]}>
                        {addressLabel || 'เพิ่มที่อยู่จัดส่ง'}
                      </Text>
                    </View>
                    <Icon name="caret-down" size={14} color={colors.onHeaderMuted} weight="bold" />
                  </Pressable>
                ) : (
                  <View style={styles.brandRow}>
                    <Image source={require('@/assets/images/icon.png')} style={styles.brandIcon} contentFit="cover" />
                    <Text style={[typography.serifSm, { color: colors.goldLight }]}>{APP_INFO.NAME}</Text>
                  </View>
                )}
                <View style={styles.flex} />
                {isAuthenticated && <NotificationBell size={42} />}
                <Pressable
                  onPress={() => (isAuthenticated ? router.push('/(tabs)/profile' as never) : router.push('/login'))}
                  accessibilityRole="button"
                  accessibilityLabel={isAuthenticated ? 'โปรไฟล์' : 'เข้าสู่ระบบ'}
                  style={({ pressed }) => [{ opacity: pressed ? 0.8 : 1 }]}
                >
                  <LinearGradient colors={['#F6E3A6', '#C9973A', '#F6E3A6']} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.avatarRing}>
                    <View style={[styles.avatarInner, { backgroundColor: '#10223F' }]}>
                      {avatarUrl ? (
                        <Image source={{ uri: avatarUrl }} style={styles.avatarImg} contentFit="cover" />
                      ) : isAuthenticated ? (
                        <Text style={[typography.serifSm, { color: colors.goldLight }]}>{initial}</Text>
                      ) : (
                        <Icon name="user" size={20} color={colors.goldLight} />
                      )}
                    </View>
                  </LinearGradient>
                </Pressable>
              </View>
            </OnHeaderProvider>

            <View style={styles.greet}>
              <Text style={[typography.bodySm, { color: colors.onHeaderMuted }]}>{greeting}</Text>
              {isAuthenticated ? (
                <Text numberOfLines={1} style={[typography.serifLg, { color: colors.onHeader }]}>
                  คุณ<Text style={{ color: colors.goldLight }}>{firstName}</Text>
                  {restName ? ` ${restName}` : ''}
                </Text>
              ) : (
                <Text style={[typography.serifLg, { color: colors.onHeader }]}>
                  ยินดีต้อนรับสู่ <Text style={{ color: colors.goldLight }}>ไทยพร้อม</Text>
                </Text>
              )}
            </View>

            <View style={styles.walletWrap}>{renderWalletCard()}</View>
          </RoyalHeader>

          {/* ---------- แผ่นเนื้อหางาช้าง ---------- */}
          <View style={[styles.sheet, { backgroundColor: colors.background }]}>
            {/* ค้นหา (ลอยคร่อมขอบหัว) */}
            <Pressable
              onPress={() => {
                tapHaptic();
                router.push('/(tabs)/shop' as never);
              }}
              accessibilityRole="search"
              accessibilityLabel="ค้นหาสินค้าและร้านค้า"
              style={({ pressed }) => [
                styles.search,
                { backgroundColor: colors.card, borderColor: colors.border, opacity: pressed ? 0.9 : 1 },
                shadowStyle('lg', colors.shadowDark),
              ]}
            >
              <Icon name="magnifying-glass" size={21} color={colors.navy} />
              <Text style={[typography.body, styles.flex, { color: colors.textFaint }]}>ค้นหาสินค้า ร้านค้า แบรนด์</Text>
              <View style={[styles.searchFilter, { backgroundColor: colors.inset }]}>
                <Icon name="sliders-horizontal" size={18} color={colors.navy} />
              </View>
            </Pressable>

            {/* ---------- บริการ ---------- */}
            <View style={styles.grid}>
              {tiles.map((tile) => (
                <ServiceTileView key={tile.id} tile={tile} onPress={() => go(tile.route, tile.requiresLogin)} />
              ))}
            </View>

            {/* ---------- แบนเนอร์ ---------- */}
            <View style={styles.bleed}>
              <BannerSlider placement="home" refreshKey={bannerKey} height={170} />
            </View>

            {/* ---------- เปิดอยู่ใกล้คุณ ---------- */}
            {taladsodOn && nearby.length > 0 && (
              <>
                <SectionHeader
                  title="เปิดอยู่ใกล้คุณ"
                  subtitle={`${nearby.length} ร้านในระยะ ${nearbyRadius} กม.`}
                  actionLabel="ดูทั้งหมด"
                  onAction={() => router.push('/taladsod' as never)}
                  style={styles.section}
                />
                <FlatList
                  horizontal
                  data={nearby}
                  keyExtractor={(s) => String(s.id)}
                  renderItem={({ item }) => <NearbyShopCard shop={item} width={shopCardWidth} />}
                  showsHorizontalScrollIndicator={false}
                  contentContainerStyle={styles.hList}
                  style={styles.bleed}
                  ItemSeparatorComponent={() => <View style={{ width: spacing.md }} />}
                />
              </>
            )}
            {taladsodOn && areaKnown === false && (
              <Card3D
                onPress={() => router.push('/taladsod' as never)}
                padding={spacing.lg}
                style={styles.section}
                accessibilityLabel="ดูร้านที่เปิดอยู่ใกล้คุณในตลาดสด"
              >
                <View style={styles.nearbyPrompt}>
                  <BrandArt name="cart" size={68} />
                  <View style={styles.flex}>
                    <Text style={[typography.h3, { color: colors.textStrong }]}>ร้านรถเข็นใกล้คุณเปิดอยู่ไหม?</Text>
                    <Text style={[typography.bodySm, { color: colors.textMuted }]}>
                      เลือกพื้นที่ในตลาดสด แล้วเราจะแสดงร้านที่เปิดอยู่ตอนนี้บนหน้าแรก
                    </Text>
                  </View>
                  <Icon name="caret-right" size={18} color={colors.goldDeep} weight="bold" />
                </View>
              </Card3D>
            )}

            {/* ---------- ร้านที่ติดตาม ---------- */}
            {followed.length > 0 && (
              <>
                <SectionHeader title="ร้านที่ติดตาม" subtitle="ร้านเปิดเมื่อไหร่เราจะแจ้งเตือน" style={styles.section} />
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.hListBubbles} style={styles.bleed}>
                  {followed.map((s) => (
                    <FollowedShopBubble key={s.id} shop={s} />
                  ))}
                </ScrollView>
              </>
            )}

            {/* ---------- เมนูยอดนิยม ---------- */}
            {popular.length > 0 && (
              <>
                <SectionHeader
                  title="เมนูยอดนิยมในตลาดสด"
                  actionLabel="ดูทั้งหมด"
                  onAction={() => router.push('/taladsod' as never)}
                  style={styles.section}
                />
                <View style={[styles.menuGrid, { gap: gridGap }]}>
                  {popular.map((l) => (
                    <ListingCard key={l.id} listing={l} width={cardWidth} />
                  ))}
                </View>
              </>
            )}

            {/* ---------- ทางลัด ---------- */}
            <View style={[styles.quickRow, styles.section]}>
              {quickLinks.map((q) => (
                <Pressable
                  key={q.id}
                  onPress={() => go(q.route, false)}
                  accessibilityRole="button"
                  accessibilityLabel={q.title}
                  style={({ pressed }) => [
                    styles.quick,
                    { backgroundColor: colors.card, borderColor: colors.border, opacity: pressed ? 0.8 : 1 },
                  ]}
                >
                  <View style={[styles.quickIcon, { backgroundColor: colors.navySoft }]}>
                    <Icon name={q.icon} size={19} color={colors.navy} />
                  </View>
                  <Text style={[typography.caption, { color: colors.textStrong, fontWeight: '600' }]}>{q.title}</Text>
                </Pressable>
              ))}
            </View>

            <Text style={[typography.micro, styles.version, { color: colors.textFaint }]}>
              {APP_INFO.NAME} v{APP_INFO.VERSION}
            </Text>
          </View>
        </ScrollView>

        {location.element}
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
  flexShrink: {
    flexShrink: 1,
  },
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm + 2,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
  },
  locationPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    height: 44,
    maxWidth: '64%',
    paddingLeft: 10,
    paddingRight: 12,
    borderRadius: 15,
    borderWidth: 1,
  },
  locationText: {
    fontSize: 13.5,
    lineHeight: 18,
    fontWeight: '600',
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  brandIcon: {
    width: 34,
    height: 34,
    borderRadius: 10,
  },
  avatarRing: {
    width: 44,
    height: 44,
    borderRadius: 22,
    padding: 2,
  },
  avatarInner: {
    flex: 1,
    borderRadius: 20,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarImg: {
    width: 40,
    height: 40,
  },
  greet: {
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.xl,
  },
  walletWrap: {
    paddingHorizontal: spacing.screen,
    marginTop: spacing.lg,
  },
  glassCard: {
    borderRadius: 24,
    padding: spacing.lg + 2,
    borderWidth: 1,
    borderColor: 'rgba(228,192,107,0.30)',
    overflow: 'hidden',
  },
  walletTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  walletBrand: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
  },
  walletBrandText: {
    fontSize: 13,
    fontWeight: '600',
    letterSpacing: 0.3,
  },
  eye: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  balance: {
    marginTop: spacing.sm,
  },
  balanceSkeleton: {
    height: 40,
    width: '60%',
    borderRadius: radii.sm,
    marginTop: spacing.sm,
    backgroundColor: 'rgba(255,255,255,0.10)',
  },
  walletError: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    paddingVertical: spacing.sm,
  },
  walletActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: spacing.lg,
    paddingTop: spacing.md + 2,
    borderTopWidth: 1,
    borderTopColor: 'rgba(255,255,255,0.08)',
  },
  walletAction: {
    flex: 1,
    alignItems: 'center',
    gap: 7,
  },
  walletActionIcon: {
    width: 46,
    height: 46,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(228,192,107,0.14)',
    borderWidth: 1,
    borderColor: 'rgba(228,192,107,0.30)',
  },
  walletActionText: {
    fontSize: 12.5,
    fontWeight: '500',
  },
  loginHint: {
    marginTop: spacing.xs,
  },
  loginActions: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.lg,
  },
  sheet: {
    marginTop: -30,
    borderTopLeftRadius: 30,
    borderTopRightRadius: 30,
    paddingHorizontal: spacing.screen,
  },
  search: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    height: 56,
    marginTop: -26,
    paddingLeft: spacing.lg + 2,
    paddingRight: spacing.sm,
    borderRadius: 18,
    borderWidth: 1,
  },
  searchFilter: {
    width: 40,
    height: 40,
    borderRadius: 13,
    alignItems: 'center',
    justifyContent: 'center',
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    rowGap: spacing.lg + 2,
    marginTop: spacing.xxl + 6,
    marginBottom: spacing.lg,
  },
  tile: {
    width: '23%',
    alignItems: 'center',
    gap: spacing.sm,
  },
  tileCard: {
    width: '100%',
    height: 70,
    borderRadius: 22,
    alignItems: 'center',
  },
  tileArt: {
    marginTop: -14,
  },
  tileLabel: {
    fontSize: 12.5,
    lineHeight: 17,
    fontWeight: '600',
  },
  bleed: {
    marginHorizontal: -spacing.screen,
  },
  section: {
    marginTop: spacing.xl,
  },
  hList: {
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.lg,
    paddingTop: spacing.xs,
  },
  hListBubbles: {
    paddingHorizontal: spacing.screen,
    gap: spacing.sm,
    paddingVertical: spacing.xs,
  },
  nearbyPrompt: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  menuGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  quickRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  quick: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    padding: spacing.sm + 2,
    borderRadius: 18,
    borderWidth: 1,
  },
  quickIcon: {
    width: 34,
    height: 34,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  version: {
    textAlign: 'center',
    marginTop: spacing.xl,
  },
});
