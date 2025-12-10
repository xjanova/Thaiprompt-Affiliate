/**
 * Rider Services Screen - หน้าเลือกบริการที่ไรเดอร์ให้บริการ
 * ไรเดอร์สามารถเลือกได้หลายบริการ พร้อมระบุประสบการณ์
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  ActivityIndicator,
  TextInput,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';
import { router } from 'expo-router';
import Animated, {
  FadeInDown,
  FadeIn,
  ZoomIn,
  FadeInUp,
  useSharedValue,
  useAnimatedStyle,
  withSpring,
  withTiming,
  interpolateColor,
} from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { ErrorState } from '@/components/ErrorState';
import {
  SERVICE_CATEGORIES,
  SERVICE_GROUPS,
  ServiceCategory,
  ServiceSlug,
  formatServicePrice,
} from '@/constants/services';

// =====================================================
// Types
// =====================================================

interface SelectedService {
  slug: ServiceSlug;
  experience: string; // ประสบการณ์
  customPrice?: number; // ราคาที่กำหนดเอง
}

// =====================================================
// Premium Service Card Component
// =====================================================

const ServiceCard = ({
  service,
  isSelected,
  onToggle,
  index,
}: {
  service: ServiceCategory;
  isSelected: boolean;
  onToggle: () => void;
  index: number;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';
  const scale = useSharedValue(1);
  const selected = useSharedValue(isSelected ? 1 : 0);

  useEffect(() => {
    selected.value = withSpring(isSelected ? 1 : 0);
  }, [isSelected]);

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [{ scale: scale.value }],
  }));

  const borderStyle = useAnimatedStyle(() => ({
    borderColor: interpolateColor(
      selected.value,
      [0, 1],
      [isDark ? '#374151' : '#E5E7EB', service.color]
    ),
    borderWidth: withSpring(isSelected ? 2 : 1),
  }));

  const handlePressIn = () => {
    scale.value = withSpring(0.95);
  };

  const handlePressOut = () => {
    scale.value = withSpring(1);
  };

  return (
    <Animated.View
      entering={FadeInDown.delay(50 + index * 30).springify()}
      style={animatedStyle}
    >
      <Pressable
        onPress={onToggle}
        onPressIn={handlePressIn}
        onPressOut={handlePressOut}
      >
        <Animated.View
          style={[borderStyle]}
          className={`rounded-2xl p-4 mb-3 ${
            isDark ? 'bg-gray-800/80' : 'bg-white'
          }`}
        >
          <View className="flex-row items-center">
            {/* Icon with Gradient Background */}
            <LinearGradient
              colors={service.gradient}
              style={{
                width: 56,
                height: 56,
                borderRadius: 16,
                alignItems: 'center',
                justifyContent: 'center',
                marginRight: 16,
                shadowColor: service.color,
                shadowOffset: { width: 0, height: 4 },
                shadowOpacity: 0.3,
                shadowRadius: 8,
                elevation: 4,
              }}
            >
              <Text style={{ fontSize: 28, color: 'white' }}>{service.icon}</Text>
            </LinearGradient>

            {/* Service Info */}
            <View className="flex-1">
              <Text
                className={`font-bold text-lg ${
                  isDark ? 'text-white' : 'text-gray-900'
                }`}
              >
                {service.name}
              </Text>
              <Text
                className={`text-sm ${
                  isDark ? 'text-gray-400' : 'text-gray-500'
                }`}
                numberOfLines={1}
              >
                {service.description}
              </Text>
              <Text
                className="text-sm font-medium mt-1"
                style={{ color: service.color }}
              >
                เริ่มต้น {formatServicePrice(service)}
              </Text>
            </View>

            {/* Selection Indicator */}
            <Animated.View
              className={`w-8 h-8 rounded-full items-center justify-center ${
                isSelected
                  ? ''
                  : isDark
                  ? 'bg-gray-700 border border-gray-600'
                  : 'bg-gray-100 border border-gray-200'
              }`}
              style={
                isSelected
                  ? { backgroundColor: service.color }
                  : undefined
              }
            >
              {isSelected && (
                <Animated.View entering={ZoomIn.springify()}>
                  <Text style={{ fontSize: 20, color: 'white' }}>✓</Text>
                </Animated.View>
              )}
            </Animated.View>
          </View>

          {/* Selected Indicator Bar */}
          {isSelected && (
            <Animated.View
              entering={FadeIn}
              className="absolute left-0 top-0 bottom-0 w-1 rounded-l-2xl"
              style={{ backgroundColor: service.color }}
            />
          )}
        </Animated.View>
      </Pressable>
    </Animated.View>
  );
};

// =====================================================
// Service Group Section
// =====================================================

const ServiceGroupSection = ({
  title,
  icon,
  services,
  selectedServices,
  onToggleService,
  startIndex,
}: {
  title: string;
  icon: string;
  services: ServiceCategory[];
  selectedServices: ServiceSlug[];
  onToggleService: (slug: ServiceSlug) => void;
  startIndex: number;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';
  const selectedCount = services.filter((s) =>
    selectedServices.includes(s.slug)
  ).length;

  return (
    <Animated.View
      entering={FadeInDown.delay(startIndex * 20).springify()}
      className="mb-6"
    >
      {/* Group Header */}
      <View className="flex-row items-center mb-3">
        <View
          className={`w-10 h-10 rounded-xl items-center justify-center mr-3 ${
            isDark ? 'bg-gray-700' : 'bg-gray-100'
          }`}
        >
          <Text style={{ fontSize: 20, color: isDark ? '#9CA3AF' : '#6B7280' }}>{icon}</Text>
        </View>
        <View className="flex-1">
          <Text
            className={`font-bold text-lg ${
              isDark ? 'text-white' : 'text-gray-900'
            }`}
          >
            {title}
          </Text>
          <Text className="text-gray-500 text-sm">
            {services.length} บริการ
            {selectedCount > 0 && (
              <Text className="text-primary-500">
                {' '}
                • เลือก {selectedCount}
              </Text>
            )}
          </Text>
        </View>
      </View>

      {/* Services */}
      {services.map((service, index) => (
        <ServiceCard
          key={service.slug}
          service={service}
          isSelected={selectedServices.includes(service.slug)}
          onToggle={() => onToggleService(service.slug)}
          index={startIndex + index}
        />
      ))}
    </Animated.View>
  );
};

// =====================================================
// Selected Services Summary
// =====================================================

const SelectedServicesSummary = ({
  selectedServices,
  onClear,
}: {
  selectedServices: ServiceSlug[];
  onClear: () => void;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  if (selectedServices.length === 0) return null;

  const selectedItems = SERVICE_CATEGORIES.filter((s) =>
    selectedServices.includes(s.slug)
  );

  return (
    <Animated.View
      entering={FadeInUp.springify()}
      className={`mx-5 mb-4 rounded-2xl overflow-hidden ${
        isDark ? 'bg-gray-800/90' : 'bg-white'
      }`}
      style={{
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.1,
        shadowRadius: 12,
        elevation: 4,
      }}
    >
      <LinearGradient
        colors={['#3B82F6', '#1D4ED8']}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 0 }}
        style={{
          paddingHorizontal: 16,
          paddingVertical: 12,
          flexDirection: 'row',
          alignItems: 'center',
          justifyContent: 'space-between',
        }}
      >
        <View className="flex-row items-center">
          <View className="w-8 h-8 bg-white/20 rounded-full items-center justify-center mr-3">
            <Text className="text-white font-bold">{selectedServices.length}</Text>
          </View>
          <Text className="text-white font-bold">บริการที่เลือก</Text>
        </View>
        <Pressable
          onPress={onClear}
          className="px-3 py-1 bg-white/20 rounded-full"
        >
          <Text className="text-white text-sm">ล้าง</Text>
        </Pressable>
      </LinearGradient>

      <View className="p-4">
        <View className="flex-row flex-wrap">
          {selectedItems.slice(0, 6).map((service) => (
            <View
              key={service.slug}
              className="flex-row items-center mr-3 mb-2 px-3 py-1.5 rounded-full"
              style={{ backgroundColor: service.color + '20' }}
            >
              <Text style={{ fontSize: 14, color: service.color }}>{service.icon}</Text>
              <Text
                className="text-sm font-medium ml-1.5"
                style={{ color: service.color }}
              >
                {service.shortName}
              </Text>
            </View>
          ))}
          {selectedItems.length > 6 && (
            <View className="px-3 py-1.5 rounded-full bg-gray-200 dark:bg-gray-700">
              <Text className="text-sm text-gray-600 dark:text-gray-400">
                +{selectedItems.length - 6}
              </Text>
            </View>
          )}
        </View>
      </View>
    </Animated.View>
  );
};

// =====================================================
// Main Component
// =====================================================

export default function RiderServicesScreen() {
  const { isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [hasError, setHasError] = useState(false);
  const [selectedServices, setSelectedServices] = useState<ServiceSlug[]>([]);
  const [searchQuery, setSearchQuery] = useState('');

  // Load rider's current services
  useEffect(() => {
    const loadServices = async () => {
      try {
        setHasError(false);
        // TODO: Load from API
        // For now, simulate loading
        await new Promise((resolve) => setTimeout(resolve, 500));
        // Mock: rider already selected some services
        setSelectedServices(['food-delivery', 'parcel-delivery']);
      } catch (error) {
        console.error('Load services error:', error);
        setHasError(true);
      } finally {
        setIsLoading(false);
      }
    };

    if (isAuthenticated) {
      loadServices();
    }
  }, [isAuthenticated]);

  const handleToggleService = (slug: ServiceSlug) => {
    setSelectedServices((prev) => {
      if (prev.includes(slug)) {
        return prev.filter((s) => s !== slug);
      }
      return [...prev, slug];
    });
  };

  const handleClearAll = () => {
    Alert.alert('ล้างทั้งหมด', 'ต้องการล้างบริการที่เลือกทั้งหมด?', [
      { text: 'ยกเลิก', style: 'cancel' },
      {
        text: 'ล้าง',
        style: 'destructive',
        onPress: () => setSelectedServices([]),
      },
    ]);
  };

  const handleSave = async () => {
    if (selectedServices.length === 0) {
      Alert.alert('เลือกบริการ', 'กรุณาเลือกอย่างน้อย 1 บริการที่คุณให้บริการ');
      return;
    }

    setIsSaving(true);
    try {
      // TODO: Save to API
      await new Promise((resolve) => setTimeout(resolve, 1000));

      Alert.alert('สำเร็จ', `บันทึก ${selectedServices.length} บริการเรียบร้อย`, [
        { text: 'ตกลง', onPress: () => router.back() },
      ]);
    } catch (error) {
      Alert.alert(
        'ไม่สามารถเชื่อมต่อได้',
        'ขณะนี้ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้\nโปรดลองใหม่ภายหลัง'
      );
    } finally {
      setIsSaving(false);
    }
  };

  // Filter services by search
  const filteredGroups = SERVICE_GROUPS.map((group) => ({
    ...group,
    services: group.services.filter(
      (s) =>
        s.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        s.description.toLowerCase().includes(searchQuery.toLowerCase())
    ),
  })).filter((group) => group.services.length > 0);

  // Not authenticated
  if (!isAuthenticated) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <Text style={{ fontSize: 80, color: '#9CA3AF' }}>🔒</Text>
          <Text className="text-xl font-bold mt-4 text-gray-800 dark:text-white">
            กรุณาเข้าสู่ระบบ
          </Text>
          <Pressable
            onPress={() => router.push('/login')}
            className="bg-primary-500 px-8 py-3 rounded-xl mt-6"
          >
            <Text className="text-white font-bold">เข้าสู่ระบบ</Text>
          </Pressable>
        </SafeAreaView>
      </View>
    );
  }

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
          <View className="flex-row items-center px-5 pt-4 pb-2">
            <Pressable onPress={() => router.back()} className="mr-4">
              <Text style={{ fontSize: 24, color: isDark ? '#fff' : '#000' }}>←</Text>
            </Pressable>
            <Text
              className={`text-2xl font-bold ${
                isDark ? 'text-white' : 'text-gray-900'
              }`}
            >
              บริการของฉัน
            </Text>
          </View>
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

  let globalIndex = 0;

  return (
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
      <SafeAreaView className="flex-1">
        {/* Header */}
        <View className="flex-row items-center px-5 pt-4 pb-2">
          <Pressable onPress={() => router.back()} className="mr-4">
            <Text style={{ fontSize: 24, color: isDark ? '#fff' : '#000' }}>←</Text>
          </Pressable>
          <View className="flex-1">
            <Text
              className={`text-2xl font-bold ${
                isDark ? 'text-white' : 'text-gray-900'
              }`}
            >
              บริการของฉัน
            </Text>
            <Text className="text-gray-500 dark:text-gray-400 text-sm">
              เลือกบริการที่คุณให้บริการได้
            </Text>
          </View>
        </View>

        {/* Search Bar */}
        <View className="px-5 py-3">
          <View
            className={`flex-row items-center rounded-2xl px-4 py-3 ${
              isDark ? 'bg-gray-800' : 'bg-white'
            }`}
            style={{
              shadowColor: '#000',
              shadowOffset: { width: 0, height: 2 },
              shadowOpacity: 0.05,
              shadowRadius: 8,
              elevation: 2,
            }}
          >
            <Text style={{ fontSize: 20, color: isDark ? '#6B7280' : '#9CA3AF' }}>🔍</Text>
            <TextInput
              value={searchQuery}
              onChangeText={setSearchQuery}
              placeholder="ค้นหาบริการ..."
              placeholderTextColor={isDark ? '#6B7280' : '#9CA3AF'}
              className={`flex-1 ml-3 text-base ${
                isDark ? 'text-white' : 'text-gray-900'
              }`}
            />
            {searchQuery.length > 0 && (
              <Pressable onPress={() => setSearchQuery('')}>
                <Text style={{ fontSize: 20, color: isDark ? '#6B7280' : '#9CA3AF' }}>✕</Text>
              </Pressable>
            )}
          </View>
        </View>

        {/* Selected Summary */}
        <SelectedServicesSummary
          selectedServices={selectedServices}
          onClear={handleClearAll}
        />

        {/* Services List */}
        <ScrollView
          className="flex-1 px-5"
          contentContainerStyle={{ paddingBottom: 120 }}
          showsVerticalScrollIndicator={false}
        >
          {/* Info Banner */}
          <Animated.View
            entering={FadeInDown.delay(50).springify()}
            className="mb-6"
          >
            <LinearGradient
              colors={
                isDark
                  ? ['#1F2937', '#111827']
                  : ['#F3F4F6', '#E5E7EB']
              }
              style={{
                borderRadius: 16,
                padding: 16,
              }}
            >
              <View className="flex-row items-center">
                <View
                  className={`w-12 h-12 rounded-xl items-center justify-center mr-3 ${
                    isDark ? 'bg-primary-500/20' : 'bg-primary-100'
                  }`}
                >
                  <Text style={{ fontSize: 24, color: '#3B82F6' }}>💼</Text>
                </View>
                <View className="flex-1">
                  <Text
                    className={`font-bold ${
                      isDark ? 'text-white' : 'text-gray-900'
                    }`}
                  >
                    เลือกบริการที่คุณถนัด
                  </Text>
                  <Text
                    className={`text-sm ${
                      isDark ? 'text-gray-400' : 'text-gray-600'
                    }`}
                  >
                    ยิ่งเลือกหลากหลาย ยิ่งได้รับงานมากขึ้น
                  </Text>
                </View>
              </View>
            </LinearGradient>
          </Animated.View>

          {/* Service Groups */}
          {filteredGroups.map((group) => {
            const startIdx = globalIndex;
            globalIndex += group.services.length;
            return (
              <ServiceGroupSection
                key={group.type}
                title={group.title}
                icon={group.icon}
                services={group.services}
                selectedServices={selectedServices}
                onToggleService={handleToggleService}
                startIndex={startIdx}
              />
            );
          })}

          {filteredGroups.length === 0 && (
            <View className="items-center py-12">
              <Text style={{ fontSize: 48, color: isDark ? '#4B5563' : '#9CA3AF' }}>🔍</Text>
              <Text
                className={`mt-4 font-medium ${
                  isDark ? 'text-gray-400' : 'text-gray-500'
                }`}
              >
                ไม่พบบริการที่ค้นหา
              </Text>
            </View>
          )}
        </ScrollView>

        {/* Save Button */}
        <View
          className={`absolute bottom-0 left-0 right-0 px-5 py-4 pb-8 ${
            isDark ? 'bg-dark' : 'bg-gray-50'
          }`}
          style={{
            shadowColor: '#000',
            shadowOffset: { width: 0, height: -4 },
            shadowOpacity: 0.1,
            shadowRadius: 12,
            elevation: 8,
          }}
        >
          <Pressable
            onPress={handleSave}
            disabled={isSaving || selectedServices.length === 0}
            className={`rounded-2xl overflow-hidden ${
              isSaving || selectedServices.length === 0 ? 'opacity-50' : ''
            }`}
          >
            <LinearGradient
              colors={
                selectedServices.length > 0
                  ? ['#10B981', '#059669']
                  : ['#9CA3AF', '#6B7280']
              }
              style={{
                paddingVertical: 16,
                flexDirection: 'row',
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              {isSaving ? (
                <ActivityIndicator color="white" />
              ) : (
                <>
                  <Text style={{ fontSize: 24, color: 'white' }}>✓</Text>
                  <Text className="text-white font-bold text-lg ml-2">
                    บันทึก ({selectedServices.length} บริการ)
                  </Text>
                </>
              )}
            </LinearGradient>
          </Pressable>
        </View>
      </SafeAreaView>
    </View>
  );
}
