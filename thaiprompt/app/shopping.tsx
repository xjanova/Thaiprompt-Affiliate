/**
 * Shopping Screen - Premium Stable Version
 * ใช้ StyleSheet แทน NativeWind
 *
 * Features:
 * - Infinite Scroll โหลด 10 รายการต่อครั้ง
 * - หมวดหมู่ร้านค้า: ร้านค้าทางการ, ร้านแนะนำติดดาว
 * - Banner โฆษณา (Admin Control)
 * - FlatList สำหรับ performance ที่ดี
 * - แสดง PV (Point Value) บนสินค้า
 */

import React, { useEffect, useState, useCallback, useRef } from 'react';
import {
  View,
  Text,
  FlatList,
  Pressable,
  RefreshControl,
  Image,
  TextInput,
  ActivityIndicator,
  ScrollView,
  Dimensions,
  StyleSheet,
  StatusBar,
} from 'react-native';
import { router, Stack } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import { getProducts, getProductCategories, getFeaturedStores, getOfficialStores } from '@/services/api';
import * as Cache from '@/services/cache';
import * as Network from '@/services/network';
import { formatCurrency } from '@/constants';
import { BannerCarousel } from '@/components';
import type { Product, ProductCategory } from '@/types';

const { width } = Dimensions.get('window');
const ITEMS_PER_PAGE = 10;
const PRODUCT_CARD_WIDTH = (width - 48) / 2;

// Store Type
interface Store {
  id: string;
  name: string;
  logo?: string;
  rating: number;
  isOfficial: boolean;
  isFeatured: boolean;
  productCount: number;
}

// Category Chip Component
const CategoryChip = ({
  category,
  isActive,
  onPress,
}: {
  category: ProductCategory;
  isActive: boolean;
  onPress: () => void;
}) => (
  <Pressable
    onPress={onPress}
    style={[
      styles.categoryChip,
      isActive ? styles.categoryChipActive : styles.categoryChipInactive,
    ]}
  >
    <Text style={[
      styles.categoryChipText,
      isActive ? styles.categoryChipTextActive : styles.categoryChipTextInactive,
    ]}>
      {category.name}
    </Text>
  </Pressable>
);

// Store Card Component
const StoreCard = ({
  store,
  isOfficial,
  onPress,
}: {
  store: Store;
  isOfficial: boolean;
  onPress: () => void;
}) => (
  <Pressable onPress={onPress} style={styles.storeCard}>
    {/* Store Image */}
    <View style={styles.storeImageContainer}>
      {store.logo ? (
        <Image source={{ uri: store.logo }} style={styles.storeImage} resizeMode="cover" />
      ) : (
        <Ionicons name="storefront-outline" size={36} color="#9CA3AF" />
      )}

      {/* Badge */}
      <View style={[styles.storeBadge, isOfficial ? styles.badgeBlue : styles.badgeOrange]}>
        <Ionicons name={isOfficial ? "checkmark-circle" : "star"} size={10} color="white" />
        <Text style={styles.storeBadgeText}>{isOfficial ? 'ทางการ' : 'แนะนำ'}</Text>
      </View>
    </View>

    {/* Store Info */}
    <View style={styles.storeInfo}>
      <Text style={styles.storeName} numberOfLines={1}>{store.name}</Text>
      <View style={styles.storeRating}>
        <Ionicons name="star" size={12} color="#FBBF24" />
        <Text style={styles.storeRatingText}>
          {store.rating.toFixed(1)} • {store.productCount} สินค้า
        </Text>
      </View>
    </View>
  </Pressable>
);

// Product Card Component พร้อม PV Badge
const ProductCard = ({
  product,
  onPress,
}: {
  product: Product;
  onPress: () => void;
}) => {
  const hasDiscount = product.discount_price && product.discount_price < product.price;
  // คำนวณ PV - ใช้ค่าจาก product หรือ 10% ของราคา
  const pv = (product as any).pv || Math.round(product.price * 0.1);

  return (
    <Pressable onPress={onPress} style={styles.productCard}>
      {/* Image */}
      <View style={styles.productImageContainer}>
        {product.image ? (
          <Image source={{ uri: product.image }} style={styles.productImage} resizeMode="cover" />
        ) : (
          <Ionicons name="cube-outline" size={48} color="#9CA3AF" />
        )}

        {/* Discount Badge */}
        {hasDiscount && (
          <View style={styles.discountBadge}>
            <Text style={styles.discountBadgeText}>
              -{Math.round((1 - product.discount_price! / product.price) * 100)}%
            </Text>
          </View>
        )}

        {/* PV Badge - แสดงมุมขวาบน */}
        <View style={styles.pvBadge}>
          <Ionicons name="star" size={10} color="#FFD700" />
          <Text style={styles.pvBadgeText}>{pv} PV</Text>
        </View>

        {/* Commission Badge */}
        {product.commission_rate && product.commission_rate > 0 && (
          <View style={styles.commissionBadge}>
            <Text style={styles.commissionBadgeText}>+{product.commission_rate}%</Text>
          </View>
        )}
      </View>

      {/* Info */}
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

        {/* Rating & PV Row */}
        <View style={styles.ratingPvRow}>
          {product.rating && (
            <View style={styles.ratingContainer}>
              <Ionicons name="star" size={12} color="#FBBF24" />
              <Text style={styles.ratingText}>
                {product.rating.toFixed(1)} ({product.review_count || 0})
              </Text>
            </View>
          )}
        </View>
      </View>
    </Pressable>
  );
};

// Section Header Component
const SectionHeader = ({
  title,
  subtitle,
  onSeeAll,
}: {
  title: string;
  subtitle?: string;
  onSeeAll?: () => void;
}) => (
  <View style={styles.sectionHeader}>
    <View>
      <Text style={styles.sectionTitle}>{title}</Text>
      {subtitle && <Text style={styles.sectionSubtitle}>{subtitle}</Text>}
    </View>
    {onSeeAll && (
      <Pressable onPress={onSeeAll} style={styles.seeAllButton}>
        <Text style={styles.seeAllText}>ดูทั้งหมด</Text>
        <Ionicons name="chevron-forward" size={16} color="#3B82F6" />
      </Pressable>
    )}
  </View>
);

// Loading Footer Component
const LoadingFooter = ({ isLoading }: { isLoading: boolean }) => {
  if (!isLoading) return null;
  return (
    <View style={styles.loadingFooter}>
      <ActivityIndicator size="small" color="#3B82F6" />
      <Text style={styles.loadingText}>กำลังโหลดเพิ่ม...</Text>
    </View>
  );
};

export default function ShoppingScreen() {
  const { resolvedTheme } = useAppStore();
  const { isAuthenticated } = useAuthStore();
  const isDark = resolvedTheme === 'dark';

  // Data states
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<ProductCategory[]>([]);
  const [officialStores, setOfficialStores] = useState<Store[]>([]);
  const [featuredStores, setFeaturedStores] = useState<Store[]>([]);

  // UI states
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [refreshing, setRefreshing] = useState(false);
  const [isOnline, setIsOnline] = useState(true);
  const [isLoading, setIsLoading] = useState(true);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [hasMore, setHasMore] = useState(true);

  // Pagination
  const [page, setPage] = useState(1);
  const flatListRef = useRef<FlatList>(null);

  // โหลดข้อมูลร้านค้า
  const loadStores = useCallback(async () => {
    try {
      const officialResponse = await getOfficialStores?.() || null;
      const featuredResponse = await getFeaturedStores?.() || null;

      // Mock data ถ้า API ไม่มี
      const mockOfficialStores: Store[] = [
        { id: '1', name: 'Thaiprompt Official', rating: 4.9, isOfficial: true, isFeatured: false, productCount: 150 },
        { id: '2', name: 'TP Electronics', rating: 4.8, isOfficial: true, isFeatured: false, productCount: 89 },
        { id: '3', name: 'TP Fashion', rating: 4.7, isOfficial: true, isFeatured: false, productCount: 234 },
      ];

      const mockFeaturedStores: Store[] = [
        { id: '4', name: 'TopSeller Shop', rating: 4.9, isOfficial: false, isFeatured: true, productCount: 67 },
        { id: '5', name: 'BestDeal Store', rating: 4.8, isOfficial: false, isFeatured: true, productCount: 123 },
        { id: '6', name: 'Premium Goods', rating: 4.7, isOfficial: false, isFeatured: true, productCount: 45 },
        { id: '7', name: 'Quality First', rating: 4.6, isOfficial: false, isFeatured: true, productCount: 78 },
      ];

      setOfficialStores(officialResponse || mockOfficialStores);
      setFeaturedStores(featuredResponse || mockFeaturedStores);
    } catch (error) {
      console.error('Load stores error:', error);
    }
  }, []);

  // โหลดสินค้า (Infinite Scroll)
  const loadProducts = useCallback(async (pageNum: number = 1, append: boolean = false) => {
    try {
      if (pageNum === 1) {
        setIsLoading(true);
      } else {
        setIsLoadingMore(true);
      }

      const online = await Network.checkNetworkStatus();
      setIsOnline(online);

      // ดึง categories ถ้าหน้าแรก
      if (pageNum === 1) {
        const cachedCategories = await Cache.getCache<ProductCategory[]>(
          Cache.CACHE_KEYS.PRODUCTS_CATEGORIES
        );
        if (cachedCategories) setCategories(cachedCategories);

        if (online) {
          const freshCategories = await getProductCategories();
          if (freshCategories) {
            setCategories(freshCategories);
            await Cache.setCache(
              Cache.CACHE_KEYS.PRODUCTS_CATEGORIES,
              freshCategories,
              Cache.LONG_CACHE_DURATION
            );
          }
        }
      }

      // ดึง products
      if (online) {
        const freshProducts = await getProducts({
          category: selectedCategory,
          page: pageNum,
          limit: ITEMS_PER_PAGE,
        });

        if (freshProducts) {
          if (append) {
            setProducts(prev => [...prev, ...freshProducts]);
          } else {
            setProducts(freshProducts);
          }
          setHasMore(freshProducts.length >= ITEMS_PER_PAGE);

          if (pageNum === 1) {
            await Cache.setCache(
              Cache.CACHE_KEYS.PRODUCTS,
              freshProducts,
              Cache.DEFAULT_CACHE_DURATION
            );
          }
        } else {
          setHasMore(false);
        }
      } else {
        const cachedProducts = await Cache.getCache<Product[]>(Cache.CACHE_KEYS.PRODUCTS);
        if (cachedProducts && pageNum === 1) {
          setProducts(cachedProducts);
        }
        setHasMore(false);
      }
    } catch (error) {
      console.error('Load products error:', error);
      setHasMore(false);
    } finally {
      setIsLoading(false);
      setIsLoadingMore(false);
    }
  }, [selectedCategory]);

  // Initial load
  useEffect(() => {
    loadStores();
    loadProducts(1);
  }, [loadStores, loadProducts]);

  // Reload when category changes
  useEffect(() => {
    setPage(1);
    setProducts([]);
    setHasMore(true);
    loadProducts(1);
  }, [selectedCategory]);

  // Load more handler
  const handleLoadMore = useCallback(() => {
    if (!isLoadingMore && hasMore && !isLoading) {
      const nextPage = page + 1;
      setPage(nextPage);
      loadProducts(nextPage, true);
    }
  }, [isLoadingMore, hasMore, isLoading, page, loadProducts]);

  // Refresh handler
  const onRefresh = async () => {
    setRefreshing(true);
    setPage(1);
    setHasMore(true);
    await Promise.all([loadStores(), loadProducts(1)]);
    setRefreshing(false);
  };

  // Filter products
  const filteredProducts = products.filter((product) => {
    const matchesSearch = product.name.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesSearch;
  });

  // Header Component
  const ListHeaderComponent = () => (
    <>
      {/* Banner โฆษณา */}
      <View style={styles.bannerContainer}>
        <BannerCarousel
          position="top"
          height={160}
          autoPlay={true}
          autoPlayInterval={5000}
          showIndicators={true}
          isDark={isDark}
        />
      </View>

      {/* ร้านค้าทางการ */}
      <View style={styles.section}>
        <SectionHeader
          title="🏪 ร้านค้าทางการ"
          subtitle="ร้านค้าที่ได้รับการรับรอง"
          onSeeAll={() => router.push('/stores?type=official')}
        />
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.storeScrollContent}
        >
          {officialStores.map((store) => (
            <StoreCard
              key={store.id}
              store={store}
              isOfficial={true}
              onPress={() => router.push(`/store/${store.id}`)}
            />
          ))}
        </ScrollView>
      </View>

      {/* ร้านแนะนำติดดาว */}
      <View style={styles.section}>
        <SectionHeader
          title="⭐ ร้านแนะนำติดดาว"
          subtitle="คัดสรรโดยทีมงาน"
          onSeeAll={() => router.push('/stores?type=featured')}
        />
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.storeScrollContent}
        >
          {featuredStores.map((store) => (
            <StoreCard
              key={store.id}
              store={store}
              isOfficial={false}
              onPress={() => router.push(`/store/${store.id}`)}
            />
          ))}
        </ScrollView>
      </View>

      {/* Categories */}
      <View style={styles.section}>
        <SectionHeader title="หมวดหมู่" />
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.categoryScrollContent}
        >
          <CategoryChip
            category={{ id: '', name: 'ทั้งหมด', slug: 'all' }}
            isActive={selectedCategory === null}
            onPress={() => setSelectedCategory(null)}
          />
          {categories.map((category) => (
            <CategoryChip
              key={category.id}
              category={category}
              isActive={selectedCategory === category.id}
              onPress={() => setSelectedCategory(category.id)}
            />
          ))}
        </ScrollView>
      </View>

      {/* Results Count */}
      <View style={styles.resultsCount}>
        <Text style={styles.resultsText}>
          พบ {filteredProducts.length} รายการ
          {hasMore && ' (โหลดเพิ่มเมื่อเลื่อนลง)'}
        </Text>
      </View>
    </>
  );

  // Empty Component
  const ListEmptyComponent = () => {
    if (isLoading) {
      return (
        <View style={styles.emptyContainer}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={styles.emptyText}>กำลังโหลดสินค้า...</Text>
        </View>
      );
    }

    return (
      <View style={styles.emptyContainer}>
        <Ionicons name="cube-outline" size={64} color="#6B7280" />
        <Text style={styles.emptyText}>
          {searchQuery ? `ไม่พบสินค้าที่ค้นหา "${searchQuery}"` : 'ไม่มีสินค้าในหมวดหมู่นี้'}
        </Text>
      </View>
    );
  };

  // Footer Component
  const ListFooterComponent = () => (
    <>
      <LoadingFooter isLoading={isLoadingMore} />

      {/* Commission Info Banner */}
      {isAuthenticated && !isLoadingMore && filteredProducts.length > 0 && (
        <View style={styles.commissionBanner}>
          <LinearGradient
            colors={['#10B981', '#059669']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.commissionGradient}
          >
            <View style={styles.commissionIcon}>
              <Ionicons name="cash" size={24} color="white" />
            </View>
            <View style={styles.commissionTextContainer}>
              <Text style={styles.commissionTitle}>รับคอมมิชชั่นทุกการขาย!</Text>
              <Text style={styles.commissionSubtitle}>
                แชร์ลิงก์สินค้าและรับค่าคอมมิชชั่นสูงสุด 30%
              </Text>
            </View>
          </LinearGradient>
        </View>
      )}

      {/* PV Info Banner */}
      <View style={styles.pvInfoBanner}>
        <LinearGradient
          colors={['rgba(255,215,0,0.15)', 'rgba(255,165,0,0.15)']}
          style={styles.pvInfoGradient}
        >
          <Ionicons name="star" size={20} color="#FFD700" />
          <View style={styles.pvInfoTextContainer}>
            <Text style={styles.pvInfoTitle}>สะสม PV ทุกการซื้อ!</Text>
            <Text style={styles.pvInfoSubtitle}>
              PV สามารถนำไปแลกรางวัลและสิทธิพิเศษต่างๆ
            </Text>
          </View>
        </LinearGradient>
      </View>
    </>
  );

  // Render Product Item
  const renderProduct = ({ item }: { item: Product }) => (
    <ProductCard
      product={item}
      onPress={() => router.push(`/product/${item.id}`)}
    />
  );

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />

      <LinearGradient
        colors={['#0F0F23', '#1A1A2E', '#16213E']}
        style={StyleSheet.absoluteFill}
      />

      <Stack.Screen
        options={{
          headerShown: true,
          title: 'ช้อปปิ้ง',
          headerStyle: { backgroundColor: '#0F0F23' },
          headerTintColor: '#FFFFFF',
          headerLeft: () => (
            <Pressable onPress={() => router.back()} style={styles.headerButton}>
              <Ionicons name="arrow-back" size={24} color="#FFFFFF" />
            </Pressable>
          ),
          headerRight: () => (
            <Pressable onPress={() => router.push('/cart')} style={styles.headerButton}>
              <Ionicons name="cart-outline" size={24} color="#FFFFFF" />
            </Pressable>
          ),
        }}
      />

      {/* Offline Banner */}
      {!isOnline && (
        <View style={styles.offlineBanner}>
          <Text style={styles.offlineText}>📡 ออฟไลน์ - แสดงสินค้าที่บันทึกไว้</Text>
        </View>
      )}

      {/* Search Bar */}
      <View style={styles.searchContainer}>
        <View style={styles.searchBar}>
          <Ionicons name="search" size={20} color="#9CA3AF" />
          <TextInput
            value={searchQuery}
            onChangeText={setSearchQuery}
            placeholder="ค้นหาสินค้า..."
            placeholderTextColor="#6B7280"
            style={styles.searchInput}
          />
          {searchQuery.length > 0 && (
            <Pressable onPress={() => setSearchQuery('')}>
              <Ionicons name="close-circle" size={20} color="#6B7280" />
            </Pressable>
          )}
        </View>
      </View>

      {/* Products Grid */}
      <FlatList
        ref={flatListRef}
        data={filteredProducts}
        renderItem={renderProduct}
        keyExtractor={(item) => item.id.toString()}
        numColumns={2}
        columnWrapperStyle={styles.productRow}
        contentContainerStyle={styles.productList}
        showsVerticalScrollIndicator={false}
        ListHeaderComponent={ListHeaderComponent}
        ListEmptyComponent={ListEmptyComponent}
        ListFooterComponent={ListFooterComponent}
        onEndReached={handleLoadMore}
        onEndReachedThreshold={0.5}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor="#3B82F6"
          />
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  headerButton: {
    padding: 8,
  },

  // Offline Banner
  offlineBanner: {
    backgroundColor: 'rgba(234,179,8,0.2)',
    paddingHorizontal: 16,
    paddingVertical: 8,
  },
  offlineText: {
    color: '#FBBF24',
    fontSize: 14,
    textAlign: 'center',
  },

  // Search
  searchContainer: {
    paddingHorizontal: 16,
    paddingTop: 16,
    paddingBottom: 8,
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderRadius: 14,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  searchInput: {
    flex: 1,
    marginLeft: 12,
    color: '#FFFFFF',
    fontSize: 16,
  },

  // Banner
  bannerContainer: {
    paddingTop: 8,
    paddingBottom: 16,
  },

  // Section
  section: {
    marginBottom: 16,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  sectionSubtitle: {
    fontSize: 14,
    color: '#9CA3AF',
    marginTop: 2,
  },
  seeAllButton: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  seeAllText: {
    color: '#3B82F6',
    fontSize: 14,
    fontWeight: '500',
  },

  // Category Chip
  categoryScrollContent: {
    paddingHorizontal: 16,
  },
  categoryChip: {
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 20,
    marginRight: 8,
  },
  categoryChipActive: {
    backgroundColor: '#3B82F6',
  },
  categoryChipInactive: {
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  categoryChipText: {
    fontSize: 14,
    fontWeight: '500',
  },
  categoryChipTextActive: {
    color: '#FFFFFF',
  },
  categoryChipTextInactive: {
    color: '#9CA3AF',
  },

  // Store Card
  storeScrollContent: {
    paddingHorizontal: 16,
  },
  storeCard: {
    width: 140,
    marginRight: 12,
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 16,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  storeImageContainer: {
    height: 96,
    backgroundColor: 'rgba(255,255,255,0.08)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  storeImage: {
    width: '100%',
    height: '100%',
  },
  storeBadge: {
    position: 'absolute',
    top: 8,
    right: 8,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 10,
  },
  badgeBlue: {
    backgroundColor: '#3B82F6',
  },
  badgeOrange: {
    backgroundColor: '#F59E0B',
  },
  storeBadgeText: {
    color: '#FFFFFF',
    fontSize: 8,
    fontWeight: 'bold',
    marginLeft: 2,
  },
  storeInfo: {
    padding: 10,
  },
  storeName: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '600',
  },
  storeRating: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 4,
  },
  storeRatingText: {
    color: '#9CA3AF',
    fontSize: 12,
    marginLeft: 4,
  },

  // Product Card
  productRow: {
    paddingHorizontal: 16,
    justifyContent: 'space-between',
  },
  productList: {
    paddingBottom: 20,
  },
  productCard: {
    width: PRODUCT_CARD_WIDTH,
    marginBottom: 16,
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 16,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  productImageContainer: {
    aspectRatio: 1,
    backgroundColor: 'rgba(255,255,255,0.08)',
    alignItems: 'center',
    justifyContent: 'center',
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
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
  },
  discountBadgeText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: 'bold',
  },
  // PV Badge - มุมขวาบนของรูปสินค้า
  pvBadge: {
    position: 'absolute',
    top: 8,
    right: 8,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.7)',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#FFD700',
  },
  pvBadgeText: {
    color: '#FFD700',
    fontSize: 11,
    fontWeight: 'bold',
    marginLeft: 3,
  },
  commissionBadge: {
    position: 'absolute',
    bottom: 8,
    right: 8,
    backgroundColor: '#10B981',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
  },
  commissionBadgeText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: 'bold',
  },
  productInfo: {
    padding: 12,
  },
  productName: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '500',
    marginBottom: 8,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  priceNormal: {
    color: '#3B82F6',
    fontSize: 16,
    fontWeight: 'bold',
  },
  priceDiscount: {
    color: '#EF4444',
    fontSize: 16,
    fontWeight: 'bold',
  },
  priceOriginal: {
    color: '#6B7280',
    fontSize: 12,
    textDecorationLine: 'line-through',
    marginLeft: 6,
  },
  ratingPvRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 6,
  },
  ratingContainer: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  ratingText: {
    color: '#9CA3AF',
    fontSize: 12,
    marginLeft: 4,
  },

  // Results
  resultsCount: {
    paddingHorizontal: 16,
    marginBottom: 12,
  },
  resultsText: {
    color: '#9CA3AF',
    fontSize: 14,
  },

  // Empty State
  emptyContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 64,
  },
  emptyText: {
    color: '#9CA3AF',
    marginTop: 16,
    textAlign: 'center',
  },

  // Loading Footer
  loadingFooter: {
    paddingVertical: 16,
    alignItems: 'center',
  },
  loadingText: {
    color: '#9CA3AF',
    fontSize: 14,
    marginTop: 8,
  },

  // Commission Banner
  commissionBanner: {
    marginHorizontal: 16,
    marginBottom: 16,
  },
  commissionGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 16,
    padding: 16,
  },
  commissionIcon: {
    width: 48,
    height: 48,
    borderRadius: 12,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  commissionTextContainer: {
    flex: 1,
  },
  commissionTitle: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  commissionSubtitle: {
    color: 'rgba(255,255,255,0.8)',
    fontSize: 14,
    marginTop: 2,
  },

  // PV Info Banner
  pvInfoBanner: {
    marginHorizontal: 16,
    marginBottom: 24,
  },
  pvInfoGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,215,0,0.3)',
  },
  pvInfoTextContainer: {
    flex: 1,
    marginLeft: 12,
  },
  pvInfoTitle: {
    color: '#FFD700',
    fontSize: 14,
    fontWeight: 'bold',
  },
  pvInfoSubtitle: {
    color: '#9CA3AF',
    fontSize: 12,
    marginTop: 2,
  },
});
