/**
 * QR Scanner Screen - หน้าสแกน QR Code
 * เปิดกล้องอัตโนมัติเมื่อเข้าหน้านี้
 *
 * Features:
 * - เปิดกล้องอัตโนมัติ
 * - สแกน QR Code และ Barcode
 * - แสดงผลลัพธ์
 * - คัดลอกผลลัพธ์ได้
 * - เปิดลิงก์ได้ (ถ้าเป็น URL)
 */

import React, { useState, useEffect, useCallback } from 'react';
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
} from 'react-native';
import { CameraView, useCameraPermissions, BarcodeScanningResult } from 'expo-camera';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import * as Clipboard from 'expo-clipboard';
import * as Haptics from 'expo-haptics';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');
const SCAN_AREA_SIZE = SCREEN_WIDTH * 0.7;

export default function QRScannerScreen() {
  const [permission, requestPermission] = useCameraPermissions();
  const [scanned, setScanned] = useState(false);
  const [scannedData, setScannedData] = useState<string | null>(null);
  const [isFlashOn, setIsFlashOn] = useState(false);
  const [facing, setFacing] = useState<'front' | 'back'>('back');

  // Animation สำหรับ scan line
  const scanLineAnim = React.useRef(new Animated.Value(0)).current;

  // เริ่ม animation scan line
  useEffect(() => {
    const startAnimation = () => {
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
    };

    if (!scanned) {
      startAnimation();
    }
  }, [scanned]);

  // ขอ permission อัตโนมัติเมื่อเข้าหน้า
  useEffect(() => {
    const checkAndRequestPermission = async () => {
      try {
        // ถ้ายังไม่มี permission ให้ request
        if (permission === null) {
          console.log('Requesting camera permission...');
          await requestPermission();
        } else if (!permission.granted && permission.canAskAgain) {
          console.log('Re-requesting camera permission...');
          await requestPermission();
        }
      } catch (error) {
        console.error('Camera permission error:', error);
      }
    };

    checkAndRequestPermission();
  }, [permission, requestPermission]);

  // Handle barcode scanned
  const handleBarCodeScanned = useCallback(({ data, type }: BarcodeScanningResult) => {
    if (scanned) return;

    setScanned(true);
    setScannedData(data);

    // Haptic feedback
    Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);

    console.log(`Scanned: ${type} - ${data}`);
  }, [scanned]);

  // Copy to clipboard
  const handleCopy = async () => {
    if (scannedData) {
      await Clipboard.setStringAsync(scannedData);
      Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
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
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
  };

  // Toggle camera
  const toggleCamera = () => {
    setFacing(facing === 'back' ? 'front' : 'back');
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
  };

  // Loading state
  if (!permission) {
    return (
      <View style={styles.container}>
        <StatusBar barStyle="light-content" />
        <View style={styles.centered}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={styles.loadingText}>กำลังตรวจสอบสิทธิ์กล้อง...</Text>
        </View>
      </View>
    );
  }

  // Permission denied
  if (!permission.granted) {
    return (
      <View style={styles.container}>
        <StatusBar barStyle="light-content" />
        <View style={styles.centered}>
          <Ionicons name="camera-outline" size={80} color="#6B7280" />
          <Text style={styles.permissionTitle}>ต้องการสิทธิ์กล้อง</Text>
          <Text style={styles.permissionText}>
            กรุณาอนุญาตให้แอพใช้กล้องเพื่อสแกน QR Code
          </Text>
          <TouchableOpacity style={styles.permissionButton} onPress={requestPermission}>
            <Text style={styles.permissionButtonText}>อนุญาตใช้กล้อง</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.backButton} onPress={() => router.back()}>
            <Text style={styles.backButtonText}>กลับ</Text>
          </TouchableOpacity>
        </View>
      </View>
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
        barcodeScannerSettings={{
          barcodeTypes: ['qr', 'ean13', 'ean8', 'code128', 'code39', 'code93', 'pdf417'],
        }}
        onBarcodeScanned={scanned ? undefined : handleBarCodeScanned}
      />

      {/* Overlay */}
      <View style={styles.overlay}>
        {/* Header */}
        <LinearGradient
          colors={['rgba(0,0,0,0.7)', 'transparent']}
          style={styles.header}
        >
          <TouchableOpacity style={styles.headerButton} onPress={() => router.back()}>
            <Ionicons name="arrow-back" size={24} color="#FFF" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>สแกน QR Code</Text>
          <TouchableOpacity style={styles.headerButton} onPress={toggleFlash}>
            <Ionicons name={isFlashOn ? 'flash' : 'flash-outline'} size={24} color="#FFF" />
          </TouchableOpacity>
        </LinearGradient>

        {/* Scan Area */}
        <View style={styles.scanAreaContainer}>
          <View style={styles.scanArea}>
            {/* Corner decorations */}
            <View style={[styles.corner, styles.cornerTL]} />
            <View style={[styles.corner, styles.cornerTR]} />
            <View style={[styles.corner, styles.cornerBL]} />
            <View style={[styles.corner, styles.cornerBR]} />

            {/* Scan line animation */}
            {!scanned && (
              <Animated.View
                style={[
                  styles.scanLine,
                  {
                    transform: [{ translateY: scanLineAnim }],
                  },
                ]}
              />
            )}
          </View>

          <Text style={styles.scanHint}>
            {scanned ? 'สแกนสำเร็จ!' : 'วาง QR Code ในกรอบเพื่อสแกน'}
          </Text>
        </View>

        {/* Bottom Controls */}
        <LinearGradient
          colors={['transparent', 'rgba(0,0,0,0.8)']}
          style={styles.bottomControls}
        >
          {!scanned ? (
            <View style={styles.controlButtons}>
              <TouchableOpacity style={styles.controlButton} onPress={toggleCamera}>
                <Ionicons name="camera-reverse-outline" size={28} color="#FFF" />
                <Text style={styles.controlButtonText}>สลับกล้อง</Text>
              </TouchableOpacity>
            </View>
          ) : (
            <View style={styles.resultContainer}>
              {/* Scanned Data */}
              <View style={styles.resultBox}>
                <View style={styles.resultHeader}>
                  <Ionicons name="checkmark-circle" size={24} color="#10B981" />
                  <Text style={styles.resultTitle}>ผลการสแกน</Text>
                </View>
                <Text style={styles.resultData} numberOfLines={4}>
                  {scannedData}
                </Text>
              </View>

              {/* Action Buttons */}
              <View style={styles.actionButtons}>
                <TouchableOpacity style={styles.actionButton} onPress={handleCopy}>
                  <Ionicons name="copy-outline" size={20} color="#FFF" />
                  <Text style={styles.actionButtonText}>คัดลอก</Text>
                </TouchableOpacity>

                {scannedData && isUrl(scannedData) && (
                  <TouchableOpacity
                    style={[styles.actionButton, styles.actionButtonPrimary]}
                    onPress={handleOpenLink}
                  >
                    <Ionicons name="open-outline" size={20} color="#FFF" />
                    <Text style={styles.actionButtonText}>เปิดลิงก์</Text>
                  </TouchableOpacity>
                )}

                <TouchableOpacity
                  style={[styles.actionButton, styles.actionButtonSecondary]}
                  onPress={handleScanAgain}
                >
                  <Ionicons name="scan-outline" size={20} color="#FFF" />
                  <Text style={styles.actionButtonText}>สแกนใหม่</Text>
                </TouchableOpacity>
              </View>
            </View>
          )}
        </LinearGradient>
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
  loadingText: {
    color: '#9CA3AF',
    fontSize: 16,
    marginTop: 16,
  },
  permissionTitle: {
    color: '#FFF',
    fontSize: 24,
    fontWeight: 'bold',
    marginTop: 24,
  },
  permissionText: {
    color: '#9CA3AF',
    fontSize: 16,
    textAlign: 'center',
    marginTop: 12,
  },
  permissionButton: {
    backgroundColor: '#3B82F6',
    paddingHorizontal: 32,
    paddingVertical: 14,
    borderRadius: 12,
    marginTop: 24,
  },
  permissionButtonText: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  backButton: {
    paddingHorizontal: 32,
    paddingVertical: 14,
    marginTop: 16,
  },
  backButtonText: {
    color: '#9CA3AF',
    fontSize: 16,
  },
  overlay: {
    ...StyleSheet.absoluteFillObject,
    justifyContent: 'space-between',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 50,
    paddingHorizontal: 16,
    paddingBottom: 32,
  },
  headerButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.2)',
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
    borderColor: '#3B82F6',
  },
  cornerTL: {
    top: 0,
    left: 0,
    borderTopWidth: 4,
    borderLeftWidth: 4,
    borderTopLeftRadius: 12,
  },
  cornerTR: {
    top: 0,
    right: 0,
    borderTopWidth: 4,
    borderRightWidth: 4,
    borderTopRightRadius: 12,
  },
  cornerBL: {
    bottom: 0,
    left: 0,
    borderBottomWidth: 4,
    borderLeftWidth: 4,
    borderBottomLeftRadius: 12,
  },
  cornerBR: {
    bottom: 0,
    right: 0,
    borderBottomWidth: 4,
    borderRightWidth: 4,
    borderBottomRightRadius: 12,
  },
  scanLine: {
    position: 'absolute',
    width: '100%',
    height: 2,
    backgroundColor: '#3B82F6',
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.8,
    shadowRadius: 8,
  },
  scanHint: {
    color: '#FFF',
    fontSize: 16,
    marginTop: 24,
    textAlign: 'center',
  },
  bottomControls: {
    paddingHorizontal: 16,
    paddingBottom: 48,
    paddingTop: 32,
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
  controlButtonText: {
    color: '#FFF',
    fontSize: 12,
    marginTop: 4,
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
  },
  resultTitle: {
    color: '#10B981',
    fontSize: 16,
    fontWeight: 'bold',
    marginLeft: 8,
  },
  resultData: {
    color: '#FFF',
    fontSize: 14,
    lineHeight: 20,
  },
  actionButtons: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 8,
  },
  actionButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingVertical: 14,
    borderRadius: 12,
    gap: 6,
  },
  actionButtonPrimary: {
    backgroundColor: '#3B82F6',
  },
  actionButtonSecondary: {
    backgroundColor: '#10B981',
  },
  actionButtonText: {
    color: '#FFF',
    fontSize: 14,
    fontWeight: '600',
  },
});
