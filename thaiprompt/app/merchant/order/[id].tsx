/**
 * รายละเอียดออเดอร์ของร้าน — GET /seller/orders/{id} + POST /seller/orders/{id}/action (SHOP-11 / SHOP-13)
 *
 * - แสดงปุ่มเฉพาะที่อยู่ใน allowed_actions จาก server เท่านั้น:
 *     confirm (ยืนยันรับออเดอร์) · request_rider (เรียกไรเดอร์) · ship (เลขพัสดุ + ขนส่ง)
 *     deliver (ยืนยันส่งถึง) · cancel (ต้องระบุเหตุผล)
 * - 409 ACTION_NOT_ALLOWED → แจ้งแล้วโหลดใหม่ (มีคนกดไปก่อน/สถานะเปลี่ยน)
 * - รีเฟรชอัตโนมัติ (ไรเดอร์กำลังวิ่ง 15 วินาที, ปกติ 30 วินาที) + ดึงลงเพื่อรีเฟรช
 * - ค่าธรรมเนียม GP และรายรับสุทธิของร้านมาจาก server
 *
 * หน้าตา: การ์ดสถานะ (ช่องไอคอนสีตามสถานะ) → การ์ดขั้นตอนถัดไป (ปุ่มใหญ่) → ไรเดอร์ → ที่อยู่ → สินค้า → ยอด → เส้นเวลาพัสดุ
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  getSellerOrder,
  getShippingProviders,
  sellerOrderAction,
  type SellerOrderAction,
  type SellerOrderActionBody,
  type SellerOrderDetail,
  type ShippingProvider,
} from '@/services/api/merchantApi';
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
  type IconName,
} from '@/components/ui';
import {
  ACTIVE_RIDER_STATUSES,
  Field,
  FormSheet,
  ORDER_STATUS_TONE,
  callPhone,
  formatThaiDateTime,
  openHttpsLink,
  toNumber,
} from '@/components/shop';
import { IconTile } from '@/components/merchant';
import { useTheme, radii, spacing, typography } from '@/theme';

const CANCEL_REASONS = ['สินค้าหมด', 'ส่งไม่ได้ในพื้นที่นี้', 'ลูกค้าขอยกเลิก', 'ราคา/ข้อมูลสินค้าผิด'];
const RIDER_POLL_MS = 15000;
const POLL_MS = 30000;

const ACTION_SUCCESS: Record<SellerOrderAction, string> = {
  confirm: 'ยืนยันรับออเดอร์แล้ว เริ่มเตรียมสินค้าได้เลย',
  request_rider: 'เรียกไรเดอร์แล้ว ระบบกำลังหาไรเดอร์ใกล้ร้าน',
  ship: 'บันทึกการจัดส่งแล้ว ลูกค้าจะได้รับเลขพัสดุ',
  deliver: 'ยืนยันส่งถึงแล้ว',
  cancel: 'ยกเลิกออเดอร์แล้ว',
};

/** แถวข้อมูลเล็ก: ไอคอนเส้น + ข้อความ */
const MetaLine: React.FC<{ icon: IconName; color: string; children: React.ReactNode; variant?: 'bodySm' | 'caption' | 'micro' }> = ({
  icon,
  color,
  children,
  variant = 'caption',
}) => (
  <View style={styles.metaLine}>
    <Icon name={icon} size={variant === 'bodySm' ? 16 : 14} color={color} style={styles.metaIcon} />
    <Text style={[typography[variant], styles.flex, { color }]}>{children}</Text>
  </View>
);

export default function MerchantOrderDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const orderId = /^\d+$/.test(String(id || '')) ? Number(id) : 0;
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [detail, setDetail] = useState<SellerOrderDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<{ message: string; code: string } | null>(null);
  const [acting, setActing] = useState<SellerOrderAction | null>(null);

  const [shipOpen, setShipOpen] = useState(false);
  const [providers, setProviders] = useState<ShippingProvider[]>([]);
  const [providersLoading, setProvidersLoading] = useState(false);
  const [providerId, setProviderId] = useState<number | 'other' | null>(null);
  const [providerName, setProviderName] = useState('');
  const [trackingNo, setTrackingNo] = useState('');

  const [cancelOpen, setCancelOpen] = useState(false);
  const [cancelReason, setCancelReason] = useState<string | null>(null);
  const [cancelOther, setCancelOther] = useState('');

  const mountedRef = useRef(true);
  const loadingRef = useRef(false);
  const actingRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'silent') => {
      if (!isAuthenticated || !orderId) {
        setLoading(false);
        if (!orderId) setError({ message: 'ไม่พบคำสั่งซื้อนี้', code: 'ORDER_NOT_FOUND' });
        return;
      }
      if (mode === 'silent' && (loadingRef.current || actingRef.current)) return;
      loadingRef.current = true;
      if (mode === 'initial') setLoading(true);
      if (mode === 'refresh') setRefreshing(true);
      const res = await getSellerOrder(orderId);
      loadingRef.current = false;
      if (!mountedRef.current) return;
      if (res.success && res.data) {
        setDetail(res.data);
        setError(null);
      } else if (!res.success && (mode !== 'silent' || !detail)) {
        setError({ message: res.message, code: res.code });
      }
      setLoading(false);
      setRefreshing(false);
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [isAuthenticated, orderId]
  );

  useEffect(() => {
    load('initial');
  }, [load]);

  const order = detail?.order;
  const rider = detail?.rider ?? null;
  const riderActive = !!rider && ACTIVE_RIDER_STATUSES.includes(rider.status);
  const isOpen = !!order && !['completed', 'cancelled', 'refunded'].includes(order.status);

  useFocusEffect(
    useCallback(() => {
      if (!isOpen) return undefined;
      const timer = setInterval(() => load('silent'), riderActive ? RIDER_POLL_MS : POLL_MS);
      return () => clearInterval(timer);
    }, [isOpen, riderActive, load])
  );

  // ---------- ทำรายการ ----------
  const perform = async (body: SellerOrderActionBody): Promise<boolean> => {
    if (actingRef.current) return false;
    actingRef.current = true;
    setActing(body.action);
    const res = await sellerOrderAction(orderId, body);
    actingRef.current = false;
    if (!mountedRef.current) return false;
    setActing(null);

    if (res.success && res.data) {
      resultHaptic('success');
      setDetail(res.data);
      Alert.alert('เรียบร้อย', res.message || ACTION_SUCCESS[body.action]);
      return true;
    }
    if (!res.success) {
      resultHaptic('error');
      if (res.code === 'ACTION_NOT_ALLOWED') {
        Alert.alert('ทำรายการนี้ไม่ได้แล้ว', `${res.message}\nสถานะออเดอร์อาจเปลี่ยนไป กำลังโหลดใหม่ให้`);
        load('silent');
      } else {
        Alert.alert('ทำรายการไม่สำเร็จ', res.message);
      }
    }
    return false;
  };

  const confirmOrder = () => perform({ action: 'confirm' });

  const requestRider = () => {
    Alert.alert('เรียกไรเดอร์?', 'ระบบจะส่งงานให้ไรเดอร์ใกล้ร้านทันที เตรียมของให้พร้อมก่อนไรเดอร์มาถึงนะ', [
      { text: 'ยังก่อน', style: 'cancel' },
      { text: 'เรียกไรเดอร์', onPress: () => perform({ action: 'request_rider' }) },
    ]);
  };

  const markDelivered = () => {
    Alert.alert('ยืนยันว่าส่งถึงแล้ว?', 'ใช้เมื่อลูกค้าได้รับพัสดุแล้วเท่านั้น', [
      { text: 'ยังไม่ถึง', style: 'cancel' },
      { text: 'ส่งถึงแล้ว', onPress: () => perform({ action: 'deliver' }) },
    ]);
  };

  const openShip = async () => {
    setShipOpen(true);
    setTrackingNo(detail?.tracking.tracking_number || '');
    if (providers.length > 0 || providersLoading) return;
    setProvidersLoading(true);
    const res = await getShippingProviders();
    if (!mountedRef.current) return;
    setProvidersLoading(false);
    if (res.success && Array.isArray(res.data)) setProviders(res.data);
  };

  const submitShip = async () => {
    const tracking = trackingNo.trim();
    if (tracking.length < 4) {
      Alert.alert('กรอกเลขพัสดุก่อนนะ', 'เลขพัสดุต้องยาวอย่างน้อย 4 ตัวอักษร');
      return;
    }
    if (providerId === null || (providerId === 'other' && !providerName.trim())) {
      Alert.alert('เลือกขนส่งก่อนนะ', 'เลือกบริษัทขนส่ง หรือพิมพ์ชื่อขนส่งเอง');
      return;
    }
    const ok = await perform({
      action: 'ship',
      tracking_number: tracking.slice(0, 100),
      ...(providerId === 'other' ? { provider: providerName.trim().slice(0, 100) } : { shipping_provider_id: providerId }),
    });
    if (ok && mountedRef.current) setShipOpen(false);
  };

  const submitCancel = async () => {
    const reason = cancelReason === 'other' ? cancelOther.trim() : cancelReason;
    if (!reason) {
      Alert.alert('ระบุเหตุผลก่อนนะ', 'ลูกค้าจะเห็นเหตุผลนี้');
      return;
    }
    const ok = await perform({ action: 'cancel', reason: reason.slice(0, 500) });
    if (ok && mountedRef.current) setCancelOpen(false);
  };

  // ---------- render ----------
  if (!isAuthenticated) {
    return (
      <Screen title="ออเดอร์" scroll={false}>
        <EmptyState art="store" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (loading && !detail) {
    return (
      <Screen title="ออเดอร์" scroll={false}>
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </Screen>
    );
  }

  if (!detail || !order) {
    const notFound = error?.code === 'ORDER_NOT_FOUND' || error?.code === 'NOT_FOUND';
    return (
      <Screen title="ออเดอร์" scroll={false}>
        <EmptyState
          variant={notFound ? 'empty' : 'error'}
          icon={notFound ? 'magnifying-glass' : error?.code === 'STORE_SUSPENDED' ? 'prohibit' : undefined}
          title={notFound ? 'ไม่พบออเดอร์นี้' : undefined}
          message={notFound ? 'ออเดอร์นี้ไม่ใช่ของร้านคุณ หรือถูกลบไปแล้ว' : error?.message}
          actionLabel={notFound ? 'ดูออเดอร์ทั้งหมด' : 'ลองใหม่'}
          onAction={notFound ? () => router.replace('/merchant/orders' as never) : () => load('initial')}
        />
      </Screen>
    );
  }

  const allowed = new Set(detail.allowed_actions || []);
  const shipping = detail.shipping;
  const totals = detail.totals;
  const isRider = order.delivery_method === 'rider';
  const hasLatLng =
    !!shipping &&
    shipping.latitude !== null &&
    shipping.latitude !== undefined &&
    shipping.longitude !== null &&
    shipping.longitude !== undefined &&
    Number.isFinite(Number(shipping.latitude)) &&
    Number.isFinite(Number(shipping.longitude));

  const statusTone = ORDER_STATUS_TONE[order.status] || 'neutral';
  // ข้อความรอขั้นตอนถัดไป (ไม่มีปุ่มให้กด)
  const waiting: { icon: IconName; text: string } =
    order.payment_status !== 'paid' && order.payment_method !== 'cod'
      ? { icon: 'hourglass', text: 'รอลูกค้าชำระเงิน ชำระแล้วจะยืนยันออเดอร์ได้' }
      : riderActive
        ? { icon: 'moped', text: 'ไรเดอร์กำลังดำเนินการ ติดตามสถานะได้ด้านล่าง' }
        : { icon: 'hourglass', text: 'รอขั้นตอนถัดไป ระบบจะอัปเดตให้อัตโนมัติ' };

  return (
    <Screen title="ออเดอร์" subtitle={order.order_number} refreshing={refreshing} onRefresh={() => load('refresh')}>
      {/* ---------- หัว ---------- */}
      <Card3D gradientBorder padding={spacing.lg} style={styles.block}>
        <View style={styles.rowBetween}>
          <IconTile icon="receipt" tone={statusTone} />
          <View style={styles.flex}>
            <Text style={[typography.caption, { color: colors.textMuted }]}>สถานะ</Text>
            <Text style={[typography.h2, { color: colors.textStrong }]}>{order.status_label}</Text>
          </View>
          <Pill
            label={order.payment_method === 'cod' && order.payment_status !== 'paid' ? 'เก็บเงินปลายทาง' : order.payment_status_label || order.payment_status}
            tone={order.payment_status === 'paid' ? 'success' : order.payment_method === 'cod' ? 'info' : 'warning'}
          />
        </View>
        <View style={[styles.meta, { borderTopColor: colors.divider }]}>
          <MetaLine icon="user" color={colors.text} variant="bodySm">
            {detail.customer?.name || 'ลูกค้า'}
          </MetaLine>
          <MetaLine icon={isRider ? 'moped' : 'package'} color={colors.textMuted}>
            {isRider ? 'ส่งด้วยไรเดอร์' : 'ส่งพัสดุ'} · {order.payment_method_label || order.payment_method} ·{' '}
            {order.payment_status_label || order.payment_status}
          </MetaLine>
          <MetaLine icon="clock" color={colors.textFaint}>
            สั่งเมื่อ {formatThaiDateTime(order.created_at)}
          </MetaLine>
          {order.is_multi_seller && (
            <MetaLine icon="info" color={colors.info}>
              ออเดอร์นี้มีสินค้าจากหลายร้าน แสดงเฉพาะของร้านคุณ
            </MetaLine>
          )}
          {!!order.cancellation_reason && (
            <MetaLine icon="x-circle" color={colors.danger}>
              เหตุผลที่ยกเลิก: {order.cancellation_reason}
            </MetaLine>
          )}
        </View>
      </Card3D>

      {/* ---------- ปุ่มดำเนินการ (ตาม allowed_actions) ---------- */}
      {allowed.size > 0 && (
        <Card3D padding={spacing.lg} style={styles.block}>
          <View style={styles.titleRow}>
            <IconTile icon="hand-tap" tone="gold" size={36} />
            <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>ขั้นตอนถัดไป</Text>
          </View>
          <View style={styles.actions}>
            {allowed.has('confirm') && (
              <Button3D title="ยืนยันรับออเดอร์" icon="check-circle" size="lg" fullWidth loading={acting === 'confirm'} disabled={!!acting} onPress={confirmOrder} />
            )}
            {allowed.has('request_rider') && (
              <Button3D title="เรียกไรเดอร์" icon="moped" variant="success" size="lg" fullWidth loading={acting === 'request_rider'} disabled={!!acting} onPress={requestRider} />
            )}
            {allowed.has('ship') && (
              <Button3D title="แจ้งจัดส่งพัสดุ" icon="package" size="lg" fullWidth loading={acting === 'ship'} disabled={!!acting} onPress={openShip} />
            )}
            {allowed.has('deliver') && (
              <Button3D title="ยืนยันส่งถึงแล้ว" icon="seal-check" variant="success" size="md" fullWidth loading={acting === 'deliver'} disabled={!!acting} onPress={markDelivered} />
            )}
            {allowed.has('cancel') && (
              <Button3D
                title="ยกเลิกออเดอร์"
                icon={<Icon name="x-circle" size={18} color={colors.danger} />}
                variant="secondary"
                size="md"
                fullWidth
                disabled={!!acting}
                textStyle={{ color: colors.danger }}
                onPress={() => {
                  setCancelReason(null);
                  setCancelOther('');
                  setCancelOpen(true);
                }}
              />
            )}
          </View>
        </Card3D>
      )}
      {allowed.size === 0 && isOpen && (
        <Card3D variant="flat" padding={spacing.md} style={styles.block}>
          <View style={styles.titleRow}>
            <IconTile icon={waiting.icon} tone="neutral" size={36} />
            <Text style={[typography.bodySm, styles.flex, { color: colors.textMuted }]}>{waiting.text}</Text>
          </View>
        </Card3D>
      )}

      {/* ---------- ไรเดอร์ ---------- */}
      {isRider && rider && (
        <Card3D padding={spacing.lg} style={styles.block}>
          <View style={styles.rowBetween}>
            <IconTile icon="moped" tone={riderActive ? 'gold' : 'navy'} />
            <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>ไรเดอร์</Text>
            <Pill label={rider.status_label} tone={riderActive ? 'gold' : 'neutral'} />
          </View>
          {rider.status === 'pending' && (
            <View style={[styles.titleRow, styles.gapTop]}>
              <ActivityIndicator size="small" color={colors.gold} />
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>กำลังหาไรเดอร์ใกล้ร้าน…</Text>
            </View>
          )}
          {!!rider.rider && (
            <View style={[styles.riderRow, { backgroundColor: colors.inset }]}>
              <IconTile icon="user" size={40} />
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{rider.rider.name || 'ไรเดอร์'}</Text>
                {!!rider.rider.vehicle_plate && (
                  <Text style={[typography.caption, { color: colors.textMuted }]}>ทะเบียน {rider.rider.vehicle_plate}</Text>
                )}
              </View>
              {!!rider.rider.phone && (
                <Button3D title="โทร" icon="phone" size="sm" variant="secondary" onPress={() => callPhone(rider.rider?.phone)} />
              )}
            </View>
          )}
          {isTrustedWebUrl(rider.tracking_url) && (
            <Button3D
              title="ดูตำแหน่งไรเดอร์แบบสด"
              icon="map-trifold"
              size="sm"
              variant="secondary"
              onPress={() => openHttpsLink(rider.tracking_url, 'ติดตามไรเดอร์')}
              style={styles.gapTop}
            />
          )}
        </Card3D>
      )}

      {/* ---------- ที่อยู่ลูกค้า ---------- */}
      {shipping && (
        <>
          <SectionHeader title="ส่งถึง" style={styles.section} />
          <Card3D padding={spacing.lg} style={styles.block}>
            <View style={styles.addressRow}>
              <IconTile icon="map-pin" />
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{shipping.name}</Text>
                <Text style={[typography.bodySm, styles.gapTopSm, { color: colors.text }]}>{shipping.full_address}</Text>
                {!!shipping.notes && (
                  <Text style={[typography.caption, styles.gapTopSm, { color: colors.textMuted }]}>หมายเหตุที่อยู่: {shipping.notes}</Text>
                )}
              </View>
            </View>
            <View style={styles.buttonRow}>
              {!!shipping.phone && (
                <Button3D title="โทรหาลูกค้า" icon="phone" size="sm" variant="secondary" onPress={() => callPhone(shipping.phone)} style={styles.flex} />
              )}
              {hasLatLng && (
                <Button3D
                  title="เปิดแผนที่"
                  icon="map-trifold"
                  size="sm"
                  variant="secondary"
                  onPress={() =>
                    openHttpsLink(
                      `https://www.google.com/maps/search/?api=1&query=${Number(shipping.latitude)},${Number(shipping.longitude)}`,
                      'เปิดแผนที่'
                    )
                  }
                  style={styles.flex}
                />
              )}
            </View>
          </Card3D>
        </>
      )}
      {!!order.note && (
        <Card3D variant="flat" padding={spacing.md} style={styles.block}>
          <View style={styles.titleRow}>
            <IconTile icon="note-pencil" tone="gold" size={36} />
            <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>หมายเหตุจากลูกค้า: {order.note}</Text>
          </View>
        </Card3D>
      )}

      {/* ---------- สินค้า ---------- */}
      <SectionHeader title="สินค้าของร้านคุณ" style={styles.section} />
      <Card3D padding={spacing.lg} style={styles.block}>
        {detail.items.map((item, i) => (
          <View key={item.id} style={[styles.itemRow, i > 0 && { borderTopColor: colors.divider, borderTopWidth: 1 }]}>
            {item.product_image ? (
              <Image source={{ uri: item.product_image }} style={[styles.thumb, { backgroundColor: colors.inset }]} contentFit="cover" />
            ) : (
              <IconTile icon="package" size={56} />
            )}
            <View style={styles.flex}>
              <Text numberOfLines={2} style={[typography.bodyStrong, { color: colors.textStrong }]}>{item.product_name}</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>
                {formatBaht(item.unit_price)} × {toNumber(item.quantity)}
              </Text>
              <Text style={[typography.micro, { color: colors.textMuted }]}>
                GP {toNumber(item.gp_rate)}% ({formatBaht(item.gp_amount)}) · รับสุทธิ {formatBaht(item.seller_earning)}
              </Text>
            </View>
            <PriceText amount={item.total} size="sm" tone="strong" />
          </View>
        ))}
      </Card3D>

      {/* ---------- ยอด ---------- */}
      <Card3D variant="inset" padding={spacing.lg} style={styles.block}>
        <View style={styles.rowBetween}>
          <Text style={[typography.body, { color: colors.text }]}>ยอดสินค้าของร้าน</Text>
          <PriceText amount={totals.items_total} size="sm" tone="strong" />
        </View>
        {toNumber(totals.discount) > 0 && (
          <View style={[styles.rowBetween, styles.gapTopSm]}>
            <Text style={[typography.body, { color: colors.text }]}>ส่วนลด</Text>
            <PriceText amount={-toNumber(totals.discount)} size="sm" tone="success" />
          </View>
        )}
        <View style={[styles.rowBetween, styles.gapTopSm]}>
          <Text style={[typography.body, { color: colors.text }]}>ค่าธรรมเนียม GP</Text>
          <PriceText amount={-toNumber(totals.gp_amount)} size="sm" tone="default" />
        </View>
        {totals.shipping_fee !== null && totals.shipping_fee !== undefined && (
          <View style={[styles.rowBetween, styles.gapTopSm]}>
            <Text style={[typography.caption, { color: colors.textMuted }]}>ค่าส่งที่ลูกค้าจ่าย</Text>
            <PriceText amount={totals.shipping_fee} size="xs" tone="muted" />
          </View>
        )}
        <View style={[styles.rowBetween, styles.total, { borderTopColor: colors.divider }]}>
          <Text style={[typography.h3, { color: colors.textStrong }]}>รายรับสุทธิของร้าน</Text>
          <PriceText amount={totals.seller_earning} size="lg" tone="success" />
        </View>
        {totals.order_total !== null && totals.order_total !== undefined && (
          <Text style={[typography.caption, styles.gapTopSm, { color: colors.textMuted }]}>
            ยอดที่ลูกค้าจ่ายทั้งออเดอร์ {formatBaht(totals.order_total, { decimals: 2 })}
          </Text>
        )}
      </Card3D>

      {/* ---------- การจัดส่งพัสดุ ---------- */}
      {!isRider && (!!detail.tracking.tracking_number || detail.tracking_history.length > 0) && (
        <>
          <SectionHeader title="การจัดส่ง" style={styles.section} />
          <Card3D padding={spacing.lg} style={styles.block}>
            {!!detail.tracking.tracking_number && (
              <View style={[styles.trackBox, { backgroundColor: colors.inset }]}>
                <IconTile icon="truck" tone="gold" size={40} />
                <View style={styles.flex}>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>{detail.tracking.shipping_provider || 'เลขพัสดุ'}</Text>
                  <Text selectable style={[typography.h3, { color: colors.textStrong }]}>
                    {detail.tracking.tracking_number}
                  </Text>
                </View>
              </View>
            )}
            {detail.tracking_history.map((h, i) => (
              <View key={h.id} style={styles.historyRow}>
                {/* เส้นเวลา: จุดทอง = ล่าสุด */}
                <View style={styles.timeline}>
                  <View
                    style={[
                      styles.timelineDot,
                      { backgroundColor: i === 0 ? colors.gold : colors.card, borderColor: i === 0 ? colors.gold : colors.border },
                    ]}
                  />
                  {i < detail.tracking_history.length - 1 && <View style={[styles.timelineLine, { backgroundColor: colors.divider }]} />}
                </View>
                <View style={[styles.flex, styles.historyBody]}>
                  <Text style={[typography.bodyStrong, { color: i === 0 ? colors.textStrong : colors.text }]}>{h.title}</Text>
                  {!!h.description && <Text style={[typography.caption, { color: colors.text }]}>{h.description}</Text>}
                  <Text style={[typography.micro, { color: colors.textFaint }]}>
                    {[formatThaiDateTime(h.tracked_at), h.location].filter(Boolean).join(' · ')}
                  </Text>
                </View>
              </View>
            ))}
          </Card3D>
        </>
      )}

      {/* ---------- แจ้งจัดส่ง ---------- */}
      <FormSheet
        visible={shipOpen}
        icon="package"
        title="แจ้งจัดส่งพัสดุ"
        description="เลือกขนส่งและกรอกเลขพัสดุ ลูกค้าจะติดตามพัสดุได้ทันที"
        submitLabel="บันทึกการจัดส่ง"
        submitDisabled={trackingNo.trim().length < 4 || providerId === null || (providerId === 'other' && !providerName.trim())}
        onSubmit={submitShip}
        busy={acting === 'ship'}
        onClose={() => setShipOpen(false)}
        cancelLabel="ปิด"
      >
        <Text style={[typography.caption, styles.gapTop, { color: colors.textMuted }]}>บริษัทขนส่ง</Text>
        {providersLoading ? (
          <ActivityIndicator color={colors.gold} style={styles.gapTopSm} />
        ) : (
          <View style={styles.chipWrap}>
            {providers.map((p) => (
              <Chip key={p.id} label={p.name} size="sm" selected={providerId === p.id} onPress={() => setProviderId(p.id)} />
            ))}
            <Chip label="อื่นๆ" size="sm" selected={providerId === 'other'} onPress={() => setProviderId('other')} />
          </View>
        )}
        {providerId === 'other' && (
          <Field label="ชื่อขนส่ง" value={providerName} onChangeText={setProviderName} maxLength={100} placeholder="เช่น ส่งเอง, ไปรษณีย์ไทย" />
        )}
        <Field
          label="เลขพัสดุ"
          required
          value={trackingNo}
          onChangeText={(v) => setTrackingNo(v.replace(/\s/g, '').toUpperCase())}
          autoCapitalize="characters"
          autoCorrect={false}
          maxLength={100}
          placeholder="เช่น TH0123456789"
        />
      </FormSheet>

      {/* ---------- ยกเลิก ---------- */}
      <FormSheet
        visible={cancelOpen}
        icon="x-circle"
        title="ยกเลิกออเดอร์"
        description={
          order.payment_status === 'paid'
            ? 'ลูกค้าจ่ายเงินแล้ว ระบบจะคืนเงินเข้ากระเป๋าของลูกค้าอัตโนมัติ'
            : 'ลูกค้าจะได้รับแจ้งเตือนพร้อมเหตุผลที่คุณเลือก'
        }
        submitLabel="ยืนยันยกเลิกออเดอร์"
        submitVariant="danger"
        submitDisabled={!cancelReason || (cancelReason === 'other' && cancelOther.trim().length < 2)}
        onSubmit={submitCancel}
        busy={acting === 'cancel'}
        cancelLabel="ไม่ยกเลิก"
        onClose={() => setCancelOpen(false)}
      >
        <View style={styles.chipWrap}>
          {CANCEL_REASONS.map((r) => (
            <Chip key={r} label={r} size="sm" selected={cancelReason === r} onPress={() => setCancelReason(r)} />
          ))}
          <Chip label="อื่นๆ" size="sm" selected={cancelReason === 'other'} onPress={() => setCancelReason('other')} />
        </View>
        {cancelReason === 'other' && (
          <Field label="เหตุผล" value={cancelOther} onChangeText={setCancelOther} multiline maxLength={500} placeholder="บอกเหตุผลให้ลูกค้าทราบ" />
        )}
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
  meta: {
    borderTopWidth: 1,
    marginTop: spacing.md,
    paddingTop: spacing.md,
    gap: spacing.xs,
  },
  metaLine: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
  },
  metaIcon: {
    marginTop: 2,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  actions: {
    gap: spacing.sm,
    marginTop: spacing.lg,
  },
  gapTop: {
    marginTop: spacing.md,
  },
  gapTopSm: {
    marginTop: spacing.xs,
  },
  riderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.md,
    borderRadius: radii.lg,
    padding: spacing.sm,
    paddingRight: spacing.md,
  },
  addressRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  buttonRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.lg,
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
    borderRadius: 15,
  },
  total: {
    borderTopWidth: 1,
    marginTop: spacing.md,
    paddingTop: spacing.md,
  },
  trackBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderRadius: radii.md,
    padding: spacing.md,
    marginBottom: spacing.md,
  },
  historyRow: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  timeline: {
    width: 14,
    alignItems: 'center',
    paddingTop: 5,
  },
  timelineDot: {
    width: 12,
    height: 12,
    borderRadius: 6,
    borderWidth: 2,
  },
  timelineLine: {
    flex: 1,
    width: 2,
    marginTop: 3,
    borderRadius: 1,
  },
  historyBody: {
    paddingBottom: spacing.md,
    gap: spacing.xxs,
  },
  chipWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
});
