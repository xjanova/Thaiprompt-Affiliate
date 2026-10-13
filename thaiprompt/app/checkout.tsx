/**
 * ชำระเงิน — POST /cart/checkout (SHOP-03 / 07 / 08 / 11 / 23)
 *
 * หน้าตา (ธีมรอยัล): การ์ดขั้นตอนมีเลขในวงกลมน้ำเงิน (ที่อยู่ → วิธีส่ง → วิธีจ่าย → โค้ด → สรุป)
 *   · ตัวเลือกเป็นการ์ดขาว (วงเลือก + กล่องไอคอน) ที่เลือกอยู่ = ขอบทอง · แถบล่างลอยยอดทอง + ปุ่มทองยืนยัน
 *   · ขั้นจ่ายพร้อมเพย์ = การ์ด QR ขอบทอง · สำเร็จ = เหรียญเครื่องหมายถูก + ยอดทอง
 *
 * ขั้นตอน
 *   1. form     เลือกที่อยู่ · วิธีส่ง (พัสดุ | ไรเดอร์ เมื่อร้านรองรับ + ค่าส่งจาก server)
 *               · วิธีจ่าย (กระเป๋าเงิน | พร้อมเพย์ | เก็บเงินปลายทาง เฉพาะส่งด้วยไรเดอร์) · คูปอง (server ตรวจ) · หมายเหตุ
 *               · 🪙 เหรียญ TPIX (เมื่อ server เปิดรับ) — แสดงยอดเหรียญโดยประมาณสดจากราคา DEX
 *   2. payment  พร้อมเพย์: แสดง QR ของแต่ละออเดอร์ (ยอดตรงทุกสตางค์) + poll สถานะทุก 4 วินาที + ขอ QR ใหม่ได้
 *               TPIX: ไปหน้า /tpix-pay/{reference} ของใบแรก (สั่งหลายร้าน = รายการใบอื่นอยู่ในหน้านั้น)
 *   3. done     สรุปคำสั่งซื้อ
 *
 * ความปลอดภัย
 *   - ยอดทุกตัวมาจาก server (GET /cart?address_id&delivery_method&coupon_code) — แอปไม่คำนวณเอง
 *   - Idempotency-Key ต่อ "ชุดข้อมูลที่กดสั่ง" — กดซ้ำ/เน็ตหลุดแล้วลองใหม่ = ไม่สั่งซ้ำ
 *   - ยืนยันก่อนสั่งทุกครั้ง (โดยเฉพาะตัดเงินกระเป๋า)
 *   - ข้อความ error ภาษาไทยจาก code ของ server เท่านั้น
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, AppState, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { LinearGradient } from 'expo-linear-gradient';
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
  Icon,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  TpixCoin,
  TpixTotal,
  formatBaht,
  resultHaptic,
} from '@/components/ui';
import {
  Field,
  FormSheet,
  IconTile,
  NoticeBanner,
  PromptPayQR,
  RadioMark,
  StepBadge,
  StickyBar,
  type PromptPayState,
} from '@/components/shop';
import {
  EscrowNotice,
  RiderChoiceBlock,
  RiderFeeLines,
  RiderRouteBlock,
  type RiderQuoteView,
} from '@/components/checkout/RiderDeliveryPanel';
import { getFavoriteRiders, DEFAULT_LOCK_MIN_HEARTS } from '@/services/api/riderSocialApi';
import type { PersonCard } from '@/services/api/handoverApi';
import type { CartRiderQuote } from '@/services/api/shopApi';
import { isPreferredChoiceValid, usePreferredRiderStore } from '@/stores/preferredRiderStore';
import { promptProfilePhoto } from '@/components/people/profilePhotoPrompt';
import { guardKycRequired } from '@/services/ekyc/kycGate';
import {
  estimateTpixTotal,
  formatTpix,
  normalizeTpixPayment,
  rememberTpixGroup,
  rememberTpixPayment,
  type TpixGroupEntry,
} from '@/services/api/tpixApi';
import { useTpixPriceStore, useTpixSpot } from '@/stores/tpixPriceStore';
import { canConfirmWithQuote, quoteFreshness, quoteParamsKey } from '@/utils/checkoutQuote';
import { useTheme, spacing, typography } from '@/theme';

type Step = 'form' | 'payment' | 'done';

/** แปลงค่าส่งไรเดอร์ของร้าน → รูปแบบที่ชิ้นส่วนหน้าชำระเงินใช้ */
const toRiderView = (r: CartRiderQuote): RiderQuoteView => ({
  distance_km: r.distance_km,
  estimated_minutes: r.estimated_minutes,
  distance_source: r.distance_source,
  route_polyline: r.route_polyline,
  buyer_fee: r.fee,
  fee_full: r.fee_full,
  rider_earnings: r.rider_earnings,
  shop_bonus: r.shop_bonus,
  shop_subsidy: r.shop_subsidy,
  rider_total: r.rider_total,
  surcharge: r.surcharge,
  free_delivery: r.free_delivery,
  subsidy_capped: r.subsidy_capped,
});

const POLL_INTERVAL_MS = 4000;
const POLL_MAX_MS = 20 * 60 * 1000;

const PAYMENT_LABEL: Record<CheckoutPaymentMethod, string> = {
  wallet: 'กระเป๋าเงิน',
  promptpay: 'พร้อมเพย์',
  cod: 'เก็บเงินปลายทาง',
  tpix: 'เหรียญ TPIX',
};

// =====================================================
// ตัวเลือกแบบการ์ด (วิธีส่ง / วิธีจ่าย)
// =====================================================

interface OptionCardProps {
  /** ชื่อไอคอน */
  icon: string;
  title: string;
  subtitle?: string;
  right?: React.ReactNode;
  selected: boolean;
  disabled?: boolean;
  disabledReason?: string | null;
  onPress: () => void;
}

/** การ์ดตัวเลือก: วงเลือก + กล่องไอคอน + ชื่อ/คำอธิบาย + ค่าด้านขวา · เลือกอยู่ = ขอบทอง */
const OptionCard: React.FC<OptionCardProps> = ({ icon, title, subtitle, right, selected, disabled, disabledReason, onPress }) => {
  const { colors } = useTheme();
  return (
    <Card3D
      onPress={disabled ? undefined : onPress}
      gradientBorder={selected}
      variant={disabled ? 'flat' : 'raised'}
      shadow="sm"
      padding={spacing.md}
      radius={20}
      style={[styles.option, disabled && styles.dimmed]}
      accessibilityLabel={`${title}${selected ? ' เลือกอยู่' : ''}${disabled && disabledReason ? ` ใช้ไม่ได้: ${disabledReason}` : ''}`}
    >
      <View style={styles.optionRow}>
        <RadioMark selected={selected} disabled={disabled} />
        <IconTile icon={icon} tone={selected ? 'gold' : 'navy'} size={42} weight={selected ? 'fill' : 'regular'} />
        <View style={styles.flex}>
          <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{title}</Text>
          {!!subtitle && <Text style={[typography.caption, { color: colors.textMuted }]}>{subtitle}</Text>}
          {disabled && !!disabledReason && (
            <View style={styles.disabledRow}>
              <Icon name="info" size={13} color={colors.warning} style={styles.disabledIcon} />
              <Text style={[typography.caption, styles.flex, { color: colors.warning }]}>{disabledReason}</Text>
            </View>
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
  const { colors, gradients } = useTheme();
  const insets = useSafeAreaInsets();
  const navigation = useNavigation();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const userId = useAuthStore((s) => s.user?.id ?? null);

  // ---------- ไรเดอร์คนโปรด (ล็อกเรียก) ----------
  const [lockable, setLockable] = useState<PersonCard[]>([]);
  const [favoritesLoading, setFavoritesLoading] = useState(false);
  const [lockMinHearts, setLockMinHearts] = useState(DEFAULT_LOCK_MIN_HEARTS);
  const [preferredRiderId, setPreferredRiderId] = useState<number | null>(null);
  const favoritesLoadedRef = useRef(false);

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
  /** กุญแจชุดตัวเลือก (ที่อยู่ · วิธีส่ง · คูปอง) ที่ได้ยอดบนจอมา — ไม่ตรงกับตัวเลือกปัจจุบัน = ยอดเก่า */
  const [quoteKey, setQuoteKey] = useState<string | null>(null);
  const [quoteLoading, setQuoteLoading] = useState(false);
  const [quoteError, setQuoteError] = useState<string | null>(null);
  const [walletBalance, setWalletBalance] = useState<number | null>(null);
  const [promptpayEnabled, setPromptpayEnabled] = useState<boolean | null>(null);
  /** 🪙 GET /payment/methods มี id "tpix" ที่เปิดอยู่ (null = ยังไม่รู้ / server ไม่บอก) */
  const [tpixMethodEnabled, setTpixMethodEnabled] = useState<boolean | null>(null);
  // 🪙 ราคา TPIX สด — คิดยอดเหรียญโดยประมาณ + เช็คว่าเปิดรับชำระด้วย TPIX ไหม
  const { spot: tpixSpot, paymentsEnabled: tpixPaymentsEnabled } = useTpixSpot(isAuthenticated);
  /** เสนอตัวเลือก TPIX เมื่อ server เปิดรับ (รายการวิธีจ่าย หรือ /tpix/price บอก) */
  // ตะกร้าที่มีสินค้า Lazada จ่ายด้วย TPIX ไม่ได้ (server ส่ง summary.tpix_payable = false)
  const tpixAvailable = (tpixMethodEnabled === true || (tpixMethodEnabled !== false && tpixPaymentsEnabled === true))
    && quote?.summary.tpix_payable !== false;

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
    // server รุ่นที่ยังไม่มี tpix ในรายการ → ไม่รู้ (null) ให้ /tpix/price ตัดสินแทน
    const tpixMethod = methods.find((m) => m.id === 'tpix');
    setTpixMethodEnabled(tpixMethod ? tpixMethod.enabled !== false : null);
  }, [isAuthenticated]);

  const fetchQuote = useCallback(async () => {
    if (!isAuthenticated) return;
    const requestId = ++quoteReqRef.current;
    const key = quoteParamsKey({ addressId: selectedAddressId, delivery, coupon: appliedCoupon });
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
      setQuoteKey(key);
      setQuoteError(null);
      useCartStore.getState().replace(cart);
    } else {
      // ยอดบนจอยังเป็นของรอบก่อน → ปุ่มสั่งซื้อถูกล็อกจนคำนวณใหม่สำเร็จ (ห้ามยืนยันด้วยยอดเก่า)
      setQuoteError(res.message || 'คำนวณยอดไม่สำเร็จ ลองใหม่อีกครั้งนะ');
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

  // 🪙 แอดมินปิดรับ TPIX ระหว่างอยู่หน้านี้ → กลับไปกระเป๋าเงิน (ยังไม่รู้ = ไม่เปลี่ยน)
  useEffect(() => {
    if (payment !== 'tpix' || step !== 'form') return;
    if (tpixMethodEnabled === false || (tpixMethodEnabled === null && tpixPaymentsEnabled === false)) {
      setPayment('wallet');
      setNotice('ตอนนี้ยังชำระด้วยเหรียญ TPIX ไม่ได้ เปลี่ยนเป็นจ่ายด้วยกระเป๋าเงินให้แล้ว');
    }
  }, [payment, step, tpixMethodEnabled, tpixPaymentsEnabled]);

  // =====================================================
  // ไรเดอร์คนโปรด: โหลดเมื่อเลือกส่งด้วยไรเดอร์ (ครั้งแรก + กลับมาจากหน้าไรเดอร์ใกล้ฉัน)
  // =====================================================

  const loadFavorites = useCallback(async () => {
    if (!isAuthenticated) return;
    setFavoritesLoading(true);
    const res = await getFavoriteRiders();
    if (!mountedRef.current) return;
    setFavoritesLoading(false);
    favoritesLoadedRef.current = true;
    if (!res.success) return; // โหลดไม่ได้ = จับคู่อัตโนมัติอย่างเดียว (ไม่ขวางการสั่ง)
    setLockMinHearts(res.data.lock_min_hearts);
    const list = res.data.riders.filter((r) => r.can_lock);
    setLockable(list);
    // ที่เลือกไว้จากหน้า "ไรเดอร์ใกล้ฉัน" → เลือกให้เลย (ถ้ายังล็อกได้)
    const stored = usePreferredRiderStore.getState().validFor(userId);
    setPreferredRiderId((prev) => {
      if (prev && list.some((r) => r.id === prev)) return prev;
      if (stored && list.some((r) => r.id === stored.rider.id)) return stored.rider.id;
      return null;
    });
  }, [isAuthenticated, userId]);

  useEffect(() => {
    if (delivery !== 'rider' || favoritesLoadedRef.current) return;
    loadFavorites();
  }, [delivery, loadFavorites]);

  // กลับจากหน้าไรเดอร์ใกล้ฉัน (กดล็อกเรียกคนใหม่) → อัปเดตตัวเลือก
  const preferredChoice = usePreferredRiderStore((s) => s.choice);
  useEffect(() => {
    // ของบัญชีนี้ + ไม่เกิน 2 ชั่วโมง (เกณฑ์เดียวกับ validFor — M5)
    if (!isPreferredChoiceValid(preferredChoice, userId)) return;
    if (lockable.some((r) => r.id === preferredChoice.rider.id)) {
      setPreferredRiderId(preferredChoice.rider.id);
    } else if (preferredChoice.rider.can_lock && favoritesLoadedRef.current) {
      // ไม่อยู่ในรายการคนโปรด (เช่น โหลดรายการไม่ได้) แต่ server บอกว่าล็อกได้ → เพิ่มเป็นตัวเลือก
      setLockable((prev) => [preferredChoice.rider, ...prev.filter((r) => r.id !== preferredChoice.rider.id)]);
      setPreferredRiderId(preferredChoice.rider.id);
    }
  }, [preferredChoice, userId, lockable]);

  const chooseRider = (riderId: number | null) => {
    setPreferredRiderId(riderId);
    if (riderId === null) usePreferredRiderStore.getState().clear();
  };

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
      setQuoteKey(quoteParamsKey({ addressId: selectedAddressId, delivery, coupon: code }));
      setQuoteError(null);
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

  // 🪙 ยอดเหรียญโดยประมาณของยอดชำระ (รายการราคาเหรียญคงที่ไม่แปลงจากบาท) — ยอดจริงออกหลังกดสั่งซื้อ
  const tpixLines = availableItems.map((i) => ({ tpix: i.tpix, quantity: i.quantity, thb: i.line_total }));
  const tpixEstimate = estimateTpixTotal(grandTotal, tpixSpot?.price_thb ?? null, tpixLines);
  const tpixRateText = tpixSpot?.price_thb_display ?? null;
  /** ยอดบนจอสดไหม (ตรงกับที่อยู่/วิธีส่ง/คูปองที่เลือกอยู่ + ขอล่าสุดสำเร็จ) — ไม่สด = ห้ามยืนยันสั่งซื้อ */
  const quoteState = quoteFreshness({
    hasQuote: !!quote,
    loading: quoteLoading,
    error: quoteError,
    quoteKey,
    currentKey: quoteParamsKey({ addressId: selectedAddressId, delivery, coupon: appliedCoupon }),
  });
  const quoteUsable = canConfirmWithQuote(quoteState);
  /** ค่าล่าสุดสำหรับเช็คซ้ำตอนกดยืนยันในกล่องถาม (กล่องเปิดค้างแล้วยอดเปลี่ยน = ไม่สั่ง) */
  const quoteGuardRef = useRef({ usable: false, total: 0 });
  quoteGuardRef.current = { usable: quoteUsable, total: grandTotal };

  /** ชุดข้อมูลที่กดสั่ง — เปลี่ยน = คีย์ใหม่ (ไม่เปลี่ยน = ใช้คีย์เดิมตอนลองใหม่ กันสั่งซ้ำ) */
  const lockRiderId = delivery === 'rider' && preferredRiderId ? preferredRiderId : null;
  const signature = JSON.stringify({
    a: selectedAddressId,
    d: delivery,
    p: payment,
    c: appliedCoupon,
    n: note.trim(),
    r: lockRiderId,
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
      case 'RIDER_LOCK_NOT_ALLOWED':
        // สิทธิ์ล็อกเรียกเปลี่ยนไปแล้ว → กลับไปจับคู่อัตโนมัติ ให้ผู้ซื้อกดยืนยันใหม่เอง
        chooseRider(null);
        favoritesLoadedRef.current = false;
        loadFavorites();
        Alert.alert(
          'ล็อกเรียกไรเดอร์คนนี้ไม่ได้',
          `${res.message}\nเปลี่ยนเป็นจับคู่อัตโนมัติให้แล้ว กดยืนยันสั่งซื้ออีกครั้งได้เลย`
        );
        return;
      case 'PROFILE_PHOTO_REQUIRED':
        promptProfilePhoto('checkout');
        return;
      case 'KYC_REQUIRED':
        // ยังไม่ยืนยันตัวตน (แอป build ≥ 44) → sheet "ยืนยันตัวตนก่อนสั่งซื้อ" แล้วกลับมาหน้านี้ (ข้อมูลที่เลือกยังอยู่)
        guardKycRequired(res, 'checkout');
        return;
      case 'COD_NOT_AVAILABLE':
        if (payment === 'cod') setPayment('wallet');
        Alert.alert('ออเดอร์นี้เก็บเงินปลายทางไม่ได้', res.message || 'ส่งด้วยไรเดอร์ต้องชำระก่อน เงินพักไว้ปลอดภัยจนคุณได้รับของ');
        fetchQuote();
        return;
      case 'RIDER_NOT_AVAILABLE':
      case 'PAYMENT_METHOD_UNAVAILABLE':
        Alert.alert('เปลี่ยนตัวเลือกก่อนนะ', res.message);
        fetchQuote();
        loadPaymentMethods();
        // 🪙 TPIX: ราคา/การเปิดรับอาจเปลี่ยน → โหลดราคาใหม่
        if (payment === 'tpix') useTpixPriceStore.getState().refresh();
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

  /**
   * 🪙 เก็บใบเสนอยอด TPIX ของทุกออเดอร์ไว้ในแคช แล้วเปิดหน้าจ่ายของใบแรกที่ออกยอดสำเร็จ
   * @returns false = ไม่มีใบไหนออกยอดสำเร็จ (ให้หน้าสำเร็จบอกว่าไปจ่ายใหม่ที่หน้าคำสั่งซื้อ)
   */
  const openTpixPayments = (data: CheckoutResult): boolean => {
    const list = Array.isArray(data.payments) ? data.payments : [];
    const entries: TpixGroupEntry[] = data.orders.map((order) => {
      const p = list.find((x) => x.order_id === order.id);
      const tpix = p ? normalizeTpixPayment(p.tpix) : null;
      if (tpix) rememberTpixPayment(tpix);
      return {
        order_id: order.id,
        order_number: order.order_number || null,
        store_name: order.store?.name || null,
        amount_thb: Number(order.total_amount) || 0,
        reference: tpix?.reference ?? null,
        message: tpix ? null : p?.message && /[฀-๿]/.test(p.message) ? p.message : null,
      };
    });
    const first = entries.find((e) => e.reference);
    if (!first?.reference) {
      const failed = entries.find((e) => e.message)?.message;
      Alert.alert(
        'ออกยอดเหรียญ TPIX ไม่สำเร็จ',
        `${failed || 'ระบบยังออกยอดเหรียญให้ไม่ได้ตอนนี้'}\nคำสั่งซื้อถูกสร้างแล้ว กดชำระใหม่ได้จากหน้าคำสั่งซื้อ`
      );
      return false;
    }
    const groupId = data.checkout_id || `checkout-${first.order_id}`;
    rememberTpixGroup(groupId, entries);
    leavingRef.current = true;
    router.replace(`/tpix-pay/${first.reference}?group=${encodeURIComponent(groupId)}&from=checkout` as never);
    return true;
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
        ...(lockRiderId ? { preferred_rider_id: lockRiderId } : {}),
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
    // ล็อกเรียกใช้ครั้งเดียวต่อการสั่ง
    if (lockRiderId) usePreferredRiderStore.getState().clear();

    const data = res.data;
    setResult(data);
    if (typeof data.wallet_balance === 'number') setWalletBalance(data.wallet_balance);

    // 🪙 จ่ายด้วยเหรียญ TPIX → ไปหน้าจ่าย TPIX ของใบแรก (สั่งหลายร้าน = รายการใบอื่นอยู่ในหน้านั้น)
    if ((data.payment_method === 'tpix' || payment === 'tpix') && data.payment_status !== 'paid') {
      if (openTpixPayments(data)) return;
      setStep('done');
      return;
    }

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
    if (!quoteUsable) {
      // ยอดบนจอเป็นของตัวเลือกชุดเก่า/คำนวณไม่สำเร็จ → คำนวณใหม่ก่อน ไม่ยืนยันด้วยยอดเก่า
      if (quoteState === 'failed') fetchQuote();
      return;
    }
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
      payment === 'tpix' && tpixEstimate !== null ? `ประมาณ ${formatTpix(tpixEstimate)} TPIX` : '',
      `จ่ายด้วย: ${PAYMENT_LABEL[payment]}`,
      `จัดส่ง: ${delivery === 'rider' ? 'ไรเดอร์' : 'พัสดุ'}`,
      quote.stores.length > 1 ? `แยกเป็น ${quote.stores.length} คำสั่งซื้อตามร้าน` : '',
      payment === 'wallet' ? 'ระบบจะตัดเงินจากกระเป๋าทันที' : '',
      payment === 'tpix'
        ? 'ยอดเหรียญที่แน่นอนจะออกให้หลังยืนยัน โอนให้ตรงทุกหลักภายในเวลาที่กำหนด'
        : '',
    ].filter(Boolean);

    const confirmedTotal = grandTotal;
    Alert.alert('ยืนยันสั่งซื้อ?', lines.join('\n'), [
      { text: 'ยังก่อน', style: 'cancel' },
      {
        text: payment === 'wallet' ? 'ยืนยันจ่ายเงิน' : 'ยืนยันสั่งซื้อ',
        onPress: () => {
          // ระหว่างกล่องเปิดอยู่ ยอดเปลี่ยน/กลายเป็นยอดเก่า → ไม่สั่ง ให้ดูยอดล่าสุดก่อน
          const latest = quoteGuardRef.current;
          if (!latest.usable || latest.total !== confirmedTotal) {
            Alert.alert('ยอดมีการเปลี่ยนแปลง', 'ตรวจยอดชำระล่าสุดแล้วกดยืนยันสั่งซื้ออีกครั้งนะ');
            return;
          }
          submit();
        },
      },
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
        <EmptyState icon="lock-key" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
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
        : result.payment_method === 'tpix' && status !== 'paid'
          ? 'ยังออกยอดเหรียญ TPIX ไม่สำเร็จ เปิดหน้าคำสั่งซื้อแล้วกดชำระใหม่ได้เลย (ด้วยเหรียญ TPIX หรือวิธีอื่น)'
          : delivery === 'rider'
            ? 'ร้านได้รับคำสั่งซื้อแล้ว เงินของคุณพักไว้ที่ระบบ เมื่อไรเดอร์มาถึง เปิดหน้าคำสั่งซื้อแล้วสแกน QR ใส่กันเพื่อรับของ'
            : 'ร้านได้รับคำสั่งซื้อแล้ว ติดตามสถานะได้ที่หน้าคำสั่งซื้อ';
    return (
      <Screen title="สั่งซื้อสำเร็จ" onBack={goOrders}>
        <Card3D gradientBorder shadow="lg" padding={spacing.xl} style={styles.block}>
          <View style={[styles.medalOuter, { backgroundColor: colors.successSoft }]}>
            <LinearGradient colors={gradients.success} start={{ x: 0, y: 0 }} end={{ x: 0, y: 1 }} style={styles.medalInner}>
              <Icon name="check" size={34} color={colors.textOnAccent} weight="bold" />
            </LinearGradient>
          </View>
          <Text style={[typography.serif, styles.centerText, { color: colors.textStrong }]}>{title}</Text>
          <Text style={[typography.body, styles.centerText, styles.gapTopSm, { color: colors.textMuted }]}>{message}</Text>
          <View style={[styles.doneTotal, { backgroundColor: colors.inset, borderColor: colors.border }]}>
            <Text style={[typography.caption, styles.centerText, { color: colors.textMuted }]}>ยอดรวมคำสั่งซื้อ</Text>
            <PriceText amount={result.total_amount} decimals={2} size="xl" tone="gold" style={styles.centerText} />
            {typeof result.wallet_balance === 'number' && (
              <Text style={[typography.caption, styles.centerText, { color: colors.textMuted }]}>
                ยอดคงเหลือในกระเป๋า {formatBaht(result.wallet_balance, { decimals: 2 })}
              </Text>
            )}
          </View>
        </Card3D>

        {result.orders.map((order) => (
          <Card3D
            key={order.id}
            onPress={() => {
              leavingRef.current = true;
              router.replace(`/order/${order.id}` as never);
            }}
            padding={spacing.md}
            radius={20}
            shadow="sm"
            style={styles.blockSm}
            accessibilityLabel={`ดูคำสั่งซื้อ ${order.order_number}`}
          >
            <View style={styles.rowBetween}>
              <IconTile icon="storefront" size={42} />
              <View style={styles.flex}>
                <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>{order.store?.name || 'คำสั่งซื้อ'}</Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>{order.order_number}</Text>
              </View>
              <PriceText amount={order.total_amount} size="md" tone="strong" />
              <Icon name="caret-right" size={16} color={colors.textFaint} weight="bold" />
            </View>
          </Card3D>
        ))}

        <Button3D title="ดูคำสั่งซื้อ" icon="receipt" size="lg" fullWidth onPress={goOrders} style={styles.gapTop} />
        <Button3D
          title="ช้อปต่อ"
          icon="shopping-bag-open"
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
          <NoticeBanner
            tone="info"
            icon="storefront"
            text="สั่งจากหลายร้าน แต่ละร้านมี QR ของตัวเอง โอนให้ครบทุก QR นะ"
            style={styles.block}
          />
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
                  icon="wallet"
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
          <NoticeBanner
            tone="warning"
            icon="hourglass"
            text="ยังไม่พบยอดโอน ถ้าโอนแล้วกดตรวจสอบอีกครั้งได้เลย"
            actionBelow
            action={
              <Button3D
                title="ตรวจสอบอีกครั้ง"
                icon="arrows-clockwise"
                size="sm"
                onPress={async () => {
                  pollStartedAtRef.current = Date.now();
                  setPollTimedOut(false);
                  await pollOnce();
                }}
              />
            }
            style={styles.block}
          />
        )}

        {anyWaiting && (
          <View style={[styles.waitingRow, { backgroundColor: colors.card, borderColor: colors.border }]}>
            <ActivityIndicator color={colors.gold} />
            <Text style={[typography.caption, { color: colors.textMuted }]}>กำลังรอยืนยันยอดโอนอัตโนมัติ...</Text>
          </View>
        )}

        <Button3D
          title="ชำระภายหลัง / ดูคำสั่งซื้อ"
          icon="receipt"
          variant="secondary"
          size="md"
          fullWidth
          onPress={goOrders}
          style={styles.gapTop}
        />
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
          art="cart"
          title="ตะกร้ายังว่างอยู่"
          message="เลือกสินค้าก่อนแล้วค่อยมาชำระเงินนะ"
          actionLabel="ไปช้อปเลย"
          onAction={() => router.replace('/(tabs)/shop' as never)}
        />
      </Screen>
    );
  }

  const summary = quote.summary;
  /** ขอยอดล่าสุดไม่สำเร็จ → ปุ่มล่างกลายเป็น "คำนวณยอดใหม่" (ยอดบนจออาจไม่ตรงกับที่ server จะคิด) */
  const quoteFailed = quoteState === 'failed';
  const submitDisabled = !quoteFailed && (!quoteUsable || submitting || availableItems.length === 0);

  return (
    <View style={[styles.root, { backgroundColor: colors.background }]}>
      <Screen
        title="ชำระเงิน"
        subtitle={`${summary.available_items_count} ชิ้น`}
        contentStyle={{ paddingBottom: 130 + Math.max(insets.bottom, spacing.md) }}
      >
        {!!notice && <NoticeBanner tone="info" text={notice} onClose={() => setNotice(null)} style={styles.block} />}
        {quoteFailed && (
          <NoticeBanner
            tone="danger"
            icon="warning"
            text={`คำนวณยอดล่าสุดไม่สำเร็จ ยอดที่เห็นอาจยังไม่ตรงกับตัวเลือกที่เลือก · ${quoteError}`}
            actionBelow
            action={
              <Button3D title="คำนวณยอดใหม่" icon="arrows-clockwise" size="sm" variant="secondary" onPress={fetchQuote} />
            }
            style={styles.block}
          />
        )}

        {/* ---------- 1 ที่อยู่ ---------- */}
        <SectionHeader
          title="ที่อยู่จัดส่ง"
          icon={<StepBadge n={1} />}
          actionLabel={addresses.length > 0 ? 'เปลี่ยน' : undefined}
          onAction={() => setAddressSheet(true)}
        />
        {selectedAddress ? (
          <Card3D onPress={() => setAddressSheet(true)} padding={spacing.lg} radius={20} style={styles.block} accessibilityLabel="เปลี่ยนที่อยู่จัดส่ง">
            <View style={styles.addressRow}>
              <IconTile icon="map-pin" tone="gold" weight="fill" />
              <View style={styles.flex}>
                <View style={styles.rowBetween}>
                  <Text style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]}>
                    {selectedAddress.recipient_name} · {selectedAddress.phone_number}
                  </Text>
                  {selectedAddress.is_default && <Pill label="หลัก" tone="gold" />}
                </View>
                <Text style={[typography.bodySm, styles.gapTopSm, { color: colors.text }]}>{selectedAddress.full_address}</Text>
              </View>
            </View>
            {!selectedAddress.has_location && (
              <NoticeBanner
                tone="warning"
                icon="map-pin"
                text="ยังไม่ปักหมุด — ปักหมุดแล้วเลือกส่งด้วยไรเดอร์ได้"
                action={
                  <Button3D
                    title="ปักหมุด"
                    size="sm"
                    variant="secondary"
                    onPress={() => router.push(`/addresses/edit?id=${selectedAddress.id}` as never)}
                  />
                }
                style={styles.pinWarn}
              />
            )}
          </Card3D>
        ) : (
          <Card3D gradientBorder padding={spacing.lg} style={styles.block}>
            <View style={styles.addressRow}>
              <IconTile icon="map-pin" tone="gold" weight="fill" />
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ยังไม่มีที่อยู่จัดส่ง</Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>เพิ่มที่อยู่ครั้งเดียว ใช้ได้ทุกครั้งที่สั่ง</Text>
              </View>
            </View>
            <Button3D
              title="เพิ่มที่อยู่"
              icon="plus"
              size="md"
              fullWidth
              onPress={() => router.push('/addresses/edit' as never)}
              style={styles.gapTop}
            />
          </Card3D>
        )}

        {/* ---------- 2 วิธีจัดส่ง ---------- */}
        <SectionHeader title="วิธีจัดส่ง" icon={<StepBadge n={2} />} style={styles.section} />
        <OptionCard
          icon="package"
          title="ส่งพัสดุ"
          subtitle="ร้านแพ็กส่งผ่านบริษัทขนส่ง พร้อมเลขติดตาม"
          selected={delivery === 'parcel'}
          onPress={() => setDelivery('parcel')}
          right={
            riderInfo.parcelFee > 0 ? (
              <PriceText amount={riderInfo.parcelFee} size="sm" tone="strong" />
            ) : (
              <Text style={[typography.caption, styles.free, { color: colors.success }]}>ส่งฟรี</Text>
            )
          }
        />
        <OptionCard
          icon="moped"
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
          right={
            riderInfo.available ? (
              riderInfo.fee > 0 ? (
                <PriceText amount={riderInfo.fee} size="sm" tone="strong" />
              ) : (
                <Text style={[typography.caption, styles.free, { color: colors.success }]}>ส่งฟรี</Text>
              )
            ) : null
          }
        />

        {/* ---------- ส่งด้วยไรเดอร์: เส้นทางจริง + เลือกไรเดอร์ ---------- */}
        {delivery === 'rider' && riderInfo.available && (
          <Card3D padding={spacing.lg} radius={20} style={styles.block}>
            {quote.stores
              .filter((s) => s.rider.available)
              .map((s, index) => (
                <View key={s.key} style={index > 0 ? [styles.riderStoreSep, { borderTopColor: colors.divider }] : undefined}>
                  <RiderRouteBlock
                    quote={toRiderView(s.rider)}
                    storeName={s.store_name}
                    title={quote.stores.length > 1 ? `ไรเดอร์ส่งจาก ${s.store_name}` : 'ส่งด้วยไรเดอร์'}
                  />
                </View>
              ))}
            <RiderChoiceBlock
              lockable={lockable}
              selectedId={lockRiderId}
              onSelect={chooseRider}
              loading={favoritesLoading}
              lockMinHearts={lockMinHearts}
              disabled={submitting}
            />
          </Card3D>
        )}

        {/* ---------- 3 วิธีชำระเงิน ---------- */}
        <SectionHeader title="วิธีชำระเงิน" icon={<StepBadge n={3} />} style={styles.section} />
        <OptionCard
          icon="wallet"
          title="กระเป๋าเงิน"
          subtitle={walletBalance !== null ? `ยอดคงเหลือ ${formatBaht(walletBalance, { decimals: 2 })}` : 'ตัดเงินจากกระเป๋าทันที'}
          selected={payment === 'wallet'}
          onPress={() => setPayment('wallet')}
          right={walletShort ? <Pill label="ไม่พอ" tone="danger" /> : null}
        />
        {walletShort && (
          <Button3D
            title="เติมเงินเข้ากระเป๋า"
            icon="plus"
            variant="secondary"
            size="sm"
            onPress={() => router.push('/wallet-topup' as never)}
            style={styles.topup}
          />
        )}
        <OptionCard
          icon="qr-code"
          title="พร้อมเพย์ (สแกน QR)"
          subtitle="โอนผ่านแอปธนาคาร ระบบยืนยันให้อัตโนมัติ"
          selected={payment === 'promptpay'}
          disabled={promptpayEnabled === false}
          disabledReason="พร้อมเพย์ปิดใช้งานชั่วคราว"
          onPress={() => setPayment('promptpay')}
        />
        {/* 🪙 เหรียญ TPIX (เชน 4289) — แสดงเฉพาะเมื่อ server เปิดรับ · คำนวณสดจากราคาจริงบน DEX */}
        {tpixAvailable && (
          <OptionCard
            icon="coins"
            title="เหรียญ TPIX"
            subtitle={
              tpixEstimate !== null && tpixRateText
                ? `≈ ${formatTpix(tpixEstimate)} TPIX · 1 TPIX = ${tpixRateText}`
                : 'โอนเหรียญจากกระเป๋าคริปโต (TPIX Chain) ระบบยืนยันบนเชนให้อัตโนมัติ'
            }
            selected={payment === 'tpix'}
            onPress={() => setPayment('tpix')}
            right={<TpixCoin size={22} />}
          />
        )}
        {payment === 'tpix' && tpixAvailable && (
          <NoticeBanner
            tone="info"
            icon="info"
            text={`ยอดเหรียญที่แน่นอน (มีเลขท้ายเฉพาะคำสั่งซื้อ) จะออกให้หลังกดยืนยันสั่งซื้อ และล็อกราคาไว้ช่วงเวลาหนึ่ง โอนจากกระเป๋าที่รองรับเครือข่าย TPIX Chain เช่น MetaMask / Trust Wallet${
              quote.stores.length > 1 ? `\nสั่งจาก ${quote.stores.length} ร้าน จะได้ยอดเหรียญแยกร้านละยอด` : ''
            }`}
            style={styles.block}
          />
        )}
        {/*
          เก็บเงินปลายทาง (U9): แสดงเฉพาะเมื่อ server เปิดให้ตะกร้านี้เก็บปลายทางได้จริง (summary.cod_available)
          ส่งด้วยไรเดอร์ต้องจ่ายก่อน (rider.allow_cod = false) → ซ่อนตัวเลือกนี้ ไม่โชว์ข้อความชวนเข้าใจผิด
          ("ใช้ได้เมื่อเลือกส่งด้วยไรเดอร์" ไม่จริงแล้ว) — EscrowNotice อธิบายเรื่องเงินพักไว้แทน
        */}
        {!!quote.summary.cod_available && (
          <OptionCard
            icon="money"
            title="เก็บเงินปลายทาง"
            subtitle="จ่ายเงินสดกับไรเดอร์ตอนรับของ"
            selected={payment === 'cod'}
            disabled={!codAvailable}
            disabledReason={delivery !== 'rider' ? 'ใช้ได้เมื่อเลือกส่งด้วยไรเดอร์' : riderInfo.codReason || 'ออเดอร์นี้เก็บเงินปลายทางไม่ได้'}
            onPress={() => setPayment('cod')}
          />
        )}

        {/* ---------- 4 คูปอง ---------- */}
        <SectionHeader title="โค้ดส่วนลด" icon={<StepBadge n={4} />} style={styles.section} />
        <Card3D padding={spacing.lg} radius={20} style={styles.block}>
          {appliedCoupon ? (
            <View style={styles.rowBetween}>
              <IconTile icon="ticket" tone="success" weight="fill" />
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.success }]}>ใช้โค้ด {appliedCoupon}</Text>
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
                containerStyle={[styles.flex, styles.noTop]}
                returnKeyType="done"
                onSubmitEditing={applyCoupon}
              />
              <Button3D title="ใช้โค้ด" size="md" onPress={applyCoupon} disabled={!couponInput.trim()} style={styles.couponButton} />
            </View>
          )}
        </Card3D>

        {/* ---------- 5 รายการตามร้าน ---------- */}
        <SectionHeader title="สรุปคำสั่งซื้อ" icon={<StepBadge n={5} />} style={styles.section} />
        {quote.stores.map((store) => {
          const items = availableItems.filter((i) => (i.store?.id ?? null) === store.store_id);
          return (
            <Card3D key={store.key} padding={spacing.lg} radius={20} style={styles.block}>
              <View style={styles.storeHead}>
                <IconTile icon="storefront" size={36} />
                <Text numberOfLines={1} style={[typography.h3, styles.flex, { color: colors.textStrong }]}>
                  {store.store_name}
                </Text>
              </View>
              {items.map((item) => (
                <View key={item.id} style={styles.itemLine}>
                  <Text numberOfLines={2} style={[typography.bodySm, styles.flex, { color: colors.text }]}>
                    {item.name} × {item.quantity}
                  </Text>
                  <PriceText amount={item.line_total} size="sm" tone="strong" />
                </View>
              ))}
              <View style={[styles.storeTotals, { borderTopColor: colors.divider }]}>
                {store.delivery_method === 'rider' && store.rider.available ? (
                  <RiderFeeLines quote={toRiderView(store.rider)} compact />
                ) : (
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
                )}
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
          <NoticeBanner
            tone="danger"
            text={`มีสินค้า ${summary.unavailable_count} รายการที่สั่งไม่ได้ เอาออกจากตะกร้าก่อนนะ`}
            actionBelow
            action={<Button3D title="ไปที่ตะกร้า" icon="shopping-cart-simple" size="sm" variant="secondary" onPress={() => router.replace('/cart')} />}
            style={styles.block}
          />
        )}

        {/* ---------- หมายเหตุ ---------- */}
        <Card3D padding={spacing.lg} radius={20} style={styles.block}>
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
        <Card3D variant="inset" padding={spacing.lg} radius={20} style={styles.block}>
          <View style={styles.rowBetween}>
            <Text style={[typography.body, { color: colors.text }]}>ค่าสินค้า</Text>
            <PriceText amount={summary.subtotal} size="sm" tone="strong" />
          </View>
          <View style={[styles.rowBetween, styles.gapTopSm]}>
            <Text style={[typography.body, { color: colors.text }]}>ค่าจัดส่ง</Text>
            {summary.shipping_fee > 0 ? (
              <PriceText amount={summary.shipping_fee} size="sm" tone="strong" />
            ) : (
              <Pill label="ส่งฟรี" tone="success" icon="truck" />
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
          {/* 🪙 ยอดเหรียญ TPIX โดยประมาณ (ราคาสดจาก DEX) */}
          {summary.tpix_payable !== false && (
            <TpixTotal thb={summary.grand_total} lines={tpixLines} showRate size="sm" align="right" style={styles.gapTopSm} />
          )}
        </Card3D>

        {delivery === 'rider' && payment !== 'cod' && <EscrowNotice style={styles.block} />}
      </Screen>

      {/* ---------- แถบยืนยัน ---------- */}
      <StickyBar style={styles.bottomBar}>
        <View style={styles.flex}>
          <Text style={[typography.caption, { color: quoteFailed ? colors.danger : colors.textMuted }]}>
            {quoteLoading || quoteState === 'stale'
              ? 'กำลังคำนวณยอด...'
              : quoteFailed
                ? 'ยอดยังไม่อัปเดต'
                : `จ่ายด้วย${PAYMENT_LABEL[payment]}`}
          </Text>
          {quoteLoading || quoteState === 'stale' ? (
            <ActivityIndicator color={colors.gold} style={styles.totalLoader} />
          ) : quoteFailed ? (
            <Text style={[typography.bodyStrong, { color: colors.danger }]}>คำนวณใหม่ก่อนสั่ง</Text>
          ) : (
            <>
              <PriceText amount={summary.grand_total} decimals={2} size="lg" tone="gold" />
              {payment === 'tpix' && <TpixTotal thb={summary.grand_total} lines={tpixLines} size="xs" />}
            </>
          )}
        </View>
        <Button3D
          title={quoteFailed ? 'คำนวณยอดใหม่' : 'ยืนยันสั่งซื้อ'}
          icon={quoteFailed ? 'arrows-clockwise' : 'check-circle'}
          size="lg"
          loading={submitting}
          loadingText="กำลังสั่งซื้อ..."
          disabled={submitDisabled}
          onPress={quoteFailed ? fetchQuote : confirmAndSubmit}
          style={styles.submit}
        />
      </StickyBar>

      {/* ---------- เลือกที่อยู่ ---------- */}
      <FormSheet
        visible={addressSheet}
        icon="map-pin"
        title="เลือกที่อยู่จัดส่ง"
        onClose={() => setAddressSheet(false)}
        cancelLabel="ปิด"
      >
        <View style={styles.sheetList}>
          {addresses.map((a) => (
            <OptionCard
              key={a.id}
              icon={a.has_location ? 'map-pin' : 'house'}
              title={`${a.recipient_name}${a.is_default ? ' (หลัก)' : ''}`}
              subtitle={a.full_address}
              selected={a.id === selectedAddressId}
              onPress={() => {
                setSelectedAddressId(a.id);
                setAddressSheet(false);
              }}
            />
          ))}
        </View>
        <View style={styles.sheetButtons}>
          <Button3D
            title="เพิ่มที่อยู่ใหม่"
            icon="plus"
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
  blockSm: {
    marginBottom: spacing.sm,
  },
  section: {
    marginTop: spacing.lg,
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
  doneTotal: {
    marginTop: spacing.lg,
    borderRadius: 16,
    borderWidth: 1,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.lg,
    gap: 2,
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
  disabledRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 4,
    marginTop: 2,
  },
  disabledIcon: {
    marginTop: 2,
  },
  free: {
    fontWeight: '700',
  },
  addressRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  pinWarn: {
    marginTop: spacing.md,
  },
  topup: {
    alignSelf: 'flex-start',
    marginBottom: spacing.sm,
    marginLeft: spacing.xs,
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
  storeHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.xs,
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
    marginBottom: spacing.lg,
  },
  waitingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    gap: spacing.sm,
    marginTop: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
    borderRadius: 999,
    borderWidth: 1,
  },
  bottomBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  totalLoader: {
    alignSelf: 'flex-start',
    marginTop: spacing.xs,
  },
  submit: {
    minWidth: 170,
  },
  sheetList: {
    marginTop: spacing.md,
  },
  sheetButtons: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  riderStoreSep: {
    borderTopWidth: 1,
    marginTop: spacing.lg,
    paddingTop: spacing.lg,
  },
});
