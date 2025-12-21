/**
 * Shopping Screen - Premium Version with Firefly Effects
 * ใช้ StyleSheet แทน NativeWind
 *
 * Features:
 * - ร้านพรีเมี่ยม (Official Shop) - ร้านเดียวของระบบ เชื่อมกับ admin/official-shop
 * - Firefly Glow Effects (หิ่งห้อยเรืองแสงฟุ้ง)
 * - Infinite Scroll โหลด 10 รายการต่อครั้ง
 * - หมวดหมู่สินค้า
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
  Animated,
  Easing,
} from 'react-native';
import { router, Stack } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import {
  getProductCategories,
  getFeaturedStores,
  getPremiumStore,
  getPremiumStoreProducts,
} from '@/services/api';
import * as Cache from '@/services/cache';
import * as Network from '@/services/network';
import { formatCurrency } from '@/constants';
import { BannerCarousel } from '@/components';
import type { Product, ProductCategory } from '@/types';

const { width, height: screenHeight } = Dimensions.get('window');
const ITEMS_PER_PAGE = 10;
const PRODUCT_CARD_WIDTH = (width - 48) / 2;

// =====================================================
// Firefly Glow Components (หิ่งห้อยเรืองแสงฟุ้ง)
// =====================================================

// จำนวนหิ่งห้อย
const NUM_FIREFLIES = 8;

// Firefly Component - หิ่งห้อยเรืองแสง
const Firefly = ({ delay, duration }: { delay: number; duration: number }) => {
  const opacity = useRef(new Animated.Value(0)).current;
  const translateX = useRef(new Animated.Value(Math.random() * width)).current;
  const translateY = useRef(new Animated.Value(Math.random() * 200)).current;
  const scale = useRef(new Animated.Value(0.8 + Math.random() * 0.4)).current;
  const glowOpacity = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    let isMounted = true;

    const animate = () => {
      if (!isMounted) return;

      const newX = Math.random() * width;
      const newY = Math.random() * 200;

      Animated.parallel([
        Animated.sequence([
          Animated.timing(opacity, {
            toValue: 0.7 + Math.random() * 0.3,
            duration: duration * 0.25,
            useNativeDriver: true,
          }),
          Animated.delay(duration * 0.2),
          Animated.timing(opacity, {
            toValue: 0,
            duration: duration * 0.55,
            useNativeDriver: true,
          }),
        ]),
        Animated.sequence([
          Animated.timing(glowOpacity, {
            toValue: 1,
            duration: duration * 0.25,
            useNativeDriver: true,
          }),
          Animated.delay(duration * 0.2),
          Animated.timing(glowOpacity, {
            toValue: 0,
            duration: duration * 0.55,
            useNativeDriver: true,
          }),
        ]),
        Animated.timing(translateX, {
          toValue: newX,
          duration: duration,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(translateY, {
          toValue: newY,
          duration: duration,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.sequence([
          Animated.timing(scale, {
            toValue: 1.2 + Math.random() * 0.5,
            duration: duration * 0.4,
            useNativeDriver: true,
          }),
          Animated.timing(scale, {
            toValue: 0.8 + Math.random() * 0.3,
            duration: duration * 0.6,
            useNativeDriver: true,
          }),
        ]),
      ]).start(() => {
        if (isMounted) animate();
      });
    };

    const timeout = setTimeout(animate, delay);

    return () => {
      isMounted = false;
      clearTimeout(timeout);
    };
  }, []);

  return (
    <Animated.View
      style={{
        position: 'absolute',
        transform: [{ translateX }, { translateY }, { scale }],
      }}
    >
      {/* Outer glow */}
      <Animated.View
        style={{
          position: 'absolute',
          width: 24,
          height: 24,
          borderRadius: 12,
          backgroundColor: '#FFD700',
          opacity: Animated.multiply(glowOpacity, 0.3),
          left: -9,
          top: -9,
        }}
      />
      {/* Middle glow */}
      <Animated.View
        style={{
          position: 'absolute',
          width: 14,
          height: 14,
          borderRadius: 7,
          backgroundColor: '#FFEC8B',
          opacity: Animated.multiply(glowOpacity, 0.5),
          left: -4,
          top: -4,
        }}
      />
      {/* Core */}
      <Animated.View
        style={{
          width: 8,
          height: 8,
          borderRadius: 4,
          backgroundColor: '#FFFACD',
          opacity,
          shadowColor: '#FFD700',
          shadowOffset: { width: 0, height: 0 },
          shadowOpacity: 1,
          shadowRadius: 12,
          elevation: 8,
        }}
      />
    </Animated.View>
  );
};

// Fireflies Container
const FirefliesContainer = () => {
  const fireflies = React.useMemo(() => {
    return Array.from({ length: NUM_FIREFLIES }, (_, i) => ({
      id: i,
      delay: Math.random() * 2000,
      duration: 3000 + Math.random() * 3000,
    }));
  }, []);

  return (
    <View style={styles.firefliesContainer} pointerEvents="none">
      {fireflies.map((fly) => (
        <Firefly key={fly.id} delay={fly.delay} duration={fly.duration} />
      ))}
    </View>
  );
};

// Premium Store Type
interface PremiumStoreData {
  id: string;
  sellerId: number;
  name: string;
  description: string;
  logo?: string;
  banner?: string;
  rating: number;
  ratingCount: number;
  isOfficial: boolean;
  isPremium: boolean;
  productCount: number;
  featuredCount: number;
  verified: boolean;
  features: string[];
}

// Store Type for Featured Stores
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

// Premium Store Card Component (ร้านพรีเมี่ยม - ร้านเดียวของระบบ) + Firefly Effects
const PremiumStoreCard = ({
  store,
  onPress,
}: {
  store: PremiumStoreData;
  onPress: () => void;
}) => (
  <Pressable onPress={onPress} style={styles.premiumCardContainer}>
    {/* Glow Effect */}
    <View style={styles.premiumGlow} />

    <LinearGradient
      colors={['#3B82F6', '#8B5CF6']}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={styles.premiumCard}
    >
      {/* Fireflies in card */}
      <FirefliesContainer />

      {/* Shine overlay */}
      <LinearGradient
        colors={['rgba(255,255,255,0.2)', 'rgba(255,255,255,0)']}
        start={{ x: 0, y: 0 }}
        end={{ x: 0.5, y: 1 }}
        style={styles.premiumShine}
      />

      <View style={styles.premiumContent}>
        {/* Store Logo with Glow */}
        <View style={styles.premiumLogoGlow}>
          <View style={styles.premiumLogoContainer}>
            {store.logo ? (
              <Image
                source={{ uri: store.logo }}
                style={styles.premiumLogo}
                resizeMode="cover"
              />
            ) : (
              <Text style={{ fontSize: 36 }}>👑</Text>
            )}
          </View>
        </View>

        {/* Store Info */}
        <View style={styles.premiumInfo}>
          <View style={styles.premiumNameRow}>
            <Text style={styles.premiumName}>{store.name}</Text>
            <View style={styles.premiumBadge}>
              <Text style={{ fontSize: 10 }}>🛡️</Text>
              <Text style={styles.premiumBadgeText}>PREMIUM</Text>
            </View>
          </View>

          <Text style={styles.premiumDescription} numberOfLines={1}>
            {store.description}
          </Text>

          <View style={styles.premiumStats}>
            <View style={styles.premiumStat}>
              <Text style={{ fontSize: 12 }}>⭐</Text>
              <Text style={styles.premiumStatText}>{store.rating.toFixed(1)}</Text>
            </View>
            <View style={styles.premiumStat}>
              <Text style={{ fontSize: 12 }}>📦</Text>
              <Text style={styles.premiumStatText}>{store.productCount} สินค้า</Text>
            </View>
            <View style={styles.premiumStat}>
              <Text style={{ fontSize: 12 }}>🔥</Text>
              <Text style={styles.premiumStatText}>{store.featuredCount} แนะนำ</Text>
            </View>
          </View>
        </View>

        {/* Arrow */}
        <View style={styles.premiumArrow}>
          <Text style={{ fontSize: 20, color: 'white' }}>→</Text>
        </View>
      </View>

      {/* Features */}
      <View style={styles.premiumFeatures}>
        {store.features.slice(0, 3).map((feature, idx) => (
          <View key={idx} style={styles.premiumFeatureTag}>
            <Text style={styles.premiumFeatureText}>{feature}</Text>
          </View>
        ))}
      </View>
    </LinearGradient>
  </Pressable>
);

// Store Card Component (ร้านแนะนำติดดาว)
const StoreCard = ({
  store,
  onPress,
}: {
  store: Store;
  onPress: () => void;
}) => (
  <Pressable onPress={onPress} style={styles.storeCard}>
    <View style={styles.storeImageContainer}>
      {store.logo ? (
        <Image source={{ uri: store.logo }} style={styles.storeImage} resizeMode="cover" />
      ) : (
        <Text style={{ fontSize: 36, color: '#9CA3AF' }}>🏪</Text>
      )}

      <View style={styles.storeBadgeFeatured}>
        <Text style={{ fontSize: 10, color: 'white' }}>⭐</Text>
        <Text style={styles.storeBadgeText}>แนะนำ</Text>
      </View>
    </View>

    <View style={styles.storeInfo}>
      <Text style={styles.storeName} numberOfLines={1}>{store.name}</Text>
      <View style={styles.storeRating}>
        <Text style={{ fontSize: 12 }}>⭐</Text>
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

        {product.commission_rate && product.commission_rate > 0 && (
          <View style={styles.commissionBadge}>
            <Text style={styles.commissionBadgeText}>+{product.commission_rate}%</Text>
          </View>
        )}
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

        <View style={styles.ratingPvRow}>
          {product.rating && (
            <View style={styles.ratingContainer}>
              <Text style={{ fontSize: 12 }}>⭐</Text>
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
        <Text style={{ fontSize: 16, color: '#3B82F6' }}>→</Text>
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
  const [premiumStore, setPremiumStore] = useState<PremiumStoreData | null>(null);
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
      // ดึงร้านพรีเมี่ยม (Official Shop)
      const premiumResponse = await getPremiumStore();
      if (premiumResponse) {
        setPremiumStore(premiumResponse);
      } else {
        // Mock data ถ้า API ยังไม่พร้อม
        setPremiumStore({
          id: 'premium-1',
          sellerId: 1,
          name: 'Official Shop',
          description: 'ร้านค้าทางการของระบบ สินค้าคุณภาพสูง รับประกันแท้ 100%',
          rating: 4.9,
          ratingCount: 1250,
          isOfficial: true,
          isPremium: true,
          productCount: 150,
          featuredCount: 25,
          verified: true,
          features: ['สินค้าแท้ 100%', 'รับประกันคุณภาพ', 'คอมมิชชั่นสูง'],
        });
      }

      // ดึงร้านแนะนำติดดาว
      const featuredResponse = await getFeaturedStores();
      if (featuredResponse) {
        setFeaturedStores(featuredResponse);
      }
    } catch (error) {
      console.error('Load stores error:', error);
    }
  }, []);

  // โหลดสินค้าจากร้านพรีเมี่ยม (Infinite Scroll)
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

      // ดึง products จากร้านพรีเมี่ยม
      if (online) {
        const result = await getPremiumStoreProducts({
          category: selectedCategory,
          page: pageNum,
          limit: ITEMS_PER_PAGE,
          search: searchQuery || undefined,
        });

        if (result) {
          if (append) {
            setProducts(prev => [...prev, ...result.products]);
          } else {
            setProducts(result.products);
          }
          setHasMore(result.pagination.hasMore);

          if (pageNum === 1) {
            await Cache.setCache(
              Cache.CACHE_KEYS.PRODUCTS,
              result.products,
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
  }, [selectedCategory, searchQuery]);

  // Initial load
  useEffect(() => {
    loadStores();
    loadProducts(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Reload when category or search changes
  useEffect(() => {
    setPage(1);
    setProducts([]);
    setHasMore(true);
    loadProducts(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedCategory, searchQuery]);

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

  // Filter products - ⭐ เพิ่ม null check เพื่อป้องกัน crash
  const filteredProducts = products.filter((product) => {
    const productName = product?.name || '';
    const matchesSearch = productName.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesSearch;
  });

  // Header Component
  const ListHeaderComponent = () => (
    <>
      {/* Banner โฆษณา */}
      <View style={styles.bannerContainer}>
        <BannerCarousel
          position="shop"
          height={160}
          autoPlay={true}
          autoPlayInterval={5000}
          showIndicators={true}
          isDark={isDark}
        />
      </View>

      {/* ร้านพรีเมี่ยม (Official Shop) */}
      {premiumStore && (
        <View style={styles.section}>
          <SectionHeader
            title="👑 ร้านพรีเมี่ยม"
            subtitle="สินค้าคุณภาพจาก Thaiprompt"
          />
          <PremiumStoreCard
            store={premiumStore}
            onPress={() => {
              // Scroll ไปดูสินค้าด้านล่าง
              flatListRef.current?.scrollToOffset({ offset: 400, animated: true });
            }}
          />
        </View>
      )}

      {/* ร้านแนะนำติดดาว */}
      {featuredStores.length > 0 && (
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
                onPress={() => router.push(`/store/${store.id}`)}
              />
            ))}
          </ScrollView>
        </View>
      )}

      {/* สินค้าจากร้านพรีเมี่ยม */}
      <View style={styles.section}>
        <SectionHeader
          title="🛍️ สินค้าจากร้านพรีเมี่ยม"
          subtitle={premiumStore?.name || 'Official Shop'}
        />
      </View>

      {/* Categories */}
      <View style={styles.section}>
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
        <Text style={{ fontSize: 64, color: '#6B7280' }}>📦</Text>
        <Text style={styles.emptyText}>
          {searchQuery ? `ไม่พบสินค้าที่ค้นหา "${searchQuery}"` : 'ยังไม่มีสินค้าในหมวดหมู่นี้'}
        </Text>
        {!premiumStore && (
          <Text style={styles.emptySubtext}>
            ร้านพรีเมี่ยมยังไม่ได้ตั้งค่า กรุณาติดต่อผู้ดูแลระบบ
          </Text>
        )}
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
              <Text style={{ fontSize: 24, color: 'white' }}>💰</Text>
            </View>
            <View style={styles.commissionTextContainer}>
              <Text style={styles.commissionTitle}>รับคอมมิชชั่นทุกการขาย!</Text>
              <Text style={styles.commissionSubtitle}>
                แชร์ลิงก์สินค้าและรับค่าคอมมิชชั่น + PV
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
          <Text style={{ fontSize: 20 }}>⭐</Text>
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
              <Text style={{ fontSize: 24, color: '#FFFFFF' }}>←</Text>
            </Pressable>
          ),
          headerRight: () => (
            <Pressable onPress={() => router.push('/cart')} style={styles.headerButton}>
              <Text style={{ fontSize: 24, color: '#FFFFFF' }}>🛒</Text>
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
          <Text style={{ fontSize: 20, color: '#9CA3AF' }}>🔍</Text>
          <TextInput
            value={searchQuery}
            onChangeText={setSearchQuery}
            placeholder="ค้นหาสินค้าในร้านพรีเมี่ยม..."
            placeholderTextColor="#6B7280"
            style={styles.searchInput}
          />
          {searchQuery.length > 0 && (
            <Pressable onPress={() => setSearchQuery('')}>
              <Text style={{ fontSize: 20, color: '#6B7280' }}>✖️</Text>
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

  // Fireflies
  firefliesContainer: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    overflow: 'hidden',
    zIndex: 1,
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

  // Premium Store Card
  premiumCardContainer: {
    marginHorizontal: 16,
  },
  premiumGlow: {
    position: 'absolute',
    top: 8,
    left: 8,
    right: 8,
    bottom: -8,
    backgroundColor: '#3B82F6',
    borderRadius: 24,
    opacity: 0.3,
  },
  premiumCard: {
    borderRadius: 20,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.15)',
    position: 'relative',
  },
  premiumShine: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: '50%',
    zIndex: 0,
  },
  premiumContent: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    zIndex: 2,
  },
  premiumLogoGlow: {
    shadowColor: '#FBBF24',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.5,
    shadowRadius: 12,
    elevation: 8,
  },
  premiumLogoContainer: {
    width: 64,
    height: 64,
    borderRadius: 16,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 16,
    overflow: 'hidden',
  },
  premiumLogo: {
    width: '100%',
    height: '100%',
  },
  premiumInfo: {
    flex: 1,
  },
  premiumNameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 4,
  },
  premiumName: {
    color: '#FFFFFF',
    fontSize: 18,
    fontWeight: 'bold',
    marginRight: 8,
  },
  premiumBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FBBF24',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 12,
  },
  premiumBadgeText: {
    color: '#1F2937',
    fontSize: 10,
    fontWeight: 'bold',
    marginLeft: 2,
  },
  premiumDescription: {
    color: 'rgba(255,255,255,0.8)',
    fontSize: 13,
    marginBottom: 8,
  },
  premiumStats: {
    flexDirection: 'row',
    gap: 12,
  },
  premiumStat: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  premiumStatText: {
    color: '#FFFFFF',
    fontSize: 12,
  },
  premiumArrow: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  premiumFeatures: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    paddingHorizontal: 16,
    paddingBottom: 12,
    gap: 8,
    zIndex: 2,
  },
  premiumFeatureTag: {
    backgroundColor: 'rgba(255,255,255,0.15)',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
  },
  premiumFeatureText: {
    color: 'rgba(255,255,255,0.9)',
    fontSize: 11,
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

  // Store Card (Featured)
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
  storeBadgeFeatured: {
    position: 'absolute',
    top: 8,
    right: 8,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F59E0B',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 10,
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
  emptySubtext: {
    color: '#6B7280',
    fontSize: 12,
    marginTop: 8,
    textAlign: 'center',
    paddingHorizontal: 32,
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
