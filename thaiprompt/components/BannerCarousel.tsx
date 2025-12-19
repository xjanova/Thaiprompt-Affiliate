/**
 * BannerCarousel Component - แสดง Banner โฆษณาจาก Admin
 *
 * Admin สามารถควบคุม:
 * - รูปภาพและหัวข้อ banner
 * - link ปลายทาง (สินค้า, หมวดหมู่, หน้าภายใน, URL ภายนอก)
 * - ลำดับการแสดง
 * - ระยะเวลาแสดง (start/end date)
 *
 * ⭐ Features:
 * - Cache รูปภาพ banner ไว้ใน device
 * - ตรวจสอบ banner ใหม่เฉพาะครั้งแรกของวัน
 * - ใช้รูปจาก cache ถ้ายังไม่มีการเปลี่ยนแปลง
 */

import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  View,
  Text,
  Image,
  Pressable,
  Dimensions,
  ScrollView,
  ActivityIndicator,
  StyleSheet,
  Animated,
} from 'react-native';
import { router } from 'expo-router';
// ⭐ ลบ react-native-reanimated เพราะอาจทำให้ crash
// import Animated, { FadeIn, FadeInRight } from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { trackBannerClick, type Banner } from '@/services/api';
import { getBannersWithCache, type CachedBanner } from '@/services/bannerCache';
import { openUrl } from '@/utils/navigation';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

interface BannerCarouselProps {
  position?: 'top' | 'middle' | 'bottom';
  height?: number;
  autoPlay?: boolean;
  autoPlayInterval?: number;
  showIndicators?: boolean;
  isDark?: boolean;
  onBannerPress?: (banner: Banner) => void;
}

export default function BannerCarousel({
  position = 'top',
  height = 180,
  autoPlay = true,
  autoPlayInterval = 5000,
  showIndicators = true,
  isDark = true,
  onBannerPress,
}: BannerCarouselProps) {
  // ⭐ ใช้ CachedBanner แทน Banner เพื่อรองรับรูปที่ cache ไว้
  const [banners, setBanners] = useState<CachedBanner[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentIndex, setCurrentIndex] = useState(0);
  const scrollViewRef = useRef<ScrollView>(null);
  const autoPlayRef = useRef<NodeJS.Timeout | null>(null);

  // ⭐ ใช้ native Animated แทน reanimated (ต้องประกาศก่อน return)
  const fadeAnim = useRef(new Animated.Value(0)).current;

  // Fade in animation เมื่อโหลด banner เสร็จ
  useEffect(() => {
    if (!loading && banners.length > 0) {
      fadeAnim.setValue(0);
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 300,
        useNativeDriver: true,
      }).start();
    }
  }, [loading, banners.length, fadeAnim]);

  /**
   * โหลด banners โดยใช้ cache system
   * - ครั้งแรกของวัน: ตรวจสอบกับ server และ download รูปใหม่ถ้ามีการเปลี่ยนแปลง
   * - ครั้งต่อไป: ใช้รูปจาก cache ทันที
   */
  const loadBanners = useCallback(async () => {
    try {
      setLoading(true);

      // ⭐ ใช้ getBannersWithCache แทน getBanners
      // จะตรวจสอบ banner ใหม่เฉพาะครั้งแรกของวัน
      const cachedBanners = await getBannersWithCache(position);

      if (Array.isArray(cachedBanners) && cachedBanners.length > 0) {
        // กรองเฉพาะ banners ที่มีข้อมูลครบถ้วน เพื่อป้องกัน crash
        const validBanners = cachedBanners.filter(
          (banner) => banner && banner.id != null
        );
        setBanners(validBanners);
      } else {
        setBanners([]);
      }
    } catch (error) {
      console.error('Load banners error:', error);
      // ⭐ Set empty array เมื่อ error เพื่อป้องกัน crash
      setBanners([]);
    } finally {
      setLoading(false);
    }
  }, [position]);

  useEffect(() => {
    loadBanners();
  }, [loadBanners]);

  // Auto play
  useEffect(() => {
    if (autoPlay && banners.length > 1) {
      autoPlayRef.current = setInterval(() => {
        setCurrentIndex((prev) => {
          const nextIndex = (prev + 1) % banners.length;
          scrollViewRef.current?.scrollTo({
            x: nextIndex * (SCREEN_WIDTH - 32),
            animated: true,
          });
          return nextIndex;
        });
      }, autoPlayInterval);
    }

    return () => {
      if (autoPlayRef.current) {
        clearInterval(autoPlayRef.current);
      }
    };
  }, [autoPlay, autoPlayInterval, banners.length]);

  // Handle scroll
  const handleScroll = (event: { nativeEvent: { contentOffset: { x: number } } }) => {
    const offsetX = event.nativeEvent.contentOffset.x;
    const index = Math.round(offsetX / (SCREEN_WIDTH - 32));
    setCurrentIndex(index);
  };

  // Handle banner press (เพิ่ม error handling)
  const handleBannerPress = async (banner: Banner) => {
    // ⭐ ป้องกัน crash ถ้า banner เป็น null/undefined
    if (!banner) return;

    try {
      // Track click (non-blocking) - ตรวจสอบ id ก่อน
      if (banner.id != null) {
        trackBannerClick(banner.id).catch(() => {});
      }

      // Custom handler
      if (onBannerPress) {
        onBannerPress(banner);
        return;
      }

      // Default navigation
      if (!banner.link) return;

      // ⭐ รายการ routes ที่มีอยู่ในแอพ (ป้องกัน navigation ไปยัง route ที่ไม่มี)
      const validInternalRoutes = [
        '/dashboard', '/shopping', '/register', '/wallet', '/referral',
        '/services', '/academy', '/support', '/tpix', '/stores', '/cart',
        '/orders', '/commissions', '/leaderboard', '/login', '/main-menu',
        '/notifications', '/settings', '/privacy', '/terms', '/rank',
        '/mlm-tree', '/wealth-guide', '/watch-earn', '/wiki', '/kyc',
        '/edit-profile', '/notification-settings', '/wallet-history',
        '/wallet-topup', '/wallet-transfer', '/wallet-withdraw', '/coming-soon',
      ];

      // ตรวจสอบว่าเป็น internal route ที่ถูกต้องหรือไม่
      const isValidInternalRoute = (link: string): boolean => {
        // ถ้าเริ่มต้นด้วย /product/ หรือ /store/ หรือ /order/ ถือว่าถูกต้อง (dynamic routes)
        if (link.startsWith('/product/') || link.startsWith('/store/') || link.startsWith('/order/')) {
          return true;
        }
        return validInternalRoutes.includes(link);
      };

      switch (banner.linkType) {
        case 'product':
          router.push(`/product/${banner.linkTarget}`);
          break;
        case 'category':
          // Navigate to category
          router.push({
            pathname: '/shopping',
            params: { category: banner.linkTarget },
          });
          break;
        case 'internal':
          // ⭐ ตรวจสอบก่อนว่า route มีอยู่หรือไม่
          if (isValidInternalRoute(banner.link)) {
            router.push(banner.link as never);
          } else {
            console.warn('Invalid internal route:', banner.link);
            // ไม่ทำอะไร - อยู่หน้าเดิม
          }
          break;
        case 'external':
          // เปิด URL ใน WebView ภายในแอพ
          try {
            await openUrl(banner.link, banner.title);
          } catch (error) {
            console.error('Cannot open URL:', error);
          }
          break;
        default:
          if (banner.link?.startsWith('http')) {
            await openUrl(banner.link, banner.title);
          } else if (banner.link && isValidInternalRoute(banner.link)) {
            router.push(banner.link as never);
          } else {
            console.warn('Invalid or unsupported link:', banner.link);
            // ไม่ทำอะไร - อยู่หน้าเดิม
          }
      }
    } catch (error) {
      console.error('Banner press error:', error);
    }
  };

  // ถ้ากำลังโหลด
  if (loading) {
    return (
      <View
        style={[
          styles.loadingContainer,
          { height },
          isDark ? styles.loadingContainerDark : styles.loadingContainerLight,
        ]}
      >
        <ActivityIndicator size="small" color={isDark ? '#3B82F6' : '#6B7280'} />
      </View>
    );
  }

  // ถ้าไม่มี banner
  if (banners.length === 0) {
    return null;
  }

  return (
    <Animated.View style={{ opacity: fadeAnim }}>
      <ScrollView
        ref={scrollViewRef}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        onScroll={handleScroll}
        scrollEventThrottle={16}
        contentContainerStyle={styles.scrollContent}
        decelerationRate="fast"
        snapToInterval={SCREEN_WIDTH - 32}
      >
        {banners.map((banner, index) => (
          <View
            key={banner?.id ?? `banner-${index}`}
            style={[styles.bannerWrapper, { width: SCREEN_WIDTH - 32 }]}
          >
            <Pressable
              onPress={() => handleBannerPress(banner)}
              style={({ pressed }) => ({
                opacity: pressed ? 0.9 : 1,
                transform: [{ scale: pressed ? 0.98 : 1 }],
              })}
            >
              <View style={[styles.bannerContainer, { height }]}>
                {/* ⭐ ใช้รูปจาก cache ถ้ามี ไม่งั้นใช้ URL จาก server */}
                {(banner?.cachedImageUri || banner?.image) ? (
                  <Image
                    source={{ uri: banner.cachedImageUri || banner.image }}
                    style={styles.bannerImage}
                    resizeMode="cover"
                  />
                ) : (
                  <View
                    style={[
                      styles.placeholderContainer,
                      isDark ? styles.placeholderDark : styles.placeholderLight,
                    ]}
                  >
                    <Text
                      style={[
                        styles.placeholderText,
                        isDark ? styles.textLight : styles.textDark,
                      ]}
                    >
                      {banner?.title || 'โปรโมชั่น'}
                    </Text>
                  </View>
                )}

                {/* Title Overlay */}
                {banner?.title && banner?.image && (
                  <LinearGradient
                    colors={['transparent', 'rgba(0,0,0,0.7)']}
                    style={styles.titleOverlay}
                  >
                    <Text style={styles.titleText}>{banner.title}</Text>
                  </LinearGradient>
                )}
              </View>
            </Pressable>
          </View>
        ))}
      </ScrollView>

      {/* Indicators */}
      {showIndicators && banners.length > 1 && (
        <View style={styles.indicatorsContainer}>
          {banners.map((_, index) => (
            <View
              key={index}
              style={[
                styles.indicator,
                index === currentIndex
                  ? styles.indicatorActive
                  : isDark
                  ? styles.indicatorInactiveDark
                  : styles.indicatorInactiveLight,
              ]}
            />
          ))}
        </View>
      )}
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  loadingContainer: {
    marginHorizontal: 16,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loadingContainerDark: {
    backgroundColor: '#1E293B',
  },
  loadingContainerLight: {
    backgroundColor: '#F3F4F6',
  },
  scrollContent: {
    paddingHorizontal: 16,
  },
  bannerWrapper: {
    paddingRight: 8,
  },
  bannerContainer: {
    borderRadius: 16,
    overflow: 'hidden',
  },
  bannerImage: {
    width: '100%',
    height: '100%',
  },
  placeholderContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  placeholderDark: {
    backgroundColor: '#334155',
  },
  placeholderLight: {
    backgroundColor: '#E5E7EB',
  },
  placeholderText: {
    fontSize: 18,
    fontWeight: 'bold',
  },
  textLight: {
    color: '#FFFFFF',
  },
  textDark: {
    color: '#374151',
  },
  titleOverlay: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    padding: 16,
  },
  titleText: {
    color: '#FFFFFF',
    fontWeight: 'bold',
    fontSize: 16,
  },
  indicatorsContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginTop: 12,
  },
  indicator: {
    width: 8,
    height: 8,
    borderRadius: 4,
    marginHorizontal: 4,
  },
  indicatorActive: {
    backgroundColor: '#3B82F6',
    width: 16,
  },
  indicatorInactiveDark: {
    backgroundColor: '#475569',
  },
  indicatorInactiveLight: {
    backgroundColor: '#D1D5DB',
  },
});
