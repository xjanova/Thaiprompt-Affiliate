/**
 * ตลาดสด — หน้าแรกฝั่งผู้ซื้อ (ธีมรอยัล น้ำเงินกรมท่า-ทอง ตามม็อกที่เจ้าของอนุมัติ)
 *
 * - หัวน้ำเงินลายกนก: ชื่อหน้า + พื้นที่ค้นหาร้าน (แตะเพื่อเปลี่ยน) + ปุ่มออเดอร์/ตะกร้า
 * - ช่องค้นหาเมนู/ร้าน (ค้นจริงผ่าน q ของ API) · หมวดหมู่ · แบนเนอร์ (placement taladsod)
 * - "เปิดอยู่ใกล้คุณ": แผนที่ย่อวางหมุดร้านตามทิศ/ระยะจริงจากจุดค้นหา + การ์ดร้านแนวนอน
 *   (ขอสิทธิ์ตำแหน่งพร้อมคำอธิบายก่อนเสมอ · ไม่อนุญาต → เลือกจังหวัดแทน ค้นในรัศมี 50 กม. จากตัวเมือง)
 * - ร้านที่ติดตาม · เมนูแนะนำ (กริด 2 คอลัมน์)
 * - ดึงลง = รีเฟรชทุกส่วน (ไม่บังจอ)
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, ScrollView, StatusBar, StyleSheet, View, useWindowDimensions } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Image } from 'expo-image';
import { useLocalSearchParams, router, useFocusEffect } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text, TextInput } from '@/components/ui/Text';
import { useAuthStore } from '@/stores/authStore';
import {
  BannerSlider,
  BrandArt,
  Button3D,
  Card3D,
  Chip,
  EmptyState,
  GlassIconButton,
  Icon,
  OnHeaderProvider,
  RoyalHeader,
  SectionHeader,
  tapHaptic,
  type IconName,
} from '@/components/ui';
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
  fmImageUri,
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
import { useTheme, radii, spacing, typography, shadowStyle } from '@/theme';

type Area = { kind: 'gps'; coords: Coords } | { kind: 'province'; province: ProvinceCenter };

const AREA_STORAGE_KEY = '@thaiprompt/taladsod_area_v1';
const GPS_RADIUS_KM = 10;
const GPS_WIDE_RADIUS_KM = 30;
const LISTINGS_PER_PAGE = 20;
const SHEET_SETTLE_MS = 420;
const MAP_IMAGE = require('@/assets/images/brand/map-light.webp');

const saveArea = (area: Area | null) => {
  try {
    const value = !area ? null : area.kind === 'gps' ? { kind: 'gps' } : { kind: 'province', name: area.province.name };
    if (value) AsyncStorage.setItem(AREA_STORAGE_KEY, JSON.stringify(value)).catch(() => {});
    else AsyncStorage.removeItem(AREA_STORAGE_KEY).catch(() => {});
  } catch {
    // เก็บไม่ได้ก็ไม่เป็นไร (แค่ความสะดวก)
  }
};

/** ไอคอนของหมวดจากชื่อหมวด (หมวดจาก server ส่งอีโมจิมา — แอปไม่แสดงอีโมจิ) */
const categoryIcon = (name: string): IconName => {
  if (/เครื่องดื่ม|กาแฟ|ชา|น้ำ/.test(name)) return 'coffee';
  if (/หวาน|ขนม|เบเกอรี่|เค้ก/.test(name)) return 'cake';
  if (/ผลไม้/.test(name)) return 'orange-slice';
  if (/ผัก|สมุนไพร/.test(name)) return 'carrot';
  if (/ทะเล|ปลา|กุ้ง/.test(name)) return 'fish';
  if (/ไข่/.test(name)) return 'egg-crack';
  if (/เนื้อ|หมู|ไก่/.test(name)) return 'cooking-pot';
  if (/อาหาร|ข้าว|ก๋วยเตี๋ยว|ทาน/.test(name)) return 'bowl-food';
  return 'basket';
};

// =====================================================
// แผนที่ย่อร้านใกล้คุณ
// =====================================================

/**
 * วางหมุดร้านตามทิศและระยะจริงจากจุดค้นหา (พื้นแผนที่เป็นภาพตกแต่ง ไม่ใช่ถนนจริง)
 * ร้านที่ไม่มีพิกัดไม่ถูกวาง · จุดกลาง = ตำแหน่งคุณ (GPS) หรือหมุดตัวเมือง (จังหวัด)
 */
const NearbyMapCard: React.FC<{
  shops: FmNearbyShop[];
  center: { latitude: number; longitude: number };
  isGps: boolean;
  radiusKm: number;
  onChangeArea: () => void;
}> = ({ shops, center, isGps, radiusKm, onChangeArea }) => {
  const { colors, gradients } = useTheme();
  const [size, setSize] = useState({ w: 0, h: 0 });
  const H = 200;

  const pins = useMemo(() => {
    const cosLat = Math.cos((center.latitude * Math.PI) / 180);
    const pts = shops
      .filter((s) => s.location)
      .slice(0, 6)
      .map((s) => ({
        shop: s,
        dx: (s.location!.longitude - center.longitude) * cosLat * 111,
        dy: (s.location!.latitude - center.latitude) * 111,
      }));
    const maxD = Math.max(0.4, ...pts.map((p) => Math.max(Math.abs(p.dx), Math.abs(p.dy))));
    return pts.map((p) => ({ ...p, nx: p.dx / maxD, ny: p.dy / maxD }));
  }, [shops, center.latitude, center.longitude]);

  const padX = 34;
  const padTop = 30;
  const padBottom = 78;
  const cx = size.w / 2;
  const cy = padTop + (H - padTop - padBottom) / 2;
  const rx = size.w / 2 - padX;
  const ry = (H - padTop - padBottom) / 2;

  return (
    <View
      onLayout={(e) => setSize({ w: e.nativeEvent.layout.width, h: e.nativeEvent.layout.height })}
      style={[styles.mapCard, { height: H, backgroundColor: colors.inset }, shadowStyle('md', colors.shadowDark)]}
    >
      <Image source={MAP_IMAGE} style={StyleSheet.absoluteFill} contentFit="cover" />
      {size.w > 0 && (
        <>
          {isGps ? (
            <View style={[styles.meDot, { left: cx - 9, top: cy - 9, borderColor: '#FFFFFF' }]} />
          ) : (
            <View style={[styles.centerPin, { left: cx - 13, top: cy - 26 }]}>
              <Icon name="map-pin" size={26} color={colors.navy} weight="fill" />
            </View>
          )}
          {pins.map(({ shop, nx, ny }) => {
            const uri = fmImageUri(shop.shop_image) || fmImageUri(shop.top_items[0]?.image_url ?? null);
            const left = cx + nx * rx - 22;
            const top = cy - ny * ry - 22;
            return (
              <Pressable
                key={shop.id}
                onPress={() => router.push(`/taladsod/shop/${shop.id}` as never)}
                accessibilityRole="button"
                accessibilityLabel={`${shop.shop_name} บนแผนที่`}
                style={[styles.pin, { left, top, backgroundColor: colors.goldSoft }]}
              >
                {uri ? (
                  <Image source={{ uri }} style={styles.pinImg} contentFit="cover" />
                ) : (
                  <BrandArt name={shop.is_mobile ? 'cart' : 'store'} size={34} />
                )}
                <View style={[styles.pinDot, { backgroundColor: shop.is_open ? '#2FBF71' : '#B9BFCA' }]} />
              </Pressable>
            );
          })}
        </>
      )}
      <View style={[styles.mapOverlay, { backgroundColor: colors.card }, shadowStyle('sm', colors.shadowDark)]}>
        <View style={styles.flex}>
          <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
            {shops.length.toLocaleString('th-TH')} ร้านเปิดอยู่
          </Text>
          <Text style={[typography.caption, { color: colors.textMuted }]}>ในระยะ {radiusKm} กม. จากจุดค้นหา</Text>
        </View>
        <Pressable
          onPress={onChangeArea}
          accessibilityRole="button"
          accessibilityLabel="เปลี่ยนพื้นที่ค้นหาร้าน"
          style={({ pressed }) => [styles.mapButtonWrap, { opacity: pressed ? 0.8 : 1 }]}
        >
          <View style={[styles.mapButton, { backgroundColor: gradients.navy[1] }]}>
            <Icon name="map-trifold" size={16} color={colors.goldLight} />
            <Text style={[typography.caption, { color: colors.goldLight, fontWeight: '700' }]}>เปลี่ยนพื้นที่</Text>
          </View>
        </Pressable>
      </View>
    </View>
  );
};

// =====================================================
// หน้าจอ
// =====================================================

export default function TaladsodHomeScreen() {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const { width } = useWindowDimensions();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const mountedRef = useMountedRef();
  const location = useBuyerLocation();
  // ?nearby=1 มาจากไทล์ "รถเข็นใกล้ฉัน" ในหน้าแรก — ยังไม่มีพื้นที่ให้หาตำแหน่งให้ทันที (ไม่ต้องกดซ้ำ)
  const { nearby: nearbyParam } = useLocalSearchParams<{ nearby?: string }>();

  const [area, setArea] = useState<Area | null>(null);
  const [areaReady, setAreaReady] = useState(false);
  const [nearby, setNearby] = useState<FmNearbyShop[]>([]);
  const [nearbyState, setNearbyState] = useState<'idle' | 'loading' | 'ready' | 'error'>('idle');
  const [nearbyError, setNearbyError] = useState<string | null>(null);
  const [nearbyRadius, setNearbyRadius] = useState<number>(GPS_RADIUS_KM);

  const [categories, setCategories] = useState<FmCategory[]>([]);
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
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
  const shopCardWidth = Math.min(290, Math.max(240, width * 0.7));

  // ---------- ค้นหา (หน่วง 400ms) ----------
  useEffect(() => {
    const timer = setTimeout(() => setSearch(searchInput.trim()), 400);
    return () => clearTimeout(timer);
  }, [searchInput]);

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
      // มาจากไทล์ "รถเข็นใกล้ฉัน" แต่ยังไม่มีตำแหน่ง → ขอตำแหน่งเลย (มีหน้าอธิบายก่อนขอสิทธิ์ใน location.request)
      if (!coords && nearbyParam === '1') locateMe();
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
    async (mode: 'initial' | 'refresh' | 'more', targetCategory: number | null, targetPage: number, q: string) => {
      const reqId = ++listingsReqRef.current;
      if (mode === 'initial') setListingsLoading(true);
      if (mode === 'more') setListingsMore(true);
      const res = await getListings({
        category_id: targetCategory ?? undefined,
        q: q || undefined,
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
    loadListings('initial', categoryId, 1, search);
  }, [categoryId, search, loadListings]);

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
      loadListings('refresh', categoryId, 1, search),
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

  const goBack = () => {
    if (router.canGoBack()) router.back();
    else router.replace('/(tabs)' as never);
  };

  // ---------- ส่วนแสดงผล ----------
  const renderNearby = () => {
    if (!areaReady) {
      return <ActivityIndicator color={colors.gold} style={styles.inlineLoader} />;
    }
    if (!area) {
      return (
        <View style={[styles.promptCard, { backgroundColor: colors.card }, shadowStyle('md', colors.shadowDark)]}>
          <Image source={MAP_IMAGE} style={styles.promptMap} contentFit="cover" />
          <View style={styles.promptBody}>
            <View style={styles.promptHead}>
              <BrandArt name="cart" size={62} />
              <View style={styles.flex}>
                <Text style={[typography.h3, { color: colors.textStrong }]}>ดูร้านที่เปิดอยู่ใกล้คุณ</Text>
                <Text style={[typography.bodySm, { color: colors.textMuted }]}>
                  รถเข็นและร้านในชุมชนเปิดไม่ตรงเวลากัน ให้เราช่วยหาร้านที่เปิดอยู่ตอนนี้นะ
                </Text>
              </View>
            </View>
            <Button3D
              title="หาร้านใกล้ฉัน"
              icon="navigation-arrow"
              size="lg"
              fullWidth
              loading={location.locating}
              loadingText="กำลังหาตำแหน่ง…"
              onPress={locateMe}
              style={styles.gapTop}
            />
            <Button3D
              title="เลือกจังหวัดเอง"
              icon="map-trifold"
              variant="secondary"
              size="md"
              fullWidth
              onPress={() => setAreaSheet(true)}
              style={styles.gapTopSm}
            />
          </View>
        </View>
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
        <Card3D padding={spacing.lg}>
          <View style={styles.promptHead}>
            <BrandArt name="store" size={60} />
            <View style={styles.flex}>
              <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
                ตอนนี้ยังไม่มีร้านเปิดใกล้ๆ {area.kind === 'gps' ? `ในระยะ ${nearbyRadius} กม.` : ''}
              </Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>
                กดติดตามร้านที่ชอบไว้ ร้านเปิดเมื่อไหร่เราจะแจ้งเตือนทันที
              </Text>
            </View>
          </View>
          <Button3D
            title="เปลี่ยนพื้นที่"
            icon="map-trifold"
            variant="secondary"
            size="sm"
            onPress={() => setAreaSheet(true)}
            style={styles.centerButton}
          />
        </Card3D>
      );
    }
    const center =
      area.kind === 'gps'
        ? { latitude: area.coords.latitude, longitude: area.coords.longitude }
        : { latitude: area.province.latitude, longitude: area.province.longitude };
    return (
      <>
        <NearbyMapCard
          shops={nearby}
          center={center}
          isGps={area.kind === 'gps'}
          radiusKm={nearbyRadius}
          onChangeArea={() => setAreaSheet(true)}
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
    );
  };

  const renderGrid = () => {
    if (listingsLoading && listings.length === 0) {
      return <ActivityIndicator color={colors.gold} style={styles.inlineLoader} />;
    }
    if (listingsError && listings.length === 0) {
      return (
        <EmptyState compact variant="error" message={listingsError} onAction={() => loadListings('initial', categoryId, 1, search)} />
      );
    }
    if (listings.length === 0) {
      return (
        <EmptyState
          compact
          art="basket"
          title={search ? `ไม่พบ "${search}"` : 'ยังไม่มีเมนูในหมวดนี้'}
          message={search ? 'ลองค้นด้วยคำอื่น หรือดูหมวดอื่นนะ' : 'ลองดูหมวดอื่น หรือกลับมาใหม่ภายหลังนะ'}
          actionLabel={categoryId || search ? 'ดูทั้งหมด' : undefined}
          onAction={
            categoryId || search
              ? () => {
                  setCategoryId(null);
                  setSearchInput('');
                }
              : undefined
          }
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
            iconRight="caret-down"
            loading={listingsMore}
            onPress={() => loadListings('more', categoryId, listingsPage + 1, search)}
            style={styles.moreButton}
          />
        )}
      </>
    );
  };

  const listTitle = search
    ? `ผลการค้นหา "${search}"`
    : categoryId
      ? categories.find((c) => c.id === categoryId)?.name || 'เมนู'
      : 'เมนูแนะนำ';

  return (
    <View style={[styles.root, { backgroundColor: colors.background }]}>
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      {location.element}

      <ScrollView
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled"
        contentContainerStyle={{ paddingBottom: spacing.xxxl + insets.bottom }}
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
        <RoyalHeader ornamentTop={insets.top - 16} style={{ paddingTop: insets.top + spacing.xs, paddingBottom: 54 }}>
          <OnHeaderProvider value>
            <View style={styles.topRow}>
              <GlassIconButton icon="caret-left" weight="bold" accessibilityLabel="ย้อนกลับ" onPress={goBack} />
              <View style={styles.flex} />
              <HeaderIconButton
                icon="receipt"
                label="ออเดอร์ตลาดสดของฉัน"
                onPress={() => router.push((isAuthenticated ? '/taladsod/orders' : '/login') as never)}
              />
              <TaladsodCartButton />
            </View>
          </OnHeaderProvider>

          <View style={styles.hero}>
            <Text accessibilityRole="header" style={[typography.serifLg, { color: colors.onHeader }]}>
              ตลาดสด
            </Text>
            <Text style={[typography.bodySm, { color: colors.onHeaderMuted }]}>อาหารร้อนๆ ของสด จากร้านใกล้บ้าน</Text>
            <Pressable
              onPress={() => {
                tapHaptic();
                setAreaSheet(true);
              }}
              accessibilityRole="button"
              accessibilityLabel={`พื้นที่: ${areaLabel} แตะเพื่อเปลี่ยน`}
              style={({ pressed }) => [
                styles.areaPill,
                { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder, opacity: pressed ? 0.8 : 1 },
              ]}
            >
              <Icon name={area?.kind === 'gps' ? 'navigation-arrow' : 'map-pin'} size={16} color={colors.gold} weight="fill" />
              <Text numberOfLines={1} style={[styles.areaText, { color: colors.onHeader }]}>
                {areaLabel}
              </Text>
              <View style={[styles.areaDivider, { backgroundColor: colors.headerGlassBorder }]} />
              <Text style={[styles.areaChange, { color: colors.goldLight }]}>เปลี่ยน</Text>
            </Pressable>
          </View>
        </RoyalHeader>

        {/* ---------- แผ่นเนื้อหา ---------- */}
        <View style={[styles.sheet, { backgroundColor: colors.background }]}>
          <View style={[styles.search, { backgroundColor: colors.card, borderColor: colors.border }, shadowStyle('lg', colors.shadowDark)]}>
            <Icon name="magnifying-glass" size={21} color={colors.navy} />
            <TextInput
              value={searchInput}
              onChangeText={setSearchInput}
              placeholder="ค้นหาเมนู หรือร้านในตลาด"
              placeholderTextColor={colors.textFaint}
              returnKeyType="search"
              onSubmitEditing={() => setSearch(searchInput.trim())}
              accessibilityLabel="ค้นหาเมนูในตลาดสด"
              style={[typography.body, styles.searchInput, { color: colors.textStrong }]}
            />
            {searchInput.length > 0 && (
              <Pressable onPress={() => setSearchInput('')} hitSlop={10} accessibilityRole="button" accessibilityLabel="ล้างคำค้นหา">
                <Icon name="x-circle" size={20} color={colors.textFaint} weight="fill" />
              </Pressable>
            )}
          </View>

          {/* ---------- หมวดหมู่ ---------- */}
          {categories.length > 0 && (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips} style={[styles.bleed, styles.chipsWrap]}>
              <Chip label="ทั้งหมด" icon="squares-four" selected={categoryId === null} onPress={() => setCategoryId(null)} />
              {categories.map((c) => (
                <Chip
                  key={c.id}
                  label={c.name}
                  icon={categoryIcon(c.name)}
                  selected={categoryId === c.id}
                  onPress={() => setCategoryId(c.id)}
                />
              ))}
            </ScrollView>
          )}

          {!search && (
            <>
              <View style={[styles.bleed, styles.bannerGap]}>
                <BannerSlider placement="taladsod" height={170} refreshKey={bannerKey} />
              </View>

              {/* ---------- เปิดอยู่ใกล้คุณ ---------- */}
              <SectionHeader
                title="เปิดอยู่ใกล้คุณตอนนี้"
                subtitle="ร้านรถเข็นย้ายที่ได้ ตำแหน่งอัปเดตสด"
                actionLabel={area && nearby.length > 0 ? 'รีเฟรช' : undefined}
                onAction={area ? () => loadNearby(area) : undefined}
                style={styles.section}
              />
              {renderNearby()}

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
            </>
          )}

          {/* ---------- เมนู ---------- */}
          <SectionHeader title={listTitle} style={styles.section} />
          {renderGrid()}
        </View>
      </ScrollView>

      {/* ---------- เลือกพื้นที่ ---------- */}
      <FormSheet
        visible={areaSheet}
        icon="map-trifold"
        title="ค้นหาร้านในพื้นที่ไหนดี"
        description="ใช้ตำแหน่งตอนนี้ หรือเลือกจังหวัด (ค้นร้านที่เปิดอยู่รอบตัวเมือง 50 กม.)"
        onClose={() => {
          setAreaSheet(false);
          setProvinceQuery('');
        }}
      >
        <Button3D title="ใช้ตำแหน่งปัจจุบัน" icon="navigation-arrow" size="md" fullWidth onPress={locateFromSheet} style={styles.gapTopSm} />
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
                  {selected ? (
                    <View style={styles.selectedTag}>
                      <Icon name="check-circle" size={16} color={colors.goldDeep} weight="fill" />
                      <Text style={[typography.caption, { color: colors.goldDeep }]}>เลือกอยู่</Text>
                    </View>
                  ) : (
                    <Text style={[typography.caption, { color: colors.textFaint }]}>{`ภาค${p.region}`}</Text>
                  )}
                </Pressable>
              );
            })
          )}
        </View>
      </FormSheet>
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
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm + 2,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
  },
  hero: {
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.lg,
  },
  areaPill: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: spacing.sm,
    height: 38,
    maxWidth: '100%',
    marginTop: spacing.md + 2,
    paddingHorizontal: spacing.md,
    borderRadius: 13,
    borderWidth: 1,
  },
  areaText: {
    fontSize: 13,
    fontWeight: '600',
    flexShrink: 1,
  },
  areaDivider: {
    width: 1,
    height: 16,
  },
  areaChange: {
    fontSize: 13,
    fontWeight: '600',
  },
  sheet: {
    marginTop: -28,
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    paddingHorizontal: spacing.screen,
  },
  search: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    height: 54,
    marginTop: -24,
    paddingHorizontal: spacing.lg + 2,
    borderRadius: 18,
    borderWidth: 1,
  },
  searchInput: {
    flex: 1,
    paddingVertical: 0,
  },
  chipsWrap: {
    marginTop: spacing.lg,
  },
  chips: {
    paddingHorizontal: spacing.screen,
    gap: spacing.sm,
    paddingVertical: spacing.xs,
  },
  bleed: {
    marginHorizontal: -spacing.screen,
  },
  bannerGap: {
    marginTop: spacing.md,
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
  inlineLoader: {
    marginVertical: spacing.xl,
  },
  promptCard: {
    borderRadius: 24,
    overflow: 'hidden',
  },
  promptMap: {
    height: 92,
    width: '100%',
  },
  promptBody: {
    padding: spacing.lg,
  },
  promptHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  centerButton: {
    alignSelf: 'center',
    marginTop: spacing.md,
  },
  mapCard: {
    borderRadius: 24,
    overflow: 'hidden',
  },
  meDot: {
    position: 'absolute',
    width: 18,
    height: 18,
    borderRadius: 9,
    borderWidth: 3,
    backgroundColor: '#2C6BE0',
    boxShadow: '0px 0px 0px 10px rgba(44,107,224,0.18)',
  },
  centerPin: {
    position: 'absolute',
  },
  pin: {
    position: 'absolute',
    width: 44,
    height: 44,
    borderRadius: 22,
    borderWidth: 3,
    borderColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    boxShadow: '0px 8px 14px -6px rgba(0,0,0,0.45)',
  },
  pinImg: {
    width: 38,
    height: 38,
    borderRadius: 19,
  },
  pinDot: {
    position: 'absolute',
    right: -3,
    top: -3,
    width: 12,
    height: 12,
    borderRadius: 6,
    borderWidth: 2,
    borderColor: '#FFFFFF',
  },
  mapOverlay: {
    position: 'absolute',
    left: spacing.md,
    right: spacing.md,
    bottom: spacing.md,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.sm + 2,
    paddingLeft: spacing.md + 2,
    paddingRight: spacing.sm + 2,
    borderRadius: 16,
  },
  mapButtonWrap: {
    borderRadius: 12,
    overflow: 'hidden',
  },
  mapButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    height: 36,
    paddingHorizontal: spacing.md,
    borderRadius: 12,
  },
  hList: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
    paddingBottom: spacing.lg,
  },
  hListBubbles: {
    paddingHorizontal: spacing.screen,
    gap: spacing.sm,
    paddingVertical: spacing.xs,
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
  selectedTag: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
});
