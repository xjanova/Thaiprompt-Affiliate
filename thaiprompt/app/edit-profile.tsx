/**
 * Edit Profile Screen - หน้าแก้ไขโปรไฟล์ Premium Design
 * รองรับ Avatar Upload และ Sync กับเว็บ
 */

import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  Image,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Animated,
  Easing,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import * as Haptics from 'expo-haptics';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { updateProfile, uploadAvatar, deleteAvatar, changePassword } from '@/services/api';
import { GlassCard, Input, Button } from '@/components';
import { isValidPassword, VALIDATION } from '@/constants';

// Avatar Component with Edit Button
const AvatarSection = ({
  avatar,
  name,
  isUploading,
  onPress,
  isDark,
}: {
  avatar?: string | null;
  name: string;
  isUploading: boolean;
  onPress: () => void;
  isDark: boolean;
}) => {
  const scaleAnim = useRef(new Animated.Value(1)).current;

  const handlePressIn = () => {
    Animated.spring(scaleAnim, {
      toValue: 0.95,
      useNativeDriver: true,
    }).start();
  };

  const handlePressOut = () => {
    Animated.spring(scaleAnim, {
      toValue: 1,
      useNativeDriver: true,
    }).start();
  };

  return (
    <Animated.View
      style={{
        transform: [{ scale: scaleAnim }],
        alignItems: 'center',
      }}
    >
      <Pressable
        onPressIn={handlePressIn}
        onPressOut={handlePressOut}
        onPress={onPress}
        disabled={isUploading}
      >
        <View
          className="w-32 h-32 rounded-full items-center justify-center"
          style={{
            shadowColor: '#3B82F6',
            shadowOffset: { width: 0, height: 0 },
            shadowOpacity: 0.4,
            shadowRadius: 15,
            elevation: 10,
          }}
        >
          {/* Gradient Border */}
          <LinearGradient
            colors={['#3B82F6', '#8B5CF6', '#06B6D4', '#3B82F6']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={{
              width: '100%',
              height: '100%',
              borderRadius: 9999,
              padding: 4,
            }}
          >
            <View className={`w-full h-full rounded-full overflow-hidden items-center justify-center ${
              isDark ? 'bg-gray-800' : 'bg-white'
            }`}>
              {avatar ? (
                <Image
                  source={{ uri: avatar }}
                  className="w-full h-full"
                  resizeMode="cover"
                />
              ) : (
                <Text className="text-6xl">
                  {name?.charAt(0)?.toUpperCase() || '👤'}
                </Text>
              )}
            </View>
          </LinearGradient>

          {/* Edit Badge */}
          <View
            className="absolute bottom-0 right-0 w-10 h-10 rounded-full items-center justify-center"
            style={{
              shadowColor: '#000',
              shadowOffset: { width: 0, height: 2 },
              shadowOpacity: 0.3,
              shadowRadius: 4,
              elevation: 6,
            }}
          >
            <LinearGradient
              colors={['#3B82F6', '#8B5CF6']}
              style={{
                width: '100%',
                height: '100%',
                borderRadius: 9999,
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              {isUploading ? (
                <ActivityIndicator size="small" color="white" />
              ) : (
                <Ionicons name="camera" size={18} color="white" />
              )}
            </LinearGradient>
          </View>
        </View>
      </Pressable>

      <Text className={`text-sm mt-3 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
        แตะเพื่อเปลี่ยนรูปโปรไฟล์
      </Text>
    </Animated.View>
  );
};

// Section Header Component
const SectionHeader = ({
  icon,
  title,
  isDark,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  title: string;
  isDark: boolean;
}) => (
  <View className="flex-row items-center mb-4">
    <LinearGradient
      colors={['rgba(59, 130, 246, 0.2)', 'rgba(139, 92, 246, 0.2)']}
      style={{
        width: 40,
        height: 40,
        borderRadius: 12,
        alignItems: 'center',
        justifyContent: 'center',
        marginRight: 12,
      }}
    >
      <Ionicons name={icon} size={20} color="#3B82F6" />
    </LinearGradient>
    <Text className={`text-lg font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
      {title}
    </Text>
  </View>
);

export default function EditProfileScreen() {
  const { user, updateUser, refreshUser, isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  // Form states
  const [name, setName] = useState(user?.name || '');
  const [phone, setPhone] = useState(user?.phone || '');
  const [address, setAddress] = useState('');
  const [bio, setBio] = useState('');

  // Bank info states
  const [bankName, setBankName] = useState('');
  const [bankAccount, setBankAccount] = useState('');
  const [bankAccountName, setBankAccountName] = useState('');

  // Password change states
  const [showPasswordSection, setShowPasswordSection] = useState(false);
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmNewPassword, setConfirmNewPassword] = useState('');

  // Loading states
  const [isSaving, setIsSaving] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [isChangingPassword, setIsChangingPassword] = useState(false);

  // Error states
  const [errors, setErrors] = useState<{ [key: string]: string }>({});

  // Animations
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const slideAnim = useRef(new Animated.Value(30)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 500,
        useNativeDriver: true,
      }),
      Animated.timing(slideAnim, {
        toValue: 0,
        duration: 500,
        easing: Easing.out(Easing.ease),
        useNativeDriver: true,
      }),
    ]).start();
  }, []);

  // Handle avatar selection
  const handleAvatarPress = useCallback(() => {
    Alert.alert(
      'เปลี่ยนรูปโปรไฟล์',
      'เลือกวิธีการเปลี่ยนรูป',
      [
        {
          text: '📷 ถ่ายรูป',
          onPress: async () => {
            try {
              const { status } = await ImagePicker.requestCameraPermissionsAsync();
              console.log('📷 Camera permission:', status);
              if (status !== 'granted') {
                Alert.alert('ต้องการสิทธิ์', 'กรุณาอนุญาตให้แอพใช้กล้อง');
                return;
              }

              console.log('📷 Launching camera...');
              const result = await ImagePicker.launchCameraAsync({
                mediaTypes: ['images'], // ใช้ syntax ใหม่ของ expo-image-picker v16
                allowsEditing: true,
                aspect: [1, 1],
                quality: 0.8,
              });

              if (!result.canceled && result.assets[0]) {
                handleUploadAvatar(result.assets[0].uri);
              }
            } catch (error: any) {
              console.error('Camera error:', error);
              Alert.alert('เกิดข้อผิดพลาด', error?.message || 'ไม่สามารถเปิดกล้องได้');
            }
          },
        },
        {
          text: '🖼️ เลือกจากแกลเลอรี่',
          onPress: async () => {
            try {
              // ขอ permission media library ก่อน
              const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
              console.log('🖼️ Media library permission:', status);
              if (status !== 'granted') {
                Alert.alert('ต้องการสิทธิ์', 'กรุณาอนุญาตให้แอพเข้าถึงรูปภาพ');
                return;
              }

              console.log('🖼️ Launching image library...');
              const result = await ImagePicker.launchImageLibraryAsync({
                mediaTypes: ['images'], // ใช้ syntax ใหม่ของ expo-image-picker v16
                allowsEditing: true,
                aspect: [1, 1],
                quality: 0.8,
              });

              if (!result.canceled && result.assets[0]) {
                handleUploadAvatar(result.assets[0].uri);
              }
            } catch (error: any) {
              console.error('Gallery error:', error);
              Alert.alert('เกิดข้อผิดพลาด', error?.message || 'ไม่สามารถเปิดแกลเลอรี่ได้');
            }
          },
        },
        ...(user?.avatar
          ? [
              {
                text: 'ลบรูปโปรไฟล์',
                style: 'destructive' as const,
                onPress: handleDeleteAvatar,
              },
            ]
          : []),
        { text: 'ยกเลิก', style: 'cancel' as const },
      ]
    );
  }, [user?.avatar]);

  // Upload avatar
  const handleUploadAvatar = async (imageUri: string) => {
    setIsUploading(true);
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);

    try {
      const response = await uploadAvatar(imageUri);

      if (response.success && response.data) {
        updateUser({ avatar: response.data.avatarUrl });
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
        Alert.alert('สำเร็จ', 'อัพโหลดรูปโปรไฟล์เรียบร้อย');
      } else {
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
        Alert.alert('ผิดพลาด', response.message || 'อัพโหลดไม่สำเร็จ');
      }
    } catch (error) {
      console.error('Upload avatar error:', error);
      Alert.alert('ผิดพลาด', 'เกิดข้อผิดพลาดในการอัพโหลด');
    } finally {
      setIsUploading(false);
    }
  };

  // Delete avatar
  const handleDeleteAvatar = async () => {
    Alert.alert(
      'ยืนยันการลบ',
      'คุณต้องการลบรูปโปรไฟล์หรือไม่?',
      [
        { text: 'ยกเลิก', style: 'cancel' },
        {
          text: 'ลบ',
          style: 'destructive',
          onPress: async () => {
            setIsUploading(true);
            try {
              const response = await deleteAvatar();
              if (response.success) {
                updateUser({ avatar: null });
                Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
                Alert.alert('สำเร็จ', 'ลบรูปโปรไฟล์เรียบร้อย');
              } else {
                Alert.alert('ผิดพลาด', response.message || 'ลบรูปไม่สำเร็จ');
              }
            } catch (error) {
              Alert.alert('ผิดพลาด', 'เกิดข้อผิดพลาด');
            } finally {
              setIsUploading(false);
            }
          },
        },
      ]
    );
  };

  // Validate form
  const validateForm = (): boolean => {
    const newErrors: { [key: string]: string } = {};

    if (!name.trim()) {
      newErrors.name = 'กรุณากรอกชื่อ';
    }

    if (phone.trim() && !/^[0-9]{9,10}$/.test(phone.replace(/-/g, ''))) {
      newErrors.phone = 'รูปแบบเบอร์โทรไม่ถูกต้อง';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Save profile
  const handleSave = async () => {
    if (!validateForm()) {
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
      return;
    }

    setIsSaving(true);
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);

    try {
      const response = await updateProfile({
        name: name.trim(),
        phone: phone.trim().replace(/-/g, ''),
        address: address.trim(),
        bio: bio.trim(),
        bank_name: bankName.trim(),
        bank_account: bankAccount.trim(),
        bank_account_name: bankAccountName.trim(),
      });

      if (response.success && response.data) {
        updateUser(response.data);
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
        Alert.alert('สำเร็จ', 'บันทึกข้อมูลเรียบร้อย', [
          { text: 'ตกลง', onPress: () => router.back() },
        ]);
      } else {
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
        Alert.alert('ผิดพลาด', response.message || 'บันทึกไม่สำเร็จ');
      }
    } catch (error) {
      console.error('Save profile error:', error);
      Alert.alert('ผิดพลาด', 'เกิดข้อผิดพลาดในการบันทึก');
    } finally {
      setIsSaving(false);
    }
  };

  // Change password
  const handleChangePassword = async () => {
    // Validate
    if (!currentPassword) {
      Alert.alert('ผิดพลาด', 'กรุณากรอกรหัสผ่านปัจจุบัน');
      return;
    }

    if (!isValidPassword(newPassword)) {
      Alert.alert('ผิดพลาด', `รหัสผ่านใหม่ต้องมี ${VALIDATION.MIN_PASSWORD_LENGTH}-${VALIDATION.MAX_PASSWORD_LENGTH} ตัวอักษร`);
      return;
    }

    if (newPassword !== confirmNewPassword) {
      Alert.alert('ผิดพลาด', 'รหัสผ่านใหม่ไม่ตรงกัน');
      return;
    }

    setIsChangingPassword(true);
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);

    try {
      const response = await changePassword({
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirmNewPassword,
      });

      if (response.success) {
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
        Alert.alert('สำเร็จ', 'เปลี่ยนรหัสผ่านเรียบร้อย');
        setCurrentPassword('');
        setNewPassword('');
        setConfirmNewPassword('');
        setShowPasswordSection(false);
      } else {
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
        Alert.alert('ผิดพลาด', response.message || 'เปลี่ยนรหัสผ่านไม่สำเร็จ');
      }
    } catch (error) {
      console.error('Change password error:', error);
      Alert.alert('ผิดพลาด', 'เกิดข้อผิดพลาด');
    } finally {
      setIsChangingPassword(false);
    }
  };

  // If not authenticated
  if (!isAuthenticated) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <Ionicons name="person-circle" size={80} color="#9CA3AF" />
          <Text className={`text-xl font-bold mt-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
            แก้ไขโปรไฟล์
          </Text>
          <Text className="text-gray-500 text-center mt-2">
            กรุณาเข้าสู่ระบบเพื่อแก้ไขโปรไฟล์
          </Text>
          <Pressable
            onPress={() => router.push('/login')}
            className="bg-primary-500 px-8 py-3 rounded-xl mt-6"
          >
            <Text className="text-white font-bold">เข้าสู่ระบบ</Text>
          </Pressable>
        </SafeAreaView>
      </View>
    );
  }

  return (
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
      <SafeAreaView className="flex-1" edges={['top']}>
        {/* Header */}
        <LinearGradient
          colors={isDark ? ['#1E3A8A', '#1E40AF'] : ['#3B82F6', '#2563EB']}
          style={{
            paddingHorizontal: 20,
            paddingTop: 16,
            paddingBottom: 32,
          }}
        >
          <View className="flex-row items-center justify-between mb-6">
            <Pressable
              onPress={() => router.back()}
              className="w-10 h-10 rounded-full bg-white/20 items-center justify-center"
            >
              <Ionicons name="arrow-back" size={24} color="white" />
            </Pressable>
            <Text className="text-white text-xl font-bold">แก้ไขโปรไฟล์</Text>
            <Pressable
              onPress={handleSave}
              disabled={isSaving}
              className="px-4 py-2 rounded-full bg-white/20"
            >
              {isSaving ? (
                <ActivityIndicator size="small" color="white" />
              ) : (
                <Text className="text-white font-semibold">บันทึก</Text>
              )}
            </Pressable>
          </View>

          {/* Avatar */}
          <AvatarSection
            avatar={user?.avatar}
            name={user?.name || ''}
            isUploading={isUploading}
            onPress={handleAvatarPress}
            isDark={isDark}
          />
        </LinearGradient>

        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          className="flex-1"
        >
          <ScrollView
            className="flex-1"
            contentContainerClassName="px-5 py-6 pb-20"
            showsVerticalScrollIndicator={false}
            keyboardShouldPersistTaps="handled"
          >
            <Animated.View
              style={{
                opacity: fadeAnim,
                transform: [{ translateY: slideAnim }],
              }}
            >
              {/* Personal Info Section */}
              <GlassCard style={{ marginBottom: 16 }}>
                <SectionHeader icon="person" title="ข้อมูลส่วนตัว" isDark={isDark} />

                <Input
                  label="ชื่อ-นามสกุล"
                  placeholder="กรอกชื่อ-นามสกุล"
                  value={name}
                  onChangeText={(text) => {
                    setName(text);
                    setErrors({ ...errors, name: '' });
                  }}
                  error={errors.name}
                  leftIcon="person"
                />

                <Input
                  label="อีเมล"
                  value={user?.email || ''}
                  leftIcon="mail"
                  editable={false}
                />

                <Input
                  label="เบอร์โทรศัพท์"
                  placeholder="08X-XXX-XXXX"
                  value={phone}
                  onChangeText={(text) => {
                    setPhone(text);
                    setErrors({ ...errors, phone: '' });
                  }}
                  error={errors.phone}
                  leftIcon="call"
                  keyboardType="phone-pad"
                />

                <Input
                  label="ที่อยู่ (ไม่บังคับ)"
                  placeholder="กรอกที่อยู่"
                  value={address}
                  onChangeText={setAddress}
                  leftIcon="location"
                  multiline
                  numberOfLines={3}
                />

                <Input
                  label="แนะนำตัว (Bio)"
                  placeholder="เขียนแนะนำตัวคุณ..."
                  value={bio}
                  onChangeText={setBio}
                  leftIcon="document-text"
                  multiline
                  numberOfLines={3}
                />
              </GlassCard>

              {/* Bank Info Section */}
              <GlassCard style={{ marginBottom: 16 }}>
                <SectionHeader icon="wallet" title="ข้อมูลธนาคาร" isDark={isDark} />

                <Input
                  label="ชื่อธนาคาร"
                  placeholder="เช่น ธนาคารกสิกรไทย"
                  value={bankName}
                  onChangeText={setBankName}
                  leftIcon="business"
                />

                <Input
                  label="เลขบัญชี"
                  placeholder="XXX-X-XXXXX-X"
                  value={bankAccount}
                  onChangeText={setBankAccount}
                  leftIcon="card"
                  keyboardType="numeric"
                />

                <Input
                  label="ชื่อบัญชี"
                  placeholder="ชื่อเจ้าของบัญชี"
                  value={bankAccountName}
                  onChangeText={setBankAccountName}
                  leftIcon="person"
                />
              </GlassCard>

              {/* Change Password Section */}
              <GlassCard style={{ marginBottom: 16 }}>
                <Pressable
                  onPress={() => setShowPasswordSection(!showPasswordSection)}
                  className="flex-row items-center justify-between"
                >
                  <View className="flex-row items-center">
                    <LinearGradient
                      colors={['rgba(239, 68, 68, 0.2)', 'rgba(239, 68, 68, 0.1)']}
                      style={{
                        width: 40,
                        height: 40,
                        borderRadius: 12,
                        alignItems: 'center',
                        justifyContent: 'center',
                        marginRight: 12,
                      }}
                    >
                      <Ionicons name="key" size={20} color="#EF4444" />
                    </LinearGradient>
                    <Text className={`text-lg font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
                      เปลี่ยนรหัสผ่าน
                    </Text>
                  </View>
                  <Ionicons
                    name={showPasswordSection ? 'chevron-up' : 'chevron-down'}
                    size={24}
                    color={isDark ? '#9CA3AF' : '#6B7280'}
                  />
                </Pressable>

                {showPasswordSection && (
                  <View className="mt-4">
                    <Input
                      label="รหัสผ่านปัจจุบัน"
                      placeholder="กรอกรหัสผ่านปัจจุบัน"
                      value={currentPassword}
                      onChangeText={setCurrentPassword}
                      leftIcon="lock-closed"
                      isPassword
                    />

                    <Input
                      label="รหัสผ่านใหม่"
                      placeholder="อย่างน้อย 8 ตัวอักษร"
                      value={newPassword}
                      onChangeText={setNewPassword}
                      leftIcon="lock-open"
                      isPassword
                    />

                    <Input
                      label="ยืนยันรหัสผ่านใหม่"
                      placeholder="กรอกรหัสผ่านใหม่อีกครั้ง"
                      value={confirmNewPassword}
                      onChangeText={setConfirmNewPassword}
                      leftIcon="shield-checkmark"
                      isPassword
                    />

                    <Pressable
                      onPress={handleChangePassword}
                      disabled={isChangingPassword}
                      className="mt-4"
                    >
                      <LinearGradient
                        colors={['#EF4444', '#DC2626']}
                        style={{
                          paddingVertical: 16,
                          borderRadius: 12,
                          alignItems: 'center',
                        }}
                      >
                        {isChangingPassword ? (
                          <ActivityIndicator color="white" />
                        ) : (
                          <Text className="text-white font-bold">เปลี่ยนรหัสผ่าน</Text>
                        )}
                      </LinearGradient>
                    </Pressable>
                  </View>
                )}
              </GlassCard>

              {/* Referral Info */}
              <GlassCard style={{ marginBottom: 16 }}>
                <SectionHeader icon="share-social" title="รหัสแนะนำ" isDark={isDark} />

                <View className={`rounded-xl p-4 ${isDark ? 'bg-gray-800' : 'bg-gray-100'}`}>
                  <Text className={`text-center text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
                    {user?.referralCode || 'ไม่มีรหัส'}
                  </Text>
                  <Text className={`text-center text-sm mt-1 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                    แชร์รหัสนี้เพื่อชวนเพื่อน
                  </Text>
                </View>

                <Pressable
                  onPress={() => router.push('/referral')}
                  className="flex-row items-center justify-center mt-4 py-3"
                >
                  <Ionicons name="share-social" size={20} color="#3B82F6" />
                  <Text className="text-primary-500 font-medium ml-2">
                    ดูลิงก์แนะนำเพื่อน
                  </Text>
                </Pressable>
              </GlassCard>

              {/* Save Button */}
              <Pressable onPress={handleSave} disabled={isSaving}>
                <LinearGradient
                  colors={['#3B82F6', '#8B5CF6']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                  style={{
                    paddingVertical: 16,
                    borderRadius: 16,
                    alignItems: 'center',
                    shadowColor: '#3B82F6',
                    shadowOffset: { width: 0, height: 4 },
                    shadowOpacity: 0.3,
                    shadowRadius: 8,
                    elevation: 8,
                  }}
                >
                  {isSaving ? (
                    <ActivityIndicator color="white" />
                  ) : (
                    <View className="flex-row items-center">
                      <Ionicons name="checkmark-circle" size={24} color="white" />
                      <Text className="text-white font-bold text-lg ml-2">บันทึกการเปลี่ยนแปลง</Text>
                    </View>
                  )}
                </LinearGradient>
              </Pressable>
            </Animated.View>
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    </View>
  );
}
