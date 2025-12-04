/**
 * Commissions Screen - หน้าคอมมิชชั่น
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  RefreshControl,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router, Stack } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import Animated, { FadeInDown, FadeInRight } from 'react-native-reanimated';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import { getCommissions, getDashboardStats } from '@/services/api';
import * as Cache from '@/services/cache';
import * as Network from '@/services/network';
import { formatCurrency } from '@/constants';
import type { Commission, DashboardStats } from '@/types';

// Filter Tab
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

// Stat Card
const StatCard = ({
  label,
  value,
  icon,
  color,
  delay,
}: {
  label: string;
  value: string;
  icon: keyof typeof Ionicons.glyphMap;
  color: string;
  delay: number;
}) => (
  <Animated.View
    entering={FadeInRight.delay(delay).springify()}
    className="flex-1 mx-1"
  >
    <View className="bg-white dark:bg-dark-50 rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
      <View
        className="w-10 h-10 rounded-xl items-center justify-center mb-2"
        style={{ backgroundColor: color + '20' }}
      >
        <Ionicons name={icon} size={20} color={color} />
      </View>
      <Text className="text-gray-500 dark:text-gray-400 text-xs">{label}</Text>
      <Text className="text-gray-900 dark:text-white font-bold text-lg mt-0.5">
        {value}
      </Text>
    </View>
  </Animated.View>
);

// Commission Item
const CommissionItem = ({
  commission,
  index,
}: {
  commission: Commission;
  index: number;
}) => {
  const statusConfig = {
    pending: {
      bg: 'bg-yellow-100',
      text: 'text-yellow-600',
      label: 'รอดำเนินการ',
    },
    approved: {
      bg: 'bg-green-100',
      text: 'text-green-600',
      label: 'อนุมัติแล้ว',
    },
    paid: {
      bg: 'bg-blue-100',
      text: 'text-blue-600',
      label: 'จ่ายแล้ว',
    },
    cancelled: {
      bg: 'bg-red-100',
      text: 'text-red-600',
      label: 'ยกเลิก',
    },
  };

  const status = statusConfig[commission.status] || statusConfig.pending;

  const typeConfig = {
    direct: { icon: 'cart', label: 'ขายตรง' },
    team: { icon: 'people', label: 'ทีม' },
    referral: { icon: 'share-social', label: 'แนะนำ' },
    bonus: { icon: 'gift', label: 'โบนัส' },
  };

  const type = typeConfig[commission.type as keyof typeof typeConfig] || typeConfig.direct;

  return (
    <Animated.View
      entering={FadeInDown.delay(100 + index * 50).springify()}
      className="bg-white dark:bg-dark-50 rounded-2xl p-4 mb-3 border border-gray-100 dark:border-gray-700"
    >
      <View className="flex-row items-start">
        {/* Icon */}
        <View className="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-900/30 items-center justify-center mr-3">
          <Ionicons
            name={type.icon as keyof typeof Ionicons.glyphMap}
            size={24}
            color="#3B82F6"
          />
        </View>

        {/* Info */}
        <View className="flex-1">
          <View className="flex-row items-center justify-between">
            <Text className="text-gray-900 dark:text-white font-semibold">
              {commission.description || `คอมมิชชั่น${type.label}`}
            </Text>
            <Text className="text-primary-500 font-bold">
              +{formatCurrency(commission.amount)}
            </Text>
          </View>

          <View className="flex-row items-center mt-1">
            <Text className="text-gray-500 dark:text-gray-400 text-xs">
              {type.label}
            </Text>
            {commission.level && (
              <Text className="text-gray-400 text-xs ml-2">
                Level {commission.level}
              </Text>
            )}
          </View>

          <View className="flex-row items-center justify-between mt-2">
            <Text className="text-gray-400 text-xs">{commission.date}</Text>
            <View className={`px-2 py-0.5 rounded-full ${status.bg}`}>
              <Text className={`text-xs font-medium ${status.text}`}>
                {status.label}
              </Text>
            </View>
          </View>
        </View>
      </View>

      {/* From User */}
      {commission.from_user && (
        <View className="flex-row items-center mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
          <Ionicons name="person-circle" size={20} color="#9CA3AF" />
          <Text className="text-gray-500 dark:text-gray-400 text-sm ml-2">
            จาก {commission.from_user.name}
          </Text>
        </View>
      )}
    </Animated.View>
  );
};

export default function CommissionsScreen() {
  const { resolvedTheme } = useAppStore();
  const { isAuthenticated } = useAuthStore();
  const isDark = resolvedTheme === 'dark';

  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [commissions, setCommissions] = useState<Commission[]>([]);
  const [filter, setFilter] = useState<string>('all');
  const [refreshing, setRefreshing] = useState(false);
  const [isOnline, setIsOnline] = useState(true);

  const filters = [
    { key: 'all', label: 'ทั้งหมด' },
    { key: 'pending', label: 'รอดำเนินการ' },
    { key: 'approved', label: 'อนุมัติแล้ว' },
    { key: 'paid', label: 'จ่ายแล้ว' },
  ];

  // โหลดข้อมูล
  const loadData = useCallback(async () => {
    if (!isAuthenticated) return;

    try {
      const online = await Network.checkNetworkStatus();
      setIsOnline(online);

      // ดึงจาก cache ก่อน
      const cachedStats = await Cache.getCache<DashboardStats>(
        Cache.CACHE_KEYS.DASHBOARD_STATS
      );
      if (cachedStats) setStats(cachedStats);

      const cachedCommissions = await Cache.getCache<Commission[]>(
        Cache.CACHE_KEYS.COMMISSIONS_LIST
      );
      if (cachedCommissions) setCommissions(cachedCommissions);

      // ถ้า online ให้ดึงข้อมูลใหม่
      if (online) {
        const [freshStats, freshCommissions] = await Promise.all([
          getDashboardStats(),
          getCommissions(),
        ]);

        if (freshStats) {
          setStats(freshStats);
          await Cache.setCache(
            Cache.CACHE_KEYS.DASHBOARD_STATS,
            freshStats,
            Cache.OFFLINE_CACHE_DURATION
          );
        }

        if (freshCommissions) {
          setCommissions(freshCommissions);
          await Cache.setCache(
            Cache.CACHE_KEYS.COMMISSIONS_LIST,
            freshCommissions,
            Cache.OFFLINE_CACHE_DURATION
          );
        }
      }
    } catch (error) {
      console.error('Load commissions error:', error);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  };

  // Filter commissions
  const filteredCommissions =
    filter === 'all'
      ? commissions
      : commissions.filter((c) => c.status === filter);

  // ถ้ายังไม่ login
  if (!isAuthenticated) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <Stack.Screen
          options={{
            headerShown: true,
            title: 'คอมมิชชั่น',
            headerStyle: { backgroundColor: isDark ? '#0F172A' : '#FFFFFF' },
            headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
          }}
        />
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <Ionicons
            name="cash"
            size={80}
            color={isDark ? '#4B5563' : '#9CA3AF'}
          />
          <Text
            className={`text-xl font-bold mt-4 ${
              isDark ? 'text-white' : 'text-gray-800'
            }`}
          >
            ดูคอมมิชชั่นของคุณ
          </Text>
          <Text className="text-gray-500 text-center mt-2">
            เข้าสู่ระบบเพื่อดูรายได้และคอมมิชชั่น
          </Text>
          <Pressable
            onPress={() => router.push('/login')}
            className="bg-primary-500 px-8 py-3 rounded-xl mt-6"
          >
            <Text className="text-white font-bold">เข้าสู่ระบบ</Text>
          </Pressable>
        </SafeAreaView>
      </View>
    );
  }

  return (
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
      <Stack.Screen
        options={{
          headerShown: true,
          title: 'คอมมิชชั่น',
          headerStyle: { backgroundColor: isDark ? '#0F172A' : '#FFFFFF' },
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
        }}
      />

      <SafeAreaView className="flex-1" edges={['bottom']}>
        <ScrollView
          className="flex-1"
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              tintColor="#3B82F6"
            />
          }
        >
          {/* Offline Banner */}
          {!isOnline && (
            <View className="bg-yellow-100 dark:bg-yellow-900/30 px-4 py-2 mx-4 mt-4 rounded-xl">
              <Text className="text-yellow-700 dark:text-yellow-400 text-sm text-center">
                📡 ออฟไลน์ - แสดงข้อมูลที่บันทึกไว้
              </Text>
            </View>
          )}

          {/* Summary Card */}
          <Animated.View
            entering={FadeInDown.springify()}
            className="px-4 pt-4"
          >
            <LinearGradient
              colors={isDark ? ['#1E3A8A', '#1E40AF'] : ['#3B82F6', '#2563EB']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              className="rounded-3xl p-6"
            >
              <Text className="text-blue-100">รายได้ทั้งหมด</Text>
              <Text className="text-white text-4xl font-bold mt-2">
                {formatCurrency(stats?.totalEarnings || 0)}
              </Text>

              <View className="flex-row mt-4">
                <View className="flex-1">
                  <Text className="text-blue-200 text-xs">เดือนนี้</Text>
                  <Text className="text-white font-semibold text-lg">
                    {formatCurrency(stats?.monthlyEarnings || 0)}
                  </Text>
                </View>
                <View className="flex-1">
                  <Text className="text-blue-200 text-xs">รอดำเนินการ</Text>
                  <Text className="text-white font-semibold text-lg">
                    {formatCurrency(stats?.pendingEarnings || 0)}
                  </Text>
                </View>
              </View>
            </LinearGradient>
          </Animated.View>

          {/* Stats Cards */}
          <View className="flex-row px-3 mt-4">
            <StatCard
              label="ยอดขายตรง"
              value={formatCurrency(stats?.directSales || 0)}
              icon="cart"
              color="#10B981"
              delay={100}
            />
            <StatCard
              label="โบนัสทีม"
              value={formatCurrency(stats?.teamBonus || 0)}
              icon="people"
              color="#8B5CF6"
              delay={150}
            />
          </View>

          {/* Filter Tabs */}
          <View className="px-4 mt-6">
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              className="-mx-1"
            >
              {filters.map((f) => (
                <FilterTab
                  key={f.key}
                  label={f.label}
                  isActive={filter === f.key}
                  onPress={() => setFilter(f.key)}
                />
              ))}
            </ScrollView>
          </View>

          {/* Commission List */}
          <View className="px-4 mt-4 pb-6">
            <Text
              className={`text-base font-bold mb-3 ${
                isDark ? 'text-white' : 'text-gray-800'
              }`}
            >
              รายการคอมมิชชั่น ({filteredCommissions.length})
            </Text>

            {filteredCommissions.length > 0 ? (
              filteredCommissions.map((commission, index) => (
                <CommissionItem
                  key={commission.id}
                  commission={commission}
                  index={index}
                />
              ))
            ) : (
              <View className="bg-white dark:bg-dark-50 rounded-2xl p-8 items-center border border-gray-100 dark:border-gray-700">
                <Ionicons
                  name="cash-outline"
                  size={48}
                  color={isDark ? '#4B5563' : '#9CA3AF'}
                />
                <Text className="text-gray-500 dark:text-gray-400 mt-3 text-center">
                  ยังไม่มีรายการคอมมิชชั่น
                </Text>
                <Pressable
                  onPress={() => router.push('/shopping')}
                  className="bg-primary-500 px-6 py-2.5 rounded-xl mt-4"
                >
                  <Text className="text-white font-semibold">
                    เริ่มขายสินค้า
                  </Text>
                </Pressable>
              </View>
            )}
          </View>
        </ScrollView>
      </SafeAreaView>
    </View>
  );
}
