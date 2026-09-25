/**
 * PromptPayQR — การ์ดชำระเงินพร้อมเพย์ของออเดอร์ (QR + ยอดตรงทุกสตางค์ + นับถอยหลัง) ธีมรอยัล
 *
 * - การ์ดขอบทอง · กรอบ QR ขาวเสมอ (ทั้งโหมดมืด — แอปธนาคารสแกนติด) ขอบทองบาง
 * - qr_code จาก server เป็น data URI (SVG จาก BaconQrCode หรือ PNG) → SVG วาดด้วย react-native-svg
 * - ยอดมีเศษสตางค์เฉพาะตัวให้ SMS Checker จับคู่ → แสดง 2 ตำแหน่งเสมอ + ปุ่มคัดลอกยอด
 * - หมดอายุ / สร้าง QR ไม่สำเร็จ → ปุ่ม "ขอ QR ใหม่" (onRenew → POST /payment/order)
 * - สถานะจริงมาจากการ poll ของหน้าที่ใช้การ์ดนี้ (ส่ง state เข้ามา)
 */

import React, { useEffect, useMemo, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { SvgXml } from 'react-native-svg';
import * as Clipboard from 'expo-clipboard';
import type { PaymentInstruction } from '@/services/api/shopApi';
import { Button3D, Card3D, Icon, Pill, PriceText, formatBaht, resultHaptic } from '@/components/ui';
import { useTheme, palette, radii, spacing, typography, withAlpha } from '@/theme';
import { IconTile } from './ShopKit';

export type PromptPayState = 'waiting' | 'paid' | 'expired' | 'error';

export interface PromptPayQRProps {
  payment: PaymentInstruction;
  state: PromptPayState;
  /** ชื่อร้าน (สั่งหลายร้าน = หลาย QR) */
  title?: string;
  /** ขอ QR ใหม่ (หมดอายุ/สร้างไม่สำเร็จ) */
  onRenew?: () => unknown;
  /** ข้อความเพิ่มใต้ QR */
  hint?: string;
}

// =====================================================
// ถอด base64 (ไม่พึ่ง atob ของเครื่อง)
// =====================================================

const B64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

const decodeBase64 = (input: string): string => {
  const clean = input.replace(/[^A-Za-z0-9+/]/g, '');
  const bytes: number[] = [];
  let buffer = 0;
  let bits = 0;
  for (let i = 0; i < clean.length; i++) {
    const value = B64.indexOf(clean.charAt(i));
    if (value < 0) continue;
    buffer = (buffer << 6) | value;
    bits += 6;
    if (bits >= 8) {
      bits -= 8;
      bytes.push((buffer >> bits) & 0xff);
    }
  }
  // SVG เป็น ASCII/UTF-8 → แปลงแบบ UTF-8
  try {
    return decodeURIComponent(bytes.map((b) => `%${b.toString(16).padStart(2, '0')}`).join(''));
  } catch {
    // ทีละก้อน กัน argument ยาวเกินใน fromCharCode
    let out = '';
    for (let i = 0; i < bytes.length; i += 4096) {
      out += String.fromCharCode.apply(null, bytes.slice(i, i + 4096));
    }
    return out;
  }
};

type QrSource = { kind: 'svg'; xml: string } | { kind: 'image'; uri: string } | null;

/** แปลง qr_code / qr_code_url เป็นสิ่งที่วาดได้ */
const resolveQr = (qr: string | null | undefined, qrUrl: string | null | undefined): QrSource => {
  if (typeof qr === 'string' && qr.startsWith('data:image/svg+xml')) {
    const comma = qr.indexOf(',');
    if (comma > 0) {
      const meta = qr.slice(0, comma);
      const body = qr.slice(comma + 1);
      try {
        const xml = meta.includes(';base64') ? decodeBase64(body) : decodeURIComponent(body);
        if (xml.includes('<svg')) return { kind: 'svg', xml };
      } catch {
        // ถอดไม่ได้ → ลองทางอื่น
      }
    }
  }
  if (typeof qr === 'string' && qr.startsWith('data:image/')) {
    return { kind: 'image', uri: qr };
  }
  if (typeof qrUrl === 'string' && /^https:\/\//i.test(qrUrl)) {
    return { kind: 'image', uri: qrUrl };
  }
  return null;
};

const secondsLeft = (iso: string | null | undefined): number | null => {
  if (!iso) return null;
  const t = new Date(iso).getTime();
  if (Number.isNaN(t)) return null;
  return Math.max(0, Math.floor((t - Date.now()) / 1000));
};

export const PromptPayQR: React.FC<PromptPayQRProps> = ({ payment, state, title, onRenew, hint }) => {
  const { colors, gradients } = useTheme();
  const qr = useMemo(() => resolveQr(payment.qr_code, payment.qr_code_url), [payment.qr_code, payment.qr_code_url]);
  const [left, setLeft] = useState<number | null>(() => secondsLeft(payment.expired_at));
  const [copied, setCopied] = useState(false);

  // นับถอยหลังเวลาหมดอายุของ QR
  useEffect(() => {
    setLeft(secondsLeft(payment.expired_at));
    if (!payment.expired_at || state !== 'waiting') return;
    const timer = setInterval(() => setLeft(secondsLeft(payment.expired_at)), 1000);
    return () => clearInterval(timer);
  }, [payment.expired_at, state]);

  useEffect(() => {
    if (!copied) return;
    const timer = setTimeout(() => setCopied(false), 2000);
    return () => clearTimeout(timer);
  }, [copied]);

  const expiredByClock = state === 'waiting' && left !== null && left <= 0;
  const effective: PromptPayState = expiredByClock ? 'expired' : state;
  const hasQr = !!qr && payment.status !== 'error';

  const copyAmount = async () => {
    try {
      await Clipboard.setStringAsync(Number(payment.amount).toFixed(2));
      resultHaptic('success');
      setCopied(true);
    } catch {
      // คัดลอกไม่ได้ก็ไม่เป็นไร
    }
  };

  const mm = left !== null ? Math.floor(left / 60) : 0;
  const ss = left !== null ? left % 60 : 0;

  /** แถวข้อมูลบัญชีในกล่องยุบ (ป้ายซ้าย ค่าขวา) */
  const infoRow = (label: string, value: string, strong: boolean = true) => (
    <View style={styles.infoRow}>
      <Text style={[typography.caption, { color: colors.textMuted }]}>{label}</Text>
      <Text selectable style={[strong ? typography.bodyStrong : typography.bodySm, styles.infoValue, { color: strong ? colors.textStrong : colors.text }]}>
        {value}
      </Text>
    </View>
  );

  return (
    <Card3D gradientBorder padding={spacing.lg} style={styles.card}>
      <View style={styles.header}>
        <IconTile icon="qr-code" tone="gold" size={40} />
        <View style={styles.flex}>
          <Text style={[typography.h3, { color: colors.textStrong }]} numberOfLines={1}>
            {title || 'สแกนจ่ายด้วยพร้อมเพย์'}
          </Text>
          <Text style={[typography.micro, { color: colors.textMuted }]}>Thai QR · พร้อมเพย์</Text>
        </View>
        {effective === 'paid' && <Pill label="ชำระแล้ว" tone="success" icon="check-circle" />}
        {effective === 'waiting' && <Pill label="รอชำระ" tone="warning" icon="hourglass" />}
        {effective === 'expired' && <Pill label="QR หมดอายุ" tone="danger" icon="clock" />}
      </View>

      {effective === 'paid' ? (
        <View style={styles.paidBox}>
          <View style={[styles.medalOuter, { backgroundColor: colors.successSoft }]}>
            <LinearGradient colors={gradients.success} start={{ x: 0, y: 0 }} end={{ x: 0, y: 1 }} style={styles.medalInner}>
              <Icon name="check" size={30} color={colors.textOnAccent} weight="bold" />
            </LinearGradient>
          </View>
          <Text style={[typography.h2, { color: colors.success }]}>ได้รับเงินแล้ว</Text>
          <PriceText amount={payment.amount} decimals={2} size="lg" tone="strong" />
        </View>
      ) : !hasQr || effective === 'error' ? (
        <View style={styles.errorBox}>
          <IconTile icon="warning-circle" tone="danger" size={56} weight="fill" style={styles.errorIcon} />
          <Text style={[typography.body, styles.center, { color: colors.textMuted }]}>
            {payment.message && /[฀-๿]/.test(payment.message)
              ? payment.message
              : 'สร้าง QR ไม่สำเร็จ กดขอ QR ใหม่ได้เลย'}
          </Text>
          {onRenew && <Button3D title="ขอ QR ใหม่" icon="arrows-clockwise" onPress={onRenew} style={styles.renew} />}
        </View>
      ) : (
        <>
          {/* พื้น QR ต้องขาวเสมอ (ทั้งโหมดมืด) ไม่งั้นแอปธนาคารสแกนไม่ติด */}
          <View style={[styles.qrFrame, { backgroundColor: palette.white, borderColor: withAlpha(colors.gold, 0.55) }]}>
            {qr?.kind === 'svg' ? (
              <SvgXml xml={qr.xml} width={220} height={220} />
            ) : qr?.kind === 'image' ? (
              <Image source={{ uri: qr.uri }} style={styles.qrImage} contentFit="contain" accessibilityLabel="คิวอาร์โค้ดพร้อมเพย์" />
            ) : null}
            {effective === 'expired' && (
              <View style={[styles.qrCover, { backgroundColor: colors.overlay }]}>
                <Icon name="hourglass" size={30} color={colors.textOnAccent} />
                <Text style={[typography.h3, { color: colors.textOnAccent }]}>QR หมดอายุแล้ว</Text>
              </View>
            )}
          </View>

          <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>ยอดที่ต้องโอน (ตรงทุกสตางค์)</Text>
          <PriceText amount={payment.amount} decimals={2} size="xl" tone="gold" style={styles.center} />

          <View style={styles.row}>
            <Button3D
              title={copied ? 'คัดลอกแล้ว' : 'คัดลอกยอด'}
              icon={copied ? 'check-circle' : 'copy'}
              variant="secondary"
              size="sm"
              onPress={copyAmount}
              accessibilityLabel={`คัดลอกยอด ${formatBaht(payment.amount, { decimals: 2 })}`}
              style={styles.flex}
            />
            {effective === 'expired' && onRenew && (
              <Button3D title="ขอ QR ใหม่" icon="arrows-clockwise" size="sm" onPress={onRenew} style={styles.flex} />
            )}
          </View>

          <View style={[styles.info, { backgroundColor: colors.inset, borderColor: colors.border }]}>
            {!!payment.promptpay?.account_name && infoRow('ชื่อบัญชี', payment.promptpay.account_name)}
            {!!payment.promptpay?.promptpay_id && infoRow('พร้อมเพย์', payment.promptpay.promptpay_id)}
            {!!payment.ref_no && infoRow('เลขอ้างอิง', payment.ref_no, false)}
            {effective === 'waiting' && left !== null && (
              <View style={[styles.timerRow, { backgroundColor: colors.warningSoft }]}>
                <Icon name="timer" size={15} color={colors.warning} />
                <Text style={[typography.caption, { color: colors.text }]}>
                  QR ใช้ได้อีก{' '}
                  <Text style={[styles.timerValue, { color: colors.warning }]}>
                    {mm}:{String(ss).padStart(2, '0')}
                  </Text>{' '}
                  นาที
                </Text>
              </View>
            )}
          </View>

          <View style={styles.hintRow}>
            <Icon name="shield-check" size={15} color={colors.textMuted} />
            <Text style={[typography.caption, styles.hintText, { color: colors.textMuted }]}>
              {hint || 'สแกนด้วยแอปธนาคาร แล้วโอนยอดนี้ให้ตรงทุกสตางค์ ระบบจะยืนยันให้อัตโนมัติ'}
            </Text>
          </View>
        </>
      )}
    </Card3D>
  );
};

const styles = StyleSheet.create({
  card: {
    marginBottom: spacing.md,
  },
  flex: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginBottom: spacing.lg,
  },
  center: {
    textAlign: 'center',
    alignSelf: 'center',
  },
  qrFrame: {
    alignSelf: 'center',
    width: 244,
    height: 244,
    borderRadius: 20,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
    marginBottom: spacing.lg,
  },
  qrImage: {
    width: 220,
    height: 220,
  },
  qrCover: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xs,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  info: {
    marginTop: spacing.md,
    borderRadius: radii.md,
    borderWidth: 1,
    padding: spacing.md,
    gap: spacing.sm,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.md,
  },
  infoValue: {
    flexShrink: 1,
    textAlign: 'right',
  },
  timerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: radii.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 6,
  },
  timerValue: {
    fontWeight: '700',
    fontVariant: ['tabular-nums'],
  },
  hintRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'center',
    gap: 6,
    marginTop: spacing.md,
    paddingHorizontal: spacing.sm,
  },
  hintText: {
    flexShrink: 1,
    textAlign: 'center',
  },
  paidBox: {
    alignItems: 'center',
    gap: spacing.xs,
    paddingVertical: spacing.lg,
  },
  medalOuter: {
    width: 84,
    height: 84,
    borderRadius: 42,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm,
  },
  medalInner: {
    width: 60,
    height: 60,
    borderRadius: 30,
    alignItems: 'center',
    justifyContent: 'center',
  },
  errorBox: {
    alignItems: 'center',
    paddingVertical: spacing.md,
  },
  errorIcon: {
    marginBottom: spacing.md,
  },
  renew: {
    marginTop: spacing.md,
  },
});

export default PromptPayQR;
