/**
 * Shopping Screen - หน้าช้อปปิ้งสินค้า
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  RefreshControl,
  Image,
  TextInput,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, Stack } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import Animated, { FadeInDown, FadeInRight } from 'react-native-reanimated';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import { getProducts, getProductCategories } from '@/services/api';
import * as Cache from '@/services/cache';
import * as Network from '@/services/network';
import { formatCurrency } from '@/constants';
import type { Product, ProductCategory } from '@/types';

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
      entering={FadeInDown.delay(100 + index * 50).springify()}
      className="w-[48%] mb-4"
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

export default function ShoppingScreen() {
  const { resolvedTheme } = useAppStore();
  const { isAuthenticated } = useAuthStore();
  const isDark = resolvedTheme === 'dark';

  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<ProductCategory[]>([]);
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [refreshing, setRefreshing] = useState(false);
  const [isOnline, setIsOnline] = useState(true);

  // โหลดข้อมูล
  const loadData = useCallback(async () => {
    try {
      const online = await Network.checkNetworkStatus();
      setIsOnline(online);

      // ดึง categories จาก cache
      const cachedCategories = await Cache.getCache<ProductCategory[]>(
        Cache.CACHE_KEYS.PRODUCTS_CATEGORIES
      );
      if (cachedCategories) setCategories(cachedCategories);

      // ดึง products จาก cache
      const cachedProducts = await Cache.getCache<Product[]>(
        Cache.CACHE_KEYS.PRODUCTS
      );
      if (cachedProducts) setProducts(cachedProducts);

      // ถ้า online ให้ดึงข้อมูลใหม่
      if (online) {
        const [freshCategories, freshProducts] = await Promise.all([
          getProductCategories(),
          getProducts({ category: selectedCategory }),
        ]);

        if (freshCategories) {
          setCategories(freshCategories);
          await Cache.setCache(
            Cache.CACHE_KEYS.PRODUCTS_CATEGORIES,
            freshCategories,
            Cache.LONG_CACHE_DURATION
          );
        }

        if (freshProducts) {
          setProducts(freshProducts);
          await Cache.setCache(
            Cache.CACHE_KEYS.PRODUCTS,
            freshProducts,
            Cache.DEFAULT_CACHE_DURATION
          );
        }
      }
    } catch (error) {
      console.error('Load shopping data error:', error);
    }
  }, [selectedCategory]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  };

  // Filter products
  const filteredProducts = products.filter((product) => {
    const matchesSearch = product.name
      .toLowerCase()
      .includes(searchQuery.toLowerCase());
    const matchesCategory =
      !selectedCategory || product.category_id === selectedCategory;
    return matchesSearch && matchesCategory;
  });

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

        {/* Categories */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          className="px-4 py-2"
          contentContainerStyle={{ paddingRight: 16 }}
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

        {/* Products Grid */}
        <ScrollView
          className="flex-1 px-4"
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              tintColor="#3B82F6"
            />
          }
        >
          {/* Results Count */}
          <Text className="text-gray-500 dark:text-gray-400 text-sm mb-3">
            พบ {filteredProducts.length} รายการ
          </Text>

          {/* Product Grid */}
          <View className="flex-row flex-wrap justify-between">
            {filteredProducts.length > 0 ? (
              filteredProducts.map((product, index) => (
                <ProductCard
                  key={product.id}
                  product={product}
                  index={index}
                  onPress={() => router.push(`/product/${product.id}`)}
                />
              ))
            ) : (
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
            )}
          </View>

          {/* Commission Info Banner */}
          {isAuthenticated && (
            <Animated.View
              entering={FadeInDown.delay(300).springify()}
              className="bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl p-4 mt-4 mb-6"
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
            </Animated.View>
          )}
        </ScrollView>
      </SafeAreaView>
    </View>
  );
}
