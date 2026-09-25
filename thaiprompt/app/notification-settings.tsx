/**
 * ตั้งค่าการแจ้งเตือน — ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * - สถานะสิทธิ์แจ้งเตือนของเครื่อง + ปุ่มเปิด (ขอสิทธิ์ของระบบ → ลงทะเบียน push token กับ server)
 * - ปฏิเสธถาวร → พาไปตั้งค่าเครื่อง · กลับมาจากตั้งค่า → ตรวจสิทธิ์ใหม่อัตโนมัติ
 * - ประเภทการแจ้งเตือน = ช่องแจ้งเตือนจริงของ Android (ออเดอร์/ข้อความ/โปรโมชั่น ฯลฯ) ปิดเปิดได้ในตั้งค่าเครื่อง
 *   (ไม่มีสวิตช์หลอกที่กดแล้วไม่ได้บันทึกที่ไหน)
 * - ทดสอบ = แจ้งเตือนในเครื่อง (ไม่ยิง server ไม่นับโควตาใดๆ)
 * - ไม่แสดง push token บนจอ (PLAY-22)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, AppState, Linking, Platform, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import * as Notifications from 'expo-notifications';
import { router } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  isPushNotificationSupported,
  registerForPushNotifications,
  requestNotificationPermission,
  sendLocalNotification,
} from '@/services/notifications';
import { Button3D, Card3D, EmptyState, Icon, Pill, Screen, resultHaptic, type IconName } from '@/components/ui';
import { MenuGroup, MenuRow, type MenuTone } from '@/components/profile';
import { useTheme, radii, spacing, typography } from '@/theme';

type PermissionState = 'granted' | 'denied' | 'undetermined';

/** ช่องแจ้งเตือนของแอป (ตรงกับ setNotificationChannelAsync ใน services/notifications) */
const CHANNELS: Array<{ id: string; name: string; desc: string; icon: IconName; tone: MenuTone }> = [
  { id: 'orders', name: 'ออเดอร์และงานส่ง', desc: 'ออเดอร์ใหม่ สถานะจัดส่ง งานไรเดอร์', icon: 'receipt', tone: 'success' },
  { id: 'important', name: 'สำคัญ', desc: 'ความปลอดภัยบัญชี การเงิน', icon: 'shield-check', tone: 'danger' },
  { id: 'messages', name: 'ข้อความ', desc: 'ข้อความจากร้าน ทีมงาน และเรื่องที่แจ้ง', icon: 'chat-circle-dots', tone: 'info' },
  { id: 'promotions', name: 'โปรโมชั่น', desc: 'ข่าวสารและดีลพิเศษ (เสียงเบา)', icon: 'gift', tone: 'warning' },
  { id: 'default', name: 'ทั่วไป', desc: 'แจ้งเตือนอื่นๆ จากแอป', icon: 'bell', tone: 'gold' },
];

const openDeviceSettings = () => {
  Linking.openSettings().catch(() => {
    Alert.alert('เปิดตั้งค่าไม่ได้', 'ไปที่ตั้งค่าเครื่อง > แอป > ThaiPrompt > การแจ้งเตือน');
  });
};

export default function NotificationSettingsScreen() {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [loading, setLoading] = useState(true);
  const [permission, setPermission] = useState<PermissionState>('undetermined');
  const [canAskAgain, setCanAskAgain] = useState(true);
  const [supported] = useState(isPushNotificationSupported());
  const [enabling, setEnabling] = useState(false);

  const mountedRef = useRef(true);
  const enablingRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const checkPermission = useCallback(async () => {
    try {
      const res = await Notifications.getPermissionsAsync();
      if (!mountedRef.current) return;
      setPermission(res.status === 'granted' ? 'granted' : res.status === 'denied' ? 'denied' : 'undetermined');
      setCanAskAgain(res.canAskAgain !== false);
    } catch {
      // อ่านสิทธิ์ไม่ได้ → ถือว่ายังไม่ได้เปิด
    } finally {
      if (mountedRef.current) setLoading(false);
    }
  }, []);

  useEffect(() => {
    checkPermission();
    // กลับมาจากตั้งค่าเครื่อง → ตรวจใหม่
    const sub = AppState.addEventListener('change', (state) => {
      if (state === 'active') checkPermission();
    });
    return () => sub.remove();
  }, [checkPermission]);

  const enable = async () => {
    if (enablingRef.current) return;
    if (permission === 'denied' && !canAskAgain) {
      openDeviceSettings();
      return;
    }
    enablingRef.current = true;
    setEnabling(true);
    try {
      const granted = await requestNotificationPermission();
      if (!mountedRef.current) return;
      if (!granted) {
        await checkPermission();
        Alert.alert('ยังไม่ได้เปิดแจ้งเตือน', 'เปิดได้ในตั้งค่าเครื่อง จะได้ไม่พลาดออเดอร์และงานส่งนะ', [
          { text: 'ไว้ก่อน', style: 'cancel' },
          { text: 'เปิดการตั้งค่า', onPress: openDeviceSettings },
        ]);
        return;
      }
      setPermission('granted');
      if (isAuthenticated) {
        const token = await registerForPushNotifications();
        if (!mountedRef.current) return;
        if (!token) {
          Alert.alert('เปิดแล้ว แต่ยังเชื่อมกับบัญชีไม่ได้', 'ตรวจอินเทอร์เน็ตแล้วเปิดหน้านี้ใหม่อีกครั้งนะ');
          return;
        }
      }
      resultHaptic('success');
    } finally {
      enablingRef.current = false;
      if (mountedRef.current) setEnabling(false);
    }
  };

  const sendTest = async () => {
    await sendLocalNotification('ทดสอบการแจ้งเตือน', 'ถ้าเห็นข้อความนี้ แปลว่าแจ้งเตือนใช้งานได้แล้ว', { type: 'test' });
    resultHaptic('success');
  };

  if (loading) {
    return (
      <Screen title="การแจ้งเตือน" scroll={false}>
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </Screen>
    );
  }

  const granted = permission === 'granted';

  return (
    <Screen title="การแจ้งเตือน" subtitle="ไม่พลาดออเดอร์ งานส่ง และความเคลื่อนไหวของเงิน">
      {!supported && (
        <View style={[styles.notice, { backgroundColor: colors.warningSoft }]}>
          <Icon name="warning" size={20} color={colors.warning} weight="fill" />
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.warning }]}>เครื่องนี้รับแจ้งเตือนไม่ได้</Text>
            <Text style={[typography.bodySm, { color: colors.textMuted }]}>
              อีมูเลเตอร์ไม่รองรับการแจ้งเตือน ลองบนมือถือจริงนะ
            </Text>
          </View>
        </View>
      )}

      {/* ---------- สถานะ ---------- */}
      <Card3D gradientBorder padding={spacing.lg} style={styles.block}>
        <View style={styles.statusRow}>
          <View style={[styles.statusIcon, { backgroundColor: granted ? colors.successSoft : colors.dangerSoft }]}>
            <Icon
              name={granted ? 'bell-ringing' : 'bell-simple'}
              size={28}
              color={granted ? colors.success : colors.danger}
              weight="fill"
            />
          </View>
          <View style={styles.flex}>
            <Text style={[typography.h2, { color: colors.textStrong }]}>{granted ? 'เปิดแจ้งเตือนอยู่' : 'ยังไม่ได้เปิดแจ้งเตือน'}</Text>
            <Text style={[typography.bodySm, { color: colors.textMuted }]}>
              {granted
                ? 'ออเดอร์ใหม่และงานส่งจะเด้งทันที'
                : permission === 'denied' && !canAskAgain
                  ? 'ถูกปิดไว้ในตั้งค่าเครื่อง แตะปุ่มด้านล่างเพื่อไปเปิด'
                  : 'เปิดไว้จะได้ไม่พลาดออเดอร์และงานส่ง'}
            </Text>
          </View>
          <Pill label={granted ? 'เปิด' : 'ปิด'} tone={granted ? 'success' : 'danger'} size="md" />
        </View>

        {granted ? (
          <View style={styles.buttons}>
            <Button3D title="ทดสอบ" icon="paper-plane-tilt" variant="secondary" onPress={sendTest} style={styles.flex} />
            <Button3D title="ดูการแจ้งเตือน" icon="bell" variant="navy" onPress={() => router.push('/notifications')} style={styles.flex} />
          </View>
        ) : (
          <Button3D
            title={permission === 'denied' && !canAskAgain ? 'เปิดในตั้งค่าเครื่อง' : 'เปิดการแจ้งเตือน'}
            icon="bell-ringing"
            size="lg"
            fullWidth
            disabled={!supported}
            loading={enabling}
            onPress={enable}
            style={styles.gapTop}
          />
        )}
      </Card3D>

      {/* ---------- ประเภท ---------- */}
      <MenuGroup
        title="ประเภทการแจ้งเตือน"
        subtitle={Platform.OS === 'android' ? 'ปิด-เปิดแต่ละประเภทได้ในตั้งค่าเครื่อง' : 'ปรับเสียงและรูปแบบได้ในตั้งค่าเครื่อง'}
      >
        {CHANNELS.map((ch) => (
          <MenuRow key={ch.id} icon={ch.icon} tone={ch.tone} title={ch.name} subtitle={ch.desc} />
        ))}
        <View style={styles.channelFooter}>
          <Button3D title="ปรับในตั้งค่าเครื่อง" icon="gear-six" variant="secondary" size="sm" onPress={openDeviceSettings} />
        </View>
      </MenuGroup>

      {!isAuthenticated && (
        <EmptyState
          compact
          icon="lock-key"
          title="เข้าสู่ระบบเพื่อรับแจ้งเตือนของบัญชี"
          message="ออเดอร์ งานส่ง และกระเป๋าเงินจะแจ้งมาที่เครื่องนี้หลังเข้าสู่ระบบ"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  block: {
    marginBottom: spacing.xxl,
  },
  notice: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
    padding: spacing.md,
    borderRadius: radii.lg,
    marginBottom: spacing.lg,
  },
  statusRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  statusIcon: {
    width: 56,
    height: 56,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
  },
  buttons: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.lg,
  },
  gapTop: {
    marginTop: spacing.lg,
  },
  channelFooter: {
    padding: spacing.md,
    alignItems: 'flex-start',
  },
});
