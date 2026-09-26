/**
 * SkeletonBlock — แท่งโครงร่างกะพริบเบาๆ ระหว่างโหลดครั้งแรก (แทนวงหมุนเต็มจอ)
 *
 * - สีจากธีม (colors.inset) รองรับโหมดมืด
 * - กะพริบด้วย Animated.loop (native driver) และหยุดเองเมื่อถูกถอดออกจากจอ
 * - ซ่อนจาก screen reader ทั้งชิ้น — หน้าจอประกาศ "กำลังโหลด" เองผ่าน SkeletonCard
 *
 * @example
 * <SkeletonCard lines={3} />
 * <SkeletonBlock width="60%" height={18} />
 */

import React, { useEffect, useRef } from 'react';
import { Animated, StyleSheet, View, type DimensionValue, type StyleProp, type ViewStyle } from 'react-native';
import { Card3D } from '@/components/ui';
import { useTheme, radii, spacing } from '@/theme';

export interface SkeletonBlockProps {
  width?: DimensionValue;
  height?: number;
  radius?: number;
  style?: StyleProp<ViewStyle>;
}

export const SkeletonBlock: React.FC<SkeletonBlockProps> = ({ width = '100%', height = 14, radius = 8, style }) => {
  const { colors } = useTheme();
  const opacity = useRef(new Animated.Value(0.55)).current;

  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, { toValue: 1, duration: 650, useNativeDriver: true }),
        Animated.timing(opacity, { toValue: 0.55, duration: 650, useNativeDriver: true }),
      ])
    );
    loop.start();
    return () => loop.stop();
  }, [opacity]);

  return (
    <Animated.View
      importantForAccessibility="no-hide-descendants"
      accessibilityElementsHidden
      style={[{ width, height, borderRadius: radius, backgroundColor: colors.inset, opacity }, style]}
    />
  );
};

export interface SkeletonCardProps {
  /** จำนวนบรรทัดในการ์ด (ค่าเริ่มต้น 3) */
  lines?: number;
  /** มีกล่องไอคอน/รูปด้านซ้าย */
  withTile?: boolean;
  style?: StyleProp<ViewStyle>;
}

/** การ์ดโครงร่าง — ใช้เรียงต่อกันแทนเนื้อหาตอนโหลดครั้งแรก */
export const SkeletonCard: React.FC<SkeletonCardProps> = ({ lines = 3, withTile = false, style }) => (
  <Card3D padding={spacing.lg} shadow="sm" style={style} accessibilityLabel="กำลังโหลด">
    <View style={styles.row}>
      {withTile && <SkeletonBlock width={44} height={44} radius={15} />}
      <View style={styles.lines}>
        {Array.from({ length: Math.max(1, lines) }).map((_, i) => (
          <SkeletonBlock key={i} width={i === 0 ? '62%' : i === lines - 1 ? '40%' : '88%'} height={i === 0 ? 18 : 13} />
        ))}
      </View>
    </View>
  </Card3D>
);

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  lines: {
    flex: 1,
    gap: spacing.sm,
  },
});

/** ความโค้งมาตรฐานของรูปสินค้าในโครงร่าง */
export const SKELETON_PHOTO_RADIUS = radii.lg;
