/**
 * Root Layout
 * โหลด Fonts และ Initialize App
 */

import React, { useEffect, useState, useCallback, useRef } from 'react';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { View, Text, StyleSheet, ActivityIndicator, AppState, AppStateStatus, Image } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import * as SplashScreen from 'expo-splash-screen';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { APP_INFO } from '@/config/appConfig';
import { initDeviceTracking, sendHeartbeat } from '@/services/deviceService';
import {
  registerForPushNotifications,
  addNotificationReceivedListener,
  addNotificationResponseListener,
  getNotificationData,
  setupNotificationHandler,
} from '@/services/notifications';
import { startGpsSharing } from '@/services/location';
import { router } from 'expo-router';

// ไม่ให้ซ่อน splash screen อัตโนมัติ
SplashScreen.preventAutoHideAsync().catch(() => {});

// Premium Loading Screen with Logo
const LoadingScreen = () => (
  <View style={loadingStyles.container}>
    <LinearGradient
      colors={['#0F0F23', '#1a1a2e', '#16213e']}
      style={StyleSheet.absoluteFill}
    />

    {/* Logo */}
    <View style={loadingStyles.logoContainer}>
      <Image
        source={require('@/assets/images/icon.png')}
        style={loadingStyles.logo}
        resizeMode="contain"
      />

      {/* Glow effect */}
      <View style={loadingStyles.logoGlow} />
    </View>

    {/* App Name */}
    <Text style={loadingStyles.appName}>{APP_INFO.NAME}</Text>
    <Text style={loadingStyles.tagline}>Affiliate Marketing Platform</Text>

    {/* Loading Indicator */}
    <View style={loadingStyles.loadingBox}>
      <ActivityIndicator size="large" color="#3B82F6" />
      <Text style={loadingStyles.loadingText}>กำลังโหลด...</Text>
    </View>

    {/* Version */}
    <Text style={loadingStyles.version}>v{APP_INFO.VERSION}</Text>
  </View>
);

const loadingStyles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoContainer: {
    position: 'relative',
    marginBottom: 24,
  },
  logo: {
    width: 120,
    height: 120,
    borderRadius: 30,
  },
  logoGlow: {
    position: 'absolute',
    top: -10,
    left: -10,
    right: -10,
    bottom: -10,
    borderRadius: 40,
    backgroundColor: '#3B82F6',
    opacity: 0.2,
  },
  appName: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  tagline: {
    fontSize: 14,
    color: '#9CA3AF',
    marginBottom: 40,
  },
  loadingBox: {
    alignItems: 'center',
  },
  loadingText: {
    color: '#9CA3AF',
    fontSize: 14,
    marginTop: 12,
  },
  version: {
    position: 'absolute',
    bottom: 40,
    color: '#6B7280',
    fontSize: 12,
  },
});

export default function RootLayout() {
  const { initialize } = useAuthStore();
  const { loadSettings, gpsSharing } = useAppStore();
  const [appIsReady, setAppIsReady] = useState(false);
  const appState = useRef(AppState.currentState);

  useEffect(() => {
    let isMounted = true;

    const prepareApp = async () => {
      try {
        console.log('✅ App initialization started (no icon fonts needed - using emojis)');

        // Initialize auth พร้อม timeout 5 วินาที
        await Promise.race([
          initialize().catch((e) => console.error('Init error:', e)),
          new Promise((resolve) => setTimeout(resolve, 5000)),
        ]);

        // ⭐ โหลด app settings (theme, language, gpsSharing)
        await loadSettings().catch((e) => console.log('Load settings error:', e));

        // ลงทะเบียนเครื่องกับ Admin Dashboard (non-blocking)
        initDeviceTracking().catch((e) => console.log('Device tracking:', e));

        // ⭐ ลงทะเบียน Push Notification (non-blocking)
        registerForPushNotifications()
          .then((token) => {
            if (token) {
              console.log('✅ Push notification registered:', token.substring(0, 30) + '...');
            }
          })
          .catch((e) => console.log('Push notification error:', e));
      } catch (error) {
        console.error('App prepare error:', error);
      } finally {
        if (isMounted) {
          setAppIsReady(true);
        }
      }
    };

    prepareApp();

    // Force ready หลัง 3 วินาที (ลดเวลาลงเพราะไม่ต้องโหลด fonts แล้ว)
    const forceTimeout = setTimeout(() => {
      if (isMounted) {
        console.log('⏱️ Force timeout - showing app');
        setAppIsReady(true);
      }
    }, 3000);

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

  // ⭐ ฟังการรับ Push Notification (เฉพาะเมื่อ app พร้อมแล้ว)
  useEffect(() => {
    // รอให้ app พร้อมก่อนถึงจะ set up listeners
    if (!appIsReady) return;

    let receivedSubscription: { remove: () => void } | null = null;
    let responseSubscription: { remove: () => void } | null = null;

    try {
      // ตั้งค่า notification handler ก่อน
      setupNotificationHandler();

      // Listener สำหรับเมื่อได้รับ notification (แอพเปิดอยู่)
      receivedSubscription = addNotificationReceivedListener((notification) => {
        try {
          console.log('📩 Notification received:', notification?.request?.content?.title);
        } catch (e) {
          console.warn('Notification received handler error:', e);
        }
      });

      // Listener สำหรับเมื่อกด notification
      responseSubscription = addNotificationResponseListener((response) => {
        try {
          console.log('👆 Notification tapped');
          const data = getNotificationData(response);

          // รอ 100ms ให้ router พร้อมก่อน navigate
          setTimeout(() => {
            try {
              // นำทางไปหน้าที่เกี่ยวข้องตาม notification type
              if (data.type === 'order') {
                router.push('/commissions');
              } else if (data.type === 'ticket' && data.ticketId) {
                router.push('/support');
              } else if (data.type === 'rider' || data.type === 'job') {
                router.push('/rider');
              } else if (data.url) {
                router.push(data.url as never);
              } else {
                router.push('/notifications');
              }
            } catch (navError) {
              console.warn('Notification navigation error:', navError);
            }
          }, 100);
        } catch (e) {
          console.warn('Notification response handler error:', e);
        }
      });
    } catch (e) {
      console.warn('Setup notification listeners error:', e);
    }

    return () => {
      try {
        receivedSubscription?.remove();
        responseSubscription?.remove();
      } catch (e) {
        // Ignore cleanup errors
      }
    };
  }, [appIsReady]);

  // ⭐ เริ่ม GPS Sharing ถ้าเคยเปิดไว้
  useEffect(() => {
    if (appIsReady && gpsSharing) {
      startGpsSharing()
        .then((success) => {
          if (success) {
            console.log('📍 GPS sharing auto-started');
          }
        })
        .catch((e) => console.log('GPS sharing auto-start error:', e));
    }
  }, [appIsReady, gpsSharing]);

  // ซ่อน splash เมื่อ app พร้อม
  const onLayoutRootView = useCallback(async () => {
    if (appIsReady) {
      await SplashScreen.hideAsync().catch(() => {});
    }
  }, [appIsReady]);

  // รอ app พร้อม
  if (!appIsReady) {
    return <LoadingScreen />;
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
        <Stack.Screen name="wallet-history" options={{ headerShown: false }} />

        {/* Tarot Screens */}
        <Stack.Screen name="tarot/index" options={{ title: 'ดูดวงไพ่ทาโรต์', headerShown: true }} />
        <Stack.Screen name="tarot/select-cards" options={{ title: 'เลือกไพ่', headerShown: true }} />
        <Stack.Screen name="tarot/reading" options={{ title: 'ผลการดูดวง', headerShown: true }} />

        {/* MLM Tree */}
        <Stack.Screen name="mlm-tree" options={{ headerShown: false }} />

        {/* Wealth Guide */}
        <Stack.Screen name="wealth-guide" options={{ headerShown: false }} />

        {/* QR Scanner */}
        <Stack.Screen name="qr-scanner" options={{ headerShown: false }} />
      </Stack>
    </View>
  );
}
