/**
 * ตั้งค่าไรเดอร์ — ยานพาหนะ เบอร์ติดต่อ และงานที่อยากรับ (แทนหน้าเว็บ /user/rider/settings)
 *
 * - GET /rider/status → เติมฟอร์ม · PUT /rider/profile ส่ง "ทุกช่อง" ทุกครั้ง
 *   (server ล้างความชอบงานที่ไม่ได้ส่งมา เหมือนฟอร์มเว็บ — server รุ่นเก่าที่ยังไม่ส่งค่าความชอบงานกลับมา
 *    → ไม่ให้บันทึกจากแอป กันค่าบนเว็บถูกล้างโดยไม่ตั้งใจ)
 * - กฎเดียวกับ server: เบอร์ 0 + 8–9 หลัก · ทะเบียนบังคับเมื่อมอเตอร์ไซค์/รถยนต์ · รัศมี 1–50 กม. · ค่าส่งขั้นต่ำ 0–1,000
 * - มีงานค้างอยู่ → เปลี่ยนยานพาหนะไม่ได้ (409 HAS_ACTIVE_JOB)
 * - ไรเดอร์ที่อนุมัติแล้วเปลี่ยนยานพาหนะ → ถามยืนยันก่อน (ต้องรอทีมงานตรวจเอกสารใหม่ ระหว่างนั้นรับงานไม่ได้)
 * - ยังไม่บันทึกแล้วจะออก → ถามก่อนทิ้งการแก้ไข · ดึงลงเพื่อรีเฟรชไม่ทับสิ่งที่กำลังแก้
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, Pressable, StyleSheet, Switch, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { router, useNavigation } from 'expo-router';
import { usePreventRemove } from 'expo-router/react-navigation';
import { useAuthStore } from '@/stores/authStore';
import {
  getRiderStatus,
  updateRiderProfile,
  type RiderJobType,
  type RiderProfileBody,
  type RiderStatus,
  type RiderVehicleType,
} from '@/services/api/riderApi';
import {
  Button3D,
  Card3D,
  Chip,
  EmptyState,
  Icon,
  Pill,
  Screen,
  WebsiteButton,
  resultHaptic,
  selectionHaptic,
  type IconName,
} from '@/components/ui';
import { Field, StickyBar } from '@/components/shop';
import { IconTile, NoticeBanner } from '@/components/merchant';
import { SkeletonCard } from '@/components/merchant/SkeletonBlock';
import { VEHICLES, digitsOnly, isValidThaiPhone } from '@/components/rider/riderHelpers';
import { useTheme, radii, spacing, typography } from '@/theme';

const JOB_TYPES: Array<{ value: RiderJobType; label: string; icon: IconName }> = [
  { value: 'delivery', label: 'ส่งของทุกประเภท', icon: 'package' },
  { value: 'fresh_market', label: 'ส่งของตลาดสด', icon: 'basket' },
  { value: 'shop_delivery', label: 'ส่งสินค้าร้านค้า', icon: 'storefront' },
  { value: 'food', label: 'ส่งอาหาร', icon: 'bowl-food' },
  { value: 'document', label: 'ส่งเอกสาร', icon: 'file-text' },
];
const RADIUS_MIN = 1;
const RADIUS_MAX = 50;
const RADIUS_QUICK = [3, 5, 10, 20];

interface SettingsDraft {
  phone: string;
  vehicle_type: RiderVehicleType;
  vehicle_plate: string;
  vehicle_brand: string;
  vehicle_color: string;
  useRadius: boolean;
  radius: number;
  minFeeText: string;
  jobTypes: RiderJobType[];
}

type DraftErrors = Partial<Record<'phone' | 'vehicle_plate' | 'vehicle_brand' | 'vehicle_color' | 'radius' | 'minFeeText' | 'jobTypes', string>>;

const draftFrom = (r: RiderStatus): SettingsDraft => ({
  phone: r.phone || '',
  vehicle_type: r.vehicle_type || 'motorcycle',
  vehicle_plate: r.vehicle_plate || '',
  vehicle_brand: r.vehicle_brand || '',
  vehicle_color: r.vehicle_color || '',
  useRadius: typeof r.preferred_radius_km === 'number' && r.preferred_radius_km > 0,
  radius:
    typeof r.preferred_radius_km === 'number' && r.preferred_radius_km > 0
      ? Math.min(RADIUS_MAX, Math.max(RADIUS_MIN, Math.round(r.preferred_radius_km)))
      : 5,
  minFeeText:
    typeof r.preferred_min_fee === 'number' && r.preferred_min_fee > 0
      ? Number.isInteger(r.preferred_min_fee)
        ? String(r.preferred_min_fee)
        : r.preferred_min_fee.toFixed(2)
      : '',
  jobTypes: (r.preferred_job_types || []).filter((t): t is RiderJobType => JOB_TYPES.some((j) => j.value === t)),
});

const serialize = (d: SettingsDraft): string =>
  JSON.stringify([
    digitsOnly(d.phone),
    d.vehicle_type,
    d.vehicle_plate.trim(),
    d.vehicle_brand.trim(),
    d.vehicle_color.trim(),
    d.useRadius ? d.radius : null,
    d.minFeeText.trim(),
    [...d.jobTypes].sort(),
  ]);

const needsPlate = (type: RiderVehicleType) => VEHICLES.find((v) => v.value === type)?.needsPlate ?? false;

const validate = (d: SettingsDraft): DraftErrors => {
  const e: DraftErrors = {};
  if (!isValidThaiPhone(d.phone)) e.phone = 'เบอร์โทรศัพท์ไม่ถูกต้อง (ตัวเลข 9-10 หลัก ขึ้นต้นด้วย 0)';
  if (needsPlate(d.vehicle_type) && !d.vehicle_plate.trim()) e.vehicle_plate = 'กรุณากรอกทะเบียนรถ';
  if (d.vehicle_plate.trim().length > 20) e.vehicle_plate = 'ทะเบียนรถยาวได้ไม่เกิน 20 ตัวอักษร';
  if (d.vehicle_brand.trim().length > 100) e.vehicle_brand = 'ยาวได้ไม่เกิน 100 ตัวอักษร';
  if (d.vehicle_color.trim().length > 50) e.vehicle_color = 'ยาวได้ไม่เกิน 50 ตัวอักษร';
  if (d.useRadius && (d.radius < RADIUS_MIN || d.radius > RADIUS_MAX)) e.radius = `รัศมีต้องอยู่ระหว่าง ${RADIUS_MIN}–${RADIUS_MAX} กม.`;
  if (d.minFeeText.trim() !== '') {
    const fee = Number(d.minFeeText.trim());
    if (!/^\d+(\.\d{1,2})?$/.test(d.minFeeText.trim()) || !Number.isFinite(fee) || fee < 0 || fee > 1000) {
      e.minFeeText = 'ค่าส่งขั้นต่ำต้องเป็นตัวเลข 0 – 1,000 บาท';
    }
  }
  return e;
};

const toBody = (d: SettingsDraft): RiderProfileBody => {
  const clean = (v: string) => v.trim();
  const body: RiderProfileBody = {
    phone: digitsOnly(d.phone),
    vehicle_type: d.vehicle_type,
    // ส่งทุกช่องเสมอ (ว่าง = ลบค่าเดิม) — ตรงกับฟอร์มเว็บ
    vehicle_plate: needsPlate(d.vehicle_type) ? clean(d.vehicle_plate) : clean(d.vehicle_plate) || undefined,
    vehicle_brand: d.vehicle_type === 'walk' ? undefined : clean(d.vehicle_brand) || undefined,
    vehicle_color: d.vehicle_type === 'walk' ? undefined : clean(d.vehicle_color) || undefined,
    preferred_job_types: d.jobTypes,
    preferred_radius_km: d.useRadius ? d.radius : null,
    preferred_min_fee: d.minFeeText.trim() !== '' ? Number(d.minFeeText.trim()) : null,
  };
  return body;
};

type LoadState = { kind: 'loading' } | { kind: 'not_rider' } | { kind: 'error'; message: string } | { kind: 'ready' };

export default function RiderSettingsScreen() {
  const { colors } = useTheme();
  const navigation = useNavigation();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [state, setState] = useState<LoadState>({ kind: 'loading' });
  const [rider, setRider] = useState<RiderStatus | null>(null);
  const [draft, setDraft] = useState<SettingsDraft | null>(null);
  const [baseline, setBaseline] = useState('');
  const [errors, setErrors] = useState<DraftErrors>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [saving, setSaving] = useState(false);

  const mountedRef = useRef(true);
  const dirtyRef = useRef(false);
  const noticeTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      if (noticeTimerRef.current) clearTimeout(noticeTimerRef.current);
    };
  }, []);

  const flash = useCallback((text: string) => {
    if (!mountedRef.current) return;
    if (noticeTimerRef.current) clearTimeout(noticeTimerRef.current);
    setNotice(text);
    noticeTimerRef.current = setTimeout(() => mountedRef.current && setNotice(null), 4000);
  }, []);

  const apply = (r: RiderStatus) => {
    const d = draftFrom(r);
    setRider(r);
    setDraft(d);
    setBaseline(serialize(d));
    setErrors({});
    setFormError(null);
  };

  const load = useCallback(
    async (mode: 'initial' | 'refresh') => {
      if (!isAuthenticated) return;
      if (mode === 'refresh') setRefreshing(true);
      const res = await getRiderStatus();
      if (!mountedRef.current) return;
      setRefreshing(false);
      if (res.success) {
        if (!res.data.is_rider || !res.data.rider) {
          setState({ kind: 'not_rider' });
          return;
        }
        if (mode === 'initial' || !dirtyRef.current) apply(res.data.rider);
        else setRider(res.data.rider);
        setState({ kind: 'ready' });
      } else if (mode === 'refresh') {
        flash(res.message);
      } else {
        setState({ kind: 'error', message: res.message });
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [isAuthenticated]
  );

  useEffect(() => {
    load('initial');
  }, [load]);

  const dirty = !!draft && serialize(draft) !== baseline;
  dirtyRef.current = dirty;

  usePreventRemove(dirty, ({ data }) => {
    if (saving) {
      Alert.alert('กำลังบันทึก', 'รอสักครู่ ระบบกำลังบันทึกการตั้งค่า');
      return;
    }
    Alert.alert('ยังไม่ได้บันทึก', 'มีการแก้ไขที่ยังไม่บันทึก ออกจากหน้านี้แล้วการแก้ไขจะหายไป', [
      { text: 'อยู่ต่อ', style: 'cancel' },
      { text: 'ทิ้งการแก้ไข', style: 'destructive', onPress: () => navigation.dispatch(data.action) },
    ]);
  });

  const update = (patch: Partial<SettingsDraft>, clear?: keyof DraftErrors) => {
    setDraft((prev) => (prev ? { ...prev, ...patch } : prev));
    setFormError(null);
    if (clear) setErrors((prev) => ({ ...prev, [clear]: undefined }));
  };

  // server รุ่นเก่าไม่ส่งค่าความชอบงานมา → บันทึกจากแอปจะล้างค่าที่ตั้งบนเว็บ
  const prefsSupported = !!rider && Array.isArray(rider.preferred_job_types);
  const isApproved = !!rider && (rider.status === 'approved' || rider.status === 'suspended');
  const hasActiveJob = !!rider?.active_job_id;
  const vehicleChanged = !!rider && !!draft && draft.vehicle_type !== rider.vehicle_type;

  const doSave = async () => {
    if (!draft || saving) return;
    setSaving(true);
    const res = await updateRiderProfile(toBody(draft));
    if (!mountedRef.current) return;
    setSaving(false);
    if (res.success) {
      resultHaptic('success');
      apply(res.data.rider);
      flash(res.message || 'บันทึกการตั้งค่าแล้ว');
      if (res.data.vehicle_changed && res.data.rider.documents_missing.length > 0) {
        Alert.alert('อัปโหลดเอกสารของคันใหม่', res.message || 'เปลี่ยนยานพาหนะแล้ว กรุณาอัปโหลดเอกสารเพิ่มเติม', [
          { text: 'ไว้ก่อน', style: 'cancel' },
          { text: 'อัปโหลดเอกสาร', onPress: () => router.push('/rider-documents' as never) },
        ]);
      }
      return;
    }
    resultHaptic('error');
    const fieldErrors: DraftErrors = {};
    const e = res.errors || {};
    if (e.phone?.[0]) fieldErrors.phone = e.phone[0];
    if (e.vehicle_plate?.[0]) fieldErrors.vehicle_plate = e.vehicle_plate[0];
    if (e.vehicle_brand?.[0]) fieldErrors.vehicle_brand = e.vehicle_brand[0];
    if (e.vehicle_color?.[0]) fieldErrors.vehicle_color = e.vehicle_color[0];
    if (e.preferred_radius_km?.[0]) fieldErrors.radius = e.preferred_radius_km[0];
    if (e.preferred_min_fee?.[0]) fieldErrors.minFeeText = e.preferred_min_fee[0];
    const jobErr = Object.keys(e).find((k) => k.startsWith('preferred_job_types'));
    if (jobErr) fieldErrors.jobTypes = e[jobErr]?.[0];
    setErrors(fieldErrors);
    setFormError(res.message);
    if (res.code === 'HAS_ACTIVE_JOB') load('refresh');
  };

  const save = () => {
    if (!draft || saving || !dirty || !prefsSupported) return;
    const found = validate(draft);
    setErrors(found);
    if (Object.keys(found).length > 0) {
      resultHaptic('warning');
      setFormError('ตรวจช่องที่มีข้อความสีแดงก่อนนะ');
      return;
    }
    if (vehicleChanged && isApproved) {
      Alert.alert(
        'เปลี่ยนยานพาหนะ?',
        `ต้องรอทีมงานตรวจเอกสารใหม่ ระหว่างนั้นรับงานไม่ได้${needsPlate(draft.vehicle_type) ? ' และต้องอัปโหลดใบขับขี่ + ทะเบียนรถของคันใหม่' : ''}`,
        [
          { text: 'ยกเลิก', style: 'cancel' },
          { text: 'เปลี่ยนยานพาหนะ', style: 'destructive', onPress: () => doSave() },
        ]
      );
      return;
    }
    return doSave();
  };

  // =====================================================
  // แสดงผล
  // =====================================================

  if (!isAuthenticated) {
    return (
      <Screen title="ตั้งค่าไรเดอร์" scroll={false}>
        <EmptyState art="scooter" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  const renderBody = () => {
    switch (state.kind) {
      case 'loading':
        return (
          <View style={styles.skeleton}>
            <SkeletonCard lines={4} withTile />
            <SkeletonCard lines={3} withTile />
            <SkeletonCard lines={4} withTile />
          </View>
        );
      case 'not_rider':
        return (
          <EmptyState
            art="scooter"
            title="ยังไม่ได้สมัครเป็นไรเดอร์"
            message="สมัครไรเดอร์ก่อน แล้วค่อยตั้งค่ายานพาหนะและงานที่อยากรับ"
            actionLabel="ไปหน้าไรเดอร์"
            onAction={() => router.replace('/rider' as never)}
          />
        );
      case 'error':
        return <EmptyState compact variant="error" message={state.message} onAction={() => load('initial')} />;
      case 'ready':
        if (!draft || !rider) return null;
        if (!prefsSupported) {
          return (
            <View>
              <EmptyState
                compact
                icon="info"
                title="ตั้งค่านี้ยังใช้ในแอปไม่ได้"
                message="ระบบกำลังอัปเดต ระหว่างนี้ตั้งค่ายานพาหนะและงานที่อยากรับบนเว็บไซต์ได้ก่อน"
              />
              <WebsiteButton path="/user/rider/settings" label="ตั้งค่าไรเดอร์บนเว็บไซต์" icon="sliders-horizontal" variant="navy" fullWidth />
            </View>
          );
        }
        return renderForm(draft, rider);
      default:
        return null;
    }
  };

  const renderForm = (d: SettingsDraft, r: RiderStatus) => (
    <>
      {!!notice && <NoticeBanner tone="success" text={notice} style={styles.block} />}

      {/* ---------- ยานพาหนะและการติดต่อ ---------- */}
      <Card3D padding={spacing.lg}>
        <View style={styles.head}>
          <IconTile icon="moped" />
          <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>ยานพาหนะและการติดต่อ</Text>
        </View>
        <Field
          label="เบอร์โทรที่ลูกค้าและร้านติดต่อได้"
          required
          value={d.phone}
          onChangeText={(t) => update({ phone: t.replace(/[^\d-]/g, '').slice(0, 12) }, 'phone')}
          keyboardType="phone-pad"
          textContentType="telephoneNumber"
          placeholder="08x-xxx-xxxx"
          editable={!saving}
          error={errors.phone}
        />

        <Text style={[typography.caption, styles.label, { color: colors.textMuted }]}>
          ประเภทยานพาหนะ<Text style={{ color: colors.danger }}> *</Text>
        </Text>
        <View style={styles.chips}>
          {VEHICLES.map((v) => {
            const locked = hasActiveJob && v.value !== r.vehicle_type;
            return (
              <Chip
                key={v.value}
                label={v.label}
                icon={v.icon}
                tone="gold"
                selected={d.vehicle_type === v.value}
                disabled={saving || locked}
                onPress={() => {
                  selectionHaptic();
                  update({ vehicle_type: v.value }, 'vehicle_plate');
                }}
              />
            );
          })}
        </View>
        {hasActiveJob && (
          <View style={styles.inlineNote}>
            <Icon name="lock" size={14} color={colors.textMuted} />
            <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>เปลี่ยนยานพาหนะได้หลังส่งงานปัจจุบันเสร็จ</Text>
          </View>
        )}
        {isApproved && vehicleChanged && (
          <NoticeBanner
            tone="warning"
            text={`เปลี่ยนยานพาหนะแล้วต้องรอทีมงานตรวจเอกสารใหม่ ระหว่างนั้นรับงานไม่ได้${needsPlate(d.vehicle_type) ? ' และต้องอัปโหลดใบขับขี่ + ทะเบียนรถของคันใหม่' : ''}`}
            style={styles.block}
          />
        )}

        {needsPlate(d.vehicle_type) && (
          <Field
            label="ทะเบียนรถ"
            required
            value={d.vehicle_plate}
            onChangeText={(t) => update({ vehicle_plate: t }, 'vehicle_plate')}
            placeholder="เช่น 1กข 1234"
            maxLength={20}
            editable={!saving}
            error={errors.vehicle_plate}
          />
        )}
        {d.vehicle_type !== 'walk' && (
          <View style={styles.row}>
            <Field
              label="ยี่ห้อ/รุ่น"
              value={d.vehicle_brand}
              onChangeText={(t) => update({ vehicle_brand: t }, 'vehicle_brand')}
              placeholder="เช่น Honda Wave"
              maxLength={100}
              editable={!saving}
              error={errors.vehicle_brand}
              containerStyle={styles.flex}
            />
            <Field
              label="สีรถ"
              value={d.vehicle_color}
              onChangeText={(t) => update({ vehicle_color: t }, 'vehicle_color')}
              placeholder="เช่น แดง"
              maxLength={50}
              editable={!saving}
              error={errors.vehicle_color}
              containerStyle={styles.flex}
            />
          </View>
        )}
      </Card3D>

      {/* ---------- งานที่อยากรับ ---------- */}
      <Card3D padding={spacing.lg} style={styles.section}>
        <View style={styles.head}>
          <IconTile icon="target" tone="gold" />
          <View style={styles.flex}>
            <Text style={[typography.h3, { color: colors.textStrong }]}>งานที่อยากรับ</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>ระบบเสนองานที่ตรงใจก่อน · ไม่ตั้งค่า = รับทุกงานตามปกติ</Text>
          </View>
        </View>

        <View style={[styles.switchRow, { borderTopColor: colors.divider }]}>
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>กำหนดรัศมีรับงานเอง</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              {d.useRadius ? `รับงานไม่เกิน ${d.radius} กม. จากตำแหน่งคุณ` : 'ใช้รัศมีมาตรฐานของระบบ'}
            </Text>
          </View>
          <Switch
            value={d.useRadius}
            onValueChange={(v) => update({ useRadius: v }, 'radius')}
            disabled={saving}
            trackColor={{ false: colors.border, true: colors.success }}
            thumbColor={colors.card}
            accessibilityLabel="กำหนดรัศมีรับงานเอง"
          />
        </View>
        {d.useRadius && (
          <>
            <View style={styles.stepper}>
              <Pressable
                onPress={() => update({ radius: Math.max(RADIUS_MIN, d.radius - 1) }, 'radius')}
                disabled={saving || d.radius <= RADIUS_MIN}
                accessibilityRole="button"
                accessibilityLabel="ลดรัศมี 1 กิโลเมตร"
                hitSlop={6}
                style={({ pressed }) => [
                  styles.stepButton,
                  { backgroundColor: colors.inset, borderColor: colors.border, opacity: d.radius <= RADIUS_MIN ? 0.4 : pressed ? 0.7 : 1 },
                ]}
              >
                <Icon name="minus" size={20} color={colors.textStrong} weight="bold" />
              </Pressable>
              <View style={styles.stepValue} accessibilityLabel={`รัศมี ${d.radius} กิโลเมตร`}>
                <Text style={[typography.money, { color: colors.goldDeep }]}>{d.radius}</Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>กิโลเมตร</Text>
              </View>
              <Pressable
                onPress={() => update({ radius: Math.min(RADIUS_MAX, d.radius + 1) }, 'radius')}
                disabled={saving || d.radius >= RADIUS_MAX}
                accessibilityRole="button"
                accessibilityLabel="เพิ่มรัศมี 1 กิโลเมตร"
                hitSlop={6}
                style={({ pressed }) => [
                  styles.stepButton,
                  { backgroundColor: colors.inset, borderColor: colors.border, opacity: d.radius >= RADIUS_MAX ? 0.4 : pressed ? 0.7 : 1 },
                ]}
              >
                <Icon name="plus" size={20} color={colors.textStrong} weight="bold" />
              </Pressable>
            </View>
            <View style={[styles.chips, styles.centerChips]}>
              {RADIUS_QUICK.map((km) => (
                <Chip key={km} label={`${km} กม.`} size="sm" selected={d.radius === km} disabled={saving} onPress={() => update({ radius: km }, 'radius')} />
              ))}
            </View>
            {!!errors.radius && <Text style={[typography.caption, styles.errorText, { color: colors.danger }]}>{errors.radius}</Text>}
          </>
        )}

        <Field
          label="ค่าส่งขั้นต่ำที่อยากรับ (บาท)"
          value={d.minFeeText}
          onChangeText={(t) => update({ minFeeText: t.replace(/[^0-9.]/g, '').slice(0, 7) }, 'minFeeText')}
          keyboardType="decimal-pad"
          placeholder="เว้นว่าง = รับทุกราคา"
          editable={!saving}
          error={errors.minFeeText}
        />

        <Text style={[typography.caption, styles.label, { color: colors.textMuted }]}>ประเภทงาน (เลือกได้หลายอย่าง · ไม่เลือก = รับทุกประเภท)</Text>
        <View style={styles.chips}>
          {JOB_TYPES.map((j) => {
            const on = d.jobTypes.includes(j.value);
            return (
              <Chip
                key={j.value}
                label={j.label}
                icon={j.icon}
                size="sm"
                selected={on}
                disabled={saving}
                onPress={() => {
                  selectionHaptic();
                  update({ jobTypes: on ? d.jobTypes.filter((x) => x !== j.value) : [...d.jobTypes, j.value] }, 'jobTypes');
                }}
              />
            );
          })}
        </View>
        {!!errors.jobTypes && <Text style={[typography.caption, styles.errorText, { color: colors.danger }]}>{errors.jobTypes}</Text>}
      </Card3D>

      <View style={[styles.statusRow, { backgroundColor: colors.surface, borderColor: colors.border }]}>
        <Icon name="identification-card" size={16} color={colors.textMuted} />
        <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>
          สถานะบัญชี: {r.status_text} · เอกสาร {r.documents_complete ? 'ครบแล้ว' : `ยังขาด ${r.documents_missing.length} รายการ`}
        </Text>
        <Button3D title="เอกสาร" size="sm" variant="ghost" onPress={() => router.push('/rider-documents' as never)} />
      </View>
    </>
  );

  const ready = state.kind === 'ready' && !!draft && prefsSupported;

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <Screen
        title="ตั้งค่าไรเดอร์"
        subtitle="ยานพาหนะ และงานที่อยากรับ"
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
    </KeyboardAvoidingView>
  );
}

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
    marginTop: spacing.md,
  },
  section: {
    marginTop: spacing.lg,
  },
  head: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  label: {
    marginTop: spacing.lg,
    marginBottom: spacing.xs,
    fontWeight: '600',
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
  },
  centerChips: {
    justifyContent: 'center',
    marginTop: spacing.sm,
  },
  inlineNote: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    marginTop: spacing.sm,
  },
  switchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.lg,
    paddingTop: spacing.lg,
    borderTopWidth: 1,
  },
  stepper: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xl,
    marginTop: spacing.md,
  },
  stepButton: {
    width: 48,
    height: 48,
    borderRadius: radii.md,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepValue: {
    minWidth: 90,
    alignItems: 'center',
  },
  errorText: {
    marginTop: spacing.xs,
  },
  statusRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.lg,
    paddingLeft: spacing.md,
    borderRadius: radii.md,
    borderWidth: 1,
  },
  bar: {
    gap: spacing.xs,
  },
});
