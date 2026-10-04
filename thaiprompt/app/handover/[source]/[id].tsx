/**
 * รับของจากไรเดอร์ (ผู้ซื้อ) — /handover/{shop|fresh-market}/{orderId}?no=เลขออเดอร์
 * ตามแบบ BuyerHandshake.png (ระหว่างส่งมอบ) + Complete.png (รับของเรียบร้อย) ที่เจ้าของอนุมัติ
 *
 * ขั้นตอน
 *   1. ไรเดอร์สแกน QR บนจอนี้ (QR หมุนเวียน อายุสั้น — กล้องไรเดอร์เสีย = บอกรหัส 6 หลักแทน)
 *   2. ผู้ซื้อสแกน QR บนจอไรเดอร์ (POST .../handover/scan {token})
 *      กล้องผู้ซื้อใช้ไม่ได้ → กรอกรหัส 6 หลักของไรเดอร์ (POST .../handover/scan {code}) §A3
 *   ครบสองฝ่าย → server ปลดเงินพักให้ร้าน/ไรเดอร์ทันที → หน้าสำเร็จ + การแบ่งเงิน + ให้หัวใจไรเดอร์
 * ทางสำรอง: ไรเดอร์ถ่ายรูปที่จุดส่ง → รอ 3 นาที → วางของ → ปลดเงินอัตโนมัติใน 24 ชม. ถ้าไม่แจ้งปัญหา
 *   ระหว่างนั้นผู้ซื้อกด "ได้รับของแล้ว" ได้ทันที (can_confirm_received → POST .../confirm-received) §A4
 *
 * สถานะพิเศษ
 *   - งานไรเดอร์ยังไม่ถึงขั้นรับของ (pending/accepted/picking_up) → "ยังไม่ถึงขั้นรับของ" (U4)
 *   - จบแล้ว: รับของเรียบร้อย (+ การแบ่งเงิน) / คืนเงินแล้ว — ไม่ค้าง "กำลังสรุป" ตลอดไป (U5) · ดึงลงเพื่อรีเฟรช
 *   - งานไรเดอร์ส่งไม่สำเร็จ/ถูกยกเลิก แต่การรับของยังไม่จบ → หน้าแจ้งชัดเจน + ปุ่มไปหน้าออเดอร์ หยุดถามซ้ำ (B6)
 *
 * ความปลอดภัย
 *   - กันแคปหน้าจอ/อัดจอระหว่างเปิดหน้านี้ (useSensitiveScreen) — QR/รหัสเป็นของใช้ครั้งเดียว
 *   - ข้อมูลทุกอย่างจาก server · ดึงใหม่ทุก 4 วินาทีระหว่างหน้าเปิด (และก่อน QR หมดอายุ)
 *     ผลการดึงที่เริ่มก่อนการสแกน/ยืนยัน/แจ้งปัญหา ถูกทิ้ง ไม่ทับผลล่าสุด (requestId — U3)
 *   - เวลาหมดอายุ QR นับตามนาฬิกา server (server_now) ไม่ใช่นาฬิกาเครื่อง (§A2)
 *   - ปุ่มสแกน/กรอกรหัส/ยืนยันรับของ/แจ้งปัญหากันกดซ้ำ · ข้อความ error ภาษาไทยจาก code ของ server
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, RefreshControl, ScrollView, StatusBar, StyleSheet, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useLocalSearchParams } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '@/components/ui/Text';
import {
  Button3D,
  Card3D,
  EmptyState,
  GlassIconButton,
  Icon,
  IconButton,
  OnHeaderProvider,
  Pill,
  RoyalHeader,
  resultHaptic,
} from '@/components/ui';
import { Field, FormSheet, NoticeBanner, RadioMark, StickyBar, callPhone, formatThaiDateTime } from '@/components/shop';
import { useFocusedInterval, useMountedRef } from '@/components/taladsod/hooks';
import { PersonAvatar } from '@/components/people/PersonAvatar';
import { RotatingQr } from '@/components/handover/RotatingQr';
import { QrScannerSheet } from '@/components/handover/QrScannerSheet';
import { RiderHeartCard } from '@/components/handover/RiderHeartCard';
import { SettlementCard } from '@/components/handover/SettlementCard';
import { isScreenCaptureProtectionAvailable, useSensitiveScreen } from '@/hooks/useSensitiveScreen';
import { useAuthStore } from '@/stores/authStore';
import {
  HANDOVER_DISPUTE_REASONS,
  HANDOVER_PRE_PICKUP_JOB_STATUSES,
  confirmHandoverReceived,
  disputeOrderHandover,
  getOrderHandover,
  isHandoverFinal,
  isHandoverJobEnded,
  isHandoverSuccess,
  sanitizeScannedToken,
  scanRiderHandoverQr,
  submitRiderHandoverCode,
  type BuyerHandoverData,
  type HandoverDisputeReason,
  type HandoverSource,
} from '@/services/api/handoverApi';
import { num, type ApiFailure } from '@/services/api/client';
import { vehicleText } from '@/services/api/riderSocialApi';
import { getMyOrder } from '@/services/api/shopApi';
import { getFmOrder } from '@/services/api/taladsodApi';
import { useTheme, radii, spacing, typography } from '@/theme';

const POLL_ACTIVE_MS = 4000;
const POLL_IDLE_MS = 10000;
/** จบงานแล้วแต่ยังไม่มีสรุปการแบ่งเงิน → ถามซ้ำทุกเท่านี้ ไม่เกิน SETTLEMENT_POLL_MAX ครั้ง (U5) */
const SETTLEMENT_POLL_MS = 5000;
const SETTLEMENT_POLL_MAX = 6;
/** ห่างจุดส่งไม่เกินนี้ = "ถึงจุดส่ง" (ตรงกับ geofence ของ server) */
const ARRIVED_M = 150;
const SHEET_RADIUS = 26;

type LoadError = { code: string; message: string; status: number };

/** error ที่ลองส่งผลสแกนเดิมซ้ำได้ (เน็ต/เซิร์ฟเวอร์) — ไม่ใช่ QR ผิด */
const isRetryableFailure = (res: ApiFailure): boolean =>
  res.status === 0 || res.status >= 500 || res.code === 'TOO_MANY_REQUESTS';

/** ข้อความผิดพลาดของรหัส 6 หลักของไรเดอร์ (ใส่จำนวนครั้งที่เหลือ / เวลาที่กรอกใหม่ได้) */
const codeErrorText = (res: ApiFailure): string => {
  const data = res.data && typeof res.data === 'object' ? res.data : {};
  if (res.code === 'HANDOVER_CODE_INVALID') {
    const left = num(data.attempts_left, -1);
    return `รหัสไม่ถูกต้อง ตรวจกับไรเดอร์อีกครั้ง${left >= 0 ? ` (กรอกได้อีก ${left} ครั้ง)` : ''}`;
  }
  if (res.code === 'HANDOVER_CODE_LOCKED') {
    const at = timeText(typeof data.locked_until === 'string' ? data.locked_until : typeof data.retry_at === 'string' ? data.retry_at : null);
    return `กรอกรหัสผิดหลายครั้ง ระบบพักการกรอกชั่วคราว${at ? ` ลองใหม่ได้ตอน ${at} น.` : ''} ระหว่างนี้สแกน QR ของไรเดอร์แทนได้`;
  }
  return res.message;
};

interface OrderInfo {
  number: string | null;
  riderPhone: string | null;
}

/** เวลา HH:MM จาก ISO */
const timeText = (iso: string | null | undefined): string => {
  if (!iso) return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};

/** เลขออเดอร์จาก query (กันข้อความแปลกปลอม) */
const cleanOrderNo = (value: unknown): string | null =>
  typeof value === 'string' && /^[A-Za-z0-9#\-_/]{1,40}$/.test(value) ? value : null;

export default function BuyerHandoverScreen() {
  const params = useLocalSearchParams<{ source?: string; id?: string; no?: string }>();
  const source: HandoverSource | null =
    params.source === 'shop' || params.source === 'fresh-market' ? params.source : null;
  const orderId = /^\d+$/.test(String(params.id || '')) ? Number(params.id) : 0;
  useSensitiveScreen('handover');

  const { colors, gradients } = useTheme();
  const insets = useSafeAreaInsets();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const mountedRef = useMountedRef();

  const [data, setData] = useState<BuyerHandoverData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<LoadError | null>(null);
  const [orderInfo, setOrderInfo] = useState<OrderInfo>({ number: cleanOrderNo(params.no), riderPhone: null });

  const [scanOpen, setScanOpen] = useState(false);
  const [scanMode, setScanMode] = useState<'camera' | 'code'>('camera');
  const [scanBusy, setScanBusy] = useState(false);
  const [scanResetKey, setScanResetKey] = useState(0);
  const [codeError, setCodeError] = useState<string | null>(null);
  const [confirmBusy, setConfirmBusy] = useState(false);
  const [disputeOpen, setDisputeOpen] = useState(false);
  const [disputeReason, setDisputeReason] = useState<HandoverDisputeReason | null>(null);
  const [disputeNote, setDisputeNote] = useState('');
  const [disputeBusy, setDisputeBusy] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [settlementPolls, setSettlementPolls] = useState(0);

  const loadBusyRef = useRef(false);
  /** เลขรอบล่าสุด — ผลของรอบเก่า (เริ่มก่อนสแกน/ยืนยัน/แจ้งปัญหา) ถูกทิ้ง (U3) */
  const requestIdRef = useRef(0);
  const scanBusyRef = useRef(false);
  const confirmBusyRef = useRef(false);
  const disputeBusyRef = useRef(false);
  const prevStatusRef = useRef<string | null>(null);
  const protectedScreen = useMemo(() => isScreenCaptureProtectionAvailable(), []);

  // ---------- โหลด ----------
  const load = useCallback(
    async (mode: 'initial' | 'silent' | 'refresh') => {
      if (!source || !orderId || !isAuthenticated) {
        setLoading(false);
        return;
      }
      // poll ซ้อนกันไม่ได้ · ดึงลงเพื่อรีเฟรชยอมให้ซ้อน (รอบล่าสุดชนะด้วย requestId)
      if (loadBusyRef.current && mode !== 'refresh') return;
      loadBusyRef.current = true;
      const requestId = ++requestIdRef.current;
      if (mode === 'refresh') setRefreshing(true);
      const res = await getOrderHandover(source, orderId);
      loadBusyRef.current = false;
      if (!mountedRef.current) return;
      if (mode === 'refresh') setRefreshing(false);
      // ระหว่างรอมีการสแกน/ยืนยัน/แจ้งปัญหา หรือดึงรอบใหม่กว่า → ทิ้งผลนี้
      if (requestId !== requestIdRef.current) return;
      if (res.success) {
        setData(res.data);
        setError(null);
      } else if (mode !== 'silent' || res.code === 'HANDOVER_NOT_READY' || res.status === 404 || res.status === 403) {
        // เน็ตหลุดตอน poll → คงหน้าจอเดิมไว้ ไม่เด้งเป็นหน้า error
        setError({ code: res.code, message: res.message, status: res.status });
      }
      setLoading(false);
    },
    [source, orderId, isAuthenticated, mountedRef]
  );

  /** ผลจากการสแกน/ยืนยัน/แจ้งปัญหา = ข้อมูลล่าสุด (ทิ้งผล poll ที่ค้างอยู่) */
  const applyActionResult = useCallback((next: BuyerHandoverData) => {
    requestIdRef.current += 1;
    setData(next);
    setError(null);
  }, []);

  useEffect(() => {
    load('initial');
  }, [load]);

  // เลขออเดอร์ + เบอร์ไรเดอร์ (โหลดครั้งเดียว ไม่บังคับ)
  useEffect(() => {
    if (!source || !orderId || !isAuthenticated) return;
    let cancelled = false;
    (async () => {
      if (source === 'shop') {
        const res = await getMyOrder(orderId);
        if (cancelled || !mountedRef.current || !res.success) return;
        setOrderInfo({ number: res.data.order_number || null, riderPhone: res.data.rider?.rider?.phone ?? null });
      } else {
        const res = await getFmOrder(orderId);
        if (cancelled || !mountedRef.current || !res.success) return;
        setOrderInfo({ number: res.data.order_number || null, riderPhone: res.data.rider_job?.rider_phone ?? null });
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [source, orderId, isAuthenticated, mountedRef]);

  const handover = data?.handover ?? null;
  const final = isHandoverFinal(handover);
  const notReady = error?.code === 'HANDOVER_NOT_READY';
  const jobStatus = data?.job?.status ?? '';
  /** ไรเดอร์ยังไม่ได้ของจากร้าน (รอรับงาน / กำลังไปร้าน) — U4 */
  const prePickup = !final && HANDOVER_PRE_PICKUP_JOB_STATUSES.includes(jobStatus);
  /** งานไรเดอร์จบแบบไม่ได้ส่ง (ส่งไม่สำเร็จ/ยกเลิก) แต่การรับของยังไม่จบ = จบสำหรับหน้านี้ (B6) */
  const jobEnded = isHandoverJobEnded(data);
  const idle = notReady || prePickup || (!!handover && !handover.required);

  // ดึงสถานะใหม่ระหว่างหน้าเปิด (หยุดเมื่อจบงาน หรืองานไรเดอร์จบแบบไม่ได้ส่ง — ดึงลงเพื่อรีเฟรชได้)
  useFocusedInterval(
    () => load('silent'),
    idle ? POLL_IDLE_MS : POLL_ACTIVE_MS,
    !!source && !!orderId && !final && !jobEnded && (!!data || notReady)
  );

  // งานไรเดอร์จบระหว่างเปิดแผ่นสแกน/แจ้งปัญหา → ปิดแผ่นที่ค้าง (ไม่มีอะไรให้สแกนแล้ว)
  useEffect(() => {
    if (!jobEnded) return;
    setScanOpen(false);
    setDisputeOpen(false);
  }, [jobEnded]);

  // จบงานแล้วแต่ server ยังสรุปการแบ่งเงินไม่เสร็จ → ถามซ้ำช่วงสั้นๆ (ไม่ค้าง "กำลังสรุป" ตลอดไป — U5)
  const waitingSettlement = final && isHandoverSuccess(handover) && !data?.settlement;
  useFocusedInterval(
    async () => {
      setSettlementPolls((n) => n + 1);
      await load('silent');
    },
    SETTLEMENT_POLL_MS,
    waitingSettlement && settlementPolls < SETTLEMENT_POLL_MAX
  );

  // สถานะเปลี่ยนเป็นสำเร็จ → สั่นแจ้ง + ปิดแผ่นที่ค้าง
  useEffect(() => {
    const status = handover?.status ?? null;
    const prev = prevStatusRef.current;
    prevStatusRef.current = status;
    if (prev && status && prev !== status && isHandoverSuccess(handover)) {
      resultHaptic('success');
      setScanOpen(false);
      setDisputeOpen(false);
    }
  }, [handover]);

  // ---------- สแกน QR / กรอกรหัสของไรเดอร์ ----------
  const openScanner = (mode: 'camera' | 'code') => {
    setScanMode(mode);
    setCodeError(null);
    setScanOpen(true);
  };

  const onScanned = useCallback(
    async (raw: string) => {
      if (!source || scanBusyRef.current) return;
      const token = sanitizeScannedToken(raw);
      if (!token) {
        resultHaptic('error');
        Alert.alert('QR นี้ใช้ไม่ได้', 'สแกน QR บนจอแอปของไรเดอร์ที่มาส่งออเดอร์นี้นะ');
        return;
      }
      scanBusyRef.current = true;
      setScanBusy(true);
      requestIdRef.current += 1;
      const res = await scanRiderHandoverQr(source, orderId, token);
      scanBusyRef.current = false;
      if (!mountedRef.current) return;
      setScanBusy(false);
      if (res.success) {
        resultHaptic('success');
        applyActionResult(res.data);
        setScanOpen(false);
        return;
      }
      if (res.code === 'HANDOVER_FINAL') {
        setScanOpen(false);
        load('silent');
        return;
      }
      resultHaptic('error');
      // เน็ต/เซิร์ฟเวอร์ขัดข้อง → ให้สแกน QR เดิมได้อีกหลังกดตกลง (U2) · QR ผิด/หมดอายุ → ต้องสแกนใหม่
      const retryable = isRetryableFailure(res);
      Alert.alert('สแกนไม่สำเร็จ', res.message, [
        { text: 'ตกลง', onPress: () => retryable && mountedRef.current && setScanResetKey((k) => k + 1) },
      ]);
    },
    [source, orderId, load, mountedRef, applyActionResult]
  );

  /** กล้องใช้ไม่ได้ → กรอกรหัส 6 หลักที่ไรเดอร์อ่านให้ (§A3) */
  const submitCode = useCallback(
    async (code: string) => {
      if (!source || scanBusyRef.current) return;
      const digits = code.replace(/\D/g, '');
      if (digits.length !== 6) {
        setCodeError('รหัสต้องเป็นตัวเลข 6 หลัก');
        return;
      }
      scanBusyRef.current = true;
      setScanBusy(true);
      setCodeError(null);
      requestIdRef.current += 1;
      const res = await submitRiderHandoverCode(source, orderId, digits);
      scanBusyRef.current = false;
      if (!mountedRef.current) return;
      setScanBusy(false);
      if (res.success) {
        resultHaptic('success');
        applyActionResult(res.data);
        setScanOpen(false);
        return;
      }
      if (res.code === 'HANDOVER_FINAL') {
        setScanOpen(false);
        load('silent');
        return;
      }
      resultHaptic('error');
      // แสดงใต้ช่องรหัส (ไม่ปิดแผ่น ให้แก้รหัสต่อได้)
      setCodeError(codeErrorText(res));
    },
    [source, orderId, load, mountedRef, applyActionResult]
  );

  // ---------- ได้รับของแล้ว (ไรเดอร์รอ/วางของไว้ให้) §A4 ----------
  const doConfirmReceived = async () => {
    if (!source || confirmBusyRef.current) return;
    confirmBusyRef.current = true;
    setConfirmBusy(true);
    requestIdRef.current += 1;
    const res = await confirmHandoverReceived(source, orderId);
    confirmBusyRef.current = false;
    if (!mountedRef.current) return;
    setConfirmBusy(false);
    if (res.success) {
      resultHaptic('success');
      applyActionResult(res.data);
      return;
    }
    resultHaptic('error');
    Alert.alert('ยืนยันไม่สำเร็จ', res.message);
    load('silent');
  };

  const confirmReceived = () => {
    if (confirmBusyRef.current) return;
    Alert.alert(
      'ได้รับของครบแล้วใช่ไหม?',
      'ยืนยันแล้วเงินที่พักไว้จะโอนให้ร้านและไรเดอร์ทันที ยกเลิกไม่ได้ ถ้ายังไม่ได้รับของ อย่ากดยืนยัน',
      [
        { text: 'ยังไม่ได้รับ', style: 'cancel' },
        { text: 'ได้รับของแล้ว', onPress: () => doConfirmReceived() },
      ]
    );
  };

  // ---------- แจ้งปัญหา ----------
  const openDispute = () => {
    setDisputeReason(null);
    setDisputeNote('');
    setDisputeOpen(true);
  };

  const submitDispute = async () => {
    if (!source || disputeBusyRef.current) return;
    if (!disputeReason) {
      Alert.alert('เลือกปัญหาก่อนนะ', 'บอกทีมงานว่าเกิดอะไรขึ้น');
      return;
    }
    if (disputeReason === 'other' && disputeNote.trim().length < 5) {
      Alert.alert('เล่ารายละเอียดหน่อยนะ', 'พิมพ์อย่างน้อย 5 ตัวอักษร ทีมงานจะได้ช่วยได้ตรงจุด');
      return;
    }
    disputeBusyRef.current = true;
    setDisputeBusy(true);
    requestIdRef.current += 1;
    const res = await disputeOrderHandover(source, orderId, disputeReason, disputeNote);
    disputeBusyRef.current = false;
    if (!mountedRef.current) return;
    setDisputeBusy(false);
    if (res.success) {
      resultHaptic('success');
      applyActionResult(res.data);
      setDisputeOpen(false);
      Alert.alert('แจ้งปัญหาแล้ว', 'ทีมงานจะตรวจสอบและแจ้งผลให้ทราบ ระหว่างนี้เงินของคุณยังพักไว้ ไม่โอนให้ใคร');
      return;
    }
    resultHaptic('error');
    Alert.alert('แจ้งปัญหาไม่สำเร็จ', res.message);
    if (res.code === 'DISPUTE_NOT_ALLOWED' || res.code === 'HANDOVER_FINAL') {
      setDisputeOpen(false);
      load('silent');
    }
  };

  const goOrder = () => {
    if (!source || !orderId) {
      router.replace('/(tabs)/orders' as never);
      return;
    }
    // มาจากหน้าออเดอร์ → ย้อนกลับไปหน้าเดิม (ไม่ซ้อนหน้าออเดอร์ซ้ำ) · มาจากแจ้งเตือน → แทนที่ด้วยหน้าออเดอร์
    router.dismissTo((source === 'shop' ? `/order/${orderId}` : `/taladsod/order/${orderId}`) as never);
  };

  const goBack = () => {
    if (router.canGoBack()) router.back();
    else goOrder();
  };

  const subtitle = orderInfo.number ? `ออเดอร์ #${orderInfo.number}` : source === 'fresh-market' ? 'ออเดอร์ตลาดสด' : 'ออเดอร์ร้านค้า';

  // =====================================================
  // หัวหน้าจอ (น้ำเงินกรมท่า) ใช้ร่วมทุกสถานะ
  // =====================================================
  const header = (extra?: React.ReactNode, titleText?: string) => (
    <RoyalHeader
      ornamentTop={insets.top - 18}
      ornamentWidth={210}
      style={{ paddingTop: insets.top + spacing.xs, paddingBottom: SHEET_RADIUS + spacing.md }}
    >
      <OnHeaderProvider value>
        <View style={styles.headerRow}>
          <GlassIconButton icon="caret-left" weight="bold" accessibilityLabel="ย้อนกลับ" onPress={goBack} />
          <View style={styles.flex}>
            <Text accessibilityRole="header" numberOfLines={1} style={[typography.serif, { color: colors.onHeader }]}>
              {titleText || 'รับของจากไรเดอร์'}
            </Text>
            {!titleText && (
              <Text numberOfLines={1} style={[typography.bodySm, { color: colors.onHeaderMuted }]}>
                {subtitle}
              </Text>
            )}
          </View>
        </View>
        {extra}
      </OnHeaderProvider>
    </RoyalHeader>
  );

  const shell = (children: React.ReactNode, opts: { headerExtra?: React.ReactNode; title?: string; bottom?: React.ReactNode } = {}) => (
    <View style={[styles.root, { backgroundColor: gradients.hero[gradients.hero.length - 1] }]}>
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      {header(opts.headerExtra, opts.title)}
      <View style={[styles.sheet, { backgroundColor: colors.background }]}>
        <ScrollView
          contentContainerStyle={[styles.content, { paddingBottom: (opts.bottom ? 110 : spacing.xxl) + insets.bottom }]}
          showsVerticalScrollIndicator={false}
          refreshControl={
            isAuthenticated && !!source && !!orderId ? (
              <RefreshControl
                refreshing={refreshing}
                onRefresh={() => {
                  setSettlementPolls(0);
                  load('refresh');
                }}
                tintColor={colors.gold}
                colors={[colors.gold]}
                progressBackgroundColor={colors.card}
              />
            ) : undefined
          }
        >
          {children}
        </ScrollView>
      </View>
      {opts.bottom}
    </View>
  );

  // =====================================================
  // สถานะพิเศษ
  // =====================================================

  if (!isAuthenticated) {
    return shell(
      <EmptyState icon="lock-key" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
    );
  }

  if (!source || !orderId) {
    return shell(<EmptyState icon="magnifying-glass" title="ไม่พบออเดอร์นี้" actionLabel="ดูคำสั่งซื้อ" onAction={goOrder} />);
  }

  if (loading && !data) {
    return shell(<ActivityIndicator size="large" color={colors.gold} style={styles.loader} />);
  }

  if (!data) {
    if (notReady) {
      return shell(
        <EmptyState
          art="scooter"
          title="ยังไม่ถึงขั้นรับของ"
          message="เมื่อไรเดอร์รับของจากร้านแล้ว หน้านี้จะแสดง QR ให้สแกนกันตอนรับของ"
          actionLabel="ไปหน้าคำสั่งซื้อ"
          onAction={goOrder}
        />
      );
    }
    const notFound = error?.status === 404 || error?.status === 403;
    return shell(
      <EmptyState
        variant={notFound ? 'empty' : 'error'}
        icon={notFound ? 'magnifying-glass' : undefined}
        title={notFound ? 'ไม่พบการรับของของออเดอร์นี้' : undefined}
        message={notFound ? 'ออเดอร์นี้อาจไม่ได้ส่งด้วยไรเดอร์ หรือไม่ใช่ของบัญชีนี้' : error?.message}
        actionLabel={notFound ? 'ไปหน้าคำสั่งซื้อ' : 'ลองใหม่'}
        onAction={notFound ? goOrder : () => load('initial')}
      />
    );
  }

  const h = data.handover;
  const rider = data.rider;

  // ไรเดอร์ยังไม่ได้ของจากร้าน → ยังไม่ต้องเปิด QR (U4) — ตัดสินก่อน required เพราะ server ตัดสิน required ตอนไรเดอร์รับงาน
  if (prePickup) {
    return shell(
      <EmptyState
        art="scooter"
        title="ยังไม่ถึงขั้นรับของ"
        message={
          jobStatus === 'pending'
            ? 'ระบบกำลังหาไรเดอร์ให้ เมื่อไรเดอร์รับของจากร้านแล้ว หน้านี้จะแสดง QR ให้สแกนกันตอนรับของ'
            : 'ไรเดอร์กำลังไปรับของที่ร้าน เมื่อได้ของแล้ว หน้านี้จะแสดง QR ให้สแกนกันตอนรับของ'
        }
        actionLabel="ไปหน้าคำสั่งซื้อ"
        onAction={goOrder}
      />
    );
  }

  // งานไรเดอร์จบแบบไม่ได้ส่ง (ไรเดอร์ส่งไม่สำเร็จ / ออเดอร์ถูกยกเลิก) → ไม่ค้าง "กำลังสร้างรหัส…" ไม่ถามซ้ำ (B6)
  // ขั้นตอนต่อ (คืนเงิน / หาไรเดอร์ใหม่) ดูที่หน้าออเดอร์ · ดึงลงเพื่อรีเฟรชได้ถ้าระบบหาไรเดอร์คนใหม่ให้
  if (jobEnded) {
    const riderFailed = jobStatus === 'failed';
    return shell(
      <EmptyState
        icon={riderFailed ? 'warning-circle' : 'x-circle'}
        title={riderFailed ? 'ไรเดอร์ยกเลิกการส่ง' : 'ออเดอร์ถูกยกเลิก'}
        message={
          riderFailed
            ? 'การส่งรอบนี้ไม่สำเร็จ ไม่ต้องสแกน QR แล้ว ดูรายละเอียดและขั้นตอนต่อไปในหน้าออเดอร์'
            : 'การส่งของออเดอร์นี้ถูกยกเลิกแล้ว ไม่ต้องสแกน QR ดูรายละเอียดในหน้าออเดอร์'
        }
        actionLabel="ไปหน้าคำสั่งซื้อ"
        onAction={goOrder}
      />
    );
  }

  if (!h.required) {
    return shell(
      <EmptyState
        icon="check-circle"
        title="ออเดอร์นี้ไม่ต้องสแกน QR"
        message="ไรเดอร์ปิดงานตามขั้นตอนเดิม ติดตามสถานะได้ที่หน้าคำสั่งซื้อ"
        actionLabel="ไปหน้าคำสั่งซื้อ"
        onAction={goOrder}
      />
    );
  }

  // =====================================================
  // จบแล้ว — รับของเรียบร้อย / คืนเงิน (Complete.png)
  // =====================================================

  if (final) {
    const success = isHandoverSuccess(h);
    const methodText =
      h.method === 'fallback'
        ? 'ปลดเงินอัตโนมัติ'
        : h.method === 'admin'
          ? 'ทีมงานตรวจสอบแล้ว'
          : h.method === 'buyer_confirm'
            ? 'คุณยืนยันรับของแล้ว'
            : h.method === 'code'
              ? 'ยืนยันด้วยรหัส 6 หลัก'
              : h.status === 'released'
                ? 'ปลดเงินให้ร้านและไรเดอร์แล้ว'
                : 'สแกนครบทั้งสองฝ่าย';
    const doneLine = success
      ? [methodText, h.completed_at ? `${timeText(h.completed_at)} น.` : null].filter(Boolean).join(' · ')
      : 'ทีมงานตรวจสอบแล้ว คืนเงินเข้ากระเป๋าของคุณ';

    const medal = (
      <View style={styles.medalBlock}>
        <View style={[styles.medalRing, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
          <LinearGradient colors={success ? gradients.gold : gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.medal}>
            <Icon name={success ? 'check' : 'arrow-counter-clockwise'} size={38} color={success ? colors.textOnGold : colors.goldLight} weight="bold" />
          </LinearGradient>
        </View>
        <Text style={[typography.serifLg, styles.centerText, { color: colors.onHeader }]}>
          {success ? 'รับของเรียบร้อย' : 'คืนเงินแล้ว'}
        </Text>
        <Text style={[typography.bodySm, styles.centerText, { color: colors.onHeaderMuted }]}>{doneLine}</Text>
      </View>
    );

    const settlementBlock = !success ? (
      // คืนเงินแล้ว — ไม่มีการแบ่งเงิน (ไม่แสดง "กำลังสรุป")
      <Card3D padding={spacing.lg} radius={22} style={styles.block}>
        <View style={styles.row}>
          <View style={[styles.stateIcon, { backgroundColor: colors.infoSoft }]}>
            <Icon name="arrow-counter-clockwise" size={24} color={colors.info} weight="bold" />
          </View>
          <View style={styles.flex}>
            <Text style={[typography.h3, { color: colors.textStrong }]}>คืนเงินเข้ากระเป๋าแล้ว</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              {h.completed_at ? `เมื่อ ${formatThaiDateTime(h.completed_at)}` : 'ตามผลตรวจสอบของทีมงาน'}
            </Text>
          </View>
        </View>
        <Text style={[typography.bodySm, styles.gapTop, { color: colors.text }]}>
          เงินที่พักไว้ไม่ได้โอนให้ร้านหรือไรเดอร์ ระบบคืนเข้ากระเป๋าเงินของคุณ ตรวจยอดได้ที่ประวัติกระเป๋าเงิน
        </Text>
        <Button3D
          title="ดูประวัติกระเป๋าเงิน"
          icon="wallet"
          variant="secondary"
          size="md"
          fullWidth
          onPress={() => router.push('/wallet-history' as never)}
          style={styles.gapTop}
        />
      </Card3D>
    ) : data.settlement ? (
      <SettlementCard settlement={data.settlement} title="เงินที่พักไว้ ถูกแบ่งแล้ว" style={styles.block} />
    ) : settlementPolls < SETTLEMENT_POLL_MAX ? (
      <NoticeBanner tone="info" icon="hourglass" text="กำลังสรุปการแบ่งเงิน อีกสักครู่จะแสดงที่นี่" style={styles.block} />
    ) : (
      <NoticeBanner
        tone="info"
        icon="info"
        text="รับของเรียบร้อยแล้ว สรุปการแบ่งเงินยังไม่พร้อม ดึงหน้าจอลงเพื่อดูอีกครั้ง หรือดูรายละเอียดที่หน้าคำสั่งซื้อ"
        style={styles.block}
      />
    );

    return shell(
      <>
        {success && rider && <RiderHeartCard source={source} orderId={orderId} rider={rider} style={styles.block} />}
        {settlementBlock}
      </>,
      {
        title: subtitle,
        headerExtra: medal,
        bottom: (
          <StickyBar style={styles.bottomRow}>
            <Button3D title="กลับหน้าแรก" variant="secondary" size="lg" onPress={() => router.replace('/(tabs)' as never)} style={styles.flex} />
            <Button3D
              title={!success ? 'ดูคำสั่งซื้อ' : source === 'shop' ? 'รีวิวสินค้า' : 'ให้คะแนนร้าน'}
              icon={!success ? 'receipt' : 'storefront'}
              variant="navy"
              size="lg"
              onPress={goOrder}
              style={styles.flex}
            />
          </StickyBar>
        ),
      }
    );
  }

  // =====================================================
  // ระหว่างส่งมอบ (BuyerHandshake.png)
  // =====================================================

  const distance = data.job?.distance_to_dropoff_m ?? null;
  const arrived = (distance !== null && distance <= ARRIVED_M) || !!h.arrival_photo_at;
  const disputed = h.status === 'disputed';
  const pendingRelease = h.status === 'fallback_pending_release';
  const fallbackWaiting = h.status === 'fallback_waiting';
  const bothScanned = h.rider_confirmed && h.buyer_confirmed;
  const canScan = !h.buyer_confirmed && !disputed && !pendingRelease;
  const riderName = rider?.display_name || 'ไรเดอร์';
  const vehicle = rider ? vehicleText(rider) : null;

  const riderLine = [
    rider?.plate_masked ? `ทะเบียน ${rider.plate_masked}` : vehicle,
    distance !== null ? (distance < 1000 ? `ห่างจุดส่ง ${Math.round(distance)} ม.` : `ห่างจุดส่ง ${(distance / 1000).toFixed(1)} กม.`) : null,
  ]
    .filter(Boolean)
    .join(' · ');

  const step = (n: number, label: string, done: boolean, current: boolean) => (
    <View style={[styles.step, current && { backgroundColor: colors.card, borderColor: colors.border }]} accessible accessibilityLabel={`ขั้นที่ ${n} ${label}${done ? ' เสร็จแล้ว' : ''}`}>
      <View style={[styles.stepBadge, { backgroundColor: done ? colors.success : current ? colors.navyFill : colors.inset }]}>
        {done ? (
          <Icon name="check" size={14} color={colors.textOnAccent} weight="bold" />
        ) : (
          <Text style={[styles.stepNum, { color: current ? colors.goldLight : colors.textMuted }]}>{n}</Text>
        )}
      </View>
      <Text numberOfLines={2} style={[typography.caption, styles.flexShrink, { color: current || done ? colors.textStrong : colors.textMuted }]}>
        {label}
      </Text>
    </View>
  );

  const headerExtra = protectedScreen ? (
    <View style={styles.headerPill}>
      <Pill label="ป้องกันการแคปหน้าจอ" icon="shield-check" tone="gold" />
    </View>
  ) : undefined;

  return (
    <>
      {shell(
        <>
          {/* ---------- ไรเดอร์ ---------- */}
          <Card3D padding={spacing.lg} radius={22} style={styles.block}>
            <View style={styles.row}>
              <PersonAvatar uri={rider?.photo_url} name={riderName} size={58} />
              <View style={styles.flex}>
                <Text numberOfLines={1} style={[typography.h3, { color: colors.textStrong }]}>
                  {riderName} {arrived ? 'มาถึงแล้ว' : 'กำลังมาส่ง'}
                </Text>
                {!!riderLine && (
                  <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
                    {riderLine}
                  </Text>
                )}
                <View style={styles.pillRow}>
                  {arrived ? <Pill label="ถึงจุดส่ง" tone="success" icon="map-pin" /> : <Pill label="กำลังเดินทาง" tone="gold" icon="moped" />}
                </View>
              </View>
              {!!orderInfo.riderPhone && (
                <IconButton icon="phone" label={`โทรหา ${riderName}`} onPress={() => callPhone(orderInfo.riderPhone)} size={48} />
              )}
            </View>
          </Card3D>

          {/* ---------- ขั้นตอน ---------- */}
          <View style={styles.stepper}>
            {step(1, 'ไรเดอร์สแกน QR ของคุณ', h.rider_confirmed, !h.rider_confirmed)}
            <View style={[styles.stepLine, { backgroundColor: colors.border }]} />
            {step(2, 'คุณสแกน QR ของไรเดอร์', h.buyer_confirmed, h.rider_confirmed && !h.buyer_confirmed)}
          </View>

          {/* ---------- ทางสำรอง / ร้องเรียน ---------- */}
          {disputed && (
            <Card3D padding={spacing.lg} radius={22} style={styles.block}>
              <View style={styles.row}>
                <View style={[styles.stateIcon, { backgroundColor: colors.warningSoft }]}>
                  <Icon name="flag" size={24} color={colors.warning} weight="fill" />
                </View>
                <View style={styles.flex}>
                  <Text style={[typography.h3, { color: colors.textStrong }]}>แจ้งปัญหาแล้ว ทีมงานกำลังตรวจสอบ</Text>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>
                    {HANDOVER_DISPUTE_REASONS.find((r) => r.key === h.dispute_reason)?.label || 'แจ้งปัญหา'}
                    {h.disputed_at ? ` · ${formatThaiDateTime(h.disputed_at)}` : ''}
                  </Text>
                </View>
              </View>
              <Text style={[typography.bodySm, styles.gapTop, { color: colors.text }]}>
                เงินของคุณยังพักไว้ที่ระบบ ไม่โอนให้ร้านหรือไรเดอร์ จนกว่าทีมงานจะตัดสิน ผลจะแจ้งทางการแจ้งเตือน
              </Text>
            </Card3D>
          )}

          {pendingRelease && (
            <Card3D padding={spacing.lg} radius={22} style={styles.block}>
              <View style={styles.row}>
                <View style={[styles.stateIcon, { backgroundColor: colors.infoSoft }]}>
                  <Icon name="package" size={24} color={colors.info} weight="fill" />
                </View>
                <View style={styles.flex}>
                  <Text style={[typography.h3, { color: colors.textStrong }]}>ไรเดอร์วางของไว้ให้แล้ว</Text>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>
                    ถ่ายรูปยืนยันที่จุดส่ง{h.waited_photo_at ? ` เมื่อ ${timeText(h.waited_photo_at)} น.` : ''}
                  </Text>
                </View>
              </View>
              <Text style={[typography.bodySm, styles.gapTop, { color: colors.text }]}>
                ได้รับของแล้วกด "ได้รับของแล้ว" ระบบจะโอนเงินให้ร้านและไรเดอร์ทันที ถ้าไม่กด ระบบโอนให้อัตโนมัติ
                {h.auto_release_at ? ` ${formatThaiDateTime(h.auto_release_at)}` : ' ภายใน 24 ชั่วโมง'}
                {' '}ถ้าไม่ได้รับของ กดแจ้งปัญหาก่อนเวลานั้น
              </Text>
              {h.can_confirm_received && (
                <Button3D
                  title="ได้รับของแล้ว"
                  icon="check-circle"
                  variant="primary"
                  size="md"
                  fullWidth
                  loading={confirmBusy}
                  disabled={confirmBusy || disputeBusy}
                  onPress={confirmReceived}
                  style={styles.gapTop}
                />
              )}
              {h.can_dispute && (
                <Button3D
                  title="ไม่ได้รับของ แจ้งปัญหา"
                  icon="flag"
                  variant="danger"
                  size="md"
                  fullWidth
                  disabled={confirmBusy}
                  onPress={openDispute}
                  style={styles.gapTop}
                />
              )}
            </Card3D>
          )}

          {fallbackWaiting && (
            <Card3D padding={spacing.lg} radius={22} style={styles.block}>
              <View style={styles.row}>
                <View style={[styles.stateIcon, { backgroundColor: colors.warningSoft }]}>
                  <Icon name="hourglass" size={24} color={colors.warning} weight="fill" />
                </View>
                <View style={styles.flex}>
                  <Text style={[typography.h3, { color: colors.textStrong }]}>ไรเดอร์ถึงจุดส่งแล้ว กำลังรอคุณ</Text>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>
                    {h.wait_until ? `รอถึง ${timeText(h.wait_until)} น. ` : ''}ถ้าไม่ได้ออกมารับ ไรเดอร์จะวางของไว้ให้
                  </Text>
                </View>
              </View>
              {h.can_confirm_received && (
                <Button3D
                  title="ได้รับของแล้ว"
                  icon="check-circle"
                  variant="primary"
                  size="md"
                  fullWidth
                  loading={confirmBusy}
                  disabled={confirmBusy || disputeBusy}
                  onPress={confirmReceived}
                  style={styles.gapTop}
                />
              )}
            </Card3D>
          )}

          {/* ---------- QR ของฉัน + รหัส 6 หลัก ---------- */}
          {!disputed && !pendingRelease && (
            <Card3D padding={spacing.lg} radius={22} style={styles.block}>
              {bothScanned ? (
                <View style={styles.centerBox}>
                  <ActivityIndicator color={colors.gold} />
                  <Text style={[typography.h3, styles.centerText, { color: colors.textStrong }]}>สแกนครบทั้งสองฝ่ายแล้ว</Text>
                  <Text style={[typography.caption, styles.centerText, { color: colors.textMuted }]}>กำลังโอนเงินให้ร้านและไรเดอร์…</Text>
                </View>
              ) : h.rider_confirmed ? (
                <View style={styles.centerBox}>
                  <View style={[styles.doneIcon, { backgroundColor: colors.successSoft }]}>
                    <Icon name="check-circle" size={38} color={colors.success} weight="fill" />
                  </View>
                  <Text style={[typography.h3, styles.centerText, { color: colors.textStrong }]}>ไรเดอร์สแกน QR ของคุณแล้ว</Text>
                  <Text style={[typography.bodySm, styles.centerText, { color: colors.textMuted }]}>
                    ขั้นสุดท้าย: สแกน QR บนจอของไรเดอร์ เพื่อยืนยันว่าได้รับของแล้ว
                  </Text>
                </View>
              ) : (
                <>
                  <RotatingQr
                    token={h.qr_token}
                    expiresAt={h.qr_expires_at}
                    clockOffsetMs={data.clock_offset_ms}
                    onExpire={() => load('silent')}
                    caption="ยื่นหน้าจอนี้ให้ไรเดอร์สแกน"
                    size={196}
                  />
                  {!!h.code && (
                    <>
                      <View style={[styles.dashed, { borderColor: colors.border }]} />
                      <Text style={[typography.bodySm, styles.centerText, { color: colors.textMuted }]}>
                        กล้องไรเดอร์ใช้ไม่ได้? บอกรหัส 6 หลักนี้แทน
                      </Text>
                      <View style={styles.codeRow} accessible accessibilityLabel={`รหัสรับของ ${h.code.split('').join(' ')}`}>
                        {h.code.split('').map((digit, i) => (
                          <View key={i} style={[styles.codeTile, { backgroundColor: colors.inset, borderColor: colors.border }, i === 2 && styles.codeGap]}>
                            <Text style={[styles.codeDigit, { color: colors.textStrong }]}>{digit}</Text>
                          </View>
                        ))}
                      </View>
                    </>
                  )}
                </>
              )}
            </Card3D>
          )}

          {/* กล้องของฉันใช้ไม่ได้ → กรอกรหัส 6 หลักที่ไรเดอร์อ่านให้ (§A3) */}
          {canScan && (
            <Button3D
              title="กล้องใช้ไม่ได้? กรอกรหัสของไรเดอร์"
              icon="key"
              variant="ghost"
              size="sm"
              disabled={scanBusy}
              onPress={() => openScanner('code')}
              style={styles.codeLink}
            />
          )}

          <View style={[styles.note, { borderColor: colors.border }]}>
            <Icon name="info" size={18} color={colors.goldDeep} />
            <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>
              สแกนครบทั้งสองฝ่ายแล้ว เงินที่พักไว้จะถูกแบ่งให้ร้านและไรเดอร์ทันที ถ้าไม่ได้รับของ ห้ามให้ไรเดอร์สแกน แล้วกด "แจ้งปัญหา"
            </Text>
          </View>
        </>,
        {
          headerExtra,
          bottom:
            canScan || (h.can_dispute && !pendingRelease) ? (
              <StickyBar style={styles.bottomRow}>
                {canScan ? (
                  <Button3D
                    title={h.rider_confirmed ? 'สแกน QR ของไรเดอร์' : 'ถัดไป: สแกน QR ของไรเดอร์'}
                    icon="scan"
                    variant="navy"
                    size="lg"
                    onPress={() => openScanner('camera')}
                    style={styles.flex}
                  />
                ) : (
                  <View style={styles.flex}>
                    <Text style={[typography.bodySm, { color: colors.textMuted }]}>คุณสแกนแล้ว รอไรเดอร์สแกน QR ของคุณ</Text>
                  </View>
                )}
                {h.can_dispute && !pendingRelease && (
                  <IconButton icon="flag" label="แจ้งปัญหา ไม่ได้รับของ" onPress={openDispute} size={56} />
                )}
              </StickyBar>
            ) : undefined,
        }
      )}

      <QrScannerSheet
        visible={scanOpen}
        title="สแกน QR ของไรเดอร์"
        onClose={() => setScanOpen(false)}
        onScanned={onScanned}
        busy={scanBusy}
        hint="สแกน QR บนจอแอปของไรเดอร์ เมื่อได้รับของครบแล้วเท่านั้น"
        initialMode={scanMode}
        scanResetKey={scanResetKey}
        codeError={codeError}
        codeFallback={{
          length: 6,
          onSubmit: submitCode,
          label: 'กล้องใช้ไม่ได้? กรอกรหัสของไรเดอร์',
          hint: 'ให้ไรเดอร์เปิดหน้าส่งมอบในแอป แล้วอ่านรหัส 6 หลักใต้ QR ให้ฟัง กรอกเมื่อได้รับของครบแล้วเท่านั้น',
        }}
      />

      <FormSheet
        visible={disputeOpen}
        icon="flag"
        title="แจ้งปัญหาการรับของ"
        description="ทีมงานจะตรวจสอบ ระหว่างนี้เงินของคุณยังพักไว้ ไม่โอนให้ร้านหรือไรเดอร์"
        submitLabel="ส่งเรื่องให้ทีมงาน"
        submitVariant="danger"
        submitDisabled={!disputeReason}
        onSubmit={submitDispute}
        busy={disputeBusy}
        cancelLabel="ยกเลิก"
        onClose={() => setDisputeOpen(false)}
      >
        <View style={styles.reasonList} accessibilityRole="radiogroup">
          {HANDOVER_DISPUTE_REASONS.map((r) => {
            const selected = disputeReason === r.key;
            return (
              <Card3D
                key={r.key}
                onPress={() => setDisputeReason(r.key)}
                gradientBorder={selected}
                variant={selected ? 'raised' : 'flat'}
                shadow="sm"
                padding={spacing.md}
                radius={radii.lg}
                accessibilityRole="radio"
                accessibilityState={{ checked: selected }}
                accessibilityLabel={r.label}
              >
                <View style={styles.row}>
                  <RadioMark selected={selected} />
                  <View style={styles.flex}>
                    <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{r.label}</Text>
                    <Text style={[typography.caption, { color: colors.textMuted }]}>{r.hint}</Text>
                  </View>
                </View>
              </Card3D>
            );
          })}
        </View>
        <Field
          label={disputeReason === 'other' ? 'รายละเอียด (จำเป็น)' : 'รายละเอียดเพิ่มเติม (ไม่บังคับ)'}
          value={disputeNote}
          onChangeText={(t) => setDisputeNote(t.slice(0, 1000))}
          placeholder="เช่น ไรเดอร์ไม่ได้มาที่บ้าน / ได้ของไม่ครบ ขาดน้ำ 1 ขวด"
          multiline
          maxLength={1000}
        />
      </FormSheet>
    </>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  flexShrink: {
    flexShrink: 1,
  },
  sheet: {
    flex: 1,
    marginTop: -SHEET_RADIUS,
    borderTopLeftRadius: SHEET_RADIUS,
    borderTopRightRadius: SHEET_RADIUS,
    overflow: 'hidden',
  },
  content: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.xl,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
  },
  headerPill: {
    flexDirection: 'row',
    paddingHorizontal: spacing.screen,
    marginTop: spacing.md,
  },
  medalBlock: {
    alignItems: 'center',
    gap: spacing.xs,
    marginTop: spacing.lg,
    paddingHorizontal: spacing.screen,
  },
  medalRing: {
    width: 96,
    height: 96,
    borderRadius: 48,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm,
  },
  medal: {
    width: 74,
    height: 74,
    borderRadius: 37,
    alignItems: 'center',
    justifyContent: 'center',
  },
  block: {
    marginBottom: spacing.md,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  pillRow: {
    flexDirection: 'row',
    marginTop: spacing.xs,
  },
  centerText: {
    textAlign: 'center',
  },
  gapTop: {
    marginTop: spacing.md,
  },
  stepper: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    marginBottom: spacing.md,
  },
  step: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.pill,
    borderWidth: 1,
    borderColor: 'transparent',
    paddingVertical: spacing.xs,
    paddingHorizontal: spacing.xs,
  },
  stepBadge: {
    width: 28,
    height: 28,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepNum: {
    fontSize: 13,
    fontWeight: '700',
  },
  stepLine: {
    width: 14,
    height: 2,
    borderRadius: 1,
  },
  stateIcon: {
    width: 48,
    height: 48,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  centerBox: {
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.lg,
  },
  doneIcon: {
    width: 72,
    height: 72,
    borderRadius: 36,
    alignItems: 'center',
    justifyContent: 'center',
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
  note: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    paddingHorizontal: spacing.xs,
    paddingTop: spacing.xs,
  },
  codeLink: {
    alignSelf: 'center',
    marginBottom: spacing.sm,
  },
  bottomRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  reasonList: {
    gap: spacing.sm,
    marginTop: spacing.md,
  },
});
