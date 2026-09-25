/**
 * ประวัติธุรกรรมกระเป๋าเงิน — ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * - GET /wallet/transactions?page&type&per_page (กรองได้แค่ รายรับ/รายจ่าย — server ยังไม่รับช่วงวันที่
 *   จึงไม่มีตัวกรองวันที่ที่กดแล้วไม่มีผล)
 * - เปลี่ยนแท็บระหว่างโหลด → ทิ้งผลเก่า (requestId) · เลื่อนโหลดเพิ่ม · ดึงลงรีเฟรช
 * - แตะรายการ = รายละเอียด · ชื่อรายการผ่าน storePolicy (ไม่มีคำเครือข่าย/คอมมิชชั่นตามนโยบาย Google Play)
 * - สรุปรายรับ/รายจ่ายแสดงเฉพาะเมื่อ server ส่งยอดสรุปมา (ไม่แสดง ฿0 หลอกๆ)
 * - หน้าตา: จัดกลุ่มตามวัน (วันนี้ / เมื่อวาน / วันที่) · แต่ละวันเป็นการ์ดขาวใบเดียว แถวคั่นเส้นบาง
 *   ไอคอนในกล่องสีอ่อนตามประเภท · เงินเข้า = +เขียว · เงินออก = -สีเข้ม · ยังไม่สำเร็จ = ป้ายสถานะ
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { router } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { getWalletTransactions } from '@/services/api';
import { hasRestrictedText, walletReferenceLabel, walletTransactionTitle } from '@/utils/storePolicy';
import { Card3D, Chip, EmptyState, Pill, PriceText, Screen, StatTile, type IconName } from '@/components/ui';
import { FormSheet } from '@/components/shop';
import { IconTile, type TileTone } from '@/components/wallet/WalletKit';
import { useTheme, radii, shadowStyle, spacing, typography, type Tone } from '@/theme';

interface Transaction {
  id: number;
  type: 'in' | 'out';
  amount: number;
  title: string;
  description?: string;
  status: string;
  date: string;
  dateRelative: string;
  referenceType?: string;
}

type FilterType = 'all' | 'in' | 'out';

const PER_PAGE = 20;

const FILTERS: Array<{ key: FilterType; label: string; icon: IconName }> = [
  { key: 'all', label: 'ทั้งหมด', icon: 'list' },
  { key: 'in', label: 'รายรับ', icon: 'arrow-down-left' },
  { key: 'out', label: 'รายจ่าย', icon: 'arrow-up-right' },
];

const STATUS: Record<string, { label: string; tone: Tone }> = {
  completed: { label: 'สำเร็จ', tone: 'success' },
  pending: { label: 'รอดำเนินการ', tone: 'warning' },
  processing: { label: 'กำลังดำเนินการ', tone: 'info' },
  failed: { label: 'ไม่สำเร็จ', tone: 'danger' },
  cancelled: { label: 'ยกเลิก', tone: 'neutral' },
};

/** ไอคอน + โทนกล่องของแต่ละประเภทรายการ (ตาม referenceType จาก server) */
const visualFor = (type: 'in' | 'out', referenceType?: string): { icon: IconName; tone: TileTone } => {
  const ref = (referenceType || '').toLowerCase();
  if (ref.includes('withdraw')) return { icon: 'bank', tone: 'navy' };
  if (ref.includes('topup') || ref.includes('deposit')) return { icon: 'arrow-down-left', tone: 'success' };
  if (ref.includes('refund')) return { icon: 'arrow-counter-clockwise', tone: 'success' };
  if (ref.includes('transfer')) return { icon: 'paper-plane-tilt', tone: 'navy' };
  if (ref.includes('rider') || ref.includes('delivery')) return { icon: 'moped', tone: type === 'in' ? 'success' : 'gold' };
  if (ref.includes('fresh')) return { icon: 'basket', tone: 'gold' };
  if (ref.includes('order') || ref.includes('purchase') || ref.includes('payment')) return { icon: 'shopping-bag-open', tone: 'gold' };
  return type === 'in' ? { icon: 'arrow-down-left', tone: 'success' } : { icon: 'arrow-up-right', tone: 'navy' };
};

// =====================================================
// จัดกลุ่มตามวัน — server ส่ง date เป็น "26 Sep 2026 19:12"
// =====================================================

const EN_MONTHS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
const TH_MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

interface TxDay {
  key: string;
  y: number;
  m: number;
  d: number;
  time: string;
}

/** แยกวัน/เวลาจากข้อความวันที่ (อ่านไม่ออก = null → ไม่จัดกลุ่ม แสดงวันที่เดิม) */
const parseTxDate = (raw: string): TxDay | null => {
  const match = /^(\d{1,2})\s+([A-Za-z]{3})[A-Za-z]*\.?\s+(\d{4})(?:\s+(\d{1,2}:\d{2}))?/.exec((raw || '').trim());
  if (!match) return null;
  const m = EN_MONTHS.indexOf(match[2].toLowerCase());
  if (m < 0) return null;
  const d = Number(match[1]);
  const y = Number(match[3]);
  return { key: `${y}-${m}-${d}`, y, m, d, time: match[4] || '' };
};

/** ป้ายหัวกลุ่ม: วันนี้ / เมื่อวาน / 26 ก.ย. 2569 */
const dayLabel = (day: TxDay): string => {
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
  const diff = Math.round((today - new Date(day.y, day.m, day.d).getTime()) / 86400000);
  if (diff === 0) return 'วันนี้';
  if (diff === 1) return 'เมื่อวาน';
  return `${day.d} ${TH_MONTHS[day.m]} ${day.y + 543}`;
};

interface RowMeta {
  /** แถวแรกของวัน (มุมบนโค้ง + หัวกลุ่ม) */
  first: boolean;
  /** แถวสุดท้ายของวัน (มุมล่างโค้ง) */
  last: boolean;
  /** ป้ายวันเหนือกลุ่ม (null = ไม่มีป้าย) */
  header: string | null;
  /** เวลา HH:mm (ว่าง = อ่านไม่ออก) */
  time: string;
}

const toTransaction = (tx: any): Transaction => {
  const isIncome = tx?.type === 'in';
  const description = typeof tx?.description === 'string' ? tx.description : undefined;
  return {
    id: Number(tx?.id) || 0,
    type: isIncome ? 'in' : 'out',
    amount: Math.abs(Number(tx?.amount) || 0),
    // นโยบาย Google Play: รายการจากระบบเครือข่ายแสดงเป็นคำกลางๆ
    title: walletTransactionTitle(tx?.title, tx?.referenceType, isIncome),
    description: description && !hasRestrictedText(description) ? description : undefined,
    status: typeof tx?.status === 'string' && tx.status ? tx.status : 'completed',
    date: typeof tx?.date === 'string' ? tx.date : '',
    dateRelative: typeof tx?.dateRelative === 'string' ? tx.dateRelative : '',
    referenceType: typeof tx?.referenceType === 'string' ? tx.referenceType : undefined,
  };
};

/** แถวรายการ = หนึ่งช่วงของการ์ดวันนั้น (แถวแรกโค้งบน แถวท้ายโค้งล่าง) */
const TransactionRow: React.FC<{ tx: Transaction; meta: RowMeta; onPress: () => void }> = ({ tx, meta, onPress }) => {
  const { colors, isDark } = useTheme();
  const income = tx.type === 'in';
  const st = STATUS[tx.status] || STATUS.pending;
  const visual = visualFor(tx.type, tx.referenceType);
  const subtitle = [tx.description, meta.time || tx.dateRelative || tx.date].filter(Boolean).join(' · ');

  return (
    <View>
      {meta.header !== null ? (
        <Text style={[typography.caption, styles.dayLabel, { color: colors.textMuted }]}>{meta.header}</Text>
      ) : meta.first ? (
        // อ่านวันที่ไม่ออก → ไม่มีป้ายวัน แต่ยังเว้นระยะก่อนการ์ด
        <View style={styles.groupGap} />
      ) : null}
      <Pressable
        onPress={onPress}
        accessibilityRole="button"
        accessibilityLabel={`${tx.title} ${income ? 'รับ' : 'จ่าย'} ${tx.amount} บาท ${st.label} ${tx.dateRelative || tx.date}`}
        style={({ pressed }) => [
          styles.segment,
          { backgroundColor: pressed ? colors.surface : colors.card },
          meta.first && styles.segmentFirst,
          meta.last && styles.segmentLast,
          // โหมดมืด: การ์ดกระจก = ขอบบางรอบกลุ่ม
          isDark && {
            borderColor: colors.border,
            borderLeftWidth: 1,
            borderRightWidth: 1,
            borderTopWidth: meta.first ? 1 : 0,
            borderBottomWidth: meta.last ? 1 : 0,
          },
          shadowStyle('sm', colors.shadowDark),
        ]}
      >
        {!meta.first && <View style={[styles.segmentDivider, { backgroundColor: colors.divider }]} />}
        <IconTile icon={visual.icon} tone={visual.tone} />
        <View style={styles.flex}>
          <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
            {tx.title}
          </Text>
          <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
            {subtitle}
          </Text>
        </View>
        <View style={styles.right}>
          <PriceText amount={income ? tx.amount : -tx.amount} signed size="md" tone={income ? 'success' : 'strong'} />
          {tx.status !== 'completed' && <Pill label={st.label} tone={st.tone} />}
        </View>
      </Pressable>
    </View>
  );
};

export default function WalletHistoryScreen() {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [filter, setFilter] = useState<FilterType>('all');
  const [items, setItems] = useState<Transaction[]>([]);
  const [hasMore, setHasMore] = useState(false);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [summary, setSummary] = useState<{ income: number; expense: number } | null>(null);
  const [selected, setSelected] = useState<Transaction | null>(null);

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);
  const pageRef = useRef(1);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'more', target: FilterType, page: number) => {
      if (!isAuthenticated) {
        setLoading(false);
        return;
      }
      const requestId = ++requestIdRef.current;
      if (mode === 'initial') setLoading(true);
      if (mode === 'refresh') setRefreshing(true);
      if (mode === 'more') setLoadingMore(true);

      const response = await getWalletTransactions(page, target, PER_PAGE);
      if (!mountedRef.current || requestId !== requestIdRef.current) return;

      if (response?.success && response.data) {
        const data = response.data as typeof response.data & {
          hasMore?: boolean;
          summary?: { totalIncome?: number; totalExpense?: number };
        };
        const list = (Array.isArray(data.items) ? data.items : []).map(toTransaction);
        setItems((prev) => {
          if (mode !== 'more') return list;
          const seen = new Set(prev.map((t) => t.id));
          return [...prev, ...list.filter((t) => !seen.has(t.id))];
        });
        pageRef.current = page;
        const p = data.pagination;
        setHasMore(data.hasMore ?? (p ? Number(p.currentPage) < Number(p.lastPage) : list.length >= PER_PAGE));
        if (page === 1) {
          setSummary(
            data.summary && (data.summary.totalIncome !== undefined || data.summary.totalExpense !== undefined)
              ? { income: Number(data.summary.totalIncome) || 0, expense: Number(data.summary.totalExpense) || 0 }
              : null
          );
        }
        setError(null);
      } else if (mode !== 'more') {
        setError('โหลดประวัติไม่สำเร็จ ตรวจสอบอินเทอร์เน็ตแล้วลองใหม่นะ');
      }
      setLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    },
    [isAuthenticated]
  );

  useEffect(() => {
    setItems([]);
    pageRef.current = 1;
    load('initial', filter, 1);
  }, [filter, load]);

  // ข้อมูลจัดกลุ่มตามวันของแต่ละแถว (คำนวณใหม่เมื่อรายการเปลี่ยน)
  const rowMeta = useMemo<RowMeta[]>(() => {
    const days = items.map((t) => parseTxDate(t.date));
    const keyOf = (i: number) => days[i]?.key ?? '?';
    return items.map((_, i) => {
      const first = i === 0 || keyOf(i - 1) !== keyOf(i);
      const last = i === items.length - 1 || keyOf(i + 1) !== keyOf(i);
      const day = days[i];
      return { first, last, header: first && day ? dayLabel(day) : null, time: day?.time ?? '' };
    });
  }, [items]);

  if (!isAuthenticated) {
    return (
      <Screen title="ประวัติธุรกรรม" scroll={false}>
        <EmptyState
          art="wallet"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อดูรายการเงินเข้าออกของกระเป๋า"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </Screen>
    );
  }

  const header = (
    <View>
      {!!summary && (
        <View style={styles.summary}>
          <StatTile
            label="รายรับ"
            icon="arrow-down-left"
            tone="success"
            value={<PriceText amount={summary.income} size="lg" tone="success" />}
            style={styles.flex}
          />
          <StatTile
            label="รายจ่าย"
            icon="arrow-up-right"
            tone="danger"
            value={<PriceText amount={summary.expense} size="lg" tone="danger" />}
            style={styles.flex}
          />
        </View>
      )}
      <View style={styles.filters}>
        {FILTERS.map((f) => (
          <Chip key={f.key} label={f.label} icon={f.icon} size="sm" selected={filter === f.key} onPress={() => setFilter(f.key)} />
        ))}
      </View>
    </View>
  );

  const st = selected ? STATUS[selected.status] || STATUS.pending : null;
  const selectedVisual = selected ? visualFor(selected.type, selected.referenceType) : null;

  return (
    <Screen title="ประวัติธุรกรรม" subtitle="เงินเข้า-ออกของกระเป๋า" scroll={false}>
      <FlatList
        data={items}
        extraData={rowMeta}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item, index }) => (
          <TransactionRow
            tx={item}
            meta={rowMeta[index] || { first: true, last: true, header: null, time: '' }}
            onPress={() => setSelected(item)}
          />
        )}
        ListHeaderComponent={header}
        ListEmptyComponent={
          loading ? (
            <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
          ) : error ? (
            <EmptyState compact variant="error" message={error} onAction={() => load('initial', filter, 1)} />
          ) : (
            <EmptyState
              compact
              art="wallet"
              title={filter === 'all' ? 'ยังไม่มีรายการ' : filter === 'in' ? 'ยังไม่มีรายรับ' : 'ยังไม่มีรายจ่าย'}
              message="เติมเงิน ซื้อของ หรือรับค่าส่ง รายการจะขึ้นที่นี่"
            />
          )
        }
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        onEndReachedThreshold={0.3}
        onEndReached={() => {
          if (hasMore && !loadingMore && !loading) load('more', filter, pageRef.current + 1);
        }}
        ListFooterComponent={
          loadingMore ? (
            <View style={styles.more}>
              <ActivityIndicator color={colors.gold} />
              <Text style={[typography.caption, { color: colors.textMuted }]}>กำลังโหลดเพิ่ม...</Text>
            </View>
          ) : null
        }
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => load('refresh', filter, 1)}
            tintColor={colors.gold}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
          />
        }
      />

      <FormSheet
        visible={!!selected}
        title={selected?.title || 'รายละเอียด'}
        cancelLabel="ปิด"
        onClose={() => setSelected(null)}
      >
        {!!selected && !!st && (
          <View>
            <View style={styles.detailAmount}>
              {!!selectedVisual && <IconTile icon={selectedVisual.icon} tone={selectedVisual.tone} size={56} />}
              <PriceText
                amount={selected.type === 'in' ? selected.amount : -selected.amount}
                signed
                size="xl"
                tone={selected.type === 'in' ? 'success' : 'strong'}
              />
              <Pill label={st.label} tone={st.tone} size="md" />
            </View>
            <Card3D variant="inset" padding={spacing.md}>
              {[
                { label: 'ประเภท', value: walletReferenceLabel(selected.referenceType, selected.type === 'in') },
                ...(selected.description ? [{ label: 'รายละเอียด', value: selected.description }] : []),
                { label: 'วันที่', value: selected.date || selected.dateRelative || '-' },
                { label: 'เลขอ้างอิง', value: `#${selected.id}` },
              ].map((row, index) => (
                <View
                  key={row.label}
                  style={[
                    styles.detailRow,
                    index > 0 && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.divider },
                  ]}
                >
                  <Text style={[typography.bodySm, { color: colors.textMuted }]}>{row.label}</Text>
                  <Text style={[typography.bodyStrong, styles.detailValue, { color: colors.textStrong }]}>{row.value}</Text>
                </View>
              ))}
            </Card3D>
            <Text style={[typography.caption, styles.help, { color: colors.textFaint }]}>
              รายการไม่ถูกต้อง? แจ้งทีมงานพร้อมเลขอ้างอิงได้ที่หน้าช่วยเหลือ
            </Text>
          </View>
        )}
      </FormSheet>
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
  summary: {
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: spacing.lg,
  },
  filters: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.xs,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  dayLabel: {
    fontWeight: '600',
    marginTop: spacing.xl,
    marginBottom: spacing.sm,
    marginLeft: spacing.xs,
  },
  groupGap: {
    height: spacing.lg,
  },
  segment: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: 14,
  },
  segmentFirst: {
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
  },
  segmentLast: {
    borderBottomLeftRadius: radii.xl,
    borderBottomRightRadius: radii.xl,
  },
  segmentDivider: {
    position: 'absolute',
    top: 0,
    // เริ่มเส้นหลังกล่องไอคอน (padding 16 + ไอคอน 44 + ระยะ 12)
    left: spacing.lg + 44 + spacing.md,
    right: spacing.lg,
    height: StyleSheet.hairlineWidth,
  },
  right: {
    alignItems: 'flex-end',
    gap: spacing.xs,
  },
  more: {
    alignItems: 'center',
    gap: spacing.xs,
    marginVertical: spacing.lg,
  },
  detailAmount: {
    alignItems: 'center',
    gap: spacing.sm,
    marginVertical: spacing.lg,
  },
  detailRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: spacing.md,
    paddingVertical: spacing.sm,
  },
  detailValue: {
    flex: 1,
    textAlign: 'right',
  },
  help: {
    marginTop: spacing.md,
    textAlign: 'center',
  },
});
