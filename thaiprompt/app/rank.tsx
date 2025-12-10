/**
 * หน้า Rank - แสดงระดับและความคืบหน้า
 *
 * แสดงข้อมูล rank พร้อม progress ring และสถิติที่สวยงาม
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  Animated,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import AnimatedRN, { FadeInDown, FadeInRight } from 'react-native-reanimated';

import { getUserRankProgress, getRanks, type Rank, type RankProgress } from '@/services/api';
import { COLORS, formatCurrency } from '@/constants';
import { ProgressRing, StatCard } from '@/components/charts';

export default function RankScreen() {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [currentRank, setCurrentRank] = useState<Rank | null>(null);
  const [nextRank, setNextRank] = useState<Rank | null>(null);
  const [statistics, setStatistics] = useState({
    rankPoints: 0,
    totalReferrals: 0,
    activeReferrals: 0,
    totalSales: 0,
    teamSales: 0,
  });
  const [progress, setProgress] = useState<RankProgress[]>([]);
  const [overallProgress, setOverallProgress] = useState(0);
  const [isMaxRank, setIsMaxRank] = useState(false);
  const [allRanks, setAllRanks] = useState<Rank[]>([]);
  const [showAllRanks, setShowAllRanks] = useState(false);

  const progressAnim = useState(new Animated.Value(0))[0];

  const loadData = useCallback(async () => {
    try {
      const [progressResult, ranksResult] = await Promise.all([
        getUserRankProgress(),
        getRanks(),
      ]);

      if (progressResult?.success && progressResult.data) {
        setCurrentRank(progressResult.data.currentRank);
        setNextRank(progressResult.data.nextRank);
        setStatistics(progressResult.data.statistics);
        setProgress(progressResult.data.progress);
        setOverallProgress(progressResult.data.overallProgress);
        setIsMaxRank(progressResult.data.isMaxRank);

        // Animate progress bar
        Animated.timing(progressAnim, {
          toValue: progressResult.data.overallProgress,
          duration: 1000,
          useNativeDriver: false,
        }).start();
      }

      if (ranksResult?.success && ranksResult.data) {
        setAllRanks(ranksResult.data.ranks);
      }
    } catch (error) {
      console.error('Load rank data error:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [progressAnim]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const onRefresh = () => {
    setRefreshing(true);
    progressAnim.setValue(0);
    loadData();
  };

  const getRankIcon = (rank: Rank | null) => {
    if (!rank) return '🔰';
    if (rank.icon) return rank.icon;

    const icons: Record<string, string> = {
      bronze: '🥉',
      silver: '🥈',
      gold: '🥇',
      platinum: '💎',
      diamond: '💠',
      crown: '👑',
      royal: '🏆',
      legend: '⭐',
    };

    return icons[rank.name?.toLowerCase()] || '🎖️';
  };

  const getRankColor = (rank: Rank | null): [string, string] => {
    if (!rank) return ['#6B7280', '#4B5563'];

    const colors: Record<string, [string, string]> = {
      bronze: ['#CD7F32', '#8B4513'],
      silver: ['#C0C0C0', '#A8A8A8'],
      gold: ['#FFD700', '#FFA500'],
      platinum: ['#E5E4E2', '#A0A0A0'],
      diamond: ['#B9F2FF', '#00CED1'],
      crown: ['#FF6B6B', '#EE5A24'],
      royal: ['#9B59B6', '#8E44AD'],
      legend: ['#FFD700', '#FF6B6B'],
    };

    return colors[rank.name?.toLowerCase()] || ['#3B82F6', '#1D4ED8'];
  };

  const getPrivilegeIcon = (privilege: string) => {
    const icons: Record<string, string> = {
      vip_support: '⭐',
      priority_withdrawal: '💨',
      exclusive_products: '🎁',
      leadership_training: '📚',
      travel_rewards: '✈️',
      car_bonus: '🚗',
      house_bonus: '🏠',
      global_bonus_pool: '🌍',
      personal_assistant: '👤',
      unlimited_withdrawals: '♾️',
    };
    return icons[privilege] || '✨';
  };

  const getPrivilegeText = (privilege: string) => {
    const texts: Record<string, string> = {
      vip_support: 'VIP Support',
      priority_withdrawal: 'ถอนเงินด่วน',
      exclusive_products: 'สินค้าพิเศษ',
      leadership_training: 'อบรมผู้นำ',
      travel_rewards: 'รางวัลท่องเที่ยว',
      car_bonus: 'โบนัสรถยนต์',
      house_bonus: 'โบนัสบ้าน',
      global_bonus_pool: 'แบ่งปันกำไร',
      personal_assistant: 'ผู้ช่วยส่วนตัว',
      unlimited_withdrawals: 'ถอนไม่จำกัด',
    };
    return texts[privilege] || privilege;
  };

  if (loading) {
    return (
      <SafeAreaView className="flex-1 bg-slate-900">
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator size="large" color={COLORS.PRIMARY} />
          <Text className="text-gray-400 mt-4">กำลังโหลด...</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView className="flex-1 bg-slate-900">
      {/* Header */}
      <View className="flex-row items-center justify-between px-4 py-3 border-b border-gray-800">
        <TouchableOpacity onPress={() => router.back()} className="p-2">
          <Text className="text-white text-2xl">←</Text>
        </TouchableOpacity>
        <Text className="text-white text-lg font-bold">ระดับของฉัน</Text>
        <TouchableOpacity onPress={() => router.push('/leaderboard')} className="p-2">
          <Text className="text-2xl">🏆</Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        className="flex-1"
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >
        {/* Current Rank Card */}
        <AnimatedRN.View entering={FadeInDown.springify()} className="px-4 py-6">
          <LinearGradient
            colors={getRankColor(currentRank)}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={{ borderRadius: 24, padding: 24, overflow: 'hidden' }}
          >
            {/* Decorative Elements */}
            <View className="absolute top-0 right-0 w-40 h-40 rounded-full bg-white/10 -mr-20 -mt-20" />
            <View className="absolute bottom-0 left-0 w-32 h-32 rounded-full bg-black/10 -ml-16 -mb-16" />

            <View className="items-center relative z-10">
              {/* Rank Icon with Progress Ring */}
              <View className="relative mb-4">
                <ProgressRing
                  progress={overallProgress}
                  size={140}
                  strokeWidth={8}
                  color="#FFFFFF"
                  backgroundColor="rgba(255,255,255,0.2)"
                  showPercentage={false}
                  isDark={true}
                />
                <View className="absolute inset-0 items-center justify-center">
                  <View className="bg-white/20 rounded-full p-4">
                    <Text className="text-5xl">{getRankIcon(currentRank)}</Text>
                  </View>
                </View>
              </View>

              {/* Rank Name */}
              <Text className="text-white text-3xl font-bold mb-1">
                {currentRank?.nameTh || currentRank?.name || 'สมาชิกใหม่'}
              </Text>
              <Text className="text-white/80 text-sm mb-4">
                Level {currentRank?.level || 1}
              </Text>

              {/* Progress Percentage */}
              {!isMaxRank && (
                <View className="bg-white/20 rounded-full px-4 py-2 mb-2">
                  <Text className="text-white font-medium">
                    ความคืบหน้า: {overallProgress}%
                  </Text>
                </View>
              )}

              {/* Commission Rate */}
              {currentRank?.commissionRate && (
                <View className="bg-white/30 rounded-full px-4 py-2">
                  <Text className="text-white font-medium">
                    อัตราคอมมิชชัน: {currentRank.commissionRate}%
                  </Text>
                </View>
              )}
            </View>
          </LinearGradient>
        </AnimatedRN.View>

        {/* Progress to Next Rank */}
        {!isMaxRank && nextRank && (
          <View className="px-4 mb-6">
            <View className="bg-slate-800 rounded-2xl p-4">
              <View className="flex-row items-center justify-between mb-3">
                <Text className="text-white font-semibold">
                  ความคืบหน้าไปยังระดับถัดไป
                </Text>
                <View className="flex-row items-center">
                  <Text className="text-2xl mr-2">{getRankIcon(nextRank)}</Text>
                  <Text className="text-amber-400 font-medium">
                    {nextRank.nameTh || nextRank.name}
                  </Text>
                </View>
              </View>

              {/* Overall Progress Bar */}
              <View className="bg-slate-700 rounded-full h-4 mb-2 overflow-hidden">
                <Animated.View
                  className="bg-gradient-to-r from-blue-500 to-purple-500 h-full rounded-full"
                  style={{
                    width: progressAnim.interpolate({
                      inputRange: [0, 100],
                      outputRange: ['0%', '100%'],
                    }),
                    backgroundColor: COLORS.PRIMARY,
                  }}
                />
              </View>
              <Text className="text-gray-400 text-sm text-right">
                {overallProgress}% เสร็จสิ้น
              </Text>

              {/* Detailed Progress */}
              {progress.length > 0 && (
                <View className="mt-4 space-y-3">
                  {progress.map((item, index) => (
                    <View key={index} className="flex-row items-center">
                      <View className="w-8 h-8 rounded-full bg-slate-700 items-center justify-center mr-3">
                        {item.completed ? (
                          <Text className="text-sm" style={{ color: '#10B981' }}>✓</Text>
                        ) : (
                          <Text className="text-sm">⏱</Text>
                        )}
                      </View>
                      <View className="flex-1">
                        <View className="flex-row justify-between mb-1">
                          <Text className="text-gray-300 text-sm">{item.typeText}</Text>
                          <Text className="text-gray-400 text-sm">
                            {item.currentValue.toLocaleString()} / {item.requiredValue.toLocaleString()}
                          </Text>
                        </View>
                        <View className="bg-slate-700 rounded-full h-2 overflow-hidden">
                          <View
                            className="h-full rounded-full"
                            style={{
                              width: `${item.progress}%`,
                              backgroundColor: item.completed ? '#10B981' : COLORS.PRIMARY,
                            }}
                          />
                        </View>
                      </View>
                    </View>
                  ))}
                </View>
              )}

              {/* Next Rank Bonus */}
              {nextRank.promotionBonus && nextRank.promotionBonus > 0 && (
                <View className="mt-4 bg-amber-500/20 rounded-xl p-3">
                  <View className="flex-row items-center">
                    <Text className="text-xl mr-2">🎁</Text>
                    <Text className="text-amber-400 flex-1">
                      โบนัสเลื่อนชั้น: {formatCurrency(nextRank.promotionBonus)}
                    </Text>
                  </View>
                </View>
              )}
            </View>
          </View>
        )}

        {/* Max Rank Message */}
        {isMaxRank && (
          <View className="px-4 mb-6">
            <View className="bg-gradient-to-r from-amber-500/20 to-orange-500/20 rounded-2xl p-4 border border-amber-500/30">
              <View className="flex-row items-center">
                <Text className="text-3xl mr-3">👑</Text>
                <View className="flex-1">
                  <Text className="text-amber-400 font-bold text-lg">
                    คุณอยู่ระดับสูงสุดแล้ว!
                  </Text>
                  <Text className="text-gray-400 text-sm">
                    ขอแสดงความยินดี คุณเป็นสุดยอดผู้นำของเรา
                  </Text>
                </View>
              </View>
            </View>
          </View>
        )}

        {/* Statistics */}
        <View className="px-4 mb-6">
          <Text className="text-white font-semibold text-lg mb-3">สถิติของคุณ</Text>
          <View className="flex-row flex-wrap -mx-1">
            <AnimatedRN.View entering={FadeInRight.delay(100).springify()} className="w-1/2 p-1">
              <StatCard
                title="คะแนนสะสม"
                value={statistics.rankPoints.toLocaleString()}
                icon="star"
                iconColor="#F59E0B"
                isDark={true}
                size="medium"
              />
            </AnimatedRN.View>
            <AnimatedRN.View entering={FadeInRight.delay(150).springify()} className="w-1/2 p-1">
              <StatCard
                title="คนแนะนำทั้งหมด"
                value={`${statistics.totalReferrals} คน`}
                icon="people"
                iconColor="#8B5CF6"
                isDark={true}
                size="medium"
              />
            </AnimatedRN.View>
            <AnimatedRN.View entering={FadeInRight.delay(200).springify()} className="w-1/2 p-1">
              <StatCard
                title="ยอดขายส่วนตัว"
                value={formatCurrency(statistics.totalSales)}
                icon="cart"
                iconColor="#10B981"
                isDark={true}
                size="medium"
              />
            </AnimatedRN.View>
            <AnimatedRN.View entering={FadeInRight.delay(250).springify()} className="w-1/2 p-1">
              <StatCard
                title="ยอดขายทีม"
                value={formatCurrency(statistics.teamSales)}
                icon="trending-up"
                iconColor="#3B82F6"
                isDark={true}
                size="medium"
              />
            </AnimatedRN.View>
          </View>
        </View>

        {/* Current Rank Privileges */}
        {currentRank?.privileges && currentRank.privileges.length > 0 && (
          <View className="px-4 mb-6">
            <Text className="text-white font-semibold text-lg mb-3">สิทธิพิเศษของคุณ</Text>
            <View className="bg-slate-800 rounded-2xl p-4">
              {currentRank.privileges.map((privilege, index) => (
                <View
                  key={privilege}
                  className={`flex-row items-center py-3 ${
                    index < currentRank.privileges!.length - 1 ? 'border-b border-slate-700' : ''
                  }`}
                >
                  <Text className="text-2xl mr-3">{getPrivilegeIcon(privilege)}</Text>
                  <Text className="text-white flex-1">{getPrivilegeText(privilege)}</Text>
                  <Text className="text-xl" style={{ color: '#10B981' }}>✓</Text>
                </View>
              ))}
            </View>
          </View>
        )}

        {/* All Ranks */}
        <View className="px-4 mb-6">
          <TouchableOpacity
            className="flex-row items-center justify-between mb-3"
            onPress={() => setShowAllRanks(!showAllRanks)}
          >
            <Text className="text-white font-semibold text-lg">ระดับทั้งหมด</Text>
            <Text className="text-xl" style={{ color: '#9CA3AF' }}>
              {showAllRanks ? '▲' : '▼'}
            </Text>
          </TouchableOpacity>

          {showAllRanks && (
            <View className="space-y-2">
              {allRanks.map((rank) => {
                const isCurrent = rank.id === currentRank?.id;
                const isAchieved = currentRank && rank.level <= currentRank.level;

                return (
                  <TouchableOpacity
                    key={rank.id}
                    className={`bg-slate-800 rounded-xl p-4 flex-row items-center ${
                      isCurrent ? 'border-2 border-amber-500' : ''
                    }`}
                    onPress={() => router.push(`/rank-detail?id=${rank.id}`)}
                  >
                    <View
                      className="w-12 h-12 rounded-full items-center justify-center mr-3"
                      style={{
                        backgroundColor: isAchieved ? getRankColor(rank)[0] : '#374151',
                      }}
                    >
                      <Text className="text-2xl">{getRankIcon(rank)}</Text>
                    </View>
                    <View className="flex-1">
                      <View className="flex-row items-center">
                        <Text
                          className={`font-semibold ${
                            isAchieved ? 'text-white' : 'text-gray-500'
                          }`}
                        >
                          {rank.nameTh || rank.name}
                        </Text>
                        {isCurrent && (
                          <View className="bg-amber-500 rounded-full px-2 py-0.5 ml-2">
                            <Text className="text-xs text-white font-medium">ปัจจุบัน</Text>
                          </View>
                        )}
                      </View>
                      <Text className="text-gray-500 text-sm">
                        Level {rank.level} • คอมมิชชัน {rank.commissionRate}%
                      </Text>
                    </View>
                    <Text className="text-xl" style={{ color: isAchieved ? '#10B981' : '#6B7280' }}>
                      {isAchieved ? '✓' : '🔒'}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>
          )}
        </View>

        {/* Bottom Padding */}
        <View className="h-8" />
      </ScrollView>
    </SafeAreaView>
  );
}
