/**
 * ชิ้นส่วนหน้าตาที่ใช้ร่วมกันฝั่งผู้ซื้อตลาดสด (ตะกร้า · ชำระเงิน · ติดตามออเดอร์ · ไรเดอร์)
 *
 * - IconTile   : ไอคอนในสี่เหลี่ยมมน 44×44 มุม 15 (แพทเทิร์น "แถวรายการในการ์ด" ของ DESIGN.md)
 * - TileButton : ปุ่มไอคอนสี่เหลี่ยมมน (เช่น โทรหาไรเดอร์) — อ่านออกทั้งโหมดสว่างและมืด
 * - Notice     : กล่องแจ้งเตือนสั้นพร้อมไอคอน (เตือน/ผิดพลาด/ข้อมูล/สำเร็จ) · ส่ง onPress = กดได้
 * - LiveDot    : จุดสถานะสด (เขียว) มีวงกระเพื่อมเบาๆ
 * - floatBarShadow : เงาของแถบปุ่มลอยท้ายจอ (ตกขึ้นด้านบน)
 *
 * โทน navy: พื้นน้ำเงินอ่อน + ไอคอนน้ำเงิน (โหมดมืดใช้ไอคอนทอง เพราะน้ำเงินกรมท่าจมหายบนการ์ดมิดไนท์)
 */

import React, { useEffect } from 'react';
import { Platform, Pressable, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import Animated, {
  Easing,
  cancelAnimation,
  useAnimatedStyle,
  useSharedValue,
  withRepeat,
  withTiming,
} from 'react-native-reanimated';
import { Text } from '@/components/ui/Text';
import { Icon, tapHaptic, usePressGuard, type IconName, type IconWeight } from '@/components/ui';
import { useTheme, radii, spacing, typography, withAlpha, type ThemeColors } from '@/theme';

export type TileTone = 'navy' | 'gold' | 'success' | 'danger' | 'warning' | 'info' | 'muted';

/** สีพื้น/ไอคอนของช่องไอคอนตามโทน */
export const tileColors = (tone: TileTone, colors: ThemeColors, isDark: boolean): { bg: string; fg: string } => {
  switch (tone) {
    case 'gold':
      return { bg: colors.goldSoft, fg: colors.goldDeep };
    case 'success':
      return { bg: colors.successSoft, fg: colors.success };
    case 'danger':
      return { bg: colors.dangerSoft, fg: colors.danger };
    case 'warning':
      return { bg: colors.warningSoft, fg: colors.warning };
    case 'info':
      return { bg: colors.infoSoft, fg: colors.info };
    case 'muted':
      return { bg: colors.inset, fg: colors.textFaint };
    case 'navy':
    default:
      return { bg: colors.navySoft, fg: isDark ? colors.gold : colors.navy };
  }
};

/** สีหลักของเส้น/ไอคอนเน้นแบบเงียบ (น้ำเงินกรมท่า · โหมดมืด = ทอง) */
export const useInk = (): string => {
  const { colors, isDark } = useTheme();
  return isDark ? colors.gold : colors.navy;
};

/**
 * เงาของแถบลอยท้ายจอ — ตกขึ้นด้านบน (shadowStyle ปกติตกลงล่าง ซึ่งจะหายไปใต้ขอบจอ)
 * Android ต่ำกว่า API 28 ไม่รองรับ boxShadow → ใช้ elevation แทน
 */
export const floatBarShadow = (shadowColor: string): ViewStyle => {
  if (Platform.OS === 'android' && typeof Platform.Version === 'number' && Platform.Version < 28) {
    return { elevation: 12, shadowColor };
  }
  return { boxShadow: `0px -10px 28px -14px ${withAlpha(shadowColor, 0.32)}` };
};

// =====================================================
// IconTile
// =====================================================

export interface IconTileProps {
  icon: IconName;
  tone?: TileTone;
  /** ขนาดกล่อง (ค่าเริ่มต้น 44) */
  size?: number;
  weight?: IconWeight;
  style?: StyleProp<ViewStyle>;
}

export const IconTile: React.FC<IconTileProps> = ({ icon, tone = 'navy', size = 44, weight = 'regular', style }) => {
  const { colors, isDark } = useTheme();
  const c = tileColors(tone, colors, isDark);
  return (
    <View
      style={[
        styles.tile,
        { width: size, height: size, borderRadius: Math.round(size * 0.34), backgroundColor: c.bg },
        style,
      ]}
    >
      <Icon name={icon} size={Math.round(size * 0.5)} color={c.fg} weight={weight} />
    </View>
  );
};

// =====================================================
// TileButton
// =====================================================

export interface TileButtonProps {
  icon: IconName;
  /** ข้อความสำหรับโปรแกรมอ่านหน้าจอ */
  label: string;
  /** คืน Promise ได้ — ล็อกกันกดซ้ำจนเสร็จ (เหมือน Button3D) */
  onPress: () => unknown;
  tone?: TileTone;
  size?: number;
  weight?: IconWeight;
  style?: StyleProp<ViewStyle>;
}

export const TileButton: React.FC<TileButtonProps> = ({
  icon,
  label,
  onPress,
  tone = 'navy',
  size = 44,
  weight = 'fill',
  style,
}) => {
  const { colors, isDark } = useTheme();
  const c = tileColors(tone, colors, isDark);
  const { run, busy } = usePressGuard(onPress);
  return (
    <Pressable
      onPress={() => {
        tapHaptic();
        run();
      }}
      accessibilityRole="button"
      accessibilityLabel={label}
      accessibilityState={{ busy }}
      hitSlop={6}
      style={({ pressed }) => [
        styles.tile,
        {
          width: size,
          height: size,
          borderRadius: Math.round(size * 0.34),
          backgroundColor: c.bg,
          borderWidth: 1,
          borderColor: colors.border,
          opacity: pressed ? 0.75 : 1,
          transform: [{ scale: pressed ? 0.95 : 1 }],
        },
        style,
      ]}
    >
      <Icon name={icon} size={Math.round(size * 0.48)} color={c.fg} weight={weight} />
    </Pressable>
  );
};

// =====================================================
// Notice
// =====================================================

export type NoticeTone = 'warning' | 'danger' | 'info' | 'success' | 'gold';

const NOTICE_ICON: Record<NoticeTone, IconName> = {
  warning: 'warning-circle',
  danger: 'warning-circle',
  info: 'info',
  success: 'check-circle',
  gold: 'sparkle',
};

export interface NoticeProps {
  tone?: NoticeTone;
  icon?: IconName;
  /** ข้อความ (string) หรือ element */
  children: React.ReactNode;
  /** เนื้อหาใต้ข้อความ (เช่น ปุ่มลองใหม่) */
  action?: React.ReactNode;
  /** ส่งมา = ทั้งกล่องกดได้ (มีลูกศรขวา) */
  onPress?: () => void;
  accessibilityLabel?: string;
  style?: StyleProp<ViewStyle>;
}

export const Notice: React.FC<NoticeProps> = ({
  tone = 'warning',
  icon,
  children,
  action,
  onPress,
  accessibilityLabel,
  style,
}) => {
  const { colors, isDark } = useTheme();
  const c = tileColors(tone, colors, isDark);

  const body = (
    <>
      <Icon name={icon ?? NOTICE_ICON[tone]} size={18} color={c.fg} weight="fill" style={styles.noticeIcon} />
      <View style={styles.flex}>
        {typeof children === 'string' ? (
          <Text style={[typography.bodySm, { color: colors.text }]}>{children}</Text>
        ) : (
          children
        )}
        {action}
      </View>
      {!!onPress && <Icon name="caret-right" size={16} color={c.fg} weight="bold" style={styles.noticeIcon} />}
    </>
  );

  if (onPress) {
    return (
      <Pressable
        onPress={onPress}
        accessibilityRole="button"
        accessibilityLabel={accessibilityLabel}
        style={({ pressed }) => [styles.notice, { backgroundColor: c.bg, opacity: pressed ? 0.8 : 1 }, style]}
      >
        {body}
      </Pressable>
    );
  }

  return (
    <View accessibilityLabel={accessibilityLabel} style={[styles.notice, { backgroundColor: c.bg }, style]}>
      {body}
    </View>
  );
};

// =====================================================
// LiveDot
// =====================================================

export const LiveDot: React.FC<{ color?: string; size?: number; pulse?: boolean }> = ({ color, size = 8, pulse = true }) => {
  const { colors } = useTheme();
  const tint = color ?? colors.success;
  const p = useSharedValue(0);

  useEffect(() => {
    if (!pulse) return undefined;
    p.value = withRepeat(withTiming(1, { duration: 1500, easing: Easing.out(Easing.quad) }), -1, false);
    return () => cancelAnimation(p);
  }, [p, pulse]);

  const ring = useAnimatedStyle(() => ({
    opacity: 0.55 * (1 - p.value),
    transform: [{ scale: 1 + p.value * 1.6 }],
  }));

  return (
    <View style={[styles.dotBox, { width: size, height: size }]}>
      {pulse && (
        <Animated.View
          pointerEvents="none"
          style={[StyleSheet.absoluteFill, { borderRadius: size / 2, backgroundColor: tint }, ring]}
        />
      )}
      <View style={{ width: size, height: size, borderRadius: size / 2, backgroundColor: tint }} />
    </View>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  tile: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  notice: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    borderRadius: radii.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md - 2,
  },
  noticeIcon: {
    marginTop: 1,
  },
  dotBox: {
    alignItems: 'center',
    justifyContent: 'center',
  },
});
