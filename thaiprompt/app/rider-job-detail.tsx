/**
 * รายละเอียดงานไรเดอร์ — ขั้นตอนส่งของตั้งแต่รับงานจนส่งสำเร็จ
 *
 * - ปุ่มที่แสดง = job.allowed_actions จาก server เท่านั้น (RIDER-APP-03/04: ไม่ค้างที่ accepted/delivered)
 * - ส่งสำเร็จ = ถ่ายรูป (บังคับ) + ยืนยันเก็บเงินสดเมื่อมี COD → POST /deliver (RIDER-APP-09)
 * - ส่งไม่สำเร็จ = เลือกเหตุผล (+ รายละเอียดเมื่อเลือก "อื่นๆ") · คืนงาน = ก่อนรับของเท่านั้น
 * - ติดตามตำแหน่ง: เริ่มเมื่อรับงาน หยุดเมื่อจบ/ยกเลิก/คืนงาน — ไม่ผูกกับการเปิดปิดหน้านี้ (RIDER-APP-12)
 * - ตำแหน่งสดของลูกค้าแสดงเฉพาะเมื่อลูกค้าแชร์มา
 * - ส่งสำเร็จ → ฉลอง "รับทรัพย์!" พร้อมยอดที่ได้จริงจาก server
 *
 * หน้าตา: การ์ดฮีโร่น้ำเงินลายกนก (ค่าส่งทอง + ตัวเลขระยะทาง + แถบขั้นตอน 5 ขั้น)
 *         → การ์ดเส้นทางจุดรับ/จุดส่ง (จุดหมายตอนนี้เด่นพร้อมปุ่มนำทาง) → รายละเอียด → แถบปุ่มทองท้ายจอ
 *
 * params: id (ไม่ส่ง = งานปัจจุบัน), accepted=1 (เพิ่งรับงาน)
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Alert, Pressable, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme, spacing, radii, typography, shadowStyle, withAlpha } from '@/theme';
import {
  Button3D,
  Card3D,
  Chip,
  EmptyState,
  Icon,
  Pill,
  PriceText,
  Screen,
  formatBaht,
  resultHaptic,
  type IconName,
} from '@/components/ui';
import { num } from '@/services/api/client';
import {
  deliverRiderJob,
  failRiderJob,
  getCurrentJob,
  getRiderJob,
  releaseRiderJob,
  updateRiderJobStatus,
  type RiderFailReason,
  type RiderJobAction,
  type RiderJobDetail,
  type RiderJobPoint,
} from '@/services/api/riderApi';
import { addNotificationReceivedListener } from '@/services/notifications';
import {
  getCurrentCoords,
  getJobTrackingState,
  isLocationServiceEnabled,
  startJobTracking,
  stopJobTrackingFor,
  type TrackingMode,
} from '@/services/location';
import { useRiderPermissionFlow } from '@/components/rider/useRiderPermissionFlow';
import { useAcceptJob } from '@/components/rider/useAcceptJob';
import { RiderSheet } from '@/components/rider/RiderSheet';
import { JobCompleteCelebration } from '@/components/rider/JobCompleteCelebration';
import { takePhoto } from '@/components/rider/photo';
import {
  FAIL_REASONS,
  JOB_STATUS_LABEL,
  JOB_STATUS_TONE,
  JOB_STEPS,
  RELEASE_REASONS,
  callPhone,
  formatAgo,
  formatKm,
  formatMinutes,
  formatThaiDateTime,
  formatTime,
  isActiveJobStatus,
  isFinishedJobStatus,
  openNavigation,
  stepIndexForStatus,
} from '@/components/rider/riderHelpers';
import {
  ActionIcon,
  DottedLine,
  FocusInput,
  IconTile,
  NavyCard,
  NoticeCard,
  StopDot,
  TextAction,
} from '@/components/rider/RiderVisuals';

const POLL_MS = 20_000;

/** รอ sheet ปิดสนิทก่อนแสดง Alert/หน้าฉลอง */
const SHEET_SETTLE_MS = 450;

type SheetKind = 'pickup' | 'deliver' | 'fail' | 'release';

const FLOW_ORDER: RiderJobAction[] = ['accept', 'picking_up', 'picked_up', 'delivering', 'deliver'];

/** หน้าตาปุ่มของแต่ละขั้น — การกระทำหลักทุกขั้นเป็นปุ่มทอง (primary) ตามระบบดีไซน์ */
const ACTION_LOOK: Record<RiderJobAction, { title: string; icon: IconName; variant: 'primary' | 'success' | 'danger' | 'ghost' }> = {
  accept: { title: 'รับงานนี้', icon: 'hand-tap', variant: 'primary' },
  picking_up: { title: 'กำลังไปรับของ', icon: 'moped', variant: 'primary' },
  picked_up: { title: 'รับของแล้ว', icon: 'package', variant: 'primary' },
  delivering: { title: 'เริ่มไปส่ง', icon: 'navigation-arrow', variant: 'primary' },
  deliver: { title: 'ส่งสำเร็จ', icon: 'check-circle', variant: 'primary' },
  fail: { title: 'ส่งไม่สำเร็จ', icon: 'warning', variant: 'ghost' },
  release: { title: 'คืนงาน', icon: 'arrow-counter-clockwise', variant: 'ghost' },
};

const STEP_HINT: Record<string, string> = {
  pending: 'รับงานนี้เพื่อดูที่อยู่เต็มและเบอร์ติดต่อ',
  accepted: 'ออกเดินทางไปร้าน แล้วกด "กำลังไปรับของ"',
  picking_up: 'ถึงร้านแล้วตรวจของให้ครบ จากนั้นกด "รับของแล้ว"',
  picked_up: 'ได้ของแล้ว กด "เริ่มไปส่ง" แล้วนำทางไปหาลูกค้า',
  delivering: 'ถึงลูกค้าแล้ว ส่งของ ถ่ายรูป แล้วกด "ส่งสำเร็จ"',
};

// =====================================================
// ชิ้นส่วนย่อย
// =====================================================

const STEP_CIRCLE = 34;

/** แถบขั้นตอน 5 ขั้นบนการ์ดน้ำเงิน (เสร็จ = ทอง · ตอนนี้ = วงทองเรือง · ยังไม่ถึง = กระจก) */
const StepProgress: React.FC<{ job: RiderJobDetail }> = ({ job }) => {
  const { colors, gradients } = useTheme();
  const current = stepIndexForStatus(job.status);
  const failedOrCancelled = job.status === 'failed' || job.status === 'cancelled';
  const times: Record<string, string | null | undefined> = {
    accepted: job.timeline?.accepted_at ?? job.accepted_at,
    picked_up: job.timeline?.picked_up_at,
    completed: job.timeline?.completed_at ?? job.timeline?.delivered_at ?? job.completed_at,
  };
  const showCounter = !failedOrCancelled && current >= 0;

  return (
    <View style={[styles.progress, { borderTopColor: colors.headerGlassBorder }]}>
      <View style={styles.progressHead}>
        <Text style={[typography.overline, { color: colors.onHeaderMuted }]}>ความคืบหน้า</Text>
        {showCounter && (
          <Text style={[typography.caption, { color: colors.goldLight }]}>
            ขั้นที่ {Math.min(current + 1, JOB_STEPS.length)}/{JOB_STEPS.length}
          </Text>
        )}
      </View>
      <View style={styles.stepsRow}>
        {JOB_STEPS.map((step, index) => {
          const done = !failedOrCancelled && index < current;
          const active = !failedOrCancelled && index === current;
          const isLast = index === JOB_STEPS.length - 1;
          const complete = active && isLast;
          const reached = done || active;
          const nextReached = !failedOrCancelled && index + 1 <= current;
          const time = times[step.key];
          const stateText = done || complete ? 'เสร็จแล้ว' : active ? 'ขั้นตอนตอนนี้' : 'ยังไม่ถึง';
          return (
            <View
              key={step.key}
              style={styles.stepCol}
              accessible
              accessibilityLabel={`${step.label} ${stateText}${time && reached ? ` เวลา ${formatTime(time)}` : ''}`}
            >
              <View style={styles.stepTrack}>
                <View style={styles.stepLines} pointerEvents="none">
                  <View
                    style={[
                      styles.flex,
                      { backgroundColor: index === 0 ? 'transparent' : reached ? colors.gold : colors.headerGlassBorder },
                    ]}
                  />
                  <View
                    style={[
                      styles.flex,
                      { backgroundColor: isLast ? 'transparent' : nextReached ? colors.gold : colors.headerGlassBorder },
                    ]}
                  />
                </View>
                {complete ? (
                  <LinearGradient colors={gradients.success} style={styles.stepCircle}>
                    <Icon name={step.icon} size={17} color={colors.textOnAccent} weight="fill" />
                  </LinearGradient>
                ) : done ? (
                  <LinearGradient colors={gradients.primary} style={styles.stepCircle}>
                    <Icon name="check" size={16} color={colors.textOnGold} weight="bold" />
                  </LinearGradient>
                ) : active ? (
                  <View
                    style={[
                      styles.stepCircle,
                      styles.stepCircleActive,
                      {
                        borderColor: colors.goldLight,
                        backgroundColor: colors.navyDeep,
                        boxShadow: `0px 0px 16px 2px ${withAlpha(colors.gold, 0.55)}`,
                      },
                    ]}
                  >
                    <Icon name={step.icon} size={17} color={colors.goldLight} weight="fill" />
                  </View>
                ) : (
                  <View
                    style={[
                      styles.stepCircle,
                      styles.stepCircleIdle,
                      { backgroundColor: colors.navyDeep, borderColor: colors.headerGlassBorder },
                    ]}
                  >
                    <Icon name={step.icon} size={16} color={colors.onHeaderMuted} />
                  </View>
                )}
              </View>
              <Text
                numberOfLines={2}
                style={[
                  typography.micro,
                  styles.stepLabel,
                  { color: active ? colors.goldLight : done ? colors.onHeader : colors.onHeaderMuted },
                ]}
              >
                {step.label}
              </Text>
              {!!time && reached && (
                <Text style={[typography.micro, styles.stepTime, { color: colors.onHeaderMuted }]}>{formatTime(time)}</Text>
              )}
            </View>
          );
        })}
      </View>
    </View>
  );
};

/**
 * จุดรับ/จุดส่งในการ์ดเส้นทาง
 * current = จุดหมายตอนนี้ → จุดมีวงแสง + ป้าย + ปุ่มนำทางเต็มความกว้าง (จุดอื่นเป็นปุ่มไอคอน)
 */
const StopBlock: React.FC<{
  kind: 'pickup' | 'dropoff';
  point: RiderJobPoint;
  canContact: boolean;
  current: boolean;
  isLast?: boolean;
}> = ({ kind, point, canContact, current, isLast = false }) => {
  const { colors } = useTheme();
  const isPickup = kind === 'pickup';
  const title = isPickup ? 'จุดรับของ' : 'จุดส่งของ';
  const name = point?.name || (isPickup ? 'ร้านค้า' : point?.area || 'ลูกค้า');
  const address = point?.address || (!isPickup ? point?.area : null);
  const showPhone = canContact && !!point?.phone;

  return (
    <View style={styles.stop}>
      <View style={styles.stopRail}>
        <StopDot kind={kind} size={13} halo={current} />
        {!isLast && <DottedLine style={styles.stopLine} />}
      </View>
      <View style={[styles.flex, !isLast && styles.stopBodyGap]}>
        <View style={styles.stopHead}>
          <View style={styles.flex}>
            <View style={styles.stopLabelRow}>
              <Text style={[typography.overline, { color: colors.textMuted }]}>{title}</Text>
              {current && <Pill label="จุดหมายตอนนี้" tone="gold" icon="target" />}
            </View>
            <Text style={[typography.h3, { color: colors.textStrong }]} numberOfLines={2}>
              {name}
            </Text>
          </View>
          <View style={styles.stopActions}>
            {showPhone && (
              <ActionIcon
                icon="phone"
                label={`โทรหา${isPickup ? 'ร้าน' : 'ลูกค้า'}`}
                onPress={() => callPhone(point.phone)}
              />
            )}
            {!current && (
              <ActionIcon icon="navigation-arrow" label={`นำทางไป${title}`} onPress={() => openNavigation(point)} />
            )}
          </View>
        </View>

        {/* ชื่อกับที่อยู่เป็นค่าเดียวกัน (จุดส่งโดยประมาณ = ชื่อย่าน) → แสดงครั้งเดียว */}
        {!!address && address !== name && (
          <Text style={[typography.bodySm, styles.stopText, { color: colors.text }]}>{address}</Text>
        )}
        {point?.is_approximate && (
          <View style={styles.inlineRow}>
            <Icon name="map-pin" size={14} color={colors.textMuted} />
            <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>
              แสดงพื้นที่โดยประมาณ — ที่อยู่เต็มและเบอร์โทรจะแสดงหลังรับงาน
            </Text>
          </View>
        )}
        {!!point?.notes && (
          <View style={[styles.noteBox, { backgroundColor: colors.warningSoft }]}>
            <Icon name="note-pencil" size={16} color={colors.warning} />
            <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>{point.notes}</Text>
          </View>
        )}
        {current && (
          <Button3D
            title="นำทาง"
            icon="navigation-arrow"
            variant="navy"
            fullWidth
            onPress={() => openNavigation(point)}
            style={styles.navButton}
            accessibilityLabel={`นำทางไป${title}`}
          />
        )}
      </View>
    </View>
  );
};

const PhotoSlot: React.FC<{
  uri: string | null;
  label: string;
  required?: boolean;
  onTake: () => unknown;
  onClear?: () => void;
}> = ({ uri, label, required, onTake, onClear }) => {
  const { colors } = useTheme();
  if (uri) {
    return (
      <View style={styles.photoWrap}>
        <View style={[styles.photoFrame, { backgroundColor: colors.inset }]}>
          <Image source={{ uri }} style={styles.photo} contentFit="cover" />
          <View style={[styles.photoBadge, { backgroundColor: colors.success, borderColor: colors.card }]}>
            <Icon name="check" size={14} color={colors.textOnAccent} weight="bold" />
          </View>
        </View>
        <View style={styles.photoActions}>
          <Button3D title="ถ่ายใหม่" icon="camera" size="sm" variant="secondary" onPress={onTake} style={styles.flex} />
          {!!onClear && !required && <Button3D title="ไม่ใช้รูป" size="sm" variant="ghost" onPress={onClear} />}
        </View>
      </View>
    );
  }
  return (
    <Pressable
      onPress={() => onTake()}
      accessibilityRole="button"
      accessibilityLabel={label}
      style={({ pressed }) => [
        styles.photoEmpty,
        {
          borderColor: required ? colors.gold : colors.border,
          backgroundColor: colors.inset,
          opacity: pressed ? 0.8 : 1,
        },
      ]}
    >
      <IconTile icon="camera" tone={required ? 'gold' : 'navy'} size={52} />
      <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{label}</Text>
      <Pill label={required ? 'จำเป็น' : 'ไม่บังคับ'} tone={required ? 'gold' : 'neutral'} />
    </Pressable>
  );
};

/** หัวการ์ดรายละเอียด: ช่องไอคอน + ชื่อการ์ด */
const CardHead: React.FC<{ icon: IconName; title: string; tone?: 'navy' | 'gold' }> = ({ icon, title, tone = 'navy' }) => {
  const { colors } = useTheme();
  return (
    <View style={styles.cardHead}>
      <IconTile icon={icon} tone={tone} size={36} />
      <Text style={[typography.h3, { color: colors.textStrong }]}>{title}</Text>
    </View>
  );
};

// =====================================================
// หน้าจอ
// =====================================================

export default function RiderJobDetailScreen() {
  const { colors, gradients } = useTheme();
  const insets = useSafeAreaInsets();
  const params = useLocalSearchParams<{ id?: string; accepted?: string; moved?: string }>();
  const paramId = useMemo(() => {
    const n = Number(params.id);
    return Number.isInteger(n) && n > 0 ? n : null;
  }, [params.id]);

  const [jobId, setJobId] = useState<number | null>(paramId);
  const [job, setJob] = useState<RiderJobDetail | null>(null);
  const [noJob, setNoJob] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<{ code: string; message: string } | null>(null);
  const [trackingMode, setTrackingMode] = useState<TrackingMode | 'none' | null>(null);
  const [gpsOff, setGpsOff] = useState(false);
  /** ร้านเคลื่อนที่ย้ายจุดรับของก่อนไรเดอร์ไปรับ (push rider_job_update event pickup_moved) */
  const [pickupMoved, setPickupMoved] = useState(params.moved === '1');

  const [sheet, setSheet] = useState<SheetKind | null>(null);
  const [sheetBusy, setSheetBusy] = useState(false);
  const [photoUri, setPhotoUri] = useState<string | null>(null);
  const [codConfirmed, setCodConfirmed] = useState(false);
  const [note, setNote] = useState('');
  const [failReason, setFailReason] = useState<RiderFailReason | null>(null);
  const [releaseReason, setReleaseReason] = useState<string | null>(null);
  const [celebration, setCelebration] = useState<{
    amount: number;
    walletBalance: number | null;
    settled: boolean;
    message: string;
    jobNumber: string;
  } | null>(null);

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);
  const actionBusyRef = useRef(false);
  const jobIdRef = useRef<number | null>(paramId);
  jobIdRef.current = jobId;
  const bgOfferedRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  useEffect(() => {
    setJobId(paramId);
  }, [paramId]);

  /** ตัวติดตามตำแหน่งต้องตรงกับสถานะงาน (ไม่ผูกกับการเปิด/ปิดหน้านี้) */
  const syncTracking = useCallback(async (target: RiderJobDetail) => {
    if (!target.is_mine) return;
    if (isActiveJobStatus(target.status)) {
      const mode = await startJobTracking(target.id);
      if (mountedRef.current) setTrackingMode(mode);
    } else if (isFinishedJobStatus(target.status)) {
      await stopJobTrackingFor(target.id);
      if (mountedRef.current) setTrackingMode('none');
    }
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'poll') => {
      const requestId = ++requestIdRef.current;
      if (mode === 'initial') setInitialLoading(true);
      if (mode === 'refresh') setRefreshing(true);

      if (jobId) {
        const result = await getRiderJob(jobId);
        if (!mountedRef.current || requestId !== requestIdRef.current) return;
        if (result.success && result.data?.job) {
          setJob(result.data.job);
          setNoJob(false);
          setError(null);
          syncTracking(result.data.job).catch(() => {});
        } else if (!result.success) {
          // งานเปิดรับที่มีคนอื่นรับไปแล้ว → server ตอบ NOT_YOUR_JOB/JOB_NOT_FOUND
          if (mode !== 'poll' || result.code === 'NOT_YOUR_JOB' || result.code === 'JOB_NOT_FOUND') {
            setError({ code: result.code, message: result.message });
          }
        }
      } else {
        const result = await getCurrentJob();
        if (!mountedRef.current || requestId !== requestIdRef.current) return;
        if (result.success) {
          const current = result.data?.has_job ? result.data.job : null;
          setJob(current);
          setNoJob(!current);
          setError(null);
          if (current) {
            setJobId(current.id);
            syncTracking(current).catch(() => {});
          }
        } else if (mode !== 'poll') {
          setError({ code: result.code, message: result.message });
        }
      }

      if (mountedRef.current && requestId === requestIdRef.current) {
        setInitialLoading(false);
        setRefreshing(false);
      }
    },
    [jobId, syncTracking]
  );

  const flow = useRiderPermissionFlow();

  const { accept, acceptingId } = useAcceptJob({
    flow,
    onGone: () => load('refresh'),
    onAccepted: (accepted, tracking) => {
      if (!mountedRef.current) return;
      setJob(accepted);
      setTrackingMode(tracking);
      if (accepted?.id) setJobId(accepted.id);
    },
  });

  // เปิดหน้านี้: โหลด + poll + ฟังแจ้งเตือนของงานนี้
  useFocusEffect(
    useCallback(() => {
      load(job ? 'poll' : 'initial');
      isLocationServiceEnabled().then((on) => mountedRef.current && setGpsOff(!on)).catch(() => {});
      getJobTrackingState()
        .then((s) => mountedRef.current && setTrackingMode((prev) => prev ?? (s ? s.mode : 'none')))
        .catch(() => {});

      const timer = setInterval(() => {
        load('poll');
        isLocationServiceEnabled().then((on) => mountedRef.current && setGpsOff(!on)).catch(() => {});
      }, POLL_MS);
      const sub = addNotificationReceivedListener((notification) => {
        const data = notification?.request?.content?.data as Record<string, unknown> | undefined;
        if (data?.type === 'rider_job_update' || data?.type === 'rider_account') {
          // ร้านย้ายจุดรับของ (งานนี้) → ดึงพิกัดใหม่ + แจ้งให้เห็นชัดๆ (ค่าส่งไม่เปลี่ยน)
          const pushedJobId = Number(data?.job_id);
          if (
            data?.type === 'rider_job_update' &&
            data?.event === 'pickup_moved' &&
            (!Number.isInteger(pushedJobId) || pushedJobId <= 0 || pushedJobId === jobIdRef.current)
          ) {
            setPickupMoved(true);
            resultHaptic('warning');
          }
          load('poll');
        }
      });
      return () => {
        clearInterval(timer);
        sub.remove();
      };
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [load])
  );

  // เพิ่งรับงานแต่ติดตามได้แค่ตอนเปิดแอป → เสนอ "ติดตามต่อแม้ปิดหน้าจอ" (ครั้งเดียว ข้ามได้)
  const offerBackground = flow.offerBackground;
  const activeJobId = job && job.is_mine && isActiveJobStatus(job.status) ? job.id : null;
  useEffect(() => {
    if (!activeJobId || trackingMode !== 'foreground' || bgOfferedRef.current) return undefined;
    const timer = setTimeout(async () => {
      bgOfferedRef.current = true;
      const granted = await offerBackground();
      if (granted && mountedRef.current) {
        const mode = await startJobTracking(activeJobId);
        if (mountedRef.current) setTrackingMode(mode);
      }
    }, 800);
    return () => clearTimeout(timer);
  }, [activeJobId, trackingMode, offerBackground]);

  // =====================================================
  // การกระทำ
  // =====================================================

  const resetSheet = () => {
    setPhotoUri(null);
    setCodConfirmed(false);
    setNote('');
    setFailReason(null);
    setReleaseReason(null);
  };

  const openSheet = (kind: SheetKind) => {
    resetSheet();
    setSheet(kind);
  };

  const closeSheet = () => {
    if (sheetBusy) return;
    setSheet(null);
  };

  /** ทำต่อหลัง sheet ปิดสนิท (iOS แสดง Alert/Modal ใหม่ระหว่าง Modal เดิมกำลังปิดไม่ได้) */
  const afterSheetClosed = (fn: () => void) => {
    setTimeout(() => {
      if (mountedRef.current) fn();
    }, SHEET_SETTLE_MS);
  };

  /** error ของขั้นตอนงาน → ข้อความไทย + รีเฟรชเมื่อสถานะเปลี่ยนไปแล้ว */
  const handleActionError = (result: { code: string; message: string }, title: string) => {
    resultHaptic('error');
    if (['INVALID_TRANSITION', 'JOB_NOT_FOUND', 'NOT_YOUR_JOB'].includes(result.code)) {
      const hadSheet = sheet !== null;
      setSheet(null);
      const show = () => Alert.alert('สถานะงานเปลี่ยนไปแล้ว', result.message);
      if (hadSheet) afterSheetClosed(show);
      else show();
      load('refresh');
      return;
    }
    Alert.alert(title, result.message);
  };

  const runStatus = async (status: 'picking_up' | 'picked_up' | 'delivering', photo?: string) => {
    if (!job || actionBusyRef.current) return;
    actionBusyRef.current = true;
    try {
      const result = await updateRiderJobStatus(job.id, status, photo);
      if (!mountedRef.current) return;
      if (result.success) {
        resultHaptic('success');
        setSheet(null);
        if (result.data?.job) {
          setJob(result.data.job);
          syncTracking(result.data.job).catch(() => {});
        } else {
          load('refresh');
        }
      } else {
        handleActionError(result, 'อัปเดตสถานะไม่สำเร็จ');
      }
    } finally {
      actionBusyRef.current = false;
    }
  };

  const submitPickup = async () => {
    setSheetBusy(true);
    try {
      await runStatus('picked_up', photoUri || undefined);
    } finally {
      if (mountedRef.current) setSheetBusy(false);
    }
  };

  const submitDeliver = async () => {
    if (!job || actionBusyRef.current) return;
    if (!photoUri) {
      Alert.alert('ถ่ายรูปก่อนนะ', 'ถ่ายรูปตอนส่งของให้ลูกค้าเป็นหลักฐาน');
      return;
    }
    if (job.cod_amount > 0 && !codConfirmed) {
      Alert.alert('ยืนยันเก็บเงินก่อนนะ', `ติ๊กยืนยันว่าเก็บเงินสด ${formatBaht(job.cod_amount)} จากลูกค้าแล้ว`);
      return;
    }
    actionBusyRef.current = true;
    setSheetBusy(true);
    try {
      const coords = await getCurrentCoords({ accuracy: 'high', timeoutMs: 6000 });
      const result = await deliverRiderJob(job.id, {
        photoUri,
        latitude: coords?.latitude,
        longitude: coords?.longitude,
        codCollected: job.cod_amount > 0 ? true : undefined,
        note: note.trim() || undefined,
      });
      if (!mountedRef.current) return;
      if (result.success) {
        setSheet(null);
        const done = result.data?.job;
        if (done) setJob(done);
        await stopJobTrackingFor(job.id);
        if (!mountedRef.current) return;
        setTrackingMode('none');
        const earned = result.data?.earnings;
        const payload = {
          amount: num(earned?.rider_earnings, num(job.rider_earnings)),
          walletBalance: earned && typeof earned.wallet_balance === 'number' ? earned.wallet_balance : null,
          settled: earned?.settled !== false,
          message: result.message,
          jobNumber: job.job_number,
        };
        afterSheetClosed(() => setCelebration(payload));
      } else {
        handleActionError(result, 'ส่งงานไม่สำเร็จ');
      }
    } finally {
      actionBusyRef.current = false;
      if (mountedRef.current) setSheetBusy(false);
    }
  };

  const submitFail = async () => {
    if (!job || actionBusyRef.current) return;
    if (!failReason) {
      Alert.alert('เลือกเหตุผลก่อนนะ', 'บอกทีมงานหน่อยว่าทำไมส่งไม่สำเร็จ');
      return;
    }
    if (failReason === 'other' && note.trim().length < 3) {
      Alert.alert('เล่าเพิ่มอีกนิด', 'เมื่อเลือก "อื่นๆ" ต้องระบุรายละเอียดด้วยนะ');
      return;
    }
    actionBusyRef.current = true;
    setSheetBusy(true);
    try {
      const result = await failRiderJob(job.id, {
        reasonCode: failReason,
        note: note.trim() || undefined,
        photoUri: photoUri || undefined,
      });
      if (!mountedRef.current) return;
      if (result.success) {
        resultHaptic('warning');
        setSheet(null);
        if (result.data?.job) setJob(result.data.job);
        await stopJobTrackingFor(job.id);
        if (mountedRef.current) setTrackingMode('none');
        const message = result.message || 'ทีมงานจะติดต่อเพื่อจัดการคืนสินค้า';
        afterSheetClosed(() => Alert.alert('บันทึกแล้ว', message));
      } else {
        handleActionError(result, 'บันทึกไม่สำเร็จ');
      }
    } finally {
      actionBusyRef.current = false;
      if (mountedRef.current) setSheetBusy(false);
    }
  };

  const submitRelease = async () => {
    if (!job || actionBusyRef.current) return;
    actionBusyRef.current = true;
    setSheetBusy(true);
    try {
      const reason = releaseReason === 'อื่นๆ' ? note.trim() : releaseReason || note.trim();
      const result = await releaseRiderJob(job.id, reason || undefined);
      if (!mountedRef.current) return;
      if (result.success) {
        resultHaptic('success');
        setSheet(null);
        await stopJobTrackingFor(job.id);
        const message = result.message || 'ระบบจะหาไรเดอร์คนอื่นให้';
        afterSheetClosed(() => {
          router.replace('/rider-jobs' as never);
          Alert.alert('คืนงานแล้ว', message);
        });
      } else {
        handleActionError(result, 'คืนงานไม่สำเร็จ');
      }
    } finally {
      actionBusyRef.current = false;
      if (mountedRef.current) setSheetBusy(false);
    }
  };

  const shootingRef = useRef(false);
  const shootPhoto = async () => {
    if (shootingRef.current || sheetBusy) return;
    shootingRef.current = true;
    try {
      const uri = await takePhoto();
      if (uri && mountedRef.current) setPhotoUri(uri);
    } finally {
      shootingRef.current = false;
    }
  };

  const onAction = (action: RiderJobAction): (() => unknown) => {
    switch (action) {
      case 'accept':
        return () => (job ? accept(job.id) : undefined);
      case 'picking_up':
        return () => runStatus('picking_up');
      case 'delivering':
        return () => runStatus('delivering');
      case 'picked_up':
        return () => openSheet('pickup');
      case 'deliver':
        return () => openSheet('deliver');
      case 'fail':
        return () => openSheet('fail');
      case 'release':
        return () => openSheet('release');
      default:
        return () => undefined;
    }
  };

  const enableLocation = async () => {
    if (!job) return;
    if (await flow.ensureForeground()) {
      const mode = await startJobTracking(job.id);
      if (mountedRef.current) setTrackingMode(mode);
    }
  };

  const upgradeToBackground = async () => {
    if (!job) return;
    if (await flow.offerBackground({ force: true })) {
      const mode = await startJobTracking(job.id);
      if (mountedRef.current) setTrackingMode(mode);
    }
  };

  // =====================================================
  // สถานะพิเศษ
  // =====================================================

  const screenTitle = job ? `งาน ${job.job_number}` : 'งานของฉัน';

  if (initialLoading && !job) {
    return (
      <Screen title={screenTitle}>
        <EmptyState icon="hourglass" title="กำลังโหลดงาน..." message="รอสักครู่นะ" />
      </Screen>
    );
  }

  if (!job) {
    if (noJob) {
      return (
        <Screen title="งานของฉัน" onRefresh={() => load('refresh')} refreshing={refreshing}>
          <EmptyState
            art="scooter"
            title="ยังไม่มีงานที่กำลังส่ง"
            message="ไปดูงานใกล้คุณ แล้วกดรับงานแรกกันเลย"
            actionLabel="ดูงานใกล้ฉัน"
            onAction={() => router.replace('/rider-jobs' as never)}
          />
        </Screen>
      );
    }
    const gone = error?.code === 'NOT_YOUR_JOB' || error?.code === 'JOB_NOT_FOUND';
    return (
      <Screen title={screenTitle} onRefresh={() => load('refresh')} refreshing={refreshing}>
        {gone ? (
          <EmptyState
            icon="prohibit"
            title="งานนี้ไม่ว่างแล้ว"
            message="มีไรเดอร์คนอื่นรับไปแล้ว หรืองานถูกยกเลิก ลองงานอื่นนะ"
            actionLabel="ดูงานอื่น"
            onAction={() => router.replace('/rider-jobs' as never)}
          />
        ) : (
          <EmptyState variant="error" message={error?.message} onAction={() => load('initial')} />
        )}
      </Screen>
    );
  }

  // =====================================================
  // เนื้อหา
  // =====================================================

  const isMine = job.is_mine;
  const active = isMine && isActiveJobStatus(job.status);
  const finished = isFinishedJobStatus(job.status);
  const goneWhileViewing = error?.code === 'NOT_YOUR_JOB' || error?.code === 'JOB_NOT_FOUND';
  const allowed = new Set<RiderJobAction>(job.allowed_actions || []);
  const flowActions = FLOW_ORDER.filter((a) => allowed.has(a));
  const primary = flowActions[0];
  const secondary = flowActions[1];
  const hasSideActions = allowed.has('fail') || allowed.has('release');
  const showActionBar = !goneWhileViewing && (flowActions.length > 0 || hasSideActions || (isMine && finished));
  const tripKm = formatKm(job.distance_km);
  const toPickup = formatKm(job.distance_to_pickup_km);
  const eta = formatMinutes(job.estimated_duration_minutes);
  const live = active ? job.customer_live_location : null;

  // ---------- หน้าตาเท่านั้น (ไม่กระทบขั้นตอนงาน) ----------
  /** จุดหมายตอนนี้: ยังไม่ได้ของ = จุดรับ · ได้ของแล้ว = จุดส่ง */
  const destination: 'pickup' | 'dropoff' | null = active
    ? job.status === 'accepted' || job.status === 'picking_up'
      ? 'pickup'
      : 'dropoff'
    : null;
  /** ตัวเลขใต้ค่าส่งบนการ์ดฮีโร่ */
  const heroStats = [
    tripKm ? { key: 'trip', value: tripKm, label: 'ระยะส่ง' } : null,
    toPickup && !finished ? { key: 'pickup', value: toPickup, label: 'ห่างจุดรับ' } : null,
    eta && !finished ? { key: 'eta', value: eta, label: 'เวลาโดยประมาณ' } : null,
  ].filter((s): s is { key: string; value: string; label: string } => !!s);
  const proofPhotos = [
    { key: 'pickup', label: 'ตอนรับของ', uri: job.photos?.pickup },
    { key: 'delivery', label: 'ตอนส่งของ', uri: job.photos?.delivery },
    { key: 'failure', label: 'ส่งไม่สำเร็จ', uri: job.photos?.failure },
  ].filter((p) => !!p.uri);

  return (
    <Screen
      title={screenTitle}
      subtitle={job.job_type_text}
      scroll={false}
      right={<Pill label={job.status_text || JOB_STATUS_LABEL[job.status]} tone={JOB_STATUS_TONE[job.status] || 'neutral'} />}
    >
      <ScrollView
        style={styles.flex}
        contentContainerStyle={[styles.content, { paddingBottom: showActionBar ? 220 + insets.bottom : spacing.xxxl }]}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load('refresh')}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
          />
        }
      >
        {/* ---------- ป้ายสถานะพิเศษ ---------- */}
        {params.accepted === '1' && job.status === 'accepted' && isMine && (
          <NoticeCard
            icon="check-circle"
            tone="success"
            title="รับงานสำเร็จ!"
            message="กดนำทางไปจุดรับของได้เลย ลูกค้ากำลังรออยู่"
            gradientBorder
            style={styles.block}
          />
        )}
        {goneWhileViewing && (
          <NoticeCard icon="prohibit" tone="neutral" title="งานนี้ไม่ว่างแล้ว" message={error?.message} style={styles.block} />
        )}
        {job.status === 'cancelled' && (
          <NoticeCard
            icon="x-circle"
            tone="danger"
            title="งานนี้ถูกยกเลิกแล้ว"
            titleColor={colors.danger}
            message={job.cancellation?.reason || 'ไม่ต้องไปรับหรือส่งของแล้ว ระบบหยุดแชร์ตำแหน่งให้เรียบร้อย'}
            style={styles.block}
          />
        )}
        {job.status === 'failed' && (
          <NoticeCard
            icon="warning-circle"
            tone="danger"
            title="ส่งไม่สำเร็จ"
            titleColor={colors.danger}
            message={
              <>
                <Text style={[typography.bodySm, { color: colors.text }]}>
                  {job.failure?.reason_text || 'บันทึกแล้ว'}
                  {job.failure?.note ? ` — ${job.failure.note}` : ''}
                </Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>ทีมงานจะติดต่อเพื่อจัดการคืนสินค้า</Text>
              </>
            }
            style={styles.block}
          />
        )}
        {active && gpsOff && (
          <NoticeCard
            icon="warning"
            tone="danger"
            title="GPS ของเครื่องปิดอยู่"
            titleColor={colors.danger}
            message="ลูกค้าจะไม่เห็นตำแหน่งของคุณ เปิด GPS ในแถบด่วนของเครื่องได้เลย"
            style={styles.block}
          />
        )}

        {/* ---------- ฮีโร่: ค่าส่ง + ความคืบหน้า ---------- */}
        <NavyCard goldBorder padding={0} style={styles.block}>
          <View style={styles.heroTop}>
            <View style={styles.heroHead}>
              <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>
                {job.status === 'completed' ? 'ค่าส่งที่คุณได้รับ' : 'ค่าส่งที่คุณจะได้รับ'}
              </Text>
              {job.status === 'completed' && (
                <Pill
                  label={job.earnings_settled ? 'เข้ากระเป๋าแล้ว' : 'กำลังโอนเข้ากระเป๋า'}
                  tone={job.earnings_settled ? 'success' : 'info'}
                  icon="wallet"
                />
              )}
            </View>
            <PriceText amount={job.rider_earnings} size="xl" style={[typography.moneyLg, { color: colors.goldLight }]} />
            {heroStats.length > 0 && (
              <View style={[styles.heroStats, { borderTopColor: colors.headerGlassBorder }]}>
                {heroStats.map((stat, index) => (
                  <View
                    key={stat.key}
                    style={[
                      styles.heroStat,
                      index > 0 && { borderLeftWidth: 1, borderLeftColor: colors.headerGlassBorder },
                    ]}
                  >
                    <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.onHeader }]}>
                      {stat.value}
                    </Text>
                    <Text numberOfLines={1} style={[typography.micro, { color: colors.onHeaderMuted }]}>
                      {stat.label}
                    </Text>
                  </View>
                ))}
              </View>
            )}
          </View>

          {job.status !== 'pending' && <StepProgress job={job} />}

          {job.is_cod && (
            <View style={[styles.codBar, { backgroundColor: colors.warningSoft }]}>
              <View style={styles.codLeft}>
                <Icon name="money" size={19} color={colors.warning} weight="fill" />
                <Text style={[typography.bodyStrong, { color: colors.text }]}>เก็บเงินสดจากลูกค้า</Text>
              </View>
              <PriceText amount={job.cod_amount} size="lg" tone="strong" />
            </View>
          )}
        </NavyCard>

        {/* ---------- ตำแหน่งสดของลูกค้า (เฉพาะเมื่อแชร์มา) ---------- */}
        {!!live && (
          <NoticeCard
            icon="broadcast"
            tone="success"
            title="ลูกค้าแชร์ตำแหน่งสด"
            message={`อัปเดต ${formatAgo(live.updated_at)}`}
            gradientBorder={gradients.success}
            style={styles.block}
          >
            <Button3D
              title="นำทางไปหาลูกค้า"
              icon="navigation-arrow"
              variant="primary"
              size="sm"
              onPress={() => openNavigation({ latitude: live.latitude, longitude: live.longitude })}
              style={styles.alignStart}
            />
          </NoticeCard>
        )}

        {/* ---------- จุดรับ / จุดส่ง ---------- */}
        {pickupMoved && !finished && !['picked_up', 'delivering'].includes(job.status) && (
          <NoticeCard
            icon="map-pin"
            tone="warning"
            title="ร้านย้ายจุดรับของแล้ว"
            message="ร้านรถเข็นขยับไปจุดใหม่ ตำแหน่งจุดรับด้านล่างอัปเดตแล้ว กดนำทางอีกครั้งได้เลย (ค่าส่งเท่าเดิม)"
            onClose={() => setPickupMoved(false)}
            closeLabel="ปิดข้อความร้านย้ายจุดรับของ"
            style={styles.block}
          />
        )}
        <Card3D style={styles.block} padding={spacing.lg}>
          <StopBlock
            kind="pickup"
            point={job.pickup}
            canContact={isMine && !finished}
            current={destination === 'pickup'}
          />
          <StopBlock
            kind="dropoff"
            point={job.dropoff}
            canContact={isMine && !finished}
            current={destination === 'dropoff'}
            isLast
          />
        </Card3D>

        {/* ---------- รายการของ / ค่าส่ง ---------- */}
        {(!!job.items_summary || !!job.description) && (
          <Card3D style={styles.block} padding={spacing.lg}>
            <CardHead icon="receipt" title="รายการ" />
            <Text style={[typography.body, { color: colors.text }]}>{job.items_summary || job.description}</Text>
          </Card3D>
        )}

        <Card3D variant="inset" style={styles.block} padding={spacing.lg}>
          <CardHead icon="coins" title="ค่าส่งงานนี้" tone="gold" />
          <View style={styles.feeRow}>
            <Text style={[typography.bodySm, { color: colors.textMuted }]}>ค่าส่งรวม</Text>
            <PriceText amount={job.total_fee} size="sm" />
          </View>
          {job.platform_fee > 0 && (
            <View style={styles.feeRow}>
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>ค่าบริการระบบ</Text>
              <PriceText amount={-job.platform_fee} size="sm" tone="muted" />
            </View>
          )}
          <View style={[styles.feeRow, styles.feeTotal, { borderTopColor: colors.divider }]}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>คุณได้รับ</Text>
            <PriceText amount={job.rider_earnings} size="lg" tone="gold" />
          </View>
        </Card3D>

        {/* ---------- รูปหลักฐาน ---------- */}
        {proofPhotos.length > 0 && (
          <Card3D style={styles.block} padding={spacing.lg}>
            <CardHead icon="camera" title="รูปหลักฐาน" />
            <View style={styles.photoRow}>
              {proofPhotos.map((p) => (
                <View key={p.key} style={styles.proofItem}>
                  <Image
                    source={{ uri: p.uri as string }}
                    style={[styles.proof, { backgroundColor: colors.inset, borderColor: colors.border }]}
                    contentFit="cover"
                    accessibilityLabel={`รูป${p.label}`}
                  />
                  <Text style={[typography.caption, { color: colors.textMuted }]}>{p.label}</Text>
                </View>
              ))}
            </View>
          </Card3D>
        )}

        {/* ---------- สถานะการแชร์ตำแหน่ง ---------- */}
        {active && trackingMode !== null && (
          <Card3D variant="inset" style={styles.block} padding={spacing.md}>
            {trackingMode === 'background' ? (
              <View style={styles.trackRow}>
                <IconTile icon="broadcast" tone="success" size={36} />
                <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
                  กำลังแชร์ตำแหน่งให้ลูกค้า (ทำงานแม้ปิดหน้าจอ) · หยุดเองเมื่อจบงาน
                </Text>
              </View>
            ) : trackingMode === 'foreground' ? (
              <>
                <View style={styles.trackRow}>
                  <IconTile icon="broadcast" tone="warning" size={36} />
                  <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
                    แชร์ตำแหน่งเฉพาะตอนเปิดแอปไว้ — ถ้าปิดหน้าจอ ลูกค้าจะเห็นตำแหน่งล่าสุดเท่านั้น
                  </Text>
                </View>
                <Button3D
                  title="ให้ติดตามต่อแม้ปิดหน้าจอ"
                  icon="broadcast"
                  size="sm"
                  variant="secondary"
                  onPress={upgradeToBackground}
                  style={styles.gapTop}
                />
              </>
            ) : trackingMode === 'none' ? (
              <>
                <View style={styles.trackRow}>
                  <IconTile icon="warning" tone="danger" size={36} />
                  <Text style={[typography.bodySm, styles.flex, { color: colors.danger }]}>
                    ยังไม่ได้แชร์ตำแหน่ง ลูกค้าจะไม่เห็นว่าคุณอยู่ไหน
                  </Text>
                </View>
                <Button3D title="อนุญาตตำแหน่ง" icon="map-pin" size="sm" onPress={enableLocation} style={styles.gapTop} />
              </>
            ) : null}
          </Card3D>
        )}

        {!!job.created_at && (
          <Text style={[typography.caption, styles.meta, { color: colors.textFaint }]}>
            สร้างงาน {formatThaiDateTime(job.created_at)} · {job.job_number}
          </Text>
        )}
      </ScrollView>

      {/* ---------- แถบปุ่มด้านล่าง ---------- */}
      {showActionBar && (
        <View
          style={[
            styles.actionBar,
            { backgroundColor: colors.card, paddingBottom: Math.max(insets.bottom, spacing.md), borderTopColor: colors.divider },
            shadowStyle('lg', colors.shadowDark),
          ]}
        >
          {!!STEP_HINT[job.status] && flowActions.length > 0 && (
            <View style={styles.hintRow}>
              <Icon name="info" size={15} color={colors.goldDeep} weight="fill" />
              <Text style={[typography.caption, styles.hintText, { color: colors.textMuted }]}>{STEP_HINT[job.status]}</Text>
            </View>
          )}
          {!!primary && (
            <Button3D
              title={ACTION_LOOK[primary].title}
              icon={ACTION_LOOK[primary].icon}
              variant={ACTION_LOOK[primary].variant === 'ghost' ? 'primary' : ACTION_LOOK[primary].variant}
              size="lg"
              fullWidth
              loading={primary === 'accept' && acceptingId === job.id}
              loadingText={primary === 'accept' ? 'กำลังรับงาน...' : undefined}
              onPress={onAction(primary)}
            />
          )}
          {!!secondary && (
            <Button3D
              title={ACTION_LOOK[secondary].title}
              icon={ACTION_LOOK[secondary].icon}
              variant="secondary"
              fullWidth
              onPress={onAction(secondary)}
              style={styles.gapTop}
            />
          )}
          {hasSideActions && (
            <View style={styles.sideRow}>
              {allowed.has('release') && (
                <TextAction icon="arrow-counter-clockwise" title="คืนงาน" onPress={onAction('release')} />
              )}
              {allowed.has('release') && allowed.has('fail') && (
                <View style={[styles.sideDivider, { backgroundColor: colors.divider }]} />
              )}
              {allowed.has('fail') && (
                <TextAction icon="warning" title="ส่งไม่สำเร็จ" onPress={onAction('fail')} color={colors.danger} />
              )}
            </View>
          )}
          {isMine && finished && flowActions.length === 0 && (
            <Button3D
              title="หางานต่อ"
              icon="moped"
              size="lg"
              fullWidth
              onPress={() => router.replace('/rider-jobs' as never)}
            />
          )}
          {!isMine && job.status === 'pending' && flowActions.length === 0 && (
            <Text style={[typography.caption, styles.hint, { color: colors.textMuted }]}>
              ตอนนี้ยังรับงานนี้ไม่ได้ — กดเริ่มรับงานที่หน้าไรเดอร์ก่อนนะ
            </Text>
          )}
        </View>
      )}

      {/* ---------- sheet: รับของแล้ว ---------- */}
      <RiderSheet
        visible={sheet === 'pickup'}
        icon="package"
        title="ได้ของครบแล้วใช่ไหม?"
        subtitle="ถ่ายรูปของที่รับมาได้ (ไม่บังคับ) ช่วยยืนยันถ้ามีปัญหาภายหลัง"
        busy={sheetBusy}
        onClose={closeSheet}
        footer={
          <>
            <Button3D title="ยืนยันรับของแล้ว" icon="check-circle" size="lg" fullWidth onPress={submitPickup} />
            <Button3D title="ยังก่อน" variant="ghost" size="sm" onPress={closeSheet} disabled={sheetBusy} />
          </>
        }
      >
        <PhotoSlot uri={photoUri} label="ถ่ายรูปของที่รับ" onTake={shootPhoto} onClear={() => setPhotoUri(null)} />
      </RiderSheet>

      {/* ---------- sheet: ส่งสำเร็จ ---------- */}
      <RiderSheet
        visible={sheet === 'deliver'}
        icon="check-circle"
        tone="success"
        title="ส่งของสำเร็จ"
        subtitle="ถ่ายรูปตอนส่งของให้ลูกค้าเป็นหลักฐาน"
        busy={sheetBusy}
        onClose={closeSheet}
        footer={
          <>
            <Button3D
              title="ยืนยันส่งสำเร็จ"
              icon="seal-check"
              variant="primary"
              size="lg"
              fullWidth
              disabled={!photoUri || (job.cod_amount > 0 && !codConfirmed)}
              loadingText="กำลังบันทึก..."
              onPress={submitDeliver}
            />
            <Button3D title="ยังก่อน" variant="ghost" size="sm" onPress={closeSheet} disabled={sheetBusy} />
          </>
        }
      >
        <PhotoSlot uri={photoUri} label="ถ่ายรูปส่งของ" required onTake={shootPhoto} />
        {job.cod_amount > 0 && (
          <Card3D
            onPress={() => setCodConfirmed((v) => !v)}
            variant={codConfirmed ? 'raised' : 'inset'}
            gradientBorder={codConfirmed ? gradients.success : false}
            padding={spacing.md}
            accessibilityLabel={`เก็บเงินสด ${formatBaht(job.cod_amount)} แล้ว`}
            accessibilityRole="checkbox"
            accessibilityState={{ checked: codConfirmed }}
          >
            <View style={styles.checkRow}>
              <View
                style={[
                  styles.checkbox,
                  {
                    borderColor: codConfirmed ? colors.success : colors.textFaint,
                    backgroundColor: codConfirmed ? colors.success : 'transparent',
                  },
                ]}
              >
                {codConfirmed && <Icon name="check" size={17} color={colors.textOnAccent} weight="bold" />}
              </View>
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
                  เก็บเงินสด {formatBaht(job.cod_amount)} แล้ว
                </Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  ระบบจะหักยอดนำส่งจากกระเป๋าของคุณ พร้อมบันทึกรายได้ให้ทันที
                </Text>
              </View>
              <Icon name="money" size={22} color={codConfirmed ? colors.success : colors.warning} weight="fill" />
            </View>
          </Card3D>
        )}
        <FocusInput
          value={note}
          onChangeText={setNote}
          placeholder="หมายเหตุ (ไม่บังคับ) เช่น ฝากไว้กับ รปภ."
          maxLength={500}
          multiline
          accessibilityLabel="หมายเหตุการส่ง"
        />
      </RiderSheet>

      {/* ---------- sheet: ส่งไม่สำเร็จ ---------- */}
      <RiderSheet
        visible={sheet === 'fail'}
        icon="warning"
        tone="danger"
        title="ส่งไม่สำเร็จ"
        subtitle="เลือกเหตุผล ทีมงานจะติดต่อเพื่อจัดการต่อ"
        busy={sheetBusy}
        onClose={closeSheet}
        footer={
          <>
            <Button3D
              title="ยืนยันส่งไม่สำเร็จ"
              variant="danger"
              size="lg"
              fullWidth
              disabled={!failReason || (failReason === 'other' && note.trim().length < 3)}
              onPress={submitFail}
            />
            <Button3D title="กลับไปส่งต่อ" variant="ghost" size="sm" onPress={closeSheet} disabled={sheetBusy} />
          </>
        }
      >
        <View style={styles.chips}>
          {FAIL_REASONS.map((r) => (
            <Chip
              key={r.code}
              label={r.label}
              icon={r.icon}
              selected={failReason === r.code}
              tone="danger"
              onPress={() => setFailReason(r.code)}
            />
          ))}
        </View>
        <FocusInput
          value={note}
          onChangeText={setNote}
          placeholder={failReason === 'other' ? 'เล่ารายละเอียด (จำเป็น)' : 'รายละเอียดเพิ่มเติม (ไม่บังคับ)'}
          maxLength={1000}
          multiline
          accessibilityLabel="รายละเอียดที่ส่งไม่สำเร็จ"
        />
        <PhotoSlot uri={photoUri} label="ถ่ายรูปประกอบ" onTake={shootPhoto} onClear={() => setPhotoUri(null)} />
      </RiderSheet>

      {/* ---------- sheet: คืนงาน ---------- */}
      <RiderSheet
        visible={sheet === 'release'}
        icon="arrow-counter-clockwise"
        tone="warning"
        title="คืนงานนี้?"
        subtitle="ระบบจะหาไรเดอร์คนอื่นให้ คืนงานบ่อยอาจมีผลกับการรับงานครั้งถัดไป"
        busy={sheetBusy}
        onClose={closeSheet}
        footer={
          <>
            <Button3D
              title="ยืนยันคืนงาน"
              variant="danger"
              size="lg"
              fullWidth
              disabled={releaseReason === 'อื่นๆ' && note.trim().length < 3}
              onPress={submitRelease}
            />
            <Button3D title="ไม่คืนแล้ว" variant="ghost" size="sm" onPress={closeSheet} disabled={sheetBusy} />
          </>
        }
      >
        <View style={styles.chips}>
          {[...RELEASE_REASONS, 'อื่นๆ'].map((r) => (
            <Chip key={r} label={r} selected={releaseReason === r} onPress={() => setReleaseReason(r)} />
          ))}
        </View>
        {releaseReason === 'อื่นๆ' && (
          <FocusInput
            value={note}
            onChangeText={setNote}
            placeholder="บอกเหตุผลสั้นๆ"
            maxLength={255}
            accessibilityLabel="เหตุผลที่คืนงาน"
          />
        )}
      </RiderSheet>

      <JobCompleteCelebration
        visible={!!celebration}
        amount={celebration?.amount ?? 0}
        walletBalance={celebration?.walletBalance}
        settled={celebration?.settled}
        message={celebration?.message}
        jobNumber={celebration?.jobNumber}
        onNextJob={() => {
          setCelebration(null);
          router.replace('/rider-jobs' as never);
        }}
        onViewEarnings={() => {
          setCelebration(null);
          router.replace('/rider-earnings' as never);
        }}
        onClose={() => setCelebration(null)}
      />

      {flow.element}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  content: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
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
  // ---------- ฮีโร่ ----------
  heroTop: {
    paddingHorizontal: spacing.lg + 2,
    paddingTop: spacing.lg + 2,
    paddingBottom: spacing.lg,
  },
  heroHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  heroStats: {
    flexDirection: 'row',
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  heroStat: {
    flex: 1,
    paddingHorizontal: spacing.sm,
    gap: 1,
  },
  progress: {
    borderTopWidth: 1,
    paddingTop: spacing.md,
    paddingBottom: spacing.lg,
    paddingHorizontal: spacing.sm,
  },
  progressHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.sm + 2,
    marginBottom: spacing.md,
  },
  stepsRow: {
    flexDirection: 'row',
  },
  stepCol: {
    flex: 1,
    alignItems: 'center',
  },
  stepTrack: {
    alignSelf: 'stretch',
    height: STEP_CIRCLE,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepLines: {
    position: 'absolute',
    left: 0,
    right: 0,
    top: STEP_CIRCLE / 2 - 1,
    height: 2,
    flexDirection: 'row',
  },
  stepCircle: {
    width: STEP_CIRCLE,
    height: STEP_CIRCLE,
    borderRadius: STEP_CIRCLE / 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepCircleActive: {
    borderWidth: 2,
  },
  stepCircleIdle: {
    borderWidth: 1,
  },
  stepLabel: {
    textAlign: 'center',
    marginTop: 6,
    paddingHorizontal: 2,
  },
  stepTime: {
    textAlign: 'center',
    fontVariant: ['tabular-nums'],
  },
  codBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg + 2,
    paddingVertical: spacing.md,
  },
  codLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    flexShrink: 1,
  },
  // ---------- จุดรับ/ส่ง ----------
  stop: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  stopRail: {
    width: 25,
    alignItems: 'center',
    paddingTop: 2,
  },
  stopLine: {
    flex: 1,
    marginTop: spacing.xs,
  },
  stopBodyGap: {
    paddingBottom: spacing.lg,
  },
  stopHead: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
  },
  stopLabelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: 2,
  },
  stopActions: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  stopText: {
    marginTop: spacing.xs,
  },
  inlineRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 5,
    marginTop: spacing.sm,
  },
  noteBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    borderRadius: radii.md,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
  navButton: {
    marginTop: spacing.md,
  },
  // ---------- การ์ดรายละเอียด ----------
  cardHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginBottom: spacing.md,
  },
  feeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: spacing.xs,
  },
  feeTotal: {
    borderTopWidth: 1,
    marginTop: spacing.xs,
    paddingTop: spacing.sm,
  },
  photoRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  proofItem: {
    flex: 1,
    gap: spacing.xs,
    alignItems: 'center',
  },
  proof: {
    width: '100%',
    aspectRatio: 1,
    borderRadius: radii.lg,
    borderWidth: 1,
  },
  trackRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  meta: {
    textAlign: 'center',
    marginTop: spacing.sm,
  },
  // ---------- แถบปุ่มท้ายจอ ----------
  actionBar: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
  },
  hintRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    marginBottom: spacing.sm + 2,
    paddingHorizontal: spacing.sm,
  },
  hintText: {
    flexShrink: 1,
    textAlign: 'center',
  },
  hint: {
    textAlign: 'center',
    marginBottom: spacing.sm,
  },
  sideRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.md,
    marginTop: spacing.xs,
  },
  sideDivider: {
    width: 1,
    height: 18,
  },
  // ---------- sheet ----------
  photoWrap: {
    gap: spacing.sm,
  },
  photoFrame: {
    borderRadius: radii.lg,
    overflow: 'hidden',
  },
  photo: {
    width: '100%',
    height: 220,
  },
  photoBadge: {
    position: 'absolute',
    top: spacing.sm,
    right: spacing.sm,
    width: 26,
    height: 26,
    borderRadius: 13,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  photoActions: {
    flexDirection: 'row',
    gap: spacing.sm,
    alignItems: 'center',
  },
  photoEmpty: {
    height: 176,
    borderRadius: radii.lg,
    borderWidth: 1.5,
    borderStyle: 'dashed',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
  },
  checkRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  checkbox: {
    width: 28,
    height: 28,
    borderRadius: 9,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
});
