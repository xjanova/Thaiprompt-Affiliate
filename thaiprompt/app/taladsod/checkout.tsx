/**
 * ชำระเงินตลาดสด (ร้านเดียว) — POST /fresh-market/orders {seller_id, ...} แบบ "จากตะกร้า"
 *
 * - รับของ: ไรเดอร์ส่ง (ปักหมุดด้วย GPS / ที่อยู่ที่บันทึกไว้ / แตะแผนที่) หรือ นัดรับที่ร้าน
 * - ค่าส่งไรเดอร์: GET /cart/quote ทุกครั้งที่หมุดเปลี่ยน (หน่วง 500ms) · นอกระยะ → เสนอนัดรับแทน
 * - จ่าย: กระเป๋าเงิน / เก็บเงินปลายทาง (ตาม config ของระบบ)
 * - ไรเดอร์ส่ง: ต้องได้ค่าส่ง (quote ready + ส่งได้) ก่อนถึงสั่งได้ — คำนวณไม่สำเร็จ = ปุ่มกลายเป็น "คำนวณค่าส่งใหม่"
 *   (กันกล่องยืนยันตัดเงินแสดงยอดที่ยังไม่รวมค่าส่ง ขณะที่ server คิดค่าส่งเองแล้วตัดจริง)
 * - กันสั่งซ้ำ: ปุ่มล็อกระหว่างส่ง + server ล้างตะกร้าร้านนั้นในธุรกรรมเดียว (กดซ้ำ = CART_EMPTY)
 *   เน็ตหลุด/ตอบช้า/CART_EMPTY หลังกดไปแล้ว → เช็คออเดอร์ล่าสุดของร้านนี้ก่อน ถ้าสร้างแล้วถือว่าสำเร็จ
 *   ไม่เจอ → ห้ามบอกว่า "ยังไม่ตัดเงิน" (server อาจยังทำธุรกรรมอยู่) ให้เช็คออเดอร์ของฉันก่อนกดใหม่
 * - SHOP_CLOSED / OUT_OF_DELIVERY_AREA / INSUFFICIENT_BALANCE ฯลฯ → ข้อความไทย + ทางไปต่อ
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { useTaladsodCartStore } from '@/stores/taladsodCartStore';
import {
  Button3D,
  Card3D,
  Chip,
  EmptyState,
  LiveMap,
  PriceText,
  Screen,
  SectionHeader,
  formatBaht,
  resultHaptic,
  selectionHaptic,
  type LiveMapMarker,
} from '@/components/ui';
import { Field } from '@/components/shop';
import { useBuyerLocation, useMountedRef } from '@/components/taladsod';
import {
  getFmCartQuote,
  getFmOrders,
  getShop,
  getTaladsodConfig,
  placeFmOrderFromCart,
  type FmConfig,
  type FmDeliveryType,
  type FmOrder,
  type FmPaymentMethod,
  type FmQuote,
  type FmShopDetail,
} from '@/services/api/taladsodApi';
import { getAddresses, type Address } from '@/services/api/shopApi';
import { formatDistance, formatDuration } from '@/services/location';
import { useTheme, clayShadowStyle, radii, spacing, typography } from '@/theme';

type Pin = { latitude: number; longitude: number; source: 'gps' | 'saved' | 'map' };
type QuoteState = { state: 'idle' } | { state: 'loading' } | { state: 'ready'; quote: FmQuote } | { state: 'error'; message: string };

const QUOTE_DEBOUNCE_MS = 500;
/** TIMEOUT แล้ว server อาจยังทำธุรกรรมอยู่ → รอสักพักแล้วเช็คออเดอร์อีกรอบ */
const TIMEOUT_RECHECK_MS = 3000;
const ADDRESS_MIN = 5;
const NOTES_MAX = 500;

export default function TaladsodCheckoutScreen() {
  const params = useLocalSearchParams<{ seller_id?: string }>();
  const sellerId = /^\d+$/.test(String(params.seller_id || '')) ? Number(params.seller_id) : 0;
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const shopCart = useTaladsodCartStore((s) => s.cart?.shops.find((x) => x.seller_id === sellerId) || null);
  const cartLoaded = useTaladsodCartStore((s) => s.cart !== null);
  const mountedRef = useMountedRef();
  const location = useBuyerLocation();

  const [config, setConfig] = useState<FmConfig | null>(null);
  const [configError, setConfigError] = useState<string | null>(null);
  const [shop, setShop] = useState<FmShopDetail | null>(null);
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [deliveryType, setDeliveryType] = useState<FmDeliveryType>('rider');
  const [payment, setPayment] = useState<FmPaymentMethod>('wallet');
  const [pin, setPin] = useState<Pin | null>(null);
  const [selectedAddressId, setSelectedAddressId] = useState<number | null>(null);
  const [addressText, setAddressText] = useState('');
  const [notes, setNotes] = useState('');
  const [quote, setQuote] = useState<QuoteState>({ state: 'idle' });
  /** เพิ่มค่าเพื่อสั่งคำนวณค่าส่งใหม่ (หลังคำนวณไม่สำเร็จ) */
  const [quoteNonce, setQuoteNonce] = useState(0);
  const [placing, setPlacing] = useState(false);
  const [addressError, setAddressError] = useState<string | null>(null);
  /** สั่งสำเร็จแล้ว กำลังพาไปหน้าออเดอร์ (กันหน้า "ตะกร้าว่าง" แวบขึ้นมา) */
  const [done, setDone] = useState(false);

  const quoteReqRef = useRef(0);
  const baselineOrderIdRef = useRef<number | null>(null);
  const attemptsRef = useRef(0);
  const placingRef = useRef(false);
  const defaultsAppliedRef = useRef(false);

  // ---------- โหลดข้อมูลตั้งต้น ----------
  const loadConfig = useCallback(async () => {
    const res = await getTaladsodConfig();
    if (!mountedRef.current) return;
    if (res.success) {
      setConfig(res.data);
      setConfigError(null);
    } else {
      setConfigError(res.message);
    }
  }, [mountedRef]);

  useEffect(() => {
    if (!isAuthenticated || !sellerId) return;
    loadConfig();
    useTaladsodCartStore.getState().refresh().catch(() => {});
    getShop(sellerId).then((res) => {
      if (mountedRef.current && res.success) setShop(res.data);
    });
    getAddresses().then((res) => {
      if (mountedRef.current && res.success && Array.isArray(res.data)) setAddresses(res.data);
    });
    // ออเดอร์ล่าสุดก่อนกดสั่ง (ใช้ตรวจว่าคำสั่งที่เน็ตหลุดสร้างออเดอร์ไปแล้วหรือยัง)
    getFmOrders({ page: 1 }).then((res) => {
      // ตั้งค่าเฉพาะก่อนกดสั่งครั้งแรก (ถ้ากดไปแล้ว ผลนี้อาจรวมออเดอร์ใหม่ของเราเข้าไปแล้ว)
      if (mountedRef.current && res.success && attemptsRef.current === 0) {
        baselineOrderIdRef.current = res.data.orders.reduce((max, o) => Math.max(max, o.id), 0);
      }
    });
  }, [isAuthenticated, sellerId, loadConfig, mountedRef]);

  const pinnedAddresses = useMemo(
    () => addresses.filter((a) => a.has_location && Number.isFinite(Number(a.latitude)) && Number.isFinite(Number(a.longitude))),
    [addresses]
  );

  const chooseAddress = useCallback((a: Address) => {
    setSelectedAddressId(a.id);
    setPin({ latitude: Number(a.latitude), longitude: Number(a.longitude), source: 'saved' });
    setAddressText(a.full_address || [a.address_line_1, a.district, a.province].filter(Boolean).join(' '));
    setAddressError(null);
  }, []);

  // ค่าเริ่มต้นครั้งแรก: ไรเดอร์ถ้าเปิดใช้ · จ่ายด้วยกระเป๋าถ้ามี · ที่อยู่หลักที่ปักหมุดแล้ว
  useEffect(() => {
    if (!config || defaultsAppliedRef.current) return;
    defaultsAppliedRef.current = true;
    setDeliveryType(config.rider_enabled ? 'rider' : 'pickup');
    setPayment(config.payment_methods.includes('wallet') ? 'wallet' : config.payment_methods[0] || 'cod');
  }, [config]);

  useEffect(() => {
    if (pin || selectedAddressId || pinnedAddresses.length === 0) return;
    const preferred = pinnedAddresses.find((a) => a.is_default) || null;
    if (preferred) chooseAddress(preferred);
  }, [pinnedAddresses, pin, selectedAddressId, chooseAddress]);

  // ---------- ค่าส่ง ----------
  const cartLines = shopCart?.lines_count ?? 0;
  const cartSubtotal = shopCart?.subtotal ?? 0;
  useEffect(() => {
    if (deliveryType !== 'rider' || !pin || !sellerId || cartLines === 0) {
      setQuote({ state: 'idle' });
      return undefined;
    }
    const reqId = ++quoteReqRef.current;
    setQuote({ state: 'loading' });
    const timer = setTimeout(async () => {
      const res = await getFmCartQuote(sellerId, pin.latitude, pin.longitude);
      if (!mountedRef.current || reqId !== quoteReqRef.current) return;
      setQuote(res.success ? { state: 'ready', quote: res.data } : { state: 'error', message: res.message });
    }, QUOTE_DEBOUNCE_MS);
    return () => clearTimeout(timer);
    // quote ขึ้นกับยอดในตะกร้าด้วย (grand_total) → คำนวณใหม่เมื่อยอดเปลี่ยน · quoteNonce = กดคำนวณใหม่
  }, [deliveryType, pin, sellerId, cartSubtotal, cartLines, mountedRef, quoteNonce]);

  const retryQuote = useCallback(() => {
    selectionHaptic();
    setQuoteNonce((n) => n + 1);
  }, []);

  const pinFromGps = async () => {
    const coords = await location.request('delivery');
    if (!coords || !mountedRef.current) return;
    selectionHaptic();
    setPin({ latitude: coords.latitude, longitude: coords.longitude, source: 'gps' });
    setSelectedAddressId(null);
  };

  // ---------- ยอดรวม ----------
  const subtotal = shopCart?.subtotal ?? 0;
  const fee = deliveryType === 'rider' && quote.state === 'ready' && quote.quote.available ? quote.quote.total_fee : 0;
  /** ไรเดอร์ส่งแต่ยังไม่รู้ค่าส่ง → ยอดรวมบนจอยังไม่รวมค่าส่ง (ห้ามใช้ยืนยันตัดเงิน) */
  const feePending = deliveryType === 'rider' && !(quote.state === 'ready' && quote.quote.available);
  const quoteFailed = deliveryType === 'rider' && !!pin && quote.state === 'error';
  const quotePending = deliveryType === 'rider' && !!pin && (quote.state === 'loading' || quote.state === 'idle');
  const total = Math.round((subtotal + fee) * 100) / 100;
  const methods: FmPaymentMethod[] = config?.payment_methods.length ? config.payment_methods : ['wallet', 'cod'];

  // ---------- กู้ผลเมื่อไม่แน่ใจว่าสั่งสำเร็จ ----------
  const findPlacedOrder = async (startedAt: number): Promise<FmOrder | null> => {
    const res = await getFmOrders({ page: 1 });
    if (!res.success) return null;
    const baseline = baselineOrderIdRef.current;
    return (
      res.data.orders.find((o) => {
        if (o.seller?.id !== sellerId) return false;
        if (baseline !== null) return o.id > baseline;
        const created = o.created_at ? new Date(o.created_at).getTime() : 0;
        return created >= startedAt - 5 * 60_000;
      }) || null
    );
  };

  const goToOrder = (order: FmOrder) => {
    setDone(true);
    // สั่นแจ้งสำเร็จที่เดียว: OrderPlacedOverlay ในหน้าออเดอร์ (?placed=1)
    useTaladsodCartStore.getState().refresh().catch(() => {});
    router.replace(`/taladsod/order/${order.id}?placed=1` as never);
  };

  // ---------- สั่งซื้อ ----------
  const validate = (): string | null => {
    if (!shopCart || shopCart.lines_count === 0) return 'ตะกร้าของร้านนี้ว่างแล้ว';
    if (!shopCart.can_checkout) return shopCart.is_open ? 'มีรายการที่สั่งไม่ได้ กลับไปแก้ในตะกร้าก่อนนะ' : 'ร้านปิดอยู่ตอนนี้';
    if (!methods.includes(payment)) return 'เลือกวิธีชำระเงินก่อนนะ';
    if (deliveryType === 'rider') {
      if (!pin) return 'ปักหมุดจุดส่งก่อนนะ';
      if (addressText.trim().length < ADDRESS_MIN) {
        setAddressError('บอกที่อยู่หรือจุดสังเกตให้ไรเดอร์หน่อย (อย่างน้อย 5 ตัวอักษร)');
        return 'กรอกที่อยู่/จุดสังเกตให้ไรเดอร์ก่อนนะ';
      }
      if (quote.state === 'loading' || quote.state === 'idle') return 'รอคำนวณค่าส่งสักครู่นะ';
      if (quote.state === 'error') return 'คำนวณค่าส่งไม่สำเร็จ กด "คำนวณค่าส่งใหม่" ก่อนนะ';
      if (!quote.quote.available) return quote.quote.message || 'จุดนี้อยู่นอกระยะส่ง ลองนัดรับที่ร้านแทนนะ';
    }
    return null;
  };

  /** ผลไม่แน่ชัด (เน็ตหลุด/ตอบช้า/server ขัดข้อง) — ห้ามบอกว่ายังไม่ตัดเงิน */
  const alertUncertain = (code: string, status: number) => {
    const ordersButton = { text: 'ดูออเดอร์ของฉัน', onPress: () => router.push('/taladsod/orders' as never) };
    if (status === 0 && code === 'NETWORK_ERROR') {
      Alert.alert(
        'ส่งคำสั่งซื้อไม่ได้',
        'เน็ตหลุดตอนส่งคำสั่งซื้อ เช็คอินเทอร์เน็ตแล้วกดสั่งอีกครั้งได้เลย\nถ้าออเดอร์เข้าไปแล้ว ระบบจะพาไปหน้าออเดอร์ให้เอง ไม่ตัดเงินซ้ำ',
        [{ text: 'ตกลง', style: 'cancel' }, ordersButton]
      );
      return;
    }
    Alert.alert(
      'ยังไม่แน่ใจว่าสั่งสำเร็จ',
      'ระบบตอบช้าจนยังไม่รู้ผลแน่ชัด ออเดอร์อาจเข้าไปแล้ว\nเช็คที่ "ออเดอร์ของฉัน" ก่อนกดสั่งอีกครั้งนะ',
      [{ text: 'อยู่หน้านี้', style: 'cancel' }, ordersButton]
    );
  };

  const handleFailure = async (code: string, message: string, status: number, startedAt: number) => {
    // ไม่แน่ใจว่า server สร้างออเดอร์ไปแล้วหรือยัง → เช็คก่อน
    const uncertain = status === 0 || status >= 500;
    if (uncertain || (code === 'CART_EMPTY' && attemptsRef.current > 1)) {
      let placed = await findPlacedOrder(startedAt);
      if (!mountedRef.current) return;
      // ตอบช้า = server อาจยัง commit ไม่เสร็จตอนเช็ครอบแรก → รอแล้วเช็คอีกรอบ
      if (!placed && code === 'TIMEOUT') {
        await new Promise((resolve) => setTimeout(resolve, TIMEOUT_RECHECK_MS));
        if (!mountedRef.current) return;
        placed = await findPlacedOrder(startedAt);
        if (!mountedRef.current) return;
      }
      if (placed) {
        goToOrder(placed);
        return;
      }
    }

    resultHaptic('error');
    switch (code) {
      case 'SHOP_CLOSED':
        Alert.alert('ร้านเพิ่งปิด', 'ร้านปิดรับออเดอร์แล้ว ติดตามร้านไว้ ร้านเปิดเมื่อไหร่เราจะแจ้งทันที', [
          { text: 'ไว้ก่อน', style: 'cancel' },
          { text: 'ไปหน้าร้าน', onPress: () => router.replace(`/taladsod/shop/${sellerId}` as never) },
        ]);
        useTaladsodCartStore.getState().refresh().catch(() => {});
        return;
      case 'OUT_OF_DELIVERY_AREA':
        Alert.alert('อยู่นอกระยะส่ง', message, [
          { text: 'เปลี่ยนหมุด', style: 'cancel' },
          { text: 'นัดรับที่ร้านแทน', onPress: () => setDeliveryType('pickup') },
        ]);
        return;
      case 'INSUFFICIENT_BALANCE':
        Alert.alert('ยอดในกระเป๋าไม่พอ', message, [
          { text: 'ไว้ก่อน', style: 'cancel' },
          ...(methods.includes('cod') ? [{ text: 'จ่ายปลายทางแทน', onPress: () => setPayment('cod') }] : []),
          { text: 'เติมเงิน', onPress: () => router.push('/wallet-topup' as never) },
        ]);
        return;
      case 'COD_NOT_AVAILABLE':
        Alert.alert('ยังเก็บเงินปลายทางไม่ได้', message, [{ text: 'จ่ายด้วยกระเป๋าเงิน', onPress: () => setPayment('wallet') }]);
        return;
      case 'WALLET_INACTIVE':
      case 'PAYMENT_METHOD_DISABLED':
        Alert.alert('เปลี่ยนวิธีชำระเงินนะ', message);
        loadConfig();
        return;
      case 'CART_EMPTY':
        Alert.alert('ตะกร้าร้านนี้ว่างแล้ว', 'ไม่มีรายการให้สั่งแล้ว ลองเลือกเมนูใหม่นะ');
        useTaladsodCartStore.getState().refresh().catch(() => {});
        router.replace('/taladsod/cart' as never);
        return;
      case 'CART_CHANGED':
      case 'OUT_OF_STOCK':
      case 'LISTING_UNAVAILABLE':
      case 'LISTING_NOT_FOUND':
      case 'OPTION_REQUIRED':
      case 'OPTION_LIMIT':
      case 'OPTION_UNAVAILABLE':
      case 'INVALID_OPTION':
      case 'INVALID_ITEM':
      case 'INVALID_QUANTITY':
        useTaladsodCartStore.getState().refresh().catch(() => {});
        Alert.alert('รายการในตะกร้าเปลี่ยนไป', message, [
          { text: 'อยู่หน้านี้', style: 'cancel' },
          { text: 'กลับไปตะกร้า', onPress: () => router.replace('/taladsod/cart' as never) },
        ]);
        return;
      case 'DELIVERY_LOCATION_REQUIRED':
        Alert.alert('ปักหมุดจุดส่งก่อนนะ', message);
        return;
      default:
        if (uncertain) {
          alertUncertain(code, status);
          return;
        }
        Alert.alert('สั่งซื้อไม่สำเร็จ', message);
    }
  };

  const submit = async () => {
    if (placingRef.current) return;
    const problem = validate();
    if (problem) {
      resultHaptic('warning');
      if (quoteFailed) {
        Alert.alert('ยังสั่งไม่ได้', problem, [
          { text: 'ไว้ก่อน', style: 'cancel' },
          { text: 'คำนวณค่าส่งใหม่', onPress: retryQuote },
        ]);
        return;
      }
      Alert.alert('ยังสั่งไม่ได้', problem);
      return;
    }
    placingRef.current = true;
    setPlacing(true);
    attemptsRef.current += 1;
    const startedAt = Date.now();
    try {
      const res = await placeFmOrderFromCart({
        seller_id: sellerId,
        delivery_type: deliveryType,
        payment_method: payment,
        delivery_address: deliveryType === 'rider' ? addressText.trim() : undefined,
        buyer_latitude: deliveryType === 'rider' ? pin?.latitude : undefined,
        buyer_longitude: deliveryType === 'rider' ? pin?.longitude : undefined,
        delivery_notes: notes.trim() || undefined,
      });
      if (!mountedRef.current) return;
      if (res.success && res.data.id > 0) {
        goToOrder(res.data);
        return;
      }
      if (!res.success) await handleFailure(res.code, res.message, res.status, startedAt);
    } finally {
      placingRef.current = false;
      if (mountedRef.current) setPlacing(false);
    }
  };

  const confirmAndSubmit = () => {
    if (payment === 'wallet' && !validate()) {
      Alert.alert('ยืนยันสั่งซื้อ?', `ตัดเงินจากกระเป๋า ${formatBaht(total, { decimals: 2 })}\nเปลี่ยนใจได้ก่อนร้านรับออเดอร์ ยกเลิกแล้วคืนเงินเข้ากระเป๋าทันที`, [
        { text: 'ยังก่อน', style: 'cancel' },
        { text: 'ยืนยันสั่ง', onPress: () => submit() },
      ]);
      return;
    }
    submit();
  };

  // ---------- render ----------
  if (!isAuthenticated) {
    return (
      <Screen title="ยืนยันคำสั่งซื้อ" scroll={false}>
        <EmptyState icon="🔐" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (done) {
    return (
      <Screen title="ยืนยันคำสั่งซื้อ" scroll={false}>
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </Screen>
    );
  }

  if (!sellerId || (cartLoaded && !shopCart && !placing)) {
    return (
      <Screen title="ยืนยันคำสั่งซื้อ" scroll={false}>
        <EmptyState
          icon="🧺"
          title="ไม่มีรายการของร้านนี้ในตะกร้า"
          message="อาจสั่งไปแล้วหรือเอาออกจากตะกร้าแล้ว"
          actionLabel="ไปที่ตะกร้า"
          onAction={() => router.replace('/taladsod/cart' as never)}
          secondaryActionLabel="ดูออเดอร์ของฉัน"
          onSecondaryAction={() => router.replace('/taladsod/orders' as never)}
        />
      </Screen>
    );
  }

  if (!shopCart || (!config && !configError)) {
    return (
      <Screen title="ยืนยันคำสั่งซื้อ" scroll={false}>
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </Screen>
    );
  }

  const shopName = shopCart.seller?.shop_name || shop?.shop_name || 'ร้านตลาดสด';
  const shopLoc = shop?.presence.location || null;
  const barHeight = 96 + insets.bottom;
  const riderEnabled = config?.rider_enabled !== false;

  const markers: LiveMapMarker[] = [];
  if (deliveryType === 'rider' && pin) {
    markers.push({ id: 'pin', kind: 'home', latitude: pin.latitude, longitude: pin.longitude, label: 'จุดส่ง' });
  }
  if (shopLoc) markers.push({ id: 'shop', kind: 'shop', latitude: shopLoc.latitude, longitude: shopLoc.longitude, label: shopName });

  const quoteBlock = () => {
    if (deliveryType !== 'rider' || !pin) return null;
    if (quote.state === 'loading' || quote.state === 'idle') {
      return (
        <View style={styles.quoteRow}>
          <ActivityIndicator size="small" color={colors.gold} />
          <Text style={[typography.caption, { color: colors.textMuted }]}>กำลังคำนวณค่าส่ง…</Text>
        </View>
      );
    }
    if (quote.state === 'error') {
      return (
        <View style={[styles.quoteWarn, { backgroundColor: colors.dangerSoft }]}>
          <Text style={[typography.bodySm, { color: colors.danger }]}>⚠️ คำนวณค่าส่งไม่สำเร็จ · {quote.message}</Text>
          <Button3D title="คำนวณค่าส่งใหม่" icon="🔄" size="sm" variant="secondary" onPress={retryQuote} style={styles.gapTopSm} />
        </View>
      );
    }
    const q = quote.quote;
    if (!q.available) {
      return (
        <View style={[styles.quoteWarn, { backgroundColor: colors.warningSoft }]}>
          <Text style={[typography.bodySm, { color: colors.warning }]}>⚠️ {q.message || 'จุดนี้อยู่นอกระยะส่งของร้าน'}</Text>
          {q.code !== 'SHOP_CLOSED' && (
            <Button3D title="นัดรับที่ร้านแทน" icon="🛍️" size="sm" variant="secondary" onPress={() => setDeliveryType('pickup')} style={styles.gapTopSm} />
          )}
        </View>
      );
    }
    return (
      <View style={[styles.quoteOk, { backgroundColor: colors.successSoft }]}>
        <Text style={[typography.bodySm, styles.flex, { color: colors.success }]}>
          🛵 ส่งได้{q.distance_km !== null ? ` · ${formatDistance(q.distance_km)}` : ''}
          {q.estimated_duration_minutes ? ` · ~${formatDuration(q.estimated_duration_minutes)}` : ''}
        </Text>
        <PriceText amount={q.total_fee} size="sm" tone="success" />
      </View>
    );
  };

  return (
    <Screen title="ยืนยันคำสั่งซื้อ" subtitle={shopName} scroll={false}>
      {location.element}
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView
          style={styles.flex}
          contentContainerStyle={[styles.content, { paddingBottom: barHeight + spacing.xl }]}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          {/* ---------- วิธีรับของ ---------- */}
          <SectionHeader title="รับของยังไงดี" icon="📦" />
          <View style={styles.choiceRow}>
            <Card3D
              onPress={() => riderEnabled && setDeliveryType('rider')}
              disabled={!riderEnabled}
              gradientBorder={deliveryType === 'rider'}
              variant={deliveryType === 'rider' ? 'raised' : 'flat'}
              padding={spacing.md}
              radius={radii.lg}
              style={styles.flex}
              accessibilityLabel="ให้ไรเดอร์ส่ง"
              accessibilityRole="radio"
              accessibilityState={{ checked: deliveryType === 'rider' }}
            >
              <Text style={styles.choiceIcon}>🛵</Text>
              <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ไรเดอร์ส่ง</Text>
              <Text style={[typography.micro, { color: colors.textMuted }]}>{riderEnabled ? 'ส่งถึงหมุดของคุณ' : 'ยังไม่เปิดบริการ'}</Text>
            </Card3D>
            <Card3D
              onPress={() => setDeliveryType('pickup')}
              gradientBorder={deliveryType === 'pickup'}
              variant={deliveryType === 'pickup' ? 'raised' : 'flat'}
              padding={spacing.md}
              radius={radii.lg}
              style={styles.flex}
              accessibilityLabel="นัดรับที่ร้าน"
              accessibilityRole="radio"
              accessibilityState={{ checked: deliveryType === 'pickup' }}
            >
              <Text style={styles.choiceIcon}>🛍️</Text>
              <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>นัดรับที่ร้าน</Text>
              <Text style={[typography.micro, { color: colors.textMuted }]}>ไม่มีค่าส่ง</Text>
            </Card3D>
          </View>

          {deliveryType === 'rider' ? (
            <Card3D padding={spacing.lg} style={styles.block}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>📍 จุดส่ง</Text>
              {pinnedAddresses.length > 0 && (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.addrChips} style={styles.addrScroll}>
                  {pinnedAddresses.map((a) => (
                    <Chip
                      key={a.id}
                      label={`${a.recipient_name || 'ที่อยู่'} · ${a.address_line_1}`.slice(0, 36)}
                      icon={a.is_default ? '🏠' : '📌'}
                      size="sm"
                      selected={selectedAddressId === a.id}
                      onPress={() => chooseAddress(a)}
                    />
                  ))}
                </ScrollView>
              )}
              <Button3D
                title={pin?.source === 'gps' ? 'อัปเดตตำแหน่งตอนนี้' : 'ใช้ตำแหน่งตอนนี้ (GPS)'}
                icon="🧭"
                size="md"
                variant={pin ? 'secondary' : 'primary'}
                fullWidth
                loading={location.locating}
                loadingText="กำลังหาตำแหน่ง…"
                onPress={pinFromGps}
                style={styles.gapTop}
              />
              {pin ? (
                <LiveMap
                  markers={markers}
                  height={200}
                  draggableId="pin"
                  onPick={(c) => {
                    setPin({ ...c, source: 'map' });
                    setSelectedAddressId(null);
                  }}
                  openTargetId={null}
                  caption="แตะแผนที่หรือลากหมุด 🏠 เพื่อปรับจุดส่งให้ตรง"
                  style={styles.gapTop}
                />
              ) : (
                <Text style={[typography.caption, styles.gapTopSm, { color: colors.textMuted }]}>
                  ยังไม่มีหมุดจุดส่ง ใช้ GPS หรือเลือกที่อยู่ที่ปักหมุดไว้
                </Text>
              )}
              {quoteBlock()}
              <Field
                label="ที่อยู่ / จุดสังเกตให้ไรเดอร์"
                required
                placeholder="เช่น บ้านเลขที่ 12 ซอย 5 ประตูรั้วสีเขียว"
                value={addressText}
                onChangeText={(t) => {
                  setAddressText(t.slice(0, 500));
                  if (addressError) setAddressError(null);
                }}
                maxLength={500}
                multiline
                error={addressError}
                containerStyle={styles.gapTop}
              />
            </Card3D>
          ) : (
            <Card3D padding={spacing.lg} style={styles.block}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>🛍️ รับที่ร้าน</Text>
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>
                {shopLoc?.label || shop?.presence.location_label || 'ร้านจะแจ้งเมื่อพร้อมให้มารับ'}
              </Text>
              {shop?.is_mobile && (
                <Text style={[typography.caption, styles.gapTopSm, { color: colors.warning }]}>
                  🛺 ร้านเป็นรถเข็น อาจย้ายจุด เช็คตำแหน่งล่าสุดในหน้าออเดอร์ก่อนออกไปรับนะ
                </Text>
              )}
              {shopLoc && <LiveMap markers={markers} height={180} openTargetId="shop" style={styles.gapTop} />}
            </Card3D>
          )}

          <Field
            label={deliveryType === 'rider' ? 'ฝากถึงร้าน/ไรเดอร์ (ไม่บังคับ)' : 'ฝากถึงร้าน (ไม่บังคับ)'}
            placeholder={deliveryType === 'rider' ? 'เช่น โทรก่อนถึง ฝากไว้ที่ป้อม' : 'เช่น จะไปรับประมาณ 12 โมง'}
            value={notes}
            onChangeText={(t) => setNotes(t.slice(0, NOTES_MAX))}
            maxLength={NOTES_MAX}
            multiline
            containerStyle={styles.block}
          />

          {/* ---------- ชำระเงิน ---------- */}
          <SectionHeader title="จ่ายเงินยังไง" icon="💳" style={styles.section} />
          {configError && !config && (
            <Text style={[typography.caption, { color: colors.danger }]}>{configError}</Text>
          )}
          <View style={styles.choiceRow}>
            {methods.map((m) => (
              <Card3D
                key={m}
                onPress={() => setPayment(m)}
                gradientBorder={payment === m}
                variant={payment === m ? 'raised' : 'flat'}
                padding={spacing.md}
                radius={radii.lg}
                style={styles.flex}
                accessibilityLabel={m === 'wallet' ? 'จ่ายด้วยกระเป๋าเงิน' : 'เก็บเงินปลายทาง'}
                accessibilityRole="radio"
                accessibilityState={{ checked: payment === m }}
              >
                <Text style={styles.choiceIcon}>{m === 'wallet' ? '👛' : '💵'}</Text>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{m === 'wallet' ? 'กระเป๋าเงิน' : deliveryType === 'rider' ? 'เงินสดปลายทาง' : 'จ่ายสดที่ร้าน'}</Text>
                <Text style={[typography.micro, { color: colors.textMuted }]}>
                  {m === 'wallet' ? 'ตัดเงินทันที ยกเลิกได้คืนเงิน' : deliveryType === 'rider' ? 'จ่ายไรเดอร์ตอนรับของ' : 'จ่ายตอนไปรับของ'}
                </Text>
              </Card3D>
            ))}
          </View>

          {/* ---------- สรุป ---------- */}
          <SectionHeader title="สรุปรายการ" icon="🧾" style={styles.section} />
          <Card3D variant="inset" padding={spacing.lg}>
            {shopCart.items.map((line) => (
              <View key={line.id} style={styles.itemRow}>
                <Text style={[typography.bodySm, styles.qty, { color: colors.goldDeep }]}>{line.quantity}×</Text>
                <View style={styles.flex}>
                  <Text numberOfLines={2} style={[typography.bodySm, { color: colors.textStrong }]}>
                    {line.title}
                  </Text>
                  {!!line.options_label && (
                    <Text numberOfLines={1} style={[typography.micro, { color: colors.textMuted }]}>
                      {line.options_label}
                    </Text>
                  )}
                  {!!line.note && (
                    <Text numberOfLines={1} style={[typography.micro, { color: colors.textMuted }]}>
                      📝 {line.note}
                    </Text>
                  )}
                </View>
                <PriceText amount={line.line_total} size="sm" tone="strong" />
              </View>
            ))}
            <View style={[styles.sumRow, styles.sumFirst, { borderTopColor: colors.divider }]}>
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>ค่าอาหาร</Text>
              <PriceText amount={subtotal} size="sm" tone="strong" />
            </View>
            {deliveryType === 'rider' && (
              <View style={styles.sumRow}>
                <Text style={[typography.bodySm, { color: colors.textMuted }]}>ค่าส่งไรเดอร์</Text>
                {quote.state === 'ready' && quote.quote.available ? (
                  <PriceText amount={fee} size="sm" tone="strong" />
                ) : (
                  <Text style={[typography.caption, { color: colors.textFaint }]}>{pin ? '—' : 'ปักหมุดก่อน'}</Text>
                )}
              </View>
            )}
            <View style={styles.sumRow}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>รวมทั้งหมด</Text>
              <PriceText amount={total} size="lg" tone="gold" />
            </View>
          </Card3D>

          {!shopCart.can_checkout && (
            <Pressable onPress={() => router.replace('/taladsod/cart' as never)} style={[styles.warnBox, { backgroundColor: colors.warningSoft }]}>
              <Text style={[typography.bodySm, { color: colors.warning }]}>
                {shopCart.is_open ? 'มีรายการที่สั่งไม่ได้ แตะเพื่อกลับไปแก้ในตะกร้า' : 'ร้านปิดอยู่ตอนนี้ สั่งได้เมื่อร้านเปิด'}
              </Text>
            </Pressable>
          )}
        </ScrollView>

        <View
          style={[
            styles.bar,
            { paddingBottom: Math.max(insets.bottom, spacing.md), backgroundColor: colors.card, borderTopColor: colors.border },
            clayShadowStyle('md', colors.shadowDark, colors.shadowLight),
          ]}
        >
          <View style={styles.barTotal}>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              {deliveryType === 'rider' ? '🛵 ไรเดอร์ส่ง' : '🛍️ นัดรับ'} · {payment === 'wallet' ? '👛 กระเป๋าเงิน' : '💵 เงินสด'}
              {feePending ? ' · ยังไม่รวมค่าส่ง' : ''}
            </Text>
            <PriceText amount={total} size="md" tone="gold" />
          </View>
          {quoteFailed && !placing ? (
            // คำนวณค่าส่งไม่สำเร็จ → ปุ่มหลักกลายเป็นคำนวณใหม่ (ห้ามสั่งด้วยยอดที่ยังไม่รวมค่าส่ง)
            <Button3D title="คำนวณค่าส่งใหม่" icon="🔄" size="lg" variant="secondary" fullWidth onPress={retryQuote} />
          ) : (
            <Button3D
              title={placing ? 'กำลังสั่ง…' : quotePending ? 'กำลังคำนวณค่าส่ง…' : `ยืนยันสั่ง ${formatBaht(total)}`}
              icon="✅"
              size="lg"
              fullWidth
              loading={placing}
              disabled={!shopCart.can_checkout || quotePending}
              onPress={confirmAndSubmit}
            />
          )}
        </View>
      </KeyboardAvoidingView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  content: {
    paddingHorizontal: spacing.screen,
  },
  block: {
    marginTop: spacing.md,
  },
  section: {
    marginTop: spacing.xl,
  },
  gapTop: {
    marginTop: spacing.md,
  },
  gapTopSm: {
    marginTop: spacing.sm,
  },
  choiceRow: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  choiceIcon: {
    fontSize: 24,
    marginBottom: spacing.xs,
  },
  addrScroll: {
    marginTop: spacing.md,
    marginHorizontal: -spacing.lg,
  },
  addrChips: {
    paddingHorizontal: spacing.lg,
    gap: spacing.sm,
  },
  quoteRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  quoteWarn: {
    marginTop: spacing.md,
    borderRadius: radii.md,
    padding: spacing.md,
  },
  quoteOk: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    borderRadius: radii.md,
    padding: spacing.md,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    paddingVertical: spacing.xs,
  },
  qty: {
    fontWeight: '800',
    minWidth: 28,
  },
  sumRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 4,
  },
  sumFirst: {
    marginTop: spacing.sm,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  warnBox: {
    marginTop: spacing.md,
    borderRadius: radii.md,
    padding: spacing.md,
  },
  bar: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
    borderTopWidth: 1,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
    gap: spacing.xs,
  },
  barTotal: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
});
