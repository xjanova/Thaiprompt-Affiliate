/**
 * BannerCarousel Component - แสดง Banner โฆษณาจาก Admin
 *
 * Admin สามารถควบคุม:
 * - รูปภาพและหัวข้อ banner
 * - link ปลายทาง (สินค้า, หมวดหมู่, หน้าภายใน, URL ภายนอก)
 * - ลำดับการแสดง
 * - ระยะเวลาแสดง (start/end date)
 */

import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  View,
  Text,
  Image,
  Pressable,
  Dimensions,
  ScrollView,
  Linking,
  ActivityIndicator,
  StyleSheet,
} from 'react-native';
import { router } from 'expo-router';
import Animated, { FadeIn, FadeInRight } from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { getBanners, trackBannerClick, type Banner } from '@/services/api';

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
  const [banners, setBanners] = useState<Banner[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentIndex, setCurrentIndex] = useState(0);
  const scrollViewRef = useRef<ScrollView>(null);
  const autoPlayRef = useRef<NodeJS.Timeout | null>(null);

  // โหลด banners จาก server
  const loadBanners = useCallback(async () => {
    try {
      setLoading(true);
      const result = await getBanners(position);
      if (result?.success && result.data) {
        setBanners(result.data);
      }
    } catch (error) {
      console.error('Load banners error:', error);
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

  // Handle banner press
  const handleBannerPress = async (banner: Banner) => {
    // Track click
    await trackBannerClick(banner.id);

    // Custom handler
    if (onBannerPress) {
      onBannerPress(banner);
      return;
    }

    // Default navigation
    if (!banner.link) return;

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
        // Navigate to internal route
        router.push(banner.link as never);
        break;
      case 'external':
        // Open external URL
        try {
          await Linking.openURL(banner.link);
        } catch (error) {
          console.error('Cannot open URL:', error);
        }
        break;
      default:
        if (banner.link.startsWith('http')) {
          await Linking.openURL(banner.link);
        } else {
          router.push(banner.link as never);
        }
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
    <Animated.View entering={FadeIn.delay(100)}>
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
          <Animated.View
            key={banner.id}
            entering={FadeInRight.delay(index * 100)}
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
                {banner.image ? (
                  <Image
                    source={{ uri: banner.image }}
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
                      {banner.title}
                    </Text>
                  </View>
                )}

                {/* Title Overlay */}
                {banner.title && banner.image && (
                  <LinearGradient
                    colors={['transparent', 'rgba(0,0,0,0.7)']}
                    style={styles.titleOverlay}
                  >
                    <Text style={styles.titleText}>{banner.title}</Text>
                  </LinearGradient>
                )}
              </View>
            </Pressable>
          </Animated.View>
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
