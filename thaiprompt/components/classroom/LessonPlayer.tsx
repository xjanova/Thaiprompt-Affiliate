/**
 * LessonPlayer — กรอบเล่นวิดีโอบทเรียน (YouTube IFrame API ใน WebView) อัตราส่วน 16:9 มุมโค้ง
 *
 * - ควบคุมจากแอปผ่าน ref: play() · pause() · back10() · restart() (ไม่มีแถบเลื่อนของ YouTube)
 * - ส่งข้อความทุกชนิดกลับผ่าน onMessage (แปลงแล้วด้วย parsePlayerMessage)
 * - react-native-webview เป็น native module → โหลดแบบ lazy ใน try/catch (build ที่ไม่มี = การ์ดแจ้งเตือน ไม่ล้มทั้งแอป)
 * - WebContent ถูกระบบปิด (iOS เบื้องหลังนาน / Android หน่วยความจำต่ำ) → โหลดใหม่เอง
 * - ลิงก์ใน iframe ที่พยายามพาออกนอกแอป (โลโก้ YouTube) ถูกบล็อก
 */

import React, { forwardRef, useCallback, useImperativeHandle, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, StyleSheet, View } from 'react-native';
import type { WebView as WebViewType, WebViewMessageEvent } from 'react-native-webview';
import { Text } from '@/components/ui/Text';
import { Button3D, Icon } from '@/components/ui';
import { APP_INFO } from '@/config/appConfig';
import { useTheme, palette, radii, spacing, typography } from '@/theme';
import { buildPlayerHtml, parsePlayerMessage, playerCommand, playerErrorMessage, type PlayerMessage } from './playerHtml';

// โหลด WebView ครั้งเดียว — ไม่มี native module (build เก่า) = null
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

/** ต้นทางของหน้าเล่น (https ของเว็บเรา) — YouTube ต้องเห็นต้นทางจริงถึงยอมให้ฝัง */
const PLAYER_ORIGIN = (() => {
  const m = /^https:\/\/[^/]+/i.exec(APP_INFO.WEBSITE || '');
  return m ? m[0] : 'https://main.thaiprompt.online';
})();
const PLAYER_BASE_URL = `${PLAYER_ORIGIN}/`;

export interface LessonPlayerHandle {
  play: () => void;
  pause: () => void;
  back10: () => void;
  restart: () => void;
}

export interface LessonPlayerProps {
  videoId: string;
  startSeconds: number;
  maxAllowedSeconds: number;
  onMessage: (msg: PlayerMessage) => void;
}

export const LessonPlayer = forwardRef<LessonPlayerHandle, LessonPlayerProps>(
  ({ videoId, startSeconds, maxAllowedSeconds, onMessage }, ref) => {
    const { colors } = useTheme();
    const WebView = useMemo(loadWebView, []);
    const webRef = useRef<WebViewType>(null);
    const [webKey, setWebKey] = useState(0);
    const [ready, setReady] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // หน้า HTML สร้างครั้งเดียวต่อวิดีโอ/รอบโหลด (จุดเริ่ม/เพดานคิดตอนเปิดหน้า)
    // eslint-disable-next-line react-hooks/exhaustive-deps
    const html = useMemo(() => buildPlayerHtml({ videoId, startSeconds, maxAllowedSeconds, origin: PLAYER_ORIGIN }), [videoId, webKey]);

    const send = useCallback((cmd: 'play' | 'pause' | 'back10' | 'restart') => {
      webRef.current?.injectJavaScript(playerCommand(cmd));
    }, []);

    useImperativeHandle(
      ref,
      () => ({
        play: () => send('play'),
        pause: () => send('pause'),
        back10: () => send('back10'),
        restart: () => send('restart'),
      }),
      [send]
    );

    const handleMessage = useCallback(
      (event: WebViewMessageEvent) => {
        const msg = parsePlayerMessage(event.nativeEvent.data);
        if (!msg) return;
        if (msg.t === 'ready') {
          setReady(true);
          setError(null);
        } else if (msg.t === 'error') {
          setError(playerErrorMessage(msg.code));
        }
        onMessage(msg);
      },
      [onMessage]
    );

    const reload = useCallback(() => {
      setError(null);
      setReady(false);
      setWebKey((k) => k + 1);
    }, []);

    if (!WebView || !html) {
      return (
        <View style={[styles.frame, styles.center, { backgroundColor: colors.navyFill }]}>
          <Icon name="warning-circle" size={30} color={colors.goldLight} />
          <Text style={[typography.bodySm, styles.centerText, { color: colors.onHeader }]}>
            {!html ? 'บทเรียนนี้ยังไม่มีวิดีโอ แจ้งทีมงานได้ที่หน้าช่วยเหลือนะ' : 'แอปรุ่นนี้ยังเล่นวิดีโอบทเรียนไม่ได้ อัปเดตแอปก่อนนะ'}
          </Text>
        </View>
      );
    }

    return (
      <View style={[styles.frame, { backgroundColor: palette.black }]}>
        <WebView
          key={webKey}
          ref={webRef}
          source={{ html, baseUrl: PLAYER_BASE_URL }}
          originWhitelist={['https://*', 'about:blank']}
          onMessage={handleMessage}
          // iframe ของ YouTube โหลดได้ · หน้าหลักห้ามเปลี่ยนไปที่อื่น (กดโลโก้ YouTube แล้วหลุดออกจากบทเรียน)
          onShouldStartLoadWithRequest={(req) =>
            req.url === 'about:blank' ||
            req.url.startsWith(PLAYER_BASE_URL) ||
            (req as { isTopFrame?: boolean }).isTopFrame === false
          }
          onError={() => setError(playerErrorMessage('api'))}
          onRenderProcessGone={reload}
          onContentProcessDidTerminate={reload}
          javaScriptEnabled
          domStorageEnabled
          allowsInlineMediaPlayback
          mediaPlaybackRequiresUserAction={false}
          allowsFullscreenVideo={false}
          allowFileAccess={false}
          allowsLinkPreview={false}
          setSupportMultipleWindows={false}
          javaScriptCanOpenWindowsAutomatically={false}
          mixedContentMode="never"
          scrollEnabled={false}
          bounces={false}
          overScrollMode="never"
          style={styles.web}
          containerStyle={styles.web}
        />
        {!ready && !error && (
          <View style={[StyleSheet.absoluteFill, styles.center]} pointerEvents="none">
            <ActivityIndicator color={colors.goldLight} size="large" />
            <Text style={[typography.caption, styles.centerText, { color: colors.onHeaderMuted }]}>กำลังเตรียมวิดีโอ…</Text>
          </View>
        )}
        {!!error && (
          <View style={[StyleSheet.absoluteFill, styles.center, { backgroundColor: colors.navyFill }]}>
            <Icon name="warning-circle" size={28} color={colors.goldLight} />
            <Text style={[typography.bodySm, styles.centerText, { color: colors.onHeader }]}>{error}</Text>
            <Button3D title="ลองโหลดใหม่" icon="arrows-clockwise" size="sm" variant="primary" onPress={reload} style={styles.retry} />
          </View>
        )}
      </View>
    );
  }
);
LessonPlayer.displayName = 'LessonPlayer';

const styles = StyleSheet.create({
  frame: {
    width: '100%',
    aspectRatio: 16 / 9,
    borderRadius: radii.lg,
    overflow: 'hidden',
  },
  web: {
    flex: 1,
    backgroundColor: palette.black,
  },
  center: {
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.lg,
    gap: spacing.sm,
  },
  centerText: {
    textAlign: 'center',
  },
  retry: {
    marginTop: spacing.xs,
  },
});
