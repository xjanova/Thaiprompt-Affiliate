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
} from 'react-native';
import { router } from 'expo-router';
import Animated, { FadeIn, FadeInRight } from 'react-native-reanimated';
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
        style={{ height }}
        className={`mx-4 rounded-2xl items-center justify-center ${
          isDark ? 'bg-slate-800' : 'bg-gray-100'
        }`}
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
        contentContainerStyle={{ paddingHorizontal: 16 }}
        decelerationRate="fast"
        snapToInterval={SCREEN_WIDTH - 32}
      >
        {banners.map((banner, index) => (
          <Animated.View
            key={banner.id}
            entering={FadeInRight.delay(index * 100)}
            style={{ width: SCREEN_WIDTH - 32 }}
          >
            <Pressable
              onPress={() => handleBannerPress(banner)}
              style={({ pressed }) => ({
                opacity: pressed ? 0.9 : 1,
                transform: [{ scale: pressed ? 0.98 : 1 }],
              })}
            >
              <View
                style={{ height }}
                className="rounded-2xl overflow-hidden mr-2"
              >
                {banner.image ? (
                  <Image
                    source={{ uri: banner.image }}
                    style={{ width: '100%', height: '100%' }}
                    resizeMode="cover"
                  />
                ) : (
                  <View
                    className={`flex-1 items-center justify-center ${
                      isDark ? 'bg-slate-700' : 'bg-gray-200'
                    }`}
                  >
                    <Text
                      className={`text-lg font-bold ${
                        isDark ? 'text-white' : 'text-gray-700'
                      }`}
                    >
                      {banner.title}
                    </Text>
                  </View>
                )}

                {/* Title Overlay */}
                {banner.title && banner.image && (
                  <View className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4">
                    <Text className="text-white font-bold text-base">
                      {banner.title}
                    </Text>
                  </View>
                )}
              </View>
            </Pressable>
          </Animated.View>
        ))}
      </ScrollView>

      {/* Indicators */}
      {showIndicators && banners.length > 1 && (
        <View className="flex-row justify-center mt-3">
          {banners.map((_, index) => (
            <View
              key={index}
              className={`w-2 h-2 rounded-full mx-1 ${
                index === currentIndex
                  ? 'bg-blue-500 w-4'
                  : isDark
                  ? 'bg-slate-600'
                  : 'bg-gray-300'
              }`}
            />
          ))}
        </View>
      )}
    </Animated.View>
  );
}
