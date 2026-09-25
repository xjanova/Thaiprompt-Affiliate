/**
 * รายละเอียดสินค้า — ธีมรอยัล น้ำเงินกรมท่า-ทอง (หน้าสินค้าแบบอีคอมเมิร์ซพรีเมียม)
 *
 * หน้าตา
 *   - รูปสินค้าใหญ่เต็มความกว้างชนขอบบน (เลื่อนดูหลายรูป + จุดบอกตำแหน่ง) · ไม่มีรูป = พื้นน้ำเงินลายกนก + ภาพถุงช้อป 3D
 *   - ปุ่มย้อนกลับ/แชร์/ตะกร้าแบบกระจกลอยบนรูป → เลื่อนพ้นรูปแล้วหัวน้ำเงินค่อยๆ ปรากฏพร้อมชื่อสินค้า
 *   - การ์ดขาวข้อมูลสินค้าซ้อนทับขอบล่างรูป (ชื่อฟอนต์มีเชิง ราคาทอง ดาว ยอดขาย)
 *   - แถบล่างลอย: ราคารวม + ปุ่มจำนวน · "ใส่ตะกร้า" (น้ำเงิน) + "ซื้อเลย" (ทอง)
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
  Share,
  StatusBar,
  StyleSheet,
  View,
  useWindowDimensions,
} from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import Animated, {
  Extrapolation,
  interpolate,
  useAnimatedScrollHandler,
  useAnimatedStyle,
  useSharedValue,
} from 'react-native-reanimated';
import { router, useLocalSearchParams } from 'expo-router';
import * as Clipboard from 'expo-clipboard';
import * as WebBrowser from 'expo-web-browser';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { useCartStore } from '@/stores/cartStore';
import { getProduct, type Cart, type ShopProductDetail } from '@/services/api/shopApi';
import { APP_INFO } from '@/config/appConfig';
import {
  CartButton,
  MetaItem,
  NoticeBanner,
  QuantityStepper,
  StickyBar,
  StoreLogo,
  stripHtml,
} from '@/components/shop';
import {
  BrandArt,
  Button3D,
  Card3D,
  EmptyState,
  GlassIconButton,
  Icon,
  OnHeaderProvider,
  Pill,
  PriceText,
  RoyalHeader,
  Screen,
  SectionHeader,
  formatBaht,
  resultHaptic,
} from '@/components/ui';
import { useTheme, spacing, typography } from '@/theme';

/** ลิงก์หน้าสินค้าบนเว็บ (ไม่มีรหัสแนะนำ — แชร์แบบลิงก์สินค้าเฉยๆ) */
const productWebUrl = (p: ShopProductDetail): string =>
  `${APP_INFO.WEBSITE}/shop/${encodeURIComponent(p.slug || String(p.id))}`;

/** การ์ดข้อมูลซ้อนทับขอบล่างรูปกี่พิกเซล */
const CARD_OVERLAP = 34;
/** ความสูงแถวปุ่มบนหัว (ไม่รวม safe area) */
const TOP_BAR_H = 58;
/** ม่านมืดบนรูป — ให้ปุ่มกระจก/แถบสถานะอ่านออกบนรูปสว่าง (บนรูปต้องมืดเสมอทั้งสองโหมด) */
const PHOTO_SCRIM = 'rgba(6, 13, 27, 0.34)';
const PHOTO_SCRIM_TOP = 'rgba(6, 13, 27, 0.55)';
const PHOTO_SCRIM_CLEAR = 'rgba(6, 13, 27, 0)';

export default function ProductDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { colors, gradients } = useTheme();
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

  // ---------- หัวลอยบนรูป: เลื่อนพ้นรูปแล้วพื้นน้ำเงิน + ชื่อสินค้าค่อยๆ ปรากฏ (หน้าตาเท่านั้น) ----------
  // จำกัดความสูงบนแท็บเล็ต ไม่ให้รูปกินทั้งจอ
  const heroHeight = images.length > 0 ? Math.round(Math.min(width * 0.92, 560)) : Math.round(Math.min(width * 0.72, 420));
  const fadeFrom = Math.max(0, heroHeight - TOP_BAR_H - insets.top - 110);
  const fadeTo = Math.max(fadeFrom + 1, heroHeight - TOP_BAR_H - insets.top - 30);
  const scrollY = useSharedValue(0);
  const onScroll = useAnimatedScrollHandler((event) => {
    scrollY.value = event.contentOffset.y;
  });
  const headerBgAnim = useAnimatedStyle(() => ({
    opacity: interpolate(scrollY.value, [fadeFrom, fadeTo], [0, 1], Extrapolation.CLAMP),
  }));
  const headerTitleAnim = useAnimatedStyle(() => ({
    opacity: interpolate(scrollY.value, [fadeTo - 10, fadeTo + 30], [0, 1], Extrapolation.CLAMP),
  }));

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
          icon={error?.notFound ? 'magnifying-glass' : undefined}
          title={error?.notFound ? 'ไม่พบสินค้านี้' : undefined}
          message={error?.notFound ? 'สินค้าอาจถูกปิดการขายแล้ว ลองดูสินค้าอื่นนะ' : error?.message}
          actionLabel={error?.notFound ? 'ไปหน้าช้อป' : 'ลองใหม่'}
          onAction={error?.notFound ? () => router.replace('/(tabs)/shop' as never) : load}
        />
      </Screen>
    );
  }

  const imageSize = width;
  /** เว้นที่ท้ายเนื้อหาให้พ้นแถบล่าง (แถวราคารวม + แถวปุ่ม) */
  const bottomSpace = (canBuy ? 72 : 0) + 96 + Math.max(insets.bottom, spacing.md);
  const reviewStars = (rating: number): number => Math.max(1, Math.min(5, Math.round(rating)));

  /** ย้อนกลับ (เหมือนปุ่มย้อนกลับของหัวหน้าจอมาตรฐาน) */
  const goBack = () => {
    if (router.canGoBack()) {
      router.back();
    } else {
      router.replace('/(tabs)' as never);
    }
  };

  return (
    <View style={[styles.root, { backgroundColor: colors.background }]}>
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />

      <Animated.ScrollView
        onScroll={onScroll}
        scrollEventThrottle={16}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: bottomSpace }}
      >
        {/* ---------- รูปสินค้า (ชนขอบบน) ---------- */}
        <View style={[styles.hero, { height: heroHeight, backgroundColor: colors.inset }]}>
          {images.length > 0 ? (
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
                  style={{ width: imageSize, height: heroHeight, backgroundColor: colors.inset }}
                  contentFit="cover"
                  transition={200}
                  accessibilityLabel={product.name}
                />
              )}
            />
          ) : (
            // ไม่มีรูป → พื้นน้ำเงินกรมท่าลายกนก + ถุงช้อป 3D
            <RoyalHeader
              ornamentTop={insets.top}
              ornamentWidth={230}
              style={[StyleSheet.absoluteFill, styles.noImage, { paddingTop: insets.top, paddingBottom: CARD_OVERLAP }]}
            >
              <BrandArt name="bag" size={Math.round(Math.min(width * 0.34, 180))} />
            </RoyalHeader>
          )}

          {/* ม่านบน: ปุ่มกระจกและแถบสถานะอ่านออกบนรูปสว่าง */}
          {images.length > 0 && (
            <LinearGradient
              pointerEvents="none"
              colors={[PHOTO_SCRIM_TOP, PHOTO_SCRIM_CLEAR]}
              style={[styles.topScrim, { height: insets.top + TOP_BAR_H + 40 }]}
            />
          )}

          {images.length > 1 && (
            <View pointerEvents="none" style={[styles.dots, { bottom: CARD_OVERLAP + spacing.md, backgroundColor: PHOTO_SCRIM }]}>
              {images.map((uri, i) => (
                <View
                  key={`${i}-${uri}`}
                  style={[
                    styles.dot,
                    { backgroundColor: i === imageIndex ? colors.goldLight : colors.onHeaderMuted, width: i === imageIndex ? 16 : 6 },
                  ]}
                />
              ))}
            </View>
          )}
        </View>

        <View style={[styles.body, { marginTop: -CARD_OVERLAP }]}>
          {/* ---------- ชื่อ + ราคา (การ์ดซ้อนรูป) ---------- */}
          <Card3D padding={spacing.xl} radius={24} shadow="lg">
            {(!!product.category || (hasDiscount && !!product.discount_percent) || !!product.store?.rider_delivery) && (
              <View style={styles.pills}>
                {!!product.category && <Pill label={product.category} tone="gold" />}
                {hasDiscount && product.discount_percent ? <Pill label={`ลด ${product.discount_percent}%`} tone="danger" icon="tag" /> : null}
                {product.store?.rider_delivery && <Pill label="ส่งด้วยไรเดอร์ได้" tone="success" icon="moped" />}
              </View>
            )}
            <Text style={[typography.serif, styles.name, { color: colors.textStrong }]}>{product.name}</Text>

            <View style={styles.priceRow}>
              <PriceText amount={product.price} size="xl" tone="gold" />
              {hasDiscount && <PriceText amount={product.original_price} size="sm" tone="muted" strike bold={false} />}
            </View>

            <View style={styles.ratingRow}>
              {product.review_count > 0 ? (
                <>
                  <Icon name="star" size={15} color={colors.gold} weight="fill" />
                  <Text style={[typography.bodySm, styles.bold, { color: colors.textStrong }]}>{Number(product.rating).toFixed(1)}</Text>
                  <Text style={[typography.bodySm, { color: colors.textMuted }]}>({product.review_count} รีวิว)</Text>
                </>
              ) : (
                <Text style={[typography.bodySm, { color: colors.textMuted }]}>ยังไม่มีรีวิว</Text>
              )}
              {product.sales_count > 0 && (
                <Text style={[typography.bodySm, { color: colors.textMuted }]}>
                  · ขายแล้ว {product.sales_count.toLocaleString('th-TH')} ชิ้น
                </Text>
              )}
            </View>

            {canBuy && product.stock !== null && product.stock !== undefined && (
              <MetaItem
                icon="package"
                text={Number(product.stock) <= 5 ? `เหลือเพียง ${product.stock} ชิ้น` : `มีสินค้า ${product.stock} ชิ้น`}
                color={Number(product.stock) <= 5 ? colors.warning : colors.textMuted}
                style={styles.stock}
              />
            )}

            {!!blockReason && !product.is_affiliate && (
              <NoticeBanner tone="danger" text={blockReason} style={styles.blockReason} />
            )}
          </Card3D>

          {/* ---------- ร้านค้า ---------- */}
          {product.store && (
            <Card3D
              onPress={() => router.push(`/store/${product.store!.id}` as never)}
              padding={spacing.md}
              radius={20}
              shadow="sm"
              style={styles.block}
              accessibilityLabel={`ร้าน ${product.store.name}`}
            >
              <View style={styles.storeRow}>
                <StoreLogo uri={product.store.logo} size={48} radius={16} />
                <View style={styles.flex}>
                  <View style={styles.storeNameRow}>
                    <Text style={[typography.bodyStrong, styles.shrink, { color: colors.textStrong }]} numberOfLines={1}>
                      {product.store.name}
                    </Text>
                    {product.store.is_verified && <Icon name="seal-check" size={16} color={colors.goldDeep} weight="fill" />}
                  </View>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>ดูสินค้าทั้งหมดของร้าน</Text>
                </View>
                <View style={[styles.chevron, { backgroundColor: colors.navySoft }]}>
                  <Icon name="caret-right" size={16} color={colors.textMuted} weight="bold" />
                </View>
              </View>
            </Card3D>
          )}

          {/* ---------- รายละเอียด ---------- */}
          <SectionHeader title="รายละเอียดสินค้า" style={styles.section} />
          <Card3D padding={spacing.lg} shadow="sm">
            <Text style={[typography.body, { color: colors.text }]}>
              {stripHtml(product.description_full || product.description) || 'ร้านยังไม่ได้ใส่รายละเอียดสินค้า'}
            </Text>
          </Card3D>

          {/* ---------- รีวิว ---------- */}
          {product.reviews.length > 0 && (
            <>
              <SectionHeader title="รีวิวจากผู้ซื้อ" style={styles.section} />
              <Card3D padding={0} shadow="sm">
                {product.reviews.slice(0, 5).map((r, index) => (
                  <View
                    key={r.id}
                    style={[styles.review, index > 0 && { borderTopWidth: 1, borderTopColor: colors.divider }]}
                  >
                    <View style={styles.reviewHead}>
                      <View style={[styles.avatar, { backgroundColor: colors.navySoft }]}>
                        <Icon name="user" size={18} color={colors.textMuted} />
                      </View>
                      <View style={styles.flex}>
                        <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
                          {r.reviewer}
                        </Text>
                        <View style={styles.stars} accessible accessibilityLabel={`${reviewStars(r.rating)} ดาว`}>
                          {[1, 2, 3, 4, 5].map((n) => (
                            <Icon key={n} name="star" size={13} color={n <= reviewStars(r.rating) ? colors.gold : colors.border} weight="fill" />
                          ))}
                        </View>
                      </View>
                      {r.is_verified_purchase && <Pill label="ซื้อจริง" tone="success" icon="seal-check" />}
                    </View>
                    {!!r.comment && <Text style={[typography.bodySm, styles.reviewText, { color: colors.text }]}>{r.comment}</Text>}
                    {!!r.seller_response && (
                      <View style={[styles.reply, { backgroundColor: colors.inset, borderLeftColor: colors.gold }]}>
                        <Text style={[typography.caption, { color: colors.textMuted }]}>
                          <Text style={[typography.caption, styles.bold, { color: colors.goldDeep }]}>ร้านตอบ: </Text>
                          {r.seller_response}
                        </Text>
                      </View>
                    )}
                  </View>
                ))}
              </Card3D>
            </>
          )}

          {/* ---------- แชร์ (ลิงก์สินค้าเฉยๆ) ---------- */}
          <View style={styles.shareRow}>
            <Button3D title="แชร์สินค้า" icon="share-network" variant="secondary" size="sm" onPress={share} style={styles.flex} />
            <Button3D title="คัดลอกลิงก์" icon="link" variant="secondary" size="sm" onPress={copyLink} style={styles.flex} />
          </View>
        </View>
      </Animated.ScrollView>

      {/* ---------- หัวลอย: ปุ่มกระจกบนรูป → พื้นน้ำเงินเมื่อเลื่อนพ้นรูป ---------- */}
      <View style={[styles.topBar, { paddingTop: insets.top + spacing.xs }]} pointerEvents="box-none">
        <Animated.View pointerEvents="none" style={[StyleSheet.absoluteFill, headerBgAnim]}>
          <LinearGradient colors={gradients.hero} start={{ x: 0, y: 0 }} end={{ x: 0, y: 1 }} style={StyleSheet.absoluteFill} />
        </Animated.View>
        <OnHeaderProvider value>
          <View style={styles.topRow} pointerEvents="box-none">
            <View style={[styles.glassWrap, { backgroundColor: PHOTO_SCRIM }]}>
              <GlassIconButton icon="caret-left" weight="bold" size={42} accessibilityLabel="ย้อนกลับ" onPress={goBack} />
            </View>
            <Animated.View
              pointerEvents="none"
              style={[styles.topTitle, headerTitleAnim]}
              accessibilityElementsHidden
              importantForAccessibility="no-hide-descendants"
            >
              <Text numberOfLines={1} style={[typography.serifSm, { color: colors.onHeader }]}>
                {product.name}
              </Text>
            </Animated.View>
            <View style={[styles.glassWrap, { backgroundColor: PHOTO_SCRIM }]}>
              <GlassIconButton icon="share-network" size={42} accessibilityLabel="แชร์สินค้า" onPress={share} />
            </View>
            {isAuthenticated && (
              <View style={[styles.glassWrap, { backgroundColor: PHOTO_SCRIM }]}>
                <CartButton />
              </View>
            )}
          </View>
        </OnHeaderProvider>
      </View>

      {/* ---------- แถบปุ่มล่าง ---------- */}
      <StickyBar>
        {canBuy && (
          <View style={styles.qtyRow}>
            <View style={styles.flex}>
              <Text style={[typography.caption, { color: colors.textMuted }]}>ราคารวม</Text>
              <PriceText amount={product.price * quantity} size="lg" tone="strong" />
            </View>
            <QuantityStepper value={quantity} min={1} max={maxQty} onChange={setQuantity} />
          </View>
        )}
        <View style={styles.ctaRow}>
          {product.is_affiliate ? (
            <Button3D title="ซื้อที่ร้านต้นทาง" icon="arrow-square-out" size="lg" fullWidth onPress={openAffiliate} />
          ) : (
            <>
              <Button3D
                title="ใส่ตะกร้า"
                icon="shopping-cart-simple"
                variant="navy"
                size="lg"
                disabled={!canBuy}
                onPress={handleAddToCart}
                style={styles.flex}
              />
              <Button3D
                title="ซื้อเลย"
                icon="lightning"
                size="lg"
                disabled={!canBuy}
                onPress={handleBuyNow}
                style={styles.flex}
              />
            </>
          )}
        </View>
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
  shrink: {
    flexShrink: 1,
  },
  bold: {
    fontWeight: '700',
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  hero: {
    width: '100%',
    overflow: 'hidden',
  },
  noImage: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  topScrim: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
  },
  dots: {
    position: 'absolute',
    alignSelf: 'center',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 999,
  },
  dot: {
    height: 6,
    borderRadius: 3,
  },
  body: {
    paddingHorizontal: spacing.screen,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginBottom: spacing.sm,
  },
  name: {
    marginBottom: spacing.xs,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'baseline',
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  ratingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 4,
    marginTop: spacing.sm,
  },
  stock: {
    marginTop: spacing.sm,
  },
  blockReason: {
    marginTop: spacing.md,
  },
  block: {
    marginTop: spacing.md,
  },
  storeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  storeNameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  chevron: {
    width: 30,
    height: 30,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  section: {
    marginTop: spacing.xxl,
  },
  review: {
    padding: spacing.lg,
  },
  reviewHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  avatar: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stars: {
    flexDirection: 'row',
    gap: 2,
    marginTop: 2,
  },
  reviewText: {
    marginTop: spacing.sm,
  },
  reply: {
    marginTop: spacing.sm,
    borderRadius: 12,
    borderLeftWidth: 3,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  shareRow: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.xxl,
  },
  topBar: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    paddingBottom: spacing.sm,
  },
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.screen,
    minHeight: TOP_BAR_H - spacing.sm,
  },
  glassWrap: {
    borderRadius: 15,
  },
  topTitle: {
    flex: 1,
    paddingHorizontal: spacing.xs,
  },
  qtyRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginBottom: spacing.md,
  },
  ctaRow: {
    flexDirection: 'row',
    gap: spacing.md,
  },
});
