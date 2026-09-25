/**
 * CartButton — ปุ่มตะกร้าบนหัวหน้าจอ + ตัวเลขจำนวนชิ้นจากตะกร้าบน server
 *
 * - โหลดตะกร้าเงียบๆ เมื่อข้อมูลเก่ากว่า 1 นาที (ไม่บังหน้าจอ)
 * - ยังไม่เข้าสู่ระบบ → กดแล้วพาไปหน้าเข้าสู่ระบบ
 * - บนหัวน้ำเงินเป็นปุ่มกระจกอัตโนมัติ (IconButton)
 */

import React, { useCallback } from 'react';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { useCartStore } from '@/stores/cartStore';
import { IconButton } from '@/components/ui/IconButton';

export const CartButton: React.FC = () => {
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const count = useCartStore((s) => s.count);

  useFocusEffect(
    useCallback(() => {
      if (isAuthenticated) {
        useCartStore.getState().ensureFresh(60_000).catch(() => {});
      }
    }, [isAuthenticated])
  );

  return (
    <IconButton
      icon="shopping-cart-simple"
      label={count > 0 ? `ตะกร้าสินค้า มี ${count} ชิ้น` : 'ตะกร้าสินค้า'}
      badge={count}
      onPress={() => router.push((isAuthenticated ? '/cart' : '/login') as never)}
    />
  );
};

export default CartButton;
