/**
 * ตัวช่วยของโหมดคนขายตลาดสด — ป้าย/สีสถานะออเดอร์, ปุ่มการกระทำ, เวลาแบบไทย
 *
 * ไอคอนเป็นชื่อไอคอนเส้น (components/ui/iconPaths.ts) — ห้ามใช้อีโมจิ
 */

import type { Tone } from '@/theme';
import type { IconName } from '@/components/ui/iconPaths';
import type { FmOrderStatus, FmSellerAction, FmOrderFilter, FmListingStatus } from '@/services/api/taladsodSellerApi';

// =====================================================
// สถานะออเดอร์
// =====================================================

export const FM_STATUS_LABEL: Record<FmOrderStatus, string> = {
  pending: 'ออเดอร์ใหม่ รอรับ',
  accepted: 'รับออเดอร์แล้ว',
  preparing: 'กำลังเตรียม',
  ready: 'พร้อมส่ง/พร้อมรับ',
  delivering: 'ไรเดอร์กำลังส่ง',
  delivered: 'ส่งถึงแล้ว',
  completed: 'สำเร็จ',
  cancelled: 'ยกเลิกแล้ว',
  delivery_failed: 'ส่งไม่สำเร็จ',
};

/** สีสถานะ — ใหม่ = ส้มเตือน, ทำอยู่ = ทอง/ฟ้า, พร้อม = เขียว, จบ = เขียว/แดง */
export const FM_STATUS_TONE: Record<FmOrderStatus, Tone> = {
  pending: 'warning',
  accepted: 'info',
  preparing: 'gold',
  ready: 'success',
  delivering: 'info',
  delivered: 'success',
  completed: 'success',
  cancelled: 'danger',
  delivery_failed: 'danger',
};

/** ไอคอนประจำสถานะ (ชื่อไอคอนเส้น) */
export const FM_STATUS_ICON: Record<FmOrderStatus, IconName> = {
  pending: 'bell-ringing',
  accepted: 'thumbs-up',
  preparing: 'cooking-pot',
  ready: 'check-circle',
  delivering: 'moped',
  delivered: 'package',
  completed: 'seal-check',
  cancelled: 'x-circle',
  delivery_failed: 'warning',
};

/** สถานะที่ยังทำไม่เสร็จ (ใช้เตือนก่อนปิดร้าน) */
export const FM_ACTIVE_STATUSES: FmOrderStatus[] = ['pending', 'accepted', 'preparing', 'ready', 'delivering'];

export const FM_ORDER_FILTERS: Array<{ key: FmOrderFilter; label: string }> = [
  { key: 'all', label: 'ทั้งหมด' },
  { key: 'pending', label: 'ใหม่' },
  { key: 'accepted', label: 'รับแล้ว' },
  { key: 'preparing', label: 'กำลังเตรียม' },
  { key: 'ready', label: 'พร้อมส่ง' },
  { key: 'delivering', label: 'กำลังส่ง' },
  { key: 'delivered', label: 'ส่งถึงแล้ว' },
  { key: 'completed', label: 'สำเร็จ' },
  { key: 'cancelled', label: 'ยกเลิก' },
];

export const isFmOrderFilter = (value: unknown): value is FmOrderFilter =>
  typeof value === 'string' && FM_ORDER_FILTERS.some((f) => f.key === value);

// =====================================================
// ปุ่มการกระทำ
// =====================================================

export interface FmActionLook {
  label: string;
  /** ชื่อไอคอนเส้นบนปุ่ม */
  icon: IconName;
  variant: 'primary' | 'success' | 'danger' | 'secondary';
  /** ข้อความหลังทำสำเร็จ */
  done: string;
}

/** ขั้นถัดไปของออเดอร์ = ปุ่มทอง (การกระทำหลักตามระบบดีไซน์) · ยกเลิก = แดง */
export const fmActionLook = (action: FmSellerAction, deliveryType: 'pickup' | 'rider'): FmActionLook => {
  switch (action) {
    case 'accept':
      return { label: 'รับออเดอร์', icon: 'hand-tap', variant: 'primary', done: 'รับออเดอร์แล้ว ลูกค้าได้รับแจ้งเตือน' };
    case 'prepare':
      return { label: 'เริ่มเตรียม', icon: 'cooking-pot', variant: 'primary', done: 'เริ่มเตรียมแล้ว' };
    case 'ready':
      return deliveryType === 'rider'
        ? { label: 'พร้อมให้ไรเดอร์รับ', icon: 'check-circle', variant: 'primary', done: 'แจ้งไรเดอร์แล้วว่าของพร้อม' }
        : { label: 'พร้อมให้มารับ', icon: 'check-circle', variant: 'primary', done: 'แจ้งลูกค้าแล้วว่าของพร้อมรับ' };
    case 'handover':
      return { label: 'ส่งมอบให้ลูกค้าแล้ว', icon: 'handshake', variant: 'primary', done: 'บันทึกการส่งมอบแล้ว' };
    case 'cancel':
    default:
      return { label: 'ยกเลิก', icon: 'x-circle', variant: 'danger', done: 'ยกเลิกออเดอร์แล้ว' };
  }
};

/** เรียงปุ่มหลักก่อน ยกเลิกไว้ท้ายสุด (เฉพาะ action ที่แอปรู้จัก) */
export const sortFmActions = (actions: string[]): FmSellerAction[] => {
  const order: FmSellerAction[] = ['accept', 'prepare', 'ready', 'handover', 'cancel'];
  return order.filter((a) => actions.includes(a));
};

export const FM_CANCEL_REASONS = [
  'ของหมด',
  'ร้านปิดแล้ว / ไม่สะดวกทำ',
  'ลูกค้าขอยกเลิก',
  'อยู่ไกลเกินส่ง',
  'ติดต่อลูกค้าไม่ได้',
];

// =====================================================
// สถานะสินค้า
// =====================================================

export const FM_LISTING_STATUS: Record<string, { label: string; tone: Tone }> = {
  active: { label: 'ขายอยู่', tone: 'success' },
  sold_out: { label: 'ของหมด', tone: 'warning' },
  draft: { label: 'ฉบับร่าง', tone: 'neutral' },
  expired: { label: 'หมดอายุ', tone: 'neutral' },
  suspended: { label: 'ถูกระงับ', tone: 'danger' },
};

export const FM_LISTING_FILTERS: Array<{ key: FmListingStatus | 'all'; label: string }> = [
  { key: 'all', label: 'ทั้งหมด' },
  { key: 'active', label: 'ขายอยู่' },
  { key: 'sold_out', label: 'ของหมด' },
  { key: 'draft', label: 'ฉบับร่าง' },
];

// =====================================================
// เวลา
// =====================================================

/** "เมื่อสักครู่" / "5 นาทีที่แล้ว" / "2 ชม.ที่แล้ว" / วันที่ */
export const timeAgoTh = (iso: string | number | null | undefined, now: number = Date.now()): string => {
  if (iso === null || iso === undefined || iso === '') return '';
  const t = typeof iso === 'number' ? iso : new Date(iso).getTime();
  if (!Number.isFinite(t)) return '';
  const sec = Math.max(0, Math.round((now - t) / 1000));
  if (sec < 45) return 'เมื่อสักครู่';
  const min = Math.round(sec / 60);
  if (min < 60) return `${min} นาทีที่แล้ว`;
  const hr = Math.round(min / 60);
  if (hr < 24) return `${hr} ชม.ที่แล้ว`;
  try {
    return new Date(t).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
  } catch {
    return new Date(t).toISOString().slice(0, 16).replace('T', ' ');
  }
};

/** "20:30 น." (วันนี้) หรือ "พรุ่งนี้ 02:00 น." */
export const clockTh = (iso: string | null | undefined, now: Date = new Date()): string => {
  if (!iso) return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  const hh = String(d.getHours()).padStart(2, '0');
  const mm = String(d.getMinutes()).padStart(2, '0');
  const sameDay = d.toDateString() === now.toDateString();
  const tomorrow = new Date(now);
  tomorrow.setDate(now.getDate() + 1);
  const prefix = sameDay ? '' : d.toDateString() === tomorrow.toDateString() ? 'พรุ่งนี้ ' : `${d.getDate()}/${d.getMonth() + 1} `;
  return `${prefix}${hh}:${mm} น.`;
};

/** เวลาปิดร้านแบบ HH:MM → Date จริง (ผ่านไปแล้ว = พรุ่งนี้) — ตรงกับกติกาของ server */
export const resolveClosingTime = (hhmm: string, now: Date = new Date()): Date | null => {
  const match = /^(\d{1,2}):(\d{2})$/.exec(hhmm);
  if (!match) return null;
  const h = Number(match[1]);
  const m = Number(match[2]);
  if (h > 23 || m > 59) return null;
  const d = new Date(now);
  d.setHours(h, m, 0, 0);
  if (d.getTime() <= now.getTime()) d.setDate(d.getDate() + 1);
  return d;
};

export const toHHMM = (date: Date): string =>
  `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
