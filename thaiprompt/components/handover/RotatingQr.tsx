/**
 * RotatingQr — QR ส่งมอบของแบบหมุนเวียน (ใช้ครั้งเดียว อายุสั้น) + กรอบมุมทอง + ตรา TP ตรงกลาง + นับถอยหลัง
 *
 * - token มาจาก server (qr_token) — แอปแค่วาด ห้ามเก็บลงเครื่อง/ห้ามพิมพ์ลง log
 * - expiresAt = เวลาหมดอายุ (ISO ตามนาฬิกา server) → นับถอยหลัง "รหัสเปลี่ยนใหม่ใน m:ss"
 *   clockOffsetMs (= เวลา server − เวลาเครื่อง จาก server_now) แก้เวลาเครื่องเพี้ยน (FIXES L3 / §A2)
 * - onExpire ถูกเรียกครั้งแรกเมื่อเหลือ ≤ 3 วินาที (ให้หน้าแม่ดึง token ใหม่ก่อนหมดจริง)
 *   ถ้าหมดอายุแล้วยังได้ token เดิม (เน็ตหลุด/ดึงไม่ทัน) → เรียกซ้ำทุก 5 วินาทีจนกว่าจะได้ token ใหม่
 * - หมดอายุแล้วแต่ยังไม่ได้ token ใหม่ → "ยังแสดง QR เดิม" (server ยอมรับรหัสช่วงก่อนหน้า) + ข้อความ
 *   "กำลังขอรหัสใหม่…" — ห้ามซ่อน QR ค้างไว้ (เดิมม่านทับถาวรเมื่อนาฬิกาเครื่องเดินเร็ว ทำให้สแกนไม่ได้เลย)
 * - QR เป็นสีเข้มบนพื้นขาวเสมอทั้งสองโหมด (กล้องอ่านได้แน่นอน)
 *
 * @example
 * <RotatingQr token={h.qr_token} expiresAt={h.qr_expires_at} clockOffsetMs={data.clock_offset_ms} onExpire={reload} caption="ยื่นหน้าจอนี้ให้ไรเดอร์สแกน" />
 */

import React, { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, StyleSheet, View } from 'react-native';
import QRCode from 'react-native-qrcode-svg';
import { Text } from '@/components/ui/Text';
import { Icon } from '@/components/ui/Icon';
import { lastClockOffset, parseIsoMs, serverNowMs } from '@/utils/serverClock';
import { useTheme, LIGHT_THEME, spacing, typography } from '@/theme';

export interface RotatingQrProps {
  token: string | null;
  /** ขนาด QR (ไม่รวมกรอบ) ค่าเริ่มต้น 200 */
  size?: number;
  expiresAt?: string | null;
  /** เรียกเมื่อใกล้หมดอายุ (≤ 3 วินาที) และซ้ำทุก 5 วินาทีถ้าหมดแล้วยังได้ token เดิม */
  onExpire?: () => void;
  /** ข้อความเหนือ QR เช่น "ยื่นหน้าจอนี้ให้ไรเดอร์สแกน" */
  caption?: string;
  /** เวลา server − เวลาเครื่อง (มิลลิวินาที) · ไม่ส่ง = ใช้ค่าล่าสุดที่แอปรู้ */
  clockOffsetMs?: number | null;
}

const TP_MARK = require('@/assets/images/brand/tp-mark.webp');
const EXPIRE_LEAD_MS = 3000;
/** หมดอายุแล้วยังได้ token เดิม → ขอใหม่ซ้ำทุกเท่านี้ */
const EXPIRED_RETRY_MS = 5000;
/** อายุ QR สูงสุดที่ยอมแสดงในตัวนับ (กันข้อมูลเวลาแปลกจนตัวเลขยาวเกิน) */
const MAX_COUNTDOWN_MS = 10 * 60 * 1000;

const formatCountdown = (ms: number): string => {
  const total = Math.max(0, Math.ceil(ms / 1000));
  const m = Math.floor(total / 60);
  const s = total % 60;
  return `${m}:${String(s).padStart(2, '0')}`;
};

export const RotatingQr: React.FC<RotatingQrProps> = ({ token, size = 200, expiresAt, onExpire, caption, clockOffsetMs }) => {
  const { colors } = useTheme();
  const expiresMs = parseIsoMs(expiresAt);
  const offset = typeof clockOffsetMs === 'number' && Number.isFinite(clockOffsetMs) ? clockOffsetMs : lastClockOffset();
  /** "ตอนนี้" ตามนาฬิกา server */
  const [now, setNow] = useState(() => serverNowMs(offset));
  /** token|expiresAt ที่แจ้งหน้าแม่ไปแล้ว + เวลาที่แจ้งล่าสุด (ไว้เรียกซ้ำเมื่อหมดอายุแล้วยังได้ของเดิม) */
  const firedRef = useRef<{ key: string; at: number } | null>(null);
  const onExpireRef = useRef(onExpire);
  onExpireRef.current = onExpire;

  // เดินนาฬิกาทุกวินาทีเฉพาะตอนมีเวลาหมดอายุ
  useEffect(() => {
    if (!expiresMs) return undefined;
    setNow(serverNowMs(offset));
    const timer = setInterval(() => setNow(serverNowMs(offset)), 1000);
    return () => clearInterval(timer);
  }, [expiresMs, token, offset]);

  const remaining = expiresMs ? Math.min(MAX_COUNTDOWN_MS, expiresMs - now) : null;
  const expired = remaining !== null && remaining <= 0;

  // ใกล้หมดอายุ → แจ้งหน้าแม่ (ครั้งแรกต่อ token) · หมดแล้วยังได้ token เดิม → แจ้งซ้ำทุก 5 วินาที
  useEffect(() => {
    if (!token || remaining === null || remaining > EXPIRE_LEAD_MS) return;
    const key = `${token}|${expiresAt ?? ''}`;
    const fired = firedRef.current;
    const at = Date.now();
    if (fired && fired.key === key && (!expired || at - fired.at < EXPIRED_RETRY_MS)) return;
    firedRef.current = { key, at };
    onExpireRef.current?.();
  }, [token, expiresAt, remaining, expired]);

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
        </View>
      </View>

      {remaining !== null && !!token && (
        <View style={styles.countdown} accessibilityLiveRegion="polite">
          {expired ? (
            <>
              {/* หมดเวลาแล้วแต่ยังได้รหัสเดิม → แสดง QR เดิมต่อ (server ยอมรับรหัสช่วงก่อนหน้า) ระหว่างขอรหัสใหม่ */}
              <ActivityIndicator size="small" color={colors.gold} />
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>กำลังขอรหัสใหม่… สแกนรหัสนี้ได้ระหว่างรอ</Text>
            </>
          ) : (
            <>
              <Icon name="clock" size={16} color={colors.textMuted} />
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>รหัสเปลี่ยนใหม่ใน</Text>
              <Text style={[typography.bodyStrong, styles.tabular, { color: colors.textStrong }]}>{formatCountdown(remaining)}</Text>
            </>
          )}
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
