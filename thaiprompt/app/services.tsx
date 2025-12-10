/**
 * Services Screen - หน้าสั่งบริการสำหรับลูกค้า
 * Premium UI Design - สวยหรู ระดับ Million Dollar App
 */

import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  TextInput,
  Dimensions,
  FlatList,
  ActivityIndicator,
  Alert,
  Modal,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';
import { router } from 'expo-router';
import Animated, {
  FadeInDown,
  FadeInUp,
  FadeIn,
  ZoomIn,
  SlideInRight,
  useSharedValue,
  useAnimatedStyle,
  withSpring,
  withTiming,
  withRepeat,
  withSequence,
  interpolate,
  Extrapolation,
} from 'react-native-reanimated';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import { ErrorState } from '@/components/ErrorState';
import {
  SERVICE_CATEGORIES,
  SERVICE_GROUPS,
  FEATURED_SERVICES,
  ServiceCategory,
  ServiceSlug,
  formatServicePrice,
  getServiceBySlug,
} from '@/constants/services';

const { width: SCREEN_WIDTH } = Dimensions.get('window');
const CARD_WIDTH = SCREEN_WIDTH * 0.42;

// =====================================================
// Premium Featured Card - 3D Effect
// =====================================================

const FeaturedServiceCard = ({
  service,
  index,
  onPress,
}: {
  service: ServiceCategory;
  index: number;
  onPress: () => void;
}) => {
  const scale = useSharedValue(1);
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [{ scale: scale.value }],
  }));

  const handlePressIn = () => {
    scale.value = withSpring(0.95);
  };

  const handlePressOut = () => {
    scale.value = withSpring(1);
  };

  return (
    <Animated.View
      entering={SlideInRight.delay(100 + index * 80).springify()}
      style={animatedStyle}
    >
      <Pressable
        onPress={onPress}
        onPressIn={handlePressIn}
        onPressOut={handlePressOut}
        className="mr-4"
      >
        <LinearGradient
          colors={service.gradient}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={{
            borderRadius: 24,
            padding: 20,
            overflow: 'hidden',
            width: CARD_WIDTH,
            height: CARD_WIDTH * 1.2,
            shadowColor: service.color,
            shadowOffset: { width: 0, height: 8 },
            shadowOpacity: 0.4,
            shadowRadius: 16,
            elevation: 8,
          }}
        >
          {/* Decorative Elements */}
          <View
            className="absolute -right-8 -top-8 w-32 h-32 rounded-full opacity-20"
            style={{ backgroundColor: 'white' }}
          />
          <View
            className="absolute -left-4 -bottom-4 w-24 h-24 rounded-full opacity-10"
            style={{ backgroundColor: 'white' }}
          />

          {/* Icon */}
          <View className="w-16 h-16 bg-white/20 rounded-2xl items-center justify-center mb-4">
            <Text style={{ fontSize: 32 }}>{service.icon}</Text>
          </View>

          {/* Content */}
          <Text className="text-white font-bold text-lg mb-1">
            {service.name}
          </Text>
          <Text className="text-white/80 text-xs mb-3" numberOfLines={2}>
            {service.description}
          </Text>

          {/* Price Tag */}
          <View className="flex-row items-center mt-auto">
            <View className="bg-white/25 rounded-full px-3 py-1.5">
              <Text className="text-white font-bold text-sm">
                {formatServicePrice(service)}
              </Text>
            </View>
          </View>

          {/* Arrow */}
          <View className="absolute bottom-4 right-4 w-8 h-8 bg-white/20 rounded-full items-center justify-center">
            <Text style={{ fontSize: 16 }}>→</Text>
          </View>
        </LinearGradient>
      </Pressable>
    </Animated.View>
  );
};

// =====================================================
// Category Pill Component
// =====================================================

const CategoryPill = ({
  title,
  icon,
  isActive,
  onPress,
  index,
}: {
  title: string;
  icon: string;
  isActive: boolean;
  onPress: () => void;
  index: number;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  return (
    <Animated.View entering={FadeInDown.delay(50 + index * 30).springify()}>
      <Pressable
        onPress={onPress}
        className={`mr-3 px-4 py-3 rounded-2xl flex-row items-center ${
          isActive
            ? 'bg-primary-500'
            : isDark
            ? 'bg-gray-800'
            : 'bg-white'
        }`}
        style={
          !isActive
            ? {
                shadowColor: '#000',
                shadowOffset: { width: 0, height: 2 },
                shadowOpacity: 0.05,
                shadowRadius: 8,
                elevation: 2,
              }
            : undefined
        }
      >
        <Text style={{ fontSize: 18 }}>{icon}</Text>
        <Text
          className={`ml-2 font-medium ${
            isActive
              ? 'text-white'
              : isDark
              ? 'text-gray-300'
              : 'text-gray-700'
          }`}
        >
          {title}
        </Text>
      </Pressable>
    </Animated.View>
  );
};

// =====================================================
// Service Grid Card - Premium Design
// =====================================================

const ServiceGridCard = ({
  service,
  index,
  onPress,
}: {
  service: ServiceCategory;
  index: number;
  onPress: () => void;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';
  const scale = useSharedValue(1);

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [{ scale: scale.value }],
  }));

  return (
    <Animated.View
      entering={FadeInDown.delay(100 + index * 50).springify()}
      style={[animatedStyle, { width: '48%', marginBottom: 16 }]}
    >
      <Pressable
        onPress={onPress}
        onPressIn={() => (scale.value = withSpring(0.95))}
        onPressOut={() => (scale.value = withSpring(1))}
        className={`rounded-3xl p-4 ${isDark ? 'bg-gray-800' : 'bg-white'}`}
        style={{
          shadowColor: '#000',
          shadowOffset: { width: 0, height: 4 },
          shadowOpacity: 0.08,
          shadowRadius: 12,
          elevation: 4,
        }}
      >
        {/* Icon with Gradient */}
        <LinearGradient
          colors={service.gradient}
          style={{
            width: 56,
            height: 56,
            borderRadius: 16,
            alignItems: 'center',
            justifyContent: 'center',
            marginBottom: 12,
            shadowColor: service.color,
            shadowOffset: { width: 0, height: 4 },
            shadowOpacity: 0.3,
            shadowRadius: 8,
          }}
        >
          <Text style={{ fontSize: 28 }}>{service.icon}</Text>
        </LinearGradient>

        {/* Title */}
        <Text
          className={`font-bold text-base mb-1 ${
            isDark ? 'text-white' : 'text-gray-900'
          }`}
          numberOfLines={1}
        >
          {service.name}
        </Text>

        {/* Description */}
        <Text
          className={`text-xs mb-2 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}
          numberOfLines={2}
        >
          {service.description}
        </Text>

        {/* Price */}
        <View className="flex-row items-center justify-between mt-auto">
          <Text
            className="font-bold text-sm"
            style={{ color: service.color }}
          >
            {formatServicePrice(service)}
          </Text>
          <View
            className="w-7 h-7 rounded-full items-center justify-center"
            style={{ backgroundColor: service.color + '20' }}
          >
            <Text style={{ fontSize: 18, color: service.color }}>+</Text>
          </View>
        </View>
      </Pressable>
    </Animated.View>
  );
};

// =====================================================
// Service Detail Modal
// =====================================================

const ServiceDetailModal = ({
  visible,
  service,
  onClose,
  onOrder,
}: {
  visible: boolean;
  service: ServiceCategory | null;
  onClose: () => void;
  onOrder: () => void;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  if (!service) return null;

  return (
    <Modal visible={visible} transparent animationType="fade">
      <BlurView
        intensity={isDark ? 40 : 80}
        tint={isDark ? 'dark' : 'light'}
        style={{ flex: 1 }}
      >
        <Pressable
          className="flex-1 justify-end"
          onPress={onClose}
        >
          <Pressable onPress={(e) => e.stopPropagation()}>
            <Animated.View
              entering={FadeInUp.springify()}
              className={`rounded-t-[32px] ${
                isDark ? 'bg-gray-900' : 'bg-white'
              }`}
              style={{
                shadowColor: '#000',
                shadowOffset: { width: 0, height: -8 },
                shadowOpacity: 0.15,
                shadowRadius: 24,
                elevation: 16,
              }}
            >
              {/* Handle */}
              <View className="items-center pt-3 pb-2">
                <View
                  className={`w-10 h-1 rounded-full ${
                    isDark ? 'bg-gray-700' : 'bg-gray-300'
                  }`}
                />
              </View>

              {/* Header with Gradient */}
              <LinearGradient
                colors={service.gradient}
                style={{
                  marginHorizontal: 20,
                  borderRadius: 24,
                  padding: 24,
                  marginBottom: 24,
                  shadowColor: service.color,
                  shadowOffset: { width: 0, height: 8 },
                  shadowOpacity: 0.4,
                  shadowRadius: 16,
                }}
              >
                {/* Decorative */}
                <View
                  className="absolute -right-10 -top-10 w-40 h-40 rounded-full opacity-15"
                  style={{ backgroundColor: 'white' }}
                />

                <View className="flex-row items-center">
                  <View className="w-20 h-20 bg-white/20 rounded-3xl items-center justify-center mr-4">
                    <Text style={{ fontSize: 40 }}>{service.icon}</Text>
                  </View>
                  <View className="flex-1">
                    <Text className="text-white/80 text-sm">บริการ</Text>
                    <Text className="text-white font-bold text-2xl">
                      {service.name}
                    </Text>
                    <View className="flex-row items-center mt-1">
                      <View className="flex-row items-center">
                        <Text style={{ fontSize: 14 }}>⭐</Text>
                        <Text className="text-white/90 text-sm ml-1">
                          4.9 (2.3k รีวิว)
                        </Text>
                      </View>
                    </View>
                  </View>
                </View>
              </LinearGradient>

              {/* Content */}
              <View className="px-5 pb-8">
                {/* Description */}
                <View className="mb-6">
                  <Text
                    className={`font-bold text-lg mb-2 ${
                      isDark ? 'text-white' : 'text-gray-900'
                    }`}
                  >
                    รายละเอียดบริการ
                  </Text>
                  <Text
                    className={`leading-6 ${
                      isDark ? 'text-gray-400' : 'text-gray-600'
                    }`}
                  >
                    {service.description}
                    {'\n\n'}
                    ผู้ให้บริการมืออาชีพ ผ่านการตรวจสอบประวัติ
                    มีประกันความเสียหาย รับประกันความพึงพอใจ
                  </Text>
                </View>

                {/* Price Info */}
                <View
                  className={`rounded-2xl p-4 mb-6 ${
                    isDark ? 'bg-gray-800' : 'bg-gray-50'
                  }`}
                >
                  <View className="flex-row items-center justify-between">
                    <View>
                      <Text
                        className={`text-sm ${
                          isDark ? 'text-gray-400' : 'text-gray-500'
                        }`}
                      >
                        ราคาเริ่มต้น
                      </Text>
                      <Text
                        className="text-2xl font-bold"
                        style={{ color: service.color }}
                      >
                        ฿{service.basePrice?.toLocaleString()}
                      </Text>
                    </View>
                    <View
                      className={`px-4 py-2 rounded-xl ${
                        isDark ? 'bg-gray-700' : 'bg-white'
                      }`}
                    >
                      <Text
                        className={`font-medium ${
                          isDark ? 'text-gray-300' : 'text-gray-700'
                        }`}
                      >
                        ต่อ{service.priceUnit}
                      </Text>
                    </View>
                  </View>
                </View>

                {/* Features */}
                <View className="flex-row flex-wrap mb-6">
                  {['มืออาชีพ', 'รวดเร็ว', 'ประกัน', 'รีวิวดี'].map((tag, i) => (
                    <View
                      key={i}
                      className="flex-row items-center mr-3 mb-2 px-3 py-1.5 rounded-full"
                      style={{ backgroundColor: service.color + '15' }}
                    >
                      <Text style={{ fontSize: 14, color: service.color }}>✓</Text>
                      <Text
                        className="text-sm font-medium ml-1"
                        style={{ color: service.color }}
                      >
                        {tag}
                      </Text>
                    </View>
                  ))}
                </View>

                {/* Order Button */}
                <Pressable onPress={onOrder} className="rounded-2xl overflow-hidden">
                  <LinearGradient
                    colors={service.gradient}
                    style={{
                      paddingVertical: 16,
                      flexDirection: 'row',
                      alignItems: 'center',
                      justifyContent: 'center',
                    }}
                  >
                    <Text style={{ fontSize: 22 }}>📅</Text>
                    <Text className="text-white font-bold text-lg ml-2">
                      จองบริการ
                    </Text>
                  </LinearGradient>
                </Pressable>
              </View>
            </Animated.View>
          </Pressable>
        </Pressable>
      </BlurView>
    </Modal>
  );
};

// =====================================================
// Main Component
// =====================================================

export default function ServicesScreen() {
  const { resolvedTheme } = useAppStore();
  const { isAuthenticated } = useAuthStore();
  const isDark = resolvedTheme === 'dark';

  const [isLoading, setIsLoading] = useState(true);
  const [hasError, setHasError] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeCategory, setActiveCategory] = useState<string | null>(null);
  const [selectedService, setSelectedService] = useState<ServiceCategory | null>(
    null
  );
  const [showDetailModal, setShowDetailModal] = useState(false);

  // Load data
  useEffect(() => {
    const load = async () => {
      try {
        setHasError(false);
        await new Promise((resolve) => setTimeout(resolve, 300));
      } catch (error) {
        setHasError(true);
      } finally {
        setIsLoading(false);
      }
    };
    load();
  }, []);

  const handleServicePress = (service: ServiceCategory) => {
    setSelectedService(service);
    setShowDetailModal(true);
  };

  const handleOrder = () => {
    if (!isAuthenticated) {
      Alert.alert('กรุณาเข้าสู่ระบบ', 'เข้าสู่ระบบเพื่อจองบริการ', [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: 'เข้าสู่ระบบ', onPress: () => router.push('/login') },
      ]);
      return;
    }

    setShowDetailModal(false);
    // TODO: Navigate to booking flow
    Alert.alert(
      'จองบริการ',
      `กำลังพัฒนาระบบจอง "${selectedService?.name}"`,
      [{ text: 'ตกลง' }]
    );
  };

  // Filter services
  const filteredServices = SERVICE_CATEGORIES.filter((s) => {
    const matchesSearch =
      searchQuery === '' ||
      s.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      s.description.toLowerCase().includes(searchQuery.toLowerCase());

    const matchesCategory =
      activeCategory === null || s.categoryType === activeCategory;

    return matchesSearch && matchesCategory;
  });

  if (isLoading) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center">
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text className="text-gray-500 mt-4">กำลังโหลด...</Text>
        </SafeAreaView>
      </View>
    );
  }

  if (hasError) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1">
          <ErrorState
            title="ไม่สามารถโหลดข้อมูลได้"
            message="ขณะนี้ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้\nโปรดลองใหม่ภายหลัง"
            onRetry={() => {
              setIsLoading(true);
              setHasError(false);
            }}
          />
        </SafeAreaView>
      </View>
    );
  }

  return (
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
      <SafeAreaView className="flex-1">
        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={{ paddingBottom: 100 }}
        >
          {/* Header */}
          <Animated.View
            entering={FadeInDown.delay(50).springify()}
            className="px-5 pt-4 pb-2"
          >
            <View className="flex-row items-center justify-between mb-4">
              <View>
                <Text
                  className={`text-3xl font-bold ${
                    isDark ? 'text-white' : 'text-gray-900'
                  }`}
                >
                  บริการ
                </Text>
                <Text className="text-gray-500 dark:text-gray-400">
                  เลือกบริการที่คุณต้องการ
                </Text>
              </View>
              <Pressable
                className={`w-12 h-12 rounded-2xl items-center justify-center ${
                  isDark ? 'bg-gray-800' : 'bg-white'
                }`}
                style={{
                  shadowColor: '#000',
                  shadowOffset: { width: 0, height: 2 },
                  shadowOpacity: 0.08,
                  shadowRadius: 8,
                  elevation: 4,
                }}
              >
                <Text style={{ fontSize: 24 }}>🔔</Text>
              </Pressable>
            </View>

            {/* Search Bar */}
            <View
              className={`flex-row items-center rounded-2xl px-4 py-4 ${
                isDark ? 'bg-gray-800' : 'bg-white'
              }`}
              style={{
                shadowColor: '#000',
                shadowOffset: { width: 0, height: 4 },
                shadowOpacity: 0.08,
                shadowRadius: 12,
                elevation: 4,
              }}
            >
              <Text style={{ fontSize: 22 }}>🔍</Text>
              <TextInput
                value={searchQuery}
                onChangeText={setSearchQuery}
                placeholder="ค้นหาบริการ เช่น ช่างไฟ, นวด..."
                placeholderTextColor={isDark ? '#6B7280' : '#9CA3AF'}
                className={`flex-1 ml-3 text-base ${
                  isDark ? 'text-white' : 'text-gray-900'
                }`}
              />
              {searchQuery.length > 0 && (
                <Pressable onPress={() => setSearchQuery('')}>
                  <Text style={{ fontSize: 22, color: isDark ? '#6B7280' : '#9CA3AF' }}>✕</Text>
                </Pressable>
              )}
            </View>
          </Animated.View>

          {/* Featured Services - Horizontal Scroll */}
          <Animated.View entering={FadeInDown.delay(100).springify()}>
            <View className="px-5 mt-6 mb-4 flex-row items-center justify-between">
              <Text
                className={`text-xl font-bold ${
                  isDark ? 'text-white' : 'text-gray-900'
                }`}
              >
                ✨ บริการยอดนิยม
              </Text>
              <Pressable className="flex-row items-center">
                <Text className="text-primary-500 font-medium mr-1">ดูทั้งหมด</Text>
                <Text style={{ fontSize: 16, color: '#3B82F6' }}>›</Text>
              </Pressable>
            </View>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={{ paddingLeft: 20, paddingRight: 8 }}
            >
              {FEATURED_SERVICES.map((service, index) => (
                <FeaturedServiceCard
                  key={service.slug}
                  service={service}
                  index={index}
                  onPress={() => handleServicePress(service)}
                />
              ))}
            </ScrollView>
          </Animated.View>

          {/* Categories */}
          <Animated.View entering={FadeInDown.delay(150).springify()}>
            <Text
              className={`px-5 mt-8 mb-4 text-xl font-bold ${
                isDark ? 'text-white' : 'text-gray-900'
              }`}
            >
              หมวดหมู่
            </Text>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={{ paddingLeft: 20, paddingRight: 8 }}
            >
              <CategoryPill
                title="ทั้งหมด"
                icon="apps"
                isActive={activeCategory === null}
                onPress={() => setActiveCategory(null)}
                index={0}
              />
              {SERVICE_GROUPS.map((group, index) => (
                <CategoryPill
                  key={group.type}
                  title={group.title}
                  icon={group.icon}
                  isActive={activeCategory === group.type}
                  onPress={() => setActiveCategory(group.type)}
                  index={index + 1}
                />
              ))}
            </ScrollView>
          </Animated.View>

          {/* All Services Grid */}
          <View className="px-5 mt-8">
            <Text
              className={`text-xl font-bold mb-4 ${
                isDark ? 'text-white' : 'text-gray-900'
              }`}
            >
              {activeCategory
                ? SERVICE_GROUPS.find((g) => g.type === activeCategory)?.title
                : 'บริการทั้งหมด'}
              <Text className="text-gray-400 font-normal text-base">
                {' '}
                ({filteredServices.length})
              </Text>
            </Text>

            <View className="flex-row flex-wrap justify-between">
              {filteredServices.map((service, index) => (
                <ServiceGridCard
                  key={service.slug}
                  service={service}
                  index={index}
                  onPress={() => handleServicePress(service)}
                />
              ))}
            </View>

            {filteredServices.length === 0 && (
              <View className="items-center py-12">
                <Text style={{ fontSize: 48 }}>🔍</Text>
                <Text
                  className={`mt-4 font-medium ${
                    isDark ? 'text-gray-400' : 'text-gray-500'
                  }`}
                >
                  ไม่พบบริการที่ค้นหา
                </Text>
              </View>
            )}
          </View>

          {/* Bottom CTA */}
          <Animated.View
            entering={FadeInUp.delay(300).springify()}
            className="mx-5 mt-8"
          >
            <LinearGradient
              colors={isDark ? ['#1F2937', '#111827'] : ['#F0F9FF', '#E0F2FE']}
              style={{
                borderRadius: 24,
                padding: 24,
              }}
            >
              <View className="flex-row items-center">
                <View className="w-16 h-16 bg-primary-500/20 rounded-2xl items-center justify-center mr-4">
                  <Text style={{ fontSize: 32 }}>👥</Text>
                </View>
                <View className="flex-1">
                  <Text
                    className={`font-bold text-lg mb-1 ${
                      isDark ? 'text-white' : 'text-gray-900'
                    }`}
                  >
                    เป็นผู้ให้บริการ
                  </Text>
                  <Text
                    className={`text-sm ${
                      isDark ? 'text-gray-400' : 'text-gray-600'
                    }`}
                  >
                    รับงานและสร้างรายได้กับเรา
                  </Text>
                </View>
                <Pressable
                  onPress={() => router.push('/rider')}
                  className="bg-primary-500 px-4 py-2.5 rounded-xl"
                >
                  <Text className="text-white font-bold">สมัคร</Text>
                </Pressable>
              </View>
            </LinearGradient>
          </Animated.View>
        </ScrollView>
      </SafeAreaView>

      {/* Service Detail Modal */}
      <ServiceDetailModal
        visible={showDetailModal}
        service={selectedService}
        onClose={() => setShowDetailModal(false)}
        onOrder={handleOrder}
      />
    </View>
  );
}
