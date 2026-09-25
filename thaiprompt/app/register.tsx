/**
 * สมัครสมาชิก — ธีมนวลทองคำ (3 ขั้น: ข้อมูล → รหัสผ่าน → ยืนยัน)
 *
 * - POST /register ผ่าน client กลาง → ข้อความผิดพลาดภาษาไทยเสมอ (validation จาก server แสดงข้อแรก)
 * - ตรวจทีละขั้นก่อนไปต่อ · ยอมรับเงื่อนไขก่อนสมัคร (แตะอ่านเงื่อนไข/นโยบายได้) · ปุ่มกันกดซ้ำในตัว
 * - ?ref=<รหัส> จากลิงก์ชวนเพื่อน → เติมรหัสให้เอง
 */

import React, { useRef, useState } from 'react';
import {
  Alert,
  KeyboardAvoidingView,
  Pressable,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import Animated, { FadeIn } from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useLocalSearchParams } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { APP_INFO } from '@/config/appConfig';
import { apiPost, isThaiText } from '@/services/api/client';
import { openUrl } from '@/utils/navigation';
import { Button3D, Card3D, Pill, resultHaptic, selectionHaptic } from '@/components/ui';
import { AuthField } from '@/components/auth/AuthField';
import { useTheme, clayShadowStyle, radii, spacing, typography, type Tone } from '@/theme';

const isValidEmail = (email: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
const PHONE_RE = /^0\d{8,9}$/;

type Step = 1 | 2 | 3;

const STEPS: Array<{ step: Step; label: string }> = [
  { step: 1, label: 'ข้อมูล' },
  { step: 2, label: 'รหัสผ่าน' },
  { step: 3, label: 'ยืนยัน' },
];

const strengthOf = (pwd: string): { level: number; text: string; tone: Tone } => {
  if (!pwd) return { level: 0, text: '', tone: 'neutral' };
  let score = 0;
  if (pwd.length >= 8) score++;
  if (pwd.length >= 12) score++;
  if (/[A-Z]/.test(pwd)) score++;
  if (/[a-z]/.test(pwd)) score++;
  if (/[0-9]/.test(pwd)) score++;
  if (/[^A-Za-z0-9]/.test(pwd)) score++;
  if (score <= 2) return { level: 1, text: 'ง่ายไป', tone: 'danger' };
  if (score <= 4) return { level: 2, text: 'พอใช้', tone: 'warning' };
  return { level: 3, text: 'แข็งแรง', tone: 'success' };
};

export default function RegisterScreen() {
  const { colors, gradients, isDark } = useTheme();
  const insets = useSafeAreaInsets();
  const { ref } = useLocalSearchParams<{ ref?: string }>();

  const [step, setStep] = useState<Step>(1);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [referral, setReferral] = useState(typeof ref === 'string' ? ref.slice(0, 30) : '');
  const [accept, setAccept] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [serverError, setServerError] = useState('');

  const emailRef = useRef<TextInput>(null);
  const phoneRef = useRef<TextInput>(null);
  const confirmRef = useRef<TextInput>(null);
  const submittingRef = useRef(false);

  const strength = strengthOf(password);

  const clearError = (key: string) => {
    if (errors[key]) setErrors((prev) => ({ ...prev, [key]: '' }));
  };

  const validateStep1 = () => {
    const next: Record<string, string> = {};
    if (name.trim().length < 2) next.name = 'ใส่ชื่อ-นามสกุลอย่างน้อย 2 ตัวอักษร';
    if (!isValidEmail(email.trim())) next.email = 'รูปแบบอีเมลยังไม่ถูกต้อง';
    const cleanPhone = phone.replace(/[\s-]/g, '');
    if (cleanPhone && !PHONE_RE.test(cleanPhone)) next.phone = 'เบอร์โทรขึ้นต้นด้วย 0 และมี 9–10 หลัก';
    setErrors(next);
    return Object.keys(next).length === 0;
  };

  const validateStep2 = () => {
    const next: Record<string, string> = {};
    if (password.length < 8 || password.length > 128) next.password = 'รหัสผ่านต้องมี 8–128 ตัวอักษร';
    if (password !== confirm) next.confirm = 'รหัสผ่านสองช่องยังไม่ตรงกัน';
    setErrors(next);
    return Object.keys(next).length === 0;
  };

  const next = () => {
    if (step === 1 && validateStep1()) setStep(2);
    else if (step === 2 && validateStep2()) setStep(3);
    else resultHaptic('warning');
  };

  const back = () => {
    if (step > 1) {
      setStep((s) => (s - 1) as Step);
      return;
    }
    if (router.canGoBack()) router.back();
    else router.replace('/login');
  };

  const register = async () => {
    if (submittingRef.current) return;
    if (!accept) {
      Alert.alert('ยอมรับเงื่อนไขก่อนนะ', 'แตะช่องยอมรับเงื่อนไขการใช้งานและนโยบายความเป็นส่วนตัว');
      return;
    }
    submittingRef.current = true;
    setServerError('');
    const result = await apiPost<unknown>(
      '/register',
      {
        name: name.trim(),
        email: email.trim().toLowerCase(),
        phone: phone.replace(/[\s-]/g, '') || undefined,
        password,
        password_confirmation: confirm,
        referral_code: referral.trim() || undefined,
      },
      { fallbackMessage: 'สมัครสมาชิกไม่สำเร็จ ลองใหม่อีกครั้งนะ' }
    );
    submittingRef.current = false;

    if (result.success) {
      resultHaptic('success');
      Alert.alert('สมัครสำเร็จ! 🎉', 'เข้าสู่ระบบด้วยอีเมลและรหัสผ่านที่เพิ่งตั้งได้เลย', [
        { text: 'เข้าสู่ระบบ', onPress: () => router.replace('/login') },
      ]);
      return;
    }
    resultHaptic('error');
    // อีเมลซ้ำ ฯลฯ → กลับไปขั้นที่ผิดให้แก้ได้ทันที
    const fieldErrors = result.errors || {};
    // ข้อความของช่องนั้นเอง (ถ้าเป็นภาษาไทย) ไม่งั้นใช้ข้อความรวมที่ client แปลให้แล้ว
    const msgFor = (key: string): string => {
      const first = fieldErrors[key]?.[0];
      return isThaiText(first) ? first : result.message;
    };
    if (fieldErrors.email || fieldErrors.name || fieldErrors.phone) {
      setErrors({
        ...(fieldErrors.name ? { name: msgFor('name') } : {}),
        ...(fieldErrors.email ? { email: msgFor('email') } : {}),
        ...(fieldErrors.phone ? { phone: msgFor('phone') } : {}),
      });
      setStep(1);
    } else if (fieldErrors.password) {
      setErrors({ password: msgFor('password') });
      setStep(2);
    }
    setServerError(result.message);
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
          <View style={styles.topRow}>
            <Pressable
              onPress={back}
              accessibilityRole="button"
              accessibilityLabel="ย้อนกลับ"
              hitSlop={10}
              style={({ pressed }) => [
                styles.backBtn,
                { backgroundColor: colors.card, opacity: pressed ? 0.7 : 1 },
                clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
              ]}
            >
              <Text style={[styles.backIcon, { color: colors.textStrong }]}>‹</Text>
            </Pressable>
            <Button3D title="มีบัญชีแล้ว" variant="ghost" size="sm" onPress={() => router.replace('/login')} />
          </View>

          <View style={styles.brand}>
            <Text style={[typography.display, styles.center, { color: colors.textStrong }]}>สมัครสมาชิก</Text>
            <Text style={[typography.body, styles.center, { color: colors.textMuted }]}>
              สมัครฟรี ช้อป สั่งตลาดสด และรับงานส่งกับ {APP_INFO.NAME}
            </Text>
          </View>

          {/* ---------- ขั้นตอน ---------- */}
          <View style={styles.steps} accessibilityRole="progressbar" accessibilityLabel={`ขั้นที่ ${step} จาก 3`}>
            {STEPS.map((s, i) => {
              const done = step > s.step;
              const current = step === s.step;
              return (
                <React.Fragment key={s.step}>
                  <View style={styles.stepItem}>
                    <View
                      style={[
                        styles.stepCircle,
                        {
                          backgroundColor: done ? colors.success : current ? colors.gold : colors.inset,
                          borderColor: current ? colors.goldDeep : colors.border,
                        },
                      ]}
                    >
                      <Text style={[typography.bodyStrong, { color: done ? colors.textOnAccent : current ? colors.textOnGold : colors.textMuted }]}>
                        {done ? '✓' : s.step}
                      </Text>
                    </View>
                    <Text style={[typography.micro, { color: current ? colors.goldDeep : colors.textMuted }]}>{s.label}</Text>
                  </View>
                  {i < STEPS.length - 1 && (
                    <View style={[styles.stepLine, { backgroundColor: step > s.step ? colors.success : colors.border }]} />
                  )}
                </React.Fragment>
              );
            })}
          </View>

          {!!referral && step === 1 && (
            <Pill label={`ได้รับคำชวนจากเพื่อน · รหัส ${referral}`} icon="🎁" tone="gold" size="md" style={styles.refPill} />
          )}

          <Card3D padding={spacing.xl} gradientBorder>
            <Animated.View key={step} entering={FadeIn.duration(200)}>
              {step === 1 && (
                <>
                  <Text style={[typography.h2, { color: colors.textStrong }]}>ข้อมูลของคุณ</Text>
                  <AuthField
                    label="ชื่อ-นามสกุล"
                    icon="👤"
                    value={name}
                    onChangeText={(t) => {
                      setName(t);
                      clearError('name');
                    }}
                    placeholder="ชื่อจริง นามสกุลจริง"
                    autoComplete="name"
                    textContentType="name"
                    returnKeyType="next"
                    onSubmitEditing={() => emailRef.current?.focus()}
                    maxLength={100}
                    error={errors.name}
                  />
                  <AuthField
                    ref={emailRef}
                    label="อีเมล"
                    icon="📧"
                    value={email}
                    onChangeText={(t) => {
                      setEmail(t);
                      clearError('email');
                    }}
                    placeholder="example@email.com"
                    keyboardType="email-address"
                    autoCapitalize="none"
                    autoCorrect={false}
                    autoComplete="email"
                    textContentType="emailAddress"
                    returnKeyType="next"
                    onSubmitEditing={() => phoneRef.current?.focus()}
                    error={errors.email}
                  />
                  <AuthField
                    ref={phoneRef}
                    label="เบอร์โทรศัพท์ (ไม่บังคับ)"
                    icon="📱"
                    value={phone}
                    onChangeText={(t) => {
                      setPhone(t);
                      clearError('phone');
                    }}
                    placeholder="08X-XXX-XXXX"
                    keyboardType="phone-pad"
                    autoComplete="tel"
                    textContentType="telephoneNumber"
                    returnKeyType="done"
                    onSubmitEditing={next}
                    maxLength={15}
                    error={errors.phone}
                  />
                  <Button3D title="ถัดไป" iconRight="→" size="lg" fullWidth onPress={next} style={styles.primary} />
                </>
              )}

              {step === 2 && (
                <>
                  <Text style={[typography.h2, { color: colors.textStrong }]}>ตั้งรหัสผ่าน</Text>
                  <AuthField
                    label="รหัสผ่าน"
                    icon="🔒"
                    value={password}
                    onChangeText={(t) => {
                      setPassword(t);
                      clearError('password');
                    }}
                    placeholder="อย่างน้อย 8 ตัวอักษร"
                    secureTextEntry={!showPassword}
                    autoCapitalize="none"
                    autoComplete="password-new"
                    textContentType="newPassword"
                    returnKeyType="next"
                    onSubmitEditing={() => confirmRef.current?.focus()}
                    error={errors.password}
                    right={
                      <Pressable
                        onPress={() => setShowPassword((v) => !v)}
                        hitSlop={10}
                        accessibilityRole="button"
                        accessibilityLabel={showPassword ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'}
                      >
                        <Text style={styles.eye}>{showPassword ? '🙈' : '👁️'}</Text>
                      </Pressable>
                    }
                  />
                  {strength.level > 0 && (
                    <View style={styles.strengthRow}>
                      {[1, 2, 3].map((n) => (
                        <View
                          key={n}
                          style={[
                            styles.strengthBar,
                            {
                              backgroundColor:
                                n <= strength.level
                                  ? strength.tone === 'danger'
                                    ? colors.danger
                                    : strength.tone === 'warning'
                                      ? colors.warning
                                      : colors.success
                                  : colors.border,
                            },
                          ]}
                        />
                      ))}
                      <Text style={[typography.caption, { color: colors.textMuted }]}>{strength.text}</Text>
                    </View>
                  )}
                  <AuthField
                    ref={confirmRef}
                    label="ยืนยันรหัสผ่าน"
                    icon="🛡️"
                    value={confirm}
                    onChangeText={(t) => {
                      setConfirm(t);
                      clearError('confirm');
                    }}
                    placeholder="พิมพ์รหัสผ่านอีกครั้ง"
                    secureTextEntry={!showPassword}
                    autoCapitalize="none"
                    returnKeyType="done"
                    onSubmitEditing={next}
                    error={errors.confirm}
                  />
                  <View style={styles.row}>
                    <Button3D title="ย้อนกลับ" variant="secondary" size="lg" onPress={back} style={styles.flex} />
                    <Button3D title="ถัดไป" iconRight="→" size="lg" onPress={next} style={styles.flex} />
                  </View>
                </>
              )}

              {step === 3 && (
                <>
                  <Text style={[typography.h2, { color: colors.textStrong }]}>ตรวจแล้วกดสมัคร</Text>
                  <Card3D variant="inset" padding={spacing.md} style={styles.summary}>
                    <View style={styles.summaryRow}>
                      <Text style={styles.summaryIcon}>👤</Text>
                      <View style={styles.flex}>
                        <Text style={[typography.bodyStrong, { color: colors.textStrong }]} numberOfLines={1}>
                          {name.trim()}
                        </Text>
                        <Text style={[typography.caption, { color: colors.textMuted }]} numberOfLines={1}>
                          {email.trim()}
                          {phone ? ` · ${phone}` : ''}
                        </Text>
                      </View>
                      <Button3D title="แก้" size="sm" variant="ghost" onPress={() => setStep(1)} />
                    </View>
                  </Card3D>

                  <AuthField
                    label="รหัสชวนเพื่อน (ไม่บังคับ)"
                    icon="🎁"
                    value={referral}
                    onChangeText={(t) => setReferral(t.replace(/\s/g, '').slice(0, 30))}
                    placeholder="ใส่ถ้ามีเพื่อนชวนมา"
                    autoCapitalize="characters"
                    autoCorrect={false}
                  />

                  <Pressable
                    onPress={() => {
                      selectionHaptic();
                      setAccept((v) => !v);
                    }}
                    accessibilityRole="checkbox"
                    accessibilityState={{ checked: accept }}
                    accessibilityLabel="ยอมรับเงื่อนไขการใช้งานและนโยบายความเป็นส่วนตัว"
                    style={styles.termsRow}
                  >
                    <View
                      style={[
                        styles.checkbox,
                        { borderColor: accept ? colors.success : colors.border, backgroundColor: accept ? colors.success : colors.inset },
                      ]}
                    >
                      {accept && <Text style={[styles.check, { color: colors.textOnAccent }]}>✓</Text>}
                    </View>
                    <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
                      ฉันยอมรับ{' '}
                      <Text style={[styles.link, { color: colors.goldDeep }]} onPress={() => openUrl(APP_INFO.TERMS_URL, 'ข้อกำหนดการใช้งาน', '📋')}>
                        เงื่อนไขการใช้งาน
                      </Text>{' '}
                      และ{' '}
                      <Text style={[styles.link, { color: colors.goldDeep }]} onPress={() => openUrl(APP_INFO.PRIVACY_URL, 'นโยบายความเป็นส่วนตัว', '📄')}>
                        นโยบายความเป็นส่วนตัว
                      </Text>
                    </Text>
                  </Pressable>

                  {!!serverError && (
                    <View style={[styles.errorBox, { backgroundColor: colors.dangerSoft }]} accessibilityRole="alert">
                      <Text style={[typography.bodySm, { color: colors.danger }]}>⚠️ {serverError}</Text>
                    </View>
                  )}

                  <View style={styles.row}>
                    <Button3D title="ย้อนกลับ" variant="secondary" size="lg" onPress={back} style={styles.flex} />
                    <Button3D
                      title="สมัครเลย"
                      icon="🎉"
                      variant="success"
                      size="lg"
                      disabled={!accept}
                      loadingText="กำลังสมัคร..."
                      onPress={register}
                      style={styles.flex}
                    />
                  </View>
                </>
              )}
            </Animated.View>
          </Card3D>
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
    height: 280,
    borderBottomLeftRadius: 48,
    borderBottomRightRadius: 48,
    opacity: 0.9,
  },
  scroll: {
    flexGrow: 1,
    paddingHorizontal: spacing.xl,
  },
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  backBtn: {
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
    gap: spacing.xs,
    marginTop: spacing.lg,
    marginBottom: spacing.lg,
  },
  center: {
    textAlign: 'center',
  },
  steps: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
  },
  stepItem: {
    alignItems: 'center',
    gap: spacing.xxs,
  },
  stepCircle: {
    width: 36,
    height: 36,
    borderRadius: 18,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepLine: {
    width: 44,
    height: 3,
    borderRadius: 2,
    marginHorizontal: spacing.xs,
    marginBottom: spacing.md,
  },
  refPill: {
    alignSelf: 'center',
    marginBottom: spacing.md,
  },
  primary: {
    marginTop: spacing.xl,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.xl,
  },
  eye: {
    fontSize: 18,
  },
  strengthRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    marginTop: spacing.sm,
  },
  strengthBar: {
    flex: 1,
    height: 5,
    borderRadius: 3,
  },
  summary: {
    marginTop: spacing.md,
  },
  summaryRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  summaryIcon: {
    fontSize: 28,
  },
  termsRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginTop: spacing.lg,
  },
  checkbox: {
    width: 26,
    height: 26,
    borderRadius: 8,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  check: {
    fontSize: 15,
    fontWeight: '800',
  },
  link: {
    fontWeight: '700',
    textDecorationLine: 'underline',
  },
  errorBox: {
    marginTop: spacing.md,
    borderRadius: radii.md,
    padding: spacing.md,
  },
});
