/**
 * Home Screen - Enhanced Version with Firefly Effects
 *
 * Features:
 * - Firefly Glow Effects (หิ่งห้อยเรืองแสงฟุ้ง)
 * - LinearGradient backgrounds
 * - Smooth animations with react-native-reanimated
 * - Dark/Light mode support
 */

import React, { useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  StatusBar,
  Dimensions,
} from 'react-native';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import Animated, {
  FadeInDown,
  FadeIn,
  useAnimatedStyle,
  useSharedValue,
  withRepeat,
  withTiming,
  withSequence,
  withDelay,
  Easing,
} from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';

const { width } = Dimensions.get('window');

// =====================================================
// Firefly Glow Components (หิ่งห้อยเรืองแสงฟุ้ง)
// =====================================================

// Firefly Orb - จุดแสงหิ่งห้อยที่กระพริบ
const FireflyOrb = ({
  size = 8,
  color = '#FBBF24',
  delay = 0,
  duration = 2000,
  style,
}: {
  size?: number;
  color?: string;
  delay?: number;
  duration?: number;
  style?: any;
}) => {
  const opacity = useSharedValue(0.3);
  const scale = useSharedValue(0.8);

  useEffect(() => {
    // กระพริบแบบหิ่งห้อย
    opacity.value = withDelay(
      delay,
      withRepeat(
        withSequence(
          withTiming(1, { duration: duration * 0.4, easing: Easing.ease }),
          withTiming(0.2, { duration: duration * 0.6, easing: Easing.ease })
        ),
        -1,
        false
      )
    );

    scale.value = withDelay(
      delay,
      withRepeat(
        withSequence(
          withTiming(1.3, { duration: duration * 0.4, easing: Easing.ease }),
          withTiming(0.7, { duration: duration * 0.6, easing: Easing.ease })
        ),
        -1,
        false
      )
    );
  }, []);

  const animatedStyle = useAnimatedStyle(() => ({
    opacity: opacity.value,
    transform: [{ scale: scale.value }],
  }));

  return (
    <Animated.View
      style={[
        {
          position: 'absolute',
          width: size,
          height: size,
          borderRadius: size / 2,
          backgroundColor: color,
          shadowColor: color,
          shadowOffset: { width: 0, height: 0 },
          shadowOpacity: 0.9,
          shadowRadius: size * 2.5,
          elevation: 8,
        },
        style,
        animatedStyle,
      ]}
    />
  );
};

// Glow Container - กล่องที่มีแสงเรืองรอบๆ
const GlowContainer = ({
  children,
  glowColor = '#3B82F6',
  intensity = 20,
  style,
}: {
  children: React.ReactNode;
  glowColor?: string;
  intensity?: number;
  style?: any;
}) => (
  <View
    style={[
      {
        shadowColor: glowColor,
        shadowOffset: { width: 0, height: 0 },
        shadowOpacity: 0.5,
        shadowRadius: intensity,
        elevation: 10,
      },
      style,
    ]}
  >
    {children}
  </View>
);

// Menu Item with Firefly Effect
const MenuItemCard = ({
  icon,
  title,
  subtitle,
  onPress,
  index,
  gradientColors,
  glowColor,
  isDark,
}: {
  icon: string;
  title: string;
  subtitle?: string;
  onPress: () => void;
  index: number;
  gradientColors: string[];
  glowColor: string;
  isDark: boolean;
}) => (
  <Animated.View
    entering={FadeInDown.delay(100 + index * 80).springify()}
    style={{ width: (width - 48) / 2, marginBottom: 16 }}
  >
    <GlowContainer glowColor={glowColor} intensity={15}>
      <Pressable
        onPress={onPress}
        style={({ pressed }) => ({ opacity: pressed ? 0.9 : 1 })}
      >
        <LinearGradient
          colors={gradientColors}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={{
            borderRadius: 20,
            padding: 16,
            minHeight: 140,
            position: 'relative',
            overflow: 'hidden',
          }}
        >
          {/* Firefly Orbs */}
          <FireflyOrb
            size={5}
            color="#FBBF24"
            delay={index * 200}
            duration={2500}
            style={{ top: 10, right: 15 }}
          />
          <FireflyOrb
            size={4}
            color="#FDE68A"
            delay={index * 200 + 500}
            duration={3000}
            style={{ bottom: 20, right: 30 }}
          />
          <FireflyOrb
            size={3}
            color="#F59E0B"
            delay={index * 200 + 800}
            duration={2800}
            style={{ top: 40, right: 50 }}
          />

          {/* Icon */}
          <View
            style={{
              width: 50,
              height: 50,
              borderRadius: 15,
              backgroundColor: 'rgba(255,255,255,0.25)',
              alignItems: 'center',
              justifyContent: 'center',
              marginBottom: 12,
            }}
          >
            <Ionicons name={icon as any} size={26} color="white" />
          </View>

          {/* Title */}
          <Text
            style={{
              color: 'white',
              fontSize: 16,
              fontWeight: '700',
              marginBottom: 4,
            }}
          >
            {title}
          </Text>

          {/* Subtitle */}
          {subtitle && (
            <Text
              style={{
                color: 'rgba(255,255,255,0.8)',
                fontSize: 12,
              }}
            >
              {subtitle}
            </Text>
          )}
        </LinearGradient>
      </Pressable>
    </GlowContainer>
  </Animated.View>
);

// Welcome Card with Firefly Effects
const WelcomeCard = ({
  userName,
  isAuthenticated,
  isDark,
}: {
  userName: string;
  isAuthenticated: boolean;
  isDark: boolean;
}) => (
  <Animated.View entering={FadeIn.delay(50).springify()} style={{ marginBottom: 24 }}>
    <GlowContainer
      glowColor={isDark ? '#60A5FA' : '#3B82F6'}
      intensity={25}
    >
      <LinearGradient
        colors={isDark ? ['#1E3A8A', '#312E81'] : ['#3B82F6', '#8B5CF6']}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={{
          borderRadius: 24,
          padding: 20,
          position: 'relative',
          overflow: 'hidden',
        }}
      >
        {/* Firefly Orbs - หิ่งห้อยกระพริบ */}
        <FireflyOrb size={8} color="#FBBF24" delay={0} duration={2500} style={{ top: 15, right: 20 }} />
        <FireflyOrb size={10} color="#F59E0B" delay={400} duration={3000} style={{ top: 50, right: 60 }} />
        <FireflyOrb size={6} color="#FCD34D" delay={800} duration={2800} style={{ top: 30, right: 120 }} />
        <FireflyOrb size={7} color="#FBBF24" delay={1200} duration={2200} style={{ bottom: 25, right: 40 }} />
        <FireflyOrb size={5} color="#FDE68A" delay={600} duration={3200} style={{ bottom: 40, right: 100 }} />
        <FireflyOrb size={6} color="#F59E0B" delay={1000} duration={2600} style={{ top: 70, left: 30 }} />
        <FireflyOrb size={4} color="#FCD34D" delay={1400} duration={2400} style={{ bottom: 20, left: 60 }} />

        {/* Blur Overlay for soft glow */}
        <View
          style={{
            position: 'absolute',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            backgroundColor: 'rgba(251, 191, 36, 0.03)',
            borderRadius: 24,
          }}
        />

        {/* Content */}
        <View style={{ flexDirection: 'row', alignItems: 'center' }}>
          {/* Avatar with Glow */}
          <GlowContainer glowColor="#FBBF24" intensity={12}>
            <View
              style={{
                width: 64,
                height: 64,
                borderRadius: 20,
                backgroundColor: 'rgba(255,255,255,0.25)',
                alignItems: 'center',
                justifyContent: 'center',
                marginRight: 16,
              }}
            >
              <Text style={{ fontSize: 32 }}>
                {isAuthenticated ? '👋' : '🌟'}
              </Text>
            </View>
          </GlowContainer>

          {/* Text */}
          <View style={{ flex: 1 }}>
            <Text style={{ color: 'rgba(255,255,255,0.8)', fontSize: 14, marginBottom: 4 }}>
              {isAuthenticated ? 'สวัสดี!' : 'ยินดีต้อนรับ'}
            </Text>
            <Text style={{ color: 'white', fontSize: 22, fontWeight: '700' }}>
              {userName}
            </Text>
            <View style={{ flexDirection: 'row', alignItems: 'center', marginTop: 8 }}>
              <View
                style={{
                  backgroundColor: isAuthenticated ? 'rgba(34, 197, 94, 0.3)' : 'rgba(251, 191, 36, 0.3)',
                  paddingHorizontal: 10,
                  paddingVertical: 4,
                  borderRadius: 12,
                  flexDirection: 'row',
                  alignItems: 'center',
                }}
              >
                <Ionicons
                  name={isAuthenticated ? 'checkmark-circle' : 'information-circle'}
                  size={14}
                  color={isAuthenticated ? '#22C55E' : '#FBBF24'}
                />
                <Text
                  style={{
                    color: isAuthenticated ? '#22C55E' : '#FBBF24',
                    fontSize: 12,
                    fontWeight: '600',
                    marginLeft: 4,
                  }}
                >
                  {isAuthenticated ? 'เข้าสู่ระบบแล้ว' : 'ยังไม่ได้เข้าสู่ระบบ'}
                </Text>
              </View>
            </View>
          </View>
        </View>
      </LinearGradient>
    </GlowContainer>
  </Animated.View>
);

// Quick Action Button
const QuickActionButton = ({
  icon,
  label,
  onPress,
  index,
  isDark,
}: {
  icon: string;
  label: string;
  onPress: () => void;
  index: number;
  isDark: boolean;
}) => (
  <Animated.View entering={FadeInDown.delay(300 + index * 60).springify()}>
    <Pressable
      onPress={onPress}
      style={({ pressed }) => ({
        opacity: pressed ? 0.8 : 1,
        alignItems: 'center',
        width: 70,
      })}
    >
      <GlowContainer glowColor={isDark ? '#60A5FA' : '#3B82F6'} intensity={8}>
        <View
          style={{
            width: 56,
            height: 56,
            borderRadius: 16,
            backgroundColor: isDark ? '#1E293B' : '#FFFFFF',
            alignItems: 'center',
            justifyContent: 'center',
            marginBottom: 8,
            borderWidth: 1,
            borderColor: isDark ? '#334155' : '#E5E7EB',
          }}
        >
          <Ionicons
            name={icon as any}
            size={24}
            color={isDark ? '#60A5FA' : '#3B82F6'}
          />
        </View>
      </GlowContainer>
      <Text
        style={{
          color: isDark ? '#9CA3AF' : '#6B7280',
          fontSize: 11,
          fontWeight: '500',
          textAlign: 'center',
        }}
      >
        {label}
      </Text>
    </Pressable>
  </Animated.View>
);

export default function HomeScreen() {
  const { user, isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  // Menu items configuration
  const menuItems = [
    {
      icon: 'wallet-outline',
      title: 'กระเป๋าเงิน',
      subtitle: 'ยอดเงิน & ธุรกรรม',
      route: '/(tabs)/wallet',
      gradientColors: isDark ? ['#1E3A8A', '#1E40AF'] : ['#3B82F6', '#2563EB'],
      glowColor: '#3B82F6',
    },
    {
      icon: 'people-outline',
      title: 'สายงาน',
      subtitle: 'เครือข่ายของคุณ',
      route: '/(tabs)/network',
      gradientColors: isDark ? ['#166534', '#15803D'] : ['#22C55E', '#16A34A'],
      glowColor: '#22C55E',
    },
    {
      icon: 'person-outline',
      title: 'โปรไฟล์',
      subtitle: 'ข้อมูลส่วนตัว',
      route: '/(tabs)/profile',
      gradientColors: isDark ? ['#7C2D12', '#9A3412'] : ['#F97316', '#EA580C'],
      glowColor: '#F97316',
    },
    {
      icon: 'cart-outline',
      title: 'ช้อปปิ้ง',
      subtitle: 'ร้านพรีเมี่ยม',
      route: '/shopping',
      gradientColors: isDark ? ['#581C87', '#6B21A8'] : ['#A855F7', '#9333EA'],
      glowColor: '#A855F7',
    },
  ];

  // Quick actions
  const quickActions = [
    { icon: 'school-outline', label: 'Academy', route: '/academy' },
    { icon: 'play-circle-outline', label: 'Watch', route: '/watch-earn' },
    { icon: 'sparkles-outline', label: 'Tarot', route: '/tarot' },
    { icon: 'diamond-outline', label: 'TPIX', route: '/tpix' },
  ];

  return (
    <View style={{ flex: 1, backgroundColor: isDark ? '#0F172A' : '#F3F4F6' }}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />

      <ScrollView
        style={{ flex: 1 }}
        contentContainerStyle={{ padding: 16, paddingTop: 60, paddingBottom: 100 }}
        showsVerticalScrollIndicator={false}
      >
        {/* Welcome Card with Fireflies */}
        <WelcomeCard
          userName={isAuthenticated ? user?.name || 'ผู้ใช้' : 'Thaiprompt'}
          isAuthenticated={isAuthenticated}
          isDark={isDark}
        />

        {/* Quick Actions */}
        <Animated.View entering={FadeIn.delay(200)} style={{ marginBottom: 24 }}>
          <Text
            style={{
              fontSize: 16,
              fontWeight: '700',
              color: isDark ? '#FFFFFF' : '#1F2937',
              marginBottom: 16,
            }}
          >
            ⚡ ทางลัด
          </Text>
          <View
            style={{
              flexDirection: 'row',
              justifyContent: 'space-between',
              paddingHorizontal: 8,
            }}
          >
            {quickActions.map((action, index) => (
              <QuickActionButton
                key={action.route}
                icon={action.icon}
                label={action.label}
                onPress={() => router.push(action.route as any)}
                index={index}
                isDark={isDark}
              />
            ))}
          </View>
        </Animated.View>

        {/* Main Menu Grid */}
        <Animated.View entering={FadeIn.delay(100)}>
          <Text
            style={{
              fontSize: 16,
              fontWeight: '700',
              color: isDark ? '#FFFFFF' : '#1F2937',
              marginBottom: 16,
            }}
          >
            🚀 เมนูหลัก
          </Text>
          <View
            style={{
              flexDirection: 'row',
              flexWrap: 'wrap',
              justifyContent: 'space-between',
            }}
          >
            {menuItems.map((item, index) => (
              <MenuItemCard
                key={item.route}
                icon={item.icon}
                title={item.title}
                subtitle={item.subtitle}
                onPress={() => router.push(item.route as any)}
                index={index}
                gradientColors={item.gradientColors}
                glowColor={item.glowColor}
                isDark={isDark}
              />
            ))}
          </View>
        </Animated.View>

        {/* Bottom Info Card with Fireflies */}
        <Animated.View entering={FadeInDown.delay(500).springify()}>
          <GlowContainer glowColor="#FBBF24" intensity={15}>
            <LinearGradient
              colors={isDark ? ['#78350F', '#92400E'] : ['#FBBF24', '#F59E0B']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={{
                borderRadius: 20,
                padding: 16,
                position: 'relative',
                overflow: 'hidden',
              }}
            >
              {/* Fireflies */}
              <FireflyOrb size={5} color="#FFF" delay={0} duration={2000} style={{ top: 10, right: 20 }} />
              <FireflyOrb size={4} color="#FEF3C7" delay={300} duration={2500} style={{ top: 25, right: 60 }} />
              <FireflyOrb size={6} color="#FFF" delay={600} duration={2200} style={{ bottom: 15, right: 40 }} />

              <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                <View
                  style={{
                    width: 48,
                    height: 48,
                    borderRadius: 14,
                    backgroundColor: 'rgba(255,255,255,0.25)',
                    alignItems: 'center',
                    justifyContent: 'center',
                    marginRight: 12,
                  }}
                >
                  <Text style={{ fontSize: 24 }}>💡</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={{ color: isDark ? '#FFFFFF' : '#1F2937', fontWeight: '700', fontSize: 14 }}>
                    เคล็ดลับ
                  </Text>
                  <Text style={{ color: isDark ? 'rgba(255,255,255,0.8)' : 'rgba(0,0,0,0.7)', fontSize: 12 }}>
                    แชร์ลิงก์สินค้าเพื่อรับคอมมิชชั่น + PV ทุกการขาย!
                  </Text>
                </View>
              </View>
            </LinearGradient>
          </GlowContainer>
        </Animated.View>
      </ScrollView>
    </View>
  );
}
