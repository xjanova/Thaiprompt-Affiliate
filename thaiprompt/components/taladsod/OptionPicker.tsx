/**
 * OptionPicker — เลือกตัวเลือกเมนู (เช่น เนื้อสัตว์ บังคับเลือก 1 · เพิ่มไข่ดาว ไม่บังคับ)
 *
 * หน้าตา (ธีมรอยัล): แต่ละกลุ่มเป็นการ์ดขาว หัวกลุ่ม + ป้ายต้องเลือก/เลือกแล้ว/ไม่บังคับ
 * ตัวเลือกเป็นแถว: วงเลือก (radio/checkbox น้ำเงิน) · รูปย่อ (ถ้ามี) · ชื่อ · ราคาเพิ่ม
 *
 * - single: แตะเลือก 1 อย่าง (บังคับ = เลือกแล้วยกเลิกไม่ได้ ต้องเปลี่ยนเป็นอย่างอื่น)
 * - multi: แตะเลือก/เอาออก ไม่เกิน max_select (max 1 = สลับแทนกันได้)
 * - ตัวเลือกที่หมดชั่วคราว (is_available=false) แสดงแต่กดไม่ได้
 *
 * ราคาที่แสดงเป็นพรีวิวเท่านั้น (server คำนวณจริงตอนใส่ตะกร้า/สั่งซื้อ)
 */

import React from 'react';
import { Pressable, StyleSheet, View, type LayoutChangeEvent } from 'react-native';
import { Image } from 'expo-image';
import { Text } from '@/components/ui/Text';
import { Icon, Pill, resultHaptic, selectionHaptic } from '@/components/ui';
import { useTheme, spacing, typography } from '@/theme';
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
        const warn = highlightGroupId === group.id;
        const isSingle = group.selection_type === 'single';

        return (
          <View
            key={group.id}
            onLayout={(e: LayoutChangeEvent) => onGroupLayout?.(group.id, e.nativeEvent.layout.y)}
            style={[
              styles.group,
              {
                backgroundColor: warn ? colors.warningSoft : colors.card,
                borderColor: warn ? colors.warning : colors.border,
              },
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
                  <Pill label="เลือกแล้ว" tone="success" icon="check" />
                ) : (
                  <Pill label="ต้องเลือก" tone={warn ? 'danger' : 'warning'} />
                )
              ) : (
                <Pill label="ไม่บังคับ" tone="neutral" />
              )}
            </View>

            {group.options.map((option, index) => {
              const selected = chosen.has(option.id);
              const soldOut = !option.is_available;
              const uri = fmImageUri(option.image_url);
              const deltaText = optionDeltaText(option.price_delta);
              const a11y = `${option.name} ${option.price_delta > 0 ? `เพิ่ม ${option.price_delta} บาท` : 'ไม่บวกเพิ่ม'}${
                soldOut ? ' หมดชั่วคราว' : ''
              }`;
              const last = index === group.options.length - 1;

              return (
                <Pressable
                  key={option.id}
                  onPress={() => toggle(group, option)}
                  disabled={disabled || soldOut}
                  accessibilityLabel={a11y}
                  accessibilityRole={isSingle ? 'radio' : 'checkbox'}
                  accessibilityState={{ checked: selected, disabled: disabled || soldOut }}
                  style={({ pressed }) => [
                    styles.optRow,
                    !last && { borderBottomWidth: 1, borderBottomColor: colors.divider },
                    selected && { backgroundColor: colors.navySoft },
                    { opacity: soldOut || disabled ? 0.5 : pressed ? 0.75 : 1 },
                  ]}
                >
                  {/* วงเลือก */}
                  {isSingle ? (
                    <View
                      style={[
                        styles.radio,
                        { borderColor: selected ? colors.navy : colors.textFaint, borderWidth: selected ? 7 : 2 },
                      ]}
                    />
                  ) : (
                    <View
                      style={[
                        styles.check,
                        selected
                          ? { backgroundColor: colors.navy, borderColor: colors.navy }
                          : { backgroundColor: 'transparent', borderColor: colors.textFaint },
                      ]}
                    >
                      {selected && <Icon name="check" size={14} color={colors.card} weight="bold" />}
                    </View>
                  )}
                  {uri && (
                    <Image
                      source={{ uri }}
                      style={[styles.optThumb, { backgroundColor: colors.inset }]}
                      contentFit="cover"
                      transition={120}
                    />
                  )}
                  <View style={styles.flex}>
                    <Text
                      numberOfLines={1}
                      style={[typography.body, { color: colors.textStrong, fontWeight: selected ? '600' : '400' }]}
                    >
                      {option.name}
                    </Text>
                    {soldOut && <Text style={[typography.micro, { color: colors.danger }]}>หมดชั่วคราว</Text>}
                  </View>
                  <Text
                    style={[
                      typography.bodySm,
                      { color: option.price_delta > 0 ? colors.textStrong : colors.textMuted, fontWeight: option.price_delta > 0 ? '600' : '400' },
                    ]}
                  >
                    {deltaText}
                  </Text>
                </Pressable>
              );
            })}
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
    borderWidth: 1,
    borderRadius: 22,
    paddingHorizontal: spacing.md,
    paddingTop: spacing.md + 2,
    paddingBottom: spacing.xs,
  },
  groupHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.xs,
    marginBottom: spacing.xs,
  },
  optRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    minHeight: 56,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.sm,
    borderRadius: 14,
  },
  radio: {
    width: 22,
    height: 22,
    borderRadius: 11,
  },
  check: {
    width: 22,
    height: 22,
    borderRadius: 7,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  optThumb: {
    width: 42,
    height: 42,
    borderRadius: 12,
  },
});
