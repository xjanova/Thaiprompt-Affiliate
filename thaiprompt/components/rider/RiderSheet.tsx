/**
 * RiderSheet — bottom sheet ของขั้นตอนงานไรเดอร์ (ยืนยันรับของ / ส่งสำเร็จ / ส่งไม่สำเร็จ / คืนงาน)
 *
 * - แตะพื้นหลังหรือปุ่มย้อนกลับ = ปิด (ยกเว้นกำลังส่งข้อมูล — busy)
 * - ปุ่มด้านล่างส่งมาทาง footer (ใช้ Button3D ซึ่งกันกดซ้ำให้เอง)
 */

import React from 'react';
import { KeyboardAvoidingView, Modal, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import Animated, { SlideInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme, spacing, radii, typography, shadowStyle } from '@/theme';

export interface RiderSheetProps {
  visible: boolean;
  title: string;
  subtitle?: string;
  icon?: string;
  onClose: () => void;
  /** กำลังส่งข้อมูล — ห้ามปิด */
  busy?: boolean;
  children?: React.ReactNode;
  footer?: React.ReactNode;
}

export const RiderSheet: React.FC<RiderSheetProps> = ({
  visible,
  title,
  subtitle,
  icon,
  onClose,
  busy = false,
  children,
  footer,
}) => {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();

  const close = () => {
    if (!busy) onClose();
  };

  return (
    <Modal visible={visible} transparent animationType="fade" statusBarTranslucent onRequestClose={close}>
      {/* padding ทั้งสองแพลตฟอร์ม: Modal ของ Android เป็น edge-to-edge → adjustResize ไม่ดันช่องพิมพ์ขึ้นให้ */}
      <KeyboardAvoidingView style={styles.root} behavior="padding">
        <Pressable
          style={[StyleSheet.absoluteFill, { backgroundColor: colors.overlay }]}
          onPress={close}
          accessibilityRole="button"
          accessibilityLabel="ปิด"
        />
        <Animated.View
          entering={SlideInDown.springify().damping(18)}
          accessibilityViewIsModal
          style={[
            styles.sheet,
            { backgroundColor: colors.card, paddingBottom: Math.max(insets.bottom, spacing.lg) + spacing.sm },
            shadowStyle('lg', '#000000'),
          ]}
        >
          <View style={[styles.handle, { backgroundColor: colors.border }]} />
          <View style={styles.header}>
            {!!icon && (
              <View style={[styles.iconCircle, { backgroundColor: colors.goldSoft }]}>
                <Text style={styles.icon}>{icon}</Text>
              </View>
            )}
            <View style={styles.flex}>
              <Text accessibilityRole="header" style={[typography.h2, { color: colors.textStrong }]}>
                {title}
              </Text>
              {!!subtitle && <Text style={[typography.bodySm, { color: colors.textMuted }]}>{subtitle}</Text>}
            </View>
          </View>

          <ScrollView
            bounces={false}
            keyboardShouldPersistTaps="handled"
            keyboardDismissMode="on-drag"
            contentContainerStyle={styles.scroll}
          >
            {children}
          </ScrollView>

          {!!footer && <View style={styles.footer}>{footer}</View>}
        </Animated.View>
      </KeyboardAvoidingView>
    </Modal>
  );
};

const styles = StyleSheet.create({
  root: {
    flex: 1,
    justifyContent: 'flex-end',
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
    marginBottom: spacing.md,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginBottom: spacing.md,
  },
  iconCircle: {
    width: 48,
    height: 48,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },
  icon: {
    fontSize: 24,
  },
  flex: {
    flex: 1,
  },
  scroll: {
    paddingBottom: spacing.md,
    gap: spacing.md,
  },
  footer: {
    gap: spacing.sm,
    paddingTop: spacing.sm,
  },
});

export default RiderSheet;
