/**
 * หน้าร้านตลาดสด — GET /fresh-market/shops/{id} (ธีมรอยัล ตามม็อกหน้าร้านที่เจ้าของอนุมัติ)
 *
 * - รูปใหญ่ด้านบน (รูปร้าน → รูปเมนูแรก → ภาพสำรอง) + ปุ่มกระจกย้อนกลับ/ตะกร้า
 * - การ์ดร้านซ้อนบนรูป: โลโก้ ชื่อร้าน (ฟอนต์มีเชิง) · ปุ่มติดตาม · เปิด/ปิด เวลาปิด ตำแหน่ง · คะแนน/ระยะ/ผู้ติดตาม
 * - ร้านเปิด + มีตำแหน่ง → แผนที่สด (poll GET /shops/{id}/location ทุก 15 วินาทีเฉพาะตอนเปิดหน้านี้)
 *   ร้านปิดระหว่างดู → หยุด poll + แสดงการ์ดร้านปิดพร้อมปุ่มติดตาม
 * - ติดตาม/เลิกติดตาม: เปลี่ยนทันที (optimistic) + สั่นเบา · ล้มเหลว = คืนค่าเดิม
 * - เมนูเป็นแถว (ชื่อ คำอธิบาย ราคา ซ้าย · รูป + ปุ่มบวกขวา) · แถบตะกร้าลอยท้ายจอ
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, RefreshControl, ScrollView, StatusBar, StyleSheet, View } from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useLocalSearchParams } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '@/components/ui/Text';
import { useAuthStore } from '@/stores/authStore';
import {
  Button3D,
  EmptyState,
  GlassIconButton,
  Icon,
  LiveMap,
  OnHeaderProvider,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  resultHaptic,
  tapHaptic,
  usePressGuard,
  type LiveMapMarker,
} from '@/components/ui';
import {
  ShopAvatar,
  TaladsodCartButton,
  distanceText,
  formatThaiTime,
  getCachedBuyerCoords,
  timeAgoText,
  useBuyerLocation,
  useFocusedInterval,
  useMountedRef,
} from '@/components/taladsod';
import { TaladsodCartBar } from '@/components/taladsod/TaladsodCartBar';
import {
  fmImageUri,
  followShop,
  getShop,
  getShopLocation,
  unfollowShop,
  type FmShopDetail,
  type FmShopListing,
  type FmShopLocation,
} from '@/services/api/taladsodApi';
import type { Coords } from '@/services/location';
import { useTheme, spacing, typography, shadowStyle } from '@/theme';

const SHOP_POLL_MS = 15000;
const HERO_HEIGHT = 290;
const FALLBACK_FOOD = require('@/assets/images/taladsod/krapao-hero.webp');

/** แถวเมนู: ข้อความซ้าย รูป + ปุ่มบวกขวา */
const MenuRow: React.FC<{ item: FmShopListing; shopOpen: boolean; last: boolean }> = ({ item, shopOpen, last }) => {
  const { colors, gradients } = useTheme();
  const uri = fmImageUri(item.main_image_url);
  const hasDiscount = item.compare_at_price !== null && item.compare_at_price > item.price;
  const soldOut = !item.can_order && shopOpen;
  const { run: openListing } = usePressGuard(() => {
    tapHaptic();
    router.push(`/taladsod/listing/${item.id}` as never);
  });

  return (
    <Pressable
      onPress={openListing}
      accessibilityRole="button"
      accessibilityLabel={`${item.title} ราคา ${item.price} บาท${soldOut ? ' หมดชั่วคราว' : ''}`}
      style={({ pressed }) => [
        styles.menuRow,
        !last && { borderBottomWidth: 1, borderBottomColor: colors.divider },
        { opacity: pressed ? 0.75 : 1 },
      ]}
    >
      <View style={styles.flex}>
        <Text numberOfLines={2} style={[typography.bodyStrong, { color: colors.textStrong }]}>
          {item.title}
        </Text>
        {!!item.description && (
          <Text numberOfLines={2} style={[typography.bodySm, styles.menuDesc, { color: colors.textMuted }]}>
            {item.description}
          </Text>
        )}
        <View style={styles.menuPriceRow}>
          <PriceText amount={item.price} size="md" tone="strong" suffix={item.unit ? `/${item.unit}` : undefined} />
          {hasDiscount && <PriceText amount={item.compare_at_price} size="xs" tone="muted" strike bold={false} />}
          {item.has_options && <Text style={[typography.caption, { color: colors.textFaint }]}>เริ่มต้น</Text>}
        </View>
        {soldOut && <Pill label="หมดชั่วคราว" tone="danger" style={styles.menuPill} />}
      </View>
      <View style={styles.menuPhotoWrap}>
        <Image
          source={uri ? { uri } : FALLBACK_FOOD}
          style={[styles.menuPhoto, { backgroundColor: colors.inset }, (!shopOpen || soldOut) && styles.dim]}
          contentFit="cover"
          transition={140}
          recyclingKey={`m-${item.id}`}
        />
        <LinearGradient colors={gradients.navy} style={[styles.menuAdd, { borderColor: colors.card }]}>
          <Icon name="plus" size={18} color={colors.goldLight} weight="bold" />
        </LinearGradient>
      </View>
    </Pressable>
  );
};

export default function TaladsodShopScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const shopId = /^\d+$/.test(String(id || '')) ? Number(id) : 0;
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const mountedRef = useMountedRef();
  const location = useBuyerLocation();

  const [shop, setShop] = useState<FmShopDetail | null>(null);
  const [liveLocation, setLiveLocation] = useState<FmShopLocation | null>(null);
  const [isOpen, setIsOpen] = useState(false);
  const [closesAt, setClosesAt] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<{ message: string; notFound: boolean } | null>(null);
  const [following, setFollowing] = useState(false);
  const [followers, setFollowers] = useState(0);
  const [followBusy, setFollowBusy] = useState(false);
  const [myCoords, setMyCoords] = useState<Coords | null>(getCachedBuyerCoords());

  const loadingRef = useRef(false);
  const followBusyRef = useRef(false);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'silent') => {
      if (!shopId) {
        setLoading(false);
        setError({ message: 'ไม่พบร้านนี้', notFound: true });
        return;
      }
      if (loadingRef.current) return;
      loadingRef.current = true;
      if (mode === 'initial') setLoading(true);
      if (mode === 'refresh') setRefreshing(true);
      const res = await getShop(shopId);
      loadingRef.current = false;
      if (!mountedRef.current) return;
      if (res.success) {
        setShop(res.data);
        setIsOpen(res.data.is_open);
        setLiveLocation(res.data.presence.location);
        setClosesAt(res.data.presence.closes_at);
        if (!followBusyRef.current) {
          setFollowing(res.data.is_following);
          setFollowers(res.data.followers_count);
        }
        setError(null);
      } else if (mode !== 'silent') {
        setError({ message: res.message, notFound: res.status === 404 });
      }
      setLoading(false);
      setRefreshing(false);
    },
    [shopId, mountedRef]
  );

  useEffect(() => {
    load('initial');
  }, [load]);

  // ตำแหน่งของฉัน (อ่านเงียบๆ เฉพาะเมื่อเคยให้สิทธิ์แล้ว — ไม่ถาม)
  useEffect(() => {
    let alive = true;
    location.peek().then((coords) => {
      if (alive && coords) setMyCoords(coords);
    });
    return () => {
      alive = false;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // ---------- ตำแหน่งร้านสด (เฉพาะตอนร้านเปิด + หน้าจอเปิดอยู่) ----------
  const pollLocation = useCallback(async () => {
    const res = await getShopLocation(shopId);
    if (!mountedRef.current || !res.success) return;
    setLiveLocation(res.data.location);
    setClosesAt(res.data.closes_at);
    if (res.data.is_open !== isOpen) {
      setIsOpen(res.data.is_open);
      // ร้านเพิ่งปิด/เพิ่งเปิด → โหลดหน้าร้านใหม่ให้ปุ่มสั่ง/เมนูถูกต้อง
      load('silent');
    }
  }, [shopId, isOpen, load, mountedRef]);

  useFocusedInterval(pollLocation, SHOP_POLL_MS, !!shop && isOpen && !shop.is_owner);

  // ---------- ติดตามร้าน ----------
  const toggleFollow = async () => {
    if (!shop || followBusyRef.current) return;
    if (!isAuthenticated) {
      router.push('/login');
      return;
    }
    const next = !following;
    followBusyRef.current = true;
    setFollowBusy(true);
    setFollowing(next);
    setFollowers((n) => Math.max(0, n + (next ? 1 : -1)));
    resultHaptic(next ? 'success' : 'warning');

    const res = next ? await followShop(shop.id) : await unfollowShop(shop.id);
    followBusyRef.current = false;
    if (!mountedRef.current) return;
    setFollowBusy(false);
    if (res.success) {
      setFollowing(res.data.is_following);
      setFollowers(res.data.followers_count);
    } else {
      setFollowing(!next);
      setFollowers((n) => Math.max(0, n + (next ? -1 : 1)));
      resultHaptic('error');
      Alert.alert(next ? 'ติดตามร้านไม่สำเร็จ' : 'เลิกติดตามไม่สำเร็จ', res.message);
    }
  };

  const markers = useMemo<LiveMapMarker[]>(() => {
    const out: LiveMapMarker[] = [];
    if (liveLocation) {
      out.push({
        id: 'shop',
        kind: 'shop',
        latitude: liveLocation.latitude,
        longitude: liveLocation.longitude,
        label: shop?.shop_name || 'ร้าน',
      });
    }
    if (myCoords && liveLocation) {
      out.push({ id: 'me', kind: 'me', latitude: myCoords.latitude, longitude: myCoords.longitude, label: 'คุณ' });
    }
    return out;
  }, [liveLocation, myCoords, shop?.shop_name]);

  const goBack = () => {
    if (router.canGoBack()) router.back();
    else router.replace('/taladsod' as never);
  };

  // ---------- render ----------
  if (loading && !shop) {
    return (
      <Screen title="ร้านตลาดสด" scroll={false}>
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </Screen>
    );
  }

  if (!shop) {
    return (
      <Screen title="ร้านตลาดสด" scroll={false}>
        <EmptyState
          variant={error?.notFound ? 'empty' : 'error'}
          art={error?.notFound ? 'store' : undefined}
          title={error?.notFound ? 'ไม่พบร้านนี้' : undefined}
          message={error?.notFound ? 'ร้านอาจปิดตัวหรือหยุดขายชั่วคราว' : error?.message}
          actionLabel={error?.notFound ? 'กลับไปตลาดสด' : 'ลองใหม่'}
          onAction={error?.notFound ? () => router.replace('/taladsod' as never) : () => load('initial')}
        />
      </Screen>
    );
  }

  const locationLabel = liveLocation?.label || shop.presence.location_label || (shop.is_mobile ? null : shop.address);
  const distance = distanceText(myCoords, liveLocation);
  const closeTime = isOpen ? formatThaiTime(closesAt) : '';
  const liveCaption = liveLocation
    ? liveLocation.is_live
      ? `ร้านแชร์ตำแหน่งสด · อัปเดต ${timeAgoText(liveLocation.updated_at) || 'ล่าสุด'}`
      : shop.is_mobile
        ? `ตำแหน่งที่ร้านปักไว้วันนี้${liveLocation.updated_at ? ` · ${timeAgoText(liveLocation.updated_at)}` : ''}`
        : 'ที่ตั้งร้าน'
    : null;
  const heroUri = fmImageUri(shop.shop_image) || fmImageUri(shop.listings[0]?.main_image_url ?? null);

  return (
    <View style={[styles.root, { backgroundColor: colors.background }]}>
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      {location.element}

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: 120 + insets.bottom }}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load('refresh')}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
            progressViewOffset={insets.top}
          />
        }
      >
        {/* ---------- รูปใหญ่ ---------- */}
        <View style={[styles.hero, { height: HERO_HEIGHT + insets.top }]}>
          <Image source={heroUri ? { uri: heroUri } : FALLBACK_FOOD} style={StyleSheet.absoluteFill} contentFit="cover" transition={180} />
          <LinearGradient
            colors={['rgba(0,0,0,0.5)', 'rgba(0,0,0,0)', 'rgba(0,0,0,0)', 'rgba(0,0,0,0.3)']}
            locations={[0, 0.35, 0.7, 1]}
            style={StyleSheet.absoluteFill}
            pointerEvents="none"
          />
          <OnHeaderProvider value>
            <View style={[styles.heroBar, { paddingTop: insets.top + spacing.sm }]}>
              <GlassIconButton icon="caret-left" weight="bold" accessibilityLabel="ย้อนกลับ" onPress={goBack} />
              <View style={styles.flex} />
              <TaladsodCartButton />
            </View>
          </OnHeaderProvider>
        </View>

        <View style={styles.body}>
          {/* ---------- การ์ดร้าน ---------- */}
          <View style={[styles.shopCard, { backgroundColor: colors.card, borderColor: colors.border }, shadowStyle('lg', colors.shadowDark)]}>
            <View style={styles.headRow}>
              <ShopAvatar image={shop.shop_image} isMobile={shop.is_mobile} size={60} isOpen={isOpen} />
              <View style={styles.flex}>
                <View style={styles.nameRow}>
                  <Text numberOfLines={2} style={[typography.serif, styles.flexShrink, { color: colors.textStrong }]}>
                    {shop.shop_name}
                  </Text>
                  {shop.is_verified && <Icon name="seal-check" size={20} color={colors.gold} weight="fill" accessibilityLabel="ร้านยืนยันแล้ว" />}
                </View>
                <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
                  {shop.is_mobile ? 'ร้านรถเข็น / ตลาดนัด' : 'ร้านในตลาดสด'}
                  {shop.total_sales > 0 ? ` · ขายแล้ว ${shop.total_sales.toLocaleString('th-TH')}` : ''}
                </Text>
              </View>
              {!shop.is_owner && (
                <Pressable
                  onPress={toggleFollow}
                  disabled={followBusy}
                  accessibilityRole="button"
                  accessibilityLabel={following ? 'ติดตามแล้ว' : 'ติดตามร้าน'}
                  accessibilityHint={following ? 'แตะเพื่อเลิกติดตาม' : 'รับแจ้งเตือนเมื่อร้านเปิด'}
                  style={({ pressed }) => [
                    styles.followBtn,
                    following
                      ? { backgroundColor: colors.goldSoft, borderColor: 'transparent' }
                      : { backgroundColor: 'transparent', borderColor: colors.navy },
                    { opacity: pressed || followBusy ? 0.7 : 1 },
                  ]}
                >
                  <Icon name={following ? 'heart' : 'plus'} size={15} color={following ? colors.goldDeep : colors.navy} weight={following ? 'fill' : 'bold'} />
                  <Text style={[styles.followText, { color: following ? colors.goldDeep : colors.navy }]}>
                    {following ? 'ติดตามแล้ว' : 'ติดตาม'}
                  </Text>
                </Pressable>
              )}
            </View>

            {/* สถานะเปิด/ปิด + เวลา + ตำแหน่ง */}
            <View style={styles.statusLine}>
              <View style={[styles.statusDot, { backgroundColor: isOpen ? colors.success : colors.textFaint }]} />
              <Text style={[typography.caption, { color: isOpen ? colors.success : colors.textMuted, fontWeight: '600' }]}>
                {isOpen ? 'เปิดอยู่' : 'ปิดอยู่'}
              </Text>
              {!!closeTime && <Text style={[typography.caption, { color: colors.textMuted }]}>· ถึง {closeTime}</Text>}
              {!!locationLabel && isOpen && (
                <Text numberOfLines={1} style={[typography.caption, styles.flexShrink, { color: colors.textMuted }]}>
                  · {locationLabel}
                </Text>
              )}
            </View>

            {!!shop.shop_description && (
              <Text style={[typography.bodySm, styles.desc, { color: colors.textMuted }]}>{shop.shop_description}</Text>
            )}

            <View style={[styles.stats, { borderTopColor: colors.divider }]}>
              <View style={styles.stat}>
                <View style={styles.statValue}>
                  <Icon name="star" size={15} color={colors.gold} weight="fill" />
                  <Text style={[typography.h3, { color: colors.textStrong }]}>
                    {shop.rating_count > 0 ? shop.rating_average.toFixed(1) : 'ใหม่'}
                  </Text>
                </View>
                <Text style={[typography.micro, { color: colors.textFaint }]}>
                  {shop.rating_count > 0 ? `${shop.rating_count.toLocaleString('th-TH')} รีวิว` : 'ยังไม่มีรีวิว'}
                </Text>
              </View>
              <View style={[styles.stat, styles.statMid, { borderColor: colors.divider }]}>
                <Text style={[typography.h3, { color: colors.textStrong }]}>{distance || '—'}</Text>
                <Text style={[typography.micro, { color: colors.textFaint }]}>จากคุณ</Text>
              </View>
              <View style={styles.stat}>
                <Text style={[typography.h3, { color: colors.textStrong }]}>{followers.toLocaleString('th-TH')}</Text>
                <Text style={[typography.micro, { color: colors.textFaint }]}>ผู้ติดตาม</Text>
              </View>
            </View>

            {shop.is_owner && (
              <View style={[styles.ownerNote, { backgroundColor: colors.infoSoft }]}>
                <Icon name="info" size={16} color={colors.info} />
                <Text style={[typography.caption, styles.flex, { color: colors.info }]}>นี่คือร้านของคุณ (สั่งซื้อจากร้านตัวเองไม่ได้)</Text>
              </View>
            )}
          </View>

          {/* ---------- ร้านปิด ---------- */}
          {!isOpen && (
            <View style={[styles.closedCard, { backgroundColor: colors.card, borderColor: colors.border }]}>
              <View style={[styles.closedIcon, { backgroundColor: colors.navySoft }]}>
                <Icon name="moon-stars" size={26} color={colors.navy} weight="fill" />
              </View>
              <View style={styles.flex}>
                <Text style={[typography.h3, { color: colors.textStrong }]}>ร้านปิดอยู่ตอนนี้</Text>
                <Text style={[typography.bodySm, { color: colors.textMuted }]}>
                  {following
                    ? 'คุณติดตามร้านนี้แล้ว ร้านเปิดเมื่อไหร่เราจะแจ้งเตือนทันที'
                    : shop.closed_message || 'ติดตามร้านไว้ ร้านเปิดเมื่อไหร่เราจะแจ้งเตือนทันที'}
                </Text>
                {!following && !shop.is_owner && (
                  <Button3D title="ติดตามร้านนี้" icon="bell" size="sm" onPress={toggleFollow} style={styles.closedBtn} />
                )}
              </View>
            </View>
          )}

          {/* ---------- แผนที่ร้าน ---------- */}
          {isOpen && liveLocation && (
            <>
              <SectionHeader
                title={shop.is_mobile ? 'ร้านอยู่ตรงนี้ตอนนี้' : 'ที่ตั้งร้าน'}
                subtitle={shop.is_mobile ? 'รถเข็นย้ายได้ แผนที่อัปเดตทุก 15 วินาที' : undefined}
                style={styles.section}
              />
              <LiveMap
                markers={markers}
                height={210}
                openTargetId="shop"
                caption={liveCaption}
                accessibilityLabel={`แผนที่ตำแหน่งร้าน ${shop.shop_name}${locationLabel ? ` ${locationLabel}` : ''}`}
              />
            </>
          )}

          {/* ---------- เมนู ---------- */}
          <SectionHeader
            title="เมนูของร้าน"
            subtitle={shop.listings.length > 0 ? `${shop.listings.length} เมนู${isOpen ? '' : ' · สั่งได้เมื่อร้านเปิด'}` : undefined}
            style={styles.section}
          />
          {shop.listings.length === 0 ? (
            <EmptyState compact art="basket" title="ร้านยังไม่ได้ลงเมนู" message="แวะกลับมาดูใหม่ภายหลังนะ" />
          ) : (
            <View style={[styles.menuCard, { backgroundColor: colors.card, borderColor: colors.border }, shadowStyle('md', colors.shadowDark)]}>
              {shop.listings.map((l, i) => (
                <MenuRow key={l.id} item={l} shopOpen={isOpen} last={i === shop.listings.length - 1} />
              ))}
            </View>
          )}
        </View>
      </ScrollView>

      <TaladsodCartBar />
    </View>
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
  loader: {
    marginTop: spacing.xxxl,
  },
  hero: {
    width: '100%',
    overflow: 'hidden',
  },
  heroBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.screen,
  },
  body: {
    paddingHorizontal: spacing.screen,
  },
  shopCard: {
    marginTop: -64,
    borderRadius: 26,
    borderWidth: 1,
    padding: spacing.lg + 2,
  },
  headRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  nameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  followBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    height: 36,
    paddingHorizontal: spacing.md,
    borderRadius: 12,
    borderWidth: 1.5,
  },
  followText: {
    fontSize: 13,
    fontWeight: '700',
  },
  statusLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: spacing.md,
  },
  statusDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  desc: {
    marginTop: spacing.sm,
  },
  stats: {
    flexDirection: 'row',
    marginTop: spacing.md + 2,
    paddingTop: spacing.md + 2,
    borderTopWidth: 1,
  },
  stat: {
    flex: 1,
    alignItems: 'center',
    gap: 1,
  },
  statMid: {
    borderLeftWidth: 1,
    borderRightWidth: 1,
  },
  statValue: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  ownerNote: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    borderRadius: 12,
    padding: spacing.sm + 2,
  },
  closedCard: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.md,
    padding: spacing.lg,
    borderRadius: 22,
    borderWidth: 1,
  },
  closedIcon: {
    width: 48,
    height: 48,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  closedBtn: {
    alignSelf: 'flex-start',
    marginTop: spacing.md,
  },
  section: {
    marginTop: spacing.xl,
  },
  menuCard: {
    borderRadius: 24,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
  },
  menuRow: {
    flexDirection: 'row',
    gap: spacing.md + 2,
    paddingVertical: spacing.md + 2,
  },
  menuDesc: {
    marginTop: 2,
  },
  menuPriceRow: {
    flexDirection: 'row',
    alignItems: 'baseline',
    gap: 6,
    marginTop: spacing.sm,
  },
  menuPill: {
    marginTop: spacing.xs,
  },
  menuPhotoWrap: {
    width: 104,
    height: 104,
  },
  menuPhoto: {
    width: 104,
    height: 104,
    borderRadius: 20,
  },
  dim: {
    opacity: 0.5,
  },
  menuAdd: {
    position: 'absolute',
    right: -6,
    bottom: -6,
    width: 36,
    height: 36,
    borderRadius: 12,
    borderWidth: 3,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
