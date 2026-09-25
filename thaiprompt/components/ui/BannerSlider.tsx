/**
 * BannerSlider — สไลด์แบนเนอร์แคมเปญตามตำแหน่ง (ดึงจาก bannerApi, สำรองด้วยรูปในแอป)
 *
 * - เลื่อนเองทุก interval ms, หยุดเมื่อผู้ใช้ลากหรือออกจากหน้า
 * - กดแบนเนอร์: cta_type screen → เปิดหน้าในแอป (ผ่าน allowlist) · url → เปิดเว็บของเรา (ล็อกอินให้ถ้าได้)
 *
 * @example
 * <BannerSlider placement="home" />
 * <BannerSlider placement="rider" height={140} autoPlay={false} />
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  FlatList,
  StyleSheet,
  View,
  useWindowDimensions,
  type NativeScrollEvent,
  type NativeSyntheticEvent,
  type StyleProp,
  type ViewStyle,
} from 'react-native';
import { router, useFocusEffect } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import { getBanners, FALLBACK_BANNERS, type AppBanner, type BannerPlacement } from '@/services/api/bannerApi';
import { isAllowedInternalRoute, isTrustedWebUrl, isWebSessionPath } from '@/utils/linking';
import { useTheme, spacing, radii } from '@/theme';
import { BannerCard } from './BannerCard';
import { openWebsite } from './WebsiteButton';

/** เปิดปลายทางของแบนเนอร์อย่างปลอดภัย */
export const openBannerTarget = async (banner: AppBanner): Promise<void> => {
  const value = banner.cta_value;
  if (!value) return;

  if (banner.cta_type === 'screen') {
    if (isAllowedInternalRoute(value)) {
      router.push(value as never);
    }
    return;
  }

  if (banner.cta_type === 'url' && isTrustedWebUrl(value)) {
    const match = /^https:\/\/[^/?#]+(\/[^?#]*)?/i.exec(value);
    const path = match?.[1] || '/';
    if (isWebSessionPath(path)) {
      await openWebsite(path);
      return;
    }
    try {
      await WebBrowser.openBrowserAsync(value);
    } catch {
      // เปิดไม่ได้ก็เงียบไว้ (ไม่มีอะไรเสียหาย)
    }
  }
};

export interface BannerSliderProps {
  placement: BannerPlacement;
  /** ความสูงแบนเนอร์ (ค่าเริ่มต้น 168) */
  height?: number;
  autoPlay?: boolean;
  /** มิลลิวินาที (ค่าเริ่มต้น 5000) */
  interval?: number;
  /** แทนการนำทางเริ่มต้น */
  onBannerPress?: (banner: AppBanner) => unknown;
  /** เพิ่มเมื่อ pull-to-refresh เพื่อบังคับดึงใหม่ */
  refreshKey?: number;
  style?: StyleProp<ViewStyle>;
}

const GAP = spacing.md;

export const BannerSlider: React.FC<BannerSliderProps> = ({
  placement,
  height = 168,
  autoPlay = true,
  interval = 5000,
  onBannerPress,
  refreshKey = 0,
  style,
}) => {
  const { colors } = useTheme();
  const { width: windowWidth } = useWindowDimensions();
  const itemWidth = Math.max(240, windowWidth - spacing.screen * 2);
  const snap = itemWidth + GAP;

  // เริ่มด้วยแบนเนอร์สำรองทันที (ไม่ต้องรอเน็ต) แล้วค่อยแทนด้วยของจริง
  const [banners, setBanners] = useState<AppBanner[]>(FALLBACK_BANNERS[placement]);
  const [index, setIndex] = useState(0);
  const indexRef = useRef(0);
  const listRef = useRef<FlatList<AppBanner>>(null);
  const draggingRef = useRef(false);
  const focusedRef = useRef(true);

  // ---------- โหลดแบนเนอร์ ----------
  useEffect(() => {
    let alive = true;
    getBanners(placement, refreshKey > 0)
      .then((items) => {
        if (alive && items.length > 0) {
          setBanners(items);
          indexRef.current = 0;
          setIndex(0);
          listRef.current?.scrollToOffset({ offset: 0, animated: false });
        }
      })
      .catch(() => {
        // getBanners ไม่ throw อยู่แล้ว — กันไว้อีกชั้น
      });
    return () => {
      alive = false;
    };
  }, [placement, refreshKey]);

  // ---------- หยุดเลื่อนเมื่อออกจากหน้า ----------
  useFocusEffect(
    useCallback(() => {
      focusedRef.current = true;
      return () => {
        focusedRef.current = false;
      };
    }, [])
  );

  // ---------- เลื่อนอัตโนมัติ ----------
  useEffect(() => {
    if (!autoPlay || banners.length < 2) return;
    const timer = setInterval(() => {
      if (draggingRef.current || !focusedRef.current) return;
      const next = (indexRef.current + 1) % banners.length;
      indexRef.current = next;
      listRef.current?.scrollToOffset({ offset: next * snap, animated: true });
      setIndex(next);
    }, Math.max(2500, interval));
    return () => clearInterval(timer);
  }, [autoPlay, banners.length, interval, snap]);

  const onMomentumEnd = (event: NativeSyntheticEvent<NativeScrollEvent>) => {
    draggingRef.current = false;
    const next = Math.max(0, Math.min(banners.length - 1, Math.round(event.nativeEvent.contentOffset.x / snap)));
    indexRef.current = next;
    setIndex(next);
  };

  const handlePress = (banner: AppBanner) => {
    if (onBannerPress) return onBannerPress(banner);
    return openBannerTarget(banner);
  };

  return (
    <View style={style}>
      <FlatList
        ref={listRef}
        data={banners}
        horizontal
        keyExtractor={(item) => String(item.id)}
        showsHorizontalScrollIndicator={false}
        snapToInterval={snap}
        decelerationRate="fast"
        disableIntervalMomentum
        contentContainerStyle={{ paddingHorizontal: spacing.screen, gap: GAP }}
        onScrollBeginDrag={() => {
          draggingRef.current = true;
        }}
        onMomentumScrollEnd={onMomentumEnd}
        getItemLayout={(_, i) => ({ length: snap, offset: snap * i, index: i })}
        renderItem={({ item }) => (
          <View style={{ width: itemWidth, paddingBottom: spacing.sm }}>
            <BannerCard
              image={item.image}
              title={item.title}
              subtitle={item.subtitle}
              ctaLabel={item.cta_label}
              height={height}
              onPress={item.cta_value ? () => handlePress(item) : undefined}
            />
          </View>
        )}
      />

      {banners.length > 1 && (
        <View style={styles.dots} accessibilityElementsHidden importantForAccessibility="no-hide-descendants">
          {banners.map((b, i) => (
            <View
              key={String(b.id)}
              style={[
                styles.dot,
                {
                  width: i === index ? 18 : 6,
                  backgroundColor: i === index ? colors.gold : colors.border,
                },
              ]}
            />
          ))}
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  dots: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    gap: 6,
    marginTop: spacing.xs,
  },
  dot: {
    height: 6,
    borderRadius: radii.pill,
  },
});

export default BannerSlider;
