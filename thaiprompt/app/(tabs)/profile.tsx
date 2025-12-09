/**
 * Profile Screen - Premium Stable Version
 * ใช้ StyleSheet แทน NativeWind
 */

import React, { useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  Switch,
  RefreshControl,
  StyleSheet,
  StatusBar,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';

// Menu Item Component
const MenuItem = ({
  icon,
  label,
  value,
  onPress,
  showArrow = true,
  danger = false,
}: {
  icon: string;
  label: string;
  value?: string;
  onPress: () => void;
  showArrow?: boolean;
  danger?: boolean;
}) => (
  <Pressable
    onPress={onPress}
    style={({ pressed }) => [
      styles.menuItem,
      { opacity: pressed ? 0.7 : 1 },
    ]}
  >
    <View style={[styles.menuIcon, danger && styles.menuIconDanger]}>
      <Ionicons
        name={icon as any}
        size={18}
        color={danger ? '#EF4444' : '#6B7280'}
      />
    </View>
    <Text style={[styles.menuLabel, danger && styles.menuLabelDanger]}>
      {label}
    </Text>
    {value && <Text style={styles.menuValue}>{value}</Text>}
    {showArrow && (
      <Ionicons name="chevron-forward" size={18} color="#9CA3AF" />
    )}
  </Pressable>
);

// Toggle Item Component
const ToggleItem = ({
  icon,
  label,
  value,
  onValueChange,
}: {
  icon: string;
  label: string;
  value: boolean;
  onValueChange: (value: boolean) => void;
}) => (
  <View style={styles.menuItem}>
    <View style={styles.menuIcon}>
      <Ionicons name={icon as any} size={18} color="#6B7280" />
    </View>
    <Text style={styles.menuLabel}>{label}</Text>
    <Switch
      value={value}
      onValueChange={onValueChange}
      trackColor={{ false: '#374151', true: '#3B82F6' }}
      thumbColor="#FFFFFF"
    />
  </View>
);

export default function ProfileScreen() {
  const { user, isAuthenticated, logout, refreshUser } = useAuthStore();
  const [refreshing, setRefreshing] = useState(false);
  const [isDarkMode, setIsDarkMode] = useState(true);

  const onRefresh = async () => {
    setRefreshing(true);
    await refreshUser();
    setRefreshing(false);
  };

  const handleLogout = () => {
    Alert.alert('ออกจากระบบ', 'คุณต้องการออกจากระบบใช่หรือไม่?', [
      { text: 'ยกเลิก', style: 'cancel' },
      {
        text: 'ออกจากระบบ',
        style: 'destructive',
        onPress: async () => {
          await logout();
          router.replace('/');
        },
      },
    ]);
  };

  // ถ้ายังไม่ login
  if (!isAuthenticated || !user) {
    return (
      <View style={styles.container}>
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <View style={styles.notLoggedIn}>
          <Ionicons name="person-circle-outline" size={80} color="#4B5563" />
          <Text style={styles.notLoggedInTitle}>โปรไฟล์ของคุณ</Text>
          <Text style={styles.notLoggedInText}>
            เข้าสู่ระบบเพื่อจัดการโปรไฟล์และตั้งค่า
          </Text>
          <Pressable style={styles.loginButton} onPress={() => router.push('/login')}>
            <Text style={styles.loginButtonText}>เข้าสู่ระบบ</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />

      <ScrollView
        style={styles.scrollView}
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
        {/* Profile Header */}
        <LinearGradient
          colors={['#3B82F6', '#2563EB']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.profileHeader}
        >
          <Text style={styles.headerTitle}>โปรไฟล์</Text>

          <View style={styles.profileRow}>
            {/* Avatar */}
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>
                {user?.name?.charAt(0).toUpperCase() || 'U'}
              </Text>
            </View>

            {/* Info */}
            <View style={styles.profileInfo}>
              <Text style={styles.profileName}>{user?.name}</Text>
              <Text style={styles.profileEmail}>{user?.email}</Text>
              <View style={styles.profileBadges}>
                <View style={styles.badge}>
                  <Text style={styles.badgeText}>{user?.role || 'Member'}</Text>
                </View>
                {user?.referralCode && (
                  <View style={[styles.badge, { backgroundColor: 'rgba(16,185,129,0.3)' }]}>
                    <Text style={styles.badgeText}>รหัส: {user.referralCode}</Text>
                  </View>
                )}
              </View>
            </View>
          </View>
        </LinearGradient>

        {/* Menu Sections */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>บัญชี</Text>
          <MenuItem
            icon="person-outline"
            label="แก้ไขโปรไฟล์"
            onPress={() => Alert.alert('แก้ไขโปรไฟล์', 'กำลังพัฒนา...')}
          />
          <MenuItem
            icon="key-outline"
            label="เปลี่ยนรหัสผ่าน"
            onPress={() => Alert.alert('เปลี่ยนรหัสผ่าน', 'กำลังพัฒนา...')}
          />
          <MenuItem
            icon="shield-checkmark-outline"
            label="ความปลอดภัย"
            onPress={() => Alert.alert('ความปลอดภัย', 'กำลังพัฒนา...')}
          />
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>การแนะนำ</Text>
          <MenuItem
            icon="share-social-outline"
            label="แนะนำเพื่อน"
            value={user?.referralCode || ''}
            onPress={() => router.push('/referral')}
          />
          <MenuItem
            icon="qr-code-outline"
            label="QR Code ของฉัน"
            onPress={() => router.push('/referral')}
          />
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>ตั้งค่า</Text>
          <ToggleItem
            icon="moon-outline"
            label="โหมดมืด"
            value={isDarkMode}
            onValueChange={setIsDarkMode}
          />
          <MenuItem
            icon="notifications-outline"
            label="การแจ้งเตือน"
            onPress={() => Alert.alert('การแจ้งเตือน', 'กำลังพัฒนา...')}
          />
          <MenuItem
            icon="language-outline"
            label="ภาษา"
            value="ไทย"
            onPress={() => Alert.alert('ภาษา', 'กำลังพัฒนา...')}
          />
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>ช่วยเหลือ</Text>
          <MenuItem
            icon="help-circle-outline"
            label="ศูนย์ช่วยเหลือ"
            onPress={() => Alert.alert('ศูนย์ช่วยเหลือ', 'กำลังพัฒนา...')}
          />
          <MenuItem
            icon="chatbubble-outline"
            label="ติดต่อเรา"
            onPress={() => Alert.alert('ติดต่อเรา', 'กำลังพัฒนา...')}
          />
          <MenuItem
            icon="document-text-outline"
            label="เงื่อนไขการใช้งาน"
            onPress={() => Alert.alert('เงื่อนไขการใช้งาน', 'กำลังพัฒนา...')}
          />
          <MenuItem
            icon="information-circle-outline"
            label="เกี่ยวกับแอพ"
            value={`v${APP_INFO.VERSION}`}
            onPress={() =>
              Alert.alert(
                APP_INFO.NAME,
                `Version ${APP_INFO.VERSION} (Build ${APP_INFO.BUILD_NUMBER})\n\n© 2024 Thaiprompt`
              )
            }
          />
        </View>

        <View style={[styles.section, { marginBottom: 100 }]}>
          <MenuItem
            icon="log-out-outline"
            label="ออกจากระบบ"
            onPress={handleLogout}
            showArrow={false}
            danger
          />
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
    backgroundColor: '#3B82F6',
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
  profileHeader: {
    paddingHorizontal: 24,
    paddingTop: 56,
    paddingBottom: 32,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 20,
  },
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  avatar: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 16,
  },
  avatarText: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#3B82F6',
  },
  profileInfo: {
    flex: 1,
  },
  profileName: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  profileEmail: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 2,
  },
  profileBadges: {
    flexDirection: 'row',
    marginTop: 8,
  },
  badge: {
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 10,
    marginRight: 8,
  },
  badgeText: {
    fontSize: 11,
    color: '#FFFFFF',
  },
  section: {
    marginTop: 16,
  },
  sectionTitle: {
    fontSize: 12,
    fontWeight: '600',
    color: '#9CA3AF',
    textTransform: 'uppercase',
    paddingHorizontal: 20,
    marginBottom: 8,
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    paddingVertical: 14,
    paddingHorizontal: 16,
    marginBottom: 1,
  },
  menuIcon: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: 'rgba(255,255,255,0.1)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  menuIconDanger: {
    backgroundColor: 'rgba(239,68,68,0.15)',
  },
  menuLabel: {
    flex: 1,
    fontSize: 15,
    color: '#FFFFFF',
    fontWeight: '500',
  },
  menuLabelDanger: {
    color: '#EF4444',
  },
  menuValue: {
    fontSize: 14,
    color: '#9CA3AF',
    marginRight: 8,
  },
});
