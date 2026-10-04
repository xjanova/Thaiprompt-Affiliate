/**
 * ค่าตอบแทนไรเดอร์ของร้าน (ไรเดอร์รอบ 2 ตามม็อกอัป SellerPricing)
 *
 * params: kind = shop (ร้านช้อป /seller/rider-pay — ค่าเริ่มต้น) | fresh (ร้านตลาดสด /fresh-market/seller/rider-pay)
 *         name = ชื่อร้าน (ไม่บังคับ — แสดงใต้ชื่อหน้า)
 *
 * - ค่าส่งตามระยะคำนวณอัตโนมัติจากสูตรของระบบ (ตรวจย้อนได้) — ร้านตั้งได้แค่
 *   โบนัสให้ไรเดอร์ (ปกติ / ช่วงเร่งด่วน, 0–30 บาท บนแถบเลื่อน) และ "ส่งฟรี" (ร้านจ่ายค่าส่งแทนลูกค้า)
 * - โบนัสหักจากรายรับของร้าน ไรเดอร์ได้เต็มจำนวน
 * - ผู้ช่วย AI เขียนคำแนะนำเท่านั้น ไม่เปลี่ยนราคาเอง · "ใช้คำแนะนำนี้" = เติมค่าที่แนะนำลงฟอร์ม (ยังต้องกดบันทึก)
 * - ขยับแถบเลื่อน → ขอ preview จาก server (หน่วง 450 ms) ให้เห็นโอกาสมีคนรับที่เปลี่ยนไปก่อนบันทึก
 * - ยังไม่บันทึกแล้วจะออก → ถามก่อนทิ้งการแก้ไข
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Alert, Pressable, StyleSheet, View } from 'react-native';
import { router, useLocalSearchParams, useNavigation } from 'expo-router';
import { usePreventRemove } from 'expo-router/react-navigation';
import { Text } from '@/components/ui/Text';
import {
  Button3D,
  Card3D,
  EmptyState,
  Icon,
  Pill,
  Screen,
  SectionHeader,
  resultHaptic,
  selectionHaptic,
} from '@/components/ui';
import { StickyBar } from '@/components/shop';
import { IconTile, NoticeBanner } from '@/components/merchant';
import { SkeletonCard } from '@/components/merchant/SkeletonBlock';
import { GoldSlider, RiderPayBands, pct } from '@/components/merchant/RiderPayKit';
import { useAuthStore } from '@/stores/authStore';
import {
  getSellerRiderPay,
  riderPayBody,
  updateSellerRiderPay,
  type RiderPay,
  type RiderPayBand,
  type RiderPayPreview,
  type RiderPaySettings,
} from '@/services/api/sellerStoreApi';
import { getFmRiderPay, updateFmRiderPay } from '@/services/api/taladsodSellerManageApi';
import type { ApiResult } from '@/services/api/client';
import { useTheme, radii, spacing, typography } from '@/theme';

type Kind = 'shop' | 'fresh';

/** แถบเลื่อนสูงสุด 30 บาท (server รับได้ถึง 100 — ค่าเดิมที่เกิน 30 จะขยายแถบให้เอง) */
const SLIDER_MAX = 30;
const PREVIEW_DELAY_MS = 450;

type LoadState =
  | { kind: 'loading' }
  | { kind: 'ready' }
  | { kind: 'not_seller' }
  | { kind: 'unavailable' }
  | { kind: 'error'; message: string };

const API: Record<
  Kind,
  {
    get: (preview?: RiderPayPreview | null, signal?: AbortSignal) => Promise<ApiResult<RiderPay>>;
    put: (settings: RiderPaySettings) => Promise<ApiResult<RiderPay>>;
    setupPath: string;
  }
> = {
  shop: { get: getSellerRiderPay, put: updateSellerRiderPay, setupPath: '/merchant/apply' },
  fresh: { get: getFmRiderPay, put: updateFmRiderPay, setupPath: '/merchant/taladsod/register' },
};

const sameSettings = (a: RiderPaySettings, b: RiderPaySettings): boolean =>
  a.rider_bonus === b.rider_bonus && a.rider_bonus_peak === b.rider_bonus_peak && a.rider_free_delivery === b.rider_free_delivery;

const toPreview = (s: RiderPaySettings): RiderPayPreview => ({
  bonus: s.rider_bonus,
  bonus_peak: s.rider_bonus_peak,
  free_delivery: s.rider_free_delivery,
});

const hourLabel = (h: number): string => `${String(Math.floor(h)).padStart(2, '0')}:00`;

/** โบนัสเป็นข้อความ: จำนวนเต็มแสดงตรงๆ · มีเศษสตางค์แสดง 2 ตำแหน่ง (ค่าจาก server ไม่ถูกปัดทิ้ง — M3) */
const bonusText = (value: number): string => (Number.isInteger(value) ? String(value) : value.toFixed(2));

/** ช่วงที่โบนัสช่วยมากที่สุด (ตัวเลขจาก preview ของ server) */
const bestLift = (bands: RiderPayBand[]): { band: RiderPayBand; from: number; to: number } | null => {
  let best: { band: RiderPayBand; from: number; to: number } | null = null;
  for (const band of bands) {
    if (band.accept_rate_5min === null || band.accept_rate_with_bonus === null) continue;
    const lift = band.accept_rate_with_bonus - band.accept_rate_5min;
    if (lift > 0.005 && (!best || lift > best.to - best.from)) {
      best = { band, from: band.accept_rate_5min, to: band.accept_rate_with_bonus };
    }
  }
  return best;
};

export default function RiderPayScreen() {
  const { colors, isDark } = useTheme();
  const navigation = useNavigation();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const params = useLocalSearchParams<{ kind?: string; name?: string }>();
  const kind: Kind = params.kind === 'fresh' ? 'fresh' : 'shop';
  const api = API[kind];
  const storeName = typeof params.name === 'string' && params.name.trim() ? params.name.trim().slice(0, 60) : null;

  const [state, setState] = useState<LoadState>({ kind: 'loading' });
  const [saved, setSaved] = useState<RiderPay | null>(null);
  const [view, setView] = useState<RiderPay | null>(null);
  const [draft, setDraft] = useState<RiderPaySettings | null>(null);
  const [previewing, setPreviewing] = useState(false);
  const [previewFailed, setPreviewFailed] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [showReason, setShowReason] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);

  const mountedRef = useRef(true);
  const savingRef = useRef(false);
  const previewReqRef = useRef(0);
  const previewAbortRef = useRef<AbortController | null>(null);
  const noticeTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const dirtyRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      previewAbortRef.current?.abort();
      if (noticeTimerRef.current) clearTimeout(noticeTimerRef.current);
    };
  }, []);

  const flash = useCallback((text: string) => {
    if (!mountedRef.current) return;
    if (noticeTimerRef.current) clearTimeout(noticeTimerRef.current);
    setNotice(text);
    noticeTimerRef.current = setTimeout(() => mountedRef.current && setNotice(null), 4000);
  }, []);

  /** ข้อมูลชุดใหม่จาก server = ค่าที่บันทึกแล้ว */
  const applySaved = useCallback((data: RiderPay) => {
    setSaved(data);
    setView(data);
    setDraft(data.settings);
    setPreviewFailed(false);
    setFormError(null);
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh') => {
      if (!isAuthenticated) return;
      if (mode === 'refresh') setRefreshing(true);
      const result = await api.get(null);
      if (!mountedRef.current) return;
      setRefreshing(false);
      if (result.success) {
        // ดึงลงตอนกำลังแก้ → ไม่ทับค่าที่กำลังแก้ (อัปเดตแค่ค่าอ้างอิง)
        if (mode === 'initial' || !dirtyRef.current) applySaved(result.data);
        else setSaved(result.data);
        setState({ kind: 'ready' });
        return;
      }
      if (['NOT_A_SELLER', 'NOT_SELLER'].includes(result.code)) {
        setState({ kind: 'not_seller' });
      } else if (result.status === 404 && result.code === 'NOT_FOUND') {
        // server ยังไม่มี endpoint นี้ (ยังไม่อัปเดต)
        setState({ kind: 'unavailable' });
      } else if (mode === 'refresh') {
        flash(result.message);
      } else {
        setState({ kind: 'error', message: result.message });
      }
    },
    [api, applySaved, flash, isAuthenticated]
  );

  useEffect(() => {
    load('initial');
  }, [load]);

  const dirty = !!draft && !!saved && !sameSettings(draft, saved.settings);
  dirtyRef.current = dirty;

  // ---------- preview: ขยับค่าแล้วขอตารางใหม่จาก server (หน่วงกันยิงถี่) ----------
  useEffect(() => {
    if (!draft || !saved) return undefined;
    if (sameSettings(draft, saved.settings)) {
      previewAbortRef.current?.abort();
      ++previewReqRef.current;
      setView(saved);
      setPreviewing(false);
      setPreviewFailed(false);
      return undefined;
    }
    setPreviewing(true);
    const timer = setTimeout(async () => {
      previewAbortRef.current?.abort();
      const controller = new AbortController();
      previewAbortRef.current = controller;
      const requestId = ++previewReqRef.current;
      const result = await api.get(toPreview(draft), controller.signal);
      if (!mountedRef.current || requestId !== previewReqRef.current) return;
      setPreviewing(false);
      if (result.success) {
        setView(result.data);
        setPreviewFailed(false);
      } else {
        setPreviewFailed(true);
      }
    }, PREVIEW_DELAY_MS);
    return () => clearTimeout(timer);
  }, [api, draft, saved]);

  usePreventRemove(dirty, ({ data }) => {
    if (savingRef.current) {
      Alert.alert('กำลังบันทึก', 'รอสักครู่ ระบบกำลังบันทึกการตั้งค่า');
      return;
    }
    Alert.alert('ยังไม่ได้บันทึก', 'มีการแก้ไขที่ยังไม่บันทึก ออกจากหน้านี้แล้วการแก้ไขจะหายไป', [
      { text: 'อยู่ต่อ', style: 'cancel' },
      { text: 'ทิ้งการแก้ไข', style: 'destructive', onPress: () => navigation.dispatch(data.action) },
    ]);
  });

  const update = (patch: Partial<RiderPaySettings>) => {
    setDraft((prev) => (prev ? { ...prev, ...patch } : prev));
    setFormError(null);
  };

  const advice = view?.advice ?? saved?.advice ?? null;
  const suggestion: RiderPaySettings | null = advice
    ? {
        rider_bonus: advice.suggested_bonus,
        rider_bonus_peak: advice.suggested_bonus_peak,
        rider_free_delivery: advice.suggest_free_delivery,
      }
    : null;
  const usingSuggestion = !!suggestion && !!draft && sameSettings(draft, suggestion);

  const applyAdvice = () => {
    if (!suggestion) return;
    resultHaptic('success');
    update(suggestion);
    flash('ใส่ค่าที่แนะนำแล้ว ตรวจดูแล้วกดบันทึกได้เลย');
  };

  const save = async () => {
    if (!draft || !dirty || savingRef.current) return;
    savingRef.current = true;
    setSaving(true);
    setFormError(null);
    try {
      const sent = riderPayBody(draft);
      const result = await api.put(draft);
      if (!mountedRef.current) return;
      if (result.success) {
        resultHaptic('success');
        applySaved(result.data);
        // server ปรับค่าที่บันทึก (เช่น เพดาน) → บอกให้รู้ ไม่เปลี่ยนเงียบๆ (M3)
        const savedSettings = result.data.settings;
        if (!sameSettings(sent, savedSettings)) {
          flash(
            `บันทึกแล้ว ระบบปรับเป็นโบนัสปกติ ${bonusText(savedSettings.rider_bonus)} บาท · ช่วงเร่งด่วน ${bonusText(savedSettings.rider_bonus_peak)} บาท${
              savedSettings.rider_free_delivery ? ' · ส่งฟรี' : ''
            }`
          );
        } else {
          flash(result.message || 'บันทึกแล้ว มีผลกับออเดอร์ใหม่ทันที');
        }
        return;
      }
      resultHaptic('error');
      if (['NOT_A_SELLER', 'NOT_SELLER'].includes(result.code)) {
        setState({ kind: 'not_seller' });
        return;
      }
      setFormError(result.message);
    } finally {
      savingRef.current = false;
      if (mountedRef.current) setSaving(false);
    }
  };

  // ---------- ค่าที่ใช้แสดงผล ----------
  const bands = view?.bands ?? [];
  const lift = useMemo(() => (draft && draft.rider_bonus > 0 ? bestLift(bands) : null), [bands, draft]);
  const anyEstimate = bands.some((b) => b.basis === 'estimate');
  const anyData = bands.some((b) => b.basis === 'data');
  const base = view?.base ?? saved?.base ?? null;
  const peakHours = (base?.peak_hours ?? []).map(([a, b]) => `${hourLabel(a)}–${hourLabel(b)}`).join(' และ ');
  const sliderMax = Math.max(SLIDER_MAX, Math.ceil(Math.max(draft?.rider_bonus ?? 0, draft?.rider_bonus_peak ?? 0) / 10) * 10);
  const dist = view?.customer_distance ?? saved?.customer_distance ?? null;

  // =====================================================
  // แสดงผล
  // =====================================================

  const title = 'ค่าตอบแทนไรเดอร์';
  const subtitle = `${storeName ? `${storeName} · ` : ''}ตั้งครั้งเดียว ใช้ทุกออเดอร์`;

  if (!isAuthenticated) {
    return (
      <Screen title={title} scroll={false}>
        <EmptyState art="scooter" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  const renderAdvice = () => {
    if (!advice || !draft) return null;
    const fromStoreData = anyData || !!dist;
    return (
      <Card3D gradientBorder padding={spacing.lg} style={styles.block}>
        <View style={styles.adviceHead}>
          <IconTile icon="sparkle" tone="gold" />
          <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>
            {advice.source === 'ai' ? 'ผู้ช่วย AI แนะนำ' : 'คำแนะนำจากระบบ'}
          </Text>
          <Pill label={fromStoreData ? 'จากข้อมูลจริงของร้าน' : 'ประมาณการเบื้องต้น'} tone="gold" />
        </View>
        {!!advice.headline && (
          <Text style={[typography.bodyStrong, styles.adviceHeadline, { color: colors.textStrong }]}>{advice.headline}</Text>
        )}
        {!!advice.text && <Text style={[typography.body, { color: colors.text }]}>{advice.text}</Text>}

        <View style={styles.adviceActions}>
          <Button3D
            title={usingSuggestion ? 'ใช้คำแนะนำนี้อยู่' : 'ใช้คำแนะนำนี้'}
            icon={usingSuggestion ? 'check-circle' : undefined}
            variant={usingSuggestion ? 'secondary' : 'primary'}
            disabled={usingSuggestion || saving}
            onPress={applyAdvice}
            style={styles.flex}
          />
          <Button3D
            title={showReason ? 'ซ่อนเหตุผล' : 'ดูเหตุผล'}
            variant="secondary"
            onPress={() => {
              selectionHaptic();
              setShowReason((v) => !v);
            }}
          />
        </View>

        {showReason && (
          <View style={[styles.reason, { backgroundColor: colors.inset, borderColor: colors.border }]}>
            <ReasonLine
              icon="target"
              text={`แนะนำ: โบนัสปกติ ${bonusText(advice.suggested_bonus)} บาท · ช่วงเร่งด่วน ${bonusText(advice.suggested_bonus_peak)} บาท${
                advice.suggest_free_delivery ? ' · เปิดส่งฟรี' : ''
              }`}
            />
            {!!dist && (
              <ReasonLine
                icon="map-pin"
                text={`ลูกค้าครึ่งหนึ่งอยู่ห่างร้านไม่เกิน ${dist.p50_km.toFixed(1)} กม. และ 9 ใน 10 ไม่เกิน ${dist.p90_km.toFixed(1)} กม. (จาก ${dist.sample_size.toLocaleString('th-TH')} ออเดอร์)`}
              />
            )}
            {!!peakHours && (
              <ReasonLine
                icon="clock"
                text={`ช่วงเร่งด่วน ${peakHours}${base && base.peak_surcharge > 0 ? ` ระบบบวกค่าส่งเพิ่ม ${base.peak_surcharge} บาท` : ''} งานไกลมักหาคนรับยากกว่า`}
              />
            )}
            <ReasonLine
              icon="chart-bar"
              text={
                anyData
                  ? 'โอกาสมีคนรับคิดจากงานจริงในพื้นที่ของร้าน'
                  : 'โอกาสมีคนรับเป็นการประมาณการ เพราะงานจริงในพื้นที่ยังมีไม่พอ'
              }
            />
            {!!advice.generated_at && (
              <Text style={[typography.micro, { color: colors.textFaint }]}>อัปเดตคำแนะนำ {formatDate(advice.generated_at)}</Text>
            )}
          </View>
        )}
      </Card3D>
    );
  };

  const renderReady = () => {
    if (!draft || !view) return null;
    return (
      <>
        {!!notice && <NoticeBanner tone="success" text={notice} style={styles.block} />}

        {renderAdvice()}

        {/* ---------- ตารางค่าส่งตามระยะ ---------- */}
        <SectionHeader title="ค่าส่งตามระยะ (คำนวณให้อัตโนมัติ)" style={styles.sectionHeader} />
        <Card3D padding={spacing.lg} style={styles.block}>
          {bands.length > 0 ? (
            <>
              <RiderPayBands bands={bands} withBonus={draft.rider_bonus > 0} />
              {anyEstimate && (
                <Text style={[typography.caption, styles.footnote, { color: colors.textMuted }]}>
                  ประมาณการ = งานจริงในระยะนั้นยังมีไม่พอ ใช้ค่าประมาณจากสูตร
                </Text>
              )}
            </>
          ) : (
            <Text style={[typography.bodySm, { color: colors.textMuted }]}>
              ยังคำนวณตารางค่าส่งไม่ได้ ตรวจว่าเปิดส่งด้วยไรเดอร์และปักหมุดร้านแล้ว
            </Text>
          )}
        </Card3D>

        {/* ---------- โบนัส + ส่งฟรี ---------- */}
        <Card3D padding={spacing.lg} style={styles.block}>
          <View style={styles.bonusHead}>
            <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>โบนัสที่ร้านเติมให้ไรเดอร์</Text>
            <Text style={[typography.money, { color: colors.goldDeep }]}>
              {bonusText(draft.rider_bonus)}
              <Text style={[typography.caption, { color: colors.textMuted }]}> บาท/ออเดอร์</Text>
            </Text>
          </View>
          <GoldSlider
            value={draft.rider_bonus}
            max={sliderMax}
            onChange={(v) => update({ rider_bonus: v })}
            disabled={saving}
            accessibilityLabel="โบนัสที่ร้านเติมให้ไรเดอร์ต่อออเดอร์"
            style={styles.slider}
          />

          <View style={[styles.callout, { backgroundColor: colors.successSoft }]}>
            <Icon name="lightning" size={18} color={colors.success} weight="fill" />
            <Text style={[typography.bodySm, styles.flex, { color: isDark ? colors.text : colors.textStrong }]}>
              {draft.rider_bonus <= 0
                ? 'ยังไม่เติมโบนัส ไรเดอร์ได้ค่าส่งตามสูตรปกติ'
                : lift
                  ? `เติม ${bonusText(draft.rider_bonus)} บาท งานระยะ ${lift.band.label} มีโอกาสมีคนรับเพิ่มจาก ${pct(lift.from)} เป็น `
                  : `ไรเดอร์ได้เพิ่ม ${bonusText(draft.rider_bonus)} บาทต่อออเดอร์ (หักจากรายรับของร้าน)`}
              {draft.rider_bonus > 0 && !!lift && (
                <Text style={[typography.bodyStrong, { color: colors.success }]}>{pct(lift.to)}</Text>
              )}
            </Text>
            {previewing && <Pill label="กำลังคำนวณ" tone="neutral" icon="hourglass" />}
          </View>
          {previewFailed && (
            <Text style={[typography.caption, styles.gapXs, { color: colors.textMuted }]}>
              คำนวณตัวอย่างไม่สำเร็จ ตัวเลขด้านบนเป็นของค่าที่บันทึกไว้ (บันทึกได้ตามปกติ)
            </Text>
          )}

          {/* โบนัสช่วงเร่งด่วน */}
          <View style={[styles.peak, { borderTopColor: colors.divider }]}>
            <View style={styles.bonusHead}>
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>โบนัสช่วงเร่งด่วน</Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  {peakHours ? `ใช้ช่วง ${peakHours}` : 'ใช้ช่วงคนสั่งเยอะ ไรเดอร์ว่างน้อย'}
                </Text>
              </View>
              <Text style={[typography.bodyStrong, { color: colors.goldDeep }]}>{bonusText(draft.rider_bonus_peak)} บาท</Text>
            </View>
            <GoldSlider
              value={draft.rider_bonus_peak}
              max={sliderMax}
              onChange={(v) => update({ rider_bonus_peak: v })}
              disabled={saving}
              accessibilityLabel="โบนัสช่วงเร่งด่วนต่อออเดอร์"
              style={styles.slider}
            />
          </View>

          {/* ส่งฟรี */}
          <Pressable
            onPress={() => {
              selectionHaptic();
              update({ rider_free_delivery: !draft.rider_free_delivery });
            }}
            disabled={saving}
            accessibilityRole="checkbox"
            accessibilityState={{ checked: draft.rider_free_delivery, disabled: saving }}
            accessibilityLabel="รวมค่าส่งไว้ในราคาสินค้า ลูกค้าเห็นป้ายส่งฟรี ร้านเป็นคนจ่ายค่าส่งทั้งหมดแทน"
            style={({ pressed }) => [styles.checkRow, { borderTopColor: colors.divider, opacity: pressed ? 0.75 : 1 }]}
          >
            <View
              style={[
                styles.checkbox,
                {
                  borderColor: draft.rider_free_delivery ? colors.gold : colors.textFaint,
                  backgroundColor: draft.rider_free_delivery ? colors.gold : 'transparent',
                },
              ]}
            >
              {draft.rider_free_delivery && <Icon name="check" size={17} color={colors.textOnGold} weight="bold" />}
            </View>
            <View style={styles.flex}>
              <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>รวมค่าส่งไว้ในราคาสินค้า</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>
                ลูกค้าเห็นป้าย "ส่งฟรี" ร้านเป็นคนจ่ายค่าส่งทั้งหมดแทน (หักจากรายรับของร้าน)
              </Text>
            </View>
          </Pressable>
        </Card3D>

        {/* ---------- ความโปร่งใส ---------- */}
        <View style={styles.honest}>
          <Icon name="shield-check" size={18} color={colors.textMuted} />
          <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>
            ตัวเลขเงินคิดด้วยสูตรที่ตรวจย้อนได้ (ระยะตามถนน + เวลา + ช่วงเวลา + จำนวนไรเดอร์ว่าง) โบนัสไรเดอร์ได้เต็มจำนวน
            AI ช่วยวิเคราะห์และแนะนำเท่านั้น ไม่เปลี่ยนราคาเอง
          </Text>
        </View>
      </>
    );
  };

  const renderBody = () => {
    switch (state.kind) {
      case 'loading':
        return (
          <View style={styles.skeleton}>
            <SkeletonCard lines={4} withTile />
            <SkeletonCard lines={5} />
            <SkeletonCard lines={4} />
          </View>
        );
      case 'not_seller':
        return (
          <EmptyState
            art="store"
            title={kind === 'fresh' ? 'ยังไม่มีร้านในตลาดสด' : 'ยังไม่ได้เปิดร้าน'}
            message="เปิดร้านก่อน แล้วค่อยตั้งค่าตอบแทนไรเดอร์ได้"
            actionLabel="สมัครเปิดร้าน"
            onAction={() => router.replace(api.setupPath as never)}
          />
        );
      case 'unavailable':
        return (
          <EmptyState
            compact
            icon="hourglass"
            title="ยังตั้งค่าในแอปไม่ได้"
            message="ระบบตั้งค่าตอบแทนไรเดอร์กำลังอัปเดต ลองใหม่อีกครั้งภายหลังนะ"
            actionLabel="ลองใหม่"
            onAction={() => load('initial')}
          />
        );
      case 'error':
        return <EmptyState compact variant="error" message={state.message} onAction={() => load('initial')} />;
      case 'ready':
        return renderReady();
      default:
        return null;
    }
  };

  const ready = state.kind === 'ready' && !!draft;

  return (
    <View style={styles.flex}>
      <Screen
        title={title}
        subtitle={subtitle}
        right={dirty ? <Pill label="ยังไม่บันทึก" tone="warning" /> : undefined}
        refreshing={refreshing}
        onRefresh={saving ? undefined : () => load('refresh')}
        contentStyle={ready ? styles.withBar : undefined}
      >
        {renderBody()}
      </Screen>

      {ready && (
        <StickyBar style={styles.bar}>
          {!!formError && <NoticeBanner tone="danger" text={formError} />}
          <Button3D
            title={dirty ? 'บันทึกการตั้งค่า' : 'บันทึกแล้ว'}
            icon={dirty ? 'check-circle' : 'seal-check'}
            variant={dirty ? 'primary' : 'secondary'}
            size="lg"
            fullWidth
            disabled={!dirty}
            loading={saving}
            loadingText="กำลังบันทึก…"
            onPress={save}
          />
        </StickyBar>
      )}
    </View>
  );
}

/** วันเวลาไทยสั้นๆ */
const formatDate = (iso: string): string => {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return '';
  try {
    return date.toLocaleString('th-TH', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
  } catch {
    return '';
  }
};

/** บรรทัดเหตุผล (ไอคอนเล็ก + ข้อความ) */
const ReasonLine: React.FC<{ icon: 'target' | 'map-pin' | 'clock' | 'chart-bar'; text: string }> = ({ icon, text }) => {
  const { colors } = useTheme();
  return (
    <View style={styles.reasonLine}>
      <Icon name={icon} size={15} color={colors.goldDeep} />
      <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>{text}</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  withBar: {
    paddingBottom: 150,
  },
  skeleton: {
    gap: spacing.lg,
  },
  block: {
    marginBottom: spacing.lg,
  },
  sectionHeader: {
    marginBottom: spacing.sm,
  },
  gapXs: {
    marginTop: spacing.xs,
  },
  adviceHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  adviceHeadline: {
    marginBottom: spacing.xs,
  },
  adviceActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.lg,
  },
  reason: {
    marginTop: spacing.md,
    borderRadius: radii.md,
    borderWidth: 1,
    padding: spacing.md,
    gap: spacing.sm,
  },
  reasonLine: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
  },
  footnote: {
    marginTop: spacing.sm,
  },
  bonusHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  slider: {
    marginTop: spacing.sm,
  },
  callout: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.md,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
  peak: {
    borderTopWidth: 1,
    marginTop: spacing.lg,
    paddingTop: spacing.lg,
  },
  checkRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderTopWidth: 1,
    marginTop: spacing.lg,
    paddingTop: spacing.lg,
    minHeight: 48,
  },
  checkbox: {
    width: 28,
    height: 28,
    borderRadius: 9,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  honest: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    paddingHorizontal: spacing.xs,
    marginBottom: spacing.lg,
  },
  bar: {
    gap: spacing.xs,
  },
});
