/**
 * JobCompleteCelebration — ฉลอง "รับทรัพย์!" ตอนส่งงานสำเร็จ
 *
 * - หัวน้ำเงินกรมท่าลายกนก + ภาพสกู๊ตเตอร์ 3D ประจำแบรนด์ + แสงทองเรือง + วงแหวนทองกระจายออก
 * - ประกายทอง (ไอคอนเส้น ไม่ใช่อีโมจิ) พุ่งออกเป็นพัด + ตัวเลขรายได้นับขึ้น + สั่นแจ้งสำเร็จ
 * - ตัวเลขมาจาก server เท่านั้น (earnings.rider_earnings จาก POST /deliver)
 */

import React, { useEffect, useRef, useState } from 'react';
import { Modal, Pressable, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import Animated, {
  FadeInUp,
  ZoomIn,
  cancelAnimation,
  useAnimatedStyle,
  useSharedValue,
  withDelay,
  withRepeat,
  withTiming,
  Easing,
} from 'react-native-reanimated';
import { useTheme, spacing, radii, typography, shadowStyle, withAlpha } from '@/theme';
import {
  BrandArt,
  Button3D,
  Icon,
  RoyalHeader,
  formatBaht,
  resultHaptic,
  type IconName,
} from '@/components/ui';

/** ประกายทองที่พุ่งออกรอบสกู๊ตเตอร์ (ไอคอน + ขนาด) */
const SPARKS: Array<{ icon: IconName; size: number }> = [
  { icon: 'sparkle', size: 18 },
  { icon: 'star', size: 11 },
  { icon: 'coins', size: 16 },
  { icon: 'sparkle', size: 13 },
  { icon: 'star', size: 9 },
  { icon: 'sparkle', size: 20 },
  { icon: 'coins', size: 14 },
  { icon: 'star', size: 12 },
  { icon: 'sparkle', size: 12 },
  { icon: 'star', size: 10 },
];

const ART_SIZE = 128;
const GLOW_SIZE = 138;

const Spark: React.FC<{ index: number; icon: IconName; size: number; color: string }> = ({ index, icon, size, color }) => {
  const progress = useSharedValue(0);
  // กระจายเป็นพัดรอบๆ ด้านบน (คงที่ต่อประกาย — ไม่สุ่มใหม่ทุก render)
  const angle = (-168 + (index / (SPARKS.length - 1)) * 156) * (Math.PI / 180);
  const distance = 92 + (index % 3) * 26;
  const dx = Math.cos(angle) * distance;
  const dy = Math.sin(angle) * distance;

  useEffect(() => {
    progress.value = withDelay(
      160 + index * 55,
      withTiming(1, { duration: 1300, easing: Easing.out(Easing.cubic) })
    );
    return () => cancelAnimation(progress);
  }, [index, progress]);

  const style = useAnimatedStyle(() => ({
    opacity: progress.value < 0.7 ? Math.min(1, progress.value * 4) : Math.max(0, (1 - progress.value) * 3.3),
    transform: [
      { translateX: dx * progress.value },
      { translateY: dy * progress.value },
      { scale: 0.4 + progress.value * 0.8 },
      { rotate: `${progress.value * (index % 2 === 0 ? 50 : -50)}deg` },
    ],
  }));

  return (
    <Animated.View pointerEvents="none" style={[styles.spark, style]}>
      <Icon name={icon} size={size} color={color} weight="fill" />
    </Animated.View>
  );
};

/** ตัวเลขนับขึ้นจาก 0 → amount */
const useCountUp = (target: number, active: boolean, durationMs = 900): number => {
  const [value, setValue] = useState(0);
  const frameRef = useRef<number | null>(null);

  useEffect(() => {
    if (!active) {
      setValue(0);
      return;
    }
    const start = Date.now();
    const tick = () => {
      const t = Math.min(1, (Date.now() - start) / durationMs);
      const eased = 1 - Math.pow(1 - t, 3);
      setValue(target * eased);
      if (t < 1) {
        frameRef.current = requestAnimationFrame(tick);
      }
    };
    frameRef.current = requestAnimationFrame(tick);
    return () => {
      if (frameRef.current !== null) cancelAnimationFrame(frameRef.current);
    };
  }, [target, active, durationMs]);

  return value;
};

/** วงแหวนทองกระจายออกจากสกู๊ตเตอร์ (วนเบาๆ ระหว่างเปิดหน้าฉลอง) */
const PulseRing: React.FC<{ color: string; delay: number }> = ({ color, delay }) => {
  const pulse = useSharedValue(0);

  useEffect(() => {
    pulse.value = withDelay(delay, withRepeat(withTiming(1, { duration: 2200, easing: Easing.out(Easing.quad) }), -1, false));
    return () => cancelAnimation(pulse);
  }, [delay, pulse]);

  const style = useAnimatedStyle(() => ({
    opacity: 0.55 * (1 - pulse.value),
    transform: [{ scale: 0.8 + pulse.value * 0.7 }],
  }));

  return <Animated.View pointerEvents="none" style={[styles.ring, { borderColor: color }, style]} />;
};

export interface JobCompleteCelebrationProps {
  visible: boolean;
  /** รายได้จากงานนี้ (บาท) */
  amount: number;
  /** ยอดกระเป๋าหลังรับรายได้ (ถ้ามี) */
  walletBalance?: number | null;
  /** โอนเข้ากระเป๋าแล้วหรือยัง */
  settled?: boolean;
  /** ข้อความจาก server (ภาษาไทย) */
  message?: string;
  jobNumber?: string;
  onNextJob: () => void;
  onViewEarnings: () => void;
  onClose: () => void;
}

export const JobCompleteCelebration: React.FC<JobCompleteCelebrationProps> = ({
  visible,
  amount,
  walletBalance,
  settled = true,
  message,
  jobNumber,
  onNextJob,
  onViewEarnings,
  onClose,
}) => {
  const { colors, isDark } = useTheme();
  const shown = useCountUp(Math.max(0, amount), visible);

  useEffect(() => {
    if (visible) resultHaptic('success');
  }, [visible]);

  if (!visible) return null;

  return (
    <Modal visible transparent animationType="fade" statusBarTranslucent onRequestClose={onClose}>
      <View style={[styles.root, { backgroundColor: colors.overlay }]}>
        <Pressable style={StyleSheet.absoluteFill} onPress={onClose} accessibilityRole="button" accessibilityLabel="ปิด" />

        <Animated.View
          entering={ZoomIn.springify().damping(14)}
          style={[styles.cardShadow, shadowStyle('lg', colors.shadowDark)]}
          accessibilityViewIsModal
        >
          <View
            style={[
              styles.card,
              { backgroundColor: colors.card },
              isDark && { borderWidth: 1, borderColor: colors.border },
            ]}
          >
            {/* ---------- หัวน้ำเงิน + สกู๊ตเตอร์เรืองทอง ---------- */}
            <RoyalHeader ornamentWidth={210} ornamentTop={-58} style={styles.hero}>
              <View style={styles.stage}>
                <View
                  pointerEvents="none"
                  style={[
                    styles.glow,
                    {
                      backgroundColor: withAlpha(colors.gold, 0.2),
                      boxShadow: `0px 0px 48px 18px ${withAlpha(colors.gold, 0.42)}`,
                    },
                  ]}
                />
                <PulseRing color={colors.goldLight} delay={250} />
                <PulseRing color={colors.goldLight} delay={1350} />
                <View style={styles.burst} pointerEvents="none">
                  {SPARKS.map((spark, index) => (
                    <Spark
                      key={`${index}-${spark.icon}`}
                      index={index}
                      icon={spark.icon}
                      size={spark.size}
                      color={index % 3 === 0 ? colors.goldLight : colors.gold}
                    />
                  ))}
                </View>
                <Animated.View entering={ZoomIn.delay(90).springify().damping(11)}>
                  <BrandArt name="scooter" size={ART_SIZE} />
                </Animated.View>
              </View>

              <Text style={[typography.serifLg, styles.center, { color: colors.goldLight }]}>รับทรัพย์!</Text>
              {!!jobNumber && (
                <View style={styles.jobRow}>
                  <Icon name="check-circle" size={15} color={colors.success} weight="fill" />
                  <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>งาน {jobNumber} ส่งสำเร็จ</Text>
                </View>
              )}
            </RoyalHeader>

            {/* ---------- ยอดที่ได้ ---------- */}
            <View style={styles.body}>
              <Text style={[typography.overline, styles.center, { color: colors.textMuted }]}>ค่าส่งที่คุณได้รับ</Text>
              <Text
                accessibilityLabel={`ได้รับ ${formatBaht(amount)}`}
                style={[styles.amount, { color: colors.goldDeep }]}
                numberOfLines={1}
                adjustsFontSizeToFit
              >
                +{formatBaht(shown, { decimals: amount % 1 === 0 ? 0 : 2 })}
              </Text>

              <Animated.View entering={FadeInUp.delay(700)} style={styles.info}>
                <Text style={[typography.bodySm, styles.center, { color: colors.text }]}>
                  {message || (settled ? 'รายได้เข้ากระเป๋าเงินของคุณแล้ว' : 'กำลังโอนรายได้เข้ากระเป๋าเงิน')}
                </Text>
                {typeof walletBalance === 'number' && Number.isFinite(walletBalance) && (
                  <View style={[styles.balance, { backgroundColor: colors.goldSoft }]}>
                    <View style={[styles.balanceIcon, { backgroundColor: colors.card }]}>
                      <Icon name="wallet" size={18} color={colors.goldDeep} weight="fill" />
                    </View>
                    <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>ยอดในกระเป๋าตอนนี้</Text>
                    <Text style={[typography.h3, { color: colors.textStrong }]}>{formatBaht(walletBalance)}</Text>
                  </View>
                )}
              </Animated.View>

              <View style={styles.buttons}>
                <Button3D title="รับงานต่อ" icon="moped" size="lg" fullWidth onPress={onNextJob} />
                <Button3D title="ดูรายได้ของฉัน" icon="chart-bar" variant="secondary" fullWidth onPress={onViewEarnings} />
                <Button3D title="ปิด" variant="ghost" size="sm" onPress={onClose} />
              </View>
            </View>
          </View>
        </Animated.View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  root: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.xl,
  },
  cardShadow: {
    width: '100%',
    maxWidth: 380,
    borderRadius: radii.xxl,
  },
  card: {
    borderRadius: radii.xxl,
    overflow: 'hidden',
  },
  hero: {
    alignItems: 'center',
    paddingTop: spacing.xl,
    paddingBottom: spacing.xl,
    gap: spacing.xs,
  },
  stage: {
    width: 220,
    height: ART_SIZE + 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  glow: {
    position: 'absolute',
    width: GLOW_SIZE,
    height: GLOW_SIZE,
    borderRadius: GLOW_SIZE / 2,
  },
  ring: {
    position: 'absolute',
    width: GLOW_SIZE,
    height: GLOW_SIZE,
    borderRadius: GLOW_SIZE / 2,
    borderWidth: 1.5,
  },
  burst: {
    position: 'absolute',
    top: ART_SIZE / 2,
    left: 0,
    right: 0,
    alignItems: 'center',
  },
  spark: {
    position: 'absolute',
  },
  center: {
    textAlign: 'center',
  },
  jobRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  body: {
    padding: spacing.xl,
    paddingTop: spacing.lg,
    gap: spacing.xs,
  },
  amount: {
    fontSize: 46,
    lineHeight: 56,
    fontWeight: '800',
    textAlign: 'center',
    fontVariant: ['tabular-nums'],
    letterSpacing: -0.6,
  },
  info: {
    gap: spacing.md,
    marginTop: spacing.xs,
  },
  balance: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.lg,
    paddingVertical: spacing.sm,
    paddingHorizontal: spacing.md,
  },
  balanceIcon: {
    width: 34,
    height: 34,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  flex: {
    flex: 1,
  },
  buttons: {
    marginTop: spacing.lg,
    gap: spacing.sm,
    alignItems: 'center',
  },
});

export default JobCompleteCelebration;
