/**
 * NotificationBell Component - กระดิ่งแจ้งเตือน
 * แสดงจำนวนการแจ้งเตือนที่ยังไม่อ่านและนำทางไปหน้า Notifications
 */

import React, { useEffect, useState, useCallback } from 'react';
import { View, Pressable, Text } from 'react-native';
import { router } from 'expo-router';
import Animated, {
  useSharedValue,
  useAnimatedStyle,
  withSequence,
  withSpring,
  withRepeat,
  withDelay,
} from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { getUnreadNotificationCount } from '@/services/api';

interface NotificationBellProps {
  color?: string;
  size?: number;
  showBadge?: boolean;
}

export default function NotificationBell({
  color = '#3B82F6',
  size = 24,
  showBadge = true,
}: NotificationBellProps) {
  const { isAuthenticated } = useAuthStore();
  const [unreadCount, setUnreadCount] = useState(0);

  // Animation values
  const scale = useSharedValue(1);
  const rotation = useSharedValue(0);

  // Fetch unread count
  const fetchUnreadCount = useCallback(async () => {
    if (!isAuthenticated) {
      setUnreadCount(0);
      return;
    }

    try {
      const response = await getUnreadNotificationCount();
      if (response?.success && response.data) {
        setUnreadCount(response.data.unreadCount);
      }
    } catch (error) {
      console.error('Fetch unread count error:', error);
    }
  }, [isAuthenticated]);

  // Initial fetch and polling
  useEffect(() => {
    fetchUnreadCount();

    // Poll every 30 seconds
    const interval = setInterval(fetchUnreadCount, 30000);

    return () => clearInterval(interval);
  }, [fetchUnreadCount]);

  // Animate when unread count changes
  useEffect(() => {
    if (unreadCount > 0) {
      // Ring animation
      rotation.value = withSequence(
        withSpring(-10),
        withRepeat(
          withSequence(
            withSpring(10, { damping: 2 }),
            withSpring(-10, { damping: 2 })
          ),
          3,
          true
        ),
        withSpring(0)
      );

      // Scale animation
      scale.value = withSequence(
        withSpring(1.2),
        withDelay(200, withSpring(1))
      );
    }
  }, [unreadCount, scale, rotation]);

  // Animated styles
  const animatedStyle = useAnimatedStyle(() => {
    return {
      transform: [
        { scale: scale.value },
        { rotate: `${rotation.value}deg` },
      ],
    };
  });

  const handlePress = () => {
    router.push('/notifications');
  };

  return (
    <Pressable onPress={handlePress} className="relative p-2">
      <Animated.View style={animatedStyle}>
        <Text style={{ fontSize: size, color }}>
          🔔
        </Text>
      </Animated.View>

      {/* Badge */}
      {showBadge && unreadCount > 0 && (
        <View
          className="absolute -top-0 -right-0 min-w-[18px] h-[18px] rounded-full bg-red-500 items-center justify-center"
          style={{
            borderWidth: 2,
            borderColor: '#fff',
          }}
        >
          <Text className="text-white text-[10px] font-bold">
            {unreadCount > 99 ? '99+' : unreadCount}
          </Text>
        </View>
      )}
    </Pressable>
  );
}

// Export a smaller version for tabs
export function NotificationBellSmall() {
  return <NotificationBell size={20} />;
}
