/**
 * ทดสอบกติกาเลือกตัวเลือกเมนู ด้วยเมนูเปิดตัว "ผัดกะเพราราดข้าว"
 * (เนื้อสัตว์ บังคับเลือก 1: หมูสับ/ไก่ +0, หมึก +10, กุ้ง +20 · เพิ่มเติม ไม่บังคับ สูงสุด 1: ไข่ดาว +10)
 */

import { describe, expect, it } from '@jest/globals';
import {
  idsToSelection,
  sanitizeSelection,
  selectedOptionImage,
  selectionDelta,
  selectionToIds,
  validateSelection,
} from '../optionSelection';
import type { FmOptionGroup } from '@/services/api/taladsodApi';

const MEAT: FmOptionGroup = {
  id: 1,
  name: 'เลือกเนื้อสัตว์',
  selection_type: 'single',
  is_required: true,
  min_select: 1,
  max_select: 1,
  rule_label: 'เลือก 1 อย่าง (บังคับ)',
  sort_order: 0,
  options: [
    { id: 11, group_id: 1, name: 'หมูสับ', price_delta: 0, image_url: '/images/taladsod/krapao-pork.webp', is_available: true, sort_order: 0 },
    { id: 12, group_id: 1, name: 'ไก่', price_delta: 0, image_url: '/images/taladsod/krapao-chicken.webp', is_available: true, sort_order: 1 },
    { id: 13, group_id: 1, name: 'หมึก', price_delta: 10, image_url: '/images/taladsod/krapao-squid.webp', is_available: true, sort_order: 2 },
    { id: 14, group_id: 1, name: 'กุ้ง', price_delta: 20, image_url: '/images/taladsod/krapao-shrimp.webp', is_available: false, sort_order: 3 },
  ],
};

const EXTRA: FmOptionGroup = {
  id: 2,
  name: 'เพิ่มเติม',
  selection_type: 'multi',
  is_required: false,
  min_select: 0,
  max_select: 1,
  rule_label: 'เลือกได้ไม่เกิน 1 อย่าง',
  sort_order: 1,
  options: [{ id: 21, group_id: 2, name: 'ไข่ดาว', price_delta: 10, image_url: '/images/taladsod/fried-egg.webp', is_available: true, sort_order: 0 }],
};

const GROUPS = [MEAT, EXTRA];

describe('validateSelection', () => {
  it('ยังไม่เลือกเนื้อสัตว์ → บอกกลุ่มที่ขาด', () => {
    expect(validateSelection(GROUPS, {})).toEqual({ groupId: 1, message: 'ยังไม่ได้เลือกเนื้อสัตว์' });
  });

  it('เลือกเนื้อสัตว์แล้ว ไม่เลือกไข่ดาวก็ผ่าน', () => {
    expect(validateSelection(GROUPS, { 1: [13] })).toBeNull();
  });

  it('ตัวเลือกที่หมดชั่วคราวไม่นับว่าเลือกแล้ว', () => {
    expect(validateSelection(GROUPS, { 1: [14] })?.groupId).toBe(1);
  });

  it('เลือกเกินจำนวนสูงสุด → ไม่ผ่าน', () => {
    const multi: FmOptionGroup = {
      ...EXTRA,
      max_select: 1,
      options: [...EXTRA.options, { ...EXTRA.options[0], id: 22, name: 'ไข่เจียว' }],
    };
    expect(validateSelection([MEAT, multi], { 1: [11], 2: [21, 22] })?.groupId).toBe(2);
  });
});

describe('ราคาและรูป', () => {
  it('รวมราคาบวกของตัวเลือกที่เลือก', () => {
    expect(selectionDelta(GROUPS, { 1: [13] })).toBe(10);
    expect(selectionDelta(GROUPS, { 1: [13], 2: [21] })).toBe(20);
    expect(selectionDelta(GROUPS, {})).toBe(0);
  });

  it('รูปของเนื้อสัตว์ที่เลือก', () => {
    expect(selectedOptionImage(GROUPS, { 1: [12] })).toBe('/images/taladsod/krapao-chicken.webp');
    expect(selectedOptionImage(GROUPS, { 2: [21] })).toBeNull();
  });
});

describe('แปลงไป-กลับกับ option_ids ของตะกร้า', () => {
  it('ids → selection → ids', () => {
    const selection = idsToSelection(GROUPS, [21, 11, 999]);
    expect(selection).toEqual({ 1: [11], 2: [21] });
    expect(selectionToIds(selection).sort()).toEqual([11, 21]);
  });

  it('ตัดตัวเลือกที่หมดแล้วออกตอนแก้ไขบรรทัดในตะกร้า', () => {
    expect(sanitizeSelection(GROUPS, { 1: [14], 2: [21] })).toEqual({ 2: [21] });
  });
});
