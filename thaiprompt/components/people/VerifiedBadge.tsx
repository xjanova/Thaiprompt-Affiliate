/**
 * VerifiedBadge — ป้ายทอง "ยืนยันตัวตนแล้ว" (ตราดอกจันทองมีเครื่องหมายถูกสีเข้ม ตามแบบ Profile.png)
 *
 * ความน่าเชื่อถือมาจากป้ายนี้ ไม่ใช่จากรูปโปรไฟล์ (รูปโปรไฟล์เป็นรูปอะไรก็ได้ที่ผู้ใช้เลือก)
 * แสดงเฉพาะเมื่อ server ส่ง verified = true มาเท่านั้น — ห้ามเดาเอง
 *
 * @example
 * <View style={{ flexDirection: 'row', alignItems: 'center', gap: 4 }}>
 *   <Text>{rider.display_name}</Text>
 *   {rider.verified && <VerifiedBadge size={16} />}
 * </View>
 */

import React, { memo } from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { Icon } from '@/components/ui/Icon';
import { Text } from '@/components/ui/Text';
import { useTheme, typography } from '@/theme';

export interface VerifiedBadgeProps {
  /** ขนาดตรา (ค่าเริ่มต้น 16) */
  size?: number;
  /** แสดงคำว่า "ยืนยันตัวตนแล้ว" ต่อท้าย */
  withLabel?: boolean;
  /** วงขอบรอบตรา (ใช้ตอนวางทับมุมรูป) — ส่งสีพื้นที่วางอยู่ */
  ringColor?: string;
  style?: StyleProp<ViewStyle>;
}

/** ค่าจริงของ verified จาก server (true/1/"1") */
export const isVerifiedFlag = (value: unknown): boolean => value === true || value === 1 || value === '1';

export const VerifiedBadge: React.FC<VerifiedBadgeProps> = memo(({ size = 16, withLabel = false, ringColor, style }) => {
  const { colors } = useTheme();
  const ring = ringColor ? Math.max(1.5, Math.round(size * 0.12)) : 0;
  const outer = size + ring * 2;

  const seal = (
    <View
      style={[
        styles.center,
        { width: outer, height: outer, borderRadius: outer / 2 },
        ringColor ? { backgroundColor: ringColor } : null,
      ]}
    >
      {/* วงสีเข้มด้านหลัง → เครื่องหมายถูกที่เจาะในตราทองเป็นสีเข้ม */}
      <View
        style={[
          styles.inner,
          { width: size * 0.56, height: size * 0.56, borderRadius: size, backgroundColor: colors.textOnGold },
        ]}
      />
      <Icon name="seal-check" size={size} color={colors.gold} weight="fill" />
    </View>
  );

  if (!withLabel) {
    return (
      <View style={style} accessible accessibilityRole="image" accessibilityLabel="ยืนยันตัวตนแล้ว">
        {seal}
      </View>
    );
  }

  return (
    <View style={[styles.row, style]} accessible accessibilityLabel="ยืนยันตัวตนแล้ว">
      {seal}
      <Text style={[typography.caption, { color: colors.goldDeep }]}>ยืนยันตัวตนแล้ว</Text>
    </View>
  );
});

VerifiedBadge.displayName = 'VerifiedBadge';

const styles = StyleSheet.create({
  center: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  inner: {
    position: 'absolute',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
});

export default VerifiedBadge;
