/**
 * TaladsodCartBar — แถบตะกร้าตลาดสดลอยท้ายจอ (น้ำเงินกรมท่า + ปุ่มทอง)
 *
 * แสดงเมื่อมีของในตะกร้าเท่านั้น: จำนวนชิ้น · ยอดรวม · ปุ่ม "ดูตะกร้า"
 * ข้อมูลจาก useTaladsodCartStore (โหลดเงียบๆ เมื่อข้อมูลเก่ากว่า 1 นาที)
 *
 * @example
 * <TaladsodCartBar />   // วางท้าย View ราก (position absolute)
 */

import React, { useCallback } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import Animated, { FadeInDown, FadeOutDown } from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useFocusEffect } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '@/components/ui/Text';
import { Icon, formatBaht, tapHaptic, usePressGuard } from '@/components/ui';
import { useAuthStore } from '@/stores/authStore';
import { useTaladsodCartStore } from '@/stores/taladsodCartStore';
import { useTheme, spacing, shadowStyle } from '@/theme';

export const TaladsodCartBar: React.FC<{ bottomOffset?: number }> = ({ bottomOffset = 0 }) => {
  const { colors, gradients } = useTheme();
  const insets = useSafeAreaInsets();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const cart = useTaladsodCartStore((s) => s.cart);
  const count = useTaladsodCartStore((s) => s.count);

  useFocusEffect(
    useCallback(() => {
      if (isAuthenticated) useTaladsodCartStore.getState().ensureFresh(60_000).catch(() => {});
    }, [isAuthenticated])
  );

  const { run: openCart } = usePressGuard(() => {
    tapHaptic();
    router.push('/taladsod/cart' as never);
  });

  if (!isAuthenticated || count <= 0) return null;

  const shopName = cart && cart.shops_count === 1 ? cart.shops[0]?.seller?.shop_name : null;

  return (
    <Animated.View
      entering={FadeInDown.duration(220)}
      exiting={FadeOutDown.duration(160)}
      style={[styles.wrap, { bottom: Math.max(insets.bottom, spacing.md) + bottomOffset }]}
    >
      <Pressable
        onPress={openCart}
        accessibilityRole="button"
        accessibilityLabel={`ดูตะกร้าตลาดสด ${count} ชิ้น ยอดรวม ${formatBaht(cart?.subtotal ?? 0)}`}
      >
        <LinearGradient colors={gradients.navy} style={[styles.bar, shadowStyle('lg', '#060D1B')]}>
          <View style={styles.iconBox}>
            <Icon name="shopping-bag-open" size={21} color={colors.goldLight} />
            <View style={[styles.badge, { backgroundColor: colors.gold }]}>
              <Text style={[styles.badgeText, { color: colors.textOnGold }]}>{count > 99 ? '99+' : count}</Text>
            </View>
          </View>
          <View style={styles.flex}>
            <Text numberOfLines={1} style={[styles.title, { color: '#FFFFFF' }]}>
              {shopName || 'ตะกร้าตลาดสด'}
            </Text>
            <Text style={[styles.sub, { color: 'rgba(214,222,238,0.75)' }]}>
              {count.toLocaleString('th-TH')} ชิ้น · {formatBaht(cart?.subtotal ?? 0)}
            </Text>
          </View>
          <LinearGradient colors={gradients.primary} style={styles.go}>
            <Text style={[styles.goText, { color: colors.textOnGold }]}>ดูตะกร้า</Text>
            <Icon name="arrow-right" size={15} color={colors.textOnGold} weight="bold" />
          </LinearGradient>
        </LinearGradient>
      </Pressable>
    </Animated.View>
  );
};

const styles = StyleSheet.create({
  wrap: {
    position: 'absolute',
    left: spacing.screen,
    right: spacing.screen,
  },
  bar: {
    height: 64,
    borderRadius: 20,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingLeft: spacing.md,
    paddingRight: spacing.sm + 2,
  },
  flex: {
    flex: 1,
  },
  iconBox: {
    width: 42,
    height: 42,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(228,192,107,0.16)',
    borderWidth: 1,
    borderColor: 'rgba(228,192,107,0.3)',
  },
  badge: {
    position: 'absolute',
    top: -6,
    right: -6,
    minWidth: 19,
    height: 19,
    borderRadius: 10,
    paddingHorizontal: 4,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeText: {
    fontSize: 10.5,
    fontWeight: '700',
  },
  title: {
    fontSize: 14.5,
    fontWeight: '700',
  },
  sub: {
    fontSize: 12,
  },
  go: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    height: 42,
    paddingHorizontal: spacing.lg,
    borderRadius: 14,
  },
  goText: {
    fontSize: 14,
    fontWeight: '700',
  },
});

export default TaladsodCartBar;
