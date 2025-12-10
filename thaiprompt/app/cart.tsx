/**
 * Cart Screen - หน้าตะกร้าสินค้า
 * Premium Design พร้อม Animation
 *
 * V2: Hybrid Mode
 * - การแสดงผล (ราคา, PV, ค่าส่ง) → คำนวณในแอพ (client-side)
 * - การ Checkout (หักเงิน, สร้าง order) → ใช้ Server คำนวณเท่านั้น
 */

import React, { useCallback, useState } from 'react';
import {
  View,
  Text,
  FlatList,
  Pressable,
  Image,
  StyleSheet,
  StatusBar,
  Alert,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { router, Stack, useFocusEffect } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { useCartStore, CartItem } from '@/stores/cartStore';
import { useAuthStore } from '@/stores/authStore';
import { formatCurrency } from '@/constants';

// Cart Item Component - แสดงข้อมูลจาก local calculation
const CartItemCard = ({
  item,
  onUpdateQuantity,
  onRemove,
  isUpdating,
}: {
  item: CartItem;
  onUpdateQuantity: (quantity: number) => void;
  onRemove: () => void;
  isUpdating: boolean;
}) => {
  // ใช้ค่าที่คำนวณไว้แล้ว
  const price = item.price;
  const pv = item.pvValue;
  const subtotal = item.subtotal;
  const image = item.productImage || item.product.image;

  return (
    <View style={[styles.cartItem, isUpdating && styles.cartItemUpdating]}>
      {/* Product Image */}
      <View style={styles.itemImageContainer}>
        {image ? (
          <Image
            source={{ uri: image }}
            style={styles.itemImage}
            resizeMode="cover"
          />
        ) : (
          <Text style={styles.itemPlaceholder}>📦</Text>
        )}
        {!item.isAvailable && (
          <View style={styles.unavailableOverlay}>
            <Text style={styles.unavailableText}>หมด</Text>
          </View>
        )}
      </View>

      {/* Product Info */}
      <View style={styles.itemInfo}>
        <Text style={styles.itemName} numberOfLines={2}>
          {item.productName || item.product.name}
        </Text>

        {item.selectedVariant && (
          <Text style={styles.itemVariant}>
            ตัวเลือก: {item.selectedVariant.name}
          </Text>
        )}

        <View style={styles.itemPriceRow}>
          <Text style={styles.itemPrice}>{formatCurrency(price)}</Text>
          {item.originalPrice > price && (
            <Text style={styles.itemOriginalPrice}>
              {formatCurrency(item.originalPrice)}
            </Text>
          )}
        </View>

        <View style={styles.itemMetaRow}>
          <View style={styles.itemPvBadge}>
            <Text style={styles.itemPvText}>⭐ {pv} PV</Text>
          </View>
          {item.stock <= 5 && item.stock > 0 && (
            <Text style={styles.lowStockText}>เหลือ {item.stock} ชิ้น</Text>
          )}
        </View>

        {/* Quantity Controls */}
        <View style={styles.quantityRow}>
          <View style={styles.quantityControls}>
            <Pressable
              style={[styles.quantityButton, isUpdating && styles.buttonDisabled]}
              onPress={() => !isUpdating && onUpdateQuantity(item.quantity - 1)}
              disabled={isUpdating}
            >
              <Text style={styles.quantityButtonText}>−</Text>
            </Pressable>

            <Text style={styles.quantityText}>{item.quantity}</Text>

            <Pressable
              style={[
                styles.quantityButton,
                (isUpdating || item.quantity >= item.stock) && styles.buttonDisabled
              ]}
              onPress={() => !isUpdating && item.quantity < item.stock && onUpdateQuantity(item.quantity + 1)}
              disabled={isUpdating || item.quantity >= item.stock}
            >
              <Text style={styles.quantityButtonText}>+</Text>
            </Pressable>
          </View>

          <Text style={styles.itemSubtotal}>{formatCurrency(subtotal)}</Text>

          <Pressable
            style={[styles.removeButton, isUpdating && styles.buttonDisabled]}
            onPress={() => !isUpdating && onRemove()}
            disabled={isUpdating}
          >
            <Text style={styles.removeButtonText}>🗑️</Text>
          </Pressable>
        </View>
      </View>

      {/* Loading overlay */}
      {isUpdating && (
        <View style={styles.itemLoadingOverlay}>
          <ActivityIndicator size="small" color="#3B82F6" />
        </View>
      )}
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
    summary,
    isLoading,
    error,
    initialize,
    updateQuantity,
    removeItem,
    clearCart,
    clearError,
  } = useCartStore();

  const { isAuthenticated } = useAuthStore();
  const [updatingItemId, setUpdatingItemId] = useState<string | null>(null);
  const [isRefreshing, setIsRefreshing] = useState(false);

  // Initialize cart on focus
  useFocusEffect(
    useCallback(() => {
      initialize();
    }, [initialize])
  );

  // Handle refresh
  const handleRefresh = async () => {
    setIsRefreshing(true);
    await initialize();
    setIsRefreshing(false);
  };

  // Handle update quantity - คำนวณในแอพทันที
  const handleUpdateQuantity = (itemId: string, quantity: number) => {
    setUpdatingItemId(itemId);
    updateQuantity(itemId, quantity);
    // Reset updating state after brief delay for visual feedback
    setTimeout(() => setUpdatingItemId(null), 100);
  };

  // Handle remove item with confirmation
  const handleRemoveItem = (item: CartItem) => {
    Alert.alert(
      'ลบสินค้า',
      `ต้องการลบ "${item.productName || item.product.name}" ออกจากตะกร้าหรือไม่?`,
      [
        { text: 'ยกเลิก', style: 'cancel' },
        {
          text: 'ลบ',
          style: 'destructive',
          onPress: () => {
            removeItem(item.id);
          },
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

    // Check if any item is unavailable
    const unavailableItems = items.filter(item => !item.isAvailable);
    if (unavailableItems.length > 0) {
      Alert.alert(
        'มีสินค้าหมดสต๊อก',
        'กรุณาลบสินค้าที่หมดสต๊อกก่อนทำการสั่งซื้อ',
        [{ text: 'ตกลง' }]
      );
      return;
    }

    // Navigate to checkout
    router.push('/checkout');
  };

  // Show error alert
  React.useEffect(() => {
    if (error) {
      Alert.alert('เกิดข้อผิดพลาด', error, [
        { text: 'ตกลง', onPress: clearError }
      ]);
    }
  }, [error, clearError]);

  // Render cart item
  const renderCartItem = ({ item }: { item: CartItem }) => (
    <CartItemCard
      item={item}
      onUpdateQuantity={(quantity) => handleUpdateQuantity(item.id, quantity)}
      onRemove={() => handleRemoveItem(item)}
      isUpdating={updatingItemId === item.id}
    />
  );

  // Loading state
  if (isLoading && items.length === 0) {
    return (
      <View style={styles.container}>
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <LinearGradient
          colors={['#0F0F23', '#1A1A2E', '#16213E']}
          style={StyleSheet.absoluteFill}
        />
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={styles.loadingText}>กำลังโหลดตะกร้า...</Text>
        </View>
      </View>
    );
  }

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
            refreshControl={
              <RefreshControl
                refreshing={isRefreshing}
                onRefresh={handleRefresh}
                tintColor="#3B82F6"
                colors={['#3B82F6']}
              />
            }
          />

          {/* Summary & Checkout */}
          <View style={styles.summaryContainer}>
            <LinearGradient
              colors={['rgba(15,15,35,0.98)', 'rgba(26,26,46,0.98)']}
              style={styles.summaryGradient}
            >
              {/* Free Shipping Progress */}
              {summary.amountToFreeShipping > 0 && (
                <View style={styles.freeShippingBanner}>
                  <Text style={styles.freeShippingText}>
                    🚚 ช้อปอีก {formatCurrency(summary.amountToFreeShipping)} รับส่งฟรี!
                  </Text>
                  <View style={styles.progressBar}>
                    <View
                      style={[
                        styles.progressFill,
                        { width: `${Math.min((totalPrice / summary.freeShippingThreshold) * 100, 100)}%` }
                      ]}
                    />
                  </View>
                </View>
              )}

              {/* Summary Info - คำนวณในแอพ */}
              <View style={styles.summaryInfo}>
                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>สินค้าทั้งหมด</Text>
                  <Text style={styles.summaryValue}>{totalItems} ชิ้น</Text>
                </View>

                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>ราคาสินค้า</Text>
                  <Text style={styles.summaryValue}>{formatCurrency(totalPrice)}</Text>
                </View>

                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>ค่าจัดส่ง</Text>
                  <Text style={[
                    styles.summaryValue,
                    summary.shippingFee === 0 && styles.freeText
                  ]}>
                    {summary.shippingFee === 0 ? 'ฟรี!' : formatCurrency(summary.shippingFee)}
                  </Text>
                </View>

                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>PV รวม</Text>
                  <View style={styles.pvRow}>
                    <Text style={styles.pvIcon}>⭐</Text>
                    <Text style={styles.pvValue}>{totalPV} PV</Text>
                  </View>
                </View>

                {summary.estimatedCommission > 0 && (
                  <View style={styles.summaryRow}>
                    <Text style={styles.summaryLabel}>คอมมิชชั่นโดยประมาณ</Text>
                    <Text style={styles.commissionValue}>
                      +{formatCurrency(summary.estimatedCommission)}
                    </Text>
                  </View>
                )}

                <View style={styles.summaryDivider} />

                <View style={styles.summaryRow}>
                  <Text style={styles.totalLabel}>ยอดรวมสุทธิ</Text>
                  <Text style={styles.totalPrice}>{formatCurrency(summary.grandTotal)}</Text>
                </View>

                <Text style={styles.calculationNote}>
                  * ยอดชำระจริงจะคำนวณใหม่ที่หน้าชำระเงิน
                </Text>
              </View>

              {/* Checkout Button */}
              <Pressable
                style={[styles.checkoutButton, isLoading && styles.buttonDisabled]}
                onPress={handleCheckout}
                disabled={isLoading}
              >
                <LinearGradient
                  colors={['#10B981', '#059669']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                  style={styles.checkoutGradient}
                >
                  {isLoading ? (
                    <ActivityIndicator size="small" color="#FFFFFF" />
                  ) : (
                    <>
                      <Text style={styles.checkoutIcon}>💳</Text>
                      <Text style={styles.checkoutText}>ดำเนินการสั่งซื้อ</Text>
                    </>
                  )}
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

  // Loading
  loadingContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loadingText: {
    color: '#9CA3AF',
    fontSize: 16,
    marginTop: 12,
  },

  // List
  listContent: {
    padding: 16,
    paddingBottom: 340,
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
    position: 'relative',
  },
  cartItemUpdating: {
    opacity: 0.7,
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
  unavailableOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.7)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  unavailableText: {
    color: '#EF4444',
    fontSize: 14,
    fontWeight: 'bold',
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
    marginBottom: 4,
  },
  itemPrice: {
    color: '#3B82F6',
    fontSize: 16,
    fontWeight: 'bold',
  },
  itemOriginalPrice: {
    color: '#6B7280',
    fontSize: 13,
    textDecorationLine: 'line-through',
    marginLeft: 8,
  },
  itemMetaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  itemPvBadge: {
    backgroundColor: 'rgba(255,215,0,0.15)',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: 'rgba(255,215,0,0.3)',
  },
  itemPvText: {
    color: '#FFD700',
    fontSize: 11,
    fontWeight: '600',
  },
  lowStockText: {
    color: '#F59E0B',
    fontSize: 12,
    marginLeft: 8,
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
  itemSubtotal: {
    color: '#10B981',
    fontSize: 14,
    fontWeight: '600',
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
  buttonDisabled: {
    opacity: 0.5,
  },
  itemLoadingOverlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(0,0,0,0.3)',
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 16,
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
  freeShippingBanner: {
    backgroundColor: 'rgba(16,185,129,0.15)',
    borderRadius: 12,
    padding: 12,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: 'rgba(16,185,129,0.3)',
  },
  freeShippingText: {
    color: '#10B981',
    fontSize: 13,
    fontWeight: '600',
    marginBottom: 8,
    textAlign: 'center',
  },
  progressBar: {
    height: 6,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 3,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    backgroundColor: '#10B981',
    borderRadius: 3,
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
  freeText: {
    color: '#10B981',
    fontWeight: '600',
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
  commissionValue: {
    color: '#10B981',
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
  calculationNote: {
    color: '#6B7280',
    fontSize: 11,
    textAlign: 'center',
    marginTop: 8,
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
