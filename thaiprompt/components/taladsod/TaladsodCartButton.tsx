/**
 * TaladsodCartButton — ปุ่มตะกร้าตลาดสดบนหัวหน้าจอ + จำนวนชิ้น (แยกจากตะกร้าร้านค้า)
 *
 * - โหลดตะกร้าเงียบๆ เมื่อข้อมูลเก่ากว่า 1 นาที · ยังไม่ล็อกอิน = พาไปหน้าเข้าสู่ระบบ
 * - จำนวนเปลี่ยน → ป้ายเด้งเบาๆ (รู้ว่าใส่ตะกร้าแล้ว)
 * - บนหัวน้ำเงินเป็นปุ่มกระจกอัตโนมัติ (IconButton)
 */

import React, { useCallback, useEffect } from 'react';
import { useAnimatedStyle, useSharedValue, withSequence, withSpring, withTiming } from 'react-native-reanimated';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { useTaladsodCartStore } from '@/stores/taladsodCartStore';
import { IconButton } from '@/components/ui/IconButton';

export const TaladsodCartButton: React.FC = () => {
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

  return (
    <IconButton
      icon="shopping-bag-open"
      label={count > 0 ? `ตะกร้าตลาดสด มี ${count} ชิ้น` : 'ตะกร้าตลาดสด'}
      badge={isAuthenticated ? count : 0}
      badgeAnimatedStyle={badgeAnim}
      onPress={() => router.push((isAuthenticated ? '/taladsod/cart' : '/login') as never)}
    />
  );
};

/** ปุ่มไอคอนบนหัวหน้าจอ (ใช้คู่กับปุ่มตะกร้า) — icon = ชื่อไอคอน (อีโมจิเดิมแปลงให้) */
export const HeaderIconButton: React.FC<{ icon: string; label: string; onPress: () => void }> = ({ icon, label, onPress }) => (
  <IconButton icon={icon} label={label} onPress={onPress} />
);
