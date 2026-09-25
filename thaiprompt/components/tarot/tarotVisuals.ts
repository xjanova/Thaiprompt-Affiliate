/**
 * ตัวช่วยหน้าตาหมวดดูดวงไพ่ทาโรต์ (ธีมรอยัล มิดไนท์-ทอง)
 *
 * ข้อมูลใน data/tarotData.ts ยังเก็บไอคอนเป็นอีโมจิ (ตรงกับเว็บ) — หน้าจอไม่แสดงอีโมจิเหล่านั้น
 * แต่แปลงเป็นไอคอนเส้น / สัญลักษณ์ชุดไพ่ / เลขโรมันผ่านไฟล์นี้แทน
 */

import type { IconName } from '@/components/ui';
import type { TarotCard } from '@/data/tarotData';

// =====================================================
// หมวด / โหมดการเปิดไพ่ → ไอคอนเส้น
// =====================================================

/** ไอคอนประจำหมวด (อ้างตาม slug ของหมวด) */
const CATEGORY_ICONS: Record<string, IconName> = {
  'love-relationships': 'heart',
  'career-finance': 'chart-line-up',
  'personal-growth': 'plant',
  'health-wellness': 'leaf',
  general: 'moon-stars',
};

/** ไอคอนของหมวด — ไม่รู้จัก slug = ประกายดาว */
export const categoryIcon = (slug?: string | null): IconName =>
  (slug && CATEGORY_ICONS[slug]) || 'sparkle';

/** ไอคอนประจำโหมดการเปิดไพ่ (อ้างตาม slug ของโหมด) */
const SPREAD_ICONS: Record<string, IconName> = {
  'single-card': 'cards',
  'past-present-future': 'hourglass',
  'celtic-cross': 'crosshair',
  relationship: 'hand-heart',
  'career-path': 'path',
};

/** ไอคอนของโหมด — ไม่รู้จัก slug = ไพ่ซ้อน */
export const spreadIcon = (slug?: string | null): IconName =>
  (slug && SPREAD_ICONS[slug]) || 'cards';

// =====================================================
// หน้าไพ่
// =====================================================

/** ชนิดสัญลักษณ์บนหน้าไพ่: ไพ่ใหญ่ (ดวงอาทิตย์) หรือชุดไพ่เล็กทั้ง 4 */
export type TarotGlyphKind = 'major' | 'wands' | 'cups' | 'swords' | 'pentacles';

/** สัญลักษณ์ที่ใช้กับไพ่ใบนี้ */
export const glyphKindOf = (card: TarotCard): TarotGlyphKind =>
  card.type === 'major_arcana' || !card.suit ? 'major' : card.suit;

const ROMAN: ReadonlyArray<readonly [number, string]> = [
  [10, 'X'],
  [9, 'IX'],
  [5, 'V'],
  [4, 'IV'],
  [1, 'I'],
];

/**
 * แปลงเลขเป็นเลขโรมัน (0 = "0" แบบไพ่ The Fool)
 *
 * @example toRoman(21) // "XXI"
 */
export const toRoman = (value: number): string => {
  if (!Number.isFinite(value) || value <= 0) return '0';
  let rest = Math.floor(value);
  let out = '';
  for (const [num, symbol] of ROMAN) {
    while (rest >= num) {
      out += symbol;
      rest -= num;
    }
  }
  return out;
};

/**
 * ป้ายอันดับบนหน้าไพ่
 * ไพ่ใหญ่ = เลขโรมัน · เอซ = ACE · 2-10 = เลขโรมัน · ไพ่ราชสำนัก = PAGE / KNIGHT / QUEEN / KING
 */
export const rankLabel = (card: TarotCard): string => {
  if (card.type === 'major_arcana') return toRoman(card.number);
  if (card.number === 1) return 'ACE';
  if (card.number <= 10) return toRoman(card.number);
  return (card.name_en.split(' ')[0] || '').toUpperCase();
};
