/**
 * เข้าสู่ระบบ — ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * - หัวน้ำเงินลายกนก + ไอคอนแอปเรืองทอง (AuthHero) · การ์ดฟอร์มขาวซ้อนขึ้นบนหัว
 * - อีเมล + รหัสผ่าน (ตรวจรูปแบบก่อนส่ง บอกผิดใต้ช่อง) · ปุ่มกันกดซ้ำในตัว (Button3D)
 * - LINE Login แสดงเฉพาะเมื่อ server เปิดใช้งานและตั้งค่าครบ · รับ callback ทั้งจาก deep link และ auth session
 * - Facebook / Google (ใต้ปุ่ม LINE) แสดงตาม /api/v1/auth/social/status — ล็อกอินผ่านหน้าเว็บ (PKCE) ด้วยค่าเดียวกับเว็บ
 *   แล้วเว็บพากลับ thaiprompt://auth → แลก code ได้ครั้งเดียว (claimWebAuthCode กันแลกซ้ำจาก deep link)
 * - เข้าสู่ระบบอยู่แล้ว → ไปหน้าแรกทันที
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  Alert,
  KeyboardAvoidingView,
  Pressable,
  ScrollView,
  StatusBar,
  StyleSheet,
  View,
} from 'react-native';
import { Text, TextInput } from '@/components/ui/Text';
import Animated, { FadeInUp } from 'react-native-reanimated';
import { router, useLocalSearchParams } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import * as Device from 'expo-device';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { APP_INFO, isFeatureEnabled } from '@/config/appConfig';
import { useAuthStore } from '@/stores/authStore';
import { checkLineLoginStatus, getSocialLoginStatus } from '@/services/api';
import { getDeviceId } from '@/services/deviceService';
import { Button3D, Card3D, Icon } from '@/components/ui';
import { AuthField } from '@/components/auth/AuthField';
import { AuthHero, AUTH_OVERLAP } from '@/components/auth/AuthHero';
import { FacebookGlyph, GoogleGlyph } from '@/components/auth/SocialGlyphs';
import {
  claimWebAuthCode,
  parseWebAuthRedirect,
  setWebAuthSessionPending,
  SOCIAL_PROVIDER_LABEL,
  WEB_AUTH_REDIRECT_URL,
  type SocialProvider,
} from '@/utils/webAuth';
import { useTheme, radii, spacing, typography } from '@/theme';

const isValidEmail = (email: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

/** ผู้ให้บริการที่ล็อกอินผ่านหน้าเว็บ (LINE ใช้เส้นเดิมของตัวเอง) */
type WebProvider = Exclude<SocialProvider, 'line'>;

/**
 * code ของ LINE ที่ส่งไปแลกแล้ว — ระดับโมดูลเพราะ deep link อาจเปิดหน้า login ใบใหม่
 * ขณะที่ auth session ของหน้าเดิมก็ได้ code ตัวเดียวกัน (code ใช้แลกได้ครั้งเดียว)
 */
let claimedLineCode: string | null = null;
const claimLineCode = (code: string) => {
  if (claimedLineCode === code) return false;
  claimedLineCode = code;
  return true;
};

// =====================================================
// หน้าหลัก
// =====================================================

export default function LoginScreen() {
  const { colors, gradients } = useTheme();
  const insets = useSafeAreaInsets();
  const {
    login,
    loginWithLine,
    handleLineCallback,
    loginWithWeb,
    handleWebAuthCallback,
    isLoading,
    error,
    clearError,
    isAuthenticated,
  } = useAuthStore();
  const params = useLocalSearchParams<{ code?: string; state?: string; error?: string }>();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [emailError, setEmailError] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [lineEnabled, setLineEnabled] = useState(false);
  const [lineLoading, setLineLoading] = useState(false);
  // ปุ่ม Facebook / Google — แสดงเมื่อเซิร์ฟเวอร์เปิดใช้ และไม่ได้ปิดไว้ในแอป (FEATURES)
  const [socialEnabled, setSocialEnabled] = useState<{ facebook: boolean; google: boolean }>({ facebook: false, google: false });
  const [socialLoading, setSocialLoading] = useState<WebProvider | null>(null);

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

  // Facebook / Google เปิดใช้งานไหม (ยังไม่ตั้งค่าบนเซิร์ฟเวอร์ / เรียกไม่สำเร็จ = ซ่อนปุ่ม)
  useEffect(() => {
    getSocialLoginStatus().then((status) => {
      if (!mountedRef.current) return;
      setSocialEnabled({
        facebook: status.facebook && isFeatureEnabled('FACEBOOK_LOGIN_ENABLED'),
        google: status.google && isFeatureEnabled('GOOGLE_LOGIN_ENABLED'),
      });
    });
  }, []);

  // callback จาก LINE (deep link)
  useEffect(() => {
    const run = async () => {
      if (params.code && params.state) {
        if (!claimLineCode(params.code)) return;
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
          if (code && state && claimLineCode(code)) {
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

  /**
   * เข้าสู่ระบบด้วย Facebook / Google ผ่านหน้าเว็บ (PKCE)
   * ขอ login_url พร้อม provider → เว็บพาไปหน้าอนุญาตทันที → กลับ thaiprompt://auth?code → แลก token
   */
  const loginSocial = useCallback(
    async (provider: WebProvider) => {
      if (socialLoading || lineLoading) return;
      const label = SOCIAL_PROVIDER_LABEL[provider];
      setSocialLoading(provider);
      try {
        const deviceId = await getDeviceId();
        const deviceName = Device.modelName || Device.deviceName || 'Mobile App';
        const result = await loginWithWeb(deviceId, deviceName, provider);
        if (!result.success || !result.loginUrl) {
          // ข้อความจาก store เป็นภาษาไทยเสมอ (แสดงในกล่องแดงบนการ์ดด้วย)
          Alert.alert(`เข้าสู่ระบบด้วย ${label}`, result.message || `เชื่อมต่อ ${label} ไม่ได้ ลองใหม่อีกครั้งนะ`);
          return;
        }

        setWebAuthSessionPending(true);
        try {
          const browserResult = await WebBrowser.openAuthSessionAsync(result.loginUrl, WEB_AUTH_REDIRECT_URL);
          // ผู้ใช้ปิดหน้าต่างเอง (cancel / dismiss) = ไม่ต้องแจ้งอะไร
          if (browserResult.type !== 'success' || !browserResult.url) return;

          const { code, state, error: authError } = parseWebAuthRedirect(browserResult.url);
          if (code && state) {
            // deep link (หน้า /auth) อาจได้ code ตัวเดียวกัน — ใครได้ก่อนคนนั้นแลก
            if (!claimWebAuthCode(code)) return;
            const ok = await handleWebAuthCallback(code, state);
            if (ok) router.replace('/(tabs)');
          } else if (authError && authError !== 'access_denied') {
            Alert.alert(`เข้าสู่ระบบด้วย ${label} ไม่สำเร็จ`, 'ลองใหม่อีกครั้ง หรือใช้อีเมลแทนนะ');
          }
        } finally {
          setWebAuthSessionPending(false);
        }
      } catch {
        Alert.alert(`เข้าสู่ระบบด้วย ${label} ไม่สำเร็จ`, 'ลองใหม่อีกครั้ง หรือใช้อีเมลแทนนะ');
      } finally {
        if (mountedRef.current) setSocialLoading(null);
      }
    },
    [socialLoading, lineLoading, loginWithWeb, handleWebAuthCallback]
  );

  const showSocial = lineEnabled || socialEnabled.facebook || socialEnabled.google;
  const socialBusy = isLoading || lineLoading || socialLoading !== null;

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
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      {/* พื้นน้ำเงินด้านบน — ดึงเลื่อนเกินขอบบนแล้วไม่เห็นพื้นงาช้าง */}
      <View style={[styles.topFill, { backgroundColor: gradients.hero[0] }]} />

      <KeyboardAvoidingView style={styles.flex} behavior="padding">
        <ScrollView
          contentContainerStyle={[styles.scroll, { paddingBottom: insets.bottom + spacing.xxxl }]}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          <AuthHero
            title="ยินดีต้อนรับกลับมา"
            subtitle={`เข้าสู่ระบบ ${APP_INFO.NAME} เพื่อช้อป สั่งตลาดสด และรับงานส่ง`}
            onBack={goBack}
          />

          <View style={styles.body}>
            <Animated.View entering={FadeInUp.delay(120).duration(420)}>
              <Card3D padding={spacing.xl} radius={26} shadow="lg">
                {!!error && (
                  <View style={[styles.errorBox, { backgroundColor: colors.dangerSoft }]} accessibilityRole="alert">
                    <Icon name="warning-circle" size={18} color={colors.danger} />
                    <Text style={[typography.bodySm, styles.flex, { color: colors.danger }]}>{error}</Text>
                  </View>
                )}

                <Text style={[typography.h2, { color: colors.textStrong }]}>เข้าสู่ระบบ</Text>

                <AuthField
                  label="อีเมล"
                  icon="envelope"
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
                  icon="lock"
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
                      style={({ pressed }) => [styles.eye, { opacity: pressed ? 0.6 : 1 }]}
                    >
                      <Icon name={showPassword ? 'eye-slash' : 'eye'} size={20} color={colors.textMuted} />
                    </Pressable>
                  }
                />

                <Button3D
                  title="เข้าสู่ระบบ"
                  icon="sign-in"
                  size="lg"
                  fullWidth
                  loading={isLoading}
                  loadingText="กำลังเข้าสู่ระบบ..."
                  onPress={submit}
                  style={styles.submit}
                />

                {showSocial && (
                  <View style={styles.dividerRow}>
                    <View style={[styles.divider, { backgroundColor: colors.divider }]} />
                    <Text style={[typography.caption, { color: colors.textMuted }]}>หรือ</Text>
                    <View style={[styles.divider, { backgroundColor: colors.divider }]} />
                  </View>
                )}

                <View style={styles.socialStack}>
                  {lineEnabled && (
                    <Button3D
                      title="เข้าสู่ระบบด้วย LINE"
                      icon="chat-circle-dots"
                      variant="success"
                      size="lg"
                      fullWidth
                      disabled={isLoading || socialLoading !== null}
                      loading={lineLoading}
                      onPress={loginLine}
                    />
                  )}
                  {socialEnabled.facebook && (
                    <Button3D
                      title="เข้าสู่ระบบด้วย Facebook"
                      icon={<FacebookGlyph />}
                      variant="facebook"
                      size="lg"
                      fullWidth
                      disabled={socialBusy && socialLoading !== 'facebook'}
                      loading={socialLoading === 'facebook'}
                      loadingText="กำลังเปิด Facebook..."
                      onPress={() => loginSocial('facebook')}
                    />
                  )}
                  {socialEnabled.google && (
                    <Button3D
                      title="เข้าสู่ระบบด้วย Google"
                      icon={<GoogleGlyph />}
                      variant="secondary"
                      size="lg"
                      fullWidth
                      disabled={socialBusy && socialLoading !== 'google'}
                      loading={socialLoading === 'google'}
                      loadingText="กำลังเปิด Google..."
                      onPress={() => loginSocial('google')}
                    />
                  )}
                </View>
              </Card3D>
            </Animated.View>

            <View style={styles.registerRow}>
              <Text style={[typography.body, { color: colors.textMuted }]}>ยังไม่มีบัญชี?</Text>
              <Button3D title="สมัครสมาชิก" variant="ghost" size="sm" onPress={() => router.push('/register')} />
            </View>
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
  topFill: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 220,
  },
  scroll: {
    flexGrow: 1,
  },
  body: {
    marginTop: -AUTH_OVERLAP,
    paddingHorizontal: spacing.screen,
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    borderRadius: radii.md,
    padding: spacing.md,
    marginBottom: spacing.md,
  },
  eye: {
    padding: spacing.xs,
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
    height: 1,
  },
  socialStack: {
    gap: spacing.md,
  },
  registerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xs,
    marginTop: spacing.xl,
  },
});
