/**
 * ช้อป — รายการสินค้าจริงจาก GET /products (ธีมนวลทองคำ)
 *
 * - ค้นหา (หน่วง 400ms) · หมวดหมู่ · เรียงลำดับ · เลื่อนโหลดเพิ่ม · ดึงลงเพื่อรีเฟรช
 * - ร้านแนะนำจาก /mobile/stores/featured (ว่าง → ร้านทางการ) · ทางเข้าตลาดสด
 * - ไม่มี PV / คอมมิชชั่น / ข้อมูลสมมติ (SHOP-16/17, นโยบาย Google Play)
 * - ใช้ทั้งแท็บ "ช้อป" (embedded) และหน้า /shopping แบบ stack
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
  useWindowDimensions,
} from 'react-native';
import { Image } from 'expo-image';
import { router } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  getFeaturedStores,
  getOfficialStores,
  getProductCategories,
  getProducts,
  type ProductListParams,
  type ShopCategory,
  type ShopProduct,
  type StoreListItem,
} from '@/services/api/shopApi';
import { isFeatureEnabled } from '@/config/appConfig';
import { BannerCard, Card3D, Chip, EmptyState, Screen, SectionHeader } from '@/components/ui';
import { CartButton, ProductCard } from '@/components/shop';
import { useTheme, clayShadowStyle, radii, spacing, typography } from '@/theme';

const PER_PAGE = 20;
const BANNER_MARKET = require('@/assets/images/taladsod/banner-market.webp');

type SortKey = 'newest' | 'popular' | 'price_asc' | 'price_desc';

const SORTS: Array<{ key: SortKey; label: string; params: Pick<ProductListParams, 'sort' | 'order'> }> = [
  { key: 'newest', label: 'มาใหม่', params: { sort: 'newest', order: 'desc' } },
  { key: 'popular', label: 'ขายดี', params: { sort: 'popular', order: 'desc' } },
  { key: 'price_asc', label: 'ราคาต่ำ → สูง', params: { sort: 'price', order: 'asc' } },
  { key: 'price_desc', label: 'ราคาสูง → ต่ำ', params: { sort: 'price', order: 'desc' } },
];

/** ร้านในแถวแนะนำ */
const StoreBubble: React.FC<{ store: StoreListItem }> = ({ store }) => {
  const { colors } = useTheme();
  return (
    <Card3D
      onPress={() => router.push(`/store/${store.id}` as never)}
      padding={spacing.md}
      radius={radii.lg}
      shadow="sm"
      style={styles.storeCard}
      accessibilityLabel={`ร้าน ${store.name}`}
    >
      {store.logo ? (
        <Image source={{ uri: store.logo }} style={[styles.storeLogo, { backgroundColor: colors.inset }]} contentFit="cover" />
      ) : (
        <View style={[styles.storeLogo, styles.center, { backgroundColor: colors.goldSoft }]}>
          <Text style={styles.storeLogoIcon}>🏪</Text>
        </View>
      )}
      <Text numberOfLines={1} style={[typography.caption, styles.storeName, { color: colors.textStrong }]}>
        {store.name}
      </Text>
      <Text numberOfLines={1} style={[typography.micro, { color: colors.textMuted }]}>
        {store.isOfficial ? '✔ ร้านยืนยันแล้ว' : `${Number(store.productCount) || 0} สินค้า`}
      </Text>
      {store.rider_delivery && <Text style={[typography.micro, { color: colors.success }]}>🛵 ไรเดอร์ส่ง</Text>}
    </Card3D>
  );
};

export default function ShoppingScreen({ embedded = false }: { embedded?: boolean } = {}) {
  const { colors } = useTheme();
  const { width } = useWindowDimensions();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const cardWidth = Math.floor((width - spacing.screen * 2 - spacing.md) / 2);

  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [sort, setSort] = useState<SortKey>('newest');

  const [products, setProducts] = useState<ShopProduct[]>([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [categories, setCategories] = useState<ShopCategory[]>([]);
  const [stores, setStores] = useState<StoreListItem[]>([]);

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // หน่วงการค้นหา 400ms
  useEffect(() => {
    const timer = setTimeout(() => setSearch(searchInput.trim()), 400);
    return () => clearTimeout(timer);
  }, [searchInput]);

  const loadProducts = useCallback(
    async (mode: 'initial' | 'refresh' | 'more', targetPage: number) => {
      if (!isAuthenticated) return;
      const requestId = ++requestIdRef.current;
      if (mode === 'initial') setInitialLoading(true);
      if (mode === 'refresh') setRefreshing(true);
      if (mode === 'more') setLoadingMore(true);

      const sortParams = SORTS.find((s) => s.key === sort)?.params ?? SORTS[0].params;
      const result = await getProducts({
        ...sortParams,
        ...(categoryId ? { category: categoryId } : {}),
        ...(search ? { search } : {}),
        per_page: PER_PAGE,
        page: targetPage,
      });

      // ผู้ใช้เปลี่ยนตัวกรอง/ออกจากหน้าแล้ว → ทิ้งผลเก่า
      if (!mountedRef.current || requestId !== requestIdRef.current) return;

      if (result.success) {
        const list = result.data.products;
        setProducts((prev) => {
          if (mode !== 'more') return list;
          const seen = new Set(prev.map((p) => p.id));
          return [...prev, ...list.filter((p) => !seen.has(p.id))];
        });
        setPage(targetPage);
        const p = result.data.pagination;
        setHasMore(!!p && (p.has_more ?? p.current_page < p.last_page));
        setError(null);
      } else if (mode !== 'more') {
        setError(result.message);
      }

      setInitialLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    },
    [isAuthenticated, sort, categoryId, search]
  );

  const loadExtras = useCallback(async () => {
    if (!isAuthenticated) return;
    const [cats, featured] = await Promise.all([getProductCategories(), getFeaturedStores()]);
    if (!mountedRef.current) return;
    if (cats.success && Array.isArray(cats.data)) {
      setCategories(cats.data.filter((c) => !c.parent_id && Number(c.products_count) > 0));
    }
    if (featured.success && Array.isArray(featured.data) && featured.data.length > 0) {
      setStores(featured.data);
    } else {
      const official = await getOfficialStores();
      if (mountedRef.current && official.success && Array.isArray(official.data)) {
        setStores(official.data);
      }
    }
  }, [isAuthenticated]);

  // โหลดใหม่เมื่อเปลี่ยนตัวกรอง
  useEffect(() => {
    if (!isAuthenticated) {
      setInitialLoading(false);
      return;
    }
    loadProducts('initial', 1);
  }, [isAuthenticated, loadProducts]);

  useEffect(() => {
    loadExtras();
  }, [loadExtras]);

  const onRefresh = () => {
    loadExtras();
    loadProducts('refresh', 1);
  };

  const onEndReached = () => {
    if (hasMore && !loadingMore && !initialLoading && !refreshing) {
      loadProducts('more', page + 1);
    }
  };

  const selectedCategory = useMemo(() => categories.find((c) => c.id === categoryId) || null, [categories, categoryId]);

  // ---------- ส่วนหัวของรายการ ----------
  const header = (
    <View>
      <View
        style={[
          styles.searchBox,
          { backgroundColor: colors.card },
          clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
        ]}
      >
        <Text style={styles.searchIcon}>🔍</Text>
        <TextInput
          value={searchInput}
          onChangeText={setSearchInput}
          placeholder="ค้นหาสินค้า แบรนด์"
          placeholderTextColor={colors.textFaint}
          returnKeyType="search"
          onSubmitEditing={() => setSearch(searchInput.trim())}
          style={[typography.body, styles.searchInput, { color: colors.textStrong }]}
          accessibilityLabel="ค้นหาสินค้า"
          maxLength={100}
        />
        {searchInput.length > 0 && (
          <Pressable onPress={() => setSearchInput('')} accessibilityRole="button" accessibilityLabel="ล้างคำค้นหา" hitSlop={10}>
            <Text style={[typography.h3, { color: colors.textFaint }]}>✕</Text>
          </Pressable>
        )}
      </View>

      {!search && isFeatureEnabled('TALADSOD_ENABLED') && (
        <BannerCard
          image={BANNER_MARKET}
          title="ตลาดสดใกล้บ้าน"
          subtitle="ของสด อาหารร้อนๆ ไรเดอร์ส่งถึงหน้าบ้าน"
          ctaLabel="ไปตลาดสด"
          height={132}
          onPress={() => router.push('/taladsod' as never)}
          style={styles.banner}
        />
      )}

      {!search && stores.length > 0 && (
        <>
          <SectionHeader
            title="ร้านแนะนำ"
            icon="🏪"
            actionLabel="ดูทั้งหมด"
            onAction={() => router.push('/stores' as never)}
            style={styles.section}
          />
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.storeRow}>
            {stores.slice(0, 12).map((s) => (
              <StoreBubble key={s.id} store={s} />
            ))}
          </ScrollView>
        </>
      )}

      {categories.length > 0 && (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
          <Chip label="ทั้งหมด" size="sm" selected={categoryId === null} onPress={() => setCategoryId(null)} />
          {categories.map((c) => (
            <Chip
              key={c.id}
              label={c.name}
              icon={c.icon && c.icon.length <= 4 ? c.icon : undefined}
              size="sm"
              selected={categoryId === c.id}
              onPress={() => setCategoryId(categoryId === c.id ? null : c.id)}
            />
          ))}
        </ScrollView>
      )}

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
        {SORTS.map((s) => (
          <Chip key={s.key} label={s.label} size="sm" tone="gold" selected={sort === s.key} onPress={() => setSort(s.key)} />
        ))}
      </ScrollView>

      <SectionHeader
        title={search ? `ผลการค้นหา "${search}"` : selectedCategory ? selectedCategory.name : 'สินค้าทั้งหมด'}
        style={styles.section}
      />
    </View>
  );

  const renderBody = () => {
    if (!isAuthenticated) {
      return (
        <EmptyState
          icon="🔐"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อดูสินค้าและสั่งซื้อ"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      );
    }

    return (
      <FlatList
        data={initialLoading && products.length === 0 ? [] : products}
        keyExtractor={(item) => String(item.id)}
        numColumns={2}
        columnWrapperStyle={styles.column}
        renderItem={({ item }) => <ProductCard product={item} width={cardWidth} />}
        ListHeaderComponent={header}
        ListEmptyComponent={
          initialLoading ? (
            <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
          ) : error ? (
            <EmptyState compact variant="error" message={error} onAction={() => loadProducts('initial', 1)} />
          ) : (
            <EmptyState
              compact
              icon="🛍️"
              title={search ? 'ไม่พบสินค้าที่ค้นหา' : 'ยังไม่มีสินค้าในหมวดนี้'}
              message={search ? 'ลองใช้คำค้นอื่น หรือดูหมวดอื่นนะ' : 'ลองดูหมวดอื่นก่อนนะ'}
              actionLabel={search || categoryId ? 'ดูสินค้าทั้งหมด' : undefined}
              onAction={
                search || categoryId
                  ? () => {
                      setSearchInput('');
                      setSearch('');
                      setCategoryId(null);
                    }
                  : undefined
              }
            />
          )
        }
        ListFooterComponent={loadingMore ? <ActivityIndicator color={colors.gold} style={styles.footer} /> : <View style={styles.footerSpace} />}
        contentContainerStyle={[styles.list, embedded && styles.listTab]}
        onEndReached={onEndReached}
        onEndReachedThreshold={0.5}
        keyboardShouldPersistTaps="handled"
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
          />
        }
        showsVerticalScrollIndicator={false}
      />
    );
  };

  return (
    <Screen
      title="ช้อป"
      subtitle="ของดีจากร้านค้าทั่วไทย"
      showBack={!embedded}
      scroll={false}
      right={isAuthenticated ? <CartButton /> : undefined}
    >
      {renderBody()}
    </Screen>
  );
}

const styles = StyleSheet.create({
  center: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  list: {
    paddingHorizontal: spacing.screen,
  },
  listTab: {
    paddingBottom: 96,
  },
  column: {
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  searchBox: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.lg,
    paddingHorizontal: spacing.md,
    minHeight: 48,
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  searchIcon: {
    fontSize: 16,
  },
  searchInput: {
    flex: 1,
    paddingVertical: spacing.sm,
  },
  banner: {
    marginBottom: spacing.sm,
  },
  section: {
    marginTop: spacing.md,
  },
  storeRow: {
    gap: spacing.md,
    paddingVertical: spacing.xs,
    paddingRight: spacing.sm,
  },
  storeCard: {
    width: 112,
  },
  storeLogo: {
    width: 52,
    height: 52,
    borderRadius: 16,
    alignSelf: 'center',
  },
  storeLogoIcon: {
    fontSize: 24,
  },
  storeName: {
    marginTop: spacing.sm,
    textAlign: 'center',
  },
  chips: {
    gap: spacing.sm,
    paddingTop: spacing.md,
    paddingRight: spacing.sm,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  footer: {
    marginVertical: spacing.lg,
  },
  footerSpace: {
    height: spacing.xxl,
  },
});
