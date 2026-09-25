/**
 * ร้านตลาดสดของฉัน (โหมดคนขาย) — รถเข็น / ตลาดนัด / ร้านอาหารในตลาดสด
 *
 * - การ์ดสถานะร้าน: "เปิดร้านที่นี่วันนี้" (ขอสิทธิ์ตำแหน่งแบบ prominent disclosure ก่อนกล่องของระบบ)
 *   → GPS → ชื่อจุดขาย → เวลาปิดอัตโนมัติ → แชร์ตำแหน่งสด (ส่งทุก 30 วิ เฉพาะตอนเปิดแอป)
 *   ความยินยอมของร้านเก็บต่อเครื่องต่อบัญชี — เริ่มแชร์สดเองอัตโนมัติเฉพาะเครื่องที่เคยยินยอมแล้ว
 * - สวิตช์แชร์ตำแหน่งสด: ล็อกทันทีที่แตะ (รวมช่วงรอคำอธิบาย/GPS) + เช็คสถานะร้านล่าสุดก่อนส่ง
 *   (POST /seller/open ซ้ำ = อัปเดต แต่ถ้าร้านเพิ่งปิดไปจะกลายเป็นเปิดร้านใหม่และแจ้งผู้ติดตามทุกคน)
 * - ปิดร้าน: เตือนถ้ามีออเดอร์ค้าง (ออเดอร์ที่รับแล้วยังทำต่อได้)
 * - ตัวเลขรายได้/ออเดอร์ + ทางไปหน้าออเดอร์และสินค้า + ปุ่มเปิดเว็บสำหรับงานละเอียด
 * - รีเฟรชเงียบๆ ทุก 30 วินาทีเฉพาะตอนเปิดหน้านี้ + รีเฟรชทันทีเมื่อมีแจ้งเตือนออเดอร์/ร้านปิดอัตโนมัติ
 * - ยังไม่มีร้านตลาดสด (403 NOT_SELLER) → ชวนสมัครบนเว็บ
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, Text, View } from 'react-native';
import { router, useFocusEffect } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import {
  closeFmShop,
  getFmSellerDashboard,
  getFmSellerPresence,
  openFmShop,
  setFmShopMobile,
  type FmSellerDashboard,
  type OpenShopResult,
  type OwnerPresence,
} from '@/services/api/taladsodSellerApi';
import {
  getShopLiveState,
  pushShopLocationNow,
  startShopLiveSharing,
  stopShopLiveSharing,
  subscribeShopLiveSharing,
  type ShopLiveState,
} from '@/services/taladsodLiveLocation';
import { getCurrentCoords } from '@/services/location';
import { addNotificationReceivedListener } from '@/services/notifications';
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
  openWebsite,
  resultHaptic,
} from '@/components/ui';
import {
  FM_ACTIVE_STATUSES,
  MerchantModeSwitch,
  OpenShopSheet,
  PresenceCard,
  hasShopLocationConsent,
  useShopLocationConsent,
} from '@/components/merchant';
import { useTheme, radii, spacing, typography, type Tone } from '@/theme';

const POLL_MS = 30_000;
const NOTICE_MS = 5_000;

type LoadState =
  | { kind: 'loading' }
  | { kind: 'not_seller' }
  | { kind: 'error'; message: string }
  | { kind: 'ready'; dashboard: FmSellerDashboard; presence: OwnerPresence };

type Notice = { tone: Tone; text: string } | null;

/** ต้องใช้ GPS เมื่อเป็นร้านเคลื่อนที่ หรือร้านยังไม่มีที่อยู่ประจำ */
const needsGpsFor = (p: OwnerPresence) => p.is_mobile || !p.has_fixed_location;

export default function TaladsodSellerScreen() {
  const { colors } = useTheme();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [state, setState] = useState<LoadState>({ kind: 'loading' });
  const [refreshing, setRefreshing] = useState(false);
  const [bannerKey, setBannerKey] = useState(0);
  const [live, setLive] = useState<ShopLiveState>(getShopLiveState());
  const [sheet, setSheet] = useState<'open' | 'update' | null>(null);
  const [switching, setSwitching] = useState(false);
  const [notice, setNotice] = useState<Notice>(null);

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);
  const actionBusyRef = useRef(false);
  const noticeTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const consent = useShopLocationConsent();

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      if (noticeTimerRef.current) clearTimeout(noticeTimerRef.current);
    };
  }, []);

  const showNotice = useCallback((tone: Tone, text: string) => {
    if (!mountedRef.current) return;
    if (noticeTimerRef.current) clearTimeout(noticeTimerRef.current);
    setNotice({ tone, text });
    noticeTimerRef.current = setTimeout(() => {
      if (mountedRef.current) setNotice(null);
    }, NOTICE_MS);
  }, []);

  // ---------- โหลด ----------
  const load = useCallback(
    async (mode: 'initial' | 'refresh' | 'silent') => {
      if (!isAuthenticated) return;
      const requestId = ++requestIdRef.current;
      if (mode === 'refresh') setRefreshing(true);

      const [dash, presence] = await Promise.all([getFmSellerDashboard(), getFmSellerPresence()]);
      if (!mountedRef.current || requestId !== requestIdRef.current) return;
      setRefreshing(false);

      if (dash.success && presence.success) {
        setState({ kind: 'ready', dashboard: dash.data, presence: presence.data });
        return;
      }
      const failure = !dash.success ? dash : !presence.success ? presence : null;
      if (failure && failure.code === 'NOT_SELLER') {
        stopShopLiveSharing('not_seller');
        setState({ kind: 'not_seller' });
        return;
      }
      // โหลดซ้ำแล้วล้ม → เก็บข้อมูลเดิมไว้ (ไม่ล้างหน้าจอ)
      setState((prev) =>
        prev.kind === 'ready' ? prev : { kind: 'error', message: failure?.message || 'โหลดข้อมูลร้านไม่สำเร็จ' }
      );
    },
    [isAuthenticated]
  );

  const presence = state.kind === 'ready' ? state.presence : null;

  /** แทน presence ด้วยข้อมูลใหม่ (จากผลเปิด/ปิดร้าน) */
  const applyPresence = useCallback((next: OwnerPresence) => {
    setState((prev) => (prev.kind === 'ready' ? { ...prev, presence: next } : prev));
  }, []);

  // ---------- ตัวส่งตำแหน่งสด ----------
  useEffect(() => subscribeShopLiveSharing((s) => mountedRef.current && setLive(s)), []);

  // ร้านหยุดรับตำแหน่งเพราะ server ปิดร้านให้ → โหลดใหม่ + แจ้ง
  const lastStopRef = useRef<string | null>(null);
  useEffect(() => {
    if (live.active || !live.stoppedReason || lastStopRef.current === live.stoppedReason) {
      if (live.active) lastStopRef.current = null;
      return;
    }
    lastStopRef.current = live.stoppedReason;
    if (live.stoppedReason === 'shop_closed') {
      showNotice('warning', 'ร้านปิดแล้ว จึงหยุดแชร์ตำแหน่ง กดเปิดร้านอีกครั้งเมื่อพร้อมขาย');
      load('silent');
    } else if (live.stoppedReason === 'permission') {
      showNotice('danger', 'หยุดแชร์ตำแหน่งเพราะยังไม่ได้สิทธิ์ตำแหน่ง');
    }
  }, [live.active, live.stoppedReason, load, showNotice]);

  // ให้ตัวส่งตรงกับสถานะร้านเสมอ
  // เริ่มเองเฉพาะเครื่องที่เคยยอมรับคำอธิบายของร้านแล้ว + มีสิทธิ์ตำแหน่ง (ไม่เด้งคำอธิบาย/กล่องขอสิทธิ์เอง)
  // เครื่องที่ยังไม่เคยยินยอม → การ์ดร้านแสดงปุ่ม "เริ่มส่งตำแหน่งจากเครื่องนี้" (ผ่านคำอธิบายก่อน)
  const presenceKey = presence
    ? `${presence.is_open}-${presence.is_mobile}-${presence.live_location_sharing}-${presence.has_fixed_location}`
    : 'none';
  useEffect(() => {
    if (!presence) return;
    const shouldShare = presence.is_open && needsGpsFor(presence) && presence.live_location_sharing;
    if (!shouldShare) {
      if (getShopLiveState().active) stopShopLiveSharing('manual');
      return;
    }
    if (getShopLiveState().active) return;
    hasShopLocationConsent()
      .then((ok) => {
        if (ok && mountedRef.current && !getShopLiveState().active) startShopLiveSharing();
      })
      .catch(() => {});
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [presenceKey]);

  // ---------- เปิดหน้า: โหลด + รีเฟรชเงียบ + ฟังแจ้งเตือน ----------
  useFocusEffect(
    useCallback(() => {
      load(state.kind === 'ready' ? 'silent' : 'initial');
      const timer = setInterval(() => load('silent'), POLL_MS);
      const sub = addNotificationReceivedListener((notification) => {
        const data = notification?.request?.content?.data as Record<string, unknown> | undefined;
        const type = typeof data?.type === 'string' ? data.type : '';
        if (type === 'fresh_market_order' || type === 'fresh_market_shop') load('silent');
      });
      return () => {
        clearInterval(timer);
        sub.remove();
      };
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [load])
  );

  const onRefresh = async () => {
    setBannerKey((k) => k + 1);
    await load('refresh');
  };

  // =====================================================
  // การกระทำ
  // =====================================================

  const handleOpen = async () => {
    if (!presence || actionBusyRef.current) return;
    if (needsGpsFor(presence)) {
      const ok = await consent.ensure();
      if (!ok) {
        if (mountedRef.current) {
          showNotice('warning', 'ร้านเคลื่อนที่ต้องใช้ตำแหน่งเพื่อเปิดร้าน อนุญาตตำแหน่งแล้วลองใหม่นะ');
        }
        return;
      }
    }
    if (mountedRef.current) setSheet('open');
  };

  const handleUpdate = async () => {
    if (!presence || actionBusyRef.current) return;
    if (needsGpsFor(presence) && !(await consent.ensure())) {
      if (mountedRef.current) showNotice('warning', 'ต้องอนุญาตตำแหน่งก่อนย้ายจุดขาย');
      return;
    }
    if (mountedRef.current) setSheet('update');
  };

  const handleSheetDone = (result: OpenShopResult, message: string) => {
    setSheet(null);
    applyPresence(result);
    if (result.is_open && needsGpsFor(result) && result.live_location_sharing) {
      startShopLiveSharing();
    } else {
      stopShopLiveSharing('manual');
    }
    showNotice('success', message);
    load('silent');
  };

  const activeOrders =
    state.kind === 'ready'
      ? FM_ACTIVE_STATUSES.reduce((sum, s) => sum + (state.dashboard.stats.orders_by_status[s] || 0), 0)
      : 0;

  const doClose = async () => {
    if (actionBusyRef.current) return;
    actionBusyRef.current = true;
    setSwitching(true);
    const result = await closeFmShop();
    actionBusyRef.current = false;
    if (!mountedRef.current) return;
    setSwitching(false);
    if (result.success) {
      stopShopLiveSharing('manual');
      applyPresence(result.data);
      resultHaptic('success');
      showNotice(result.data.active_orders > 0 ? 'warning' : 'success', result.message || 'ปิดร้านแล้ว');
      load('silent');
    } else {
      resultHaptic('error');
      Alert.alert('ปิดร้านไม่สำเร็จ', result.message);
    }
  };

  const handleClose = () => {
    if (!presence || actionBusyRef.current) return;
    const message =
      activeOrders > 0
        ? `ยังมีออเดอร์ที่ทำไม่เสร็จ ${activeOrders} รายการ\nออเดอร์ที่รับแล้วยังทำต่อได้ตามปกติ แต่ลูกค้าใหม่จะสั่งไม่ได้จนกว่าจะเปิดร้านอีกครั้ง`
        : 'ลูกค้าจะเห็นร้านเป็น "ปิดอยู่" และตำแหน่งร้านจะถูกซ่อน';
    Alert.alert('ปิดร้านตอนนี้?', message, [
      { text: 'ยังไม่ปิด', style: 'cancel' },
      { text: 'ปิดร้าน', style: 'destructive', onPress: () => doClose() },
    ]);
  };

  /**
   * เปิด/ปิดแชร์ตำแหน่งสดระหว่างร้านเปิด (POST /seller/open ซ้ำ = อัปเดต — ร้านเคลื่อนที่ต้องส่งพิกัดทุกครั้ง)
   *
   * - ล็อกทันทีที่แตะ (ก่อนรอคำอธิบาย/GPS ที่อาจนานถึง 10 วิ) → กดซ้ำ / กด "ปิดร้าน" แทรกไม่ได้
   * - เช็คสถานะร้านล่าสุดก่อนส่ง: ร้านปิดไปแล้ว (ปิดอัตโนมัติ/ปิดจากเครื่องอื่น) → ไม่ส่ง
   *   เพราะ /seller/open กับร้านที่ปิดอยู่ = เปิดร้านใหม่ + แจ้งผู้ติดตามทุกคน ซึ่งผู้ขายไม่ได้ตั้งใจ
   */
  const handleToggleLive = async (next: boolean) => {
    if (!presence || actionBusyRef.current) return;
    actionBusyRef.current = true;
    setSwitching(true);
    try {
      let point: { latitude: number; longitude: number } | null = null;
      if (next) {
        if (!(await consent.ensure())) return;
        if (!mountedRef.current) return;
        point = await getCurrentCoords({ accuracy: 'high', timeoutMs: 10_000 });
      } else if (presence.location) {
        point = { latitude: presence.location.latitude, longitude: presence.location.longitude };
      } else {
        point = await getCurrentCoords({ timeoutMs: 8_000 });
      }
      if (!mountedRef.current) return;
      if (!point) {
        Alert.alert('ยังหาตำแหน่งไม่เจอ', 'เปิด GPS ของเครื่องแล้วลองใหม่อีกครั้งนะ');
        return;
      }

      // สถานะร้านบนจออาจเก่าได้ถึง 30 วิ → ถาม server ก่อนว่าร้านยังเปิดอยู่จริง
      const fresh = await getFmSellerPresence();
      if (!mountedRef.current) return;
      if (!fresh.success) {
        resultHaptic('error');
        Alert.alert('เปลี่ยนไม่สำเร็จ', fresh.message);
        return;
      }
      if (!fresh.data.is_open) {
        applyPresence(fresh.data);
        stopShopLiveSharing('manual');
        resultHaptic('warning');
        showNotice('warning', 'ร้านปิดไปแล้ว (ถึงเวลาปิดหรือปิดจากเครื่องอื่น) จึงไม่ได้แชร์ตำแหน่ง กด "เปิดร้าน" เมื่อพร้อมขาย');
        load('silent');
        return;
      }

      const result = await openFmShop({ latitude: point.latitude, longitude: point.longitude, live_location_sharing: next });
      if (!mountedRef.current) return;
      if (result.success) {
        applyPresence(result.data);
        if (next) startShopLiveSharing();
        else stopShopLiveSharing('manual');
        resultHaptic('success');
        if (result.data.just_opened) {
          // กันไว้อีกชั้น: ร้านปิดไประหว่างเช็คกับส่ง → server เปิดร้านใหม่ บอกผู้ขายให้ชัด
          showNotice('warning', 'ร้านเพิ่งปิดไปก่อนหน้า ระบบจึงเปิดร้านให้ใหม่และแจ้งผู้ติดตามแล้ว ถ้ายังไม่ขาย กด "ปิดร้าน" ได้เลย');
        } else {
          showNotice('success', next ? 'เริ่มแชร์ตำแหน่งสดแล้ว (เฉพาะตอนเปิดแอป)' : 'หยุดแชร์ตำแหน่งสดแล้ว ลูกค้าเห็นร้านตรงจุดล่าสุด');
        }
        load('silent');
      } else {
        resultHaptic('error');
        Alert.alert('เปลี่ยนไม่สำเร็จ', result.message);
        if (result.code === 'SHOP_CLOSED') load('silent');
      }
    } finally {
      actionBusyRef.current = false;
      if (mountedRef.current) setSwitching(false);
    }
  };

  const handleResumeLive = async () => {
    if (!(await consent.ensure())) return;
    startShopLiveSharing();
    pushShopLocationNow().catch(() => {});
  };

  const doToggleMobile = async (next: boolean) => {
    if (actionBusyRef.current) return;
    actionBusyRef.current = true;
    setSwitching(true);
    const result = await setFmShopMobile(next);
    actionBusyRef.current = false;
    if (!mountedRef.current) return;
    setSwitching(false);
    if (result.success) {
      if (!next) stopShopLiveSharing('manual');
      resultHaptic('success');
      showNotice('success', next ? 'เปลี่ยนเป็นร้านเคลื่อนที่แล้ว กดเปิดร้านเมื่อถึงจุดขาย' : 'เปลี่ยนเป็นร้านประจำที่แล้ว');
      load('silent');
    } else {
      Alert.alert('เปลี่ยนไม่สำเร็จ', result.message);
    }
  };

  const handleToggleMobile = (next: boolean) => {
    if (!presence || actionBusyRef.current) return;
    if (!next && !presence.has_fixed_location) {
      Alert.alert(
        'ยังไม่มีที่อยู่ร้านประจำ',
        'ตั้งที่อยู่และปักหมุดร้านบนเว็บไซต์ก่อน แล้วค่อยเปลี่ยนเป็นร้านประจำที่นะ',
        [
          { text: 'ไว้ก่อน', style: 'cancel' },
          { text: 'ตั้งค่าบนเว็บ', onPress: () => openWebsite('/taladsod/seller/profile') },
        ]
      );
      return;
    }
    Alert.alert(
      next ? 'เปลี่ยนเป็นร้านเคลื่อนที่?' : 'เปลี่ยนเป็นร้านประจำที่?',
      next
        ? 'เหมาะกับรถเข็นหรือร้านตลาดนัด ร้านจะปิดไว้ก่อน แล้วคุณกด "เปิดร้านที่นี่วันนี้" ที่จุดขายได้เลย'
        : 'ร้านจะเปิดที่ที่อยู่ร้านที่ลงทะเบียนไว้ และหยุดแชร์ตำแหน่งสด',
      [
        { text: 'ยกเลิก', style: 'cancel' },
        { text: 'เปลี่ยน', onPress: () => doToggleMobile(next) },
      ]
    );
  };

  // =====================================================
  // แสดงผล
  // =====================================================

  if (!isAuthenticated) {
    return (
      <Screen title="ร้านตลาดสดของฉัน" scroll={false}>
        <EmptyState
          icon="🔐"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อเปิดร้านและดูออเดอร์ตลาดสด"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </Screen>
    );
  }

  const renderBody = () => {
    switch (state.kind) {
      case 'loading':
        return <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />;

      case 'not_seller':
        return (
          <Card3D gradientBorder padding={spacing.xl}>
            <Text style={[typography.h2, { color: colors.textStrong }]}>ยังไม่มีร้านในตลาดสด</Text>
            <Text style={[typography.body, styles.lead, { color: colors.textMuted }]}>
              ขายของสด อาหารทำตามสั่ง รถเข็น หรือร้านตลาดนัดก็ได้ สมัครบนเว็บไซต์ไม่กี่นาที แล้วกลับมาเปิดร้านและรับออเดอร์ในแอปนี้
            </Text>
            <WebsiteButton
              path="/taladsod/register-seller"
              label="สมัครขายในตลาดสด"
              icon="🥬"
              variant="primary"
              size="lg"
              fullWidth
              style={styles.cta}
            />
            <WebsiteButton path="/taladsod/start/seller" label="อ่านรายละเอียดก่อน" variant="ghost" size="md" fullWidth style={styles.gapTop} />
          </Card3D>
        );

      case 'error':
        return <EmptyState compact variant="error" message={state.message} onAction={() => load('initial')} />;

      case 'ready': {
        const { dashboard, presence: p } = state;
        const stats = dashboard.stats;
        return (
          <>
            {/* ---------- ร้าน ---------- */}
            <View style={styles.shopRow}>
              <View style={styles.flex}>
                <Text style={[typography.h2, { color: colors.textStrong }]} numberOfLines={1}>
                  {dashboard.shop_name}
                </Text>
                <View style={styles.pills}>
                  {dashboard.is_verified && <Pill label="ร้านยืนยันแล้ว" tone="success" icon="✔" />}
                  {!!dashboard.status_label && <Pill label={dashboard.status_label} tone="neutral" />}
                </View>
              </View>
            </View>

            {!!notice && (
              <Card3D variant="flat" padding={spacing.md} style={styles.notice}>
                <Text
                  accessibilityLiveRegion="polite"
                  style={[
                    typography.bodySm,
                    {
                      color:
                        notice.tone === 'danger'
                          ? colors.danger
                          : notice.tone === 'warning'
                            ? colors.warning
                            : colors.success,
                    },
                  ]}
                >
                  {notice.text}
                </Text>
              </Card3D>
            )}

            <PresenceCard
              presence={p}
              live={live}
              needsGps={needsGpsFor(p)}
              activeOrders={activeOrders}
              onOpen={handleOpen}
              onUpdate={handleUpdate}
              onClose={handleClose}
              onToggleLive={handleToggleLive}
              onToggleMobile={handleToggleMobile}
              onResumeLive={handleResumeLive}
              switching={switching}
            />

            {!dashboard.is_visible_to_buyers && (
              <Card3D variant="inset" padding={spacing.md} style={styles.warning} contentStyle={styles.warningContent}>
                <Text style={[typography.bodyStrong, { color: colors.warning }]}>⚠️ ลูกค้ายังมองไม่เห็นร้าน</Text>
                <Text style={[typography.bodySm, { color: colors.text }]}>
                  สถานะร้าน: {dashboard.status_label || '-'} ดูรายละเอียดและสิ่งที่ต้องทำบนเว็บไซต์ได้เลย
                </Text>
                <WebsiteButton path="/taladsod/seller/profile" label="ดูบนเว็บไซต์" size="sm" style={styles.warningCta} />
              </Card3D>
            )}
            {dashboard.outstanding_gp_debt > 0 && (
              <Card3D variant="inset" padding={spacing.md} style={styles.warning} contentStyle={styles.warningContent}>
                <Text style={[typography.bodyStrong, { color: colors.warning }]}>ค่าธรรมเนียม GP ค้างชำระ</Text>
                <PriceText amount={dashboard.outstanding_gp_debt} size="md" tone="strong" />
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  จากออเดอร์เก็บเงินปลายทาง ระบบหักจากกระเป๋าเงินเมื่อมียอดเพียงพอ
                </Text>
              </Card3D>
            )}

            {/* ---------- ตัวเลข ---------- */}
            <SectionHeader title="สรุปร้าน" icon="📊" style={styles.section} />
            <View style={styles.grid}>
              <StatTile
                label="ออเดอร์ใหม่รอรับ"
                value={stats.pending_orders}
                icon="🔔"
                tone={stats.pending_orders > 0 ? 'warning' : 'neutral'}
                caption={stats.pending_orders > 0 ? 'แตะเพื่อรับออเดอร์' : undefined}
                style={styles.tile}
                onPress={() => router.push('/merchant/taladsod/orders?status=pending' as never)}
              />
              <StatTile
                label="กำลังทำ"
                value={activeOrders - stats.pending_orders > 0 ? activeOrders - stats.pending_orders : 0}
                icon="🍳"
                tone="gold"
                style={styles.tile}
                onPress={() => router.push('/merchant/taladsod/orders' as never)}
              />
              <StatTile
                label="รายรับสุทธิสะสม"
                value={<PriceText amount={stats.total_revenue} size="lg" tone="success" />}
                icon="💰"
                tone="success"
                caption={`จาก ${stats.total_sales.toLocaleString('th-TH')} ออเดอร์ที่สำเร็จ`}
                style={styles.tile}
              />
              <StatTile
                label="คะแนนร้าน"
                value={stats.rating_count > 0 ? `${stats.rating_average.toFixed(1)} ⭐` : '-'}
                icon="🏅"
                tone="info"
                caption={stats.rating_count > 0 ? `${stats.rating_count.toLocaleString('th-TH')} รีวิว` : 'ยังไม่มีรีวิว'}
                style={styles.tile}
              />
            </View>

            {/* ---------- ทางลัด ---------- */}
            <View style={styles.navRow}>
              <Card3D
                onPress={() => router.push('/merchant/taladsod/orders' as never)}
                padding={spacing.md}
                radius={radii.lg}
                style={styles.flex}
                accessibilityLabel={`ออเดอร์ร้าน ${stats.pending_orders > 0 ? `มีออเดอร์ใหม่ ${stats.pending_orders} รายการ` : ''}`}
              >
                <Text style={styles.navIcon}>🧾</Text>
                <Text style={[typography.h3, { color: colors.textStrong }]}>ออเดอร์</Text>
                <Text style={[typography.caption, { color: stats.pending_orders > 0 ? colors.warning : colors.textMuted }]}>
                  {stats.pending_orders > 0 ? `ใหม่ ${stats.pending_orders} รายการ` : 'รับ เตรียม ส่งของ'}
                </Text>
              </Card3D>
              <Card3D
                onPress={() => router.push('/merchant/taladsod/listings' as never)}
                padding={spacing.md}
                radius={radii.lg}
                style={styles.flex}
                accessibilityLabel="สินค้าของร้าน"
              >
                <Text style={styles.navIcon}>🥬</Text>
                <Text style={[typography.h3, { color: colors.textStrong }]}>สินค้า</Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  {stats.total_listings.toLocaleString('th-TH')} รายการ · ราคา ตัวเลือก
                </Text>
              </Card3D>
            </View>

            {/* ---------- เว็บ ---------- */}
            <SectionHeader title="จัดการละเอียดบนเว็บไซต์" icon="🌐" subtitle="ลงขายสินค้าใหม่ ดูรายได้ย้อนหลัง ตั้งค่าร้าน" style={styles.section} />
            <Card3D padding={spacing.lg}>
              <WebsiteButton path="/taladsod/seller-dashboard" label="เปิดหน้าร้านบนเว็บไซต์" icon="🛠️" variant="primary" fullWidth />
              <View style={styles.webRow}>
                <WebsiteButton path="/taladsod/create-listing" label="ลงขายสินค้าใหม่" icon="➕" size="sm" style={styles.flex} />
                <WebsiteButton path="/taladsod/seller/earnings" label="ดูรายได้" icon="📈" size="sm" style={styles.flex} />
              </View>
              <Text style={[typography.caption, styles.webNote, { color: colors.textMuted }]}>
                เปิดในเบราว์เซอร์และเข้าสู่ระบบให้อัตโนมัติ
              </Text>
            </Card3D>
          </>
        );
      }
      default:
        return null;
    }
  };

  return (
    <Screen title="ร้านตลาดสดของฉัน" subtitle="เปิดร้าน รับออเดอร์ จัดการสินค้า" refreshing={refreshing} onRefresh={onRefresh}>
      <MerchantModeSwitch
        current="taladsod"
        taladsodBadge={state.kind === 'ready' ? state.dashboard.stats.pending_orders : undefined}
      />
      <View style={styles.bannerWrap}>
        <BannerSlider placement="merchant" height={132} autoPlay={false} refreshKey={bannerKey} />
      </View>
      {renderBody()}
      {state.kind === 'ready' && (
        <Button3D title="ช่วยเหลือร้านค้า" variant="ghost" size="sm" onPress={() => router.push('/support')} style={styles.help} />
      )}

      {presence && (
        <OpenShopSheet
          visible={sheet !== null}
          mode={sheet === 'update' ? 'update' : 'open'}
          presence={presence}
          needsGps={needsGpsFor(presence)}
          onClose={() => setSheet(null)}
          onDone={handleSheetDone}
        />
      )}
      {consent.element}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  bannerWrap: {
    marginHorizontal: -spacing.screen,
    marginBottom: spacing.lg,
  },
  lead: {
    marginTop: spacing.sm,
  },
  cta: {
    marginTop: spacing.xl,
  },
  gapTop: {
    marginTop: spacing.sm,
  },
  shopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: spacing.md,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  notice: {
    marginBottom: spacing.md,
  },
  warning: {
    marginTop: spacing.md,
  },
  warningContent: {
    gap: spacing.xs,
  },
  warningCta: {
    marginTop: spacing.sm,
    alignSelf: 'flex-start',
  },
  section: {
    marginTop: spacing.xl,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    rowGap: spacing.md,
  },
  tile: {
    width: '48%',
  },
  navRow: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.md,
  },
  navIcon: {
    fontSize: 26,
    marginBottom: spacing.xs,
  },
  webRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  webNote: {
    marginTop: spacing.sm,
    textAlign: 'center',
  },
  help: {
    marginTop: spacing.lg,
    alignSelf: 'center',
  },
});
