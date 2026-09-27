/**
 * คลิปแนะนำแอป (น้องพร้อม) แบบเต็มจอ — เปิดเองครั้งเดียวหลังล็อกอิน หรือกดดูซ้ำจากหน้าตั้งค่า
 *
 * - เล่นผ่านหน้าเว็บ /app/intro ใน WebView (ไม่ต้องมี native module วิดีโอเพิ่ม · เปลี่ยนคลิปจากเซิร์ฟเวอร์ได้)
 * - ปุ่ม "ข้าม" มุมขวาบน · ดูจบ (หน้าเว็บส่ง 'ended') = ปิดเอง · กดย้อนกลับของเครื่อง = ปิด
 * - ออกจากหน้านี้ทางไหนก็ตาม = จำว่าดู version นี้แล้ว
 * - build ที่ไม่มี WebView / ไม่มีค่าคลิป → ปิดหน้าทันที ไม่ค้าง
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Pressable, StatusBar, StyleSheet, View } from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import type { WebView as WebViewType, WebViewMessageEvent } from 'react-native-webview';
import { Text } from '@/components/ui/Text';
import { Icon } from '@/components/ui';
import { fetchIntroVideoConfig, markIntroVideoSeen } from '@/services/introVideo';
import { isTrustedWebUrl } from '@/utils/linking';

// โหลด WebView แบบไม่บังคับ — build ที่ไม่มี native module = null → ปิดหน้าไปเลย (แบบเดียวกับ LiveMap)
let webViewComponent: typeof WebViewType | null | undefined;
const loadWebView = (): typeof WebViewType | null => {
  if (webViewComponent !== undefined) return webViewComponent;
  try {
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    webViewComponent = (require('react-native-webview') as { WebView: typeof WebViewType }).WebView ?? null;
  } catch {
    webViewComponent = null;
  }
  return webViewComponent;
};

const GOLD = '#F0C96A';

export default function IntroVideoScreen() {
  const insets = useSafeAreaInsets();
  const params = useLocalSearchParams<{ url?: string; version?: string }>();
  const [pageUrl, setPageUrl] = useState<string | null>(
    typeof params.url === 'string' && isTrustedWebUrl(params.url) ? params.url : null
  );
  const [loading, setLoading] = useState(true);
  const versionRef = useRef<string>(typeof params.version === 'string' ? params.version : '');
  const closedRef = useRef(false);
  const WebView = loadWebView();

  const close = useCallback(() => {
    if (closedRef.current) return;
    closedRef.current = true;
    if (router.canGoBack()) router.back();
    else router.replace('/(tabs)' as never);
  }, []);

  // เปิดจากหน้าตั้งค่า (ไม่มี url) → ถามเซิร์ฟเวอร์เอง
  useEffect(() => {
    if (pageUrl) return;
    let cancelled = false;
    fetchIntroVideoConfig()
      .then((config) => {
        if (cancelled) return;
        if (config && isTrustedWebUrl(config.pageUrl)) {
          versionRef.current = versionRef.current || config.version;
          setPageUrl(config.pageUrl);
        } else {
          close();
        }
      })
      .catch(() => {
        if (!cancelled) close();
      });
    return () => {
      cancelled = true;
    };
  }, [pageUrl, close]);

  // ออกจากหน้านี้ทางไหนก็ตาม (ข้าม / ดูจบ / ปุ่มย้อนกลับ) = จำว่าดูแล้ว
  useEffect(() => {
    return () => {
      void markIntroVideoSeen(versionRef.current);
    };
  }, []);

  // build ไม่มี WebView → ปิดทันที
  useEffect(() => {
    if (!WebView) close();
  }, [WebView, close]);

  const onMessage = useCallback(
    (event: WebViewMessageEvent) => {
      const msg = event.nativeEvent.data;
      if (msg === 'ended' || msg === 'error') close();
    },
    [close]
  );

  return (
    <View style={styles.root}>
      <StatusBar barStyle="light-content" backgroundColor="#05070c" />
      {WebView && pageUrl ? (
        <WebView
          source={{ uri: pageUrl }}
          style={styles.web}
          containerStyle={styles.web}
          originWhitelist={['https://*']}
          mediaPlaybackRequiresUserAction={false}
          allowsInlineMediaPlayback
          allowsFullscreenVideo
          javaScriptEnabled
          domStorageEnabled
          setSupportMultipleWindows={false}
          onLoadEnd={() => setLoading(false)}
          onError={close}
          onMessage={onMessage}
          onShouldStartLoadWithRequest={(req) => req.url === pageUrl || req.url.startsWith(pageUrl)}
        />
      ) : null}

      {loading ? (
        <View style={styles.loader} pointerEvents="none">
          <ActivityIndicator color={GOLD} size="large" />
          <Text style={styles.loaderText}>กำลังเปิดคลิปแนะนำ…</Text>
        </View>
      ) : null}

      <Pressable
        onPress={close}
        accessibilityRole="button"
        accessibilityLabel="ข้ามคลิปแนะนำ"
        hitSlop={12}
        style={({ pressed }) => [styles.skip, { top: insets.top + 12, opacity: pressed ? 0.7 : 1 }]}
      >
        <Text style={styles.skipText}>ข้าม</Text>
        <Icon name="caret-right" size={16} color="#1a1405" />
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#05070c' },
  web: { flex: 1, backgroundColor: '#05070c' },
  loader: { position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, alignItems: 'center', justifyContent: 'center', gap: 12 },
  loaderText: { color: 'rgba(236,229,210,0.8)', fontSize: 14 },
  skip: {
    position: 'absolute',
    right: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 16,
    height: 38,
    borderRadius: 19,
    backgroundColor: GOLD,
  },
  skipText: { color: '#1a1405', fontSize: 15, fontWeight: '700' },
});
