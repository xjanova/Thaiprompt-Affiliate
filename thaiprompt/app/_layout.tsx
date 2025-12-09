/**
 * Root Layout
 * โหลด Fonts และ Initialize App
 */

import React, { useEffect, useState } from 'react';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { View, Text, StyleSheet, ActivityIndicator } from 'react-native';
import * as SplashScreen from 'expo-splash-screen';
import * as Font from 'expo-font';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';

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
  const { initialize, isInitialized } = useAuthStore();
  const [isReady, setIsReady] = useState(false);
  const [fontsLoaded, setFontsLoaded] = useState(false);

  useEffect(() => {
    let isMounted = true;

    const init = async () => {
      try {
        // โหลด Fonts ก่อน (สำคัญมาก!)
        await Font.loadAsync({
          ...Ionicons.font,
        });

        if (isMounted) {
          setFontsLoaded(true);
        }

        // ซ่อน splash หลังโหลด font เสร็จ
        await SplashScreen.hideAsync().catch(() => {});

        // Initialize auth (timeout 5 วินาที)
        const initPromise = initialize().catch((e) => {
          console.error('Init error:', e);
        });

        // Race กับ timeout
        await Promise.race([
          initPromise,
          new Promise((resolve) => setTimeout(resolve, 5000)),
        ]);
      } catch (error) {
        console.error('Layout init error:', error);
        // ถึงแม้จะ error ก็ต้องโหลด fonts
        if (isMounted && !fontsLoaded) {
          try {
            await Font.loadAsync({
              ...Ionicons.font,
            });
            setFontsLoaded(true);
          } catch (fontError) {
            console.error('Font load error:', fontError);
          }
        }
      } finally {
        if (isMounted) {
          setIsReady(true);
        }
      }
    };

    // Force ready หลังจาก 6 วินาที ไม่ว่าจะเกิดอะไรขึ้น
    const forceTimeout = setTimeout(() => {
      if (isMounted) {
        setFontsLoaded(true);
        setIsReady(true);
      }
    }, 6000);

    init();

    return () => {
      isMounted = false;
      clearTimeout(forceTimeout);
    };
  }, []);

  // แสดง loading ถ้ายังไม่พร้อมหรือ fonts ยังไม่โหลด
  if (!isReady || !fontsLoaded) {
    return <SimpleLoadingScreen />;
  }

  return (
    <View style={{ flex: 1, backgroundColor: '#F9FAFB' }}>
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

        {/* Tarot Screens */}
        <Stack.Screen name="tarot/index" options={{ title: 'ดูดวงไพ่ทาโรต์', headerShown: true }} />
        <Stack.Screen name="tarot/select-cards" options={{ title: 'เลือกไพ่', headerShown: true }} />
        <Stack.Screen name="tarot/reading" options={{ title: 'ผลการดูดวง', headerShown: true }} />
      </Stack>
    </View>
  );
}
