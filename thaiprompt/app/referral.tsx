/**
 * Referral Screen - หน้าแนะนำเพื่อน/สมัครต่อสายงานแบบ Premium
 * QR Code, Commission Tiers, Sharing Features
 */

import React, { useEffect, useState, useCallback, useRef } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  Share,
  ActivityIndicator,
  Animated,
  Easing,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router, Stack } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import ReAnimated, { FadeInDown, FadeInUp, ZoomIn } from 'react-native-reanimated';
import * as Clipboard from 'expo-clipboard';
import * as Haptics from 'expo-haptics';
import QRCode from 'react-native-qrcode-svg';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import { getReferralStats } from '@/services/api';
import { formatCurrency } from '@/constants';
import type { ReferralStats } from '@/types';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

// =====================================================
// Commission Tier Type
// =====================================================

interface CommissionTier {
  level: number;
  name: string;
  percentage: string;
  description: string;
  color: string;
  icon: string;
}

// =====================================================
// Stat Card Component
// =====================================================

const StatCard = ({
  icon,
  value,
  label,
  color,
  isDark,
  delay,
}: {
  icon: string;
  value: string;
  label: string;
  color: string;
  isDark: boolean;
  delay: number;
}) => (
  <ReAnimated.View entering={FadeInDown.delay(delay).springify()} className="flex-1 mx-1">
    <View
      className={`p-4 rounded-2xl ${isDark ? 'bg-gray-800' : 'bg-white'}`}
      style={{
        shadowColor: color,
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.15,
        shadowRadius: 12,
        elevation: 5,
      }}
    >
      <View
        className="w-10 h-10 rounded-xl items-center justify-center mb-2"
        style={{ backgroundColor: color + '20' }}
      >
        <Ionicons name={icon as any} size={20} color={color} />
      </View>
      <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
        {value}
      </Text>
      <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>{label}</Text>
    </View>
  </ReAnimated.View>
);

// =====================================================
// QR Code Section Component
// =====================================================

const QRCodeSection = ({
  referralLink,
  referralCode,
  isDark,
  onCopyCode,
}: {
  referralLink: string;
  referralCode: string;
  isDark: boolean;
  onCopyCode: () => void;
}) => {
  // Glow animation
  const glowAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.loop(
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
    ).start();
  }, []);

  const shadowOpacity = glowAnim.interpolate({
    inputRange: [0, 1],
    outputRange: [0.2, 0.5],
  });

  return (
    <ReAnimated.View entering={ZoomIn.delay(300)}>
      <View className={`rounded-3xl overflow-hidden ${isDark ? 'bg-gray-800' : 'bg-white'}`}>
        {/* Gradient Header */}
        <LinearGradient
          colors={['#EC4899', '#BE185D']}
          style={{ paddingHorizontal: 24, paddingVertical: 16 }}
        >
          <Text className="text-white text-lg font-bold text-center">
            🎁 แชร์รหัสนี้ให้เพื่อน
          </Text>
        </LinearGradient>

        <View className="p-6">
          {/* QR Code with Glow */}
          <Animated.View
            style={{
              alignSelf: 'center',
              shadowColor: '#EC4899',
              shadowOffset: { width: 0, height: 8 },
              shadowOpacity,
              shadowRadius: 24,
              elevation: 12,
            }}
          >
            <LinearGradient
              colors={['#EC4899', '#BE185D', '#9D174D']}
              style={{ padding: 4, borderRadius: 24 }}
            >
              <View className="bg-white p-5 rounded-3xl">
                <QRCode
                  value={referralLink}
                  size={180}
                  color="#1F2937"
                  backgroundColor="white"
                  logo={require('@/assets/images/icon.png')}
                  logoSize={40}
                  logoBackgroundColor="white"
                  logoMargin={4}
                />
              </View>
            </LinearGradient>
          </Animated.View>

          {/* Scan instruction */}
          <Text className={`text-center mt-4 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
            สแกน QR Code เพื่อสมัครสมาชิก
          </Text>

          {/* Referral Code Display */}
          <Pressable
            onPress={onCopyCode}
            className={`mt-6 p-4 rounded-2xl flex-row items-center justify-between ${
              isDark ? 'bg-gray-700' : 'bg-pink-50'
            }`}
          >
            <View>
              <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                รหัสแนะนำของคุณ
              </Text>
              <Text className="text-pink-500 font-bold text-2xl tracking-widest">
                {referralCode}
              </Text>
            </View>
            <View className="bg-pink-500 px-4 py-2 rounded-xl flex-row items-center">
              <Ionicons name="copy" size={18} color="white" />
              <Text className="text-white font-semibold ml-2">คัดลอก</Text>
            </View>
          </Pressable>
        </View>
      </View>
    </ReAnimated.View>
  );
};

// =====================================================
// Commission Tier Card
// =====================================================

const CommissionTierCard = ({
  tier,
  index,
  isDark,
}: {
  tier: CommissionTier;
  index: number;
  isDark: boolean;
}) => (
  <ReAnimated.View
    entering={FadeInDown.delay(500 + index * 80)}
    className={`p-4 rounded-2xl mb-3 ${isDark ? 'bg-gray-800' : 'bg-white'}`}
    style={{
      shadowColor: tier.color,
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.1,
      shadowRadius: 8,
      elevation: 3,
    }}
  >
    <View className="flex-row items-center">
      <LinearGradient
        colors={[tier.color, tier.color + 'CC']}
        style={{ width: 48, height: 48, borderRadius: 12, alignItems: 'center', justifyContent: 'center', marginRight: 16 }}
      >
        <Text className="text-white text-lg font-bold">{tier.level}</Text>
      </LinearGradient>
      <View className="flex-1">
        <View className="flex-row items-center">
          <Text className={`font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
            {tier.name}
          </Text>
          <Text className="ml-2">{tier.icon}</Text>
        </View>
        <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
          {tier.description}
        </Text>
      </View>
      <View
        className="px-4 py-2 rounded-xl"
        style={{ backgroundColor: tier.color + '20' }}
      >
        <Text className="font-bold text-lg" style={{ color: tier.color }}>
          {tier.percentage}
        </Text>
      </View>
    </View>
  </ReAnimated.View>
);

// =====================================================
// Share Button Component
// =====================================================

const ShareButton = ({
  icon,
  label,
  color,
  onPress,
}: {
  icon: string;
  label: string;
  color: string;
  onPress: () => void;
}) => (
  <Pressable onPress={onPress} className="items-center flex-1" style={{ maxWidth: 80 }}>
    <LinearGradient
      colors={[color, color + 'CC']}
      style={{
        width: 56,
        height: 56,
        borderRadius: 16,
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: 8,
      }}
    >
      <Ionicons name={icon as any} size={24} color="white" />
    </LinearGradient>
    <Text className="text-gray-500 text-xs text-center">{label}</Text>
  </Pressable>
);

// =====================================================
// Benefit Card
// =====================================================

const BenefitCard = ({
  icon,
  title,
  description,
  delay,
  isDark,
}: {
  icon: string;
  title: string;
  description: string;
  delay: number;
  isDark: boolean;
}) => (
  <ReAnimated.View
    entering={FadeInDown.delay(delay).springify()}
    className="flex-row items-start mb-4"
  >
    <View className="w-10 h-10 rounded-xl bg-pink-100 dark:bg-pink-900/30 items-center justify-center mr-3">
      <Ionicons name={icon as any} size={20} color="#EC4899" />
    </View>
    <View className="flex-1">
      <Text className={`font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
        {title}
      </Text>
      <Text className={`text-sm mt-0.5 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
        {description}
      </Text>
    </View>
  </ReAnimated.View>
);

// =====================================================
// Main Component
// =====================================================

export default function ReferralScreen() {
  const { resolvedTheme } = useAppStore();
  const { isAuthenticated, user } = useAuthStore();
  const isDark = resolvedTheme === 'dark';

  const [stats, setStats] = useState<ReferralStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // สร้าง referral link
  const referralCode = user?.referralCode || user?.referral_code || user?.id?.toString() || 'XXXX';
  const referralLink = `https://main.thaiprompt.online/register?ref=${referralCode}`;

  // Commission tiers
  const commissionTiers: CommissionTier[] = [
    {
      level: 1,
      name: 'สายตรง (Direct)',
      percentage: '10%',
      description: 'จากยอดซื้อของสมาชิกโดยตรง',
      color: '#3B82F6',
      icon: '⭐',
    },
    {
      level: 2,
      name: 'ระดับ 2',
      percentage: '5%',
      description: 'จากยอดซื้อของสมาชิกระดับ 2',
      color: '#8B5CF6',
      icon: '🌟',
    },
    {
      level: 3,
      name: 'ระดับ 3',
      percentage: '3%',
      description: 'จากยอดซื้อของสมาชิกระดับ 3',
      color: '#EC4899',
      icon: '💫',
    },
    {
      level: 4,
      name: 'ระดับ 4',
      percentage: '2%',
      description: 'จากยอดซื้อของสมาชิกระดับ 4',
      color: '#F97316',
      icon: '✨',
    },
    {
      level: 5,
      name: 'ระดับ 5',
      percentage: '1%',
      description: 'จากยอดซื้อของสมาชิกระดับ 5',
      color: '#10B981',
      icon: '🎯',
    },
  ];

  // โหลดข้อมูล stats
  const loadStats = useCallback(async () => {
    if (!isAuthenticated) {
      setIsLoading(false);
      return;
    }
    try {
      setIsLoading(true);
      const data = await getReferralStats();
      if (data) setStats(data);
    } catch (error) {
      console.error('Load referral stats error:', error);
    } finally {
      setIsLoading(false);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    loadStats();
  }, [loadStats]);

  // Copy code
  const handleCopyCode = async () => {
    await Clipboard.setStringAsync(referralCode);
    await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    Alert.alert('คัดลอกแล้ว!', 'รหัสแนะนำถูกคัดลอกไปยังคลิปบอร์ด');
  };

  // Copy link
  const handleCopyLink = async () => {
    await Clipboard.setStringAsync(referralLink);
    await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    Alert.alert('คัดลอกแล้ว!', 'ลิงก์แนะนำถูกคัดลอกไปยังคลิปบอร์ด');
  };

  // Share
  const handleShare = async () => {
    try {
      await Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium);
      const message =
        `🚀 มาสร้างรายได้ไปด้วยกัน!\n\n` +
        `สมัครเป็นสมาชิก Thaiprompt Affiliate วันนี้\n` +
        `รับค่าคอมมิชชั่นสูงถึง 10%!\n\n` +
        `🔗 สมัครเลย: ${referralLink}\n` +
        `หรือใช้รหัส: ${referralCode}`;

      await Share.share({
        message,
        title: 'แนะนำเพื่อน - Thaiprompt Affiliate',
      });
    } catch (error) {
      console.error('Share error:', error);
    }
  };

  // ถ้ายังไม่ login
  if (!isAuthenticated) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <Stack.Screen options={{ headerShown: false }} />
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <LinearGradient
            colors={['#EC4899', '#BE185D']}
            style={{
              width: 112,
              height: 112,
              borderRadius: 56,
              alignItems: 'center',
              justifyContent: 'center',
              marginBottom: 24,
              shadowColor: '#EC4899',
              shadowOffset: { width: 0, height: 8 },
              shadowOpacity: 0.4,
              shadowRadius: 16,
              elevation: 12,
            }}
          >
            <Ionicons name="share-social" size={56} color="white" />
          </LinearGradient>
          <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
            แนะนำเพื่อนรับรายได้
          </Text>
          <Text className="text-gray-500 text-center mt-2 px-8">
            เข้าสู่ระบบเพื่อรับรหัสแนะนำและเริ่มสร้างรายได้จากเครือข่าย
          </Text>
          <Pressable onPress={() => router.push('/login')} className="mt-6">
            <LinearGradient
              colors={['#EC4899', '#BE185D']}
              style={{
                paddingHorizontal: 40,
                paddingVertical: 16,
                borderRadius: 16,
              }}
            >
              <Text className="text-white font-bold text-lg">เข้าสู่ระบบ</Text>
            </LinearGradient>
          </Pressable>
        </SafeAreaView>
      </View>
    );
  }

  return (
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
      <Stack.Screen options={{ headerShown: false }} />

      <SafeAreaView className="flex-1" edges={['top']}>
        {/* Header */}
        <LinearGradient
          colors={isDark ? ['#9D174D', '#831843'] : ['#EC4899', '#BE185D']}
          style={{ paddingHorizontal: 20, paddingTop: 16, paddingBottom: 32 }}
        >
          <View className="flex-row items-center mb-4">
            <Pressable
              onPress={() => router.back()}
              className="w-10 h-10 rounded-full bg-white/20 items-center justify-center mr-3"
            >
              <Ionicons name="arrow-back" size={24} color="white" />
            </Pressable>
            <View className="flex-1">
              <Text className="text-white text-xl font-bold">แนะนำเพื่อน</Text>
              <Text className="text-pink-100 text-sm">สร้างรายได้จากเครือข่าย</Text>
            </View>
          </View>

          <ReAnimated.View entering={FadeInUp}>
            <Text className="text-white text-3xl font-bold text-center">
              รับค่าคอมมิชชั่น
            </Text>
            <Text className="text-white text-3xl font-bold text-center">
              สูงถึง 21%!
            </Text>
            <Text className="text-pink-100 text-center mt-2">
              จาก 5 ระดับลึกในเครือข่ายของคุณ
            </Text>
          </ReAnimated.View>
        </LinearGradient>

        {isLoading ? (
          <View className="flex-1 items-center justify-center">
            <ActivityIndicator size="large" color="#EC4899" />
            <Text className="text-gray-500 mt-4">กำลังโหลด...</Text>
          </View>
        ) : (
          <ScrollView
            className="flex-1"
            showsVerticalScrollIndicator={false}
            contentContainerStyle={{ paddingBottom: 24 }}
          >
            {/* Stats */}
            <View className="flex-row px-3 -mt-4">
              <StatCard
                icon="people"
                value={`${stats?.totalReferrals || 0}`}
                label="สมาชิกทั้งหมด"
                color="#8B5CF6"
                isDark={isDark}
                delay={100}
              />
              <StatCard
                icon="cash"
                value={formatCurrency(stats?.totalEarnings || 0)}
                label="รายได้รวม"
                color="#10B981"
                isDark={isDark}
                delay={150}
              />
            </View>

            {/* QR Code Section */}
            <View className="px-4 mt-6">
              <QRCodeSection
                referralLink={referralLink}
                referralCode={referralCode}
                isDark={isDark}
                onCopyCode={handleCopyCode}
              />
            </View>

            {/* Referral Link */}
            <ReAnimated.View entering={FadeInDown.delay(400)} className="px-4 mt-6">
              <Text className={`font-semibold mb-3 ${isDark ? 'text-white' : 'text-gray-800'}`}>
                ลิงก์แนะนำ
              </Text>
              <Pressable
                onPress={handleCopyLink}
                className={`p-4 rounded-2xl flex-row items-center ${isDark ? 'bg-gray-800' : 'bg-white'}`}
              >
                <Ionicons name="link" size={20} color="#EC4899" />
                <Text
                  className={`flex-1 mx-3 text-sm ${isDark ? 'text-gray-300' : 'text-gray-700'}`}
                  numberOfLines={1}
                >
                  {referralLink}
                </Text>
                <View className="bg-pink-500 px-4 py-2 rounded-xl">
                  <Text className="text-white font-semibold">คัดลอก</Text>
                </View>
              </Pressable>
            </ReAnimated.View>

            {/* Share Options */}
            <ReAnimated.View entering={FadeInDown.delay(450)} className="px-4 mt-6">
              <Text className={`font-semibold mb-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
                แชร์ผ่านช่องทาง
              </Text>
              <View className={`p-6 rounded-2xl flex-row justify-around ${isDark ? 'bg-gray-800' : 'bg-white'}`}>
                <ShareButton icon="chatbubbles" label="LINE" color="#06C755" onPress={handleShare} />
                <ShareButton icon="logo-facebook" label="Facebook" color="#1877F2" onPress={handleShare} />
                <ShareButton icon="mail" label="Email" color="#EA4335" onPress={handleShare} />
                <ShareButton icon="share-social" label="อื่นๆ" color="#6B7280" onPress={handleShare} />
              </View>
            </ReAnimated.View>

            {/* Commission Tiers */}
            <View className="px-4 mt-6">
              <View className="flex-row items-center justify-between mb-4">
                <Text className={`font-semibold ${isDark ? 'text-white' : 'text-gray-800'}`}>
                  โครงสร้างค่าคอมมิชชั่น
                </Text>
                <View className="px-3 py-1 bg-pink-100 dark:bg-pink-900/30 rounded-full">
                  <Text className="text-pink-600 dark:text-pink-400 text-xs font-semibold">
                    รวม 21%
                  </Text>
                </View>
              </View>

              {commissionTiers.map((tier, index) => (
                <CommissionTierCard key={tier.level} tier={tier} index={index} isDark={isDark} />
              ))}
            </View>

            {/* Benefits */}
            <ReAnimated.View entering={FadeInDown.delay(800)} className="px-4 mt-6">
              <LinearGradient
                colors={['#EC4899', '#BE185D']}
                style={{
                  padding: 20,
                  borderRadius: 16,
                }}
              >
                <Text className="text-white font-bold text-lg mb-3">
                  🎁 สิทธิประโยชน์ที่จะได้รับ
                </Text>
                {[
                  'ค่าคอมมิชชั่นจาก 5 ระดับลึก',
                  'โบนัสรายเดือนจากยอดทีม',
                  'โบนัสพิเศษเมื่อทีมถึงเป้าหมาย',
                  'รางวัล Rank สำหรับ Top Performer',
                ].map((benefit, index) => (
                  <View key={index} className="flex-row items-center mb-2">
                    <Ionicons name="checkmark-circle" size={20} color="#A7F3D0" />
                    <Text className="text-pink-100 ml-3">{benefit}</Text>
                  </View>
                ))}
              </LinearGradient>
            </ReAnimated.View>

            {/* CTA Buttons */}
            <View className="px-4 mt-6">
              <ReAnimated.View entering={FadeInUp.delay(900)}>
                <Pressable onPress={handleShare}>
                  <LinearGradient
                    colors={['#10B981', '#059669']}
                    style={{
                      paddingVertical: 20,
                      borderRadius: 16,
                      alignItems: 'center',
                      flexDirection: 'row',
                      justifyContent: 'center',
                      shadowColor: '#10B981',
                      shadowOffset: { width: 0, height: 4 },
                      shadowOpacity: 0.3,
                      shadowRadius: 8,
                      elevation: 6,
                    }}
                  >
                    <Ionicons name="share-social" size={24} color="white" />
                    <Text className="text-white font-bold text-lg ml-2">
                      แชร์ลิงก์แนะนำเพื่อน
                    </Text>
                  </LinearGradient>
                </Pressable>
              </ReAnimated.View>

              <ReAnimated.View entering={FadeInUp.delay(1000)} className="mt-4">
                <Pressable
                  onPress={() => router.push('/mlm-tree')}
                  className={`py-4 rounded-2xl items-center flex-row justify-center border-2 border-pink-500 ${
                    isDark ? 'bg-pink-500/10' : 'bg-pink-50'
                  }`}
                >
                  <Ionicons name="git-network" size={24} color="#EC4899" />
                  <Text className="text-pink-600 dark:text-pink-400 font-bold text-lg ml-2">
                    ดูแผนผังสายงาน
                  </Text>
                </Pressable>
              </ReAnimated.View>
            </View>
          </ScrollView>
        )}
      </SafeAreaView>
    </View>
  );
}
