/**
 * FormSheet + Field — แผ่นล่างสำหรับกรอกข้อมูลสั้นๆ (เหตุผลยกเลิก, เลขพัสดุ, รีวิว, เลือกที่อยู่)
 *
 * - ดันขึ้นพ้นคีย์บอร์ด (KeyboardAvoidingView — ใช้ได้ทั้ง edge-to-edge ของ Android)
 * - ปุ่มยืนยันคืน Promise ได้ → Button3D หมุนโหลดและกันกดซ้ำให้เอง
 * - ระหว่างบันทึกปิดแผ่นไม่ได้ (กันกดปิดแล้วงานยังวิ่งอยู่)
 * - icon: ชื่อไอคอน — หน้าเก่าที่ยังส่งอีโมจิจะถูกแปลงเป็นไอคอนเส้นให้ (ไม่รู้จัก = ไม่แสดง)
 * - Field: ช่องกรอกพื้นยุบ มุม 14 สูง ≥ 48 · โฟกัส = ขอบทอง · ผิดพลาด = ขอบแดง + ไอคอนเตือน
 */

import React, { useState } from 'react';
import {
  KeyboardAvoidingView,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  View,
  type StyleProp,
  type TextInputProps,
  type ViewStyle,
} from 'react-native';
import { Text, TextInput } from '@/components/ui/Text';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Button3D, Icon, iconFromLegacy, type Button3DVariant } from '@/components/ui';
import { useTheme, radii, spacing, typography, withAlpha } from '@/theme';
import { IconTile } from './ShopKit';

export interface FormSheetProps {
  visible: boolean;
  title: string;
  description?: string;
  /** ชื่อไอคอน (อีโมจิเดิมแปลงให้) */
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
  const { colors, isDark } = useTheme();
  const insets = useSafeAreaInsets();
  // อีโมจิที่ไม่รู้จัก → ไม่วาดกล่องไอคอน (ไม่ให้อีโมจิหลุดกลับมา)
  const iconName = iconFromLegacy(icon);

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
            {
              backgroundColor: colors.card,
              borderColor: isDark ? colors.border : 'transparent',
              paddingBottom: Math.max(insets.bottom, spacing.lg) + spacing.sm,
              boxShadow: `0px -18px 40px -20px ${withAlpha(colors.shadowDark, isDark ? 0.95 : 0.5)}`,
            },
          ]}
        >
          <View style={[styles.handle, { backgroundColor: colors.border }]} />
          <ScrollView bounces={false} keyboardShouldPersistTaps="handled" contentContainerStyle={styles.scroll}>
            <View style={styles.titleRow}>
              {!!iconName && <IconTile icon={iconName} tone="gold" size={44} weight="fill" />}
              <View style={styles.flex}>
                <Text accessibilityRole="header" style={[typography.h2, { color: colors.textStrong }]}>
                  {title}
                </Text>
                {!!description && (
                  <Text style={[typography.bodySm, styles.description, { color: colors.textMuted }]}>{description}</Text>
                )}
              </View>
            </View>
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

export const Field: React.FC<FieldProps> = ({
  label,
  error,
  hint,
  required,
  containerStyle,
  style,
  multiline,
  onFocus,
  onBlur,
  ...rest
}) => {
  const { colors } = useTheme();
  const [focused, setFocused] = useState(false);

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
            borderColor: error ? colors.danger : focused ? colors.gold : colors.border,
          },
          style,
        ]}
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
      {error ? (
        <View style={styles.messageRow}>
          <Icon name="warning-circle" size={14} color={colors.danger} weight="fill" style={styles.messageIcon} />
          <Text style={[typography.caption, styles.flex, { color: colors.danger }]}>{error}</Text>
        </View>
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
    borderWidth: 1,
    borderBottomWidth: 0,
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
    gap: spacing.md,
  },
  description: {
    marginTop: 2,
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
    fontWeight: '600',
  },
  input: {
    minHeight: 48,
    borderRadius: 14,
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
  messageRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 5,
    marginTop: spacing.xs,
  },
  messageIcon: {
    marginTop: 1.5,
  },
});

export default FormSheet;
