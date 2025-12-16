/**
 * Login Screen - Premium V3 Design
 * ออกแบบใหม่ให้สวยงาม น่าใช้ สมราคา
 *
 * Features:
 * - Animated floating elements
 * - Glassmorphism form card
 * - Premium gradient effects
 * - Smooth animations
 */

import React, { useState, useEffect, useCallback, useRef } from 'react';
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
  Image,
  Animated,
  Dimensions,
} from 'react-native';
import { APP_INFO } from '@/config/appConfig';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useLocalSearchParams } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import { useAuthStore } from '@/stores/authStore';

const { width, height } = Dimensions.get('window');

// Floating Orb Animation Component
const FloatingOrb = ({ delay, size, color, position }: {
  delay: number;
  size: number;
  color: string;
  position: { top?: number; bottom?: number; left?: number; right?: number };
}) => {
  const translateY = useRef(new Animated.Value(0)).current;
  const opacity = useRef(new Animated.Value(0.3)).current;

  useEffect(() => {
    const floatAnimation = Animated.loop(
      Animated.sequence([
        Animated.parallel([
          Animated.timing(translateY, {
            toValue: -20,
            duration: 3000,
            useNativeDriver: true,
          }),
          Animated.timing(opacity, {
            toValue: 0.6,
            duration: 3000,
            useNativeDriver: true,
          }),
        ]),
        Animated.parallel([
          Animated.timing(translateY, {
            toValue: 0,
            duration: 3000,
            useNativeDriver: true,
          }),
          Animated.timing(opacity, {
            toValue: 0.3,
            duration: 3000,
            useNativeDriver: true,
          }),
        ]),
      ])
    );

    const timeout = setTimeout(() => floatAnimation.start(), delay);
    return () => {
      clearTimeout(timeout);
      floatAnimation.stop();
    };
  }, []);

  return (
    <Animated.View
      style={[
        {
          position: 'absolute',
          width: size,
          height: size,
          borderRadius: size / 2,
          backgroundColor: color,
          ...position,
          transform: [{ translateY }],
          opacity,
        },
      ]}
    />
  );
};

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

  // Logo animation
  const logoScale = useRef(new Animated.Value(0.8)).current;
  const logoOpacity = useRef(new Animated.Value(0)).current;
  const formSlide = useRef(new Animated.Value(50)).current;
  const formOpacity = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // Logo entrance animation
    Animated.parallel([
      Animated.spring(logoScale, {
        toValue: 1,
        tension: 50,
        friction: 7,
        useNativeDriver: true,
      }),
      Animated.timing(logoOpacity, {
        toValue: 1,
        duration: 600,
        useNativeDriver: true,
      }),
    ]).start();

    // Form entrance animation (delayed)
    setTimeout(() => {
      Animated.parallel([
        Animated.spring(formSlide, {
          toValue: 0,
          tension: 50,
          friction: 7,
          useNativeDriver: true,
        }),
        Animated.timing(formOpacity, {
          toValue: 1,
          duration: 500,
          useNativeDriver: true,
        }),
      ]).start();
    }, 300);
  }, []);

  return (
    <View style={styles.container}>
      {/* Premium Gradient Background */}
      <LinearGradient
        colors={['#0F0F23', '#1a1a2e', '#16213e', '#0F0F23']}
        locations={[0, 0.3, 0.7, 1]}
        style={StyleSheet.absoluteFill}
      />

      {/* Floating Orbs Background */}
      <FloatingOrb delay={0} size={150} color="rgba(59,130,246,0.15)" position={{ top: 50, left: -50 }} />
      <FloatingOrb delay={500} size={100} color="rgba(139,92,246,0.15)" position={{ top: 200, right: -30 }} />
      <FloatingOrb delay={1000} size={80} color="rgba(236,72,153,0.15)" position={{ bottom: 200, left: 30 }} />
      <FloatingOrb delay={1500} size={120} color="rgba(16,185,129,0.1)" position={{ bottom: 100, right: -40 }} />

      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />

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
              <LinearGradient
                colors={['rgba(255,255,255,0.15)', 'rgba(255,255,255,0.05)']}
                style={styles.backButtonGradient}
              >
                <Text style={{ fontSize: 22, color: '#FFFFFF' }}>←</Text>
              </LinearGradient>
            </Pressable>
          </View>

          {/* Animated Logo */}
          <Animated.View
            style={[
              styles.logoContainer,
              {
                transform: [{ scale: logoScale }],
                opacity: logoOpacity,
              },
            ]}
          >
            <View style={styles.logoWrapper}>
              {/* Outer glow */}
              <View style={styles.logoOuterGlow} />
              {/* Logo container with gradient border */}
              <LinearGradient
                colors={['#3B82F6', '#8B5CF6', '#EC4899']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.logoBorder}
              >
                <View style={styles.logoInner}>
                  <Image
                    source={require('@/assets/images/icon.png')}
                    style={styles.logo}
                    resizeMode="contain"
                  />
                </View>
              </LinearGradient>
            </View>
            <Text style={styles.appName}>{APP_INFO.NAME}</Text>
            <Text style={styles.title}>เข้าสู่ระบบ</Text>
            <Text style={styles.subtitle}>ยินดีต้อนรับกลับมา 👋</Text>
          </Animated.View>

          {/* Animated Form Card */}
          <Animated.View
            style={[
              styles.formCard,
              {
                transform: [{ translateY: formSlide }],
                opacity: formOpacity,
              },
            ]}
          >
            {/* Glassmorphism shine */}
            <View style={styles.formShine} />

            {/* Error Message */}
            {error && (
              <View style={styles.errorBox}>
                <Text style={{ fontSize: 18 }}>⚠️</Text>
                <Text style={styles.errorText}>{error}</Text>
              </View>
            )}

            {/* Email Input */}
            <View style={styles.inputGroup}>
              <Text style={styles.label}>อีเมล</Text>
              <View style={[styles.inputContainer, emailError && styles.inputError]}>
                <Text style={[styles.inputIcon, { fontSize: 18 }]}>📧</Text>
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
                <Text style={[styles.inputIcon, { fontSize: 18 }]}>🔒</Text>
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
                  <Text style={{ fontSize: 18 }}>{showPassword ? '🙈' : '👁️'}</Text>
                </Pressable>
              </View>
              {passwordError ? <Text style={styles.errorLabel}>{passwordError}</Text> : null}
            </View>

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
                    <Text style={{ fontSize: 20 }}>🔓</Text>
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
          </Animated.View>

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
                <Text style={{ fontSize: 24 }}>💬</Text>
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
    overflow: 'hidden',
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
    width: 48,
    height: 48,
    borderRadius: 16,
    overflow: 'hidden',
  },
  backButtonGradient: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  logoContainer: {
    alignItems: 'center',
    marginBottom: 32,
  },
  logoWrapper: {
    position: 'relative',
    marginBottom: 20,
  },
  logoOuterGlow: {
    position: 'absolute',
    top: -15,
    left: -15,
    right: -15,
    bottom: -15,
    borderRadius: 40,
    backgroundColor: '#3B82F6',
    opacity: 0.2,
  },
  logoBorder: {
    padding: 4,
    borderRadius: 28,
  },
  logoInner: {
    backgroundColor: '#0F0F23',
    borderRadius: 24,
    padding: 2,
  },
  logo: {
    width: 90,
    height: 90,
    borderRadius: 22,
  },
  appName: {
    fontSize: 26,
    fontWeight: 'bold',
    color: '#3B82F6',
    marginBottom: 8,
    letterSpacing: 1,
  },
  title: {
    fontSize: 24,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  subtitle: {
    fontSize: 15,
    color: '#9CA3AF',
    marginTop: 6,
  },
  formCard: {
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 28,
    padding: 24,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
    overflow: 'hidden',
    // Shadow
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.3,
    shadowRadius: 20,
    elevation: 10,
  },
  formShine: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 100,
    backgroundColor: 'rgba(255,255,255,0.03)',
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
