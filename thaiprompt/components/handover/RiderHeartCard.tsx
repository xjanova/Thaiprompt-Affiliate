/**
 * RiderHeartCard — "ประทับใจ{ไรเดอร์}ไหม?" + ปุ่มให้หัวใจ + ความคืบหน้าสู่การล็อกเรียก (ตามแบบ Complete.png)
 *
 * - POST /orders/{source}/{id}/heart (1 ดวงต่อออเดอร์) — กดรัว/กดซ้ำ: ปุ่มล็อกระหว่างรอ + server ตอบ already
 * - สิทธิ์ล็อกเรียก (can_lock) ใช้ค่าจาก server เสมอ · เกณฑ์ขั้นต่ำใช้แสดงข้อความเท่านั้น
 * - HEART_NOT_ALLOWED (เช่น งานยังไม่จบ/ไม่ใช่ผู้ซื้อ) → ซ่อนปุ่ม แสดงข้อความไทย
 */

import React, { useEffect, useRef, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, Icon, resultHaptic } from '@/components/ui';
import { PersonAvatar } from '@/components/people/PersonAvatar';
import type { HandoverSource, PersonCard } from '@/services/api/handoverApi';
import { DEFAULT_LOCK_MIN_HEARTS, giveRiderHeart, heartsToLock } from '@/services/api/riderSocialApi';
import { useTheme, spacing, typography } from '@/theme';

export interface RiderHeartCardProps {
  source: HandoverSource;
  orderId: number;
  rider: PersonCard;
  /** เกณฑ์หัวใจขั้นต่ำ (ค่าเริ่มต้น 11 — ใช้แสดงข้อความ) */
  lockMinHearts?: number;
  style?: object;
}

/** ชื่อเล่นสั้นๆ (คำแรกของชื่อ) ใช้ในประโยค */
const shortName = (name: string): string => name.trim().split(/\s+/)[0] || 'ไรเดอร์';

export const RiderHeartCard: React.FC<RiderHeartCardProps> = ({
  source,
  orderId,
  rider,
  lockMinHearts = DEFAULT_LOCK_MIN_HEARTS,
  style,
}) => {
  const { colors } = useTheme();
  const [heartsFromMe, setHeartsFromMe] = useState<number>(rider.hearts_from_me ?? 0);
  const [canLock, setCanLock] = useState<boolean>(rider.can_lock);
  const [given, setGiven] = useState(false);
  const [blockedMessage, setBlockedMessage] = useState<string | null>(null);
  const [transientError, setTransientError] = useState<string | null>(null);
  const mountedRef = useRef(true);
  const busyRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // ข้อมูลไรเดอร์จาก server อัปเดต (เช่น poll) → ใช้ค่าล่าสุด เว้นแต่เพิ่งกดให้หัวใจไป
  useEffect(() => {
    if (given) return;
    setHeartsFromMe(rider.hearts_from_me ?? 0);
    setCanLock(rider.can_lock);
  }, [rider.hearts_from_me, rider.can_lock, given]);

  const name = shortName(rider.display_name);
  const remaining = heartsToLock(heartsFromMe, lockMinHearts);

  const giveHeart = async () => {
    if (busyRef.current || given) return;
    busyRef.current = true;
    const res = await giveRiderHeart(source, orderId);
    busyRef.current = false;
    if (!mountedRef.current) return;
    if (res.success) {
      resultHaptic('success');
      setTransientError(null);
      setGiven(true);
      setHeartsFromMe(res.data.hearts_from_me);
      setCanLock(res.data.can_lock);
      return;
    }
    resultHaptic('error');
    if (res.code === 'HEART_NOT_ALLOWED') {
      setBlockedMessage(res.message);
      return;
    }
    setBlockedMessage(null);
    // ผิดพลาดชั่วคราว (เน็ต/เซิร์ฟเวอร์) → ให้กดใหม่ได้ แสดงข้อความใต้ปุ่ม
    setTransientError(res.message);
  };

  return (
    <Card3D padding={spacing.xl} radius={22} style={[styles.card, { borderColor: colors.dangerSoft }, style]}>
      <PersonAvatar uri={rider.photo_url} name={rider.display_name} size={72} style={styles.center} />
      <Text style={[typography.h2, styles.centerText, styles.gapTop, { color: colors.textStrong }]}>
        {given ? `ขอบคุณที่ให้หัวใจ${name}` : `ประทับใจ${name}ไหม?`}
      </Text>
      <Text style={[typography.caption, styles.centerText, { color: colors.textMuted }]}>
        ให้หัวใจได้ 1 ดวงต่อออเดอร์ · คุณให้{name}ไปแล้ว {heartsFromMe} ดวง
      </Text>

      {blockedMessage ? (
        <View style={[styles.note, { backgroundColor: colors.inset }]}>
          <Icon name="info" size={16} color={colors.textMuted} />
          <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>{blockedMessage}</Text>
        </View>
      ) : (
        <Button3D
          title={given ? 'ให้หัวใจแล้ว' : `ให้หัวใจ${name}`}
          icon="heart"
          variant="danger"
          size="lg"
          fullWidth
          disabled={given}
          onPress={giveHeart}
          accessibilityLabel={given ? `ให้หัวใจ ${rider.display_name} แล้ว` : `ให้หัวใจ ${rider.display_name}`}
          style={styles.gapTop}
        />
      )}
      {!!transientError && !given && !blockedMessage && (
        <Text style={[typography.caption, styles.centerText, styles.gapTopSm, { color: colors.danger }]}>{transientError}</Text>
      )}

      <View style={[styles.lockRow, styles.gapTop]}>
        <Icon name={canLock ? 'lock' : 'lock-key'} size={15} color={canLock ? colors.success : colors.textMuted} />
        <Text style={[typography.caption, styles.flexShrink, { color: canLock ? colors.success : colors.textMuted }]}>
          {canLock
            ? `ครบ ${lockMinHearts} ดวงแล้ว ล็อกเรียก${name}ได้ทุกครั้งที่สั่ง`
            : `อีก ${remaining} ดวง ล็อกเรียก${name}ได้ทุกครั้งที่สั่ง`}
        </Text>
      </View>
    </Card3D>
  );
};

const styles = StyleSheet.create({
  card: {
    alignItems: 'stretch',
    borderWidth: 1,
  },
  flex: {
    flex: 1,
  },
  flexShrink: {
    flexShrink: 1,
  },
  center: {
    alignSelf: 'center',
  },
  centerText: {
    textAlign: 'center',
  },
  gapTop: {
    marginTop: spacing.md,
  },
  gapTopSm: {
    marginTop: spacing.xs,
  },
  note: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: 14,
    padding: spacing.md,
    marginTop: spacing.md,
  },
  lockRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
  },
});

export default RiderHeartCard;
