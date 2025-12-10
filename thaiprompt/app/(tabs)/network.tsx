/**
 * Network/MLM Screen - Premium Stable Version
 * ใช้ StyleSheet แทน NativeWind
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  RefreshControl,
  Alert,
  StyleSheet,
  StatusBar,
  ActivityIndicator,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';

// Emoji icons map - ใช้ emoji แทน Ionicons ทั้งหมด
const EMOJI_ICONS: Record<string, string> = {
  'people': '👥',
  'people-outline': '👥',
  'cash-outline': '💵',
  'git-network-outline': '🌳',
  'share-social-outline': '📤',
  'chevron-forward': '›',
};
import { getReferrals } from '@/services/api';
import { formatCurrency } from '@/constants';
import type { ReferralsData, Referral } from '@/types';

// Stat Card Component - ใช้ emoji icons
const StatCard = ({
  icon,
  label,
  value,
  color,
}: {
  icon: string;
  label: string;
  value: string;
  color: string;
}) => (
  <View style={styles.statCard}>
    <View style={[styles.statIconBox, { backgroundColor: color + '20' }]}>
      <Text style={{ fontSize: 20 }}>{EMOJI_ICONS[icon] || '📊'}</Text>
    </View>
    <Text style={styles.statLabel}>{label}</Text>
    <Text style={styles.statValue}>{value}</Text>
  </View>
);

// Member Card Component
const MemberCard = ({ member }: { member: Referral }) => {
  const isActive = member.status === 'active';

  return (
    <View style={styles.memberCard}>
      <View style={styles.memberAvatar}>
        <Text style={styles.memberAvatarText}>
          {member.user.name.charAt(0).toUpperCase()}
        </Text>
      </View>
      <View style={styles.memberInfo}>
        <Text style={styles.memberName}>{member.user.name}</Text>
        <Text style={styles.memberMeta}>
          ระดับ {member.level} • {member.user.email}
        </Text>
      </View>
      <View style={styles.memberRight}>
        <View style={[styles.statusBadge, { backgroundColor: isActive ? '#D1FAE5' : '#E5E7EB' }]}>
          <Text style={[styles.statusText, { color: isActive ? '#059669' : '#6B7280' }]}>
            {isActive ? 'ใช้งาน' : 'ไม่ใช้งาน'}
          </Text>
        </View>
        <Text style={styles.memberEarnings}>{formatCurrency(member.earnings)}</Text>
      </View>
    </View>
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
        {/* Header */}
        <View style={styles.header}>
          <Text style={styles.headerTitle}>สายงานของฉัน</Text>
          <Text style={styles.headerSubtitle}>ดูข้อมูลทีมและเครือข่ายของคุณ</Text>
        </View>

        {/* Stats Row */}
        <View style={styles.statsRow}>
          <StatCard
            icon="people"
            label="สมาชิกทั้งหมด"
            value={`${data?.totalReferrals || 0} คน`}
            color="#8B5CF6"
          />
          <StatCard
            icon="cash-outline"
            label="รายได้จากทีม"
            value={formatCurrency(data?.totalEarnings || 0)}
            color="#10B981"
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
          <Text style={styles.sectionTitle}>สมาชิกในทีม</Text>

          {data?.referrals && data.referrals.length > 0 ? (
            data.referrals.map((member) => (
              <MemberCard key={member.id} member={member} />
            ))
          ) : (
            <View style={styles.emptyBox}>
              <Text style={{ fontSize: 48 }}>👥</Text>
              <Text style={styles.emptyText}>ยังไม่มีสมาชิกในทีม</Text>
              <Pressable style={styles.emptyButton} onPress={() => router.push('/referral')}>
                <Text style={styles.emptyButtonText}>แนะนำเพื่อน</Text>
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
  header: {
    paddingHorizontal: 20,
    paddingTop: 56,
    paddingBottom: 16,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  headerSubtitle: {
    fontSize: 14,
    color: '#9CA3AF',
    marginTop: 4,
  },
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
  statsRow: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    marginBottom: 16,
  },
  statCard: {
    flex: 1,
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 16,
    padding: 16,
    marginHorizontal: 4,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  statIconBox: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  statLabel: {
    fontSize: 12,
    color: '#9CA3AF',
  },
  statValue: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 2,
  },
  actionCardWrapper: {
    paddingHorizontal: 20,
    marginBottom: 12,
  },
  actionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 16,
    padding: 16,
  },
  actionCardIcon: {
    width: 48,
    height: 48,
    borderRadius: 12,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  actionCardInfo: {
    flex: 1,
  },
  actionCardTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  actionCardDesc: {
    fontSize: 13,
    color: 'rgba(255,255,255,0.7)',
    marginTop: 2,
  },
  memberSection: {
    paddingHorizontal: 20,
    marginTop: 8,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 12,
  },
  memberCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 16,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  memberAvatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: 'rgba(139,92,246,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  memberAvatarText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#8B5CF6',
  },
  memberInfo: {
    flex: 1,
  },
  memberName: {
    fontSize: 15,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  memberMeta: {
    fontSize: 12,
    color: '#9CA3AF',
    marginTop: 2,
  },
  memberRight: {
    alignItems: 'flex-end',
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 8,
  },
  statusText: {
    fontSize: 10,
    fontWeight: '600',
  },
  memberEarnings: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#8B5CF6',
    marginTop: 4,
  },
  emptyBox: {
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 16,
    padding: 32,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  emptyText: {
    color: '#9CA3AF',
    marginTop: 12,
  },
  emptyButton: {
    backgroundColor: '#8B5CF6',
    paddingHorizontal: 24,
    paddingVertical: 10,
    borderRadius: 10,
    marginTop: 16,
  },
  emptyButtonText: {
    color: '#FFFFFF',
    fontWeight: '600',
  },
});
