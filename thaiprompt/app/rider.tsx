/**
 * Rider Screen - Premium Stable Version
 * ใช้ StyleSheet แทน NativeWind
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
  StyleSheet,
  StatusBar,
  Linking,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import * as Location from 'expo-location';
import * as ImagePicker from 'expo-image-picker';
import { Audio } from 'expo-av';
import { useAuthStore } from '@/stores/authStore';
import {
  getRiderStatus,
  registerRider,
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

// Permission Modal
const PermissionModal = ({
  visible,
  onClose,
  onGrant,
  permissionType,
  isLoading,
}: {
  visible: boolean;
  onClose: () => void;
  onGrant: () => void;
  permissionType: 'location' | 'camera' | 'microphone';
  isLoading?: boolean;
}) => {
  const info = {
    location: {
      icon: '📍',
      title: 'การเข้าถึงตำแหน่ง',
      description: 'แอปจะขอเข้าถึงตำแหน่งเพื่อแสดงให้ลูกค้าเห็นเมื่อรับงาน',
      color: '#3B82F6',
    },
    camera: {
      icon: '📷',
      title: 'การเข้าถึงกล้อง',
      description: 'แอปจะขอเข้าถึงกล้องเพื่อถ่ายรูปเอกสารและหลักฐาน',
      color: '#10B981',
    },
    microphone: {
      icon: '🎤',
      title: 'การเข้าถึงไมโครโฟน',
      description: 'แอปจะขอเข้าถึงไมโครโฟนเพื่อโทรหาลูกค้า',
      color: '#8B5CF6',
    },
  }[permissionType];

  return (
    <Modal visible={visible} transparent animationType="fade">
      <View style={styles.modalOverlay}>
        <View style={styles.modalContent}>
          <LinearGradient colors={[info.color, info.color + 'CC']} style={styles.modalHeader}>
            <View style={styles.modalIconBox}>
              <Text style={{ fontSize: 32 }}>{info.icon}</Text>
            </View>
            <Text style={styles.modalTitle}>{info.title}</Text>
          </LinearGradient>

          <View style={styles.modalBody}>
            <Text style={styles.modalDesc}>{info.description}</Text>
          </View>

          <View style={styles.modalActions}>
            <Pressable style={styles.modalCancelBtn} onPress={onClose}>
              <Text style={styles.modalCancelText}>ภายหลัง</Text>
            </Pressable>
            <Pressable
              style={[styles.modalGrantBtn, { backgroundColor: info.color }]}
              onPress={onGrant}
              disabled={isLoading}
            >
              {isLoading ? (
                <ActivityIndicator color="#FFF" />
              ) : (
                <Text style={styles.modalGrantText}>อนุญาต</Text>
              )}
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
  );
};

export default function RiderScreen() {
  const { isAuthenticated, user } = useAuthStore();

  // ⭐ Super Admin Bypass - เข้าได้โดยไม่ต้องสมัคร
  const isSuperAdmin = user?.is_super_admin === true || user?.role === 'super_admin';

  const [riderData, setRiderData] = useState<any>(null);
  const [showRegisterForm, setShowRegisterForm] = useState(false);
  const [formData, setFormData] = useState({
    full_name: user?.name || '',
    phone: '',
    vehicle_type: 'motorcycle' as 'motorcycle' | 'car' | 'bicycle' | 'walk',
    vehicle_plate: '',
  });

  const [permissionModal, setPermissionModal] = useState<{
    visible: boolean;
    type: 'location' | 'camera' | 'microphone';
  }>({ visible: false, type: 'location' });

  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isGrantingPermission, setIsGrantingPermission] = useState(false);
  const [isTogglingAvailability, setIsTogglingAvailability] = useState(false);
  const [isLocationSharing, setIsLocationSharing] = useState(false);
  const [isTogglingLocationShare, setIsTogglingLocationShare] = useState(false);

  // Load Rider Status
  const loadRiderStatus = useCallback(async () => {
    if (!isAuthenticated) return;
    setIsLoading(true);
    try {
      // ⭐ Super Admin Bypass - สร้างข้อมูลจำลองสำหรับทดสอบ
      if (isSuperAdmin) {
        setRiderData({
          isRider: true,
          riderId: 'ADMIN-DEV',
          status: 'approved',
          statusText: '✅ อนุมัติแล้ว (Developer Mode)',
          availability: 'online',
          availabilityText: 'ออนไลน์',
          vehicleType: 'motorcycle',
          vehicleTypeText: 'มอเตอร์ไซค์',
          rating: 5.0,
          totalJobs: 999,
          completedJobs: 999,
          completionRate: 100,
          totalEarnings: 999999,
          permissions: {
            gps: true,
            camera: true,
            microphone: true,
            notification: true,
            allGranted: true,
          },
          isSuperAdminMode: true,  // Flag แสดงว่าเป็น dev mode
        });
        setIsLoading(false);
        return;
      }

      const response = await getRiderStatus();
      if (response?.success && response.data) {
        setRiderData(response.data);
      }
    } catch (error) {
      console.error('Load rider status error:', error);
    } finally {
      setIsLoading(false);
    }
  }, [isAuthenticated, isSuperAdmin]);

  useEffect(() => {
    loadRiderStatus();
    setIsLocationSharing(isTrackingLocation());
  }, [loadRiderStatus]);

  // Permission Handlers
  const requestLocationPermission = async () => {
    setIsGrantingPermission(true);
    try {
      const { status } = await Location.requestForegroundPermissionsAsync();
      if (status === 'granted') {
        const { status: bg } = await Location.requestBackgroundPermissionsAsync();
        await updateRiderPermissions({ gps: bg === 'granted' });
        await loadRiderStatus();
        Alert.alert('สำเร็จ', 'อนุญาตการเข้าถึงตำแหน่งเรียบร้อย');
      } else {
        Alert.alert('ไม่ได้รับอนุญาต', 'คุณต้องอนุญาตการเข้าถึงตำแหน่ง', [
          { text: 'ยกเลิก', style: 'cancel' },
          { text: 'ไปตั้งค่า', onPress: () => Linking.openSettings() },
        ]);
      }
    } catch (error) {
      Alert.alert('ข้อผิดพลาด', 'ไม่สามารถขอสิทธิ์ได้');
    } finally {
      setIsGrantingPermission(false);
      setPermissionModal({ visible: false, type: 'location' });
    }
  };

  const requestCameraPermission = async () => {
    setIsGrantingPermission(true);
    try {
      const { status } = await ImagePicker.requestCameraPermissionsAsync();
      await updateRiderPermissions({ camera: status === 'granted' });
      await loadRiderStatus();
      if (status === 'granted') Alert.alert('สำเร็จ', 'อนุญาตการเข้าถึงกล้องเรียบร้อย');
    } catch (error) {
      Alert.alert('ข้อผิดพลาด', 'ไม่สามารถขอสิทธิ์ได้');
    } finally {
      setIsGrantingPermission(false);
      setPermissionModal({ visible: false, type: 'camera' });
    }
  };

  const requestMicrophonePermission = async () => {
    setIsGrantingPermission(true);
    try {
      const { status } = await Audio.requestPermissionsAsync();
      await updateRiderPermissions({ microphone: status === 'granted' });
      await loadRiderStatus();
      if (status === 'granted') Alert.alert('สำเร็จ', 'อนุญาตการเข้าถึงไมโครโฟนเรียบร้อย');
    } catch (error) {
      Alert.alert('ข้อผิดพลาด', 'ไม่สามารถขอสิทธิ์ได้');
    } finally {
      setIsGrantingPermission(false);
      setPermissionModal({ visible: false, type: 'microphone' });
    }
  };

  const handleGrantPermission = async () => {
    switch (permissionModal.type) {
      case 'location': await requestLocationPermission(); break;
      case 'camera': await requestCameraPermission(); break;
      case 'microphone': await requestMicrophonePermission(); break;
    }
  };

  // Register Handler
  const handleRegister = async () => {
    if (!formData.full_name.trim() || !formData.phone.trim()) {
      Alert.alert('ข้อผิดพลาด', 'กรุณากรอกข้อมูลให้ครบ');
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
        Alert.alert('ข้อผิดพลาด', response.message || 'สมัครไม่สำเร็จ');
      }
    } catch (error) {
      Alert.alert('ข้อผิดพลาด', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Availability Toggle
  const toggleAvailability = async () => {
    if (!riderData?.permissions?.gps) {
      Alert.alert('ต้องอนุญาตตำแหน่งก่อน', 'คุณต้องอนุญาตการเข้าถึงตำแหน่งก่อนเปิดรับงาน', [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: 'อนุญาต', onPress: () => setPermissionModal({ visible: true, type: 'location' }) },
      ]);
      return;
    }
    setIsTogglingAvailability(true);
    try {
      const newStatus = riderData?.availability === 'online' ? 'offline' : 'online';
      const response = await setRiderAvailability(newStatus);
      if (response.success) {
        await loadRiderStatus();
        if (newStatus === 'online') Alert.alert('🟢 เปิดรับงานแล้ว', 'ระบบจะแจ้งเตือนเมื่อมีงานใกล้คุณ');
      } else {
        Alert.alert('ข้อผิดพลาด', response.message || 'ไม่สามารถเปลี่ยนสถานะได้');
      }
    } catch (error) {
      Alert.alert('ข้อผิดพลาด', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
    } finally {
      setIsTogglingAvailability(false);
    }
  };

  // Location Sharing Toggle
  const toggleLocationSharing = async () => {
    if (!riderData?.permissions?.gps) {
      Alert.alert('ต้องอนุญาตตำแหน่งก่อน', 'กรุณาอนุญาตการเข้าถึงตำแหน่ง', [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: 'อนุญาต', onPress: () => setPermissionModal({ visible: true, type: 'location' }) },
      ]);
      return;
    }
    setIsTogglingLocationShare(true);
    try {
      if (isLocationSharing) {
        await stopTracking();
        setIsLocationSharing(false);
        Alert.alert('หยุดแชร์ตำแหน่งแล้ว');
      } else {
        const success = await startTracking(0);
        if (success) {
          setIsLocationSharing(true);
          const location = await getCurrentLocation();
          if (location) {
            await updateRiderLocation({ latitude: location.latitude, longitude: location.longitude, accuracy: location.accuracy });
          }
          Alert.alert('🟢 เริ่มแชร์ตำแหน่งแล้ว');
        } else {
          Alert.alert('ไม่สามารถเริ่มแชร์ตำแหน่งได้');
        }
      }
    } catch (error) {
      Alert.alert('ข้อผิดพลาด', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
    } finally {
      setIsTogglingLocationShare(false);
    }
  };

  // Not Authenticated
  if (!isAuthenticated) {
    return (
      <View style={styles.container}>
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <View style={styles.centerBox}>
          <Text style={{ fontSize: 80, color: '#4B5563' }}>🚴</Text>
          <Text style={styles.centerTitle}>สมัครเป็นไรเดอร์</Text>
          <Text style={styles.centerText}>เข้าสู่ระบบเพื่อสมัครเป็นไรเดอร์และรับงาน</Text>
          <Pressable style={styles.primaryButton} onPress={() => router.push('/login')}>
            <Text style={styles.primaryButtonText}>เข้าสู่ระบบ</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  // Loading
  if (isLoading) {
    return (
      <View style={styles.container}>
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <View style={styles.centerBox}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={styles.loadingText}>กำลังโหลด...</Text>
        </View>
      </View>
    );
  }

  // Not a Rider - Show Registration
  if (!riderData?.isRider) {
    return (
      <View style={styles.container}>
        <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
        <ScrollView style={styles.scrollView} contentContainerStyle={styles.scrollContent}>
          {/* Header */}
          <View style={styles.headerRow}>
            <Pressable style={styles.backBtn} onPress={() => router.back()}>
              <Text style={{ fontSize: 24, color: '#FFF' }}>←</Text>
            </Pressable>
            <Text style={styles.headerTitle}>สมัครเป็นไรเดอร์</Text>
            <View style={{ width: 44 }} />
          </View>

          {/* Benefits Card */}
          <LinearGradient colors={['#06B6D4', '#0891B2']} style={styles.benefitsCard}>
            <Text style={styles.benefitsTitle}>🚴 ทำไมต้องเป็นไรเดอร์กับเรา?</Text>
            {['รายได้ดี คิดค่าบริการยุติธรรม', 'เวลาทำงานยืดหยุ่น', 'ถอนเงินได้ทันที', 'ประกันอุบัติเหตุ'].map((item, i) => (
              <View key={i} style={styles.benefitItem}>
                <Text style={{ fontSize: 20, color: '#FFF' }}>✓</Text>
                <Text style={styles.benefitText}>{item}</Text>
              </View>
            ))}
          </LinearGradient>

          {/* Permission Notice */}
          <View style={styles.warningCard}>
            <Text style={styles.warningTitle}>⚠️ แอปจะขอสิทธิ์ดังนี้:</Text>
            <Text style={styles.warningItem}>📍 ตำแหน่ง - แชร์เฉพาะเมื่อรับงาน</Text>
            <Text style={styles.warningItem}>📷 กล้อง - ถ่ายรูปเอกสารและหลักฐาน</Text>
            <Text style={styles.warningItem}>🎤 ไมโครโฟน - โทรหาลูกค้า</Text>
          </View>

          {/* Register Form */}
          {showRegisterForm ? (
            <View style={styles.formCard}>
              <Text style={styles.formTitle}>กรอกข้อมูลสมัคร</Text>

              <View style={styles.inputGroup}>
                <Text style={styles.label}>ชื่อ-นามสกุล *</Text>
                <TextInput
                  style={styles.input}
                  placeholder="ชื่อจริง นามสกุล"
                  placeholderTextColor="#6B7280"
                  value={formData.full_name}
                  onChangeText={(t) => setFormData({ ...formData, full_name: t })}
                />
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.label}>เบอร์โทรศัพท์ *</Text>
                <TextInput
                  style={styles.input}
                  placeholder="08X-XXX-XXXX"
                  placeholderTextColor="#6B7280"
                  value={formData.phone}
                  onChangeText={(t) => setFormData({ ...formData, phone: t })}
                  keyboardType="phone-pad"
                />
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.label}>ประเภทยานพาหนะ *</Text>
                <View style={styles.vehicleRow}>
                  {[
                    { value: 'motorcycle', label: '🏍️ มอเตอร์ไซค์' },
                    { value: 'car', label: '🚗 รถยนต์' },
                    { value: 'bicycle', label: '🚲 จักรยาน' },
                    { value: 'walk', label: '🚶 เดินเท้า' },
                  ].map((opt) => (
                    <Pressable
                      key={opt.value}
                      style={[styles.vehicleBtn, formData.vehicle_type === opt.value && styles.vehicleBtnActive]}
                      onPress={() => setFormData({ ...formData, vehicle_type: opt.value as any })}
                    >
                      <Text style={[styles.vehicleBtnText, formData.vehicle_type === opt.value && styles.vehicleBtnTextActive]}>
                        {opt.label}
                      </Text>
                    </Pressable>
                  ))}
                </View>
              </View>

              {formData.vehicle_type !== 'walk' && (
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>ทะเบียนรถ</Text>
                  <TextInput
                    style={styles.input}
                    placeholder="กข 1234"
                    placeholderTextColor="#6B7280"
                    value={formData.vehicle_plate}
                    onChangeText={(t) => setFormData({ ...formData, vehicle_plate: t })}
                  />
                </View>
              )}

              <Pressable
                style={[styles.submitBtn, isSubmitting && styles.buttonDisabled]}
                onPress={handleRegister}
                disabled={isSubmitting}
              >
                {isSubmitting ? (
                  <ActivityIndicator color="#FFF" />
                ) : (
                  <Text style={styles.submitBtnText}>สมัครเป็นไรเดอร์</Text>
                )}
              </Pressable>
            </View>
          ) : (
            <Pressable style={styles.primaryButtonLarge} onPress={() => setShowRegisterForm(true)}>
              <Text style={styles.primaryButtonText}>สมัครเลย</Text>
            </Pressable>
          )}
        </ScrollView>
      </View>
    );
  }

  // Rider Dashboard
  const isApproved = riderData.status === 'approved';
  const isPending = riderData.status === 'pending';
  const isRejected = riderData.status === 'rejected';
  const isOnline = riderData.availability === 'online';

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F0F23" />
      <ScrollView style={styles.scrollView} contentContainerStyle={styles.scrollContent}>
        {/* Header */}
        <View style={styles.headerRow}>
          <Pressable style={styles.backBtn} onPress={() => router.back()}>
            <Text style={{ fontSize: 24, color: '#FFF' }}>←</Text>
          </Pressable>
          <Text style={styles.headerTitle}>ไรเดอร์</Text>
          {isApproved && (
            <View style={[styles.statusBadge, isOnline && styles.statusBadgeOnline]}>
              <Text style={[styles.statusBadgeText, isOnline && styles.statusBadgeTextOnline]}>
                {isOnline ? '🟢 ออนไลน์' : '⚫ ออฟไลน์'}
              </Text>
            </View>
          )}
        </View>

        {/* ⭐ Developer Mode Banner for Super Admin */}
        {riderData?.isSuperAdminMode && (
          <View style={styles.devModeBanner}>
            <Text style={styles.devModeText}>⚙️ Developer Mode - Super Admin Access</Text>
          </View>
        )}

        {/* Status Card */}
        <LinearGradient
          colors={isPending ? ['#F59E0B', '#D97706'] : isRejected ? ['#EF4444', '#DC2626'] : ['#10B981', '#059669']}
          style={styles.statusCard}
        >
          <View style={styles.statusRow}>
            <Text style={{ fontSize: 32, color: '#FFF' }}>
              {isPending ? '⏰' : isRejected ? '❌' : '✅'}
            </Text>
            <View style={styles.statusInfo}>
              <Text style={styles.statusTitle}>{riderData.statusText}</Text>
              <Text style={styles.statusSubtitle}>รหัสไรเดอร์: #{riderData.riderId}</Text>
            </View>
          </View>

          {isPending && (
            <View style={styles.statusNote}>
              <Text style={styles.statusNoteText}>เอกสารกำลังตรวจสอบ โดยปกติใช้เวลา 1-3 วันทำการ</Text>
              <Pressable style={styles.statusBtn} onPress={() => router.push('/rider-documents')}>
                <Text style={styles.statusBtnText}>📄 ดูเอกสารที่ส่ง</Text>
              </Pressable>
            </View>
          )}

          {isApproved && (
            <View style={styles.statsRow}>
              <View style={styles.statItem}>
                <Text style={styles.statLabel}>งานทั้งหมด</Text>
                <Text style={styles.statValue}>{riderData.totalJobs || 0}</Text>
              </View>
              <View style={styles.statItem}>
                <Text style={styles.statLabel}>เสร็จสิ้น</Text>
                <Text style={styles.statValue}>{riderData.completedJobs || 0}</Text>
              </View>
              <View style={styles.statItem}>
                <Text style={styles.statLabel}>รายได้รวม</Text>
                <Text style={styles.statValue}>{formatCurrency(riderData.totalEarnings || 0)}</Text>
              </View>
            </View>
          )}
        </LinearGradient>

        {/* Permission Status */}
        <View style={styles.permissionCard}>
          <Text style={styles.permissionTitle}>สถานะสิทธิ์การใช้งาน</Text>

          {[
            { key: 'gps', icon: '📍', label: 'ตำแหน่ง (GPS)', desc: 'แชร์เฉพาะเมื่อรับงาน', type: 'location' as const },
            { key: 'camera', icon: '📷', label: 'กล้อง', desc: 'ถ่ายรูปเอกสาร', type: 'camera' as const },
            { key: 'microphone', icon: '🎤', label: 'ไมโครโฟน', desc: 'โทรหาลูกค้า', type: 'microphone' as const },
          ].map((perm) => (
            <Pressable
              key={perm.key}
              style={styles.permissionRow}
              onPress={() => !riderData.permissions?.[perm.key] && setPermissionModal({ visible: true, type: perm.type })}
            >
              <View style={[styles.permissionIcon, riderData.permissions?.[perm.key] ? styles.permissionIconGranted : styles.permissionIconDenied]}>
                <Text style={{ fontSize: 20, color: riderData.permissions?.[perm.key] ? '#10B981' : '#EF4444' }}>
                  {perm.icon}
                </Text>
              </View>
              <View style={styles.permissionInfo}>
                <Text style={styles.permissionLabel}>{perm.label}</Text>
                <Text style={styles.permissionDesc}>{perm.desc}</Text>
              </View>
              {riderData.permissions?.[perm.key] ? (
                <Text style={{ fontSize: 24, color: '#10B981' }}>✓</Text>
              ) : (
                <Text style={styles.permissionGrantText}>อนุญาต</Text>
              )}
            </Pressable>
          ))}
        </View>

        {/* Quick Actions - Approved Only */}
        {isApproved && (
          <>
            <Text style={styles.sectionTitle}>เมนูลัด</Text>
            <View style={styles.actionsGrid}>
              {[
                { icon: '📋', label: 'งานที่รอรับ', desc: 'ดูรายการงานใหม่', color: '#3B82F6', route: '/rider-jobs' },
                { icon: '🧭', label: 'งานปัจจุบัน', desc: 'ดูงานที่กำลังทำ', color: '#10B981', route: '/rider-job-detail' },
                { icon: '💼', label: 'บริการของฉัน', desc: 'เลือกบริการที่ให้', color: '#EC4899', route: '/rider-services' },
                { icon: '📄', label: 'เอกสาร', desc: 'อัพเดทเอกสาร', color: '#8B5CF6', route: '/rider-documents' },
              ].map((action, i) => (
                <Pressable key={i} style={styles.actionCard} onPress={() => router.push(action.route as any)}>
                  <View style={[styles.actionIcon, { backgroundColor: action.color + '20' }]}>
                    <Text style={{ fontSize: 24, color: action.color }}>{action.icon}</Text>
                  </View>
                  <Text style={styles.actionLabel}>{action.label}</Text>
                  <Text style={styles.actionDesc}>{action.desc}</Text>
                </Pressable>
              ))}
            </View>

            {/* Online/Offline Toggle */}
            <Pressable
              style={[styles.toggleBtn, isOnline ? styles.toggleBtnOffline : styles.toggleBtnOnline, isTogglingAvailability && styles.buttonDisabled]}
              onPress={toggleAvailability}
              disabled={isTogglingAvailability}
            >
              {isTogglingAvailability ? (
                <ActivityIndicator color="#FFF" />
              ) : (
                <Text style={styles.toggleBtnText}>{isOnline ? '🔴 ปิดรับงาน' : '🟢 เปิดรับงาน'}</Text>
              )}
            </Pressable>

            {/* Location Sharing Toggle */}
            <Pressable
              style={[styles.locationShareBtn, isLocationSharing && styles.locationShareBtnActive, isTogglingLocationShare && styles.buttonDisabled]}
              onPress={toggleLocationSharing}
              disabled={isTogglingLocationShare}
            >
              {isTogglingLocationShare ? (
                <ActivityIndicator color={isLocationSharing ? '#FFF' : '#3B82F6'} />
              ) : (
                <>
                  <View style={[styles.locationDot, isLocationSharing && styles.locationDotActive]} />
                  <Text style={[styles.locationShareText, isLocationSharing && styles.locationShareTextActive]}>
                    {isLocationSharing ? '📍 กำลังแชร์ตำแหน่ง' : '📍 แชร์ตำแหน่ง'}
                  </Text>
                </>
              )}
            </Pressable>
          </>
        )}
      </ScrollView>

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

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0F0F23' },
  scrollView: { flex: 1 },
  scrollContent: { paddingHorizontal: 20, paddingBottom: 40 },
  devModeBanner: { backgroundColor: 'rgba(245,158,11,0.2)', borderRadius: 12, padding: 12, marginBottom: 16, borderWidth: 1, borderColor: '#F59E0B' },
  devModeText: { color: '#FBBF24', fontSize: 14, fontWeight: '600', textAlign: 'center' },
  centerBox: { flex: 1, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 24 },
  centerTitle: { fontSize: 20, fontWeight: 'bold', color: '#FFF', marginTop: 16 },
  centerText: { fontSize: 14, color: '#9CA3AF', textAlign: 'center', marginTop: 8 },
  loadingText: { color: '#9CA3AF', marginTop: 12 },
  primaryButton: { backgroundColor: '#3B82F6', paddingHorizontal: 32, paddingVertical: 14, borderRadius: 12, marginTop: 24 },
  primaryButtonLarge: { backgroundColor: '#3B82F6', paddingVertical: 16, borderRadius: 14, marginTop: 16 },
  primaryButtonText: { color: '#FFF', fontSize: 16, fontWeight: 'bold', textAlign: 'center' },
  headerRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingTop: 56, paddingBottom: 16 },
  headerTitle: { fontSize: 22, fontWeight: 'bold', color: '#FFF' },
  backBtn: { width: 44, height: 44, borderRadius: 22, backgroundColor: 'rgba(255,255,255,0.1)', alignItems: 'center', justifyContent: 'center' },
  benefitsCard: { borderRadius: 16, padding: 24, marginBottom: 16 },
  benefitsTitle: { fontSize: 18, fontWeight: 'bold', color: '#FFF', marginBottom: 16 },
  benefitItem: { flexDirection: 'row', alignItems: 'center', marginBottom: 8 },
  benefitText: { color: '#FFF', marginLeft: 8 },
  warningCard: { backgroundColor: 'rgba(245,158,11,0.15)', borderRadius: 12, padding: 16, marginBottom: 16 },
  warningTitle: { color: '#FBBF24', fontWeight: 'bold', marginBottom: 8 },
  warningItem: { color: '#FCD34D', fontSize: 13, marginBottom: 4 },
  formCard: { backgroundColor: 'rgba(255,255,255,0.05)', borderRadius: 16, padding: 20, marginBottom: 16 },
  formTitle: { fontSize: 18, fontWeight: 'bold', color: '#FFF', marginBottom: 16 },
  inputGroup: { marginBottom: 16 },
  label: { color: '#E5E7EB', fontSize: 14, fontWeight: '600', marginBottom: 8 },
  input: { backgroundColor: 'rgba(255,255,255,0.08)', borderRadius: 12, paddingVertical: 14, paddingHorizontal: 16, color: '#FFF', fontSize: 16 },
  vehicleRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  vehicleBtn: { paddingHorizontal: 14, paddingVertical: 10, borderRadius: 10, backgroundColor: 'rgba(255,255,255,0.08)' },
  vehicleBtnActive: { backgroundColor: '#3B82F6' },
  vehicleBtnText: { color: '#9CA3AF', fontWeight: '500' },
  vehicleBtnTextActive: { color: '#FFF' },
  submitBtn: { backgroundColor: '#3B82F6', paddingVertical: 16, borderRadius: 14, marginTop: 8 },
  submitBtnText: { color: '#FFF', fontSize: 16, fontWeight: 'bold', textAlign: 'center' },
  buttonDisabled: { opacity: 0.6 },
  statusCard: { borderRadius: 16, padding: 24, marginBottom: 16 },
  statusRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  statusInfo: { marginLeft: 12, flex: 1 },
  statusTitle: { fontSize: 18, fontWeight: 'bold', color: '#FFF' },
  statusSubtitle: { fontSize: 12, color: 'rgba(255,255,255,0.8)', marginTop: 2 },
  statusNote: { marginTop: 8 },
  statusNoteText: { color: 'rgba(255,255,255,0.9)', marginBottom: 12 },
  statusBtn: { backgroundColor: 'rgba(255,255,255,0.2)', borderRadius: 12, paddingVertical: 8, paddingHorizontal: 16, alignSelf: 'flex-start' },
  statusBtnText: { color: '#FFF', fontWeight: '500' },
  statsRow: { flexDirection: 'row', marginTop: 8 },
  statItem: { flex: 1 },
  statLabel: { fontSize: 12, color: 'rgba(255,255,255,0.7)' },
  statValue: { fontSize: 18, fontWeight: 'bold', color: '#FFF', marginTop: 2 },
  statusBadge: { paddingHorizontal: 12, paddingVertical: 4, borderRadius: 16, backgroundColor: 'rgba(255,255,255,0.1)' },
  statusBadgeOnline: { backgroundColor: 'rgba(16,185,129,0.2)' },
  statusBadgeText: { fontSize: 12, fontWeight: '600', color: '#9CA3AF' },
  statusBadgeTextOnline: { color: '#10B981' },
  permissionCard: { backgroundColor: 'rgba(255,255,255,0.05)', borderRadius: 16, padding: 20, marginBottom: 16 },
  permissionTitle: { fontSize: 16, fontWeight: 'bold', color: '#FFF', marginBottom: 16 },
  permissionRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: 'rgba(255,255,255,0.08)' },
  permissionIcon: { width: 40, height: 40, borderRadius: 20, alignItems: 'center', justifyContent: 'center' },
  permissionIconGranted: { backgroundColor: 'rgba(16,185,129,0.15)' },
  permissionIconDenied: { backgroundColor: 'rgba(239,68,68,0.15)' },
  permissionInfo: { flex: 1, marginLeft: 12 },
  permissionLabel: { fontSize: 15, fontWeight: '500', color: '#FFF' },
  permissionDesc: { fontSize: 12, color: '#6B7280', marginTop: 2 },
  permissionGrantText: { color: '#3B82F6', fontWeight: '600' },
  sectionTitle: { fontSize: 16, fontWeight: 'bold', color: '#FFF', marginBottom: 12, marginTop: 8 },
  actionsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  actionCard: { width: '47%', backgroundColor: 'rgba(255,255,255,0.05)', borderRadius: 16, padding: 16, borderWidth: 1, borderColor: 'rgba(255,255,255,0.08)' },
  actionIcon: { width: 48, height: 48, borderRadius: 12, alignItems: 'center', justifyContent: 'center', marginBottom: 8 },
  actionLabel: { fontSize: 14, fontWeight: 'bold', color: '#FFF' },
  actionDesc: { fontSize: 11, color: '#6B7280', marginTop: 2 },
  toggleBtn: { borderRadius: 16, paddingVertical: 18, marginTop: 16 },
  toggleBtnOnline: { backgroundColor: '#10B981' },
  toggleBtnOffline: { backgroundColor: '#EF4444' },
  toggleBtnText: { color: '#FFF', fontSize: 16, fontWeight: 'bold', textAlign: 'center' },
  locationShareBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', borderRadius: 16, paddingVertical: 18, marginTop: 12, backgroundColor: 'rgba(255,255,255,0.08)', borderWidth: 2, borderColor: 'rgba(255,255,255,0.1)' },
  locationShareBtnActive: { backgroundColor: '#10B981', borderColor: '#34D399' },
  locationDot: { width: 12, height: 12, borderRadius: 6, backgroundColor: '#6B7280', marginRight: 12 },
  locationDotActive: { backgroundColor: '#FFF' },
  locationShareText: { fontSize: 16, fontWeight: 'bold', color: '#9CA3AF' },
  locationShareTextActive: { color: '#FFF' },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'center', alignItems: 'center', paddingHorizontal: 24 },
  modalContent: { backgroundColor: '#1F2937', borderRadius: 24, width: '100%', overflow: 'hidden' },
  modalHeader: { padding: 24, alignItems: 'center' },
  modalIconBox: { width: 64, height: 64, borderRadius: 32, backgroundColor: 'rgba(255,255,255,0.2)', alignItems: 'center', justifyContent: 'center', marginBottom: 12 },
  modalTitle: { fontSize: 18, fontWeight: 'bold', color: '#FFF' },
  modalBody: { padding: 20 },
  modalDesc: { fontSize: 14, color: '#D1D5DB', lineHeight: 22 },
  modalActions: { flexDirection: 'row', padding: 16, borderTopWidth: 1, borderTopColor: 'rgba(255,255,255,0.1)' },
  modalCancelBtn: { flex: 1, paddingVertical: 12, backgroundColor: 'rgba(255,255,255,0.1)', borderRadius: 12, marginRight: 8 },
  modalCancelText: { color: '#9CA3AF', textAlign: 'center', fontWeight: '600' },
  modalGrantBtn: { flex: 1, paddingVertical: 12, borderRadius: 12, marginLeft: 8 },
  modalGrantText: { color: '#FFF', textAlign: 'center', fontWeight: 'bold' },
});
