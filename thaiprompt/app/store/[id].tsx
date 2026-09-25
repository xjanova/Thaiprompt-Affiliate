/**
 * หน้าร้าน — GET /mobile/stores/{id} + /mobile/stores/{id}/products (SHOP-18)
 *
 * หน้าตา (ธีมรอยัล): การ์ดหน้าร้านขอบทอง — ปก (รูปร้าน หรือพื้นน้ำเงินลายกนก) · โลโก้ลอยทับปก
 *   · ชื่อร้านฟอนต์มีเชิง · ป้ายสถานะ · แถวสถิติ 3 ช่องคั่นเส้น · ช่องค้นหาในร้าน · ตารางสินค้า 2 คอลัมน์
 * - อ่าน data[] + pagination.has_more ตาม contract ใหม่ (เดิมอ่าน .items จึงว่างเสมอ)
 * - ค้นหาในร้าน (หน่วง 400ms) · เรียงลำดับ · เลื่อนโหลดเพิ่ม · ดึงลงเพื่อรีเฟรช
 * - ตัวเลขร้านเป็นค่าจริงทั้งหมด (ไม่มีอัตราตอบกลับสมมติ)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  RefreshControl,
  ScrollView,
  StyleSheet,
  View,
  useWindowDimensions,
} from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useLocalSearchParams } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { getStore, getStoreProducts, type ShopProduct, type StoreDetail } from '@/services/api/shopApi';
import { Card3D, Chip, EmptyState, Icon, Pill, RoyalHeader, Screen, SectionHeader } from '@/components/ui';
import { CartButton, MetaItem, ProductCard, SearchField, StoreLogo, formatThaiDateTime } from '@/components/shop';
import { useTheme, shadowStyle, spacing, typography } from '@/theme';

const PER_PAGE = 20;

type SortKey = 'newest' | 'popular' | 'price_asc' | 'price_desc';
const SORTS: Array<{ key: SortKey; label: string; sort: string; order: 'asc' | 'desc' }> = [
  { key: 'newest', label: 'มาใหม่', sort: 'newest', order: 'desc' },
  { key: 'popular', label: 'ขายดี', sort: 'popular', order: 'desc' },
  { key: 'price_asc', label: 'ราคาต่ำไปสูง', sort: 'price', order: 'asc' },
  { key: 'price_desc', label: 'ราคาสูงไปต่ำ', sort: 'price', order: 'desc' },
];

/** มุมการ์ดหน้าร้าน */
const HERO_RADIUS = 22;

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
          icon="lock-key"
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
          art={storeError.notFound ? 'store' : undefined}
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
      <Card3D padding={0} radius={HERO_RADIUS} gradientBorder shadow="lg" style={styles.hero}>
        <View style={styles.bannerBox}>
          {store?.banner ? (
            <>
              <Image source={{ uri: store.banner }} style={StyleSheet.absoluteFill} contentFit="cover" transition={150} />
              <LinearGradient colors={gradients.bannerScrim} style={StyleSheet.absoluteFill} />
            </>
          ) : (
            // ไม่มีรูปปก → พื้นน้ำเงินกรมท่าลายกนกทอง (ตัวตนแบรนด์)
            <RoyalHeader ornamentWidth={190} ornamentTop={-26} style={StyleSheet.absoluteFill} />
          )}
        </View>
        <View style={styles.heroBody}>
          <View style={styles.heroRow}>
            <View style={[styles.logoFrame, { backgroundColor: colors.card }, shadowStyle('md', colors.shadowDark)]}>
              <StoreLogo uri={store?.logo} size={72} radius={20} />
            </View>
            {!!store?.joinedAt && (
              <MetaItem
                icon="calendar-blank"
                text={`เปิดร้านเมื่อ ${formatThaiDateTime(store.joinedAt, false)}`}
                color={colors.textFaint}
                style={styles.joined}
              />
            )}
          </View>

          <View style={styles.nameRow}>
            <Text numberOfLines={2} style={[typography.serif, styles.name, { color: colors.textStrong }]}>
              {store?.name || 'ร้านค้า'}
            </Text>
            {store?.is_verified && <Icon name="seal-check" size={20} color={colors.goldDeep} weight="fill" />}
          </View>

          {(store?.is_verified || store?.rider_delivery || store?.cod_available) && (
            <View style={styles.pills}>
              {store?.is_verified && <Pill label="ร้านยืนยันแล้ว" tone="success" icon="seal-check" />}
              {store?.rider_delivery && <Pill label="ไรเดอร์ส่ง" tone="gold" icon="moped" />}
              {store?.cod_available && <Pill label="เก็บเงินปลายทาง" tone="info" icon="money" />}
            </View>
          )}

          {!!store?.description && (
            <Text numberOfLines={4} style={[typography.bodySm, styles.description, { color: colors.textMuted }]}>
              {store.description}
            </Text>
          )}

          <View style={[styles.stats, { borderTopColor: colors.divider }]}>
            <View style={styles.stat}>
              <Text style={[typography.h3, styles.statValue, { color: colors.textStrong }]}>{Number(store?.product_count) || 0}</Text>
              <Text style={[typography.micro, { color: colors.textMuted }]}>สินค้า</Text>
            </View>
            <View style={[styles.statDivider, { backgroundColor: colors.divider }]} />
            <View style={styles.stat}>
              <View style={styles.statValueRow}>
                {ratingCount > 0 && <Icon name="star" size={16} color={colors.gold} weight="fill" />}
                <Text style={[typography.h3, styles.statValue, { color: colors.textStrong }]}>
                  {ratingCount > 0 ? rating.toFixed(1) : '—'}
                </Text>
              </View>
              <Text style={[typography.micro, { color: colors.textMuted }]}>{ratingCount > 0 ? `${ratingCount} รีวิว` : 'ยังไม่มีรีวิว'}</Text>
            </View>
            <View style={[styles.statDivider, { backgroundColor: colors.divider }]} />
            <View style={styles.stat}>
              <Text style={[typography.h3, styles.statValue, { color: colors.textStrong }]}>{Number(store?.follower_count) || 0}</Text>
              <Text style={[typography.micro, { color: colors.textMuted }]}>ผู้ติดตาม</Text>
            </View>
          </View>
        </View>
      </Card3D>

      {/* ค้นหาในร้าน */}
      <SearchField
        value={searchInput}
        onChangeText={setSearchInput}
        placeholder="ค้นหาสินค้าในร้านนี้"
        returnKeyType="search"
        onSubmitEditing={() => setSearch(searchInput.trim())}
        accessibilityLabel="ค้นหาสินค้าในร้าน"
        maxLength={100}
      />

      <SectionHeader title={search ? `ผลการค้นหา "${search}"` : 'สินค้าในร้าน'} style={styles.section} />

      {/* เรียงลำดับ */}
      <View style={styles.sortRow}>
        <View style={[styles.sortIcon, { backgroundColor: colors.card, borderColor: colors.border }]}>
          <Icon name="sliders-horizontal" size={16} color={colors.goldDeep} weight="bold" />
        </View>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
          {SORTS.map((s) => (
            <Chip key={s.key} label={s.label} size="sm" selected={sort === s.key} onPress={() => setSort(s.key)} />
          ))}
        </ScrollView>
      </View>
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
              art="bag"
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
  list: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
  },
  column: {
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  hero: {
    marginBottom: spacing.lg,
  },
  bannerBox: {
    height: 128,
    // ขอบทองของการ์ดหนา 1.5 → มุมปกเล็กกว่ามุมการ์ดเท่านั้น ให้ชิดขอบพอดี
    borderTopLeftRadius: HERO_RADIUS - 1.5,
    borderTopRightRadius: HERO_RADIUS - 1.5,
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
    marginTop: -38,
  },
  logoFrame: {
    padding: 3,
    borderRadius: 23,
  },
  joined: {
    flex: 1,
    marginBottom: spacing.xs,
  },
  nameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: spacing.md,
  },
  name: {
    flexShrink: 1,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  description: {
    marginTop: spacing.sm,
  },
  stats: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: spacing.lg,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  stat: {
    flex: 1,
    alignItems: 'center',
  },
  statValueRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  statValue: {
    fontWeight: '700',
    fontVariant: ['tabular-nums'],
  },
  statDivider: {
    width: 1,
    height: 32,
  },
  section: {
    marginTop: spacing.xl,
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
  chips: {
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
