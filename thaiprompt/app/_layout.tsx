/**
 * Root Layout
 * โหลด Fonts และ Initialize App
 */

import React, { useEffect, useState, useCallback, useRef } from 'react';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { View, Text, StyleSheet, ActivityIndicator, AppState, AppStateStatus } from 'react-native';
import * as SplashScreen from 'expo-splash-screen';
import * as Font from 'expo-font';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';
import { initDeviceTracking, sendHeartbeat } from '@/services/deviceService';

// ไม่ให้ซ่อน splash screen อัตโนมัติ
SplashScreen.preventAutoHideAsync().catch(() => {});

// Simple Loading Screen
const SimpleLoadingScreen = () => (
  <View style={loadingStyles.container}>
    <ActivityIndicator size="large" color="#3B82F6" />
    <Text style={loadingStyles.text}>กำลังโหลด...</Text>
    <Text style={loadingStyles.version}>v{APP_INFO.VERSION} ({APP_INFO.BUILD_DATE})</Text>
  </View>
);

const loadingStyles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
    alignItems: 'center',
    justifyContent: 'center',
  },
  text: {
    color: '#FFFFFF',
    fontSize: 16,
    marginTop: 16,
  },
  version: {
    color: '#6B7280',
    fontSize: 12,
    marginTop: 8,
  },
});

export default function RootLayout() {
  const { initialize } = useAuthStore();
  const [appIsReady, setAppIsReady] = useState(false);
  const [fontsLoaded, setFontsLoaded] = useState(false);
  const appState = useRef(AppState.currentState);

  useEffect(() => {
    let isMounted = true;

    const prepareApp = async () => {
      try {
        // โหลด Ionicons font แบบ manual (เสถียรกว่า useFonts hook)
        await Font.loadAsync({
          ...Ionicons.font,
        });

        if (isMounted) {
          setFontsLoaded(true);
        }

        // Initialize auth พร้อม timeout 5 วินาที
        await Promise.race([
          initialize().catch((e) => console.error('Init error:', e)),
          new Promise((resolve) => setTimeout(resolve, 5000)),
        ]);

        // ลงทะเบียนเครื่องกับ Admin Dashboard (non-blocking)
        initDeviceTracking().catch((e) => console.log('Device tracking:', e));
      } catch (error) {
        console.error('App prepare error:', error);
        // ถ้า font error ก็ยังแสดงแอพได้
        if (isMounted) {
          setFontsLoaded(true);
        }
      } finally {
        if (isMounted) {
          setAppIsReady(true);
        }
      }
    };

    prepareApp();

    // Force ready หลัง 4 วินาที
    const forceTimeout = setTimeout(() => {
      if (isMounted) {
        setFontsLoaded(true);
        setAppIsReady(true);
      }
    }, 4000);

    return () => {
      isMounted = false;
      clearTimeout(forceTimeout);
    };
  }, []);

  // ส่ง heartbeat เมื่อแอพกลับมา foreground (สำหรับ Admin Analytics)
  useEffect(() => {
    const subscription = AppState.addEventListener('change', (nextAppState: AppStateStatus) => {
      if (appState.current.match(/inactive|background/) && nextAppState === 'active') {
        // แอพกลับมา foreground - ส่ง heartbeat
        sendHeartbeat().catch(() => {});
      }
      appState.current = nextAppState;
    });

    return () => {
      subscription.remove();
    };
  }, []);

  // ซ่อน splash เมื่อ fonts โหลดเสร็จ และ app พร้อม
  const onLayoutRootView = useCallback(async () => {
    if (fontsLoaded && appIsReady) {
      await SplashScreen.hideAsync().catch(() => {});
    }
  }, [fontsLoaded, appIsReady]);

  // รอ fonts โหลด และ app พร้อม
  if (!fontsLoaded || !appIsReady) {
    return <SimpleLoadingScreen />;
  }

  return (
    <View style={{ flex: 1, backgroundColor: '#F9FAFB' }} onLayout={onLayoutRootView}>
      <StatusBar style="dark" />
      <Stack
        screenOptions={{
          headerShown: false,
          animation: 'slide_from_right',
          contentStyle: {
            backgroundColor: '#F9FAFB',
          },
        }}
      >
        {/* Main Tab Navigator */}
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />

        {/* Auth Screens */}
        <Stack.Screen name="index" options={{ headerShown: false }} />
        <Stack.Screen name="login" options={{ headerShown: false }} />
        <Stack.Screen name="register" options={{ headerShown: false }} />

        {/* Feature Screens */}
        <Stack.Screen name="main-menu" options={{ headerShown: false }} />
        <Stack.Screen name="dashboard" options={{ headerShown: false }} />
        <Stack.Screen name="shopping" options={{ title: 'ช้อปปิ้ง', headerShown: true }} />
        <Stack.Screen name="commissions" options={{ title: 'คอมมิชชั่น', headerShown: true }} />
        <Stack.Screen name="referral" options={{ title: 'แนะนำเพื่อน', headerShown: true }} />
        <Stack.Screen name="notifications" options={{ title: 'การแจ้งเตือน', headerShown: true }} />
        <Stack.Screen name="support" options={{ title: 'ช่วยเหลือ', headerShown: true }} />
        <Stack.Screen name="kyc" options={{ title: 'ยืนยันตัวตน', headerShown: true }} />
        <Stack.Screen name="leaderboard" options={{ title: 'อันดับ', headerShown: true }} />
        <Stack.Screen name="rank" options={{ title: 'ระดับ', headerShown: true }} />
        <Stack.Screen name="rider" options={{ title: 'ไรเดอร์', headerShown: true }} />
        <Stack.Screen name="wiki" options={{ title: 'คู่มือการใช้งาน', headerShown: true }} />
        <Stack.Screen name="settings" options={{ title: 'ตั้งค่า', headerShown: true }} />
        <Stack.Screen name="coming-soon" options={{ title: 'เร็วๆ นี้', headerShown: true }} />
        <Stack.Screen name="product/[id]" options={{ headerShown: false }} />

        {/* Wallet Screens */}
        <Stack.Screen name="wallet-topup" options={{ headerShown: false }} />
        <Stack.Screen name="wallet-withdraw" options={{ headerShown: false }} />
        <Stack.Screen name="wallet-transfer" options={{ headerShown: false }} />

        {/* Tarot Screens */}
        <Stack.Screen name="tarot/index" options={{ title: 'ดูดวงไพ่ทาโรต์', headerShown: true }} />
        <Stack.Screen name="tarot/select-cards" options={{ title: 'เลือกไพ่', headerShown: true }} />
        <Stack.Screen name="tarot/reading" options={{ title: 'ผลการดูดวง', headerShown: true }} />

        {/* MLM Tree */}
        <Stack.Screen name="mlm-tree" options={{ headerShown: false }} />

        {/* Wealth Guide */}
        <Stack.Screen name="wealth-guide" options={{ headerShown: false }} />
      </Stack>
    </View>
  );
}
