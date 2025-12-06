/**
 * Home Screen - หน้าหลัก พร้อม Hub Selection ที่สวยงาม
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  RefreshControl,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import Animated, {
  FadeInDown,
  FadeInRight,
} from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { HUB_ITEMS, getGreetingByTime, formatCurrency } from '@/constants';
import { APP_INFO } from '@/config/appConfig';
import { getDashboardStats } from '@/services/api';
import * as Cache from '@/services/cache';
import * as Network from '@/services/network';
import type { DashboardStats } from '@/types';

// Quick Action Button Component
const QuickActionButton = ({
  icon,
  label,
  color,
  onPress,
  delay = 0,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  color: string;
  onPress: () => void;
  delay?: number;
}) => (
  <Animated.View entering={FadeInDown.delay(delay).springify()}>
    <Pressable
      onPress={onPress}
      className="items-center mr-4"
      style={({ pressed }) => ({ opacity: pressed ? 0.7 : 1 })}
    >
      <View
        className="w-14 h-14 rounded-2xl items-center justify-center mb-1.5"
        style={{ backgroundColor: color }}
      >
        <Ionicons name={icon} size={26} color="white" />
      </View>
      <Text className="text-gray-600 dark:text-gray-300 text-xs font-medium">
        {label}
      </Text>
    </Pressable>
  </Animated.View>
);

// Hub Card Component (ใหม่ สวยขึ้น)
const HubCard = ({
  item,
  onPress,
  index,
}: {
  item: (typeof HUB_ITEMS)[number];
  onPress: () => void;
  index: number;
}) => (
  <Animated.View
    entering={FadeInDown.delay(100 + index * 50).springify()}
    className="w-1/2 p-1.5"
  >
    <Pressable
      onPress={onPress}
      style={({ pressed }) => ({
        opacity: pressed ? 0.8 : 1,
        transform: [{ scale: pressed ? 0.97 : 1 }],
      })}
    >
      <LinearGradient
        colors={[item.gradientStart, item.gradientEnd]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        className="rounded-2xl p-4 min-h-[130px] relative overflow-hidden"
      >
        {/* Badge */}
        {item.hasBadge && (
          <View className="absolute top-2 right-2 bg-white/25 px-2 py-0.5 rounded-full">
            <Text className="text-white text-[10px] font-bold">
              {item.badgeText}
            </Text>
          </View>
        )}

        {/* Icon */}
        <Text className="text-3xl mb-2">{item.icon}</Text>

        {/* Title */}
        <Text className="text-white font-bold text-sm mb-0.5">
          {item.title}
        </Text>

        {/* Subtitle */}
        <Text className="text-white/70 text-[11px]" numberOfLines={2}>
          {item.subtitle}
        </Text>

        {/* Decorative */}
        <View className="absolute -bottom-6 -right-6 w-20 h-20 rounded-full bg-white/10" />
      </LinearGradient>
    </Pressable>
  </Animated.View>
);

export default function HomeScreen() {
  const { user, isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [isOnline, setIsOnline] = useState(true);
  const [greeting] = useState(getGreetingByTime());

  // โหลดข้อมูล
  const loadData = useCallback(async () => {
    try {
      // ตรวจสอบ network
      const online = await Network.checkNetworkStatus();
      setIsOnline(online);

      if (isAuthenticated) {
        // ดึงจาก cache ก่อน
        const cached = await Cache.getCache<DashboardStats>(
          Cache.CACHE_KEYS.DASHBOARD_STATS
        );
        if (cached) setStats(cached);

        // ถ้า online ให้ดึงข้อมูลใหม่
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
      }
    } catch (error) {
      console.error('Load data error:', error);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    loadData();

    // Listen to network changes
    const unsubscribe = Network.addNetworkListener((connected) => {
      setIsOnline(connected);
      if (connected) loadData();
    });

    return () => unsubscribe();
  }, [loadData]);

  // Pull to refresh
  const onRefresh = async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  };

  // Hub press handler
  const handleHubPress = (hub: (typeof HUB_ITEMS)[number]) => {
    if (hub.requiresLogin && !isAuthenticated) {
      Alert.alert('ต้องเข้าสู่ระบบ', `กรุณาเข้าสู่ระบบเพื่อใช้บริการ ${hub.title}`, [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: 'เข้าสู่ระบบ', onPress: () => router.push('/login') },
      ]);
      return;
    }

    // Navigate based on hub id
    switch (hub.id) {
      case 'shopping':
        router.push('/shopping');
        break;
      case 'wallet':
        router.push('/(tabs)/wallet');
        break;
      case 'mlm':
        router.push('/(tabs)/network');
        break;
      case 'invest':
      case 'rider':
      case 'aibot':
      case 'academy':
      case 'gaming':
        Alert.alert(hub.title, `${hub.subtitle}\n\nกำลังพัฒนา...`);
        break;
      default:
        Alert.alert(hub.title, 'กำลังพัฒนา...');
    }
  };

  return (
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
      <SafeAreaView className="flex-1" edges={['top']}>
        <ScrollView
          className="flex-1"
          contentContainerClassName="pb-4"
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
            <View className="bg-yellow-500 px-4 py-2">
              <Text className="text-white text-center text-sm font-medium">
                📡 ออฟไลน์ - แสดงข้อมูลที่บันทึกไว้
              </Text>
            </View>
          )}

          {/* Header */}
          <LinearGradient
            colors={isDark ? ['#1E3A8A', '#1E40AF'] : ['#3B82F6', '#2563EB']}
            className="px-5 pt-4 pb-8 rounded-b-3xl"
          >
            {/* Top Row */}
            <Animated.View
              entering={FadeInRight.springify()}
              className="flex-row justify-between items-center mb-5"
            >
              <View>
                <Text className="text-blue-100 text-sm">{greeting}</Text>
                <Text className="text-white text-xl font-bold">
                  {isAuthenticated ? user?.name : 'ยินดีต้อนรับ'}
                </Text>
              </View>

              <View className="flex-row items-center">
                {/* Notification */}
                <Pressable
                  onPress={() => Alert.alert('การแจ้งเตือน', 'กำลังพัฒนา...')}
                  className="w-10 h-10 rounded-full bg-white/20 items-center justify-center mr-2"
                >
                  <Ionicons name="notifications" size={20} color="white" />
                </Pressable>

                {/* Profile */}
                {isAuthenticated ? (
                  <Pressable
                    onPress={() => router.push('/(tabs)/profile')}
                    className="w-10 h-10 rounded-full bg-white items-center justify-center"
                  >
                    <Text className="text-primary-600 font-bold text-lg">
                      {user?.name?.charAt(0).toUpperCase() || 'U'}
                    </Text>
                  </Pressable>
                ) : (
                  <Pressable
                    onPress={() => router.push('/login')}
                    className="bg-white px-4 py-2 rounded-full"
                  >
                    <Text className="text-primary-600 font-bold text-sm">
                      เข้าสู่ระบบ
                    </Text>
                  </Pressable>
                )}
              </View>
            </Animated.View>

            {/* Balance Card (if authenticated) */}
            {isAuthenticated && (
              <Animated.View
                entering={FadeInDown.delay(100).springify()}
                className="bg-white/15 rounded-2xl p-4"
              >
                <Text className="text-blue-100 text-sm">ยอดเงินคงเหลือ</Text>
                <Text className="text-white text-3xl font-bold mt-1">
                  {formatCurrency(stats?.totalEarnings || 0)}
                </Text>
                <View className="flex-row mt-3">
                  <View className="flex-1 mr-2">
                    <Text className="text-blue-200 text-xs">รอดำเนินการ</Text>
                    <Text className="text-white font-semibold">
                      {formatCurrency(stats?.pendingEarnings || 0)}
                    </Text>
                  </View>
                  <View className="flex-1">
                    <Text className="text-blue-200 text-xs">ผู้ใช้ที่แนะนำ</Text>
                    <Text className="text-white font-semibold">
                      {stats?.totalReferrals || 0} คน
                    </Text>
                  </View>
                </View>
              </Animated.View>
            )}
          </LinearGradient>

          {/* Quick Actions */}
          <View className="px-5 mt-5">
            <Text
              className={`text-base font-bold mb-3 ${
                isDark ? 'text-white' : 'text-gray-800'
              }`}
            >
              ทางลัด
            </Text>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              className="-mx-1"
            >
              <View className="flex-row px-1">
                <QuickActionButton
                  icon="cart"
                  label="ช้อปปิ้ง"
                  color="#F59E0B"
                  onPress={() => router.push('/shopping')}
                  delay={0}
                />
                <QuickActionButton
                  icon="people"
                  label="สายงาน"
                  color="#8B5CF6"
                  onPress={() => router.push('/(tabs)/network')}
                  delay={50}
                />
                <QuickActionButton
                  icon="wallet"
                  label="กระเป๋า"
                  color="#10B981"
                  onPress={() => router.push('/(tabs)/wallet')}
                  delay={100}
                />
                <QuickActionButton
                  icon="share-social"
                  label="แนะนำเพื่อน"
                  color="#EC4899"
                  onPress={() => router.push('/referral')}
                  delay={150}
                />
                <QuickActionButton
                  icon="bar-chart"
                  label="รายงาน"
                  color="#3B82F6"
                  onPress={() => router.push('/commissions')}
                  delay={200}
                />
              </View>
            </ScrollView>
          </View>

          {/* Hub Grid */}
          <View className="px-3.5 mt-5">
            <Text
              className={`text-base font-bold mb-3 px-1.5 ${
                isDark ? 'text-white' : 'text-gray-800'
              }`}
            >
              บริการทั้งหมด
            </Text>
            <View className="flex-row flex-wrap">
              {HUB_ITEMS.map((hub, index) => (
                <HubCard
                  key={hub.id}
                  item={hub}
                  index={index}
                  onPress={() => handleHubPress(hub)}
                />
              ))}
            </View>
          </View>

          {/* Footer */}
          <View className="items-center mt-6 pb-4">
            <Text className={`text-xs ${isDark ? 'text-gray-500' : 'text-gray-400'}`}>
              {APP_INFO.NAME} v{APP_INFO.VERSION}
            </Text>
          </View>
        </ScrollView>
      </SafeAreaView>
    </View>
  );
}
