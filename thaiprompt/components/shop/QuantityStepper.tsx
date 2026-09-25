/**
 * QuantityStepper — ปุ่ม − จำนวน + แบบยุบลง (ปุ่มกดใหญ่พอสำหรับนิ้ว 44px)
 */

import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { selectionHaptic } from '@/components/ui';
import { useTheme, radii, typography } from '@/theme';

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
  const { colors } = useTheme();
  const box = size === 'sm' ? 36 : 44;
  const canDec = !disabled && !busy && value > min;
  const canInc = !disabled && !busy && value < max;

  const change = (next: number) => {
    selectionHaptic();
    onChange(Math.max(min, Math.min(max, next)));
  };

  return (
    <View style={[styles.row, { backgroundColor: colors.inset, borderRadius: radii.md }]}>
      <Pressable
        onPress={() => change(value - 1)}
        disabled={!canDec}
        accessibilityRole="button"
        accessibilityLabel="ลดจำนวน"
        hitSlop={4}
        style={[styles.button, { width: box, height: box }, !canDec && styles.dim]}
      >
        <Text style={[typography.h2, { color: colors.textStrong }]}>−</Text>
      </Pressable>
      <View style={[styles.valueBox, { minWidth: size === 'sm' ? 30 : 38 }]}>
        {busy ? (
          <ActivityIndicator size="small" color={colors.gold} />
        ) : (
          <Text accessibilityLabel={`จำนวน ${value}`} style={[typography.h3, { color: colors.textStrong }]}>
            {value}
          </Text>
        )}
      </View>
      <Pressable
        onPress={() => change(value + 1)}
        disabled={!canInc}
        accessibilityRole="button"
        accessibilityLabel="เพิ่มจำนวน"
        hitSlop={4}
        style={[styles.button, { width: box, height: box }, !canInc && styles.dim]}
      >
        <Text style={[typography.h2, { color: colors.textStrong }]}>+</Text>
      </Pressable>
    </View>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
  },
  button: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  valueBox: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  dim: {
    opacity: 0.3,
  },
});

export default QuantityStepper;
