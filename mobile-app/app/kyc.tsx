/**
 * KYC Screen - หน้ายืนยันตัวตน
 * รองรับถ่ายรูปจากกล้องมือถือ
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  Image,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import Animated, { FadeInDown } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { getKycStatus, uploadKycImage, confirmKycSubmission } from '@/services/api';

// KYC Status type
type KycStatus = 'not_submitted' | 'pending' | 'approved' | 'rejected' | 'draft';

export default function KycScreen() {
  const { isAuthenticated, user } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [status, setStatus] = useState<KycStatus>('not_submitted');
  const [idCardImage, setIdCardImage] = useState<string | null>(null);
  const [selfieImage, setSelfieImage] = useState<string | null>(null);
  const [hasIdCard, setHasIdCard] = useState(false);
  const [hasSelfie, setHasSelfie] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [rejectionReason, setRejectionReason] = useState<string | null>(null);

  // โหลดสถานะ KYC
  const loadKycStatus = useCallback(async () => {
    setIsLoading(true);
    try {
      const response = await getKycStatus();
      if (response?.success && response.data) {
        setStatus(response.data.status as KycStatus);
        if (response.data.submission) {
          setHasIdCard(response.data.submission.hasIdCard);
          setHasSelfie(response.data.submission.hasSelfie);
          setRejectionReason(response.data.submission.rejectionReason || null);
        }
      }
    } catch (error) {
      console.error('Load KYC status error:', error);
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    if (isAuthenticated) {
      loadKycStatus();
    }
  }, [isAuthenticated, loadKycStatus]);

  // ขอ permission กล้อง
  const requestCameraPermission = async () => {
    const { status } = await ImagePicker.requestCameraPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert(
        'ต้องการสิทธิ์กล้อง',
        'กรุณาอนุญาตให้แอพใช้กล้องเพื่อถ่ายรูปเอกสาร',
        [{ text: 'ตกลง' }]
      );
      return false;
    }
    return true;
  };

  // ถ่ายรูปหรือเลือกจากแกลเลอรี่
  const pickImage = async (type: 'id_card' | 'selfie') => {
    const title = type === 'id_card' ? 'บัตรประชาชน' : 'รูปถ่ายคู่บัตร';

    Alert.alert(
      `เลือกรูป${title}`,
      'เลือกวิธีการถ่ายรูป',
      [
        {
          text: 'ถ่ายรูป',
          onPress: async () => {
            const hasPermission = await requestCameraPermission();
            if (!hasPermission) return;

            const result = await ImagePicker.launchCameraAsync({
              mediaTypes: ImagePicker.MediaTypeOptions.Images,
              allowsEditing: true,
              aspect: type === 'id_card' ? [16, 10] : [3, 4],
              quality: 0.8,
            });

            if (!result.canceled && result.assets[0]) {
              handleImageSelected(result.assets[0].uri, type);
            }
          },
        },
        {
          text: 'เลือกจากแกลเลอรี่',
          onPress: async () => {
            const result = await ImagePicker.launchImageLibraryAsync({
              mediaTypes: ImagePicker.MediaTypeOptions.Images,
              allowsEditing: true,
              aspect: type === 'id_card' ? [16, 10] : [3, 4],
              quality: 0.8,
            });

            if (!result.canceled && result.assets[0]) {
              handleImageSelected(result.assets[0].uri, type);
            }
          },
        },
        { text: 'ยกเลิก', style: 'cancel' },
      ]
    );
  };

  // จัดการรูปที่เลือก
  const handleImageSelected = async (uri: string, type: 'id_card' | 'selfie') => {
    setIsUploading(true);

    try {
      const response = await uploadKycImage(uri, type);

      if (response.success && response.data) {
        if (type === 'id_card') {
          setIdCardImage(uri);
          setHasIdCard(response.data.hasIdCard);
        } else {
          setSelfieImage(uri);
          setHasSelfie(response.data.hasSelfie);
        }
        Alert.alert('สำเร็จ', 'อัพโหลดรูปภาพเรียบร้อย');
      } else {
        Alert.alert('ผิดพลาด', response.message || 'อัพโหลดไม่สำเร็จ');
      }
    } catch (error) {
      console.error('Upload error:', error);
      Alert.alert('ผิดพลาด', 'เกิดข้อผิดพลาดในการอัพโหลด');
    } finally {
      setIsUploading(false);
    }
  };

  // ส่ง KYC
  const handleSubmitKyc = async () => {
    if (!hasIdCard || !hasSelfie) {
      Alert.alert('ข้อมูลไม่ครบ', 'กรุณาอัพโหลดรูปบัตรประชาชนและรูปถ่ายคู่บัตร');
      return;
    }

    Alert.alert(
      'ยืนยันส่งเอกสาร',
      'คุณต้องการส่งเอกสารยืนยันตัวตนใช่หรือไม่?',
      [
        { text: 'ยกเลิก', style: 'cancel' },
        {
          text: 'ส่งเอกสาร',
          onPress: async () => {
            setIsLoading(true);
            try {
              const response = await confirmKycSubmission();

              if (response.success) {
                setStatus('pending');
                Alert.alert(
                  'ส่งเอกสารสำเร็จ',
                  'เอกสารของคุณจะถูกตรวจสอบภายใน 24-48 ชั่วโมง',
                  [{ text: 'ตกลง', onPress: () => router.back() }]
                );
              } else {
                Alert.alert('ผิดพลาด', response.message || 'ส่งเอกสารไม่สำเร็จ');
              }
            } catch (error) {
              console.error('Submit KYC error:', error);
              Alert.alert('ผิดพลาด', 'เกิดข้อผิดพลาด');
            } finally {
              setIsLoading(false);
            }
          },
        },
      ]
    );
  };

  // ถ้ายังไม่ login
  if (!isAuthenticated) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <Ionicons name="shield-checkmark" size={80} color="#9CA3AF" />
          <Text className={`text-xl font-bold mt-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
            ยืนยันตัวตน (KYC)
          </Text>
          <Text className="text-gray-500 text-center mt-2">
            เข้าสู่ระบบเพื่อยืนยันตัวตน
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

  // แสดงสถานะตาม status
  const renderStatusView = () => {
    switch (status) {
      case 'approved':
        return (
          <Animated.View entering={FadeInDown} className="items-center py-8">
            <View className="w-24 h-24 rounded-full bg-green-100 items-center justify-center mb-4">
              <Ionicons name="checkmark-circle" size={64} color="#10B981" />
            </View>
            <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
              ยืนยันตัวตนสำเร็จ
            </Text>
            <Text className="text-gray-500 mt-2 text-center">
              บัญชีของคุณได้รับการยืนยันแล้ว
            </Text>
          </Animated.View>
        );

      case 'pending':
        return (
          <Animated.View entering={FadeInDown} className="items-center py-8">
            <View className="w-24 h-24 rounded-full bg-yellow-100 items-center justify-center mb-4">
              <Ionicons name="time" size={64} color="#F59E0B" />
            </View>
            <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
              รอการตรวจสอบ
            </Text>
            <Text className="text-gray-500 mt-2 text-center px-8">
              เอกสารของคุณอยู่ระหว่างการตรวจสอบ{'\n'}กรุณารอ 24-48 ชั่วโมง
            </Text>
          </Animated.View>
        );

      case 'rejected':
        return (
          <Animated.View entering={FadeInDown} className="items-center py-8">
            <View className="w-24 h-24 rounded-full bg-red-100 items-center justify-center mb-4">
              <Ionicons name="close-circle" size={64} color="#EF4444" />
            </View>
            <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
              การยืนยันถูกปฏิเสธ
            </Text>
            {rejectionReason && (
              <View className="bg-red-50 dark:bg-red-900/30 rounded-xl p-4 mt-4 mx-4">
                <Text className="text-red-600 dark:text-red-400 text-center">
                  เหตุผล: {rejectionReason}
                </Text>
              </View>
            )}
            <Pressable
              onPress={() => setStatus('not_submitted')}
              className="bg-primary-500 px-8 py-3 rounded-xl mt-6"
            >
              <Text className="text-white font-bold">ส่งเอกสารใหม่</Text>
            </Pressable>
          </Animated.View>
        );

      default:
        return null;
    }
  };

  return (
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
      <SafeAreaView className="flex-1" edges={['top']}>
        {/* Header */}
        <LinearGradient
          colors={isDark ? ['#1E3A8A', '#1E40AF'] : ['#3B82F6', '#2563EB']}
          className="px-5 pt-4 pb-6"
        >
          <View className="flex-row items-center">
            <Pressable
              onPress={() => router.back()}
              className="w-10 h-10 rounded-full bg-white/20 items-center justify-center mr-3"
            >
              <Ionicons name="arrow-back" size={24} color="white" />
            </Pressable>
            <View className="flex-1">
              <Text className="text-white text-xl font-bold">ยืนยันตัวตน (KYC)</Text>
              <Text className="text-blue-100 text-sm">เพื่อปลดล็อคฟีเจอร์ทั้งหมด</Text>
            </View>
          </View>
        </LinearGradient>

        {isLoading ? (
          <View className="flex-1 justify-center items-center">
            <ActivityIndicator size="large" color="#3B82F6" />
            <Text className="text-gray-500 mt-4">กำลังโหลด...</Text>
          </View>
        ) : status !== 'not_submitted' && status !== 'draft' ? (
          <ScrollView className="flex-1">{renderStatusView()}</ScrollView>
        ) : (
          <ScrollView className="flex-1 px-5 pt-6">
            {/* คำแนะนำ */}
            <Animated.View
              entering={FadeInDown.delay(100)}
              className="bg-blue-50 dark:bg-blue-900/30 rounded-xl p-4 mb-6"
            >
              <Text className="text-blue-800 dark:text-blue-300 font-medium mb-2">
                เอกสารที่ต้องใช้:
              </Text>
              <Text className="text-blue-700 dark:text-blue-400 text-sm">
                1. รูปบัตรประชาชน (ด้านหน้า ชัดเจน อ่านได้){'\n'}
                2. รูปถ่ายคู่บัตร (ถือบัตรประชาชนข้างหน้า เห็นหน้าชัด)
              </Text>
            </Animated.View>

            {/* บัตรประชาชน */}
            <Animated.View entering={FadeInDown.delay(200)} className="mb-6">
              <Text className={`font-medium mb-3 ${isDark ? 'text-white' : 'text-gray-800'}`}>
                1. รูปบัตรประชาชน
              </Text>
              <Pressable
                onPress={() => pickImage('id_card')}
                disabled={isUploading}
                className={`border-2 border-dashed rounded-2xl p-6 items-center justify-center ${
                  hasIdCard || idCardImage
                    ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-dark-50'
                }`}
                style={{ minHeight: 180 }}
              >
                {idCardImage ? (
                  <Image
                    source={{ uri: idCardImage }}
                    className="w-full h-40 rounded-xl"
                    resizeMode="cover"
                  />
                ) : hasIdCard ? (
                  <>
                    <Ionicons name="checkmark-circle" size={48} color="#10B981" />
                    <Text className="text-green-600 mt-2">อัพโหลดแล้ว</Text>
                    <Text className="text-gray-500 text-sm mt-1">แตะเพื่อเปลี่ยน</Text>
                  </>
                ) : (
                  <>
                    <Ionicons
                      name="camera"
                      size={48}
                      color={isDark ? '#6B7280' : '#9CA3AF'}
                    />
                    <Text className={`mt-2 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                      แตะเพื่อถ่ายรูปหรือเลือกรูป
                    </Text>
                    <Text className="text-gray-400 text-sm mt-1">
                      รูปด้านหน้าบัตร ชัดเจน อ่านได้
                    </Text>
                  </>
                )}
                {isUploading && (
                  <View className="absolute inset-0 bg-black/50 items-center justify-center rounded-2xl">
                    <ActivityIndicator color="white" />
                    <Text className="text-white mt-2">กำลังอัพโหลด...</Text>
                  </View>
                )}
              </Pressable>
            </Animated.View>

            {/* รูปถ่ายคู่บัตร */}
            <Animated.View entering={FadeInDown.delay(300)} className="mb-6">
              <Text className={`font-medium mb-3 ${isDark ? 'text-white' : 'text-gray-800'}`}>
                2. รูปถ่ายคู่บัตร (เซลฟี่ถือบัตร)
              </Text>
              <Pressable
                onPress={() => pickImage('selfie')}
                disabled={isUploading}
                className={`border-2 border-dashed rounded-2xl p-6 items-center justify-center ${
                  hasSelfie || selfieImage
                    ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-dark-50'
                }`}
                style={{ minHeight: 220 }}
              >
                {selfieImage ? (
                  <Image
                    source={{ uri: selfieImage }}
                    className="w-full h-52 rounded-xl"
                    resizeMode="cover"
                  />
                ) : hasSelfie ? (
                  <>
                    <Ionicons name="checkmark-circle" size={48} color="#10B981" />
                    <Text className="text-green-600 mt-2">อัพโหลดแล้ว</Text>
                    <Text className="text-gray-500 text-sm mt-1">แตะเพื่อเปลี่ยน</Text>
                  </>
                ) : (
                  <>
                    <Ionicons
                      name="person"
                      size={48}
                      color={isDark ? '#6B7280' : '#9CA3AF'}
                    />
                    <Text className={`mt-2 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                      แตะเพื่อถ่ายรูปหรือเลือกรูป
                    </Text>
                    <Text className="text-gray-400 text-sm mt-1 text-center">
                      ถือบัตรประชาชนข้างหน้า{'\n'}ให้เห็นหน้าและบัตรชัดเจน
                    </Text>
                  </>
                )}
                {isUploading && (
                  <View className="absolute inset-0 bg-black/50 items-center justify-center rounded-2xl">
                    <ActivityIndicator color="white" />
                    <Text className="text-white mt-2">กำลังอัพโหลด...</Text>
                  </View>
                )}
              </Pressable>
            </Animated.View>

            {/* ปุ่มส่งเอกสาร */}
            <Animated.View entering={FadeInDown.delay(400)} className="mb-8">
              <Pressable
                onPress={handleSubmitKyc}
                disabled={!hasIdCard || !hasSelfie || isLoading || isUploading}
                className={`py-4 rounded-xl items-center ${
                  hasIdCard && hasSelfie && !isLoading && !isUploading
                    ? 'bg-primary-500'
                    : 'bg-gray-300 dark:bg-gray-700'
                }`}
              >
                {isLoading ? (
                  <ActivityIndicator color="white" />
                ) : (
                  <Text className="text-white font-bold text-lg">ส่งเอกสารยืนยันตัวตน</Text>
                )}
              </Pressable>
              <Text className="text-gray-500 text-sm text-center mt-3">
                เอกสารจะถูกตรวจสอบภายใน 24-48 ชั่วโมง
              </Text>
            </Animated.View>
          </ScrollView>
        )}
      </SafeAreaView>
    </View>
  );
}
