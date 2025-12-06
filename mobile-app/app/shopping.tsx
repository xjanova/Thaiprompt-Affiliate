/**
 * Shopping Screen - หน้าช้อปปิ้งสินค้า (Premium Version)
 *
 * Features:
 * - Infinite Scroll โหลด 10 รายการต่อครั้ง
 * - หมวดหมู่ร้านค้า: ร้านค้าทางการ, ร้านแนะนำติดดาว
 * - Banner โฆษณา (Admin Control)
 * - FlatList สำหรับ performance ที่ดี
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
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, Stack } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import Animated, { FadeInDown, FadeInRight, FadeIn } from 'react-native-reanimated';
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

// Category Chip
const CategoryChip = ({
  category,
  isActive,
  onPress,
  index,
}: {
  category: ProductCategory;
  isActive: boolean;
  onPress: () => void;
  index: number;
}) => (
  <Animated.View entering={FadeInRight.delay(index * 50).springify()}>
    <Pressable
      onPress={onPress}
      className={`px-4 py-2 rounded-full mr-2 ${
        isActive
          ? 'bg-primary-500'
          : 'bg-white dark:bg-dark-50 border border-gray-200 dark:border-gray-700'
      }`}
    >
      <Text
        className={`font-medium ${
          isActive ? 'text-white' : 'text-gray-700 dark:text-gray-300'
        }`}
      >
        {category.name}
      </Text>
    </Pressable>
  </Animated.View>
);

// Store Card (ร้านค้าทางการ / ร้านแนะนำ)
const StoreCard = ({
  store,
  index,
  isOfficial,
  onPress,
}: {
  store: Store;
  index: number;
  isOfficial: boolean;
  onPress: () => void;
}) => (
  <Animated.View
    entering={FadeInRight.delay(index * 80).springify()}
    style={{ marginRight: 12, width: 140 }}
  >
    <Pressable
      onPress={onPress}
      className="bg-white dark:bg-dark-50 rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700"
      style={({ pressed }) => ({ opacity: pressed ? 0.9 : 1 })}
    >
      {/* Store Image */}
      <View className="h-24 bg-gray-100 dark:bg-gray-800 items-center justify-center relative">
        {store.logo ? (
          <Image
            source={{ uri: store.logo }}
            className="w-full h-full"
            resizeMode="cover"
          />
        ) : (
          <Ionicons name="storefront-outline" size={36} color="#9CA3AF" />
        )}

        {/* Badge */}
        {isOfficial ? (
          <View className="absolute top-2 right-2 bg-blue-500 px-1.5 py-0.5 rounded-full flex-row items-center">
            <Ionicons name="checkmark-circle" size={10} color="white" />
            <Text className="text-white text-[8px] ml-0.5 font-bold">ทางการ</Text>
          </View>
        ) : (
          <View className="absolute top-2 right-2 bg-amber-500 px-1.5 py-0.5 rounded-full flex-row items-center">
            <Ionicons name="star" size={10} color="white" />
            <Text className="text-white text-[8px] ml-0.5 font-bold">แนะนำ</Text>
          </View>
        )}
      </View>

      {/* Store Info */}
      <View className="p-2">
        <Text
          className="text-gray-900 dark:text-white font-semibold text-sm"
          numberOfLines={1}
        >
          {store.name}
        </Text>
        <View className="flex-row items-center mt-1">
          <Ionicons name="star" size={12} color="#FBBF24" />
          <Text className="text-gray-500 text-xs ml-1">
            {store.rating.toFixed(1)} • {store.productCount} สินค้า
          </Text>
        </View>
      </View>
    </Pressable>
  </Animated.View>
);

// Product Card
const ProductCard = ({
  product,
  index,
  onPress,
}: {
  product: Product;
  index: number;
  onPress: () => void;
}) => {
  const hasDiscount = product.discount_price && product.discount_price < product.price;

  return (
    <Animated.View
      entering={FadeInDown.delay(50 + (index % 10) * 30).springify()}
      style={{ width: (width - 48) / 2, marginBottom: 16 }}
    >
      <Pressable
        onPress={onPress}
        className="bg-white dark:bg-dark-50 rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700"
        style={({ pressed }) => ({ opacity: pressed ? 0.9 : 1 })}
      >
        {/* Image */}
        <View className="aspect-square bg-gray-100 dark:bg-gray-800 items-center justify-center">
          {product.image ? (
            <Image
              source={{ uri: product.image }}
              className="w-full h-full"
              resizeMode="cover"
            />
          ) : (
            <Ionicons name="cube-outline" size={48} color="#9CA3AF" />
          )}

          {/* Discount Badge */}
          {hasDiscount && (
            <View className="absolute top-2 left-2 bg-red-500 px-2 py-0.5 rounded-full">
              <Text className="text-white text-xs font-bold">
                -{Math.round((1 - product.discount_price! / product.price) * 100)}%
              </Text>
            </View>
          )}

          {/* Commission Badge */}
          {product.commission_rate && product.commission_rate > 0 && (
            <View className="absolute top-2 right-2 bg-green-500 px-2 py-0.5 rounded-full">
              <Text className="text-white text-xs font-bold">
                +{product.commission_rate}%
              </Text>
            </View>
          )}
        </View>

        {/* Info */}
        <View className="p-3">
          <Text
            className="text-gray-900 dark:text-white font-medium text-sm"
            numberOfLines={2}
          >
            {product.name}
          </Text>
          <View className="flex-row items-center mt-2">
            {hasDiscount ? (
              <>
                <Text className="text-primary-500 font-bold">
                  {formatCurrency(product.discount_price!)}
                </Text>
                <Text className="text-gray-400 text-xs line-through ml-1">
                  {formatCurrency(product.price)}
                </Text>
              </>
            ) : (
              <Text className="text-primary-500 font-bold">
                {formatCurrency(product.price)}
              </Text>
            )}
          </View>

          {/* Rating */}
          {product.rating && (
            <View className="flex-row items-center mt-1">
              <Ionicons name="star" size={12} color="#FBBF24" />
              <Text className="text-gray-500 text-xs ml-1">
                {product.rating.toFixed(1)} ({product.review_count || 0})
              </Text>
            </View>
          )}
        </View>
      </Pressable>
    </Animated.View>
  );
};

// Section Header
const SectionHeader = ({
  title,
  subtitle,
  onSeeAll,
  isDark,
}: {
  title: string;
  subtitle?: string;
  onSeeAll?: () => void;
  isDark: boolean;
}) => (
  <View className="flex-row justify-between items-center px-4 mb-3">
    <View>
      <Text className={`text-lg font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
        {title}
      </Text>
      {subtitle && (
        <Text className="text-gray-500 text-sm">{subtitle}</Text>
      )}
    </View>
    {onSeeAll && (
      <Pressable onPress={onSeeAll} className="flex-row items-center">
        <Text className="text-primary-500 font-medium text-sm">ดูทั้งหมด</Text>
        <Ionicons name="chevron-forward" size={16} color="#3B82F6" />
      </Pressable>
    )}
  </View>
);

// Loading Footer
const LoadingFooter = ({ isLoading }: { isLoading: boolean }) => {
  if (!isLoading) return null;
  return (
    <View className="py-4 items-center">
      <ActivityIndicator size="small" color="#3B82F6" />
      <Text className="text-gray-500 text-sm mt-2">กำลังโหลดเพิ่ม...</Text>
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
      // ดึงจาก API หรือใช้ mock data
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

          // Check if has more
          setHasMore(freshProducts.length >= ITEMS_PER_PAGE);

          // Cache first page
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
        // Offline - load from cache
        const cachedProducts = await Cache.getCache<Product[]>(
          Cache.CACHE_KEYS.PRODUCTS
        );
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
    const matchesSearch = product.name
      .toLowerCase()
      .includes(searchQuery.toLowerCase());
    return matchesSearch;
  });

  // Header Component (Banner + Categories + Stores)
  const ListHeaderComponent = () => (
    <>
      {/* Banner โฆษณา */}
      <View className="pt-2 pb-4">
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
      <View className="mb-4">
        <SectionHeader
          title="🏪 ร้านค้าทางการ"
          subtitle="ร้านค้าที่ได้รับการรับรอง"
          onSeeAll={() => router.push('/stores?type=official')}
          isDark={isDark}
        />
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{ paddingHorizontal: 16 }}
        >
          {officialStores.map((store, index) => (
            <StoreCard
              key={store.id}
              store={store}
              index={index}
              isOfficial={true}
              onPress={() => router.push(`/store/${store.id}`)}
            />
          ))}
        </ScrollView>
      </View>

      {/* ร้านแนะนำติดดาว */}
      <View className="mb-4">
        <SectionHeader
          title="⭐ ร้านแนะนำติดดาว"
          subtitle="คัดสรรโดยทีมงาน"
          onSeeAll={() => router.push('/stores?type=featured')}
          isDark={isDark}
        />
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{ paddingHorizontal: 16 }}
        >
          {featuredStores.map((store, index) => (
            <StoreCard
              key={store.id}
              store={store}
              index={index}
              isOfficial={false}
              onPress={() => router.push(`/store/${store.id}`)}
            />
          ))}
        </ScrollView>
      </View>

      {/* Categories */}
      <View className="mb-4">
        <SectionHeader title="หมวดหมู่" isDark={isDark} />
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{ paddingHorizontal: 16 }}
        >
          <CategoryChip
            category={{ id: '', name: 'ทั้งหมด', slug: 'all' }}
            isActive={selectedCategory === null}
            onPress={() => setSelectedCategory(null)}
            index={0}
          />
          {categories.map((category, index) => (
            <CategoryChip
              key={category.id}
              category={category}
              isActive={selectedCategory === category.id}
              onPress={() => setSelectedCategory(category.id)}
              index={index + 1}
            />
          ))}
        </ScrollView>
      </View>

      {/* Results Count */}
      <View className="px-4 mb-3">
        <Text className="text-gray-500 dark:text-gray-400 text-sm">
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
        <View className="flex-1 items-center justify-center py-16">
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text className="text-gray-500 mt-4">กำลังโหลดสินค้า...</Text>
        </View>
      );
    }

    return (
      <View className="flex-1 items-center justify-center py-16">
        <Ionicons
          name="cube-outline"
          size={64}
          color={isDark ? '#4B5563' : '#9CA3AF'}
        />
        <Text className="text-gray-500 dark:text-gray-400 mt-4 text-center">
          {searchQuery
            ? `ไม่พบสินค้าที่ค้นหา "${searchQuery}"`
            : 'ไม่มีสินค้าในหมวดหมู่นี้'}
        </Text>
      </View>
    );
  };

  // Footer Component (Commission Banner + Loading)
  const ListFooterComponent = () => (
    <>
      <LoadingFooter isLoading={isLoadingMore} />

      {/* Commission Info Banner */}
      {isAuthenticated && !isLoadingMore && filteredProducts.length > 0 && (
        <Animated.View
          entering={FadeIn.delay(300)}
          className="mx-4 mb-6"
        >
          <LinearGradient
            colors={['#10B981', '#059669']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            className="rounded-2xl p-4"
          >
            <View className="flex-row items-center">
              <View className="w-12 h-12 rounded-xl bg-white/20 items-center justify-center mr-3">
                <Ionicons name="cash" size={24} color="white" />
              </View>
              <View className="flex-1">
                <Text className="text-white font-bold">รับคอมมิชชั่นทุกการขาย!</Text>
                <Text className="text-green-100 text-sm">
                  แชร์ลิงก์สินค้าและรับค่าคอมมิชชั่นสูงสุด 30%
                </Text>
              </View>
            </View>
          </LinearGradient>
        </Animated.View>
      )}
    </>
  );

  // Render Product Item
  const renderProduct = ({ item, index }: { item: Product; index: number }) => (
    <ProductCard
      product={item}
      index={index}
      onPress={() => router.push(`/product/${item.id}`)}
    />
  );

  return (
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
      <Stack.Screen
        options={{
          headerShown: true,
          title: 'ช้อปปิ้ง',
          headerStyle: {
            backgroundColor: isDark ? '#0F172A' : '#FFFFFF',
          },
          headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
          headerLeft: () => (
            <Pressable onPress={() => router.back()} className="p-2 -ml-2">
              <Ionicons
                name="arrow-back"
                size={24}
                color={isDark ? '#FFFFFF' : '#1F2937'}
              />
            </Pressable>
          ),
          headerRight: () => (
            <Pressable
              onPress={() => router.push('/cart')}
              className="p-2 -mr-2"
            >
              <Ionicons
                name="cart-outline"
                size={24}
                color={isDark ? '#FFFFFF' : '#1F2937'}
              />
            </Pressable>
          ),
        }}
      />

      <SafeAreaView className="flex-1" edges={['bottom']}>
        {/* Offline Banner */}
        {!isOnline && (
          <View className="bg-yellow-100 dark:bg-yellow-900/30 px-4 py-2">
            <Text className="text-yellow-700 dark:text-yellow-400 text-sm text-center">
              📡 ออฟไลน์ - แสดงสินค้าที่บันทึกไว้
            </Text>
          </View>
        )}

        {/* Search Bar */}
        <View className="px-4 pt-4 pb-2">
          <View
            className={`flex-row items-center px-4 py-3 rounded-xl ${
              isDark ? 'bg-dark-50' : 'bg-white'
            } border border-gray-200 dark:border-gray-700`}
          >
            <Ionicons
              name="search"
              size={20}
              color={isDark ? '#9CA3AF' : '#6B7280'}
            />
            <TextInput
              value={searchQuery}
              onChangeText={setSearchQuery}
              placeholder="ค้นหาสินค้า..."
              placeholderTextColor={isDark ? '#6B7280' : '#9CA3AF'}
              className="flex-1 ml-3 text-gray-900 dark:text-white"
            />
            {searchQuery.length > 0 && (
              <Pressable onPress={() => setSearchQuery('')}>
                <Ionicons
                  name="close-circle"
                  size={20}
                  color={isDark ? '#6B7280' : '#9CA3AF'}
                />
              </Pressable>
            )}
          </View>
        </View>

        {/* Products Grid with Infinite Scroll */}
        <FlatList
          ref={flatListRef}
          data={filteredProducts}
          renderItem={renderProduct}
          keyExtractor={(item) => item.id.toString()}
          numColumns={2}
          columnWrapperStyle={{ paddingHorizontal: 16, justifyContent: 'space-between' }}
          contentContainerStyle={{ paddingBottom: 20 }}
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
      </SafeAreaView>
    </View>
  );
}
