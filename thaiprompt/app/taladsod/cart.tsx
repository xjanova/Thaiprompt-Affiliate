/**
 * ตะกร้าตลาดสด — แยกตามร้าน (1 ร้าน = 1 ออเดอร์)
 *
 * - server เป็นข้อมูลหลัก: ทุกการแก้ไขแทนตะกร้าทั้งใบด้วยค่าที่ server ตอบ
 * - กด +/− รัวๆ → รวมเป็นคำสั่งเดียวหลังหยุดกด 450ms
 * - แก้ตัวเลือก/หมายเหตุ ได้ในแผ่นล่าง (ตรงกับบรรทัดอื่น = server รวมให้)
 * - ลบ / ล้างร้าน ต้องยืนยันก่อนเสมอ
 * - ค่าส่งไรเดอร์โดยประมาณ: ใช้ตำแหน่งที่เคยอนุญาตแล้วเท่านั้น (ไม่ถามเอง) หรือกดปุ่มขอดูค่าส่ง
 * - ร้านปิด/มีรายการสั่งไม่ได้ → ปุ่มสั่งปิดพร้อมบอกเหตุผล
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { useTaladsodCartStore } from '@/stores/taladsodCartStore';
import {
  Button3D,
  Card3D,
  EmptyState,
  PriceText,
  Screen,
  formatBaht,
  resultHaptic,
} from '@/components/ui';
import { Field, FormSheet, QuantityStepper } from '@/components/shop';
import {
  OptionPicker,
  ShopAvatar,
  ShopStatusRow,
  idsToSelection,
  sanitizeSelection,
  selectionDelta,
  selectionToIds,
  useBuyerLocation,
  useMountedRef,
  validateSelection,
  type OptionSelection,
} from '@/components/taladsod';
import {
  fmImageUri,
  getFmCartQuote,
  getListing,
  type FmCartLine,
  type FmCartShop,
  type FmListingDetail,
  type FmQuote,
} from '@/services/api/taladsodApi';
import { formatDistance, formatDuration, type Coords } from '@/services/location';
import { useTheme, radii, spacing, typography } from '@/theme';

const QTY_DEBOUNCE_MS = 450;
const NOTE_MAX = 255;
const FALLBACK_FOOD = require('@/assets/images/taladsod/krapao-hero.webp');

type QuoteState = { state: 'loading' } | { state: 'ready'; quote: FmQuote } | { state: 'error'; message: string };

// =====================================================
// บรรทัดในตะกร้า
// =====================================================

const LineRow: React.FC<{
  line: FmCartLine;
  quantity: number;
  busy: boolean;
  onQuantity: (next: number) => void;
  onEdit: () => void;
  onRemove: () => void;
}> = ({ line, quantity, busy, onQuantity, onEdit, onRemove }) => {
  const { colors } = useTheme();
  const uri = fmImageUri(line.image_url);
  const maxQty = line.track_stock ? Math.max(1, line.max_order_quantity) : Math.max(1, Math.min(99, line.max_order_quantity));

  return (
    <View style={[styles.line, !line.is_valid && styles.dimmed]}>
      <Pressable
        onPress={() => router.push(`/taladsod/listing/${line.listing_id}` as never)}
        accessibilityRole="button"
        accessibilityLabel={`ดูเมนู ${line.title}`}
      >
        <Image source={uri ? { uri } : FALLBACK_FOOD} style={[styles.thumb, { backgroundColor: colors.inset }]} contentFit="cover" transition={120} />
      </Pressable>
      <View style={styles.flex}>
        <View style={styles.lineTop}>
          <Text numberOfLines={2} style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]}>
            {line.title}
          </Text>
          <Pressable
            onPress={onRemove}
            disabled={busy}
            hitSlop={10}
            accessibilityRole="button"
            accessibilityLabel={`เอา ${line.title} ออกจากตะกร้า`}
            style={styles.removeBtn}
          >
            <Text style={[styles.removeIcon, { color: colors.textFaint }]}>✕</Text>
          </Pressable>
        </View>
        {!!line.options_label && (
          <Text numberOfLines={2} style={[typography.caption, { color: colors.textMuted }]}>
            {line.options_label}
          </Text>
        )}
        {!!line.note && (
          <Text numberOfLines={2} style={[typography.caption, { color: colors.textMuted }]}>
            📝 {line.note}
          </Text>
        )}
        {!!line.issue && (
          <Text style={[typography.caption, { color: colors.danger }]}>⚠️ {line.issue.message}</Text>
        )}
        <View style={styles.lineBottom}>
          <View>
            <PriceText amount={line.unit_price * quantity} size="md" tone="gold" />
            {quantity > 1 && (
              <Text style={[typography.micro, { color: colors.textFaint }]}>{formatBaht(line.unit_price)} × {quantity}</Text>
            )}
          </View>
          <View style={styles.lineActions}>
            <Pressable
              onPress={onEdit}
              disabled={busy}
              hitSlop={8}
              accessibilityRole="button"
              accessibilityLabel={`แก้ไขตัวเลือก ${line.title}`}
              style={[styles.editBtn, { borderColor: colors.border }]}
            >
              <Text style={[typography.caption, { color: colors.goldDeep }]}>แก้ไข</Text>
            </Pressable>
            <QuantityStepper value={quantity} min={1} max={maxQty} onChange={onQuantity} busy={busy} size="sm" disabled={!line.is_valid && line.issue?.code !== 'OUT_OF_STOCK'} />
          </View>
        </View>
      </View>
    </View>
  );
};

// =====================================================
// หน้าจอ
// =====================================================

export default function TaladsodCartScreen() {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const cart = useTaladsodCartStore((s) => s.cart);
  const loadingCart = useTaladsodCartStore((s) => s.loading);
  const cartError = useTaladsodCartStore((s) => s.error);
  const mountedRef = useMountedRef();
  const location = useBuyerLocation();

  const [refreshing, setRefreshing] = useState(false);
  const [pendingQty, setPendingQty] = useState<Record<number, number>>({});
  const [busyLines, setBusyLines] = useState<Record<number, boolean>>({});
  const [coords, setCoords] = useState<Coords | null>(null);
  const [quotes, setQuotes] = useState<Record<number, QuoteState>>({});

  const [editLine, setEditLine] = useState<FmCartLine | null>(null);
  const [editListing, setEditListing] = useState<FmListingDetail | null>(null);
  const [editLoading, setEditLoading] = useState(false);
  const [editError, setEditError] = useState<string | null>(null);
  const [editSelection, setEditSelection] = useState<OptionSelection>({});
  const [editNote, setEditNote] = useState('');
  const [editProblem, setEditProblem] = useState<{ groupId: number; message: string } | null>(null);
  const [editBusy, setEditBusy] = useState(false);

  const timersRef = useRef<Record<number, ReturnType<typeof setTimeout>>>({});
  /** กันผลโหลดตัวเลือกที่มาช้า (ปิดแผ่น/เปิดบรรทัดอื่นไปแล้ว) ทับของใหม่ */
  const editReqRef = useRef(0);

  useEffect(
    () => () => {
      Object.values(timersRef.current).forEach(clearTimeout);
    },
    []
  );

  useFocusEffect(
    useCallback(() => {
      if (isAuthenticated) useTaladsodCartStore.getState().refresh().catch(() => {});
    }, [isAuthenticated])
  );

  // ตำแหน่งที่เคยอนุญาตแล้ว (ไม่ถาม)
  useEffect(() => {
    let alive = true;
    location.peek().then((c) => {
      if (alive && c) setCoords(c);
    });
    return () => {
      alive = false;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // ---------- ค่าส่งโดยประมาณต่อร้าน ----------
  const shopKey = (cart?.shops || [])
    .filter((s) => s.can_checkout)
    .map((s) => s.seller_id)
    .join(',');

  useEffect(() => {
    if (!coords || !shopKey) return;
    let alive = true;
    const ids = shopKey.split(',').map(Number).filter((n) => n > 0);
    ids.forEach(async (sellerId) => {
      setQuotes((q) => ({ ...q, [sellerId]: { state: 'loading' } }));
      const res = await getFmCartQuote(sellerId, coords.latitude, coords.longitude);
      if (!alive || !mountedRef.current) return;
      setQuotes((q) => ({
        ...q,
        [sellerId]: res.success ? { state: 'ready', quote: res.data } : { state: 'error', message: res.message },
      }));
    });
    return () => {
      alive = false;
    };
  }, [coords, shopKey, mountedRef]);

  const askQuoteLocation = async () => {
    const c = await location.request('delivery');
    if (c && mountedRef.current) setCoords(c);
  };

  // ---------- จำนวน ----------
  const changeQty = (line: FmCartLine, next: number) => {
    setPendingQty((p) => ({ ...p, [line.id]: next }));
    if (timersRef.current[line.id]) clearTimeout(timersRef.current[line.id]);
    timersRef.current[line.id] = setTimeout(async () => {
      delete timersRef.current[line.id];
      if (!mountedRef.current) return;
      setBusyLines((b) => ({ ...b, [line.id]: true }));
      const res = await useTaladsodCartStore.getState().update(line.id, { quantity: next });
      if (!mountedRef.current) return;
      setBusyLines((b) => {
        const out = { ...b };
        delete out[line.id];
        return out;
      });
      setPendingQty((p) => {
        const out = { ...p };
        if (out[line.id] === next) delete out[line.id];
        return out;
      });
      if (!res.success) {
        resultHaptic('error');
        Alert.alert('แก้จำนวนไม่สำเร็จ', res.message);
      }
    }, QTY_DEBOUNCE_MS);
  };

  const removeLine = (line: FmCartLine) => {
    Alert.alert('เอาออกจากตะกร้า?', `${line.title}${line.options_label ? ` (${line.options_label})` : ''}`, [
      { text: 'ไม่เอาออก', style: 'cancel' },
      {
        text: 'เอาออก',
        style: 'destructive',
        onPress: async () => {
          if (timersRef.current[line.id]) {
            clearTimeout(timersRef.current[line.id]);
            delete timersRef.current[line.id];
          }
          setBusyLines((b) => ({ ...b, [line.id]: true }));
          const res = await useTaladsodCartStore.getState().remove(line.id);
          if (!mountedRef.current) return;
          setBusyLines((b) => {
            const out = { ...b };
            delete out[line.id];
            return out;
          });
          if (res.success) resultHaptic('success');
          else {
            resultHaptic('error');
            Alert.alert('เอาออกไม่สำเร็จ', res.message);
          }
        },
      },
    ]);
  };

  const clearShop = (shop: FmCartShop) => {
    Alert.alert('ล้างตะกร้าร้านนี้?', `เอาทุกรายการของ ${shop.seller?.shop_name || 'ร้านนี้'} ออกจากตะกร้า`, [
      { text: 'ไม่ล้าง', style: 'cancel' },
      {
        text: 'ล้างเลย',
        style: 'destructive',
        onPress: async () => {
          const res = await useTaladsodCartStore.getState().clearShop(shop.seller_id);
          if (!mountedRef.current) return;
          if (res.success) resultHaptic('success');
          else Alert.alert('ล้างตะกร้าไม่สำเร็จ', res.message);
        },
      },
    ]);
  };

  // ---------- แก้ตัวเลือก/หมายเหตุ ----------
  /** โหลดตัวเลือกของเมนู (ไม่แตะหมายเหตุที่พิมพ์ไว้) — ล้มแล้วยังแก้หมายเหตุอย่างเดียวได้ หรือกดโหลดใหม่ */
  const loadEditListing = async (line: FmCartLine) => {
    const reqId = ++editReqRef.current;
    setEditListing(null);
    setEditError(null);
    setEditProblem(null);
    setEditSelection({});
    setEditLoading(true);
    const res = await getListing(line.listing_id);
    if (!mountedRef.current || reqId !== editReqRef.current) return;
    setEditLoading(false);
    if (res.success) {
      setEditListing(res.data.listing);
      setEditSelection(sanitizeSelection(res.data.listing.option_groups, idsToSelection(res.data.listing.option_groups, line.option_ids)));
    } else {
      setEditError(res.message);
    }
  };

  const openEdit = (line: FmCartLine) => {
    setEditLine(line);
    setEditNote(line.note || '');
    loadEditListing(line);
  };

  const closeEdit = () => {
    if (editBusy) return;
    editReqRef.current += 1;
    setEditLine(null);
    setEditListing(null);
    setEditLoading(false);
  };

  const saveEdit = async () => {
    if (!editLine || editBusy) return;
    const groups = editListing?.option_groups || [];
    const issue = validateSelection(groups, editSelection);
    if (issue) {
      resultHaptic('warning');
      setEditProblem(issue);
      return;
    }
    setEditBusy(true);
    const res = await useTaladsodCartStore.getState().update(editLine.id, {
      option_ids: editListing ? selectionToIds(editSelection) : undefined,
      note: editNote.trim() || null,
    });
    if (!mountedRef.current) return;
    setEditBusy(false);
    if (res.success) {
      resultHaptic('success');
      setEditLine(null);
      setEditListing(null);
    } else {
      resultHaptic('error');
      Alert.alert('บันทึกไม่สำเร็จ', res.message);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await useTaladsodCartStore.getState().refresh();
    if (mountedRef.current) setRefreshing(false);
  };

  // ---------- render ----------
  if (!isAuthenticated) {
    return (
      <Screen title="ตะกร้าตลาดสด" scroll={false}>
        <EmptyState icon="🔐" title="เข้าสู่ระบบก่อนนะ" message="เข้าสู่ระบบเพื่อดูตะกร้าและสั่งอาหาร" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (!cart && loadingCart) {
    return (
      <Screen title="ตะกร้าตลาดสด" scroll={false}>
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </Screen>
    );
  }

  if (!cart && cartError) {
    return (
      <Screen title="ตะกร้าตลาดสด" scroll={false}>
        <EmptyState variant="error" message={cartError} onAction={() => useTaladsodCartStore.getState().refresh()} />
      </Screen>
    );
  }

  const shops = cart?.shops || [];
  const editGroups = editListing?.option_groups || [];
  const editUnit = editListing ? editListing.price + selectionDelta(editGroups, editSelection) : null;

  return (
    <Screen
      title="ตะกร้าตลาดสด"
      subtitle={cart && cart.items_count > 0 ? `${cart.items_count} ชิ้น จาก ${cart.shops_count} ร้าน` : undefined}
      refreshing={refreshing}
      onRefresh={onRefresh}
    >
      {location.element}

      {shops.length === 0 ? (
        <EmptyState
          icon="🧺"
          title="ตะกร้ายังว่างอยู่"
          message="เลือกเมนูร้อนๆ จากร้านใกล้บ้านได้เลย"
          actionLabel="ไปเลือกเมนู"
          onAction={() => router.replace('/taladsod' as never)}
        />
      ) : (
        <>
          {!coords && (
            <Pressable
              onPress={askQuoteLocation}
              accessibilityRole="button"
              style={({ pressed }) => [styles.quoteHint, { backgroundColor: colors.infoSoft, opacity: pressed ? 0.8 : 1 }]}
            >
              <Text style={[typography.bodySm, styles.flex, { color: colors.info }]}>🛵 อยากรู้ค่าส่งไรเดอร์ถึงตำแหน่งคุณ?</Text>
              <Text style={[typography.caption, { color: colors.info }]}>ดูค่าส่ง ›</Text>
            </Pressable>
          )}

          {shops.map((shop) => {
            const q = quotes[shop.seller_id];
            const name = shop.seller?.shop_name || 'ร้านตลาดสด';
            const reason = !shop.seller?.is_available
              ? 'ร้านนี้ปิดรับออเดอร์ชั่วคราว'
              : !shop.is_open
                ? shop.seller?.closed_message || 'ร้านปิดอยู่ตอนนี้ เก็บไว้ในตะกร้าได้ สั่งได้เมื่อร้านเปิด'
                : shop.issues_count > 0
                  ? `มี ${shop.issues_count} รายการที่สั่งไม่ได้ เอาออกหรือแก้ไขก่อนนะ`
                  : null;

            return (
              <Card3D key={shop.seller_id} padding={spacing.lg} style={styles.shopCard}>
                <View style={styles.shopHead}>
                  <Pressable
                    onPress={() => router.push(`/taladsod/shop/${shop.seller_id}` as never)}
                    accessibilityRole="button"
                    accessibilityLabel={`ดูร้าน ${name}`}
                    style={styles.shopHeadMain}
                  >
                    <ShopAvatar image={shop.seller?.shop_image || null} isMobile={!!shop.seller?.is_mobile} size={44} isOpen={shop.is_open} />
                    <View style={styles.flex}>
                      <Text numberOfLines={1} style={[typography.h3, { color: colors.textStrong }]}>
                        {name}
                      </Text>
                      <ShopStatusRow isOpen={shop.is_open} isMobile={!!shop.seller?.is_mobile} />
                    </View>
                  </Pressable>
                  <Pressable onPress={() => clearShop(shop)} hitSlop={8} accessibilityRole="button" accessibilityLabel={`ล้างตะกร้าร้าน ${name}`}>
                    <Text style={[typography.caption, { color: colors.danger }]}>ล้าง</Text>
                  </Pressable>
                </View>

                <View style={[styles.lines, { borderTopColor: colors.divider }]}>
                  {shop.items.map((line) => (
                    <LineRow
                      key={line.id}
                      line={line}
                      quantity={pendingQty[line.id] ?? line.quantity}
                      busy={!!busyLines[line.id]}
                      onQuantity={(n) => changeQty(line, n)}
                      onEdit={() => openEdit(line)}
                      onRemove={() => removeLine(line)}
                    />
                  ))}
                </View>

                <View style={[styles.summary, { borderTopColor: colors.divider }]}>
                  <View style={styles.sumRow}>
                    <Text style={[typography.bodySm, { color: colors.textMuted }]}>ค่าอาหาร</Text>
                    <PriceText amount={shop.subtotal} size="sm" tone="strong" />
                  </View>
                  {shop.can_checkout && q && (
                    <View style={styles.sumRow}>
                      <Text style={[typography.bodySm, styles.flex, { color: colors.textMuted }]}>
                        {q.state === 'ready' && q.quote.available
                          ? `ค่าส่งไรเดอร์ (${[
                              q.quote.distance_km !== null ? formatDistance(q.quote.distance_km) : null,
                              q.quote.estimated_duration_minutes ? `~${formatDuration(q.quote.estimated_duration_minutes)}` : null,
                            ]
                              .filter(Boolean)
                              .join(' · ')})`
                          : 'ค่าส่งไรเดอร์'}
                      </Text>
                      {q.state === 'loading' ? (
                        <ActivityIndicator size="small" color={colors.gold} />
                      ) : q.state === 'ready' && q.quote.available ? (
                        <PriceText amount={q.quote.total_fee} size="sm" tone="strong" />
                      ) : (
                        <Text style={[typography.caption, { color: colors.warning }]}>
                          {q.state === 'ready' ? q.quote.message || 'ส่งไม่ถึง' : 'คำนวณไม่ได้'}
                        </Text>
                      )}
                    </View>
                  )}
                  {shop.can_checkout && q?.state === 'ready' && !q.quote.available && (
                    <Text style={[typography.micro, { color: colors.textMuted }]}>นัดรับที่ร้านได้ เลือกตอนชำระเงินนะ</Text>
                  )}
                </View>

                {!!reason && (
                  <View style={[styles.reason, { backgroundColor: colors.warningSoft }]}>
                    <Text style={[typography.caption, { color: colors.warning }]}>{reason}</Text>
                  </View>
                )}

                <Button3D
                  title={shop.can_checkout ? `สั่งร้านนี้ · ${formatBaht(shop.subtotal)}` : shop.is_open ? 'แก้รายการก่อนสั่ง' : 'ร้านปิดอยู่'}
                  icon={shop.can_checkout ? '🛵' : undefined}
                  size="lg"
                  fullWidth
                  disabled={!shop.can_checkout || Object.keys(pendingQty).length > 0 || Object.keys(busyLines).length > 0}
                  onPress={() => router.push(`/taladsod/checkout?seller_id=${shop.seller_id}` as never)}
                  style={styles.checkoutBtn}
                />
                {!shop.is_open && (
                  <Button3D
                    title="ติดตามร้าน รับแจ้งเตือนเมื่อเปิด"
                    variant="ghost"
                    size="sm"
                    fullWidth
                    onPress={() => router.push(`/taladsod/shop/${shop.seller_id}` as never)}
                    style={styles.gapTopSm}
                  />
                )}
              </Card3D>
            );
          })}
        </>
      )}

      {/* ---------- แก้ไขตัวเลือก / หมายเหตุ ---------- */}
      <FormSheet
        visible={!!editLine}
        icon="✏️"
        title={editLine ? `แก้ไข ${editLine.title}` : 'แก้ไข'}
        description={editUnit !== null ? `ราคาต่อที่ ${formatBaht(editUnit)} (ราคาจริงคำนวณตอนบันทึก)` : undefined}
        submitLabel={editLoading ? undefined : 'บันทึก'}
        onSubmit={editLoading ? undefined : saveEdit}
        // โหลดตัวเลือกไม่ขึ้น → ยังบันทึกหมายเหตุอย่างเดียวได้ (ตัวเลือกเดิมคงไว้ที่ server)
        busy={editBusy}
        onClose={closeEdit}
      >
        {editLoading && <ActivityIndicator color={colors.gold} style={styles.sheetLoader} />}
        {!!editError && !editLoading && (
          <View style={styles.gapTopSm}>
            <Text style={[typography.bodySm, { color: colors.danger }]}>
              {editError} — ตอนนี้แก้ได้เฉพาะหมายเหตุ ตัวเลือกเดิมยังอยู่ครบ
            </Text>
            {!!editLine && (
              <Button3D
                title="โหลดตัวเลือกอีกครั้ง"
                icon="🔄"
                size="sm"
                variant="secondary"
                disabled={editBusy}
                onPress={() => editLine && loadEditListing(editLine)}
                style={styles.gapTopSm}
              />
            )}
          </View>
        )}
        {editListing && editGroups.length > 0 && (
          <View style={styles.gapTop}>
            <OptionPicker
              groups={editGroups}
              value={editSelection}
              onChange={(next) => {
                setEditSelection(next);
                setEditProblem(null);
              }}
              highlightGroupId={editProblem?.groupId ?? null}
              disabled={editBusy}
            />
            {!!editProblem && (
              <Text style={[typography.bodyStrong, styles.gapTopSm, { color: colors.danger }]}>⚠️ {editProblem.message}</Text>
            )}
          </View>
        )}
        {!editLoading && (
          <Field
            label="หมายเหตุถึงร้าน (ไม่บังคับ)"
            placeholder="เช่น ไม่เผ็ด ไม่ใส่ผัก"
            value={editNote}
            onChangeText={(t) => setEditNote(t.slice(0, NOTE_MAX))}
            maxLength={NOTE_MAX}
            multiline
            hint={`${editNote.length}/${NOTE_MAX}`}
            containerStyle={styles.gapTop}
          />
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
  gapTop: {
    marginTop: spacing.md,
  },
  gapTopSm: {
    marginTop: spacing.sm,
  },
  quoteHint: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    marginBottom: spacing.md,
    minHeight: 48,
  },
  shopCard: {
    marginBottom: spacing.lg,
  },
  shopHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  shopHeadMain: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  lines: {
    marginTop: spacing.md,
    borderTopWidth: 1,
  },
  line: {
    flexDirection: 'row',
    gap: spacing.md,
    paddingVertical: spacing.md,
  },
  dimmed: {
    opacity: 0.7,
  },
  thumb: {
    width: 64,
    height: 64,
    borderRadius: radii.md,
  },
  lineTop: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
  },
  removeBtn: {
    minWidth: 28,
    minHeight: 28,
    alignItems: 'center',
    justifyContent: 'center',
  },
  removeIcon: {
    fontSize: 16,
    fontWeight: '700',
  },
  lineBottom: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: spacing.sm,
    gap: spacing.sm,
  },
  lineActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  editBtn: {
    borderWidth: 1,
    borderRadius: radii.pill,
    paddingHorizontal: spacing.md,
    minHeight: 32,
    justifyContent: 'center',
  },
  summary: {
    borderTopWidth: 1,
    paddingTop: spacing.md,
    gap: spacing.xs,
  },
  sumRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  reason: {
    marginTop: spacing.md,
    borderRadius: radii.sm,
    padding: spacing.sm,
  },
  checkoutBtn: {
    marginTop: spacing.lg,
  },
  sheetLoader: {
    marginVertical: spacing.xl,
  },
});
