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
 * params: id (ไม่ส่ง = งานปัจจุบัน), accepted=1 (เพิ่งรับงาน)
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Alert, Pressable, RefreshControl, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme, spacing, radii, typography, shadowStyle } from '@/theme';
import {
  Button3D,
  Card3D,
  Chip,
  EmptyState,
  Pill,
  PriceText,
  Screen,
  formatBaht,
  resultHaptic,
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

const POLL_MS = 20_000;

/** รอ sheet ปิดสนิทก่อนแสดง Alert/หน้าฉลอง */
const SHEET_SETTLE_MS = 450;

type SheetKind = 'pickup' | 'deliver' | 'fail' | 'release';

const FLOW_ORDER: RiderJobAction[] = ['accept', 'picking_up', 'picked_up', 'delivering', 'deliver'];

const ACTION_LOOK: Record<RiderJobAction, { title: string; icon: string; variant: 'primary' | 'success' | 'danger' | 'ghost' }> = {
  accept: { title: 'รับงานนี้', icon: '⚡', variant: 'success' },
  picking_up: { title: 'กำลังไปรับของ', icon: '🛵', variant: 'primary' },
  picked_up: { title: 'รับของแล้ว', icon: '📦', variant: 'primary' },
  delivering: { title: 'เริ่มไปส่ง', icon: '🚀', variant: 'primary' },
  deliver: { title: 'ส่งสำเร็จ', icon: '✅', variant: 'success' },
  fail: { title: 'ส่งไม่สำเร็จ', icon: '⚠️', variant: 'ghost' },
  release: { title: 'คืนงาน', icon: '↩️', variant: 'ghost' },
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

const StepTimeline: React.FC<{ job: RiderJobDetail }> = ({ job }) => {
  const { colors, gradients } = useTheme();
  const current = stepIndexForStatus(job.status);
  const failedOrCancelled = job.status === 'failed' || job.status === 'cancelled';
  const times: Record<string, string | null | undefined> = {
    accepted: job.timeline?.accepted_at ?? job.accepted_at,
    picked_up: job.timeline?.picked_up_at,
    completed: job.timeline?.completed_at ?? job.timeline?.delivered_at ?? job.completed_at,
  };

  return (
    <Card3D style={styles.block} padding={spacing.lg}>
      <Text style={[typography.h3, styles.cardTitle, { color: colors.textStrong }]}>ความคืบหน้า</Text>
      {JOB_STEPS.map((step, index) => {
        const done = !failedOrCancelled && index < current;
        const active = !failedOrCancelled && index === current;
        const isLast = index === JOB_STEPS.length - 1;
        const complete = active && isLast;
        const time = times[step.key];
        return (
          <View key={step.key} style={styles.stepRow}>
            <View style={styles.stepRail}>
              {active || complete ? (
                <LinearGradient colors={complete ? gradients.success : gradients.primary} style={styles.stepDot}>
                  <Text style={styles.stepDotText}>{step.icon}</Text>
                </LinearGradient>
              ) : (
                <View
                  style={[
                    styles.stepDot,
                    {
                      backgroundColor: done ? colors.success : colors.inset,
                      borderColor: done ? colors.success : colors.border,
                      borderWidth: 1,
                    },
                  ]}
                >
                  <Text style={[styles.stepDotSmall, { color: done ? colors.textOnAccent : colors.textFaint }]}>
                    {done ? '✓' : String(index + 1)}
                  </Text>
                </View>
              )}
              {!isLast && (
                <View style={[styles.stepLine, { backgroundColor: done ? colors.success : colors.border }]} />
              )}
            </View>
            <View style={styles.stepBody}>
              <Text
                style={[
                  active ? typography.h3 : typography.body,
                  { color: active ? colors.textStrong : done ? colors.text : colors.textFaint },
                ]}
              >
                {step.label}
                {active && !isLast ? '  · ตอนนี้' : ''}
              </Text>
              {!!time && (done || active) && (
                <Text style={[typography.caption, { color: colors.textMuted }]}>{formatTime(time)}</Text>
              )}
            </View>
          </View>
        );
      })}
    </Card3D>
  );
};

const PointCard: React.FC<{
  kind: 'pickup' | 'dropoff';
  point: RiderJobPoint;
  canContact: boolean;
}> = ({ kind, point, canContact }) => {
  const { colors } = useTheme();
  const isPickup = kind === 'pickup';
  const title = isPickup ? 'จุดรับของ' : 'จุดส่งของ';
  const name = point?.name || (isPickup ? 'ร้านค้า' : point?.area || 'ลูกค้า');
  const address = point?.address || (!isPickup ? point?.area : null);

  return (
    <Card3D style={styles.block} padding={spacing.lg}>
      <View style={styles.pointHead}>
        <Text style={styles.pointIcon}>{isPickup ? '📦' : '🏠'}</Text>
        <View style={styles.flex}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>{title}</Text>
          <Text style={[typography.h3, { color: colors.textStrong }]} numberOfLines={2}>
            {name}
          </Text>
        </View>
      </View>
      {!!address && <Text style={[typography.body, styles.pointText, { color: colors.text }]}>{address}</Text>}
      {point?.is_approximate && (
        <Text style={[typography.caption, styles.pointText, { color: colors.textMuted }]}>
          📍 แสดงพื้นที่โดยประมาณ — ที่อยู่เต็มและเบอร์โทรจะแสดงหลังรับงาน
        </Text>
      )}
      {!!point?.notes && (
        <View style={[styles.noteBox, { backgroundColor: colors.warningSoft }]}>
          <Text style={[typography.bodySm, { color: colors.text }]}>📝 {point.notes}</Text>
        </View>
      )}
      <View style={styles.pointActions}>
        <Button3D
          title="นำทาง"
          icon="🧭"
          size="sm"
          onPress={() => openNavigation(point)}
          style={styles.flex}
          accessibilityLabel={`นำทางไป${title}`}
        />
        {canContact && !!point?.phone && (
          <Button3D
            title="โทร"
            icon="📞"
            size="sm"
            variant="secondary"
            onPress={() => callPhone(point.phone)}
            style={styles.flex}
            accessibilityLabel={`โทรหา${isPickup ? 'ร้าน' : 'ลูกค้า'}`}
          />
        )}
      </View>
    </Card3D>
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
        <Image source={{ uri }} style={[styles.photo, { backgroundColor: colors.inset }]} contentFit="cover" />
        <View style={styles.photoActions}>
          <Button3D title="ถ่ายใหม่" icon="📷" size="sm" variant="secondary" onPress={onTake} style={styles.flex} />
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
        { borderColor: required ? colors.gold : colors.border, backgroundColor: colors.inset, opacity: pressed ? 0.8 : 1 },
      ]}
    >
      <Text style={styles.photoEmptyIcon}>📷</Text>
      <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{label}</Text>
      <Text style={[typography.caption, { color: required ? colors.goldDeep : colors.textMuted }]}>
        {required ? 'จำเป็น' : 'ไม่บังคับ'}
      </Text>
    </Pressable>
  );
};

// =====================================================
// หน้าจอ
// =====================================================

export default function RiderJobDetailScreen() {
  const { colors, gradients } = useTheme();
  const insets = useSafeAreaInsets();
  const params = useLocalSearchParams<{ id?: string; accepted?: string }>();
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
        <EmptyState icon="⏳" title="กำลังโหลดงาน..." message="รอสักครู่นะ" />
      </Screen>
    );
  }

  if (!job) {
    if (noJob) {
      return (
        <Screen title="งานของฉัน" onRefresh={() => load('refresh')} refreshing={refreshing}>
          <EmptyState
            icon="🛵"
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
            icon="🏃"
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
          <Card3D gradientBorder style={styles.block} padding={spacing.md}>
            <Text style={[typography.h3, { color: colors.textStrong }]}>รับงานสำเร็จ! 🎉</Text>
            <Text style={[typography.bodySm, { color: colors.textMuted }]}>
              กดนำทางไปจุดรับของได้เลย ลูกค้ากำลังรออยู่
            </Text>
          </Card3D>
        )}
        {goneWhileViewing && (
          <Card3D variant="inset" style={styles.block} padding={spacing.lg}>
            <Text style={[typography.h3, { color: colors.textStrong }]}>🏃 งานนี้ไม่ว่างแล้ว</Text>
            <Text style={[typography.bodySm, { color: colors.textMuted }]}>{error?.message}</Text>
          </Card3D>
        )}
        {job.status === 'cancelled' && (
          <Card3D variant="inset" style={styles.block} padding={spacing.lg}>
            <Text style={[typography.h3, { color: colors.danger }]}>งานนี้ถูกยกเลิกแล้ว</Text>
            <Text style={[typography.bodySm, { color: colors.text }]}>
              {job.cancellation?.reason || 'ไม่ต้องไปรับหรือส่งของแล้ว ระบบหยุดแชร์ตำแหน่งให้เรียบร้อย'}
            </Text>
          </Card3D>
        )}
        {job.status === 'failed' && (
          <Card3D variant="inset" style={styles.block} padding={spacing.lg}>
            <Text style={[typography.h3, { color: colors.danger }]}>ส่งไม่สำเร็จ</Text>
            <Text style={[typography.bodySm, { color: colors.text }]}>
              {job.failure?.reason_text || 'บันทึกแล้ว'}
              {job.failure?.note ? ` — ${job.failure.note}` : ''}
            </Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>ทีมงานจะติดต่อเพื่อจัดการคืนสินค้า</Text>
          </Card3D>
        )}
        {active && gpsOff && (
          <Card3D variant="inset" style={styles.block} padding={spacing.md}>
            <Text style={[typography.bodyStrong, { color: colors.danger }]}>⚠️ GPS ของเครื่องปิดอยู่</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              ลูกค้าจะไม่เห็นตำแหน่งของคุณ เปิด GPS ในแถบด่วนของเครื่องได้เลย
            </Text>
          </Card3D>
        )}

        {/* ---------- รายได้ของงาน ---------- */}
        <Card3D gradientBorder padding={0} style={styles.block}>
          <LinearGradient colors={gradients.hero} style={styles.hero}>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              {job.status === 'completed' ? 'ค่าส่งที่คุณได้รับ' : 'ค่าส่งที่คุณจะได้รับ'}
            </Text>
            <PriceText amount={job.rider_earnings} size="xl" tone="gold" />
            <View style={styles.pills}>
              {!!tripKm && <Pill label={`ระยะส่ง ${tripKm}`} icon="🛣️" />}
              {!!toPickup && !finished && <Pill label={`ห่างจุดรับ ${toPickup}`} icon="🛵" />}
              {!!eta && !finished && <Pill label={eta} icon="⏱️" />}
              {job.status === 'completed' && (
                <Pill
                  label={job.earnings_settled ? 'เข้ากระเป๋าแล้ว' : 'กำลังโอนเข้ากระเป๋า'}
                  tone={job.earnings_settled ? 'success' : 'info'}
                  icon="👛"
                />
              )}
            </View>
          </LinearGradient>
          {job.is_cod && (
            <View style={[styles.codBar, { backgroundColor: colors.warningSoft }]}>
              <Text style={[typography.bodyStrong, { color: colors.text }]}>💵 เก็บเงินสดจากลูกค้า</Text>
              <PriceText amount={job.cod_amount} size="lg" tone="strong" />
            </View>
          )}
        </Card3D>

        {/* ---------- ไทม์ไลน์ ---------- */}
        {job.status !== 'pending' && <StepTimeline job={job} />}

        {/* ---------- ตำแหน่งสดของลูกค้า (เฉพาะเมื่อแชร์มา) ---------- */}
        {!!live && (
          <Card3D style={styles.block} padding={spacing.lg} gradientBorder={gradients.success}>
            <Text style={[typography.h3, { color: colors.textStrong }]}>📍 ลูกค้าแชร์ตำแหน่งสด</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>อัปเดต {formatAgo(live.updated_at)}</Text>
            <Button3D
              title="นำทางไปหาลูกค้า"
              icon="🧭"
              variant="success"
              size="sm"
              onPress={() => openNavigation({ latitude: live.latitude, longitude: live.longitude })}
              style={styles.gapTop}
            />
          </Card3D>
        )}

        {/* ---------- จุดรับ / จุดส่ง ---------- */}
        <PointCard kind="pickup" point={job.pickup} canContact={isMine && !finished} />
        <PointCard kind="dropoff" point={job.dropoff} canContact={isMine && !finished} />

        {/* ---------- รายการของ / ค่าส่ง ---------- */}
        {(!!job.items_summary || !!job.description) && (
          <Card3D style={styles.block} padding={spacing.lg}>
            <Text style={[typography.h3, styles.cardTitle, { color: colors.textStrong }]}>🧾 รายการ</Text>
            <Text style={[typography.body, { color: colors.text }]}>{job.items_summary || job.description}</Text>
          </Card3D>
        )}

        <Card3D variant="inset" style={styles.block} padding={spacing.lg}>
          <Text style={[typography.h3, styles.cardTitle, { color: colors.textStrong }]}>💰 ค่าส่งงานนี้</Text>
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
            <PriceText amount={job.rider_earnings} size="md" tone="gold" />
          </View>
        </Card3D>

        {/* ---------- รูปหลักฐาน ---------- */}
        {!!(job.photos?.pickup || job.photos?.delivery || job.photos?.failure) && (
          <Card3D style={styles.block} padding={spacing.lg}>
            <Text style={[typography.h3, styles.cardTitle, { color: colors.textStrong }]}>📸 รูปหลักฐาน</Text>
            <View style={styles.photoRow}>
              {[
                { key: 'pickup', label: 'ตอนรับของ', uri: job.photos?.pickup },
                { key: 'delivery', label: 'ตอนส่งของ', uri: job.photos?.delivery },
                { key: 'failure', label: 'ส่งไม่สำเร็จ', uri: job.photos?.failure },
              ]
                .filter((p) => !!p.uri)
                .map((p) => (
                  <View key={p.key} style={styles.proofItem}>
                    <Image
                      source={{ uri: p.uri as string }}
                      style={[styles.proof, { backgroundColor: colors.inset }]}
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
        {active && (
          <Card3D variant="inset" style={styles.block} padding={spacing.md}>
            {trackingMode === 'background' ? (
              <Text style={[typography.bodySm, { color: colors.text }]}>
                📡 กำลังแชร์ตำแหน่งให้ลูกค้า (ทำงานแม้ปิดหน้าจอ) · หยุดเองเมื่อจบงาน
              </Text>
            ) : trackingMode === 'foreground' ? (
              <>
                <Text style={[typography.bodySm, { color: colors.text }]}>
                  📡 แชร์ตำแหน่งเฉพาะตอนเปิดแอปไว้ — ถ้าปิดหน้าจอ ลูกค้าจะเห็นตำแหน่งล่าสุดเท่านั้น
                </Text>
                <Button3D
                  title="ให้ติดตามต่อแม้ปิดหน้าจอ"
                  icon="🛰️"
                  size="sm"
                  variant="secondary"
                  onPress={upgradeToBackground}
                  style={styles.gapTop}
                />
              </>
            ) : trackingMode === 'none' ? (
              <>
                <Text style={[typography.bodySm, { color: colors.danger }]}>
                  ⚠️ ยังไม่ได้แชร์ตำแหน่ง ลูกค้าจะไม่เห็นว่าคุณอยู่ไหน
                </Text>
                <Button3D title="อนุญาตตำแหน่ง" icon="📍" size="sm" onPress={enableLocation} style={styles.gapTop} />
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
            { backgroundColor: colors.card, paddingBottom: Math.max(insets.bottom, spacing.md), borderTopColor: colors.border },
            shadowStyle('lg', colors.shadowDark),
          ]}
        >
          {!!STEP_HINT[job.status] && flowActions.length > 0 && (
            <Text style={[typography.caption, styles.hint, { color: colors.textMuted }]}>💡 {STEP_HINT[job.status]}</Text>
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
                <Button3D title="คืนงาน" icon="↩️" variant="ghost" size="sm" onPress={onAction('release')} />
              )}
              {allowed.has('fail') && (
                <Button3D title="ส่งไม่สำเร็จ" icon="⚠️" variant="ghost" size="sm" onPress={onAction('fail')} />
              )}
            </View>
          )}
          {isMine && finished && flowActions.length === 0 && (
            <Button3D
              title="หางานต่อ"
              icon="🛵"
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
        icon="📦"
        title="ได้ของครบแล้วใช่ไหม?"
        subtitle="ถ่ายรูปของที่รับมาได้ (ไม่บังคับ) ช่วยยืนยันถ้ามีปัญหาภายหลัง"
        busy={sheetBusy}
        onClose={closeSheet}
        footer={
          <>
            <Button3D title="ยืนยันรับของแล้ว" icon="✅" size="lg" fullWidth onPress={submitPickup} />
            <Button3D title="ยังก่อน" variant="ghost" size="sm" onPress={closeSheet} disabled={sheetBusy} />
          </>
        }
      >
        <PhotoSlot uri={photoUri} label="ถ่ายรูปของที่รับ" onTake={shootPhoto} onClear={() => setPhotoUri(null)} />
      </RiderSheet>

      {/* ---------- sheet: ส่งสำเร็จ ---------- */}
      <RiderSheet
        visible={sheet === 'deliver'}
        icon="✅"
        title="ส่งของสำเร็จ"
        subtitle="ถ่ายรูปตอนส่งของให้ลูกค้าเป็นหลักฐาน"
        busy={sheetBusy}
        onClose={closeSheet}
        footer={
          <>
            <Button3D
              title="ยืนยันส่งสำเร็จ"
              icon="🎉"
              variant="success"
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
                {codConfirmed && <Text style={[styles.checkMark, { color: colors.textOnAccent }]}>✓</Text>}
              </View>
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
                  เก็บเงินสด {formatBaht(job.cod_amount)} แล้ว
                </Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  ระบบจะหักยอดนำส่งจากกระเป๋าของคุณ พร้อมบันทึกรายได้ให้ทันที
                </Text>
              </View>
            </View>
          </Card3D>
        )}
        <TextInput
          value={note}
          onChangeText={setNote}
          placeholder="หมายเหตุ (ไม่บังคับ) เช่น ฝากไว้กับ รปภ."
          placeholderTextColor={colors.textFaint}
          maxLength={500}
          multiline
          accessibilityLabel="หมายเหตุการส่ง"
          style={[typography.body, styles.input, { color: colors.text, backgroundColor: colors.inset, borderColor: colors.border }]}
        />
      </RiderSheet>

      {/* ---------- sheet: ส่งไม่สำเร็จ ---------- */}
      <RiderSheet
        visible={sheet === 'fail'}
        icon="⚠️"
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
        <TextInput
          value={note}
          onChangeText={setNote}
          placeholder={failReason === 'other' ? 'เล่ารายละเอียด (จำเป็น)' : 'รายละเอียดเพิ่มเติม (ไม่บังคับ)'}
          placeholderTextColor={colors.textFaint}
          maxLength={1000}
          multiline
          accessibilityLabel="รายละเอียดที่ส่งไม่สำเร็จ"
          style={[typography.body, styles.input, { color: colors.text, backgroundColor: colors.inset, borderColor: colors.border }]}
        />
        <PhotoSlot uri={photoUri} label="ถ่ายรูปประกอบ" onTake={shootPhoto} onClear={() => setPhotoUri(null)} />
      </RiderSheet>

      {/* ---------- sheet: คืนงาน ---------- */}
      <RiderSheet
        visible={sheet === 'release'}
        icon="↩️"
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
          <TextInput
            value={note}
            onChangeText={setNote}
            placeholder="บอกเหตุผลสั้นๆ"
            placeholderTextColor={colors.textFaint}
            maxLength={255}
            accessibilityLabel="เหตุผลที่คืนงาน"
            style={[typography.body, styles.input, { color: colors.text, backgroundColor: colors.inset, borderColor: colors.border }]}
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
  },
  block: {
    marginBottom: spacing.md,
  },
  cardTitle: {
    marginBottom: spacing.sm,
  },
  gapTop: {
    marginTop: spacing.sm,
  },
  hero: {
    padding: spacing.lg,
    gap: spacing.xs,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  codBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    borderBottomLeftRadius: radii.xl,
    borderBottomRightRadius: radii.xl,
  },
  stepRow: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  stepRail: {
    alignItems: 'center',
    width: 36,
  },
  stepDot: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepDotText: {
    fontSize: 18,
  },
  stepDotSmall: {
    fontSize: 14,
    fontWeight: '700',
  },
  stepLine: {
    width: 3,
    flex: 1,
    minHeight: 16,
    borderRadius: 2,
    marginVertical: 2,
  },
  stepBody: {
    flex: 1,
    paddingTop: 6,
    paddingBottom: spacing.md,
  },
  pointHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  pointIcon: {
    fontSize: 28,
  },
  pointText: {
    marginTop: spacing.sm,
  },
  noteBox: {
    borderRadius: radii.md,
    padding: spacing.sm,
    marginTop: spacing.sm,
  },
  pointActions: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  feeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: spacing.xs,
  },
  feeTotal: {
    borderTopWidth: StyleSheet.hairlineWidth,
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
    borderRadius: radii.md,
  },
  meta: {
    textAlign: 'center',
    marginTop: spacing.sm,
  },
  actionBar: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
  },
  hint: {
    textAlign: 'center',
    marginBottom: spacing.sm,
  },
  sideRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: spacing.lg,
    marginTop: spacing.xs,
  },
  photoWrap: {
    gap: spacing.sm,
  },
  photo: {
    width: '100%',
    height: 220,
    borderRadius: radii.lg,
  },
  photoActions: {
    flexDirection: 'row',
    gap: spacing.sm,
    alignItems: 'center',
  },
  photoEmpty: {
    height: 160,
    borderRadius: radii.lg,
    borderWidth: 2,
    borderStyle: 'dashed',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xs,
  },
  photoEmptyIcon: {
    fontSize: 36,
  },
  checkRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  checkbox: {
    width: 28,
    height: 28,
    borderRadius: 8,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkMark: {
    fontSize: 16,
    fontWeight: '800',
  },
  input: {
    borderRadius: radii.md,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    minHeight: 48,
    textAlignVertical: 'top',
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
});
