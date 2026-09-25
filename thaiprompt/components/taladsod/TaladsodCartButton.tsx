/**
 * TaladsodCartButton — ปุ่มตะกร้าตลาดสดบนหัวหน้าจอ + จำนวนชิ้น (แยกจากตะกร้าร้านค้า)
 *
 * - โหลดตะกร้าเงียบๆ เมื่อข้อมูลเก่ากว่า 1 นาที · ยังไม่ล็อกอิน = พาไปหน้าเข้าสู่ระบบ
 * - จำนวนเปลี่ยน → ป้ายเด้งเบาๆ (รู้ว่าใส่ตะกร้าแล้ว)
 */

import React, { useCallback, useEffect } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSequence, withSpring, withTiming } from 'react-native-reanimated';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { useTaladsodCartStore } from '@/stores/taladsodCartStore';
import { tapHaptic } from '@/components/ui';
import { useTheme, clayShadowStyle, radii } from '@/theme';

export const TaladsodCartButton: React.FC = () => {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const count = useTaladsodCartStore((s) => s.count);
  const bump = useSharedValue(1);

  useFocusEffect(
    useCallback(() => {
      useTaladsodCartStore.getState().ensureFresh(60_000).catch(() => {});
    }, [])
  );

  useEffect(() => {
    if (count > 0) {
      bump.value = withSequence(withTiming(1.35, { duration: 120 }), withSpring(1, { damping: 8, stiffness: 220 }));
    }
  }, [count, bump]);

  const badgeAnim = useAnimatedStyle(() => ({ transform: [{ scale: bump.value }] }));

  const open = () => {
    tapHaptic();
    router.push((isAuthenticated ? '/taladsod/cart' : '/login') as never);
  };

  return (
    <Pressable
      onPress={open}
      accessibilityRole="button"
      accessibilityLabel={count > 0 ? `ตะกร้าตลาดสด มี ${count} ชิ้น` : 'ตะกร้าตลาดสด'}
      hitSlop={8}
      style={({ pressed }) => [
        styles.button,
        { backgroundColor: colors.card, opacity: pressed ? 0.75 : 1, transform: [{ scale: pressed ? 0.96 : 1 }] },
        clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
      ]}
    >
      <Text style={styles.icon}>🧺</Text>
      {isAuthenticated && count > 0 && (
        <Animated.View style={[styles.badge, { backgroundColor: colors.danger, borderColor: colors.card }, badgeAnim]}>
          <Text style={[styles.badgeText, { color: colors.textOnAccent }]}>{count > 99 ? '99+' : count}</Text>
        </Animated.View>
      )}
    </Pressable>
  );
};

/** ปุ่มไอคอนกลมบนหัวหน้าจอ (ใช้คู่กับปุ่มตะกร้า) */
export const HeaderIconButton: React.FC<{ icon: string; label: string; onPress: () => void }> = ({ icon, label, onPress }) => {
  const { colors } = useTheme();
  return (
    <Pressable
      onPress={() => {
        tapHaptic();
        onPress();
      }}
      accessibilityRole="button"
      accessibilityLabel={label}
      hitSlop={8}
      style={({ pressed }) => [
        styles.button,
        { backgroundColor: colors.card, opacity: pressed ? 0.75 : 1, transform: [{ scale: pressed ? 0.96 : 1 }] },
        clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
      ]}
    >
      <Text style={styles.icon}>{icon}</Text>
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
    top: -5,
    right: -5,
    minWidth: 20,
    height: 20,
    borderRadius: 10,
    paddingHorizontal: 4,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
  },
  badgeText: {
    fontSize: 10,
    fontWeight: '800',
  },
});
