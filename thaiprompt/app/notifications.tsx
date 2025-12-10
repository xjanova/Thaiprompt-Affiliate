/**
 * Notifications Screen - หน้าการแจ้งเตือน
 * แสดงรายการการแจ้งเตือนทั้งหมดของผู้ใช้
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  Pressable,
  ActivityIndicator,
  FlatList,
  RefreshControl,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import Animated, { FadeInDown, FadeInRight } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import {
  getNotifications,
  markNotificationRead,
  markAllNotificationsRead,
  deleteNotification,
  Notification,
} from '@/services/api';

// =====================================================
// Type Icons
// =====================================================

const TYPE_ICONS: Record<string, { emoji: string; color: string }> = {
  general: { emoji: '🔔', color: '#3B82F6' },
  order: { emoji: '🧾', color: '#10B981' },
  rider: { emoji: '🚴', color: '#06B6D4' },
  wallet: { emoji: '💰', color: '#8B5CF6' },
  promotion: { emoji: '🎁', color: '#F59E0B' },
  system: { emoji: '⚙️', color: '#6B7280' },
  ticket: { emoji: '💬', color: '#EC4899' },
};

// =====================================================
// Notification Item
// =====================================================

interface NotificationItemProps {
  item: Notification;
  isDark: boolean;
  onPress: () => void;
  onDelete: () => void;
}

const NotificationItem = ({ item, isDark, onPress, onDelete }: NotificationItemProps) => {
  const typeInfo = TYPE_ICONS[item.type] || TYPE_ICONS.general;

  return (
    <Pressable
      onPress={onPress}
      onLongPress={() => {
        Alert.alert(
          'ลบการแจ้งเตือน',
          'คุณต้องการลบการแจ้งเตือนนี้หรือไม่?',
          [
            { text: 'ยกเลิก', style: 'cancel' },
            { text: 'ลบ', style: 'destructive', onPress: onDelete },
          ]
        );
      }}
      className={`mb-3 p-4 rounded-2xl ${
        isDark ? 'bg-gray-800' : 'bg-white'
      } ${!item.isRead ? 'border-l-4 border-primary-500' : ''}`}
    >
      <View className="flex-row">
        {/* Icon */}
        <View
          className="w-12 h-12 rounded-full items-center justify-center mr-3"
          style={{ backgroundColor: typeInfo.color + '20' }}
        >
          <Text style={{ fontSize: 24 }}>{typeInfo.emoji}</Text>
        </View>

        {/* Content */}
        <View className="flex-1">
          <View className="flex-row items-start justify-between">
            <Text
              className={`flex-1 font-bold ${isDark ? 'text-white' : 'text-gray-800'} ${
                !item.isRead ? '' : 'opacity-70'
              }`}
              numberOfLines={1}
            >
              {item.title}
            </Text>
            {!item.isRead && (
              <View className="w-2 h-2 rounded-full bg-primary-500 ml-2 mt-2" />
            )}
          </View>

          <Text
            className={`mt-1 ${isDark ? 'text-gray-400' : 'text-gray-600'} ${
              !item.isRead ? '' : 'opacity-70'
            }`}
            numberOfLines={2}
          >
            {item.body}
          </Text>

          <Text className="text-gray-400 text-xs mt-2">
            {item.timeAgo}
          </Text>
        </View>
      </View>
    </Pressable>
  );
};

// =====================================================
// Main Notifications Screen
// =====================================================

export default function NotificationsScreen() {
  const { isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);

  const loadNotifications = useCallback(async () => {
    try {
      const response = await getNotifications();
      if (response?.success && response.data) {
        setNotifications(response.data.notifications);
        setUnreadCount(response.data.unreadCount);
      }
    } catch (error) {
      console.error('Load notifications error:', error);
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  useEffect(() => {
    if (isAuthenticated) {
      loadNotifications();
    }
  }, [isAuthenticated, loadNotifications]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    loadNotifications();
  };

  const handleMarkAsRead = async (notification: Notification) => {
    if (!notification.isRead) {
      await markNotificationRead(notification.id);
      setNotifications((prev) =>
        prev.map((n) => (n.id === notification.id ? { ...n, isRead: true } : n))
      );
      setUnreadCount((prev) => Math.max(0, prev - 1));
    }

    // Handle action URL if exists
    if (notification.actionUrl) {
      // Navigate based on action URL
      // For example: router.push(notification.actionUrl as any);
    }
  };

  const handleMarkAllRead = async () => {
    if (unreadCount === 0) return;

    const response = await markAllNotificationsRead();
    if (response.success) {
      setNotifications((prev) => prev.map((n) => ({ ...n, isRead: true })));
      setUnreadCount(0);
    }
  };

  const handleDelete = async (id: number) => {
    const response = await deleteNotification(id);
    if (response.success) {
      setNotifications((prev) => prev.filter((n) => n.id !== id));
    } else {
      Alert.alert('ข้อผิดพลาด', response.message || 'ไม่สามารถลบการแจ้งเตือนได้');
    }
  };

  // Not authenticated
  if (!isAuthenticated) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <Text style={{ fontSize: 80 }}>🔔</Text>
          <Text className={`text-xl font-bold mt-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
            การแจ้งเตือน
          </Text>
          <Text className="text-gray-500 text-center mt-2">
            เข้าสู่ระบบเพื่อดูการแจ้งเตือน
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
      <SafeAreaView className="flex-1">
        {/* Header */}
        <View className="flex-row items-center justify-between px-5 pt-4 pb-2">
          <View className="flex-row items-center">
            <Pressable onPress={() => router.back()} className="mr-4">
              <Text style={{ fontSize: 24, color: isDark ? '#fff' : '#000' }}>←</Text>
            </Pressable>
            <View>
              <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
                การแจ้งเตือน
              </Text>
              {unreadCount > 0 && (
                <Text className="text-primary-500 text-sm">
                  {unreadCount} ยังไม่อ่าน
                </Text>
              )}
            </View>
          </View>
          {unreadCount > 0 && (
            <Pressable
              onPress={handleMarkAllRead}
              className="px-3 py-2 rounded-xl bg-primary-100 dark:bg-primary-900"
            >
              <Text className="text-primary-500 font-medium text-sm">
                อ่านทั้งหมด
              </Text>
            </Pressable>
          )}
        </View>

        {/* Content */}
        {isLoading ? (
          <View className="flex-1 justify-center items-center">
            <ActivityIndicator size="large" color="#3B82F6" />
            <Text className="text-gray-500 mt-4">กำลังโหลด...</Text>
          </View>
        ) : notifications.length === 0 ? (
          <View className="flex-1 justify-center items-center px-6">
            <LinearGradient
              colors={['#3B82F6', '#8B5CF6']}
              style={{ width: 96, height: 96, borderRadius: 48, alignItems: 'center', justifyContent: 'center', marginBottom: 24 }}
            >
              <Text style={{ fontSize: 48 }}>🔕</Text>
            </LinearGradient>
            <Text className={`text-xl font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
              ไม่มีการแจ้งเตือน
            </Text>
            <Text className="text-gray-500 text-center mt-2">
              คุณจะได้รับการแจ้งเตือนที่นี่เมื่อมีข้อมูลใหม่
            </Text>
          </View>
        ) : (
          <FlatList
            data={notifications}
            keyExtractor={(item) => item.id.toString()}
            contentContainerClassName="px-5 py-4"
            refreshControl={
              <RefreshControl
                refreshing={isRefreshing}
                onRefresh={handleRefresh}
                tintColor="#3B82F6"
              />
            }
            renderItem={({ item, index }) => (
              <Animated.View entering={FadeInRight.delay(index * 30).springify()}>
                <NotificationItem
                  item={item}
                  isDark={isDark}
                  onPress={() => handleMarkAsRead(item)}
                  onDelete={() => handleDelete(item.id)}
                />
              </Animated.View>
            )}
          />
        )}
      </SafeAreaView>
    </View>
  );
}
