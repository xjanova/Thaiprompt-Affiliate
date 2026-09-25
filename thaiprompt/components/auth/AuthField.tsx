/**
 * AuthField — ช่องกรอกหน้าเข้าสู่ระบบ/สมัครสมาชิก ธีมรอยัล น้ำเงินกรมท่า-ทอง
 * (ไอคอนซ้าย + ปุ่มขวา + ข้อความผิดพลาดใต้ช่อง)
 *
 * - icon: ชื่อไอคอน (เช่น "envelope") — อีโมจิเดิมยังส่งได้ (แปลงเป็นไอคอนเส้นให้ผ่าน IconSlot) หรือส่ง element
 * - ช่องพื้นยุบ มุม 14 สูง ≥ 52 · โฟกัส = ขอบทอง + ไอคอนทอง · ผิดพลาด = ขอบแดง
 * - ส่ง ref ต่อให้ TextInput ได้ (ใช้เลื่อนโฟกัสไปช่องถัดไปตอนกด "ถัดไป" บนคีย์บอร์ด)
 */

import React, { forwardRef, useState } from 'react';
import { StyleSheet, View, type TextInputProps } from 'react-native';
import { Text, TextInput } from '@/components/ui/Text';
import { IconSlot } from '@/components/ui/Icon';
import { useTheme, spacing, typography } from '@/theme';

export interface AuthFieldProps extends TextInputProps {
  label: string;
  /** ชื่อไอคอน / อีโมจิเดิม / element */
  icon: React.ReactNode;
  error?: string | null;
  hint?: string;
  right?: React.ReactNode;
}

export const AuthField = forwardRef<TextInput, AuthFieldProps>(
  ({ label, icon, error, hint, right, style, onFocus, onBlur, ...rest }, ref) => {
    const { colors } = useTheme();
    const [focused, setFocused] = useState(false);

    const borderColor = error ? colors.danger : focused ? colors.gold : colors.border;
    const iconColor = error ? colors.danger : focused ? colors.goldDeep : colors.textFaint;

    return (
      <View style={styles.field}>
        <Text style={[typography.caption, styles.label, { color: colors.textMuted }]}>{label}</Text>
        <View style={[styles.box, { backgroundColor: colors.inset, borderColor }]}>
          <IconSlot icon={icon} size={20} color={iconColor} weight={focused ? 'fill' : 'regular'} />
          <TextInput
            ref={ref}
            placeholderTextColor={colors.textFaint}
            selectionColor={colors.gold}
            accessibilityLabel={label}
            style={[typography.body, styles.input, { color: colors.textStrong }, style]}
            {...rest}
            onFocus={(e) => {
              setFocused(true);
              onFocus?.(e);
            }}
            onBlur={(e) => {
              setFocused(false);
              onBlur?.(e);
            }}
          />
          {right}
        </View>
        {error ? (
          <View style={styles.messageRow} accessibilityRole="alert">
            <IconSlot icon="warning-circle" size={14} color={colors.danger} />
            <Text style={[typography.caption, styles.messageText, { color: colors.danger }]}>{error}</Text>
          </View>
        ) : hint ? (
          <Text style={[typography.caption, styles.message, { color: colors.textFaint }]}>{hint}</Text>
        ) : null}
      </View>
    );
  }
);

AuthField.displayName = 'AuthField';

const styles = StyleSheet.create({
  field: {
    marginTop: spacing.md,
  },
  label: {
    fontWeight: '600',
    marginBottom: spacing.xs,
  },
  box: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderRadius: 14,
    paddingHorizontal: spacing.md,
    minHeight: 52,
    gap: spacing.sm,
  },
  input: {
    flex: 1,
    paddingVertical: spacing.sm,
  },
  message: {
    marginTop: spacing.xs,
  },
  messageRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: spacing.xs,
  },
  messageText: {
    flex: 1,
  },
});

export default AuthField;
