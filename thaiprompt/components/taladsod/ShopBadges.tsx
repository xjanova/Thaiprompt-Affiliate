/**
 * ป้ายสถานะร้านตลาดสด — เปิดอยู่/ปิดอยู่ + รถเข็น (ร้านเคลื่อนที่)
 */

import React from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { Pill } from '@/components/ui';
import { useTheme, spacing } from '@/theme';

export const OpenPill: React.FC<{ isOpen: boolean; size?: 'sm' | 'md'; style?: StyleProp<ViewStyle> }> = ({
  isOpen,
  size = 'sm',
  style,
}) => {
  const { colors } = useTheme();
  const dot = (
    <View
      style={[
        styles.dot,
        { backgroundColor: isOpen ? colors.success : colors.textFaint },
        size === 'md' && styles.dotMd,
      ]}
    />
  );
  return <Pill label={isOpen ? 'เปิดอยู่' : 'ปิดอยู่'} tone={isOpen ? 'success' : 'neutral'} icon={dot} size={size} style={style} />;
};

export const MobilePill: React.FC<{ size?: 'sm' | 'md'; style?: StyleProp<ViewStyle> }> = ({ size = 'sm', style }) => (
  <Pill label="รถเข็น" icon="🛺" tone="gold" size={size} style={style} />
);

/** แถวป้าย: เปิด/ปิด + รถเข็น (ถ้าเป็นร้านเคลื่อนที่) */
export const ShopStatusRow: React.FC<{
  isOpen: boolean;
  isMobile: boolean;
  size?: 'sm' | 'md';
  style?: StyleProp<ViewStyle>;
}> = ({ isOpen, isMobile, size = 'sm', style }) => (
  <View style={[styles.row, style]}>
    <OpenPill isOpen={isOpen} size={size} />
    {isMobile && <MobilePill size={size} />}
  </View>
);

const styles = StyleSheet.create({
  dot: {
    width: 7,
    height: 7,
    borderRadius: 4,
    marginRight: 2,
  },
  dotMd: {
    width: 9,
    height: 9,
    borderRadius: 5,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: spacing.xs,
  },
});
