/**
 * Rider Job Detail Screen - หน้ารายละเอียดงานปัจจุบัน
 * รองรับ GPS tracking, ถ่ายรูปพร้อม geolocation, คำนวณค่าส่ง
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
  Image,
  StatusBar,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import * as Location from 'expo-location';
import Animated, { FadeInDown, FadeIn, ZoomIn } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { getCurrentJob, updateJobStatus, updateRiderLocation } from '@/services/api';
import { formatCurrency } from '@/constants';
import { startTracking, stopTracking, isTrackingLocation, getCurrentLocation } from '@/services/location';
import { ErrorState, NetworkErrorBanner } from '@/components/ErrorState';
import {
  calculateDistance,
  calculateDeliveryFee,
  formatDistance,
  formatEstimatedTime,
  isRushHour,
  isLateNight,
} from '@/utils/delivery';

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

interface ProofData {
  imageUri: string;
  latitude: number;
  longitude: number;
  accuracy?: number;
  timestamp: string;
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
          <Text style={{ fontSize: 20, color: 'white' }}>✓</Text>
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
  currentLocation,
  onNavigate,
  onCall,
}: {
  type: 'pickup' | 'delivery';
  location: JobDetail['pickup'];
  currentLocation?: { latitude: number; longitude: number } | null;
  onNavigate: () => void;
  onCall: () => void;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';
  const isPickup = type === 'pickup';

  // คำนวณระยะทางจากตำแหน่งปัจจุบัน
  const distanceFromCurrent = currentLocation
    ? calculateDistance(currentLocation, { latitude: location.latitude, longitude: location.longitude })
    : null;

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
          <Text style={{ fontSize: 24, color: isPickup ? '#10B981' : '#EF4444' }}>
            {isPickup ? '📍' : '🚩'}
          </Text>
        </View>
        <View className="flex-1">
          <View className="flex-row items-center justify-between">
            <Text className="text-gray-500 dark:text-gray-400 text-sm mb-1">
              {isPickup ? 'จุดรับของ' : 'จุดส่งของ'}
            </Text>
            {distanceFromCurrent !== null && (
              <Text className="text-primary-500 text-sm font-medium">
                📍 {formatDistance(distanceFromCurrent)}
              </Text>
            )}
          </View>
          <Text className="text-gray-900 dark:text-white font-bold">
            {location.address}
          </Text>
        </View>
      </View>

      {/* Contact Info */}
      {location.contactName && (
        <View className="flex-row items-center mb-2 ml-15">
          <Text style={{ fontSize: 16, color: isDark ? '#9CA3AF' : '#6B7280' }}>👤</Text>
          <Text className="text-gray-600 dark:text-gray-400 ml-2">
            {location.contactName}
          </Text>
        </View>
      )}

      {location.contactPhone && (
        <View className="flex-row items-center mb-2 ml-15">
          <Text style={{ fontSize: 16, color: isDark ? '#9CA3AF' : '#6B7280' }}>📞</Text>
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
            <Text style={{ fontSize: 18, color: '#10B981' }}>📞</Text>
            <Text className="text-green-600 dark:text-green-400 font-medium ml-2">
              โทร
            </Text>
          </Pressable>
        )}
        <Pressable
          onPress={onNavigate}
          className={`flex-1 ${location.contactPhone ? 'ml-2' : ''} bg-primary-500 rounded-xl py-3 flex-row items-center justify-center`}
        >
          <Text style={{ fontSize: 18, color: 'white' }}>🧭</Text>
          <Text className="text-white font-bold ml-2">นำทาง</Text>
        </Pressable>
      </View>
    </Animated.View>
  );
};

// =====================================================
// Fee Breakdown Card
// =====================================================

const FeeBreakdownCard = ({
  job,
}: {
  job: JobDetail;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  // คำนวณค่าส่ง - ⭐ เพิ่ม null checks เพื่อป้องกัน crash
  const feeResult = calculateDeliveryFee(
    { latitude: job?.pickup?.latitude ?? 0, longitude: job?.pickup?.longitude ?? 0 },
    { latitude: job?.delivery?.latitude ?? 0, longitude: job?.delivery?.longitude ?? 0 },
    {
      isRushHour: isRushHour(),
      isLateNight: isLateNight(),
    }
  );

  return (
    <Animated.View
      entering={FadeInDown.delay(175).springify()}
      className="bg-white dark:bg-gray-800 rounded-2xl p-4 mb-4"
    >
      <Text className={`font-bold mb-3 ${isDark ? 'text-white' : 'text-gray-800'}`}>
        💰 รายละเอียดค่าส่ง
      </Text>

      <View className="space-y-2">
        <View className="flex-row justify-between">
          <Text className="text-gray-500 dark:text-gray-400">ค่าเริ่มต้น</Text>
          <Text className={isDark ? 'text-gray-300' : 'text-gray-700'}>
            ฿{feeResult.breakdown.baseFee}
          </Text>
        </View>

        <View className="flex-row justify-between">
          <Text className="text-gray-500 dark:text-gray-400">
            ค่าระยะทาง ({formatDistance(feeResult.distanceKm)})
          </Text>
          <Text className={isDark ? 'text-gray-300' : 'text-gray-700'}>
            ฿{feeResult.breakdown.distanceFee}
          </Text>
        </View>

        {feeResult.breakdown.timeMultiplier > 1 && (
          <View className="flex-row justify-between">
            <Text className="text-orange-500">
              {isLateNight() ? '🌙 ค่าบริการดึก' : '⏰ ค่าบริการชั่วโมงเร่งด่วน'}
            </Text>
            <Text className="text-orange-500">
              x{feeResult.breakdown.timeMultiplier}
            </Text>
          </View>
        )}

        <View className="border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
          <View className="flex-row justify-between">
            <Text className={`font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
              ลูกค้าจ่าย
            </Text>
            <Text className={`font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
              {formatCurrency(feeResult.totalFee)}
            </Text>
          </View>

          <View className="flex-row justify-between mt-1">
            <Text className="text-gray-500 dark:text-gray-400 text-sm">
              ค่าธรรมเนียมแพลตฟอร์ม
            </Text>
            <Text className="text-gray-500 dark:text-gray-400 text-sm">
              -฿{feeResult.platformFee}
            </Text>
          </View>
        </View>

        <View className="bg-green-50 dark:bg-green-900/30 rounded-xl p-3 mt-2">
          <View className="flex-row justify-between items-center">
            <Text className="text-green-700 dark:text-green-300 font-bold">
              คุณได้รับ
            </Text>
            <Text className="text-green-600 dark:text-green-400 font-bold text-xl">
              {formatCurrency(job.riderEarnings || feeResult.riderEarnings)}
            </Text>
          </View>
        </View>

        <View className="flex-row items-center justify-center mt-2">
          <Text style={{ fontSize: 14, color: isDark ? '#9CA3AF' : '#6B7280' }}>⏱️</Text>
          <Text className="text-gray-500 dark:text-gray-400 text-sm ml-1">
            ประมาณ {formatEstimatedTime(feeResult.estimatedMinutes)}
          </Text>
        </View>
      </View>
    </Animated.View>
  );
};

// =====================================================
// Photo Proof Modal with GPS
// =====================================================

const PhotoProofModal = ({
  visible,
  onClose,
  onConfirm,
  isLoading,
}: {
  visible: boolean;
  onClose: () => void;
  onConfirm: (proofData: ProofData) => void;
  isLoading: boolean;
}) => {
  const [imageUri, setImageUri] = useState<string | null>(null);
  const [gpsLocation, setGpsLocation] = useState<{
    latitude: number;
    longitude: number;
    accuracy?: number;
  } | null>(null);
  const [isGettingLocation, setIsGettingLocation] = useState(false);
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const takePhoto = async () => {
    const { status } = await ImagePicker.requestCameraPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert('ต้องอนุญาตกล้อง', 'กรุณาอนุญาตการเข้าถึงกล้องเพื่อถ่ายรูปหลักฐาน');
      return;
    }

    // Get current location before taking photo
    setIsGettingLocation(true);
    try {
      const location = await getCurrentLocation();
      if (location) {
        setGpsLocation({
          latitude: location.latitude,
          longitude: location.longitude,
          accuracy: location.accuracy,
        });
      }
    } catch (error) {
      console.error('Get location error:', error);
    }
    setIsGettingLocation(false);

    const result = await ImagePicker.launchCameraAsync({
      mediaTypes: 'images',
      quality: 0.8,
      allowsEditing: false,
      exif: true,
    });

    if (!result.canceled && result.assets[0]) {
      setImageUri(result.assets[0].uri);

      // Try to get EXIF GPS if available
      if (result.assets[0].exif?.GPSLatitude && result.assets[0].exif?.GPSLongitude) {
        setGpsLocation({
          latitude: result.assets[0].exif.GPSLatitude,
          longitude: result.assets[0].exif.GPSLongitude,
        });
      }
    }
  };

  const handleConfirm = () => {
    if (!imageUri) return;

    // Use location from getCurrentLocation or fallback
    const location = gpsLocation || { latitude: 0, longitude: 0 };

    onConfirm({
      imageUri,
      latitude: location.latitude,
      longitude: location.longitude,
      accuracy: location.accuracy,
      timestamp: new Date().toISOString(),
    });
  };

  const resetState = () => {
    setImageUri(null);
    setGpsLocation(null);
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
            style={{
              padding: 24,
              alignItems: 'center',
            }}
          >
            <Text style={{ fontSize: 40, color: 'white' }}>📷</Text>
            <Text className="text-white text-xl font-bold mt-2">
              ถ่ายรูปหลักฐานการส่ง
            </Text>
            <Text className="text-green-100 text-sm mt-1">
              พร้อมบันทึกตำแหน่ง GPS
            </Text>
          </LinearGradient>

          <View className="p-6">
            {imageUri ? (
              <View>
                <View className="w-full h-48 bg-gray-100 dark:bg-gray-700 rounded-xl mb-3 overflow-hidden">
                  <Image
                    source={{ uri: imageUri }}
                    className="w-full h-full"
                    resizeMode="cover"
                  />
                </View>

                {/* GPS Info */}
                {gpsLocation && (
                  <View className="flex-row items-center justify-center bg-green-50 dark:bg-green-900/30 rounded-xl p-2 mb-3">
                    <Text className="text-green-600 dark:text-green-400 text-sm ml-2">
                      📍 {gpsLocation.latitude.toFixed(6)}, {gpsLocation.longitude.toFixed(6)}
                      {gpsLocation.accuracy && ` (±${Math.round(gpsLocation.accuracy)}m)`}
                    </Text>
                  </View>
                )}

                <Pressable
                  onPress={takePhoto}
                  className="bg-gray-100 dark:bg-gray-700 rounded-xl py-2 px-4 self-center"
                >
                  <Text className="text-gray-600 dark:text-gray-400">ถ่ายใหม่</Text>
                </Pressable>
              </View>
            ) : (
              <Pressable
                onPress={takePhoto}
                disabled={isGettingLocation}
                className="bg-gray-100 dark:bg-gray-700 rounded-2xl py-12 items-center"
              >
                {isGettingLocation ? (
                  <>
                    <ActivityIndicator size="large" color="#10B981" />
                    <Text className="text-gray-500 mt-2">กำลังรับตำแหน่ง GPS...</Text>
                  </>
                ) : (
                  <>
                    <Text style={{ fontSize: 48, color: '#9CA3AF' }}>📷</Text>
                    <Text className="text-gray-500 mt-2">แตะเพื่อถ่ายรูป</Text>
                    <Text className="text-gray-400 text-xs mt-1">
                      จะบันทึกตำแหน่ง GPS อัตโนมัติ
                    </Text>
                  </>
                )}
              </Pressable>
            )}

            {/* Warning */}
            <View className="bg-yellow-50 dark:bg-yellow-900/30 rounded-xl p-3 mt-4">
              <Text className="text-yellow-700 dark:text-yellow-300 text-sm text-center">
                ⚠️ ถ่ายรูปหน้าบ้าน/สถานที่ที่ส่งของเพื่อยืนยัน
              </Text>
            </View>
          </View>

          <View className="flex-row p-4 border-t border-gray-200 dark:border-gray-700">
            <Pressable
              onPress={() => {
                resetState();
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
              onPress={handleConfirm}
              disabled={!imageUri || isLoading}
              className={`flex-1 py-3 ml-2 bg-green-500 rounded-xl ${
                !imageUri || isLoading ? 'opacity-50' : ''
              }`}
            >
              {isLoading ? (
                <ActivityIndicator color="white" />
              ) : (
                <Text className="text-white text-center font-bold">ยืนยันส่งสำเร็จ</Text>
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
  const [hasError, setHasError] = useState(false);
  const [currentLocation, setCurrentLocation] = useState<{
    latitude: number;
    longitude: number;
  } | null>(null);

  // Status progression
  const statusSteps = ['picking_up', 'picked_up', 'delivering', 'delivered', 'completed'];
  const currentStepIndex = job ? statusSteps.indexOf(job.status) : 0;

  // Load job
  const loadJob = useCallback(async () => {
    setHasError(false);
    try {
      const response = await getCurrentJob();
      if (response?.success && response.data?.hasJob && response.data.job) {
        setJob(response.data.job);
        setIsTracking(response.data.isTracking || false);
      } else if (response === null) {
        // API error
        setHasError(true);
      } else {
        // No current job
        Alert.alert('ไม่มีงาน', 'คุณยังไม่ได้รับงาน', [
          { text: 'กลับ', onPress: () => router.back() },
        ]);
      }
    } catch (error) {
      console.error('Load job error:', error);
      setHasError(true);
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Get current location
  const updateCurrentLocation = useCallback(async () => {
    try {
      const location = await getCurrentLocation();
      if (location) {
        setCurrentLocation({
          latitude: location.latitude,
          longitude: location.longitude,
        });

        // Send location to server
        if (job) {
          await updateRiderLocation({
            latitude: location.latitude,
            longitude: location.longitude,
            accuracy: location.accuracy,
          });
        }
      }
    } catch (error) {
      console.error('Get location error:', error);
    }
  }, [job]);

  useEffect(() => {
    if (isAuthenticated) {
      loadJob();
    }
  }, [isAuthenticated, loadJob]);

  // Start tracking and location updates
  useEffect(() => {
    if (job && !isTrackingLocation()) {
      startTracking(job.id);
      setIsTracking(true);
    }

    // Update location every 30 seconds
    updateCurrentLocation();
    const locationInterval = setInterval(updateCurrentLocation, 30000);

    return () => {
      clearInterval(locationInterval);
      if (isTrackingLocation()) {
        stopTracking();
      }
    };
  }, [job, updateCurrentLocation]);

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
  const handleUpdateStatus = async (newStatus: string, proofData?: ProofData) => {
    if (!job) return;

    setIsUpdating(true);
    try {
      const response = await updateJobStatus(
        job.id,
        newStatus as 'picked_up' | 'delivered' | 'completed',
        {
          proofImage: proofData?.imageUri,
        }
      );

      if (response.success) {
        if (newStatus === 'completed') {
          Alert.alert(
            '🎉 ส่งสำเร็จ!',
            `คุณได้รับ ${formatCurrency(job.riderEarnings)}\n\n📍 บันทึกตำแหน่ง: ${
              proofData?.latitude?.toFixed(4)
            }, ${proofData?.longitude?.toFixed(4)}`,
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
        Alert.alert(
          'ไม่สำเร็จ',
          response.message || 'ขณะนี้ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ โปรดลองใหม่ภายหลัง'
        );
      }
    } catch (error) {
      console.error('Update status error:', error);
      Alert.alert(
        'ข้อผิดพลาด',
        'ขณะนี้ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ โปรดลองใหม่ภายหลัง'
      );
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
                Alert.alert(
                  'ไม่สำเร็จ',
                  response.message || 'ขณะนี้ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ โปรดลองใหม่ภายหลัง'
                );
              }
            } catch (error) {
              Alert.alert(
                'ข้อผิดพลาด',
                'ขณะนี้ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ โปรดลองใหม่ภายหลัง'
              );
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
      <View style={{ flex: 1, backgroundColor: isDark ? '#0F172A' : '#F9FAFB' }}>
        <StatusBar barStyle="light-content" backgroundColor={isDark ? '#0F172A' : '#3B82F6'} />
        <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 24 }}>
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
        </View>
      </View>
    );
  }

  // Loading
  if (isLoading) {
    return (
      <View style={{ flex: 1, backgroundColor: isDark ? '#0F172A' : '#F9FAFB' }}>
        <StatusBar barStyle="light-content" backgroundColor={isDark ? '#0F172A' : '#3B82F6'} />
        <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text className="text-gray-500 mt-4">กำลังโหลดข้อมูลงาน...</Text>
        </View>
      </View>
    );
  }

  // Error state
  if (hasError) {
    return (
      <View style={{ flex: 1, backgroundColor: isDark ? '#0F172A' : '#F9FAFB' }}>
        <StatusBar barStyle="light-content" backgroundColor={isDark ? '#0F172A' : '#3B82F6'} />
        <View style={{ flex: 1 }}>
          <View style={{ flexDirection: 'row', alignItems: 'center', paddingHorizontal: 20, paddingTop: 50, paddingBottom: 8 }}>
            <Pressable onPress={() => router.back()} className="mr-4">
              <Text style={{ fontSize: 24, color: isDark ? '#fff' : '#000' }}>←</Text>
            </Pressable>
            <Text className={`text-xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
              งานปัจจุบัน
            </Text>
          </View>
          <ErrorState
            title="ไม่สามารถเชื่อมต่อได้"
            message="ขณะนี้ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้\nโปรดลองใหม่ภายหลัง"
            onRetry={loadJob}
          />
        </View>
      </View>
    );
  }

  if (!job) {
    return (
      <View style={{ flex: 1, backgroundColor: isDark ? '#0F172A' : '#F9FAFB' }}>
        <StatusBar barStyle="light-content" backgroundColor={isDark ? '#0F172A' : '#3B82F6'} />
        <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 24 }}>
          <Text style={{ fontSize: 80, color: '#9CA3AF' }}>📦</Text>
          <Text className="text-xl font-bold mt-4 text-gray-800 dark:text-white">
            ไม่มีงาน
          </Text>
          <Pressable
            onPress={() => router.back()}
            className="bg-primary-500 px-8 py-3 rounded-xl mt-6"
          >
            <Text className="text-white font-bold">กลับ</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  const nextAction = getNextAction();

  return (
    <View style={{ flex: 1, backgroundColor: isDark ? '#0F172A' : '#F9FAFB' }}>
      <StatusBar barStyle="light-content" backgroundColor={isDark ? '#0F172A' : '#3B82F6'} />
      <View style={{ flex: 1 }}>
        {/* Header */}
        <View style={{ flexDirection: 'row', alignItems: 'center', paddingHorizontal: 20, paddingTop: 50, paddingBottom: 8 }}>
          <Pressable onPress={() => router.back()} className="mr-4">
            <Text style={{ fontSize: 24, color: isDark ? '#fff' : '#000' }}>←</Text>
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
                GPS ทำงาน
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
              <View
                className={`flex-1 h-0.5 mx-1 ${
                  currentStepIndex > 0 ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-600'
                }`}
              />
              <StatusStep
                step={2}
                label="รับแล้ว"
                isActive={job.status === 'picked_up'}
                isCompleted={currentStepIndex > 1}
              />
              <View
                className={`flex-1 h-0.5 mx-1 ${
                  currentStepIndex > 1 ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-600'
                }`}
              />
              <StatusStep
                step={3}
                label="กำลังส่ง"
                isActive={job.status === 'delivering'}
                isCompleted={currentStepIndex > 2}
              />
              <View
                className={`flex-1 h-0.5 mx-1 ${
                  currentStepIndex > 2 ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-600'
                }`}
              />
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
          </Animated.View>

          {/* Fee Breakdown */}
          <FeeBreakdownCard job={job} />

          {/* Pickup Location - ⭐ เพิ่ม null checks */}
          {job?.pickup && (
            <LocationCard
              type="pickup"
              location={job.pickup}
              currentLocation={currentLocation}
              onNavigate={() => navigateTo(job.pickup?.latitude ?? 0, job.pickup?.longitude ?? 0)}
              onCall={() => job.pickup?.contactPhone && callContact(job.pickup.contactPhone)}
            />
          )}

          {/* Delivery Location - ⭐ เพิ่ม null checks */}
          {job?.delivery && (
            <LocationCard
              type="delivery"
              location={job.delivery}
              currentLocation={currentLocation}
              onNavigate={() => navigateTo(job.delivery?.latitude ?? 0, job.delivery?.longitude ?? 0)}
              onCall={() => job.delivery?.contactPhone && callContact(job.delivery.contactPhone)}
            />
          )}

          {/* Current GPS Location */}
          {currentLocation && (
            <Animated.View
              entering={FadeInDown.delay(225).springify()}
              className="bg-blue-50 dark:bg-blue-900/30 rounded-2xl p-4 flex-row items-center"
            >
              <View className="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-800/50 items-center justify-center mr-3">
                <Text style={{ fontSize: 24, color: '#3B82F6' }}>🧭</Text>
              </View>
              <View className="flex-1">
                <Text className="text-blue-700 dark:text-blue-300 font-medium">
                  ตำแหน่งปัจจุบันของคุณ
                </Text>
                <Text className="text-blue-600 dark:text-blue-400 text-sm">
                  {currentLocation.latitude.toFixed(6)}, {currentLocation.longitude.toFixed(6)}
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
              <Text style={{ fontSize: 24, color: '#EF4444' }}>✕</Text>
            </Pressable>

            {nextAction && (
              <Pressable
                onPress={nextAction.onPress}
                disabled={isUpdating}
                className={`flex-1 rounded-xl overflow-hidden ${isUpdating ? 'opacity-50' : ''}`}
              >
                <LinearGradient
                  colors={nextAction.color}
                  style={{
                    paddingVertical: 16,
                    flexDirection: 'row',
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  {isUpdating ? (
                    <ActivityIndicator color="white" />
                  ) : (
                    <>
                      <Text style={{ fontSize: 20, color: 'white' }}>✓</Text>
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
      </View>

      {/* Photo Proof Modal */}
      <PhotoProofModal
        visible={showProofModal}
        onClose={() => setShowProofModal(false)}
        onConfirm={(proofData) => handleUpdateStatus('completed', proofData)}
        isLoading={isUpdating}
      />
    </View>
  );
}
