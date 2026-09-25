/**
 * BannerCard — รูปแคมเปญ + ม่านน้ำเงินกรมท่าจากซ้าย + หัวข้อฟอนต์มีเชิง + ปุ่มทอง
 *
 * รูปไม่มีตัวหนังสือ (ตามแนวทางแบนเนอร์) → ข้อความทั้งหมดมาจาก props
 * ตัวหนังสือวางซ้าย รูปเด่นควรอยู่ครึ่งขวา (ภาพที่สร้างให้แบรนด์เว้นที่ซ้ายไว้แล้ว)
 * image รับได้ทั้ง URL (string) และรูปในแอป (require)
 *
 * @example
 * <BannerCard
 *   image={require('@/assets/images/brand/night-market.webp')}
 *   title="ตลาดสดใกล้บ้าน"
 *   subtitle="ของสด อาหารร้อนๆ ส่งถึงหน้าบ้าน"
 *   ctaLabel="สั่งเลย"
 *   onPress={() => router.push('/taladsod')}
 * />
 */

import React from 'react';
import { StyleSheet, View, type ImageSourcePropType, type StyleProp, type ViewStyle } from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import { useTheme, spacing, typography } from '@/theme';
import { Card3D } from './Card3D';
import { Button3D } from './Button3D';
import { Pill } from './Chip';

export interface BannerCardProps {
  /** URL (https) หรือ require('...') */
  image: string | ImageSourcePropType | null | undefined;
  title?: string;
  subtitle?: string | null;
  ctaLabel?: string | null;
  onPress?: () => unknown;
  /** ความสูง (ค่าเริ่มต้น 172) */
  height?: number;
  /** ป้ายเหนือหัวข้อ เช่น "ใหม่" */
  badge?: string;
  radius?: number;
  style?: StyleProp<ViewStyle>;
  accessibilityLabel?: string;
}

export const BannerCard: React.FC<BannerCardProps> = ({
  image,
  title,
  subtitle,
  ctaLabel,
  onPress,
  height = 172,
  badge,
  radius = 24,
  style,
  accessibilityLabel,
}) => {
  const { colors } = useTheme();
  const source = typeof image === 'string' ? { uri: image } : image || null;

  return (
    <Card3D
      onPress={onPress}
      padding={0}
      radius={radius}
      shadow="lg"
      style={style}
      accessibilityLabel={accessibilityLabel || [title, subtitle].filter(Boolean).join(' — ') || 'แบนเนอร์'}
    >
      <View style={[styles.frame, { height, borderRadius: radius, backgroundColor: '#0C1A33' }]}>
        {source ? (
          <Image
            source={source}
            style={StyleSheet.absoluteFill}
            contentFit="cover"
            transition={200}
            cachePolicy="memory-disk"
            accessibilityIgnoresInvertColors
          />
        ) : null}
        {/* ม่านน้ำเงินกรมท่าจากซ้าย ให้ตัวหนังสืออ่านง่ายทุกภาพ */}
        <LinearGradient
          colors={['rgba(7,14,28,0.94)', 'rgba(7,14,28,0.68)', 'rgba(7,14,28,0)']}
          locations={[0, 0.46, 0.88]}
          start={{ x: 0, y: 0.5 }}
          end={{ x: 1, y: 0.5 }}
          style={StyleSheet.absoluteFill}
          pointerEvents="none"
        />
        <View style={styles.textBlock}>
          {!!badge && <Pill label={badge} solid style={styles.badge} />}
          {!!title && (
            <Text numberOfLines={2} style={[typography.serif, styles.title]}>
              {title}
            </Text>
          )}
          {!!subtitle && (
            <Text numberOfLines={2} style={[typography.bodySm, styles.subtitle]}>
              {subtitle}
            </Text>
          )}
          {!!ctaLabel && !!onPress && (
            <Button3D
              title={ctaLabel}
              onPress={onPress}
              size="sm"
              variant="primary"
              iconRight="arrow-right"
              style={styles.cta}
              haptic
            />
          )}
        </View>
      </View>
    </Card3D>
  );
};

const styles = StyleSheet.create({
  frame: {
    overflow: 'hidden',
    justifyContent: 'center',
  },
  badge: {
    marginBottom: spacing.xs + 2,
  },
  textBlock: {
    paddingHorizontal: spacing.xl,
    paddingVertical: spacing.lg,
    maxWidth: '70%',
  },
  title: {
    color: '#FFFFFF',
  },
  subtitle: {
    color: 'rgba(235,238,245,0.86)',
    marginTop: 2,
  },
  cta: {
    alignSelf: 'flex-start',
    marginTop: spacing.md,
  },
});

export default BannerCard;
