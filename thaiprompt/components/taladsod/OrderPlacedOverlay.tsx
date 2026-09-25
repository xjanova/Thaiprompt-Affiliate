/**
 * OrderPlacedOverlay — ฉลองสั่งซื้อสำเร็จ
 *
 * การ์ดรอยัลน้ำเงิน-ทอง (ลายกนก + ขอบทอง) · ตะกร้าผักสด 3D เด้งขึ้นกลางวงแสงทอง
 * · เหรียญถูกสีทองเด้งตาม · ประกายทองกระจายออกรอบภาพ · สั่นแจ้งสำเร็จ
 *
 * แสดงครั้งเดียวตอนเข้าหน้าออเดอร์หลังสั่งเสร็จ · ปิดเองใน ~2.6 วินาที หรือแตะเพื่อปิด
 */

import React, { useEffect, useRef } from 'react';
import { Modal, Pressable, StyleSheet, View, useWindowDimensions } from 'react-native';
import { Text } from '@/components/ui/Text';
import Animated, {
  Easing,
  FadeIn,
  FadeInDown,
  cancelAnimation,
  useAnimatedStyle,
  useSharedValue,
  withDelay,
  withSequence,
  withSpring,
  withTiming,
} from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { BrandArt, Icon, RoyalHeader, resultHaptic } from '@/components/ui';
import { useTheme, glowStyle, radii, shadowStyle, spacing, typography, withAlpha } from '@/theme';

const SPARKS = 10;
const AUTO_CLOSE_MS = 2600;
/** ขนาดวงแสงรอบภาพ */
const HALO = 156;
const SPARK_BOX = 22;

/** ประกายทองหนึ่งดวง — พุ่งออกจากกลางภาพ หมุน แล้วจางหาย */
const Spark: React.FC<{ index: number; color: string }> = ({ index, color }) => {
  const t = useSharedValue(0);
  const angle = (index / SPARKS) * Math.PI * 2 - Math.PI / 2 + (index % 2 === 0 ? 0.18 : -0.18);
  const dist = 78 + (index % 3) * 14;
  const dx = Math.cos(angle) * dist;
  const dy = Math.sin(angle) * dist;
  const size = 11 + (index % 3) * 4;
  const spin = index % 2 === 0 ? 150 : -150;

  useEffect(() => {
    t.value = withDelay(
      200 + index * 45,
      withTiming(1, { duration: 1150 + (index % 3) * 200, easing: Easing.out(Easing.cubic) })
    );
    return () => cancelAnimation(t);
  }, [t, index]);

  const style = useAnimatedStyle(() => ({
    opacity: t.value < 0.2 ? t.value * 5 : 1 - (t.value - 0.2) / 0.8,
    transform: [
      { translateX: dx * t.value },
      { translateY: dy * t.value },
      { rotate: `${spin * t.value}deg` },
      { scale: 0.5 + t.value * 0.7 },
    ],
  }));

  return (
    <Animated.View pointerEvents="none" style={[styles.spark, style]}>
      <Icon name="sparkle" size={size} color={color} weight="fill" />
    </Animated.View>
  );
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

  // การ์ดขยายเบาๆ · ภาพเด้งเต็ม · เหรียญถูกเด้งตามทีหลัง
  const cardStyle = useAnimatedStyle(() => ({
    opacity: Math.min(1, pop.value * 1.6),
    transform: [{ scale: 0.88 + 0.12 * pop.value }],
  }));
  const artStyle = useAnimatedStyle(() => ({ transform: [{ scale: pop.value }] }));
  const checkStyle = useAnimatedStyle(() => ({ transform: [{ scale: check.value }], opacity: Math.min(1, check.value) }));

  if (!visible) return null;

  const cardWidth = Math.min(340, width - spacing.xxl * 2);

  return (
    <Modal visible transparent animationType="fade" statusBarTranslucent onRequestClose={onClose}>
      <Pressable
        style={[styles.root, { backgroundColor: colors.overlay }]}
        onPress={onClose}
        accessibilityRole="button"
        accessibilityLabel={`${title} ${message} แตะเพื่อปิด`}
      >
        <Animated.View style={[{ width: cardWidth, borderRadius: radii.xxl }, shadowStyle('lg', colors.shadowDark), cardStyle]}>
          {/* ขอบทองบาง */}
          <LinearGradient colors={gradients.goldBorder} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.border}>
            <RoyalHeader ornamentWidth={170} ornamentTop={-40} style={styles.card}>
              {/* ---------- ภาพ + วงแสง + ประกาย ---------- */}
              <View style={styles.haloBox}>
                <View style={[styles.haloOuter, { backgroundColor: withAlpha(colors.gold, 0.1) }]} />
                <View
                  style={[
                    styles.haloInner,
                    { backgroundColor: withAlpha(colors.gold, 0.16), borderColor: withAlpha(colors.goldLight, 0.35) },
                  ]}
                />
                {Array.from({ length: SPARKS }).map((_, i) => (
                  <Spark key={i} index={i} color={i % 3 === 0 ? colors.goldLight : colors.gold} />
                ))}
                <Animated.View style={artStyle}>
                  <BrandArt name="basket" size={124} />
                </Animated.View>
                <Animated.View style={[styles.badge, glowStyle(colors.gold, 0.9), checkStyle]}>
                  <LinearGradient colors={gradients.primary} start={{ x: 0, y: 0 }} end={{ x: 0.3, y: 1 }} style={styles.badgeInner}>
                    <Icon name="check" size={24} color={colors.textOnGold} weight="bold" />
                  </LinearGradient>
                </Animated.View>
              </View>

              {/* ---------- ข้อความ ---------- */}
              <Animated.View entering={FadeInDown.delay(260).springify().damping(16)} style={styles.texts}>
                <Text style={[typography.serifLg, styles.center, { color: colors.onHeader }]}>{title}</Text>
                <Text style={[typography.body, styles.center, { color: colors.onHeaderMuted }]}>{message}</Text>
              </Animated.View>

              <Animated.View entering={FadeIn.delay(900)} style={[styles.hint, { borderColor: colors.headerGlassBorder, backgroundColor: colors.headerGlass }]}>
                <Text style={[typography.caption, { color: colors.goldLight }]}>แตะเพื่อดูสถานะออเดอร์</Text>
                <Icon name="caret-right" size={13} color={colors.goldLight} weight="bold" />
              </Animated.View>
            </RoyalHeader>
          </LinearGradient>
        </Animated.View>
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
  border: {
    borderRadius: radii.xxl,
    padding: 1.5,
  },
  card: {
    borderRadius: radii.xxl - 1.5,
    alignItems: 'center',
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.xxl,
    paddingBottom: spacing.xl,
  },
  haloBox: {
    width: HALO,
    height: HALO,
    alignItems: 'center',
    justifyContent: 'center',
  },
  haloOuter: {
    position: 'absolute',
    width: HALO,
    height: HALO,
    borderRadius: HALO / 2,
  },
  haloInner: {
    position: 'absolute',
    width: HALO - 34,
    height: HALO - 34,
    borderRadius: (HALO - 34) / 2,
    borderWidth: 1,
  },
  spark: {
    position: 'absolute',
    top: HALO / 2 - SPARK_BOX / 2,
    left: HALO / 2 - SPARK_BOX / 2,
    width: SPARK_BOX,
    height: SPARK_BOX,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badge: {
    position: 'absolute',
    right: 10,
    bottom: 12,
    borderRadius: 22,
  },
  badgeInner: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  texts: {
    marginTop: spacing.lg,
    gap: spacing.xs,
  },
  center: {
    textAlign: 'center',
  },
  hint: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: spacing.xl,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
    borderRadius: radii.pill,
    borderWidth: 1,
  },
});

export default OrderPlacedOverlay;
