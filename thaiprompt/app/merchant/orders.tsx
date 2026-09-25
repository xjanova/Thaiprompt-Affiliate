/**
 * ออเดอร์ของร้าน — GET /seller/orders?status= (SHOP-13)
 *
 * - แท็บตัวกรองตรงกับ server (to_confirm / to_ship / shipping / delivered / completed / cancelled / awaiting_payment)
 *   พร้อมตัวเลขงานค้างจาก counts
 * - เปลี่ยนแท็บระหว่างโหลด → ทิ้งผลเก่า (requestId)
 * - หน้าแรกรีเฟรชเงียบๆ ทุก 30 วินาทีระหว่างเปิดหน้า + ดึงลงเพื่อรีเฟรช + เลื่อนโหลดเพิ่ม
 * - แสดงเฉพาะสินค้า/ยอดของร้านนี้ (ออเดอร์หลายร้านแยกให้แล้วที่ server)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { getSellerOrders, type SellerOrderFilter, type SellerOrderListItem } from '@/services/api/merchantApi';
import { Card3D, Chip, EmptyState, Pill, PriceText, Screen, formatBaht } from '@/components/ui';
import { ORDER_STATUS_TONE, formatThaiDateTime, toNumber } from '@/components/shop';
import { useTheme, radii, spacing, typography } from '@/theme';

const FILTERS: Array<{ key: SellerOrderFilter; label: string; countKey?: string }> = [
  { key: 'to_confirm', label: 'รอยืนยัน', countKey: 'to_confirm' },
  { key: 'to_ship', label: 'รอจัดส่ง', countKey: 'to_ship' },
  { key: 'shipping', label: 'กำลังส่ง', countKey: 'shipping' },
  { key: 'awaiting_payment', label: 'รอชำระ', countKey: 'awaiting_payment' },
  { key: 'delivered', label: 'ส่งถึงแล้ว' },
  { key: 'completed', label: 'สำเร็จ' },
  { key: 'cancelled', label: 'ยกเลิก' },
  { key: 'all', label: 'ทั้งหมด' },
];

const VALID = new Set<string>(FILTERS.map((f) => f.key));
const POLL_MS = 30000;
const PER_PAGE = 20;

const OrderRow: React.FC<{ order: SellerOrderListItem }> = ({ order }) => {
  const { colors } = useTheme();
  return (
    <Card3D
      onPress={() => router.push(`/merchant/order/${order.id}` as never)}
      padding={spacing.md}
      radius={radii.lg}
      shadow="sm"
      style={styles.card}
      accessibilityLabel={`ออเดอร์ ${order.order_number} ${order.status_label}`}
    >
      <View style={styles.rowTop}>
        <View style={styles.flex}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>{order.order_number}</Text>
          <Text style={[typography.micro, { color: colors.textFaint }]}>{formatThaiDateTime(order.created_at)}</Text>
        </View>
        <Pill label={order.status_label} tone={ORDER_STATUS_TONE[order.status] || 'neutral'} />
      </View>

      <View style={styles.rowItem}>
        {order.first_item?.product_image ? (
          <Image source={{ uri: order.first_item.product_image }} style={[styles.thumb, { backgroundColor: colors.inset }]} contentFit="cover" />
        ) : (
          <View style={[styles.thumb, styles.center, { backgroundColor: colors.inset }]}>
            <Text style={styles.thumbIcon}>📦</Text>
          </View>
        )}
        <View style={styles.flex}>
          <Text numberOfLines={2} style={[typography.bodyStrong, { color: colors.textStrong }]}>
            {order.first_item?.product_name || 'คำสั่งซื้อ'}
          </Text>
          <Text style={[typography.caption, { color: colors.textMuted }]}>
            👤 {order.customer_name} · {toNumber(order.items_count)} ชิ้น
          </Text>
          {order.is_multi_seller && (
            <Text style={[typography.micro, { color: colors.info }]}>ออเดอร์รวมหลายร้าน แสดงเฉพาะของร้านคุณ</Text>
          )}
        </View>
      </View>

      <View style={[styles.rowBottom, { borderTopColor: colors.divider }]}>
        <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>
          {order.delivery_method === 'rider' ? '🛵 ไรเดอร์' : '📦 พัสดุ'} · {order.payment_method_label}
        </Text>
        <View style={styles.amounts}>
          <PriceText amount={order.seller_total} size="md" tone="gold" />
          <Text style={[typography.micro, { color: colors.success }]}>รับสุทธิ {formatBaht(order.seller_earning)}</Text>
        </View>
      </View>
    </Card3D>
  );
};

type Blocked = { kind: 'not_seller' } | { kind: 'suspended'; message: string } | null;

export default function MerchantOrdersScreen() {
  const { colors } = useTheme();
  const params = useLocalSearchParams<{ status?: string }>();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [filter, setFilter] = useState<SellerOrderFilter>(
    params.status && VALID.has(params.status) ? (params.status as SellerOrderFilter) : 'to_confirm'
  );
  const [orders, setOrders] = useState<SellerOrderListItem[]>([]);
  const [counts, setCounts] = useState<Record<string, number>>({});
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [blocked, setBlocked] = useState<Blocked>(null);

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);
  const pageRef = useRef(1);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'more' | 'silent', targetFilter: SellerOrderFilter, targetPage: number) => {
      if (!isAuthenticated) {
        setInitialLoading(false);
        return;
      }
      const requestId = ++requestIdRef.current;
      if (mode === 'initial') setInitialLoading(true);
      if (mode === 'refresh') setRefreshing(true);
      if (mode === 'more') setLoadingMore(true);

      const res = await getSellerOrders({ status: targetFilter, page: targetPage, per_page: PER_PAGE });
      if (!mountedRef.current || requestId !== requestIdRef.current) return;

      if (res.success) {
        const list = Array.isArray(res.data?.orders) ? res.data.orders : [];
        setOrders((prev) => {
          if (mode !== 'more') return list;
          const seen = new Set(prev.map((o) => o.id));
          return [...prev, ...list.filter((o) => !seen.has(o.id))];
        });
        setPage(targetPage);
        pageRef.current = targetPage;
        const p = res.data?.pagination;
        setHasMore(!!p && (p.has_more ?? p.current_page < p.last_page));
        if (res.data?.counts && typeof res.data.counts === 'object') setCounts(res.data.counts);
        setError(null);
        setBlocked(null);
      } else if (res.code === 'NOT_A_SELLER') {
        setBlocked({ kind: 'not_seller' });
      } else if (res.code === 'STORE_SUSPENDED') {
        setBlocked({ kind: 'suspended', message: res.message });
      } else if (mode === 'initial' || mode === 'refresh') {
        setError(res.message);
      }

      setInitialLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    },
    [isAuthenticated]
  );

  useEffect(() => {
    setOrders([]);
    load('initial', filter, 1);
  }, [filter, load]);

  // รีเฟรชเงียบๆ เฉพาะตอนดูหน้าแรก (ไม่ดึงรายการที่เลื่อนโหลดไว้ทิ้ง)
  useFocusEffect(
    useCallback(() => {
      const timer = setInterval(() => {
        if (pageRef.current === 1) load('silent', filter, 1);
      }, POLL_MS);
      return () => clearInterval(timer);
    }, [filter, load])
  );

  const header = (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filters}>
      {FILTERS.map((f) => (
        <Chip
          key={f.key}
          label={f.label}
          size="sm"
          selected={filter === f.key}
          count={f.countKey ? toNumber(counts[f.countKey]) || undefined : undefined}
          onPress={() => setFilter(f.key)}
        />
      ))}
    </ScrollView>
  );

  const renderBody = () => {
    if (!isAuthenticated) {
      return (
        <EmptyState icon="🔐" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      );
    }
    if (blocked?.kind === 'not_seller') {
      return (
        <EmptyState
          icon="🏪"
          title="บัญชีนี้ยังไม่ได้เปิดร้าน"
          message="เปิดร้านบนเว็บไซต์ก่อน แล้วจัดการออเดอร์ในแอปได้เลย"
          actionLabel="ไปหน้าร้านของฉัน"
          onAction={() => router.replace('/merchant' as never)}
        />
      );
    }
    if (blocked?.kind === 'suspended') {
      return (
        <EmptyState
          variant="error"
          icon="⛔"
          title="ร้านถูกระงับชั่วคราว"
          message={blocked.message}
          actionLabel="ติดต่อทีมงาน"
          onAction={() => router.push('/support')}
        />
      );
    }

    return (
      <FlatList
        data={orders}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => <OrderRow order={item} />}
        ListHeaderComponent={header}
        ListEmptyComponent={
          initialLoading ? (
            <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
          ) : error ? (
            <EmptyState compact variant="error" message={error} onAction={() => load('initial', filter, 1)} />
          ) : (
            <EmptyState
              compact
              icon="🧾"
              title="ไม่มีออเดอร์ในสถานะนี้"
              message={filter === 'to_confirm' ? 'ออเดอร์ใหม่จะแจ้งเตือนมาที่แอปทันที' : 'ลองดูแท็บอื่นนะ'}
            />
          )
        }
        ListFooterComponent={loadingMore ? <ActivityIndicator color={colors.gold} style={styles.footer} /> : null}
        contentContainerStyle={styles.list}
        onEndReached={() => {
          if (hasMore && !loadingMore && !initialLoading && !refreshing) load('more', filter, page + 1);
        }}
        onEndReachedThreshold={0.4}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load('refresh', filter, 1)}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
          />
        }
        showsVerticalScrollIndicator={false}
      />
    );
  };

  return (
    <Screen title="ออเดอร์ของร้าน" subtitle="ยืนยัน · เรียกไรเดอร์ · จัดส่ง" scroll={false}>
      {renderBody()}
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
  loader: {
    marginTop: spacing.xxxl,
  },
  filters: {
    gap: spacing.sm,
    paddingBottom: spacing.md,
    paddingRight: spacing.sm,
  },
  list: {
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.xxxl,
  },
  card: {
    marginBottom: spacing.md,
  },
  rowTop: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.sm,
  },
  rowItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  thumb: {
    width: 56,
    height: 56,
    borderRadius: radii.sm,
  },
  thumbIcon: {
    fontSize: 24,
  },
  rowBottom: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
    marginTop: spacing.md,
    paddingTop: spacing.sm,
    borderTopWidth: 1,
  },
  amounts: {
    alignItems: 'flex-end',
  },
  footer: {
    marginVertical: spacing.lg,
  },
});
