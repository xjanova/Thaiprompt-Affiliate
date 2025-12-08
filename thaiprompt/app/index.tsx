/**
 * Index Screen - Landing Page Premium Design
 * ใช้ StyleSheet + LinearGradient + Ionicons (ไม่ใช้ NativeWind)
 */

import React, { useEffect } from 'react';
import {
  View,
  Text,
  Pressable,
  StyleSheet,
  StatusBar,
  Dimensions,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';

const { width, height } = Dimensions.get('window');

export default function IndexScreen() {
  const { user, isAuthenticated } = useAuthStore();

  // Auto redirect ถ้า login แล้ว
  useEffect(() => {
    if (isAuthenticated) {
      router.replace('/(tabs)');
    }
  }, [isAuthenticated]);

  const goToLogin = () => router.push('/login');
  const goToRegister = () => router.push('/register');
  const goToHome = () => router.replace('/(tabs)');

  // ถ้า login แล้ว แสดง loading ระหว่าง redirect
  if (isAuthenticated) {
    return (
      <View style={styles.container}>
        <LinearGradient
          colors={['#0F0F23', '#1A1A2E', '#16213E']}
          style={StyleSheet.absoluteFillObject}
        />
        <View style={styles.loadingContainer}>
          <Ionicons name="sparkles" size={48} color="#3B82F6" />
          <Text style={styles.loadingText}>กำลังเข้าสู่ระบบ...</Text>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" />

      {/* Background Gradient */}
      <LinearGradient
        colors={['#0F0F23', '#1A1A2E', '#16213E']}
        style={StyleSheet.absoluteFillObject}
      />

      {/* Decorative Elements */}
      <View style={styles.decorCircle1} />
      <View style={styles.decorCircle2} />

      {/* Content */}
      <View style={styles.content}>
        {/* Logo Section */}
        <View style={styles.logoSection}>
          <LinearGradient
            colors={['#3B82F6', '#8B5CF6']}
            style={styles.logoContainer}
          >
            <Ionicons name="diamond" size={48} color="#FFFFFF" />
          </LinearGradient>
          <Text style={styles.appName}>Thaiprompt</Text>
          <Text style={styles.appSubtitle}>Affiliate</Text>
        </View>

        {/* Tagline */}
        <View style={styles.taglineSection}>
          <Text style={styles.tagline}>สร้างรายได้</Text>
          <Text style={styles.taglineHighlight}>ไม่จำกัด</Text>
          <Text style={styles.taglineDescription}>
            ร่วมเป็นส่วนหนึ่งของเครือข่ายพันธมิตรที่เติบโตเร็วที่สุด
          </Text>
        </View>

        {/* Features */}
        <View style={styles.featuresSection}>
          <View style={styles.featureItem}>
            <View style={styles.featureIcon}>
              <Ionicons name="wallet" size={20} color="#10B981" />
            </View>
            <Text style={styles.featureText}>รับค่าคอมมิชชั่นทันที</Text>
          </View>
          <View style={styles.featureItem}>
            <View style={styles.featureIcon}>
              <Ionicons name="people" size={20} color="#8B5CF6" />
            </View>
            <Text style={styles.featureText}>สร้างทีมได้ไม่จำกัด</Text>
          </View>
          <View style={styles.featureItem}>
            <View style={styles.featureIcon}>
              <Ionicons name="shield-checkmark" size={20} color="#3B82F6" />
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
              <Ionicons name="log-in" size={20} color="#FFFFFF" />
              <Text style={styles.primaryButtonText}>เข้าสู่ระบบ</Text>
            </LinearGradient>
          </Pressable>

          <Pressable style={styles.secondaryButton} onPress={goToRegister}>
            <Ionicons name="person-add" size={20} color="#FFFFFF" />
            <Text style={styles.secondaryButtonText}>สมัครสมาชิก</Text>
          </Pressable>
        </View>

        {/* Footer */}
        <View style={styles.footer}>
          <Text style={styles.footerText}>
            v{APP_INFO.VERSION} ({APP_INFO.BUILD_DATE})
          </Text>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  loadingContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loadingText: {
    color: '#FFFFFF',
    fontSize: 16,
    marginTop: 16,
  },
  decorCircle1: {
    position: 'absolute',
    top: -100,
    right: -100,
    width: 300,
    height: 300,
    borderRadius: 150,
    backgroundColor: 'rgba(59, 130, 246, 0.1)',
  },
  decorCircle2: {
    position: 'absolute',
    bottom: -50,
    left: -100,
    width: 250,
    height: 250,
    borderRadius: 125,
    backgroundColor: 'rgba(139, 92, 246, 0.1)',
  },
  content: {
    flex: 1,
    paddingHorizontal: 24,
    paddingTop: height * 0.1,
    paddingBottom: 40,
  },
  logoSection: {
    alignItems: 'center',
    marginBottom: 40,
  },
  logoContainer: {
    width: 100,
    height: 100,
    borderRadius: 30,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 20,
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.4,
    shadowRadius: 20,
    elevation: 15,
  },
  appName: {
    fontSize: 36,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  appSubtitle: {
    fontSize: 18,
    color: '#3B82F6',
    fontWeight: '600',
    letterSpacing: 4,
  },
  taglineSection: {
    alignItems: 'center',
    marginBottom: 40,
  },
  tagline: {
    fontSize: 28,
    color: '#FFFFFF',
    fontWeight: '300',
  },
  taglineHighlight: {
    fontSize: 40,
    fontWeight: 'bold',
    color: '#3B82F6',
    marginBottom: 12,
  },
  taglineDescription: {
    fontSize: 14,
    color: '#9CA3AF',
    textAlign: 'center',
    lineHeight: 22,
  },
  featuresSection: {
    marginBottom: 40,
    gap: 16,
  },
  featureItem: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  featureIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: 'rgba(255,255,255,0.1)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 16,
  },
  featureText: {
    fontSize: 16,
    color: '#FFFFFF',
    fontWeight: '500',
  },
  buttonSection: {
    gap: 12,
    marginTop: 'auto',
  },
  primaryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 18,
    borderRadius: 16,
    gap: 10,
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.4,
    shadowRadius: 16,
    elevation: 10,
  },
  primaryButtonText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  secondaryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 18,
    borderRadius: 16,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.2)',
    gap: 10,
  },
  secondaryButtonText: {
    fontSize: 18,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  footer: {
    alignItems: 'center',
    marginTop: 24,
  },
  footerText: {
    fontSize: 12,
    color: '#6B7280',
  },
});
