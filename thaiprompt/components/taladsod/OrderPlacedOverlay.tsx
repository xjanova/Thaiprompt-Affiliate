/**
 * OrderPlacedOverlay — ฉลองสั่งซื้อสำเร็จ (วงทองเด้ง + เครื่องหมายถูก + ของกระจาย + สั่นแจ้งสำเร็จ)
 *
 * แสดงครั้งเดียวตอนเข้าหน้าออเดอร์หลังสั่งเสร็จ · ปิดเองใน ~2.6 วินาที หรือแตะเพื่อปิด
 */

import React, { useEffect, useRef } from 'react';
import { Modal, Pressable, StyleSheet, Text, View, useWindowDimensions } from 'react-native';
import Animated, {
  Easing,
  FadeIn,
  FadeInDown,
  useAnimatedStyle,
  useSharedValue,
  withDelay,
  withRepeat,
  withSequence,
  withSpring,
  withTiming,
} from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { resultHaptic } from '@/components/ui';
import { useTheme, spacing, typography, shadowStyle } from '@/theme';

const CONFETTI = ['🌿', '🍛', '✨', '🌶️', '🍳', '💛', '✨', '🥢', '🌿', '💛'];
const AUTO_CLOSE_MS = 2600;

const Confetti: React.FC<{ index: number; width: number }> = ({ index, width }) => {
  const fall = useSharedValue(0);
  const spin = useSharedValue(0);
  const startX = ((index + 0.5) / CONFETTI.length) * width - width / 2 + (index % 2 === 0 ? 14 : -14);

  useEffect(() => {
    fall.value = withDelay(index * 60, withTiming(1, { duration: 1500 + (index % 3) * 250, easing: Easing.out(Easing.quad) }));
    spin.value = withRepeat(withTiming(1, { duration: 900 + index * 40 }), -1, false);
  }, [fall, spin, index]);

  const style = useAnimatedStyle(() => ({
    opacity: 1 - fall.value * 0.85,
    transform: [
      { translateX: startX * (0.4 + fall.value * 0.6) },
      { translateY: -40 + fall.value * 260 },
      { rotate: `${spin.value * (index % 2 === 0 ? 360 : -360)}deg` },
    ],
  }));

  return <Animated.Text style={[styles.confetti, style]}>{CONFETTI[index]}</Animated.Text>;
};

export const OrderPlacedOverlay: React.FC<{
  visible: boolean;
  onClose: () => void;
  title?: string;
  message?: string;
}> = ({ visible, onClose, title = 'สั่งเรียบร้อยแล้ว!', message = 'ร้านได้รับออเดอร์แล้ว รอสักครู่นะ' }) => {
  const { colors, gradients } = useTheme();
  const { width } = useWindowDimensions();
  const pop = useSharedValue(0);
  const check = useSharedValue(0);
  const closeRef = useRef(onClose);
  closeRef.current = onClose;

  useEffect(() => {
    if (!visible) return undefined;
    resultHaptic('success');
    pop.value = 0;
    check.value = 0;
    pop.value = withSpring(1, { damping: 9, stiffness: 180 });
    check.value = withDelay(220, withSequence(withTiming(1.25, { duration: 180 }), withSpring(1, { damping: 7 })));
    const timer = setTimeout(() => closeRef.current(), AUTO_CLOSE_MS);
    return () => clearTimeout(timer);
  }, [visible, pop, check]);

  const circleStyle = useAnimatedStyle(() => ({ transform: [{ scale: pop.value }] }));
  const checkStyle = useAnimatedStyle(() => ({ transform: [{ scale: check.value }], opacity: Math.min(1, check.value) }));

  if (!visible) return null;

  return (
    <Modal visible transparent animationType="fade" statusBarTranslucent onRequestClose={onClose}>
      <Pressable
        style={[styles.root, { backgroundColor: colors.overlay }]}
        onPress={onClose}
        accessibilityRole="button"
        accessibilityLabel={`${title} ${message} แตะเพื่อปิด`}
      >
        <View style={styles.confettiLayer} pointerEvents="none">
          {CONFETTI.map((_, i) => (
            <Confetti key={i} index={i} width={width} />
          ))}
        </View>

        <Animated.View style={[styles.circleWrap, circleStyle, shadowStyle('lg', colors.amber)]}>
          <LinearGradient colors={gradients.gold} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.circle}>
            <Animated.Text style={[styles.check, { color: colors.textOnGold }, checkStyle]}>✓</Animated.Text>
          </LinearGradient>
        </Animated.View>

        <Animated.View entering={FadeInDown.delay(260).springify().damping(16)} style={styles.texts}>
          <Text style={[typography.display, styles.center, { color: colors.textOnAccent }]}>{title}</Text>
          <Text style={[typography.body, styles.center, { color: colors.textOnAccent }]}>{message}</Text>
        </Animated.View>

        <Animated.Text entering={FadeIn.delay(900)} style={[typography.caption, styles.hint, { color: colors.textOnAccent }]}>
          แตะเพื่อดูสถานะออเดอร์
        </Animated.Text>
      </Pressable>
    </Modal>
  );
};

const styles = StyleSheet.create({
  root: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.xxl,
  },
  confettiLayer: {
    position: 'absolute',
    top: '28%',
    left: 0,
    right: 0,
    alignItems: 'center',
  },
  confetti: {
    position: 'absolute',
    fontSize: 26,
  },
  circleWrap: {
    borderRadius: 64,
  },
  circle: {
    width: 128,
    height: 128,
    borderRadius: 64,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 4,
    borderColor: 'rgba(255,255,255,0.7)',
  },
  check: {
    fontSize: 64,
    fontWeight: '900',
    lineHeight: 72,
  },
  texts: {
    marginTop: spacing.xxl,
    gap: spacing.sm,
  },
  center: {
    textAlign: 'center',
  },
  hint: {
    marginTop: spacing.xxl,
    opacity: 0.8,
  },
});

export default OrderPlacedOverlay;
