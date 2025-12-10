/**
 * Home Screen - Premium Design V2
 * แก้ไข: crash on resume, icons หาย
 * เพิ่ม: AnimatedBackground หิ่งห้อยเคลื่อนไหว
 */

import React, { useState, useCallback } from 'react';
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
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';
import { BannerCarousel } from '@/components';
import { AnimatedBackground } from '@/components/AnimatedBackground';

const { width } = Dimensions.get('window');
const CARD_WIDTH = (width - 52) / 2;

// Menu Items - เพิ่มเมนูครบตามที่ต้องการ
const MENU_ITEMS = [
  { id: 'wallet', title: 'กระเป๋าเงิน', icon: 'wallet-outline', colors: ['#10B981', '#059669'], route: '/(tabs)/wallet' },
  { id: 'network', title: 'สายงาน', icon: 'people-outline', colors: ['#8B5CF6', '#6D28D9'], route: '/(tabs)/network' },
  { id: 'referral', title: 'แนะนำเพื่อน', icon: 'person-add-outline', colors: ['#EC4899', '#DB2777'], route: '/referral' },
  { id: 'commissions', title: 'คอมมิชชั่น', icon: 'cash-outline', colors: ['#3B82F6', '#2563EB'], route: '/commissions' },
  { id: 'shopping', title: 'ช้อปปิ้ง', icon: 'cart-outline', colors: ['#F59E0B', '#D97706'], route: '/shopping' },
  { id: 'rider', title: 'ไรเดอร์', icon: 'bicycle-outline', colors: ['#06B6D4', '#0891B2'], route: '/rider' },
  { id: 'wealth-guide', title: 'เส้นทางเศรษฐี', icon: 'book-outline', colors: ['#F59E0B', '#D97706'], route: '/wealth-guide' },
  { id: 'tarot', title: 'ดูดวง', icon: 'sparkles-outline', colors: ['#6366F1', '#4F46E5'], route: '/tarot' },
  { id: 'leaderboard', title: 'อันดับ', icon: 'trophy-outline', colors: ['#EF4444', '#DC2626'], route: '/leaderboard' },
  { id: 'coming-soon', title: 'เร็วๆ นี้', icon: 'rocket-outline', colors: ['#8B5CF6', '#6D28D9'], route: '/coming-soon' },
  { id: 'settings', title: 'ตั้งค่า', icon: 'settings-outline', colors: ['#6B7280', '#4B5563'], route: '/settings' },
] as const;

// Get greeting by time
const getGreeting = () => {
  const hour = new Date().getHours();
  if (hour < 12) return 'สวัสดีตอนเช้า';
  if (hour < 17) return 'สวัสดีตอนบ่าย';
  return 'สวัสดีตอนเย็น';
};

export default function HomeScreen() {
  const { user, isAuthenticated } = useAuthStore();
  const [refreshing, setRefreshing] = useState(false);
  const [greeting, setGreeting] = useState(getGreeting());

  // Update greeting when screen is focused
  useFocusEffect(
    useCallback(() => {
      setGreeting(getGreeting());
    }, [])
  );

  const onRefresh = async () => {
    setRefreshing(true);
    await new Promise(resolve => setTimeout(resolve, 1000));
    setRefreshing(false);
  };

  const handleMenuPress = (route: string) => {
    router.push(route as any);
  };

  return (
    <AnimatedBackground
      variant="firefly"
      isDark={true}
      intensity="low"
      particleCount={18}
    >
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
            tintColor="#3B82F6"
            colors={['#3B82F6']}
          />
        }
      >
        {/* Header */}
        <View style={styles.header}>
          <View style={styles.headerLeft}>
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
              style={styles.profileGradient}
            >
              <Text style={styles.profileText}>
                {user?.name?.charAt(0).toUpperCase() || 'U'}
              </Text>
            </LinearGradient>
          </Pressable>
        </View>

        {/* ⭐ Banner Carousel จาก Admin */}
        <View style={styles.bannerSection}>
          <BannerCarousel
            position="top"
            height={160}
            autoPlay={true}
            autoPlayInterval={5000}
            showIndicators={true}
            isDark={true}
          />
        </View>

        {/* Balance Card */}
        {isAuthenticated && (
          <View style={styles.balanceCardWrapper}>
            <LinearGradient
              colors={['#3B82F6', '#1D4ED8']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.balanceCard}
            >
              <View style={styles.balanceHeader}>
                <Ionicons name="wallet-outline" size={24} color="rgba(255,255,255,0.9)" />
                <Text style={styles.balanceLabel}>ยอดเงินคงเหลือ</Text>
              </View>
              <Text style={styles.balanceAmount}>฿0.00</Text>
              <View style={styles.balanceActions}>
                <Pressable
                  style={styles.balanceButton}
                  onPress={() => router.push('/(tabs)/wallet')}
                >
                  <Ionicons name="add-circle-outline" size={18} color="#FFF" />
                  <Text style={styles.balanceButtonText}>เติมเงิน</Text>
                </Pressable>
                <Pressable
                  style={styles.balanceButton}
                  onPress={() => router.push('/commissions')}
                >
                  <Ionicons name="arrow-up-circle-outline" size={18} color="#FFF" />
                  <Text style={styles.balanceButtonText}>ถอนเงิน</Text>
                </Pressable>
              </View>
            </LinearGradient>
          </View>
        )}

        {/* Quick Stats */}
        {isAuthenticated && (
          <View style={styles.statsRow}>
            <View style={styles.statCard}>
              <Ionicons name="people-outline" size={22} color="#8B5CF6" />
              <Text style={styles.statValue}>0</Text>
              <Text style={styles.statLabel}>สมาชิก</Text>
            </View>
            <View style={styles.statCard}>
              <Ionicons name="trending-up-outline" size={22} color="#10B981" />
              <Text style={styles.statValue}>฿0</Text>
              <Text style={styles.statLabel}>รายได้</Text>
            </View>
            <View style={styles.statCard}>
              <Ionicons name="star-outline" size={22} color="#F59E0B" />
              <Text style={styles.statValue}>Bronze</Text>
              <Text style={styles.statLabel}>ระดับ</Text>
            </View>
          </View>
        )}

        {/* Menu Section */}
        <View style={styles.menuSection}>
          <Text style={styles.sectionTitle}>บริการ</Text>
          <View style={styles.menuGrid}>
            {MENU_ITEMS.map((item) => (
              <Pressable
                key={item.id}
                style={styles.menuCard}
                onPress={() => handleMenuPress(item.route)}
              >
                <LinearGradient
                  colors={item.colors}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 1 }}
                  style={styles.menuGradient}
                >
                  <View style={styles.menuIconBox}>
                    <Ionicons name={item.icon as any} size={26} color="#FFF" />
                  </View>
                  <Text style={styles.menuTitle}>{item.title}</Text>
                </LinearGradient>
              </Pressable>
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
    </AnimatedBackground>
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
  bannerSection: {
    marginBottom: 16,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 56,
    paddingBottom: 16,
  },
  headerLeft: {
    flex: 1,
  },
  greeting: {
    fontSize: 14,
    color: '#9CA3AF',
  },
  userName: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 2,
  },
  profileButton: {
    width: 46,
    height: 46,
    borderRadius: 23,
    overflow: 'hidden',
  },
  profileGradient: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  balanceCardWrapper: {
    paddingHorizontal: 20,
    marginBottom: 16,
  },
  balanceCard: {
    borderRadius: 20,
    padding: 20,
  },
  balanceHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 6,
  },
  balanceLabel: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.9)',
    marginLeft: 8,
  },
  balanceAmount: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 14,
  },
  balanceActions: {
    flexDirection: 'row',
  },
  balanceButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 10,
    marginRight: 10,
  },
  balanceButtonText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '600',
    marginLeft: 5,
  },
  statsRow: {
    flexDirection: 'row',
    paddingHorizontal: 20,
    marginBottom: 20,
  },
  statCard: {
    flex: 1,
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 14,
    padding: 14,
    alignItems: 'center',
    marginHorizontal: 4,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  statValue: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 6,
  },
  statLabel: {
    fontSize: 11,
    color: '#9CA3AF',
    marginTop: 2,
  },
  menuSection: {
    paddingHorizontal: 20,
  },
  sectionTitle: {
    fontSize: 17,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 14,
  },
  menuGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },
  menuCard: {
    width: CARD_WIDTH,
    height: 95,
    borderRadius: 14,
    overflow: 'hidden',
    marginBottom: 12,
  },
  menuGradient: {
    flex: 1,
    padding: 14,
    justifyContent: 'space-between',
  },
  menuIconBox: {
    width: 40,
    height: 40,
    borderRadius: 10,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  menuTitle: {
    fontSize: 13,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  footer: {
    alignItems: 'center',
    paddingVertical: 20,
    marginTop: 16,
  },
  footerText: {
    fontSize: 11,
    color: '#6B7280',
  },
});
