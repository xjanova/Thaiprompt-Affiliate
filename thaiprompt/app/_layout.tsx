/**
 * Root Layout
 * โหลดฟอนต์ (Anuphan + Noto Serif Thai) และ Initialize App
 * หน้าโหลด = ธีมรอยัล น้ำเงินกรมท่า-ทอง ตรงกับไอคอนแอป
 */

import React, { useEffect, useState, useRef } from 'react';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { View, StyleSheet, AppState, AppStateStatus, Image, Animated, Easing, Alert, Text as RNText } from 'react-native';
import { useFonts } from 'expo-font';
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
import { FONT_ASSETS, setFontsEnabled, useTheme } from '@/theme';

// ซ่อน native splash screen ทันทีเพื่อให้เห็น custom loading screen
SplashScreen.hideAsync().catch(() => {});

// หน้าโหลดตอนเปิดแอป — น้ำเงินกรมท่า + ลายกนกทอง + โลโก้ TP UltraApp (ตรงกับไอคอนแอป)
// ใช้ Text ของ react-native ตรงๆ เพราะฟอนต์ Anuphan อาจยังโหลดไม่เสร็จ
const KANOK = require('@/assets/images/brand/kanok-gold.webp');

const LoadingScreen = () => {
  const pulseAnim = useRef(new Animated.Value(1)).current;
  const glowAnim = useRef(new Animated.Value(0.35)).current;
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const barAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.timing(fadeAnim, { toValue: 1, duration: 450, useNativeDriver: true }).start();

    const pulse = Animated.loop(
      Animated.sequence([
        Animated.timing(pulseAnim, { toValue: 1.04, duration: 1100, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
        Animated.timing(pulseAnim, { toValue: 1, duration: 1100, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
      ])
    );
    const glow = Animated.loop(
      Animated.sequence([
        Animated.timing(glowAnim, { toValue: 0.7, duration: 1300, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
        Animated.timing(glowAnim, { toValue: 0.35, duration: 1300, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
      ])
    );
    const bar = Animated.loop(
      Animated.timing(barAnim, { toValue: 1, duration: 1400, easing: Easing.inOut(Easing.ease), useNativeDriver: true })
    );
    pulse.start();
    glow.start();
    bar.start();
    return () => {
      pulse.stop();
      glow.stop();
      bar.stop();
    };
  }, [barAnim, fadeAnim, glowAnim, pulseAnim]);

  const barX = barAnim.interpolate({ inputRange: [0, 1], outputRange: [-70, 160] });

  return (
    <Animated.View style={[loadingStyles.container, { opacity: fadeAnim }]}>
      <LinearGradient colors={['#081224', '#0C1A33', '#10223F']} style={StyleSheet.absoluteFill} />
      <LinearGradient
        colors={['rgba(46,84,150,0.5)', 'rgba(46,84,150,0)']}
        start={{ x: 1, y: 0 }}
        end={{ x: 0.3, y: 0.6 }}
        style={StyleSheet.absoluteFill}
      />
      <Image source={KANOK} style={loadingStyles.kanokTop} resizeMode="contain" />
      <Image source={KANOK} style={loadingStyles.kanokBottom} resizeMode="contain" />

      <View style={loadingStyles.logoContainer}>
        <Animated.View style={[loadingStyles.logoGlow, { opacity: glowAnim }]} />
        <Animated.Image
          source={require('@/assets/images/icon.png')}
          style={[loadingStyles.logo, { transform: [{ scale: pulseAnim }] }]}
          resizeMode="contain"
        />
      </View>

      <RNText style={loadingStyles.appName}>{APP_INFO.NAME}</RNText>
      <RNText style={loadingStyles.tagline}>ตลาดสด · ช้อป · ส่งของใกล้บ้าน</RNText>

      <View style={loadingStyles.track}>
        <Animated.View style={[loadingStyles.bar, { transform: [{ translateX: barX }] }]} />
      </View>

      <RNText style={loadingStyles.version}>v{APP_INFO.VERSION}</RNText>
    </Animated.View>
  );
};

const loadingStyles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0C1A33',
    alignItems: 'center',
    justifyContent: 'center',
  },
  kanokTop: {
    position: 'absolute',
    top: 24,
    right: -60,
    width: 300,
    height: 264,
    opacity: 0.5,
  },
  kanokBottom: {
    position: 'absolute',
    bottom: 10,
    left: -60,
    width: 260,
    height: 229,
    opacity: 0.28,
    transform: [{ rotate: '180deg' }],
  },
  logoContainer: {
    width: 128,
    height: 128,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 26,
  },
  logoGlow: {
    position: 'absolute',
    width: 190,
    height: 190,
    borderRadius: 95,
    backgroundColor: 'rgba(228,192,107,0.16)',
    boxShadow: '0px 0px 60px 20px rgba(228,192,107,0.25)',
  },
  logo: {
    width: 128,
    height: 128,
    borderRadius: 30,
  },
  appName: {
    fontSize: 26,
    fontWeight: '700',
    color: '#F3DC9B',
    letterSpacing: 0.4,
    marginBottom: 6,
  },
  tagline: {
    fontSize: 14,
    color: 'rgba(214,222,238,0.72)',
    marginBottom: 34,
  },
  track: {
    width: 160,
    height: 3,
    borderRadius: 2,
    overflow: 'hidden',
    backgroundColor: 'rgba(255,255,255,0.10)',
  },
  bar: {
    width: 70,
    height: 3,
    borderRadius: 2,
    backgroundColor: '#E4C06B',
  },
  version: {
    position: 'absolute',
    bottom: 36,
    color: 'rgba(214,222,238,0.45)',
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
  // ฟอนต์ Anuphan / Noto Serif Thai — โหลดไม่สำเร็จหรือช้าเกิน 4 วิ ให้กลับไปใช้ฟอนต์ระบบ (ข้อความไม่หาย)
  const [fontsLoaded, fontError] = useFonts(FONT_ASSETS);
  const [fontsTimedOut, setFontsTimedOut] = useState(false);
  const { colors, isDark } = useTheme();

  useEffect(() => {
    if (fontError) {
      console.warn('Font load failed, using system font:', fontError);
      setFontsEnabled(false);
    }
  }, [fontError]);

  useEffect(() => {
    if (fontsLoaded) return;
    const t = setTimeout(() => {
      setFontsEnabled(false);
      setFontsTimedOut(true);
    }, 4000);
    return () => clearTimeout(t);
  }, [fontsLoaded]);

  useEffect(() => {
    if (fontsLoaded) setFontsEnabled(true);
  }, [fontsLoaded]);
  const appState = useRef(AppState.currentState);

  useEffect(() => {
    let isMounted = true;

    const prepareApp = async () => {
      // ⭐ บันทึกเวลาเริ่มต้นเพื่อให้ loading screen แสดงขั้นต่ำ 1.5 วินาที
      const startTime = Date.now();
      const MIN_LOADING_TIME = 1500; // แสดง loading อย่างน้อย 1.5 วินาที

      try {
        console.log('App initialization started');

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

    // Force ready หลัง 3 วินาที (ฟอนต์มีตัวจับเวลาของตัวเองด้านบน)
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

  // รอ app + ฟอนต์พร้อม
  if (!appIsReady || !(fontsLoaded || fontError || fontsTimedOut)) {
    return <LoadingScreen />;
  }

  return (
    <View style={{ flex: 1, backgroundColor: colors.background }}>
      <StatusBar style={isDark ? 'light' : 'dark'} />
      <Stack
        screenOptions={{
          headerShown: false,
          animation: 'slide_from_right',
          contentStyle: {
            backgroundColor: colors.background,
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
