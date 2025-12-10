/**
 * Login Screen - Premium Stable Version
 * ใช้ StyleSheet แทน NativeWind
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Pressable,
  Alert,
  ActivityIndicator,
  TextInput,
  StyleSheet,
  StatusBar,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useLocalSearchParams } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import * as WebBrowser from 'expo-web-browser';
import { useAuthStore } from '@/stores/authStore';

// Email validation
const isValidEmail = (email: string) => {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
};

export default function LoginScreen() {
  const {
    login,
    loginWithLine,
    handleLineCallback,
    isLoading,
    error,
    clearError,
    isAuthenticated
  } = useAuthStore();

  const params = useLocalSearchParams<{ code?: string; state?: string; error?: string }>();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [emailError, setEmailError] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const [isLineLoading, setIsLineLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  // ถ้า login แล้ว ให้ไปหน้า tabs โดยตรง
  useEffect(() => {
    if (isAuthenticated) {
      router.replace('/(tabs)');
    }
  }, [isAuthenticated]);

  useEffect(() => {
    return () => clearError();
  }, [clearError]);

  // Handle LINE OAuth callback
  useEffect(() => {
    const handleCallback = async () => {
      if (params.code && params.state) {
        setIsLineLoading(true);
        try {
          const success = await handleLineCallback(params.code, params.state);
          if (success) {
            router.replace('/(tabs)');
          }
        } catch (err) {
          console.error('LINE callback error:', err);
        } finally {
          setIsLineLoading(false);
        }
      } else if (params.error) {
        Alert.alert('LINE Login ล้มเหลว', 'ไม่สามารถเข้าสู่ระบบด้วย LINE ได้');
      }
    };
    handleCallback();
  }, [params.code, params.state, params.error]);

  // Handle LINE Login
  const handleLineLogin = useCallback(async () => {
    setIsLineLoading(true);
    try {
      const result = await loginWithLine();
      if (result.success && result.authUrl) {
        const browserResult = await WebBrowser.openAuthSessionAsync(
          result.authUrl,
          'thaiprompt://login'
        );
        if (browserResult.type === 'success' && browserResult.url) {
          const url = new URL(browserResult.url);
          const code = url.searchParams.get('code');
          const state = url.searchParams.get('state');
          if (code && state) {
            const success = await handleLineCallback(code, state);
            if (success) {
              router.replace('/(tabs)');
            }
          }
        }
      } else {
        Alert.alert('LINE Login', result.message || 'ไม่สามารถเชื่อมต่อ LINE ได้');
      }
    } catch (err) {
      Alert.alert('ข้อผิดพลาด', 'ไม่สามารถเข้าสู่ระบบด้วย LINE ได้');
    } finally {
      setIsLineLoading(false);
    }
  }, [loginWithLine, handleLineCallback]);

  // Validate form
  const validateForm = (): boolean => {
    let isValid = true;
    if (!email.trim()) {
      setEmailError('กรุณากรอกอีเมล');
      isValid = false;
    } else if (!isValidEmail(email)) {
      setEmailError('รูปแบบอีเมลไม่ถูกต้อง');
      isValid = false;
    } else {
      setEmailError('');
    }
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
      router.replace('/(tabs)');
    }
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />

      {/* Background Gradient */}
      <LinearGradient
        colors={['#0F0F23', '#1A1A2E', '#16213E']}
        style={StyleSheet.absoluteFill}
      />

      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.keyboardView}
      >
        <ScrollView
          style={styles.scrollView}
          contentContainerStyle={styles.scrollContent}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          {/* Header */}
          <View style={styles.header}>
            <Pressable style={styles.backButton} onPress={() => router.back()}>
              <Ionicons name="arrow-back" size={24} color="#FFF" />
            </Pressable>
          </View>

          {/* Logo */}
          <View style={styles.logoContainer}>
            <LinearGradient
              colors={['#3B82F6', '#8B5CF6', '#06B6D4']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.logoBox}
            >
              <Ionicons name="log-in-outline" size={40} color="#FFF" />
            </LinearGradient>
            <Text style={styles.title}>เข้าสู่ระบบ</Text>
            <Text style={styles.subtitle}>ยินดีต้อนรับกลับมา</Text>
          </View>

          {/* Form Card */}
          <View style={styles.formCard}>
            {/* Error Message */}
            {error && (
              <View style={styles.errorBox}>
                <Ionicons name="alert-circle" size={20} color="#EF4444" />
                <Text style={styles.errorText}>{error}</Text>
              </View>
            )}

            {/* Email Input */}
            <View style={styles.inputGroup}>
              <Text style={styles.label}>อีเมล</Text>
              <View style={[styles.inputContainer, emailError && styles.inputError]}>
                <Ionicons name="mail-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                <TextInput
                  style={styles.input}
                  placeholder="example@email.com"
                  placeholderTextColor="#6B7280"
                  value={email}
                  onChangeText={(text) => {
                    setEmail(text);
                    setEmailError('');
                  }}
                  keyboardType="email-address"
                  autoCapitalize="none"
                  autoCorrect={false}
                />
              </View>
              {emailError ? <Text style={styles.errorLabel}>{emailError}</Text> : null}
            </View>

            {/* Password Input */}
            <View style={styles.inputGroup}>
              <Text style={styles.label}>รหัสผ่าน</Text>
              <View style={[styles.inputContainer, passwordError && styles.inputError]}>
                <Ionicons name="lock-closed-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                <TextInput
                  style={styles.input}
                  placeholder="••••••••"
                  placeholderTextColor="#6B7280"
                  value={password}
                  onChangeText={(text) => {
                    setPassword(text);
                    setPasswordError('');
                  }}
                  secureTextEntry={!showPassword}
                />
                <Pressable onPress={() => setShowPassword(!showPassword)} style={styles.eyeButton}>
                  <Ionicons name={showPassword ? 'eye-off-outline' : 'eye-outline'} size={20} color="#9CA3AF" />
                </Pressable>
              </View>
              {passwordError ? <Text style={styles.errorLabel}>{passwordError}</Text> : null}
            </View>

            {/* Forgot Password */}
            <Pressable style={styles.forgotButton}>
              <Text style={styles.forgotText}>ลืมรหัสผ่าน?</Text>
            </Pressable>

            {/* Login Button */}
            <Pressable
              style={[styles.loginButton, isLoading && styles.buttonDisabled]}
              onPress={handleLogin}
              disabled={isLoading}
            >
              <LinearGradient
                colors={['#3B82F6', '#2563EB']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.loginGradient}
              >
                {isLoading ? (
                  <ActivityIndicator color="#FFF" />
                ) : (
                  <>
                    <Ionicons name="log-in-outline" size={20} color="#FFF" />
                    <Text style={styles.loginButtonText}>เข้าสู่ระบบ</Text>
                  </>
                )}
              </LinearGradient>
            </Pressable>

            {/* Register Link */}
            <View style={styles.registerRow}>
              <Text style={styles.registerText}>ยังไม่มีบัญชี? </Text>
              <Pressable onPress={() => router.push('/register')}>
                <Text style={styles.registerLink}>สมัครเลย</Text>
              </Pressable>
            </View>
          </View>

          {/* Social Login Divider */}
          <View style={styles.dividerRow}>
            <View style={styles.dividerLine} />
            <Text style={styles.dividerText}>หรือ</Text>
            <View style={styles.dividerLine} />
          </View>

          {/* LINE Login */}
          <Pressable
            style={[styles.lineButton, (isLoading || isLineLoading) && styles.buttonDisabled]}
            onPress={handleLineLogin}
            disabled={isLoading || isLineLoading}
          >
            {isLineLoading ? (
              <ActivityIndicator color="#FFF" />
            ) : (
              <>
                <Ionicons name="chatbubbles" size={24} color="#FFF" />
                <Text style={styles.lineButtonText}>เข้าสู่ระบบด้วย LINE</Text>
              </>
            )}
          </Pressable>

          <View style={styles.bottomSpace} />
        </ScrollView>
      </KeyboardAvoidingView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  keyboardView: {
    flex: 1,
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    paddingHorizontal: 24,
  },
  header: {
    paddingTop: 56,
    paddingBottom: 16,
  },
  backButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.1)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoContainer: {
    alignItems: 'center',
    marginBottom: 32,
  },
  logoBox: {
    width: 80,
    height: 80,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  subtitle: {
    fontSize: 16,
    color: '#9CA3AF',
    marginTop: 8,
  },
  formCard: {
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 24,
    padding: 24,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(239,68,68,0.15)',
    borderWidth: 1,
    borderColor: 'rgba(239,68,68,0.3)',
    borderRadius: 12,
    padding: 12,
    marginBottom: 16,
  },
  errorText: {
    color: '#EF4444',
    marginLeft: 8,
    flex: 1,
  },
  inputGroup: {
    marginBottom: 16,
  },
  label: {
    color: '#E5E7EB',
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 8,
  },
  inputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderRadius: 14,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  inputError: {
    borderColor: '#EF4444',
  },
  inputIcon: {
    marginLeft: 14,
  },
  input: {
    flex: 1,
    paddingVertical: 14,
    paddingHorizontal: 12,
    color: '#FFFFFF',
    fontSize: 16,
  },
  eyeButton: {
    padding: 14,
  },
  errorLabel: {
    color: '#EF4444',
    fontSize: 12,
    marginTop: 4,
  },
  forgotButton: {
    alignItems: 'flex-end',
    marginBottom: 20,
  },
  forgotText: {
    color: '#3B82F6',
    fontSize: 14,
  },
  loginButton: {
    borderRadius: 14,
    overflow: 'hidden',
    marginBottom: 16,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  loginGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 16,
    gap: 8,
  },
  loginButtonText: {
    color: '#FFFFFF',
    fontSize: 18,
    fontWeight: 'bold',
  },
  registerRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginTop: 8,
  },
  registerText: {
    color: '#9CA3AF',
    fontSize: 14,
  },
  registerLink: {
    color: '#3B82F6',
    fontSize: 14,
    fontWeight: 'bold',
  },
  dividerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 24,
  },
  dividerLine: {
    flex: 1,
    height: 1,
    backgroundColor: 'rgba(255,255,255,0.1)',
  },
  dividerText: {
    color: '#6B7280',
    marginHorizontal: 16,
  },
  lineButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#00B900',
    borderRadius: 14,
    paddingVertical: 16,
    gap: 8,
  },
  lineButtonText: {
    color: '#FFFFFF',
    fontSize: 18,
    fontWeight: 'bold',
  },
  bottomSpace: {
    height: 40,
  },
});
