/**
 * Home Screen - Premium Design V3
 * ปรับปรุงปุ่มให้สวยงามมากขึ้น
 * - Glassmorphism effect
 * - 3D shadow depth
 * - Glow effects
 * - Smooth animations
 */

import React, { useState, useCallback, useRef, useEffect } from 'react';
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
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';
import { BannerCarousel } from '@/components';

const { width } = Dimensions.get('window');
const CARD_WIDTH = (width - 52) / 2;

// ภาพปุ่มเมนู - ใช้ภาพจาก assets/images
const MENU_IMAGES: Record<string, ImageSourcePropType> = {
  wallet: require('@/assets/images/wallet.png'),
  network: require('@/assets/images/mlm-network.png'),
  shopping: require('@/assets/images/shopping.png'),
  rider: require('@/assets/images/rider.png'),
  settings: require('@/assets/images/setting.png'),
};

// Menu Items - ใช้ภาพถ้ามี ไม่มีใช้ emoji
const MENU_ITEMS = [
  { id: 'wallet', title: 'กระเป๋าเงิน', icon: '💰', colors: ['#10B981', '#059669'], glowColor: '#10B981', route: '/(tabs)/wallet', hasImage: true },
  { id: 'network', title: 'สายงาน', icon: '👥', colors: ['#8B5CF6', '#6D28D9'], glowColor: '#8B5CF6', route: '/(tabs)/network', hasImage: true },
  { id: 'referral', title: 'แนะนำเพื่อน', icon: '🤝', colors: ['#EC4899', '#DB2777'], glowColor: '#EC4899', route: '/referral', hasImage: false },
  { id: 'commissions', title: 'คอมมิชชั่น', icon: '💵', colors: ['#3B82F6', '#2563EB'], glowColor: '#3B82F6', route: '/commissions', hasImage: false },
  { id: 'shopping', title: 'ช้อปปิ้ง', icon: '🛒', colors: ['#F59E0B', '#D97706'], glowColor: '#F59E0B', route: '/shopping', hasImage: true },
  { id: 'rider', title: 'ไรเดอร์', icon: '🚴', colors: ['#06B6D4', '#0891B2'], glowColor: '#06B6D4', route: '/rider', hasImage: true },
  { id: 'wealth-guide', title: 'เส้นทางเศรษฐี', icon: '📚', colors: ['#F97316', '#EA580C'], glowColor: '#F97316', route: '/wealth-guide', hasImage: false },
  { id: 'tarot', title: 'ดูดวง', icon: '🔮', colors: ['#6366F1', '#4F46E5'], glowColor: '#6366F1', route: '/tarot', hasImage: false },
  { id: 'leaderboard', title: 'อันดับ', icon: '🏆', colors: ['#EF4444', '#DC2626'], glowColor: '#EF4444', route: '/leaderboard', hasImage: false },
  { id: 'coming-soon', title: 'เร็วๆ นี้', icon: '🚀', colors: ['#A855F7', '#9333EA'], glowColor: '#A855F7', route: '/coming-soon', hasImage: false },
  { id: 'settings', title: 'ตั้งค่า', icon: '⚙️', colors: ['#64748B', '#475569'], glowColor: '#64748B', route: '/settings', hasImage: true },
] as const;

// Menu Card Component - ปรับปรุงให้สวยงาม
const MenuCard = ({
  item,
  index,
  onPress
}: {
  item: typeof MENU_ITEMS[number];
  index: number;
  onPress: () => void;
}) => {
  const scaleAnim = useRef(new Animated.Value(1)).current;
  const glowAnim = useRef(new Animated.Value(0)).current;

  // Glow animation
  useEffect(() => {
    const animation = Animated.loop(
      Animated.sequence([
        Animated.timing(glowAnim, {
          toValue: 1,
          duration: 2000,
          useNativeDriver: false,
        }),
        Animated.timing(glowAnim, {
          toValue: 0,
          duration: 2000,
          useNativeDriver: false,
        }),
      ])
    );

    // Stagger start based on index
    const timeout = setTimeout(() => animation.start(), index * 200);
    return () => {
      clearTimeout(timeout);
      animation.stop();
    };
  }, []);

  const handlePressIn = () => {
    Animated.spring(scaleAnim, {
      toValue: 0.95,
      useNativeDriver: true,
      speed: 50,
      bounciness: 4,
    }).start();
  };

  const handlePressOut = () => {
    Animated.spring(scaleAnim, {
      toValue: 1,
      useNativeDriver: true,
      speed: 20,
      bounciness: 8,
    }).start();
  };

  const glowOpacity = glowAnim.interpolate({
    inputRange: [0, 1],
    outputRange: [0.3, 0.6],
  });

  return (
    <Animated.View
      style={[
        styles.menuCardWrapper,
        { transform: [{ scale: scaleAnim }] },
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

      <Pressable
        onPress={onPress}
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
          {/* Shine overlay - เอฟเฟกต์แสงวาว */}
          <LinearGradient
            colors={['rgba(255,255,255,0.25)', 'rgba(255,255,255,0)']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.shineOverlay}
          />

          {/* Glass border - ขอบแก้ว */}
          <View style={styles.glassBorder} />

          {/* Icon Container - แสดงภาพถ้ามี ไม่งั้นใช้ emoji */}
          {item.hasImage && MENU_IMAGES[item.id] ? (
            <View style={styles.menuImageContainer}>
              <Image
                source={MENU_IMAGES[item.id]}
                style={styles.menuImage}
                resizeMode="cover"
              />
            </View>
          ) : (
            <View style={styles.menuIconContainer}>
              <View style={styles.menuIconShadow} />
              <LinearGradient
                colors={['rgba(255,255,255,0.4)', 'rgba(255,255,255,0.1)']}
                style={styles.menuIconBox}
              >
                <Text style={styles.menuEmoji}>{item.icon}</Text>
              </LinearGradient>
            </View>
          )}

          {/* Title */}
          <Text style={[styles.menuTitle, item.hasImage && styles.menuTitleWithImage]}>{item.title}</Text>

          {/* Bottom shine line */}
          <View style={styles.bottomShine} />
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
          <Text style={styles.sectionTitle}>บริการ</Text>
          <View style={styles.menuGrid}>
            {MENU_ITEMS.map((item, index) => (
              <MenuCard
                key={item.id}
                item={item}
                index={index}
                onPress={() => handleMenuPress(item.route)}
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
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 16,
  },
  menuGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },

  // Menu Card Styles
  menuCardWrapper: {
    width: CARD_WIDTH,
    height: 120,
    marginBottom: 14,
  },
  menuGlow: {
    position: 'absolute',
    top: 8,
    left: 8,
    right: 8,
    bottom: -4,
    borderRadius: 20,
    opacity: 0.4,
  },
  menuCard: {
    flex: 1,
    borderRadius: 20,
    overflow: 'hidden',
    // Shadow for iOS
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.3,
    shadowRadius: 12,
    // Shadow for Android
    elevation: 8,
  },
  menuGradient: {
    flex: 1,
    padding: 14,
    justifyContent: 'space-between',
    overflow: 'hidden',
  },
  shineOverlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: '60%',
  },
  glassBorder: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.2)',
    borderRadius: 20,
  },
  menuIconContainer: {
    width: 48,
    height: 48,
  },
  menuIconShadow: {
    position: 'absolute',
    top: 4,
    left: 4,
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: 'rgba(0,0,0,0.2)',
  },
  menuIconBox: {
    width: 48,
    height: 48,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.3)',
  },
  menuEmoji: {
    fontSize: 26,
    textAlign: 'center',
  },
  // สไตล์สำหรับปุ่มที่มีภาพ
  menuImageContainer: {
    width: 60,
    height: 60,
    borderRadius: 16,
    overflow: 'hidden',
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.4)',
    backgroundColor: 'rgba(255,255,255,0.2)',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.4,
    shadowRadius: 8,
    elevation: 8,
  },
  menuImage: {
    width: '100%',
    height: '100%',
    borderRadius: 14,
  },
  menuTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: '#FFFFFF',
    textShadowColor: 'rgba(0,0,0,0.3)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 2,
  },
  menuTitleWithImage: {
    marginTop: 4,
  },
  bottomShine: {
    position: 'absolute',
    bottom: 0,
    left: 14,
    right: 14,
    height: 1,
    backgroundColor: 'rgba(255,255,255,0.3)',
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
