/**
 * RoyalHeader — พื้นหัวหน้าจอน้ำเงินกรมท่า + ลายกนกทอง (ตัวตนแบรนด์ TP UltraApp)
 *
 * ใช้เป็นพื้นหลังส่วนบนของหน้าจอ (หน้าแรก ตลาดสด ไรเดอร์ กระเป๋าเงิน และหัวของ <Screen>)
 * - ไล่เฉดน้ำเงินกรมท่า + แสงน้ำเงินจางจากมุมขวาบน
 * - ลายกนกทอง (สร้างเฉพาะแบรนด์) มุมขวาบน โปร่งจางไม่แย่งสายตา
 * - GlassIconButton = ปุ่มไอคอนกระจกบนหัวน้ำเงิน (ย้อนกลับ กระดิ่ง ตะกร้า)
 *
 * @example
 * <RoyalHeader style={{ paddingTop: insets.top, paddingBottom: 48 }}>
 *   <Text style={{ color: colors.onHeader }}>สวัสดี</Text>
 * </RoyalHeader>
 */

import React, { createContext, useContext } from 'react';
import { Pressable, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { useTheme } from '@/theme';
import { Text } from './Text';
import { Icon, type IconName, type IconWeight } from './Icon';
import { tapHaptic } from './haptics';

/**
 * บอก component ลูกว่ากำลังวางอยู่บนหัวน้ำเงิน (เช่น ปุ่มด้านขวาของ <Screen>)
 * → Button3D/Pill/ปุ่มตะกร้า เปลี่ยนเป็นแบบกระจกตัวอักษรสว่าง ให้อ่านออกบนพื้นเข้ม
 */
const OnHeaderContext = createContext(false);
export const OnHeaderProvider = OnHeaderContext.Provider;
export const useOnHeader = (): boolean => useContext(OnHeaderContext);

const KANOK = require('@/assets/images/brand/kanok-gold.webp');
/** สัดส่วนภาพลายกนก (กว้าง 900 × สูง 791) */
const KANOK_RATIO = 791 / 900;

export interface RoyalHeaderProps {
  children?: React.ReactNode;
  style?: StyleProp<ViewStyle>;
  /** แสดงลายกนกทอง (ค่าเริ่มต้น true) */
  ornament?: boolean;
  /** ความกว้างลายกนก (ค่าเริ่มต้น 250) */
  ornamentWidth?: number;
  /** ระยะลายกนกจากขอบบน (ปกติส่ง insets.top) */
  ornamentTop?: number;
}

export const RoyalHeader: React.FC<RoyalHeaderProps> = ({
  children,
  style,
  ornament = true,
  ornamentWidth = 250,
  ornamentTop = 0,
}) => {
  const { gradients, isDark } = useTheme();

  return (
    <View style={[styles.root, style]}>
      <LinearGradient colors={gradients.hero} start={{ x: 0, y: 0 }} end={{ x: 0, y: 1 }} style={StyleSheet.absoluteFill} />
      <LinearGradient
        colors={[isDark ? 'rgba(46,84,150,0.20)' : 'rgba(46,84,150,0.50)', 'rgba(46,84,150,0)']}
        start={{ x: 1, y: 0 }}
        end={{ x: 0.35, y: 0.75 }}
        style={StyleSheet.absoluteFill}
        pointerEvents="none"
      />
      {ornament && (
        <Image
          source={KANOK}
          contentFit="contain"
          pointerEvents="none"
          accessible={false}
          style={[
            styles.kanok,
            {
              top: ornamentTop + 10,
              width: ornamentWidth,
              height: ornamentWidth * KANOK_RATIO,
              opacity: isDark ? 0.3 : 0.44,
            },
          ]}
        />
      )}
      {children}
    </View>
  );
};

export interface GlassIconButtonProps {
  icon: IconName;
  onPress?: () => void;
  accessibilityLabel: string;
  /** ตัวเลขป้ายมุมขวาบน (เช่น จำนวนในตะกร้า) */
  badge?: number;
  /** จุดแดงแจ้งเตือน */
  dot?: boolean;
  weight?: IconWeight;
  size?: number;
  style?: StyleProp<ViewStyle>;
}

/** ปุ่มไอคอนกระจกบนหัวน้ำเงิน */
export const GlassIconButton: React.FC<GlassIconButtonProps> = ({
  icon,
  onPress,
  accessibilityLabel,
  badge,
  dot = false,
  weight = 'regular',
  size = 40,
  style,
}) => {
  const { colors, gradients } = useTheme();
  return (
    <Pressable
      onPress={() => {
        tapHaptic();
        onPress?.();
      }}
      disabled={!onPress}
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel}
      hitSlop={6}
      style={({ pressed }) => [
        styles.glass,
        {
          width: size,
          height: size,
          borderRadius: size * 0.36,
          backgroundColor: colors.headerGlass,
          borderColor: colors.headerGlassBorder,
          opacity: pressed ? 0.7 : 1,
        },
        style,
      ]}
    >
      <Icon name={icon} size={size * 0.5} color={colors.onHeader} weight={weight} />
      {typeof badge === 'number' && badge > 0 && (
        <LinearGradient colors={gradients.primary} style={styles.badge}>
          <Text style={[styles.badgeText, { color: colors.textOnGold }]}>{badge > 99 ? '99+' : badge}</Text>
        </LinearGradient>
      )}
      {dot && !badge && <View style={[styles.dot, { backgroundColor: '#FF6B57', borderColor: '#0C1A33' }]} />}
    </Pressable>
  );
};

const styles = StyleSheet.create({
  root: {
    overflow: 'hidden',
  },
  kanok: {
    position: 'absolute',
    right: -44,
  },
  glass: {
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
  badge: {
    position: 'absolute',
    top: -6,
    right: -6,
    minWidth: 20,
    height: 20,
    borderRadius: 10,
    paddingHorizontal: 5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
  dot: {
    position: 'absolute',
    top: 9,
    right: 10,
    width: 9,
    height: 9,
    borderRadius: 5,
    borderWidth: 2,
  },
});

export default RoyalHeader;
