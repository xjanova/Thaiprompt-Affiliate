/**
 * รายละเอียดคำสั่งซื้อ (ผู้ซื้อ) — GET /orders/{id} + /orders/{id}/tracking (SHOP-11 / SHOP-20 / CC-20)
 *
 * - ไทม์ไลน์ตาม enum จริงของ orders.status (ไม่มี 'confirmed')
 * - ยังไม่จ่าย: แสดง QR พร้อมเพย์ที่ค้าง + poll สถานะ / ขอ QR ใหม่ / จ่ายด้วยกระเป๋า
 * - ส่งด้วยไรเดอร์: การ์ดไรเดอร์ (ชื่อ ทะเบียน โทร) + แผนที่ตำแหน่งไรเดอร์สด (GET /orders/shop/{id}/rider-location)
 *   + แชร์ตำแหน่งของฉันให้ไรเดอร์ + ลิงก์ติดตามบนเว็บ — รีเฟรชทุก 15 วินาทีระหว่างไรเดอร์วิ่ง
 * - ส่งพัสดุ: เลขพัสดุ (คัดลอกได้) + ประวัติการขนส่ง
 * - ยืนยันรับสินค้า · ยกเลิก (เมื่อ server อนุญาต) · รีวิวสินค้า · แชทกับร้าน (?tab=chat)
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  AppState,
  FlatList,
  KeyboardAvoidingView,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { Image } from 'expo-image';
import * as Clipboard from 'expo-clipboard';
import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
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
  type ShopOrderMessage,
  type ShopOrderTracking,
} from '@/services/api/shopApi';
import { isTrustedWebUrl } from '@/utils/linking';
import {
  Button3D,
  Card3D,
  Chip,
  EmptyState,
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
  PromptPayQR,
  StatusTimeline,
  callPhone,
  formatThaiDateTime,
  openHttpsLink,
  type PromptPayState,
  type TimelineStep,
} from '@/components/shop';
import { RiderTracker } from '@/components/taladsod';
import { useTheme, clayShadowStyle, radii, spacing, typography } from '@/theme';

type Tab = 'detail' | 'chat';

const STATUS_FLOW = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'completed'] as const;
const CANCEL_REASONS = ['เปลี่ยนใจไม่ซื้อแล้ว', 'สั่งผิด อยากแก้รายการ', 'ได้ของจากที่อื่นแล้ว', 'รอนานเกินไป'];
const PAY_POLL_MS = 4000;
const RIDER_POLL_MS = 15000;
const ORDER_POLL_MS = 45000;
const CHAT_POLL_MS = 10000;

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
    { key: 'placed', label: 'สั่งซื้อแล้ว', caption: formatThaiDateTime(order.created_at), icon: '🧾', state: 'done' },
    {
      key: 'paid',
      label: isCod ? 'เก็บเงินปลายทาง' : stateOf(1) === 'current' ? 'รอชำระเงิน' : 'ชำระเงินแล้ว',
      caption: isCod ? 'จ่ายเงินสดกับไรเดอร์ตอนรับของ' : formatThaiDateTime(order.paid_at),
      icon: '💳',
      state: stateOf(1),
    },
    {
      key: 'processing',
      label: stateOf(2) === 'current' ? 'รอร้านยืนยันคำสั่งซื้อ' : 'ร้านรับคำสั่งซื้อแล้ว',
      icon: '🏪',
      state: stateOf(2),
    },
    {
      key: 'shipped',
      label: isRider
        ? stateOf(3) === 'current' ? 'ร้านกำลังเตรียมของให้ไรเดอร์' : 'ไรเดอร์รับของแล้ว'
        : stateOf(3) === 'current' ? 'ร้านกำลังแพ็กสินค้า' : 'จัดส่งแล้ว',
      caption: formatThaiDateTime(order.shipped_at),
      icon: isRider ? '🛵' : '📦',
      state: stateOf(3),
    },
    {
      key: 'delivered',
      label: stateOf(4) === 'current' ? (isRider ? 'ไรเดอร์กำลังไปส่ง' : 'อยู่ระหว่างขนส่ง') : 'ส่งถึงแล้ว',
      caption: formatThaiDateTime(order.delivered_at),
      icon: '📍',
      state: stateOf(4),
    },
    {
      key: 'completed',
      label: stateOf(5) === 'current' ? 'ได้รับของแล้วกดยืนยันได้เลย' : 'สำเร็จ',
      icon: '🎉',
      state: stateOf(5),
    },
  ];
};

// =====================================================
// แชทกับร้าน
// =====================================================

const ChatPanel: React.FC<{ orderId: number; canSend: boolean }> = ({ orderId, canSend }) => {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const [messages, setMessages] = useState<ShopOrderMessage[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [text, setText] = useState('');
  const [sending, setSending] = useState(false);
  const mountedRef = useRef(true);
  const busyRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(
    async (silent: boolean) => {
      if (busyRef.current) return;
      busyRef.current = true;
      if (!silent) setLoading(true);
      const res = await getOrderMessages(orderId, { per_page: 50 });
      busyRef.current = false;
      if (!mountedRef.current) return;
      if (res.success) {
        setMessages(Array.isArray(res.data?.messages) ? res.data.messages : []);
        setError(null);
      } else if (!silent) {
        setError(res.message);
      }
      setLoading(false);
    },
    [orderId]
  );

  useFocusEffect(
    useCallback(() => {
      load(false);
      const timer = setInterval(() => load(true), CHAT_POLL_MS);
      return () => clearInterval(timer);
    }, [load])
  );

  const send = async () => {
    const message = text.trim();
    if (!message || sending) return;
    setSending(true);
    const res = await sendOrderMessage(orderId, message.slice(0, 2000));
    if (!mountedRef.current) return;
    setSending(false);
    if (res.success) {
      setText('');
      if (res.data) setMessages((prev) => [res.data, ...prev.filter((m) => m.id !== res.data.id)]);
    } else {
      Alert.alert('ส่งข้อความไม่สำเร็จ', res.message);
    }
  };

  if (loading && messages.length === 0) {
    return <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />;
  }

  return (
    <KeyboardAvoidingView style={styles.flex} behavior="padding">
      <FlatList
        data={messages}
        inverted
        keyExtractor={(m) => String(m.id)}
        contentContainerStyle={styles.chatList}
        keyboardShouldPersistTaps="handled"
        ListEmptyComponent={
          // รายการกลับหัว (inverted) → กลับหัวช่องว่างอีกครั้งให้อ่านได้ปกติ
          <View style={styles.flipped}>
            {error ? (
              <EmptyState compact variant="error" message={error} onAction={() => load(false)} />
            ) : (
              <View style={styles.chatEmpty}>
                <Text style={[typography.body, { color: colors.textMuted }]}>💬 มีคำถามเรื่องสินค้า ทักร้านได้เลย</Text>
              </View>
            )}
          </View>
        }
        renderItem={({ item }) => {
          const mine = item.is_mine;
          return (
            <View style={[styles.bubbleRow, mine ? styles.bubbleRight : styles.bubbleLeft]}>
              <View
                style={[
                  styles.bubble,
                  {
                    backgroundColor: item.is_system_message ? colors.infoSoft : mine ? colors.goldSoft : colors.card,
                    borderColor: colors.border,
                  },
                ]}
              >
                {!mine && !!item.sender_name && (
                  <Text style={[typography.micro, { color: colors.goldDeep }]}>{item.sender_name}</Text>
                )}
                {!!item.message && <Text style={[typography.body, { color: colors.textStrong }]}>{item.message}</Text>}
                {!!item.attachment && item.attachment_type === 'image' && isTrustedWebUrl(item.attachment) && (
                  <Image source={{ uri: item.attachment }} style={styles.chatImage} contentFit="cover" />
                )}
                <Text style={[typography.micro, styles.bubbleTime, { color: colors.textFaint }]}>
                  {formatThaiDateTime(item.created_at)}
                </Text>
              </View>
            </View>
          );
        }}
      />
      {canSend ? (
        <View
          style={[
            styles.chatInputBar,
            { backgroundColor: colors.card, paddingBottom: Math.max(insets.bottom, spacing.sm) },
            clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
          ]}
        >
          <TextInput
            value={text}
            onChangeText={setText}
            placeholder="พิมพ์ข้อความถึงร้าน"
            placeholderTextColor={colors.textFaint}
            multiline
            maxLength={2000}
            style={[typography.body, styles.chatInput, { backgroundColor: colors.inset, color: colors.textStrong }]}
            accessibilityLabel="ข้อความถึงร้าน"
          />
          <Button3D title="ส่ง" size="sm" onPress={send} loading={sending} disabled={!text.trim()} />
        </View>
      ) : (
        <Text style={[typography.caption, styles.chatClosed, { color: colors.textMuted }]}>
          คำสั่งซื้อนี้ปิดแล้ว ส่งข้อความเพิ่มไม่ได้
        </Text>
      )}
    </KeyboardAvoidingView>
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
              Alert.alert('ขอบคุณที่ช้อปกับเรา 🎉', 'รีวิวสินค้าให้ร้านหน่อยไหม?', [
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
      Alert.alert(res.success ? 'ขอบคุณสำหรับรีวิว 💛' : 'รีวิวแล้ว', res.success ? 'รีวิวของคุณช่วยร้านและผู้ซื้อคนอื่นได้มาก' : res.message);
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
        <EmptyState icon="🔐" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
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
          icon={error?.notFound ? '🔍' : undefined}
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
      <Chip label="รายละเอียด" icon="🧾" selected={activeTab === 'detail'} onPress={() => setActiveTab('detail')} />
      <Chip label="แชทกับร้าน" icon="💬" selected={activeTab === 'chat'} onPress={() => setActiveTab('chat')} />
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
      <Card3D gradientBorder padding={spacing.lg} style={styles.block}>
        <View style={styles.rowBetween}>
          <View style={styles.flex}>
            <Text style={[typography.caption, { color: colors.textMuted }]}>สถานะ</Text>
            <Text style={[typography.h2, { color: colors.textStrong }]}>{order.status_label}</Text>
          </View>
          <Pill label={order.payment_status_label || order.payment_status} tone={order.payment_status === 'paid' ? 'success' : 'warning'} />
        </View>
        <View style={[styles.metaRow, { borderTopColor: colors.divider }]}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>
            {order.store?.name ? `🏪 ${order.store.name} · ` : ''}
            {isRider ? '🛵 ส่งด้วยไรเดอร์' : '📦 ส่งพัสดุ'} · {order.payment_method_label}
          </Text>
          <Text style={[typography.micro, { color: colors.textFaint }]}>สั่งเมื่อ {formatThaiDateTime(order.created_at)}</Text>
        </View>
      </Card3D>

      {/* ---------- ชำระเงินที่ค้าง ---------- */}
      {payment && order.payment_status !== 'paid' && (
        <>
          <PromptPayQR payment={payment} state={payState} onRenew={() => startPay('promptpay')} />
          {payState !== 'paid' && (
            <Button3D title="จ่ายด้วยกระเป๋าเงินแทน" icon="👛" variant="ghost" size="sm" onPress={payWithWallet} style={styles.center} />
          )}
        </>
      )}
      {!payment && order.can_pay && (
        <Card3D padding={spacing.lg} style={styles.block}>
          <Text style={[typography.h3, { color: colors.textStrong }]}>ยังไม่ได้ชำระเงิน</Text>
          <Text style={[typography.bodySm, { color: colors.textMuted }]}>
            ยอด {formatBaht(order.total_amount, { decimals: 2 })} ชำระแล้วร้านจะเริ่มเตรียมสินค้าให้ทันที
          </Text>
          <View style={styles.buttonRow}>
            <Button3D title="สแกนพร้อมเพย์" icon="📱" size="md" onPress={() => startPay('promptpay')} style={styles.flex} />
            <Button3D title="กระเป๋าเงิน" icon="👛" variant="secondary" size="md" onPress={payWithWallet} style={styles.flex} />
          </View>
        </Card3D>
      )}
      {codWaiting && (
        <Card3D variant="flat" padding={spacing.md} style={styles.block}>
          <Text style={[typography.bodySm, { color: colors.info }]}>💵 รอยืนยันยอดเก็บปลายทางจากไรเดอร์</Text>
        </Card3D>
      )}

      {/* ---------- ไรเดอร์ ---------- */}
      {isRider && rider && (
        <Card3D padding={spacing.lg} style={styles.block}>
          <View style={styles.rowBetween}>
            <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>🛵 ไรเดอร์</Text>
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
            <View style={styles.riderRow}>
              <View style={[styles.riderAvatar, { backgroundColor: colors.goldSoft }]}>
                <Text style={styles.riderAvatarIcon}>🧑‍✈️</Text>
              </View>
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{rider.rider.name || 'ไรเดอร์'}</Text>
                {!!rider.rider.vehicle_plate && (
                  <Text style={[typography.caption, { color: colors.textMuted }]}>ทะเบียน {rider.rider.vehicle_plate}</Text>
                )}
              </View>
              {!!rider.rider.phone && (
                <Button3D title="โทร" icon="📞" size="sm" variant="secondary" onPress={() => callPhone(rider.rider?.phone)} />
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
              icon="🌐"
              size="sm"
              variant="secondary"
              fullWidth
              onPress={() => openHttpsLink(rider.tracking_url, 'ติดตามไรเดอร์')}
              style={styles.gapTop}
            />
          )}
          {riderActive && (
            <Text style={[typography.micro, styles.gapTopSm, { color: colors.textFaint }]}>อัปเดตสถานะอัตโนมัติทุก 15 วินาที</Text>
          )}
        </Card3D>
      )}

      {/* ---------- พัสดุ ---------- */}
      {!isRider && (!!order.tracking_number || history.length > 0) && (
        <Card3D padding={spacing.lg} style={styles.block}>
          <Text style={[typography.h3, { color: colors.textStrong }]}>📦 การจัดส่ง</Text>
          {!!order.tracking_number && (
            <View style={[styles.trackBox, { backgroundColor: colors.inset }]}>
              <View style={styles.flex}>
                <Text style={[typography.caption, { color: colors.textMuted }]}>{order.shipping_provider || 'เลขพัสดุ'}</Text>
                <Text selectable style={[typography.h3, { color: colors.textStrong }]}>
                  {order.tracking_number}
                </Text>
              </View>
              <Button3D title="คัดลอก" size="sm" variant="secondary" onPress={() => copyTracking(order.tracking_number!)} />
            </View>
          )}
          {!!order.tracking_url && (
            <Button3D
              title="ติดตามพัสดุ"
              icon="🔎"
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
                  <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{h.title}</Text>
                  {!!h.description && <Text style={[typography.caption, { color: colors.text }]}>{h.description}</Text>}
                  <Text style={[typography.micro, { color: colors.textFaint }]}>
                    {[formatThaiDateTime(h.tracked_at), h.location].filter(Boolean).join(' · ')}
                  </Text>
                </View>
              ))}
            </View>
          )}
        </Card3D>
      )}

      {/* ---------- ไทม์ไลน์ ---------- */}
      <SectionHeader title="สถานะคำสั่งซื้อ" icon="🕒" style={styles.section} />
      <Card3D padding={spacing.lg} style={styles.block}>
        <StatusTimeline steps={timeline} />
      </Card3D>

      {/* ---------- สินค้า ---------- */}
      <SectionHeader title="สินค้า" icon="🛍️" style={styles.section} />
      <Card3D padding={spacing.lg} style={styles.block}>
        {order.items.map((item, i) => (
          <View key={item.id} style={[styles.itemRow, i > 0 && { borderTopColor: colors.divider, borderTopWidth: 1 }]}>
            <Pressable onPress={() => router.push(`/product/${item.product_id}` as never)} accessibilityRole="button" accessibilityLabel={`ดูสินค้า ${item.product_name}`}>
              {item.product_image ? (
                <Image source={{ uri: item.product_image }} style={[styles.thumb, { backgroundColor: colors.inset }]} contentFit="cover" />
              ) : (
                <View style={[styles.thumb, styles.thumbEmpty, { backgroundColor: colors.inset }]}>
                  <Text>📦</Text>
                </View>
              )}
            </Pressable>
            <View style={styles.flex}>
              <Text numberOfLines={2} style={[typography.bodyStrong, { color: colors.textStrong }]}>{item.product_name}</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>
                {formatBaht(item.unit_price)} × {item.quantity}
              </Text>
              {item.can_review && (
                <Button3D title="รีวิวสินค้า" icon="⭐" size="sm" variant="secondary" onPress={() => openReview(item)} style={styles.reviewButton} />
              )}
            </View>
            <PriceText amount={item.total} size="sm" tone="strong" />
          </View>
        ))}
      </Card3D>

      {/* ---------- ที่อยู่ ---------- */}
      {order.shipping && (
        <>
          <SectionHeader title="ที่อยู่จัดส่ง" icon="📍" style={styles.section} />
          <Card3D padding={spacing.lg} style={styles.block}>
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
            {!!order.note && (
              <Text style={[typography.caption, styles.gapTopSm, { color: colors.textMuted }]}>หมายเหตุ: {order.note}</Text>
            )}
          </Card3D>
        </>
      )}

      {/* ---------- ยอดเงิน ---------- */}
      <Card3D variant="inset" padding={spacing.lg} style={styles.block}>
        <View style={styles.rowBetween}>
          <Text style={[typography.body, { color: colors.text }]}>ค่าสินค้า</Text>
          <PriceText amount={order.subtotal} size="sm" tone="strong" />
        </View>
        <View style={[styles.rowBetween, styles.gapTopSm]}>
          <Text style={[typography.body, { color: colors.text }]}>ค่าจัดส่ง</Text>
          {order.shipping_fee > 0 ? (
            <PriceText amount={order.shipping_fee} size="sm" tone="strong" />
          ) : (
            <Text style={[typography.bodyStrong, { color: colors.success }]}>ส่งฟรี</Text>
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
        <Button3D title="ได้รับสินค้าแล้ว" icon="✅" variant="success" size="lg" fullWidth onPress={confirmReceived} style={styles.gapTop} />
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
        icon="🙋"
        variant="ghost"
        size="sm"
        onPress={() => router.push('/support' as never)}
        style={[styles.center, styles.gapTop]}
      />

      {/* ---------- ยกเลิก ---------- */}
      <FormSheet
        visible={cancelOpen}
        icon="🛑"
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
        icon="⭐"
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
              style={styles.star}
            >
              <Text style={[styles.starText, { opacity: n <= rating ? 1 : 0.25 }]}>⭐</Text>
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
    marginBottom: spacing.md,
    paddingHorizontal: 0,
  },
  block: {
    marginBottom: spacing.md,
  },
  section: {
    marginTop: spacing.sm,
  },
  rowBetween: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  metaRow: {
    borderTopWidth: 1,
    marginTop: spacing.md,
    paddingTop: spacing.sm,
    gap: spacing.xxs,
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
    marginTop: spacing.md,
  },
  riderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.md,
  },
  riderAvatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },
  riderAvatarIcon: {
    fontSize: 24,
  },
  trackBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.md,
    padding: spacing.md,
    marginTop: spacing.md,
  },
  historyRow: {
    paddingVertical: spacing.sm,
    gap: spacing.xxs,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.sm,
  },
  thumb: {
    width: 60,
    height: 60,
    borderRadius: radii.sm,
  },
  thumbEmpty: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  reviewButton: {
    alignSelf: 'flex-start',
    marginTop: spacing.xs,
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
    marginTop: spacing.md,
    justifyContent: 'center',
    flexGrow: 1,
  },
  star: {
    padding: spacing.xs,
  },
  starText: {
    fontSize: 34,
  },
  chatList: {
    paddingHorizontal: spacing.screen,
    paddingVertical: spacing.md,
  },
  flipped: {
    transform: [{ scaleY: -1 }],
  },
  chatEmpty: {
    alignItems: 'center',
    paddingVertical: spacing.xxxl,
  },
  chatTabs: {
    paddingHorizontal: spacing.screen,
  },
  bubbleRow: {
    flexDirection: 'row',
    marginVertical: spacing.xs,
  },
  bubbleLeft: {
    justifyContent: 'flex-start',
  },
  bubbleRight: {
    justifyContent: 'flex-end',
  },
  bubble: {
    maxWidth: '80%',
    borderRadius: radii.lg,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    gap: spacing.xxs,
  },
  bubbleTime: {
    alignSelf: 'flex-end',
  },
  chatImage: {
    width: 180,
    height: 180,
    borderRadius: radii.md,
  },
  chatInputBar: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: spacing.sm,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
  },
  chatInput: {
    flex: 1,
    maxHeight: 120,
    minHeight: 44,
    borderRadius: radii.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  chatClosed: {
    textAlign: 'center',
    paddingVertical: spacing.md,
  },
});
