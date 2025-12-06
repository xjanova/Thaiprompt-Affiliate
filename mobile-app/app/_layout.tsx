/**
 * Root Layout - Layout หลักของ app
 * ใช้ Expo Router สำหรับ navigation
 * รองรับ Offline Mode - เข้า app ได้แม้ไม่มีเน็ต
 *
 * Safety Features:
 * - Font timeout: 5 วินาที (ถ้าโหลดไม่ได้จะข้ามไป)
 * - Auth timeout: 10 วินาที (ถ้า init ไม่เสร็จจะข้ามไป)
 * - Force ready: 10 วินาที (ข้ามทุกอย่างถ้ายังไม่พร้อม)
 * - Splash hide: 3 วินาที (ซ่อน native splash อย่างไรก็ตาม)
 */

import React, { useEffect, useState, useRef } from 'react';
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
SplashScreen.preventAutoHideAsync().catch(() => {
  // Splash screen อาจถูกซ่อนไปแล้ว - ไม่ต้อง handle
});

// Timeouts (ลดเวลาลงเพื่อให้ผู้ใช้ไม่ต้องรอนาน)
const MAX_INIT_WAIT = 10000; // 10 วินาที (ลดจาก 15)
const MAX_FONT_WAIT = 5000; // 5 วินาที
const FORCE_SPLASH_HIDE = 3000; // 3 วินาที - ซ่อน splash อย่างไรก็ตาม

export default function RootLayout() {
  const { initialize, isInitialized } = useAuthStore();
  const { loadSettings, resolvedTheme, setAppReady } = useAppStore();

  // State สำหรับ force proceed ถ้า timeout
  const [forceReady, setForceReady] = useState(false);
  const [loadingMessage, setLoadingMessage] = useState('กำลังโหลด...');
  const [splashHidden, setSplashHidden] = useState(false);
  // State สำหรับ force skip fonts ถ้า timeout
  const [fontTimeout, setFontTimeout] = useState(false);
  // Track if component is mounted
  const isMounted = useRef(true);

  // โหลดฟอนต์จาก Google Fonts
  const [fontsLoaded, fontError] = useFonts({
    OpenSans: OpenSans_400Regular,
    'OpenSans-Bold': OpenSans_700Bold,
    'OpenSans-SemiBold': OpenSans_600SemiBold,
  });

  // ถ้าโหลด font error ให้ข้ามไปเลย
  useEffect(() => {
    if (fontError) {
      console.log('Font loading error, proceeding without custom fonts');
      setFontTimeout(true);
    }
  }, [fontError]);

  // Font timeout - ถ้าโหลดไม่ได้ใน 5 วินาที ให้ข้ามไป
  useEffect(() => {
    if (fontsLoaded || fontTimeout) return;

    const fontTimeoutId = setTimeout(() => {
      if (isMounted.current) {
        console.log('Font loading timeout, proceeding without custom fonts');
        setFontTimeout(true);
      }
    }, MAX_FONT_WAIT);

    return () => clearTimeout(fontTimeoutId);
  }, [fontsLoaded, fontTimeout]);

  // Force hide splash screen หลังจาก 3 วินาที ไม่ว่าจะเกิดอะไรขึ้น
  useEffect(() => {
    const forceSplashHideId = setTimeout(() => {
      if (isMounted.current && !splashHidden) {
        console.log('Force hiding splash screen');
        SplashScreen.hideAsync().catch(() => {}).finally(() => {
          if (isMounted.current) {
            setSplashHidden(true);
          }
        });
      }
    }, FORCE_SPLASH_HIDE);

    return () => clearTimeout(forceSplashHideId);
  }, [splashHidden]);

  // ซ่อน native splash screen ทันทีที่ fonts โหลดเสร็จหรือ timeout
  useEffect(() => {
    if ((fontsLoaded || fontTimeout) && !splashHidden) {
      SplashScreen.hideAsync().catch(() => {}).finally(() => {
        if (isMounted.current) {
          setSplashHidden(true);
        }
      });
    }
  }, [fontsLoaded, fontTimeout, splashHidden]);

  // Initialize app with timeout
  useEffect(() => {
    let timeoutId: NodeJS.Timeout;
    let messageTimeoutId: NodeJS.Timeout;

    const initApp = async () => {
      try {
        // Update loading message after 2 seconds
        messageTimeoutId = setTimeout(() => {
          if (isMounted.current) {
            setLoadingMessage('กำลังเชื่อมต่อเซิร์ฟเวอร์...');
          }
        }, 2000);

        // โหลด settings และ auth state พร้อมกัน แต่ไม่ให้ค้าง
        const initPromise = Promise.all([
          loadSettings().catch((e) => console.error('loadSettings error:', e)),
          initialize().catch((e) => console.error('initialize error:', e)),
        ]);

        // Race กับ timeout 8 วินาที (ให้ init มี buffer 2 วินาทีก่อน force timeout)
        await Promise.race([
          initPromise,
          new Promise((resolve) => setTimeout(resolve, 8000)),
        ]);
      } catch (error) {
        console.error('Init error:', error);
      } finally {
        if (isMounted.current) {
          setAppReady(true);
          clearTimeout(messageTimeoutId);
        }
      }
    };

    // Set timeout เพื่อ force proceed ถ้า init นานเกินไป
    timeoutId = setTimeout(() => {
      if (isMounted.current) {
        console.log('Init timeout, forcing app to proceed');
        setLoadingMessage('กำลังเข้าสู่โหมดออฟไลน์...');
        setTimeout(() => {
          if (isMounted.current) {
            setForceReady(true);
            setAppReady(true);
          }
        }, 500); // ลดจาก 1000 เป็น 500
      }
    }, MAX_INIT_WAIT);

    initApp().finally(() => {
      clearTimeout(timeoutId);
    });

    return () => {
      isMounted.current = false;
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
