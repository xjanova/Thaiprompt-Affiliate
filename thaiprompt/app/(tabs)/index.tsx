/**
 * Home Screen - Premium Design
 * ใช้ StyleSheet + LinearGradient + Ionicons (ไม่ใช้ NativeWind)
 */

import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  StyleSheet,
  StatusBar,
  Dimensions,
  RefreshControl,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';

const { width } = Dimensions.get('window');
const CARD_WIDTH = (width - 48) / 2;

// Menu Items
const MENU_ITEMS = [
  { id: 'wallet', title: 'กระเป๋าเงิน', icon: 'wallet', colors: ['#10B981', '#059669'], route: '/(tabs)/wallet' },
  { id: 'network', title: 'สายงาน', icon: 'people', colors: ['#8B5CF6', '#6D28D9'], route: '/(tabs)/network' },
  { id: 'shopping', title: 'ช้อปปิ้ง', icon: 'cart', colors: ['#F59E0B', '#D97706'], route: '/shopping' },
  { id: 'commissions', title: 'คอมมิชชั่น', icon: 'cash', colors: ['#3B82F6', '#2563EB'], route: '/commissions' },
  { id: 'referral', title: 'แนะนำเพื่อน', icon: 'person-add', colors: ['#EC4899', '#DB2777'], route: '/referral' },
  { id: 'tarot', title: 'ดูดวง', icon: 'sparkles', colors: ['#6366F1', '#4F46E5'], route: '/tarot' },
  { id: 'leaderboard', title: 'อันดับ', icon: 'trophy', colors: ['#EF4444', '#DC2626'], route: '/leaderboard' },
  { id: 'settings', title: 'ตั้งค่า', icon: 'settings', colors: ['#6B7280', '#4B5563'], route: '/settings' },
];

// Menu Card Component
const MenuCard = ({ item, onPress }: { item: typeof MENU_ITEMS[0]; onPress: () => void }) => (
  <Pressable onPress={onPress} style={styles.menuCard}>
    <LinearGradient
      colors={item.colors as [string, string]}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={styles.menuCardGradient}
    >
      <View style={styles.menuCardIconContainer}>
        <Ionicons name={item.icon as any} size={28} color="#FFFFFF" />
      </View>
      <Text style={styles.menuCardTitle}>{item.title}</Text>
    </LinearGradient>
  </Pressable>
);

export default function HomeScreen() {
  const { user, isAuthenticated } = useAuthStore();
  const [refreshing, setRefreshing] = useState(false);
  const [greeting, setGreeting] = useState('สวัสดี');

  useEffect(() => {
    const hour = new Date().getHours();
    if (hour < 12) setGreeting('สวัสดีตอนเช้า');
    else if (hour < 17) setGreeting('สวัสดีตอนบ่าย');
    else setGreeting('สวัสดีตอนเย็น');
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    // Simulate refresh
    await new Promise(resolve => setTimeout(resolve, 1000));
    setRefreshing(false);
  };

  const handleMenuPress = (route: string) => {
    router.push(route as any);
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" />

      {/* Background Gradient */}
      <LinearGradient
        colors={['#0F0F23', '#1A1A2E', '#16213E']}
        style={StyleSheet.absoluteFillObject}
      />

      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor="#3B82F6"
          />
        }
      >
        {/* Header */}
        <View style={styles.header}>
          <View>
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
              style={styles.profileButtonGradient}
            >
              <Text style={styles.profileButtonText}>
                {user?.name?.charAt(0).toUpperCase() || 'U'}
              </Text>
            </LinearGradient>
          </Pressable>
        </View>

        {/* Balance Card */}
        {isAuthenticated && (
          <LinearGradient
            colors={['#3B82F6', '#1D4ED8']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.balanceCard}
          >
            <View style={styles.balanceHeader}>
              <Ionicons name="wallet" size={24} color="rgba(255,255,255,0.8)" />
              <Text style={styles.balanceLabel}>ยอดเงินคงเหลือ</Text>
            </View>
            <Text style={styles.balanceAmount}>฿0.00</Text>
            <View style={styles.balanceActions}>
              <Pressable style={styles.balanceButton} onPress={() => router.push('/(tabs)/wallet')}>
                <Ionicons name="add-circle" size={18} color="#FFFFFF" />
                <Text style={styles.balanceButtonText}>เติมเงิน</Text>
              </Pressable>
              <Pressable style={styles.balanceButton} onPress={() => router.push('/commissions')}>
                <Ionicons name="arrow-up-circle" size={18} color="#FFFFFF" />
                <Text style={styles.balanceButtonText}>ถอนเงิน</Text>
              </Pressable>
            </View>
          </LinearGradient>
        )}

        {/* Quick Stats */}
        {isAuthenticated && (
          <View style={styles.statsContainer}>
            <View style={styles.statCard}>
              <Ionicons name="people" size={20} color="#8B5CF6" />
              <Text style={styles.statValue}>0</Text>
              <Text style={styles.statLabel}>สมาชิก</Text>
            </View>
            <View style={styles.statCard}>
              <Ionicons name="trending-up" size={20} color="#10B981" />
              <Text style={styles.statValue}>฿0</Text>
              <Text style={styles.statLabel}>รายได้เดือนนี้</Text>
            </View>
            <View style={styles.statCard}>
              <Ionicons name="star" size={20} color="#F59E0B" />
              <Text style={styles.statValue}>Bronze</Text>
              <Text style={styles.statLabel}>ระดับ</Text>
            </View>
          </View>
        )}

        {/* Menu Grid */}
        <View style={styles.menuSection}>
          <Text style={styles.sectionTitle}>บริการ</Text>
          <View style={styles.menuGrid}>
            {MENU_ITEMS.map((item) => (
              <MenuCard
                key={item.id}
                item={item}
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
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 60,
    paddingBottom: 20,
  },
  greeting: {
    fontSize: 14,
    color: '#9CA3AF',
  },
  userName: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 4,
  },
  profileButton: {
    width: 48,
    height: 48,
    borderRadius: 24,
    overflow: 'hidden',
  },
  profileButtonGradient: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileButtonText: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  balanceCard: {
    marginHorizontal: 20,
    borderRadius: 20,
    padding: 20,
    marginBottom: 20,
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.3,
    shadowRadius: 16,
    elevation: 10,
  },
  balanceHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  balanceLabel: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    marginLeft: 8,
  },
  balanceAmount: {
    fontSize: 36,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 16,
  },
  balanceActions: {
    flexDirection: 'row',
    gap: 12,
  },
  balanceButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 12,
    gap: 6,
  },
  balanceButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '600',
  },
  statsContainer: {
    flexDirection: 'row',
    paddingHorizontal: 20,
    gap: 12,
    marginBottom: 24,
  },
  statCard: {
    flex: 1,
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 16,
    padding: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  statValue: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 8,
  },
  statLabel: {
    fontSize: 12,
    color: '#9CA3AF',
    marginTop: 4,
  },
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
    gap: 12,
  },
  menuCard: {
    width: CARD_WIDTH,
    height: 100,
    borderRadius: 16,
    overflow: 'hidden',
  },
  menuCardGradient: {
    flex: 1,
    padding: 16,
    justifyContent: 'space-between',
  },
  menuCardIconContainer: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  menuCardTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  footer: {
    alignItems: 'center',
    paddingVertical: 24,
    marginTop: 20,
  },
  footerText: {
    fontSize: 12,
    color: '#6B7280',
  },
});
