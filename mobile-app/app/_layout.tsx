/**
 * Root Layout - Layout หลักของ app
 * ใช้ Expo Router สำหรับ navigation
 */

import React, { useEffect } from 'react';
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

export default function RootLayout() {
  const { initialize, isInitialized, isLoading } = useAuthStore();
  const { loadSettings, resolvedTheme, setAppReady } = useAppStore();

  // โหลดฟอนต์จาก Google Fonts
  const [fontsLoaded] = useFonts({
    OpenSans: OpenSans_400Regular,
    'OpenSans-Bold': OpenSans_700Bold,
    'OpenSans-SemiBold': OpenSans_600SemiBold,
  });

  // Initialize app
  useEffect(() => {
    const initApp = async () => {
      try {
        // โหลด settings และ auth state
        await Promise.all([loadSettings(), initialize()]);
      } catch (error) {
        console.error('Init error:', error);
      } finally {
        setAppReady(true);
      }
    };

    initApp();
  }, []);

  // ซ่อน splash screen เมื่อโหลดเสร็จ
  useEffect(() => {
    if (fontsLoaded && isInitialized) {
      SplashScreen.hideAsync();
    }
  }, [fontsLoaded, isInitialized]);

  // แสดง loading ถ้ายังไม่พร้อม
  if (!fontsLoaded || !isInitialized) {
    return <LoadingScreen />;
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
