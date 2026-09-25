/**
 * ชวนเพื่อน — แบบชั้นเดียว (PLAY-08)
 *
 * แสดงแค่: รหัสแนะนำ · QR · ลิงก์สมัคร · ปุ่มแชร์/คัดลอก
 * + ข้อความเดียว "รับค่าแนะนำเมื่อเพื่อนสั่งซื้อครั้งแรก — ดูรายละเอียดบนเว็บไซต์" + ปุ่มเปิดเว็บ
 * ไม่มีระดับ / ทีม / อันดับ / เปอร์เซ็นต์คอมมิชชั่นในแอป (นโยบาย Google Play)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Share, StyleSheet, Text, View } from 'react-native';
import * as Clipboard from 'expo-clipboard';
import QRCode from 'react-native-qrcode-svg';
import { router } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { getReferralInfo } from '@/services/api/accountApi';
import { APP_INFO } from '@/config/appConfig';
import { isTrustedWebUrl } from '@/utils/linking';
import {
  Button3D,
  Card3D,
  EmptyState,
  Screen,
  WebsiteButton,
  resultHaptic,
} from '@/components/ui';
import { useTheme, spacing, radii, typography } from '@/theme';

/** ลิงก์สมัครจากรหัส (ใช้เมื่อ server ไม่ส่งลิงก์มา) */
const buildRegisterLink = (code: string): string =>
  `${APP_INFO.WEBSITE}/register?ref=${encodeURIComponent(code)}`;

export default function ReferralScreen() {
  const { colors } = useTheme();
  const user = useAuthStore((state) => state.user);
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

  const [code, setCode] = useState<string | null>(user?.referralCode || null);
  const [link, setLink] = useState<string | null>(
    user?.referralLink && isTrustedWebUrl(user.referralLink) ? user.referralLink : null
  );
  const [loading, setLoading] = useState(!user?.referralCode);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [copied, setCopied] = useState<'code' | 'link' | null>(null);
  const mountedRef = useRef(true);
  const copiedTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      if (copiedTimer.current) clearTimeout(copiedTimer.current);
    };
  }, []);

  const load = useCallback(async () => {
    if (!isAuthenticated) return;
    setErrorMessage(null);
    const result = await getReferralInfo();
    if (!mountedRef.current) return;
    if (result.success && result.data?.referralCode) {
      setCode(result.data.referralCode);
      setLink(
        result.data.referralLink && isTrustedWebUrl(result.data.referralLink)
          ? result.data.referralLink
          : buildRegisterLink(result.data.referralCode)
      );
    } else if (!code) {
      setErrorMessage(result.success ? 'ยังไม่มีรหัสแนะนำ ลองใหม่อีกครั้งนะ' : result.message);
    }
    setLoading(false);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isAuthenticated]);

  useEffect(() => {
    load();
  }, [load]);

  const shareLink = link || (code ? buildRegisterLink(code) : null);

  const copy = async (what: 'code' | 'link') => {
    const value = what === 'code' ? code : shareLink;
    if (!value) return;
    try {
      await Clipboard.setStringAsync(value);
      resultHaptic('success');
      setCopied(what);
      if (copiedTimer.current) clearTimeout(copiedTimer.current);
      copiedTimer.current = setTimeout(() => {
        if (mountedRef.current) setCopied(null);
      }, 1800);
    } catch {
      // คัดลอกไม่ได้ก็ไม่เป็นไร ผู้ใช้กดแชร์แทนได้
    }
  };

  const share = async () => {
    if (!shareLink || !code) return;
    try {
      await Share.share({
        message: `มาใช้ ThaiPrompt สั่งของจากตลาดสดและร้านใกล้บ้านกัน ใช้รหัสแนะนำ ${code} ตอนสมัครนะ\n${shareLink}`,
      });
    } catch {
      // ผู้ใช้ยกเลิกการแชร์
    }
  };

  if (!isAuthenticated) {
    return (
      <Screen title="ชวนเพื่อน" scroll={false}>
        <EmptyState
          icon="🤝"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อรับรหัสแนะนำของคุณ"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </Screen>
    );
  }

  return (
    <Screen title="ชวนเพื่อน" subtitle="แชร์รหัสของคุณให้เพื่อนสมัคร">
      {loading ? (
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      ) : errorMessage || !code ? (
        <EmptyState compact variant="error" message={errorMessage || undefined} onAction={load} />
      ) : (
        <>
          <Card3D gradientBorder padding={spacing.xl} style={styles.card}>
            <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>รหัสแนะนำของคุณ</Text>
            <Text
              selectable
              accessibilityLabel={`รหัสแนะนำ ${code}`}
              style={[typography.display, styles.code, { color: colors.goldDeep }]}
            >
              {code}
            </Text>

            <View style={[styles.qrBox, { backgroundColor: '#FFFFFF', borderColor: colors.border }]}>
              {shareLink ? <QRCode value={shareLink} size={176} backgroundColor="#FFFFFF" color="#2B241A" /> : null}
            </View>
            <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>
              ให้เพื่อนสแกน QR นี้เพื่อสมัครพร้อมรหัสของคุณ
            </Text>

            <View style={styles.row}>
              <Button3D
                title={copied === 'code' ? 'คัดลอกแล้ว' : 'คัดลอกรหัส'}
                icon={copied === 'code' ? '✅' : '📋'}
                variant="secondary"
                size="sm"
                onPress={() => copy('code')}
                style={styles.flex}
              />
              <Button3D
                title={copied === 'link' ? 'คัดลอกแล้ว' : 'คัดลอกลิงก์'}
                icon={copied === 'link' ? '✅' : '🔗'}
                variant="secondary"
                size="sm"
                onPress={() => copy('link')}
                style={styles.flex}
              />
            </View>
            <Button3D title="แชร์ให้เพื่อน" icon="📤" size="lg" fullWidth onPress={share} style={styles.shareButton} />
          </Card3D>

          <Card3D variant="flat" padding={spacing.lg} style={styles.card}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
              🎁 รับค่าแนะนำเมื่อเพื่อนสั่งซื้อครั้งแรก — ดูรายละเอียดบนเว็บไซต์
            </Text>
            <WebsiteButton path="/user" size="sm" style={styles.webButton} />
          </Card3D>
        </>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  card: {
    marginBottom: spacing.lg,
  },
  center: {
    textAlign: 'center',
  },
  code: {
    textAlign: 'center',
    letterSpacing: 2,
    marginTop: spacing.xs,
  },
  qrBox: {
    alignSelf: 'center',
    padding: spacing.md,
    borderRadius: radii.lg,
    borderWidth: 1,
    marginTop: spacing.lg,
    marginBottom: spacing.sm,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.lg,
  },
  shareButton: {
    marginTop: spacing.md,
  },
  webButton: {
    marginTop: spacing.md,
    alignSelf: 'flex-start',
  },
});
