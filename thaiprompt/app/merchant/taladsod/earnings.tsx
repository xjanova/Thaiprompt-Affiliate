/**
 * รายได้ร้านตลาดสด — แทนหน้าเว็บ /taladsod/seller/earnings (อ่านอย่างเดียว)
 *
 * GET /fresh-market/seller/earnings → ตัวเลขชุดเดียวกับหน้าเว็บ (server คำนวณจาก service เดียวกัน)
 *   - รายรับสุทธิ = seller_earning ของออเดอร์ที่ "สำเร็จ" · ยอดขาย = total_amount (ไม่รวมค่าส่ง) · GP = platform_fee
 *   - เงินที่กำลังจะได้: จ่ายผ่านกระเป๋าแล้ว (ระบบถือไว้) / เงินสดเก็บปลายทาง
 * แอปไม่คำนวณเงินเอง (ยกเว้นผลรวมกราฟ 14 วันจากตัวเลขของ server เพื่อแสดงหัวกราฟ)
 *
 * หน้าตา: การ์ดน้ำเงินลายกนก (ตัวเลขทอง) + ชิปเลือกช่วงเวลา → กราฟแท่ง 14 วัน → เงินที่กำลังจะได้
 *         → ออเดอร์ที่สำเร็จล่าสุด → เงินเข้ากระเป๋า
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { getFmSellerEarnings, type FmEarnings } from '@/services/api/taladsodSellerManageApi';
import {
  Button3D,
  Card3D,
  Chip,
  EmptyState,
  Icon,
  PriceText,
  Screen,
  SectionHeader,
  formatBaht,
  selectionHaptic,
} from '@/components/ui';
import { HeroCard, IconTile, NoticeBanner } from '@/components/merchant';
import { SkeletonBlock, SkeletonCard } from '@/components/merchant/SkeletonBlock';
import { useTheme, radii, spacing, typography } from '@/theme';

type PeriodKey = 'today' | 'week' | 'month' | 'all';
const PERIOD_KEYS: PeriodKey[] = ['today', 'week', 'month', 'all'];
const CHART_HEIGHT = 120;

type LoadState = { kind: 'loading' } | { kind: 'not_seller' } | { kind: 'error'; message: string } | { kind: 'ready'; data: FmEarnings };

/** วันที่แบบไทยสั้น เช่น "26 ก.ย. 14:30" (อ่านไม่ได้ = '') */
const thaiDate = (iso: string | null, withTime: boolean = true): string => {
  if (!iso) return '';
  const t = new Date(iso);
  if (!Number.isFinite(t.getTime())) return '';
  try {
    return t.toLocaleDateString('th-TH', withTime ? { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' } : { day: 'numeric', month: 'short', year: 'numeric' });
  } catch {
    return iso.slice(0, 10);
  }
};

export default function TaladsodSellerEarningsScreen() {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [state, setState] = useState<LoadState>({ kind: 'loading' });
  const [refreshing, setRefreshing] = useState(false);
  const [period, setPeriod] = useState<PeriodKey>('today');
  const [selectedDay, setSelectedDay] = useState<number | null>(null);

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);
  const readyRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'silent') => {
      if (!isAuthenticated) return;
      const requestId = ++requestIdRef.current;
      if (mode === 'refresh') setRefreshing(true);
      const res = await getFmSellerEarnings();
      if (!mountedRef.current || requestId !== requestIdRef.current) return;
      setRefreshing(false);
      if (res.success) {
        readyRef.current = true;
        setState({ kind: 'ready', data: res.data });
      } else if (res.code === 'NOT_SELLER') {
        setState({ kind: 'not_seller' });
      } else {
        // โหลดซ้ำแล้วล้ม → เก็บตัวเลขเดิมไว้
        setState((prev) => (prev.kind === 'ready' ? prev : { kind: 'error', message: res.message }));
      }
    },
    [isAuthenticated]
  );

  // เปิดหน้า = โหลด · กลับมาหน้านี้ = รีเฟรชเงียบ (ไม่โชว์โครงร่างซ้ำ)
  useFocusEffect(
    useCallback(() => {
      load(readyRef.current ? 'silent' : 'initial');
    }, [load])
  );

  if (!isAuthenticated) {
    return (
      <Screen title="รายได้ร้าน" scroll={false}>
        <EmptyState art="wallet" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  const renderBody = () => {
    switch (state.kind) {
      case 'loading':
        return (
          <View style={styles.skeleton}>
            <SkeletonBlock height={150} radius={24} />
            <View style={styles.chips}>
              {PERIOD_KEYS.map((k) => (
                <SkeletonBlock key={k} width={72} height={32} radius={16} />
              ))}
            </View>
            <SkeletonBlock height={CHART_HEIGHT + 40} radius={radii.xl} />
            <SkeletonCard lines={3} withTile />
          </View>
        );
      case 'not_seller':
        return (
          <EmptyState
            art="cart"
            title="ยังไม่มีร้านในตลาดสด"
            message="สมัครเปิดร้านก่อน แล้วรายได้จากออเดอร์จะมาแสดงที่นี่"
            actionLabel="สมัครเปิดร้าน"
            onAction={() => router.replace('/merchant/taladsod/register' as never)}
          />
        );
      case 'error':
        return <EmptyState compact variant="error" message={state.message} onAction={() => load('initial')} />;
      case 'ready':
        return renderReady(state.data);
      default:
        return null;
    }
  };

  const renderReady = (data: FmEarnings) => {
    const p = data.periods[period];
    const maxNet = Math.max(1, ...data.daily.map((d) => d.net));
    const sum14 = data.daily.reduce((s, d) => s + d.net, 0);
    const orders14 = data.daily.reduce((s, d) => s + d.orders, 0);
    const day = selectedDay !== null ? data.daily[selectedDay] : null;
    const noSales = data.periods.all.orders === 0;

    return (
      <>
        {/* ---------- ตัวเลขหลัก ---------- */}
        <HeroCard>
          <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>รายรับสุทธิ · {p.label}</Text>
          <Text
            style={[typography.moneyLg, { color: colors.goldLight }]}
            numberOfLines={1}
            adjustsFontSizeToFit
            accessibilityLabel={`รายรับสุทธิ${p.label} ${p.net} บาท`}
          >
            {formatBaht(p.net, { decimals: 2 })}
          </Text>
          <View style={[styles.heroRow, { borderTopColor: colors.headerGlassBorder }]}>
            <View style={styles.heroStat}>
              <Text style={[typography.micro, { color: colors.onHeaderMuted }]}>ออเดอร์สำเร็จ</Text>
              <Text style={[typography.bodyStrong, { color: colors.onHeader }]}>{p.orders.toLocaleString('th-TH')}</Text>
            </View>
            <View style={styles.heroStat}>
              <Text style={[typography.micro, { color: colors.onHeaderMuted }]}>ยอดขาย</Text>
              <Text style={[typography.bodyStrong, { color: colors.onHeader }]}>{formatBaht(p.gross, { decimals: 2 })}</Text>
            </View>
            <View style={styles.heroStat}>
              <Text style={[typography.micro, { color: colors.onHeaderMuted }]}>ค่า GP</Text>
              <Text style={[typography.bodyStrong, { color: colors.onHeader }]}>{formatBaht(p.gp, { decimals: 2 })}</Text>
            </View>
          </View>
        </HeroCard>

        <View style={[styles.chips, styles.gapTop]}>
          {PERIOD_KEYS.map((k) => (
            <Chip
              key={k}
              label={data.periods[k].label}
              size="sm"
              selected={period === k}
              onPress={() => {
                selectionHaptic();
                setPeriod(k);
              }}
            />
          ))}
        </View>

        {data.gp_free && (
          <NoticeBanner
            tone="success"
            text={`ฟรี GP ช่วงเปิดตัว${data.gp_free_until ? ` ถึง ${thaiDate(data.gp_free_until, false)}` : ''} — ออเดอร์ใหม่ไม่ถูกหักค่าธรรมเนียม`}
            style={styles.gapTop}
          />
        )}

        {noSales ? (
          <EmptyState
            compact
            art="basket"
            title="ยังไม่มีออเดอร์ที่สำเร็จ"
            message="รายได้จะขึ้นที่นี่เมื่อออเดอร์จบ (ลูกค้ายืนยันรับของหรือระบบยืนยันให้อัตโนมัติ)"
            style={styles.section}
          />
        ) : (
          <>
            {/* ---------- กราฟ 14 วัน ---------- */}
            <SectionHeader
              title="รายได้สุทธิ 14 วันล่าสุด"
              subtitle={`${formatBaht(sum14, { decimals: 2 })} จาก ${orders14.toLocaleString('th-TH')} ออเดอร์ · แตะแท่งเพื่อดูรายวัน`}
              style={styles.section}
            />
            <Card3D padding={spacing.lg}>
              <View style={styles.chart}>
                {data.daily.map((d, i) => {
                  const h = d.net > 0 ? Math.max(4, Math.round((d.net / maxNet) * CHART_HEIGHT)) : 2;
                  const active = selectedDay === i;
                  return (
                    <Pressable
                      key={d.date}
                      onPress={() => setSelectedDay(active ? null : i)}
                      style={styles.barCol}
                      accessibilityRole="button"
                      accessibilityLabel={`${d.label} รายได้ ${d.net} บาท ${d.orders} ออเดอร์`}
                      hitSlop={4}
                    >
                      <View style={[styles.barTrack, { height: CHART_HEIGHT }]}>
                        <View
                          style={[
                            styles.bar,
                            {
                              height: h,
                              backgroundColor: active ? colors.goldDeep : d.net > 0 ? colors.gold : colors.divider,
                            },
                          ]}
                        />
                      </View>
                      <Text style={[typography.micro, { color: active ? colors.goldDeep : colors.textFaint }]}>
                        {d.date.slice(8).replace(/^0/, '')}
                      </Text>
                    </Pressable>
                  );
                })}
              </View>
              <View style={[styles.dayInfo, { borderTopColor: colors.divider }]}>
                {day ? (
                  <>
                    <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
                      {day.label} · {day.orders.toLocaleString('th-TH')} ออเดอร์
                    </Text>
                    <PriceText amount={day.net} size="md" tone="gold" decimals={2} />
                  </>
                ) : (
                  <Text style={[typography.caption, { color: colors.textMuted }]}>นับตามวันที่ออเดอร์สำเร็จ · วันที่ไม่มีขาย = 0</Text>
                )}
              </View>
            </Card3D>
          </>
        )}

        {/* ---------- เงินที่กำลังจะได้ ---------- */}
        <SectionHeader title="เงินที่กำลังจะได้" subtitle="ออเดอร์ที่ยังไม่จบ" style={styles.section} />
        <Card3D padding={0} contentStyle={styles.listCard}>
          <View style={styles.row}>
            <IconTile icon="hourglass" tone="info" />
            <Text style={[typography.body, styles.flex, { color: colors.text }]}>ออเดอร์ที่ยังไม่จบ</Text>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{data.pending.orders.toLocaleString('th-TH')} รายการ</Text>
          </View>
          <View style={[styles.row, { borderTopColor: colors.divider }, styles.rowBorder]}>
            <IconTile icon="wallet" tone="gold" />
            <View style={styles.flex}>
              <Text style={[typography.body, { color: colors.text }]}>จ่ายแล้วผ่านกระเป๋าเงิน</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>ระบบถือไว้ เข้ากระเป๋าเมื่อลูกค้ารับของ</Text>
            </View>
            <PriceText amount={data.pending.held_net} size="md" tone="gold" decimals={2} />
          </View>
          <View style={[styles.row, { borderTopColor: colors.divider }, styles.rowBorder]}>
            <IconTile icon="money" tone="success" />
            <View style={styles.flex}>
              <Text style={[typography.body, { color: colors.text }]}>เงินสดเก็บปลายทาง</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>ยังไม่ได้รับ เก็บจากลูกค้าตอนส่งของ</Text>
            </View>
            <PriceText amount={data.pending.cod_to_collect} size="md" tone="strong" decimals={2} />
          </View>
          <View style={[styles.row, { borderTopColor: colors.divider }, styles.rowBorder]}>
            <IconTile icon="percent" />
            <Text style={[typography.body, styles.flex, { color: colors.text }]}>อัตรา GP ตอนนี้</Text>
            <Text style={[typography.bodyStrong, { color: data.gp_free ? colors.success : colors.textStrong }]}>
              {data.gp_free ? 'ฟรี' : `${data.gp_rate}%`}
            </Text>
          </View>
        </Card3D>
        {data.gp_debt > 0 && (
          <NoticeBanner
            tone="warning"
            text={`GP ค้างชำระ ${formatBaht(data.gp_debt, { decimals: 2 })} (จากออเดอร์เก็บเงินปลายทาง) — หักจากรายได้ออเดอร์ถัดไปอัตโนมัติ`}
            style={styles.gapTop}
          />
        )}

        {/* ---------- ออเดอร์สำเร็จล่าสุด ---------- */}
        {data.recent_completed.length > 0 && (
          <>
            <SectionHeader title="ออเดอร์ที่สำเร็จล่าสุด" style={styles.section} />
            <Card3D padding={0} contentStyle={styles.listCard}>
              {data.recent_completed.map((o, i) => (
                <View key={o.id} style={[styles.row, i > 0 && [styles.rowBorder, { borderTopColor: colors.divider }]]}>
                  <IconTile icon="receipt" />
                  <View style={styles.flex}>
                    <Text numberOfLines={1} style={[typography.bodyStrong, { color: colors.textStrong }]}>
                      #{o.order_number}
                      {o.title ? ` · ${o.title}` : ''}
                      {o.items_count > 1 ? ` +${o.items_count - 1}` : ''}
                    </Text>
                    <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
                      {thaiDate(o.completed_at)}
                      {o.payment_method_label ? ` · ${o.payment_method_label}` : ''}
                    </Text>
                  </View>
                  <View style={styles.amountCol}>
                    <PriceText amount={o.seller_earning} size="md" tone="success" decimals={2} signed />
                    <Text style={[typography.micro, { color: colors.textFaint }]}>GP {formatBaht(o.platform_fee, { decimals: 2 })}</Text>
                  </View>
                </View>
              ))}
            </Card3D>
          </>
        )}

        {/* ---------- เงินเข้ากระเป๋า ---------- */}
        <SectionHeader title="เงินเข้ากระเป๋าจากตลาดสด" style={styles.section} />
        <Card3D padding={0} contentStyle={styles.listCard}>
          {data.payouts.length === 0 ? (
            <View style={styles.emptyRow}>
              <Icon name="wallet" size={22} color={colors.textFaint} />
              <Text style={[typography.bodySm, styles.center, { color: colors.textMuted }]}>
                ยังไม่มีเงินเข้ากระเป๋า — เงินเข้าเมื่อลูกค้าที่จ่ายผ่านกระเป๋าเงินยืนยันรับของ
              </Text>
            </View>
          ) : (
            data.payouts.map((tx, i) => (
              <View key={tx.id} style={[styles.row, i > 0 && [styles.rowBorder, { borderTopColor: colors.divider }]]}>
                <IconTile icon="arrow-down" tone="success" />
                <View style={styles.flex}>
                  <Text numberOfLines={1} style={[typography.body, { color: colors.text }]}>
                    {tx.description || 'รายได้ออเดอร์ตลาดสด'}
                  </Text>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>{thaiDate(tx.created_at)}</Text>
                </View>
                <PriceText amount={tx.amount} size="md" tone="success" decimals={2} signed />
              </View>
            ))
          )}
          <View style={[styles.walletRow, { borderTopColor: colors.divider }]}>
            <View style={styles.flex}>
              <Text style={[typography.caption, { color: colors.textMuted }]}>ยอดในกระเป๋าเงินตอนนี้</Text>
              <PriceText amount={data.wallet_balance} size="lg" tone="gold" decimals={2} />
            </View>
            <Button3D title="ถอนเงิน" icon="bank" size="sm" variant="navy" onPress={() => router.push('/wallet-withdraw' as never)} />
          </View>
        </Card3D>
      </>
    );
  };

  return (
    <Screen
      title="รายได้ร้าน"
      subtitle="รายรับสุทธิ ค่า GP และเงินที่กำลังจะได้"
      refreshing={refreshing}
      onRefresh={() => load('refresh')}
    >
      {renderBody()}
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
  skeleton: {
    gap: spacing.lg,
  },
  section: {
    marginTop: spacing.xxl,
  },
  gapTop: {
    marginTop: spacing.md,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
  },
  heroRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
  heroStat: {
    flex: 1,
    gap: 2,
  },
  chart: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    gap: 3,
  },
  barCol: {
    flex: 1,
    alignItems: 'center',
    gap: 4,
  },
  barTrack: {
    width: '100%',
    justifyContent: 'flex-end',
    alignItems: 'center',
  },
  bar: {
    width: '72%',
    maxWidth: 16,
    borderTopLeftRadius: 5,
    borderTopRightRadius: 5,
  },
  dayInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    minHeight: 40,
  },
  listCard: {
    overflow: 'hidden',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: 14,
  },
  rowBorder: {
    borderTopWidth: 1,
  },
  amountCol: {
    alignItems: 'flex-end',
  },
  emptyRow: {
    alignItems: 'center',
    gap: spacing.xs,
    padding: spacing.xl,
  },
  walletRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: 14,
    borderTopWidth: 1,
  },
});
