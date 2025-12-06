/**
 * Rider Job Detail Screen - หน้ารายละเอียดงานปัจจุบัน
 * แสดงข้อมูลงานที่รับ พร้อมปุ่มนำทางและอัพเดทสถานะ
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  ActivityIndicator,
  Linking,
  Platform,
  Modal,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import Animated, { FadeInDown, FadeIn, ZoomIn } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { getCurrentJob, updateJobStatus } from '@/services/api';
import { formatCurrency } from '@/constants';
import { startTracking, stopTracking, isTrackingLocation } from '@/services/location';

// =====================================================
// Types
// =====================================================

interface JobDetail {
  id: number;
  jobNumber: string;
  jobType: string;
  jobTypeText: string;
  title: string;
  description?: string;
  status: string;
  statusText: string;
  pickup: {
    address: string;
    latitude: number;
    longitude: number;
    contactName?: string;
    contactPhone?: string;
    notes?: string;
  };
  delivery: {
    address: string;
    latitude: number;
    longitude: number;
    contactName?: string;
    contactPhone?: string;
    notes?: string;
  };
  distanceKm?: number;
  totalFee: number;
  riderEarnings: number;
  acceptedAt?: string;
  pickedUpAt?: string;
}

// =====================================================
// Status Steps Component
// =====================================================

const StatusStep = ({
  step,
  label,
  isActive,
  isCompleted,
}: {
  step: number;
  label: string;
  isActive: boolean;
  isCompleted: boolean;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  return (
    <View className="items-center flex-1">
      <View
        className={`w-10 h-10 rounded-full items-center justify-center ${
          isCompleted
            ? 'bg-green-500'
            : isActive
              ? 'bg-primary-500'
              : isDark
                ? 'bg-gray-700'
                : 'bg-gray-200'
        }`}
      >
        {isCompleted ? (
          <Ionicons name="checkmark" size={20} color="white" />
        ) : (
          <Text
            className={`font-bold ${
              isActive ? 'text-white' : isDark ? 'text-gray-400' : 'text-gray-500'
            }`}
          >
            {step}
          </Text>
        )}
      </View>
      <Text
        className={`text-xs mt-1 text-center ${
          isActive || isCompleted
            ? isDark
              ? 'text-white'
              : 'text-gray-800'
            : 'text-gray-400'
        }`}
      >
        {label}
      </Text>
    </View>
  );
};

// =====================================================
// Location Card Component
// =====================================================

const LocationCard = ({
  type,
  location,
  onNavigate,
  onCall,
}: {
  type: 'pickup' | 'delivery';
  location: JobDetail['pickup'];
  onNavigate: () => void;
  onCall: () => void;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';
  const isPickup = type === 'pickup';

  return (
    <Animated.View
      entering={FadeInDown.delay(isPickup ? 100 : 150).springify()}
      className="bg-white dark:bg-gray-800 rounded-2xl p-4 mb-4 border border-gray-100 dark:border-gray-700"
    >
      <View className="flex-row items-start mb-3">
        <View
          className={`w-12 h-12 rounded-xl items-center justify-center mr-3 ${
            isPickup ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'
          }`}
        >
          <Ionicons
            name={isPickup ? 'location' : 'flag'}
            size={24}
            color={isPickup ? '#10B981' : '#EF4444'}
          />
        </View>
        <View className="flex-1">
          <Text className="text-gray-500 dark:text-gray-400 text-sm mb-1">
            {isPickup ? 'จุดรับของ' : 'จุดส่งของ'}
          </Text>
          <Text className="text-gray-900 dark:text-white font-bold">
            {location.address}
          </Text>
        </View>
      </View>

      {/* Contact Info */}
      {location.contactName && (
        <View className="flex-row items-center mb-2 pl-15">
          <Ionicons
            name="person"
            size={16}
            color={isDark ? '#9CA3AF' : '#6B7280'}
          />
          <Text className="text-gray-600 dark:text-gray-400 ml-2">
            {location.contactName}
          </Text>
        </View>
      )}

      {location.contactPhone && (
        <View className="flex-row items-center mb-2 pl-15">
          <Ionicons
            name="call"
            size={16}
            color={isDark ? '#9CA3AF' : '#6B7280'}
          />
          <Text className="text-gray-600 dark:text-gray-400 ml-2">
            {location.contactPhone}
          </Text>
        </View>
      )}

      {location.notes && (
        <View className="bg-yellow-50 dark:bg-yellow-900/30 rounded-xl p-3 mb-3">
          <Text className="text-yellow-700 dark:text-yellow-300 text-sm">
            📝 {location.notes}
          </Text>
        </View>
      )}

      {/* Action Buttons */}
      <View className="flex-row mt-2">
        {location.contactPhone && (
          <Pressable
            onPress={onCall}
            className="flex-1 mr-2 bg-green-100 dark:bg-green-900/30 rounded-xl py-3 flex-row items-center justify-center"
          >
            <Ionicons name="call" size={18} color="#10B981" />
            <Text className="text-green-600 dark:text-green-400 font-medium ml-2">
              โทร
            </Text>
          </Pressable>
        )}
        <Pressable
          onPress={onNavigate}
          className={`flex-1 ${location.contactPhone ? 'ml-2' : ''} bg-primary-500 rounded-xl py-3 flex-row items-center justify-center`}
        >
          <Ionicons name="navigate" size={18} color="white" />
          <Text className="text-white font-bold ml-2">นำทาง</Text>
        </Pressable>
      </View>
    </Animated.View>
  );
};

// =====================================================
// Photo Proof Modal
// =====================================================

const PhotoProofModal = ({
  visible,
  onClose,
  onConfirm,
  isLoading,
}: {
  visible: boolean;
  onClose: () => void;
  onConfirm: (imageUri: string) => void;
  isLoading: boolean;
}) => {
  const [imageUri, setImageUri] = useState<string | null>(null);

  const takePhoto = async () => {
    const { status } = await ImagePicker.requestCameraPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert('ต้องอนุญาตกล้อง', 'กรุณาอนุญาตการเข้าถึงกล้องเพื่อถ่ายรูปหลักฐาน');
      return;
    }

    const result = await ImagePicker.launchCameraAsync({
      mediaTypes: 'images',
      quality: 0.8,
      allowsEditing: true,
      aspect: [4, 3],
    });

    if (!result.canceled && result.assets[0]) {
      setImageUri(result.assets[0].uri);
    }
  };

  return (
    <Modal visible={visible} transparent animationType="fade">
      <View className="flex-1 bg-black/60 justify-center items-center px-6">
        <Animated.View
          entering={ZoomIn.springify()}
          className="bg-white dark:bg-gray-800 rounded-3xl w-full max-w-md overflow-hidden"
        >
          <LinearGradient
            colors={['#10B981', '#059669']}
            className="p-6 items-center"
          >
            <Ionicons name="camera" size={40} color="white" />
            <Text className="text-white text-xl font-bold mt-2">
              ถ่ายรูปหลักฐาน
            </Text>
          </LinearGradient>

          <View className="p-6">
            {imageUri ? (
              <View className="items-center">
                <View className="w-full h-48 bg-gray-100 dark:bg-gray-700 rounded-xl mb-4 overflow-hidden">
                  <Animated.Image
                    entering={FadeIn}
                    source={{ uri: imageUri }}
                    className="w-full h-full"
                    resizeMode="cover"
                  />
                </View>
                <Pressable
                  onPress={takePhoto}
                  className="bg-gray-100 dark:bg-gray-700 rounded-xl py-2 px-4"
                >
                  <Text className="text-gray-600 dark:text-gray-400">ถ่ายใหม่</Text>
                </Pressable>
              </View>
            ) : (
              <Pressable
                onPress={takePhoto}
                className="bg-gray-100 dark:bg-gray-700 rounded-2xl py-12 items-center"
              >
                <Ionicons name="camera" size={48} color="#9CA3AF" />
                <Text className="text-gray-500 mt-2">แตะเพื่อถ่ายรูป</Text>
              </Pressable>
            )}
          </View>

          <View className="flex-row p-4 border-t border-gray-200 dark:border-gray-700">
            <Pressable
              onPress={() => {
                setImageUri(null);
                onClose();
              }}
              disabled={isLoading}
              className="flex-1 py-3 mr-2 bg-gray-100 dark:bg-gray-700 rounded-xl"
            >
              <Text className="text-gray-700 dark:text-gray-300 text-center font-medium">
                ยกเลิก
              </Text>
            </Pressable>
            <Pressable
              onPress={() => imageUri && onConfirm(imageUri)}
              disabled={!imageUri || isLoading}
              className={`flex-1 py-3 ml-2 bg-green-500 rounded-xl ${!imageUri || isLoading ? 'opacity-50' : ''}`}
            >
              {isLoading ? (
                <ActivityIndicator color="white" />
              ) : (
                <Text className="text-white text-center font-bold">ยืนยัน</Text>
              )}
            </Pressable>
          </View>
        </Animated.View>
      </View>
    </Modal>
  );
};

// =====================================================
// Main Component
// =====================================================

export default function RiderJobDetailScreen() {
  const { isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [job, setJob] = useState<JobDetail | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isUpdating, setIsUpdating] = useState(false);
  const [showProofModal, setShowProofModal] = useState(false);
  const [isTracking, setIsTracking] = useState(false);

  // Status progression
  const statusSteps = ['picking_up', 'picked_up', 'delivering', 'delivered', 'completed'];
  const currentStepIndex = job ? statusSteps.indexOf(job.status) : 0;

  // Load job
  const loadJob = useCallback(async () => {
    try {
      const response = await getCurrentJob();
      if (response?.success && response.data?.hasJob && response.data.job) {
        setJob(response.data.job);
        setIsTracking(response.data.isTracking || false);
      } else {
        // No current job
        Alert.alert('ไม่มีงาน', 'คุณยังไม่ได้รับงาน', [
          { text: 'กลับ', onPress: () => router.back() },
        ]);
      }
    } catch (error) {
      console.error('Load job error:', error);
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    if (isAuthenticated) {
      loadJob();
    }
  }, [isAuthenticated, loadJob]);

  // Start tracking when job is loaded
  useEffect(() => {
    if (job && !isTrackingLocation()) {
      startTracking(job.id);
      setIsTracking(true);
    }

    return () => {
      // Stop tracking when leaving screen
      if (isTrackingLocation()) {
        stopTracking();
      }
    };
  }, [job]);

  // Navigate to location
  const navigateTo = (latitude: number, longitude: number) => {
    const url = Platform.select({
      ios: `maps://?daddr=${latitude},${longitude}`,
      android: `google.navigation:q=${latitude},${longitude}`,
    });

    if (url) {
      Linking.openURL(url).catch(() => {
        Linking.openURL(
          `https://www.google.com/maps/dir/?api=1&destination=${latitude},${longitude}`
        );
      });
    }
  };

  // Call contact
  const callContact = (phone: string) => {
    Linking.openURL(`tel:${phone}`);
  };

  // Update status
  const handleUpdateStatus = async (newStatus: string, proofImage?: string) => {
    if (!job) return;

    setIsUpdating(true);
    try {
      const response = await updateJobStatus(job.id, newStatus as 'picked_up' | 'delivered' | 'completed', {
        proofImage,
      });

      if (response.success) {
        if (newStatus === 'completed') {
          Alert.alert(
            '🎉 ส่งสำเร็จ!',
            `คุณได้รับ ${formatCurrency(job.riderEarnings)}`,
            [
              {
                text: 'ตกลง',
                onPress: () => {
                  stopTracking();
                  router.replace('/rider');
                },
              },
            ]
          );
        } else {
          await loadJob();
        }
      } else {
        Alert.alert('ไม่สำเร็จ', response.message || 'ไม่สามารถอัพเดทสถานะได้');
      }
    } catch (error) {
      console.error('Update status error:', error);
      Alert.alert('ข้อผิดพลาด', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
    } finally {
      setIsUpdating(false);
      setShowProofModal(false);
    }
  };

  // Cancel job
  const handleCancelJob = () => {
    Alert.alert(
      'ยกเลิกงาน?',
      'การยกเลิกงานอาจมีผลต่อคะแนนของคุณ',
      [
        { text: 'ไม่', style: 'cancel' },
        {
          text: 'ยกเลิก',
          style: 'destructive',
          onPress: async () => {
            if (!job) return;
            setIsUpdating(true);
            try {
              const response = await updateJobStatus(job.id, 'cancelled', {
                cancellationReason: 'ไรเดอร์ยกเลิก',
              });
              if (response.success) {
                stopTracking();
                router.replace('/rider');
              } else {
                Alert.alert('ไม่สำเร็จ', response.message || 'ไม่สามารถยกเลิกได้');
              }
            } catch (error) {
              Alert.alert('ข้อผิดพลาด', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
            } finally {
              setIsUpdating(false);
            }
          },
        },
      ]
    );
  };

  // Get next status action
  const getNextAction = () => {
    if (!job) return null;

    switch (job.status) {
      case 'picking_up':
        return {
          label: 'ถึงจุดรับแล้ว',
          color: ['#10B981', '#059669'] as [string, string],
          onPress: () => handleUpdateStatus('picked_up'),
        };
      case 'picked_up':
        return {
          label: 'เริ่มจัดส่ง',
          color: ['#3B82F6', '#1D4ED8'] as [string, string],
          onPress: () => handleUpdateStatus('delivering'),
        };
      case 'delivering':
        return {
          label: 'ถึงจุดส่งแล้ว',
          color: ['#8B5CF6', '#6D28D9'] as [string, string],
          onPress: () => handleUpdateStatus('delivered'),
        };
      case 'delivered':
        return {
          label: 'ส่งสำเร็จ - ถ่ายรูปหลักฐาน',
          color: ['#10B981', '#059669'] as [string, string],
          onPress: () => setShowProofModal(true),
        };
      default:
        return null;
    }
  };

  // Not authenticated
  if (!isAuthenticated) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <Ionicons name="lock-closed" size={80} color="#9CA3AF" />
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

  // Loading
  if (isLoading) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center">
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text className="text-gray-500 mt-4">กำลังโหลดข้อมูลงาน...</Text>
        </SafeAreaView>
      </View>
    );
  }

  if (!job) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <Ionicons name="cube-outline" size={80} color="#9CA3AF" />
          <Text className="text-xl font-bold mt-4 text-gray-800 dark:text-white">
            ไม่มีงาน
          </Text>
          <Pressable
            onPress={() => router.back()}
            className="bg-primary-500 px-8 py-3 rounded-xl mt-6"
          >
            <Text className="text-white font-bold">กลับ</Text>
          </Pressable>
        </SafeAreaView>
      </View>
    );
  }

  const nextAction = getNextAction();

  return (
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
      <SafeAreaView className="flex-1">
        {/* Header */}
        <View className="flex-row items-center px-5 pt-4 pb-2">
          <Pressable onPress={() => router.back()} className="mr-4">
            <Ionicons name="arrow-back" size={24} color={isDark ? '#fff' : '#000'} />
          </Pressable>
          <View className="flex-1">
            <Text className={`text-xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
              งาน #{job.jobNumber}
            </Text>
            <Text className="text-gray-500 dark:text-gray-400 text-sm">
              {job.jobTypeText}
            </Text>
          </View>
          {isTracking && (
            <View className="flex-row items-center bg-green-100 dark:bg-green-900/30 px-3 py-1 rounded-full">
              <View className="w-2 h-2 rounded-full bg-green-500 mr-2" />
              <Text className="text-green-600 dark:text-green-400 text-xs font-medium">
                กำลังติดตาม
              </Text>
            </View>
          )}
        </View>

        <ScrollView
          className="flex-1 px-5"
          contentContainerClassName="pb-32"
          showsVerticalScrollIndicator={false}
        >
          {/* Status Progress */}
          <Animated.View
            entering={FadeInDown.delay(50).springify()}
            className="bg-white dark:bg-gray-800 rounded-2xl p-4 mb-4"
          >
            <View className="flex-row items-center justify-between">
              <StatusStep
                step={1}
                label="รับของ"
                isActive={job.status === 'picking_up'}
                isCompleted={currentStepIndex > 0}
              />
              <View className={`flex-1 h-0.5 mx-1 ${currentStepIndex > 0 ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-600'}`} />
              <StatusStep
                step={2}
                label="รับแล้ว"
                isActive={job.status === 'picked_up'}
                isCompleted={currentStepIndex > 1}
              />
              <View className={`flex-1 h-0.5 mx-1 ${currentStepIndex > 1 ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-600'}`} />
              <StatusStep
                step={3}
                label="กำลังส่ง"
                isActive={job.status === 'delivering'}
                isCompleted={currentStepIndex > 2}
              />
              <View className={`flex-1 h-0.5 mx-1 ${currentStepIndex > 2 ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-600'}`} />
              <StatusStep
                step={4}
                label="ส่งแล้ว"
                isActive={job.status === 'delivered' || job.status === 'completed'}
                isCompleted={job.status === 'completed'}
              />
            </View>
          </Animated.View>

          {/* Job Info */}
          <Animated.View
            entering={FadeInDown.delay(75).springify()}
            className="bg-white dark:bg-gray-800 rounded-2xl p-4 mb-4"
          >
            <Text className="text-gray-900 dark:text-white font-bold text-lg mb-2">
              {job.title}
            </Text>
            {job.description && (
              <Text className="text-gray-600 dark:text-gray-400 mb-3">
                {job.description}
              </Text>
            )}
            <View className="flex-row items-center justify-between bg-green-50 dark:bg-green-900/30 rounded-xl p-3">
              <Text className="text-green-700 dark:text-green-300">รายได้</Text>
              <Text className="text-green-600 dark:text-green-400 font-bold text-xl">
                {formatCurrency(job.riderEarnings)}
              </Text>
            </View>
          </Animated.View>

          {/* Pickup Location */}
          <LocationCard
            type="pickup"
            location={job.pickup}
            onNavigate={() => navigateTo(job.pickup.latitude, job.pickup.longitude)}
            onCall={() => job.pickup.contactPhone && callContact(job.pickup.contactPhone)}
          />

          {/* Delivery Location */}
          <LocationCard
            type="delivery"
            location={job.delivery}
            onNavigate={() => navigateTo(job.delivery.latitude, job.delivery.longitude)}
            onCall={() => job.delivery.contactPhone && callContact(job.delivery.contactPhone)}
          />

          {/* Distance Info */}
          {job.distanceKm && (
            <Animated.View
              entering={FadeInDown.delay(200).springify()}
              className="bg-white dark:bg-gray-800 rounded-2xl p-4 flex-row items-center"
            >
              <View className="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 items-center justify-center mr-3">
                <Ionicons name="navigate" size={24} color={isDark ? '#9CA3AF' : '#6B7280'} />
              </View>
              <View>
                <Text className="text-gray-500 dark:text-gray-400 text-sm">ระยะทาง</Text>
                <Text className="text-gray-900 dark:text-white font-bold text-lg">
                  {job.distanceKm.toFixed(1)} กม.
                </Text>
              </View>
            </Animated.View>
          )}
        </ScrollView>

        {/* Bottom Actions */}
        <View className="absolute bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 px-5 py-4 pb-8">
          <View className="flex-row">
            <Pressable
              onPress={handleCancelJob}
              disabled={isUpdating}
              className="mr-3 bg-red-100 dark:bg-red-900/30 rounded-xl py-4 px-6"
            >
              <Ionicons name="close" size={24} color="#EF4444" />
            </Pressable>

            {nextAction && (
              <Pressable
                onPress={nextAction.onPress}
                disabled={isUpdating}
                className={`flex-1 rounded-xl overflow-hidden ${isUpdating ? 'opacity-50' : ''}`}
              >
                <LinearGradient
                  colors={nextAction.color}
                  className="py-4 flex-row items-center justify-center"
                >
                  {isUpdating ? (
                    <ActivityIndicator color="white" />
                  ) : (
                    <>
                      <Ionicons name="checkmark-circle" size={20} color="white" />
                      <Text className="text-white font-bold text-lg ml-2">
                        {nextAction.label}
                      </Text>
                    </>
                  )}
                </LinearGradient>
              </Pressable>
            )}
          </View>
        </View>
      </SafeAreaView>

      {/* Photo Proof Modal */}
      <PhotoProofModal
        visible={showProofModal}
        onClose={() => setShowProofModal(false)}
        onConfirm={(imageUri) => handleUpdateStatus('completed', imageUri)}
        isLoading={isUpdating}
      />
    </View>
  );
}
