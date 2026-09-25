/**
 * ออเดอร์ร้านตลาดสด — GET /fresh-market/seller/orders?status= + POST /seller/orders/{id}/action
 *
 * - รีเฟรชเงียบๆ ทุก 15 วินาทีเฉพาะตอนเปิดหน้านี้ (หยุดเมื่อออกจากหน้า) + รีเฟรชทันทีเมื่อมีแจ้งเตือนออเดอร์
 * - ปุ่มตาม allowed_actions: รับออเดอร์ → เริ่มเตรียม → พร้อมส่ง/พร้อมรับ → ส่งมอบ (มารับเอง) · ยกเลิกต้องใส่เหตุผล
 * - กันกดซ้ำต่อออเดอร์ (กดสองปุ่มของออเดอร์เดียวกันพร้อมกันไม่ได้)
 * - ?status= เลือกแท็บ · ?focus=<id> ไฮไลต์ออเดอร์ที่มาจากแจ้งเตือน
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, FlatList, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  fmSellerOrderAction,
  getFmSellerDashboard,
  getFmSellerOrder,
  getFmSellerOrders,
  type FmOrderFilter,
  type FmSellerAction,
  type FmSellerOrder,
} from '@/services/api/taladsodSellerApi';
import { addNotificationReceivedListener } from '@/services/notifications';
import { Card3D, Chip, EmptyState, Screen, WebsiteButton, formatBaht, resultHaptic } from '@/components/ui';
import { FormSheet, Field } from '@/components/shop';
import {
  FM_CANCEL_REASONS,
  FM_ORDER_FILTERS,
  FmOrderCard,
  MerchantModeSwitch,
  fmActionLook,
  isFmOrderFilter,
} from '@/components/merchant';
import { useTheme, spacing, typography, type Tone } from '@/theme';

const POLL_MS = 15_000;
const NOTICE_MS = 4_000;

const toId = (value: unknown): number | null => {
  const n = Number(value);
  return Number.isInteger(n) && n > 0 ? n : null;
};

/** ถามยืนยันแบบ Promise (Alert ของระบบ) */
const confirmAsync = (title: string, message: string, okLabel: string): Promise<boolean> =>
  new Promise((resolve) => {
    Alert.alert(
      title,
      message,
      [
        { text: 'ยังก่อน', style: 'cancel', onPress: () => resolve(false) },
        { text: okLabel, onPress: () => resolve(true) },
      ],
      { cancelable: true, onDismiss: () => resolve(false) }
    );
  });

export default function TaladsodSellerOrdersScreen() {
  const { colors } = useTheme();
  const params = useLocalSearchParams<{ status?: string; focus?: string }>();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const focusId = toId(params.focus);

  const [filter, setFilter] = useState<FmOrderFilter>(isFmOrderFilter(params.status) ? params.status : 'all');
  const [orders, setOrders] = useState<FmSellerOrder[]>([]);
  const [counts, setCounts] = useState<Record<string, number>>({});
  const [hasMore, setHasMore] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notSeller, setNotSeller] = useState(false);
  const [focusOrder, setFocusOrder] = useState<FmSellerOrder | null>(null);
  const [notice, setNotice] = useState<{ tone: Tone; text: string } | null>(null);
  const [now, setNow] = useState(Date.now());

  const [cancelTarget, setCancelTarget] = useState<FmSellerOrder | null>(null);
  const [cancelChoice, setCancelChoice] = useState<string | null>(null);
  const [cancelText, setCancelText] = useState('');
  const [cancelBusy, setCancelBusy] = useState(false);

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);
  const pageRef = useRef(1);
  const actingRef = useRef(new Set<number>());
  const noticeTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      if (noticeTimerRef.current) clearTimeout(noticeTimerRef.current);
    };
  }, []);

  useEffect(() => {
    if (isFmOrderFilter(params.status)) setFilter(params.status);
  }, [params.status]);

  const showNotice = useCallback((tone: Tone, text: string) => {
    if (!mountedRef.current) return;
    if (noticeTimerRef.current) clearTimeout(noticeTimerRef.current);
    setNotice({ tone, text });
    noticeTimerRef.current = setTimeout(() => mountedRef.current && setNotice(null), NOTICE_MS);
  }, []);

  // ---------- โหลด ----------
  const loadCounts = useCallback(async () => {
    const res = await getFmSellerDashboard();
    if (mountedRef.current && res.success) setCounts(res.data.stats.orders_by_status);
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'more' | 'silent', target: FmOrderFilter, page: number) => {
      if (!isAuthenticated) {
        setInitialLoading(false);
        return;
      }
      const requestId = ++requestIdRef.current;
      if (mode === 'initial') setInitialLoading(true);
      if (mode === 'refresh') setRefreshing(true);
      if (mode === 'more') setLoadingMore(true);

      if (page === 1) loadCounts().catch(() => {});
      const res = await getFmSellerOrders({ status: target, page });
      if (!mountedRef.current || requestId !== requestIdRef.current) return;

      if (res.success) {
        const list = res.data.orders;
        setOrders((prev) => {
          if (mode !== 'more') return list;
          const seen = new Set(prev.map((o) => o.id));
          return [...prev, ...list.filter((o) => !seen.has(o.id))];
        });
        pageRef.current = page;
        setHasMore(res.data.pagination.current_page < res.data.pagination.last_page);
        setError(null);
        setNotSeller(false);
      } else if (res.code === 'NOT_SELLER') {
        setNotSeller(true);
      } else if (mode === 'initial' || mode === 'refresh') {
        setError(res.message);
      }

      setInitialLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    },
    [isAuthenticated, loadCounts]
  );

  useEffect(() => {
    setOrders([]);
    pageRef.current = 1;
    load('initial', filter, 1);
  }, [filter, load]);

  // ออเดอร์จากแจ้งเตือน
  useEffect(() => {
    if (!focusId || !isAuthenticated) {
      setFocusOrder(null);
      return;
    }
    let alive = true;
    getFmSellerOrder(focusId).then((res) => {
      if (alive && mountedRef.current && res.success) setFocusOrder(res.data);
    });
    return () => {
      alive = false;
    };
  }, [focusId, isAuthenticated]);

  // เปิดหน้า: รีเฟรชทุก 15 วิ (เฉพาะหน้าแรก) + ฟังแจ้งเตือนออเดอร์ · ออกจากหน้า = หยุด
  useFocusEffect(
    useCallback(() => {
      setNow(Date.now());
      const timer = setInterval(() => {
        setNow(Date.now());
        if (pageRef.current === 1) load('silent', filter, 1);
      }, POLL_MS);
      const sub = addNotificationReceivedListener((notification) => {
        const data = notification?.request?.content?.data as Record<string, unknown> | undefined;
        if (data?.type === 'fresh_market_order') {
          if (pageRef.current === 1) load('silent', filter, 1);
          else loadCounts().catch(() => {});
        }
      });
      return () => {
        clearInterval(timer);
        sub.remove();
      };
    }, [filter, load, loadCounts])
  );

  // ---------- การกระทำ ----------
  const applyUpdated = useCallback(
    (updated: FmSellerOrder) => {
      setOrders((prev) => {
        const next = prev.map((o) => (o.id === updated.id ? updated : o));
        // แท็บที่กรองสถานะ → ออเดอร์ที่ย้ายสถานะแล้วหลุดจากแท็บนี้
        return filter === 'all' ? next : next.filter((o) => o.id !== updated.id || o.order_status === filter);
      });
      setFocusOrder((prev) => (prev && prev.id === updated.id ? updated : prev));
    },
    [filter]
  );

  const runAction = async (order: FmSellerOrder, action: FmSellerAction, reason?: string): Promise<boolean> => {
    if (actingRef.current.has(order.id)) return false;
    actingRef.current.add(order.id);
    try {
      const res = await fmSellerOrderAction(order.id, action, reason);
      if (!mountedRef.current) return false;
      if (res.success) {
        resultHaptic('success');
        applyUpdated(res.data);
        showNotice('success', res.message || fmActionLook(action, order.delivery_type).done);
        loadCounts().catch(() => {});
        return true;
      }
      resultHaptic('error');
      Alert.alert('ทำรายการไม่สำเร็จ', res.message);
      if (['INVALID_TRANSITION', 'CONFLICT', 'ORDER_NOT_FOUND', 'VALIDATION_ERROR'].includes(res.code)) {
        load('silent', filter, 1);
      }
      return false;
    } finally {
      actingRef.current.delete(order.id);
    }
  };

  const handleAction = async (order: FmSellerOrder, action: FmSellerAction) => {
    if (action === 'cancel') {
      setCancelChoice(null);
      setCancelText('');
      setCancelTarget(order);
      return;
    }
    if (action === 'handover' && order.payment_method === 'cod') {
      const ok = await confirmAsync(
        'ส่งมอบและรับเงินแล้ว?',
        `ยืนยันว่าลูกค้ารับของและจ่ายเงินสด ${formatBaht(order.grand_total || order.total_amount)} แล้ว`,
        'ยืนยัน'
      );
      if (!ok) return;
    }
    await runAction(order, action);
  };

  const cancelReason = (cancelChoice === 'อื่นๆ' ? cancelText : cancelChoice || cancelText).trim();

  const submitCancel = async () => {
    if (!cancelTarget || cancelBusy) return;
    if (cancelReason.length < 2) return;
    setCancelBusy(true);
    const ok = await runAction(cancelTarget, 'cancel', cancelReason.slice(0, 500));
    if (!mountedRef.current) return;
    setCancelBusy(false);
    if (ok) setCancelTarget(null);
  };

  // ---------- แสดงผล ----------
  const focusInList = !!focusOrder && orders.some((o) => o.id === focusOrder.id);

  const header = (
    <View>
      <MerchantModeSwitch current="taladsod" style={styles.mode} />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filters}>
        {FM_ORDER_FILTERS.map((f) => (
          <Chip
            key={f.key}
            label={f.label}
            size="sm"
            selected={filter === f.key}
            count={f.key === 'all' ? undefined : counts[f.key] || undefined}
            tone={f.key === 'pending' && (counts.pending || 0) > 0 ? 'warning' : 'neutral'}
            onPress={() => setFilter(f.key)}
          />
        ))}
      </ScrollView>
      {!!notice && (
        <Card3D variant="flat" padding={spacing.md} style={styles.notice}>
          <Text
            accessibilityLiveRegion="polite"
            style={[typography.bodySm, { color: notice.tone === 'success' ? colors.success : colors.warning }]}
          >
            {notice.text}
          </Text>
        </Card3D>
      )}
      {!!focusOrder && !focusInList && (
        <View>
          <Text style={[typography.caption, styles.focusLabel, { color: colors.textMuted }]}>ออเดอร์จากแจ้งเตือน</Text>
          <FmOrderCard order={focusOrder} onAction={handleAction} highlighted now={now} />
        </View>
      )}
    </View>
  );

  const renderEmpty = () => {
    if (initialLoading) return <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />;
    if (error) return <EmptyState compact variant="error" message={error} onAction={() => load('refresh', filter, 1)} />;
    return (
      <EmptyState
        compact
        icon={filter === 'pending' ? '🔔' : '🧾'}
        title={filter === 'pending' ? 'ยังไม่มีออเดอร์ใหม่' : 'ยังไม่มีออเดอร์ในแท็บนี้'}
        message="ออเดอร์ใหม่จะเด้งแจ้งเตือน และหน้านี้อัปเดตเองทุก 15 วินาที"
      />
    );
  };

  if (!isAuthenticated) {
    return (
      <Screen title="ออเดอร์ตลาดสด" scroll={false}>
        <EmptyState icon="🔐" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (notSeller) {
    return (
      <Screen title="ออเดอร์ตลาดสด" scroll={false} contentStyle={styles.pad}>
        <EmptyState icon="🥬" title="ยังไม่มีร้านในตลาดสด" message="สมัครขายบนเว็บไซต์ แล้วกลับมารับออเดอร์ในแอปได้เลย" />
        <WebsiteButton path="/taladsod/register-seller" label="สมัครขายในตลาดสด" variant="primary" fullWidth />
      </Screen>
    );
  }

  return (
    <Screen title="ออเดอร์ตลาดสด" subtitle="อัปเดตเองทุก 15 วินาที" scroll={false}>
      <FlatList
        data={orders}
        keyExtractor={(item) => String(item.id)}
        ListHeaderComponent={header}
        ListEmptyComponent={renderEmpty()}
        renderItem={({ item }) => (
          <FmOrderCard order={item} onAction={handleAction} highlighted={item.id === focusId} now={now} />
        )}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        onEndReachedThreshold={0.4}
        onEndReached={() => {
          if (hasMore && !loadingMore && !initialLoading) load('more', filter, pageRef.current + 1);
        }}
        ListFooterComponent={loadingMore ? <ActivityIndicator color={colors.gold} style={styles.more} /> : null}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load('refresh', filter, 1)}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
          />
        }
      />

      <FormSheet
        visible={!!cancelTarget}
        icon="✖️"
        title="ยกเลิกออเดอร์นี้?"
        description={
          cancelTarget
            ? `${cancelTarget.order_number} · ลูกค้าจะได้รับแจ้งเตือนพร้อมเหตุผล${cancelTarget.payment_method !== 'cod' ? ' และได้เงินคืนเข้ากระเป๋า' : ''}`
            : undefined
        }
        submitLabel="ยืนยันยกเลิก"
        submitVariant="danger"
        submitDisabled={cancelReason.length < 2}
        onSubmit={submitCancel}
        busy={cancelBusy}
        cancelLabel="ไม่ยกเลิก"
        onClose={() => setCancelTarget(null)}
      >
        <Text style={[typography.caption, styles.reasonLabel, { color: colors.textMuted }]}>เหตุผล</Text>
        <View style={styles.reasons}>
          {[...FM_CANCEL_REASONS, 'อื่นๆ'].map((r) => (
            <Chip key={r} label={r} size="sm" selected={cancelChoice === r} onPress={() => setCancelChoice(r)} />
          ))}
        </View>
        {cancelChoice === 'อื่นๆ' && (
          <Field
            label="พิมพ์เหตุผล"
            value={cancelText}
            onChangeText={setCancelText}
            placeholder="บอกลูกค้าสั้นๆ ว่าทำไมต้องยกเลิก"
            maxLength={500}
            multiline
          />
        )}
      </FormSheet>
    </Screen>
  );
}

const styles = StyleSheet.create({
  list: {
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.xxxl * 2,
  },
  pad: {
    paddingHorizontal: spacing.screen,
  },
  mode: {
    marginBottom: spacing.md,
  },
  filters: {
    gap: spacing.xs,
    paddingBottom: spacing.md,
  },
  notice: {
    marginBottom: spacing.md,
  },
  focusLabel: {
    marginBottom: spacing.xs,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  more: {
    marginVertical: spacing.lg,
  },
  reasonLabel: {
    marginTop: spacing.md,
    marginBottom: spacing.xs,
  },
  reasons: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
  },
});
