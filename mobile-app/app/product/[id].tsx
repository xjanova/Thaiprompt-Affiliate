/**
 * Product Detail Screen - หน้ารายละเอียดสินค้า
 * ใช้ Lava Lamp Background Theme สำหรับ Shopping Experience
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  Share,
  ActivityIndicator,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, useLocalSearchParams } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { Image } from 'expo-image';
import * as Haptics from 'expo-haptics';
import * as Clipboard from 'expo-clipboard';
import { LavaBackground, GlassCard, Button } from '@/components';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import { formatCurrency, API_BASE_URL } from '@/constants';
import type { Product } from '@/types';
import axios from 'axios';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

export default function ProductDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { resolvedTheme } = useAppStore();
  const { user, isAuthenticated, token } = useAuthStore();
  const isDark = resolvedTheme === 'dark';

  // State
  const [product, setProduct] = useState<Product | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState('');
  const [quantity, setQuantity] = useState(1);
  const [selectedImageIndex, setSelectedImageIndex] = useState(0);
  const [isAddingToCart, setIsAddingToCart] = useState(false);
  const [isFavorite, setIsFavorite] = useState(false);

  // โหลดข้อมูลสินค้า
  const fetchProduct = useCallback(async () => {
    if (!id) return;

    setIsLoading(true);
    setError('');

    try {
      const response = await axios.get(`${API_BASE_URL}/products/${id}`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      });

      if (response.data.success) {
        setProduct(response.data.data);
      } else {
        setError(response.data.message || 'ไม่พบสินค้า');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
    } finally {
      setIsLoading(false);
    }
  }, [id, token]);

  useEffect(() => {
    fetchProduct();
  }, [fetchProduct]);

  // เพิ่มลงตะกร้า
  const addToCart = async () => {
    if (!isAuthenticated) {
      Alert.alert(
        'กรุณาเข้าสู่ระบบ',
        'คุณต้องเข้าสู่ระบบก่อนเพิ่มสินค้าลงตะกร้า',
        [
          { text: 'ยกเลิก', style: 'cancel' },
          { text: 'เข้าสู่ระบบ', onPress: () => router.push('/login') },
        ]
      );
      return;
    }

    if (!product) return;

    setIsAddingToCart(true);

    try {
      const response = await axios.post(
        `${API_BASE_URL}/cart/add`,
        {
          product_id: product.id,
          quantity,
        },
        {
          headers: { Authorization: `Bearer ${token}` },
        }
      );

      if (response.data.success) {
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
        Alert.alert('สำเร็จ!', `เพิ่ม ${product.name} ลงตะกร้าแล้ว`, [
          { text: 'ดูตะกร้า', onPress: () => router.push('/cart') },
          { text: 'ช้อปต่อ', style: 'cancel' },
        ]);
      } else {
        Alert.alert('เกิดข้อผิดพลาด', response.data.message);
      }
    } catch (err: any) {
      Alert.alert('เกิดข้อผิดพลาด', err.response?.data?.message || 'ไม่สามารถเพิ่มสินค้าได้');
    } finally {
      setIsAddingToCart(false);
    }
  };

  // แชร์สินค้า
  const shareProduct = async () => {
    if (!product) return;

    try {
      const referralCode = user?.referral_code || '';
      const shareUrl = `https://shop.thaiprompt.com/product/${product.id}${
        referralCode ? `?ref=${referralCode}` : ''
      }`;

      await Share.share({
        title: product.name,
        message: `${product.name}\n\nราคา: ${formatCurrency(product.price)}\n\nซื้อได้ที่: ${shareUrl}`,
        url: shareUrl,
      });
    } catch (error) {
      console.error('Share error:', error);
    }
  };

  // คัดลอกลิงก์
  const copyLink = async () => {
    if (!product) return;

    const referralCode = user?.referral_code || '';
    const shareUrl = `https://shop.thaiprompt.com/product/${product.id}${
      referralCode ? `?ref=${referralCode}` : ''
    }`;

    await Clipboard.setStringAsync(shareUrl);
    Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    Alert.alert('คัดลอกแล้ว!', 'ลิงก์สินค้าถูกคัดลอกไปยังคลิปบอร์ด');
  };

  // Toggle favorite
  const toggleFavorite = () => {
    setIsFavorite(!isFavorite);
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
  };

  // ปุ่มกลับ
  const goBack = () => router.back();

  // Mock images (เมื่อไม่มี images จาก API)
  const productImages = product?.images?.length
    ? product.images
    : product
    ? [`https://picsum.photos/seed/${product.id}/600/600`]
    : [];

  // คำนวณคอมมิชชั่นที่ได้
  const commissionRate = 0.05; // 5%
  const estimatedCommission = product ? product.price * commissionRate : 0;

  if (isLoading) {
    return (
      <LavaBackground variant="shopping" intensity="low">
        <SafeAreaView className="flex-1 items-center justify-center">
          <ActivityIndicator size="large" color="#FF6B35" />
          <Text className={`mt-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
            กำลังโหลดข้อมูลสินค้า...
          </Text>
        </SafeAreaView>
      </LavaBackground>
    );
  }

  if (error || !product) {
    return (
      <LavaBackground variant="shopping" intensity="low">
        <SafeAreaView className="flex-1 items-center justify-center px-6">
          <Ionicons name="alert-circle" size={64} color="#EF4444" />
          <Text className={`text-xl font-bold mt-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
            {error || 'ไม่พบสินค้า'}
          </Text>
          <Button title="กลับ" onPress={goBack} variant="secondary" style={{ marginTop: 16 }} />
        </SafeAreaView>
      </LavaBackground>
    );
  }

  return (
    <LavaBackground variant="shopping" intensity="low">
      <SafeAreaView className="flex-1" edges={['top']}>
        {/* Header */}
        <View className="flex-row items-center justify-between px-4 py-2">
          <Pressable
            onPress={goBack}
            className="w-10 h-10 rounded-full bg-black/20 items-center justify-center"
          >
            <Ionicons name="arrow-back" size={24} color="white" />
          </Pressable>

          <View className="flex-row gap-2">
            <Pressable
              onPress={toggleFavorite}
              className="w-10 h-10 rounded-full bg-black/20 items-center justify-center"
            >
              <Ionicons
                name={isFavorite ? 'heart' : 'heart-outline'}
                size={24}
                color={isFavorite ? '#EF4444' : 'white'}
              />
            </Pressable>
            <Pressable
              onPress={shareProduct}
              className="w-10 h-10 rounded-full bg-black/20 items-center justify-center"
            >
              <Ionicons name="share-outline" size={24} color="white" />
            </Pressable>
          </View>
        </View>

        <ScrollView className="flex-1" showsVerticalScrollIndicator={false}>
          {/* Product Images */}
          <View className="bg-white/5 rounded-b-3xl overflow-hidden">
            <Image
              source={{ uri: productImages[selectedImageIndex] }}
              style={{ width: SCREEN_WIDTH, height: SCREEN_WIDTH }}
              contentFit="cover"
              transition={300}
            />

            {/* Image Indicators */}
            {productImages.length > 1 && (
              <View className="flex-row justify-center gap-2 py-4">
                {productImages.map((_, index) => (
                  <Pressable
                    key={index}
                    onPress={() => setSelectedImageIndex(index)}
                    className={`w-2 h-2 rounded-full ${
                      index === selectedImageIndex ? 'bg-primary-500 w-6' : 'bg-white/30'
                    }`}
                  />
                ))}
              </View>
            )}
          </View>

          {/* Product Info */}
          <View className="px-4 pt-6 pb-32">
            {/* Price & Name */}
            <GlassCard style={{ marginBottom: 16 }}>
              {/* Category Badge */}
              {product.category && (
                <View className="flex-row mb-2">
                  <View className="bg-primary-500/20 rounded-full px-3 py-1">
                    <Text className="text-primary-400 text-sm font-medium">
                      {product.category}
                    </Text>
                  </View>
                </View>
              )}

              {/* Name */}
              <Text className={`text-xl font-bold mb-2 ${isDark ? 'text-white' : 'text-gray-900'}`}>
                {product.name}
              </Text>

              {/* Price */}
              <View className="flex-row items-baseline mb-4">
                <Text className="text-3xl font-bold text-primary-500">
                  {formatCurrency(product.price)}
                </Text>
                {product.original_price && product.original_price > product.price && (
                  <>
                    <Text
                      className={`text-lg ml-3 line-through ${
                        isDark ? 'text-gray-500' : 'text-gray-400'
                      }`}
                    >
                      {formatCurrency(product.original_price)}
                    </Text>
                    <View className="bg-red-500 rounded px-2 py-1 ml-2">
                      <Text className="text-white text-sm font-bold">
                        -{Math.round(((product.original_price - product.price) / product.original_price) * 100)}%
                      </Text>
                    </View>
                  </>
                )}
              </View>

              {/* Commission Info */}
              {isAuthenticated && (
                <View className="bg-green-500/10 rounded-xl p-3 border border-green-500/30">
                  <View className="flex-row items-center">
                    <Ionicons name="cash-outline" size={20} color="#10B981" />
                    <Text className="text-green-400 ml-2">
                      คอมมิชชั่นโดยประมาณ:{' '}
                      <Text className="font-bold">{formatCurrency(estimatedCommission)}</Text>
                    </Text>
                  </View>
                </View>
              )}
            </GlassCard>

            {/* Quantity Selector */}
            <GlassCard style={{ marginBottom: 16 }}>
              <Text className={`font-medium mb-3 ${isDark ? 'text-white' : 'text-gray-900'}`}>
                จำนวน
              </Text>
              <View className="flex-row items-center justify-between">
                <View className="flex-row items-center bg-white/10 rounded-xl">
                  <Pressable
                    onPress={() => quantity > 1 && setQuantity(quantity - 1)}
                    className="w-12 h-12 items-center justify-center"
                    disabled={quantity <= 1}
                  >
                    <Ionicons
                      name="remove"
                      size={24}
                      color={quantity <= 1 ? '#6B7280' : isDark ? '#fff' : '#374151'}
                    />
                  </Pressable>
                  <Text
                    className={`text-xl font-bold w-12 text-center ${
                      isDark ? 'text-white' : 'text-gray-900'
                    }`}
                  >
                    {quantity}
                  </Text>
                  <Pressable
                    onPress={() => setQuantity(quantity + 1)}
                    className="w-12 h-12 items-center justify-center"
                  >
                    <Ionicons name="add" size={24} color={isDark ? '#fff' : '#374151'} />
                  </Pressable>
                </View>

                <View>
                  <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                    ราคารวม
                  </Text>
                  <Text className="text-xl font-bold text-primary-500">
                    {formatCurrency(product.price * quantity)}
                  </Text>
                </View>
              </View>
            </GlassCard>

            {/* Description */}
            <GlassCard style={{ marginBottom: 16 }}>
              <Text className={`font-medium mb-3 ${isDark ? 'text-white' : 'text-gray-900'}`}>
                รายละเอียดสินค้า
              </Text>
              <Text className={isDark ? 'text-gray-300' : 'text-gray-600'}>
                {product.description || 'ไม่มีรายละเอียดสินค้า'}
              </Text>
            </GlassCard>

            {/* Share & Referral */}
            <GlassCard>
              <Text className={`font-medium mb-3 ${isDark ? 'text-white' : 'text-gray-900'}`}>
                แชร์สินค้านี้และรับค่าคอมมิชชั่น!
              </Text>
              <View className="flex-row gap-3">
                <Pressable
                  onPress={shareProduct}
                  className="flex-1 flex-row items-center justify-center bg-blue-500/20 rounded-xl py-3"
                >
                  <Ionicons name="share-social" size={20} color="#3B82F6" />
                  <Text className="text-blue-400 font-medium ml-2">แชร์</Text>
                </Pressable>
                <Pressable
                  onPress={copyLink}
                  className="flex-1 flex-row items-center justify-center bg-purple-500/20 rounded-xl py-3"
                >
                  <Ionicons name="link" size={20} color="#8B5CF6" />
                  <Text className="text-purple-400 font-medium ml-2">คัดลอกลิงก์</Text>
                </Pressable>
              </View>
            </GlassCard>
          </View>
        </ScrollView>

        {/* Bottom Action Bar */}
        <View
          className={`absolute bottom-0 left-0 right-0 px-4 pb-8 pt-4 ${
            isDark ? 'bg-dark-800/95' : 'bg-white/95'
          }`}
          style={{
            shadowColor: '#000',
            shadowOffset: { width: 0, height: -4 },
            shadowOpacity: 0.1,
            shadowRadius: 12,
            elevation: 10,
          }}
        >
          <View className="flex-row gap-3">
            {/* Chat Button */}
            <Pressable
              onPress={() => Alert.alert('แชท', 'ระบบแชทกำลังพัฒนา')}
              className="w-14 h-14 rounded-xl bg-white/10 items-center justify-center border border-gray-300/30"
            >
              <Ionicons name="chatbubble-outline" size={24} color={isDark ? '#fff' : '#374151'} />
            </Pressable>

            {/* Add to Cart Button */}
            <Pressable
              onPress={() => router.push('/cart')}
              className="w-14 h-14 rounded-xl bg-white/10 items-center justify-center border border-gray-300/30"
            >
              <Ionicons name="cart-outline" size={24} color={isDark ? '#fff' : '#374151'} />
            </Pressable>

            {/* Buy Now Button */}
            <View className="flex-1">
              <Button
                title={isAddingToCart ? 'กำลังเพิ่ม...' : 'เพิ่มลงตะกร้า'}
                onPress={addToCart}
                loading={isAddingToCart}
                disabled={isAddingToCart}
                fullWidth
                size="lg"
                variant="primary"
                style={{
                  backgroundColor: '#FF6B35',
                  shadowColor: '#FF6B35',
                  shadowOpacity: 0.4,
                  shadowRadius: 12,
                }}
              />
            </View>
          </View>
        </View>
      </SafeAreaView>
    </LavaBackground>
  );
}
