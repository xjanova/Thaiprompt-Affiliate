/**
 * TpixPrice / TpixTotal / TpixCoin — ราคาเหรียญ TPIX ข้างราคาบาท (ธีมรอยัล น้ำเงินกรมท่า-ทอง)
 *
 * - เหรียญทองเล็ก + "≈ 307.69 TPIX" (ราคาคงที่ของ Official Shop ใช้ "=")
 * - variant 'compact' (การ์ด/รายการ) · 'detailed' (หน้าสินค้า: เพิ่มบรรทัด "1 TPIX = ฿3.25 · ราคาจริงจาก TPIX DEX · อัปเดต 14:05")
 * - ไม่มีข้อมูล (server เก่า / ไม่รู้ราคา / แอดมินปิดการแสดง) → ไม่แสดงอะไรเลย (คืน null)
 *
 * ราคา TPIX ของสินค้าเป็น "ราคาต่อชิ้น" — ถ้าแสดงข้างยอดรวมหลายชิ้น/รวมตัวเลือก ส่ง thb (ยอดบาทที่แสดง) มาด้วย
 * แล้วจะแปลงด้วยราคาเดียวกับป้าย (ราคาคงที่ใช้ ราคา × quantity)
 *
 * @example
 * <PriceText amount={product.price} />
 * <TpixPrice tpix={product.tpix} />
 * <TpixPrice tpix={product.tpix} variant="detailed" />
 * <TpixPrice tpix={item.tpix} thb={item.unit_price * qty} quantity={qty} spotFallback />
 * <TpixTotal thb={summary.grand_total} lines={items.map(...)} />
 */

import React from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import {
  estimateTpixTotal,
  formatTpix,
  formatTpixRate,
  formatTpixTime,
  normalizeTpixTag,
  tagRateThb,
  type TpixEstimateLine,
  type TpixPriceTag,
} from '@/services/api/tpixApi';
import { useTpixPriceStore } from '@/stores/tpixPriceStore';
import { useTheme, radii, spacing, typography, withAlpha } from '@/theme';

type TpixSize = 'xs' | 'sm' | 'md';
type TpixAlign = 'left' | 'center' | 'right';

const FONT: Record<TpixSize, { fontSize: number; lineHeight: number; coin: number }> = {
  xs: { fontSize: 11, lineHeight: 15, coin: 12 },
  sm: { fontSize: 12, lineHeight: 17, coin: 14 },
  md: { fontSize: 14, lineHeight: 20, coin: 16 },
};

const JUSTIFY: Record<TpixAlign, ViewStyle['justifyContent']> = {
  left: 'flex-start',
  center: 'center',
  right: 'flex-end',
};

// =====================================================
// เหรียญทอง
// =====================================================

export interface TpixCoinProps {
  size?: number;
  style?: StyleProp<ViewStyle>;
}

/** เหรียญทองตัว T (สัญลักษณ์ TPIX) — ไล่เฉดทองฟอยล์ ขอบทองเข้ม */
export const TpixCoin: React.FC<TpixCoinProps> = ({ size = 14, style }) => {
  const { colors, gradients } = useTheme();
  return (
    <LinearGradient
      colors={gradients.gold}
      start={{ x: 0.15, y: 0 }}
      end={{ x: 0.85, y: 1 }}
      style={[
        styles.coin,
        { width: size, height: size, borderRadius: size / 2, borderColor: withAlpha(colors.goldDeep, 0.55) },
        style,
      ]}
      accessible={false}
      importantForAccessibility="no-hide-descendants"
    >
      <Text
        allowFontScaling={false}
        style={[styles.coinText, { fontSize: Math.max(7, Math.round(size * 0.6)), lineHeight: size - 1, color: colors.textOnGold }]}
      >
        T
      </Text>
    </LinearGradient>
  );
};

// =====================================================
// แถวแสดงผล (ใช้ร่วมกันทั้งราคาสินค้าและยอดรวม)
// =====================================================

interface TpixLineProps {
  text: string;
  approx: boolean;
  variant: 'compact' | 'detailed';
  size: TpixSize;
  align: TpixAlign;
  /** บรรทัดรอง (เฉพาะ detailed) */
  note?: string | null;
  style?: StyleProp<ViewStyle>;
}

const TpixLine: React.FC<TpixLineProps> = ({ text, approx, variant, size, align, note, style }) => {
  const { colors } = useTheme();
  const f = FONT[size];
  const sign = approx ? '≈' : '=';
  const label = `${approx ? 'ประมาณ ' : ''}${text.replace(/,/g, '')} เหรียญ TPIX`;

  if (variant === 'detailed') {
    return (
      <View
        style={[styles.detailBox, { backgroundColor: colors.goldSoft, borderColor: withAlpha(colors.gold, 0.35) }, style]}
        accessible
        accessibilityLabel={note ? `${label} ${note}` : label}
      >
        <TpixCoin size={24} />
        <View style={styles.flex}>
          <Text numberOfLines={1} style={[typography.h3, styles.amount, { color: colors.goldDeep }]}>
            {sign} {text} <Text style={[styles.unit, { color: colors.goldDeep }]}>TPIX</Text>
          </Text>
          {!!note && <Text style={[typography.caption, { color: colors.textMuted }]}>{note}</Text>}
        </View>
      </View>
    );
  }

  return (
    <View style={[styles.row, { justifyContent: JUSTIFY[align] }, style]} accessible accessibilityLabel={label}>
      <TpixCoin size={f.coin} />
      <Text
        numberOfLines={1}
        style={[styles.amount, { fontSize: f.fontSize, lineHeight: f.lineHeight, color: colors.goldDeep }]}
      >
        {sign} {text} TPIX
      </Text>
    </View>
  );
};

/** บรรทัดรองของแบบ detailed */
const noteFor = (tag: TpixPriceTag, rateThb: number | null): string => {
  const rate = tag.rate_display || (rateThb ? formatTpixRate(rateThb) : null);
  const time = formatTpixTime(tag.updated_at);
  if (tag.fixed) {
    return ['ราคาเหรียญคงที่ของร้าน Official', rate ? `ราคาตลาด 1 TPIX = ${rate}` : null].filter(Boolean).join(' · ');
  }
  return [rate ? `1 TPIX = ${rate}` : null, 'ราคาจริงจาก TPIX DEX', time ? `อัปเดต ${time}` : null]
    .filter(Boolean)
    .join(' · ');
};

// =====================================================
// TpixPrice — ราคาเหรียญของสินค้า
// =====================================================

export interface TpixPriceProps {
  /** ก้อน tpix จาก API (null = ซ่อน · undefined = server ไม่ส่ง) */
  tpix?: unknown;
  /** ยอดบาทที่แสดงอยู่ข้างๆ (หลายชิ้น/รวมตัวเลือก) — ไม่ส่ง = ราคาต่อชิ้นตามป้าย */
  thb?: number | null;
  /** จำนวนชิ้น (ใช้กับราคาคงที่ หรือเมื่อไม่ส่ง thb) */
  quantity?: number;
  /** server ไม่ส่งป้ายมา (undefined) → ใช้ราคาสดจาก GET /tpix/price แปลงจาก thb แทน */
  spotFallback?: boolean;
  variant?: 'compact' | 'detailed';
  size?: TpixSize;
  align?: TpixAlign;
  style?: StyleProp<ViewStyle>;
}

export const TpixPrice: React.FC<TpixPriceProps> = ({
  tpix,
  thb,
  quantity = 1,
  spotFallback = false,
  variant = 'compact',
  size = 'sm',
  align = 'left',
  style,
}) => {
  // อ่านราคาสดเฉพาะตัวที่ขอ fallback (การ์ดสินค้าทั่วไปไม่ re-render ตามราคา)
  const spot = useTpixPriceStore((s) => (spotFallback ? s.spot : null));
  const showPrices = useTpixPriceStore((s) => s.showPrices);

  const tag = normalizeTpixTag(tpix);
  const qty = Math.max(1, Math.floor(Number(quantity) || 1));
  const thbAmount = typeof thb === 'number' && Number.isFinite(thb) && thb > 0 ? thb : null;

  if (tag) {
    const unit = Number(tag.raw);
    const rate = tagRateThb(tag);
    let text: string;
    let approx = true;
    if (tag.fixed) {
      approx = false;
      text = qty === 1 ? tag.amount : formatTpix(unit * qty);
    } else if (thbAmount !== null && rate) {
      const converted = thbAmount / rate;
      // ยอดเดียวกับป้าย (1 ชิ้น ไม่มีตัวเลือกเพิ่ม) → ใช้ตัวเลขของ server ให้ตรงกับที่แสดงที่อื่น
      text = qty === 1 && unit > 0 && Math.abs(converted - unit) / unit < 0.005 ? tag.amount : formatTpix(converted);
    } else {
      text = qty === 1 ? tag.amount : formatTpix(unit * qty);
    }
    return (
      <TpixLine
        text={text}
        approx={approx}
        variant={variant}
        size={size}
        align={align}
        note={variant === 'detailed' ? noteFor(tag, rate) : null}
        style={style}
      />
    );
  }

  // server ไม่ส่งป้าย (ไม่ใช่ส่ง null มาเพื่อซ่อน) → ใช้ราคาสด
  if (tpix === undefined && spotFallback && spot && showPrices && thbAmount !== null) {
    const time = formatTpixTime(spot.updated_at);
    return (
      <TpixLine
        text={formatTpix(thbAmount / spot.price_thb)}
        approx
        variant={variant}
        size={size}
        align={align}
        note={
          variant === 'detailed'
            ? [`1 TPIX = ${spot.price_thb_display}`, 'ราคาจริงจาก TPIX DEX', time ? `อัปเดต ${time}` : null].filter(Boolean).join(' · ')
            : null
        }
        style={style}
      />
    );
  }

  return null;
};

// =====================================================
// TpixTotal — ยอดรวมโดยประมาณ (ตะกร้า / หน้าชำระเงิน)
// =====================================================

export interface TpixTotalProps {
  /** ยอดบาททั้งหมด (รวมค่าส่ง/ส่วนลดแล้ว) */
  thb: number;
  /** รายการสินค้า (ใช้หักรายการราคาเหรียญคงที่ออกก่อนแปลง) */
  lines?: TpixEstimateLine[];
  /** แสดงบรรทัด "1 TPIX = ฿…" ต่อท้าย (compact) */
  showRate?: boolean;
  variant?: 'compact' | 'detailed';
  size?: TpixSize;
  align?: TpixAlign;
  style?: StyleProp<ViewStyle>;
}

export const TpixTotal: React.FC<TpixTotalProps> = ({
  thb,
  lines = [],
  showRate = false,
  variant = 'compact',
  size = 'sm',
  align = 'left',
  style,
}) => {
  const { colors } = useTheme();
  const spot = useTpixPriceStore((s) => s.spot);
  const showPrices = useTpixPriceStore((s) => s.showPrices);

  if (!showPrices) return null;
  // ราคาสดก่อน · ไม่มี → ราคาจากป้ายของรายการ (ทุกป้ายมาจากราคาเดียวกันของ server)
  let rate: number | null = spot?.price_thb ?? null;
  let rateText: string | null = spot?.price_thb_display ?? null;
  let updatedAt: string | null = spot?.updated_at ?? null;
  if (!rate) {
    for (const line of lines) {
      const tag = normalizeTpixTag(line.tpix);
      const r = tagRateThb(tag);
      if (tag && r) {
        rate = r;
        rateText = tag.rate_display;
        updatedAt = tag.updated_at;
        break;
      }
    }
  }
  if (!rate) return null;

  const total = estimateTpixTotal(thb, rate, lines);
  if (total === null) return null;
  const rateLabel = rateText || formatTpixRate(rate);
  const time = formatTpixTime(updatedAt);

  if (variant === 'detailed') {
    return (
      <TpixLine
        text={formatTpix(total)}
        approx
        variant="detailed"
        size={size}
        align={align}
        note={[`1 TPIX = ${rateLabel}`, 'ราคาจริงจาก TPIX DEX', time ? `อัปเดต ${time}` : null].filter(Boolean).join(' · ')}
        style={style}
      />
    );
  }

  return (
    <View style={style}>
      <TpixLine text={formatTpix(total)} approx variant="compact" size={size} align={align} />
      {showRate && (
        <Text
          numberOfLines={1}
          style={[typography.micro, styles.rateLine, { color: colors.textMuted, textAlign: align }]}
        >
          1 TPIX = {rateLabel}
          {time ? ` · อัปเดต ${time}` : ''}
        </Text>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  coin: {
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    overflow: 'hidden',
  },
  coinText: {
    fontWeight: '700',
    textAlign: 'center',
    includeFontPadding: false,
  },
  amount: {
    fontWeight: '700',
    fontVariant: ['tabular-nums'],
    flexShrink: 1,
  },
  unit: {
    fontSize: 12,
    fontWeight: '600',
  },
  detailBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderRadius: radii.md,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm + 2,
  },
  rateLine: {
    marginTop: 1,
    fontVariant: ['tabular-nums'],
  },
});

export default TpixPrice;
