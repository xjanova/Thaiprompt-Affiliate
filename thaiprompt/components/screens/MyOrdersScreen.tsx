/**
 * MyOrdersScreen — คำสั่งซื้อของฉัน (ใช้ทั้งแท็บ "คำสั่งซื้อ" และหน้า /orders)
 *
 * - แท็บสถานะตรงกับ enum ของ orders บน server (SHOP-24) และแสดง status_label จาก server
 * - เปลี่ยนแท็บระหว่างโหลด → ทิ้งผลลัพธ์เก่า (requestId) กันรายการสลับแท็บ
 * - รีเฟรช = pull-to-refresh (ไม่เอาสปินเนอร์เต็มจอมาบัง)
 * - สลับ "ร้านค้า | ตลาดสด" ด้านบน → ตลาดสดใช้ FreshOrdersList (GET /fresh-market/orders)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { getMyOrders, type ShopOrderListItem, type ShopOrderStatus } from '@/services/api/shopApi';
import { useTheme, spacing, radii, typography, type Tone } from '@/theme';
import { Card3D, Chip, EmptyState, Pill, PriceText, Screen } from '@/components/ui';
import { FreshOrdersList, OrderSourceSwitch, type OrderSource } from '@/components/taladsod';

type Filter = 'all' | ShopOrderStatus;

const FILTERS: Array<{ key: Filter; label: string }> = [
  { key: 'all', label: 'ทั้งหมด' },
  { key: 'pending', label: 'รอชำระ' },
  { key: 'paid', label: 'ชำระแล้ว' },
  { key: 'processing', label: 'กำลังเตรียม' },
  { key: 'shipped', label: 'กำลังจัดส่ง' },
  { key: 'delivered', label: 'ส่งถึงแล้ว' },
  { key: 'completed', label: 'สำเร็จ' },
  { key: 'cancelled', label: 'ยกเลิก' },
  { key: 'refunded', label: 'คืนเงิน' },
];

const STATUS_TONE: Record<string, Tone> = {
  pending: 'warning',
  paid: 'info',
  processing: 'gold',
  shipped: 'info',
  delivered: 'success',
  completed: 'success',
  cancelled: 'danger',
  refunded: 'neutral',
};

const formatDate = (iso: string): string => {
  try {
    return new Date(iso).toLocaleDateString('th-TH', {
      day: 'numeric',
      month: 'short',
      year: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return '';
  }
};

const OrderRow: React.FC<{ order: ShopOrderListItem }> = ({ order }) => {
  const { colors } = useTheme();

  return (
    <Card3D
      onPress={() => router.push(`/order/${order.id}` as never)}
      padding={spacing.md}
      radius={radii.lg}
      shadow="sm"
      style={styles.card}
      accessibilityLabel={`คำสั่งซื้อ ${order.order_number} ${order.status_label}`}
    >
      <View style={styles.rowTop}>
        <View style={styles.flex}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>{order.order_number}</Text>
          <Text style={[typography.micro, { color: colors.textFaint }]}>{formatDate(order.created_at)}</Text>
        </View>
        {order.has_unread_messages && (
          <View style={[styles.unread, { backgroundColor: colors.danger }]} accessibilityLabel="มีข้อความใหม่" />
        )}
        <Pill label={order.status_label} tone={STATUS_TONE[order.status] || 'neutral'} />
      </View>

      <View style={styles.rowItem}>
        {order.first_item?.product_image ? (
          <Image
            source={{ uri: order.first_item.product_image }}
            style={[styles.thumb, { backgroundColor: colors.inset }]}
            contentFit="cover"
            transition={150}
          />
        ) : (
          <View style={[styles.thumb, styles.thumbEmpty, { backgroundColor: colors.inset }]}>
            <Text style={styles.thumbIcon}>📦</Text>
          </View>
        )}
        <View style={styles.flex}>
          <Text numberOfLines={2} style={[typography.bodyStrong, { color: colors.textStrong }]}>
            {order.first_item?.product_name || 'คำสั่งซื้อ'}
          </Text>
          {order.items_count > 1 && (
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              และอีก {order.items_count - 1} รายการ
            </Text>
          )}
        </View>
      </View>

      <View style={[styles.rowBottom, { borderTopColor: colors.divider }]}>
        <Text style={[typography.caption, { color: colors.textMuted }]}>
          {order.delivery_method === 'rider' ? '🛵 ส่งด้วยไรเดอร์' : '📦 ส่งพัสดุ'} · {order.items_count} ชิ้น
        </Text>
        <PriceText amount={order.total_amount} size="md" tone="gold" />
      </View>
    </Card3D>
  );
};

export interface MyOrdersScreenProps {
  /** true = อยู่ในแท็บ (ไม่มีปุ่มย้อนกลับ) */
  embedded?: boolean;
  /** เปิดมาที่รายการไหนก่อน (ค่าเริ่มต้น ร้านค้า) */
  initialSource?: OrderSource;
}

export const MyOrdersScreen: React.FC<MyOrdersScreenProps> = ({ embedded = false, initialSource = 'shop' }) => {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const [source, setSource] = useState<OrderSource>(initialSource);

  const [filter, setFilter] = useState<Filter>('all');
  const [orders, setOrders] = useState<ShopOrderListItem[]>([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const requestIdRef = useRef(0);
  const mountedRef = useRef(true);
  const loadedOnceRef = useRef(false);
  /** แท็บสถานะปัจจุบัน — ให้ตัวรีเฟรชตอนกลับเข้าหน้าอ่านค่าล่าสุดเสมอ (ไม่ติดค่าตอน mount) */
  const filterRef = useRef<Filter>(filter);
  filterRef.current = filter;

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'more', targetFilter: Filter, targetPage: number) => {
      if (!isAuthenticated) return;
      const requestId = ++requestIdRef.current;

      if (mode === 'initial') setInitialLoading(true);
      if (mode === 'refresh') setRefreshing(true);
      if (mode === 'more') setLoadingMore(true);

      const result = await getMyOrders({
        status: targetFilter === 'all' ? undefined : targetFilter,
        page: targetPage,
        per_page: 15,
      });

      // ผู้ใช้เปลี่ยนแท็บ/ออกจากหน้าแล้ว → ทิ้งผลลัพธ์นี้
      if (!mountedRef.current || requestId !== requestIdRef.current) return;

      if (result.success) {
        const items = Array.isArray(result.data?.orders) ? result.data.orders : [];
        setOrders((prev) => (mode === 'more' ? [...prev, ...items] : items));
        setPage(targetPage);
        const p = result.data?.pagination;
        setHasMore(!!p && (p.has_more ?? p.current_page < p.last_page));
        setErrorMessage(null);
        loadedOnceRef.current = true;
      } else if (mode !== 'more') {
        setErrorMessage(result.message);
      }

      setInitialLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    },
    [isAuthenticated]
  );

  // โหลดใหม่เมื่อเปลี่ยนแท็บสถานะ
  useEffect(() => {
    if (!isAuthenticated) {
      setInitialLoading(false);
      return;
    }
    setOrders([]);
    load('initial', filter, 1);
  }, [filter, isAuthenticated, load]);

  // กลับมาที่หน้านี้ (เช่น หลังชำระเงิน) → รีเฟรชเงียบๆ ตามแท็บสถานะที่เลือกอยู่
  useFocusEffect(
    useCallback(() => {
      if (isAuthenticated && loadedOnceRef.current && source === 'shop') {
        load('refresh', filterRef.current, 1);
      }
    }, [isAuthenticated, load, source])
  );

  const onEndReached = () => {
    if (hasMore && !loadingMore && !initialLoading && !refreshing) {
      load('more', filter, page + 1);
    }
  };

  const sourceSwitch = (
    <View style={styles.switchWrap}>
      <OrderSourceSwitch value={source} onChange={setSource} />
    </View>
  );

  const header = (
    <View>
      {sourceSwitch}
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.filters}
      >
        {FILTERS.map((f) => (
          <Chip
            key={f.key}
            label={f.label}
            size="sm"
            selected={filter === f.key}
            onPress={() => setFilter(f.key)}
          />
        ))}
      </ScrollView>
    </View>
  );

  const renderBody = () => {
    if (!isAuthenticated) {
      return (
        <EmptyState
          icon="🔐"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อดูและติดตามคำสั่งซื้อของคุณ"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      );
    }

    if (source === 'fresh') {
      return <FreshOrdersList header={sourceSwitch} />;
    }

    if (initialLoading && orders.length === 0) {
      return (
        <View style={styles.center}>
          {header}
          <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
        </View>
      );
    }

    if (errorMessage && orders.length === 0) {
      return (
        <View style={styles.flex}>
          {header}
          <EmptyState variant="error" message={errorMessage} onAction={() => load('initial', filter, 1)} />
        </View>
      );
    }

    return (
      <FlatList
        data={orders}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => <OrderRow order={item} />}
        ListHeaderComponent={header}
        ListEmptyComponent={
          <EmptyState
            compact
            icon="🧾"
            title={filter === 'all' ? 'ยังไม่มีคำสั่งซื้อ' : 'ไม่มีคำสั่งซื้อในสถานะนี้'}
            message="ลองเลือกของดีจากร้านค้าหรือตลาดสดดูไหม"
            actionLabel="ไปช้อปเลย"
            onAction={() => router.push('/(tabs)/shop' as never)}
          />
        }
        ListFooterComponent={
          loadingMore ? <ActivityIndicator color={colors.gold} style={styles.footerLoader} /> : null
        }
        contentContainerStyle={styles.list}
        onEndReached={onEndReached}
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
    <Screen title="คำสั่งซื้อ" showBack={!embedded} scroll={false}>
      {renderBody()}
    </Screen>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    flex: 1,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  filters: {
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.md,
    gap: spacing.sm,
  },
  switchWrap: {
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.md,
  },
  list: {
    paddingBottom: spacing.xxxl,
  },
  card: {
    marginHorizontal: spacing.screen,
    marginBottom: spacing.md,
  },
  rowTop: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.sm,
  },
  unread: {
    width: 10,
    height: 10,
    borderRadius: 5,
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
  thumbEmpty: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  thumbIcon: {
    fontSize: 24,
  },
  rowBottom: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: spacing.md,
    paddingTop: spacing.sm,
    borderTopWidth: 1,
  },
  footerLoader: {
    marginVertical: spacing.lg,
  },
});

export default MyOrdersScreen;
