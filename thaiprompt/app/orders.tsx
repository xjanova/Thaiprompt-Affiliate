/**
 * Orders Screen - หน้ารายการคำสั่งซื้อ
 *
 * แสดงรายการคำสั่งซื้อทั้งหมดของลูกค้า
 * พร้อมกรองตามสถานะและดูรายละเอียด
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  RefreshControl,
  Image,
  ActivityIndicator,
  FlatList,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router, Stack } from 'expo-router';
import Animated, { FadeInDown, FadeInRight } from 'react-native-reanimated';
import { Ionicons } from '@expo/vector-icons';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import { getOrders, type OrderSummary } from '@/services/api';
import { formatCurrency } from '@/constants';

// Tab สถานะคำสั่งซื้อ
const statusTabs = [
  { key: 'all', label: 'ทั้งหมด' },
  { key: 'pending', label: 'รอดำเนินการ' },
  { key: 'processing', label: 'กำลังจัดส่ง' },
  { key: 'shipped', label: 'จัดส่งแล้ว' },
  { key: 'delivered', label: 'ได้รับแล้ว' },
  { key: 'cancelled', label: 'ยกเลิก' },
];

// สีสถานะ
const getStatusColor = (status: string) => {
  switch (status) {
    case 'pending':
      return '#F59E0B'; // Amber
    case 'confirmed':
      return '#3B82F6'; // Blue
    case 'processing':
      return '#8B5CF6'; // Purple
    case 'shipped':
      return '#06B6D4'; // Cyan
    case 'delivered':
    case 'completed':
      return '#10B981'; // Green
    case 'cancelled':
    case 'refunded':
      return '#EF4444'; // Red
    default:
      return '#6B7280'; // Gray
  }
};

// Filter Tab Component
const FilterTab = ({
  label,
  isActive,
  onPress,
}: {
  label: string;
  isActive: boolean;
  onPress: () => void;
}) => (
  <Pressable
    onPress={onPress}
    className={`px-4 py-2 rounded-lg mr-2 ${
      isActive ? 'bg-primary-500' : 'bg-gray-100 dark:bg-gray-800'
    }`}
  >
    <Text
      className={`font-medium ${
        isActive ? 'text-white' : 'text-gray-600 dark:text-gray-400'
      }`}
    >
      {label}
    </Text>
  </Pressable>
);

// Order Card Component
const OrderCard = ({
  order,
  delay,
  onPress,
}: {
  order: OrderSummary;
  delay: number;
  onPress: () => void;
}) => {
  const statusColor = getStatusColor(order.status);

  return (
    <Animated.View entering={FadeInDown.delay(delay).springify()}>
      <Pressable
        onPress={onPress}
        className="bg-white dark:bg-dark-50 rounded-2xl p-4 mb-3 border border-gray-100 dark:border-gray-700"
        style={{ elevation: 2 }}
      >
        {/* Header */}
        <View className="flex-row justify-between items-start mb-3">
          <View className="flex-row items-center flex-1">
            <View className="flex-1">
              <Text className="text-sm text-gray-500 dark:text-gray-400">
                {order.order_number}
              </Text>
              <Text className="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                {new Date(order.created_at).toLocaleDateString('th-TH', {
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit',
                })}
              </Text>
            </View>
            {/* Unread Message Badge */}
            {order.has_unread_messages && (
              <View className="bg-red-500 rounded-full w-6 h-6 items-center justify-center mr-2">
                <Ionicons name="chatbubble" size={12} color="white" />
              </View>
            )}
          </View>
          <View
            className="px-3 py-1 rounded-full"
            style={{ backgroundColor: statusColor + '20' }}
          >
            <Text style={{ color: statusColor }} className="text-xs font-medium">
              {order.status_label}
            </Text>
          </View>
        </View>

        {/* Product Info */}
        {order.first_item && (
          <View className="flex-row items-center mb-3">
            {order.first_item.product_image ? (
              <Image
                source={{ uri: order.first_item.product_image }}
                className="w-16 h-16 rounded-xl"
                resizeMode="cover"
              />
            ) : (
              <View className="w-16 h-16 rounded-xl bg-gray-100 dark:bg-gray-800 items-center justify-center">
                <Ionicons name="cube-outline" size={24} color="#9CA3AF" />
              </View>
            )}
            <View className="flex-1 ml-3">
              <Text
                className="text-base font-medium text-gray-900 dark:text-white"
                numberOfLines={2}
              >
                {order.first_item.product_name}
              </Text>
              {order.items_count > 1 && (
                <Text className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  +{order.items_count - 1} รายการอื่น
                </Text>
              )}
            </View>
          </View>
        )}

        {/* Footer */}
        <View className="flex-row justify-between items-center pt-3 border-t border-gray-100 dark:border-gray-700">
          <Text className="text-sm text-gray-500 dark:text-gray-400">
            {order.items_count} รายการ
          </Text>
          <Text className="text-lg font-bold text-primary-500">
            {formatCurrency(order.total_amount)}
          </Text>
        </View>
      </Pressable>
    </Animated.View>
  );
};

// Empty State Component
const EmptyState = ({ status }: { status: string }) => (
  <View className="items-center justify-center py-20">
    <Ionicons name="receipt-outline" size={64} color="#9CA3AF" />
    <Text className="text-gray-500 dark:text-gray-400 text-lg mt-4">
      {status === 'all'
        ? 'ยังไม่มีคำสั่งซื้อ'
        : `ไม่มีคำสั่งซื้อในสถานะนี้`}
    </Text>
    <Pressable
      onPress={() => router.push('/shopping')}
      className="mt-4 bg-primary-500 px-6 py-3 rounded-xl"
    >
      <Text className="text-white font-medium">เริ่มช้อปปิ้ง</Text>
    </Pressable>
  </View>
);

export default function OrdersScreen() {
  const { isDarkMode } = useAppStore();
  const { isAuthenticated } = useAuthStore();
  const [orders, setOrders] = useState<OrderSummary[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [activeTab, setActiveTab] = useState('all');
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [isLoadingMore, setIsLoadingMore] = useState(false);

  // โหลดรายการคำสั่งซื้อ
  const loadOrders = useCallback(
    async (refresh: boolean = false) => {
      if (!isAuthenticated) {
        router.replace('/login');
        return;
      }

      try {
        if (refresh) {
          setIsRefreshing(true);
          setPage(1);
        } else {
          setIsLoading(true);
        }

        const status = activeTab === 'all' ? undefined : activeTab;
        const result = await getOrders(1, 15, status);

        if (result) {
          setOrders(result.orders);
          setHasMore(result.pagination.current_page < result.pagination.last_page);
        }
      } catch (error) {
        console.error('Load orders error:', error);
      } finally {
        setIsLoading(false);
        setIsRefreshing(false);
      }
    },
    [isAuthenticated, activeTab]
  );

  // โหลดเพิ่มเติม
  const loadMore = useCallback(async () => {
    if (isLoadingMore || !hasMore) return;

    try {
      setIsLoadingMore(true);
      const nextPage = page + 1;
      const status = activeTab === 'all' ? undefined : activeTab;
      const result = await getOrders(nextPage, 15, status);

      if (result) {
        setOrders((prev) => [...prev, ...result.orders]);
        setPage(nextPage);
        setHasMore(result.pagination.current_page < result.pagination.last_page);
      }
    } catch (error) {
      console.error('Load more orders error:', error);
    } finally {
      setIsLoadingMore(false);
    }
  }, [isLoadingMore, hasMore, page, activeTab]);

  // โหลดเมื่อเปลี่ยน tab
  useEffect(() => {
    loadOrders(true);
  }, [activeTab]);

  // Initial load
  useEffect(() => {
    loadOrders();
  }, []);

  return (
    <SafeAreaView
      className={`flex-1 ${isDarkMode ? 'bg-dark-100' : 'bg-gray-50'}`}
      edges={['top']}
    >
      <Stack.Screen
        options={{
          headerShown: false,
        }}
      />

      {/* Header */}
      <LinearGradient
        colors={isDarkMode ? ['#1F2937', '#111827'] : ['#3B82F6', '#1D4ED8']}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        className="px-4 pt-2 pb-4"
      >
        {/* Back Button & Title */}
        <View className="flex-row items-center mb-4">
          <Pressable
            onPress={() => router.back()}
            className="w-10 h-10 rounded-full bg-white/20 items-center justify-center mr-3"
          >
            <Ionicons name="arrow-back" size={24} color="white" />
          </Pressable>
          <Text className="text-2xl font-bold text-white">คำสั่งซื้อ</Text>
        </View>

        {/* Status Tabs */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          className="flex-row"
        >
          {statusTabs.map((tab) => (
            <FilterTab
              key={tab.key}
              label={tab.label}
              isActive={activeTab === tab.key}
              onPress={() => setActiveTab(tab.key)}
            />
          ))}
        </ScrollView>
      </LinearGradient>

      {/* Content */}
      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text className="text-gray-500 dark:text-gray-400 mt-3">
            กำลังโหลด...
          </Text>
        </View>
      ) : orders.length === 0 ? (
        <EmptyState status={activeTab} />
      ) : (
        <FlatList
          data={orders}
          keyExtractor={(item) => item.id.toString()}
          renderItem={({ item, index }) => (
            <OrderCard
              order={item}
              delay={index * 50}
              onPress={() => router.push(`/order/${item.id}`)}
            />
          )}
          contentContainerStyle={{ padding: 16 }}
          refreshControl={
            <RefreshControl
              refreshing={isRefreshing}
              onRefresh={() => loadOrders(true)}
              colors={['#3B82F6']}
            />
          }
          onEndReached={loadMore}
          onEndReachedThreshold={0.5}
          ListFooterComponent={
            isLoadingMore ? (
              <View className="py-4">
                <ActivityIndicator size="small" color="#3B82F6" />
              </View>
            ) : null
          }
        />
      )}
    </SafeAreaView>
  );
}
