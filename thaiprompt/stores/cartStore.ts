/**
 * Cart Store - จัดการระบบตะกร้าสินค้า
 * ใช้ Zustand สำหรับ state management
 *
 * V2: Hybrid Mode
 * - การแสดงผล (ราคา, PV, ค่าส่ง) → คำนวณในแอพ (client-side)
 * - การ Checkout (หักเงิน, สร้าง order) → ใช้ Server คำนวณเท่านั้น
 */

import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as checkoutApi from '@/services/api';
import type { Product } from '@/types';

// Cart Item Type
export interface CartItem {
  id: string;
  product: Product;
  quantity: number;
  selectedVariant?: {
    id: string;
    name: string;
    price: number;
  };
  addedAt: Date;
  // Compatibility fields สำหรับ UI ใหม่
  productId: number;
  productName: string;
  productImage: string | null;
  price: number;
  originalPrice: number;
  subtotal: number;
  pvValue: number;
  pvSubtotal: number;
  isAvailable: boolean;
  stock: number;
}

// Cart Config - กฎการคำนวณ
const CART_CONFIG = {
  FREE_SHIPPING_THRESHOLD: 1500,  // ยอดส่งฟรี
  SHIPPING_FEE: 50,               // ค่าส่งปกติ
  PV_RATE: 0.1,                   // อัตรา PV (10% ของราคา)
  COMMISSION_RATE: 0.05,          // อัตราคอมมิชชั่น (5%)
};

// Cart Summary Type (คำนวณในแอพ)
export interface CartSummary {
  totalItems: number;
  totalPrice: number;
  totalPV: number;
  shippingFee: number;
  grandTotal: number;
  estimatedCommission: number;
  freeShippingThreshold: number;
  amountToFreeShipping: number;
}

// Cart State Type
interface CartState {
  // State
  items: CartItem[];
  isLoading: boolean;
  error: string | null;

  // Summary (คำนวณในแอพ)
  summary: CartSummary;

  // Computed (สำหรับ backward compatibility)
  totalItems: number;
  totalPrice: number;
  totalPV: number;

  // Promo code (แสดงผลเฉยๆ)
  promoCode: string | null;
  promoDiscount: number;

  // Actions - ทำงานในแอพ
  initialize: () => Promise<void>;
  addItem: (product: Product, quantity?: number, variant?: CartItem['selectedVariant']) => void;
  removeItem: (itemId: string) => void;
  updateQuantity: (itemId: string, quantity: number) => void;
  clearCart: () => void;
  setPromoCode: (code: string | null, discount: number) => void;
  clearError: () => void;

  // Actions - ใช้ Server
  checkout: (params: CheckoutParams) => Promise<CheckoutResult>;
}

// Checkout params
export interface CheckoutParams {
  paymentMethod: 'wallet' | 'bank' | 'card' | 'cod';
  shippingAddressId?: number;
  promoCode?: string;
  note?: string;
}

// Checkout result
export interface CheckoutResult {
  success: boolean;
  message?: string;
  orderId?: number;
  orderNumber?: string;
  pvEarned?: number;
}

const CART_STORAGE_KEY = '@thaiprompt_cart';

/**
 * บันทึกตะกร้าลง AsyncStorage
 */
const saveCartToStorage = async (items: CartItem[]): Promise<void> => {
  try {
    await AsyncStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items));
  } catch (error) {
    console.error('Save cart to storage error:', error);
  }
};

/**
 * โหลดตะกร้าจาก AsyncStorage
 */
const loadCartFromStorage = async (): Promise<CartItem[]> => {
  try {
    const cartData = await AsyncStorage.getItem(CART_STORAGE_KEY);
    if (cartData) {
      return JSON.parse(cartData) as CartItem[];
    }
    return [];
  } catch (error) {
    console.error('Load cart from storage error:', error);
    return [];
  }
};

/**
 * คำนวณ totals และ summary จาก items (ทำในแอพ)
 */
const calculateSummary = (items: CartItem[]): CartSummary => {
  // คำนวณจำนวนสินค้า
  const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);

  // คำนวณราคารวม
  const totalPrice = items.reduce((sum, item) => {
    const price = item.selectedVariant?.price ||
      item.product.discount_price ||
      item.product.price;
    return sum + (price * item.quantity);
  }, 0);

  // คำนวณ PV รวม (ใช้ pv จากสินค้า หรือ 10% ของราคา)
  const totalPV = items.reduce((sum, item) => {
    const pv = (item.product as any).pv ||
      Math.round(item.product.price * CART_CONFIG.PV_RATE);
    return sum + (pv * item.quantity);
  }, 0);

  // คำนวณค่าส่ง (ฟรีเมื่อยอดถึง threshold)
  const shippingFee = totalPrice >= CART_CONFIG.FREE_SHIPPING_THRESHOLD
    ? 0
    : CART_CONFIG.SHIPPING_FEE;

  // คำนวณยอดรวมสุทธิ
  const grandTotal = totalPrice + shippingFee;

  // คำนวณคอมมิชชั่นโดยประมาณ
  const estimatedCommission = Math.round(totalPrice * CART_CONFIG.COMMISSION_RATE);

  // คำนวณยอดที่ต้องซื้อเพิ่มเพื่อได้ส่งฟรี
  const amountToFreeShipping = Math.max(
    0,
    CART_CONFIG.FREE_SHIPPING_THRESHOLD - totalPrice
  );

  return {
    totalItems,
    totalPrice,
    totalPV,
    shippingFee,
    grandTotal,
    estimatedCommission,
    freeShippingThreshold: CART_CONFIG.FREE_SHIPPING_THRESHOLD,
    amountToFreeShipping,
  };
};

/**
 * สร้าง CartItem จาก Product
 */
const createCartItem = (
  product: Product,
  quantity: number,
  variant?: CartItem['selectedVariant']
): CartItem => {
  const price = variant?.price || product.discount_price || product.price;
  const pv = (product as any).pv || Math.round(product.price * CART_CONFIG.PV_RATE);

  return {
    id: `${product.id}_${variant?.id || 'default'}_${Date.now()}`,
    product,
    quantity,
    selectedVariant: variant,
    addedAt: new Date(),
    // Compatibility fields
    productId: Number(product.id),
    productName: product.name,
    productImage: product.image || null,
    price,
    originalPrice: product.price,
    subtotal: price * quantity,
    pvValue: pv,
    pvSubtotal: pv * quantity,
    isAvailable: (product as any).stock !== 0,
    stock: (product as any).stock || 999,
  };
};

/**
 * อัพเดท CartItem (คำนวณ subtotals ใหม่)
 */
const updateCartItemCalc = (item: CartItem): CartItem => {
  const price = item.selectedVariant?.price ||
    item.product.discount_price ||
    item.product.price;
  const pv = (item.product as any).pv ||
    Math.round(item.product.price * CART_CONFIG.PV_RATE);

  return {
    ...item,
    price,
    subtotal: price * item.quantity,
    pvSubtotal: pv * item.quantity,
  };
};

/**
 * Default summary
 */
const defaultSummary: CartSummary = {
  totalItems: 0,
  totalPrice: 0,
  totalPV: 0,
  shippingFee: CART_CONFIG.SHIPPING_FEE,
  grandTotal: 0,
  estimatedCommission: 0,
  freeShippingThreshold: CART_CONFIG.FREE_SHIPPING_THRESHOLD,
  amountToFreeShipping: CART_CONFIG.FREE_SHIPPING_THRESHOLD,
};

export const useCartStore = create<CartState>((set, get) => ({
  // Initial State
  items: [],
  isLoading: false,
  error: null,
  summary: defaultSummary,
  totalItems: 0,
  totalPrice: 0,
  totalPV: 0,
  promoCode: null,
  promoDiscount: 0,

  /**
   * Initialize - โหลดตะกร้าจาก storage และคำนวณในแอพ
   */
  initialize: async () => {
    try {
      set({ isLoading: true, error: null });

      const items = await loadCartFromStorage();
      const summary = calculateSummary(items);

      set({
        items,
        summary,
        totalItems: summary.totalItems,
        totalPrice: summary.totalPrice,
        totalPV: summary.totalPV,
        isLoading: false,
      });
    } catch (error) {
      console.error('Initialize cart error:', error);
      set({
        isLoading: false,
        error: 'ไม่สามารถโหลดตะกร้าได้',
      });
    }
  },

  /**
   * เพิ่มสินค้าลงตะกร้า (คำนวณในแอพ)
   */
  addItem: (product: Product, quantity: number = 1, variant?: CartItem['selectedVariant']) => {
    const { items } = get();

    // ตรวจสอบว่ามีสินค้านี้อยู่แล้วหรือไม่
    const existingIndex = items.findIndex(
      item => item.product.id === product.id &&
        item.selectedVariant?.id === variant?.id
    );

    let newItems: CartItem[];

    if (existingIndex >= 0) {
      // อัพเดทจำนวน
      newItems = [...items];
      newItems[existingIndex] = updateCartItemCalc({
        ...newItems[existingIndex],
        quantity: newItems[existingIndex].quantity + quantity,
      });
    } else {
      // เพิ่มใหม่
      const newItem = createCartItem(product, quantity, variant);
      newItems = [...items, newItem];
    }

    const summary = calculateSummary(newItems);

    set({
      items: newItems,
      summary,
      totalItems: summary.totalItems,
      totalPrice: summary.totalPrice,
      totalPV: summary.totalPV,
    });

    saveCartToStorage(newItems);
  },

  /**
   * ลบสินค้าออกจากตะกร้า (คำนวณในแอพ)
   */
  removeItem: (itemId: string) => {
    const { items } = get();
    const newItems = items.filter(item => item.id !== itemId);
    const summary = calculateSummary(newItems);

    set({
      items: newItems,
      summary,
      totalItems: summary.totalItems,
      totalPrice: summary.totalPrice,
      totalPV: summary.totalPV,
    });

    saveCartToStorage(newItems);
  },

  /**
   * อัพเดทจำนวนสินค้า (คำนวณในแอพ)
   */
  updateQuantity: (itemId: string, quantity: number) => {
    if (quantity < 1) {
      get().removeItem(itemId);
      return;
    }

    const { items } = get();
    const newItems = items.map(item =>
      item.id === itemId
        ? updateCartItemCalc({ ...item, quantity })
        : item
    );
    const summary = calculateSummary(newItems);

    set({
      items: newItems,
      summary,
      totalItems: summary.totalItems,
      totalPrice: summary.totalPrice,
      totalPV: summary.totalPV,
    });

    saveCartToStorage(newItems);
  },

  /**
   * ล้างตะกร้า (คำนวณในแอพ)
   */
  clearCart: () => {
    set({
      items: [],
      summary: defaultSummary,
      totalItems: 0,
      totalPrice: 0,
      totalPV: 0,
      promoCode: null,
      promoDiscount: 0,
    });
    saveCartToStorage([]);
  },

  /**
   * ตั้งค่า promo code (แสดงผลเฉยๆ - server จะตรวจสอบอีกครั้งตอน checkout)
   */
  setPromoCode: (code: string | null, discount: number) => {
    const { summary } = get();

    set({
      promoCode: code,
      promoDiscount: discount,
      summary: {
        ...summary,
        grandTotal: summary.totalPrice + summary.shippingFee - discount,
      },
    });
  },

  /**
   * ล้าง error
   */
  clearError: () => {
    set({ error: null });
  },

  /**
   * Checkout - สร้าง order ที่ Server (Server คำนวณทุกอย่างใหม่)
   *
   * ⚠️ การหักเงิน, เพิ่ม PV, สร้าง commission ทำที่ Server เท่านั้น!
   */
  checkout: async (params: CheckoutParams): Promise<CheckoutResult> => {
    try {
      set({ isLoading: true, error: null });

      const { items, promoCode } = get();

      // เตรียมข้อมูลส่ง Server
      const cartItems = items.map(item => ({
        product_id: Number(item.product.id),
        quantity: item.quantity,
        variant_id: item.selectedVariant?.id || null,
      }));

      // เรียก API checkout (Server คำนวณทุกอย่างใหม่)
      const response = await checkoutApi.checkout({
        payment_method: params.paymentMethod,
        shipping_address_id: params.shippingAddressId,
        promo_code: promoCode || params.promoCode,
        note: params.note,
      });

      if (response?.success && response.data) {
        // ล้างตะกร้าเมื่อ checkout สำเร็จ
        set({
          items: [],
          summary: defaultSummary,
          totalItems: 0,
          totalPrice: 0,
          totalPV: 0,
          promoCode: null,
          promoDiscount: 0,
          isLoading: false,
        });
        await AsyncStorage.removeItem(CART_STORAGE_KEY);

        return {
          success: true,
          orderId: response.data.orderId,
          orderNumber: response.data.orderNumber,
          pvEarned: response.data.pvEarned,
          message: 'สั่งซื้อสำเร็จ',
        };
      } else {
        set({ isLoading: false });
        return {
          success: false,
          message: response?.message || 'ไม่สามารถสร้างคำสั่งซื้อได้',
        };
      }
    } catch (error) {
      console.error('Checkout error:', error);
      set({
        isLoading: false,
        error: 'เกิดข้อผิดพลาดในการสั่งซื้อ',
      });
      return {
        success: false,
        message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
      };
    }
  },
}));
