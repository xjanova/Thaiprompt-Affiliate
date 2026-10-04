/**
 * ชิ้นส่วนร่วมของหน้ายืนยันตัวตน (eKYC) — ตามแบบที่เจ้าของอนุมัติ (design-ekyc/shots)
 *
 * - EkycShell     หัวน้ำเงินลายกนก (ปุ่มย้อนกลับกระจก + ชื่อหน้า + hero + แถบขั้นตอน) แล้วเนื้อหาบนแผ่นงาช้าง + แถบปุ่มลอยท้ายจอ
 * - EkycStepper   แถบ 4 ขั้น ยินยอม · บัตร · ใบหน้า · ตรวจ (ผ่านแล้ว = วงทองติ๊ก · ปัจจุบัน = วงขาวเลข · ยังไม่ถึง = วงกระจก)
 * - CameraTopBar  หัวของหน้ากล้อง (พื้นมืดเสมอ)
 * - CheckRow      แถวสถานะการตรวจ (ผ่าน / กำลังตรวจ / ต้องระวัง / ยังไม่ถึง) + ค่าทางขวา
 * - useEkycExit   ออกจากขั้นตอนทั้งชุด กลับหน้าที่พามา (ข้อมูลที่กรอกในหน้านั้นยังอยู่)
 *
 * สีจาก useTheme() เท่านั้น · หน้ากล้องใช้ DARK_THEME.colors เสมอ (ภาพจากกล้องเด่น กรอบทองชัด)
 */

import React, { useCallback } from 'react';
import { ActivityIndicator, ScrollView, StatusBar, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { router, useNavigation } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '@/components/ui/Text';
import { GlassIconButton, Icon, OnHeaderProvider, RoyalHeader, type IconName } from '@/components/ui';
import { StickyBar } from '@/components/shop';
import { DARK_THEME, radii, spacing, typography, useTheme, withAlpha } from '@/theme';
import { useEkycStore } from '@/stores/ekycStore';

const SHEET_RADIUS = 26;
const CAM = DARK_THEME.colors;

// =====================================================
// แถบขั้นตอน
// =====================================================

export const EKYC_STEPS = ['ยินยอม', 'บัตร', 'ใบหน้า', 'ตรวจ'] as const;

/** @param current ขั้นปัจจุบัน 1–4 · 5 = ผ่านครบทุกขั้น */
export const EkycStepper: React.FC<{ current: number; style?: StyleProp<ViewStyle> }> = ({ current, style }) => {
  const { colors } = useTheme();
  return (
    <View
      style={[styles.stepper, style]}
      accessible
      accessibilityRole="progressbar"
      accessibilityLabel={`ขั้นที่ ${Math.min(current, 4)} จาก 4 ${EKYC_STEPS[Math.min(current, 4) - 1] ?? ''}`}
    >
      {EKYC_STEPS.map((label, index) => {
        const n = index + 1;
        const done = n < current;
        const active = n === current;
        return (
          <View key={label} style={styles.stepItem}>
            <View
              style={[
                styles.stepDot,
                done
                  ? { backgroundColor: colors.gold }
                  : active
                    ? { backgroundColor: colors.onHeader }
                    : { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder, borderWidth: 1 },
              ]}
            >
              {done ? (
                <Icon name="check" size={14} color={colors.textOnGold} weight="bold" />
              ) : (
                <Text style={[typography.micro, { color: active ? colors.navyFill : colors.onHeaderMuted }]}>{n}</Text>
              )}
            </View>
            <Text
              style={[
                typography.micro,
                { color: done || active ? colors.onHeader : colors.onHeaderMuted, fontWeight: done || active ? '700' : '600' },
              ]}
            >
              {label}
            </Text>
          </View>
        );
      })}
    </View>
  );
};

// =====================================================
// โครงหน้าที่มีหัวน้ำเงิน
// =====================================================

export interface EkycShellProps {
  title: string;
  subtitle?: string;
  /** ซ่อนปุ่มย้อนกลับ (เช่น ระหว่างส่งตรวจ) */
  hideBack?: boolean;
  onBack?: () => void;
  /** ส่วนกลางหัว (ไอคอนใหญ่ + หัวข้อ) */
  hero?: React.ReactNode;
  /** ขั้นปัจจุบัน 1–4 · 5 = ครบ · ไม่ส่ง = ไม่แสดงแถบ */
  step?: number;
  /** ปุ่มท้ายจอ */
  bottom?: React.ReactNode;
  children?: React.ReactNode;
  scroll?: boolean;
}

export const EkycShell: React.FC<EkycShellProps> = ({
  title,
  subtitle,
  hideBack = false,
  onBack,
  hero,
  step,
  bottom,
  children,
  scroll = true,
}) => {
  const { colors, gradients } = useTheme();
  const insets = useSafeAreaInsets();

  const back = () => {
    if (onBack) {
      onBack();
      return;
    }
    if (router.canGoBack()) router.back();
    else router.replace('/(tabs)' as never);
  };

  const content = (
    <View style={[styles.sheetInner, { paddingBottom: (bottom ? 120 : spacing.xxl) + insets.bottom }]}>{children}</View>
  );

  return (
    <View style={[styles.root, { backgroundColor: gradients.hero[gradients.hero.length - 1] }]}>
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      <RoyalHeader
        ornamentTop={insets.top - 18}
        ornamentWidth={210}
        style={{ paddingTop: insets.top + spacing.xs, paddingBottom: SHEET_RADIUS + spacing.lg }}
      >
        <OnHeaderProvider value>
          <View style={styles.headerRow}>
            {!hideBack && <GlassIconButton icon="caret-left" weight="bold" accessibilityLabel="ย้อนกลับ" onPress={back} />}
            <View style={[styles.flex, hideBack && styles.titleNoBack]}>
              <Text accessibilityRole="header" numberOfLines={1} style={[typography.serif, { color: colors.onHeader }]}>
                {title}
              </Text>
              {!!subtitle && (
                <Text numberOfLines={1} style={[typography.bodySm, { color: colors.onHeaderMuted }]}>
                  {subtitle}
                </Text>
              )}
            </View>
          </View>
          {hero ? <View style={styles.hero}>{hero}</View> : null}
          {typeof step === 'number' ? <EkycStepper current={step} style={styles.stepperGap} /> : null}
        </OnHeaderProvider>
      </RoyalHeader>

      <View style={[styles.sheet, { backgroundColor: colors.background }]}>
        {scroll ? (
          <ScrollView showsVerticalScrollIndicator={false} keyboardShouldPersistTaps="handled">
            {content}
          </ScrollView>
        ) : (
          content
        )}
      </View>

      {bottom ? <StickyBar>{bottom}</StickyBar> : null}
    </View>
  );
};

// =====================================================
// หัวหน้ากล้อง (พื้นมืดเสมอ)
// =====================================================

export const CameraTopBar: React.FC<{
  title: string;
  subtitle?: string;
  onBack: () => void;
  right?: React.ReactNode;
}> = ({ title, subtitle, onBack, right }) => {
  const insets = useSafeAreaInsets();
  return (
    <View style={[styles.camHeader, { paddingTop: insets.top + spacing.sm }]}>
      <OnHeaderProvider value>
        <GlassIconButton icon="caret-left" weight="bold" accessibilityLabel="ย้อนกลับ" onPress={onBack} />
      </OnHeaderProvider>
      <View style={styles.flex}>
        <Text accessibilityRole="header" numberOfLines={1} style={[typography.h2, { color: CAM.textStrong }]}>
          {title}
        </Text>
        {!!subtitle && (
          <Text numberOfLines={1} style={[typography.caption, { color: CAM.textMuted }]}>
            {subtitle}
          </Text>
        )}
      </View>
      {right}
    </View>
  );
};

/** ป้ายเล็กมุมขวาของหน้ากล้อง (ขอบทอง พื้นกระจกมืด) */
export const CameraPill: React.FC<{ icon: IconName; label: string }> = ({ icon, label }) => (
  <View style={[styles.camPill, { borderColor: withAlpha(CAM.gold, 0.45), backgroundColor: withAlpha(CAM.card, 0.85) }]}>
    <Icon name={icon} size={13} color={CAM.gold} />
    <Text style={[typography.micro, { color: CAM.goldLight }]}>{label}</Text>
  </View>
);

/** ชิปเคล็ดลับบนหน้ากล้อง (ติ๊กทองเมื่อผ่าน) */
export const CameraChip: React.FC<{ label: string; on?: boolean }> = ({ label, on = false }) => (
  <View
    style={[
      styles.camChip,
      { borderColor: on ? withAlpha(CAM.gold, 0.5) : CAM.border, backgroundColor: withAlpha(CAM.card, 0.9) },
    ]}
  >
    <Icon name="check" size={12} color={on ? CAM.gold : CAM.textFaint} weight="bold" />
    <Text style={[typography.micro, { color: on ? CAM.goldLight : CAM.textMuted }]}>{label}</Text>
  </View>
);

// =====================================================
// แถวสถานะการตรวจ
// =====================================================

export type CheckState = 'done' | 'running' | 'warn' | 'fail' | 'idle';

export const CheckRow: React.FC<{
  state: CheckState;
  title: string;
  subtitle?: string;
  value?: string;
  first?: boolean;
}> = ({ state, title, subtitle, value, first = false }) => {
  const { colors } = useTheme();
  const idle = state === 'idle';
  const valueColor =
    state === 'done' ? colors.success : state === 'warn' ? colors.goldDeep : state === 'fail' ? colors.danger : colors.textMuted;

  const mark = (() => {
    switch (state) {
      case 'done':
        return (
          <View style={[styles.mark, { backgroundColor: colors.success }]}>
            <Icon name="check" size={15} color={colors.textOnAccent} weight="bold" />
          </View>
        );
      case 'warn':
        return (
          <View style={[styles.mark, { backgroundColor: colors.goldSoft }]}>
            <Icon name="info" size={17} color={colors.goldDeep} />
          </View>
        );
      case 'fail':
        return (
          <View style={[styles.mark, { backgroundColor: colors.dangerSoft }]}>
            <Icon name="x" size={15} color={colors.danger} weight="bold" />
          </View>
        );
      case 'running':
        return (
          <View style={[styles.mark, { borderWidth: 2.5, borderColor: colors.goldSoft }]}>
            <ActivityIndicator size="small" color={colors.gold} />
          </View>
        );
      default:
        return <View style={[styles.mark, { backgroundColor: colors.inset }]} />;
    }
  })();

  return (
    <View
      style={[styles.checkRow, !first && { borderTopWidth: 1, borderTopColor: colors.divider }]}
      accessible
      accessibilityLabel={`${title}${subtitle ? ` ${subtitle}` : ''}${value ? ` ${value}` : ''}`}
    >
      {mark}
      <View style={styles.flex}>
        <Text style={[typography.bodyStrong, { color: idle ? colors.textFaint : colors.textStrong }]}>{title}</Text>
        {!!subtitle && <Text style={[typography.caption, { color: idle ? colors.textFaint : colors.textMuted }]}>{subtitle}</Text>}
      </View>
      {!!value && <Text style={[typography.bodyStrong, { color: valueColor }]}>{value}</Text>}
    </View>
  );
};

// =====================================================
// กล่องข้อความแจ้ง (พื้นน้ำเงินอ่อน)
// =====================================================

export const InfoNote: React.FC<{ icon?: IconName; title?: string; children: React.ReactNode; style?: StyleProp<ViewStyle> }> = ({
  icon = 'lock',
  title,
  children,
  style,
}) => {
  const { colors, isDark } = useTheme();
  return (
    <View style={[styles.note, { backgroundColor: colors.navySoft, borderColor: colors.border }, style]}>
      <View style={styles.noteHead}>
        <Icon name={icon} size={18} color={isDark ? colors.text : colors.navy} />
        {title ? (
          <Text style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]}>{title}</Text>
        ) : (
          <View style={styles.flex}>{typeof children === 'string' ? <Text style={[typography.bodySm, { color: colors.text }]}>{children}</Text> : children}</View>
        )}
      </View>
      {title ? (typeof children === 'string' ? <Text style={[typography.bodySm, { color: colors.text }]}>{children}</Text> : children) : null}
    </View>
  );
};

// =====================================================
// ออกจากขั้นตอนยืนยันตัวตน
// =====================================================

/**
 * ออกจากขั้นตอนทั้งชุด (ทุกหน้าใต้ /ekyc) แล้วกลับหน้าที่พามา เช่น หน้าชำระเงิน — ข้อมูลที่กรอกไว้ยังอยู่
 * ลบไฟล์รูปชั่วคราวทั้งหมดก่อนออก · เปิดมาจากลิงก์/แจ้งเตือนโดยไม่มีหน้าก่อนหน้า = ไปหน้าแรก
 */
export const useEkycExit = () => {
  const navigation = useNavigation();
  return useCallback(() => {
    // นำทางก่อน แล้วค่อยล้างข้อมูล (ล้างก่อน = หน้าที่ยังค้างใน stack เห็นว่าไม่มีรอบแล้วพาไปหน้าแนะนำซ้อน)
    const sessionAtExit = useEkycStore.getState().session;
    const parent = navigation.getParent?.();
    if (parent?.canGoBack()) parent.goBack();
    else router.replace('/(tabs)' as never);
    setTimeout(() => {
      const store = useEkycStore.getState();
      // เริ่มรอบใหม่ไปแล้วระหว่างรอ = ไม่ล้าง
      if (store.session === sessionAtExit) store.resetFlow();
    }, 600);
  }, [navigation]);
};

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
  },
  titleNoBack: {
    paddingLeft: spacing.xs,
  },
  hero: {
    paddingHorizontal: spacing.screen,
    marginTop: spacing.lg,
  },
  stepperGap: {
    marginTop: spacing.xl,
  },
  stepper: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.xxl,
  },
  stepItem: {
    alignItems: 'center',
    gap: 4,
    minWidth: 56,
  },
  stepDot: {
    width: 26,
    height: 26,
    borderRadius: 13,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sheet: {
    flex: 1,
    marginTop: -SHEET_RADIUS,
    borderTopLeftRadius: SHEET_RADIUS,
    borderTopRightRadius: SHEET_RADIUS,
    overflow: 'hidden',
  },
  sheetInner: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.xl,
    gap: spacing.lg,
  },
  camHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.sm,
  },
  camPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    borderRadius: radii.pill,
    borderWidth: 1,
    paddingHorizontal: 10,
    paddingVertical: 5,
  },
  camChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    borderRadius: radii.pill,
    borderWidth: 1,
    paddingHorizontal: 10,
    paddingVertical: 5,
  },
  checkRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: 14,
  },
  mark: {
    width: 28,
    height: 28,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  note: {
    borderRadius: radii.lg,
    borderWidth: 1,
    padding: spacing.lg,
    gap: spacing.sm,
  },
  noteHead: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
  },
});
