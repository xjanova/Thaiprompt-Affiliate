/**
 * จ่ายด้วยเหรียญ TPIX — ใบเสนอยอดเดียว (GET /tpix/payments/{reference})
 *
 * เข้ามาจาก: หน้าชำระเงิน (?from=checkout&group=<checkout_id>) · หน้าคำสั่งซื้อ (?from=order) · เติมเงิน (?from=topup)
 *            · ตลาดสด (?from=taladsod — purpose fresh_market: ได้เหรียญแล้วระบบเติมกระเป๋าแล้วสั่งให้ → fresh_market_order_id)
 *            · จองบริการ (purpose service_booking — ได้เหรียญแล้วระบบชำระการจองจากกระเป๋าให้)
 *
 * หน้าตา (ธีมรอยัล): การ์ดหัวขอบทอง (เลขคำสั่งซื้อ + ยอดบาท + สถานะ) → การ์ดจ่าย (ยอดเหรียญตัวใหญ่ + คัดลอก
 *   · นับถอยหลัง · QR บนแผ่นขาว · กระเป๋ารับเงิน + คัดลอก · ปุ่มเปิดกระเป๋า/เปิดหน้าจ่ายบนเว็บ) → ช่องวางเลขธุรกรรม
 *   → ตั้งค่าเครือข่าย → รายการอื่นในชุดเดียวกัน (สั่งหลายร้าน) · สำเร็จ = เหรียญเครื่องหมายถูก
 *
 * กติกา
 *   - ต้องโอน "ยอดตรงทุกหลัก" (เลขท้าย 8 ตำแหน่งเฉพาะใบ) ไปยังกระเป๋ารับเงินบนเชน 4289 → คัดลอกด้วย amount_tpix_exact
 *   - เช็คสถานะทุก 5 วินาทีเฉพาะตอนหน้านี้เปิดอยู่ (server ตรวจบนเชนให้) + กลับจากแอปกระเป๋า = เช็คทันที
 *   - หมดเวลา → "ออกยอดใหม่" (POST /payment/order tpix) ต้องยืนยันก่อน (ถ้าโอนไปแล้วให้วางเลขธุรกรรมแทน)
 *   - ส่งเลขธุรกรรม: ตรวจรูปแบบ 0x + 64 hex ก่อนส่ง · กันกดซ้ำ
 *   - ทุก timer ล้างตอนออกจากหน้า · ไม่ setState หลังหน้าถูกถอด
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, AppState, Linking, Pressable, StyleSheet, View } from 'react-native';
import { router, useFocusEffect, useLocalSearchParams, useNavigation } from 'expo-router';
import * as Clipboard from 'expo-clipboard';
import { LinearGradient } from 'expo-linear-gradient';
import QRCode from 'react-native-qrcode-svg';
import { Text } from '@/components/ui/Text';
import {
  Button3D,
  Card3D,
  EmptyState,
  Icon,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  TpixCoin,
  formatBaht,
  resultHaptic,
  type IconName,
} from '@/components/ui';
import { Field, IconTile, NoticeBanner, openHttpsLink } from '@/components/shop';
import { payOrder } from '@/services/api/shopApi';
import { useTaladsodCartStore } from '@/stores/taladsodCartStore';
import {
  TPIX_ACTIVE_STATUSES,
  getTpixPayment,
  isTpixFulfilling,
  isTpixReference,
  isValidTxHash,
  normalizeTpixPayment,
  peekTpixGroup,
  peekTpixPayment,
  rememberTpixPayment,
  replaceTpixGroupReference,
  submitTpixTx,
  type TpixGroupEntry,
  type TpixPayment,
} from '@/services/api/tpixApi';
import { useTheme, LIGHT_THEME, radii, spacing, typography, withAlpha, type Tone } from '@/theme';

const POLL_MS = 5000;

type CopyKey = 'amount' | 'address' | 'rpc' | 'chain';

/** สีป้ายตามสถานะ */
const STATUS_TONE: Record<string, Tone> = {
  pending: 'warning',
  confirming: 'info',
  paid: 'success',
  credited_wallet: 'success',
  late: 'info',
  expired: 'danger',
  cancelled: 'neutral',
  failed: 'danger',
};

const STATUS_LABEL: Record<string, string> = {
  pending: 'รอโอนเหรียญ',
  confirming: 'กำลังยืนยันบนเชน',
  paid: 'ชำระแล้ว',
  credited_wallet: 'เติมเข้ากระเป๋าเงินแล้ว',
  late: 'โอนหลังหมดเวลา',
  expired: 'หมดเวลา',
  cancelled: 'ยกเลิก',
  failed: 'ตรวจธุรกรรมไม่ผ่าน',
};

const STATUS_ICON: Record<string, IconName> = {
  pending: 'hourglass',
  confirming: 'arrows-clockwise',
  paid: 'check-circle',
  credited_wallet: 'wallet',
  late: 'clock',
  expired: 'clock',
  cancelled: 'x-circle',
  failed: 'warning-circle',
};

/** คำอธิบายฐานราคาของใบเสนอยอด */
const basisLabel = (basis: string | null): string => {
  if (basis === 'twap') return 'คิดจากราคาเฉลี่ยบน TPIX DEX';
  if (basis === 'spot') return 'คิดจากราคาตลาดล่าสุดบน TPIX DEX';
  return 'คิดจากราคาจริงบน TPIX DEX';
};

/** ข้อความเริ่มต้นของผลการส่งเลขธุรกรรม (server ไม่ส่งข้อความไทยมา) */
const SUBMIT_FALLBACK: Record<string, string> = {
  paid: 'ได้รับเหรียญครบแล้ว ชำระเงินเรียบร้อย',
  confirming: 'พบธุรกรรมแล้ว กำลังรอการยืนยันบนเชน',
  pending: 'ยังไม่พบธุรกรรมนี้บนเชน รอสักครู่แล้วลองใหม่นะ',
  credited_wallet: 'ระบบเติมมูลค่าเหรียญเข้ากระเป๋าเงินของคุณแล้ว',
  late: 'ได้รับเหรียญหลังหมดเวลา ระบบจะคิดมูลค่าตามราคาปัจจุบันแล้วเติมเข้ากระเป๋าเงินให้',
};

const mmss = (seconds: number): string => `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;

/** ย่อที่อยู่กระเป๋า 0x1234…abcd (ใช้กับ accessibility) */
const shortAddress = (address: string): string => `${address.slice(0, 6)}…${address.slice(-4)}`;

/** เวลาหมดอายุ (ms ของเครื่อง) จาก seconds_left ของ server — ไม่พึ่งนาฬิกาเครื่องที่อาจเพี้ยน */
const deadlineOf = (p: TpixPayment | null): number | null => {
  if (!p) return null;
  if (p.seconds_left !== null) return Date.now() + p.seconds_left * 1000;
  if (p.expires_at) {
    const t = new Date(p.expires_at).getTime();
    return Number.isNaN(t) ? null : t;
  }
  return null;
};

export default function TpixPayScreen() {
  const params = useLocalSearchParams<{ reference?: string; group?: string; from?: string }>();
  const reference = String(params.reference || '').trim().toUpperCase();
  const groupId = typeof params.group === 'string' && params.group ? params.group : null;
  const from = typeof params.from === 'string' ? params.from : '';
  const navigation = useNavigation();
  const { colors, gradients } = useTheme();

  const validReference = isTpixReference(reference);
  const [payment, setPayment] = useState<TpixPayment | null>(() => peekTpixPayment(reference));
  const [loading, setLoading] = useState(() => validReference && !peekTpixPayment(reference));
  const [loadError, setLoadError] = useState<string | null>(null);
  const [netIssue, setNetIssue] = useState(false);
  const [now, setNow] = useState(() => Date.now());
  const [deadline, setDeadline] = useState<number | null>(() => deadlineOf(peekTpixPayment(reference)));
  const [txInput, setTxInput] = useState('');
  const [txError, setTxError] = useState<string | null>(null);
  const [submitNote, setSubmitNote] = useState<{ tone: Tone; text: string } | null>(null);
  const [copied, setCopied] = useState<CopyKey | null>(null);
  const [group, setGroup] = useState<TpixGroupEntry[]>(() => peekTpixGroup(groupId));

  const mountedRef = useRef(true);
  const pollBusyRef = useRef(false);
  const submitBusyRef = useRef(false);
  const renewBusyRef = useRef(false);
  const leavingRef = useRef(false);
  const prevStatusRef = useRef<string | null>(payment?.status ?? null);
  const copyTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      if (copyTimerRef.current) clearTimeout(copyTimerRef.current);
    };
  }, []);

  // =====================================================
  // โหลด / เช็คสถานะ
  // =====================================================

  /** ใช้ใบเสนอยอดล่าสุด + ตั้งเวลาหมดอายุจาก seconds_left ของ server (ไม่พึ่งนาฬิกาเครื่อง) */
  const applyPayment = useCallback((next: TpixPayment) => {
    rememberTpixPayment(next);
    setPayment(next);
    setDeadline(deadlineOf(next));
    setNow(Date.now());

    // เพิ่งจ่ายสำเร็จ → สั่นแจ้ง
    const prev = prevStatusRef.current;
    if (prev !== next.status && (next.status === 'paid' || next.status === 'credited_wallet') && prev !== null) {
      resultHaptic('success');
    }
    prevStatusRef.current = next.status;
  }, []);

  const poll = useCallback(
    async (mode: 'initial' | 'silent') => {
      // รหัสอ้างอิงผิดรูปแบบ → หน้าแสดง "ไม่พบรายการ" เอง (ไม่ยิง API)
      if (!isTpixReference(reference) || pollBusyRef.current) return;
      pollBusyRef.current = true;
      try {
        const res = await getTpixPayment(reference);
        if (!mountedRef.current) return;
        if (res.success) {
          applyPayment(res.data);
          setNetIssue(false);
          setLoadError(null);
        } else if (mode === 'initial' && !peekTpixPayment(reference)) {
          setLoadError(res.message);
        } else {
          // มีข้อมูลเดิมอยู่แล้ว → แสดงต่อ แค่บอกว่ากำลังลองใหม่
          setNetIssue(true);
        }
      } finally {
        pollBusyRef.current = false;
        if (mountedRef.current) setLoading(false);
      }
    },
    [reference, applyPayment]
  );

  // เปิดหน้า → โหลดสถานะล่าสุดทุกครั้ง แม้มีแคชจากหน้าก่อน (สถานะอาจเปลี่ยนแล้ว)
  // (ออกยอดใหม่ / สลับร้าน = router.replace เปิดหน้าใหม่ของใบนั้น)
  useEffect(() => {
    poll('initial');
  }, [poll]);

  const status = payment?.status ?? 'pending';
  // ตลาดสด/จองบริการ: ได้เหรียญแล้วแต่ระบบยังสั่งไม่เสร็จ → เช็คต่อจนรู้ผล
  const active = !!payment && (TPIX_ACTIVE_STATUSES.includes(status) || isTpixFulfilling(payment));
  const isFreshMarket = payment?.purpose === 'fresh_market';
  const isBooking = payment?.purpose === 'service_booking';

  // เช็คสถานะทุก 5 วินาทีระหว่างเปิดหน้านี้ + กลับจากแอปกระเป๋า = เช็คทันที
  useFocusEffect(
    useCallback(() => {
      if (!active) return undefined;
      const timer = setInterval(() => poll('silent'), POLL_MS);
      const sub = AppState.addEventListener('change', (state) => {
        if (state === 'active') poll('silent');
      });
      return () => {
        clearInterval(timer);
        sub.remove();
      };
    }, [active, poll])
  );

  // นับถอยหลัง (เฉพาะตอนรอโอน)
  useEffect(() => {
    if (status !== 'pending' || deadline === null) return undefined;
    const timer = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(timer);
  }, [status, deadline]);

  const secondsLeft = deadline !== null ? Math.max(0, Math.floor((deadline - now) / 1000)) : null;
  const timeUp = status === 'pending' && secondsLeft === 0;

  // หมดเวลาตามนาฬิกา → ถาม server ทันทีหนึ่งครั้ง (อาจเพิ่งพบธุรกรรม)
  useEffect(() => {
    if (timeUp) poll('silent');
  }, [timeUp, poll]);

  // ของที่ copy แล้วขึ้น "คัดลอกแล้ว" 2 วินาที
  const copy = async (key: CopyKey, value: string | null | undefined) => {
    if (!value) return;
    try {
      await Clipboard.setStringAsync(value);
      resultHaptic('success');
      if (!mountedRef.current) return;
      setCopied(key);
      if (copyTimerRef.current) clearTimeout(copyTimerRef.current);
      copyTimerRef.current = setTimeout(() => {
        if (mountedRef.current) setCopied(null);
      }, 2000);
    } catch {
      // คัดลอกไม่ได้ก็ไม่เป็นไร
    }
  };

  // =====================================================
  // ออกจากหน้า
  // =====================================================

  const exitTarget = useMemo((): string => {
    if (payment?.purpose === 'wallet_topup' || from === 'topup') return '/(tabs)/wallet';
    if (payment?.purpose === 'fresh_market') {
      return payment.fresh_market_order_id ? `/taladsod/order/${payment.fresh_market_order_id}` : '/taladsod/orders';
    }
    if (payment?.purpose === 'service_booking') return '/(tabs)/wallet';
    if (group.length > 1) return '/(tabs)/orders';
    if (payment?.order_id) return `/order/${payment.order_id}`;
    return '/(tabs)/orders';
  }, [payment?.purpose, payment?.order_id, payment?.fresh_market_order_id, from, group.length]);

  const goExit = useCallback(() => {
    leavingRef.current = true;
    // เปิดมาจากหน้าคำสั่งซื้อ → ย้อนกลับไปหน้าเดิม (ไม่ซ้อนหน้าคำสั่งซื้อซ้ำ)
    if (from === 'order' && navigation.canGoBack()) {
      router.back();
      return;
    }
    router.replace(exitTarget as never);
  }, [exitTarget, from, navigation]);

  /** ไปหน้าคำสั่งซื้อ — มาจากหน้าคำสั่งซื้อเดียวกัน = ย้อนกลับ (หน้านั้นโหลดสถานะใหม่เองตอนกลับมา) */
  const goOrder = useCallback(
    (orderId: number) => {
      leavingRef.current = true;
      if (from === 'order' && navigation.canGoBack()) {
        router.back();
        return;
      }
      router.replace(`/order/${orderId}` as never);
    },
    [from, navigation]
  );

  const goBack = useCallback(() => {
    // มาจากหน้าชำระเงิน (ตะกร้าว่างแล้ว) → ไปหน้าคำสั่งซื้อแทนการย้อนกลับ
    if (from === 'checkout' || !navigation.canGoBack()) {
      goExit();
      return;
    }
    leavingRef.current = true;
    router.back();
  }, [from, navigation, goExit]);

  // ปุ่มย้อนกลับของเครื่อง (Android) จากหน้าชำระเงิน → ไปหน้าคำสั่งซื้อ
  useEffect(() => {
    if (from !== 'checkout') return undefined;
    const unsubscribe = navigation.addListener('beforeRemove', (event: any) => {
      if (leavingRef.current) return;
      event.preventDefault();
      goExit();
    });
    return unsubscribe;
  }, [navigation, from, goExit]);

  /** สลับไปจ่ายใบอื่นในชุดเดียวกัน (สั่งหลายร้าน) */
  const openEntry = (entry: TpixGroupEntry) => {
    if (entry.reference === reference) return;
    leavingRef.current = true;
    if (!entry.reference) {
      router.replace(`/order/${entry.order_id}` as never);
      return;
    }
    const qs = [groupId ? `group=${encodeURIComponent(groupId)}` : null, from ? `from=${encodeURIComponent(from)}` : null]
      .filter(Boolean)
      .join('&');
    router.replace(`/tpix-pay/${entry.reference}${qs ? `?${qs}` : ''}` as never);
  };

  // =====================================================
  // การกระทำ
  // =====================================================

  const openWallet = async () => {
    if (!payment?.eip681) {
      Alert.alert('เปิดกระเป๋าไม่ได้', 'คัดลอกที่อยู่กระเป๋ารับเงินและยอดเหรียญไปโอนเองได้เลยนะ');
      return;
    }
    try {
      await Linking.openURL(payment.eip681);
    } catch {
      Alert.alert(
        'ไม่พบแอปกระเป๋าที่รองรับ',
        'ติดตั้ง MetaMask หรือ Trust Wallet แล้วลองใหม่ หรือคัดลอกที่อยู่กับยอดเหรียญไปโอนเองได้'
      );
    }
  };

  const openPayPage = () => openHttpsLink(payment?.pay_url, 'หน้าจ่าย TPIX');

  const pasteTx = async () => {
    try {
      const text = (await Clipboard.getStringAsync()).trim();
      if (!mountedRef.current) return;
      if (!text) {
        setTxError('ยังไม่มีข้อความที่คัดลอกไว้');
        return;
      }
      setTxInput(text.slice(0, 100));
      setTxError(isValidTxHash(text) ? null : 'ข้อความที่วางไม่ใช่เลขธุรกรรม ตรวจอีกครั้งนะ');
    } catch {
      // อ่านคลิปบอร์ดไม่ได้ → ให้พิมพ์/วางเองในช่อง
    }
  };

  const submitTx = async () => {
    if (submitBusyRef.current || !payment) return;
    const hash = txInput.trim();
    if (!isValidTxHash(hash)) {
      setTxError('เลขธุรกรรมต้องขึ้นต้นด้วย 0x ตามด้วยตัวเลข/ตัวอักษร a-f อีก 64 ตัว');
      resultHaptic('error');
      return;
    }
    submitBusyRef.current = true;
    setTxError(null);
    setSubmitNote(null);
    try {
      const res = await submitTpixTx(payment.reference, hash);
      if (!mountedRef.current) return;
      if (res.success) {
        if (res.data.payment) applyPayment(res.data.payment);
        const result = res.data.result;
        const tone: Tone =
          result === 'paid' || result === 'credited_wallet' ? 'success' : result === 'pending' ? 'warning' : 'info';
        setSubmitNote({ tone, text: res.data.message || SUBMIT_FALLBACK[result] || 'ส่งเลขธุรกรรมแล้ว ระบบกำลังตรวจสอบ' });
        if (result !== 'pending') setTxInput('');
        resultHaptic(tone === 'warning' ? 'warning' : 'success');
      } else {
        const latest = normalizeTpixPayment(res.data);
        if (latest) applyPayment(latest);
        setTxError(res.message);
        resultHaptic('error');
      }
    } finally {
      submitBusyRef.current = false;
    }
  };

  /** ออกยอดใหม่หลังหมดเวลา (ออเดอร์) / กลับไปเริ่มเติมเงินใหม่ (เติมเงิน) */
  const renew = async () => {
    if (!payment || renewBusyRef.current) return;
    // ตลาดสด: กลับไปหน้าสั่งซื้อของร้านนี้ (ตะกร้ายังอยู่) แล้วกดจ่ายใหม่ = ยอดตามราคาปัจจุบัน
    if (payment.purpose === 'fresh_market') {
      leavingRef.current = true;
      router.replace(
        (payment.fresh_market_seller_id ? `/taladsod/checkout?seller_id=${payment.fresh_market_seller_id}` : '/taladsod/cart') as never
      );
      return;
    }
    if (payment.purpose === 'service_booking') {
      Alert.alert('ยอดนี้หมดเวลาแล้ว', 'เปิดการจองอีกครั้งจากหน้าเว็บเพื่อออกยอดใหม่ หรือชำระด้วยกระเป๋าเงิน');
      return;
    }
    if (payment.purpose === 'wallet_topup' || !payment.order_id) {
      leavingRef.current = true;
      router.replace('/wallet-topup' as never);
      return;
    }
    const orderId = payment.order_id;
    renewBusyRef.current = true;
    try {
      const res = await payOrder(orderId, 'tpix');
      if (!mountedRef.current) return;
      if (!res.success) {
        resultHaptic('error');
        if (res.code === 'ALREADY_PAID') {
          Alert.alert('คำสั่งซื้อนี้ชำระแล้ว', 'ไปดูรายละเอียดที่หน้าคำสั่งซื้อได้เลย', [
            { text: 'ดูคำสั่งซื้อ', onPress: () => goOrder(orderId) },
          ]);
          return;
        }
        Alert.alert('ออกยอดใหม่ไม่สำเร็จ', res.message);
        return;
      }
      const next = normalizeTpixPayment(res.data?.tpix);
      if (!next) {
        Alert.alert('ออกยอดใหม่ไม่สำเร็จ', 'ลองใหม่อีกครั้ง หรือเลือกวิธีจ่ายอื่นที่หน้าคำสั่งซื้อนะ');
        return;
      }
      resultHaptic('success');
      rememberTpixPayment(next);
      replaceTpixGroupReference(groupId, orderId, next.reference);
      setGroup(peekTpixGroup(groupId));
      setSubmitNote(null);
      setTxError(null);
      if (next.reference === payment.reference) {
        applyPayment(next);
        return;
      }
      leavingRef.current = true;
      const qs = [groupId ? `group=${encodeURIComponent(groupId)}` : null, from ? `from=${encodeURIComponent(from)}` : null]
        .filter(Boolean)
        .join('&');
      router.replace(`/tpix-pay/${next.reference}${qs ? `?${qs}` : ''}` as never);
    } finally {
      renewBusyRef.current = false;
    }
  };

  const confirmRenew = () => {
    if (payment?.purpose === 'wallet_topup' || payment?.purpose === 'fresh_market' || !payment?.order_id) {
      renew();
      return;
    }
    Alert.alert(
      'ออกยอดใหม่?',
      'ระบบจะคิดยอดเหรียญใหม่ตามราคาปัจจุบัน\nถ้าโอนเหรียญไปแล้ว อย่าเพิ่งกด ให้วางเลขธุรกรรมให้ระบบตรวจแทน',
      [
        { text: 'ยังก่อน', style: 'cancel' },
        { text: 'ออกยอดใหม่', onPress: () => renew() },
      ]
    );
  };

  // =====================================================
  // render
  // =====================================================

  if (loading && !payment) {
    return (
      <Screen title="จ่ายด้วยเหรียญ TPIX" scroll={false} onBack={goBack}>
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </Screen>
    );
  }

  if (!payment) {
    return (
      <Screen title="จ่ายด้วยเหรียญ TPIX" scroll={false} onBack={goBack}>
        {validReference ? (
          <EmptyState
            variant="error"
            message={loadError || 'โหลดรายการชำระเงินไม่สำเร็จ'}
            actionLabel="ลองใหม่"
            onAction={() => poll('initial')}
            secondaryActionLabel="ไปหน้าคำสั่งซื้อ"
            onSecondaryAction={goExit}
          />
        ) : (
          <EmptyState
            icon="magnifying-glass"
            title="ไม่พบรายการชำระเงินนี้"
            message="ลิงก์อาจไม่ถูกต้อง เปิดจากหน้าคำสั่งซื้ออีกครั้งนะ"
            actionLabel="ไปหน้าคำสั่งซื้อ"
            onAction={goExit}
          />
        )}
      </Screen>
    );
  }

  const isTopup = payment.purpose === 'wallet_topup';
  const checkoutLabel = isFreshMarket || isBooking ? payment.checkout_title || (isFreshMarket ? 'ตลาดสด' : 'จองบริการ') : null;
  const shownStatus = timeUp ? 'expired' : status;
  const statusLabel = timeUp ? 'หมดเวลา' : payment.status_label || STATUS_LABEL[status] || status;
  const canPay = status === 'pending' && !timeUp;
  const showTxInput = status === 'pending' || status === 'expired' || status === 'failed';
  const settled = status === 'paid' || status === 'credited_wallet';
  const lowTime = canPay && secondsLeft !== null && secondsLeft <= 120;
  const progress = Math.min(1, payment.confirmations / Math.max(1, payment.confirmations_required));

  // ---------- การ์ดหัว ----------
  const header = (
    <Card3D gradientBorder shadow="lg" padding={spacing.lg} style={styles.block}>
      <View style={styles.rowBetween}>
        <View style={[styles.coinTile, { backgroundColor: colors.goldSoft }]}>
          <TpixCoin size={30} />
        </View>
        <View style={styles.flex}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>
            {isTopup
              ? 'เติมเงินเข้ากระเป๋า'
              : checkoutLabel
                ? checkoutLabel
                : payment.order_number
                  ? `คำสั่งซื้อ ${payment.order_number}`
                  : 'คำสั่งซื้อ'}
          </Text>
          <PriceText amount={payment.amount_thb} decimals={2} size="lg" tone="strong" />
        </View>
        <Pill label={statusLabel} tone={STATUS_TONE[shownStatus] || 'neutral'} icon={STATUS_ICON[shownStatus]} />
      </View>
    </Card3D>
  );

  // ---------- ตลาดสด / จองบริการ: ได้เหรียญแล้ว → ผลการสั่งด้วยกระเป๋าเงิน ----------
  if (settled && (isFreshMarket || isBooking)) {
    const fulfil = payment.fulfilment_status;
    const placed = fulfil === 'placed' || (status === 'paid' && fulfil !== 'failed');
    const placing = fulfil === 'pending';
    const title = placing
      ? 'ได้รับเหรียญแล้ว กำลังสั่งให้'
      : placed
        ? isFreshMarket
          ? 'สั่งซื้อสำเร็จ'
          : 'ชำระการจองสำเร็จ'
        : 'เงินอยู่ในกระเป๋าเงินของคุณ';
    const message = placing
      ? 'เติมมูลค่าเข้ากระเป๋าเงินแล้ว ระบบกำลังสั่งจากกระเป๋าให้ รอสักครู่นะ'
      : payment.fulfilment_message ||
        (placed
          ? 'ร้านได้รับคำสั่งซื้อแล้ว เงินพักไว้จนคุณได้รับของ'
          : 'ระบบได้รับเหรียญแต่สั่งให้ไม่สำเร็จ ยอดเงินเติมเข้ากระเป๋าเงินของคุณแล้ว สั่งใหม่ด้วยกระเป๋าเงินได้ทันที');
    const tone = placed ? 'success' : placing ? 'info' : 'warning';
    const goFreshOrder = () => {
      if (!payment.fresh_market_order_id) return;
      leavingRef.current = true;
      useTaladsodCartStore.getState().refresh().catch(() => {});
      router.replace(`/taladsod/order/${payment.fresh_market_order_id}?placed=1` as never);
    };
    return (
      <Screen title="จ่ายด้วยเหรียญ TPIX" subtitle={checkoutLabel || undefined} onBack={goBack}>
        <Card3D gradientBorder shadow="lg" padding={spacing.xl} style={styles.block}>
          <View style={[styles.medalOuter, { backgroundColor: tone === 'success' ? colors.successSoft : colors.goldSoft }]}>
            <LinearGradient
              colors={tone === 'success' ? gradients.success : gradients.primary}
              start={{ x: 0, y: 0 }}
              end={{ x: 0, y: 1 }}
              style={styles.medalInner}
            >
              {placing ? (
                <ActivityIndicator color={colors.textOnGold} />
              ) : (
                <Icon
                  name={placed ? 'check' : 'wallet'}
                  size={32}
                  color={placed ? colors.textOnAccent : colors.textOnGold}
                  weight="bold"
                />
              )}
            </LinearGradient>
          </View>
          <Text style={[typography.serif, styles.center, { color: colors.textStrong }]}>{title}</Text>
          <Text style={[typography.body, styles.center, styles.gapTopSm, { color: colors.textMuted }]}>{message}</Text>
          <View style={[styles.doneBox, { backgroundColor: colors.inset, borderColor: colors.border }]}>
            <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>ยอดเหรียญที่ได้รับ</Text>
            <View style={styles.doneAmountRow}>
              <TpixCoin size={20} />
              <Text selectable style={[typography.h2, styles.mono, { color: colors.goldDeep }]}>
                {payment.amount_tpix} TPIX
              </Text>
            </View>
            <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>
              มูลค่า {formatBaht(payment.amount_thb, { decimals: 2 })}
            </Text>
          </View>
          {!!payment.explorer_tx_url && (
            <Button3D
              title="ดูธุรกรรมบนเชน"
              icon="arrow-square-out"
              variant="ghost"
              size="sm"
              onPress={() => openHttpsLink(payment.explorer_tx_url, 'ธุรกรรม TPIX')}
              style={styles.gapTop}
            />
          )}
        </Card3D>

        {placed && isFreshMarket && !!payment.fresh_market_order_id && (
          <Button3D title="ดูออเดอร์ตลาดสด" icon="receipt" size="lg" fullWidth onPress={goFreshOrder} />
        )}
        {!placed && !placing && (
          <>
            <Button3D
              title="ดูกระเป๋าเงิน"
              icon="wallet"
              size="lg"
              fullWidth
              onPress={() => {
                leavingRef.current = true;
                router.replace('/(tabs)/wallet' as never);
              }}
            />
            {isFreshMarket && (
              <Button3D
                title="กลับไปสั่งใหม่ด้วยกระเป๋าเงิน"
                icon="shopping-cart"
                variant="secondary"
                size="lg"
                fullWidth
                onPress={() => {
                  leavingRef.current = true;
                  useTaladsodCartStore.getState().refresh().catch(() => {});
                  router.replace('/taladsod/cart' as never);
                }}
                style={styles.gapTop}
              />
            )}
          </>
        )}
        {placed && isBooking && (
          <NoticeBanner tone="success" icon="calendar" text="ระบบกำลังหาผู้ให้บริการให้ ติดตามการจองได้ที่หน้าเว็บ" style={styles.block} />
        )}
      </Screen>
    );
  }

  // ---------- สำเร็จ ----------
  if (settled) {
    const walletCredit = status === 'credited_wallet';
    const title = isTopup ? 'เติมเงินสำเร็จ' : walletCredit ? 'เติมเข้ากระเป๋าเงินแทน' : 'ชำระเงินเรียบร้อย';
    const message = isTopup
      ? `ได้รับเหรียญแล้ว เติม ${formatBaht(payment.amount_thb, { decimals: 2 })} เข้ากระเป๋าเงินให้แล้ว`
      : walletCredit
        ? 'ระบบได้รับเหรียญของคุณ แต่ใช้ปิดคำสั่งซื้อนี้ไม่ได้ (เช่น โอนหลังหมดเวลา หรือคำสั่งซื้อเปลี่ยนไป) จึงคิดมูลค่าเป็นเงินบาทตามราคาปัจจุบันแล้วเติมเข้ากระเป๋าเงินของคุณ ใช้จ่ายคำสั่งซื้อนี้ด้วยกระเป๋าเงินได้เลย'
        : 'ร้านได้รับคำสั่งซื้อแล้ว ติดตามสถานะได้ที่หน้าคำสั่งซื้อ';
    return (
      <Screen title="จ่ายด้วยเหรียญ TPIX" subtitle={payment.order_number || undefined} onBack={goBack}>
        <Card3D gradientBorder shadow="lg" padding={spacing.xl} style={styles.block}>
          <View style={[styles.medalOuter, { backgroundColor: walletCredit && !isTopup ? colors.goldSoft : colors.successSoft }]}>
            <LinearGradient
              colors={walletCredit && !isTopup ? gradients.primary : gradients.success}
              start={{ x: 0, y: 0 }}
              end={{ x: 0, y: 1 }}
              style={styles.medalInner}
            >
              <Icon
                name={walletCredit && !isTopup ? 'wallet' : 'check'}
                size={32}
                color={walletCredit && !isTopup ? colors.textOnGold : colors.textOnAccent}
                weight="bold"
              />
            </LinearGradient>
          </View>
          <Text style={[typography.serif, styles.center, { color: colors.textStrong }]}>{title}</Text>
          <Text style={[typography.body, styles.center, styles.gapTopSm, { color: colors.textMuted }]}>{message}</Text>
          <View style={[styles.doneBox, { backgroundColor: colors.inset, borderColor: colors.border }]}>
            <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>ยอดเหรียญที่ได้รับ</Text>
            <View style={styles.doneAmountRow}>
              <TpixCoin size={20} />
              <Text selectable style={[typography.h2, styles.mono, { color: colors.goldDeep }]}>
                {payment.amount_tpix} TPIX
              </Text>
            </View>
            <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>
              มูลค่า {formatBaht(payment.amount_thb, { decimals: 2 })}
            </Text>
          </View>
          {!!payment.explorer_tx_url && (
            <Button3D
              title="ดูธุรกรรมบนเชน"
              icon="arrow-square-out"
              variant="ghost"
              size="sm"
              onPress={() => openHttpsLink(payment.explorer_tx_url, 'ธุรกรรม TPIX')}
              style={styles.gapTop}
            />
          )}
        </Card3D>

        {isTopup || walletCredit ? (
          <Button3D
            title="ดูกระเป๋าเงิน"
            icon="wallet"
            size="lg"
            fullWidth
            onPress={() => {
              leavingRef.current = true;
              router.replace('/(tabs)/wallet' as never);
            }}
          />
        ) : null}
        {!isTopup && !!payment.order_id && (
          <Button3D
            title={walletCredit ? 'ไปจ่ายคำสั่งซื้อด้วยกระเป๋าเงิน' : 'ดูคำสั่งซื้อ'}
            icon="receipt"
            variant={walletCredit ? 'secondary' : 'primary'}
            size="lg"
            fullWidth
            onPress={() => goOrder(payment.order_id as number)}
            style={styles.gapTop}
          />
        )}
        {group.length > 1 && <GroupList group={group} current={reference} onOpen={openEntry} />}
      </Screen>
    );
  }

  return (
    <Screen
      title="จ่ายด้วยเหรียญ TPIX"
      subtitle={isTopup ? 'เติมเงินเข้ากระเป๋า' : payment.order_number || undefined}
      onBack={goBack}
      refreshing={false}
      onRefresh={() => poll('silent')}
    >
      {header}

      {netIssue && (
        <NoticeBanner tone="warning" icon="wifi-slash" text="เชื่อมต่อไม่ได้ กำลังลองเช็คสถานะใหม่อัตโนมัติ" style={styles.block} />
      )}

      {/* ---------- รอโอน: ยอด + QR + กระเป๋ารับเงิน ---------- */}
      {canPay && (
        <Card3D gradientBorder padding={spacing.lg} style={styles.block}>
          <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>ยอดเหรียญที่ต้องโอน (ตรงทุกหลัก)</Text>
          <View style={styles.amountRow}>
            <TpixCoin size={26} />
            <Text
              selectable
              adjustsFontSizeToFit
              numberOfLines={1}
              style={[typography.money, styles.mono, styles.amountText, { color: colors.goldDeep }]}
              accessibilityLabel={`ยอดที่ต้องโอน ${payment.amount_tpix_exact} เหรียญ TPIX`}
            >
              {payment.amount_tpix}
            </Text>
            <Text style={[typography.h3, { color: colors.goldDeep }]}>TPIX</Text>
          </View>
          <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>
            {[payment.rate_display ? `1 TPIX = ${payment.rate_display}` : null, basisLabel(payment.rate_basis)].filter(Boolean).join(' · ')}
          </Text>
          {payment.fixed_price_lines > 0 && (
            <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>
              มีสินค้าราคาเหรียญคงที่ {payment.fixed_price_lines} รายการ (ไม่ขึ้นกับราคาตลาด)
            </Text>
          )}

          <Button3D
            title={copied === 'amount' ? 'คัดลอกยอดแล้ว' : 'คัดลอกยอดเหรียญ'}
            icon={copied === 'amount' ? 'check-circle' : 'copy'}
            variant="secondary"
            size="sm"
            onPress={() => copy('amount', payment.amount_tpix_exact)}
            accessibilityLabel={`คัดลอกยอด ${payment.amount_tpix_exact} TPIX`}
            style={styles.copyAmount}
          />

          {secondsLeft !== null && (
            <View style={[styles.timerRow, { backgroundColor: lowTime ? colors.dangerSoft : colors.warningSoft }]}>
              <Icon name="timer" size={15} color={lowTime ? colors.danger : colors.warning} />
              <Text style={[typography.caption, { color: colors.text }]}>
                ยอดนี้ใช้ได้อีก{' '}
                <Text style={[styles.timerValue, { color: lowTime ? colors.danger : colors.warning }]}>{mmss(secondsLeft)}</Text> นาที
              </Text>
            </View>
          )}

          {/* QR ต้องอยู่บนพื้นขาวเสมอ (แอปกระเป๋าสแกนติดทั้งโหมดมืด/สว่าง) */}
          {payment.eip681 ? (
            <View style={[styles.qrFrame, { backgroundColor: LIGHT_THEME.colors.card, borderColor: withAlpha(colors.gold, 0.55) }]}>
              <QRCode value={payment.eip681} size={208} color={LIGHT_THEME.colors.navyFill} backgroundColor={LIGHT_THEME.colors.card} ecl="M" />
            </View>
          ) : (
            <NoticeBanner
              tone="info"
              icon="qr-code"
              text="ใบนี้ไม่มี QR ให้สแกน คัดลอกที่อยู่และยอดเหรียญไปโอนเอง หรือเปิดหน้าจ่ายบนเว็บ"
              style={styles.gapTop}
            />
          )}
          <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>
            สแกนด้วย MetaMask / Trust Wallet หรือกระเป๋าที่รองรับลิงก์ ethereum
          </Text>

          <View style={[styles.addressBox, { backgroundColor: colors.inset, borderColor: colors.border }]}>
            <View style={styles.flex}>
              <Text style={[typography.caption, { color: colors.textMuted }]}>
                กระเป๋ารับเงิน · {payment.chain_name || 'TPIX Chain'} ({payment.chain_id})
              </Text>
              <Text selectable style={[typography.bodySm, styles.mono, { color: colors.textStrong }]}>
                {payment.receive_address}
              </Text>
            </View>
            <Pressable
              onPress={() => copy('address', payment.receive_address)}
              hitSlop={8}
              accessibilityRole="button"
              accessibilityLabel={`คัดลอกที่อยู่กระเป๋ารับเงิน ${shortAddress(payment.receive_address)}`}
              style={({ pressed }) => [styles.copyChip, { backgroundColor: colors.goldSoft, opacity: pressed ? 0.7 : 1 }]}
            >
              <Icon name={copied === 'address' ? 'check-circle' : 'copy'} size={14} color={colors.goldDeep} weight="bold" />
              <Text style={[typography.caption, styles.bold, { color: colors.goldDeep }]}>
                {copied === 'address' ? 'คัดลอกแล้ว' : 'คัดลอก'}
              </Text>
            </Pressable>
          </View>

          <Button3D title="เปิดกระเป๋า" icon="wallet" size="lg" fullWidth onPress={openWallet} style={styles.gapTop} />
          {!!payment.pay_url && (
            <Button3D
              title="เปิดหน้าจ่ายในเบราว์เซอร์"
              icon="globe"
              variant="secondary"
              size="md"
              fullWidth
              onPress={openPayPage}
              style={styles.gapTopSm}
            />
          )}

          <View style={styles.hintRow}>
            <Icon name="shield-check" size={15} color={colors.textMuted} />
            <Text style={[typography.caption, styles.hintText, { color: colors.textMuted }]}>
              โอนเหรียญ TPIX ยอดนี้ให้ตรงทุกหลักบนเครือข่าย TPIX Chain (Chain ID {payment.chain_id}) เท่านั้น
              ยอดไม่ตรงหรือผิดเครือข่าย ระบบจะจับคู่กับคำสั่งซื้อไม่ได้
            </Text>
          </View>
        </Card3D>
      )}

      {/* ---------- หมดเวลา ---------- */}
      {shownStatus === 'expired' && (
        <Card3D padding={spacing.lg} radius={20} style={styles.block}>
          <View style={styles.rowBetween}>
            <IconTile icon="clock" tone="danger" />
            <View style={styles.flex}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>หมดเวลาชำระยอดนี้แล้ว</Text>
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>
                {isTopup
                  ? 'เริ่มเติมเงินใหม่เพื่อรับยอดเหรียญตามราคาปัจจุบัน'
                  : 'ออกยอดใหม่ตามราคาปัจจุบันได้เลย ถ้าโอนไปแล้ว ให้วางเลขธุรกรรมด้านล่าง เหรียญที่โอนหลังหมดเวลาจะเติมเข้ากระเป๋าเงินให้'}
              </Text>
            </View>
          </View>
          <Button3D
            title={isTopup ? 'เติมเงินใหม่' : 'ออกยอดใหม่'}
            icon="arrows-clockwise"
            size="md"
            fullWidth
            onPress={confirmRenew}
            style={styles.gapTop}
          />
        </Card3D>
      )}

      {/* ---------- ยืนยันบนเชน / โอนหลังหมดเวลา ---------- */}
      {(status === 'confirming' || status === 'late') && (
        <Card3D padding={spacing.lg} radius={20} style={styles.block}>
          <View style={styles.rowBetween}>
            <IconTile icon={status === 'late' ? 'clock' : 'arrows-clockwise'} tone="info" />
            <View style={styles.flex}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>
                {status === 'late' ? 'ได้รับเหรียญหลังหมดเวลา' : 'พบการโอนแล้ว กำลังยืนยันบนเชน'}
              </Text>
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>
                {status === 'late'
                  ? 'ระบบจะคิดมูลค่าตามราคาปัจจุบันแล้วเติมเข้ากระเป๋าเงินให้ ไม่ต้องโอนซ้ำนะ'
                  : `ยืนยันแล้ว ${Math.min(payment.confirmations, payment.confirmations_required)}/${payment.confirmations_required} บล็อก ไม่ต้องโอนซ้ำนะ`}
              </Text>
            </View>
          </View>
          <View style={[styles.progressTrack, { backgroundColor: colors.inset, borderColor: colors.border }]}>
            <LinearGradient
              colors={gradients.primary}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={[styles.progressFill, { width: `${Math.max(6, Math.round(progress * 100))}%` }]}
            />
          </View>
          {!!payment.explorer_tx_url && (
            <Button3D
              title="ดูธุรกรรมบนเชน"
              icon="arrow-square-out"
              variant="ghost"
              size="sm"
              onPress={() => openHttpsLink(payment.explorer_tx_url, 'ธุรกรรม TPIX')}
              style={styles.gapTopSm}
            />
          )}
        </Card3D>
      )}

      {/* ---------- ยกเลิก / ตรวจไม่ผ่าน ---------- */}
      {(status === 'cancelled' || status === 'failed') && (
        <Card3D padding={spacing.lg} radius={20} style={styles.block}>
          <View style={styles.rowBetween}>
            <IconTile icon={status === 'failed' ? 'warning-circle' : 'x-circle'} tone={status === 'failed' ? 'danger' : 'warning'} />
            <View style={styles.flex}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>
                {status === 'failed' ? 'ตรวจธุรกรรมไม่ผ่าน' : 'ยอดนี้ถูกยกเลิกแล้ว'}
              </Text>
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>
                {payment.failure_reason && /[฀-๿]/.test(payment.failure_reason)
                  ? payment.failure_reason
                  : status === 'failed'
                    ? 'ธุรกรรมที่ส่งมาไม่ตรงกับยอดนี้ ตรวจเลขธุรกรรมอีกครั้ง หรือออกยอดใหม่'
                    : 'ไม่ต้องโอนเหรียญเข้ายอดนี้ เลือกวิธีชำระใหม่ได้ที่หน้าคำสั่งซื้อ'}
              </Text>
            </View>
          </View>
          {status === 'failed' && (
            <Button3D
              title={isTopup ? 'เติมเงินใหม่' : 'ออกยอดใหม่'}
              icon="arrows-clockwise"
              size="md"
              fullWidth
              onPress={confirmRenew}
              style={styles.gapTop}
            />
          )}
        </Card3D>
      )}

      {/* ---------- วางเลขธุรกรรม ---------- */}
      {showTxInput && (
        <Card3D padding={spacing.lg} radius={20} style={styles.block}>
          <View style={styles.rowBetween}>
            <IconTile icon="link" tone="navy" size={40} />
            <View style={styles.flex}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>โอนแล้ว แต่ระบบยังไม่ขึ้น?</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>
                วางเลขธุรกรรม (Transaction Hash) จากกระเป๋า ให้ระบบตรวจบนเชนทันที
              </Text>
            </View>
          </View>
          <Field
            label="เลขธุรกรรม"
            value={txInput}
            onChangeText={(v) => {
              setTxInput(v.replace(/\s/g, '').slice(0, 100));
              if (txError) setTxError(null);
            }}
            placeholder="0x..."
            autoCapitalize="none"
            autoCorrect={false}
            error={txError}
            returnKeyType="done"
            onSubmitEditing={submitTx}
            containerStyle={styles.gapTop}
          />
          <View style={styles.buttonRow}>
            <Button3D title="วาง" icon="clipboard-text" variant="secondary" size="md" onPress={pasteTx} />
            <Button3D
              title="ตรวจสอบการโอน"
              icon="magnifying-glass"
              size="md"
              loadingText="กำลังตรวจ..."
              disabled={txInput.trim().length === 0}
              onPress={submitTx}
              style={styles.flex}
            />
          </View>
          {!!submitNote && (
            <NoticeBanner tone={submitNote.tone} text={submitNote.text} onClose={() => setSubmitNote(null)} style={styles.gapTop} />
          )}
        </Card3D>
      )}

      {/* ---------- ตั้งค่าเครือข่ายในกระเป๋า ---------- */}
      {canPay && (
        <Card3D variant="inset" padding={spacing.lg} radius={20} style={styles.block}>
          <View style={styles.netHead}>
            <Icon name="globe" size={16} color={colors.textMuted} />
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>กระเป๋ายังไม่มีเครือข่าย TPIX?</Text>
          </View>
          <Text style={[typography.caption, { color: colors.textMuted }]}>เพิ่มเครือข่ายเองในกระเป๋าด้วยข้อมูลนี้</Text>
          <NetRow label="ชื่อเครือข่าย" value={payment.chain_name || 'TPIX Chain'} />
          <NetRow
            label="Chain ID"
            value={String(payment.chain_id)}
            copied={copied === 'chain'}
            onCopy={() => copy('chain', String(payment.chain_id))}
          />
          {!!payment.rpc_url && (
            <NetRow label="RPC URL" value={payment.rpc_url} copied={copied === 'rpc'} onCopy={() => copy('rpc', payment.rpc_url)} />
          )}
          <NetRow label="สกุลเหรียญ" value="TPIX" />
        </Card3D>
      )}

      {active && (
        <View style={[styles.waitingRow, { backgroundColor: colors.card, borderColor: colors.border }]}>
          <ActivityIndicator color={colors.gold} />
          <Text style={[typography.caption, { color: colors.textMuted }]}>กำลังตรวจสอบบนเชนอัตโนมัติ...</Text>
        </View>
      )}

      {group.length > 1 && <GroupList group={group} current={reference} onOpen={openEntry} />}

      <Button3D
        title={isTopup ? 'กลับไปกระเป๋าเงิน' : 'ชำระภายหลัง / ดูคำสั่งซื้อ'}
        icon={isTopup ? 'wallet' : 'receipt'}
        variant="secondary"
        size="md"
        fullWidth
        onPress={goExit}
        style={styles.gapTop}
      />
    </Screen>
  );
}

// =====================================================
// ชิ้นส่วนย่อย
// =====================================================

/** แถวข้อมูลเครือข่าย (ป้ายซ้าย ค่าขวา + ปุ่มคัดลอก) */
const NetRow: React.FC<{ label: string; value: string; copied?: boolean; onCopy?: () => void }> = ({
  label,
  value,
  copied,
  onCopy,
}) => {
  const { colors } = useTheme();
  return (
    <View style={[styles.netRow, { borderTopColor: colors.divider }]}>
      <Text style={[typography.caption, { color: colors.textMuted }]}>{label}</Text>
      <Text selectable numberOfLines={1} style={[typography.bodySm, styles.netValue, { color: colors.textStrong }]}>
        {value}
      </Text>
      {onCopy && (
        <Pressable onPress={onCopy} hitSlop={10} accessibilityRole="button" accessibilityLabel={`คัดลอก ${label}`}>
          <Icon name={copied ? 'check-circle' : 'copy'} size={16} color={colors.goldDeep} />
        </Pressable>
      )}
    </View>
  );
};

/** รายการใบเสนอยอดอื่นในชุดคำสั่งซื้อเดียวกัน (สั่งหลายร้าน = จ่ายแยกทีละร้าน) */
const GroupList: React.FC<{ group: TpixGroupEntry[]; current: string; onOpen: (entry: TpixGroupEntry) => void }> = ({
  group,
  current,
  onOpen,
}) => {
  const { colors } = useTheme();
  return (
    <>
      <SectionHeader title="จ่ายให้ครบทุกร้าน" icon="storefront" style={styles.section} />
      <Text style={[typography.caption, styles.groupNote, { color: colors.textMuted }]}>
        สั่งจากหลายร้าน แต่ละร้านมียอดเหรียญของตัวเอง โอนแยกให้ครบทุกยอดนะ
      </Text>
      <Card3D padding={0} radius={20} style={styles.block}>
        {group.map((entry, index) => {
          const isCurrent = entry.reference === current;
          const cached = entry.reference ? peekTpixPayment(entry.reference) : null;
          const st = cached?.status ?? (entry.reference ? 'pending' : 'error');
          return (
            <Pressable
              key={`${entry.order_id}-${entry.reference ?? 'x'}`}
              onPress={() => onOpen(entry)}
              disabled={isCurrent}
              accessibilityRole="button"
              accessibilityLabel={`${entry.store_name || 'คำสั่งซื้อ'} ${formatBaht(entry.amount_thb, { decimals: 2 })}${isCurrent ? ' กำลังดูอยู่' : ''}`}
              style={({ pressed }) => [
                styles.groupRow,
                index > 0 && { borderTopWidth: 1, borderTopColor: colors.divider },
                isCurrent && { backgroundColor: colors.goldSoft },
                pressed && { opacity: 0.7 },
              ]}
            >
              <IconTile icon="storefront" tone={isCurrent ? 'gold' : 'navy'} size={38} />
              <View style={styles.flex}>
                <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
                  {entry.store_name || entry.order_number || 'คำสั่งซื้อ'}
                </Text>
                <Text numberOfLines={2} style={[typography.caption, { color: entry.reference ? colors.textMuted : colors.danger }]}>
                  {entry.reference
                    ? [entry.order_number, STATUS_LABEL[st] || st].filter(Boolean).join(' · ')
                    : entry.message || 'ออกยอดเหรียญไม่สำเร็จ กดเพื่อจ่ายใหม่ที่หน้าคำสั่งซื้อ'}
                </Text>
              </View>
              <PriceText amount={entry.amount_thb} decimals={2} size="sm" tone="strong" />
              {!isCurrent && <Icon name="caret-right" size={15} color={colors.textFaint} weight="bold" />}
            </Pressable>
          );
        })}
      </Card3D>
    </>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  block: {
    marginBottom: spacing.md,
  },
  section: {
    marginTop: spacing.lg,
  },
  rowBetween: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  center: {
    textAlign: 'center',
    alignSelf: 'center',
  },
  bold: {
    fontWeight: '700',
  },
  mono: {
    fontVariant: ['tabular-nums'],
  },
  gapTop: {
    marginTop: spacing.md,
  },
  gapTopSm: {
    marginTop: spacing.sm,
  },
  coinTile: {
    width: 48,
    height: 48,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  amountRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    marginTop: spacing.xs,
    marginBottom: 2,
  },
  amountText: {
    flexShrink: 1,
  },
  copyAmount: {
    alignSelf: 'center',
    marginTop: spacing.md,
  },
  timerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    gap: 6,
    borderRadius: radii.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
    marginTop: spacing.md,
  },
  timerValue: {
    fontWeight: '700',
    fontVariant: ['tabular-nums'],
  },
  qrFrame: {
    alignSelf: 'center',
    width: 232,
    height: 232,
    borderRadius: 20,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: spacing.lg,
    marginBottom: spacing.sm,
  },
  addressBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.md,
    borderWidth: 1,
    padding: spacing.md,
    marginTop: spacing.md,
  },
  copyChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  hintRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 6,
    marginTop: spacing.md,
    paddingHorizontal: spacing.xs,
  },
  hintText: {
    flexShrink: 1,
  },
  buttonRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  progressTrack: {
    height: 10,
    borderRadius: 999,
    borderWidth: 1,
    overflow: 'hidden',
    marginTop: spacing.md,
  },
  progressFill: {
    height: '100%',
    borderRadius: 999,
  },
  netHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  netRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderTopWidth: 1,
    marginTop: spacing.sm,
    paddingTop: spacing.sm,
  },
  netValue: {
    flex: 1,
    textAlign: 'right',
    fontVariant: ['tabular-nums'],
  },
  waitingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    gap: spacing.sm,
    marginTop: spacing.xs,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
    borderRadius: 999,
    borderWidth: 1,
  },
  groupNote: {
    marginTop: -spacing.xs,
    marginBottom: spacing.sm,
  },
  groupRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: 14,
  },
  medalOuter: {
    alignSelf: 'center',
    width: 92,
    height: 92,
    borderRadius: 46,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.md,
  },
  medalInner: {
    width: 66,
    height: 66,
    borderRadius: 33,
    alignItems: 'center',
    justifyContent: 'center',
  },
  doneBox: {
    marginTop: spacing.lg,
    borderRadius: 16,
    borderWidth: 1,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.lg,
    gap: 2,
  },
  doneAmountRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
  },
});
