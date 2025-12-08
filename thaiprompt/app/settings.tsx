/**
 * Settings Screen - หน้าตั้งค่า
 * - ตั้งค่าโปรไฟล์
 * - ตั้งค่าการแจ้งเตือน
 * - ตั้งค่าธีม (Dark/Light) พร้อม RGB Lava Effect
 * - ตั้งค่าภาษา
 * - ความปลอดภัย
 * - เกี่ยวกับแอพ
 */

import React, { useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Switch,
  StyleSheet,
  Alert,
  Linking,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import Animated, {
  FadeInDown,
  FadeInUp,
  useSharedValue,
  useAnimatedStyle,
  withSpring,
} from 'react-native-reanimated';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import { LavaBackground, GlassCard } from '@/components';
import { APP_INFO } from '@/config/appConfig';

// Theme Option Component
const ThemeOption = ({
  mode,
  currentMode,
  icon,
  title,
  description,
  onPress,
  isDark,
}: {
  mode: 'light' | 'dark' | 'system';
  currentMode: string;
  icon: keyof typeof Ionicons.glyphMap;
  title: string;
  description: string;
  onPress: () => void;
  isDark: boolean;
}) => {
  const isSelected = currentMode === mode;

  return (
    <Pressable
      onPress={onPress}
      style={[
        styles.themeOption,
        {
          backgroundColor: isSelected
            ? isDark
              ? 'rgba(59, 130, 246, 0.2)'
              : 'rgba(59, 130, 246, 0.1)'
            : isDark
            ? 'rgba(255,255,255,0.05)'
            : 'rgba(0,0,0,0.02)',
          borderColor: isSelected
            ? '#3B82F6'
            : isDark
            ? 'rgba(255,255,255,0.1)'
            : 'rgba(0,0,0,0.05)',
        },
      ]}
    >
      <View
        style={[
          styles.themeIconContainer,
          {
            backgroundColor: isSelected
              ? mode === 'dark'
                ? '#7B2CBF'
                : mode === 'light'
                ? '#FFB300'
                : '#3B82F6'
              : isDark
              ? 'rgba(255,255,255,0.1)'
              : 'rgba(0,0,0,0.05)',
          },
        ]}
      >
        <Ionicons
          name={icon}
          size={24}
          color={isSelected ? '#FFFFFF' : isDark ? '#9CA3AF' : '#6B7280'}
        />
      </View>
      <View style={styles.themeContent}>
        <Text
          style={[
            styles.themeTitle,
            {
              color: isSelected
                ? '#3B82F6'
                : isDark
                ? '#FFFFFF'
                : '#1F2937',
            },
          ]}
        >
          {title}
        </Text>
        <Text style={[styles.themeDescription, { color: isDark ? '#9CA3AF' : '#6B7280' }]}>
          {description}
        </Text>
      </View>
      {isSelected && (
        <View style={styles.checkContainer}>
          <Ionicons name="checkmark-circle" size={24} color="#3B82F6" />
        </View>
      )}
    </Pressable>
  );
};

// Setting Item Component
const SettingItem = ({
  icon,
  iconColor,
  title,
  subtitle,
  onPress,
  rightElement,
  isDark,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  iconColor?: string;
  title: string;
  subtitle?: string;
  onPress?: () => void;
  rightElement?: React.ReactNode;
  isDark: boolean;
}) => {
  const scale = useSharedValue(1);

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [{ scale: scale.value }],
  }));

  return (
    <Animated.View style={animatedStyle}>
      <Pressable
        onPress={onPress}
        onPressIn={() => {
          scale.value = withSpring(0.98);
        }}
        onPressOut={() => {
          scale.value = withSpring(1);
        }}
        style={[
          styles.settingItem,
          { backgroundColor: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.02)' },
        ]}
      >
        <View
          style={[
            styles.iconContainer,
            { backgroundColor: iconColor ? `${iconColor}20` : 'rgba(212,175,55,0.2)' },
          ]}
        >
          <Ionicons name={icon} size={22} color={iconColor || '#D4AF37'} />
        </View>
        <View style={styles.settingContent}>
          <Text style={[styles.settingTitle, { color: isDark ? '#FFFFFF' : '#1F2937' }]}>
            {title}
          </Text>
          {subtitle && (
            <Text style={[styles.settingSubtitle, { color: isDark ? '#999999' : '#666666' }]}>
              {subtitle}
            </Text>
          )}
        </View>
        {rightElement || (
          <Ionicons name="chevron-forward" size={20} color={isDark ? '#666666' : '#999999'} />
        )}
      </Pressable>
    </Animated.View>
  );
};

// Section Header
const SectionHeader = ({ title, isDark }: { title: string; isDark: boolean }) => (
  <Text style={[styles.sectionHeader, { color: isDark ? '#999999' : '#666666' }]}>
    {title}
  </Text>
);

export default function SettingsScreen() {
  const { resolvedTheme, themeMode, setThemeMode } = useAppStore();
  const { user, isAuthenticated, logout } = useAuthStore();
  const isDark = resolvedTheme === 'dark';

  // Settings state
  const [pushNotifications, setPushNotifications] = useState(true);
  const [emailNotifications, setEmailNotifications] = useState(true);
  const [biometricEnabled, setBiometricEnabled] = useState(false);

  // Handlers
  const handleChangePassword = () => {
    if (!isAuthenticated) {
      Alert.alert('แจ้งเตือน', 'กรุณาเข้าสู่ระบบก่อน');
      return;
    }
    Alert.alert(
      'เปลี่ยนรหัสผ่าน',
      'ระบบจะส่งลิงก์เปลี่ยนรหัสผ่านไปยังอีเมลของคุณ',
      [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: 'ส่งลิงก์', onPress: () => Alert.alert('สำเร็จ', 'ส่งลิงก์ไปยังอีเมลแล้ว') },
      ]
    );
  };

  const handleKYC = () => {
    router.push('/kyc');
  };

  const handleLanguageChange = () => {
    Alert.alert(
      'เลือกภาษา',
      'ขณะนี้รองรับเฉพาะภาษาไทย',
      [
        { text: 'ไทย (เริ่มต้น)', onPress: () => {} },
        { text: 'English (เร็วๆ นี้)', onPress: () => {} },
        { text: 'ยกเลิก', style: 'cancel' },
      ]
    );
  };

  const handlePrivacyPolicy = () => {
    Linking.openURL(APP_INFO.PRIVACY_URL || 'https://main.thaiprompt.online/privacy');
  };

  const handleTermsOfService = () => {
    Linking.openURL(APP_INFO.TERMS_URL || 'https://main.thaiprompt.online/terms');
  };

  const handleRateApp = () => {
    Alert.alert('ให้คะแนนแอพ', 'ขอบคุณที่ใช้งานแอพของเรา!');
  };

  const handleLogout = () => {
    Alert.alert(
      'ออกจากระบบ',
      'คุณต้องการออกจากระบบหรือไม่?',
      [
        { text: 'ยกเลิก', style: 'cancel' },
        {
          text: 'ออกจากระบบ',
          style: 'destructive',
          onPress: async () => {
            await logout();
            router.replace('/login');
          },
        },
      ]
    );
  };

  const handleDeleteAccount = () => {
    Alert.alert(
      'ลบบัญชี',
      'คุณแน่ใจหรือไม่ที่จะลบบัญชี? การดำเนินการนี้ไม่สามารถยกเลิกได้',
      [
        { text: 'ยกเลิก', style: 'cancel' },
        {
          text: 'ลบบัญชี',
          style: 'destructive',
          onPress: () => Alert.alert('แจ้งเตือน', 'กรุณาติดต่อ support@thaiprompt.com เพื่อลบบัญชี'),
        },
      ]
    );
  };

  const getThemeText = () => {
    switch (themeMode) {
      case 'light':
        return 'สว่าง';
      case 'dark':
        return 'มืด (RGB Lava)';
      default:
        return 'ตามระบบ';
    }
  };

  return (
    <LavaBackground variant="default" intensity="low">
      <SafeAreaView style={styles.container} edges={['bottom']}>
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Profile Header */}
          {isAuthenticated && user && (
            <Animated.View entering={FadeInDown.delay(100).springify()} style={styles.profileCard}>
              <LinearGradient
                colors={isDark ? ['#7B2CBF', '#3B82F6'] : ['#D4AF37', '#B8941E']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.profileGradient}
              >
                <View style={styles.avatarContainer}>
                  <Text style={styles.avatarText}>
                    {user.name?.charAt(0).toUpperCase() || 'U'}
                  </Text>
                </View>
                <Text style={styles.profileName}>{user.name}</Text>
                <Text style={styles.profileEmail}>{user.email}</Text>
                <Pressable
                  onPress={() => router.push('/(tabs)/profile')}
                  style={styles.editProfileButton}
                >
                  <Ionicons name="pencil" size={16} color="white" style={{ marginRight: 6 }} />
                  <Text style={styles.editProfileText}>แก้ไขโปรไฟล์</Text>
                </Pressable>
              </LinearGradient>
            </Animated.View>
          )}

          {/* Theme Settings - Prominent */}
          <SectionHeader title="🎨 ธีมและการแสดงผล" isDark={isDark} />
          <Animated.View entering={FadeInUp.delay(150).springify()}>
            <GlassCard style={{ marginBottom: 16 }}>
              <Text style={[styles.themeSectionTitle, { color: isDark ? '#FFFFFF' : '#1F2937' }]}>
                เลือกธีม
              </Text>
              <Text style={[styles.themeSectionSubtitle, { color: isDark ? '#9CA3AF' : '#6B7280' }]}>
                โหมดมืดจะแสดงเอฟเฟค RGB Lava Lamp เรืองแสง
              </Text>

              <ThemeOption
                mode="light"
                currentMode={themeMode}
                icon="sunny"
                title="โหมดสว่าง"
                description="สีสันสดใส เหมาะสำหรับกลางวัน"
                onPress={() => setThemeMode('light')}
                isDark={isDark}
              />

              <ThemeOption
                mode="dark"
                currentMode={themeMode}
                icon="moon"
                title="โหมดมืด (RGB Lava)"
                description="ธีมมืดพร้อมเอฟเฟค Lava Lamp RGB เรืองแสง"
                onPress={() => setThemeMode('dark')}
                isDark={isDark}
              />

              <ThemeOption
                mode="system"
                currentMode={themeMode}
                icon="phone-portrait"
                title="ตามระบบ"
                description="เปลี่ยนตามการตั้งค่าของอุปกรณ์"
                onPress={() => setThemeMode('system')}
                isDark={isDark}
              />
            </GlassCard>

            <SettingItem
              icon="language"
              iconColor="#f093fb"
              title="ภาษา"
              subtitle="ไทย"
              onPress={handleLanguageChange}
              isDark={isDark}
            />
          </Animated.View>

          {/* Account Settings */}
          <SectionHeader title="👤 บัญชี" isDark={isDark} />
          <Animated.View entering={FadeInUp.delay(200).springify()}>
            <SettingItem
              icon="shield-checkmark"
              iconColor="#10B981"
              title="ยืนยันตัวตน (KYC)"
              subtitle="ยืนยันตัวตนเพื่อเพิ่มวงเงิน"
              onPress={handleKYC}
              isDark={isDark}
            />
            <SettingItem
              icon="lock-closed"
              iconColor="#7B2CBF"
              title="เปลี่ยนรหัสผ่าน"
              subtitle="เปลี่ยนรหัสผ่านเข้าสู่ระบบ"
              onPress={handleChangePassword}
              isDark={isDark}
            />
          </Animated.View>

          {/* Notifications */}
          <SectionHeader title="🔔 การแจ้งเตือน" isDark={isDark} />
          <Animated.View entering={FadeInUp.delay(250).springify()}>
            <SettingItem
              icon="notifications"
              iconColor="#FF6B6B"
              title="Push Notification"
              subtitle="รับการแจ้งเตือนบนอุปกรณ์"
              isDark={isDark}
              rightElement={
                <Switch
                  value={pushNotifications}
                  onValueChange={setPushNotifications}
                  trackColor={{ false: '#767577', true: isDark ? '#7B2CBF' : '#3B82F6' }}
                  thumbColor={pushNotifications ? '#FFFFFF' : '#f4f3f4'}
                />
              }
            />
            <SettingItem
              icon="mail"
              iconColor="#4ECDC4"
              title="Email Notification"
              subtitle="รับการแจ้งเตือนทางอีเมล"
              isDark={isDark}
              rightElement={
                <Switch
                  value={emailNotifications}
                  onValueChange={setEmailNotifications}
                  trackColor={{ false: '#767577', true: isDark ? '#7B2CBF' : '#3B82F6' }}
                  thumbColor={emailNotifications ? '#FFFFFF' : '#f4f3f4'}
                />
              }
            />
          </Animated.View>

          {/* Security */}
          <SectionHeader title="🔐 ความปลอดภัย" isDark={isDark} />
          <Animated.View entering={FadeInUp.delay(300).springify()}>
            <SettingItem
              icon="finger-print"
              iconColor="#45B7D1"
              title="Biometric Login"
              subtitle="เข้าสู่ระบบด้วยลายนิ้วมือ/Face ID"
              isDark={isDark}
              rightElement={
                <Switch
                  value={biometricEnabled}
                  onValueChange={setBiometricEnabled}
                  trackColor={{ false: '#767577', true: isDark ? '#7B2CBF' : '#3B82F6' }}
                  thumbColor={biometricEnabled ? '#FFFFFF' : '#f4f3f4'}
                />
              }
            />
          </Animated.View>

          {/* About */}
          <SectionHeader title="ℹ️ เกี่ยวกับ" isDark={isDark} />
          <Animated.View entering={FadeInUp.delay(350).springify()}>
            <SettingItem
              icon="document-text"
              iconColor="#3B82F6"
              title="นโยบายความเป็นส่วนตัว"
              onPress={handlePrivacyPolicy}
              isDark={isDark}
            />
            <SettingItem
              icon="shield"
              iconColor="#8B5CF6"
              title="ข้อกำหนดการใช้งาน"
              onPress={handleTermsOfService}
              isDark={isDark}
            />
            <SettingItem
              icon="star"
              iconColor="#FFD700"
              title="ให้คะแนนแอพ"
              onPress={handleRateApp}
              isDark={isDark}
            />
            <SettingItem
              icon="information-circle"
              iconColor="#6B7280"
              title="เวอร์ชัน"
              subtitle={APP_INFO.VERSION || '1.1.0'}
              isDark={isDark}
              rightElement={<View />}
            />
          </Animated.View>

          {/* Account Actions */}
          {isAuthenticated && (
            <>
              <SectionHeader title="⚙️ จัดการบัญชี" isDark={isDark} />
              <Animated.View entering={FadeInUp.delay(400).springify()}>
                <SettingItem
                  icon="log-out"
                  iconColor="#FF6B6B"
                  title="ออกจากระบบ"
                  onPress={handleLogout}
                  isDark={isDark}
                />
                <SettingItem
                  icon="trash"
                  iconColor="#DC143C"
                  title="ลบบัญชี"
                  subtitle="ลบบัญชีของคุณอย่างถาวร"
                  onPress={handleDeleteAccount}
                  isDark={isDark}
                />
              </Animated.View>
            </>
          )}

          {/* Bottom Spacer */}
          <View style={{ height: 40 }} />
        </ScrollView>
      </SafeAreaView>
    </LavaBackground>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 16,
    paddingTop: 16,
  },
  profileCard: {
    marginBottom: 24,
    borderRadius: 20,
    overflow: 'hidden',
  },
  profileGradient: {
    padding: 24,
    alignItems: 'center',
  },
  avatarContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(255,255,255,0.3)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  avatarText: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  profileName: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  profileEmail: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    marginBottom: 16,
  },
  editProfileButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 8,
    backgroundColor: 'rgba(255,255,255,0.2)',
    borderRadius: 20,
  },
  editProfileText: {
    fontSize: 14,
    color: '#FFFFFF',
    fontWeight: '600',
  },
  sectionHeader: {
    fontSize: 13,
    fontWeight: '600',
    textTransform: 'uppercase',
    letterSpacing: 1,
    marginTop: 16,
    marginBottom: 12,
    marginLeft: 4,
  },
  settingItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 12,
    marginBottom: 8,
  },
  iconContainer: {
    width: 40,
    height: 40,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  settingContent: {
    flex: 1,
  },
  settingTitle: {
    fontSize: 15,
    fontWeight: '600',
  },
  settingSubtitle: {
    fontSize: 12,
    marginTop: 2,
  },
  // Theme Options
  themeSectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  themeSectionSubtitle: {
    fontSize: 13,
    marginBottom: 16,
  },
  themeOption: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 16,
    marginBottom: 12,
    borderWidth: 2,
  },
  themeIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 14,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 14,
  },
  themeContent: {
    flex: 1,
  },
  themeTitle: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 2,
  },
  themeDescription: {
    fontSize: 12,
  },
  checkContainer: {
    marginLeft: 8,
  },
});
