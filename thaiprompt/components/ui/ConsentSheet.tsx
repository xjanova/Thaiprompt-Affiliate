/**
 * ConsentSheet — bottom sheet สำหรับ "prominent disclosure" ก่อนขอสิทธิ์/ทำสิ่งสำคัญ
 *
 * ใช้ก่อน: ขอตำแหน่งเบื้องหลัง (ไรเดอร์), กล้อง, แชร์ตำแหน่งกับลูกค้า, ลบบัญชี ฯลฯ
 * Google Play ต้องการให้แสดงเหตุผลเป็นข้อความชัดเจน + ปุ่มยอมรับ/ไม่ยอมรับ ก่อนเรียก system dialog
 *
 * มีช่องพิมพ์ใน children ได้ (PIN ถอนเงิน / พิมพ์ยืนยันลบบัญชี) — sheet ดันขึ้นพ้นคีย์บอร์ดเอง
 * (behavior="padding" ทั้งสองแพลตฟอร์ม: Modal ของ Android เป็น edge-to-edge → adjustResize ไม่ทำงาน)
 *
 * @example
 * <ConsentSheet
 *   visible={show}
 *   icon="📍"
 *   title="แชร์ตำแหน่งระหว่างส่งงาน"
 *   reasons={['ลูกค้าเห็นตำแหน่งคุณเฉพาะออเดอร์ที่กำลังส่ง', 'ระบบหยุดติดตามทันทีเมื่อจบงานหรือออฟไลน์']}
 *   acceptLabel="ยอมรับและอนุญาต"
 *   onAccept={async () => { await requestPermission(); setShow(false); }}
 *   onDecline={() => setShow(false)}
 * />
 */

import React from 'react';
import { KeyboardAvoidingView, Modal, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import Animated, { SlideInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme, spacing, radii, typography, shadowStyle } from '@/theme';
import { Button3D, type Button3DVariant } from './Button3D';

export type ConsentReason = string | { icon?: string; text: string };

export interface ConsentSheetProps {
  visible: boolean;
  title: string;
  /** คำอธิบายสั้นใต้หัวข้อ */
  description?: string;
  /** เหตุผลเป็นข้อๆ */
  reasons?: ConsentReason[];
  /** emoji ใหญ่ด้านบน */
  icon?: string;
  acceptLabel?: string;
  declineLabel?: string;
  /** คืน Promise ได้ — ปุ่มยอมรับหมุนโหลดและกันกดซ้ำให้เอง */
  onAccept: () => unknown;
  onDecline: () => void;
  acceptVariant?: Exclude<Button3DVariant, 'ghost' | 'secondary'>;
  /** เนื้อหาเพิ่ม (เช่น ช่องพิมพ์ยืนยัน) */
  children?: React.ReactNode;
  /** ข้อความเล็กท้าย sheet */
  footnote?: string;
  /** ปิดปุ่มยอมรับ (เช่น ยังพิมพ์ยืนยันไม่ครบ) */
  acceptDisabled?: boolean;
  /** แตะพื้นหลัง/ปุ่มย้อนกลับเพื่อปิดได้ (ค่าเริ่มต้น true) */
  dismissible?: boolean;
}

export const ConsentSheet: React.FC<ConsentSheetProps> = ({
  visible,
  title,
  description,
  reasons = [],
  icon,
  acceptLabel = 'ยอมรับ',
  declineLabel = 'ไม่ตอนนี้',
  onAccept,
  onDecline,
  acceptVariant = 'primary',
  children,
  footnote,
  acceptDisabled = false,
  dismissible = true,
}) => {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();

  const close = () => {
    if (dismissible) onDecline();
  };

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      statusBarTranslucent
      onRequestClose={close}
    >
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
            {
              backgroundColor: colors.card,
              paddingBottom: Math.max(insets.bottom, spacing.lg) + spacing.sm,
            },
            shadowStyle('lg', '#000000'),
          ]}
        >
          <View style={[styles.handle, { backgroundColor: colors.border }]} />

          <ScrollView
            bounces={false}
            contentContainerStyle={styles.scroll}
            keyboardShouldPersistTaps="handled"
            keyboardDismissMode="on-drag"
          >
            {!!icon && (
              <View style={[styles.iconCircle, { backgroundColor: colors.goldSoft }]}>
                <Text style={styles.icon}>{icon}</Text>
              </View>
            )}

            <Text accessibilityRole="header" style={[typography.h1, styles.center, { color: colors.textStrong }]}>
              {title}
            </Text>

            {!!description && (
              <Text style={[typography.body, styles.center, styles.description, { color: colors.textMuted }]}>
                {description}
              </Text>
            )}

            {reasons.length > 0 && (
              <View style={[styles.reasons, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                {reasons.map((reason, index) => {
                  const item = typeof reason === 'string' ? { text: reason } : reason;
                  return (
                    <View key={`${index}-${item.text}`} style={styles.reasonRow}>
                      <Text style={styles.reasonIcon}>{item.icon || '•'}</Text>
                      <Text style={[typography.body, styles.reasonText, { color: colors.text }]}>{item.text}</Text>
                    </View>
                  );
                })}
              </View>
            )}

            {children}

            {!!footnote && (
              <Text style={[typography.caption, styles.center, styles.footnote, { color: colors.textFaint }]}>
                {footnote}
              </Text>
            )}
          </ScrollView>

          <View style={styles.buttons}>
            <Button3D
              title={acceptLabel}
              onPress={onAccept}
              variant={acceptVariant}
              size="lg"
              disabled={acceptDisabled}
              fullWidth
            />
            <Button3D title={declineLabel} onPress={onDecline} variant="ghost" size="md" fullWidth />
          </View>
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
    maxHeight: '90%',
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
  iconCircle: {
    alignSelf: 'center',
    width: 72,
    height: 72,
    borderRadius: 36,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.md,
  },
  icon: {
    fontSize: 36,
  },
  center: {
    textAlign: 'center',
  },
  description: {
    marginTop: spacing.sm,
  },
  reasons: {
    marginTop: spacing.lg,
    borderRadius: radii.lg,
    borderWidth: 1,
    padding: spacing.lg,
    gap: spacing.md,
  },
  reasonRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
  },
  reasonIcon: {
    fontSize: 16,
    width: 22,
    textAlign: 'center',
    lineHeight: 22,
  },
  reasonText: {
    flex: 1,
  },
  footnote: {
    marginTop: spacing.md,
  },
  buttons: {
    gap: spacing.sm,
    paddingTop: spacing.sm,
  },
});

export default ConsentSheet;
