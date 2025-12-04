/**
 * Dashboard Screen - แดชบอร์ดผู้ใช้
 * แปลงจาก .NET MAUI DashboardPage
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  RefreshControl,
  Share,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '@/stores/authStore';
import { getDashboardStats, getCommissions } from '@/services/api';
import { formatCurrency, getGreetingByTime } from '@/constants';
import type { DashboardStats, Commission } from '@/types';

export default function DashboardScreen() {
  const { user, isAuthenticated, logout } = useAuthStore();

  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [recentCommissions, setRecentCommissions] = useState<Commission[]>([]);
  const [refreshing, setRefreshing] = useState(false);
  const [loading, setLoading] = useState(true);

  // Redirect ถ้ายังไม่ login
  useEffect(() => {
    if (!isAuthenticated) {
      router.replace('/login');
    }
  }, [isAuthenticated]);

  // โหลดข้อมูล
  const loadData = useCallback(async () => {
    try {
      const [statsData, commissionsData] = await Promise.all([
        getDashboardStats(),
        getCommissions(1),
      ]);

      if (statsData) {
        setStats(statsData);
        setRecentCommissions(statsData.recentCommissions || []);
      }
    } catch (error) {
      console.error('Load dashboard error:', error);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadData();
  }, [loadData]);

  // Pull to refresh
  const onRefresh = async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  };

  // Share referral link
  const shareReferralLink = async () => {
    if (!user?.referralCode) {
      Alert.alert('แจ้งเตือน', 'ไม่พบรหัสแนะนำของคุณ');
      return;
    }

    try {
      await Share.share({
        message: `ร่วมเป็นพาร์ทเนอร์กับเรา! ลงทะเบียนผ่านลิงก์นี้: https://your-domain.com/register?ref=${user.referralCode}`,
        title: 'แชร์ลิงก์แนะนำ',
      });
    } catch (error) {
      console.error('Share error:', error);
    }
  };

  // Logout
  const handleLogout = () => {
    Alert.alert('ออกจากระบบ', 'คุณต้องการออกจากระบบใช่หรือไม่?', [
      { text: 'ยกเลิก', style: 'cancel' },
      {
        text: 'ออกจากระบบ',
        style: 'destructive',
        onPress: async () => {
          await logout();
          router.replace('/');
        },
      },
    ]);
  };

  // Go back
  const goBack = () => router.back();

  // Get status color
  const getStatusColor = (status: string) => {
    switch (status) {
      case 'approved':
        return 'bg-green-500';
      case 'pending':
        return 'bg-yellow-500';
      case 'rejected':
        return 'bg-red-500';
      default:
        return 'bg-gray-500';
    }
  };

  // Get status text
  const getStatusText = (status: string) => {
    switch (status) {
      case 'approved':
        return 'อนุมัติแล้ว';
      case 'pending':
        return 'รอดำเนินการ';
      case 'rejected':
        return 'ปฏิเสธ';
      default:
        return status;
    }
  };

  return (
    <LinearGradient colors={['#0F0F23', '#1A1A2E']} className="flex-1">
      <SafeAreaView className="flex-1">
        <ScrollView
          className="flex-1"
          contentContainerClassName="pb-6"
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              tintColor="#3B82F6"
            />
          }
        >
          {/* Header */}
          <View className="px-5 pt-4 pb-2 flex-row justify-between items-center">
            <Pressable
              onPress={goBack}
              className="w-10 h-10 rounded-full bg-white/10 items-center justify-center"
            >
              <Ionicons name="arrow-back" size={24} color="white" />
            </Pressable>

            <Text className="text-white text-lg font-bold">แดชบอร์ด</Text>

            <Pressable
              onPress={handleLogout}
              className="w-10 h-10 rounded-full bg-white/10 items-center justify-center"
            >
              <Ionicons name="log-out" size={20} color="white" />
            </Pressable>
          </View>

          {/* Welcome Card */}
          <View className="px-5 pt-4">
            <LinearGradient
              colors={['#3B82F6', '#1D4ED8']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              className="rounded-2xl p-5"
            >
              <View className="flex-row justify-between items-start">
                <View className="flex-1">
                  <Text className="text-white/80 text-sm">
                    {getGreetingByTime()}
                  </Text>
                  <Text className="text-white text-xl font-bold mt-1">
                    {user?.name}
                  </Text>
                </View>

                {/* Growth Badge */}
                {stats && (
                  <View
                    className={`px-3 py-1.5 rounded-full ${
                      stats.growthPercentage >= 0 ? 'bg-green-500' : 'bg-red-500'
                    }`}
                  >
                    <Text className="text-white text-sm font-bold">
                      {stats.growthPercentage >= 0 ? '+' : ''}
                      {stats.growthPercentage.toFixed(1)}%
                    </Text>
                  </View>
                )}
              </View>

              <Text className="text-white/80 text-sm mt-4">รายได้ทั้งหมด</Text>
              <Text className="text-white text-4xl font-bold mt-1">
                {formatCurrency(stats?.totalEarnings || 0)}
              </Text>
            </LinearGradient>
          </View>

          {/* Stats Cards */}
          <View className="px-5 mt-4 flex-row">
            {/* Pending */}
            <View className="flex-1 mr-2 bg-white/5 rounded-xl p-4 border border-white/10">
              <Text className="text-gray-400 text-sm">รอดำเนินการ</Text>
              <Text className="text-white text-xl font-bold mt-1">
                {formatCurrency(stats?.pendingEarnings || 0)}
              </Text>
            </View>

            {/* Referrals */}
            <View className="flex-1 ml-2 bg-white/5 rounded-xl p-4 border border-white/10">
              <Text className="text-gray-400 text-sm">ผู้ใช้ที่แนะนำ</Text>
              <Text className="text-white text-xl font-bold mt-1">
                {stats?.totalReferrals || 0} คน
              </Text>
            </View>
          </View>

          {/* Quick Actions */}
          <View className="px-5 mt-4 flex-row">
            <Pressable
              onPress={shareReferralLink}
              className="flex-1 mr-2"
            >
              <LinearGradient
                colors={['#8B5CF6', '#6D28D9']}
                className="rounded-xl p-4 flex-row items-center justify-center"
              >
                <Ionicons name="share-social" size={20} color="white" />
                <Text className="text-white font-bold ml-2">แชร์ลิงก์แนะนำ</Text>
              </LinearGradient>
            </Pressable>

            <Pressable
              onPress={() => Alert.alert('คอมมิชชัน', 'หน้าคอมมิชชันกำลังพัฒนา')}
              className="flex-1 ml-2 bg-white/5 rounded-xl p-4 border border-white/10 flex-row items-center justify-center"
            >
              <Ionicons name="cash" size={20} color="white" />
              <Text className="text-white font-bold ml-2">คอมมิชชัน</Text>
            </Pressable>
          </View>

          {/* Recent Commissions */}
          <View className="px-5 mt-6">
            <View className="flex-row justify-between items-center mb-4">
              <Text className="text-white text-lg font-bold">
                คอมมิชชันล่าสุด
              </Text>
              <Pressable>
                <Text className="text-primary-400">ดูทั้งหมด</Text>
              </Pressable>
            </View>

            {recentCommissions.length === 0 ? (
              <View className="bg-white/5 rounded-xl p-8 items-center border border-white/10">
                <Ionicons name="receipt-outline" size={48} color="#6B7280" />
                <Text className="text-gray-400 mt-4">ยังไม่มีคอมมิชชัน</Text>
              </View>
            ) : (
              recentCommissions.map((commission) => (
                <View
                  key={commission.id}
                  className="bg-white/5 rounded-xl p-4 mb-3 border border-white/10"
                >
                  <View className="flex-row justify-between items-start">
                    <View className="flex-1">
                      <Text className="text-white font-bold">
                        {commission.description}
                      </Text>
                      <Text className="text-gray-400 text-sm mt-1">
                        {new Date(commission.createdAt).toLocaleDateString('th-TH', {
                          day: 'numeric',
                          month: 'short',
                          year: 'numeric',
                        })}
                      </Text>
                    </View>
                    <View className="items-end">
                      <Text className="text-primary-400 font-bold text-lg">
                        {formatCurrency(commission.amount)}
                      </Text>
                      <View
                        className={`px-2 py-0.5 rounded-full mt-1 ${getStatusColor(
                          commission.status
                        )}`}
                      >
                        <Text className="text-white text-xs">
                          {getStatusText(commission.status)}
                        </Text>
                      </View>
                    </View>
                  </View>
                </View>
              ))
            )}
          </View>
        </ScrollView>
      </SafeAreaView>
    </LinearGradient>
  );
}
