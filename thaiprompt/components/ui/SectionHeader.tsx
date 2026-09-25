/**
 * SectionHeader — หัวข้อส่วน + ลิงก์ "ดูทั้งหมด" ด้านขวา
 *
 * @example
 * <SectionHeader title="ร้านใกล้คุณ" subtitle="ส่งไวภายใน 30 นาที" actionLabel="ดูทั้งหมด" onAction={openAll} />
 */

import React from 'react';
import { Pressable, StyleSheet, Text, View, type StyleProp, type ViewStyle } from 'react-native';
import { useTheme, spacing, typography } from '@/theme';

export interface SectionHeaderProps {
  title: string;
  subtitle?: string;
  /** emoji หรือ element หน้าหัวข้อ */
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
        {icon !== undefined && icon !== null && (
          typeof icon === 'string' ? <Text style={styles.icon}>{icon}</Text> : icon
        )}
        <View style={styles.texts}>
          <Text
            accessibilityRole="header"
            numberOfLines={1}
            style={[typography.h2, { color: colors.textStrong }]}
          >
            {title}
          </Text>
          {!!subtitle && (
            <Text numberOfLines={2} style={[typography.bodySm, { color: colors.textMuted }]}>
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
          <Text style={[typography.caption, styles.actionText, { color: colors.goldDeep }]}>
            {actionLabel} ›
          </Text>
        </Pressable>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
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
  icon: {
    fontSize: 20,
  },
  action: {
    paddingLeft: spacing.md,
    paddingVertical: spacing.xs,
  },
  actionText: {
    fontWeight: '700',
  },
});

export default SectionHeader;
