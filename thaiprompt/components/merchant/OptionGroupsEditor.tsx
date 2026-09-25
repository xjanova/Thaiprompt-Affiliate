/**
 * OptionGroupsEditor — แก้กลุ่มตัวเลือกของสินค้าแบบพื้นฐาน (เช่น "เลือกเนื้อสัตว์", "เพิ่มเติม")
 *
 * - กลุ่ม: ชื่อ · เลือกได้ 1 อย่าง / หลายอย่าง · บังคับเลือก · เลือกได้สูงสุด (หลายอย่าง)
 * - ตัวเลือก: ชื่อ · ราคาเพิ่ม (+฿) · มีขาย/หมด · ลบ
 * - เป็น controlled component: หน้าจอเก็บ draft แล้วส่งทั้งชุดด้วย saveFmOptionGroups()
 *
 * ร่าง/แปลงข้อมูลอยู่ใน optionDraft.ts (ไม่มี UI — ทดสอบได้)
 */

import React from 'react';
import { Alert, Pressable, StyleSheet, Switch, Text, TextInput, View } from 'react-native';
import { Button3D, Card3D, Chip, formatBaht } from '@/components/ui';
import { FM_LIMITS } from '@/services/api/fmLimits';
import { useTheme, radii, spacing, typography } from '@/theme';
import { newDraftGroup, newDraftOption, parsePrice, type DraftGroup, type DraftOption } from './optionDraft';

export { toDraft, fromDraft, newDraftGroup, newDraftOption, parsePrice } from './optionDraft';
export type { DraftGroup, DraftOption } from './optionDraft';

// =====================================================
// UI
// =====================================================

export interface OptionGroupsEditorProps {
  value: DraftGroup[];
  onChange: (next: DraftGroup[]) => void;
  basePrice: number;
  disabled?: boolean;
}

export const OptionGroupsEditor: React.FC<OptionGroupsEditorProps> = ({ value, onChange, basePrice, disabled = false }) => {
  const { colors } = useTheme();

  const updateGroup = (key: string, patch: Partial<DraftGroup>) =>
    onChange(value.map((g) => (g.key === key ? { ...g, ...patch } : g)));

  const updateOption = (groupKey: string, optionKey: string, patch: Partial<DraftOption>) =>
    onChange(
      value.map((g) =>
        g.key === groupKey ? { ...g, options: g.options.map((o) => (o.key === optionKey ? { ...o, ...patch } : o)) } : g
      )
    );

  const removeGroup = (group: DraftGroup, index: number) => {
    Alert.alert('ลบกลุ่มตัวเลือก?', `ลบ "${group.name.trim() || `กลุ่มที่ ${index + 1}`}" และตัวเลือกทั้งหมดในกลุ่ม (มีผลเมื่อกดบันทึก)`, [
      { text: 'ไม่ลบ', style: 'cancel' },
      { text: 'ลบกลุ่ม', style: 'destructive', onPress: () => onChange(value.filter((g) => g.key !== group.key)) },
    ]);
  };

  const removeOption = (group: DraftGroup, option: DraftOption) => {
    if (group.options.length <= 1) {
      Alert.alert('ลบไม่ได้', 'กลุ่มต้องมีตัวเลือกอย่างน้อย 1 อย่าง ถ้าไม่ใช้กลุ่มนี้แล้วให้ลบทั้งกลุ่มแทนนะ');
      return;
    }
    onChange(value.map((g) => (g.key === group.key ? { ...g, options: g.options.filter((o) => o.key !== option.key) } : g)));
  };

  const inputStyle = [
    typography.body,
    styles.input,
    { backgroundColor: colors.inset, color: colors.textStrong, borderColor: colors.border },
  ];

  return (
    <View>
      {value.length === 0 && (
        <Card3D variant="flat" padding={spacing.lg} style={styles.group}>
          <Text style={[typography.bodySm, styles.center, { color: colors.textMuted }]}>
            ยังไม่มีตัวเลือก — เพิ่มกลุ่มเช่น "เลือกเนื้อสัตว์" (หมู/ไก่/กุ้ง +20) หรือ "เพิ่มเติม" (ไข่ดาว +10)
          </Text>
        </Card3D>
      )}

      {value.map((group, gi) => {
        const available = group.options.filter((o) => o.is_available);
        const deltas = available.map((o) => parsePrice(o.priceText) ?? 0);
        const cheapest = deltas.length ? Math.min(...deltas) : 0;
        return (
          <Card3D key={group.key} padding={spacing.md} style={styles.group}>
            <View style={styles.row}>
              <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>กลุ่มที่ {gi + 1}</Text>
              <Button3D
                title="ลบกลุ่ม"
                variant="ghost"
                size="sm"
                disabled={disabled}
                onPress={() => removeGroup(group, gi)}
                textStyle={{ color: colors.danger }}
              />
            </View>

            <TextInput
              value={group.name}
              onChangeText={(text) => updateGroup(group.key, { name: text })}
              placeholder="ชื่อกลุ่ม เช่น เลือกเนื้อสัตว์"
              placeholderTextColor={colors.textFaint}
              maxLength={FM_LIMITS.NAME_MAX}
              editable={!disabled}
              accessibilityLabel={`ชื่อกลุ่มที่ ${gi + 1}`}
              style={[inputStyle, styles.gapTop]}
            />

            <View style={[styles.chips, styles.gapTop]}>
              <Chip
                label="เลือกได้ 1 อย่าง"
                size="sm"
                selected={group.selection_type === 'single'}
                disabled={disabled}
                onPress={() => updateGroup(group.key, { selection_type: 'single' })}
              />
              <Chip
                label="เลือกได้หลายอย่าง"
                size="sm"
                selected={group.selection_type === 'multi'}
                disabled={disabled}
                onPress={() => updateGroup(group.key, { selection_type: 'multi', is_required: group.is_required })}
              />
            </View>

            <View style={[styles.row, styles.gapTop]}>
              <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>ลูกค้าต้องเลือก (บังคับ)</Text>
              <Switch
                value={group.is_required}
                onValueChange={(next) => updateGroup(group.key, { is_required: next })}
                disabled={disabled}
                trackColor={{ false: colors.border, true: colors.gold }}
                thumbColor={colors.card}
                accessibilityLabel={`กลุ่มที่ ${gi + 1} บังคับเลือก`}
              />
            </View>

            {group.selection_type === 'multi' && (
              <View style={[styles.row, styles.gapTop]}>
                <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>เลือกได้สูงสุด (ว่าง = ไม่จำกัด)</Text>
                <TextInput
                  value={group.maxText}
                  onChangeText={(text) => updateGroup(group.key, { maxText: text.replace(/[^0-9]/g, '').slice(0, 2) })}
                  keyboardType="number-pad"
                  placeholder="∞"
                  placeholderTextColor={colors.textFaint}
                  editable={!disabled}
                  accessibilityLabel={`กลุ่มที่ ${gi + 1} เลือกได้สูงสุด`}
                  style={[inputStyle, styles.smallInput]}
                />
              </View>
            )}

            {/* ---------- ตัวเลือก ---------- */}
            <Text style={[typography.caption, styles.optionsLabel, { color: colors.textMuted }]}>
              ตัวเลือก · ราคาเพิ่มจากราคาปกติ {formatBaht(basePrice)}
            </Text>
            {group.options.map((option, oi) => (
              <View
                key={option.key}
                style={[styles.optionRow, !option.is_available && styles.optionOff, { borderColor: colors.divider }]}
              >
                <TextInput
                  value={option.name}
                  onChangeText={(text) => updateOption(group.key, option.key, { name: text })}
                  placeholder={`ตัวเลือก ${oi + 1}`}
                  placeholderTextColor={colors.textFaint}
                  maxLength={FM_LIMITS.NAME_MAX}
                  editable={!disabled}
                  accessibilityLabel={`ชื่อตัวเลือก ${oi + 1} ของกลุ่มที่ ${gi + 1}`}
                  style={[inputStyle, styles.flex]}
                />
                <View style={styles.priceBox}>
                  <Text style={[typography.caption, styles.plus, { color: colors.textMuted }]}>+฿</Text>
                  <TextInput
                    value={option.priceText}
                    onChangeText={(text) => updateOption(group.key, option.key, { priceText: text.replace(/[^0-9.]/g, '').slice(0, 9) })}
                    keyboardType="decimal-pad"
                    placeholder="0"
                    placeholderTextColor={colors.textFaint}
                    editable={!disabled}
                    accessibilityLabel={`ราคาเพิ่มของตัวเลือก ${oi + 1}`}
                    style={[
                      inputStyle,
                      styles.priceInput,
                      parsePrice(option.priceText) === null && { borderColor: colors.danger },
                    ]}
                  />
                </View>
                <Switch
                  value={option.is_available}
                  onValueChange={(next) => updateOption(group.key, option.key, { is_available: next })}
                  disabled={disabled}
                  trackColor={{ false: colors.border, true: colors.success }}
                  thumbColor={colors.card}
                  accessibilityLabel={`${option.name || `ตัวเลือก ${oi + 1}`} ${option.is_available ? 'มีขาย' : 'หมด'}`}
                />
                <Pressable
                  onPress={() => removeOption(group, option)}
                  disabled={disabled}
                  hitSlop={8}
                  accessibilityRole="button"
                  accessibilityLabel={`ลบตัวเลือก ${option.name || oi + 1}`}
                  style={({ pressed }) => [styles.remove, { backgroundColor: colors.dangerSoft, opacity: pressed ? 0.6 : 1 }]}
                >
                  <Text style={[typography.bodyStrong, { color: colors.danger }]}>✕</Text>
                </Pressable>
              </View>
            ))}
            <Text style={[typography.micro, styles.gapTop, { color: colors.textFaint }]}>
              สวิตช์เขียว = มีขาย · ปิดสวิตช์เมื่อของหมด (ลูกค้าจะเห็นเป็นตัวเลือกที่กดไม่ได้)
              {available.length > 0 && group.is_required ? `\nราคาเริ่มต้นเมื่อรวมกลุ่มนี้ ${formatBaht(basePrice + cheapest)}` : ''}
            </Text>
            {group.options.length < FM_LIMITS.MAX_OPTIONS_PER_GROUP && (
              <Button3D
                title="เพิ่มตัวเลือก"
                icon="➕"
                variant="secondary"
                size="sm"
                disabled={disabled}
                onPress={() => updateGroup(group.key, { options: [...group.options, newDraftOption()] })}
                style={styles.addOption}
              />
            )}
          </Card3D>
        );
      })}

      {value.length < FM_LIMITS.MAX_GROUPS && (
        <Button3D
          title="เพิ่มกลุ่มตัวเลือก"
          icon="🧩"
          variant="secondary"
          fullWidth
          disabled={disabled}
          onPress={() => onChange([...value, newDraftGroup()])}
        />
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    textAlign: 'center',
  },
  group: {
    marginBottom: spacing.md,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  gapTop: {
    marginTop: spacing.sm,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
  },
  input: {
    minHeight: 44,
    borderRadius: radii.sm,
    borderWidth: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
  },
  smallInput: {
    width: 64,
    textAlign: 'center',
  },
  optionsLabel: {
    marginTop: spacing.md,
    marginBottom: spacing.xs,
  },
  optionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    paddingVertical: spacing.xs,
  },
  optionOff: {
    opacity: 0.6,
  },
  priceBox: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  plus: {
    marginRight: 2,
  },
  priceInput: {
    width: 64,
    textAlign: 'right',
  },
  remove: {
    width: 30,
    height: 30,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  addOption: {
    marginTop: spacing.sm,
    alignSelf: 'flex-start',
  },
});

export default OptionGroupsEditor;
