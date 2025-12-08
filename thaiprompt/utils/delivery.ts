/**
 * Delivery Utilities - คำนวณค่าส่งและระยะทาง
 */

// =====================================================
// ราคาค่าส่ง (สามารถปรับจาก Admin ได้)
// =====================================================

export const DELIVERY_PRICING = {
  // ค่าเริ่มต้น (บาท)
  baseFee: 25,

  // ค่าต่อกิโลเมตร (บาท)
  perKmRate: 8,

  // ระยะทางฟรี (กม.) - รวมในค่าเริ่มต้น
  freeDistance: 2,

  // ค่าบริการขั้นต่ำ (บาท)
  minimumFee: 25,

  // ค่าบริการสูงสุด (บาท)
  maximumFee: 500,

  // ค่าธรรมเนียมแพลตฟอร์ม (%)
  platformFeePercent: 20,

  // โบนัสตามเวลา
  rushHourMultiplier: 1.2, // ชั่วโมงเร่งด่วน
  lateNightMultiplier: 1.3, // ดึก (22:00-06:00)

  // ค่าเพิ่มตามประเภทรถ
  vehicleMultiplier: {
    walk: 0.8,
    bicycle: 0.9,
    motorcycle: 1.0,
    car: 1.5,
  } as Record<string, number>,
};

// =====================================================
// คำนวณระยะทาง (Haversine formula)
// =====================================================

export interface Coordinates {
  latitude: number;
  longitude: number;
}

/**
 * คำนวณระยะทางระหว่างสองจุด (กิโลเมตร)
 */
export const calculateDistance = (
  from: Coordinates,
  to: Coordinates
): number => {
  const R = 6371; // รัศมีโลก (กิโลเมตร)
  const dLat = toRad(to.latitude - from.latitude);
  const dLon = toRad(to.longitude - from.longitude);

  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(toRad(from.latitude)) *
      Math.cos(toRad(to.latitude)) *
      Math.sin(dLon / 2) *
      Math.sin(dLon / 2);

  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  const distance = R * c;

  return Math.round(distance * 100) / 100; // ปัดเป็นทศนิยม 2 ตำแหน่ง
};

const toRad = (deg: number): number => {
  return deg * (Math.PI / 180);
};

// =====================================================
// คำนวณค่าส่ง
// =====================================================

export interface DeliveryFeeResult {
  // ค่าส่งรวม (ลูกค้าจ่าย)
  totalFee: number;

  // ค่าธรรมเนียมแพลตฟอร์ม
  platformFee: number;

  // รายได้ไรเดอร์
  riderEarnings: number;

  // ระยะทาง (กม.)
  distanceKm: number;

  // เวลาประมาณ (นาที)
  estimatedMinutes: number;

  // รายละเอียดการคำนวณ
  breakdown: {
    baseFee: number;
    distanceFee: number;
    timeMultiplier: number;
    vehicleMultiplier: number;
  };
}

/**
 * คำนวณค่าส่งตามระยะทาง
 */
export const calculateDeliveryFee = (
  pickup: Coordinates,
  delivery: Coordinates,
  options?: {
    vehicleType?: 'walk' | 'bicycle' | 'motorcycle' | 'car';
    isRushHour?: boolean;
    isLateNight?: boolean;
  }
): DeliveryFeeResult => {
  const distanceKm = calculateDistance(pickup, delivery);
  const vehicleType = options?.vehicleType || 'motorcycle';

  // ค่าเริ่มต้น
  let baseFee = DELIVERY_PRICING.baseFee;

  // คำนวณค่าระยะทาง (หักระยะทางฟรี)
  const billableDistance = Math.max(0, distanceKm - DELIVERY_PRICING.freeDistance);
  let distanceFee = billableDistance * DELIVERY_PRICING.perKmRate;

  // คูณตัวประเภทรถ
  const vehicleMultiplier = DELIVERY_PRICING.vehicleMultiplier[vehicleType] || 1;

  // คูณตัวเวลา
  let timeMultiplier = 1;
  if (options?.isRushHour) {
    timeMultiplier = DELIVERY_PRICING.rushHourMultiplier;
  } else if (options?.isLateNight) {
    timeMultiplier = DELIVERY_PRICING.lateNightMultiplier;
  }

  // คำนวณค่าส่งรวม
  let totalFee = (baseFee + distanceFee) * vehicleMultiplier * timeMultiplier;

  // จำกัดค่าส่ง
  totalFee = Math.max(DELIVERY_PRICING.minimumFee, totalFee);
  totalFee = Math.min(DELIVERY_PRICING.maximumFee, totalFee);

  // ปัดเศษ
  totalFee = Math.ceil(totalFee);

  // คำนวณค่าธรรมเนียมแพลตฟอร์ม
  const platformFee = Math.ceil(totalFee * (DELIVERY_PRICING.platformFeePercent / 100));

  // รายได้ไรเดอร์
  const riderEarnings = totalFee - platformFee;

  // ประมาณเวลา (15 กม./ชม. สำหรับมอเตอร์ไซค์ในเมือง)
  const speedKmh = vehicleType === 'motorcycle' ? 15 : vehicleType === 'car' ? 12 : 8;
  const estimatedMinutes = Math.ceil((distanceKm / speedKmh) * 60) + 10; // +10 นาทีสำหรับรับของ

  return {
    totalFee,
    platformFee,
    riderEarnings,
    distanceKm,
    estimatedMinutes,
    breakdown: {
      baseFee,
      distanceFee: Math.ceil(distanceFee),
      timeMultiplier,
      vehicleMultiplier,
    },
  };
};

// =====================================================
// ตรวจสอบเวลา
// =====================================================

/**
 * ตรวจสอบว่าเป็นชั่วโมงเร่งด่วนหรือไม่
 */
export const isRushHour = (): boolean => {
  const hour = new Date().getHours();
  // 07:00-09:00 และ 17:00-19:00
  return (hour >= 7 && hour < 9) || (hour >= 17 && hour < 19);
};

/**
 * ตรวจสอบว่าเป็นเวลาดึกหรือไม่
 */
export const isLateNight = (): boolean => {
  const hour = new Date().getHours();
  // 22:00-06:00
  return hour >= 22 || hour < 6;
};

// =====================================================
// Format
// =====================================================

/**
 * แสดงเวลาประมาณ
 */
export const formatEstimatedTime = (minutes: number): string => {
  if (minutes < 60) {
    return `${minutes} นาที`;
  }
  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;
  if (mins === 0) {
    return `${hours} ชม.`;
  }
  return `${hours} ชม. ${mins} นาที`;
};

/**
 * แสดงระยะทาง
 */
export const formatDistance = (km: number): string => {
  if (km < 1) {
    return `${Math.round(km * 1000)} เมตร`;
  }
  return `${km.toFixed(1)} กม.`;
};
