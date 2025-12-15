/**
 * Product Detail Screen - Premium Version
 * - ใช้ local cartStore สำหรับจัดการตะกร้า
 * - ซื้อเลย จะเพิ่มสินค้าลงตะกร้าแล้วไปหน้า checkout
 * - ชำระเงินทั้งหมดจะทำในหน้า checkout (Native Payment Flow)
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  Share,
  ActivityIndicator,
  Dimensions,
  StyleSheet,
  StatusBar,
  Image,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useLocalSearchParams } from 'expo-router';
import * as Clipboard from 'expo-clipboard';
import { useAuthStore } from '@/stores/authStore';
import { useCartStore } from '@/stores/cartStore';
import { formatCurrency, API_BASE_URL } from '@/constants';
import { getProduct, getWallet } from '@/services/api';
import type { Product as ProductType } from '@/types';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

interface Product {
  id: number;
  name: string;
  description?: string;
  price: number;
  original_price?: number;
  image?: string;
  images?: string[];
  category?: string;
  commission_rate?: number;
  pv?: number;
  stock?: number;
  seller?: {
    id: number;
    name: string;
    avatar?: string;
  };
}

export default function ProductDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { user, isAuthenticated, token } = useAuthStore();
  const { addItem: addToCartStore } = useCartStore();

  const [product, setProduct] = useState<Product | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState('');
  const [quantity, setQuantity] = useState(1);
  const [isAddingToCart, setIsAddingToCart] = useState(false);
  const [isFavorite, setIsFavorite] = useState(false);

  // Wallet balance สำหรับแสดงข้อมูล
  const [walletBalance, setWalletBalance] = useState(0);

  // โหลดข้อมูลสินค้า
  const fetchProduct = useCallback(async () => {
    if (!id) {
      setError('ไม่พบ ID สินค้า');
      setIsLoading(false);
      return;
    }

    setIsLoading(true);
    setError('');

    try {
      const response = await getProduct(id);

      if (response?.success && response.data) {
        setProduct(response.data);
      } else {
        // Fallback mock data สำหรับทดสอบ
        setProduct({
          id: parseInt(id),
          name: `สินค้าตัวอย่าง #${id}`,
          description: 'รายละเอียดสินค้าตัวอย่าง สำหรับทดสอบระบบ',
          price: 1990,
          original_price: 2490,
          category: 'ทั่วไป',
          commission_rate: 10,
          pv: 199,
          stock: 100,
        });
      }
    } catch (err: any) {
      console.error('Fetch product error:', err);
      // ใช้ข้อมูล mock เมื่อ API ล้มเหลว
      setProduct({
        id: parseInt(id),
        name: `สินค้า #${id}`,
        description: 'ไม่สามารถโหลดข้อมูลจริงได้ นี่คือข้อมูลตัวอย่าง',
        price: 990,
        category: 'สินค้าทั่วไป',
        commission_rate: 5,
        pv: 99,
      });
    } finally {
      setIsLoading(false);
    }
  }, [id]);

  // โหลดยอดเงิน wallet
  const fetchWalletBalance = useCallback(async () => {
    if (!isAuthenticated) return;

    try {
      const response = await getWallet();
      if (response?.success && response.data) {
        setWalletBalance(response.data.balance || 0);
      }
    } catch (err) {
      console.error('Fetch wallet error:', err);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    fetchProduct();
    fetchWalletBalance();
  }, [fetchProduct, fetchWalletBalance]);

  // เพิ่มลงตะกร้า (ใช้ local cartStore)
  const handleAddToCart = async () => {
    if (!isAuthenticated) {
      Alert.alert('กรุณาเข้าสู่ระบบ', 'คุณต้องเข้าสู่ระบบก่อนเพิ่มสินค้าลงตะกร้า', [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: 'เข้าสู่ระบบ', onPress: () => router.push('/login') },
      ]);
      return;
    }
    if (!product) return;

    setIsAddingToCart(true);
    try {
      // แปลง Product เป็น ProductType สำหรับ cartStore
      const productForCart: ProductType = {
        id: product.id.toString(),
        name: product.name,
        description: product.description || '',
        price: product.price,
        discount_price: product.original_price && product.original_price > product.price
          ? product.price : undefined,
        image: product.image || product.images?.[0],
        category: product.category || '',
        commission_rate: product.commission_rate,
        rating: 0,
        review_count: 0,
      };

      // เพิ่มลง cartStore (local)
      addToCartStore(productForCart, quantity);

      Alert.alert('สำเร็จ! ✓', `เพิ่ม ${product.name} ลงตะกร้าแล้ว`, [
        { text: 'ดูตะกร้า', onPress: () => router.push('/cart') },
        { text: 'ช้อปต่อ', style: 'cancel' },
      ]);
    } catch (err: any) {
      Alert.alert('เกิดข้อผิดพลาด', err.message || 'ไม่สามารถเพิ่มสินค้าได้');
    } finally {
      setIsAddingToCart(false);
    }
  };

  // ซื้อเลย (เพิ่มตะกร้าแล้วไป checkout)
  const handleBuyNow = async () => {
    if (!isAuthenticated) {
      Alert.alert('กรุณาเข้าสู่ระบบ', 'คุณต้องเข้าสู่ระบบก่อนซื้อสินค้า', [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: 'เข้าสู่ระบบ', onPress: () => router.push('/login') },
      ]);
      return;
    }

    if (!product) return;

    try {
      // แปลง Product เป็น ProductType สำหรับ cartStore
      const productForCart: ProductType = {
        id: product.id.toString(),
        name: product.name,
        description: product.description || '',
        price: product.price,
        discount_price: product.original_price && product.original_price > product.price
          ? product.price : undefined,
        image: product.image || product.images?.[0],
        category: product.category || '',
        commission_rate: product.commission_rate,
        rating: 0,
        review_count: 0,
      };

      // เพิ่มลง cartStore แล้วไป checkout
      addToCartStore(productForCart, quantity);
      router.push('/checkout');
    } catch (err: any) {
      Alert.alert('เกิดข้อผิดพลาด', err.message || 'ไม่สามารถดำเนินการได้');
    }
  };

  // แชร์สินค้า
  const shareProduct = async () => {
    if (!product) return;
    try {
      const referralCode = user?.referral_code || '';
      const shareUrl = `https://shop.thaiprompt.com/product/${product.id}${referralCode ? `?ref=${referralCode}` : ''}`;
      await Share.share({
        title: product.name,
        message: `${product.name}\n\nราคา: ${formatCurrency(product.price)}\n\nซื้อได้ที่: ${shareUrl}`,
      });
    } catch (error) {
      console.error('Share error:', error);
    }
  };

  // คัดลอกลิงก์
  const copyLink = async () => {
    if (!product) return;
    const referralCode = user?.referral_code || '';
    const shareUrl = `https://shop.thaiprompt.com/product/${product.id}${referralCode ? `?ref=${referralCode}` : ''}`;
    await Clipboard.setStringAsync(shareUrl);
    Alert.alert('คัดลอกแล้ว! ✓', 'ลิงก์สินค้าถูกคัดลอกไปยังคลิปบอร์ด');
  };

  // คำนวณ
  const commissionRate = product?.commission_rate || 5;
  const estimatedCommission = product ? (product.price * commissionRate / 100) : 0;
  const pv = product?.pv || Math.round((product?.price || 0) * 0.1);
  const hasDiscount = product?.original_price && product.original_price > product.price;
  const discountPercent = hasDiscount ? Math.round(((product.original_price! - product.price) / product.original_price!) * 100) : 0;
  const productImage = product?.image || product?.images?.[0] || `https://picsum.photos/seed/${id}/600/600`;
  const totalPrice = product ? product.price * quantity : 0;

  // Loading
  if (isLoading) {
    return (
      <View style={styles.container}>
        <LinearGradient colors={['#0F0F23', '#1A1A2E']} style={StyleSheet.absoluteFill} />
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <View style={styles.centerBox}>
          <ActivityIndicator size="large" color="#FF6B35" />
          <Text style={styles.loadingText}>กำลังโหลดข้อมูลสินค้า...</Text>
        </View>
      </View>
    );
  }

  // Error without product data
  if (!product) {
    return (
      <View style={styles.container}>
        <LinearGradient colors={['#0F0F23', '#1A1A2E']} style={StyleSheet.absoluteFill} />
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <View style={styles.centerBox}>
          <Text style={{ fontSize: 64 }}>⚠️</Text>
          <Text style={styles.errorText}>{error || 'ไม่พบสินค้า'}</Text>
          <Pressable style={styles.retryButton} onPress={fetchProduct}>
            <Text style={styles.retryButtonText}>🔄 ลองใหม่</Text>
          </Pressable>
          <Pressable style={styles.backButtonAlt} onPress={() => router.back()}>
            <Text style={styles.backButtonAltText}>← กลับ</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
      <LinearGradient colors={['#0F0F23', '#1A1A2E', '#16213E']} style={StyleSheet.absoluteFill} />

      {/* Header */}
      <View style={styles.header}>
        <Pressable style={styles.headerButton} onPress={() => router.back()}>
          <Text style={{ fontSize: 24, color: '#FFF' }}>←</Text>
        </Pressable>
        <View style={styles.headerRight}>
          <Pressable style={styles.headerButton} onPress={() => setIsFavorite(!isFavorite)}>
            <Text style={{ fontSize: 24 }}>{isFavorite ? '❤️' : '🤍'}</Text>
          </Pressable>
          <Pressable style={styles.headerButton} onPress={shareProduct}>
            <Text style={{ fontSize: 24 }}>📤</Text>
          </Pressable>
        </View>
      </View>

      <ScrollView style={styles.scrollView} showsVerticalScrollIndicator={false}>
        {/* Product Image */}
        <View style={styles.imageContainer}>
          <Image source={{ uri: productImage }} style={styles.productImage} resizeMode="cover" />

          {/* Discount Badge */}
          {hasDiscount && (
            <View style={styles.discountBadge}>
              <Text style={styles.discountText}>-{discountPercent}%</Text>
            </View>
          )}

          {/* PV Badge */}
          <View style={styles.pvBadge}>
            <Text style={{ fontSize: 12 }}>⭐</Text>
            <Text style={styles.pvBadgeText}>{pv} PV</Text>
          </View>
        </View>

        {/* Product Info */}
        <View style={styles.infoSection}>
          {/* Name & Price Card */}
          <View style={styles.card}>
            {product.category && (
              <View style={styles.categoryBadge}>
                <Text style={styles.categoryText}>{product.category}</Text>
              </View>
            )}

            <Text style={styles.productName}>{product.name}</Text>

            {/* Price Row */}
            <View style={styles.priceRow}>
              <Text style={styles.price}>{formatCurrency(product.price)}</Text>
              {hasDiscount && (
                <>
                  <Text style={styles.originalPrice}>{formatCurrency(product.original_price!)}</Text>
                  <View style={styles.discountTag}>
                    <Text style={styles.discountTagText}>ลด {discountPercent}%</Text>
                  </View>
                </>
              )}
            </View>

            {/* PV Info */}
            <View style={styles.pvInfo}>
              <LinearGradient colors={['rgba(255,215,0,0.15)', 'rgba(255,165,0,0.15)']} style={styles.pvGradient}>
                <Text style={{ fontSize: 18 }}>⭐</Text>
                <Text style={styles.pvText}>ได้รับ <Text style={styles.pvValue}>{pv} PV</Text> เมื่อซื้อสินค้านี้</Text>
              </LinearGradient>
            </View>

            {/* Commission Info */}
            {isAuthenticated && (
              <View style={styles.commissionBox}>
                <Text style={{ fontSize: 20 }}>💰</Text>
                <Text style={styles.commissionText}>
                  คอมมิชชั่น: <Text style={styles.commissionValue}>{formatCurrency(estimatedCommission)}</Text> ({commissionRate}%)
                </Text>
              </View>
            )}
          </View>

          {/* Quantity Selector */}
          <View style={styles.card}>
            <Text style={styles.cardTitle}>จำนวน</Text>
            <View style={styles.quantityRow}>
              <View style={styles.quantitySelector}>
                <Pressable
                  style={[styles.quantityButton, quantity <= 1 && styles.quantityButtonDisabled]}
                  onPress={() => quantity > 1 && setQuantity(quantity - 1)}
                  disabled={quantity <= 1}
                >
                  <Text style={{ fontSize: 24, color: quantity <= 1 ? '#4B5563' : '#FFF' }}>−</Text>
                </Pressable>
                <Text style={styles.quantityText}>{quantity}</Text>
                <Pressable style={styles.quantityButton} onPress={() => setQuantity(quantity + 1)}>
                  <Text style={{ fontSize: 24, color: '#FFF' }}>+</Text>
                </Pressable>
              </View>
              <View style={styles.totalBox}>
                <Text style={styles.totalLabel}>ราคารวม</Text>
                <Text style={styles.totalPrice}>{formatCurrency(totalPrice)}</Text>
                <Text style={styles.totalPV}>{pv * quantity} PV</Text>
              </View>
            </View>
          </View>

          {/* Description */}
          <View style={styles.card}>
            <Text style={styles.cardTitle}>รายละเอียดสินค้า</Text>
            <Text style={styles.description}>{product.description || 'ไม่มีรายละเอียดสินค้า'}</Text>
          </View>

          {/* Share Section */}
          <View style={styles.card}>
            <Text style={styles.cardTitle}>แชร์สินค้าและรับค่าคอมมิชชั่น!</Text>
            <View style={styles.shareRow}>
              <Pressable style={styles.shareButton} onPress={shareProduct}>
                <Text style={{ fontSize: 20 }}>📤</Text>
                <Text style={styles.shareButtonText}>แชร์</Text>
              </Pressable>
              <Pressable style={[styles.shareButton, styles.copyButton]} onPress={copyLink}>
                <Text style={{ fontSize: 20 }}>🔗</Text>
                <Text style={[styles.shareButtonText, styles.copyButtonText]}>คัดลอกลิงก์</Text>
              </Pressable>
            </View>
          </View>
        </View>

        <View style={{ height: 120 }} />
      </ScrollView>

      {/* Bottom Action Bar */}
      <View style={styles.bottomBar}>
        <Pressable style={styles.bottomIconButton} onPress={() => router.push('/cart')}>
          <Text style={{ fontSize: 24 }}>🛒</Text>
        </Pressable>
        <Pressable
          style={[styles.cartButton, isAddingToCart && styles.buttonDisabled]}
          onPress={handleAddToCart}
          disabled={isAddingToCart}
        >
          {isAddingToCart ? (
            <ActivityIndicator color="#FFF" size="small" />
          ) : (
            <>
              <Text style={{ fontSize: 18 }}>➕</Text>
              <Text style={styles.cartButtonText}>ตะกร้า</Text>
            </>
          )}
        </Pressable>
        <Pressable style={styles.buyButton} onPress={handleBuyNow}>
          <LinearGradient colors={['#FF6B35', '#FF8F5A']} style={styles.buyGradient}>
            <Text style={{ fontSize: 18 }}>💳</Text>
            <Text style={styles.buyButtonText}>ซื้อเลย</Text>
          </LinearGradient>
        </Pressable>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0F0F23' },
  centerBox: { flex: 1, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 24 },
  loadingText: { color: '#9CA3AF', marginTop: 16, fontSize: 15 },
  errorText: { fontSize: 18, fontWeight: 'bold', color: '#FFF', marginTop: 16, textAlign: 'center' },
  retryButton: { backgroundColor: '#3B82F6', paddingHorizontal: 24, paddingVertical: 12, borderRadius: 12, marginTop: 20 },
  retryButtonText: { color: '#FFF', fontWeight: 'bold', fontSize: 16 },
  backButtonAlt: { paddingHorizontal: 24, paddingVertical: 12, marginTop: 12 },
  backButtonAltText: { color: '#9CA3AF', fontSize: 16 },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 16, paddingTop: 56, paddingBottom: 12, position: 'absolute', top: 0, left: 0, right: 0, zIndex: 10 },
  headerButton: { width: 44, height: 44, borderRadius: 22, backgroundColor: 'rgba(0,0,0,0.5)', alignItems: 'center', justifyContent: 'center' },
  headerRight: { flexDirection: 'row', gap: 8 },
  scrollView: { flex: 1 },
  imageContainer: { width: SCREEN_WIDTH, height: SCREEN_WIDTH, backgroundColor: '#1F2937', position: 'relative' },
  productImage: { width: '100%', height: '100%' },
  discountBadge: { position: 'absolute', top: 70, left: 16, backgroundColor: '#EF4444', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 8 },
  discountText: { color: '#FFF', fontWeight: 'bold', fontSize: 14 },
  pvBadge: { position: 'absolute', top: 70, right: 16, backgroundColor: 'rgba(0,0,0,0.7)', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 8, flexDirection: 'row', alignItems: 'center', gap: 4 },
  pvBadgeText: { color: '#FFD700', fontWeight: 'bold', fontSize: 14 },
  infoSection: { paddingHorizontal: 16, paddingTop: 20 },
  card: { backgroundColor: 'rgba(255,255,255,0.05)', borderRadius: 20, padding: 20, marginBottom: 16, borderWidth: 1, borderColor: 'rgba(255,255,255,0.08)' },
  categoryBadge: { backgroundColor: 'rgba(59,130,246,0.2)', borderRadius: 16, paddingHorizontal: 12, paddingVertical: 4, alignSelf: 'flex-start', marginBottom: 8 },
  categoryText: { color: '#60A5FA', fontSize: 12, fontWeight: '600' },
  productName: { fontSize: 22, fontWeight: 'bold', color: '#FFF', marginBottom: 12 },
  priceRow: { flexDirection: 'row', alignItems: 'baseline', flexWrap: 'wrap', marginBottom: 16 },
  price: { fontSize: 28, fontWeight: 'bold', color: '#FF6B35' },
  originalPrice: { fontSize: 16, color: '#6B7280', textDecorationLine: 'line-through', marginLeft: 12 },
  discountTag: { backgroundColor: '#EF4444', paddingHorizontal: 8, paddingVertical: 2, borderRadius: 6, marginLeft: 8 },
  discountTagText: { color: '#FFF', fontSize: 12, fontWeight: 'bold' },
  pvInfo: { marginBottom: 12 },
  pvGradient: { flexDirection: 'row', alignItems: 'center', borderRadius: 12, padding: 12, gap: 8 },
  pvText: { color: '#FCD34D', fontSize: 14 },
  pvValue: { fontWeight: 'bold', color: '#FFD700' },
  commissionBox: { flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(16,185,129,0.1)', borderRadius: 12, padding: 12, borderWidth: 1, borderColor: 'rgba(16,185,129,0.3)' },
  commissionText: { color: '#34D399', marginLeft: 8, flex: 1 },
  commissionValue: { fontWeight: 'bold' },
  cardTitle: { fontSize: 16, fontWeight: 'bold', color: '#FFF', marginBottom: 12 },
  quantityRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  quantitySelector: { flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(255,255,255,0.1)', borderRadius: 12 },
  quantityButton: { width: 48, height: 48, alignItems: 'center', justifyContent: 'center' },
  quantityButtonDisabled: { opacity: 0.5 },
  quantityText: { fontSize: 20, fontWeight: 'bold', color: '#FFF', width: 48, textAlign: 'center' },
  totalBox: { alignItems: 'flex-end' },
  totalLabel: { fontSize: 12, color: '#9CA3AF' },
  totalPrice: { fontSize: 22, fontWeight: 'bold', color: '#FF6B35' },
  totalPV: { fontSize: 12, color: '#FFD700' },
  description: { color: '#D1D5DB', lineHeight: 22 },
  shareRow: { flexDirection: 'row', gap: 12 },
  shareButton: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(59,130,246,0.15)', borderRadius: 12, paddingVertical: 12, gap: 8 },
  shareButtonText: { color: '#3B82F6', fontWeight: '600' },
  copyButton: { backgroundColor: 'rgba(139,92,246,0.15)' },
  copyButtonText: { color: '#8B5CF6' },
  bottomBar: { position: 'absolute', bottom: 0, left: 0, right: 0, flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingBottom: 34, paddingTop: 16, backgroundColor: 'rgba(15,15,35,0.98)', borderTopWidth: 1, borderTopColor: 'rgba(255,255,255,0.1)', gap: 10 },
  bottomIconButton: { width: 52, height: 52, borderRadius: 14, backgroundColor: 'rgba(255,255,255,0.1)', alignItems: 'center', justifyContent: 'center' },
  cartButton: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(59,130,246,0.2)', paddingHorizontal: 16, paddingVertical: 14, borderRadius: 14, gap: 6, borderWidth: 1, borderColor: 'rgba(59,130,246,0.4)' },
  cartButtonText: { color: '#3B82F6', fontWeight: '600' },
  buyButton: { flex: 1, borderRadius: 14, overflow: 'hidden' },
  buttonDisabled: { opacity: 0.6 },
  buyGradient: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 16, gap: 8 },
  buyButtonText: { color: '#FFF', fontSize: 16, fontWeight: 'bold' },
});
