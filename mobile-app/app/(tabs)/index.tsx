/**
 * Home Screen - DEBUG VERSION
 * ⚠️ หน้าแบบ Simple สุดๆ เพื่อทดสอบปัญหาหน้าจอขาว
 * ไม่ใช้: LinearGradient, react-native-reanimated, NativeWind className
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

export default function HomeScreen() {
  const { user, isAuthenticated } = useAuthStore();

  return (
    <View style={styles.container}>
      <StatusBar barStyle="dark-content" />

      <ScrollView style={styles.scrollView} contentContainerStyle={styles.content}>
        {/* Header */}
        <View style={styles.header}>
          <Text style={styles.greeting}>สวัสดี</Text>
          <Text style={styles.userName}>
            {isAuthenticated ? user?.name || 'ผู้ใช้' : 'ยินดีต้อนรับ'}
          </Text>
        </View>

        {/* Status */}
        <View style={styles.statusCard}>
          <Text style={styles.statusLabel}>สถานะ:</Text>
          <Text style={styles.statusValue}>
            {isAuthenticated ? '✅ เข้าสู่ระบบแล้ว' : '❌ ยังไม่ได้เข้าสู่ระบบ'}
          </Text>
        </View>

        {/* Simple Menu */}
        <View style={styles.menuContainer}>
          <Text style={styles.sectionTitle}>เมนูหลัก</Text>

          <Pressable
            style={styles.menuItem}
            onPress={() => router.push('/(tabs)/wallet')}
          >
            <Text style={styles.menuIcon}>💰</Text>
            <Text style={styles.menuText}>กระเป๋าเงิน</Text>
          </Pressable>

          <Pressable
            style={styles.menuItem}
            onPress={() => router.push('/(tabs)/network')}
          >
            <Text style={styles.menuIcon}>👥</Text>
            <Text style={styles.menuText}>สายงาน</Text>
          </Pressable>

          <Pressable
            style={styles.menuItem}
            onPress={() => router.push('/(tabs)/profile')}
          >
            <Text style={styles.menuIcon}>👤</Text>
            <Text style={styles.menuText}>โปรไฟล์</Text>
          </Pressable>

          <Pressable
            style={styles.menuItem}
            onPress={() => router.push('/shopping')}
          >
            <Text style={styles.menuIcon}>🛒</Text>
            <Text style={styles.menuText}>ช้อปปิ้ง</Text>
          </Pressable>
        </View>

        {/* Debug Info */}
        <View style={styles.debugInfo}>
          <Text style={styles.debugTitle}>🔧 DEBUG INFO</Text>
          <Text style={styles.debugText}>
            หน้านี้ใช้แค่ React Native พื้นฐาน{'\n'}
            ไม่มี LinearGradient{'\n'}
            ไม่มี react-native-reanimated{'\n'}
            ไม่มี NativeWind/className{'\n'}
            ไม่มี SafeAreaView{'\n'}
            ไม่มี Ionicons
          </Text>
          <Text style={styles.debugNote}>
            ถ้าหน้านี้ไม่ขาว = ปัญหาอยู่ที่ library เหล่านั้น
          </Text>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  scrollView: {
    flex: 1,
  },
  content: {
    padding: 20,
    paddingTop: 60,
  },
  header: {
    marginBottom: 24,
  },
  greeting: {
    fontSize: 16,
    color: '#6B7280',
  },
  userName: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#1F2937',
    marginTop: 4,
  },
  statusCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
    marginBottom: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  statusLabel: {
    fontSize: 14,
    color: '#6B7280',
  },
  statusValue: {
    fontSize: 18,
    fontWeight: '600',
    color: '#1F2937',
    marginTop: 4,
  },
  menuContainer: {
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 12,
  },
  menuItem: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  menuIcon: {
    fontSize: 24,
    marginRight: 12,
  },
  menuText: {
    fontSize: 16,
    fontWeight: '500',
    color: '#1F2937',
  },
  debugInfo: {
    backgroundColor: '#FEF3C7',
    borderRadius: 12,
    padding: 16,
    borderWidth: 1,
    borderColor: '#F59E0B',
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
  debugNote: {
    fontSize: 14,
    fontWeight: '600',
    color: '#B45309',
    marginTop: 12,
  },
});
