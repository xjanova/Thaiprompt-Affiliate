/**
 * รายละเอียดคำสั่งซื้อ (ผู้ซื้อ) — GET /orders/{id} + /orders/{id}/tracking (SHOP-11 / SHOP-20 / CC-20)
 *
 * - ไทม์ไลน์ตาม enum จริงของ orders.status (ไม่มี 'confirmed')
 * - ยังไม่จ่าย: แสดง QR พร้อมเพย์ที่ค้าง + poll สถานะ / ขอ QR ใหม่ / จ่ายด้วยกระเป๋า
 * - ส่งด้วยไรเดอร์: การ์ดไรเดอร์ (ชื่อ ทะเบียน โทร) + แผนที่ตำแหน่งไรเดอร์สด (GET /orders/shop/{id}/rider-location)
 *   + แชร์ตำแหน่งของฉันให้ไรเดอร์ + ลิงก์ติดตามบนเว็บ — รีเฟรชทุก 15 วินาทีระหว่างไรเดอร์วิ่ง
 * - ส่งพัสดุ: เลขพัสดุ (คัดลอกได้) + ประวัติการขนส่ง
 * - ยืนยันรับสินค้า · ยกเลิก (เมื่อ server อนุญาต) · รีวิวสินค้า · แชทกับร้าน (?tab=chat)
 *
 * หน้าตา (ธีมรอยัล): การ์ดสถานะขอบทอง + ข้อมูลย่อยพร้อมไอคอน · การ์ดขาวแต่ละส่วนมีกล่องไอคอนนำหน้า
 *   · ไทม์ไลน์เหรียญไอคอน · แชทฟองน้ำเงิน (ของฉัน) / ขาว (ร้าน)
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  AppState,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { Text } from '@/components/ui/Text';
import * as Clipboard from 'expo-clipboard';
import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  cancelMyOrder,
  confirmOrderReceived,
  getMyOrder,
  getOrderMessages,
  getOrderTracking,
  getPaymentStatus,
  payOrder,
  reviewOrderItem,
  sendOrderMessage,
  type PaymentInstruction,
  type ShopOrder,
  type ShopOrderItem,
  type ShopOrderTracking,
} from '@/services/api/shopApi';
import { isTrustedWebUrl } from '@/utils/linking';
import {
  Button3D,
  Card3D,
  Chip,
  EmptyState,
  Icon,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  formatBaht,
  resultHaptic,
} from '@/components/ui';
import {
  ACTIVE_RIDER_STATUSES,
  Field,
  FormSheet,
  IconTile,
  MetaItem,
  NoticeBanner,
  PromptPayQR,
  StatusTimeline,
  ThumbImage,
  callPhone,
  formatThaiDateTime,
  openHttpsLink,
  type PromptPayState,
  type TimelineStep,
} from '@/components/shop';
import { OrderChatPanel } from '@/components/shop/OrderChatPanel';
import { RiderTracker } from '@/components/taladsod';
import { useTheme, radii, spacing, typography } from '@/theme';

type Tab = 'detail' | 'chat';

const STATUS_FLOW = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'completed'] as const;
const CANCEL_REASONS = ['เปลี่ยนใจไม่ซื้อแล้ว', 'สั่งผิด อยากแก้รายการ', 'ได้ของจากที่อื่นแล้ว', 'รอนานเกินไป'];
const PAY_POLL_MS = 4000;
const RIDER_POLL_MS = 15000;
const ORDER_POLL_MS = 45000;

/** ไทม์ไลน์ของออเดอร์ร้านค้า */
const buildTimeline = (order: ShopOrder): TimelineStep[] => {
  const isRider = order.delivery_method === 'rider';
  const isCod = order.payment_method === 'cod';

  if (order.status === 'cancelled' || order.status === 'refunded') {
    return [
      { key: 'placed', label: 'สั่งซื้อแล้ว', caption: formatThaiDateTime(order.created_at), state: 'done' },
      {
        key: 'cancelled',
        label: order.status === 'refunded' ? 'คืนเงินแล้ว' : 'ยกเลิกแล้ว',
        caption: [formatThaiDateTime(order.cancelled_at), order.cancellation_reason].filter(Boolean).join(' · '),
        state: 'failed',
      },
    ];
  }

  let idx = STATUS_FLOW.indexOf(order.status as (typeof STATUS_FLOW)[number]);
  if (idx < 0) idx = 0;
  // เก็บเงินปลายทาง: ไม่ต้องรอชำระก่อน → ถือว่าผ่านขั้นชำระแล้ว
  if (isCod && idx === 0) idx = 1;

  const stateOf = (i: number): TimelineStep['state'] =>
    i <= idx || (i === 5 && order.status === 'completed') ? 'done' : i === idx + 1 ? 'current' : 'todo';

  return [
    { key: 'placed', label: 'สั่งซื้อแล้ว', caption: formatThaiDateTime(order.created_at), icon: 'receipt', state: 'done' },
    {
      key: 'paid',
      label: isCod ? 'เก็บเงินปลายทาง' : stateOf(1) === 'current' ? 'รอชำระเงิน' : 'ชำระเงินแล้ว',
      caption: isCod ? 'จ่ายเงินสดกับไรเดอร์ตอนรับของ' : formatThaiDateTime(order.paid_at),
      icon: isCod ? 'money' : 'credit-card',
      state: stateOf(1),
    },
    {
      key: 'processing',
      label: stateOf(2) === 'current' ? 'รอร้านยืนยันคำสั่งซื้อ' : 'ร้านรับคำสั่งซื้อแล้ว',
      icon: 'storefront',
      state: stateOf(2),
    },
    {
      key: 'shipped',
      label: isRider
        ? stateOf(3) === 'current' ? 'ร้านกำลังเตรียมของให้ไรเดอร์' : 'ไรเดอร์รับของแล้ว'
        : stateOf(3) === 'current' ? 'ร้านกำลังแพ็กสินค้า' : 'จัดส่งแล้ว',
      caption: formatThaiDateTime(order.shipped_at),
      icon: isRider ? 'moped' : 'package',
      state: stateOf(3),
    },
    {
      key: 'delivered',
      label: stateOf(4) === 'current' ? (isRider ? 'ไรเดอร์กำลังไปส่ง' : 'อยู่ระหว่างขนส่ง') : 'ส่งถึงแล้ว',
      caption: formatThaiDateTime(order.delivered_at),
      icon: 'map-pin',
      state: stateOf(4),
    },
    {
      key: 'completed',
      label: stateOf(5) === 'current' ? 'ได้รับของแล้วกดยืนยันได้เลย' : 'สำเร็จ',
      icon: 'seal-check',
      state: stateOf(5),
    },
  ];
};

// =====================================================
// แชทกับร้าน
// =====================================================

const ChatPanel: React.FC<{ orderId: number; canSend: boolean }> = ({ orderId, canSend }) => {
  // แผงแชทใช้ร่วมกับฝั่งร้าน (components/shop/OrderChatPanel) — ที่นี่แค่ผูก API ฝั่งผู้ซื้อ
  const fetchPage = useCallback((page: number) => getOrderMessages(orderId, { page, per_page: 50 }), [orderId]);
  const send = useCallback(
    (message: string, clientId: string) => sendOrderMessage(orderId, message, clientId),
    [orderId]
  );
  return (
    <OrderChatPanel
      fetchPage={fetchPage}
      send={send}
      canSend={canSend}
      placeholder="พิมพ์ข้อความถึงร้าน"
      inputLabel="ข้อความถึงร้าน"
      emptyText="มีคำถามเรื่องสินค้า ทักร้านได้เลย"
    />
  );
};

// =====================================================
// หน้าจอหลัก
// =====================================================

export default function OrderDetailScreen() {
  const { id, tab } = useLocalSearchParams<{ id: string; tab?: string }>();
  const orderId = /^\d+$/.test(String(id || '')) ? Number(id) : 0;
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [activeTab, setActiveTab] = useState<Tab>(tab === 'chat' ? 'chat' : 'detail');
  const [order, setOrder] = useState<ShopOrder | null>(null);
  const [tracking, setTracking] = useState<ShopOrderTracking | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<{ message: string; notFound: boolean } | null>(null);

  const [payment, setPayment] = useState<PaymentInstruction | null>(null);
  const [payState, setPayState] = useState<PromptPayState>('waiting');

  const [cancelOpen, setCancelOpen] = useState(false);
  const [cancelReason, setCancelReason] = useState<string | null>(null);
  const [cancelOther, setCancelOther] = useState('');
  const [cancelBusy, setCancelBusy] = useState(false);

  const [reviewItem, setReviewItem] = useState<ShopOrderItem | null>(null);
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState('');
  const [reviewBusy, setReviewBusy] = useState(false);

  const mountedRef = useRef(true);
  const loadingRef = useRef(false);
  const payBusyRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const applyOrder = useCallback((next: ShopOrder) => {
    setOrder(next);
    if (next.payment && next.payment.transaction_id) {
      setPayment(next.payment);
      setPayState('waiting');
    } else if (next.payment_status === 'paid') {
      setPayment((prev) => (prev ? prev : null));
      setPayState('paid');
    } else {
      setPayment(null);
    }
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'silent') => {
      if (!isAuthenticated || !orderId) {
        setLoading(false);
        if (!orderId) setError({ message: 'ไม่พบคำสั่งซื้อนี้', notFound: true });
        return;
      }
      if (loadingRef.current && mode === 'silent') return;
      loadingRef.current = true;
      if (mode === 'initial') setLoading(true);
      if (mode === 'refresh') setRefreshing(true);

      const [orderRes, trackRes] = await Promise.all([getMyOrder(orderId), getOrderTracking(orderId)]);
      loadingRef.current = false;
      if (!mountedRef.current) return;

      if (orderRes.success && orderRes.data) {
        applyOrder(orderRes.data);
        setError(null);
      } else if (!orderRes.success && mode !== 'silent') {
        setError({ message: orderRes.message, notFound: orderRes.status === 404 });
      }
      if (trackRes.success) setTracking(trackRes.data);
      setLoading(false);
      setRefreshing(false);
    },
    [isAuthenticated, orderId, applyOrder]
  );

  useEffect(() => {
    load('initial');
  }, [load]);

  // ---------- รีเฟรชอัตโนมัติ ----------
  const riderActive = !!order?.rider && ACTIVE_RIDER_STATUSES.includes(order.rider.status);
  const orderOpen = !!order && !['completed', 'cancelled', 'refunded'].includes(order.status);

  useFocusEffect(
    useCallback(() => {
      if (!orderOpen || activeTab !== 'detail') return undefined;
      const timer = setInterval(() => load('silent'), riderActive ? RIDER_POLL_MS : ORDER_POLL_MS);
      return () => clearInterval(timer);
    }, [orderOpen, riderActive, activeTab, load])
  );

  // poll สถานะพร้อมเพย์ที่ค้าง
  const pollPayment = useCallback(async () => {
    if (!payment?.transaction_id || payBusyRef.current) return;
    payBusyRef.current = true;
    const res = await getPaymentStatus(payment.transaction_id);
    payBusyRef.current = false;
    if (!mountedRef.current || !res.success) return;
    const st = String(res.data?.status || '');
    if (st === 'completed' || res.data?.order?.payment_status === 'paid') {
      setPayState('paid');
      resultHaptic('success');
      load('silent');
    } else if (st === 'failed' || st === 'cancelled') {
      setPayState('error');
    } else if (st === 'expired' || res.data?.is_expired === true) {
      setPayState('expired');
    }
  }, [payment?.transaction_id, load]);

  useFocusEffect(
    useCallback(() => {
      if (!payment?.transaction_id || payState !== 'waiting') return undefined;
      const timer = setInterval(pollPayment, PAY_POLL_MS);
      const sub = AppState.addEventListener('change', (s) => {
        if (s === 'active') pollPayment();
      });
      return () => {
        clearInterval(timer);
        sub.remove();
      };
    }, [payment?.transaction_id, payState, pollPayment])
  );

  // ---------- การกระทำ ----------
  const startPay = async (method: 'promptpay' | 'wallet') => {
    if (!order) return;
    const res = await payOrder(order.id, method);
    if (!mountedRef.current) return;
    if (!res.success) {
      resultHaptic('error');
      if (res.code === 'ALREADY_PAID') {
        load('silent');
        return;
      }
      if (res.code === 'INSUFFICIENT_BALANCE') {
        Alert.alert('ยอดเงินในกระเป๋าไม่พอ', res.message, [
          { text: 'ไว้ก่อน', style: 'cancel' },
          { text: 'เติมเงิน', onPress: () => router.push('/wallet-topup' as never) },
        ]);
        return;
      }
      Alert.alert('ชำระเงินไม่สำเร็จ', res.message);
      return;
    }
    const paid = res.data?.order?.payment_status === 'paid' || res.data?.status === 'completed';
    if (paid) {
      resultHaptic('success');
      setPayState('paid');
      Alert.alert('ชำระเงินแล้ว', 'ร้านได้รับคำสั่งซื้อแล้ว');
      load('silent');
      return;
    }
    setPayment({ ...res.data, order_id: res.data.order_id || order.id });
    setPayState('waiting');
  };

  const payWithWallet = () => {
    if (!order) return;
    Alert.alert(
      'จ่ายด้วยกระเป๋าเงิน?',
      `ระบบจะตัดเงิน ${formatBaht(order.total_amount, { decimals: 2 })} จากกระเป๋าทันที\nถ้าโอนพร้อมเพย์ไปแล้ว ไม่ต้องกดนะ`,
      [
        { text: 'ยังก่อน', style: 'cancel' },
        { text: 'ยืนยันจ่าย', onPress: () => startPay('wallet') },
      ]
    );
  };

  const confirmReceived = () => {
    if (!order) return;
    Alert.alert('ได้รับสินค้าครบแล้ว?', 'ยืนยันแล้วคำสั่งซื้อจะปิดงาน และร้านจะได้รับเงิน', [
      { text: 'ยังไม่ได้รับ', style: 'cancel' },
      {
        text: 'ได้รับแล้ว',
        onPress: async () => {
          const res = await confirmOrderReceived(order.id);
          if (!mountedRef.current) return;
          if (res.success && res.data) {
            resultHaptic('success');
            applyOrder(res.data);
            const reviewable = res.data.items.find((i) => i.can_review);
            if (reviewable) {
              Alert.alert('ขอบคุณที่ช้อปกับเรา', 'รีวิวสินค้าให้ร้านหน่อยไหม?', [
                { text: 'ไว้ทีหลัง', style: 'cancel' },
                { text: 'รีวิวเลย', onPress: () => openReview(reviewable) },
              ]);
            }
          } else if (!res.success) {
            resultHaptic('error');
            Alert.alert('ยืนยันไม่สำเร็จ', res.message);
            load('silent');
          }
        },
      },
    ]);
  };

  const submitCancel = async () => {
    if (!order || cancelBusy) return;
    const reason = cancelReason === 'other' ? cancelOther.trim() : cancelReason;
    if (!reason) {
      Alert.alert('เลือกเหตุผลก่อนนะ', 'บอกเหตุผลสั้นๆ ให้ร้านทราบหน่อย');
      return;
    }
    setCancelBusy(true);
    const res = await cancelMyOrder(order.id, reason.slice(0, 500));
    if (!mountedRef.current) return;
    setCancelBusy(false);
    if (res.success) {
      resultHaptic('success');
      setCancelOpen(false);
      if (res.data) applyOrder(res.data);
      Alert.alert('ยกเลิกคำสั่งซื้อแล้ว', res.message || (res.meta?.refunded ? 'คืนเงินเข้ากระเป๋าให้แล้ว' : 'ยกเลิกเรียบร้อย'));
      load('silent');
    } else {
      resultHaptic('error');
      Alert.alert('ยกเลิกไม่สำเร็จ', res.message);
      load('silent');
    }
  };

  const openReview = (item: ShopOrderItem) => {
    setReviewItem(item);
    setRating(5);
    setComment('');
  };

  const submitReview = async () => {
    if (!order || !reviewItem || reviewBusy) return;
    if (comment.trim().length < 2) {
      Alert.alert('เขียนรีวิวสั้นๆ ก่อนนะ', 'บอกความรู้สึกต่อสินค้าอย่างน้อย 2 ตัวอักษร');
      return;
    }
    setReviewBusy(true);
    const res = await reviewOrderItem(order.id, reviewItem.id, { rating, comment: comment.trim().slice(0, 1000) });
    if (!mountedRef.current) return;
    setReviewBusy(false);
    if (res.success || res.code === 'ALREADY_REVIEWED') {
      resultHaptic('success');
      setReviewItem(null);
      Alert.alert(res.success ? 'ขอบคุณสำหรับรีวิว' : 'รีวิวแล้ว', res.success ? 'รีวิวของคุณช่วยร้านและผู้ซื้อคนอื่นได้มาก' : res.message);
      load('silent');
    } else {
      resultHaptic('error');
      Alert.alert('ส่งรีวิวไม่สำเร็จ', res.message);
    }
  };

  const copyTracking = async (value: string) => {
    try {
      await Clipboard.setStringAsync(value);
      resultHaptic('success');
      Alert.alert('คัดลอกแล้ว', `เลขพัสดุ ${value}`);
    } catch {
      // คัดลอกไม่ได้ก็ไม่เป็นไร
    }
  };

  const timeline = useMemo(() => (order ? buildTimeline(order) : []), [order]);

  // ---------- render ----------
  if (!isAuthenticated) {
    return (
      <Screen title="คำสั่งซื้อ" scroll={false}>
        <EmptyState icon="lock-key" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (loading && !order) {
    return (
      <Screen title="คำสั่งซื้อ" scroll={false}>
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </Screen>
    );
  }

  if (!order) {
    return (
      <Screen title="คำสั่งซื้อ" scroll={false}>
        <EmptyState
          variant={error?.notFound ? 'empty' : 'error'}
          icon={error?.notFound ? 'magnifying-glass' : undefined}
          title={error?.notFound ? 'ไม่พบคำสั่งซื้อนี้' : undefined}
          message={error?.notFound ? 'คำสั่งซื้ออาจไม่ใช่ของบัญชีนี้' : error?.message}
          actionLabel={error?.notFound ? 'ดูคำสั่งซื้อทั้งหมด' : 'ลองใหม่'}
          onAction={error?.notFound ? () => router.replace('/(tabs)/orders' as never) : () => load('initial')}
        />
      </Screen>
    );
  }

  const tabs = (
    <View style={styles.tabs}>
      <Chip label="รายละเอียด" icon="receipt" selected={activeTab === 'detail'} onPress={() => setActiveTab('detail')} />
      <Chip label="แชทกับร้าน" icon="chat-circle-dots" selected={activeTab === 'chat'} onPress={() => setActiveTab('chat')} />
    </View>
  );

  if (activeTab === 'chat') {
    return (
      <Screen title={order.store?.name || 'แชทกับร้าน'} subtitle={order.order_number} scroll={false}>
        <View style={styles.chatTabs}>{tabs}</View>
        <ChatPanel orderId={order.id} canSend={!['cancelled', 'refunded'].includes(order.status)} />
      </Screen>
    );
  }

  const isRider = order.delivery_method === 'rider';
  const rider = order.rider;
  const codWaiting =
    order.payment_method === 'cod' && order.payment_status !== 'paid' && ['delivered', 'completed'].includes(order.status);
  const history = tracking?.history ?? [];

  return (
    <Screen
      title="คำสั่งซื้อ"
      subtitle={order.order_number}
      refreshing={refreshing}
      onRefresh={() => load('refresh')}
    >
      {tabs}

      {/* ---------- หัวคำสั่งซื้อ ---------- */}
      <Card3D gradientBorder shadow="lg" padding={spacing.lg} style={styles.block}>
        <View style={styles.rowBetween}>
          <IconTile icon="receipt" tone="gold" size={48} weight="fill" />
          <View style={styles.flex}>
            <Text style={[typography.caption, { color: colors.textMuted }]}>สถานะ</Text>
            <Text style={[typography.serif, { color: colors.textStrong }]}>{order.status_label}</Text>
          </View>
          <Pill label={order.payment_status_label || order.payment_status} tone={order.payment_status === 'paid' ? 'success' : 'warning'} />
        </View>
        <View style={[styles.metaRow, { borderTopColor: colors.divider }]}>
          {!!order.store?.name && <MetaItem icon="storefront" text={order.store.name} color={colors.text} />}
          <View style={styles.metaLine}>
            <MetaItem icon={isRider ? 'moped' : 'package'} text={isRider ? 'ส่งด้วยไรเดอร์' : 'ส่งพัสดุ'} />
            {!!order.payment_method_label && <MetaItem icon="credit-card" text={order.payment_method_label} />}
          </View>
          <MetaItem icon="clock" text={`สั่งเมื่อ ${formatThaiDateTime(order.created_at)}`} color={colors.textFaint} />
        </View>
      </Card3D>

      {/* ---------- ชำระเงินที่ค้าง ---------- */}
      {payment && order.payment_status !== 'paid' && (
        <>
          <PromptPayQR payment={payment} state={payState} onRenew={() => startPay('promptpay')} />
          {payState !== 'paid' && (
            <Button3D title="จ่ายด้วยกระเป๋าเงินแทน" icon="wallet" variant="ghost" size="sm" onPress={payWithWallet} style={styles.center} />
          )}
        </>
      )}
      {!payment && order.can_pay && (
        <Card3D padding={spacing.lg} radius={20} style={styles.block}>
          <View style={styles.rowBetween}>
            <IconTile icon="hourglass" tone="warning" />
            <View style={styles.flex}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>ยังไม่ได้ชำระเงิน</Text>
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>
                ยอด {formatBaht(order.total_amount, { decimals: 2 })} ชำระแล้วร้านจะเริ่มเตรียมสินค้าให้ทันที
              </Text>
            </View>
          </View>
          <View style={styles.buttonRow}>
            <Button3D title="สแกนพร้อมเพย์" icon="qr-code" size="md" onPress={() => startPay('promptpay')} style={styles.flex} />
            <Button3D title="กระเป๋าเงิน" icon="wallet" variant="secondary" size="md" onPress={payWithWallet} style={styles.flex} />
          </View>
        </Card3D>
      )}
      {codWaiting && (
        <NoticeBanner tone="info" icon="money" text="รอยืนยันยอดเก็บปลายทางจากไรเดอร์" style={styles.block} />
      )}

      {/* ---------- ไรเดอร์ ---------- */}
      {isRider && rider && (
        <Card3D padding={spacing.lg} radius={20} style={styles.block}>
          <View style={styles.rowBetween}>
            <IconTile icon="moped" weight="fill" />
            <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>ไรเดอร์</Text>
            <Pill label={rider.status_label} tone={ACTIVE_RIDER_STATUSES.includes(rider.status) ? 'gold' : 'neutral'} />
          </View>
          {rider.status === 'not_requested' && (
            <Text style={[typography.bodySm, styles.gapTopSm, { color: colors.textMuted }]}>
              ร้านจะเรียกไรเดอร์เมื่อเตรียมของเสร็จ
            </Text>
          )}
          {rider.status === 'pending' && (
            <Text style={[typography.bodySm, styles.gapTopSm, { color: colors.textMuted }]}>กำลังหาไรเดอร์ใกล้ร้านให้อยู่นะ</Text>
          )}
          {!!rider.rider && (
            <View style={[styles.riderRow, { backgroundColor: colors.inset, borderColor: colors.border }]}>
              <View style={[styles.riderAvatar, { backgroundColor: colors.goldSoft, borderColor: colors.gold }]}>
                <Icon name="user" size={24} color={colors.goldDeep} weight="fill" />
              </View>
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{rider.rider.name || 'ไรเดอร์'}</Text>
                {!!rider.rider.vehicle_plate && (
                  <Text style={[typography.caption, { color: colors.textMuted }]}>ทะเบียน {rider.rider.vehicle_plate}</Text>
                )}
              </View>
              {!!rider.rider.phone && (
                <Button3D title="โทร" icon="phone" size="sm" variant="navy" onPress={() => callPhone(rider.rider?.phone)} />
              )}
            </View>
          )}
          {!!rider.job_id && ACTIVE_RIDER_STATUSES.includes(rider.status) && rider.status !== 'pending' && (
            <View style={styles.gapTop}>
              <RiderTracker
                source="shop"
                orderId={order.id}
                enabled={riderActive}
                showRiderInfo={false}
                dropoffFallback={
                  order.shipping?.latitude != null && order.shipping?.longitude != null
                    ? { latitude: Number(order.shipping.latitude), longitude: Number(order.shipping.longitude) }
                    : null
                }
                onJobStatusChange={() => load('silent')}
              />
            </View>
          )}
          {isTrustedWebUrl(rider.tracking_url) && (
            <Button3D
              title="เปิดหน้าติดตามบนเว็บ"
              icon="globe"
              size="sm"
              variant="secondary"
              fullWidth
              onPress={() => openHttpsLink(rider.tracking_url, 'ติดตามไรเดอร์')}
              style={styles.gapTop}
            />
          )}
          {riderActive && (
            <MetaItem icon="arrows-clockwise" text="อัปเดตสถานะอัตโนมัติทุก 15 วินาที" color={colors.textFaint} style={styles.gapTopSm} />
          )}
        </Card3D>
      )}

      {/* ---------- พัสดุ ---------- */}
      {!isRider && (!!order.tracking_number || history.length > 0) && (
        <Card3D padding={spacing.lg} radius={20} style={styles.block}>
          <View style={styles.rowBetween}>
            <IconTile icon="package" weight="fill" />
            <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>การจัดส่ง</Text>
          </View>
          {!!order.tracking_number && (
            <View style={[styles.trackBox, { backgroundColor: colors.inset, borderColor: colors.border }]}>
              <View style={styles.flex}>
                <Text style={[typography.caption, { color: colors.textMuted }]}>{order.shipping_provider || 'เลขพัสดุ'}</Text>
                <Text selectable style={[typography.h3, styles.trackNumber, { color: colors.textStrong }]}>
                  {order.tracking_number}
                </Text>
              </View>
              <Button3D title="คัดลอก" icon="copy" size="sm" variant="secondary" onPress={() => copyTracking(order.tracking_number!)} />
            </View>
          )}
          {!!order.tracking_url && (
            <Button3D
              title="ติดตามพัสดุ"
              icon="magnifying-glass"
              size="sm"
              variant="secondary"
              onPress={() => openHttpsLink(order.tracking_url, 'ติดตามพัสดุ')}
              style={styles.gapTop}
            />
          )}
          {history.length > 0 && (
            <View style={styles.gapTop}>
              {history.map((h, i) => (
                <View key={h.id} style={[styles.historyRow, i > 0 && { borderTopColor: colors.divider, borderTopWidth: 1 }]}>
                  <View style={[styles.historyDot, { backgroundColor: i === 0 ? colors.gold : colors.border }]} />
                  <View style={styles.flex}>
                    <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{h.title}</Text>
                    {!!h.description && <Text style={[typography.caption, { color: colors.text }]}>{h.description}</Text>}
                    <Text style={[typography.micro, { color: colors.textFaint }]}>
                      {[formatThaiDateTime(h.tracked_at), h.location].filter(Boolean).join(' · ')}
                    </Text>
                  </View>
                </View>
              ))}
            </View>
          )}
        </Card3D>
      )}

      {/* ---------- ไทม์ไลน์ ---------- */}
      <SectionHeader title="สถานะคำสั่งซื้อ" icon="clock" style={styles.section} />
      <Card3D padding={spacing.lg} radius={20} style={styles.block}>
        <StatusTimeline steps={timeline} />
      </Card3D>

      {/* ---------- สินค้า ---------- */}
      <SectionHeader title="สินค้า" icon="shopping-bag-open" style={styles.section} />
      <Card3D padding={0} radius={20} style={styles.block}>
        {order.items.map((item, i) => (
          <View key={item.id} style={styles.itemWrap}>
            <View style={[styles.itemRow, i > 0 && { borderTopColor: colors.divider, borderTopWidth: 1 }]}>
              <Pressable
                onPress={() => router.push(`/product/${item.product_id}` as never)}
                accessibilityRole="button"
                accessibilityLabel={`ดูสินค้า ${item.product_name}`}
              >
                <ThumbImage uri={item.product_image} size={64} radius={16} transition={0} />
              </Pressable>
              <View style={styles.flex}>
                <Text numberOfLines={2} style={[typography.bodyStrong, { color: colors.textStrong }]}>{item.product_name}</Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  {formatBaht(item.unit_price)} × {item.quantity}
                </Text>
                {item.can_review && (
                  <Button3D title="รีวิวสินค้า" icon="star" size="sm" variant="secondary" onPress={() => openReview(item)} style={styles.reviewButton} />
                )}
              </View>
              <PriceText amount={item.total} size="sm" tone="strong" />
            </View>
          </View>
        ))}
      </Card3D>

      {/* ---------- ที่อยู่ ---------- */}
      {order.shipping && (
        <>
          <SectionHeader title="ที่อยู่จัดส่ง" icon="map-pin" style={styles.section} />
          <Card3D padding={spacing.lg} radius={20} style={styles.block}>
            <View style={styles.addressRow}>
              <IconTile icon="map-pin" tone="gold" weight="fill" />
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
                  {order.shipping.name}
                  {order.shipping.phone ? ` · ${order.shipping.phone}` : ''}
                </Text>
                <Text style={[typography.bodySm, styles.gapTopSm, { color: colors.text }]}>
                  {order.shipping.full_address ||
                    [order.shipping.address, order.shipping.address_line_2, order.shipping.subdistrict, order.shipping.district, order.shipping.province, order.shipping.postal_code]
                      .filter(Boolean)
                      .join(' ')}
                </Text>
              </View>
            </View>
            {!!order.note && (
              <View style={[styles.noteBox, { backgroundColor: colors.inset, borderLeftColor: colors.gold }]}>
                <Text style={[typography.caption, { color: colors.textMuted }]}>หมายเหตุ: {order.note}</Text>
              </View>
            )}
          </Card3D>
        </>
      )}

      {/* ---------- ยอดเงิน ---------- */}
      <Card3D variant="inset" padding={spacing.lg} radius={20} style={styles.block}>
        <View style={styles.rowBetween}>
          <Text style={[typography.body, { color: colors.text }]}>ค่าสินค้า</Text>
          <PriceText amount={order.subtotal} size="sm" tone="strong" />
        </View>
        <View style={[styles.rowBetween, styles.gapTopSm]}>
          <Text style={[typography.body, { color: colors.text }]}>ค่าจัดส่ง</Text>
          {order.shipping_fee > 0 ? (
            <PriceText amount={order.shipping_fee} size="sm" tone="strong" />
          ) : (
            <Pill label="ส่งฟรี" tone="success" icon="truck" />
          )}
        </View>
        {order.discount > 0 && (
          <View style={[styles.rowBetween, styles.gapTopSm]}>
            <Text style={[typography.body, { color: colors.text }]}>ส่วนลด</Text>
            <PriceText amount={-order.discount} size="sm" tone="success" />
          </View>
        )}
        <View style={[styles.rowBetween, styles.total, { borderTopColor: colors.divider }]}>
          <Text style={[typography.h3, { color: colors.textStrong }]}>ยอดรวม</Text>
          <PriceText amount={order.total_amount} decimals={2} size="lg" tone="gold" />
        </View>
      </Card3D>

      {/* ---------- ปุ่มดำเนินการ ---------- */}
      {order.can_confirm_received && (
        <Button3D title="ได้รับสินค้าแล้ว" icon="check-circle" variant="primary" size="lg" fullWidth onPress={confirmReceived} style={styles.gapTop} />
      )}
      {order.can_cancel && (
        <Button3D
          title="ยกเลิกคำสั่งซื้อ"
          variant="ghost"
          size="md"
          fullWidth
          onPress={() => {
            setCancelReason(null);
            setCancelOther('');
            setCancelOpen(true);
          }}
          style={styles.gapTop}
        />
      )}
      <Button3D
        title="ต้องการความช่วยเหลือ"
        icon="headset"
        variant="ghost"
        size="sm"
        onPress={() => router.push('/support' as never)}
        style={[styles.center, styles.gapTop]}
      />

      {/* ---------- ยกเลิก ---------- */}
      <FormSheet
        visible={cancelOpen}
        icon="x-circle"
        title="ยกเลิกคำสั่งซื้อ"
        description={
          order.payment_status === 'paid'
            ? 'ยกเลิกแล้วระบบจะคืนเงินเข้ากระเป๋าเงินของคุณ'
            : 'คำสั่งซื้อนี้ยังไม่ได้ชำระเงิน ยกเลิกได้เลย'
        }
        submitLabel="ยืนยันยกเลิก"
        submitVariant="danger"
        submitDisabled={!cancelReason || (cancelReason === 'other' && cancelOther.trim().length < 2)}
        onSubmit={submitCancel}
        busy={cancelBusy}
        cancelLabel="ไม่ยกเลิก"
        onClose={() => setCancelOpen(false)}
      >
        <View style={styles.reasonWrap}>
          {CANCEL_REASONS.map((r) => (
            <Chip key={r} label={r} size="sm" selected={cancelReason === r} onPress={() => setCancelReason(r)} />
          ))}
          <Chip label="อื่นๆ" size="sm" selected={cancelReason === 'other'} onPress={() => setCancelReason('other')} />
        </View>
        {cancelReason === 'other' && (
          <Field label="เหตุผล" value={cancelOther} onChangeText={setCancelOther} multiline maxLength={500} placeholder="บอกเหตุผลสั้นๆ" />
        )}
      </FormSheet>

      {/* ---------- รีวิว ---------- */}
      <FormSheet
        visible={!!reviewItem}
        icon="star"
        title="รีวิวสินค้า"
        description={reviewItem?.product_name}
        submitLabel="ส่งรีวิว"
        submitDisabled={comment.trim().length < 2}
        onSubmit={submitReview}
        busy={reviewBusy}
        cancelLabel="ไว้ทีหลัง"
        onClose={() => setReviewItem(null)}
      >
        <ScrollView horizontal contentContainerStyle={styles.stars} scrollEnabled={false}>
          {[1, 2, 3, 4, 5].map((n) => (
            <Pressable
              key={n}
              onPress={() => setRating(n)}
              accessibilityRole="button"
              accessibilityLabel={`ให้ ${n} ดาว`}
              hitSlop={6}
              style={({ pressed }) => [styles.star, pressed && styles.starPressed]}
            >
              <Icon name="star" size={38} color={n <= rating ? colors.gold : colors.border} weight="fill" />
            </Pressable>
          ))}
        </ScrollView>
        <Field
          label="เล่าให้ฟังหน่อย"
          value={comment}
          onChangeText={setComment}
          multiline
          maxLength={1000}
          placeholder="สินค้าเป็นยังไงบ้าง ตรงปกไหม"
        />
      </FormSheet>
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
  center: {
    alignSelf: 'center',
  },
  tabs: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.lg,
    paddingHorizontal: 0,
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
    gap: spacing.md,
  },
  metaRow: {
    borderTopWidth: 1,
    marginTop: spacing.md,
    paddingTop: spacing.md,
    gap: 6,
  },
  metaLine: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    columnGap: spacing.lg,
    rowGap: 6,
  },
  gapTop: {
    marginTop: spacing.md,
  },
  gapTopSm: {
    marginTop: spacing.xs,
  },
  buttonRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.lg,
  },
  riderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.md,
    borderRadius: 16,
    borderWidth: 1,
    padding: spacing.md,
  },
  riderAvatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  trackBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.md,
    borderWidth: 1,
    padding: spacing.md,
    marginTop: spacing.md,
  },
  trackNumber: {
    letterSpacing: 0.6,
    fontVariant: ['tabular-nums'],
  },
  historyRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
    paddingVertical: spacing.sm,
  },
  historyDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    marginTop: 8,
  },
  itemWrap: {
    paddingHorizontal: spacing.lg,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.md,
  },
  reviewButton: {
    alignSelf: 'flex-start',
    marginTop: spacing.xs,
  },
  addressRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  noteBox: {
    marginTop: spacing.md,
    borderRadius: 12,
    borderLeftWidth: 3,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  total: {
    borderTopWidth: 1,
    marginTop: spacing.md,
    paddingTop: spacing.md,
  },
  reasonWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  stars: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.lg,
    justifyContent: 'center',
    flexGrow: 1,
  },
  star: {
    padding: spacing.xs,
  },
  starPressed: {
    transform: [{ scale: 0.9 }],
  },
  chatTabs: {
    paddingHorizontal: spacing.screen,
  },
});
