/**
 * BannerCard — รูปแคมเปญ + ม่านไล่เฉดมืด + ข้อความไทยวางทับ + ปุ่ม CTA
 *
 * รูปไม่มีตัวหนังสือ (ตามแนวทางแบนเนอร์) → ข้อความทั้งหมดมาจาก props
 * image รับได้ทั้ง URL (string) และรูปในแอป (require)
 *
 * @example
 * <BannerCard
 *   image={require('@/assets/images/taladsod/banner-market.webp')}
 *   title="ตลาดสดใกล้บ้าน"
 *   subtitle="ของสด อาหารร้อนๆ ส่งถึงหน้าบ้าน"
 *   ctaLabel="สั่งเลย"
 *   onPress={() => router.push('/taladsod')}
 * />
 */

import React from 'react';
import { StyleSheet, Text, View, type ImageSourcePropType, type StyleProp, type ViewStyle } from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { useTheme, radii, spacing, typography } from '@/theme';
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
  /** ความสูง (ค่าเริ่มต้น 168) */
  height?: number;
  /** ป้ายมุมซ้ายบน เช่น "ใหม่" */
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
  height = 168,
  badge,
  radius = radii.xl,
  style,
  accessibilityLabel,
}) => {
  const { colors, gradients } = useTheme();
  const source = typeof image === 'string' ? { uri: image } : image || null;

  return (
    <Card3D
      onPress={onPress}
      padding={0}
      radius={radius}
      shadow="md"
      style={style}
      accessibilityLabel={accessibilityLabel || [title, subtitle].filter(Boolean).join(' — ') || 'แบนเนอร์'}
    >
      <View style={[styles.frame, { height, borderRadius: radius, backgroundColor: colors.goldSoft }]}>
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

        <LinearGradient
          colors={gradients.bannerScrim}
          locations={[0.2, 0.55, 1]}
          start={{ x: 0, y: 0 }}
          end={{ x: 0, y: 1 }}
          style={StyleSheet.absoluteFill}
          pointerEvents="none"
        />

        {!!badge && <Pill label={badge} solid style={styles.badge} />}

        <View style={styles.textBlock}>
          {!!title && (
            <Text numberOfLines={2} style={[typography.h1, styles.title]}>
              {title}
            </Text>
          )}
          {!!subtitle && (
            <Text numberOfLines={2} style={[typography.bodySm, styles.subtitle]}>
              {subtitle}
            </Text>
          )}
          {!!ctaLabel && !!onPress && (
            <Button3D title={ctaLabel} onPress={onPress} size="sm" variant="primary" style={styles.cta} haptic />
          )}
        </View>
      </View>
    </Card3D>
  );
};

const styles = StyleSheet.create({
  frame: {
    overflow: 'hidden',
    justifyContent: 'flex-end',
  },
  badge: {
    position: 'absolute',
    top: spacing.md,
    left: spacing.md,
  },
  textBlock: {
    padding: spacing.lg,
    paddingTop: spacing.xxl,
  },
  title: {
    color: '#FFFFFF',
    textShadowColor: 'rgba(0,0,0,0.35)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 4,
  },
  subtitle: {
    color: 'rgba(255,255,255,0.92)',
    marginTop: 2,
    textShadowColor: 'rgba(0,0,0,0.3)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 3,
  },
  cta: {
    alignSelf: 'flex-start',
    marginTop: spacing.md,
  },
});

export default BannerCard;
