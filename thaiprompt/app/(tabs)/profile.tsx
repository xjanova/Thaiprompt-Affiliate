/**
 * Profile Screen - Premium Full Featured Version
 * ใช้ StyleSheet แทน NativeWind
 *
 * Features:
 * - แสดงรูปโปรไฟล์จาก API (sync กับเว็บ)
 * - เปลี่ยนรูปโปรไฟล์ได้
 * - เปลี่ยนรหัสผ่านได้
 * - โหมดมืด/สว่างใช้งานได้
 * - รหัสแนะนำ = รหัสสมาชิก
 */

import React, { useState, useEffect } from 'react';
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
  Image,
  Modal,
  TextInput,
  ActivityIndicator,
  Share,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import * as Clipboard from 'expo-clipboard';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { uploadAvatar, changePassword } from '@/services/api';
import { APP_INFO } from '@/config/appConfig';
import { API_BASE_URL } from '@/constants';

// Menu Item Component
const MenuItem = ({
  icon,
  label,
  value,
  onPress,
  showArrow = true,
  danger = false,
  isDark = true,
}: {
  icon: string;
  label: string;
  value?: string;
  onPress: () => void;
  showArrow?: boolean;
  danger?: boolean;
  isDark?: boolean;
}) => (
  <Pressable
    onPress={onPress}
    style={({ pressed }) => [
      styles.menuItem,
      isDark ? styles.menuItemDark : styles.menuItemLight,
      { opacity: pressed ? 0.7 : 1 },
    ]}
  >
    <View style={[styles.menuIcon, danger && styles.menuIconDanger]}>
      <Ionicons
        name={icon as any}
        size={18}
        color={danger ? '#EF4444' : (isDark ? '#9CA3AF' : '#6B7280')}
      />
    </View>
    <Text style={[
      styles.menuLabel,
      danger && styles.menuLabelDanger,
      !isDark && styles.menuLabelLight,
    ]}>
      {label}
    </Text>
    {value && (
      <Text style={[styles.menuValue, !isDark && styles.menuValueLight]}>
        {value}
      </Text>
    )}
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
  isDark = true,
}: {
  icon: string;
  label: string;
  value: boolean;
  onValueChange: (value: boolean) => void;
  isDark?: boolean;
}) => (
  <View style={[styles.menuItem, isDark ? styles.menuItemDark : styles.menuItemLight]}>
    <View style={styles.menuIcon}>
      <Ionicons name={icon as any} size={18} color={isDark ? '#9CA3AF' : '#6B7280'} />
    </View>
    <Text style={[styles.menuLabel, !isDark && styles.menuLabelLight]}>{label}</Text>
    <Switch
      value={value}
      onValueChange={onValueChange}
      trackColor={{ false: '#374151', true: '#3B82F6' }}
      thumbColor="#FFFFFF"
    />
  </View>
);

// Format member ID as referral code
const formatMemberId = (id: number): string => {
  return `TP${String(id).padStart(6, '0')}`;
};

export default function ProfileScreen() {
  const { user, isAuthenticated, logout, refreshUser, updateUser } = useAuthStore();
  const { resolvedTheme, themeMode, setThemeMode } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [refreshing, setRefreshing] = useState(false);
  const [isUploading, setIsUploading] = useState(false);

  // Password Modal State
  const [showPasswordModal, setShowPasswordModal] = useState(false);
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [isChangingPassword, setIsChangingPassword] = useState(false);
  const [showCurrentPassword, setShowCurrentPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);

  // Member ID as referral code
  const memberCode = user?.id ? formatMemberId(user.id) : '';
  // ใช้ referralCode จาก user ถ้ามี หรือใช้ memberCode
  const referralCode = user?.referralCode || memberCode;

  // Get avatar URL
  const getAvatarUrl = () => {
    if (user?.avatar) {
      // ถ้าเป็น full URL ใช้เลย
      if (user.avatar.startsWith('http')) {
        return user.avatar;
      }
      // ถ้าเป็น relative path ต่อกับ base URL
      const baseUrl = API_BASE_URL.replace('/api/v1', '');
      return `${baseUrl}${user.avatar.startsWith('/') ? '' : '/'}${user.avatar}`;
    }
    return null;
  };

  const avatarUrl = getAvatarUrl();

  const onRefresh = async () => {
    setRefreshing(true);
    await refreshUser();
    setRefreshing(false);
  };

  // Handle pick image
  const handlePickImage = async () => {
    try {
      // ขอ permission
      const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('ต้องการสิทธิ์', 'กรุณาอนุญาตให้เข้าถึงรูปภาพในการตั้งค่า');
        return;
      }

      // เลือกรูป
      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ImagePicker.MediaTypeOptions.Images,
        allowsEditing: true,
        aspect: [1, 1],
        quality: 0.8,
      });

      if (!result.canceled && result.assets[0]) {
        setIsUploading(true);
        const response = await uploadAvatar(result.assets[0].uri);

        if (response.success && response.data) {
          // อัพเดท user ใน store
          updateUser({ avatar: response.data.avatarUrl });
          Alert.alert('สำเร็จ', 'เปลี่ยนรูปโปรไฟล์แล้ว');
        } else {
          Alert.alert('ผิดพลาด', response.message || 'ไม่สามารถอัพโหลดรูปได้');
        }
        setIsUploading(false);
      }
    } catch (error) {
      setIsUploading(false);
      Alert.alert('ผิดพลาด', 'เกิดข้อผิดพลาดในการเลือกรูป');
    }
  };

  // Handle take photo
  const handleTakePhoto = async () => {
    try {
      const { status } = await ImagePicker.requestCameraPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('ต้องการสิทธิ์', 'กรุณาอนุญาตให้ใช้กล้องในการตั้งค่า');
        return;
      }

      const result = await ImagePicker.launchCameraAsync({
        allowsEditing: true,
        aspect: [1, 1],
        quality: 0.8,
      });

      if (!result.canceled && result.assets[0]) {
        setIsUploading(true);
        const response = await uploadAvatar(result.assets[0].uri);

        if (response.success && response.data) {
          updateUser({ avatar: response.data.avatarUrl });
          Alert.alert('สำเร็จ', 'เปลี่ยนรูปโปรไฟล์แล้ว');
        } else {
          Alert.alert('ผิดพลาด', response.message || 'ไม่สามารถอัพโหลดรูปได้');
        }
        setIsUploading(false);
      }
    } catch (error) {
      setIsUploading(false);
      Alert.alert('ผิดพลาด', 'เกิดข้อผิดพลาดในการถ่ายรูป');
    }
  };

  // Show image picker options
  const showImageOptions = () => {
    Alert.alert(
      'เปลี่ยนรูปโปรไฟล์',
      'เลือกวิธีการเปลี่ยนรูป',
      [
        { text: 'ถ่ายรูป', onPress: handleTakePhoto },
        { text: 'เลือกจากอัลบั้ม', onPress: handlePickImage },
        { text: 'ยกเลิก', style: 'cancel' },
      ]
    );
  };

  // Handle change password
  const handleChangePassword = async () => {
    if (!currentPassword || !newPassword || !confirmPassword) {
      Alert.alert('กรุณากรอกข้อมูล', 'กรุณากรอกรหัสผ่านให้ครบทุกช่อง');
      return;
    }

    if (newPassword.length < 8) {
      Alert.alert('รหัสผ่านสั้นเกินไป', 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร');
      return;
    }

    if (newPassword !== confirmPassword) {
      Alert.alert('รหัสผ่านไม่ตรงกัน', 'กรุณากรอกรหัสผ่านใหม่ให้ตรงกันทั้งสองช่อง');
      return;
    }

    setIsChangingPassword(true);
    try {
      const response = await changePassword({
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirmPassword,
      });

      if (response.success) {
        Alert.alert('สำเร็จ', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
        setShowPasswordModal(false);
        setCurrentPassword('');
        setNewPassword('');
        setConfirmPassword('');
      } else {
        Alert.alert('ผิดพลาด', response.message || 'ไม่สามารถเปลี่ยนรหัสผ่านได้');
      }
    } catch (error) {
      Alert.alert('ผิดพลาด', 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน');
    } finally {
      setIsChangingPassword(false);
    }
  };

  // Handle theme toggle
  const handleThemeToggle = (isDarkMode: boolean) => {
    setThemeMode(isDarkMode ? 'dark' : 'light');
  };

  // Handle share referral
  const handleShareReferral = async () => {
    try {
      const message = `มาเป็นสมาชิก Thaiprompt กับผม! ใช้รหัสแนะนำ: ${referralCode}\n\nสมัครได้ที่: https://main.thaiprompt.online/register?ref=${referralCode}`;
      await Share.share({ message });
    } catch (error) {
      console.error('Share error:', error);
    }
  };

  // Handle copy referral code
  const handleCopyReferral = async () => {
    await Clipboard.setStringAsync(referralCode);
    Alert.alert('คัดลอกแล้ว', `รหัสแนะนำ ${referralCode} ถูกคัดลอกแล้ว`);
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
      <View style={[styles.container, !isDark && styles.containerLight]}>
        <StatusBar
          barStyle={isDark ? 'light-content' : 'dark-content'}
          backgroundColor={isDark ? '#0F0F23' : '#FFFFFF'}
        />
        <View style={styles.notLoggedIn}>
          <Ionicons name="person-circle-outline" size={80} color="#4B5563" />
          <Text style={[styles.notLoggedInTitle, !isDark && styles.textDark]}>
            โปรไฟล์ของคุณ
          </Text>
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
    <View style={[styles.container, !isDark && styles.containerLight]}>
      <StatusBar
        barStyle={isDark ? 'light-content' : 'dark-content'}
        backgroundColor={isDark ? '#0F0F23' : '#FFFFFF'}
      />

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
            {/* Avatar - Pressable to change */}
            <Pressable onPress={showImageOptions} disabled={isUploading}>
              <View style={styles.avatarContainer}>
                {isUploading ? (
                  <View style={styles.avatar}>
                    <ActivityIndicator size="small" color="#3B82F6" />
                  </View>
                ) : avatarUrl ? (
                  <Image
                    source={{ uri: avatarUrl }}
                    style={styles.avatarImage}
                    resizeMode="cover"
                  />
                ) : (
                  <View style={styles.avatar}>
                    <Text style={styles.avatarText}>
                      {user?.name?.charAt(0).toUpperCase() || 'U'}
                    </Text>
                  </View>
                )}
                <View style={styles.avatarEditBadge}>
                  <Ionicons name="camera" size={12} color="#FFFFFF" />
                </View>
              </View>
            </Pressable>

            {/* Info */}
            <View style={styles.profileInfo}>
              <Text style={styles.profileName}>{user?.name}</Text>
              <Text style={styles.profileEmail}>{user?.email}</Text>
              <View style={styles.profileBadges}>
                <View style={styles.badge}>
                  <Text style={styles.badgeText}>{user?.role || 'Member'}</Text>
                </View>
                <Pressable
                  style={[styles.badge, { backgroundColor: 'rgba(16,185,129,0.3)' }]}
                  onPress={handleCopyReferral}
                >
                  <Ionicons name="copy-outline" size={10} color="#FFFFFF" />
                  <Text style={[styles.badgeText, { marginLeft: 4 }]}>
                    {referralCode}
                  </Text>
                </Pressable>
              </View>
            </View>
          </View>
        </LinearGradient>

        {/* Member ID Card */}
        <View style={[styles.memberCard, !isDark && styles.memberCardLight]}>
          <View style={styles.memberCardHeader}>
            <Ionicons name="id-card-outline" size={20} color="#3B82F6" />
            <Text style={[styles.memberCardTitle, !isDark && styles.textDark]}>
              รหัสสมาชิก
            </Text>
          </View>
          <Text style={styles.memberCodeLarge}>{memberCode}</Text>
          <Text style={[styles.memberCardHint, !isDark && { color: '#6B7280' }]}>
            ใช้รหัสนี้ในการแนะนำสมาชิกใหม่
          </Text>
          <View style={styles.memberCardActions}>
            <Pressable style={styles.memberCardBtn} onPress={handleCopyReferral}>
              <Ionicons name="copy-outline" size={16} color="#3B82F6" />
              <Text style={styles.memberCardBtnText}>คัดลอก</Text>
            </Pressable>
            <Pressable style={styles.memberCardBtn} onPress={handleShareReferral}>
              <Ionicons name="share-social-outline" size={16} color="#3B82F6" />
              <Text style={styles.memberCardBtnText}>แชร์</Text>
            </Pressable>
          </View>
        </View>

        {/* Menu Sections */}
        <View style={styles.section}>
          <Text style={[styles.sectionTitle, !isDark && styles.sectionTitleLight]}>
            บัญชี
          </Text>
          <MenuItem
            icon="person-outline"
            label="แก้ไขโปรไฟล์"
            onPress={() => router.push('/edit-profile')}
            isDark={isDark}
          />
          <MenuItem
            icon="key-outline"
            label="เปลี่ยนรหัสผ่าน"
            onPress={() => setShowPasswordModal(true)}
            isDark={isDark}
          />
          <MenuItem
            icon="shield-checkmark-outline"
            label="ยืนยันตัวตน (KYC)"
            onPress={() => router.push('/kyc')}
            isDark={isDark}
          />
        </View>

        <View style={styles.section}>
          <Text style={[styles.sectionTitle, !isDark && styles.sectionTitleLight]}>
            การแนะนำ
          </Text>
          <MenuItem
            icon="share-social-outline"
            label="แนะนำเพื่อน"
            value={referralCode}
            onPress={handleShareReferral}
            isDark={isDark}
          />
          <MenuItem
            icon="qr-code-outline"
            label="QR Code ของฉัน"
            onPress={() => router.push('/referral')}
            isDark={isDark}
          />
          <MenuItem
            icon="people-outline"
            label="ทีมของฉัน"
            onPress={() => router.push('/referral')}
            isDark={isDark}
          />
        </View>

        <View style={styles.section}>
          <Text style={[styles.sectionTitle, !isDark && styles.sectionTitleLight]}>
            ตั้งค่า
          </Text>
          <ToggleItem
            icon="moon-outline"
            label="โหมดมืด"
            value={resolvedTheme === 'dark'}
            onValueChange={handleThemeToggle}
            isDark={isDark}
          />
          <MenuItem
            icon="notifications-outline"
            label="การแจ้งเตือน"
            onPress={() => router.push('/notifications')}
            isDark={isDark}
          />
          <MenuItem
            icon="language-outline"
            label="ภาษา"
            value="ไทย"
            onPress={() => Alert.alert('ภาษา', 'ขณะนี้รองรับเฉพาะภาษาไทย')}
            isDark={isDark}
          />
        </View>

        <View style={styles.section}>
          <Text style={[styles.sectionTitle, !isDark && styles.sectionTitleLight]}>
            ช่วยเหลือ
          </Text>
          <MenuItem
            icon="help-circle-outline"
            label="ศูนย์ช่วยเหลือ"
            onPress={() => router.push('/help')}
            isDark={isDark}
          />
          <MenuItem
            icon="chatbubble-outline"
            label="ติดต่อเรา"
            onPress={() => router.push('/support')}
            isDark={isDark}
          />
          <MenuItem
            icon="document-text-outline"
            label="เงื่อนไขการใช้งาน"
            onPress={() => router.push('/terms')}
            isDark={isDark}
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
            isDark={isDark}
          />
        </View>

        <View style={[styles.section, { marginBottom: 100 }]}>
          <MenuItem
            icon="log-out-outline"
            label="ออกจากระบบ"
            onPress={handleLogout}
            showArrow={false}
            danger
            isDark={isDark}
          />
        </View>
      </ScrollView>

      {/* Change Password Modal */}
      <Modal
        visible={showPasswordModal}
        transparent
        animationType="slide"
        onRequestClose={() => setShowPasswordModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={[styles.modalContent, !isDark && styles.modalContentLight]}>
            <View style={styles.modalHeader}>
              <Text style={[styles.modalTitle, !isDark && styles.textDark]}>
                เปลี่ยนรหัสผ่าน
              </Text>
              <Pressable onPress={() => setShowPasswordModal(false)}>
                <Ionicons
                  name="close"
                  size={24}
                  color={isDark ? '#FFFFFF' : '#1F2937'}
                />
              </Pressable>
            </View>

            {/* Current Password */}
            <View style={styles.inputGroup}>
              <Text style={[styles.inputLabel, !isDark && { color: '#374151' }]}>
                รหัสผ่านปัจจุบัน
              </Text>
              <View style={[styles.inputContainer, !isDark && styles.inputContainerLight]}>
                <TextInput
                  style={[styles.input, !isDark && styles.inputLight]}
                  placeholder="กรอกรหัสผ่านปัจจุบัน"
                  placeholderTextColor="#9CA3AF"
                  secureTextEntry={!showCurrentPassword}
                  value={currentPassword}
                  onChangeText={setCurrentPassword}
                />
                <Pressable onPress={() => setShowCurrentPassword(!showCurrentPassword)}>
                  <Ionicons
                    name={showCurrentPassword ? 'eye-off-outline' : 'eye-outline'}
                    size={20}
                    color="#9CA3AF"
                  />
                </Pressable>
              </View>
            </View>

            {/* New Password */}
            <View style={styles.inputGroup}>
              <Text style={[styles.inputLabel, !isDark && { color: '#374151' }]}>
                รหัสผ่านใหม่
              </Text>
              <View style={[styles.inputContainer, !isDark && styles.inputContainerLight]}>
                <TextInput
                  style={[styles.input, !isDark && styles.inputLight]}
                  placeholder="กรอกรหัสผ่านใหม่ (อย่างน้อย 8 ตัว)"
                  placeholderTextColor="#9CA3AF"
                  secureTextEntry={!showNewPassword}
                  value={newPassword}
                  onChangeText={setNewPassword}
                />
                <Pressable onPress={() => setShowNewPassword(!showNewPassword)}>
                  <Ionicons
                    name={showNewPassword ? 'eye-off-outline' : 'eye-outline'}
                    size={20}
                    color="#9CA3AF"
                  />
                </Pressable>
              </View>
            </View>

            {/* Confirm Password */}
            <View style={styles.inputGroup}>
              <Text style={[styles.inputLabel, !isDark && { color: '#374151' }]}>
                ยืนยันรหัสผ่านใหม่
              </Text>
              <View style={[styles.inputContainer, !isDark && styles.inputContainerLight]}>
                <TextInput
                  style={[styles.input, !isDark && styles.inputLight]}
                  placeholder="กรอกรหัสผ่านใหม่อีกครั้ง"
                  placeholderTextColor="#9CA3AF"
                  secureTextEntry={!showNewPassword}
                  value={confirmPassword}
                  onChangeText={setConfirmPassword}
                />
              </View>
            </View>

            {/* Submit Button */}
            <Pressable
              style={[
                styles.submitButton,
                isChangingPassword && styles.submitButtonDisabled,
              ]}
              onPress={handleChangePassword}
              disabled={isChangingPassword}
            >
              {isChangingPassword ? (
                <ActivityIndicator color="#FFFFFF" />
              ) : (
                <Text style={styles.submitButtonText}>เปลี่ยนรหัสผ่าน</Text>
              )}
            </Pressable>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  containerLight: {
    backgroundColor: '#F9FAFB',
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
  textDark: {
    color: '#1F2937',
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
  avatarContainer: {
    position: 'relative',
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
  avatarImage: {
    width: 72,
    height: 72,
    borderRadius: 36,
    marginRight: 16,
    borderWidth: 3,
    borderColor: '#FFFFFF',
  },
  avatarText: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#3B82F6',
  },
  avatarEditBadge: {
    position: 'absolute',
    bottom: 0,
    right: 12,
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: '#3B82F6',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: '#FFFFFF',
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
    flexWrap: 'wrap',
  },
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 10,
    marginRight: 8,
    marginBottom: 4,
  },
  badgeText: {
    fontSize: 11,
    color: '#FFFFFF',
    fontWeight: '500',
  },

  // Member Card
  memberCard: {
    marginHorizontal: 20,
    marginTop: -16,
    backgroundColor: 'rgba(59,130,246,0.1)',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: 'rgba(59,130,246,0.3)',
  },
  memberCardLight: {
    backgroundColor: '#FFFFFF',
    borderColor: '#E5E7EB',
  },
  memberCardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  memberCardTitle: {
    fontSize: 14,
    color: '#FFFFFF',
    marginLeft: 8,
    fontWeight: '500',
  },
  memberCodeLarge: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#3B82F6',
    letterSpacing: 2,
  },
  memberCardHint: {
    fontSize: 12,
    color: '#9CA3AF',
    marginTop: 4,
  },
  memberCardActions: {
    flexDirection: 'row',
    marginTop: 12,
  },
  memberCardBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 8,
    backgroundColor: 'rgba(59,130,246,0.1)',
    marginRight: 12,
  },
  memberCardBtnText: {
    fontSize: 14,
    color: '#3B82F6',
    marginLeft: 6,
    fontWeight: '500',
  },

  // Section
  section: {
    marginTop: 20,
  },
  sectionTitle: {
    fontSize: 12,
    fontWeight: '600',
    color: '#9CA3AF',
    textTransform: 'uppercase',
    paddingHorizontal: 20,
    marginBottom: 8,
  },
  sectionTitleLight: {
    color: '#6B7280',
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 14,
    paddingHorizontal: 16,
    marginBottom: 1,
  },
  menuItemDark: {
    backgroundColor: 'rgba(255,255,255,0.05)',
  },
  menuItemLight: {
    backgroundColor: '#FFFFFF',
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
  menuLabelLight: {
    color: '#1F2937',
  },
  menuLabelDanger: {
    color: '#EF4444',
  },
  menuValue: {
    fontSize: 14,
    color: '#9CA3AF',
    marginRight: 8,
  },
  menuValueLight: {
    color: '#6B7280',
  },

  // Modal
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.6)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#1A1A2E',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: 24,
    maxHeight: '80%',
  },
  modalContentLight: {
    backgroundColor: '#FFFFFF',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 24,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  inputGroup: {
    marginBottom: 16,
  },
  inputLabel: {
    fontSize: 14,
    color: '#9CA3AF',
    marginBottom: 8,
  },
  inputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderRadius: 12,
    paddingHorizontal: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  inputContainerLight: {
    backgroundColor: '#F3F4F6',
    borderColor: '#E5E7EB',
  },
  input: {
    flex: 1,
    paddingVertical: 14,
    fontSize: 16,
    color: '#FFFFFF',
  },
  inputLight: {
    color: '#1F2937',
  },
  submitButton: {
    backgroundColor: '#3B82F6',
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 8,
  },
  submitButtonDisabled: {
    opacity: 0.6,
  },
  submitButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
});
