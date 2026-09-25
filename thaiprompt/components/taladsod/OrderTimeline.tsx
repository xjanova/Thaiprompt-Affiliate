/**
 * OrderTimeline — ไทม์ไลน์สถานะออเดอร์แนวตั้ง (หน้าติดตามออเดอร์ตลาดสด)
 *
 * รับ steps รูปแบบเดียวกับ StatusTimeline (buildFmTimeline) แต่วาดไอคอนด้วย <Icon/> แทนตัวอักษร
 *   done    = เหรียญน้ำเงินกรมท่า + เครื่องหมายถูกสีทอง · เส้นเชื่อมสีทอง
 *   current = เหรียญทองเรือง + ไอคอนขั้นนั้น + ป้าย "ตอนนี้"
 *   todo    = วงจางขอบบาง
 *   failed  = วงแดงอ่อน + กากบาท
 * step.icon เป็นชื่อไอคอน (ไม่รู้จัก = ใช้นาฬิกาแทน)
 */

import React from 'react';
import { StyleSheet, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import { Icon, Pill, isIconName, type IconName } from '@/components/ui';
import type { TimelineStep } from '@/components/shop';
import { useTheme, glowStyle, spacing, typography } from '@/theme';

const DOT = 32;

export const OrderTimeline: React.FC<{ steps: TimelineStep[] }> = ({ steps }) => {
  const { colors, gradients } = useTheme();

  return (
    <View accessibilityRole="list">
      {steps.map((step, index) => {
        const last = index === steps.length - 1;
        const icon: IconName = isIconName(step.icon) ? step.icon : 'clock';

        // ---------- วงไอคอน ----------
        let dot: React.ReactNode;
        if (step.state === 'done') {
          dot = (
            <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 0, y: 1 }} style={styles.dot}>
              <Icon name="check" size={15} color={colors.goldLight} weight="bold" />
            </LinearGradient>
          );
        } else if (step.state === 'current') {
          dot = (
            <LinearGradient
              colors={gradients.primary}
              start={{ x: 0, y: 0 }}
              end={{ x: 0.3, y: 1 }}
              style={[styles.dot, glowStyle(colors.gold, 0.9)]}
            >
              <Icon name={icon} size={16} color={colors.textOnGold} weight="fill" />
            </LinearGradient>
          );
        } else if (step.state === 'failed') {
          dot = (
            <View style={[styles.dot, { backgroundColor: colors.dangerSoft }]}>
              <Icon name="x" size={15} color={colors.danger} weight="bold" />
            </View>
          );
        } else {
          dot = (
            <View style={[styles.dot, { backgroundColor: colors.inset, borderWidth: 1, borderColor: colors.border }]}>
              <Icon name={icon} size={15} color={colors.textFaint} />
            </View>
          );
        }

        const labelColor =
          step.state === 'todo' ? colors.textFaint : step.state === 'failed' ? colors.danger : colors.textStrong;

        return (
          <View key={step.key} style={styles.row} accessibilityLabel={`${step.label} ${step.caption || ''}`}>
            <View style={styles.rail}>
              {dot}
              {!last && (
                <View style={[styles.line, { backgroundColor: step.state === 'done' ? colors.gold : colors.border }]} />
              )}
            </View>
            <View style={[styles.text, !last && styles.textGap]}>
              <View style={styles.labelRow}>
                <Text
                  style={[
                    step.state === 'current' || step.state === 'failed' ? typography.bodyStrong : typography.bodySm,
                    styles.label,
                    { color: labelColor },
                  ]}
                >
                  {step.label}
                </Text>
                {step.state === 'current' && <Pill label="ตอนนี้" tone="gold" />}
              </View>
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
    width: DOT,
  },
  dot: {
    width: DOT,
    height: DOT,
    borderRadius: DOT / 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  line: {
    width: 2,
    flex: 1,
    minHeight: 14,
    marginVertical: 3,
    borderRadius: 1,
  },
  text: {
    flex: 1,
    paddingTop: 5,
  },
  textGap: {
    paddingBottom: spacing.lg,
  },
  labelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  label: {
    flexShrink: 1,
  },
});

export default OrderTimeline;
