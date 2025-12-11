/**
 * Root Layout
 * โหลด Fonts และ Initialize App
 */

import React, { useEffect, useState, useRef } from 'react';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { View, Text, StyleSheet, ActivityIndicator, AppState, AppStateStatus, Image, Animated, Easing } from 'react-native';
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

// ซ่อน native splash screen ทันทีเพื่อให้เห็น custom loading screen
SplashScreen.hideAsync().catch(() => {});

// Premium Loading Screen with Logo + Animation
const LoadingScreen = () => {
  // Animations
  const pulseAnim = useRef(new Animated.Value(1)).current;
  const glowAnim = useRef(new Animated.Value(0.2)).current;
  const fadeAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // Fade in animation
    Animated.timing(fadeAnim, {
      toValue: 1,
      duration: 500,
      useNativeDriver: true,
    }).start();

    // Pulse animation for logo
    const pulseAnimation = Animated.loop(
      Animated.sequence([
        Animated.timing(pulseAnim, {
          toValue: 1.05,
          duration: 1000,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(pulseAnim, {
          toValue: 1,
          duration: 1000,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
      ])
    );

    // Glow animation
    const glowAnimation = Animated.loop(
      Animated.sequence([
        Animated.timing(glowAnim, {
          toValue: 0.5,
          duration: 1200,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: false,
        }),
        Animated.timing(glowAnim, {
          toValue: 0.2,
          duration: 1200,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: false,
        }),
      ])
    );

    pulseAnimation.start();
    glowAnimation.start();

    return () => {
      pulseAnimation.stop();
      glowAnimation.stop();
    };
  }, []);

  return (
    <Animated.View style={[loadingStyles.container, { opacity: fadeAnim }]}>
      <LinearGradient
        colors={['#0F0F23', '#1a1a2e', '#16213e']}
        style={StyleSheet.absoluteFill}
      />

      {/* Logo with pulse animation */}
      <View style={loadingStyles.logoContainer}>
        <Animated.View style={[loadingStyles.logoGlow, { opacity: glowAnim }]} />
        <Animated.Image
          source={require('@/assets/images/icon.png')}
          style={[loadingStyles.logo, { transform: [{ scale: pulseAnim }] }]}
          resizeMode="contain"
        />
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
    </Animated.View>
  );
};

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
    width: 120,
    height: 120,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logo: {
    width: 120,
    height: 120,
    borderRadius: 30,
    zIndex: 1,
  },
  logoGlow: {
    position: 'absolute',
    top: -15,
    left: -15,
    right: -15,
    bottom: -15,
    borderRadius: 45,
    backgroundColor: '#3B82F6',
    // opacity controlled by animation
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
      // ⭐ บันทึกเวลาเริ่มต้นเพื่อให้ loading screen แสดงขั้นต่ำ 1.5 วินาที
      const startTime = Date.now();
      const MIN_LOADING_TIME = 1500; // แสดง loading อย่างน้อย 1.5 วินาที

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
        // ⭐ รอให้ครบเวลาขั้นต่ำก่อนแสดงหน้าแอพ
        const elapsed = Date.now() - startTime;
        const remainingTime = Math.max(0, MIN_LOADING_TIME - elapsed);

        if (remainingTime > 0) {
          await new Promise((resolve) => setTimeout(resolve, remainingTime));
        }

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
              const orderId = data.orderId || data.order_id;
              if (data.type === 'order_message' && orderId) {
                // ข้อความใหม่ในคำสั่งซื้อ - ไปหน้ารายละเอียด Order (tab แชท)
                router.push(`/order/${orderId}` as never);
              } else if (data.type === 'order') {
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

  // รอ app พร้อม
  if (!appIsReady) {
    return <LoadingScreen />;
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

        {/* Feature Screens - ปิด header ทั้งหมดให้แอพจัดการเอง */}
        <Stack.Screen name="main-menu" options={{ headerShown: false }} />
        <Stack.Screen name="dashboard" options={{ headerShown: false }} />
        <Stack.Screen name="shopping" options={{ headerShown: false }} />
        <Stack.Screen name="commissions" options={{ headerShown: false }} />
        <Stack.Screen name="referral" options={{ headerShown: false }} />
        <Stack.Screen name="notifications" options={{ headerShown: false }} />
        <Stack.Screen name="support" options={{ headerShown: false }} />
        <Stack.Screen name="kyc" options={{ headerShown: false }} />
        <Stack.Screen name="leaderboard" options={{ headerShown: false }} />
        <Stack.Screen name="rank" options={{ headerShown: false }} />
        <Stack.Screen name="rider" options={{ headerShown: false }} />
        <Stack.Screen name="wiki" options={{ headerShown: false }} />
        <Stack.Screen name="settings" options={{ headerShown: false }} />
        <Stack.Screen name="coming-soon" options={{ headerShown: false }} />
        <Stack.Screen name="product/[id]" options={{ headerShown: false }} />
        <Stack.Screen name="edit-profile" options={{ headerShown: false }} />

        {/* Wallet Screens */}
        <Stack.Screen name="wallet-topup" options={{ headerShown: false }} />
        <Stack.Screen name="wallet-withdraw" options={{ headerShown: false }} />
        <Stack.Screen name="wallet-transfer" options={{ headerShown: false }} />
        <Stack.Screen name="wallet-history" options={{ headerShown: false }} />

        {/* Tarot Screens */}
        <Stack.Screen name="tarot/index" options={{ headerShown: false }} />
        <Stack.Screen name="tarot/select-cards" options={{ headerShown: false }} />
        <Stack.Screen name="tarot/reading" options={{ headerShown: false }} />

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
