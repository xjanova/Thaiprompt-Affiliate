/**
 * KYC Screen - หน้ายืนยันตัวตนแบบ Premium
 * รองรับถ่ายรูปจากกล้องมือถือ พร้อม Step Progress
 */

import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  Image,
  ActivityIndicator,
  Animated,
  Easing,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import ReAnimated, { FadeInDown, FadeInUp, ZoomIn } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { getKycStatus, uploadKycImage, confirmKycSubmission } from '@/services/api';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

// KYC Status type
type KycStatus = 'not_submitted' | 'pending' | 'approved' | 'rejected' | 'draft';

// =====================================================
// Step Indicator Component
// =====================================================

const StepIndicator = ({
  currentStep,
  hasIdCard,
  hasSelfie,
  isDark,
}: {
  currentStep: number;
  hasIdCard: boolean;
  hasSelfie: boolean;
  isDark: boolean;
}) => {
  const steps = [
    { label: 'บัตรประชาชน', icon: 'card', completed: hasIdCard },
    { label: 'รูปถ่ายคู่บัตร', icon: 'person', completed: hasSelfie },
    { label: 'ยืนยัน', icon: 'checkmark', completed: false },
  ];

  return (
    <View className="flex-row items-center justify-between px-4 py-6">
      {steps.map((step, index) => {
        const isActive = index === currentStep;
        const isCompleted = step.completed;
        const isPast = index < currentStep || isCompleted;

        return (
          <React.Fragment key={index}>
            {/* Step Circle */}
            <View className="items-center flex-1">
              <View
                className={`w-14 h-14 rounded-full items-center justify-center ${
                  isCompleted
                    ? ''
                    : isActive
                    ? ''
                    : isDark
                    ? 'bg-gray-700'
                    : 'bg-gray-200'
                }`}
                style={[
                  isActive && !isCompleted && {
                    shadowColor: '#3B82F6',
                    shadowOffset: { width: 0, height: 0 },
                    shadowOpacity: 0.5,
                    shadowRadius: 12,
                    elevation: 8,
                  },
                ]}
              >
                {isCompleted ? (
                  <LinearGradient
                    colors={['#10B981', '#059669']}
                    className="w-full h-full rounded-full items-center justify-center"
                  >
                    <Ionicons name="checkmark" size={28} color="white" />
                  </LinearGradient>
                ) : isActive ? (
                  <LinearGradient
                    colors={['#3B82F6', '#2563EB']}
                    className="w-full h-full rounded-full items-center justify-center"
                  >
                    <Ionicons name={step.icon as any} size={24} color="white" />
                  </LinearGradient>
                ) : (
                  <Ionicons
                    name={step.icon as any}
                    size={24}
                    color={isDark ? '#6B7280' : '#9CA3AF'}
                  />
                )}
              </View>
              <Text
                className={`text-xs mt-2 text-center ${
                  isActive || isCompleted
                    ? isDark
                      ? 'text-white font-semibold'
                      : 'text-gray-800 font-semibold'
                    : 'text-gray-500'
                }`}
              >
                {step.label}
              </Text>
            </View>

            {/* Connector Line */}
            {index < steps.length - 1 && (
              <View className="flex-1 h-1 mx-1 -mt-6 rounded-full overflow-hidden">
                {isPast ? (
                  <LinearGradient
                    colors={['#10B981', '#10B981']}
                    className="h-full"
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 0 }}
                  />
                ) : (
                  <View className={`h-full ${isDark ? 'bg-gray-700' : 'bg-gray-200'}`} />
                )}
              </View>
            )}
          </React.Fragment>
        );
      })}
    </View>
  );
};

// =====================================================
// Upload Card Component
// =====================================================

const UploadCard = ({
  title,
  subtitle,
  icon,
  imageUri,
  isUploaded,
  isUploading,
  onPress,
  aspectRatio,
  isDark,
}: {
  title: string;
  subtitle: string;
  icon: string;
  imageUri: string | null;
  isUploaded: boolean;
  isUploading: boolean;
  onPress: () => void;
  aspectRatio: [number, number];
  isDark: boolean;
}) => {
  // Animation
  const scaleAnim = useRef(new Animated.Value(1)).current;
  const glowAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (isUploaded) {
      Animated.spring(scaleAnim, {
        toValue: 1.02,
        useNativeDriver: true,
      }).start(() => {
        Animated.spring(scaleAnim, {
          toValue: 1,
          useNativeDriver: true,
        }).start();
      });

      // Glow effect
      Animated.loop(
        Animated.sequence([
          Animated.timing(glowAnim, {
            toValue: 1,
            duration: 1500,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: false,
          }),
          Animated.timing(glowAnim, {
            toValue: 0,
            duration: 1500,
            easing: Easing.inOut(Easing.ease),
            useNativeDriver: false,
          }),
        ])
      ).start();
    }
  }, [isUploaded]);

  const shadowOpacity = glowAnim.interpolate({
    inputRange: [0, 1],
    outputRange: [0.1, 0.4],
  });

  const height = aspectRatio[0] > aspectRatio[1] ? 180 : 240;

  return (
    <Animated.View
      style={{
        transform: [{ scale: scaleAnim }],
      }}
    >
      <Pressable
        onPress={onPress}
        disabled={isUploading}
        className="mb-6"
      >
        <Animated.View
          style={[
            isUploaded && {
              shadowColor: '#10B981',
              shadowOffset: { width: 0, height: 4 },
              shadowOpacity,
              shadowRadius: 16,
              elevation: 8,
            },
          ]}
          className={`rounded-3xl overflow-hidden ${
            isUploaded
              ? isDark
                ? 'bg-green-900/30'
                : 'bg-green-50'
              : isDark
              ? 'bg-gray-800'
              : 'bg-white'
          }`}
        >
          {/* Header */}
          <View className="flex-row items-center p-4 pb-0">
            <View
              className={`w-10 h-10 rounded-xl items-center justify-center ${
                isUploaded
                  ? 'bg-green-100 dark:bg-green-800/50'
                  : 'bg-primary-100 dark:bg-primary-900/50'
              }`}
            >
              <Ionicons
                name={isUploaded ? 'checkmark-circle' : (icon as any)}
                size={22}
                color={isUploaded ? '#10B981' : '#3B82F6'}
              />
            </View>
            <View className="ml-3 flex-1">
              <Text className={`font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                {title}
              </Text>
              <Text className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                {subtitle}
              </Text>
            </View>
            {isUploaded && (
              <View className="px-3 py-1 bg-green-500 rounded-full">
                <Text className="text-white text-xs font-semibold">อัพโหลดแล้ว</Text>
              </View>
            )}
          </View>

          {/* Upload Area */}
          <View
            className={`m-4 rounded-2xl border-2 border-dashed overflow-hidden ${
              isUploaded
                ? 'border-green-400 dark:border-green-600'
                : isDark
                ? 'border-gray-600'
                : 'border-gray-300'
            }`}
            style={{ height }}
          >
            {imageUri ? (
              <Image
                source={{ uri: imageUri }}
                className="w-full h-full"
                resizeMode="cover"
              />
            ) : isUploaded ? (
              <View className="flex-1 items-center justify-center bg-green-100/50 dark:bg-green-900/30">
                <Ionicons name="checkmark-circle" size={56} color="#10B981" />
                <Text className="text-green-600 dark:text-green-400 mt-2 font-medium">
                  รูปภาพถูกอัพโหลดแล้ว
                </Text>
                <Text className="text-green-500 dark:text-green-500 text-sm mt-1">
                  แตะเพื่อเปลี่ยนรูป
                </Text>
              </View>
            ) : (
              <View className="flex-1 items-center justify-center p-6">
                <View
                  className={`w-20 h-20 rounded-full items-center justify-center ${
                    isDark ? 'bg-gray-700' : 'bg-gray-100'
                  }`}
                >
                  <Ionicons
                    name="camera"
                    size={36}
                    color={isDark ? '#6B7280' : '#9CA3AF'}
                  />
                </View>
                <Text className={`mt-4 text-base font-medium ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  แตะเพื่อถ่ายรูปหรือเลือกรูป
                </Text>
                <Text className="text-gray-400 text-sm mt-1 text-center">
                  {aspectRatio[0] > aspectRatio[1]
                    ? 'ถ่ายรูปด้านหน้าบัตร ชัดเจน อ่านได้'
                    : 'ถือบัตรข้างหน้า ให้เห็นหน้าและบัตรชัดเจน'}
                </Text>
              </View>
            )}

            {/* Uploading Overlay */}
            {isUploading && (
              <View className="absolute inset-0 bg-black/60 items-center justify-center">
                <View className="bg-white dark:bg-gray-800 rounded-2xl p-6 items-center">
                  <ActivityIndicator size="large" color="#3B82F6" />
                  <Text className="text-gray-800 dark:text-white mt-3 font-medium">
                    กำลังอัพโหลด...
                  </Text>
                </View>
              </View>
            )}
          </View>
        </Animated.View>
      </Pressable>
    </Animated.View>
  );
};

// =====================================================
// Approved Animation Component
// =====================================================

const ApprovedView = ({ isDark }: { isDark: boolean }) => {
  const scaleAnim = useRef(new Animated.Value(0)).current;
  const rotateAnim = useRef(new Animated.Value(0)).current;
  const glowAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // Shield animation
    Animated.sequence([
      Animated.spring(scaleAnim, {
        toValue: 1.2,
        tension: 50,
        friction: 3,
        useNativeDriver: true,
      }),
      Animated.spring(scaleAnim, {
        toValue: 1,
        useNativeDriver: true,
      }),
    ]).start();

    // Rotate checkmark
    Animated.timing(rotateAnim, {
      toValue: 1,
      duration: 500,
      delay: 300,
      useNativeDriver: true,
    }).start();

    // Glow effect
    Animated.loop(
      Animated.sequence([
        Animated.timing(glowAnim, {
          toValue: 1,
          duration: 1500,
          useNativeDriver: false,
        }),
        Animated.timing(glowAnim, {
          toValue: 0,
          duration: 1500,
          useNativeDriver: false,
        }),
      ])
    ).start();
  }, []);

  const rotation = rotateAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  return (
    <View className="flex-1 items-center justify-center px-6">
      {/* Animated Shield */}
      <Animated.View
        style={{
          transform: [{ scale: scaleAnim }],
        }}
      >
        <LinearGradient
          colors={['#10B981', '#059669', '#047857']}
          className="w-32 h-32 rounded-full items-center justify-center"
          style={{
            shadowColor: '#10B981',
            shadowOffset: { width: 0, height: 8 },
            shadowOpacity: 0.4,
            shadowRadius: 16,
            elevation: 12,
          }}
        >
          <View className="w-28 h-28 rounded-full bg-white/20 items-center justify-center">
            <Animated.View style={{ transform: [{ rotate: rotation }] }}>
              <Ionicons name="shield-checkmark" size={56} color="white" />
            </Animated.View>
          </View>
        </LinearGradient>
      </Animated.View>

      <ReAnimated.View entering={FadeInUp.delay(500)}>
        <Text className={`text-3xl font-bold mt-8 ${isDark ? 'text-white' : 'text-gray-900'}`}>
          ยืนยันตัวตนสำเร็จ!
        </Text>
      </ReAnimated.View>

      <ReAnimated.View entering={FadeInUp.delay(700)}>
        <Text className="text-gray-500 text-center mt-4 text-lg px-8">
          บัญชีของคุณได้รับการยืนยันแล้ว{'\n'}คุณสามารถใช้งานฟีเจอร์ทั้งหมดได้
        </Text>
      </ReAnimated.View>

      {/* Benefits */}
      <ReAnimated.View
        entering={FadeInUp.delay(900)}
        className={`mt-8 p-5 rounded-2xl ${isDark ? 'bg-gray-800' : 'bg-green-50'}`}
        style={{ width: SCREEN_WIDTH - 48 }}
      >
        <Text className={`font-semibold mb-4 ${isDark ? 'text-white' : 'text-gray-800'}`}>
          ฟีเจอร์ที่ปลดล็อค:
        </Text>
        {['ถอนเงินได้ไม่จำกัด', 'รับค่าคอมมิชชั่นทันที', 'เข้าถึง Premium Features'].map(
          (benefit, index) => (
            <View key={index} className="flex-row items-center mb-2">
              <Ionicons name="checkmark-circle" size={20} color="#10B981" />
              <Text className={`ml-3 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                {benefit}
              </Text>
            </View>
          )
        )}
      </ReAnimated.View>

      <ReAnimated.View entering={FadeInUp.delay(1100)}>
        <Pressable
          onPress={() => router.back()}
          className="mt-8"
        >
          <LinearGradient
            colors={['#10B981', '#059669']}
            className="px-12 py-4 rounded-2xl"
            style={{
              shadowColor: '#10B981',
              shadowOffset: { width: 0, height: 4 },
              shadowOpacity: 0.3,
              shadowRadius: 8,
              elevation: 6,
            }}
          >
            <Text className="text-white font-bold text-lg">กลับหน้าหลัก</Text>
          </LinearGradient>
        </Pressable>
      </ReAnimated.View>
    </View>
  );
};

// =====================================================
// Pending View Component
// =====================================================

const PendingView = ({ isDark }: { isDark: boolean }) => {
  const rotateAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.loop(
      Animated.timing(rotateAnim, {
        toValue: 1,
        duration: 3000,
        easing: Easing.linear,
        useNativeDriver: true,
      })
    ).start();
  }, []);

  const rotation = rotateAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  return (
    <View className="flex-1 items-center justify-center px-6">
      {/* Animated Clock */}
      <View
        className={`w-32 h-32 rounded-full items-center justify-center ${
          isDark ? 'bg-yellow-900/30' : 'bg-yellow-100'
        }`}
        style={{
          shadowColor: '#F59E0B',
          shadowOffset: { width: 0, height: 8 },
          shadowOpacity: 0.3,
          shadowRadius: 16,
          elevation: 12,
        }}
      >
        <Animated.View style={{ transform: [{ rotate: rotation }] }}>
          <Ionicons name="time" size={72} color="#F59E0B" />
        </Animated.View>
      </View>

      <ReAnimated.View entering={FadeInUp.delay(300)}>
        <Text className={`text-3xl font-bold mt-8 ${isDark ? 'text-white' : 'text-gray-900'}`}>
          รอการตรวจสอบ
        </Text>
      </ReAnimated.View>

      <ReAnimated.View entering={FadeInUp.delay(500)}>
        <Text className="text-gray-500 text-center mt-4 text-lg px-6">
          เอกสารของคุณอยู่ระหว่างการตรวจสอบ{'\n'}โดยทีมงานของเรา
        </Text>
      </ReAnimated.View>

      {/* Timeline */}
      <ReAnimated.View
        entering={FadeInUp.delay(700)}
        className={`mt-8 p-5 rounded-2xl ${isDark ? 'bg-gray-800' : 'bg-yellow-50'}`}
        style={{ width: SCREEN_WIDTH - 48 }}
      >
        <View className="flex-row items-center mb-4">
          <View className="w-10 h-10 rounded-full bg-green-500 items-center justify-center">
            <Ionicons name="checkmark" size={20} color="white" />
          </View>
          <View className="ml-4 flex-1">
            <Text className={`font-medium ${isDark ? 'text-white' : 'text-gray-800'}`}>
              ส่งเอกสารแล้ว
            </Text>
            <Text className="text-gray-500 text-sm">เมื่อสักครู่</Text>
          </View>
        </View>

        <View className="flex-row items-center mb-4">
          <View className="w-10 h-10 rounded-full bg-yellow-500 items-center justify-center">
            <Ionicons name="search" size={20} color="white" />
          </View>
          <View className="ml-4 flex-1">
            <Text className={`font-medium ${isDark ? 'text-white' : 'text-gray-800'}`}>
              กำลังตรวจสอบ
            </Text>
            <Text className="text-gray-500 text-sm">อยู่ระหว่างดำเนินการ</Text>
          </View>
        </View>

        <View className="flex-row items-center opacity-50">
          <View className={`w-10 h-10 rounded-full items-center justify-center ${isDark ? 'bg-gray-700' : 'bg-gray-200'}`}>
            <Ionicons name="checkmark-done" size={20} color={isDark ? '#6B7280' : '#9CA3AF'} />
          </View>
          <View className="ml-4 flex-1">
            <Text className={`font-medium ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
              ยืนยันสำเร็จ
            </Text>
            <Text className="text-gray-400 text-sm">รอดำเนินการ</Text>
          </View>
        </View>
      </ReAnimated.View>

      <ReAnimated.View entering={FadeInUp.delay(900)}>
        <Text className="text-gray-400 text-center mt-6 px-6">
          ⏱️ คาดว่าจะแล้วเสร็จภายใน 24-48 ชั่วโมง
        </Text>
      </ReAnimated.View>

      <ReAnimated.View entering={FadeInUp.delay(1100)}>
        <Pressable
          onPress={() => router.back()}
          className={`mt-8 px-12 py-4 rounded-2xl ${isDark ? 'bg-gray-800' : 'bg-gray-100'}`}
        >
          <Text className={`font-bold text-lg ${isDark ? 'text-white' : 'text-gray-700'}`}>
            กลับหน้าหลัก
          </Text>
        </Pressable>
      </ReAnimated.View>
    </View>
  );
};

// =====================================================
// Rejected View Component
// =====================================================

const RejectedView = ({
  reason,
  onRetry,
  isDark,
}: {
  reason: string | null;
  onRetry: () => void;
  isDark: boolean;
}) => {
  return (
    <View className="flex-1 items-center justify-center px-6">
      {/* Animated X */}
      <ReAnimated.View entering={ZoomIn.delay(200)}>
        <View
          className={`w-32 h-32 rounded-full items-center justify-center ${
            isDark ? 'bg-red-900/30' : 'bg-red-100'
          }`}
          style={{
            shadowColor: '#EF4444',
            shadowOffset: { width: 0, height: 8 },
            shadowOpacity: 0.3,
            shadowRadius: 16,
            elevation: 12,
          }}
        >
          <Ionicons name="close-circle" size={72} color="#EF4444" />
        </View>
      </ReAnimated.View>

      <ReAnimated.View entering={FadeInUp.delay(400)}>
        <Text className={`text-3xl font-bold mt-8 ${isDark ? 'text-white' : 'text-gray-900'}`}>
          การยืนยันถูกปฏิเสธ
        </Text>
      </ReAnimated.View>

      {reason && (
        <ReAnimated.View
          entering={FadeInUp.delay(600)}
          className={`mt-6 p-5 rounded-2xl ${isDark ? 'bg-red-900/30' : 'bg-red-50'}`}
          style={{ width: SCREEN_WIDTH - 48 }}
        >
          <Text className={`font-semibold mb-2 ${isDark ? 'text-red-300' : 'text-red-700'}`}>
            เหตุผล:
          </Text>
          <Text className={`${isDark ? 'text-red-200' : 'text-red-600'}`}>{reason}</Text>
        </ReAnimated.View>
      )}

      <ReAnimated.View
        entering={FadeInUp.delay(800)}
        className={`mt-6 p-5 rounded-2xl ${isDark ? 'bg-gray-800' : 'bg-gray-50'}`}
        style={{ width: SCREEN_WIDTH - 48 }}
      >
        <Text className={`font-semibold mb-3 ${isDark ? 'text-white' : 'text-gray-800'}`}>
          คำแนะนำในการส่งเอกสารใหม่:
        </Text>
        {[
          'ถ่ายรูปในที่ที่มีแสงเพียงพอ',
          'ให้รูปชัดเจน ไม่เบลอ',
          'เห็นข้อมูลบนบัตรครบถ้วน',
          'หน้าและบัตรอยู่ในรูปเดียวกัน',
        ].map((tip, index) => (
          <View key={index} className="flex-row items-center mb-2">
            <Ionicons name="bulb" size={16} color="#F59E0B" />
            <Text className={`ml-2 ${isDark ? 'text-gray-300' : 'text-gray-600'}`}>{tip}</Text>
          </View>
        ))}
      </ReAnimated.View>

      <ReAnimated.View entering={FadeInUp.delay(1000)}>
        <Pressable onPress={onRetry} className="mt-8">
          <LinearGradient
            colors={['#3B82F6', '#2563EB']}
            className="px-12 py-4 rounded-2xl"
            style={{
              shadowColor: '#3B82F6',
              shadowOffset: { width: 0, height: 4 },
              shadowOpacity: 0.3,
              shadowRadius: 8,
              elevation: 6,
            }}
          >
            <Text className="text-white font-bold text-lg">ส่งเอกสารใหม่</Text>
          </LinearGradient>
        </Pressable>
      </ReAnimated.View>
    </View>
  );
};

// =====================================================
// Main Component
// =====================================================

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
  const [isUploadingIdCard, setIsUploadingIdCard] = useState(false);
  const [isUploadingSelfie, setIsUploadingSelfie] = useState(false);
  const [rejectionReason, setRejectionReason] = useState<string | null>(null);

  // Calculate current step
  const currentStep = hasSelfie ? 2 : hasIdCard ? 1 : 0;

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
    const aspect: [number, number] = type === 'id_card' ? [16, 10] : [3, 4];

    Alert.alert(`เลือกรูป${title}`, 'เลือกวิธีการถ่ายรูป', [
      {
        text: '📷 ถ่ายรูป',
        onPress: async () => {
          const hasPermission = await requestCameraPermission();
          if (!hasPermission) return;

          const result = await ImagePicker.launchCameraAsync({
            mediaTypes: ImagePicker.MediaTypeOptions.Images,
            allowsEditing: true,
            aspect,
            quality: 0.8,
          });

          if (!result.canceled && result.assets[0]) {
            handleImageSelected(result.assets[0].uri, type);
          }
        },
      },
      {
        text: '🖼️ เลือกจากแกลเลอรี่',
        onPress: async () => {
          const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ImagePicker.MediaTypeOptions.Images,
            allowsEditing: true,
            aspect,
            quality: 0.8,
          });

          if (!result.canceled && result.assets[0]) {
            handleImageSelected(result.assets[0].uri, type);
          }
        },
      },
      { text: 'ยกเลิก', style: 'cancel' },
    ]);
  };

  // จัดการรูปที่เลือก
  const handleImageSelected = async (uri: string, type: 'id_card' | 'selfie') => {
    const setUploading = type === 'id_card' ? setIsUploadingIdCard : setIsUploadingSelfie;
    setUploading(true);

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
        // Show success silently
      } else {
        Alert.alert('อัพโหลดไม่สำเร็จ', response.message || 'กรุณาลองใหม่อีกครั้ง');
      }
    } catch (error) {
      console.error('Upload error:', error);
      Alert.alert('เกิดข้อผิดพลาด', 'ไม่สามารถอัพโหลดรูปได้ กรุณาลองใหม่');
    } finally {
      setUploading(false);
    }
  };

  // ส่ง KYC
  const handleSubmitKyc = async () => {
    if (!hasIdCard || !hasSelfie) {
      Alert.alert('ข้อมูลไม่ครบ', 'กรุณาอัพโหลดรูปบัตรประชาชนและรูปถ่ายคู่บัตร');
      return;
    }

    Alert.alert('ยืนยันส่งเอกสาร', 'คุณต้องการส่งเอกสารยืนยันตัวตนใช่หรือไม่?', [
      { text: 'ยกเลิก', style: 'cancel' },
      {
        text: '✅ ส่งเอกสาร',
        onPress: async () => {
          setIsLoading(true);
          try {
            const response = await confirmKycSubmission();

            if (response.success) {
              setStatus('pending');
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
    ]);
  };

  // Handle retry after rejection
  const handleRetry = () => {
    setStatus('not_submitted');
    setIdCardImage(null);
    setSelfieImage(null);
    setHasIdCard(false);
    setHasSelfie(false);
    setRejectionReason(null);
  };

  // ถ้ายังไม่ login
  if (!isAuthenticated) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center px-6">
          <LinearGradient
            colors={['#3B82F6', '#2563EB']}
            className="w-24 h-24 rounded-full items-center justify-center mb-6"
          >
            <Ionicons name="shield-checkmark" size={48} color="white" />
          </LinearGradient>
          <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-800'}`}>
            ยืนยันตัวตน (KYC)
          </Text>
          <Text className="text-gray-500 text-center mt-2">เข้าสู่ระบบเพื่อยืนยันตัวตน</Text>
          <Pressable onPress={() => router.push('/login')} className="mt-6">
            <LinearGradient colors={['#3B82F6', '#2563EB']} className="px-8 py-3 rounded-xl">
              <Text className="text-white font-bold">เข้าสู่ระบบ</Text>
            </LinearGradient>
          </Pressable>
        </SafeAreaView>
      </View>
    );
  }

  // Loading state
  if (isLoading && status === 'not_submitted') {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1 justify-center items-center">
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text className="text-gray-500 mt-4">กำลังโหลด...</Text>
        </SafeAreaView>
      </View>
    );
  }

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

        {/* Content based on status */}
        {status === 'approved' ? (
          <ApprovedView isDark={isDark} />
        ) : status === 'pending' ? (
          <PendingView isDark={isDark} />
        ) : status === 'rejected' ? (
          <RejectedView reason={rejectionReason} onRetry={handleRetry} isDark={isDark} />
        ) : (
          <ScrollView className="flex-1" showsVerticalScrollIndicator={false}>
            {/* Step Indicator */}
            <StepIndicator
              currentStep={currentStep}
              hasIdCard={hasIdCard}
              hasSelfie={hasSelfie}
              isDark={isDark}
            />

            {/* Upload Cards */}
            <View className="px-4">
              {/* คำแนะนำ */}
              <ReAnimated.View
                entering={FadeInDown.delay(100)}
                className={`p-4 rounded-2xl mb-6 ${
                  isDark ? 'bg-blue-900/30' : 'bg-blue-50'
                }`}
              >
                <View className="flex-row items-start">
                  <Ionicons name="information-circle" size={24} color="#3B82F6" />
                  <View className="ml-3 flex-1">
                    <Text className={`font-semibold ${isDark ? 'text-blue-300' : 'text-blue-800'}`}>
                      เอกสารที่ต้องใช้
                    </Text>
                    <Text className={`text-sm mt-1 ${isDark ? 'text-blue-200' : 'text-blue-700'}`}>
                      • รูปบัตรประชาชน (ด้านหน้า ชัดเจน){'\n'}• รูปถ่ายคู่บัตร (ถือบัตรข้างหน้า)
                    </Text>
                  </View>
                </View>
              </ReAnimated.View>

              {/* ID Card Upload */}
              <ReAnimated.View entering={FadeInDown.delay(200)}>
                <UploadCard
                  title="รูปบัตรประชาชน"
                  subtitle="ด้านหน้า ชัดเจน อ่านตัวอักษรได้"
                  icon="card"
                  imageUri={idCardImage}
                  isUploaded={hasIdCard}
                  isUploading={isUploadingIdCard}
                  onPress={() => pickImage('id_card')}
                  aspectRatio={[16, 10]}
                  isDark={isDark}
                />
              </ReAnimated.View>

              {/* Selfie Upload */}
              <ReAnimated.View entering={FadeInDown.delay(300)}>
                <UploadCard
                  title="รูปถ่ายคู่บัตร (เซลฟี่)"
                  subtitle="ถือบัตรข้างใบหน้า เห็นทั้งหน้าและบัตร"
                  icon="person"
                  imageUri={selfieImage}
                  isUploaded={hasSelfie}
                  isUploading={isUploadingSelfie}
                  onPress={() => pickImage('selfie')}
                  aspectRatio={[3, 4]}
                  isDark={isDark}
                />
              </ReAnimated.View>

              {/* Submit Button */}
              <ReAnimated.View entering={FadeInDown.delay(400)} className="mb-8">
                <Pressable
                  onPress={handleSubmitKyc}
                  disabled={!hasIdCard || !hasSelfie || isLoading}
                >
                  {hasIdCard && hasSelfie && !isLoading ? (
                    <LinearGradient
                      colors={['#10B981', '#059669']}
                      className="py-5 rounded-2xl items-center flex-row justify-center"
                      style={{
                        shadowColor: '#10B981',
                        shadowOffset: { width: 0, height: 4 },
                        shadowOpacity: 0.3,
                        shadowRadius: 8,
                        elevation: 6,
                      }}
                    >
                      <Ionicons name="shield-checkmark" size={24} color="white" />
                      <Text className="text-white font-bold text-lg ml-2">
                        ส่งเอกสารยืนยันตัวตน
                      </Text>
                    </LinearGradient>
                  ) : (
                    <View
                      className={`py-5 rounded-2xl items-center flex-row justify-center ${
                        isDark ? 'bg-gray-700' : 'bg-gray-200'
                      }`}
                    >
                      <Ionicons
                        name="shield-checkmark"
                        size={24}
                        color={isDark ? '#6B7280' : '#9CA3AF'}
                      />
                      <Text
                        className={`font-bold text-lg ml-2 ${
                          isDark ? 'text-gray-500' : 'text-gray-400'
                        }`}
                      >
                        {isLoading ? 'กำลังดำเนินการ...' : 'กรุณาอัพโหลดเอกสารให้ครบ'}
                      </Text>
                    </View>
                  )}
                </Pressable>

                <Text className="text-gray-500 text-sm text-center mt-4">
                  เอกสารจะถูกตรวจสอบภายใน 24-48 ชั่วโมง
                </Text>
              </ReAnimated.View>
            </View>
          </ScrollView>
        )}
      </SafeAreaView>
    </View>
  );
}
