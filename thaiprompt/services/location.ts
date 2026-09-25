/**
 * Location Service — ตำแหน่งของไรเดอร์ (ตามนโยบาย Google Play)
 *
 * วงจรชีวิตการติดตามตำแหน่งระหว่างส่งงาน (RIDER-APP-12 / PLAY-13 / PLAY-14)
 *   - เริ่มติดตาม "เฉพาะ" ตอนรับงานสำเร็จ (startJobTracking)
 *   - หยุดเมื่อส่งสำเร็จ / ส่งไม่สำเร็จ / คืนงาน / งานถูกยกเลิก (stopJobTracking / stopJobTrackingFor)
 *   - สถานะว่ากำลังติดตามงานไหน เก็บใน AsyncStorage (ไม่ใช่ตัวแปรในหน่วยความจำ)
 *     → แอปถูกปิดแล้วเปิดใหม่ก็ยังรู้ว่าต้องหยุด/ติดตามต่อ และหยุด task ที่ค้างได้เสมอ
 *   - มีสิทธิ์ "อนุญาตตลอดเวลา" → ใช้ background task + foreground service (มีแจ้งเตือนค้างบนเครื่อง)
 *     ไม่มี → ส่งตำแหน่งเฉพาะตอนเปิดแอปอยู่ (ยังรับงานได้ตามปกติ)
 *   - ตอนออนไลน์แต่ยังไม่มีงาน: ส่งตำแหน่งครั้งเดียวตอนเปิดหน้าไรเดอร์/รายการงาน (pingRiderLocation) ไม่ติดตามเบื้องหลัง
 *
 * ตำแหน่งส่งผ่าน POST /rider/location เท่านั้น (throttle 40/นาที → ห้ามถี่กว่า ~10 วินาที)
 * heading/speed ติดลบ (iOS = ไม่ทราบ) ไม่ส่งไป (RIDER-APP-14)
 */

import * as Location from 'expo-location';
import * as TaskManager from 'expo-task-manager';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Device from 'expo-device';
import { Platform } from 'react-native';
import { stopGpsSharing as apiStopGpsSharing } from './api';
import { getCurrentJob, sendRiderLocation, type RiderLocationBody } from './api/riderApi';
import { palette } from '@/theme/tokens';

// =====================================================
// ค่าคงที่
// =====================================================

/** ชื่อ background task (ชื่อเดิม — task ที่เคยเปิดค้างจากแอปรุ่นก่อนจะถูกหยุดได้) */
export const LOCATION_TASK_NAME = 'RIDER_LOCATION_TRACKING';

/** key ใน AsyncStorage ที่จำว่ากำลังติดตามงานไหน */
const TRACKING_STORAGE_KEY = '@thaiprompt/rider_job_tracking_v1';

/** ส่งตำแหน่งห่างกันอย่างน้อยกี่มิลลิวินาที (server throttle 40/นาที) */
const MIN_SEND_INTERVAL_MS = 10_000;

/** รอบส่งตำแหน่งแบบ foreground (ไม่มีสิทธิ์เบื้องหลัง) */
const FOREGROUND_INTERVAL_MS = 20_000;

/** สถานะงานที่ยังต้องติดตามตำแหน่ง */
const ACTIVE_JOB_STATUSES = ['accepted', 'picking_up', 'picked_up', 'delivering'];

export type TrackingMode = 'background' | 'foreground';

export interface JobTrackingState {
  jobId: number;
  mode: TrackingMode;
  startedAt: string;
}

export interface LocationPermissionState {
  /** อนุญาต "ขณะใช้แอป" แล้ว */
  foreground: boolean;
  /** อนุญาต "ตลอดเวลา" แล้ว */
  background: boolean;
  /** ยังเปิดกล่องขอสิทธิ์ของระบบได้อีก (false = ต้องไปเปิดในตั้งค่าเครื่อง) */
  canAskForeground: boolean;
  canAskBackground: boolean;
}

export interface Coords {
  latitude: number;
  longitude: number;
  accuracy?: number;
}

// =====================================================
// สถานะในหน่วยความจำ (ใช้เป็น cache เท่านั้น — ความจริงอยู่ใน AsyncStorage)
// =====================================================

let cachedState: JobTrackingState | null | undefined; // undefined = ยังไม่เคยอ่าน
let foregroundTimer: ReturnType<typeof setInterval> | null = null;
let lastSentAt = 0;
let sendingNow = false;

/** เรียงลำดับคำสั่ง start/stop ไม่ให้ทับกัน (กดรับงานแล้วหน้า detail เรียกซ้ำพร้อมกัน) */
let opChain: Promise<unknown> = Promise.resolve();
const serialize = <T>(task: () => Promise<T>): Promise<T> => {
  const next = opChain.then(task, task);
  opChain = next.catch(() => undefined);
  return next;
};

const isPositiveInt = (value: unknown): value is number =>
  typeof value === 'number' && Number.isInteger(value) && value > 0;

// =====================================================
// AsyncStorage
// =====================================================

/** อ่านสถานะการติดตามที่บันทึกไว้ */
export const getJobTrackingState = async (): Promise<JobTrackingState | null> => {
  if (cachedState !== undefined) return cachedState;
  try {
    const raw = await AsyncStorage.getItem(TRACKING_STORAGE_KEY);
    if (!raw) {
      cachedState = null;
      return null;
    }
    const parsed = JSON.parse(raw) as Partial<JobTrackingState>;
    if (isPositiveInt(parsed?.jobId) && (parsed.mode === 'background' || parsed.mode === 'foreground')) {
      cachedState = { jobId: parsed.jobId, mode: parsed.mode, startedAt: String(parsed.startedAt || '') };
      return cachedState;
    }
  } catch {
    // ข้อมูลเสีย → ถือว่าไม่ได้ติดตาม
  }
  cachedState = null;
  return null;
};

const saveJobTrackingState = async (state: JobTrackingState | null): Promise<void> => {
  cachedState = state;
  try {
    if (state) {
      await AsyncStorage.setItem(TRACKING_STORAGE_KEY, JSON.stringify(state));
    } else {
      await AsyncStorage.removeItem(TRACKING_STORAGE_KEY);
    }
  } catch {
    // เขียนไม่ได้ก็ยังใช้ค่าในหน่วยความจำต่อได้
  }
};

// =====================================================
// สิทธิ์ตำแหน่ง
// =====================================================

/** สถานะสิทธิ์ตำแหน่งในเครื่อง (ไม่เปิดกล่องขอสิทธิ์) */
export const getLocationPermissionState = async (): Promise<LocationPermissionState> => {
  try {
    const fg = await Location.getForegroundPermissionsAsync();
    let bg: Location.LocationPermissionResponse | null = null;
    try {
      bg = await Location.getBackgroundPermissionsAsync();
    } catch {
      bg = null;
    }
    return {
      foreground: fg.status === 'granted',
      background: bg?.status === 'granted',
      canAskForeground: fg.status !== 'granted' ? fg.canAskAgain !== false : true,
      canAskBackground: bg ? (bg.status !== 'granted' ? bg.canAskAgain !== false : true) : false,
    };
  } catch {
    return { foreground: false, background: false, canAskForeground: true, canAskBackground: false };
  }
};

/** เปิด GPS ของเครื่องอยู่หรือไม่ */
export const isLocationServiceEnabled = async (): Promise<boolean> => {
  try {
    return await Location.hasServicesEnabledAsync();
  } catch {
    return true;
  }
};

// =====================================================
// ตำแหน่งปัจจุบัน
// =====================================================

const withTimeout = <T>(promise: Promise<T>, ms: number): Promise<T | null> =>
  new Promise((resolve) => {
    let done = false;
    const timer = setTimeout(() => {
      if (!done) {
        done = true;
        resolve(null);
      }
    }, ms);
    promise.then(
      (value) => {
        if (!done) {
          done = true;
          clearTimeout(timer);
          resolve(value);
        }
      },
      () => {
        if (!done) {
          done = true;
          clearTimeout(timer);
          resolve(null);
        }
      }
    );
  });

/**
 * ตำแหน่งปัจจุบันแบบไม่ค้าง (มี timeout และถอยไปใช้ตำแหน่งล่าสุดของเครื่อง)
 * ไม่มีสิทธิ์ → null (ไม่เปิดกล่องขอสิทธิ์เอง)
 */
export const getCurrentCoords = async (
  options: { accuracy?: 'high' | 'balanced'; timeoutMs?: number } = {}
): Promise<Coords | null> => {
  const { accuracy = 'balanced', timeoutMs = 8000 } = options;
  try {
    const fg = await Location.getForegroundPermissionsAsync();
    if (fg.status !== 'granted') return null;

    const fresh = await withTimeout(
      Location.getCurrentPositionAsync({
        accuracy: accuracy === 'high' ? Location.Accuracy.High : Location.Accuracy.Balanced,
      }),
      timeoutMs
    );
    const position = fresh || (await withTimeout(Location.getLastKnownPositionAsync({ maxAge: 5 * 60_000 }), 2000));
    if (!position) return null;

    const { latitude, longitude } = position.coords;
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || (latitude === 0 && longitude === 0)) {
      return null;
    }
    return {
      latitude,
      longitude,
      accuracy: typeof position.coords.accuracy === 'number' ? position.coords.accuracy : undefined,
    };
  } catch {
    return null;
  }
};

/** @deprecated ใช้ getCurrentCoords() — คงชื่อเดิมไว้ให้โค้ดเก่า */
export const getCurrentLocation = (): Promise<Coords | null> => getCurrentCoords();

// =====================================================
// ส่งตำแหน่งขึ้น server
// =====================================================

const buildLocationBody = (location: Location.LocationObject, jobId: number | null): RiderLocationBody | null => {
  const { latitude, longitude, accuracy, speed, heading, altitude } = location.coords;
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || (latitude === 0 && longitude === 0)) {
    return null;
  }
  const body: RiderLocationBody = {
    latitude,
    longitude,
    device_model: Device.modelName || undefined,
    os_version: `${Platform.OS} ${String(Platform.Version)}`,
  };
  if (typeof accuracy === 'number' && accuracy >= 0) body.accuracy = accuracy;
  // speed (m/s) → km/h · ค่าติดลบ = ไม่ทราบ ไม่ส่ง
  if (typeof speed === 'number' && speed >= 0) body.speed = Math.round(speed * 3.6 * 10) / 10;
  if (typeof heading === 'number' && heading >= 0 && heading <= 360) body.heading = heading;
  if (typeof altitude === 'number') body.altitude = altitude;
  if (isPositiveInt(jobId)) body.job_id = jobId;
  return body;
};

/**
 * ส่งตำแหน่งหนึ่งจุด
 * @returns ผลจาก server (null = ไม่ได้ส่ง/ส่งไม่สำเร็จ)
 */
const sendLocation = async (
  location: Location.LocationObject,
  jobId: number | null,
  force = false
): Promise<{ hasActiveJob: boolean } | null> => {
  const now = Date.now();
  if (sendingNow || (!force && now - lastSentAt < MIN_SEND_INTERVAL_MS)) return null;

  const body = buildLocationBody(location, jobId);
  if (!body) return null;

  sendingNow = true;
  try {
    const result = await sendRiderLocation(body);
    if (result.success) {
      lastSentAt = Date.now();
      return { hasActiveJob: !!result.data?.has_active_job };
    }
    return null;
  } finally {
    sendingNow = false;
  }
};

/**
 * ส่งตำแหน่งปัจจุบันหนึ่งครั้ง (ตอนกดออนไลน์ / เปิดหน้ารายการงาน / ก่อนรับงาน)
 * ไม่มีสิทธิ์หรือหาตำแหน่งไม่ได้ → false
 */
export const pingRiderLocation = async (options: { force?: boolean } = {}): Promise<boolean> => {
  try {
    const fg = await Location.getForegroundPermissionsAsync();
    if (fg.status !== 'granted') return false;
    const position = await withTimeout(
      Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced }),
      8000
    );
    if (!position) return false;
    const state = await getJobTrackingState();
    const sent = await sendLocation(position, state?.jobId ?? null, options.force === true);
    return sent !== null;
  } catch {
    return false;
  }
};

// =====================================================
// Background task (ประกาศระดับ module — ต้องมีตั้งแต่แอปเริ่ม)
// =====================================================

if (!TaskManager.isTaskDefined(LOCATION_TASK_NAME)) {
  TaskManager.defineTask<{ locations?: Location.LocationObject[] }>(LOCATION_TASK_NAME, async ({ data, error }) => {
    if (error) return;
    try {
      const state = await getJobTrackingState();
      // ไม่มีงานที่ต้องติดตามแล้ว (task ค้างจากรอบก่อน) → หยุดตัวเอง
      if (!state) {
        await Location.stopLocationUpdatesAsync(LOCATION_TASK_NAME).catch(() => {});
        return;
      }
      const locations = data?.locations || [];
      const latest = locations[locations.length - 1];
      if (!latest) return;

      const result = await sendLocation(latest, state.jobId);
      // server บอกว่าไม่มีงานแล้ว (ถูกยกเลิก/จบไปแล้ว) → หยุดติดตามทันที
      if (result && !result.hasActiveJob) {
        await stopJobTracking();
      }
    } catch {
      // background task ห้ามโยน error
    }
  });
}

// =====================================================
// เริ่ม / หยุด การติดตามระหว่างงาน
// =====================================================

const clearForegroundTimer = () => {
  if (foregroundTimer) {
    clearInterval(foregroundTimer);
    foregroundTimer = null;
  }
};

const startForegroundTimer = (jobId: number) => {
  clearForegroundTimer();
  foregroundTimer = setInterval(async () => {
    try {
      const state = await getJobTrackingState();
      if (!state || state.jobId !== jobId) {
        clearForegroundTimer();
        return;
      }
      const position = await withTimeout(
        Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High }),
        10_000
      );
      if (!position) return;
      const result = await sendLocation(position, jobId);
      if (result && !result.hasActiveJob) {
        await stopJobTracking();
      }
    } catch {
      // รอบหน้าลองใหม่
    }
  }, FOREGROUND_INTERVAL_MS);
};

const hasStartedBackgroundTask = async (): Promise<boolean> => {
  try {
    return await Location.hasStartedLocationUpdatesAsync(LOCATION_TASK_NAME);
  } catch {
    return false;
  }
};

const startBackgroundTask = async (): Promise<boolean> => {
  try {
    if (await hasStartedBackgroundTask()) return true;
    await Location.startLocationUpdatesAsync(LOCATION_TASK_NAME, {
      accuracy: Location.Accuracy.High,
      timeInterval: 15_000,
      distanceInterval: 25,
      deferredUpdatesInterval: 15_000,
      pausesUpdatesAutomatically: false,
      activityType: Location.ActivityType.AutomotiveNavigation,
      showsBackgroundLocationIndicator: true,
      foregroundService: {
        notificationTitle: 'กำลังส่งงาน · แชร์ตำแหน่งให้ลูกค้า',
        notificationBody: 'ThaiPrompt ใช้ตำแหน่งของคุณให้ลูกค้าติดตามการจัดส่ง และจะหยุดเองเมื่อจบงาน',
        notificationColor: palette.gold400,
      },
    });
    return true;
  } catch {
    return false;
  }
};

const stopBackgroundTask = async (): Promise<void> => {
  try {
    if (await hasStartedBackgroundTask()) {
      await Location.stopLocationUpdatesAsync(LOCATION_TASK_NAME);
    }
  } catch {
    // task อาจหยุดไปแล้ว
  }
};

/**
 * เริ่มติดตามตำแหน่งของงานที่รับแล้ว (เรียกซ้ำได้ — ไม่เริ่มซ้อน)
 *
 * @returns background = ติดตามแม้ปิดหน้าจอ · foreground = เฉพาะตอนเปิดแอป · none = ไม่มีสิทธิ์ตำแหน่ง
 */
export const startJobTracking = (jobId: number): Promise<TrackingMode | 'none'> =>
  serialize(async () => {
    if (!isPositiveInt(jobId)) return 'none';

    const perms = await getLocationPermissionState();
    if (!perms.foreground) {
      return 'none';
    }

    const current = await getJobTrackingState();
    const wantMode: TrackingMode = perms.background ? 'background' : 'foreground';

    // ติดตามงานเดิมในโหมดเดิมอยู่แล้ว → แค่ตรวจว่าตัวติดตามยังทำงาน
    if (current && current.jobId === jobId && current.mode === wantMode) {
      if (wantMode === 'background') {
        if (!(await hasStartedBackgroundTask()) && !(await startBackgroundTask())) {
          await saveJobTrackingState({ ...current, mode: 'foreground' });
          startForegroundTimer(jobId);
          return 'foreground';
        }
      } else if (!foregroundTimer) {
        startForegroundTimer(jobId);
      }
      return wantMode;
    }

    // เปลี่ยนงาน/เปลี่ยนโหมด → ล้างของเดิมก่อน
    clearForegroundTimer();
    if (wantMode === 'foreground') {
      await stopBackgroundTask();
    }

    let mode: TrackingMode = wantMode;
    await saveJobTrackingState({ jobId, mode, startedAt: new Date().toISOString() });

    if (mode === 'background' && !(await startBackgroundTask())) {
      mode = 'foreground';
      await saveJobTrackingState({ jobId, mode, startedAt: new Date().toISOString() });
    }
    if (mode === 'foreground') {
      startForegroundTimer(jobId);
    }

    // ส่งตำแหน่งแรกทันที (ไม่รอ)
    withTimeout(Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High }), 10_000)
      .then((position) => (position ? sendLocation(position, jobId, true) : null))
      .catch(() => null);

    return mode;
  });

/** หยุดติดตามทุกอย่าง (ไม่สนว่าเป็นงานไหน) */
export const stopJobTracking = (): Promise<void> =>
  serialize(async () => {
    clearForegroundTimer();
    await stopBackgroundTask();
    await saveJobTrackingState(null);
  });

/** หยุดติดตามเฉพาะถ้ากำลังติดตามงานนี้อยู่ (ดูประวัติงานเก่าจะไม่ไปหยุดงานปัจจุบัน) */
export const stopJobTrackingFor = async (jobId: number): Promise<void> => {
  const state = await getJobTrackingState();
  if (!state || state.jobId === jobId) {
    await stopJobTracking();
  }
};

/**
 * ทำให้ตัวติดตามตรงกับความจริง
 * @param activeJobId งานที่ server บอกว่ายังทำอยู่ (null = ไม่มีงาน → หยุด)
 */
export const reconcileJobTracking = async (activeJobId: number | null): Promise<TrackingMode | 'none'> => {
  if (!isPositiveInt(activeJobId)) {
    const state = await getJobTrackingState();
    if (state || foregroundTimer || (await hasStartedBackgroundTask())) {
      await stopJobTracking();
    }
    return 'none';
  }
  return startJobTracking(activeJobId);
};

/** สถานะงานนี้ยังต้องติดตามตำแหน่งหรือไม่ */
export const isTrackableJobStatus = (status: string | null | undefined): boolean =>
  !!status && ACTIVE_JOB_STATUSES.includes(status);

/**
 * ถาม server ว่ามีงานค้างไหม แล้วเริ่ม/หยุดตัวติดตามให้ตรง
 *
 * @param options.onlyIfTracking true = เรียก server เฉพาะเมื่อเครื่องนี้กำลังติดตามอยู่
 *        (ใช้ตอนเปิดแอป/กลับเข้าแอป — ผู้ใช้ทั่วไปจะไม่มีการเรียก API เพิ่ม)
 */
export const syncJobTrackingWithServer = async (options: { onlyIfTracking?: boolean } = {}): Promise<void> => {
  try {
    const state = await getJobTrackingState();
    const taskRunning = await hasStartedBackgroundTask();
    if (options.onlyIfTracking && !state && !taskRunning) return;

    const result = await getCurrentJob();
    if (result.success) {
      const job = result.data?.job;
      const activeId = result.data?.has_job && job && isTrackableJobStatus(job.status) ? job.id : null;
      await reconcileJobTracking(activeId);
      return;
    }
    // ไม่ใช่ไรเดอร์ / ไม่ได้ล็อกอิน → ไม่ควรติดตามอะไรเลย
    if (result.status === 401 || result.status === 403) {
      await reconcileJobTracking(null);
    }
    // เน็ตหลุด → ปล่อยตามเดิม รอบหน้าค่อยตรวจใหม่
  } catch {
    // ห้ามทำแอปล้ม
  }
};

// =====================================================
// ฟีเจอร์ "แชร์ตำแหน่งให้แอดมิน" รุ่นเก่า (ถูกถอดแล้ว — PLAY-13)
// =====================================================

/**
 * ปิดการแชร์ตำแหน่งให้แอดมินที่เครื่องรุ่นเก่าเคยเปิดไว้ (แจ้ง server ครั้งเดียว)
 */
export const stopLegacyGpsSharing = async (): Promise<void> => {
  try {
    await apiStopGpsSharing();
  } catch {
    // ไม่สำคัญ
  }
};

// =====================================================
// ตัวช่วยคำนวณ/แสดงผล
// =====================================================

/**
 * ระยะทางระหว่างสองจุด (Haversine) หน่วย กม.
 */
export const calculateDistance = (lat1: number, lon1: number, lat2: number, lon2: number): number => {
  const earthRadius = 6371;
  const toRad = (value: number) => (value * Math.PI) / 180;
  const latDiff = toRad(lat2 - lat1);
  const lonDiff = toRad(lon2 - lon1);
  const a =
    Math.sin(latDiff / 2) * Math.sin(latDiff / 2) +
    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(lonDiff / 2) * Math.sin(lonDiff / 2);
  return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

/** ระยะทางเป็นข้อความ เช่น "850 ม." / "3.2 กม." */
export const formatDistance = (distanceKm: number): string => {
  if (!Number.isFinite(distanceKm) || distanceKm < 0) return '-';
  if (distanceKm < 1) {
    return `${Math.round(distanceKm * 1000)} ม.`;
  }
  return `${distanceKm.toFixed(1)} กม.`;
};

/** เวลาเป็นข้อความ เช่น "25 นาที" / "1 ชม. 10 นาที" */
export const formatDuration = (minutes: number): string => {
  if (!Number.isFinite(minutes) || minutes <= 0) return '-';
  const rounded = Math.round(minutes);
  if (rounded < 60) return `${rounded} นาที`;
  const hours = Math.floor(rounded / 60);
  const mins = rounded % 60;
  return mins === 0 ? `${hours} ชั่วโมง` : `${hours} ชม. ${mins} นาที`;
};
