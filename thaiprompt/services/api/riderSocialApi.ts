/**
 * Rider Social API — หัวใจ / ไรเดอร์คนโปรด / ไรเดอร์ใกล้ฉัน / ล็อกเรียก (ไรเดอร์รอบ 2)
 *
 *   GET  /riders/nearby?lat=&lng=     → { riders: NearbyRider[], radius_km, fuzz_m, lock_min_hearts }
 *   GET  /riders/favorites            → { riders: FavoriteRider[], lock_min_hearts }
 *   GET  /riders/{id}                 → { rider: PersonCard + {completed_jobs, joined_at} }
 *   POST /orders/{source}/{id}/heart  → { hearts_from_me, hearts_total, can_lock, already }  (409 HEART_NOT_ALLOWED)
 *
 * หลักการ
 *   - พิกัดไรเดอร์ที่ได้มาเป็นแบบ "คลาดเคลื่อนตั้งใจ" (~200 ม.) จาก server แล้ว แอปห้ามพยายามหาตำแหน่งจริง
 *   - สิทธิ์ล็อกเรียก (can_lock) ตัดสินที่ server — แอปแค่แสดง และส่ง preferred_rider_id ตอนสั่ง
 *   - พิกัดของผู้ซื้อส่งแค่ทศนิยม 4 ตำแหน่ง (~11 ม.) พอสำหรับหาไรเดอร์ใกล้ๆ
 */

import { apiGet, apiPost, num, type ApiResult } from './client';
import { normalizePersonCard, type HandoverSource, type PersonCard } from './handoverApi';

// =====================================================
// Types
// =====================================================

export interface NearbyRider extends PersonCard {
  approx_latitude: number;
  approx_longitude: number;
  /** ระยะโดยประมาณ (กม. ทศนิยม 1) */
  distance_km: number | null;
  status: 'available' | 'busy';
  is_favorite: boolean;
}

export interface FavoriteRider extends PersonCard {
  online: boolean;
  last_order_at: string | null;
}

export interface NearbyRidersResult {
  riders: NearbyRider[];
  radius_km: number;
  fuzz_m: number;
  lock_min_hearts: number;
}

export interface FavoriteRidersResult {
  riders: FavoriteRider[];
  lock_min_hearts: number;
}

export interface RiderProfile extends PersonCard {
  completed_jobs: number | null;
  joined_at: string | null;
}

export interface HeartResult {
  hearts_from_me: number;
  hearts_total: number;
  can_lock: boolean;
  /** ให้หัวใจออเดอร์นี้ไปแล้วก่อนหน้า (กดซ้ำ) */
  already: boolean;
}

/** ค่าเริ่มต้นของเกณฑ์หัวใจขั้นต่ำ (server ส่งค่าจริงมาทุกครั้ง) */
export const DEFAULT_LOCK_MIN_HEARTS = 11;

// =====================================================
// แปลงข้อมูล
// =====================================================

const str = (value: unknown): string | null => (typeof value === 'string' && value.length > 0 ? value : null);
const bool = (value: unknown): boolean => value === true || value === 1 || value === '1';

const validCoord = (lat: number, lng: number): boolean =>
  Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180 && !(lat === 0 && lng === 0);

const normalizeNearby = (raw: any): NearbyRider | null => {
  const card = normalizePersonCard(raw);
  if (!card) return null;
  const lat = Number(raw?.approx_latitude);
  const lng = Number(raw?.approx_longitude);
  if (!validCoord(lat, lng)) return null;
  const distance = raw?.distance_km === null || raw?.distance_km === undefined ? null : num(raw.distance_km, NaN);
  return {
    ...card,
    approx_latitude: lat,
    approx_longitude: lng,
    distance_km: distance !== null && Number.isFinite(distance) ? distance : null,
    status: raw?.status === 'busy' ? 'busy' : 'available',
    is_favorite: bool(raw?.is_favorite),
  };
};

const normalizeFavorite = (raw: any): FavoriteRider | null => {
  const card = normalizePersonCard(raw);
  if (!card) return null;
  return { ...card, online: bool(raw?.online), last_order_at: str(raw?.last_order_at) };
};

const lockMin = (value: unknown): number => {
  const n = Math.round(num(value, DEFAULT_LOCK_MIN_HEARTS));
  return n > 0 ? n : DEFAULT_LOCK_MIN_HEARTS;
};

const mapResult = <A, B>(result: ApiResult<A>, fn: (data: A) => B): ApiResult<B> =>
  result.success ? { ...result, data: fn(result.data) } : result;

// =====================================================
// API
// =====================================================

/** GET /riders/nearby — พิกัดของผู้ซื้อปัดเหลือ 4 ตำแหน่ง */
export const getNearbyRiders = async (lat: number, lng: number): Promise<ApiResult<NearbyRidersResult>> =>
  mapResult(
    await apiGet<any>('/riders/nearby', { lat: Number(lat.toFixed(4)), lng: Number(lng.toFixed(4)) }),
    (raw) => ({
      riders: (Array.isArray(raw?.riders) ? raw.riders : [])
        .map(normalizeNearby)
        .filter((r: NearbyRider | null): r is NearbyRider => r !== null),
      radius_km: num(raw?.radius_km, 3),
      fuzz_m: num(raw?.fuzz_m, 200),
      lock_min_hearts: lockMin(raw?.lock_min_hearts),
    })
  );

/** GET /riders/favorites — ไรเดอร์ที่ฉันเคยให้หัวใจ (หัวใจมากสุดก่อน) */
export const getFavoriteRiders = async (): Promise<ApiResult<FavoriteRidersResult>> =>
  mapResult(await apiGet<any>('/riders/favorites'), (raw) => ({
    riders: (Array.isArray(raw?.riders) ? raw.riders : [])
      .map(normalizeFavorite)
      .filter((r: FavoriteRider | null): r is FavoriteRider => r !== null),
    lock_min_hearts: lockMin(raw?.lock_min_hearts),
  }));

/** GET /riders/{id} */
export const getRiderProfile = async (riderId: number): Promise<ApiResult<RiderProfile>> =>
  mapResult(await apiGet<any>(`/riders/${riderId}`), (raw) => {
    const card = normalizePersonCard(raw?.rider);
    return {
      ...(card || {
        id: riderId,
        display_name: 'ไรเดอร์',
        photo_url: null,
        vehicle_type: null,
        vehicle_label: null,
        plate_masked: null,
        hearts_total: null,
        hearts_from_me: null,
        can_lock: false,
        verified: false,
      }),
      completed_jobs: raw?.rider?.completed_jobs === undefined ? null : num(raw.rider.completed_jobs),
      joined_at: str(raw?.rider?.joined_at),
    };
  });

/**
 * POST /orders/{source}/{id}/heart — ให้หัวใจไรเดอร์ของออเดอร์นี้ (1 ดวงต่อออเดอร์)
 * กดซ้ำ = already:true (ไม่นับเพิ่ม) · ไม่ใช่ผู้ซื้อ/งานยังไม่จบ = 409 HEART_NOT_ALLOWED
 */
export const giveRiderHeart = async (source: HandoverSource, orderId: number): Promise<ApiResult<HeartResult>> =>
  mapResult(await apiPost<any>(`/orders/${source}/${orderId}/heart`, {}), (raw) => ({
    hearts_from_me: num(raw?.hearts_from_me),
    hearts_total: num(raw?.hearts_total),
    can_lock: bool(raw?.can_lock),
    already: bool(raw?.already),
  }));

// =====================================================
// ตัวช่วยแสดงผล
// =====================================================

/** ต้องให้หัวใจอีกกี่ดวงถึงล็อกเรียกได้ (0 = ล็อกได้แล้ว) */
export const heartsToLock = (heartsFromMe: number | null | undefined, minHearts: number): number =>
  Math.max(0, minHearts - Math.max(0, Math.round(heartsFromMe ?? 0)));

/** ชื่อรถสั้นๆ ของไรเดอร์ */
export const vehicleText = (rider: Pick<PersonCard, 'vehicle_label' | 'vehicle_type'>): string | null => {
  if (rider.vehicle_label) return rider.vehicle_label;
  switch (rider.vehicle_type) {
    case 'motorcycle':
      return 'มอเตอร์ไซค์';
    case 'car':
      return 'รถยนต์';
    case 'bicycle':
      return 'จักรยาน';
    case 'walk':
      return 'เดินส่ง';
    default:
      return null;
  }
};
