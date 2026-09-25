/**
 * SectionHeader — หัวข้อส่วน + ลิงก์ "ดูทั้งหมด" ด้านขวา
 *
 * หัวข้อแบบเรียบหรู: ตัวหนา Anuphan + คำอธิบายสีรอง + ลิงก์ทองพร้อมลูกศร
 * icon: ส่งชื่อไอคอนได้ (เช่น "storefront") — อีโมจิเดิมจะถูกแปลงเป็นไอคอนเส้นสีทอง
 *
 * @example
 * <SectionHeader title="ร้านใกล้คุณ" subtitle="ส่งไวภายใน 30 นาที" actionLabel="ดูทั้งหมด" onAction={openAll} />
 */

import React from 'react';
import { Pressable, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { useTheme, spacing, typography } from '@/theme';
import { Text } from './Text';
import { Icon, IconSlot } from './Icon';

export interface SectionHeaderProps {
  title: string;
  subtitle?: string;
  /** ชื่อไอคอน / อีโมจิเดิม / element หน้าหัวข้อ */
  icon?: React.ReactNode;
  actionLabel?: string;
  onAction?: () => void;
  style?: StyleProp<ViewStyle>;
}

export const SectionHeader: React.FC<SectionHeaderProps> = ({
  title,
  subtitle,
  icon,
  actionLabel,
  onAction,
  style,
}) => {
  const { colors } = useTheme();

  return (
    <View style={[styles.row, style]}>
      <View style={styles.left}>
        <IconSlot icon={icon} size={19} color={colors.goldDeep} weight="fill" />
        <View style={styles.texts}>
          <Text
            accessibilityRole="header"
            numberOfLines={1}
            style={[typography.h2, { color: colors.textStrong }]}
          >
            {title}
          </Text>
          {!!subtitle && (
            <Text numberOfLines={2} style={[typography.bodySm, { color: colors.textMuted, marginTop: 1 }]}>
              {subtitle}
            </Text>
          )}
        </View>
      </View>

      {!!actionLabel && !!onAction && (
        <Pressable
          onPress={onAction}
          accessibilityRole="button"
          accessibilityLabel={actionLabel}
          hitSlop={10}
          style={({ pressed }) => [styles.action, { opacity: pressed ? 0.6 : 1 }]}
        >
          <Text style={[typography.caption, styles.actionText, { color: colors.goldDeep }]}>{actionLabel}</Text>
          <Icon name="caret-right" size={14} color={colors.goldDeep} weight="bold" />
        </Pressable>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  left: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
    gap: spacing.sm,
  },
  texts: {
    flex: 1,
  },
  action: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 2,
    paddingLeft: spacing.md,
    paddingVertical: spacing.xs,
  },
  actionText: {
    fontSize: 13,
    fontWeight: '600',
  },
});

export default SectionHeader;
