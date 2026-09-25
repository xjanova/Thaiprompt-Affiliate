/**
 * Button3D — ปุ่มมีมิติแบบดินเหนียวทองคำ
 *
 * โครงเลเยอร์ (ล่าง → บน):
 *   1. เงาตกนุ่มๆ (boxShadow สีเดียวกับปุ่ม) — หดลงตอนกด
 *   2. ขอบล่างสีเข้ม = ความหนาของปุ่ม
 *   3. ตัวปุ่ม: LinearGradient + เส้นไฮไลต์ 1px ด้านบน + ประกายครึ่งบน
 * ตอนกด: ย่อ 0.97 + ตัวปุ่มจมลงบนขอบ + เงาหด (reanimated) + สั่นเบา (expo-haptics)
 *
 * กันกดซ้ำในตัว: ถ้า onPress คืน Promise ปุ่มจะหมุนโหลดและล็อกจนเสร็จเอง
 *
 * @example
 * <Button3D title="ยืนยันคำสั่งซื้อ" icon="✅" onPress={async () => { await submit(); }} />
 * <Button3D title="ยกเลิก" variant="ghost" size="sm" onPress={close} />
 */

import React, { useMemo } from 'react';
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
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
import { useTheme, shadowStyle, type GradientTuple } from '@/theme';
import { tapHaptic } from './haptics';
import { usePressGuard } from './usePressGuard';

export type Button3DVariant = 'primary' | 'secondary' | 'success' | 'danger' | 'ghost';
export type Button3DSize = 'sm' | 'md' | 'lg';

export interface Button3DProps {
  /** ข้อความบนปุ่ม (ภาษาไทย สั้นๆ) */
  title: string;
  /** กดแล้วทำอะไร — คืน Promise ได้ ปุ่มจะแสดงโหลดและกันกดซ้ำให้เอง */
  onPress?: () => unknown;
  variant?: Button3DVariant;
  size?: Button3DSize;
  /** ไอคอนด้านซ้าย: emoji (string) หรือ element */
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
  sm: { height: 38, padX: 14, font: 13, radius: 12, edge: 3, icon: 15, gap: 6 },
  md: { height: 48, padX: 18, font: 15, radius: 14, edge: 4, icon: 18, gap: 8 },
  lg: { height: 56, padX: 22, font: 16, radius: 16, edge: 5, icon: 20, gap: 10 },
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
  const isGhost = variant === 'ghost';

  const pressed = useSharedValue(0);

  // ---------- สีตาม variant ----------
  const look = useMemo(() => {
    const byVariant: Record<Button3DVariant, { gradient: GradientTuple; edge: string; glow: string; text: string }> = {
      // ทอง → ตัวอักษรเข้ม (ขาวบนทองอ่านไม่ออกกลางแดด) · เขียว/แดง → ตัวอักษรขาว
      primary: { gradient: gradients.primary, edge: buttonEdges.primary, glow: colors.amber, text: colors.textOnGold },
      secondary: { gradient: gradients.secondary, edge: buttonEdges.secondary, glow: colors.shadowDark, text: colors.textStrong },
      success: { gradient: gradients.success, edge: buttonEdges.success, glow: colors.success, text: colors.textOnAccent },
      danger: { gradient: gradients.danger, edge: buttonEdges.danger, glow: colors.danger, text: colors.textOnAccent },
      ghost: { gradient: ['transparent', 'transparent'], edge: 'transparent', glow: 'transparent', text: colors.goldDeep },
    };
    return byVariant[variant];
  }, [variant, gradients, buttonEdges, colors]);

  const lightText = variant === 'success' || variant === 'danger';

  // ---------- แอนิเมชันตอนกด ----------
  const wrapperAnim = useAnimatedStyle(() => ({
    transform: [{ scale: 1 - 0.03 * pressed.value }],
  }));

  const bodyAnim = useAnimatedStyle(() => ({
    transform: [{ translateY: spec.edge * 0.7 * pressed.value }],
  }));

  const shadowAnim = useAnimatedStyle(() => ({
    opacity: 1 - 0.65 * pressed.value,
    transform: [{ scaleX: 1 - 0.08 * pressed.value }, { translateY: -3 * pressed.value }],
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

  const renderIcon = (node: React.ReactNode) => {
    if (node === null || node === undefined || node === false) return null;
    if (typeof node === 'string' || typeof node === 'number') {
      return <Text style={{ fontSize: spec.icon }}>{node}</Text>;
    }
    return node;
  };

  const label = isBusy && loadingText ? loadingText : title;

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
          { paddingBottom: isGhost ? 0 : spec.edge },
          wrapperAnim,
          (disabled || !onPress) && !isBusy && styles.disabled,
        ]}
      >
        {!isGhost && (
          <>
            {/* 1) เงาตกนุ่มๆ */}
            <Animated.View
              pointerEvents="none"
              style={[
                styles.shadowLayer,
                { borderRadius: spec.radius, top: spec.edge + 2 },
                shadowStyle(isDark ? 'md' : 'md', look.glow),
                shadowAnim,
              ]}
            />
            {/* 2) ขอบล่าง = ความหนา */}
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
            isGhost && {
              borderWidth: 1.5,
              borderColor: colors.border,
              backgroundColor: 'transparent',
            },
            bodyAnim,
          ]}
        >
          {!isGhost && (
            <>
              <LinearGradient
                colors={look.gradient}
                start={{ x: 0, y: 0 }}
                end={{ x: 0.35, y: 1 }}
                style={StyleSheet.absoluteFill}
              />
              {/* ประกายครึ่งบน */}
              <LinearGradient
                colors={['rgba(255,255,255,0.28)', 'rgba(255,255,255,0)']}
                start={{ x: 0, y: 0 }}
                end={{ x: 0, y: 1 }}
                style={[styles.sheen, { borderTopLeftRadius: spec.radius, borderTopRightRadius: spec.radius }]}
                pointerEvents="none"
              />
              {/* เส้นไฮไลต์ 1px ด้านบน */}
              <View
                pointerEvents="none"
                style={[
                  styles.highlight,
                  {
                    left: spec.radius * 0.6,
                    right: spec.radius * 0.6,
                    backgroundColor: variant === 'secondary' ? colors.shadowLight : 'rgba(255,255,255,0.65)',
                  },
                ]}
              />
            </>
          )}

          <View style={[styles.content, { gap: spec.gap }]}>
            {isBusy ? (
              <ActivityIndicator size="small" color={look.text} />
            ) : (
              renderIcon(icon)
            )}
            <Text
              numberOfLines={1}
              style={[
                styles.label,
                { fontSize: spec.font, color: look.text },
                lightText && styles.labelShadow,
                textStyle,
              ]}
            >
              {label}
            </Text>
            {!isBusy && renderIcon(iconRight)}
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
    opacity: 0.5,
  },
  shadowLayer: {
    position: 'absolute',
    left: 6,
    right: 6,
    bottom: 0,
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
  labelShadow: {
    textShadowColor: 'rgba(0,0,0,0.18)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 2,
  },
});

export default Button3D;
