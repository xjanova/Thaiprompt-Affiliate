/**
 * ช้อป — รายการสินค้าจริงจาก GET /products (ธีมรอยัล น้ำเงินกรมท่า-ทอง)
 *
 * หน้าตา: ช่องค้นหาการ์ดขาว · แบนเนอร์ตลาดสด · ร้านแนะนำแบบวงกลมโลโก้ · ชิปหมวดหมู่ · แถบเรียงลำดับ
 *         · ตารางการ์ดสินค้า 2 คอลัมน์ (รูปมุมมน + ปุ่ม "+" น้ำเงิน)
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
  View,
  useWindowDimensions,
} from 'react-native';
import { Text } from '@/components/ui/Text';
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
import { BannerCard, Chip, EmptyState, Icon, Screen, SectionHeader, tapHaptic, usePressGuard } from '@/components/ui';
import { CartButton, ProductCard, SearchField, StoreLogo } from '@/components/shop';
import { useTheme, shadowStyle, spacing, typography } from '@/theme';

const PER_PAGE = 20;
const BANNER_MARKET = require('@/assets/images/taladsod/banner-market.webp');

type SortKey = 'newest' | 'popular' | 'price_asc' | 'price_desc';

const SORTS: Array<{ key: SortKey; label: string; params: Pick<ProductListParams, 'sort' | 'order'> }> = [
  { key: 'newest', label: 'มาใหม่', params: { sort: 'newest', order: 'desc' } },
  { key: 'popular', label: 'ขายดี', params: { sort: 'popular', order: 'desc' } },
  { key: 'price_asc', label: 'ราคาต่ำไปสูง', params: { sort: 'price', order: 'asc' } },
  { key: 'price_desc', label: 'ราคาสูงไปต่ำ', params: { sort: 'price', order: 'desc' } },
];

/** ร้านในแถวแนะนำ — วงกลมโลโก้ (ขอบทอง = ร้านยืนยันแล้ว) + ชื่อ + สถานะ */
const StoreBubble: React.FC<{ store: StoreListItem }> = ({ store }) => {
  const { colors } = useTheme();
  // กันแตะรัวจนเปิดหน้าร้านซ้อน (แบบเดียวกับการ์ดกดได้ทั้งแอป)
  const { run: openStore } = usePressGuard(() => router.push(`/store/${store.id}` as never));
  return (
    <Pressable
      onPress={openStore}
      onPressIn={() => tapHaptic()}
      accessibilityRole="button"
      accessibilityLabel={`ร้าน ${store.name}`}
      style={({ pressed }) => [styles.storeBubble, { opacity: pressed ? 0.8 : 1, transform: [{ scale: pressed ? 0.97 : 1 }] }]}
    >
      <View
        style={[
          styles.storeRing,
          { borderColor: store.isOfficial ? colors.gold : colors.border, backgroundColor: colors.card },
          shadowStyle('sm', colors.shadowDark),
        ]}
      >
        <StoreLogo uri={store.logo} size={60} radius={30} />
        {store.isOfficial && (
          <View style={[styles.verified, { backgroundColor: colors.card }]}>
            <Icon name="seal-check" size={18} color={colors.goldDeep} weight="fill" />
          </View>
        )}
      </View>
      <Text numberOfLines={1} style={[typography.caption, styles.storeName, { color: colors.textStrong }]}>
        {store.name}
      </Text>
      <Text numberOfLines={1} style={[typography.micro, styles.storeSub, { color: colors.textMuted }]}>
        {store.isOfficial ? 'ร้านยืนยันแล้ว' : `${Number(store.productCount) || 0} สินค้า`}
      </Text>
      {store.rider_delivery && (
        <View style={styles.storeRider}>
          <Icon name="moped" size={12} color={colors.success} weight="fill" />
          <Text style={[typography.micro, { color: colors.success }]}>ไรเดอร์ส่ง</Text>
        </View>
      )}
    </Pressable>
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
      <SearchField
        value={searchInput}
        onChangeText={setSearchInput}
        placeholder="ค้นหาสินค้า แบรนด์"
        returnKeyType="search"
        onSubmitEditing={() => setSearch(searchInput.trim())}
        accessibilityLabel="ค้นหาสินค้า"
        maxLength={100}
        style={styles.search}
      />

      {!search && isFeatureEnabled('TALADSOD_ENABLED') && (
        <BannerCard
          image={BANNER_MARKET}
          title="ตลาดสดใกล้บ้าน"
          subtitle="ของสด อาหารร้อนๆ ไรเดอร์ส่งถึงหน้าบ้าน"
          ctaLabel="ไปตลาดสด"
          height={140}
          onPress={() => router.push('/taladsod' as never)}
          style={styles.banner}
        />
      )}

      {!search && stores.length > 0 && (
        <>
          <SectionHeader
            title="ร้านแนะนำ"
            subtitle="ร้านเด่นที่คัดมาให้"
            actionLabel="ดูทั้งหมด"
            onAction={() => router.push('/stores' as never)}
            style={styles.section}
          />
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.storeRow}
            style={styles.bleed}
          >
            {stores.slice(0, 12).map((s) => (
              <StoreBubble key={s.id} store={s} />
            ))}
          </ScrollView>
        </>
      )}

      {categories.length > 0 && (
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.chips}
          style={[styles.bleed, styles.categoryRow]}
        >
          <Chip label="ทั้งหมด" selected={categoryId === null} onPress={() => setCategoryId(null)} />
          {categories.map((c) => (
            <Chip
              key={c.id}
              label={c.name}
              selected={categoryId === c.id}
              onPress={() => setCategoryId(categoryId === c.id ? null : c.id)}
            />
          ))}
        </ScrollView>
      )}

      <SectionHeader
        title={search ? `ผลการค้นหา "${search}"` : selectedCategory ? selectedCategory.name : 'สินค้าทั้งหมด'}
        style={styles.section}
      />

      {/* เรียงลำดับ */}
      <View style={styles.sortRow}>
        <View style={[styles.sortIcon, { backgroundColor: colors.card, borderColor: colors.border }]}>
          <Icon name="sliders-horizontal" size={16} color={colors.goldDeep} weight="bold" />
        </View>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.sortChips}>
          {SORTS.map((s) => (
            <Chip key={s.key} label={s.label} size="sm" selected={sort === s.key} onPress={() => setSort(s.key)} />
          ))}
        </ScrollView>
      </View>
    </View>
  );

  const renderBody = () => {
    if (!isAuthenticated) {
      return (
        <EmptyState
          icon="lock-key"
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
              art="bag"
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
  list: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
  },
  listTab: {
    paddingBottom: spacing.xl,
  },
  column: {
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  search: {
    marginBottom: spacing.lg,
  },
  banner: {
    marginBottom: spacing.sm,
  },
  section: {
    marginTop: spacing.lg,
  },
  /** แถวเลื่อนแนวนอนชิดขอบจอ (ล้นระยะขอบของรายการ) */
  bleed: {
    marginHorizontal: -spacing.screen,
  },
  storeRow: {
    gap: spacing.lg,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.xs,
    paddingBottom: spacing.sm,
  },
  storeBubble: {
    width: 84,
    alignItems: 'center',
  },
  storeRing: {
    width: 70,
    height: 70,
    borderRadius: 35,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  verified: {
    position: 'absolute',
    right: -3,
    bottom: -3,
    width: 24,
    height: 24,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  storeName: {
    marginTop: spacing.sm,
    textAlign: 'center',
    fontWeight: '600',
  },
  storeSub: {
    textAlign: 'center',
  },
  storeRider: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 3,
    marginTop: 1,
  },
  categoryRow: {
    marginTop: spacing.lg,
  },
  chips: {
    gap: spacing.sm,
    paddingHorizontal: spacing.screen,
    paddingVertical: spacing.xxs,
  },
  sortRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.md,
    marginRight: -spacing.screen,
  },
  sortIcon: {
    width: 32,
    height: 32,
    borderRadius: 11,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sortChips: {
    gap: spacing.sm,
    paddingRight: spacing.screen,
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
