/**
 * Store Detail Screen - แสดงรายละเอียดร้านค้า
 * Dynamic route สำหรับดูสินค้าในร้านค้า
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  Pressable,
  RefreshControl,
  Image,
  ActivityIndicator,
  Dimensions,
  StyleSheet,
  StatusBar,
} from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { useAppStore } from '@/stores/appStore';
import { getStoreDetail, getStoreProducts } from '@/services/api';
import { formatCurrency } from '@/constants';
import type { Product } from '@/types';

const { width } = Dimensions.get('window');
const PRODUCT_CARD_WIDTH = (width - 48) / 2;

// Store type
interface StoreDetail {
  id: string;
  name: string;
  description?: string;
  logo?: string;
  banner?: string;
  rating: number;
  isOfficial: boolean;
  isFeatured: boolean;
  productCount: number;
  followerCount: number;
  responseRate?: number;
  joinedAt?: string;
}

// Product Card Component
const ProductCard = ({
  product,
  onPress,
}: {
  product: Product;
  onPress: () => void;
}) => {
  const hasDiscount = product.discount_price && product.discount_price < product.price;
  const pv = (product as any).pv || Math.round(product.price * 0.1);

  return (
    <Pressable onPress={onPress} style={styles.productCard}>
      <View style={styles.productImageContainer}>
        {product.image ? (
          <Image source={{ uri: product.image }} style={styles.productImage} resizeMode="cover" />
        ) : (
          <Text style={{ fontSize: 48, color: '#9CA3AF' }}>📦</Text>
        )}

        {hasDiscount && (
          <View style={styles.discountBadge}>
            <Text style={styles.discountBadgeText}>
              -{Math.round((1 - product.discount_price! / product.price) * 100)}%
            </Text>
          </View>
        )}

        <View style={styles.pvBadge}>
          <Text style={{ fontSize: 10, color: '#FFD700' }}>⭐</Text>
          <Text style={styles.pvBadgeText}>{pv} PV</Text>
        </View>
      </View>

      <View style={styles.productInfo}>
        <Text style={styles.productName} numberOfLines={2}>{product.name}</Text>

        <View style={styles.priceRow}>
          {hasDiscount ? (
            <>
              <Text style={styles.priceDiscount}>{formatCurrency(product.discount_price!)}</Text>
              <Text style={styles.priceOriginal}>{formatCurrency(product.price)}</Text>
            </>
          ) : (
            <Text style={styles.priceNormal}>{formatCurrency(product.price)}</Text>
          )}
        </View>
      </View>
    </Pressable>
  );
};

export default function StoreDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [store, setStore] = useState<StoreDetail | null>(null);
  const [products, setProducts] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);

  // โหลดข้อมูลร้านค้า
  const loadStoreData = useCallback(async () => {
    if (!id) return;

    try {
      setIsLoading(true);

      // โหลดข้อมูลร้านค้าและสินค้าพร้อมกัน
      const [storeRes, productsRes] = await Promise.all([
        getStoreDetail(id),
        getStoreProducts(id, 1),
      ]);

      if (storeRes?.success && storeRes.data) {
        setStore(storeRes.data);
      }

      if (productsRes?.success && productsRes.data) {
        setProducts(productsRes.data.items || []);
        setHasMore(productsRes.data.hasMore || false);
        setPage(1);
      }
    } catch (error) {
      console.error('Load store data error:', error);
    } finally {
      setIsLoading(false);
    }
  }, [id]);

  // โหลดสินค้าเพิ่มเติม
  const loadMore = useCallback(async () => {
    if (!hasMore || isLoadingMore || !id) return;

    try {
      setIsLoadingMore(true);
      const nextPage = page + 1;

      const response = await getStoreProducts(id, nextPage);

      if (response?.success && response.data) {
        setProducts(prev => [...prev, ...(response.data.items || [])]);
        setHasMore(response.data.hasMore || false);
        setPage(nextPage);
      }
    } catch (error) {
      console.error('Load more products error:', error);
    } finally {
      setIsLoadingMore(false);
    }
  }, [id, page, hasMore, isLoadingMore]);

  // Refresh
  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await loadStoreData();
    setRefreshing(false);
  }, [loadStoreData]);

  useEffect(() => {
    loadStoreData();
  }, [loadStoreData]);

  // Loading state
  if (isLoading) {
    return (
      <View style={[styles.container, isDark && styles.containerDark]}>
        <StatusBar barStyle="light-content" />
        <LinearGradient
          colors={['#0F0F23', '#1a1a2e', '#16213e']}
          style={StyleSheet.absoluteFill}
        />
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={styles.loadingText}>กำลังโหลด...</Text>
        </View>
      </View>
    );
  }

  // ถ้าไม่พบร้านค้า
  if (!store) {
    return (
      <View style={[styles.container, isDark && styles.containerDark]}>
        <StatusBar barStyle="light-content" />
        <LinearGradient
          colors={['#0F0F23', '#1a1a2e', '#16213e']}
          style={StyleSheet.absoluteFill}
        />
        <View style={styles.errorContainer}>
          <Text style={{ fontSize: 64 }}>🏪</Text>
          <Text style={styles.errorTitle}>ไม่พบร้านค้า</Text>
          <Text style={styles.errorSubtitle}>ร้านค้านี้อาจถูกลบหรือไม่มีอยู่</Text>
          <Pressable style={styles.backButton} onPress={() => router.back()}>
            <Text style={styles.backButtonText}>← กลับ</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  const renderHeader = () => (
    <View>
      {/* Header */}
      <View style={styles.header}>
        <Pressable style={styles.backIconButton} onPress={() => router.back()}>
          <Text style={{ fontSize: 20 }}>←</Text>
        </Pressable>
        <Text style={styles.headerTitle}>ร้านค้า</Text>
        <View style={{ width: 44 }} />
      </View>

      {/* Store Banner */}
      <View style={styles.bannerContainer}>
        {store.banner ? (
          <Image source={{ uri: store.banner }} style={styles.banner} resizeMode="cover" />
        ) : (
          <LinearGradient
            colors={['#3B82F6', '#8B5CF6']}
            style={styles.bannerPlaceholder}
          />
        )}

        {/* Store Logo */}
        <View style={styles.logoContainer}>
          {store.logo ? (
            <Image source={{ uri: store.logo }} style={styles.logo} resizeMode="cover" />
          ) : (
            <View style={styles.logoPlaceholder}>
              <Text style={{ fontSize: 40 }}>🏪</Text>
            </View>
          )}
        </View>
      </View>

      {/* Store Info - ⭐ เพิ่ม null checks เพื่อป้องกัน crash */}
      <View style={styles.storeInfoContainer}>
        <View style={styles.storeNameRow}>
          <Text style={styles.storeName}>{store?.name || 'ร้านค้า'}</Text>
          {store.isOfficial && (
            <View style={styles.officialBadge}>
              <Text style={{ fontSize: 12 }}>✅</Text>
              <Text style={styles.officialText}>ทางการ</Text>
            </View>
          )}
          {store.isFeatured && !store.isOfficial && (
            <View style={styles.featuredBadge}>
              <Text style={{ fontSize: 12 }}>⭐</Text>
              <Text style={styles.featuredText}>แนะนำ</Text>
            </View>
          )}
        </View>

        {store.description && (
          <Text style={styles.storeDescription}>{store.description}</Text>
        )}

        {/* Stats Row - ⭐ เพิ่ม null checks */}
        <View style={styles.statsRow}>
          <View style={styles.statItem}>
            <Text style={styles.statValue}>⭐ {(store?.rating ?? 0).toFixed(1)}</Text>
            <Text style={styles.statLabel}>คะแนน</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.statItem}>
            <Text style={styles.statValue}>{store?.productCount ?? 0}</Text>
            <Text style={styles.statLabel}>สินค้า</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.statItem}>
            <Text style={styles.statValue}>{store?.followerCount ?? 0}</Text>
            <Text style={styles.statLabel}>ผู้ติดตาม</Text>
          </View>
        </View>
      </View>

      {/* Products Section Title */}
      <View style={styles.productsSectionHeader}>
        <Text style={styles.productsSectionTitle}>สินค้าในร้าน</Text>
        <Text style={styles.productsCount}>{store?.productCount ?? 0} รายการ</Text>
      </View>
    </View>
  );

  const renderFooter = () => {
    if (!isLoadingMore) return null;
    return (
      <View style={styles.loadMoreContainer}>
        <ActivityIndicator size="small" color="#3B82F6" />
        <Text style={styles.loadMoreText}>กำลังโหลดเพิ่มเติม...</Text>
      </View>
    );
  };

  return (
    <View style={[styles.container, isDark && styles.containerDark]}>
      <StatusBar barStyle="light-content" />
      <LinearGradient
        colors={['#0F0F23', '#1a1a2e', '#16213e']}
        style={StyleSheet.absoluteFill}
      />

      <FlatList
        data={products}
        numColumns={2}
        keyExtractor={(item, index) => `product-${item.id || index}`}
        renderItem={({ item }) => (
          <ProductCard
            product={item}
            onPress={() => router.push(`/product/${item.id}`)}
          />
        )}
        ListHeaderComponent={renderHeader}
        ListFooterComponent={renderFooter}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={{ fontSize: 64 }}>📦</Text>
            <Text style={styles.emptyTitle}>ยังไม่มีสินค้า</Text>
            <Text style={styles.emptySubtitle}>ร้านค้านี้ยังไม่มีสินค้าในขณะนี้</Text>
          </View>
        }
        contentContainerStyle={styles.listContainer}
        columnWrapperStyle={styles.columnWrapper}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor="#3B82F6"
            colors={['#3B82F6']}
          />
        }
        onEndReached={loadMore}
        onEndReachedThreshold={0.5}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  containerDark: {
    backgroundColor: '#0F0F23',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    color: '#9CA3AF',
    marginTop: 12,
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  errorTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 16,
  },
  errorSubtitle: {
    fontSize: 14,
    color: '#9CA3AF',
    marginTop: 8,
  },
  backButton: {
    marginTop: 24,
    paddingHorizontal: 24,
    paddingVertical: 12,
    backgroundColor: '#3B82F6',
    borderRadius: 12,
  },
  backButtonText: {
    color: '#FFFFFF',
    fontWeight: '600',
  },

  // Header
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingTop: 56,
    paddingBottom: 16,
  },
  backIconButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.1)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },

  // Banner
  bannerContainer: {
    height: 160,
    position: 'relative',
  },
  banner: {
    width: '100%',
    height: '100%',
  },
  bannerPlaceholder: {
    width: '100%',
    height: '100%',
  },
  logoContainer: {
    position: 'absolute',
    bottom: -40,
    left: 20,
    width: 80,
    height: 80,
    borderRadius: 16,
    borderWidth: 4,
    borderColor: '#0F0F23',
    overflow: 'hidden',
    backgroundColor: '#1F2937',
  },
  logo: {
    width: '100%',
    height: '100%',
  },
  logoPlaceholder: {
    width: '100%',
    height: '100%',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#374151',
  },

  // Store Info
  storeInfoContainer: {
    paddingHorizontal: 20,
    paddingTop: 50,
    paddingBottom: 20,
  },
  storeNameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 8,
  },
  storeName: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  officialBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#3B82F6',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
    gap: 4,
  },
  officialText: {
    fontSize: 12,
    color: '#FFFFFF',
    fontWeight: '600',
  },
  featuredBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F59E0B',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
    gap: 4,
  },
  featuredText: {
    fontSize: 12,
    color: '#FFFFFF',
    fontWeight: '600',
  },
  storeDescription: {
    fontSize: 14,
    color: '#9CA3AF',
    marginTop: 8,
    lineHeight: 20,
  },

  // Stats
  statsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 12,
    padding: 16,
    marginTop: 16,
  },
  statItem: {
    flex: 1,
    alignItems: 'center',
  },
  statValue: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  statLabel: {
    fontSize: 12,
    color: '#9CA3AF',
    marginTop: 4,
  },
  statDivider: {
    width: 1,
    height: 30,
    backgroundColor: 'rgba(255,255,255,0.1)',
  },

  // Products Section
  productsSectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255,255,255,0.1)',
  },
  productsSectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  productsCount: {
    fontSize: 14,
    color: '#9CA3AF',
  },

  // Product Card
  listContainer: {
    paddingBottom: 100,
  },
  columnWrapper: {
    paddingHorizontal: 16,
    gap: 12,
  },
  productCard: {
    width: PRODUCT_CARD_WIDTH,
    backgroundColor: '#1F2937',
    borderRadius: 16,
    overflow: 'hidden',
    marginVertical: 6,
  },
  productImageContainer: {
    width: '100%',
    height: 140,
    backgroundColor: '#374151',
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
  },
  productImage: {
    width: '100%',
    height: '100%',
  },
  discountBadge: {
    position: 'absolute',
    top: 8,
    left: 8,
    backgroundColor: '#EF4444',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
  },
  discountBadgeText: {
    color: '#FFFFFF',
    fontSize: 10,
    fontWeight: 'bold',
  },
  pvBadge: {
    position: 'absolute',
    top: 8,
    right: 8,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.6)',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
    gap: 2,
  },
  pvBadgeText: {
    color: '#FFFFFF',
    fontSize: 10,
    fontWeight: '600',
  },
  productInfo: {
    padding: 12,
  },
  productName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#FFFFFF',
    marginBottom: 8,
    minHeight: 36,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  priceDiscount: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#EF4444',
  },
  priceOriginal: {
    fontSize: 12,
    color: '#9CA3AF',
    textDecorationLine: 'line-through',
  },
  priceNormal: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#10B981',
  },

  // Empty State
  emptyContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 60,
  },
  emptyTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 16,
  },
  emptySubtitle: {
    fontSize: 14,
    color: '#9CA3AF',
    marginTop: 8,
  },

  // Load More
  loadMoreContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 20,
    gap: 8,
  },
  loadMoreText: {
    color: '#9CA3AF',
    fontSize: 14,
  },
});
