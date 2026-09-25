/**
 * ShopKit — ชิ้นส่วนหน้าตาที่ใช้ร่วมกันในหน้าช้อป / ร้านค้า / ตะกร้า / ชำระเงิน / คำสั่งซื้อ (ธีมรอยัล น้ำเงินกรมท่า-ทอง)
 *
 * - IconTile      สี่เหลี่ยมมนไอคอนนำหน้าแถว (44×44 มุม 15 · พื้น navySoft ไอคอนน้ำเงิน)
 * - SearchField   ช่องค้นหาแบบการ์ดขาวลอย + แว่นขยาย + ปุ่มล้าง (โฟกัส = ขอบทอง)
 * - StickyBar     แถบขาวลอยท้ายจอ (ยอดรวม + ปุ่มหลัก)
 * - NoticeBanner  แถบข้อความสถานะ info/warning/danger/success พร้อมไอคอน
 * - StoreLogo     โลโก้ร้าน (ไม่มีรูป = ภาพร้าน 3D ประจำแบรนด์)
 * - ThumbImage    รูปสินค้าเล็ก (ไม่มีรูป = ไอคอนกล่องพัสดุ)
 * - MetaItem      ไอคอนเล็ก + ข้อความรอง (เช่น "ส่งด้วยไรเดอร์")
 * - StepBadge     เลขขั้นตอนในวงกลมน้ำเงิน (หน้าชำระเงิน)
 * - RadioMark     วงกลมเลือก (น้ำเงินในโหมดสว่าง · ทองในโหมดมืด)
 *
 * กติกา: สีจาก useTheme() เท่านั้น · ไอคอนเป็นชื่อไอคอน (อีโมจิเดิมแปลงผ่าน IconSlot)
 */

import React, { useState } from 'react';
import { Pressable, StyleSheet, View, type StyleProp, type TextInputProps, type ViewStyle } from 'react-native';
import { Image } from 'expo-image';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text, TextInput } from '@/components/ui/Text';
import { BrandArt, Icon, IconSlot, type IconName, type IconWeight } from '@/components/ui';
import { useTheme, radii, spacing, toneColors, typography, withAlpha, type Tone } from '@/theme';

// =====================================================
// IconTile — ไอคอนในสี่เหลี่ยมมน
// =====================================================

export type IconTileTone = 'navy' | 'gold' | 'success' | 'danger' | 'warning' | 'info';

export interface IconTileProps {
  /** ชื่อไอคอน (อีโมจิเดิมแปลงให้) หรือ element */
  icon: React.ReactNode;
  tone?: IconTileTone;
  /** ขนาดกล่อง (ค่าเริ่มต้น 44) */
  size?: number;
  iconSize?: number;
  weight?: IconWeight;
  style?: StyleProp<ViewStyle>;
}

/** สีพื้น/ไอคอนของกล่องไอคอนตามโทน */
export const useTileColors = (tone: IconTileTone): { bg: string; fg: string } => {
  const { colors, isDark } = useTheme();
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
    case 'navy':
    default:
      // โหมดมืด: น้ำเงินบนพื้นมืดอ่านไม่ออก → ใช้ทองเรืองแทน (มิดไนท์โกลด์)
      return { bg: colors.navySoft, fg: isDark ? colors.gold : colors.navy };
  }
};

export const IconTile: React.FC<IconTileProps> = ({ icon, tone = 'navy', size = 44, iconSize, weight = 'regular', style }) => {
  const look = useTileColors(tone);
  return (
    <View
      style={[
        styles.tile,
        { width: size, height: size, borderRadius: Math.round(size * 0.34), backgroundColor: look.bg },
        style,
      ]}
    >
      <IconSlot icon={icon} size={iconSize ?? Math.round(size * 0.5)} color={look.fg} weight={weight} />
    </View>
  );
};

// =====================================================
// SearchField — ช่องค้นหาการ์ดขาว
// =====================================================

export interface SearchFieldProps extends Omit<TextInputProps, 'style' | 'value' | 'onChangeText'> {
  value: string;
  onChangeText: (next: string) => void;
  /** กดปุ่มล้าง (ไม่ส่ง = ล้างข้อความด้วย onChangeText('')) */
  onClear?: () => void;
  style?: StyleProp<ViewStyle>;
}

export const SearchField: React.FC<SearchFieldProps> = ({ value, onChangeText, onClear, style, onFocus, onBlur, ...rest }) => {
  const { colors, isDark } = useTheme();
  const [focused, setFocused] = useState(false);

  return (
    <View
      style={[
        styles.search,
        {
          backgroundColor: colors.card,
          borderColor: focused ? colors.gold : isDark ? colors.border : 'transparent',
          boxShadow: `0px 14px 28px -20px ${withAlpha(colors.shadowDark, isDark ? 0.9 : 0.45)}`,
        },
        style,
      ]}
    >
      <Icon name="magnifying-glass" size={20} color={focused ? colors.goldDeep : colors.textMuted} />
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholderTextColor={colors.textFaint}
        style={[typography.body, styles.searchInput, { color: colors.textStrong }]}
        {...rest}
        onFocus={(e) => {
          setFocused(true);
          onFocus?.(e);
        }}
        onBlur={(e) => {
          setFocused(false);
          onBlur?.(e);
        }}
      />
      {value.length > 0 && (
        <Pressable
          onPress={onClear ?? (() => onChangeText(''))}
          accessibilityRole="button"
          accessibilityLabel="ล้างคำค้นหา"
          hitSlop={10}
          style={({ pressed }) => ({ opacity: pressed ? 0.6 : 1 })}
        >
          <Icon name="x-circle" size={20} color={colors.textFaint} weight="fill" />
        </Pressable>
      )}
    </View>
  );
};

// =====================================================
// StickyBar — แถบลอยท้ายจอ
// =====================================================

export const StickyBar: React.FC<{ children?: React.ReactNode; style?: StyleProp<ViewStyle> }> = ({ children, style }) => {
  const { colors, isDark } = useTheme();
  const insets = useSafeAreaInsets();
  return (
    <View
      style={[
        styles.sticky,
        {
          backgroundColor: colors.card,
          borderTopColor: colors.divider,
          paddingBottom: Math.max(insets.bottom, spacing.md),
          // เงาขึ้นด้านบน (แถบลอยเหนือเนื้อหา)
          boxShadow: `0px -14px 30px -22px ${withAlpha(colors.shadowDark, isDark ? 0.9 : 0.45)}`,
        },
        style,
      ]}
    >
      {children}
    </View>
  );
};

// =====================================================
// NoticeBanner — แถบข้อความสถานะ
// =====================================================

const NOTICE_ICON: Record<Tone, IconName> = {
  neutral: 'info',
  gold: 'sparkle',
  success: 'check-circle',
  danger: 'warning-circle',
  info: 'info',
  warning: 'warning',
};

export interface NoticeBannerProps {
  text?: React.ReactNode;
  tone?: Tone;
  /** ชื่อไอคอน (ไม่ส่ง = ตามโทน) */
  icon?: IconName;
  /** ปุ่ม/ลิงก์ด้านขวาหรือด้านล่าง */
  action?: React.ReactNode;
  /** วางปุ่ม action ใต้ข้อความ (ค่าเริ่มต้น = ด้านขวา) */
  actionBelow?: boolean;
  /** ปุ่มปิด (ไม่ส่ง = ไม่มีปุ่มปิด) */
  onClose?: () => void;
  closeLabel?: string;
  style?: StyleProp<ViewStyle>;
}

export const NoticeBanner: React.FC<NoticeBannerProps> = ({
  text,
  tone = 'info',
  icon,
  action,
  actionBelow = false,
  onClose,
  closeLabel = 'ปิดข้อความ',
  style,
}) => {
  const { colors } = useTheme();
  const t = toneColors(tone, colors);

  return (
    <View style={[styles.notice, { backgroundColor: t.bg, borderColor: t.border }, style]}>
      <View style={styles.noticeRow}>
        <Icon name={icon ?? NOTICE_ICON[tone]} size={18} color={t.fg} weight="fill" style={styles.noticeIcon} />
        <View style={styles.flex}>
          {typeof text === 'string' ? (
            <Text style={[typography.bodySm, { color: colors.text }]}>{text}</Text>
          ) : (
            text
          )}
        </View>
        {!actionBelow && action}
        {onClose && (
          <Pressable onPress={onClose} accessibilityRole="button" accessibilityLabel={closeLabel} hitSlop={10} style={styles.noticeClose}>
            <Icon name="x" size={16} color={colors.textMuted} weight="bold" />
          </Pressable>
        )}
      </View>
      {actionBelow && !!action && <View style={styles.noticeActionBelow}>{action}</View>}
    </View>
  );
};

// =====================================================
// StoreLogo / ThumbImage
// =====================================================

export interface StoreLogoProps {
  uri?: string | null;
  size?: number;
  radius?: number;
  style?: StyleProp<ViewStyle>;
}

/** โลโก้ร้าน — ไม่มีรูปใช้ภาพร้าน 3D บนพื้นทองอ่อน */
export const StoreLogo: React.FC<StoreLogoProps> = ({ uri, size = 56, radius, style }) => {
  const { colors } = useTheme();
  const r = radius ?? Math.round(size * 0.32);
  const box: ViewStyle = { width: size, height: size, borderRadius: r };
  if (uri) {
    return (
      <View style={[box, styles.clip, { backgroundColor: colors.inset }, style]}>
        <Image source={{ uri }} style={StyleSheet.absoluteFill} contentFit="cover" transition={120} />
      </View>
    );
  }
  return (
    <View style={[box, styles.tile, { backgroundColor: colors.goldSoft }, style]}>
      <BrandArt name="store" size={Math.round(size * 0.76)} />
    </View>
  );
};

export interface ThumbImageProps {
  uri?: string | null;
  size?: number;
  radius?: number;
  transition?: number;
  style?: StyleProp<ViewStyle>;
}

/** รูปสินค้าเล็กในรายการ — ไม่มีรูปแสดงไอคอนกล่องพัสดุ */
export const ThumbImage: React.FC<ThumbImageProps> = ({ uri, size = 64, radius = radii.md, transition = 140, style }) => {
  const { colors } = useTheme();
  const box: ViewStyle = { width: size, height: size, borderRadius: radius, backgroundColor: colors.inset };
  if (uri) {
    return (
      <View style={[box, styles.clip, style]}>
        <Image source={{ uri }} style={StyleSheet.absoluteFill} contentFit="cover" transition={transition} />
      </View>
    );
  }
  return (
    <View style={[box, styles.tile, style]}>
      <Icon name="package" size={Math.round(size * 0.42)} color={colors.textFaint} />
    </View>
  );
};

// =====================================================
// MetaItem / StepBadge / RadioMark
// =====================================================

export const MetaItem: React.FC<{ icon: IconName; text: string; color?: string; style?: StyleProp<ViewStyle> }> = ({
  icon,
  text,
  color,
  style,
}) => {
  const { colors } = useTheme();
  const fg = color ?? colors.textMuted;
  return (
    <View style={[styles.meta, style]}>
      <Icon name={icon} size={14} color={fg} />
      <Text numberOfLines={1} style={[typography.caption, styles.metaText, { color: fg }]}>
        {text}
      </Text>
    </View>
  );
};

/** เลขขั้นตอน (ส่งเข้า icon ของ SectionHeader ได้) — โหมดมืดมีวงทองบางให้เห็นขอบบนพื้นมิดไนท์ */
export const StepBadge: React.FC<{ n: number }> = ({ n }) => {
  const { colors, isDark } = useTheme();
  return (
    <View
      style={[
        styles.step,
        { backgroundColor: colors.navyFill, borderColor: isDark ? withAlpha(colors.gold, 0.45) : colors.navyFill },
      ]}
    >
      <Text style={[styles.stepText, { color: colors.goldLight }]}>{n}</Text>
    </View>
  );
};

/** วงกลมตัวเลือก (radio) */
export const RadioMark: React.FC<{ selected: boolean; disabled?: boolean }> = ({ selected, disabled = false }) => {
  const { colors, isDark } = useTheme();
  const on = isDark ? colors.gold : colors.navy;
  return (
    <View
      style={[
        styles.radio,
        {
          borderColor: selected ? on : colors.border,
          backgroundColor: selected ? on : 'transparent',
          opacity: disabled ? 0.5 : 1,
        },
      ]}
    >
      {selected && <View style={[styles.radioDot, { backgroundColor: colors.card }]} />}
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
  clip: {
    overflow: 'hidden',
  },
  search: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    minHeight: 52,
    borderRadius: 18,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
  },
  searchInput: {
    flex: 1,
    paddingVertical: spacing.sm,
  },
  sticky: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  notice: {
    borderRadius: radii.lg,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
  },
  noticeRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
  },
  noticeIcon: {
    marginTop: 1,
  },
  noticeClose: {
    width: 24,
    height: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },
  noticeActionBelow: {
    marginTop: spacing.md,
    paddingLeft: 26,
  },
  meta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    flexShrink: 1,
  },
  metaText: {
    flexShrink: 1,
  },
  step: {
    width: 26,
    height: 26,
    borderRadius: 13,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepText: {
    fontSize: 13,
    lineHeight: 17,
    fontWeight: '700',
  },
  radio: {
    width: 22,
    height: 22,
    borderRadius: 11,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  radioDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
});
