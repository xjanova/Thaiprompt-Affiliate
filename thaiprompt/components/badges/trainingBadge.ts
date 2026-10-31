/**
 * ตรา "ผ่านการอบรม" (ห้องเรียน) — ชนิดข้อมูล + ตัวแปลงค่าจาก server + view model ของตรา (pure logic ไม่มี UI)
 *
 * สัญญา (CONTRACT.md "Badge payload") — server ใส่มาเป็น optional เสมอ ห้ามเดาเอง:
 *   training_badge  : { certified, role: 'seller'|'rider'|'buyer', label (ไทย), needs_refresh } | null
 *   training_badges : { seller: badge|null, rider: badge|null, buyer: badge|null }  (โปรไฟล์ของฉัน /me)
 *
 * - ไม่มีคีย์ / ค่าแปลก / certified ไม่จริง → null = ไม่แสดงตรา (server รุ่นเก่าไม่ส่งมา)
 * - needs_refresh = เคยผ่าน แต่กฎเปลี่ยน (rules_version ใหม่) → ตราจาง + ป้าย "ต้องอบรมเพิ่ม"
 *
 * @example
 * const badge = parseTrainingBadge(order.rider?.rider?.training_badge, 'rider');
 * {badge && <TrainingSeal size={15} needsRefresh={badge.needs_refresh} label={badge.label} />}
 */

export type TrainingRole = 'seller' | 'rider' | 'buyer';

export interface TrainingBadge {
  certified: boolean;
  role: TrainingRole;
  /** ข้อความไทยของตรา เช่น "ผ่านการอบรมไรเดอร์" */
  label: string;
  /** ผ่านกฎรุ่นเก่า ต้องอบรมเพิ่ม */
  needs_refresh: boolean;
}

export interface TrainingBadges {
  seller: TrainingBadge | null;
  rider: TrainingBadge | null;
  buyer: TrainingBadge | null;
}

/** ลำดับบทบาทมาตรฐาน (ร้าน → ไรเดอร์ → ผู้ซื้อ) */
export const TRAINING_ROLES: readonly TrainingRole[] = ['seller', 'rider', 'buyer'];

/** ชื่อบทบาทภาษาไทย */
export const TRAINING_ROLE_NAME: Record<TrainingRole, string> = {
  seller: 'ร้านค้า',
  rider: 'ไรเดอร์',
  buyer: 'ผู้ซื้อ',
};

/** ข้อความตราเมื่อ server ไม่ส่ง label ภาษาไทยมา */
export const TRAINING_BADGE_LABEL: Record<TrainingRole, string> = {
  seller: 'ผ่านการอบรมร้านค้า',
  rider: 'ผ่านการอบรมไรเดอร์',
  buyer: 'ผ่านการอบรมผู้ซื้อ',
};

/** ป้ายเล็กใต้/ข้างตราที่ต้องอบรมเพิ่ม */
export const NEEDS_REFRESH_LABEL = 'ต้องอบรมเพิ่ม';

const THAI_CHARS = /[฀-๿]/;

/** ค่าจริงจาก server (true / 1 / "1" / "true") */
export const isTruthyFlag = (value: unknown): boolean =>
  value === true || value === 1 || value === '1' || value === 'true';

/** แปลงค่าบทบาท (ไม่รู้จัก = null) — รองรับคำพ้อง merchant → seller */
export const parseTrainingRole = (value: unknown): TrainingRole | null => {
  if (typeof value !== 'string') return null;
  const v = value.trim().toLowerCase();
  if (v === 'seller' || v === 'merchant' || v === 'shop') return 'seller';
  if (v === 'rider') return 'rider';
  if (v === 'buyer' || v === 'customer') return 'buyer';
  return null;
};

/**
 * แปลง training_badge ดิบจาก server → TrainingBadge (หรือ null = ไม่แสดงตรา)
 *
 * @param raw ค่าดิบ
 * @param fallbackRole บทบาทที่รู้จากตำแหน่งที่วาง (เช่น rider object = 'rider') เมื่อ server ไม่ส่ง role
 */
export const parseTrainingBadge = (raw: unknown, fallbackRole?: TrainingRole): TrainingBadge | null => {
  if (!raw || typeof raw !== 'object' || Array.isArray(raw)) return null;
  const obj = raw as Record<string, unknown>;
  if (!isTruthyFlag(obj.certified)) return null;
  const role = parseTrainingRole(obj.role) ?? fallbackRole ?? null;
  if (!role) return null;
  const label =
    typeof obj.label === 'string' && obj.label.trim().length > 0 && obj.label.length <= 80 && THAI_CHARS.test(obj.label)
      ? obj.label.trim()
      : TRAINING_BADGE_LABEL[role];
  return { certified: true, role, label, needs_refresh: isTruthyFlag(obj.needs_refresh) };
};

/** แปลง training_badges ของโปรไฟล์ (ไม่มี/ไม่ใช่ object = null) */
export const parseTrainingBadges = (raw: unknown): TrainingBadges | null => {
  if (!raw || typeof raw !== 'object' || Array.isArray(raw)) return null;
  const obj = raw as Record<string, unknown>;
  return {
    seller: parseTrainingBadge(obj.seller, 'seller'),
    rider: parseTrainingBadge(obj.rider, 'rider'),
    buyer: parseTrainingBadge(obj.buyer, 'buyer'),
  };
};

/** ข้อความสำหรับโปรแกรมอ่านหน้าจอ (รวม "ต้องอบรมเพิ่ม") */
export const trainingBadgeA11yLabel = (badge: Pick<TrainingBadge, 'label' | 'needs_refresh'>): string =>
  badge.needs_refresh ? `${badge.label} · ${NEEDS_REFRESH_LABEL}` : badge.label;

/**
 * ตราเดียวที่แสดงข้างชื่อในหน้าโปรไฟล์ของฉัน
 * เลือกตราที่ยังไม่หมดอายุก่อน (ร้าน → ไรเดอร์ → ผู้ซื้อ) · มีแต่ตราที่ต้องอบรมเพิ่ม = ตราจาง · ไม่มีเลย = null
 */
export const pickProfileBadge = (badges: TrainingBadges | null | undefined): TrainingBadge | null => {
  if (!badges) return null;
  const list = TRAINING_ROLES.map((r) => badges[r]).filter((b): b is TrainingBadge => !!b && b.certified);
  return list.find((b) => !b.needs_refresh) ?? list[0] ?? null;
};

/** บทบาทที่ผ่านแล้ว (ไม่นับที่ต้องอบรมเพิ่ม) */
export const certifiedRoles = (badges: TrainingBadges | null | undefined): TrainingRole[] =>
  badges ? TRAINING_ROLES.filter((r) => !!badges[r]?.certified && !badges[r]?.needs_refresh) : [];
