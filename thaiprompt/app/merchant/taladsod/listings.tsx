/**
 * สินค้าของร้านตลาดสด — GET /fresh-market/seller/listings
 *
 * - เปิด/ปิดขายด้วยสวิตช์ (เปลี่ยนทันทีบนจอ ล้มเหลว = คืนค่าเดิม + แจ้งเหตุผล)
 * - แก้ราคาเร็วๆ ในแผ่นล่าง · แตะการ์ด = แก้ข้อมูลทั้งหมด รูป กลุ่มตัวเลือก และลบสินค้า
 * - ลงขายสินค้าใหม่ → หน้าลงขายในแอป (merchant/taladsod/listing/new)
 *
 * หน้าตา: กริดสองคอลัมน์ รูปสินค้าเด่น + ป้ายสถานะบนรูป · ราคาทอง · แถวเปิดขาย · ปุ่มแก้ราคา
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Switch,
  View,
  useWindowDimensions,
} from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  fmImageUrl,
  getFmSellerListings,
  updateFmListing,
  type FmListingStatus,
  type FmOwnerListing,
} from '@/services/api/taladsodSellerApi';
import {
  BrandArt,
  Button3D,
  Card3D,
  Chip,
  EmptyState,
  Icon,
  Pill,
  PriceText,
  Screen,
  resultHaptic,
  tapHaptic,
} from '@/components/ui';
import { FormSheet, Field } from '@/components/shop';
import { FM_LISTING_FILTERS, FM_LISTING_STATUS } from '@/components/merchant';
import { SkeletonBlock } from '@/components/merchant/SkeletonBlock';
import { DARK_THEME, useTheme, radii, spacing, toneColors, typography } from '@/theme';

/** ระยะห่างระหว่างคอลัมน์ของกริด */
const GRID_GAP = spacing.md;

type Filter = FmListingStatus | 'all';

const parseBaht = (text: string): number | null => {
  const clean = text.replace(/[,\s฿]/g, '');
  if (!/^\d+(\.\d{1,2})?$/.test(clean)) return null;
  const n = Number(clean);
  return Number.isFinite(n) && n >= 1 && n <= 1_000_000 ? n : null;
};

export default function TaladsodListingsScreen() {
  const { colors } = useTheme();
  const { width } = useWindowDimensions();
  // ความกว้างการ์ดในกริดสองคอลัมน์ (หักขอบจอซ้ายขวาและช่องว่างกลาง)
  const cardWidth = Math.floor((width - spacing.screen * 2 - GRID_GAP) / 2);
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [filter, setFilter] = useState<Filter>('all');
  const [listings, setListings] = useState<FmOwnerListing[]>([]);
  const [hasMore, setHasMore] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notSeller, setNotSeller] = useState(false);

  const [priceTarget, setPriceTarget] = useState<FmOwnerListing | null>(null);
  const [priceText, setPriceText] = useState('');
  const [priceBusy, setPriceBusy] = useState(false);
  const [priceError, setPriceError] = useState<string | null>(null);

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);
  const pageRef = useRef(1);
  const togglingRef = useRef(new Set<number>());
  const loadedOnceRef = useRef(false);
  /** กันแตะการ์ดซ้ำจนเปิดหน้าแก้สินค้าซ้อน 2 ชั้น */
  const lastOpenRef = useRef(0);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'more' | 'silent', target: Filter, page: number) => {
      if (!isAuthenticated) {
        setInitialLoading(false);
        return;
      }
      const requestId = ++requestIdRef.current;
      if (mode === 'initial') setInitialLoading(true);
      if (mode === 'refresh') setRefreshing(true);
      if (mode === 'more') setLoadingMore(true);

      const res = await getFmSellerListings({ status: target === 'all' ? undefined : target, page });
      if (!mountedRef.current || requestId !== requestIdRef.current) return;

      if (res.success) {
        const list = res.data.listings;
        setListings((prev) => {
          if (mode !== 'more') return list;
          const seen = new Set(prev.map((l) => l.id));
          return [...prev, ...list.filter((l) => !seen.has(l.id))];
        });
        pageRef.current = page;
        setHasMore(res.data.pagination.current_page < res.data.pagination.last_page);
        setError(null);
        setNotSeller(false);
        loadedOnceRef.current = true;
      } else if (res.code === 'NOT_SELLER') {
        setNotSeller(true);
      } else if (mode === 'initial' || mode === 'refresh') {
        setError(res.message);
      }
      setInitialLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    },
    [isAuthenticated]
  );

  useEffect(() => {
    setListings([]);
    pageRef.current = 1;
    load('initial', filter, 1);
  }, [filter, load]);

  // กลับจากหน้าแก้สินค้า → ดึงหน้าแรกใหม่เงียบๆ (ไม่ต้อง poll — ข้อมูลเปลี่ยนเพราะร้านเองเท่านั้น)
  useFocusEffect(
    useCallback(() => {
      if (loadedOnceRef.current && pageRef.current === 1) load('silent', filter, 1);
    }, [filter, load])
  );

  const replaceListing = (updated: FmOwnerListing) =>
    setListings((prev) => prev.map((l) => (l.id === updated.id ? updated : l)));

  const toggleAvailable = async (listing: FmOwnerListing, next: boolean) => {
    if (togglingRef.current.has(listing.id)) return;
    if (listing.status === 'suspended') {
      Alert.alert('เปิดขายไม่ได้', 'สินค้านี้ถูกระงับโดยทีมงาน ติดต่อทีมงานได้ที่หน้าช่วยเหลือ');
      return;
    }
    togglingRef.current.add(listing.id);
    replaceListing({ ...listing, is_available: next });
    const res = await updateFmListing(listing.id, { is_available: next });
    togglingRef.current.delete(listing.id);
    if (!mountedRef.current) return;
    if (res.success) {
      resultHaptic('success');
      replaceListing(res.data);
    } else {
      replaceListing(listing);
      resultHaptic('error');
      Alert.alert('เปลี่ยนไม่สำเร็จ', res.message);
    }
  };

  const openPrice = (listing: FmOwnerListing) => {
    setPriceText(String(listing.price));
    setPriceError(null);
    setPriceTarget(listing);
  };

  const submitPrice = async () => {
    if (!priceTarget || priceBusy) return;
    const price = parseBaht(priceText);
    if (price === null) {
      setPriceError('ใส่ราคาเป็นตัวเลข 1 – 1,000,000 บาท');
      return;
    }
    if (price === priceTarget.price) {
      setPriceTarget(null);
      return;
    }
    setPriceBusy(true);
    const res = await updateFmListing(priceTarget.id, { price });
    if (!mountedRef.current) return;
    setPriceBusy(false);
    if (res.success) {
      resultHaptic('success');
      replaceListing(res.data);
      setPriceTarget(null);
    } else {
      setPriceError(res.message);
    }
  };

  const openEditor = (item: FmOwnerListing) => {
    const now = Date.now();
    if (now - lastOpenRef.current < 800) return;
    lastOpenRef.current = now;
    tapHaptic();
    router.push(`/merchant/taladsod/listing/${item.id}` as never);
  };

  const openCreate = () => {
    const now = Date.now();
    if (now - lastOpenRef.current < 800) return;
    lastOpenRef.current = now;
    router.push('/merchant/taladsod/listing/new' as never);
  };

  /**
   * การ์ดสินค้า: พื้นที่กดเปิดหน้าแก้ (รูป ชื่อ ราคา) แยกจากแถวปุ่ม "แก้ราคา" + สวิตช์เปิดขาย
   * — ถ้าทั้งการ์ดเป็นปุ่มเดียว iOS จะรวมลูกเป็น element เดียว VoiceOver กดสวิตช์/ปุ่มข้างในไม่ได้
   */
  const renderItem = ({ item }: { item: FmOwnerListing }) => {
    const img = fmImageUrl(item.main_image_url || item.images[0]);
    const st = FM_LISTING_STATUS[item.status] || { label: item.status, tone: 'neutral' as const };
    const groups = item.option_groups.length;
    // ป้ายบนรูป = พื้นม่านมืดเสมอ → จุดสถานะใช้สีสว่างของชุดสีโหมดมืด
    const statusDot = toneColors(st.tone, DARK_THEME.colors).fg;
    return (
      <Card3D
        padding={0}
        radius={radii.xl}
        shadow="sm"
        style={[styles.card, { width: cardWidth }]}
        contentStyle={styles.clip}
      >
        <Pressable
          onPress={() => openEditor(item)}
          accessibilityRole="button"
          accessibilityLabel={`${item.title} ราคา ${item.price} บาท ${item.is_available ? 'เปิดขาย' : 'ปิดขาย'}`}
          accessibilityHint="แตะเพื่อแก้รูป ราคา และตัวเลือก"
          style={({ pressed }) => [pressed && styles.pressed]}
        >
          <View style={[styles.photo, { height: Math.round(cardWidth * 0.8), backgroundColor: colors.inset }]}>
            {img ? (
              <Image
                source={{ uri: img }}
                style={[styles.photoImage, !item.is_available && styles.photoOff]}
                contentFit="cover"
                transition={120}
              />
            ) : (
              <BrandArt name="basket" size={Math.round(cardWidth * 0.46)} style={!item.is_available && styles.photoOff} />
            )}
            <View style={[styles.photoTag, styles.tagTop, { backgroundColor: colors.overlay }]}>
              <View style={[styles.tagDot, { backgroundColor: statusDot }]} />
              <Text numberOfLines={1} style={[typography.micro, { color: colors.textOnAccent }]}>
                {st.label}
              </Text>
            </View>
            {groups > 0 && (
              <View style={[styles.photoTag, styles.tagBottom, { backgroundColor: colors.overlay }]}>
                <Icon name="list" size={12} color={DARK_THEME.colors.goldLight} weight="bold" />
                <Text numberOfLines={1} style={[typography.micro, { color: colors.textOnAccent }]}>
                  ตัวเลือก {groups} กลุ่ม
                </Text>
              </View>
            )}
          </View>
          <View style={styles.info}>
            <Text numberOfLines={2} style={[typography.bodyStrong, styles.title, { color: colors.textStrong }]}>
              {item.title}
            </Text>
            <View style={styles.pills}>
              {item.track_stock ? (
                <Pill label={`เหลือ ${item.quantity_available.toLocaleString('th-TH')}`} tone={item.quantity_available > 0 ? 'neutral' : 'danger'} />
              ) : (
                <Pill label="ทำตามสั่ง" tone="info" icon="cooking-pot" />
              )}
            </View>
          </View>
        </Pressable>
        <View style={[styles.bottom, { borderTopColor: colors.divider }]}>
          {/* ราคาแตะแล้วเปิดหน้าแก้เหมือนเดิม — ซ่อนจาก screen reader เพราะปุ่มด้านบนอ่านราคาแล้ว */}
          <Pressable
            onPress={() => openEditor(item)}
            style={({ pressed }) => [pressed && styles.pressed]}
            accessible={false}
            importantForAccessibility="no-hide-descendants"
            accessibilityElementsHidden
          >
            <PriceText amount={item.price} size="lg" tone="gold" suffix={item.unit ? `/${item.unit}` : undefined} />
          </Pressable>
          <View style={styles.switchRow}>
            <Text style={[typography.caption, styles.flex, { color: item.is_available ? colors.success : colors.textMuted }]}>
              {item.is_available ? 'เปิดขาย' : 'ปิดขาย'}
            </Text>
            <Switch
              value={item.is_available}
              onValueChange={(next) => toggleAvailable(item, next)}
              disabled={item.status === 'suspended'}
              trackColor={{ false: colors.border, true: colors.success }}
              thumbColor={colors.card}
              accessibilityLabel={`${item.title} เปิดขาย`}
            />
          </View>
          <Button3D title="แก้ราคา" icon="pencil-simple" size="sm" variant="secondary" fullWidth onPress={() => openPrice(item)} />
        </View>
      </Card3D>
    );
  };

  if (!isAuthenticated) {
    return (
      <Screen title="สินค้าของร้าน" scroll={false}>
        <EmptyState art="cart" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (notSeller) {
    return (
      <Screen title="สินค้าของร้าน" scroll={false} contentStyle={styles.pad}>
        <EmptyState art="cart" title="ยังไม่มีร้านในตลาดสด" message="สมัครเปิดร้านในแอปไม่กี่นาที แล้วลงขายสินค้าได้เลย" />
        <Button3D
          title="สมัครเปิดร้านในตลาดสด"
          icon="basket"
          variant="primary"
          fullWidth
          onPress={() => router.push('/merchant/taladsod/register' as never)}
          style={styles.notSellerCta}
        />
      </Screen>
    );
  }

  const header = (
    <View>
      <Button3D title="ลงขายสินค้าใหม่" icon="plus" variant="primary" fullWidth onPress={openCreate} style={styles.create} />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filters}>
        {FM_LISTING_FILTERS.map((f) => (
          <Chip key={f.key} label={f.label} size="sm" selected={filter === f.key} onPress={() => setFilter(f.key)} />
        ))}
      </ScrollView>
    </View>
  );

  return (
    <Screen title="สินค้าของร้าน" subtitle="ราคา เปิด-ปิดขาย และตัวเลือก" scroll={false}>
      <FlatList
        data={listings}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderItem}
        numColumns={2}
        columnWrapperStyle={styles.column}
        ListHeaderComponent={header}
        ListEmptyComponent={
          initialLoading ? (
            <View style={styles.skeletonGrid} accessibilityLabel="กำลังโหลดสินค้า">
              {[0, 1, 2, 3].map((i) => (
                <View key={i} style={[styles.card, { width: cardWidth }]}>
                  <SkeletonBlock height={Math.round(cardWidth * 0.8)} radius={radii.xl} />
                  <SkeletonBlock width="80%" height={16} style={styles.skeletonLine} />
                  <SkeletonBlock width="50%" height={20} style={styles.skeletonLine} />
                </View>
              ))}
            </View>
          ) : error ? (
            <EmptyState compact variant="error" message={error} onAction={() => load('refresh', filter, 1)} />
          ) : (
            <EmptyState
              compact
              art="basket"
              title="ยังไม่มีสินค้าในแท็บนี้"
              message="กด ลงขายสินค้าใหม่ ด้านบน ใส่รูป ราคา และตัวเลือกได้ในแอปเลย"
            />
          )
        }
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        onEndReachedThreshold={0.4}
        onEndReached={() => {
          if (hasMore && !loadingMore && !initialLoading) load('more', filter, pageRef.current + 1);
        }}
        ListFooterComponent={loadingMore ? <ActivityIndicator color={colors.gold} style={styles.more} /> : null}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load('refresh', filter, 1)}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
          />
        }
      />

      <FormSheet
        visible={!!priceTarget}
        icon="pencil-simple"
        title="แก้ราคา"
        description={priceTarget ? `${priceTarget.title}${priceTarget.unit ? ` (ต่อ${priceTarget.unit})` : ''}` : undefined}
        submitLabel="บันทึกราคา"
        onSubmit={submitPrice}
        busy={priceBusy}
        cancelLabel="ยกเลิก"
        onClose={() => setPriceTarget(null)}
      >
        <Field
          label="ราคาขาย (บาท)"
          value={priceText}
          onChangeText={(t) => {
            setPriceText(t.replace(/[^0-9.]/g, '').slice(0, 10));
            setPriceError(null);
          }}
          keyboardType="decimal-pad"
          placeholder="เช่น 50"
          error={priceError}
          hint={priceTarget && priceTarget.option_groups.length > 0 ? 'ราคาตัวเลือก (+฿) คิดเพิ่มจากราคานี้' : undefined}
          autoFocus
        />
      </FormSheet>
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  pad: {
    paddingHorizontal: spacing.screen,
  },
  notSellerCta: {
    marginBottom: spacing.xxl,
  },
  list: {
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.xxxl * 2,
  },
  column: {
    gap: GRID_GAP,
  },
  create: {
    marginTop: spacing.xs,
    marginBottom: spacing.md,
  },
  filters: {
    gap: spacing.xs,
    paddingBottom: spacing.lg,
  },
  card: {
    marginBottom: GRID_GAP,
  },
  clip: {
    overflow: 'hidden',
  },
  pressed: {
    opacity: 0.7,
  },
  // ---------- รูป ----------
  photo: {
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
  },
  photoImage: {
    width: '100%',
    height: '100%',
  },
  photoOff: {
    opacity: 0.45,
  },
  photoTag: {
    position: 'absolute',
    left: spacing.sm,
    maxWidth: '86%',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    borderRadius: 9,
    paddingVertical: 3,
    paddingHorizontal: 8,
  },
  tagTop: {
    top: spacing.sm,
  },
  tagBottom: {
    bottom: spacing.sm,
  },
  tagDot: {
    width: 7,
    height: 7,
    borderRadius: 4,
  },
  // ---------- เนื้อหา ----------
  info: {
    paddingHorizontal: spacing.md,
    paddingTop: spacing.md,
  },
  title: {
    minHeight: 44,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  bottom: {
    gap: spacing.sm,
    marginTop: spacing.md,
    marginHorizontal: spacing.md,
    paddingTop: spacing.sm,
    paddingBottom: spacing.md,
    borderTopWidth: 1,
  },
  switchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
  },
  skeletonGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: GRID_GAP,
  },
  skeletonLine: {
    marginTop: spacing.sm,
  },
  more: {
    marginVertical: spacing.lg,
  },
});
