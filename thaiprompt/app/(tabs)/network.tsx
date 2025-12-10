/**
 * Network/MLM Screen - Premium V3 Design
 * ออกแบบใหม่ให้สวยงาม น่าใช้ สมราคา
 *
 * Features:
 * - Gradient header ที่สวยงาม
 * - Stats cards แบบ 3D
 * - Animated elements
 * - Glassmorphism effects
 */

import React, { useEffect, useState, useCallback, useRef } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  RefreshControl,
  StyleSheet,
  StatusBar,
  ActivityIndicator,
  Animated,
  Dimensions,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { getReferrals } from '@/services/api';
import { formatCurrency } from '@/constants';
import type { ReferralsData, Referral } from '@/types';

const { width } = Dimensions.get('window');

// Premium Stat Card Component with Animation
const StatCard = ({
  icon,
  label,
  value,
  color,
  index,
}: {
  icon: string;
  label: string;
  value: string;
  color: string;
  index: number;
}) => {
  const scaleAnim = useRef(new Animated.Value(0.9)).current;
  const opacityAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.spring(scaleAnim, {
        toValue: 1,
        delay: index * 100,
        useNativeDriver: true,
        tension: 50,
        friction: 7,
      }),
      Animated.timing(opacityAnim, {
        toValue: 1,
        delay: index * 100,
        duration: 300,
        useNativeDriver: true,
      }),
    ]).start();
  }, []);

  return (
    <Animated.View
      style={[
        styles.statCard,
        {
          transform: [{ scale: scaleAnim }],
          opacity: opacityAnim,
        },
      ]}
    >
      {/* Glow effect */}
      <View style={[styles.statGlow, { backgroundColor: color }]} />

      {/* Icon */}
      <View style={[styles.statIconBox, { backgroundColor: color + '25' }]}>
        <Text style={{ fontSize: 24 }}>{icon}</Text>
      </View>

      {/* Value */}
      <Text style={styles.statValue}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>

      {/* Shine effect */}
      <View style={styles.statShine} />
    </Animated.View>
  );
};

// Premium Member Card Component
const MemberCard = ({ member, index }: { member: Referral; index: number }) => {
  const isActive = member.status === 'active';
  const scaleAnim = useRef(new Animated.Value(1)).current;

  const handlePressIn = () => {
    Animated.spring(scaleAnim, {
      toValue: 0.98,
      useNativeDriver: true,
    }).start();
  };

  const handlePressOut = () => {
    Animated.spring(scaleAnim, {
      toValue: 1,
      useNativeDriver: true,
    }).start();
  };

  // Level color based on level number
  const getLevelColor = (level: number) => {
    if (level === 1) return '#8B5CF6';
    if (level === 2) return '#06B6D4';
    if (level === 3) return '#10B981';
    return '#6B7280';
  };

  return (
    <Animated.View style={{ transform: [{ scale: scaleAnim }] }}>
      <Pressable
        onPressIn={handlePressIn}
        onPressOut={handlePressOut}
        style={styles.memberCard}
      >
        {/* Avatar with gradient border */}
        <View style={styles.memberAvatarWrapper}>
          <LinearGradient
            colors={[getLevelColor(member.level), getLevelColor(member.level) + '60']}
            style={styles.memberAvatarBorder}
          >
            <View style={styles.memberAvatar}>
              <Text style={styles.memberAvatarText}>
                {member.user.name.charAt(0).toUpperCase()}
              </Text>
            </View>
          </LinearGradient>
          {/* Level badge */}
          <View style={[styles.levelBadge, { backgroundColor: getLevelColor(member.level) }]}>
            <Text style={styles.levelBadgeText}>{member.level}</Text>
          </View>
        </View>

        {/* Info */}
        <View style={styles.memberInfo}>
          <Text style={styles.memberName}>{member.user.name}</Text>
          <Text style={styles.memberEmail} numberOfLines={1}>
            {member.user.email}
          </Text>
          {/* Active status indicator */}
          <View style={styles.statusRow}>
            <View style={[styles.statusDot, { backgroundColor: isActive ? '#10B981' : '#6B7280' }]} />
            <Text style={[styles.statusText, { color: isActive ? '#10B981' : '#6B7280' }]}>
              {isActive ? 'กำลังใช้งาน' : 'ไม่ใช้งาน'}
            </Text>
          </View>
        </View>

        {/* Earnings */}
        <View style={styles.memberRight}>
          <Text style={styles.earningsLabel}>รายได้</Text>
          <Text style={styles.memberEarnings}>{formatCurrency(member.earnings)}</Text>
        </View>
      </Pressable>
    </Animated.View>
  );
};

export default function NetworkScreen() {
  const { isAuthenticated } = useAuthStore();

  const [data, setData] = useState<ReferralsData | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  // โหลดข้อมูล
  const loadData = useCallback(async () => {
    if (!isAuthenticated) {
      setIsLoading(false);
      return;
    }

    try {
      const freshData = await getReferrals();
      if (freshData) {
        setData(freshData);
      }
    } catch (error) {
      console.error('Load network data error:', error);
    } finally {
      setIsLoading(false);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  };

  // ถ้ายังไม่ login
  if (!isAuthenticated) {
    return (
      <View style={styles.container}>
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <View style={styles.notLoggedIn}>
          <Text style={{ fontSize: 70 }}>👥</Text>
          <Text style={styles.notLoggedInTitle}>สายงานของคุณ</Text>
          <Text style={styles.notLoggedInText}>เข้าสู่ระบบเพื่อดูข้อมูลทีมและเครือข่าย</Text>
          <Pressable style={styles.loginButton} onPress={() => router.push('/login')}>
            <Text style={styles.loginButtonText}>เข้าสู่ระบบ</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  // Loading
  if (isLoading && !data) {
    return (
      <View style={styles.container}>
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <View style={styles.loadingBox}>
          <ActivityIndicator size="large" color="#8B5CF6" />
          <Text style={styles.loadingText}>กำลังโหลดข้อมูลทีม...</Text>
        </View>
      </View>
    );
  }

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
            tintColor="#8B5CF6"
            colors={['#8B5CF6']}
          />
        }
      >
        {/* Premium Header with Gradient */}
        <View style={styles.headerWrapper}>
          <LinearGradient
            colors={['#8B5CF6', '#6D28D9', '#4C1D95']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.headerGradient}
          >
            {/* Shine overlay */}
            <LinearGradient
              colors={['rgba(255,255,255,0.2)', 'rgba(255,255,255,0)']}
              start={{ x: 0, y: 0 }}
              end={{ x: 0.5, y: 1 }}
              style={styles.headerShine}
            />

            <View style={styles.headerContent}>
              <View style={styles.headerIcon}>
                <Text style={{ fontSize: 32 }}>👥</Text>
              </View>
              <Text style={styles.headerTitle}>สายงานของฉัน</Text>
              <Text style={styles.headerSubtitle}>ดูข้อมูลทีมและเครือข่ายของคุณ</Text>
            </View>

            {/* Summary stats in header */}
            <View style={styles.headerStats}>
              <View style={styles.headerStatItem}>
                <Text style={styles.headerStatValue}>{data?.totalReferrals || 0}</Text>
                <Text style={styles.headerStatLabel}>สมาชิก</Text>
              </View>
              <View style={styles.headerStatDivider} />
              <View style={styles.headerStatItem}>
                <Text style={styles.headerStatValue}>{data?.activeReferrals || 0}</Text>
                <Text style={styles.headerStatLabel}>ใช้งาน</Text>
              </View>
              <View style={styles.headerStatDivider} />
              <View style={styles.headerStatItem}>
                <Text style={styles.headerStatValue}>{formatCurrency(data?.totalEarnings || 0)}</Text>
                <Text style={styles.headerStatLabel}>รายได้</Text>
              </View>
            </View>
          </LinearGradient>
        </View>

        {/* Stats Cards Row */}
        <View style={styles.statsRow}>
          <StatCard
            icon="👥"
            label="สมาชิกทั้งหมด"
            value={`${data?.totalReferrals || 0} คน`}
            color="#8B5CF6"
            index={0}
          />
          <StatCard
            icon="💰"
            label="รายได้จากทีม"
            value={formatCurrency(data?.totalEarnings || 0)}
            color="#10B981"
            index={1}
          />
        </View>

        {/* Tree View Button */}
        <View style={styles.actionCardWrapper}>
          <Pressable onPress={() => router.push('/mlm-tree')}>
            <LinearGradient
              colors={['#8B5CF6', '#6D28D9']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.actionCard}
            >
              <View style={styles.actionCardIcon}>
                <Text style={{ fontSize: 24 }}>🌳</Text>
              </View>
              <View style={styles.actionCardInfo}>
                <Text style={styles.actionCardTitle}>ดูแผนผังสายงาน</Text>
                <Text style={styles.actionCardDesc}>ดู Tree View แบบเต็ม</Text>
              </View>
              <Text style={{ fontSize: 24, color: '#FFF' }}>›</Text>
            </LinearGradient>
          </Pressable>
        </View>

        {/* Referral Link Button */}
        <View style={styles.actionCardWrapper}>
          <Pressable onPress={() => router.push('/referral')}>
            <LinearGradient
              colors={['#EC4899', '#BE185D']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.actionCard}
            >
              <View style={styles.actionCardIcon}>
                <Text style={{ fontSize: 24 }}>📤</Text>
              </View>
              <View style={styles.actionCardInfo}>
                <Text style={styles.actionCardTitle}>แนะนำเพื่อน</Text>
                <Text style={styles.actionCardDesc}>แชร์ลิงก์เพื่อเพิ่มสมาชิก</Text>
              </View>
              <Text style={{ fontSize: 24, color: '#FFF' }}>›</Text>
            </LinearGradient>
          </Pressable>
        </View>

        {/* Member List */}
        <View style={styles.memberSection}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>สมาชิกในทีม</Text>
            {data?.referrals && data.referrals.length > 0 && (
              <View style={styles.sectionBadge}>
                <Text style={styles.sectionBadgeText}>{data.referrals.length} คน</Text>
              </View>
            )}
          </View>

          {data?.referrals && data.referrals.length > 0 ? (
            data.referrals.map((member, index) => (
              <MemberCard key={member.id} member={member} index={index} />
            ))
          ) : (
            <View style={styles.emptyBox}>
              {/* Empty state with illustration */}
              <View style={styles.emptyIconWrapper}>
                <LinearGradient
                  colors={['#8B5CF6', '#6D28D9']}
                  style={styles.emptyIconGradient}
                >
                  <Text style={{ fontSize: 40 }}>👥</Text>
                </LinearGradient>
              </View>
              <Text style={styles.emptyTitle}>ยังไม่มีสมาชิกในทีม</Text>
              <Text style={styles.emptyText}>เริ่มสร้างทีมของคุณด้วยการแนะนำเพื่อน</Text>
              <Pressable style={styles.emptyButton} onPress={() => router.push('/referral')}>
                <LinearGradient
                  colors={['#8B5CF6', '#6D28D9']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                  style={styles.emptyButtonGradient}
                >
                  <Text style={{ fontSize: 18 }}>📤</Text>
                  <Text style={styles.emptyButtonText}>แนะนำเพื่อน</Text>
                </LinearGradient>
              </Pressable>
            </View>
          )}
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

  // Premium Header Styles
  headerWrapper: {
    paddingHorizontal: 0,
    marginBottom: 16,
  },
  headerGradient: {
    paddingTop: 56,
    paddingBottom: 24,
    paddingHorizontal: 20,
    borderBottomLeftRadius: 30,
    borderBottomRightRadius: 30,
    overflow: 'hidden',
  },
  headerShine: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: '60%',
  },
  headerContent: {
    alignItems: 'center',
    marginBottom: 20,
  },
  headerIcon: {
    width: 64,
    height: 64,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  headerTitle: {
    fontSize: 26,
    fontWeight: 'bold',
    color: '#FFFFFF',
    textAlign: 'center',
  },
  headerSubtitle: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 6,
    textAlign: 'center',
  },
  headerStats: {
    flexDirection: 'row',
    backgroundColor: 'rgba(255,255,255,0.15)',
    borderRadius: 16,
    padding: 16,
    marginTop: 8,
  },
  headerStatItem: {
    flex: 1,
    alignItems: 'center',
  },
  headerStatValue: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  headerStatLabel: {
    fontSize: 11,
    color: 'rgba(255,255,255,0.7)',
    marginTop: 4,
  },
  headerStatDivider: {
    width: 1,
    backgroundColor: 'rgba(255,255,255,0.2)',
  },

  // Not Logged In
  notLoggedIn: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 24,
  },
  notLoggedInTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 16,
  },
  notLoggedInText: {
    fontSize: 14,
    color: '#9CA3AF',
    textAlign: 'center',
    marginTop: 8,
  },
  loginButton: {
    backgroundColor: '#8B5CF6',
    paddingHorizontal: 32,
    paddingVertical: 14,
    borderRadius: 12,
    marginTop: 24,
  },
  loginButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  loadingBox: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    color: '#9CA3AF',
    marginTop: 12,
  },

  // Stats Cards
  statsRow: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    marginBottom: 16,
    gap: 12,
  },
  statCard: {
    flex: 1,
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 20,
    padding: 18,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
    overflow: 'hidden',
  },
  statGlow: {
    position: 'absolute',
    top: -20,
    left: '50%',
    width: 60,
    height: 60,
    borderRadius: 30,
    opacity: 0.3,
    marginLeft: -30,
  },
  statShine: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: '50%',
    backgroundColor: 'rgba(255,255,255,0.03)',
  },
  statIconBox: {
    width: 52,
    height: 52,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  statValue: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  statLabel: {
    fontSize: 12,
    color: '#9CA3AF',
  },

  // Action Cards
  actionCardWrapper: {
    paddingHorizontal: 20,
    marginBottom: 12,
  },
  actionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 18,
    padding: 18,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 8,
  },
  actionCardIcon: {
    width: 52,
    height: 52,
    borderRadius: 14,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  actionCardInfo: {
    flex: 1,
  },
  actionCardTitle: {
    fontSize: 17,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  actionCardDesc: {
    fontSize: 13,
    color: 'rgba(255,255,255,0.7)',
    marginTop: 4,
  },

  // Member Section
  memberSection: {
    paddingHorizontal: 20,
    marginTop: 12,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 14,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  sectionBadge: {
    backgroundColor: 'rgba(139,92,246,0.2)',
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 10,
  },
  sectionBadgeText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#8B5CF6',
  },

  // Member Card
  memberCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 18,
    padding: 14,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  memberAvatarWrapper: {
    position: 'relative',
    marginRight: 14,
  },
  memberAvatarBorder: {
    width: 52,
    height: 52,
    borderRadius: 26,
    padding: 2,
  },
  memberAvatar: {
    width: '100%',
    height: '100%',
    borderRadius: 24,
    backgroundColor: '#1a1a2e',
    alignItems: 'center',
    justifyContent: 'center',
  },
  memberAvatarText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  levelBadge: {
    position: 'absolute',
    bottom: -2,
    right: -2,
    width: 18,
    height: 18,
    borderRadius: 9,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: '#1a1a2e',
  },
  levelBadgeText: {
    fontSize: 9,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  memberInfo: {
    flex: 1,
  },
  memberName: {
    fontSize: 15,
    fontWeight: '600',
    color: '#FFFFFF',
    marginBottom: 2,
  },
  memberEmail: {
    fontSize: 12,
    color: '#9CA3AF',
    marginBottom: 6,
  },
  statusRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  statusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    marginRight: 6,
  },
  statusText: {
    fontSize: 11,
    fontWeight: '500',
  },
  memberRight: {
    alignItems: 'flex-end',
  },
  earningsLabel: {
    fontSize: 10,
    color: '#9CA3AF',
    marginBottom: 2,
  },
  memberEarnings: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#10B981',
  },

  // Empty State
  emptyBox: {
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 24,
    padding: 40,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  emptyIconWrapper: {
    marginBottom: 20,
  },
  emptyIconGradient: {
    width: 80,
    height: 80,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 8,
  },
  emptyText: {
    fontSize: 14,
    color: '#9CA3AF',
    textAlign: 'center',
    lineHeight: 20,
  },
  emptyButton: {
    marginTop: 24,
    borderRadius: 14,
    overflow: 'hidden',
  },
  emptyButtonGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 24,
    paddingVertical: 14,
    gap: 8,
  },
  emptyButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
});
