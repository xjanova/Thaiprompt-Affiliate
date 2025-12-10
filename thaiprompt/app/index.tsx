/**
 * Index Screen - Landing Page Stable Version
 * แก้ไข: crash on resume
 * ปิด AnimatedBackground ชั่วคราวเพื่อทดสอบ crash
 */

import React from 'react';
import {
  View,
  Text,
  Pressable,
  StyleSheet,
  StatusBar,
  Dimensions,
  ScrollView,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, Redirect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';

const { height } = Dimensions.get('window');

export default function IndexScreen() {
  const { isAuthenticated, isInitialized } = useAuthStore();

  // ถ้า login แล้ว redirect ไป tabs ทันที
  if (isAuthenticated && isInitialized) {
    return <Redirect href="/(tabs)" />;
  }

  const goToLogin = () => router.push('/login');
  const goToRegister = () => router.push('/register');

  return (
    <View style={styles.container}>
      {/* Gradient Background แทน AnimatedBackground ชั่วคราว */}
      <LinearGradient
        colors={['#0F0F23', '#1a1a2e', '#16213e']}
        style={StyleSheet.absoluteFill}
      />
      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />

        <ScrollView
          style={styles.scrollView}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
        {/* Logo Section */}
        <View style={styles.logoSection}>
          <View style={styles.logoBox}>
            <LinearGradient
              colors={['#3B82F6', '#8B5CF6']}
              style={styles.logoGradient}
            >
              <Text style={{ fontSize: 44 }}>💎</Text>
            </LinearGradient>
          </View>
          <Text style={styles.appName}>Thaiprompt</Text>
          <Text style={styles.appSubtitle}>AFFILIATE</Text>
        </View>

        {/* Tagline */}
        <View style={styles.taglineSection}>
          <Text style={styles.tagline}>สร้างรายได้</Text>
          <Text style={styles.taglineHighlight}>ไม่จำกัด</Text>
          <Text style={styles.taglineDesc}>
            ร่วมเป็นส่วนหนึ่งของเครือข่ายพันธมิตร{'\n'}ที่เติบโตเร็วที่สุด
          </Text>
        </View>

        {/* Features */}
        <View style={styles.featuresSection}>
          <View style={styles.featureRow}>
            <View style={styles.featureIcon}>
              <Text style={{ fontSize: 20 }}>💰</Text>
            </View>
            <Text style={styles.featureText}>รับค่าคอมมิชชั่นทันที</Text>
          </View>
          <View style={styles.featureRow}>
            <View style={styles.featureIcon}>
              <Text style={{ fontSize: 20 }}>👥</Text>
            </View>
            <Text style={styles.featureText}>สร้างทีมได้ไม่จำกัด</Text>
          </View>
          <View style={styles.featureRow}>
            <View style={styles.featureIcon}>
              <Text style={{ fontSize: 20 }}>🛡️</Text>
            </View>
            <Text style={styles.featureText}>ปลอดภัย มั่นคง</Text>
          </View>
        </View>

        {/* Buttons */}
        <View style={styles.buttonSection}>
          <Pressable onPress={goToLogin}>
            <LinearGradient
              colors={['#3B82F6', '#2563EB']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.primaryButton}
            >
              <Text style={{ fontSize: 20 }}>🔓</Text>
              <Text style={styles.primaryButtonText}>เข้าสู่ระบบ</Text>
            </LinearGradient>
          </Pressable>

          <Pressable style={styles.secondaryButton} onPress={goToRegister}>
            <Text style={{ fontSize: 20 }}>➕</Text>
            <Text style={styles.secondaryButtonText}>สมัครสมาชิก</Text>
          </Pressable>
        </View>

        {/* Footer */}
        <View style={styles.footer}>
          <Text style={styles.footerText}>
            v{APP_INFO.VERSION} ({APP_INFO.BUILD_DATE})
          </Text>
        </View>
        </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 24,
    paddingTop: height * 0.08,
    paddingBottom: 40,
    minHeight: height,
  },
  logoSection: {
    alignItems: 'center',
    marginBottom: 36,
  },
  logoBox: {
    marginBottom: 16,
  },
  logoGradient: {
    width: 90,
    height: 90,
    borderRadius: 26,
    alignItems: 'center',
    justifyContent: 'center',
  },
  appName: {
    fontSize: 34,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  appSubtitle: {
    fontSize: 16,
    color: '#3B82F6',
    fontWeight: '600',
    letterSpacing: 6,
    marginTop: 2,
  },
  taglineSection: {
    alignItems: 'center',
    marginBottom: 32,
  },
  tagline: {
    fontSize: 26,
    color: '#FFFFFF',
    fontWeight: '300',
  },
  taglineHighlight: {
    fontSize: 38,
    fontWeight: 'bold',
    color: '#3B82F6',
    marginBottom: 10,
  },
  taglineDesc: {
    fontSize: 14,
    color: '#9CA3AF',
    textAlign: 'center',
    lineHeight: 22,
  },
  featuresSection: {
    marginBottom: 36,
  },
  featureRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    paddingHorizontal: 18,
    paddingVertical: 14,
    borderRadius: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  featureIcon: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: 'rgba(255,255,255,0.1)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  featureText: {
    fontSize: 15,
    color: '#FFFFFF',
    fontWeight: '500',
  },
  buttonSection: {
    marginTop: 'auto',
  },
  primaryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 16,
    borderRadius: 14,
    marginBottom: 12,
  },
  primaryButtonText: {
    fontSize: 17,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginLeft: 8,
  },
  secondaryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 16,
    borderRadius: 14,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.15)',
  },
  secondaryButtonText: {
    fontSize: 17,
    fontWeight: '600',
    color: '#FFFFFF',
    marginLeft: 8,
  },
  footer: {
    alignItems: 'center',
    marginTop: 20,
  },
  footerText: {
    fontSize: 11,
    color: '#6B7280',
  },
});
