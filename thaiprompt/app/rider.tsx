/**
 * หน้าไรเดอร์ (ศูนย์กลาง) — สมัคร · สถานะบัญชี · เริ่ม/หยุดรับงาน · รายได้วันนี้ · สิทธิ์ตำแหน่ง
 *
 * แก้ audit: RIDER-APP-12 (ไม่เปิดติดตามเบื้องหลังตอนไม่มีงาน), RIDER-APP-13/PLAY-13/PLAY-14
 * (prominent disclosure + ออนไลน์ได้ด้วยสิทธิ์ "ขณะใช้แอป"), RIDER-APP-18 (ปุ่มอัปโหลดเอกสารเมื่อยังไม่ครบ),
 * RIDER-APP-21 (ลิงก์หน้ารายได้), RIDER-APP-22 (busy/suspended/rejected แสดงถูก + สมัครใหม่ได้),
 * RIDER-APP-26 (ไม่มีแถวไมโครโฟน)
 * ถอนความยินยอมแชร์ตำแหน่งให้ลูกค้าได้เองในแอป (POST /rider/consent {location_consent:false})
 * — ระหว่างมีงานถอนไม่ได้ (409 HAS_ACTIVE_JOB) · ถอนแล้วต้องยอมรับใหม่ก่อนรับงานถัดไป
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, Linking, Pressable, StyleSheet, Text, View } from 'react-native';
import { router, useFocusEffect } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { useAuthStore } from '@/stores/authStore';
import { useTheme, spacing, radii, typography, toneColors, type Tone } from '@/theme';
import {
  BannerSlider,
  Button3D,
  Card3D,
  EmptyState,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  StatTile,
  WebsiteButton,
  resultHaptic,
} from '@/components/ui';
import {
  getRiderEarnings,
  getRiderStatus,
  setRiderConsent,
  updateRiderPermissions,
  type RiderEarningsResponse,
  type RiderStatus,
  type RiderStatusResponse,
} from '@/services/api/riderApi';
import { num } from '@/services/api/client';
import { addNotificationReceivedListener } from '@/services/notifications';
import {
  getJobTrackingState,
  getLocationPermissionState,
  isLocationServiceEnabled,
  pingRiderLocation,
  reconcileJobTracking,
  type TrackingMode,
} from '@/services/location';
import { useRiderPermissionFlow } from '@/components/rider/useRiderPermissionFlow';
import { useRiderAvailability } from '@/components/rider/useRiderAvailability';
import { RiderRegisterForm } from '@/components/rider/RiderRegisterForm';

/** ส่งตำแหน่งทุกกี่มิลลิวินาทีตอนออนไลน์และเปิดหน้านี้อยู่ (กัน server ปิดรับงานอัตโนมัติ) */
const ONLINE_PING_MS = 45_000;

// =====================================================
// ชิ้นส่วนย่อย
// =====================================================

const PermissionRow: React.FC<{
  icon: string;
  title: string;
  description: string;
  granted: boolean;
  optional?: boolean;
  actionLabel?: string;
  onPress?: () => unknown;
}> = ({ icon, title, description, granted, optional, actionLabel = 'เปิดใช้', onPress }) => {
  const { colors } = useTheme();
  const tone: Tone = granted ? 'success' : optional ? 'neutral' : 'warning';
  const t = toneColors(tone, colors);
  return (
    <View style={[styles.permRow, { borderBottomColor: colors.divider }]}>
      <View style={[styles.permIcon, { backgroundColor: t.bg }]}>
        <Text style={styles.permEmoji}>{icon}</Text>
      </View>
      <View style={styles.flex}>
        <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{title}</Text>
        <Text style={[typography.caption, { color: colors.textMuted }]}>{description}</Text>
      </View>
      {granted ? (
        <Pill label="พร้อม" tone="success" icon="✓" />
      ) : onPress ? (
        <Button3D title={actionLabel} size="sm" variant={optional ? 'secondary' : 'primary'} onPress={onPress} />
      ) : (
        <Pill label={optional ? 'ไม่บังคับ' : 'ยังไม่พร้อม'} tone={optional ? 'neutral' : 'warning'} />
      )}
    </View>
  );
};

const ActionTile: React.FC<{ icon: string; title: string; caption: string; onPress: () => void; highlight?: boolean }> = ({
  icon,
  title,
  caption,
  onPress,
  highlight,
}) => {
  const { colors } = useTheme();
  return (
    <Card3D
      onPress={onPress}
      style={styles.tile}
      padding={spacing.md}
      radius={radii.lg}
      shadow="sm"
      gradientBorder={highlight}
      accessibilityLabel={title}
    >
      <Text style={styles.tileIcon}>{icon}</Text>
      <Text style={[typography.bodyStrong, { color: colors.textStrong }]} numberOfLines={1}>
        {title}
      </Text>
      <Text style={[typography.caption, { color: colors.textMuted }]} numberOfLines={2}>
        {caption}
      </Text>
    </Card3D>
  );
};

// =====================================================
// หน้าจอ
// =====================================================

export default function RiderScreen() {
  const { colors, gradients } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const user = useAuthStore((s) => s.user);

  const [data, setData] = useState<RiderStatusResponse | null>(null);
  const [earnings, setEarnings] = useState<RiderEarningsResponse | null>(null);
  const [earningsLoading, setEarningsLoading] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [trackingMode, setTrackingMode] = useState<TrackingMode | 'none'>('none');
  const [gpsOff, setGpsOff] = useState(false);
  const [bannerKey, setBannerKey] = useState(0);
  const [withdrawing, setWithdrawing] = useState(false);

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);
  const loadedOnceRef = useRef(false);
  const withdrawingRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // เปลี่ยนบัญชี → ล้างข้อมูลของบัญชีเดิมทิ้ง ไม่ให้เห็นข้อมูลคนอื่นแวบขึ้นมา
  const userId = user?.id ?? null;
  useEffect(() => {
    requestIdRef.current += 1;
    loadedOnceRef.current = false;
    setData(null);
    setEarnings(null);
    setShowForm(false);
    setErrorMessage(null);
    setInitialLoading(true);
  }, [userId]);

  const loadEarnings = useCallback(async () => {
    setEarningsLoading(true);
    const result = await getRiderEarnings('today');
    if (!mountedRef.current) return;
    if (result.success) setEarnings(result.data);
    setEarningsLoading(false);
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'silent') => {
      if (!isAuthenticated) {
        setInitialLoading(false);
        return;
      }
      const requestId = ++requestIdRef.current;
      if (mode === 'initial') setInitialLoading(true);
      if (mode === 'refresh') setRefreshing(true);

      const result = await getRiderStatus();
      if (!mountedRef.current || requestId !== requestIdRef.current) return;

      if (result.success) {
        setData(result.data);
        setErrorMessage(null);
        loadedOnceRef.current = true;
        const rider = result.data?.rider;

        // ตัวติดตามตำแหน่งต้องตรงกับงานที่ server บอก (แอปถูกปิดกลางงาน / งานถูกยกเลิก)
        reconcileJobTracking(rider?.active_job_id ?? null)
          .then((m) => mountedRef.current && setTrackingMode(m))
          .catch(() => {});

        // ผู้ใช้ไปเปิดสิทธิ์ตำแหน่งในตั้งค่าเครื่องเอง → แจ้ง server ให้ตรงกัน (RIDER-APP-13)
        if (rider && !rider.permissions?.gps) {
          getLocationPermissionState()
            .then((p) => (p.foreground ? updateRiderPermissions({ gps: true }) : null))
            .catch(() => null);
        }

        if (rider?.status === 'approved') {
          loadEarnings();
        }
      } else if (mode !== 'silent' || !loadedOnceRef.current) {
        setErrorMessage(result.message);
      }

      setInitialLoading(false);
      setRefreshing(false);
    },
    // userId: เปลี่ยนบัญชีแล้วต้องได้ฟังก์ชันใหม่ → หน้าโหลดข้อมูลของบัญชีใหม่ทันที
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [isAuthenticated, loadEarnings, userId]
  );

  const flow = useRiderPermissionFlow({ onServerUpdated: () => load('silent') });
  const { goOnline, goOffline } = useRiderAvailability(flow);

  const rider: RiderStatus | null = data?.rider ?? null;
  const isApproved = rider?.status === 'approved';
  const isOnline = rider?.availability === 'online';
  const isBusy = rider?.availability === 'busy' || !!rider?.active_job_id;

  // เข้าหน้านี้ทุกครั้ง: รีเฟรชเงียบๆ + ตรวจสิทธิ์ในเครื่อง (ผู้ใช้อาจไปเปิดในตั้งค่ามา)
  useFocusEffect(
    useCallback(() => {
      load(loadedOnceRef.current ? 'silent' : 'initial');
      flow.refreshPermissions().catch(() => {});
      isLocationServiceEnabled().then((on) => mountedRef.current && setGpsOff(!on)).catch(() => {});
      getJobTrackingState()
        .then((s) => mountedRef.current && setTrackingMode(s ? s.mode : 'none'))
        .catch(() => {});

      // แจ้งเตือนเรื่องบัญชีไรเดอร์ (อนุมัติ/ระงับ/ออฟไลน์อัตโนมัติ) → รีเฟรช
      const sub = addNotificationReceivedListener((notification) => {
        const type = (notification?.request?.content?.data as Record<string, unknown> | undefined)?.type;
        if (type === 'rider_account' || type === 'rider_job_update') {
          load('silent');
        }
      });
      return () => sub.remove();
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [load])
  );

  // ออนไลน์อยู่และเปิดหน้านี้ → ส่งตำแหน่งเป็นระยะ (เฉพาะตอนเปิดแอป ไม่ติดตามเบื้องหลัง)
  useFocusEffect(
    useCallback(() => {
      if (!isApproved || !isOnline) return undefined;
      pingRiderLocation().catch(() => {});
      const timer = setInterval(() => {
        pingRiderLocation().catch(() => {});
      }, ONLINE_PING_MS);
      return () => clearInterval(timer);
    }, [isApproved, isOnline])
  );

  const onRefresh = useCallback(() => {
    setBannerKey((k) => k + 1);
    load('refresh');
  }, [load]);

  const handleGoOnline = useCallback(async () => {
    const ok = await goOnline({ hasConsent: !!rider?.permissions?.location_consent });
    await load('silent');
    if (ok && mountedRef.current) {
      // รอ sheet ขอสิทธิ์ปิดสนิทก่อน (iOS)
      setTimeout(() => {
        if (!mountedRef.current) return;
        Alert.alert('พร้อมรับทรัพย์แล้ว! 🟢', 'ระบบจะแจ้งเตือนทันทีเมื่อมีงานใกล้คุณ', [
          { text: 'อยู่หน้านี้', style: 'cancel' },
          { text: 'ดูงานใกล้ฉัน', onPress: () => router.push('/rider-jobs' as never) },
        ]);
      }, 450);
    }
  }, [goOnline, load, rider?.permissions?.location_consent]);

  // ---------- ถอนความยินยอมแชร์ตำแหน่งให้ลูกค้า ----------
  const doWithdrawConsent = useCallback(async () => {
    if (withdrawingRef.current) return;
    withdrawingRef.current = true;
    setWithdrawing(true);
    try {
      const result = await setRiderConsent(false);
      if (!mountedRef.current) return;
      if (result.success) {
        resultHaptic('success');
        Alert.alert('ถอนความยินยอมแล้ว', 'ลูกค้าจะไม่เห็นตำแหน่งของคุณอีก ถ้าจะรับงานใหม่ ต้องกดยอมรับอีกครั้งก่อนนะ');
      } else if (result.code === 'HAS_ACTIVE_JOB') {
        resultHaptic('warning');
        Alert.alert('ตอนนี้ยังถอนไม่ได้', 'คุณกำลังส่งงานอยู่ ลูกค้ายังต้องติดตามของ ส่งงานนี้ให้เสร็จก่อนแล้วค่อยถอนนะ');
      } else {
        resultHaptic('error');
        Alert.alert('ถอนความยินยอมไม่สำเร็จ', result.message);
      }
      load('silent');
    } finally {
      withdrawingRef.current = false;
      if (mountedRef.current) setWithdrawing(false);
    }
  }, [load]);

  const handleWithdrawConsent = useCallback(() => {
    if (withdrawingRef.current) return;
    if (isBusy) {
      Alert.alert('ตอนนี้ยังถอนไม่ได้', 'คุณกำลังส่งงานอยู่ ลูกค้ายังต้องติดตามของ ส่งงานนี้ให้เสร็จก่อนแล้วค่อยถอนนะ');
      return;
    }
    Alert.alert(
      'ถอนความยินยอม?',
      'ลูกค้าจะไม่เห็นตำแหน่งของคุณระหว่างส่งอีก และคุณจะรับงานใหม่ไม่ได้จนกว่าจะกดยอมรับอีกครั้ง',
      [
        { text: 'ไม่ถอน', style: 'cancel' },
        { text: 'ถอนความยินยอม', style: 'destructive', onPress: () => doWithdrawConsent() },
      ]
    );
  }, [isBusy, doWithdrawConsent]);

  const handleGoOffline = useCallback(async () => {
    await goOffline();
    await load('silent');
  }, [goOffline, load]);

  const handleRegistered = useCallback(
    (result: { outcome: string; rider: RiderStatus }, message: string) => {
      setShowForm(false);
      setData({ is_rider: true, rider: result.rider });
      load('silent');
      const needDocs = (result.rider?.documents_missing || []).length > 0;
      Alert.alert(
        result.outcome === 'created' ? 'สมัครสำเร็จ! 🎉' : 'ส่งใบสมัครแล้ว',
        needDocs ? `${message || 'บันทึกใบสมัครแล้ว'}\nขั้นต่อไป อัปโหลดเอกสารให้ครบนะ` : message || 'ทีมงานจะตรวจสอบโดยเร็ว',
        needDocs
          ? [
              { text: 'ไว้ก่อน', style: 'cancel' },
              { text: 'อัปโหลดเอกสาร', onPress: () => router.push('/rider-documents' as never) },
            ]
          : [{ text: 'ตกลง' }]
      );
    },
    [load]
  );

  const openJob = useCallback(() => {
    const id = rider?.active_job_id;
    router.push((id ? `/rider-job-detail?id=${id}` : '/rider-job-detail') as never);
  }, [rider?.active_job_id]);

  // =====================================================
  // สถานะพิเศษ
  // =====================================================

  if (!isAuthenticated) {
    return (
      <Screen title="ไรเดอร์">
        <EmptyState
          icon="🛵"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อสมัครเป็นไรเดอร์และเริ่มรับงานส่งใกล้บ้าน"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login' as never)}
        />
      </Screen>
    );
  }

  if (initialLoading && !data) {
    return (
      <Screen title="ไรเดอร์">
        <EmptyState icon="⏳" title="กำลังโหลด..." message="รอสักครู่นะ" />
      </Screen>
    );
  }

  if (!data) {
    return (
      <Screen title="ไรเดอร์" onRefresh={onRefresh} refreshing={refreshing}>
        <EmptyState
          variant="error"
          message={errorMessage || 'โหลดข้อมูลไรเดอร์ไม่สำเร็จ'}
          onAction={() => load('initial')}
        />
      </Screen>
    );
  }

  // =====================================================
  // ยังไม่ได้สมัคร
  // =====================================================

  if (!rider) {
    return (
      <Screen title="มาเป็นไรเดอร์" subtitle="รับงานส่งใกล้บ้าน เลือกเวลาได้เอง" onRefresh={onRefresh} refreshing={refreshing}>
        <Card3D gradientBorder padding={0} style={styles.block}>
          <LinearGradient colors={gradients.hero} style={styles.heroInner}>
            <Text style={styles.heroEmoji}>🛵</Text>
            <Text style={[typography.h1, { color: colors.textStrong }]}>ขับไป รับทรัพย์ไป</Text>
            <Text style={[typography.body, { color: colors.text }]}>
              เห็นค่าส่งก่อนกดรับทุกงาน ส่งเสร็จเงินเข้ากระเป๋าทันที
            </Text>
          </LinearGradient>
          <View style={styles.benefits}>
            {[
              { icon: '💰', text: 'ค่าส่งแสดงชัดก่อนรับงาน ไม่มีหักแอบแฝง' },
              { icon: '⏰', text: 'ออนไลน์เมื่อไหร่ก็ได้ พักเมื่อไหร่ก็ได้' },
              { icon: '📍', text: 'รับงานใกล้ตัว วิ่งไม่ไกล' },
              { icon: '👛', text: 'รายได้เข้ากระเป๋าเงินในแอปทันทีที่ส่งสำเร็จ' },
            ].map((b) => (
              <View key={b.text} style={styles.benefitRow}>
                <Text style={styles.benefitIcon}>{b.icon}</Text>
                <Text style={[typography.body, styles.flex, { color: colors.text }]}>{b.text}</Text>
              </View>
            ))}
          </View>
        </Card3D>

        <Card3D variant="inset" style={styles.block} padding={spacing.lg}>
          <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>สิ่งที่ต้องเตรียม</Text>
          <Text style={[typography.bodySm, { color: colors.textMuted }]}>
            บัตรประชาชน · ใบขับขี่ (มอเตอร์ไซค์/รถยนต์) · เล่มทะเบียนรถ · รูปหน้าตรง
          </Text>
          <Text style={[typography.bodySm, { color: colors.textMuted, marginTop: spacing.sm }]}>
            แอปจะขอใช้ตำแหน่งตอนคุณออนไลน์รับงาน และกล้องตอนถ่ายรูปเอกสาร/หลักฐานการส่ง
          </Text>
        </Card3D>

        {showForm ? (
          <RiderRegisterForm
            defaultName={user?.name}
            defaultPhone={user?.phone}
            onDone={handleRegistered}
            onCancel={() => setShowForm(false)}
          />
        ) : (
          <Button3D title="สมัครเลย" icon="🚀" size="lg" fullWidth onPress={() => setShowForm(true)} />
        )}
      </Screen>
    );
  }

  // =====================================================
  // สมัครแล้ว — การ์ดสถานะตามบัญชี
  // =====================================================

  const docsRequired = rider.documents_required?.length ?? 0;
  const docsMissing = rider.documents_missing?.length ?? 0;
  const docsDone = Math.max(0, docsRequired - docsMissing);
  const canReapply = !!rider.can_reapply;
  const onlineBlock = rider.online_block_reason;

  const statusLook = (() => {
    switch (rider.status) {
      case 'pending':
        return { icon: '⏳', title: 'รอตรวจใบสมัคร', tone: 'warning' as Tone, gradient: gradients.hero };
      case 'rejected':
        return { icon: '📝', title: 'ใบสมัครยังไม่ผ่าน', tone: 'danger' as Tone, gradient: gradients.secondary };
      case 'suspended':
        return { icon: '🚫', title: 'บัญชีถูกระงับชั่วคราว', tone: 'danger' as Tone, gradient: gradients.secondary };
      case 'inactive':
        return { icon: '💤', title: 'บัญชีไม่ได้ใช้งาน', tone: 'neutral' as Tone, gradient: gradients.secondary };
      default:
        if (isBusy) return { icon: '🛵', title: 'กำลังส่งงาน', tone: 'gold' as Tone, gradient: gradients.primary };
        if (isOnline) return { icon: '🟢', title: 'ออนไลน์ รอรับงาน', tone: 'success' as Tone, gradient: gradients.success };
        return { icon: '☕', title: 'พักอยู่ (ออฟไลน์)', tone: 'neutral' as Tone, gradient: gradients.surface };
    }
  })();
  const heroOnAccent = isApproved && (isBusy || isOnline);
  // กำลังส่งงาน = พื้นทอง → ตัวอักษรเข้ม · ออนไลน์ = พื้นเขียวเข้ม → ตัวอักษรขาว
  const heroOnColor = isBusy ? colors.textOnGold : colors.textOnAccent;
  const heroText = heroOnAccent ? heroOnColor : colors.textStrong;
  const heroSub = heroOnAccent ? heroOnColor : colors.textMuted;

  const perms = flow.permissions;
  const fgGranted = perms?.foreground ?? !!rider.permissions?.gps;
  const bgGranted = perms?.background ?? false;
  const consentGiven = !!rider.permissions?.location_consent;

  return (
    <Screen
      title="ไรเดอร์"
      subtitle={rider.full_name}
      onRefresh={onRefresh}
      refreshing={refreshing}
      right={
        isApproved ? (
          <Pill
            label={isBusy ? 'กำลังส่งงาน' : isOnline ? 'ออนไลน์' : 'ออฟไลน์'}
            tone={isBusy ? 'gold' : isOnline ? 'success' : 'neutral'}
            icon={isBusy ? '🛵' : isOnline ? '●' : '○'}
          />
        ) : null
      }
    >
      {/* ---------- การ์ดสถานะ ---------- */}
      <Card3D padding={0} style={styles.block} gradientBorder={isApproved}>
        <LinearGradient colors={statusLook.gradient} style={styles.statusInner}>
          <View style={styles.statusRow}>
            <Text style={styles.statusEmoji}>{statusLook.icon}</Text>
            <View style={styles.flex}>
              <Text style={[typography.h2, { color: heroText }]}>{statusLook.title}</Text>
              <Text style={[typography.caption, { color: heroSub }]}>
                {rider.vehicle_type_text}
                {rider.vehicle_plate ? ` · ${rider.vehicle_plate}` : ''}
              </Text>
            </View>
          </View>

          {rider.status === 'pending' && (
            <Text style={[typography.bodySm, { color: colors.text }]}>
              {docsMissing > 0
                ? `อัปโหลดเอกสารอีก ${docsMissing} รายการ แล้วทีมงานจะเริ่มตรวจใบสมัครให้`
                : 'เอกสารครบแล้ว ทีมงานกำลังตรวจ ปกติใช้เวลา 1-3 วันทำการ'}
            </Text>
          )}
          {rider.status === 'rejected' && (
            <Text style={[typography.bodySm, { color: colors.text }]}>
              {rider.rejection_reason ? `เหตุผล: ${rider.rejection_reason}` : 'แก้ไขข้อมูลหรือเอกสาร แล้วส่งใบสมัครใหม่ได้เลย'}
            </Text>
          )}
          {rider.status === 'suspended' && (
            <Text style={[typography.bodySm, { color: colors.text }]}>
              {rider.suspension_reason ? `เหตุผล: ${rider.suspension_reason}` : 'ติดต่อทีมงานเพื่อสอบถามรายละเอียด'}
            </Text>
          )}
          {rider.status === 'inactive' && (
            <Text style={[typography.bodySm, { color: colors.text }]}>ส่งใบสมัครอีกครั้งเพื่อกลับมารับงาน</Text>
          )}
          {isApproved && isBusy && (
            <Text style={[typography.bodySm, { color: heroSub }]}>ส่งงานนี้ให้เสร็จ แล้วระบบจะเปิดรับงานถัดไปให้เอง</Text>
          )}
          {isApproved && !isBusy && isOnline && (
            <Text style={[typography.bodySm, { color: heroSub }]}>ระบบจะแจ้งเตือนทันทีเมื่อมีงานใกล้คุณ</Text>
          )}
          {isApproved && !isBusy && !isOnline && !onlineBlock && (
            <Text style={[typography.bodySm, { color: heroSub }]}>พร้อมเมื่อไหร่ กดเริ่มรับงานได้เลย</Text>
          )}
          {isApproved && !!onlineBlock && !isBusy && (
            <View style={[styles.blockNote, { backgroundColor: colors.warningSoft }]}>
              <Text style={[typography.bodySm, { color: colors.text }]}>⚠️ {onlineBlock.message}</Text>
            </View>
          )}
        </LinearGradient>
      </Card3D>

      {/* ---------- ปุ่มหลักตามสถานะ ---------- */}
      {isApproved && isBusy && (
        <Button3D title="ไปที่งานที่กำลังส่ง" icon="🧭" size="lg" fullWidth onPress={openJob} style={styles.block} />
      )}
      {isApproved && !isBusy && isOnline && (
        <View style={styles.block}>
          <Button3D title="ดูงานใกล้ฉัน" icon="📋" size="lg" fullWidth onPress={() => router.push('/rider-jobs' as never)} />
          <Button3D
            title="หยุดรับงาน"
            icon="⏸️"
            variant="secondary"
            fullWidth
            onPress={handleGoOffline}
            style={styles.gapTop}
          />
        </View>
      )}
      {isApproved && !isBusy && !isOnline && (
        <Button3D
          title="เริ่มรับงาน"
          icon="🟢"
          variant="success"
          size="lg"
          fullWidth
          disabled={!rider.can_go_online}
          onPress={handleGoOnline}
          loadingText="กำลังเปิดรับงาน..."
          style={styles.block}
        />
      )}
      {(rider.status === 'pending' || canReapply) && docsMissing > 0 && (
        <Button3D
          title={`อัปโหลดเอกสาร (${docsDone}/${docsRequired})`}
          icon="📄"
          size="lg"
          fullWidth
          onPress={() => router.push('/rider-documents' as never)}
          style={styles.block}
        />
      )}
      {(canReapply || rider.status === 'pending') && !showForm && (
        <Button3D
          title={canReapply ? 'แก้ไขแล้วส่งใบสมัครใหม่' : 'แก้ไขใบสมัคร'}
          icon="✏️"
          variant="secondary"
          fullWidth
          onPress={() => setShowForm(true)}
          style={styles.block}
        />
      )}
      {showForm && (
        <RiderRegisterForm
          rider={rider}
          defaultName={user?.name}
          defaultPhone={user?.phone}
          onDone={handleRegistered}
          onCancel={() => setShowForm(false)}
        />
      )}
      {rider.status === 'suspended' && (
        <Button3D
          title="ติดต่อทีมงาน"
          icon="💬"
          variant="secondary"
          fullWidth
          onPress={() => router.push('/support' as never)}
          style={styles.block}
        />
      )}

      {/* ---------- แบนเนอร์แคมเปญไรเดอร์ ---------- */}
      {isApproved && (
        <View style={styles.bleed}>
          <BannerSlider placement="rider" height={148} refreshKey={bannerKey} />
        </View>
      )}

      {/* ---------- รายได้วันนี้ ---------- */}
      {isApproved && (
        <>
          <SectionHeader
            title="รายได้วันนี้"
            icon="💰"
            actionLabel="ดูทั้งหมด"
            onAction={() => router.push('/rider-earnings' as never)}
            style={styles.section}
          />
          <View style={styles.grid}>
            <StatTile
              label="รายได้วันนี้"
              icon="💸"
              tone="gold"
              loading={earningsLoading && !earnings}
              value={<PriceText amount={earnings?.gross_earnings ?? 0} size="lg" tone="gold" />}
              onPress={() => router.push('/rider-earnings' as never)}
              style={styles.gridItem}
            />
            <StatTile
              label="ส่งสำเร็จวันนี้"
              icon="📦"
              tone="success"
              loading={earningsLoading && !earnings}
              value={earnings?.completed_jobs ?? 0}
              caption="งาน"
              style={styles.gridItem}
            />
            <StatTile
              label="ยอดในกระเป๋า"
              icon="👛"
              tone="info"
              value={<PriceText amount={rider.wallet_balance} size="lg" />}
              onPress={() => router.push('/(tabs)/wallet' as never)}
              style={styles.gridItem}
            />
            <StatTile
              label="วงเงินรับงานเก็บเงินปลายทาง"
              icon="💵"
              tone="warning"
              value={<PriceText amount={rider.cod_credit_available} size="lg" />}
              style={styles.gridItem}
            />
          </View>
        </>
      )}

      {/* ---------- เมนูลัด ---------- */}
      <SectionHeader title="เมนูไรเดอร์" icon="🧰" style={styles.section} />
      <View style={styles.grid}>
        {isApproved && (
          <ActionTile icon="📋" title="งานใกล้ฉัน" caption="ดูงานที่รอคนรับ" onPress={() => router.push('/rider-jobs' as never)} />
        )}
        {isApproved && isBusy && (
          <ActionTile icon="🧭" title="งานปัจจุบัน" caption="ขั้นตอนและนำทาง" onPress={openJob} highlight />
        )}
        {isApproved && (
          <ActionTile icon="📊" title="รายได้ & ประวัติ" caption="สรุปรายวัน รายเดือน" onPress={() => router.push('/rider-earnings' as never)} />
        )}
        <ActionTile
          icon="📄"
          title="เอกสาร"
          caption={docsMissing > 0 ? `ยังขาด ${docsMissing} รายการ` : rider.documents_pending_review ? 'รอทีมงานตรวจ' : 'ครบแล้ว'}
          onPress={() => router.push('/rider-documents' as never)}
          highlight={docsMissing > 0}
        />
      </View>

      {/* ---------- ตำแหน่ง & ความเป็นส่วนตัว ---------- */}
      <SectionHeader
        title="ตำแหน่ง & ความเป็นส่วนตัว"
        subtitle="ใช้ตำแหน่งเฉพาะตอนออนไลน์หรือกำลังส่งงาน"
        icon="📍"
        style={styles.section}
      />
      <Card3D padding={spacing.md} style={styles.block}>
        {gpsOff && (
          <Pressable
            onPress={() => Linking.openSettings().catch(() => {})}
            style={[styles.gpsOff, { backgroundColor: colors.dangerSoft }]}
            accessibilityRole="button"
          >
            <Text style={[typography.bodySm, { color: colors.danger }]}>⚠️ GPS ของเครื่องปิดอยู่ — แตะเพื่อเปิดในตั้งค่า</Text>
          </Pressable>
        )}
        <PermissionRow
          icon="📍"
          title="ตำแหน่งขณะใช้แอป"
          description="จำเป็นสำหรับเริ่มรับงานและนำทาง"
          granted={fgGranted}
          onPress={() => flow.ensureForeground().then(() => load('silent'))}
        />
        <PermissionRow
          icon="🤝"
          title="แชร์ตำแหน่งให้ลูกค้าระหว่างส่ง"
          description="ลูกค้าเห็นเฉพาะออเดอร์ที่คุณกำลังส่ง ต้องยอมรับก่อนรับงานแรก"
          granted={consentGiven}
          actionLabel="ยอมรับ"
          onPress={() => flow.requestConsent().then(() => load('silent'))}
        />
        {consentGiven && (
          <Button3D
            title="ถอนความยินยอม"
            size="sm"
            variant="ghost"
            loading={withdrawing}
            loadingText="กำลังถอน…"
            onPress={handleWithdrawConsent}
            style={styles.revoke}
            accessibilityHint="ลูกค้าจะไม่เห็นตำแหน่งของคุณอีก และต้องยอมรับใหม่ก่อนรับงาน"
          />
        )}
        <PermissionRow
          icon="🛰️"
          title="ติดตามต่อแม้ปิดหน้าจอ"
          description={
            bgGranted
              ? 'ทำงานเฉพาะช่วงมีงาน และหยุดเองเมื่อจบงาน'
              : 'ไม่บังคับ — ถ้าไม่เปิด ตำแหน่งจะอัปเดตเฉพาะตอนเปิดแอปไว้'
          }
          granted={bgGranted}
          optional
          actionLabel="ตั้งค่า"
          onPress={
            fgGranted
              ? () => flow.offerBackground({ force: true }).then(() => load('silent')) // มีงานอยู่ → สลับไปติดตามแบบเบื้องหลัง
              : undefined
          }
        />
        {isBusy && (
          <Text style={[typography.caption, styles.trackingNote, { color: colors.textMuted }]}>
            {trackingMode === 'background'
              ? '📡 กำลังแชร์ตำแหน่งงานปัจจุบัน (ทำงานแม้ปิดหน้าจอ)'
              : trackingMode === 'foreground'
                ? '📡 กำลังแชร์ตำแหน่งงานปัจจุบัน (เฉพาะตอนเปิดแอป)'
                : '⚠️ ยังไม่ได้แชร์ตำแหน่งงานปัจจุบัน — เปิดสิทธิ์ตำแหน่งก่อนนะ'}
          </Text>
        )}
      </Card3D>

      {/* ---------- ข้อมูลบัญชี ---------- */}
      {isApproved && (
        <>
          <SectionHeader title="ผลงานของฉัน" icon="⭐" style={styles.section} />
          <View style={styles.grid}>
            <StatTile
              label="คะแนน"
              icon="⭐"
              tone="gold"
              value={rider.rating_count > 0 ? num(rider.rating).toFixed(1) : '-'}
              caption={rider.rating_count > 0 ? `จาก ${rider.rating_count} รีวิว` : 'ยังไม่มีรีวิว'}
              style={styles.gridItem}
            />
            <StatTile
              label="ส่งสำเร็จทั้งหมด"
              icon="🏁"
              tone="success"
              value={rider.completed_jobs}
              caption={rider.total_jobs > 0 ? `สำเร็จ ${Math.round(num(rider.completion_rate))}%` : undefined}
              style={styles.gridItem}
            />
          </View>
        </>
      )}

      {!!rider.id && (
        <View style={styles.webBox}>
          <Text style={[typography.caption, styles.center, { color: colors.textMuted }]}>
            แก้ไขยานพาหนะ ตั้งค่างาน และดูรายงานเต็มได้บนเว็บไซต์
          </Text>
          <WebsiteButton path="/user/rider" label="จัดการบัญชีไรเดอร์บนเว็บไซต์" fullWidth />
        </View>
      )}

      {flow.element}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    textAlign: 'center',
  },
  block: {
    marginBottom: spacing.lg,
  },
  gapTop: {
    marginTop: spacing.sm,
  },
  section: {
    marginTop: spacing.sm,
    marginBottom: spacing.md,
  },
  bleed: {
    marginHorizontal: -spacing.screen,
    marginBottom: spacing.md,
  },
  heroInner: {
    padding: spacing.xl,
    gap: spacing.xs,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
  },
  heroEmoji: {
    fontSize: 44,
    marginBottom: spacing.xs,
  },
  benefits: {
    padding: spacing.lg,
    gap: spacing.md,
  },
  benefitRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  benefitIcon: {
    fontSize: 22,
    width: 30,
    textAlign: 'center',
  },
  statusInner: {
    padding: spacing.lg,
    gap: spacing.sm,
    borderRadius: radii.xl,
  },
  statusRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  statusEmoji: {
    fontSize: 36,
  },
  blockNote: {
    borderRadius: radii.md,
    padding: spacing.sm,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    marginBottom: spacing.lg,
  },
  gridItem: {
    flexBasis: '47%',
    flexGrow: 1,
  },
  tile: {
    flexBasis: '47%',
    flexGrow: 1,
    minHeight: 112,
  },
  tileIcon: {
    fontSize: 28,
    marginBottom: spacing.xs,
  },
  permRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.md,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
  permIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  permEmoji: {
    fontSize: 20,
  },
  gpsOff: {
    borderRadius: radii.md,
    padding: spacing.sm,
    marginBottom: spacing.xs,
  },
  trackingNote: {
    marginTop: spacing.md,
  },
  revoke: {
    alignSelf: 'flex-end',
    marginTop: spacing.xs,
  },
  webBox: {
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
});
