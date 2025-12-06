/**
 * Network/MLM Screen - หน้าสายงาน/เครือข่าย
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
import Animated, { FadeInDown } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { getReferrals } from '@/services/api';
import * as Cache from '@/services/cache';
import * as Network from '@/services/network';
import { formatCurrency } from '@/constants';
import type { ReferralsData, Referral } from '@/types';

// Stat Card
const StatCard = ({
  icon,
  label,
  value,
  color,
  delay,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  value: string;
  color: string;
  delay: number;
}) => (
  <Animated.View
    entering={FadeInDown.delay(delay).springify()}
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

// Member Card
const MemberCard = ({
  member,
  index,
}: {
  member: Referral;
  index: number;
}) => {
  const statusColor =
    member.status === 'active'
      ? 'bg-green-100 text-green-600'
      : 'bg-gray-100 text-gray-500';
  const statusText = member.status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน';

  return (
    <Animated.View
      entering={FadeInDown.delay(200 + index * 50).springify()}
      className="bg-white dark:bg-dark-50 rounded-2xl p-4 mb-3 border border-gray-100 dark:border-gray-700"
    >
      <View className="flex-row items-center">
        {/* Avatar */}
        <View className="w-12 h-12 rounded-full bg-primary-100 items-center justify-center mr-3">
          <Text className="text-primary-600 font-bold text-lg">
            {member.user.name.charAt(0).toUpperCase()}
          </Text>
        </View>

        {/* Info */}
        <View className="flex-1">
          <Text className="text-gray-900 dark:text-white font-semibold">
            {member.user.name}
          </Text>
          <Text className="text-gray-500 dark:text-gray-400 text-xs">
            ระดับ {member.level} • {member.user.email}
          </Text>
        </View>

        {/* Status & Earnings */}
        <View className="items-end">
          <View className={`px-2 py-0.5 rounded-full ${statusColor}`}>
            <Text className="text-xs font-medium">{statusText}</Text>
          </View>
          <Text className="text-primary-500 font-semibold text-sm mt-1">
            {formatCurrency(member.earnings)}
          </Text>
        </View>
      </View>
    </Animated.View>
  );
};

// Inline Loading Indicator
const LoadingIndicator = ({ isDark }: { isDark: boolean }) => (
  <View className="flex-1 justify-center items-center py-20">
    <View className="w-12 h-12 rounded-full border-4 border-primary-200 border-t-primary-500"
      style={{ borderTopColor: '#8B5CF6', borderColor: isDark ? '#374151' : '#E5E7EB' }}
    />
    <Text className={`mt-4 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
      กำลังโหลดข้อมูลทีม...
    </Text>
  </View>
);

export default function NetworkScreen() {
  const { isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [data, setData] = useState<ReferralsData | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [isOnline, setIsOnline] = useState(true);
  const [isLoading, setIsLoading] = useState(true);

  // โหลดข้อมูล
  const loadData = useCallback(async () => {
    if (!isAuthenticated) {
      setIsLoading(false);
      return;
    }

    try {
      const online = await Network.checkNetworkStatus();
      setIsOnline(online);

      // ดึงจาก cache ก่อน
      const cached = await Cache.getCache<ReferralsData>(
        Cache.CACHE_KEYS.REFERRALS
      );
      if (cached) setData(cached);

      // ถ้า online ให้ดึงข้อมูลใหม่
      if (online) {
        const freshData = await getReferrals();
        if (freshData) {
          setData(freshData);
          await Cache.setCache(
            Cache.CACHE_KEYS.REFERRALS,
            freshData,
            Cache.OFFLINE_CACHE_DURATION
          );
        }
      }
    } catch (error) {
      console.error('Load network data error:', error);
    } finally {
      setIsLoading(false);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  // Pull to refresh
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
            name="people"
            size={80}
            color={isDark ? '#4B5563' : '#9CA3AF'}
          />
          <Text
            className={`text-xl font-bold mt-4 ${
              isDark ? 'text-white' : 'text-gray-800'
            }`}
          >
            ดูสายงานของคุณ
          </Text>
          <Text className="text-gray-500 text-center mt-2">
            เข้าสู่ระบบเพื่อดูข้อมูลสายงานและทีมของคุณ
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

  // แสดง Loading ขณะโหลดข้อมูล
  if (isLoading && !data) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1">
          <LoadingIndicator isDark={isDark} />
        </SafeAreaView>
      </View>
    );
  }

  return (
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
      <SafeAreaView className="flex-1" edges={['top']}>
        {/* Header */}
        <View className="px-5 pt-4 pb-2">
          <Text
            className={`text-2xl font-bold ${
              isDark ? 'text-white' : 'text-gray-900'
            }`}
          >
            สายงานของฉัน
          </Text>
          <Text className="text-gray-500 dark:text-gray-400">
            ดูข้อมูลทีมและเครือข่ายของคุณ
          </Text>
        </View>

        {/* Offline Banner */}
        {!isOnline && (
          <View className="mx-5 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl px-4 py-2 mb-3">
            <Text className="text-yellow-700 dark:text-yellow-400 text-sm">
              📡 ออฟไลน์ - แสดงข้อมูลที่บันทึกไว้
            </Text>
          </View>
        )}

        <ScrollView
          className="flex-1"
          contentContainerClassName="px-4 pb-6"
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              tintColor="#3B82F6"
            />
          }
        >
          {/* Stats */}
          <View className="flex-row mt-2 mb-4 -mx-1">
            <StatCard
              icon="people"
              label="สมาชิกทั้งหมด"
              value={`${data?.totalReferrals || 0} คน`}
              color="#8B5CF6"
              delay={0}
            />
            <StatCard
              icon="cash"
              label="รายได้จากทีม"
              value={formatCurrency(data?.totalEarnings || 0)}
              color="#10B981"
              delay={50}
            />
          </View>

          {/* Tree View Button */}
          <Animated.View entering={FadeInDown.delay(100).springify()}>
            <Pressable
              onPress={() => Alert.alert('แผนผังสายงาน', 'กำลังพัฒนา...')}
              className="mb-4"
            >
              <LinearGradient
                colors={['#8B5CF6', '#6D28D9']}
                className="rounded-2xl p-4 flex-row items-center"
              >
                <View className="w-12 h-12 rounded-xl bg-white/20 items-center justify-center mr-3">
                  <Ionicons name="git-network" size={24} color="white" />
                </View>
                <View className="flex-1">
                  <Text className="text-white font-bold text-base">
                    ดูแผนผังสายงาน
                  </Text>
                  <Text className="text-purple-200 text-sm">
                    ดู Tree View แบบเต็ม
                  </Text>
                </View>
                <Ionicons name="chevron-forward" size={24} color="white" />
              </LinearGradient>
            </Pressable>
          </Animated.View>

          {/* Referral Link */}
          <Animated.View entering={FadeInDown.delay(150).springify()}>
            <Pressable
              onPress={() => router.push('/referral')}
              className="mb-4"
            >
              <LinearGradient
                colors={['#EC4899', '#BE185D']}
                className="rounded-2xl p-4 flex-row items-center"
              >
                <View className="w-12 h-12 rounded-xl bg-white/20 items-center justify-center mr-3">
                  <Ionicons name="share-social" size={24} color="white" />
                </View>
                <View className="flex-1">
                  <Text className="text-white font-bold text-base">
                    แนะนำเพื่อน
                  </Text>
                  <Text className="text-pink-200 text-sm">
                    แชร์ลิงก์เพื่อเพิ่มสมาชิก
                  </Text>
                </View>
                <Ionicons name="chevron-forward" size={24} color="white" />
              </LinearGradient>
            </Pressable>
          </Animated.View>

          {/* Member List */}
          <Text
            className={`text-base font-bold mt-2 mb-3 ${
              isDark ? 'text-white' : 'text-gray-800'
            }`}
          >
            สมาชิกในทีม
          </Text>

          {data?.referrals && data.referrals.length > 0 ? (
            data.referrals.map((member, index) => (
              <MemberCard key={member.id} member={member} index={index} />
            ))
          ) : (
            <View className="bg-white dark:bg-dark-50 rounded-2xl p-8 items-center border border-gray-100 dark:border-gray-700">
              <Ionicons
                name="people-outline"
                size={48}
                color={isDark ? '#4B5563' : '#9CA3AF'}
              />
              <Text className="text-gray-500 dark:text-gray-400 mt-3 text-center">
                ยังไม่มีสมาชิกในทีม
              </Text>
              <Pressable
                onPress={() => router.push('/referral')}
                className="bg-primary-500 px-6 py-2.5 rounded-xl mt-4"
              >
                <Text className="text-white font-semibold">แนะนำเพื่อน</Text>
              </Pressable>
            </View>
          )}
        </ScrollView>
      </SafeAreaView>
    </View>
  );
}
