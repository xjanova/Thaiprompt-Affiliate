/**
 * รายได้ไรเดอร์ — สรุปตามช่วงเวลา + ประวัติงาน (RIDER-APP-21)
 *
 * - แท็บ วันนี้ / สัปดาห์นี้ / เดือนนี้ / ทั้งหมด → GET /rider/earnings?period=
 * - ประวัติงานแบ่งหน้า → GET /rider/jobs/history (โหลดเพิ่มเมื่อเลื่อนถึงท้าย)
 * - เปลี่ยนแท็บระหว่างโหลด → ทิ้งผลลัพธ์เก่า (requestId)
 * - ถอนเงินทำที่แท็บกระเป๋าเงิน · รายงานละเอียดดูบนเว็บไซต์
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { router, useFocusEffect } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { useTheme, spacing, radii, typography } from '@/theme';
import {
  Button3D,
  Card3D,
  Chip,
  EmptyState,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  StatTile,
  WebsiteButton,
  formatBaht,
} from '@/components/ui';
import { num } from '@/services/api/client';
import {
  getJobHistory,
  getRiderEarnings,
  type EarningsPeriod,
  type RiderEarningsResponse,
  type RiderJobSummary,
} from '@/services/api/riderApi';
import { JOB_STATUS_LABEL, JOB_STATUS_TONE, formatThaiDateTime } from '@/components/rider/riderHelpers';

const PERIODS: Array<{ key: EarningsPeriod; label: string; heading: string }> = [
  { key: 'today', label: 'วันนี้', heading: 'รายได้วันนี้' },
  { key: 'week', label: 'สัปดาห์นี้', heading: 'รายได้สัปดาห์นี้' },
  { key: 'month', label: 'เดือนนี้', heading: 'รายได้เดือนนี้' },
  { key: 'all', label: 'ทั้งหมด', heading: 'รายได้ทั้งหมด' },
];

type HistoryFilter = 'all' | 'completed' | 'failed' | 'cancelled';

const HISTORY_FILTERS: Array<{ key: HistoryFilter; label: string }> = [
  { key: 'all', label: 'ทั้งหมด' },
  { key: 'completed', label: 'สำเร็จ' },
  { key: 'failed', label: 'ไม่สำเร็จ' },
  { key: 'cancelled', label: 'ยกเลิก' },
];

const DAY_LABEL = (iso: string): string => {
  const date = new Date(`${iso}T00:00:00`);
  if (Number.isNaN(date.getTime())) return iso.slice(5);
  try {
    return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' });
  } catch {
    return iso.slice(5);
  }
};

/** กราฟแท่งรายวันแบบเบาๆ (ไม่ต้องใช้ไลบรารีกราฟ) */
const DailyBars: React.FC<{ daily: RiderEarningsResponse['daily'] }> = ({ daily }) => {
  const { colors, gradients } = useTheme();
  const items = daily.slice(-14);
  const max = Math.max(1, ...items.map((d) => num(d.earnings)));
  if (items.length < 2) return null;

  return (
    <Card3D style={styles.block} padding={spacing.lg}>
      <Text style={[typography.h3, styles.cardTitle, { color: colors.textStrong }]}>รายได้รายวัน</Text>
      <View style={styles.bars} accessibilityLabel="กราฟรายได้รายวัน">
        {items.map((d) => {
          const value = num(d.earnings);
          const height = Math.max(4, Math.round((value / max) * 96));
          return (
            <View key={d.date} style={styles.barCol} accessibilityLabel={`${DAY_LABEL(d.date)} ${formatBaht(value)}`}>
              <View style={[styles.barTrack, { backgroundColor: colors.inset }]}>
                <LinearGradient
                  colors={value > 0 ? gradients.primary : [colors.border, colors.border]}
                  style={[styles.barFill, { height }]}
                />
              </View>
              <Text style={[typography.micro, { color: colors.textFaint }]} numberOfLines={1}>
                {DAY_LABEL(d.date).split(' ')[0]}
              </Text>
            </View>
          );
        })}
      </View>
    </Card3D>
  );
};

const HistoryRow: React.FC<{ job: RiderJobSummary }> = ({ job }) => {
  const { colors } = useTheme();
  const completed = job.status === 'completed' || job.status === 'delivered';
  return (
    <Card3D
      onPress={() => router.push(`/rider-job-detail?id=${job.id}` as never)}
      style={styles.historyCard}
      padding={spacing.md}
      radius={radii.lg}
      shadow="sm"
      accessibilityLabel={`งาน ${job.job_number} ${job.status_text}`}
    >
      <View style={styles.historyRow}>
        <View style={styles.flex}>
          <Text style={[typography.bodyStrong, { color: colors.textStrong }]} numberOfLines={1}>
            {job.title || job.job_type_text}
          </Text>
          <Text style={[typography.caption, { color: colors.textMuted }]} numberOfLines={1}>
            {job.job_number} · {formatThaiDateTime(job.completed_at || job.accepted_at || job.created_at)}
          </Text>
        </View>
        <View style={styles.historyRight}>
          <PriceText
            amount={job.rider_earnings}
            size="md"
            tone={completed ? 'success' : 'muted'}
            strike={!completed}
            signed={completed}
          />
          <Pill label={job.status_text || JOB_STATUS_LABEL[job.status]} tone={JOB_STATUS_TONE[job.status] || 'neutral'} />
        </View>
      </View>
    </Card3D>
  );
};

export default function RiderEarningsScreen() {
  const { colors, gradients } = useTheme();

  const [period, setPeriod] = useState<EarningsPeriod>('today');
  const [summary, setSummary] = useState<RiderEarningsResponse | null>(null);
  const [summaryLoading, setSummaryLoading] = useState(true);
  const [summaryError, setSummaryError] = useState<{ code: string; message: string } | null>(null);

  const [filter, setFilter] = useState<HistoryFilter>('all');
  const [jobs, setJobs] = useState<RiderJobSummary[]>([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [historyLoading, setHistoryLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [historyError, setHistoryError] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  const mountedRef = useRef(true);
  const summaryReqRef = useRef(0);
  const historyReqRef = useRef(0);
  const loadedOnceRef = useRef(false);
  /** ช่วงเวลา/ตัวกรองที่เลือกอยู่ — ตัวรีเฟรชตอนกลับเข้าหน้าต้องใช้ค่าล่าสุด (ไม่ใช่ค่าตอน mount) */
  const periodRef = useRef<EarningsPeriod>(period);
  periodRef.current = period;
  const filterRef = useRef<HistoryFilter>(filter);
  filterRef.current = filter;

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const loadSummary = useCallback(async (target: EarningsPeriod, silent = false) => {
    const requestId = ++summaryReqRef.current;
    if (!silent) setSummaryLoading(true);
    const result = await getRiderEarnings(target);
    if (!mountedRef.current || requestId !== summaryReqRef.current) return;
    if (result.success) {
      setSummary(result.data);
      setSummaryError(null);
    } else if (!silent) {
      setSummaryError({ code: result.code, message: result.message });
    }
    setSummaryLoading(false);
  }, []);

  const loadHistory = useCallback(async (target: HistoryFilter, targetPage: number, mode: 'reset' | 'more' | 'silent') => {
    const requestId = ++historyReqRef.current;
    if (mode === 'reset') setHistoryLoading(true);
    if (mode === 'more') setLoadingMore(true);
    const result = await getJobHistory({
      page: targetPage,
      per_page: 20,
      status: target === 'all' ? undefined : target,
    });
    if (!mountedRef.current || requestId !== historyReqRef.current) return;
    if (result.success) {
      const items = Array.isArray(result.data?.jobs) ? result.data.jobs : [];
      setJobs((prev) => (mode === 'more' ? [...prev, ...items.filter((i) => !prev.some((p) => p.id === i.id))] : items));
      setPage(targetPage);
      const p = result.data?.pagination;
      setHasMore(!!p && (p.has_more ?? p.current_page < p.last_page));
      setHistoryError(null);
    } else if (mode !== 'silent') {
      setHistoryError(result.message);
    }
    setHistoryLoading(false);
    setLoadingMore(false);
  }, []);

  useEffect(() => {
    loadSummary(period);
  }, [period, loadSummary]);

  useEffect(() => {
    loadHistory(filter, 1, 'reset');
  }, [filter, loadHistory]);

  // กลับมาหน้านี้ (เช่น หลังส่งงานเสร็จ) → รีเฟรชเงียบๆ ตามช่วงเวลา/ตัวกรองที่เลือกอยู่
  useFocusEffect(
    useCallback(() => {
      if (loadedOnceRef.current) {
        loadSummary(periodRef.current, true);
        loadHistory(filterRef.current, 1, 'silent');
      }
      loadedOnceRef.current = true;
    }, [loadHistory, loadSummary])
  );

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await Promise.all([loadSummary(period, true), loadHistory(filter, 1, 'silent')]);
    if (mountedRef.current) setRefreshing(false);
  }, [filter, loadHistory, loadSummary, period]);

  const onEndReached = useCallback(() => {
    if (hasMore && !loadingMore && !historyLoading) {
      loadHistory(filter, page + 1, 'more');
    }
  }, [filter, hasMore, historyLoading, loadHistory, loadingMore, page]);

  const heading = useMemo(() => PERIODS.find((p) => p.key === period)?.heading || 'รายได้', [period]);

  if (summaryError?.code === 'NOT_RIDER' && !summary) {
    return (
      <Screen title="รายได้ไรเดอร์">
        <EmptyState
          icon="🛵"
          title="ยังไม่ได้เป็นไรเดอร์"
          message="สมัครไรเดอร์ก่อน แล้วรายได้จากการส่งงานจะแสดงที่นี่"
          actionLabel="ไปหน้าสมัคร"
          onAction={() => router.replace('/rider' as never)}
        />
      </Screen>
    );
  }

  const header = (
    <View>
      <View style={styles.chips}>
        {PERIODS.map((p) => (
          <Chip key={p.key} label={p.label} selected={period === p.key} tone="gold" onPress={() => setPeriod(p.key)} />
        ))}
      </View>

      {/* ---------- ยอดรวม ---------- */}
      <Card3D gradientBorder padding={0} style={styles.block}>
        <LinearGradient colors={gradients.hero} style={styles.hero}>
          <Text style={[typography.caption, { color: colors.textMuted }]}>{heading}</Text>
          {summaryLoading && !summary ? (
            <ActivityIndicator color={colors.gold} style={styles.heroLoader} />
          ) : summaryError && !summary ? (
            <View style={styles.heroError}>
              <Text style={[typography.bodySm, { color: colors.danger }]}>{summaryError.message}</Text>
              <Button3D title="ลองใหม่" size="sm" variant="secondary" onPress={() => loadSummary(period)} />
            </View>
          ) : (
            <>
              <PriceText amount={summary?.gross_earnings ?? 0} size="xl" tone="gold" />
              <Text style={[typography.bodySm, { color: colors.text }]}>
                ส่งสำเร็จ {num(summary?.completed_jobs).toLocaleString('th-TH')} งาน
                {num(summary?.completed_jobs) > 0 ? ' — เก่งมาก! 💪' : ''}
              </Text>
            </>
          )}
          {summaryLoading && !!summary && <ActivityIndicator size="small" color={colors.gold} style={styles.inlineLoader} />}
        </LinearGradient>
      </Card3D>

      {!!summary && (
        <View style={styles.grid}>
          <StatTile
            label="ยอดในกระเป๋า"
            icon="👛"
            tone="info"
            value={<PriceText amount={summary.wallet_balance} size="lg" />}
            onPress={() => router.push('/(tabs)/wallet' as never)}
            style={styles.gridItem}
          />
          <StatTile
            label="รายได้สะสมทั้งหมด"
            icon="🏆"
            tone="gold"
            value={<PriceText amount={summary.total_earnings_all_time} size="lg" />}
            style={styles.gridItem}
          />
          <StatTile
            label="งานเก็บเงินปลายทาง"
            icon="💵"
            tone="warning"
            value={num(summary.cod_jobs)}
            caption={`เงินสดที่เก็บ ${formatBaht(summary.cod_collected)}`}
            style={styles.gridItem}
          />
          <StatTile
            label="รอโอนเข้ากระเป๋า"
            icon="⏳"
            tone={num(summary.unsettled_jobs) > 0 ? 'warning' : 'success'}
            value={num(summary.unsettled_jobs)}
            caption="งาน"
            style={styles.gridItem}
          />
        </View>
      )}

      {!!summary && period !== 'today' && Array.isArray(summary.daily) && <DailyBars daily={summary.daily} />}

      <View style={styles.links}>
        <Button3D
          title="ถอนเงินที่กระเป๋าเงิน"
          icon="🏦"
          variant="secondary"
          fullWidth
          onPress={() => router.push('/(tabs)/wallet' as never)}
        />
        <WebsiteButton path="/user/rider/earnings" label="ดูรายงานรายได้แบบละเอียดบนเว็บไซต์" variant="ghost" fullWidth />
      </View>

      <SectionHeader title="ประวัติงาน" icon="🗂️" style={styles.section} />
      <View style={styles.chips}>
        {HISTORY_FILTERS.map((f) => (
          <Chip key={f.key} label={f.label} size="sm" selected={filter === f.key} onPress={() => setFilter(f.key)} />
        ))}
      </View>
    </View>
  );

  return (
    <Screen title="รายได้ไรเดอร์" subtitle="ทุกงานที่ส่งสำเร็จคือรายได้ของคุณ" scroll={false}>
      <FlatList
        data={historyLoading && jobs.length === 0 ? [] : jobs}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => <HistoryRow job={item} />}
        ListHeaderComponent={header}
        ListEmptyComponent={
          historyLoading ? (
            <ActivityIndicator color={colors.gold} style={styles.listLoader} />
          ) : historyError ? (
            <EmptyState variant="error" message={historyError} onAction={() => loadHistory(filter, 1, 'reset')} compact />
          ) : (
            <EmptyState
              icon="🛵"
              title="ยังไม่มีประวัติงาน"
              message="รับงานแรกแล้วรายได้จะมาแสดงที่นี่"
              actionLabel="ดูงานใกล้ฉัน"
              onAction={() => router.push('/rider-jobs' as never)}
              compact
            />
          )
        }
        ListFooterComponent={
          loadingMore ? (
            <ActivityIndicator color={colors.gold} style={styles.listLoader} />
          ) : hasMore ? (
            <Button3D
              title="ดูเพิ่ม"
              variant="ghost"
              size="sm"
              onPress={() => loadHistory(filter, page + 1, 'more')}
              style={styles.more}
            />
          ) : null
        }
        onEndReached={onEndReached}
        onEndReachedThreshold={0.4}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
          />
        }
      />
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
  block: {
    marginBottom: spacing.md,
  },
  section: {
    marginTop: spacing.md,
    marginBottom: spacing.sm,
  },
  cardTitle: {
    marginBottom: spacing.md,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  hero: {
    padding: spacing.xl,
    gap: spacing.xs,
    borderRadius: radii.xl,
    minHeight: 120,
  },
  heroLoader: {
    marginVertical: spacing.lg,
    alignSelf: 'flex-start',
  },
  inlineLoader: {
    position: 'absolute',
    top: spacing.lg,
    right: spacing.lg,
  },
  heroError: {
    gap: spacing.sm,
    alignItems: 'flex-start',
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    marginBottom: spacing.md,
  },
  gridItem: {
    flexBasis: '47%',
    flexGrow: 1,
  },
  bars: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 4,
    height: 120,
  },
  barCol: {
    flex: 1,
    alignItems: 'center',
    gap: 4,
  },
  barTrack: {
    width: '100%',
    height: 96,
    borderRadius: 6,
    justifyContent: 'flex-end',
    overflow: 'hidden',
  },
  barFill: {
    width: '100%',
    borderRadius: 6,
  },
  links: {
    gap: spacing.sm,
    marginBottom: spacing.sm,
  },
  historyCard: {
    marginBottom: spacing.sm,
  },
  historyRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  historyRight: {
    alignItems: 'flex-end',
    gap: spacing.xs,
  },
  listLoader: {
    marginVertical: spacing.xl,
  },
  more: {
    alignSelf: 'center',
    marginTop: spacing.sm,
  },
});
