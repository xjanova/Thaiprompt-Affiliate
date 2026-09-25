/**
 * Design tokens — ธีม "นวลทองคำ" (Nuan Gold Clay) สำหรับแอป
 *
 * ถอดค่ามาจากเว็บ Theme V4 (resources/css/theme-v4.css + layouts/admin-v4 applyTheme)
 *   light: bg #f3eee4 · surf #ece5d8 · sd #cdc1ad · sl #fffdf7 · ink #544c40 · ink2 #9a8f7c
 *   dark : bg #1d1912 · surf #272118 · sd #100d08 · sl #352b1d · ink #ece3d2 · ink2 #a89c84
 *   ทอง : accent1 #e6b347 · accent2 #d98e3f · deep1 #b8892a · deep2 #b06f2f
 *
 * หลักการ
 *   - หน้าจอใหม่ทุกหน้าอ่านสีจาก useTheme().colors เท่านั้น ห้ามฮาร์ดโค้ดสี
 *   - เงาใช้ shadowStyle() (boxShadow ของ New Architecture) — Android รุ่นเก่ากว่า API 28 ใช้ elevation แทน
 *   - gradient ส่งให้ expo-linear-gradient ได้ตรงๆ (tuple อย่างน้อย 2 สี)
 */

import { Platform, type ViewStyle } from 'react-native';

// =====================================================
// ชนิดข้อมูล
// =====================================================

/** gradient ของ expo-linear-gradient ต้องมีอย่างน้อย 2 สี */
export type GradientTuple = readonly [string, string, ...string[]];

/** โทนสีที่ component ใช้ร่วมกัน (Chip, StatTile, Pill ฯลฯ) */
export type Tone = 'neutral' | 'gold' | 'success' | 'danger' | 'info' | 'warning';

export interface ThemeColors {
  /** พื้นหลังหน้าจอ */
  background: string;
  /** พื้นผิวดินเหนียว (การ์ด/แท็บบาร์) */
  surface: string;
  /** การ์ดที่ยกขึ้นจากพื้น (สว่างกว่าพื้นเล็กน้อย) */
  card: string;
  /** พื้นช่องกด/ช่องกรอก (ยุบลง) */
  inset: string;
  /** เงาด้านมืดของดินเหนียว */
  shadowDark: string;
  /** เงาด้านสว่างของดินเหนียว */
  shadowLight: string;
  /** เส้นขอบบางๆ */
  border: string;
  /** เส้นแบ่ง */
  divider: string;

  /** ตัวอักษรหลัก */
  text: string;
  /** ตัวอักษรเน้น (หัวข้อ) */
  textStrong: string;
  /** ตัวอักษรรอง */
  textMuted: string;
  /** ตัวอักษรจาง (placeholder) */
  textFaint: string;
  /** ตัวอักษรบนพื้นสีเข้ม (ปุ่มเขียว/แดง, ป้ายแจ้งเตือน) — ขาว */
  textOnAccent: string;
  /**
   * ตัวอักษรบนพื้นทอง (ปุ่มหลัก, ชิปที่เลือก, ป้ายทอง) — น้ำตาลเข้ม
   * ขาวบนทองได้ contrast แค่ 1.6–2.8:1 (อ่านไม่ออกกลางแดด) · สีนี้ได้ ≥ 4.9:1 ทุกจุดของไล่เฉด
   */
  textOnGold: string;

  /** ทองหลัก */
  gold: string;
  /** ทองเข้ม (ขอบล่างปุ่ม/ตัวอักษรเน้น) */
  goldDeep: string;
  /** อำพัน */
  amber: string;
  /** อำพันเข้ม */
  amberDeep: string;
  /** ทองอ่อน (พื้นป้าย) */
  goldSoft: string;
  /** อำพันอ่อน */
  amberSoft: string;

  success: string;
  successSoft: string;
  danger: string;
  dangerSoft: string;
  info: string;
  infoSoft: string;
  warning: string;
  warningSoft: string;

  /** ม่านมืดหลัง modal */
  overlay: string;
  /** สีไอคอนแท็บที่ไม่ได้เลือก */
  tabInactive: string;
}

export interface ThemeGradients {
  primary: GradientTuple;
  secondary: GradientTuple;
  success: GradientTuple;
  danger: GradientTuple;
  gold: GradientTuple;
  goldBorder: GradientTuple;
  surface: GradientTuple;
  /** ม่านไล่เฉดจากใสลงมามืด สำหรับ banner */
  bannerScrim: GradientTuple;
  hero: GradientTuple;
}

// =====================================================
// พาเลตต์ดิบ
// =====================================================

export const palette = {
  gold50: '#FBF6E6',
  gold100: '#F6ECCF',
  gold200: '#F0DCA3',
  gold300: '#EDC76E',
  gold400: '#E6B347',
  gold500: '#D9A038',
  gold600: '#B8892A',
  gold700: '#936B1E',
  /** ทองเข้มสำหรับตัวอักษรบนพื้นสว่าง (≥ 4.5:1 บนพื้นดินเหนียวทุกระดับ) */
  gold750: '#7F5C18',
  gold800: '#6E4F15',
  gold900: '#46320C',

  amber100: '#F4E3CC',
  amber300: '#E8AE6A',
  amber400: '#D98E3F',
  amber600: '#B06F2F',
  amber800: '#7A4A1C',

  clayLight: '#F3EEE4',
  clayLightSurface: '#ECE5D8',
  clayLightCard: '#F8F4EC',
  clayLightShadow: '#CDC1AD',
  clayLightHighlight: '#FFFDF7',

  clayDark: '#1D1912',
  clayDarkSurface: '#272118',
  clayDarkCard: '#2D261C',
  clayDarkShadow: '#100D08',
  clayDarkHighlight: '#352B1D',

  inkLight: '#544C40',
  inkLightStrong: '#3A342B',
  inkLightMuted: '#857A67',
  inkLightFaint: '#A89E8B',
  /** ไอคอน/ป้ายแท็บที่ไม่ได้เลือก (≥ 4.5:1 บนการ์ด) */
  inkLightTab: '#756A58',

  inkDark: '#ECE3D2',
  inkDarkStrong: '#FBF5EA',
  inkDarkMuted: '#A89C84',
  inkDarkFaint: '#7D7260',

  green400: '#5CC98A',
  green500: '#34A96A',
  green700: '#1F7A48',
  red400: '#F07A6A',
  red500: '#D9483B',
  red700: '#A8322A',
  blue400: '#5B9BD8',
  blue500: '#3A7BC0',
  orange400: '#F0A24A',
  orange500: '#D98324',

  white: '#FFFFFF',
  black: '#000000',
} as const;

// =====================================================
// สีตามโหมด
// =====================================================

export const lightColors: ThemeColors = {
  background: palette.clayLight,
  surface: palette.clayLightSurface,
  card: palette.clayLightCard,
  inset: '#E6DECF',
  shadowDark: palette.clayLightShadow,
  shadowLight: palette.clayLightHighlight,
  border: 'rgba(84, 76, 64, 0.12)',
  divider: 'rgba(84, 76, 64, 0.08)',

  text: palette.inkLight,
  textStrong: palette.inkLightStrong,
  textMuted: palette.inkLightMuted,
  textFaint: palette.inkLightFaint,
  textOnAccent: palette.white,
  textOnGold: '#3A2A0C',

  gold: palette.gold400,
  // ใช้เป็นสีตัวอักษรเน้น (ปุ่ม ghost / แท็บที่เลือก / ลิงก์) → ต้องอ่านออกบนพื้นสว่าง
  goldDeep: palette.gold750,
  amber: palette.amber400,
  amberDeep: palette.amber600,
  goldSoft: palette.gold100,
  amberSoft: palette.amber100,

  success: palette.green500,
  successSoft: '#DDF1E3',
  danger: palette.red500,
  dangerSoft: '#F8DFDA',
  info: palette.blue500,
  infoSoft: '#DCE9F5',
  warning: palette.orange500,
  warningSoft: '#F7E6CF',

  overlay: 'rgba(29, 25, 18, 0.55)',
  tabInactive: palette.inkLightTab,
};

export const darkColors: ThemeColors = {
  background: palette.clayDark,
  surface: palette.clayDarkSurface,
  card: palette.clayDarkCard,
  inset: '#1A160F',
  shadowDark: palette.clayDarkShadow,
  shadowLight: palette.clayDarkHighlight,
  border: 'rgba(236, 227, 210, 0.10)',
  divider: 'rgba(236, 227, 210, 0.06)',

  text: palette.inkDark,
  textStrong: palette.inkDarkStrong,
  textMuted: palette.inkDarkMuted,
  textFaint: palette.inkDarkFaint,
  textOnAccent: palette.white,
  textOnGold: '#2A1D06',

  gold: palette.gold300,
  goldDeep: palette.gold500,
  amber: palette.amber300,
  amberDeep: palette.amber400,
  goldSoft: 'rgba(230, 179, 71, 0.16)',
  amberSoft: 'rgba(217, 142, 63, 0.16)',

  success: palette.green400,
  successSoft: 'rgba(92, 201, 138, 0.16)',
  danger: palette.red400,
  dangerSoft: 'rgba(240, 122, 106, 0.16)',
  info: palette.blue400,
  infoSoft: 'rgba(91, 155, 216, 0.16)',
  warning: palette.orange400,
  warningSoft: 'rgba(240, 162, 74, 0.16)',

  overlay: 'rgba(0, 0, 0, 0.65)',
  tabInactive: palette.inkDarkMuted,
};

export const lightGradients: ThemeGradients = {
  // primary ใช้ตัวอักษร textOnGold (เข้ม) · success/danger ใช้ตัวอักษรขาว → ไล่เฉดเข้มพอให้ได้ ≥ 4.5:1
  primary: ['#EFC25A', '#E3A948', '#D48A3A'],
  secondary: ['#FBF8F1', '#EDE6D8'],
  success: ['#27844F', '#1A6B3E'],
  danger: ['#C8483C', '#A8322A'],
  gold: ['#F6DC8E', '#E6B347', '#C98A2E'],
  goldBorder: ['#F3D27A', '#D98E3F', '#F3D27A'],
  surface: ['#FAF6EE', '#EFE8DB'],
  bannerScrim: ['rgba(20, 16, 10, 0)', 'rgba(20, 16, 10, 0.35)', 'rgba(20, 16, 10, 0.82)'],
  hero: ['#F7E7BE', '#EFD49A', '#E7BD74'],
};

export const darkGradients: ThemeGradients = {
  primary: ['#EDC76E', '#DDA544', '#C9812F'],
  secondary: ['#3A3124', '#2B241A'],
  success: ['#27844F', '#1A5C38'],
  danger: ['#C44236', '#8E2B23'],
  gold: ['#EDC76E', '#D9A038', '#A8741F'],
  goldBorder: ['#EDC76E', '#B06F2F', '#EDC76E'],
  surface: ['#322A1F', '#272118'],
  bannerScrim: ['rgba(0, 0, 0, 0)', 'rgba(0, 0, 0, 0.4)', 'rgba(0, 0, 0, 0.88)'],
  hero: ['#3B2F1C', '#2E2517', '#221C12'],
};

/** สีขอบล่าง (ความหนา) ของปุ่ม 3D แต่ละแบบ */
export const buttonEdgeColors = {
  light: {
    primary: '#A8741F',
    secondary: '#CDC1AD',
    success: '#145233',
    danger: '#7E2620',
    ghost: 'transparent',
  },
  dark: {
    primary: '#7A5416',
    secondary: '#100D08',
    success: '#103D25',
    danger: '#5E1C18',
    ghost: 'transparent',
  },
} as const;

// =====================================================
// ระยะ / มุมโค้ง / ตัวอักษร
// =====================================================

export const spacing = {
  xxs: 2,
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 20,
  xxl: 24,
  xxxl: 32,
  /** ระยะขอบซ้ายขวาของหน้าจอ */
  screen: 16,
} as const;

export const radii = {
  xs: 6,
  sm: 10,
  md: 14,
  lg: 18,
  xl: 22,
  xxl: 28,
  pill: 999,
} as const;

/** ขนาดตัวอักษร (ไม่กำหนด fontFamily — ใช้ฟอนต์ระบบที่รองรับภาษาไทย) */
export const typography = {
  display: { fontSize: 28, lineHeight: 36, fontWeight: '800' as const },
  h1: { fontSize: 22, lineHeight: 30, fontWeight: '800' as const },
  h2: { fontSize: 18, lineHeight: 26, fontWeight: '700' as const },
  h3: { fontSize: 16, lineHeight: 23, fontWeight: '700' as const },
  body: { fontSize: 15, lineHeight: 22, fontWeight: '400' as const },
  bodyStrong: { fontSize: 15, lineHeight: 22, fontWeight: '600' as const },
  bodySm: { fontSize: 13, lineHeight: 19, fontWeight: '400' as const },
  caption: { fontSize: 12, lineHeight: 17, fontWeight: '500' as const },
  micro: { fontSize: 11, lineHeight: 15, fontWeight: '600' as const },
  /** ตัวเลขเงิน */
  money: { fontSize: 24, lineHeight: 30, fontWeight: '800' as const },
} as const;

/** ขนาดขั้นต่ำของจุดกด (Material/Apple = 44-48) */
export const MIN_TOUCH = 44;

// =====================================================
// เงาดินเหนียว
// =====================================================

export type ShadowLevel = 'none' | 'sm' | 'md' | 'lg';

const SHADOW_SPEC: Record<Exclude<ShadowLevel, 'none'>, { y: number; blur: number; opacity: number; elevation: number }> = {
  sm: { y: 3, blur: 8, opacity: 0.18, elevation: 3 },
  md: { y: 6, blur: 16, opacity: 0.22, elevation: 6 },
  lg: { y: 12, blur: 26, opacity: 0.28, elevation: 12 },
};

/** แปลง hex (#RRGGBB) เป็น rgba() — ค่าอื่นคืนตามเดิม */
export const withAlpha = (color: string, alpha: number): string => {
  const hex = color.trim();
  if (!/^#([0-9a-f]{6})$/i.test(hex)) {
    return color;
  }
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  const a = Math.max(0, Math.min(1, alpha));
  return `rgba(${r}, ${g}, ${b}, ${a})`;
};

/**
 * เงาตกแบบนุ่ม (drop shadow)
 *
 * - New Architecture (SDK 57) รองรับ boxShadow ทั้ง iOS และ Android API 28+
 * - Android ต่ำกว่า API 28 วาด boxShadow ไม่ได้ → ใช้ elevation แทน
 *
 * @param level ระดับความลึก
 * @param color สีเงา (ปกติใช้ shadowDark ของธีม หรือสีปุ่มเพื่อให้เงาเรืองสี)
 */
export const shadowStyle = (level: ShadowLevel, color: string = palette.black): ViewStyle => {
  if (level === 'none') {
    return {};
  }
  const spec = SHADOW_SPEC[level];

  if (Platform.OS === 'android' && typeof Platform.Version === 'number' && Platform.Version < 28) {
    return { elevation: spec.elevation, shadowColor: color };
  }

  return {
    boxShadow: `0px ${spec.y}px ${spec.blur}px ${withAlpha(color, spec.opacity)}`,
  };
};

/**
 * เงาดินเหนียวสองทาง (มืดขวาล่าง + สว่างซ้ายบน) แบบการ์ดบนเว็บ V4
 * ใช้กับพื้นผิวสีเดียวกับพื้นหลังเท่านั้น (ถ้าพื้นต่างสี เงาสว่างจะดูหลอก)
 */
export const clayShadowStyle = (
  level: Exclude<ShadowLevel, 'none'>,
  dark: string,
  light: string
): ViewStyle => {
  const d = { sm: 4, md: 7, lg: 9 }[level];
  const blur = { sm: 9, md: 16, lg: 20 }[level];

  if (Platform.OS === 'android' && typeof Platform.Version === 'number' && Platform.Version < 28) {
    return { elevation: SHADOW_SPEC[level].elevation, shadowColor: dark };
  }

  return {
    boxShadow: `${d}px ${d}px ${blur}px ${dark}, -${d}px -${d}px ${blur}px ${light}`,
  };
};

// =====================================================
// โทนสีสำเร็จรูป (ใช้กับ Chip / StatTile / Pill)
// =====================================================

export interface ToneColors {
  fg: string;
  bg: string;
  border: string;
}

/** คืนสีตัวอักษร/พื้น/ขอบของโทนที่เลือก */
export const toneColors = (tone: Tone, colors: ThemeColors): ToneColors => {
  switch (tone) {
    case 'gold':
      return { fg: colors.goldDeep, bg: colors.goldSoft, border: withAlpha(palette.gold400, 0.35) };
    case 'success':
      return { fg: colors.success, bg: colors.successSoft, border: withAlpha(palette.green500, 0.3) };
    case 'danger':
      return { fg: colors.danger, bg: colors.dangerSoft, border: withAlpha(palette.red500, 0.3) };
    case 'info':
      return { fg: colors.info, bg: colors.infoSoft, border: withAlpha(palette.blue500, 0.3) };
    case 'warning':
      return { fg: colors.warning, bg: colors.warningSoft, border: withAlpha(palette.orange500, 0.3) };
    case 'neutral':
    default:
      return { fg: colors.text, bg: colors.surface, border: colors.border };
  }
};
