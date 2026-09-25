/**
 * ProductCard — การ์ดสินค้าแบบตาราง 2 คอลัมน์ (ธีมรอยัล น้ำเงินกรมท่า-ทอง)
 *
 * การ์ดขาว มุม 20 · รูปจัตุรัสเต็มหัวการ์ด · ป้ายส่วนลดแดงมุมซ้ายบน · ป้าย "ไรเดอร์ส่ง" แบบกระจกบนรูป
 * ใต้รูป: ชื่อ 2 บรรทัด · ดาว/ยอดขาย · ราคา + ปุ่ม "+" น้ำเงินกรมท่า (แตะทั้งการ์ด = เปิดหน้าสินค้า)
 *
 * แสดงเฉพาะข้อมูลจริงจาก API: รูป ชื่อ ราคา ราคาก่อนลด รีวิว ยอดขาย ส่งด้วยไรเดอร์ได้
 * ไม่มี PV / คอมมิชชั่น (นโยบาย Google Play)
 */

import React from 'react';
import { StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import type { ShopProduct } from '@/services/api/shopApi';
import { Card3D, Icon, Pill, PriceText } from '@/components/ui';
import { useTheme, palette, spacing, typography } from '@/theme';

export interface ProductCardProps {
  product: ShopProduct;
  /** ความกว้างการ์ด (คำนวณจากหน้าจอ) */
  width: number;
  onPress?: () => void;
}

/** มุมการ์ดสินค้า */
const CARD_RADIUS = 20;
/** ม่านเข้มบนรูปสำหรับป้ายกระจก (บนรูปต้องมืดเสมอทั้งสองโหมด) */
const PHOTO_GLASS = 'rgba(8, 14, 28, 0.62)';

export const ProductCard: React.FC<ProductCardProps> = ({ product, width, onPress }) => {
  const { colors, gradients } = useTheme();
  const image = product.image || (Array.isArray(product.images) ? product.images[0] : null) || null;
  const price = Number(product.price) || 0;
  const original = product.original_price !== null && product.original_price !== undefined ? Number(product.original_price) : null;
  const hasDiscount = original !== null && Number.isFinite(original) && original > price;
  const soldOut = product.in_stock === false;
  const reviewCount = Number(product.review_count) || 0;
  const salesCount = Number(product.sales_count) || 0;

  const open = onPress || (() => router.push(`/product/${product.id}` as never));

  return (
    <Card3D
      onPress={open}
      padding={0}
      radius={CARD_RADIUS}
      shadow="sm"
      style={{ width }}
      accessibilityLabel={`${product.name} ราคา ${price} บาท`}
    >
      <View style={[styles.imageBox, { height: width, backgroundColor: colors.inset }]}>
        {image ? (
          <Image source={{ uri: image }} style={StyleSheet.absoluteFill} contentFit="cover" transition={150} />
        ) : (
          <Icon name="package" size={Math.round(width * 0.26)} color={colors.textFaint} />
        )}

        {hasDiscount && product.discount_percent ? (
          <View style={[styles.discount, { backgroundColor: palette.red500 }]}>
            <Text style={[styles.discountText, { color: colors.textOnAccent }]}>
              -{Math.round(Number(product.discount_percent))}%
            </Text>
          </View>
        ) : null}

        {soldOut && (
          <View style={[styles.soldOut, { backgroundColor: colors.overlay }]}>
            <View style={[styles.soldOutTag, { borderColor: colors.headerGlassBorder, backgroundColor: colors.headerGlass }]}>
              <Text style={[typography.bodyStrong, { color: colors.onHeader }]}>สินค้าหมด</Text>
            </View>
          </View>
        )}

        {product.store?.rider_delivery && (
          <View style={[styles.photoPill, { backgroundColor: PHOTO_GLASS }]}>
            <Icon name="moped" size={13} color={colors.textOnAccent} weight="fill" />
            <Text style={[styles.photoPillText, { color: colors.textOnAccent }]}>ไรเดอร์ส่ง</Text>
          </View>
        )}
      </View>

      <View style={styles.body}>
        <Text numberOfLines={2} style={[typography.bodySm, styles.name, { color: colors.textStrong }]}>
          {product.name}
        </Text>

        <View style={styles.metaRow}>
          {reviewCount > 0 ? (
            <>
              <Icon name="star" size={12} color={colors.gold} weight="fill" />
              <Text style={[typography.micro, { color: colors.text }]}>{Number(product.rating).toFixed(1)}</Text>
            </>
          ) : (
            <Text style={[typography.micro, { color: colors.textFaint }]}>ยังไม่มีรีวิว</Text>
          )}
          {salesCount > 0 && (
            <Text numberOfLines={1} style={[typography.micro, styles.flex, { color: colors.textMuted }]}>
              {` · ขายแล้ว ${salesCount.toLocaleString('th-TH')}`}
            </Text>
          )}
        </View>

        {product.is_affiliate && <Pill label="ร้านต้นทาง" tone="info" size="sm" style={styles.pill} />}

        <View style={styles.priceRow}>
          <View style={styles.priceCol}>
            <PriceText amount={price} size="md" tone="strong" />
            {hasDiscount && <PriceText amount={original} size="xs" tone="muted" strike bold={false} />}
          </View>
          {!soldOut && (
            <LinearGradient
              colors={gradients.navy}
              start={{ x: 0, y: 0 }}
              end={{ x: 0, y: 1 }}
              style={styles.addButton}
              pointerEvents="none"
            >
              <Icon name={product.is_affiliate ? 'arrow-up-right' : 'plus'} size={18} color={colors.goldLight} weight="bold" />
            </LinearGradient>
          )}
        </View>
      </View>
    </Card3D>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  imageBox: {
    width: '100%',
    borderTopLeftRadius: CARD_RADIUS,
    borderTopRightRadius: CARD_RADIUS,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  discount: {
    position: 'absolute',
    top: spacing.sm,
    left: spacing.sm,
    borderRadius: 8,
    paddingHorizontal: 7,
    paddingVertical: 2,
  },
  discountText: {
    fontSize: 11.5,
    lineHeight: 16,
    fontWeight: '700',
  },
  photoPill: {
    position: 'absolute',
    left: spacing.sm,
    bottom: spacing.sm,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    borderRadius: 999,
    paddingHorizontal: 8,
    paddingVertical: 3,
  },
  photoPillText: {
    fontSize: 11,
    lineHeight: 15,
    fontWeight: '600',
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
  soldOutTag: {
    borderRadius: 999,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
  },
  body: {
    paddingHorizontal: spacing.md,
    paddingTop: spacing.md,
    paddingBottom: spacing.md,
  },
  name: {
    minHeight: 38,
    fontWeight: '600',
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 3,
    marginTop: 2,
  },
  pill: {
    marginTop: spacing.xs,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    gap: spacing.xs,
    marginTop: spacing.sm,
  },
  priceCol: {
    flex: 1,
  },
  addButton: {
    width: 34,
    height: 34,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
});

export default ProductCard;
