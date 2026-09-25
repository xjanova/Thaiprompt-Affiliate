/**
 * เข้าสู่ระบบ — ธีมนวลทองคำ
 *
 * - อีเมล + รหัสผ่าน (ตรวจรูปแบบก่อนส่ง บอกผิดใต้ช่อง) · ปุ่มกันกดซ้ำในตัว (Button3D)
 * - LINE Login แสดงเฉพาะเมื่อ server เปิดใช้งานและตั้งค่าครบ · รับ callback ทั้งจาก deep link และ auth session
 * - เข้าสู่ระบบอยู่แล้ว → ไปหน้าแรกทันที
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  Alert,
  Image,
  KeyboardAvoidingView,
  Pressable,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import Animated, { FadeInDown, FadeInUp } from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useLocalSearchParams } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { APP_INFO } from '@/config/appConfig';
import { useAuthStore } from '@/stores/authStore';
import { checkLineLoginStatus } from '@/services/api';
import { Button3D, Card3D } from '@/components/ui';
import { AuthField } from '@/components/auth/AuthField';
import { useTheme, clayShadowStyle, radii, spacing, typography } from '@/theme';

const isValidEmail = (email: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

// =====================================================
// หน้าหลัก
// =====================================================

export default function LoginScreen() {
  const { colors, gradients, isDark } = useTheme();
  const insets = useSafeAreaInsets();
  const { login, loginWithLine, handleLineCallback, isLoading, error, clearError, isAuthenticated } = useAuthStore();
  const params = useLocalSearchParams<{ code?: string; state?: string; error?: string }>();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [emailError, setEmailError] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [lineEnabled, setLineEnabled] = useState(false);
  const [lineLoading, setLineLoading] = useState(false);

  const mountedRef = useRef(true);
  const passwordRef = useRef<TextInput>(null);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // เข้าสู่ระบบแล้ว → หน้าแรก
  useEffect(() => {
    if (isAuthenticated) router.replace('/(tabs)');
  }, [isAuthenticated]);

  useEffect(() => () => clearError(), [clearError]);

  // LINE Login เปิดใช้งานไหม
  useEffect(() => {
    checkLineLoginStatus()
      .then((status) => {
        if (mountedRef.current) setLineEnabled(!!(status.success && status.enabled && status.configured));
      })
      .catch(() => {
        if (mountedRef.current) setLineEnabled(false);
      });
  }, []);

  // callback จาก LINE (deep link)
  useEffect(() => {
    const run = async () => {
      if (params.code && params.state) {
        setLineLoading(true);
        try {
          const ok = await handleLineCallback(params.code, params.state);
          if (ok) router.replace('/(tabs)');
        } finally {
          if (mountedRef.current) setLineLoading(false);
        }
      } else if (params.error) {
        Alert.alert('เข้าสู่ระบบด้วย LINE ไม่สำเร็จ', 'ลองใหม่อีกครั้ง หรือใช้อีเมลแทนนะ');
      }
    };
    run();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [params.code, params.state, params.error]);

  const loginLine = useCallback(async () => {
    if (lineLoading) return;
    setLineLoading(true);
    try {
      const result = await loginWithLine();
      if (result.success && result.authUrl) {
        const browserResult = await WebBrowser.openAuthSessionAsync(result.authUrl, 'thaiprompt://login');
        if (browserResult.type === 'success' && browserResult.url) {
          const url = new URL(browserResult.url);
          const code = url.searchParams.get('code');
          const state = url.searchParams.get('state');
          if (code && state) {
            const ok = await handleLineCallback(code, state);
            if (ok) router.replace('/(tabs)');
          }
        }
      } else {
        Alert.alert('เข้าสู่ระบบด้วย LINE', result.message || 'เชื่อมต่อ LINE ไม่ได้ ลองใหม่อีกครั้งนะ');
      }
    } catch {
      Alert.alert('เข้าสู่ระบบด้วย LINE ไม่สำเร็จ', 'ลองใหม่อีกครั้ง หรือใช้อีเมลแทนนะ');
    } finally {
      if (mountedRef.current) setLineLoading(false);
    }
  }, [lineLoading, loginWithLine, handleLineCallback]);

  const validate = (): boolean => {
    let ok = true;
    if (!email.trim()) {
      setEmailError('กรอกอีเมลก่อนนะ');
      ok = false;
    } else if (!isValidEmail(email.trim())) {
      setEmailError('รูปแบบอีเมลยังไม่ถูกต้อง');
      ok = false;
    }
    if (!password) {
      setPasswordError('กรอกรหัสผ่านก่อนนะ');
      ok = false;
    } else if (password.length < 8) {
      setPasswordError('รหัสผ่านมีอย่างน้อย 8 ตัวอักษร');
      ok = false;
    }
    return ok;
  };

  const submit = async () => {
    if (!validate()) return;
    const ok = await login(email.trim(), password);
    if (ok) router.replace('/(tabs)');
  };

  const goBack = () => {
    if (router.canGoBack()) router.back();
    else router.replace('/(tabs)' as never);
  };

  return (
    <View style={[styles.root, { backgroundColor: colors.background }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={colors.background} />
      <LinearGradient colors={gradients.hero} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.heroBg} />

      <KeyboardAvoidingView style={styles.flex} behavior="padding">
        <ScrollView
          contentContainerStyle={[styles.scroll, { paddingTop: insets.top + spacing.sm, paddingBottom: insets.bottom + spacing.xxxl }]}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          <Pressable
            onPress={goBack}
            accessibilityRole="button"
            accessibilityLabel="ย้อนกลับ"
            hitSlop={10}
            style={({ pressed }) => [
              styles.back,
              { backgroundColor: colors.card, opacity: pressed ? 0.7 : 1 },
              clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
            ]}
          >
            <Text style={[styles.backIcon, { color: colors.textStrong }]}>‹</Text>
          </Pressable>

          <Animated.View entering={FadeInDown.duration(420)} style={styles.brand}>
            <View style={[styles.logoRing, clayShadowStyle('md', colors.shadowDark, colors.shadowLight)]}>
              <LinearGradient colors={gradients.gold} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.logoGradient}>
                <View style={[styles.logoInner, { backgroundColor: colors.card }]}>
                  <Image source={require('@/assets/images/icon.png')} style={styles.logo} resizeMode="contain" />
                </View>
              </LinearGradient>
            </View>
            <Text style={[typography.display, styles.center, { color: colors.textStrong }]}>ยินดีต้อนรับกลับมา</Text>
            <Text style={[typography.body, styles.center, { color: colors.textMuted }]}>
              เข้าสู่ระบบ {APP_INFO.NAME} เพื่อช้อป สั่งตลาดสด และรับงานส่ง
            </Text>
          </Animated.View>

          <Animated.View entering={FadeInUp.delay(120).duration(420)}>
            <Card3D padding={spacing.xl} gradientBorder>
              {!!error && (
                <View style={[styles.errorBox, { backgroundColor: colors.dangerSoft }]} accessibilityRole="alert">
                  <Text style={[typography.bodySm, { color: colors.danger }]}>⚠️ {error}</Text>
                </View>
              )}

              <AuthField
                label="อีเมล"
                icon="📧"
                value={email}
                onChangeText={(t) => {
                  setEmail(t);
                  setEmailError('');
                }}
                placeholder="example@email.com"
                keyboardType="email-address"
                autoCapitalize="none"
                autoCorrect={false}
                autoComplete="email"
                textContentType="emailAddress"
                returnKeyType="next"
                onSubmitEditing={() => passwordRef.current?.focus()}
                error={emailError}
              />
              <AuthField
                ref={passwordRef}
                label="รหัสผ่าน"
                icon="🔒"
                value={password}
                onChangeText={(t) => {
                  setPassword(t);
                  setPasswordError('');
                }}
                placeholder="อย่างน้อย 8 ตัวอักษร"
                secureTextEntry={!showPassword}
                autoCapitalize="none"
                autoComplete="password"
                textContentType="password"
                returnKeyType="go"
                onSubmitEditing={submit}
                error={passwordError}
                right={
                  <Pressable
                    onPress={() => setShowPassword((v) => !v)}
                    hitSlop={10}
                    accessibilityRole="button"
                    accessibilityLabel={showPassword ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'}
                    style={styles.eye}
                  >
                    <Text style={styles.eyeIcon}>{showPassword ? '🙈' : '👁️'}</Text>
                  </Pressable>
                }
              />

              <Button3D
                title="เข้าสู่ระบบ"
                icon="🔓"
                size="lg"
                fullWidth
                loading={isLoading}
                loadingText="กำลังเข้าสู่ระบบ..."
                onPress={submit}
                style={styles.submit}
              />

              {lineEnabled && (
                <>
                  <View style={styles.dividerRow}>
                    <View style={[styles.divider, { backgroundColor: colors.border }]} />
                    <Text style={[typography.caption, { color: colors.textMuted }]}>หรือ</Text>
                    <View style={[styles.divider, { backgroundColor: colors.border }]} />
                  </View>
                  <Button3D
                    title="เข้าสู่ระบบด้วย LINE"
                    icon="💬"
                    variant="success"
                    size="lg"
                    fullWidth
                    disabled={isLoading}
                    loading={lineLoading}
                    onPress={loginLine}
                  />
                </>
              )}
            </Card3D>
          </Animated.View>

          <View style={styles.registerRow}>
            <Text style={[typography.body, { color: colors.textMuted }]}>ยังไม่มีบัญชี?</Text>
            <Button3D title="สมัครสมาชิก" variant="ghost" size="sm" onPress={() => router.push('/register')} />
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  heroBg: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 300,
    borderBottomLeftRadius: 48,
    borderBottomRightRadius: 48,
    opacity: 0.9,
  },
  scroll: {
    flexGrow: 1,
    paddingHorizontal: spacing.xl,
  },
  back: {
    width: 42,
    height: 42,
    borderRadius: radii.md,
    alignItems: 'center',
    justifyContent: 'center',
  },
  backIcon: {
    fontSize: 28,
    lineHeight: 30,
    fontWeight: '600',
    marginTop: -2,
  },
  brand: {
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    marginBottom: spacing.xl,
  },
  logoRing: {
    borderRadius: 48,
    marginBottom: spacing.sm,
  },
  logoGradient: {
    width: 96,
    height: 96,
    borderRadius: 48,
    padding: 4,
  },
  logoInner: {
    flex: 1,
    borderRadius: 44,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  logo: {
    width: 64,
    height: 64,
  },
  center: {
    textAlign: 'center',
  },
  errorBox: {
    borderRadius: radii.md,
    padding: spacing.md,
    marginBottom: spacing.sm,
  },
  eye: {
    padding: spacing.xs,
  },
  eyeIcon: {
    fontSize: 18,
  },
  submit: {
    marginTop: spacing.xl,
  },
  dividerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginVertical: spacing.lg,
  },
  divider: {
    flex: 1,
    height: StyleSheet.hairlineWidth,
  },
  registerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xs,
    marginTop: spacing.xl,
  },
});
