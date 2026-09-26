/**
 * ShopInfoForm — ฟอร์มข้อมูลร้านตลาดสด (สมัครร้าน / ตั้งค่าร้าน) ตรงกับฟอร์มเว็บ
 *
 * ช่อง: ชื่อร้าน* · แนะนำร้าน · เบอร์โทรร้าน* · ที่อยู่ร้าน* · ตำบล/อำเภอ/จังหวัด · หมุดร้าน* (พิกัด)
 * ตรวจในเครื่องด้วยกฎเดียวกับ server (ความยาว + รูปแบบเบอร์ /^[0-9+\-\s]{9,20}$/) — server ตรวจซ้ำอีกชั้น
 *
 * หมุดร้าน:
 *   - "ใช้ตำแหน่งปัจจุบัน" → อธิบายเหตุผลก่อน (prominent disclosure) → กล่องขอสิทธิ์ของระบบ → GPS
 *   - หรือปักเองบนแผนที่ (แตะ/ลากหมุด) ไม่ต้องให้สิทธิ์ตำแหน่ง
 *   - ได้พิกัดแล้วเติมตำบล/อำเภอ/จังหวัดที่ยังว่างให้ (ถ้าเครื่องแปลงชื่อสถานที่ได้)
 *
 * เป็น controlled component: หน้าจอเก็บ draft + errors แล้วส่งเอง (toShopBody)
 */

import React, { useEffect, useRef, useState } from 'react';
import { Alert, Linking, StyleSheet, View } from 'react-native';
import * as Location from 'expo-location';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, ConsentSheet, Icon, LiveMap, resultHaptic } from '@/components/ui';
import { Field } from '@/components/shop';
import { getCurrentCoords } from '@/services/location';
import type { FmShopInfoBody } from '@/services/api/taladsodSellerManageApi';
import { useTheme, radii, spacing, typography } from '@/theme';
import { IconTile } from './MerchantUi';

/** จุดกลางแผนที่ตอนยังไม่มีหมุด (กรุงเทพฯ) */
const DEFAULT_CENTER = { latitude: 13.7563, longitude: 100.5018 };
const MODAL_SETTLE_MS = 350;

export interface ShopDraft {
  shop_name: string;
  shop_description: string;
  phone: string;
  address: string;
  sub_district: string;
  district: string;
  province: string;
  latitude: number | null;
  longitude: number | null;
}

export type ShopDraftErrors = Partial<Record<keyof ShopDraft | 'pin', string>>;

export const emptyShopDraft = (phone: string = ''): ShopDraft => ({
  shop_name: '',
  shop_description: '',
  phone,
  address: '',
  sub_district: '',
  district: '',
  province: '',
  latitude: null,
  longitude: null,
});

/** ข้อมูลร้านจาก server → draft (ค่าว่าง = '') */
export const shopDraftFrom = (raw: {
  shop_name?: string | null;
  shop_description?: string | null;
  phone?: string | null;
  address?: string | null;
  sub_district?: string | null;
  district?: string | null;
  province?: string | null;
  latitude?: number | null;
  longitude?: number | null;
}): ShopDraft => ({
  shop_name: raw.shop_name || '',
  shop_description: raw.shop_description || '',
  phone: raw.phone || '',
  address: raw.address || '',
  sub_district: raw.sub_district || '',
  district: raw.district || '',
  province: raw.province || '',
  latitude: typeof raw.latitude === 'number' && Number.isFinite(raw.latitude) ? raw.latitude : null,
  longitude: typeof raw.longitude === 'number' && Number.isFinite(raw.longitude) ? raw.longitude : null,
});

/** สรุป draft เป็นข้อความ (เทียบว่ามีการแก้ไขหรือไม่) */
export const serializeShopDraft = (d: ShopDraft): string =>
  JSON.stringify([
    d.shop_name.trim(),
    d.shop_description.trim(),
    d.phone.trim(),
    d.address.trim(),
    d.sub_district.trim(),
    d.district.trim(),
    d.province.trim(),
    d.latitude === null ? null : d.latitude.toFixed(6),
    d.longitude === null ? null : d.longitude.toFixed(6),
  ]);

/** ตรวจตามกฎเดียวกับ server — คืน {} = ผ่าน */
export const validateShopDraft = (d: ShopDraft): ShopDraftErrors => {
  const e: ShopDraftErrors = {};
  const name = d.shop_name.trim();
  if (!name) e.shop_name = 'กรอกชื่อร้าน';
  else if (name.length > 200) e.shop_name = 'ชื่อร้านยาวได้ไม่เกิน 200 ตัวอักษร';
  if (d.shop_description.trim().length > 1000) e.shop_description = 'แนะนำร้านยาวได้ไม่เกิน 1,000 ตัวอักษร';
  if (!/^[0-9+\-\s]{9,20}$/.test(d.phone.trim())) e.phone = 'กรอกเบอร์โทรร้าน 9–20 ตัว (ตัวเลข + - เว้นวรรค)';
  const address = d.address.trim();
  if (!address) e.address = 'กรอกที่อยู่ร้าน';
  else if (address.length > 500) e.address = 'ที่อยู่ยาวได้ไม่เกิน 500 ตัวอักษร';
  (['sub_district', 'district', 'province'] as const).forEach((key) => {
    if (d[key].trim().length > 100) e[key] = 'ยาวได้ไม่เกิน 100 ตัวอักษร';
  });
  if (d.latitude === null || d.longitude === null) e.pin = 'ปักหมุดตำแหน่งร้านก่อนนะ (ใช้คำนวณระยะทางและเรียกไรเดอร์)';
  return e;
};

/** draft ที่ผ่านการตรวจแล้ว → body ของ API (ช่องไม่บังคับที่ว่าง = null เพื่อลบค่าเดิม) */
export const toShopBody = (d: ShopDraft): FmShopInfoBody => {
  const optional = (v: string) => (v.trim() ? v.trim() : null);
  return {
    shop_name: d.shop_name.trim(),
    shop_description: optional(d.shop_description),
    phone: d.phone.trim(),
    address: d.address.trim(),
    sub_district: optional(d.sub_district),
    district: optional(d.district),
    province: optional(d.province),
    latitude: Math.round((d.latitude as number) * 1e7) / 1e7,
    longitude: Math.round((d.longitude as number) * 1e7) / 1e7,
  };
};

/** error ราย field จาก server (422) → errors ของฟอร์ม */
export const shopErrorsFromServer = (errors: Record<string, string[]> | undefined): ShopDraftErrors => {
  const out: ShopDraftErrors = {};
  if (!errors) return out;
  const keys: Array<keyof ShopDraft> = ['shop_name', 'shop_description', 'phone', 'address', 'sub_district', 'district', 'province'];
  keys.forEach((key) => {
    const msg = errors[key]?.[0];
    if (msg) out[key] = msg;
  });
  const pin = errors.latitude?.[0] || errors.longitude?.[0];
  if (pin) out.pin = pin;
  return out;
};

const withTimeout = <T,>(promise: Promise<T>, ms: number): Promise<T | null> =>
  Promise.race([promise, new Promise<null>((resolve) => setTimeout(() => resolve(null), ms))]);

// =====================================================
// UI
// =====================================================

export interface ShopInfoFormProps {
  value: ShopDraft;
  onChange: (next: ShopDraft) => void;
  errors: ShopDraftErrors;
  /** ล้าง error ของช่องที่เพิ่งแก้ */
  onClearError: (key: keyof ShopDraftErrors) => void;
  disabled?: boolean;
}

export const ShopInfoForm: React.FC<ShopInfoFormProps> = ({ value, onChange, errors, onClearError, disabled = false }) => {
  const { colors } = useTheme();
  const [locating, setLocating] = useState(false);
  const [disclosure, setDisclosure] = useState(false);
  const [manualMap, setManualMap] = useState(false);
  const mountedRef = useRef(true);
  const valueRef = useRef(value);
  valueRef.current = value;

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const set = (key: keyof ShopDraft, text: string) => {
    onChange({ ...valueRef.current, [key]: text });
    if (errors[key]) onClearError(key);
  };

  const setPin = (latitude: number, longitude: number) => {
    onChange({ ...valueRef.current, latitude, longitude });
    if (errors.pin) onClearError('pin');
  };

  /** เติมตำบล/อำเภอ/จังหวัดที่ยังว่างจากชื่อสถานที่ (ไม่ทับค่าที่กรอกไว้) */
  const fillPlace = async (latitude: number, longitude: number) => {
    try {
      const places = await withTimeout(Location.reverseGeocodeAsync({ latitude, longitude }), 8000);
      const place = places?.[0];
      if (!place || !mountedRef.current) return;
      const cur = valueRef.current;
      onChange({
        ...cur,
        sub_district: cur.sub_district || (place.district ?? '') || '',
        district: cur.district || (place.subregion ?? '') || (place.city ?? '') || '',
        province: cur.province || (place.region ?? '') || '',
      });
    } catch {
      // แปลงชื่อสถานที่ไม่ได้ก็ไม่เป็นไร ปักหมุดได้แล้ว
    }
  };

  const locate = async () => {
    setLocating(true);
    try {
      const coords = await getCurrentCoords({ accuracy: 'high', timeoutMs: 10_000 });
      if (!mountedRef.current) return;
      if (!coords) {
        Alert.alert('หาตำแหน่งไม่เจอ', 'เปิด GPS หรือออกไปที่โล่งแล้วลองใหม่ หรือปักหมุดเองบนแผนที่ก็ได้');
        return;
      }
      const latitude = Math.round(coords.latitude * 1e7) / 1e7;
      const longitude = Math.round(coords.longitude * 1e7) / 1e7;
      setPin(latitude, longitude);
      setManualMap(true);
      resultHaptic('success');
      fillPlace(latitude, longitude);
    } finally {
      if (mountedRef.current) setLocating(false);
    }
  };

  /** ปุ่ม "ใช้ตำแหน่งปัจจุบัน": มีสิทธิ์แล้ว = หาเลย · ยังไม่มี = อธิบายก่อน · ระบบไม่ให้ถามซ้ำ = พาไปตั้งค่า */
  const onLocatePress = async () => {
    if (disabled || locating) return;
    try {
      const current = await Location.getForegroundPermissionsAsync();
      if (current.status === 'granted') {
        await locate();
        return;
      }
      if (current.canAskAgain === false) {
        Alert.alert('เปิดสิทธิ์ตำแหน่งก่อนนะ', 'ไปที่การตั้งค่าเครื่อง > ตำแหน่ง แล้วเลือก "ขณะใช้แอป" หรือปักหมุดเองบนแผนที่ก็ได้', [
          { text: 'ปักหมุดเอง', onPress: () => setManualMap(true) },
          { text: 'เปิดการตั้งค่า', onPress: () => Linking.openSettings().catch(() => {}) },
        ]);
        return;
      }
    } catch {
      // อ่านสิทธิ์ไม่ได้ → อธิบายก่อนแล้วลองขอ
    }
    setDisclosure(true);
  };

  const acceptDisclosure = async () => {
    setDisclosure(false);
    await new Promise((resolve) => setTimeout(resolve, MODAL_SETTLE_MS));
    try {
      const response = await Location.requestForegroundPermissionsAsync();
      if (!mountedRef.current) return;
      if (response.status === 'granted') {
        await locate();
      } else {
        Alert.alert('ยังไม่ได้อนุญาตตำแหน่ง', 'ปักหมุดร้านเองบนแผนที่ได้เลย แตะหรือลากหมุดไปที่ร้าน');
        setManualMap(true);
      }
    } catch {
      if (mountedRef.current) setManualMap(true);
    }
  };

  const hasPin = value.latitude !== null && value.longitude !== null;
  const center = hasPin ? { latitude: value.latitude as number, longitude: value.longitude as number } : DEFAULT_CENTER;

  return (
    <>
      <Card3D padding={spacing.lg}>
        <View style={styles.head}>
          <IconTile icon="storefront" />
          <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>ข้อมูลร้าน</Text>
        </View>
        <Field
          label="ชื่อร้าน"
          required
          value={value.shop_name}
          onChangeText={(t) => set('shop_name', t)}
          placeholder="เช่น กะเพราป้าแดง, ผักสวนลุงชม"
          maxLength={200}
          editable={!disabled}
          error={errors.shop_name}
        />
        <Field
          label="แนะนำร้าน"
          value={value.shop_description}
          onChangeText={(t) => set('shop_description', t)}
          placeholder="ขายอะไร เด่นเรื่องอะไร เปิดกี่โมง"
          maxLength={1000}
          multiline
          editable={!disabled}
          error={errors.shop_description}
        />
        <Field
          label="เบอร์โทรร้าน"
          required
          value={value.phone}
          onChangeText={(t) => set('phone', t.replace(/[^0-9+\-\s]/g, '').slice(0, 20))}
          placeholder="08x-xxx-xxxx"
          keyboardType="phone-pad"
          textContentType="telephoneNumber"
          autoComplete="tel"
          editable={!disabled}
          error={errors.phone}
          hint="ลูกค้าเห็นเบอร์หลังร้านรับออเดอร์แล้วเท่านั้น"
        />
      </Card3D>

      <Card3D padding={spacing.lg} style={styles.gap}>
        <View style={styles.head}>
          <IconTile icon="map-pin" />
          <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>ที่อยู่และหมุดร้าน</Text>
        </View>
        <Field
          label="ที่อยู่ร้าน"
          required
          value={value.address}
          onChangeText={(t) => set('address', t)}
          placeholder="บ้านเลขที่ ซอย ถนน หรือจุดสังเกต"
          maxLength={500}
          multiline
          editable={!disabled}
          error={errors.address}
        />
        <View style={styles.row}>
          <Field
            label="ตำบล/แขวง"
            value={value.sub_district}
            onChangeText={(t) => set('sub_district', t)}
            maxLength={100}
            editable={!disabled}
            error={errors.sub_district}
            containerStyle={styles.flex}
          />
          <Field
            label="อำเภอ/เขต"
            value={value.district}
            onChangeText={(t) => set('district', t)}
            maxLength={100}
            editable={!disabled}
            error={errors.district}
            containerStyle={styles.flex}
          />
        </View>
        <Field
          label="จังหวัด"
          value={value.province}
          onChangeText={(t) => set('province', t)}
          maxLength={100}
          editable={!disabled}
          error={errors.province}
        />

        <View style={styles.pinHead}>
          <Text style={[typography.caption, styles.pinLabel, { color: colors.textMuted }]}>
            ปักหมุดร้าน<Text style={{ color: colors.danger }}> *</Text>
          </Text>
          {hasPin && (
            <View style={styles.pinOk}>
              <Icon name="check-circle" size={14} color={colors.success} weight="fill" />
              <Text style={[typography.caption, { color: colors.success }]}>ปักหมุดแล้ว</Text>
            </View>
          )}
        </View>

        {hasPin || manualMap ? (
          <LiveMap
            markers={[{ id: 'shop', kind: 'shop', latitude: center.latitude, longitude: center.longitude, label: value.shop_name.trim() || 'ร้านของฉัน' }]}
            height={210}
            draggableId="shop"
            onPick={disabled ? undefined : ({ latitude, longitude }) => setPin(latitude, longitude)}
            openTargetId={hasPin ? 'shop' : null}
            caption={hasPin ? 'แตะแผนที่หรือลากหมุดเพื่อปรับให้ตรงร้าน' : 'แตะแผนที่ตรงที่ตั้งร้าน หรือลากหมุดไปวาง'}
            accessibilityLabel="แผนที่ปักหมุดร้าน"
          />
        ) : (
          <View style={[styles.pinEmpty, { backgroundColor: colors.inset, borderColor: errors.pin ? colors.danger : colors.border }]}>
            <Icon name="map-pin" size={26} color={colors.textFaint} />
            <Text style={[typography.bodySm, styles.center, { color: colors.textMuted }]}>
              ใช้คำนวณระยะทางและเรียกไรเดอร์มารับของ
            </Text>
          </View>
        )}

        <View style={styles.pinButtons}>
          <Button3D
            title={hasPin ? 'ปักใหม่ที่ตำแหน่งปัจจุบัน' : 'ใช้ตำแหน่งปัจจุบัน'}
            icon="crosshair"
            size="sm"
            variant="navy"
            loading={locating}
            loadingText="กำลังหาตำแหน่ง…"
            disabled={disabled}
            onPress={onLocatePress}
            style={styles.flex}
          />
          {!hasPin && !manualMap && (
            <Button3D title="ปักเองบนแผนที่" icon="map-trifold" size="sm" variant="secondary" disabled={disabled} onPress={() => setManualMap(true)} style={styles.flex} />
          )}
        </View>
        {!!errors.pin && (
          <View style={styles.pinError} accessibilityRole="alert">
            <Icon name="warning-circle" size={14} color={colors.danger} weight="fill" />
            <Text style={[typography.caption, styles.flex, { color: colors.danger }]}>{errors.pin}</Text>
          </View>
        )}
        <Text style={[typography.caption, styles.note, { color: colors.textFaint }]}>
          รถเข็น/ตลาดนัด: ปักที่จุดขายประจำหรือที่บ้านได้ ถ้าตั้งเป็น "ร้านเคลื่อนที่" ลูกค้าจะไม่เห็นหมุดนี้ เห็นเฉพาะตำแหน่งตอนเปิดร้าน
        </Text>
      </Card3D>

      <ConsentSheet
        visible={disclosure}
        icon="map-pin"
        title="ขอใช้ตำแหน่งเพื่อปักหมุดร้าน"
        description="ใช้ครั้งเดียวตอนคุณกดปุ่ม เพื่อหาพิกัดร้านให้ถูกต้อง"
        reasons={[
          { icon: 'crosshair', text: 'อ่านตำแหน่งตอนกด "ใช้ตำแหน่งปัจจุบัน" เท่านั้น ไม่ติดตามเบื้องหลัง' },
          { icon: 'storefront', text: 'ร้านประจำที่: ลูกค้าเห็นหมุดร้านเพื่อคำนวณระยะทางและค่าส่ง' },
          { icon: 'eye-slash', text: 'ร้านเคลื่อนที่: ลูกค้าไม่เห็นหมุดนี้ เห็นเฉพาะตำแหน่งตอนเปิดร้าน' },
        ]}
        acceptLabel="เข้าใจแล้ว ใช้ตำแหน่ง"
        declineLabel="ปักเองบนแผนที่"
        footnote='ถ้ามีกล่องขอสิทธิ์ถัดไป เลือก "ขณะใช้แอป" ได้เลย'
        onAccept={acceptDisclosure}
        onDecline={() => {
          setDisclosure(false);
          setManualMap(true);
        }}
      />
    </>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  gap: {
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
  center: {
    textAlign: 'center',
  },
  pinHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: spacing.lg,
    marginBottom: spacing.xs,
  },
  pinLabel: {
    fontWeight: '600',
  },
  pinOk: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  pinEmpty: {
    minHeight: 110,
    borderRadius: radii.md,
    borderWidth: 1,
    borderStyle: 'dashed',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xs,
    padding: spacing.lg,
  },
  pinButtons: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  pinError: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 5,
    marginTop: spacing.sm,
  },
  note: {
    marginTop: spacing.md,
  },
});

export default ShopInfoForm;
