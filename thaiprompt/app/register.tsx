/**
 * Register Screen - Premium Stable Version
 * ใช้ StyleSheet แทน NativeWind
 * ปิด AnimatedBackground ชั่วคราวเพื่อทดสอบ crash
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
  TextInput,
  StyleSheet,
  StatusBar,
  ActivityIndicator,
  Animated,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useLocalSearchParams } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import axios from 'axios';
import { API_BASE_URL } from '@/constants';

// Validation
const isValidEmail = (email: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
const isValidPassword = (password: string) => password.length >= 8 && password.length <= 128;

export default function RegisterScreen() {
  const { ref } = useLocalSearchParams<{ ref?: string }>();

  const [currentStep, setCurrentStep] = useState(1);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [referralCode, setReferralCode] = useState(ref || '');
  const [acceptTerms, setAcceptTerms] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const [nameError, setNameError] = useState('');
  const [emailError, setEmailError] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const [confirmPasswordError, setConfirmPasswordError] = useState('');

  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');

  // Password strength
  const getPasswordStrength = (pwd: string) => {
    if (!pwd) return { level: 0, text: '', color: '#4B5563' };
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

  // Validate Step 1
  const validateStep1 = () => {
    let isValid = true;
    if (!name.trim() || name.trim().length < 2) {
      setNameError('กรุณากรอกชื่อ-นามสกุล (อย่างน้อย 2 ตัวอักษร)');
      isValid = false;
    } else setNameError('');

    if (!email.trim() || !isValidEmail(email)) {
      setEmailError('กรุณากรอกอีเมลให้ถูกต้อง');
      isValid = false;
    } else setEmailError('');

    return isValid;
  };

  // Validate Step 2
  const validateStep2 = () => {
    let isValid = true;
    if (!password || !isValidPassword(password)) {
      setPasswordError('รหัสผ่านต้องมี 8-128 ตัวอักษร');
      isValid = false;
    } else setPasswordError('');

    if (password !== confirmPassword) {
      setConfirmPasswordError('รหัสผ่านไม่ตรงกัน');
      isValid = false;
    } else setConfirmPasswordError('');

    return isValid;
  };

  // Handle Next Step
  const handleNextStep = () => {
    if (currentStep === 1 && validateStep1()) setCurrentStep(2);
    else if (currentStep === 2 && validateStep2()) setCurrentStep(3);
  };

  // Handle Register
  const handleRegister = async () => {
    if (!acceptTerms) {
      Alert.alert('ข้อกำหนด', 'กรุณายอมรับเงื่อนไขการใช้งาน');
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
        Alert.alert('สำเร็จ! 🎉', 'สมัครสมาชิกเรียบร้อย', [
          { text: 'เข้าสู่ระบบ', onPress: () => router.replace('/login') },
        ]);
      } else {
        setError(response.data.message || 'เกิดข้อผิดพลาด');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'เกิดข้อผิดพลาดในการสมัครสมาชิก');
    } finally {
      setIsLoading(false);
    }
  };

  // Step Indicator
  const StepIndicator = ({ step, label }: { step: number; label: string }) => {
    const isActive = currentStep >= step;
    const isCurrent = currentStep === step;
    return (
      <View style={styles.stepItem}>
        <View style={[
          styles.stepCircle,
          isActive && styles.stepCircleActive,
          isCurrent && styles.stepCircleCurrent,
        ]}>
          {isActive && !isCurrent ? (
            <Ionicons name="checkmark" size={16} color="#FFF" />
          ) : (
            <Text style={[styles.stepNumber, isActive && styles.stepNumberActive]}>{step}</Text>
          )}
        </View>
        <Text style={[styles.stepLabel, isActive && styles.stepLabelActive]}>{label}</Text>
      </View>
    );
  };

  return (
    <View style={styles.container}>
      {/* Gradient Background แทน AnimatedBackground ชั่วคราว */}
      <LinearGradient
        colors={['#0F0F23', '#1a1a2e', '#16213e']}
        style={StyleSheet.absoluteFill}
      />
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
              <Ionicons name="arrow-back" size={24} color="#FFF" />
            </Pressable>
            <Pressable onPress={() => router.push('/login')}>
              <Text style={styles.loginLink}>มีบัญชีแล้ว? เข้าสู่ระบบ</Text>
            </Pressable>
          </View>

          {/* Logo */}
          <View style={styles.logoContainer}>
            <LinearGradient
              colors={['#3B82F6', '#8B5CF6', '#06B6D4']}
              style={styles.logoBox}
            >
              <Text style={styles.logoEmoji}>🚀</Text>
            </LinearGradient>
            <Text style={styles.title}>สมัครสมาชิก</Text>
            <Text style={styles.subtitle}>เริ่มต้นสร้างรายได้กับ Thaiprompt</Text>
          </View>

          {/* Step Indicators */}
          <View style={styles.stepRow}>
            <StepIndicator step={1} label="ข้อมูล" />
            <View style={[styles.stepLine, currentStep >= 2 && styles.stepLineActive]} />
            <StepIndicator step={2} label="รหัสผ่าน" />
            <View style={[styles.stepLine, currentStep >= 3 && styles.stepLineActive]} />
            <StepIndicator step={3} label="ยืนยัน" />
          </View>

          {/* Referral Badge */}
          {ref && currentStep === 1 && (
            <View style={styles.referralBadge}>
              <Ionicons name="gift" size={20} color="#10B981" />
              <Text style={styles.referralText}>คุณได้รับเชิญ! รหัส: {ref}</Text>
            </View>
          )}

          {/* Form Card */}
          <View style={styles.formCard}>
            {/* Step 1 */}
            {currentStep === 1 && (
              <>
                <Text style={styles.stepTitle}>ข้อมูลส่วนตัว</Text>

                <View style={styles.inputGroup}>
                  <Text style={styles.label}>ชื่อ-นามสกุล</Text>
                  <View style={[styles.inputContainer, nameError && styles.inputError]}>
                    <Ionicons name="person-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                    <TextInput
                      style={styles.input}
                      placeholder="กรอกชื่อจริง - นามสกุลจริง"
                      placeholderTextColor="#6B7280"
                      value={name}
                      onChangeText={(t) => { setName(t); setNameError(''); }}
                      autoCapitalize="words"
                    />
                  </View>
                  {nameError ? <Text style={styles.errorLabel}>{nameError}</Text> : null}
                </View>

                <View style={styles.inputGroup}>
                  <Text style={styles.label}>อีเมล</Text>
                  <View style={[styles.inputContainer, emailError && styles.inputError]}>
                    <Ionicons name="mail-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                    <TextInput
                      style={styles.input}
                      placeholder="example@email.com"
                      placeholderTextColor="#6B7280"
                      value={email}
                      onChangeText={(t) => { setEmail(t); setEmailError(''); }}
                      keyboardType="email-address"
                      autoCapitalize="none"
                    />
                  </View>
                  {emailError ? <Text style={styles.errorLabel}>{emailError}</Text> : null}
                </View>

                <View style={styles.inputGroup}>
                  <Text style={styles.label}>เบอร์โทรศัพท์ (ไม่บังคับ)</Text>
                  <View style={styles.inputContainer}>
                    <Ionicons name="call-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                    <TextInput
                      style={styles.input}
                      placeholder="08X-XXX-XXXX"
                      placeholderTextColor="#6B7280"
                      value={phone}
                      onChangeText={setPhone}
                      keyboardType="phone-pad"
                    />
                  </View>
                </View>

                <Pressable style={styles.nextButton} onPress={handleNextStep}>
                  <LinearGradient colors={['#3B82F6', '#8B5CF6']} style={styles.gradientButton}>
                    <Text style={styles.buttonText}>ถัดไป</Text>
                    <Ionicons name="arrow-forward" size={20} color="#FFF" />
                  </LinearGradient>
                </Pressable>
              </>
            )}

            {/* Step 2 */}
            {currentStep === 2 && (
              <>
                <Text style={styles.stepTitle}>ตั้งรหัสผ่าน</Text>

                <View style={styles.inputGroup}>
                  <Text style={styles.label}>รหัสผ่าน</Text>
                  <View style={[styles.inputContainer, passwordError && styles.inputError]}>
                    <Ionicons name="lock-closed-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                    <TextInput
                      style={styles.input}
                      placeholder="อย่างน้อย 8 ตัวอักษร"
                      placeholderTextColor="#6B7280"
                      value={password}
                      onChangeText={(t) => { setPassword(t); setPasswordError(''); }}
                      secureTextEntry={!showPassword}
                    />
                    <Pressable onPress={() => setShowPassword(!showPassword)} style={styles.eyeButton}>
                      <Ionicons name={showPassword ? 'eye-off-outline' : 'eye-outline'} size={20} color="#9CA3AF" />
                    </Pressable>
                  </View>
                  {passwordError ? <Text style={styles.errorLabel}>{passwordError}</Text> : null}
                </View>

                {/* Password Strength */}
                {password.length > 0 && (
                  <View style={styles.strengthRow}>
                    <Text style={styles.strengthLabel}>ความแข็งแรง:</Text>
                    <Text style={[styles.strengthText, { color: passwordStrength.color }]}>
                      {passwordStrength.text}
                    </Text>
                  </View>
                )}

                <View style={styles.inputGroup}>
                  <Text style={styles.label}>ยืนยันรหัสผ่าน</Text>
                  <View style={[styles.inputContainer, confirmPasswordError && styles.inputError]}>
                    <Ionicons name="shield-checkmark-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                    <TextInput
                      style={styles.input}
                      placeholder="กรอกรหัสผ่านอีกครั้ง"
                      placeholderTextColor="#6B7280"
                      value={confirmPassword}
                      onChangeText={(t) => { setConfirmPassword(t); setConfirmPasswordError(''); }}
                      secureTextEntry={!showPassword}
                    />
                  </View>
                  {confirmPasswordError ? <Text style={styles.errorLabel}>{confirmPasswordError}</Text> : null}
                </View>

                <View style={styles.buttonRow}>
                  <Pressable style={styles.backBtn} onPress={() => setCurrentStep(1)}>
                    <Ionicons name="arrow-back" size={20} color="#9CA3AF" />
                    <Text style={styles.backBtnText}>ย้อนกลับ</Text>
                  </Pressable>
                  <Pressable style={styles.nextButtonSmall} onPress={handleNextStep}>
                    <LinearGradient colors={['#3B82F6', '#8B5CF6']} style={styles.gradientButtonSmall}>
                      <Text style={styles.buttonText}>ถัดไป</Text>
                    </LinearGradient>
                  </Pressable>
                </View>
              </>
            )}

            {/* Step 3 */}
            {currentStep === 3 && (
              <>
                <Text style={styles.stepTitle}>ยืนยันการสมัคร</Text>

                {/* Summary */}
                <View style={styles.summaryCard}>
                  <Ionicons name="person-circle" size={40} color="#3B82F6" />
                  <View style={styles.summaryInfo}>
                    <Text style={styles.summaryName}>{name}</Text>
                    <Text style={styles.summaryEmail}>{email}</Text>
                  </View>
                  <Pressable onPress={() => setCurrentStep(1)}>
                    <Ionicons name="pencil" size={20} color="#3B82F6" />
                  </Pressable>
                </View>

                <View style={styles.inputGroup}>
                  <Text style={styles.label}>รหัสผู้แนะนำ (ไม่บังคับ)</Text>
                  <View style={styles.inputContainer}>
                    <Ionicons name="gift-outline" size={20} color="#9CA3AF" style={styles.inputIcon} />
                    <TextInput
                      style={styles.input}
                      placeholder="กรอกรหัสถ้ามี"
                      placeholderTextColor="#6B7280"
                      value={referralCode}
                      onChangeText={setReferralCode}
                      autoCapitalize="characters"
                    />
                  </View>
                </View>

                {/* Terms */}
                <Pressable style={styles.termsRow} onPress={() => setAcceptTerms(!acceptTerms)}>
                  <View style={[styles.checkbox, acceptTerms && styles.checkboxActive]}>
                    {acceptTerms && <Ionicons name="checkmark" size={16} color="#FFF" />}
                  </View>
                  <Text style={styles.termsText}>
                    ข้าพเจ้ายอมรับ เงื่อนไขการใช้งาน และ นโยบายความเป็นส่วนตัว
                  </Text>
                </Pressable>

                {/* Error */}
                {error ? (
                  <View style={styles.errorBox}>
                    <Ionicons name="alert-circle" size={20} color="#EF4444" />
                    <Text style={styles.errorBoxText}>{error}</Text>
                  </View>
                ) : null}

                <View style={styles.buttonRow}>
                  <Pressable style={styles.backBtn} onPress={() => setCurrentStep(2)}>
                    <Ionicons name="arrow-back" size={20} color="#9CA3AF" />
                    <Text style={styles.backBtnText}>ย้อนกลับ</Text>
                  </Pressable>
                  <Pressable
                    style={[styles.nextButtonSmall, (!acceptTerms || isLoading) && styles.buttonDisabled]}
                    onPress={handleRegister}
                    disabled={!acceptTerms || isLoading}
                  >
                    <LinearGradient colors={['#10B981', '#059669']} style={styles.gradientButtonSmall}>
                      {isLoading ? (
                        <ActivityIndicator color="#FFF" />
                      ) : (
                        <>
                          <Ionicons name="rocket" size={20} color="#FFF" />
                          <Text style={styles.buttonText}>สมัครสมาชิก</Text>
                        </>
                      )}
                    </LinearGradient>
                  </Pressable>
                </View>
              </>
            )}
          </View>

          {/* Login Link Bottom */}
          <View style={styles.bottomRow}>
            <Text style={styles.bottomText}>มีบัญชีอยู่แล้ว? </Text>
            <Pressable onPress={() => router.push('/login')}>
              <Text style={styles.bottomLink}>เข้าสู่ระบบ</Text>
            </Pressable>
          </View>
          </ScrollView>
        </KeyboardAvoidingView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0F0F23' },
  keyboardView: { flex: 1 },
  scrollView: { flex: 1 },
  scrollContent: { flexGrow: 1, paddingHorizontal: 24, paddingBottom: 40 },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingTop: 56, paddingBottom: 16 },
  backButton: { width: 44, height: 44, borderRadius: 22, backgroundColor: 'rgba(255,255,255,0.1)', alignItems: 'center', justifyContent: 'center' },
  loginLink: { color: 'rgba(255,255,255,0.7)', fontSize: 14 },
  logoContainer: { alignItems: 'center', marginBottom: 24 },
  logoBox: { width: 80, height: 80, borderRadius: 24, alignItems: 'center', justifyContent: 'center', marginBottom: 16 },
  logoEmoji: { fontSize: 40 },
  title: { fontSize: 28, fontWeight: 'bold', color: '#FFF' },
  subtitle: { fontSize: 14, color: '#9CA3AF', marginTop: 8 },
  stepRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', marginBottom: 24 },
  stepItem: { alignItems: 'center' },
  stepCircle: { width: 36, height: 36, borderRadius: 18, borderWidth: 2, borderColor: '#4B5563', backgroundColor: '#1F2937', alignItems: 'center', justifyContent: 'center' },
  stepCircleActive: { backgroundColor: '#3B82F6', borderColor: '#3B82F6' },
  stepCircleCurrent: { borderWidth: 3, borderColor: '#60A5FA' },
  stepNumber: { color: '#6B7280', fontWeight: 'bold' },
  stepNumberActive: { color: '#FFF' },
  stepLabel: { fontSize: 12, color: '#6B7280', marginTop: 4 },
  stepLabelActive: { color: '#3B82F6', fontWeight: '600' },
  stepLine: { width: 30, height: 2, backgroundColor: '#4B5563', marginHorizontal: 8 },
  stepLineActive: { backgroundColor: '#3B82F6' },
  referralBadge: { flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(16,185,129,0.15)', borderRadius: 12, padding: 12, marginBottom: 16 },
  referralText: { color: '#10B981', marginLeft: 8, flex: 1 },
  formCard: { backgroundColor: 'rgba(255,255,255,0.05)', borderRadius: 24, padding: 24, borderWidth: 1, borderColor: 'rgba(255,255,255,0.1)' },
  stepTitle: { fontSize: 18, fontWeight: 'bold', color: '#FFF', marginBottom: 16 },
  inputGroup: { marginBottom: 16 },
  label: { color: '#E5E7EB', fontSize: 14, fontWeight: '600', marginBottom: 8 },
  inputContainer: { flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(255,255,255,0.08)', borderRadius: 14, borderWidth: 1, borderColor: 'rgba(255,255,255,0.1)' },
  inputError: { borderColor: '#EF4444' },
  inputIcon: { marginLeft: 14 },
  input: { flex: 1, paddingVertical: 14, paddingHorizontal: 12, color: '#FFF', fontSize: 16 },
  eyeButton: { padding: 14 },
  errorLabel: { color: '#EF4444', fontSize: 12, marginTop: 4 },
  strengthRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  strengthLabel: { color: '#9CA3AF', fontSize: 12, marginRight: 8 },
  strengthText: { fontSize: 12, fontWeight: '600' },
  nextButton: { marginTop: 8, borderRadius: 14, overflow: 'hidden' },
  gradientButton: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 16, gap: 8 },
  buttonText: { color: '#FFF', fontSize: 16, fontWeight: 'bold' },
  buttonRow: { flexDirection: 'row', gap: 12, marginTop: 16 },
  backBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 14, borderRadius: 14, borderWidth: 1, borderColor: '#4B5563' },
  backBtnText: { color: '#9CA3AF', fontWeight: '600', marginLeft: 8 },
  nextButtonSmall: { flex: 1, borderRadius: 14, overflow: 'hidden' },
  gradientButtonSmall: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 14, gap: 8 },
  buttonDisabled: { opacity: 0.5 },
  summaryCard: { flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(255,255,255,0.08)', borderRadius: 16, padding: 16, marginBottom: 16 },
  summaryInfo: { flex: 1, marginLeft: 12 },
  summaryName: { color: '#FFF', fontWeight: 'bold', fontSize: 16 },
  summaryEmail: { color: '#9CA3AF', fontSize: 14, marginTop: 2 },
  termsRow: { flexDirection: 'row', alignItems: 'flex-start', marginTop: 8, marginBottom: 16 },
  checkbox: { width: 24, height: 24, borderRadius: 6, borderWidth: 2, borderColor: '#4B5563', alignItems: 'center', justifyContent: 'center', marginRight: 12 },
  checkboxActive: { backgroundColor: '#3B82F6', borderColor: '#3B82F6' },
  termsText: { flex: 1, color: '#9CA3AF', fontSize: 14, lineHeight: 20 },
  errorBox: { flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(239,68,68,0.15)', borderRadius: 12, padding: 12, marginBottom: 16 },
  errorBoxText: { color: '#EF4444', marginLeft: 8, flex: 1 },
  bottomRow: { flexDirection: 'row', justifyContent: 'center', marginTop: 24 },
  bottomText: { color: '#9CA3AF' },
  bottomLink: { color: '#3B82F6', fontWeight: 'bold' },
});
