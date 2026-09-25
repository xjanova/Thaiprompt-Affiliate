/**
 * ร้านของฉัน (โหมดร้านค้า) — GET /seller/summary + ออเดอร์ใหม่ล่าสุด (SHOP-13 / SELLER-21)
 *
 * - ตัวเลขงานค้าง (กดแล้วไปรายการที่กรองไว้) + ยอดขาย/รายรับสุทธิ/ค่า GP ของเดือนนี้
 * - ออเดอร์ใหม่รอยืนยัน 5 รายการล่าสุด → แตะเพื่อจัดการในแอป
 * - จัดการสินค้า ตั้งราคา และวางกลยุทธ์ GP อยู่บนเว็บไซต์ (WebsiteButton — ล็อกอินให้อัตโนมัติ)
 * - ยังไม่มีร้าน (403 NOT_A_SELLER) → ชวนเปิดร้านบนเว็บ · ร้านถูกระงับ (STORE_SUSPENDED) → แจ้งเหตุผล
 * - รีเฟรชเงียบๆ ทุก 30 วินาทีระหว่างเปิดหน้านี้ (ไม่มีสปินเนอร์เต็มจอ)
 * - ปุ่มสลับด้านบน → ร้านตลาดสด (/merchant/taladsod) ซึ่งเป็นอีกระบบ (รถเข็น/ตลาดนัด/ร้านอาหาร)
 *
 * หน้าตา: การ์ดน้ำเงินกรมท่าลายกนก (ชื่อร้านตัวมีเชิง + ยอดขายวันนี้ตัวเลขทอง) → งานค้าง (StatTile)
 *         → ออเดอร์ใหม่ → ปุ่มทองจัดการออเดอร์ → งานบนเว็บ
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
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
  BrandArt,
  Button3D,
  Card3D,
  EmptyState,
  Icon,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  StatTile,
  WebsiteButton,
} from '@/components/ui';
import { ORDER_STATUS_TONE, formatThaiDateTime, toNumber } from '@/components/shop';
import { HeroCard, IconTile, MerchantModeSwitch } from '@/components/merchant';
import { checkIsFreshMarketSeller } from '@/services/api/taladsodSellerApi';
import { DARK_THEME, useTheme, radii, spacing, typography } from '@/theme';

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
  const isRider = order.delivery_method === 'rider';
  return (
    <Card3D
      onPress={() => router.push(`/merchant/order/${order.id}` as never)}
      padding={spacing.md}
      radius={radii.xl}
      shadow="sm"
      style={styles.orderCard}
      accessibilityLabel={`ออเดอร์ ${order.order_number} ของ ${order.customer_name}`}
    >
      <View style={styles.orderRow}>
        {order.first_item?.product_image ? (
          <Image source={{ uri: order.first_item.product_image }} style={[styles.thumb, { backgroundColor: colors.inset }]} contentFit="cover" />
        ) : (
          <IconTile icon="package" size={52} />
        )}
        <View style={styles.flex}>
          <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
            {order.first_item?.product_name || 'คำสั่งซื้อ'}
            {order.items_count > 1 ? ` +${order.items_count - 1}` : ''}
          </Text>
          <View style={styles.metaLine}>
            <Icon name={isRider ? 'moped' : 'package'} size={14} color={colors.textMuted} />
            <Text numberOfLines={1} style={[typography.caption, styles.flex, { color: colors.textMuted }]}>
              {order.customer_name} · {isRider ? 'ไรเดอร์' : 'พัสดุ'} · {formatThaiDateTime(order.created_at)}
            </Text>
          </View>
        </View>
        <View style={styles.orderRight}>
          <PriceText amount={order.seller_total} size="md" tone="gold" />
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
  const [hasFmShop, setHasFmShop] = useState(false);
  const userId = useAuthStore((s) => s.user?.id ?? null);
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

  // ยังไม่มีร้านออนไลน์ → เช็คว่ามีร้านตลาดสดไหม (ชวนไปหน้าร้านตลาดสดแทน)
  useEffect(() => {
    if (state.kind !== 'not_seller' || !userId) return;
    let alive = true;
    checkIsFreshMarketSeller(userId)
      .then((isFm) => {
        if (alive && mountedRef.current) setHasFmShop(isFm);
      })
      .catch(() => {});
    return () => {
      alive = false;
    };
  }, [state.kind, userId]);

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
          art="store"
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
          <>
            {hasFmShop && (
              <Card3D
                gradientBorder
                padding={spacing.lg}
                style={styles.fmCard}
              >
                <View style={styles.fmRow}>
                  <BrandArt name="basket" size={64} />
                  <View style={styles.flex}>
                    <Text style={[typography.h3, { color: colors.textStrong }]}>คุณมีร้านในตลาดสด</Text>
                    <Text style={[typography.bodySm, { color: colors.textMuted }]}>
                      เปิดร้าน รับออเดอร์ และจัดการสินค้าตลาดสดได้ที่ "ร้านตลาดสด"
                    </Text>
                  </View>
                </View>
                <Button3D
                  title="ไปที่ร้านตลาดสด"
                  icon="basket"
                  iconRight="arrow-right"
                  variant="success"
                  size="md"
                  fullWidth
                  onPress={() => router.replace('/merchant/taladsod' as never)}
                  style={styles.fmCta}
                />
              </Card3D>
            )}
            <Card3D gradientBorder padding={spacing.xl} contentStyle={styles.centerBox}>
              <BrandArt name="store" size={128} />
              <Text style={[typography.serif, styles.centerText, { color: colors.textStrong }]}>ยังไม่มีร้านค้า</Text>
              <Text style={[typography.body, styles.lead, styles.centerText, { color: colors.textMuted }]}>
                เปิดร้านบนเว็บไซต์ได้เลย เมื่อร้านพร้อมขาย ออเดอร์ใหม่จะแจ้งเตือนมาที่แอปนี้ และจัดการออเดอร์ในแอปได้ทันที
              </Text>
              <WebsiteButton
                path="/user/seller-apply"
                label="เปิดร้านบนเว็บไซต์"
                icon="storefront"
                variant="primary"
                size="lg"
                fullWidth
                style={styles.cta}
              />
            </Card3D>
          </>
        );

      case 'suspended':
        return (
          <EmptyState
            compact
            variant="error"
            icon="prohibit"
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
            {/* ร้าน + ยอดขาย (การ์ดน้ำเงินกรมท่า ตัวเลขเงินสีทอง) */}
            <HeroCard>
              <View style={styles.storeRow}>
                {store?.logo ? (
                  <Image
                    source={{ uri: store.logo }}
                    style={[styles.logo, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}
                    contentFit="cover"
                  />
                ) : (
                  <View style={[styles.logo, styles.center, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
                    <BrandArt name="store" size={46} />
                  </View>
                )}
                <View style={styles.flex}>
                  <Text numberOfLines={1} style={[typography.serif, { color: colors.onHeader }]}>
                    {store?.name || 'ร้านของฉัน'}
                  </Text>
                  <View style={styles.pills}>
                    {store?.is_verified && <Pill label="ร้านยืนยันแล้ว" tone="success" icon="seal-check" />}
                    {store && (
                      <Pill
                        label={store.rider_delivery ? 'ส่งด้วยไรเดอร์ได้' : 'ส่งพัสดุ'}
                        tone={store.rider_delivery ? 'gold' : 'neutral'}
                        icon={store.rider_delivery ? 'moped' : 'package'}
                      />
                    )}
                    {store && !store.is_active && <Pill label="ปิดร้านอยู่" tone="danger" />}
                  </View>
                </View>
              </View>

              <View style={[styles.todayBox, { borderTopColor: colors.headerGlassBorder }]}>
                <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>ยอดขายวันนี้</Text>
                <PriceText amount={sales.today} size="xl" style={[typography.moneyLg, { color: colors.goldLight }]} />
              </View>

              <View style={[styles.kpiRow, { borderTopColor: colors.headerGlassBorder }]}>
                <View style={styles.flex}>
                  <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>ยอดขายเดือนนี้</Text>
                  <PriceText amount={sales.month} size="md" style={{ color: colors.onHeader }} />
                </View>
                <View style={[styles.kpiDivider, { backgroundColor: colors.headerGlassBorder }]} />
                {/* เงินเข้าใช้เขียวสว่างของชุดสีโหมดมืด — การ์ดน้ำเงินนี้มืดเสมอทั้งสองโหมด */}
                <View style={styles.flex}>
                  <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>รายรับสุทธิเดือนนี้</Text>
                  <PriceText amount={sales.month_net_earning} size="md" style={{ color: DARK_THEME.colors.success }} />
                </View>
              </View>
              <View style={[styles.gpRow, { backgroundColor: colors.headerGlass }]}>
                <Icon name="percent" size={15} color={colors.goldLight} />
                <Text style={[typography.caption, styles.flex, { color: colors.onHeaderMuted }]}>ค่าธรรมเนียม GP เดือนนี้</Text>
                <PriceText amount={sales.month_gp} size="sm" style={{ color: colors.onHeader }} />
              </View>
            </HeroCard>

            {store?.rider_delivery_enabled && !store.has_pickup_location && (
              <Card3D padding={spacing.md} shadow="sm" style={styles.warning}>
                <View style={styles.warningRow}>
                  <IconTile icon="map-pin" tone="warning" />
                  <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
                    ยังไม่ได้ปักหมุดจุดรับสินค้า ไรเดอร์จะยังรับงานของร้านไม่ได้
                  </Text>
                </View>
                <WebsiteButton
                  path="/seller/store/settings"
                  label="ปักหมุดบนเว็บไซต์"
                  icon="map-pin"
                  size="sm"
                  variant="secondary"
                  style={styles.warningCta}
                />
              </Card3D>
            )}

            {/* งานค้าง */}
            <SectionHeader title="ออเดอร์ที่ต้องจัดการ" actionLabel="ดูทั้งหมด" onAction={() => openOrders('all')} style={styles.sectionHeader} />
            <View style={styles.grid}>
              <StatTile label="รอยืนยัน" value={counts.to_confirm} icon="bell-ringing" tone="warning" style={styles.tile} onPress={() => openOrders('to_confirm')} />
              <StatTile label="รอจัดส่ง" value={counts.to_ship} icon="package" tone="gold" style={styles.tile} onPress={() => openOrders('to_ship')} />
              <StatTile label="กำลังจัดส่ง" value={counts.shipping} icon="truck" tone="info" style={styles.tile} onPress={() => openOrders('shipping')} />
              <StatTile
                label="รอลูกค้าชำระ"
                value={counts.awaiting_payment}
                icon="hourglass"
                tone="neutral"
                caption={counts.rider_active > 0 ? `ไรเดอร์กำลังวิ่ง ${counts.rider_active} งาน` : undefined}
                style={styles.tile}
                onPress={() => openOrders('awaiting_payment')}
              />
            </View>

            {/* ออเดอร์ใหม่ */}
            <SectionHeader
              title="ออเดอร์ใหม่รอยืนยัน"
              actionLabel={newOrders.length > 0 ? 'ทั้งหมด' : undefined}
              onAction={() => openOrders('to_confirm')}
              style={styles.sectionHeader}
            />
            {newOrders.length === 0 ? (
              <Card3D variant="flat" padding={spacing.lg}>
                <View style={styles.emptyRow}>
                  <IconTile icon="bell-ringing" tone="gold" />
                  <Text style={[typography.bodySm, styles.flex, { color: colors.textMuted }]}>
                    ยังไม่มีออเดอร์ใหม่ เมื่อมีออเดอร์เข้า แอปจะแจ้งเตือนทันที
                  </Text>
                </View>
              </Card3D>
            ) : (
              newOrders.map((o) => <NewOrderRow key={o.id} order={o} />)
            )}

            <Button3D
              title="จัดการออเดอร์ทั้งหมด"
              icon="receipt"
              iconRight="arrow-right"
              size="lg"
              fullWidth
              onPress={() => openOrders('all')}
              style={styles.sectionHeader}
            />

            {/* งานบนเว็บ */}
            <SectionHeader title="จัดการร้านบนเว็บไซต์" subtitle="สินค้า ราคา และกลยุทธ์ GP อยู่บนเว็บ" style={styles.sectionHeader} />
            <Card3D padding={spacing.lg}>
              <WebsiteButton
                path="/seller/products"
                label="จัดการสินค้า ตั้งราคา & วางกลยุทธ์ GP"
                icon="sliders-horizontal"
                variant="navy"
                fullWidth
              />
              <View style={styles.webRow}>
                <WebsiteButton path="/seller/pricing/planner" label="วางแผนราคา" icon="chart-bar" size="sm" style={styles.flex} />
                <WebsiteButton path="/seller/store/settings" label="ตั้งค่าร้าน" icon="gear-six" size="sm" style={styles.flex} />
              </View>
              <View style={styles.webNoteRow}>
                <Icon name="lock" size={13} color={colors.textFaint} />
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  เปิดในเบราว์เซอร์และเข้าสู่ระบบให้อัตโนมัติ
                </Text>
              </View>
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
      <MerchantModeSwitch current="shop" />
      <View style={styles.bannerWrap}>
        <BannerSlider placement="merchant" height={140} autoPlay={false} />
      </View>
      {renderBody()}
      {state.kind !== 'loading' && state.kind !== 'not_seller' && (
        <Button3D title="ช่วยเหลือร้านค้า" icon="lifebuoy" variant="ghost" size="sm" onPress={() => router.push('/support')} style={styles.help} />
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
  centerBox: {
    alignItems: 'center',
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
  fmRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  fmCta: {
    marginTop: spacing.md,
  },
  // ---------- การ์ดน้ำเงิน ----------
  storeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  logo: {
    width: 56,
    height: 56,
    borderRadius: 18,
    borderWidth: 1,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  todayBox: {
    marginTop: spacing.lg,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  kpiRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  kpiDivider: {
    width: 1,
    alignSelf: 'stretch',
  },
  gpRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    borderRadius: radii.sm,
    paddingVertical: spacing.sm,
    paddingHorizontal: spacing.md,
  },
  // ---------- เนื้อหา ----------
  warning: {
    marginTop: spacing.md,
  },
  warningRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  warningCta: {
    marginTop: spacing.md,
    alignSelf: 'flex-start',
  },
  sectionHeader: {
    marginTop: spacing.xxl,
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
  emptyRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
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
  metaLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 2,
  },
  thumb: {
    width: 52,
    height: 52,
    borderRadius: 15,
  },
  webRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  webNoteRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 5,
    marginTop: spacing.md,
  },
  help: {
    marginTop: spacing.lg,
    alignSelf: 'center',
  },
  fmCard: {
    marginBottom: spacing.lg,
  },
});
