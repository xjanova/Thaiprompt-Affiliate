/**
 * ทดสอบร่างกลุ่มตัวเลือกสินค้าตลาดสด (toDraft / fromDraft / parsePrice)
 * ใช้ข้อมูลรูปแบบเดียวกับ GET /fresh-market/seller/listings/{id} (ผัดกะเพรา: เลือกเนื้อสัตว์ + เพิ่มเติม)
 *
 * รัน: npx jest components/merchant/__tests__/optionDraft.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { fromDraft, newDraftGroup, parsePrice, toDraft } from '../optionDraft';
import type { FmOptionGroup } from '@/services/api/taladsodSellerApi';

const KRAPAO_GROUPS: FmOptionGroup[] = [
  {
    id: 11,
    name: 'เลือกเนื้อสัตว์',
    selection_type: 'single',
    is_required: true,
    min_select: 1,
    max_select: 1,
    rule_label: 'เลือก 1 อย่าง (บังคับ)',
    sort_order: 0,
    options: [
      { id: 101, group_id: 11, name: 'หมูสับ', price_delta: 0, image_url: null, is_available: true, sort_order: 0 },
      { id: 102, group_id: 11, name: 'ไก่', price_delta: 0, image_url: null, is_available: true, sort_order: 1 },
      { id: 103, group_id: 11, name: 'หมึก', price_delta: 10, image_url: null, is_available: true, sort_order: 2 },
      { id: 104, group_id: 11, name: 'กุ้ง', price_delta: 20, image_url: null, is_available: false, sort_order: 3 },
    ],
  },
  {
    id: 12,
    name: 'เพิ่มเติม',
    selection_type: 'multi',
    is_required: false,
    min_select: 0,
    max_select: 1,
    rule_label: 'เลือกได้ไม่เกิน 1 อย่าง',
    sort_order: 1,
    options: [{ id: 105, group_id: 12, name: 'ไข่ดาว', price_delta: 10, image_url: null, is_available: true, sort_order: 0 }],
  },
];

describe('parsePrice', () => {
  it('ว่าง = 0 และรับทศนิยม 2 ตำแหน่ง', () => {
    expect(parsePrice('')).toBe(0);
    expect(parsePrice('20')).toBe(20);
    expect(parsePrice('12.50')).toBe(12.5);
    expect(parsePrice('1,000')).toBe(1000);
  });

  it('ไม่ใช่ตัวเลข / ติดลบ / เกินเพดาน = null', () => {
    expect(parsePrice('abc')).toBeNull();
    expect(parsePrice('-5')).toBeNull();
    expect(parsePrice('1.234')).toBeNull();
    expect(parsePrice('100001')).toBeNull();
  });
});

describe('toDraft → fromDraft', () => {
  it('ข้อมูลจาก server แปลงไปกลับได้ครบ (คง id เดิม เพื่อให้ server แก้แทนสร้างใหม่)', () => {
    const { groups, error } = fromDraft(toDraft(KRAPAO_GROUPS));
    expect(error).toBeNull();
    expect(groups).toHaveLength(2);
    expect(groups[0]).toMatchObject({ id: 11, name: 'เลือกเนื้อสัตว์', selection_type: 'single', is_required: true, max_select: 1 });
    expect(groups[0].options.map((o) => [o.id, o.name, o.price_delta, o.is_available])).toEqual([
      [101, 'หมูสับ', 0, true],
      [102, 'ไก่', 0, true],
      [103, 'หมึก', 10, true],
      [104, 'กุ้ง', 20, false],
    ]);
    expect(groups[1]).toMatchObject({ id: 12, selection_type: 'multi', is_required: false, max_select: 1 });
  });

  it('กลุ่มใหม่ไม่มี id และลำดับ sort_order ตามที่เรียงบนจอ', () => {
    const draft = toDraft(KRAPAO_GROUPS);
    const extra = newDraftGroup();
    extra.name = 'ระดับความเผ็ด';
    extra.options[0].name = 'เผ็ดน้อย';
    const { groups, error } = fromDraft([...draft, extra]);
    expect(error).toBeNull();
    expect(groups[2].id).toBeUndefined();
    expect(groups[2].sort_order).toBe(2);
    expect(groups[2].options[0]).toMatchObject({ name: 'เผ็ดน้อย', price_delta: 0, sort_order: 0 });
  });

  it('หลายอย่างแบบไม่จำกัด (เว้นว่าง) ส่ง max_select = null', () => {
    const draft = toDraft(KRAPAO_GROUPS);
    draft[1].maxText = '';
    expect(fromDraft(draft).groups[1].max_select).toBeNull();
  });
});

describe('fromDraft — ขั้นต่ำที่ต้องเลือก (min_select)', () => {
  /** กลุ่มท็อปปิ้งหลายอย่างที่เคยตั้งบังคับไว้ (server เก็บ min_select = 1) */
  const TOPPING_REQUIRED: FmOptionGroup = {
    id: 21,
    name: 'ท็อปปิ้ง',
    selection_type: 'multi',
    is_required: true,
    min_select: 1,
    max_select: null,
    rule_label: 'เลือกอย่างน้อย 1 อย่าง',
    sort_order: 0,
    options: [
      { id: 201, group_id: 21, name: 'ไข่มุก', price_delta: 5, image_url: null, is_available: true, sort_order: 0 },
      { id: 202, group_id: 21, name: 'วุ้น', price_delta: 5, image_url: null, is_available: true, sort_order: 1 },
      { id: 203, group_id: 21, name: 'พุดดิ้ง', price_delta: 10, image_url: null, is_available: true, sort_order: 2 },
    ],
  };

  it('ปิดบังคับกลุ่มหลายอย่าง → ส่ง min_select = 0 ไปด้วยเสมอ (ไม่งั้น server ดีดกลับเป็นบังคับ)', () => {
    const draft = toDraft([TOPPING_REQUIRED]);
    draft[0].is_required = false;
    const { groups, error } = fromDraft(draft);
    expect(error).toBeNull();
    expect(groups[0]).toMatchObject({ is_required: false, min_select: 0 });
  });

  it('เปลี่ยนกลุ่มเลือกอย่างเดียวที่บังคับ → หลายอย่างไม่บังคับ ก็ส่ง min_select = 0', () => {
    const draft = toDraft(KRAPAO_GROUPS);
    draft[0].selection_type = 'multi';
    draft[0].is_required = false;
    expect(fromDraft(draft).groups[0]).toMatchObject({ selection_type: 'multi', is_required: false, min_select: 0 });
  });

  it('บังคับแบบหลายอย่าง คงขั้นต่ำเดิมไว้ (อย่างน้อย 1)', () => {
    const draft = toDraft([{ ...TOPPING_REQUIRED, min_select: 2 }]);
    expect(draft[0].minSelect).toBe(2);
    expect(fromDraft(draft).groups[0].min_select).toBe(2);

    const fromZero = toDraft([{ ...TOPPING_REQUIRED, is_required: false, min_select: 0 }]);
    fromZero[0].is_required = true;
    expect(fromDraft(fromZero).groups[0].min_select).toBe(1);
  });

  it('ขั้นต่ำเดิมไม่เกิน "เลือกได้สูงสุด" ที่แก้ใหม่', () => {
    const draft = toDraft([{ ...TOPPING_REQUIRED, min_select: 3 }]);
    draft[0].maxText = '2';
    expect(fromDraft(draft).groups[0]).toMatchObject({ min_select: 2, max_select: 2 });
  });

  it('เลือกอย่างเดียว: บังคับ = 1 · ไม่บังคับ = 0 · กลุ่มใหม่ (บังคับ) = 1', () => {
    const draft = toDraft(KRAPAO_GROUPS);
    expect(fromDraft(draft).groups[0].min_select).toBe(1);
    draft[0].is_required = false;
    expect(fromDraft(draft).groups[0].min_select).toBe(0);

    const fresh = newDraftGroup();
    fresh.name = 'ระดับความเผ็ด';
    fresh.options[0].name = 'เผ็ดน้อย';
    expect(fromDraft([fresh]).groups[0]).toMatchObject({ is_required: true, min_select: 1 });
  });
});

describe('fromDraft — ข้อความผิดพลาดภาษาไทย', () => {
  it('ไม่มีชื่อกลุ่ม', () => {
    const draft = toDraft(KRAPAO_GROUPS);
    draft[1].name = '  ';
    expect(fromDraft(draft).error).toContain('กลุ่มที่ 2');
  });

  it('ชื่อตัวเลือกว่าง', () => {
    const draft = toDraft(KRAPAO_GROUPS);
    draft[0].options[2].name = '';
    expect(fromDraft(draft).error).toContain('ข้อที่ 3');
  });

  it('ราคาเพิ่มไม่ใช่ตัวเลข', () => {
    const draft = toDraft(KRAPAO_GROUPS);
    draft[0].options[3].priceText = '20บาท';
    expect(fromDraft(draft).error).toContain('กุ้ง');
  });

  it('กลุ่มบังคับเลือกแต่ทุกตัวเลือกหมด', () => {
    const draft = toDraft(KRAPAO_GROUPS);
    draft[0].options.forEach((o) => {
      o.is_available = false;
    });
    expect(fromDraft(draft).error).toContain('ยังขายอยู่');
  });

  it('เลือกได้สูงสุดต้องเป็นเลข 1–30', () => {
    const draft = toDraft(KRAPAO_GROUPS);
    draft[1].maxText = '0';
    expect(fromDraft(draft).error).toContain('เลือกได้สูงสุด');
  });

  it('กลุ่มเกิน 10 กลุ่ม', () => {
    const many = Array.from({ length: 11 }, () => {
      const g = newDraftGroup();
      g.name = 'กลุ่ม';
      g.options[0].name = 'ตัวเลือก';
      return g;
    });
    expect(fromDraft(many).error).toContain('10');
  });
});
