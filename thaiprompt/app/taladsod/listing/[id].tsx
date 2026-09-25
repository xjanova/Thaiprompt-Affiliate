/**
 * เมนูตลาดสด — GET /fresh-market/listings/{id}
 *
 * - แกลเลอรีรูป (รูปเมนู + รูปตัวเลือก) · เลือกเนื้อสัตว์ที่มีรูป → เลื่อนไปรูปนั้นให้เห็นทันที
 * - ตัวเลือก: บังคับเลือก 1 (การ์ดรูป + ราคาบวก) · เพิ่มเติมไม่บังคับ (เช่น ไข่ดาว +10)
 * - จำนวน (ไม่เกิน max_order_quantity) · หมายเหตุถึงร้าน · ยอดรวมพรีวิวสด
 * - ปุ่ม "ใส่ตะกร้า ฿xx" ติดล่างจอ: ตัวเลือกไม่ครบ → เลื่อนไปกลุ่มที่ขาด + สั่นเตือน
 *   กันกดซ้ำ (Button3D ล็อกระหว่างส่ง + พักปุ่มสั้นๆ หลังสำเร็จ เพราะกดซ้ำ = จำนวนเพิ่ม)
 * - ร้านปิด → ปุ่มสั่งปิด + ปุ่มติดตามร้าน
 * ราคาที่แสดงเป็นพรีวิว server คิดราคาจริงเสมอ
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
  useWindowDimensions,
  type NativeScrollEvent,
  type NativeSyntheticEvent,
} from 'react-native';
import Animated, { FadeInDown, FadeOutDown } from 'react-native-reanimated';
import { Image } from 'expo-image';
import { router, useLocalSearchParams } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { useTaladsodCartStore } from '@/stores/taladsodCartStore';
import {
  Button3D,
  Card3D,
  EmptyState,
  PriceText,
  Screen,
  SectionHeader,
  formatBaht,
  resultHaptic,
  tapHaptic,
} from '@/components/ui';
import { Field, QuantityStepper, stripHtml } from '@/components/shop';
import {
  OptionPicker,
  ShopAvatar,
  ShopStatusRow,
  TaladsodCartButton,
  selectedOptionImage,
  selectionDelta,
  selectionToIds,
  useMountedRef,
  validateSelection,
  type OptionSelection,
} from '@/components/taladsod';
import {
  fmImageUri,
  followShop,
  getListing,
  getShop,
  type FmListingDetail,
  type FmRelatedListing,
} from '@/services/api/taladsodApi';
import { useTheme, clayShadowStyle, palette, radii, spacing, typography } from '@/theme';

const FALLBACK_FOOD = require('@/assets/images/taladsod/krapao-hero.webp');
const ADD_COOLDOWN_MS = 900;
const TOAST_MS = 3200;
const NOTE_MAX = 255;

export default function TaladsodListingScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const listingId = /^\d+$/.test(String(id || '')) ? Number(id) : 0;
  const { colors, isDark } = useTheme();
  const { width } = useWindowDimensions();
  const insets = useSafeAreaInsets();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const userId = useAuthStore((s) => Number((s.user as { id?: unknown } | null)?.id) || null);
  const mountedRef = useMountedRef();

  const [listing, setListing] = useState<FmListingDetail | null>(null);
  const [related, setRelated] = useState<FmRelatedListing[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<{ message: string; notFound: boolean } | null>(null);
  const [selection, setSelection] = useState<OptionSelection>({});
  const [quantity, setQuantity] = useState(1);
  const [note, setNote] = useState('');
  const [highlightGroup, setHighlightGroup] = useState<number | null>(null);
  const [problem, setProblem] = useState<string | null>(null);
  const [galleryIndex, setGalleryIndex] = useState(0);
  const [toast, setToast] = useState<{ message: string; cartLink: boolean } | null>(null);
  const [shopClosed, setShopClosed] = useState(false);
  const [following, setFollowing] = useState<boolean | null>(null);

  const scrollRef = useRef<ScrollView>(null);
  const galleryRef = useRef<FlatList<string>>(null);
  const optionsYRef = useRef(0);
  const groupYRef = useRef<Record<number, number>>({});
  const cooldownRef = useRef(0);
  const toastTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(
    () => () => {
      if (toastTimerRef.current) clearTimeout(toastTimerRef.current);
    },
    []
  );

  const load = useCallback(async () => {
    if (!listingId) {
      setLoading(false);
      setError({ message: 'ไม่พบเมนูนี้', notFound: true });
      return;
    }
    setLoading(true);
    const res = await getListing(listingId);
    if (!mountedRef.current) return;
    if (res.success) {
      setListing(res.data.listing);
      setRelated(res.data.related.filter((r) => r.id !== listingId));
      const open = res.data.listing.seller?.is_open ?? res.data.listing.shop_is_open;
      setShopClosed(open === false);
      setError(null);
    } else {
      setError({ message: res.message, notFound: res.status === 404 });
    }
    setLoading(false);
  }, [listingId, mountedRef]);

  useEffect(() => {
    load();
  }, [load]);

  // ร้านปิด + ล็อกอินแล้ว → ดูว่าติดตามร้านอยู่ไหม (ใช้กับปุ่มติดตาม)
  useEffect(() => {
    const sellerId = listing?.seller?.id;
    if (!shopClosed || !isAuthenticated || !sellerId) return;
    let alive = true;
    getShop(sellerId).then((res) => {
      if (alive && mountedRef.current && res.success) setFollowing(res.data.is_following);
    });
    return () => {
      alive = false;
    };
  }, [shopClosed, isAuthenticated, listing?.seller?.id, mountedRef]);

  // ---------- รูป ----------
  const gallery = useMemo(() => {
    if (!listing) return [] as string[];
    const urls = [listing.main_image_url, ...listing.images];
    listing.option_groups.forEach((g) => g.options.forEach((o) => o.is_available && urls.push(o.image_url)));
    const out: string[] = [];
    urls.forEach((u) => {
      const uri = fmImageUri(u);
      if (uri && !out.includes(uri)) out.push(uri);
    });
    return out;
  }, [listing]);

  const onGalleryScroll = (e: NativeSyntheticEvent<NativeScrollEvent>) => {
    const i = Math.round(e.nativeEvent.contentOffset.x / Math.max(1, width));
    if (i !== galleryIndex) setGalleryIndex(i);
  };

  const changeSelection = (next: OptionSelection) => {
    setSelection(next);
    setProblem(null);
    if (highlightGroup !== null) setHighlightGroup(null);
    // เลือกเนื้อสัตว์ที่มีรูป → พาแกลเลอรีไปรูปนั้น
    if (listing) {
      const img = fmImageUri(selectedOptionImage(listing.option_groups, next));
      const idx = img ? gallery.indexOf(img) : -1;
      if (idx >= 0 && idx !== galleryIndex) {
        galleryRef.current?.scrollToIndex({ index: idx, animated: true });
        setGalleryIndex(idx);
      }
    }
  };

  // ---------- ราคา ----------
  const groups = listing?.option_groups ?? [];
  const unitPrice = (listing?.price ?? 0) + selectionDelta(groups, selection);
  const total = Math.round(unitPrice * quantity * 100) / 100;
  const maxQty = listing
    ? listing.track_stock
      ? Math.max(1, Math.min(listing.quantity_available, listing.max_order_quantity))
      : Math.max(1, Math.min(99, listing.max_order_quantity))
    : 1;
  const isOwner = !!listing?.seller?.user_id && !!userId && listing.seller.user_id === userId;
  const soldOut = !!listing && (!listing.is_available_for_purchase || (listing.track_stock && listing.quantity_available <= 0));

  useEffect(() => {
    if (quantity > maxQty) setQuantity(maxQty);
  }, [maxQty, quantity]);

  const showToast = (message: string, cartLink: boolean = false) => {
    setToast({ message, cartLink });
    if (toastTimerRef.current) clearTimeout(toastTimerRef.current);
    toastTimerRef.current = setTimeout(() => {
      if (mountedRef.current) setToast(null);
    }, TOAST_MS);
  };

  // ---------- ใส่ตะกร้า ----------
  const addToCart = async () => {
    if (!listing) return;
    if (Date.now() < cooldownRef.current) return;
    if (!isAuthenticated) {
      router.push('/login');
      return;
    }
    const issue = validateSelection(groups, selection);
    if (issue) {
      resultHaptic('warning');
      setHighlightGroup(issue.groupId);
      setProblem(issue.message);
      const y = optionsYRef.current + (groupYRef.current[issue.groupId] ?? 0) - spacing.lg;
      scrollRef.current?.scrollTo({ y: Math.max(0, y), animated: true });
      return;
    }

    const res = await useTaladsodCartStore.getState().add({
      listing_id: listing.id,
      quantity,
      option_ids: selectionToIds(selection),
      note: note.trim() || null,
    });
    if (!mountedRef.current) return;

    if (res.success) {
      cooldownRef.current = Date.now() + ADD_COOLDOWN_MS;
      resultHaptic('success');
      showToast(`ใส่ตะกร้าแล้ว ${quantity} ${listing.unit || 'ที่'}`, true);
      setQuantity(1);
      return;
    }

    resultHaptic('error');
    switch (res.code) {
      case 'UNAUTHENTICATED':
        router.push('/login');
        break;
      case 'SHOP_CLOSED':
      case 'SHOP_UNAVAILABLE':
        setShopClosed(true);
        Alert.alert('ร้านปิดอยู่ตอนนี้', res.message);
        break;
      case 'OPTION_REQUIRED':
      case 'OPTION_LIMIT':
      case 'OPTION_UNAVAILABLE':
      case 'INVALID_OPTION':
        Alert.alert('ตัวเลือกเปลี่ยนไปแล้ว', `${res.message}\nเลือกใหม่อีกครั้งนะ`);
        setSelection({});
        load();
        break;
      default:
        if (res.status === 0 || res.status >= 500) {
          // ไม่แน่ใจว่า server ใส่ให้แล้วหรือยัง (กดซ้ำ = จำนวนเพิ่ม) → ให้ดูตะกร้าก่อน
          useTaladsodCartStore.getState().refresh().catch(() => {});
          Alert.alert('ยังไม่แน่ใจว่าใส่ตะกร้าแล้ว', `${res.message}\nเช็คในตะกร้าก่อนกดอีกครั้งนะ`, [
            { text: 'อยู่หน้านี้', style: 'cancel' },
            { text: 'ดูตะกร้า', onPress: () => router.push('/taladsod/cart' as never) },
          ]);
        } else {
          Alert.alert('ใส่ตะกร้าไม่สำเร็จ', res.message);
        }
    }
  };

  const followFromClosed = async () => {
    const sellerId = listing?.seller?.id;
    if (!sellerId) return;
    if (!isAuthenticated) {
      router.push('/login');
      return;
    }
    const res = await followShop(sellerId);
    if (!mountedRef.current) return;
    if (res.success) {
      resultHaptic('success');
      setFollowing(true);
      showToast('ติดตามร้านแล้ว ร้านเปิดเมื่อไหร่จะแจ้งทันที');
    } else {
      resultHaptic('error');
      Alert.alert('ติดตามร้านไม่สำเร็จ', res.message);
    }
  };

  // ---------- render ----------
  if (loading && !listing) {
    return (
      <Screen title="เมนู" scroll={false}>
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </Screen>
    );
  }

  if (!listing) {
    return (
      <Screen title="เมนู" scroll={false}>
        <EmptyState
          variant={error?.notFound ? 'empty' : 'error'}
          icon={error?.notFound ? '🍽️' : undefined}
          title={error?.notFound ? 'ไม่พบเมนูนี้' : undefined}
          message={error?.notFound ? 'เมนูนี้อาจปิดขายไปแล้ว' : error?.message}
          actionLabel={error?.notFound ? 'กลับไปตลาดสด' : 'ลองใหม่'}
          onAction={error?.notFound ? () => router.replace('/taladsod' as never) : load}
        />
      </Screen>
    );
  }

  const seller = listing.seller;
  const galleryHeight = Math.round(width * 0.72);
  const hasDiscount = listing.compare_at_price !== null && listing.compare_at_price > listing.price;
  const description = stripHtml(listing.description);
  const barHeight = 88 + insets.bottom;
  const blocked = shopClosed || soldOut || isOwner;

  const renderBar = () => {
    if (isOwner) {
      return <Button3D title="นี่คือเมนูในร้านของคุณ" size="lg" fullWidth disabled variant="secondary" />;
    }
    if (shopClosed) {
      return (
        <View style={styles.barRow}>
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ร้านปิดอยู่ตอนนี้</Text>
            <Text numberOfLines={2} style={[typography.caption, { color: colors.textMuted }]}>
              {following ? 'ติดตามแล้ว ร้านเปิดเมื่อไหร่จะแจ้งทันที' : 'ติดตามร้านไว้ เปิดเมื่อไหร่เราบอกทันที'}
            </Text>
          </View>
          {following ? (
            <Button3D title="ดูร้าน" icon="🏪" size="md" variant="secondary" onPress={() => seller && router.push(`/taladsod/shop/${seller.id}` as never)} />
          ) : (
            <Button3D title="ติดตามร้าน" icon="🔔" size="md" onPress={followFromClosed} />
          )}
        </View>
      );
    }
    if (soldOut) {
      return <Button3D title="เมนูนี้หมดชั่วคราว" size="lg" fullWidth disabled variant="secondary" />;
    }
    if (!isAuthenticated) {
      return <Button3D title="เข้าสู่ระบบเพื่อสั่ง" icon="🔓" size="lg" fullWidth onPress={() => router.push('/login')} />;
    }
    return (
      <Button3D
        title={`ใส่ตะกร้า ${formatBaht(total)}`}
        icon="🧺"
        size="lg"
        fullWidth
        onPress={addToCart}
        loadingText="กำลังใส่ตะกร้า…"
        accessibilityLabel={`ใส่ตะกร้า ${quantity} ${listing.unit || 'ที่'} รวม ${total} บาท`}
      />
    );
  };

  return (
    <Screen title={listing.title} subtitle={seller?.shop_name} scroll={false} right={<TaladsodCartButton />}>
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView
          ref={scrollRef}
          style={styles.flex}
          contentContainerStyle={{ paddingBottom: barHeight + spacing.xl }}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          {/* ---------- แกลเลอรี ---------- */}
          <View style={{ height: galleryHeight }}>
            {gallery.length > 0 ? (
              <FlatList
                ref={galleryRef}
                data={gallery}
                keyExtractor={(u) => u}
                horizontal
                pagingEnabled
                showsHorizontalScrollIndicator={false}
                onMomentumScrollEnd={onGalleryScroll}
                getItemLayout={(_, index) => ({ length: width, offset: width * index, index })}
                onScrollToIndexFailed={() => {}}
                renderItem={({ item }) => (
                  <Image
                    source={{ uri: item }}
                    style={{ width, height: galleryHeight, backgroundColor: colors.inset }}
                    contentFit="cover"
                    transition={180}
                    accessibilityLabel={`รูป${listing.title}`}
                  />
                )}
              />
            ) : (
              <Image source={FALLBACK_FOOD} style={{ width, height: galleryHeight }} contentFit="cover" />
            )}
            {gallery.length > 1 && (
              <View style={styles.dots} pointerEvents="none">
                {gallery.map((u, i) => (
                  <View
                    key={u}
                    style={[
                      styles.dot,
                      { backgroundColor: i === galleryIndex ? colors.gold : 'rgba(255,255,255,0.7)' },
                      i === galleryIndex && styles.dotActive,
                    ]}
                  />
                ))}
              </View>
            )}
          </View>

          <View style={styles.body}>
            {/* ---------- ข้อมูลเมนู ---------- */}
            <Card3D gradientBorder padding={spacing.lg} style={styles.infoCard}>
              <Text style={[typography.h1, { color: colors.textStrong }]}>{listing.title}</Text>
              <View style={styles.priceRow}>
                <PriceText amount={listing.price} size="lg" tone="gold" suffix={groups.length > 0 ? 'เริ่มต้น' : listing.unit ? `/${listing.unit}` : undefined} />
                {hasDiscount && <PriceText amount={listing.compare_at_price} size="sm" tone="muted" strike bold={false} />}
              </View>
              {!!description && (
                <Text style={[typography.bodySm, styles.gapTopSm, { color: colors.textMuted }]}>{description}</Text>
              )}
              {listing.track_stock && listing.quantity_available > 0 && listing.quantity_available <= 10 && (
                <Text style={[typography.caption, styles.gapTopSm, { color: colors.warning }]}>
                  เหลืออีก {listing.quantity_available} {listing.unit || 'ชิ้น'}
                </Text>
              )}

              {seller && (
                <Pressable
                  onPress={() => {
                    tapHaptic();
                    router.push(`/taladsod/shop/${seller.id}` as never);
                  }}
                  accessibilityRole="button"
                  accessibilityLabel={`ดูร้าน ${seller.shop_name}`}
                  style={({ pressed }) => [styles.shopRow, { borderTopColor: colors.divider, opacity: pressed ? 0.75 : 1 }]}
                >
                  <ShopAvatar image={seller.shop_image} isMobile={seller.is_mobile} size={44} isOpen={!shopClosed} />
                  <View style={styles.flex}>
                    <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
                      {seller.shop_name}
                    </Text>
                    <ShopStatusRow isOpen={!shopClosed} isMobile={seller.is_mobile} />
                    {!shopClosed && !!seller.location_label && (
                      <Text numberOfLines={1} style={[typography.micro, { color: colors.textMuted }]}>
                        📍 {seller.location_label}
                      </Text>
                    )}
                  </View>
                  <Text style={[typography.caption, { color: colors.goldDeep }]}>ดูร้าน ›</Text>
                </Pressable>
              )}
            </Card3D>

            {/* ---------- ตัวเลือก ---------- */}
            {groups.length > 0 && (
              <View
                style={styles.section}
                onLayout={(e) => {
                  optionsYRef.current = e.nativeEvent.layout.y + galleryHeight;
                }}
              >
                <OptionPicker
                  groups={groups}
                  value={selection}
                  onChange={changeSelection}
                  highlightGroupId={highlightGroup}
                  onGroupLayout={(groupId, y) => {
                    groupYRef.current[groupId] = y;
                  }}
                  disabled={blocked}
                />
                {!!problem && (
                  <Text accessibilityLiveRegion="polite" style={[typography.bodyStrong, styles.problem, { color: colors.danger }]}>
                    ⚠️ {problem}
                  </Text>
                )}
              </View>
            )}

            {/* ---------- จำนวน + หมายเหตุ ---------- */}
            {!blocked && (
              <Card3D padding={spacing.lg} style={styles.section}>
                <View style={styles.qtyRow}>
                  <View style={styles.flex}>
                    <Text style={[typography.h3, { color: colors.textStrong }]}>จำนวน</Text>
                    <Text style={[typography.caption, { color: colors.textMuted }]}>
                      {formatBaht(unitPrice)} ต่อ{listing.unit || 'ที่'}
                    </Text>
                  </View>
                  <QuantityStepper value={quantity} min={1} max={maxQty} onChange={setQuantity} />
                </View>
                <Field
                  label="หมายเหตุถึงร้าน (ไม่บังคับ)"
                  placeholder="เช่น ไม่เผ็ด ไม่ใส่ผัก แยกน้ำ"
                  value={note}
                  onChangeText={(t) => setNote(t.slice(0, NOTE_MAX))}
                  maxLength={NOTE_MAX}
                  multiline
                  hint={`${note.length}/${NOTE_MAX}`}
                  containerStyle={styles.gapTop}
                  onFocus={() => setTimeout(() => scrollRef.current?.scrollToEnd({ animated: true }), 250)}
                />
                <View style={[styles.totalRow, { borderTopColor: colors.divider }]}>
                  <Text style={[typography.bodyStrong, { color: colors.text }]}>รวม</Text>
                  <PriceText amount={total} size="lg" tone="gold" />
                </View>
              </Card3D>
            )}

            {/* ---------- เมนูอื่น ---------- */}
            {related.length > 0 && (
              <>
                <SectionHeader title="เมนูอื่นที่น่าลอง" icon="✨" style={styles.section} />
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.relatedList} style={styles.bleed}>
                  {related.map((r) => {
                    const uri = fmImageUri(r.main_image_url);
                    return (
                      <Card3D
                        key={r.id}
                        onPress={() => router.push(`/taladsod/listing/${r.id}` as never)}
                        padding={0}
                        radius={radii.lg}
                        shadow="sm"
                        style={styles.relatedCard}
                        accessibilityLabel={`${r.title} ราคา ${r.price} บาท`}
                      >
                        <Image
                          source={uri ? { uri } : FALLBACK_FOOD}
                          style={[styles.relatedImg, { backgroundColor: colors.inset }]}
                          contentFit="cover"
                          transition={140}
                        />
                        <View style={styles.relatedBody}>
                          <Text numberOfLines={1} style={[typography.caption, { color: colors.textStrong }]}>
                            {r.title}
                          </Text>
                          <PriceText amount={r.price} size="sm" tone="gold" />
                        </View>
                      </Card3D>
                    );
                  })}
                </ScrollView>
              </>
            )}
          </View>
        </ScrollView>

        {/* ---------- แจ้งใส่ตะกร้าแล้ว ---------- */}
        {!!toast && (
          <Animated.View
            entering={FadeInDown.springify().damping(16)}
            exiting={FadeOutDown}
            style={[styles.toast, { bottom: barHeight + spacing.sm, backgroundColor: colors.textStrong }, clayShadowStyle('md', colors.shadowDark, colors.shadowLight)]}
          >
            <Text style={[typography.bodyStrong, styles.flex, { color: colors.background }]}>✅ {toast.message}</Text>
            {toast.cartLink && (
              <Pressable
                onPress={() => router.push('/taladsod/cart' as never)}
                accessibilityRole="button"
                hitSlop={8}
                style={styles.toastAction}
              >
                {/* พื้น toast = สีกลับด้าน (โหมดมืดเป็นครีม) → ทองเข้มในโหมดมืดให้อ่านออก (≈5.6:1) */}
                <Text style={[typography.bodyStrong, { color: isDark ? palette.gold750 : colors.gold }]}>ดูตะกร้า ›</Text>
              </Pressable>
            )}
          </Animated.View>
        )}

        {/* ---------- ปุ่มติดล่าง ---------- */}
        <View
          style={[
            styles.bar,
            {
              paddingBottom: Math.max(insets.bottom, spacing.md),
              backgroundColor: colors.card,
              borderTopColor: colors.border,
            },
            clayShadowStyle('md', colors.shadowDark, colors.shadowLight),
          ]}
        >
          {renderBar()}
        </View>
      </KeyboardAvoidingView>
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
  dots: {
    position: 'absolute',
    bottom: spacing.md,
    left: 0,
    right: 0,
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 6,
  },
  dot: {
    width: 7,
    height: 7,
    borderRadius: 4,
  },
  dotActive: {
    width: 20,
  },
  body: {
    paddingHorizontal: spacing.screen,
  },
  infoCard: {
    marginTop: -spacing.xl,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'baseline',
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  gapTop: {
    marginTop: spacing.md,
  },
  gapTopSm: {
    marginTop: spacing.sm,
  },
  shopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.lg,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  section: {
    marginTop: spacing.xl,
  },
  problem: {
    marginTop: spacing.md,
  },
  qtyRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  totalRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  bleed: {
    marginHorizontal: -spacing.screen,
  },
  relatedList: {
    paddingHorizontal: spacing.screen,
    paddingVertical: spacing.sm,
    gap: spacing.md,
  },
  relatedCard: {
    width: 140,
  },
  relatedImg: {
    width: 140,
    height: 104,
    borderTopLeftRadius: radii.lg,
    borderTopRightRadius: radii.lg,
  },
  relatedBody: {
    padding: spacing.sm,
  },
  toast: {
    position: 'absolute',
    left: spacing.screen,
    right: spacing.screen,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  toastAction: {
    minHeight: 32,
    justifyContent: 'center',
  },
  bar: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
  },
  barRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
});
