/**
 * OptionPicker — เลือกตัวเลือกเมนู (เช่น เนื้อสัตว์ บังคับเลือก 1 · เพิ่มไข่ดาว ไม่บังคับ)
 *
 * - single: แตะเลือก 1 อย่าง (บังคับ = เลือกแล้วยกเลิกไม่ได้ ต้องเปลี่ยนเป็นอย่างอื่น)
 * - multi: แตะเลือก/เอาออก ไม่เกิน max_select (max 1 = สลับแทนกันได้)
 * - ตัวเลือกที่หมดชั่วคราว (is_available=false) แสดงแต่กดไม่ได้
 * - กลุ่มที่มีรูป ≥ 2 ตัวเลือก → การ์ดรูป 2 คอลัมน์ · นอกนั้นเป็นแถว
 *
 * ราคาที่แสดงเป็นพรีวิวเท่านั้น (server คำนวณจริงตอนใส่ตะกร้า/สั่งซื้อ)
 */

import React from 'react';
import { StyleSheet, Text, View, useWindowDimensions, type LayoutChangeEvent } from 'react-native';
import { Image } from 'expo-image';
import { Card3D, Pill, resultHaptic, selectionHaptic } from '@/components/ui';
import { useTheme, radii, spacing, typography } from '@/theme';
import { fmImageUri, type FmOption, type FmOptionGroup } from '@/services/api/taladsodApi';
import { optionDeltaText } from './helpers';
import type { OptionSelection } from './optionSelection';

export {
  idsToSelection,
  sanitizeSelection,
  selectedOptionImage,
  selectionDelta,
  selectionToIds,
  validateSelection,
} from './optionSelection';
export type { OptionSelection, SelectionProblem } from './optionSelection';

export interface OptionPickerProps {
  groups: FmOptionGroup[];
  value: OptionSelection;
  onChange: (next: OptionSelection) => void;
  /** กลุ่มที่ยังเลือกไม่ครบ (ไฮไลต์สีเตือน) */
  highlightGroupId?: number | null;
  /** ตำแหน่ง y ของแต่ละกลุ่ม (ใช้เลื่อนไปหากลุ่มที่ยังไม่เลือก) */
  onGroupLayout?: (groupId: number, y: number) => void;
  disabled?: boolean;
}

export const OptionPicker: React.FC<OptionPickerProps> = ({
  groups,
  value,
  onChange,
  highlightGroupId,
  onGroupLayout,
  disabled = false,
}) => {
  const { colors } = useTheme();
  const { width } = useWindowDimensions();
  const cardWidth = Math.floor((Math.min(width, 640) - spacing.screen * 2 - spacing.md) / 2);

  const toggle = (group: FmOptionGroup, option: FmOption) => {
    if (disabled || !option.is_available) return;
    const current = value[group.id] || [];
    const selected = current.includes(option.id);
    let next: number[];

    if (group.selection_type === 'single') {
      if (selected) {
        if (group.is_required) return;
        next = [];
      } else {
        next = [option.id];
      }
    } else if (selected) {
      next = current.filter((id) => id !== option.id);
    } else if (group.max_select === 1) {
      next = [option.id];
    } else if (group.max_select !== null && current.length >= group.max_select) {
      resultHaptic('warning');
      return;
    } else {
      next = [...current, option.id];
    }

    selectionHaptic();
    const out = { ...value };
    if (next.length) out[group.id] = next;
    else delete out[group.id];
    onChange(out);
  };

  return (
    <View style={styles.root}>
      {groups.map((group) => {
        const chosen = new Set(value[group.id] || []);
        const withImages = group.options.length >= 2 && group.options.some((o) => !!o.image_url);
        const warn = highlightGroupId === group.id;
        const isSingle = group.selection_type === 'single';

        return (
          <View
            key={group.id}
            onLayout={(e: LayoutChangeEvent) => onGroupLayout?.(group.id, e.nativeEvent.layout.y)}
            style={[
              styles.group,
              warn && { borderColor: colors.warning, backgroundColor: colors.warningSoft },
              !warn && { borderColor: 'transparent' },
            ]}
          >
            <View style={styles.groupHead}>
              <View style={styles.flex}>
                <Text accessibilityRole="header" style={[typography.h3, { color: colors.textStrong }]}>
                  {group.name}
                </Text>
                {!!group.rule_label && (
                  <Text style={[typography.caption, { color: warn ? colors.warning : colors.textMuted }]}>{group.rule_label}</Text>
                )}
              </View>
              {group.is_required ? (
                chosen.size > 0 ? (
                  <Pill label="เลือกแล้ว" tone="success" icon="✓" />
                ) : (
                  <Pill label="ต้องเลือก" tone={warn ? 'danger' : 'warning'} />
                )
              ) : (
                <Pill label="ไม่บังคับ" tone="neutral" />
              )}
            </View>

            <View style={withImages ? styles.grid : styles.list}>
              {group.options.map((option) => {
                const selected = chosen.has(option.id);
                const soldOut = !option.is_available;
                const uri = fmImageUri(option.image_url);
                const deltaText = optionDeltaText(option.price_delta);
                const a11y = `${option.name} ${option.price_delta > 0 ? `เพิ่ม ${option.price_delta} บาท` : 'ไม่บวกเพิ่ม'}${
                  soldOut ? ' หมดชั่วคราว' : ''
                }`;
                const indicator = (
                  <View
                    style={[
                      isSingle ? styles.radio : styles.check,
                      {
                        borderColor: selected ? colors.goldDeep : colors.border,
                        backgroundColor: selected ? colors.gold : colors.card,
                      },
                    ]}
                  >
                    {selected && <Text style={[styles.checkMark, { color: colors.textOnGold }]}>✓</Text>}
                  </View>
                );

                if (withImages) {
                  return (
                    <Card3D
                      key={option.id}
                      onPress={() => toggle(group, option)}
                      disabled={disabled || soldOut}
                      gradientBorder={selected}
                      shadow="sm"
                      padding={0}
                      radius={radii.lg}
                      haptic={false}
                      style={{ width: cardWidth }}
                      accessibilityLabel={a11y}
                      accessibilityRole={isSingle ? 'radio' : 'checkbox'}
                      accessibilityState={{ checked: selected }}
                    >
                      <View style={[styles.optImageWrap, { height: cardWidth * 0.72, backgroundColor: colors.inset }]}>
                        {uri ? (
                          <Image
                            source={{ uri }}
                            style={[StyleSheet.absoluteFill, soldOut && styles.dim]}
                            contentFit="cover"
                            transition={140}
                          />
                        ) : (
                          <Text style={styles.optEmoji}>🍽️</Text>
                        )}
                        <View style={styles.optIndicator}>{indicator}</View>
                        {soldOut && (
                          <View style={[styles.soldOut, { backgroundColor: colors.overlay }]}>
                            <Text style={[typography.micro, { color: colors.textOnAccent }]}>หมดชั่วคราว</Text>
                          </View>
                        )}
                      </View>
                      <View style={styles.optBody}>
                        <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
                          {option.name}
                        </Text>
                        <Text
                          style={[
                            typography.caption,
                            { color: option.price_delta > 0 ? colors.goldDeep : colors.textMuted, fontWeight: '700' },
                          ]}
                        >
                          {deltaText}
                        </Text>
                      </View>
                    </Card3D>
                  );
                }

                return (
                  <Card3D
                    key={option.id}
                    onPress={() => toggle(group, option)}
                    disabled={disabled || soldOut}
                    gradientBorder={selected}
                    variant={selected ? 'raised' : 'flat'}
                    shadow="sm"
                    padding={spacing.md}
                    radius={radii.md}
                    haptic={false}
                    accessibilityLabel={a11y}
                    accessibilityRole={isSingle ? 'radio' : 'checkbox'}
                    accessibilityState={{ checked: selected }}
                  >
                    <View style={styles.optRow}>
                      {indicator}
                      {uri && (
                        <Image
                          source={{ uri }}
                          style={[styles.optThumb, { backgroundColor: colors.inset }, soldOut && styles.dim]}
                          contentFit="cover"
                          transition={120}
                        />
                      )}
                      <Text numberOfLines={1} style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]}>
                        {option.name}
                        {soldOut ? '  · หมดชั่วคราว' : ''}
                      </Text>
                      <Text
                        style={[
                          typography.bodySm,
                          { color: option.price_delta > 0 ? colors.goldDeep : colors.textMuted, fontWeight: '700' },
                        ]}
                      >
                        {deltaText}
                      </Text>
                    </View>
                  </Card3D>
                );
              })}
            </View>
          </View>
        );
      })}
    </View>
  );
};

const styles = StyleSheet.create({
  root: {
    gap: spacing.md,
  },
  flex: {
    flex: 1,
  },
  group: {
    borderWidth: 1.5,
    borderRadius: radii.lg,
    padding: spacing.xs,
    marginHorizontal: -spacing.xs,
  },
  groupHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
  },
  list: {
    gap: spacing.sm,
  },
  optImageWrap: {
    borderTopLeftRadius: radii.lg,
    borderTopRightRadius: radii.lg,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  optEmoji: {
    fontSize: 32,
  },
  optIndicator: {
    position: 'absolute',
    top: spacing.sm,
    right: spacing.sm,
  },
  soldOut: {
    position: 'absolute',
    left: spacing.sm,
    bottom: spacing.sm,
    borderRadius: radii.pill,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  optBody: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  optRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  optThumb: {
    width: 44,
    height: 44,
    borderRadius: radii.sm,
  },
  radio: {
    width: 24,
    height: 24,
    borderRadius: 12,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  check: {
    width: 24,
    height: 24,
    borderRadius: 7,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkMark: {
    fontSize: 13,
    fontWeight: '900',
    lineHeight: 16,
  },
  dim: {
    opacity: 0.45,
  },
});
