/**
 * RotatingQr — QR ส่งมอบของแบบหมุนเวียน (ใช้ครั้งเดียว อายุสั้น) + กรอบมุมทอง + ตรา TP ตรงกลาง + นับถอยหลัง
 *
 * - token มาจาก server (qr_token) — แอปแค่วาด ห้ามเก็บลงเครื่อง/ห้ามพิมพ์ลง log
 * - expiresAt = เวลาหมดอายุ (ISO) → นับถอยหลัง "รหัสเปลี่ยนใหม่ใน m:ss"
 * - onExpire ถูกเรียกครั้งเดียวต่อ token เมื่อเหลือ ≤ 3 วินาที (ให้หน้าแม่ดึง token ใหม่ก่อนหมดจริง)
 * - หมดอายุแล้วแต่ยังไม่ได้ token ใหม่ → ม่านจางทับ QR + "กำลังสร้างรหัสใหม่…" (กันอีกฝ่ายสแกนของเก่า)
 * - QR เป็นสีเข้มบนพื้นขาวเสมอทั้งสองโหมด (กล้องอ่านได้แน่นอน)
 *
 * @example
 * <RotatingQr token={h.qr_token} expiresAt={h.qr_expires_at} onExpire={reload} caption="ยื่นหน้าจอนี้ให้ไรเดอร์สแกน" />
 */

import React, { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, StyleSheet, View } from 'react-native';
import QRCode from 'react-native-qrcode-svg';
import { Text } from '@/components/ui/Text';
import { Icon } from '@/components/ui/Icon';
import { useTheme, LIGHT_THEME, spacing, typography, withAlpha } from '@/theme';

export interface RotatingQrProps {
  token: string | null;
  /** ขนาด QR (ไม่รวมกรอบ) ค่าเริ่มต้น 200 */
  size?: number;
  expiresAt?: string | null;
  /** เรียกเมื่อใกล้หมดอายุ (≤ 3 วินาที) ครั้งเดียวต่อ token */
  onExpire?: () => void;
  /** ข้อความเหนือ QR เช่น "ยื่นหน้าจอนี้ให้ไรเดอร์สแกน" */
  caption?: string;
}

const TP_MARK = require('@/assets/images/brand/tp-mark.webp');
const EXPIRE_LEAD_MS = 3000;
/** อายุ QR สูงสุดที่ยอมแสดงในตัวนับ (กันนาฬิกาเครื่องเพี้ยนจนตัวเลขแปลก) */
const MAX_COUNTDOWN_MS = 10 * 60 * 1000;

const parseTime = (iso: string | null | undefined): number | null => {
  if (!iso) return null;
  const t = new Date(iso).getTime();
  return Number.isFinite(t) ? t : null;
};

const formatCountdown = (ms: number): string => {
  const total = Math.max(0, Math.ceil(ms / 1000));
  const m = Math.floor(total / 60);
  const s = total % 60;
  return `${m}:${String(s).padStart(2, '0')}`;
};

export const RotatingQr: React.FC<RotatingQrProps> = ({ token, size = 200, expiresAt, onExpire, caption }) => {
  const { colors } = useTheme();
  const expiresMs = parseTime(expiresAt);
  const [now, setNow] = useState(() => Date.now());
  const firedForRef = useRef<string | null>(null);
  const onExpireRef = useRef(onExpire);
  onExpireRef.current = onExpire;

  // เดินนาฬิกาทุกวินาทีเฉพาะตอนมีเวลาหมดอายุ
  useEffect(() => {
    if (!expiresMs) return undefined;
    setNow(Date.now());
    const timer = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(timer);
  }, [expiresMs, token]);

  const remaining = expiresMs ? Math.min(MAX_COUNTDOWN_MS, expiresMs - now) : null;
  const expired = remaining !== null && remaining <= 0;

  // ใกล้หมดอายุ → แจ้งหน้าแม่ครั้งเดียวต่อ token
  useEffect(() => {
    if (!token || remaining === null || remaining > EXPIRE_LEAD_MS) return;
    const key = `${token}|${expiresAt ?? ''}`;
    if (firedForRef.current === key) return;
    firedForRef.current = key;
    onExpireRef.current?.();
  }, [token, expiresAt, remaining]);

  const frame = size + 36;
  const corner = Math.round(size * 0.16);
  const cornerWidth = 4;
  const qrInk = LIGHT_THEME.colors.navyFill;
  const qrPaper = LIGHT_THEME.colors.card;

  return (
    <View style={styles.root}>
      {!!caption && <Text style={[typography.bodySm, styles.caption, { color: colors.textMuted }]}>{caption}</Text>}

      <View style={{ width: frame, height: frame }} accessible accessibilityRole="image" accessibilityLabel={caption || 'QR สำหรับให้อีกฝ่ายสแกน'}>
        {/* กรอบมุมทอง 4 มุม */}
        {(['tl', 'tr', 'bl', 'br'] as const).map((pos) => (
          <View
            key={pos}
            pointerEvents="none"
            style={[
              styles.corner,
              {
                width: corner,
                height: corner,
                borderColor: colors.gold,
                borderTopWidth: pos[0] === 't' ? cornerWidth : 0,
                borderBottomWidth: pos[0] === 'b' ? cornerWidth : 0,
                borderLeftWidth: pos[1] === 'l' ? cornerWidth : 0,
                borderRightWidth: pos[1] === 'r' ? cornerWidth : 0,
                borderTopLeftRadius: pos === 'tl' ? 14 : 0,
                borderTopRightRadius: pos === 'tr' ? 14 : 0,
                borderBottomLeftRadius: pos === 'bl' ? 14 : 0,
                borderBottomRightRadius: pos === 'br' ? 14 : 0,
              },
              pos[0] === 't' ? styles.top : styles.bottom,
              pos[1] === 'l' ? styles.left : styles.right,
            ]}
          />
        ))}

        <View style={[styles.qrBox, { width: size + 16, height: size + 16, backgroundColor: qrPaper }]}>
          {token ? (
            <QRCode
              value={token}
              size={size}
              color={qrInk}
              backgroundColor={qrPaper}
              ecl="H"
              logo={TP_MARK}
              logoSize={Math.round(size * 0.22)}
              logoBackgroundColor={qrInk}
              logoMargin={4}
              logoBorderRadius={10}
            />
          ) : (
            <View style={[styles.placeholder, { width: size, height: size }]}>
              <ActivityIndicator color={colors.gold} />
              <Text style={[typography.caption, { color: LIGHT_THEME.colors.textMuted }]}>กำลังสร้างรหัส…</Text>
            </View>
          )}
          {!!token && expired && (
            <View style={[StyleSheet.absoluteFill, styles.veil, { backgroundColor: withAlpha(LIGHT_THEME.colors.card, 0.92) }]}>
              <ActivityIndicator color={colors.gold} />
              <Text style={[typography.caption, { color: LIGHT_THEME.colors.textStrong }]}>กำลังสร้างรหัสใหม่…</Text>
            </View>
          )}
        </View>
      </View>

      {remaining !== null && !!token && (
        <View style={styles.countdown} accessibilityLiveRegion="polite">
          <Icon name="clock" size={16} color={colors.textMuted} />
          <Text style={[typography.bodySm, { color: colors.textMuted }]}>รหัสเปลี่ยนใหม่ใน</Text>
          <Text style={[typography.bodyStrong, styles.tabular, { color: colors.textStrong }]}>{formatCountdown(remaining)}</Text>
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  root: {
    alignItems: 'center',
  },
  caption: {
    textAlign: 'center',
    marginBottom: spacing.md,
  },
  corner: {
    position: 'absolute',
  },
  top: {
    top: 0,
  },
  bottom: {
    bottom: 0,
  },
  left: {
    left: 0,
  },
  right: {
    right: 0,
  },
  qrBox: {
    position: 'absolute',
    top: 10,
    left: 10,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 12,
    overflow: 'hidden',
  },
  placeholder: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
  },
  veil: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
  },
  countdown: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: spacing.md,
  },
  tabular: {
    fontVariant: ['tabular-nums'],
  },
});

export default RotatingQr;
