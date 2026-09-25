/**
 * ชิ้นส่วนหน้าตาหมวดดูดวง — บรรยากาศ "มิดไนท์-ทอง" ใช้ทั้งโหมดสว่างและโหมดมืด
 *
 * - MysticBackground = พื้นน้ำเงินกรมท่าเต็มจอ (RoyalHeader) + ลายกนกทองสองมุม + ดาวระยิบ/ประกายลอย
 * - GlowHalo         = รัศมีเรืองทองหายใจช้าๆ หลังภาพหลัก
 * - GoldDivider      = เส้นทอง–ข้าวหลามตัด–เส้นทอง คั่นส่วน
 * - GlassPanel       = แผงกระจกทึบบนพื้นเข้ม (ขอบบาง / ขอบทองเมื่อ highlight)
 * - Medallion        = เหรียญตรากลม (ทองฟอยล์ตัวไอคอนน้ำตาล หรือ น้ำเงินขอบทองไอคอนทอง)
 *
 * ทุกสีอ่านจาก useTheme() — พื้นเป็นโทนหัวหน้าจอ (gradients.hero) ตัวอักษรใช้ colors.onHeader*
 */

import React, { useEffect, useMemo, useRef } from 'react';
import { Animated, Dimensions, Easing, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Icon, RoyalHeader, type IconName } from '@/components/ui';
import { useTheme, withAlpha } from '@/theme';

const KANOK = require('@/assets/images/brand/kanok-gold.webp');
/** สัดส่วนภาพลายกนก (กว้าง 900 × สูง 791) */
const KANOK_RATIO = 791 / 900;

// =====================================================
// ดาวระยิบ (อยู่กับที่ กะพริบช้าๆ)
// =====================================================

type StarPosition = { top?: number | `${number}%`; left?: number | `${number}%`; right?: number | `${number}%` };

const TWINKLE_STARS: Array<{ delay: number; size: number; pos: StarPosition }> = [
  { delay: 0, size: 14, pos: { top: 64, left: 28 } },
  { delay: 500, size: 11, pos: { top: 126, right: 38 } },
  { delay: 1000, size: 9, pos: { top: 206, left: 62 } },
  { delay: 300, size: 12, pos: { top: 88, right: 86 } },
  { delay: 700, size: 8, pos: { top: 168, left: 104 } },
  { delay: 1300, size: 10, pos: { top: '58%', right: 22 } },
  { delay: 900, size: 8, pos: { top: '76%', left: 18 } },
];

const TwinkleStar = ({ delay, size, pos, color }: { delay: number; size: number; pos: StarPosition; color: string }) => {
  const opacity = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(0.5)).current;

  useEffect(() => {
    const animation = Animated.loop(
      Animated.sequence([
        Animated.delay(delay),
        Animated.parallel([
          Animated.timing(opacity, { toValue: 1, duration: 1000, useNativeDriver: true }),
          Animated.timing(scale, { toValue: 1, duration: 1000, useNativeDriver: true }),
        ]),
        Animated.parallel([
          Animated.timing(opacity, { toValue: 0.3, duration: 1000, useNativeDriver: true }),
          Animated.timing(scale, { toValue: 0.5, duration: 1000, useNativeDriver: true }),
        ]),
      ])
    );
    animation.start();
    return () => animation.stop();
  }, [delay, opacity, scale]);

  return (
    <Animated.View style={[styles.star, pos, { opacity, transform: [{ scale }] }]}>
      <Icon name="sparkle" size={size} color={color} weight="fill" />
    </Animated.View>
  );
};

// =====================================================
// ประกายลอยขึ้น (ใช้หลังกองไพ่ในหน้าเลือกไพ่)
// =====================================================

const RisingSparkle = ({ delay, color }: { delay: number; color: string }) => {
  const { width, height } = Dimensions.get('window');
  const translateY = useRef(new Animated.Value(height)).current;
  const translateX = useRef(new Animated.Value(Math.random() * width)).current;
  const opacity = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // หยุดวนทันทีเมื่อถูกถอดออกจากจอ (กันแอนิเมชันวนค้างหลังออกจากหน้า)
    let cancelled = false;
    let current: Animated.CompositeAnimation | null = null;

    const animate = () => {
      if (cancelled) return;
      translateY.setValue(height);
      translateX.setValue(Math.random() * width);
      opacity.setValue(0);

      current = Animated.parallel([
        Animated.timing(translateY, {
          toValue: -50,
          duration: 8000 + Math.random() * 4000,
          easing: Easing.linear,
          useNativeDriver: true,
        }),
        Animated.sequence([
          Animated.delay(delay),
          Animated.timing(opacity, { toValue: 0.8, duration: 1000, useNativeDriver: true }),
          Animated.delay(5000),
          Animated.timing(opacity, { toValue: 0, duration: 1000, useNativeDriver: true }),
        ]),
      ]);
      current.start(({ finished }) => {
        if (finished && !cancelled) animate();
      });
    };

    const timer = setTimeout(animate, delay);
    return () => {
      cancelled = true;
      clearTimeout(timer);
      current?.stop();
    };
  }, [delay, height, width, translateX, translateY, opacity]);

  return (
    <Animated.View style={[styles.rising, { opacity, transform: [{ translateX }, { translateY }] }]}>
      <Icon name="sparkle" size={11} color={color} weight="fill" />
    </Animated.View>
  );
};

// =====================================================
// MysticBackground
// =====================================================

export interface MysticBackgroundProps {
  /** ดาวบนพื้นหลัง: twinkle = กะพริบอยู่กับที่ · rise = ลอยขึ้น · none = ไม่มี */
  stars?: 'twinkle' | 'rise' | 'none';
}

/** พื้นหลังเต็มจอของหมวดดูดวง (วางเป็นลูกแรกของหน้าจอ) */
export const MysticBackground: React.FC<MysticBackgroundProps> = ({ stars = 'twinkle' }) => {
  const { colors, isDark } = useTheme();
  const insets = useSafeAreaInsets();
  const rising = useMemo(() => Array.from({ length: 10 }, (_, i) => i * 300), []);
  const kanokWidth = 230;

  return (
    <View pointerEvents="none" style={StyleSheet.absoluteFill}>
      <RoyalHeader style={StyleSheet.absoluteFill} ornamentTop={insets.top - 14} ornamentWidth={240} />
      {/* ลายกนกกลับหัว มุมซ้ายล่าง — ล้อกับกรอบไอคอนแอป */}
      <Image
        source={KANOK}
        contentFit="contain"
        accessible={false}
        style={[
          styles.kanokBottom,
          { width: kanokWidth, height: kanokWidth * KANOK_RATIO, opacity: isDark ? 0.12 : 0.18 },
        ]}
      />
      {stars === 'twinkle' &&
        TWINKLE_STARS.map((s, i) => <TwinkleStar key={i} delay={s.delay} size={s.size} pos={s.pos} color={colors.goldLight} />)}
      {stars === 'rise' && rising.map((delay) => <RisingSparkle key={delay} delay={delay} color={colors.goldLight} />)}
    </View>
  );
};

// =====================================================
// GlowHalo
// =====================================================

/** รัศมีเรืองทองหลังภาพหลัก — หายใจเข้าออกช้าๆ (วางเป็น absolute ตรงกลางกล่องแม่) */
export const GlowHalo = ({ size = 180 }: { size?: number }) => {
  const { colors } = useTheme();
  const pulse = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(pulse, { toValue: 1, duration: 2200, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
        Animated.timing(pulse, { toValue: 0, duration: 2200, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
      ])
    );
    loop.start();
    return () => loop.stop();
  }, [pulse]);

  const opacity = pulse.interpolate({ inputRange: [0, 1], outputRange: [0.55, 1] });
  const scale = pulse.interpolate({ inputRange: [0, 1], outputRange: [0.94, 1.06] });

  return (
    <Animated.View
      pointerEvents="none"
      style={[
        styles.halo,
        {
          width: size,
          height: size,
          borderRadius: size / 2,
          backgroundColor: withAlpha(colors.gold, 0.13),
          boxShadow: `0px 0px 56px 16px ${withAlpha(colors.gold, 0.26)}`,
          opacity,
          transform: [{ scale }],
        },
      ]}
    />
  );
};

// =====================================================
// GoldDivider
// =====================================================

/** เส้นคั่นทอง (จางที่ปลาย) + ข้าวหลามตัดตรงกลาง */
export const GoldDivider = ({ width = 72, style }: { width?: number; style?: StyleProp<ViewStyle> }) => {
  const { colors } = useTheme();
  const clear = withAlpha(colors.gold, 0);
  return (
    <View style={[styles.divider, style]}>
      <LinearGradient colors={[clear, colors.gold]} start={{ x: 0, y: 0 }} end={{ x: 1, y: 0 }} style={[styles.dividerLine, { width }]} />
      <View style={[styles.dividerDiamond, { borderColor: colors.goldLight }]} />
      <LinearGradient colors={[colors.gold, clear]} start={{ x: 0, y: 0 }} end={{ x: 1, y: 0 }} style={[styles.dividerLine, { width }]} />
    </View>
  );
};

// =====================================================
// GlassPanel
// =====================================================

export interface GlassPanelProps {
  children?: React.ReactNode;
  /** ขอบทองเรือง (การ์ดที่ถูกเลือก / การ์ดสำคัญ) */
  highlight?: boolean;
  radius?: number;
  padding?: number;
  style?: StyleProp<ViewStyle>;
}

/** แผงกระจกทึบบนพื้นน้ำเงิน — ไล่เฉดขาวจางจากบนลงล่าง ขอบบาง */
export const GlassPanel: React.FC<GlassPanelProps> = ({ children, highlight = false, radius = 22, padding = 16, style }) => {
  const { colors, gradients } = useTheme();
  return (
    <View
      style={[
        styles.panel,
        {
          borderRadius: radius,
          padding,
          borderColor: highlight ? colors.gold : colors.headerGlassBorder,
          backgroundColor: highlight ? withAlpha(colors.gold, 0.1) : 'transparent',
        },
        style,
      ]}
    >
      <LinearGradient
        colors={gradients.glass}
        start={{ x: 0, y: 0 }}
        end={{ x: 0, y: 1 }}
        style={[StyleSheet.absoluteFill, { borderRadius: radius }]}
        pointerEvents="none"
      />
      {children}
    </View>
  );
};

// =====================================================
// Medallion
// =====================================================

export interface MedallionProps {
  icon: IconName;
  size?: number;
  /** gold = ทองฟอยล์ไอคอนน้ำตาลเข้ม · navy = น้ำเงินขอบทองไอคอนทอง */
  tone?: 'gold' | 'navy';
  style?: StyleProp<ViewStyle>;
}

/** เหรียญตรากลมสำหรับไอคอนหมวด/โหมด */
export const Medallion: React.FC<MedallionProps> = ({ icon, size = 52, tone = 'gold', style }) => {
  const { colors, gradients } = useTheme();
  const gold = tone === 'gold';
  return (
    <LinearGradient
      colors={gold ? gradients.gold : gradients.navy}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={[
        styles.medallion,
        {
          width: size,
          height: size,
          borderRadius: size / 2,
          borderColor: gold ? withAlpha(colors.textOnGold, 0.18) : withAlpha(colors.gold, 0.65),
        },
        style,
      ]}
    >
      <Icon name={icon} size={size * 0.48} color={gold ? colors.textOnGold : colors.goldLight} />
    </LinearGradient>
  );
};

const styles = StyleSheet.create({
  star: {
    position: 'absolute',
  },
  rising: {
    position: 'absolute',
    top: 0,
    left: 0,
  },
  kanokBottom: {
    position: 'absolute',
    bottom: -12,
    left: -56,
    transform: [{ rotate: '180deg' }],
  },
  halo: {
    position: 'absolute',
    alignSelf: 'center',
  },
  divider: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  dividerLine: {
    height: 1,
  },
  dividerDiamond: {
    width: 7,
    height: 7,
    borderWidth: 1,
    transform: [{ rotate: '45deg' }],
  },
  panel: {
    borderWidth: 1,
    overflow: 'hidden',
  },
  medallion: {
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
});
