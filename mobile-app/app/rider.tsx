/**
 * Rider Screen - หน้าสมัครและจัดการไรเดอร์
 * รองรับการขออนุญาตตามมาตรฐาน Google:
 * - GPS/Location - แชร์ตำแหน่งเฉพาะเมื่อรับงาน
 * - Camera - ถ่ายรูปเอกสารและหลักฐานการส่ง
 * - Microphone - สำหรับการติดต่อลูกค้า
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  TextInput,
  ActivityIndicator,
  Modal,
  Platform,
  Linking,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import * as Location from 'expo-location';
import * as ImagePicker from 'expo-image-picker';
import { Audio } from 'expo-av';
import Animated, { FadeInDown, FadeInUp } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import {
  getRiderStatus,
  registerRider,
  uploadRiderDocument,
  updateRiderPermissions,
  setRiderAvailability,
  updateRiderLocation,
} from '@/services/api';
import { formatCurrency } from '@/constants';
import {
  startTracking,
  stopTracking,
  isTrackingLocation,
  getCurrentLocation,
} from '@/services/location';

// =====================================================
// Permission Request Modal Component
// =====================================================

interface PermissionModalProps {
  visible: boolean;
  onClose: () => void;
  onGrant: () => void;
  permissionType: 'location' | 'camera' | 'microphone';
  isLoading?: boolean;
}

const PermissionModal = ({
  visible,
  onClose,
  onGrant,
  permissionType,
  isLoading,
}: PermissionModalProps) => {
  const permissionInfo = {
    location: {
      icon: 'location',
      title: 'การเข้าถึงตำแหน่ง',
      description:
        'แอปจะขอเข้าถึงตำแหน่งของคุณเพื่อ:\n\n• แสดงตำแหน่งของคุณให้ลูกค้าเห็นเมื่อคุณรับงาน\n• ช่วยค้นหางานที่อยู่ใกล้คุณ\n• คำนวณระยะทางและเวลาในการส่ง',
      warning:
        '⚠️ ตำแหน่งจะถูกแชร์เฉพาะเมื่อคุณรับงานและกำลังจัดส่งเท่านั้น ไม่แชร์ตลอดเวลา',
      color: '#3B82F6',
    },
    camera: {
      icon: 'camera',
      title: 'การเข้าถึงกล้อง',
      description:
        'แอปจะขอเข้าถึงกล้องของคุณเพื่อ:\n\n• ถ่ายรูปบัตรประชาชนสำหรับยืนยันตัวตน\n• ถ่ายรูปใบขับขี่และทะเบียนรถ\n• ถ่ายรูปหลักฐานการรับ-ส่งของ',
      warning: '📷 รูปภาพจะใช้เฉพาะในการยืนยันตัวตนและบันทึกหลักฐานการทำงาน',
      color: '#10B981',
    },
    microphone: {
      icon: 'mic',
      title: 'การเข้าถึงไมโครโฟน',
      description:
        'แอปจะขอเข้าถึงไมโครโฟนของคุณเพื่อ:\n\n• โทรหาลูกค้าผ่านแอป\n• บันทึกเสียงสำหรับรายงานปัญหา\n• การสื่อสารกับทีมสนับสนุน',
      warning: '🎤 ไมโครโฟนจะทำงานเฉพาะเมื่อคุณใช้ฟีเจอร์โทรหรือบันทึกเสียง',
      color: '#8B5CF6',
    },
  };

  const info = permissionInfo[permissionType];

  return (
    <Modal visible={visible} transparent animationType="fade">
      <View className="flex-1 bg-black/60 justify-center items-center px-6">
        <Animated.View
          entering={FadeInUp.springify()}
          className="bg-white dark:bg-gray-800 rounded-3xl w-full max-w-md overflow-hidden"
        >
          {/* Header */}
          <LinearGradient
            colors={[info.color, info.color + 'CC']}
            style={{ padding: 24, alignItems: 'center' }}
          >
            <View className="w-20 h-20 bg-white/20 rounded-full items-center justify-center mb-4">
              <Ionicons
                name={info.icon as keyof typeof Ionicons.glyphMap}
                size={40}
                color="white"
              />
            </View>
            <Text className="text-white text-xl font-bold text-center">
              {info.title}
            </Text>
          </LinearGradient>

          {/* Content */}
          <View className="p-6">
            <Text className="text-gray-700 dark:text-gray-300 text-base leading-6">
              {info.description}
            </Text>

            <View className="mt-4 bg-yellow-50 dark:bg-yellow-900/30 rounded-xl p-4">
              <Text className="text-yellow-800 dark:text-yellow-200 text-sm">
                {info.warning}
              </Text>
            </View>
          </View>

          {/* Actions */}
          <View className="flex-row p-4 border-t border-gray-200 dark:border-gray-700">
            <Pressable
              onPress={onClose}
              className="flex-1 py-3 mr-2 bg-gray-100 dark:bg-gray-700 rounded-xl"
            >
              <Text className="text-gray-700 dark:text-gray-300 text-center font-medium">
                ภายหลัง
              </Text>
            </Pressable>
            <Pressable
              onPress={onGrant}
              disabled={isLoading}
              className="flex-1 py-3 ml-2 rounded-xl"
              style={{ backgroundColor: info.color }}
            >
              {isLoading ? (
                <ActivityIndicator color="white" />
              ) : (
                <Text className="text-white text-center font-bold">อนุญาต</Text>
              )}
            </Pressable>
          </View>
        </Animated.View>
      </View>
    </Modal>
  );
};

// =====================================================
// Main Rider Screen
// =====================================================

export default function RiderScreen() {
  const { isAuthenticated, user } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  // Rider state
  const [riderData, setRiderData] = useState<{
    isRider: boolean;
    riderId?: number;
    status?: string;
    statusText?: string;
    availability?: string;
    availabilityText?: string;
    permissions?: {
      gps: boolean;
      camera: boolean;
      microphone: boolean;
      notification: boolean;
      allGranted: boolean;
    };
    rating?: number;
    totalJobs?: number;
    completedJobs?: number;
    totalEarnings?: number;
    rejectionReason?: string;
  } | null>(null);

  // Form state
  const [showRegisterForm, setShowRegisterForm] = useState(false);
  const [formData, setFormData] = useState({
    full_name: user?.name || '',
    phone: '',
    vehicle_type: 'motorcycle' as 'motorcycle' | 'car' | 'bicycle' | 'walk',
    vehicle_plate: '',
  });

  // Permission modal state
  const [permissionModal, setPermissionModal] = useState<{
    visible: boolean;
    type: 'location' | 'camera' | 'microphone';
  }>({
    visible: false,
    type: 'location',
  });

  // Loading states
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isGrantingPermission, setIsGrantingPermission] = useState(false);
  const [isTogglingAvailability, setIsTogglingAvailability] = useState(false);

  // Location sharing state
  const [isLocationSharing, setIsLocationSharing] = useState(false);
  const [isTogglingLocationShare, setIsTogglingLocationShare] = useState(false);

  // =====================================================
  // Load Rider Status
  // =====================================================

  const loadRiderStatus = useCallback(async () => {
    if (!isAuthenticated) return;

    setIsLoading(true);
    try {
      const response = await getRiderStatus();
      if (response?.success && response.data) {
        setRiderData(response.data);
      }
    } catch (error) {
      console.error('Load rider status error:', error);
    } finally {
      setIsLoading(false);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    loadRiderStatus();
  }, [loadRiderStatus]);

  // =====================================================
  // Permission Handlers
  // =====================================================

  const requestLocationPermission = async () => {
    setIsGrantingPermission(true);
    try {
      const { status: foregroundStatus } =
        await Location.requestForegroundPermissionsAsync();

      if (foregroundStatus === 'granted') {
        // Request background permission for tracking while delivering
        const { status: backgroundStatus } =
          await Location.requestBackgroundPermissionsAsync();

        const granted = backgroundStatus === 'granted';

        // Save to server
        await updateRiderPermissions({ gps: granted });
        await loadRiderStatus();

        if (granted) {
          Alert.alert('สำเร็จ', 'อนุญาตการเข้าถึงตำแหน่งเรียบร้อย');
        } else {
          Alert.alert(
            'คำเตือน',
            'คุณอนุญาตเฉพาะการเข้าถึงตำแหน่งขณะใช้งานแอป การติดตามตำแหน่งขณะจัดส่งอาจไม่ทำงานเต็มประสิทธิภาพ'
          );
        }
      } else {
        Alert.alert(
          'ไม่ได้รับอนุญาต',
          'คุณต้องอนุญาตการเข้าถึงตำแหน่งเพื่อเป็นไรเดอร์',
          [
            { text: 'ยกเลิก', style: 'cancel' },
            {
              text: 'ไปตั้งค่า',
              onPress: () => Linking.openSettings(),
            },
          ]
        );
      }
    } catch (error) {
      console.error('Location permission error:', error);
      Alert.alert('ข้อผิดพลาด', 'ไม่สามารถขอสิทธิ์การเข้าถึงตำแหน่งได้');
    } finally {
      setIsGrantingPermission(false);
      setPermissionModal({ visible: false, type: 'location' });
    }
  };

  const requestCameraPermission = async () => {
    setIsGrantingPermission(true);
    try {
      const { status } = await ImagePicker.requestCameraPermissionsAsync();
      const granted = status === 'granted';

      await updateRiderPermissions({ camera: granted });
      await loadRiderStatus();

      if (granted) {
        Alert.alert('สำเร็จ', 'อนุญาตการเข้าถึงกล้องเรียบร้อย');
      } else {
        Alert.alert(
          'ไม่ได้รับอนุญาต',
          'คุณต้องอนุญาตการเข้าถึงกล้องเพื่อถ่ายรูปเอกสารและหลักฐาน',
          [
            { text: 'ยกเลิก', style: 'cancel' },
            {
              text: 'ไปตั้งค่า',
              onPress: () => Linking.openSettings(),
            },
          ]
        );
      }
    } catch (error) {
      console.error('Camera permission error:', error);
      Alert.alert('ข้อผิดพลาด', 'ไม่สามารถขอสิทธิ์การเข้าถึงกล้องได้');
    } finally {
      setIsGrantingPermission(false);
      setPermissionModal({ visible: false, type: 'camera' });
    }
  };

  const requestMicrophonePermission = async () => {
    setIsGrantingPermission(true);
    try {
      const { status } = await Audio.requestPermissionsAsync();
      const granted = status === 'granted';

      await updateRiderPermissions({ microphone: granted });
      await loadRiderStatus();

      if (granted) {
        Alert.alert('สำเร็จ', 'อนุญาตการเข้าถึงไมโครโฟนเรียบร้อย');
      } else {
        Alert.alert(
          'ไม่ได้รับอนุญาต',
          'คุณต้องอนุญาตการเข้าถึงไมโครโฟนเพื่อโทรหาลูกค้า',
          [
            { text: 'ยกเลิก', style: 'cancel' },
            {
              text: 'ไปตั้งค่า',
              onPress: () => Linking.openSettings(),
            },
          ]
        );
      }
    } catch (error) {
      console.error('Microphone permission error:', error);
      Alert.alert('ข้อผิดพลาด', 'ไม่สามารถขอสิทธิ์การเข้าถึงไมโครโฟนได้');
    } finally {
      setIsGrantingPermission(false);
      setPermissionModal({ visible: false, type: 'microphone' });
    }
  };

  const handleGrantPermission = async () => {
    switch (permissionModal.type) {
      case 'location':
        await requestLocationPermission();
        break;
      case 'camera':
        await requestCameraPermission();
        break;
      case 'microphone':
        await requestMicrophonePermission();
        break;
    }
  };

  // =====================================================
  // Registration Handler
  // =====================================================

  const handleRegister = async () => {
    if (!formData.full_name.trim()) {
      Alert.alert('ข้อผิดพลาด', 'กรุณากรอกชื่อ-นามสกุล');
      return;
    }
    if (!formData.phone.trim()) {
      Alert.alert('ข้อผิดพลาด', 'กรุณากรอกเบอร์โทรศัพท์');
      return;
    }

    setIsSubmitting(true);
    try {
      const response = await registerRider(formData);
      if (response.success) {
        Alert.alert('สำเร็จ', response.message || 'สมัครเป็นไรเดอร์สำเร็จ');
        setShowRegisterForm(false);
        await loadRiderStatus();
      } else {
        Alert.alert('ข้อผิดพลาด', response.message || 'สมัครไรเดอร์ไม่สำเร็จ');
      }
    } catch (error) {
      console.error('Register rider error:', error);
      Alert.alert('ข้อผิดพลาด', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
    } finally {
      setIsSubmitting(false);
    }
  };

  // =====================================================
  // Availability Toggle
  // =====================================================

  const toggleAvailability = async () => {
    if (!riderData?.permissions?.gps) {
      Alert.alert(
        'ต้องอนุญาตตำแหน่งก่อน',
        'คุณต้องอนุญาตการเข้าถึงตำแหน่งก่อนเปิดรับงาน',
        [
          { text: 'ยกเลิก', style: 'cancel' },
          {
            text: 'อนุญาต',
            onPress: () =>
              setPermissionModal({ visible: true, type: 'location' }),
          },
        ]
      );
      return;
    }

    setIsTogglingAvailability(true);
    try {
      const newAvailability =
        riderData?.availability === 'online' ? 'offline' : 'online';
      const response = await setRiderAvailability(newAvailability);

      if (response.success) {
        await loadRiderStatus();

        if (newAvailability === 'online') {
          Alert.alert(
            '🟢 เปิดรับงานแล้ว',
            'ระบบจะแจ้งเตือนเมื่อมีงานใกล้คุณ\n\n⚠️ ตำแหน่งของคุณจะถูกแชร์เมื่อคุณรับงานเท่านั้น'
          );
        }
      } else {
        Alert.alert('ข้อผิดพลาด', response.message || 'ไม่สามารถเปลี่ยนสถานะได้');
      }
    } catch (error) {
      console.error('Toggle availability error:', error);
      Alert.alert('ข้อผิดพลาด', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
    } finally {
      setIsTogglingAvailability(false);
    }
  };

  // =====================================================
  // Location Sharing Toggle
  // =====================================================

  // Check tracking status on mount
  useEffect(() => {
    setIsLocationSharing(isTrackingLocation());
  }, []);

  const toggleLocationSharing = async () => {
    // ตรวจสอบสิทธิ์ GPS ก่อน
    if (!riderData?.permissions?.gps) {
      Alert.alert(
        'ต้องอนุญาตตำแหน่งก่อน',
        'คุณต้องอนุญาตการเข้าถึงตำแหน่งก่อนแชร์ตำแหน่ง',
        [
          { text: 'ยกเลิก', style: 'cancel' },
          {
            text: 'อนุญาต',
            onPress: () =>
              setPermissionModal({ visible: true, type: 'location' }),
          },
        ]
      );
      return;
    }

    setIsTogglingLocationShare(true);
    try {
      if (isLocationSharing) {
        // หยุดแชร์ตำแหน่ง
        await stopTracking();
        setIsLocationSharing(false);
        Alert.alert('หยุดแชร์ตำแหน่งแล้ว', 'ตำแหน่งของคุณจะไม่ถูกส่งไปยังระบบ');
      } else {
        // เริ่มแชร์ตำแหน่ง
        const jobId = 0; // ใช้ 0 สำหรับการแชร์ตำแหน่งแบบ manual
        const success = await startTracking(jobId);

        if (success) {
          setIsLocationSharing(true);

          // ส่งตำแหน่งปัจจุบันไป server ทันที
          const location = await getCurrentLocation();
          if (location) {
            await updateRiderLocation({
              latitude: location.latitude,
              longitude: location.longitude,
              accuracy: location.accuracy,
            });
          }

          Alert.alert(
            '🟢 เริ่มแชร์ตำแหน่งแล้ว',
            'ตำแหน่งของคุณกำลังถูกส่งไปยังระบบ\n\nแอดมินสามารถเห็นตำแหน่งของคุณในหน้า GPS Monitor'
          );
        } else {
          Alert.alert('ไม่สามารถเริ่มแชร์ตำแหน่งได้', 'กรุณาตรวจสอบการอนุญาตตำแหน่ง');
        }
      }
    } catch (error) {
      console.error('Toggle location sharing error:', error);
      Alert.alert('ข้อผิดพลาด', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
    } finally {
      setIsTogglingLocationShare(false);
    }
  };

  // =====================================================
  // Render: Not Authenticated
  // =====================================================

  if (!isAuthenticated) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <Ionicons
            name="bicycle"
            size={80}
            color={isDark ? '#4B5563' : '#9CA3AF'}
          />
          <Text
            className={`text-xl font-bold mt-4 ${isDark ? 'text-white' : 'text-gray-800'}`}
          >
            สมัครเป็นไรเดอร์
          </Text>
          <Text className="text-gray-500 text-center mt-2">
            เข้าสู่ระบบเพื่อสมัครเป็นไรเดอร์และรับงาน
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

  // =====================================================
  // Render: Loading
  // =====================================================

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

  // =====================================================
  // Render: Not a Rider - Show Registration
  // =====================================================

  if (!riderData?.isRider) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1">
          <ScrollView className="flex-1 px-5 pt-4">
            {/* Header */}
            <View className="flex-row items-center mb-6">
              <Pressable onPress={() => router.back()} className="mr-4">
                <Ionicons
                  name="arrow-back"
                  size={24}
                  color={isDark ? '#fff' : '#000'}
                />
              </Pressable>
              <Text
                className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}
              >
                สมัครเป็นไรเดอร์
              </Text>
            </View>

            {/* Benefits */}
            <Animated.View entering={FadeInDown.delay(100).springify()}>
              <LinearGradient
                colors={['#06B6D4', '#0891B2']}
                style={{ borderRadius: 16, padding: 24, marginBottom: 24 }}
              >
                <Text className="text-white text-xl font-bold mb-4">
                  🚴 ทำไมต้องเป็นไรเดอร์กับเรา?
                </Text>
                <View className="space-y-3">
                  <View className="flex-row items-center">
                    <Ionicons name="checkmark-circle" size={20} color="#fff" />
                    <Text className="text-white ml-2">รายได้ดี คิดค่าบริการยุติธรรม</Text>
                  </View>
                  <View className="flex-row items-center">
                    <Ionicons name="checkmark-circle" size={20} color="#fff" />
                    <Text className="text-white ml-2">เวลาทำงานยืดหยุ่น</Text>
                  </View>
                  <View className="flex-row items-center">
                    <Ionicons name="checkmark-circle" size={20} color="#fff" />
                    <Text className="text-white ml-2">ถอนเงินได้ทันที</Text>
                  </View>
                  <View className="flex-row items-center">
                    <Ionicons name="checkmark-circle" size={20} color="#fff" />
                    <Text className="text-white ml-2">ประกันอุบัติเหตุ</Text>
                  </View>
                </View>
              </LinearGradient>
            </Animated.View>

            {/* Permission Notice */}
            <Animated.View
              entering={FadeInDown.delay(200).springify()}
              className="bg-yellow-50 dark:bg-yellow-900/30 rounded-xl p-4 mb-6"
            >
              <Text className="text-yellow-800 dark:text-yellow-200 font-bold mb-2">
                ⚠️ แอปจะขอสิทธิ์ดังนี้:
              </Text>
              <View className="space-y-2">
                <Text className="text-yellow-700 dark:text-yellow-300 text-sm">
                  📍 ตำแหน่ง - แชร์เฉพาะเมื่อรับงานเพื่อให้ลูกค้าติดตามได้
                </Text>
                <Text className="text-yellow-700 dark:text-yellow-300 text-sm">
                  📷 กล้อง - ถ่ายรูปเอกสารและหลักฐานการส่ง
                </Text>
                <Text className="text-yellow-700 dark:text-yellow-300 text-sm">
                  🎤 ไมโครโฟน - โทรหาลูกค้าผ่านแอป
                </Text>
              </View>
            </Animated.View>

            {/* Register Form */}
            {showRegisterForm ? (
              <Animated.View
                entering={FadeInDown.springify()}
                className="bg-white dark:bg-gray-800 rounded-2xl p-5 mb-6"
              >
                <Text
                  className={`text-lg font-bold mb-4 ${isDark ? 'text-white' : 'text-gray-800'}`}
                >
                  กรอกข้อมูลสมัคร
                </Text>

                {/* Full Name */}
                <View className="mb-4">
                  <Text className="text-gray-600 dark:text-gray-400 mb-1">
                    ชื่อ-นามสกุล *
                  </Text>
                  <TextInput
                    value={formData.full_name}
                    onChangeText={(text) =>
                      setFormData({ ...formData, full_name: text })
                    }
                    placeholder="ชื่อจริง นามสกุล"
                    placeholderTextColor="#9CA3AF"
                    className="bg-gray-100 dark:bg-gray-700 rounded-xl px-4 py-3 text-gray-900 dark:text-white"
                  />
                </View>

                {/* Phone */}
                <View className="mb-4">
                  <Text className="text-gray-600 dark:text-gray-400 mb-1">
                    เบอร์โทรศัพท์ *
                  </Text>
                  <TextInput
                    value={formData.phone}
                    onChangeText={(text) =>
                      setFormData({ ...formData, phone: text })
                    }
                    placeholder="08X-XXX-XXXX"
                    placeholderTextColor="#9CA3AF"
                    keyboardType="phone-pad"
                    className="bg-gray-100 dark:bg-gray-700 rounded-xl px-4 py-3 text-gray-900 dark:text-white"
                  />
                </View>

                {/* Vehicle Type */}
                <View className="mb-4">
                  <Text className="text-gray-600 dark:text-gray-400 mb-2">
                    ประเภทยานพาหนะ *
                  </Text>
                  <View className="flex-row flex-wrap">
                    {[
                      { value: 'motorcycle', label: '🏍️ มอเตอร์ไซค์' },
                      { value: 'car', label: '🚗 รถยนต์' },
                      { value: 'bicycle', label: '🚲 จักรยาน' },
                      { value: 'walk', label: '🚶 เดินเท้า' },
                    ].map((option) => (
                      <Pressable
                        key={option.value}
                        onPress={() =>
                          setFormData({
                            ...formData,
                            vehicle_type: option.value as typeof formData.vehicle_type,
                          })
                        }
                        className={`mr-2 mb-2 px-4 py-2 rounded-xl ${
                          formData.vehicle_type === option.value
                            ? 'bg-primary-500'
                            : 'bg-gray-100 dark:bg-gray-700'
                        }`}
                      >
                        <Text
                          className={
                            formData.vehicle_type === option.value
                              ? 'text-white font-medium'
                              : 'text-gray-700 dark:text-gray-300'
                          }
                        >
                          {option.label}
                        </Text>
                      </Pressable>
                    ))}
                  </View>
                </View>

                {/* Vehicle Plate */}
                {formData.vehicle_type !== 'walk' && (
                  <View className="mb-6">
                    <Text className="text-gray-600 dark:text-gray-400 mb-1">
                      ทะเบียนรถ
                    </Text>
                    <TextInput
                      value={formData.vehicle_plate}
                      onChangeText={(text) =>
                        setFormData({ ...formData, vehicle_plate: text })
                      }
                      placeholder="กข 1234"
                      placeholderTextColor="#9CA3AF"
                      className="bg-gray-100 dark:bg-gray-700 rounded-xl px-4 py-3 text-gray-900 dark:text-white"
                    />
                  </View>
                )}

                {/* Submit Button */}
                <Pressable
                  onPress={handleRegister}
                  disabled={isSubmitting}
                  className={`bg-primary-500 rounded-xl py-4 ${isSubmitting ? 'opacity-60' : ''}`}
                >
                  {isSubmitting ? (
                    <ActivityIndicator color="white" />
                  ) : (
                    <Text className="text-white text-center font-bold text-lg">
                      สมัครเป็นไรเดอร์
                    </Text>
                  )}
                </Pressable>
              </Animated.View>
            ) : (
              <Animated.View entering={FadeInDown.delay(300).springify()}>
                <Pressable
                  onPress={() => setShowRegisterForm(true)}
                  className="bg-primary-500 rounded-xl py-4"
                >
                  <Text className="text-white text-center font-bold text-lg">
                    สมัครเลย
                  </Text>
                </Pressable>
              </Animated.View>
            )}

            <View className="h-10" />
          </ScrollView>
        </SafeAreaView>
      </View>
    );
  }

  // =====================================================
  // Render: Rider Dashboard
  // =====================================================

  const isApproved = riderData.status === 'approved';
  const isPending = riderData.status === 'pending';
  const isRejected = riderData.status === 'rejected';
  const isOnline = riderData.availability === 'online';

  return (
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
      <SafeAreaView className="flex-1">
        <ScrollView className="flex-1 px-5 pt-4">
          {/* Header */}
          <View className="flex-row items-center justify-between mb-6">
            <View className="flex-row items-center">
              <Pressable onPress={() => router.back()} className="mr-4">
                <Ionicons
                  name="arrow-back"
                  size={24}
                  color={isDark ? '#fff' : '#000'}
                />
              </Pressable>
              <Text
                className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}
              >
                ไรเดอร์
              </Text>
            </View>
            {isApproved && (
              <View
                className={`px-3 py-1 rounded-full ${isOnline ? 'bg-green-100' : 'bg-gray-100'}`}
              >
                <Text
                  className={`text-sm font-medium ${isOnline ? 'text-green-600' : 'text-gray-600'}`}
                >
                  {isOnline ? '🟢 ออนไลน์' : '⚫ ออฟไลน์'}
                </Text>
              </View>
            )}
          </View>

          {/* Status Card */}
          <Animated.View entering={FadeInDown.delay(100).springify()}>
            <LinearGradient
              colors={
                isPending
                  ? ['#F59E0B', '#D97706']
                  : isRejected
                    ? ['#EF4444', '#DC2626']
                    : ['#10B981', '#059669']
              }
              style={{ borderRadius: 16, padding: 24, marginBottom: 24 }}
            >
              <View className="flex-row items-center mb-4">
                <Ionicons
                  name={
                    isPending
                      ? 'time'
                      : isRejected
                        ? 'close-circle'
                        : 'checkmark-circle'
                  }
                  size={32}
                  color="white"
                />
                <View className="ml-3">
                  <Text className="text-white text-lg font-bold">
                    {riderData.statusText}
                  </Text>
                  <Text className="text-white/80 text-sm">
                    รหัสไรเดอร์: #{riderData.riderId}
                  </Text>
                </View>
              </View>

              {isPending && (
                <View>
                  <Text className="text-white/90 mb-3">
                    เอกสารของคุณกำลังได้รับการตรวจสอบ โดยปกติใช้เวลา 1-3 วันทำการ
                  </Text>
                  <Pressable
                    onPress={() => router.push('/rider-documents')}
                    className="bg-white/20 rounded-xl py-2 px-4 self-start"
                  >
                    <Text className="text-white font-medium">📄 ดูเอกสารที่ส่ง</Text>
                  </Pressable>
                </View>
              )}

              {isRejected && (
                <View>
                  {riderData.rejectionReason && (
                    <Text className="text-white/90 mb-3">
                      เหตุผล: {riderData.rejectionReason}
                    </Text>
                  )}
                  <Pressable
                    onPress={() => router.push('/rider-documents')}
                    className="bg-white/20 rounded-xl py-2 px-4 self-start"
                  >
                    <Text className="text-white font-medium">📄 อัพโหลดเอกสารใหม่</Text>
                  </Pressable>
                </View>
              )}

              {isApproved && (
                <View className="flex-row">
                  <View className="flex-1">
                    <Text className="text-white/70 text-sm">งานทั้งหมด</Text>
                    <Text className="text-white font-bold text-xl">
                      {riderData.totalJobs || 0}
                    </Text>
                  </View>
                  <View className="flex-1">
                    <Text className="text-white/70 text-sm">เสร็จสิ้น</Text>
                    <Text className="text-white font-bold text-xl">
                      {riderData.completedJobs || 0}
                    </Text>
                  </View>
                  <View className="flex-1">
                    <Text className="text-white/70 text-sm">รายได้รวม</Text>
                    <Text className="text-white font-bold text-xl">
                      {formatCurrency(riderData.totalEarnings || 0)}
                    </Text>
                  </View>
                </View>
              )}
            </LinearGradient>
          </Animated.View>

          {/* Permission Status */}
          <Animated.View
            entering={FadeInDown.delay(200).springify()}
            className="bg-white dark:bg-gray-800 rounded-2xl p-5 mb-6"
          >
            <Text
              className={`text-lg font-bold mb-4 ${isDark ? 'text-white' : 'text-gray-800'}`}
            >
              สถานะสิทธิ์การใช้งาน
            </Text>

            {/* Location */}
            <Pressable
              onPress={() =>
                !riderData.permissions?.gps &&
                setPermissionModal({ visible: true, type: 'location' })
              }
              className="flex-row items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700"
            >
              <View className="flex-row items-center">
                <View
                  className={`w-10 h-10 rounded-full items-center justify-center ${
                    riderData.permissions?.gps ? 'bg-green-100' : 'bg-red-100'
                  }`}
                >
                  <Ionicons
                    name="location"
                    size={20}
                    color={riderData.permissions?.gps ? '#10B981' : '#EF4444'}
                  />
                </View>
                <View className="ml-3">
                  <Text className="text-gray-800 dark:text-white font-medium">
                    ตำแหน่ง (GPS)
                  </Text>
                  <Text className="text-gray-500 text-xs">
                    แชร์เฉพาะเมื่อรับงาน
                  </Text>
                </View>
              </View>
              {riderData.permissions?.gps ? (
                <Ionicons name="checkmark-circle" size={24} color="#10B981" />
              ) : (
                <Text className="text-primary-500 font-medium">อนุญาต</Text>
              )}
            </Pressable>

            {/* Camera */}
            <Pressable
              onPress={() =>
                !riderData.permissions?.camera &&
                setPermissionModal({ visible: true, type: 'camera' })
              }
              className="flex-row items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700"
            >
              <View className="flex-row items-center">
                <View
                  className={`w-10 h-10 rounded-full items-center justify-center ${
                    riderData.permissions?.camera ? 'bg-green-100' : 'bg-red-100'
                  }`}
                >
                  <Ionicons
                    name="camera"
                    size={20}
                    color={riderData.permissions?.camera ? '#10B981' : '#EF4444'}
                  />
                </View>
                <View className="ml-3">
                  <Text className="text-gray-800 dark:text-white font-medium">
                    กล้อง
                  </Text>
                  <Text className="text-gray-500 text-xs">
                    ถ่ายรูปเอกสารและหลักฐาน
                  </Text>
                </View>
              </View>
              {riderData.permissions?.camera ? (
                <Ionicons name="checkmark-circle" size={24} color="#10B981" />
              ) : (
                <Text className="text-primary-500 font-medium">อนุญาต</Text>
              )}
            </Pressable>

            {/* Microphone */}
            <Pressable
              onPress={() =>
                !riderData.permissions?.microphone &&
                setPermissionModal({ visible: true, type: 'microphone' })
              }
              className="flex-row items-center justify-between py-3"
            >
              <View className="flex-row items-center">
                <View
                  className={`w-10 h-10 rounded-full items-center justify-center ${
                    riderData.permissions?.microphone
                      ? 'bg-green-100'
                      : 'bg-red-100'
                  }`}
                >
                  <Ionicons
                    name="mic"
                    size={20}
                    color={
                      riderData.permissions?.microphone ? '#10B981' : '#EF4444'
                    }
                  />
                </View>
                <View className="ml-3">
                  <Text className="text-gray-800 dark:text-white font-medium">
                    ไมโครโฟน
                  </Text>
                  <Text className="text-gray-500 text-xs">
                    โทรหาลูกค้า
                  </Text>
                </View>
              </View>
              {riderData.permissions?.microphone ? (
                <Ionicons name="checkmark-circle" size={24} color="#10B981" />
              ) : (
                <Text className="text-primary-500 font-medium">อนุญาต</Text>
              )}
            </Pressable>
          </Animated.View>

          {/* Quick Actions Menu - สำหรับไรเดอร์ที่ได้รับอนุมัติ */}
          {isApproved && (
            <Animated.View entering={FadeInDown.delay(250).springify()}>
              <Text
                className={`text-lg font-bold mb-3 ${isDark ? 'text-white' : 'text-gray-800'}`}
              >
                เมนูลัด
              </Text>
              <View className="flex-row flex-wrap mb-4">
                {/* ดูงานที่รอรับ */}
                <Pressable
                  onPress={() => router.push('/rider-jobs')}
                  className="w-[48%] mr-[4%] mb-3 bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700"
                >
                  <View className="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 items-center justify-center mb-2">
                    <Ionicons name="list" size={24} color="#3B82F6" />
                  </View>
                  <Text className="text-gray-900 dark:text-white font-bold">งานที่รอรับ</Text>
                  <Text className="text-gray-500 dark:text-gray-400 text-xs">ดูรายการงานใหม่</Text>
                </Pressable>

                {/* งานปัจจุบัน */}
                <Pressable
                  onPress={() => router.push('/rider-job-detail')}
                  className="w-[48%] mb-3 bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700"
                >
                  <View className="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 items-center justify-center mb-2">
                    <Ionicons name="navigate" size={24} color="#10B981" />
                  </View>
                  <Text className="text-gray-900 dark:text-white font-bold">งานปัจจุบัน</Text>
                  <Text className="text-gray-500 dark:text-gray-400 text-xs">ดูงานที่กำลังทำ</Text>
                </Pressable>

                {/* บริการของฉัน */}
                <Pressable
                  onPress={() => router.push('/rider-services')}
                  className="w-[48%] mr-[4%] mb-3 bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700"
                >
                  <View className="w-12 h-12 rounded-xl bg-pink-100 dark:bg-pink-900/30 items-center justify-center mb-2">
                    <Ionicons name="briefcase" size={24} color="#EC4899" />
                  </View>
                  <Text className="text-gray-900 dark:text-white font-bold">บริการของฉัน</Text>
                  <Text className="text-gray-500 dark:text-gray-400 text-xs">เลือกบริการที่ให้</Text>
                </Pressable>

                {/* อัพโหลดเอกสาร */}
                <Pressable
                  onPress={() => router.push('/rider-documents')}
                  className="w-[48%] mb-3 bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700"
                >
                  <View className="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 items-center justify-center mb-2">
                    <Ionicons name="document-text" size={24} color="#8B5CF6" />
                  </View>
                  <Text className="text-gray-900 dark:text-white font-bold">เอกสาร</Text>
                  <Text className="text-gray-500 dark:text-gray-400 text-xs">อัพเดทเอกสาร</Text>
                </Pressable>

                {/* ประวัติงาน */}
                <Pressable
                  onPress={() => Alert.alert('เร็วๆ นี้', 'ฟีเจอร์นี้กำลังพัฒนา')}
                  className="w-[48%] mr-[4%] bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700"
                >
                  <View className="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-900/30 items-center justify-center mb-2">
                    <Ionicons name="time" size={24} color="#F59E0B" />
                  </View>
                  <Text className="text-gray-900 dark:text-white font-bold">ประวัติงาน</Text>
                  <Text className="text-gray-500 dark:text-gray-400 text-xs">ดูงานที่ทำแล้ว</Text>
                </Pressable>

                {/* รายได้ */}
                <Pressable
                  onPress={() => Alert.alert('เร็วๆ นี้', 'ฟีเจอร์นี้กำลังพัฒนา')}
                  className="w-[48%] bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700"
                >
                  <View className="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 items-center justify-center mb-2">
                    <Ionicons name="wallet" size={24} color="#10B981" />
                  </View>
                  <Text className="text-gray-900 dark:text-white font-bold">รายได้</Text>
                  <Text className="text-gray-500 dark:text-gray-400 text-xs">ดูรายได้ทั้งหมด</Text>
                </Pressable>
              </View>
            </Animated.View>
          )}

          {/* Online/Offline Toggle */}
          {isApproved && (
            <Animated.View entering={FadeInDown.delay(300).springify()}>
              <Pressable
                onPress={toggleAvailability}
                disabled={isTogglingAvailability}
                className={`rounded-2xl py-5 ${
                  isOnline ? 'bg-red-500' : 'bg-green-500'
                } ${isTogglingAvailability ? 'opacity-60' : ''}`}
              >
                {isTogglingAvailability ? (
                  <ActivityIndicator color="white" />
                ) : (
                  <Text className="text-white text-center font-bold text-lg">
                    {isOnline ? '🔴 ปิดรับงาน' : '🟢 เปิดรับงาน'}
                  </Text>
                )}
              </Pressable>

              {isOnline && (
                <View className="mt-4 bg-blue-50 dark:bg-blue-900/30 rounded-xl p-4">
                  <Text className="text-blue-800 dark:text-blue-200 text-center text-sm">
                    📍 ตำแหน่งจะถูกแชร์เมื่อคุณรับงานเท่านั้น
                  </Text>
                </View>
              )}
            </Animated.View>
          )}

          {/* Location Sharing Toggle Button */}
          {isApproved && (
            <Animated.View entering={FadeInDown.delay(400).springify()} className="mt-6">
              <Pressable
                onPress={toggleLocationSharing}
                disabled={isTogglingLocationShare}
                className={`rounded-2xl py-5 flex-row items-center justify-center ${
                  isLocationSharing
                    ? 'bg-green-500 border-2 border-green-300'
                    : 'bg-gray-200 dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600'
                } ${isTogglingLocationShare ? 'opacity-60' : ''}`}
              >
                {isTogglingLocationShare ? (
                  <ActivityIndicator color={isLocationSharing ? 'white' : '#3B82F6'} />
                ) : (
                  <>
                    {/* Location Sharing Indicator */}
                    <View
                      className={`w-6 h-6 rounded-full mr-3 items-center justify-center ${
                        isLocationSharing ? 'bg-white/30' : 'bg-gray-400 dark:bg-gray-500'
                      }`}
                    >
                      {isLocationSharing && (
                        <Animated.View
                          className="w-3 h-3 rounded-full bg-white"
                          style={{
                            shadowColor: '#fff',
                            shadowOffset: { width: 0, height: 0 },
                            shadowOpacity: 0.8,
                            shadowRadius: 4,
                          }}
                        />
                      )}
                    </View>

                    <View className="items-center">
                      <Text
                        className={`font-bold text-lg ${
                          isLocationSharing ? 'text-white' : 'text-gray-700 dark:text-gray-300'
                        }`}
                      >
                        {isLocationSharing ? '📍 กำลังแชร์ตำแหน่ง' : '📍 แชร์ตำแหน่ง'}
                      </Text>
                      <Text
                        className={`text-xs mt-1 ${
                          isLocationSharing ? 'text-white/80' : 'text-gray-500'
                        }`}
                      >
                        {isLocationSharing ? 'กดเพื่อหยุดแชร์' : 'กดเพื่อแชร์ตำแหน่งกับแอดมิน'}
                      </Text>
                    </View>

                    {/* Pulsing indicator when sharing */}
                    {isLocationSharing && (
                      <View className="absolute right-4">
                        <View className="w-4 h-4 rounded-full bg-white animate-pulse" />
                      </View>
                    )}
                  </>
                )}
              </Pressable>

              {/* Info text */}
              {isLocationSharing && (
                <View className="mt-3 bg-green-50 dark:bg-green-900/30 rounded-xl p-4">
                  <View className="flex-row items-center">
                    <View className="w-3 h-3 rounded-full bg-green-500 mr-2" />
                    <Text className="text-green-800 dark:text-green-200 text-sm font-medium flex-1">
                      ตำแหน่งของคุณกำลังถูกส่งไปยังระบบ
                    </Text>
                  </View>
                  <Text className="text-green-700 dark:text-green-300 text-xs mt-2">
                    แอดมินสามารถเห็นตำแหน่งของคุณใน GPS Monitor
                  </Text>
                </View>
              )}
            </Animated.View>
          )}

          <View className="h-10" />
        </ScrollView>
      </SafeAreaView>

      {/* Permission Modal */}
      <PermissionModal
        visible={permissionModal.visible}
        onClose={() => setPermissionModal({ ...permissionModal, visible: false })}
        onGrant={handleGrantPermission}
        permissionType={permissionModal.type}
        isLoading={isGrantingPermission}
      />
    </View>
  );
}
