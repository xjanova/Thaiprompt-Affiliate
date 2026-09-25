/**
 * StarRating — ให้คะแนน 1-5 ดาว (ปุ่มใหญ่พอสำหรับนิ้ว + สั่นเบาตอนเลือก)
 *
 * ดาวเป็นไอคอนเส้น Phosphor: เลือกแล้ว = ดาวทึบสีทอง · ยังไม่เลือก = ดาวเส้นจาง
 */

import React from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Icon, selectionHaptic } from '@/components/ui';
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
        {[1, 2, 3, 4, 5].map((n) => {
          const on = n <= value;
          return (
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
              accessibilityState={{ selected: on }}
              style={({ pressed }) => [styles.star, { transform: [{ scale: pressed ? 0.88 : 1 }] }]}
            >
              <Icon name="star" size={size} weight={on ? 'fill' : 'regular'} color={on ? colors.gold : colors.textFaint} />
            </Pressable>
          );
        })}
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
