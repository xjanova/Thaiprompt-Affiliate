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
// Maximum time to wait for fonts (5 seconds)
const MAX_FONT_WAIT = 5000;

export default function RootLayout() {
  const { initialize, isInitialized } = useAuthStore();
  const { loadSettings, resolvedTheme, setAppReady } = useAppStore();

  // State สำหรับ force proceed ถ้า timeout
  const [forceReady, setForceReady] = useState(false);
  const [loadingMessage, setLoadingMessage] = useState('กำลังโหลด...');
  const [splashHidden, setSplashHidden] = useState(false);
  // State สำหรับ force skip fonts ถ้า timeout
  const [fontTimeout, setFontTimeout] = useState(false);

  // โหลดฟอนต์จาก Google Fonts
  const [fontsLoaded] = useFonts({
    OpenSans: OpenSans_400Regular,
    'OpenSans-Bold': OpenSans_700Bold,
    'OpenSans-SemiBold': OpenSans_600SemiBold,
  });

  // Font timeout - ถ้าโหลดไม่ได้ใน 5 วินาที ให้ข้ามไป
  useEffect(() => {
    if (fontsLoaded) return;

    const fontTimeoutId = setTimeout(() => {
      console.log('Font loading timeout, proceeding without custom fonts');
      setFontTimeout(true);
    }, MAX_FONT_WAIT);

    return () => clearTimeout(fontTimeoutId);
  }, [fontsLoaded]);

  // ซ่อน native splash screen ทันทีที่ fonts โหลดเสร็จหรือ timeout
  // เพื่อให้ผู้ใช้เห็น LoadingScreen พร้อม animation
  useEffect(() => {
    if ((fontsLoaded || fontTimeout) && !splashHidden) {
      SplashScreen.hideAsync().then(() => {
        setSplashHidden(true);
      });
    }
  }, [fontsLoaded, fontTimeout, splashHidden]);

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

  // แสดง loading ถ้ายังไม่พร้อม
  // ตอนนี้ native splash หายไปแล้ว ผู้ใช้จะเห็น LoadingScreen
  // forceReady สามารถข้ามทั้ง fonts และ auth initialization ได้
  const fontsReady = fontsLoaded || fontTimeout;
  const isReady = (fontsReady && (isInitialized || forceReady)) || forceReady;
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

          {/* Main Menu - หน้าแรกหลัง Splash (Premium) */}
          <Stack.Screen
            name="index"
            options={{
              headerShown: false,
              animation: 'fade',
            }}
          />

          {/* Hub Selection */}
          <Stack.Screen
            name="hub-selection"
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

          {/* Coming Soon Screen */}
          <Stack.Screen
            name="coming-soon"
            options={{
              headerShown: true,
              title: 'เร็วๆ นี้',
              headerStyle: {
                backgroundColor: isDark ? '#0F172A' : '#FFFFFF',
              },
              headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
              animation: 'fade',
            }}
          />

          {/* Notifications Screen */}
          <Stack.Screen
            name="notifications"
            options={{
              headerShown: true,
              title: 'การแจ้งเตือน',
              headerStyle: {
                backgroundColor: isDark ? '#0F172A' : '#FFFFFF',
              },
              headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
            }}
          />

          {/* Support Screen */}
          <Stack.Screen
            name="support"
            options={{
              headerShown: true,
              title: 'ช่วยเหลือ',
              headerStyle: {
                backgroundColor: isDark ? '#0F172A' : '#FFFFFF',
              },
              headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
            }}
          />

          {/* KYC Screen */}
          <Stack.Screen
            name="kyc"
            options={{
              headerShown: true,
              title: 'ยืนยันตัวตน',
              headerStyle: {
                backgroundColor: isDark ? '#0F172A' : '#FFFFFF',
              },
              headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
            }}
          />

          {/* Leaderboard Screen */}
          <Stack.Screen
            name="leaderboard"
            options={{
              headerShown: true,
              title: 'อันดับ',
              headerStyle: {
                backgroundColor: isDark ? '#0F172A' : '#FFFFFF',
              },
              headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
            }}
          />

          {/* Rank Screen */}
          <Stack.Screen
            name="rank"
            options={{
              headerShown: true,
              title: 'ระดับ',
              headerStyle: {
                backgroundColor: isDark ? '#0F172A' : '#FFFFFF',
              },
              headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
            }}
          />

          {/* Rider Screen */}
          <Stack.Screen
            name="rider"
            options={{
              headerShown: true,
              title: 'ไรเดอร์',
              headerStyle: {
                backgroundColor: isDark ? '#0F172A' : '#FFFFFF',
              },
              headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
            }}
          />

          {/* Wiki Screen */}
          <Stack.Screen
            name="wiki"
            options={{
              headerShown: true,
              title: 'คู่มือการใช้งาน',
              headerStyle: {
                backgroundColor: isDark ? '#0F172A' : '#FFFFFF',
              },
              headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
            }}
          />

          {/* Settings Screen */}
          <Stack.Screen
            name="settings"
            options={{
              headerShown: true,
              title: 'ตั้งค่า',
              headerStyle: {
                backgroundColor: isDark ? '#0F172A' : '#FFFFFF',
              },
              headerTintColor: isDark ? '#FFFFFF' : '#1F2937',
            }}
          />
        </Stack>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
