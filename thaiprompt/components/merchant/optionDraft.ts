/**
 * ร่างกลุ่มตัวเลือกสินค้า (ไม่มี UI — ทดสอบได้ตรงๆ)
 *
 * - toDraft(): กลุ่มตัวเลือกจาก server → ร่างที่แก้ในจอได้ (ราคาเป็นข้อความระหว่างพิมพ์)
 * - fromDraft(): ร่าง → ข้อมูลที่ส่ง PUT /listings/{id}/option-groups หรือข้อความผิดพลาดภาษาไทย
 *   (บอกว่ากลุ่ม/ข้อไหนผิด) — server ตรวจซ้ำอีกชั้น
 */

import { FM_LIMITS } from '@/services/api/fmLimits';
import type { FmGroupInput, FmOptionGroup } from '@/services/api/taladsodSellerApi';

export interface DraftOption {
  key: string;
  id?: number;
  name: string;
  /** ราคาเพิ่มเป็นข้อความ (พิมพ์อยู่) — ว่าง = 0 */
  priceText: string;
  is_available: boolean;
}

export interface DraftGroup {
  key: string;
  id?: number;
  name: string;
  selection_type: 'single' | 'multi';
  is_required: boolean;
  /**
   * ขั้นต่ำที่ต้องเลือก ตามที่ server เก็บไว้ (กลุ่มใหม่ = 0)
   * ใช้คงค่า "ต้องเลือกอย่างน้อย N" ของกลุ่มหลายอย่างเดิมไว้ตอนบันทึก — ส่งกลับทุกครั้ง
   * เพราะ server คงค่าเดิมถ้าไม่ส่งมา แล้วตั้ง is_required = min > 0 กลับเป็นบังคับเอง
   */
  minSelect: number;
  /** เลือกได้สูงสุด (หลายอย่าง) — ว่าง = ไม่จำกัด */
  maxText: string;
  options: DraftOption[];
}

let keySeq = 0;
const newKey = (prefix: string) => `${prefix}-${Date.now().toString(36)}-${(keySeq++).toString(36)}`;

const priceToText = (value: number): string => {
  if (!Number.isFinite(value) || value <= 0) return '';
  return Number.isInteger(value) ? String(value) : value.toFixed(2);
};

export const toDraft = (groups: FmOptionGroup[]): DraftGroup[] =>
  groups.map((g) => ({
    key: `g-${g.id}`,
    id: g.id,
    name: g.name,
    selection_type: g.selection_type,
    is_required: g.is_required,
    minSelect: Number.isFinite(g.min_select) ? Math.max(0, Math.floor(g.min_select)) : 0,
    maxText: g.selection_type === 'multi' && g.max_select ? String(g.max_select) : '',
    options: g.options.map((o) => ({
      key: `o-${o.id}`,
      id: o.id,
      name: o.name,
      priceText: priceToText(o.price_delta),
      is_available: o.is_available,
    })),
  }));

export const newDraftOption = (): DraftOption => ({ key: newKey('o'), name: '', priceText: '', is_available: true });

export const newDraftGroup = (): DraftGroup => ({
  key: newKey('g'),
  name: '',
  selection_type: 'single',
  is_required: true,
  minSelect: 0,
  maxText: '',
  options: [newDraftOption()],
});

/** ราคาเพิ่มจากข้อความ (null = ไม่ใช่ตัวเลขที่ใช้ได้) */
export const parsePrice = (text: string): number | null => {
  const clean = text.replace(/[,\s฿]/g, '');
  if (clean === '') return 0;
  if (!/^\d+(\.\d{1,2})?$/.test(clean)) return null;
  const n = Number(clean);
  return Number.isFinite(n) && n >= 0 && n <= FM_LIMITS.MAX_PRICE_DELTA ? n : null;
};

/**
 * ขั้นต่ำที่ต้องเลือกที่ส่งให้ server ทุกครั้ง
 * - ไม่บังคับ = 0 (ถ้าไม่ส่ง server จะคงขั้นต่ำเดิมไว้ แล้วดีดกลับเป็นบังคับเอง)
 * - บังคับแบบเลือกอย่างเดียว = 1
 * - บังคับแบบหลายอย่าง = ขั้นต่ำเดิม (อย่างน้อย 1) แต่ไม่เกิน "เลือกได้สูงสุด" และจำนวนตัวเลือก
 */
const minSelectFor = (g: DraftGroup, maxSelect: number | null, optionCount: number): number => {
  if (!g.is_required) return 0;
  if (g.selection_type === 'single') return 1;
  const kept = Number.isFinite(g.minSelect) ? Math.floor(g.minSelect) : 0;
  const cap = Math.max(1, Math.min(maxSelect ?? optionCount, optionCount));
  return Math.min(Math.max(1, kept), cap);
};

/** draft → ข้อมูลที่ส่งให้ server (หรือข้อความผิดพลาดภาษาไทย) */
export const fromDraft = (draft: DraftGroup[]): { groups: FmGroupInput[]; error: string | null } => {
  if (draft.length > FM_LIMITS.MAX_GROUPS) {
    return { groups: [], error: `ตั้งกลุ่มตัวเลือกได้ไม่เกิน ${FM_LIMITS.MAX_GROUPS} กลุ่ม` };
  }
  const groups: FmGroupInput[] = [];
  for (let gi = 0; gi < draft.length; gi++) {
    const g = draft[gi];
    const tag = `กลุ่มที่ ${gi + 1}`;
    const name = g.name.trim();
    if (!name) return { groups: [], error: `${tag}: ใส่ชื่อกลุ่มก่อนนะ เช่น "เลือกเนื้อสัตว์"` };
    if (g.options.length === 0) return { groups: [], error: `${tag}: ต้องมีตัวเลือกอย่างน้อย 1 อย่าง` };
    if (g.options.length > FM_LIMITS.MAX_OPTIONS_PER_GROUP) {
      return { groups: [], error: `${tag}: มีตัวเลือกได้ไม่เกิน ${FM_LIMITS.MAX_OPTIONS_PER_GROUP} อย่าง` };
    }

    let maxSelect: number | null = null;
    if (g.selection_type === 'multi' && g.maxText.trim() !== '') {
      const m = Number(g.maxText.trim());
      if (!Number.isInteger(m) || m < 1 || m > FM_LIMITS.MAX_OPTIONS_PER_GROUP) {
        return { groups: [], error: `${tag}: "เลือกได้สูงสุด" ต้องเป็นเลข 1–${FM_LIMITS.MAX_OPTIONS_PER_GROUP} หรือเว้นว่าง` };
      }
      maxSelect = m;
    }

    const options = [];
    for (let oi = 0; oi < g.options.length; oi++) {
      const o = g.options[oi];
      const optName = o.name.trim();
      if (!optName) return { groups: [], error: `${tag}: ใส่ชื่อตัวเลือกข้อที่ ${oi + 1} ให้ครบนะ` };
      const price = parsePrice(o.priceText);
      if (price === null) {
        return { groups: [], error: `${tag}: ราคาเพิ่มของ "${optName}" ต้องเป็นตัวเลข 0–${FM_LIMITS.MAX_PRICE_DELTA.toLocaleString('th-TH')}` };
      }
      options.push({
        ...(o.id ? { id: o.id } : {}),
        name: optName.slice(0, FM_LIMITS.NAME_MAX),
        price_delta: price,
        is_available: o.is_available,
        sort_order: oi,
      });
    }

    if (g.is_required && !options.some((o) => o.is_available)) {
      return { groups: [], error: `${tag}: กลุ่มบังคับเลือกต้องมีตัวเลือกที่ยังขายอยู่อย่างน้อย 1 อย่าง` };
    }

    groups.push({
      ...(g.id ? { id: g.id } : {}),
      name: name.slice(0, FM_LIMITS.NAME_MAX),
      selection_type: g.selection_type,
      is_required: g.is_required,
      min_select: minSelectFor(g, maxSelect, options.length),
      max_select: g.selection_type === 'single' ? 1 : maxSelect,
      sort_order: gi,
      options,
    });
  }
  return { groups, error: null };
};
