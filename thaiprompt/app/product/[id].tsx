/**
 * รายละเอียดสินค้า — ธีมนวลทองคำ
 *
 * - ข้อมูลจริงจาก GET /products/{id} เท่านั้น (SHOP-17 / PLAY-19: เลิกสร้างสินค้าปลอมเมื่อ API ล้ม)
 * - ไม่มี PV / คอมมิชชั่น / "แชร์รับค่าคอม" (SHOP-16)
 * - ลิงก์แชร์ชี้ไป https://main.thaiprompt.online/shop/{slug|id} (SHOP-25)
 * - สินค้า affiliate → ปุ่ม "ซื้อที่ร้านต้นทาง" แทนตะกร้า
 * - ใส่ตะกร้า: ตะกร้าบน server เป็นข้อมูลหลัก (POST /cart/items ผ่าน cartStore) — ไม่มีตะกร้าในเครื่องแล้ว (SHOP-03)
 * - ซื้อเลย: ใส่ตะกร้าแล้วไปหน้าชำระเงิน ไม่ลบของเดิมในตะกร้าโดยไม่ถาม
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Pressable,
  Share,
  StyleSheet,
  Text,
  View,
  useWindowDimensions,
} from 'react-native';
import { Image } from 'expo-image';
import { router, useLocalSearchParams } from 'expo-router';
import * as Clipboard from 'expo-clipboard';
import * as WebBrowser from 'expo-web-browser';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { useCartStore } from '@/stores/cartStore';
import { getProduct, type Cart, type ShopProductDetail } from '@/services/api/shopApi';
import { APP_INFO } from '@/config/appConfig';
import { CartButton, QuantityStepper, stripHtml } from '@/components/shop';
import {
  Button3D,
  Card3D,
  EmptyState,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  formatBaht,
  resultHaptic,
} from '@/components/ui';
import { useTheme, spacing, radii, typography, clayShadowStyle } from '@/theme';

/** ลิงก์หน้าสินค้าบนเว็บ (ไม่มีรหัสแนะนำ — แชร์แบบลิงก์สินค้าเฉยๆ) */
const productWebUrl = (p: ShopProductDetail): string =>
  `${APP_INFO.WEBSITE}/shop/${encodeURIComponent(p.slug || String(p.id))}`;

export default function ProductDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const { width } = useWindowDimensions();
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const addToCart = useCartStore((state) => state.add);
  const refreshCart = useCartStore((state) => state.refresh);
  const setCartQuantity = useCartStore((state) => state.setQuantity);
  /** กันกด "ซื้อเลย" ซ้ำระหว่างที่ยังทำขั้นตอนหลังกล่องถามอยู่ */
  const buyingRef = useRef(false);

  const [product, setProduct] = useState<ShopProductDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<{ message: string; notFound: boolean } | null>(null);
  const [quantity, setQuantity] = useState(1);
  const [imageIndex, setImageIndex] = useState(0);
  const mountedRef = useRef(true);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(async () => {
    const productId = Number(id);
    if (!Number.isInteger(productId) || productId <= 0) {
      setError({ message: 'ไม่พบสินค้านี้', notFound: true });
      setLoading(false);
      return;
    }
    setLoading(true);
    setError(null);
    const result = await getProduct(productId);
    if (!mountedRef.current) return;
    if (result.success && result.data) {
      setProduct({
        ...result.data,
        images: Array.isArray(result.data.images) ? result.data.images : [],
        reviews: Array.isArray(result.data.reviews) ? result.data.reviews : [],
      });
      setQuantity(1);
    } else if (!result.success) {
      setError({ message: result.message, notFound: result.status === 404 });
    }
    setLoading(false);
  }, [id]);

  useEffect(() => {
    load();
  }, [load]);

  // ---------- ค่าที่ใช้แสดง ----------
  const images = product
    ? (product.images && product.images.length > 0 ? product.images : product.image ? [product.image] : [])
    : [];
  const hasDiscount = !!product?.original_price && product.original_price > product.price;
  const maxQty = Math.max(1, Math.min(99, product?.stock ?? 99));
  const blockReason = product?.purchase_block_reason || (!product?.in_stock ? 'สินค้าหมดชั่วคราว' : null);
  const canBuy = !!product && !product.is_affiliate && product.can_add_to_cart && !blockReason;

  // ---------- ตะกร้า (server เป็นข้อมูลหลัก) ----------
  const requireLogin = (): boolean => {
    if (isAuthenticated) return true;
    Alert.alert('เข้าสู่ระบบก่อนนะ', 'เข้าสู่ระบบเพื่อสั่งซื้อสินค้า', [
      { text: 'ไว้ก่อน', style: 'cancel' },
      { text: 'เข้าสู่ระบบ', onPress: () => router.push('/login') },
    ]);
    return false;
  };

  /** แจ้งผลใส่ตะกร้าไม่สำเร็จ (สต็อกไม่พอ → บอกจำนวนที่เหลือ) */
  const showAddError = (title: string, result: { code: string; message: string; data?: any }) => {
    resultHaptic('error');
    const available = Number(result.data?.available);
    const message =
      result.code === 'OUT_OF_STOCK' && Number.isFinite(available)
        ? available > 0
          ? `สินค้าเหลือ ${available} ชิ้น (รวมที่อยู่ในตะกร้าแล้ว) ลองลดจำนวนลงนะ`
          : 'สินค้าหมดแล้ว'
        : result.message;
    Alert.alert(title, message);
  };

  const handleAddToCart = async () => {
    if (!product || !requireLogin()) return;
    const result = await addToCart(product.id, quantity);
    if (!mountedRef.current) return;
    if (!result.success) {
      showAddError('ใส่ตะกร้าไม่สำเร็จ', result);
      return;
    }
    resultHaptic('success');
    Alert.alert('ใส่ตะกร้าแล้ว', `${product.name} × ${quantity}`, [
      { text: 'ช้อปต่อ', style: 'cancel' },
      { text: 'ดูตะกร้า', onPress: () => router.push('/cart') },
    ]);
  };

  /** ไปชำระเงิน — ถ้าตะกร้ามีสินค้าอื่นอยู่ด้วย ถามก่อนว่าจะสั่งรวมหรือไปจัดการตะกร้า (ไม่ลบของผู้ใช้เอง) */
  const goCheckout = (cart: Cart, productId: number) => {
    const others = cart.items.filter((item) => item.product_id !== productId);
    if (others.length === 0) {
      router.push('/checkout');
      return;
    }
    const otherCount = others.reduce((sum, item) => sum + item.quantity, 0);
    Alert.alert(
      'ในตะกร้ามีสินค้าอื่นด้วย',
      `ตะกร้ามีสินค้าอื่นอีก ${otherCount} ชิ้น จะสั่งพร้อมกันเลย หรือไปเลือกในตะกร้าก่อน?`,
      [
        { text: 'ไปที่ตะกร้า', onPress: () => router.push('/cart') },
        { text: 'สั่งรวมกันเลย', onPress: () => router.push('/checkout') },
      ]
    );
  };

  /** ตั้งจำนวนสินค้านี้ในตะกร้าให้ตรงกับที่เลือก แล้วไปชำระเงิน */
  const setLineAndCheckout = async (itemId: number, productId: number, target: number) => {
    if (buyingRef.current) return;
    buyingRef.current = true;
    try {
      const result = await setCartQuantity(itemId, target);
      if (!mountedRef.current) return;
      if (!result.success) {
        showAddError('สั่งซื้อไม่สำเร็จ', result);
        return;
      }
      goCheckout(result.data, productId);
    } finally {
      buyingRef.current = false;
    }
  };

  /**
   * ซื้อเลย = สั่งสินค้านี้ "จำนวนที่เลือก" แล้วไปชำระเงิน
   * - ยังไม่มีในตะกร้า → ใส่ตะกร้าตามจำนวนที่เลือก
   * - มีอยู่แล้วเท่ากัน (เช่น กดซื้อเลยซ้ำหลังย้อนกลับจากหน้าชำระเงิน) → ไม่บวกเพิ่ม
   * - มีอยู่แล้วคนละจำนวน → ถามก่อนว่าจะสั่งกี่ชิ้น (ไม่บวกเพิ่มเงียบๆ)
   */
  const handleBuyNow = async () => {
    if (!product || !requireLogin() || buyingRef.current) return;
    const productId = product.id;

    buyingRef.current = true;
    let current: Awaited<ReturnType<typeof refreshCart>>;
    try {
      current = await refreshCart();
    } finally {
      buyingRef.current = false;
    }
    if (!mountedRef.current) return;
    if (!current.success) {
      showAddError('สั่งซื้อไม่สำเร็จ', current);
      return;
    }
    const cart = current.data;

    // เฉพาะรายการที่ไม่มีตัวเลือกเพิ่ม (หน้านี้ใส่ตะกร้าแบบไม่มีตัวเลือก)
    const existing = cart.items.find(
      (item) =>
        item.product_id === productId && (!item.attributes || Object.keys(item.attributes).length === 0)
    );

    if (!existing) {
      buyingRef.current = true;
      try {
        const added = await addToCart(productId, quantity);
        if (!mountedRef.current) return;
        if (!added.success) {
          showAddError('สั่งซื้อไม่สำเร็จ', added);
          return;
        }
        goCheckout(added.data, productId);
      } finally {
        buyingRef.current = false;
      }
      return;
    }

    if (existing.quantity === quantity) {
      goCheckout(cart, productId);
      return;
    }

    Alert.alert(
      'มีสินค้านี้ในตะกร้าแล้ว',
      `ในตะกร้ามีสินค้านี้ ${existing.quantity} ชิ้นแล้ว จะสั่งกี่ชิ้นดี?`,
      [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: `สั่ง ${existing.quantity} ชิ้นตามตะกร้า`, onPress: () => goCheckout(cart, productId) },
        { text: `สั่ง ${quantity} ชิ้น`, onPress: () => setLineAndCheckout(existing.id, productId, quantity) },
      ]
    );
  };

  const openAffiliate = async () => {
    const url = product?.affiliate_url;
    if (!url || !/^https:\/\//i.test(url)) {
      Alert.alert('ซื้อที่ร้านต้นทาง', 'ลิงก์ร้านต้นทางไม่พร้อมใช้งาน');
      return;
    }
    try {
      await WebBrowser.openBrowserAsync(url);
    } catch {
      Alert.alert('ซื้อที่ร้านต้นทาง', 'เปิดลิงก์ไม่สำเร็จ ลองใหม่อีกครั้งนะ');
    }
  };

  // ---------- แชร์ ----------
  const share = async () => {
    if (!product) return;
    try {
      await Share.share({
        title: product.name,
        message: `${product.name}\nราคา ${formatBaht(product.price)}\n${productWebUrl(product)}`,
      });
    } catch {
      // ผู้ใช้ยกเลิก
    }
  };

  const copyLink = async () => {
    if (!product) return;
    try {
      await Clipboard.setStringAsync(productWebUrl(product));
      resultHaptic('success');
      Alert.alert('คัดลอกแล้ว', 'คัดลอกลิงก์สินค้าแล้ว');
    } catch {
      // คัดลอกไม่ได้ก็ไม่เป็นไร
    }
  };

  // ---------- render ----------
  if (loading && !product) {
    return (
      <Screen title="สินค้า" scroll={false}>
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </Screen>
    );
  }

  if (!product) {
    return (
      <Screen title="สินค้า" scroll={false}>
        <EmptyState
          variant={error?.notFound ? 'empty' : 'error'}
          icon={error?.notFound ? '🔍' : undefined}
          title={error?.notFound ? 'ไม่พบสินค้านี้' : undefined}
          message={error?.notFound ? 'สินค้าอาจถูกปิดการขายแล้ว ลองดูสินค้าอื่นนะ' : error?.message}
          actionLabel={error?.notFound ? 'ไปหน้าช้อป' : 'ลองใหม่'}
          onAction={error?.notFound ? () => router.replace('/(tabs)/shop' as never) : load}
        />
      </Screen>
    );
  }

  const imageSize = width;

  return (
    <View style={[styles.root, { backgroundColor: colors.background }]}>
      <Screen
        title="สินค้า"
        right={
          <>
            <Pressable onPress={share} accessibilityRole="button" accessibilityLabel="แชร์สินค้า" hitSlop={8}>
              <Text style={styles.headerIcon}>📤</Text>
            </Pressable>
            {isAuthenticated && <CartButton />}
          </>
        }
        contentStyle={{ paddingHorizontal: 0, paddingBottom: 140 + insets.bottom }}
      >
        {/* รูปสินค้า */}
        {images.length > 0 ? (
          <View>
            <FlatList
              data={images}
              horizontal
              pagingEnabled
              showsHorizontalScrollIndicator={false}
              keyExtractor={(uri, i) => `${i}-${uri}`}
              onMomentumScrollEnd={(e) => setImageIndex(Math.round(e.nativeEvent.contentOffset.x / imageSize))}
              renderItem={({ item }) => (
                <Image
                  source={{ uri: item }}
                  style={{ width: imageSize, height: imageSize * 0.85, backgroundColor: colors.inset }}
                  contentFit="cover"
                  transition={200}
                  accessibilityLabel={product.name}
                />
              )}
            />
            {images.length > 1 && (
              <View style={styles.dots}>
                {images.map((uri, i) => (
                  <View
                    key={`${i}-${uri}`}
                    style={[styles.dot, { backgroundColor: i === imageIndex ? colors.gold : colors.border, width: i === imageIndex ? 16 : 6 }]}
                  />
                ))}
              </View>
            )}
          </View>
        ) : (
          <View style={[styles.noImage, { height: imageSize * 0.6, backgroundColor: colors.inset }]}>
            <Text style={styles.noImageIcon}>📦</Text>
          </View>
        )}

        <View style={styles.body}>
          {/* ชื่อ + ราคา */}
          <Card3D padding={spacing.lg}>
            <View style={styles.pills}>
              {!!product.category && <Pill label={product.category} tone="gold" />}
              {hasDiscount && product.discount_percent ? <Pill label={`ลด ${product.discount_percent}%`} tone="danger" /> : null}
              {product.store?.rider_delivery && <Pill label="ส่งด้วยไรเดอร์ได้" tone="success" icon="🛵" />}
            </View>
            <Text style={[typography.h1, styles.name, { color: colors.textStrong }]}>{product.name}</Text>
            <View style={styles.priceRow}>
              <PriceText amount={product.price} size="xl" tone="gold" />
              {hasDiscount && <PriceText amount={product.original_price} size="sm" tone="muted" strike bold={false} />}
            </View>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              {product.review_count > 0
                ? `⭐ ${Number(product.rating).toFixed(1)} (${product.review_count} รีวิว)`
                : 'ยังไม่มีรีวิว'}
              {product.sales_count > 0 ? ` · ขายแล้ว ${product.sales_count.toLocaleString('th-TH')} ชิ้น` : ''}
            </Text>
            {!!blockReason && !product.is_affiliate && (
              <Text style={[typography.bodySm, styles.block, { color: colors.danger }]}>{blockReason}</Text>
            )}
          </Card3D>

          {/* ร้านค้า */}
          {product.store && (
            <Card3D
              onPress={() => router.push(`/store/${product.store!.id}` as never)}
              padding={spacing.md}
              radius={radii.lg}
              shadow="sm"
              style={styles.block}
              accessibilityLabel={`ร้าน ${product.store.name}`}
            >
              <View style={styles.storeRow}>
                {product.store.logo ? (
                  <Image source={{ uri: product.store.logo }} style={styles.storeLogo} contentFit="cover" />
                ) : (
                  <View style={[styles.storeLogo, styles.storeLogoEmpty, { backgroundColor: colors.goldSoft }]}>
                    <Text>🏪</Text>
                  </View>
                )}
                <View style={styles.flex}>
                  <Text style={[typography.bodyStrong, { color: colors.textStrong }]} numberOfLines={1}>
                    {product.store.name} {product.store.is_verified ? '✔' : ''}
                  </Text>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>ดูสินค้าทั้งหมดของร้าน</Text>
                </View>
                <Text style={[typography.h2, { color: colors.textFaint }]}>›</Text>
              </View>
            </Card3D>
          )}

          {/* จำนวน */}
          {canBuy && (
            <Card3D padding={spacing.lg} style={styles.block}>
              <View style={styles.qtyRow}>
                <View>
                  <Text style={[typography.h3, { color: colors.textStrong }]}>จำนวน</Text>
                  {product.stock !== null && product.stock !== undefined && (
                    <Text style={[typography.caption, { color: Number(product.stock) <= 5 ? colors.warning : colors.textMuted }]}>
                      {Number(product.stock) <= 5 ? `เหลือเพียง ${product.stock} ชิ้น` : `มีสินค้า ${product.stock} ชิ้น`}
                    </Text>
                  )}
                </View>
                <QuantityStepper value={quantity} min={1} max={maxQty} onChange={setQuantity} />
              </View>
              <View style={styles.totalRow}>
                <Text style={[typography.bodySm, { color: colors.textMuted }]}>ราคารวม</Text>
                <PriceText amount={product.price * quantity} size="lg" tone="strong" />
              </View>
            </Card3D>
          )}

          {/* รายละเอียด */}
          <SectionHeader title="รายละเอียดสินค้า" style={styles.section} />
          <Card3D variant="flat" padding={spacing.lg}>
            <Text style={[typography.body, { color: colors.text }]}>
              {stripHtml(product.description_full || product.description) || 'ร้านยังไม่ได้ใส่รายละเอียดสินค้า'}
            </Text>
          </Card3D>

          {/* รีวิว */}
          {product.reviews.length > 0 && (
            <>
              <SectionHeader title="รีวิวจากผู้ซื้อ" style={styles.section} />
              {product.reviews.slice(0, 5).map((r) => (
                <Card3D key={r.id} variant="flat" padding={spacing.md} style={styles.review}>
                  <Text style={[typography.caption, { color: colors.goldDeep }]}>
                    {'⭐'.repeat(Math.max(1, Math.min(5, Math.round(r.rating))))} · {r.reviewer}
                    {r.is_verified_purchase ? ' · ซื้อจริง' : ''}
                  </Text>
                  {!!r.comment && <Text style={[typography.bodySm, { color: colors.text }]}>{r.comment}</Text>}
                  {!!r.seller_response && (
                    <Text style={[typography.caption, styles.reply, { color: colors.textMuted }]}>ร้านตอบ: {r.seller_response}</Text>
                  )}
                </Card3D>
              ))}
            </>
          )}

          {/* แชร์ (ลิงก์สินค้าเฉยๆ) */}
          <View style={styles.shareRow}>
            <Button3D title="แชร์สินค้า" icon="📤" variant="secondary" size="sm" onPress={share} style={styles.flex} />
            <Button3D title="คัดลอกลิงก์" icon="🔗" variant="secondary" size="sm" onPress={copyLink} style={styles.flex} />
          </View>
        </View>
      </Screen>

      {/* แถบปุ่มล่าง */}
      <View
        style={[
          styles.bottomBar,
          { backgroundColor: colors.card, paddingBottom: Math.max(insets.bottom, spacing.md) },
          clayShadowStyle('md', colors.shadowDark, colors.shadowLight),
        ]}
      >
        {product.is_affiliate ? (
          <Button3D title="ซื้อที่ร้านต้นทาง" icon="🛒" size="lg" fullWidth onPress={openAffiliate} />
        ) : (
          <>
            <Button3D
              title="ใส่ตะกร้า"
              icon="➕"
              variant="secondary"
              size="lg"
              disabled={!canBuy}
              onPress={handleAddToCart}
              style={styles.flex}
            />
            <Button3D
              title="ซื้อเลย"
              icon="⚡"
              size="lg"
              disabled={!canBuy}
              onPress={handleBuyNow}
              style={styles.flex}
            />
          </>
        )}
      </View>
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
  headerIcon: {
    fontSize: 22,
  },
  dots: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 6,
    marginTop: spacing.sm,
  },
  dot: {
    height: 6,
    borderRadius: 3,
  },
  noImage: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  noImageIcon: {
    fontSize: 56,
  },
  body: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.lg,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
  },
  name: {
    marginTop: spacing.sm,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'baseline',
    gap: spacing.sm,
    marginTop: spacing.xs,
    marginBottom: spacing.xs,
  },
  block: {
    marginTop: spacing.md,
  },
  storeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  storeLogo: {
    width: 44,
    height: 44,
    borderRadius: 12,
  },
  storeLogoEmpty: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  totalRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: spacing.md,
  },
  section: {
    marginTop: spacing.xl,
  },
  review: {
    marginBottom: spacing.sm,
    gap: spacing.xs,
  },
  reply: {
    marginTop: spacing.xs,
  },
  shareRow: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.xl,
  },
  bottomBar: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    flexDirection: 'row',
    gap: spacing.md,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
  },
});
