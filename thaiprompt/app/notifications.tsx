/**
 * การแจ้งเตือน — ธีมนวลทองคำ
 *
 * - แท็บ "ทั้งหมด / ยังไม่อ่าน" · แตะ = อ่านแล้ว + เปิดหน้าที่เกี่ยวข้อง (ผ่าน allowlist เดียวกับ push)
 * - กดค้าง = ลบ (ถามก่อน) · "อ่านทั้งหมด" ด้านบน
 * - แจ้งเตือนระบบเครือข่าย (คอมมิชชั่น/สายงาน/rank) ไม่แสดงในแอป (นโยบาย Google Play) — ดูได้บนเว็บ
 * - โหลดไม่สำเร็จ = หน้าลองใหม่ (ไม่ค้างหน้าว่าง) · ออกจากหน้าระหว่างโหลดไม่ setState
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  deleteNotification,
  getNotifications,
  markAllNotificationsRead,
  markNotificationRead,
  type Notification,
} from '@/services/api';
import { isRestrictedNotification } from '@/utils/storePolicy';
import { routeForNotification } from '@/utils/notificationRouting';
import { Button3D, Card3D, Chip, EmptyState, Screen, resultHaptic } from '@/components/ui';
import { useTheme, radii, spacing, toneColors, typography, type Tone } from '@/theme';

const TYPE_LOOK: Record<string, { emoji: string; tone: Tone }> = {
  general: { emoji: '🔔', tone: 'gold' },
  order: { emoji: '🧾', tone: 'success' },
  shop_order: { emoji: '🧾', tone: 'success' },
  fresh_market_order: { emoji: '🥬', tone: 'success' },
  fresh_market_shop: { emoji: '🛒', tone: 'warning' },
  fresh_market_shop_open: { emoji: '🛒', tone: 'success' },
  delivery_update: { emoji: '🛵', tone: 'info' },
  rider: { emoji: '🛵', tone: 'info' },
  rider_job_offer: { emoji: '🛵', tone: 'info' },
  rider_job_update: { emoji: '🛵', tone: 'info' },
  wallet: { emoji: '👛', tone: 'gold' },
  promotion: { emoji: '🎁', tone: 'warning' },
  system: { emoji: '⚙️', tone: 'neutral' },
  ticket: { emoji: '💬', tone: 'info' },
};

type Filter = 'all' | 'unread';

/** path ที่แจ้งเตือนนี้พาไป (null = ไม่มีหน้าเฉพาะ) */
const targetOf = (n: Notification): string | null => {
  const data = { ...(n.data || {}) } as Record<string, unknown>;
  if (!data.type) data.type = n.type;
  if (!data.url && n.actionUrl) data.url = n.actionUrl;
  const path = routeForNotification(data);
  return path === '/notifications' ? null : path;
};

const NotificationRow: React.FC<{
  item: Notification;
  onPress: () => unknown;
  onLongPress: () => void;
}> = ({ item, onPress, onLongPress }) => {
  const { colors } = useTheme();
  const look = TYPE_LOOK[item.type] || TYPE_LOOK.general;
  const t = toneColors(look.tone, colors);
  const unread = !item.isRead;

  return (
    <Card3D
      onPress={onPress}
      onLongPress={onLongPress}
      padding={spacing.md}
      radius={radii.lg}
      shadow={unread ? 'md' : 'sm'}
      gradientBorder={unread}
      style={styles.card}
      accessibilityLabel={`${unread ? 'ยังไม่อ่าน ' : ''}${item.title} ${item.body}`}
      accessibilityHint="แตะเพื่อเปิด กดค้างเพื่อลบ"
    >
      <View style={styles.row}>
        <View style={[styles.icon, { backgroundColor: t.bg }]}>
          <Text style={styles.emoji}>{look.emoji}</Text>
        </View>
        <View style={styles.flex}>
          <View style={styles.titleRow}>
            <Text
              numberOfLines={1}
              style={[typography.bodyStrong, styles.flex, { color: unread ? colors.textStrong : colors.textMuted }]}
            >
              {item.title}
            </Text>
            {unread && <View style={[styles.dot, { backgroundColor: colors.gold }]} />}
          </View>
          {!!item.body && (
            <Text numberOfLines={3} style={[typography.bodySm, { color: unread ? colors.text : colors.textMuted }]}>
              {item.body}
            </Text>
          )}
          <Text style={[typography.micro, styles.time, { color: colors.textFaint }]}>
            {item.timeAgo || item.typeText || ''}
          </Text>
        </View>
      </View>
    </Card3D>
  );
};

export default function NotificationsScreen() {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [items, setItems] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [filter, setFilter] = useState<Filter>('all');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);
  const markAllBusyRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'silent') => {
      if (!isAuthenticated) {
        setLoading(false);
        return;
      }
      const requestId = ++requestIdRef.current;
      if (mode === 'refresh') setRefreshing(true);
      const response = await getNotifications();
      if (!mountedRef.current || requestId !== requestIdRef.current) return;

      if (response?.success && response.data) {
        const all = Array.isArray(response.data.notifications) ? response.data.notifications : [];
        // นโยบาย Google Play: แจ้งเตือนระบบเครือข่ายดูได้บนเว็บเท่านั้น
        const visible = all.filter((n) => !isRestrictedNotification(n));
        const hiddenUnread = all.filter((n) => !n.isRead && isRestrictedNotification(n)).length;
        setItems(visible);
        setUnreadCount(Math.max(0, (Number(response.data.unreadCount) || 0) - hiddenUnread));
        setError(null);
      } else if (mode !== 'silent') {
        setError('โหลดการแจ้งเตือนไม่สำเร็จ ตรวจสอบอินเทอร์เน็ตแล้วลองใหม่นะ');
      }
      setLoading(false);
      setRefreshing(false);
    },
    [isAuthenticated]
  );

  // เปิดหน้า = ดึงใหม่ (ครั้งแรกแสดงตัวโหลด ครั้งต่อไปเงียบๆ)
  const loadedRef = useRef(false);
  useFocusEffect(
    useCallback(() => {
      load(loadedRef.current ? 'silent' : 'initial');
      loadedRef.current = true;
    }, [load])
  );

  const openItem = async (item: Notification) => {
    if (!item.isRead) {
      // อัปเดตบนจอทันที แล้วค่อยบอก server (ล้มเหลวก็ไม่เป็นไร รอบหน้าดึงใหม่)
      setItems((prev) => prev.map((n) => (n.id === item.id ? { ...n, isRead: true } : n)));
      setUnreadCount((c) => Math.max(0, c - 1));
      markNotificationRead(item.id).catch(() => {});
    }
    const target = targetOf(item);
    if (target) router.push(target as never);
  };

  const confirmDelete = (item: Notification) => {
    Alert.alert('ลบการแจ้งเตือนนี้?', item.title, [
      { text: 'ไม่ลบ', style: 'cancel' },
      {
        text: 'ลบ',
        style: 'destructive',
        onPress: async () => {
          const response = await deleteNotification(item.id);
          if (!mountedRef.current) return;
          if (response?.success) {
            resultHaptic('success');
            setItems((prev) => prev.filter((n) => n.id !== item.id));
            if (!item.isRead) setUnreadCount((c) => Math.max(0, c - 1));
          } else {
            Alert.alert('ลบไม่สำเร็จ', 'ลบการแจ้งเตือนไม่สำเร็จ ลองใหม่อีกครั้งนะ');
          }
        },
      },
    ]);
  };

  const markAll = async () => {
    if (unreadCount === 0 || markAllBusyRef.current) return;
    markAllBusyRef.current = true;
    const response = await markAllNotificationsRead();
    markAllBusyRef.current = false;
    if (!mountedRef.current) return;
    if (response?.success) {
      resultHaptic('success');
      setItems((prev) => prev.map((n) => ({ ...n, isRead: true })));
      setUnreadCount(0);
    } else {
      Alert.alert('ทำรายการไม่สำเร็จ', 'ลองใหม่อีกครั้งนะ');
    }
  };

  if (!isAuthenticated) {
    return (
      <Screen title="การแจ้งเตือน" scroll={false}>
        <EmptyState
          icon="🔔"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อดูการแจ้งเตือนออเดอร์ งานส่ง และกระเป๋าเงิน"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </Screen>
    );
  }

  const shown = filter === 'unread' ? items.filter((n) => !n.isRead) : items;

  return (
    <Screen
      title="การแจ้งเตือน"
      subtitle={unreadCount > 0 ? `ยังไม่อ่าน ${unreadCount.toLocaleString('th-TH')} รายการ` : 'อ่านครบแล้ว'}
      scroll={false}
      right={unreadCount > 0 ? <Button3D title="อ่านทั้งหมด" size="sm" variant="secondary" onPress={markAll} /> : undefined}
    >
      <FlatList
        data={shown}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        ListHeaderComponent={
          <View style={styles.filters}>
            <Chip label="ทั้งหมด" size="sm" selected={filter === 'all'} onPress={() => setFilter('all')} />
            <Chip
              label="ยังไม่อ่าน"
              size="sm"
              selected={filter === 'unread'}
              count={unreadCount || undefined}
              onPress={() => setFilter('unread')}
            />
          </View>
        }
        ListEmptyComponent={
          loading ? (
            <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
          ) : error ? (
            <EmptyState compact variant="error" message={error} onAction={() => load('initial')} />
          ) : (
            <EmptyState
              compact
              icon={filter === 'unread' ? '✅' : '🔕'}
              title={filter === 'unread' ? 'อ่านครบทุกรายการแล้ว' : 'ยังไม่มีการแจ้งเตือน'}
              message="ออเดอร์ งานส่ง และความเคลื่อนไหวของกระเป๋าเงินจะแจ้งที่นี่"
            />
          )
        }
        renderItem={({ item, index }) => (
          <Animated.View entering={index < 12 ? FadeInDown.delay(index * 25).duration(220) : undefined}>
            <NotificationRow item={item} onPress={() => openItem(item)} onLongPress={() => confirmDelete(item)} />
          </Animated.View>
        )}
        ListFooterComponent={
          items.length > 0 ? (
            <Text style={[typography.caption, styles.footer, { color: colors.textFaint }]}>กดค้างที่รายการเพื่อลบ</Text>
          ) : null
        }
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load('refresh')}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
          />
        }
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  list: {
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.xxxl * 2,
  },
  filters: {
    flexDirection: 'row',
    gap: spacing.xs,
    marginBottom: spacing.md,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  card: {
    marginBottom: spacing.sm,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  icon: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emoji: {
    fontSize: 22,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  dot: {
    width: 9,
    height: 9,
    borderRadius: 5,
  },
  time: {
    marginTop: spacing.xs,
  },
  footer: {
    textAlign: 'center',
    marginTop: spacing.md,
  },
});
