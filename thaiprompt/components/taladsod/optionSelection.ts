/**
 * กติกาการเลือกตัวเลือกเมนู (ไม่มี React Native — ทดสอบได้ตรงๆ)
 *
 * - selection = { groupId: optionIds[] }
 * - validateSelection ตรวจ min_select / max_select ของทุกกลุ่ม (นับเฉพาะตัวเลือกที่ยังขายอยู่)
 * - ราคาที่ได้เป็นพรีวิว — server คำนวณจริงเสมอ
 */

import type { FmOptionGroup } from '@/services/api/taladsodApi';

/** groupId → optionIds ที่เลือก */
export type OptionSelection = Record<number, number[]>;

export const selectionToIds = (value: OptionSelection): number[] =>
  Object.values(value)
    .flat()
    .filter((id) => Number.isInteger(id) && id > 0);

/** option_ids (จากตะกร้า) → selection ตามกลุ่ม */
export const idsToSelection = (groups: FmOptionGroup[], ids: number[]): OptionSelection => {
  const set = new Set(ids);
  const out: OptionSelection = {};
  groups.forEach((g) => {
    const chosen = g.options.filter((o) => set.has(o.id)).map((o) => o.id);
    if (chosen.length) out[g.id] = chosen;
  });
  return out;
};

/** ผลรวม price_delta ของตัวเลือกที่เลือก */
export const selectionDelta = (groups: FmOptionGroup[], value: OptionSelection): number =>
  groups.reduce((sum, g) => {
    const chosen = new Set(value[g.id] || []);
    return sum + g.options.filter((o) => chosen.has(o.id)).reduce((s, o) => s + o.price_delta, 0);
  }, 0);

/** รูปของตัวเลือกแบบ single ที่เลือก (ใช้เปลี่ยนรูปหลัก) */
export const selectedOptionImage = (groups: FmOptionGroup[], value: OptionSelection): string | null => {
  for (const g of groups) {
    if (g.selection_type !== 'single') continue;
    const id = (value[g.id] || [])[0];
    const opt = g.options.find((o) => o.id === id);
    if (opt?.image_url) return opt.image_url;
  }
  return null;
};

export interface SelectionProblem {
  groupId: number;
  message: string;
}

/** ตรวจว่าเลือกครบตามกฎของทุกกลุ่ม (null = ผ่าน) */
export const validateSelection = (groups: FmOptionGroup[], value: OptionSelection): SelectionProblem | null => {
  for (const g of groups) {
    const available = new Set(g.options.filter((o) => o.is_available).map((o) => o.id));
    const chosen = (value[g.id] || []).filter((id) => available.has(id));
    if (chosen.length < g.min_select) {
      return {
        groupId: g.id,
        message: g.min_select > 1 ? `${g.name}: เลือกอย่างน้อย ${g.min_select} อย่าง` : `ยังไม่ได้${g.name.startsWith('เลือก') ? '' : 'เลือก'}${g.name}`,
      };
    }
    if (g.max_select !== null && chosen.length > g.max_select) {
      return { groupId: g.id, message: `${g.name}: เลือกได้ไม่เกิน ${g.max_select} อย่าง` };
    }
  }
  return null;
};

/** ตัดตัวเลือกที่หมดแล้ว/ไม่มีในเมนูออก (เช่น ตอนแก้ไขบรรทัดในตะกร้า) */
export const sanitizeSelection = (groups: FmOptionGroup[], value: OptionSelection): OptionSelection => {
  const out: OptionSelection = {};
  groups.forEach((g) => {
    const available = new Set(g.options.filter((o) => o.is_available).map((o) => o.id));
    let chosen = (value[g.id] || []).filter((id) => available.has(id));
    if (g.max_select !== null) chosen = chosen.slice(0, g.max_select);
    if (chosen.length) out[g.id] = chosen;
  });
  return out;
};
