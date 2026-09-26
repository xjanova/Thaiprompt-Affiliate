/**
 * KanokTabBar — แถบเมนูล่าง "ซุ้มกนก" (แบบ B ที่เจ้าของเลือก 2026-09-26)
 *
 * หน้าตา:
 * - แถบงาช้าง (สว่าง) / มิดไนท์ (มืด) เส้นทองบางด้านบน ตรงกลางยกเป็นโดมรับซุ้ม
 * - ซุ้มกนกทองนูน (ภาพจาก ChatGPT) คร่อมกลางแถบ หางกนกทอดยาวไปตามเส้นทอง
 * - เหรียญตรากนกขอบทองลงยากรมท่า + ไอคอนตะกร้าทอง = ปุ่ม "ตลาดสด" ในช่องโค้งของซุ้ม
 * - แท็บที่เลือก = ไอคอนทึบสีทอง + ชื่อสีทอง + ขีดทองใต้ชื่อ (สปริงเบาๆ) · ไม่เลือก = ไอคอนเส้นสีจาง
 *
 * เรขาคณิตอ้างอิงภาพ tabbar-kanok-arch.webp (วัดจากภาพจริงด้วย PIL):
 *   อัตราส่วน 4.28:1 · เส้นหางกนก (baseline) อยู่ที่ 96.3% ของความสูง
 *   ช่องโค้งด้านใน: ขอบบนที่ 53.8% ของความสูง กว้างราว 18.7% ของความกว้าง จุดศูนย์กลางราวเส้นหาง
 *   เหรียญตรา: แผ่นน้ำเงินด้านในกว้าง 61% ของเหรียญ
 *
 * แยกเป็น 2 ชั้น (สำคัญ):
 *   1. KanokTabBar — แถบจริงของ navigator: พื้น เส้นทอง แท็บ 4 ช่อง + ช่องว่างกลาง
 *   2. KanokCrest — เลเยอร์ลอยวางใน root ของ (tabs)/_layout (กินพื้นที่เต็มจอ): ไล่สีจาง โดม ซุ้ม เหรียญ
 *   ทำไม: บน Android ส่วนที่ยื่นเกินขอบ tab bar ถูกตัดทิ้ง (ทดสอบบนเครื่องจริง 2026-09-26: โดม/ไล่สีหายหมด)
 *   และแตะส่วนที่ยื่นเกินขอบไม่ติด — ย้ายไปไว้ในเลเยอร์ที่ครอบทั้งจอจึงวาดครบและแตะเหรียญได้ทั้งวง
 * การแตะ: ไล่สี/โดม/ซุ้มไม่รับการแตะ (pointerEvents none) เลเยอร์เป็น box-none → นิ้วทะลุถึงแท็บและเนื้อหาข้างหลัง
 */

import React, { useEffect } from 'react';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { Pressable, StyleSheet, View, useWindowDimensions } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring, withTiming } from 'react-native-reanimated';
import Svg, { Path } from 'react-native-svg';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '@/components/ui/Text';
import { Icon, type IconName } from '@/components/ui/Icon';
import { selectionHaptic, tapHaptic } from '@/components/ui/haptics';
import { FONT, useTheme, withAlpha } from '@/theme';
import type { Tabs } from 'expo-router';

/** props ที่ expo-router ส่งให้ tabBar (ดึงชนิดจาก Tabs เอง ไม่ import จาก path ภายในของไลบรารี) */
type TabBarProps = Parameters<NonNullable<React.ComponentProps<typeof Tabs>['tabBar']>>[0];

const ARCH_ART = require('@/assets/images/brand/tabbar-kanok-arch.webp');
const MEDALLION_ART = require('@/assets/images/brand/tabbar-kanok-medallion.webp');

// ---------- เรขาคณิต (dp) ----------
/** ความสูงแถบ (ไม่รวม safe area ด้านล่าง) */
export const KANOK_BAR_HEIGHT = 64;
/** เหรียญตรากลาง */
const MEDALLION = 56;
/**
 * ขนาดซุ้ม: เจ้าของให้ย่อ (2026-09-26 "ใหญ่มากไป เอาให้พอดีๆ") — ซุ้มกว้าง 214dp ยอดเปลวสูงเหนือแถบ ~48dp
 * ช่องโค้งของซุ้ม (18.7% ของภาพ ≈ 40dp) แคบกว่าเหรียญ → ขอบเหรียญทับวงในของซุ้ม เห็นเป็นมงกุฎกนกรอบเหรียญ
 */
const ARCH_ASPECT = 4.28;
const ARCH_BASELINE_RATIO = 0.963;
const ARCH_WIDTH = 214;
const ARCH_HEIGHT = ARCH_WIDTH / ARCH_ASPECT; // ≈ 50
/** เส้นหางกนกวางทับเส้นทองของแถบพอดี */
const ARCH_TOP = -ARCH_HEIGHT * ARCH_BASELINE_RATIO;
/** โดมรับซุ้ม (อยู่หลังซุ้ม — กันเนื้อหาข้างหลังโผล่ผ่านช่องว่างระหว่างลายกนกช่วงล่าง) */
const DOME_RADIUS = MEDALLION / 2 + 8;
const DOME_RISE = 22;
/** จุดกึ่งกลางเหรียญ: ต่ำกว่าเส้นแถบเล็กน้อย ให้ครึ่งล่างอยู่ในแถบ (แตะง่าย) */
const MEDALLION_CENTER_Y = 4;
/** แถบไล่สีเหนือแถบ: เนื้อหาข้างหลังจางลงก่อนถึงหางกนก (ตัวหนังสือไม่โผล่ลอดลายให้รก) */
const FADE_HEIGHT = 34;
/** ความสูงเผื่อด้านล่างของเนื้อหาในแท็บ (ยอดซุ้มยื่นขึ้นมาทับท้ายรายการ) */
export const KANOK_CREST_CLEARANCE = 36;

/** แท็บหนึ่งช่อง — name ต้องตรงกับไฟล์ใน app/(tabs) */
export interface KanokTab {
  name: string;
  label: string;
  icon: IconName;
  /** แสดงรูปโปรไฟล์แทนไอคอน */
  avatarUrl?: string | null;
}

export interface KanokTabBarProps extends TabBarProps {
  /** แท็บซ้าย (2 ช่อง) และขวา (2 ช่อง) ของเหรียญกลาง */
  left: KanokTab[];
  right: KanokTab[];
  /** ปุ่มเหรียญกลาง — ไม่ส่ง = ไม่มีซุ้ม (แถบเรียบ 4 แท็บ) */
  center?: { label: string; icon: IconName; onPress: () => void; accessibilityHint?: string };
}

/** ช่องแท็บหนึ่งช่อง */
const TabSlot: React.FC<{
  tab: KanokTab;
  focused: boolean;
  onPress: () => void;
  onLongPress: () => void;
}> = ({ tab, focused, onPress, onLongPress }) => {
  const { colors, isDark } = useTheme();
  const progress = useSharedValue(focused ? 1 : 0);

  useEffect(() => {
    progress.value = withSpring(focused ? 1 : 0, { damping: 15, stiffness: 220 });
  }, [focused, progress]);

  const iconAnim = useAnimatedStyle(() => ({
    transform: [{ translateY: -1.5 * progress.value }, { scale: 0.94 + 0.06 * progress.value }],
  }));
  const barAnim = useAnimatedStyle(() => ({
    opacity: progress.value,
    transform: [{ scaleX: 0.3 + 0.7 * progress.value }],
  }));

  const activeColor = isDark ? colors.gold : colors.goldDeep;
  const color = focused ? activeColor : colors.tabInactive;

  return (
    <Pressable
      onPress={onPress}
      onLongPress={onLongPress}
      accessibilityRole="tab"
      accessibilityLabel={tab.label}
      accessibilityState={{ selected: focused }}
      style={styles.slot}
      hitSlop={{ top: 6 }}
    >
      <Animated.View style={[styles.iconBox, iconAnim]}>
        {tab.avatarUrl ? (
          <View style={[styles.avatarRing, { borderColor: focused ? activeColor : withAlpha(colors.tabInactive, 0.5) }]}>
            <Image source={{ uri: tab.avatarUrl }} style={styles.avatar} contentFit="cover" />
          </View>
        ) : (
          <Icon name={tab.icon} size={24} color={color} weight={focused ? 'fill' : 'regular'} />
        )}
      </Animated.View>
      <Text numberOfLines={1} style={[styles.label, { color }, focused && styles.labelActive]}>
        {tab.label}
      </Text>
      <Animated.View style={[styles.activeBar, { backgroundColor: activeColor }, barAnim]} />
    </Pressable>
  );
};

/** เหรียญตรากลาง (ตลาดสด) */
const Medallion: React.FC<{ icon: IconName; label: string; onPress: () => void; hint?: string }> = ({
  icon,
  label,
  onPress,
  hint,
}) => {
  const { colors, isDark } = useTheme();
  const scale = useSharedValue(1);
  const anim = useAnimatedStyle(() => ({ transform: [{ scale: scale.value }] }));

  return (
    <Pressable
      onPress={() => {
        tapHaptic();
        onPress();
      }}
      onPressIn={() => {
        scale.value = withTiming(0.93, { duration: 90 });
      }}
      onPressOut={() => {
        scale.value = withSpring(1, { damping: 10, stiffness: 260 });
      }}
      accessibilityRole="button"
      accessibilityLabel={label}
      accessibilityHint={hint}
      style={styles.medallionPress}
    >
      <Animated.View style={[styles.medallion, anim]}>
        <Image source={MEDALLION_ART} style={StyleSheet.absoluteFill} contentFit="contain" transition={0} />
        <Icon name={icon} size={25} color={isDark ? colors.goldLight : '#F3DC9B'} weight="fill" />
      </Animated.View>
      <Text numberOfLines={1} style={[styles.label, styles.centerLabel, { color: isDark ? colors.gold : colors.goldDeep }]}>
        {label}
      </Text>
    </Pressable>
  );
};

/**
 * โดมรับซุ้ม: โค้งขึ้นจากเส้นแถบ (y = baseY) แล้วลงกลับ — fill ลงมาทับเส้นทองตรงช่วงโดมให้ต่อเนียน
 */
const buildDomePath = (width: number, baseY: number): { fill: string; stroke: string } => {
  const cx = width / 2;
  const r = DOME_RADIUS;
  const shoulder = 22; // ความยาวไหล่โค้งเข้าโดม (นุ่ม ไม่หักมุม)
  const x1 = cx - r - shoulder;
  const x2 = cx + r + shoulder;
  const top = baseY - DOME_RISE;
  const edge = `M${x1} ${baseY} C${x1 + shoulder * 0.9} ${baseY} ${cx - r * 0.95} ${top} ${cx} ${top} C${cx + r * 0.95} ${top} ${x2 - shoulder * 0.9} ${baseY} ${x2} ${baseY}`;
  return { fill: `${edge} V${baseY + 2} H${x1} Z`, stroke: edge };
};

/** สีพื้นแถบ/เส้นทอง ใช้ร่วมกันทั้งแถบและเลเยอร์ซุ้ม */
const useBarColors = () => {
  const { colors, isDark } = useTheme();
  return {
    barColor: isDark ? '#0F131C' : '#FFFDF8',
    hairline: withAlpha(isDark ? colors.gold : '#CFA349', isDark ? 0.32 : 0.6),
  };
};

export const KanokTabBar: React.FC<KanokTabBarProps> = ({ state, navigation, left, right, center }) => {
  const { isDark } = useTheme();
  const insets = useSafeAreaInsets();
  const { width } = useWindowDimensions();

  const bottomPad = Math.max(insets.bottom, 8);
  const barHeight = KANOK_BAR_HEIGHT + bottomPad;
  const { barColor, hairline } = useBarColors();

  const focusedName = state.routes[state.index]?.name;

  const renderTab = (tab: KanokTab) => {
    const route = state.routes.find((r) => r.name === tab.name);
    if (!route) return null;
    const focused = focusedName === tab.name;
    const onPress = () => {
      selectionHaptic();
      const event = navigation.emit({ type: 'tabPress', target: route.key, canPreventDefault: true });
      if (!focused && !event.defaultPrevented) {
        navigation.navigate(route.name, route.params);
      }
    };
    const onLongPress = () => navigation.emit({ type: 'tabLongPress', target: route.key });
    return <TabSlot key={tab.name} tab={tab} focused={focused} onPress={onPress} onLongPress={onLongPress} />;
  };

  return (
    <View style={[styles.root, { height: barHeight }]} accessibilityRole="tablist">
      {/* พื้นแถบ + เส้นทองด้านบน (โดม/ซุ้ม/เหรียญอยู่ใน KanokCrest) */}
      <Svg pointerEvents="none" width={width} height={barHeight} style={[styles.svg, !isDark && styles.lightShadow]}>
        <Path d={`M0 0 H${width} V${barHeight} H0 Z`} fill={barColor} />
        <Path d={`M0 0.6 H${width}`} stroke={hairline} strokeWidth={1.2} fill="none" />
      </Svg>

      <View style={[styles.row, { paddingBottom: bottomPad }]}>
        {left.map(renderTab)}
        {center ? <View style={styles.centerSpacer} /> : null}
        {right.map(renderTab)}
      </View>
    </View>
  );
};

/** พื้นที่เหนือเส้นแถบที่เลเยอร์ซุ้มต้องใช้ (ยอดเปลวกนก) */
const CREST_SPACE = Math.ceil(-ARCH_TOP) + 2;

export interface KanokCrestProps {
  /** ปุ่มเหรียญกลาง — ไม่ส่ง = ไม่แสดงอะไรเลย */
  center?: KanokTabBarProps['center'];
}

/**
 * เลเยอร์ซุ้มกนก + เหรียญตรา — วางเป็นลูกตัวท้ายของ root ใน (tabs)/_layout (ครอบทั้งจอ)
 * ตำแหน่งอิงเส้นบนของแถบ: bottom 0 + ความสูงแถบ (รวม safe area) เท่ากับ KanokTabBar เป๊ะ
 */
export const KanokCrest: React.FC<KanokCrestProps> = ({ center }) => {
  const insets = useSafeAreaInsets();
  const { width } = useWindowDimensions();
  const { barColor, hairline } = useBarColors();

  if (!center) return null;

  const barHeight = KANOK_BAR_HEIGHT + Math.max(insets.bottom, 8);
  const baseY = CREST_SPACE; // เส้นบนของแถบในพิกัดของเลเยอร์
  const dome = buildDomePath(width, DOME_RISE);

  return (
    <View pointerEvents="box-none" style={[styles.crest, { height: CREST_SPACE + barHeight }]}>
      {/* ไล่สีจางเหนือแถบ: เนื้อหาข้างหลังจางลงก่อนถึงหางกนก */}
      <LinearGradient
        pointerEvents="none"
        colors={[withAlpha(barColor, 0), withAlpha(barColor, 0.94)]}
        start={{ x: 0, y: 0 }}
        end={{ x: 0, y: 1 }}
        style={[styles.fade, { top: baseY - FADE_HEIGHT }]}
      />

      {/* โดมรับซุ้ม (ทับเส้นทองช่วงกลาง) */}
      <Svg pointerEvents="none" width={width} height={DOME_RISE + 2} style={[styles.svg, { top: baseY - DOME_RISE }]}>
        <Path d={dome.fill} fill={barColor} />
        <Path d={dome.stroke} stroke={hairline} strokeWidth={1.2} fill="none" />
      </Svg>

      {/* ซุ้มกนกทอง */}
      <Image
        pointerEvents="none"
        source={ARCH_ART}
        contentFit="contain"
        transition={0}
        accessible={false}
        style={[styles.arch, { left: (width - ARCH_WIDTH) / 2, top: baseY + ARCH_TOP }]}
      />

      {/* เหรียญตรากลาง */}
      <View
        pointerEvents="box-none"
        style={[
          styles.medallionWrap,
          { left: width / 2 - MEDALLION / 2 - 8, top: baseY + MEDALLION_CENTER_Y - MEDALLION / 2 },
        ]}
      >
        <Medallion icon={center.icon} label={center.label} onPress={center.onPress} hint={center.accessibilityHint} />
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  root: {
    overflow: 'visible',
  },
  crest: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
  },
  svg: {
    position: 'absolute',
    left: 0,
  },
  lightShadow: {
    // เงานุ่มใต้ขอบบน (โหมดสว่าง) ให้แถบลอยจากเนื้อหาเล็กน้อย
    boxShadow: '0px -10px 24px -18px rgba(16,34,63,0.35)',
  },
  fade: {
    position: 'absolute',
    left: 0,
    right: 0,
    height: FADE_HEIGHT,
  },
  arch: {
    position: 'absolute',
    width: ARCH_WIDTH,
    height: ARCH_HEIGHT,
  },
  row: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'flex-start',
    paddingTop: 10,
  },
  slot: {
    flex: 1,
    alignItems: 'center',
  },
  iconBox: {
    height: 28,
    justifyContent: 'center',
    alignItems: 'center',
  },
  label: {
    fontFamily: FONT.semibold,
    fontSize: 11.5,
    marginTop: 2,
  },
  labelActive: {
    fontFamily: FONT.bold,
  },
  activeBar: {
    width: 18,
    height: 3,
    borderRadius: 2,
    marginTop: 3,
  },
  centerSpacer: {
    width: MEDALLION + 26,
  },
  medallionWrap: {
    position: 'absolute',
    width: MEDALLION + 16,
    alignItems: 'center',
    overflow: 'visible',
  },
  medallionPress: {
    alignItems: 'center',
  },
  medallion: {
    width: MEDALLION,
    height: MEDALLION,
    alignItems: 'center',
    justifyContent: 'center',
  },
  // ให้ป้าย "ตลาดสด" อยู่แนวเดียวกับป้ายแท็บอื่น: ป้ายแท็บเริ่มที่ 10 + 28 + 2 = 40dp จากเส้นแถบ
  // ใต้เหรียญ = MEDALLION_CENTER_Y + MEDALLION / 2 = 32dp → เว้นอีก 8dp
  centerLabel: {
    marginTop: 40 - (MEDALLION_CENTER_Y + MEDALLION / 2),
  },
  avatarRing: {
    width: 28,
    height: 28,
    borderRadius: 14,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  avatar: {
    width: 22,
    height: 22,
    borderRadius: 11,
  },
});

export default KanokTabBar;
