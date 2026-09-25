/**
 * StatusTimeline — ไทม์ไลน์สถานะแนวตั้ง (จุด + เส้นเชื่อม)
 *
 * step.state: done = ผ่านแล้ว · current = กำลังอยู่ขั้นนี้ · todo = ยังไม่ถึง · failed = ยกเลิก/ล้มเหลว
 */

import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme, spacing, typography } from '@/theme';

export interface TimelineStep {
  key: string;
  label: string;
  /** คำอธิบาย/เวลา */
  caption?: string;
  icon?: string;
  state: 'done' | 'current' | 'todo' | 'failed';
}

export const StatusTimeline: React.FC<{ steps: TimelineStep[] }> = ({ steps }) => {
  const { colors } = useTheme();

  return (
    <View accessibilityRole="list">
      {steps.map((step, index) => {
        const last = index === steps.length - 1;
        const dotColor =
          step.state === 'done'
            ? colors.success
            : step.state === 'current'
              ? colors.gold
              : step.state === 'failed'
                ? colors.danger
                : colors.border;
        const lineColor = step.state === 'done' ? colors.success : colors.border;
        const textColor = step.state === 'todo' ? colors.textFaint : colors.textStrong;

        return (
          <View key={step.key} style={styles.row} accessibilityLabel={`${step.label} ${step.caption || ''}`}>
            <View style={styles.rail}>
              <View
                style={[
                  styles.dot,
                  {
                    backgroundColor: step.state === 'todo' ? colors.inset : dotColor,
                    borderColor: dotColor,
                  },
                  step.state === 'current' && styles.dotCurrent,
                ]}
              >
                <Text style={[styles.dotIcon, { color: colors.textOnAccent }]}>
                  {step.state === 'done' ? '✓' : step.state === 'failed' ? '✕' : step.icon || ''}
                </Text>
              </View>
              {!last && <View style={[styles.line, { backgroundColor: lineColor }]} />}
            </View>
            <View style={[styles.text, !last && styles.textGap]}>
              <Text style={[step.state === 'current' ? typography.bodyStrong : typography.bodySm, { color: textColor }]}>
                {step.label}
              </Text>
              {!!step.caption && (
                <Text style={[typography.caption, { color: colors.textMuted }]}>{step.caption}</Text>
              )}
            </View>
          </View>
        );
      })}
    </View>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  rail: {
    alignItems: 'center',
    width: 26,
  },
  dot: {
    width: 22,
    height: 22,
    borderRadius: 11,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dotCurrent: {
    width: 26,
    height: 26,
    borderRadius: 13,
  },
  dotIcon: {
    fontSize: 11,
    fontWeight: '800',
  },
  line: {
    width: 2,
    flex: 1,
    minHeight: 18,
    marginVertical: 2,
  },
  text: {
    flex: 1,
    paddingTop: 1,
  },
  textGap: {
    paddingBottom: spacing.md,
  },
});

export default StatusTimeline;
