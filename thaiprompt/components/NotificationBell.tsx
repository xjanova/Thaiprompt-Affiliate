/**
 * NotificationBell — กระดิ่งแจ้งเตือนบนหัวหน้าแรก
 *
 * - แสดงจำนวนที่ยังไม่อ่าน (ป้ายทอง) และส่ายเบาๆ เมื่อมีรายการใหม่
 * - ดึงจำนวนทุก 30 วินาทีระหว่างที่หน้าแสดงอยู่ · ยังไม่ล็อกอิน = ไม่ดึง
 * - บนหัวน้ำเงินเป็นปุ่มกระจกอัตโนมัติ (IconButton)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { router } from 'expo-router';
import Animated, { useAnimatedStyle, useSharedValue, withRepeat, withSequence, withSpring } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { getUnreadNotificationCount } from '@/services/api';
import { IconButton } from '@/components/ui/IconButton';

interface NotificationBellProps {
  size?: number;
}

export default function NotificationBell({ size = 42 }: NotificationBellProps) {
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const [unreadCount, setUnreadCount] = useState(0);
  const prevCount = useRef(0);
  const rotation = useSharedValue(0);

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
    } catch {
      // เงียบไว้ — กระดิ่งไม่ควรทำให้หน้าแรกพัง
    }
  }, [isAuthenticated]);

  useEffect(() => {
    fetchUnreadCount();
    const interval = setInterval(fetchUnreadCount, 30000);
    return () => clearInterval(interval);
  }, [fetchUnreadCount]);

  // มีรายการใหม่เพิ่มขึ้น → กระดิ่งส่าย
  useEffect(() => {
    if (unreadCount > prevCount.current) {
      rotation.value = withSequence(
        withRepeat(withSequence(withSpring(12, { damping: 3 }), withSpring(-12, { damping: 3 })), 2, true),
        withSpring(0)
      );
    }
    prevCount.current = unreadCount;
  }, [unreadCount, rotation]);

  const ring = useAnimatedStyle(() => ({ transform: [{ rotate: `${rotation.value}deg` }] }));

  return (
    <Animated.View style={ring}>
      <IconButton
        icon="bell"
        size={size}
        badge={unreadCount}
        label={unreadCount > 0 ? `การแจ้งเตือน ยังไม่อ่าน ${unreadCount} รายการ` : 'การแจ้งเตือน'}
        onPress={() => router.push((isAuthenticated ? '/notifications' : '/login') as never)}
      />
    </Animated.View>
  );
}
