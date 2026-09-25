/**
 * สินค้าของร้านตลาดสด — GET /fresh-market/seller/listings
 *
 * - เปิด/ปิดขายด้วยสวิตช์ (เปลี่ยนทันทีบนจอ ล้มเหลว = คืนค่าเดิม + แจ้งเหตุผล)
 * - แก้ราคาเร็วๆ ในแผ่นล่าง · แตะการ์ด = แก้รูป ราคา และกลุ่มตัวเลือก
 * - ลงขายสินค้าใหม่ → เว็บไซต์ (ฟอร์มเต็ม: หมวดหมู่ รูป สต็อก)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, FlatList, Pressable, RefreshControl, ScrollView, StyleSheet, Switch, Text, View } from 'react-native';
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
import { Button3D, Card3D, Chip, EmptyState, Pill, PriceText, Screen, WebsiteButton, resultHaptic, tapHaptic } from '@/components/ui';
import { FormSheet, Field } from '@/components/shop';
import { FM_LISTING_FILTERS, FM_LISTING_STATUS } from '@/components/merchant';
import { useTheme, radii, spacing, typography } from '@/theme';

type Filter = FmListingStatus | 'all';

const parseBaht = (text: string): number | null => {
  const clean = text.replace(/[,\s฿]/g, '');
  if (!/^\d+(\.\d{1,2})?$/.test(clean)) return null;
  const n = Number(clean);
  return Number.isFinite(n) && n >= 1 && n <= 1_000_000 ? n : null;
};

export default function TaladsodListingsScreen() {
  const { colors } = useTheme();
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

  /**
   * การ์ดสินค้า: พื้นที่กดเปิดหน้าแก้ (รูป ชื่อ ราคา) แยกจากแถวปุ่ม "แก้ราคา" + สวิตช์เปิดขาย
   * — ถ้าทั้งการ์ดเป็นปุ่มเดียว iOS จะรวมลูกเป็น element เดียว VoiceOver กดสวิตช์/ปุ่มข้างในไม่ได้
   */
  const renderItem = ({ item }: { item: FmOwnerListing }) => {
    const img = fmImageUrl(item.main_image_url || item.images[0]);
    const st = FM_LISTING_STATUS[item.status] || { label: item.status, tone: 'neutral' as const };
    const groups = item.option_groups.length;
    return (
      <Card3D padding={spacing.md} radius={radii.lg} shadow="sm" style={styles.card}>
        <Pressable
          onPress={() => openEditor(item)}
          accessibilityRole="button"
          accessibilityLabel={`${item.title} ราคา ${item.price} บาท ${item.is_available ? 'เปิดขาย' : 'ปิดขาย'}`}
          accessibilityHint="แตะเพื่อแก้รูป ราคา และตัวเลือก"
          style={({ pressed }) => [styles.row, pressed && styles.pressed]}
        >
          {img ? (
            <Image source={{ uri: img }} style={[styles.thumb, { backgroundColor: colors.inset }]} contentFit="cover" />
          ) : (
            <View style={[styles.thumb, styles.center, { backgroundColor: colors.inset }]}>
              <Text style={styles.thumbIcon}>🥬</Text>
            </View>
          )}
          <View style={styles.flex}>
            <Text numberOfLines={2} style={[typography.bodyStrong, { color: colors.textStrong }]}>
              {item.title}
            </Text>
            <View style={styles.pills}>
              <Pill label={st.label} tone={st.tone} />
              {groups > 0 && <Pill label={`ตัวเลือก ${groups} กลุ่ม`} tone="gold" icon="🧩" />}
              {item.track_stock ? (
                <Pill label={`เหลือ ${item.quantity_available.toLocaleString('th-TH')}`} tone={item.quantity_available > 0 ? 'neutral' : 'danger'} />
              ) : (
                <Pill label="ทำตามสั่ง" tone="info" />
              )}
            </View>
          </View>
        </Pressable>
        <View style={[styles.bottom, { borderTopColor: colors.divider }]}>
          {/* ราคาแตะแล้วเปิดหน้าแก้เหมือนเดิม — ซ่อนจาก screen reader เพราะปุ่มด้านบนอ่านราคาแล้ว */}
          <Pressable
            onPress={() => openEditor(item)}
            style={({ pressed }) => [styles.flex, pressed && styles.pressed]}
            accessible={false}
            importantForAccessibility="no-hide-descendants"
            accessibilityElementsHidden
          >
            <PriceText amount={item.price} size="lg" tone="gold" suffix={item.unit ? `/${item.unit}` : undefined} />
          </Pressable>
          <Button3D title="แก้ราคา" icon="✏️" size="sm" variant="secondary" onPress={() => openPrice(item)} />
          <View style={styles.switchBox}>
            <Switch
              value={item.is_available}
              onValueChange={(next) => toggleAvailable(item, next)}
              disabled={item.status === 'suspended'}
              trackColor={{ false: colors.border, true: colors.success }}
              thumbColor={colors.card}
              accessibilityLabel={`${item.title} เปิดขาย`}
            />
            <Text style={[typography.micro, { color: item.is_available ? colors.success : colors.textMuted }]}>
              {item.is_available ? 'เปิดขาย' : 'ปิดขาย'}
            </Text>
          </View>
        </View>
      </Card3D>
    );
  };

  if (!isAuthenticated) {
    return (
      <Screen title="สินค้าของร้าน" scroll={false}>
        <EmptyState icon="🔐" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (notSeller) {
    return (
      <Screen title="สินค้าของร้าน" scroll={false} contentStyle={styles.pad}>
        <EmptyState icon="🥬" title="ยังไม่มีร้านในตลาดสด" message="สมัครขายบนเว็บไซต์ แล้วกลับมาจัดการสินค้าในแอปได้เลย" />
        <WebsiteButton path="/taladsod/register-seller" label="สมัครขายในตลาดสด" variant="primary" fullWidth />
      </Screen>
    );
  }

  const header = (
    <View>
      <WebsiteButton
        path="/taladsod/create-listing"
        label="ลงขายสินค้าใหม่ (บนเว็บ)"
        icon="➕"
        variant="primary"
        fullWidth
        style={styles.create}
      />
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
        ListHeaderComponent={header}
        ListEmptyComponent={
          initialLoading ? (
            <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
          ) : error ? (
            <EmptyState compact variant="error" message={error} onAction={() => load('refresh', filter, 1)} />
          ) : (
            <EmptyState compact icon="🧺" title="ยังไม่มีสินค้าในแท็บนี้" message="ลงขายสินค้าใหม่บนเว็บ แล้วกลับมาแก้ราคาและตัวเลือกในแอปได้" />
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
        icon="✏️"
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
  center: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  pad: {
    paddingHorizontal: spacing.screen,
  },
  list: {
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.xxxl * 2,
  },
  create: {
    marginBottom: spacing.md,
  },
  filters: {
    gap: spacing.xs,
    paddingBottom: spacing.md,
  },
  card: {
    marginBottom: spacing.md,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  pressed: {
    opacity: 0.7,
  },
  thumb: {
    width: 64,
    height: 64,
    borderRadius: radii.md,
  },
  thumbIcon: {
    fontSize: 26,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  bottom: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    paddingTop: spacing.sm,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
  switchBox: {
    alignItems: 'center',
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  more: {
    marginVertical: spacing.lg,
  },
});
