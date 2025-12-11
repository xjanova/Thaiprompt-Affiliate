/**
 * KYC Screen - หน้ายืนยันตัวตนแบบ Premium
 * ใช้ expo-camera (CameraView) + expo-file-system สำหรับเซฟรูป
 * แก้ไขปัญหาเข้าถึงพื้นที่เก็บไฟล์ไม่ได้
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
  Modal,
  Platform,
  StyleSheet,
  StatusBar,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { CameraView, CameraType, useCameraPermissions } from 'expo-camera';
import * as ImagePicker from 'expo-image-picker';
import * as FileSystem from 'expo-file-system';
import * as MediaLibrary from 'expo-media-library';
import ReAnimated, { FadeInDown, FadeInUp, ZoomIn } from 'react-native-reanimated';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { getKycStatus, uploadKycImage, confirmKycSubmission } from '@/services/api';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

// KYC Status type
type KycStatus = 'not_submitted' | 'pending' | 'approved' | 'rejected' | 'draft';
type ImageType = 'id_card' | 'selfie';

// =====================================================
// Camera Modal Component - ใช้ CameraView โดยตรง
// =====================================================

const CameraModal = ({
  visible,
  onClose,
  onCapture,
  imageType,
  facing = 'back',
}: {
  visible: boolean;
  onClose: () => void;
  onCapture: (uri: string) => void;
  imageType: ImageType;
  facing?: CameraType;
}) => {
  const cameraRef = useRef<CameraView>(null);
  const [permission, requestPermission] = useCameraPermissions();
  const [isCapturing, setIsCapturing] = useState(false);
  const [cameraFacing, setCameraFacing] = useState<CameraType>(facing);

  useEffect(() => {
    if (visible && !permission?.granted) {
      requestPermission();
    }
  }, [visible]);

  const handleCapture = async () => {
    if (!cameraRef.current || isCapturing) return;

    setIsCapturing(true);
    try {
      // ถ่ายรูป
      const photo = await cameraRef.current.takePictureAsync({
        quality: 0.8,
        skipProcessing: false,
      });

      if (photo?.uri) {
        // สร้าง directory สำหรับเก็บรูป KYC ถ้ายังไม่มี
        const kycDir = `${FileSystem.documentDirectory}kyc/`;
        const dirInfo = await FileSystem.getInfoAsync(kycDir);
        if (!dirInfo.exists) {
          await FileSystem.makeDirectoryAsync(kycDir, { intermediates: true });
        }

        // สร้างชื่อไฟล์ใหม่
        const fileName = `${imageType}_${Date.now()}.jpg`;
        const newUri = `${kycDir}${fileName}`;

        // คัดลอกไฟล์ไปยัง document directory (ที่แอพมี permission เข้าถึงได้)
        await FileSystem.copyAsync({
          from: photo.uri,
          to: newUri,
        });

        // ตรวจสอบว่าไฟล์ถูก save สำเร็จ
        const fileInfo = await FileSystem.getInfoAsync(newUri);
        if (fileInfo.exists) {
          console.log('Photo saved successfully:', newUri);
          onCapture(newUri);
          onClose();
        } else {
          throw new Error('ไม่สามารถบันทึกรูปได้');
        }
      }
    } catch (error) {
      console.error('Capture error:', error);
      Alert.alert('เกิดข้อผิดพลาด', 'ไม่สามารถถ่ายรูปได้ กรุณาลองใหม่');
    } finally {
      setIsCapturing(false);
    }
  };

  const toggleCameraFacing = () => {
    setCameraFacing(current => (current === 'back' ? 'front' : 'back'));
  };

  if (!visible) return null;

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={onClose}>
      <View style={cameraStyles.container}>
        {/* Header */}
        <View style={cameraStyles.header}>
          <Pressable style={cameraStyles.closeButton} onPress={onClose}>
            <Text style={cameraStyles.closeIcon}>✕</Text>
          </Pressable>
          <Text style={cameraStyles.headerTitle}>
            {imageType === 'id_card' ? 'ถ่ายรูปบัตรประชาชน' : 'ถ่ายรูปคู่บัตร (เซลฟี่)'}
          </Text>
          <Pressable style={cameraStyles.flipButton} onPress={toggleCameraFacing}>
            <Text style={cameraStyles.flipIcon}>🔄</Text>
          </Pressable>
        </View>

        {/* Camera */}
        {permission?.granted ? (
          <CameraView
            ref={cameraRef}
            style={cameraStyles.camera}
            facing={cameraFacing}
          >
            {/* Guide overlay */}
            <View style={cameraStyles.overlay}>
              {imageType === 'id_card' ? (
                // กรอบสำหรับบัตรประชาชน (แนวนอน)
                <View style={cameraStyles.idCardFrame}>
                  <View style={cameraStyles.cornerTL} />
                  <View style={cameraStyles.cornerTR} />
                  <View style={cameraStyles.cornerBL} />
                  <View style={cameraStyles.cornerBR} />
                </View>
              ) : (
                // กรอบสำหรับเซลฟี่ (วงกลม + พื้นที่บัตร)
                <View style={cameraStyles.selfieFrame}>
                  <View style={cameraStyles.faceCircle} />
                  <View style={cameraStyles.cardHint}>
                    <Text style={cameraStyles.cardHintText}>🪪 ถือบัตรไว้ตรงนี้</Text>
                  </View>
                </View>
              )}
            </View>
          </CameraView>
        ) : (
          <View style={cameraStyles.noPermission}>
            <Text style={{ fontSize: 60 }}>📷</Text>
            <Text style={cameraStyles.noPermissionText}>ไม่สามารถเข้าถึงกล้องได้</Text>
            <Pressable style={cameraStyles.permissionButton} onPress={requestPermission}>
              <Text style={cameraStyles.permissionButtonText}>ขออนุญาตอีกครั้ง</Text>
            </Pressable>
          </View>
        )}

        {/* Footer */}
        <View style={cameraStyles.footer}>
          <Text style={cameraStyles.hint}>
            {imageType === 'id_card'
              ? 'วางบัตรในกรอบ ให้เห็นข้อมูลชัดเจน'
              : 'ถือบัตรข้างใบหน้า ให้เห็นทั้งหน้าและบัตร'}
          </Text>

          {/* Capture Button */}
          <Pressable
            style={[cameraStyles.captureButton, isCapturing && cameraStyles.captureButtonDisabled]}
            onPress={handleCapture}
            disabled={isCapturing || !permission?.granted}
          >
            {isCapturing ? (
              <ActivityIndicator color="#FFF" size="large" />
            ) : (
              <View style={cameraStyles.captureInner} />
            )}
          </Pressable>
        </View>
      </View>
    </Modal>
  );
};

const cameraStyles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#000',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 50,
    paddingHorizontal: 16,
    paddingBottom: 16,
  },
  closeButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  closeIcon: {
    fontSize: 24,
    color: '#FFF',
  },
  headerTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#FFF',
  },
  flipButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  flipIcon: {
    fontSize: 24,
  },
  camera: {
    flex: 1,
  },
  overlay: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.3)',
  },
  idCardFrame: {
    width: SCREEN_WIDTH - 60,
    height: (SCREEN_WIDTH - 60) * 0.63, // อัตราส่วนบัตร
    borderWidth: 3,
    borderColor: '#3B82F6',
    borderRadius: 12,
    position: 'relative',
  },
  cornerTL: {
    position: 'absolute',
    top: -3,
    left: -3,
    width: 30,
    height: 30,
    borderTopWidth: 5,
    borderLeftWidth: 5,
    borderColor: '#FFF',
    borderTopLeftRadius: 12,
  },
  cornerTR: {
    position: 'absolute',
    top: -3,
    right: -3,
    width: 30,
    height: 30,
    borderTopWidth: 5,
    borderRightWidth: 5,
    borderColor: '#FFF',
    borderTopRightRadius: 12,
  },
  cornerBL: {
    position: 'absolute',
    bottom: -3,
    left: -3,
    width: 30,
    height: 30,
    borderBottomWidth: 5,
    borderLeftWidth: 5,
    borderColor: '#FFF',
    borderBottomLeftRadius: 12,
  },
  cornerBR: {
    position: 'absolute',
    bottom: -3,
    right: -3,
    width: 30,
    height: 30,
    borderBottomWidth: 5,
    borderRightWidth: 5,
    borderColor: '#FFF',
    borderBottomRightRadius: 12,
  },
  selfieFrame: {
    alignItems: 'center',
  },
  faceCircle: {
    width: 200,
    height: 200,
    borderRadius: 100,
    borderWidth: 3,
    borderColor: '#3B82F6',
    borderStyle: 'dashed',
  },
  cardHint: {
    marginTop: 40,
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 20,
  },
  cardHintText: {
    color: '#FFF',
    fontSize: 14,
  },
  noPermission: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 40,
  },
  noPermissionText: {
    color: '#FFF',
    fontSize: 16,
    marginTop: 16,
    marginBottom: 20,
  },
  permissionButton: {
    backgroundColor: '#3B82F6',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 12,
  },
  permissionButtonText: {
    color: '#FFF',
    fontWeight: '600',
  },
  footer: {
    padding: 24,
    alignItems: 'center',
  },
  hint: {
    color: '#FFF',
    fontSize: 14,
    marginBottom: 20,
    textAlign: 'center',
  },
  captureButton: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(255,255,255,0.3)',
    borderWidth: 4,
    borderColor: '#FFF',
    alignItems: 'center',
    justifyContent: 'center',
  },
  captureButtonDisabled: {
    opacity: 0.5,
  },
  captureInner: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: '#FFF',
  },
});

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
    { label: 'บัตรประชาชน', icon: '🪪', completed: hasIdCard },
    { label: 'รูปถ่ายคู่บัตร', icon: '👤', completed: hasSelfie },
    { label: 'ยืนยัน', icon: '✓', completed: false },
  ];

  return (
    <View style={stepStyles.container}>
      {steps.map((step, index) => {
        const isActive = index === currentStep;
        const isCompleted = step.completed;
        const isPast = index < currentStep || isCompleted;

        return (
          <React.Fragment key={index}>
            {/* Step Circle */}
            <View style={stepStyles.stepContainer}>
              <View
                style={[
                  stepStyles.circle,
                  isCompleted && stepStyles.circleCompleted,
                  isActive && !isCompleted && stepStyles.circleActive,
                  !isCompleted && !isActive && (isDark ? stepStyles.circleDark : stepStyles.circleLight),
                ]}
              >
                {isCompleted ? (
                  <LinearGradient
                    colors={['#10B981', '#059669']}
                    style={stepStyles.circleGradient}
                  >
                    <Text style={{ fontSize: 28 }}>✓</Text>
                  </LinearGradient>
                ) : isActive ? (
                  <LinearGradient
                    colors={['#3B82F6', '#2563EB']}
                    style={stepStyles.circleGradient}
                  >
                    <Text style={{ fontSize: 24 }}>{step.icon}</Text>
                  </LinearGradient>
                ) : (
                  <Text style={{ fontSize: 24, color: isDark ? '#6B7280' : '#9CA3AF' }}>
                    {step.icon}
                  </Text>
                )}
              </View>
              <Text
                style={[
                  stepStyles.label,
                  (isActive || isCompleted) && (isDark ? stepStyles.labelActiveDark : stepStyles.labelActive),
                ]}
              >
                {step.label}
              </Text>
            </View>

            {/* Connector Line */}
            {index < steps.length - 1 && (
              <View style={stepStyles.connector}>
                {isPast ? (
                  <LinearGradient
                    colors={['#10B981', '#10B981']}
                    style={stepStyles.connectorFill}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 0 }}
                  />
                ) : (
                  <View style={[stepStyles.connectorEmpty, isDark && stepStyles.connectorEmptyDark]} />
                )}
              </View>
            )}
          </React.Fragment>
        );
      })}
    </View>
  );
};

const stepStyles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 24,
  },
  stepContainer: {
    alignItems: 'center',
    flex: 1,
  },
  circle: {
    width: 56,
    height: 56,
    borderRadius: 28,
    alignItems: 'center',
    justifyContent: 'center',
  },
  circleCompleted: {},
  circleActive: {
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.5,
    shadowRadius: 12,
    elevation: 8,
  },
  circleDark: {
    backgroundColor: '#374151',
  },
  circleLight: {
    backgroundColor: '#E5E7EB',
  },
  circleGradient: {
    width: 56,
    height: 56,
    borderRadius: 28,
    alignItems: 'center',
    justifyContent: 'center',
  },
  label: {
    fontSize: 12,
    marginTop: 8,
    textAlign: 'center',
    color: '#9CA3AF',
  },
  labelActive: {
    color: '#1F2937',
    fontWeight: '600',
  },
  labelActiveDark: {
    color: '#FFF',
    fontWeight: '600',
  },
  connector: {
    flex: 1,
    height: 4,
    marginHorizontal: 4,
    marginTop: -24,
    borderRadius: 2,
    overflow: 'hidden',
  },
  connectorFill: {
    height: '100%',
  },
  connectorEmpty: {
    height: '100%',
    backgroundColor: '#E5E7EB',
  },
  connectorEmptyDark: {
    backgroundColor: '#374151',
  },
});

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
  onTakePhoto,
  onPickFromGallery,
  aspectRatio,
  isDark,
}: {
  title: string;
  subtitle: string;
  icon: string;
  imageUri: string | null;
  isUploaded: boolean;
  isUploading: boolean;
  onTakePhoto: () => void;
  onPickFromGallery: () => void;
  aspectRatio: [number, number];
  isDark: boolean;
}) => {
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

  const height = aspectRatio[0] > aspectRatio[1] ? 180 : 240;

  return (
    <Animated.View style={{ transform: [{ scale: scaleAnim }] }}>
      <View
        style={[
          uploadStyles.card,
          isUploaded && uploadStyles.cardUploaded,
          isDark ? uploadStyles.cardDark : uploadStyles.cardLight,
        ]}
      >
        {/* Header */}
        <View style={uploadStyles.header}>
          <View style={[uploadStyles.iconBox, isUploaded ? uploadStyles.iconBoxUploaded : uploadStyles.iconBoxDefault]}>
            <Text style={{ fontSize: 22 }}>
              {isUploaded ? '✅' : icon === 'card' ? '🪪' : '👤'}
            </Text>
          </View>
          <View style={uploadStyles.headerText}>
            <Text style={[uploadStyles.title, isDark && uploadStyles.titleDark]}>{title}</Text>
            <Text style={uploadStyles.subtitle}>{subtitle}</Text>
          </View>
          {isUploaded && (
            <View style={uploadStyles.badge}>
              <Text style={uploadStyles.badgeText}>อัพโหลดแล้ว</Text>
            </View>
          )}
        </View>

        {/* Preview Area */}
        <View style={[uploadStyles.previewArea, { height }]}>
          {imageUri ? (
            <Image source={{ uri: imageUri }} style={uploadStyles.previewImage} resizeMode="cover" />
          ) : isUploaded ? (
            <View style={uploadStyles.uploadedPlaceholder}>
              <Text style={{ fontSize: 56 }}>✅</Text>
              <Text style={uploadStyles.uploadedText}>รูปภาพถูกอัพโหลดแล้ว</Text>
              <Text style={uploadStyles.uploadedHint}>แตะเพื่อเปลี่ยนรูป</Text>
            </View>
          ) : (
            <View style={uploadStyles.emptyPlaceholder}>
              <Text style={{ fontSize: 48 }}>📷</Text>
              <Text style={[uploadStyles.emptyText, isDark && uploadStyles.emptyTextDark]}>
                เลือกวิธีถ่ายรูป
              </Text>
            </View>
          )}

          {/* Uploading Overlay */}
          {isUploading && (
            <View style={uploadStyles.uploadingOverlay}>
              <View style={uploadStyles.uploadingBox}>
                <ActivityIndicator size="large" color="#3B82F6" />
                <Text style={uploadStyles.uploadingText}>กำลังอัพโหลด...</Text>
              </View>
            </View>
          )}
        </View>

        {/* Action Buttons */}
        <View style={uploadStyles.actions}>
          <Pressable
            style={[uploadStyles.actionButton, uploadStyles.cameraButton]}
            onPress={onTakePhoto}
            disabled={isUploading}
          >
            <Text style={{ fontSize: 20 }}>📷</Text>
            <Text style={uploadStyles.actionButtonText}>ถ่ายรูป</Text>
          </Pressable>

          <Pressable
            style={[uploadStyles.actionButton, uploadStyles.galleryButton]}
            onPress={onPickFromGallery}
            disabled={isUploading}
          >
            <Text style={{ fontSize: 20 }}>🖼️</Text>
            <Text style={uploadStyles.actionButtonTextDark}>แกลเลอรี่</Text>
          </Pressable>
        </View>
      </View>
    </Animated.View>
  );
};

const uploadStyles = StyleSheet.create({
  card: {
    borderRadius: 20,
    marginBottom: 24,
    overflow: 'hidden',
  },
  cardLight: {
    backgroundColor: '#FFF',
  },
  cardDark: {
    backgroundColor: '#1F2937',
  },
  cardUploaded: {
    borderWidth: 2,
    borderColor: '#10B981',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    paddingBottom: 0,
  },
  iconBox: {
    width: 44,
    height: 44,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconBoxDefault: {
    backgroundColor: '#EBF5FF',
  },
  iconBoxUploaded: {
    backgroundColor: '#D1FAE5',
  },
  headerText: {
    flex: 1,
    marginLeft: 12,
  },
  title: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1F2937',
  },
  titleDark: {
    color: '#FFF',
  },
  subtitle: {
    fontSize: 13,
    color: '#9CA3AF',
    marginTop: 2,
  },
  badge: {
    backgroundColor: '#10B981',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  badgeText: {
    color: '#FFF',
    fontSize: 12,
    fontWeight: '600',
  },
  previewArea: {
    margin: 16,
    borderRadius: 16,
    borderWidth: 2,
    borderStyle: 'dashed',
    borderColor: '#E5E7EB',
    overflow: 'hidden',
  },
  previewImage: {
    width: '100%',
    height: '100%',
  },
  uploadedPlaceholder: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#D1FAE5',
  },
  uploadedText: {
    color: '#059669',
    fontWeight: '500',
    marginTop: 8,
  },
  uploadedHint: {
    color: '#10B981',
    fontSize: 13,
    marginTop: 4,
  },
  emptyPlaceholder: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#F9FAFB',
  },
  emptyText: {
    color: '#6B7280',
    marginTop: 12,
    fontSize: 15,
  },
  emptyTextDark: {
    color: '#9CA3AF',
  },
  uploadingOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.6)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  uploadingBox: {
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
  },
  uploadingText: {
    color: '#1F2937',
    marginTop: 12,
    fontWeight: '500',
  },
  actions: {
    flexDirection: 'row',
    padding: 16,
    paddingTop: 0,
    gap: 12,
  },
  actionButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 14,
    borderRadius: 12,
    gap: 8,
  },
  cameraButton: {
    backgroundColor: '#3B82F6',
  },
  galleryButton: {
    backgroundColor: '#E5E7EB',
  },
  actionButtonText: {
    color: '#FFF',
    fontWeight: '600',
  },
  actionButtonTextDark: {
    color: '#374151',
    fontWeight: '600',
  },
});

// =====================================================
// Status Views (Approved, Pending, Rejected)
// =====================================================

const ApprovedView = ({ isDark }: { isDark: boolean }) => (
  <View style={statusStyles.container}>
    <LinearGradient
      colors={['#10B981', '#059669', '#047857']}
      style={statusStyles.iconCircle}
    >
      <Text style={{ fontSize: 56 }}>🛡️</Text>
    </LinearGradient>

    <Text style={[statusStyles.title, isDark && statusStyles.titleDark]}>ยืนยันตัวตนสำเร็จ!</Text>
    <Text style={statusStyles.subtitle}>บัญชีของคุณได้รับการยืนยันแล้ว</Text>

    <View style={[statusStyles.benefitCard, isDark && statusStyles.benefitCardDark]}>
      <Text style={[statusStyles.benefitTitle, isDark && statusStyles.benefitTitleDark]}>ฟีเจอร์ที่ปลดล็อค:</Text>
      {['ถอนเงินได้ไม่จำกัด', 'รับค่าคอมมิชชั่นทันที', 'เข้าถึง Premium Features'].map((item, i) => (
        <View key={i} style={statusStyles.benefitRow}>
          <Text style={{ fontSize: 18 }}>✅</Text>
          <Text style={[statusStyles.benefitText, isDark && statusStyles.benefitTextDark]}>{item}</Text>
        </View>
      ))}
    </View>

    <Pressable onPress={() => router.back()}>
      <LinearGradient colors={['#10B981', '#059669']} style={statusStyles.button}>
        <Text style={statusStyles.buttonText}>กลับหน้าหลัก</Text>
      </LinearGradient>
    </Pressable>
  </View>
);

const PendingView = ({ isDark }: { isDark: boolean }) => (
  <View style={statusStyles.container}>
    <View style={[statusStyles.iconCircle, { backgroundColor: '#FEF3C7' }]}>
      <Text style={{ fontSize: 56 }}>⏰</Text>
    </View>

    <Text style={[statusStyles.title, isDark && statusStyles.titleDark]}>รอการตรวจสอบ</Text>
    <Text style={statusStyles.subtitle}>เอกสารของคุณอยู่ระหว่างการตรวจสอบ</Text>
    <Text style={statusStyles.hint}>⏱️ คาดว่าจะแล้วเสร็จภายใน 24-48 ชั่วโมง</Text>

    <Pressable style={[statusStyles.secondaryButton, isDark && statusStyles.secondaryButtonDark]} onPress={() => router.back()}>
      <Text style={[statusStyles.secondaryButtonText, isDark && statusStyles.secondaryButtonTextDark]}>กลับหน้าหลัก</Text>
    </Pressable>
  </View>
);

const RejectedView = ({ reason, onRetry, isDark }: { reason: string | null; onRetry: () => void; isDark: boolean }) => (
  <View style={statusStyles.container}>
    <View style={[statusStyles.iconCircle, { backgroundColor: '#FEE2E2' }]}>
      <Text style={{ fontSize: 56 }}>❌</Text>
    </View>

    <Text style={[statusStyles.title, isDark && statusStyles.titleDark]}>การยืนยันถูกปฏิเสธ</Text>

    {reason && (
      <View style={statusStyles.reasonCard}>
        <Text style={statusStyles.reasonTitle}>เหตุผล:</Text>
        <Text style={statusStyles.reasonText}>{reason}</Text>
      </View>
    )}

    <Pressable onPress={onRetry}>
      <LinearGradient colors={['#3B82F6', '#2563EB']} style={statusStyles.button}>
        <Text style={statusStyles.buttonText}>ส่งเอกสารใหม่</Text>
      </LinearGradient>
    </Pressable>
  </View>
);

const statusStyles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  iconCircle: {
    width: 128,
    height: 128,
    borderRadius: 64,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 8,
  },
  titleDark: {
    color: '#FFF',
  },
  subtitle: {
    fontSize: 16,
    color: '#6B7280',
    textAlign: 'center',
  },
  hint: {
    fontSize: 14,
    color: '#9CA3AF',
    marginTop: 24,
  },
  benefitCard: {
    backgroundColor: '#F0FDF4',
    borderRadius: 16,
    padding: 20,
    width: '100%',
    marginTop: 24,
    marginBottom: 32,
  },
  benefitCardDark: {
    backgroundColor: '#1F2937',
  },
  benefitTitle: {
    fontSize: 15,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 12,
  },
  benefitTitleDark: {
    color: '#FFF',
  },
  benefitRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 8,
  },
  benefitText: {
    fontSize: 14,
    color: '#374151',
  },
  benefitTextDark: {
    color: '#D1D5DB',
  },
  button: {
    paddingHorizontal: 48,
    paddingVertical: 16,
    borderRadius: 14,
  },
  buttonText: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  secondaryButton: {
    paddingHorizontal: 48,
    paddingVertical: 16,
    borderRadius: 14,
    backgroundColor: '#F3F4F6',
    marginTop: 24,
  },
  secondaryButtonDark: {
    backgroundColor: '#374151',
  },
  secondaryButtonText: {
    color: '#374151',
    fontSize: 16,
    fontWeight: 'bold',
  },
  secondaryButtonTextDark: {
    color: '#FFF',
  },
  reasonCard: {
    backgroundColor: '#FEE2E2',
    borderRadius: 12,
    padding: 16,
    width: '100%',
    marginTop: 16,
    marginBottom: 24,
  },
  reasonTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#B91C1C',
    marginBottom: 4,
  },
  reasonText: {
    fontSize: 14,
    color: '#DC2626',
  },
});

// =====================================================
// Main Component
// =====================================================

export default function KycScreen() {
  const { isAuthenticated } = useAuthStore();
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

  // Camera modal state
  const [showCamera, setShowCamera] = useState(false);
  const [cameraType, setCameraType] = useState<ImageType>('id_card');

  const currentStep = hasSelfie ? 2 : hasIdCard ? 1 : 0;

  // Load KYC status
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

  // Handle image from camera
  const handleCameraCapture = async (uri: string, type: ImageType) => {
    await handleImageSelected(uri, type);
  };

  // Handle image from gallery - ใช้ ImagePicker.launchImageLibraryAsync
  // หมายเหตุ: กล้องใช้ expo-camera (CameraView) แต่ gallery ใช้ expo-image-picker ได้ปกติ
  const handlePickFromGallery = async (type: ImageType) => {
    try {
      // ขอ permission
      const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('ต้องการสิทธิ์', 'กรุณาอนุญาตให้แอพเข้าถึงรูปภาพในแกลเลอรี่');
        return;
      }

      // เปิด gallery เลือกรูป
      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        allowsEditing: true,
        aspect: type === 'id_card' ? [16, 10] : [3, 4],
        quality: 0.8,
      });

      if (!result.canceled && result.assets && result.assets[0]) {
        const selectedUri = result.assets[0].uri;

        // สร้าง directory สำหรับเก็บรูป KYC ถ้ายังไม่มี
        const kycDir = `${FileSystem.documentDirectory}kyc/`;
        const dirInfo = await FileSystem.getInfoAsync(kycDir);
        if (!dirInfo.exists) {
          await FileSystem.makeDirectoryAsync(kycDir, { intermediates: true });
        }

        // สร้างชื่อไฟล์ใหม่
        const fileName = `${type}_${Date.now()}.jpg`;
        const newUri = `${kycDir}${fileName}`;

        // คัดลอกไฟล์ไปยัง document directory (ที่แอพมี permission เข้าถึงได้)
        await FileSystem.copyAsync({
          from: selectedUri,
          to: newUri,
        });

        // ตรวจสอบว่าไฟล์ถูก save สำเร็จ
        const fileInfo = await FileSystem.getInfoAsync(newUri);
        if (fileInfo.exists) {
          console.log('Gallery image saved successfully:', newUri);
          await handleImageSelected(newUri, type);
        } else {
          throw new Error('ไม่สามารถบันทึกรูปได้');
        }
      }
    } catch (error) {
      console.error('Gallery error:', error);
      Alert.alert('เกิดข้อผิดพลาด', 'ไม่สามารถเลือกรูปได้ กรุณาลองใหม่');
    }
  };

  // Upload image
  const handleImageSelected = async (uri: string, type: ImageType) => {
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
      } else {
        Alert.alert('อัพโหลดไม่สำเร็จ', response.message || 'กรุณาลองใหม่');
      }
    } catch (error) {
      console.error('Upload error:', error);
      Alert.alert('เกิดข้อผิดพลาด', 'ไม่สามารถอัพโหลดได้');
    } finally {
      setUploading(false);
    }
  };

  // Submit KYC
  const handleSubmitKyc = async () => {
    if (!hasIdCard || !hasSelfie) {
      Alert.alert('ข้อมูลไม่ครบ', 'กรุณาอัพโหลดรูปให้ครบ');
      return;
    }

    Alert.alert('ยืนยันส่งเอกสาร', 'คุณต้องการส่งเอกสารยืนยันตัวตน?', [
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
              Alert.alert('ผิดพลาด', response.message || 'ส่งไม่สำเร็จ');
            }
          } catch (error) {
            Alert.alert('ผิดพลาด', 'เกิดข้อผิดพลาด');
          } finally {
            setIsLoading(false);
          }
        },
      },
    ]);
  };

  const handleRetry = () => {
    setStatus('not_submitted');
    setIdCardImage(null);
    setSelfieImage(null);
    setHasIdCard(false);
    setHasSelfie(false);
    setRejectionReason(null);
  };

  // Not logged in
  if (!isAuthenticated) {
    return (
      <View style={[mainStyles.container, isDark && mainStyles.containerDark]}>
        <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={isDark ? '#111827' : '#F9FAFB'} />
        <View style={mainStyles.center}>
          <LinearGradient colors={['#3B82F6', '#2563EB']} style={mainStyles.loginIcon}>
            <Text style={{ fontSize: 48 }}>🛡️</Text>
          </LinearGradient>
          <Text style={[mainStyles.loginTitle, isDark && mainStyles.textLight]}>ยืนยันตัวตน (KYC)</Text>
          <Text style={mainStyles.loginSubtitle}>เข้าสู่ระบบเพื่อยืนยันตัวตน</Text>
          <Pressable onPress={() => router.push('/login')}>
            <LinearGradient colors={['#3B82F6', '#2563EB']} style={mainStyles.loginButton}>
              <Text style={mainStyles.loginButtonText}>เข้าสู่ระบบ</Text>
            </LinearGradient>
          </Pressable>
        </View>
      </View>
    );
  }

  // Loading
  if (isLoading && status === 'not_submitted') {
    return (
      <View style={[mainStyles.container, isDark && mainStyles.containerDark]}>
        <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={isDark ? '#111827' : '#F9FAFB'} />
        <View style={mainStyles.center}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={[mainStyles.loadingText, isDark && mainStyles.textLight]}>กำลังโหลด...</Text>
        </View>
      </View>
    );
  }

  return (
    <View style={[mainStyles.container, isDark && mainStyles.containerDark]}>
      <StatusBar barStyle="light-content" backgroundColor={isDark ? '#1E3A8A' : '#3B82F6'} />
      {/* Header */}
      <LinearGradient
        colors={isDark ? ['#1E3A8A', '#1E40AF'] : ['#3B82F6', '#2563EB']}
        style={mainStyles.header}
      >
          <Pressable style={mainStyles.backButton} onPress={() => router.back()}>
            <Text style={mainStyles.backIcon}>←</Text>
          </Pressable>
          <View style={{ flex: 1 }}>
            <Text style={mainStyles.headerTitle}>ยืนยันตัวตน (KYC)</Text>
            <Text style={mainStyles.headerSubtitle}>เพื่อปลดล็อคฟีเจอร์ทั้งหมด</Text>
          </View>
        </LinearGradient>

        {/* Content */}
        {status === 'approved' ? (
          <ApprovedView isDark={isDark} />
        ) : status === 'pending' ? (
          <PendingView isDark={isDark} />
        ) : status === 'rejected' ? (
          <RejectedView reason={rejectionReason} onRetry={handleRetry} isDark={isDark} />
        ) : (
          <ScrollView style={{ flex: 1 }} showsVerticalScrollIndicator={false}>
            <StepIndicator currentStep={currentStep} hasIdCard={hasIdCard} hasSelfie={hasSelfie} isDark={isDark} />

            <View style={{ paddingHorizontal: 16 }}>
              {/* Info Card */}
              <View style={[mainStyles.infoCard, isDark && mainStyles.infoCardDark]}>
                <Text style={{ fontSize: 24 }}>ℹ️</Text>
                <View style={{ flex: 1, marginLeft: 12 }}>
                  <Text style={[mainStyles.infoTitle, isDark && mainStyles.infoTitleDark]}>เอกสารที่ต้องใช้</Text>
                  <Text style={[mainStyles.infoText, isDark && mainStyles.infoTextDark]}>
                    • รูปบัตรประชาชน (ด้านหน้า ชัดเจน){'\n'}• รูปถ่ายคู่บัตร (ถือบัตรข้างหน้า)
                  </Text>
                </View>
              </View>

              {/* ID Card Upload */}
              <UploadCard
                title="รูปบัตรประชาชน"
                subtitle="ด้านหน้า ชัดเจน อ่านตัวอักษรได้"
                icon="card"
                imageUri={idCardImage}
                isUploaded={hasIdCard}
                isUploading={isUploadingIdCard}
                onTakePhoto={() => {
                  setCameraType('id_card');
                  setShowCamera(true);
                }}
                onPickFromGallery={() => handlePickFromGallery('id_card')}
                aspectRatio={[16, 10]}
                isDark={isDark}
              />

              {/* Selfie Upload */}
              <UploadCard
                title="รูปถ่ายคู่บัตร (เซลฟี่)"
                subtitle="ถือบัตรข้างใบหน้า เห็นทั้งหน้าและบัตร"
                icon="person"
                imageUri={selfieImage}
                isUploaded={hasSelfie}
                isUploading={isUploadingSelfie}
                onTakePhoto={() => {
                  setCameraType('selfie');
                  setShowCamera(true);
                }}
                onPickFromGallery={() => handlePickFromGallery('selfie')}
                aspectRatio={[3, 4]}
                isDark={isDark}
              />

              {/* Submit Button */}
              <Pressable
                onPress={handleSubmitKyc}
                disabled={!hasIdCard || !hasSelfie || isLoading}
                style={{ marginBottom: 32 }}
              >
                {hasIdCard && hasSelfie && !isLoading ? (
                  <LinearGradient colors={['#10B981', '#059669']} style={mainStyles.submitButton}>
                    <Text style={{ fontSize: 24 }}>🛡️</Text>
                    <Text style={mainStyles.submitButtonText}>ส่งเอกสารยืนยันตัวตน</Text>
                  </LinearGradient>
                ) : (
                  <View style={[mainStyles.submitButtonDisabled, isDark && mainStyles.submitButtonDisabledDark]}>
                    <Text style={{ fontSize: 24, color: '#9CA3AF' }}>🛡️</Text>
                    <Text style={mainStyles.submitButtonTextDisabled}>
                      {isLoading ? 'กำลังดำเนินการ...' : 'กรุณาอัพโหลดเอกสารให้ครบ'}
                    </Text>
                  </View>
                )}
              </Pressable>

              <Text style={mainStyles.footerHint}>เอกสารจะถูกตรวจสอบภายใน 24-48 ชั่วโมง</Text>
              <View style={{ height: 50 }} />
            </View>
          </ScrollView>
        )}

      {/* Camera Modal */}
      <CameraModal
        visible={showCamera}
        onClose={() => setShowCamera(false)}
        onCapture={(uri) => handleCameraCapture(uri, cameraType)}
        imageType={cameraType}
        facing={cameraType === 'selfie' ? 'front' : 'back'}
      />
    </View>
  );
}

const mainStyles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F9FAFB',
  },
  containerDark: {
    backgroundColor: '#111827',
  },
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  textLight: {
    color: '#FFF',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingTop: 50,
    paddingBottom: 24,
  },
  backButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  backIcon: {
    fontSize: 24,
    color: '#FFF',
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFF',
  },
  headerSubtitle: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 2,
  },
  loginIcon: {
    width: 96,
    height: 96,
    borderRadius: 48,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
  },
  loginTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 8,
  },
  loginSubtitle: {
    fontSize: 16,
    color: '#6B7280',
    marginBottom: 24,
  },
  loginButton: {
    paddingHorizontal: 32,
    paddingVertical: 14,
    borderRadius: 12,
  },
  loginButtonText: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  loadingText: {
    color: '#6B7280',
    marginTop: 12,
  },
  infoCard: {
    flexDirection: 'row',
    backgroundColor: '#EFF6FF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 24,
  },
  infoCardDark: {
    backgroundColor: '#1E3A8A',
  },
  infoTitle: {
    fontSize: 15,
    fontWeight: '600',
    color: '#1E40AF',
    marginBottom: 4,
  },
  infoTitleDark: {
    color: '#93C5FD',
  },
  infoText: {
    fontSize: 13,
    color: '#3B82F6',
    lineHeight: 20,
  },
  infoTextDark: {
    color: '#BFDBFE',
  },
  submitButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 18,
    borderRadius: 16,
    gap: 10,
  },
  submitButtonText: {
    color: '#FFF',
    fontSize: 18,
    fontWeight: 'bold',
  },
  submitButtonDisabled: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 18,
    borderRadius: 16,
    backgroundColor: '#E5E7EB',
    gap: 10,
  },
  submitButtonDisabledDark: {
    backgroundColor: '#374151',
  },
  submitButtonTextDisabled: {
    color: '#9CA3AF',
    fontSize: 16,
    fontWeight: '600',
  },
  footerHint: {
    textAlign: 'center',
    color: '#9CA3AF',
    fontSize: 13,
  },
});
