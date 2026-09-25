/**
 * การ์ดของหน้าตลาดสด
 *
 * - ListingCard       เมนูในกริด 2 คอลัมน์ (รูป 1:1, ราคา, ร้าน + สถานะเปิด/ปิด)
 * - NearbyShopCard    ร้านที่เปิดอยู่ใกล้คุณ (แนวนอน) + เมนูเด่น 3 อย่าง
 * - FollowedShopBubble ร้านที่ติดตาม (วงกลม + จุดเขียวเมื่อเปิด)
 */

import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { router } from 'expo-router';
import { Card3D, Pill, PriceText, tapHaptic } from '@/components/ui';
import { useTheme, radii, spacing, typography, clayShadowStyle } from '@/theme';
import { formatDistance } from '@/services/location';
import { fmImageUri, type FmFollowedShop, type FmListingSummary, type FmNearbyShop, type FmShopListing } from '@/services/api/taladsodApi';
import { OpenPill, ShopStatusRow } from './ShopBadges';

const FALLBACK_FOOD = require('@/assets/images/taladsod/krapao-hero.webp');

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
  const { colors } = useTheme();
  const summary = 'seller' in listing ? listing : null;
  const open = shopOpen ?? summary?.shop_is_open ?? summary?.seller?.is_open ?? null;
  const closed = open === false;
  const image = fmImageUri(listing.main_image_url);
  const hasDiscount = listing.compare_at_price !== null && listing.compare_at_price > listing.price;
  const hasOptions = listing.has_options === true;

  return (
    <Card3D
      onPress={() => router.push(`/taladsod/listing/${listing.id}` as never)}
      padding={0}
      radius={radii.lg}
      shadow="sm"
      style={{ width }}
      accessibilityLabel={`${listing.title} ราคา ${listing.price} บาท${closed ? ' ร้านปิดอยู่' : ''}`}
    >
      <View style={[styles.listingImageWrap, { height: width, backgroundColor: colors.inset }]}>
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
          <View style={[styles.closedTag, { backgroundColor: colors.overlay }]}>
            <Text style={[typography.micro, { color: colors.textOnAccent }]}>ร้านปิดอยู่</Text>
          </View>
        )}
      </View>
      <View style={styles.listingBody}>
        <Text numberOfLines={2} style={[typography.bodyStrong, styles.listingTitle, { color: colors.textStrong }]}>
          {listing.title}
        </Text>
        <View style={styles.priceRow}>
          <PriceText amount={listing.price} size="md" tone="gold" suffix={listing.unit ? `/${listing.unit}` : undefined} />
          {hasDiscount && <PriceText amount={listing.compare_at_price} size="xs" tone="muted" strike bold={false} />}
        </View>
        {!hideShop && summary?.seller && (
          <View style={styles.shopRow}>
            <View style={[styles.dot, { backgroundColor: summary.seller.is_open ? colors.success : colors.textFaint }]} />
            <Text numberOfLines={1} style={[typography.caption, styles.flex, { color: colors.textMuted }]}>
              {summary.seller.is_mobile ? '🛺 ' : ''}
              {summary.seller.shop_name}
            </Text>
          </View>
        )}
        {summary?.distance_km !== null && summary?.distance_km !== undefined && (
          <Text style={[typography.micro, { color: colors.textFaint }]}>ห่าง {formatDistance(summary.distance_km)}</Text>
        )}
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
  const label = shop.location?.label || shop.presence.location_label;

  return (
    <Card3D
      onPress={() => router.push(`/taladsod/shop/${shop.id}` as never)}
      padding={spacing.md}
      radius={radii.lg}
      shadow="sm"
      gradientBorder={shop.is_following}
      style={{ width }}
      accessibilityLabel={`${shop.shop_name} ${shop.is_open ? 'เปิดอยู่' : 'ปิดอยู่'} ห่าง ${formatDistance(shop.distance_km)}`}
    >
      <View style={styles.shopHead}>
        <View style={[styles.shopAvatar, { backgroundColor: colors.goldSoft }]}>
          {image ? (
            <Image source={{ uri: image }} style={styles.shopAvatarImg} contentFit="cover" transition={120} />
          ) : (
            <Text style={styles.shopAvatarIcon}>{shop.is_mobile ? '🛺' : '🏪'}</Text>
          )}
        </View>
        <View style={styles.flex}>
          <Text numberOfLines={1} style={[typography.h3, { color: colors.textStrong }]}>
            {shop.shop_name}
          </Text>
          <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
            📍 {formatDistance(shop.distance_km)}
            {label ? ` · ${label}` : ''}
          </Text>
        </View>
      </View>

      <ShopStatusRow isOpen={shop.is_open} isMobile={shop.is_mobile} style={styles.gapTopSm} />

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
          styles.bubbleCircle,
          { backgroundColor: colors.card, borderColor: shop.is_open ? colors.success : colors.border },
          clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
        ]}
      >
        {image ? (
          <Image source={{ uri: image }} style={styles.bubbleImg} contentFit="cover" transition={120} />
        ) : (
          <Text style={styles.bubbleIcon}>{shop.is_mobile ? '🛺' : '🏪'}</Text>
        )}
        <View
          style={[
            styles.bubbleDot,
            { backgroundColor: shop.is_open ? colors.success : colors.textFaint, borderColor: colors.card },
          ]}
        />
      </View>
      <Text numberOfLines={1} style={[typography.micro, styles.bubbleName, { color: colors.text }]}>
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
  return (
    <View style={[styles.avatarWrap, { width: size, height: size, borderRadius: size / 2, backgroundColor: colors.goldSoft }]}>
      {uri ? (
        <Image source={{ uri }} style={{ width: size, height: size, borderRadius: size / 2 }} contentFit="cover" transition={120} />
      ) : (
        <Text style={{ fontSize: size * 0.48 }}>{isMobile ? '🛺' : '🏪'}</Text>
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

export { OpenPill };

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  gapTopSm: {
    marginTop: spacing.sm,
  },
  listingImageWrap: {
    borderTopLeftRadius: radii.lg,
    borderTopRightRadius: radii.lg,
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
    borderRadius: radii.pill,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  listingBody: {
    padding: spacing.md,
    gap: 2,
  },
  listingTitle: {
    minHeight: 44,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'baseline',
    gap: spacing.xs,
    flexWrap: 'wrap',
  },
  shopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    marginTop: 2,
  },
  dot: {
    width: 7,
    height: 7,
    borderRadius: 4,
  },
  shopHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  shopAvatar: {
    width: 46,
    height: 46,
    borderRadius: 23,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  shopAvatarImg: {
    width: 46,
    height: 46,
  },
  shopAvatarIcon: {
    fontSize: 22,
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
    aspectRatio: 1,
    borderRadius: radii.sm,
  },
  bubble: {
    width: 76,
    alignItems: 'center',
    gap: 2,
  },
  bubbleCircle: {
    width: 60,
    height: 60,
    borderRadius: 30,
    borderWidth: 2.5,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.xs,
  },
  bubbleImg: {
    width: 54,
    height: 54,
    borderRadius: 27,
  },
  bubbleIcon: {
    fontSize: 26,
  },
  bubbleDot: {
    position: 'absolute',
    right: -1,
    bottom: -1,
    width: 16,
    height: 16,
    borderRadius: 8,
    borderWidth: 2.5,
  },
  bubbleName: {
    textAlign: 'center',
  },
  avatarWrap: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarDot: {
    position: 'absolute',
    right: 0,
    bottom: 0,
    width: 16,
    height: 16,
    borderRadius: 8,
    borderWidth: 2.5,
  },
});
