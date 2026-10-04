/**
 * ชิ้นส่วนหน้า "ค่าตอบแทนไรเดอร์" ของร้าน (ไรเดอร์รอบ 2 ตามม็อกอัป SellerPricing)
 *
 * - GoldSlider     แถบเลื่อนทอง (ลาก/แตะตำแหน่ง/ปุ่มปรับของโปรแกรมอ่านหน้าจอ) — แอปไม่มีไลบรารี slider จึงทำเอง
 * - AcceptGauge    มาตร "โอกาสมีคนรับใน 5 นาที" (เขียว ≥ 75% · ส้ม ≥ 40% · แดงต่ำกว่า)
 * - RiderPayBands  ตารางค่าส่งตามระยะ (ตัวเลขจาก server ทั้งหมด · basis estimate = ป้าย "ประมาณการ")
 *
 * สีจาก useTheme() เท่านั้น · ไม่มีอีโมจิ · ข้อความไทย
 */

import React, { useMemo, useRef, useState } from 'react';
import { PanResponder, StyleSheet, View, type LayoutChangeEvent, type StyleProp, type ViewStyle } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import { selectionHaptic } from '@/components/ui';
import { useTheme, spacing, typography, shadowStyle, withAlpha } from '@/theme';
import type { RiderPayBand } from '@/services/api/sellerStoreApi';

// =====================================================
// GoldSlider
// =====================================================

const THUMB = 26;

export interface GoldSliderProps {
  value: number;
  min?: number;
  max: number;
  step?: number;
  onChange: (value: number) => void;
  /** เลขกำกับใต้แถบ (ค่าเริ่มต้น ทุก 5) */
  ticks?: number[];
  disabled?: boolean;
  accessibilityLabel: string;
  /** หน่วยสำหรับโปรแกรมอ่านหน้าจอ เช่น "บาท" */
  unit?: string;
  style?: StyleProp<ViewStyle>;
}

export const GoldSlider: React.FC<GoldSliderProps> = ({
  value,
  min = 0,
  max,
  step = 1,
  onChange,
  ticks,
  disabled = false,
  accessibilityLabel,
  unit = 'บาท',
  style,
}) => {
  const { colors, gradients, isDark } = useTheme();
  const [width, setWidth] = useState(0);
  const widthRef = useRef(0);
  const grantXRef = useRef(0);
  const valueRef = useRef(value);
  valueRef.current = value;
  const propsRef = useRef({ min, max, step, onChange, disabled });
  propsRef.current = { min, max, step, onChange, disabled };

  const clampToStep = (raw: number): number => {
    const p = propsRef.current;
    const stepped = Math.round((raw - p.min) / p.step) * p.step + p.min;
    return Math.max(p.min, Math.min(p.max, stepped));
  };

  /** ตั้งค่าจากตำแหน่งนิ้ว (พิกเซลในแถบ) */
  const setFromX = (x: number) => {
    const w = widthRef.current;
    if (w <= 0) return;
    const p = propsRef.current;
    const ratio = Math.max(0, Math.min(1, x / w));
    const next = clampToStep(p.min + ratio * (p.max - p.min));
    if (next !== valueRef.current) {
      valueRef.current = next;
      if (next % 5 === 0) selectionHaptic();
      p.onChange(next);
    }
  };

  const responder = useMemo(
    () =>
      PanResponder.create({
        onStartShouldSetPanResponder: () => !propsRef.current.disabled,
        onMoveShouldSetPanResponder: () => !propsRef.current.disabled,
        // ไม่ยอมให้ ScrollView แย่งระหว่างลาก
        onPanResponderTerminationRequest: () => false,
        onPanResponderGrant: (event) => {
          grantXRef.current = event.nativeEvent.locationX;
          setFromX(grantXRef.current);
        },
        onPanResponderMove: (_event, gesture) => {
          setFromX(grantXRef.current + gesture.dx);
        },
      }),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    []
  );

  const onLayout = (event: LayoutChangeEvent) => {
    const w = event.nativeEvent.layout.width;
    widthRef.current = w;
    if (Math.round(w) !== Math.round(width)) setWidth(w);
  };

  const span = Math.max(1, max - min);
  const ratio = Math.max(0, Math.min(1, (value - min) / span));
  const tickValues = ticks ?? Array.from({ length: Math.floor(span / 5) + 1 }, (_, i) => min + i * 5).filter((t) => t <= max);

  const adjust = (delta: number) => {
    if (disabled) return;
    const next = clampToStep(value + delta);
    if (next !== value) onChange(next);
  };

  return (
    <View style={style}>
      <View
        {...responder.panHandlers}
        onLayout={onLayout}
        accessible
        accessibilityRole="adjustable"
        accessibilityLabel={accessibilityLabel}
        accessibilityValue={{ min, max, now: value, text: `${value} ${unit}` }}
        accessibilityState={{ disabled }}
        accessibilityActions={[{ name: 'increment' }, { name: 'decrement' }]}
        onAccessibilityAction={(event) => {
          if (event.nativeEvent.actionName === 'increment') adjust(step);
          if (event.nativeEvent.actionName === 'decrement') adjust(-step);
        }}
        style={[styles.sliderHit, disabled && styles.disabled]}
      >
        <View pointerEvents="none" style={[styles.track, { backgroundColor: isDark ? colors.inset : withAlpha(colors.navyFill, 0.85) }]}>
          <LinearGradient
            colors={gradients.primary}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={[styles.fill, { width: `${ratio * 100}%` }]}
          />
        </View>
        {width > 0 && (
          <View
            pointerEvents="none"
            style={[
              styles.thumb,
              { left: ratio * width - THUMB / 2, borderColor: colors.card },
              shadowStyle('sm', colors.shadowDark),
            ]}
          >
            <LinearGradient colors={gradients.primary} style={StyleSheet.absoluteFill} />
          </View>
        )}
      </View>
      {tickValues.length > 1 && (
        <View style={styles.ticks} pointerEvents="none">
          {tickValues.map((t) => (
            <Text
              key={t}
              style={[
                typography.micro,
                styles.tick,
                { left: `${((t - min) / span) * 100}%`, color: t === value ? colors.goldDeep : colors.textFaint },
              ]}
            >
              {t}
            </Text>
          ))}
        </View>
      )}
    </View>
  );
};

// =====================================================
// AcceptGauge
// =====================================================

export const pct = (rate: number | null | undefined): string =>
  rate === null || rate === undefined || !Number.isFinite(rate) ? '—' : `${Math.round(rate * 100)}%`;

export const AcceptGauge: React.FC<{ rate: number | null; estimate?: boolean }> = ({ rate, estimate }) => {
  const { colors } = useTheme();
  const tone = rate === null ? colors.textFaint : rate >= 0.75 ? colors.success : rate >= 0.4 ? colors.warning : colors.danger;
  return (
    <View style={styles.gaugeWrap}>
      <View style={styles.gaugeRow}>
        <View style={[styles.gaugeTrack, { backgroundColor: colors.inset }]}>
          {rate !== null && <View style={[styles.gaugeFill, { width: `${Math.max(4, rate * 100)}%`, backgroundColor: tone }]} />}
        </View>
        <Text style={[typography.bodyStrong, styles.gaugeText, { color: tone }]}>{rate === null ? '—' : pct(rate)}</Text>
      </View>
      {estimate && rate !== null && (
        <Text style={[typography.micro, styles.gaugeNote, { color: colors.textFaint }]}>ประมาณการ</Text>
      )}
    </View>
  );
};

// =====================================================
// RiderPayBands — ตารางค่าส่งตามระยะ
// =====================================================

/** ช่วงค่าส่ง เช่น "30" / "38–50" / "78+" */
export const feeRange = (band: RiderPayBand): string => {
  const lo = Math.round(band.fee_min);
  const hi = Math.round(band.fee_max);
  if (band.to_km === null) return `${lo}+`;
  return lo === hi ? `${lo}` : `${lo}–${hi}`;
};

export const RiderPayBands: React.FC<{ bands: RiderPayBand[]; withBonus: boolean }> = ({ bands, withBonus }) => {
  const { colors } = useTheme();
  return (
    <View>
      <View style={[styles.bandRow, styles.bandHead, { borderBottomColor: colors.divider }]}>
        <Text style={[typography.caption, styles.colRange, { color: colors.textMuted }]}>ระยะลูกค้า</Text>
        <Text style={[typography.caption, styles.colFee, { color: colors.textMuted }]}>ค่าส่ง (บาท)</Text>
        <Text style={[typography.caption, styles.colRate, { color: colors.textMuted }]}>โอกาสมีคนรับใน 5 นาที</Text>
      </View>
      {bands.map((band, index) => {
        const rate = withBonus && band.accept_rate_with_bonus !== null ? band.accept_rate_with_bonus : band.accept_rate_5min;
        return (
          <View
            key={band.key || String(index)}
            style={[styles.bandRow, index > 0 && { borderTopWidth: 1, borderTopColor: colors.divider }]}
            accessible
            accessibilityLabel={`ระยะ ${band.label} ค่าส่ง ${feeRange(band)} บาท โอกาสมีคนรับใน 5 นาที ${pct(rate)}${band.basis === 'estimate' ? ' ประมาณการ' : ''}`}
          >
            <Text style={[typography.bodyStrong, styles.colRange, { color: colors.textStrong }]} numberOfLines={1}>
              {band.label}
            </Text>
            <Text style={[typography.body, styles.colFee, styles.tabular, { color: colors.text }]}>{feeRange(band)}</Text>
            <View style={styles.colRate}>
              <AcceptGauge rate={rate} estimate={band.basis === 'estimate'} />
            </View>
          </View>
        );
      })}
    </View>
  );
};

const styles = StyleSheet.create({
  disabled: {
    opacity: 0.5,
  },
  sliderHit: {
    height: 44,
    justifyContent: 'center',
  },
  track: {
    height: 8,
    borderRadius: 4,
    overflow: 'hidden',
  },
  fill: {
    height: '100%',
    borderRadius: 4,
  },
  thumb: {
    position: 'absolute',
    top: (44 - THUMB) / 2,
    width: THUMB,
    height: THUMB,
    borderRadius: THUMB / 2,
    borderWidth: 3,
    overflow: 'hidden',
  },
  ticks: {
    height: 18,
    marginHorizontal: 0,
  },
  tick: {
    position: 'absolute',
    width: 28,
    marginLeft: -14,
    textAlign: 'center',
    fontVariant: ['tabular-nums'],
  },
  gaugeWrap: {
    flex: 1,
  },
  gaugeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  gaugeTrack: {
    flex: 1,
    height: 8,
    borderRadius: 4,
    overflow: 'hidden',
  },
  gaugeFill: {
    height: '100%',
    borderRadius: 4,
  },
  gaugeText: {
    minWidth: 40,
    textAlign: 'right',
    fontVariant: ['tabular-nums'],
  },
  gaugeNote: {
    textAlign: 'right',
  },
  bandHead: {
    borderBottomWidth: 1,
    paddingBottom: spacing.sm,
  },
  bandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.sm + 2,
    minHeight: 44,
  },
  colRange: {
    width: 76,
  },
  colFee: {
    width: 66,
  },
  colRate: {
    flex: 1,
  },
  tabular: {
    fontVariant: ['tabular-nums'],
  },
});
