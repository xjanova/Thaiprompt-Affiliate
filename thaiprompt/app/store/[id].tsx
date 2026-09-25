/**
 * หน้าร้าน — GET /mobile/stores/{id} + /mobile/stores/{id}/products (SHOP-18)
 *
 * - อ่าน data[] + pagination.has_more ตาม contract ใหม่ (เดิมอ่าน .items จึงว่างเสมอ)
 * - ค้นหาในร้าน (หน่วง 400ms) · เรียงลำดับ · เลื่อนโหลดเพิ่ม · ดึงลงเพื่อรีเฟรช
 * - ตัวเลขร้านเป็นค่าจริงทั้งหมด (ไม่มีอัตราตอบกลับสมมติ)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
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
import { LinearGradient } from 'expo-linear-gradient';
import { router, useLocalSearchParams } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { getStore, getStoreProducts, type ShopProduct, type StoreDetail } from '@/services/api/shopApi';
import { Card3D, Chip, EmptyState, Pill, Screen, SectionHeader } from '@/components/ui';
import { CartButton, ProductCard, formatThaiDateTime } from '@/components/shop';
import { useTheme, clayShadowStyle, radii, spacing, typography } from '@/theme';

const PER_PAGE = 20;

type SortKey = 'newest' | 'popular' | 'price_asc' | 'price_desc';
const SORTS: Array<{ key: SortKey; label: string; sort: string; order: 'asc' | 'desc' }> = [
  { key: 'newest', label: 'มาใหม่', sort: 'newest', order: 'desc' },
  { key: 'popular', label: 'ขายดี', sort: 'popular', order: 'desc' },
  { key: 'price_asc', label: 'ราคาต่ำ → สูง', sort: 'price', order: 'asc' },
  { key: 'price_desc', label: 'ราคาสูง → ต่ำ', sort: 'price', order: 'desc' },
];

export default function StoreDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const storeId = String(id || '');
  const { colors, gradients } = useTheme();
  const { width } = useWindowDimensions();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const cardWidth = Math.floor((width - spacing.screen * 2 - spacing.md) / 2);

  const [store, setStore] = useState<StoreDetail | null>(null);
  const [storeError, setStoreError] = useState<{ message: string; notFound: boolean } | null>(null);
  const [products, setProducts] = useState<ShopProduct[]>([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [productsError, setProductsError] = useState<string | null>(null);
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [sort, setSort] = useState<SortKey>('newest');

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => setSearch(searchInput.trim()), 400);
    return () => clearTimeout(timer);
  }, [searchInput]);

  const loadStore = useCallback(async () => {
    if (!isAuthenticated || !/^\d+$/.test(storeId)) {
      if (!/^\d+$/.test(storeId)) setStoreError({ message: 'ไม่พบร้านค้านี้', notFound: true });
      return;
    }
    const result = await getStore(storeId);
    if (!mountedRef.current) return;
    if (result.success && result.data) {
      setStore(result.data);
      setStoreError(null);
    } else if (!result.success) {
      setStoreError({ message: result.message, notFound: result.status === 404 });
    }
  }, [isAuthenticated, storeId]);

  const loadProducts = useCallback(
    async (mode: 'initial' | 'refresh' | 'more', targetPage: number) => {
      if (!isAuthenticated || !/^\d+$/.test(storeId)) {
        setLoading(false);
        return;
      }
      const requestId = ++requestIdRef.current;
      if (mode === 'initial') setLoading(true);
      if (mode === 'refresh') setRefreshing(true);
      if (mode === 'more') setLoadingMore(true);

      const sortSpec = SORTS.find((s) => s.key === sort) ?? SORTS[0];
      const result = await getStoreProducts(storeId, {
        page: targetPage,
        per_page: PER_PAGE,
        sort: sortSpec.sort,
        order: sortSpec.order,
        ...(search ? { search } : {}),
      });
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
        setProductsError(null);
      } else if (mode !== 'more') {
        setProductsError(result.message);
      }
      setLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    },
    [isAuthenticated, storeId, sort, search]
  );

  useEffect(() => {
    loadStore();
  }, [loadStore]);

  useEffect(() => {
    loadProducts('initial', 1);
  }, [loadProducts]);

  const onRefresh = () => {
    loadStore();
    loadProducts('refresh', 1);
  };

  if (!isAuthenticated) {
    return (
      <Screen title="ร้านค้า" scroll={false}>
        <EmptyState
          icon="🔐"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อดูสินค้าของร้าน"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </Screen>
    );
  }

  if (storeError && !store) {
    return (
      <Screen title="ร้านค้า" scroll={false}>
        <EmptyState
          variant={storeError.notFound ? 'empty' : 'error'}
          icon={storeError.notFound ? '🏚️' : undefined}
          title={storeError.notFound ? 'ไม่พบร้านค้านี้' : undefined}
          message={storeError.notFound ? 'ร้านอาจปิดให้บริการแล้ว ลองดูร้านอื่นนะ' : storeError.message}
          actionLabel={storeError.notFound ? 'ดูร้านอื่น' : 'ลองใหม่'}
          onAction={storeError.notFound ? () => router.replace('/stores' as never) : loadStore}
        />
      </Screen>
    );
  }

  const rating = Number(store?.rating) || 0;
  const ratingCount = Number(store?.rating_count) || 0;

  const header = (
    <View>
      {/* หัวร้าน */}
      <Card3D padding={0} radius={radii.xl} gradientBorder style={styles.hero}>
        <View style={styles.bannerBox}>
          {store?.banner ? (
            <Image source={{ uri: store.banner }} style={StyleSheet.absoluteFill} contentFit="cover" transition={150} />
          ) : (
            <LinearGradient colors={gradients.hero} style={StyleSheet.absoluteFill} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} />
          )}
          <LinearGradient colors={gradients.bannerScrim} style={StyleSheet.absoluteFill} />
        </View>
        <View style={styles.heroBody}>
          <View style={styles.heroRow}>
            {store?.logo ? (
              <Image source={{ uri: store.logo }} style={[styles.logo, { backgroundColor: colors.inset, borderColor: colors.card }]} contentFit="cover" />
            ) : (
              <View style={[styles.logo, styles.center, { backgroundColor: colors.goldSoft, borderColor: colors.card }]}>
                <Text style={styles.logoIcon}>🏪</Text>
              </View>
            )}
            <View style={styles.flex}>
              <Text numberOfLines={2} style={[typography.h2, { color: colors.textStrong }]}>
                {store?.name || 'ร้านค้า'}
              </Text>
              <View style={styles.pills}>
                {store?.is_verified && <Pill label="ร้านยืนยันแล้ว" tone="success" icon="✔" />}
                {store?.rider_delivery && <Pill label="ไรเดอร์ส่ง" tone="gold" icon="🛵" />}
                {store?.cod_available && <Pill label="เก็บเงินปลายทาง" tone="info" />}
              </View>
            </View>
          </View>
          <View style={[styles.stats, { borderTopColor: colors.divider }]}>
            <View style={styles.stat}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>{Number(store?.product_count) || 0}</Text>
              <Text style={[typography.micro, { color: colors.textMuted }]}>สินค้า</Text>
            </View>
            <View style={styles.stat}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>{ratingCount > 0 ? `⭐ ${rating.toFixed(1)}` : '—'}</Text>
              <Text style={[typography.micro, { color: colors.textMuted }]}>{ratingCount > 0 ? `${ratingCount} รีวิว` : 'ยังไม่มีรีวิว'}</Text>
            </View>
            <View style={styles.stat}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>{Number(store?.follower_count) || 0}</Text>
              <Text style={[typography.micro, { color: colors.textMuted }]}>ผู้ติดตาม</Text>
            </View>
          </View>
          {!!store?.description && (
            <Text numberOfLines={4} style={[typography.bodySm, styles.description, { color: colors.text }]}>
              {store.description}
            </Text>
          )}
          {!!store?.joinedAt && (
            <Text style={[typography.micro, { color: colors.textFaint }]}>เปิดร้านเมื่อ {formatThaiDateTime(store.joinedAt, false)}</Text>
          )}
        </View>
      </Card3D>

      {/* ค้นหาในร้าน */}
      <View style={[styles.searchBox, { backgroundColor: colors.card }, clayShadowStyle('sm', colors.shadowDark, colors.shadowLight)]}>
        <Text>🔍</Text>
        <TextInput
          value={searchInput}
          onChangeText={setSearchInput}
          placeholder="ค้นหาสินค้าในร้านนี้"
          placeholderTextColor={colors.textFaint}
          returnKeyType="search"
          onSubmitEditing={() => setSearch(searchInput.trim())}
          style={[typography.body, styles.searchInput, { color: colors.textStrong }]}
          accessibilityLabel="ค้นหาสินค้าในร้าน"
          maxLength={100}
        />
        {searchInput.length > 0 && (
          <Pressable onPress={() => setSearchInput('')} accessibilityRole="button" accessibilityLabel="ล้างคำค้นหา" hitSlop={10}>
            <Text style={[typography.h3, { color: colors.textFaint }]}>✕</Text>
          </Pressable>
        )}
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
        {SORTS.map((s) => (
          <Chip key={s.key} label={s.label} size="sm" tone="gold" selected={sort === s.key} onPress={() => setSort(s.key)} />
        ))}
      </ScrollView>

      <SectionHeader title={search ? `ผลการค้นหา "${search}"` : 'สินค้าในร้าน'} style={styles.section} />
    </View>
  );

  return (
    <Screen title={store?.name || 'ร้านค้า'} scroll={false} right={<CartButton />}>
      <FlatList
        data={products}
        keyExtractor={(item) => String(item.id)}
        numColumns={2}
        columnWrapperStyle={styles.column}
        renderItem={({ item }) => <ProductCard product={item} width={cardWidth} />}
        ListHeaderComponent={header}
        ListEmptyComponent={
          loading ? (
            <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
          ) : productsError ? (
            <EmptyState compact variant="error" message={productsError} onAction={() => loadProducts('initial', 1)} />
          ) : (
            <EmptyState
              compact
              icon="📦"
              title={search ? 'ไม่พบสินค้าที่ค้นหา' : 'ร้านนี้ยังไม่มีสินค้า'}
              message={search ? 'ลองใช้คำค้นอื่นนะ' : 'แวะมาดูใหม่เร็วๆ นี้นะ'}
            />
          )
        }
        ListFooterComponent={loadingMore ? <ActivityIndicator color={colors.gold} style={styles.footer} /> : <View style={styles.footerSpace} />}
        contentContainerStyle={styles.list}
        onEndReached={() => {
          if (hasMore && !loadingMore && !loading && !refreshing) loadProducts('more', page + 1);
        }}
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
  list: {
    paddingHorizontal: spacing.screen,
  },
  column: {
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  hero: {
    marginBottom: spacing.md,
  },
  bannerBox: {
    height: 110,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
    overflow: 'hidden',
  },
  heroBody: {
    padding: spacing.lg,
    paddingTop: 0,
  },
  heroRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: spacing.md,
    marginTop: -28,
  },
  logo: {
    width: 68,
    height: 68,
    borderRadius: 20,
    borderWidth: 3,
  },
  logoIcon: {
    fontSize: 30,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  stats: {
    flexDirection: 'row',
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  stat: {
    flex: 1,
    alignItems: 'center',
  },
  description: {
    marginTop: spacing.md,
    marginBottom: spacing.xs,
  },
  searchBox: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.lg,
    paddingHorizontal: spacing.md,
    minHeight: 48,
    gap: spacing.sm,
  },
  searchInput: {
    flex: 1,
    paddingVertical: spacing.sm,
  },
  chips: {
    gap: spacing.sm,
    paddingTop: spacing.md,
    paddingRight: spacing.sm,
  },
  section: {
    marginTop: spacing.md,
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
