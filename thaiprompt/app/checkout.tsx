/**
 * Checkout Screen - Native Payment Flow
 * Premium Design พร้อมการชำระเงินภายในแอพ
 *
 * V2: Native Payment Flow
 * - ชำระผ่าน Wallet (หักทันที)
 * - ชำระผ่าน QR Code / Bank Transfer (แสดง QR + Polling)
 * - COD (เก็บเงินปลายทาง)
 * - ไม่เปิด WebBrowser
 */

import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  TextInput,
  StyleSheet,
  StatusBar,
  Alert,
  ActivityIndicator,
  Modal,
  Image,
  Dimensions,
} from 'react-native';
import { router, Stack, useFocusEffect } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { useCartStore } from '@/stores/cartStore';
import { useAuthStore } from '@/stores/authStore';
import { formatCurrency, API_BASE_URL } from '@/constants';
import {
  checkout,
  getWallet,
  getPaymentMethods,
  getPaymentStatus,
  type CheckoutRequest,
} from '@/services/api';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

// Payment Method Type
interface PaymentMethod {
  id: string;
  name: string;
  icon: string;
  description: string;
  category: string;
  isAvailable: boolean;
}

// Payment Steps
type PaymentStep = 'summary' | 'processing' | 'qrcode' | 'success' | 'failed';

// Default Payment Methods
const DEFAULT_PAYMENT_METHODS: PaymentMethod[] = [
  { id: 'wallet', name: 'กระเป๋าเงิน TP', icon: '💰', description: 'ชำระด้วยยอดเงินในกระเป๋า', category: 'wallet', isAvailable: true },
  { id: 'promptpay', name: 'พร้อมเพย์', icon: '📱', description: 'สแกน QR Code ชำระผ่านแอพธนาคาร', category: 'qrcode', isAvailable: true },
  { id: 'bank', name: 'โอนเงินธนาคาร', icon: '🏦', description: 'โอนเงินเข้าบัญชีธนาคาร', category: 'bank', isAvailable: true },
  { id: 'cod', name: 'เก็บเงินปลายทาง', icon: '📦', description: 'ชำระเงินเมื่อรับสินค้า', category: 'cod', isAvailable: true },
];

export default function CheckoutScreen() {
  const {
    items,
    totalItems,
    totalPrice,
    totalPV,
    summary,
    isLoading: isCartLoading,
    initialize: initializeCart,
    clearCart,
    checkout: cartCheckout,
  } = useCartStore();
  const { user, isAuthenticated } = useAuthStore();

  // States
  const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>(DEFAULT_PAYMENT_METHODS);
  const [selectedPayment, setSelectedPayment] = useState<string>('wallet');
  const [promoCode, setPromoCode] = useState('');
  const [discount, setDiscount] = useState(0);
  const [isApplyingPromo, setIsApplyingPromo] = useState(false);

  // Wallet
  const [walletBalance, setWalletBalance] = useState(0);
  const [isLoadingWallet, setIsLoadingWallet] = useState(false);

  // Payment Flow
  const [paymentStep, setPaymentStep] = useState<PaymentStep>('summary');
  const [isProcessing, setIsProcessing] = useState(false);
  const [paymentData, setPaymentData] = useState<any>(null);
  const [orderId, setOrderId] = useState<number | null>(null);
  const [orderNumber, setOrderNumber] = useState<string | null>(null);

  // Polling
  const pollingRef = useRef<NodeJS.Timeout | null>(null);
  const [pollingCount, setPollingCount] = useState(0);
  const MAX_POLLING_COUNT = 120; // 6 นาที (3 วินาที x 120)

  // Calculate
  const shippingFee = summary.shippingFee;
  const finalTotal = totalPrice + shippingFee - discount;

  // โหลดยอด wallet
  const loadWalletBalance = useCallback(async () => {
    if (!isAuthenticated) return;

    setIsLoadingWallet(true);
    try {
      const response = await getWallet();
      if (response?.success && response.data) {
        setWalletBalance(response.data.balance || 0);
      }
    } catch (error) {
      console.error('Load wallet error:', error);
    } finally {
      setIsLoadingWallet(false);
    }
  }, [isAuthenticated]);

  // โหลด payment methods
  const loadPaymentMethods = useCallback(async () => {
    try {
      const response = await getPaymentMethods();
      if (response?.success && response.methods) {
        // Map และแปลงข้อมูลจาก server
        const serverMethods: PaymentMethod[] = response.methods
          .filter((m: any) => m.enabled !== false)  // กรองเฉพาะที่เปิดใช้งาน
          .map((m: any) => ({
            // ถ้า id เป็น cash_on_delivery ให้เปลี่ยนเป็น cod เพื่อความสอดคล้อง
            id: m.id === 'cash_on_delivery' ? 'cod' : m.id,
            name: m.id === 'cash_on_delivery' ? 'เก็บเงินปลายทาง' : m.name,
            icon: m.icon || '💳',
            description: m.description || '',
            category: m.category || 'other',
            isAvailable: m.enabled !== false,
          }));

        // ตรวจสอบว่ามี methods ที่จำเป็นหรือไม่
        const hasWallet = serverMethods.some((m) => m.id === 'wallet');
        const hasCod = serverMethods.some((m) => m.id === 'cod');
        const hasPromptPay = serverMethods.some((m) => m.id === 'promptpay');
        const hasBank = serverMethods.some((m) => m.id === 'bank_transfer' || m.id === 'bank');

        // สร้างรายการ methods โดยไม่ให้ซ้ำ
        const allMethods: PaymentMethod[] = [];

        // เพิ่ม wallet ถ้าไม่มี
        if (!hasWallet) {
          allMethods.push(DEFAULT_PAYMENT_METHODS[0]); // wallet
        }

        // เพิ่ม methods จาก server
        allMethods.push(...serverMethods);

        // เพิ่ม promptpay ถ้าไม่มี
        if (!hasPromptPay) {
          allMethods.push(DEFAULT_PAYMENT_METHODS[1]); // promptpay
        }

        // เพิ่ม bank ถ้าไม่มี
        if (!hasBank) {
          allMethods.push(DEFAULT_PAYMENT_METHODS[2]); // bank
        }

        // เพิ่ม COD ถ้าไม่มี
        if (!hasCod) {
          allMethods.push(DEFAULT_PAYMENT_METHODS[3]); // cod
        }

        // กรอง duplicates โดยใช้ id
        const uniqueMethods = allMethods.filter(
          (method, index, self) =>
            index === self.findIndex((m) => m.id === method.id)
        );

        setPaymentMethods(uniqueMethods.length > 0 ? uniqueMethods : DEFAULT_PAYMENT_METHODS);
      } else {
        // ถ้า API ไม่สำเร็จ ใช้ default
        setPaymentMethods(DEFAULT_PAYMENT_METHODS);
      }
    } catch (error) {
      console.error('Load payment methods error:', error);
      // ใช้ default methods เมื่อเกิด error
      setPaymentMethods(DEFAULT_PAYMENT_METHODS);
    }
  }, []);

  // Initial load - โหลด cart, wallet และ payment methods
  useFocusEffect(
    useCallback(() => {
      // ⭐ CRITICAL: โหลด cart จาก storage ก่อนแสดงผล
      initializeCart();
      loadWalletBalance();
      loadPaymentMethods();

      // Cleanup polling on unmount
      return () => {
        if (pollingRef.current) {
          clearInterval(pollingRef.current);
          pollingRef.current = null;
        }
      };
    }, [initializeCart, loadWalletBalance, loadPaymentMethods])
  );

  // Apply promo code
  const handleApplyPromo = async () => {
    if (!promoCode.trim()) {
      Alert.alert('กรุณากรอกโค้ด', 'กรุณากรอกโค้ดส่วนลดก่อน');
      return;
    }

    setIsApplyingPromo(true);
    try {
      // Mock promo codes - ในอนาคตเรียก API จริง
      if (promoCode.toUpperCase() === 'FIRST10') {
        setDiscount(totalPrice * 0.1);
        Alert.alert('สำเร็จ!', 'ใช้โค้ดส่วนลด 10% แล้ว');
      } else if (promoCode.toUpperCase() === 'FREE50') {
        setDiscount(50);
        Alert.alert('สำเร็จ!', 'ใช้โค้ดส่วนลด ฿50 แล้ว');
      } else {
        Alert.alert('ไม่พบโค้ด', 'โค้ดส่วนลดไม่ถูกต้องหรือหมดอายุแล้ว');
      }
    } finally {
      setIsApplyingPromo(false);
    }
  };

  // Check payment status (polling)
  const checkPaymentStatus = useCallback(async (paymentId: number) => {
    try {
      const response = await getPaymentStatus(paymentId);
      console.log('💳 Payment status:', response);

      if (response?.success && response.data) {
        const status = response.data.status;

        if (status === 'completed' || status === 'paid') {
          // ชำระเงินสำเร็จ
          if (pollingRef.current) {
            clearInterval(pollingRef.current);
            pollingRef.current = null;
          }
          setPaymentStep('success');
          clearCart();
        } else if (status === 'failed' || status === 'expired' || status === 'cancelled') {
          // ชำระเงินล้มเหลว
          if (pollingRef.current) {
            clearInterval(pollingRef.current);
            pollingRef.current = null;
          }
          setPaymentStep('failed');
        }
      }
    } catch (error) {
      console.error('Check payment status error:', error);
    }

    setPollingCount((prev) => {
      if (prev >= MAX_POLLING_COUNT) {
        if (pollingRef.current) {
          clearInterval(pollingRef.current);
          pollingRef.current = null;
        }
        setPaymentStep('failed');
      }
      return prev + 1;
    });
  }, [clearCart]);

  // Start polling
  const startPolling = useCallback((paymentId: number) => {
    if (pollingRef.current) {
      clearInterval(pollingRef.current);
    }

    setPollingCount(0);
    pollingRef.current = setInterval(() => {
      checkPaymentStatus(paymentId);
    }, 3000);
  }, [checkPaymentStatus]);

  // Place order
  const handlePlaceOrder = async () => {
    if (!isAuthenticated) {
      Alert.alert('กรุณาเข้าสู่ระบบ', 'คุณต้องเข้าสู่ระบบก่อนทำการสั่งซื้อ', [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: 'เข้าสู่ระบบ', onPress: () => router.push('/login') },
      ]);
      return;
    }

    // Check wallet balance if wallet payment
    if (selectedPayment === 'wallet' && walletBalance < finalTotal) {
      Alert.alert(
        'ยอดเงินไม่พอ',
        `ยอดเงินในกระเป๋า: ${formatCurrency(walletBalance)}\nต้องชำระ: ${formatCurrency(finalTotal)}\n\nต้องการเติมเงินหรือไม่?`,
        [
          { text: 'ยกเลิก', style: 'cancel' },
          { text: 'เติมเงิน', onPress: () => router.push('/wallet-topup') },
        ]
      );
      return;
    }

    setIsProcessing(true);
    setPaymentStep('processing');

    try {
      // เตรียมข้อมูล checkout
      const checkoutData: CheckoutRequest = {
        payment_method: selectedPayment as any,
        promo_code: promoCode || undefined,
      };

      console.log('🛒 Checkout with:', checkoutData);

      const response = await checkout(checkoutData);
      console.log('🛒 Checkout response:', response);

      if (response?.success && response.data) {
        setOrderId(response.data.orderId);
        setOrderNumber(response.data.orderNumber);

        // ตรวจสอบ payment method
        if (selectedPayment === 'wallet') {
          // Wallet - หักเงินทันที ไปหน้า success
          setPaymentStep('success');
          clearCart();
        } else if (selectedPayment === 'cod') {
          // COD - ไปหน้า success
          setPaymentStep('success');
          clearCart();
        } else {
          // QR Code / Bank Transfer - แสดง QR และ polling
          setPaymentData(response.data);
          setPaymentStep('qrcode');

          // Start polling
          if (response.data.paymentId) {
            startPolling(response.data.paymentId);
          }
        }
      } else {
        // Error
        Alert.alert('เกิดข้อผิดพลาด', response?.message || 'ไม่สามารถสร้างคำสั่งซื้อได้');
        setPaymentStep('summary');
      }
    } catch (error: any) {
      console.error('Checkout error:', error);
      Alert.alert('เกิดข้อผิดพลาด', error.message || 'ไม่สามารถดำเนินการได้');
      setPaymentStep('summary');
    } finally {
      setIsProcessing(false);
    }
  };

  // Cancel payment
  const handleCancelPayment = () => {
    Alert.alert(
      'ยกเลิกการชำระเงิน',
      'คุณต้องการยกเลิกการชำระเงินหรือไม่?',
      [
        { text: 'ไม่', style: 'cancel' },
        {
          text: 'ยกเลิก',
          style: 'destructive',
          onPress: () => {
            if (pollingRef.current) {
              clearInterval(pollingRef.current);
              pollingRef.current = null;
            }
            setPaymentStep('summary');
            setPaymentData(null);
          },
        },
      ]
    );
  };

  // Loading cart state - รอ cart โหลดเสร็จก่อนตรวจสอบ
  if (isCartLoading && paymentStep === 'summary') {
    return (
      <View style={styles.container}>
        <LinearGradient colors={['#0F0F23', '#1A1A2E', '#16213E']} style={StyleSheet.absoluteFill} />
        <Stack.Screen
          options={{
            headerShown: true,
            title: 'ชำระเงิน',
            headerStyle: { backgroundColor: '#0F0F23' },
            headerTintColor: '#FFFFFF',
          }}
        />
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={styles.processingText}>กำลังโหลดตะกร้า...</Text>
        </View>
      </View>
    );
  }

  // Empty cart - แสดงเมื่อโหลด cart เสร็จแล้วและไม่มีสินค้า
  if (items.length === 0 && paymentStep === 'summary' && !isCartLoading) {
    return (
      <View style={styles.container}>
        <LinearGradient colors={['#0F0F23', '#1A1A2E', '#16213E']} style={StyleSheet.absoluteFill} />
        <Stack.Screen
          options={{
            headerShown: true,
            title: 'ชำระเงิน',
            headerStyle: { backgroundColor: '#0F0F23' },
            headerTintColor: '#FFFFFF',
          }}
        />
        <View style={styles.emptyContainer}>
          <Text style={styles.emptyIcon}>🛒</Text>
          <Text style={styles.emptyText}>ไม่มีสินค้าในตะกร้า</Text>
          <Pressable style={styles.shopButton} onPress={() => router.push('/shopping')}>
            <Text style={styles.shopButtonText}>ไปช้อปปิ้ง</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  // QR Code / Bank Transfer Step
  if (paymentStep === 'qrcode') {
    return (
      <View style={styles.container}>
        <LinearGradient colors={['#0F0F23', '#1A1A2E', '#16213E']} style={StyleSheet.absoluteFill} />
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <Stack.Screen
          options={{
            headerShown: true,
            title: 'ชำระเงิน',
            headerStyle: { backgroundColor: '#0F0F23' },
            headerTintColor: '#FFFFFF',
            headerLeft: () => null, // ปิดปุ่มกลับ
          }}
        />

        <ScrollView contentContainerStyle={styles.qrContainer}>
          {/* QR Code Card */}
          <View style={styles.qrCard}>
            <Text style={styles.qrTitle}>
              {selectedPayment === 'promptpay' ? '📱 สแกน QR Code' : '🏦 โอนเงินธนาคาร'}
            </Text>

            <View style={styles.qrAmountRow}>
              <Text style={styles.qrAmountLabel}>ยอดที่ต้องชำระ</Text>
              <Text style={styles.qrAmount}>{formatCurrency(finalTotal)}</Text>
            </View>

            {/* QR Code */}
            {paymentData?.qrCodeUrl ? (
              <View style={styles.qrImageContainer}>
                <Image
                  source={{ uri: paymentData.qrCodeUrl }}
                  style={styles.qrImage}
                  resizeMode="contain"
                />
              </View>
            ) : paymentData?.qrCode ? (
              <View style={styles.qrImageContainer}>
                <Image
                  source={{ uri: `data:image/png;base64,${paymentData.qrCode}` }}
                  style={styles.qrImage}
                  resizeMode="contain"
                />
              </View>
            ) : (
              <View style={styles.qrPlaceholder}>
                <Text style={styles.qrPlaceholderText}>QR Code</Text>
              </View>
            )}

            {/* Bank Info (for bank transfer) */}
            {selectedPayment === 'bank' && paymentData?.bankInfo && (
              <View style={styles.bankInfo}>
                <Text style={styles.bankInfoTitle}>ข้อมูลบัญชีธนาคาร</Text>
                <View style={styles.bankInfoRow}>
                  <Text style={styles.bankInfoLabel}>ธนาคาร</Text>
                  <Text style={styles.bankInfoValue}>{paymentData.bankInfo.bankName}</Text>
                </View>
                <View style={styles.bankInfoRow}>
                  <Text style={styles.bankInfoLabel}>เลขบัญชี</Text>
                  <Text style={styles.bankInfoValue}>{paymentData.bankInfo.accountNumber}</Text>
                </View>
                <View style={styles.bankInfoRow}>
                  <Text style={styles.bankInfoLabel}>ชื่อบัญชี</Text>
                  <Text style={styles.bankInfoValue}>{paymentData.bankInfo.accountName}</Text>
                </View>
              </View>
            )}

            {/* Reference */}
            {paymentData?.reference && (
              <View style={styles.referenceBox}>
                <Text style={styles.referenceLabel}>รหัสอ้างอิง</Text>
                <Text style={styles.referenceValue}>{paymentData.reference}</Text>
              </View>
            )}

            {/* Order Number */}
            {orderNumber && (
              <Text style={styles.orderNumberText}>หมายเลขคำสั่งซื้อ: {orderNumber}</Text>
            )}

            {/* Polling Status */}
            <View style={styles.pollingStatus}>
              <ActivityIndicator size="small" color="#3B82F6" />
              <Text style={styles.pollingText}>
                กำลังรอการชำระเงิน... ({Math.floor((MAX_POLLING_COUNT - pollingCount) * 3 / 60)} นาที)
              </Text>
            </View>
          </View>

          {/* Instructions */}
          <View style={styles.instructionCard}>
            <Text style={styles.instructionTitle}>📋 วิธีชำระเงิน</Text>
            <Text style={styles.instructionText}>
              1. เปิดแอพธนาคารบนมือถือ{'\n'}
              2. เลือกสแกน QR Code หรือโอนเงิน{'\n'}
              3. สแกน QR Code ด้านบน หรือใส่ข้อมูลบัญชี{'\n'}
              4. ยืนยันการชำระเงินตามยอดที่แสดง{'\n'}
              5. รอระบบตรวจสอบอัตโนมัติ (ประมาณ 1-3 นาที)
            </Text>
          </View>

          {/* Cancel Button */}
          <Pressable style={styles.cancelButton} onPress={handleCancelPayment}>
            <Text style={styles.cancelButtonText}>ยกเลิกการชำระเงิน</Text>
          </Pressable>
        </ScrollView>
      </View>
    );
  }

  // Processing Step
  if (paymentStep === 'processing') {
    return (
      <View style={styles.container}>
        <LinearGradient colors={['#0F0F23', '#1A1A2E', '#16213E']} style={StyleSheet.absoluteFill} />
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <Stack.Screen
          options={{
            headerShown: true,
            title: 'กำลังดำเนินการ',
            headerStyle: { backgroundColor: '#0F0F23' },
            headerTintColor: '#FFFFFF',
            headerLeft: () => null,
          }}
        />
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={styles.processingText}>กำลังสร้างคำสั่งซื้อ...</Text>
        </View>
      </View>
    );
  }

  // Success Step
  if (paymentStep === 'success') {
    return (
      <View style={styles.container}>
        <LinearGradient colors={['#0F0F23', '#1A1A2E', '#16213E']} style={StyleSheet.absoluteFill} />
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <Stack.Screen
          options={{
            headerShown: true,
            title: 'สำเร็จ',
            headerStyle: { backgroundColor: '#0F0F23' },
            headerTintColor: '#FFFFFF',
            headerLeft: () => null,
          }}
        />
        <View style={styles.centerContainer}>
          <LinearGradient
            colors={['#10B981', '#059669']}
            style={styles.successIcon}
          >
            <Text style={styles.successEmoji}>✅</Text>
          </LinearGradient>
          <Text style={styles.successTitle}>สั่งซื้อสำเร็จ!</Text>
          <Text style={styles.successSubtitle}>
            คำสั่งซื้อของคุณได้รับการยืนยันแล้ว
          </Text>
          {orderNumber && (
            <Text style={styles.successOrderNumber}>
              หมายเลขคำสั่งซื้อ: {orderNumber}
            </Text>
          )}

          <View style={styles.successPvBox}>
            <Text style={styles.successPvText}>⭐ คุณได้รับ {totalPV} PV</Text>
          </View>

          <View style={styles.successButtons}>
            <Pressable
              style={styles.successButton}
              onPress={() => router.replace('/orders')}
            >
              <Text style={styles.successButtonText}>📋 ดูคำสั่งซื้อ</Text>
            </Pressable>
            <Pressable
              style={[styles.successButton, styles.successButtonPrimary]}
              onPress={() => router.replace('/(tabs)')}
            >
              <LinearGradient
                colors={['#3B82F6', '#2563EB']}
                style={styles.successButtonGradient}
              >
                <Text style={styles.successButtonTextPrimary}>🏠 กลับหน้าหลัก</Text>
              </LinearGradient>
            </Pressable>
          </View>
        </View>
      </View>
    );
  }

  // Failed Step
  if (paymentStep === 'failed') {
    return (
      <View style={styles.container}>
        <LinearGradient colors={['#0F0F23', '#1A1A2E', '#16213E']} style={StyleSheet.absoluteFill} />
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <Stack.Screen
          options={{
            headerShown: true,
            title: 'ไม่สำเร็จ',
            headerStyle: { backgroundColor: '#0F0F23' },
            headerTintColor: '#FFFFFF',
            headerLeft: () => null,
          }}
        />
        <View style={styles.centerContainer}>
          <View style={styles.failedIcon}>
            <Text style={styles.failedEmoji}>❌</Text>
          </View>
          <Text style={styles.failedTitle}>การชำระเงินไม่สำเร็จ</Text>
          <Text style={styles.failedSubtitle}>
            ไม่พบการชำระเงิน หรือหมดเวลารอ{'\n'}กรุณาลองใหม่อีกครั้ง
          </Text>

          <Pressable
            style={styles.retryButton}
            onPress={() => {
              setPaymentStep('summary');
              setPaymentData(null);
            }}
          >
            <Text style={styles.retryButtonText}>🔄 ลองใหม่</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  // Summary Step (Default)
  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
      <LinearGradient colors={['#0F0F23', '#1A1A2E', '#16213E']} style={StyleSheet.absoluteFill} />

      <Stack.Screen
        options={{
          headerShown: true,
          title: 'ชำระเงิน',
          headerStyle: { backgroundColor: '#0F0F23' },
          headerTintColor: '#FFFFFF',
          headerLeft: () => (
            <Pressable onPress={() => router.back()} style={styles.headerButton}>
              <Text style={styles.headerIcon}>⬅️</Text>
            </Pressable>
          ),
        }}
      />

      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {/* Shipping Address */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionIcon}>📍</Text>
            <Text style={styles.sectionTitle}>ที่อยู่จัดส่ง</Text>
          </View>
          <Pressable style={styles.addressCard}>
            <Text style={styles.addressName}>{user?.name || 'ชื่อผู้รับ'}</Text>
            <Text style={styles.addressPhone}>{user?.phone || '0xx-xxx-xxxx'}</Text>
            <Text style={styles.addressText}>
              {user?.address || 'กรุณาเพิ่มที่อยู่ในโปรไฟล์'}
            </Text>
            <Text style={styles.addressChange}>เปลี่ยนที่อยู่ ➡️</Text>
          </Pressable>
        </View>

        {/* Order Summary */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionIcon}>📦</Text>
            <Text style={styles.sectionTitle}>สรุปคำสั่งซื้อ ({totalItems} ชิ้น)</Text>
          </View>
          <View style={styles.summaryCard}>
            {items.map((item) => (
              <View key={item.id} style={styles.summaryItem}>
                <Text style={styles.summaryItemName} numberOfLines={1}>
                  {item.productName || item.product.name}
                </Text>
                <Text style={styles.summaryItemQty}>x{item.quantity}</Text>
                <Text style={styles.summaryItemPrice}>
                  {formatCurrency(item.subtotal)}
                </Text>
              </View>
            ))}
          </View>
        </View>

        {/* Promo Code */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionIcon}>🎟️</Text>
            <Text style={styles.sectionTitle}>โค้ดส่วนลด</Text>
          </View>
          <View style={styles.promoRow}>
            <TextInput
              value={promoCode}
              onChangeText={setPromoCode}
              placeholder="ใส่โค้ดส่วนลด"
              placeholderTextColor="#6B7280"
              style={styles.promoInput}
            />
            <Pressable
              style={[styles.promoButton, isApplyingPromo && styles.buttonDisabled]}
              onPress={handleApplyPromo}
              disabled={isApplyingPromo}
            >
              {isApplyingPromo ? (
                <ActivityIndicator size="small" color="#FFF" />
              ) : (
                <Text style={styles.promoButtonText}>ใช้โค้ด</Text>
              )}
            </Pressable>
          </View>
        </View>

        {/* Payment Method */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionIcon}>💳</Text>
            <Text style={styles.sectionTitle}>วิธีชำระเงิน</Text>
          </View>
          {paymentMethods.map((method) => (
            <Pressable
              key={method.id}
              style={[
                styles.paymentOption,
                selectedPayment === method.id && styles.paymentOptionSelected,
                !method.isAvailable && styles.paymentOptionDisabled,
              ]}
              onPress={() => method.isAvailable && setSelectedPayment(method.id)}
              disabled={!method.isAvailable}
            >
              <Text style={styles.paymentIcon}>{method.icon}</Text>
              <View style={styles.paymentInfo}>
                <Text style={styles.paymentName}>{method.name}</Text>
                <Text style={styles.paymentDesc}>
                  {method.id === 'wallet'
                    ? `ยอดเงิน: ${formatCurrency(walletBalance)}`
                    : method.description}
                </Text>
                {method.id === 'wallet' && walletBalance < finalTotal && (
                  <Text style={styles.insufficientText}>⚠️ ยอดเงินไม่พอ</Text>
                )}
              </View>
              <View
                style={[
                  styles.paymentRadio,
                  selectedPayment === method.id && styles.paymentRadioSelected,
                ]}
              >
                {selectedPayment === method.id && <View style={styles.paymentRadioDot} />}
              </View>
            </Pressable>
          ))}
        </View>

        {/* Price Breakdown */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionIcon}>📊</Text>
            <Text style={styles.sectionTitle}>สรุปยอดชำระ</Text>
          </View>
          <View style={styles.priceCard}>
            <View style={styles.priceRow}>
              <Text style={styles.priceLabel}>ราคาสินค้า</Text>
              <Text style={styles.priceValue}>{formatCurrency(totalPrice)}</Text>
            </View>
            <View style={styles.priceRow}>
              <Text style={styles.priceLabel}>ค่าจัดส่ง</Text>
              <Text style={[styles.priceValue, shippingFee === 0 && styles.freeText]}>
                {shippingFee === 0 ? 'ฟรี!' : formatCurrency(shippingFee)}
              </Text>
            </View>
            {discount > 0 && (
              <View style={styles.priceRow}>
                <Text style={styles.priceLabel}>ส่วนลด</Text>
                <Text style={styles.discountValue}>-{formatCurrency(discount)}</Text>
              </View>
            )}
            <View style={styles.priceDivider} />
            <View style={styles.priceRow}>
              <Text style={styles.totalLabel}>ยอดรวมทั้งสิ้น</Text>
              <Text style={styles.totalValue}>{formatCurrency(finalTotal)}</Text>
            </View>
            <View style={styles.pvRow}>
              <Text style={styles.pvLabel}>⭐ รับ PV</Text>
              <Text style={styles.pvValue}>{totalPV} PV</Text>
            </View>
          </View>
        </View>
      </ScrollView>

      {/* Place Order Button */}
      <View style={styles.bottomContainer}>
        <Pressable
          style={[styles.placeOrderButton, isProcessing && styles.buttonDisabled]}
          onPress={handlePlaceOrder}
          disabled={isProcessing}
        >
          <LinearGradient
            colors={isProcessing ? ['#6B7280', '#4B5563'] : ['#10B981', '#059669']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.placeOrderGradient}
          >
            {isProcessing ? (
              <ActivityIndicator size="small" color="#FFF" />
            ) : (
              <Text style={styles.placeOrderText}>
                ✅ ยืนยันคำสั่งซื้อ {formatCurrency(finalTotal)}
              </Text>
            )}
          </LinearGradient>
        </Pressable>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F0F23',
  },
  headerButton: {
    padding: 8,
  },
  headerIcon: {
    fontSize: 24,
    color: '#FFFFFF',
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 120,
  },

  // Center Container
  centerContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 32,
  },
  processingText: {
    color: '#9CA3AF',
    fontSize: 16,
    marginTop: 16,
  },

  // Section
  section: {
    marginBottom: 20,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionIcon: {
    fontSize: 20,
    marginRight: 8,
  },
  sectionTitle: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },

  // Address
  addressCard: {
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  addressName: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
  addressPhone: {
    color: '#9CA3AF',
    fontSize: 14,
    marginTop: 4,
  },
  addressText: {
    color: '#9CA3AF',
    fontSize: 14,
    marginTop: 4,
  },
  addressChange: {
    color: '#3B82F6',
    fontSize: 14,
    marginTop: 12,
  },

  // Summary
  summaryCard: {
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  summaryItem: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  summaryItemName: {
    flex: 1,
    color: '#FFFFFF',
    fontSize: 14,
  },
  summaryItemQty: {
    color: '#9CA3AF',
    fontSize: 14,
    marginHorizontal: 12,
  },
  summaryItemPrice: {
    color: '#3B82F6',
    fontSize: 14,
    fontWeight: '600',
  },

  // Promo
  promoRow: {
    flexDirection: 'row',
  },
  promoInput: {
    flex: 1,
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 12,
    color: '#FFFFFF',
    fontSize: 14,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
    marginRight: 8,
  },
  promoButton: {
    backgroundColor: '#3B82F6',
    borderRadius: 12,
    paddingHorizontal: 20,
    justifyContent: 'center',
    minWidth: 80,
    alignItems: 'center',
  },
  promoButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '600',
  },

  // Payment
  paymentOption: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 14,
    padding: 16,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  paymentOptionSelected: {
    borderColor: '#3B82F6',
    backgroundColor: 'rgba(59,130,246,0.1)',
  },
  paymentOptionDisabled: {
    opacity: 0.5,
  },
  paymentIcon: {
    fontSize: 28,
    marginRight: 12,
  },
  paymentInfo: {
    flex: 1,
  },
  paymentName: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '600',
  },
  paymentDesc: {
    color: '#9CA3AF',
    fontSize: 12,
    marginTop: 2,
  },
  insufficientText: {
    color: '#EF4444',
    fontSize: 11,
    marginTop: 4,
  },
  paymentRadio: {
    width: 22,
    height: 22,
    borderRadius: 11,
    borderWidth: 2,
    borderColor: '#6B7280',
    alignItems: 'center',
    justifyContent: 'center',
  },
  paymentRadioSelected: {
    borderColor: '#3B82F6',
  },
  paymentRadioDot: {
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: '#3B82F6',
  },

  // Price
  priceCard: {
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  priceRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  priceLabel: {
    color: '#9CA3AF',
    fontSize: 14,
  },
  priceValue: {
    color: '#FFFFFF',
    fontSize: 14,
  },
  freeText: {
    color: '#10B981',
    fontWeight: '600',
  },
  discountValue: {
    color: '#10B981',
    fontSize: 14,
    fontWeight: '600',
  },
  priceDivider: {
    height: 1,
    backgroundColor: 'rgba(255,255,255,0.1)',
    marginVertical: 12,
  },
  totalLabel: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
  totalValue: {
    color: '#10B981',
    fontSize: 20,
    fontWeight: 'bold',
  },
  pvRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 8,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: 'rgba(255,215,0,0.2)',
  },
  pvLabel: {
    color: '#FFD700',
    fontSize: 14,
  },
  pvValue: {
    color: '#FFD700',
    fontSize: 14,
    fontWeight: 'bold',
  },

  // Bottom
  bottomContainer: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    padding: 16,
    paddingBottom: 34,
    backgroundColor: 'rgba(15,15,35,0.95)',
    borderTopWidth: 1,
    borderTopColor: 'rgba(255,255,255,0.1)',
  },
  placeOrderButton: {
    borderRadius: 14,
    overflow: 'hidden',
  },
  buttonDisabled: {
    opacity: 0.7,
  },
  placeOrderGradient: {
    paddingVertical: 16,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 14,
  },
  placeOrderText: {
    color: '#FFFFFF',
    fontSize: 17,
    fontWeight: 'bold',
  },

  // Empty
  emptyContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 32,
  },
  emptyIcon: {
    fontSize: 64,
    marginBottom: 16,
    opacity: 0.5,
  },
  emptyText: {
    color: '#9CA3AF',
    fontSize: 18,
    marginBottom: 24,
  },
  shopButton: {
    backgroundColor: '#3B82F6',
    paddingHorizontal: 32,
    paddingVertical: 14,
    borderRadius: 12,
  },
  shopButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },

  // QR Code Screen
  qrContainer: {
    padding: 16,
  },
  qrCard: {
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 20,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  qrTitle: {
    color: '#FFFFFF',
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 16,
  },
  qrAmountRow: {
    alignItems: 'center',
    marginBottom: 20,
  },
  qrAmountLabel: {
    color: '#9CA3AF',
    fontSize: 14,
    marginBottom: 4,
  },
  qrAmount: {
    color: '#10B981',
    fontSize: 32,
    fontWeight: 'bold',
  },
  qrImageContainer: {
    width: SCREEN_WIDTH - 80,
    height: SCREEN_WIDTH - 80,
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 20,
  },
  qrImage: {
    width: '100%',
    height: '100%',
  },
  qrPlaceholder: {
    width: SCREEN_WIDTH - 80,
    height: SCREEN_WIDTH - 80,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 20,
  },
  qrPlaceholderText: {
    color: '#6B7280',
    fontSize: 18,
  },
  bankInfo: {
    width: '100%',
    backgroundColor: 'rgba(59,130,246,0.1)',
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
  },
  bankInfoTitle: {
    color: '#3B82F6',
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 12,
  },
  bankInfoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  bankInfoLabel: {
    color: '#9CA3AF',
    fontSize: 14,
  },
  bankInfoValue: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '500',
  },
  referenceBox: {
    backgroundColor: 'rgba(255,215,0,0.1)',
    borderRadius: 12,
    padding: 12,
    width: '100%',
    alignItems: 'center',
    marginBottom: 16,
  },
  referenceLabel: {
    color: '#9CA3AF',
    fontSize: 12,
    marginBottom: 4,
  },
  referenceValue: {
    color: '#FFD700',
    fontSize: 18,
    fontWeight: 'bold',
    letterSpacing: 2,
  },
  orderNumberText: {
    color: '#9CA3AF',
    fontSize: 13,
    marginBottom: 16,
  },
  pollingStatus: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  pollingText: {
    color: '#9CA3AF',
    fontSize: 14,
  },
  instructionCard: {
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 16,
    padding: 20,
    marginTop: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  instructionTitle: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 12,
  },
  instructionText: {
    color: '#9CA3AF',
    fontSize: 14,
    lineHeight: 24,
  },
  cancelButton: {
    marginTop: 24,
    paddingVertical: 16,
    alignItems: 'center',
  },
  cancelButtonText: {
    color: '#EF4444',
    fontSize: 16,
    fontWeight: '500',
  },

  // Success Screen
  successIcon: {
    width: 100,
    height: 100,
    borderRadius: 50,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
  },
  successEmoji: {
    fontSize: 48,
  },
  successTitle: {
    color: '#FFFFFF',
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 8,
  },
  successSubtitle: {
    color: '#9CA3AF',
    fontSize: 16,
    textAlign: 'center',
    marginBottom: 16,
  },
  successOrderNumber: {
    color: '#3B82F6',
    fontSize: 14,
    marginBottom: 24,
  },
  successPvBox: {
    backgroundColor: 'rgba(255,215,0,0.1)',
    borderRadius: 12,
    paddingHorizontal: 20,
    paddingVertical: 12,
    marginBottom: 32,
    borderWidth: 1,
    borderColor: 'rgba(255,215,0,0.3)',
  },
  successPvText: {
    color: '#FFD700',
    fontSize: 18,
    fontWeight: '600',
  },
  successButtons: {
    width: '100%',
    gap: 12,
  },
  successButton: {
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 14,
    paddingVertical: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  successButtonPrimary: {
    backgroundColor: 'transparent',
    borderWidth: 0,
    overflow: 'hidden',
  },
  successButtonGradient: {
    width: '100%',
    paddingVertical: 16,
    alignItems: 'center',
    borderRadius: 14,
  },
  successButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
  successButtonTextPrimary: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },

  // Failed Screen
  failedIcon: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: 'rgba(239,68,68,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
  },
  failedEmoji: {
    fontSize: 48,
  },
  failedTitle: {
    color: '#FFFFFF',
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 8,
  },
  failedSubtitle: {
    color: '#9CA3AF',
    fontSize: 16,
    textAlign: 'center',
    marginBottom: 32,
  },
  retryButton: {
    backgroundColor: '#3B82F6',
    borderRadius: 14,
    paddingHorizontal: 40,
    paddingVertical: 16,
  },
  retryButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
});
