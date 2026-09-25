/**
 * WebView Screen - เปิดลิงก์เว็บของเราในเบราว์เซอร์ในแอป (Custom Tabs)
 *
 * PLAY-23: deep link thaiprompt://webview?url= รับเฉพาะ https://*.thaiprompt.online
 * scheme/โดเมนอื่น (intent://, javascript:, เว็บภายนอก) ปฏิเสธทั้งหมด
 */

import React, { useEffect, useRef } from 'react';
import {
  View,
  Text,
  Pressable,
  ActivityIndicator,
  StyleSheet,
  StatusBar,
  SafeAreaView,
} from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import { isTrustedWebUrl } from '@/utils/linking';

const safeDecode = (value?: string): string | null => {
  if (!value) return null;
  try {
    return decodeURIComponent(value);
  } catch {
    return null;
  }
};

const goBackSafely = () => {
  if (router.canGoBack()) {
    router.back();
  } else {
    router.replace('/(tabs)' as never);
  }
};

export default function WebViewScreen() {
  const params = useLocalSearchParams<{
    url?: string;
    title?: string;
    icon?: string;
  }>();

  // Decode URL
  const rawUrl = safeDecode(params.url);
  const url = isTrustedWebUrl(rawUrl) ? rawUrl : null;
  const title = safeDecode(params.title) || 'เว็บไซต์';
  const icon = safeDecode(params.icon) || '🌐';
  const openedRef = useRef(false);

  // เปิด URL ในเบราว์เซอร์แล้วกลับหน้าเดิม (เปิดครั้งเดียว)
  useEffect(() => {
    if (!url || openedRef.current) return;
    openedRef.current = true;
    WebBrowser.openBrowserAsync(url)
      .catch(() => {
        // เปิดไม่ได้ก็กลับหน้าเดิม
      })
      .finally(() => {
        goBackSafely();
      });
  }, [url]);

  // ไม่มี URL หรือเป็นลิงก์ที่ไม่อนุญาต
  if (!url) {
    return (
      <SafeAreaView style={styles.container}>
        <StatusBar barStyle="light-content" backgroundColor="#0F172A" />
        <View style={styles.errorContainer}>
          <Text style={styles.errorEmoji}>🔒</Text>
          <Text style={styles.errorText}>
            {rawUrl ? 'ลิงก์นี้เปิดจากแอปไม่ได้เพื่อความปลอดภัยของคุณ' : 'ไม่พบลิงก์ที่ต้องการเปิด'}
          </Text>
          <Pressable style={styles.backButton} onPress={goBackSafely} accessibilityRole="button">
            <Text style={styles.backButtonText}>กลับ</Text>
          </Pressable>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F172A" />
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#3B82F6" />
        <Text style={styles.loadingText}>กำลังเปิด {title}...</Text>
        <Text style={styles.urlText}>{icon}</Text>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F172A',
  },
  loadingContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  loadingText: {
    color: '#FFFFFF',
    fontSize: 18,
    fontWeight: '600',
    marginTop: 16,
  },
  urlText: {
    color: '#9CA3AF',
    fontSize: 14,
    marginTop: 8,
    textAlign: 'center',
  },
  errorContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  errorEmoji: {
    fontSize: 64,
    marginBottom: 16,
  },
  errorText: {
    color: '#EF4444',
    fontSize: 16,
    textAlign: 'center',
    marginBottom: 24,
  },
  backButton: {
    backgroundColor: '#3B82F6',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 12,
  },
  backButtonText: {
    color: '#FFFFFF',
    fontWeight: 'bold',
  },
});
