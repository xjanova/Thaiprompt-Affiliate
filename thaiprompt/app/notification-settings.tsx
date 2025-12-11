/**
 * Notification Settings Screen - ตั้งค่าการแจ้งเตือน
 *
 * ให้ผู้ใช้สามารถ:
 * - เปิด/ปิด Push Notifications
 * - ลงทะเบียน Push Token กับ server
 * - ตั้งค่าประเภทการแจ้งเตือนที่ต้องการรับ
 */

import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  Switch,
  Alert,
  ActivityIndicator,
  StyleSheet,
  StatusBar,
  Linking,
  Platform,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import * as Notifications from 'expo-notifications';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import {
  registerForPushNotifications,
  requestNotificationPermission,
  isPushNotificationSupported,
  sendLocalNotification,
} from '@/services/notifications';

// =====================================================
// Types
// =====================================================

interface NotificationChannel {
  id: string;
  name: string;
  description: string;
  icon: string;
  enabled: boolean;
}

// =====================================================
// Main Component
// =====================================================

export default function NotificationSettingsScreen() {
  const { isAuthenticated, user } = useAuthStore();
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  // States
  const [isLoading, setIsLoading] = useState(true);
  const [isRegistering, setIsRegistering] = useState(false);
  const [permissionStatus, setPermissionStatus] = useState<string>('undetermined');
  const [pushToken, setPushToken] = useState<string | null>(null);
  const [isDeviceSupported, setIsDeviceSupported] = useState(true);

  // Notification channels (ตั้งค่าประเภทการแจ้งเตือน)
  const [channels, setChannels] = useState<NotificationChannel[]>([
    { id: 'orders', name: 'ออเดอร์', description: 'การแจ้งเตือนเกี่ยวกับคำสั่งซื้อ', icon: '🧾', enabled: true },
    { id: 'wallet', name: 'กระเป๋าเงิน', description: 'การเคลื่อนไหวทางการเงิน', icon: '💰', enabled: true },
    { id: 'promotions', name: 'โปรโมชั่น', description: 'ข่าวสารและโปรโมชั่น', icon: '🎁', enabled: true },
    { id: 'messages', name: 'ข้อความ', description: 'ข้อความจากทีมงานและ Support', icon: '💬', enabled: true },
    { id: 'system', name: 'ระบบ', description: 'การแจ้งเตือนจากระบบ', icon: '⚙️', enabled: true },
  ]);

  // =====================================================
  // Load Initial State
  // =====================================================

  useEffect(() => {
    checkInitialState();
  }, []);

  const checkInitialState = async () => {
    try {
      // ตรวจสอบว่าอุปกรณ์รองรับ Push Notifications หรือไม่
      const supported = isPushNotificationSupported();
      setIsDeviceSupported(supported);

      if (!supported) {
        setIsLoading(false);
        return;
      }

      // ตรวจสอบสถานะ permission
      const { status } = await Notifications.getPermissionsAsync();
      setPermissionStatus(status);

      // ถ้าได้รับอนุญาตแล้ว ดึง token
      if (status === 'granted') {
        try {
          const Constants = await import('expo-constants');
          const projectId = Constants.default.expoConfig?.extra?.eas?.projectId;
          if (projectId) {
            const tokenData = await Notifications.getExpoPushTokenAsync({ projectId });
            setPushToken(tokenData.data);
          }
        } catch (e) {
          console.log('Could not get existing token:', e);
        }
      }
    } catch (error) {
      console.error('Check initial state error:', error);
    } finally {
      setIsLoading(false);
    }
  };

  // =====================================================
  // Handlers
  // =====================================================

  const handleEnablePushNotifications = async () => {
    if (isRegistering) return;
    setIsRegistering(true);

    try {
      // ขอ permission
      const hasPermission = await requestNotificationPermission();

      if (!hasPermission) {
        // แสดง dialog ให้ไปเปิดใน Settings
        Alert.alert(
          'ต้องการสิทธิ์',
          'กรุณาเปิดการแจ้งเตือนในการตั้งค่าของอุปกรณ์',
          [
            { text: 'ยกเลิก', style: 'cancel' },
            {
              text: 'เปิดการตั้งค่า',
              onPress: () => {
                if (Platform.OS === 'ios') {
                  Linking.openURL('app-settings:');
                } else {
                  Linking.openSettings();
                }
              }
            },
          ]
        );
        setIsRegistering(false);
        return;
      }

      // ลงทะเบียน token
      const token = await registerForPushNotifications();

      if (token) {
        setPushToken(token);
        setPermissionStatus('granted');
        Alert.alert('สำเร็จ', 'เปิดการแจ้งเตือนเรียบร้อยแล้ว');
      } else {
        Alert.alert('ข้อผิดพลาด', 'ไม่สามารถลงทะเบียนการแจ้งเตือนได้');
      }
    } catch (error) {
      console.error('Enable push notifications error:', error);
      Alert.alert('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเปิดการแจ้งเตือน');
    } finally {
      setIsRegistering(false);
    }
  };

  const handleTestNotification = async () => {
    if (permissionStatus !== 'granted') {
      Alert.alert('ยังไม่ได้เปิดการแจ้งเตือน', 'กรุณาเปิดการแจ้งเตือนก่อน');
      return;
    }

    await sendLocalNotification(
      '🔔 ทดสอบการแจ้งเตือน',
      'นี่คือการแจ้งเตือนทดสอบจากแอป Thaiprompt',
      { type: 'test' }
    );

    Alert.alert('ส่งแล้ว', 'การแจ้งเตือนทดสอบถูกส่งเรียบร้อยแล้ว');
  };

  const handleChannelToggle = (channelId: string, value: boolean) => {
    setChannels(prev =>
      prev.map(ch => ch.id === channelId ? { ...ch, enabled: value } : ch)
    );
    // TODO: Save to backend
  };

  const handleViewNotifications = () => {
    router.push('/notifications');
  };

  // =====================================================
  // Render
  // =====================================================

  // Not authenticated
  if (!isAuthenticated) {
    return (
      <View style={[styles.container, !isDark && styles.containerLight]}>
        <StatusBar
          barStyle="light-content"
          backgroundColor={isDark ? '#0F172A' : '#3B82F6'}
        />
        <LinearGradient
          colors={isDark ? ['#1E40AF', '#1E3A8A'] : ['#3B82F6', '#2563EB']}
          style={styles.header}
        >
          <View style={styles.headerContent}>
            <Pressable style={styles.backButton} onPress={() => router.back()}>
              <Text style={styles.backButtonText}>‹</Text>
            </Pressable>
            <Text style={styles.headerTitle}>ตั้งค่าการแจ้งเตือน</Text>
            <View style={styles.placeholder} />
          </View>
        </LinearGradient>

        <View style={styles.notAuthContent}>
          <Text style={{ fontSize: 80 }}>🔔</Text>
          <Text style={[styles.notAuthTitle, !isDark && styles.textDark]}>
            การแจ้งเตือน
          </Text>
          <Text style={styles.notAuthText}>
            เข้าสู่ระบบเพื่อตั้งค่าการแจ้งเตือน
          </Text>
          <Pressable style={styles.loginButton} onPress={() => router.push('/login')}>
            <Text style={styles.loginButtonText}>เข้าสู่ระบบ</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  // Loading
  if (isLoading) {
    return (
      <View style={[styles.container, !isDark && styles.containerLight]}>
        <StatusBar
          barStyle="light-content"
          backgroundColor={isDark ? '#0F172A' : '#3B82F6'}
        />
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#3B82F6" />
          <Text style={styles.loadingText}>กำลังโหลด...</Text>
        </View>
      </View>
    );
  }

  return (
    <View style={[styles.container, !isDark && styles.containerLight]}>
      <StatusBar
        barStyle="light-content"
        backgroundColor={isDark ? '#0F172A' : '#3B82F6'}
      />

      {/* Header */}
      <LinearGradient
        colors={isDark ? ['#1E40AF', '#1E3A8A'] : ['#3B82F6', '#2563EB']}
        style={styles.header}
      >
        <View style={styles.headerContent}>
          <Pressable style={styles.backButton} onPress={() => router.back()}>
            <Text style={styles.backButtonText}>‹</Text>
          </Pressable>
          <Text style={styles.headerTitle}>ตั้งค่าการแจ้งเตือน</Text>
          <View style={styles.placeholder} />
        </View>
      </LinearGradient>

      <ScrollView style={styles.scrollView} contentContainerStyle={styles.scrollContent}>
        {/* Device Not Supported Warning */}
        {!isDeviceSupported && (
          <View style={[styles.warningCard, !isDark && styles.cardLight]}>
            <Text style={{ fontSize: 32 }}>⚠️</Text>
            <Text style={[styles.warningTitle, !isDark && styles.textDark]}>
              ไม่รองรับ
            </Text>
            <Text style={styles.warningText}>
              Push Notifications ไม่รองรับบน Simulator/Emulator{'\n'}
              กรุณาทดสอบบนอุปกรณ์จริง
            </Text>
          </View>
        )}

        {/* Push Notification Status Card */}
        <View style={[styles.statusCard, !isDark && styles.cardLight]}>
          <View style={styles.statusHeader}>
            <View style={[
              styles.statusIcon,
              permissionStatus === 'granted' ? styles.statusIconEnabled : styles.statusIconDisabled
            ]}>
              <Text style={{ fontSize: 28 }}>
                {permissionStatus === 'granted' ? '🔔' : '🔕'}
              </Text>
            </View>
            <View style={styles.statusInfo}>
              <Text style={[styles.statusTitle, !isDark && styles.textDark]}>
                Push Notifications
              </Text>
              <Text style={[styles.statusSubtitle, !isDark && styles.textMuted]}>
                {permissionStatus === 'granted'
                  ? 'เปิดใช้งานอยู่'
                  : permissionStatus === 'denied'
                    ? 'ถูกปิดกั้น - เปิดในการตั้งค่า'
                    : 'ยังไม่ได้เปิดใช้งาน'
                }
              </Text>
            </View>
          </View>

          {permissionStatus !== 'granted' ? (
            <Pressable
              style={[styles.enableButton, isRegistering && styles.enableButtonDisabled]}
              onPress={handleEnablePushNotifications}
              disabled={isRegistering || !isDeviceSupported}
            >
              {isRegistering ? (
                <ActivityIndicator color="#FFFFFF" size="small" />
              ) : (
                <>
                  <Text style={{ fontSize: 20, marginRight: 8 }}>🔔</Text>
                  <Text style={styles.enableButtonText}>เปิดการแจ้งเตือน</Text>
                </>
              )}
            </Pressable>
          ) : (
            <View style={styles.enabledActions}>
              <Pressable style={styles.testButton} onPress={handleTestNotification}>
                <Text style={{ fontSize: 16, marginRight: 6 }}>📤</Text>
                <Text style={styles.testButtonText}>ทดสอบ</Text>
              </Pressable>
              <Pressable style={styles.viewButton} onPress={handleViewNotifications}>
                <Text style={{ fontSize: 16, marginRight: 6 }}>📬</Text>
                <Text style={styles.viewButtonText}>ดูการแจ้งเตือน</Text>
              </Pressable>
            </View>
          )}
        </View>

        {/* Token Info (Debug) */}
        {pushToken && (
          <View style={[styles.tokenCard, !isDark && styles.cardLight]}>
            <Text style={[styles.tokenLabel, !isDark && styles.textMuted]}>
              Push Token (สำหรับนักพัฒนา)
            </Text>
            <Text style={[styles.tokenValue, !isDark && styles.textDark]} numberOfLines={1}>
              {pushToken.substring(0, 40)}...
            </Text>
          </View>
        )}

        {/* Notification Channels */}
        <View style={styles.section}>
          <Text style={[styles.sectionTitle, !isDark && styles.sectionTitleLight]}>
            ประเภทการแจ้งเตือน
          </Text>
          <Text style={[styles.sectionSubtitle, !isDark && styles.textMuted]}>
            เลือกประเภทการแจ้งเตือนที่ต้องการรับ
          </Text>
        </View>

        {channels.map((channel) => (
          <View
            key={channel.id}
            style={[styles.channelItem, !isDark && styles.cardLight]}
          >
            <View style={styles.channelIcon}>
              <Text style={{ fontSize: 24 }}>{channel.icon}</Text>
            </View>
            <View style={styles.channelInfo}>
              <Text style={[styles.channelName, !isDark && styles.textDark]}>
                {channel.name}
              </Text>
              <Text style={[styles.channelDesc, !isDark && styles.textMuted]}>
                {channel.description}
              </Text>
            </View>
            <Switch
              value={channel.enabled}
              onValueChange={(value) => handleChannelToggle(channel.id, value)}
              trackColor={{ false: '#374151', true: '#3B82F6' }}
              thumbColor="#FFFFFF"
              disabled={permissionStatus !== 'granted'}
            />
          </View>
        ))}

        {/* Info Card */}
        <View style={[styles.infoCard, !isDark && styles.infoCardLight]}>
          <Text style={{ fontSize: 20 }}>💡</Text>
          <Text style={[styles.infoText, !isDark && styles.textMuted]}>
            การแจ้งเตือนจะช่วยให้คุณไม่พลาดข้อมูลสำคัญ เช่น สถานะออเดอร์ การเคลื่อนไหวทางการเงิน และโปรโมชั่นพิเศษ
          </Text>
        </View>

        {/* Bottom padding */}
        <View style={{ height: 100 }} />
      </ScrollView>
    </View>
  );
}

// =====================================================
// Styles
// =====================================================

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F172A',
  },
  containerLight: {
    backgroundColor: '#F8FAFC',
  },
  header: {
    paddingTop: 50,
    paddingBottom: 16,
    paddingHorizontal: 16,
  },
  headerContent: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  backButtonText: {
    fontSize: 28,
    color: '#FFFFFF',
    fontWeight: 'bold',
    marginTop: -2,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  placeholder: {
    width: 40,
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    color: '#9CA3AF',
    marginTop: 12,
  },
  notAuthContent: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 24,
  },
  notAuthTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 16,
  },
  notAuthText: {
    fontSize: 14,
    color: '#9CA3AF',
    textAlign: 'center',
    marginTop: 8,
  },
  loginButton: {
    backgroundColor: '#3B82F6',
    paddingHorizontal: 32,
    paddingVertical: 14,
    borderRadius: 12,
    marginTop: 24,
  },
  loginButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  textDark: {
    color: '#1E293B',
  },
  textMuted: {
    color: '#64748B',
  },
  warningCard: {
    backgroundColor: 'rgba(245, 158, 11, 0.1)',
    borderRadius: 16,
    padding: 20,
    marginBottom: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(245, 158, 11, 0.2)',
  },
  warningTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#F59E0B',
    marginTop: 8,
  },
  warningText: {
    fontSize: 14,
    color: '#9CA3AF',
    textAlign: 'center',
    marginTop: 8,
  },
  statusCard: {
    backgroundColor: 'rgba(255, 255, 255, 0.05)',
    borderRadius: 16,
    padding: 20,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
  },
  cardLight: {
    backgroundColor: '#FFFFFF',
    borderColor: '#E2E8F0',
  },
  statusHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  statusIcon: {
    width: 56,
    height: 56,
    borderRadius: 28,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 16,
  },
  statusIconEnabled: {
    backgroundColor: 'rgba(16, 185, 129, 0.2)',
  },
  statusIconDisabled: {
    backgroundColor: 'rgba(107, 114, 128, 0.2)',
  },
  statusInfo: {
    flex: 1,
  },
  statusTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  statusSubtitle: {
    fontSize: 14,
    color: '#9CA3AF',
    marginTop: 2,
  },
  enableButton: {
    backgroundColor: '#3B82F6',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 14,
    borderRadius: 12,
  },
  enableButtonDisabled: {
    opacity: 0.6,
  },
  enableButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  enabledActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  testButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(59, 130, 246, 0.1)',
    paddingVertical: 12,
    borderRadius: 10,
    marginRight: 8,
  },
  testButtonText: {
    color: '#3B82F6',
    fontWeight: '600',
  },
  viewButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(16, 185, 129, 0.1)',
    paddingVertical: 12,
    borderRadius: 10,
    marginLeft: 8,
  },
  viewButtonText: {
    color: '#10B981',
    fontWeight: '600',
  },
  tokenCard: {
    backgroundColor: 'rgba(255, 255, 255, 0.05)',
    borderRadius: 12,
    padding: 12,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
  },
  tokenLabel: {
    fontSize: 12,
    color: '#9CA3AF',
    marginBottom: 4,
  },
  tokenValue: {
    fontSize: 12,
    color: '#6B7280',
    fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
  },
  section: {
    marginBottom: 12,
    marginTop: 8,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  sectionTitleLight: {
    color: '#1E293B',
  },
  sectionSubtitle: {
    fontSize: 13,
    color: '#9CA3AF',
    marginTop: 4,
  },
  channelItem: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.05)',
    borderRadius: 12,
    padding: 16,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
  },
  channelIcon: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: 'rgba(59, 130, 246, 0.1)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  channelInfo: {
    flex: 1,
  },
  channelName: {
    fontSize: 15,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  channelDesc: {
    fontSize: 12,
    color: '#9CA3AF',
    marginTop: 2,
  },
  infoCard: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    backgroundColor: 'rgba(59, 130, 246, 0.1)',
    borderRadius: 12,
    padding: 16,
    marginTop: 16,
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.2)',
  },
  infoCardLight: {
    backgroundColor: '#EFF6FF',
    borderColor: '#BFDBFE',
  },
  infoText: {
    flex: 1,
    fontSize: 13,
    color: '#94A3B8',
    marginLeft: 12,
    lineHeight: 20,
  },
});
