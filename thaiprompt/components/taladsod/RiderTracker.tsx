/**
 * RiderTracker — ติดตามไรเดอร์แบบสด + แชร์ตำแหน่งของฉันให้ไรเดอร์ (ออเดอร์ร้านค้าและตลาดสด)
 *
 * - GET /orders/{source}/{id}/rider-location ทุก 15 วินาที เฉพาะตอนหน้าจอเปิดอยู่ (ออกจากหน้า = หยุด)
 * - งานไรเดอร์จบแล้ว / ไม่พบออเดอร์ → หยุด poll เอง
 * - สวิตช์ "แชร์ตำแหน่งของฉันให้ไรเดอร์": ConsentSheet ก่อนเสมอ → ขอสิทธิ์ → POST share-location
 *   แล้วส่งตำแหน่งทุก ~30 วินาทีระหว่างเปิดหน้านี้ · ปิดสวิตช์ = share:false (server ลบตำแหน่งทันที)
 *   · 409 JOB_NOT_ACTIVE = ไรเดอร์ยังไม่มา/ส่งเสร็จแล้ว → หยุดส่ง
 *
 * หน้าตา (ธีมรอยัล): แถวสถานะ + ช่องไอคอนสกู๊ตเตอร์ · การ์ดไรเดอร์ (อักษรย่อบนเหรียญน้ำเงิน + ดาว + ปุ่มโทร)
 *   · แผนที่สด · แถวสวิตช์แชร์ตำแหน่ง — ไม่มีการ์ดครอบเอง (หน้าแม่วางใน Card3D)
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, Switch, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import { Button3D, Icon, LiveMap, Pill, resultHaptic, type LiveMapMarker } from '@/components/ui';
import { callPhone } from '@/components/shop';
import { useTheme, radii, spacing, typography } from '@/theme';
import { getCurrentCoords } from '@/services/location';
import {
  getDeliveryRiderLocation,
  shareDeliveryLocation,
  type DeliverySource,
  type RiderLocationInfo,
} from '@/services/api/taladsodApi';
import { useFocusedInterval, useMountedRef } from './hooks';
import { distanceText, timeAgoText } from './helpers';
import { useBuyerLocation } from './useBuyerLocation';
import { IconTile, LiveDot, TileButton } from './BuyerParts';

export interface RiderTrackerProps {
  source: DeliverySource;
  orderId: number;
  /** ออเดอร์ยังวิ่งอยู่ (false = ไม่ poll) */
  enabled: boolean;
  /** แสดงชื่อ/รถ/ปุ่มโทรของไรเดอร์ (หน้าที่มีการ์ดไรเดอร์อยู่แล้วส่ง false) */
  showRiderInfo?: boolean;
  /** ให้แชร์ตำแหน่งของฉันให้ไรเดอร์ */
  allowShare?: boolean;
  /** จุดส่งจากออเดอร์ (ใช้เมื่อ server ยังไม่ส่ง dropoff) */
  dropoffFallback?: { latitude: number; longitude: number } | null;
  /** สถานะงานไรเดอร์เปลี่ยน → หน้าแม่รีเฟรชออเดอร์ */
  onJobStatusChange?: (status: string | null) => void;
}

const DEFAULT_POLL_MS = 15000;

/** อักษรย่อของชื่อไรเดอร์บนเหรียญ (ข้ามสระหน้า เ แ โ ใ ไ และวรรณยุกต์) — ไม่มีชื่อ = null */
const initialOf = (name: string | null | undefined): string | null => {
  for (const ch of String(name || '').trim()) {
    if (/\s/.test(ch)) break;
    if (/[\u0E40-\u0E44\u0E31\u0E34-\u0E3A\u0E47-\u0E4E]/.test(ch)) continue;
    return ch.toUpperCase();
  }
  return null;
};

/** ข้อความสำหรับผู้ซื้อ (อ่านง่ายกว่าข้อความระบบ) */
const REASON_TEXT: Record<string, string> = {
  no_rider_job: 'ร้านจะเรียกไรเดอร์เมื่ออาหารใกล้เสร็จ',
  waiting_for_rider: 'กำลังหาไรเดอร์ใกล้ร้านให้อยู่นะ',
  job_not_active: 'งานส่งจบแล้ว',
  consent_missing: 'ไรเดอร์ยังไม่ได้เปิดแชร์ตำแหน่ง',
  no_gps_yet: 'รอสัญญาณตำแหน่งจากไรเดอร์อยู่',
  gps_stale: 'ตำแหน่งไรเดอร์ไม่อัปเดตสักพัก',
};

export const RiderTracker: React.FC<RiderTrackerProps> = ({
  source,
  orderId,
  enabled,
  showRiderInfo = true,
  allowShare = true,
  dropoffFallback,
  onJobStatusChange,
}) => {
  const { colors, gradients } = useTheme();
  const mountedRef = useMountedRef();
  const location = useBuyerLocation();

  const [info, setInfo] = useState<RiderLocationInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [stopped, setStopped] = useState(false);
  /** null = ใช้ค่าจาก server */
  const [sharingLocal, setSharingLocal] = useState<boolean | null>(null);
  const [shareBusy, setShareBusy] = useState(false);
  const [lastSentAt, setLastSentAt] = useState<number | null>(null);

  const loadingRef = useRef(false);
  const lastJobStatusRef = useRef<string | null | undefined>(undefined);
  const onJobStatusRef = useRef(onJobStatusChange);
  onJobStatusRef.current = onJobStatusChange;

  const load = useCallback(async () => {
    if (loadingRef.current || !orderId) return;
    loadingRef.current = true;
    const res = await getDeliveryRiderLocation(source, orderId);
    loadingRef.current = false;
    if (!mountedRef.current) return;
    setLoading(false);

    if (!res.success) {
      if (res.status === 404) {
        setStopped(true);
        setError(null);
        setInfo(null);
      } else if (!info) {
        setError(res.message);
      }
      return;
    }

    setError(null);
    setInfo(res.data);
    const jobStatus = res.data.job?.status ?? null;
    if (lastJobStatusRef.current !== undefined && lastJobStatusRef.current !== jobStatus) {
      onJobStatusRef.current?.(jobStatus);
    }
    lastJobStatusRef.current = jobStatus;
    // งานจบแล้ว → หยุด poll · ยังไม่มีงานไรเดอร์ → poll ต่อ (ร้านอาจเพิ่งเรียกไรเดอร์)
    if (res.data.has_rider_job && res.data.job && !res.data.job.is_active && res.data.job.status !== 'pending') {
      setStopped(true);
    }
  }, [source, orderId, info, mountedRef]);

  // โหลดครั้งแรก
  useEffect(() => {
    if (enabled) load();
    else setLoading(false);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [enabled, source, orderId]);

  const pollMs = info ? info.poll_interval_seconds * 1000 : DEFAULT_POLL_MS;
  useFocusedInterval(load, pollMs, enabled && !stopped);

  // ---------- แชร์ตำแหน่งให้ไรเดอร์ ----------
  const sharing = sharingLocal ?? info?.customer_sharing.enabled ?? false;
  const canShare = allowShare && enabled && !stopped && !!info?.can_share_location;
  const sendMs = (info?.customer_send_interval_seconds || 30) * 1000;

  const sendLocation = useCallback(async () => {
    const coords = await getCurrentCoords({ accuracy: 'high', timeoutMs: 8000 });
    if (!coords || !mountedRef.current) return;
    const res = await shareDeliveryLocation(source, orderId, true, coords);
    if (!mountedRef.current) return;
    if (res.success) {
      setLastSentAt(Date.now());
      if (!res.data.sharing) setSharingLocal(false);
    } else if (res.code === 'JOB_NOT_ACTIVE') {
      setSharingLocal(false);
    }
  }, [source, orderId, mountedRef]);

  useFocusedInterval(sendLocation, sendMs, canShare && sharing, false);

  const turnOn = async () => {
    if (shareBusy) return;
    const coords = await location.request('share');
    if (!coords || !mountedRef.current) return;
    setShareBusy(true);
    const res = await shareDeliveryLocation(source, orderId, true, coords);
    if (!mountedRef.current) return;
    setShareBusy(false);
    if (res.success) {
      resultHaptic('success');
      setSharingLocal(res.data.sharing);
      setLastSentAt(Date.now());
    } else {
      resultHaptic('error');
      setSharingLocal(false);
      Alert.alert(
        res.code === 'JOB_NOT_ACTIVE' ? 'ยังแชร์ไม่ได้ตอนนี้' : 'แชร์ตำแหน่งไม่สำเร็จ',
        res.code === 'JOB_NOT_ACTIVE' ? 'รอให้ไรเดอร์รับงานก่อน แล้วค่อยเปิดแชร์ตำแหน่งนะ' : res.message
      );
    }
  };

  const turnOff = async () => {
    if (shareBusy) return;
    setShareBusy(true);
    setSharingLocal(false);
    const res = await shareDeliveryLocation(source, orderId, false);
    if (!mountedRef.current) return;
    setShareBusy(false);
    if (res.success) {
      resultHaptic('success');
      setLastSentAt(null);
    } else if (res.code !== 'JOB_NOT_ACTIVE') {
      // ปิดไม่สำเร็จ → คืนสถานะเดิม ให้ผู้ใช้ลองใหม่
      setSharingLocal(true);
      Alert.alert('ปิดการแชร์ไม่สำเร็จ', res.message);
    }
  };

  // ---------- หมุดบนแผนที่ ----------
  const markers = useMemo<LiveMapMarker[]>(() => {
    const out: LiveMapMarker[] = [];
    if (info?.pickup) {
      out.push({ id: 'pickup', kind: 'shop', latitude: info.pickup.latitude, longitude: info.pickup.longitude, label: 'ร้าน' });
    }
    const drop = info?.dropoff || dropoffFallback || null;
    if (drop) {
      out.push({ id: 'dropoff', kind: 'home', latitude: drop.latitude, longitude: drop.longitude, label: 'จุดส่ง' });
    }
    if (info?.rider_location) {
      out.push({
        id: 'rider',
        kind: 'rider',
        latitude: info.rider_location.latitude,
        longitude: info.rider_location.longitude,
        label: info.rider?.name ? `ไรเดอร์ ${info.rider.name}` : 'ไรเดอร์',
      });
    }
    return out;
  }, [info, dropoffFallback]);

  if (!enabled && !info) return null;

  if (loading && !info) {
    return (
      <View style={styles.loadingRow}>
        <ActivityIndicator color={colors.gold} />
        <Text style={[typography.caption, { color: colors.textMuted }]}>กำลังหาตำแหน่งไรเดอร์…</Text>
      </View>
    );
  }

  if (!info) {
    return error ? (
      <View style={styles.loadingRow}>
        <Icon name="warning-circle" size={18} color={colors.warning} weight="fill" />
        <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>{error}</Text>
        <Button3D title="ลองใหม่" icon="arrows-clockwise" size="sm" variant="secondary" onPress={load} />
      </View>
    ) : null;
  }

  const rider = info.rider;
  const loc = info.rider_location;
  const riderToDrop = loc ? distanceText(loc, info.dropoff || dropoffFallback) : null;
  const reasonText = info.reason ? REASON_TEXT[info.reason] || info.reason_text : info.reason_text;
  const statusText =
    !info.has_rider_job || info.reason === 'waiting_for_rider'
      ? reasonText || 'กำลังติดตามสถานะ'
      : info.job?.status_text || reasonText || 'กำลังติดตามสถานะ';
  const caption = loc
    ? `${loc.is_stale ? 'ตำแหน่งไรเดอร์ไม่อัปเดตสักพัก · ' : ''}อัปเดต ${timeAgoText(loc.updated_at) || 'ล่าสุด'}${
        riderToDrop ? ` · ห่างจุดส่ง ~${riderToDrop}` : ''
      }`
    : reasonText && reasonText !== statusText
      ? reasonText
      : undefined; // ข้อความเดียวกับหัวสถานะ → ไม่ต้องพูดซ้ำใต้แผนที่
  const showMap = markers.length > 0 && (info.has_rider_job || !!loc);
  const initial = rider ? initialOf(rider.name) : null;
  const live = !!info.job?.is_active;

  return (
    <View style={styles.root}>
      {location.element}

      {/* ---------- สถานะงานส่ง ---------- */}
      <View style={styles.statusRow}>
        <IconTile icon="moped" tone={live ? 'success' : 'navy'} weight="fill" />
        <Text style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]}>{statusText}</Text>
        {live && <Pill label="สด" tone="success" icon={<LiveDot size={6} />} />}
      </View>

      {/* ---------- การ์ดไรเดอร์ ---------- */}
      {showRiderInfo && rider && (
        <View style={[styles.riderCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.avatar}>
            {initial ? (
              <Text style={[typography.serifSm, styles.avatarText, { color: colors.goldLight }]}>{initial}</Text>
            ) : (
              <Icon name="user" size={22} color={colors.goldLight} weight="fill" />
            )}
          </LinearGradient>
          <View style={styles.flex}>
            <View style={styles.nameRow}>
              <Text numberOfLines={1} style={[typography.bodyStrong, styles.name, { color: colors.textStrong }]}>
                {rider.name || 'ไรเดอร์'}
              </Text>
              {!!rider.rating && (
                <View style={[styles.rating, { backgroundColor: colors.goldSoft }]}>
                  <Icon name="star" size={12} color={colors.gold} weight="fill" />
                  <Text style={[typography.micro, { color: colors.goldDeep }]}>{rider.rating.toFixed(1)}</Text>
                </View>
              )}
            </View>
            <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
              {[rider.vehicle_type_text, rider.vehicle_plate ? `ทะเบียน ${rider.vehicle_plate}` : null].filter(Boolean).join(' · ') ||
                'ไรเดอร์ในชุมชน'}
            </Text>
          </View>
          {!!rider.phone && <TileButton icon="phone" label="โทร" tone="success" onPress={() => callPhone(rider.phone)} />}
        </View>
      )}

      {/* ---------- แผนที่สด ---------- */}
      {showMap && (
        <LiveMap
          markers={markers}
          height={230}
          followId={loc ? 'rider' : undefined}
          openTargetId={loc ? 'rider' : info.dropoff || dropoffFallback ? 'dropoff' : 'pickup'}
          caption={caption}
          accessibilityLabel={loc ? `แผนที่ตำแหน่งไรเดอร์ ${caption || ''}` : 'แผนที่ร้านและจุดส่ง'}
        />
      )}
      {!showMap && info.has_rider_job && !!reasonText && reasonText !== statusText && (
        <Text style={[typography.caption, { color: colors.textMuted }]}>{reasonText}</Text>
      )}

      {/* ---------- แชร์ตำแหน่งของฉัน ---------- */}
      {canShare && (
        <View
          style={[
            styles.shareRow,
            { backgroundColor: sharing ? colors.successSoft : colors.surface, borderColor: colors.border },
          ]}
        >
          <IconTile icon="broadcast" tone={sharing ? 'success' : 'navy'} size={40} />
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>แชร์ตำแหน่งของฉันให้ไรเดอร์</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              {sharing
                ? lastSentAt
                  ? `กำลังแชร์ · ส่งล่าสุด ${timeAgoText(new Date(lastSentAt).toISOString())} · ต้องเปิดหน้านี้ไว้`
                  : 'กำลังแชร์ · ส่งตำแหน่งทุก 30 วินาทีระหว่างเปิดหน้านี้'
                : 'ช่วยให้ไรเดอร์หาคุณเจอง่ายขึ้น เห็นเฉพาะออเดอร์นี้'}
            </Text>
          </View>
          {shareBusy || location.locating ? (
            <ActivityIndicator color={colors.gold} />
          ) : (
            <Switch
              value={sharing}
              onValueChange={(next) => (next ? turnOn() : turnOff())}
              trackColor={{ false: colors.inset, true: colors.success }}
              thumbColor={colors.card}
              ios_backgroundColor={colors.inset}
              accessibilityLabel="แชร์ตำแหน่งของฉันให้ไรเดอร์"
            />
          )}
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  root: {
    gap: spacing.md,
  },
  flex: {
    flex: 1,
  },
  loadingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.sm,
  },
  statusRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  riderCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderRadius: radii.lg,
    borderWidth: 1,
    padding: spacing.md,
  },
  avatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontSize: 20,
    lineHeight: 28,
  },
  nameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  name: {
    flexShrink: 1,
  },
  rating: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 3,
    borderRadius: 7,
    paddingHorizontal: 6,
    paddingVertical: 1,
  },
  shareRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderRadius: radii.lg,
    borderWidth: 1,
    padding: spacing.md,
  },
});

export default RiderTracker;
