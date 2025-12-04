/**
 * Register Screen - หน้าสมัครสมาชิก
 * ใช้ Lava Lamp Background Theme พร้อม Glassmorphism Design
 */

import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Pressable,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, useLocalSearchParams } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import * as Haptics from 'expo-haptics';
import { LavaBackground, GlassCard, Button, Input } from '@/components';
import { useAppStore } from '@/stores/appStore';
import { isValidEmail, isValidPassword, VALIDATION, API_BASE_URL } from '@/constants';
import axios from 'axios';

export default function RegisterScreen() {
  const { ref } = useLocalSearchParams<{ ref?: string }>();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  // Form state
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [referralCode, setReferralCode] = useState(ref || '');

  // Validation errors
  const [nameError, setNameError] = useState('');
  const [emailError, setEmailError] = useState('');
  const [phoneError, setPhoneError] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const [confirmPasswordError, setConfirmPasswordError] = useState('');

  // Loading state
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');

  // ตรวจสอบความแข็งแรงรหัสผ่าน
  const getPasswordStrength = (pwd: string): { level: number; text: string; color: string } => {
    if (!pwd) return { level: 0, text: '', color: 'transparent' };

    let score = 0;
    if (pwd.length >= 8) score++;
    if (pwd.length >= 12) score++;
    if (/[A-Z]/.test(pwd)) score++;
    if (/[a-z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;

    if (score <= 2) return { level: 1, text: 'อ่อน', color: '#EF4444' };
    if (score <= 4) return { level: 2, text: 'ปานกลาง', color: '#F59E0B' };
    return { level: 3, text: 'แข็งแรง', color: '#10B981' };
  };

  const passwordStrength = getPasswordStrength(password);

  // Validate form
  const validateForm = (): boolean => {
    let isValid = true;

    // Validate name
    if (!name.trim()) {
      setNameError('กรุณากรอกชื่อ-นามสกุล');
      isValid = false;
    } else if (name.trim().length < VALIDATION.MIN_USERNAME_LENGTH) {
      setNameError(`ชื่อต้องมีอย่างน้อย ${VALIDATION.MIN_USERNAME_LENGTH} ตัวอักษร`);
      isValid = false;
    } else {
      setNameError('');
    }

    // Validate email
    if (!email.trim()) {
      setEmailError('กรุณากรอกอีเมล');
      isValid = false;
    } else if (!isValidEmail(email)) {
      setEmailError('รูปแบบอีเมลไม่ถูกต้อง');
      isValid = false;
    } else {
      setEmailError('');
    }

    // Validate phone (optional but if provided, must be valid)
    if (phone.trim() && !/^[0-9]{9,10}$/.test(phone.replace(/-/g, ''))) {
      setPhoneError('รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง');
      isValid = false;
    } else {
      setPhoneError('');
    }

    // Validate password
    if (!password) {
      setPasswordError('กรุณากรอกรหัสผ่าน');
      isValid = false;
    } else if (!isValidPassword(password)) {
      setPasswordError(`รหัสผ่านต้องมี ${VALIDATION.MIN_PASSWORD_LENGTH}-${VALIDATION.MAX_PASSWORD_LENGTH} ตัวอักษร`);
      isValid = false;
    } else {
      setPasswordError('');
    }

    // Validate confirm password
    if (!confirmPassword) {
      setConfirmPasswordError('กรุณายืนยันรหัสผ่าน');
      isValid = false;
    } else if (password !== confirmPassword) {
      setConfirmPasswordError('รหัสผ่านไม่ตรงกัน');
      isValid = false;
    } else {
      setConfirmPasswordError('');
    }

    return isValid;
  };

  // Handle register
  const handleRegister = async () => {
    if (!validateForm()) {
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
      return;
    }

    setIsLoading(true);
    setError('');

    try {
      const response = await axios.post(`${API_BASE_URL}/register`, {
        name: name.trim(),
        email: email.trim().toLowerCase(),
        phone: phone.trim().replace(/-/g, ''),
        password,
        password_confirmation: confirmPassword,
        referral_code: referralCode.trim() || undefined,
      });

      if (response.data.success) {
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
        Alert.alert(
          'สมัครสมาชิกสำเร็จ! 🎉',
          'ยินดีต้อนรับสู่ Thaiprompt Affiliate\nกรุณาเข้าสู่ระบบเพื่อเริ่มต้นใช้งาน',
          [
            {
              text: 'เข้าสู่ระบบ',
              onPress: () => router.replace('/login'),
            },
          ]
        );
      } else {
        setError(response.data.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
      }
    } catch (err: any) {
      const message = err.response?.data?.message || 'เกิดข้อผิดพลาดในการสมัครสมาชิก';
      setError(message);
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
    } finally {
      setIsLoading(false);
    }
  };

  // Go back
  const goBack = () => router.back();

  // Navigate to login
  const goToLogin = () => router.push('/login');

  return (
    <LavaBackground variant="shopping" intensity="medium">
      <SafeAreaView className="flex-1">
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          className="flex-1"
        >
          <ScrollView
            className="flex-1"
            contentContainerClassName="flex-grow pb-8"
            keyboardShouldPersistTaps="handled"
            showsVerticalScrollIndicator={false}
          >
            {/* Header */}
            <View className="px-5 pt-4 flex-row justify-between items-center">
              <Pressable
                onPress={goBack}
                className="w-10 h-10 rounded-full bg-white/10 items-center justify-center"
              >
                <Ionicons name="arrow-back" size={24} color="white" />
              </Pressable>

              <Pressable onPress={goToLogin}>
                <Text className="text-white/80">มีบัญชีแล้ว? เข้าสู่ระบบ</Text>
              </Pressable>
            </View>

            {/* Content */}
            <View className="flex-1 px-6 pt-6">
              {/* Logo & Title */}
              <View className="items-center mb-6">
                <View className="w-20 h-20 rounded-3xl bg-gradient-to-br from-primary-400 to-secondary-400 items-center justify-center mb-4 shadow-lg">
                  <Text className="text-4xl">🚀</Text>
                </View>
                <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  สมัครสมาชิก
                </Text>
                <Text className={`mt-2 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                  เริ่มต้นสร้างรายได้กับ Thaiprompt
                </Text>
              </View>

              {/* Registration Form */}
              <GlassCard style={{ marginBottom: 24 }}>
                {/* Error Message */}
                {error && (
                  <View className="bg-red-500/20 border border-red-500/50 rounded-xl p-4 mb-4">
                    <Text className="text-red-400 text-center">{error}</Text>
                  </View>
                )}

                {/* Referral Code (if from link) */}
                {ref && (
                  <View className="bg-green-500/20 border border-green-500/50 rounded-xl p-4 mb-4">
                    <View className="flex-row items-center">
                      <Ionicons name="gift" size={20} color="#10B981" />
                      <Text className="text-green-400 ml-2">
                        คุณได้รับเชิญจากเพื่อน! รหัส: {ref}
                      </Text>
                    </View>
                  </View>
                )}

                {/* Name Input */}
                <Input
                  label="ชื่อ-นามสกุล"
                  placeholder="กรอกชื่อจริง - นามสกุลจริง"
                  value={name}
                  onChangeText={(text) => {
                    setName(text);
                    setNameError('');
                  }}
                  error={nameError}
                  leftIcon="person"
                  autoCapitalize="words"
                />

                {/* Email Input */}
                <Input
                  label="อีเมล"
                  placeholder="example@email.com"
                  value={email}
                  onChangeText={(text) => {
                    setEmail(text);
                    setEmailError('');
                  }}
                  error={emailError}
                  leftIcon="mail"
                  keyboardType="email-address"
                  autoCapitalize="none"
                  autoCorrect={false}
                />

                {/* Phone Input */}
                <Input
                  label="เบอร์โทรศัพท์ (ไม่บังคับ)"
                  placeholder="08X-XXX-XXXX"
                  value={phone}
                  onChangeText={(text) => {
                    setPhone(text);
                    setPhoneError('');
                  }}
                  error={phoneError}
                  leftIcon="call"
                  keyboardType="phone-pad"
                />

                {/* Password Input */}
                <Input
                  label="รหัสผ่าน"
                  placeholder="อย่างน้อย 8 ตัวอักษร"
                  value={password}
                  onChangeText={(text) => {
                    setPassword(text);
                    setPasswordError('');
                  }}
                  error={passwordError}
                  leftIcon="lock-closed"
                  isPassword
                />

                {/* Password Strength Indicator */}
                {password.length > 0 && (
                  <View className="mb-4">
                    <View className="flex-row items-center justify-between mb-2">
                      <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                        ความแข็งแรงรหัสผ่าน
                      </Text>
                      <Text style={{ color: passwordStrength.color }} className="text-sm font-medium">
                        {passwordStrength.text}
                      </Text>
                    </View>
                    <View className="flex-row gap-1">
                      {[1, 2, 3].map((level) => (
                        <View
                          key={level}
                          className="flex-1 h-1 rounded-full"
                          style={{
                            backgroundColor:
                              level <= passwordStrength.level
                                ? passwordStrength.color
                                : isDark
                                ? 'rgba(255,255,255,0.2)'
                                : 'rgba(0,0,0,0.1)',
                          }}
                        />
                      ))}
                    </View>
                  </View>
                )}

                {/* Confirm Password Input */}
                <Input
                  label="ยืนยันรหัสผ่าน"
                  placeholder="กรอกรหัสผ่านอีกครั้ง"
                  value={confirmPassword}
                  onChangeText={(text) => {
                    setConfirmPassword(text);
                    setConfirmPasswordError('');
                  }}
                  error={confirmPasswordError}
                  leftIcon="shield-checkmark"
                  isPassword
                />

                {/* Referral Code Input (if not from link) */}
                {!ref && (
                  <Input
                    label="รหัสแนะนำ (ไม่บังคับ)"
                    placeholder="ถ้ามีรหัสแนะนำ กรอกที่นี่"
                    value={referralCode}
                    onChangeText={setReferralCode}
                    leftIcon="gift"
                    autoCapitalize="characters"
                  />
                )}

                {/* Terms & Conditions */}
                <View className="mb-6">
                  <Text className={`text-center text-sm ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                    การสมัครสมาชิกหมายความว่าคุณยอมรับ{' '}
                    <Text className="text-primary-400 underline">เงื่อนไขการใช้งาน</Text>
                    {' '}และ{' '}
                    <Text className="text-primary-400 underline">นโยบายความเป็นส่วนตัว</Text>
                  </Text>
                </View>

                {/* Register Button */}
                <Button
                  title={isLoading ? 'กำลังสมัคร...' : 'สมัครสมาชิก'}
                  onPress={handleRegister}
                  loading={isLoading}
                  disabled={isLoading}
                  fullWidth
                  size="lg"
                  variant="primary"
                />
              </GlassCard>

              {/* Social Login */}
              <View className="mt-4">
                <View className="flex-row items-center mb-6">
                  <View className="flex-1 h-px bg-white/20" />
                  <Text className={`mx-4 ${isDark ? 'text-gray-500' : 'text-gray-400'}`}>
                    หรือสมัครด้วย
                  </Text>
                  <View className="flex-1 h-px bg-white/20" />
                </View>

                {/* LINE Login */}
                <Pressable
                  onPress={() => Alert.alert('LINE สมัครสมาชิก', 'กำลังพัฒนา')}
                  className="flex-row items-center justify-center bg-[#00B900] rounded-xl py-4 mb-3 shadow-lg"
                  style={{
                    shadowColor: '#00B900',
                    shadowOpacity: 0.3,
                    shadowRadius: 8,
                    elevation: 4,
                  }}
                >
                  <Ionicons name="chatbubbles" size={24} color="white" />
                  <Text className="text-white font-bold text-lg ml-2">
                    สมัครด้วย LINE
                  </Text>
                </Pressable>

                {/* Google Login */}
                <Pressable
                  onPress={() => Alert.alert('Google สมัครสมาชิก', 'กำลังพัฒนา')}
                  className={`flex-row items-center justify-center rounded-xl py-4 ${
                    isDark ? 'bg-white/10' : 'bg-white'
                  }`}
                  style={{
                    shadowColor: '#000',
                    shadowOpacity: 0.1,
                    shadowRadius: 8,
                    elevation: 2,
                  }}
                >
                  <Text className="text-2xl">G</Text>
                  <Text className={`font-bold text-lg ml-2 ${isDark ? 'text-white' : 'text-gray-800'}`}>
                    สมัครด้วย Google
                  </Text>
                </Pressable>
              </View>

              {/* Login Link */}
              <View className="flex-row justify-center mt-8">
                <Text className={isDark ? 'text-gray-400' : 'text-gray-600'}>
                  มีบัญชีอยู่แล้ว?{' '}
                </Text>
                <Pressable onPress={goToLogin}>
                  <Text className="text-primary-400 font-bold">เข้าสู่ระบบ</Text>
                </Pressable>
              </View>
            </View>
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    </LavaBackground>
  );
}
