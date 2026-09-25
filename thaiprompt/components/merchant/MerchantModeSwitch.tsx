/**
 * MerchantModeSwitch — สลับ "ร้านค้าออนไลน์" (e-commerce) ↔ "ร้านตลาดสด" (รถเข็น/ตลาดนัด/ร้านอาหาร)
 *
 * สองร้านเป็นคนละระบบ (ออเดอร์/สินค้า/รายได้แยกกัน) — ปุ่มนี้แค่พาไปอีกหน้าจอหนึ่ง
 * ใช้ router.replace เพื่อไม่ให้สลับไปมาแล้วกองหน้าจอซ้อนกัน
 */

import React from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { router } from 'expo-router';
import { Chip } from '@/components/ui';
import { spacing } from '@/theme';

export type MerchantMode = 'shop' | 'taladsod';

export interface MerchantModeSwitchProps {
  current: MerchantMode;
  /** จำนวนออเดอร์ใหม่ของตลาดสด (แสดงบนปุ่ม) */
  taladsodBadge?: number;
  style?: StyleProp<ViewStyle>;
}

export const MerchantModeSwitch: React.FC<MerchantModeSwitchProps> = ({ current, taladsodBadge, style }) => (
  <View
    style={[styles.row, style]}
    accessibilityRole="tablist"
    accessibilityLabel="เลือกร้านที่จะจัดการ"
  >
    <Chip
      label="ร้านค้าออนไลน์"
      icon="🏪"
      selected={current === 'shop'}
      onPress={() => {
        if (current !== 'shop') router.replace('/merchant' as never);
      }}
      accessibilityLabel="ร้านค้าออนไลน์"
    />
    <Chip
      label="ร้านตลาดสด"
      icon="🥬"
      selected={current === 'taladsod'}
      count={taladsodBadge}
      onPress={() => {
        if (current !== 'taladsod') router.replace('/merchant/taladsod' as never);
      }}
      accessibilityLabel="ร้านตลาดสด"
    />
  </View>
);

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.lg,
  },
});

export default MerchantModeSwitch;
