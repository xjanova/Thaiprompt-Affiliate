/**
 * AuthField — ช่องกรอกหน้าเข้าสู่ระบบ/สมัครสมาชิก (ไอคอนซ้าย + ปุ่มขวา + ข้อความผิดพลาดใต้ช่อง)
 *
 * ส่ง ref ต่อให้ TextInput ได้ (ใช้เลื่อนโฟกัสไปช่องถัดไปตอนกด "ถัดไป" บนคีย์บอร์ด)
 */

import React, { forwardRef } from 'react';
import { StyleSheet, Text, TextInput, View, type TextInputProps } from 'react-native';
import { useTheme, radii, spacing, typography } from '@/theme';

export interface AuthFieldProps extends TextInputProps {
  label: string;
  icon: string;
  error?: string | null;
  hint?: string;
  right?: React.ReactNode;
}

export const AuthField = forwardRef<TextInput, AuthFieldProps>(({ label, icon, error, hint, right, style, ...rest }, ref) => {
  const { colors } = useTheme();
  return (
    <View style={styles.field}>
      <Text style={[typography.caption, styles.label, { color: colors.textMuted }]}>{label}</Text>
      <View style={[styles.box, { backgroundColor: colors.inset, borderColor: error ? colors.danger : colors.border }]}>
        <Text style={styles.icon}>{icon}</Text>
        <TextInput
          ref={ref}
          placeholderTextColor={colors.textFaint}
          accessibilityLabel={label}
          style={[typography.body, styles.input, { color: colors.textStrong }, style]}
          {...rest}
        />
        {right}
      </View>
      {error ? (
        <Text style={[typography.caption, styles.message, { color: colors.danger }]} accessibilityRole="alert">
          {error}
        </Text>
      ) : hint ? (
        <Text style={[typography.caption, styles.message, { color: colors.textFaint }]}>{hint}</Text>
      ) : null}
    </View>
  );
});

AuthField.displayName = 'AuthField';

const styles = StyleSheet.create({
  field: {
    marginTop: spacing.md,
  },
  label: {
    marginBottom: spacing.xs,
  },
  box: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderRadius: radii.md,
    paddingHorizontal: spacing.md,
    minHeight: 52,
    gap: spacing.sm,
  },
  icon: {
    fontSize: 18,
  },
  input: {
    flex: 1,
    paddingVertical: spacing.sm,
  },
  message: {
    marginTop: spacing.xs,
  },
});

export default AuthField;
