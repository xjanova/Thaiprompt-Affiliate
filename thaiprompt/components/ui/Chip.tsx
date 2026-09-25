/**
 * Chip / Pill — ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * - Chip = ปุ่มเลือกตัวกรอง (กดได้) · เลือกอยู่ = พื้นน้ำเงินกรมท่า ตัวอักษรทอง
 * - Pill = ป้ายสถานะเล็กๆ (กดไม่ได้) เช่น "รอชำระ", "ส่งแล้ว" · solid = ป้ายทองเด่น (เช่น "ใหม่")
 * - icon: ชื่อไอคอน / อีโมจิเดิม (แปลงเป็นไอคอนเส้นให้) / element
 *
 * @example
 * <Chip label="ทั้งหมด" selected={tab === 'all'} onPress={() => setTab('all')} />
 * <Pill label="ชำระแล้ว" tone="success" />
 */

import React from 'react';
import { Pressable, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useTheme, radii, toneColors, shadowStyle, type Tone } from '@/theme';
import { Text } from './Text';
import { IconSlot } from './Icon';
import { selectionHaptic } from './haptics';
import { useOnHeader } from './RoyalHeader';

/** สีตัวอักษรป้ายเมื่อวางบนหัวน้ำเงิน (พื้นกระจก) */
const ON_HEADER_FG: Record<Tone, string> = {
  neutral: '#F4F1EA',
  gold: '#F3DC9B',
  success: '#7EE2A8',
  danger: '#FF9C8F',
  info: '#9CC2FF',
  warning: '#F5C77A',
};

type ChipSize = 'sm' | 'md';

const SIZE: Record<ChipSize, { height: number; padX: number; font: number; icon: number }> = {
  sm: { height: 32, padX: 12, font: 12.5, icon: 14 },
  md: { height: 40, padX: 16, font: 14, icon: 17 },
};

// =====================================================
// Chip
// =====================================================

export interface ChipProps {
  label: string;
  icon?: React.ReactNode;
  selected?: boolean;
  onPress?: () => void;
  /** โทนสีตอนยังไม่เลือก (ค่าเริ่มต้น neutral) */
  tone?: Tone;
  size?: ChipSize;
  disabled?: boolean;
  /** ตัวเลขนับท้ายป้าย เช่น จำนวนออเดอร์ */
  count?: number;
  accessibilityLabel?: string;
  style?: StyleProp<ViewStyle>;
}

export const Chip: React.FC<ChipProps> = ({
  label,
  icon,
  selected = false,
  onPress,
  tone = 'neutral',
  size = 'md',
  disabled = false,
  count,
  accessibilityLabel,
  style,
}) => {
  const { colors, gradients, isDark } = useTheme();
  const spec = SIZE[size];
  const t = toneColors(tone, colors);
  const neutral = tone === 'neutral';
  const fg = selected ? colors.goldLight : neutral ? colors.text : t.fg;

  const inner = (
    <View style={[styles.row, { height: spec.height, paddingHorizontal: spec.padX }]}>
      <IconSlot icon={icon} size={spec.icon} color={selected ? colors.goldLight : neutral ? colors.goldDeep : t.fg} weight={selected ? 'fill' : 'regular'} />
      <Text numberOfLines={1} style={[styles.label, { fontSize: spec.font, color: fg }]}>
        {label}
      </Text>
      {typeof count === 'number' && count > 0 && (
        <View
          style={[
            styles.count,
            { backgroundColor: selected ? 'rgba(243,220,155,0.22)' : colors.goldSoft },
          ]}
        >
          <Text style={[styles.countText, { color: selected ? colors.goldLight : colors.goldDeep }]}>
            {count > 99 ? '99+' : count}
          </Text>
        </View>
      )}
    </View>
  );

  const body = selected ? (
    <LinearGradient
      colors={gradients.navy}
      start={{ x: 0, y: 0 }}
      end={{ x: 0, y: 1 }}
      style={[styles.shape, shadowStyle('sm', isDark ? '#000000' : '#0C1A33')]}
    >
      {inner}
    </LinearGradient>
  ) : (
    <View
      style={[
        styles.shape,
        neutral
          ? { backgroundColor: colors.card, borderWidth: 1, borderColor: colors.border }
          : { backgroundColor: t.bg, borderWidth: 1, borderColor: t.border },
      ]}
    >
      {inner}
    </View>
  );

  if (!onPress) {
    return <View style={style}>{body}</View>;
  }

  return (
    <Pressable
      onPress={() => {
        selectionHaptic();
        onPress();
      }}
      disabled={disabled}
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel || label}
      accessibilityState={{ selected, disabled }}
      hitSlop={4}
      style={({ pressed }) => [style, { opacity: disabled ? 0.5 : pressed ? 0.85 : 1 }]}
    >
      {body}
    </Pressable>
  );
};

// =====================================================
// Pill
// =====================================================

export interface PillProps {
  label: string;
  tone?: Tone;
  icon?: React.ReactNode;
  size?: ChipSize;
  /** ทองไล่เฉดแบบเด่น (เช่น ป้าย "ใหม่") */
  solid?: boolean;
  style?: StyleProp<ViewStyle>;
}

export const Pill: React.FC<PillProps> = ({ label, tone = 'neutral', icon, size = 'sm', solid = false, style }) => {
  const { colors, gradients } = useTheme();
  const onHeader = useOnHeader();
  const t = toneColors(tone, colors);
  const font = size === 'sm' ? 11.5 : 13;
  const padY = size === 'sm' ? 3 : 5;
  const padX = size === 'sm' ? 8 : 12;
  const fg = solid ? colors.textOnGold : onHeader ? ON_HEADER_FG[tone] : t.fg;

  const content = (
    <View style={[styles.pillRow, { paddingVertical: padY, paddingHorizontal: padX }]}>
      <IconSlot icon={icon} size={font + 2} color={fg} weight="fill" />
      <Text numberOfLines={1} style={[styles.pillText, { fontSize: font, color: fg }]}>
        {label}
      </Text>
    </View>
  );

  if (solid) {
    return (
      <LinearGradient
        colors={gradients.primary}
        start={{ x: 0, y: 0 }}
        end={{ x: 0, y: 1 }}
        style={[styles.pill, style]}
      >
        {content}
      </LinearGradient>
    );
  }

  return (
    <View
      style={[
        styles.pill,
        onHeader
          ? { backgroundColor: colors.headerGlass, borderWidth: 1, borderColor: colors.headerGlassBorder }
          : { backgroundColor: t.bg },
        style,
      ]}
    >
      {content}
    </View>
  );
};

const styles = StyleSheet.create({
  shape: {
    borderRadius: radii.pill,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  label: {
    fontWeight: '600',
  },
  count: {
    minWidth: 20,
    height: 20,
    borderRadius: 10,
    paddingHorizontal: 5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  countText: {
    fontSize: 11,
    fontWeight: '700',
  },
  pill: {
    borderRadius: 8,
    alignSelf: 'flex-start',
    overflow: 'hidden',
  },
  pillRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  pillText: {
    fontWeight: '700',
  },
});

export default Chip;
