/**
 * Referral Screen - หน้าแนะนำเพื่อน
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router, Stack } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import Animated, { FadeInDown, FadeInUp } from 'react-native-reanimated';
import * as Clipboard from 'expo-clipboard';
import * as Sharing from 'expo-sharing';
import * as Haptics from 'expo-haptics';
import QRCode from 'react-native-qrcode-svg';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import { getReferralStats } from '@/services/api';
import { formatCurrency } from '@/constants';
import type { ReferralStats } from '@/types';

// Benefit Card
const BenefitCard = ({
  icon,
  title,
  description,
  delay,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  title: string;
  description: string;
  delay: number;
}) => (
  <Animated.View
    entering={FadeInDown.delay(delay).springify()}
    className="flex-row items-start mb-4"
  >
    <View className="w-10 h-10 rounded-xl bg-pink-100 dark:bg-pink-900/30 items-center justify-center mr-3">
      <Ionicons name={icon} size={20} color="#EC4899" />
    </View>
    <View className="flex-1">
      <Text className="text-gray-900 dark:text-white font-semibold">
        {title}
      </Text>
      <Text className="text-gray-500 dark:text-gray-400 text-sm mt-0.5">
        {description}
      </Text>
    </View>
  </Animated.View>
);

// Step Item
const StepItem = ({
  number,
  title,
  description,
  delay,
}: {
  number: number;
  title: string;
  description: string;
  delay: number;
}) => (
  <Animated.View
    entering={FadeInDown.delay(delay).springify()}
    className="flex-row items-start mb-4"
  >
    <View className="w-8 h-8 rounded-full bg-primary-500 items-center justify-center mr-3">
      <Text className="text-white font-bold">{number}</Text>
    </View>
    <View className="flex-1">
      <Text className="text-gray-900 dark:text-white font-semibold">
        {title}
      </Text>
      <Text className="text-gray-500 dark:text-gray-400 text-sm mt-0.5">
        {description}
      </Text>
    </View>
  </Animated.View>
);

export default function ReferralScreen() {
  const { resolvedTheme } = useAppStore();
  const { isAuthenticated, user } = useAuthStore();
  const isDark = resolvedTheme === 'dark';

  const [stats, setStats] = useState<ReferralStats | null>(null);

  // สร้าง referral link
  const referralCode = user?.referral_code || user?.id?.toString() || '';
  const referralLink = `https://thaiprompt.com/register?ref=${referralCode}`;

  // โหลดข้อมูล stats
  const loadStats = useCallback(async () => {
    if (!isAuthenticated) return;
    try {
      const data = await getReferralStats();
      if (data) setStats(data);
    } catch (error) {
      console.error('Load referral stats error:', error);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    loadStats();
  }, [loadStats]);

  // Copy link
  const handleCopyLink = async () => {
    await Clipboard.setStringAsync(referralLink);
    await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    Alert.alert('คัดลอกแล้ว!', 'ลิงก์แนะนำถูกคัดลอกไปยังคลิปบอร์ด');
  };

  // Copy code
  const handleCopyCode = async () => {
    await Clipboard.setStringAsync(referralCode);
    await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    Alert.alert('คัดลอกแล้ว!', 'รหัสแนะนำถูกคัดลอกไปยังคลิปบอร์ด');
  };

  // Share
  const handleShare = async () => {
    try {
      const shareMessage = `สมัครสมาชิก Thaiprompt Affiliate เพื่อรับสิทธิประโยชน์มากมาย!\n\nใช้รหัสแนะนำ: ${referralCode}\nหรือสมัครผ่านลิงก์: ${referralLink}`;

      if (await Sharing.isAvailableAsync()) {
        await Sharing.shareAsync(referralLink, {
          dialogTitle: 'แชร์ลิงก์แนะนำเพื่อน',
        });
      } else {
        await Clipboard.setStringAsync(shareMessage);
        Alert.alert('คัดลอกข้อความแล้ว!', 'ข้อความพร้อมแชร์ถูกคัดลอกไปยังคลิปบอร์ด');
      }
      await Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium);
    } catch (error) {
      console.error('Share error:', error);
    }
  };

  // ถ้ายังไม่ login
  if (!isAuthenticated) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <Stack.Screen
          options={{
            headerShown: true,
            title: 'แนะนำเพื่อน',
            headerStyle: { backgroundColor: isDark ? '#0F172A' : '#FFFFFF' },
            headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
          }}
        />
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <Ionicons
            name="share-social"
            size={80}
            color={isDark ? '#4B5563' : '#9CA3AF'}
          />
          <Text
            className={`text-xl font-bold mt-4 ${
              isDark ? 'text-white' : 'text-gray-800'
            }`}
          >
            แนะนำเพื่อนรับรายได้
          </Text>
          <Text className="text-gray-500 text-center mt-2">
            เข้าสู่ระบบเพื่อรับลิงก์แนะนำเพื่อนของคุณ
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
      <Stack.Screen
        options={{
          headerShown: true,
          title: 'แนะนำเพื่อน',
          headerStyle: { backgroundColor: isDark ? '#0F172A' : '#FFFFFF' },
          headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
          headerLeft: () => (
            <Pressable onPress={() => router.back()} className="p-2 -ml-2">
              <Ionicons
                name="arrow-back"
                size={24}
                color={isDark ? '#FFFFFF' : '#1F2937'}
              />
            </Pressable>
          ),
        }}
      />

      <SafeAreaView className="flex-1" edges={['bottom']}>
        <ScrollView
          className="flex-1"
          showsVerticalScrollIndicator={false}
          contentContainerStyle={{ paddingBottom: 24 }}
        >
          {/* Header Banner */}
          <Animated.View entering={FadeInUp.springify()} className="px-4 pt-4">
            <LinearGradient
              colors={['#EC4899', '#BE185D']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              className="rounded-3xl p-6"
            >
              <Text className="text-white text-2xl font-bold text-center">
                แนะนำเพื่อน
              </Text>
              <Text className="text-white text-2xl font-bold text-center">
                รับรายได้ไม่จำกัด!
              </Text>
              <Text className="text-pink-100 text-center mt-2">
                ทุกครั้งที่เพื่อนซื้อสินค้า คุณจะได้รับคอมมิชชั่น
              </Text>
            </LinearGradient>
          </Animated.View>

          {/* Stats */}
          {stats && (
            <View className="flex-row px-4 mt-4 -mx-1">
              <Animated.View
                entering={FadeInDown.delay(100).springify()}
                className="flex-1 mx-1"
              >
                <View className="bg-white dark:bg-dark-50 rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
                  <Text className="text-gray-500 dark:text-gray-400 text-xs">
                    แนะนำแล้ว
                  </Text>
                  <Text className="text-gray-900 dark:text-white font-bold text-xl mt-1">
                    {stats.totalReferrals || 0} คน
                  </Text>
                </View>
              </Animated.View>
              <Animated.View
                entering={FadeInDown.delay(150).springify()}
                className="flex-1 mx-1"
              >
                <View className="bg-white dark:bg-dark-50 rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
                  <Text className="text-gray-500 dark:text-gray-400 text-xs">
                    รายได้จากการแนะนำ
                  </Text>
                  <Text className="text-green-500 font-bold text-xl mt-1">
                    {formatCurrency(stats.totalEarnings || 0)}
                  </Text>
                </View>
              </Animated.View>
            </View>
          )}

          {/* QR Code Card */}
          <Animated.View
            entering={FadeInDown.delay(200).springify()}
            className="mx-4 mt-4"
          >
            <View className="bg-white dark:bg-dark-50 rounded-3xl p-6 border border-gray-100 dark:border-gray-700">
              <Text
                className={`text-center font-bold text-lg mb-4 ${
                  isDark ? 'text-white' : 'text-gray-900'
                }`}
              >
                QR Code ของคุณ
              </Text>

              {/* QR Code */}
              <View className="items-center p-4 bg-white rounded-2xl">
                <QRCode
                  value={referralLink}
                  size={180}
                  color="#1F2937"
                  backgroundColor="white"
                />
              </View>

              {/* Referral Code */}
              <View className="mt-4">
                <Text className="text-gray-500 dark:text-gray-400 text-sm text-center">
                  รหัสแนะนำของคุณ
                </Text>
                <Pressable
                  onPress={handleCopyCode}
                  className="flex-row items-center justify-center mt-2 bg-gray-100 dark:bg-gray-800 rounded-xl px-4 py-3"
                >
                  <Text className="text-primary-500 font-bold text-xl">
                    {referralCode}
                  </Text>
                  <Ionicons
                    name="copy-outline"
                    size={20}
                    color="#3B82F6"
                    style={{ marginLeft: 8 }}
                  />
                </Pressable>
              </View>

              {/* Action Buttons */}
              <View className="flex-row mt-4 -mx-1">
                <Pressable
                  onPress={handleCopyLink}
                  className="flex-1 mx-1 bg-primary-500 rounded-xl py-3 flex-row items-center justify-center"
                  style={({ pressed }) => ({ opacity: pressed ? 0.8 : 1 })}
                >
                  <Ionicons name="copy" size={18} color="white" />
                  <Text className="text-white font-semibold ml-2">
                    คัดลอกลิงก์
                  </Text>
                </Pressable>
                <Pressable
                  onPress={handleShare}
                  className="flex-1 mx-1 bg-pink-500 rounded-xl py-3 flex-row items-center justify-center"
                  style={({ pressed }) => ({ opacity: pressed ? 0.8 : 1 })}
                >
                  <Ionicons name="share-social" size={18} color="white" />
                  <Text className="text-white font-semibold ml-2">แชร์</Text>
                </Pressable>
              </View>
            </View>
          </Animated.View>

          {/* Benefits */}
          <Animated.View
            entering={FadeInDown.delay(300).springify()}
            className="mx-4 mt-6"
          >
            <Text
              className={`text-lg font-bold mb-4 ${
                isDark ? 'text-white' : 'text-gray-900'
              }`}
            >
              สิทธิประโยชน์ที่จะได้รับ
            </Text>

            <View className="bg-white dark:bg-dark-50 rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
              <BenefitCard
                icon="cash"
                title="รับค่าคอมมิชชั่นตลอดชีพ"
                description="เมื่อเพื่อนที่คุณแนะนำซื้อสินค้า คุณจะได้รับค่าคอมมิชชั่น"
                delay={350}
              />
              <BenefitCard
                icon="people"
                title="สร้างทีมไม่จำกัด"
                description="ยิ่งมีทีมมากขึ้น ยิ่งมีรายได้มากขึ้น"
                delay={400}
              />
              <BenefitCard
                icon="trophy"
                title="โบนัสพิเศษ"
                description="รับโบนัสพิเศษเมื่อทำยอดถึงเป้า"
                delay={450}
              />
              <BenefitCard
                icon="gift"
                title="ของรางวัล Rank"
                description="เลื่อนระดับและรับของรางวัลสุดพิเศษ"
                delay={500}
              />
            </View>
          </Animated.View>

          {/* How to */}
          <Animated.View
            entering={FadeInDown.delay(400).springify()}
            className="mx-4 mt-6"
          >
            <Text
              className={`text-lg font-bold mb-4 ${
                isDark ? 'text-white' : 'text-gray-900'
              }`}
            >
              วิธีแนะนำเพื่อน
            </Text>

            <View className="bg-white dark:bg-dark-50 rounded-2xl p-4 border border-gray-100 dark:border-gray-700">
              <StepItem
                number={1}
                title="แชร์ลิงก์หรือ QR Code"
                description="แชร์ลิงก์แนะนำไปยังเพื่อนผ่าน Line, Facebook หรือช่องทางอื่นๆ"
                delay={550}
              />
              <StepItem
                number={2}
                title="เพื่อนสมัครสมาชิก"
                description="เพื่อนของคุณสมัครสมาชิกผ่านลิงก์หรือกรอกรหัสแนะนำ"
                delay={600}
              />
              <StepItem
                number={3}
                title="รับคอมมิชชั่น"
                description="เมื่อเพื่อนซื้อสินค้า คุณจะได้รับค่าคอมมิชชั่นทันที"
                delay={650}
              />
            </View>
          </Animated.View>

          {/* View Network Button */}
          <Animated.View
            entering={FadeInDown.delay(500).springify()}
            className="mx-4 mt-6"
          >
            <Pressable
              onPress={() => router.push('/(tabs)/network')}
              className="bg-gradient-to-r from-purple-500 to-indigo-500 rounded-2xl p-4 flex-row items-center"
            >
              <LinearGradient
                colors={['#8B5CF6', '#6366F1']}
                className="rounded-2xl p-4 flex-row items-center flex-1"
              >
                <View className="w-12 h-12 rounded-xl bg-white/20 items-center justify-center mr-3">
                  <Ionicons name="git-network" size={24} color="white" />
                </View>
                <View className="flex-1">
                  <Text className="text-white font-bold">ดูสายงานของคุณ</Text>
                  <Text className="text-purple-200 text-sm">
                    ดูรายชื่อสมาชิกและทีมทั้งหมด
                  </Text>
                </View>
                <Ionicons name="chevron-forward" size={24} color="white" />
              </LinearGradient>
            </Pressable>
          </Animated.View>
        </ScrollView>
      </SafeAreaView>
    </View>
  );
}
