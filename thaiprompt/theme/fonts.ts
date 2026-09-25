/**
 * ฟอนต์ของแอป — Anuphan (ข้อความทั่วไป) + Noto Serif Thai (หัวเรื่องแบบรอยัล)
 *
 * - ไฟล์ฟอนต์อยู่ใน assets/fonts (SIL Open Font License — ดู OFL-*.txt)
 * - Android สังเคราะห์น้ำหนักฟอนต์เองไม่ได้ จึงลงทะเบียนแยกชื่อต่อหนึ่งน้ำหนัก
 *   แล้วให้ components/ui/Text.tsx เลือกชื่อจาก fontWeight ให้อัตโนมัติ
 * - โหลดใน app/_layout.tsx ด้วย useFonts(FONT_ASSETS) ก่อนแสดงหน้าแรก
 */

export const FONT = {
  light: 'Anuphan-Light',
  regular: 'Anuphan-Regular',
  medium: 'Anuphan-Medium',
  semibold: 'Anuphan-SemiBold',
  bold: 'Anuphan-Bold',
  /** หัวเรื่องแบบมีเชิง (ชื่อหน้า ชื่อร้าน ชื่อผู้ใช้) */
  serifSemibold: 'NotoSerifThai-SemiBold',
  serif: 'NotoSerifThai-Bold',
} as const;

export const FONT_ASSETS = {
  [FONT.light]: require('@/assets/fonts/Anuphan_300Light.ttf'),
  [FONT.regular]: require('@/assets/fonts/Anuphan_400Regular.ttf'),
  [FONT.medium]: require('@/assets/fonts/Anuphan_500Medium.ttf'),
  [FONT.semibold]: require('@/assets/fonts/Anuphan_600SemiBold.ttf'),
  [FONT.bold]: require('@/assets/fonts/Anuphan_700Bold.ttf'),
  [FONT.serifSemibold]: require('@/assets/fonts/NotoSerifThai_600SemiBold.ttf'),
  [FONT.serif]: require('@/assets/fonts/NotoSerifThai_700Bold.ttf'),
};

/** fontWeight → ชื่อฟอนต์ Anuphan ที่ลงทะเบียนไว้ */
export const ANUPHAN_BY_WEIGHT: Record<string, string> = {
  '100': FONT.light,
  '200': FONT.light,
  '300': FONT.light,
  '400': FONT.regular,
  normal: FONT.regular,
  '500': FONT.medium,
  '600': FONT.semibold,
  '700': FONT.bold,
  bold: FONT.bold,
  '800': FONT.bold,
  '900': FONT.bold,
};

let fontsEnabled = true;

/** ปิดการใส่ฟอนต์อัตโนมัติ (ใช้เมื่อโหลดฟอนต์ไม่สำเร็จ → กลับไปใช้ฟอนต์ระบบ ไม่ให้ข้อความหาย) */
export const setFontsEnabled = (enabled: boolean): void => {
  fontsEnabled = enabled;
};

export const areFontsEnabled = (): boolean => fontsEnabled;
