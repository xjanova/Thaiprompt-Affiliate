/**
 * ListingDetailsForm — ฟอร์มข้อมูลสินค้าตลาดสด (ลงขายใหม่ / แก้ไข) ตรงกับฟอร์มเว็บ
 *
 * ช่อง: ชื่อ* · รายละเอียด · หมวดหมู่* (หมวดหลัก → หมวดย่อยถ้ามี) · ความสด · อินทรีย์
 *       ราคาขาย* · ราคาก่อนลด (ต้องมากกว่าราคาขาย) · หน่วยขาย* · ทำตามสั่ง/นับสต็อก + จำนวน
 *       เปิดขาย (เฉพาะแก้ไข) · เงินคืนให้ลูกค้า (ถ้าร้านตั้งได้)
 * ตรวจในเครื่องด้วยกฎเดียวกับ server — server ตรวจซ้ำอีกชั้น
 *
 * "ร้านรับจริง" บนฟอร์มเป็นแค่ตัวอย่างจากอัตรา GP ตอนนี้ — ยอดจริงคิดที่ server ตอนลูกค้าสั่ง
 * หมวดหมู่จาก server มีอีโมจิ → ไม่แสดง ใช้ชื่ออย่างเดียว (กติกาเหล็กข้อ 1 ของ DESIGN.md)
 */

import React, { useMemo, useRef } from 'react';
import { StyleSheet, Switch, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Card3D, Chip, Icon, formatBaht, selectionHaptic } from '@/components/ui';
import { Field } from '@/components/shop';
import type {
  FmCategoryOption,
  FmListingDetailsBody,
  FmListingForm,
  FmOwnerListingFull,
} from '@/services/api/taladsodSellerManageApi';
import { useTheme, radii, spacing, typography } from '@/theme';
import { IconTile } from './MerchantUi';

export const LISTING_LIMITS = {
  TITLE_MAX: 200,
  DESCRIPTION_MAX: 2000,
  UNIT_MAX: 50,
  PRICE_MIN: 1,
  PRICE_MAX: 1_000_000,
  QTY_MAX: 100_000,
} as const;

export interface ListingDraft {
  title: string;
  description: string;
  /** หมวดที่เลือกจริง (หมวดหลักหรือหมวดย่อย) */
  categoryId: number | null;
  priceText: string;
  compareText: string;
  unit: string;
  trackStock: boolean;
  qtyText: string;
  isOrganic: boolean;
  freshness: string | null;
  cashbackText: string;
  isAvailable: boolean;
}

export type ListingDraftErrors = Partial<Record<keyof ListingDraft, string>>;

export const emptyListingDraft = (): ListingDraft => ({
  title: '',
  description: '',
  categoryId: null,
  priceText: '',
  compareText: '',
  unit: 'จาน',
  // ฟอร์มเว็บเริ่มที่ "ทำตามสั่ง" (เหมาะกับอาหารปรุงสด)
  trackStock: false,
  qtyText: '',
  isOrganic: false,
  freshness: null,
  cashbackText: '',
  isAvailable: true,
});

const moneyText = (value: number | null | undefined): string => {
  if (value === null || value === undefined || !Number.isFinite(value) || value <= 0) return '';
  return Number.isInteger(value) ? String(value) : value.toFixed(2);
};

export const listingDraftFrom = (l: FmOwnerListingFull): ListingDraft => ({
  title: l.title,
  description: l.description || '',
  categoryId: l.category_id,
  priceText: moneyText(l.price),
  compareText: moneyText(l.compare_at_price),
  unit: l.unit || '',
  trackStock: l.track_stock,
  qtyText: l.track_stock ? String(Math.max(0, Math.floor(l.quantity_available))) : '',
  isOrganic: l.is_organic,
  freshness: l.freshness_level,
  cashbackText: l.cashback_percentage > 0 ? String(l.cashback_percentage) : '',
  isAvailable: l.is_available,
});

export const serializeListingDraft = (d: ListingDraft): string =>
  JSON.stringify([
    d.title.trim(),
    d.description.trim(),
    d.categoryId,
    d.priceText.trim(),
    d.compareText.trim(),
    d.unit.trim(),
    d.trackStock,
    d.trackStock ? d.qtyText.trim() : '',
    d.isOrganic,
    d.freshness,
    d.cashbackText.trim(),
    d.isAvailable,
  ]);

/** ราคาเป็นตัวเลข ทศนิยมไม่เกิน 2 ตำแหน่ง (null = รูปแบบผิด) */
export const parseMoney = (text: string): number | null => {
  const clean = text.replace(/[,\s฿]/g, '');
  if (!/^\d+(\.\d{1,2})?$/.test(clean)) return null;
  const n = Number(clean);
  return Number.isFinite(n) ? n : null;
};

const parseCount = (text: string): number | null => {
  const clean = text.trim();
  if (!/^\d+$/.test(clean)) return null;
  const n = Number(clean);
  return Number.isSafeInteger(n) ? n : null;
};

export interface ListingValidateOptions {
  mode: 'create' | 'edit';
  form: FmListingForm | null;
}

/** ตรวจตามกฎเดียวกับ server — คืน {} = ผ่าน */
export const validateListingDraft = (d: ListingDraft, { mode, form }: ListingValidateOptions): ListingDraftErrors => {
  const e: ListingDraftErrors = {};
  const title = d.title.trim();
  if (!title) e.title = 'กรอกชื่อสินค้า/เมนู';
  else if (title.length > LISTING_LIMITS.TITLE_MAX) e.title = `ชื่อยาวได้ไม่เกิน ${LISTING_LIMITS.TITLE_MAX} ตัวอักษร`;
  if (d.description.trim().length > LISTING_LIMITS.DESCRIPTION_MAX) {
    e.description = `รายละเอียดยาวได้ไม่เกิน ${LISTING_LIMITS.DESCRIPTION_MAX.toLocaleString('th-TH')} ตัวอักษร`;
  }
  if (!d.categoryId) e.categoryId = 'เลือกหมวดหมู่';

  const price = parseMoney(d.priceText);
  if (price === null || price < LISTING_LIMITS.PRICE_MIN || price > LISTING_LIMITS.PRICE_MAX) {
    e.priceText = 'ราคาต้องเป็นตัวเลข 1 – 1,000,000 บาท';
  }
  if (d.compareText.trim() !== '') {
    const compare = parseMoney(d.compareText);
    if (compare === null) e.compareText = 'ราคาก่อนลดต้องเป็นตัวเลข';
    else if (price !== null && compare <= price) e.compareText = 'ราคาก่อนลดต้องมากกว่าราคาขาย';
  }

  const unit = d.unit.trim();
  if (!unit) e.unit = 'กรอกหน่วยขาย เช่น จาน ถุง กก.';
  else if (unit.length > LISTING_LIMITS.UNIT_MAX) e.unit = `หน่วยขายยาวได้ไม่เกิน ${LISTING_LIMITS.UNIT_MAX} ตัวอักษร`;

  if (d.trackStock) {
    const qty = parseCount(d.qtyText);
    const min = mode === 'create' ? 1 : 0;
    if (qty === null) e.qtyText = 'กรอกจำนวนที่มีขาย';
    else if (qty < min) e.qtyText = 'จำนวนสินค้าต้องมีอย่างน้อย 1';
    else if (qty > LISTING_LIMITS.QTY_MAX) e.qtyText = `จำนวนต้องไม่เกิน ${LISTING_LIMITS.QTY_MAX.toLocaleString('th-TH')}`;
  }

  if (d.cashbackText.trim() !== '') {
    const cashback = parseMoney(d.cashbackText);
    const max = form?.max_cashback_percent ?? 0;
    if (cashback === null) e.cashbackText = 'เงินคืนต้องเป็นตัวเลข';
    else if (cashback > max) e.cashbackText = `แคชแบ็คตั้งได้ไม่เกิน ${max}%`;
  }

  if (d.freshness && form && form.freshness_levels.length > 0 && !form.freshness_levels.includes(d.freshness)) {
    e.freshness = 'เลือกระดับความสดใหม่อีกครั้ง';
  }
  return e;
};

/** draft (ผ่านการตรวจแล้ว) → ช่องข้อมูลทั้งหมด */
export const listingBodyFromDraft = (d: ListingDraft, mode: 'create' | 'edit'): FmListingDetailsBody => {
  const compare = d.compareText.trim() ? parseMoney(d.compareText) : null;
  const cashback = d.cashbackText.trim() ? parseMoney(d.cashbackText) : null;
  const body: FmListingDetailsBody = {
    title: d.title.trim(),
    description: d.description.trim() || null,
    category_id: d.categoryId ?? undefined,
    price: parseMoney(d.priceText) ?? undefined,
    compare_at_price: compare,
    unit: d.unit.trim(),
    track_stock: d.trackStock,
    is_organic: d.isOrganic,
    freshness_level: d.freshness,
    cashback_percentage: cashback ?? 0,
  };
  if (d.trackStock) body.quantity_available = parseCount(d.qtyText) ?? 0;
  if (mode === 'edit') body.is_available = d.isAvailable;
  return body;
};

/** ช่องที่เปลี่ยนจากค่าเดิม (แก้ไข) — ส่งเฉพาะช่องที่เปลี่ยน ลดการทับข้อมูลที่เครื่องอื่นแก้ */
export const listingPatch = (d: ListingDraft, base: ListingDraft): FmListingDetailsBody | null => {
  const next = listingBodyFromDraft(d, 'edit');
  const prev = listingBodyFromDraft(base, 'edit');
  const patch: FmListingDetailsBody = {};
  (Object.keys(next) as Array<keyof FmListingDetailsBody>).forEach((key) => {
    if (JSON.stringify(next[key]) !== JSON.stringify(prev[key])) {
      (patch as Record<string, unknown>)[key] = next[key];
    }
  });
  // เปลี่ยนราคาอย่างเดียว server เทียบกับราคาก่อนลดเดิมไม่ได้ → ส่งราคาก่อนลดคู่ไปด้วยเสมอเมื่อมี
  if (patch.price !== undefined && next.compare_at_price !== null && patch.compare_at_price === undefined) {
    patch.compare_at_price = next.compare_at_price;
  }
  // เปิดนับสต็อก = ต้องส่งจำนวนคู่ไปด้วย
  if (patch.track_stock === true && patch.quantity_available === undefined) {
    patch.quantity_available = next.quantity_available;
  }
  return Object.keys(patch).length > 0 ? patch : null;
};

/** error ราย field จาก server (422) → errors ของฟอร์ม */
export const listingErrorsFromServer = (errors: Record<string, string[]> | undefined): ListingDraftErrors => {
  const out: ListingDraftErrors = {};
  if (!errors) return out;
  const map: Record<string, keyof ListingDraft> = {
    title: 'title',
    description: 'description',
    category_id: 'categoryId',
    price: 'priceText',
    compare_at_price: 'compareText',
    unit: 'unit',
    quantity_available: 'qtyText',
    cashback_percentage: 'cashbackText',
    freshness_level: 'freshness',
  };
  Object.entries(map).forEach(([serverKey, key]) => {
    const msg = errors[serverKey]?.[0];
    if (msg) out[key] = msg;
  });
  return out;
};

// =====================================================
// UI
// =====================================================

export interface ListingDetailsFormProps {
  mode: 'create' | 'edit';
  value: ListingDraft;
  onChange: (next: ListingDraft) => void;
  errors: ListingDraftErrors;
  onClearError: (key: keyof ListingDraft) => void;
  form: FmListingForm;
  /** สินค้าถูกระงับ → เปิดขายเองไม่ได้ */
  suspended?: boolean;
  disabled?: boolean;
}

const ErrorText: React.FC<{ text?: string }> = ({ text }) => {
  const { colors } = useTheme();
  if (!text) return null;
  return (
    <View style={styles.errorRow} accessibilityRole="alert">
      <Icon name="warning-circle" size={14} color={colors.danger} weight="fill" />
      <Text style={[typography.caption, styles.flex, { color: colors.danger }]}>{text}</Text>
    </View>
  );
};

export const ListingDetailsForm: React.FC<ListingDetailsFormProps> = ({
  mode,
  value,
  onChange,
  errors,
  onClearError,
  form,
  suspended = false,
  disabled = false,
}) => {
  const { colors } = useTheme();
  const valueRef = useRef(value);
  valueRef.current = value;

  const set = <K extends keyof ListingDraft>(key: K, next: ListingDraft[K]) => {
    onChange({ ...valueRef.current, [key]: next });
    if (errors[key]) onClearError(key);
  };

  // ---------- หมวดหมู่: หมวดหลัก → หมวดย่อย ----------
  const { roots, childrenOf, byId } = useMemo(() => {
    const map = new Map<number, FmCategoryOption>();
    form.categories.forEach((c) => map.set(c.id, c));
    const rootList = form.categories.filter((c) => c.parent_id === null || !map.has(c.parent_id));
    const kids = new Map<number, FmCategoryOption[]>();
    form.categories.forEach((c) => {
      if (c.parent_id !== null && map.has(c.parent_id)) {
        kids.set(c.parent_id, [...(kids.get(c.parent_id) || []), c]);
      }
    });
    return { roots: rootList, childrenOf: kids, byId: map };
  }, [form.categories]);

  const selected = value.categoryId !== null ? byId.get(value.categoryId) : undefined;
  const selectedRootId = selected ? (selected.parent_id !== null && byId.has(selected.parent_id) ? selected.parent_id : selected.id) : null;
  const subList = selectedRootId !== null ? childrenOf.get(selectedRootId) || [] : [];
  // หมวดเดิมของสินค้าถูกปิดไปแล้ว (ไม่อยู่ในรายการ) → ให้เลือกใหม่
  const categoryMissing = value.categoryId !== null && !selected;

  // ---------- ตัวอย่าง GP ----------
  const price = parseMoney(value.priceText);
  const gpAmount = price !== null && !form.gp_free ? Math.round(price * form.gp_rate) / 100 : 0;
  const net = price !== null ? Math.round((price - gpAmount) * 100) / 100 : null;
  const unitLabel = value.unit.trim() || 'ชิ้น';

  return (
    <>
      {/* ---------- ข้อมูลสินค้า ---------- */}
      <Card3D padding={spacing.lg}>
        <View style={styles.head}>
          <IconTile icon="bowl-food" />
          <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>ข้อมูลสินค้า</Text>
        </View>
        <Field
          label="ชื่อสินค้า/เมนู"
          required
          value={value.title}
          onChangeText={(t) => set('title', t)}
          placeholder="เช่น ผัดกะเพราราดข้าว, ผักบุ้งจีนปลอดสาร"
          maxLength={LISTING_LIMITS.TITLE_MAX}
          editable={!disabled}
          error={errors.title}
        />
        <Field
          label="รายละเอียด"
          value={value.description}
          onChangeText={(t) => set('description', t)}
          placeholder="วัตถุดิบ ปริมาณ จุดเด่น เช่น ผัดไฟแรง หอมใบกะเพรา"
          maxLength={LISTING_LIMITS.DESCRIPTION_MAX}
          multiline
          editable={!disabled}
          error={errors.description}
        />

        <Text style={[typography.caption, styles.label, { color: colors.textMuted }]}>
          หมวดหมู่<Text style={{ color: colors.danger }}> *</Text>
        </Text>
        {roots.length === 0 ? (
          <Text style={[typography.bodySm, { color: colors.textMuted }]}>ยังไม่มีหมวดหมู่ให้เลือก ติดต่อทีมงานได้ที่หน้าช่วยเหลือ</Text>
        ) : (
          <View style={styles.chips}>
            {roots.map((c) => (
              <Chip
                key={c.id}
                label={c.name}
                size="sm"
                selected={selectedRootId === c.id}
                disabled={disabled}
                onPress={() => {
                  selectionHaptic();
                  set('categoryId', c.id);
                }}
              />
            ))}
          </View>
        )}
        {subList.length > 0 && (
          <>
            <Text style={[typography.caption, styles.subLabel, { color: colors.textFaint }]}>หมวดย่อย (ไม่บังคับ)</Text>
            <View style={styles.chips}>
              {subList.map((c) => (
                <Chip
                  key={c.id}
                  label={c.name}
                  size="sm"
                  tone="gold"
                  selected={value.categoryId === c.id}
                  disabled={disabled}
                  onPress={() => {
                    selectionHaptic();
                    set('categoryId', value.categoryId === c.id && selectedRootId !== null ? selectedRootId : c.id);
                  }}
                />
              ))}
            </View>
          </>
        )}
        <ErrorText text={errors.categoryId || (categoryMissing ? 'หมวดเดิมปิดไปแล้ว เลือกหมวดใหม่นะ' : undefined)} />

        {form.freshness_levels.length > 0 && (
          <>
            <Text style={[typography.caption, styles.label, { color: colors.textMuted }]}>ความสด</Text>
            <View style={styles.chips}>
              <Chip label="ไม่ระบุ" size="sm" selected={!value.freshness} disabled={disabled} onPress={() => set('freshness', null)} />
              {form.freshness_levels.map((level) => (
                <Chip
                  key={level}
                  label={level}
                  size="sm"
                  icon="leaf"
                  selected={value.freshness === level}
                  disabled={disabled}
                  onPress={() => set('freshness', level)}
                />
              ))}
            </View>
            <ErrorText text={errors.freshness} />
          </>
        )}

        <View style={[styles.switchRow, { borderTopColor: colors.divider }]}>
          <IconTile icon="plant" tone={value.isOrganic ? 'success' : 'neutral'} size={40} />
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>สินค้าอินทรีย์ / ปลอดสาร</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>ติดป้ายให้ลูกค้าเห็น</Text>
          </View>
          <Switch
            value={value.isOrganic}
            onValueChange={(v) => set('isOrganic', v)}
            disabled={disabled}
            trackColor={{ false: colors.border, true: colors.success }}
            thumbColor={colors.card}
            accessibilityLabel="สินค้าอินทรีย์"
          />
        </View>
      </Card3D>

      {/* ---------- ราคาและสต็อก ---------- */}
      <Card3D padding={spacing.lg} style={styles.gap}>
        <View style={styles.head}>
          <IconTile icon="tag" tone="gold" />
          <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>ราคาและสต็อก</Text>
        </View>
        <View style={styles.row}>
          <Field
            label="ราคาขาย (บาท)"
            required
            value={value.priceText}
            onChangeText={(t) => set('priceText', t.replace(/[^0-9.]/g, '').slice(0, 10))}
            keyboardType="decimal-pad"
            placeholder="เช่น 50"
            editable={!disabled}
            error={errors.priceText}
            containerStyle={styles.flex}
          />
          <Field
            label="ราคาก่อนลด (ไม่บังคับ)"
            value={value.compareText}
            onChangeText={(t) => set('compareText', t.replace(/[^0-9.]/g, '').slice(0, 10))}
            keyboardType="decimal-pad"
            placeholder="เช่น 60"
            editable={!disabled}
            error={errors.compareText}
            containerStyle={styles.flex}
          />
        </View>
        <Field
          label="หน่วยขาย"
          required
          value={value.unit}
          onChangeText={(t) => set('unit', t)}
          placeholder="เช่น จาน ถุง กก."
          maxLength={LISTING_LIMITS.UNIT_MAX}
          editable={!disabled}
          error={errors.unit}
        />
        {form.units.length > 0 && (
          <View style={[styles.chips, styles.unitChips]}>
            {form.units.map((u) => (
              <Chip key={u} label={u} size="sm" selected={value.unit.trim() === u} disabled={disabled} onPress={() => set('unit', u)} />
            ))}
          </View>
        )}

        {price !== null && price >= LISTING_LIMITS.PRICE_MIN && net !== null && (
          <View style={[styles.gpBox, { backgroundColor: form.gp_free ? colors.successSoft : colors.infoSoft }]}>
            <Icon name={form.gp_free ? 'gift' : 'calculator'} size={18} color={form.gp_free ? colors.success : colors.info} />
            <View style={styles.flex}>
              {form.gp_free ? (
                <Text style={[typography.bodySm, { color: colors.text }]}>
                  ฟรี GP ช่วงเปิดตัว — ร้านรับเต็ม {formatBaht(price, { decimals: 2 })} ต่อ{unitLabel}
                </Text>
              ) : (
                <Text style={[typography.bodySm, { color: colors.text }]}>
                  ราคา {formatBaht(price, { decimals: 2 })} − GP {form.gp_rate}% ({formatBaht(gpAmount, { decimals: 2 })}) ={' '}
                  <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ร้านรับจริงประมาณ {formatBaht(net, { decimals: 2 })}</Text> ต่อ{unitLabel}
                </Text>
              )}
              <Text style={[typography.caption, { color: colors.textMuted }]}>
                ราคาตัวเลือก (+฿) คิด GP อัตราเดียวกัน · ค่าส่งลูกค้าจ่ายให้ไรเดอร์ ไม่หักจากร้าน
              </Text>
            </View>
          </View>
        )}

        <Text style={[typography.caption, styles.label, { color: colors.textMuted }]}>วิธีขาย</Text>
        <View style={styles.choiceRow}>
          {([
            { key: false, title: 'ทำตามสั่ง', caption: 'ไม่จำกัดจำนวน เหมาะกับอาหารปรุงสด', icon: 'cooking-pot' as const },
            { key: true, title: 'นับสต็อก', caption: 'ของหมดแล้วปิดขายอัตโนมัติ', icon: 'package' as const },
          ]).map((opt) => {
            const on = value.trackStock === opt.key;
            return (
              <Card3D
                key={String(opt.key)}
                onPress={disabled ? undefined : () => set('trackStock', opt.key)}
                padding={spacing.md}
                radius={radii.lg}
                shadow="sm"
                variant={on ? 'raised' : 'inset'}
                gradientBorder={on}
                style={styles.flex}
                accessibilityRole="radio"
                accessibilityState={{ checked: on }}
                accessibilityLabel={`${opt.title} ${opt.caption}`}
              >
                <View style={styles.choiceTop}>
                  <Icon name={opt.icon} size={20} color={on ? colors.goldDeep : colors.textMuted} />
                  {on && <Icon name="check-circle" size={18} color={colors.goldDeep} weight="fill" />}
                </View>
                <Text style={[typography.bodyStrong, styles.choiceTitle, { color: colors.textStrong }]}>{opt.title}</Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>{opt.caption}</Text>
              </Card3D>
            );
          })}
        </View>
        {value.trackStock && (
          <Field
            label={`จำนวนที่มีขาย (${unitLabel})`}
            required
            value={value.qtyText}
            onChangeText={(t) => set('qtyText', t.replace(/[^0-9]/g, '').slice(0, 6))}
            keyboardType="number-pad"
            placeholder="เช่น 20"
            editable={!disabled}
            error={errors.qtyText}
            hint={mode === 'edit' ? 'เติมจำนวนแล้วสินค้าที่ของหมดจะกลับมาขายเอง' : undefined}
          />
        )}

        {mode === 'edit' && (
          <View style={[styles.switchRow, { borderTopColor: colors.divider }]}>
            <IconTile icon={value.isAvailable ? 'eye' : 'eye-slash'} tone={value.isAvailable ? 'success' : 'neutral'} size={40} />
            <View style={styles.flex}>
              <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>เปิดขาย</Text>
              <Text style={[typography.caption, { color: suspended ? colors.danger : colors.textMuted }]}>
                {suspended ? 'ถูกระงับโดยทีมงาน เปิดขายเองไม่ได้' : value.isAvailable ? 'ลูกค้าเห็นและสั่งได้' : 'ซ่อนจากลูกค้าชั่วคราว'}
              </Text>
            </View>
            <Switch
              value={value.isAvailable}
              onValueChange={(v) => set('isAvailable', v)}
              disabled={disabled || suspended}
              trackColor={{ false: colors.border, true: colors.success }}
              thumbColor={colors.card}
              accessibilityLabel="เปิดขาย"
            />
          </View>
        )}

        {form.max_cashback_percent > 0 && (
          <Field
            label={`เงินคืนให้ลูกค้า (%) ไม่เกิน ${form.max_cashback_percent}%`}
            value={value.cashbackText}
            onChangeText={(t) => set('cashbackText', t.replace(/[^0-9.]/g, '').slice(0, 6))}
            keyboardType="decimal-pad"
            placeholder="0"
            editable={!disabled}
            error={errors.cashbackText}
            hint="เงินคืนจ่ายจากค่า GP ของแพลตฟอร์ม ไม่หักจากรายรับร้าน"
          />
        )}
      </Card3D>
    </>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  gap: {
    marginTop: spacing.lg,
  },
  head: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  label: {
    marginTop: spacing.lg,
    marginBottom: spacing.xs,
    fontWeight: '600',
  },
  subLabel: {
    marginTop: spacing.sm,
    marginBottom: spacing.xs,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
  },
  unitChips: {
    marginTop: spacing.sm,
  },
  errorRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 5,
    marginTop: spacing.xs,
  },
  switchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.lg,
    paddingTop: spacing.lg,
    borderTopWidth: 1,
  },
  gpBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginTop: spacing.md,
    borderRadius: radii.md,
    padding: spacing.md,
  },
  choiceRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  choiceTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  choiceTitle: {
    marginTop: spacing.sm,
  },
});

export default ListingDetailsForm;
