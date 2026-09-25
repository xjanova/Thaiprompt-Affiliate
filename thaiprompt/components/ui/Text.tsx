/**
 * Text / TextInput ของแอป — ใส่ฟอนต์ Anuphan ให้อัตโนมัติตาม fontWeight
 *
 * ทุกไฟล์ในแอป import Text/TextInput จากที่นี่แทน 'react-native'
 * - ไม่ระบุ fontFamily → เลือก Anuphan ตามน้ำหนัก (400/500/600/700) และตั้ง fontWeight เป็น normal
 *   (กัน Android ทำตัวหนาปลอมซ้อนบนไฟล์ที่หนาอยู่แล้ว)
 * - ระบุ fontFamily อื่น เช่น NotoSerifThai-Bold → ใช้ตามนั้น ไม่แตะ
 * - Text ที่ซ้อนอยู่ใน Text (เช่น ชื่อสีทองในคำทักทาย) → สืบทอดฟอนต์ของข้อความแม่
 *   แตะเฉพาะเมื่อข้อความลูกระบุ fontWeight/fontFamily เอง
 * - จำกัดการขยายตัวอักษรตามระบบไว้ที่ 1.35 เท่า ให้เลย์เอาต์ไม่แตก (ยังขยายได้ตามการตั้งค่าผู้ใช้)
 */

import React, { createContext, forwardRef, useContext } from 'react';
import {
  StyleSheet,
  Text as RNText,
  TextInput as RNTextInput,
  type StyleProp,
  type TextInputProps,
  type TextProps,
  type TextStyle,
} from 'react-native';
import { ANUPHAN_BY_WEIGHT, FONT, areFontsEnabled } from '@/theme/fonts';

const MAX_FONT_SCALE = 1.35;

/** อยู่ภายใน Text อีกตัวหรือไม่ (ข้อความซ้อนสืบทอดฟอนต์จากแม่) */
const InsideTextContext = createContext(false);

/** คืน style ฟอนต์ที่ต้องทับ (null = ไม่ต้องแตะ) */
export const resolveFontStyle = (style: StyleProp<TextStyle>, nested: boolean = false): TextStyle | null => {
  if (!areFontsEnabled()) return null;
  const flat = (StyleSheet.flatten(style) || {}) as TextStyle;
  const family = flat.fontFamily;

  // ข้อความซ้อนที่ไม่ได้กำหนดฟอนต์/น้ำหนักเอง → ปล่อยให้สืบทอดจากข้อความแม่
  if (nested && !family && flat.fontWeight == null) return null;

  if (family && !family.startsWith('Anuphan')) return null;
  if (family && flat.fontWeight == null) return { fontWeight: 'normal' };

  const weight = flat.fontWeight != null ? String(flat.fontWeight) : '400';
  return { fontFamily: ANUPHAN_BY_WEIGHT[weight] ?? FONT.regular, fontWeight: 'normal' };
};

export const Text = forwardRef<RNText, TextProps>(function AppText(props, ref) {
  const nested = useContext(InsideTextContext);
  const fontStyle = resolveFontStyle(props.style, nested);
  const node = (
    <RNText
      maxFontSizeMultiplier={MAX_FONT_SCALE}
      {...props}
      ref={ref}
      style={fontStyle ? [props.style, fontStyle] : props.style}
    />
  );
  return nested ? node : <InsideTextContext.Provider value>{node}</InsideTextContext.Provider>;
});

export const TextInput = forwardRef<RNTextInput, TextInputProps>(function AppTextInput(props, ref) {
  const fontStyle = resolveFontStyle(props.style);
  return (
    <RNTextInput
      maxFontSizeMultiplier={MAX_FONT_SCALE}
      {...props}
      ref={ref}
      style={fontStyle ? [props.style, fontStyle] : props.style}
    />
  );
});

/** ชนิดของ ref (useRef<TextInput>(null)) — ชื่อเดียวกับ component เหมือน react-native */
export type Text = RNText;
export type TextInput = RNTextInput;

export type { TextProps, TextInputProps };
