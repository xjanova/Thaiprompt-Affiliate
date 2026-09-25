/**
 * งานใกล้ฉัน — รายการงานที่รอไรเดอร์รับ
 *
 * - โหลดใหม่ทุก 15 วินาทีตอนเปิดหน้านี้อยู่ + ทันทีเมื่อมีแจ้งเตือนงานใหม่ (RIDER-APP-11)
 * - ออฟไลน์/มีงานค้าง/ไม่มีตำแหน่ง = การ์ดบอกเหตุผลพร้อมปุ่มแก้ (ไม่ใช่ error — RIDER-APP-15)
 * - ปุ่มรับงานกันกดซ้ำ + ข้อความไทยชัดทุกกรณี (RIDER-APP-16) แล้วพาไปหน้างานทันที
 * - ตัวเลขทุกตัวมาจาก server (ค่าส่ง/รายได้/ระยะทาง) — RIDER-APP-02
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { router, useFocusEffect } from 'expo-router';
import { useTheme, spacing, radii, typography } from '@/theme';
import { Button3D, Card3D, EmptyState, Pill, PriceText, Screen, formatBaht } from '@/components/ui';
import {
  getAvailableJobs,
  getRiderStatus,
  rejectRiderJob,
  type AvailableJobsResponse,
  type RiderJobSummary,
} from '@/services/api/riderApi';
import { addNotificationReceivedListener } from '@/services/notifications';
import { getCurrentCoords, pingRiderLocation } from '@/services/location';
import { useRiderPermissionFlow } from '@/components/rider/useRiderPermissionFlow';
import { useRiderAvailability } from '@/components/rider/useRiderAvailability';
import { useAcceptJob } from '@/components/rider/useAcceptJob';
import { formatKm, formatMinutes, formatTime } from '@/components/rider/riderHelpers';

const POLL_MS = 15_000;

// =====================================================
// การ์ดงาน
// =====================================================

const JobCard: React.FC<{
  job: RiderJobSummary;
  accepting: boolean;
  disabled: boolean;
  onAccept: () => Promise<void>;
  onSkip: () => Promise<void>;
  onOpen: () => void;
}> = ({ job, accepting, disabled, onAccept, onSkip, onOpen }) => {
  const { colors } = useTheme();
  const toPickup = formatKm(job.distance_to_pickup_km);
  const tripKm = formatKm(job.distance_km);
  const eta = formatMinutes(job.estimated_duration_minutes);

  return (
    <Card3D
      onPress={onOpen}
      style={styles.card}
      padding={spacing.lg}
      radius={radii.xl}
      gradientBorder={job.rider_earnings >= 60}
      accessibilityLabel={`งาน ${job.title} ได้รับ ${job.rider_earnings} บาท`}
      accessibilityHint="แตะเพื่อดูรายละเอียดงาน"
    >
      <View style={styles.cardTop}>
        <View style={styles.pills}>
          <Pill label={job.job_type_text || 'งานส่ง'} tone="info" />
          {job.is_cod && <Pill label="เก็บเงินปลายทาง" tone="warning" icon="💵" />}
        </View>
        {!!job.created_at && (
          <Text style={[typography.micro, { color: colors.textFaint }]}>{formatTime(job.created_at)}</Text>
        )}
      </View>

      <View style={styles.earnRow}>
        <View style={styles.flex}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>คุณได้รับ</Text>
          <PriceText amount={job.rider_earnings} size="xl" tone="gold" />
        </View>
        <View style={styles.metaBox}>
          {!!toPickup && (
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>🛵 ห่าง {toPickup}</Text>
          )}
          {!!tripKm && (
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              ระยะส่ง {tripKm}
              {eta ? ` · ${eta}` : ''}
            </Text>
          )}
        </View>
      </View>

      <View style={[styles.route, { backgroundColor: colors.inset }]}>
        <View style={styles.routeRow}>
          <Text style={styles.routeIcon}>📦</Text>
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]} numberOfLines={1}>
              {job.pickup?.name || 'จุดรับของ'}
            </Text>
            {!!job.pickup?.address && (
              <Text style={[typography.caption, { color: colors.textMuted }]} numberOfLines={2}>
                {job.pickup.address}
              </Text>
            )}
          </View>
        </View>
        <View style={[styles.routeLine, { backgroundColor: colors.border }]} />
        <View style={styles.routeRow}>
          <Text style={styles.routeIcon}>🏠</Text>
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]} numberOfLines={1}>
              {job.dropoff?.area || job.dropoff?.address || 'จุดส่ง'}
            </Text>
            {job.dropoff?.is_approximate && (
              <Text style={[typography.caption, { color: colors.textMuted }]}>ที่อยู่เต็มจะแสดงหลังรับงาน</Text>
            )}
          </View>
        </View>
      </View>

      {!!job.items_summary && (
        <Text style={[typography.bodySm, styles.items, { color: colors.text }]} numberOfLines={2}>
          🧾 {job.items_summary}
        </Text>
      )}
      {job.is_cod && (
        <Text style={[typography.caption, { color: colors.warning }]}>
          💵 ต้องเก็บเงินสดจากลูกค้า {formatBaht(job.cod_amount)}
        </Text>
      )}

      <View style={styles.cardActions}>
        <Button3D
          title="ไม่สนใจ"
          variant="ghost"
          size="sm"
          disabled={disabled}
          onPress={onSkip}
          accessibilityHint="ซ่อนงานนี้"
        />
        <Button3D
          title="รับงานนี้"
          icon="⚡"
          variant="success"
          size="lg"
          disabled={disabled && !accepting}
          loading={accepting}
          loadingText="กำลังรับงาน..."
          onPress={onAccept}
          style={styles.flex}
        />
      </View>
    </Card3D>
  );
};

// =====================================================
// หน้าจอ
// =====================================================

export default function RiderJobsScreen() {
  const { colors } = useTheme();

  const [data, setData] = useState<AvailableJobsResponse | null>(null);
  const [hasConsent, setHasConsent] = useState(true);
  const [initialLoading, setInitialLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<{ code: string; message: string } | null>(null);
  const [updatedAt, setUpdatedAt] = useState<Date | null>(null);
  const [hidden, setHidden] = useState<Set<number>>(new Set());

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);
  const inFlightRef = useRef(false);
  const lastReasonRef = useRef<AvailableJobsResponse['reason'] | undefined>(undefined);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(async (mode: 'initial' | 'refresh' | 'poll') => {
    // รอบ poll ไม่ซ้อนกับรอบที่ยังไม่เสร็จ
    if (mode === 'poll' && inFlightRef.current) return;
    inFlightRef.current = true;
    const requestId = ++requestIdRef.current;
    if (mode === 'initial') setInitialLoading(true);
    if (mode === 'refresh') setRefreshing(true);

    try {
      const coords = await getCurrentCoords({ timeoutMs: 5000 });
      if (coords && lastReasonRef.current !== 'offline') {
        // ออนไลน์อยู่ → ให้ server มีตำแหน่งสด (รับงานได้ + ไม่ถูกปิดรับงานอัตโนมัติ)
        pingRiderLocation().catch(() => {});
      }
      const result = await getAvailableJobs(
        coords ? { latitude: coords.latitude, longitude: coords.longitude } : undefined
      );
      if (!mountedRef.current || requestId !== requestIdRef.current) return;

      if (result.success) {
        lastReasonRef.current = result.data?.reason ?? null;
        setData(result.data);
        setError(null);
        setUpdatedAt(new Date());
      } else if (mode !== 'poll') {
        setError({ code: result.code, message: result.message });
      }
    } finally {
      inFlightRef.current = false;
      if (mountedRef.current && requestId === requestIdRef.current) {
        setInitialLoading(false);
        setRefreshing(false);
      }
    }
  }, []);

  const loadConsent = useCallback(async () => {
    const result = await getRiderStatus();
    if (mountedRef.current && result.success) {
      setHasConsent(!!result.data?.rider?.permissions?.location_consent);
    }
  }, []);

  const flow = useRiderPermissionFlow({
    onServerUpdated: () => {
      loadConsent();
      load('refresh');
    },
  });
  const { goOnline } = useRiderAvailability(flow);

  const removeJob = useCallback((jobId: number) => {
    setHidden((prev) => new Set(prev).add(jobId));
    setData((prev) => (prev ? { ...prev, jobs: prev.jobs.filter((j) => j.id !== jobId) } : prev));
  }, []);

  const { accept, acceptingId } = useAcceptJob({
    flow,
    onGone: (jobId) => {
      removeJob(jobId);
      load('refresh');
    },
    onAccepted: (job) => {
      router.replace((job?.id ? `/rider-job-detail?id=${job.id}&accepted=1` : '/rider-job-detail') as never);
    },
  });

  // เปิดหน้านี้: โหลด + poll ทุก 15 วินาที + ฟังแจ้งเตือนงานใหม่
  useFocusEffect(
    useCallback(() => {
      load(data ? 'poll' : 'initial');
      loadConsent();
      const timer = setInterval(() => load('poll'), POLL_MS);
      const sub = addNotificationReceivedListener((notification) => {
        const type = (notification?.request?.content?.data as Record<string, unknown> | undefined)?.type;
        if (type === 'rider_job_offer' || type === 'rider_job_update' || type === 'rider_account') {
          load('poll');
        }
      });
      return () => {
        clearInterval(timer);
        sub.remove();
      };
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [load, loadConsent])
  );

  const skipJob = useCallback(
    async (job: RiderJobSummary) => {
      removeJob(job.id);
      await rejectRiderJob(job.id); // ซ่อนไม่สำเร็จก็ไม่เป็นไร รอบหน้าอาจเห็นอีก
    },
    [removeJob]
  );

  const handleGoOnline = useCallback(async () => {
    await goOnline({ hasConsent });
    await loadConsent();
    await load('refresh');
  }, [goOnline, hasConsent, load, loadConsent]);

  // =====================================================

  if (initialLoading && !data) {
    return (
      <Screen title="งานใกล้ฉัน">
        <EmptyState icon="🔍" title="กำลังหางานใกล้คุณ..." message="รอสักครู่นะ" />
      </Screen>
    );
  }

  if (!data) {
    const notApproved = error?.code === 'NOT_APPROVED' || error?.code === 'NOT_RIDER';
    return (
      <Screen title="งานใกล้ฉัน" onRefresh={() => load('refresh')} refreshing={refreshing}>
        {notApproved ? (
          <EmptyState
            icon="🛵"
            title="ยังรับงานไม่ได้"
            message={error?.message || 'บัญชีไรเดอร์ยังไม่พร้อมใช้งาน'}
            actionLabel="ไปหน้าไรเดอร์"
            onAction={() => router.replace('/rider' as never)}
          />
        ) : (
          <EmptyState variant="error" message={error?.message} onAction={() => load('initial')} />
        )}
        {flow.element}
      </Screen>
    );
  }

  const jobs = data.jobs.filter((j) => !hidden.has(j.id));
  const block = data.block_reason;
  const showConsentBanner = data.reason === null && (block?.code === 'CONSENT_REQUIRED' || !hasConsent);

  const header = (
    <View>
      {/* แถบสถานะ live */}
      <View style={styles.liveRow}>
        <View style={[styles.liveDot, { backgroundColor: data.reason === null ? colors.success : colors.textFaint }]} />
        <Text style={[typography.caption, { color: colors.textMuted }]}>
          {data.reason === null
            ? `อัปเดตอัตโนมัติทุก 15 วินาที${updatedAt ? ` · ล่าสุด ${formatTime(updatedAt.toISOString())}` : ''}`
            : 'ยังไม่ได้ค้นหางาน'}
        </Text>
      </View>

      {data.reason === 'offline' && (
        <Card3D gradientBorder style={styles.card} padding={spacing.lg}>
          <Text style={styles.reasonIcon}>☕</Text>
          <Text style={[typography.h3, { color: colors.textStrong }]}>คุณยังปิดรับงานอยู่</Text>
          <Text style={[typography.bodySm, styles.reasonText, { color: colors.textMuted }]}>
            กดเริ่มรับงานเพื่อดูงานใกล้คุณ และรับแจ้งเตือนงานใหม่ทันที
          </Text>
          <Button3D title="เริ่มรับงาน" icon="🟢" variant="success" size="lg" fullWidth onPress={handleGoOnline} />
        </Card3D>
      )}

      {data.reason === 'busy' && (
        <Card3D gradientBorder style={styles.card} padding={spacing.lg}>
          <Text style={styles.reasonIcon}>🛵</Text>
          <Text style={[typography.h3, { color: colors.textStrong }]}>คุณมีงานที่กำลังส่งอยู่</Text>
          <Text style={[typography.bodySm, styles.reasonText, { color: colors.textMuted }]}>
            ส่งงานนี้ให้เสร็จก่อน แล้วค่อยรับงานถัดไปนะ
          </Text>
          <Button3D
            title="ไปที่งานปัจจุบัน"
            icon="🧭"
            size="lg"
            fullWidth
            onPress={() =>
              router.push(
                (data.active_job_id ? `/rider-job-detail?id=${data.active_job_id}` : '/rider-job-detail') as never
              )
            }
          />
        </Card3D>
      )}

      {data.reason === 'no_location' && (
        <Card3D style={styles.card} padding={spacing.lg}>
          <Text style={styles.reasonIcon}>📍</Text>
          <Text style={[typography.h3, { color: colors.textStrong }]}>ยังไม่รู้ตำแหน่งของคุณ</Text>
          <Text style={[typography.bodySm, styles.reasonText, { color: colors.textMuted }]}>
            เปิด GPS และอนุญาตตำแหน่ง เพื่อหางานที่ใกล้คุณที่สุด
          </Text>
          <Button3D
            title="เปิดตำแหน่ง"
            icon="📍"
            fullWidth
            onPress={async () => {
              if (await flow.ensureForeground()) {
                await pingRiderLocation({ force: true });
                await load('refresh');
              }
            }}
          />
        </Card3D>
      )}

      {showConsentBanner && (
        <Card3D variant="inset" style={styles.card} padding={spacing.md}>
          <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>🤝 ยอมรับการแชร์ตำแหน่งก่อนรับงานแรก</Text>
          <Text style={[typography.caption, styles.reasonText, { color: colors.textMuted }]}>
            ลูกค้าเห็นตำแหน่งคุณเฉพาะออเดอร์ที่กำลังส่ง และหยุดเองเมื่อจบงาน
          </Text>
          <Button3D title="ยอมรับ" size="sm" variant="success" onPress={() => flow.requestConsent()} />
        </Card3D>
      )}

      {data.reason === null && block && block.code !== 'CONSENT_REQUIRED' && (
        <Card3D variant="inset" style={styles.card} padding={spacing.md}>
          <Text style={[typography.bodySm, { color: colors.text }]}>⚠️ {block.message}</Text>
          {block.code === 'LOCATION_STALE' && (
            <Button3D
              title="ส่งตำแหน่งตอนนี้"
              size="sm"
              icon="📍"
              onPress={async () => {
                if (await flow.ensureForeground()) {
                  await pingRiderLocation({ force: true });
                  await load('refresh');
                }
              }}
              style={styles.gapTop}
            />
          )}
        </Card3D>
      )}

      {data.reason === null && jobs.length > 0 && (
        <Text style={[typography.bodyStrong, styles.countText, { color: colors.textStrong }]}>
          มี {jobs.length} งานรอคุณอยู่ 🔥
        </Text>
      )}
    </View>
  );

  return (
    <Screen
      title="งานใกล้ฉัน"
      subtitle="เลือกงานที่ใช่ แล้วกดรับได้เลย"
      scroll={false}
      right={<Button3D title="รายได้" icon="📊" size="sm" variant="secondary" onPress={() => router.push('/rider-earnings' as never)} />}
    >
      <FlatList
        data={data.reason === null ? jobs : []}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => (
          <JobCard
            job={item}
            accepting={acceptingId === item.id}
            disabled={acceptingId !== null}
            onAccept={() => accept(item.id)}
            onSkip={() => skipJob(item)}
            onOpen={() => router.push(`/rider-job-detail?id=${item.id}` as never)}
          />
        )}
        ListHeaderComponent={header}
        ListEmptyComponent={
          data.reason === null ? (
            <EmptyState
              icon="🛵"
              title="ยังไม่มีงานใกล้คุณตอนนี้"
              message="เปิดหน้านี้ค้างไว้ได้เลย งานใหม่จะเด้งขึ้นมาเอง และมีแจ้งเตือนทันที"
              compact
            />
          ) : null
        }
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load('refresh')}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
          />
        }
      />
      {flow.element}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  list: {
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.xxxl * 2,
  },
  card: {
    marginBottom: spacing.md,
  },
  cardTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: spacing.sm,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    flex: 1,
  },
  earnRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: spacing.md,
    marginBottom: spacing.md,
  },
  metaBox: {
    alignItems: 'flex-end',
  },
  route: {
    borderRadius: radii.md,
    padding: spacing.md,
  },
  routeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  routeIcon: {
    fontSize: 18,
    width: 24,
    textAlign: 'center',
  },
  routeLine: {
    width: 2,
    height: 14,
    marginLeft: 11,
    marginVertical: 2,
  },
  items: {
    marginTop: spacing.sm,
  },
  cardActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  liveRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  liveDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  reasonIcon: {
    fontSize: 36,
    marginBottom: spacing.xs,
  },
  reasonText: {
    marginTop: spacing.xs,
    marginBottom: spacing.md,
  },
  countText: {
    marginBottom: spacing.md,
  },
  gapTop: {
    marginTop: spacing.sm,
  },
});
