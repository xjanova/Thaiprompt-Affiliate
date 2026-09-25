/**
 * Design tokens — ธีม "รอยัล น้ำเงินกรมท่า-ทอง" (เจ้าของเลือกแบบ A + โหมดมืดแบบ B, 2026-09-26)
 *
 * ต่อยอดจากไอคอนแอป TP UltraApp (น้ำเงินกรมท่า + ทองลายกนก)
 *   โหมดสว่าง : หัวหน้าจอน้ำเงินกรมท่า · เนื้อหาพื้นงาช้าง #F4F0E7 · การ์ดขาว · ทองแชมเปญเป็นจุดเน้น
 *   โหมดมืด   : มิดไนท์ #05070C · การ์ดกระจกทึบ · ทองเรือง
 *
 * หลักการ
 *   - หน้าจอทุกหน้าอ่านสีจาก useTheme().colors เท่านั้น ห้ามฮาร์ดโค้ดสี
 *   - ทองใช้ "น้อยแต่ชัด": ปุ่มหลัก ตัวเลขเงิน ไอคอนเน้น — พื้นที่ส่วนใหญ่เป็นกลาง
 *   - เงาเป็นเงานุ่มหลายชั้นโทนน้ำเงิน (boxShadow ของ New Architecture) — Android ต่ำกว่า API 28 ใช้ elevation
 *   - ฟอนต์: Anuphan เลือกน้ำหนักอัตโนมัติผ่าน components/ui/Text · หัวเรื่องรอยัลใช้ typography.serif*
 */

import { Platform, type ViewStyle } from 'react-native';
import { FONT } from './fonts';

// =====================================================
// ชนิดข้อมูล
// =====================================================

/** gradient ของ expo-linear-gradient ต้องมีอย่างน้อย 2 สี */
export type GradientTuple = readonly [string, string, ...string[]];

/** โทนสีที่ component ใช้ร่วมกัน (Chip, StatTile, Pill ฯลฯ) */
export type Tone = 'neutral' | 'gold' | 'success' | 'danger' | 'info' | 'warning';

export interface ThemeColors {
  /** พื้นหลังหน้าจอ (งาช้าง / มิดไนท์) */
  background: string;
  /** พื้นผิวรอง (แท็บบาร์ ชิปปกติ แถบเครื่องมือ) */
  surface: string;
  /** การ์ดที่ยกขึ้นจากพื้น */
  card: string;
  /** พื้นช่องกรอก/ช่องที่ยุบลง */
  inset: string;
  /** สีเงาตก (น้ำเงินในโหมดสว่าง · ดำในโหมดมืด) */
  shadowDark: string;
  /** ไฮไลต์ขอบบนของการ์ด */
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
  /** ตัวอักษรบนพื้นสีเข้ม (ปุ่มเขียว/แดง/น้ำเงิน) — ขาว */
  textOnAccent: string;
  /** ตัวอักษรบนพื้นทอง (ปุ่มหลัก ชิปทอง) — น้ำตาลเข้ม ได้ contrast ≥ 7:1 */
  textOnGold: string;

  /** ทองหลัก (ไอคอนเน้น เส้นทอง) */
  gold: string;
  /** ทองสำหรับตัวอักษร/ลิงก์ (อ่านออกบนพื้นของโหมดนั้น ≥ 4.5:1) */
  goldDeep: string;
  /** ทองอ่อนสว่าง (ตัวเลขเงินบนพื้นน้ำเงิน ไอคอนบนหัวน้ำเงิน) */
  goldLight: string;
  /** อำพัน (จุดเน้นรอง) */
  amber: string;
  amberDeep: string;
  /** ทองอ่อน (พื้นป้าย) */
  goldSoft: string;
  amberSoft: string;

  /**
   * น้ำเงินกรมท่าสำหรับ "ไอคอน/ตัวอักษร/เส้น" เน้น (โหมดมืดเป็นน้ำเงินอ่อนให้อ่านออก)
   * พื้นสีน้ำเงิน (ปุ่ม เม็ดแท็บ แถบตะกร้า) ให้ใช้ gradients.navy แทน
   */
  navy: string;
  /**
   * พื้นน้ำเงินกรมท่าแบบสีเดียว ใต้ตัวอักษรขาว/ทอง — เข้มทั้งสองโหมด
   * (ฟองแชทของฉัน toast ป้ายตัวเลขขั้นตอน) ห้ามใช้ navy เป็นพื้นเพราะโหมดมืด navy เป็นฟ้าอ่อน
   */
  navyFill: string;
  /** น้ำเงินเข้มสุด (ขอบล่างหัวหน้าจอ) */
  navyDeep: string;
  /** พื้นอ่อนโทนน้ำเงิน (ไอคอนในวงกลม) */
  navySoft: string;
  /** ตัวอักษรบนหัวหน้าจอน้ำเงิน */
  onHeader: string;
  /** ตัวอักษรรองบนหัวหน้าจอน้ำเงิน */
  onHeaderMuted: string;
  /** พื้นปุ่มกระจกบนหัวหน้าจอ */
  headerGlass: string;
  /** ขอบปุ่มกระจกบนหัวหน้าจอ */
  headerGlassBorder: string;

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
  /** ปุ่มหลัก (ทองแชมเปญ) */
  primary: GradientTuple;
  /** ปุ่มรอง (ขาว/กระจก) */
  secondary: GradientTuple;
  /** ปุ่มน้ำเงินกรมท่า (ตัวอักษรทอง) */
  navy: GradientTuple;
  success: GradientTuple;
  danger: GradientTuple;
  /** ทองฟอยล์ (ตัวเลขเงิน ขอบบัตร) */
  gold: GradientTuple;
  goldBorder: GradientTuple;
  surface: GradientTuple;
  /** ม่านไล่เฉดจากใสลงมามืด สำหรับ banner */
  bannerScrim: GradientTuple;
  /** หัวหน้าจอ (น้ำเงินกรมท่า / มิดไนท์) */
  hero: GradientTuple;
  /** การ์ดกระจกบนหัวหน้าจอ */
  glass: GradientTuple;
}

// =====================================================
// พาเลตต์ดิบ
// =====================================================

export const palette = {
  navy950: '#060D1B',
  navy900: '#081224',
  navy800: '#0C1A33',
  navy700: '#10223F',
  navy600: '#173059',
  navy500: '#22427A',
  navy400: '#3A5C96',
  navy100: '#E3E9F3',
  navy50: '#EEF1F6',

  gold50: '#FDF9EE',
  gold100: '#FBF3DD',
  gold200: '#F6E6B8',
  gold300: '#F3DC9B',
  gold400: '#E4C06B',
  gold500: '#CFA349',
  gold600: '#A87E2C',
  /** ทองเข้มสำหรับตัวอักษรบนพื้นสว่าง (≥ 4.5:1 บนขาว/งาช้าง) */
  gold700: '#8A6420',
  gold750: '#7F5C18',
  gold800: '#6E4F15',
  gold900: '#46320C',

  amber100: '#F7E4CC',
  amber300: '#E8AE6A',
  amber400: '#D98E3F',
  amber600: '#B06F2F',
  amber800: '#7A4A1C',

  ivory: '#F4F0E7',
  ivoryDeep: '#ECE6D9',
  paper: '#FFFFFF',
  paperWarm: '#FBF8F2',

  midnight950: '#05070C',
  midnight900: '#0A0D14',
  midnight800: '#0F131C',
  midnight700: '#141925',
  midnight600: '#1B2130',

  ink900: '#0E1626',
  ink800: '#1A2233',
  ink600: '#5E6778',
  ink400: '#8A93A3',
  ink300: '#B9BFCA',

  green400: '#3DDC84',
  green500: '#23996A',
  green600: '#1F8A5B',
  green700: '#177A50',
  red400: '#FF7A6B',
  red500: '#D6493A',
  red700: '#A8322A',
  blue400: '#6FA3FF',
  blue500: '#2F5FA8',
  orange400: '#F5B454',
  orange500: '#C77A12',

  // ชื่อเดิมจากธีมดินเหนียว (ยังมีไฟล์อ้างถึง) — ชี้ไปสีชุดใหม่
  clayLight: '#F4F0E7',
  clayLightSurface: '#FBF8F2',
  clayLightCard: '#FFFFFF',
  clayLightShadow: '#10223F',
  clayLightHighlight: '#FFFFFF',
  clayDark: '#0A0D14',
  clayDarkSurface: '#0F131C',
  clayDarkCard: '#141925',
  clayDarkShadow: '#000000',
  clayDarkHighlight: '#1B2130',
  inkLight: '#1A2233',
  inkLightStrong: '#0E1626',
  inkLightMuted: '#5E6778',
  inkLightFaint: '#8A93A3',
  inkLightTab: '#8A93A3',
  inkDark: '#E8E3D8',
  inkDarkStrong: '#F7F3EA',
  inkDarkMuted: '#9AA3B5',
  inkDarkFaint: '#6B7385',

  white: '#FFFFFF',
  black: '#000000',
} as const;

// =====================================================
// สีตามโหมด
// =====================================================

export const lightColors: ThemeColors = {
  background: palette.ivory,
  surface: palette.paperWarm,
  card: palette.paper,
  inset: '#F1ECE2',
  shadowDark: palette.navy700,
  shadowLight: palette.white,
  border: 'rgba(16, 24, 42, 0.09)',
  divider: 'rgba(16, 24, 42, 0.07)',

  text: palette.ink800,
  textStrong: palette.ink900,
  textMuted: palette.ink600,
  textFaint: palette.ink400,
  textOnAccent: palette.white,
  textOnGold: '#2A1D06',

  gold: palette.gold500,
  goldDeep: palette.gold700,
  goldLight: palette.gold300,
  amber: palette.amber400,
  amberDeep: palette.amber600,
  goldSoft: '#F6EBCF',
  amberSoft: palette.amber100,

  navy: palette.navy800,
  navyFill: palette.navy800,
  navyDeep: palette.navy900,
  navySoft: palette.navy50,
  onHeader: palette.white,
  onHeaderMuted: 'rgba(214, 222, 238, 0.74)',
  headerGlass: 'rgba(255, 255, 255, 0.08)',
  headerGlassBorder: 'rgba(255, 255, 255, 0.12)',

  success: palette.green600,
  successSoft: '#E4F4EA',
  danger: palette.red500,
  dangerSoft: '#FBE7E3',
  info: palette.blue500,
  infoSoft: '#E3ECF8',
  warning: palette.orange500,
  warningSoft: '#FBF0D6',

  overlay: 'rgba(6, 13, 27, 0.55)',
  tabInactive: palette.ink400,
};

export const darkColors: ThemeColors = {
  background: palette.midnight900,
  surface: palette.midnight800,
  card: palette.midnight700,
  inset: '#0C1018',
  shadowDark: palette.black,
  shadowLight: 'rgba(255, 255, 255, 0.10)',
  border: 'rgba(255, 255, 255, 0.08)',
  divider: 'rgba(255, 255, 255, 0.06)',

  text: '#E8E3D8',
  textStrong: '#F7F3EA',
  textMuted: '#9AA3B5',
  textFaint: '#6B7385',
  textOnAccent: palette.white,
  textOnGold: '#2A1D06',

  gold: '#F0C96A',
  goldDeep: '#F0C96A',
  goldLight: '#FFE7A3',
  amber: palette.amber300,
  amberDeep: palette.amber400,
  goldSoft: 'rgba(240, 201, 106, 0.14)',
  amberSoft: 'rgba(232, 174, 106, 0.14)',

  // โหมดมืด: navy ใช้เป็นสีไอคอน/ตัวอักษรเน้นบนพื้นเข้ม → ต้องสว่าง (≥ 7:1 บนการ์ด) · พื้นน้ำเงินใช้ gradients.navy
  navy: '#A9C1F2',
  navyFill: '#22427A',
  navyDeep: palette.midnight950,
  navySoft: 'rgba(111, 163, 255, 0.13)',
  onHeader: '#F7F3EA',
  onHeaderMuted: 'rgba(235, 230, 220, 0.66)',
  headerGlass: 'rgba(255, 255, 255, 0.06)',
  headerGlassBorder: 'rgba(255, 255, 255, 0.10)',

  success: palette.green400,
  successSoft: 'rgba(61, 220, 132, 0.14)',
  danger: palette.red400,
  dangerSoft: 'rgba(255, 122, 107, 0.14)',
  info: palette.blue400,
  infoSoft: 'rgba(111, 163, 255, 0.14)',
  warning: palette.orange400,
  warningSoft: 'rgba(245, 180, 84, 0.14)',

  overlay: 'rgba(0, 0, 0, 0.66)',
  tabInactive: 'rgba(235, 230, 220, 0.5)',
};

export const lightGradients: ThemeGradients = {
  primary: ['#F7E1A0', '#E4BE66', '#D4A447'],
  secondary: ['#FFFFFF', '#F7F4EE'],
  navy: ['#1B3766', '#0C1A33'],
  success: ['#23996A', '#177A50'],
  danger: ['#E0553F', '#B83A2B'],
  gold: ['#FFF3CC', '#F0D08A', '#C9973A'],
  goldBorder: ['#F3DC9B', '#CFA349', '#F3DC9B'],
  surface: ['#FFFFFF', '#FBF8F2'],
  bannerScrim: ['rgba(7, 14, 28, 0)', 'rgba(7, 14, 28, 0.35)', 'rgba(7, 14, 28, 0.86)'],
  hero: ['#081224', '#0C1A33', '#10223F'],
  glass: ['rgba(255, 255, 255, 0.11)', 'rgba(255, 255, 255, 0.03)'],
};

export const darkGradients: ThemeGradients = {
  primary: ['#F6DE9A', '#E4BC62', '#CF9E42'],
  secondary: ['#1E2433', '#151A26'],
  navy: ['#22427A', '#10223F'],
  success: ['#23996A', '#146843'],
  danger: ['#E0553F', '#A8322A'],
  gold: ['#FFF3CC', '#F0D08A', '#C9973A'],
  goldBorder: ['#F0C96A', '#8A6420', '#F0C96A'],
  surface: ['#161B27', '#10141D'],
  bannerScrim: ['rgba(0, 0, 0, 0)', 'rgba(0, 0, 0, 0.4)', 'rgba(0, 0, 0, 0.88)'],
  hero: ['#05070C', '#0A1020', '#0E1628'],
  glass: ['rgba(255, 255, 255, 0.08)', 'rgba(255, 255, 255, 0.025)'],
};

/** สีขอบล่างของปุ่ม (ความหนาบางๆ ให้ปุ่มดูกดได้) */
export const buttonEdgeColors = {
  light: {
    primary: '#B8862B',
    secondary: 'rgba(16, 24, 42, 0.12)',
    navy: '#060D1B',
    success: '#0F5A39',
    danger: '#7E2620',
    ghost: 'transparent',
  },
  dark: {
    primary: '#8A6420',
    secondary: 'rgba(0, 0, 0, 0.6)',
    navy: '#060D1B',
    success: '#0B3F27',
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
  xs: 7,
  sm: 11,
  md: 15,
  lg: 19,
  xl: 22,
  xxl: 28,
  pill: 999,
} as const;

/**
 * ขนาดตัวอักษร — ข้อความทั่วไปใช้ Anuphan (Text ของแอปเลือกน้ำหนักให้จาก fontWeight)
 * serif* = Noto Serif Thai สำหรับชื่อหน้า/ชื่อร้าน/คำทักทาย (บุคลิกรอยัล ตรงกับไอคอนแอป)
 * line-height ราว 1.4–1.5 เท่า เผื่อสระบน-ล่างและวรรณยุกต์ซ้อนของภาษาไทย
 */
export const typography = {
  display: { fontFamily: FONT.serif, fontSize: 28, lineHeight: 40, fontWeight: '700' as const },
  serifLg: { fontFamily: FONT.serif, fontSize: 26, lineHeight: 37, fontWeight: '700' as const },
  serif: { fontFamily: FONT.serif, fontSize: 21, lineHeight: 31, fontWeight: '700' as const },
  serifSm: { fontFamily: FONT.serifSemibold, fontSize: 17, lineHeight: 26, fontWeight: '600' as const },
  h1: { fontSize: 22, lineHeight: 31, fontWeight: '700' as const },
  h2: { fontSize: 18.5, lineHeight: 26, fontWeight: '700' as const },
  h3: { fontSize: 16, lineHeight: 23, fontWeight: '600' as const },
  body: { fontSize: 15, lineHeight: 22, fontWeight: '400' as const },
  bodyStrong: { fontSize: 15, lineHeight: 22, fontWeight: '600' as const },
  bodySm: { fontSize: 13, lineHeight: 19, fontWeight: '400' as const },
  caption: { fontSize: 12, lineHeight: 17, fontWeight: '500' as const },
  micro: { fontSize: 11, lineHeight: 15, fontWeight: '600' as const },
  /** ป้ายหัวข้อเล็ก (เช่น "คืนนี้ 18:00") */
  overline: { fontSize: 11.5, lineHeight: 16, fontWeight: '600' as const, letterSpacing: 0.4 },
  /** ตัวเลขเงิน */
  money: { fontSize: 24, lineHeight: 31, fontWeight: '700' as const, fontVariant: ['tabular-nums'] as ('tabular-nums')[] },
  moneyLg: { fontSize: 36, lineHeight: 44, fontWeight: '700' as const, letterSpacing: -0.6, fontVariant: ['tabular-nums'] as ('tabular-nums')[] },
} as const;

/** ขนาดขั้นต่ำของจุดกด (Material/Apple = 44-48) */
export const MIN_TOUCH = 44;

// =====================================================
// เงา
// =====================================================

export type ShadowLevel = 'none' | 'sm' | 'md' | 'lg';

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

const ELEVATION: Record<Exclude<ShadowLevel, 'none'>, number> = { sm: 2, md: 5, lg: 10 };

const legacyAndroid = (): boolean =>
  Platform.OS === 'android' && typeof Platform.Version === 'number' && Platform.Version < 28;

/**
 * เงาตกนุ่มสองชั้น (เงาไกลจาง + เงาใกล้คม) แบบการ์ดพรีเมียม
 *
 * @param level ระดับความลึก
 * @param color สีเงา (ปกติใช้ colors.shadowDark ของธีม หรือสีปุ่มเพื่อให้เงาเรืองสี)
 */
export const shadowStyle = (level: ShadowLevel, color: string = palette.navy700): ViewStyle => {
  if (level === 'none') {
    return {};
  }
  if (legacyAndroid()) {
    return { elevation: ELEVATION[level], shadowColor: color };
  }
  const css = {
    sm: `0px 6px 14px -8px ${withAlpha(color, 0.38)}, 0px 1px 2px ${withAlpha(color, 0.06)}`,
    md: `0px 14px 28px -18px ${withAlpha(color, 0.5)}, 0px 1px 3px ${withAlpha(color, 0.07)}`,
    lg: `0px 24px 44px -24px ${withAlpha(color, 0.62)}, 0px 2px 6px ${withAlpha(color, 0.08)}`,
  }[level];
  return { boxShadow: css };
};

/**
 * (ชื่อเดิมจากธีมดินเหนียว) — ตอนนี้คืนเงานุ่มแบบเดียวกับ shadowStyle
 * คงลายเซ็นเดิมไว้ให้หน้าที่ยังเรียกอยู่ไม่ต้องแก้
 */
export const clayShadowStyle = (
  level: Exclude<ShadowLevel, 'none'>,
  dark: string,
  _light?: string
): ViewStyle => shadowStyle(level, dark);

/** เรืองแสงสี (ปุ่มทอง/ป้ายสด) */
export const glowStyle = (color: string, strength: number = 0.8): ViewStyle => {
  if (legacyAndroid()) return {};
  return { boxShadow: `0px 12px 22px -12px ${withAlpha(color, strength)}` };
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
      return { fg: colors.goldDeep, bg: colors.goldSoft, border: withAlpha(palette.gold500, 0.35) };
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
