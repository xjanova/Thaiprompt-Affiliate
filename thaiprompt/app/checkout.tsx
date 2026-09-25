/**
 * ชำระเงิน — POST /cart/checkout (SHOP-03 / 07 / 08 / 11 / 23)
 *
 * ขั้นตอน
 *   1. form     เลือกที่อยู่ · วิธีส่ง (พัสดุ | ไรเดอร์ เมื่อร้านรองรับ + ค่าส่งจาก server)
 *               · วิธีจ่าย (กระเป๋าเงิน | พร้อมเพย์ | เก็บเงินปลายทาง เฉพาะส่งด้วยไรเดอร์) · คูปอง (server ตรวจ) · หมายเหตุ
 *   2. payment  พร้อมเพย์: แสดง QR ของแต่ละออเดอร์ (ยอดตรงทุกสตางค์) + poll สถานะทุก 4 วินาที + ขอ QR ใหม่ได้
 *   3. done     สรุปคำสั่งซื้อ
 *
 * ความปลอดภัย
 *   - ยอดทุกตัวมาจาก server (GET /cart?address_id&delivery_method&coupon_code) — แอปไม่คำนวณเอง
 *   - Idempotency-Key ต่อ "ชุดข้อมูลที่กดสั่ง" — กดซ้ำ/เน็ตหลุดแล้วลองใหม่ = ไม่สั่งซ้ำ
 *   - ยืนยันก่อนสั่งทุกครั้ง (โดยเฉพาะตัดเงินกระเป๋า)
 *   - ข้อความ error ภาษาไทยจาก code ของ server เท่านั้น
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, AppState, Pressable, StyleSheet, Text, View } from 'react-native';
import { router, useFocusEffect, useNavigation } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { useCartStore, normalizeCart } from '@/stores/cartStore';
import { getWallet } from '@/services/api';
import {
  applyCartPromo,
  checkout,
  getAddresses,
  getCart,
  getPaymentMethods,
  getPaymentStatus,
  payOrder,
  type Address,
  type Cart,
  type CheckoutPaymentMethod,
  type CheckoutResult,
  type DeliveryMethod,
  type PaymentInstruction,
} from '@/services/api/shopApi';
import { newIdempotencyKey, type ApiFailure } from '@/services/api/client';
import {
  Button3D,
  Card3D,
  EmptyState,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  formatBaht,
  resultHaptic,
} from '@/components/ui';
import { Field, FormSheet, PromptPayQR, type PromptPayState } from '@/components/shop';
import { useTheme, clayShadowStyle, radii, spacing, typography } from '@/theme';

type Step = 'form' | 'payment' | 'done';

const POLL_INTERVAL_MS = 4000;
const POLL_MAX_MS = 20 * 60 * 1000;

const PAYMENT_LABEL: Record<CheckoutPaymentMethod, string> = {
  wallet: 'กระเป๋าเงิน',
  promptpay: 'พร้อมเพย์',
  cod: 'เก็บเงินปลายทาง',
};

// =====================================================
// ตัวเลือกแบบการ์ด (วิธีส่ง / วิธีจ่าย)
// =====================================================

interface OptionCardProps {
  icon: string;
  title: string;
  subtitle?: string;
  right?: React.ReactNode;
  selected: boolean;
  disabled?: boolean;
  disabledReason?: string | null;
  onPress: () => void;
}

const OptionCard: React.FC<OptionCardProps> = ({ icon, title, subtitle, right, selected, disabled, disabledReason, onPress }) => {
  const { colors } = useTheme();
  return (
    <Card3D
      onPress={disabled ? undefined : onPress}
      gradientBorder={selected}
      variant={disabled ? 'flat' : 'raised'}
      shadow="sm"
      padding={spacing.md}
      radius={radii.lg}
      style={[styles.option, disabled && styles.dimmed]}
      accessibilityLabel={`${title}${selected ? ' เลือกอยู่' : ''}${disabled && disabledReason ? ` ใช้ไม่ได้: ${disabledReason}` : ''}`}
    >
      <View style={styles.optionRow}>
        <View style={[styles.radio, { borderColor: selected ? colors.gold : colors.border }]}>
          {selected && <View style={[styles.radioDot, { backgroundColor: colors.gold }]} />}
        </View>
        <Text style={styles.optionIcon}>{icon}</Text>
        <View style={styles.flex}>
          <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{title}</Text>
          {!!subtitle && <Text style={[typography.caption, { color: colors.textMuted }]}>{subtitle}</Text>}
          {disabled && !!disabledReason && (
            <Text style={[typography.caption, { color: colors.warning }]}>{disabledReason}</Text>
          )}
        </View>
        {right}
      </View>
    </Card3D>
  );
};

// =====================================================
// หน้าจอ
// =====================================================

export default function CheckoutScreen() {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const navigation = useNavigation();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  // ---------- ข้อมูลฟอร์ม ----------
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [addressesChecked, setAddressesChecked] = useState(false);
  const [selectedAddressId, setSelectedAddressId] = useState<number | null>(null);
  const [addressSheet, setAddressSheet] = useState(false);
  const [delivery, setDelivery] = useState<DeliveryMethod>('parcel');
  const [payment, setPayment] = useState<CheckoutPaymentMethod>('wallet');
  const [couponInput, setCouponInput] = useState('');
  const [appliedCoupon, setAppliedCoupon] = useState<string | null>(null);
  const [couponError, setCouponError] = useState<string | null>(null);
  const [note, setNote] = useState('');
  const [notice, setNotice] = useState<string | null>(null);

  const [quote, setQuote] = useState<Cart | null>(null);
  const [quoteLoading, setQuoteLoading] = useState(false);
  const [quoteError, setQuoteError] = useState<string | null>(null);
  const [walletBalance, setWalletBalance] = useState<number | null>(null);
  const [promptpayEnabled, setPromptpayEnabled] = useState<boolean | null>(null);

  // ---------- ผลการสั่ง ----------
  const [step, setStep] = useState<Step>('form');
  const [submitting, setSubmitting] = useState(false);
  const [result, setResult] = useState<CheckoutResult | null>(null);
  const [payments, setPayments] = useState<PaymentInstruction[]>([]);
  const [payStates, setPayStates] = useState<Record<string, PromptPayState>>({});
  const [pollTimedOut, setPollTimedOut] = useState(false);

  const mountedRef = useRef(true);
  const quoteReqRef = useRef(0);
  const knownAddressIdsRef = useRef<Set<number> | null>(null);
  const focusCountRef = useRef(0);
  const idemRef = useRef<{ signature: string; key: string } | null>(null);
  const leavingRef = useRef(false);
  const pollStartedAtRef = useRef(0);
  const pollingBusyRef = useRef(false);
  const submittingRef = useRef(false);
  const repayingRef = useRef<Set<number>>(new Set());
  const pollCursorRef = useRef(0);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // =====================================================
  // โหลดข้อมูลประกอบ
  // =====================================================

  const loadAddresses = useCallback(async () => {
    if (!isAuthenticated) return;
    const res = await getAddresses();
    if (!mountedRef.current) return;
    setAddressesChecked(true);
    if (!res.success) return;
    const list = Array.isArray(res.data) ? res.data : [];
    setAddresses(list);

    // ที่อยู่ที่เพิ่งเพิ่มจากหน้าเพิ่มที่อยู่ → เลือกให้เลย
    const known = knownAddressIdsRef.current;
    const added = known ? list.filter((a) => !known.has(a.id)) : [];
    knownAddressIdsRef.current = new Set(list.map((a) => a.id));
    setSelectedAddressId((prev) => {
      if (added.length > 0) return added.reduce((max, a) => (a.id > max.id ? a : max)).id;
      if (prev && list.some((a) => a.id === prev)) return prev;
      const fallback = list.find((a) => a.is_default) || list[0];
      return fallback ? fallback.id : null;
    });
  }, [isAuthenticated]);

  const loadWallet = useCallback(async () => {
    if (!isAuthenticated) return;
    const res = await getWallet();
    if (!mountedRef.current) return;
    const balance = Number(res?.data?.balance);
    if (res?.success && Number.isFinite(balance)) setWalletBalance(balance);
  }, [isAuthenticated]);

  const loadPaymentMethods = useCallback(async () => {
    if (!isAuthenticated) return;
    const res = await getPaymentMethods();
    if (!mountedRef.current || !res.success) return;
    const methods = Array.isArray(res.data?.methods) ? res.data.methods : [];
    setPromptpayEnabled(methods.some((m) => m.id === 'promptpay' && m.enabled !== false));
  }, [isAuthenticated]);

  const fetchQuote = useCallback(async () => {
    if (!isAuthenticated) return;
    const requestId = ++quoteReqRef.current;
    setQuoteLoading(true);
    const res = await getCart({
      ...(selectedAddressId ? { address_id: selectedAddressId } : {}),
      delivery_method: delivery,
      ...(appliedCoupon ? { coupon_code: appliedCoupon } : {}),
    });
    if (!mountedRef.current || requestId !== quoteReqRef.current) return;
    setQuoteLoading(false);
    if (res.success) {
      const cart = normalizeCart(res.data);
      setQuote(cart);
      setQuoteError(null);
      useCartStore.getState().replace(cart);
    } else {
      setQuoteError(res.message);
    }
  }, [isAuthenticated, selectedAddressId, delivery, appliedCoupon]);

  useEffect(() => {
    loadAddresses();
    loadWallet();
    loadPaymentMethods();
  }, [loadAddresses, loadWallet, loadPaymentMethods]);

  // คำนวณยอดใหม่ทุกครั้งที่เปลี่ยนที่อยู่/วิธีส่ง/คูปอง
  useEffect(() => {
    if (step !== 'form' || !addressesChecked) return;
    fetchQuote();
  }, [step, addressesChecked, fetchQuote]);

  // ฟังก์ชันล่าสุด (กัน closure เก่าใน focus effect ยิงด้วยที่อยู่/วิธีส่งเดิม)
  const latestRef = useRef({ loadAddresses, loadWallet, fetchQuote });
  latestRef.current = { loadAddresses, loadWallet, fetchQuote };

  // กลับมาจากหน้าเพิ่ม/แก้ที่อยู่ หรือเติมเงิน → โหลดใหม่
  useFocusEffect(
    useCallback(() => {
      focusCountRef.current += 1;
      if (focusCountRef.current > 1 && step === 'form') {
        latestRef.current.loadAddresses();
        latestRef.current.loadWallet();
        latestRef.current.fetchQuote();
      }
    }, [step])
  );

  // =====================================================
  // ปรับตัวเลือกให้ตรงกับที่ server บอก (ไรเดอร์/COD/คูปอง)
  // =====================================================

  const riderInfo = useMemo(() => {
    const stores = quote?.stores ?? [];
    const available = !!quote?.summary.rider_available;
    const fee = stores.reduce((sum, s) => sum + (s.rider.available && s.rider.fee !== null ? s.rider.fee : 0), 0);
    const minutes = stores.reduce((max, s) => Math.max(max, s.rider.estimated_minutes ?? 0), 0);
    const distance = stores.reduce((max, s) => Math.max(max, s.rider.distance_km ?? 0), 0);
    const reason = stores.find((s) => !s.rider.available && s.rider.reason)?.rider.reason ?? null;
    const parcelFee = stores.reduce((sum, s) => sum + s.parcel_fee, 0);
    const codReason = stores.find((s) => !s.cod.available && s.cod.reason)?.cod.reason ?? null;
    return { available, fee, minutes, distance, reason, parcelFee, codReason };
  }, [quote]);

  const codAvailable = delivery === 'rider' && !!quote?.summary.cod_available;

  useEffect(() => {
    if (!quote || quoteLoading || step !== 'form') return;
    if (delivery === 'rider' && !quote.summary.rider_available) {
      setDelivery('parcel');
      setNotice(`ส่งด้วยไรเดอร์ไม่ได้ (${riderInfo.reason || 'ร้านหรือที่อยู่ยังไม่พร้อม'}) เปลี่ยนเป็นส่งพัสดุให้แล้ว`);
    }
    if (payment === 'cod' && !(delivery === 'rider' && quote.summary.cod_available)) {
      setPayment('wallet');
      setNotice('เก็บเงินปลายทางใช้ได้เมื่อส่งด้วยไรเดอร์ เปลี่ยนเป็นจ่ายด้วยกระเป๋าเงินให้แล้ว');
    }
    if (appliedCoupon && quote.coupon_error) {
      setCouponError(quote.coupon_error.message || 'คูปองนี้ใช้ไม่ได้แล้ว');
      setAppliedCoupon(null);
    }
  }, [quote, quoteLoading, step, delivery, payment, appliedCoupon, riderInfo.reason]);

  useEffect(() => {
    if (payment === 'promptpay' && promptpayEnabled === false) {
      setPayment('wallet');
    }
  }, [payment, promptpayEnabled]);

  // =====================================================
  // คูปอง
  // =====================================================

  const applyCoupon = async () => {
    const code = couponInput.trim().toUpperCase();
    if (!code) {
      setCouponError('ใส่โค้ดส่วนลดก่อนนะ');
      return;
    }
    setCouponError(null);
    const res = await applyCartPromo(code, {
      ...(selectedAddressId ? { address_id: selectedAddressId } : {}),
      delivery_method: delivery,
    });
    if (!mountedRef.current) return;
    if (res.success) {
      resultHaptic('success');
      const cart = normalizeCart(res.data);
      quoteReqRef.current += 1; // ทิ้งผลคำนวณเก่าที่ยังค้าง
      setQuote(cart);
      setQuoteLoading(false);
      setAppliedCoupon(code);
      setCouponInput('');
    } else {
      resultHaptic('error');
      setCouponError(res.message);
    }
  };

  const removeCoupon = () => {
    setAppliedCoupon(null);
    setCouponError(null);
  };

  // =====================================================
  // สั่งซื้อ
  // =====================================================

  const selectedAddress = addresses.find((a) => a.id === selectedAddressId) || null;
  const grandTotal = quote?.summary.grand_total ?? 0;
  const availableItems = (quote?.items ?? []).filter((i) => i.is_available);
  const walletShort = payment === 'wallet' && walletBalance !== null && walletBalance < grandTotal;

  /** ชุดข้อมูลที่กดสั่ง — เปลี่ยน = คีย์ใหม่ (ไม่เปลี่ยน = ใช้คีย์เดิมตอนลองใหม่ กันสั่งซ้ำ) */
  const signature = JSON.stringify({
    a: selectedAddressId,
    d: delivery,
    p: payment,
    c: appliedCoupon,
    n: note.trim(),
    i: (quote?.items ?? []).map((i) => [i.id, i.quantity]),
  });

  const handleFailure = (res: ApiFailure) => {
    resultHaptic('error');
    switch (res.code) {
      case 'INSUFFICIENT_BALANCE': {
        const shortfall = Number(res.data?.shortfall);
        Alert.alert(
          'ยอดเงินในกระเป๋าไม่พอ',
          Number.isFinite(shortfall) ? `ขาดอีก ${formatBaht(shortfall, { decimals: 2 })} เติมเงินหรือเลือกวิธีจ่ายอื่นได้นะ` : res.message,
          [
            { text: 'เลือกวิธีอื่น', style: 'cancel' },
            { text: 'เติมเงิน', onPress: () => router.push('/wallet-topup' as never) },
          ]
        );
        loadWallet();
        return;
      }
      case 'ADDRESS_REQUIRED':
      case 'ADDRESS_NOT_FOUND':
        Alert.alert('เลือกที่อยู่จัดส่งก่อนนะ', res.message, [
          { text: 'ไว้ก่อน', style: 'cancel' },
          { text: 'เพิ่มที่อยู่', onPress: () => router.push('/addresses/edit' as never) },
        ]);
        loadAddresses();
        return;
      case 'ADDRESS_LOCATION_REQUIRED': {
        const id = Number(res.data?.address_id) || selectedAddressId;
        Alert.alert('ปักหมุดที่อยู่ก่อนนะ', 'ส่งด้วยไรเดอร์ต้องปักหมุดตำแหน่ง ไรเดอร์จะได้ไปส่งถูกที่', [
          { text: 'ส่งพัสดุแทน', onPress: () => setDelivery('parcel') },
          { text: 'ปักหมุด', onPress: () => router.push((id ? `/addresses/edit?id=${id}` : '/addresses/edit') as never) },
        ]);
        return;
      }
      case 'COUPON_INVALID':
        setCouponError(res.message);
        setAppliedCoupon(null);
        Alert.alert('คูปองใช้ไม่ได้', res.message);
        return;
      case 'CART_EMPTY':
      case 'PRODUCT_UNAVAILABLE':
      case 'OUT_OF_STOCK':
        Alert.alert('ตะกร้ามีการเปลี่ยนแปลง', res.message, [{ text: 'ไปที่ตะกร้า', onPress: () => router.replace('/cart') }]);
        return;
      case 'RIDER_NOT_AVAILABLE':
      case 'COD_NOT_AVAILABLE':
      case 'PAYMENT_METHOD_UNAVAILABLE':
        Alert.alert('เปลี่ยนตัวเลือกก่อนนะ', res.message);
        fetchQuote();
        loadPaymentMethods();
        return;
      case 'CHECKOUT_IN_PROGRESS':
        Alert.alert('กำลังสร้างคำสั่งซื้อ', 'รอสักครู่แล้วกดยืนยันอีกครั้ง ระบบจะไม่สั่งซ้ำ');
        return;
      case 'NETWORK_ERROR':
      case 'TIMEOUT':
      case 'SERVER_ERROR':
      case 'CHECKOUT_FAILED':
        Alert.alert('ยังสั่งซื้อไม่สำเร็จ', `${res.message}\nกดยืนยันอีกครั้งได้เลย ระบบจะไม่สั่งซ้ำ`);
        return;
      default:
        Alert.alert('สั่งซื้อไม่สำเร็จ', res.message);
    }
  };

  const submit = async () => {
    // กันกดยืนยันซ้ำระหว่างรอ (state อาจยังไม่อัปเดตทัน)
    if (submittingRef.current) return;
    submittingRef.current = true;
    setSubmitting(true);
    if (!idemRef.current || idemRef.current.signature !== signature) {
      idemRef.current = { signature, key: newIdempotencyKey() };
    }
    const res = await checkout(
      {
        payment_method: payment,
        delivery_method: delivery,
        ...(selectedAddressId ? { address_id: selectedAddressId } : {}),
        ...(appliedCoupon ? { coupon_code: appliedCoupon } : {}),
        ...(note.trim() ? { note: note.trim() } : {}),
      },
      idemRef.current.key
    );
    submittingRef.current = false;
    if (!mountedRef.current) return;
    setSubmitting(false);

    if (!res.success) {
      handleFailure(res);
      return;
    }

    // สั่งสำเร็จ — server ล้างตะกร้าแล้ว
    idemRef.current = null;
    resultHaptic('success');
    useCartStore.getState().reset();
    useCartStore.getState().refresh();

    const data = res.data;
    setResult(data);
    if (typeof data.wallet_balance === 'number') setWalletBalance(data.wallet_balance);

    if (data.payment_status === 'pending' && Array.isArray(data.payments) && data.payments.length > 0) {
      const states: Record<string, PromptPayState> = {};
      data.payments.forEach((p, index) => {
        states[p.transaction_id || `order-${p.order_id}-${index}`] = p.status === 'error' || !p.transaction_id ? 'error' : 'waiting';
      });
      setPayments(data.payments);
      setPayStates(states);
      pollStartedAtRef.current = Date.now();
      setPollTimedOut(false);
      setStep('payment');
    } else {
      setStep('done');
    }
  };

  const confirmAndSubmit = () => {
    if (!quote || quoteLoading) return;
    if (availableItems.length === 0) {
      Alert.alert('ตะกร้าว่าง', 'ยังไม่มีสินค้าที่สั่งได้');
      return;
    }
    if ((quote.summary.unavailable_count ?? 0) > 0) {
      Alert.alert('มีสินค้าที่สั่งไม่ได้', 'เอาสินค้าที่หมด/ปิดขายออกจากตะกร้าก่อนนะ', [
        { text: 'ไปที่ตะกร้า', onPress: () => router.replace('/cart') },
      ]);
      return;
    }
    if (walletShort) {
      Alert.alert(
        'ยอดเงินในกระเป๋าไม่พอ',
        `ต้องจ่าย ${formatBaht(grandTotal, { decimals: 2 })} แต่มี ${formatBaht(walletBalance, { decimals: 2 })}`,
        [
          { text: 'เลือกวิธีอื่น', style: 'cancel' },
          { text: 'เติมเงิน', onPress: () => router.push('/wallet-topup' as never) },
        ]
      );
      return;
    }

    const lines = [
      `ยอดชำระ ${formatBaht(grandTotal, { decimals: 2 })}`,
      `จ่ายด้วย: ${PAYMENT_LABEL[payment]}`,
      `จัดส่ง: ${delivery === 'rider' ? 'ไรเดอร์' : 'พัสดุ'}`,
      quote.stores.length > 1 ? `แยกเป็น ${quote.stores.length} คำสั่งซื้อตามร้าน` : '',
      payment === 'wallet' ? 'ระบบจะตัดเงินจากกระเป๋าทันที' : '',
    ].filter(Boolean);

    Alert.alert('ยืนยันสั่งซื้อ?', lines.join('\n'), [
      { text: 'ยังก่อน', style: 'cancel' },
      { text: payment === 'wallet' ? 'ยืนยันจ่ายเงิน' : 'ยืนยันสั่งซื้อ', onPress: () => submit() },
    ]);
  };

  // =====================================================
  // พร้อมเพย์: poll สถานะ
  // =====================================================

  const paymentKey = (p: PaymentInstruction, index: number) => p.transaction_id || `order-${p.order_id}-${index}`;

  /**
   * เช็คสถานะทีละรายการแบบวนรอบ (หลายร้าน = หลาย QR) — รวมแล้วไม่เกิน 15 ครั้ง/นาที
   * ไม่ชน throttle 60 ครั้ง/นาทีของ API
   */
  const pollOnce = useCallback(async () => {
    if (pollingBusyRef.current) return;
    pollingBusyRef.current = true;
    try {
      const waiting = payments
        .map((p, index) => ({ p, key: paymentKey(p, index) }))
        .filter(({ p, key }) => payStates[key] === 'waiting' && !!p.transaction_id);
      if (waiting.length === 0) return;

      const target = waiting[pollCursorRef.current % waiting.length];
      pollCursorRef.current += 1;
      const res = await getPaymentStatus(target.p.transaction_id);
      if (!mountedRef.current || !res.success) return;

      const st = String(res.data?.status || '');
      const nextState: PromptPayState | null =
        st === 'completed' || res.data?.order?.payment_status === 'paid'
          ? 'paid'
          : st === 'failed' || st === 'cancelled'
            ? 'error'
            : st === 'expired' || res.data?.is_expired === true
              ? 'expired'
              : null;
      if (nextState) {
        setPayStates((prev) => (prev[target.key] === 'waiting' ? { ...prev, [target.key]: nextState } : prev));
      }
    } finally {
      pollingBusyRef.current = false;
    }
  }, [payments, payStates]);

  const anyWaiting = Object.values(payStates).some((s) => s === 'waiting');
  const allPaid = payments.length > 0 && payments.every((p, i) => payStates[paymentKey(p, i)] === 'paid');

  // poll ระหว่างอยู่หน้านี้ + ยังมี QR ที่รอจ่าย
  useFocusEffect(
    useCallback(() => {
      if (step !== 'payment' || !anyWaiting || pollTimedOut) return undefined;
      const timer = setInterval(() => {
        if (Date.now() - pollStartedAtRef.current > POLL_MAX_MS) {
          setPollTimedOut(true);
          return;
        }
        pollOnce();
      }, POLL_INTERVAL_MS);
      // กลับมาจากแอปธนาคาร → เช็คทันที
      const sub = AppState.addEventListener('change', (state) => {
        if (state === 'active') pollOnce();
      });
      return () => {
        clearInterval(timer);
        sub.remove();
      };
    }, [step, anyWaiting, pollTimedOut, pollOnce])
  );

  // จ่ายครบทุก QR → ไปหน้าสำเร็จ
  useEffect(() => {
    if (step !== 'payment' || !allPaid) return;
    resultHaptic('success');
    const timer = setTimeout(() => {
      if (mountedRef.current) setStep('done');
    }, 1200);
    return () => clearTimeout(timer);
  }, [step, allPaid]);

  /** ขอ QR ใหม่ / เปลี่ยนไปจ่ายด้วยกระเป๋า สำหรับออเดอร์หนึ่ง */
  const repay = async (index: number, method: 'promptpay' | 'wallet') => {
    const current = payments[index];
    if (!current || repayingRef.current.has(current.order_id)) return;
    const oldKey = paymentKey(current, index);
    repayingRef.current.add(current.order_id);
    const res = await payOrder(current.order_id, method);
    repayingRef.current.delete(current.order_id);
    if (!mountedRef.current) return;

    if (!res.success) {
      if (res.code === 'ALREADY_PAID') {
        setPayStates((prev) => ({ ...prev, [oldKey]: 'paid' }));
        return;
      }
      resultHaptic('error');
      Alert.alert(method === 'wallet' ? 'จ่ายด้วยกระเป๋าไม่สำเร็จ' : 'ขอ QR ใหม่ไม่สำเร็จ', res.message);
      return;
    }

    const paidNow = res.data?.order?.payment_status === 'paid' || res.data?.status === 'completed';
    const updated: PaymentInstruction = { ...res.data, order_id: res.data.order_id || current.order_id };
    const newKey = updated.transaction_id || oldKey;
    setPayments((prev) => prev.map((p, i) => (i === index ? updated : p)));
    setPayStates((prev) => {
      const next = { ...prev };
      delete next[oldKey];
      next[newKey] = paidNow ? 'paid' : 'waiting';
      return next;
    });
    if (paidNow) {
      resultHaptic('success');
      loadWallet();
    } else {
      pollStartedAtRef.current = Date.now();
      setPollTimedOut(false);
    }
  };

  const payWithWallet = (index: number) => {
    const p = payments[index];
    if (!p) return;
    Alert.alert(
      'จ่ายด้วยกระเป๋าเงิน?',
      `ระบบจะตัดเงิน ${formatBaht(p.amount, { decimals: 2 })} จากกระเป๋าทันที${
        walletBalance !== null ? `\nยอดคงเหลือ ${formatBaht(walletBalance, { decimals: 2 })}` : ''
      }\nถ้าโอนพร้อมเพย์ไปแล้ว ไม่ต้องกดนะ`,
      [
        { text: 'ยังก่อน', style: 'cancel' },
        { text: 'ยืนยันจ่าย', onPress: () => repay(index, 'wallet') },
      ]
    );
  };

  // =====================================================
  // ออกจากหน้า (ขั้นจ่ายเงิน/สำเร็จ ไม่ย้อนกลับไปตะกร้าที่ว่างแล้ว)
  // =====================================================

  const orderIds = result?.order_ids ?? [];
  const goOrders = useCallback(() => {
    leavingRef.current = true;
    if (orderIds.length === 1) router.replace(`/order/${orderIds[0]}` as never);
    else router.replace('/(tabs)/orders' as never);
  }, [orderIds]);

  useEffect(() => {
    const unsubscribe = navigation.addListener('beforeRemove', (event: any) => {
      if (leavingRef.current || step === 'form') return;
      event.preventDefault();
      if (step === 'payment' && anyWaiting) {
        Alert.alert('ออกจากหน้าชำระเงิน?', 'ชำระภายหลังได้ที่หน้าคำสั่งซื้อ (ก่อน QR หมดอายุ)', [
          { text: 'อยู่ต่อ', style: 'cancel' },
          { text: 'ไปหน้าคำสั่งซื้อ', onPress: goOrders },
        ]);
      } else {
        goOrders();
      }
    });
    return unsubscribe;
  }, [navigation, step, anyWaiting, goOrders]);

  // =====================================================
  // render
  // =====================================================

  if (!isAuthenticated) {
    return (
      <Screen title="ชำระเงิน" scroll={false}>
        <EmptyState icon="🔐" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  // ---------- สำเร็จ ----------
  if (step === 'done' && result) {
    const status = result.payment_status;
    const title =
      status === 'cod' ? 'สั่งซื้อสำเร็จ!' : status === 'paid' || allPaid ? 'ชำระเงินเรียบร้อย!' : 'สร้างคำสั่งซื้อแล้ว';
    const message =
      status === 'cod'
        ? 'เตรียมเงินสดไว้จ่ายไรเดอร์ตอนรับของนะ ร้านจะเริ่มเตรียมสินค้าเร็วๆ นี้'
        : 'ร้านได้รับคำสั่งซื้อแล้ว ติดตามสถานะได้ที่หน้าคำสั่งซื้อ';
    return (
      <Screen title="สั่งซื้อสำเร็จ" onBack={goOrders}>
        <Card3D gradientBorder padding={spacing.xl} style={styles.block}>
          <Text style={styles.bigIcon}>🎉</Text>
          <Text style={[typography.h1, styles.centerText, { color: colors.textStrong }]}>{title}</Text>
          <Text style={[typography.body, styles.centerText, styles.gapTop, { color: colors.textMuted }]}>{message}</Text>
          <PriceText amount={result.total_amount} decimals={2} size="xl" tone="gold" style={[styles.centerText, styles.gapTop]} />
          {typeof result.wallet_balance === 'number' && (
            <Text style={[typography.caption, styles.centerText, { color: colors.textMuted }]}>
              ยอดคงเหลือในกระเป๋า {formatBaht(result.wallet_balance, { decimals: 2 })}
            </Text>
          )}
        </Card3D>

        {result.orders.map((order) => (
          <Card3D
            key={order.id}
            onPress={() => {
              leavingRef.current = true;
              router.replace(`/order/${order.id}` as never);
            }}
            padding={spacing.md}
            radius={radii.lg}
            shadow="sm"
            style={styles.block}
            accessibilityLabel={`ดูคำสั่งซื้อ ${order.order_number}`}
          >
            <View style={styles.rowBetween}>
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{order.store?.name || 'คำสั่งซื้อ'}</Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>{order.order_number}</Text>
              </View>
              <PriceText amount={order.total_amount} size="md" tone="strong" />
              <Text style={[typography.h2, { color: colors.textFaint }]}> ›</Text>
            </View>
          </Card3D>
        ))}

        <Button3D title="ดูคำสั่งซื้อ" icon="🧾" size="lg" fullWidth onPress={goOrders} style={styles.gapTop} />
        <Button3D
          title="ช้อปต่อ"
          variant="secondary"
          size="lg"
          fullWidth
          onPress={() => {
            leavingRef.current = true;
            router.replace('/(tabs)/shop' as never);
          }}
          style={styles.gapTop}
        />
      </Screen>
    );
  }

  // ---------- จ่ายพร้อมเพย์ ----------
  if (step === 'payment' && result) {
    return (
      <Screen title="สแกนจ่ายพร้อมเพย์" subtitle={`${payments.length} รายการที่ต้องชำระ`} onBack={() => navigation.goBack()}>
        {payments.length > 1 && (
          <Card3D variant="flat" padding={spacing.md} style={styles.block}>
            <Text style={[typography.bodySm, { color: colors.text }]}>
              สั่งจากหลายร้าน แต่ละร้านมี QR ของตัวเอง โอนให้ครบทุก QR นะ
            </Text>
          </Card3D>
        )}
        {payments.map((p, index) => {
          const key = paymentKey(p, index);
          const state = payStates[key] || 'waiting';
          const order = result.orders.find((o) => o.id === p.order_id);
          return (
            <View key={key}>
              <PromptPayQR
                payment={p}
                state={state}
                title={order?.store?.name ? `ร้าน ${order.store.name}` : `คำสั่งซื้อ ${order?.order_number || ''}`}
                onRenew={() => repay(index, 'promptpay')}
              />
              {state !== 'paid' && (
                <Button3D
                  title="จ่ายด้วยกระเป๋าเงินแทน"
                  icon="👛"
                  variant="ghost"
                  size="sm"
                  onPress={() => payWithWallet(index)}
                  style={styles.walletAlt}
                />
              )}
            </View>
          );
        })}

        {pollTimedOut && anyWaiting && (
          <Card3D variant="flat" padding={spacing.md} style={styles.block}>
            <Text style={[typography.bodySm, { color: colors.warning }]}>
              ยังไม่พบยอดโอน ถ้าโอนแล้วกดตรวจสอบอีกครั้งได้เลย
            </Text>
            <Button3D
              title="ตรวจสอบอีกครั้ง"
              icon="🔄"
              size="sm"
              onPress={async () => {
                pollStartedAtRef.current = Date.now();
                setPollTimedOut(false);
                await pollOnce();
              }}
              style={styles.gapTop}
            />
          </Card3D>
        )}

        {anyWaiting && (
          <View style={styles.waitingRow}>
            <ActivityIndicator color={colors.gold} />
            <Text style={[typography.caption, { color: colors.textMuted }]}>กำลังรอยืนยันยอดโอนอัตโนมัติ...</Text>
          </View>
        )}

        <Button3D title="ชำระภายหลัง / ดูคำสั่งซื้อ" variant="secondary" size="md" fullWidth onPress={goOrders} style={styles.gapTop} />
      </Screen>
    );
  }

  // ---------- ฟอร์ม ----------
  if (!quote) {
    return (
      <Screen title="ชำระเงิน" scroll={false}>
        {quoteError ? (
          <EmptyState variant="error" message={quoteError} onAction={fetchQuote} />
        ) : (
          <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
        )}
      </Screen>
    );
  }

  if (quote.items.length === 0) {
    return (
      <Screen title="ชำระเงิน" scroll={false}>
        <EmptyState
          icon="🛒"
          title="ตะกร้ายังว่างอยู่"
          message="เลือกสินค้าก่อนแล้วค่อยมาชำระเงินนะ"
          actionLabel="ไปช้อปเลย"
          onAction={() => router.replace('/(tabs)/shop' as never)}
        />
      </Screen>
    );
  }

  const summary = quote.summary;
  const submitDisabled = quoteLoading || submitting || availableItems.length === 0;

  return (
    <View style={[styles.root, { backgroundColor: colors.background }]}>
      <Screen title="ชำระเงิน" subtitle={`${summary.available_items_count} ชิ้น`} contentStyle={{ paddingBottom: 150 + insets.bottom }}>
        {!!notice && (
          <Card3D variant="flat" padding={spacing.md} style={styles.block}>
            <View style={styles.rowBetween}>
              <Text style={[typography.bodySm, styles.flex, { color: colors.info }]}>ℹ️ {notice}</Text>
              <Pressable onPress={() => setNotice(null)} accessibilityRole="button" accessibilityLabel="ปิดข้อความ" hitSlop={10}>
                <Text style={[typography.h3, { color: colors.textFaint }]}>✕</Text>
              </Pressable>
            </View>
          </Card3D>
        )}

        {/* ---------- ที่อยู่ ---------- */}
        <SectionHeader title="ที่อยู่จัดส่ง" icon="📍" actionLabel={addresses.length > 0 ? 'เปลี่ยน' : undefined} onAction={() => setAddressSheet(true)} />
        {selectedAddress ? (
          <Card3D onPress={() => setAddressSheet(true)} padding={spacing.lg} style={styles.block} accessibilityLabel="เปลี่ยนที่อยู่จัดส่ง">
            <View style={styles.rowBetween}>
              <Text style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]}>
                {selectedAddress.recipient_name} · {selectedAddress.phone_number}
              </Text>
              {selectedAddress.is_default && <Pill label="หลัก" tone="gold" />}
            </View>
            <Text style={[typography.bodySm, styles.gapTopSm, { color: colors.text }]}>{selectedAddress.full_address}</Text>
            {!selectedAddress.has_location && (
              <View style={styles.pinWarn}>
                <Text style={[typography.caption, styles.flex, { color: colors.warning }]}>
                  📍 ยังไม่ปักหมุด — ปักหมุดแล้วเลือกส่งด้วยไรเดอร์ได้
                </Text>
                <Button3D
                  title="ปักหมุด"
                  size="sm"
                  variant="secondary"
                  onPress={() => router.push(`/addresses/edit?id=${selectedAddress.id}` as never)}
                />
              </View>
            )}
          </Card3D>
        ) : (
          <Card3D gradientBorder padding={spacing.lg} style={styles.block}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ยังไม่มีที่อยู่จัดส่ง</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>เพิ่มที่อยู่ครั้งเดียว ใช้ได้ทุกครั้งที่สั่ง</Text>
            <Button3D
              title="เพิ่มที่อยู่"
              icon="➕"
              size="md"
              onPress={() => router.push('/addresses/edit' as never)}
              style={styles.gapTop}
            />
          </Card3D>
        )}

        {/* ---------- วิธีจัดส่ง ---------- */}
        <SectionHeader title="วิธีจัดส่ง" icon="🚚" style={styles.section} />
        <OptionCard
          icon="📦"
          title="ส่งพัสดุ"
          subtitle="ร้านแพ็กส่งผ่านบริษัทขนส่ง พร้อมเลขติดตาม"
          selected={delivery === 'parcel'}
          onPress={() => setDelivery('parcel')}
          right={
            riderInfo.parcelFee > 0 ? (
              <PriceText amount={riderInfo.parcelFee} size="sm" tone="strong" />
            ) : (
              <Text style={[typography.caption, { color: colors.success }]}>ส่งฟรี</Text>
            )
          }
        />
        <OptionCard
          icon="🛵"
          title="ส่งด้วยไรเดอร์"
          subtitle={
            riderInfo.available
              ? `ประมาณ ${riderInfo.minutes || '-'} นาที · ${riderInfo.distance ? riderInfo.distance.toFixed(1) : '-'} กม.`
              : undefined
          }
          selected={delivery === 'rider'}
          disabled={!riderInfo.available}
          disabledReason={riderInfo.reason || 'ร้านนี้ยังไม่เปิดส่งด้วยไรเดอร์'}
          onPress={() => setDelivery('rider')}
          right={riderInfo.available ? <PriceText amount={riderInfo.fee} size="sm" tone="strong" /> : null}
        />

        {/* ---------- วิธีชำระเงิน ---------- */}
        <SectionHeader title="วิธีชำระเงิน" icon="💳" style={styles.section} />
        <OptionCard
          icon="👛"
          title="กระเป๋าเงิน"
          subtitle={walletBalance !== null ? `ยอดคงเหลือ ${formatBaht(walletBalance, { decimals: 2 })}` : 'ตัดเงินจากกระเป๋าทันที'}
          selected={payment === 'wallet'}
          onPress={() => setPayment('wallet')}
          right={walletShort ? <Pill label="ไม่พอ" tone="danger" /> : null}
        />
        {walletShort && (
          <Button3D
            title="เติมเงินเข้ากระเป๋า"
            icon="➕"
            variant="secondary"
            size="sm"
            onPress={() => router.push('/wallet-topup' as never)}
            style={styles.topup}
          />
        )}
        <OptionCard
          icon="📱"
          title="พร้อมเพย์ (สแกน QR)"
          subtitle="โอนผ่านแอปธนาคาร ระบบยืนยันให้อัตโนมัติ"
          selected={payment === 'promptpay'}
          disabled={promptpayEnabled === false}
          disabledReason="พร้อมเพย์ปิดใช้งานชั่วคราว"
          onPress={() => setPayment('promptpay')}
        />
        <OptionCard
          icon="💵"
          title="เก็บเงินปลายทาง"
          subtitle="จ่ายเงินสดกับไรเดอร์ตอนรับของ"
          selected={payment === 'cod'}
          disabled={!codAvailable}
          disabledReason={delivery !== 'rider' ? 'ใช้ได้เมื่อเลือกส่งด้วยไรเดอร์' : riderInfo.codReason || 'ออเดอร์นี้เก็บเงินปลายทางไม่ได้'}
          onPress={() => setPayment('cod')}
        />

        {/* ---------- คูปอง ---------- */}
        <SectionHeader title="โค้ดส่วนลด" icon="🎟️" style={styles.section} />
        <Card3D padding={spacing.lg} style={styles.block}>
          {appliedCoupon ? (
            <View style={styles.rowBetween}>
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.success }]}>✅ ใช้โค้ด {appliedCoupon}</Text>
                {quote.coupon && quote.coupon.discount > 0 && (
                  <Text style={[typography.caption, { color: colors.textMuted }]}>
                    ลด {formatBaht(quote.coupon.discount, { decimals: 2 })}
                  </Text>
                )}
              </View>
              <Button3D title="เอาออก" variant="ghost" size="sm" onPress={removeCoupon} />
            </View>
          ) : (
            <View style={styles.couponRow}>
              <Field
                label="โค้ด"
                value={couponInput}
                onChangeText={(v) => {
                  setCouponInput(v.replace(/\s/g, '').slice(0, 50));
                  if (couponError) setCouponError(null);
                }}
                placeholder="ใส่โค้ดส่วนลด"
                autoCapitalize="characters"
                autoCorrect={false}
                error={couponError}
                containerStyle={styles.flex}
                returnKeyType="done"
                onSubmitEditing={applyCoupon}
              />
              <Button3D title="ใช้โค้ด" size="md" onPress={applyCoupon} disabled={!couponInput.trim()} style={styles.couponButton} />
            </View>
          )}
        </Card3D>

        {/* ---------- รายการตามร้าน ---------- */}
        <SectionHeader title="สรุปคำสั่งซื้อ" icon="🧾" style={styles.section} />
        {quote.stores.map((store) => {
          const items = availableItems.filter((i) => (i.store?.id ?? null) === store.store_id);
          return (
            <Card3D key={store.key} padding={spacing.lg} style={styles.block}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>🏪 {store.store_name}</Text>
              {items.map((item) => (
                <View key={item.id} style={styles.itemLine}>
                  <Text numberOfLines={2} style={[typography.bodySm, styles.flex, { color: colors.text }]}>
                    {item.name} × {item.quantity}
                  </Text>
                  <PriceText amount={item.line_total} size="sm" tone="strong" />
                </View>
              ))}
              <View style={[styles.storeTotals, { borderTopColor: colors.divider }]}>
                <View style={styles.rowBetween}>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>
                    ค่าส่ง ({store.delivery_method === 'rider' ? 'ไรเดอร์' : 'พัสดุ'})
                  </Text>
                  {store.shipping_fee > 0 ? (
                    <PriceText amount={store.shipping_fee} size="xs" tone="default" />
                  ) : (
                    <Text style={[typography.caption, { color: colors.success }]}>ฟรี</Text>
                  )}
                </View>
                {store.discount > 0 && (
                  <View style={styles.rowBetween}>
                    <Text style={[typography.caption, { color: colors.textMuted }]}>ส่วนลด</Text>
                    <PriceText amount={-store.discount} size="xs" tone="success" />
                  </View>
                )}
                <View style={styles.rowBetween}>
                  <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>รวมร้านนี้</Text>
                  <PriceText amount={store.total} size="md" tone="strong" />
                </View>
              </View>
            </Card3D>
          );
        })}
        {summary.unavailable_count > 0 && (
          <Card3D variant="flat" padding={spacing.md} style={styles.block}>
            <Text style={[typography.bodySm, { color: colors.danger }]}>
              ⚠️ มีสินค้า {summary.unavailable_count} รายการที่สั่งไม่ได้ เอาออกจากตะกร้าก่อนนะ
            </Text>
            <Button3D title="ไปที่ตะกร้า" size="sm" variant="secondary" onPress={() => router.replace('/cart')} style={styles.gapTopSm} />
          </Card3D>
        )}

        {/* ---------- หมายเหตุ ---------- */}
        <Card3D padding={spacing.lg} style={styles.block}>
          <Field
            label="หมายเหตุถึงร้าน (ถ้ามี)"
            value={note}
            onChangeText={(v) => setNote(v.slice(0, 500))}
            placeholder="เช่น ขอถุงแยก, โทรก่อนส่ง"
            multiline
            maxLength={500}
            containerStyle={styles.noTop}
          />
        </Card3D>

        {/* ---------- ยอดรวม ---------- */}
        <Card3D variant="inset" padding={spacing.lg} style={styles.block}>
          <View style={styles.rowBetween}>
            <Text style={[typography.body, { color: colors.text }]}>ค่าสินค้า</Text>
            <PriceText amount={summary.subtotal} size="sm" tone="strong" />
          </View>
          <View style={[styles.rowBetween, styles.gapTopSm]}>
            <Text style={[typography.body, { color: colors.text }]}>ค่าจัดส่ง</Text>
            {summary.shipping_fee > 0 ? (
              <PriceText amount={summary.shipping_fee} size="sm" tone="strong" />
            ) : (
              <Text style={[typography.bodyStrong, { color: colors.success }]}>ส่งฟรี</Text>
            )}
          </View>
          {summary.discount > 0 && (
            <View style={[styles.rowBetween, styles.gapTopSm]}>
              <Text style={[typography.body, { color: colors.text }]}>ส่วนลด</Text>
              <PriceText amount={-summary.discount} size="sm" tone="success" />
            </View>
          )}
          <View style={[styles.rowBetween, styles.grand, { borderTopColor: colors.divider }]}>
            <Text style={[typography.h3, { color: colors.textStrong }]}>ยอดชำระ</Text>
            <PriceText amount={summary.grand_total} decimals={2} size="lg" tone="gold" />
          </View>
        </Card3D>
      </Screen>

      {/* ---------- แถบยืนยัน ---------- */}
      <View
        style={[
          styles.bottomBar,
          { backgroundColor: colors.card, paddingBottom: Math.max(insets.bottom, spacing.md) },
          clayShadowStyle('md', colors.shadowDark, colors.shadowLight),
        ]}
      >
        <View style={styles.flex}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>
            {quoteLoading ? 'กำลังคำนวณยอด...' : `จ่ายด้วย${PAYMENT_LABEL[payment]}`}
          </Text>
          {quoteLoading ? (
            <ActivityIndicator color={colors.gold} style={styles.totalLoader} />
          ) : (
            <PriceText amount={summary.grand_total} decimals={2} size="lg" tone="gold" />
          )}
        </View>
        <Button3D
          title="ยืนยันสั่งซื้อ"
          icon="✅"
          size="lg"
          loading={submitting}
          loadingText="กำลังสั่งซื้อ..."
          disabled={submitDisabled}
          onPress={confirmAndSubmit}
          style={styles.submit}
        />
      </View>

      {/* ---------- เลือกที่อยู่ ---------- */}
      <FormSheet
        visible={addressSheet}
        icon="📍"
        title="เลือกที่อยู่จัดส่ง"
        onClose={() => setAddressSheet(false)}
        cancelLabel="ปิด"
      >
        {addresses.map((a) => (
          <OptionCard
            key={a.id}
            icon={a.has_location ? '📍' : '🏠'}
            title={`${a.recipient_name}${a.is_default ? ' (หลัก)' : ''}`}
            subtitle={a.full_address}
            selected={a.id === selectedAddressId}
            onPress={() => {
              setSelectedAddressId(a.id);
              setAddressSheet(false);
            }}
          />
        ))}
        <View style={styles.sheetButtons}>
          <Button3D
            title="เพิ่มที่อยู่ใหม่"
            icon="➕"
            size="md"
            onPress={() => {
              setAddressSheet(false);
              router.push('/addresses/edit' as never);
            }}
            style={styles.flex}
          />
          <Button3D
            title="จัดการที่อยู่"
            variant="secondary"
            size="md"
            onPress={() => {
              setAddressSheet(false);
              router.push('/addresses' as never);
            }}
            style={styles.flex}
          />
        </View>
      </FormSheet>
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
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
    marginTop: spacing.md,
  },
  rowBetween: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  centerText: {
    textAlign: 'center',
    alignSelf: 'center',
  },
  gapTop: {
    marginTop: spacing.md,
  },
  gapTopSm: {
    marginTop: spacing.xs,
  },
  bigIcon: {
    fontSize: 56,
    textAlign: 'center',
    marginBottom: spacing.sm,
  },
  option: {
    marginBottom: spacing.sm,
  },
  dimmed: {
    opacity: 0.6,
  },
  optionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  radio: {
    width: 22,
    height: 22,
    borderRadius: 11,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  radioDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
  },
  optionIcon: {
    fontSize: 22,
  },
  pinWarn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  topup: {
    alignSelf: 'flex-start',
    marginBottom: spacing.sm,
  },
  couponRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: spacing.sm,
  },
  couponButton: {
    marginBottom: spacing.xxs,
  },
  noTop: {
    marginTop: 0,
  },
  itemLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  storeTotals: {
    borderTopWidth: 1,
    marginTop: spacing.md,
    paddingTop: spacing.sm,
    gap: spacing.xs,
  },
  grand: {
    borderTopWidth: 1,
    marginTop: spacing.md,
    paddingTop: spacing.md,
  },
  walletAlt: {
    alignSelf: 'center',
    marginTop: -spacing.xs,
    marginBottom: spacing.md,
  },
  waitingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  bottomBar: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
  },
  totalLoader: {
    alignSelf: 'flex-start',
    marginTop: spacing.xs,
  },
  submit: {
    minWidth: 170,
  },
  sheetButtons: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
});
