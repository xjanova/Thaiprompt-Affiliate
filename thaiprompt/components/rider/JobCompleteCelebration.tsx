/**
 * JobCompleteCelebration — ฉลอง "รับทรัพย์!" ตอนส่งงานสำเร็จ
 *
 * - เหรียญ/ประกายพุ่งขึ้น + ตัวเลขรายได้นับขึ้น + สั่นแจ้งสำเร็จ
 * - ตัวเลขมาจาก server เท่านั้น (earnings.rider_earnings จาก POST /deliver)
 */

import React, { useEffect, useRef, useState } from 'react';
import { Modal, Pressable, StyleSheet, Text, View } from 'react-native';
import Animated, {
  FadeInUp,
  ZoomIn,
  useAnimatedStyle,
  useSharedValue,
  withDelay,
  withTiming,
  Easing,
} from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { useTheme, spacing, radii, typography, shadowStyle } from '@/theme';
import { Button3D, formatBaht, resultHaptic } from '@/components/ui';

const PARTICLES = ['💰', '✨', '🪙', '⭐', '💰', '✨', '🪙', '💛', '✨', '💰', '🪙', '⭐'];

const Particle: React.FC<{ index: number; char: string }> = ({ index, char }) => {
  const progress = useSharedValue(0);
  // กระจายเป็นพัดรอบๆ ด้านบน (คงที่ต่ออนุภาค — ไม่สุ่มใหม่ทุก render)
  const angle = (-160 + (index / (PARTICLES.length - 1)) * 140) * (Math.PI / 180);
  const distance = 110 + (index % 3) * 35;
  const dx = Math.cos(angle) * distance;
  const dy = Math.sin(angle) * distance;

  useEffect(() => {
    progress.value = withDelay(
      120 + index * 45,
      withTiming(1, { duration: 1100, easing: Easing.out(Easing.cubic) })
    );
  }, [index, progress]);

  const style = useAnimatedStyle(() => ({
    opacity: progress.value < 0.75 ? Math.min(1, progress.value * 4) : Math.max(0, (1 - progress.value) * 4),
    transform: [
      { translateX: dx * progress.value },
      { translateY: dy * progress.value },
      { scale: 0.5 + progress.value * 0.8 },
      { rotate: `${progress.value * (index % 2 === 0 ? 40 : -40)}deg` },
    ],
  }));

  return (
    <Animated.Text pointerEvents="none" style={[styles.particle, style]}>
      {char}
    </Animated.Text>
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
  const { colors, gradients } = useTheme();
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
          style={[styles.card, { backgroundColor: colors.card }, shadowStyle('lg', colors.amber)]}
          accessibilityViewIsModal
        >
          <LinearGradient colors={gradients.hero} style={styles.hero}>
            <View style={styles.burst} pointerEvents="none">
              {PARTICLES.map((char, index) => (
                <Particle key={`${index}-${char}`} index={index} char={char} />
              ))}
            </View>
            <Animated.Text entering={ZoomIn.delay(80).springify().damping(10)} style={styles.bigEmoji}>
              🎉
            </Animated.Text>
            <Text style={[typography.h1, styles.center, { color: colors.textStrong }]}>รับทรัพย์!</Text>
            {!!jobNumber && (
              <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>งาน {jobNumber} ส่งสำเร็จ</Text>
            )}
          </LinearGradient>

          <View style={styles.body}>
            <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>ค่าส่งที่คุณได้รับ</Text>
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
                <View style={[styles.balance, { backgroundColor: colors.goldSoft, borderColor: colors.border }]}>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>ยอดในกระเป๋าตอนนี้</Text>
                  <Text style={[typography.h3, { color: colors.textStrong }]}>{formatBaht(walletBalance)}</Text>
                </View>
              )}
            </Animated.View>

            <View style={styles.buttons}>
              <Button3D title="รับงานต่อ" icon="🛵" size="lg" fullWidth onPress={onNextJob} />
              <Button3D title="ดูรายได้ของฉัน" icon="📊" variant="secondary" fullWidth onPress={onViewEarnings} />
              <Button3D title="ปิด" variant="ghost" size="sm" onPress={onClose} />
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
  card: {
    width: '100%',
    maxWidth: 380,
    borderRadius: radii.xxl,
    overflow: 'hidden',
  },
  hero: {
    alignItems: 'center',
    paddingTop: spacing.xxl,
    paddingBottom: spacing.lg,
    gap: spacing.xs,
  },
  burst: {
    position: 'absolute',
    top: 70,
    left: 0,
    right: 0,
    alignItems: 'center',
  },
  particle: {
    position: 'absolute',
    fontSize: 26,
  },
  bigEmoji: {
    fontSize: 56,
    lineHeight: 68,
  },
  center: {
    textAlign: 'center',
  },
  body: {
    padding: spacing.xl,
    gap: spacing.sm,
  },
  amount: {
    fontSize: 44,
    lineHeight: 54,
    fontWeight: '800',
    textAlign: 'center',
  },
  info: {
    gap: spacing.md,
  },
  balance: {
    borderRadius: radii.lg,
    borderWidth: 1,
    paddingVertical: spacing.sm,
    paddingHorizontal: spacing.lg,
    alignItems: 'center',
  },
  buttons: {
    marginTop: spacing.md,
    gap: spacing.sm,
    alignItems: 'center',
  },
});

export default JobCompleteCelebration;
