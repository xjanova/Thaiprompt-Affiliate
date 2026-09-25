/**
 * ชิ้นส่วนหน้าตาที่ใช้ร่วมกันในโหมดคนขาย (ร้านของฉัน / ร้านตลาดสด)
 *
 * - IconTile     ช่องไอคอนมุมมน 44×44 (พื้นอ่อน + ไอคอนเส้น) ตามแพทเทิร์น "แถวรายการในการ์ด" ของ DESIGN.md
 * - HeroCard     การ์ดน้ำเงินกรมท่าลายกนก (พื้นแบบ RoyalHeader) สำหรับตัวเลขสรุป/สถานะร้าน
 *                ของที่วางข้างในถือว่าอยู่บนหัวน้ำเงิน (Pill/ปุ่มรอง เป็นแบบกระจกเอง)
 * - NoticeBanner แถบแจ้งผลสั้นๆ (สำเร็จ/เตือน/ผิดพลาด) พื้นอ่อนตามโทน + ไอคอน
 *
 * สีทั้งหมดมาจาก useTheme() — รองรับโหมดสว่าง/มืด
 */

import React from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Icon, OnHeaderProvider, RoyalHeader, type IconName } from '@/components/ui';
import { useTheme, radii, shadowStyle, spacing, toneColors, typography, type Tone } from '@/theme';

// =====================================================
// IconTile
// =====================================================

export type IconTileTone = 'navy' | Tone;

export interface IconTileProps {
  icon: IconName;
  /** navy = พื้นน้ำเงินอ่อน (ค่าเริ่มต้น) · gold = เรื่องเงิน · success/warning/danger/info = สถานะ */
  tone?: IconTileTone;
  /** ขนาดกล่อง (ค่าเริ่มต้น 44) */
  size?: number;
  style?: StyleProp<ViewStyle>;
}

/** ช่องไอคอนนำหน้าแถว — พื้นอ่อน มุม 15 ไอคอนเส้นกลางกล่อง */
export const IconTile: React.FC<IconTileProps> = ({ icon, tone = 'navy', size = 44, style }) => {
  const { colors, isDark } = useTheme();
  // โหมดมืด: น้ำเงินกรมท่าจมหายบนการ์ดมืด → ใช้ทองเรืองแทน (บุคลิก "มิดไนท์โกลด์")
  const look =
    tone === 'navy'
      ? { bg: colors.navySoft, fg: isDark ? colors.goldDeep : colors.navy }
      : (() => {
          const t = toneColors(tone, colors);
          return { bg: tone === 'neutral' ? colors.inset : t.bg, fg: tone === 'neutral' ? colors.textMuted : t.fg };
        })();

  return (
    <View
      style={[
        styles.tile,
        { width: size, height: size, borderRadius: Math.round(size * 0.34), backgroundColor: look.bg },
        style,
      ]}
    >
      <Icon name={icon} size={Math.round(size * 0.5)} color={look.fg} />
    </View>
  );
};

// =====================================================
// HeroCard
// =====================================================

export interface HeroCardProps {
  children?: React.ReactNode;
  /** style กรอบนอก (margin) */
  style?: StyleProp<ViewStyle>;
  /** style พื้นที่เนื้อหา (padding) */
  contentStyle?: StyleProp<ViewStyle>;
  /** ความกว้างลายกนกมุมขวาบน (ค่าเริ่มต้น 170) */
  ornamentWidth?: number;
  /** มุมโค้ง (ค่าเริ่มต้น 24) */
  radius?: number;
}

/** การ์ดหัวน้ำเงินกรมท่า + ลายกนกทองจาง — ตัวเลขเงินข้างในใช้ colors.goldLight */
export const HeroCard: React.FC<HeroCardProps> = ({ children, style, contentStyle, ornamentWidth = 170, radius = 24 }) => {
  const { colors } = useTheme();
  return (
    <View style={[{ borderRadius: radius }, shadowStyle('lg', colors.shadowDark), style]}>
      <RoyalHeader
        ornamentWidth={ornamentWidth}
        ornamentTop={-30}
        style={[
          styles.hero,
          { borderRadius: radius, borderColor: colors.headerGlassBorder },
          contentStyle,
        ]}
      >
        <OnHeaderProvider value>{children}</OnHeaderProvider>
      </RoyalHeader>
    </View>
  );
};

// =====================================================
// NoticeBanner
// =====================================================

const NOTICE_ICON: Record<Tone, IconName> = {
  success: 'check-circle',
  warning: 'warning',
  danger: 'warning-circle',
  info: 'info',
  gold: 'sparkle',
  neutral: 'info',
};

export interface NoticeBannerProps {
  tone: Tone;
  text: string;
  style?: StyleProp<ViewStyle>;
}

/** แถบแจ้งผล — พื้นอ่อนตามโทน ไอคอนสีโทน ตัวอักษรสีหลัก (อ่านง่ายทุกโหมด) */
export const NoticeBanner: React.FC<NoticeBannerProps> = ({ tone, text, style }) => {
  const { colors } = useTheme();
  const t = toneColors(tone, colors);
  return (
    <View style={[styles.notice, { backgroundColor: t.bg, borderColor: t.border }, style]}>
      <Icon name={NOTICE_ICON[tone]} size={20} color={t.fg} weight="fill" />
      <Text accessibilityLiveRegion="polite" style={[typography.bodySm, styles.noticeText, { color: colors.textStrong }]}>
        {text}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  tile: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  hero: {
    borderWidth: 1,
    padding: spacing.lg,
  },
  notice: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    borderRadius: radii.md,
    borderWidth: 1,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.md,
  },
  noticeText: {
    flex: 1,
    fontWeight: '500',
  },
});
