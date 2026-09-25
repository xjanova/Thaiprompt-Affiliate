/**
 * StatusTimeline — ไทม์ไลน์สถานะแนวตั้ง (เหรียญไอคอน + เส้นเชื่อม) ธีมรอยัล
 *
 * step.state: done = ผ่านแล้ว (เหรียญเขียว + เครื่องหมายถูก) · current = กำลังอยู่ขั้นนี้ (เหรียญทองเรือง + ไอคอนขั้น)
 *             todo = ยังไม่ถึง (วงจาง + ไอคอนขั้น) · failed = ยกเลิก/ล้มเหลว (เหรียญแดง + กากบาท)
 * step.icon: ชื่อไอคอน — หน้าเก่าที่ยังส่งอีโมจิจะถูกแปลงเป็นไอคอนเส้นให้ (IconSlot)
 */

import React from 'react';
import { StyleSheet, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import { Icon, IconSlot } from '@/components/ui';
import { useTheme, glowStyle, spacing, typography } from '@/theme';

export interface TimelineStep {
  key: string;
  label: string;
  /** คำอธิบาย/เวลา */
  caption?: string;
  /** ชื่อไอคอนของขั้น (อีโมจิเดิมแปลงให้) */
  icon?: string;
  state: 'done' | 'current' | 'todo' | 'failed';
}

const DOT = 28;
const DOT_CURRENT = 32;

export const StatusTimeline: React.FC<{ steps: TimelineStep[] }> = ({ steps }) => {
  const { colors, gradients } = useTheme();

  return (
    <View accessibilityRole="list">
      {steps.map((step, index) => {
        const last = index === steps.length - 1;
        const size = step.state === 'current' ? DOT_CURRENT : DOT;
        // เส้นเชื่อมเป็นสีเขียวเมื่อขั้นนี้ผ่านแล้ว
        const lineColor = step.state === 'done' ? colors.success : colors.border;
        const labelColor = step.state === 'todo' ? colors.textFaint : step.state === 'failed' ? colors.danger : colors.textStrong;

        const renderDot = () => {
          // เหรียญเขียว/แดงใช้ไล่เฉดเข้ม → ไอคอนขาวอ่านออกทั้งโหมดสว่างและมืด
          if (step.state === 'done') {
            return (
              <LinearGradient
                colors={gradients.success}
                start={{ x: 0, y: 0 }}
                end={{ x: 0, y: 1 }}
                style={[styles.dot, { width: size, height: size, borderRadius: size / 2 }]}
              >
                <Icon name="check" size={14} color={colors.textOnAccent} weight="bold" />
              </LinearGradient>
            );
          }
          if (step.state === 'failed') {
            return (
              <LinearGradient
                colors={gradients.danger}
                start={{ x: 0, y: 0 }}
                end={{ x: 0, y: 1 }}
                style={[styles.dot, { width: size, height: size, borderRadius: size / 2 }]}
              >
                <Icon name="x" size={14} color={colors.textOnAccent} weight="bold" />
              </LinearGradient>
            );
          }
          if (step.state === 'current') {
            return (
              <LinearGradient
                colors={gradients.primary}
                start={{ x: 0, y: 0 }}
                end={{ x: 0, y: 1 }}
                style={[styles.dot, { width: size, height: size, borderRadius: size / 2 }, glowStyle(colors.gold)]}
              >
                <IconSlot icon={step.icon || 'clock'} size={16} color={colors.textOnGold} weight="fill" />
              </LinearGradient>
            );
          }
          return (
            <View
              style={[
                styles.dot,
                styles.todo,
                { width: size, height: size, borderRadius: size / 2, backgroundColor: colors.inset, borderColor: colors.border },
              ]}
            >
              <IconSlot icon={step.icon} size={14} color={colors.textFaint} />
            </View>
          );
        };

        return (
          <View key={step.key} style={styles.row} accessibilityLabel={`${step.label} ${step.caption || ''}`}>
            <View style={styles.rail}>
              {renderDot()}
              {!last && <View style={[styles.line, { backgroundColor: lineColor }]} />}
            </View>
            <View style={[styles.text, { paddingTop: step.state === 'current' ? 5 : 3 }, !last && styles.textGap]}>
              <Text style={[step.state === 'current' ? typography.bodyStrong : typography.bodySm, { color: labelColor }]}>
                {step.label}
              </Text>
              {!!step.caption && (
                <Text style={[typography.caption, { color: step.state === 'todo' ? colors.textFaint : colors.textMuted }]}>
                  {step.caption}
                </Text>
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
    width: DOT_CURRENT,
  },
  dot: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  todo: {
    borderWidth: 1.5,
  },
  line: {
    width: 2,
    flex: 1,
    minHeight: 18,
    marginVertical: 3,
    borderRadius: 1,
  },
  text: {
    flex: 1,
  },
  textGap: {
    paddingBottom: spacing.lg,
  },
});

export default StatusTimeline;
