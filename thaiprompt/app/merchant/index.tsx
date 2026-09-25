/**
 * ร้านของฉัน (โหมดร้านค้า) — GET /seller/summary + ออเดอร์ใหม่ล่าสุด (SHOP-13 / SELLER-21)
 *
 * - ตัวเลขงานค้าง (กดแล้วไปรายการที่กรองไว้) + ยอดขาย/รายรับสุทธิ/ค่า GP ของเดือนนี้
 * - ออเดอร์ใหม่รอยืนยัน 5 รายการล่าสุด → แตะเพื่อจัดการในแอป
 * - จัดการสินค้า ตั้งราคา และวางกลยุทธ์ GP อยู่บนเว็บไซต์ (WebsiteButton — ล็อกอินให้อัตโนมัติ)
 * - ยังไม่มีร้าน (403 NOT_A_SELLER) → ชวนเปิดร้านบนเว็บ · ร้านถูกระงับ (STORE_SUSPENDED) → แจ้งเหตุผล
 * - รีเฟรชเงียบๆ ทุก 30 วินาทีระหว่างเปิดหน้านี้ (ไม่มีสปินเนอร์เต็มจอ)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  getSellerOrders,
  getSellerSummary,
  type SellerOrderFilter,
  type SellerOrderListItem,
  type SellerSummary,
} from '@/services/api/merchantApi';
import {
  BannerSlider,
  Button3D,
  Card3D,
  EmptyState,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  StatTile,
  WebsiteButton,
} from '@/components/ui';
import { ORDER_STATUS_TONE, formatThaiDateTime, toNumber } from '@/components/shop';
import { useTheme, radii, spacing, typography } from '@/theme';

const POLL_MS = 30000;

type LoadState =
  | { kind: 'loading' }
  | { kind: 'ready'; summary: SellerSummary }
  | { kind: 'not_seller' }
  | { kind: 'suspended'; message: string; reason: string | null }
  | { kind: 'error'; message: string };

/** กันคีย์หาย/เป็นสตริง — ตัวเลขเป็น number เสมอ */
const normalizeSummary = (d: SellerSummary): SellerSummary => ({
  store: d.store ?? null,
  counts: {
    to_confirm: toNumber(d.counts?.to_confirm),
    to_ship: toNumber(d.counts?.to_ship),
    shipping: toNumber(d.counts?.shipping),
    awaiting_payment: toNumber(d.counts?.awaiting_payment),
    rider_active: toNumber(d.counts?.rider_active),
  },
  sales: {
    today: toNumber(d.sales?.today),
    month: toNumber(d.sales?.month),
    month_net_earning: toNumber(d.sales?.month_net_earning),
    month_gp: toNumber(d.sales?.month_gp),
  },
});

const NewOrderRow: React.FC<{ order: SellerOrderListItem }> = ({ order }) => {
  const { colors } = useTheme();
  return (
    <Card3D
      onPress={() => router.push(`/merchant/order/${order.id}` as never)}
      padding={spacing.md}
      radius={radii.lg}
      shadow="sm"
      style={styles.orderCard}
      accessibilityLabel={`ออเดอร์ ${order.order_number} ของ ${order.customer_name}`}
    >
      <View style={styles.orderRow}>
        {order.first_item?.product_image ? (
          <Image source={{ uri: order.first_item.product_image }} style={[styles.thumb, { backgroundColor: colors.inset }]} contentFit="cover" />
        ) : (
          <View style={[styles.thumb, styles.center, { backgroundColor: colors.inset }]}>
            <Text>📦</Text>
          </View>
        )}
        <View style={styles.flex}>
          <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
            {order.first_item?.product_name || 'คำสั่งซื้อ'}
            {order.items_count > 1 ? ` +${order.items_count - 1}` : ''}
          </Text>
          <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
            {order.customer_name} · {order.delivery_method === 'rider' ? '🛵 ไรเดอร์' : '📦 พัสดุ'} · {formatThaiDateTime(order.created_at)}
          </Text>
        </View>
        <View style={styles.orderRight}>
          <PriceText amount={order.seller_total} size="sm" tone="gold" />
          <Pill label={order.status_label} tone={ORDER_STATUS_TONE[order.status] || 'neutral'} />
        </View>
      </View>
    </Card3D>
  );
};

export default function MerchantScreen() {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const [state, setState] = useState<LoadState>({ kind: 'loading' });
  const [newOrders, setNewOrders] = useState<SellerOrderListItem[]>([]);
  const [refreshing, setRefreshing] = useState(false);
  const mountedRef = useRef(true);
  const busyRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(async () => {
    if (!isAuthenticated || busyRef.current) return;
    busyRef.current = true;
    const [result, orders] = await Promise.all([
      getSellerSummary(),
      getSellerOrders({ status: 'to_confirm', per_page: 5 }),
    ]);
    busyRef.current = false;
    if (!mountedRef.current) return;

    if (orders.success) {
      setNewOrders(Array.isArray(orders.data?.orders) ? orders.data.orders : []);
    }

    if (result.success && result.data) {
      setState({ kind: 'ready', summary: normalizeSummary(result.data) });
      return;
    }
    if (!result.success) {
      if (result.code === 'NOT_A_SELLER') {
        setState({ kind: 'not_seller' });
      } else if (result.code === 'STORE_SUSPENDED') {
        const reason = typeof result.data?.reason === 'string' && result.data.reason ? result.data.reason : null;
        setState({ kind: 'suspended', message: result.message, reason });
      } else {
        // โหลดซ้ำแล้วล้ม → เก็บข้อมูลเดิมไว้ ไม่ล้างหน้าจอ
        setState((prev) => (prev.kind === 'ready' ? prev : { kind: 'error', message: result.message }));
      }
    }
  }, [isAuthenticated]);

  // เปิดหน้า → โหลด + รีเฟรชเงียบๆ ทุก 30 วินาที
  useFocusEffect(
    useCallback(() => {
      load();
      const timer = setInterval(load, POLL_MS);
      return () => clearInterval(timer);
    }, [load])
  );

  const onRefresh = async () => {
    setRefreshing(true);
    await load();
    if (mountedRef.current) setRefreshing(false);
  };

  const openOrders = (status: SellerOrderFilter) => router.push(`/merchant/orders?status=${status}` as never);

  if (!isAuthenticated) {
    return (
      <Screen title="ร้านของฉัน" scroll={false}>
        <EmptyState
          icon="🔐"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อดูออเดอร์และยอดขายของร้าน"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </Screen>
    );
  }

  const renderBody = () => {
    switch (state.kind) {
      case 'loading':
        return <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />;

      case 'not_seller':
        return (
          <Card3D gradientBorder padding={spacing.xl}>
            <Text style={[typography.h2, { color: colors.textStrong }]}>ยังไม่มีร้านค้า</Text>
            <Text style={[typography.body, styles.lead, { color: colors.textMuted }]}>
              เปิดร้านบนเว็บไซต์ได้เลย เมื่อร้านพร้อมขาย ออเดอร์ใหม่จะแจ้งเตือนมาที่แอปนี้ และจัดการออเดอร์ในแอปได้ทันที
            </Text>
            <WebsiteButton
              path="/user/seller-apply"
              label="เปิดร้านบนเว็บไซต์"
              icon="🏪"
              variant="primary"
              size="lg"
              fullWidth
              style={styles.cta}
            />
          </Card3D>
        );

      case 'suspended':
        return (
          <EmptyState
            compact
            variant="error"
            icon="⛔"
            title="ร้านถูกระงับชั่วคราว"
            message={state.reason ? `${state.message}\nเหตุผล: ${state.reason}` : state.message}
            actionLabel="ติดต่อทีมงาน"
            onAction={() => router.push('/support')}
          />
        );

      case 'error':
        return <EmptyState compact variant="error" message={state.message} onAction={load} />;

      case 'ready': {
        const { store, counts, sales } = state.summary;
        return (
          <>
            {/* ร้าน + ยอดขาย */}
            <Card3D gradientBorder padding={spacing.lg}>
              <View style={styles.storeRow}>
                {store?.logo ? (
                  <Image source={{ uri: store.logo }} style={[styles.logo, { backgroundColor: colors.inset }]} contentFit="cover" />
                ) : (
                  <View style={[styles.logo, styles.center, { backgroundColor: colors.goldSoft }]}>
                    <Text style={styles.logoIcon}>🏪</Text>
                  </View>
                )}
                <View style={styles.flex}>
                  <Text numberOfLines={1} style={[typography.h2, { color: colors.textStrong }]}>
                    {store?.name || 'ร้านของฉัน'}
                  </Text>
                  <View style={styles.pills}>
                    {store?.is_verified && <Pill label="ร้านยืนยันแล้ว" tone="success" icon="✔" />}
                    {store && (
                      <Pill
                        label={store.rider_delivery ? 'ส่งด้วยไรเดอร์ได้' : 'ส่งพัสดุ'}
                        tone={store.rider_delivery ? 'gold' : 'neutral'}
                        icon={store.rider_delivery ? '🛵' : '📦'}
                      />
                    )}
                    {store && !store.is_active && <Pill label="ปิดร้านอยู่" tone="danger" />}
                  </View>
                </View>
              </View>

              <View style={styles.salesRow}>
                <View style={styles.flex}>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>ยอดขายวันนี้</Text>
                  <PriceText amount={sales.today} size="lg" tone="gold" />
                </View>
                <View style={styles.flex}>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>ยอดขายเดือนนี้</Text>
                  <PriceText amount={sales.month} size="lg" tone="strong" />
                </View>
              </View>
              <View style={[styles.netBox, { backgroundColor: colors.inset }]}>
                <View style={styles.flex}>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>รายรับสุทธิเดือนนี้</Text>
                  <PriceText amount={sales.month_net_earning} size="md" tone="success" />
                </View>
                <View style={styles.flex}>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>ค่าธรรมเนียม GP เดือนนี้</Text>
                  <PriceText amount={sales.month_gp} size="md" tone="default" />
                </View>
              </View>
            </Card3D>

            {store?.rider_delivery_enabled && !store.has_pickup_location && (
              <Card3D variant="flat" padding={spacing.md} style={styles.warning}>
                <Text style={[typography.bodySm, { color: colors.warning }]}>
                  📍 ยังไม่ได้ปักหมุดจุดรับสินค้า ไรเดอร์จะยังรับงานของร้านไม่ได้
                </Text>
                <WebsiteButton
                  path="/seller/store/settings"
                  label="ปักหมุดบนเว็บไซต์"
                  size="sm"
                  variant="secondary"
                  style={styles.warningCta}
                />
              </Card3D>
            )}

            {/* งานค้าง */}
            <SectionHeader title="ออเดอร์ที่ต้องจัดการ" icon="🧾" actionLabel="ดูทั้งหมด" onAction={() => openOrders('all')} style={styles.sectionHeader} />
            <View style={styles.grid}>
              <StatTile label="รอยืนยัน" value={counts.to_confirm} icon="🔔" tone="warning" style={styles.tile} onPress={() => openOrders('to_confirm')} />
              <StatTile label="รอจัดส่ง" value={counts.to_ship} icon="📦" tone="gold" style={styles.tile} onPress={() => openOrders('to_ship')} />
              <StatTile label="กำลังจัดส่ง" value={counts.shipping} icon="🚚" tone="info" style={styles.tile} onPress={() => openOrders('shipping')} />
              <StatTile
                label="รอลูกค้าชำระ"
                value={counts.awaiting_payment}
                icon="⏳"
                tone="neutral"
                caption={counts.rider_active > 0 ? `ไรเดอร์กำลังวิ่ง ${counts.rider_active} งาน` : undefined}
                style={styles.tile}
                onPress={() => openOrders('awaiting_payment')}
              />
            </View>

            {/* ออเดอร์ใหม่ */}
            <SectionHeader
              title="ออเดอร์ใหม่รอยืนยัน"
              icon="🆕"
              actionLabel={newOrders.length > 0 ? 'ทั้งหมด' : undefined}
              onAction={() => openOrders('to_confirm')}
              style={styles.sectionHeader}
            />
            {newOrders.length === 0 ? (
              <Card3D variant="flat" padding={spacing.lg}>
                <Text style={[typography.bodySm, styles.centerText, { color: colors.textMuted }]}>
                  ยังไม่มีออเดอร์ใหม่ เมื่อมีออเดอร์เข้า แอปจะแจ้งเตือนทันที 🔔
                </Text>
              </Card3D>
            ) : (
              newOrders.map((o) => <NewOrderRow key={o.id} order={o} />)
            )}

            <Button3D
              title="จัดการออเดอร์ทั้งหมด"
              icon="🧾"
              size="lg"
              fullWidth
              onPress={() => openOrders('all')}
              style={styles.sectionHeader}
            />

            {/* งานบนเว็บ */}
            <SectionHeader title="จัดการร้านบนเว็บไซต์" icon="🌐" subtitle="สินค้า ราคา และกลยุทธ์ GP อยู่บนเว็บ" style={styles.sectionHeader} />
            <Card3D padding={spacing.lg}>
              <WebsiteButton
                path="/seller/products"
                label="จัดการสินค้า ตั้งราคา & วางกลยุทธ์ GP"
                icon="🛠️"
                variant="primary"
                fullWidth
              />
              <View style={styles.webRow}>
                <WebsiteButton path="/seller/pricing/planner" label="วางแผนราคา" icon="📊" size="sm" style={styles.flex} />
                <WebsiteButton path="/seller/store/settings" label="ตั้งค่าร้าน" icon="⚙️" size="sm" style={styles.flex} />
              </View>
              <Text style={[typography.caption, styles.webNote, { color: colors.textMuted }]}>
                เปิดในเบราว์เซอร์และเข้าสู่ระบบให้อัตโนมัติ
              </Text>
            </Card3D>
          </>
        );
      }
      default:
        return null;
    }
  };

  return (
    <Screen title="ร้านของฉัน" subtitle="ออเดอร์และยอดขาย" refreshing={refreshing} onRefresh={onRefresh}>
      <View style={styles.bannerWrap}>
        <BannerSlider placement="merchant" height={140} autoPlay={false} />
      </View>
      {renderBody()}
      {state.kind !== 'loading' && state.kind !== 'not_seller' && (
        <Button3D title="ช่วยเหลือร้านค้า" variant="ghost" size="sm" onPress={() => router.push('/support')} style={styles.help} />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  centerText: {
    textAlign: 'center',
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  bannerWrap: {
    marginHorizontal: -spacing.screen,
    marginBottom: spacing.lg,
  },
  lead: {
    marginTop: spacing.sm,
  },
  cta: {
    marginTop: spacing.xl,
  },
  storeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  logo: {
    width: 52,
    height: 52,
    borderRadius: 16,
  },
  logoIcon: {
    fontSize: 24,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  salesRow: {
    flexDirection: 'row',
    marginTop: spacing.lg,
    gap: spacing.md,
  },
  netBox: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.md,
    borderRadius: radii.md,
    padding: spacing.md,
  },
  warning: {
    marginTop: spacing.md,
  },
  warningCta: {
    marginTop: spacing.sm,
    alignSelf: 'flex-start',
  },
  sectionHeader: {
    marginTop: spacing.xl,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    rowGap: spacing.md,
  },
  tile: {
    width: '48%',
  },
  orderCard: {
    marginBottom: spacing.sm,
  },
  orderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  orderRight: {
    alignItems: 'flex-end',
    gap: spacing.xs,
  },
  thumb: {
    width: 48,
    height: 48,
    borderRadius: radii.sm,
  },
  webRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  webNote: {
    marginTop: spacing.sm,
    textAlign: 'center',
  },
  help: {
    marginTop: spacing.lg,
    alignSelf: 'center',
  },
});
