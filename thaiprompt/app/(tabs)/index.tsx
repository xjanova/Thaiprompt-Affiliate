/**
 * Home Screen - Premium Design V5
 * ปรับปรุงปุ่มให้สวยงามมากขึ้น
 * - รูปภาพ 16:9 aspect ratio พอดี ไม่ crop
 * - 3D effect พร้อมเงาและแสง
 * - Glassmorphism effect
 * - Firefly animation background
 * - Pin menu feature (กดค้างเพื่อ pin)
 * - Smooth animations
 */

import React, { useState, useCallback, useRef, useEffect, useMemo } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  StyleSheet,
  StatusBar,
  Dimensions,
  RefreshControl,
  Animated,
  Image,
  ImageSourcePropType,
  Easing,
  Vibration,
  Alert,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';
import { BannerCarousel } from '@/components';
import * as SecureStore from 'expo-secure-store';

const { width, height: screenHeight } = Dimensions.get('window');
// ปรับขนาดปุ่มให้รองรับภาพ 16:9
const CARD_WIDTH = (width - 52) / 2;
const CARD_HEIGHT = Math.round(CARD_WIDTH * 9 / 16); // 16:9 aspect ratio

// Key สำหรับเก็บ pinned items
const PINNED_ITEMS_KEY = 'home_pinned_items';

// ภาพปุ่มเมนู - ใช้ภาพจาก assets/images/2bt (Premium 3D Style)
const MENU_IMAGES: Record<string, ImageSourcePropType> = {
  wallet: require('@/assets/images/2bt/wallet.png'),
  network: require('@/assets/images/2bt/MLMNetwork.png'),
  referral: require('@/assets/images/2bt/refer_friend.png'),
  commissions: require('@/assets/images/2bt/Commission.png'),
  shopping: require('@/assets/images/2bt/shopping.png'),
  services: require('@/assets/images/2bt/service.png'),
  rider: require('@/assets/images/2bt/rider.png'),
  tpix: require('@/assets/images/TPIX.png'),  // TPIX Token - ยังใช้รูปเดิม
  'wealth-guide': require('@/assets/images/2bt/RichesPath.png'),
  tarot: require('@/assets/images/2bt/tarot.png'),
  leaderboard: require('@/assets/images/2bt/Ranking.png'),
  'coming-soon': require('@/assets/images/2bt/comming soon.png'),
  settings: require('@/assets/images/2bt/setting.png'),
  academy: require('@/assets/images/2bt/academy.png'),
  'watch-earn': require('@/assets/images/2bt/watch&earn.png'),
};

// Menu Items - ทุกปุ่มใช้ภาพจาก 2bt folder (Premium 3D Style)
const MENU_ITEMS = [
  { id: 'wallet', title: 'กระเป๋าเงิน', icon: '💰', colors: ['#10B981', '#059669'], glowColor: '#10B981', route: '/(tabs)/wallet', hasImage: true },
  { id: 'network', title: 'ดูผัง MLM', icon: '👥', colors: ['#8B5CF6', '#6D28D9'], glowColor: '#8B5CF6', route: '/(tabs)/network', hasImage: true },
  { id: 'referral', title: 'แนะนำเพื่อน', icon: '🤝', colors: ['#EC4899', '#DB2777'], glowColor: '#EC4899', route: '/referral', hasImage: true },
  { id: 'commissions', title: 'คอมมิชชั่น', icon: '💵', colors: ['#3B82F6', '#2563EB'], glowColor: '#3B82F6', route: '/commissions', hasImage: true },
  { id: 'shopping', title: 'ช้อปปิ้ง', icon: '🛒', colors: ['#F59E0B', '#D97706'], glowColor: '#F59E0B', route: '/shopping', hasImage: true },
  { id: 'services', title: 'สั่งบริการ', icon: '📦', colors: ['#14B8A6', '#0D9488'], glowColor: '#14B8A6', route: '/services', hasImage: true },
  { id: 'rider', title: 'เป็นไรเดอร์', icon: '🚴', colors: ['#06B6D4', '#0891B2'], glowColor: '#06B6D4', route: '/rider', hasImage: true },
  { id: 'tpix', title: 'TPIX Token', icon: '🪙', colors: ['#FFD700', '#FFA500'], glowColor: '#FFD700', route: '/tpix', hasImage: true },
  { id: 'wealth-guide', title: 'เส้นทางเศรษฐี', icon: '📚', colors: ['#F97316', '#EA580C'], glowColor: '#F97316', route: '/wealth-guide', hasImage: true },
  { id: 'academy', title: 'Academy', icon: '🎓', colors: ['#0EA5E9', '#0284C7'], glowColor: '#0EA5E9', route: '/academy', hasImage: true },
  { id: 'watch-earn', title: 'ดูคลิปได้เงิน', icon: '🎬', colors: ['#E11D48', '#BE123C'], glowColor: '#E11D48', route: '/watch-earn', hasImage: true },
  { id: 'tarot', title: 'ดูดวง', icon: '🔮', colors: ['#6366F1', '#4F46E5'], glowColor: '#6366F1', route: '/tarot', hasImage: true },
  { id: 'leaderboard', title: 'อันดับ', icon: '🏆', colors: ['#EF4444', '#DC2626'], glowColor: '#EF4444', route: '/leaderboard', hasImage: true },
  { id: 'settings', title: 'ตั้งค่า', icon: '⚙️', colors: ['#64748B', '#475569'], glowColor: '#64748B', route: '/settings', hasImage: true },
  { id: 'coming-soon', title: 'เร็วๆ นี้', icon: '🚀', colors: ['#A855F7', '#9333EA'], glowColor: '#A855F7', route: '/coming-soon', hasImage: true },
] as const;

// จำนวนหิ่งห้อย (ลดจำนวนเพื่อไม่ให้แอพหนัก)
const NUM_FIREFLIES = 12;

// Firefly Component - หิ่งห้อยเรืองแสง (ปรับปรุงให้สว่างและชัดขึ้น)
const Firefly = ({ delay, duration }: { delay: number; duration: number }) => {
  const opacity = useRef(new Animated.Value(0)).current;
  const translateX = useRef(new Animated.Value(Math.random() * width)).current;
  const translateY = useRef(new Animated.Value(Math.random() * screenHeight * 0.6)).current;
  const scale = useRef(new Animated.Value(0.8 + Math.random() * 0.4)).current;
  const glowOpacity = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    let isMounted = true;

    const animate = () => {
      if (!isMounted) return;

      // Random new position
      const newX = Math.random() * width;
      const newY = Math.random() * screenHeight * 0.6;

      Animated.parallel([
        // Fade in and out - เพิ่มความสว่าง
        Animated.sequence([
          Animated.timing(opacity, {
            toValue: 0.7 + Math.random() * 0.3, // สว่างขึ้น 0.7-1.0
            duration: duration * 0.25,
            useNativeDriver: true,
          }),
          Animated.delay(duration * 0.2), // ค้างไว้ให้เห็นชัด
          Animated.timing(opacity, {
            toValue: 0,
            duration: duration * 0.55,
            useNativeDriver: true,
          }),
        ]),
        // Glow pulse
        Animated.sequence([
          Animated.timing(glowOpacity, {
            toValue: 1,
            duration: duration * 0.25,
            useNativeDriver: true,
          }),
          Animated.delay(duration * 0.2),
          Animated.timing(glowOpacity, {
            toValue: 0,
            duration: duration * 0.55,
            useNativeDriver: true,
          }),
        ]),
        // Move
        Animated.timing(translateX, {
          toValue: newX,
          duration: duration,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(translateY, {
          toValue: newY,
          duration: duration,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        // Pulse - ขยายขนาดให้ชัดขึ้น
        Animated.sequence([
          Animated.timing(scale, {
            toValue: 1.2 + Math.random() * 0.5,
            duration: duration * 0.4,
            useNativeDriver: true,
          }),
          Animated.timing(scale, {
            toValue: 0.8 + Math.random() * 0.3,
            duration: duration * 0.6,
            useNativeDriver: true,
          }),
        ]),
      ]).start(() => {
        if (isMounted) animate();
      });
    };

    // Delay start
    const timeout = setTimeout(animate, delay);

    return () => {
      isMounted = false;
      clearTimeout(timeout);
    };
  }, []);

  return (
    <Animated.View
      style={{
        position: 'absolute',
        transform: [{ translateX }, { translateY }, { scale }],
      }}
    >
      {/* Outer glow - แสงรอบนอก */}
      <Animated.View
        style={{
          position: 'absolute',
          width: 24,
          height: 24,
          borderRadius: 12,
          backgroundColor: '#FFD700',
          opacity: Animated.multiply(glowOpacity, 0.3),
          left: -9,
          top: -9,
        }}
      />
      {/* Middle glow */}
      <Animated.View
        style={{
          position: 'absolute',
          width: 14,
          height: 14,
          borderRadius: 7,
          backgroundColor: '#FFEC8B',
          opacity: Animated.multiply(glowOpacity, 0.5),
          left: -4,
          top: -4,
        }}
      />
      {/* Core - แกนกลางสว่าง */}
      <Animated.View
        style={{
          width: 8,
          height: 8,
          borderRadius: 4,
          backgroundColor: '#FFFACD',
          opacity,
          shadowColor: '#FFD700',
          shadowOffset: { width: 0, height: 0 },
          shadowOpacity: 1,
          shadowRadius: 12,
          elevation: 8,
        }}
      />
    </Animated.View>
  );
};

// Fireflies Background Component
const FirefliesBackground = () => {
  const fireflies = useMemo(() => {
    return Array.from({ length: NUM_FIREFLIES }, (_, i) => ({
      id: i,
      delay: Math.random() * 3000,
      duration: 4000 + Math.random() * 4000,
    }));
  }, []);

  return (
    <View style={StyleSheet.absoluteFill} pointerEvents="none">
      {fireflies.map((fly) => (
        <Firefly key={fly.id} delay={fly.delay} duration={fly.duration} />
      ))}
    </View>
  );
};

// Menu Card Component - ปรับปรุงให้รูปภาพเต็มปุ่ม 16:9 + Pin feature
const MenuCard = ({
  item,
  index,
  onPress,
  isPinned,
  onLongPress,
}: {
  item: typeof MENU_ITEMS[number];
  index: number;
  onPress: () => void;
  isPinned?: boolean;
  onLongPress?: () => void;
}) => {
  const scaleAnim = useRef(new Animated.Value(1)).current;
  const glowAnim = useRef(new Animated.Value(0)).current;
  const pressAnim = useRef(new Animated.Value(0)).current;
  const shineAnim = useRef(new Animated.Value(0)).current;

  // Glow animation
  useEffect(() => {
    let isMounted = true;
    const animation = Animated.loop(
      Animated.sequence([
        Animated.timing(glowAnim, {
          toValue: 1,
          duration: 2000,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: false,
        }),
        Animated.timing(glowAnim, {
          toValue: 0,
          duration: 2000,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: false,
        }),
      ])
    );

    // Shine animation - เงาวาวเลื่อนผ่าน
    const shineAnimation = Animated.loop(
      Animated.sequence([
        Animated.timing(shineAnim, {
          toValue: 1,
          duration: 3000,
          easing: Easing.linear,
          useNativeDriver: true,
        }),
        Animated.delay(2000),
        Animated.timing(shineAnim, {
          toValue: 0,
          duration: 0,
          useNativeDriver: true,
        }),
      ])
    );

    // Stagger start based on index
    const timeout = setTimeout(() => {
      if (isMounted) {
        animation.start();
        shineAnimation.start();
      }
    }, index * 300);

    return () => {
      isMounted = false;
      clearTimeout(timeout);
      animation.stop();
      shineAnimation.stop();
    };
  }, [glowAnim, shineAnim, index]);

  const handlePressIn = () => {
    Animated.parallel([
      Animated.spring(scaleAnim, {
        toValue: 0.92,
        useNativeDriver: true,
        speed: 50,
        bounciness: 4,
      }),
      Animated.timing(pressAnim, {
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
        useNativeDriver: true,
        speed: 20,
        bounciness: 8,
      }),
      Animated.timing(pressAnim, {
        toValue: 0,
        duration: 150,
        useNativeDriver: true,
      }),
    ]).start();
  };

  const glowOpacity = glowAnim.interpolate({
    inputRange: [0, 1],
    outputRange: [0.4, 0.7],
  });

  const shineTranslateX = shineAnim.interpolate({
    inputRange: [0, 1],
    outputRange: [-CARD_WIDTH, CARD_WIDTH * 2],
  });

  const pressTranslateY = pressAnim.interpolate({
    inputRange: [0, 1],
    outputRange: [0, 2],
  });

  const hasImage = item.hasImage && MENU_IMAGES[item.id];

  return (
    <Animated.View
      style={[
        styles.menuCardWrapper,
        {
          transform: [
            { scale: scaleAnim },
            { translateY: pressTranslateY },
          ]
        },
      ]}
    >
      {/* Glow Effect - เงาเรืองแสง */}
      <Animated.View
        style={[
          styles.menuGlow,
          {
            backgroundColor: item.glowColor,
            opacity: glowOpacity,
          },
        ]}
      />

      {/* 3D Shadow Base */}
      <View style={[styles.menu3DShadow, { backgroundColor: item.colors[1] }]} />

      <Pressable
        onPress={onPress}
        onLongPress={() => {
          Vibration.vibrate(50);  // สั่นเบาๆ เมื่อ pin/unpin
          onLongPress?.();
        }}
        delayLongPress={500}
        onPressIn={handlePressIn}
        onPressOut={handlePressOut}
        style={styles.menuCard}
      >
        <LinearGradient
          colors={item.colors}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.menuGradient}
        >
          {/* Pin Indicator - แสดงเมื่อ pinned */}
          {isPinned && (
            <View style={styles.pinIndicator}>
              <Text style={styles.pinIcon}>📌</Text>
            </View>
          )}

          {/* Glass border - ขอบแก้ว 3D */}
          <View style={styles.glassBorder} />
          <View style={styles.glassHighlight} />

          {/* แสดงภาพเต็มปุ่ม ถ้ามีภาพ */}
          {hasImage ? (
            <View style={styles.fullImageContainer}>
              <Image
                source={MENU_IMAGES[item.id]}
                style={styles.fullImage}
                resizeMode="cover"
              />
              {/* Overlay gradient ด้านล่าง (ไม่แสดงชื่อ) */}
              <LinearGradient
                colors={['transparent', 'rgba(0,0,0,0.3)']}
                style={styles.imageOverlay}
              />
              {/* Shine effect เลื่อนผ่าน */}
              <Animated.View
                style={[
                  styles.shineEffect,
                  {
                    transform: [{ translateX: shineTranslateX }],
                  },
                ]}
              >
                <LinearGradient
                  colors={['transparent', 'rgba(255,255,255,0.4)', 'transparent']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                  style={styles.shineGradient}
                />
              </Animated.View>
            </View>
          ) : (
            // ถ้าไม่มีภาพ ใช้ emoji
            <>
              {/* Shine overlay - เอฟเฟกต์แสงวาว */}
              <LinearGradient
                colors={['rgba(255,255,255,0.3)', 'rgba(255,255,255,0)']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.shineOverlay}
              />

              {/* Icon Container */}
              <View style={styles.menuIconContainer}>
                <View style={styles.menuIconShadow} />
                <LinearGradient
                  colors={['rgba(255,255,255,0.5)', 'rgba(255,255,255,0.15)']}
                  style={styles.menuIconBox}
                >
                  <Text style={styles.menuEmoji}>{item.icon}</Text>
                </LinearGradient>
              </View>

              {/* Title - แสดงเฉพาะเมื่อไม่มีภาพ */}
              <Text style={styles.menuTitle}>{item.title}</Text>
            </>
          )}

          {/* 3D inner shadow top */}
          <View style={styles.inner3DTop} />
        </LinearGradient>
      </Pressable>
    </Animated.View>
  );
};

// Get greeting by time
const getGreeting = () => {
  const hour = new Date().getHours();
  if (hour < 12) return 'สวัสดีตอนเช้า';
  if (hour < 17) return 'สวัสดีตอนบ่าย';
  return 'สวัสดีตอนเย็น';
};

export default function HomeScreen() {
  const { user, isAuthenticated } = useAuthStore();
  const [refreshing, setRefreshing] = useState(false);
  const [greeting, setGreeting] = useState(getGreeting());
  const [pinnedItems, setPinnedItems] = useState<string[]>([]);

  // Load pinned items from storage
  useEffect(() => {
    const loadPinnedItems = async () => {
      try {
        const saved = await SecureStore.getItemAsync(PINNED_ITEMS_KEY);
        if (saved) {
          setPinnedItems(JSON.parse(saved));
        }
      } catch (error) {
        console.log('Load pinned items error:', error);
      }
    };
    loadPinnedItems();
  }, []);

  // Save pinned items to storage
  const savePinnedItems = async (items: string[]) => {
    try {
      await SecureStore.setItemAsync(PINNED_ITEMS_KEY, JSON.stringify(items));
    } catch (error) {
      console.log('Save pinned items error:', error);
    }
  };

  // Toggle pin item
  const togglePin = useCallback((itemId: string) => {
    setPinnedItems(prev => {
      const newPinned = prev.includes(itemId)
        ? prev.filter(id => id !== itemId)
        : [...prev, itemId];

      // Save to storage
      savePinnedItems(newPinned);

      // Show feedback
      const isPinning = !prev.includes(itemId);
      Alert.alert(
        isPinning ? '📌 ปักหมุดแล้ว' : '📌 ยกเลิกปักหมุด',
        isPinning ? 'เมนูนี้จะแสดงด้านบนสุด' : 'เมนูกลับไปตำแหน่งเดิม',
        [{ text: 'ตกลง' }],
        { cancelable: true }
      );

      return newPinned;
    });
  }, []);

  // Sort menu items - pinned first
  const sortedMenuItems = useMemo(() => {
    return [...MENU_ITEMS].sort((a, b) => {
      const aPinned = pinnedItems.includes(a.id);
      const bPinned = pinnedItems.includes(b.id);
      if (aPinned && !bPinned) return -1;
      if (!aPinned && bPinned) return 1;
      return 0;
    });
  }, [pinnedItems]);

  // Update greeting when screen is focused
  useFocusEffect(
    useCallback(() => {
      setGreeting(getGreeting());
    }, [])
  );

  const onRefresh = async () => {
    setRefreshing(true);
    await new Promise(resolve => setTimeout(resolve, 1000));
    setRefreshing(false);
  };

  const handleMenuPress = (route: string) => {
    router.push(route as any);
  };

  return (
    <View style={styles.container}>
      {/* Gradient Background */}
      <LinearGradient
        colors={['#0F0F23', '#1a1a2e', '#16213e']}
        style={StyleSheet.absoluteFill}
      />

      {/* Fireflies Animation Background */}
      <FirefliesBackground />

      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />

      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor="#3B82F6"
            colors={['#3B82F6']}
          />
        }
      >
        {/* Header */}
        <View style={styles.header}>
          <View style={styles.headerLeft}>
            <Text style={styles.greeting}>{greeting} 👋</Text>
            <Text style={styles.userName}>
              {isAuthenticated ? user?.name || 'ผู้ใช้' : 'ยินดีต้อนรับ'}
            </Text>
          </View>

          <Pressable
            style={styles.profileButton}
            onPress={() => router.push('/(tabs)/profile')}
          >
            <LinearGradient
              colors={['#3B82F6', '#8B5CF6']}
              style={styles.profileGradient}
            >
              <Text style={styles.profileText}>
                {user?.name?.charAt(0).toUpperCase() || 'U'}
              </Text>
            </LinearGradient>
          </Pressable>
        </View>

        {/* ⭐ Banner Carousel จาก Admin */}
        <View style={styles.bannerSection}>
          <BannerCarousel
            position="top"
            height={160}
            autoPlay={true}
            autoPlayInterval={5000}
            showIndicators={true}
            isDark={true}
          />
        </View>

        {/* Balance Card - ปรับปรุง */}
        {isAuthenticated && (
          <View style={styles.balanceCardWrapper}>
            {/* Card glow */}
            <View style={styles.balanceGlow} />

            <LinearGradient
              colors={['#3B82F6', '#1D4ED8', '#1E40AF']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.balanceCard}
            >
              {/* Shine overlay */}
              <LinearGradient
                colors={['rgba(255,255,255,0.2)', 'rgba(255,255,255,0)']}
                start={{ x: 0, y: 0 }}
                end={{ x: 0.5, y: 1 }}
                style={styles.balanceShine}
              />

              <View style={styles.balanceHeader}>
                <View style={styles.balanceIconBox}>
                  <Text style={{ fontSize: 24 }}>💳</Text>
                </View>
                <Text style={styles.balanceLabel}>ยอดเงินคงเหลือ</Text>
              </View>
              <Text style={styles.balanceAmount}>฿0.00</Text>

              <View style={styles.balanceActions}>
                <Pressable
                  style={styles.balanceButton}
                  onPress={() => router.push('/(tabs)/wallet')}
                >
                  <LinearGradient
                    colors={['rgba(255,255,255,0.3)', 'rgba(255,255,255,0.1)']}
                    style={styles.balanceButtonGradient}
                  >
                    <Text style={{ fontSize: 18 }}>➕</Text>
                    <Text style={styles.balanceButtonText}>เติมเงิน</Text>
                  </LinearGradient>
                </Pressable>

                <Pressable
                  style={styles.balanceButton}
                  onPress={() => router.push('/commissions')}
                >
                  <LinearGradient
                    colors={['rgba(255,255,255,0.3)', 'rgba(255,255,255,0.1)']}
                    style={styles.balanceButtonGradient}
                  >
                    <Text style={{ fontSize: 18 }}>💸</Text>
                    <Text style={styles.balanceButtonText}>ถอนเงิน</Text>
                  </LinearGradient>
                </Pressable>
              </View>

              {/* Bottom border shine */}
              <View style={styles.balanceBottomShine} />
            </LinearGradient>
          </View>
        )}

        {/* Quick Stats - ปรับปรุง */}
        {isAuthenticated && (
          <View style={styles.statsRow}>
            {[
              { icon: '👥', value: '0', label: 'สมาชิก', color: '#8B5CF6' },
              { icon: '📈', value: '฿0', label: 'รายได้', color: '#10B981' },
              { icon: '⭐', value: 'Bronze', label: 'ระดับ', color: '#F59E0B' },
            ].map((stat, index) => (
              <View key={index} style={styles.statCard}>
                <View style={[styles.statGlow, { backgroundColor: stat.color }]} />
                <View style={[styles.statIconBox, { backgroundColor: stat.color + '30' }]}>
                  <Text style={{ fontSize: 22 }}>{stat.icon}</Text>
                </View>
                <Text style={styles.statValue}>{stat.value}</Text>
                <Text style={styles.statLabel}>{stat.label}</Text>
              </View>
            ))}
          </View>
        )}

        {/* Menu Section */}
        <View style={styles.menuSection}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>บริการ</Text>
            <Text style={styles.sectionHint}>กดค้างเพื่อปักหมุด 📌</Text>
          </View>
          <View style={styles.menuGrid}>
            {sortedMenuItems.map((item, index) => (
              <MenuCard
                key={item.id}
                item={item}
                index={index}
                onPress={() => handleMenuPress(item.route)}
                isPinned={pinnedItems.includes(item.id)}
                onLongPress={() => togglePin(item.id)}
              />
            ))}
          </View>
        </View>

        {/* Footer */}
        <View style={styles.footer}>
          <Text style={styles.footerText}>
            v{APP_INFO.VERSION} ({APP_INFO.BUILD_DATE})
          </Text>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    paddingBottom: 100,
  },
  bannerSection: {
    marginBottom: 16,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 56,
    paddingBottom: 16,
  },
  headerLeft: {
    flex: 1,
  },
  greeting: {
    fontSize: 14,
    color: '#9CA3AF',
  },
  userName: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 2,
  },
  profileButton: {
    width: 50,
    height: 50,
    borderRadius: 25,
    overflow: 'hidden',
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.2)',
  },
  profileGradient: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileText: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },

  // Balance Card Styles
  balanceCardWrapper: {
    paddingHorizontal: 20,
    marginBottom: 16,
  },
  balanceGlow: {
    position: 'absolute',
    top: 10,
    left: 30,
    right: 30,
    bottom: -10,
    backgroundColor: '#3B82F6',
    borderRadius: 24,
    opacity: 0.3,
  },
  balanceCard: {
    borderRadius: 24,
    padding: 20,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.15)',
  },
  balanceShine: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: '50%',
  },
  balanceHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  balanceIconBox: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 10,
  },
  balanceLabel: {
    fontSize: 15,
    color: 'rgba(255,255,255,0.9)',
    fontWeight: '500',
  },
  balanceAmount: {
    fontSize: 36,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 16,
    letterSpacing: 1,
  },
  balanceActions: {
    flexDirection: 'row',
    gap: 12,
  },
  balanceButton: {
    flex: 1,
    borderRadius: 14,
    overflow: 'hidden',
  },
  balanceButtonGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 12,
    gap: 8,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.2)',
  },
  balanceButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '600',
  },
  balanceBottomShine: {
    position: 'absolute',
    bottom: 0,
    left: 20,
    right: 20,
    height: 1,
    backgroundColor: 'rgba(255,255,255,0.3)',
  },

  // Stats Row Styles
  statsRow: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    marginBottom: 20,
    gap: 8,
  },
  statCard: {
    flex: 1,
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 18,
    padding: 14,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
    overflow: 'hidden',
  },
  statGlow: {
    position: 'absolute',
    top: -20,
    left: '50%',
    width: 40,
    height: 40,
    borderRadius: 20,
    opacity: 0.3,
    marginLeft: -20,
  },
  statIconBox: {
    width: 44,
    height: 44,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  statValue: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  statLabel: {
    fontSize: 11,
    color: '#9CA3AF',
    marginTop: 2,
  },

  // Menu Section Styles
  menuSection: {
    paddingHorizontal: 20,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  sectionHint: {
    fontSize: 12,
    color: '#6B7280',
  },
  menuGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },

  // Menu Card Styles
  menuCardWrapper: {
    width: CARD_WIDTH,
    height: CARD_HEIGHT,
    marginBottom: 14,
  },
  menuGlow: {
    position: 'absolute',
    top: 8,
    left: 8,
    right: 8,
    bottom: -4,
    borderRadius: 22,
    opacity: 0.4,
  },
  menu3DShadow: {
    position: 'absolute',
    bottom: -4,
    left: 4,
    right: 4,
    height: 20,
    borderRadius: 20,
    opacity: 0.4,
  },
  menuCard: {
    flex: 1,
    borderRadius: 22,
    overflow: 'hidden',
    // Shadow for iOS
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.4,
    shadowRadius: 16,
    // Shadow for Android
    elevation: 12,
  },
  menuGradient: {
    flex: 1,
    overflow: 'hidden',
  },
  shineOverlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: '60%',
    zIndex: 1,
  },
  glassBorder: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.25)',
    borderRadius: 22,
    zIndex: 2,
  },
  glassHighlight: {
    position: 'absolute',
    top: 2,
    left: 2,
    right: 2,
    height: '40%',
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    zIndex: 2,
  },
  // Pin indicator styles
  pinIndicator: {
    position: 'absolute',
    top: 8,
    right: 8,
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: 'rgba(255, 255, 255, 0.95)',
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
    elevation: 5,
  },
  pinIcon: {
    fontSize: 14,
  },
  inner3DTop: {
    position: 'absolute',
    top: 2,
    left: 4,
    right: 4,
    height: 20,
    backgroundColor: 'rgba(255,255,255,0.15)',
    borderTopLeftRadius: 18,
    borderTopRightRadius: 18,
    zIndex: 3,
  },
  // รูปภาพเต็มปุ่ม
  fullImageContainer: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    overflow: 'hidden',
    borderRadius: 22,
  },
  fullImage: {
    width: '100%',
    height: '100%',
    borderRadius: 22,
  },
  imageOverlay: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    height: '30%',
  },
  shineEffect: {
    position: 'absolute',
    top: 0,
    bottom: 0,
    width: 60,
  },
  shineGradient: {
    flex: 1,
    transform: [{ skewX: '-20deg' }],
  },
  // Icon Container (เมื่อไม่มีภาพ)
  menuIconContainer: {
    position: 'absolute',
    top: 14,
    left: 14,
    width: 52,
    height: 52,
    zIndex: 4,
  },
  menuIconShadow: {
    position: 'absolute',
    top: 4,
    left: 4,
    width: 48,
    height: 48,
    borderRadius: 16,
    backgroundColor: 'rgba(0,0,0,0.3)',
  },
  menuIconBox: {
    width: 52,
    height: 52,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.35)',
  },
  menuEmoji: {
    fontSize: 28,
    textAlign: 'center',
  },
  menuTitle: {
    position: 'absolute',
    bottom: 14,
    left: 14,
    right: 14,
    fontSize: 15,
    fontWeight: '700',
    color: '#FFFFFF',
    textShadowColor: 'rgba(0,0,0,0.5)',
    textShadowOffset: { width: 0, height: 2 },
    textShadowRadius: 4,
    zIndex: 4,
  },

  // Footer
  footer: {
    alignItems: 'center',
    paddingVertical: 20,
    marginTop: 16,
  },
  footerText: {
    fontSize: 11,
    color: '#6B7280',
  },
});
