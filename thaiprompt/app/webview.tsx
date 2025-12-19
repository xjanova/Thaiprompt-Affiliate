/**
 * WebView Screen - เปิด URL ในเบราว์เซอร์ภายนอก
 * (ใช้ Linking แทน react-native-webview เพราะยังไม่ได้ติดตั้ง)
 */

import React, { useEffect } from 'react';
import {
  View,
  Text,
  Pressable,
  ActivityIndicator,
  StyleSheet,
  StatusBar,
  SafeAreaView,
  Linking,
} from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';

export default function WebViewScreen() {
  const params = useLocalSearchParams<{
    url?: string;
    title?: string;
    icon?: string;
  }>();

  // Decode URL
  const url = params.url ? decodeURIComponent(params.url) : null;
  const title = params.title ? decodeURIComponent(params.title) : 'เว็บไซต์';
  const icon = params.icon ? decodeURIComponent(params.icon) : '🌐';

  // เปิด URL ในเบราว์เซอร์และกลับหน้าเดิม
  useEffect(() => {
    if (url) {
      Linking.openURL(url).catch((err) => {
        console.error('Cannot open URL:', err);
      });
      // กลับหน้าเดิมหลังเปิด URL
      setTimeout(() => {
        router.back();
      }, 500);
    }
  }, [url]);

  // ถ้าไม่มี URL ให้กลับ
  if (!url) {
    return (
      <SafeAreaView style={styles.container}>
        <StatusBar barStyle="light-content" backgroundColor="#0F172A" />
        <View style={styles.errorContainer}>
          <Text style={styles.errorEmoji}>❌</Text>
          <Text style={styles.errorText}>ไม่พบ URL</Text>
          <Pressable style={styles.backButton} onPress={() => router.back()}>
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
        <Text style={styles.urlText}>{icon} {url}</Text>
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
