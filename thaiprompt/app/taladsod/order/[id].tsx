/**
 * รายละเอียดออเดอร์ตลาดสด (ผู้ซื้อ) — GET /fresh-market/orders/{id}
 *
 * - ฉลองสั่งสำเร็จ (?placed=1) · ไทม์ไลน์สถานะ · รีเฟรชเงียบทุก 15 วินาทีระหว่างออเดอร์ยังไม่จบและเปิดหน้านี้อยู่
 * - ไรเดอร์ส่ง: การ์ดไรเดอร์ + แผนที่สด (GET /orders/fresh-market/{id}/rider-location ทุก 15 วินาที)
 *   + สวิตช์ "แชร์ตำแหน่งของฉันให้ไรเดอร์" (ConsentSheet ก่อนเสมอ)
 * - นัดรับ: ตำแหน่งร้านตอนนี้ + นำทางด้วย Google Maps
 * - ยกเลิก (ตอนร้านยังไม่รับ) · ยืนยันรับของ + ให้คะแนนร้าน/ไรเดอร์ · ให้คะแนนภายหลัง
 * ปุ่มแสดงตาม allowed_actions / can_review จาก server เท่านั้น
 * - เจ้าของร้านเปิดหน้านี้ (push delivery_update ส่งหาทั้งสองฝั่งโดยไม่มี role) → viewer_role = 'seller'
 *   → พาไปหน้าออเดอร์ของร้านแทน (หน้านี้เป็นมุมมองผู้ซื้อ ปุ่มยกเลิก/ข้อความคืนเงินใช้กับร้านไม่ได้)
 * - ฉลองสั่งสำเร็จอยู่ที่เดียวนอกทุก branch → ไม่ถูกถอด/สร้างใหม่ตอนโหลดเสร็จ (ไม่กระพริบ ไม่สั่นซ้ำ)
 *
 * หน้าตา (ธีมรอยัล): การ์ดสถานะน้ำเงิน-ทอง + แถบความคืบหน้าพร้อมสกู๊ตเตอร์ 3D → ติดตามไรเดอร์ (การ์ดไรเดอร์ + แผนที่)
 *   → ไทม์ไลน์เหรียญน้ำเงิน/ทอง → รายการ + ยอดรวม → ปุ่มท้ายหน้า
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { router, useLocalSearchParams } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  Button3D,
  Card3D,
  Chip,
  EmptyState,
  Icon,
  LiveMap,
  PriceText,
  Screen,
  SectionHeader,
  resultHaptic,
} from '@/components/ui';
import { Field, FormSheet, callPhone } from '@/components/shop';
import {
  OrderPlacedOverlay,
  RiderTracker,
  StarRating,
  buildFmTimeline,
  useFocusedInterval,
  useMountedRef,
} from '@/components/taladsod';
import { IconTile, Notice, useInk } from '@/components/taladsod/BuyerParts';
import { OrderStatusHero } from '@/components/taladsod/OrderStatusHero';
import { OrderTimeline } from '@/components/taladsod/OrderTimeline';
import {
  FM_ORDER_TERMINAL_STATUSES,
  cancelFmOrder,
  confirmFmOrder,
  fmImageUri,
  getFmOrder,
  isUsableCoord,
  reviewFmOrder,
  type FmOrder,
} from '@/services/api/taladsodApi';
import { useTheme, radii, spacing, typography } from '@/theme';

const ORDER_POLL_MS = 15000;
const CANCEL_REASONS = ['เปลี่ยนใจไม่สั่งแล้ว', 'สั่งผิด อยากสั่งใหม่', 'รอนานเกินไป'];
const RIDER_STAGE = ['accepted', 'preparing', 'ready', 'delivering', 'delivered'];
const FALLBACK_FOOD = require('@/assets/images/taladsod/krapao-hero.webp');

type SheetKind = 'cancel' | 'confirm' | 'review' | null;

export default function TaladsodOrderScreen() {
  const { id, placed } = useLocalSearchParams<{ id: string; placed?: string }>();
  const orderId = /^\d+$/.test(String(id || '')) ? Number(id) : 0;
  const { colors } = useTheme();
  const ink = useInk();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const mountedRef = useMountedRef();

  const [order, setOrder] = useState<FmOrder | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<{ message: string; notFound: boolean } | null>(null);
  const [celebrate, setCelebrate] = useState(placed === '1');
  /** เจ้าของร้านเปิดหน้านี้ → กำลังพาไปหน้าออเดอร์ร้าน */
  const [redirecting, setRedirecting] = useState(false);

  const [sheet, setSheet] = useState<SheetKind>(null);
  const [sheetBusy, setSheetBusy] = useState(false);
  const [cancelReason, setCancelReason] = useState<string | null>(null);
  const [cancelOther, setCancelOther] = useState('');
  const [rating, setRating] = useState(5);
  const [riderRating, setRiderRating] = useState(5);
  const [review, setReview] = useState('');

  const loadingRef = useRef(false);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'silent') => {
      if (!isAuthenticated || !orderId) {
        setLoading(false);
        if (!orderId) setError({ message: 'ไม่พบออเดอร์นี้', notFound: true });
        return;
      }
      if (loadingRef.current && mode === 'silent') return;
      loadingRef.current = true;
      if (mode === 'initial') setLoading(true);
      if (mode === 'refresh') setRefreshing(true);
      const res = await getFmOrder(orderId);
      loadingRef.current = false;
      if (!mountedRef.current) return;
      if (res.success && res.data.viewer_role === 'seller') {
        // ร้านเปิดออเดอร์ของร้านตัวเอง → หน้าออเดอร์ของร้าน (ไฮไลต์ออเดอร์นี้)
        setRedirecting(true);
        router.replace(`/merchant/taladsod/orders?focus=${res.data.id}` as never);
        return;
      }
      if (res.success) {
        setOrder(res.data);
        setError(null);
      } else if (mode !== 'silent') {
        setError({ message: res.message, notFound: res.status === 404 });
      }
      setLoading(false);
      setRefreshing(false);
    },
    [isAuthenticated, orderId, mountedRef]
  );

  useEffect(() => {
    load('initial');
  }, [load]);

  const terminal = !!order && FM_ORDER_TERMINAL_STATUSES.includes(order.order_status);
  useFocusedInterval(() => load('silent'), ORDER_POLL_MS, !!order && !terminal && sheet === null && !redirecting);

  const timeline = useMemo(() => (order ? buildFmTimeline(order) : []), [order]);

  // ---------- การกระทำ ----------
  const openSheet = (kind: SheetKind) => {
    setCancelReason(null);
    setCancelOther('');
    setRating(5);
    setRiderRating(5);
    setReview('');
    setSheet(kind);
  };

  const closeSheet = () => {
    if (!sheetBusy) setSheet(null);
  };

  const submitCancel = async () => {
    if (!order || sheetBusy) return;
    const reason = cancelReason === 'other' ? cancelOther.trim() : cancelReason;
    if (!reason) {
      resultHaptic('warning');
      Alert.alert('บอกเหตุผลสั้นๆ ก่อนนะ', 'ร้านจะได้รู้ว่าทำไมยกเลิก');
      return;
    }
    setSheetBusy(true);
    const res = await cancelFmOrder(order.id, reason);
    if (!mountedRef.current) return;
    setSheetBusy(false);
    if (res.success) {
      resultHaptic('success');
      setOrder(res.data);
      setSheet(null);
      Alert.alert(
        'ยกเลิกออเดอร์แล้ว',
        res.data.payment_method === 'wallet' && order.payment_status === 'paid'
          ? 'เงินที่จ่ายไปคืนเข้ากระเป๋าเงินเรียบร้อย'
          : res.message || 'ยกเลิกเรียบร้อย'
      );
    } else {
      resultHaptic('error');
      setSheet(null);
      Alert.alert('ยกเลิกไม่สำเร็จ', res.message);
      load('silent');
    }
  };

  const submitConfirm = async () => {
    if (!order || sheetBusy) return;
    setSheetBusy(true);
    const res = await confirmFmOrder(order.id, {
      rating,
      review,
      rider_rating: order.delivery_type === 'rider' && order.rider_job ? riderRating : undefined,
    });
    if (!mountedRef.current) return;
    setSheetBusy(false);
    if (res.success) {
      resultHaptic('success');
      setOrder(res.data);
      setSheet(null);
      Alert.alert('ขอบคุณที่อุดหนุนร้านในชุมชน', 'ยืนยันรับของเรียบร้อยแล้ว');
    } else {
      resultHaptic('error');
      setSheet(null);
      Alert.alert('ยืนยันไม่สำเร็จ', res.message);
      load('silent');
    }
  };

  const submitReview = async () => {
    if (!order || sheetBusy) return;
    setSheetBusy(true);
    const res = await reviewFmOrder(order.id, {
      rating,
      review,
      rider_rating: order.delivery_type === 'rider' && order.rider_job ? riderRating : undefined,
    });
    if (!mountedRef.current) return;
    setSheetBusy(false);
    if (res.success || res.code === 'ALREADY_RATED') {
      resultHaptic('success');
      if (res.success) setOrder(res.data);
      setSheet(null);
      Alert.alert(res.success ? 'ขอบคุณสำหรับคะแนน' : 'ให้คะแนนไปแล้ว', res.success ? 'ช่วยให้ร้านในชุมชนเก่งขึ้นทุกวัน' : res.message);
      if (!res.success) load('silent');
    } else {
      resultHaptic('error');
      Alert.alert('ส่งคะแนนไม่สำเร็จ', res.message);
    }
  };

  // ---------- render ----------
  const renderBody = (): React.ReactElement => {
    if (!isAuthenticated) {
      return (
        <Screen title="ออเดอร์ตลาดสด" scroll={false}>
          <EmptyState icon="lock-key" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
        </Screen>
      );
    }

    if ((loading && !order) || redirecting) {
      return (
        <Screen title="ออเดอร์ตลาดสด" scroll={false}>
          <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
        </Screen>
      );
    }

    if (!order) {
      return (
        <Screen title="ออเดอร์ตลาดสด" scroll={false}>
          <EmptyState
            variant={error?.notFound ? 'empty' : 'error'}
            icon={error?.notFound ? 'magnifying-glass' : undefined}
            title={error?.notFound ? 'ไม่พบออเดอร์นี้' : undefined}
            message={error?.notFound ? 'ออเดอร์อาจไม่ใช่ของบัญชีนี้' : error?.message}
            actionLabel={error?.notFound ? 'ดูออเดอร์ทั้งหมด' : 'ลองใหม่'}
            onAction={error?.notFound ? () => router.replace('/taladsod/orders' as never) : () => load('initial')}
          />
        </Screen>
      );
    }

    const isRider = order.delivery_type === 'rider';
    const canCancel = order.allowed_actions.includes('cancel');
    const canConfirm = order.allowed_actions.includes('confirm');
    const showTracker = isRider && (RIDER_STAGE.includes(order.order_status) || !!order.rider_job);
    const trackerEnabled = isRider && !terminal;
    const seller = order.seller;
    const shopPoint = seller && isUsableCoord(seller.latitude, seller.longitude) ? { latitude: Number(seller.latitude), longitude: Number(seller.longitude) } : null;
    const dropoff = isUsableCoord(order.buyer_latitude, order.buyer_longitude)
      ? { latitude: Number(order.buyer_latitude), longitude: Number(order.buyer_longitude) }
      : null;
    const hasRiderRating = isRider && !!order.rider_job;
    const pickupReady = order.order_status === 'ready';
    const ratedStars = order.buyer_rating !== null ? Math.max(1, Math.min(5, order.buyer_rating)) : 0;

    const ratingSheet = sheet === 'confirm' || sheet === 'review';

    return (
      <Screen
        title="ออเดอร์ตลาดสด"
        subtitle={order.order_number}
        refreshing={refreshing}
        onRefresh={() => load('refresh')}
      >
        {/* ---------- การ์ดสถานะหลัก ---------- */}
        <OrderStatusHero order={order}>
          {order.order_status === 'pending' && (
            <View style={styles.heroNote}>
              <Icon name="hourglass" size={15} color={colors.goldLight} />
              <Text style={[typography.bodySm, styles.flex, { color: colors.onHeader }]}>รอร้านกดรับออเดอร์ ปกติไม่เกินไม่กี่นาที</Text>
            </View>
          )}
          {!!seller?.phone && !terminal && (
            <Button3D title="โทรหาร้าน" icon="phone" size="sm" variant="secondary" onPress={() => callPhone(seller.phone)} />
          )}
        </OrderStatusHero>

        {/* ---------- ไรเดอร์ ---------- */}
        {showTracker && (
          <>
            <SectionHeader title="ติดตามไรเดอร์" icon="moped" style={styles.section} />
            <Card3D padding={spacing.lg}>
              <RiderTracker
                source="fresh-market"
                orderId={order.id}
                enabled={trackerEnabled}
                dropoffFallback={dropoff}
                onJobStatusChange={() => load('silent')}
              />
              {terminal && (
                <View style={[styles.inlineRow, styles.gapTopSm]}>
                  <Icon name="info" size={15} color={colors.textMuted} />
                  <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>งานส่งจบแล้ว ไม่แสดงตำแหน่งไรเดอร์อีก</Text>
                </View>
              )}
            </Card3D>
          </>
        )}

        {/* ---------- นัดรับที่ร้าน ---------- */}
        {!isRider && !terminal && (
          <>
            <SectionHeader title="ไปรับที่ร้าน" icon="storefront" style={styles.section} />
            <Card3D padding={spacing.lg}>
              <View style={styles.tileRow}>
                <IconTile icon={pickupReady ? 'check-circle' : 'cooking-pot'} tone={pickupReady ? 'success' : 'gold'} weight="fill" />
                <Text style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]}>
                  {pickupReady ? 'ของพร้อมแล้ว ไปรับได้เลย' : 'ร้านกำลังเตรียมของ รอแจ้งพร้อมก่อนค่อยออกไปรับนะ'}
                </Text>
              </View>
              {!!seller?.address && (
                <View style={[styles.inlineRow, styles.gapTop]}>
                  <Icon name="map-pin" size={16} color={ink} weight="fill" />
                  <Text style={[typography.bodySm, styles.flex, { color: colors.textMuted }]}>{seller.address}</Text>
                </View>
              )}
              {seller?.is_mobile && (
                <Notice tone="warning" icon="navigation-arrow" style={styles.gapTop}>
                  ร้านเป็นรถเข็น ตำแหน่งอาจเปลี่ยน ดูหมุดล่าสุดก่อนออกไป
                </Notice>
              )}
              {shopPoint ? (
                <LiveMap
                  markers={[{ id: 'shop', kind: 'shop', latitude: shopPoint.latitude, longitude: shopPoint.longitude, label: seller?.shop_name }]}
                  height={190}
                  openTargetId="shop"
                  style={styles.gapTop}
                />
              ) : (
                <Text style={[typography.caption, styles.gapTopSm, { color: colors.textFaint }]}>ร้านยังไม่ได้ปักหมุดตำแหน่ง ติดต่อร้านได้เลย</Text>
              )}
            </Card3D>
          </>
        )}

        {/* ---------- ไทม์ไลน์ ---------- */}
        <SectionHeader title="สถานะออเดอร์" icon="clock" style={styles.section} />
        <Card3D padding={spacing.lg}>
          <OrderTimeline steps={timeline} />
          {!!order.cancel_reason && order.order_status === 'cancelled' && (
            <Text style={[typography.caption, styles.gapTopSm, { color: colors.textMuted }]}>เหตุผล: {order.cancel_reason}</Text>
          )}
        </Card3D>

        {/* ---------- รายการ ---------- */}
        <SectionHeader title="รายการที่สั่ง" icon="bowl-food" style={styles.section} />
        <Card3D padding={spacing.lg}>
          {order.items.map((item, i) => {
            const uri = fmImageUri(item.image_url);
            return (
              <View key={`${item.id}-${i}`} style={[styles.itemRow, i > 0 && { borderTopColor: colors.divider, borderTopWidth: 1 }]}>
                <Image source={uri ? { uri } : FALLBACK_FOOD} style={[styles.thumb, { backgroundColor: colors.inset }]} contentFit="cover" transition={120} />
                <View style={styles.flex}>
                  <Text numberOfLines={2} style={[typography.bodyStrong, { color: colors.textStrong }]}>
                    {item.title}
                  </Text>
                  {!!item.options_label && (
                    <Text style={[typography.caption, { color: colors.textMuted }]}>{item.options_label}</Text>
                  )}
                  {!!item.note && (
                    <View style={styles.inlineRow}>
                      <Icon name="note-pencil" size={13} color={colors.textMuted} />
                      <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>{item.note}</Text>
                    </View>
                  )}
                  <Text style={[typography.micro, { color: colors.textFaint }]}>
                    {item.quantity} × <PriceText amount={item.unit_price} size="xs" tone="muted" bold={false} />
                  </Text>
                </View>
                <PriceText amount={item.line_total} size="sm" tone="strong" />
              </View>
            );
          })}
          <View style={[styles.totals, { backgroundColor: colors.inset, borderColor: colors.border }]}>
            <View style={styles.sumRow}>
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>ค่าอาหาร</Text>
              <PriceText amount={order.subtotal} size="sm" tone="strong" />
            </View>
            {isRider && (
              <View style={styles.sumRow}>
                <Text style={[typography.bodySm, { color: colors.textMuted }]}>
                  ค่าส่ง{order.delivery_distance_km ? ` (${order.delivery_distance_km.toFixed(1)} กม.)` : ''}
                </Text>
                <PriceText amount={order.delivery_fee} size="sm" tone="strong" />
              </View>
            )}
            <View style={[styles.sumRow, styles.grandRow, { borderTopColor: colors.divider }]}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>รวม</Text>
              <PriceText amount={order.grand_total} size="lg" tone="gold" />
            </View>
          </View>
        </Card3D>

        {isRider && (!!order.delivery_address || !!order.delivery_notes) && (
          <Card3D padding={spacing.lg} style={styles.block}>
            {!!order.delivery_address && (
              <View style={styles.tileRow}>
                <IconTile icon="house" size={40} />
                <View style={styles.flex}>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>จุดส่ง</Text>
                  <Text style={[typography.bodySm, { color: colors.text }]}>{order.delivery_address}</Text>
                </View>
              </View>
            )}
            {!!order.delivery_notes && (
              <View style={[styles.tileRow, !!order.delivery_address && styles.gapTop]}>
                <IconTile icon="note-pencil" tone="gold" size={40} />
                <View style={styles.flex}>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>ฝากถึงร้าน/ไรเดอร์</Text>
                  <Text style={[typography.bodySm, { color: colors.text }]}>{order.delivery_notes}</Text>
                </View>
              </View>
            )}
          </Card3D>
        )}

        {/* ---------- ปุ่ม ---------- */}
        <View style={styles.actions}>
          {canConfirm && (
            <Button3D title="ได้รับของแล้ว" icon="check-circle" variant="primary" size="lg" fullWidth onPress={() => openSheet('confirm')} />
          )}
          {order.can_review && !canConfirm && (
            <Button3D title="ให้คะแนนร้าน" icon="star" size="lg" fullWidth onPress={() => openSheet('review')} />
          )}
          {order.buyer_rating !== null && (
            <View style={styles.ratedRow} accessible accessibilityLabel={`คุณให้ ${ratedStars} ดาว แล้ว ขอบคุณนะ`}>
              <Text style={[typography.caption, { color: colors.textMuted }]}>คุณให้</Text>
              <View style={styles.ratedStars}>
                {Array.from({ length: ratedStars }).map((_, n) => (
                  <Icon key={n} name="star" size={14} color={colors.gold} weight="fill" />
                ))}
              </View>
              <Text style={[typography.caption, { color: colors.textMuted }]}>แล้ว ขอบคุณนะ</Text>
            </View>
          )}
          {canCancel && (
            <Button3D title="ยกเลิกออเดอร์" variant="ghost" size="md" fullWidth onPress={() => openSheet('cancel')} />
          )}
          {terminal && (
            <Button3D
              title="สั่งจากร้านนี้อีก"
              icon="storefront"
              variant="secondary"
              size="md"
              fullWidth
              onPress={() => (seller ? router.push(`/taladsod/shop/${seller.id}` as never) : router.push('/taladsod' as never))}
            />
          )}
        </View>

        {/* ---------- ยกเลิก ---------- */}
        <FormSheet
          visible={sheet === 'cancel'}
          title="ยกเลิกออเดอร์นี้?"
          description={
            order.payment_method === 'wallet'
              ? 'ร้านยังไม่รับออเดอร์ ยกเลิกแล้วเงินคืนเข้ากระเป๋าเงินทันที'
              : 'ร้านยังไม่รับออเดอร์ ยกเลิกได้เลยไม่มีค่าใช้จ่าย'
          }
          submitLabel="ยืนยันยกเลิก"
          submitVariant="danger"
          submitDisabled={!cancelReason || (cancelReason === 'other' && cancelOther.trim().length < 2)}
          onSubmit={submitCancel}
          cancelLabel="ไม่ยกเลิก"
          busy={sheetBusy}
          onClose={closeSheet}
        >
          <View style={styles.reasonChips}>
            {CANCEL_REASONS.map((r) => (
              <Chip key={r} label={r} size="sm" selected={cancelReason === r} onPress={() => setCancelReason(r)} />
            ))}
            <Chip label="อื่นๆ" size="sm" selected={cancelReason === 'other'} onPress={() => setCancelReason('other')} />
          </View>
          {cancelReason === 'other' && (
            <Field
              label="เหตุผล"
              placeholder="บอกร้านสั้นๆ"
              value={cancelOther}
              onChangeText={(t) => setCancelOther(t.slice(0, 500))}
              maxLength={500}
              containerStyle={styles.gapTop}
            />
          )}
        </FormSheet>

        {/* ---------- ยืนยันรับของ / ให้คะแนน ---------- */}
        <FormSheet
          visible={ratingSheet}
          title={sheet === 'confirm' ? 'ได้รับของครบแล้วใช่ไหม?' : 'ให้คะแนนออเดอร์นี้'}
          description={
            sheet === 'confirm'
              ? 'ยืนยันแล้วออเดอร์จะปิด และร้านได้รับเงิน ถ้ายังไม่ได้ของหรือของไม่ครบ อย่าเพิ่งกดนะ'
              : 'คะแนนของคุณช่วยให้ร้านในชุมชนเก่งขึ้น'
          }
          submitLabel={sheet === 'confirm' ? 'ได้รับครบแล้ว' : 'ส่งคะแนน'}
          submitVariant={sheet === 'confirm' ? 'success' : 'primary'}
          onSubmit={sheet === 'confirm' ? submitConfirm : submitReview}
          cancelLabel={sheet === 'confirm' ? 'ยังไม่ได้รับ' : 'ไว้ทีหลัง'}
          busy={sheetBusy}
          onClose={closeSheet}
        >
          <View style={[styles.ratingBox, { backgroundColor: colors.inset, borderColor: colors.border }]}>
            <StarRating value={rating} onChange={setRating} label={`ร้าน ${seller?.shop_name || ''}`.trim()} disabled={sheetBusy} />
            {hasRiderRating && (
              <View style={[styles.riderRating, { borderTopColor: colors.divider }]}>
                <StarRating value={riderRating} onChange={setRiderRating} label="ไรเดอร์" size={28} disabled={sheetBusy} />
              </View>
            )}
          </View>
          <Field
            label="รีวิวสั้นๆ (ไม่บังคับ)"
            placeholder="เช่น กะเพราหอมมาก ส่งไว"
            value={review}
            onChangeText={(t) => setReview(t.slice(0, 1000))}
            maxLength={1000}
            multiline
            containerStyle={styles.gapTop}
          />
        </FormSheet>
      </Screen>
    );
  };

  // ฉลองสั่งสำเร็จ: วางที่เดียว (ลูกตัวแรกของ Fragment คงที่) ไม่ว่าจะอยู่ branch ไหน
  // → React ไม่ถอดแล้วสร้างใหม่ตอน loading → โหลดเสร็จ (Modal ไม่ fade ซ้ำ · timer/haptic ไม่เริ่มใหม่)
  return (
    <>
      <OrderPlacedOverlay visible={celebrate && !redirecting} onClose={() => setCelebrate(false)} />
      {renderBody()}
    </>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  block: {
    marginTop: spacing.md,
  },
  section: {
    marginTop: spacing.xxl,
  },
  gapTop: {
    marginTop: spacing.md,
  },
  gapTopSm: {
    marginTop: spacing.sm,
  },
  heroNote: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  inlineRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 6,
  },
  tileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.md,
  },
  thumb: {
    width: 56,
    height: 56,
    borderRadius: radii.md,
  },
  totals: {
    marginTop: spacing.sm,
    borderRadius: radii.md,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  sumRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 4,
  },
  grandRow: {
    marginTop: spacing.xs,
    paddingTop: spacing.sm,
    borderTopWidth: 1,
  },
  actions: {
    marginTop: spacing.xxl,
    gap: spacing.sm,
  },
  ratedRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
  },
  ratedStars: {
    flexDirection: 'row',
    gap: 2,
  },
  reasonChips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  ratingBox: {
    marginTop: spacing.md,
    alignItems: 'center',
    borderRadius: radii.lg,
    borderWidth: 1,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.sm,
  },
  riderRating: {
    alignSelf: 'stretch',
    alignItems: 'center',
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
});
