/**
 * Root Layout
 * โหลด Fonts และ Initialize App
 */

import React, { useEffect, useState, useRef } from 'react';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { View, Text, StyleSheet, ActivityIndicator, AppState, AppStateStatus, Image, Animated, Easing, Alert } from 'react-native';
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
import { stopJobTracking, stopLegacyGpsSharing, syncJobTrackingWithServer } from '@/services/location';
import { routeForNotification } from '@/utils/notificationRouting';
import { isRestrictedNotification } from '@/utils/storePolicy';
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
      <Text style={loadingStyles.tagline}>ช้อป · ตลาดสด · ส่งของใกล้บ้าน</Text>

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
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const authInitialized = useAuthStore((state) => state.isInitialized);
  const userId = useAuthStore((state) => state.user?.id ?? null);
  /** สถานะล็อกอินรอบก่อน (null = ยังไม่รู้) — ใช้แยก "ออกจากระบบจริง" ออกจาก "ยังตรวจ token ไม่เสร็จ" */
  const prevAuthenticatedRef = useRef<boolean | null>(null);
  const { loadSettings, gpsSharing, setGpsSharing } = useAppStore();
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

        // Push Notification ลงทะเบียนหลัง login เท่านั้น (ดู effect isAuthenticated ด้านล่าง)
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
        // 📍 ไรเดอร์ที่กำลังติดตามตำแหน่งงานอยู่ → ตรวจกับ server ว่างานยังไม่จบ (ไม่มีการติดตาม = ไม่เรียก API)
        syncJobTrackingWithServer({ onlyIfTracking: true }).catch(() => {});
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
          const title = notification?.request?.content?.title || 'แจ้งเตือน';
          const body = notification?.request?.content?.body || '';
          console.log('📩 Notification received:', title);

          // นโยบาย Google Play: แจ้งเตือนระบบเครือข่าย (คอมมิชชั่น/สายงาน/rank) ไม่แสดงในแอป
          if (
            isRestrictedNotification({
              title,
              body,
              data: notification?.request?.content?.data,
            })
          ) {
            return;
          }

          // แสดง alert เมื่อได้รับ notification (เพราะ app เปิดอยู่)
          // ใช้ setTimeout เพื่อให้ UI render เสร็จก่อน
          setTimeout(() => {
            try {
              Alert.alert(
                title,
                body,
                [
                  {
                    text: 'ดูเลย',
                    onPress: () => {
                      // เปิดหน้าที่เกี่ยวข้อง (เช่น งานไรเดอร์ใหม่ → /rider-jobs) ผ่าน allowlist เดียวกับตอนกดแจ้งเตือน
                      const data = notification?.request?.content?.data as Record<string, unknown> | undefined;
                      router.push(routeForNotification(data) as never);
                    },
                  },
                  {
                    text: 'ปิด',
                    style: 'cancel',
                  },
                ],
                { cancelable: true }
              );
            } catch (alertError) {
              console.warn('Alert error:', alertError);
            }
          }, 100);
        } catch (e) {
          console.warn('Notification received handler error:', e);
        }
      });

      // Listener สำหรับเมื่อกด notification
      responseSubscription = addNotificationResponseListener((response) => {
        try {
          const data = getNotificationData(response);
          // แจ้งเตือนระบบเครือข่ายที่หลุดมาจากถาดแจ้งเตือน → เปิดแอปหน้าแรกเฉยๆ ไม่พาไปหน้าที่เกี่ยวข้อง
          const content = response?.notification?.request?.content;
          if (isRestrictedNotification({ title: content?.title, body: content?.body, data: content?.data })) {
            return;
          }

          // รอ 100ms ให้ router พร้อมก่อน navigate
          setTimeout(() => {
            try {
              // path ผ่าน allowlist แล้ว (PLAY-23: ห้ามพาไป path/URL ใดๆ จาก payload ตรงๆ)
              router.push(routeForNotification(data) as never);
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

  // 📍 PLAY-13: เลิก auto-start "แชร์ตำแหน่งให้แอดมิน" แล้ว — เครื่องที่เคยเปิดไว้ให้ปิดทิ้งหนึ่งครั้ง
  //    (ไรเดอร์ใช้การติดตามตำแหน่งระหว่างส่งงานในหน้าไรเดอร์แทน)
  useEffect(() => {
    if (appIsReady && gpsSharing) {
      stopLegacyGpsSharing().catch(() => {});
      setGpsSharing(false).catch(() => {});
    }
  }, [appIsReady, gpsSharing, setGpsSharing]);

  // 📍 RIDER-APP-12: ตัวติดตามตำแหน่งงานไรเดอร์ต้องตรงกับความจริงเสมอ
  //    - รอ authStore ตรวจ token เสร็จก่อน (isInitialized) — แอปขึ้นหน้าแรกได้ก่อนที่ /me จะตอบบนเน็ตช้า
  //      ถ้าตัดสินใจตอนนั้น isAuthenticated ยังเป็นค่าเริ่มต้น false → เคยหยุดติดตามงานที่ยังส่งอยู่ทิ้ง
  //    - เปิดแอปใหม่ (เคยถูกปิดกลางงาน) → ถาม server แล้วติดตามต่อ/หยุด task ที่ค้าง
  //      (ยังไม่ล็อกอิน/ตรวจ token ไม่ทัน ก็ถาม server เหมือนกัน: หยุดเฉพาะเมื่อ server ตอบ 401/403 จริง)
  //    - ออกจากระบบจริง (ล็อกอินอยู่ → ไม่ล็อกอิน) → หยุดติดตามทันที
  useEffect(() => {
    if (!appIsReady || !authInitialized) return;
    const wasAuthenticated = prevAuthenticatedRef.current;
    prevAuthenticatedRef.current = isAuthenticated;

    if (!isAuthenticated && wasAuthenticated === true) {
      stopJobTracking().catch(() => {});
      return;
    }
    syncJobTrackingWithServer({ onlyIfTracking: true }).catch(() => {});
  }, [appIsReady, authInitialized, isAuthenticated]);

  // 🔔 RIDER-APP-29: ลงทะเบียน push token หลัง login (และตอนเปิดแอปที่ login ค้างไว้)
  //    ผูก token กับผู้ใช้คนปัจจุบันเสมอ — เปลี่ยนบัญชีแล้วลงทะเบียนใหม่
  useEffect(() => {
    if (!appIsReady || !isAuthenticated || !userId) return;
    registerForPushNotifications().catch(() => {});
  }, [appIsReady, isAuthenticated, userId]);

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

        {/* Feature Screens - ปิด header ทั้งหมดให้แอพจัดการเอง
            (หน้า MLM / คริปโต / ดูคลิปได้เงิน ถูกถอดออกจากแอปแล้ว — นโยบาย Google Play) */}
        <Stack.Screen name="shopping" options={{ headerShown: false }} />
        <Stack.Screen name="referral" options={{ headerShown: false }} />
        <Stack.Screen name="notifications" options={{ headerShown: false }} />
        <Stack.Screen name="support" options={{ headerShown: false }} />
        <Stack.Screen name="kyc" options={{ headerShown: false }} />
        <Stack.Screen name="rider" options={{ headerShown: false }} />
        <Stack.Screen name="wiki" options={{ headerShown: false }} />
        <Stack.Screen name="settings" options={{ headerShown: false }} />
        <Stack.Screen name="product/[id]" options={{ headerShown: false }} />
        <Stack.Screen name="edit-profile" options={{ headerShown: false }} />

        {/* ตลาดสด / ร้านของฉัน */}
        <Stack.Screen name="taladsod/index" options={{ headerShown: false }} />
        <Stack.Screen name="merchant/index" options={{ headerShown: false }} />

        {/* Wallet Screens */}
        <Stack.Screen name="wallet-topup" options={{ headerShown: false }} />
        <Stack.Screen name="wallet-withdraw" options={{ headerShown: false }} />
        <Stack.Screen name="wallet-transfer" options={{ headerShown: false }} />
        <Stack.Screen name="wallet-history" options={{ headerShown: false }} />

        {/* Tarot Screens */}
        <Stack.Screen name="tarot/index" options={{ headerShown: false }} />
        <Stack.Screen name="tarot/select-cards" options={{ headerShown: false }} />
        <Stack.Screen name="tarot/reading" options={{ headerShown: false }} />

        {/* WebView - เปิดลิงก์เว็บของเรา (allowlist) */}
        <Stack.Screen name="webview" options={{ headerShown: false }} />
      </Stack>
    </View>
  );
}
