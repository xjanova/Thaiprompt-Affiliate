/**
 * Index Screen - DEBUG VERSION
 * ⚠️ แบบ Simple สุดๆ เพื่อทดสอบปัญหาหน้าจอขาว
 * ไม่ใช้: LavaBackground, LinearGradient, Ionicons, NativeWind className
 */

import React from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  StyleSheet,
  StatusBar,
} from 'react-native';
import { router } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';

export default function IndexScreen() {
  const { user, isAuthenticated, logout } = useAuthStore();

  // ถ้า login แล้ว ไปหน้า tabs
  const goToHome = () => router.replace('/(tabs)');
  const goToLogin = () => router.push('/login');

  const handleLogout = async () => {
    await logout();
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" />

      <ScrollView style={styles.scrollView} contentContainerStyle={styles.content}>
        {/* Header */}
        <View style={styles.header}>
          <Text style={styles.appName}>Thaiprompt</Text>
          <Text style={styles.appSubtitle}>Affiliate</Text>
        </View>

        {/* User Status */}
        <View style={styles.statusCard}>
          {isAuthenticated ? (
            <>
              <Text style={styles.greeting}>สวัสดี 👋</Text>
              <Text style={styles.userName}>{user?.name || 'ผู้ใช้'}</Text>
              <Text style={styles.statusText}>✅ เข้าสู่ระบบแล้ว</Text>
            </>
          ) : (
            <>
              <Text style={styles.greeting}>ยินดีต้อนรับ</Text>
              <Text style={styles.statusText}>กรุณาเข้าสู่ระบบ</Text>
            </>
          )}
        </View>

        {/* Action Buttons */}
        <View style={styles.buttonContainer}>
          {isAuthenticated ? (
            <>
              <Pressable style={styles.primaryButton} onPress={goToHome}>
                <Text style={styles.primaryButtonText}>🏠 เข้าสู่หน้าหลัก</Text>
              </Pressable>

              <Pressable style={styles.secondaryButton} onPress={handleLogout}>
                <Text style={styles.secondaryButtonText}>🚪 ออกจากระบบ</Text>
              </Pressable>
            </>
          ) : (
            <Pressable style={styles.primaryButton} onPress={goToLogin}>
              <Text style={styles.primaryButtonText}>🔑 เข้าสู่ระบบ</Text>
            </Pressable>
          )}
        </View>

        {/* Debug Info */}
        <View style={styles.debugInfo}>
          <Text style={styles.debugTitle}>🔧 DEBUG INFO</Text>
          <Text style={styles.debugText}>
            Version: {APP_INFO.VERSION}{'\n'}
            Build: {APP_INFO.BUILD_DATE}{'\n'}
            {'\n'}
            หน้านี้ใช้แค่ React Native พื้นฐาน{'\n'}
            ไม่มี LavaBackground{'\n'}
            ไม่มี LinearGradient{'\n'}
            ไม่มี Ionicons{'\n'}
            ไม่มี NativeWind
          </Text>
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
  content: {
    padding: 20,
    paddingTop: 60,
  },
  header: {
    alignItems: 'center',
    marginBottom: 40,
  },
  appName: {
    fontSize: 36,
    fontWeight: 'bold',
    color: '#3B82F6',
  },
  appSubtitle: {
    fontSize: 18,
    color: '#9CA3AF',
    marginTop: 4,
  },
  statusCard: {
    backgroundColor: '#1A1A2E',
    borderRadius: 16,
    padding: 24,
    marginBottom: 24,
    alignItems: 'center',
  },
  greeting: {
    fontSize: 16,
    color: '#9CA3AF',
    marginBottom: 4,
  },
  userName: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 8,
  },
  statusText: {
    fontSize: 14,
    color: '#10B981',
  },
  buttonContainer: {
    marginBottom: 24,
  },
  primaryButton: {
    backgroundColor: '#3B82F6',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    marginBottom: 12,
  },
  primaryButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
  secondaryButton: {
    backgroundColor: '#374151',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
  },
  secondaryButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
  debugInfo: {
    backgroundColor: '#FEF3C7',
    borderRadius: 12,
    padding: 16,
    marginBottom: 24,
  },
  debugTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#92400E',
    marginBottom: 8,
  },
  debugText: {
    fontSize: 14,
    color: '#92400E',
    lineHeight: 22,
  },
  footer: {
    alignItems: 'center',
    paddingVertical: 20,
  },
  footerText: {
    fontSize: 12,
    color: '#6B7280',
  },
});
