/**
 * Register Screen - หน้าสมัครสมาชิก Premium Design
 * ออกแบบสมราคาหลักล้าน พร้อม Animation และ Glassmorphism
 */

import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Pressable,
  Alert,
  Animated,
  Easing,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';
import { router, useLocalSearchParams } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import * as Haptics from 'expo-haptics';
import { LavaBackground, GlassCard, Button, Input } from '@/components';
import { useAppStore } from '@/stores/appStore';
import { isValidEmail, isValidPassword, VALIDATION, API_BASE_URL } from '@/constants';
import axios from 'axios';

const { width, height } = Dimensions.get('window');

// Step Indicator Component
const StepIndicator = ({
  step,
  currentStep,
  label,
  isDark,
}: {
  step: number;
  currentStep: number;
  label: string;
  isDark: boolean;
}) => {
  const isActive = currentStep >= step;
  const isCurrent = currentStep === step;

  return (
    <View className="items-center flex-1">
      <View
        className={`w-10 h-10 rounded-full items-center justify-center border-2 ${
          isActive
            ? 'bg-primary-500 border-primary-500'
            : isDark
            ? 'border-gray-600 bg-gray-800'
            : 'border-gray-300 bg-gray-100'
        } ${isCurrent ? 'scale-110' : ''}`}
        style={
          isCurrent
            ? {
                shadowColor: '#3B82F6',
                shadowOffset: { width: 0, height: 0 },
                shadowOpacity: 0.5,
                shadowRadius: 10,
                elevation: 10,
              }
            : {}
        }
      >
        {isActive && !isCurrent ? (
          <Ionicons name="checkmark" size={20} color="white" />
        ) : (
          <Text
            className={`font-bold ${
              isActive ? 'text-white' : isDark ? 'text-gray-500' : 'text-gray-400'
            }`}
          >
            {step}
          </Text>
        )}
      </View>
      <Text
        className={`text-xs mt-2 ${
          isActive
            ? 'text-primary-500 font-medium'
            : isDark
            ? 'text-gray-500'
            : 'text-gray-400'
        }`}
      >
        {label}
      </Text>
    </View>
  );
};

// Feature Card for benefits
const FeatureCard = ({
  icon,
  title,
  description,
  delay,
  isDark,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  title: string;
  description: string;
  delay: number;
  isDark: boolean;
}) => {
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const slideAnim = useRef(new Animated.Value(20)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 500,
        delay,
        useNativeDriver: true,
      }),
      Animated.timing(slideAnim, {
        toValue: 0,
        duration: 500,
        delay,
        easing: Easing.out(Easing.ease),
        useNativeDriver: true,
      }),
    ]).start();
  }, []);

  return (
    <Animated.View
      style={{
        opacity: fadeAnim,
        transform: [{ translateY: slideAnim }],
      }}
      className="flex-row items-center p-4 mb-3 rounded-2xl"
    >
      <LinearGradient
        colors={['rgba(59, 130, 246, 0.2)', 'rgba(139, 92, 246, 0.2)']}
        className="w-12 h-12 rounded-xl items-center justify-center mr-4"
      >
        <Ionicons name={icon} size={24} color="#3B82F6" />
      </LinearGradient>
      <View className="flex-1">
        <Text className={`font-semibold ${isDark ? 'text-white' : 'text-gray-800'}`}>
          {title}
        </Text>
        <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
          {description}
        </Text>
      </View>
    </Animated.View>
  );
};

export default function RegisterScreen() {
  const { ref } = useLocalSearchParams<{ ref?: string }>();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  // Animation values
  const logoScale = useRef(new Animated.Value(0.8)).current;
  const logoOpacity = useRef(new Animated.Value(0)).current;
  const formSlide = useRef(new Animated.Value(50)).current;
  const formOpacity = useRef(new Animated.Value(0)).current;

  // Step state
  const [currentStep, setCurrentStep] = useState(1);

  // Form state
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [referralCode, setReferralCode] = useState(ref || '');
  const [acceptTerms, setAcceptTerms] = useState(false);

  // Validation errors
  const [nameError, setNameError] = useState('');
  const [emailError, setEmailError] = useState('');
  const [phoneError, setPhoneError] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const [confirmPasswordError, setConfirmPasswordError] = useState('');

  // Loading state
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');

  // Start animations
  useEffect(() => {
    Animated.parallel([
      Animated.spring(logoScale, {
        toValue: 1,
        friction: 8,
        tension: 40,
        useNativeDriver: true,
      }),
      Animated.timing(logoOpacity, {
        toValue: 1,
        duration: 600,
        useNativeDriver: true,
      }),
      Animated.timing(formSlide, {
        toValue: 0,
        duration: 800,
        delay: 300,
        easing: Easing.out(Easing.ease),
        useNativeDriver: true,
      }),
      Animated.timing(formOpacity, {
        toValue: 1,
        duration: 800,
        delay: 300,
        useNativeDriver: true,
      }),
    ]).start();
  }, []);

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

  // Validate step 1
  const validateStep1 = (): boolean => {
    let isValid = true;

    if (!name.trim()) {
      setNameError('กรุณากรอกชื่อ-นามสกุล');
      isValid = false;
    } else if (name.trim().length < VALIDATION.MIN_USERNAME_LENGTH) {
      setNameError(`ชื่อต้องมีอย่างน้อย ${VALIDATION.MIN_USERNAME_LENGTH} ตัวอักษร`);
      isValid = false;
    } else {
      setNameError('');
    }

    if (!email.trim()) {
      setEmailError('กรุณากรอกอีเมล');
      isValid = false;
    } else if (!isValidEmail(email)) {
      setEmailError('รูปแบบอีเมลไม่ถูกต้อง');
      isValid = false;
    } else {
      setEmailError('');
    }

    if (phone.trim() && !/^[0-9]{9,10}$/.test(phone.replace(/-/g, ''))) {
      setPhoneError('รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง');
      isValid = false;
    } else {
      setPhoneError('');
    }

    return isValid;
  };

  // Validate step 2
  const validateStep2 = (): boolean => {
    let isValid = true;

    if (!password) {
      setPasswordError('กรุณากรอกรหัสผ่าน');
      isValid = false;
    } else if (!isValidPassword(password)) {
      setPasswordError(`รหัสผ่านต้องมี ${VALIDATION.MIN_PASSWORD_LENGTH}-${VALIDATION.MAX_PASSWORD_LENGTH} ตัวอักษร`);
      isValid = false;
    } else {
      setPasswordError('');
    }

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

  // Handle next step
  const handleNextStep = () => {
    if (currentStep === 1) {
      if (validateStep1()) {
        Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
        setCurrentStep(2);
      } else {
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
      }
    } else if (currentStep === 2) {
      if (validateStep2()) {
        Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
        setCurrentStep(3);
      } else {
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
      }
    }
  };

  // Handle register
  const handleRegister = async () => {
    if (!acceptTerms) {
      Alert.alert('ข้อกำหนดการใช้งาน', 'กรุณายอมรับเงื่อนไขและข้อกำหนดการใช้งาน');
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
          'ยินดีต้อนรับสู่ครอบครัว Thaiprompt Affiliate\nเริ่มต้นสร้างรายได้ไปด้วยกัน!',
          [
            {
              text: 'เข้าสู่ระบบเลย',
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

  // Render Step 1 - ข้อมูลส่วนตัว
  const renderStep1 = () => (
    <View>
      <Text className={`text-lg font-bold mb-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
        ข้อมูลส่วนตัว
      </Text>

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

      <Pressable
        onPress={handleNextStep}
        className="mt-4"
      >
        <LinearGradient
          colors={['#3B82F6', '#8B5CF6']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          className="py-4 rounded-2xl items-center"
          style={{
            shadowColor: '#3B82F6',
            shadowOffset: { width: 0, height: 4 },
            shadowOpacity: 0.3,
            shadowRadius: 8,
            elevation: 8,
          }}
        >
          <View className="flex-row items-center">
            <Text className="text-white font-bold text-lg mr-2">ถัดไป</Text>
            <Ionicons name="arrow-forward" size={20} color="white" />
          </View>
        </LinearGradient>
      </Pressable>
    </View>
  );

  // Render Step 2 - รหัสผ่าน
  const renderStep2 = () => (
    <View>
      <Text className={`text-lg font-bold mb-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
        ตั้งรหัสผ่าน
      </Text>

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
                className="flex-1 h-2 rounded-full overflow-hidden"
                style={{
                  backgroundColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)',
                }}
              >
                <View
                  style={{
                    width: level <= passwordStrength.level ? '100%' : '0%',
                    height: '100%',
                    backgroundColor: passwordStrength.color,
                  }}
                />
              </View>
            ))}
          </View>
          <View className="mt-2 flex-row flex-wrap">
            <Text className={`text-xs mr-2 ${/[A-Z]/.test(password) ? 'text-green-500' : isDark ? 'text-gray-500' : 'text-gray-400'}`}>
              ✓ ตัวพิมพ์ใหญ่
            </Text>
            <Text className={`text-xs mr-2 ${/[a-z]/.test(password) ? 'text-green-500' : isDark ? 'text-gray-500' : 'text-gray-400'}`}>
              ✓ ตัวพิมพ์เล็ก
            </Text>
            <Text className={`text-xs mr-2 ${/[0-9]/.test(password) ? 'text-green-500' : isDark ? 'text-gray-500' : 'text-gray-400'}`}>
              ✓ ตัวเลข
            </Text>
            <Text className={`text-xs ${password.length >= 8 ? 'text-green-500' : isDark ? 'text-gray-500' : 'text-gray-400'}`}>
              ✓ 8 ตัวอักษร
            </Text>
          </View>
        </View>
      )}

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

      {/* Password Match Indicator */}
      {confirmPassword.length > 0 && (
        <View className={`flex-row items-center mb-4 ${password === confirmPassword ? 'opacity-100' : 'opacity-70'}`}>
          <Ionicons
            name={password === confirmPassword ? 'checkmark-circle' : 'close-circle'}
            size={16}
            color={password === confirmPassword ? '#10B981' : '#EF4444'}
          />
          <Text
            className={`text-sm ml-1 ${
              password === confirmPassword ? 'text-green-500' : 'text-red-500'
            }`}
          >
            {password === confirmPassword ? 'รหัสผ่านตรงกัน' : 'รหัสผ่านไม่ตรงกัน'}
          </Text>
        </View>
      )}

      <View className="flex-row gap-3 mt-4">
        <Pressable
          onPress={() => setCurrentStep(1)}
          className={`flex-1 py-4 rounded-2xl items-center border ${
            isDark ? 'border-gray-600' : 'border-gray-300'
          }`}
        >
          <View className="flex-row items-center">
            <Ionicons name="arrow-back" size={20} color={isDark ? '#9CA3AF' : '#6B7280'} />
            <Text className={`font-medium ml-2 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
              ย้อนกลับ
            </Text>
          </View>
        </Pressable>

        <Pressable onPress={handleNextStep} className="flex-1">
          <LinearGradient
            colors={['#3B82F6', '#8B5CF6']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            className="py-4 rounded-2xl items-center"
            style={{
              shadowColor: '#3B82F6',
              shadowOffset: { width: 0, height: 4 },
              shadowOpacity: 0.3,
              shadowRadius: 8,
              elevation: 8,
            }}
          >
            <View className="flex-row items-center">
              <Text className="text-white font-bold mr-2">ถัดไป</Text>
              <Ionicons name="arrow-forward" size={20} color="white" />
            </View>
          </LinearGradient>
        </Pressable>
      </View>
    </View>
  );

  // Render Step 3 - ยืนยันและสมัคร
  const renderStep3 = () => (
    <View>
      <Text className={`text-lg font-bold mb-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
        ยืนยันการสมัคร
      </Text>

      {/* Summary Card */}
      <View
        className={`rounded-2xl p-4 mb-4 ${isDark ? 'bg-gray-800/50' : 'bg-gray-50'}`}
      >
        <View className="flex-row items-center mb-3">
          <Ionicons name="person-circle" size={40} color="#3B82F6" />
          <View className="ml-3 flex-1">
            <Text className={`font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
              {name}
            </Text>
            <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
              {email}
            </Text>
          </View>
          <Pressable onPress={() => setCurrentStep(1)}>
            <Ionicons name="pencil" size={20} color="#3B82F6" />
          </Pressable>
        </View>

        {phone && (
          <View className="flex-row items-center">
            <Ionicons name="call" size={16} color={isDark ? '#9CA3AF' : '#6B7280'} />
            <Text className={`ml-2 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
              {phone}
            </Text>
          </View>
        )}
      </View>

      {/* Referral Code */}
      <Input
        label="รหัสผู้แนะนำ (ไม่บังคับ)"
        placeholder="กรอกรหัสถ้ามีผู้แนะนำ"
        value={referralCode}
        onChangeText={setReferralCode}
        leftIcon="gift"
        autoCapitalize="characters"
      />

      {referralCode ? (
        <View className="bg-green-500/20 border border-green-500/50 rounded-xl p-3 mb-4 flex-row items-center">
          <Ionicons name="checkmark-circle" size={20} color="#10B981" />
          <Text className="text-green-500 ml-2 flex-1">
            คุณจะได้รับโบนัสพิเศษจากการแนะนำ!
          </Text>
        </View>
      ) : null}

      {/* Terms & Conditions */}
      <Pressable
        onPress={() => setAcceptTerms(!acceptTerms)}
        className="flex-row items-start mb-6"
      >
        <View
          className={`w-6 h-6 rounded-md border-2 items-center justify-center mr-3 mt-0.5 ${
            acceptTerms
              ? 'bg-primary-500 border-primary-500'
              : isDark
              ? 'border-gray-600'
              : 'border-gray-300'
          }`}
        >
          {acceptTerms && <Ionicons name="checkmark" size={16} color="white" />}
        </View>
        <Text className={`flex-1 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
          ข้าพเจ้ายอมรับ{' '}
          <Text className="text-primary-500 underline">เงื่อนไขการใช้งาน</Text>
          {' '}และ{' '}
          <Text className="text-primary-500 underline">นโยบายความเป็นส่วนตัว</Text>
          {' '}ของ Thaiprompt Affiliate
        </Text>
      </Pressable>

      {/* Error Message */}
      {error && (
        <View className="bg-red-500/20 border border-red-500/50 rounded-xl p-4 mb-4">
          <View className="flex-row items-center">
            <Ionicons name="alert-circle" size={20} color="#EF4444" />
            <Text className="text-red-400 ml-2 flex-1">{error}</Text>
          </View>
        </View>
      )}

      <View className="flex-row gap-3">
        <Pressable
          onPress={() => setCurrentStep(2)}
          className={`flex-1 py-4 rounded-2xl items-center border ${
            isDark ? 'border-gray-600' : 'border-gray-300'
          }`}
        >
          <View className="flex-row items-center">
            <Ionicons name="arrow-back" size={20} color={isDark ? '#9CA3AF' : '#6B7280'} />
            <Text className={`font-medium ml-2 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
              ย้อนกลับ
            </Text>
          </View>
        </Pressable>

        <Pressable
          onPress={handleRegister}
          disabled={isLoading || !acceptTerms}
          className="flex-1"
          style={{ opacity: isLoading || !acceptTerms ? 0.6 : 1 }}
        >
          <LinearGradient
            colors={['#10B981', '#059669']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            className="py-4 rounded-2xl items-center"
            style={{
              shadowColor: '#10B981',
              shadowOffset: { width: 0, height: 4 },
              shadowOpacity: 0.3,
              shadowRadius: 8,
              elevation: 8,
            }}
          >
            <View className="flex-row items-center">
              {isLoading ? (
                <Text className="text-white font-bold">กำลังสมัคร...</Text>
              ) : (
                <>
                  <Ionicons name="rocket" size={20} color="white" />
                  <Text className="text-white font-bold ml-2">สมัครสมาชิก</Text>
                </>
              )}
            </View>
          </LinearGradient>
        </Pressable>
      </View>
    </View>
  );

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
                onPress={() => router.back()}
                className="w-10 h-10 rounded-full bg-white/10 items-center justify-center"
                style={{
                  shadowColor: '#000',
                  shadowOffset: { width: 0, height: 2 },
                  shadowOpacity: 0.2,
                  shadowRadius: 4,
                  elevation: 4,
                }}
              >
                <Ionicons name="arrow-back" size={24} color="white" />
              </Pressable>

              <Pressable onPress={() => router.push('/login')}>
                <Text className="text-white/80">มีบัญชีแล้ว? เข้าสู่ระบบ</Text>
              </Pressable>
            </View>

            {/* Logo & Title */}
            <Animated.View
              style={{
                opacity: logoOpacity,
                transform: [{ scale: logoScale }],
              }}
              className="items-center mt-6 mb-6"
            >
              <View
                className="w-24 h-24 rounded-3xl items-center justify-center mb-4"
                style={{
                  shadowColor: '#3B82F6',
                  shadowOffset: { width: 0, height: 0 },
                  shadowOpacity: 0.5,
                  shadowRadius: 20,
                  elevation: 10,
                }}
              >
                <LinearGradient
                  colors={['#3B82F6', '#8B5CF6', '#06B6D4']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 1 }}
                  className="w-full h-full rounded-3xl items-center justify-center"
                >
                  <Text className="text-5xl">🚀</Text>
                </LinearGradient>
              </View>
              <Text className="text-white text-2xl font-bold">
                สมัครสมาชิก
              </Text>
              <Text className="text-gray-400 mt-1">
                เริ่มต้นสร้างรายได้กับ Thaiprompt
              </Text>
            </Animated.View>

            {/* Step Indicator */}
            <View className="flex-row px-8 mb-6">
              <StepIndicator step={1} currentStep={currentStep} label="ข้อมูล" isDark={isDark} />
              <View className={`flex-1 h-0.5 self-center mx-2 ${
                currentStep >= 2 ? 'bg-primary-500' : isDark ? 'bg-gray-700' : 'bg-gray-300'
              }`} />
              <StepIndicator step={2} currentStep={currentStep} label="รหัสผ่าน" isDark={isDark} />
              <View className={`flex-1 h-0.5 self-center mx-2 ${
                currentStep >= 3 ? 'bg-primary-500' : isDark ? 'bg-gray-700' : 'bg-gray-300'
              }`} />
              <StepIndicator step={3} currentStep={currentStep} label="ยืนยัน" isDark={isDark} />
            </View>

            {/* Referral Info */}
            {ref && currentStep === 1 && (
              <View className="mx-6 mb-4 bg-gradient-to-r from-green-500/20 to-emerald-500/20 border border-green-500/30 rounded-2xl p-4">
                <View className="flex-row items-center">
                  <View className="w-10 h-10 rounded-full bg-green-500/30 items-center justify-center mr-3">
                    <Ionicons name="gift" size={20} color="#10B981" />
                  </View>
                  <View className="flex-1">
                    <Text className="text-green-400 font-medium">
                      คุณได้รับเชิญจากเพื่อน!
                    </Text>
                    <Text className="text-green-300/70 text-sm">
                      รหัสแนะนำ: {ref}
                    </Text>
                  </View>
                </View>
              </View>
            )}

            {/* Form Card */}
            <Animated.View
              style={{
                opacity: formOpacity,
                transform: [{ translateY: formSlide }],
              }}
              className="px-6"
            >
              <GlassCard style={{ marginBottom: 24 }}>
                {currentStep === 1 && renderStep1()}
                {currentStep === 2 && renderStep2()}
                {currentStep === 3 && renderStep3()}
              </GlassCard>
            </Animated.View>

            {/* Benefits Section - Show only on step 1 */}
            {currentStep === 1 && (
              <View className="px-6 mb-6">
                <Text className={`text-lg font-bold mb-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
                  สิทธิประโยชน์สำหรับสมาชิก
                </Text>
                <GlassCard>
                  <FeatureCard
                    icon="cash"
                    title="รายได้ไม่จำกัด"
                    description="รับคอมมิชชั่นจากทุกการขาย"
                    delay={0}
                    isDark={isDark}
                  />
                  <FeatureCard
                    icon="people"
                    title="สร้างทีมงาน"
                    description="แนะนำเพื่อน รับโบนัสเพิ่ม"
                    delay={100}
                    isDark={isDark}
                  />
                  <FeatureCard
                    icon="trending-up"
                    title="ระบบ Rank"
                    description="เลื่อนขั้น รับสิทธิพิเศษมากขึ้น"
                    delay={200}
                    isDark={isDark}
                  />
                  <FeatureCard
                    icon="wallet"
                    title="ถอนเงินง่าย"
                    description="ถอนเข้าบัญชีธนาคารได้ทันที"
                    delay={300}
                    isDark={isDark}
                  />
                </GlassCard>
              </View>
            )}

            {/* Social Login */}
            {currentStep === 1 && (
              <View className="px-6">
                <View className="flex-row items-center mb-6">
                  <View className="flex-1 h-px bg-white/20" />
                  <Text className={`mx-4 ${isDark ? 'text-gray-500' : 'text-gray-400'}`}>
                    หรือสมัครด้วย
                  </Text>
                  <View className="flex-1 h-px bg-white/20" />
                </View>

                <Pressable
                  onPress={() => Alert.alert('LINE สมัครสมาชิก', 'กำลังพัฒนา')}
                  className="flex-row items-center justify-center bg-[#00B900] rounded-2xl py-4 mb-3"
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

                <Pressable
                  onPress={() => Alert.alert('Google สมัครสมาชิก', 'กำลังพัฒนา')}
                  className={`flex-row items-center justify-center rounded-2xl py-4 ${
                    isDark ? 'bg-white/10' : 'bg-white'
                  }`}
                  style={{
                    shadowColor: '#000',
                    shadowOpacity: 0.1,
                    shadowRadius: 8,
                    elevation: 2,
                  }}
                >
                  <Text className="text-2xl mr-2">G</Text>
                  <Text className={`font-bold text-lg ${isDark ? 'text-white' : 'text-gray-800'}`}>
                    สมัครด้วย Google
                  </Text>
                </Pressable>
              </View>
            )}

            {/* Login Link */}
            <View className="flex-row justify-center mt-8 mb-4">
              <Text className={isDark ? 'text-gray-400' : 'text-gray-600'}>
                มีบัญชีอยู่แล้ว?{' '}
              </Text>
              <Pressable onPress={() => router.push('/login')}>
                <Text className="text-primary-400 font-bold">เข้าสู่ระบบ</Text>
              </Pressable>
            </View>
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    </LavaBackground>
  );
}
