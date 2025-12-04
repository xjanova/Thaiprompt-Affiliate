/**
 * Root Layout - Layout หลักของ app
 * ใช้ Expo Router สำหรับ navigation
 */

import React, { useEffect } from 'react';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useFonts } from 'expo-font';
import * as SplashScreen from 'expo-splash-screen';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { LoadingScreen } from '@/components';
import '../global.css';

// ไม่ให้ซ่อน splash screen อัตโนมัติ
SplashScreen.preventAutoHideAsync();

export default function RootLayout() {
  const { initialize, isInitialized, isLoading } = useAuthStore();
  const { loadSettings, resolvedTheme, setAppReady } = useAppStore();

  // โหลดฟอนต์
  const [fontsLoaded] = useFonts({
    OpenSans: require('../assets/fonts/OpenSans-Regular.ttf'),
    'OpenSans-Bold': require('../assets/fonts/OpenSans-Bold.ttf'),
    'OpenSans-SemiBold': require('../assets/fonts/OpenSans-SemiBold.ttf'),
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

  return (
    <>
      <StatusBar style={resolvedTheme === 'dark' ? 'light' : 'dark'} />
      <Stack
        screenOptions={{
          headerShown: false,
          animation: 'slide_from_right',
          contentStyle: {
            backgroundColor: resolvedTheme === 'dark' ? '#0F0F23' : '#FFFFFF',
          },
        }}
      >
        <Stack.Screen name="index" />
        <Stack.Screen name="login" />
        <Stack.Screen name="dashboard" />
        <Stack.Screen
          name="hub/[id]"
          options={{
            presentation: 'modal',
          }}
        />
      </Stack>
    </>
  );
}
