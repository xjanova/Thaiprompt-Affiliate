/**
 * Cart Screen - หน้าตะกร้าสินค้า
 * Premium Design พร้อม Animation
 *
 * Features:
 * - แสดงรายการสินค้าในตะกร้า
 * - ปรับจำนวนสินค้า +/-
 * - ลบสินค้า (swipe หรือกดปุ่ม)
 * - แสดงยอดรวม, PV รวม
 * - ปุ่มชำระเงิน
 */

import React, { useEffect, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  Pressable,
  Image,
  StyleSheet,
  StatusBar,
  Alert,
  Animated,
} from 'react-native';
import { router, Stack, useFocusEffect } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { useCartStore, CartItem } from '@/stores/cartStore';
import { useAuthStore } from '@/stores/authStore';
import { formatCurrency } from '@/constants';

// Cart Item Component
const CartItemCard = ({
  item,
  onUpdateQuantity,
  onRemove,
}: {
  item: CartItem;
  onUpdateQuantity: (quantity: number) => void;
  onRemove: () => void;
}) => {
  const price = item.selectedVariant?.price || item.product.discount_price || item.product.price;
  const pv = (item.product as any).pv || Math.round(item.product.price * 0.1);

  return (
    <View style={styles.cartItem}>
      {/* Product Image */}
      <View style={styles.itemImageContainer}>
        {item.product.image ? (
          <Image
            source={{ uri: item.product.image }}
            style={styles.itemImage}
            resizeMode="cover"
          />
        ) : (
          <Text style={styles.itemPlaceholder}>📦</Text>
        )}
      </View>

      {/* Product Info */}
      <View style={styles.itemInfo}>
        <Text style={styles.itemName} numberOfLines={2}>
          {item.product.name}
        </Text>

        {item.selectedVariant && (
          <Text style={styles.itemVariant}>
            ตัวเลือก: {item.selectedVariant.name}
          </Text>
        )}

        <View style={styles.itemPriceRow}>
          <Text style={styles.itemPrice}>{formatCurrency(price)}</Text>
          <View style={styles.itemPvBadge}>
            <Text style={styles.itemPvText}>⭐ {pv} PV</Text>
          </View>
        </View>

        {/* Quantity Controls */}
        <View style={styles.quantityRow}>
          <View style={styles.quantityControls}>
            <Pressable
              style={styles.quantityButton}
              onPress={() => onUpdateQuantity(item.quantity - 1)}
            >
              <Text style={styles.quantityButtonText}>−</Text>
            </Pressable>

            <Text style={styles.quantityText}>{item.quantity}</Text>

            <Pressable
              style={styles.quantityButton}
              onPress={() => onUpdateQuantity(item.quantity + 1)}
            >
              <Text style={styles.quantityButtonText}>+</Text>
            </Pressable>
          </View>

          <Pressable style={styles.removeButton} onPress={onRemove}>
            <Text style={styles.removeButtonText}>🗑️</Text>
          </Pressable>
        </View>
      </View>
    </View>
  );
};

// Empty Cart Component
const EmptyCart = () => (
  <View style={styles.emptyContainer}>
    <Text style={styles.emptyIcon}>🛒</Text>
    <Text style={styles.emptyTitle}>ตะกร้าว่างเปล่า</Text>
    <Text style={styles.emptySubtitle}>เริ่มช้อปปิ้งและเพิ่มสินค้าลงตะกร้ากันเลย!</Text>

    <Pressable
      style={styles.shopButton}
      onPress={() => router.push('/shopping')}
    >
      <LinearGradient
        colors={['#3B82F6', '#2563EB']}
        style={styles.shopButtonGradient}
      >
        <Text style={styles.shopButtonText}>🛍️ เริ่มช้อปปิ้ง</Text>
      </LinearGradient>
    </Pressable>
  </View>
);

export default function CartScreen() {
  const {
    items,
    totalItems,
    totalPrice,
    totalPV,
    isLoading,
    initialize,
    updateQuantity,
    removeItem,
    clearCart,
  } = useCartStore();

  const { isAuthenticated } = useAuthStore();

  // Initialize cart on focus
  useFocusEffect(
    useCallback(() => {
      initialize();
    }, [initialize])
  );

  // Handle remove item with confirmation
  const handleRemoveItem = (item: CartItem) => {
    Alert.alert(
      'ลบสินค้า',
      `ต้องการลบ "${item.product.name}" ออกจากตะกร้าหรือไม่?`,
      [
        { text: 'ยกเลิก', style: 'cancel' },
        {
          text: 'ลบ',
          style: 'destructive',
          onPress: () => removeItem(item.id),
        },
      ]
    );
  };

  // Handle clear cart
  const handleClearCart = () => {
    Alert.alert(
      'ล้างตะกร้า',
      'ต้องการลบสินค้าทั้งหมดออกจากตะกร้าหรือไม่?',
      [
        { text: 'ยกเลิก', style: 'cancel' },
        {
          text: 'ล้างตะกร้า',
          style: 'destructive',
          onPress: clearCart,
        },
      ]
    );
  };

  // Handle checkout
  const handleCheckout = () => {
    if (!isAuthenticated) {
      Alert.alert(
        'กรุณาเข้าสู่ระบบ',
        'คุณต้องเข้าสู่ระบบก่อนทำการสั่งซื้อ',
        [
          { text: 'ยกเลิก', style: 'cancel' },
          {
            text: 'เข้าสู่ระบบ',
            onPress: () => router.push('/login'),
          },
        ]
      );
      return;
    }

    // Navigate to checkout
    router.push('/checkout');
  };

  // Render cart item
  const renderCartItem = ({ item }: { item: CartItem }) => (
    <CartItemCard
      item={item}
      onUpdateQuantity={(quantity) => updateQuantity(item.id, quantity)}
      onRemove={() => handleRemoveItem(item)}
    />
  );

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />

      <LinearGradient
        colors={['#0F0F23', '#1A1A2E', '#16213E']}
        style={StyleSheet.absoluteFill}
      />

      <Stack.Screen
        options={{
          headerShown: true,
          title: `ตะกร้า (${totalItems})`,
          headerStyle: { backgroundColor: '#0F0F23' },
          headerTintColor: '#FFFFFF',
          headerLeft: () => (
            <Pressable onPress={() => router.back()} style={styles.headerButton}>
              <Text style={styles.headerIcon}>⬅️</Text>
            </Pressable>
          ),
          headerRight: () =>
            items.length > 0 ? (
              <Pressable onPress={handleClearCart} style={styles.headerButton}>
                <Text style={styles.headerIcon}>🗑️</Text>
              </Pressable>
            ) : null,
        }}
      />

      {items.length === 0 ? (
        <EmptyCart />
      ) : (
        <>
          {/* Cart Items List */}
          <FlatList
            data={items}
            renderItem={renderCartItem}
            keyExtractor={(item) => item.id}
            contentContainerStyle={styles.listContent}
            showsVerticalScrollIndicator={false}
          />

          {/* Summary & Checkout */}
          <View style={styles.summaryContainer}>
            <LinearGradient
              colors={['rgba(15,15,35,0.95)', 'rgba(26,26,46,0.95)']}
              style={styles.summaryGradient}
            >
              {/* Summary Info */}
              <View style={styles.summaryInfo}>
                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>สินค้าทั้งหมด</Text>
                  <Text style={styles.summaryValue}>{totalItems} ชิ้น</Text>
                </View>

                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>PV รวม</Text>
                  <View style={styles.pvRow}>
                    <Text style={styles.pvIcon}>⭐</Text>
                    <Text style={styles.pvValue}>{totalPV} PV</Text>
                  </View>
                </View>

                <View style={styles.summaryDivider} />

                <View style={styles.summaryRow}>
                  <Text style={styles.totalLabel}>ยอดรวม</Text>
                  <Text style={styles.totalPrice}>{formatCurrency(totalPrice)}</Text>
                </View>
              </View>

              {/* Checkout Button */}
              <Pressable style={styles.checkoutButton} onPress={handleCheckout}>
                <LinearGradient
                  colors={['#10B981', '#059669']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                  style={styles.checkoutGradient}
                >
                  <Text style={styles.checkoutIcon}>💳</Text>
                  <Text style={styles.checkoutText}>ดำเนินการสั่งซื้อ</Text>
                </LinearGradient>
              </Pressable>
            </LinearGradient>
          </View>
        </>
      )}
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

  // List
  listContent: {
    padding: 16,
    paddingBottom: 220,
  },

  // Cart Item
  cartItem: {
    flexDirection: 'row',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 16,
    padding: 12,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  itemImageContainer: {
    width: 100,
    height: 100,
    borderRadius: 12,
    backgroundColor: 'rgba(255,255,255,0.08)',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  itemImage: {
    width: '100%',
    height: '100%',
  },
  itemPlaceholder: {
    fontSize: 40,
    color: '#9CA3AF',
  },
  itemInfo: {
    flex: 1,
    marginLeft: 12,
  },
  itemName: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '600',
    marginBottom: 4,
  },
  itemVariant: {
    color: '#9CA3AF',
    fontSize: 13,
    marginBottom: 4,
  },
  itemPriceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  itemPrice: {
    color: '#3B82F6',
    fontSize: 16,
    fontWeight: 'bold',
  },
  itemPvBadge: {
    backgroundColor: 'rgba(255,215,0,0.15)',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 10,
    marginLeft: 8,
    borderWidth: 1,
    borderColor: 'rgba(255,215,0,0.3)',
  },
  itemPvText: {
    color: '#FFD700',
    fontSize: 11,
    fontWeight: '600',
  },

  // Quantity Controls
  quantityRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  quantityControls: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderRadius: 10,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  quantityButton: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
  },
  quantityButtonText: {
    color: '#FFFFFF',
    fontSize: 20,
    fontWeight: '600',
  },
  quantityText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
    minWidth: 40,
    textAlign: 'center',
  },
  removeButton: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(239,68,68,0.15)',
    borderRadius: 10,
    borderWidth: 1,
    borderColor: 'rgba(239,68,68,0.3)',
  },
  removeButtonText: {
    fontSize: 18,
  },

  // Empty Cart
  emptyContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 32,
  },
  emptyIcon: {
    fontSize: 80,
    marginBottom: 16,
    opacity: 0.5,
  },
  emptyTitle: {
    color: '#FFFFFF',
    fontSize: 22,
    fontWeight: 'bold',
    marginBottom: 8,
  },
  emptySubtitle: {
    color: '#9CA3AF',
    fontSize: 16,
    textAlign: 'center',
    marginBottom: 32,
  },
  shopButton: {
    borderRadius: 14,
    overflow: 'hidden',
  },
  shopButtonGradient: {
    paddingHorizontal: 32,
    paddingVertical: 16,
    borderRadius: 14,
  },
  shopButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },

  // Summary
  summaryContainer: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
  },
  summaryGradient: {
    padding: 20,
    paddingBottom: 34,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    borderTopWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
  },
  summaryInfo: {
    marginBottom: 16,
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  summaryLabel: {
    color: '#9CA3AF',
    fontSize: 14,
  },
  summaryValue: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '500',
  },
  pvRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  pvIcon: {
    fontSize: 14,
    marginRight: 4,
  },
  pvValue: {
    color: '#FFD700',
    fontSize: 14,
    fontWeight: '600',
  },
  summaryDivider: {
    height: 1,
    backgroundColor: 'rgba(255,255,255,0.1)',
    marginVertical: 12,
  },
  totalLabel: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
  totalPrice: {
    color: '#10B981',
    fontSize: 22,
    fontWeight: 'bold',
  },

  // Checkout Button
  checkoutButton: {
    borderRadius: 14,
    overflow: 'hidden',
  },
  checkoutGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 16,
    borderRadius: 14,
  },
  checkoutIcon: {
    fontSize: 20,
    marginRight: 8,
  },
  checkoutText: {
    color: '#FFFFFF',
    fontSize: 18,
    fontWeight: 'bold',
  },
});
