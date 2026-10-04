/**
 * งานใกล้ฉัน — รายการงานที่รอไรเดอร์รับ
 *
 * - โหลดใหม่ทุก 15 วินาทีตอนเปิดหน้านี้อยู่ + ทันทีเมื่อมีแจ้งเตือนงานใหม่ (RIDER-APP-11)
 * - ออฟไลน์/มีงานค้าง/ไม่มีตำแหน่ง = การ์ดบอกเหตุผลพร้อมปุ่มแก้ (ไม่ใช่ error — RIDER-APP-15)
 * - ปุ่มรับงานกันกดซ้ำ + ข้อความไทยชัดทุกกรณี (RIDER-APP-16) แล้วพาไปหน้างานทันที
 * - ตัวเลขทุกตัวมาจาก server (ค่าส่ง/รายได้/ระยะทาง) — RIDER-APP-02
 *
 * หน้าตา: การ์ดข้อเสนองานตามม็อกอัป RiderOffer (ไรเดอร์รอบ 2 — components/rider/JobOfferCard)
 *         ภาพเส้นทางจริง · ลูกค้าล็อกเรียก · "คุณได้รับ" = ค่าส่ง + โบนัสร้าน · จุดรับ/ส่งพร้อมระยะตามถนน
 *         สถานะออฟไลน์/มีงานค้าง = การ์ดน้ำเงินลายกนก
 * - งานที่ลูกค้าประจำล็อกเรียกคุณ (locked_by_buyer) ขึ้นก่อนเสมอ — ข้อเสนอนี้มีเวลาสั้นๆ ก่อนเปิดให้คนอื่น
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { FlatList, RefreshControl, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { router, useFocusEffect } from 'expo-router';
import { useTheme, spacing, radii, typography } from '@/theme';
import { BrandArt, Button3D, EmptyState, Screen, SectionHeader } from '@/components/ui';
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
import { formatTime } from '@/components/rider/riderHelpers';
import { LiveDot, NavyCard, NoticeCard } from '@/components/rider/RiderVisuals';
import { JobOfferCard } from '@/components/rider/JobOfferCard';

const POLL_MS = 15_000;

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
        <EmptyState art="scooter" title="กำลังหางานใกล้คุณ..." message="รอสักครู่นะ" />
      </Screen>
    );
  }

  if (!data) {
    const notApproved = error?.code === 'NOT_APPROVED' || error?.code === 'NOT_RIDER';
    return (
      <Screen title="งานใกล้ฉัน" onRefresh={() => load('refresh')} refreshing={refreshing}>
        {notApproved ? (
          <EmptyState
            art="scooter"
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

  // งานที่ลูกค้าล็อกเรียกคุณขึ้นก่อน (คงลำดับเดิมของ server ภายในกลุ่ม)
  const visible = data.jobs.filter((j) => !hidden.has(j.id));
  const jobs = [...visible.filter((j) => j.locked_by_buyer), ...visible.filter((j) => !j.locked_by_buyer)];
  const block = data.block_reason;
  const showConsentBanner = data.reason === null && (block?.code === 'CONSENT_REQUIRED' || !hasConsent);
  const searching = data.reason === null;

  const header = (
    <View>
      {/* แถบสถานะ live */}
      <View style={[styles.liveRow, { backgroundColor: colors.card, borderColor: colors.border }]}>
        <LiveDot live={searching} color={searching ? colors.success : colors.textFaint} />
        <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]} numberOfLines={1}>
          {searching
            ? `อัปเดตอัตโนมัติทุก 15 วินาที${updatedAt ? ` · ล่าสุด ${formatTime(updatedAt.toISOString())}` : ''}`
            : 'ยังไม่ได้ค้นหางาน'}
        </Text>
      </View>

      {data.reason === 'offline' && (
        <NavyCard goldBorder style={styles.block}>
          <View style={styles.heroRow}>
            <View style={styles.flex}>
              <Text style={[typography.h2, { color: colors.onHeader }]}>คุณยังปิดรับงานอยู่</Text>
              <Text style={[typography.bodySm, styles.heroText, { color: colors.onHeaderMuted }]}>
                กดเริ่มรับงานเพื่อดูงานใกล้คุณ และรับแจ้งเตือนงานใหม่ทันที
              </Text>
            </View>
            <BrandArt name="scooter" size={92} style={styles.heroArt} />
          </View>
          <Button3D title="เริ่มรับงาน" icon="power" size="lg" fullWidth onPress={handleGoOnline} />
        </NavyCard>
      )}

      {data.reason === 'busy' && (
        <NavyCard goldBorder style={styles.block}>
          <View style={styles.heroRow}>
            <View style={styles.flex}>
              <Text style={[typography.h2, { color: colors.onHeader }]}>คุณมีงานที่กำลังส่งอยู่</Text>
              <Text style={[typography.bodySm, styles.heroText, { color: colors.onHeaderMuted }]}>
                ส่งงานนี้ให้เสร็จก่อน แล้วค่อยรับงานถัดไปนะ
              </Text>
            </View>
            <BrandArt name="scooter" size={92} style={styles.heroArt} />
          </View>
          <Button3D
            title="ไปที่งานปัจจุบัน"
            icon="navigation-arrow"
            size="lg"
            fullWidth
            onPress={() =>
              router.push(
                (data.active_job_id ? `/rider-job-detail?id=${data.active_job_id}` : '/rider-job-detail') as never
              )
            }
          />
        </NavyCard>
      )}

      {data.reason === 'no_location' && (
        <NoticeCard
          icon="map-pin"
          tone="gold"
          title="ยังไม่รู้ตำแหน่งของคุณ"
          message="เปิด GPS และอนุญาตตำแหน่ง เพื่อหางานที่ใกล้คุณที่สุด"
          style={styles.block}
        >
          <Button3D
            title="เปิดตำแหน่ง"
            icon="crosshair"
            fullWidth
            onPress={async () => {
              if (await flow.ensureForeground()) {
                await pingRiderLocation({ force: true });
                await load('refresh');
              }
            }}
          />
        </NoticeCard>
      )}

      {showConsentBanner && (
        <NoticeCard
          icon="handshake"
          tone="gold"
          title="ยอมรับการแชร์ตำแหน่งก่อนรับงานแรก"
          message="ลูกค้าเห็นตำแหน่งคุณเฉพาะออเดอร์ที่กำลังส่ง และหยุดเองเมื่อจบงาน"
          style={styles.block}
        >
          <Button3D
            title="ยอมรับ"
            size="sm"
            variant="navy"
            icon="check"
            onPress={() => flow.requestConsent()}
            style={styles.alignStart}
          />
        </NoticeCard>
      )}

      {data.reason === null && block && block.code !== 'CONSENT_REQUIRED' && (
        <NoticeCard icon="warning" tone="warning" message={block.message} style={styles.block}>
          {block.code === 'LOCATION_STALE' ? (
            <Button3D
              title="ส่งตำแหน่งตอนนี้"
              size="sm"
              icon="crosshair"
              onPress={async () => {
                if (await flow.ensureForeground()) {
                  await pingRiderLocation({ force: true });
                  await load('refresh');
                }
              }}
              style={styles.alignStart}
            />
          ) : null}
        </NoticeCard>
      )}

      {data.reason === null && jobs.length > 0 && (
        <SectionHeader title={`มี ${jobs.length} งานรอคุณอยู่`} icon="fire" style={styles.countHeader} />
      )}
    </View>
  );

  return (
    <Screen
      title="งานใกล้ฉัน"
      subtitle="เลือกงานที่ใช่ แล้วกดรับได้เลย"
      scroll={false}
      right={
        <Button3D
          title="รายได้"
          icon="chart-bar"
          size="sm"
          variant="secondary"
          onPress={() => router.push('/rider-earnings' as never)}
        />
      }
    >
      <FlatList
        data={data.reason === null ? jobs : []}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => (
          <JobOfferCard
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
              art="scooter"
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
    paddingTop: spacing.md,
    paddingBottom: spacing.xxxl * 2,
  },
  block: {
    marginBottom: spacing.lg,
  },
  liveRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    alignSelf: 'flex-start',
    maxWidth: '100%',
    paddingLeft: spacing.xs,
    paddingRight: spacing.md,
    paddingVertical: 3,
    borderRadius: radii.pill,
    borderWidth: 1,
    marginBottom: spacing.lg,
  },
  heroRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.lg,
  },
  heroText: {
    marginTop: spacing.xs,
  },
  heroArt: {
    marginRight: -spacing.xs,
  },
  alignStart: {
    alignSelf: 'flex-start',
  },
  countHeader: {
    marginTop: spacing.xs,
  },
});
