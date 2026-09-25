/**
 * รายได้ไรเดอร์ — สรุปตามช่วงเวลา + ประวัติงาน (RIDER-APP-21)
 *
 * - แท็บ วันนี้ / สัปดาห์นี้ / เดือนนี้ / ทั้งหมด → GET /rider/earnings?period=
 * - ประวัติงานแบ่งหน้า → GET /rider/jobs/history (โหลดเพิ่มเมื่อเลื่อนถึงท้าย)
 * - เปลี่ยนแท็บระหว่างโหลด → ทิ้งผลลัพธ์เก่า (requestId)
 * - ถอนเงินทำที่แท็บกระเป๋าเงิน · รายงานละเอียดดูบนเว็บไซต์
 *
 * หน้าตา: การ์ดน้ำเงินลายกนก (ตัวเลือกช่วงเวลาแบบกระจก + ยอดทอง + กราฟแท่งรายวัน)
 *         → ตัวเลขสรุป 4 ช่อง → ประวัติงานเป็นการ์ดขาวใบเดียวแบ่งแถว
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { router, useFocusEffect } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { useTheme, spacing, radii, typography, shadowStyle, withAlpha } from '@/theme';
import {
  BrandArt,
  Button3D,
  Chip,
  EmptyState,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  StatTile,
  WebsiteButton,
  formatBaht,
  selectionHaptic,
  tapHaptic,
  usePressGuard,
  type IconName,
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
import { IconTile, NavyCard, type TileTone } from '@/components/rider/RiderVisuals';

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

const BAR_HEIGHT = 92;

/** ตัวเลือกช่วงเวลาแบบกระจกบนการ์ดน้ำเงิน (ที่เลือก = เม็ดทอง) */
const PeriodSwitch: React.FC<{ value: EarningsPeriod; onChange: (period: EarningsPeriod) => void }> = ({
  value,
  onChange,
}) => {
  const { colors, gradients } = useTheme();
  return (
    <View style={[styles.segment, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
      {PERIODS.map((p) => {
        const selected = value === p.key;
        return (
          <Pressable
            key={p.key}
            onPress={() => {
              selectionHaptic();
              onChange(p.key);
            }}
            accessibilityRole="button"
            accessibilityLabel={p.label}
            accessibilityState={{ selected }}
            hitSlop={4}
            style={({ pressed }) => [styles.segmentItem, { opacity: pressed && !selected ? 0.7 : 1 }]}
          >
            {selected ? (
              <LinearGradient
                colors={gradients.primary}
                start={{ x: 0, y: 0 }}
                end={{ x: 0, y: 1 }}
                style={[styles.segmentPill, shadowStyle('sm', colors.shadowDark)]}
              >
                <Text numberOfLines={1} style={[styles.segmentText, { color: colors.textOnGold }]}>
                  {p.label}
                </Text>
              </LinearGradient>
            ) : (
              <View style={styles.segmentPill}>
                <Text numberOfLines={1} style={[styles.segmentText, { color: colors.onHeaderMuted }]}>
                  {p.label}
                </Text>
              </View>
            )}
          </Pressable>
        );
      })}
    </View>
  );
};

/** กราฟแท่งรายวันบนการ์ดน้ำเงิน (วันที่ได้มากที่สุด = แท่งทองพร้อมยอด) — ไม่ต้องใช้ไลบรารีกราฟ */
const DailyBars: React.FC<{ daily: RiderEarningsResponse['daily'] }> = ({ daily }) => {
  const { colors, gradients } = useTheme();
  const items = daily.slice(-14);
  const max = Math.max(1, ...items.map((d) => num(d.earnings)));
  if (items.length < 2) return null;
  const bestIndex = items.reduce((best, d, i) => (num(d.earnings) > num(items[best].earnings) ? i : best), 0);
  const hasBest = num(items[bestIndex].earnings) > 0;

  return (
    <View style={[styles.chart, { borderTopColor: colors.headerGlassBorder }]}>
      <View style={styles.chartHead}>
        <Text style={[typography.overline, { color: colors.onHeaderMuted }]}>รายได้รายวัน</Text>
        {hasBest && (
          <Text style={[typography.caption, { color: colors.onHeaderMuted }]} numberOfLines={1}>
            สูงสุด {DAY_LABEL(items[bestIndex].date)} ·{' '}
            <Text style={[styles.bestValue, { color: colors.goldLight }]}>{formatBaht(max)}</Text>
          </Text>
        )}
      </View>
      <View style={styles.bars} accessibilityLabel="กราฟรายได้รายวัน">
        {items.map((d, index) => {
          const value = num(d.earnings);
          const height = Math.max(4, Math.round((value / max) * BAR_HEIGHT));
          const best = hasBest && index === bestIndex;
          return (
            <View key={d.date} style={styles.barCol} accessibilityLabel={`${DAY_LABEL(d.date)} ${formatBaht(value)}`}>
              <View style={styles.barTrack}>
                {best ? (
                  <LinearGradient colors={gradients.primary} style={[styles.barFill, { height }]} />
                ) : (
                  <View
                    style={[
                      styles.barFill,
                      { height, backgroundColor: value > 0 ? withAlpha(colors.goldLight, 0.3) : colors.headerGlassBorder },
                    ]}
                  />
                )}
              </View>
              <Text
                style={[typography.micro, styles.barLabel, { color: best ? colors.goldLight : colors.onHeaderMuted }]}
                numberOfLines={1}
              >
                {DAY_LABEL(d.date).split(' ')[0]}
              </Text>
            </View>
          );
        })}
      </View>
    </View>
  );
};

/** แถวประวัติงาน — แถวติดกันเป็นการ์ดขาวใบเดียว (แถวแรกมุมบนโค้ง · แถวสุดท้ายมุมล่างโค้ง + เงา) */
const HistoryRow: React.FC<{ job: RiderJobSummary; first: boolean; last: boolean }> = ({ job, first, last }) => {
  const { colors, isDark } = useTheme();
  const completed = job.status === 'completed' || job.status === 'delivered';
  const { run } = usePressGuard(() => router.push(`/rider-job-detail?id=${job.id}` as never));
  const tile: { icon: IconName; tone: TileTone } = completed
    ? { icon: 'coins', tone: 'success' }
    : job.status === 'failed'
      ? { icon: 'x-circle', tone: 'danger' }
      : job.status === 'cancelled'
        ? { icon: 'prohibit', tone: 'neutral' }
        : { icon: 'moped', tone: 'navy' };

  return (
    <Pressable
      onPress={() => {
        tapHaptic();
        run();
      }}
      accessibilityRole="button"
      accessibilityLabel={`งาน ${job.job_number} ${job.status_text}`}
      style={({ pressed }) => [
        styles.historyItem,
        { backgroundColor: pressed ? colors.surface : colors.card },
        first && styles.historyFirst,
        last && styles.historyLast,
        isDark && {
          borderColor: colors.border,
          borderLeftWidth: 1,
          borderRightWidth: 1,
          borderTopWidth: first ? 1 : 0,
          borderBottomWidth: last ? 1 : 0,
        },
        last && shadowStyle('md', colors.shadowDark),
      ]}
    >
      {!first && <View style={[styles.historyDivider, { backgroundColor: colors.divider }]} />}
      <IconTile icon={tile.icon} tone={tile.tone} />
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
    </Pressable>
  );
};

export default function RiderEarningsScreen() {
  const { colors } = useTheme();

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
          art="scooter"
          title="ยังไม่ได้เป็นไรเดอร์"
          message="สมัครไรเดอร์ก่อน แล้วรายได้จากการส่งงานจะแสดงที่นี่"
          actionLabel="ไปหน้าสมัคร"
          onAction={() => router.replace('/rider' as never)}
        />
      </Screen>
    );
  }

  // ---------- หน้าตาเท่านั้น ----------
  const completedJobs = num(summary?.completed_jobs);
  /** กราฟรายวันแสดงเมื่อไม่ใช่ "วันนี้" และมีข้อมูลอย่างน้อย 2 วัน (เหมือนเดิม) */
  const showChart = !!summary && period !== 'today' && Array.isArray(summary.daily) && summary.daily.length >= 2;
  const listData = historyLoading && jobs.length === 0 ? [] : jobs;

  const header = (
    <View>
      {/* ---------- ยอดรวม ---------- */}
      <NavyCard goldBorder padding={0} style={styles.block}>
        <View style={styles.heroBody}>
          <PeriodSwitch value={period} onChange={setPeriod} />
          <View style={styles.heroMain}>
            <View style={styles.flex}>
              <View style={styles.heroLabelRow}>
                <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>{heading}</Text>
                {summaryLoading && !!summary && <ActivityIndicator size="small" color={colors.goldLight} />}
              </View>
              {summaryLoading && !summary ? (
                <ActivityIndicator color={colors.goldLight} style={styles.heroLoader} />
              ) : summaryError && !summary ? (
                <View style={styles.heroError}>
                  <Text style={[typography.bodySm, { color: colors.onHeader }]}>{summaryError.message}</Text>
                  <Button3D
                    title="ลองใหม่"
                    size="sm"
                    variant="secondary"
                    icon="arrows-clockwise"
                    onPress={() => loadSummary(period)}
                  />
                </View>
              ) : (
                <>
                  <PriceText
                    amount={summary?.gross_earnings ?? 0}
                    size="xl"
                    style={[typography.moneyLg, { color: colors.goldLight }]}
                  />
                  <Text style={[typography.bodySm, { color: colors.onHeader }]}>
                    ส่งสำเร็จ {completedJobs.toLocaleString('th-TH')} งาน
                    {completedJobs > 0 ? ' — เก่งมาก!' : ''}
                  </Text>
                </>
              )}
            </View>
            {!showChart && <BrandArt name="wallet" size={84} style={styles.heroArt} />}
          </View>
        </View>
        {showChart && !!summary && <DailyBars daily={summary.daily} />}
      </NavyCard>

      {!!summary && (
        <View style={styles.grid}>
          <StatTile
            label="ยอดในกระเป๋า"
            icon="wallet"
            tone="info"
            value={<PriceText amount={summary.wallet_balance} size="lg" tone="strong" />}
            onPress={() => router.push('/(tabs)/wallet' as never)}
            style={styles.gridItem}
          />
          <StatTile
            label="รายได้สะสมทั้งหมด"
            icon="trophy"
            tone="gold"
            value={<PriceText amount={summary.total_earnings_all_time} size="lg" tone="strong" />}
            style={styles.gridItem}
          />
          <StatTile
            label="งานเก็บเงินปลายทาง"
            icon="money"
            tone="warning"
            value={num(summary.cod_jobs)}
            caption={`เงินสดที่เก็บ ${formatBaht(summary.cod_collected)}`}
            style={styles.gridItem}
          />
          <StatTile
            label="รอโอนเข้ากระเป๋า"
            icon="hourglass"
            tone={num(summary.unsettled_jobs) > 0 ? 'warning' : 'success'}
            value={num(summary.unsettled_jobs)}
            caption="งาน"
            style={styles.gridItem}
          />
        </View>
      )}

      <View style={styles.links}>
        <Button3D
          title="ถอนเงินที่กระเป๋าเงิน"
          icon="bank"
          variant="navy"
          fullWidth
          onPress={() => router.push('/(tabs)/wallet' as never)}
        />
        <WebsiteButton path="/user/rider/earnings" label="ดูรายงานรายได้แบบละเอียดบนเว็บไซต์" variant="ghost" fullWidth />
      </View>

      <SectionHeader title="ประวัติงาน" icon="clock-counter-clockwise" style={styles.section} />
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
        data={listData}
        extraData={listData.length}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item, index }) => (
          <HistoryRow job={item} first={index === 0} last={index === listData.length - 1} />
        )}
        ListHeaderComponent={header}
        ListEmptyComponent={
          historyLoading ? (
            <ActivityIndicator color={colors.gold} style={styles.listLoader} />
          ) : historyError ? (
            <EmptyState variant="error" message={historyError} onAction={() => loadHistory(filter, 1, 'reset')} compact />
          ) : (
            <EmptyState
              art="scooter"
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
              iconRight="caret-down"
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
    paddingTop: spacing.md,
    paddingBottom: spacing.xxxl * 2,
  },
  block: {
    marginBottom: spacing.lg,
  },
  section: {
    marginTop: spacing.lg,
    marginBottom: spacing.sm,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  // ---------- ฮีโร่ ----------
  heroBody: {
    padding: spacing.lg,
    paddingBottom: spacing.lg + 2,
  },
  heroMain: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.lg,
    minHeight: 84,
  },
  heroLabelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  heroLoader: {
    marginVertical: spacing.lg,
    alignSelf: 'flex-start',
  },
  heroError: {
    gap: spacing.sm,
    alignItems: 'flex-start',
    marginTop: spacing.xs,
  },
  heroArt: {
    marginRight: -spacing.xs,
  },
  segment: {
    flexDirection: 'row',
    borderRadius: radii.pill,
    borderWidth: 1,
    padding: 3,
  },
  segmentItem: {
    flex: 1,
  },
  segmentPill: {
    height: 34,
    borderRadius: radii.pill,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.xs,
  },
  segmentText: {
    fontSize: 13,
    fontWeight: '700',
  },
  // ---------- กราฟรายวัน ----------
  chart: {
    borderTopWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
    paddingBottom: spacing.lg,
  },
  chartHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  bestValue: {
    fontWeight: '700',
  },
  bars: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 4,
  },
  barCol: {
    flex: 1,
    alignItems: 'center',
    gap: 4,
  },
  barTrack: {
    width: '100%',
    height: BAR_HEIGHT,
    justifyContent: 'flex-end',
  },
  barFill: {
    width: '100%',
    borderRadius: 5,
  },
  barLabel: {
    fontVariant: ['tabular-nums'],
  },
  // ---------- ตัวเลขสรุป ----------
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
  links: {
    gap: spacing.sm,
    marginBottom: spacing.sm,
  },
  // ---------- ประวัติงาน ----------
  historyItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md + 2,
  },
  historyFirst: {
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
  },
  historyLast: {
    borderBottomLeftRadius: radii.xl,
    borderBottomRightRadius: radii.xl,
    marginBottom: spacing.md,
  },
  historyDivider: {
    position: 'absolute',
    top: 0,
    left: spacing.lg + 44 + spacing.md,
    right: spacing.lg,
    height: 1,
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
    marginTop: spacing.xs,
  },
});
