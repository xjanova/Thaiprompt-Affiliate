/**
 * StarRating — ให้คะแนน 1-5 ดาว (ปุ่มใหญ่พอสำหรับนิ้ว + สั่นเบาตอนเลือก)
 */

import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { selectionHaptic } from '@/components/ui';
import { useTheme, spacing, typography } from '@/theme';

const LABELS = ['', 'ต้องปรับปรุง', 'พอใช้', 'ดี', 'ดีมาก', 'ประทับใจสุดๆ'];

export const StarRating: React.FC<{
  value: number;
  onChange: (next: number) => void;
  label?: string;
  size?: number;
  disabled?: boolean;
}> = ({ value, onChange, label, size = 34, disabled = false }) => {
  const { colors } = useTheme();
  return (
    <View style={styles.root}>
      {!!label && <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{label}</Text>}
      <View style={styles.row} accessibilityRole="adjustable" accessibilityValue={{ min: 1, max: 5, now: value }}>
        {[1, 2, 3, 4, 5].map((n) => (
          <Pressable
            key={n}
            disabled={disabled}
            onPress={() => {
              selectionHaptic();
              onChange(n);
            }}
            hitSlop={4}
            accessibilityRole="button"
            accessibilityLabel={`${n} ดาว`}
            accessibilityState={{ selected: n <= value }}
            style={({ pressed }) => [styles.star, { transform: [{ scale: pressed ? 0.88 : 1 }] }]}
          >
            <Text style={{ fontSize: size, opacity: n <= value ? 1 : 0.28 }}>⭐</Text>
          </Pressable>
        ))}
      </View>
      <Text style={[typography.caption, { color: colors.goldDeep }]}>{LABELS[value] || 'แตะดาวเพื่อให้คะแนน'}</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  root: {
    alignItems: 'center',
    gap: spacing.xs,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.xs,
  },
  star: {
    minWidth: 44,
    minHeight: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
