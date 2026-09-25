/**
 * FreshOrdersList — รายการออเดอร์ตลาดสดของฉัน (ใช้ในแท็บคำสั่งซื้อ + /taladsod/orders)
 *
 * - เปลี่ยนตัวกรองระหว่างโหลด → ทิ้งผลเก่า (requestId)
 * - กลับเข้าหน้า → รีเฟรชเงียบ · ดึงลง = รีเฟรช (ไม่บังจอ)
 * - ออเดอร์ที่ยังวิ่งอยู่ในหน้าแรก → รีเฟรชเงียบทุก 30 วินาทีระหว่างเปิดหน้า
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { router, useFocusEffect } from 'expo-router';
import { Card3D, Chip, EmptyState, Pill, PriceText } from '@/components/ui';
import { formatThaiDateTime } from '@/components/shop';
import { useTheme, radii, spacing, typography } from '@/theme';
import {
  FM_ORDER_ACTIVE_STATUSES,
  fmImageUri,
  getFmOrders,
  type FmOrder,
  type FmOrderStatus,
} from '@/services/api/taladsodApi';
import { FM_ORDER_TONE, fmOrderLabel } from './helpers';
import { useFocusedInterval, useMountedRef } from './hooks';

type Filter = 'all' | FmOrderStatus;

const FILTERS: Array<{ key: Filter; label: string }> = [
  { key: 'all', label: 'ทั้งหมด' },
  { key: 'pending', label: 'รอร้านรับ' },
  { key: 'accepted', label: 'ร้านรับแล้ว' },
  { key: 'preparing', label: 'กำลังทำ' },
  { key: 'ready', label: 'พร้อมแล้ว' },
  { key: 'delivering', label: 'กำลังส่ง' },
  { key: 'delivered', label: 'ส่งถึงแล้ว' },
  { key: 'completed', label: 'สำเร็จ' },
  { key: 'cancelled', label: 'ยกเลิก' },
];

const FALLBACK_FOOD = require('@/assets/images/taladsod/krapao-hero.webp');

export const FmOrderRow: React.FC<{ order: FmOrder }> = ({ order }) => {
  const { colors } = useTheme();
  const first = order.items[0];
  const image = fmImageUri(first?.image_url);
  const title = order.items_summary || first?.title || 'ออเดอร์ตลาดสด';

  return (
    <Card3D
      onPress={() => router.push(`/taladsod/order/${order.id}` as never)}
      padding={spacing.md}
      radius={radii.lg}
      shadow="sm"
      style={styles.card}
      accessibilityLabel={`ออเดอร์ตลาดสด ${order.order_number} ${fmOrderLabel(order)}`}
    >
      <View style={styles.rowTop}>
        <View style={styles.flex}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>
            🥬 {order.seller?.shop_name || 'ตลาดสด'} · {order.order_number}
          </Text>
          <Text style={[typography.micro, { color: colors.textFaint }]}>{formatThaiDateTime(order.created_at)}</Text>
        </View>
        <Pill label={fmOrderLabel(order)} tone={FM_ORDER_TONE[order.order_status] || 'neutral'} />
      </View>

      <View style={styles.rowItem}>
        <Image
          source={image ? { uri: image } : FALLBACK_FOOD}
          style={[styles.thumb, { backgroundColor: colors.inset }]}
          contentFit="cover"
          transition={140}
        />
        <View style={styles.flex}>
          <Text numberOfLines={2} style={[typography.bodyStrong, { color: colors.textStrong }]}>
            {title}
          </Text>
          {!!first?.options_label && order.items.length === 1 && (
            <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
              {first.options_label}
            </Text>
          )}
        </View>
      </View>

      <View style={[styles.rowBottom, { borderTopColor: colors.divider }]}>
        <Text style={[typography.caption, { color: colors.textMuted }]}>
          {order.delivery_type === 'rider' ? '🛵 ไรเดอร์ส่ง' : '🛍️ นัดรับที่ร้าน'} · {order.items_count} ชิ้น
        </Text>
        <PriceText amount={order.grand_total} size="md" tone="gold" />
      </View>
    </Card3D>
  );
};

export interface FreshOrdersListProps {
  /** ส่วนหัวเพิ่มเติมด้านบนตัวกรอง (เช่น ปุ่มสลับร้านค้า/ตลาดสด) */
  header?: React.ReactNode;
}

export const FreshOrdersList: React.FC<FreshOrdersListProps> = ({ header }) => {
  const { colors } = useTheme();
  const mountedRef = useMountedRef();
  const [filter, setFilter] = useState<Filter>('all');
  const [orders, setOrders] = useState<FmOrder[]>([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const requestIdRef = useRef(0);
  const loadedOnceRef = useRef(false);
  const filterRef = useRef<Filter>(filter);
  filterRef.current = filter;

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'silent' | 'more', targetFilter: Filter, targetPage: number) => {
      const requestId = ++requestIdRef.current;
      if (mode === 'initial') setInitialLoading(true);
      if (mode === 'refresh') setRefreshing(true);
      if (mode === 'more') setLoadingMore(true);

      const res = await getFmOrders({ status: targetFilter, page: targetPage });
      if (!mountedRef.current || requestId !== requestIdRef.current) return;

      if (res.success) {
        setOrders((prev) => (mode === 'more' ? [...prev, ...res.data.orders] : res.data.orders));
        setPage(targetPage);
        setHasMore(!!res.data.pagination?.has_more);
        setErrorMessage(null);
        loadedOnceRef.current = true;
      } else if (mode !== 'more' && mode !== 'silent') {
        setErrorMessage(res.message);
      }
      setInitialLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    },
    [mountedRef]
  );

  useEffect(() => {
    setOrders([]);
    load('initial', filter, 1);
  }, [filter, load]);

  useFocusEffect(
    useCallback(() => {
      if (loadedOnceRef.current) load('silent', filterRef.current, 1);
    }, [load])
  );

  const hasActive = page === 1 && orders.some((o) => FM_ORDER_ACTIVE_STATUSES.includes(o.order_status));
  useFocusedInterval(() => load('silent', filterRef.current, 1), 30000, hasActive && !loadingMore);

  const listHeader = (
    <View>
      {header}
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filters}>
        {FILTERS.map((f) => (
          <Chip key={f.key} label={f.label} size="sm" selected={filter === f.key} onPress={() => setFilter(f.key)} />
        ))}
      </ScrollView>
    </View>
  );

  if (initialLoading && orders.length === 0) {
    return (
      <View style={styles.flex}>
        {listHeader}
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </View>
    );
  }

  if (errorMessage && orders.length === 0) {
    return (
      <View style={styles.flex}>
        {listHeader}
        <EmptyState variant="error" message={errorMessage} onAction={() => load('initial', filter, 1)} />
      </View>
    );
  }

  return (
    <FlatList
      data={orders}
      keyExtractor={(item) => String(item.id)}
      renderItem={({ item }) => <FmOrderRow order={item} />}
      ListHeaderComponent={listHeader}
      ListEmptyComponent={
        <EmptyState
          compact
          icon="🥬"
          title={filter === 'all' ? 'ยังไม่มีออเดอร์ตลาดสด' : 'ไม่มีออเดอร์ในสถานะนี้'}
          message="ผัดกะเพราร้อนๆ จากร้านใกล้บ้านรออยู่นะ"
          actionLabel="ไปตลาดสด"
          onAction={() => router.push('/taladsod' as never)}
        />
      }
      ListFooterComponent={loadingMore ? <ActivityIndicator color={colors.gold} style={styles.footerLoader} /> : null}
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

const styles = StyleSheet.create({
  flex: {
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

export default FreshOrdersList;
