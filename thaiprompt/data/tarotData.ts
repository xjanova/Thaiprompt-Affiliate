/**
 * Tarot Card Data - ข้อมูลไพ่ทาโรต์ 78 ใบ
 * ตรงกับเว็บ TarotSystemSeeder.php
 */

// ประเภทไพ่
export type CardType = 'major_arcana' | 'minor_arcana';
export type Suit = 'wands' | 'cups' | 'swords' | 'pentacles' | null;

// ข้อมูลไพ่
export interface TarotCard {
  id: number;
  number: number;
  type: CardType;
  suit: Suit;
  name_en: string;
  name_th: string;
  keywords_en: string[];
  keywords_th: string[];
  upright_meaning_th: string;
  reversed_meaning_th: string;
  icon: string;
  image_url?: string; // URL รูปไพ่ (ถ้ามี)
}

// URL รูปไพ่พื้นฐาน
export const CARD_IMAGES = {
  // รูปหลังไพ่
  cardBack: '/images/tarot/card-back-default.svg',
  // รูปไพ่ default
  defaultCard: '/images/tarot/default-card.svg',
  // Base URL สำหรับรูปไพ่ (ใช้เมื่อมีรูปจริง)
  baseUrl: '/images/tarot/cards/',
};

// หมวดหมู่การอ่านไพ่
export interface TarotCategory {
  id: number;
  slug: string;
  name_en: string;
  name_th: string;
  description_th: string;
  icon: string;
  color: string;
  gradientStart: string;
  gradientEnd: string;
  price: number;
  is_free_first: boolean;
}

// โหมดการเปิดไพ่
export interface SpreadType {
  id: number;
  slug: string;
  name_en: string;
  name_th: string;
  description_th: string;
  card_count: number;
  positions: SpreadPosition[];
  icon: string;
  color: string;
}

export interface SpreadPosition {
  name_en: string;
  name_th: string;
  description_th: string;
}

// ==============================
// Major Arcana (22 ใบ)
// ==============================

const MAJOR_ARCANA: TarotCard[] = [
  {
    id: 1,
    number: 0,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Fool',
    name_th: 'คนบ้า',
    keywords_en: ['new beginnings', 'innocence', 'spontaneity', 'free spirit'],
    keywords_th: ['การเริ่มต้นใหม่', 'ความบริสุทธิ์', 'ความเป็นธรรมชาติ', 'จิตวิญญาณเสรี'],
    upright_meaning_th: 'การเริ่มต้นใหม่ การผจญภัย ความบริสุทธิ์ ความเป็นธรรมชาติ โอกาสใหม่ๆ การก้าวไปข้างหน้าด้วยความมั่นใจ',
    reversed_meaning_th: 'ความประมาท ความไร้เหตุผล การขาดการวางแผน ความเสี่ยงที่ไม่จำเป็น',
    icon: '🃏',
  },
  {
    id: 2,
    number: 1,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Magician',
    name_th: 'นักมายากล',
    keywords_en: ['manifestation', 'resourcefulness', 'power', 'inspired action'],
    keywords_th: ['การสร้างสรรค์', 'ความสามารถ', 'พลัง', 'การกระทำที่มีแรงบันดาลใจ'],
    upright_meaning_th: 'ความสามารถในการทำสิ่งต่างๆให้สำเร็จ การใช้ทักษะและความสามารถอย่างเต็มที่ การสร้างสรรค์สิ่งใหม่ พลังแห่งการกระทำ',
    reversed_meaning_th: 'การใช้พลังในทางที่ผิด การหลอกลวง ความสามารถที่ยังไม่พัฒนา การขาดทิศทาง',
    icon: '✨',
  },
  {
    id: 3,
    number: 2,
    type: 'major_arcana',
    suit: null,
    name_en: 'The High Priestess',
    name_th: 'มหาปุโรหิตหญิง',
    keywords_en: ['intuition', 'sacred knowledge', 'divine feminine', 'subconscious'],
    keywords_th: ['สัญชาตญาณ', 'ความรู้ลึกลับ', 'พลังแห่งหญิง', 'จิตใต้สำนึก'],
    upright_meaning_th: 'สัญชาตญาณที่แข็งแกร่ง ความรู้ที่ลึกซึ้ง ความลึกลับ การฟังเสียงภายใน ความเข้าใจในระดับจิตวิญญาณ',
    reversed_meaning_th: 'การเพิกเฉยต่อสัญชาตญาณ ความลับที่ถูกเปิดเผย การขาดความเชื่อมโยงกับตัวเอง',
    icon: '🌙',
  },
  {
    id: 4,
    number: 3,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Empress',
    name_th: 'จักรพรรดินี',
    keywords_en: ['femininity', 'beauty', 'nature', 'nurturing', 'abundance'],
    keywords_th: ['ความเป็นหญิง', 'ความงาม', 'ธรรมชาติ', 'การเลี้ยงดู', 'ความอุดมสมบูรณ์'],
    upright_meaning_th: 'ความอุดมสมบูรณ์ การเติบโต ความงาม การเลี้ยงดู ความรัก ความอ่อนโยน ความสำเร็จในครอบครัว',
    reversed_meaning_th: 'การขาดความมั่นใจ ความสับสน การขาดการเติบโต ปัญหาเรื่องความอุดมสมบูรณ์',
    icon: '👑',
  },
  {
    id: 5,
    number: 4,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Emperor',
    name_th: 'จักรพรรดิ',
    keywords_en: ['authority', 'structure', 'control', 'fatherhood'],
    keywords_th: ['อำนาจ', 'โครงสร้าง', 'การควบคุม', 'ความเป็นพ่อ'],
    upright_meaning_th: 'อำนาจ การควบคุม โครงสร้างที่มั่นคง ความเป็นผู้นำ กฎระเบียบ การตัดสินใจที่มั่นคง',
    reversed_meaning_th: 'การใช้อำนาจเกินขอบเขต ความเข้มงวดเกินไป ความเป็นเผด็จการ การขาดระเบียบวินัย',
    icon: '🏛️',
  },
  {
    id: 6,
    number: 5,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Hierophant',
    name_th: 'มหาปุโรหิต',
    keywords_en: ['spiritual wisdom', 'religious beliefs', 'conformity', 'tradition'],
    keywords_th: ['ภูมิปัญญาทางจิตวิญญาณ', 'ความเชื่อทางศาสนา', 'ประเพณี', 'การยึดมั่น'],
    upright_meaning_th: 'ความเชื่อทางจิตวิญญาณ ประเพณี การศึกษา การแนะนำ ความรู้ที่สืบทอด การยึดมั่นในหลักการ',
    reversed_meaning_th: 'การท้าทายประเพณี ความเชื่อที่ล้าสมัย การขาดความยืดหยุ่น การหลุดพ้นจากข้อจำกัด',
    icon: '📿',
  },
  {
    id: 7,
    number: 6,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Lovers',
    name_th: 'คู่รัก',
    keywords_en: ['love', 'harmony', 'relationships', 'choices'],
    keywords_th: ['ความรัก', 'ความสามัคคี', 'ความสัมพันธ์', 'การเลือก'],
    upright_meaning_th: 'ความรักที่แท้จริง ความสัมพันธ์ที่ลงตัว การเลือกที่สำคัญ ความกลมกลืน การรวมเป็นหนึ่ง',
    reversed_meaning_th: 'ความไม่ลงรอยกัน การเลือกที่ผิดพลาด ความสัมพันธ์ที่ไม่สมดุล ความขัดแย้ง',
    icon: '💕',
  },
  {
    id: 8,
    number: 7,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Chariot',
    name_th: 'รถม้า',
    keywords_en: ['control', 'willpower', 'success', 'determination'],
    keywords_th: ['การควบคุม', 'พลังใจ', 'ความสำเร็จ', 'ความมุ่งมั่น'],
    upright_meaning_th: 'ความมุ่งมั่น ความสำเร็จ การควบคุมทิศทาง ชัยชนะ การก้าวไปข้างหน้าอย่างมั่นใจ',
    reversed_meaning_th: 'การสูญเสียการควบคุม ทิศทางที่สับสน ความล้มเหลว การขาดแรงจูงใจ',
    icon: '🏇',
  },
  {
    id: 9,
    number: 8,
    type: 'major_arcana',
    suit: null,
    name_en: 'Strength',
    name_th: 'พลัง',
    keywords_en: ['strength', 'courage', 'patience', 'control'],
    keywords_th: ['ความแข็งแกร่ง', 'ความกล้าหาญ', 'ความอดทน', 'การควบคุม'],
    upright_meaning_th: 'ความแข็งแกร่งภายใน ความกล้าหาญ ความอดทน การควบคุมตนเอง ความอ่อนโยนที่มีพลัง',
    reversed_meaning_th: 'ความอ่อนแอ การขาดความมั่นใจ ความกลัว การไม่สามารถควบคุมตนเอง',
    icon: '🦁',
  },
  {
    id: 10,
    number: 9,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Hermit',
    name_th: 'นักบวช',
    keywords_en: ['soul searching', 'introspection', 'inner guidance', 'solitude'],
    keywords_th: ['การค้นหาตัวเอง', 'การมองเข้าไปข้างใน', 'การชี้นำภายใน', 'ความสันโดษ'],
    upright_meaning_th: 'การค้นหาตัวเอง ปัญญาภายใน ความสันโดษ การใคร่ครวญ การเดินทางภายใน',
    reversed_meaning_th: 'ความโดดเดี่ยว การแยกตัว ความสับสน การหลีกหนีจากความจริง',
    icon: '🏔️',
  },
  {
    id: 11,
    number: 10,
    type: 'major_arcana',
    suit: null,
    name_en: 'Wheel of Fortune',
    name_th: 'กงล้อแห่งโชค',
    keywords_en: ['good luck', 'karma', 'life cycles', 'destiny'],
    keywords_th: ['โชคดี', 'กรรม', 'วงจรชีวิต', 'โชคชะตา'],
    upright_meaning_th: 'การเปลี่ยนแปลง โชคลาภ วงจรชีวิต โอกาสใหม่ จุดเปลี่ยนที่สำคัญ',
    reversed_meaning_th: 'โชคร้าย ความไม่แน่นอน การต่อต้านการเปลี่ยนแปลง วงจรที่ไม่ดี',
    icon: '🎡',
  },
  {
    id: 12,
    number: 11,
    type: 'major_arcana',
    suit: null,
    name_en: 'Justice',
    name_th: 'ความยุติธรรม',
    keywords_en: ['justice', 'fairness', 'truth', 'law'],
    keywords_th: ['ความยุติธรรม', 'ความเป็นธรรม', 'ความจริง', 'กฎหมาย'],
    upright_meaning_th: 'ความยุติธรรม ความเป็นธรรม ความจริง การตัดสินใจที่ถูกต้อง ความสมดุล',
    reversed_meaning_th: 'ความไม่เป็นธรรม ความลำเอียง การตัดสินผิดพลาด ความไม่สมดุล',
    icon: '⚖️',
  },
  {
    id: 13,
    number: 12,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Hanged Man',
    name_th: 'ชายผู้ถูกแขวนคอ',
    keywords_en: ['pause', 'surrender', 'letting go', 'new perspectives'],
    keywords_th: ['การหยุดพัก', 'การยอมรับ', 'การปล่อยวาง', 'มุมมองใหม่'],
    upright_meaning_th: 'การหยุดพัก การเห็นจากมุมมองใหม่ การยอมรับ การเสียสละ การรอคอย',
    reversed_meaning_th: 'การต่อต้าน ความไม่พร้อมที่จะปล่อยวาง การเสียเวลา ความหยุดชะงัก',
    icon: '🙃',
  },
  {
    id: 14,
    number: 13,
    type: 'major_arcana',
    suit: null,
    name_en: 'Death',
    name_th: 'ความตาย',
    keywords_en: ['endings', 'change', 'transformation', 'transition'],
    keywords_th: ['การสิ้นสุด', 'การเปลี่ยนแปลง', 'การเปลี่ยนแปลงครั้งใหญ่', 'การเปลี่ยนผ่าน'],
    upright_meaning_th: 'การสิ้นสุดและการเริ่มต้นใหม่ การเปลี่ยนแปลงครั้งใหญ่ การปล่อยวางอดีต การเปลี่ยนแปลงที่จำเป็น',
    reversed_meaning_th: 'การต่อต้านการเปลี่ยนแปลง ความกลัวต่อสิ่งใหม่ การยึดติดกับอดีต',
    icon: '🦋',
  },
  {
    id: 15,
    number: 14,
    type: 'major_arcana',
    suit: null,
    name_en: 'Temperance',
    name_th: 'ความพอประมาณ',
    keywords_en: ['balance', 'moderation', 'patience', 'purpose'],
    keywords_th: ['ความสมดุล', 'ความพอดี', 'ความอดทน', 'จุดมุ่งหมาย'],
    upright_meaning_th: 'ความสมดุล ความพอดี ความอดทน การประสานกลมกลืน ความสงบ',
    reversed_meaning_th: 'ความไม่สมดุล ความเกินพอดี ความใจร้อน การขาดการประสานงาน',
    icon: '⚗️',
  },
  {
    id: 16,
    number: 15,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Devil',
    name_th: 'ปีศาจ',
    keywords_en: ['shadow self', 'attachment', 'addiction', 'restriction'],
    keywords_th: ['ด้านมืด', 'การยึดติด', 'การเสพติด', 'ข้อจำกัด'],
    upright_meaning_th: 'การยึดติด ข้อจำกัด ความมืดมิด สิ่งล่อใจ อำนาจของวัตถุนิยม',
    reversed_meaning_th: 'การปลดปล่อย การหลุดพ้น การเอาชนะความกลัว การมองเห็นความจริง',
    icon: '😈',
  },
  {
    id: 17,
    number: 16,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Tower',
    name_th: 'หอคอย',
    keywords_en: ['sudden change', 'upheaval', 'chaos', 'revelation'],
    keywords_th: ['การเปลี่ยนแปลงกระทันหัน', 'ความโกลาหล', 'ความวุ่นวาย', 'การเปิดเผย'],
    upright_meaning_th: 'การเปลี่ยนแปลงกะทันหัน การพังทลาย ความจริงที่ถูกเปิดเผย วิกฤตการณ์',
    reversed_meaning_th: 'การหลีกเลี่ยงภัยพิบัติ การเปลี่ยนแปลงที่ค่อยเป็นค่อยไป ความกลัวต่อการเปลี่ยนแปลง',
    icon: '🗼',
  },
  {
    id: 18,
    number: 17,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Star',
    name_th: 'ดวงดาว',
    keywords_en: ['hope', 'faith', 'purpose', 'renewal'],
    keywords_th: ['ความหวัง', 'ศรัทธา', 'จุดมุ่งหมาย', 'การฟื้นฟู'],
    upright_meaning_th: 'ความหวัง การฟื้นฟู แรงบันดาลใจ ความเชื่อมั่น ความสงบในจิตใจ',
    reversed_meaning_th: 'การสูญเสียความหวัง ความท้อแท้ การขาดแรงบันดาลใจ ความสงสัยในตนเอง',
    icon: '⭐',
  },
  {
    id: 19,
    number: 18,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Moon',
    name_th: 'ดวงจันทร์',
    keywords_en: ['illusion', 'fear', 'anxiety', 'subconscious'],
    keywords_th: ['ภาพลวงตา', 'ความกลัว', 'ความวิตกกังวล', 'จิตใต้สำนึก'],
    upright_meaning_th: 'ความไม่แน่นอน ภาพลวงตา ความกลัว สัญชาตญาณ ความลึกลับ',
    reversed_meaning_th: 'การปลดปล่อยความกลัว ความชัดเจน การเปิดเผยความจริง',
    icon: '🌕',
  },
  {
    id: 20,
    number: 19,
    type: 'major_arcana',
    suit: null,
    name_en: 'The Sun',
    name_th: 'ดวงอาทิตย์',
    keywords_en: ['positivity', 'fun', 'warmth', 'success'],
    keywords_th: ['ความเป็นบวก', 'ความสนุกสนาน', 'ความอบอุ่น', 'ความสำเร็จ'],
    upright_meaning_th: 'ความสุข ความสำเร็จ ความมีชีวิตชีวา พลังงานบวก ความสดใส',
    reversed_meaning_th: 'ความมืดมน ความไม่มีความสุข ความล้มเหลวชั่วคราว การมองโลกในแง่ร้าย',
    icon: '☀️',
  },
  {
    id: 21,
    number: 20,
    type: 'major_arcana',
    suit: null,
    name_en: 'Judgement',
    name_th: 'การพิพากษา',
    keywords_en: ['judgement', 'rebirth', 'inner calling', 'absolution'],
    keywords_th: ['การตัดสิน', 'การเกิดใหม่', 'เสียงเรียกภายใน', 'การให้อภัย'],
    upright_meaning_th: 'การเกิดใหม่ การตัดสินที่ดี การให้อภัย การยอมรับในอดีต จุดเปลี่ยนสำคัญ',
    reversed_meaning_th: 'ความสงสัยในตนเอง การตัดสินผิดพลาด การไม่ให้อภัย การยึดติดอดีต',
    icon: '📯',
  },
  {
    id: 22,
    number: 21,
    type: 'major_arcana',
    suit: null,
    name_en: 'The World',
    name_th: 'โลก',
    keywords_en: ['completion', 'accomplishment', 'travel', 'success'],
    keywords_th: ['ความสมบูรณ์', 'ความสำเร็จ', 'การเดินทาง', 'ความสำเร็จ'],
    upright_meaning_th: 'ความสำเร็จอย่างสมบูรณ์ การบรรลุเป้าหมาย การเดินทาง ความสมบูรณ์',
    reversed_meaning_th: 'ความไม่สมบูรณ์ การขาดความสำเร็จ ความรู้สึกติดขัด การไม่เสร็จสิ้น',
    icon: '🌍',
  },
];

// ==============================
// Minor Arcana Helper Function
// ==============================

const createMinorArcana = (
  suit: Suit,
  suitTh: string,
  suitIcon: string,
  startId: number,
  keywords: { en: string[]; th: string[] }
): TarotCard[] => {
  const ranks = [
    { number: 1, name: 'Ace', name_th: 'เอซ', icon: '🔥' },
    { number: 2, name: 'Two', name_th: 'สอง', icon: '⚔️' },
    { number: 3, name: 'Three', name_th: 'สาม', icon: '🔱' },
    { number: 4, name: 'Four', name_th: 'สี่', icon: '🏠' },
    { number: 5, name: 'Five', name_th: 'ห้า', icon: '⚡' },
    { number: 6, name: 'Six', name_th: 'หก', icon: '🌟' },
    { number: 7, name: 'Seven', name_th: 'เจ็ด', icon: '🎯' },
    { number: 8, name: 'Eight', name_th: 'แปด', icon: '🚀' },
    { number: 9, name: 'Nine', name_th: 'เก้า', icon: '🏆' },
    { number: 10, name: 'Ten', name_th: 'สิบ', icon: '💫' },
    { number: 11, name: 'Page', name_th: 'ข้าราชบริพาร', icon: '📜' },
    { number: 12, name: 'Knight', name_th: 'อัศวิน', icon: '🏇' },
    { number: 13, name: 'Queen', name_th: 'ราชินี', icon: '👑' },
    { number: 14, name: 'King', name_th: 'กษัตริย์', icon: '🤴' },
  ];

  // 🃏 (2026-05-26) USER SPEC: ไม่ใช้คำว่า "แห่ง" — sync กับ Laravel seeder + migration
  //   "สองแห่งดาบ" → "สองดาบ" / "สิบแห่งเหรียญ" → "สิบเหรียญ"
  //   ดู: database/seeders/TarotSystemSeeder.php + migration 2026_05_26_010000_remove_haeng_from_minor_arcana_names.php
  return ranks.map((rank, index) => ({
    id: startId + index,
    number: rank.number,
    type: 'minor_arcana' as CardType,
    suit,
    name_en: `${rank.name} of ${suit!.charAt(0).toUpperCase() + suit!.slice(1)}`,
    name_th: `${rank.name_th}${suitTh}`,
    keywords_en: keywords.en,
    keywords_th: keywords.th,
    upright_meaning_th: `ด้านบวกของ${keywords.th.join(', ')} - ${rank.name_th}${suitTh}`,
    reversed_meaning_th: `ด้านลบของ${keywords.th.join(', ')} - ${rank.name_th}${suitTh}`,
    icon: rank.number <= 10 ? suitIcon : rank.icon,
  }));
};

// ==============================
// Minor Arcana (56 ใบ)
// ==============================

const WANDS = createMinorArcana(
  'wands',
  'ไม้เท้า',
  '🪄',
  23,
  {
    en: ['passion', 'creativity', 'action', 'adventure'],
    th: ['ความหลงใหล', 'ความคิดสร้างสรรค์', 'การกระทำ', 'การผจญภัย'],
  }
);

const CUPS = createMinorArcana(
  'cups',
  'ถ้วย',
  '🏆',
  37,
  {
    en: ['emotions', 'relationships', 'feelings', 'intuition'],
    th: ['อารมณ์', 'ความสัมพันธ์', 'ความรู้สึก', 'สัญชาตญาณ'],
  }
);

const SWORDS = createMinorArcana(
  'swords',
  'ดาบ',
  '⚔️',
  51,
  {
    en: ['thoughts', 'intellect', 'conflict', 'communication'],
    th: ['ความคิด', 'สติปัญญา', 'ความขัดแย้ง', 'การสื่อสาร'],
  }
);

const PENTACLES = createMinorArcana(
  'pentacles',
  'เหรียญ',
  '🪙',
  65,
  {
    en: ['material', 'money', 'career', 'practical'],
    th: ['วัตถุ', 'เงิน', 'การงาน', 'ความเป็นจริง'],
  }
);

// ==============================
// รวมไพ่ทั้งหมด 78 ใบ
// ==============================

export const ALL_TAROT_CARDS: TarotCard[] = [
  ...MAJOR_ARCANA,
  ...WANDS,
  ...CUPS,
  ...SWORDS,
  ...PENTACLES,
];

// ==============================
// หมวดหมู่การอ่านไพ่ (5 หมวด)
// ==============================

export const TAROT_CATEGORIES: TarotCategory[] = [
  {
    id: 1,
    slug: 'love-relationships',
    name_en: 'Love & Relationships',
    name_th: 'ความรัก & ความสัมพันธ์',
    description_th: 'เปิดไพ่ดูดวงความรัก คู่ครอง เนื้อคู่',
    icon: '💕',
    color: '#EC4899',
    gradientStart: '#EC4899',
    gradientEnd: '#BE185D',
    price: 0,
    is_free_first: true,
  },
  {
    id: 2,
    slug: 'career-finance',
    name_en: 'Career & Finance',
    name_th: 'การงาน & การเงิน',
    description_th: 'ดูดวงการงาน การเงิน ธุรกิจ',
    icon: '💼',
    color: '#3B82F6',
    gradientStart: '#3B82F6',
    gradientEnd: '#1D4ED8',
    price: 99,
    is_free_first: true,
  },
  {
    id: 3,
    slug: 'personal-growth',
    name_en: 'Personal Growth',
    name_th: 'พัฒนาตนเอง',
    description_th: 'ค้นหาตัวเอง เส้นทางชีวิต',
    icon: '🌟',
    color: '#8B5CF6',
    gradientStart: '#8B5CF6',
    gradientEnd: '#6D28D9',
    price: 79,
    is_free_first: true,
  },
  {
    id: 4,
    slug: 'health-wellness',
    name_en: 'Health & Wellness',
    name_th: 'สุขภาพ & ความเป็นอยู่',
    description_th: 'ดูดวงสุขภาพ พลังงานชีวิต',
    icon: '🍀',
    color: '#10B981',
    gradientStart: '#10B981',
    gradientEnd: '#059669',
    price: 89,
    is_free_first: true,
  },
  {
    id: 5,
    slug: 'general',
    name_en: 'General Reading',
    name_th: 'ดูดวงทั่วไป',
    description_th: 'เปิดไพ่ดูดวงทั่วไป ฟรี!',
    icon: '🔮',
    color: '#F59E0B',
    gradientStart: '#F59E0B',
    gradientEnd: '#D97706',
    price: 0,
    is_free_first: true,
  },
];

// ==============================
// โหมดการเปิดไพ่ (5 โหมด)
// ==============================

export const SPREAD_TYPES: SpreadType[] = [
  {
    id: 1,
    slug: 'single-card',
    name_en: 'Single Card',
    name_th: 'ไพ่ใบเดียว',
    description_th: 'การทำนายแบบไพ่ใบเดียวสำหรับคำตอบรวดเร็ว',
    card_count: 1,
    positions: [
      { name_en: 'Answer', name_th: 'คำตอบ', description_th: 'คำตอบของคำถามที่คุณถาม' },
    ],
    icon: '🎴',
    color: '#8B5CF6',
  },
  {
    id: 2,
    slug: 'past-present-future',
    name_en: 'Past, Present, Future',
    name_th: 'อดีต ปัจจุบัน อนาคต',
    description_th: 'การเปิดไพ่ 3 ใบแสดงเส้นทางเวลา',
    card_count: 3,
    positions: [
      { name_en: 'Past', name_th: 'อดีต', description_th: 'สิ่งที่ผ่านมาที่มีผลต่อปัจจุบัน' },
      { name_en: 'Present', name_th: 'ปัจจุบัน', description_th: 'สถานการณ์ปัจจุบันของคุณ' },
      { name_en: 'Future', name_th: 'อนาคต', description_th: 'สิ่งที่กำลังจะเกิดขึ้น' },
    ],
    icon: '🕐',
    color: '#EC4899',
  },
  {
    id: 3,
    slug: 'celtic-cross',
    name_en: 'Celtic Cross',
    name_th: 'ไม้กางเขนเซลติก',
    description_th: 'การเปิดไพ่ 10 ใบแบบละเอียดลึกซึ้ง',
    card_count: 10,
    positions: [
      { name_en: 'Present', name_th: 'ปัจจุบัน', description_th: 'สถานการณ์ปัจจุบัน' },
      { name_en: 'Challenge', name_th: 'อุปสรรค', description_th: 'สิ่งที่ขัดขวาง' },
      { name_en: 'Past', name_th: 'อดีต', description_th: 'รากฐานของสถานการณ์' },
      { name_en: 'Future', name_th: 'อนาคต', description_th: 'สิ่งที่กำลังจะมา' },
      { name_en: 'Above', name_th: 'เป้าหมาย', description_th: 'สิ่งที่คุณมุ่งหวัง' },
      { name_en: 'Below', name_th: 'รากฐาน', description_th: 'พื้นฐานของคุณ' },
      { name_en: 'Advice', name_th: 'คำแนะนำ', description_th: 'สิ่งที่คุณควรทำ' },
      { name_en: 'External', name_th: 'ภายนอก', description_th: 'อิทธิพลจากภายนอก' },
      { name_en: 'Hopes', name_th: 'ความหวัง', description_th: 'ความหวังและความกลัว' },
      { name_en: 'Outcome', name_th: 'ผลลัพธ์', description_th: 'ผลลัพธ์สุดท้าย' },
    ],
    icon: '✝️',
    color: '#6366F1',
  },
  {
    id: 4,
    slug: 'relationship',
    name_en: 'Relationship Spread',
    name_th: 'การทำนายความสัมพันธ์',
    description_th: 'การเปิดไพ่ 5 ใบเกี่ยวกับความสัมพันธ์',
    card_count: 5,
    positions: [
      { name_en: 'You', name_th: 'คุณ', description_th: 'สถานะของคุณ' },
      { name_en: 'Partner', name_th: 'คู่ของคุณ', description_th: 'สถานะของคู่' },
      { name_en: 'Connection', name_th: 'ความเชื่อมโยง', description_th: 'สิ่งที่เชื่อมคุณทั้งสอง' },
      { name_en: 'Challenge', name_th: 'อุปสรรค', description_th: 'สิ่งที่เป็นปัญหา' },
      { name_en: 'Outcome', name_th: 'ผลลัพธ์', description_th: 'อนาคตของความสัมพันธ์' },
    ],
    icon: '💑',
    color: '#F43F5E',
  },
  {
    id: 5,
    slug: 'career-path',
    name_en: 'Career Path',
    name_th: 'เส้นทางอาชีพ',
    description_th: 'การเปิดไพ่ 5 ใบเกี่ยวกับอาชีพการงาน',
    card_count: 5,
    positions: [
      { name_en: 'Current', name_th: 'ปัจจุบัน', description_th: 'สถานการณ์งานปัจจุบัน' },
      { name_en: 'Strengths', name_th: 'จุดแข็ง', description_th: 'จุดแข็งของคุณ' },
      { name_en: 'Challenges', name_th: 'อุปสรรค', description_th: 'สิ่งที่ต้องเอาชนะ' },
      { name_en: 'Advice', name_th: 'คำแนะนำ', description_th: 'สิ่งที่ควรทำ' },
      { name_en: 'Outcome', name_th: 'ผลลัพธ์', description_th: 'อนาคตด้านอาชีพ' },
    ],
    icon: '📈',
    color: '#0EA5E9',
  },
];

// ==============================
// Helper Functions
// ==============================

/**
 * ดึงไพ่ตาม ID
 */
export const getCardById = (id: number): TarotCard | undefined => {
  return ALL_TAROT_CARDS.find((card) => card.id === id);
};

/**
 * ดึงไพ่แบบสุ่ม
 */
export const getRandomCards = (count: number): TarotCard[] => {
  const shuffled = [...ALL_TAROT_CARDS].sort(() => Math.random() - 0.5);
  return shuffled.slice(0, count);
};

/**
 * ดึงหมวดหมู่ตาม slug
 */
export const getCategoryBySlug = (slug: string): TarotCategory | undefined => {
  return TAROT_CATEGORIES.find((cat) => cat.slug === slug);
};

/**
 * ดึงโหมดตาม slug
 */
export const getSpreadBySlug = (slug: string): SpreadType | undefined => {
  return SPREAD_TYPES.find((spread) => spread.slug === slug);
};

/**
 * ดึง Major Arcana เท่านั้น
 */
export const getMajorArcana = (): TarotCard[] => {
  return ALL_TAROT_CARDS.filter((card) => card.type === 'major_arcana');
};

/**
 * ดึง Minor Arcana เท่านั้น
 */
export const getMinorArcana = (): TarotCard[] => {
  return ALL_TAROT_CARDS.filter((card) => card.type === 'minor_arcana');
};

/**
 * ดึงไพ่ตาม Suit
 */
export const getCardsBySuit = (suit: Suit): TarotCard[] => {
  return ALL_TAROT_CARDS.filter((card) => card.suit === suit);
};

/**
 * ดึง URL รูปไพ่ พร้อม fallback ถ้าไม่มีรูป
 */
export const getCardImageUrl = (card: TarotCard): string => {
  if (card.image_url) {
    return card.image_url;
  }
  // สร้าง URL ตามรูปแบบ: major-00-fool.jpg หรือ wands-01-ace.jpg
  const slug = card.name_en.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
  if (card.type === 'major_arcana') {
    return `${CARD_IMAGES.baseUrl}major-${String(card.number).padStart(2, '0')}-${slug}.jpg`;
  } else {
    return `${CARD_IMAGES.baseUrl}${card.suit}-${String(card.number).padStart(2, '0')}-${slug}.jpg`;
  }
};

/**
 * ดึง URL รูปหลังไพ่
 */
export const getCardBackUrl = (): string => {
  return CARD_IMAGES.cardBack;
};

/**
 * ดึง URL รูป default สำหรับแสดงแทนที่เมื่อไม่มีรูป
 */
export const getDefaultCardUrl = (): string => {
  return CARD_IMAGES.defaultCard;
};
