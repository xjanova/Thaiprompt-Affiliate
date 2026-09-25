/**
 * MerchantModeSwitch — สลับ "ร้านค้าออนไลน์" (e-commerce) ↔ "ร้านตลาดสด" (รถเข็น/ตลาดนัด/ร้านอาหาร)
 *
 * สองร้านเป็นคนละระบบ (ออเดอร์/สินค้า/รายได้แยกกัน) — ปุ่มนี้แค่พาไปอีกหน้าจอหนึ่ง
 * ใช้ router.replace เพื่อไม่ให้สลับไปมาแล้วกองหน้าจอซ้อนกัน
 *
 * หน้าตา: แถบสลับเต็มความกว้าง (segmented) — ฝั่งที่เลือก = เม็ดน้ำเงินกรมท่าตัวทอง
 * ตัวเลขออเดอร์ใหม่ของตลาดสด = ป้ายทองมุมขวา
 */

import React from 'react';
import { Pressable, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { router } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import { Icon, selectionHaptic, type IconName } from '@/components/ui';
import { useTheme, radii, shadowStyle, spacing } from '@/theme';

export type MerchantMode = 'shop' | 'taladsod';

export interface MerchantModeSwitchProps {
  current: MerchantMode;
  /** จำนวนออเดอร์ใหม่ของตลาดสด (แสดงบนปุ่ม) */
  taladsodBadge?: number;
  style?: StyleProp<ViewStyle>;
}

interface SegmentProps {
  label: string;
  icon: IconName;
  selected: boolean;
  count?: number;
  onPress: () => void;
}

/** ปุ่มหนึ่งฝั่งของแถบสลับ (คงบทบาท/สถานะแบบ Chip เดิม: button + selected) */
const Segment: React.FC<SegmentProps> = ({ label, icon, selected, count, onPress }) => {
  const { colors, gradients } = useTheme();
  const fg = selected ? colors.goldLight : colors.text;

  return (
    <Pressable
      onPress={() => {
        selectionHaptic();
        onPress();
      }}
      accessibilityRole="button"
      accessibilityLabel={label}
      accessibilityState={{ selected }}
      hitSlop={4}
      style={({ pressed }) => [styles.segment, { opacity: pressed ? 0.85 : 1 }]}
    >
      {selected && (
        <LinearGradient
          colors={gradients.navy}
          start={{ x: 0, y: 0 }}
          end={{ x: 0, y: 1 }}
          style={[StyleSheet.absoluteFill, styles.segmentFill, shadowStyle('sm', colors.shadowDark)]}
          pointerEvents="none"
        />
      )}
      <Icon name={icon} size={18} color={selected ? colors.goldLight : colors.goldDeep} weight={selected ? 'fill' : 'regular'} />
      <Text numberOfLines={1} style={[styles.label, { color: fg }]}>
        {label}
      </Text>
      {typeof count === 'number' && count > 0 && (
        <LinearGradient colors={gradients.primary} style={styles.count}>
          <Text style={[styles.countText, { color: colors.textOnGold }]}>{count > 99 ? '99+' : count}</Text>
        </LinearGradient>
      )}
    </Pressable>
  );
};

export const MerchantModeSwitch: React.FC<MerchantModeSwitchProps> = ({ current, taladsodBadge, style }) => {
  const { colors } = useTheme();
  return (
    <View
      style={[
        styles.track,
        { backgroundColor: colors.card, borderColor: colors.border },
        shadowStyle('sm', colors.shadowDark),
        style,
      ]}
      accessibilityRole="tablist"
      accessibilityLabel="เลือกร้านที่จะจัดการ"
    >
      <Segment
        label="ร้านค้าออนไลน์"
        icon="storefront"
        selected={current === 'shop'}
        onPress={() => {
          if (current !== 'shop') router.replace('/merchant' as never);
        }}
      />
      <Segment
        label="ร้านตลาดสด"
        icon="basket"
        selected={current === 'taladsod'}
        count={taladsodBadge}
        onPress={() => {
          if (current !== 'taladsod') router.replace('/merchant/taladsod' as never);
        }}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  track: {
    flexDirection: 'row',
    gap: spacing.xs,
    padding: spacing.xs,
    borderRadius: radii.pill,
    borderWidth: 1,
    marginBottom: spacing.lg,
  },
  segment: {
    flex: 1,
    minHeight: 44,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingHorizontal: spacing.sm,
    borderRadius: radii.pill,
  },
  segmentFill: {
    borderRadius: radii.pill,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    flexShrink: 1,
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
});

export default MerchantModeSwitch;
