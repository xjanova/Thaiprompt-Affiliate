/**
 * Tab Layout - Premium Version with 3D Effect
 * เพิ่มมิติให้เมนูล่างและปุ่มรถเข็ญช๊อปปิ้ง
 */

import React, { useEffect, useRef, useState, useCallback } from 'react';
import { Tabs, router, useFocusEffect } from 'expo-router';
import { View, Text, StyleSheet, Platform, Pressable, Animated, Easing } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useCartStore } from '@/stores/cartStore';
import { useAuthStore } from '@/stores/authStore';
import { getUnreadNotificationCount } from '@/services/api';

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

// Cart Button Component - ปุ่มตะกร้าตรงกลาง
const CartButton = () => {
  const totalItems = useCartStore((state) => state.totalItems);
  const scaleAnim = useRef(new Animated.Value(1)).current;
  const rotateAnim = useRef(new Animated.Value(0)).current;
  const glowAnim = useRef(new Animated.Value(0.5)).current;

  useEffect(() => {
    // Glow pulse animation
    const glowLoop = Animated.loop(
      Animated.sequence([
        Animated.timing(glowAnim, {
          toValue: 1,
          duration: 1500,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: false,
        }),
        Animated.timing(glowAnim, {
          toValue: 0.5,
          duration: 1500,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: false,
        }),
      ])
    );
    glowLoop.start();

    return () => glowLoop.stop();
  }, [glowAnim]);

  const handlePressIn = () => {
    Animated.parallel([
      Animated.spring(scaleAnim, {
        toValue: 0.9,
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

  return (
    <View style={styles.cartButtonContainer}>
      {/* Glow effect */}
      <Animated.View
        style={[
          styles.cartGlow,
          {
            opacity: glowAnim,
            transform: [{ scale: glowAnim.interpolate({
              inputRange: [0.5, 1],
              outputRange: [1, 1.3],
            }) }],
          },
        ]}
      />

      <Pressable
        onPress={handlePress}
        onPressIn={handlePressIn}
        onPressOut={handlePressOut}
      >
        <Animated.View
          style={[
            styles.cartButton,
            {
              transform: [{ scale: scaleAnim }, { rotate }],
            },
          ]}
        >
          <LinearGradient
            colors={['#3B82F6', '#2563EB', '#1D4ED8']}
            style={styles.cartButtonGradient}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
          >
            {/* 3D inner shadow */}
            <View style={styles.cartButtonInner}>
              <Text style={styles.cartEmoji}>🛒</Text>
            </View>
          </LinearGradient>

          {/* Cart Badge */}
          <CartBadge count={totalItems} />
        </Animated.View>
      </Pressable>
    </View>
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

export default function TabLayout() {
  const { isAuthenticated } = useAuthStore();
  const [unreadCount, setUnreadCount] = useState(0);

  // ดึงจำนวนข้อความที่ยังไม่ได้อ่าน
  const fetchUnreadCount = useCallback(async () => {
    if (!isAuthenticated) {
      setUnreadCount(0);
      return;
    }

    try {
      const response = await getUnreadNotificationCount();
      if (response?.success && typeof response.data?.count === 'number') {
        setUnreadCount(response.data.count);
      }
    } catch (error) {
      console.log('Failed to fetch unread count:', error);
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
    <View style={{ flex: 1 }}>
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
                colors={['rgba(59, 130, 246, 0.3)', 'rgba(139, 92, 246, 0.2)', 'rgba(59, 130, 246, 0.1)']}
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
        {/* Placeholder for cart button */}
        <Tabs.Screen
          name="cart-placeholder"
          options={{
            title: '',
            tabBarButton: () => <CartButton />,
          }}
          listeners={{
            tabPress: (e) => {
              e.preventDefault();
            },
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
              <TabButton focused={focused} icon={TAB_ICONS.profile} label="โปรไฟล์" />
            ),
          }}
        />
      </Tabs>
    </View>
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
  // Cart Button Styles
  cartButtonContainer: {
    position: 'relative',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: -30,
    width: 70,
  },
  cartGlow: {
    position: 'absolute',
    width: 70,
    height: 70,
    borderRadius: 35,
    backgroundColor: 'rgba(59, 130, 246, 0.3)',
  },
  cartButton: {
    width: 60,
    height: 60,
    borderRadius: 30,
    // 3D shadow
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.5,
    shadowRadius: 12,
    elevation: 12,
  },
  cartButtonGradient: {
    width: 60,
    height: 60,
    borderRadius: 30,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 3,
    borderColor: 'rgba(255, 255, 255, 0.2)',
  },
  cartButtonInner: {
    width: '100%',
    height: '100%',
    borderRadius: 27,
    alignItems: 'center',
    justifyContent: 'center',
    // Inner highlight
    borderTopWidth: 2,
    borderTopColor: 'rgba(255, 255, 255, 0.3)',
    borderLeftWidth: 1,
    borderLeftColor: 'rgba(255, 255, 255, 0.1)',
  },
  cartEmoji: {
    fontSize: 28,
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
});
