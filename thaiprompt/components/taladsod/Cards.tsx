/**
 * การ์ดของตลาดสด — ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * - ListingCard        เมนูในกริด 2 คอลัมน์ (รูปใหญ่มุมมน · ราคา · ร้าน + สถานะเปิด/ปิด · ปุ่ม + น้ำเงิน)
 * - NearbyShopCard     ร้านที่เปิดอยู่ใกล้คุณ (แนวนอน) รูปใหญ่ + ป้ายเปิดอยู่ + ระยะทาง + เมนูเด่น
 * - FollowedShopBubble ร้านที่ติดตาม (วงกลม วงเขียวเมื่อเปิด · ปิดอยู่จะจางลง)
 * - ShopAvatar         รูปร้าน (ไม่มีรูป = ภาพ 3D รถเข็น/ร้านค้าประจำแบรนด์)
 */

import React from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { BrandArt, Card3D, Icon, Pill, PriceText, tapHaptic } from '@/components/ui';
import { useTheme, radii, spacing, typography, shadowStyle } from '@/theme';
import { formatDistance } from '@/services/location';
import { fmImageUri, type FmFollowedShop, type FmListingSummary, type FmNearbyShop, type FmShopListing } from '@/services/api/taladsodApi';
import { OpenPill, ShopStatusRow } from './ShopBadges';

const FALLBACK_FOOD = require('@/assets/images/taladsod/krapao-hero.webp');

/** ป้ายสถานะบนรูป (กระจกมืด อ่านออกบนรูปทุกสี) */
const PhotoTag: React.FC<{ open?: boolean; label: string }> = ({ open, label }) => (
  <View style={styles.photoTag}>
    {typeof open === 'boolean' && <View style={[styles.photoDot, { backgroundColor: open ? '#3DDC84' : '#B9BFCA' }]} />}
    <Text style={styles.photoTagText}>{label}</Text>
  </View>
);

// =====================================================
// ListingCard
// =====================================================

export interface ListingCardProps {
  listing: FmListingSummary | FmShopListing;
  width: number;
  /** ซ่อนชื่อร้าน (ใช้ในหน้าร้าน) */
  hideShop?: boolean;
  /** ร้านเปิดอยู่ไหม (หน้าร้านส่งมาเอง) */
  shopOpen?: boolean | null;
}

export const ListingCard: React.FC<ListingCardProps> = ({ listing, width, hideShop = false, shopOpen }) => {
  const { colors, gradients } = useTheme();
  const summary = 'seller' in listing ? listing : null;
  const open = shopOpen ?? summary?.shop_is_open ?? summary?.seller?.is_open ?? null;
  const closed = open === false;
  const image = fmImageUri(listing.main_image_url);
  const hasDiscount = listing.compare_at_price !== null && listing.compare_at_price > listing.price;
  const hasOptions = listing.has_options === true;
  const imageHeight = Math.round(width * 0.82);

  return (
    <Card3D
      onPress={() => router.push(`/taladsod/listing/${listing.id}` as never)}
      padding={0}
      radius={20}
      shadow="md"
      style={{ width }}
      accessibilityLabel={`${listing.title} ราคา ${listing.price} บาท${closed ? ' ร้านปิดอยู่' : ''}`}
    >
      <View style={[styles.listingImageWrap, { height: imageHeight, backgroundColor: colors.inset }]}>
        <Image
          source={image ? { uri: image } : FALLBACK_FOOD}
          style={[StyleSheet.absoluteFill, closed && styles.dimImage]}
          contentFit="cover"
          transition={160}
          recyclingKey={`l-${listing.id}`}
        />
        <View style={styles.listingBadges}>
          {hasDiscount && <Pill label="ลดราคา" tone="danger" />}
          {hasOptions && <Pill label="เลือกได้" solid />}
        </View>
        {closed && (
          <View style={styles.closedTag}>
            <PhotoTag open={false} label="ร้านปิดอยู่" />
          </View>
        )}
      </View>
      <View style={styles.listingBody}>
        <Text numberOfLines={2} style={[styles.listingTitle, { color: colors.textStrong }]}>
          {listing.title}
        </Text>
        {!hideShop && summary?.seller && (
          <View style={styles.shopRow}>
            <View style={[styles.dot, { backgroundColor: summary.seller.is_open ? colors.success : colors.textFaint }]} />
            <Text numberOfLines={1} style={[typography.caption, styles.flex, { color: colors.textMuted }]}>
              {summary.seller.shop_name}
              {summary?.distance_km !== null && summary?.distance_km !== undefined ? ` · ${formatDistance(summary.distance_km)}` : ''}
            </Text>
          </View>
        )}
        <View style={styles.priceRow}>
          <View style={styles.priceCol}>
            <PriceText amount={listing.price} size="md" tone="strong" suffix={listing.unit ? `/${listing.unit}` : undefined} />
            {hasDiscount && <PriceText amount={listing.compare_at_price} size="xs" tone="muted" strike bold={false} />}
          </View>
          <LinearGradient colors={gradients.navy} style={[styles.addButton, shadowStyle('sm', colors.shadowDark)]}>
            <Icon name="plus" size={17} color={colors.goldLight} weight="bold" />
          </LinearGradient>
        </View>
      </View>
    </Card3D>
  );
};

// =====================================================
// NearbyShopCard
// =====================================================

export const NearbyShopCard: React.FC<{ shop: FmNearbyShop; width: number }> = ({ shop, width }) => {
  const { colors } = useTheme();
  const image = fmImageUri(shop.shop_image);
  const cover = image || fmImageUri(shop.top_items[0]?.image_url ?? null);
  const label = shop.location?.label || shop.presence.location_label;

  return (
    <Card3D
      onPress={() => router.push(`/taladsod/shop/${shop.id}` as never)}
      padding={0}
      radius={22}
      shadow="md"
      style={{ width }}
      accessibilityLabel={`${shop.shop_name} ${shop.is_open ? 'เปิดอยู่' : 'ปิดอยู่'} ห่าง ${formatDistance(shop.distance_km)}`}
    >
      <View style={[styles.shopCover, { backgroundColor: colors.goldSoft }]}>
        {cover ? (
          <Image source={{ uri: cover }} style={[StyleSheet.absoluteFill, !shop.is_open && styles.dimImage]} contentFit="cover" transition={140} />
        ) : (
          <View style={styles.coverArt}>
            <BrandArt name={shop.is_mobile ? 'cart' : 'store'} size={96} />
          </View>
        )}
        <LinearGradient colors={['rgba(0,0,0,0)', 'rgba(0,0,0,0.38)']} style={styles.coverScrim} pointerEvents="none" />
        <View style={styles.coverTopLeft}>
          <PhotoTag open={shop.is_open} label={shop.is_open ? 'เปิดอยู่' : 'ปิดอยู่'} />
        </View>
        {shop.is_following && (
          <View style={styles.coverTopRight}>
            <Icon name="heart" size={16} color="#FFFFFF" weight="fill" />
          </View>
        )}
        <View style={styles.coverBottom}>
          <Icon name="navigation-arrow" size={13} color="#FFFFFF" weight="fill" />
          <Text style={styles.coverBottomText}>{formatDistance(shop.distance_km)}</Text>
        </View>
      </View>

      <View style={styles.shopInfo}>
        <View style={styles.nameRow}>
          <Text numberOfLines={1} style={[typography.h3, styles.flexShrink, { color: colors.textStrong }]}>
            {shop.shop_name}
          </Text>
          {shop.is_mobile && <Pill label="รถเข็น" tone="gold" />}
        </View>
        <View style={styles.metaRow}>
          {shop.rating_count > 0 ? (
            <>
              <Icon name="star" size={13} color={colors.gold} weight="fill" />
              <Text style={[typography.caption, { color: colors.textStrong }]}>{shop.rating_average.toFixed(1)}</Text>
              <Text style={[typography.caption, { color: colors.textFaint }]}>({shop.rating_count})</Text>
            </>
          ) : (
            <Text style={[typography.caption, { color: colors.textFaint }]}>ร้านใหม่</Text>
          )}
          {!!label && (
            <>
              <View style={[styles.metaDot, { backgroundColor: colors.textFaint }]} />
              <Text numberOfLines={1} style={[typography.caption, styles.flexShrink, { color: colors.textMuted }]}>
                {label}
              </Text>
            </>
          )}
        </View>

        {shop.top_items.length > 0 ? (
          <View style={styles.topItems}>
            {shop.top_items.slice(0, 3).map((item) => {
              const uri = fmImageUri(item.image_url);
              return (
                <View key={item.id} style={styles.topItem}>
                  <Image
                    source={uri ? { uri } : FALLBACK_FOOD}
                    style={[styles.topItemImg, { backgroundColor: colors.inset }]}
                    contentFit="cover"
                    transition={120}
                  />
                  <Text numberOfLines={1} style={[typography.micro, { color: colors.text }]}>
                    {item.title}
                  </Text>
                  <PriceText amount={item.price} size="xs" tone="gold" />
                </View>
              );
            })}
          </View>
        ) : (
          <Text style={[typography.caption, styles.gapTopSm, { color: colors.textFaint }]}>
            {shop.listings_count > 0 ? `${shop.listings_count} เมนู` : 'แวะดูเมนูในร้านได้เลย'}
          </Text>
        )}
      </View>
    </Card3D>
  );
};

// =====================================================
// FollowedShopBubble
// =====================================================

export const FollowedShopBubble: React.FC<{ shop: FmFollowedShop }> = ({ shop }) => {
  const { colors } = useTheme();
  const image = fmImageUri(shop.shop_image);

  return (
    <Pressable
      onPress={() => {
        tapHaptic();
        router.push(`/taladsod/shop/${shop.id}` as never);
      }}
      accessibilityRole="button"
      accessibilityLabel={`${shop.shop_name} ${shop.is_open ? 'เปิดอยู่' : 'ปิดอยู่'}`}
      style={({ pressed }) => [styles.bubble, { opacity: pressed ? 0.8 : 1, transform: [{ scale: pressed ? 0.96 : 1 }] }]}
    >
      <View
        style={[
          styles.bubbleRing,
          { borderColor: shop.is_open ? colors.success : colors.border },
        ]}
      >
        <View style={[styles.bubbleCircle, { backgroundColor: colors.goldSoft }]}>
          {image ? (
            <Image
              source={{ uri: image }}
              style={[styles.bubbleImg, !shop.is_open && styles.dimImage]}
              contentFit="cover"
              transition={120}
            />
          ) : (
            <BrandArt name={shop.is_mobile ? 'cart' : 'store'} size={44} />
          )}
        </View>
      </View>
      <Text numberOfLines={1} style={[typography.caption, styles.bubbleName, { color: colors.textStrong }]}>
        {shop.shop_name}
      </Text>
      <Text style={[typography.micro, { color: shop.is_open ? colors.success : colors.textFaint }]}>
        {shop.is_open ? 'เปิดอยู่' : 'ปิดอยู่'}
      </Text>
    </Pressable>
  );
};

// =====================================================
// Shop header avatar (ใช้ในหน้าร้าน/ตะกร้า)
// =====================================================

export const ShopAvatar: React.FC<{ image: string | null; isMobile: boolean; size?: number; isOpen?: boolean }> = ({
  image,
  isMobile,
  size = 56,
  isOpen,
}) => {
  const { colors } = useTheme();
  const uri = fmImageUri(image);
  const radius = size * 0.32;
  return (
    <View
      style={[
        styles.avatarWrap,
        { width: size, height: size, borderRadius: radius, backgroundColor: colors.goldSoft, borderColor: colors.border },
      ]}
    >
      {uri ? (
        <Image source={{ uri }} style={{ width: size, height: size, borderRadius: radius }} contentFit="cover" transition={120} />
      ) : (
        <BrandArt name={isMobile ? 'cart' : 'store'} size={size * 0.86} />
      )}
      {typeof isOpen === 'boolean' && (
        <View
          style={[
            styles.avatarDot,
            { backgroundColor: isOpen ? colors.success : colors.textFaint, borderColor: colors.card },
          ]}
        />
      )}
    </View>
  );
};

export { OpenPill, ShopStatusRow };

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  flexShrink: {
    flexShrink: 1,
  },
  gapTopSm: {
    marginTop: spacing.sm,
  },
  photoTag: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    height: 24,
    paddingHorizontal: 9,
    borderRadius: 9,
    backgroundColor: 'rgba(10,20,36,0.58)',
  },
  photoDot: {
    width: 7,
    height: 7,
    borderRadius: 4,
  },
  photoTagText: {
    color: '#FFFFFF',
    fontSize: 11.5,
    fontWeight: '600',
  },
  listingImageWrap: {
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    overflow: 'hidden',
  },
  dimImage: {
    opacity: 0.55,
  },
  listingBadges: {
    position: 'absolute',
    top: spacing.sm,
    left: spacing.sm,
    gap: spacing.xs,
  },
  closedTag: {
    position: 'absolute',
    bottom: spacing.sm,
    left: spacing.sm,
  },
  listingBody: {
    paddingHorizontal: spacing.md,
    paddingTop: spacing.sm + 2,
    paddingBottom: spacing.md,
  },
  listingTitle: {
    fontSize: 14.5,
    lineHeight: 20,
    fontWeight: '600',
    minHeight: 40,
  },
  shopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 2,
  },
  dot: {
    width: 7,
    height: 7,
    borderRadius: 4,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    marginTop: spacing.sm,
  },
  priceCol: {
    flex: 1,
  },
  addButton: {
    width: 32,
    height: 32,
    borderRadius: 11,
    alignItems: 'center',
    justifyContent: 'center',
  },
  shopCover: {
    height: 136,
    borderTopLeftRadius: 22,
    borderTopRightRadius: 22,
    overflow: 'hidden',
  },
  coverArt: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  coverScrim: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    height: '55%',
  },
  coverTopLeft: {
    position: 'absolute',
    top: 10,
    left: 10,
  },
  coverTopRight: {
    position: 'absolute',
    top: 10,
    right: 10,
    width: 30,
    height: 30,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(10,20,36,0.5)',
  },
  coverBottom: {
    position: 'absolute',
    left: 12,
    bottom: 10,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  coverBottomText: {
    color: '#FFFFFF',
    fontSize: 12.5,
    fontWeight: '600',
  },
  shopInfo: {
    paddingHorizontal: spacing.md + 2,
    paddingTop: spacing.md,
    paddingBottom: spacing.md + 2,
  },
  nameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 4,
  },
  metaDot: {
    width: 3,
    height: 3,
    borderRadius: 2,
    marginHorizontal: 3,
  },
  topItems: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  topItem: {
    flex: 1,
    gap: 2,
  },
  topItemImg: {
    width: '100%',
    aspectRatio: 1.25,
    borderRadius: radii.sm,
  },
  bubble: {
    width: 76,
    alignItems: 'center',
  },
  bubbleRing: {
    width: 66,
    height: 66,
    borderRadius: 33,
    borderWidth: 2.5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bubbleCircle: {
    width: 56,
    height: 56,
    borderRadius: 28,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  bubbleImg: {
    width: 56,
    height: 56,
  },
  bubbleName: {
    marginTop: 6,
    maxWidth: 76,
    fontWeight: '600',
  },
  avatarWrap: {
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
  avatarDot: {
    position: 'absolute',
    right: -2,
    bottom: -2,
    width: 14,
    height: 14,
    borderRadius: 7,
    borderWidth: 2,
  },
});
