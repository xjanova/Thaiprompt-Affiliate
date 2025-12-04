/**
 * Wallet Screen - หน้ากระเป๋าเงิน
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  RefreshControl,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import Animated, { FadeInDown, FadeInRight } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { getDashboardStats } from '@/services/api';
import * as Cache from '@/services/cache';
import * as Network from '@/services/network';
import { formatCurrency } from '@/constants';
import type { DashboardStats } from '@/types';

// Action Button
const ActionButton = ({
  icon,
  label,
  color,
  onPress,
  delay,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  color: string;
  onPress: () => void;
  delay: number;
}) => (
  <Animated.View entering={FadeInDown.delay(delay).springify()} className="flex-1 mx-1">
    <Pressable
      onPress={onPress}
      className="bg-white dark:bg-dark-50 rounded-2xl p-4 items-center border border-gray-100 dark:border-gray-700"
      style={({ pressed }) => ({ opacity: pressed ? 0.7 : 1 })}
    >
      <View
        className="w-12 h-12 rounded-xl items-center justify-center mb-2"
        style={{ backgroundColor: color }}
      >
        <Ionicons name={icon} size={24} color="white" />
      </View>
      <Text className="text-gray-700 dark:text-gray-300 font-medium text-sm">
        {label}
      </Text>
    </Pressable>
  </Animated.View>
);

// Transaction Item
const TransactionItem = ({
  type,
  title,
  amount,
  date,
  status,
  index,
}: {
  type: 'in' | 'out';
  title: string;
  amount: number;
  date: string;
  status: 'completed' | 'pending';
  index: number;
}) => {
  const isIncome = type === 'in';
  const statusColor =
    status === 'completed'
      ? 'bg-green-100 text-green-600'
      : 'bg-yellow-100 text-yellow-600';
  const statusText = status === 'completed' ? 'สำเร็จ' : 'รอดำเนินการ';

  return (
    <Animated.View
      entering={FadeInDown.delay(200 + index * 50).springify()}
      className="bg-white dark:bg-dark-50 rounded-xl p-4 mb-2 flex-row items-center border border-gray-100 dark:border-gray-700"
    >
      <View
        className={`w-10 h-10 rounded-full items-center justify-center mr-3 ${
          isIncome ? 'bg-green-100' : 'bg-red-100'
        }`}
      >
        <Ionicons
          name={isIncome ? 'arrow-down' : 'arrow-up'}
          size={20}
          color={isIncome ? '#10B981' : '#EF4444'}
        />
      </View>
      <View className="flex-1">
        <Text className="text-gray-900 dark:text-white font-medium">{title}</Text>
        <Text className="text-gray-500 text-xs">{date}</Text>
      </View>
      <View className="items-end">
        <Text
          className={`font-bold ${isIncome ? 'text-green-500' : 'text-red-500'}`}
        >
          {isIncome ? '+' : '-'}
          {formatCurrency(amount)}
        </Text>
        <View className={`px-2 py-0.5 rounded-full mt-1 ${statusColor}`}>
          <Text className="text-xs font-medium">{statusText}</Text>
        </View>
      </View>
    </Animated.View>
  );
};

export default function WalletScreen() {
  const { isAuthenticated, user } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [isOnline, setIsOnline] = useState(true);

  // Mock transactions - replace with real API
  const transactions = [
    { type: 'in' as const, title: 'คอมมิชชันจากการขาย', amount: 500, date: '20 พ.ย. 2024', status: 'completed' as const },
    { type: 'in' as const, title: 'โบนัสทีม', amount: 1200, date: '18 พ.ย. 2024', status: 'completed' as const },
    { type: 'out' as const, title: 'ถอนเงิน', amount: 2000, date: '15 พ.ย. 2024', status: 'completed' as const },
    { type: 'in' as const, title: 'คอมมิชชันจากการแนะนำ', amount: 300, date: '10 พ.ย. 2024', status: 'pending' as const },
  ];

  // โหลดข้อมูล
  const loadData = useCallback(async () => {
    if (!isAuthenticated) return;

    try {
      const online = await Network.checkNetworkStatus();
      setIsOnline(online);

      const cached = await Cache.getCache<DashboardStats>(
        Cache.CACHE_KEYS.DASHBOARD_STATS
      );
      if (cached) setStats(cached);

      if (online) {
        const freshData = await getDashboardStats();
        if (freshData) {
          setStats(freshData);
          await Cache.setCache(
            Cache.CACHE_KEYS.DASHBOARD_STATS,
            freshData,
            Cache.OFFLINE_CACHE_DURATION
          );
        }
      }
    } catch (error) {
      console.error('Load wallet data error:', error);
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

  // ถ้ายังไม่ login
  if (!isAuthenticated) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <Ionicons
            name="wallet"
            size={80}
            color={isDark ? '#4B5563' : '#9CA3AF'}
          />
          <Text className={`text-xl font-bold mt-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
            กระเป๋าเงินของคุณ
          </Text>
          <Text className="text-gray-500 text-center mt-2">
            เข้าสู่ระบบเพื่อดูยอดเงินและทำธุรกรรม
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
      <SafeAreaView className="flex-1" edges={['top']}>
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
          {/* Balance Card */}
          <Animated.View entering={FadeInRight.springify()} className="px-5 pt-4">
            <LinearGradient
              colors={['#10B981', '#059669']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              className="rounded-3xl p-6"
            >
              {/* Offline Badge */}
              {!isOnline && (
                <View className="absolute top-3 right-3 bg-yellow-400 px-2 py-0.5 rounded-full">
                  <Text className="text-yellow-900 text-xs font-medium">ออฟไลน์</Text>
                </View>
              )}

              <Text className="text-green-100">ยอดเงินคงเหลือ</Text>
              <Text className="text-white text-4xl font-bold mt-2">
                {formatCurrency(stats?.totalEarnings || 0)}
              </Text>

              <View className="flex-row mt-4">
                <View className="flex-1">
                  <Text className="text-green-200 text-xs">พร้อมถอน</Text>
                  <Text className="text-white font-semibold text-lg">
                    {formatCurrency(stats?.approvedEarnings || 0)}
                  </Text>
                </View>
                <View className="flex-1">
                  <Text className="text-green-200 text-xs">รอดำเนินการ</Text>
                  <Text className="text-white font-semibold text-lg">
                    {formatCurrency(stats?.pendingEarnings || 0)}
                  </Text>
                </View>
              </View>
            </LinearGradient>
          </Animated.View>

          {/* Action Buttons */}
          <View className="flex-row px-4 mt-4 -mx-1">
            <ActionButton
              icon="add-circle"
              label="เติมเงิน"
              color="#3B82F6"
              onPress={() => Alert.alert('เติมเงิน', 'กำลังพัฒนา...')}
              delay={100}
            />
            <ActionButton
              icon="arrow-up-circle"
              label="ถอนเงิน"
              color="#10B981"
              onPress={() => Alert.alert('ถอนเงิน', 'กำลังพัฒนา...')}
              delay={150}
            />
            <ActionButton
              icon="swap-horizontal"
              label="โอนเงิน"
              color="#8B5CF6"
              onPress={() => Alert.alert('โอนเงิน', 'กำลังพัฒนา...')}
              delay={200}
            />
            <ActionButton
              icon="qr-code"
              label="QR Code"
              color="#EC4899"
              onPress={() => Alert.alert('QR Code', 'กำลังพัฒนา...')}
              delay={250}
            />
          </View>

          {/* Transactions */}
          <View className="px-5 mt-6">
            <View className="flex-row justify-between items-center mb-3">
              <Text className={`text-base font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
                ประวัติธุรกรรม
              </Text>
              <Pressable onPress={() => Alert.alert('ประวัติทั้งหมด', 'กำลังพัฒนา...')}>
                <Text className="text-primary-500 font-medium">ดูทั้งหมด</Text>
              </Pressable>
            </View>

            {transactions.map((tx, index) => (
              <TransactionItem key={index} {...tx} index={index} />
            ))}
          </View>

          <View className="h-6" />
        </ScrollView>
      </SafeAreaView>
    </View>
  );
}
