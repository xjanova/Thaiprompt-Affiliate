/**
 * ตั้งค่าร้าน — GET/PUT /seller/store · POST /seller/store/logo|banner
 * (กติกาเดียวกับหน้าเว็บ /seller/store/settings — server ตรวจซ้ำทุกช่อง)
 *
 * - โลโก้/แบนเนอร์: แตะเพื่อเปลี่ยน อัปโหลดทันที (โลโก้ ≤2MB · แบนเนอร์ ≤4MB) ออกจากหน้า = ยกเลิกการอัปโหลด
 * - ข้อมูลร้าน / ที่อยู่ / ธุรกิจและภาษี (VAT ต้องมีเลขผู้เสียภาษี 13 หลัก) / การจัดส่งและชำระเงิน / ช่องทางติดต่อ
 * - ส่งด้วยไรเดอร์: ต้องปักหมุดจุดรับของก่อน — แตะแผนที่/ลากหมุด หรือใช้ตำแหน่งปัจจุบัน (อธิบายเหตุผลก่อนขอสิทธิ์)
 * - บันทึกเฉพาะช่องที่เปลี่ยน · ออกจากหน้าโดยยังไม่บันทึก → ถามก่อน
 * - สี/เลย์เอาต์หน้าร้าน อยู่บนเว็บไซต์
 *
 * หน้าตา: การ์ดแบนเนอร์ + โลโก้ลอย (ชื่อร้านตัวมีเชิง) → การ์ดฟอร์มแต่ละส่วน → แผนที่จุดรับของ → ปุ่มทองในแถบลอยท้ายจอ
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, KeyboardAvoidingView, Platform, Pressable, StyleSheet, View } from 'react-native';
import { Image } from 'expo-image';
import * as Location from 'expo-location';
import { router, useNavigation } from 'expo-router';
import { usePreventRemove } from 'expo-router/react-navigation';
import { Text } from '@/components/ui/Text';
import { useAuthStore } from '@/stores/authStore';
import type { ApiFailure } from '@/services/api/client';
import {
  STORE_IMAGE_MAX_BYTES,
  getSellerStore,
  updateSellerStore,
  uploadSellerStoreImage,
  type SellerBusinessType,
  type SellerStorePatch,
  type SellerStoreSettings,
} from '@/services/api/sellerStoreApi';
import {
  BrandArt,
  Button3D,
  Card3D,
  Chip,
  ConsentSheet,
  EmptyState,
  Icon,
  LiveMap,
  Pill,
  Screen,
  WebsiteButton,
  resultHaptic,
} from '@/components/ui';
import { Field, StickyBar } from '@/components/shop';
import { NoticeBanner } from '@/components/merchant';
import {
  ErrorNote,
  FormCard,
  FormSkeleton,
  SellerGateNotice,
  ToggleRow,
  choosePhotoSource,
  formatInputNumber,
  isGateFailure,
  parseMoney,
  pickImages,
} from '@/components/seller';
import { useTheme, radii, spacing, typography } from '@/theme';

// =====================================================
// ฟอร์ม
// =====================================================

type TextKey =
  | 'store_name'
  | 'store_description'
  | 'store_phone'
  | 'store_email'
  | 'store_address'
  | 'store_city'
  | 'store_state'
  | 'store_postal_code'
  | 'company_name'
  | 'tax_id'
  | 'shipping_fee'
  | 'free_shipping_threshold'
  | 'minimum_order_amount'
  | 'pickup_address'
  | 'facebook_url'
  | 'line_oa_id'
  | 'instagram_url'
  | 'tiktok_url';

interface FormState extends Record<TextKey, string> {
  business_type: SellerBusinessType;
  vat_registered: boolean;
  enable_cod: boolean;
  enable_reviews: boolean;
  rider_delivery_enabled: boolean;
  pickup_latitude: number | null;
  pickup_longitude: number | null;
}

type Errors = Partial<Record<TextKey | 'pickup_latitude', string>>;

const THAI = /[฀-๿]/;
const LOCATION_TIMEOUT_MS = 15000;

const withTimeout = <T,>(promise: Promise<T>, ms: number): Promise<T | null> =>
  Promise.race([promise, new Promise<null>((resolve) => setTimeout(() => resolve(null), ms))]);

const formFromStore = (s: SellerStoreSettings): FormState => ({
  store_name: s.store_name,
  store_description: s.store_description ?? '',
  store_phone: s.store_phone ?? '',
  store_email: s.store_email ?? '',
  store_address: s.store_address ?? '',
  store_city: s.store_city ?? '',
  store_state: s.store_state ?? '',
  store_postal_code: s.store_postal_code ?? '',
  company_name: s.company_name ?? '',
  tax_id: s.tax_id ?? '',
  shipping_fee: formatInputNumber(s.shipping_fee),
  free_shipping_threshold: formatInputNumber(s.free_shipping_threshold),
  minimum_order_amount: formatInputNumber(s.minimum_order_amount),
  pickup_address: s.pickup_address ?? '',
  facebook_url: s.facebook_url ?? '',
  line_oa_id: s.line_oa_id ?? '',
  instagram_url: s.instagram_url ?? '',
  tiktok_url: s.tiktok_url ?? '',
  business_type: s.business_type,
  vat_registered: s.vat_registered,
  enable_cod: s.enable_cod,
  enable_reviews: s.enable_reviews,
  rider_delivery_enabled: s.rider_delivery_enabled,
  pickup_latitude: s.pickup_latitude,
  pickup_longitude: s.pickup_longitude,
});

/** ตรวจก่อนส่ง — กฎเดียวกับ SellerStoreSettingsService */
const validate = (f: FormState): Errors => {
  const e: Errors = {};
  const url = (key: TextKey, label: string) => {
    const v = f[key].trim();
    if (v && !/^https?:\/\/[^\s]+\.[^\s]+/i.test(v)) e[key] = `ลิงก์ ${label} ต้องขึ้นต้นด้วย https://`;
  };
  const money = (key: TextKey, label: string) => {
    const v = parseMoney(f[key]);
    if (v !== null && (Number.isNaN(v) || v > 10_000_000)) e[key] = `${label}ต้องเป็นตัวเลข 0 – 10,000,000`;
  };

  if (!f.store_name.trim()) e.store_name = 'กรุณากรอกชื่อร้าน';
  if (f.store_email.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.store_email.trim())) e.store_email = 'อีเมลร้านไม่ถูกต้อง';
  if (f.store_phone.trim().length > 20) e.store_phone = 'เบอร์โทรร้านต้องไม่เกิน 20 ตัวอักษร';
  if (f.store_postal_code.trim().length > 20) e.store_postal_code = 'รหัสไปรษณีย์ไม่ถูกต้อง';
  if (f.vat_registered && f.tax_id.replace(/\D/g, '').length !== 13) {
    e.tax_id = 'ร้านที่จดทะเบียน VAT ต้องกรอกเลขประจำตัวผู้เสียภาษี 13 หลัก';
  }
  money('shipping_fee', 'ค่าจัดส่ง');
  money('free_shipping_threshold', 'ยอดส่งฟรี');
  money('minimum_order_amount', 'ยอดสั่งซื้อขั้นต่ำ');
  if (f.rider_delivery_enabled && (f.pickup_latitude === null || f.pickup_longitude === null)) {
    e.pickup_latitude = 'เปิดส่งด้วยไรเดอร์ต้องปักหมุดจุดรับของก่อน';
  }
  url('facebook_url', 'Facebook');
  url('instagram_url', 'Instagram');
  url('tiktok_url', 'TikTok');
  return e;
};

/** ส่งเฉพาะช่องที่เปลี่ยนจากค่าเดิม */
const buildPatch = (f: FormState, base: FormState): SellerStorePatch => {
  const patch: Record<string, unknown> = {};
  const textOrNull = (v: string) => (v.trim() === '' ? null : v.trim());
  const moneyOrNull = (v: string) => {
    const n = parseMoney(v);
    return n === null || Number.isNaN(n) ? null : n;
  };
  (Object.keys(f) as (keyof FormState)[]).forEach((key) => {
    if (f[key] === base[key]) return;
    const value = f[key];
    if (key === 'shipping_fee' || key === 'free_shipping_threshold' || key === 'minimum_order_amount') {
      patch[key] = moneyOrNull(value as string);
    } else if (typeof value === 'string') {
      patch[key] = key === 'store_name' ? value.trim() : textOrNull(value);
    } else {
      patch[key] = value;
    }
  });
  // พิกัดส่งคู่กันเสมอ (server ไม่รับหมุดครึ่งๆ กลางๆ)
  if ('pickup_latitude' in patch || 'pickup_longitude' in patch) {
    patch.pickup_latitude = f.pickup_latitude;
    patch.pickup_longitude = f.pickup_longitude;
  }
  // เปิด VAT หรือแก้เลขภาษี → ส่งทั้งคู่ให้ server ตรวจร่วมกัน
  if ('vat_registered' in patch || 'tax_id' in patch) {
    patch.vat_registered = f.vat_registered;
    patch.tax_id = textOrNull(f.tax_id);
  }
  return patch as SellerStorePatch;
};

export default function StoreSettingsScreen() {
  const { colors } = useTheme();
  const navigation = useNavigation();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [store, setStore] = useState<SellerStoreSettings | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadError, setLoadError] = useState<ApiFailure | null>(null);

  const [form, setForm] = useState<FormState | null>(null);
  const [base, setBase] = useState<FormState | null>(null);
  const [errors, setErrors] = useState<Errors>({});
  const [saveError, setSaveError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState<'logo' | 'banner' | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [locating, setLocating] = useState(false);
  const [consentVisible, setConsentVisible] = useState(false);

  const mountedRef = useRef(true);
  const savingRef = useRef(false);
  const uploadAbortRef = useRef<AbortController | null>(null);
  const noticeTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      uploadAbortRef.current?.abort();
      if (noticeTimerRef.current) clearTimeout(noticeTimerRef.current);
    };
  }, []);

  const flash = useCallback((text: string) => {
    if (!mountedRef.current) return;
    if (noticeTimerRef.current) clearTimeout(noticeTimerRef.current);
    setNotice(text);
    noticeTimerRef.current = setTimeout(() => mountedRef.current && setNotice(null), 3500);
  }, []);

  const applyStore = (s: SellerStoreSettings) => {
    const next = formFromStore(s);
    setStore(s);
    setForm(next);
    setBase(next);
    setErrors({});
    setSaveError(null);
  };

  const load = useCallback(
    async (mode: 'initial' | 'refresh') => {
      if (!isAuthenticated) {
        setLoading(false);
        return;
      }
      if (mode === 'refresh') setRefreshing(true);
      const res = await getSellerStore();
      if (!mountedRef.current) return;
      setLoading(false);
      setRefreshing(false);
      if (res.success) {
        applyStore(res.data);
        setLoadError(null);
      } else if (mode === 'initial' || isGateFailure(res)) {
        setLoadError(res);
      } else {
        Alert.alert('รีเฟรชไม่สำเร็จ', res.message);
      }
    },
    [isAuthenticated]
  );

  useEffect(() => {
    load('initial');
  }, [load]);

  const dirty = !!form && !!base && JSON.stringify(form) !== JSON.stringify(base);

  // ---------- ออกจากหน้าโดยยังไม่บันทึก / ระหว่างอัปโหลด → ถามก่อน ----------
  usePreventRemove(dirty || saving || !!uploading, ({ data }) => {
    const busy = saving || !!uploading;
    Alert.alert(
      busy ? 'กำลังบันทึกอยู่' : 'ยังไม่ได้บันทึก',
      busy ? 'ออกจากหน้านี้ตอนนี้ การอัปโหลดจะถูกยกเลิก' : 'มีการแก้ไขที่ยังไม่บันทึก ออกจากหน้านี้แล้วการแก้ไขจะหายไป',
      [
        { text: 'อยู่ต่อ', style: 'cancel' },
        {
          text: busy ? 'ยกเลิกแล้วออก' : 'ทิ้งการแก้ไข',
          style: 'destructive',
          onPress: () => {
            uploadAbortRef.current?.abort();
            navigation.dispatch(data.action);
          },
        },
      ]
    );
  });

  const setText = (key: TextKey, value: string) => {
    setForm((prev) => (prev ? { ...prev, [key]: value } : prev));
    setErrors((prev) => (prev[key] ? { ...prev, [key]: undefined } : prev));
    setSaveError(null);
  };
  const setMoneyText = (key: TextKey) => (value: string) => setText(key, value.replace(/[^0-9.]/g, '').slice(0, 12));
  const setFlag = (key: 'vat_registered' | 'enable_cod' | 'enable_reviews' | 'rider_delivery_enabled', value: boolean) => {
    setForm((prev) => (prev ? { ...prev, [key]: value } : prev));
    if (key === 'vat_registered') setErrors((prev) => ({ ...prev, tax_id: undefined }));
    if (key === 'rider_delivery_enabled') setErrors((prev) => ({ ...prev, pickup_latitude: undefined }));
    setSaveError(null);
  };
  const setPin = (coords: { latitude: number; longitude: number } | null) => {
    setForm((prev) =>
      prev
        ? {
            ...prev,
            pickup_latitude: coords ? Math.round(coords.latitude * 1e7) / 1e7 : null,
            pickup_longitude: coords ? Math.round(coords.longitude * 1e7) / 1e7 : null,
          }
        : prev
    );
    setErrors((prev) => ({ ...prev, pickup_latitude: undefined }));
  };

  // ---------- โลโก้ / แบนเนอร์ ----------
  const changeImage = async (kind: 'logo' | 'banner') => {
    if (uploading || saving) return;
    const source = await choosePhotoSource(kind === 'logo' ? 'เปลี่ยนโลโก้ร้าน' : 'เปลี่ยนแบนเนอร์ร้าน');
    if (!source || !mountedRef.current) return;
    const picked = await pickImages({
      source,
      max: 1,
      maxBytes: STORE_IMAGE_MAX_BYTES[kind],
      aspect: kind === 'logo' ? [1, 1] : [16, 5],
    });
    if (!picked?.uris[0] || !mountedRef.current) return;

    const controller = new AbortController();
    uploadAbortRef.current = controller;
    setUploading(kind);
    const res = await uploadSellerStoreImage(kind, picked.uris[0], controller.signal);
    uploadAbortRef.current = null;
    if (!mountedRef.current || controller.signal.aborted) return;
    setUploading(null);
    if (res.success) {
      resultHaptic('success');
      // อัปเดตเฉพาะรูป ไม่ทับฟอร์มที่กำลังแก้
      setStore((prev) => (prev ? { ...prev, logo_url: res.data.logo_url, banner_url: res.data.banner_url } : res.data));
      flash(kind === 'logo' ? 'เปลี่ยนโลโก้แล้ว' : 'เปลี่ยนแบนเนอร์แล้ว');
    } else {
      resultHaptic('error');
      Alert.alert('อัปโหลดไม่สำเร็จ', res.message);
    }
  };

  // ---------- ตำแหน่งปัจจุบัน ----------
  const locate = async () => {
    setLocating(true);
    try {
      const permission = await Location.requestForegroundPermissionsAsync();
      if (!mountedRef.current) return;
      if (permission.status !== 'granted') {
        Alert.alert('ยังไม่ได้อนุญาตตำแหน่ง', 'แตะบนแผนที่เพื่อปักหมุดเองได้เลย หรือเปิดสิทธิ์ตำแหน่งในการตั้งค่าเครื่องแล้วลองใหม่');
        return;
      }
      const position =
        (await withTimeout(Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High }), LOCATION_TIMEOUT_MS)) ||
        (await Location.getLastKnownPositionAsync().catch(() => null));
      if (!mountedRef.current) return;
      if (!position) {
        Alert.alert('หาตำแหน่งไม่เจอ', 'ออกไปที่โล่งหรือเปิด GPS แล้วลองใหม่ หรือแตะบนแผนที่เพื่อปักหมุดเอง');
        return;
      }
      setPin({ latitude: position.coords.latitude, longitude: position.coords.longitude });
      resultHaptic('success');
    } catch {
      if (mountedRef.current) Alert.alert('หาตำแหน่งไม่สำเร็จ', 'เปิด GPS แล้วลองใหม่อีกครั้งนะ');
    } finally {
      if (mountedRef.current) setLocating(false);
    }
  };

  const onLocatePress = async () => {
    try {
      const current = await Location.getForegroundPermissionsAsync();
      if (current.status === 'granted') {
        await locate();
        return;
      }
    } catch {
      // อ่านสิทธิ์ไม่ได้ → อธิบายเหตุผลก่อน
    }
    setConsentVisible(true);
  };

  // ---------- บันทึก ----------
  const save = async () => {
    if (!form || !base || savingRef.current) return;
    const found = validate(form);
    if (Object.keys(found).length > 0) {
      setErrors(found);
      setSaveError('ตรวจข้อมูลที่ขึ้นสีแดงอีกครั้งนะ');
      resultHaptic('error');
      return;
    }
    const patch = buildPatch(form, base);
    if (Object.keys(patch).length === 0) return;

    savingRef.current = true;
    setSaving(true);
    setSaveError(null);
    const res = await updateSellerStore(patch);
    savingRef.current = false;
    if (!mountedRef.current) return;
    setSaving(false);

    if (res.success) {
      resultHaptic('success');
      applyStore(res.data);
      flash('บันทึกการตั้งค่าร้านแล้ว');
      return;
    }
    resultHaptic('error');
    if (isGateFailure(res)) {
      setLoadError(res);
      return;
    }
    if (res.errors) {
      const fieldErrors: Errors = {};
      Object.entries(res.errors).forEach(([key, list]) => {
        const msg = Array.isArray(list) ? list[0] : null;
        if (typeof msg !== 'string' || !THAI.test(msg)) return;
        (fieldErrors as Record<string, string>)[key === 'pickup_longitude' ? 'pickup_latitude' : key] = msg;
      });
      setErrors(fieldErrors);
    }
    setSaveError(res.message);
  };

  // =====================================================
  // แสดงผล
  // =====================================================

  if (!isAuthenticated) {
    return (
      <Screen title="ตั้งค่าร้าน" scroll={false}>
        <EmptyState art="store" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (loading && !store) {
    return (
      <Screen title="ตั้งค่าร้าน">
        <FormSkeleton sections={4} />
      </Screen>
    );
  }

  if (loadError && (isGateFailure(loadError) || !store)) {
    return (
      <Screen title="ตั้งค่าร้าน" onRefresh={() => load('refresh')} refreshing={refreshing}>
        {isGateFailure(loadError) ? (
          <SellerGateNotice failure={loadError} />
        ) : (
          <EmptyState variant="error" message={loadError.message} onAction={() => load('initial')} secondaryActionLabel="กลับ" onSecondaryAction={() => router.back()} />
        )}
      </Screen>
    );
  }

  if (!store || !form) return null;

  const hasPin = form.pickup_latitude !== null && form.pickup_longitude !== null;
  const isCompany = form.business_type === 'company';

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <Screen
        title="ตั้งค่าร้าน"
        subtitle={store.store_name}
        refreshing={refreshing}
        onRefresh={dirty ? undefined : () => load('refresh')}
        right={dirty ? <Pill label="ยังไม่บันทึก" tone="warning" /> : undefined}
      >
        {!!notice && <NoticeBanner tone="success" text={notice} style={styles.block} />}

        {/* ---------- แบนเนอร์ + โลโก้ ---------- */}
        <Card3D padding={0} radius={radii.xl} style={styles.block} contentStyle={styles.clip}>
          <Pressable
            onPress={() => changeImage('banner')}
            disabled={!!uploading}
            accessibilityRole="button"
            accessibilityLabel="เปลี่ยนแบนเนอร์ร้าน"
            style={[styles.banner, { backgroundColor: colors.navyFill }]}
          >
            {store.banner_url ? (
              <Image source={{ uri: store.banner_url }} style={StyleSheet.absoluteFill} contentFit="cover" transition={150} />
            ) : (
              <View style={styles.bannerEmpty}>
                <Icon name="image" size={26} color={colors.goldLight} />
                <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>แตะเพื่อเพิ่มแบนเนอร์ (แนะนำ 1920×600)</Text>
              </View>
            )}
            <View style={[styles.bannerBadge, { backgroundColor: colors.overlay }]}>
              {uploading === 'banner' ? <ActivityIndicator size="small" color={colors.textOnAccent} /> : <Icon name="camera" size={16} color={colors.textOnAccent} />}
            </View>
          </Pressable>
          <View style={styles.identity}>
            <Pressable
              onPress={() => changeImage('logo')}
              disabled={!!uploading}
              accessibilityRole="button"
              accessibilityLabel="เปลี่ยนโลโก้ร้าน"
              style={[styles.logo, { backgroundColor: colors.card, borderColor: colors.gold }]}
            >
              {store.logo_url ? (
                <Image source={{ uri: store.logo_url }} style={styles.logoImage} contentFit="cover" transition={150} />
              ) : (
                <BrandArt name="store" size={52} />
              )}
              <View style={[styles.logoBadge, { backgroundColor: colors.navyFill }]}>
                {uploading === 'logo' ? <ActivityIndicator size="small" color={colors.goldLight} /> : <Icon name="camera" size={13} color={colors.goldLight} />}
              </View>
            </Pressable>
            <View style={styles.flex}>
              <Text numberOfLines={1} style={[typography.serif, { color: colors.textStrong }]}>
                {store.store_name}
              </Text>
              <View style={styles.pills}>
                {store.is_verified && <Pill label="ร้านยืนยันแล้ว" tone="success" icon="seal-check" />}
                <Pill label={store.is_active ? 'เปิดร้านอยู่' : 'ปิดร้านอยู่'} tone={store.is_active ? 'gold' : 'danger'} />
              </View>
            </View>
          </View>
          <Text style={[typography.micro, styles.imageHint, { color: colors.textMuted }]}>
            โลโก้ไม่เกิน 2MB · แบนเนอร์ไม่เกิน 4MB · JPG PNG WebP
          </Text>
        </Card3D>

        {/* ---------- ข้อมูลร้าน ---------- */}
        <FormCard icon="storefront" title="ข้อมูลร้าน">
          <Field label="ชื่อร้าน" required value={form.store_name} onChangeText={(v) => setText('store_name', v)} error={errors.store_name} maxLength={255} />
          <Field
            label="รายละเอียดร้าน"
            value={form.store_description}
            onChangeText={(v) => setText('store_description', v)}
            error={errors.store_description}
            placeholder="ขายอะไร จุดเด่นของร้าน"
            multiline
            maxLength={5000}
          />
          <View style={styles.fieldRow}>
            <Field
              label="เบอร์โทรร้าน"
              value={form.store_phone}
              onChangeText={(v) => setText('store_phone', v.replace(/[^0-9+\-\s]/g, ''))}
              error={errors.store_phone}
              keyboardType="phone-pad"
              maxLength={20}
              containerStyle={styles.flex}
            />
            <Field
              label="อีเมลร้าน"
              value={form.store_email}
              onChangeText={(v) => setText('store_email', v.trim())}
              error={errors.store_email}
              keyboardType="email-address"
              autoCapitalize="none"
              autoCorrect={false}
              maxLength={255}
              containerStyle={styles.flex}
            />
          </View>
        </FormCard>

        {/* ---------- ที่อยู่ ---------- */}
        <FormCard icon="house" title="ที่อยู่ร้าน">
          <Field label="ที่อยู่" value={form.store_address} onChangeText={(v) => setText('store_address', v)} error={errors.store_address} multiline maxLength={1000} />
          <View style={styles.fieldRow}>
            <Field label="อำเภอ / เขต" value={form.store_city} onChangeText={(v) => setText('store_city', v)} error={errors.store_city} maxLength={100} containerStyle={styles.flex} />
            <Field label="จังหวัด" value={form.store_state} onChangeText={(v) => setText('store_state', v)} error={errors.store_state} maxLength={100} containerStyle={styles.flex} />
          </View>
          <Field
            label="รหัสไปรษณีย์"
            value={form.store_postal_code}
            onChangeText={(v) => setText('store_postal_code', v.replace(/[^0-9]/g, '').slice(0, 5))}
            error={errors.store_postal_code}
            keyboardType="number-pad"
            maxLength={5}
            containerStyle={styles.postal}
          />
        </FormCard>

        {/* ---------- ธุรกิจและภาษี ---------- */}
        <FormCard icon="identification-card" title="ธุรกิจและภาษี">
          <View style={styles.chipRow}>
            <Chip label="บุคคลธรรมดา" icon="user" selected={!isCompany} onPress={() => setForm((p) => (p ? { ...p, business_type: 'individual' } : p))} />
            <Chip label="นิติบุคคล" icon="buildings" selected={isCompany} onPress={() => setForm((p) => (p ? { ...p, business_type: 'company' } : p))} />
          </View>
          {isCompany && (
            <Field label="ชื่อบริษัท / หจก." value={form.company_name} onChangeText={(v) => setText('company_name', v)} error={errors.company_name} maxLength={255} />
          )}
          <Field
            label="เลขประจำตัวผู้เสียภาษี (13 หลัก)"
            value={form.tax_id}
            onChangeText={(v) => setText('tax_id', v.replace(/[^0-9]/g, '').slice(0, 13))}
            error={errors.tax_id}
            keyboardType="number-pad"
            maxLength={13}
          />
          <ToggleRow
            icon="receipt"
            title="ร้านจดทะเบียน VAT"
            description="ระบบถอด VAT 7/107 ออกจากยอดขายก่อนโอนเงินให้ร้าน"
            value={form.vat_registered}
            onValueChange={(v) => setFlag('vat_registered', v)}
            tone={form.vat_registered ? 'gold' : 'neutral'}
          />
        </FormCard>

        {/* ---------- การจัดส่งและชำระเงิน ---------- */}
        <FormCard icon="truck" title="การจัดส่งและชำระเงิน" subtitle="ใช้กับสินค้าที่ตั้ง 'ใช้ค่าเริ่มต้นของร้าน'">
          <View style={styles.fieldRow}>
            <Field
              label="ค่าจัดส่ง (บาท)"
              value={form.shipping_fee}
              onChangeText={setMoneyText('shipping_fee')}
              error={errors.shipping_fee}
              keyboardType="decimal-pad"
              placeholder="0"
              containerStyle={styles.flex}
            />
            <Field
              label="ส่งฟรีเมื่อซื้อครบ"
              value={form.free_shipping_threshold}
              onChangeText={setMoneyText('free_shipping_threshold')}
              error={errors.free_shipping_threshold}
              keyboardType="decimal-pad"
              placeholder="ว่าง = ไม่มี"
              containerStyle={styles.flex}
            />
          </View>
          <Field
            label="ยอดสั่งซื้อขั้นต่ำ (บาท)"
            value={form.minimum_order_amount}
            onChangeText={setMoneyText('minimum_order_amount')}
            error={errors.minimum_order_amount}
            keyboardType="decimal-pad"
            placeholder="0 = ไม่มีขั้นต่ำ"
          />
          <ToggleRow
            icon="hand-coins"
            title="รับเก็บเงินปลายทาง (COD)"
            description="ลูกค้าจ่ายเงินตอนรับของ"
            value={form.enable_cod}
            onValueChange={(v) => setFlag('enable_cod', v)}
          />
          <ToggleRow
            icon="star"
            title="เปิดรีวิวสินค้า"
            description="ลูกค้าให้คะแนนและเขียนรีวิวหลังได้รับของ"
            value={form.enable_reviews}
            onValueChange={(v) => setFlag('enable_reviews', v)}
          />
        </FormCard>

        {/* ---------- ส่งด้วยไรเดอร์ + จุดรับของ ---------- */}
        <FormCard icon="moped" title="ส่งด้วยไรเดอร์" tone="gold" subtitle="ไรเดอร์ของแพลตฟอร์มมารับของที่ร้านไปส่งลูกค้าใกล้ๆ">
          <ToggleRow
            icon="moped"
            title="เปิดส่งด้วยไรเดอร์"
            description={hasPin ? 'ปักหมุดจุดรับของแล้ว' : 'ต้องปักหมุดจุดรับของก่อน'}
            value={form.rider_delivery_enabled}
            onValueChange={(v) => setFlag('rider_delivery_enabled', v)}
          />
          <Text style={[typography.bodyStrong, styles.pinTitle, { color: colors.textStrong }]}>จุดรับของ</Text>
          <Text style={[typography.caption, { color: colors.textMuted }]}>แตะบนแผนที่หรือลากหมุดไปยังหน้าร้าน</Text>
          <LiveMap
            markers={
              hasPin
                ? [{ id: 'shop', kind: 'shop', latitude: form.pickup_latitude as number, longitude: form.pickup_longitude as number, label: form.store_name || 'ร้านของฉัน' }]
                : []
            }
            height={200}
            onPick={(coords) => setPin(coords)}
            draggableId="shop"
            openTargetId={hasPin ? 'shop' : null}
            accessibilityLabel="แผนที่ปักหมุดจุดรับของ"
            style={styles.map}
          />
          {hasPin ? (
            <View style={[styles.pinRow, { backgroundColor: colors.inset, borderColor: colors.border }]}>
              <Pill label="ปักหมุดแล้ว" tone="success" icon="check-circle" />
              <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>
                {(form.pickup_latitude as number).toFixed(5)}, {(form.pickup_longitude as number).toFixed(5)}
              </Text>
            </View>
          ) : (
            <NoticeBanner tone="warning" text="ยังไม่ได้ปักหมุด ไรเดอร์จะยังรับงานของร้านไม่ได้" style={styles.gapSm} />
          )}
          {!!errors.pickup_latitude && <Text style={[typography.caption, styles.gapXs, { color: colors.danger }]}>{errors.pickup_latitude}</Text>}
          <View style={styles.fieldRow}>
            <Button3D
              title={hasPin ? 'ปักที่ตำแหน่งปัจจุบัน' : 'ใช้ตำแหน่งปัจจุบัน'}
              icon="crosshair"
              size="sm"
              loading={locating}
              loadingText="กำลังหาตำแหน่ง..."
              onPress={onLocatePress}
              style={[styles.flex, styles.gapMd]}
            />
            {hasPin && (
              <Button3D
                title="เอาหมุดออก"
                variant="ghost"
                size="sm"
                style={styles.gapMd}
                onPress={() => {
                  if (form.rider_delivery_enabled) {
                    Alert.alert('ยังเปิดส่งด้วยไรเดอร์อยู่', 'ปิด "เปิดส่งด้วยไรเดอร์" ก่อน แล้วค่อยเอาหมุดออก');
                    return;
                  }
                  setPin(null);
                }}
              />
            )}
          </View>
          <Field
            label="รายละเอียดจุดรับของ"
            value={form.pickup_address}
            onChangeText={(v) => setText('pickup_address', v)}
            error={errors.pickup_address}
            placeholder="เช่น หน้าร้าน ตึกสีฟ้า ใกล้ 7-Eleven"
            multiline
            maxLength={500}
          />
        </FormCard>

        {/* ---------- ช่องทางติดต่อ ---------- */}
        <FormCard icon="chats" title="ช่องทางติดต่อ" subtitle="แสดงในหน้าร้านให้ลูกค้าติดต่อ">
          <Field label="Facebook" value={form.facebook_url} onChangeText={(v) => setText('facebook_url', v.trim())} error={errors.facebook_url} placeholder="https://facebook.com/..." keyboardType="url" autoCapitalize="none" autoCorrect={false} maxLength={255} />
          <Field label="LINE OA" value={form.line_oa_id} onChangeText={(v) => setText('line_oa_id', v.trim())} error={errors.line_oa_id} placeholder="@yourshop" autoCapitalize="none" autoCorrect={false} maxLength={255} />
          <Field label="Instagram" value={form.instagram_url} onChangeText={(v) => setText('instagram_url', v.trim())} error={errors.instagram_url} placeholder="https://instagram.com/..." keyboardType="url" autoCapitalize="none" autoCorrect={false} maxLength={255} />
          <Field label="TikTok" value={form.tiktok_url} onChangeText={(v) => setText('tiktok_url', v.trim())} error={errors.tiktok_url} placeholder="https://tiktok.com/@..." keyboardType="url" autoCapitalize="none" autoCorrect={false} maxLength={255} />
        </FormCard>

        {/* ---------- เว็บ ---------- */}
        <Card3D variant="flat" padding={spacing.lg} style={styles.block}>
          <Text style={[typography.bodySm, { color: colors.textMuted }]}>สีร้าน เลย์เอาต์หน้าร้าน และ SEO ปรับได้บนเว็บไซต์</Text>
          <WebsiteButton path="/seller/store/layout" label="ปรับหน้าร้านบนเว็บ" icon="globe" size="sm" style={styles.webButton} />
        </Card3D>

        {!!saveError && <ErrorNote text={saveError} />}
        <View style={styles.bottomSpace} />
      </Screen>

      <StickyBar>
        <Button3D
          title={dirty ? 'บันทึกการตั้งค่า' : 'บันทึกแล้ว'}
          icon={dirty ? 'check-circle' : 'seal-check'}
          variant={dirty ? 'primary' : 'secondary'}
          size="lg"
          fullWidth
          disabled={!dirty}
          loading={saving}
          loadingText="กำลังบันทึก..."
          onPress={save}
        />
      </StickyBar>

      <ConsentSheet
        visible={consentVisible}
        icon="map-pin"
        title="ขอใช้ตำแหน่งเพื่อปักหมุดร้าน"
        description="แอปจะอ่านตำแหน่งครั้งเดียวตอนคุณกดปุ่มนี้ เพื่อปักหมุดจุดรับของของร้าน"
        reasons={[
          { icon: 'moped', text: 'ไรเดอร์ใช้หมุดนี้มารับของที่ร้าน และคำนวณค่าส่งตามระยะทางจริง' },
          { icon: 'lock', text: 'ไม่ติดตามตำแหน่งเบื้องหลัง อ่านครั้งเดียวตอนกดเท่านั้น' },
          { icon: 'hand-tap', text: 'ไม่อยากให้สิทธิ์ก็แตะบนแผนที่เพื่อปักหมุดเองได้' },
        ]}
        acceptLabel="อนุญาตและปักหมุด"
        declineLabel="ปักเองบนแผนที่"
        onAccept={async () => {
          setConsentVisible(false);
          await locate();
        }}
        onDecline={() => setConsentVisible(false)}
      />
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  block: {
    marginBottom: spacing.lg,
  },
  clip: {
    overflow: 'hidden',
  },
  gapXs: {
    marginTop: spacing.xs,
  },
  gapSm: {
    marginTop: spacing.sm,
  },
  gapMd: {
    marginTop: spacing.md,
  },
  banner: {
    height: 128,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bannerEmpty: {
    alignItems: 'center',
    gap: 6,
  },
  bannerBadge: {
    position: 'absolute',
    right: 10,
    top: 10,
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  identity: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    marginTop: -34,
  },
  logo: {
    width: 76,
    height: 76,
    borderRadius: 22,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  logoImage: {
    width: '100%',
    height: '100%',
  },
  logoBadge: {
    position: 'absolute',
    right: 4,
    bottom: 4,
    width: 24,
    height: 24,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  imageHint: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.sm,
    paddingBottom: spacing.md,
  },
  fieldRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  postal: {
    width: 160,
  },
  chipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  pinTitle: {
    marginTop: spacing.lg,
  },
  map: {
    marginTop: spacing.sm,
  },
  pinRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.sm,
    borderRadius: 14,
    borderWidth: 1,
    padding: spacing.sm,
  },
  webButton: {
    marginTop: spacing.md,
    alignSelf: 'flex-start',
  },
  bottomSpace: {
    height: 90,
  },
});
