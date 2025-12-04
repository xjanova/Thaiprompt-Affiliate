/**
 * Profile Screen - หน้าโปรไฟล์และตั้งค่า
 * รองรับ Offline Mode - แสดงข้อมูลจาก local storage
 */

import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  Switch,
  RefreshControl,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import Animated, { FadeInDown } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import * as Sync from '@/services/sync';
import * as Network from '@/services/network';

// Menu Item
const MenuItem = ({
  icon,
  label,
  value,
  onPress,
  showArrow = true,
  danger = false,
  disabled = false,
  delay,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  value?: string;
  onPress: () => void;
  showArrow?: boolean;
  danger?: boolean;
  disabled?: boolean;
  delay: number;
}) => (
  <Animated.View entering={FadeInDown.delay(delay).springify()}>
    <Pressable
      onPress={disabled ? undefined : onPress}
      className={`flex-row items-center py-4 px-4 bg-white dark:bg-dark-50 mb-1 ${
        disabled ? 'opacity-50' : ''
      }`}
      style={({ pressed }) => ({ opacity: disabled ? 0.5 : pressed ? 0.7 : 1 })}
    >
      <View
        className={`w-9 h-9 rounded-xl items-center justify-center mr-3 ${
          danger ? 'bg-red-100' : 'bg-gray-100 dark:bg-gray-700'
        }`}
      >
        <Ionicons
          name={icon}
          size={18}
          color={danger ? '#EF4444' : '#6B7280'}
        />
      </View>
      <Text
        className={`flex-1 font-medium ${
          danger ? 'text-red-500' : 'text-gray-700 dark:text-gray-300'
        }`}
      >
        {label}
      </Text>
      {value && (
        <Text className="text-gray-500 text-sm mr-2">{value}</Text>
      )}
      {showArrow && !disabled && (
        <Ionicons name="chevron-forward" size={18} color="#9CA3AF" />
      )}
    </Pressable>
  </Animated.View>
);

// Toggle Item
const ToggleItem = ({
  icon,
  label,
  value,
  onValueChange,
  delay,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  value: boolean;
  onValueChange: (value: boolean) => void;
  delay: number;
}) => (
  <Animated.View entering={FadeInDown.delay(delay).springify()}>
    <View className="flex-row items-center py-4 px-4 bg-white dark:bg-dark-50 mb-1">
      <View className="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-700 items-center justify-center mr-3">
        <Ionicons name={icon} size={18} color="#6B7280" />
      </View>
      <Text className="flex-1 font-medium text-gray-700 dark:text-gray-300">
        {label}
      </Text>
      <Switch
        value={value}
        onValueChange={onValueChange}
        trackColor={{ false: '#D1D5DB', true: '#93C5FD' }}
        thumbColor={value ? '#3B82F6' : '#F3F4F6'}
      />
    </View>
  </Animated.View>
);

export default function ProfileScreen() {
  const { user, isAuthenticated, isOfflineMode, logout, refreshUser } = useAuthStore();
  const { themeMode, setThemeMode, resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [isOnline, setIsOnline] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const lastSync = Sync.getLastSyncFormatted();

  // ตรวจสอบสถานะเน็ตเมื่อเปิดหน้า
  useEffect(() => {
    const checkNetwork = async () => {
      const online = await Network.checkNetworkStatus();
      setIsOnline(online);
    };
    checkNetwork();

    // Subscribe network changes
    const unsubscribe = Network.subscribeNetworkChanges((online) => {
      setIsOnline(online);
    });

    return () => {
      if (unsubscribe) unsubscribe();
    };
  }, []);

  // Pull to refresh
  const onRefresh = async () => {
    setRefreshing(true);
    await refreshUser();
    const online = await Network.checkNetworkStatus();
    setIsOnline(online);
    setRefreshing(false);
  };

  // Handle logout
  const handleLogout = () => {
    if (!isOnline) {
      Alert.alert('ออฟไลน์', 'ไม่สามารถออกจากระบบขณะออฟไลน์ได้');
      return;
    }

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

  // Handle sync
  const handleSync = async () => {
    if (!isOnline) {
      Alert.alert('ออฟไลน์', 'ไม่สามารถ sync ข้อมูลขณะออฟไลน์ได้');
      return;
    }

    Alert.alert('Sync ข้อมูล', 'กำลัง sync ข้อมูล...');
    const success = await Sync.syncAll();
    await refreshUser();
    Alert.alert(
      success ? 'สำเร็จ' : 'ผิดพลาด',
      success ? 'Sync ข้อมูลเรียบร้อย' : 'ไม่สามารถ sync ได้'
    );
  };

  // ถ้ายังไม่ login
  if (!isAuthenticated && !user) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <Ionicons
            name="person-circle"
            size={100}
            color={isDark ? '#4B5563' : '#9CA3AF'}
          />
          <Text className={`text-xl font-bold mt-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
            โปรไฟล์ของคุณ
          </Text>
          <Text className="text-gray-500 text-center mt-2">
            เข้าสู่ระบบเพื่อจัดการโปรไฟล์และตั้งค่า
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
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-100'}`}>
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
          {/* Offline Banner */}
          {(!isOnline || isOfflineMode) && (
            <View className="bg-yellow-100 dark:bg-yellow-900/30 px-4 py-2">
              <View className="flex-row items-center justify-center">
                <Ionicons name="cloud-offline" size={16} color="#D97706" />
                <Text className="text-yellow-700 dark:text-yellow-400 text-sm ml-2">
                  โหมดออฟไลน์ - แสดงข้อมูลที่บันทึกไว้
                </Text>
              </View>
            </View>
          )}

          {/* Profile Header */}
          <LinearGradient
            colors={isDark ? ['#1E3A8A', '#1E40AF'] : ['#3B82F6', '#2563EB']}
            className="px-6 pt-6 pb-8"
          >
            <Text className="text-white text-2xl font-bold mb-6">โปรไฟล์</Text>

            <View className="flex-row items-center">
              {/* Avatar */}
              <View className="w-20 h-20 rounded-full bg-white items-center justify-center mr-4">
                <Text className="text-primary-600 font-bold text-3xl">
                  {user?.name?.charAt(0).toUpperCase() || 'U'}
                </Text>
              </View>

              {/* Info */}
              <View className="flex-1">
                <Text className="text-white text-xl font-bold">{user?.name}</Text>
                <Text className="text-blue-100">{user?.email}</Text>
                <View className="flex-row items-center mt-1">
                  <View className="bg-white/20 px-2 py-0.5 rounded-full mr-2">
                    <Text className="text-white text-xs">
                      {user?.role || 'Member'}
                    </Text>
                  </View>
                  {user?.referralCode && (
                    <View className="bg-green-500/30 px-2 py-0.5 rounded-full">
                      <Text className="text-white text-xs">
                        รหัส: {user.referralCode}
                      </Text>
                    </View>
                  )}
                </View>
              </View>
            </View>
          </LinearGradient>

          {/* Menu Sections */}
          <View className="mt-4">
            {/* Account */}
            <Text className="text-gray-500 text-xs font-medium uppercase px-4 mb-2">
              บัญชี
            </Text>
            <MenuItem
              icon="person"
              label="แก้ไขโปรไฟล์"
              onPress={() => Alert.alert('แก้ไขโปรไฟล์', 'กำลังพัฒนา...')}
              disabled={!isOnline}
              delay={100}
            />
            <MenuItem
              icon="key"
              label="เปลี่ยนรหัสผ่าน"
              onPress={() => Alert.alert('เปลี่ยนรหัสผ่าน', 'กำลังพัฒนา...')}
              disabled={!isOnline}
              delay={150}
            />
            <MenuItem
              icon="shield-checkmark"
              label="ความปลอดภัย"
              onPress={() => Alert.alert('ความปลอดภัย', 'กำลังพัฒนา...')}
              disabled={!isOnline}
              delay={200}
            />
          </View>

          <View className="mt-4">
            {/* Referral - always available offline */}
            <Text className="text-gray-500 text-xs font-medium uppercase px-4 mb-2">
              การแนะนำ
            </Text>
            <MenuItem
              icon="share-social"
              label="แนะนำเพื่อน"
              value={user?.referralCode || ''}
              onPress={() => router.push('/referral')}
              delay={220}
            />
            <MenuItem
              icon="qr-code"
              label="QR Code ของฉัน"
              onPress={() => router.push('/referral')}
              delay={240}
            />
          </View>

          <View className="mt-4">
            {/* Settings */}
            <Text className="text-gray-500 text-xs font-medium uppercase px-4 mb-2">
              ตั้งค่า
            </Text>
            <ToggleItem
              icon="moon"
              label="โหมดมืด"
              value={themeMode === 'dark'}
              onValueChange={(value) => setThemeMode(value ? 'dark' : 'light')}
              delay={250}
            />
            <MenuItem
              icon="notifications"
              label="การแจ้งเตือน"
              onPress={() => Alert.alert('การแจ้งเตือน', 'กำลังพัฒนา...')}
              delay={300}
            />
            <MenuItem
              icon="language"
              label="ภาษา"
              value="ไทย"
              onPress={() => Alert.alert('ภาษา', 'กำลังพัฒนา...')}
              delay={350}
            />
          </View>

          <View className="mt-4">
            {/* Data */}
            <Text className="text-gray-500 text-xs font-medium uppercase px-4 mb-2">
              ข้อมูล
            </Text>
            <MenuItem
              icon="sync"
              label="Sync ข้อมูล"
              value={lastSync}
              onPress={handleSync}
              disabled={!isOnline}
              delay={400}
            />
            <MenuItem
              icon="cloud-download"
              label="ดาวน์โหลดข้อมูลออฟไลน์"
              onPress={async () => {
                if (!isOnline) {
                  Alert.alert('ออฟไลน์', 'ต้องมีเน็ตเพื่อดาวน์โหลด');
                  return;
                }
                Alert.alert('กำลังดาวน์โหลด', 'ดาวน์โหลดข้อมูลสำหรับใช้งานออฟไลน์...');
                await Sync.syncAll();
                Alert.alert('สำเร็จ', 'ดาวน์โหลดข้อมูลออฟไลน์เรียบร้อย');
              }}
              disabled={!isOnline}
              delay={430}
            />
            <MenuItem
              icon="trash"
              label="ล้าง Cache"
              onPress={() => {
                Alert.alert('ล้าง Cache', 'คุณต้องการล้าง cache ทั้งหมด?', [
                  { text: 'ยกเลิก', style: 'cancel' },
                  {
                    text: 'ล้าง',
                    onPress: async () => {
                      const Cache = await import('@/services/cache');
                      await Cache.clearAllCache();
                      Alert.alert('สำเร็จ', 'ล้าง cache เรียบร้อย');
                    },
                  },
                ]);
              }}
              delay={450}
            />
          </View>

          <View className="mt-4">
            {/* Support */}
            <Text className="text-gray-500 text-xs font-medium uppercase px-4 mb-2">
              ช่วยเหลือ
            </Text>
            <MenuItem
              icon="help-circle"
              label="ศูนย์ช่วยเหลือ"
              onPress={() => Alert.alert('ศูนย์ช่วยเหลือ', 'กำลังพัฒนา...')}
              delay={500}
            />
            <MenuItem
              icon="chatbubble"
              label="ติดต่อเรา"
              onPress={() => Alert.alert('ติดต่อเรา', 'กำลังพัฒนา...')}
              delay={550}
            />
            <MenuItem
              icon="document-text"
              label="เงื่อนไขการใช้งาน"
              onPress={() => Alert.alert('เงื่อนไขการใช้งาน', 'กำลังพัฒนา...')}
              delay={600}
            />
            <MenuItem
              icon="information-circle"
              label="เกี่ยวกับแอพ"
              value="v1.0.0"
              onPress={() => Alert.alert('Thaiprompt Affiliate', 'Version 1.0.0\n\n© 2024 Thaiprompt')}
              delay={650}
            />
          </View>

          <View className="mt-4 mb-8">
            <MenuItem
              icon="log-out"
              label="ออกจากระบบ"
              onPress={handleLogout}
              showArrow={false}
              danger
              disabled={!isOnline}
              delay={700}
            />
          </View>
        </ScrollView>
      </SafeAreaView>
    </View>
  );
}
