/**
 * ตะกร้าสินค้า — ตะกร้าบน server เป็นข้อมูลหลัก (SHOP-03 / SHOP-23) ธีมรอยัล
 *
 * หน้าตา: การ์ดขาวแยกตามร้าน (หัวร้าน + แถวสินค้าคั่นเส้น รูปมุมมน ปุ่มจำนวน) · กล่องสรุปยอดแบบยุบ
 *         · แถบล่างลอย "ยอดรวม" ทอง + ปุ่มทอง "ไปชำระเงิน"
 * - ทุกการแก้ไขเรียก API แล้วแทนตะกร้าทั้งใบด้วยค่าที่ server ตอบ (cartStore)
 * - กด +/− รัวๆ → รวมเป็นคำสั่งเดียวหลังหยุดกด 450ms (ไม่ยิง API ทุกครั้งที่กด)
 * - ราคา/ค่าส่ง/ยอดรวม มาจาก server เท่านั้น — ไม่มี PV/คอมมิชชั่น
 * - สินค้าที่สั่งไม่ได้ (หมด/ปิดขาย) ต้องเอาออกก่อนชำระเงิน เพราะ checkout ตรวจทั้งตะกร้า
 * - ลบสินค้า / ล้างตะกร้า ต้องยืนยันก่อนเสมอ
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { router, useFocusEffect } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { useCartStore, type CartItem } from '@/stores/cartStore';
import { Button3D, Card3D, EmptyState, Icon, Pill, PriceText, Screen, resultHaptic } from '@/components/ui';
import { IconTile, MetaItem, QuantityStepper, StickyBar, ThumbImage } from '@/components/shop';
import { useTheme, spacing, typography } from '@/theme';

const QTY_DEBOUNCE_MS = 450;

interface ItemRowProps {
  item: CartItem;
  quantity: number;
  busy: boolean;
  onQuantity: (next: number) => void;
  onRemove: () => void;
}

const ItemRow: React.FC<ItemRowProps> = ({ item, quantity, busy, onQuantity, onRemove }) => {
  const { colors } = useTheme();
  const hasDiscount = item.original_price !== null && item.original_price > item.unit_price;
  const maxQty = Math.max(1, Math.min(99, item.max_quantity || 99));

  return (
    <View style={[styles.itemRow, !item.is_available && styles.dimmed]}>
      <Pressable
        onPress={() => router.push(`/product/${item.product_id}` as never)}
        accessibilityRole="button"
        accessibilityLabel={`ดูสินค้า ${item.name}`}
      >
        <ThumbImage uri={item.image} size={80} radius={18} transition={120} />
      </Pressable>

      <View style={styles.flex}>
        <View style={styles.itemTop}>
          <Text numberOfLines={2} style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]}>
            {item.name}
          </Text>
          <Pressable
            onPress={onRemove}
            disabled={busy}
            accessibilityRole="button"
            accessibilityLabel={`ลบ ${item.name} ออกจากตะกร้า`}
            hitSlop={10}
            style={({ pressed }) => [styles.removeButton, { backgroundColor: colors.inset, opacity: busy ? 0.4 : pressed ? 0.6 : 1 }]}
          >
            <Icon name="trash" size={15} color={colors.textMuted} />
          </Pressable>
        </View>

        <View style={styles.priceRow}>
          <PriceText amount={item.unit_price} size="sm" tone="gold" />
          {hasDiscount && <PriceText amount={item.original_price} size="xs" tone="muted" strike bold={false} />}
        </View>

        {!item.is_available ? (
          <MetaItem
            icon="warning-circle"
            text={item.unavailable_reason || 'สินค้านี้สั่งซื้อไม่ได้ตอนนี้'}
            color={colors.danger}
            style={styles.unavailable}
          />
        ) : (
          <View style={styles.qtyRow}>
            <QuantityStepper value={quantity} min={1} max={maxQty} size="sm" onChange={onQuantity} busy={busy} />
            <PriceText amount={item.unit_price * quantity} size="md" tone="strong" />
          </View>
        )}
        {item.is_available && item.stock !== null && item.stock <= 5 && (
          <MetaItem icon="warning" text={`เหลือเพียง ${item.stock} ชิ้น`} color={colors.warning} style={styles.lowStock} />
        )}
      </View>
    </View>
  );
};

export default function CartScreen() {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const cart = useCartStore((s) => s.cart);
  const loading = useCartStore((s) => s.loading);
  const loadError = useCartStore((s) => s.error);

  const [refreshing, setRefreshing] = useState(false);
  /** จำนวนที่ผู้ใช้กดไว้แต่ยังไม่ได้ส่ง/ยังไม่ตอบกลับ */
  const [pendingQty, setPendingQty] = useState<Record<number, number>>({});
  const [busyItems, setBusyItems] = useState<Record<number, boolean>>({});
  const [clearing, setClearing] = useState(false);

  const mountedRef = useRef(true);
  const timersRef = useRef<Record<number, ReturnType<typeof setTimeout>>>({});

  useEffect(() => {
    mountedRef.current = true;
    const timers = timersRef.current;
    return () => {
      mountedRef.current = false;
      Object.values(timers).forEach(clearTimeout);
    };
  }, []);

  // กลับมาหน้านี้ → โหลดตะกร้าล่าสุด (ครั้งแรกแสดงวงหมุน ครั้งถัดไปเงียบๆ)
  useFocusEffect(
    useCallback(() => {
      if (isAuthenticated) {
        useCartStore.getState().refresh();
      }
    }, [isAuthenticated])
  );

  const onRefresh = async () => {
    setRefreshing(true);
    await useCartStore.getState().refresh();
    if (mountedRef.current) setRefreshing(false);
  };

  const setBusy = (itemId: number, busy: boolean) =>
    setBusyItems((prev) => {
      const next = { ...prev };
      if (busy) next[itemId] = true;
      else delete next[itemId];
      return next;
    });

  const clearPending = (itemId: number) =>
    setPendingQty((prev) => {
      if (!(itemId in prev)) return prev;
      const next = { ...prev };
      delete next[itemId];
      return next;
    });

  /** เปลี่ยนจำนวน: แสดงทันที แล้วส่งค่าสุดท้ายหลังหยุดกด */
  const changeQuantity = (item: CartItem, next: number) => {
    setPendingQty((prev) => ({ ...prev, [item.id]: next }));
    if (timersRef.current[item.id]) clearTimeout(timersRef.current[item.id]);
    timersRef.current[item.id] = setTimeout(async () => {
      delete timersRef.current[item.id];
      if (!mountedRef.current) return;
      setBusy(item.id, true);
      const result = await useCartStore.getState().setQuantity(item.id, next);
      if (!mountedRef.current) return;
      setBusy(item.id, false);
      // ถ้าผู้ใช้กดต่อระหว่างรอ อย่าล้างค่าที่กดใหม่
      setPendingQty((prev) => {
        if (prev[item.id] !== next) return prev;
        const copy = { ...prev };
        delete copy[item.id];
        return copy;
      });
      if (!result.success) {
        resultHaptic('error');
        clearPending(item.id);
        Alert.alert('แก้จำนวนไม่สำเร็จ', result.message);
      }
    }, QTY_DEBOUNCE_MS);
  };

  const removeItem = (item: CartItem) => {
    Alert.alert('ลบสินค้าออกจากตะกร้า?', item.name, [
      { text: 'ไม่ลบ', style: 'cancel' },
      {
        text: 'ลบออก',
        style: 'destructive',
        onPress: async () => {
          if (timersRef.current[item.id]) {
            clearTimeout(timersRef.current[item.id]);
            delete timersRef.current[item.id];
          }
          setBusy(item.id, true);
          const result = await useCartStore.getState().remove(item.id);
          if (!mountedRef.current) return;
          setBusy(item.id, false);
          clearPending(item.id);
          if (!result.success) {
            resultHaptic('error');
            Alert.alert('ลบไม่สำเร็จ', result.message);
          }
        },
      },
    ]);
  };

  const clearAll = () => {
    if (clearing) return;
    Alert.alert('ล้างตะกร้าทั้งหมด?', 'สินค้าทุกชิ้นในตะกร้าจะถูกนำออก', [
      { text: 'ไม่ล้าง', style: 'cancel' },
      {
        text: 'ล้างตะกร้า',
        style: 'destructive',
        onPress: async () => {
          setClearing(true);
          const result = await useCartStore.getState().clear();
          if (!mountedRef.current) return;
          setClearing(false);
          setPendingQty({});
          if (!result.success) {
            Alert.alert('ล้างตะกร้าไม่สำเร็จ', result.message);
          }
        },
      },
    ]);
  };

  const items = cart?.items ?? [];
  const availableItems = items.filter((i) => i.is_available);
  const unavailableItems = items.filter((i) => !i.is_available);
  const hasPending = Object.keys(pendingQty).length > 0 || Object.keys(busyItems).length > 0;

  /** รายการที่สั่งได้ จัดกลุ่มตามร้าน (ลำดับตาม cart.stores จาก server) */
  const groups = useMemo(() => {
    const byStore = new Map<string, { key: string; name: string; items: CartItem[]; subtotal: number | null; riderAvailable: boolean }>();
    for (const store of cart?.stores ?? []) {
      byStore.set(String(store.store_id ?? 'none'), {
        key: store.key,
        name: store.store_name,
        items: [],
        subtotal: store.subtotal,
        riderAvailable: store.rider.available,
      });
    }
    for (const item of availableItems) {
      const key = String(item.store?.id ?? 'none');
      if (!byStore.has(key)) {
        byStore.set(key, { key, name: item.store?.name || 'ร้านค้า', items: [], subtotal: null, riderAvailable: false });
      }
      byStore.get(key)!.items.push(item);
    }
    return Array.from(byStore.values()).filter((g) => g.items.length > 0);
  }, [cart?.stores, availableItems]);

  const removeUnavailable = () => {
    Alert.alert(
      'เอาสินค้าที่สั่งไม่ได้ออก?',
      `มี ${unavailableItems.length} รายการที่หมดหรือปิดขาย ต้องเอาออกก่อนจึงชำระเงินได้`,
      [
        { text: 'ไว้ก่อน', style: 'cancel' },
        {
          text: 'เอาออก',
          style: 'destructive',
          onPress: async () => {
            for (const item of unavailableItems) {
              const result = await useCartStore.getState().remove(item.id);
              if (!mountedRef.current) return;
              if (!result.success) {
                Alert.alert('เอาสินค้าออกไม่สำเร็จ', result.message);
                return;
              }
            }
          },
        },
      ]
    );
  };

  const goCheckout = () => {
    if (hasPending) {
      Alert.alert('รอสักครู่', 'กำลังบันทึกจำนวนสินค้า ลองกดอีกครั้งนะ');
      return;
    }
    if (unavailableItems.length > 0) {
      removeUnavailable();
      return;
    }
    router.push('/checkout');
  };

  // ---------- render ----------
  if (!isAuthenticated) {
    return (
      <Screen title="ตะกร้าสินค้า" scroll={false}>
        <EmptyState
          icon="lock-key"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อดูตะกร้าและสั่งซื้อสินค้า"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </Screen>
    );
  }

  if (!cart) {
    return (
      <Screen title="ตะกร้าสินค้า" scroll={false}>
        {loading || !loadError ? (
          <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
        ) : (
          <EmptyState variant="error" message={loadError} onAction={() => useCartStore.getState().refresh()} />
        )}
      </Screen>
    );
  }

  if (items.length === 0) {
    return (
      <Screen title="ตะกร้าสินค้า" scroll={false}>
        <EmptyState
          art="cart"
          title="ตะกร้ายังว่างอยู่"
          message="เลือกของถูกใจแล้วกดใส่ตะกร้าได้เลย"
          actionLabel="ไปช้อปเลย"
          onAction={() => router.replace('/(tabs)/shop' as never)}
          secondaryActionLabel="ไปตลาดสด"
          onSecondaryAction={() => router.push('/taladsod' as never)}
        />
      </Screen>
    );
  }

  const summary = cart.summary;

  return (
    <View style={[styles.root, { backgroundColor: colors.background }]}>
      <Screen
        title="ตะกร้าสินค้า"
        subtitle={`${summary.items_count.toLocaleString('th-TH')} ชิ้น`}
        refreshing={refreshing}
        onRefresh={onRefresh}
        right={
          <Button3D
            title="ล้าง"
            icon="trash"
            variant="ghost"
            size="sm"
            onPress={clearAll}
            loading={clearing}
            accessibilityLabel="ล้างตะกร้าทั้งหมด"
          />
        }
        contentStyle={{ paddingBottom: 130 + Math.max(insets.bottom, spacing.md) }}
      >
        {groups.map((group) => (
          <Card3D key={group.key} padding={0} radius={22} style={styles.group}>
            <View style={[styles.groupHeader, { borderBottomColor: colors.divider }]}>
              <IconTile icon="storefront" size={38} />
              <Text numberOfLines={1} style={[typography.h3, styles.flex, { color: colors.textStrong }]}>
                {group.name}
              </Text>
              {group.riderAvailable && <Pill label="ไรเดอร์ส่งได้" icon="moped" tone="success" />}
            </View>
            {group.items.map((item, index) => (
              <View key={item.id} style={styles.itemWrap}>
                <View style={index > 0 ? [styles.divider, { borderTopColor: colors.divider }] : undefined}>
                  <ItemRow
                    item={item}
                    quantity={pendingQty[item.id] ?? item.quantity}
                    busy={!!busyItems[item.id]}
                    onQuantity={(next) => changeQuantity(item, next)}
                    onRemove={() => removeItem(item)}
                  />
                </View>
              </View>
            ))}
            {group.subtotal !== null && (
              <View style={[styles.groupFooter, { borderTopColor: colors.divider, backgroundColor: colors.surface }]}>
                <Text style={[typography.caption, { color: colors.textMuted }]}>รวมร้านนี้</Text>
                <PriceText amount={group.subtotal} size="sm" tone="strong" />
              </View>
            )}
          </Card3D>
        ))}

        {unavailableItems.length > 0 && (
          <Card3D padding={0} radius={22} style={styles.group}>
            <View style={[styles.groupHeader, { borderBottomColor: colors.divider }]}>
              <IconTile icon="warning" tone="danger" size={38} weight="fill" />
              <Text style={[typography.h3, styles.flex, { color: colors.danger }]}>สั่งซื้อไม่ได้ตอนนี้</Text>
            </View>
            {unavailableItems.map((item, index) => (
              <View key={item.id} style={styles.itemWrap}>
                <View style={index > 0 ? [styles.divider, { borderTopColor: colors.divider }] : undefined}>
                  <ItemRow
                    item={item}
                    quantity={item.quantity}
                    busy={!!busyItems[item.id]}
                    onQuantity={() => {}}
                    onRemove={() => removeItem(item)}
                  />
                </View>
              </View>
            ))}
            <View style={styles.removeAllWrap}>
              <Button3D title="เอาสินค้าเหล่านี้ออก" icon="trash" variant="danger" size="sm" onPress={removeUnavailable} />
            </View>
          </Card3D>
        )}

        {/* สรุปยอดจาก server */}
        <Card3D variant="inset" padding={spacing.lg} radius={20} style={styles.group}>
          <View style={styles.summaryRow}>
            <Text style={[typography.body, { color: colors.text }]}>ค่าสินค้า ({summary.available_items_count} ชิ้น)</Text>
            <PriceText amount={summary.subtotal} size="sm" tone="strong" />
          </View>
          <View style={styles.summaryRow}>
            <Text style={[typography.body, { color: colors.text }]}>ค่าจัดส่ง (พัสดุ)</Text>
            {summary.shipping_fee > 0 ? (
              <PriceText amount={summary.shipping_fee} size="sm" tone="strong" />
            ) : (
              <Pill label="ส่งฟรี" tone="success" icon="truck" />
            )}
          </View>
          {summary.discount > 0 && (
            <View style={styles.summaryRow}>
              <Text style={[typography.body, { color: colors.text }]}>ส่วนลด</Text>
              <PriceText amount={-summary.discount} size="sm" tone="success" />
            </View>
          )}
          <View style={[styles.summaryNote, { borderTopColor: colors.divider }]}>
            <Icon name="info" size={15} color={colors.textMuted} />
            <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>
              เลือกส่งด้วยไรเดอร์ ใส่โค้ดส่วนลด และเลือกที่อยู่ได้ในขั้นตอนชำระเงิน
            </Text>
          </View>
        </Card3D>
      </Screen>

      {/* แถบชำระเงิน */}
      <StickyBar style={styles.bottomBar}>
        <View style={styles.flex}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>ยอดรวมโดยประมาณ</Text>
          <PriceText amount={summary.grand_total} size="lg" tone="gold" />
        </View>
        <Button3D
          title={unavailableItems.length > 0 ? 'จัดการตะกร้าก่อน' : 'ไปชำระเงิน'}
          iconRight={unavailableItems.length > 0 ? undefined : 'arrow-right'}
          icon={unavailableItems.length > 0 ? 'warning' : undefined}
          size="lg"
          disabled={availableItems.length === 0}
          onPress={goCheckout}
          style={styles.checkoutButton}
        />
      </StickyBar>
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  group: {
    marginBottom: spacing.lg,
  },
  groupHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    borderBottomWidth: 1,
  },
  itemWrap: {
    paddingHorizontal: spacing.lg,
  },
  itemRow: {
    flexDirection: 'row',
    gap: spacing.md,
    paddingVertical: spacing.md,
  },
  dimmed: {
    opacity: 0.6,
  },
  itemTop: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
  },
  removeButton: {
    width: 30,
    height: 30,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'baseline',
    gap: spacing.xs,
    marginTop: spacing.xxs,
  },
  qtyRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  unavailable: {
    marginTop: spacing.xs,
  },
  lowStock: {
    marginTop: spacing.xs,
  },
  divider: {
    borderTopWidth: 1,
  },
  groupFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    // การ์ดโหมดมืดมีขอบ 1px → มุมพื้นท้ายการ์ดเล็กกว่ามุมการ์ดนิดหนึ่ง ไม่ให้ล้นขอบ
    borderBottomLeftRadius: 21,
    borderBottomRightRadius: 21,
  },
  removeAllWrap: {
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.lg,
    alignItems: 'flex-start',
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.sm,
  },
  summaryNote: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 6,
    borderTopWidth: 1,
    paddingTop: spacing.sm,
    marginTop: spacing.xs,
  },
  bottomBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  checkoutButton: {
    minWidth: 170,
  },
});
