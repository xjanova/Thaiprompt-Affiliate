/**
 * Button3D — ปุ่มหลักของแอป ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * variant
 *   primary   = ทองแชมเปญ ตัวอักษรน้ำตาลเข้ม (การกระทำหลักของหน้า: สั่งเลย ใส่ตะกร้า รับงาน)
 *   navy      = น้ำเงินกรมท่า ตัวอักษรทอง (การกระทำเด่นรอง: ดูตะกร้า ติดตาม)
 *   secondary = ขาว/กระจก มีเส้นขอบ (ทางเลือก: ยกเลิก ข้าม)
 *   success / danger = ยืนยัน / ลบ
 *   ghost     = ตัวอักษรทอง ไม่มีพื้น
 *
 * มิติ: ไล่เฉด + ประกายครึ่งบน + เส้นไฮไลต์ 1px + ขอบล่างบาง 2px + เงาเรืองสีปุ่ม
 * ตอนกด: ย่อ 0.97 + จมลงบนขอบ + เงาหด (reanimated) + สั่นเบา
 * กันกดซ้ำในตัว: ถ้า onPress คืน Promise ปุ่มจะหมุนโหลดและล็อกจนเสร็จเอง
 *
 * icon / iconRight: ชื่อไอคอน (เช่น "plus") · อีโมจิเดิม (แปลงเป็นไอคอนให้) · หรือ element
 *
 * @example
 * <Button3D title="ใส่ตะกร้า · ฿60" icon="shopping-bag-open" onPress={async () => { await add(); }} />
 * <Button3D title="ดูตะกร้า" variant="navy" iconRight="arrow-right" onPress={openCart} />
 */

import React, { useMemo } from 'react';
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  View,
  type StyleProp,
  type TextStyle,
  type ViewStyle,
} from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withSpring,
  withTiming,
} from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { useTheme, withAlpha, type GradientTuple } from '@/theme';
import { Text } from './Text';
import { IconSlot } from './Icon';
import { tapHaptic } from './haptics';
import { usePressGuard } from './usePressGuard';
import { useOnHeader } from './RoyalHeader';

export type Button3DVariant = 'primary' | 'navy' | 'secondary' | 'success' | 'danger' | 'ghost';
export type Button3DSize = 'sm' | 'md' | 'lg';

export interface Button3DProps {
  /** ข้อความบนปุ่ม (ภาษาไทย สั้นๆ) */
  title: string;
  /** กดแล้วทำอะไร — คืน Promise ได้ ปุ่มจะแสดงโหลดและกันกดซ้ำให้เอง */
  onPress?: () => unknown;
  variant?: Button3DVariant;
  size?: Button3DSize;
  /** ไอคอนด้านซ้าย: ชื่อไอคอน / อีโมจิเดิม / element */
  icon?: React.ReactNode;
  /** ไอคอนด้านขวา */
  iconRight?: React.ReactNode;
  /** บังคับแสดงสถานะโหลดจากภายนอก */
  loading?: boolean;
  /** ข้อความระหว่างโหลด (ไม่ใส่ = ใช้ title เดิม) */
  loadingText?: string;
  disabled?: boolean;
  /** กว้างเต็มพื้นที่ */
  fullWidth?: boolean;
  /** สั่นตอนกด (ค่าเริ่มต้น true) */
  haptic?: boolean;
  accessibilityLabel?: string;
  accessibilityHint?: string;
  /** style ของกรอบนอก (margin / flex / width) */
  style?: StyleProp<ViewStyle>;
  textStyle?: StyleProp<TextStyle>;
  testID?: string;
}

const SIZE_SPEC: Record<Button3DSize, { height: number; padX: number; font: number; radius: number; edge: number; icon: number; gap: number }> = {
  sm: { height: 38, padX: 14, font: 13.5, radius: 12, edge: 2, icon: 16, gap: 6 },
  md: { height: 50, padX: 18, font: 15.5, radius: 16, edge: 2, icon: 19, gap: 8 },
  lg: { height: 56, padX: 22, font: 16.5, radius: 18, edge: 3, icon: 21, gap: 10 },
};

export const Button3D: React.FC<Button3DProps> = ({
  title,
  onPress,
  variant = 'primary',
  size = 'md',
  icon,
  iconRight,
  loading = false,
  loadingText,
  disabled = false,
  fullWidth = false,
  haptic = true,
  accessibilityLabel,
  accessibilityHint,
  style,
  textStyle,
  testID,
}) => {
  const { colors, gradients, buttonEdges, isDark } = useTheme();
  const spec = SIZE_SPEC[size];
  const { run, busy } = usePressGuard(onPress, { disabled: disabled || loading });

  const isBusy = loading || busy;
  const isDisabled = disabled || isBusy || !onPress;
  // ปุ่มรอง/ghost ที่วางบนหัวน้ำเงิน → ปุ่มกระจกตัวอักษรสว่าง
  const onHeader = useOnHeader();
  const glass = onHeader && (variant === 'ghost' || variant === 'secondary');
  const isGhost = variant === 'ghost' && !glass;
  const isSecondary = variant === 'secondary' && !glass;

  const pressed = useSharedValue(0);

  // ---------- สีตาม variant ----------
  const look = useMemo(() => {
    const byVariant: Record<Button3DVariant, { gradient: GradientTuple; edge: string; glow: string; glowAlpha: number; text: string }> = {
      primary: { gradient: gradients.primary, edge: buttonEdges.primary, glow: '#C99A3E', glowAlpha: 0.85, text: colors.textOnGold },
      navy: { gradient: gradients.navy, edge: buttonEdges.navy, glow: isDark ? '#000000' : '#0C1A33', glowAlpha: 0.8, text: colors.goldLight },
      secondary: { gradient: gradients.secondary, edge: buttonEdges.secondary, glow: colors.shadowDark, glowAlpha: 0.35, text: colors.textStrong },
      success: { gradient: gradients.success, edge: buttonEdges.success, glow: colors.success, glowAlpha: 0.7, text: colors.textOnAccent },
      danger: { gradient: gradients.danger, edge: buttonEdges.danger, glow: colors.danger, glowAlpha: 0.7, text: colors.textOnAccent },
      ghost: { gradient: ['transparent', 'transparent'], edge: 'transparent', glow: 'transparent', glowAlpha: 0, text: colors.goldDeep },
    };
    if (glass) {
      return { gradient: ['transparent', 'transparent'] as GradientTuple, edge: 'transparent', glow: 'transparent', glowAlpha: 0, text: colors.onHeader };
    }
    return byVariant[variant];
  }, [variant, gradients, buttonEdges, colors, isDark, glass]);

  // ---------- แอนิเมชันตอนกด ----------
  const wrapperAnim = useAnimatedStyle(() => ({
    transform: [{ scale: 1 - 0.03 * pressed.value }],
  }));

  const bodyAnim = useAnimatedStyle(() => ({
    transform: [{ translateY: spec.edge * pressed.value }],
  }));

  const shadowAnim = useAnimatedStyle(() => ({
    opacity: 1 - 0.7 * pressed.value,
  }));

  const handlePressIn = () => {
    if (isDisabled) return;
    pressed.value = withTiming(1, { duration: 90 });
    if (haptic) {
      tapHaptic();
    }
  };

  const handlePressOut = () => {
    pressed.value = withSpring(0, { damping: 14, stiffness: 260 });
  };

  const label = isBusy && loadingText ? loadingText : title;
  const iconWeight = variant === 'secondary' || variant === 'ghost' ? 'regular' : 'bold';

  return (
    <Pressable
      onPress={run}
      onPressIn={handlePressIn}
      onPressOut={handlePressOut}
      disabled={isDisabled}
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel || title}
      accessibilityHint={accessibilityHint}
      accessibilityState={{ disabled: isDisabled, busy: isBusy }}
      hitSlop={size === 'sm' ? 6 : 2}
      testID={testID}
      style={[fullWidth && styles.fullWidth, style]}
    >
      <Animated.View
        style={[
          { paddingBottom: isGhost || glass ? 0 : spec.edge },
          wrapperAnim,
          (disabled || !onPress) && !isBusy && styles.disabled,
        ]}
      >
        {!isGhost && !glass && (
          <>
            {/* 1) เงาเรืองสีปุ่ม */}
            <Animated.View
              pointerEvents="none"
              style={[
                StyleSheet.absoluteFill,
                {
                  borderRadius: spec.radius,
                  boxShadow: `0px 12px 22px -12px ${withAlpha(look.glow, look.glowAlpha)}`,
                },
                shadowAnim,
              ]}
            />
            {/* 2) ขอบล่างบาง = ความหนา */}
            <View
              pointerEvents="none"
              style={[
                styles.edgeLayer,
                { top: spec.edge, borderRadius: spec.radius, backgroundColor: look.edge },
              ]}
            />
          </>
        )}

        {/* 3) ตัวปุ่ม */}
        <Animated.View
          style={[
            styles.body,
            {
              height: spec.height,
              borderRadius: spec.radius,
              paddingHorizontal: spec.padX,
            },
            isSecondary && { borderWidth: 1, borderColor: colors.border },
            glass && { borderWidth: 1, borderColor: colors.headerGlassBorder, backgroundColor: colors.headerGlass },
            isGhost && {
              borderWidth: 1.5,
              borderColor: withAlpha(colors.gold.startsWith('#') ? colors.gold : '#CFA349', 0.45),
              backgroundColor: 'transparent',
            },
            bodyAnim,
          ]}
        >
          {!isGhost && !glass && (
            <>
              <LinearGradient
                colors={look.gradient}
                start={{ x: 0, y: 0 }}
                end={{ x: 0.25, y: 1 }}
                style={StyleSheet.absoluteFill}
              />
              {/* ประกายครึ่งบน */}
              {!isSecondary && (
                <LinearGradient
                  colors={['rgba(255,255,255,0.26)', 'rgba(255,255,255,0)']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 0, y: 1 }}
                  style={[styles.sheen, { borderTopLeftRadius: spec.radius, borderTopRightRadius: spec.radius }]}
                  pointerEvents="none"
                />
              )}
              {/* เส้นไฮไลต์ 1px ด้านบน */}
              <View
                pointerEvents="none"
                style={[
                  styles.highlight,
                  {
                    left: spec.radius * 0.6,
                    right: spec.radius * 0.6,
                    backgroundColor: isSecondary
                      ? colors.shadowLight
                      : variant === 'navy'
                        ? 'rgba(255,255,255,0.16)'
                        : 'rgba(255,255,255,0.6)',
                  },
                ]}
              />
            </>
          )}

          <View style={[styles.content, { gap: spec.gap }]}>
            {isBusy ? (
              <ActivityIndicator size="small" color={look.text} />
            ) : (
              <IconSlot icon={icon} size={spec.icon} color={look.text} weight={iconWeight} />
            )}
            <Text numberOfLines={1} style={[styles.label, { fontSize: spec.font, color: look.text }, textStyle]}>
              {label}
            </Text>
            {!isBusy && <IconSlot icon={iconRight} size={spec.icon} color={look.text} weight={iconWeight} />}
          </View>
        </Animated.View>
      </Animated.View>
    </Pressable>
  );
};

const styles = StyleSheet.create({
  fullWidth: {
    width: '100%',
  },
  disabled: {
    opacity: 0.45,
  },
  edgeLayer: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
  },
  body: {
    overflow: 'hidden',
    justifyContent: 'center',
  },
  sheen: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: '55%',
  },
  highlight: {
    position: 'absolute',
    top: 0,
    height: 1,
  },
  content: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  label: {
    fontWeight: '700',
    textAlign: 'center',
    flexShrink: 1,
  },
});

export default Button3D;
