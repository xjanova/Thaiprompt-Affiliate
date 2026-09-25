/**
 * ชวนเพื่อน — แบบชั้นเดียว (PLAY-08) · ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * แสดงแค่: รหัสแนะนำ (กรอบทองเส้นประ) · QR · ลิงก์สมัคร · ปุ่มคัดลอก + ปุ่มแชร์ในแถบลอยท้ายจอ
 * + ข้อความเดียว "รับค่าแนะนำเมื่อเพื่อนสั่งซื้อครั้งแรก — ดูรายละเอียดบนเว็บไซต์" + ปุ่มเปิดเว็บ
 * ไม่มีระดับ / ทีม / อันดับ / เปอร์เซ็นต์คอมมิชชั่นในแอป (นโยบาย Google Play)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, ScrollView, Share, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import * as Clipboard from 'expo-clipboard';
import QRCode from 'react-native-qrcode-svg';
import { router } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { getReferralInfo } from '@/services/api/accountApi';
import { APP_INFO } from '@/config/appConfig';
import { isTrustedWebUrl } from '@/utils/linking';
import {
  BrandArt,
  Button3D,
  Card3D,
  EmptyState,
  Screen,
  WebsiteButton,
  resultHaptic,
} from '@/components/ui';
import { IconTile } from '@/components/profile';
import { useTheme, LIGHT_THEME, spacing, radii, typography } from '@/theme';

/** ลิงก์สมัครจากรหัส (ใช้เมื่อ server ไม่ส่งลิงก์มา) */
const buildRegisterLink = (code: string): string =>
  `${APP_INFO.WEBSITE}/register?ref=${encodeURIComponent(code)}`;

/** QR ต้องเป็นสีเข้มบนพื้นขาวเสมอ (ทั้งโหมดสว่าง/มืด) ไม่งั้นกล้องบางรุ่นสแกนไม่ติด */
const QR_BG = LIGHT_THEME.colors.card;
const QR_FG = LIGHT_THEME.colors.navy;

export default function ReferralScreen() {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
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
          art="gift"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อรับรหัสแนะนำของคุณ"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </Screen>
    );
  }

  const ready = !loading && !errorMessage && !!code;

  return (
    <Screen title="ชวนเพื่อน" subtitle="แชร์รหัสของคุณให้เพื่อนสมัคร" scroll={false}>
      {loading ? (
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      ) : !ready ? (
        <EmptyState compact variant="error" message={errorMessage || undefined} onAction={load} />
      ) : (
        <>
          <ScrollView
            style={styles.flex}
            contentContainerStyle={styles.scroll}
            showsVerticalScrollIndicator={false}
          >
            {/* ---------- ภาพกล่องของขวัญ ---------- */}
            <View style={styles.hero}>
              <View style={[styles.heroGlow, { backgroundColor: colors.goldSoft }]} />
              <BrandArt name="gift" size={124} />
            </View>

            {/* ---------- รหัส + QR ---------- */}
            <Card3D gradientBorder padding={spacing.xl} style={styles.card} contentStyle={styles.cardInner}>
              <Text style={[typography.caption, { color: colors.textMuted }]}>รหัสแนะนำของคุณ</Text>
              <View style={[styles.codeBox, { backgroundColor: colors.goldSoft, borderColor: colors.gold }]}>
                <Text
                  selectable
                  accessibilityLabel={`รหัสแนะนำ ${code}`}
                  style={[typography.display, styles.code, { color: colors.goldDeep }]}
                >
                  {code}
                </Text>
              </View>

              <View style={styles.row}>
                <Button3D
                  title={copied === 'code' ? 'คัดลอกแล้ว' : 'คัดลอกรหัส'}
                  icon={copied === 'code' ? 'check-circle' : 'copy'}
                  variant="secondary"
                  size="sm"
                  onPress={() => copy('code')}
                  style={styles.flex}
                />
                <Button3D
                  title={copied === 'link' ? 'คัดลอกแล้ว' : 'คัดลอกลิงก์'}
                  icon={copied === 'link' ? 'check-circle' : 'link'}
                  variant="secondary"
                  size="sm"
                  onPress={() => copy('link')}
                  style={styles.flex}
                />
              </View>

              <View style={styles.orRow}>
                <View style={[styles.orLine, { backgroundColor: colors.divider }]} />
                <Text style={[typography.micro, { color: colors.textFaint }]}>หรือสแกน QR</Text>
                <View style={[styles.orLine, { backgroundColor: colors.divider }]} />
              </View>

              <View style={[styles.qrBox, { backgroundColor: QR_BG, borderColor: colors.border }]}>
                {shareLink ? <QRCode value={shareLink} size={176} backgroundColor={QR_BG} color={QR_FG} /> : null}
              </View>
              <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>
                ให้เพื่อนสแกน QR นี้เพื่อสมัครพร้อมรหัสของคุณ
              </Text>
            </Card3D>

            {/* ---------- ค่าแนะนำ (รายละเอียดอยู่บนเว็บ) ---------- */}
            <Card3D padding={spacing.lg} shadow="sm" style={styles.card}>
              <View style={styles.infoRow}>
                <IconTile icon="gift" tone="gold" />
                <Text style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]}>
                  รับค่าแนะนำเมื่อเพื่อนสั่งซื้อครั้งแรก — ดูรายละเอียดบนเว็บไซต์
                </Text>
              </View>
              <WebsiteButton path="/user" size="sm" icon="globe" style={styles.webButton} />
            </Card3D>
          </ScrollView>

          {/* ---------- แถบแชร์ลอยท้ายจอ ---------- */}
          <View
            style={[
              styles.bottomBar,
              {
                backgroundColor: colors.card,
                borderTopColor: colors.divider,
                paddingBottom: Math.max(insets.bottom, spacing.md) + spacing.sm,
              },
            ]}
          >
            <Button3D title="แชร์ให้เพื่อน" icon="share-network" size="lg" fullWidth onPress={share} />
          </View>
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
  scroll: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
    paddingBottom: spacing.xl,
  },
  hero: {
    alignItems: 'center',
    justifyContent: 'center',
    height: 148,
    marginBottom: -spacing.lg,
    zIndex: 1,
  },
  heroGlow: {
    position: 'absolute',
    width: 150,
    height: 150,
    borderRadius: 75,
    opacity: 0.9,
  },
  card: {
    marginBottom: spacing.lg,
  },
  cardInner: {
    alignItems: 'center',
  },
  center: {
    textAlign: 'center',
  },
  codeBox: {
    alignSelf: 'stretch',
    marginTop: spacing.sm,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.lg,
    borderRadius: radii.lg,
    borderWidth: 1.5,
    borderStyle: 'dashed',
  },
  code: {
    textAlign: 'center',
    letterSpacing: 3,
  },
  row: {
    alignSelf: 'stretch',
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.lg,
  },
  orRow: {
    alignSelf: 'stretch',
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.xl,
  },
  orLine: {
    flex: 1,
    height: 1,
  },
  qrBox: {
    padding: spacing.md,
    borderRadius: radii.lg,
    borderWidth: 1,
    marginTop: spacing.lg,
    marginBottom: spacing.sm,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  webButton: {
    marginTop: spacing.md,
    alignSelf: 'flex-start',
  },
  bottomBar: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
});
