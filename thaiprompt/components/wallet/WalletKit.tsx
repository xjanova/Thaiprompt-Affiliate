/**
 * WalletKit — ชิ้นส่วนหน้าตาที่หน้ากระเป๋าเงินใช้ร่วมกัน (เติมเงิน / ถอนเงิน / ประวัติ / โอนเงิน)
 * ธีมรอยัล น้ำเงินกรมท่า-ทอง — สีทั้งหมดมาจาก useTheme() รองรับโหมดมืด/สว่าง
 *
 * - IconTile   ไอคอนในสี่เหลี่ยมมน (44×44 มุม 15) พื้นอ่อนตามโทน — หัวแถวรายการ/หัวการ์ด
 * - NavyCard   การ์ดน้ำเงินกรมท่าแบบบัตรโลหะ + ลายกนกทองจาง (ยอดเงิน / ยอดที่จะเติม)
 * - MoneyText  ตัวเลขเงินแบบบัตร: บาทตัวใหญ่ + สตางค์ตัวเล็ก (฿2,450.00)
 * - MoneyInput ช่องกรอกจำนวนเงินตัวเลขใหญ่ + ฿ สีทอง · โฟกัส = ขอบทอง
 * - StepTrack  แถบขั้นตอนแบบวงกลม + เส้นทอง
 * - ActionBar  แถบปุ่มหลักลอยท้ายจอ (พื้นการ์ด + เส้นบน + เว้น safe area)
 * - InfoRow    แถวป้ายซ้าย-ค่าขวา ในกล่องสรุป
 *
 * @example
 * <NavyCard>
 *   <MoneyText text={formatCurrency(balance)} color={colors.goldLight} />
 * </NavyCard>
 */

import React, { forwardRef, useState } from 'react';
import {
  Platform,
  StyleSheet,
  View,
  type StyleProp,
  type TextInputProps,
  type TextStyle,
  type ViewStyle,
} from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text, TextInput } from '@/components/ui/Text';
import { Icon, type IconName, type IconWeight } from '@/components/ui/Icon';
import { useTheme, radii, shadowStyle, spacing, typography, withAlpha, type ThemeColors } from '@/theme';

const KANOK = require('@/assets/images/brand/kanok-gold.webp');
/** สัดส่วนภาพลายกนก (กว้าง 900 × สูง 791) */
const KANOK_RATIO = 791 / 900;

// =====================================================
// IconTile
// =====================================================

export type TileTone = 'navy' | 'gold' | 'success' | 'danger' | 'warning' | 'info';

/**
 * สีพื้น/ไอคอนของสี่เหลี่ยมไอคอนตามโทน
 * โทนน้ำเงินในโหมดมืดใช้ฟ้าอ่อน (info) ให้ไอคอนอ่านออกบนการ์ดมืด
 */
export const tileColors = (tone: TileTone, colors: ThemeColors, isDark: boolean): { bg: string; fg: string } => {
  switch (tone) {
    case 'gold':
      return { bg: colors.goldSoft, fg: colors.goldDeep };
    case 'success':
      return { bg: colors.successSoft, fg: colors.success };
    case 'danger':
      return { bg: colors.dangerSoft, fg: colors.danger };
    case 'warning':
      return { bg: colors.warningSoft, fg: colors.warning };
    case 'info':
      return { bg: colors.infoSoft, fg: colors.info };
    case 'navy':
    default:
      return { bg: colors.navySoft, fg: isDark ? colors.info : colors.navy };
  }
};

export interface IconTileProps {
  icon: IconName;
  tone?: TileTone;
  /** ขนาดกล่อง (ค่าเริ่มต้น 44) — มุมโค้งปรับตามขนาดให้เอง */
  size?: number;
  weight?: IconWeight;
  style?: StyleProp<ViewStyle>;
}

export const IconTile: React.FC<IconTileProps> = ({ icon, tone = 'navy', size = 44, weight = 'regular', style }) => {
  const { colors, isDark } = useTheme();
  const c = tileColors(tone, colors, isDark);
  return (
    <View
      style={[
        styles.tile,
        { width: size, height: size, borderRadius: Math.round(size * 0.34), backgroundColor: c.bg },
        style,
      ]}
    >
      <Icon name={icon} size={Math.round(size * 0.5)} color={c.fg} weight={weight} />
    </View>
  );
};

// =====================================================
// NavyCard
// =====================================================

export interface NavyCardProps {
  children?: React.ReactNode;
  /** style กรอบนอก (margin) */
  style?: StyleProp<ViewStyle>;
  /** style พื้นที่เนื้อหา (padding / จัดกลาง) */
  contentStyle?: StyleProp<ViewStyle>;
  /** ความกว้างลายกนก (ค่าเริ่มต้น 200) */
  ornamentWidth?: number;
}

/** การ์ดน้ำเงินกรมท่าแบบบัตรโลหะ — ตัวอักษรข้างในใช้ colors.onHeader / onHeaderMuted / goldLight */
export const NavyCard: React.FC<NavyCardProps> = ({ children, style, contentStyle, ornamentWidth = 200 }) => {
  const { colors, gradients, isDark } = useTheme();
  return (
    <View style={[styles.navyOuter, shadowStyle('lg', colors.shadowDark), style]}>
      <View style={[styles.navyInner, { borderColor: withAlpha(colors.gold, isDark ? 0.3 : 0.42) }]}>
        <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={StyleSheet.absoluteFill} />
        {/* ประกายกระจกจางๆ มุมซ้ายบน */}
        <LinearGradient
          colors={gradients.glass}
          start={{ x: 0, y: 0 }}
          end={{ x: 0.8, y: 0.9 }}
          style={StyleSheet.absoluteFill}
          pointerEvents="none"
        />
        <Image
          source={KANOK}
          contentFit="contain"
          accessible={false}
          pointerEvents="none"
          style={[
            styles.navyKanok,
            { width: ornamentWidth, height: ornamentWidth * KANOK_RATIO, opacity: isDark ? 0.3 : 0.4 },
          ]}
        />
        <View style={[styles.navyContent, contentStyle]}>{children}</View>
      </View>
    </View>
  );
};

// =====================================================
// MoneyText
// =====================================================

export interface MoneyTextProps {
  /** ข้อความเงินที่จัดรูปแบบแล้ว เช่น "฿2,450.00" / "+฿500.00" */
  text: string;
  color: string;
  /** ขนาดตัวเลขบาท (ค่าเริ่มต้น 36) — สตางค์เล็กลงให้เอง */
  size?: number;
  style?: StyleProp<TextStyle>;
}

/** ตัวเลขเงินแบบบัตร — บาทตัวใหญ่ สตางค์ตัวเล็ก · โปรแกรมอ่านหน้าจออ่าน "... บาท" แบบเดียวกับ PriceText */
export const MoneyText: React.FC<MoneyTextProps> = ({ text, color, size = 36, style }) => {
  const match = /^(.*?)(\.\d{2})$/.exec(text);
  return (
    <Text
      numberOfLines={1}
      adjustsFontSizeToFit
      accessibilityLabel={`${text.replace('฿', '')} บาท`}
      style={[
        styles.money,
        { fontSize: size, lineHeight: Math.round(size * 1.24), color },
        style,
      ]}
    >
      {match ? match[1] : text}
      {match ? <Text style={[styles.moneyFraction, { fontSize: Math.round(size * 0.56) }]}>{match[2]}</Text> : null}
    </Text>
  );
};

// =====================================================
// MoneyInput
// =====================================================

export interface MoneyInputProps extends TextInputProps {
  /** ขนาดตัวเลข (ค่าเริ่มต้น 38) */
  fontSize?: number;
  /** style กรอบช่องกรอก */
  containerStyle?: StyleProp<ViewStyle>;
}

/** ช่องกรอกจำนวนเงินตัวเลขใหญ่ — ฿ สีทองเมื่อมีค่า/โฟกัส · ขอบทองตอนโฟกัส */
export const MoneyInput = forwardRef<TextInput, MoneyInputProps>(function MoneyInput(
  { fontSize = 38, containerStyle, style, onFocus, onBlur, ...rest },
  ref
) {
  const { colors } = useTheme();
  const [focused, setFocused] = useState(false);
  const active = focused || !!rest.value;

  return (
    <View
      style={[
        styles.moneyBox,
        { backgroundColor: colors.inset, borderColor: focused ? colors.gold : colors.border },
        containerStyle,
      ]}
    >
      <Text
        accessibilityElementsHidden
        importantForAccessibility="no-hide-descendants"
        style={[styles.moneyPrefix, { fontSize: Math.round(fontSize * 0.66), color: active ? colors.goldDeep : colors.textFaint }]}
      >
        ฿
      </Text>
      <TextInput
        ref={ref}
        placeholderTextColor={colors.textFaint}
        selectionColor={colors.gold}
        {...rest}
        onFocus={(e) => {
          setFocused(true);
          onFocus?.(e);
        }}
        onBlur={(e) => {
          setFocused(false);
          onBlur?.(e);
        }}
        style={[styles.moneyInput, { fontSize, color: colors.textStrong }, style]}
      />
    </View>
  );
});

// =====================================================
// StepTrack
// =====================================================

export interface StepTrackProps {
  /** ชื่อขั้นตอนเรียงตามลำดับ */
  steps: string[];
  /** ขั้นปัจจุบัน (เริ่มที่ 0) */
  current: number;
  style?: StyleProp<ViewStyle>;
}

/** แถบขั้นตอน: ผ่านแล้ว = วงน้ำเงินติ๊กทอง · ปัจจุบัน = วงทอง · ยังไม่ถึง = วงยุบ */
export const StepTrack: React.FC<StepTrackProps> = ({ steps, current, style }) => {
  const { colors, gradients } = useTheme();
  const last = steps.length - 1;

  return (
    <View
      accessibilityRole="progressbar"
      accessibilityLabel={`ขั้นที่ ${current + 1} จาก ${steps.length} ${steps[current] ?? ''}`}
      style={[styles.track, style]}
    >
      {steps.map((label, i) => {
        const done = i < current;
        const active = i === current;
        return (
          <View key={label} style={styles.trackStep}>
            <View style={styles.trackLineRow}>
              <View
                style={[
                  styles.trackLine,
                  { backgroundColor: i === 0 ? 'transparent' : i <= current ? colors.gold : colors.border },
                ]}
              />
              {active ? (
                <LinearGradient colors={gradients.primary} start={{ x: 0, y: 0 }} end={{ x: 0, y: 1 }} style={styles.trackDot}>
                  <Text style={[styles.trackNum, { color: colors.textOnGold }]}>{i + 1}</Text>
                </LinearGradient>
              ) : (
                <View
                  style={[
                    styles.trackDot,
                    done
                      ? { backgroundColor: colors.navyFill }
                      : { backgroundColor: colors.inset, borderWidth: 1, borderColor: colors.border },
                  ]}
                >
                  {done ? (
                    <Icon name="check" size={13} color={colors.goldLight} weight="bold" />
                  ) : (
                    <Text style={[styles.trackNum, { color: colors.textFaint }]}>{i + 1}</Text>
                  )}
                </View>
              )}
              <View
                style={[
                  styles.trackLine,
                  { backgroundColor: i === last ? 'transparent' : i < current ? colors.gold : colors.border },
                ]}
              />
            </View>
            <Text
              numberOfLines={1}
              style={[
                typography.micro,
                styles.trackLabel,
                { color: active ? colors.goldDeep : done ? colors.text : colors.textFaint },
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
// ActionBar
// =====================================================

/** เงาชี้ขึ้นของแถบท้ายจอ (Android เก่าใช้ elevation) */
const upShadow = (color: string): ViewStyle => {
  if (Platform.OS === 'android' && typeof Platform.Version === 'number' && Platform.Version < 28) {
    return { elevation: 12, shadowColor: color };
  }
  return { boxShadow: `0px -10px 24px -14px ${withAlpha(color, 0.35)}` };
};

export interface ActionBarProps {
  children?: React.ReactNode;
  style?: StyleProp<ViewStyle>;
}

/** แถบปุ่มหลักลอยท้ายจอ — วางต่อท้าย ScrollView (นอก ScrollView) ภายใน KeyboardAvoidingView */
export const ActionBar: React.FC<ActionBarProps> = ({ children, style }) => {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  return (
    <View
      style={[
        styles.bar,
        {
          backgroundColor: colors.card,
          borderTopColor: colors.divider,
          paddingBottom: Math.max(insets.bottom, spacing.md) + spacing.xs,
        },
        upShadow(colors.shadowDark),
        style,
      ]}
    >
      {children}
    </View>
  );
};

// =====================================================
// InfoRow
// =====================================================

export interface InfoRowProps {
  label: string;
  /** ค่าเป็นข้อความ — หรือส่ง children เป็น element เอง (เช่น PriceText) */
  value?: string;
  children?: React.ReactNode;
  /** แถวเด่น (ยอดรวม) */
  strong?: boolean;
  style?: StyleProp<ViewStyle>;
}

export const InfoRow: React.FC<InfoRowProps> = ({ label, value, children, strong = false, style }) => {
  const { colors } = useTheme();
  return (
    <View style={[styles.infoRow, style]}>
      <Text
        style={[
          strong ? typography.bodyStrong : typography.bodySm,
          styles.infoLabel,
          { color: strong ? colors.textStrong : colors.textMuted },
        ]}
      >
        {label}
      </Text>
      {value !== undefined ? (
        <Text style={[typography.bodyStrong, styles.infoValue, { color: colors.textStrong }]}>{value}</Text>
      ) : (
        children
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  tile: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  navyOuter: {
    borderRadius: 24,
  },
  navyInner: {
    borderRadius: 24,
    borderWidth: 1,
    overflow: 'hidden',
  },
  navyKanok: {
    position: 'absolute',
    top: -16,
    right: -46,
  },
  navyContent: {
    padding: spacing.xl,
  },
  money: {
    fontWeight: '700',
    letterSpacing: -0.6,
    fontVariant: ['tabular-nums'],
  },
  moneyFraction: {
    fontWeight: '700',
    letterSpacing: 0,
  },
  moneyBox: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1.5,
    borderRadius: 18,
    paddingHorizontal: spacing.lg,
    minHeight: 72,
    gap: spacing.sm,
  },
  moneyPrefix: {
    fontWeight: '700',
  },
  moneyInput: {
    flex: 1,
    fontWeight: '700',
    fontVariant: ['tabular-nums'],
    paddingVertical: spacing.sm,
  },
  track: {
    flexDirection: 'row',
  },
  trackStep: {
    flex: 1,
    alignItems: 'center',
    gap: 5,
  },
  trackLineRow: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'stretch',
  },
  trackLine: {
    flex: 1,
    height: 2,
    borderRadius: 1,
  },
  trackDot: {
    width: 26,
    height: 26,
    borderRadius: 13,
    alignItems: 'center',
    justifyContent: 'center',
  },
  trackNum: {
    fontSize: 12,
    lineHeight: 16,
    fontWeight: '700',
  },
  trackLabel: {
    textAlign: 'center',
  },
  bar: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.md,
    paddingVertical: spacing.xs,
  },
  infoLabel: {
    flexShrink: 1,
  },
  infoValue: {
    textAlign: 'right',
    fontVariant: ['tabular-nums'],
  },
});
