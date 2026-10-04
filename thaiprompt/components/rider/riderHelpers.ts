/**
 * ตัวช่วยของหน้าไรเดอร์ — ข้อความสถานะ, ขั้นตอนงาน, นำทาง, โทร, ตรวจแบบฟอร์มสมัคร
 *
 * ทุกข้อความที่ผู้ใช้เห็นเป็นภาษาไทยสั้นๆ เป็นกันเอง
 * ตัวเลขจาก API ผ่าน num() ก่อนใช้เสมอ (กันค่า null/string หลุดมา)
 */

import { Alert, Linking, Platform } from 'react-native';
import { num } from '@/services/api/client';
import type {
  RiderFailReason,
  RiderJobPoint,
  RiderJobStatus,
  RiderVehicleType,
} from '@/services/api/riderApi';
import type { Tone } from '@/theme';
import type { IconName } from '@/components/ui/Icon';

// =====================================================
// สถานะงาน
// =====================================================

export const JOB_STATUS_TONE: Record<RiderJobStatus, Tone> = {
  pending: 'info',
  accepted: 'gold',
  picking_up: 'gold',
  picked_up: 'warning',
  delivering: 'warning',
  delivered: 'success',
  completed: 'success',
  cancelled: 'neutral',
  failed: 'danger',
  awaiting_release: 'gold',
};

export const JOB_STATUS_LABEL: Record<RiderJobStatus, string> = {
  pending: 'รอไรเดอร์รับงาน',
  accepted: 'รับงานแล้ว',
  picking_up: 'กำลังไปรับของ',
  picked_up: 'รับของแล้ว',
  delivering: 'กำลังไปส่ง',
  delivered: 'ส่งถึงแล้ว',
  completed: 'ส่งสำเร็จ',
  cancelled: 'ยกเลิกแล้ว',
  failed: 'ส่งไม่สำเร็จ',
  awaiting_release: 'รอปลดเงิน',
};

/** ขั้นตอนบนไทม์ไลน์ (ตรงกับ state machine ของ server) · icon = ชื่อไอคอนเส้น */
export const JOB_STEPS: Array<{ key: string; label: string; icon: IconName }> = [
  { key: 'accepted', label: 'รับงาน', icon: 'check-circle' },
  { key: 'picking_up', label: 'ไปรับของ', icon: 'moped' },
  { key: 'picked_up', label: 'รับของแล้ว', icon: 'package' },
  { key: 'delivering', label: 'ไปส่ง', icon: 'navigation-arrow' },
  { key: 'completed', label: 'ส่งสำเร็จ', icon: 'seal-check' },
];

/** ขั้นปัจจุบันบนไทม์ไลน์ (-1 = ยังไม่รับงาน) */
export const stepIndexForStatus = (status: RiderJobStatus | string): number => {
  switch (status) {
    case 'accepted':
      return 0;
    case 'picking_up':
      return 1;
    case 'picked_up':
      return 2;
    case 'delivering':
      return 3;
    case 'delivered':
    case 'completed':
    case 'awaiting_release':
      return 4;
    default:
      return -1;
  }
};

export const isActiveJobStatus = (status: string | null | undefined): boolean =>
  status === 'accepted' || status === 'picking_up' || status === 'picked_up' || status === 'delivering';

/**
 * งานจบในมุมไรเดอร์ (หยุดแชร์ตำแหน่ง · รับงานใหม่ได้)
 * awaiting_release = วางของแล้วด้วยทางสำรอง รอปลดเงิน — ไรเดอร์ไม่ต้องทำอะไรต่อ
 */
export const isFinishedJobStatus = (status: string | null | undefined): boolean =>
  status === 'completed' ||
  status === 'delivered' ||
  status === 'cancelled' ||
  status === 'failed' ||
  status === 'awaiting_release';

// =====================================================
// รายได้ / ระยะทาง (ไรเดอร์รอบ 2)
// =====================================================

/** ยอดที่ไรเดอร์ได้จริงของงานนี้ = rider_total จาก server (ค่าส่ง + โบนัสร้าน) · server รุ่นเก่า = rider_earnings */
export const riderTotalOf = (job: { rider_earnings: number; earnings_breakdown?: { rider_total?: unknown } | null }): number => {
  const total = num(job.earnings_breakdown?.rider_total, NaN);
  return Number.isFinite(total) ? total : num(job.rider_earnings);
};

/** โบนัสจากร้านของงานนี้ (0 = ไม่มี) */
export const shopBonusOf = (job: { earnings_breakdown?: { shop_bonus?: unknown } | null }): number =>
  Math.max(0, num(job.earnings_breakdown?.shop_bonus, 0));

/** ระยะคิดตามถนนจริงหรือไม่ (valhalla/google) — haversine = เส้นตรงโดยประมาณ */
export const isRoadDistance = (source: string | null | undefined): boolean => source === 'valhalla' || source === 'google';

/** ป้ายที่มาของระยะทาง (ไม่มีข้อมูล = null) */
export const distanceSourceLabel = (source: string | null | undefined): string | null => {
  if (!source) return null;
  return isRoadDistance(source) ? 'ตามถนนจริง' : 'ระยะโดยประมาณ';
};

/** ระยะเป็นเมตร เช่น "120 ม." / "1.2 กม." */
export const formatMeters = (value: unknown): string | null => {
  const m = num(value, -1);
  if (m < 0) return null;
  if (m < 1000) return `${Math.round(m)} ม.`;
  return `${(m / 1000).toFixed(1)} กม.`;
};

// =====================================================
// ส่งมอบของ — ข้อความ error ภาษาไทย (ใส่ตัวเลขจาก server ให้ชัดว่าต้องทำอะไรต่อ)
// =====================================================

/** เวลา HH:MM:SS จาก ISO */
const clockOf = (iso: unknown): string => {
  if (typeof iso !== 'string') return '';
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return '';
  return [date.getHours(), date.getMinutes(), date.getSeconds()].map((n) => String(n).padStart(2, '0')).join(':');
};

/**
 * ข้อความ error ของการส่งมอบ
 * @param result ผลจาก API (code + message ไทยจาก client + data)
 * @param geofenceM รัศมีที่ต้องอยู่ใกล้จุดส่ง (เมตร) — ใช้ประกอบข้อความ "ห่างเกินไป"
 */
export const handoverErrorText = (
  result: { code: string; message: string; data?: any },
  geofenceM?: number | null
): { title: string; message: string } => {
  const data = result.data && typeof result.data === 'object' ? result.data : {};
  switch (result.code) {
    case 'TOO_FAR_FROM_DROPOFF': {
      const distance = formatMeters(data.distance_m);
      const limit = geofenceM && geofenceM > 0 ? `${Math.round(geofenceM)} ม.` : '150 ม.';
      return {
        title: 'ยังไม่ถึงจุดส่ง',
        message: distance
          ? `ตอนนี้คุณอยู่ห่างจุดส่ง ${distance} ต้องอยู่ภายใน ${limit} ขยับเข้าใกล้แล้วลองใหม่นะ`
          : `ต้องอยู่ห่างจุดส่งไม่เกิน ${limit} ขยับเข้าใกล้แล้วลองใหม่นะ`,
      };
    }
    case 'LOCATION_REQUIRED':
    case 'INVALID_LOCATION':
      return { title: 'ต้องใช้ตำแหน่ง GPS', message: 'เปิด GPS และอนุญาตตำแหน่ง เพื่อยืนยันว่าคุณอยู่ที่จุดส่ง แล้วลองใหม่นะ' };
    case 'WAIT_NOT_OVER': {
      const at = clockOf(data.wait_until);
      return {
        title: 'ยังรอไม่ครบเวลา',
        message: at ? `ถ่ายรูปรอบ 2 ได้ตอน ${at} น. ระหว่างนี้ลองโทรหาลูกค้าอีกครั้งนะ` : 'รอให้ครบเวลาก่อน แล้วค่อยถ่ายรูปรอบ 2 นะ',
      };
    }
    case 'HANDOVER_CODE_LOCKED': {
      const at = clockOf(data.locked_until ?? data.retry_at);
      return {
        title: 'กรอกรหัสผิดหลายครั้ง',
        message: `ระบบพักการกรอกรหัสชั่วคราว${at ? ` ลองใหม่ได้ตอน ${at} น.` : ''} ระหว่างนี้ให้ลูกค้าสแกน QR แทนนะ`,
      };
    }
    case 'HANDOVER_CODE_INVALID': {
      const left = num(data.attempts_left, -1);
      return {
        title: 'รหัสไม่ถูกต้อง',
        message: `ตรวจรหัส 6 หลักกับลูกค้าอีกครั้ง${left >= 0 ? ` (กรอกได้อีก ${left} ครั้ง)` : ''}`,
      };
    }
    case 'HANDOVER_TOKEN_EXPIRED':
      return { title: 'QR หมดอายุแล้ว', message: 'QR ของลูกค้าเปลี่ยนใหม่ทุกไม่กี่วินาที ให้ลูกค้าเปิดหน้าส่งมอบค้างไว้ แล้วสแกนอีกครั้งนะ' };
    case 'HANDOVER_TOKEN_INVALID':
      return { title: 'QR นี้ใช้ไม่ได้', message: 'ให้ลูกค้าเปิด QR ส่งมอบของออเดอร์นี้ในแอป แล้วสแกนใหม่ หรือกรอกรหัส 6 หลักแทน' };
    case 'HANDOVER_NOT_READY':
      return { title: 'ยังส่งมอบไม่ได้', message: 'รับของจากร้านและกดเริ่มไปส่งก่อน แล้วค่อยส่งมอบให้ลูกค้านะ' };
    case 'HANDOVER_FINAL':
      return { title: 'ส่งมอบเรียบร้อยแล้ว', message: 'งานนี้ปิดการส่งมอบไปแล้ว ระบบอัปเดตสถานะให้แล้ว' };
    case 'HANDOVER_REQUIRED':
      return { title: 'ต้องส่งมอบด้วย QR', message: 'งานนี้ต้องสแกน QR กับลูกค้า หรือถ่ายรูป 2 รอบเมื่อลูกค้าไม่อยู่' };
    default:
      return { title: 'ทำรายการไม่สำเร็จ', message: result.message };
  }
};

// =====================================================
// เหตุผลส่งไม่สำเร็จ / คืนงาน
// =====================================================

export const FAIL_REASONS: Array<{ code: RiderFailReason; label: string; icon: IconName }> = [
  { code: 'customer_unreachable', label: 'ติดต่อลูกค้าไม่ได้', icon: 'phone' },
  { code: 'wrong_address', label: 'ที่อยู่ไม่ถูกต้อง', icon: 'map-trifold' },
  { code: 'customer_refused', label: 'ลูกค้าไม่รับของ', icon: 'prohibit' },
  { code: 'item_damaged', label: 'สินค้าเสียหาย', icon: 'package' },
  { code: 'other', label: 'อื่นๆ', icon: 'pencil-simple' },
];

export const RELEASE_REASONS: string[] = ['ติดธุระด่วน', 'รถเสีย / ยางแตก', 'ร้านยังไม่พร้อม', 'ไกลเกินไป'];

// =====================================================
// ยานพาหนะ
// =====================================================

/**
 * icon = ชื่อไอคอนเส้น — ชุดไอคอนยังไม่มีรถยนต์/จักรยาน/คนเดิน จึงใช้ตัวที่ใกล้ที่สุดไปก่อน
 * (เพิ่ม car / bicycle / person-simple-walk ใน components/ui/iconPaths.ts แล้วค่อยเปลี่ยนตรงนี้)
 */
export const VEHICLES: Array<{ value: RiderVehicleType; label: string; icon: IconName; needsPlate: boolean }> = [
  { value: 'motorcycle', label: 'มอเตอร์ไซค์', icon: 'motorcycle', needsPlate: true },
  { value: 'car', label: 'รถยนต์', icon: 'truck', needsPlate: true },
  { value: 'bicycle', label: 'จักรยาน', icon: 'road-horizon', needsPlate: false },
  { value: 'walk', label: 'เดินเท้า', icon: 'path', needsPlate: false },
];

// =====================================================
// แสดงผลตัวเลข/เวลา
// =====================================================

/** ระยะทาง เช่น "850 ม." / "3.2 กม." (ค่าไม่ถูกต้อง = null) */
export const formatKm = (value: unknown): string | null => {
  if (value === null || value === undefined || value === '') return null;
  const km = num(value, -1);
  if (km < 0) return null;
  if (km < 1) return `${Math.max(50, Math.round((km * 1000) / 50) * 50)} ม.`;
  return `${km.toFixed(1)} กม.`;
};

/** เวลาเดินทาง เช่น "~12 นาที" */
export const formatMinutes = (value: unknown): string | null => {
  const minutes = Math.round(num(value, 0));
  if (minutes <= 0) return null;
  if (minutes < 60) return `~${minutes} นาที`;
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  return m ? `~${h} ชม. ${m} นาที` : `~${h} ชม.`;
};

/** วันเวลาแบบไทยสั้นๆ เช่น "25 ก.ย. 14:30" */
export const formatThaiDateTime = (iso: string | null | undefined): string => {
  if (!iso) return '';
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return '';
  try {
    return date.toLocaleString('th-TH', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return '';
  }
};

/** เวลาอย่างเดียว เช่น "14:30" */
export const formatTime = (iso: string | null | undefined): string => {
  if (!iso) return '';
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return '';
  const hh = String(date.getHours()).padStart(2, '0');
  const mm = String(date.getMinutes()).padStart(2, '0');
  return `${hh}:${mm}`;
};

/** "เมื่อ x นาทีที่แล้ว" */
export const formatAgo = (iso: string | null | undefined): string => {
  if (!iso) return '';
  const time = new Date(iso).getTime();
  if (Number.isNaN(time)) return '';
  const seconds = Math.max(0, Math.round((Date.now() - time) / 1000));
  if (seconds < 60) return 'เมื่อสักครู่';
  const minutes = Math.round(seconds / 60);
  if (minutes < 60) return `${minutes} นาทีที่แล้ว`;
  const hours = Math.round(minutes / 60);
  if (hours < 24) return `${hours} ชม.ที่แล้ว`;
  return formatThaiDateTime(iso);
};

// =====================================================
// นำทาง / โทร
// =====================================================

const hasCoords = (lat: unknown, lng: unknown): boolean => {
  const la = num(lat, NaN);
  const lo = num(lng, NaN);
  return Number.isFinite(la) && Number.isFinite(lo) && !(la === 0 && lo === 0);
};

/**
 * เปิด Google Maps นำทางไปจุดหมาย (ไม่มีพิกัด → ค้นหาด้วยที่อยู่)
 */
export const openNavigation = async (
  point: Pick<RiderJobPoint, 'latitude' | 'longitude'> & { address?: string | null; name?: string | null }
): Promise<void> => {
  let url: string | null = null;
  if (hasCoords(point.latitude, point.longitude)) {
    const dest = `${num(point.latitude)},${num(point.longitude)}`;
    url = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(dest)}&travelmode=driving`;
  } else {
    const query = [point.name, point.address].filter(Boolean).join(' ').trim();
    if (query) {
      url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
    }
  }

  if (!url) {
    Alert.alert('ยังไม่มีตำแหน่ง', 'จุดนี้ยังไม่มีพิกัดหรือที่อยู่ให้นำทาง');
    return;
  }

  try {
    await Linking.openURL(url);
  } catch {
    Alert.alert('เปิดแผนที่ไม่ได้', 'ติดตั้งหรือเปิดใช้ Google Maps แล้วลองใหม่นะ');
  }
};

/**
 * โทรออกผ่านแอปโทรศัพท์ของเครื่อง (ไม่ต้องใช้สิทธิ์ไมโครโฟน)
 */
export const callPhone = async (phone: string | null | undefined): Promise<void> => {
  const clean = String(phone || '').replace(/[^\d+]/g, '');
  if (clean.length < 3) {
    Alert.alert('ไม่มีเบอร์โทร', 'จุดนี้ยังไม่มีเบอร์ให้โทร');
    return;
  }
  try {
    await Linking.openURL(`tel:${clean}`);
  } catch {
    Alert.alert('โทรไม่ได้', Platform.OS === 'ios' ? 'เครื่องนี้โทรออกไม่ได้' : 'เปิดแอปโทรศัพท์ไม่ได้ ลองใหม่นะ');
  }
};

// =====================================================
// ตรวจแบบฟอร์มสมัครไรเดอร์
// =====================================================

/** ตัดขีด/ช่องว่างออกจากตัวเลข */
export const digitsOnly = (value: string): string => value.replace(/\D/g, '');

/** เบอร์มือถือไทย 0 + 8-9 หลัก */
export const isValidThaiPhone = (value: string): boolean => /^0\d{8,9}$/.test(digitsOnly(value));

/** เลขบัตรประชาชน 13 หลัก + checksum */
export const isValidThaiId = (value: string): boolean => {
  const id = digitsOnly(value);
  if (!/^\d{13}$/.test(id)) return false;
  let sum = 0;
  for (let i = 0; i < 12; i += 1) {
    sum += Number(id[i]) * (13 - i);
  }
  const check = (11 - (sum % 11)) % 10;
  return check === Number(id[12]);
};

/** จัดรูปเลขบัตร 1-2345-67890-12-3 ระหว่างพิมพ์ */
export const formatThaiIdInput = (value: string): string => {
  const d = digitsOnly(value).slice(0, 13);
  const parts = [d.slice(0, 1), d.slice(1, 5), d.slice(5, 10), d.slice(10, 12), d.slice(12, 13)];
  return parts.filter((p) => p.length > 0).join('-');
};

/** จัดรูปวันเกิด วว/ดด/ปปปป ระหว่างพิมพ์ */
export const formatBirthDateInput = (value: string): string => {
  const d = digitsOnly(value).slice(0, 8);
  if (d.length <= 2) return d;
  if (d.length <= 4) return `${d.slice(0, 2)}/${d.slice(2)}`;
  return `${d.slice(0, 2)}/${d.slice(2, 4)}/${d.slice(4)}`;
};

/**
 * แปลงวันเกิด วว/ดด/ปปปป (รับได้ทั้ง พ.ศ. และ ค.ศ.) → YYYY-MM-DD
 * @returns null ถ้าวันที่ไม่ถูกต้อง
 */
export const parseBirthDate = (value: string): string | null => {
  const match = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(value.trim());
  if (!match) return null;
  const day = Number(match[1]);
  const month = Number(match[2]);
  let year = Number(match[3]);
  if (year > 2400) year -= 543; // พ.ศ. → ค.ศ.
  if (year < 1900 || month < 1 || month > 12 || day < 1 || day > 31) return null;
  const date = new Date(year, month - 1, day);
  if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null;
  return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
};

/** อายุ (ปีเต็ม) จาก YYYY-MM-DD */
export const ageFromIsoDate = (iso: string): number => {
  const [y, m, d] = iso.split('-').map(Number);
  const today = new Date();
  let age = today.getFullYear() - y;
  if (today.getMonth() + 1 < m || (today.getMonth() + 1 === m && today.getDate() < d)) age -= 1;
  return age;
};

/** YYYY-MM-DD → วว/ดด/ปปปป (พ.ศ.) สำหรับเติมฟอร์ม */
export const isoToThaiBirthInput = (iso: string | null | undefined): string => {
  if (!iso) return '';
  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso);
  if (!match) return '';
  return `${match[3]}/${match[2]}/${Number(match[1]) + 543}`;
};
