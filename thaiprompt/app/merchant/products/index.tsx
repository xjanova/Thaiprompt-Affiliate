/**
 * สินค้าของร้าน — GET /seller/products (กติกาเดียวกับหลังร้านเว็บ /seller/products)
 *
 * - ค้นหาชื่อ/SKU (หน่วงพิมพ์ 400ms) · แท็บกรอง: ทั้งหมด / ขายอยู่ / ปิดขาย / สินค้าหมด / ใกล้หมด / ถูกระงับ (มีตัวเลข)
 * - สวิตช์เปิด/ปิดขายบนการ์ด (เปลี่ยนทันทีบนจอ ล้มเหลว = คืนค่าเดิม + แจ้งเหตุผล)
 * - ปรับสต็อกเร็วๆ ในแผ่นล่าง · แตะการ์ด = หน้าแก้สินค้าเต็ม · ปุ่มทอง "เพิ่มสินค้าใหม่"
 * - สินค้าถูกระงับ/มีตัวเลือกย่อย → ป้ายบอกเหตุผล (แก้ในแอปไม่ได้)
 * - ยังไม่ผ่านด่านผู้ขาย (ยังไม่เปิดร้าน / KYC / แพ็กเกจ / ร้านถูกระงับ) → การ์ดบอกขั้นถัดไป
 * - โหลดครั้งแรกเป็นโครงกระพริบ · ดึงลงเพื่อรีเฟรช · กลับจากหน้าแก้ = ดึงหน้าแรกใหม่เงียบๆ
 *
 * หน้าตา: ช่องค้นหาลอย → ชิปตัวกรอง → ปุ่มทองเพิ่มสินค้า → การ์ดสินค้า (รูปมุม 18 · ราคาทอง · ป้ายสต็อก · สวิตช์)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, FlatList, Pressable, RefreshControl, ScrollView, StyleSheet, Switch, View } from 'react-native';
import { router, useFocusEffect } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { useAuthStore } from '@/stores/authStore';
import {
  getSellerProducts,
  setSellerProductActive,
  setSellerProductStock,
  type SellerProductCounts,
  type SellerProductFilter,
  type SellerProductListItem,
} from '@/services/api/sellerProductApi';
import type { ApiFailure } from '@/services/api/client';
import { Button3D, Card3D, Chip, EmptyState, Pill, PriceText, Screen, resultHaptic, tapHaptic } from '@/components/ui';
import { Field, FormSheet, SearchField, ThumbImage } from '@/components/shop';
import { ProductListSkeleton, SellerGateNotice, isGateFailure, parseInteger } from '@/components/seller';
import { useTheme, radii, spacing, typography } from '@/theme';

const FILTERS: { key: SellerProductFilter; label: string }[] = [
  { key: 'all', label: 'ทั้งหมด' },
  { key: 'active', label: 'ขายอยู่' },
  { key: 'hidden', label: 'ปิดขาย' },
  { key: 'out_of_stock', label: 'สินค้าหมด' },
  { key: 'low_stock', label: 'ใกล้หมด' },
  { key: 'blocked', label: 'ถูกระงับ' },
];

const EMPTY_TEXT: Record<SellerProductFilter, string> = {
  all: 'ยังไม่มีสินค้า เพิ่มสินค้าชิ้นแรกได้เลย',
  active: 'ยังไม่มีสินค้าที่เปิดขายอยู่',
  hidden: 'ไม่มีสินค้าที่ปิดขาย',
  out_of_stock: 'ไม่มีสินค้าหมด เยี่ยมมาก',
  low_stock: 'ไม่มีสินค้าใกล้หมด',
  blocked: 'ไม่มีสินค้าถูกระงับ',
};

const SEARCH_DELAY_MS = 400;

type LoadMode = 'initial' | 'refresh' | 'more' | 'silent';

export default function SellerProductsScreen() {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [filter, setFilter] = useState<SellerProductFilter>('all');
  const [searchText, setSearchText] = useState('');
  const [search, setSearch] = useState('');
  const [products, setProducts] = useState<SellerProductListItem[]>([]);
  const [counts, setCounts] = useState<SellerProductCounts | null>(null);
  const [hasMore, setHasMore] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [gate, setGate] = useState<ApiFailure | null>(null);

  const [stockTarget, setStockTarget] = useState<SellerProductListItem | null>(null);
  const [stockText, setStockText] = useState('');
  const [stockError, setStockError] = useState<string | null>(null);
  const [stockBusy, setStockBusy] = useState(false);

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);
  const pageRef = useRef(1);
  const loadedOnceRef = useRef(false);
  const togglingRef = useRef(new Set<number>());
  const lastOpenRef = useRef(0);
  const abortRef = useRef<AbortController | null>(null);
  /** ตัวกรอง/คำค้นล่าสุด (ใช้ตอนกลับเข้าหน้าหรือรีเฟรชเงียบๆ ไม่ให้ใช้ค่าเก่าที่ค้างใน closure) */
  const filterRef = useRef<SellerProductFilter>('all');
  const searchRef = useRef('');

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      abortRef.current?.abort();
    };
  }, []);

  // หน่วงคำค้น — ไม่ยิง API ทุกตัวอักษร
  useEffect(() => {
    const timer = setTimeout(() => setSearch(searchText.trim()), SEARCH_DELAY_MS);
    return () => clearTimeout(timer);
  }, [searchText]);

  const load = useCallback(
    async (mode: LoadMode, target: SellerProductFilter, keyword: string, page: number) => {
      if (!isAuthenticated) {
        setInitialLoading(false);
        return;
      }
      // ยกเลิกคำขอเก่าที่ยังไม่กลับ (เปลี่ยนแท็บ/พิมพ์ค้นหาเร็วๆ) — ผลเก่าจะไม่ทับผลใหม่
      if (mode !== 'more') abortRef.current?.abort();
      const controller = new AbortController();
      if (mode !== 'more') abortRef.current = controller;
      const requestId = ++requestIdRef.current;

      if (mode === 'initial') setInitialLoading(true);
      if (mode === 'refresh') setRefreshing(true);
      if (mode === 'more') setLoadingMore(true);

      const res = await getSellerProducts({ filter: target, search: keyword, page }, controller.signal);
      if (!mountedRef.current || requestId !== requestIdRef.current) {
        if (mountedRef.current && mode === 'more') setLoadingMore(false);
        return;
      }

      if (res.success) {
        setProducts((prev) => {
          if (mode !== 'more') return res.data.products;
          const seen = new Set(prev.map((p) => p.id));
          return [...prev, ...res.data.products.filter((p) => !seen.has(p.id))];
        });
        setCounts(res.data.counts);
        pageRef.current = page;
        setHasMore(res.data.pagination.current_page < res.data.pagination.last_page);
        setError(null);
        setGate(null);
        loadedOnceRef.current = true;
      } else if (isGateFailure(res)) {
        setGate(res);
      } else if (mode === 'initial' || mode === 'refresh') {
        // โหลดซ้ำล้ม → เก็บรายการเดิมไว้ บอกผ่านแถบข้อความแทน
        setError(res.message);
      } else if (mode === 'more') {
        setHasMore(false);
      }
      setInitialLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    },
    [isAuthenticated]
  );

  // เปลี่ยนแท็บ = โหลดใหม่พร้อมโครงกระพริบ · เปลี่ยนคำค้น = โหลดเงียบๆ (รายการเดิมค้างไว้จนผลใหม่มา)
  useEffect(() => {
    const filterChanged = filterRef.current !== filter;
    filterRef.current = filter;
    searchRef.current = search;
    pageRef.current = 1;
    load(!loadedOnceRef.current || filterChanged ? 'initial' : 'silent', filter, search, 1);
  }, [filter, search, load]);

  /** รีเฟรชหน้าแรกเงียบๆ ด้วยตัวกรองปัจจุบัน (เลื่อนไปหน้าถัดๆ ไปแล้ว = ไม่ดึงใหม่ กันรายการกระโดดกลับบนสุด) */
  const reloadSilently = useCallback(() => {
    if (pageRef.current > 1) return;
    load('silent', filterRef.current, searchRef.current, 1);
  }, [load]);

  // กลับจากหน้าเพิ่ม/แก้สินค้า → ดึงหน้าแรกใหม่เงียบๆ
  useFocusEffect(
    useCallback(() => {
      if (loadedOnceRef.current) reloadSilently();
    }, [reloadSilently])
  );

  const replaceProduct = (updated: SellerProductListItem) =>
    setProducts((prev) => prev.map((p) => (p.id === updated.id ? { ...p, ...updated } : p)));

  const toggleActive = async (product: SellerProductListItem, next: boolean) => {
    if (togglingRef.current.has(product.id)) return;
    if (product.is_blocked) {
      Alert.alert('เปิดขายไม่ได้', product.read_only_reason || 'สินค้านี้ถูกระงับโดยทีมงาน');
      return;
    }
    togglingRef.current.add(product.id);
    replaceProduct({ ...product, is_active: next });
    const res = await setSellerProductActive(product.id, next);
    togglingRef.current.delete(product.id);
    if (!mountedRef.current) return;
    if (res.success) {
      resultHaptic('success');
      replaceProduct(res.data);
      // ตัวเลขบนแท็บเปลี่ยน → ดึงใหม่เงียบๆ
      reloadSilently();
    } else {
      replaceProduct(product);
      resultHaptic('error');
      Alert.alert('เปลี่ยนไม่สำเร็จ', res.message);
    }
  };

  const openStock = (product: SellerProductListItem) => {
    setStockText(String(product.stock_quantity));
    setStockError(null);
    setStockTarget(product);
  };

  const submitStock = async () => {
    if (!stockTarget || stockBusy) return;
    const qty = parseInteger(stockText);
    if (qty === null || Number.isNaN(qty) || qty > 1_000_000) {
      setStockError('จำนวนต้องเป็นเลขจำนวนเต็ม 0 – 1,000,000');
      return;
    }
    if (qty === stockTarget.stock_quantity) {
      setStockTarget(null);
      return;
    }
    setStockBusy(true);
    const res = await setSellerProductStock(stockTarget.id, qty);
    if (!mountedRef.current) return;
    setStockBusy(false);
    if (res.success) {
      resultHaptic('success');
      replaceProduct(res.data);
      setStockTarget(null);
      reloadSilently();
    } else {
      setStockError(res.message);
    }
  };

  /** เปิดหน้าแก้ (กันแตะซ้ำจนเปิดซ้อน 2 ชั้น) */
  const openEditor = (id: number | 'new') => {
    const now = Date.now();
    if (now - lastOpenRef.current < 800) return;
    lastOpenRef.current = now;
    tapHaptic();
    router.push(`/merchant/products/${id}` as never);
  };

  const renderItem = ({ item }: { item: SellerProductListItem }) => {
    const stockPill = !item.track_inventory ? (
      <Pill label="ไม่นับสต็อก" tone="neutral" />
    ) : item.is_out_of_stock ? (
      <Pill label="สินค้าหมด" tone="danger" icon="warning-circle" />
    ) : item.is_low_stock ? (
      <Pill label={`ใกล้หมด · เหลือ ${item.stock_quantity.toLocaleString('th-TH')}`} tone="warning" />
    ) : (
      <Pill label={`เหลือ ${item.stock_quantity.toLocaleString('th-TH')}`} tone="neutral" />
    );

    return (
      <Card3D padding={0} radius={radii.xl} shadow="sm" style={styles.card} contentStyle={styles.clip}>
        <Pressable
          onPress={() => openEditor(item.id)}
          accessibilityRole="button"
          accessibilityLabel={`${item.name} ราคา ${item.price} บาท ${item.is_active ? 'เปิดขาย' : 'ปิดขาย'}`}
          accessibilityHint="แตะเพื่อแก้ไขสินค้า"
          style={({ pressed }) => [styles.cardBody, pressed && styles.pressed]}
        >
          <ThumbImage uri={item.main_image} size={76} radius={18} style={!item.is_active ? styles.dim : undefined} />
          <View style={styles.flex}>
            <Text numberOfLines={2} style={[typography.bodyStrong, { color: colors.textStrong }]}>
              {item.name}
            </Text>
            {!!item.category?.name && (
              <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
                {item.category.name}
                {item.sku ? ` · ${item.sku}` : ''}
              </Text>
            )}
            <View style={styles.priceRow}>
              <PriceText amount={item.price} size="md" tone="gold" />
              {item.compare_at_price !== null && item.compare_at_price > item.price && (
                <PriceText amount={item.compare_at_price} size="xs" tone="muted" strike bold={false} />
              )}
            </View>
            <View style={styles.pills}>
              {stockPill}
              {item.is_blocked && <Pill label="ถูกระงับ" tone="danger" icon="prohibit" />}
              {!item.is_blocked && item.has_variants && <Pill label="มีตัวเลือกย่อย" tone="info" icon="list" />}
              {!item.is_blocked && item.is_hidden && <Pill label="ทีมงานซ่อนไว้" tone="warning" icon="eye-slash" />}
            </View>
          </View>
        </Pressable>
        <View style={[styles.cardFoot, { borderTopColor: colors.divider }]}>
          <View style={styles.switchWrap}>
            <Switch
              value={item.is_active}
              onValueChange={(next) => toggleActive(item, next)}
              disabled={item.is_blocked}
              trackColor={{ false: colors.border, true: colors.success }}
              thumbColor={colors.card}
              accessibilityLabel={`${item.name} เปิดขาย`}
            />
            <Text style={[typography.caption, { color: item.is_active ? colors.success : colors.textMuted }]}>
              {item.is_blocked ? 'ถูกระงับ' : item.is_active ? 'เปิดขาย' : 'ปิดขาย'}
            </Text>
          </View>
          {!item.read_only && item.track_inventory && (
            <Button3D title="ปรับสต็อก" icon="package" size="sm" variant="secondary" onPress={() => openStock(item)} />
          )}
          <Button3D title={item.read_only ? 'ดู' : 'แก้ไข'} icon={item.read_only ? 'eye' : 'pencil-simple'} size="sm" variant="ghost" onPress={() => openEditor(item.id)} />
        </View>
      </Card3D>
    );
  };

  // =====================================================
  // แสดงผล
  // =====================================================

  if (!isAuthenticated) {
    return (
      <Screen title="สินค้าของร้าน" scroll={false}>
        <EmptyState art="bag" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (gate) {
    return (
      <Screen title="สินค้าของร้าน" onRefresh={() => load('refresh', filter, search, 1)} refreshing={refreshing}>
        <SellerGateNotice failure={gate} />
      </Screen>
    );
  }

  const header = (
    <View>
      <SearchField
        value={searchText}
        onChangeText={setSearchText}
        placeholder="ค้นหาชื่อสินค้า หรือ SKU"
        returnKeyType="search"
        maxLength={100}
        style={styles.search}
      />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filters}>
        {FILTERS.map((f) => (
          <Chip
            key={f.key}
            label={f.label}
            size="sm"
            selected={filter === f.key}
            count={counts ? counts[f.key] : undefined}
            tone={f.key === 'blocked' && (counts?.blocked ?? 0) > 0 ? 'danger' : f.key === 'out_of_stock' && (counts?.out_of_stock ?? 0) > 0 ? 'warning' : 'neutral'}
            onPress={() => setFilter(f.key)}
          />
        ))}
      </ScrollView>
      <Button3D title="เพิ่มสินค้าใหม่" icon="plus" size="lg" fullWidth onPress={() => openEditor('new')} style={styles.create} />
      {!!error && products.length > 0 && (
        <Text style={[typography.caption, styles.inlineError, { color: colors.danger }]} accessibilityRole="alert">
          {error}
        </Text>
      )}
    </View>
  );

  return (
    <Screen title="สินค้าของร้าน" subtitle="เพิ่ม แก้ไข เปิด-ปิดขาย" scroll={false}>
      <FlatList
        data={initialLoading ? [] : products}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderItem}
        ListHeaderComponent={header}
        keyboardShouldPersistTaps="handled"
        ListEmptyComponent={
          initialLoading ? (
            <ProductListSkeleton count={5} />
          ) : error ? (
            <EmptyState compact variant="error" message={error} onAction={() => load('refresh', filter, search, 1)} />
          ) : (
            <EmptyState
              compact
              art="bag"
              title={search ? 'ไม่พบสินค้าที่ค้นหา' : 'ยังไม่มีสินค้าในแท็บนี้'}
              message={search ? `ไม่มีสินค้าที่ตรงกับ "${search}"` : EMPTY_TEXT[filter]}
              actionLabel={filter === 'all' && !search ? 'เพิ่มสินค้าแรก' : undefined}
              onAction={filter === 'all' && !search ? () => openEditor('new') : undefined}
            />
          )
        }
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        onEndReachedThreshold={0.4}
        onEndReached={() => {
          if (hasMore && !loadingMore && !initialLoading && !refreshing) load('more', filter, search, pageRef.current + 1);
        }}
        ListFooterComponent={loadingMore ? <ActivityIndicator color={colors.gold} style={styles.more} /> : null}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load('refresh', filter, search, 1)}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
          />
        }
      />

      <FormSheet
        visible={!!stockTarget}
        icon="package"
        title="ปรับสต็อก"
        description={stockTarget?.name}
        submitLabel="บันทึกสต็อก"
        onSubmit={submitStock}
        busy={stockBusy}
        cancelLabel="ยกเลิก"
        onClose={() => setStockTarget(null)}
      >
        <Field
          label="จำนวนคงเหลือ"
          value={stockText}
          onChangeText={(t) => {
            setStockText(t.replace(/[^0-9]/g, '').slice(0, 7));
            setStockError(null);
          }}
          keyboardType="number-pad"
          error={stockError}
          hint="ใส่ 0 = สินค้าหมด ลูกค้าจะสั่งไม่ได้จนกว่าจะเติม"
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
  list: {
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.xxxl * 2,
  },
  search: {
    marginTop: spacing.xs,
  },
  filters: {
    gap: spacing.sm,
    paddingVertical: spacing.md,
  },
  create: {
    marginBottom: spacing.lg,
  },
  inlineError: {
    marginTop: -spacing.sm,
    marginBottom: spacing.md,
  },
  card: {
    marginBottom: spacing.md,
  },
  clip: {
    overflow: 'hidden',
  },
  cardBody: {
    flexDirection: 'row',
    gap: spacing.md,
    padding: spacing.md,
  },
  pressed: {
    opacity: 0.8,
  },
  dim: {
    opacity: 0.55,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'baseline',
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  cardFoot: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderTopWidth: 1,
  },
  switchWrap: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
  },
  more: {
    marginVertical: spacing.lg,
  },
});
