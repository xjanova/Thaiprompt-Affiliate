/**
 * Checkout Screen - หน้าชำระเงิน
 * Premium Design
 *
 * Features:
 * - แสดงสรุปคำสั่งซื้อ
 * - เลือกที่อยู่จัดส่ง
 * - เลือกวิธีชำระเงิน
 * - ใส่โค้ดส่วนลด
 * - ยืนยันการสั่งซื้อ
 */

import React, { useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  TextInput,
  StyleSheet,
  StatusBar,
  Alert,
} from 'react-native';
import { router, Stack } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { useCartStore } from '@/stores/cartStore';
import { useAuthStore } from '@/stores/authStore';
import { formatCurrency } from '@/constants';

// Payment Method Options
const PAYMENT_METHODS = [
  { id: 'wallet', name: 'กระเป๋าเงิน TP', icon: '💰', description: 'ชำระด้วยยอดเงินในกระเป๋า' },
  { id: 'bank', name: 'โอนเงินธนาคาร', icon: '🏦', description: 'โอนผ่าน QR Code / บัญชีธนาคาร' },
  { id: 'card', name: 'บัตรเครดิต/เดบิต', icon: '💳', description: 'Visa, Mastercard, JCB' },
  { id: 'cod', name: 'เก็บเงินปลายทาง', icon: '📦', description: 'ชำระเงินเมื่อรับสินค้า' },
];

export default function CheckoutScreen() {
  const { items, totalItems, totalPrice, totalPV, clearCart } = useCartStore();
  const { user, isAuthenticated } = useAuthStore();

  const [selectedPayment, setSelectedPayment] = useState<string>('wallet');
  const [promoCode, setPromoCode] = useState('');
  const [discount, setDiscount] = useState(0);
  const [isProcessing, setIsProcessing] = useState(false);

  // Calculate shipping (mock)
  const shippingFee = totalPrice >= 500 ? 0 : 50;
  const finalTotal = totalPrice + shippingFee - discount;

  // Apply promo code
  const handleApplyPromo = () => {
    if (promoCode.toUpperCase() === 'FIRST10') {
      setDiscount(totalPrice * 0.1);
      Alert.alert('สำเร็จ!', 'ใช้โค้ดส่วนลด 10% แล้ว');
    } else if (promoCode.toUpperCase() === 'FREE50') {
      setDiscount(50);
      Alert.alert('สำเร็จ!', 'ใช้โค้ดส่วนลด ฿50 แล้ว');
    } else {
      Alert.alert('ไม่พบโค้ด', 'โค้ดส่วนลดไม่ถูกต้องหรือหมดอายุแล้ว');
    }
  };

  // Place order
  const handlePlaceOrder = async () => {
    if (!isAuthenticated) {
      Alert.alert('กรุณาเข้าสู่ระบบ', 'คุณต้องเข้าสู่ระบบก่อนทำการสั่งซื้อ');
      return;
    }

    setIsProcessing(true);

    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 2000));

      // Clear cart after successful order
      clearCart();

      Alert.alert(
        'สั่งซื้อสำเร็จ! 🎉',
        `คำสั่งซื้อของคุณได้รับการยืนยันแล้ว\n\nคุณได้รับ ${totalPV} PV จากการสั่งซื้อนี้`,
        [
          {
            text: 'ดูคำสั่งซื้อ',
            onPress: () => router.replace('/orders'),
          },
          {
            text: 'กลับหน้าหลัก',
            onPress: () => router.replace('/(tabs)'),
          },
        ]
      );
    } catch (error) {
      Alert.alert('เกิดข้อผิดพลาด', 'ไม่สามารถดำเนินการสั่งซื้อได้ กรุณาลองใหม่');
    } finally {
      setIsProcessing(false);
    }
  };

  if (items.length === 0) {
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
                  {item.product.name}
                </Text>
                <Text style={styles.summaryItemQty}>x{item.quantity}</Text>
                <Text style={styles.summaryItemPrice}>
                  {formatCurrency(
                    (item.selectedVariant?.price || item.product.discount_price || item.product.price) *
                      item.quantity
                  )}
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
            <Pressable style={styles.promoButton} onPress={handleApplyPromo}>
              <Text style={styles.promoButtonText}>ใช้โค้ด</Text>
            </Pressable>
          </View>
        </View>

        {/* Payment Method */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionIcon}>💳</Text>
            <Text style={styles.sectionTitle}>วิธีชำระเงิน</Text>
          </View>
          {PAYMENT_METHODS.map((method) => (
            <Pressable
              key={method.id}
              style={[
                styles.paymentOption,
                selectedPayment === method.id && styles.paymentOptionSelected,
              ]}
              onPress={() => setSelectedPayment(method.id)}
            >
              <Text style={styles.paymentIcon}>{method.icon}</Text>
              <View style={styles.paymentInfo}>
                <Text style={styles.paymentName}>{method.name}</Text>
                <Text style={styles.paymentDesc}>{method.description}</Text>
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
              <Text style={styles.priceValue}>
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
            <Text style={styles.placeOrderText}>
              {isProcessing ? '⏳ กำลังดำเนินการ...' : `✅ ยืนยันคำสั่งซื้อ ${formatCurrency(finalTotal)}`}
            </Text>
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
});
