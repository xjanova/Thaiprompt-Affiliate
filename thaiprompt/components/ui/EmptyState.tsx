/**
 * EmptyState — หน้าว่าง / โหลดไม่สำเร็จ / ออฟไลน์ (แทนข้อมูลสมมติทุกที่)
 *
 * variant:
 *   - empty   (ค่าเริ่มต้น) ยังไม่มีข้อมูล
 *   - error   โหลดไม่สำเร็จ → ส่ง onAction เป็นปุ่ม "ลองใหม่"
 *   - offline ไม่มีอินเทอร์เน็ต
 *
 * illustration = ช่องใส่รูป/SVG เอง (ไม่ส่ง = ใช้ emoji จาก icon)
 *
 * @example
 * <EmptyState title="ยังไม่มีคำสั่งซื้อ" message="ลองเลือกของอร่อยในตลาดสดดูไหม" actionLabel="ไปช้อปเลย" onAction={goShop} />
 * <EmptyState variant="error" onAction={reload} />
 */

import React from 'react';
import { StyleSheet, Text, View, type StyleProp, type ViewStyle } from 'react-native';
import Animated, { FadeIn } from 'react-native-reanimated';
import { useTheme, spacing, typography, clayShadowStyle } from '@/theme';
import { Button3D } from './Button3D';

export type EmptyStateVariant = 'empty' | 'error' | 'offline';

export interface EmptyStateProps {
  title?: string;
  message?: string;
  /** emoji ในวงกลม (ใช้เมื่อไม่ส่ง illustration) */
  icon?: string;
  /** ช่องใส่ภาพประกอบเอง (Image / SVG) */
  illustration?: React.ReactNode;
  variant?: EmptyStateVariant;
  actionLabel?: string;
  /** คืน Promise ได้ — ปุ่มจะหมุนโหลดให้เอง */
  onAction?: () => unknown;
  secondaryActionLabel?: string;
  onSecondaryAction?: () => unknown;
  /** แบบย่อ (ใช้ในการ์ด/ส่วนย่อยของหน้า) */
  compact?: boolean;
  style?: StyleProp<ViewStyle>;
}

const DEFAULTS: Record<EmptyStateVariant, { icon: string; title: string; message: string; action: string }> = {
  empty: {
    icon: '🗂️',
    title: 'ยังไม่มีข้อมูล',
    message: 'เมื่อมีรายการใหม่ จะแสดงที่นี่',
    action: 'รีเฟรช',
  },
  error: {
    icon: '😵',
    title: 'โหลดข้อมูลไม่สำเร็จ',
    message: 'ระบบขัดข้องชั่วคราว ลองใหม่อีกครั้งนะ',
    action: 'ลองใหม่',
  },
  offline: {
    icon: '📡',
    title: 'ไม่มีอินเทอร์เน็ต',
    message: 'ตรวจสอบการเชื่อมต่อ แล้วลองใหม่อีกครั้ง',
    action: 'ลองใหม่',
  },
};

export const EmptyState: React.FC<EmptyStateProps> = ({
  title,
  message,
  icon,
  illustration,
  variant = 'empty',
  actionLabel,
  onAction,
  secondaryActionLabel,
  onSecondaryAction,
  compact = false,
  style,
}) => {
  const { colors } = useTheme();
  const d = DEFAULTS[variant];
  const circle = compact ? 64 : 96;

  return (
    <Animated.View
      entering={FadeIn.duration(220)}
      style={[styles.container, compact ? styles.compact : styles.full, style]}
      accessibilityRole={variant === 'empty' ? 'summary' : 'alert'}
    >
      {illustration ? (
        <View style={styles.illustration}>{illustration}</View>
      ) : (
        <View
          style={[
            styles.circle,
            {
              width: circle,
              height: circle,
              borderRadius: circle / 2,
              backgroundColor: variant === 'error' ? colors.dangerSoft : colors.goldSoft,
            },
            clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
          ]}
        >
          <Text style={{ fontSize: compact ? 30 : 46 }}>{icon || d.icon}</Text>
        </View>
      )}

      <Text style={[compact ? typography.h3 : typography.h2, styles.center, { color: colors.textStrong }]}>
        {title || d.title}
      </Text>

      {(message || !title) && (
        <Text style={[typography.body, styles.center, styles.message, { color: colors.textMuted }]}>
          {message || d.message}
        </Text>
      )}

      {!!onAction && (
        <Button3D
          title={actionLabel || d.action}
          onPress={onAction}
          variant={variant === 'empty' ? 'primary' : 'secondary'}
          size={compact ? 'sm' : 'md'}
          icon={variant === 'empty' ? undefined : '🔄'}
          style={styles.action}
        />
      )}

      {!!onSecondaryAction && !!secondaryActionLabel && (
        <Button3D
          title={secondaryActionLabel}
          onPress={onSecondaryAction}
          variant="ghost"
          size="sm"
          style={styles.secondary}
        />
      )}
    </Animated.View>
  );
};

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.xxl,
  },
  full: {
    flex: 1,
    paddingVertical: spacing.xxxl * 1.5,
  },
  compact: {
    paddingVertical: spacing.xl,
  },
  illustration: {
    marginBottom: spacing.lg,
    alignItems: 'center',
  },
  circle: {
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
  },
  center: {
    textAlign: 'center',
  },
  message: {
    marginTop: spacing.xs,
    maxWidth: 320,
  },
  action: {
    marginTop: spacing.xl,
    minWidth: 180,
  },
  secondary: {
    marginTop: spacing.sm,
  },
});

export default EmptyState;
