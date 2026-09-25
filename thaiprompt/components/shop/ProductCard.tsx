/**
 * ProductCard — การ์ดสินค้าแบบตาราง 2 คอลัมน์ (ธีมนวลทองคำ)
 *
 * แสดงเฉพาะข้อมูลจริงจาก API: รูป ชื่อ ราคา ราคาก่อนลด รีวิว ยอดขาย ส่งด้วยไรเดอร์ได้
 * ไม่มี PV / คอมมิชชั่น (นโยบาย Google Play)
 */

import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { router } from 'expo-router';
import type { ShopProduct } from '@/services/api/shopApi';
import { Card3D, Pill, PriceText } from '@/components/ui';
import { useTheme, radii, spacing, typography } from '@/theme';

export interface ProductCardProps {
  product: ShopProduct;
  /** ความกว้างการ์ด (คำนวณจากหน้าจอ) */
  width: number;
  onPress?: () => void;
}

export const ProductCard: React.FC<ProductCardProps> = ({ product, width, onPress }) => {
  const { colors } = useTheme();
  const image = product.image || (Array.isArray(product.images) ? product.images[0] : null) || null;
  const price = Number(product.price) || 0;
  const original = product.original_price !== null && product.original_price !== undefined ? Number(product.original_price) : null;
  const hasDiscount = original !== null && Number.isFinite(original) && original > price;
  const soldOut = product.in_stock === false;

  const open = onPress || (() => router.push(`/product/${product.id}` as never));

  return (
    <Card3D
      onPress={open}
      padding={0}
      radius={radii.lg}
      shadow="sm"
      style={{ width }}
      accessibilityLabel={`${product.name} ราคา ${price} บาท`}
    >
      <View style={[styles.imageBox, { height: width, backgroundColor: colors.inset }]}>
        {image ? (
          <Image source={{ uri: image }} style={StyleSheet.absoluteFill} contentFit="cover" transition={150} />
        ) : (
          <Text style={styles.noImage}>📦</Text>
        )}
        {hasDiscount && product.discount_percent ? (
          <View style={styles.badge}>
            <Pill label={`-${Math.round(Number(product.discount_percent))}%`} tone="danger" size="sm" solid />
          </View>
        ) : null}
        {soldOut && (
          <View style={[styles.soldOut, { backgroundColor: colors.overlay }]}>
            <Text style={[typography.bodyStrong, { color: colors.textOnAccent }]}>สินค้าหมด</Text>
          </View>
        )}
      </View>

      <View style={styles.body}>
        <Text numberOfLines={2} style={[typography.bodySm, styles.name, { color: colors.textStrong }]}>
          {product.name}
        </Text>
        <View style={styles.priceRow}>
          <PriceText amount={price} size="md" tone="gold" />
          {hasDiscount && <PriceText amount={original} size="xs" tone="muted" strike bold={false} />}
        </View>
        <Text numberOfLines={1} style={[typography.micro, { color: colors.textMuted }]}>
          {Number(product.review_count) > 0 ? `⭐ ${Number(product.rating).toFixed(1)}` : 'ยังไม่มีรีวิว'}
          {Number(product.sales_count) > 0 ? ` · ขายแล้ว ${Number(product.sales_count).toLocaleString('th-TH')}` : ''}
        </Text>
        {(product.store?.rider_delivery || product.is_affiliate) && (
          <View style={styles.pills}>
            {product.store?.rider_delivery && <Pill label="ไรเดอร์ส่ง" icon="🛵" tone="success" size="sm" />}
            {product.is_affiliate && <Pill label="ร้านต้นทาง" tone="info" size="sm" />}
          </View>
        )}
      </View>
    </Card3D>
  );
};

const styles = StyleSheet.create({
  imageBox: {
    width: '100%',
    borderTopLeftRadius: radii.lg,
    borderTopRightRadius: radii.lg,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  noImage: {
    fontSize: 40,
  },
  badge: {
    position: 'absolute',
    top: spacing.sm,
    left: spacing.sm,
  },
  soldOut: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    alignItems: 'center',
    justifyContent: 'center',
  },
  body: {
    padding: spacing.md,
    gap: spacing.xxs,
  },
  name: {
    minHeight: 38,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'baseline',
    gap: spacing.xs,
    flexWrap: 'wrap',
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
});

export default ProductCard;
