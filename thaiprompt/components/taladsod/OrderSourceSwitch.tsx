/**
 * OrderSourceSwitch — สลับรายการคำสั่งซื้อ "ร้านค้า | ตลาดสด" (ธีมรอยัล)
 *
 * รางยุบลง + ปุ่มที่เลือกเป็นเม็ดน้ำเงินกรมท่าตัวทอง (แบบเดียวกับชิป/แท็บที่เลือกทั้งแอป)
 */

import React from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { LinearGradient } from 'expo-linear-gradient';
import { Icon, selectionHaptic, type IconName } from '@/components/ui';
import { useTheme, radii, spacing, typography, shadowStyle } from '@/theme';

export type OrderSource = 'shop' | 'fresh';

const OPTIONS: Array<{ key: OrderSource; label: string; icon: IconName }> = [
  { key: 'shop', label: 'ร้านค้า', icon: 'shopping-bag-open' },
  { key: 'fresh', label: 'ตลาดสด', icon: 'basket' },
];

export const OrderSourceSwitch: React.FC<{ value: OrderSource; onChange: (next: OrderSource) => void; freshBadge?: number }> = ({
  value,
  onChange,
  freshBadge,
}) => {
  const { colors, gradients, isDark } = useTheme();

  return (
    <View style={[styles.track, { backgroundColor: colors.inset, borderColor: colors.border }]} accessibilityRole="tablist">
      {OPTIONS.map((opt) => {
        const selected = value === opt.key;
        const fg = selected ? colors.goldLight : colors.textMuted;
        const content = (
          <View style={styles.inner}>
            <Icon name={opt.icon} size={18} color={selected ? colors.goldLight : colors.textMuted} weight={selected ? 'fill' : 'regular'} />
            <Text style={[typography.bodyStrong, { color: fg }]}>{opt.label}</Text>
            {opt.key === 'fresh' && !!freshBadge && freshBadge > 0 && (
              <View style={[styles.badge, { backgroundColor: selected ? colors.headerGlass : colors.goldSoft }]}>
                <Text style={[typography.micro, { color: selected ? colors.goldLight : colors.goldDeep }]}>
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
                colors={gradients.navy}
                start={{ x: 0, y: 0 }}
                end={{ x: 0, y: 1 }}
                style={[styles.pill, shadowStyle('sm', isDark ? colors.shadowDark : colors.navy)]}
              >
                <View style={[styles.pillHighlight, { backgroundColor: colors.headerGlassBorder }]} />
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
    minHeight: 44,
    justifyContent: 'center',
  },
  pillHighlight: {
    position: 'absolute',
    top: 0,
    left: 22,
    right: 22,
    height: 1,
  },
  inner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingHorizontal: spacing.md,
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
