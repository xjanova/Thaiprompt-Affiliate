/**
 * OrderSourceSwitch — สลับรายการคำสั่งซื้อ "ร้านค้า | ตลาดสด" (แถบยุบลง + ปุ่มทองลอยขึ้น)
 */

import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { selectionHaptic } from '@/components/ui';
import { useTheme, radii, spacing, typography, shadowStyle } from '@/theme';

export type OrderSource = 'shop' | 'fresh';

const OPTIONS: Array<{ key: OrderSource; label: string; icon: string }> = [
  { key: 'shop', label: 'ร้านค้า', icon: '🛍️' },
  { key: 'fresh', label: 'ตลาดสด', icon: '🥬' },
];

export const OrderSourceSwitch: React.FC<{ value: OrderSource; onChange: (next: OrderSource) => void; freshBadge?: number }> = ({
  value,
  onChange,
  freshBadge,
}) => {
  const { colors, gradients } = useTheme();

  return (
    <View style={[styles.track, { backgroundColor: colors.inset, borderColor: colors.border }]} accessibilityRole="tablist">
      {OPTIONS.map((opt) => {
        const selected = value === opt.key;
        const content = (
          <View style={styles.inner}>
            <Text style={styles.icon}>{opt.icon}</Text>
            <Text style={[typography.bodyStrong, { color: selected ? colors.textOnGold : colors.textMuted }]}>{opt.label}</Text>
            {opt.key === 'fresh' && !!freshBadge && freshBadge > 0 && (
              <View style={[styles.badge, { backgroundColor: selected ? 'rgba(255,255,255,0.35)' : colors.goldSoft }]}>
                <Text style={[typography.micro, { color: selected ? colors.textOnGold : colors.goldDeep }]}>
                  {freshBadge > 99 ? '99+' : freshBadge}
                </Text>
              </View>
            )}
          </View>
        );
        return (
          <Pressable
            key={opt.key}
            onPress={() => {
              if (selected) return;
              selectionHaptic();
              onChange(opt.key);
            }}
            accessibilityRole="tab"
            accessibilityState={{ selected }}
            accessibilityLabel={`คำสั่งซื้อ${opt.label}`}
            style={styles.flex}
          >
            {selected ? (
              <LinearGradient
                colors={gradients.primary}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={[styles.pill, shadowStyle('sm', colors.amber)]}
              >
                {content}
              </LinearGradient>
            ) : (
              <View style={styles.pill}>{content}</View>
            )}
          </Pressable>
        );
      })}
    </View>
  );
};

const styles = StyleSheet.create({
  track: {
    flexDirection: 'row',
    borderRadius: radii.pill,
    borderWidth: 1,
    padding: 4,
    gap: 4,
  },
  flex: {
    flex: 1,
  },
  pill: {
    borderRadius: radii.pill,
    minHeight: 42,
    justifyContent: 'center',
  },
  inner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xs,
    paddingHorizontal: spacing.md,
  },
  icon: {
    fontSize: 16,
  },
  badge: {
    minWidth: 20,
    height: 20,
    borderRadius: 10,
    paddingHorizontal: 5,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
