/**
 * StatTile — กล่องตัวเลขสรุป (ยอดวันนี้, งานที่รับ, ออเดอร์รอยืนยัน)
 *
 * value เป็น string | number | element ได้ (ส่ง <PriceText /> สำหรับเงิน)
 *
 * @example
 * <StatTile label="รอยืนยัน" value={counts.to_confirm} icon="🧾" tone="warning" onPress={openToConfirm} />
 * <StatTile label="ยอดขายวันนี้" value={<PriceText amount={sales.today} />} icon="💰" />
 */

import React from 'react';
import { ActivityIndicator, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { Text } from '@/components/ui/Text';
import { useTheme, spacing, radii, toneColors, typography, type Tone } from '@/theme';
import { Card3D } from './Card3D';
import { IconSlot } from './Icon';
import { formatBaht } from './PriceText';

export interface StatTileProps {
  label: string;
  value: React.ReactNode;
  icon?: React.ReactNode;
  tone?: Tone;
  /** ข้อความเล็กใต้ตัวเลข */
  caption?: string;
  loading?: boolean;
  onPress?: () => void;
  /**
   * ข้อความสำหรับโปรแกรมอ่านหน้าจอ — ไม่ส่งมา = ประกอบจาก label + value + caption ให้เอง
   * (value เป็น element อื่นที่ไม่ใช่ PriceText → ส่งข้อความเต็มมาเอง ไม่งั้นอ่านจากตัวอักษรข้างในแทน)
   */
  accessibilityLabel?: string;
  style?: StyleProp<ViewStyle>;
}

/** ข้อความที่อ่านได้ของ value (null = อ่านไม่ออก เช่น element อื่นที่ไม่ใช่ PriceText) */
const spokenValue = (value: React.ReactNode): string | null => {
  if (typeof value === 'number') return value.toLocaleString('th-TH');
  if (typeof value === 'string') return value;
  if (React.isValidElement(value)) {
    const props = value.props as { amount?: unknown; decimals?: unknown };
    if (props && 'amount' in props) {
      const decimals = props.decimals === 0 || props.decimals === 2 ? props.decimals : undefined;
      return formatBaht(props.amount, decimals === undefined ? {} : { decimals });
    }
  }
  return null;
};

export const StatTile: React.FC<StatTileProps> = ({
  label,
  value,
  icon,
  tone = 'gold',
  caption,
  loading = false,
  onPress,
  accessibilityLabel,
  style,
}) => {
  const { colors } = useTheme();
  const t = toneColors(tone, colors);

  // กดได้ = Pressable ใช้ accessibilityLabel แทนตัวอักษรข้างในทั้งหมด → ต้องมีตัวเลขอยู่ในนั้นด้วย
  const a11yLabel = (() => {
    if (accessibilityLabel) return accessibilityLabel;
    if (loading) return `${label} กำลังโหลด`;
    const spoken = spokenValue(value);
    if (spoken === null) return undefined; // ให้ระบบอ่านตัวอักษรข้างในเอง
    return [label, spoken, caption].filter(Boolean).join(' ');
  })();

  const renderValue = () => {
    if (loading) {
      return <ActivityIndicator size="small" color={colors.gold} style={styles.loader} />;
    }
    if (typeof value === 'string' || typeof value === 'number') {
      const text = typeof value === 'number' ? value.toLocaleString('th-TH') : value;
      return (
        <Text numberOfLines={1} style={[typography.h1, { color: colors.textStrong }]}>
          {text}
        </Text>
      );
    }
    return value;
  };

  return (
    <Card3D
      onPress={onPress}
      padding={spacing.md}
      radius={radii.lg}
      shadow="sm"
      style={style}
      accessibilityLabel={a11yLabel}
    >
      <View style={styles.top}>
        {icon !== undefined && icon !== null && (
          <View style={[styles.iconBox, { backgroundColor: t.bg }]}>
            <IconSlot icon={icon} size={18} color={t.fg} weight="fill" />
          </View>
        )}
        <Text numberOfLines={2} style={[typography.caption, styles.label, { color: colors.textMuted }]}>
          {label}
        </Text>
      </View>
      <View style={styles.value}>{renderValue()}</View>
      {!!caption && (
        <Text numberOfLines={1} style={[typography.micro, { color: t.fg }]}>
          {caption}
        </Text>
      )}
    </Card3D>
  );
};

const styles = StyleSheet.create({
  top: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  iconBox: {
    width: 34,
    height: 34,
    borderRadius: 11,
    alignItems: 'center',
    justifyContent: 'center',
  },
  icon: {
    fontSize: 17,
  },
  label: {
    flex: 1,
  },
  value: {
    marginTop: spacing.sm,
    minHeight: 30,
    justifyContent: 'center',
  },
  loader: {
    alignSelf: 'flex-start',
  },
});

export default StatTile;
