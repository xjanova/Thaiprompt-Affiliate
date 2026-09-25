/**
 * CartButton — ปุ่มตะกร้าบนหัวหน้าจอ + ตัวเลขจำนวนชิ้นจากตะกร้าบน server
 *
 * - โหลดตะกร้าเงียบๆ เมื่อข้อมูลเก่ากว่า 1 นาที (ไม่บังหน้าจอ)
 * - ยังไม่เข้าสู่ระบบ → กดแล้วพาไปหน้าเข้าสู่ระบบ
 */

import React, { useCallback } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { useCartStore } from '@/stores/cartStore';
import { tapHaptic } from '@/components/ui';
import { useTheme, clayShadowStyle, radii } from '@/theme';

export const CartButton: React.FC = () => {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const count = useCartStore((s) => s.count);

  useFocusEffect(
    useCallback(() => {
      if (isAuthenticated) {
        useCartStore.getState().ensureFresh(60_000).catch(() => {});
      }
    }, [isAuthenticated])
  );

  const open = () => {
    tapHaptic();
    router.push((isAuthenticated ? '/cart' : '/login') as never);
  };

  const label = count > 0 ? `ตะกร้าสินค้า มี ${count} ชิ้น` : 'ตะกร้าสินค้า';

  return (
    <Pressable
      onPress={open}
      accessibilityRole="button"
      accessibilityLabel={label}
      hitSlop={8}
      style={({ pressed }) => [
        styles.button,
        { backgroundColor: colors.card, opacity: pressed ? 0.75 : 1, transform: [{ scale: pressed ? 0.96 : 1 }] },
        clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
      ]}
    >
      <Text style={styles.icon}>🛒</Text>
      {count > 0 && (
        <View style={[styles.badge, { backgroundColor: colors.danger, borderColor: colors.card }]}>
          <Text style={[styles.badgeText, { color: colors.textOnAccent }]}>{count > 99 ? '99+' : count}</Text>
        </View>
      )}
    </Pressable>
  );
};

const styles = StyleSheet.create({
  button: {
    width: 44,
    height: 44,
    borderRadius: radii.md,
    alignItems: 'center',
    justifyContent: 'center',
  },
  icon: {
    fontSize: 20,
  },
  badge: {
    position: 'absolute',
    top: -4,
    right: -4,
    minWidth: 20,
    height: 20,
    borderRadius: 10,
    paddingHorizontal: 4,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeText: {
    fontSize: 10,
    fontWeight: '800',
  },
});

export default CartButton;
