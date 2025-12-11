/**
 * MainMenu Screen - หน้าเมนูหลัก Premium
 * แสดงหลัง Splash Screen
 * - Lava Lamp animated background
 * - 3D Glassmorphism buttons
 * - Coming Soon badges
 * - FAB buttons (Cart, Help, Wiki)
 * - Premium bottom navigation
 */

import React, { useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Dimensions,
  StyleSheet,
  StatusBar,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import Animated, {
  useSharedValue,
  useAnimatedStyle,
  withSpring,
  withSequence,
  withDelay,
  FadeInUp,
  FadeInDown,
} from 'react-native-reanimated';
import { LavaBackground } from '@/components';
import { useAuthStore } from '@/stores/authStore';

const { width } = Dimensions.get('window');
const BUTTON_SIZE = (width - 60) / 2;

// Menu Items Configuration
const MENU_ITEMS = [
  {
    id: 'shopping',
    title: 'ช้อปปิ้ง',
    subtitle: 'สินค้าและบริการ',
    icon: '🛒',
    route: '/shopping',
    gradient: ['#FF6B6B', '#FF8E53'],
    comingSoon: false,
  },
  {
    id: 'dashboard',
    title: 'แดชบอร์ด',
    subtitle: 'ดูรายได้และสถิติ',
    icon: '📊',
    route: '/dashboard',
    gradient: ['#4ECDC4', '#45B7D1'],
    comingSoon: false,
  },
  {
    id: 'wallet',
    title: 'กระเป๋าเงิน',
    subtitle: 'จัดการเงินของคุณ',
    icon: '💰',
    route: '/(tabs)/wallet',
    gradient: ['#D4AF37', '#B8941E'],
    comingSoon: false,
  },
  {
    id: 'network',
    title: 'เครือข่าย',
    subtitle: 'ดูทีมของคุณ',
    icon: '👥',
    route: '/(tabs)/network',
    gradient: ['#7B2CBF', '#9B4DCA'],
    comingSoon: false,
  },
  {
    id: 'wealth-guide',
    title: 'เส้นทางเศรษฐี',
    subtitle: 'คู่มือสร้างรายได้',
    icon: '📚',
    route: '/wealth-guide',
    gradient: ['#F59E0B', '#D97706'],
    comingSoon: false,
  },
  {
    id: 'tarot',
    title: 'ไพ่ทาโรต์',
    subtitle: 'ดูดวงออนไลน์',
    icon: '🔮',
    route: '/tarot',
    gradient: ['#8B5CF6', '#6D28D9'],
    comingSoon: false,
  },
  {
    id: 'ai-bot',
    title: 'AI Bot',
    subtitle: 'ผู้ช่วยอัจฉริยะ',
    icon: '🤖',
    route: '/coming-soon',
    gradient: ['#667eea', '#764ba2'],
    comingSoon: true,
  },
  {
    id: 'game',
    title: 'เกม',
    subtitle: 'เล่นและรับรางวัล',
    icon: '🎮',
    route: '/coming-soon',
    gradient: ['#f093fb', '#f5576c'],
    comingSoon: true,
  },
];

// 3D Button Component with Glassmorphism
const MenuButton = ({
  item,
  index,
  onPress,
}: {
  item: typeof MENU_ITEMS[0];
  index: number;
  onPress: () => void;
}) => {
  const scale = useSharedValue(1);

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [{ scale: scale.value }],
  }));

  const handlePressIn = () => {
    scale.value = withSpring(0.95);
  };

  const handlePressOut = () => {
    scale.value = withSpring(1);
  };

  return (
    <Animated.View
      entering={FadeInUp.delay(index * 100).springify()}
      style={[styles.buttonWrapper, animatedStyle]}
    >
      <Pressable
        onPress={onPress}
        onPressIn={handlePressIn}
        onPressOut={handlePressOut}
        style={styles.buttonPressable}
      >
        {/* 3D Shadow Layer */}
        <View style={styles.shadowLayer} />

        {/* Main Button */}
        <LinearGradient
          colors={item.gradient as [string, string]}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.buttonGradient}
        >
          {/* Glass Overlay */}
          <View style={styles.glassOverlay} />

          {/* Content */}
          <View style={styles.buttonContent}>
            <Text style={styles.buttonIcon}>{item.icon}</Text>
            <Text style={styles.buttonTitle}>{item.title}</Text>
            <Text style={styles.buttonSubtitle}>{item.subtitle}</Text>
          </View>

          {/* Coming Soon Badge */}
          {item.comingSoon && (
            <View style={styles.comingSoonBadge}>
              <Text style={styles.comingSoonText}>Coming Soon</Text>
            </View>
          )}
        </LinearGradient>
      </Pressable>
    </Animated.View>
  );
};

// FAB Button Component
const FabButton = ({
  icon,
  label,
  onPress,
  color = '#D4AF37',
  badge,
}: {
  icon: string;
  label: string;
  onPress: () => void;
  color?: string;
  badge?: number;
}) => {
  const scale = useSharedValue(1);

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [{ scale: scale.value }],
  }));

  return (
    <Animated.View style={animatedStyle}>
      <Pressable
        onPress={onPress}
        onPressIn={() => {
          scale.value = withSpring(0.9);
        }}
        onPressOut={() => {
          scale.value = withSpring(1);
        }}
        style={[styles.fabButton, { backgroundColor: color }]}
      >
        <Text style={styles.fabIcon}>{icon}</Text>
        {badge !== undefined && badge > 0 && (
          <View style={styles.fabBadge}>
            <Text style={styles.fabBadgeText}>{badge > 99 ? '99+' : badge}</Text>
          </View>
        )}
      </Pressable>
      <Text style={styles.fabLabel}>{label}</Text>
    </Animated.View>
  );
};

// Bottom Nav Button
const NavButton = ({
  emoji,
  label,
  isActive,
  onPress,
}: {
  emoji: string;
  label: string;
  isActive?: boolean;
  onPress: () => void;
}) => (
  <Pressable onPress={onPress} style={styles.navButton}>
    <Text style={{ fontSize: 24, opacity: isActive ? 1 : 0.5 }}>
      {emoji}
    </Text>
    <Text
      style={[
        styles.navLabel,
        { color: isActive ? '#D4AF37' : '#999999' },
      ]}
    >
      {label}
    </Text>
  </Pressable>
);

export default function MainMenuScreen() {
  const { user, isAuthenticated } = useAuthStore();

  const handleMenuPress = (item: typeof MENU_ITEMS[0]) => {
    if (item.comingSoon) {
      router.push('/coming-soon');
    } else {
      router.push(item.route as any);
    }
  };

  const handleFabCart = () => router.push('/shopping');
  const handleFabHelp = () => router.push('/support');
  const handleFabWiki = () => router.push('/wiki');

  const handleNavHome = () => {}; // Already on home
  const handleNavDashboard = () => router.push('/dashboard');
  const handleNavProfile = () => router.push('/(tabs)/profile');
  const handleNavSettings = () => router.push('/settings');

  return (
    <LavaBackground variant="crypto" intensity="high">
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      <View style={styles.container}>
        {/* Header */}
        <Animated.View
          entering={FadeInDown.delay(100).springify()}
          style={[styles.header, { paddingTop: 50 }]}
        >
          <View>
            <Text style={styles.welcomeText}>ยินดีต้อนรับ</Text>
            <Text style={styles.userName}>
              {isAuthenticated ? user?.name : 'Guest'}
            </Text>
          </View>

          {/* Notification Bell */}
          <Pressable
            onPress={() => router.push('/notifications')}
            style={styles.notificationButton}
          >
            <Text style={{ fontSize: 24 }}>🔔</Text>
            <View style={styles.notificationDot} />
          </Pressable>
        </Animated.View>

        {/* Menu Grid */}
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          <View style={styles.menuGrid}>
            {MENU_ITEMS.map((item, index) => (
              <MenuButton
                key={item.id}
                item={item}
                index={index}
                onPress={() => handleMenuPress(item)}
              />
            ))}
          </View>

          {/* Spacer for FAB */}
          <View style={{ height: 120 }} />
        </ScrollView>

        {/* FAB Buttons */}
        <Animated.View
          entering={FadeInUp.delay(600).springify()}
          style={styles.fabContainer}
        >
          <FabButton
            icon="🛒"
            label="ตะกร้า"
            onPress={handleFabCart}
            color="#DC143C"
            badge={3}
          />
          <FabButton
            icon="❓"
            label="ช่วยเหลือ"
            onPress={handleFabHelp}
            color="#7B2CBF"
          />
          <FabButton
            icon="📚"
            label="Wiki"
            onPress={handleFabWiki}
            color="#4ECDC4"
          />
        </Animated.View>

        {/* Premium Bottom Navigation */}
        <Animated.View
          entering={FadeInUp.delay(700).springify()}
          style={styles.bottomNav}
        >
          <LinearGradient
            colors={['rgba(26,26,26,0.95)', 'rgba(42,42,42,0.98)']}
            style={styles.bottomNavGradient}
          >
            <NavButton
              emoji="🏠"
              label="หน้าแรก"
              isActive={true}
              onPress={handleNavHome}
            />
            <NavButton
              emoji="📊"
              label="แดชบอร์ด"
              onPress={handleNavDashboard}
            />
            <NavButton
              emoji="👤"
              label="โปรไฟล์"
              onPress={handleNavProfile}
            />
            <NavButton
              emoji="⚙️"
              label="ตั้งค่า"
              onPress={handleNavSettings}
            />
          </LinearGradient>
        </Animated.View>
      </View>
    </LavaBackground>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 10,
    paddingBottom: 20,
  },
  welcomeText: {
    fontSize: 14,
    color: '#999999',
  },
  userName: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  notificationButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(212, 175, 55, 0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  notificationDot: {
    position: 'absolute',
    top: 8,
    right: 8,
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: '#DC143C',
  },
  scrollContent: {
    paddingHorizontal: 15,
    paddingTop: 10,
  },
  menuGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },
  buttonWrapper: {
    width: BUTTON_SIZE,
    height: BUTTON_SIZE,
    marginBottom: 15,
  },
  buttonPressable: {
    flex: 1,
    position: 'relative',
  },
  shadowLayer: {
    position: 'absolute',
    top: 6,
    left: 6,
    right: -6,
    bottom: -6,
    borderRadius: 24,
    backgroundColor: 'rgba(0,0,0,0.4)',
  },
  buttonGradient: {
    flex: 1,
    borderRadius: 24,
    overflow: 'hidden',
    position: 'relative',
  },
  glassOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(255,255,255,0.15)',
    opacity: 0.5,
  },
  buttonContent: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 15,
  },
  buttonIcon: {
    fontSize: 40,
    marginBottom: 10,
  },
  buttonTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
    textAlign: 'center',
    textShadowColor: 'rgba(0,0,0,0.3)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 2,
  },
  buttonSubtitle: {
    fontSize: 11,
    color: 'rgba(255,255,255,0.8)',
    textAlign: 'center',
    marginTop: 4,
  },
  comingSoonBadge: {
    position: 'absolute',
    top: 10,
    right: 10,
    backgroundColor: 'rgba(0,0,0,0.6)',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 10,
  },
  comingSoonText: {
    fontSize: 9,
    color: '#FFD700',
    fontWeight: 'bold',
  },
  fabContainer: {
    position: 'absolute',
    right: 15,
    bottom: 100,
    alignItems: 'center',
    gap: 12,
  },
  fabButton: {
    width: 56,
    height: 56,
    borderRadius: 28,
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 8,
  },
  fabIcon: {
    fontSize: 24,
  },
  fabBadge: {
    position: 'absolute',
    top: -5,
    right: -5,
    minWidth: 20,
    height: 20,
    borderRadius: 10,
    backgroundColor: '#FFD700',
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 5,
  },
  fabBadgeText: {
    fontSize: 10,
    fontWeight: 'bold',
    color: '#000000',
  },
  fabLabel: {
    fontSize: 10,
    color: '#FFFFFF',
    textAlign: 'center',
    marginTop: 4,
  },
  bottomNav: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
  },
  bottomNavGradient: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'center',
    paddingVertical: 12,
    paddingBottom: 25,
    borderTopLeftRadius: 25,
    borderTopRightRadius: 25,
    borderTopWidth: 1,
    borderColor: 'rgba(212, 175, 55, 0.3)',
  },
  navButton: {
    alignItems: 'center',
    paddingHorizontal: 20,
  },
  navLabel: {
    fontSize: 10,
    marginTop: 4,
  },
});
