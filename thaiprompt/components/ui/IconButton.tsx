/**
 * IconButton — ปุ่มไอคอนสี่เหลี่ยมมุมมน (ตะกร้า แชร์ ออเดอร์ ฯลฯ)
 *
 * - วางบนหัวน้ำเงิน (ใน right ของ <Screen> หรือใน OnHeaderProvider) → ปุ่มกระจกไอคอนสว่างอัตโนมัติ
 * - วางบนพื้นสว่าง → การ์ดขาวขอบบาง ไอคอนน้ำเงิน
 * - badge = ตัวเลขมุมขวาบน (ป้ายทอง) · badgeAnimatedStyle = ใส่แอนิเมชันเด้งเองได้
 *
 * @example
 * <IconButton icon="share-network" label="แชร์สินค้า" onPress={share} />
 */

import React from 'react';
import { Pressable, StyleSheet, type StyleProp, type ViewStyle } from 'react-native';
import Animated from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { useTheme, shadowStyle } from '@/theme';
import { Text } from './Text';
import { IconSlot, type IconWeight } from './Icon';
import { useOnHeader } from './RoyalHeader';
import { tapHaptic } from './haptics';

export interface IconButtonProps {
  /** ชื่อไอคอน (อีโมจิเดิมจะถูกแปลงให้) */
  icon: string;
  label: string;
  onPress?: () => void;
  badge?: number;
  badgeAnimatedStyle?: object;
  size?: number;
  weight?: IconWeight;
  style?: StyleProp<ViewStyle>;
}

export const IconButton: React.FC<IconButtonProps> = ({
  icon,
  label,
  onPress,
  badge,
  badgeAnimatedStyle,
  size = 42,
  weight = 'regular',
  style,
}) => {
  const { colors, gradients } = useTheme();
  const onHeader = useOnHeader();

  const look: ViewStyle = onHeader
    ? { backgroundColor: colors.headerGlass, borderWidth: 1, borderColor: colors.headerGlassBorder }
    : { backgroundColor: colors.card, borderWidth: 1, borderColor: colors.border, ...shadowStyle('sm', colors.shadowDark) };

  return (
    <Pressable
      onPress={() => {
        tapHaptic();
        onPress?.();
      }}
      disabled={!onPress}
      accessibilityRole="button"
      accessibilityLabel={label}
      hitSlop={8}
      style={({ pressed }) => [
        styles.button,
        { width: size, height: size, borderRadius: size * 0.36, opacity: pressed ? 0.75 : 1, transform: [{ scale: pressed ? 0.96 : 1 }] },
        look,
        style,
      ]}
    >
      <IconSlot icon={icon} size={size * 0.5} color={onHeader ? colors.onHeader : colors.navy} weight={weight} />
      {typeof badge === 'number' && badge > 0 && (
        <Animated.View style={[styles.badgeWrap, badgeAnimatedStyle]}>
          <LinearGradient colors={gradients.primary} style={styles.badge}>
            <Text style={[styles.badgeText, { color: colors.textOnGold }]}>{badge > 99 ? '99+' : badge}</Text>
          </LinearGradient>
        </Animated.View>
      )}
    </Pressable>
  );
};

const styles = StyleSheet.create({
  button: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeWrap: {
    position: 'absolute',
    top: -6,
    right: -6,
  },
  badge: {
    minWidth: 20,
    height: 20,
    borderRadius: 10,
    paddingHorizontal: 5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
});

export default IconButton;
