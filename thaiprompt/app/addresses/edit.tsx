/**
 * เพิ่ม / แก้ไขที่อยู่จัดส่ง — POST /addresses · PUT /addresses/{id}
 *
 * - /addresses/edit          = เพิ่มใหม่
 * - /addresses/edit?id=12    = แก้ไข (เติมค่าเดิมให้ครบ)
 * - "ใช้ตำแหน่งปัจจุบัน": แจ้งเหตุผลก่อนขอสิทธิ์ตำแหน่ง (ConsentSheet) → ปักหมุด + เติมจังหวัด/เขต/รหัสไปรษณีย์ที่ยังว่าง
 * - ส่งด้วยไรเดอร์ต้องปักหมุด (latitude/longitude ส่งคู่กันเสมอ)
 * - ออกจากหน้าโดยยังไม่บันทึก → ถามก่อน
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, Switch, Text, View } from 'react-native';
import * as Location from 'expo-location';
import { router, useLocalSearchParams, useNavigation } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { createAddress, getAddresses, updateAddress, type AddressInput } from '@/services/api/shopApi';
import { Button3D, Card3D, ConsentSheet, EmptyState, Pill, Screen, resultHaptic } from '@/components/ui';
import { Field, openHttpsLink } from '@/components/shop';
import { useTheme, spacing, typography } from '@/theme';

type FormKey =
  | 'recipient_name'
  | 'phone_number'
  | 'address_line_1'
  | 'address_line_2'
  | 'sub_district'
  | 'district'
  | 'province'
  | 'postal_code'
  | 'notes';

type FormState = Record<FormKey, string>;

const EMPTY_FORM: FormState = {
  recipient_name: '',
  phone_number: '',
  address_line_1: '',
  address_line_2: '',
  sub_district: '',
  district: '',
  province: '',
  postal_code: '',
  notes: '',
};

const LOCATION_TIMEOUT_MS = 15000;

/** รอตำแหน่งไม่เกินเวลาที่กำหนด (GPS บางเครื่องค้าง) */
const withTimeout = <T,>(promise: Promise<T>, ms: number): Promise<T | null> =>
  Promise.race([promise, new Promise<null>((resolve) => setTimeout(() => resolve(null), ms))]);

/** ตรวจค่าก่อนส่ง (ข้อความไทยรายช่อง) */
const validate = (form: FormState): Partial<Record<FormKey, string>> => {
  const errors: Partial<Record<FormKey, string>> = {};
  if (!form.recipient_name.trim()) errors.recipient_name = 'กรอกชื่อผู้รับก่อนนะ';
  if (!/^[0-9+\-\s]{9,20}$/.test(form.phone_number.trim())) errors.phone_number = 'เบอร์โทรไม่ถูกต้อง (ตัวเลข 9-10 หลัก)';
  if (!form.address_line_1.trim()) errors.address_line_1 = 'กรอกบ้านเลขที่ / ถนน ก่อนนะ';
  if (!form.province.trim()) errors.province = 'กรอกจังหวัดก่อนนะ';
  if (!/^[0-9]{5}$/.test(form.postal_code.trim())) errors.postal_code = 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก';
  return errors;
};

export default function AddressEditScreen() {
  const { colors } = useTheme();
  const navigation = useNavigation();
  const params = useLocalSearchParams<{ id?: string }>();
  const editId = params.id && /^\d+$/.test(params.id) ? Number(params.id) : null;
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [coords, setCoords] = useState<{ latitude: number; longitude: number } | null>(null);
  const [isDefault, setIsDefault] = useState(false);
  const [wasDefault, setWasDefault] = useState(false);
  const [errors, setErrors] = useState<Partial<Record<FormKey, string>>>({});
  const [loading, setLoading] = useState(editId !== null);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [locating, setLocating] = useState(false);
  const [consentVisible, setConsentVisible] = useState(false);
  const [dirty, setDirty] = useState(false);

  const mountedRef = useRef(true);
  const savedRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // ---------- โหลดค่าเดิม (โหมดแก้ไข) ----------
  const loadExisting = useCallback(async () => {
    if (editId === null || !isAuthenticated) return;
    setLoading(true);
    setLoadError(null);
    const result = await getAddresses();
    if (!mountedRef.current) return;
    if (!result.success) {
      setLoadError(result.message);
      setLoading(false);
      return;
    }
    const found = (Array.isArray(result.data) ? result.data : []).find((a) => a.id === editId);
    if (!found) {
      setLoadError('ไม่พบที่อยู่นี้ อาจถูกลบไปแล้ว');
      setLoading(false);
      return;
    }
    setForm({
      recipient_name: found.recipient_name || '',
      phone_number: found.phone_number || '',
      address_line_1: found.address_line_1 || '',
      address_line_2: found.address_line_2 || '',
      sub_district: found.sub_district || '',
      district: found.district || '',
      province: found.province || '',
      postal_code: found.postal_code || '',
      notes: found.notes || '',
    });
    const lat = Number(found.latitude);
    const lng = Number(found.longitude);
    setCoords(found.has_location && Number.isFinite(lat) && Number.isFinite(lng) ? { latitude: lat, longitude: lng } : null);
    setIsDefault(!!found.is_default);
    setWasDefault(!!found.is_default);
    setDirty(false);
    setLoading(false);
  }, [editId, isAuthenticated]);

  useEffect(() => {
    loadExisting();
  }, [loadExisting]);

  // ---------- เตือนเมื่อออกโดยยังไม่บันทึก ----------
  useEffect(() => {
    const unsubscribe = navigation.addListener('beforeRemove', (event: any) => {
      if (!dirty || savedRef.current) return;
      event.preventDefault();
      Alert.alert('ยังไม่ได้บันทึก', 'ออกจากหน้านี้แล้วข้อมูลที่กรอกจะหายไป', [
        { text: 'อยู่ต่อ', style: 'cancel' },
        { text: 'ออกโดยไม่บันทึก', style: 'destructive', onPress: () => navigation.dispatch(event.data.action) },
      ]);
    });
    return unsubscribe;
  }, [navigation, dirty]);

  const setField = (key: FormKey, value: string) => {
    setForm((prev) => ({ ...prev, [key]: value }));
    setErrors((prev) => (prev[key] ? { ...prev, [key]: undefined } : prev));
    setDirty(true);
  };

  // ---------- ตำแหน่งปัจจุบัน ----------
  const locate = async () => {
    setLocating(true);
    try {
      const permission = await Location.requestForegroundPermissionsAsync();
      if (!mountedRef.current) return;
      if (permission.status !== 'granted') {
        Alert.alert('ยังไม่ได้อนุญาตตำแหน่ง', 'กรอกที่อยู่เองได้เลย หรือเปิดสิทธิ์ตำแหน่งในการตั้งค่าเครื่องแล้วลองใหม่');
        return;
      }

      const position =
        (await withTimeout(Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High }), LOCATION_TIMEOUT_MS)) ||
        (await Location.getLastKnownPositionAsync().catch(() => null));
      if (!mountedRef.current) return;
      if (!position) {
        Alert.alert('หาตำแหน่งไม่เจอ', 'ออกไปที่โล่งหรือเปิด GPS แล้วลองใหม่นะ');
        return;
      }

      const latitude = Math.round(position.coords.latitude * 1e7) / 1e7;
      const longitude = Math.round(position.coords.longitude * 1e7) / 1e7;
      setCoords({ latitude, longitude });
      setDirty(true);
      resultHaptic('success');

      // เติมช่องที่ยังว่างจากชื่อสถานที่ (ถ้าเครื่องแปลงได้)
      try {
        const places = await withTimeout(Location.reverseGeocodeAsync({ latitude, longitude }), 8000);
        const place = places?.[0];
        if (place && mountedRef.current) {
          setForm((prev) => ({
            ...prev,
            address_line_1:
              prev.address_line_1 ||
              [place.streetNumber, place.street].filter(Boolean).join(' ') ||
              (place.name && place.name !== place.region ? place.name : '') ||
              '',
            sub_district: prev.sub_district || (place.district ?? '') || '',
            district: prev.district || (place.subregion ?? '') || (place.city ?? '') || '',
            province: prev.province || (place.region ?? '') || (place.city ?? '') || '',
            postal_code: prev.postal_code || (place.postalCode && /^\d{5}$/.test(place.postalCode) ? place.postalCode : ''),
          }));
        }
      } catch {
        // แปลงชื่อสถานที่ไม่ได้ก็ไม่เป็นไร ปักหมุดได้แล้ว
      }
    } catch {
      if (mountedRef.current) {
        Alert.alert('หาตำแหน่งไม่สำเร็จ', 'เปิด GPS แล้วลองใหม่อีกครั้งนะ');
      }
    } finally {
      if (mountedRef.current) setLocating(false);
    }
  };

  /** กดปุ่มปักหมุด: ถ้ายังไม่เคยให้สิทธิ์ → อธิบายเหตุผลก่อน */
  const onLocatePress = async () => {
    try {
      const current = await Location.getForegroundPermissionsAsync();
      if (current.status === 'granted') {
        await locate();
        return;
      }
    } catch {
      // อ่านสิทธิ์ไม่ได้ → แสดงคำอธิบายก่อน
    }
    setConsentVisible(true);
  };

  // ---------- บันทึก ----------
  const save = async () => {
    const found = validate(form);
    if (Object.keys(found).length > 0) {
      setErrors(found);
      resultHaptic('error');
      return;
    }

    // ช่องไม่บังคับที่ว่าง: สร้างใหม่ = ไม่ส่ง · แก้ไข = ส่ง null เพื่อลบค่าเดิม
    // (server อัปเดตเฉพาะคีย์ที่ส่งมา — ส่ง undefined คีย์จะหายไปจาก JSON แล้วค่าเก่าจะค้างอยู่)
    const optional = (value: string): string | null | undefined => {
      const v = value.trim();
      if (v) return v;
      return editId === null ? undefined : null;
    };

    const payload: AddressInput = {
      recipient_name: form.recipient_name.trim(),
      phone_number: form.phone_number.trim(),
      address_line_1: form.address_line_1.trim(),
      address_line_2: optional(form.address_line_2),
      sub_district: optional(form.sub_district),
      district: optional(form.district),
      province: form.province.trim(),
      postal_code: form.postal_code.trim(),
      notes: optional(form.notes),
      ...(coords ? { latitude: coords.latitude, longitude: coords.longitude } : {}),
      ...(isDefault && !wasDefault ? { is_default: true } : editId === null ? { is_default: isDefault } : {}),
    };

    const result = editId === null ? await createAddress(payload) : await updateAddress(editId, payload);
    if (!mountedRef.current) return;

    if (!result.success) {
      resultHaptic('error');
      // ข้อความรายช่องจาก server (ภาษาไทย)
      if (result.errors) {
        const fieldErrors: Partial<Record<FormKey, string>> = {};
        (Object.keys(EMPTY_FORM) as FormKey[]).forEach((key) => {
          const message = result.errors?.[key]?.[0];
          if (message && /[฀-๿]/.test(message)) fieldErrors[key] = message;
        });
        if (Object.keys(fieldErrors).length > 0) setErrors(fieldErrors);
      }
      Alert.alert('บันทึกไม่สำเร็จ', result.message);
      return;
    }

    resultHaptic('success');
    savedRef.current = true;
    setDirty(false);
    if (router.canGoBack()) router.back();
    else router.replace('/addresses' as never);
  };

  // ---------- render ----------
  const title = editId === null ? 'เพิ่มที่อยู่' : 'แก้ไขที่อยู่';

  if (!isAuthenticated) {
    return (
      <Screen title={title} scroll={false}>
        <EmptyState icon="🔐" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (loading) {
    return (
      <Screen title={title} scroll={false}>
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </Screen>
    );
  }

  if (loadError) {
    return (
      <Screen title={title} scroll={false}>
        <EmptyState variant="error" message={loadError} onAction={loadExisting} secondaryActionLabel="กลับ" onSecondaryAction={() => router.back()} />
      </Screen>
    );
  }

  return (
    <Screen title={title} subtitle="ที่อยู่สำหรับจัดส่งสินค้า">
      {/* ปักหมุด */}
      <Card3D gradientBorder padding={spacing.lg} style={styles.card}>
        <View style={styles.pinHeader}>
          <Text style={styles.pinIcon}>📍</Text>
          <View style={styles.flex}>
            <Text style={[typography.h3, { color: colors.textStrong }]}>ปักหมุดตำแหน่ง</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              ปักหมุดแล้วเลือกส่งด้วยไรเดอร์ได้ ไรเดอร์ไปถึงถูกที่
            </Text>
          </View>
        </View>
        {coords ? (
          <View style={styles.pinRow}>
            <Pill label="ปักหมุดแล้ว" tone="success" icon="✅" />
            <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>
              {coords.latitude.toFixed(5)}, {coords.longitude.toFixed(5)}
            </Text>
          </View>
        ) : (
          <Text style={[typography.bodySm, styles.pinHint, { color: colors.warning }]}>ยังไม่ได้ปักหมุด (ส่งพัสดุได้ แต่ส่งด้วยไรเดอร์ไม่ได้)</Text>
        )}
        <View style={styles.row}>
          <Button3D
            title={coords ? 'ปักหมุดใหม่ที่นี่' : 'ใช้ตำแหน่งปัจจุบัน'}
            icon="🎯"
            size="sm"
            loading={locating}
            loadingText="กำลังหาตำแหน่ง..."
            onPress={onLocatePress}
            style={styles.flex}
          />
          {coords && (
            <Button3D
              title="ดูบนแผนที่"
              icon="🗺️"
              variant="secondary"
              size="sm"
              onPress={() =>
                openHttpsLink(
                  `https://www.google.com/maps/search/?api=1&query=${coords.latitude},${coords.longitude}`,
                  'ดูบนแผนที่'
                )
              }
              style={styles.flex}
            />
          )}
        </View>
        {coords && (
          <Button3D
            title="เอาหมุดออก"
            variant="ghost"
            size="sm"
            onPress={() => {
              setCoords(null);
              setDirty(true);
            }}
            style={styles.unpin}
          />
        )}
      </Card3D>

      {/* ข้อมูลผู้รับ */}
      <Card3D padding={spacing.lg} style={styles.card}>
        <Text style={[typography.h3, { color: colors.textStrong }]}>ผู้รับ</Text>
        <Field
          label="ชื่อผู้รับ"
          required
          value={form.recipient_name}
          onChangeText={(v) => setField('recipient_name', v)}
          error={errors.recipient_name}
          placeholder="ชื่อ-นามสกุล"
          maxLength={255}
          autoComplete="name"
        />
        <Field
          label="เบอร์โทรศัพท์"
          required
          value={form.phone_number}
          onChangeText={(v) => setField('phone_number', v.replace(/[^0-9+\-\s]/g, ''))}
          error={errors.phone_number}
          placeholder="08x-xxx-xxxx"
          keyboardType="phone-pad"
          maxLength={20}
          autoComplete="tel"
        />
      </Card3D>

      {/* ที่อยู่ */}
      <Card3D padding={spacing.lg} style={styles.card}>
        <Text style={[typography.h3, { color: colors.textStrong }]}>ที่อยู่</Text>
        <Field
          label="บ้านเลขที่ / หมู่บ้าน / ถนน"
          required
          value={form.address_line_1}
          onChangeText={(v) => setField('address_line_1', v)}
          error={errors.address_line_1}
          placeholder="เช่น 99/1 หมู่ 2 ถ.สุขุมวิท"
          maxLength={255}
        />
        <Field
          label="อาคาร / ชั้น / ห้อง (ถ้ามี)"
          value={form.address_line_2}
          onChangeText={(v) => setField('address_line_2', v)}
          maxLength={255}
        />
        <View style={styles.fieldRow}>
          <Field
            label="แขวง / ตำบล"
            value={form.sub_district}
            onChangeText={(v) => setField('sub_district', v)}
            containerStyle={styles.flex}
            maxLength={255}
          />
          <Field
            label="เขต / อำเภอ"
            value={form.district}
            onChangeText={(v) => setField('district', v)}
            containerStyle={styles.flex}
            maxLength={255}
          />
        </View>
        <View style={styles.fieldRow}>
          <Field
            label="จังหวัด"
            required
            value={form.province}
            onChangeText={(v) => setField('province', v)}
            error={errors.province}
            containerStyle={styles.flex}
            maxLength={255}
          />
          <Field
            label="รหัสไปรษณีย์"
            required
            value={form.postal_code}
            onChangeText={(v) => setField('postal_code', v.replace(/[^0-9]/g, '').slice(0, 5))}
            error={errors.postal_code}
            keyboardType="number-pad"
            maxLength={5}
            containerStyle={styles.postal}
            autoComplete="postal-code"
          />
        </View>
        <Field
          label="หมายเหตุถึงผู้ส่ง (ถ้ามี)"
          value={form.notes}
          onChangeText={(v) => setField('notes', v)}
          placeholder="เช่น ฝากป้อมยาม, บ้านรั้วสีเขียว"
          multiline
          maxLength={500}
        />
        <View style={styles.defaultRow}>
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ตั้งเป็นที่อยู่หลัก</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>ใช้ที่อยู่นี้เป็นค่าเริ่มต้นตอนสั่งซื้อ</Text>
          </View>
          <Switch
            value={isDefault}
            onValueChange={(v) => {
              // ที่อยู่หลักเดิมเอาออกจากหลักตรงๆ ไม่ได้ (ให้ตั้งที่อยู่อื่นเป็นหลักแทน)
              if (wasDefault && !v) {
                Alert.alert('ที่อยู่หลัก', 'ตั้งที่อยู่อื่นเป็นหลักแทนได้ที่หน้ารายการที่อยู่');
                return;
              }
              setIsDefault(v);
              setDirty(true);
            }}
            trackColor={{ false: colors.border, true: colors.gold }}
            thumbColor={colors.card}
            accessibilityLabel="ตั้งเป็นที่อยู่หลัก"
          />
        </View>
      </Card3D>

      <Button3D title="บันทึกที่อยู่" icon="💾" size="lg" fullWidth onPress={save} loadingText="กำลังบันทึก..." />

      <ConsentSheet
        visible={consentVisible}
        icon="📍"
        title="ขอใช้ตำแหน่งเพื่อปักหมุดที่อยู่"
        description="แอปจะอ่านตำแหน่งครั้งเดียวตอนคุณกดปุ่มนี้ เพื่อปักหมุดที่อยู่จัดส่ง"
        reasons={[
          { icon: '🛵', text: 'ไรเดอร์ใช้หมุดนี้ไปส่งของถึงหน้าบ้าน และคำนวณค่าส่งตามระยะทางจริง' },
          { icon: '🔒', text: 'ไม่ติดตามตำแหน่งเบื้องหลัง และไม่แชร์ให้ใครนอกจากร้านและไรเดอร์ของออเดอร์คุณ' },
          { icon: '✍️', text: 'ไม่อยากให้สิทธิ์ก็กรอกที่อยู่เองได้ (แต่จะส่งด้วยไรเดอร์ไม่ได้)' },
        ]}
        acceptLabel="อนุญาตและปักหมุด"
        declineLabel="กรอกเองดีกว่า"
        onAccept={async () => {
          setConsentVisible(false);
          await locate();
        }}
        onDecline={() => setConsentVisible(false)}
      />
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
  card: {
    marginBottom: spacing.md,
  },
  pinHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  pinIcon: {
    fontSize: 28,
  },
  pinRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  pinHint: {
    marginTop: spacing.md,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  fieldRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  unpin: {
    alignSelf: 'flex-start',
    marginTop: spacing.xs,
  },
  postal: {
    width: 130,
  },
  defaultRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.lg,
  },
});
