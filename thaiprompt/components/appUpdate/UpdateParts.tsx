/**
 * ชิ้นส่วนร่วมของหน้าต่าง/หน้าอัปเดตแอป (ธีมรอยัล น้ำเงินกรมท่า-ทอง)
 *
 * - GoldEmblem    วงทองไล่เฉด + ไอคอน + แสงเรือง (หัว bottom sheet)
 * - GlassCard     การ์ดกระจกบนหัวน้ำเงิน
 * - GlassTag      ป้ายกระจกเล็ก (เวอร์ชัน / ขนาด)
 * - NoteList      รายการ "มีอะไรใหม่" (ติ๊กทอง)
 * - VersionJump   "3.387.0 → 3.388.0"
 * - TrustLine     "ไฟล์จากเซิร์ฟเวอร์ Thai Prompt โดยตรง · ตรวจความถูกต้องก่อนติดตั้ง"
 *
 * สีจาก useTheme() เท่านั้น · onHeader = วางบนหัวน้ำเงิน (ตัวอักษรสว่าง)
 */

import React from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import { Icon, type IconName } from '@/components/ui';
import { glowStyle, radii, spacing, typography, useTheme } from '@/theme';

/** ข้อความเมื่อ server ไม่ได้ใส่รายการ "มีอะไรใหม่" */
export const DEFAULT_NOTE = 'ปรับปรุงการทำงานของแอปให้ลื่นและเสถียรขึ้น';

export const TRUST_TEXT = 'ไฟล์จากเซิร์ฟเวอร์ Thai Prompt โดยตรง · ตรวจความถูกต้องก่อนติดตั้ง';

export const GoldEmblem: React.FC<{ icon: IconName; size?: number }> = ({ icon, size = 56 }) => {
  const { colors, gradients } = useTheme();
  return (
    <View style={[{ width: size, height: size, borderRadius: size / 2 }, glowStyle(colors.gold, 0.9)]}>
      <LinearGradient
        colors={gradients.primary}
        start={{ x: 0.1, y: 0 }}
        end={{ x: 0.9, y: 1 }}
        style={[styles.emblem, { width: size, height: size, borderRadius: size / 2 }]}
      >
        <Icon name={icon} size={size * 0.48} color={colors.textOnGold} weight="bold" />
      </LinearGradient>
    </View>
  );
};

export const GlassCard: React.FC<{ children?: React.ReactNode; style?: StyleProp<ViewStyle> }> = ({ children, style }) => {
  const { colors, gradients } = useTheme();
  return (
    <LinearGradient
      colors={gradients.glass}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={[styles.glassCard, { borderColor: colors.headerGlassBorder }, style]}
    >
      {children}
    </LinearGradient>
  );
};

export const GlassTag: React.FC<{ icon: IconName; label: string }> = ({ icon, label }) => {
  const { colors } = useTheme();
  return (
    <View style={[styles.tag, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
      <Icon name={icon} size={14} color={colors.goldLight} />
      <Text numberOfLines={1} style={[typography.caption, { color: colors.onHeader }]}>
        {label}
      </Text>
    </View>
  );
};

export const NoteList: React.FC<{ notes: readonly string[]; max?: number; onHeader?: boolean }> = ({
  notes,
  max,
  onHeader = false,
}) => {
  const { colors } = useTheme();
  const source = notes.length > 0 ? notes : [DEFAULT_NOTE];
  const list = typeof max === 'number' ? source.slice(0, max) : source;
  return (
    <View style={styles.notes}>
      {list.map((note, index) => (
        <View key={`${index}-${note}`} style={styles.noteRow}>
          <View style={[styles.noteMark, { backgroundColor: onHeader ? colors.headerGlass : colors.goldSoft }]}>
            <Icon name="check" size={12} color={onHeader ? colors.goldLight : colors.goldDeep} weight="bold" />
          </View>
          <Text style={[typography.body, styles.flex, { color: onHeader ? colors.onHeader : colors.text }]}>{note}</Text>
        </View>
      ))}
    </View>
  );
};

export const VersionJump: React.FC<{ from: string; to: string; onHeader?: boolean }> = ({ from, to, onHeader = false }) => {
  const { colors } = useTheme();
  return (
    <View style={styles.jump} accessible accessibilityLabel={`จากเวอร์ชัน ${from} เป็น ${to}`}>
      <Text style={[typography.bodyStrong, { color: onHeader ? colors.onHeaderMuted : colors.textMuted }]}>{from}</Text>
      <Icon name="arrow-right" size={16} color={onHeader ? colors.goldLight : colors.goldDeep} weight="bold" />
      <Text style={[typography.h3, { color: onHeader ? colors.goldLight : colors.goldDeep }]}>{to}</Text>
    </View>
  );
};

export const TrustLine: React.FC<{ onHeader?: boolean; style?: StyleProp<ViewStyle> }> = ({ onHeader = false, style }) => {
  const { colors } = useTheme();
  const color = onHeader ? colors.onHeaderMuted : colors.textMuted;
  return (
    <View style={[styles.trust, style]}>
      <Icon name="shield-check" size={15} color={onHeader ? colors.goldLight : colors.goldDeep} />
      <Text style={[typography.caption, styles.trustText, { color }]}>{TRUST_TEXT}</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  emblem: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  glassCard: {
    borderRadius: radii.xl,
    borderWidth: 1,
    padding: spacing.lg,
    gap: spacing.md,
  },
  tag: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: radii.pill,
    borderWidth: 1,
    paddingHorizontal: 11,
    paddingVertical: 5,
  },
  notes: {
    gap: spacing.md,
  },
  noteRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
  },
  noteMark: {
    width: 22,
    height: 22,
    borderRadius: 11,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 0,
  },
  jump: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  trust: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
  },
  trustText: {
    flexShrink: 1,
    textAlign: 'center',
  },
});
