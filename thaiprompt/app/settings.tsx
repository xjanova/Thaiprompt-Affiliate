/**
 * Settings Screen - หน้าตั้งค่า
 * ใช้ StyleSheet แทน Reanimated เพื่อความเสถียร
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
  StatusBar,
  ActivityIndicator,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';
import { startGpsSharing, stopGpsSharing, requestLocationPermission } from '@/services/location';

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
}) => (
  <Pressable
    onPress={onPress}
    style={[
      styles.settingItem,
      { backgroundColor: isDark ? 'rgba(255,255,255,0.05)' : '#FFF' },
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
        <Text style={[styles.settingSubtitle, { color: isDark ? '#9CA3AF' : '#6B7280' }]}>
          {subtitle}
        </Text>
      )}
    </View>
    {rightElement || (
      <Ionicons name="chevron-forward" size={20} color={isDark ? '#666666' : '#999999'} />
    )}
  </Pressable>
);

// Section Header
const SectionHeader = ({ title, isDark }: { title: string; isDark: boolean }) => (
  <Text style={[styles.sectionHeader, { color: isDark ? '#9CA3AF' : '#6B7280' }]}>
    {title}
  </Text>
);

// Theme Option
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
            : '#FFF',
          borderColor: isSelected
            ? '#3B82F6'
            : isDark
            ? 'rgba(255,255,255,0.1)'
            : '#E5E7EB',
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
              : '#F3F4F6',
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
            { color: isSelected ? '#3B82F6' : isDark ? '#FFFFFF' : '#1F2937' },
          ]}
        >
          {title}
        </Text>
        <Text style={[styles.themeDescription, { color: isDark ? '#9CA3AF' : '#6B7280' }]}>
          {description}
        </Text>
      </View>
      {isSelected && (
        <Ionicons name="checkmark-circle" size={24} color="#3B82F6" />
      )}
    </Pressable>
  );
};

export default function SettingsScreen() {
  const { resolvedTheme, themeMode, setThemeMode, gpsSharing, setGpsSharing } = useAppStore();
  const { user, isAuthenticated, logout } = useAuthStore();
  const isDark = resolvedTheme === 'dark';

  // Settings state
  const [pushNotifications, setPushNotifications] = useState(true);
  const [emailNotifications, setEmailNotifications] = useState(true);
  const [biometricEnabled, setBiometricEnabled] = useState(false);
  const [isGpsLoading, setIsGpsLoading] = useState(false);

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

  // ⭐ GPS Sharing Handler
  const handleGpsSharingToggle = async (enabled: boolean) => {
    if (!isAuthenticated) {
      Alert.alert('แจ้งเตือน', 'กรุณาเข้าสู่ระบบก่อน');
      return;
    }

    setIsGpsLoading(true);

    try {
      if (enabled) {
        // ขอสิทธิ์ก่อน
        const permissions = await requestLocationPermission();
        if (!permissions.foreground) {
          Alert.alert(
            'ต้องการสิทธิ์',
            'กรุณาอนุญาตการเข้าถึงตำแหน่งในการตั้งค่าเพื่อเปิดใช้งาน GPS Sharing',
            [{ text: 'ตกลง' }]
          );
          setIsGpsLoading(false);
          return;
        }

        // เริ่ม GPS Sharing
        const success = await startGpsSharing();
        if (success) {
          await setGpsSharing(true);
          Alert.alert('เปิดใช้งาน', 'เริ่มแชร์ตำแหน่ง GPS แล้ว\nAdmin จะสามารถเห็นตำแหน่งของคุณได้');
        } else {
          Alert.alert('ล้มเหลว', 'ไม่สามารถเริ่ม GPS Sharing ได้');
        }
      } else {
        // หยุด GPS Sharing
        await stopGpsSharing();
        await setGpsSharing(false);
        Alert.alert('ปิดใช้งาน', 'หยุดแชร์ตำแหน่ง GPS แล้ว');
      }
    } catch (error) {
      console.error('GPS Sharing toggle error:', error);
      Alert.alert('ข้อผิดพลาด', 'ไม่สามารถเปลี่ยนการตั้งค่า GPS ได้');
    } finally {
      setIsGpsLoading(false);
    }
  };

  return (
    <View style={[styles.container, { backgroundColor: isDark ? '#0F0F23' : '#F9FAFB' }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />

      {/* Header */}
      <View style={[styles.header, { borderBottomColor: isDark ? 'rgba(255,255,255,0.1)' : '#E5E7EB' }]}>
        <Pressable style={styles.backButton} onPress={() => router.back()}>
          <Ionicons name="arrow-back" size={24} color={isDark ? '#FFF' : '#1F2937'} />
        </Pressable>
        <Text style={[styles.headerTitle, { color: isDark ? '#FFF' : '#1F2937' }]}>
          ตั้งค่า
        </Text>
        <View style={styles.placeholder} />
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Profile Header */}
        {isAuthenticated && user && (
          <View style={styles.profileCard}>
            <LinearGradient
              colors={isDark ? ['#7B2CBF', '#3B82F6'] : ['#3B82F6', '#60A5FA']}
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
                <Ionicons name="pencil" size={16} color="white" />
                <Text style={styles.editProfileText}>แก้ไขโปรไฟล์</Text>
              </Pressable>
            </LinearGradient>
          </View>
        )}

        {/* Theme Settings */}
        <SectionHeader title="🎨 ธีมและการแสดงผล" isDark={isDark} />
        <View style={[styles.card, { backgroundColor: isDark ? 'rgba(255,255,255,0.05)' : '#FFF' }]}>
          <Text style={[styles.cardTitle, { color: isDark ? '#FFFFFF' : '#1F2937' }]}>
            เลือกธีม
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
            title="โหมดมืด"
            description="ธีมมืดถนอมสายตา"
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
        </View>

        <SettingItem
          icon="language"
          iconColor="#f093fb"
          title="ภาษา"
          subtitle="ไทย"
          onPress={handleLanguageChange}
          isDark={isDark}
        />

        {/* Account Settings */}
        <SectionHeader title="👤 บัญชี" isDark={isDark} />
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

        {/* Notifications */}
        <SectionHeader title="🔔 การแจ้งเตือน" isDark={isDark} />
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

        {/* Security */}
        <SectionHeader title="🔐 ความปลอดภัย" isDark={isDark} />
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

        {/* ⭐ GPS Sharing Toggle */}
        <SettingItem
          icon="location"
          iconColor="#10B981"
          title="แชร์ตำแหน่ง GPS"
          subtitle={gpsSharing ? '📍 กำลังแชร์ตำแหน่งอยู่...' : 'แชร์ตำแหน่งให้ Admin GPS Monitor'}
          isDark={isDark}
          rightElement={
            isGpsLoading ? (
              <ActivityIndicator size="small" color={isDark ? '#7B2CBF' : '#3B82F6'} />
            ) : (
              <Switch
                value={gpsSharing}
                onValueChange={handleGpsSharingToggle}
                trackColor={{ false: '#767577', true: '#10B981' }}
                thumbColor={gpsSharing ? '#FFFFFF' : '#f4f3f4'}
              />
            )
          }
        />

        {/* About */}
        <SectionHeader title="ℹ️ เกี่ยวกับ" isDark={isDark} />
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

        {/* Account Actions */}
        {isAuthenticated && (
          <>
            <SectionHeader title="⚙️ จัดการบัญชี" isDark={isDark} />
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
          </>
        )}

        <View style={{ height: 40 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingTop: 50,
    paddingBottom: 12,
    borderBottomWidth: 1,
  },
  backButton: {
    padding: 8,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: 'bold',
  },
  placeholder: {
    width: 40,
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
    gap: 6,
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
  card: {
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '600',
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
});
