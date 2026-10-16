/**
 * TrustSeals — ตรากลมคู่ "ยืนยันตัวตนแล้ว" (eKYC) + "ผ่านการอบรม" (ห้องเรียน) แบบ B ที่เจ้าของเลือก (2026-10-13)
 *
 *   VerifiedSeal : พื้นเขียวมิ้นต์ #E1F5EE · ขอบเขียว #1D9E75 · โล่มีเครื่องหมายถูก #085041
 *   TrainingSeal : พื้นน้ำเงินกรมท่า #042C53 · ขอบทอง #EF9F27 · หมวกบัณฑิตทอง #FAC775
 *
 * - สีตราเป็นสีประจำตรา (เหมือนโลโก้) เหมือนกันทั้งโหมดสว่าง/มืด — ไม่ดึงจากธีม
 * - วางคู่กันเสมอ: ตรายืนยันตัวตนก่อน แล้วตราอบรมถัดไป (TrustSeals วางให้เอง)
 * - needsRefresh = ตราจาง + ป้ายเล็ก "ต้องอบรมเพิ่ม" (compact = ไม่มีป้าย ใช้ตอนวางทับมุมรูป)
 * - ทุกตรามี accessibilityLabel ภาษาไทยเสมอ (คนใช้โปรแกรมอ่านหน้าจอรู้ว่าตราแปลว่าอะไร)
 *
 * @example
 * <View style={{ flexDirection: 'row', alignItems: 'center', gap: 4 }}>
 *   <Text>{rider.name}</Text>
 *   <TrustSeals verified={rider.verified} training={parseTrainingBadge(rider.training_badge, 'rider')} size={15} />
 * </View>
 */

import React, { memo, useMemo } from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import Svg, { Circle, G, Path } from 'react-native-svg';
import { Text } from '@/components/ui/Text';
import { ICON_PATHS } from '@/components/ui/iconPaths';
import { useTheme, typography } from '@/theme';
import {
  NEEDS_REFRESH_LABEL,
  parseTrainingBadge,
  trainingBadgeA11yLabel,
  type TrainingBadge,
  type TrainingRole,
} from './trainingBadge';

/** สีประจำตรา (ตามแบบ B) */
export const SEAL_COLORS = {
  verified: { fill: '#E1F5EE', rim: '#1D9E75', glyph: '#085041' },
  training: { fill: '#042C53', rim: '#EF9F27', glyph: '#FAC775' },
} as const;

/** ความจางของตราที่ต้องอบรมเพิ่ม */
const REFRESH_OPACITY = 0.45;

// =====================================================
// ตรากลมพื้นฐาน (วงกลม + ขอบ + ไอคอนกลาง)
// =====================================================

interface SealDiscProps {
  size: number;
  fill: string;
  rim: string;
  glyph: string;
  glyphPaths: readonly string[];
  /** สัดส่วนไอคอนต่อเส้นผ่านศูนย์กลาง */
  glyphScale?: number;
  /** ขยับไอคอนลงเล็กน้อย (หมวกบัณฑิตดูลอยถ้าวางกึ่งกลางพอดี) */
  glyphOffsetY?: number;
}

const SealDisc: React.FC<SealDiscProps> = ({ size, fill, rim, glyph, glyphPaths, glyphScale = 0.58, glyphOffsetY = 0 }) => {
  // ขอบ 2px ตามแบบ (ตราจิ๋วต่ำกว่า 18px ใช้ 1.5px ไม่ให้ขอบกินพื้นที่ไอคอน)
  const rimWidth = size >= 18 ? 2 : 1.5;
  const r = (size - rimWidth) / 2;
  const glyphSize = size * glyphScale;
  const offset = (size - glyphSize) / 2;
  const scale = glyphSize / 256;
  return (
    <Svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
      <Circle cx={size / 2} cy={size / 2} r={r} fill={fill} stroke={rim} strokeWidth={rimWidth} />
      <G transform={`translate(${offset} ${offset + size * glyphOffsetY}) scale(${scale})`}>
        {glyphPaths.map((d, i) => (
          <Path key={i} d={d} fill={glyph} />
        ))}
      </G>
    </Svg>
  );
};

/** วงสีพื้นรอบตรา (ใช้ตอนวางทับมุมรูป ให้ตราไม่จมไปกับรูป) */
const RingWrap: React.FC<{ size: number; ringColor?: string; children: React.ReactNode }> = ({ size, ringColor, children }) => {
  if (!ringColor) return <>{children}</>;
  const ring = Math.max(1.5, Math.round(size * 0.12));
  const outer = size + ring * 2;
  return (
    <View style={[styles.center, { width: outer, height: outer, borderRadius: outer / 2, backgroundColor: ringColor }]}>
      {children}
    </View>
  );
};

// =====================================================
// ตรายืนยันตัวตน (eKYC)
// =====================================================

export interface VerifiedSealProps {
  /** เส้นผ่านศูนย์กลาง (ค่าเริ่มต้น 16) */
  size?: number;
  /** แสดงคำว่า "ยืนยันตัวตนแล้ว" ต่อท้าย */
  withLabel?: boolean;
  /** วงขอบสีพื้นรอบตรา (วางทับมุมรูป) — ส่งสีพื้นที่วางอยู่ */
  ringColor?: string;
  style?: StyleProp<ViewStyle>;
}

export const VerifiedSeal: React.FC<VerifiedSealProps> = memo(({ size = 16, withLabel = false, ringColor, style }) => {
  const { colors } = useTheme();
  const seal = (
    <RingWrap size={size} ringColor={ringColor}>
      <SealDisc
        size={size}
        fill={SEAL_COLORS.verified.fill}
        rim={SEAL_COLORS.verified.rim}
        glyph={SEAL_COLORS.verified.glyph}
        glyphPaths={ICON_PATHS['shield-check'].fill}
        glyphScale={0.6}
      />
    </RingWrap>
  );

  if (!withLabel) {
    return (
      <View style={style} accessible accessibilityRole="image" accessibilityLabel="ยืนยันตัวตนแล้ว">
        {seal}
      </View>
    );
  }
  return (
    <View style={[styles.row, style]} accessible accessibilityLabel="ยืนยันตัวตนแล้ว">
      {seal}
      <Text style={[typography.caption, { color: colors.success }]}>ยืนยันตัวตนแล้ว</Text>
    </View>
  );
});
VerifiedSeal.displayName = 'VerifiedSeal';

// =====================================================
// ตราผ่านการอบรม (ห้องเรียน)
// =====================================================

export interface TrainingSealProps {
  size?: number;
  /** ผ่านกฎรุ่นเก่า → ตราจาง + ป้าย "ต้องอบรมเพิ่ม" */
  needsRefresh?: boolean;
  /** ข้อความตรา (ไทย) สำหรับโปรแกรมอ่านหน้าจอ/ป้ายข้างตรา (ค่าเริ่มต้น "ผ่านการอบรม") */
  label?: string;
  /** แสดงข้อความตราต่อท้าย */
  withLabel?: boolean;
  /** ไม่แสดงป้าย "ต้องอบรมเพิ่ม" (พื้นที่แคบ เช่น มุมรูป) — ยังอ่านออกเสียงครบ */
  compact?: boolean;
  ringColor?: string;
  style?: StyleProp<ViewStyle>;
}

export const TrainingSeal: React.FC<TrainingSealProps> = memo(
  ({ size = 16, needsRefresh = false, label = 'ผ่านการอบรม', withLabel = false, compact = false, ringColor, style }) => {
    const { colors } = useTheme();
    const a11y = trainingBadgeA11yLabel({ label, needs_refresh: needsRefresh });
    const seal = (
      <View style={needsRefresh ? styles.dim : undefined}>
        <RingWrap size={size} ringColor={ringColor}>
          <SealDisc
            size={size}
            fill={SEAL_COLORS.training.fill}
            rim={SEAL_COLORS.training.rim}
            glyph={SEAL_COLORS.training.glyph}
            glyphPaths={ICON_PATHS['graduation-cap'].fill}
            glyphScale={0.62}
            glyphOffsetY={0.02}
          />
        </RingWrap>
      </View>
    );
    const showRefresh = needsRefresh && !compact;

    if (!withLabel && !showRefresh) {
      return (
        <View style={style} accessible accessibilityRole="image" accessibilityLabel={a11y}>
          {seal}
        </View>
      );
    }
    return (
      <View style={[styles.row, style]} accessible accessibilityLabel={a11y}>
        {seal}
        {withLabel && (
          <Text numberOfLines={1} style={[typography.caption, { color: needsRefresh ? colors.textMuted : colors.goldDeep }]}>
            {label}
          </Text>
        )}
        {showRefresh && (
          <View style={[styles.refreshTag, { backgroundColor: colors.warningSoft }]}>
            <Text numberOfLines={1} style={[styles.refreshText, { color: colors.warning }]}>
              {NEEDS_REFRESH_LABEL}
            </Text>
          </View>
        )}
      </View>
    );
  }
);
TrainingSeal.displayName = 'TrainingSeal';

// =====================================================
// ตราอบรมจากค่าดิบของ server (แปลงให้เอง · ไม่มี/ไม่ผ่าน = ไม่วาดอะไรเลย)
// =====================================================

export interface TrainingBadgeSealProps extends Omit<TrainingSealProps, 'needsRefresh' | 'label'> {
  /** training_badge ดิบจาก payload (server รุ่นเก่าไม่ส่ง = undefined) */
  value: unknown;
  /** บทบาทที่รู้จากตำแหน่งที่วาง (rider object = 'rider') เมื่อ server ไม่ส่ง role */
  role?: TrainingRole;
}

export const TrainingBadgeSeal: React.FC<TrainingBadgeSealProps> = memo(({ value, role, ...rest }) => {
  const badge = useMemo(() => parseTrainingBadge(value, role), [value, role]);
  if (!badge) return null;
  return <TrainingSeal {...rest} needsRefresh={badge.needs_refresh} label={badge.label} />;
});
TrainingBadgeSeal.displayName = 'TrainingBadgeSeal';

// =====================================================
// คู่ตรา (ยืนยันตัวตน + อบรม) — ใช้ข้างชื่อคน/ร้าน
// =====================================================

export interface TrustSealsProps {
  /** ยืนยันตัวตนแล้ว (จาก server เท่านั้น) */
  verified?: boolean;
  /** ตราอบรมที่แปลงแล้ว (parseTrainingBadge) — null = ไม่แสดง */
  training?: TrainingBadge | null;
  size?: number;
  /** ไม่แสดงป้าย "ต้องอบรมเพิ่ม" */
  compact?: boolean;
  style?: StyleProp<ViewStyle>;
}

export const TrustSeals: React.FC<TrustSealsProps> = memo(({ verified = false, training, size = 16, compact = false, style }) => {
  const hasTraining = !!training?.certified;
  if (!verified && !hasTraining) return null;
  return (
    <View style={[styles.row, style]}>
      {verified && <VerifiedSeal size={size} />}
      {hasTraining && training && (
        <TrainingSeal size={size} needsRefresh={training.needs_refresh} label={training.label} compact={compact} />
      )}
    </View>
  );
});
TrustSeals.displayName = 'TrustSeals';

const styles = StyleSheet.create({
  center: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  dim: {
    opacity: REFRESH_OPACITY,
  },
  refreshTag: {
    borderRadius: 999,
    paddingHorizontal: 6,
    paddingVertical: 1,
  },
  refreshText: {
    fontSize: 10.5,
    lineHeight: 15,
    fontWeight: '600',
  },
});
