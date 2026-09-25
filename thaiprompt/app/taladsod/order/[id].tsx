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
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { router, useLocalSearchParams } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  Button3D,
  Card3D,
  Chip,
  EmptyState,
  LiveMap,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  resultHaptic,
} from '@/components/ui';
import { Field, FormSheet, StatusTimeline, callPhone, formatThaiDateTime } from '@/components/shop';
import {
  FM_ORDER_TONE,
  OrderPlacedOverlay,
  RiderTracker,
  StarRating,
  buildFmTimeline,
  fmOrderLabel,
  useFocusedInterval,
  useMountedRef,
} from '@/components/taladsod';
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
      Alert.alert('ขอบคุณที่อุดหนุนร้านในชุมชน 💛', 'ยืนยันรับของเรียบร้อยแล้ว');
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
      Alert.alert(res.success ? 'ขอบคุณสำหรับคะแนน 💛' : 'ให้คะแนนไปแล้ว', res.success ? 'ช่วยให้ร้านในชุมชนเก่งขึ้นทุกวัน' : res.message);
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
          <EmptyState icon="🔐" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
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
            icon={error?.notFound ? '🔍' : undefined}
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

    const ratingSheet = sheet === 'confirm' || sheet === 'review';

    return (
      <Screen
        title="ออเดอร์ตลาดสด"
        subtitle={order.order_number}
        refreshing={refreshing}
        onRefresh={() => load('refresh')}
      >
        {/* ---------- หัวออเดอร์ ---------- */}
        <Card3D gradientBorder padding={spacing.lg} style={styles.block}>
          <View style={styles.rowBetween}>
            <View style={styles.flex}>
              <Text style={[typography.caption, { color: colors.textMuted }]}>สถานะ</Text>
              <Text style={[typography.h2, { color: colors.textStrong }]}>{fmOrderLabel(order)}</Text>
            </View>
            <Pill label={fmOrderLabel(order)} tone={FM_ORDER_TONE[order.order_status] || 'neutral'} />
          </View>
          <View style={[styles.metaRow, { borderTopColor: colors.divider }]}>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              🏪 {seller?.shop_name || 'ร้านตลาดสด'} · {isRider ? '🛵 ไรเดอร์ส่ง' : '🛍️ นัดรับที่ร้าน'} ·{' '}
              {order.payment_method_label || (order.payment_method === 'cod' ? 'เงินสด' : 'กระเป๋าเงิน')}
            </Text>
            <Text style={[typography.micro, { color: colors.textFaint }]}>
              สั่งเมื่อ {formatThaiDateTime(order.created_at)} · {order.payment_status_label}
            </Text>
          </View>
          {order.order_status === 'pending' && (
            <Text style={[typography.bodySm, styles.gapTopSm, { color: colors.info }]}>⏳ รอร้านกดรับออเดอร์ ปกติไม่เกินไม่กี่นาที</Text>
          )}
          {!!seller?.phone && !terminal && (
            <Button3D title="โทรหาร้าน" icon="📞" size="sm" variant="secondary" onPress={() => callPhone(seller.phone)} style={styles.gapTop} />
          )}
        </Card3D>

        {/* ---------- ไรเดอร์ ---------- */}
        {showTracker && (
          <>
            <SectionHeader title="ติดตามไรเดอร์" icon="🛵" style={styles.section} />
            <Card3D padding={spacing.lg}>
              <RiderTracker
                source="fresh-market"
                orderId={order.id}
                enabled={trackerEnabled}
                dropoffFallback={dropoff}
                onJobStatusChange={() => load('silent')}
              />
              {terminal && (
                <Text style={[typography.caption, { color: colors.textMuted }]}>งานส่งจบแล้ว ไม่แสดงตำแหน่งไรเดอร์อีก</Text>
              )}
            </Card3D>
          </>
        )}

        {/* ---------- นัดรับที่ร้าน ---------- */}
        {!isRider && !terminal && (
          <>
            <SectionHeader title="ไปรับที่ร้าน" icon="🛍️" style={styles.section} />
            <Card3D padding={spacing.lg}>
              <Text style={[typography.bodySm, { color: colors.text }]}>
                {order.order_status === 'ready' ? '✅ ของพร้อมแล้ว ไปรับได้เลย' : 'ร้านกำลังเตรียมของ รอแจ้งพร้อมก่อนค่อยออกไปรับนะ'}
              </Text>
              {!!seller?.address && (
                <Text style={[typography.caption, styles.gapTopSm, { color: colors.textMuted }]}>📍 {seller.address}</Text>
              )}
              {seller?.is_mobile && (
                <Text style={[typography.caption, styles.gapTopSm, { color: colors.warning }]}>🛺 ร้านเป็นรถเข็น ตำแหน่งอาจเปลี่ยน ดูหมุดล่าสุดก่อนออกไป</Text>
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
        <SectionHeader title="สถานะออเดอร์" icon="🕒" style={styles.section} />
        <Card3D padding={spacing.lg}>
          <StatusTimeline steps={timeline} />
          {!!order.cancel_reason && order.order_status === 'cancelled' && (
            <Text style={[typography.caption, styles.gapTopSm, { color: colors.textMuted }]}>เหตุผล: {order.cancel_reason}</Text>
          )}
        </Card3D>

        {/* ---------- รายการ ---------- */}
        <SectionHeader title="รายการที่สั่ง" icon="🍛" style={styles.section} />
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
                  {!!item.note && <Text style={[typography.caption, { color: colors.textMuted }]}>📝 {item.note}</Text>}
                  <Text style={[typography.micro, { color: colors.textFaint }]}>
                    {item.quantity} × <PriceText amount={item.unit_price} size="xs" tone="muted" bold={false} />
                  </Text>
                </View>
                <PriceText amount={item.line_total} size="sm" tone="strong" />
              </View>
            );
          })}
          <View style={[styles.sumRow, styles.sumFirst, { borderTopColor: colors.divider }]}>
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
          <View style={styles.sumRow}>
            <Text style={[typography.h3, { color: colors.textStrong }]}>รวม</Text>
            <PriceText amount={order.grand_total} size="lg" tone="gold" />
          </View>
        </Card3D>

        {isRider && (!!order.delivery_address || !!order.delivery_notes) && (
          <Card3D variant="flat" padding={spacing.md} style={styles.block}>
            {!!order.delivery_address && (
              <Text style={[typography.bodySm, { color: colors.text }]}>🏠 {order.delivery_address}</Text>
            )}
            {!!order.delivery_notes && (
              <Text style={[typography.caption, styles.gapTopSm, { color: colors.textMuted }]}>📝 {order.delivery_notes}</Text>
            )}
          </Card3D>
        )}

        {/* ---------- ปุ่ม ---------- */}
        <View style={styles.actions}>
          {canConfirm && (
            <Button3D title="ได้รับของแล้ว" icon="✅" variant="success" size="lg" fullWidth onPress={() => openSheet('confirm')} />
          )}
          {order.can_review && !canConfirm && (
            <Button3D title="ให้คะแนนร้าน" icon="⭐" size="lg" fullWidth onPress={() => openSheet('review')} />
          )}
          {order.buyer_rating !== null && (
            <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>
              คุณให้ {'⭐'.repeat(Math.max(1, Math.min(5, order.buyer_rating)))} แล้ว ขอบคุณนะ
            </Text>
          )}
          {canCancel && (
            <Button3D title="ยกเลิกออเดอร์" variant="ghost" size="md" fullWidth onPress={() => openSheet('cancel')} />
          )}
          {terminal && (
            <Button3D
              title="สั่งจากร้านนี้อีก"
              icon="🛒"
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
          icon="🙏"
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
          icon={sheet === 'confirm' ? '🎉' : '⭐'}
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
          <View style={styles.ratingBox}>
            <StarRating value={rating} onChange={setRating} label={`ร้าน ${seller?.shop_name || ''}`.trim()} disabled={sheetBusy} />
            {hasRiderRating && (
              <View style={styles.gapTop}>
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
  center: {
    textAlign: 'center',
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  block: {
    marginTop: spacing.sm,
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
  rowBetween: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  metaRow: {
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    gap: 2,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.md,
  },
  thumb: {
    width: 52,
    height: 52,
    borderRadius: radii.sm,
  },
  sumRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 4,
  },
  sumFirst: {
    marginTop: spacing.xs,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  actions: {
    marginTop: spacing.xl,
    gap: spacing.sm,
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
  },
});
