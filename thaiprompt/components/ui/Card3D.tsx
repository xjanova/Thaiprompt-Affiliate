/**
 * Card3D — การ์ดดินเหนียวยกขึ้นจากพื้น (เงาสองทางแบบเว็บ V4)
 *
 * - variant raised (ค่าเริ่มต้น) = ยกขึ้น · flat = เรียบไม่มีเงา · inset = ยุบลง (กล่องกรอก/สรุปยอด)
 * - gradientBorder = ขอบไล่เฉดทอง (true) หรือส่ง gradient เอง
 * - ส่ง onPress = การ์ดกดได้ (ย่อเล็กน้อย + เงาหด + สั่นเบา + กันกดซ้ำ)
 *
 * @example
 * <Card3D onPress={() => router.push('/orders')} gradientBorder>
 *   <Text>คำสั่งซื้อของฉัน</Text>
 * </Card3D>
 */

import React from 'react';
import { Pressable, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withSpring,
  withTiming,
} from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import {
  useTheme,
  clayShadowStyle,
  radii,
  spacing,
  type GradientTuple,
  type ShadowLevel,
} from '@/theme';
import { tapHaptic } from './haptics';
import { usePressGuard } from './usePressGuard';

export type Card3DVariant = 'raised' | 'flat' | 'inset';

export interface Card3DProps {
  children?: React.ReactNode;
  /** กดได้เมื่อส่ง onPress (คืน Promise ได้ — กันกดซ้ำให้เอง) */
  onPress?: () => unknown;
  onLongPress?: () => void;
  disabled?: boolean;
  variant?: Card3DVariant;
  /** ขอบไล่เฉด: true = ทอง, หรือส่ง gradient เอง */
  gradientBorder?: boolean | GradientTuple;
  /** ความลึกเงา (raised เท่านั้น) */
  shadow?: Exclude<ShadowLevel, 'none'>;
  /** ระยะขอบใน (ค่าเริ่มต้น 16) */
  padding?: number;
  /** มุมโค้ง (ค่าเริ่มต้น 22) */
  radius?: number;
  /** style กรอบนอก (margin / width / flex) */
  style?: StyleProp<ViewStyle>;
  /** style พื้นที่เนื้อหา */
  contentStyle?: StyleProp<ViewStyle>;
  haptic?: boolean;
  accessibilityLabel?: string;
  accessibilityHint?: string;
  testID?: string;
}

const BORDER_WIDTH = 1.5;

export const Card3D: React.FC<Card3DProps> = ({
  children,
  onPress,
  onLongPress,
  disabled = false,
  variant = 'raised',
  gradientBorder = false,
  shadow = 'md',
  padding = spacing.lg,
  radius = radii.xl,
  style,
  contentStyle,
  haptic = true,
  accessibilityLabel,
  accessibilityHint,
  testID,
}) => {
  const { colors, gradients, isDark } = useTheme();
  const { run } = usePressGuard(onPress, { disabled });
  const pressed = useSharedValue(0);
  const pressable = !!onPress || !!onLongPress;

  const anim = useAnimatedStyle(() => ({
    transform: [{ scale: 1 - 0.02 * pressed.value }],
    opacity: 1 - 0.06 * pressed.value,
  }));

  const borderColors: GradientTuple | null = gradientBorder
    ? gradientBorder === true
      ? gradients.goldBorder
      : gradientBorder
    : null;

  // ---------- พื้นผิว + เงา ----------
  const surfaceStyle: ViewStyle =
    variant === 'inset'
      ? {
          backgroundColor: colors.inset,
          borderWidth: 1,
          borderColor: colors.border,
        }
      : {
          backgroundColor: variant === 'flat' ? colors.surface : colors.card,
          // ไฮไลต์ขอบบนแบบดินเหนียว (โหมดสว่างเท่านั้น)
          borderTopWidth: variant === 'raised' && !isDark && !borderColors ? 1 : 0,
          borderTopColor: colors.shadowLight,
        };

  const outerShadow: ViewStyle =
    variant === 'raised' ? clayShadowStyle(shadow, colors.shadowDark, colors.shadowLight) : {};

  const content = (
    <View
      style={[
        surfaceStyle,
        {
          borderRadius: borderColors ? radius - BORDER_WIDTH : radius,
          padding,
        },
        contentStyle,
      ]}
    >
      {children}
    </View>
  );

  const body = borderColors ? (
    <LinearGradient
      colors={borderColors}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={{ borderRadius: radius, padding: BORDER_WIDTH }}
    >
      {content}
    </LinearGradient>
  ) : (
    content
  );

  if (!pressable) {
    return (
      <View style={[{ borderRadius: radius }, outerShadow, style]} testID={testID}>
        {body}
      </View>
    );
  }

  return (
    <Pressable
      onPress={run}
      onLongPress={onLongPress}
      delayLongPress={450}
      disabled={disabled}
      onPressIn={() => {
        pressed.value = withTiming(1, { duration: 90 });
        if (haptic) tapHaptic();
      }}
      onPressOut={() => {
        pressed.value = withSpring(0, { damping: 15, stiffness: 240 });
      }}
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel}
      accessibilityHint={accessibilityHint}
      accessibilityState={{ disabled }}
      testID={testID}
      style={style}
    >
      <Animated.View style={[{ borderRadius: radius }, outerShadow, anim, disabled && styles.disabled]}>
        {body}
      </Animated.View>
    </Pressable>
  );
};

const styles = StyleSheet.create({
  disabled: {
    opacity: 0.55,
  },
});

export default Card3D;
