/**
 * Chip / Pill
 *
 * - Chip = ปุ่มเลือกตัวกรอง (กดได้, มีสถานะ selected เป็นทองไล่เฉด)
 * - Pill = ป้ายสถานะเล็กๆ (กดไม่ได้) เช่น "รอชำระ", "ส่งแล้ว"
 *
 * @example
 * <Chip label="ทั้งหมด" selected={tab === 'all'} onPress={() => setTab('all')} />
 * <Pill label="ชำระแล้ว" tone="success" />
 */

import React from 'react';
import { Pressable, StyleSheet, Text, View, type StyleProp, type ViewStyle } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useTheme, radii, toneColors, shadowStyle, type Tone } from '@/theme';
import { selectionHaptic } from './haptics';

type ChipSize = 'sm' | 'md';

const SIZE: Record<ChipSize, { height: number; padX: number; font: number; icon: number }> = {
  sm: { height: 30, padX: 12, font: 12, icon: 13 },
  md: { height: 38, padX: 16, font: 14, icon: 16 },
};

const renderIcon = (icon: React.ReactNode, size: number) => {
  if (icon === null || icon === undefined || icon === false) return null;
  if (typeof icon === 'string' || typeof icon === 'number') {
    return <Text style={{ fontSize: size }}>{icon}</Text>;
  }
  return icon;
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
  const { colors, gradients } = useTheme();
  const spec = SIZE[size];
  const t = toneColors(tone, colors);
  const fg = selected ? colors.textOnGold : t.fg;

  const inner = (
    <View style={[styles.row, { height: spec.height, paddingHorizontal: spec.padX }]}>
      {renderIcon(icon, spec.icon)}
      <Text numberOfLines={1} style={[styles.label, { fontSize: spec.font, color: fg }]}>
        {label}
      </Text>
      {typeof count === 'number' && count > 0 && (
        <View
          style={[
            styles.count,
            { backgroundColor: selected ? 'rgba(255,255,255,0.3)' : colors.goldSoft },
          ]}
        >
          <Text style={[styles.countText, { color: selected ? colors.textOnGold : colors.goldDeep }]}>
            {count > 99 ? '99+' : count}
          </Text>
        </View>
      )}
    </View>
  );

  const body = selected ? (
    <LinearGradient
      colors={gradients.primary}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={[styles.shape, shadowStyle('sm', colors.amber)]}
    >
      {inner}
    </LinearGradient>
  ) : (
    <View style={[styles.shape, { backgroundColor: t.bg, borderWidth: 1, borderColor: t.border }]}>{inner}</View>
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
  const t = toneColors(tone, colors);
  const font = size === 'sm' ? 11 : 13;
  const padY = size === 'sm' ? 3 : 5;
  const padX = size === 'sm' ? 8 : 12;

  const content = (
    <View style={[styles.pillRow, { paddingVertical: padY, paddingHorizontal: padX }]}>
      {renderIcon(icon, font + 1)}
      <Text numberOfLines={1} style={[styles.pillText, { fontSize: font, color: solid ? colors.textOnGold : t.fg }]}>
        {label}
      </Text>
    </View>
  );

  if (solid) {
    return (
      <LinearGradient
        colors={gradients.primary}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={[styles.pill, style]}
      >
        {content}
      </LinearGradient>
    );
  }

  return (
    <View style={[styles.pill, { backgroundColor: t.bg, borderColor: t.border, borderWidth: 1 }, style]}>
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
    borderRadius: radii.pill,
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
