/**
 * กรองเนื้อหาที่ขัดนโยบาย Google Play ออกจากข้อมูลที่ server ส่งมา (ชั้นที่ 2 ต่อจากฝั่ง server)
 *
 * แอป build สโตร์ห้ามมี MLM / สายงาน / rank / PV / คอมมิชชั่นหลายชั้น / คริปโต / โทเคน / ลงทุน / ดูคลิปได้เงิน
 * แต่ server ยังสร้างแจ้งเตือนและรายการกระเป๋าเงินของระบบเครือข่ายบนเว็บอยู่ → แอปต้องไม่แสดงถ้อยคำเหล่านั้น
 *
 * - แจ้งเตือน (กล่องแจ้งเตือน + push ตอนเปิดแอป): ซ่อนทั้งรายการ
 * - รายการกระเป๋าเงิน: ยอดเงินยังแสดงครบ แต่เปลี่ยนชื่อรายการเป็นคำกลางๆ เช่น "ค่าแนะนำ"
 *
 * ไฟล์นี้ไม่มี side effect — ทดสอบได้ตรงๆ
 */

// =====================================================
// รูปแบบที่ถือว่าเป็นเนื้อหาเครือข่าย/การเงินต้องห้าม
// =====================================================

/** ประเภท (type / reference_type) ที่เป็นระบบเครือข่าย — จับแบบ substring */
const MLM_TYPE_SUBSTRING = /commission|downline|upline|unilevel|genealogy|mlm|binary|pool_?bonus|matching_?bonus|rank_?(up|bonus|reward|achieved|changed)/i;

/** ประเภทที่เป็นคำเดี่ยว (คั่นด้วย _ . - \ / หรือเว้นวรรค) — กันจับคำที่บังเอิญมีตัวอักษรเดียวกัน */
const MLM_TYPE_WORD = /(^|[_.\-\\/\s])(rank|team|pv|bv|sponsor|matrix|leadership)([_.\-\\/\s]|$)/i;

/** ประเภทคริปโต / โทเคน / ลงทุน / ดูคลิปได้เงิน */
const RESTRICTED_FINANCE_TYPE = /crypto|tpix|token|staking|stake|invest|coin_?exchange|carbon_?credit|video_?(quest|mission|reward|referral)|watch_?(reward|earn)/i;

/** ถ้อยคำต้องห้ามในหัวข้อ/ข้อความ (ไทย + อังกฤษ) */
const RESTRICTED_TEXT =
  /คอมมิชชั่น|คอมมิชชัน|คอมมิสชั่น|ค่าคอม|ดาวน์ไลน์|ดาวไลน์|อัพไลน์|อัปไลน์|สายงาน|ลูกทีม|แม่ทีม|สร้างทีม|รายได้ไม่จำกัด|เลื่อนขั้น|เลื่อนตำแหน่ง|โบนัสทีม|โบนัสจับคู่|คะแนน\s*PV|คริปโต|โทเคน|โทเค็น|staking|commission|downline|upline|\bMLM\b|\bPV\b|\bTPIX\b/i;

/** ประเภทนี้เป็นเนื้อหาเครือข่าย (MLM) หรือไม่ */
export const isMlmType = (type: unknown): boolean =>
  typeof type === 'string' && type.length > 0 && (MLM_TYPE_SUBSTRING.test(type) || MLM_TYPE_WORD.test(type));

/** ประเภทนี้เป็นคริปโต/ลงทุน/ดูคลิปได้เงินหรือไม่ */
export const isRestrictedFinanceType = (type: unknown): boolean =>
  typeof type === 'string' && type.length > 0 && RESTRICTED_FINANCE_TYPE.test(type);

/** ข้อความนี้มีถ้อยคำต้องห้ามหรือไม่ */
export const hasRestrictedText = (text: unknown): boolean => typeof text === 'string' && RESTRICTED_TEXT.test(text);

// =====================================================
// แจ้งเตือน
// =====================================================

export interface PolicyNotificationLike {
  type?: unknown;
  title?: unknown;
  body?: unknown;
  data?: unknown;
}

/**
 * แจ้งเตือนนี้ต้องซ่อนจากแอปหรือไม่
 * ดูทั้ง type ของกล่องแจ้งเตือน, data.source_type (ประเภทเดิมฝั่งเว็บ), data.type (payload ของ push) และถ้อยคำ
 *
 * @example isRestrictedNotification({ type: 'wallet', title: 'ได้รับคอมมิชชั่น', data: { source_type: 'commission' } }) // true
 * @example isRestrictedNotification({ type: 'rider', title: 'มีงานใหม่ใกล้คุณ' }) // false
 */
export const isRestrictedNotification = (item: PolicyNotificationLike | null | undefined): boolean => {
  if (!item) return false;
  const data = item.data && typeof item.data === 'object' ? (item.data as Record<string, unknown>) : {};
  const types = [item.type, data.source_type, data.type, data.notification_type];
  if (types.some((t) => isMlmType(t) || isRestrictedFinanceType(t))) return true;
  return hasRestrictedText(item.title) || hasRestrictedText(item.body);
};

// =====================================================
// รายการกระเป๋าเงิน
// =====================================================

/** ชื่อกลางๆ ของรายได้จากการแนะนำ (แทนคอมมิชชั่นทุกชั้น) */
export const NEUTRAL_REFERRAL_LABEL = 'ค่าแนะนำ';

/**
 * ชื่อรายการกระเป๋าเงินที่แสดงได้ในแอป
 *
 * @example walletTransactionTitle('จ่ายคอมมิชชั่นดูดวง L2', 'App\\Models\\FortuneCommission', true) // 'ค่าแนะนำ'
 * @example walletTransactionTitle('ค่าส่งงาน #123', 'rider_job', true) // 'ค่าส่งงาน #123'
 */
export const walletTransactionTitle = (title: unknown, referenceType: unknown, isIncome: boolean): string => {
  const text = typeof title === 'string' ? title.trim() : '';
  if (isMlmType(referenceType) || (hasRestrictedText(text) && /คอม|commission/i.test(text))) {
    return isIncome ? NEUTRAL_REFERRAL_LABEL : `ปรับยอด${NEUTRAL_REFERRAL_LABEL}`;
  }
  if (isRestrictedFinanceType(referenceType) || hasRestrictedText(text)) {
    return isIncome ? 'รับเงินเข้ากระเป๋า' : 'ตัดเงินจากกระเป๋า';
  }
  return text || (isIncome ? 'รายรับ' : 'รายจ่าย');
};

/** ชนิดรายการ (reference_type) → คำไทยสั้นๆ (ไม่แสดงชื่อคลาส/คีย์ดิบของ server) */
export const walletReferenceLabel = (referenceType: unknown, isIncome: boolean): string => {
  const fallback = isIncome ? 'รายรับ' : 'รายจ่าย';
  if (typeof referenceType !== 'string' || referenceType.trim() === '') return fallback;
  const type = referenceType.toLowerCase();
  if (isMlmType(type)) return NEUTRAL_REFERRAL_LABEL;
  if (isRestrictedFinanceType(type)) return fallback;
  if (type.includes('withdraw')) return 'ถอนเงิน';
  if (type.includes('topup') || type.includes('deposit')) return 'เติมเงิน';
  if (type.includes('refund')) return 'คืนเงิน';
  if (type.includes('transfer')) return 'โอนเงิน';
  if (type.includes('rider') || type.includes('delivery')) return 'ค่าส่ง';
  if (type.includes('fresh_market') || type.includes('freshmarket')) return 'ตลาดสด';
  if (type.includes('order') || type.includes('purchase') || type.includes('payment')) return 'คำสั่งซื้อ';
  if (type.includes('referral')) return NEUTRAL_REFERRAL_LABEL;
  return fallback;
};
