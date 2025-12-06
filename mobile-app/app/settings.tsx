/**
 * Settings Screen - หน้าตั้งค่า
 * - ตั้งค่าโปรไฟล์
 * - ตั้งค่าการแจ้งเตือน
 * - ตั้งค่าธีม (Dark/Light)
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
import { APP_INFO } from '@/config/appConfig';

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
  const { resolvedTheme, setTheme, theme } = useAppStore();
  const { user, isAuthenticated, logout } = useAuthStore();
  const isDark = resolvedTheme === 'dark';

  // Settings state
  const [pushNotifications, setPushNotifications] = useState(true);
  const [emailNotifications, setEmailNotifications] = useState(true);
  const [biometricEnabled, setBiometricEnabled] = useState(false);

  // Handlers
  const handleThemeChange = () => {
    Alert.alert(
      'เลือกธีม',
      'เลือกธีมที่ต้องการ',
      [
        { text: 'สว่าง', onPress: () => setTheme('light') },
        { text: 'มืด', onPress: () => setTheme('dark') },
        { text: 'ตามระบบ', onPress: () => setTheme('system') },
        { text: 'ยกเลิก', style: 'cancel' },
      ]
    );
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
    switch (theme) {
      case 'light':
        return 'สว่าง';
      case 'dark':
        return 'มืด';
      default:
        return 'ตามระบบ';
    }
  };

  return (
    <View style={[styles.container, { backgroundColor: isDark ? '#0F172A' : '#F9FAFB' }]}>
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Profile Header */}
        {isAuthenticated && user && (
          <Animated.View entering={FadeInDown.delay(100).springify()} style={styles.profileCard}>
            <LinearGradient
              colors={['#D4AF37', '#B8941E']}
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
                <Text style={styles.editProfileText}>แก้ไขโปรไฟล์</Text>
              </Pressable>
            </LinearGradient>
          </Animated.View>
        )}

        {/* Account Settings */}
        <SectionHeader title="บัญชี" isDark={isDark} />
        <Animated.View entering={FadeInUp.delay(150).springify()}>
          <SettingItem
            icon="person-outline"
            title="ยืนยันตัวตน (KYC)"
            subtitle="ยืนยันตัวตนเพื่อเพิ่มวงเงิน"
            onPress={handleKYC}
            isDark={isDark}
          />
          <SettingItem
            icon="lock-closed-outline"
            iconColor="#7B2CBF"
            title="เปลี่ยนรหัสผ่าน"
            subtitle="เปลี่ยนรหัสผ่านเข้าสู่ระบบ"
            onPress={handleChangePassword}
            isDark={isDark}
          />
        </Animated.View>

        {/* Notifications */}
        <SectionHeader title="การแจ้งเตือน" isDark={isDark} />
        <Animated.View entering={FadeInUp.delay(200).springify()}>
          <SettingItem
            icon="notifications-outline"
            iconColor="#FF6B6B"
            title="Push Notification"
            subtitle="รับการแจ้งเตือนบนอุปกรณ์"
            isDark={isDark}
            rightElement={
              <Switch
                value={pushNotifications}
                onValueChange={setPushNotifications}
                trackColor={{ false: '#767577', true: '#D4AF37' }}
                thumbColor={pushNotifications ? '#FFFFFF' : '#f4f3f4'}
              />
            }
          />
          <SettingItem
            icon="mail-outline"
            iconColor="#4ECDC4"
            title="Email Notification"
            subtitle="รับการแจ้งเตือนทางอีเมล"
            isDark={isDark}
            rightElement={
              <Switch
                value={emailNotifications}
                onValueChange={setEmailNotifications}
                trackColor={{ false: '#767577', true: '#D4AF37' }}
                thumbColor={emailNotifications ? '#FFFFFF' : '#f4f3f4'}
              />
            }
          />
        </Animated.View>

        {/* Appearance */}
        <SectionHeader title="การแสดงผล" isDark={isDark} />
        <Animated.View entering={FadeInUp.delay(250).springify()}>
          <SettingItem
            icon={isDark ? 'moon-outline' : 'sunny-outline'}
            iconColor="#667eea"
            title="ธีม"
            subtitle={getThemeText()}
            onPress={handleThemeChange}
            isDark={isDark}
          />
          <SettingItem
            icon="language-outline"
            iconColor="#f093fb"
            title="ภาษา"
            subtitle="ไทย"
            onPress={handleLanguageChange}
            isDark={isDark}
          />
        </Animated.View>

        {/* Security */}
        <SectionHeader title="ความปลอดภัย" isDark={isDark} />
        <Animated.View entering={FadeInUp.delay(300).springify()}>
          <SettingItem
            icon="finger-print-outline"
            iconColor="#45B7D1"
            title="Biometric Login"
            subtitle="เข้าสู่ระบบด้วยลายนิ้วมือ/Face ID"
            isDark={isDark}
            rightElement={
              <Switch
                value={biometricEnabled}
                onValueChange={setBiometricEnabled}
                trackColor={{ false: '#767577', true: '#D4AF37' }}
                thumbColor={biometricEnabled ? '#FFFFFF' : '#f4f3f4'}
              />
            }
          />
        </Animated.View>

        {/* About */}
        <SectionHeader title="เกี่ยวกับ" isDark={isDark} />
        <Animated.View entering={FadeInUp.delay(350).springify()}>
          <SettingItem
            icon="document-text-outline"
            title="นโยบายความเป็นส่วนตัว"
            onPress={handlePrivacyPolicy}
            isDark={isDark}
          />
          <SettingItem
            icon="shield-checkmark-outline"
            title="ข้อกำหนดการใช้งาน"
            onPress={handleTermsOfService}
            isDark={isDark}
          />
          <SettingItem
            icon="star-outline"
            iconColor="#FFD700"
            title="ให้คะแนนแอพ"
            onPress={handleRateApp}
            isDark={isDark}
          />
          <SettingItem
            icon="information-circle-outline"
            title="เวอร์ชัน"
            subtitle={APP_INFO.VERSION || '1.1.0'}
            isDark={isDark}
            rightElement={<View />}
          />
        </Animated.View>

        {/* Account Actions */}
        {isAuthenticated && (
          <>
            <SectionHeader title="จัดการบัญชี" isDark={isDark} />
            <Animated.View entering={FadeInUp.delay(400).springify()}>
              <SettingItem
                icon="log-out-outline"
                iconColor="#FF6B6B"
                title="ออกจากระบบ"
                onPress={handleLogout}
                isDark={isDark}
              />
              <SettingItem
                icon="trash-outline"
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
    </View>
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
});
