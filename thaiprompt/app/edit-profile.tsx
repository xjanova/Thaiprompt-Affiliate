/**
 * Edit Profile Screen - เขียนใหม่ทั้งหมด
 * - Design สะอาด ใช้ StyleSheet ล้วน
 * - Upload และ Update ใช้ fetch API ตรงๆ
 */

import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  Image,
  ActivityIndicator,
  TextInput,
  StyleSheet,
  StatusBar,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import * as SecureStore from 'expo-secure-store';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { API_BASE_URL, STORAGE_KEYS } from '@/constants';
import { getAvatarUrl, getAvatarInitial } from '@/utils/user';

// =====================================================
// API Functions - เขียนตรงๆ ไม่ผ่าน api.ts
// =====================================================

const getToken = async (): Promise<string | null> => {
  return await SecureStore.getItemAsync(STORAGE_KEYS.AUTH_TOKEN);
};

const updateProfileApi = async (data: {
  name?: string;
  phone?: string;
  address?: string;
  bio?: string;
  bank_name?: string;
  bank_account?: string;
  bank_account_name?: string;
}): Promise<{ success: boolean; message?: string; data?: any }> => {
  const token = await getToken();
  if (!token) {
    return { success: false, message: 'กรุณาเข้าสู่ระบบใหม่' };
  }

  try {
    console.log('📝 Updating profile...', data);

    const response = await fetch(`${API_BASE_URL}/profile`, {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify(data),
    });

    const result = await response.json();
    console.log('📝 Update result:', response.status, result);

    if (!response.ok) {
      return {
        success: false,
        message: result.message || `อัพเดทไม่สำเร็จ (${response.status})`,
      };
    }

    return result;
  } catch (error: any) {
    console.error('❌ Update profile error:', error);
    return {
      success: false,
      message: error.message || 'เกิดข้อผิดพลาด',
    };
  }
};

const uploadAvatarApi = async (
  imageUri: string
): Promise<{ success: boolean; message?: string; data?: any }> => {
  const token = await getToken();
  if (!token) {
    return { success: false, message: 'กรุณาเข้าสู่ระบบใหม่' };
  }

  try {
    const filename = imageUri.split('/').pop() || 'avatar.jpg';
    const ext = filename.split('.').pop()?.toLowerCase() || 'jpg';
    const mimeType = ext === 'png' ? 'image/png' : 'image/jpeg';

    console.log('📸 Uploading avatar...', { filename, mimeType });

    const formData = new FormData();
    formData.append('avatar', {
      uri: imageUri,
      name: filename,
      type: mimeType,
    } as any);

    const response = await fetch(`${API_BASE_URL}/mobile/profile/avatar`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
      body: formData,
    });

    const result = await response.json();
    console.log('📸 Upload result:', response.status, result);

    if (!response.ok) {
      return {
        success: false,
        message: result.message || `อัพโหลดไม่สำเร็จ (${response.status})`,
      };
    }

    return result;
  } catch (error: any) {
    console.error('❌ Upload avatar error:', error);
    return {
      success: false,
      message: error.message || 'เกิดข้อผิดพลาด',
    };
  }
};

// =====================================================
// Input Component
// =====================================================

const FormInput = ({
  label,
  value,
  onChangeText,
  placeholder,
  keyboardType = 'default',
  multiline = false,
  editable = true,
  isDark,
}: {
  label: string;
  value: string;
  onChangeText?: (text: string) => void;
  placeholder?: string;
  keyboardType?: 'default' | 'phone-pad' | 'email-address' | 'numeric';
  multiline?: boolean;
  editable?: boolean;
  isDark: boolean;
}) => (
  <View style={styles.inputGroup}>
    <Text style={[styles.inputLabel, isDark && styles.textGray300]}>{label}</Text>
    <TextInput
      style={[
        styles.input,
        isDark && styles.inputDark,
        multiline && styles.inputMultiline,
        !editable && styles.inputDisabled,
      ]}
      value={value}
      onChangeText={onChangeText}
      placeholder={placeholder}
      placeholderTextColor="#9CA3AF"
      keyboardType={keyboardType}
      multiline={multiline}
      numberOfLines={multiline ? 3 : 1}
      editable={editable}
    />
  </View>
);

// =====================================================
// Main Component
// =====================================================

export default function EditProfileScreen() {
  const { user, updateUser, isAuthenticated, refreshUser } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  // Form states
  const [name, setName] = useState(user?.name || '');
  const [phone, setPhone] = useState(user?.phone || '');
  const [address, setAddress] = useState(user?.address || '');
  const [bio, setBio] = useState(user?.bio || '');
  const [bankName, setBankName] = useState(user?.bank_name || '');
  const [bankAccount, setBankAccount] = useState(user?.bank_account || '');
  const [bankAccountName, setBankAccountName] = useState(user?.bank_account_name || '');

  // Loading states
  const [isSaving, setIsSaving] = useState(false);
  const [isUploading, setIsUploading] = useState(false);

  // ⭐ ใช้ utility function สำหรับ avatar URL

  // Handle pick image
  const handlePickImage = async () => {
    Alert.alert('เปลี่ยนรูปโปรไฟล์', 'เลือกวิธีการ', [
      {
        text: '📷 ถ่ายรูป',
        onPress: async () => {
          const { status } = await ImagePicker.requestCameraPermissionsAsync();
          if (status !== 'granted') {
            Alert.alert('ต้องการสิทธิ์', 'กรุณาอนุญาตให้ใช้กล้อง');
            return;
          }

          const result = await ImagePicker.launchCameraAsync({
            mediaTypes: ['images'],
            allowsEditing: true,
            aspect: [1, 1],
            quality: 0.8,
          });

          if (!result.canceled && result.assets[0]) {
            await handleUploadAvatar(result.assets[0].uri);
          }
        },
      },
      {
        text: '🖼️ แกลเลอรี่',
        onPress: async () => {
          const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
          if (status !== 'granted') {
            Alert.alert('ต้องการสิทธิ์', 'กรุณาอนุญาตให้เข้าถึงรูปภาพ');
            return;
          }

          const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ['images'],
            allowsEditing: true,
            aspect: [1, 1],
            quality: 0.8,
          });

          if (!result.canceled && result.assets[0]) {
            await handleUploadAvatar(result.assets[0].uri);
          }
        },
      },
      { text: 'ยกเลิก', style: 'cancel' },
    ]);
  };

  // Upload avatar
  const handleUploadAvatar = async (uri: string) => {
    setIsUploading(true);

    const result = await uploadAvatarApi(uri);

    if (result.success) {
      // ใช้ user object จาก API response (มีข้อมูลล่าสุด)
      if (result.data?.user) {
        updateUser(result.data.user);
      } else if (result.data?.avatarUrl) {
        // Fallback: ถ้าไม่มี user object ให้ใช้ avatarUrl
        updateUser({ avatar: result.data.avatarUrl });
      }

      // Refresh user data จาก server เพื่อให้แน่ใจว่าข้อมูลตรงกัน
      await refreshUser();

      Alert.alert('สำเร็จ', 'อัพโหลดรูปโปรไฟล์เรียบร้อย');
    } else {
      Alert.alert('ผิดพลาด', result.message || 'อัพโหลดไม่สำเร็จ');
    }

    setIsUploading(false);
  };

  // Save profile
  const handleSave = async () => {
    if (!name.trim()) {
      Alert.alert('กรุณากรอกข้อมูล', 'กรุณากรอกชื่อ');
      return;
    }

    setIsSaving(true);

    const result = await updateProfileApi({
      name: name.trim(),
      phone: phone.trim(),
      address: address.trim(),
      bio: bio.trim(),
      bank_name: bankName.trim(),
      bank_account: bankAccount.trim(),
      bank_account_name: bankAccountName.trim(),
    });

    if (result.success) {
      // Update local user data
      updateUser({
        name: name.trim(),
        phone: phone.trim(),
        address: address.trim(),
        bio: bio.trim(),
        bank_name: bankName.trim(),
        bank_account: bankAccount.trim(),
        bank_account_name: bankAccountName.trim(),
      });
      Alert.alert('สำเร็จ', 'บันทึกข้อมูลเรียบร้อย', [
        { text: 'ตกลง', onPress: () => router.back() },
      ]);
    } else {
      Alert.alert('ผิดพลาด', result.message || 'บันทึกไม่สำเร็จ');
    }

    setIsSaving(false);
  };

  // Not authenticated
  if (!isAuthenticated) {
    return (
      <View style={[styles.container, isDark && styles.containerDark]}>
        <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />
        <View style={styles.centerContent}>
          <Text style={{ fontSize: 80 }}>👤</Text>
          <Text style={[styles.title, isDark && styles.textWhite]}>แก้ไขโปรไฟล์</Text>
          <Text style={styles.subtitle}>กรุณาเข้าสู่ระบบก่อน</Text>
          <Pressable style={styles.loginBtn} onPress={() => router.push('/login')}>
            <Text style={styles.loginBtnText}>เข้าสู่ระบบ</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  const avatarUrl = getAvatarUrl(user?.avatar);

  return (
    <View style={[styles.container, isDark && styles.containerDark]}>
      <StatusBar barStyle="light-content" />

      {/* Header */}
      <LinearGradient colors={['#3B82F6', '#2563EB']} style={styles.header}>
        <Pressable style={styles.backBtn} onPress={() => router.back()}>
          <Text style={styles.backIcon}>←</Text>
        </Pressable>
        <Text style={styles.headerTitle}>แก้ไขโปรไฟล์</Text>
        <Pressable style={styles.saveBtn} onPress={handleSave} disabled={isSaving}>
          {isSaving ? (
            <ActivityIndicator size="small" color="#FFF" />
          ) : (
            <Text style={styles.saveBtnText}>บันทึก</Text>
          )}
        </Pressable>
      </LinearGradient>

      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
          {/* Avatar Section */}
          <View style={styles.avatarSection}>
            <Pressable onPress={handlePickImage} disabled={isUploading}>
              <View style={styles.avatarContainer}>
                {isUploading ? (
                  <View style={[styles.avatar, styles.avatarLoading]}>
                    <ActivityIndicator size="large" color="#3B82F6" />
                  </View>
                ) : avatarUrl ? (
                  <Image source={{ uri: avatarUrl }} style={styles.avatar} />
                ) : (
                  <View style={[styles.avatar, styles.avatarPlaceholder]}>
                    <Text style={styles.avatarText}>
                      {getAvatarInitial(user?.name)}
                    </Text>
                  </View>
                )}
                <View style={styles.avatarBadge}>
                  <Text style={{ fontSize: 14 }}>📷</Text>
                </View>
              </View>
            </Pressable>
            <Text style={[styles.avatarHint, isDark && styles.textGray400]}>
              แตะเพื่อเปลี่ยนรูป
            </Text>
          </View>

          {/* Personal Info */}
          <View style={[styles.section, isDark && styles.sectionDark]}>
            <Text style={[styles.sectionTitle, isDark && styles.textWhite]}>👤 ข้อมูลส่วนตัว</Text>

            <FormInput
              label="ชื่อ-นามสกุล"
              value={name}
              onChangeText={setName}
              placeholder="กรอกชื่อ-นามสกุล"
              isDark={isDark}
            />

            <FormInput
              label="อีเมล"
              value={user?.email || ''}
              placeholder="อีเมล"
              editable={false}
              isDark={isDark}
            />

            <FormInput
              label="เบอร์โทรศัพท์"
              value={phone}
              onChangeText={setPhone}
              placeholder="08X-XXX-XXXX"
              keyboardType="phone-pad"
              isDark={isDark}
            />

            <FormInput
              label="ที่อยู่"
              value={address}
              onChangeText={setAddress}
              placeholder="กรอกที่อยู่ (ไม่บังคับ)"
              multiline
              isDark={isDark}
            />

            <FormInput
              label="แนะนำตัว (Bio)"
              value={bio}
              onChangeText={setBio}
              placeholder="เขียนแนะนำตัว..."
              multiline
              isDark={isDark}
            />
          </View>

          {/* Bank Info */}
          <View style={[styles.section, isDark && styles.sectionDark]}>
            <Text style={[styles.sectionTitle, isDark && styles.textWhite]}>💰 ข้อมูลธนาคาร</Text>

            <FormInput
              label="ชื่อธนาคาร"
              value={bankName}
              onChangeText={setBankName}
              placeholder="เช่น ธนาคารกสิกรไทย"
              isDark={isDark}
            />

            <FormInput
              label="เลขบัญชี"
              value={bankAccount}
              onChangeText={setBankAccount}
              placeholder="XXX-X-XXXXX-X"
              keyboardType="numeric"
              isDark={isDark}
            />

            <FormInput
              label="ชื่อบัญชี"
              value={bankAccountName}
              onChangeText={setBankAccountName}
              placeholder="ชื่อเจ้าของบัญชี"
              isDark={isDark}
            />
          </View>

          {/* Referral Code */}
          <View style={[styles.section, isDark && styles.sectionDark]}>
            <Text style={[styles.sectionTitle, isDark && styles.textWhite]}>📤 รหัสแนะนำ</Text>
            <View style={[styles.referralBox, isDark && styles.referralBoxDark]}>
              <Text style={[styles.referralCode, isDark && styles.textWhite]}>
                {user?.referralCode || `TP${String(user?.id || 0).padStart(6, '0')}`}
              </Text>
              <Text style={[styles.referralHint, isDark && styles.textGray400]}>
                แชร์รหัสนี้เพื่อชวนเพื่อน
              </Text>
            </View>
          </View>

          {/* Save Button */}
          <Pressable style={styles.submitBtn} onPress={handleSave} disabled={isSaving}>
            {isSaving ? (
              <View style={[styles.btnGradientDisabled]}>
                <ActivityIndicator color="#FFF" />
              </View>
            ) : (
              <LinearGradient colors={['#3B82F6', '#8B5CF6']} style={styles.btnGradient}>
                <Text style={{ fontSize: 20 }}>✅</Text>
                <Text style={styles.btnText}>บันทึกการเปลี่ยนแปลง</Text>
              </LinearGradient>
            )}
          </Pressable>

          <View style={{ height: 50 }} />
        </ScrollView>
      </KeyboardAvoidingView>
    </View>
  );
}

// =====================================================
// Styles
// =====================================================

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  containerDark: { backgroundColor: '#111827' },
  centerContent: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
  title: { fontSize: 24, fontWeight: 'bold', color: '#1F2937', marginTop: 16 },
  textWhite: { color: '#FFFFFF' },
  textGray300: { color: '#D1D5DB' },
  textGray400: { color: '#9CA3AF' },
  subtitle: { fontSize: 16, color: '#6B7280', textAlign: 'center', marginTop: 8 },
  loginBtn: { backgroundColor: '#3B82F6', paddingHorizontal: 32, paddingVertical: 14, borderRadius: 12, marginTop: 24 },
  loginBtnText: { color: '#FFF', fontWeight: 'bold', fontSize: 16 },

  // Header
  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 16, paddingTop: 50, paddingBottom: 20 },
  backBtn: { width: 44, height: 44, borderRadius: 22, backgroundColor: 'rgba(255,255,255,0.2)', justifyContent: 'center', alignItems: 'center' },
  backIcon: { fontSize: 24, color: '#FFF' },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#FFF' },
  saveBtn: { paddingHorizontal: 16, paddingVertical: 8, borderRadius: 20, backgroundColor: 'rgba(255,255,255,0.2)' },
  saveBtnText: { color: '#FFF', fontWeight: '600' },

  // Content
  content: { flex: 1, paddingHorizontal: 16 },

  // Avatar
  avatarSection: { alignItems: 'center', paddingVertical: 24 },
  avatarContainer: { position: 'relative' },
  avatar: { width: 100, height: 100, borderRadius: 50, borderWidth: 3, borderColor: '#3B82F6' },
  avatarLoading: { backgroundColor: '#E5E7EB', justifyContent: 'center', alignItems: 'center' },
  avatarPlaceholder: { backgroundColor: '#3B82F6', justifyContent: 'center', alignItems: 'center' },
  avatarText: { fontSize: 40, color: '#FFF', fontWeight: 'bold' },
  avatarBadge: { position: 'absolute', bottom: 0, right: 0, width: 32, height: 32, borderRadius: 16, backgroundColor: '#3B82F6', justifyContent: 'center', alignItems: 'center', borderWidth: 2, borderColor: '#FFF' },
  avatarHint: { fontSize: 13, color: '#6B7280', marginTop: 8 },

  // Section
  section: { backgroundColor: '#FFF', borderRadius: 16, padding: 16, marginBottom: 16 },
  sectionDark: { backgroundColor: '#1F2937' },
  sectionTitle: { fontSize: 16, fontWeight: '600', color: '#1F2937', marginBottom: 16 },

  // Input
  inputGroup: { marginBottom: 16 },
  inputLabel: { fontSize: 14, color: '#6B7280', marginBottom: 8 },
  input: { backgroundColor: '#F3F4F6', borderRadius: 12, paddingHorizontal: 16, paddingVertical: 14, fontSize: 16, color: '#1F2937', borderWidth: 1, borderColor: '#E5E7EB' },
  inputDark: { backgroundColor: '#374151', color: '#FFF', borderColor: '#4B5563' },
  inputMultiline: { minHeight: 80, textAlignVertical: 'top' },
  inputDisabled: { opacity: 0.6 },

  // Referral
  referralBox: { backgroundColor: '#F3F4F6', borderRadius: 12, padding: 16, alignItems: 'center' },
  referralBoxDark: { backgroundColor: '#374151' },
  referralCode: { fontSize: 24, fontWeight: 'bold', color: '#3B82F6', letterSpacing: 2 },
  referralHint: { fontSize: 13, color: '#6B7280', marginTop: 4 },

  // Submit Button
  submitBtn: { borderRadius: 16, overflow: 'hidden', marginTop: 8 },
  btnGradient: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 16, gap: 10 },
  btnGradientDisabled: { backgroundColor: '#9CA3AF', paddingVertical: 16, alignItems: 'center' },
  btnText: { color: '#FFF', fontSize: 16, fontWeight: 'bold' },
});
