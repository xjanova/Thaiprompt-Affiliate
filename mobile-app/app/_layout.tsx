/**
 * Root Layout - Layout หลักของ app
 * ใช้ Expo Router สำหรับ navigation
 * รองรับ Offline Mode - เข้า app ได้แม้ไม่มีเน็ต
 */

import React, { useEffect, useState } from 'react';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import * as SplashScreen from 'expo-splash-screen';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { LoadingScreen } from '@/components';
import {
  useFonts,
  OpenSans_400Regular,
  OpenSans_600SemiBold,
  OpenSans_700Bold,
} from '@expo-google-fonts/open-sans';

// ไม่ให้ซ่อน splash screen อัตโนมัติ
SplashScreen.preventAutoHideAsync();

// Maximum time to wait for initialization (15 seconds)
const MAX_INIT_WAIT = 15000;

export default function RootLayout() {
  const { initialize, isInitialized } = useAuthStore();
  const { loadSettings, resolvedTheme, setAppReady } = useAppStore();

  // State สำหรับ force proceed ถ้า timeout
  const [forceReady, setForceReady] = useState(false);
  const [loadingMessage, setLoadingMessage] = useState('กำลังโหลด...');

  // โหลดฟอนต์จาก Google Fonts
  const [fontsLoaded] = useFonts({
    OpenSans: OpenSans_400Regular,
    'OpenSans-Bold': OpenSans_700Bold,
    'OpenSans-SemiBold': OpenSans_600SemiBold,
  });

  // Initialize app with timeout
  useEffect(() => {
    let timeoutId: NodeJS.Timeout;
    let messageTimeoutId: NodeJS.Timeout;

    const initApp = async () => {
      try {
        // Update loading message after 3 seconds
        messageTimeoutId = setTimeout(() => {
          setLoadingMessage('กำลังเชื่อมต่อเซิร์ฟเวอร์...');
        }, 3000);

        // โหลด settings และ auth state
        await Promise.all([loadSettings(), initialize()]);
      } catch (error) {
        console.error('Init error:', error);
      } finally {
        setAppReady(true);
        clearTimeout(messageTimeoutId);
      }
    };

    // Set timeout เพื่อ force proceed ถ้า init นานเกินไป
    timeoutId = setTimeout(() => {
      console.log('Init timeout, forcing app to proceed');
      setLoadingMessage('กำลังเข้าสู่โหมดออฟไลน์...');
      setTimeout(() => {
        setForceReady(true);
        setAppReady(true);
      }, 1000);
    }, MAX_INIT_WAIT);

    initApp().finally(() => {
      clearTimeout(timeoutId);
    });

    return () => {
      clearTimeout(timeoutId);
      clearTimeout(messageTimeoutId);
    };
  }, []);

  // ซ่อน splash screen เมื่อโหลดเสร็จ
  useEffect(() => {
    const isReady = fontsLoaded && (isInitialized || forceReady);
    if (isReady) {
      SplashScreen.hideAsync();
    }
  }, [fontsLoaded, isInitialized, forceReady]);

  // แสดง loading ถ้ายังไม่พร้อม
  const isReady = fontsLoaded && (isInitialized || forceReady);
  if (!isReady) {
    return <LoadingScreen message={loadingMessage} />;
  }

  const isDark = resolvedTheme === 'dark';

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <SafeAreaProvider>
        <StatusBar style={isDark ? 'light' : 'dark'} />
        <Stack
          screenOptions={{
            headerShown: false,
            animation: 'slide_from_right',
            contentStyle: {
              backgroundColor: isDark ? '#0F172A' : '#F9FAFB',
            },
          }}
        >
          {/* Main Tab Navigator */}
          <Stack.Screen
            name="(tabs)"
            options={{
              headerShown: false,
            }}
          />

          {/* Auth Screens */}
          <Stack.Screen
            name="index"
            options={{
              headerShown: false,
            }}
          />
          <Stack.Screen
            name="login"
            options={{
              headerShown: false,
              animation: 'fade',
            }}
          />
          <Stack.Screen
            name="register"
            options={{
              headerShown: false,
              animation: 'slide_from_right',
            }}
          />

          {/* Feature Screens */}
          <Stack.Screen
            name="dashboard"
            options={{
              headerShown: false,
            }}
          />
          <Stack.Screen
            name="shopping"
            options={{
              headerShown: true,
              title: 'ช้อปปิ้ง',
              headerStyle: {
                backgroundColor: isDark ? '#0F172A' : '#FFFFFF',
              },
              headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
            }}
          />
          <Stack.Screen
            name="commissions"
            options={{
              headerShown: true,
              title: 'คอมมิชชั่น',
              headerStyle: {
                backgroundColor: isDark ? '#0F172A' : '#FFFFFF',
              },
              headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
            }}
          />
          <Stack.Screen
            name="referral"
            options={{
              headerShown: true,
              title: 'แนะนำเพื่อน',
              headerStyle: {
                backgroundColor: isDark ? '#0F172A' : '#FFFFFF',
              },
              headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
            }}
          />

          {/* Product Detail Screen */}
          <Stack.Screen
            name="product/[id]"
            options={{
              presentation: 'card',
              animation: 'slide_from_right',
            }}
          />
        </Stack>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
