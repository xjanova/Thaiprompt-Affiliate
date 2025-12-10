/**
 * หน้า Leaderboard - อันดับผู้นำ
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';

import { getLeaderboard } from '@/services/api';
import { COLORS, formatCurrency } from '@/constants';

interface Leader {
  position: number;
  userId: number;
  name: string;
  avatar?: string;
  rank?: {
    name: string;
    nameTh: string;
    icon?: string;
    color?: string;
  };
  value: number;
}

export default function LeaderboardScreen() {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [leaders, setLeaders] = useState<Leader[]>([]);
  const [currentUserPosition, setCurrentUserPosition] = useState<number | null>(null);
  const [activeType, setActiveType] = useState<'referrals' | 'sales' | 'earnings'>('referrals');
  const [activePeriod, setActivePeriod] = useState<'all' | 'month' | 'week'>('all');

  const loadData = useCallback(async () => {
    try {
      const result = await getLeaderboard({
        type: activeType,
        period: activePeriod,
        limit: 50,
      });

      if (result?.success && result.data) {
        setLeaders(result.data.leaders);
        setCurrentUserPosition(result.data.currentUserPosition || null);
      }
    } catch (error) {
      console.error('Load leaderboard error:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [activeType, activePeriod]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const onRefresh = () => {
    setRefreshing(true);
    loadData();
  };

  const getRankIcon = (rankName?: string) => {
    if (!rankName) return null;
    const icons: Record<string, string> = {
      bronze: String.fromCodePoint(0x1F949),
      silver: String.fromCodePoint(0x1F948),
      gold: String.fromCodePoint(0x1F947),
      platinum: String.fromCodePoint(0x1F48E),
      diamond: String.fromCodePoint(0x1F4A0),
      crown: String.fromCodePoint(0x1F451),
    };
    return icons[rankName.toLowerCase()] || String.fromCodePoint(0x1F396);
  };

  const getPositionStyle = (position: number) => {
    if (position === 1) {
      return {
        gradient: ['#FFD700', '#FFA500'] as [string, string],
        icon: String.fromCodePoint(0x1F947),
        size: 'large',
      };
    }
    if (position === 2) {
      return {
        gradient: ['#C0C0C0', '#A8A8A8'] as [string, string],
        icon: String.fromCodePoint(0x1F948),
        size: 'medium',
      };
    }
    if (position === 3) {
      return {
        gradient: ['#CD7F32', '#8B4513'] as [string, string],
        icon: String.fromCodePoint(0x1F949),
        size: 'medium',
      };
    }
    return {
      gradient: ['#374151', '#1F2937'] as [string, string],
      icon: null,
      size: 'small',
    };
  };

  const formatValue = (value: number) => {
    if (activeType === 'referrals') {
      return `${value.toLocaleString()} คน`;
    }
    return formatCurrency(value);
  };

  const getTypeLabel = () => {
    switch (activeType) {
      case 'referrals': return 'จำนวนคนแนะนำ';
      case 'sales': return 'ยอดขาย';
      case 'earnings': return 'รายได้';
      default: return '';
    }
  };

  const getPeriodLabel = () => {
    switch (activePeriod) {
      case 'week': return 'สัปดาห์นี้';
      case 'month': return 'เดือนนี้';
      case 'all': return 'ตลอดกาล';
      default: return '';
    }
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

  const top3 = leaders.slice(0, 3);
  const others = leaders.slice(3);

  return (
    <SafeAreaView className="flex-1 bg-slate-900">
      {/* Header */}
      <View className="flex-row items-center justify-between px-4 py-3 border-b border-gray-800">
        <TouchableOpacity onPress={() => router.back()} className="p-2">
          <Text className="text-white text-2xl">←</Text>
        </TouchableOpacity>
        <Text className="text-white text-lg font-bold">Leaderboard</Text>
        <View className="w-10" />
      </View>

      <ScrollView
        className="flex-1"
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >
        {/* Type Tabs */}
        <View className="px-4 pt-4">
          <View className="flex-row bg-slate-800 rounded-xl p-1">
            {(['referrals', 'sales', 'earnings'] as const).map((type) => (
              <TouchableOpacity
                key={type}
                className={`flex-1 py-2 rounded-lg ${
                  activeType === type ? 'bg-blue-600' : ''
                }`}
                onPress={() => {
                  setActiveType(type);
                  setLoading(true);
                }}
              >
                <Text className={`text-center font-medium ${
                  activeType === type ? 'text-white' : 'text-gray-400'
                }`}>
                  {type === 'referrals' ? 'แนะนำ' : type === 'sales' ? 'ยอดขาย' : 'รายได้'}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        {/* Period Tabs */}
        <View className="px-4 pt-3">
          <View className="flex-row space-x-2">
            {(['week', 'month', 'all'] as const).map((period) => (
              <TouchableOpacity
                key={period}
                className={`flex-1 py-2 rounded-lg border ${
                  activePeriod === period
                    ? 'border-blue-500 bg-blue-500/20'
                    : 'border-slate-700'
                }`}
                onPress={() => {
                  setActivePeriod(period);
                  setLoading(true);
                }}
              >
                <Text className={`text-center text-sm ${
                  activePeriod === period ? 'text-blue-400' : 'text-gray-400'
                }`}>
                  {period === 'week' ? 'สัปดาห์นี้' : period === 'month' ? 'เดือนนี้' : 'ตลอดกาล'}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        {/* Top 3 Podium */}
        {top3.length > 0 && (
          <View className="px-4 py-6">
            <View className="flex-row items-end justify-center">
              {/* 2nd Place */}
              {top3.length >= 2 && (
                <View className="items-center flex-1">
                  <LinearGradient
                    colors={getPositionStyle(2).gradient}
                    style={{ width: 64, height: 64, borderRadius: 32, alignItems: 'center', justifyContent: 'center', marginBottom: 8 }}
                  >
                    <Text className="text-3xl">{getPositionStyle(2).icon}</Text>
                  </LinearGradient>
                  <Text className="text-white font-medium text-sm text-center" numberOfLines={1}>
                    {top3[1].name}
                  </Text>
                  {top3[1].rank && (
                    <Text className="text-gray-400 text-xs">
                      {getRankIcon(top3[1].rank.name)} {top3[1].rank.nameTh || top3[1].rank.name}
                    </Text>
                  )}
                  <Text className="text-gray-300 text-sm font-medium mt-1">
                    {formatValue(top3[1].value)}
                  </Text>
                  <View className="bg-slate-700 w-full h-24 rounded-t-xl mt-2" />
                </View>
              )}

              {/* 1st Place */}
              {top3.length >= 1 && (
                <View className="items-center flex-1 mx-2">
                  <LinearGradient
                    colors={getPositionStyle(1).gradient}
                    style={{ width: 80, height: 80, borderRadius: 40, alignItems: 'center', justifyContent: 'center', marginBottom: 8 }}
                  >
                    <Text className="text-4xl">{getPositionStyle(1).icon}</Text>
                  </LinearGradient>
                  <Text className="text-white font-bold text-center" numberOfLines={1}>
                    {top3[0].name}
                  </Text>
                  {top3[0].rank && (
                    <Text className="text-amber-400 text-xs">
                      {getRankIcon(top3[0].rank.name)} {top3[0].rank.nameTh || top3[0].rank.name}
                    </Text>
                  )}
                  <Text className="text-amber-400 font-bold mt-1">
                    {formatValue(top3[0].value)}
                  </Text>
                  <View className="bg-amber-600 w-full h-32 rounded-t-xl mt-2" />
                </View>
              )}

              {/* 3rd Place */}
              {top3.length >= 3 && (
                <View className="items-center flex-1">
                  <LinearGradient
                    colors={getPositionStyle(3).gradient}
                    style={{ width: 56, height: 56, borderRadius: 28, alignItems: 'center', justifyContent: 'center', marginBottom: 8 }}
                  >
                    <Text className="text-2xl">{getPositionStyle(3).icon}</Text>
                  </LinearGradient>
                  <Text className="text-white font-medium text-sm text-center" numberOfLines={1}>
                    {top3[2].name}
                  </Text>
                  {top3[2].rank && (
                    <Text className="text-gray-400 text-xs">
                      {getRankIcon(top3[2].rank.name)} {top3[2].rank.nameTh || top3[2].rank.name}
                    </Text>
                  )}
                  <Text className="text-gray-300 text-sm font-medium mt-1">
                    {formatValue(top3[2].value)}
                  </Text>
                  <View className="bg-orange-700 w-full h-16 rounded-t-xl mt-2" />
                </View>
              )}
            </View>
          </View>
        )}

        {/* Current User Position */}
        {currentUserPosition && currentUserPosition > 3 && (
          <View className="px-4 mb-4">
            <View className="bg-blue-600/20 border border-blue-500/50 rounded-xl p-4">
              <View className="flex-row items-center justify-between">
                <View className="flex-row items-center">
                  <View className="w-10 h-10 rounded-full bg-blue-600 items-center justify-center mr-3">
                    <Text className="text-white font-bold">{currentUserPosition}</Text>
                  </View>
                  <View>
                    <Text className="text-white font-medium">ตำแหน่งของคุณ</Text>
                    <Text className="text-blue-400 text-sm">อันดับที่ {currentUserPosition}</Text>
                  </View>
                </View>
                <Text className="text-2xl">👤</Text>
              </View>
            </View>
          </View>
        )}

        {/* Other Rankings */}
        {others.length > 0 && (
          <View className="px-4 mb-6">
            <Text className="text-white font-semibold text-lg mb-3">อันดับอื่นๆ</Text>
            <View className="bg-slate-800 rounded-2xl overflow-hidden">
              {others.map((leader, index) => {
                const isCurrentUser = currentUserPosition === leader.position;
                return (
                  <View
                    key={leader.userId}
                    className={`flex-row items-center p-4 ${
                      index < others.length - 1 ? 'border-b border-slate-700' : ''
                    } ${isCurrentUser ? 'bg-blue-600/10' : ''}`}
                  >
                    <View className="w-8 h-8 rounded-full bg-slate-700 items-center justify-center mr-3">
                      <Text className="text-gray-300 font-medium text-sm">
                        {leader.position}
                      </Text>
                    </View>
                    <View className="w-10 h-10 rounded-full bg-slate-600 items-center justify-center mr-3">
                      {leader.avatar ? (
                        <Text className="text-xl">
                          {String.fromCodePoint(0x1F464)}
                        </Text>
                      ) : (
                        <Text className="text-white font-bold">
                          {leader.name?.charAt(0).toUpperCase()}
                        </Text>
                      )}
                    </View>
                    <View className="flex-1">
                      <View className="flex-row items-center">
                        <Text className={`font-medium ${
                          isCurrentUser ? 'text-blue-400' : 'text-white'
                        }`}>
                          {leader.name}
                        </Text>
                        {isCurrentUser && (
                          <View className="bg-blue-600 rounded-full px-2 py-0.5 ml-2">
                            <Text className="text-white text-xs">คุณ</Text>
                          </View>
                        )}
                      </View>
                      {leader.rank && (
                        <Text className="text-gray-500 text-xs">
                          {getRankIcon(leader.rank.name)} {leader.rank.nameTh || leader.rank.name}
                        </Text>
                      )}
                    </View>
                    <Text className="text-gray-300 font-medium">
                      {formatValue(leader.value)}
                    </Text>
                  </View>
                );
              })}
            </View>
          </View>
        )}

        {/* Empty State */}
        {leaders.length === 0 && (
          <View className="px-4 py-8 items-center">
            <Text className="text-4xl mb-3">
              {String.fromCodePoint(0x1F3C6)}
            </Text>
            <Text className="text-gray-400 text-center">
              ยังไม่มีข้อมูล Leaderboard{'\n'}{getTypeLabel()} {getPeriodLabel()}
            </Text>
          </View>
        )}

        {/* Bottom Padding */}
        <View className="h-8" />
      </ScrollView>
    </SafeAreaView>
  );
}
