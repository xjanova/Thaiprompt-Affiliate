/**
 * Cart Store - จัดการระบบตะกร้าสินค้า
 * ใช้ Zustand สำหรับ state management
 * รองรับ Offline Mode - เก็บตะกร้าใน local storage
 */

import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';
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
}

// Cart State Type
interface CartState {
  // State
  items: CartItem[];
  isLoading: boolean;
  error: string | null;

  // Computed (จะคำนวณเอง)
  totalItems: number;
  totalPrice: number;
  totalPV: number;

  // Actions
  initialize: () => Promise<void>;
  addItem: (product: Product, quantity?: number, variant?: CartItem['selectedVariant']) => void;
  removeItem: (itemId: string) => void;
  updateQuantity: (itemId: string, quantity: number) => void;
  clearCart: () => void;
  clearError: () => void;
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
 * คำนวณ totals จาก items
 */
const calculateTotals = (items: CartItem[]) => {
  const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);
  const totalPrice = items.reduce((sum, item) => {
    const price = item.selectedVariant?.price || item.product.discount_price || item.product.price;
    return sum + (price * item.quantity);
  }, 0);
  const totalPV = items.reduce((sum, item) => {
    const pv = (item.product as any).pv || Math.round(item.product.price * 0.1);
    return sum + (pv * item.quantity);
  }, 0);

  return { totalItems, totalPrice, totalPV };
};

export const useCartStore = create<CartState>((set, get) => ({
  // Initial State
  items: [],
  isLoading: false,
  error: null,
  totalItems: 0,
  totalPrice: 0,
  totalPV: 0,

  /**
   * Initialize - โหลดตะกร้าจาก storage
   */
  initialize: async () => {
    try {
      set({ isLoading: true });
      const items = await loadCartFromStorage();
      const totals = calculateTotals(items);
      set({
        items,
        ...totals,
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
   * เพิ่มสินค้าลงตะกร้า
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
      newItems[existingIndex] = {
        ...newItems[existingIndex],
        quantity: newItems[existingIndex].quantity + quantity,
      };
    } else {
      // เพิ่มใหม่
      const newItem: CartItem = {
        id: `${product.id}_${variant?.id || 'default'}_${Date.now()}`,
        product,
        quantity,
        selectedVariant: variant,
        addedAt: new Date(),
      };
      newItems = [...items, newItem];
    }

    const totals = calculateTotals(newItems);
    set({ items: newItems, ...totals });
    saveCartToStorage(newItems);
  },

  /**
   * ลบสินค้าออกจากตะกร้า
   */
  removeItem: (itemId: string) => {
    const { items } = get();
    const newItems = items.filter(item => item.id !== itemId);
    const totals = calculateTotals(newItems);
    set({ items: newItems, ...totals });
    saveCartToStorage(newItems);
  },

  /**
   * อัพเดทจำนวนสินค้า
   */
  updateQuantity: (itemId: string, quantity: number) => {
    if (quantity < 1) {
      get().removeItem(itemId);
      return;
    }

    const { items } = get();
    const newItems = items.map(item =>
      item.id === itemId ? { ...item, quantity } : item
    );
    const totals = calculateTotals(newItems);
    set({ items: newItems, ...totals });
    saveCartToStorage(newItems);
  },

  /**
   * ล้างตะกร้า
   */
  clearCart: () => {
    set({
      items: [],
      totalItems: 0,
      totalPrice: 0,
      totalPV: 0,
    });
    saveCartToStorage([]);
  },

  /**
   * ล้าง error
   */
  clearError: () => {
    set({ error: null });
  },
}));
