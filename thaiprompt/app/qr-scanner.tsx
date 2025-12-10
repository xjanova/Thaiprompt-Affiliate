/**
 * QR Scanner Screen - หน้าสแกน QR Code
 * ปรับปรุงใหม่ให้รองรับ error handling และ UI ที่สวยงาม
 *
 * Features:
 * - เปิดกล้องอัตโนมัติ
 * - สแกน QR Code และ Barcode
 * - UI สวยงามแบบ Glassmorphism
 * - Error handling ที่ดีขึ้น
 */

import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Alert,
  Linking,
  ActivityIndicator,
  StatusBar,
  Dimensions,
  Animated,
  Platform,
} from 'react-native';
import { Camera, CameraView, useCameraPermissions, BarcodeScanningResult } from 'expo-camera';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';
import { router } from 'expo-router';
import * as Clipboard from 'expo-clipboard';
import * as Haptics from 'expo-haptics';

// Import Animated Background
import { AnimatedBackground } from '@/components/AnimatedBackground';

// Import SVG Icons
import {
  ArrowBackIcon,
  FlashIcon,
  FlashOffIcon,
  CameraIcon,
  CameraRotateIcon,
  CheckCircleIcon,
  CopyIcon,
  ExternalLinkIcon,
  QRCodeIcon,
  AlertCircleIcon,
  RefreshIcon,
} from '@/components/icons';

const { width: SCREEN_WIDTH } = Dimensions.get('window');
const SCAN_AREA_SIZE = SCREEN_WIDTH * 0.7;

export default function QRScannerScreen() {
  const [permission, requestPermission] = useCameraPermissions();
  const [scanned, setScanned] = useState(false);
  const [scannedData, setScannedData] = useState<string | null>(null);
  const [isFlashOn, setIsFlashOn] = useState(false);
  const [facing, setFacing] = useState<'front' | 'back'>('back');
  const [cameraReady, setCameraReady] = useState(false);
  const [cameraError, setCameraError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Animation สำหรับ scan line
  const scanLineAnim = useRef(new Animated.Value(0)).current;
  const pulseAnim = useRef(new Animated.Value(1)).current;

  // เริ่ม animation scan line
  useEffect(() => {
    if (!scanned && cameraReady) {
      Animated.loop(
        Animated.sequence([
          Animated.timing(scanLineAnim, {
            toValue: SCAN_AREA_SIZE - 4,
            duration: 2000,
            useNativeDriver: true,
          }),
          Animated.timing(scanLineAnim, {
            toValue: 0,
            duration: 2000,
            useNativeDriver: true,
          }),
        ])
      ).start();

      // Pulse animation for corners
      Animated.loop(
        Animated.sequence([
          Animated.timing(pulseAnim, {
            toValue: 1.1,
            duration: 1000,
            useNativeDriver: true,
          }),
          Animated.timing(pulseAnim, {
            toValue: 1,
            duration: 1000,
            useNativeDriver: true,
          }),
        ])
      ).start();
    }
  }, [scanned, cameraReady]);

  // ขอ permission อัตโนมัติ
  useEffect(() => {
    const initCamera = async () => {
      setIsLoading(true);
      setCameraError(null);

      try {
        // รอให้ component mount
        await new Promise(resolve => setTimeout(resolve, 500));

        if (!permission) {
          console.log('📷 Requesting camera permission...');
          const result = await requestPermission();
          console.log('📷 Permission result:', result);

          if (!result.granted) {
            setCameraError('ไม่ได้รับอนุญาตให้ใช้กล้อง');
          }
        } else if (!permission.granted && permission.canAskAgain) {
          console.log('📷 Re-requesting camera permission...');
          await requestPermission();
        }
      } catch (error) {
        console.error('Camera init error:', error);
        setCameraError('เกิดข้อผิดพลาดในการเปิดกล้อง');
      } finally {
        setIsLoading(false);
      }
    };

    initCamera();
  }, []);

  // Handle camera ready
  const handleCameraReady = useCallback(() => {
    console.log('📷 Camera is ready');
    setCameraReady(true);
    setCameraError(null);
  }, []);

  // Handle camera mount error
  const handleMountError = useCallback((error: { message: string }) => {
    console.error('📷 Camera mount error:', error);
    setCameraError(error.message || 'ไม่สามารถเปิดกล้องได้');
  }, []);

  // Handle barcode scanned
  const handleBarCodeScanned = useCallback(({ data, type }: BarcodeScanningResult) => {
    if (scanned) return;

    setScanned(true);
    setScannedData(data);

    // Haptic feedback
    try {
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    } catch (e) {
      // Ignore haptic errors
    }

    console.log(`✅ Scanned: ${type} - ${data}`);
  }, [scanned]);

  // Copy to clipboard
  const handleCopy = async () => {
    if (scannedData) {
      await Clipboard.setStringAsync(scannedData);
      try {
        Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
      } catch (e) {}
      Alert.alert('คัดลอกแล้ว', 'ข้อมูลถูกคัดลอกไปยังคลิปบอร์ด');
    }
  };

  // Open link
  const handleOpenLink = async () => {
    if (scannedData && isUrl(scannedData)) {
      try {
        const supported = await Linking.canOpenURL(scannedData);
        if (supported) {
          await Linking.openURL(scannedData);
        } else {
          Alert.alert('ไม่สามารถเปิดลิงก์', 'URL นี้ไม่รองรับ');
        }
      } catch (error) {
        Alert.alert('ผิดพลาด', 'ไม่สามารถเปิดลิงก์ได้');
      }
    }
  };

  // Check if string is URL
  const isUrl = (str: string): boolean => {
    try {
      const url = new URL(str);
      return url.protocol === 'http:' || url.protocol === 'https:';
    } catch {
      return false;
    }
  };

  // Reset scanner
  const handleScanAgain = () => {
    setScanned(false);
    setScannedData(null);
  };

  // Toggle flash
  const toggleFlash = () => {
    setIsFlashOn(!isFlashOn);
    try {
      Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    } catch (e) {}
  };

  // Toggle camera
  const toggleCamera = () => {
    setFacing(facing === 'back' ? 'front' : 'back');
    try {
      Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    } catch (e) {}
  };

  // Retry camera
  const retryCamera = async () => {
    setIsLoading(true);
    setCameraError(null);
    setCameraReady(false);

    try {
      await new Promise(resolve => setTimeout(resolve, 500));
      const result = await requestPermission();
      if (result.granted) {
        setCameraReady(true);
      } else {
        setCameraError('ไม่ได้รับอนุญาตให้ใช้กล้อง');
      }
    } catch (error) {
      setCameraError('เกิดข้อผิดพลาด กรุณาลองใหม่');
    } finally {
      setIsLoading(false);
    }
  };

  // Loading state - พร้อม AnimatedBackground
  if (isLoading) {
    return (
      <AnimatedBackground
        variant="firefly"
        isDark={true}
        intensity="medium"
        particleCount={20}
      >
        <View style={styles.container}>
          <StatusBar barStyle="light-content" />
          <View style={styles.centered}>
            <View style={styles.loadingBox}>
              <ActivityIndicator size="large" color="#3B82F6" />
              <Text style={styles.loadingText}>กำลังเปิดกล้อง...</Text>
              <Text style={styles.loadingSubtext}>กรุณารอสักครู่</Text>
            </View>
          </View>
        </View>
      </AnimatedBackground>
    );
  }

  // Error state - พร้อม AnimatedBackground
  if (cameraError) {
    return (
      <AnimatedBackground
        variant="particles"
        isDark={true}
        intensity="low"
        particleCount={15}
      >
        <View style={styles.container}>
          <StatusBar barStyle="light-content" />
          <View style={styles.centered}>
            <View style={styles.errorBox}>
              <AlertCircleIcon size={60} color="#EF4444" />
              <Text style={styles.errorTitle}>เกิดข้อผิดพลาด</Text>
              <Text style={styles.errorText}>{cameraError}</Text>

              <TouchableOpacity style={styles.retryButton} onPress={retryCamera}>
                <LinearGradient
                  colors={['#3B82F6', '#2563EB']}
                  style={styles.retryButtonGradient}
                >
                  <RefreshIcon size={20} color="#FFF" />
                  <Text style={styles.retryButtonText}>ลองใหม่</Text>
                </LinearGradient>
              </TouchableOpacity>

              <TouchableOpacity style={styles.backButtonAlt} onPress={() => router.back()}>
                <Text style={styles.backButtonText}>กลับ</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </AnimatedBackground>
    );
  }

  // Permission denied - พร้อม AnimatedBackground
  if (permission && !permission.granted) {
    return (
      <AnimatedBackground
        variant="aurora"
        isDark={true}
        intensity="medium"
        particleCount={18}
      >
        <View style={styles.container}>
          <StatusBar barStyle="light-content" />
          <View style={styles.centered}>
            <View style={styles.permissionBox}>
              <View style={styles.permissionIconContainer}>
                <CameraIcon size={50} color="#FFF" />
              </View>
              <Text style={styles.permissionTitle}>ต้องการสิทธิ์กล้อง</Text>
              <Text style={styles.permissionText}>
                กรุณาอนุญาตให้แอพใช้กล้องเพื่อสแกน QR Code
              </Text>

              <TouchableOpacity style={styles.permissionButton} onPress={requestPermission}>
                <LinearGradient
                  colors={['#10B981', '#059669']}
                  style={styles.permissionButtonGradient}
                >
                  <Text style={styles.permissionButtonText}>อนุญาตใช้กล้อง</Text>
                </LinearGradient>
              </TouchableOpacity>

              <TouchableOpacity style={styles.backButtonAlt} onPress={() => router.back()}>
                <Text style={styles.backButtonText}>กลับ</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </AnimatedBackground>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" />

      {/* Camera View */}
      <CameraView
        style={StyleSheet.absoluteFillObject}
        facing={facing}
        enableTorch={isFlashOn}
        onCameraReady={handleCameraReady}
        onMountError={handleMountError}
        barcodeScannerSettings={{
          barcodeTypes: ['qr', 'ean13', 'ean8', 'code128', 'code39', 'code93', 'pdf417'],
        }}
        onBarcodeScanned={scanned ? undefined : handleBarCodeScanned}
      />

      {/* Overlay */}
      <View style={styles.overlay}>
        {/* Header with Glassmorphism */}
        <View style={styles.headerContainer}>
          <BlurView intensity={30} tint="dark" style={styles.header}>
            <TouchableOpacity style={styles.headerButton} onPress={() => router.back()}>
              <ArrowBackIcon size={24} color="#FFF" />
            </TouchableOpacity>
            <Text style={styles.headerTitle}>สแกน QR Code</Text>
            <TouchableOpacity style={styles.headerButton} onPress={toggleFlash}>
              {isFlashOn ? (
                <FlashIcon size={24} color="#FFD700" />
              ) : (
                <FlashOffIcon size={24} color="#FFF" />
              )}
            </TouchableOpacity>
          </BlurView>
        </View>

        {/* Scan Area */}
        <View style={styles.scanAreaContainer}>
          <Animated.View style={[styles.scanArea, { transform: [{ scale: pulseAnim }] }]}>
            {/* Corner decorations with gradient */}
            <LinearGradient colors={['#3B82F6', '#8B5CF6']} style={[styles.corner, styles.cornerTL]} />
            <LinearGradient colors={['#3B82F6', '#8B5CF6']} style={[styles.corner, styles.cornerTR]} />
            <LinearGradient colors={['#8B5CF6', '#EC4899']} style={[styles.corner, styles.cornerBL]} />
            <LinearGradient colors={['#8B5CF6', '#EC4899']} style={[styles.corner, styles.cornerBR]} />

            {/* Scan line animation */}
            {!scanned && cameraReady && (
              <Animated.View
                style={[
                  styles.scanLine,
                  { transform: [{ translateY: scanLineAnim }] },
                ]}
              >
                <LinearGradient
                  colors={['transparent', '#3B82F6', 'transparent']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                  style={styles.scanLineGradient}
                />
              </Animated.View>
            )}
          </Animated.View>

          <Text style={styles.scanHint}>
            {scanned ? '✅ สแกนสำเร็จ!' : cameraReady ? 'วาง QR Code ในกรอบเพื่อสแกน' : 'กำลังเตรียมกล้อง...'}
          </Text>
        </View>

        {/* Bottom Controls with Glassmorphism */}
        <View style={styles.bottomContainer}>
          <BlurView intensity={50} tint="dark" style={styles.bottomControls}>
            {!scanned ? (
              <View style={styles.controlButtons}>
                <TouchableOpacity style={styles.controlButton} onPress={toggleCamera}>
                  <View style={styles.controlButtonInner}>
                    <CameraRotateIcon size={28} color="#FFF" />
                  </View>
                  <Text style={styles.controlButtonText}>สลับกล้อง</Text>
                </TouchableOpacity>
              </View>
            ) : (
              <View style={styles.resultContainer}>
                {/* Scanned Data */}
                <View style={styles.resultBox}>
                  <View style={styles.resultHeader}>
                    <CheckCircleIcon size={24} color="#10B981" />
                    <Text style={styles.resultTitle}>ผลการสแกน</Text>
                  </View>
                  <Text style={styles.resultData} numberOfLines={4}>
                    {scannedData}
                  </Text>
                </View>

                {/* Action Buttons */}
                <View style={styles.actionButtons}>
                  <TouchableOpacity style={styles.actionButton} onPress={handleCopy}>
                    <LinearGradient colors={['#6B7280', '#4B5563']} style={styles.actionButtonGradient}>
                      <CopyIcon size={18} color="#FFF" />
                      <Text style={styles.actionButtonText}>คัดลอก</Text>
                    </LinearGradient>
                  </TouchableOpacity>

                  {scannedData && isUrl(scannedData) && (
                    <TouchableOpacity style={styles.actionButton} onPress={handleOpenLink}>
                      <LinearGradient colors={['#3B82F6', '#2563EB']} style={styles.actionButtonGradient}>
                        <ExternalLinkIcon size={18} color="#FFF" />
                        <Text style={styles.actionButtonText}>เปิดลิงก์</Text>
                      </LinearGradient>
                    </TouchableOpacity>
                  )}

                  <TouchableOpacity style={styles.actionButton} onPress={handleScanAgain}>
                    <LinearGradient colors={['#10B981', '#059669']} style={styles.actionButtonGradient}>
                      <QRCodeIcon size={18} color="#FFF" />
                      <Text style={styles.actionButtonText}>สแกนใหม่</Text>
                    </LinearGradient>
                  </TouchableOpacity>
                </View>
              </View>
            )}
          </BlurView>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#000',
  },
  centered: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 24,
  },
  loadingBox: {
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 24,
    padding: 32,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.2)',
  },
  loadingText: {
    color: '#FFF',
    fontSize: 18,
    fontWeight: '600',
    marginTop: 20,
  },
  loadingSubtext: {
    color: '#9CA3AF',
    fontSize: 14,
    marginTop: 8,
  },
  errorBox: {
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 24,
    padding: 32,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(239,68,68,0.3)',
    width: '100%',
    maxWidth: 320,
  },
  errorTitle: {
    color: '#EF4444',
    fontSize: 22,
    fontWeight: 'bold',
    marginTop: 16,
  },
  errorText: {
    color: '#9CA3AF',
    fontSize: 15,
    textAlign: 'center',
    marginTop: 8,
    lineHeight: 22,
  },
  retryButton: {
    marginTop: 24,
    borderRadius: 16,
    overflow: 'hidden',
    width: '100%',
  },
  retryButtonGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 14,
    paddingHorizontal: 24,
    gap: 8,
  },
  retryButtonText: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: '600',
  },
  backButtonAlt: {
    paddingVertical: 14,
    marginTop: 12,
  },
  backButtonText: {
    color: '#9CA3AF',
    fontSize: 16,
  },
  permissionBox: {
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 24,
    padding: 32,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.2)',
    width: '100%',
    maxWidth: 320,
  },
  permissionIconContainer: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: 'rgba(59,130,246,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  permissionTitle: {
    color: '#FFF',
    fontSize: 22,
    fontWeight: 'bold',
  },
  permissionText: {
    color: '#9CA3AF',
    fontSize: 15,
    textAlign: 'center',
    marginTop: 8,
    lineHeight: 22,
  },
  permissionButton: {
    marginTop: 24,
    borderRadius: 16,
    overflow: 'hidden',
    width: '100%',
  },
  permissionButtonGradient: {
    paddingVertical: 14,
    paddingHorizontal: 24,
    alignItems: 'center',
  },
  permissionButtonText: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: '600',
  },
  overlay: {
    ...StyleSheet.absoluteFillObject,
    justifyContent: 'space-between',
  },
  headerContainer: {
    paddingTop: Platform.OS === 'ios' ? 50 : 40,
    paddingHorizontal: 16,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 20,
    overflow: 'hidden',
  },
  headerButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.15)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerTitle: {
    color: '#FFF',
    fontSize: 18,
    fontWeight: 'bold',
  },
  scanAreaContainer: {
    alignItems: 'center',
  },
  scanArea: {
    width: SCAN_AREA_SIZE,
    height: SCAN_AREA_SIZE,
    position: 'relative',
  },
  corner: {
    position: 'absolute',
    width: 40,
    height: 40,
  },
  cornerTL: {
    top: 0,
    left: 0,
    borderTopLeftRadius: 12,
    borderTopWidth: 4,
    borderLeftWidth: 4,
    borderColor: 'transparent',
  },
  cornerTR: {
    top: 0,
    right: 0,
    borderTopRightRadius: 12,
    transform: [{ rotate: '90deg' }],
  },
  cornerBL: {
    bottom: 0,
    left: 0,
    borderBottomLeftRadius: 12,
    transform: [{ rotate: '-90deg' }],
  },
  cornerBR: {
    bottom: 0,
    right: 0,
    borderBottomRightRadius: 12,
    transform: [{ rotate: '180deg' }],
  },
  scanLine: {
    position: 'absolute',
    width: '100%',
    height: 3,
    left: 0,
  },
  scanLineGradient: {
    flex: 1,
    borderRadius: 2,
  },
  scanHint: {
    color: '#FFF',
    fontSize: 16,
    marginTop: 24,
    textAlign: 'center',
    textShadowColor: 'rgba(0,0,0,0.8)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 4,
  },
  bottomContainer: {
    paddingHorizontal: 16,
    paddingBottom: Platform.OS === 'ios' ? 40 : 24,
  },
  bottomControls: {
    borderRadius: 24,
    overflow: 'hidden',
    padding: 20,
  },
  controlButtons: {
    flexDirection: 'row',
    justifyContent: 'center',
  },
  controlButton: {
    alignItems: 'center',
    paddingHorizontal: 24,
    paddingVertical: 12,
  },
  controlButtonInner: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: 'rgba(255,255,255,0.15)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 8,
  },
  controlButtonText: {
    color: '#FFF',
    fontSize: 13,
  },
  resultContainer: {
    width: '100%',
  },
  resultBox: {
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
  },
  resultHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
    gap: 8,
  },
  resultTitle: {
    color: '#10B981',
    fontSize: 16,
    fontWeight: 'bold',
  },
  resultData: {
    color: '#FFF',
    fontSize: 14,
    lineHeight: 20,
  },
  actionButtons: {
    flexDirection: 'row',
    gap: 8,
  },
  actionButton: {
    flex: 1,
    borderRadius: 12,
    overflow: 'hidden',
  },
  actionButtonGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 12,
    gap: 6,
  },
  actionButtonText: {
    color: '#FFF',
    fontSize: 13,
    fontWeight: '600',
  },
});
