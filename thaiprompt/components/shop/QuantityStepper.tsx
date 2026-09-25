/**
 * QuantityStepper — ปุ่ม − จำนวน + (ธีมรอยัล)
 *
 * ราง "ยุบลง" สีพื้นช่องกรอก + ปุ่มการ์ดขาวลอยสองข้าง ไอคอนน้ำเงิน (โหมดมืด = ทอง)
 * จุดกดรวม hitSlop ≥ 44px สำหรับนิ้ว · กำลังบันทึก = วงหมุนแทนตัวเลขและล็อกปุ่ม
 */

import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Icon, selectionHaptic } from '@/components/ui';
import { useTheme, shadowStyle, typography } from '@/theme';

export interface QuantityStepperProps {
  value: number;
  min?: number;
  max?: number;
  onChange: (next: number) => void;
  /** กำลังบันทึก — ล็อกปุ่มและแสดงวงหมุนแทนตัวเลข */
  busy?: boolean;
  disabled?: boolean;
  size?: 'sm' | 'md';
}

export const QuantityStepper: React.FC<QuantityStepperProps> = ({
  value,
  min = 1,
  max = 99,
  onChange,
  busy = false,
  disabled = false,
  size = 'md',
}) => {
  const { colors, isDark } = useTheme();
  const box = size === 'sm' ? 32 : 40;
  const pad = size === 'sm' ? 3 : 4;
  const canDec = !disabled && !busy && value > min;
  const canInc = !disabled && !busy && value < max;
  const iconColor = isDark ? colors.gold : colors.navy;

  const change = (next: number) => {
    selectionHaptic();
    onChange(Math.max(min, Math.min(max, next)));
  };

  const buttonStyle = (enabled: boolean) => [
    styles.button,
    {
      width: box,
      height: box,
      borderRadius: Math.round(box * 0.34),
      backgroundColor: colors.card,
      borderColor: colors.border,
    },
    enabled ? shadowStyle('sm', colors.shadowDark) : styles.dim,
  ];

  return (
    <View style={[styles.row, { backgroundColor: colors.inset, borderColor: colors.border, padding: pad, borderRadius: Math.round(box * 0.34) + pad }]}>
      <Pressable
        onPress={() => change(value - 1)}
        disabled={!canDec}
        accessibilityRole="button"
        accessibilityLabel="ลดจำนวน"
        hitSlop={6}
        style={({ pressed }) => [buttonStyle(canDec), pressed && styles.pressed]}
      >
        <Icon name="minus" size={size === 'sm' ? 15 : 18} color={iconColor} weight="bold" />
      </Pressable>
      <View style={[styles.valueBox, { minWidth: size === 'sm' ? 30 : 38 }]}>
        {busy ? (
          <ActivityIndicator size="small" color={colors.gold} />
        ) : (
          <Text accessibilityLabel={`จำนวน ${value}`} style={[size === 'sm' ? typography.bodyStrong : typography.h3, styles.value, { color: colors.textStrong }]}>
            {value}
          </Text>
        )}
      </View>
      <Pressable
        onPress={() => change(value + 1)}
        disabled={!canInc}
        accessibilityRole="button"
        accessibilityLabel="เพิ่มจำนวน"
        hitSlop={6}
        style={({ pressed }) => [buttonStyle(canInc), pressed && styles.pressed]}
      >
        <Icon name="plus" size={size === 'sm' ? 15 : 18} color={iconColor} weight="bold" />
      </Pressable>
    </View>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    borderWidth: 1,
  },
  button: {
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
  valueBox: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
  },
  value: {
    fontVariant: ['tabular-nums'],
    fontWeight: '700',
  },
  dim: {
    opacity: 0.4,
  },
  pressed: {
    transform: [{ scale: 0.94 }],
  },
});

export default QuantityStepper;
