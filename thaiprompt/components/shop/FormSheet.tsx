/**
 * FormSheet + Field — แผ่นล่างสำหรับกรอกข้อมูลสั้นๆ (เหตุผลยกเลิก, เลขพัสดุ, รีวิว, เลือกที่อยู่)
 *
 * - ดันขึ้นพ้นคีย์บอร์ด (KeyboardAvoidingView — ใช้ได้ทั้ง edge-to-edge ของ Android)
 * - ปุ่มยืนยันคืน Promise ได้ → Button3D หมุนโหลดและกันกดซ้ำให้เอง
 * - ระหว่างบันทึกปิดแผ่นไม่ได้ (กันกดปิดแล้วงานยังวิ่งอยู่)
 */

import React from 'react';
import {
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
  type StyleProp,
  type TextInputProps,
  type ViewStyle,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Button3D, type Button3DVariant } from '@/components/ui';
import { useTheme, radii, shadowStyle, spacing, typography, palette } from '@/theme';

export interface FormSheetProps {
  visible: boolean;
  title: string;
  description?: string;
  icon?: string;
  children?: React.ReactNode;
  /** ไม่ส่ง = ไม่มีปุ่มยืนยัน (เช่น แผ่นเลือกรายการ) */
  submitLabel?: string;
  submitVariant?: Button3DVariant;
  submitDisabled?: boolean;
  onSubmit?: () => unknown;
  cancelLabel?: string;
  onClose: () => void;
  /** กำลังบันทึก — ปิดแผ่นไม่ได้ */
  busy?: boolean;
}

export const FormSheet: React.FC<FormSheetProps> = ({
  visible,
  title,
  description,
  icon,
  children,
  submitLabel,
  submitVariant = 'primary',
  submitDisabled = false,
  onSubmit,
  cancelLabel = 'ปิด',
  onClose,
  busy = false,
}) => {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();

  const close = () => {
    if (!busy) onClose();
  };

  return (
    <Modal visible={visible} transparent animationType="slide" statusBarTranslucent onRequestClose={close}>
      <KeyboardAvoidingView style={styles.root} behavior="padding">
        <Pressable
          style={[StyleSheet.absoluteFill, { backgroundColor: colors.overlay }]}
          onPress={close}
          accessibilityRole="button"
          accessibilityLabel="ปิด"
        />
        <View
          accessibilityViewIsModal
          style={[
            styles.sheet,
            { backgroundColor: colors.card, paddingBottom: Math.max(insets.bottom, spacing.lg) + spacing.sm },
            shadowStyle('lg', palette.black),
          ]}
        >
          <View style={[styles.handle, { backgroundColor: colors.border }]} />
          <ScrollView bounces={false} keyboardShouldPersistTaps="handled" contentContainerStyle={styles.scroll}>
            <View style={styles.titleRow}>
              {!!icon && <Text style={styles.icon}>{icon}</Text>}
              <Text accessibilityRole="header" style={[typography.h2, styles.flex, { color: colors.textStrong }]}>
                {title}
              </Text>
            </View>
            {!!description && (
              <Text style={[typography.bodySm, styles.description, { color: colors.textMuted }]}>{description}</Text>
            )}
            {children}
          </ScrollView>
          <View style={styles.buttons}>
            {!!submitLabel && onSubmit && (
              <Button3D
                title={submitLabel}
                variant={submitVariant}
                size="lg"
                fullWidth
                disabled={submitDisabled}
                loading={busy}
                onPress={onSubmit}
              />
            )}
            <Button3D title={cancelLabel} variant="ghost" size="md" fullWidth disabled={busy} onPress={close} />
          </View>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
};

// =====================================================
// Field — ช่องกรอกแบบยุบลง + ป้ายชื่อ + ข้อความผิดพลาด
// =====================================================

export interface FieldProps extends TextInputProps {
  label: string;
  error?: string | null;
  hint?: string;
  required?: boolean;
  containerStyle?: StyleProp<ViewStyle>;
}

export const Field: React.FC<FieldProps> = ({ label, error, hint, required, containerStyle, style, multiline, ...rest }) => {
  const { colors } = useTheme();
  return (
    <View style={[styles.field, containerStyle]}>
      <Text style={[typography.caption, styles.label, { color: colors.textMuted }]}>
        {label}
        {required ? <Text style={{ color: colors.danger }}> *</Text> : null}
      </Text>
      <TextInput
        placeholderTextColor={colors.textFaint}
        accessibilityLabel={label}
        multiline={multiline}
        style={[
          typography.body,
          styles.input,
          multiline && styles.multiline,
          {
            backgroundColor: colors.inset,
            color: colors.textStrong,
            borderColor: error ? colors.danger : colors.border,
          },
          style,
        ]}
        {...rest}
      />
      {error ? (
        <Text style={[typography.caption, styles.message, { color: colors.danger }]}>{error}</Text>
      ) : hint ? (
        <Text style={[typography.caption, styles.message, { color: colors.textFaint }]}>{hint}</Text>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  root: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  flex: {
    flex: 1,
  },
  sheet: {
    borderTopLeftRadius: radii.xxl,
    borderTopRightRadius: radii.xxl,
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.sm,
    maxHeight: '92%',
  },
  handle: {
    alignSelf: 'center',
    width: 44,
    height: 5,
    borderRadius: 3,
    marginBottom: spacing.lg,
  },
  scroll: {
    paddingBottom: spacing.md,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  icon: {
    fontSize: 26,
  },
  description: {
    marginTop: spacing.xs,
    marginBottom: spacing.sm,
  },
  buttons: {
    gap: spacing.xs,
    paddingTop: spacing.sm,
  },
  field: {
    marginTop: spacing.md,
  },
  label: {
    marginBottom: spacing.xs,
  },
  input: {
    minHeight: 48,
    borderRadius: radii.md,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  multiline: {
    minHeight: 96,
    textAlignVertical: 'top',
  },
  message: {
    marginTop: spacing.xs,
  },
});

export default FormSheet;
