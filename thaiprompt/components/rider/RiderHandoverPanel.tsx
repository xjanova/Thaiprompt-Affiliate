/**
 * RiderHandoverPanel — ส่งมอบของให้ลูกค้า (ไรเดอร์รอบ 2 ตามม็อกอัป RiderDropoff)
 *
 * ใช้ในหน้ารายละเอียดงานเมื่อ job.handover.required (งานแบบเดิมยังใช้ปุ่ม "ส่งสำเร็จ" ตามเดิม)
 *
 * ทางหลัก — สแกนใส่กันครบ 2 ฝ่าย:
 *   1) ไรเดอร์สแกน QR ของลูกค้า (กล้องใช้ไม่ได้ → กรอกรหัส 6 หลักที่ลูกค้าอ่านให้)
 *   2) ลูกค้าสแกน QR ของไรเดอร์ (QR หมุนเวียน อายุสั้น ใช้ครั้งเดียว)
 *   ทุกครั้งส่งพิกัดปัจจุบันไปด้วย — ต้องอยู่ใกล้จุดส่ง (server ตรวจ geofence)
 * ทางสำรอง — ลูกค้าไม่สแกน/ไม่อยู่:
 *   รูปรอบ 1 ที่จุดส่ง → นับถอยหลังถึง wait_until → รูปรอบ 2 → งานเป็น "รอปลดเงิน"
 *   ระบบปลดเงินอัตโนมัติใน 24 ชม. ถ้าลูกค้าไม่ร้องเรียน
 *
 * ความทนทาน
 * - สถานะจริงอยู่ที่ server (เวลาเริ่มรอ / รูปที่ส่งแล้ว) → ดึงใหม่ทุก 5 วินาทีตอนเปิดหน้าอยู่ + ทันทีที่กลับเข้าแอป
 * - เวลาทุกตัวนับตามนาฬิกา server (server_now → clock_offset_ms) ไม่ใช่นาฬิกาเครื่อง (§A2 / L3)
 *   ปุ่มรูปรอบ 2 เปิดเมื่อ server บอก can_waited_photo หรือครบเวลาตามนาฬิกา server
 * - รูปที่ถ่ายแล้วแต่ส่งไม่ออก (เน็ตหลุด/แอปถูกปิด) เก็บไว้ในเครื่อง (handoverCache) → กด "ส่งรูปอีกครั้ง" ได้ ไม่ต้องถ่ายใหม่
 *   server ตอบ WAIT_NOT_OVER → เก็บรูปไว้ แล้วส่งให้อัตโนมัติเมื่อครบเวลา (ไม่ต้องถ่ายใหม่)
 *   ส่งอัตโนมัติแล้วล้ม (เน็ต/5xx/429) → นับครั้ง + พักแบบทวีคูณ (handoverRetry) ไม่เด้ง Alert แสดงสถานะในการ์ดแทน
 *   ล้มครบโควตา → หยุดส่งเอง ให้กด "ส่งรูปอีกครั้ง" (B1 — เดิมยิงวนถี่ + Alert ซ้อน)
 * - ไฟล์รูปในเครื่องลบทิ้งเมื่อวางของครบ/งานจบ/ทิ้งรูป (M1)
 * - ลูกค้าแจ้งปัญหา (disputed) → การ์ดแจ้งชัดเจนทุกสถานะ ปิดปุ่มส่งมอบทั้งหมด (L4)
 * - รูปลูกค้าผ่าน allowlist เดียวกับฝั่งผู้ซื้อ (personPhotoUri — L5)
 * - ทุกปุ่มกันกดซ้ำด้วย ref · ทุก await เช็ค mounted ก่อน setState · ตัวจับเวลาทุกตัวถูกล้างตอนออกจากหน้า
 * - ข้อความ error ภาษาไทยบอกสิ่งที่ต้องทำต่อ (ห่างจุดส่งกี่เมตร / ถ่ายรอบ 2 ได้กี่โมง / รหัสถูกล็อกถึงกี่โมง)
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Alert, AppState, Linking, Pressable, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { requireOptionalNativeModule } from 'expo';
import { Image } from 'expo-image';
import { useFocusEffect } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, Icon, Pill, PriceText, formatBaht, resultHaptic, type IconName } from '@/components/ui';
import { useTheme, radii, spacing, typography } from '@/theme';
import { PersonAvatar } from '@/components/people/PersonAvatar';
import { RotatingQr } from '@/components/handover/RotatingQr';
import { QrScannerSheet } from '@/components/handover/QrScannerSheet';
import {
  getRiderHandover,
  riderArrivalPhoto,
  riderHandoverScan,
  riderWaitedPhoto,
  type RiderHandoverResponse,
  type RiderJobDetail,
} from '@/services/api/riderApi';
import { normalizeRiderHandoverData, personPhotoUri } from '@/services/api/handoverApi';
import { isVerifiedFlag } from '@/components/people/VerifiedBadge';
import { calculateDistance, getCurrentCoords, type Coords } from '@/services/location';
import { deviceTimeFor, lastClockOffset, parseIsoMs, serverNowMs } from '@/utils/serverClock';
import { takePhoto } from './photo';
import {
  clearHandoverCache,
  deleteLocalPhoto,
  readHandoverCache,
  writeHandoverCache,
  type ArrivalHandoverShot as ArrivalShot,
  type HandoverPhotoKind as PhotoKind,
  type PendingHandoverPhoto as PendingPhoto,
  type StoredHandoverState as StoredState,
} from './handoverCache';
import {
  callPhone,
  formatMeters,
  formatThaiDateTime,
  handoverErrorText,
  openNavigation,
  riderTotalOf,
} from './riderHelpers';
import { ActionIcon, FocusInput, IconTile, NoticeCard, ProgressRing } from './RiderVisuals';
import { RiderSheet } from './RiderSheet';
import {
  INITIAL_AUTO_RETRY,
  autoRetryExhausted,
  autoRetryReasonText,
  classifyUploadFailure,
  isTransportFailure,
  nextAutoRetryAt,
  recordAutoRetryFailure,
  type AutoRetryState,
} from './handoverRetry';

const POLL_MS = 5_000;
/** รอ Modal (กล้อง/sheet) ปิดสนิทก่อนแสดง Alert — iOS ซ้อน Modal ไม่ได้ */
const MODAL_SETTLE_MS = 450;
const DEFAULT_WAIT_SECONDS = 180;
const DEFAULT_GEOFENCE_M = 150;

type BusyKind = 'scan' | 'code' | PhotoKind | null;
/** ใครสั่งส่งรูป: ไรเดอร์กดเอง (แจ้งผลด้วย Alert) / ตัวจับเวลาส่งให้ (แสดงสถานะในการ์ด ไม่เด้ง Alert) */
type UploadMode = 'manual' | 'auto';

/** เขียนข้อมูลในเครื่อง (ไม่รอผล) */
const writeStored = (jobId: number, value: StoredState): void => {
  writeHandoverCache(jobId, value).catch(() => {});
};

// =====================================================
// ตัวช่วยอ่านสถานะ
// =====================================================

const FINAL_STATUSES = ['completed', 'released', 'refunded'];

/**
 * เครื่องนี้กันแคปหน้าจอได้จริงไหม (แอปรุ่นเก่าไม่มี native module → ไม่อ้างว่ากันได้)
 * useSensitiveScreen ของหน้าแม่เป็นตัวสั่งกันจริง — ตรงนี้แค่ตัดสินว่าจะโชว์ป้ายหรือไม่
 */
let captureGuardAvailable: boolean | undefined;
const canBlockCapture = (): boolean => {
  if (captureGuardAvailable === undefined) {
    try {
      captureGuardAvailable = !!requireOptionalNativeModule('ExpoScreenCapture');
    } catch {
      captureGuardAvailable = false;
    }
  }
  return captureGuardAvailable;
};

const parseTime = (iso: string | null | undefined): number => {
  if (!iso) return NaN;
  const t = Date.parse(iso);
  return Number.isNaN(t) ? NaN : t;
};

/** เวลา HH:MM:SS */
const clock = (iso: string | null | undefined): string => {
  const t = parseTime(iso);
  if (!Number.isFinite(t)) return '';
  const d = new Date(t);
  return [d.getHours(), d.getMinutes(), d.getSeconds()].map((n) => String(n).padStart(2, '0')).join(':');
};

const mmss = (seconds: number): string => {
  const s = Math.max(0, Math.round(seconds));
  return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
};

/** error ที่แปลว่าสถานะงานเปลี่ยนไปแล้ว → ให้หน้าแม่โหลดงานใหม่ */
const JOB_CHANGED_CODES = ['HANDOVER_FINAL', 'HANDOVER_NOT_READY', 'INVALID_TRANSITION', 'JOB_NOT_FOUND', 'NOT_YOUR_JOB'];

// =====================================================
// แถวขั้นตอนของทางสำรอง
// =====================================================

const StepRow: React.FC<{
  index: number;
  state: 'done' | 'active' | 'locked';
  title: string;
  caption: string;
  right?: React.ReactNode;
  first?: boolean;
}> = ({ index, state, title, caption, right, first }) => {
  const { colors } = useTheme();
  const done = state === 'done';
  const locked = state === 'locked';
  return (
    <View
      style={[styles.step, !first && { borderTopWidth: 1, borderTopColor: colors.divider }]}
      accessible
      accessibilityLabel={`ขั้นที่ ${index} ${title} ${done ? 'เสร็จแล้ว' : locked ? 'ยังไม่ถึง' : 'ขั้นตอนตอนนี้'} ${caption}`}
    >
      <View
        style={[
          styles.stepBadge,
          {
            backgroundColor: done ? colors.success : locked ? colors.inset : colors.navyFill,
            borderColor: locked ? colors.border : 'transparent',
          },
        ]}
      >
        {done ? (
          <Icon name="check" size={16} color={colors.textOnAccent} weight="bold" />
        ) : (
          <Text style={[typography.bodyStrong, { color: locked ? colors.textFaint : colors.goldLight }]}>{index}</Text>
        )}
      </View>
      <View style={styles.flex}>
        <Text style={[typography.bodyStrong, { color: locked ? colors.textFaint : colors.textStrong }]}>{title}</Text>
        <Text style={[typography.caption, { color: locked ? colors.textFaint : colors.textMuted }]}>{caption}</Text>
      </View>
      {right}
    </View>
  );
};

// =====================================================
// แผงหลัก
// =====================================================

export interface RiderHandoverPanelProps {
  job: RiderJobDetail;
  /** ขอสิทธิ์ตำแหน่ง/เปิด GPS (จาก useRiderPermissionFlow().ensureForeground) */
  ensureLocation: () => Promise<boolean>;
  /** ส่งมอบสำเร็จด้วยการสแกนครบ 2 ฝ่าย (เรียกครั้งเดียว) */
  onCompleted: (info: { amount: number }) => void;
  /** วางของด้วยทางสำรองแล้ว → งานรอปลดเงิน (ไรเดอร์รับงานใหม่ได้) */
  onAwaitingRelease: () => void;
  /** สถานะงานเปลี่ยนจากที่อื่น → ให้หน้าแม่โหลดงานใหม่ */
  onJobChanged: () => void;
  style?: StyleProp<ViewStyle>;
}

export const RiderHandoverPanel: React.FC<RiderHandoverPanelProps> = ({
  job,
  ensureLocation,
  onCompleted,
  onAwaitingRelease,
  onJobChanged,
  style,
}) => {
  const { colors, isDark } = useTheme();
  const jobId = job.id;

  const [data, setData] = useState<RiderHandoverResponse | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [busy, setBusy] = useState<BusyKind>(null);
  const [scanOpen, setScanOpen] = useState(false);
  const [codeOpen, setCodeOpen] = useState(false);
  const [code, setCode] = useState('');
  const [codeError, setCodeError] = useState<string | null>(null);
  const [showMyQr, setShowMyQr] = useState(false);
  const [pending, setPending] = useState<PendingPhoto | null>(null);
  const [arrival, setArrival] = useState<ArrivalShot | null>(null);
  const [now, setNow] = useState(() => Date.now());

  const mountedRef = useRef(true);
  const busyRef = useRef(false);
  const requestIdRef = useRef(0);
  /** ส่งรูปที่รอครบเวลา (WAIT_NOT_OVER) ถ้าถึงเวลาแล้ว — ตัวจริงตั้งด้านล่าง */
  const retryDueRef = useRef<() => void>(() => {});
  /** wait_until ล่าสุดจาก server (ใช้ตอน WAIT_NOT_OVER ไม่ได้แนบเวลามา) */
  const waitUntilRef = useRef<string | null>(null);
  /**
   * ส่งรูปรอบ 2 อัตโนมัติหลัง WAIT_NOT_OVER: ล้มไปกี่ครั้ง + ห้ามลองก่อนเวลานี้ (เวลาเครื่อง) + สาเหตุล่าสุด
   * state = ให้หน้าคำนวณเวลาลองครั้งถัดไปใหม่ทุกครั้งที่ล้ม · ref = ค่าล่าสุดสำหรับ callback ที่อ่านกลางทาง
   */
  const [autoRetry, setAutoRetryState] = useState<AutoRetryState>(INITIAL_AUTO_RETRY);
  const autoRetryRef = useRef<AutoRetryState>(INITIAL_AUTO_RETRY);
  const sawActiveRef = useRef(false);
  const completedRef = useRef(false);
  const awaitingRef = useRef(false);
  const storedRef = useRef<StoredState>({});
  const callbacksRef = useRef({ onCompleted, onAwaitingRelease, onJobChanged });
  callbacksRef.current = { onCompleted, onAwaitingRelease, onJobChanged };
  // งานจากหน้าแม่เป็น object ใหม่ทุกรอบโหลด — อ่านผ่าน ref เพื่อไม่ให้ตัวดึงข้อมูล/interval ถูกสร้างใหม่ซ้ำๆ
  const jobRef = useRef(job);
  jobRef.current = job;

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // ---------- รูปค้างส่ง / รูปรอบ 1 ที่เคยถ่าย (กลับเข้าหน้า/เปิดแอปใหม่ก็ยังอยู่) ----------
  useEffect(() => {
    let alive = true;
    readHandoverCache(jobId).then((stored) => {
      if (!alive || !mountedRef.current) return;
      storedRef.current = stored;
      setPending(stored.pending ?? null);
      setArrival(stored.arrival ?? null);
    });
    return () => {
      alive = false;
    };
  }, [jobId]);

  const setAutoRetry = useCallback((next: AutoRetryState) => {
    autoRetryRef.current = next;
    if (mountedRef.current) setAutoRetryState(next);
  }, []);

  const persist = useCallback(
    (patch: StoredState) => {
      storedRef.current = { ...storedRef.current, ...patch };
      writeStored(jobId, storedRef.current);
    },
    [jobId]
  );

  /** ล้างของในเครื่องของงานนี้ทั้งหมด (ข้อมูล + ไฟล์รูป) — วางของครบ/งานจบ */
  const dropLocal = useCallback(() => {
    storedRef.current = {};
    clearHandoverCache(jobId).catch(() => {});
  }, [jobId]);

  // ---------- รับข้อมูลใหม่จาก server ----------
  const apply = useCallback(
    (next: RiderHandoverResponse) => {
      if (!mountedRef.current || !next?.handover) return;
      setData(next);
      setLoadError(null);
      const h = next.handover;
      const status = String(h.status || '');
      if (!FINAL_STATUSES.includes(status) && !h.waited_photo_at && status !== 'fallback_pending_release' && status !== 'disputed') {
        sawActiveRef.current = true;
      }
      if (status === 'completed' && sawActiveRef.current && !completedRef.current) {
        // สแกนครบ 2 ฝ่าย → ฉลอง (ครั้งเดียว) + ล้างของที่เก็บในเครื่อง
        completedRef.current = true;
        dropLocal();
        callbacksRef.current.onCompleted({ amount: riderTotalOf(jobRef.current) });
        return;
      }
      if (FINAL_STATUSES.includes(status)) {
        dropLocal();
        callbacksRef.current.onJobChanged();
        return;
      }
      if ((status === 'fallback_pending_release' || !!h.waited_photo_at) && !awaitingRef.current) {
        awaitingRef.current = true;
        // วางของครบแล้ว ไม่ต้องเก็บรูปในเครื่องต่อ
        dropLocal();
        if (sawActiveRef.current) callbacksRef.current.onAwaitingRelease();
      }
    },
    [dropLocal]
  );

  const fetchHandover = useCallback(async () => {
    const requestId = ++requestIdRef.current;
    const result = await getRiderHandover(jobId);
    if (!mountedRef.current || requestId !== requestIdRef.current) return;
    if (result.success) {
      apply(result.data);
    } else {
      // HANDOVER_NOT_READY ฯลฯ: ใช้ข้อความไทยของ server (ข้อความสำรองเขียนสำหรับไรเดอร์) — L4
      setLoadError(handoverErrorText(result).message);
      // ดึงข้อมูลเฉยๆ: ให้หน้าแม่โหลดใหม่เฉพาะเมื่องานหาย/จบไปแล้วจริง (กันวนโหลดทุก 5 วินาที)
      if (['HANDOVER_FINAL', 'JOB_NOT_FOUND', 'NOT_YOUR_JOB'].includes(result.code)) callbacksRef.current.onJobChanged();
    }
  }, [apply, jobId]);

  // เปิดหน้าอยู่: ดึงทุก 5 วินาที (หยุดตอนแอปอยู่เบื้องหลัง) · กลับเข้าแอป = ดึงทันที
  useFocusEffect(
    useCallback(() => {
      fetchHandover();
      let active = AppState.currentState === 'active';
      const timer = setInterval(() => {
        if (active && !busyRef.current) fetchHandover();
      }, POLL_MS);
      const sub = AppState.addEventListener('change', (next) => {
        const wasActive = active;
        active = next === 'active';
        if (active && !wasActive) {
          setNow(Date.now());
          fetchHandover();
          // ระหว่างอยู่เบื้องหลังอาจครบเวลารอแล้ว → ส่งรูปที่รอส่งอัตโนมัติ
          retryDueRef.current();
        }
      });
      return () => {
        clearInterval(timer);
        sub.remove();
      };
    }, [fetchHandover])
  );

  // ---------- ค่าที่ใช้แสดงผล ----------
  const h = data?.handover ?? null;
  const buyer = data?.buyer ?? null;
  const status = String(h?.status ?? job.handover?.status ?? '');
  /** เวลา server − เวลาเครื่อง (ยังไม่ได้ server_now ของงานนี้ = ค่าล่าสุดที่แอปรู้) */
  const clockOffset = typeof data?.clock_offset_ms === 'number' ? data.clock_offset_ms : lastClockOffset();
  const geofence = Number(h?.geofence_m) > 0 ? Number(h?.geofence_m) : DEFAULT_GEOFENCE_M;
  const waitSeconds = Number(h?.wait_seconds) > 0 ? Number(h?.wait_seconds) : DEFAULT_WAIT_SECONDS;
  const waitMinutes = Math.max(1, Math.round(waitSeconds / 60));
  const riderConfirmed = !!(h?.rider_confirmed ?? job.handover?.rider_confirmed);
  const buyerConfirmed = !!(h?.buyer_confirmed ?? job.handover?.buyer_confirmed);
  const arrived = !!h?.arrival_photo_at;
  const waitedDone = !!h?.waited_photo_at || status === 'fallback_pending_release' || job.status === 'awaiting_release';
  const disputed = status === 'disputed';
  const waitUntilIso = h?.wait_until ?? job.handover?.wait_until ?? null;
  waitUntilRef.current = waitUntilIso;
  const waitUntilMs = parseIsoMs(waitUntilIso);
  /** วินาทีที่เหลือก่อนถ่ายรอบ 2 ได้ — นับตามนาฬิกา server */
  const remaining =
    arrived && waitUntilMs !== null
      ? Math.min(waitSeconds, Math.max(0, Math.ceil((waitUntilMs - serverNowMs(clockOffset, now)) / 1000)))
      : arrived
        ? 0
        : waitSeconds;
  /** ถ่ายรอบ 2 ได้: server บอก can_waited_photo หรือครบเวลาตามนาฬิกา server (ถ้ายังไม่ครบจริง server ตอบ WAIT_NOT_OVER แล้วแอปส่งให้เองเมื่อครบ) */
  const waitOver = arrived && (!!h?.can_waited_photo || remaining <= 0);
  const amount = riderTotalOf(job);
  const buyerName = buyer?.display_name || job.buyer?.display_name || job.dropoff?.name || 'ลูกค้า';
  // รูปลูกค้าผ่าน allowlist เดียวกับฝั่งผู้ซื้อ (L5) — ของ handover แปลงแล้วใน riderApi · ของ job แปลงตรงนี้
  const buyerPhoto = buyer?.photo_url ?? personPhotoUri(job.buyer?.photo_url);
  /** ผู้ซื้อยืนยันตัวตนแล้ว (ป้ายทอง) — จาก handover หรือข้อมูลงาน */
  const buyerVerified = !!buyer?.verified || isVerifiedFlag(job.buyer?.verified);
  /** งานเก็บเงินปลายทาง: server ไม่รับทางสำรองรูป 2 รอบ (ต้องส่งมอบกับลูกค้าโดยตรง) */
  const fallbackAllowed = !job.is_cod;
  /** แจ้ง "ส่งไม่สำเร็จ" ได้ไหม — ตาม allowed_actions ของ server เท่านั้น (ลูกค้ายืนยันรับแล้ว/ร้องเรียน = ไม่มี) */
  const canFail = (job.allowed_actions || []).includes('fail');

  // นับถอยหลังทีละวินาทีเฉพาะช่วงรอลูกค้า
  const counting = arrived && !waitedDone && remaining > 0;
  useEffect(() => {
    if (!counting) return undefined;
    const timer = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(timer);
  }, [counting]);

  // ครบเวลารอ → ถาม server ทันทีว่าถ่ายรอบ 2 ได้หรือยัง
  const waitEnded = arrived && !waitedDone && remaining <= 0;
  useEffect(() => {
    if (waitEnded) fetchHandover();
  }, [waitEnded, fetchHandover]);

  // ลูกค้าสแกนเราแล้ว/เราสแกนลูกค้าแล้ว → เปิด QR ของเราให้ลูกค้าสแกนต่อ
  useEffect(() => {
    if (riderConfirmed && !buyerConfirmed) setShowMyQr(true);
  }, [riderConfirmed, buyerConfirmed]);

  /** ระยะจากพิกัดถึงจุดส่ง (เมตร) — จุดส่งไม่มีพิกัด = null */
  const distanceToDropoff = useCallback(
    (coords: Coords): number | null => {
      const lat = Number(job.dropoff?.latitude);
      const lng = Number(job.dropoff?.longitude);
      if (!Number.isFinite(lat) || !Number.isFinite(lng) || (lat === 0 && lng === 0)) return null;
      return Math.round(calculateDistance(coords.latitude, coords.longitude, lat, lng) * 1000);
    },
    [job.dropoff?.latitude, job.dropoff?.longitude]
  );

  /** พิกัดสด (ไม่มีสิทธิ์/ปิด GPS → ขอเปิดก่อน) */
  const freshCoords = useCallback(async (): Promise<Coords | null> => {
    let coords = await getCurrentCoords({ accuracy: 'high', timeoutMs: 8000 });
    if (coords) return coords;
    if (!(await ensureLocation())) return null;
    coords = await getCurrentCoords({ accuracy: 'high', timeoutMs: 8000 });
    return coords;
  }, [ensureLocation]);

  /** แจ้ง error (รอ Modal ปิดก่อนถ้าเพิ่งปิด) */
  const alertError = useCallback(
    (result: { code: string; message: string; data?: any }, afterModal = false) => {
      const text = handoverErrorText(result, geofence);
      const buttons =
        result.code === 'TOO_FAR_FROM_DROPOFF'
          ? [
              { text: 'ปิด', style: 'cancel' as const },
              { text: 'นำทางไปจุดส่ง', onPress: () => openNavigation(jobRef.current.dropoff) },
            ]
          : [{ text: 'ตกลง' }];
      const show = () => Alert.alert(text.title, text.message, buttons);
      if (afterModal) {
        setTimeout(() => mountedRef.current && show(), MODAL_SETTLE_MS);
      } else {
        show();
      }
    },
    [geofence]
  );

  const locationMissing = useCallback(
    (afterModal = false) => alertError({ code: 'LOCATION_REQUIRED', message: '' }, afterModal),
    [alertError]
  );

  // =====================================================
  // ทางหลัก: สแกน QR / กรอกรหัสของลูกค้า
  // =====================================================

  /**
   * ยืนยันกับ server ด้วย token (สแกน) หรือ code (6 หลัก)
   * @returns true = สำเร็จ
   */
  const submitScan = useCallback(
    async (input: { token?: string; code?: string }, source: 'scanner' | 'code'): Promise<boolean> => {
      if (busyRef.current) return false;
      busyRef.current = true;
      setBusy(source === 'code' ? 'code' : 'scan');
      try {
        const coords = await freshCoords();
        if (!mountedRef.current) return false;
        if (!coords) {
          if (source === 'code') setCodeError(handoverErrorText({ code: 'LOCATION_REQUIRED', message: '' }).message);
          else locationMissing(true);
          return false;
        }
        // ผลของรอบดึงข้อมูลที่ค้างอยู่ (เริ่มก่อนสแกน) ห้ามทับผลการสแกนนี้
        requestIdRef.current += 1;
        const result = await riderHandoverScan(jobId, { ...input, latitude: coords.latitude, longitude: coords.longitude });
        if (!mountedRef.current) return false;
        if (result.success) {
          resultHaptic('success');
          apply(result.data);
          return true;
        }
        resultHaptic('error');
        if (result.data?.handover) apply(normalizeRiderHandoverData(result.data));
        if (JOB_CHANGED_CODES.includes(result.code)) callbacksRef.current.onJobChanged();
        if (source === 'code') {
          // แสดงในกล่องกรอกรหัสเลย (ไม่ปิดกล่อง ให้แก้รหัสต่อได้)
          setCodeError(handoverErrorText(result, geofence).message);
        } else {
          alertError(result, true);
        }
        return false;
      } finally {
        busyRef.current = false;
        if (mountedRef.current) setBusy(null);
      }
    },
    [alertError, apply, freshCoords, geofence, jobId, locationMissing]
  );

  const onScanned = useCallback(
    (raw: string) => {
      setScanOpen(false);
      const token = String(raw || '').trim();
      if (!token) return;
      submitScan({ token }, 'scanner');
    },
    [submitScan]
  );

  const openCodeSheet = () => {
    setCode('');
    setCodeError(null);
    setCodeOpen(true);
  };

  const submitCode = async (value?: string) => {
    const digits = (value ?? code).replace(/\D/g, '');
    if (digits.length !== 6) {
      setCodeError('รหัสต้องเป็นตัวเลข 6 หลัก');
      return;
    }
    setCodeError(null);
    const ok = await submitScan({ code: digits }, 'code');
    if (ok && mountedRef.current) setCodeOpen(false);
  };

  // =====================================================
  // ทางสำรอง: รูปรอบ 1 / รอบ 2
  // =====================================================

  const uploadPhoto = useCallback(
    async (photo: PendingPhoto, mode: UploadMode): Promise<void> => {
      const send = photo.kind === 'arrival' ? riderArrivalPhoto : riderWaitedPhoto;
      // ผลของรอบดึงข้อมูลที่ค้างอยู่ห้ามทับผลการส่งรูปนี้
      requestIdRef.current += 1;
      const result = await send(jobId, { photoUri: photo.uri, latitude: photo.latitude, longitude: photo.longitude });
      if (!mountedRef.current) return;
      if (result.success) {
        resultHaptic('success');
        setAutoRetry(INITIAL_AUTO_RETRY);
        setPending(null);
        if (photo.kind === 'arrival') {
          // เก็บไฟล์รูปรอบ 1 ไว้แสดงรูปย่อระหว่างรอลูกค้า (ลบเมื่อวางของครบ/งานจบ)
          const shot: ArrivalShot = { uri: photo.uri, distanceM: photo.distanceM, takenAt: photo.takenAt };
          setArrival(shot);
          persist({ pending: null, arrival: shot });
        } else {
          // วางของครบแล้ว ไม่ต้องเก็บอะไรในเครื่องต่อ — ลบไฟล์รูปทั้งสองรอบทิ้ง (M1)
          deleteLocalPhoto(photo.uri);
          dropLocal();
        }
        setNow(Date.now());
        apply(result.data);
        return;
      }
      // ส่งอัตโนมัติ: สั่นเตือนเฉพาะตอนไรเดอร์กดเอง (ตัวจับเวลาล้มเงียบๆ แล้วแสดงสถานะในการ์ด)
      if (mode === 'manual') resultHaptic('error');
      const failure = classifyUploadFailure(result, photo.kind);
      if (failure && failure !== 'wait_not_over') {
        // เน็ต/เซิร์ฟเวอร์/ถูกจำกัดความถี่ → เก็บรูปไว้ กด "ส่งรูปอีกครั้ง" ได้โดยไม่ต้องถ่ายใหม่
        // รูปที่อยู่ในโหมดส่งอัตโนมัติ (เคยได้ WAIT_NOT_OVER) → นับครั้ง + เลื่อนเวลาแบบทวีคูณ ห้ามยิงซ้ำทันที (B1)
        if (photo.kind === 'waited' && !!photo.waitUntil) {
          setAutoRetry(recordAutoRetryFailure(autoRetryRef.current, failure, Date.now()));
        }
        if (mode === 'manual') {
          Alert.alert('ส่งรูปไม่สำเร็จ', `${result.message}\nรูปยังอยู่ในเครื่อง กด "ส่งรูปอีกครั้ง" ได้เลย`);
        }
        return;
      }
      if (failure === 'wait_not_over') {
        // ยังไม่ครบเวลาตามนาฬิกา server → เก็บรูปไว้ แล้วส่งให้อัตโนมัติเมื่อครบเวลา (ไม่ต้องถ่ายใหม่ — L3)
        const waitUntil =
          typeof result.data?.wait_until === 'string' ? result.data.wait_until : waitUntilRef.current ?? null;
        setAutoRetry(recordAutoRetryFailure(autoRetryRef.current, 'wait_not_over', Date.now()));
        const kept: PendingPhoto = { ...photo, waitUntil };
        setPending(kept);
        persist({ pending: kept });
        fetchHandover();
        return;
      }
      // รูปนี้ใช้ไม่ได้แล้ว (ห่างเกิน / สถานะเปลี่ยน) → ทิ้ง แล้วบอกสิ่งที่ต้องทำ (ครั้งเดียว ไม่วนเพราะไม่มีรูปค้างแล้ว)
      if (mode === 'auto') resultHaptic('error');
      setAutoRetry(INITIAL_AUTO_RETRY);
      setPending(null);
      persist({ pending: null });
      if (photo.uri !== storedRef.current.arrival?.uri) deleteLocalPhoto(photo.uri);
      if (result.data?.handover) apply(normalizeRiderHandoverData(result.data));
      if (JOB_CHANGED_CODES.includes(result.code)) callbacksRef.current.onJobChanged();
      if (result.code === 'WAIT_NOT_OVER') fetchHandover();
      alertError(result);
    },
    [alertError, apply, dropLocal, fetchHandover, jobId, persist, setAutoRetry]
  );

  const shoot = useCallback(
    async (kind: PhotoKind) => {
      if (busyRef.current) return;
      busyRef.current = true;
      setBusy(kind);
      try {
        // 1) ตำแหน่งก่อน — ไม่มี GPS ไม่ต้องเปิดกล้องให้เสียเวลา
        const before = await freshCoords();
        if (!mountedRef.current) return;
        if (!before) {
          locationMissing();
          return;
        }
        // 2) ห่างจุดส่งเกินชัดเจน → บอกเลย (เผื่อความคลาดเคลื่อน GPS ให้แล้ว server ตรวจซ้ำอีกที)
        const distance = distanceToDropoff(before);
        const slack = Math.max(before.accuracy ?? 0, 30);
        if (distance !== null && distance > geofence + slack) {
          alertError({ code: 'TOO_FAR_FROM_DROPOFF', message: '', data: { distance_m: distance } });
          return;
        }
        // 3) ถ่ายรูป
        const uri = await takePhoto();
        if (!mountedRef.current || !uri) return;
        // 4) พิกัดหลังถ่าย (ไรเดอร์อาจขยับ) — หาไม่ได้ใช้ของก่อนถ่าย
        const after = (await getCurrentCoords({ accuracy: 'high', timeoutMs: 5000 })) ?? before;
        if (!mountedRef.current) return;
        const photo: PendingPhoto = {
          kind,
          uri,
          latitude: after.latitude,
          longitude: after.longitude,
          distanceM: distanceToDropoff(after),
          takenAt: new Date().toISOString(),
        };
        // รูปใหม่แทนรูปค้างเดิม → ลบไฟล์เดิม · เริ่มนับการส่งอัตโนมัติใหม่
        const previous = storedRef.current.pending;
        if (previous && previous.uri !== uri && previous.uri !== storedRef.current.arrival?.uri) deleteLocalPhoto(previous.uri);
        setAutoRetry(INITIAL_AUTO_RETRY);
        setPending(photo);
        persist({ pending: photo });
        await uploadPhoto(photo, 'manual');
      } finally {
        busyRef.current = false;
        if (mountedRef.current) setBusy(null);
      }
    },
    [alertError, distanceToDropoff, freshCoords, geofence, locationMissing, persist, setAutoRetry, uploadPhoto]
  );

  /** ส่งรูปค้างอีกครั้ง — manual = ไรเดอร์กดปุ่ม · auto = ตัวจับเวลาหลังครบเวลารอ */
  const retryPending = useCallback(
    async (mode: UploadMode) => {
      if (!pending || busyRef.current) return;
      busyRef.current = true;
      setBusy(pending.kind);
      try {
        await uploadPhoto(pending, mode);
      } finally {
        busyRef.current = false;
        if (mountedRef.current) setBusy(null);
      }
    },
    [pending, uploadPhoto]
  );

  const discardPending = () => {
    // ทิ้งรูปนี้ = ลบไฟล์ด้วย (ยกเว้นเป็นไฟล์เดียวกับรูปย่อรอบ 1)
    if (pending && pending.uri !== storedRef.current.arrival?.uri) deleteLocalPhoto(pending.uri);
    setAutoRetry(INITIAL_AUTO_RETRY);
    setPending(null);
    persist({ pending: null });
  };

  // ---------- รูปรอบ 2 ที่ server บอกว่ายังไม่ครบเวลา → ส่งให้อัตโนมัติเมื่อครบ (นาฬิกา server) ----------
  /** มีรูปรอบ 2 ที่อยู่ในโหมดส่งอัตโนมัติ (เคยได้ WAIT_NOT_OVER) และขั้นนั้นยังไม่เสร็จบน server */
  const autoMode = !!pending && pending.kind === 'waited' && !!pending.waitUntil && !waitedDone && !disputed;
  /**
   * เวลาเครื่องที่ควรส่งรูปที่รอไว้ (null = ไม่มีรูปรอส่งตามเวลา / ล้มครบโควตาแล้ว)
   * ไม่เร็วกว่าเวลาครบของ server และไม่เร็วกว่าเวลาพักหลังล้มครั้งก่อน (ทวีคูณ — B1)
   */
  const retryAtMs = autoMode
    ? nextAutoRetryAt(autoRetry, deviceTimeFor(pending?.waitUntil ?? waitUntilIso, clockOffset))
    : null;
  /** ส่งอัตโนมัติล้มครบโควตา → ไรเดอร์ต้องกด "ส่งรูปอีกครั้ง" เอง */
  const autoStopped = autoMode && autoRetryExhausted(autoRetry);

  // ให้ตัวกลับเข้าแอป (AppState) เรียกใช้ได้: ครบเวลาแล้วและไม่มีงานค้าง → ส่งเลย
  retryDueRef.current = () => {
    // เผื่อตัวจับเวลาของเครื่องยิงก่อนเวลาเล็กน้อย
    if (retryAtMs !== null && Date.now() >= retryAtMs - 250 && !busyRef.current) retryPending('auto');
  };

  useEffect(() => {
    if (retryAtMs === null || busy !== null) return undefined;
    const delay = Math.max(0, retryAtMs - Date.now());
    const timer = setTimeout(() => {
      if (mountedRef.current && AppState.currentState === 'active') retryDueRef.current();
    }, delay);
    return () => clearTimeout(timer);
  }, [retryAtMs, busy]);

  const textCustomer = async () => {
    const clean = String(job.dropoff?.phone || '').replace(/[^\d+]/g, '');
    if (clean.length < 3) {
      Alert.alert('ไม่มีเบอร์ลูกค้า', 'งานนี้ยังไม่มีเบอร์ให้ส่งข้อความ');
      return;
    }
    try {
      await Linking.openURL(`sms:${clean}`);
    } catch {
      Alert.alert('เปิดข้อความไม่ได้', 'ลองโทรหาลูกค้าแทนนะ');
    }
  };

  // รูปค้างส่งที่ยังใช้ได้ (ขั้นนั้นยังไม่เสร็จบน server)
  const pendingUsable = useMemo(() => {
    if (!pending) return null;
    if (pending.kind === 'arrival' && arrived) return null;
    if (pending.kind === 'waited' && waitedDone) return null;
    return pending;
  }, [pending, arrived, waitedDone]);

  // server รับรูปนั้นไปแล้ว (เช่น ส่งสำเร็จตอนแอปอยู่เบื้องหลัง) → ทิ้งรูปค้างในเครื่อง
  const stalePending = !!pending && !!data && !pendingUsable && busy === null;
  useEffect(() => {
    if (!stalePending) return;
    setAutoRetry(INITIAL_AUTO_RETRY);
    setPending(null);
    persist({ pending: null });
  }, [stalePending, persist, setAutoRetry]);

  // =====================================================
  // แสดงผล
  // =====================================================

  const buyerCard = (
    <Card3D padding={spacing.md + 2} style={styles.block}>
      <View style={styles.buyerRow}>
        <PersonAvatar uri={buyerPhoto} name={buyerName} size={60} ring="gold" verified={buyerVerified} />
        <View style={styles.flex}>
          <Text numberOfLines={1} style={[typography.h3, { color: colors.textStrong }]}>
            {buyerName}
          </Text>
          {!!(job.dropoff?.address || job.dropoff?.area) && (
            <Text numberOfLines={2} style={[typography.caption, { color: colors.textMuted }]}>
              {job.dropoff?.address || job.dropoff?.area}
            </Text>
          )}
        </View>
        <ActionIcon icon="phone" label="โทรหาลูกค้า" onPress={() => callPhone(job.dropoff?.phone)} />
        <ActionIcon icon="chat-circle-dots" label="ส่งข้อความหาลูกค้า" onPress={textCustomer} />
      </View>
      {!!job.dropoff?.notes && (
        <View style={[styles.noteRow, { backgroundColor: colors.warningSoft }]}>
          <Icon name="note-pencil" size={15} color={colors.warning} />
          <Text style={[typography.caption, styles.flex, { color: colors.text }]}>{job.dropoff.notes}</Text>
        </View>
      )}
    </Card3D>
  );

  const headerPills = (
    <View style={styles.pills}>
      {canBlockCapture() && <Pill label="ป้องกันการแคปหน้าจอ" tone="neutral" icon="shield-check" />}
      {!data && !loadError && <Pill label="กำลังโหลด..." tone="neutral" icon="hourglass" />}
    </View>
  );

  // ---------- ลูกค้าแจ้งปัญหา (ทุกสถานะ — ระหว่างส่ง หรือหลังวางของแล้ว) L4 / §A6 ----------
  if (disputed) {
    return (
      <View style={style}>
        {headerPills}
        {buyerCard}
        <NoticeCard
          icon="warning-circle"
          tone="danger"
          title="ลูกค้าแจ้งว่ามีปัญหา"
          titleColor={colors.danger}
          message={
            waitedDone || job.status === 'awaiting_release'
              ? 'ทีมงานกำลังตรวจรูปถ่ายและตำแหน่งตอนส่งของ แล้วจะแจ้งผลให้ทราบ ระหว่างนี้รับงานใหม่ได้ตามปกติ'
              : 'ลูกค้าแจ้งปัญหาการรับของ ทีมงานจะตรวจสอบและแจ้งผลให้ทราบ งานนี้ไม่ต้องสแกนหรือถ่ายรูปต่อ ระหว่างนี้รับงานใหม่ได้ตามปกติ'
          }
          style={styles.block}
        />
        <Card3D padding={spacing.lg} style={styles.block}>
          <View style={styles.releaseHead}>
            <IconTile icon="lock" tone="navy" size={44} />
            <View style={styles.flex}>
              <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>เงินค่าส่งงานนี้พักไว้ก่อน</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>
                ทีมงานตัดสินแล้วระบบจะปลดเงินหรือคืนเงินตามผล และแจ้งเตือนให้ทราบทันที
              </Text>
            </View>
          </View>
          <View style={[styles.releaseRow, { borderTopColor: colors.divider }]}>
            <Text style={[typography.bodySm, { color: colors.textMuted }]}>ค่าส่งของงานนี้</Text>
            <PriceText amount={amount} size="lg" tone="gold" />
          </View>
        </Card3D>
      </View>
    );
  }

  // ---------- วางของแล้ว รอปลดเงิน ----------
  if (waitedDone) {
    return (
      <View style={style}>
        {headerPills}
        {buyerCard}
        {(
          <Card3D gradientBorder padding={spacing.lg} style={styles.block}>
            <View style={styles.releaseHead}>
              <IconTile icon="hourglass" tone="gold" size={44} />
              <View style={styles.flex}>
                <Text style={[typography.h3, { color: colors.textStrong }]}>วางของเรียบร้อย รอปลดเงิน</Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  ถ้าลูกค้าไม่ร้องเรียนภายใน 24 ชม. ระบบปลดเงินให้อัตโนมัติ
                </Text>
              </View>
            </View>
            <View style={[styles.releaseRow, { borderTopColor: colors.divider }]}>
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>เงินที่จะได้รับ</Text>
              <PriceText amount={amount} size="lg" tone="gold" />
            </View>
            {!!h?.auto_release_at && (
              <Text style={[typography.caption, { color: colors.textMuted }]}>
                ปลดเงินประมาณ {formatThaiDateTime(h.auto_release_at)}
              </Text>
            )}
          </Card3D>
        )}
      </View>
    );
  }

  // ---------- กำลังส่งมอบ ----------
  const step1State: 'done' | 'active' = arrived ? 'done' : 'active';
  const step2State: 'done' | 'active' | 'locked' = !arrived ? 'locked' : waitOver ? 'done' : 'active';
  const step3State: 'active' | 'locked' = waitOver ? 'active' : 'locked';
  const arrivalShot = arrival && arrived ? arrival : null;
  const arrivalDistance = formatMeters(arrivalShot?.distanceM);
  const progress = arrived ? 1 - remaining / waitSeconds : 0;

  /** การ์ดรูปค้างส่ง: รอครบเวลา / ส่งอัตโนมัติล้ม (จะลองอีก) / หยุดส่งอัตโนมัติแล้ว / ส่งไม่ออก */
  const pendingNotice: {
    icon: IconName;
    tone: 'info' | 'warning';
    title: string;
    message: string;
    action: string;
  } | null = (() => {
    if (!pendingUsable) return null;
    const transport = isTransportFailure(autoRetry.lastFailure);
    const reason = transport ? `${autoRetryReasonText(autoRetry.lastFailure)} ` : '';
    if (retryAtMs !== null) {
      const at = clock(new Date(retryAtMs).toISOString());
      return transport
        ? {
            icon: 'arrows-clockwise',
            tone: 'warning',
            title: 'ส่งรูปรอบ 2 ยังไม่สำเร็จ',
            message: `${reason}ระบบจะลองส่งให้อีกครั้งตอน ${at} น. ไม่ต้องถ่ายใหม่`,
            action: 'ส่งตอนนี้',
          }
        : {
            icon: 'hourglass',
            tone: 'info',
            title: 'ถ่ายรูปรอบ 2 ไว้แล้ว รอครบเวลา',
            message: `ระบบยังไม่ครบเวลารอลูกค้า จะส่งรูปนี้ให้อัตโนมัติตอน ${at} น. ไม่ต้องถ่ายใหม่`,
            action: 'ส่งตอนนี้',
          };
    }
    if (autoStopped) {
      return {
        icon: 'upload-simple',
        tone: 'warning',
        title: 'ส่งรูปรอบ 2 อัตโนมัติไม่สำเร็จ',
        message: `${reason}ลองส่งให้หลายครั้งแล้ว ตรวจสัญญาณเน็ตแล้วกด "ส่งรูปอีกครั้ง" ได้เลย ไม่ต้องถ่ายใหม่`,
        action: 'ส่งรูปอีกครั้ง',
      };
    }
    return {
      icon: 'upload-simple',
      tone: 'warning',
      title: pendingUsable.kind === 'arrival' ? 'รูปรอบ 1 ยังส่งไม่สำเร็จ' : 'รูปรอบ 2 ยังส่งไม่สำเร็จ',
      message: `ถ่ายไว้เมื่อ ${clock(pendingUsable.takenAt)} น. กดส่งอีกครั้งได้เลย ไม่ต้องถ่ายใหม่`,
      action: 'ส่งรูปอีกครั้ง',
    };
  })();

  return (
    <View style={style}>
      {headerPills}
      {buyerCard}

      {!!loadError && !data && (
        <NoticeCard icon="wifi-slash" tone="warning" message={loadError} style={styles.block}>
          <Button3D title="ลองใหม่" icon="arrows-clockwise" size="sm" variant="secondary" onPress={fetchHandover} style={styles.alignStart} />
        </NoticeCard>
      )}

      {/* ---------- ทางหลัก: สแกน QR ใส่กัน ---------- */}
      {riderConfirmed ? (
        <NoticeCard
          icon="check-circle"
          tone="success"
          title="สแกน QR ของลูกค้าแล้ว"
          message={buyerConfirmed ? 'ลูกค้ายืนยันแล้ว กำลังปิดงาน...' : 'เหลือให้ลูกค้าสแกน QR ของคุณด้านล่าง เพื่อปิดงานและรับเงิน'}
          style={styles.block}
        />
      ) : (
        <View style={styles.block}>
          <Button3D
            title="สแกน QR ของลูกค้า"
            icon="scan"
            variant="primary"
            size="lg"
            fullWidth
            loading={busy === 'scan'}
            loadingText="กำลังยืนยัน..."
            disabled={busy !== null && busy !== 'scan'}
            onPress={() => setScanOpen(true)}
            accessibilityHint="เปิดกล้องสแกน QR ที่ลูกค้าเปิดในแอป"
          />
          <Button3D
            title="กรอกรหัส 6 หลักแทน"
            variant="secondary"
            fullWidth
            disabled={busy !== null}
            onPress={openCodeSheet}
            style={styles.gapTop}
          />
        </View>
      )}

      {/* ---------- QR ของไรเดอร์ให้ลูกค้าสแกน ---------- */}
      <Card3D padding={spacing.lg} style={styles.block}>
        <Pressable
          onPress={() => setShowMyQr((v) => !v)}
          accessibilityRole="button"
          accessibilityState={{ expanded: showMyQr }}
          accessibilityLabel={showMyQr ? 'ซ่อน QR ของฉัน' : 'แสดง QR ของฉันให้ลูกค้าสแกน'}
          style={styles.qrHead}
        >
          <IconTile icon="qr-code" tone={buyerConfirmed ? 'success' : 'navy'} size={40} />
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ให้ลูกค้าสแกน QR ของคุณ</Text>
            <Text style={[typography.caption, { color: buyerConfirmed ? colors.success : colors.textMuted }]}>
              {buyerConfirmed ? 'ลูกค้าสแกนแล้ว' : 'ยืนยันว่าลูกค้าได้รับของจากคุณจริง'}
            </Text>
          </View>
          {!buyerConfirmed && <Icon name={showMyQr ? 'caret-up' : 'caret-down'} size={18} color={colors.textMuted} />}
        </Pressable>
        {showMyQr && !buyerConfirmed && (
          <View style={styles.qrBody}>
            {h?.qr_token ? (
              <RotatingQr
                token={h.qr_token}
                size={200}
                expiresAt={h.qr_expires_at ?? null}
                clockOffsetMs={clockOffset}
                onExpire={fetchHandover}
                caption="QR เปลี่ยนใหม่อัตโนมัติ ใช้ได้ครั้งเดียว"
              />
            ) : (
              <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>
                {loadError ? 'โหลด QR ไม่สำเร็จ ดึงหน้าจอลงเพื่อลองใหม่' : 'กำลังเตรียม QR...'}
              </Text>
            )}
            {/* รหัส 6 หลักของไรเดอร์ — กล้องลูกค้าใช้ไม่ได้ ให้อ่านรหัสนี้ให้ลูกค้ากรอก (§A3) */}
            {!!h?.code && (
              <View style={styles.codeBlock}>
                <View style={[styles.dashed, { borderColor: colors.border }]} />
                <Text style={[typography.bodySm, styles.center, { color: colors.textMuted }]}>
                  กล้องลูกค้าใช้ไม่ได้? บอกรหัส 6 หลักนี้แทน
                </Text>
                <View style={styles.codeRow} accessible accessibilityLabel={`รหัสส่งมอบของไรเดอร์ ${h.code.split('').join(' ')}`}>
                  {h.code.split('').map((digit, i) => (
                    <View
                      key={i}
                      style={[styles.codeTile, { backgroundColor: colors.inset, borderColor: colors.border }, i === 2 && styles.codeGap]}
                    >
                      <Text style={[styles.codeDigit, { color: colors.textStrong }]}>{digit}</Text>
                    </View>
                  ))}
                </View>
              </View>
            )}
          </View>
        )}
      </Card3D>

      {/* ---------- รูปค้างส่ง (สถานะการส่งอัตโนมัติแสดงตรงนี้ ไม่เด้ง Alert — B1) ---------- */}
      {!!pendingNotice && busy === null && (
        <NoticeCard
          icon={pendingNotice.icon}
          tone={pendingNotice.tone}
          title={pendingNotice.title}
          message={pendingNotice.message}
          style={styles.block}
        >
          <View style={styles.rowGap}>
            <Button3D
              title={pendingNotice.action}
              icon="upload-simple"
              size="sm"
              onPress={() => retryPending('manual')}
              style={styles.flex}
            />
            <Button3D title="ทิ้งรูปนี้" size="sm" variant="ghost" onPress={discardPending} />
          </View>
        </NoticeCard>
      )}

      {/* งานเก็บเงินปลายทาง: ไม่มีทางสำรองรูป 2 รอบ (server ไม่รับ) */}
      {!fallbackAllowed && (
        <NoticeCard
          icon="money"
          tone="warning"
          title="งานเก็บเงินปลายทาง ต้องส่งมอบกับลูกค้าโดยตรง"
          message={
            buyerConfirmed
              ? 'ลูกค้ายืนยันรับของแล้ว สแกน QR หรือกรอกรหัสของลูกค้าเพื่อปิดงาน'
              : canFail
                ? 'ลูกค้าไม่อยู่หรือติดต่อไม่ได้ ใช้ปุ่ม "ส่งไม่สำเร็จ" ด้านล่างแทนการวางของ'
                : 'ลูกค้าไม่อยู่หรือติดต่อไม่ได้ โทรหาลูกค้า หรือติดต่อทีมงานที่หน้าช่วยเหลือ'
          }
          style={styles.block}
        />
      )}

      {/* ---------- ทางสำรอง: ลูกค้าไม่สแกน / ไม่อยู่ ---------- */}
      {fallbackAllowed && (
      <Card3D padding={0} style={styles.block}>
        <View style={[styles.fallbackHead, { borderBottomColor: colors.divider }]}>
          <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>ลูกค้าไม่สแกน / ไม่อยู่</Text>
          {arrived && <Pill label="ระบบแจ้งลูกค้าแล้ว" tone="info" icon="bell-ringing" />}
        </View>

        <View style={styles.fallbackBody}>
          <StepRow
            first
            index={1}
            state={step1State}
            title="ถ่ายรูปรอบ 1: ถึงจุดส่งแล้ว"
            caption={
              arrived
                ? `GPS ตรงจุดส่ง${arrivalDistance ? ` (ห่าง ${arrivalDistance})` : ''} · เริ่มนับรอ`
                : `ต้องอยู่ห่างจุดส่งไม่เกิน ${geofence} ม. · ระบบแจ้งลูกค้าให้ออกมารับ`
            }
            right={
              arrivalShot ? (
                <View style={[styles.thumb, { backgroundColor: colors.inset }]}>
                  <Image source={{ uri: arrivalShot.uri }} style={StyleSheet.absoluteFill} contentFit="cover" />
                  <View style={[styles.thumbScrim, { backgroundColor: colors.overlay }]}>
                    <Text style={[typography.micro, { color: colors.onHeader }]}>{clock(h?.arrival_photo_at)}</Text>
                  </View>
                </View>
              ) : arrived ? (
                <IconTile icon="image" tone="success" size={44} />
              ) : null
            }
          />
          <StepRow
            index={2}
            state={step2State}
            title={`รอลูกค้า ${waitMinutes} นาที`}
            caption={waitOver ? 'ครบเวลาแล้ว' : 'โทรและส่งข้อความหาลูกค้าระหว่างรอได้'}
            right={
              arrived && !waitOver ? (
                <ProgressRing progress={progress} size={78} stroke={7} color={colors.gold} track={colors.inset}>
                  <Text
                    style={[typography.h2, styles.tabular, { color: colors.textStrong }]}
                    accessibilityLabel={`เหลือเวลารอ ${mmss(remaining)} นาที`}
                  >
                    {mmss(remaining)}
                  </Text>
                </ProgressRing>
              ) : null
            }
          />
          <StepRow
            index={3}
            state={step3State}
            title="ถ่ายรูปรอบ 2: รอครบแล้ว"
            caption="ถ่ายของที่วางไว้ให้เห็นจุดส่งชัดๆ"
            right={<IconTile icon="camera" tone={waitOver ? 'gold' : 'neutral'} size={44} />}
          />

          {!arrived ? (
            <Button3D
              title="ถ่ายรูปรอบ 1 ที่จุดส่ง"
              icon="camera"
              variant="navy"
              fullWidth
              loading={busy === 'arrival'}
              loadingText="กำลังส่งรูป..."
              disabled={(busy !== null && busy !== 'arrival') || (!!h && !h.can_arrival_photo)}
              onPress={() => shoot('arrival')}
              style={styles.fallbackButton}
            />
          ) : waitOver ? (
            <Button3D
              title="ถ่ายรูปรอบ 2"
              icon="camera"
              variant="primary"
              size="lg"
              fullWidth
              loading={busy === 'waited'}
              loadingText="กำลังส่งรูป..."
              disabled={busy !== null && busy !== 'waited'}
              onPress={() => shoot('waited')}
              style={styles.fallbackButton}
            />
          ) : (
            <View
              style={[styles.lockedButton, { borderColor: colors.border, backgroundColor: colors.inset }]}
              accessible
              accessibilityLabel={`ถ่ายรูปรอบ 2 ได้เมื่อครบ ${waitMinutes} นาที`}
            >
              <Icon name="camera" size={18} color={colors.textFaint} />
              <Text style={[typography.bodyStrong, { color: colors.textFaint }]}>ถ่ายรูปรอบ 2 ได้เมื่อครบ {waitMinutes} นาที</Text>
            </View>
          )}
          {!arrived && !!h && !h.can_arrival_photo && (
            <Text style={[typography.caption, styles.hint, { color: colors.textMuted }]}>
              กด "เริ่มไปส่ง" แล้วไปให้ถึงจุดส่งก่อน จึงถ่ายรูปรอบ 1 ได้
            </Text>
          )}
        </View>
      </Card3D>
      )}

      {fallbackAllowed && (
        <View style={[styles.footerNote, { backgroundColor: colors.successSoft }]}>
          <Icon name="wallet" size={18} color={colors.success} />
          <Text style={[typography.caption, styles.flex, { color: isDark ? colors.text : colors.textStrong }]}>
            ทำครบ 3 ขั้นแล้ว ถ้าลูกค้าไม่ร้องเรียนภายใน 24 ชม. ระบบจะปลดเงิน {formatBaht(amount)} ให้คุณอัตโนมัติ
          </Text>
        </View>
      )}

      {/* ---------- กล้องสแกน QR ของลูกค้า ---------- */}
      <QrScannerSheet
        visible={scanOpen}
        title="สแกน QR ของลูกค้า"
        onClose={() => setScanOpen(false)}
        onScanned={onScanned}
        codeFallback={{
          length: 6,
          onSubmit: (value: string) => {
            setScanOpen(false);
            submitScan({ code: value }, 'scanner');
          },
        }}
      />

      {/* ---------- กรอกรหัส 6 หลัก ---------- */}
      <RiderSheet
        visible={codeOpen}
        icon="key"
        title="กรอกรหัส 6 หลักของลูกค้า"
        subtitle="ให้ลูกค้าเปิดหน้าส่งมอบในแอป แล้วอ่านรหัสให้ฟัง"
        busy={busy === 'code'}
        onClose={() => setCodeOpen(false)}
        footer={
          <>
            <Button3D
              title="ยืนยันรหัส"
              icon="check-circle"
              size="lg"
              fullWidth
              loading={busy === 'code'}
              loadingText="กำลังยืนยัน..."
              disabled={code.replace(/\D/g, '').length !== 6}
              onPress={() => submitCode()}
            />
            <Button3D title="ยกเลิก" variant="ghost" size="sm" onPress={() => setCodeOpen(false)} disabled={busy === 'code'} />
          </>
        }
      >
        <FocusInput
          value={code}
          onChangeText={(text) => {
            setCode(text.replace(/\D/g, '').slice(0, 6));
            setCodeError(null);
          }}
          keyboardType="number-pad"
          maxLength={6}
          autoFocus
          placeholder="000000"
          textContentType="oneTimeCode"
          accessibilityLabel="รหัส 6 หลักของลูกค้า"
          invalid={!!codeError}
          style={[typography.h1, styles.codeInput]}
          onSubmitEditing={() => submitCode()}
        />
        {!!codeError && <Text style={[typography.caption, { color: colors.danger }]}>{codeError}</Text>}
      </RiderSheet>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    textAlign: 'center',
  },
  block: {
    marginBottom: spacing.lg,
  },
  gapTop: {
    marginTop: spacing.sm,
  },
  alignStart: {
    alignSelf: 'flex-start',
  },
  rowGap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginBottom: spacing.sm,
  },
  buyerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  noteRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    borderRadius: radii.md,
    padding: spacing.sm + 2,
    marginTop: spacing.md,
  },
  qrHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    minHeight: 44,
  },
  qrBody: {
    alignItems: 'center',
    marginTop: spacing.lg,
  },
  fallbackHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md + 2,
    borderBottomWidth: 1,
  },
  fallbackBody: {
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.lg,
  },
  step: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.md + 2,
  },
  stepBadge: {
    width: 30,
    height: 30,
    borderRadius: 15,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  thumb: {
    width: 84,
    height: 64,
    borderRadius: radii.md,
    overflow: 'hidden',
    justifyContent: 'flex-end',
  },
  thumbScrim: {
    paddingHorizontal: 6,
    paddingVertical: 2,
  },
  tabular: {
    fontVariant: ['tabular-nums'],
  },
  fallbackButton: {
    marginTop: spacing.sm,
  },
  lockedButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    minHeight: 54,
    marginTop: spacing.sm,
    borderRadius: radii.lg,
    borderWidth: 1.5,
    borderStyle: 'dashed',
  },
  hint: {
    textAlign: 'center',
    marginTop: spacing.sm,
  },
  releaseHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  releaseRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderTopWidth: 1,
    marginTop: spacing.md,
    paddingTop: spacing.md,
    marginBottom: spacing.xs,
  },
  footerNote: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    borderRadius: radii.lg,
    padding: spacing.md + 2,
    marginBottom: spacing.lg,
  },
  codeInput: {
    textAlign: 'center',
    letterSpacing: 8,
  },
  codeBlock: {
    alignSelf: 'stretch',
  },
  dashed: {
    borderTopWidth: 1,
    borderStyle: 'dashed',
    marginVertical: spacing.lg,
  },
  codeRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  codeTile: {
    width: 44,
    height: 54,
    borderRadius: radii.md,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  codeGap: {
    marginRight: spacing.md,
  },
  codeDigit: {
    fontSize: 26,
    lineHeight: 34,
    fontWeight: '700',
    fontVariant: ['tabular-nums'],
  },
});

export default RiderHandoverPanel;
