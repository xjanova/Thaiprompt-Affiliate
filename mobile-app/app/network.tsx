/**
 * หน้า Network - แสดงเครือข่าย Affiliate / MLM
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  Share,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import * as Clipboard from 'expo-clipboard';

import { getMyAffiliate, getDirectReferrals, type AffiliateData, type TeamMember } from '@/services/api';
import { COLORS, formatCurrency } from '@/constants';

export default function NetworkScreen() {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [affiliateData, setAffiliateData] = useState<AffiliateData | null>(null);
  const [referrals, setReferrals] = useState<TeamMember[]>([]);
  const [activeTab, setActiveTab] = useState<'overview' | 'team'>('overview');

  const loadData = useCallback(async () => {
    try {
      const [affiliateResult, referralsResult] = await Promise.all([
        getMyAffiliate(),
        getDirectReferrals({ per_page: 10 }),
      ]);

      if (affiliateResult?.success && affiliateResult.data) {
        setAffiliateData(affiliateResult.data);
      }

      if (referralsResult?.success && referralsResult.data) {
        setReferrals(referralsResult.data.referrals);
      }
    } catch (error) {
      console.error('Load network data error:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const onRefresh = () => {
    setRefreshing(true);
    loadData();
  };

  const copyReferralCode = async () => {
    if (affiliateData?.referralCode) {
      await Clipboard.setStringAsync(affiliateData.referralCode);
      Alert.alert('คัดลอกแล้ว', 'รหัสแนะนำถูกคัดลอกแล้ว');
    }
  };

  const copyReferralLink = async () => {
    if (affiliateData?.referralLink) {
      await Clipboard.setStringAsync(affiliateData.referralLink);
      Alert.alert('คัดลอกแล้ว', 'ลิงก์แนะนำถูกคัดลอกแล้ว');
    }
  };

  const shareReferralLink = async () => {
    if (affiliateData?.referralLink) {
      try {
        await Share.share({
          message: `🎉 มาร่วมทีมกับฉัน! สมัครสมาชิกผ่านลิงก์นี้เพื่อรับสิทธิพิเศษ: ${affiliateData.referralLink}`,
          title: 'แนะนำเพื่อน - Thaiprompt Affiliate',
        });
      } catch (error) {
        console.error('Share error:', error);
      }
    }
  };

  const getRankIcon = (rankName?: string) => {
    if (!rankName) return '🔰';
    const icons: Record<string, string> = {
      bronze: '🥉',
      silver: '🥈',
      gold: '🥇',
      platinum: '💎',
      diamond: '💠',
      crown: '👑',
    };
    return icons[rankName.toLowerCase()] || '🎖️';
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
          <Ionicons name="arrow-back" size={24} color="white" />
        </TouchableOpacity>
        <Text className="text-white text-lg font-bold">เครือข่ายของฉัน</Text>
        <TouchableOpacity onPress={() => router.push('/team-tree')} className="p-2">
          <Ionicons name="git-network-outline" size={24} color={COLORS.PRIMARY} />
        </TouchableOpacity>
      </View>

      {/* Tabs */}
      <View className="flex-row px-4 py-2 border-b border-gray-800">
        <TouchableOpacity
          className={`flex-1 py-2 rounded-lg mr-2 ${
            activeTab === 'overview' ? 'bg-blue-600' : 'bg-slate-800'
          }`}
          onPress={() => setActiveTab('overview')}
        >
          <Text className={`text-center font-medium ${
            activeTab === 'overview' ? 'text-white' : 'text-gray-400'
          }`}>
            ภาพรวม
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          className={`flex-1 py-2 rounded-lg ${
            activeTab === 'team' ? 'bg-blue-600' : 'bg-slate-800'
          }`}
          onPress={() => setActiveTab('team')}
        >
          <Text className={`text-center font-medium ${
            activeTab === 'team' ? 'text-white' : 'text-gray-400'
          }`}>
            ลูกทีม
          </Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        className="flex-1"
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >
        {activeTab === 'overview' ? (
          <>
            {/* Referral Code Card */}
            <View className="px-4 py-6">
              <LinearGradient
                colors={['#8B5CF6', '#6D28D9']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                className="rounded-3xl p-6 overflow-hidden"
              >
                <View className="absolute top-0 right-0 w-40 h-40 rounded-full bg-white/10 -mr-20 -mt-20" />

                <Text className="text-white/80 text-sm mb-1">รหัสแนะนำของคุณ</Text>
                <View className="flex-row items-center mb-4">
                  <Text className="text-white text-3xl font-bold flex-1">
                    {affiliateData?.referralCode || 'N/A'}
                  </Text>
                  <TouchableOpacity
                    className="bg-white/20 rounded-full p-3"
                    onPress={copyReferralCode}
                  >
                    <Ionicons name="copy-outline" size={20} color="white" />
                  </TouchableOpacity>
                </View>

                <View className="flex-row space-x-2">
                  <TouchableOpacity
                    className="flex-1 bg-white/20 rounded-xl py-3 flex-row items-center justify-center"
                    onPress={copyReferralLink}
                  >
                    <Ionicons name="link-outline" size={18} color="white" />
                    <Text className="text-white font-medium ml-2">คัดลอกลิงก์</Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    className="flex-1 bg-white rounded-xl py-3 flex-row items-center justify-center"
                    onPress={shareReferralLink}
                  >
                    <Ionicons name="share-social" size={18} color="#6D28D9" />
                    <Text className="text-purple-700 font-medium ml-2">แชร์</Text>
                  </TouchableOpacity>
                </View>
              </LinearGradient>
            </View>

            {/* Rank Card */}
            {affiliateData?.rank && (
              <View className="px-4 mb-6">
                <TouchableOpacity
                  className="bg-slate-800 rounded-2xl p-4 flex-row items-center"
                  onPress={() => router.push('/rank')}
                >
                  <View
                    className="w-14 h-14 rounded-full items-center justify-center mr-4"
                    style={{ backgroundColor: affiliateData.rank.color || '#3B82F6' }}
                  >
                    <Text className="text-3xl">{getRankIcon(affiliateData.rank.name)}</Text>
                  </View>
                  <View className="flex-1">
                    <Text className="text-white font-bold text-lg">
                      {affiliateData.rank.nameTh || affiliateData.rank.name}
                    </Text>
                    <Text className="text-gray-400 text-sm">
                      อัตราคอมมิชชัน: {affiliateData.rank.commissionRate}%
                    </Text>
                  </View>
                  <Ionicons name="chevron-forward" size={20} color="#9CA3AF" />
                </TouchableOpacity>
              </View>
            )}

            {/* Statistics */}
            <View className="px-4 mb-6">
              <Text className="text-white font-semibold text-lg mb-3">สถิติเครือข่าย</Text>
              <View className="flex-row flex-wrap -mx-1">
                <View className="w-1/3 p-1">
                  <View className="bg-slate-800 rounded-xl p-4 items-center">
                    <Text className="text-3xl mb-2">👤</Text>
                    <Text className="text-2xl text-white font-bold">
                      {affiliateData?.statistics.directReferrals || 0}
                    </Text>
                    <Text className="text-gray-400 text-xs text-center">ลูกทีมตรง</Text>
                  </View>
                </View>
                <View className="w-1/3 p-1">
                  <View className="bg-slate-800 rounded-xl p-4 items-center">
                    <Text className="text-3xl mb-2">👥</Text>
                    <Text className="text-2xl text-white font-bold">
                      {affiliateData?.statistics.totalTeamMembers || 0}
                    </Text>
                    <Text className="text-gray-400 text-xs text-center">ทีมทั้งหมด</Text>
                  </View>
                </View>
                <View className="w-1/3 p-1">
                  <View className="bg-slate-800 rounded-xl p-4 items-center">
                    <Text className="text-3xl mb-2">✅</Text>
                    <Text className="text-2xl text-white font-bold">
                      {affiliateData?.statistics.activeMembers || 0}
                    </Text>
                    <Text className="text-gray-400 text-xs text-center">Active</Text>
                  </View>
                </View>
              </View>
            </View>

            {/* Earnings Summary */}
            <View className="px-4 mb-6">
              <View className="flex-row items-center justify-between mb-3">
                <Text className="text-white font-semibold text-lg">รายได้</Text>
                <TouchableOpacity onPress={() => router.push('/commissions')}>
                  <Text className="text-blue-400">ดูทั้งหมด</Text>
                </TouchableOpacity>
              </View>
              <View className="bg-slate-800 rounded-2xl p-4">
                <View className="flex-row justify-between mb-4 pb-4 border-b border-slate-700">
                  <View>
                    <Text className="text-gray-400 text-sm">รายได้ทั้งหมด</Text>
                    <Text className="text-white text-2xl font-bold">
                      {formatCurrency(affiliateData?.earnings.total || 0)}
                    </Text>
                  </View>
                  <View className="items-end">
                    <Text className="text-gray-400 text-sm">เดือนนี้</Text>
                    <Text className="text-green-400 text-2xl font-bold">
                      {formatCurrency(affiliateData?.earnings.thisMonth || 0)}
                    </Text>
                  </View>
                </View>
                <View className="flex-row items-center justify-between">
                  <View className="flex-row items-center">
                    <View className="w-3 h-3 rounded-full bg-amber-500 mr-2" />
                    <Text className="text-gray-400">รอดำเนินการ</Text>
                  </View>
                  <Text className="text-amber-400 font-semibold">
                    {formatCurrency(affiliateData?.earnings.pending || 0)}
                  </Text>
                </View>
              </View>
            </View>

            {/* Quick Actions */}
            <View className="px-4 mb-6">
              <Text className="text-white font-semibold text-lg mb-3">เมนูด่วน</Text>
              <View className="flex-row flex-wrap -mx-1">
                <View className="w-1/2 p-1">
                  <TouchableOpacity
                    className="bg-slate-800 rounded-xl p-4 flex-row items-center"
                    onPress={() => router.push('/commissions')}
                  >
                    <View className="bg-blue-500/20 rounded-full p-3 mr-3">
                      <Ionicons name="cash-outline" size={24} color="#3B82F6" />
                    </View>
                    <Text className="text-white font-medium">คอมมิชชัน</Text>
                  </TouchableOpacity>
                </View>
                <View className="w-1/2 p-1">
                  <TouchableOpacity
                    className="bg-slate-800 rounded-xl p-4 flex-row items-center"
                    onPress={() => router.push('/leaderboard')}
                  >
                    <View className="bg-amber-500/20 rounded-full p-3 mr-3">
                      <Ionicons name="trophy-outline" size={24} color="#F59E0B" />
                    </View>
                    <Text className="text-white font-medium">Leaderboard</Text>
                  </TouchableOpacity>
                </View>
                <View className="w-1/2 p-1">
                  <TouchableOpacity
                    className="bg-slate-800 rounded-xl p-4 flex-row items-center"
                    onPress={() => router.push('/rank')}
                  >
                    <View className="bg-purple-500/20 rounded-full p-3 mr-3">
                      <Ionicons name="ribbon-outline" size={24} color="#8B5CF6" />
                    </View>
                    <Text className="text-white font-medium">ระดับของฉัน</Text>
                  </TouchableOpacity>
                </View>
                <View className="w-1/2 p-1">
                  <TouchableOpacity
                    className="bg-slate-800 rounded-xl p-4 flex-row items-center"
                    onPress={() => router.push('/team-tree')}
                  >
                    <View className="bg-green-500/20 rounded-full p-3 mr-3">
                      <Ionicons name="git-network-outline" size={24} color="#10B981" />
                    </View>
                    <Text className="text-white font-medium">ผังทีม</Text>
                  </TouchableOpacity>
                </View>
              </View>
            </View>
          </>
        ) : (
          <>
            {/* Team List */}
            <View className="px-4 py-4">
              <View className="flex-row items-center justify-between mb-4">
                <Text className="text-white font-semibold text-lg">
                  ลูกทีมของคุณ ({affiliateData?.statistics.directReferrals || 0})
                </Text>
                <TouchableOpacity
                  className="bg-slate-800 rounded-lg px-3 py-2 flex-row items-center"
                  onPress={() => router.push('/team-tree')}
                >
                  <Ionicons name="git-network-outline" size={16} color="#9CA3AF" />
                  <Text className="text-gray-400 text-sm ml-1">ดูผัง</Text>
                </TouchableOpacity>
              </View>

              {referrals.length === 0 ? (
                <View className="bg-slate-800 rounded-2xl p-8 items-center">
                  <Text className="text-4xl mb-3">👥</Text>
                  <Text className="text-gray-400 text-center">
                    คุณยังไม่มีลูกทีม{'\n'}แชร์รหัสแนะนำเพื่อเริ่มสร้างทีม
                  </Text>
                  <TouchableOpacity
                    className="mt-4 bg-blue-600 rounded-xl px-6 py-3"
                    onPress={shareReferralLink}
                  >
                    <Text className="text-white font-medium">แชร์รหัสแนะนำ</Text>
                  </TouchableOpacity>
                </View>
              ) : (
                <View className="space-y-2">
                  {referrals.map((member) => (
                    <View
                      key={member.id}
                      className="bg-slate-800 rounded-xl p-4 flex-row items-center"
                    >
                      <View className="w-12 h-12 rounded-full bg-slate-700 items-center justify-center mr-3">
                        {member.avatar ? (
                          <Text className="text-2xl">👤</Text>
                        ) : (
                          <Text className="text-white font-bold text-lg">
                            {member.name?.charAt(0).toUpperCase()}
                          </Text>
                        )}
                      </View>
                      <View className="flex-1">
                        <View className="flex-row items-center">
                          <Text className="text-white font-medium">{member.name}</Text>
                          {member.isActive && (
                            <View className="bg-green-500/20 rounded-full px-2 py-0.5 ml-2">
                              <Text className="text-green-400 text-xs">Active</Text>
                            </View>
                          )}
                        </View>
                        <View className="flex-row items-center mt-1">
                          {member.rank && (
                            <View className="flex-row items-center mr-3">
                              <Text className="text-sm mr-1">{getRankIcon(member.rank.name)}</Text>
                              <Text className="text-gray-400 text-xs">
                                {member.rank.nameTh || member.rank.name}
                              </Text>
                            </View>
                          )}
                          <Text className="text-gray-500 text-xs">
                            {member.daysAgo === 0
                              ? 'วันนี้'
                              : member.daysAgo === 1
                              ? 'เมื่อวาน'
                              : `${member.daysAgo} วันที่แล้ว`}
                          </Text>
                        </View>
                      </View>
                      <View className="items-end">
                        <Text className="text-gray-400 text-xs">ลูกทีม</Text>
                        <Text className="text-white font-medium">
                          {member.totalReferrals || 0}
                        </Text>
                      </View>
                    </View>
                  ))}

                  {referrals.length >= 10 && (
                    <TouchableOpacity
                      className="bg-slate-800 rounded-xl p-4 items-center"
                      onPress={() => router.push('/team-list')}
                    >
                      <Text className="text-blue-400">ดูทั้งหมด</Text>
                    </TouchableOpacity>
                  )}
                </View>
              )}
            </View>
          </>
        )}

        {/* Bottom Padding */}
        <View className="h-8" />
      </ScrollView>
    </SafeAreaView>
  );
}
