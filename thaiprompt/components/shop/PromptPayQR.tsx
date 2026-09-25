/**
 * PromptPayQR — การ์ดชำระเงินพร้อมเพย์ของออเดอร์ (QR + ยอดตรงทุกสตางค์ + นับถอยหลัง)
 *
 * - qr_code จาก server เป็น data URI (SVG จาก BaconQrCode หรือ PNG) → SVG วาดด้วย react-native-svg
 * - ยอดมีเศษสตางค์เฉพาะตัวให้ SMS Checker จับคู่ → แสดง 2 ตำแหน่งเสมอ + ปุ่มคัดลอกยอด
 * - หมดอายุ / สร้าง QR ไม่สำเร็จ → ปุ่ม "ขอ QR ใหม่" (onRenew → POST /payment/order)
 * - สถานะจริงมาจากการ poll ของหน้าที่ใช้การ์ดนี้ (ส่ง state เข้ามา)
 */

import React, { useEffect, useMemo, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { SvgXml } from 'react-native-svg';
import * as Clipboard from 'expo-clipboard';
import type { PaymentInstruction } from '@/services/api/shopApi';
import { Button3D, Card3D, Pill, PriceText, formatBaht, resultHaptic } from '@/components/ui';
import { useTheme, palette, radii, spacing, typography } from '@/theme';

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
  const { colors } = useTheme();
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

  return (
    <Card3D gradientBorder padding={spacing.lg} style={styles.card}>
      <View style={styles.header}>
        <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]} numberOfLines={1}>
          {title || 'สแกนจ่ายด้วยพร้อมเพย์'}
        </Text>
        {effective === 'paid' && <Pill label="ชำระแล้ว" tone="success" icon="✅" />}
        {effective === 'waiting' && <Pill label="รอชำระ" tone="warning" icon="⏳" />}
        {effective === 'expired' && <Pill label="QR หมดอายุ" tone="danger" />}
      </View>

      {effective === 'paid' ? (
        <View style={styles.paidBox}>
          <Text style={styles.paidIcon}>🎉</Text>
          <Text style={[typography.h2, { color: colors.success }]}>ได้รับเงินแล้ว</Text>
          <PriceText amount={payment.amount} decimals={2} size="lg" tone="strong" />
        </View>
      ) : !hasQr || effective === 'error' ? (
        <View style={styles.errorBox}>
          <Text style={[typography.body, styles.center, { color: colors.textMuted }]}>
            {payment.message && /[฀-๿]/.test(payment.message)
              ? payment.message
              : 'สร้าง QR ไม่สำเร็จ กดขอ QR ใหม่ได้เลย'}
          </Text>
          {onRenew && <Button3D title="ขอ QR ใหม่" icon="🔄" onPress={onRenew} style={styles.renew} />}
        </View>
      ) : (
        <>
          {/* พื้น QR ต้องขาวเสมอ (ทั้งโหมดมืด) ไม่งั้นแอปธนาคารสแกนไม่ติด */}
          <View style={[styles.qrFrame, { backgroundColor: palette.white, borderColor: colors.border }]}>
            {qr?.kind === 'svg' ? (
              <SvgXml xml={qr.xml} width={220} height={220} />
            ) : qr?.kind === 'image' ? (
              <Image source={{ uri: qr.uri }} style={styles.qrImage} contentFit="contain" accessibilityLabel="คิวอาร์โค้ดพร้อมเพย์" />
            ) : null}
            {effective === 'expired' && (
              <View style={[styles.qrCover, { backgroundColor: colors.overlay }]}>
                <Text style={[typography.h3, { color: colors.textOnAccent }]}>QR หมดอายุแล้ว</Text>
              </View>
            )}
          </View>

          <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>ยอดที่ต้องโอน (ตรงทุกสตางค์)</Text>
          <PriceText amount={payment.amount} decimals={2} size="xl" tone="gold" style={styles.center} />

          <View style={styles.row}>
            <Button3D
              title={copied ? 'คัดลอกแล้ว' : 'คัดลอกยอด'}
              icon={copied ? '✅' : '📋'}
              variant="secondary"
              size="sm"
              onPress={copyAmount}
              accessibilityLabel={`คัดลอกยอด ${formatBaht(payment.amount, { decimals: 2 })}`}
              style={styles.flex}
            />
            {effective === 'expired' && onRenew && (
              <Button3D title="ขอ QR ใหม่" icon="🔄" size="sm" onPress={onRenew} style={styles.flex} />
            )}
          </View>

          <View style={[styles.info, { backgroundColor: colors.inset }]}>
            {!!payment.promptpay?.account_name && (
              <Text style={[typography.bodySm, { color: colors.text }]}>ชื่อบัญชี: {payment.promptpay.account_name}</Text>
            )}
            {!!payment.promptpay?.promptpay_id && (
              <Text style={[typography.bodySm, { color: colors.text }]}>พร้อมเพย์: {payment.promptpay.promptpay_id}</Text>
            )}
            {!!payment.ref_no && (
              <Text style={[typography.caption, { color: colors.textMuted }]}>เลขอ้างอิง: {payment.ref_no}</Text>
            )}
            {effective === 'waiting' && left !== null && (
              <Text style={[typography.caption, { color: colors.warning }]}>
                ⏱ QR ใช้ได้อีก {mm}:{String(ss).padStart(2, '0')} นาที
              </Text>
            )}
          </View>

          <Text style={[typography.caption, styles.center, styles.hint, { color: colors.textMuted }]}>
            {hint || 'สแกนด้วยแอปธนาคาร แล้วโอนยอดนี้ให้ตรงทุกสตางค์ ระบบจะยืนยันให้อัตโนมัติ'}
          </Text>
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
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  center: {
    textAlign: 'center',
    alignSelf: 'center',
  },
  qrFrame: {
    alignSelf: 'center',
    width: 244,
    height: 244,
    borderRadius: radii.lg,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
    marginBottom: spacing.md,
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
  },
  row: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  info: {
    marginTop: spacing.md,
    borderRadius: radii.md,
    padding: spacing.md,
    gap: spacing.xxs,
  },
  hint: {
    marginTop: spacing.md,
  },
  paidBox: {
    alignItems: 'center',
    gap: spacing.xs,
    paddingVertical: spacing.lg,
  },
  paidIcon: {
    fontSize: 48,
  },
  errorBox: {
    alignItems: 'center',
    paddingVertical: spacing.md,
  },
  renew: {
    marginTop: spacing.md,
  },
});

export default PromptPayQR;
