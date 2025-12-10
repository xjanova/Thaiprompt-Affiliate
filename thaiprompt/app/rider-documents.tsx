/**
 * Rider Documents Screen - หน้าอัพโหลดเอกสารยืนยันตัวตน
 * รองรับการอัพโหลด: บัตรประชาชน, ใบขับขี่, ทะเบียนรถ
 */

import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  ActivityIndicator,
  Image,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import Animated, { FadeInDown, FadeIn, ZoomIn } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { uploadRiderDocument, getRiderStatus } from '@/services/api';
import { ErrorState } from '@/components/ErrorState';

// =====================================================
// Types
// =====================================================

type DocumentType = 'id_card' | 'driver_license' | 'vehicle_registration' | 'profile';

interface DocumentItem {
  type: DocumentType;
  title: string;
  subtitle: string;
  icon: string;
  color: string;
  required: boolean;
  uploaded?: boolean;
}

// =====================================================
// Document Card Component
// =====================================================

const DocumentCard = ({
  item,
  index,
  imageUri,
  isUploading,
  onUpload,
  onRemove,
}: {
  item: DocumentItem;
  index: number;
  imageUri?: string;
  isUploading: boolean;
  onUpload: () => void;
  onRemove: () => void;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  return (
    <Animated.View
      entering={FadeInDown.delay(100 + index * 50).springify()}
      className="bg-white dark:bg-gray-800 rounded-2xl p-4 mb-4 border border-gray-100 dark:border-gray-700"
      style={{
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.05,
        shadowRadius: 8,
        elevation: 2,
      }}
    >
      <View className="flex-row items-center mb-4">
        <View
          className="w-12 h-12 rounded-xl items-center justify-center mr-3"
          style={{ backgroundColor: item.color + '20' }}
        >
          <Text style={{ fontSize: 24, color: item.color }}>{item.icon}</Text>
        </View>
        <View className="flex-1">
          <View className="flex-row items-center">
            <Text className="text-gray-900 dark:text-white font-bold">
              {item.title}
            </Text>
            {item.required && (
              <Text className="text-red-500 ml-1">*</Text>
            )}
          </View>
          <Text className="text-gray-500 dark:text-gray-400 text-sm">
            {item.subtitle}
          </Text>
        </View>
        {item.uploaded && (
          <View className="bg-green-100 dark:bg-green-900/30 rounded-full p-1">
            <Text style={{ fontSize: 16, color: '#10B981' }}>✓</Text>
          </View>
        )}
      </View>

      {/* Image Preview or Upload Button */}
      {imageUri ? (
        <View className="relative">
          <Image
            source={{ uri: imageUri }}
            className="w-full h-48 rounded-xl"
            resizeMode="cover"
          />
          <Pressable
            onPress={onRemove}
            disabled={isUploading}
            className="absolute top-2 right-2 w-8 h-8 bg-black/50 rounded-full items-center justify-center"
          >
            <Text style={{ fontSize: 18, color: 'white' }}>✕</Text>
          </Pressable>
          {isUploading && (
            <View className="absolute inset-0 bg-black/30 rounded-xl items-center justify-center">
              <ActivityIndicator color="white" size="large" />
              <Text className="text-white mt-2 font-medium">กำลังอัพโหลด...</Text>
            </View>
          )}
        </View>
      ) : (
        <Pressable
          onPress={onUpload}
          disabled={isUploading}
          className="bg-gray-100 dark:bg-gray-700 rounded-xl py-8 items-center border-2 border-dashed border-gray-300 dark:border-gray-600"
        >
          <Text style={{ fontSize: 40, color: isDark ? '#9CA3AF' : '#6B7280' }}>📷</Text>
          <Text className="text-gray-500 dark:text-gray-400 mt-2 font-medium">
            แตะเพื่อถ่ายรูปหรือเลือกรูป
          </Text>
        </Pressable>
      )}
    </Animated.View>
  );
};

// =====================================================
// Progress Indicator
// =====================================================

const ProgressIndicator = ({
  uploaded,
  total,
}: {
  uploaded: number;
  total: number;
}) => {
  const progress = (uploaded / total) * 100;

  return (
    <Animated.View
      entering={FadeIn}
      className="bg-white dark:bg-gray-800 rounded-2xl p-4 mb-4"
    >
      <View className="flex-row items-center justify-between mb-2">
        <Text className="text-gray-700 dark:text-gray-300 font-medium">
          ความคืบหน้า
        </Text>
        <Text className="text-primary-500 font-bold">
          {uploaded}/{total} เอกสาร
        </Text>
      </View>
      <View className="h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
        <Animated.View
          entering={ZoomIn.springify()}
          className="h-full bg-primary-500 rounded-full"
          style={{ width: `${progress}%` }}
        />
      </View>
    </Animated.View>
  );
};

// =====================================================
// Main Component
// =====================================================

export default function RiderDocumentsScreen() {
  const { isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const [isLoading, setIsLoading] = useState(true);
  const [uploadingType, setUploadingType] = useState<DocumentType | null>(null);
  const [images, setImages] = useState<Record<DocumentType, string | undefined>>({
    id_card: undefined,
    driver_license: undefined,
    vehicle_registration: undefined,
    profile: undefined,
  });
  const [uploadedDocs, setUploadedDocs] = useState<DocumentType[]>([]);
  const [hasError, setHasError] = useState(false);
  const [isRetrying, setIsRetrying] = useState(false);

  // Document items
  const documents: DocumentItem[] = [
    {
      type: 'id_card',
      title: 'บัตรประชาชน',
      subtitle: 'ถ่ายรูปบัตรประชาชนด้านหน้า',
      icon: '🪪',
      color: '#3B82F6',
      required: true,
      uploaded: uploadedDocs.includes('id_card'),
    },
    {
      type: 'driver_license',
      title: 'ใบขับขี่',
      subtitle: 'ถ่ายรูปใบขับขี่ด้านหน้า',
      icon: '🚗',
      color: '#10B981',
      required: true,
      uploaded: uploadedDocs.includes('driver_license'),
    },
    {
      type: 'vehicle_registration',
      title: 'ทะเบียนรถ',
      subtitle: 'ถ่ายรูปเล่มทะเบียนรถหน้าแรก',
      icon: '📄',
      color: '#8B5CF6',
      required: false,
      uploaded: uploadedDocs.includes('vehicle_registration'),
    },
    {
      type: 'profile',
      title: 'รูปโปรไฟล์',
      subtitle: 'รูปถ่ายหน้าตรงของคุณ',
      icon: '👤',
      color: '#F59E0B',
      required: false,
      uploaded: uploadedDocs.includes('profile'),
    },
  ];

  const requiredCount = documents.filter((d) => d.required).length;
  const uploadedRequiredCount = documents.filter(
    (d) => d.required && d.uploaded
  ).length;

  // Load status
  const loadStatus = useCallback(async () => {
    try {
      setHasError(false);

      // Get rider status to check what documents are uploaded
      const response = await getRiderStatus();

      // In real app, response would include which documents are uploaded
      if (!response?.success) {
        setHasError(true);
      }
    } catch (error) {
      console.error('Load status error:', error);
      // Network error or API connection failed
      setHasError(true);
    } finally {
      setIsLoading(false);
      setIsRetrying(false);
    }
  }, []);

  // Retry on error
  const handleRetry = async () => {
    setIsRetrying(true);
    setIsLoading(true);
    await loadStatus();
  };

  useEffect(() => {
    if (isAuthenticated) {
      loadStatus();
    }
  }, [isAuthenticated, loadStatus]);

  // Pick image
  const pickImage = async (type: DocumentType) => {
    Alert.alert(
      'เลือกรูปภาพ',
      'คุณต้องการถ่ายรูปใหม่หรือเลือกจากคลัง?',
      [
        {
          text: 'ถ่ายรูป',
          onPress: () => takePhoto(type),
        },
        {
          text: 'เลือกจากคลัง',
          onPress: () => selectFromGallery(type),
        },
        {
          text: 'ยกเลิก',
          style: 'cancel',
        },
      ]
    );
  };

  const takePhoto = async (type: DocumentType) => {
    const { status } = await ImagePicker.requestCameraPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert(
        'ต้องอนุญาตกล้อง',
        'กรุณาอนุญาตการเข้าถึงกล้องในการตั้งค่า'
      );
      return;
    }

    const result = await ImagePicker.launchCameraAsync({
      mediaTypes: 'images',
      quality: 0.8,
      allowsEditing: true,
      aspect: type === 'profile' ? [1, 1] : [16, 10],
    });

    if (!result.canceled && result.assets[0]) {
      await handleUpload(type, result.assets[0].uri);
    }
  };

  const selectFromGallery = async (type: DocumentType) => {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert(
        'ต้องอนุญาตคลังรูป',
        'กรุณาอนุญาตการเข้าถึงคลังรูปในการตั้งค่า'
      );
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: 'images',
      quality: 0.8,
      allowsEditing: true,
      aspect: type === 'profile' ? [1, 1] : [16, 10],
    });

    if (!result.canceled && result.assets[0]) {
      await handleUpload(type, result.assets[0].uri);
    }
  };

  // Upload image
  const handleUpload = async (type: DocumentType, uri: string) => {
    setImages((prev) => ({ ...prev, [type]: uri }));
    setUploadingType(type);

    try {
      const response = await uploadRiderDocument(uri, type);

      if (response.success) {
        setUploadedDocs((prev) => [...prev.filter((t) => t !== type), type]);
        Alert.alert('สำเร็จ', 'อัพโหลดเอกสารเรียบร้อย');
      } else {
        setImages((prev) => ({ ...prev, [type]: undefined }));
        Alert.alert('ไม่สำเร็จ', response.message || 'อัพโหลดไม่สำเร็จ');
      }
    } catch (error) {
      console.error('Upload error:', error);
      setImages((prev) => ({ ...prev, [type]: undefined }));
      // Show proper error message for connection failures
      Alert.alert(
        'ไม่สามารถเชื่อมต่อได้',
        'ขณะนี้ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้\nโปรดลองใหม่ภายหลัง'
      );
    } finally {
      setUploadingType(null);
    }
  };

  // Remove image
  const handleRemove = (type: DocumentType) => {
    Alert.alert(
      'ลบรูปภาพ',
      'ต้องการลบรูปภาพนี้?',
      [
        { text: 'ยกเลิก', style: 'cancel' },
        {
          text: 'ลบ',
          style: 'destructive',
          onPress: () => {
            setImages((prev) => ({ ...prev, [type]: undefined }));
            setUploadedDocs((prev) => prev.filter((t) => t !== type));
          },
        },
      ]
    );
  };

  // Submit documents
  const handleSubmit = () => {
    if (uploadedRequiredCount < requiredCount) {
      Alert.alert(
        'เอกสารไม่ครบ',
        'กรุณาอัพโหลดเอกสารที่จำเป็นให้ครบก่อน'
      );
      return;
    }

    Alert.alert(
      'ส่งเอกสาร',
      'ยืนยันส่งเอกสารเพื่อตรวจสอบ?',
      [
        { text: 'ยกเลิก', style: 'cancel' },
        {
          text: 'ส่ง',
          onPress: () => {
            Alert.alert(
              'สำเร็จ',
              'ส่งเอกสารเรียบร้อย รอการตรวจสอบ 1-3 วันทำการ',
              [{ text: 'ตกลง', onPress: () => router.back() }]
            );
          },
        },
      ]
    );
  };

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

  // Error state - cannot connect to server
  if (hasError) {
    return (
      <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
        <SafeAreaView className="flex-1">
          {/* Header */}
          <View className="flex-row items-center px-5 pt-4 pb-2">
            <Pressable onPress={() => router.back()} className="mr-4">
              <Text style={{ fontSize: 24, color: isDark ? '#fff' : '#000' }}>←</Text>
            </Pressable>
            <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
              อัพโหลดเอกสาร
            </Text>
          </View>

          <ErrorState
            title="ไม่สามารถโหลดข้อมูลได้"
            message="ขณะนี้ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้\nโปรดลองใหม่ภายหลัง"
            onRetry={handleRetry}
            retryText={isRetrying ? 'กำลังลอง...' : 'ลองใหม่'}
            showRetry={!isRetrying}
          />
        </SafeAreaView>
      </View>
    );
  }

  return (
    <View className={`flex-1 ${isDark ? 'bg-dark' : 'bg-gray-50'}`}>
      <SafeAreaView className="flex-1">
        {/* Header */}
        <View className="flex-row items-center px-5 pt-4 pb-2">
          <Pressable onPress={() => router.back()} className="mr-4">
            <Text style={{ fontSize: 24, color: isDark ? '#fff' : '#000' }}>←</Text>
          </Pressable>
          <View className="flex-1">
            <Text className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
              อัพโหลดเอกสาร
            </Text>
            <Text className="text-gray-500 dark:text-gray-400 text-sm">
              ยืนยันตัวตนเพื่อเป็นไรเดอร์
            </Text>
          </View>
        </View>

        <ScrollView
          className="flex-1 px-5"
          contentContainerClassName="pb-32"
          showsVerticalScrollIndicator={false}
        >
          {/* Info Banner */}
          <Animated.View
            entering={FadeInDown.delay(50).springify()}
            className="mb-4"
          >
            <LinearGradient
              colors={['#3B82F6', '#1D4ED8']}
              style={{
                borderRadius: 16,
                padding: 16,
              }}
            >
              <View className="flex-row items-center">
                <View className="w-12 h-12 bg-white/20 rounded-xl items-center justify-center mr-3">
                  <Text style={{ fontSize: 24, color: 'white' }}>🛡️</Text>
                </View>
                <View className="flex-1">
                  <Text className="text-white font-bold">ยืนยันตัวตน</Text>
                  <Text className="text-blue-100 text-sm">
                    อัพโหลดเอกสารเพื่อเริ่มรับงาน
                  </Text>
                </View>
              </View>
            </LinearGradient>
          </Animated.View>

          {/* Progress */}
          <ProgressIndicator
            uploaded={uploadedDocs.length}
            total={documents.length}
          />

          {/* Required Documents */}
          <Text className="text-gray-700 dark:text-gray-300 font-bold mb-3">
            เอกสารจำเป็น
          </Text>
          {documents
            .filter((d) => d.required)
            .map((doc, index) => (
              <DocumentCard
                key={doc.type}
                item={doc}
                index={index}
                imageUri={images[doc.type]}
                isUploading={uploadingType === doc.type}
                onUpload={() => pickImage(doc.type)}
                onRemove={() => handleRemove(doc.type)}
              />
            ))}

          {/* Optional Documents */}
          <Text className="text-gray-700 dark:text-gray-300 font-bold mb-3 mt-4">
            เอกสารเสริม (ไม่บังคับ)
          </Text>
          {documents
            .filter((d) => !d.required)
            .map((doc, index) => (
              <DocumentCard
                key={doc.type}
                item={doc}
                index={index + 2}
                imageUri={images[doc.type]}
                isUploading={uploadingType === doc.type}
                onUpload={() => pickImage(doc.type)}
                onRemove={() => handleRemove(doc.type)}
              />
            ))}

          {/* Tips */}
          <Animated.View
            entering={FadeInDown.delay(300).springify()}
            className="bg-yellow-50 dark:bg-yellow-900/30 rounded-2xl p-4 mt-4"
          >
            <Text className="text-yellow-800 dark:text-yellow-200 font-bold mb-2">
              💡 เคล็ดลับการถ่ายเอกสาร
            </Text>
            <View className="space-y-2">
              <View className="flex-row items-start">
                <Text className="text-yellow-700 dark:text-yellow-300 mr-2">•</Text>
                <Text className="text-yellow-700 dark:text-yellow-300 text-sm flex-1">
                  ถ่ายในที่แสงสว่าง ไม่มีเงา
                </Text>
              </View>
              <View className="flex-row items-start">
                <Text className="text-yellow-700 dark:text-yellow-300 mr-2">•</Text>
                <Text className="text-yellow-700 dark:text-yellow-300 text-sm flex-1">
                  ให้เอกสารอยู่เต็มกรอบ อ่านข้อมูลได้ชัด
                </Text>
              </View>
              <View className="flex-row items-start">
                <Text className="text-yellow-700 dark:text-yellow-300 mr-2">•</Text>
                <Text className="text-yellow-700 dark:text-yellow-300 text-sm flex-1">
                  ไม่ต้องแก้ไขหรือเบลอรูป
                </Text>
              </View>
            </View>
          </Animated.View>
        </ScrollView>

        {/* Submit Button */}
        <View className="absolute bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 px-5 py-4 pb-8">
          <Pressable
            onPress={handleSubmit}
            disabled={uploadingType !== null}
            className={`rounded-xl overflow-hidden ${uploadingType !== null ? 'opacity-50' : ''}`}
          >
            <LinearGradient
              colors={
                uploadedRequiredCount >= requiredCount
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
              {uploadingType !== null ? (
                <ActivityIndicator color="white" />
              ) : (
                <>
                  <Text style={{ fontSize: 20, color: 'white' }}>✓</Text>
                  <Text className="text-white font-bold text-lg ml-2">
                    ส่งเอกสารเพื่อตรวจสอบ
                  </Text>
                </>
              )}
            </LinearGradient>
          </Pressable>

          {uploadedRequiredCount < requiredCount && (
            <Text className="text-center text-gray-500 dark:text-gray-400 text-sm mt-2">
              ต้องอัพโหลดเอกสารจำเป็นอีก {requiredCount - uploadedRequiredCount} รายการ
            </Text>
          )}
        </View>
      </SafeAreaView>
    </View>
  );
}
