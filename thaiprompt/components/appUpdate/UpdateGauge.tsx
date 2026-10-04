/**
 * UpdateGauge — เกจวงกลมทองสำหรับหน้าอัปเดตแอป (วางบนหัวน้ำเงินกรมท่า)
 *
 * ชั้นของภาพ (ล่าง → บน)
 *   1. แสงเรืองทองจางกลางวง (RadialGradient)
 *   2. ขีดสเกล 60 ขีดรอบวง (ทุกขีดที่ 5 ยาวกว่า) — ขีดที่ผ่านแล้วเป็นสีทอง
 *   3. รางวงแหวน + วงในบางๆ
 *   4. แสงฟุ้งหลังเส้นทอง (เส้นกว้างโปร่ง 4 ชั้น ยิ่งกว้างยิ่งจาง — ไม่ใช้ filter blur ที่บางเครื่องไม่รองรับ)
 *   5. เส้นทองไล่เฉด goldLight → gold ปลายมน — กวาดลื่นด้วย reanimated (ไม่กระโดดตาม event)
 *   6. จุดหัวเส้นสว่างที่ปลายเส้น
 *   7. โหมด indeterminate (กำลังตรวจ) = ส่วนโค้งทองหมุนรอบวง · verifying = ประกายวิ่งรอบวงเต็ม
 *
 * ตัวเลข/ข้อความกลางวงส่งมาเป็น children
 */

import React, { memo, useEffect } from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import Animated, {
  cancelAnimation,
  Easing,
  useAnimatedProps,
  useAnimatedStyle,
  useSharedValue,
  withRepeat,
  withTiming,
} from 'react-native-reanimated';
import Svg, { Circle, Defs, G, Line, LinearGradient, RadialGradient, Stop } from 'react-native-svg';
import { useTheme, withAlpha } from '@/theme';

const AnimatedCircle = Animated.createAnimatedComponent(Circle);

export type GaugeMode = 'progress' | 'indeterminate' | 'sweep';

export interface UpdateGaugeProps {
  /** 0–1 */
  progress: number;
  /** progress = ตามค่าจริง · indeterminate = หมุนรอ (กำลังตรวจ) · sweep = วงเต็ม + ประกายวิ่ง (กำลังตรวจไฟล์) */
  mode?: GaugeMode;
  /** เส้นจางลง (หยุดชั่วคราว / ผิดพลาด) */
  dimmed?: boolean;
  size?: number;
  children?: React.ReactNode;
  accessibilityLabel?: string;
  style?: StyleProp<ViewStyle>;
}

const TICKS = 60;
const DESIGN = 240;
/** ชั้นแสงฟุ้งหลังเส้นทอง (กว้างกว่าเส้นหลัก extra px ที่ขนาด 240 · ยิ่งกว้างยิ่งจาง) */
const GLOW_LAYERS = [
  { extra: 30, alpha: 0.035 },
  { extra: 20, alpha: 0.05 },
  { extra: 12, alpha: 0.08 },
  { extra: 5, alpha: 0.14 },
] as const;

/** ขีดสเกล — render ใหม่เฉพาะเมื่อจำนวนขีดที่สว่างเปลี่ยน */
const Ticks = memo(function Ticks({
  lit,
  center,
  scale,
  dim,
  on,
  onMajor,
}: {
  lit: number;
  center: number;
  scale: number;
  dim: string;
  on: string;
  onMajor: string;
}) {
  const inner = 103 * scale;
  return (
    <G>
      {Array.from({ length: TICKS }, (_, i) => {
        const major = i % 5 === 0;
        const outer = (major ? 115 : 110) * scale;
        const a = (i / TICKS) * Math.PI * 2 - Math.PI / 2;
        const cos = Math.cos(a);
        const sin = Math.sin(a);
        const active = i < lit;
        return (
          <Line
            key={i}
            x1={center + inner * cos}
            y1={center + inner * sin}
            x2={center + outer * cos}
            y2={center + outer * sin}
            stroke={active ? (major ? onMajor : on) : dim}
            strokeWidth={(major ? 2.4 : 1.4) * scale}
            strokeLinecap="round"
          />
        );
      })}
    </G>
  );
});

export const UpdateGauge: React.FC<UpdateGaugeProps> = ({
  progress,
  mode = 'progress',
  dimmed = false,
  size = DESIGN,
  children,
  accessibilityLabel,
  style,
}) => {
  const { colors } = useTheme();
  const scale = size / DESIGN;
  const c = size / 2;
  const r = 86 * scale;
  const stroke = 12 * scale;
  const circumference = 2 * Math.PI * r;
  const clamped = Math.max(0, Math.min(1, Number.isFinite(progress) ? progress : 0));
  const shown = mode === 'sweep' ? 1 : mode === 'indeterminate' ? 0 : clamped;

  // ---------- เส้นทองกวาดลื่น ----------
  const p = useSharedValue(shown);
  useEffect(() => {
    // ค่าลดลงมาก (เริ่มใหม่) = เลื่อนกลับเร็ว · ค่าเพิ่ม = ไหลตามแบบนุ่ม
    const duration = shown < p.value - 0.05 ? 380 : 650;
    p.value = withTiming(shown, { duration, easing: Easing.out(Easing.cubic) });
  }, [shown, p]);

  const arcProps = useAnimatedProps(() => ({
    strokeDashoffset: circumference * (1 - p.value),
    opacity: p.value > 0.004 ? 1 : 0,
  }));
  const headProps = useAnimatedProps(() => {
    const a = -Math.PI / 2 + Math.PI * 2 * p.value;
    return { cx: c + r * Math.cos(a), cy: c + r * Math.sin(a), opacity: p.value > 0.004 ? 1 : 0 };
  });

  // ---------- หมุนรอ (indeterminate) / ประกายวิ่ง (sweep) ----------
  const spin = useSharedValue(0);
  const spinning = mode !== 'progress';
  useEffect(() => {
    if (!spinning) {
      cancelAnimation(spin);
      spin.value = 0;
      return undefined;
    }
    spin.value = 0;
    spin.value = withRepeat(withTiming(1, { duration: mode === 'sweep' ? 1600 : 1300, easing: Easing.linear }), -1, false);
    return () => cancelAnimation(spin);
  }, [spinning, mode, spin]);
  const spinStyle = useAnimatedStyle(() => ({ transform: [{ rotate: `${spin.value * 360}deg` }] }));

  const lit = mode === 'sweep' ? TICKS : mode === 'indeterminate' ? 0 : Math.round(clamped * TICKS);
  const gold = colors.gold;
  const goldLight = colors.goldLight;
  const segment = circumference * (mode === 'sweep' ? 0.12 : 0.22);

  return (
    <View
      style={[{ width: size, height: size }, style]}
      accessible
      accessibilityRole="progressbar"
      accessibilityLabel={accessibilityLabel}
      accessibilityValue={mode === 'progress' ? { min: 0, max: 100, now: Math.floor(clamped * 100) } : undefined}
    >
      <Svg width={size} height={size} style={StyleSheet.absoluteFill}>
        <Defs>
          <RadialGradient id="tpHalo" cx="50%" cy="50%" r="50%">
            <Stop offset="0" stopColor={gold} stopOpacity={dimmed ? 0.1 : 0.22} />
            <Stop offset="0.55" stopColor={gold} stopOpacity={dimmed ? 0.04 : 0.08} />
            <Stop offset="1" stopColor={gold} stopOpacity={0} />
          </RadialGradient>
          <LinearGradient id="tpArc" x1="0" y1="0" x2="1" y2="1">
            <Stop offset="0" stopColor={goldLight} />
            <Stop offset="0.55" stopColor={gold} />
            <Stop offset="1" stopColor={goldLight} />
          </LinearGradient>
        </Defs>

        {/* 1) แสงเรืองกลางวง */}
        <Circle cx={c} cy={c} r={c} fill="url(#tpHalo)" />

        {/* 2) ขีดสเกล */}
        <Ticks
          lit={lit}
          center={c}
          scale={scale}
          dim={withAlpha(colors.onHeader, 0.2)}
          on={withAlpha(goldLight, dimmed ? 0.5 : 0.85)}
          onMajor={dimmed ? withAlpha(goldLight, 0.7) : goldLight}
        />

        {/* 3) รางวงแหวน + วงในบางๆ */}
        <Circle cx={c} cy={c} r={r} stroke={withAlpha(colors.onHeader, 0.09)} strokeWidth={stroke} fill="none" />
        <Circle cx={c} cy={c} r={r - 15 * scale} stroke={withAlpha(colors.onHeader, 0.07)} strokeWidth={1} fill="none" />

        {/* 4–5) รัศมีเรือง (เส้นโปร่งซ้อนหลายชั้น = แสงฟุ้งจางออก) + เส้นทอง (เริ่มที่ 12 นาฬิกา) */}
        <G transform={`rotate(-90 ${c} ${c})`}>
          {GLOW_LAYERS.map((layer) => (
            <AnimatedCircle
              key={layer.extra}
              cx={c}
              cy={c}
              r={r}
              stroke={withAlpha(goldLight, dimmed ? layer.alpha * 0.5 : layer.alpha)}
              strokeWidth={stroke + layer.extra * scale}
              strokeLinecap="round"
              fill="none"
              strokeDasharray={`${circumference} ${circumference}`}
              animatedProps={arcProps}
            />
          ))}
          <AnimatedCircle
            cx={c}
            cy={c}
            r={r}
            stroke="url(#tpArc)"
            strokeWidth={stroke}
            strokeLinecap="round"
            fill="none"
            strokeOpacity={dimmed ? 0.55 : 1}
            strokeDasharray={`${circumference} ${circumference}`}
            animatedProps={arcProps}
          />
        </G>

        {/* 6) จุดหัวเส้น */}
        {mode === 'progress' && (
          <>
            <AnimatedCircle r={11 * scale} fill={withAlpha(goldLight, dimmed ? 0.12 : 0.3)} animatedProps={headProps} />
            <AnimatedCircle r={4.6 * scale} fill={dimmed ? goldLight : colors.onHeader} animatedProps={headProps} />
          </>
        )}
      </Svg>

      {/* 7) ส่วนโค้งหมุน / ประกายวิ่ง */}
      {spinning && (
        <Animated.View pointerEvents="none" style={[StyleSheet.absoluteFill, spinStyle]}>
          <Svg width={size} height={size}>
            <G transform={`rotate(-90 ${c} ${c})`}>
              <Circle
                cx={c}
                cy={c}
                r={r}
                stroke={withAlpha(goldLight, mode === 'sweep' ? 0.35 : 0.22)}
                strokeWidth={stroke + 10 * scale}
                strokeLinecap="round"
                fill="none"
                strokeDasharray={`${segment} ${circumference}`}
              />
              <Circle
                cx={c}
                cy={c}
                r={r}
                stroke={mode === 'sweep' ? colors.onHeader : goldLight}
                strokeOpacity={mode === 'sweep' ? 0.75 : 1}
                strokeWidth={mode === 'sweep' ? stroke * 0.45 : stroke}
                strokeLinecap="round"
                fill="none"
                strokeDasharray={`${segment} ${circumference}`}
              />
            </G>
          </Svg>
        </Animated.View>
      )}

      <View pointerEvents="none" style={styles.center}>
        {children}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  center: {
    position: 'absolute',
    top: 0,
    right: 0,
    bottom: 0,
    left: 0,
    alignItems: 'center',
    justifyContent: 'center',
  },
});

export default UpdateGauge;
