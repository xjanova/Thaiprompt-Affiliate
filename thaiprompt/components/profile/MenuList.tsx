/**
 * MenuList — เมนูแบบการ์ดกลุ่ม ธีมรอยัล น้ำเงินกรมท่า-ทอง (โปรไฟล์ / ตั้งค่า / การแจ้งเตือน)
 *
 * แพทเทิร์นตาม DESIGN.md
 *   - การ์ดขาว 1 ใบต่อกลุ่ม · แถว padding 14 · เส้นคั่น colors.divider (เว้นตรงไอคอน ให้ดูเป็นระเบียบ)
 *   - ไอคอนนำหน้าในสี่เหลี่ยมมน 44×44 radius 15 · พื้น navySoft + ไอคอน navy
 *     (เรื่องเงินใช้ทอง · สถานะใช้สีของโทนนั้น · โหมดมืดไอคอนเป็นสีงาช้าง ไม่จมไปกับพื้น)
 *   - MenuGroup ใส่เส้นคั่นระหว่างแถวให้เอง (แถวที่ซ่อนด้วยเงื่อนไข {flag && ...} ไม่ทิ้งเส้นเกิน)
 *
 * @example
 * <MenuGroup title="บัญชี">
 *   <MenuRow icon="user" title="แก้ไขโปรไฟล์" subtitle="ชื่อ เบอร์โทร รูป" onPress={openEdit} />
 *   <MenuRow icon="moon" title="โหมดมืด" right={<ThemedSwitch value={isDark} onValueChange={toggle} accessibilityLabel="โหมดมืด" />} />
 * </MenuGroup>
 */

import React from 'react';
import { Pressable, StyleSheet, Switch, View, type StyleProp, type ViewStyle } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Card3D, Icon, type IconName } from '@/components/ui';
import { useTheme, radii, spacing, toneColors, typography, withAlpha, type ThemeColors } from '@/theme';

/** โทนของช่องไอคอน */
export type MenuTone = 'navy' | 'gold' | 'success' | 'danger' | 'info' | 'warning';

/** ขนาดช่องไอคอนนำหน้า (ตาม DESIGN.md) */
const TILE = 44;
const ROW_PAD = 14;

/** สีพื้น/ไอคอนของช่องไอคอนตามโทน */
export const tileColors = (tone: MenuTone, colors: ThemeColors, isDark: boolean): { bg: string; fg: string } => {
  if (tone === 'navy') {
    // โหมดมืด: navy เข้มเกินจะจมกับการ์ด → ใช้สีงาช้างบนพื้นน้ำเงินจาง
    return { bg: colors.navySoft, fg: isDark ? colors.text : colors.navy };
  }
  const t = toneColors(tone, colors);
  return { bg: t.bg, fg: t.fg };
};

// =====================================================
// IconTile — สี่เหลี่ยมมนใส่ไอคอน
// =====================================================

export interface IconTileProps {
  icon: IconName;
  tone?: MenuTone;
  size?: number;
  style?: StyleProp<ViewStyle>;
}

export const IconTile: React.FC<IconTileProps> = ({ icon, tone = 'navy', size = TILE, style }) => {
  const { colors, isDark } = useTheme();
  const t = tileColors(tone, colors, isDark);
  return (
    <View
      style={[
        styles.tile,
        { width: size, height: size, borderRadius: size * 0.34, backgroundColor: t.bg },
        style,
      ]}
    >
      <Icon name={icon} size={size * 0.5} color={t.fg} />
    </View>
  );
};

// =====================================================
// GroupLabel — หัวข้อเล็กเหนือการ์ดกลุ่ม (แบบรายการตั้งค่า)
// =====================================================

export interface GroupLabelProps {
  title: string;
  subtitle?: string;
  style?: StyleProp<ViewStyle>;
}

export const GroupLabel: React.FC<GroupLabelProps> = ({ title, subtitle, style }) => {
  const { colors } = useTheme();
  return (
    <View style={[styles.groupHead, style]}>
      <Text accessibilityRole="header" style={[styles.groupTitle, { color: colors.textMuted }]}>
        {title}
      </Text>
      {!!subtitle && (
        <Text style={[typography.caption, styles.groupSubtitle, { color: colors.textMuted }]} numberOfLines={2}>
          {subtitle}
        </Text>
      )}
    </View>
  );
};

// =====================================================
// MenuGroup — การ์ดกลุ่ม + หัวข้อเล็กด้านบน
// =====================================================

export interface MenuGroupProps {
  title?: string;
  /** คำอธิบายสั้นใต้หัวข้อ */
  subtitle?: string;
  children?: React.ReactNode;
  style?: StyleProp<ViewStyle>;
}

export const MenuGroup: React.FC<MenuGroupProps> = ({ title, subtitle, children, style }) => {
  const { colors } = useTheme();
  // ตัดลูกที่ว่าง (null/false จากเงื่อนไข) ออกก่อน เพื่อวางเส้นคั่นเฉพาะระหว่างแถวที่แสดงจริง
  const items = React.Children.toArray(children);
  if (items.length === 0) return null;

  return (
    <View style={[styles.group, style]}>
      {!!title && <GroupLabel title={title} subtitle={subtitle} />}
      <Card3D padding={0} shadow="sm">
        <View style={styles.clip}>
          {items.map((item, index) => (
            <React.Fragment key={(item as React.ReactElement).key ?? index}>
              {index > 0 && <View style={[styles.divider, { backgroundColor: colors.divider }]} />}
              {item}
            </React.Fragment>
          ))}
        </View>
      </Card3D>
    </View>
  );
};

// =====================================================
// MenuRow — แถวเมนู (กดได้ / แสดงผลอย่างเดียว)
// =====================================================

export interface MenuRowProps {
  icon: IconName;
  title: string;
  subtitle?: string;
  /** ข้อความสั้นด้านขวา เช่น "ไทย" หรือเลขเวอร์ชัน */
  value?: string;
  onPress?: () => unknown;
  /** undefined = ลูกศร (ถ้ากดได้) · null = ไม่มีอะไร · element = แสดงตามนั้น (สวิตช์ ตัวหมุนโหลด) */
  right?: React.ReactNode;
  tone?: MenuTone;
  /** แถวอันตราย (ออกจากระบบ ลบบัญชี) — ตัวอักษรแดง ช่องไอคอนแดงจาง */
  danger?: boolean;
  disabled?: boolean;
  accessibilityLabel?: string;
  accessibilityHint?: string;
}

export const MenuRow: React.FC<MenuRowProps> = ({
  icon,
  title,
  subtitle,
  value,
  onPress,
  right,
  tone = 'navy',
  danger = false,
  disabled = false,
  accessibilityLabel,
  accessibilityHint,
}) => {
  const { colors } = useTheme();

  const trailing =
    right !== undefined ? (
      right
    ) : onPress ? (
      <Icon name="caret-right" size={16} color={colors.textFaint} weight="bold" />
    ) : null;

  const body = (
    <>
      <IconTile icon={icon} tone={danger ? 'danger' : tone} />
      <View style={styles.texts}>
        <Text numberOfLines={2} style={[typography.bodyStrong, { color: danger ? colors.danger : colors.textStrong }]}>
          {title}
        </Text>
        {!!subtitle && (
          <Text numberOfLines={2} style={[typography.caption, styles.subtitle, { color: colors.textMuted }]}>
            {subtitle}
          </Text>
        )}
      </View>
      {!!value && (
        <Text numberOfLines={1} style={[typography.bodySm, styles.value, { color: colors.textMuted }]}>
          {value}
        </Text>
      )}
      {trailing}
    </>
  );

  if (!onPress) {
    return <View style={styles.row}>{body}</View>;
  }

  return (
    <Pressable
      onPress={onPress}
      disabled={disabled}
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel ?? [title, subtitle, value].filter(Boolean).join(' ')}
      accessibilityHint={accessibilityHint}
      accessibilityState={{ disabled }}
      style={({ pressed }) => [
        styles.row,
        pressed && { backgroundColor: colors.inset },
        disabled && styles.disabled,
      ]}
    >
      {body}
    </Pressable>
  );
};

// =====================================================
// ThemedSwitch — สวิตช์สีทอง (เปิด) / เทาจาง (ปิด)
// =====================================================

export interface ThemedSwitchProps {
  value: boolean;
  onValueChange: (value: boolean) => void;
  disabled?: boolean;
  accessibilityLabel?: string;
}

export const ThemedSwitch: React.FC<ThemedSwitchProps> = ({ value, onValueChange, disabled, accessibilityLabel }) => {
  const { colors } = useTheme();
  const offTrack = withAlpha(colors.textFaint, 0.35);
  return (
    <Switch
      value={value}
      onValueChange={onValueChange}
      disabled={disabled}
      trackColor={{ false: offTrack, true: colors.gold }}
      thumbColor={colors.onHeader}
      ios_backgroundColor={offTrack}
      accessibilityLabel={accessibilityLabel}
    />
  );
};

const styles = StyleSheet.create({
  group: {
    marginBottom: spacing.xxl,
  },
  groupHead: {
    paddingHorizontal: spacing.xs,
    marginBottom: spacing.sm,
  },
  groupTitle: {
    fontSize: 13.5,
    lineHeight: 20,
    fontWeight: '600',
    letterSpacing: 0.2,
  },
  groupSubtitle: {
    fontWeight: '400',
    opacity: 0.85,
  },
  clip: {
    // เล็กกว่าการ์ด 1px — โหมดมืดการ์ดมีขอบ 1px ไม่ให้พื้นตอนกดล้นทับขอบที่มุม
    borderRadius: radii.xl - 1,
    overflow: 'hidden',
  },
  tile: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: ROW_PAD,
    paddingVertical: ROW_PAD - 2,
    minHeight: TILE + (ROW_PAD - 2) * 2,
  },
  texts: {
    flex: 1,
  },
  subtitle: {
    marginTop: 1,
  },
  value: {
    maxWidth: '40%',
  },
  divider: {
    height: StyleSheet.hairlineWidth * 2,
    marginLeft: ROW_PAD + TILE + spacing.md,
    marginRight: ROW_PAD,
  },
  disabled: {
    opacity: 0.5,
  },
});
