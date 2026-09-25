/**
 * ประวัติธุรกรรมกระเป๋าเงิน — ธีมนวลทองคำ
 *
 * - GET /wallet/transactions?page&type&per_page (กรองได้แค่ รายรับ/รายจ่าย — server ยังไม่รับช่วงวันที่
 *   จึงไม่มีตัวกรองวันที่ที่กดแล้วไม่มีผล)
 * - เปลี่ยนแท็บระหว่างโหลด → ทิ้งผลเก่า (requestId) · เลื่อนโหลดเพิ่ม · ดึงลงรีเฟรช
 * - แตะรายการ = รายละเอียด · ชื่อรายการผ่าน storePolicy (ไม่มีคำเครือข่าย/คอมมิชชั่นตามนโยบาย Google Play)
 * - สรุปรายรับ/รายจ่ายแสดงเฉพาะเมื่อ server ส่งยอดสรุปมา (ไม่แสดง ฿0 หลอกๆ)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { router } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { getWalletTransactions } from '@/services/api';
import { hasRestrictedText, walletReferenceLabel, walletTransactionTitle } from '@/utils/storePolicy';
import { Card3D, Chip, EmptyState, Pill, PriceText, Screen, StatTile } from '@/components/ui';
import { FormSheet } from '@/components/shop';
import { useTheme, radii, spacing, toneColors, typography, type Tone } from '@/theme';

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

const FILTERS: Array<{ key: FilterType; label: string; icon: string }> = [
  { key: 'all', label: 'ทั้งหมด', icon: '📋' },
  { key: 'in', label: 'รายรับ', icon: '⬇️' },
  { key: 'out', label: 'รายจ่าย', icon: '⬆️' },
];

const STATUS: Record<string, { label: string; tone: Tone }> = {
  completed: { label: 'สำเร็จ', tone: 'success' },
  pending: { label: 'รอดำเนินการ', tone: 'warning' },
  processing: { label: 'กำลังดำเนินการ', tone: 'info' },
  failed: { label: 'ไม่สำเร็จ', tone: 'danger' },
  cancelled: { label: 'ยกเลิก', tone: 'neutral' },
};

const emojiFor = (type: 'in' | 'out', referenceType?: string): string => {
  const ref = (referenceType || '').toLowerCase();
  if (ref.includes('withdraw')) return '🏦';
  if (ref.includes('topup') || ref.includes('deposit')) return '➕';
  if (ref.includes('refund')) return '↩️';
  if (ref.includes('transfer')) return '🔄';
  if (ref.includes('rider') || ref.includes('delivery')) return '🛵';
  if (ref.includes('fresh')) return '🥬';
  if (ref.includes('order') || ref.includes('purchase') || ref.includes('payment')) return '🛒';
  return type === 'in' ? '⬇️' : '⬆️';
};

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

const TransactionRow: React.FC<{ tx: Transaction; onPress: () => void }> = ({ tx, onPress }) => {
  const { colors } = useTheme();
  const income = tx.type === 'in';
  const t = toneColors(income ? 'success' : 'danger', colors);
  const st = STATUS[tx.status] || STATUS.pending;

  return (
    <Card3D
      onPress={onPress}
      padding={spacing.md}
      radius={radii.lg}
      shadow="sm"
      style={styles.card}
      accessibilityLabel={`${tx.title} ${income ? 'รับ' : 'จ่าย'} ${tx.amount} บาท ${st.label} ${tx.dateRelative || tx.date}`}
    >
      <View style={styles.row}>
        <View style={[styles.icon, { backgroundColor: t.bg }]}>
          <Text style={styles.emoji}>{emojiFor(tx.type, tx.referenceType)}</Text>
        </View>
        <View style={styles.flex}>
          <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
            {tx.title}
          </Text>
          <Text style={[typography.caption, { color: colors.textMuted }]}>{tx.dateRelative || tx.date}</Text>
        </View>
        <View style={styles.right}>
          <PriceText amount={income ? tx.amount : -tx.amount} signed size="md" tone={income ? 'success' : 'danger'} />
          {tx.status !== 'completed' && <Pill label={st.label} tone={st.tone} />}
        </View>
      </View>
    </Card3D>
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

  if (!isAuthenticated) {
    return (
      <Screen title="ประวัติธุรกรรม" scroll={false}>
        <EmptyState
          icon="📋"
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
            icon="📈"
            tone="success"
            value={<PriceText amount={summary.income} size="lg" tone="success" />}
            style={styles.flex}
          />
          <StatTile
            label="รายจ่าย"
            icon="📉"
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

  return (
    <Screen title="ประวัติธุรกรรม" subtitle="เงินเข้า-ออกของกระเป๋า" scroll={false}>
      <FlatList
        data={items}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => <TransactionRow tx={item} onPress={() => setSelected(item)} />}
        ListHeaderComponent={header}
        ListEmptyComponent={
          loading ? (
            <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
          ) : error ? (
            <EmptyState compact variant="error" message={error} onAction={() => load('initial', filter, 1)} />
          ) : (
            <EmptyState
              compact
              icon="📭"
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
        icon={selected ? emojiFor(selected.type, selected.referenceType) : undefined}
        title={selected?.title || 'รายละเอียด'}
        cancelLabel="ปิด"
        onClose={() => setSelected(null)}
      >
        {!!selected && !!st && (
          <View>
            <View style={styles.detailAmount}>
              <PriceText
                amount={selected.type === 'in' ? selected.amount : -selected.amount}
                signed
                size="xl"
                tone={selected.type === 'in' ? 'success' : 'danger'}
              />
              <Pill label={st.label} tone={st.tone} size="md" />
            </View>
            <Card3D variant="inset" padding={spacing.md}>
              {[
                { label: 'ประเภท', value: walletReferenceLabel(selected.referenceType, selected.type === 'in') },
                ...(selected.description ? [{ label: 'รายละเอียด', value: selected.description }] : []),
                { label: 'วันที่', value: selected.date || selected.dateRelative || '-' },
                { label: 'เลขอ้างอิง', value: `#${selected.id}` },
              ].map((row) => (
                <View key={row.label} style={styles.detailRow}>
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
    paddingBottom: spacing.xxxl * 2,
  },
  summary: {
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: spacing.md,
  },
  filters: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginBottom: spacing.md,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  card: {
    marginBottom: spacing.sm,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  icon: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emoji: {
    fontSize: 20,
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
    paddingVertical: spacing.xs,
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
