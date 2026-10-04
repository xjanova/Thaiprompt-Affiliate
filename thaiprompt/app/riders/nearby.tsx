/**
 * ไรเดอร์ใกล้ฉัน — GET /riders/nearby + GET /riders/favorites (ตามแบบ Main.png ที่เจ้าของอนุมัติ)
 *
 * - แผนที่เต็มขอบด้านบน: ไรเดอร์ที่ออนไลน์ (รูปลายน้ำ + ป้ายหัวใจที่ฉันให้ + วงตำแหน่งคร่าวๆ ~200 ม.)
 * - แผ่นรายการด้านล่าง: ตัวกรอง คนโปรด / ใกล้ที่สุด / หัวใจเยอะ
 *   · คนโปรด: การ์ดใหญ่ "ล็อกเรียกคนนี้" เมื่อ can_lock · ยังไม่ครบ = แถบความคืบหน้าหัวใจ "อีก N ออเดอร์…"
 *   · ใกล้คุณตอนนี้: รายการไรเดอร์ในรัศมี
 * - ล็อกเรียก = จำตัวเลือกไว้ใช้ตอนชำระเงินครั้งถัดไป (server ตรวจสิทธิ์ซ้ำตอนสั่งเสมอ)
 * - ตำแหน่งฉัน: มีสิทธิ์แล้ว → อ่านเงียบ · ไม่มี → ใช้ที่อยู่หลักที่ปักหมุดไว้ · กดปุ่มเป้า = ขอสิทธิ์ (มีคำอธิบายก่อน)
 * - รีเฟรชตำแหน่งไรเดอร์ทุก 30 วินาทีระหว่างหน้าเปิด
 */

import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, ScrollView, StatusBar, StyleSheet, View, useWindowDimensions } from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import {
  Button3D,
  Card3D,
  Chip,
  EmptyState,
  Icon,
  LiveMap,
  Pill,
  SectionHeader,
  resultHaptic,
  tapHaptic,
  type LiveMapMarker,
} from '@/components/ui';
import { PersonAvatar } from '@/components/people/PersonAvatar';
import { VerifiedBadge } from '@/components/people/VerifiedBadge';
import { useBuyerLocation, useFocusedInterval, useMountedRef, getCachedBuyerCoords } from '@/components/taladsod';
import { useAuthStore } from '@/stores/authStore';
import { isPreferredChoiceValid, usePreferredRiderStore } from '@/stores/preferredRiderStore';
import { getAddresses } from '@/services/api/shopApi';
import {
  DEFAULT_LOCK_MIN_HEARTS,
  getFavoriteRiders,
  getNearbyRiders,
  heartsToLock,
  vehicleText,
  type FavoriteRider,
  type NearbyRider,
} from '@/services/api/riderSocialApi';
import { useTheme, radii, shadowStyle, spacing, typography, withAlpha } from '@/theme';

type Mode = 'favorites' | 'nearest' | 'hearts';
type Coords = { latitude: number; longitude: number };

const POLL_MS = 30000;
const NEARBY_PREVIEW = 6;

/** ระยะเป็นข้อความ */
const kmText = (km: number | null | undefined): string | null =>
  km === null || km === undefined || !Number.isFinite(km) ? null : km < 1 ? `${Math.round(km * 1000)} ม.` : `${km.toFixed(1)} กม.`;

export default function NearbyRidersScreen() {
  const params = useLocalSearchParams<{ from?: string }>();
  const fromCheckout = params.from === 'checkout';
  const { colors, isDark } = useTheme();
  const insets = useSafeAreaInsets();
  const { height: screenHeight } = useWindowDimensions();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const userId = useAuthStore((s) => s.user?.id ?? null);
  const mountedRef = useMountedRef();
  const location = useBuyerLocation();
  // ป้าย "ออเดอร์ถัดไปจะล็อกเรียก..." ใช้เกณฑ์เดียวกับหน้าชำระเงิน: ของบัญชีนี้ + ไม่เกิน 2 ชั่วโมง (M5)
  const chosen = usePreferredRiderStore((s) => (isPreferredChoiceValid(s.choice, userId) ? s.choice : null));

  const [coords, setCoords] = useState<Coords | null>(null);
  const [coordsSource, setCoordsSource] = useState<'gps' | 'address' | null>(null);
  const [nearby, setNearby] = useState<NearbyRider[]>([]);
  const [favorites, setFavorites] = useState<FavoriteRider[]>([]);
  const [radiusKm, setRadiusKm] = useState(3);
  const [fuzzM, setFuzzM] = useState(200);
  const [lockMin, setLockMin] = useState(DEFAULT_LOCK_MIN_HEARTS);
  const [loading, setLoading] = useState(true);
  const [nearbyError, setNearbyError] = useState<string | null>(null);
  const [locating, setLocating] = useState(true);
  const [mode, setMode] = useState<Mode>('favorites');
  const [showAll, setShowAll] = useState(false);
  const [focusedId, setFocusedId] = useState<number | null>(null);
  const [refitKey, setRefitKey] = useState(0);

  const mapHeight = Math.round(Math.min(Math.max(screenHeight * 0.44, 300), 420));

  // ---------- หาตำแหน่งฉัน (เงียบ) ----------
  useEffect(() => {
    if (!isAuthenticated) return;
    let cancelled = false;
    (async () => {
      const cached = getCachedBuyerCoords(5 * 60_000);
      const silent = cached || (await location.peek());
      if (cancelled || !mountedRef.current) return;
      if (silent) {
        setCoords({ latitude: silent.latitude, longitude: silent.longitude });
        setCoordsSource('gps');
        setLocating(false);
        return;
      }
      // ไม่มีสิทธิ์ตำแหน่ง → ใช้ที่อยู่หลักที่ปักหมุดไว้
      const res = await getAddresses();
      if (cancelled || !mountedRef.current) return;
      const list = res.success && Array.isArray(res.data) ? res.data : [];
      const pinned = list.filter((a) => a.has_location && Number.isFinite(Number(a.latitude)) && Number.isFinite(Number(a.longitude)));
      const pick = pinned.find((a) => a.is_default) || pinned[0];
      if (pick) {
        setCoords({ latitude: Number(pick.latitude), longitude: Number(pick.longitude) });
        setCoordsSource('address');
      }
      setLocating(false);
    })();
    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isAuthenticated]);

  // ---------- โหลดไรเดอร์ ----------
  const loadFavorites = useCallback(async () => {
    const res = await getFavoriteRiders();
    if (!mountedRef.current) return;
    if (res.success) {
      setFavorites(res.data.riders);
      setLockMin(res.data.lock_min_hearts);
    }
  }, [mountedRef]);

  const loadNearby = useCallback(async () => {
    if (!coords) return;
    const res = await getNearbyRiders(coords.latitude, coords.longitude);
    if (!mountedRef.current) return;
    if (res.success) {
      setNearby(res.data.riders);
      setRadiusKm(res.data.radius_km);
      setFuzzM(res.data.fuzz_m);
      setLockMin(res.data.lock_min_hearts);
      setNearbyError(null);
    } else {
      setNearbyError(res.message);
    }
  }, [coords, mountedRef]);

  useEffect(() => {
    if (!isAuthenticated) return;
    loadFavorites();
  }, [isAuthenticated, loadFavorites]);

  useEffect(() => {
    if (!coords) {
      if (!locating) setLoading(false);
      return;
    }
    setLoading(true);
    loadNearby().finally(() => {
      if (mountedRef.current) setLoading(false);
    });
  }, [coords, locating, loadNearby, mountedRef]);

  useFocusedInterval(loadNearby, POLL_MS, !!coords && isAuthenticated);

  const locateMe = async () => {
    tapHaptic();
    const c = await location.request('riders');
    if (!c || !mountedRef.current) return;
    setCoords({ latitude: c.latitude, longitude: c.longitude });
    setCoordsSource('gps');
    setRefitKey((k) => k + 1);
  };

  // ---------- ล็อกเรียก ----------
  const lockRider = (rider: FavoriteRider | NearbyRider) => {
    if (!userId) return;
    usePreferredRiderStore.getState().choose(userId, {
      id: rider.id,
      display_name: rider.display_name,
      photo_url: rider.photo_url,
      vehicle_type: rider.vehicle_type,
      vehicle_label: rider.vehicle_label,
      plate_masked: rider.plate_masked,
      hearts_total: rider.hearts_total,
      hearts_from_me: rider.hearts_from_me,
      can_lock: rider.can_lock,
      verified: rider.verified,
    });
    resultHaptic('success');
    if (fromCheckout) {
      router.back();
      return;
    }
    Alert.alert(
      `เลือก${rider.display_name}แล้ว`,
      'สั่งครั้งถัดไปที่ส่งด้วยไรเดอร์ ระบบจะเสนองานให้คนนี้ก่อน 60 วินาที ถ้าไม่รับจะหาไรเดอร์คนอื่นให้อัตโนมัติ',
      [
        { text: 'ตกลง', style: 'cancel' },
        { text: 'ไปช้อปเลย', onPress: () => router.push('/(tabs)/shop' as never) },
      ]
    );
  };

  const unlock = () => {
    usePreferredRiderStore.getState().clear();
    tapHaptic();
  };

  // ---------- จัดข้อมูล ----------
  const nearbyById = useMemo(() => new Map(nearby.map((r) => [r.id, r])), [nearby]);
  const favoriteIds = useMemo(() => new Set(favorites.map((r) => r.id)), [favorites]);

  const markers = useMemo<LiveMapMarker[]>(() => {
    const list: LiveMapMarker[] = nearby.map((r) => ({
      id: `r${r.id}`,
      kind: 'rider',
      latitude: r.approx_latitude,
      longitude: r.approx_longitude,
      photoUrl: r.photo_url,
      hearts: r.hearts_from_me ?? 0,
      radiusM: fuzzM,
      online: r.status === 'available',
    }));
    if (coords) list.push({ id: 'me', kind: 'me', latitude: coords.latitude, longitude: coords.longitude, label: 'คุณ' });
    return list;
  }, [nearby, coords, fuzzM]);

  const sortedNearby = useMemo(() => {
    const list = [...nearby];
    if (mode === 'hearts') list.sort((a, b) => (b.hearts_total ?? 0) - (a.hearts_total ?? 0));
    else list.sort((a, b) => (a.distance_km ?? 99) - (b.distance_km ?? 99));
    if (focusedId) {
      const idx = list.findIndex((r) => r.id === focusedId);
      if (idx > 0) list.unshift(list.splice(idx, 1)[0]);
    }
    return mode === 'favorites' ? list.filter((r) => !favoriteIds.has(r.id)) : list;
  }, [nearby, mode, focusedId, favoriteIds]);

  const visibleNearby = showAll ? sortedNearby : sortedNearby.slice(0, NEARBY_PREVIEW);
  const onlineCount = nearby.length;

  // ---------- render ----------
  if (!isAuthenticated) {
    return (
      <View style={[styles.root, { backgroundColor: colors.background, paddingTop: insets.top + spacing.lg }]}>
        <EmptyState icon="lock-key" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </View>
    );
  }

  const favoriteCard = (f: FavoriteRider) => {
    const near = nearbyById.get(f.id);
    const distance = kmText(near?.distance_km);
    const online = near ? near.status === 'available' : f.online;
    const vehicle = vehicleText(f);
    const fromMe = f.hearts_from_me ?? 0;
    const isChosen = chosen?.rider.id === f.id;

    if (f.can_lock) {
      return (
        <Card3D key={f.id} gradientBorder shadow="md" padding={spacing.lg} radius={22} style={styles.block}>
          <View style={styles.row}>
            <PersonAvatar uri={f.photo_url} name={f.display_name} size={64} online={online} />
            <View style={styles.flex}>
              <View style={styles.nameRow}>
                <Text numberOfLines={1} style={[typography.h3, styles.flexShrink, { color: colors.textStrong }]}>
                  {f.display_name}
                </Text>
                {f.verified && <VerifiedBadge size={16} />}
                <Pill label={isChosen ? 'เลือกแล้ว' : 'ล็อกได้'} tone="gold" icon="lock" />
              </View>
              <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
                {[vehicle, distance, online ? 'ว่างตอนนี้' : near ? 'กำลังส่งงาน' : 'ออฟไลน์'].filter(Boolean).join(' · ')}
              </Text>
              <View style={styles.heartLine}>
                <Icon name="heart" size={15} color={colors.danger} weight="fill" />
                <Text style={[typography.bodySm, styles.bold, { color: colors.danger }]}>{fromMe} ดวงจากคุณ</Text>
                {f.hearts_total !== null && (
                  <Text style={[typography.caption, { color: colors.textFaint }]}>· รวม {f.hearts_total}</Text>
                )}
              </View>
            </View>
          </View>
          {isChosen ? (
            <View style={styles.chosenRow}>
              <Button3D title="ใช้คนนี้ในออเดอร์ถัดไปแล้ว" icon="check-circle" variant="secondary" size="md" disabled style={styles.flex} />
              <Button3D title="ยกเลิก" variant="ghost" size="md" onPress={unlock} />
            </View>
          ) : (
            <Button3D
              title="ล็อกเรียกคนนี้"
              icon="lock"
              size="lg"
              fullWidth
              onPress={() => lockRider(f)}
              accessibilityLabel={`ล็อกเรียก ${f.display_name} สำหรับออเดอร์ถัดไป`}
              style={styles.gapTop}
            />
          )}
        </Card3D>
      );
    }

    const need = heartsToLock(fromMe, lockMin);
    const progress = Math.max(0, Math.min(1, fromMe / Math.max(1, lockMin)));
    return (
      <Card3D key={f.id} shadow="sm" padding={spacing.lg} radius={22} style={styles.block}>
        <View style={styles.row}>
          <PersonAvatar uri={f.photo_url} name={f.display_name} size={54} online={online} />
          <View style={styles.flex}>
            <View style={styles.nameRow}>
              <Text numberOfLines={1} style={[typography.h3, styles.flexShrink, { color: colors.textStrong }]}>
                {f.display_name}
              </Text>
              {f.verified && <VerifiedBadge size={16} />}
              <View style={styles.flex} />
              {!!distance && <Text style={[typography.caption, { color: colors.textMuted }]}>{distance}</Text>}
            </View>
            <View style={styles.heartLine}>
              <Icon name="heart" size={15} color={colors.danger} weight="fill" />
              <Text style={[typography.bodySm, styles.bold, { color: colors.textStrong }]}>
                {fromMe} / {lockMin} ดวง
              </Text>
            </View>
            <View style={[styles.track, { backgroundColor: colors.inset }]} accessibilityRole="progressbar" accessibilityValue={{ min: 0, max: lockMin, now: fromMe }}>
              <View style={[styles.fill, { width: `${Math.round(progress * 100)}%`, backgroundColor: colors.danger }]} />
            </View>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              อีก {need} ออเดอร์ ให้หัวใจครบ {lockMin} ดวง แล้วล็อกเรียกได้
            </Text>
          </View>
        </View>
      </Card3D>
    );
  };

  const nearbyRow = (r: NearbyRider, index: number) => {
    const vehicle = vehicleText(r);
    const isNew = (r.hearts_total ?? 0) === 0;
    const focused = focusedId === r.id;
    return (
      <Pressable
        key={r.id}
        onPress={() => setFocusedId((id) => (id === r.id ? null : r.id))}
        accessibilityRole="button"
        accessibilityLabel={`${r.display_name} ห่าง ${kmText(r.distance_km) || 'ไม่ทราบระยะ'} หัวใจ ${r.hearts_total ?? 0} ดวง`}
        style={({ pressed }) => [
          styles.listRow,
          index > 0 && { borderTopWidth: 1, borderTopColor: colors.divider },
          focused && { backgroundColor: colors.goldSoft },
          { opacity: pressed ? 0.8 : 1 },
        ]}
      >
        <PersonAvatar uri={r.photo_url} name={r.display_name} size={46} online={r.status === 'available'} />
        <View style={styles.flex}>
          <View style={styles.nameRow}>
            <Text numberOfLines={1} style={[typography.bodyStrong, styles.flexShrink, { color: colors.textStrong }]}>
              {r.display_name}
            </Text>
            {r.verified && <VerifiedBadge size={15} />}
          </View>
          <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
            {[kmText(r.distance_km), isNew ? 'ไรเดอร์ใหม่' : vehicle, r.status === 'busy' ? 'กำลังส่งงาน' : null].filter(Boolean).join(' · ')}
          </Text>
        </View>
        {r.can_lock && !favoriteIds.has(r.id) ? (
          <Button3D title="ล็อก" icon="lock" size="sm" variant="secondary" onPress={() => lockRider(r)} />
        ) : null}
        <View style={styles.heartCount}>
          <Icon name="heart" size={15} color={colors.danger} weight="fill" />
          <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{r.hearts_total ?? 0}</Text>
        </View>
      </Pressable>
    );
  };

  const showFavorites = mode === 'favorites';

  return (
    <View style={[styles.root, { backgroundColor: colors.background }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor="transparent" translucent />
      {location.element}

      {/* ---------- แผนที่เต็มขอบ ---------- */}
      <View style={{ height: mapHeight }}>
        {coords ? (
          <LiveMap
            bare
            markers={markers}
            height={mapHeight}
            hideRecenter
            openTargetId={null}
            refitKey={refitKey}
            onMarkerPress={(id) => {
              const n = Number(id.replace(/^r/, ''));
              if (Number.isInteger(n) && n > 0) setFocusedId(n);
            }}
            accessibilityLabel={`แผนที่ไรเดอร์ใกล้คุณ ${onlineCount} คน ตำแหน่งแบบคร่าวๆ`}
          />
        ) : (
          <View style={[styles.mapPlaceholder, { backgroundColor: colors.inset }]}>
            {locating ? (
              <ActivityIndicator color={colors.gold} />
            ) : (
              <>
                <Icon name="map-trifold" size={36} color={colors.textFaint} />
                <Text style={[typography.bodySm, styles.centerText, { color: colors.textMuted }]}>
                  ยังไม่รู้ตำแหน่งของคุณ กดปุ่มเป้าเพื่อดูไรเดอร์รอบตัว
                </Text>
              </>
            )}
          </View>
        )}

        {/* หัวลอย: ย้อนกลับ · ชื่อหน้า · หาตำแหน่งฉัน */}
        <View style={[styles.floatHeader, { top: insets.top + spacing.sm }]} pointerEvents="box-none">
          <Pressable
            onPress={() => (router.canGoBack() ? router.back() : router.replace('/(tabs)' as never))}
            accessibilityRole="button"
            accessibilityLabel="ย้อนกลับ"
            style={({ pressed }) => [styles.squareBtn, { backgroundColor: colors.navyFill, opacity: pressed ? 0.8 : 1 }, shadowStyle('md', colors.shadowDark)]}
          >
            <Icon name="caret-left" size={22} color={colors.goldLight} weight="bold" />
          </Pressable>
          <LinearGradient colors={[withAlpha(colors.navyFill, 0.94), withAlpha(colors.navyFill, 0.86)]} style={styles.titlePill}>
            <View style={[styles.liveDot, { backgroundColor: colors.success }]} />
            <View style={styles.flex}>
              <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.onHeader }]}>
                ไรเดอร์ใกล้ฉัน
              </Text>
              <Text numberOfLines={1} style={[typography.micro, { color: colors.onHeaderMuted }]}>
                ออนไลน์ {onlineCount} คน · รัศมี {radiusKm} กม.
              </Text>
            </View>
            <Icon name="shield-check" size={18} color={colors.goldLight} />
          </LinearGradient>
          <Pressable
            onPress={locateMe}
            accessibilityRole="button"
            accessibilityLabel="ใช้ตำแหน่งปัจจุบันของฉัน"
            style={({ pressed }) => [styles.squareBtn, { backgroundColor: colors.card, opacity: pressed ? 0.8 : 1 }, shadowStyle('md', colors.shadowDark)]}
          >
            {location.locating ? <ActivityIndicator size="small" color={colors.gold} /> : <Icon name="crosshair" size={22} color={colors.navy} />}
          </Pressable>
        </View>
        <View style={[styles.privacy, { top: insets.top + 74, backgroundColor: colors.card }, shadowStyle('sm', colors.shadowDark)]}>
          <Icon name="info" size={14} color={colors.textMuted} />
          <Text style={[typography.micro, { color: colors.textMuted }]}>
            ตำแหน่งไรเดอร์แสดงแบบคร่าวๆ ~{fuzzM} ม.{coordsSource === 'address' ? ' · ใช้ที่อยู่หลักของคุณ' : ''}
          </Text>
        </View>
      </View>

      {/* ---------- แผ่นรายการ ---------- */}
      <View style={[styles.sheet, { backgroundColor: colors.background }]}>
        <View style={[styles.handle, { backgroundColor: colors.border }]} />
        <ScrollView contentContainerStyle={[styles.sheetContent, { paddingBottom: insets.bottom + spacing.xxl }]} showsVerticalScrollIndicator={false}>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
            <Chip label="คนโปรด" icon="heart" selected={mode === 'favorites'} onPress={() => setMode('favorites')} />
            <Chip label="ใกล้ที่สุด" icon="navigation-arrow" selected={mode === 'nearest'} onPress={() => setMode('nearest')} />
            <Chip label="หัวใจเยอะ" icon="trophy" selected={mode === 'hearts'} onPress={() => setMode('hearts')} />
          </ScrollView>

          {!!chosen && (
            <View style={[styles.chosenBanner, { backgroundColor: colors.goldSoft }]}>
              <Icon name="lock" size={16} color={colors.goldDeep} weight="fill" />
              <Text style={[typography.caption, styles.flex, { color: colors.goldDeep }]}>
                ออเดอร์ถัดไปจะล็อกเรียก {chosen.rider.display_name} ก่อน
              </Text>
              <Text onPress={unlock} accessibilityRole="button" style={[typography.caption, styles.bold, { color: colors.goldDeep }]}>
                ยกเลิก
              </Text>
            </View>
          )}

          {showFavorites && (
            <>
              <SectionHeader title="ไรเดอร์คนโปรดของคุณ" style={styles.sectionFirst} />
              {favorites.length > 0 ? (
                favorites.map(favoriteCard)
              ) : (
                <Card3D variant="inset" padding={spacing.lg} radius={20} style={styles.block}>
                  <View style={styles.row}>
                    <Icon name="heart" size={26} color={colors.danger} />
                    <Text style={[typography.bodySm, styles.flex, { color: colors.textMuted }]}>
                      ยังไม่มีไรเดอร์คนโปรด ให้หัวใจไรเดอร์หลังรับของ คนที่คุณให้หัวใจจะมาอยู่ตรงนี้ ครบ {lockMin} ดวงล็อกเรียกได้
                    </Text>
                  </View>
                </Card3D>
              )}
            </>
          )}

          <SectionHeader
            title={showFavorites ? 'ใกล้คุณตอนนี้' : mode === 'hearts' ? 'หัวใจเยอะที่สุดใกล้คุณ' : 'ใกล้คุณที่สุด'}
            actionLabel={!showAll && sortedNearby.length > NEARBY_PREVIEW ? 'ดูทั้งหมด' : undefined}
            onAction={() => setShowAll(true)}
            style={showFavorites ? styles.section : styles.sectionFirst}
          />
          {loading ? (
            <ActivityIndicator color={colors.gold} style={styles.loader} />
          ) : !coords ? (
            <EmptyState
              compact
              art="scooter"
              title="บอกตำแหน่งก่อนนะ"
              message="ใช้ตำแหน่งตอนนี้ หรือปักหมุดที่อยู่หลัก เพื่อดูไรเดอร์รอบตัว"
              actionLabel="ใช้ตำแหน่งตอนนี้"
              onAction={locateMe}
            />
          ) : nearbyError && nearby.length === 0 ? (
            <EmptyState compact variant="error" message={nearbyError} onAction={loadNearby} />
          ) : visibleNearby.length === 0 ? (
            <EmptyState
              compact
              art="scooter"
              title={nearby.length > 0 ? 'ไรเดอร์ที่ใกล้คุณเป็นคนโปรดทั้งหมด' : 'ยังไม่มีไรเดอร์ออนไลน์ใกล้คุณ'}
              message={nearby.length > 0 ? 'ดูได้ในส่วนคนโปรดด้านบน' : `ในรัศมี ${radiusKm} กม. ตอนนี้ยังไม่มีไรเดอร์ ระบบจะหาไรเดอร์ให้ตอนสั่งอยู่แล้ว`}
            />
          ) : (
            <Card3D padding={0} radius={22} style={styles.block}>
              {visibleNearby.map(nearbyRow)}
            </Card3D>
          )}
        </ScrollView>
      </View>
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
  bold: {
    fontWeight: '700',
  },
  centerText: {
    textAlign: 'center',
  },
  mapPlaceholder: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.xxl,
  },
  floatHeader: {
    position: 'absolute',
    left: spacing.screen,
    right: spacing.screen,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  squareBtn: {
    width: 48,
    height: 48,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  titlePill: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    height: 48,
    borderRadius: 16,
    paddingHorizontal: spacing.md,
  },
  liveDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  privacy: {
    position: 'absolute',
    left: spacing.screen,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: radii.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
  },
  sheet: {
    flex: 1,
    marginTop: -26,
    borderTopLeftRadius: 26,
    borderTopRightRadius: 26,
    overflow: 'hidden',
  },
  handle: {
    alignSelf: 'center',
    width: 44,
    height: 5,
    borderRadius: 3,
    marginTop: spacing.sm,
  },
  sheetContent: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
  },
  chips: {
    gap: spacing.sm,
    paddingBottom: spacing.xs,
  },
  chosenBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    marginTop: spacing.md,
  },
  sectionFirst: {
    marginTop: spacing.lg,
  },
  section: {
    marginTop: spacing.xl,
  },
  block: {
    marginBottom: spacing.md,
  },
  loader: {
    marginVertical: spacing.xl,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  nameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  heartLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    marginTop: 3,
  },
  track: {
    height: 8,
    borderRadius: 4,
    overflow: 'hidden',
    marginTop: spacing.sm,
    marginBottom: spacing.xs,
  },
  fill: {
    height: 8,
    borderRadius: 4,
  },
  gapTop: {
    marginTop: spacing.md,
  },
  chosenRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  listRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: 14,
  },
  heartCount: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    minWidth: 44,
    justifyContent: 'flex-end',
  },
});
