/**
 * PriceText + formatBaht — แสดงราคาเงินบาทแบบไทย (฿1,234.50)
 *
 * - รับได้ทั้ง number และ string ตัวเลข (API บางเส้นยังส่ง "12.50") — แปลงด้วย Number() เสมอ
 * - decimals 'auto' (ค่าเริ่มต้น) = มีเศษสตางค์ค่อยแสดง 2 ตำแหน่ง, ไม่มีเศษแสดงเลขกลม
 * - ยอดที่ต้องตรงทุกหลัก (เช่น ยอดโอน PromptPay ที่มีเศษสตางค์เฉพาะตัว) ให้ส่ง decimals={2}
 *
 * @example
 * <PriceText amount={order.total_amount} size="lg" tone="gold" />
 * <PriceText amount={product.original_price} strike size="sm" tone="muted" />
 * formatBaht(1990) // "฿1,990"
 */

import React from 'react';
import { StyleSheet, Text, type StyleProp, type TextStyle } from 'react-native';
import { useTheme } from '@/theme';

export type PriceDecimals = 'auto' | 0 | 2;

export interface FormatBahtOptions {
  decimals?: PriceDecimals;
  /** แสดงเครื่องหมาย + นำหน้ายอดบวก (เช่น รายการเงินเข้า) */
  signed?: boolean;
  /** ข้อความเมื่อไม่มีค่า (null/undefined/ไม่ใช่ตัวเลข) */
  empty?: string;
}

/** แปลงค่าจาก API เป็น number (คืน null ถ้าแปลงไม่ได้) */
export const toAmount = (value: unknown): number | null => {
  if (value === null || value === undefined || value === '') return null;
  const n = typeof value === 'number' ? value : Number(String(value).replace(/,/g, ''));
  return Number.isFinite(n) ? n : null;
};

/**
 * จัดรูปแบบเงินบาท
 *
 * @param amount จำนวนเงิน (number | string)
 * @returns เช่น "฿1,234.50", "-฿20", "+฿100"
 */
export const formatBaht = (amount: unknown, options: FormatBahtOptions = {}): string => {
  const { decimals = 'auto', signed = false, empty = '—' } = options;
  const n = toAmount(amount);
  if (n === null) return empty;

  // ปัดเศษ 2 ตำแหน่งก่อน กันเลขทศนิยมลอย (0.1 + 0.2)
  const rounded = Math.round(n * 100) / 100;
  const abs = Math.abs(rounded);
  const hasFraction = Math.round(abs * 100) % 100 !== 0;
  const digits = decimals === 'auto' ? (hasFraction ? 2 : 0) : decimals;

  let body: string;
  try {
    body = abs.toLocaleString('th-TH', {
      minimumFractionDigits: digits,
      maximumFractionDigits: digits,
    });
  } catch {
    // บางเครื่องไม่มี Intl → จัดรูปแบบเอง
    const fixed = abs.toFixed(digits);
    const [intPart, fracPart] = fixed.split('.');
    body = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (fracPart ? `.${fracPart}` : '');
  }

  const sign = rounded < 0 ? '-' : signed && rounded > 0 ? '+' : '';
  return `${sign}฿${body}`;
};

type PriceSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl';
type PriceTone = 'default' | 'strong' | 'gold' | 'success' | 'danger' | 'muted' | 'onAccent';

const FONT: Record<PriceSize, { fontSize: number; lineHeight: number }> = {
  xs: { fontSize: 12, lineHeight: 16 },
  sm: { fontSize: 14, lineHeight: 19 },
  md: { fontSize: 16, lineHeight: 22 },
  lg: { fontSize: 22, lineHeight: 28 },
  xl: { fontSize: 30, lineHeight: 38 },
};

export interface PriceTextProps {
  amount: unknown;
  size?: PriceSize;
  tone?: PriceTone;
  decimals?: PriceDecimals;
  signed?: boolean;
  /** ขีดฆ่า (ราคาก่อนลด) */
  strike?: boolean;
  /** ตัวหนา (ค่าเริ่มต้น true) */
  bold?: boolean;
  /** ข้อความท้ายราคา เช่น "/ชิ้น" */
  suffix?: string;
  empty?: string;
  style?: StyleProp<TextStyle>;
  numberOfLines?: number;
}

export const PriceText: React.FC<PriceTextProps> = ({
  amount,
  size = 'md',
  tone = 'default',
  decimals = 'auto',
  signed = false,
  strike = false,
  bold = true,
  suffix,
  empty,
  style,
  numberOfLines = 1,
}) => {
  const { colors } = useTheme();

  const color: Record<PriceTone, string> = {
    default: colors.text,
    strong: colors.textStrong,
    gold: colors.goldDeep,
    success: colors.success,
    danger: colors.danger,
    muted: colors.textMuted,
    onAccent: colors.textOnAccent,
  };

  const text = formatBaht(amount, { decimals, signed, empty });

  return (
    <Text
      numberOfLines={numberOfLines}
      accessibilityLabel={`${text.replace('฿', '')} บาท${suffix ? ` ${suffix}` : ''}`}
      style={[
        FONT[size],
        styles.base,
        { color: color[tone], fontWeight: bold ? '800' : '500' },
        strike && styles.strike,
        style,
      ]}
    >
      {text}
      {suffix ? <Text style={styles.suffix}>{` ${suffix}`}</Text> : null}
    </Text>
  );
};

const styles = StyleSheet.create({
  base: {
    fontVariant: ['tabular-nums'],
  },
  strike: {
    textDecorationLine: 'line-through',
  },
  suffix: {
    fontSize: 12,
    fontWeight: '500',
  },
});

export default PriceText;
