/**
 * หน้าร้านตลาดสด — GET /fresh-market/shops/{id}
 *
 * - หัวร้าน: รูป ชื่อ คะแนน ผู้ติดตาม · เปิด/ปิด · รถเข็น · ป้ายตำแหน่ง · เวลาปิด · ระยะจากคุณ
 * - ร้านเปิด + มีตำแหน่ง → แผนที่สด (poll GET /shops/{id}/location ทุก 15 วินาทีเฉพาะตอนเปิดหน้านี้)
 *   ร้านปิดระหว่างดู → หยุด poll + แสดงปุ่มติดตามร้าน
 * - ติดตาม/เลิกติดตาม: เปลี่ยนทันที (optimistic) + สั่นเบา · ล้มเหลว = คืนค่าเดิม
 * - เมนูของร้าน (กริด 2 คอลัมน์)
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, Text, View, useWindowDimensions } from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  Button3D,
  Card3D,
  EmptyState,
  LiveMap,
  Pill,
  Screen,
  SectionHeader,
  resultHaptic,
  type LiveMapMarker,
} from '@/components/ui';
import {
  ListingCard,
  ShopAvatar,
  ShopStatusRow,
  TaladsodCartButton,
  distanceText,
  formatThaiTime,
  getCachedBuyerCoords,
  timeAgoText,
  useBuyerLocation,
  useFocusedInterval,
  useMountedRef,
} from '@/components/taladsod';
import {
  followShop,
  getShop,
  getShopLocation,
  unfollowShop,
  type FmShopDetail,
  type FmShopLocation,
} from '@/services/api/taladsodApi';
import type { Coords } from '@/services/location';
import { useTheme, spacing, typography } from '@/theme';

const SHOP_POLL_MS = 15000;

export default function TaladsodShopScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const shopId = /^\d+$/.test(String(id || '')) ? Number(id) : 0;
  const { colors } = useTheme();
  const { width } = useWindowDimensions();
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
          icon={error?.notFound ? '🏪' : undefined}
          title={error?.notFound ? 'ไม่พบร้านนี้' : undefined}
          message={error?.notFound ? 'ร้านอาจปิดตัวหรือหยุดขายชั่วคราว' : error?.message}
          actionLabel={error?.notFound ? 'กลับไปตลาดสด' : 'ลองใหม่'}
          onAction={error?.notFound ? () => router.replace('/taladsod' as never) : () => load('initial')}
        />
      </Screen>
    );
  }

  const cardWidth = Math.floor((width - spacing.screen * 2 - spacing.md) / 2);
  const locationLabel = liveLocation?.label || shop.presence.location_label || (shop.is_mobile ? null : shop.address);
  const distance = distanceText(myCoords, liveLocation);
  const closeTime = isOpen ? formatThaiTime(closesAt) : '';
  const liveCaption = liveLocation
    ? liveLocation.is_live
      ? `📡 ร้านแชร์ตำแหน่งสด · อัปเดต${timeAgoText(liveLocation.updated_at) || 'ล่าสุด'}`
      : shop.is_mobile
        ? `📌 ตำแหน่งที่ร้านปักไว้วันนี้${liveLocation.updated_at ? ` · ${timeAgoText(liveLocation.updated_at)}` : ''}`
        : '📌 ที่ตั้งร้าน'
    : null;

  return (
    <Screen
      title={shop.shop_name}
      subtitle={isOpen ? 'เปิดอยู่ สั่งได้เลย' : 'ปิดอยู่ตอนนี้'}
      refreshing={refreshing}
      onRefresh={() => load('refresh')}
      right={<TaladsodCartButton />}
    >
      {location.element}

      {/* ---------- หัวร้าน ---------- */}
      <Card3D gradientBorder padding={spacing.lg} style={styles.block}>
        <View style={styles.headRow}>
          <ShopAvatar image={shop.shop_image} isMobile={shop.is_mobile} size={64} isOpen={isOpen} />
          <View style={styles.flex}>
            <Text numberOfLines={2} style={[typography.h2, { color: colors.textStrong }]}>
              {shop.shop_name}
              {shop.is_verified ? ' ✅' : ''}
            </Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              {shop.rating_count > 0 ? `⭐ ${shop.rating_average.toFixed(1)} (${shop.rating_count}) · ` : ''}
              {followers > 0 ? `${followers} คนติดตาม` : 'ยังไม่มีผู้ติดตาม'}
              {shop.total_sales > 0 ? ` · ขายแล้ว ${shop.total_sales}` : ''}
            </Text>
          </View>
        </View>

        <ShopStatusRow isOpen={isOpen} isMobile={shop.is_mobile} size="md" style={styles.gapTop} />

        {isOpen && (
          <View style={styles.metaList}>
            {!!locationLabel && (
              <Text style={[typography.bodySm, { color: colors.text }]}>📍 {locationLabel}</Text>
            )}
            {!!distance && <Text style={[typography.bodySm, { color: colors.text }]}>🚶 ห่างจากคุณ ~{distance}</Text>}
            {!!closeTime && <Text style={[typography.bodySm, { color: colors.text }]}>🕒 เปิดถึง {closeTime}</Text>}
          </View>
        )}

        {!!shop.shop_description && (
          <Text style={[typography.bodySm, styles.gapTop, { color: colors.textMuted }]}>{shop.shop_description}</Text>
        )}

        {!shop.is_owner && (
          <Button3D
            title={following ? 'ติดตามแล้ว' : 'ติดตามร้าน'}
            icon={following ? '💛' : '🔔'}
            variant={following ? 'secondary' : 'primary'}
            size="md"
            fullWidth
            loading={followBusy}
            onPress={toggleFollow}
            accessibilityHint={following ? 'แตะเพื่อเลิกติดตาม' : 'รับแจ้งเตือนเมื่อร้านเปิด'}
            style={styles.gapTop}
          />
        )}
        {shop.is_owner && (
          <View style={[styles.ownerNote, { backgroundColor: colors.infoSoft }]}>
            <Text style={[typography.caption, { color: colors.info }]}>นี่คือร้านของคุณ (สั่งซื้อจากร้านตัวเองไม่ได้)</Text>
          </View>
        )}
      </Card3D>

      {/* ---------- ร้านปิด ---------- */}
      {!isOpen && (
        <Card3D variant="flat" padding={spacing.lg} style={styles.block}>
          <Text style={styles.closedIcon}>🌙</Text>
          <Text style={[typography.h3, styles.center, { color: colors.textStrong }]}>ร้านปิดอยู่ตอนนี้</Text>
          <Text style={[typography.bodySm, styles.center, { color: colors.textMuted }]}>
            {following
              ? 'คุณติดตามร้านนี้แล้ว ร้านเปิดเมื่อไหร่เราจะแจ้งเตือนทันที'
              : shop.closed_message || 'ติดตามร้านไว้ ร้านเปิดเมื่อไหร่เราจะแจ้งเตือนทันที'}
          </Text>
          {!following && !shop.is_owner && (
            <Button3D title="ติดตามร้านนี้" icon="🔔" size="md" onPress={toggleFollow} style={styles.centerButton} />
          )}
        </Card3D>
      )}

      {/* ---------- แผนที่ร้าน ---------- */}
      {isOpen && liveLocation && (
        <>
          <SectionHeader
            title={shop.is_mobile ? 'ร้านอยู่ตรงนี้ตอนนี้' : 'ที่ตั้งร้าน'}
            icon="🗺️"
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
        icon="🍛"
        subtitle={shop.listings.length > 0 ? `${shop.listings.length} เมนู` : undefined}
        style={styles.section}
      />
      {shop.listings.length === 0 ? (
        <EmptyState compact icon="🍽️" title="ร้านยังไม่ได้ลงเมนู" message="แวะกลับมาดูใหม่ภายหลังนะ" />
      ) : (
        <View style={styles.grid}>
          {shop.listings.map((l) => (
            <ListingCard key={l.id} listing={l} width={cardWidth} hideShop shopOpen={isOpen} />
          ))}
        </View>
      )}

      {!isOpen && shop.listings.length > 0 && (
        <Pill label="ดูเมนูได้ สั่งได้เมื่อร้านเปิด" tone="neutral" icon="ℹ️" style={styles.note} />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    textAlign: 'center',
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  block: {
    marginTop: spacing.sm,
  },
  headRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  gapTop: {
    marginTop: spacing.md,
  },
  metaList: {
    marginTop: spacing.md,
    gap: spacing.xs,
  },
  ownerNote: {
    marginTop: spacing.md,
    borderRadius: 12,
    padding: spacing.sm,
  },
  closedIcon: {
    fontSize: 34,
    textAlign: 'center',
    marginBottom: spacing.xs,
  },
  centerButton: {
    alignSelf: 'center',
    marginTop: spacing.md,
  },
  section: {
    marginTop: spacing.xl,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
  },
  note: {
    alignSelf: 'center',
    marginTop: spacing.lg,
  },
});
