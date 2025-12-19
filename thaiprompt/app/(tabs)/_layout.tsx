/**
 * Tab Layout - Premium Version with 3D Effect
 * เพิ่มมิติให้เมนูล่างและปุ่มรถเข็ญช๊อปปิ้ง
 * + Sync Status Indicator
 */

import React, { useEffect, useRef, useState, useCallback } from 'react';
import { Tabs, router, useFocusEffect } from 'expo-router';
import { View, Text, StyleSheet, Platform, Pressable, Animated, Easing, Image } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
// ⭐ ลบ BlurView เพราะอาจ crash บน Android บางรุ่น
// import { BlurView } from 'expo-blur';
import { useCartStore } from '@/stores/cartStore';
import { useAuthStore } from '@/stores/authStore';
import { useSyncStore, initSyncMonitor } from '@/stores/syncStore';
import { getUnreadNotificationCount } from '@/services/api';
import { ErrorBoundary } from '@/components';
import { getAvatarUrl, getAvatarInitial } from '@/utils/user';

// Tab icons ใช้ emoji
const TAB_ICONS = {
  home: { active: '🏠', inactive: '🏡' },
  network: { active: '👥', inactive: '👤' },
  wallet: { active: '💰', inactive: '💳' },
  notifications: { active: '🔔', inactive: '🔕' },
  profile: { active: '⚙️', inactive: '👤' },
};

// Cart Badge Component - แสดงจำนวนสินค้าในตะกร้า
const CartBadge = ({ count }: { count: number }) => {
  const scaleAnim = useRef(new Animated.Value(0)).current;
  const pulseAnim = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    if (count > 0) {
      // Bounce animation เมื่อมีสินค้าเพิ่ม
      scaleAnim.setValue(0);
      Animated.spring(scaleAnim, {
        toValue: 1,
        tension: 100,
        friction: 8,
        useNativeDriver: true,
      }).start();

      // Pulse animation
      const pulse = () => {
        Animated.sequence([
          Animated.timing(pulseAnim, {
            toValue: 1.2,
            duration: 300,
            useNativeDriver: true,
          }),
          Animated.timing(pulseAnim, {
            toValue: 1,
            duration: 300,
            useNativeDriver: true,
          }),
        ]).start();
      };
      pulse();
    }
  }, [count, scaleAnim, pulseAnim]);

  if (count === 0) return null;

  return (
    <Animated.View
      style={[
        styles.cartBadge,
        {
          transform: [{ scale: Animated.multiply(scaleAnim, pulseAnim) }],
        },
      ]}
    >
      <LinearGradient
        colors={['#EF4444', '#DC2626']}
        style={styles.cartBadgeGradient}
      >
        <Text style={styles.cartBadgeText}>
          {count > 99 ? '99+' : count}
        </Text>
      </LinearGradient>
    </Animated.View>
  );
};

// Notification Badge Component - แสดงจำนวนข้อความที่ยังไม่ได้อ่าน
const NotificationBadge = ({ count }: { count: number }) => {
  const scaleAnim = useRef(new Animated.Value(0)).current;
  const pulseAnim = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    if (count > 0) {
      // Bounce animation
      scaleAnim.setValue(0);
      Animated.spring(scaleAnim, {
        toValue: 1,
        tension: 100,
        friction: 8,
        useNativeDriver: true,
      }).start();

      // Pulse animation loop
      const pulse = Animated.loop(
        Animated.sequence([
          Animated.timing(pulseAnim, {
            toValue: 1.15,
            duration: 800,
            useNativeDriver: true,
          }),
          Animated.timing(pulseAnim, {
            toValue: 1,
            duration: 800,
            useNativeDriver: true,
          }),
        ])
      );
      pulse.start();
      return () => pulse.stop();
    }
  }, [count, scaleAnim, pulseAnim]);

  if (count === 0) return null;

  return (
    <Animated.View
      style={[
        styles.notificationBadge,
        {
          transform: [{ scale: Animated.multiply(scaleAnim, pulseAnim) }],
        },
      ]}
    >
      <LinearGradient
        colors={['#EF4444', '#DC2626']}
        style={styles.notificationBadgeGradient}
      >
        <Text style={styles.notificationBadgeText}>
          {count > 99 ? '99+' : count}
        </Text>
      </LinearGradient>
    </Animated.View>
  );
};

// Notification Tab Button with Badge
const NotificationTabButton = ({
  focused,
  unreadCount,
  onPress,
}: {
  focused: boolean;
  unreadCount: number;
  onPress: () => void;
}) => {
  const scaleAnim = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    if (focused) {
      Animated.spring(scaleAnim, {
        toValue: 1.1,
        tension: 100,
        friction: 8,
        useNativeDriver: true,
      }).start();
    } else {
      Animated.spring(scaleAnim, {
        toValue: 1,
        tension: 100,
        friction: 8,
        useNativeDriver: true,
      }).start();
    }
  }, [focused, scaleAnim]);

  return (
    <Pressable onPress={onPress}>
      <Animated.View style={{ transform: [{ scale: scaleAnim }] }}>
        {focused ? (
          <View style={styles.activeTabContainer}>
            <LinearGradient
              colors={['#F59E0B', '#D97706']}
              style={styles.activeTab}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
            >
              <View style={styles.tabHighlight} />
              <Text style={styles.tabEmojiActive}>🔔</Text>
            </LinearGradient>
            <View style={[styles.tabShadow, { backgroundColor: 'rgba(245, 158, 11, 0.3)' }]} />
            {/* Badge */}
            <NotificationBadge count={unreadCount} />
          </View>
        ) : (
          <View style={styles.inactiveTab}>
            <Text style={styles.tabEmoji}>🔕</Text>
            {/* Badge */}
            <NotificationBadge count={unreadCount} />
          </View>
        )}
      </Animated.View>
    </Pressable>
  );
};

// Floating Cart Button Component - ปุ่มรถเข็นลอย (แสดงเฉพาะเมื่อมีสินค้า)
const FloatingCartButton = () => {
  const totalItems = useCartStore((state) => state.totalItems);
  const scaleAnim = useRef(new Animated.Value(0)).current;
  const rotateAnim = useRef(new Animated.Value(0)).current;

  // แสดง/ซ่อน animation เมื่อมี/ไม่มีสินค้า
  useEffect(() => {
    if (totalItems > 0) {
      Animated.spring(scaleAnim, {
        toValue: 1,
        tension: 80,
        friction: 8,
        useNativeDriver: true,
      }).start();
    } else {
      Animated.spring(scaleAnim, {
        toValue: 0,
        tension: 80,
        friction: 8,
        useNativeDriver: true,
      }).start();
    }
  }, [totalItems, scaleAnim]);

  const handlePressIn = () => {
    Animated.parallel([
      Animated.spring(scaleAnim, {
        toValue: 0.85,
        useNativeDriver: true,
      }),
      Animated.timing(rotateAnim, {
        toValue: 1,
        duration: 150,
        useNativeDriver: true,
      }),
    ]).start();
  };

  const handlePressOut = () => {
    Animated.parallel([
      Animated.spring(scaleAnim, {
        toValue: 1,
        tension: 100,
        friction: 5,
        useNativeDriver: true,
      }),
      Animated.timing(rotateAnim, {
        toValue: 0,
        duration: 150,
        useNativeDriver: true,
      }),
    ]).start();
  };

  const handlePress = () => {
    router.push('/cart');
  };

  const rotate = rotateAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '-10deg'],
  });

  // ไม่แสดงถ้าไม่มีสินค้า
  if (totalItems === 0) {
    return null;
  }

  return (
    <Pressable
      onPress={handlePress}
      onPressIn={handlePressIn}
      onPressOut={handlePressOut}
      style={styles.floatingCartContainer}
    >
      <Animated.View
        style={[
          styles.floatingCart,
          {
            transform: [{ scale: scaleAnim }, { rotate }],
          },
        ]}
      >
        <LinearGradient
          colors={['#3B82F6', '#2563EB', '#1D4ED8']}
          style={styles.floatingCartGradient}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
        >
          <View style={styles.floatingCartInner}>
            <Text style={styles.floatingCartEmoji}>🛒</Text>
          </View>
        </LinearGradient>

        {/* Badge */}
        <CartBadge count={totalItems} />
      </Animated.View>
    </Pressable>
  );
};

// Sync Status Indicator Component - แสดงเฉพาะเมื่อ offline
const SyncStatusBadge = () => {
  const { status, lastSyncTime, isConnected } = useSyncStore();
  const pulseAnim = useRef(new Animated.Value(1)).current;
  const rotateAnim = useRef(new Animated.Value(0)).current;
  const glowAnim = useRef(new Animated.Value(0.5)).current;

  const statusConfig = {
    online: { icon: '🟢', color: '#10B981', label: 'ออนไลน์' },
    offline: { icon: '🔴', color: '#EF4444', label: 'ออฟไลน์' },
    syncing: { icon: '🔄', color: '#3B82F6', label: 'Syncing...' },
    error: { icon: '⚠️', color: '#F59E0B', label: 'ผิดพลาด' },
  };

  const config = statusConfig[status];

  // ซ่อนเมื่อ online และ connected
  if (status === 'online' && isConnected) {
    return null;
  }

  // Animations
  useEffect(() => {
    // Glow pulse
    const glow = Animated.loop(
      Animated.sequence([
        Animated.timing(glowAnim, {
          toValue: 1,
          duration: 1500,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: false,
        }),
        Animated.timing(glowAnim, {
          toValue: 0.3,
          duration: 1500,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: false,
        }),
      ])
    );
    glow.start();

    return () => glow.stop();
  }, [glowAnim]);

  // Syncing animation
  useEffect(() => {
    if (status === 'syncing') {
      const rotate = Animated.loop(
        Animated.timing(rotateAnim, {
          toValue: 1,
          duration: 1000,
          easing: Easing.linear,
          useNativeDriver: true,
        })
      );
      rotate.start();

      const pulse = Animated.loop(
        Animated.sequence([
          Animated.timing(pulseAnim, {
            toValue: 1.1,
            duration: 400,
            useNativeDriver: true,
          }),
          Animated.timing(pulseAnim, {
            toValue: 1,
            duration: 400,
            useNativeDriver: true,
          }),
        ])
      );
      pulse.start();

      return () => {
        rotate.stop();
        pulse.stop();
      };
    }
    rotateAnim.setValue(0);
    pulseAnim.setValue(1);
  }, [status, rotateAnim, pulseAnim]);

  const spin = rotateAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  // Format last sync time
  const getLastSyncText = () => {
    if (!lastSyncTime) return '';
    const diff = Date.now() - lastSyncTime.getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'เมื่อสักครู่';
    if (mins < 60) return `${mins}น.`;
    return `${Math.floor(mins / 60)}ชม.`;
  };

  return (
    <Pressable style={styles.syncContainer}>
      {/* ⭐ เปลี่ยนจาก BlurView เป็น View ธรรมดา */}
      <View style={[styles.syncBlur, { backgroundColor: 'rgba(15, 23, 42, 0.95)' }]}>
        <View style={styles.syncContent}>
          {/* Glow effect */}
          <Animated.View
            style={[
              styles.syncGlow,
              {
                backgroundColor: config.color,
                opacity: glowAnim,
              },
            ]}
          />

          {/* Status icon */}
          <Animated.View
            style={[
              styles.syncIconContainer,
              { transform: [{ scale: pulseAnim }] },
            ]}
          >
            <Animated.Text
              style={[
                styles.syncIcon,
                status === 'syncing' && { transform: [{ rotate: spin }] },
              ]}
            >
              {config.icon}
            </Animated.Text>
          </Animated.View>

          {/* Status text */}
          <View style={styles.syncTextContainer}>
            <Text style={styles.syncLabel}>{config.label}</Text>
            {status === 'online' && lastSyncTime && (
              <Text style={styles.syncTime}>{getLastSyncText()}</Text>
            )}
          </View>

          {/* Connection indicator */}
          <Text style={styles.syncConnection}>
            {isConnected ? '📶' : '📵'}
          </Text>
        </View>
      </View>
    </Pressable>
  );
};

// Tab Button Component with 3D effect
const TabButton = ({ focused, icon, label }: { focused: boolean; icon: { active: string; inactive: string }; label: string }) => {
  const scaleAnim = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    if (focused) {
      Animated.spring(scaleAnim, {
        toValue: 1.1,
        tension: 100,
        friction: 8,
        useNativeDriver: true,
      }).start();
    } else {
      Animated.spring(scaleAnim, {
        toValue: 1,
        tension: 100,
        friction: 8,
        useNativeDriver: true,
      }).start();
    }
  }, [focused, scaleAnim]);

  return (
    <Animated.View style={{ transform: [{ scale: scaleAnim }] }}>
      {focused ? (
        <View style={styles.activeTabContainer}>
          <LinearGradient
            colors={['#3B82F6', '#2563EB']}
            style={styles.activeTab}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
          >
            {/* 3D highlight */}
            <View style={styles.tabHighlight} />
            <Text style={styles.tabEmojiActive}>
              {icon.active}
            </Text>
          </LinearGradient>
          {/* Shadow */}
          <View style={styles.tabShadow} />
        </View>
      ) : (
        <View style={styles.inactiveTab}>
          <Text style={styles.tabEmoji}>
            {icon.inactive}
          </Text>
        </View>
      )}
    </Animated.View>
  );
};

// ⭐ Profile Tab Button with Avatar (เพิ่ม error handling)
const ProfileTabButton = ({ focused, user }: { focused: boolean; user: any }) => {
  const scaleAnim = useRef(new Animated.Value(1)).current;

  // ⭐ Safe avatar URL - ป้องกัน crash
  let avatarUrl: string | null = null;
  let initial = 'U';
  try {
    avatarUrl = getAvatarUrl(user?.avatar);
    initial = getAvatarInitial(user?.name);
  } catch (e) {
    console.log('ProfileTabButton avatar error:', e);
  }

  useEffect(() => {
    try {
      if (focused) {
        Animated.spring(scaleAnim, {
          toValue: 1.1,
          tension: 100,
          friction: 8,
          useNativeDriver: true,
        }).start();
      } else {
        Animated.spring(scaleAnim, {
          toValue: 1,
          tension: 100,
          friction: 8,
          useNativeDriver: true,
        }).start();
      }
    } catch (e) {
      // Ignore animation errors
    }
  }, [focused, scaleAnim]);

  return (
    <Animated.View style={{ transform: [{ scale: scaleAnim }] }}>
      {focused ? (
        <View style={styles.activeTabContainer}>
          {avatarUrl ? (
            <Image
              source={{ uri: avatarUrl }}
              style={styles.profileTabAvatar}
            />
          ) : (
            <LinearGradient
              colors={['#3B82F6', '#2563EB']}
              style={styles.activeTab}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
            >
              <View style={styles.tabHighlight} />
              <Text style={styles.tabEmojiActive}>{initial}</Text>
            </LinearGradient>
          )}
          <View style={styles.tabShadow} />
        </View>
      ) : (
        <View style={styles.inactiveTab}>
          {avatarUrl ? (
            <Image
              source={{ uri: avatarUrl }}
              style={styles.profileTabAvatarInactive}
            />
          ) : (
            <Text style={styles.tabEmoji}>{initial}</Text>
          )}
        </View>
      )}
    </Animated.View>
  );
};

export default function TabLayout() {
  const { isAuthenticated, user } = useAuthStore();
  const [unreadCount, setUnreadCount] = useState(0);
  const { startSync, completeSync } = useSyncStore();

  // เริ่มต้น Sync Monitor
  useEffect(() => {
    const unsubscribe = initSyncMonitor();
    return () => unsubscribe();
  }, []);

  // ดึงจำนวนข้อความที่ยังไม่ได้อ่าน
  const fetchUnreadCount = useCallback(async () => {
    if (!isAuthenticated) {
      setUnreadCount(0);
      return;
    }

    try {
      const response = await getUnreadNotificationCount();
      // ⭐ แก้ไข: ใช้ unreadCount แทน count (ตาม API response type)
      if (response?.success && typeof response.data?.unreadCount === 'number') {
        setUnreadCount(response.data.unreadCount);
      }
    } catch (error) {
      console.log('Failed to fetch unread count:', error);
      // ไม่ให้ crash - ตั้งค่าเป็น 0
      setUnreadCount(0);
    }
  }, [isAuthenticated]);

  // ดึงข้อมูลเมื่อเข้าแอพ และ refresh ทุก 30 วินาที
  useEffect(() => {
    fetchUnreadCount();

    // Auto refresh ทุก 30 วินาที
    const interval = setInterval(fetchUnreadCount, 30000);

    return () => clearInterval(interval);
  }, [fetchUnreadCount]);

  // Refresh เมื่อกลับมาที่ tab
  useFocusEffect(
    useCallback(() => {
      fetchUnreadCount();
    }, [fetchUnreadCount])
  );

  const handleNotificationPress = () => {
    router.push('/notifications');
  };

  return (
    <ErrorBoundary>
    <View style={{ flex: 1 }}>
      {/* Sync Status Badge - แสดงเฉพาะเมื่อ offline */}
      <View style={styles.syncBadgeWrapper}>
        <SyncStatusBadge />
      </View>

      {/* Floating Cart Button - แสดงเฉพาะเมื่อมีสินค้า */}
      <FloatingCartButton />

      <Tabs
        screenOptions={{
          headerShown: false,
          tabBarStyle: styles.tabBar,
          tabBarActiveTintColor: '#3B82F6',
          tabBarInactiveTintColor: '#6B7280',
          tabBarLabelStyle: styles.tabBarLabel,
          tabBarBackground: () => (
            <View style={styles.tabBarBackground}>
              {/* Top border gradient */}
              <LinearGradient
                colors={['rgba(59, 130, 246, 0.4)', 'rgba(139, 92, 246, 0.3)', 'rgba(236, 72, 153, 0.2)']}
                style={styles.tabBarTopBorder}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
              />
              {/* Background gradient */}
              <LinearGradient
                colors={['#0F172A', '#1E293B', '#0F172A']}
                style={styles.tabBarGradient}
                start={{ x: 0, y: 0 }}
                end={{ x: 0, y: 1 }}
              />
            </View>
          ),
        }}
      >
        <Tabs.Screen
          name="index"
          options={{
            title: 'หน้าหลัก',
            tabBarIcon: ({ focused }) => (
              <TabButton focused={focused} icon={TAB_ICONS.home} label="หน้าหลัก" />
            ),
          }}
        />
        <Tabs.Screen
          name="network"
          options={{
            title: 'สายงาน',
            tabBarIcon: ({ focused }) => (
              <TabButton focused={focused} icon={TAB_ICONS.network} label="สายงาน" />
            ),
          }}
        />
        <Tabs.Screen
          name="wallet"
          options={{
            title: 'กระเป๋า',
            tabBarIcon: ({ focused }) => (
              <TabButton focused={focused} icon={TAB_ICONS.wallet} label="กระเป๋า" />
            ),
          }}
        />
        {/* Notification Tab with Badge */}
        <Tabs.Screen
          name="notifications-tab"
          options={{
            title: 'แจ้งเตือน',
            tabBarIcon: ({ focused }) => (
              <NotificationTabButton
                focused={focused}
                unreadCount={unreadCount}
                onPress={handleNotificationPress}
              />
            ),
            tabBarButton: (props) => (
              <Pressable
                {...props}
                onPress={handleNotificationPress}
                style={props.style}
              />
            ),
          }}
        />
        <Tabs.Screen
          name="profile"
          options={{
            title: 'โปรไฟล์',
            tabBarIcon: ({ focused }) => (
              <ProfileTabButton focused={focused} user={user} />
            ),
          }}
        />
      </Tabs>
    </View>
    </ErrorBoundary>
  );
}

const styles = StyleSheet.create({
  tabBar: {
    backgroundColor: 'transparent',
    borderTopWidth: 0,
    height: Platform.OS === 'ios' ? 90 : 70,
    paddingBottom: Platform.OS === 'ios' ? 25 : 10,
    paddingTop: 10,
    elevation: 0,
    shadowOpacity: 0,
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
  },
  tabBarBackground: {
    ...StyleSheet.absoluteFillObject,
    overflow: 'hidden',
  },
  tabBarTopBorder: {
    height: 2,
  },
  tabBarGradient: {
    flex: 1,
  },
  tabBarLabel: {
    fontSize: 10,
    fontWeight: '600',
    marginTop: 2,
  },
  // Active tab with 3D effect
  activeTabContainer: {
    position: 'relative',
  },
  activeTab: {
    width: 44,
    height: 44,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    // 3D shadow
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.4,
    shadowRadius: 8,
    elevation: 8,
  },
  tabHighlight: {
    position: 'absolute',
    top: 2,
    left: 4,
    right: 4,
    height: 12,
    borderRadius: 8,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
  },
  tabShadow: {
    position: 'absolute',
    bottom: -4,
    left: 6,
    right: 6,
    height: 10,
    borderRadius: 14,
    backgroundColor: 'rgba(59, 130, 246, 0.3)',
    transform: [{ scaleY: 0.3 }],
  },
  inactiveTab: {
    width: 44,
    height: 44,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.05)',
  },
  tabEmoji: {
    fontSize: 20,
    opacity: 0.7,
  },
  tabEmojiActive: {
    fontSize: 20,
  },
  // ⭐ Profile Tab Avatar Styles
  profileTabAvatar: {
    width: 44,
    height: 44,
    borderRadius: 14,
    borderWidth: 2,
    borderColor: '#3B82F6',
  },
  profileTabAvatarInactive: {
    width: 36,
    height: 36,
    borderRadius: 12,
    opacity: 0.7,
  },
  // Floating Cart Button Styles - ปุ่มรถเข็นลอย
  floatingCartContainer: {
    position: 'absolute',
    bottom: Platform.OS === 'ios' ? 100 : 80,
    right: 20,
    zIndex: 999,
  },
  floatingCart: {
    width: 64,
    height: 64,
    borderRadius: 32,
    // 3D shadow
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.6,
    shadowRadius: 16,
    elevation: 16,
  },
  floatingCartGradient: {
    width: 64,
    height: 64,
    borderRadius: 32,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 3,
    borderColor: 'rgba(255, 255, 255, 0.25)',
  },
  floatingCartInner: {
    width: '100%',
    height: '100%',
    borderRadius: 29,
    alignItems: 'center',
    justifyContent: 'center',
    // Inner highlight
    borderTopWidth: 2,
    borderTopColor: 'rgba(255, 255, 255, 0.35)',
    borderLeftWidth: 1,
    borderLeftColor: 'rgba(255, 255, 255, 0.15)',
  },
  floatingCartEmoji: {
    fontSize: 32,
  },
  // Cart Badge Styles
  cartBadge: {
    position: 'absolute',
    top: -5,
    right: -5,
    minWidth: 22,
    height: 22,
    zIndex: 10,
  },
  cartBadgeGradient: {
    minWidth: 22,
    height: 22,
    borderRadius: 11,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 6,
    borderWidth: 2,
    borderColor: '#0F172A',
  },
  cartBadgeText: {
    color: '#FFFFFF',
    fontSize: 11,
    fontWeight: 'bold',
  },
  // Notification Badge Styles
  notificationBadge: {
    position: 'absolute',
    top: -5,
    right: -5,
    minWidth: 18,
    height: 18,
    zIndex: 10,
  },
  notificationBadgeGradient: {
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
    borderWidth: 2,
    borderColor: '#0F172A',
  },
  notificationBadgeText: {
    color: '#FFFFFF',
    fontSize: 10,
    fontWeight: 'bold',
  },
  // Sync Status Badge Styles
  syncBadgeWrapper: {
    position: 'absolute',
    bottom: Platform.OS === 'ios' ? 95 : 75,
    left: 12,
    zIndex: 100,
  },
  syncContainer: {
    borderRadius: 20,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 8,
  },
  syncBlur: {
    borderRadius: 20,
    overflow: 'hidden',
  },
  syncContent: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: 'rgba(15, 23, 42, 0.85)',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
  },
  syncGlow: {
    position: 'absolute',
    left: 8,
    width: 24,
    height: 24,
    borderRadius: 12,
  },
  syncIconContainer: {
    width: 20,
    height: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  syncIcon: {
    fontSize: 12,
  },
  syncTextContainer: {
    marginLeft: 6,
    marginRight: 6,
  },
  syncLabel: {
    fontSize: 11,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  syncTime: {
    fontSize: 9,
    color: 'rgba(255, 255, 255, 0.5)',
  },
  syncConnection: {
    fontSize: 12,
  },
});
