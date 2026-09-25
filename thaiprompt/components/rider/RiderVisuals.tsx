/**
 * ชิ้นส่วนหน้าตาที่ใช้ร่วมกันในหน้าไรเดอร์ (งานใกล้ฉัน · รายละเอียดงาน · รายได้ · เอกสาร)
 *
 * ธีมรอยัล น้ำเงินกรมท่า-ทอง — สีมาจาก useTheme() เท่านั้น
 * - โหมดมืด: ของที่เป็น "น้ำเงิน" ในโหมดสว่าง (ไอคอน ตัวเลขเงิน จุดส่ง) เปลี่ยนเป็นทอง/งาช้าง
 *   เพราะน้ำเงินกรมท่าจะจมหายบนการ์ดมืด
 * - ทุกชิ้นเป็นหน้าตาล้วน ไม่มี logic ของงาน (สถานะ/API อยู่ที่หน้าจอ)
 */

import React, { forwardRef, useEffect, useMemo, useState } from 'react';
import {
  Pressable,
  StyleSheet,
  View,
  type LayoutChangeEvent,
  type StyleProp,
  type ViewStyle,
} from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import Svg, { Circle, Path } from 'react-native-svg';
import Animated, {
  Easing,
  cancelAnimation,
  useAnimatedStyle,
  useSharedValue,
  withRepeat,
  withTiming,
} from 'react-native-reanimated';
import { Text, TextInput, type TextInputProps } from '@/components/ui/Text';
import {
  Card3D,
  Icon,
  OnHeaderProvider,
  RoyalHeader,
  tapHaptic,
  usePressGuard,
  type IconName,
  type IconWeight,
} from '@/components/ui';
import {
  useTheme,
  radii,
  spacing,
  typography,
  shadowStyle,
  toneColors,
  withAlpha,
  type GradientTuple,
  type ThemeColors,
  type Tone,
} from '@/theme';
import { num } from '@/services/api/client';

// =====================================================
// สีเฉพาะหน้าไรเดอร์
// =====================================================

/** โทนของช่องไอคอน: navy = พื้นน้ำเงินอ่อน ไอคอนน้ำเงิน (ค่าเริ่มต้นของแอป) */
export type TileTone = Tone | 'navy';

/**
 * สีหลักของหน้าไรเดอร์ตามโหมด
 * accent = ไอคอน/เส้นเน้น · money = ตัวเลขเงินบนการ์ดขาว · pickup/dropoff = สีจุดรับ/จุดส่ง
 */
export const useRiderTones = () => {
  const { colors, isDark } = useTheme();
  return useMemo(
    () => ({
      accent: isDark ? colors.gold : colors.navy,
      money: isDark ? colors.goldLight : colors.navy,
      pickup: colors.gold,
      dropoff: isDark ? colors.textStrong : colors.navy,
    }),
    [colors, isDark]
  );
};

/** สีพื้น/ไอคอนของช่องไอคอนตามโทน */
export const tileColors = (tone: TileTone, colors: ThemeColors, isDark: boolean): { bg: string; fg: string } => {
  if (tone === 'navy') return { bg: colors.navySoft, fg: isDark ? colors.goldLight : colors.navy };
  if (tone === 'neutral') return { bg: colors.inset, fg: colors.textMuted };
  const t = toneColors(tone, colors);
  return { bg: t.bg, fg: t.fg };
};

/** ไอคอนตามประเภทงาน (job_type จาก server) */
export const jobTypeIcon = (jobType: string | null | undefined): IconName => {
  switch (jobType) {
    case 'food':
      return 'bowl-food';
    case 'fresh_market':
      return 'basket';
    case 'shop_delivery':
      return 'storefront';
    case 'document':
      return 'file-text';
    default:
      return 'package';
  }
};

// =====================================================
// ช่องไอคอน 44×44 มุม 15
// =====================================================

export interface IconTileProps {
  icon: IconName;
  tone?: TileTone;
  size?: number;
  iconSize?: number;
  weight?: IconWeight;
  style?: StyleProp<ViewStyle>;
}

export const IconTile: React.FC<IconTileProps> = ({ icon, tone = 'navy', size = 44, iconSize, weight, style }) => {
  const { colors, isDark } = useTheme();
  const c = tileColors(tone, colors, isDark);
  return (
    <View
      style={[
        styles.center,
        { width: size, height: size, borderRadius: Math.round(size * 0.34), backgroundColor: c.bg },
        style,
      ]}
    >
      <Icon name={icon} size={iconSize ?? Math.round(size * 0.5)} color={c.fg} weight={weight} />
    </View>
  );
};

// =====================================================
// ปุ่มไอคอนสี่เหลี่ยม (โทร / นำทาง) — กันกดซ้ำในตัว
// =====================================================

export interface ActionIconProps {
  icon: IconName;
  /** ข้อความสำหรับโปรแกรมอ่านหน้าจอ */
  label: string;
  onPress: () => unknown;
  tone?: TileTone;
  size?: number;
  style?: StyleProp<ViewStyle>;
}

export const ActionIcon: React.FC<ActionIconProps> = ({ icon, label, onPress, tone = 'navy', size = 44, style }) => {
  const { colors, isDark } = useTheme();
  const { run } = usePressGuard(onPress);
  const c = tileColors(tone, colors, isDark);
  return (
    <Pressable
      onPress={() => {
        tapHaptic();
        run();
      }}
      accessibilityRole="button"
      accessibilityLabel={label}
      hitSlop={6}
      style={({ pressed }) => [
        styles.center,
        {
          width: size,
          height: size,
          borderRadius: Math.round(size * 0.34),
          backgroundColor: c.bg,
          opacity: pressed ? 0.8 : 1,
          transform: [{ scale: pressed ? 0.94 : 1 }],
        },
        isDark && { borderWidth: 1, borderColor: colors.border },
        style,
      ]}
    >
      <Icon name={icon} size={Math.round(size * 0.48)} color={c.fg} />
    </Pressable>
  );
};

// =====================================================
// ปุ่มข้อความเล็ก (คืนงาน / ส่งไม่สำเร็จ) — ไม่แย่งสายตาปุ่มทองหลัก
// =====================================================

export interface TextActionProps {
  icon: IconName;
  title: string;
  onPress: () => unknown;
  color?: string;
}

export const TextAction: React.FC<TextActionProps> = ({ icon, title, onPress, color }) => {
  const { colors } = useTheme();
  const { run } = usePressGuard(onPress);
  const fg = color ?? colors.textMuted;
  return (
    <Pressable
      onPress={() => {
        tapHaptic();
        run();
      }}
      accessibilityRole="button"
      accessibilityLabel={title}
      hitSlop={10}
      style={({ pressed }) => [styles.textAction, { opacity: pressed ? 0.55 : 1 }]}
    >
      <Icon name={icon} size={17} color={fg} />
      <Text style={[typography.bodySm, styles.textActionLabel, { color: fg }]}>{title}</Text>
    </Pressable>
  );
};

// =====================================================
// การ์ดน้ำเงินกรมท่าลายกนก (ฮีโร่ของหน้า)
// =====================================================

const GOLD_BORDER = 1.5;

export interface NavyCardProps {
  children?: React.ReactNode;
  style?: StyleProp<ViewStyle>;
  contentStyle?: StyleProp<ViewStyle>;
  padding?: number;
  radius?: number;
  /** ขอบทองไล่เฉด (การ์ดสำคัญ) */
  goldBorder?: boolean;
  ornamentWidth?: number;
}

/**
 * พื้นน้ำเงินกรมท่า + ลายกนกทองมุมขวาบน (ใช้ RoyalHeader ตัวเดียวกับหัวหน้าจอ)
 * ของข้างในถือว่า "อยู่บนหัวน้ำเงิน" → Pill / ปุ่ม secondary เป็นแบบกระจกให้เอง
 */
export const NavyCard: React.FC<NavyCardProps> = ({
  children,
  style,
  contentStyle,
  padding = spacing.lg + 2,
  radius = radii.xl,
  goldBorder = false,
  ornamentWidth = 190,
}) => {
  const { colors, gradients } = useTheme();
  const innerRadius = goldBorder ? radius - GOLD_BORDER : radius;

  const body = (
    <RoyalHeader
      ornamentWidth={ornamentWidth}
      ornamentTop={-Math.round(ornamentWidth * 0.32)}
      style={[
        { borderRadius: innerRadius, padding },
        !goldBorder && { borderWidth: 1, borderColor: colors.headerGlassBorder },
        contentStyle,
      ]}
    >
      <OnHeaderProvider value>{children}</OnHeaderProvider>
    </RoyalHeader>
  );

  return (
    <View style={[{ borderRadius: radius }, shadowStyle('lg', colors.shadowDark), style]}>
      {goldBorder ? (
        <LinearGradient
          colors={gradients.goldBorder}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={{ borderRadius: radius, padding: GOLD_BORDER }}
        >
          {body}
        </LinearGradient>
      ) : (
        body
      )}
    </View>
  );
};

// =====================================================
// การ์ดแจ้งเตือนในหน้า (สถานะพิเศษ / คำเตือน)
// =====================================================

export interface NoticeCardProps {
  icon: IconName;
  tone?: TileTone;
  /** ไม่ส่ง = แสดงเฉพาะข้อความ (สีตัวอักษรปกติ) */
  title?: string;
  /** สีหัวข้อ (ไม่ส่ง = ตัวอักษรเข้ม) */
  titleColor?: string;
  message?: React.ReactNode;
  children?: React.ReactNode;
  /** มีปุ่มปิดมุมขวา */
  onClose?: () => void;
  closeLabel?: string;
  /** ขอบไล่เฉด: true = ทอง หรือส่ง gradient เอง */
  gradientBorder?: boolean | GradientTuple;
  style?: StyleProp<ViewStyle>;
}

export const NoticeCard: React.FC<NoticeCardProps> = ({
  icon,
  tone = 'navy',
  title,
  titleColor,
  message,
  children,
  onClose,
  closeLabel = 'ปิด',
  gradientBorder = false,
  style,
}) => {
  const { colors } = useTheme();
  return (
    <Card3D shadow="sm" padding={spacing.md + 2} radius={radii.lg + 1} gradientBorder={gradientBorder} style={style}>
      <View style={styles.noticeRow}>
        <IconTile icon={icon} tone={tone} size={40} />
        <View style={[styles.flex, !title && styles.noticeBodyOnly]}>
          {!!title && <Text style={[typography.bodyStrong, { color: titleColor ?? colors.textStrong }]}>{title}</Text>}
          {typeof message === 'string' ? (
            !!message && (
              <Text
                style={[typography.bodySm, !!title && styles.noticeMessage, { color: title ? colors.textMuted : colors.text }]}
              >
                {message}
              </Text>
            )
          ) : (
            message
          )}
        </View>
        {!!onClose && (
          <Pressable
            onPress={onClose}
            hitSlop={10}
            accessibilityRole="button"
            accessibilityLabel={closeLabel}
            style={({ pressed }) => [styles.noticeClose, { backgroundColor: colors.inset, opacity: pressed ? 0.6 : 1 }]}
          >
            <Icon name="x" size={15} color={colors.textMuted} weight="bold" />
          </Pressable>
        )}
      </View>
      {children ? <View style={styles.noticeChildren}>{children}</View> : null}
    </Card3D>
  );
};

// =====================================================
// จุดรับ / จุดส่ง + เส้นประเชื่อม
// =====================================================

export type StopKind = 'pickup' | 'dropoff';

export interface StopDotProps {
  kind: StopKind;
  size?: number;
  /** วงแสงรอบจุด (จุดหมายตอนนี้) */
  halo?: boolean;
}

export const StopDot: React.FC<StopDotProps> = ({ kind, size = 12, halo = false }) => {
  const { colors } = useTheme();
  const tones = useRiderTones();
  const fill = kind === 'pickup' ? tones.pickup : tones.dropoff;
  const outer = size + (halo ? 12 : 6);
  return (
    <View
      style={[
        styles.center,
        { width: outer, height: outer, borderRadius: outer / 2 },
        halo ? { backgroundColor: withAlpha(fill, 0.18) } : { backgroundColor: colors.card },
      ]}
    >
      <View
        style={{
          width: size,
          height: size,
          borderRadius: size / 2,
          backgroundColor: fill,
          borderWidth: halo ? 2 : 0,
          borderColor: colors.card,
        }}
      />
    </View>
  );
};

/** เส้นประแนวตั้ง (จุดเล็กๆ เรียงกัน — ใช้แทน borderStyle dashed ที่ iOS วาดด้านเดียวไม่ได้) */
export const DottedLine: React.FC<{ color?: string; style?: StyleProp<ViewStyle> }> = ({ color, style }) => {
  const { colors } = useTheme();
  return (
    <View style={[styles.dotted, style]} pointerEvents="none">
      {[0, 1, 2, 3].map((i) => (
        <View key={i} style={[styles.dottedDot, { backgroundColor: color ?? colors.textFaint }]} />
      ))}
    </View>
  );
};

export interface RouteStop {
  kind: StopKind;
  title: React.ReactNode;
  /** บรรทัดรอง (ค่าว่างจะถูกข้าม) รวมกันด้วย " · " */
  meta?: Array<string | null | undefined | false>;
  /** ข้อความเล็กเพิ่มเติมบรรทัดสุดท้าย */
  note?: string | null;
}

/** รายการจุดรับ → จุดส่ง แบบการ์ดงานในม็อกอัป (จุดทอง = รับ · จุดน้ำเงิน = ส่ง) */
export const RouteStops: React.FC<{ stops: RouteStop[]; style?: StyleProp<ViewStyle> }> = ({ stops, style }) => {
  const { colors } = useTheme();
  return (
    <View style={style}>
      {stops.map((stop, index) => {
        const isLast = index === stops.length - 1;
        const meta = (stop.meta || []).filter(Boolean).join(' · ');
        return (
          <View key={`${stop.kind}-${index}`} style={[styles.stopRow, !isLast && styles.stopRowGap]}>
            <View style={styles.stopRail}>
              <StopDot kind={stop.kind} size={11} />
              {!isLast && <DottedLine style={styles.stopLine} />}
            </View>
            <View style={styles.flex}>
              {typeof stop.title === 'string' ? (
                <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
                  {stop.title}
                </Text>
              ) : (
                stop.title
              )}
              {!!meta && (
                <Text numberOfLines={2} style={[typography.caption, { color: colors.textMuted }]}>
                  {meta}
                </Text>
              )}
              {!!stop.note && (
                <Text numberOfLines={2} style={[typography.caption, { color: colors.textFaint }]}>
                  {stop.note}
                </Text>
              )}
            </View>
          </View>
        );
      })}
    </View>
  );
};

// =====================================================
// แผนที่ประกอบ + เส้นทาง (ภาพประกอบ ไม่ใช่แผนที่จริง)
// =====================================================

const MAP_ART = require('@/assets/images/brand/map-light.webp');
/** สัดส่วนภาพแผนที่ (กว้าง 1000 × สูง 563) */
const MAP_RATIO = 1000 / 563;

type LatLngLike = { latitude?: unknown; longitude?: unknown } | null | undefined;

/**
 * ทิศจากจุดรับไปจุดส่งบนจอ (x ขวา = ตะวันออก · y ลง = ใต้) เป็นเวกเตอร์หนึ่งหน่วย
 * ไม่มีพิกัด/พิกัด 0,0/จุดเดียวกัน = null
 */
const routeDirection = (from: LatLngLike, to: LatLngLike): { x: number; y: number } | null => {
  if (!from || !to) return null;
  const la1 = num(from.latitude, NaN);
  const lo1 = num(from.longitude, NaN);
  const la2 = num(to.latitude, NaN);
  const lo2 = num(to.longitude, NaN);
  if (![la1, lo1, la2, lo2].every(Number.isFinite)) return null;
  if ((la1 === 0 && lo1 === 0) || (la2 === 0 && lo2 === 0)) return null;
  const x = (lo2 - lo1) * Math.cos((la1 * Math.PI) / 180);
  const y = -(la2 - la1);
  const length = Math.hypot(x, y);
  if (length < 1e-7) return null;
  return { x: x / length, y: y / length };
};

/** เลขสุ่มคงที่ 0..1 จาก seed (ให้แต่ละงานได้มุมแผนที่ไม่ซ้ำกัน แต่ไม่เปลี่ยนทุก render) */
const seeded = (seed: number, salt: number): number => {
  const x = Math.sin((seed + 1) * 12.9898 + salt * 78.233) * 43758.5453;
  return x - Math.floor(x);
};

export interface MapRouteStripProps {
  from?: LatLngLike;
  to?: LatLngLike;
  /** เลือกมุมแผนที่/ความโค้งของเส้น (เช่น job.id) */
  seed: number;
  height?: number;
  /** มุมโค้งด้านบน ให้ตรงกับการ์ด */
  radius?: number;
  /** ป้ายลอยบนแผนที่ */
  children?: React.ReactNode;
}

/**
 * แถบแผนที่ประกอบหัวการ์ดงาน — เส้นทองจากจุดรับ (ทอง) ไปจุดส่ง (น้ำเงิน)
 * ทิศของเส้นอิงพิกัดจริงของงาน (ถ้ามี) แต่ภาพพื้นเป็นภาพประกอบของแบรนด์
 */
export const MapRouteStrip: React.FC<MapRouteStripProps> = ({ from, to, seed, height = 104, radius = radii.xl, children }) => {
  const { colors, isDark } = useTheme();
  const tones = useRiderTones();
  const [width, setWidth] = useState(0);

  const onLayout = (event: LayoutChangeEvent) => {
    const w = Math.round(event.nativeEvent.layout.width);
    if (w > 0 && w !== width) setWidth(w);
  };

  const geometry = useMemo(() => {
    if (width <= 0) return null;
    // ภาพขยายใหญ่กว่ากรอบ แล้วเลื่อนมุมตาม seed
    const imgW = Math.max(width * 1.55, height * MAP_RATIO * 1.2);
    const imgH = imgW / MAP_RATIO;
    const left = -(imgW - width) * seeded(seed, 1);
    const top = -(imgH - height) * seeded(seed, 2);

    // เส้นทาง: ปลายสองด้านอยู่ในกรอบ (เว้นขอบให้ป้ายด้านบน)
    const dir = routeDirection(from, to) ?? { x: 0.94, y: -0.34 };
    const padX = 30;
    const padTop = 34;
    const padBottom = 20;
    const cx = width / 2;
    const cy = padTop + (height - padTop - padBottom) / 2;
    const ax = Math.max(1, width / 2 - padX);
    const ay = Math.max(1, (height - padTop - padBottom) / 2);
    const t = 1 / Math.max(Math.abs(dir.x) / ax, Math.abs(dir.y) / ay);
    const p1 = { x: cx - dir.x * t, y: cy - dir.y * t };
    const p2 = { x: cx + dir.x * t, y: cy + dir.y * t };
    // จุดควบคุมเส้นโค้ง: ตั้งฉากกับเส้นตรง โค้งซ้าย/ขวาตาม seed
    const length = Math.hypot(p2.x - p1.x, p2.y - p1.y);
    const bend = (seeded(seed, 3) > 0.5 ? 1 : -1) * Math.max(14, length * 0.2);
    const c = {
      x: Math.min(width - 8, Math.max(8, (p1.x + p2.x) / 2 - dir.y * bend)),
      y: Math.min(height - 8, Math.max(8, (p1.y + p2.y) / 2 + dir.x * bend)),
    };
    return { imgW, imgH, left, top, p1, p2, c };
  }, [width, height, seed, from, to]);

  const dot = (kind: StopKind, point: { x: number; y: number }) => {
    const size = 16;
    const fill = kind === 'pickup' ? tones.pickup : tones.dropoff;
    return (
      <View
        style={[
          styles.mapDot,
          {
            left: point.x - size / 2,
            top: point.y - size / 2,
            width: size,
            height: size,
            borderRadius: size / 2,
            backgroundColor: fill,
            borderColor: colors.card,
          },
          shadowStyle('sm', colors.shadowDark),
        ]}
      />
    );
  };

  return (
    <View
      onLayout={onLayout}
      pointerEvents="none"
      accessibilityElementsHidden
      importantForAccessibility="no-hide-descendants"
      style={[
        styles.map,
        { height, borderTopLeftRadius: radius, borderTopRightRadius: radius, backgroundColor: colors.inset },
      ]}
    >
      {geometry && (
        <>
          <Image
            source={MAP_ART}
            contentFit="cover"
            accessible={false}
            transition={0}
            style={{
              position: 'absolute',
              width: geometry.imgW,
              height: geometry.imgH,
              left: geometry.left,
              top: geometry.top,
              opacity: isDark ? 0.14 : 1,
            }}
          />
          <Svg width={width} height={height} style={StyleSheet.absoluteFill}>
            <Path
              d={`M ${geometry.p1.x} ${geometry.p1.y} Q ${geometry.c.x} ${geometry.c.y} ${geometry.p2.x} ${geometry.p2.y}`}
              stroke={withAlpha(colors.gold, isDark ? 0.28 : 0.35)}
              strokeWidth={9}
              strokeLinecap="round"
              fill="none"
            />
            <Path
              d={`M ${geometry.p1.x} ${geometry.p1.y} Q ${geometry.c.x} ${geometry.c.y} ${geometry.p2.x} ${geometry.p2.y}`}
              stroke={colors.gold}
              strokeWidth={3.5}
              strokeLinecap="round"
              fill="none"
            />
          </Svg>
          {dot('pickup', geometry.p1)}
          {dot('dropoff', geometry.p2)}
        </>
      )}
      <View style={styles.mapOverlay}>{children}</View>
    </View>
  );
};

/** ป้ายเล็กลอยบนแผนที่ */
export const MapTag: React.FC<{ icon?: IconName; label: string; iconColor?: string }> = ({ icon, label, iconColor }) => {
  const { colors, isDark } = useTheme();
  return (
    <View
      style={[
        styles.mapTag,
        { backgroundColor: colors.card },
        isDark && { borderWidth: 1, borderColor: colors.border },
        shadowStyle('sm', colors.shadowDark),
      ]}
    >
      {!!icon && <Icon name={icon} size={13} color={iconColor ?? colors.goldDeep} weight="fill" />}
      <Text numberOfLines={1} style={[typography.micro, { color: colors.textStrong }]}>
        {label}
      </Text>
    </View>
  );
};

// =====================================================
// ช่องพิมพ์ (พื้นยุบ · โฟกัส = ขอบทอง)
// =====================================================

export interface FocusInputProps extends TextInputProps {
  /** ข้อมูลไม่ถูกต้อง = ขอบแดง */
  invalid?: boolean;
}

export const FocusInput = forwardRef<TextInput, FocusInputProps>(function FocusInput(
  { invalid = false, style, onFocus, onBlur, multiline, ...rest },
  ref
) {
  const { colors } = useTheme();
  const [focused, setFocused] = useState(false);
  return (
    <TextInput
      ref={ref}
      placeholderTextColor={colors.textFaint}
      {...rest}
      multiline={multiline}
      onFocus={(event) => {
        setFocused(true);
        onFocus?.(event);
      }}
      onBlur={(event) => {
        setFocused(false);
        onBlur?.(event);
      }}
      style={[
        typography.body,
        styles.input,
        multiline && styles.inputMultiline,
        {
          color: colors.text,
          backgroundColor: colors.inset,
          borderColor: invalid ? colors.danger : focused ? colors.gold : colors.border,
        },
        style,
      ]}
    />
  );
});

// =====================================================
// วงแหวนความคืบหน้า
// =====================================================

export interface ProgressRingProps {
  /** 0..1 */
  progress: number;
  size?: number;
  stroke?: number;
  color: string;
  track: string;
  children?: React.ReactNode;
}

export const ProgressRing: React.FC<ProgressRingProps> = ({ progress, size = 64, stroke = 6, color, track, children }) => {
  const r = (size - stroke) / 2;
  const circumference = 2 * Math.PI * r;
  const p = Math.max(0, Math.min(1, progress));
  return (
    <View style={{ width: size, height: size }}>
      <Svg width={size} height={size} style={styles.ringSvg}>
        <Circle cx={size / 2} cy={size / 2} r={r} stroke={track} strokeWidth={stroke} fill="none" />
        {p > 0 && (
          <Circle
            cx={size / 2}
            cy={size / 2}
            r={r}
            stroke={color}
            strokeWidth={stroke}
            fill="none"
            strokeLinecap="round"
            strokeDasharray={`${circumference} ${circumference}`}
            strokeDashoffset={circumference * (1 - p)}
          />
        )}
      </Svg>
      <View style={[StyleSheet.absoluteFill, styles.center]}>{children}</View>
    </View>
  );
};

// =====================================================
// จุดสถานะสด (กะพริบเบาๆ ตอนกำลังค้นหางาน)
// =====================================================

export const LiveDot: React.FC<{ live: boolean; color: string; size?: number }> = ({ live, color, size = 8 }) => {
  const pulse = useSharedValue(0);

  useEffect(() => {
    if (live) {
      pulse.value = 0;
      pulse.value = withRepeat(withTiming(1, { duration: 1500, easing: Easing.out(Easing.quad) }), -1, false);
    } else {
      cancelAnimation(pulse);
      pulse.value = 0;
    }
    return () => cancelAnimation(pulse);
  }, [live, pulse]);

  const ring = useAnimatedStyle(() => ({
    opacity: 0.5 * (1 - pulse.value),
    transform: [{ scale: 1 + 1.8 * pulse.value }],
  }));

  return (
    <View style={[styles.center, { width: size * 2.5, height: size * 2.5 }]}>
      {live && (
        <Animated.View
          pointerEvents="none"
          style={[{ position: 'absolute', width: size, height: size, borderRadius: size / 2, backgroundColor: color }, ring]}
        />
      )}
      <View style={{ width: size, height: size, borderRadius: size / 2, backgroundColor: color }} />
    </View>
  );
};

// =====================================================

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  textAction: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    minHeight: 40,
    paddingHorizontal: spacing.sm,
  },
  textActionLabel: {
    fontWeight: '600',
  },
  noticeRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  noticeMessage: {
    marginTop: 1,
  },
  noticeBodyOnly: {
    minHeight: 40,
    justifyContent: 'center',
  },
  noticeClose: {
    width: 28,
    height: 28,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  noticeChildren: {
    marginTop: spacing.md,
  },
  stopRow: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  stopRowGap: {
    paddingBottom: spacing.md,
  },
  stopRail: {
    width: 17,
    alignItems: 'center',
    paddingTop: 3,
  },
  stopLine: {
    flex: 1,
    marginTop: 4,
    marginBottom: -spacing.md + 2,
  },
  dotted: {
    alignItems: 'center',
    justifyContent: 'space-evenly',
    minHeight: 18,
  },
  dottedDot: {
    width: 2.5,
    height: 2.5,
    borderRadius: 1.25,
  },
  map: {
    overflow: 'hidden',
  },
  mapDot: {
    position: 'absolute',
    borderWidth: 3,
  },
  mapOverlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    padding: spacing.md,
  },
  mapTag: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: radii.pill,
    alignSelf: 'flex-start',
  },
  input: {
    borderRadius: radii.md - 1,
    borderWidth: 1,
    paddingHorizontal: spacing.md + 2,
    paddingVertical: spacing.md - 1,
    minHeight: 50,
  },
  inputMultiline: {
    minHeight: 88,
    textAlignVertical: 'top',
  },
  ringSvg: {
    transform: [{ rotate: '-90deg' }],
  },
});
