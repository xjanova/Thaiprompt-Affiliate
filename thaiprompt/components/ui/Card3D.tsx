/**
 * Card3D — การ์ดของแอป ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * - variant raised (ค่าเริ่มต้น) = การ์ดขาวลอยด้วยเงานุ่มสองชั้น (โหมดมืด = การ์ดกระจกทึบ + ขอบบาง)
 *   flat = เรียบไม่มีเงา · inset = ยุบลง (กล่องกรอก/สรุปยอด)
 * - gradientBorder = ขอบไล่เฉดทอง (true) หรือส่ง gradient เอง — ใช้กับการ์ดสำคัญเท่านั้น
 * - ส่ง onPress = การ์ดกดได้ (ย่อเล็กน้อย + สั่นเบา + กันกดซ้ำ)
 * - การ์ดที่เป็นตัวเลือก: ส่ง accessibilityRole="radio" (เลือกได้อย่างเดียว) / "checkbox" (หลายอย่าง)
 *   + accessibilityState={{ checked }} ให้ screen reader บอกว่าเลือกอยู่หรือไม่
 *
 * @example
 * <Card3D onPress={() => router.push('/orders')}>
 *   <Text>คำสั่งซื้อของฉัน</Text>
 * </Card3D>
 */

import React from 'react';
import {
  Pressable,
  StyleSheet,
  View,
  type AccessibilityRole,
  type AccessibilityState,
  type StyleProp,
  type ViewStyle,
} from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withSpring,
  withTiming,
} from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import {
  useTheme,
  shadowStyle,
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
  /** บทบาทสำหรับ screen reader (ค่าเริ่มต้น button) — ตัวเลือกใช้ radio / checkbox */
  accessibilityRole?: AccessibilityRole;
  /** สถานะเพิ่มเติม เช่น { checked } / { selected } (disabled ใส่ให้เองจาก prop disabled) */
  accessibilityState?: Omit<AccessibilityState, 'disabled'>;
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
  accessibilityRole = 'button',
  accessibilityState,
  testID,
}) => {
  const { colors, gradients, isDark } = useTheme();
  const { run } = usePressGuard(onPress, { disabled });
  const pressed = useSharedValue(0);
  const pressable = !!onPress || !!onLongPress;

  const anim = useAnimatedStyle(() => ({
    transform: [{ scale: 1 - 0.018 * pressed.value }],
    opacity: 1 - 0.05 * pressed.value,
  }));

  const borderColors: GradientTuple | null = gradientBorder
    ? gradientBorder === true
      ? gradients.goldBorder
      : gradientBorder
    : null;

  // ---------- พื้นผิว ----------
  const surfaceStyle: ViewStyle =
    variant === 'inset'
      ? {
          backgroundColor: colors.inset,
          borderWidth: 1,
          borderColor: colors.border,
        }
      : variant === 'flat'
        ? {
            backgroundColor: colors.surface,
            borderWidth: 1,
            borderColor: colors.border,
          }
        : {
            backgroundColor: colors.card,
            // โหมดมืด: การ์ดกระจก = ขอบบาง + เส้นไฮไลต์บน
            borderWidth: isDark && !borderColors ? 1 : 0,
            borderColor: colors.border,
            borderTopColor: isDark && !borderColors ? colors.shadowLight : undefined,
          };

  const outerShadow: ViewStyle = variant === 'raised' ? shadowStyle(shadow, colors.shadowDark) : {};

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
      accessibilityRole={accessibilityRole}
      accessibilityLabel={accessibilityLabel}
      accessibilityHint={accessibilityHint}
      accessibilityState={{ ...accessibilityState, disabled }}
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
