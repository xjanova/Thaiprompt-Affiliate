/**
 * Login Screen - หน้าเข้าสู่ระบบ
 * แปลงจาก .NET MAUI LoginPage
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
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '@/stores/authStore';
import { Button, Input } from '@/components';
import { isValidEmail } from '@/constants';

export default function LoginScreen() {
  const { login, isLoading, error, clearError, isAuthenticated } = useAuthStore();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [emailError, setEmailError] = useState('');
  const [passwordError, setPasswordError] = useState('');

  // ถ้า login แล้ว ให้กลับไปหน้าหลัก
  useEffect(() => {
    if (isAuthenticated) {
      router.replace('/');
    }
  }, [isAuthenticated]);

  // Clear error เมื่อ unmount
  useEffect(() => {
    return () => clearError();
  }, []);

  // Validate form
  const validateForm = (): boolean => {
    let isValid = true;

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

    // Validate password
    if (!password) {
      setPasswordError('กรุณากรอกรหัสผ่าน');
      isValid = false;
    } else if (password.length < 8) {
      setPasswordError('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
      isValid = false;
    } else {
      setPasswordError('');
    }

    return isValid;
  };

  // Handle login
  const handleLogin = async () => {
    if (!validateForm()) return;

    const success = await login(email.trim(), password);

    if (success) {
      router.replace('/');
    }
  };

  // Go back
  const goBack = () => router.back();

  return (
    <LinearGradient colors={['#0F0F23', '#1A1A2E']} className="flex-1">
      <SafeAreaView className="flex-1">
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          className="flex-1"
        >
          <ScrollView
            className="flex-1"
            contentContainerClassName="flex-grow"
            keyboardShouldPersistTaps="handled"
          >
            {/* Header */}
            <View className="px-5 pt-4">
              <Pressable
                onPress={goBack}
                className="w-10 h-10 rounded-full bg-white/10 items-center justify-center"
              >
                <Ionicons name="arrow-back" size={24} color="white" />
              </Pressable>
            </View>

            {/* Content */}
            <View className="flex-1 px-6 pt-8">
              {/* Logo */}
              <View className="items-center mb-8">
                <Text className="text-5xl mb-4">🚀</Text>
                <Text className="text-white text-2xl font-bold">
                  เข้าสู่ระบบ
                </Text>
                <Text className="text-gray-400 mt-2">
                  ยินดีต้อนรับกลับมา
                </Text>
              </View>

              {/* Form */}
              <View className="bg-white/5 rounded-3xl p-6">
                {/* Error Message */}
                {error && (
                  <View className="bg-red-500/20 border border-red-500/50 rounded-xl p-4 mb-4">
                    <Text className="text-red-400 text-center">{error}</Text>
                  </View>
                )}

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

                {/* Password Input */}
                <Input
                  label="รหัสผ่าน"
                  placeholder="••••••••"
                  value={password}
                  onChangeText={(text) => {
                    setPassword(text);
                    setPasswordError('');
                  }}
                  error={passwordError}
                  leftIcon="lock-closed"
                  isPassword
                />

                {/* Forgot Password */}
                <Pressable className="items-end mb-6">
                  <Text className="text-primary-400">ลืมรหัสผ่าน?</Text>
                </Pressable>

                {/* Login Button */}
                <Button
                  title="เข้าสู่ระบบ"
                  onPress={handleLogin}
                  loading={isLoading}
                  fullWidth
                  size="lg"
                />

                {/* Register Link */}
                <View className="flex-row justify-center mt-6">
                  <Text className="text-gray-400">ยังไม่มีบัญชี? </Text>
                  <Pressable onPress={() => router.push('/register')}>
                    <Text className="text-primary-400 font-bold">สมัครเลย</Text>
                  </Pressable>
                </View>
              </View>

              {/* Social Login */}
              <View className="mt-8">
                <View className="flex-row items-center mb-6">
                  <View className="flex-1 h-px bg-gray-700" />
                  <Text className="text-gray-500 mx-4">หรือ</Text>
                  <View className="flex-1 h-px bg-gray-700" />
                </View>

                {/* LINE Login */}
                <Pressable
                  onPress={() => Alert.alert('LINE Login', 'กำลังพัฒนา')}
                  className="flex-row items-center justify-center bg-[#00B900] rounded-xl py-4"
                >
                  <Text className="text-white font-bold text-lg">
                    เข้าสู่ระบบด้วย LINE
                  </Text>
                </Pressable>
              </View>
            </View>
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    </LinearGradient>
  );
}
