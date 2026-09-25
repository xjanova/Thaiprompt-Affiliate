/**
 * ภาพไพ่ทาโรต์ของแอป (วาดด้วย SVG — ไม่ใช้อีโมจิจากข้อมูลไพ่)
 *
 * - TarotGlyph     = สัญลักษณ์บนหน้าไพ่: ดวงอาทิตย์ (ไพ่ใหญ่) · ไม้เท้า · ถ้วย · ดาบ · เหรียญ (ดาวห้าแฉก)
 * - TarotCardBack  = หลังไพ่น้ำเงินกรมท่า กรอบทอง ข้าวหลามตัด + พระจันทร์เสี้ยว + ประกายดาว
 * - TarotCardFace  = หน้าไพ่ทองฟอยล์ + เลขโรมัน/อันดับ + สัญลักษณ์ชุดไพ่
 *
 * สัดส่วนไพ่ = กว้าง 1 : สูง 1.4 ทุกขนาด
 *
 * @example
 * <TarotCardBack width={56} />
 * <TarotCardFace card={card} width={108} />
 */

import React, { memo } from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import Svg, { Circle, Line, Path, Rect } from 'react-native-svg';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import { useTheme, typography, withAlpha } from '@/theme';
import type { TarotCard } from '@/data/tarotData';
import { glyphKindOf, rankLabel, type TarotGlyphKind } from './tarotVisuals';

/** อัตราส่วนความสูงต่อความกว้างของไพ่ */
export const TAROT_CARD_RATIO = 1.4;

// =====================================================
// เส้นทางรูปทรง (คำนวณครั้งเดียว)
// =====================================================

/** ดาวห้าแฉกแบบลากเส้นต่อเนื่อง (pentagram) จุดยอดอยู่บนวงกลมรัศมี r */
const pentagram = (cx: number, cy: number, r: number): string => {
  const points = [0, 1, 2, 3, 4].map((k) => {
    const a = ((-90 + k * 144) * Math.PI) / 180;
    return `${(cx + r * Math.cos(a)).toFixed(2)} ${(cy + r * Math.sin(a)).toFixed(2)}`;
  });
  return `M${points.join(' L')} Z`;
};

/** ประกายดาวสี่แฉก (ขอบโค้งเว้า) */
const sparkle = (cx: number, cy: number, r: number): string =>
  `M${cx} ${cy - r} Q${cx} ${cy} ${cx + r} ${cy} Q${cx} ${cy} ${cx} ${cy + r} Q${cx} ${cy} ${cx - r} ${cy} Q${cx} ${cy} ${cx} ${cy - r} Z`;

/** รัศมีดวงอาทิตย์ 8 เส้น (ยาวสลับสั้น) */
const SUN_RAYS = Array.from({ length: 8 }, (_, i) => {
  const a = (i * 45 * Math.PI) / 180;
  const r1 = 6.6;
  const r2 = i % 2 === 0 ? 9.6 : 8.4;
  return {
    x1: 12 + r1 * Math.cos(a),
    y1: 12 + r1 * Math.sin(a),
    x2: 12 + r2 * Math.cos(a),
    y2: 12 + r2 * Math.sin(a),
  };
});

const PENTAGRAM = pentagram(12, 12, 6.8);

/** พระจันทร์เสี้ยวกลางหลังไพ่ (วงนอก r8 ที่ 30,42 ถูกวงใน r7 ที่ 33.5,40 กัด) */
const CRESCENT = 'M29.894 34.002 A8 8 0 1 0 36.836 46.152 A7 7 0 0 1 29.894 34.002 Z';
const BACK_SPARKLE_TOP = sparkle(30, 11, 3.2);
const BACK_SPARKLE_BOTTOM = sparkle(30, 73, 3.2);
const BACK_CORNERS: ReadonlyArray<readonly [number, number]> = [
  [8.5, 8.5],
  [51.5, 8.5],
  [8.5, 75.5],
  [51.5, 75.5],
];

// =====================================================
// TarotGlyph
// =====================================================

export interface TarotGlyphProps {
  kind: TarotGlyphKind;
  size?: number;
  color: string;
  /** ความหนาเส้นในหน่วย viewBox 24 (ค่าเริ่มต้น 1.6) */
  strokeWidth?: number;
}

/** สัญลักษณ์ชุดไพ่แบบลายเส้น (viewBox 24 เข้าชุดกับไอคอนเส้นของแอป) */
export const TarotGlyph = memo(function TarotGlyph({ kind, size = 24, color, strokeWidth = 1.6 }: TarotGlyphProps) {
  const line = {
    stroke: color,
    strokeWidth,
    strokeLinecap: 'round' as const,
    strokeLinejoin: 'round' as const,
    fill: 'none',
  };

  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" accessible={false} importantForAccessibility="no-hide-descendants">
      {kind === 'major' && (
        <>
          <Circle cx={12} cy={12} r={4.2} {...line} />
          <Circle cx={12} cy={12} r={1.3} fill={color} />
          {SUN_RAYS.map((ray, i) => (
            <Line key={i} {...ray} {...line} />
          ))}
        </>
      )}

      {kind === 'wands' && (
        <>
          <Line x1={7} y1={19.8} x2={16.4} y2={4.6} {...line} strokeWidth={strokeWidth * 1.12} />
          <Circle cx={16.9} cy={3.8} r={1.3} fill={color} />
          <Path d="M12.17 11.44 Q14.6 12.74 16.76 11.03 Q14.33 9.74 12.17 11.44 Z" fill={color} />
          <Path d="M9.82 15.24 Q9.45 12.66 6.95 11.9 Q7.32 14.48 9.82 15.24 Z" fill={color} />
        </>
      )}

      {kind === 'cups' && (
        <>
          <Path d="M6 4.5 H18 V6.5 A6 6 0 0 1 6 6.5 Z" {...line} />
          <Line x1={12} y1={12.5} x2={12} y2={16.6} {...line} />
          <Path d="M10 16.6 H14 L15.6 19.6 H8.4 Z" {...line} />
        </>
      )}

      {kind === 'swords' && (
        <>
          <Path d="M12 2.6 L13.3 4.7 V14 H10.7 V4.7 Z" {...line} />
          <Path d="M7.6 13.2 Q12 15.6 16.4 13.2" {...line} />
          <Line x1={12} y1={14.6} x2={12} y2={18.4} {...line} />
          <Circle cx={12} cy={19.9} r={1.3} {...line} />
        </>
      )}

      {kind === 'pentacles' && (
        <>
          <Circle cx={12} cy={12} r={8.8} {...line} />
          <Path d={PENTAGRAM} {...line} />
        </>
      )}
    </Svg>
  );
});

// =====================================================
// TarotCardBack
// =====================================================

export interface TarotCardBackProps {
  width: number;
  radius?: number;
  style?: StyleProp<ViewStyle>;
}

/** หลังไพ่ — น้ำเงินกรมท่าไล่เฉด กรอบทองสองชั้น ลายข้าวหลามตัด + พระจันทร์เสี้ยว */
export const TarotCardBack = memo(function TarotCardBack({ width, radius = 7, style }: TarotCardBackProps) {
  const { colors, gradients } = useTheme();
  const height = width * TAROT_CARD_RATIO;

  return (
    <View
      style={[
        styles.card,
        { width, height, borderRadius: radius, borderColor: withAlpha(colors.gold, 0.7) },
        style,
      ]}
    >
      <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={StyleSheet.absoluteFill} />
      <Svg
        width={width}
        height={height}
        viewBox="0 0 60 84"
        style={StyleSheet.absoluteFill}
        accessible={false}
        importantForAccessibility="no-hide-descendants"
      >
        <Rect x={4} y={4} width={52} height={76} rx={3} stroke={colors.gold} strokeOpacity={0.55} strokeWidth={0.9} fill="none" />
        <Path d="M30 20 L45 42 L30 64 L15 42 Z" stroke={colors.gold} strokeOpacity={0.8} strokeWidth={1} fill="none" />
        <Path d={CRESCENT} fill={colors.goldLight} />
        <Path d={BACK_SPARKLE_TOP} fill={colors.goldLight} />
        <Path d={BACK_SPARKLE_BOTTOM} fill={colors.goldLight} />
        {BACK_CORNERS.map(([cx, cy]) => (
          <Circle key={`${cx}-${cy}`} cx={cx} cy={cy} r={1} fill={colors.gold} />
        ))}
      </Svg>
    </View>
  );
});

// =====================================================
// TarotCardFace
// =====================================================

export interface TarotCardFaceProps {
  card: TarotCard;
  width: number;
  radius?: number;
  /** แสดงเลขโรมัน/อันดับบนหัวไพ่ + ลายท้ายไพ่ (ไพ่ขนาดเล็กมากควรปิด) */
  detailed?: boolean;
  style?: StyleProp<ViewStyle>;
}

/** หน้าไพ่ทองฟอยล์ — สัญลักษณ์ชุดไพ่ตรงกลาง + อันดับด้านบน */
export const TarotCardFace = memo(function TarotCardFace({
  card,
  width,
  radius = 9,
  detailed = true,
  style,
}: TarotCardFaceProps) {
  const { colors, gradients } = useTheme();
  const height = width * TAROT_CARD_RATIO;
  const ink = colors.textOnGold;
  const inset = Math.max(3, width * 0.06);

  return (
    <View style={[styles.card, { width, height, borderRadius: radius, borderColor: withAlpha(ink, 0.22) }, style]}>
      <LinearGradient colors={gradients.gold} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={StyleSheet.absoluteFill} />
      {/* กรอบในบางๆ */}
      <View
        pointerEvents="none"
        style={[
          styles.innerFrame,
          { top: inset, left: inset, right: inset, bottom: inset, borderRadius: Math.max(2, radius - inset / 2), borderColor: withAlpha(ink, 0.32) },
        ]}
      />

      {detailed && (
        <Text
          numberOfLines={1}
          style={[
            typography.serifSm,
            styles.rank,
            { top: inset + width * 0.05, fontSize: width * 0.115, lineHeight: width * 0.17, color: ink },
          ]}
        >
          {rankLabel(card)}
        </Text>
      )}

      <TarotGlyph kind={glyphKindOf(card)} size={width * (detailed ? 0.46 : 0.56)} color={ink} strokeWidth={1.5} />

      {detailed && (
        <View style={[styles.footer, { bottom: inset + width * 0.07 }]}>
          <View style={[styles.footerLine, { backgroundColor: withAlpha(ink, 0.45) }]} />
          <View style={[styles.footerDiamond, { backgroundColor: ink }]} />
          <View style={[styles.footerLine, { backgroundColor: withAlpha(ink, 0.45) }]} />
        </View>
      )}
    </View>
  );
});

const styles = StyleSheet.create({
  card: {
    overflow: 'hidden',
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  innerFrame: {
    position: 'absolute',
    borderWidth: 1,
  },
  rank: {
    position: 'absolute',
    left: 0,
    right: 0,
    textAlign: 'center',
    letterSpacing: 1,
  },
  footer: {
    position: 'absolute',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  footerLine: {
    width: 14,
    height: 1,
  },
  footerDiamond: {
    width: 4,
    height: 4,
    transform: [{ rotate: '45deg' }],
  },
});

export default TarotCardFace;
