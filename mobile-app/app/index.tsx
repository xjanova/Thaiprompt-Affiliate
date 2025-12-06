/**
 * Index Screen - Hub Selection (หน้าหลัก)
 * แปลงจาก .NET MAUI HubSelectionPage
 */

import React, { useEffect, useState } from 'react';
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
import { useAuthStore } from '@/stores/authStore';
import { HubCard, Button } from '@/components';
import { HUB_ITEMS, getGreetingByTime, formatCurrency } from '@/constants';
import { getDashboardStats } from '@/services/api';
import type { HubItem } from '@/types';

export default function IndexScreen() {
  const { user, isAuthenticated, logout } = useAuthStore();
  const [balance, setBalance] = useState(0);
  const [refreshing, setRefreshing] = useState(false);
  const [greeting, setGreeting] = useState(getGreetingByTime());

  // โหลดข้อมูล balance
  const loadBalance = async () => {
    if (!isAuthenticated) return;
    try {
      const stats = await getDashboardStats();
      if (stats) {
        setBalance(stats.totalEarnings);
      }
    } catch (error) {
      console.error('Load balance error:', error);
    }
  };

  useEffect(() => {
    loadBalance();
    // อัพเดท greeting ทุก 1 นาที
    const interval = setInterval(() => {
      setGreeting(getGreetingByTime());
    }, 60000);
    return () => clearInterval(interval);
  }, [isAuthenticated]);

  // Pull to refresh
  const onRefresh = async () => {
    setRefreshing(true);
    await loadBalance();
    setRefreshing(false);
  };

  // เมื่อเลือก Hub
  const onHubPress = (hub: typeof HUB_ITEMS[number]) => {
    // ตรวจสอบว่าต้อง login ก่อนไหม
    if (hub.requiresLogin && !isAuthenticated) {
      Alert.alert(
        'ต้องเข้าสู่ระบบ',
        `กรุณาเข้าสู่ระบบเพื่อใช้บริการ ${hub.title}`,
        [
          { text: 'ยกเลิก', style: 'cancel' },
          { text: 'เข้าสู่ระบบ', onPress: () => router.push('/login') },
        ]
      );
      return;
    }

    // TODO: Navigate to hub page
    Alert.alert(hub.title, `กำลังพัฒนา: ${hub.subtitle}`);
  };

  // ไปหน้า login
  const goToLogin = () => router.push('/login');

  // ไปหน้า dashboard
  const goToDashboard = () => router.push('/dashboard');

  // Logout
  const handleLogout = () => {
    Alert.alert('ออกจากระบบ', 'คุณต้องการออกจากระบบใช่หรือไม่?', [
      { text: 'ยกเลิก', style: 'cancel' },
      {
        text: 'ออกจากระบบ',
        style: 'destructive',
        onPress: async () => {
          await logout();
        },
      },
    ]);
  };

  return (
    <LinearGradient colors={['#0F0F23', '#1A1A2E']} style={{ flex: 1 }}>
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
          <View className="px-5 pt-4 pb-6">
            {/* Top Row - Greeting & Profile */}
            <View className="flex-row justify-between items-center mb-4">
              <View>
                <Text className="text-gray-400 text-sm">{greeting}</Text>
                <Text className="text-white text-xl font-bold">
                  {isAuthenticated ? user?.name : 'ยินดีต้อนรับ'}
                </Text>
              </View>

              {/* Profile / Login Button */}
              {isAuthenticated ? (
                <Pressable
                  onPress={goToDashboard}
                  className="w-12 h-12 rounded-full bg-primary-500 items-center justify-center"
                >
                  <Text className="text-white text-lg font-bold">
                    {user?.name?.charAt(0).toUpperCase() || 'U'}
                  </Text>
                </Pressable>
              ) : (
                <Button
                  title="เข้าสู่ระบบ"
                  onPress={goToLogin}
                  size="sm"
                />
              )}
            </View>

            {/* Balance Card (ถ้า login แล้ว) */}
            {isAuthenticated && (
              <LinearGradient
                colors={['#3B82F6', '#1D4ED8']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={{ borderRadius: 16, padding: 20, marginBottom: 8 }}
              >
                <Text className="text-white/80 text-sm mb-1">ยอดเงินคงเหลือ</Text>
                <Text className="text-white text-3xl font-bold mb-3">
                  {formatCurrency(balance)}
                </Text>
                <View className="flex-row">
                  <Pressable
                    onPress={goToDashboard}
                    className="flex-row items-center bg-white/20 rounded-full px-4 py-2 mr-2"
                  >
                    <Ionicons name="wallet" size={16} color="white" />
                    <Text className="text-white text-sm ml-2">ดูแดชบอร์ด</Text>
                  </Pressable>
                  <Pressable
                    onPress={handleLogout}
                    className="flex-row items-center bg-white/10 rounded-full px-4 py-2"
                  >
                    <Ionicons name="log-out" size={16} color="white" />
                    <Text className="text-white text-sm ml-2">ออก</Text>
                  </Pressable>
                </View>
              </LinearGradient>
            )}
          </View>

          {/* Section Title */}
          <View className="px-5 mb-4">
            <Text className="text-white text-lg font-bold">บริการทั้งหมด</Text>
            <Text className="text-gray-400 text-sm">เลือกบริการที่คุณต้องการ</Text>
          </View>

          {/* Hub Grid (2 columns) */}
          <View className="px-3">
            <View className="flex-row flex-wrap">
              {HUB_ITEMS.map((hub) => (
                <View key={hub.id} className="w-1/2">
                  <HubCard item={hub as HubItem} onPress={() => onHubPress(hub)} />
                </View>
              ))}
            </View>
          </View>

          {/* Footer */}
          <View className="px-5 mt-6 items-center">
            <Text className="text-gray-500 text-xs">
              Thaiprompt Affiliate v1.0.0
            </Text>
          </View>
        </ScrollView>
      </SafeAreaView>
    </LinearGradient>
  );
}
