/**
 * ตัวช่วยของหน้าตลาดสด — เวลาไทย, ระยะทาง, ป้ายสถานะ, ไทม์ไลน์ออเดอร์
 *
 * ไอคอนในไทม์ไลน์เป็น "ชื่อไอคอน" ของชุด Phosphor (components/ui/iconPaths.ts) — วาดด้วย <Icon/> ไม่ใช่อีโมจิ
 */

import type { Tone } from '@/theme';
import type { TimelineStep } from '@/components/shop';
import { formatThaiDateTime } from '@/components/shop';
import { formatBaht } from '@/components/ui';
import { calculateDistance, formatDistance } from '@/services/location';
import { FM_ORDER_STATUS_LABEL, type FmOrder } from '@/services/api/taladsodApi';

/** เวลาแบบ "20:30 น." (ค่าไม่ถูกต้อง = '') */
export const formatThaiTime = (iso: string | null | undefined): string => {
  if (!iso) return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  const hh = String(d.getHours()).padStart(2, '0');
  const mm = String(d.getMinutes()).padStart(2, '0');
  return `${hh}:${mm} น.`;
};

/** "อัปเดตเมื่อ 30 วินาทีก่อน" / "5 นาทีก่อน" */
export const timeAgoText = (iso: string | null | undefined, now: number = Date.now()): string => {
  if (!iso) return '';
  const t = new Date(iso).getTime();
  if (Number.isNaN(t)) return '';
  const sec = Math.max(0, Math.round((now - t) / 1000));
  if (sec < 10) return 'เมื่อสักครู่';
  if (sec < 60) return `${sec} วินาทีก่อน`;
  const min = Math.round(sec / 60);
  if (min < 60) return `${min} นาทีก่อน`;
  const hr = Math.round(min / 60);
  if (hr < 24) return `${hr} ชั่วโมงก่อน`;
  return formatThaiDateTime(iso);
};

/** ระยะจากตำแหน่งผู้ใช้ถึงจุด (ข้อความ) — ไม่มีข้อมูล = null */
export const distanceText = (
  from: { latitude: number; longitude: number } | null | undefined,
  to: { latitude: number; longitude: number } | null | undefined
): string | null => {
  if (!from || !to) return null;
  const km = calculateDistance(from.latitude, from.longitude, to.latitude, to.longitude);
  return Number.isFinite(km) ? formatDistance(km) : null;
};

/** "+฿10" / "ฟรี" สำหรับตัวเลือก */
export const optionDeltaText = (delta: number): string => (delta > 0 ? `+${formatBaht(delta)}` : delta < 0 ? formatBaht(delta) : 'ไม่บวกเพิ่ม');

/** โทนสีของสถานะออเดอร์ตลาดสด */
export const FM_ORDER_TONE: Record<string, Tone> = {
  pending: 'warning',
  accepted: 'info',
  preparing: 'gold',
  ready: 'gold',
  delivering: 'info',
  delivered: 'success',
  completed: 'success',
  cancelled: 'danger',
  delivery_failed: 'danger',
};

export const fmOrderLabel = (order: Pick<FmOrder, 'order_status' | 'status_label'>): string =>
  order.status_label || FM_ORDER_STATUS_LABEL[order.order_status] || 'กำลังดำเนินการ';

/** ไทม์ไลน์สถานะออเดอร์ตลาดสด (ส่งด้วยไรเดอร์ / นัดรับที่ร้าน) */
export const buildFmTimeline = (order: FmOrder): TimelineStep[] => {
  const isRider = order.delivery_type === 'rider';
  const status = order.order_status;

  if (status === 'cancelled' || status === 'delivery_failed') {
    return [
      { key: 'placed', label: 'สั่งแล้ว', caption: formatThaiDateTime(order.created_at), icon: 'receipt', state: 'done' },
      {
        key: 'end',
        label: status === 'cancelled' ? 'ยกเลิกแล้ว' : 'ส่งไม่สำเร็จ',
        caption: [formatThaiDateTime(order.cancelled_at), order.cancel_reason].filter(Boolean).join(' · '),
        icon: status === 'cancelled' ? 'x-circle' : 'warning',
        state: 'failed',
      },
    ];
  }

  const flow = isRider
    ? ['pending', 'accepted', 'preparing', 'ready', 'delivering', 'delivered', 'completed']
    : ['pending', 'accepted', 'preparing', 'ready', 'delivered', 'completed'];
  let idx = flow.indexOf(status);
  if (idx < 0) idx = 0;

  const stateOf = (i: number): TimelineStep['state'] =>
    i < idx || status === 'completed' ? 'done' : i === idx ? 'current' : 'todo';

  const labels: Record<string, { label: string; icon: string; caption?: string }> = {
    pending: { label: idx === 0 ? 'รอร้านรับออเดอร์' : 'สั่งแล้ว', icon: 'receipt', caption: formatThaiDateTime(order.created_at) },
    accepted: { label: 'ร้านรับออเดอร์แล้ว', icon: 'thumbs-up', caption: formatThaiDateTime(order.accepted_at) },
    preparing: { label: idx === 2 ? 'ร้านกำลังทำให้อยู่' : 'ทำเสร็จแล้ว', icon: 'cooking-pot' },
    ready: {
      label: isRider ? (idx === 3 ? 'รอไรเดอร์มารับ' : 'ไรเดอร์รับของแล้ว') : idx === 3 ? 'พร้อมแล้ว มารับได้เลย' : 'รับของแล้ว',
      icon: isRider ? 'moped' : 'shopping-bag-open',
      caption: formatThaiDateTime(order.ready_at),
    },
    delivering: { label: idx === 4 ? 'ไรเดอร์กำลังไปส่ง' : 'ไรเดอร์ออกส่งแล้ว', icon: 'navigation-arrow' },
    delivered: {
      label: isRider ? 'ส่งถึงแล้ว' : 'ร้านส่งมอบแล้ว',
      icon: 'map-pin',
      caption: formatThaiDateTime(order.delivered_at),
    },
    completed: {
      label: status === 'completed' ? 'สำเร็จ ขอบคุณที่อุดหนุน' : 'ยืนยันรับของ',
      icon: 'seal-check',
      caption: formatThaiDateTime(order.completed_at),
    },
  };

  return flow.map((key, i) => ({
    key,
    label: labels[key].label,
    caption: labels[key].caption || undefined,
    icon: labels[key].icon,
    state: stateOf(i),
  }));
};
