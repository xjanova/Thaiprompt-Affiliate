/**
 * RiderRegisterForm — ฟอร์มสมัครไรเดอร์ / ส่งใบสมัครใหม่หลังถูกปฏิเสธ / แก้ใบสมัครที่รอตรวจ
 *
 * ตรวจในเครื่องก่อนส่ง (ข้อความไทย) ให้ตรงกับกฎของ server:
 *   ชื่อ-นามสกุล · เบอร์ 0 + 8-9 หลัก · เลขบัตร 13 หลัก (checksum) · อายุ 18+ · ที่อยู่ ≥ 10 ตัวอักษร
 *   · ทะเบียนรถบังคับเมื่อใช้มอเตอร์ไซค์/รถยนต์
 * error ราย field จาก server (422 errors) แสดงใต้ช่องนั้นๆ
 */

import React, { useCallback, useMemo, useRef, useState } from 'react';
import { Alert, StyleSheet, View, type KeyboardTypeOptions } from 'react-native';
import { Text, TextInput } from '@/components/ui/Text';
import { router } from 'expo-router';
import { useTheme, spacing, typography } from '@/theme';
import { Button3D, Card3D, Chip, Icon, resultHaptic, type IconName } from '@/components/ui';
import { FocusInput, IconTile } from './RiderVisuals';
import {
  registerRider,
  type RiderRegisterBody,
  type RiderRegisterResponse,
  type RiderStatus,
  type RiderVehicleType,
} from '@/services/api/riderApi';
import {
  VEHICLES,
  ageFromIsoDate,
  digitsOnly,
  formatBirthDateInput,
  formatThaiIdInput,
  isValidThaiId,
  isValidThaiPhone,
  isoToThaiBirthInput,
  parseBirthDate,
} from './riderHelpers';

type FieldKey =
  | 'full_name'
  | 'phone'
  | 'id_card_number'
  | 'birth_date'
  | 'address'
  | 'province'
  | 'district'
  | 'vehicle_plate'
  | 'vehicle_brand'
  | 'vehicle_color';

interface FormState {
  full_name: string;
  phone: string;
  id_card_number: string;
  birth_date: string;
  address: string;
  province: string;
  district: string;
  vehicle_type: RiderVehicleType;
  vehicle_plate: string;
  vehicle_brand: string;
  vehicle_color: string;
}

export interface RiderRegisterFormProps {
  /** ข้อมูลเดิม (ส่งใบสมัครใหม่/แก้ใบสมัคร) */
  rider?: RiderStatus | null;
  defaultName?: string;
  defaultPhone?: string;
  onDone: (result: RiderRegisterResponse, message: string) => void;
  onCancel?: () => void;
}

const Field: React.FC<{
  label: string;
  value: string;
  onChangeText: (text: string) => void;
  error?: string;
  placeholder?: string;
  hint?: string;
  required?: boolean;
  keyboardType?: KeyboardTypeOptions;
  maxLength?: number;
  multiline?: boolean;
  autoCapitalize?: 'none' | 'sentences' | 'words' | 'characters';
  inputRef?: React.RefObject<TextInput | null>;
  onSubmitEditing?: () => void;
}> = ({
  label,
  value,
  onChangeText,
  error,
  placeholder,
  hint,
  required,
  keyboardType,
  maxLength,
  multiline,
  autoCapitalize,
  inputRef,
  onSubmitEditing,
}) => {
  const { colors } = useTheme();
  return (
    <View style={styles.field}>
      <Text style={[typography.bodySm, styles.label, { color: colors.textStrong }]}>
        {label}
        {required ? <Text style={{ color: colors.danger }}> *</Text> : null}
      </Text>
      <FocusInput
        ref={inputRef}
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        keyboardType={keyboardType}
        maxLength={maxLength}
        multiline={multiline}
        autoCapitalize={autoCapitalize}
        onSubmitEditing={onSubmitEditing}
        accessibilityLabel={label}
        invalid={!!error}
      />
      {!!error && (
        <View style={styles.messageRow}>
          <Icon name="warning-circle" size={14} color={colors.danger} weight="fill" />
          <Text style={[typography.caption, styles.flex, { color: colors.danger }]}>{error}</Text>
        </View>
      )}
      {!error && !!hint && <Text style={[typography.caption, { color: colors.textMuted }]}>{hint}</Text>}
    </View>
  );
};

/** หัวข้อกลุ่มในฟอร์ม (ไอคอนทอง + ชื่อกลุ่ม + เส้นบาง) */
const FormSection: React.FC<{ icon: IconName; title: string }> = ({ icon, title }) => {
  const { colors } = useTheme();
  return (
    <View style={styles.section}>
      <Icon name={icon} size={16} color={colors.goldDeep} weight="fill" />
      <Text style={[typography.overline, { color: colors.textMuted }]}>{title}</Text>
      <View style={[styles.sectionLine, { backgroundColor: colors.divider }]} />
    </View>
  );
};

export const RiderRegisterForm: React.FC<RiderRegisterFormProps> = ({
  rider,
  defaultName,
  defaultPhone,
  onDone,
  onCancel,
}) => {
  const { colors } = useTheme();
  const isReapply = !!rider;

  const [form, setForm] = useState<FormState>(() => ({
    full_name: rider?.full_name || defaultName || '',
    phone: rider?.phone || defaultPhone || '',
    id_card_number: '',
    birth_date: isoToThaiBirthInput(rider?.birth_date),
    address: rider?.address || '',
    province: rider?.province || '',
    district: rider?.district || '',
    vehicle_type: rider?.vehicle_type || 'motorcycle',
    vehicle_plate: rider?.vehicle_plate || '',
    vehicle_brand: rider?.vehicle_brand || '',
    vehicle_color: rider?.vehicle_color || '',
  }));
  const [errors, setErrors] = useState<Partial<Record<FieldKey, string>>>({});
  const submittingRef = useRef(false);

  const vehicle = useMemo(() => VEHICLES.find((v) => v.value === form.vehicle_type) || VEHICLES[0], [form.vehicle_type]);

  const set = useCallback(<K extends keyof FormState>(key: K, value: FormState[K]) => {
    setForm((prev) => ({ ...prev, [key]: value }));
    setErrors((prev) => (prev[key as FieldKey] ? { ...prev, [key]: undefined } : prev));
  }, []);

  const validate = (): { body: RiderRegisterBody | null; errors: Partial<Record<FieldKey, string>> } => {
    const e: Partial<Record<FieldKey, string>> = {};
    const name = form.full_name.trim().replace(/\s+/g, ' ');
    if (name.length < 4) e.full_name = 'กรอกชื่อและนามสกุลให้ครบ';
    if (!isValidThaiPhone(form.phone)) e.phone = 'เบอร์มือถือต้องขึ้นต้นด้วย 0 และมี 9-10 หลัก';
    if (!isValidThaiId(form.id_card_number)) {
      e.id_card_number = digitsOnly(form.id_card_number).length !== 13
        ? 'เลขบัตรประชาชนต้องมี 13 หลัก'
        : 'เลขบัตรประชาชนไม่ถูกต้อง ตรวจอีกครั้งนะ';
    }
    const birthIso = parseBirthDate(form.birth_date);
    if (!birthIso) {
      e.birth_date = 'กรอกวันเกิดแบบ วว/ดด/ปปปป เช่น 05/08/2540';
    } else {
      const age = ageFromIsoDate(birthIso);
      if (age < 18) e.birth_date = 'ต้องมีอายุ 18 ปีขึ้นไปจึงสมัครไรเดอร์ได้';
      else if (age > 80) e.birth_date = 'ตรวจวันเกิดอีกครั้งนะ';
    }
    if (form.address.trim().length < 10) e.address = 'กรอกที่อยู่ให้ละเอียด (อย่างน้อย 10 ตัวอักษร)';
    if (vehicle.needsPlate && form.vehicle_plate.trim().length < 2) e.vehicle_plate = 'กรอกทะเบียนรถ';

    if (Object.keys(e).length > 0 || !birthIso) return { body: null, errors: e };

    const body: RiderRegisterBody = {
      full_name: name,
      phone: digitsOnly(form.phone),
      id_card_number: digitsOnly(form.id_card_number),
      birth_date: birthIso,
      address: form.address.trim(),
      vehicle_type: form.vehicle_type,
    };
    if (form.province.trim()) body.province = form.province.trim();
    if (form.district.trim()) body.district = form.district.trim();
    if (vehicle.needsPlate) body.vehicle_plate = form.vehicle_plate.trim();
    if (form.vehicle_brand.trim()) body.vehicle_brand = form.vehicle_brand.trim();
    if (form.vehicle_color.trim()) body.vehicle_color = form.vehicle_color.trim();
    return { body, errors: {} };
  };

  const submit = async () => {
    if (submittingRef.current) return;
    const { body, errors: localErrors } = validate();
    if (!body) {
      setErrors(localErrors);
      resultHaptic('warning');
      return;
    }

    submittingRef.current = true;
    try {
      const result = await registerRider(body);
      if (result.success) {
        resultHaptic('success');
        onDone(result.data, result.message);
        return;
      }

      resultHaptic('error');
      if (result.code === 'VALIDATION_ERROR' && result.errors) {
        const mapped: Partial<Record<FieldKey, string>> = {};
        for (const [key, messages] of Object.entries(result.errors)) {
          const first = Array.isArray(messages) ? messages[0] : undefined;
          if (first) mapped[key as FieldKey] = first;
        }
        setErrors(mapped);
        if (Object.keys(mapped).length === 0) Alert.alert('ข้อมูลยังไม่ถูกต้อง', result.message);
        return;
      }
      if (result.code === 'ALREADY_REGISTERED' && result.data?.rider) {
        onDone({ outcome: 'updated', rider: result.data.rider }, result.message);
        return;
      }
      Alert.alert('ส่งใบสมัครไม่สำเร็จ', result.message);
    } finally {
      submittingRef.current = false;
    }
  };

  return (
    <Card3D style={styles.card} padding={spacing.lg + 2}>
      <View style={styles.header}>
        <IconTile icon="identification-card" tone="gold" size={48} />
        <View style={styles.flex}>
          <Text style={[typography.h2, { color: colors.textStrong }]}>
            {isReapply ? 'แก้ไขใบสมัครไรเดอร์' : 'กรอกข้อมูลสมัครไรเดอร์'}
          </Text>
          <Text style={[typography.bodySm, { color: colors.textMuted }]}>
            ใช้ข้อมูลจริงตามบัตรประชาชน ทีมงานจะตรวจสอบก่อนเปิดให้รับงาน
          </Text>
        </View>
      </View>

      <FormSection icon="user" title="ข้อมูลส่วนตัว" />
      <Field
        label="ชื่อ-นามสกุล"
        required
        value={form.full_name}
        onChangeText={(t) => set('full_name', t)}
        placeholder="เช่น สมชาย ใจดี"
        error={errors.full_name}
        maxLength={120}
      />
      <Field
        label="เบอร์มือถือ"
        required
        value={form.phone}
        onChangeText={(t) => set('phone', t.replace(/[^\d-]/g, '').slice(0, 12))}
        placeholder="08X-XXX-XXXX"
        keyboardType="phone-pad"
        error={errors.phone}
      />
      <Field
        label="เลขบัตรประชาชน"
        required
        value={form.id_card_number}
        onChangeText={(t) => set('id_card_number', formatThaiIdInput(t))}
        placeholder="X-XXXX-XXXXX-XX-X"
        keyboardType="number-pad"
        maxLength={17}
        error={errors.id_card_number}
        hint={
          isReapply && rider?.id_card_number_masked
            ? `เลขเดิม ${rider.id_card_number_masked} — กรอกใหม่อีกครั้งเพื่อยืนยัน`
            : 'ใช้ยืนยันตัวตนไรเดอร์เท่านั้น ไม่แสดงให้ผู้อื่นเห็น'
        }
      />
      <Field
        label="วันเกิด"
        required
        value={form.birth_date}
        onChangeText={(t) => set('birth_date', formatBirthDateInput(t))}
        placeholder="วว/ดด/ปปปป (พ.ศ.)"
        keyboardType="number-pad"
        maxLength={10}
        error={errors.birth_date}
        hint="ต้องมีอายุ 18 ปีขึ้นไป"
      />
      <FormSection icon="map-pin" title="ที่อยู่" />
      <Field
        label="ที่อยู่ปัจจุบัน"
        required
        value={form.address}
        onChangeText={(t) => set('address', t)}
        placeholder="บ้านเลขที่ ซอย ถนน ตำบล"
        multiline
        maxLength={500}
        error={errors.address}
      />
      <View style={styles.row}>
        <View style={styles.flex}>
          <Field
            label="อำเภอ/เขต"
            value={form.district}
            onChangeText={(t) => set('district', t)}
            placeholder="เช่น บางรัก"
            maxLength={100}
            error={errors.district}
          />
        </View>
        <View style={styles.flex}>
          <Field
            label="จังหวัด"
            value={form.province}
            onChangeText={(t) => set('province', t)}
            placeholder="เช่น กรุงเทพฯ"
            maxLength={100}
            error={errors.province}
          />
        </View>
      </View>

      <FormSection icon="moped" title="การเดินทาง" />
      <View style={styles.field}>
        <Text style={[typography.bodySm, styles.label, { color: colors.textStrong }]}>
          ยานพาหนะ<Text style={{ color: colors.danger }}> *</Text>
        </Text>
        <View style={styles.chips}>
          {VEHICLES.map((v) => (
            <Chip
              key={v.value}
              label={v.label}
              icon={v.icon}
              selected={form.vehicle_type === v.value}
              onPress={() => set('vehicle_type', v.value)}
              tone="gold"
            />
          ))}
        </View>
      </View>

      {vehicle.needsPlate && (
        <Field
          label="ทะเบียนรถ"
          required
          value={form.vehicle_plate}
          onChangeText={(t) => set('vehicle_plate', t)}
          placeholder="เช่น 1กข 1234"
          maxLength={20}
          error={errors.vehicle_plate}
        />
      )}
      {form.vehicle_type !== 'walk' && (
        <View style={styles.row}>
          <View style={styles.flex}>
            <Field
              label="ยี่ห้อ/รุ่น"
              value={form.vehicle_brand}
              onChangeText={(t) => set('vehicle_brand', t)}
              placeholder="เช่น Honda Wave"
              maxLength={50}
              error={errors.vehicle_brand}
            />
          </View>
          <View style={styles.flex}>
            <Field
              label="สี"
              value={form.vehicle_color}
              onChangeText={(t) => set('vehicle_color', t)}
              placeholder="เช่น แดง"
              maxLength={30}
              error={errors.vehicle_color}
            />
          </View>
        </View>
      )}

      <View style={[styles.privacy, { backgroundColor: colors.inset, borderColor: colors.border }]}>
        <Icon name="shield-check" size={18} color={colors.goldDeep} weight="fill" />
        <Text style={[typography.caption, styles.flex, { color: colors.textMuted }]}>
          กดส่งใบสมัคร = ยอมรับให้ทีมงานใช้ข้อมูลนี้ตรวจสอบตัวตนตาม{' '}
          <Text
            style={[styles.link, { color: colors.goldDeep }]}
            onPress={() => router.push('/privacy' as never)}
            suppressHighlighting
          >
            นโยบายความเป็นส่วนตัว
          </Text>
        </Text>
      </View>

      <Button3D
        title={isReapply ? 'ส่งใบสมัครอีกครั้ง' : 'ส่งใบสมัคร'}
        icon="paper-plane-tilt"
        size="lg"
        fullWidth
        onPress={submit}
        loadingText="กำลังส่ง..."
        style={styles.submit}
      />
      {!!onCancel && <Button3D title="ยกเลิก" variant="ghost" size="sm" onPress={onCancel} style={styles.cancel} />}
    </Card3D>
  );
};

const styles = StyleSheet.create({
  card: {
    marginBottom: spacing.lg,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  section: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.xl,
    marginBottom: spacing.xxs,
  },
  sectionLine: {
    flex: 1,
    height: 1,
  },
  field: {
    gap: 6,
    marginTop: spacing.md,
  },
  label: {
    fontWeight: '600',
  },
  messageRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 5,
  },
  privacy: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginTop: spacing.xl,
    padding: spacing.md,
    borderRadius: 14,
    borderWidth: 1,
  },
  link: {
    fontWeight: '600',
    textDecorationLine: 'underline',
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  flex: {
    flex: 1,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  submit: {
    marginTop: spacing.lg,
  },
  cancel: {
    alignSelf: 'center',
    marginTop: spacing.sm,
  },
});

export default RiderRegisterForm;
