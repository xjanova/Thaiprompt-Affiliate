/**
 * Product Detail Screen - Premium Version with Wallet Payment
 * แก้ปัญหาจอขาว + เพิ่มระบบชำระเงินผ่าน Wallet
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
  Modal,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useLocalSearchParams } from 'expo-router';
import * as Clipboard from 'expo-clipboard';
import { useAuthStore } from '@/stores/authStore';
import { formatCurrency, API_BASE_URL } from '@/constants';
import { getProduct, addToCart, createOrder, getWallet } from '@/services/api';

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

  const [product, setProduct] = useState<Product | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState('');
  const [quantity, setQuantity] = useState(1);
  const [isAddingToCart, setIsAddingToCart] = useState(false);
  const [isFavorite, setIsFavorite] = useState(false);

  // Payment modal
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [walletBalance, setWalletBalance] = useState(0);
  const [isProcessingPayment, setIsProcessingPayment] = useState(false);

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

  // เพิ่มลงตะกร้า
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
      const response = await addToCart(product.id, quantity);

      if (response?.success) {
        Alert.alert('สำเร็จ! ✓', `เพิ่ม ${product.name} ลงตะกร้าแล้ว`, [
          { text: 'ดูตะกร้า', onPress: () => router.push('/shopping') },
          { text: 'ช้อปต่อ', style: 'cancel' },
        ]);
      } else {
        Alert.alert('เกิดข้อผิดพลาด', response?.message || 'ไม่สามารถเพิ่มสินค้าได้');
      }
    } catch (err: any) {
      Alert.alert('เกิดข้อผิดพลาด', err.message || 'ไม่สามารถเพิ่มสินค้าได้');
    } finally {
      setIsAddingToCart(false);
    }
  };

  // ซื้อเลย (เปิด payment modal)
  const handleBuyNow = async () => {
    if (!isAuthenticated) {
      Alert.alert('กรุณาเข้าสู่ระบบ', 'คุณต้องเข้าสู่ระบบก่อนซื้อสินค้า', [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: 'เข้าสู่ระบบ', onPress: () => router.push('/login') },
      ]);
      return;
    }

    // โหลดยอดเงินใหม่ก่อนเปิด modal
    await fetchWalletBalance();
    setShowPaymentModal(true);
  };

  // ชำระเงินผ่าน Wallet
  const handleWalletPayment = async () => {
    if (!product) return;

    const totalAmount = product.price * quantity;

    if (walletBalance < totalAmount) {
      Alert.alert(
        'ยอดเงินไม่พอ',
        `ยอดเงินในกระเป๋า: ${formatCurrency(walletBalance)}\nต้องชำระ: ${formatCurrency(totalAmount)}\n\nต้องการเติมเงินหรือไม่?`,
        [
          { text: 'ยกเลิก', style: 'cancel' },
          { text: 'เติมเงิน', onPress: () => {
            setShowPaymentModal(false);
            router.push('/wallet-topup');
          }},
        ]
      );
      return;
    }

    setIsProcessingPayment(true);

    try {
      const response = await createOrder({
        items: [{ product_id: product.id, quantity }],
        payment_method: 'wallet',
      });

      if (response?.success) {
        setShowPaymentModal(false);
        Alert.alert(
          'ซื้อสำเร็จ! ✓',
          `สั่งซื้อ ${product.name} x${quantity} สำเร็จแล้ว\n\nหักจากกระเป๋า: ${formatCurrency(totalAmount)}`,
          [{ text: 'ดูคำสั่งซื้อ', onPress: () => router.push('/commissions') }]
        );
        // อัพเดทยอดเงิน
        fetchWalletBalance();
      } else {
        Alert.alert('เกิดข้อผิดพลาด', response?.message || 'ไม่สามารถสร้างคำสั่งซื้อได้');
      }
    } catch (err: any) {
      Alert.alert('เกิดข้อผิดพลาด', err.message || 'ไม่สามารถชำระเงินได้');
    } finally {
      setIsProcessingPayment(false);
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
        <Pressable style={styles.bottomIconButton} onPress={() => router.push('/shopping')}>
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

      {/* Payment Modal */}
      <Modal visible={showPaymentModal} transparent animationType="slide" onRequestClose={() => setShowPaymentModal(false)}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            {/* Header */}
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>ชำระเงิน</Text>
              <Pressable onPress={() => setShowPaymentModal(false)}>
                <Text style={{ fontSize: 24 }}>✕</Text>
              </Pressable>
            </View>

            {/* Order Summary */}
            <View style={styles.orderSummary}>
              <Text style={styles.summaryTitle}>สรุปคำสั่งซื้อ</Text>
              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>{product.name} x{quantity}</Text>
                <Text style={styles.summaryValue}>{formatCurrency(totalPrice)}</Text>
              </View>
              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>PV ที่จะได้รับ</Text>
                <Text style={[styles.summaryValue, { color: '#FFD700' }]}>⭐ {pv * quantity}</Text>
              </View>
              <View style={styles.summaryDivider} />
              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabelBold}>ยอดชำระทั้งหมด</Text>
                <Text style={styles.summaryTotal}>{formatCurrency(totalPrice)}</Text>
              </View>
            </View>

            {/* Wallet Balance */}
            <View style={styles.walletSection}>
              <View style={styles.walletHeader}>
                <Text style={{ fontSize: 24 }}>💰</Text>
                <Text style={styles.walletTitle}>ชำระผ่านกระเป๋าเงิน</Text>
              </View>
              <View style={styles.walletBalance}>
                <Text style={styles.balanceLabel}>ยอดเงินคงเหลือ</Text>
                <Text style={[
                  styles.balanceValue,
                  walletBalance < totalPrice && { color: '#EF4444' }
                ]}>
                  {formatCurrency(walletBalance)}
                </Text>
              </View>
              {walletBalance < totalPrice && (
                <View style={styles.insufficientWarning}>
                  <Text style={{ fontSize: 16 }}>⚠️</Text>
                  <Text style={styles.insufficientText}>ยอดเงินไม่เพียงพอ</Text>
                </View>
              )}
            </View>

            {/* Payment Button */}
            <Pressable
              style={[
                styles.payButton,
                (isProcessingPayment || walletBalance < totalPrice) && styles.payButtonDisabled
              ]}
              onPress={handleWalletPayment}
              disabled={isProcessingPayment || walletBalance < totalPrice}
            >
              {isProcessingPayment ? (
                <ActivityIndicator color="#FFF" />
              ) : walletBalance < totalPrice ? (
                <>
                  <Text style={{ fontSize: 20 }}>➕</Text>
                  <Text style={styles.payButtonText}>เติมเงิน</Text>
                </>
              ) : (
                <>
                  <Text style={{ fontSize: 20 }}>✓</Text>
                  <Text style={styles.payButtonText}>ยืนยันชำระเงิน</Text>
                </>
              )}
            </Pressable>

            {/* Top up link */}
            {walletBalance < totalPrice && (
              <Pressable
                style={styles.topupLink}
                onPress={() => {
                  setShowPaymentModal(false);
                  router.push('/wallet-topup');
                }}
              >
                <Text style={styles.topupLinkText}>ไปเติมเงิน →</Text>
              </Pressable>
            )}
          </View>
        </View>
      </Modal>
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

  // Modal styles
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.7)', justifyContent: 'flex-end' },
  modalContent: { backgroundColor: '#1F2937', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 24 },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 },
  modalTitle: { fontSize: 20, fontWeight: 'bold', color: '#FFF' },
  orderSummary: { backgroundColor: 'rgba(255,255,255,0.05)', borderRadius: 16, padding: 16, marginBottom: 16 },
  summaryTitle: { fontSize: 14, fontWeight: '600', color: '#9CA3AF', marginBottom: 12 },
  summaryRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 },
  summaryLabel: { color: '#D1D5DB', fontSize: 14 },
  summaryValue: { color: '#FFF', fontSize: 14, fontWeight: '500' },
  summaryDivider: { height: 1, backgroundColor: 'rgba(255,255,255,0.1)', marginVertical: 12 },
  summaryLabelBold: { color: '#FFF', fontSize: 15, fontWeight: '600' },
  summaryTotal: { color: '#FF6B35', fontSize: 20, fontWeight: 'bold' },
  walletSection: { backgroundColor: 'rgba(16,185,129,0.1)', borderRadius: 16, padding: 16, marginBottom: 16, borderWidth: 1, borderColor: 'rgba(16,185,129,0.2)' },
  walletHeader: { flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 12 },
  walletTitle: { color: '#10B981', fontSize: 16, fontWeight: '600' },
  walletBalance: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  balanceLabel: { color: '#9CA3AF', fontSize: 14 },
  balanceValue: { color: '#10B981', fontSize: 20, fontWeight: 'bold' },
  insufficientWarning: { flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 10, backgroundColor: 'rgba(239,68,68,0.1)', padding: 10, borderRadius: 8 },
  insufficientText: { color: '#EF4444', fontSize: 13 },
  payButton: { backgroundColor: '#10B981', flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 16, borderRadius: 14, gap: 8 },
  payButtonDisabled: { backgroundColor: '#374151' },
  payButtonText: { color: '#FFF', fontSize: 16, fontWeight: 'bold' },
  topupLink: { alignItems: 'center', paddingVertical: 16 },
  topupLinkText: { color: '#3B82F6', fontSize: 15, fontWeight: '500' },
});
