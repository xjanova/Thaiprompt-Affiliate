/**
 * ตลาดสด — หน้าแรกฝั่งผู้ซื้อ
 *
 * - แบนเนอร์ (placement taladsod)
 * - "เปิดอยู่ใกล้คุณ": ร้าน/รถเข็นที่เปิดอยู่ตอนนี้จากตำแหน่งของคุณ (ขอสิทธิ์พร้อมคำอธิบายก่อนเสมอ)
 *   ไม่อนุญาตตำแหน่ง → เลือกจังหวัดแทน (ค้นในรัศมี 50 กม. จากตัวเมือง)
 * - ร้านที่ติดตาม (เปิดอยู่ขึ้นก่อน) · หมวดหมู่ · เมนูแนะนำ (กริด 2 คอลัมน์)
 * - ดึงลง = รีเฟรชทุกส่วน (ไม่บังจอ)
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, ScrollView, StyleSheet, Text, View, useWindowDimensions } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { BannerSlider, Button3D, Card3D, Chip, EmptyState, Screen, SectionHeader, tapHaptic } from '@/components/ui';
import { Field, FormSheet } from '@/components/shop';
import {
  FollowedShopBubble,
  HeaderIconButton,
  ListingCard,
  NearbyShopCard,
  PROVINCE_SEARCH_RADIUS_KM,
  PROVINCES,
  TaladsodCartButton,
  searchProvinces,
  useBuyerLocation,
  useMountedRef,
  type ProvinceCenter,
} from '@/components/taladsod';
import {
  getFollowedShops,
  getListings,
  getNearbyShops,
  getTaladsodCategories,
  type FmCategory,
  type FmFollowedShop,
  type FmListingSummary,
  type FmNearbyShop,
} from '@/services/api/taladsodApi';
import type { Coords } from '@/services/location';
import { useTheme, radii, spacing, typography } from '@/theme';

type Area = { kind: 'gps'; coords: Coords } | { kind: 'province'; province: ProvinceCenter };

const AREA_STORAGE_KEY = '@thaiprompt/taladsod_area_v1';
const GPS_RADIUS_KM = 10;
const GPS_WIDE_RADIUS_KM = 30;
const LISTINGS_PER_PAGE = 20;
const SHEET_SETTLE_MS = 420;

const saveArea = (area: Area | null) => {
  try {
    const value = !area ? null : area.kind === 'gps' ? { kind: 'gps' } : { kind: 'province', name: area.province.name };
    if (value) AsyncStorage.setItem(AREA_STORAGE_KEY, JSON.stringify(value)).catch(() => {});
    else AsyncStorage.removeItem(AREA_STORAGE_KEY).catch(() => {});
  } catch {
    // เก็บไม่ได้ก็ไม่เป็นไร (แค่ความสะดวก)
  }
};

export default function TaladsodHomeScreen() {
  const { colors } = useTheme();
  const { width } = useWindowDimensions();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const mountedRef = useMountedRef();
  const location = useBuyerLocation();

  const [area, setArea] = useState<Area | null>(null);
  const [areaReady, setAreaReady] = useState(false);
  const [nearby, setNearby] = useState<FmNearbyShop[]>([]);
  const [nearbyState, setNearbyState] = useState<'idle' | 'loading' | 'ready' | 'error'>('idle');
  const [nearbyError, setNearbyError] = useState<string | null>(null);
  const [nearbyRadius, setNearbyRadius] = useState<number>(GPS_RADIUS_KM);

  const [categories, setCategories] = useState<FmCategory[]>([]);
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [listings, setListings] = useState<FmListingSummary[]>([]);
  const [listingsPage, setListingsPage] = useState(1);
  const [listingsHasMore, setListingsHasMore] = useState(false);
  const [listingsLoading, setListingsLoading] = useState(true);
  const [listingsMore, setListingsMore] = useState(false);
  const [listingsError, setListingsError] = useState<string | null>(null);

  const [followed, setFollowed] = useState<FmFollowedShop[]>([]);
  const [refreshing, setRefreshing] = useState(false);
  const [bannerKey, setBannerKey] = useState(0);
  const [areaSheet, setAreaSheet] = useState(false);
  const [provinceQuery, setProvinceQuery] = useState('');

  const nearbyReqRef = useRef(0);
  const listingsReqRef = useRef(0);
  const lastFocusLoadRef = useRef(0);

  const gridGap = spacing.md;
  const cardWidth = Math.floor((width - spacing.screen * 2 - gridGap) / 2);
  const shopCardWidth = Math.min(300, Math.max(240, width * 0.74));

  // ---------- ร้านใกล้คุณ ----------
  const loadNearby = useCallback(
    async (target: Area | null) => {
      if (!target) return;
      const reqId = ++nearbyReqRef.current;
      setNearbyState((prev) => (prev === 'ready' ? prev : 'loading'));
      const lat = target.kind === 'gps' ? target.coords.latitude : target.province.latitude;
      const lng = target.kind === 'gps' ? target.coords.longitude : target.province.longitude;
      let radius = target.kind === 'gps' ? GPS_RADIUS_KM : PROVINCE_SEARCH_RADIUS_KM;
      let res = await getNearbyShops(lat, lng, radius);
      // ใกล้ๆ ยังไม่มีร้านเปิด → ขยายวงค้นหา
      if (res.success && res.data.shops.length === 0 && target.kind === 'gps') {
        radius = GPS_WIDE_RADIUS_KM;
        res = await getNearbyShops(lat, lng, radius);
      }
      if (!mountedRef.current || reqId !== nearbyReqRef.current) return;
      if (res.success) {
        setNearby(res.data.shops);
        setNearbyRadius(radius);
        setNearbyState('ready');
        setNearbyError(null);
      } else {
        setNearbyError(res.message);
        setNearbyState('error');
      }
    },
    [mountedRef]
  );

  useEffect(() => {
    if (area) loadNearby(area);
  }, [area, loadNearby]);

  // เปิดหน้า: ใช้พื้นที่ที่เลือกไว้ครั้งก่อน · เคยให้สิทธิ์ตำแหน่งแล้วใช้ GPS เงียบๆ (ไม่ถาม)
  useEffect(() => {
    let alive = true;
    (async () => {
      let saved: { kind?: string; name?: string } | null = null;
      try {
        const raw = await AsyncStorage.getItem(AREA_STORAGE_KEY);
        saved = raw ? JSON.parse(raw) : null;
      } catch {
        saved = null;
      }
      if (saved?.kind === 'province') {
        const province = PROVINCES.find((p) => p.name === saved?.name);
        if (province && alive) {
          setArea({ kind: 'province', province });
          setAreaReady(true);
          return;
        }
      }
      const coords = await location.peek();
      if (!alive || !mountedRef.current) return;
      if (coords) setArea({ kind: 'gps', coords });
      setAreaReady(true);
    })();
    return () => {
      alive = false;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const locateMe = async () => {
    const coords = await location.request('nearby');
    if (!coords || !mountedRef.current) return;
    const next: Area = { kind: 'gps', coords };
    setArea(next);
    saveArea(next);
  };

  const chooseProvince = (province: ProvinceCenter) => {
    tapHaptic();
    const next: Area = { kind: 'province', province };
    setAreaSheet(false);
    setProvinceQuery('');
    setArea(next);
    saveArea(next);
  };

  const locateFromSheet = () => {
    setAreaSheet(false);
    // รอ sheet ปิดสนิทก่อนเปิดคำอธิบายขอสิทธิ์ (iOS เปิด Modal ซ้อนไม่ได้)
    setTimeout(() => {
      if (mountedRef.current) locateMe();
    }, SHEET_SETTLE_MS);
  };

  // ---------- หมวดหมู่ + เมนู ----------
  const loadCategories = useCallback(async () => {
    const res = await getTaladsodCategories();
    if (mountedRef.current && res.success) setCategories(res.data.filter((c) => c.id > 0));
  }, [mountedRef]);

  const loadListings = useCallback(
    async (mode: 'initial' | 'refresh' | 'more', targetCategory: number | null, targetPage: number) => {
      const reqId = ++listingsReqRef.current;
      if (mode === 'initial') setListingsLoading(true);
      if (mode === 'more') setListingsMore(true);
      const res = await getListings({
        category_id: targetCategory ?? undefined,
        sort: 'popular',
        page: targetPage,
        per_page: LISTINGS_PER_PAGE,
      });
      if (!mountedRef.current || reqId !== listingsReqRef.current) return;
      if (res.success) {
        // เมนูแนะนำ (is_featured) ขึ้นก่อนในแต่ละหน้า
        const sorted = [...res.data.listings].sort((a, b) => Number(b.is_featured) - Number(a.is_featured));
        setListings((prev) => {
          if (mode !== 'more') return sorted;
          const seen = new Set(prev.map((l) => l.id));
          return [...prev, ...sorted.filter((l) => !seen.has(l.id))];
        });
        setListingsPage(targetPage);
        setListingsHasMore(!!res.data.pagination?.has_more);
        setListingsError(null);
      } else if (mode !== 'more') {
        setListingsError(res.message);
      }
      setListingsLoading(false);
      setListingsMore(false);
    },
    [mountedRef]
  );

  useEffect(() => {
    loadCategories();
  }, [loadCategories]);

  useEffect(() => {
    loadListings('initial', categoryId, 1);
  }, [categoryId, loadListings]);

  // ---------- ร้านที่ติดตาม ----------
  const loadFollowed = useCallback(async () => {
    if (!isAuthenticated) {
      setFollowed([]);
      return;
    }
    const res = await getFollowedShops(1, 20);
    if (mountedRef.current && res.success) setFollowed(res.data.shops);
  }, [isAuthenticated, mountedRef]);

  useEffect(() => {
    loadFollowed();
  }, [loadFollowed]);

  // กลับเข้าหน้า (เช่น หลังกดติดตามร้าน) → รีเฟรชเงียบ ไม่ถี่กว่า 1 นาที
  useFocusEffect(
    useCallback(() => {
      const now = Date.now();
      if (lastFocusLoadRef.current && now - lastFocusLoadRef.current > 60_000) {
        loadFollowed();
        if (area) loadNearby(area);
      }
      lastFocusLoadRef.current = now;
    }, [area, loadFollowed, loadNearby])
  );

  const onRefresh = async () => {
    setRefreshing(true);
    setBannerKey((k) => k + 1);
    await Promise.all([
      loadListings('refresh', categoryId, 1),
      loadFollowed(),
      loadCategories(),
      area ? loadNearby(area) : Promise.resolve(),
    ]);
    if (mountedRef.current) setRefreshing(false);
  };

  const provinces = useMemo(() => searchProvinces(provinceQuery), [provinceQuery]);

  const areaLabel = !area
    ? 'ยังไม่ได้เลือกพื้นที่'
    : area.kind === 'gps'
      ? 'ใกล้ตำแหน่งของคุณ'
      : `จังหวัด${area.province.name.replace(/^กรุงเทพมหานคร$/, 'กรุงเทพฯ')}`;

  // ---------- ส่วนแสดงผล ----------
  const renderNearby = () => {
    if (!areaReady) {
      return <ActivityIndicator color={colors.gold} style={styles.inlineLoader} />;
    }
    if (!area) {
      return (
        <Card3D gradientBorder padding={spacing.lg}>
          <Text style={styles.promptIcon}>📍</Text>
          <Text style={[typography.h3, styles.center, { color: colors.textStrong }]}>ดูร้านที่เปิดอยู่ใกล้คุณ</Text>
          <Text style={[typography.bodySm, styles.center, { color: colors.textMuted }]}>
            รถเข็นและร้านในชุมชนเปิดไม่ตรงเวลากัน ให้เราช่วยหาร้านที่เปิดอยู่ตอนนี้นะ
          </Text>
          <Button3D
            title="หาร้านใกล้ฉัน"
            icon="🧭"
            size="lg"
            fullWidth
            loading={location.locating}
            loadingText="กำลังหาตำแหน่ง…"
            onPress={locateMe}
            style={styles.gapTop}
          />
          <Button3D
            title="เลือกจังหวัดเอง"
            icon="🗺️"
            variant="ghost"
            size="md"
            fullWidth
            onPress={() => setAreaSheet(true)}
            style={styles.gapTopSm}
          />
        </Card3D>
      );
    }
    if (nearbyState === 'loading' && nearby.length === 0) {
      return <ActivityIndicator color={colors.gold} style={styles.inlineLoader} />;
    }
    if (nearbyState === 'error' && nearby.length === 0) {
      return (
        <EmptyState compact variant="error" message={nearbyError || undefined} onAction={() => loadNearby(area)} />
      );
    }
    if (nearby.length === 0) {
      return (
        <Card3D variant="flat" padding={spacing.lg}>
          <Text style={[typography.bodyStrong, styles.center, { color: colors.textStrong }]}>
            ตอนนี้ยังไม่มีร้านเปิดใกล้ๆ {area.kind === 'gps' ? `ในระยะ ${nearbyRadius} กม.` : ''}
          </Text>
          <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>
            กดติดตามร้านที่ชอบไว้ ร้านเปิดเมื่อไหร่เราจะแจ้งเตือนทันที
          </Text>
          <Button3D
            title="เปลี่ยนพื้นที่"
            icon="🗺️"
            variant="secondary"
            size="sm"
            onPress={() => setAreaSheet(true)}
            style={styles.centerButton}
          />
        </Card3D>
      );
    }
    return (
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
    );
  };

  const renderGrid = () => {
    if (listingsLoading && listings.length === 0) {
      return <ActivityIndicator color={colors.gold} style={styles.inlineLoader} />;
    }
    if (listingsError && listings.length === 0) {
      return <EmptyState compact variant="error" message={listingsError} onAction={() => loadListings('initial', categoryId, 1)} />;
    }
    if (listings.length === 0) {
      return (
        <EmptyState
          compact
          icon="🥬"
          title="ยังไม่มีเมนูในหมวดนี้"
          message="ลองดูหมวดอื่น หรือกลับมาใหม่ภายหลังนะ"
          actionLabel={categoryId ? 'ดูทั้งหมด' : undefined}
          onAction={categoryId ? () => setCategoryId(null) : undefined}
        />
      );
    }
    return (
      <>
        <View style={[styles.grid, { gap: gridGap }]}>
          {listings.map((l) => (
            <ListingCard key={l.id} listing={l} width={cardWidth} />
          ))}
        </View>
        {listingsHasMore && (
          <Button3D
            title="ดูเมนูเพิ่ม"
            variant="secondary"
            size="md"
            loading={listingsMore}
            onPress={() => loadListings('more', categoryId, listingsPage + 1)}
            style={styles.moreButton}
          />
        )}
      </>
    );
  };

  return (
    <Screen
      title="ตลาดสด"
      subtitle="อาหารร้อนๆ ของสด จากร้านใกล้บ้าน"
      refreshing={refreshing}
      onRefresh={onRefresh}
      right={
        <>
          <HeaderIconButton
            icon="🧾"
            label="ออเดอร์ตลาดสดของฉัน"
            onPress={() => router.push((isAuthenticated ? '/taladsod/orders' : '/login') as never)}
          />
          <TaladsodCartButton />
        </>
      }
    >
      {location.element}

      <View style={styles.bleed}>
        <BannerSlider placement="taladsod" height={176} refreshKey={bannerKey} />
      </View>

      {/* ---------- พื้นที่ค้นหา ---------- */}
      <Pressable
        onPress={() => {
          tapHaptic();
          setAreaSheet(true);
        }}
        accessibilityRole="button"
        accessibilityLabel={`พื้นที่: ${areaLabel} แตะเพื่อเปลี่ยน`}
        style={({ pressed }) => [
          styles.areaBar,
          { backgroundColor: colors.inset, borderColor: colors.border, opacity: pressed ? 0.8 : 1 },
        ]}
      >
        <Text style={styles.areaIcon}>{area?.kind === 'gps' ? '📍' : '🗺️'}</Text>
        <View style={styles.flex}>
          <Text style={[typography.micro, { color: colors.textFaint }]}>ส่งถึง / ค้นหาร้าน</Text>
          <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
            {areaLabel}
          </Text>
        </View>
        <Text style={[typography.caption, { color: colors.goldDeep }]}>เปลี่ยน ›</Text>
      </Pressable>

      {/* ---------- เปิดอยู่ใกล้คุณ ---------- */}
      <SectionHeader
        title="เปิดอยู่ใกล้คุณ"
        icon="🔥"
        subtitle={area && nearby.length > 0 ? `${nearby.length} ร้านเปิดอยู่ตอนนี้` : undefined}
        actionLabel={area && nearby.length > 0 ? 'รีเฟรช' : undefined}
        onAction={area ? () => loadNearby(area) : undefined}
        style={styles.section}
      />
      {renderNearby()}

      {/* ---------- ร้านที่ติดตาม ---------- */}
      {followed.length > 0 && (
        <>
          <SectionHeader title="ร้านที่ติดตาม" icon="💛" subtitle="ร้านเปิดเมื่อไหร่เราจะแจ้งเตือน" style={styles.section} />
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.hList}
            style={styles.bleed}
          >
            {followed.map((s) => (
              <FollowedShopBubble key={s.id} shop={s} />
            ))}
          </ScrollView>
        </>
      )}

      {/* ---------- หมวดหมู่ ---------- */}
      {categories.length > 0 && (
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.chips}
          style={[styles.bleed, styles.section]}
        >
          <Chip label="ทั้งหมด" icon="🧺" selected={categoryId === null} onPress={() => setCategoryId(null)} />
          {categories.map((c) => (
            <Chip
              key={c.id}
              label={c.name}
              icon={c.icon && c.icon.length <= 4 ? c.icon : undefined}
              selected={categoryId === c.id}
              onPress={() => setCategoryId(c.id)}
            />
          ))}
        </ScrollView>
      )}

      {/* ---------- เมนูแนะนำ ---------- */}
      <SectionHeader
        title={categoryId ? categories.find((c) => c.id === categoryId)?.name || 'เมนู' : 'เมนูแนะนำ'}
        icon="🍛"
        style={categories.length > 0 ? styles.sectionTight : styles.section}
      />
      {renderGrid()}

      {/* ---------- เลือกพื้นที่ ---------- */}
      <FormSheet
        visible={areaSheet}
        icon="🗺️"
        title="ค้นหาร้านในพื้นที่ไหนดี"
        description="ใช้ตำแหน่งตอนนี้ หรือเลือกจังหวัด (ค้นร้านที่เปิดอยู่รอบตัวเมือง 50 กม.)"
        onClose={() => {
          setAreaSheet(false);
          setProvinceQuery('');
        }}
      >
        <Button3D title="ใช้ตำแหน่งปัจจุบัน" icon="📍" size="md" fullWidth onPress={locateFromSheet} style={styles.gapTopSm} />
        <Field
          label="ค้นหาจังหวัด"
          placeholder="เช่น เชียงใหม่"
          value={provinceQuery}
          onChangeText={setProvinceQuery}
          autoCorrect={false}
          returnKeyType="search"
          containerStyle={styles.gapTop}
        />
        <View style={styles.provinceList}>
          {provinces.length === 0 ? (
            <Text style={[typography.caption, { color: colors.textMuted }]}>ไม่พบจังหวัดที่ค้นหา</Text>
          ) : (
            provinces.map((p) => {
              const selected = area?.kind === 'province' && area.province.name === p.name;
              return (
                <Pressable
                  key={p.name}
                  onPress={() => chooseProvince(p)}
                  accessibilityRole="button"
                  accessibilityState={{ selected }}
                  style={({ pressed }) => [
                    styles.provinceRow,
                    {
                      borderBottomColor: colors.divider,
                      backgroundColor: selected ? colors.goldSoft : pressed ? colors.surface : 'transparent',
                    },
                  ]}
                >
                  <Text style={[typography.body, styles.flex, { color: colors.textStrong }]}>{p.name}</Text>
                  <Text style={[typography.caption, { color: colors.textFaint }]}>{selected ? '✓ เลือกอยู่' : `ภาค${p.region}`}</Text>
                </Pressable>
              );
            })
          )}
        </View>
      </FormSheet>
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
  bleed: {
    marginHorizontal: -spacing.screen,
  },
  gapTop: {
    marginTop: spacing.lg,
  },
  gapTopSm: {
    marginTop: spacing.sm,
  },
  section: {
    marginTop: spacing.xl,
  },
  sectionTight: {
    marginTop: spacing.md,
  },
  inlineLoader: {
    marginVertical: spacing.xl,
  },
  areaBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderRadius: radii.lg,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    marginTop: spacing.lg,
    minHeight: 56,
  },
  areaIcon: {
    fontSize: 22,
  },
  promptIcon: {
    fontSize: 36,
    textAlign: 'center',
    marginBottom: spacing.sm,
  },
  centerButton: {
    alignSelf: 'center',
    marginTop: spacing.md,
  },
  hList: {
    paddingHorizontal: spacing.screen,
    paddingVertical: spacing.sm,
  },
  chips: {
    paddingHorizontal: spacing.screen,
    gap: spacing.sm,
    paddingBottom: spacing.xs,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  moreButton: {
    alignSelf: 'center',
    marginTop: spacing.lg,
  },
  provinceList: {
    marginTop: spacing.sm,
  },
  provinceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    minHeight: 48,
    paddingHorizontal: spacing.sm,
    borderBottomWidth: 1,
    borderRadius: radii.xs,
  },
});
