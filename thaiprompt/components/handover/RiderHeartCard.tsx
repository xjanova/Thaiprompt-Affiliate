/**
 * RiderHeartCard — "ประทับใจ{ไรเดอร์}ไหม?" + ปุ่มให้หัวใจ + ความคืบหน้าสู่การล็อกเรียก (ตามแบบ Complete.png)
 *
 * - POST /orders/{source}/{id}/heart (1 ดวงต่อออเดอร์) — กดรัว/กดซ้ำ: ปุ่มล็อกระหว่างรอ + server ตอบ already
 * - จำว่าออเดอร์นี้ให้หัวใจแล้ว (heartMemory) → กลับมาเปิดหน้าใหม่ไม่ชวนให้ซ้ำ (M6)
 *   ระหว่างอ่านความจำจากเครื่อง (ครั้งแรกหลังเปิดแอป) แสดงโครงว่าง ไม่โชว์ปุ่มแล้วกระพริบเป็น "ให้แล้ว" (B11)
 *   server ตอบ already = ถือว่าให้แล้ว (ไม่นับเพิ่ม) แสดงสถานะ "ให้หัวใจแล้ว"
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
import { isHeartMemoryLoaded, peekHeartGiven, readHeartGiven, rememberHeartGiven } from './heartMemory';

/** รอผลอ่านจากเครื่องไม่เกินเท่านี้ ก่อนแสดงปุ่มตามปกติ */
const MEMORY_WAIT_MS = 1500;

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
  /** จำได้แล้ว (หน่วยความจำ) ว่าออเดอร์นี้ให้หัวใจไปแล้ว → ไม่ชวนซ้ำตั้งแต่เฟรมแรก */
  const remembered = peekHeartGiven(source, orderId);
  const [heartsFromMe, setHeartsFromMe] = useState<number>(
    Math.max(rider.hearts_from_me ?? 0, remembered?.hearts_from_me ?? 0)
  );
  const [canLock, setCanLock] = useState<boolean>(rider.can_lock || !!remembered?.can_lock);
  const [given, setGiven] = useState(!!remembered);
  /** given มาจากการกดในหน้านี้ (ตัวเลขจากคำตอบ server สดกว่า PersonCard) */
  const [justGiven, setJustGiven] = useState(false);
  const [busy, setBusy] = useState(false);
  const [blockedMessage, setBlockedMessage] = useState<string | null>(null);
  const [transientError, setTransientError] = useState<string | null>(null);
  /**
   * รู้แล้วว่าออเดอร์นี้เคยให้หัวใจหรือยัง — ก่อนรู้แสดงโครงว่าง (ไม่โชว์ปุ่ม "ให้หัวใจ" แล้วกระพริบเป็น "ให้แล้ว" — B11)
   * หน่วยความจำโหลดแล้ว = รู้ตั้งแต่เฟรมแรก · ยังไม่โหลด = รอ AsyncStorage (หรือหมดเวลารอ)
   */
  const [memoryReady, setMemoryReady] = useState<boolean>(() => !!remembered || isHeartMemoryLoaded());
  const mountedRef = useRef(true);
  const busyRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // จำจากเครื่อง (เปิดแอปใหม่) — ให้หัวใจออเดอร์นี้ไปแล้ว → แสดง "ให้หัวใจแล้ว"
  useEffect(() => {
    let alive = true;
    // อ่านเครื่องค้างนานผิดปกติ → เลิกรอ แสดงปุ่มตามปกติ (แย่สุด server ตอบ already)
    const fallback = setTimeout(() => {
      if (alive && mountedRef.current) setMemoryReady(true);
    }, MEMORY_WAIT_MS);
    readHeartGiven(source, orderId)
      .then((entry) => {
        if (!alive || !mountedRef.current) return;
        if (entry) {
          setGiven(true);
          setHeartsFromMe((prev) => Math.max(prev, entry.hearts_from_me ?? 0));
          setCanLock((prev) => prev || !!entry.can_lock);
        }
      })
      .catch(() => {})
      .finally(() => {
        clearTimeout(fallback);
        if (alive && mountedRef.current) setMemoryReady(true);
      });
    return () => {
      alive = false;
      clearTimeout(fallback);
    };
  }, [source, orderId]);

  // ข้อมูลไรเดอร์จาก server อัปเดต (เช่น poll) → ใช้ค่าล่าสุด เว้นแต่เพิ่งกดให้หัวใจไปในหน้านี้
  useEffect(() => {
    if (justGiven) return;
    setHeartsFromMe((prev) => (given ? Math.max(prev, rider.hearts_from_me ?? 0) : rider.hearts_from_me ?? 0));
    setCanLock((prev) => (given ? prev || rider.can_lock : rider.can_lock));
  }, [rider.hearts_from_me, rider.can_lock, given, justGiven]);

  const name = shortName(rider.display_name);
  const remaining = heartsToLock(heartsFromMe, lockMinHearts);

  const giveHeart = async () => {
    if (busyRef.current || given) return;
    busyRef.current = true;
    setBusy(true);
    const res = await giveRiderHeart(source, orderId);
    busyRef.current = false;
    if (!mountedRef.current) return;
    setBusy(false);
    if (res.success) {
      // already = ให้ไปแล้วก่อนหน้า (ไม่นับเพิ่ม) — แสดงเป็น "ให้หัวใจแล้ว" เหมือนกัน ไม่ชวนกดอีก
      resultHaptic(res.data.already ? 'warning' : 'success');
      setTransientError(null);
      setGiven(true);
      setJustGiven(true);
      setHeartsFromMe(res.data.hearts_from_me);
      setCanLock(res.data.can_lock);
      rememberHeartGiven(source, orderId, { hearts_from_me: res.data.hearts_from_me, can_lock: res.data.can_lock }).catch(() => {});
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

  // ยังไม่รู้ว่าเคยให้หัวใจหรือยัง → โครงว่างขนาดเท่าของจริง (ไม่กระพริบปุ่ม "ให้หัวใจ" — B11)
  if (!memoryReady) {
    return (
      <Card3D padding={spacing.xl} radius={22} style={[styles.card, { borderColor: colors.dangerSoft }, style]}>
        <PersonAvatar uri={rider.photo_url} name={rider.display_name} size={72} verified={rider.verified} style={styles.center} />
        <View accessible accessibilityLabel={`กำลังโหลดข้อมูลหัวใจของ ${rider.display_name}`}>
          <View style={[styles.skelTitle, styles.gapTop, { backgroundColor: colors.inset }]} />
          <View style={[styles.skelCaption, { backgroundColor: colors.inset }]} />
          <View style={[styles.skelButton, styles.gapTop, { backgroundColor: colors.inset, borderColor: colors.border }]} />
          <View style={[styles.skelCaption, styles.gapTop, { backgroundColor: colors.inset }]} />
        </View>
      </Card3D>
    );
  }

  return (
    <Card3D padding={spacing.xl} radius={22} style={[styles.card, { borderColor: colors.dangerSoft }, style]}>
      <PersonAvatar uri={rider.photo_url} name={rider.display_name} size={72} verified={rider.verified} style={styles.center} />
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
          disabled={given || busy}
          loading={busy}
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
  skelTitle: {
    alignSelf: 'center',
    width: '62%',
    height: 24,
    borderRadius: 8,
  },
  skelCaption: {
    alignSelf: 'center',
    width: '78%',
    height: 14,
    borderRadius: 7,
    marginTop: spacing.sm,
  },
  skelButton: {
    height: 56,
    borderRadius: 18,
    borderWidth: 1,
  },
});

export default RiderHeartCard;
