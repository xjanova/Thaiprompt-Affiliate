/**
 * KYC Screen - เขียนใหม่ทั้งหมด
 * - Design ใหม่ที่สวยงาม
 * - Upload ใช้ fetch API ตรงๆ
 * - ไม่ใช้ NativeWind (ใช้ StyleSheet ล้วน)
 */

import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Alert,
  Image,
  ActivityIndicator,
  Dimensions,
  StyleSheet,
  StatusBar,
  Modal,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { CameraView, useCameraPermissions } from 'expo-camera';
import * as ImagePicker from 'expo-image-picker';
import * as FileSystem from 'expo-file-system';
import * as SecureStore from 'expo-secure-store';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { API_BASE_URL, STORAGE_KEYS } from '@/constants';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

type KycStatus = 'not_submitted' | 'pending' | 'approved' | 'rejected';
type ImageType = 'id_card' | 'selfie';

// =====================================================
// API Functions - เขียนตรงๆ ไม่ผ่าน api.ts
// =====================================================

const getToken = async (): Promise<string | null> => {
  return await SecureStore.getItemAsync(STORAGE_KEYS.AUTH_TOKEN);
};

const fetchKycStatus = async () => {
  const token = await getToken();
  if (!token) return null;

  try {
    const response = await fetch(`${API_BASE_URL}/kyc/status`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    });
    const data = await response.json();
    console.log('📋 KYC Status:', data);
    return data;
  } catch (error) {
    console.error('❌ Fetch KYC status error:', error);
    return null;
  }
};

const uploadKycFile = async (
  imageUri: string,
  type: ImageType
): Promise<{ success: boolean; message?: string; data?: any }> => {
  const token = await getToken();
  if (!token) {
    return { success: false, message: 'กรุณาเข้าสู่ระบบใหม่' };
  }

  try {
    // อ่านไฟล์เป็น base64
    const base64 = await FileSystem.readAsStringAsync(imageUri, {
      encoding: FileSystem.EncodingType.Base64,
    });

    // หา mime type
    const filename = imageUri.split('/').pop() || `${type}.jpg`;
    const ext = filename.split('.').pop()?.toLowerCase() || 'jpg';
    const mimeType = ext === 'png' ? 'image/png' : 'image/jpeg';

    console.log(`📤 Uploading ${type}...`, { filename, mimeType, size: base64.length });

    // สร้าง FormData
    const formData = new FormData();
    formData.append('image', {
      uri: imageUri,
      name: filename,
      type: mimeType,
    } as any);
    formData.append('type', type);

    const response = await fetch(`${API_BASE_URL}/kyc/upload`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
      body: formData,
    });

    const result = await response.json();
    console.log(`📤 Upload ${type} result:`, response.status, result);

    if (!response.ok) {
      return {
        success: false,
        message: result.message || `อัพโหลดไม่สำเร็จ (${response.status})`,
      };
    }

    return result;
  } catch (error: any) {
    console.error(`❌ Upload ${type} error:`, error);
    return {
      success: false,
      message: error.message || 'เกิดข้อผิดพลาดในการอัพโหลด',
    };
  }
};

const submitKyc = async (): Promise<{ success: boolean; message?: string }> => {
  const token = await getToken();
  if (!token) {
    return { success: false, message: 'กรุณาเข้าสู่ระบบใหม่' };
  }

  try {
    // ใช้ /kyc/confirm แทน /kyc/submit เพราะรูปถูก upload แยกไว้แล้ว
    const response = await fetch(`${API_BASE_URL}/kyc/confirm`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
    });

    const result = await response.json();
    console.log('📋 Submit KYC result:', result);
    return result;
  } catch (error: any) {
    console.error('❌ Submit KYC error:', error);
    return { success: false, message: 'เกิดข้อผิดพลาด' };
  }
};

// =====================================================
// Main Component
// =====================================================

export default function KycScreen() {
  const { isAuthenticated } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  // States
  const [status, setStatus] = useState<KycStatus>('not_submitted');
  const [isLoading, setIsLoading] = useState(true);
  const [idCardUri, setIdCardUri] = useState<string | null>(null);
  const [selfieUri, setSelfieUri] = useState<string | null>(null);
  const [hasIdCard, setHasIdCard] = useState(false);
  const [hasSelfie, setHasSelfie] = useState(false);
  const [isUploadingIdCard, setIsUploadingIdCard] = useState(false);
  const [isUploadingSelfie, setIsUploadingSelfie] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [rejectionReason, setRejectionReason] = useState<string | null>(null);

  // Camera states
  const [showCamera, setShowCamera] = useState(false);
  const [cameraType, setCameraType] = useState<ImageType>('id_card');
  const [cameraPermission, requestCameraPermission] = useCameraPermissions();
  const cameraRef = useRef<CameraView>(null);

  // Load KYC status on mount
  useEffect(() => {
    loadStatus();
  }, []);

  const loadStatus = async () => {
    setIsLoading(true);
    const result = await fetchKycStatus();
    if (result?.success && result.data) {
      setStatus(result.data.status || 'not_submitted');
      setHasIdCard(result.data.submission?.hasIdCard || false);
      setHasSelfie(result.data.submission?.hasSelfie || false);
      setRejectionReason(result.data.submission?.rejectionReason || null);
    }
    setIsLoading(false);
  };

  // Pick image from gallery
  const pickImage = async (type: ImageType) => {
    try {
      const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('ต้องการสิทธิ์', 'กรุณาอนุญาตให้เข้าถึงรูปภาพ');
        return;
      }

      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        allowsEditing: true,
        aspect: type === 'id_card' ? [16, 10] : [3, 4],
        quality: 0.8,
      });

      if (!result.canceled && result.assets[0]) {
        await handleUpload(result.assets[0].uri, type);
      }
    } catch (error) {
      console.error('Pick image error:', error);
      Alert.alert('เกิดข้อผิดพลาด', 'ไม่สามารถเลือกรูปได้');
    }
  };

  // Take photo with camera
  const takePhoto = async () => {
    if (!cameraRef.current) return;

    try {
      const photo = await cameraRef.current.takePictureAsync({
        quality: 0.8,
      });

      if (photo?.uri) {
        setShowCamera(false);
        await handleUpload(photo.uri, cameraType);
      }
    } catch (error) {
      console.error('Take photo error:', error);
      Alert.alert('เกิดข้อผิดพลาด', 'ไม่สามารถถ่ายรูปได้');
    }
  };

  // Handle upload
  const handleUpload = async (uri: string, type: ImageType) => {
    const setUploading = type === 'id_card' ? setIsUploadingIdCard : setIsUploadingSelfie;
    const setUri = type === 'id_card' ? setIdCardUri : setSelfieUri;
    const setHas = type === 'id_card' ? setHasIdCard : setHasSelfie;

    setUploading(true);
    setUri(uri);

    const result = await uploadKycFile(uri, type);

    if (result.success) {
      setHas(true);
      Alert.alert('สำเร็จ', `อัพโหลด${type === 'id_card' ? 'บัตรประชาชน' : 'รูปเซลฟี่'}เรียบร้อย`);
    } else {
      setUri(null);
      Alert.alert('ผิดพลาด', result.message || 'อัพโหลดไม่สำเร็จ');
    }

    setUploading(false);
  };

  // Submit KYC
  const handleSubmit = async () => {
    if (!hasIdCard || !hasSelfie) {
      Alert.alert('ข้อมูลไม่ครบ', 'กรุณาอัพโหลดรูปบัตรประชาชนและรูปเซลฟี่');
      return;
    }

    Alert.alert('ยืนยันส่งเอกสาร', 'คุณต้องการส่งเอกสารยืนยันตัวตน?', [
      { text: 'ยกเลิก', style: 'cancel' },
      {
        text: 'ส่งเอกสาร',
        onPress: async () => {
          setIsSubmitting(true);
          const result = await submitKyc();
          setIsSubmitting(false);

          if (result.success) {
            setStatus('pending');
            Alert.alert('สำเร็จ', 'ส่งเอกสารเรียบร้อย รอการตรวจสอบภายใน 24-48 ชั่วโมง');
          } else {
            Alert.alert('ผิดพลาด', result.message || 'ส่งเอกสารไม่สำเร็จ');
          }
        },
      },
    ]);
  };

  // Open camera modal
  const openCamera = async (type: ImageType) => {
    if (!cameraPermission?.granted) {
      const result = await requestCameraPermission();
      if (!result.granted) {
        Alert.alert('ต้องการสิทธิ์', 'กรุณาอนุญาตให้ใช้กล้อง');
        return;
      }
    }
    setCameraType(type);
    setShowCamera(true);
  };

  // =====================================================
  // Render - Not Authenticated
  // =====================================================
  if (!isAuthenticated) {
    return (
      <View style={[styles.container, isDark && styles.containerDark]}>
        <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />
        <View style={styles.centerContent}>
          <Text style={styles.bigIcon}>🛡️</Text>
          <Text style={[styles.title, isDark && styles.textWhite]}>ยืนยันตัวตน (KYC)</Text>
          <Text style={styles.subtitle}>กรุณาเข้าสู่ระบบก่อน</Text>
          <Pressable style={styles.loginBtn} onPress={() => router.push('/login')}>
            <Text style={styles.loginBtnText}>เข้าสู่ระบบ</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  // =====================================================
  // Render - Loading
  // =====================================================
  if (isLoading) {
    return (
      <View style={[styles.container, isDark && styles.containerDark]}>
        <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />
        <View style={styles.centerContent}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={[styles.subtitle, { marginTop: 16 }]}>กำลังโหลด...</Text>
        </View>
      </View>
    );
  }

  // =====================================================
  // Render - Approved
  // =====================================================
  if (status === 'approved') {
    return (
      <View style={[styles.container, isDark && styles.containerDark]}>
        <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />
        <View style={styles.centerContent}>
          <View style={styles.successCircle}>
            <Text style={{ fontSize: 60 }}>✅</Text>
          </View>
          <Text style={[styles.title, isDark && styles.textWhite, { marginTop: 24 }]}>
            ยืนยันตัวตนสำเร็จ!
          </Text>
          <Text style={styles.subtitle}>บัญชีของคุณได้รับการยืนยันแล้ว</Text>

          <View style={[styles.benefitBox, isDark && styles.benefitBoxDark]}>
            <Text style={[styles.benefitTitle, isDark && styles.textWhite]}>ฟีเจอร์ที่ปลดล็อค:</Text>
            {['✓ ถอนเงินได้ไม่จำกัด', '✓ รับคอมมิชชั่นทันที', '✓ Premium Features'].map((item, i) => (
              <Text key={i} style={[styles.benefitItem, isDark && styles.textGray300]}>{item}</Text>
            ))}
          </View>

          <Pressable style={styles.primaryBtn} onPress={() => router.back()}>
            <LinearGradient colors={['#10B981', '#059669']} style={styles.btnGradient}>
              <Text style={styles.btnText}>กลับหน้าหลัก</Text>
            </LinearGradient>
          </Pressable>
        </View>
      </View>
    );
  }

  // =====================================================
  // Render - Pending
  // =====================================================
  if (status === 'pending') {
    return (
      <View style={[styles.container, isDark && styles.containerDark]}>
        <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />
        <View style={styles.centerContent}>
          <View style={[styles.pendingCircle]}>
            <Text style={{ fontSize: 60 }}>⏳</Text>
          </View>
          <Text style={[styles.title, isDark && styles.textWhite, { marginTop: 24 }]}>
            รอการตรวจสอบ
          </Text>
          <Text style={styles.subtitle}>เอกสารของคุณอยู่ระหว่างการตรวจสอบ</Text>
          <Text style={[styles.subtitle, { marginTop: 16 }]}>⏱️ คาดว่าจะแล้วเสร็จภายใน 24-48 ชั่วโมง</Text>

          <Pressable style={[styles.secondaryBtn, isDark && styles.secondaryBtnDark]} onPress={() => router.back()}>
            <Text style={[styles.secondaryBtnText, isDark && styles.textWhite]}>กลับหน้าหลัก</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  // =====================================================
  // Render - Rejected
  // =====================================================
  if (status === 'rejected') {
    return (
      <View style={[styles.container, isDark && styles.containerDark]}>
        <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />
        <View style={styles.centerContent}>
          <View style={styles.rejectedCircle}>
            <Text style={{ fontSize: 60 }}>❌</Text>
          </View>
          <Text style={[styles.title, isDark && styles.textWhite, { marginTop: 24 }]}>
            ถูกปฏิเสธ
          </Text>

          {rejectionReason && (
            <View style={styles.reasonBox}>
              <Text style={styles.reasonTitle}>เหตุผล:</Text>
              <Text style={styles.reasonText}>{rejectionReason}</Text>
            </View>
          )}

          <Pressable
            style={styles.primaryBtn}
            onPress={() => {
              setStatus('not_submitted');
              setHasIdCard(false);
              setHasSelfie(false);
              setIdCardUri(null);
              setSelfieUri(null);
            }}
          >
            <LinearGradient colors={['#3B82F6', '#2563EB']} style={styles.btnGradient}>
              <Text style={styles.btnText}>ส่งเอกสารใหม่</Text>
            </LinearGradient>
          </Pressable>
        </View>
      </View>
    );
  }

  // =====================================================
  // Render - Upload Form
  // =====================================================
  return (
    <View style={[styles.container, isDark && styles.containerDark]}>
      <StatusBar barStyle="light-content" />

      {/* Header */}
      <LinearGradient colors={['#3B82F6', '#2563EB']} style={styles.header}>
        <Pressable style={styles.backBtn} onPress={() => router.back()}>
          <Text style={styles.backIcon}>←</Text>
        </Pressable>
        <View style={{ flex: 1 }}>
          <Text style={styles.headerTitle}>ยืนยันตัวตน (KYC)</Text>
          <Text style={styles.headerSubtitle}>เพื่อปลดล็อคฟีเจอร์ทั้งหมด</Text>
        </View>
      </LinearGradient>

      <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
        {/* Step Indicator */}
        <View style={styles.stepRow}>
          <View style={[styles.stepCircle, hasIdCard && styles.stepDone]}>
            <Text style={styles.stepText}>{hasIdCard ? '✓' : '1'}</Text>
          </View>
          <View style={[styles.stepLine, hasIdCard && styles.stepLineDone]} />
          <View style={[styles.stepCircle, hasSelfie && styles.stepDone]}>
            <Text style={styles.stepText}>{hasSelfie ? '✓' : '2'}</Text>
          </View>
          <View style={[styles.stepLine, hasSelfie && styles.stepLineDone]} />
          <View style={[styles.stepCircle, hasIdCard && hasSelfie && styles.stepDone]}>
            <Text style={styles.stepText}>{hasIdCard && hasSelfie ? '✓' : '3'}</Text>
          </View>
        </View>

        {/* Info Box */}
        <View style={[styles.infoBox, isDark && styles.infoBoxDark]}>
          <Text style={{ fontSize: 24 }}>ℹ️</Text>
          <View style={{ flex: 1, marginLeft: 12 }}>
            <Text style={[styles.infoTitle, isDark && styles.textWhite]}>เอกสารที่ต้องใช้</Text>
            <Text style={[styles.infoText, isDark && styles.textGray300]}>
              1. รูปบัตรประชาชน (ด้านหน้า ชัดเจน){'\n'}
              2. รูปเซลฟี่ถือบัตร (เห็นหน้าและบัตร)
            </Text>
          </View>
        </View>

        {/* ID Card Upload */}
        <View style={[styles.uploadCard, isDark && styles.uploadCardDark, hasIdCard && styles.uploadCardDone]}>
          <View style={styles.uploadHeader}>
            <Text style={{ fontSize: 28 }}>{hasIdCard ? '✅' : '🪪'}</Text>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <Text style={[styles.uploadTitle, isDark && styles.textWhite]}>รูปบัตรประชาชน</Text>
              <Text style={styles.uploadSubtitle}>ด้านหน้า ชัดเจน</Text>
            </View>
            {hasIdCard && (
              <View style={styles.doneBadge}>
                <Text style={styles.doneBadgeText}>อัพโหลดแล้ว</Text>
              </View>
            )}
          </View>

          {/* Preview */}
          <View style={styles.previewBox}>
            {isUploadingIdCard ? (
              <View style={styles.uploadingBox}>
                <ActivityIndicator size="large" color="#3B82F6" />
                <Text style={styles.uploadingText}>กำลังอัพโหลด...</Text>
              </View>
            ) : idCardUri ? (
              <Image source={{ uri: idCardUri }} style={styles.previewImage} resizeMode="cover" />
            ) : hasIdCard ? (
              <View style={styles.donePreview}>
                <Text style={{ fontSize: 48 }}>✅</Text>
                <Text style={styles.doneText}>อัพโหลดแล้ว</Text>
              </View>
            ) : (
              <View style={styles.emptyPreview}>
                <Text style={{ fontSize: 40 }}>📷</Text>
                <Text style={[styles.emptyText, isDark && styles.textGray400]}>เลือกวิธีถ่ายรูป</Text>
              </View>
            )}
          </View>

          {/* Action Buttons */}
          <View style={styles.uploadActions}>
            <Pressable
              style={styles.cameraBtn}
              onPress={() => openCamera('id_card')}
              disabled={isUploadingIdCard}
            >
              <Text style={{ fontSize: 18 }}>📷</Text>
              <Text style={styles.cameraBtnText}>ถ่ายรูป</Text>
            </Pressable>
            <Pressable
              style={styles.galleryBtn}
              onPress={() => pickImage('id_card')}
              disabled={isUploadingIdCard}
            >
              <Text style={{ fontSize: 18 }}>🖼️</Text>
              <Text style={styles.galleryBtnText}>แกลเลอรี่</Text>
            </Pressable>
          </View>
        </View>

        {/* Selfie Upload */}
        <View style={[styles.uploadCard, isDark && styles.uploadCardDark, hasSelfie && styles.uploadCardDone]}>
          <View style={styles.uploadHeader}>
            <Text style={{ fontSize: 28 }}>{hasSelfie ? '✅' : '🤳'}</Text>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <Text style={[styles.uploadTitle, isDark && styles.textWhite]}>รูปเซลฟี่ถือบัตร</Text>
              <Text style={styles.uploadSubtitle}>เห็นหน้าและบัตรชัดเจน</Text>
            </View>
            {hasSelfie && (
              <View style={styles.doneBadge}>
                <Text style={styles.doneBadgeText}>อัพโหลดแล้ว</Text>
              </View>
            )}
          </View>

          {/* Preview */}
          <View style={[styles.previewBox, { height: 240 }]}>
            {isUploadingSelfie ? (
              <View style={styles.uploadingBox}>
                <ActivityIndicator size="large" color="#3B82F6" />
                <Text style={styles.uploadingText}>กำลังอัพโหลด...</Text>
              </View>
            ) : selfieUri ? (
              <Image source={{ uri: selfieUri }} style={styles.previewImage} resizeMode="cover" />
            ) : hasSelfie ? (
              <View style={styles.donePreview}>
                <Text style={{ fontSize: 48 }}>✅</Text>
                <Text style={styles.doneText}>อัพโหลดแล้ว</Text>
              </View>
            ) : (
              <View style={styles.emptyPreview}>
                <Text style={{ fontSize: 40 }}>🤳</Text>
                <Text style={[styles.emptyText, isDark && styles.textGray400]}>ถ่ายเซลฟี่ถือบัตร</Text>
              </View>
            )}
          </View>

          {/* Action Buttons */}
          <View style={styles.uploadActions}>
            <Pressable
              style={styles.cameraBtn}
              onPress={() => openCamera('selfie')}
              disabled={isUploadingSelfie}
            >
              <Text style={{ fontSize: 18 }}>📷</Text>
              <Text style={styles.cameraBtnText}>ถ่ายรูป</Text>
            </Pressable>
            <Pressable
              style={styles.galleryBtn}
              onPress={() => pickImage('selfie')}
              disabled={isUploadingSelfie}
            >
              <Text style={{ fontSize: 18 }}>🖼️</Text>
              <Text style={styles.galleryBtnText}>แกลเลอรี่</Text>
            </Pressable>
          </View>
        </View>

        {/* Submit Button */}
        <Pressable
          style={[styles.submitBtn, (!hasIdCard || !hasSelfie) && styles.submitBtnDisabled]}
          onPress={handleSubmit}
          disabled={!hasIdCard || !hasSelfie || isSubmitting}
        >
          {isSubmitting ? (
            <ActivityIndicator color="#FFF" />
          ) : hasIdCard && hasSelfie ? (
            <LinearGradient colors={['#10B981', '#059669']} style={styles.btnGradient}>
              <Text style={{ fontSize: 24 }}>🛡️</Text>
              <Text style={styles.btnText}>ส่งเอกสารยืนยันตัวตน</Text>
            </LinearGradient>
          ) : (
            <View style={[styles.btnGradient, { backgroundColor: '#374151' }]}>
              <Text style={{ fontSize: 24 }}>🛡️</Text>
              <Text style={[styles.btnText, { color: '#9CA3AF' }]}>กรุณาอัพโหลดให้ครบ</Text>
            </View>
          )}
        </Pressable>

        <Text style={styles.footerHint}>เอกสารจะถูกตรวจสอบภายใน 24-48 ชั่วโมง</Text>
        <View style={{ height: 50 }} />
      </ScrollView>

      {/* Camera Modal */}
      <Modal visible={showCamera} animationType="slide" onRequestClose={() => setShowCamera(false)}>
        <View style={styles.cameraContainer}>
          <View style={styles.cameraHeader}>
            <Pressable style={styles.cameraCloseBtn} onPress={() => setShowCamera(false)}>
              <Text style={styles.cameraCloseText}>✕</Text>
            </Pressable>
            <Text style={styles.cameraTitle}>
              {cameraType === 'id_card' ? 'ถ่ายรูปบัตรประชาชน' : 'ถ่ายเซลฟี่ถือบัตร'}
            </Text>
            <View style={{ width: 44 }} />
          </View>

          {cameraPermission?.granted ? (
            <CameraView
              ref={cameraRef}
              style={styles.camera}
              facing={cameraType === 'selfie' ? 'front' : 'back'}
            >
              <View style={styles.cameraOverlay}>
                {cameraType === 'id_card' ? (
                  <View style={styles.idCardFrame} />
                ) : (
                  <View style={styles.selfieFrame} />
                )}
              </View>
            </CameraView>
          ) : (
            <View style={styles.noPermission}>
              <Text style={{ fontSize: 48 }}>📷</Text>
              <Text style={styles.noPermissionText}>ไม่สามารถเข้าถึงกล้องได้</Text>
            </View>
          )}

          <View style={styles.cameraFooter}>
            <Text style={styles.cameraHint}>
              {cameraType === 'id_card'
                ? 'วางบัตรในกรอบ ให้เห็นข้อมูลชัดเจน'
                : 'ถือบัตรข้างใบหน้า ให้เห็นทั้งหน้าและบัตร'}
            </Text>
            <Pressable style={styles.captureBtn} onPress={takePhoto}>
              <View style={styles.captureBtnInner} />
            </Pressable>
          </View>
        </View>
      </Modal>
    </View>
  );
}

// =====================================================
// Styles
// =====================================================

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  containerDark: { backgroundColor: '#111827' },
  centerContent: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
  bigIcon: { fontSize: 80 },
  title: { fontSize: 24, fontWeight: 'bold', color: '#1F2937', marginTop: 16 },
  textWhite: { color: '#FFFFFF' },
  textGray300: { color: '#D1D5DB' },
  textGray400: { color: '#9CA3AF' },
  subtitle: { fontSize: 16, color: '#6B7280', textAlign: 'center', marginTop: 8 },
  loginBtn: { backgroundColor: '#3B82F6', paddingHorizontal: 32, paddingVertical: 14, borderRadius: 12, marginTop: 24 },
  loginBtnText: { color: '#FFF', fontWeight: 'bold', fontSize: 16 },

  // Success/Pending/Rejected
  successCircle: { width: 120, height: 120, borderRadius: 60, backgroundColor: '#D1FAE5', justifyContent: 'center', alignItems: 'center' },
  pendingCircle: { width: 120, height: 120, borderRadius: 60, backgroundColor: '#FEF3C7', justifyContent: 'center', alignItems: 'center' },
  rejectedCircle: { width: 120, height: 120, borderRadius: 60, backgroundColor: '#FEE2E2', justifyContent: 'center', alignItems: 'center' },
  benefitBox: { backgroundColor: '#F0FDF4', borderRadius: 16, padding: 20, width: '100%', marginTop: 24 },
  benefitBoxDark: { backgroundColor: '#1F2937' },
  benefitTitle: { fontSize: 15, fontWeight: '600', color: '#1F2937', marginBottom: 12 },
  benefitItem: { fontSize: 14, color: '#374151', marginBottom: 6 },
  reasonBox: { backgroundColor: '#FEE2E2', borderRadius: 12, padding: 16, width: '100%', marginTop: 16 },
  reasonTitle: { fontSize: 14, fontWeight: '600', color: '#B91C1C', marginBottom: 4 },
  reasonText: { fontSize: 14, color: '#DC2626' },

  // Buttons
  primaryBtn: { borderRadius: 14, overflow: 'hidden', marginTop: 24, width: '100%' },
  btnGradient: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 16, gap: 10 },
  btnText: { color: '#FFF', fontSize: 16, fontWeight: 'bold' },
  secondaryBtn: { backgroundColor: '#E5E7EB', paddingHorizontal: 32, paddingVertical: 16, borderRadius: 14, marginTop: 24 },
  secondaryBtnDark: { backgroundColor: '#374151' },
  secondaryBtnText: { color: '#374151', fontWeight: 'bold', fontSize: 16 },

  // Header
  header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingTop: 50, paddingBottom: 24 },
  backBtn: { width: 44, height: 44, borderRadius: 22, backgroundColor: 'rgba(255,255,255,0.2)', justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  backIcon: { fontSize: 24, color: '#FFF' },
  headerTitle: { fontSize: 20, fontWeight: 'bold', color: '#FFF' },
  headerSubtitle: { fontSize: 14, color: 'rgba(255,255,255,0.8)', marginTop: 2 },

  // Content
  content: { flex: 1, paddingHorizontal: 16 },

  // Steps
  stepRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 24 },
  stepCircle: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#E5E7EB', justifyContent: 'center', alignItems: 'center' },
  stepDone: { backgroundColor: '#10B981' },
  stepText: { fontSize: 16, fontWeight: 'bold', color: '#FFF' },
  stepLine: { width: 50, height: 4, backgroundColor: '#E5E7EB', marginHorizontal: 8 },
  stepLineDone: { backgroundColor: '#10B981' },

  // Info Box
  infoBox: { flexDirection: 'row', backgroundColor: '#EFF6FF', borderRadius: 16, padding: 16, marginBottom: 20 },
  infoBoxDark: { backgroundColor: '#1E3A8A' },
  infoTitle: { fontSize: 15, fontWeight: '600', color: '#1E40AF', marginBottom: 4 },
  infoText: { fontSize: 13, color: '#3B82F6', lineHeight: 20 },

  // Upload Card
  uploadCard: { backgroundColor: '#FFF', borderRadius: 20, marginBottom: 20, overflow: 'hidden' },
  uploadCardDark: { backgroundColor: '#1F2937' },
  uploadCardDone: { borderWidth: 2, borderColor: '#10B981' },
  uploadHeader: { flexDirection: 'row', alignItems: 'center', padding: 16 },
  uploadTitle: { fontSize: 16, fontWeight: '600', color: '#1F2937' },
  uploadSubtitle: { fontSize: 13, color: '#9CA3AF', marginTop: 2 },
  doneBadge: { backgroundColor: '#10B981', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12 },
  doneBadgeText: { color: '#FFF', fontSize: 12, fontWeight: '600' },

  // Preview
  previewBox: { marginHorizontal: 16, height: 180, borderRadius: 16, borderWidth: 2, borderStyle: 'dashed', borderColor: '#E5E7EB', overflow: 'hidden' },
  previewImage: { width: '100%', height: '100%' },
  uploadingBox: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: 'rgba(0,0,0,0.05)' },
  uploadingText: { marginTop: 12, color: '#6B7280' },
  donePreview: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#D1FAE5' },
  doneText: { color: '#059669', fontWeight: '500', marginTop: 8 },
  emptyPreview: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F9FAFB' },
  emptyText: { color: '#6B7280', marginTop: 12 },

  // Upload Actions
  uploadActions: { flexDirection: 'row', padding: 16, gap: 12 },
  cameraBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', backgroundColor: '#3B82F6', paddingVertical: 14, borderRadius: 12, gap: 8 },
  cameraBtnText: { color: '#FFF', fontWeight: '600' },
  galleryBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', backgroundColor: '#E5E7EB', paddingVertical: 14, borderRadius: 12, gap: 8 },
  galleryBtnText: { color: '#374151', fontWeight: '600' },

  // Submit
  submitBtn: { borderRadius: 16, overflow: 'hidden', marginTop: 8 },
  submitBtnDisabled: { opacity: 1 },
  footerHint: { textAlign: 'center', color: '#9CA3AF', fontSize: 13, marginTop: 16 },

  // Camera Modal
  cameraContainer: { flex: 1, backgroundColor: '#000' },
  cameraHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingTop: 50, paddingHorizontal: 16, paddingBottom: 16 },
  cameraCloseBtn: { width: 44, height: 44, borderRadius: 22, backgroundColor: 'rgba(255,255,255,0.2)', justifyContent: 'center', alignItems: 'center' },
  cameraCloseText: { fontSize: 24, color: '#FFF' },
  cameraTitle: { fontSize: 16, fontWeight: '600', color: '#FFF' },
  camera: { flex: 1 },
  cameraOverlay: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: 'rgba(0,0,0,0.3)' },
  idCardFrame: { width: SCREEN_WIDTH - 60, height: (SCREEN_WIDTH - 60) * 0.63, borderWidth: 3, borderColor: '#3B82F6', borderRadius: 12 },
  selfieFrame: { width: 200, height: 200, borderWidth: 3, borderColor: '#3B82F6', borderRadius: 100, borderStyle: 'dashed' },
  noPermission: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  noPermissionText: { color: '#FFF', marginTop: 16 },
  cameraFooter: { padding: 24, alignItems: 'center' },
  cameraHint: { color: '#FFF', fontSize: 14, marginBottom: 20, textAlign: 'center' },
  captureBtn: { width: 80, height: 80, borderRadius: 40, backgroundColor: 'rgba(255,255,255,0.3)', borderWidth: 4, borderColor: '#FFF', justifyContent: 'center', alignItems: 'center' },
  captureBtnInner: { width: 60, height: 60, borderRadius: 30, backgroundColor: '#FFF' },
});
