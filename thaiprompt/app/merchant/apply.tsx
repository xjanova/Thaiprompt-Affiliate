/**
 * สมัครเปิดร้านค้า — GET/POST /seller/application (ตรรกะเดียวกับเว็บ /user/seller-apply)
 *
 * - สถานะคำขอ: ยื่นได้ / รอตรวจสอบ / ไม่ผ่าน (แสดงเหตุผล + ยื่นใหม่) / เป็นผู้ขายแล้ว / บัญชีนี้สมัครเองไม่ได้
 * - ยื่นใหม่หลังไม่ผ่าน: เติมข้อมูลเดิมให้ แก้แล้วส่งได้เลย (แถวร้านเดิม ไม่สร้างร้านซ้อน)
 * - ตรวจข้อมูลในจอก่อนส่ง (กฎเดียวกับ server) + ข้อความรายช่องจาก server เป็นภาษาไทย
 * - ออกจากหน้าโดยยังไม่ส่ง → ถามก่อนทิ้งข้อมูลที่กรอก
 * - ระหว่างรอแนะนำให้ยืนยันตัวตน (KYC) ไว้ก่อน ร้านจะเปิดขายได้ทันทีหลังอนุมัติ
 *
 * หน้าตา: การ์ดหัวภาพร้าน 3D → การ์ดสถานะ/เหตุผล → การ์ดฟอร์ม (ร้าน / ผู้ขาย / ที่อยู่) → ยอมรับเงื่อนไข → ปุ่มทอง
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, Pressable, StyleSheet, View } from 'react-native';
import { router, useNavigation } from 'expo-router';
import { usePreventRemove } from 'expo-router/react-navigation';
import { Text } from '@/components/ui/Text';
import { useAuthStore } from '@/stores/authStore';
import {
  getSellerApplication,
  submitSellerApplication,
  type SellerApplicationInput,
  type SellerApplicationStatus,
  type SellerBusinessType,
} from '@/services/api/sellerStoreApi';
import { BrandArt, Button3D, Card3D, Chip, EmptyState, Icon, Pill, Screen, resultHaptic } from '@/components/ui';
import { Field, formatThaiDateTime } from '@/components/shop';
import { IconTile, NoticeBanner } from '@/components/merchant';
import { ErrorNote, FormCard, FormSkeleton } from '@/components/seller';
import { useTheme, radii, spacing, typography } from '@/theme';

type FormKey =
  | 'store_name'
  | 'store_phone'
  | 'store_description'
  | 'store_address'
  | 'store_city'
  | 'store_state'
  | 'store_postal_code'
  | 'company_name'
  | 'tax_id';

type FormState = Record<FormKey, string>;
type Errors = Partial<Record<FormKey | 'accept_terms' | 'business_type', string>>;

const EMPTY_FORM: FormState = {
  store_name: '',
  store_phone: '',
  store_description: '',
  store_address: '',
  store_city: '',
  store_state: '',
  store_postal_code: '',
  company_name: '',
  tax_id: '',
};

const THAI = /[฀-๿]/;

/** ตรวจข้อมูลก่อนส่ง — กฎเดียวกับ SellerApplicationService::rules() */
const validate = (form: FormState, businessType: SellerBusinessType, accepted: boolean): Errors => {
  const e: Errors = {};
  const name = form.store_name.trim();
  if (!name) e.store_name = 'กรุณากรอกชื่อร้าน';
  else if (name.length < 2) e.store_name = 'ชื่อร้านสั้นเกินไป';
  if (!/^0[0-9]{8,9}$/.test(form.store_phone.trim())) e.store_phone = 'เบอร์โทรต้องเป็นตัวเลข 9-10 หลัก ขึ้นต้นด้วย 0';
  if (!form.store_address.trim()) e.store_address = 'กรุณากรอกที่อยู่ร้าน';
  if (!form.store_city.trim()) e.store_city = 'กรุณากรอกอำเภอ/เขต';
  if (!form.store_state.trim()) e.store_state = 'กรุณากรอกจังหวัด';
  if (!/^\d{5}$/.test(form.store_postal_code.trim())) e.store_postal_code = 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก';
  if (businessType === 'company') {
    if (!form.company_name.trim()) e.company_name = 'กรุณากรอกชื่อบริษัท';
    if (!/^\d{13}$/.test(form.tax_id.trim())) e.tax_id = 'เลขประจำตัวผู้เสียภาษีต้องเป็นตัวเลข 13 หลัก';
  }
  if (!accepted) e.accept_terms = 'กรุณายอมรับเงื่อนไขการเปิดร้านค้า';
  return e;
};

export default function SellerApplyScreen() {
  const { colors } = useTheme();
  const navigation = useNavigation();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [status, setStatus] = useState<SellerApplicationStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadError, setLoadError] = useState<string | null>(null);

  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [businessType, setBusinessType] = useState<SellerBusinessType>('individual');
  const [accepted, setAccepted] = useState(false);
  const [errors, setErrors] = useState<Errors>({});
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [dirty, setDirty] = useState(false);
  const [justSubmitted, setJustSubmitted] = useState(false);

  const mountedRef = useRef(true);
  const submittingRef = useRef(false);
  const prefilledRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  /** เติมข้อมูลคำขอเดิม (ยื่นใหม่หลังไม่ผ่าน) — ครั้งเดียว ไม่ทับที่ผู้ใช้กำลังพิมพ์ */
  const prefill = (s: SellerApplicationStatus) => {
    if (prefilledRef.current || !s.application || !s.can_submit) return;
    prefilledRef.current = true;
    const a = s.application;
    setForm({
      store_name: a.store_name || '',
      store_phone: a.store_phone || '',
      store_description: a.store_description || '',
      store_address: a.store_address || '',
      store_city: a.store_city || '',
      store_state: a.store_state || '',
      store_postal_code: a.store_postal_code || '',
      company_name: a.company_name || '',
      tax_id: a.tax_id || '',
    });
    setBusinessType(a.business_type);
  };

  const load = useCallback(
    async (mode: 'initial' | 'refresh') => {
      if (!isAuthenticated) {
        setLoading(false);
        return;
      }
      if (mode === 'refresh') setRefreshing(true);
      const res = await getSellerApplication();
      if (!mountedRef.current) return;
      setLoading(false);
      setRefreshing(false);
      if (res.success) {
        setStatus(res.data);
        setLoadError(null);
        prefill(res.data);
      } else if (mode === 'initial') {
        setLoadError(res.message);
      } else {
        Alert.alert('รีเฟรชไม่สำเร็จ', res.message);
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [isAuthenticated]
  );

  useEffect(() => {
    load('initial');
  }, [load]);

  // ---------- ออกจากหน้าโดยยังไม่ส่ง → ถามก่อน ----------
  usePreventRemove(dirty && !submitting, ({ data }) => {
    Alert.alert('ยังไม่ได้ส่งคำขอ', 'ออกจากหน้านี้แล้วข้อมูลที่กรอกจะหายไป', [
      { text: 'อยู่ต่อ', style: 'cancel' },
      { text: 'ออกโดยไม่ส่ง', style: 'destructive', onPress: () => navigation.dispatch(data.action) },
    ]);
  });

  const setField = (key: FormKey, value: string) => {
    setForm((prev) => ({ ...prev, [key]: value }));
    setErrors((prev) => (prev[key] ? { ...prev, [key]: undefined } : prev));
    setSubmitError(null);
    setDirty(true);
  };

  const submit = async () => {
    if (submittingRef.current) return;
    const found = validate(form, businessType, accepted);
    if (Object.keys(found).length > 0) {
      setErrors(found);
      setSubmitError('ตรวจข้อมูลที่ขึ้นสีแดงอีกครั้งนะ');
      resultHaptic('error');
      return;
    }

    const isCompany = businessType === 'company';
    const payload: SellerApplicationInput = {
      store_name: form.store_name.trim(),
      business_type: businessType,
      store_phone: form.store_phone.trim(),
      store_description: form.store_description.trim() || null,
      store_address: form.store_address.trim(),
      store_city: form.store_city.trim(),
      store_state: form.store_state.trim(),
      store_postal_code: form.store_postal_code.trim(),
      company_name: isCompany ? form.company_name.trim() : null,
      tax_id: isCompany ? form.tax_id.trim() : null,
      accept_terms: true,
    };

    submittingRef.current = true;
    setSubmitting(true);
    setSubmitError(null);
    const res = await submitSellerApplication(payload);
    submittingRef.current = false;
    if (!mountedRef.current) return;
    setSubmitting(false);

    if (res.success) {
      resultHaptic('success');
      setDirty(false);
      setJustSubmitted(true);
      setStatus(res.data);
      return;
    }

    resultHaptic('error');
    // สถานะเปลี่ยนไปแล้ว (เช่น ยื่นจากเว็บไปแล้ว) → แสดงสถานะล่าสุด
    if (res.code === 'APPLICATION_NOT_ALLOWED' && res.data && typeof res.data === 'object' && 'state' in res.data) {
      setDirty(false);
      setStatus(res.data as SellerApplicationStatus);
      Alert.alert('ส่งคำขอไม่ได้', res.message);
      return;
    }
    if (res.errors) {
      const fieldErrors: Errors = {};
      Object.entries(res.errors).forEach(([key, list]) => {
        const msg = Array.isArray(list) ? list[0] : null;
        if (typeof msg === 'string' && THAI.test(msg)) (fieldErrors as Record<string, string>)[key] = msg;
      });
      setErrors(fieldErrors);
    }
    setSubmitError(res.message);
  };

  // =====================================================
  // แสดงผล
  // =====================================================

  if (!isAuthenticated) {
    return (
      <Screen title="เปิดร้านค้า" scroll={false}>
        <EmptyState art="store" title="เข้าสู่ระบบก่อนนะ" message="เข้าสู่ระบบเพื่อสมัครเปิดร้านค้า" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (loading && !status) {
    return (
      <Screen title="เปิดร้านค้า" subtitle="ขายสินค้าให้สมาชิกทั่วประเทศ">
        <FormSkeleton sections={3} />
      </Screen>
    );
  }

  if (!status) {
    return (
      <Screen title="เปิดร้านค้า" scroll={false}>
        <EmptyState variant="error" message={loadError || 'โหลดข้อมูลไม่สำเร็จ'} onAction={() => load('initial')} secondaryActionLabel="กลับ" onSecondaryAction={() => router.back()} />
      </Screen>
    );
  }

  const kycHint = !status.kyc_approved && (
    <View style={[styles.kycBox, { backgroundColor: colors.infoSoft }]}>
      <IconTile icon="identification-card" tone="info" size={40} />
      <View style={styles.flex}>
        <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ยืนยันตัวตนไว้ก่อนได้เลย</Text>
        <Text style={[typography.caption, { color: colors.textMuted }]}>ร้านจะเริ่มขายได้ทันทีหลังอนุมัติ</Text>
      </View>
      <Button3D title="KYC" size="sm" variant="secondary" iconRight="arrow-right" onPress={() => router.push('/kyc')} />
    </View>
  );

  const renderStatus = () => {
    const app = status.application;
    switch (status.state) {
      case 'seller':
      case 'approved':
        return (
          <Card3D gradientBorder padding={spacing.xl} contentStyle={styles.centerBox}>
            <BrandArt name="store" size={112} />
            <Pill label="อนุมัติแล้ว" tone="success" icon="seal-check" size="md" style={styles.gapMd} />
            <Text style={[typography.serif, styles.centerText, styles.gapSm, { color: colors.textStrong }]}>คุณเป็นผู้ขายแล้ว</Text>
            <Text style={[typography.body, styles.centerText, styles.gapSm, { color: colors.textMuted }]}>
              {status.kyc_approved ? 'เริ่มลงสินค้าและรับออเดอร์ได้เลย' : 'ยืนยันตัวตน (KYC) แล้วเริ่มลงสินค้าได้เลย'}
            </Text>
            <Button3D title="ไปที่ร้านของฉัน" icon="storefront" iconRight="arrow-right" size="lg" fullWidth style={styles.cta} onPress={() => router.replace('/merchant' as never)} />
            {!status.kyc_approved && (
              <Button3D title="ยืนยันตัวตน (KYC)" icon="identification-card" variant="secondary" fullWidth style={styles.gapSm} onPress={() => router.push('/kyc')} />
            )}
          </Card3D>
        );
      case 'pending':
        return (
          <>
            {justSubmitted && <NoticeBanner tone="success" text="ส่งคำขอเรียบร้อย ทีมงานจะตรวจสอบและแจ้งผลทางการแจ้งเตือน" style={styles.block} />}
            <Card3D gradientBorder padding={spacing.xl}>
              <View style={styles.statusHead}>
                <IconTile icon="hourglass" tone="info" size={52} />
                <View style={styles.flex}>
                  <Pill label="รอตรวจสอบ" tone="info" icon="clock" />
                  <Text style={[typography.serif, styles.gapSm, { color: colors.textStrong }]} numberOfLines={2}>
                    {app?.store_name || 'คำขอเปิดร้าน'}
                  </Text>
                </View>
              </View>
              {!!app?.submitted_at && (
                <Text style={[typography.bodySm, styles.gapMd, { color: colors.textMuted }]}>ส่งคำขอเมื่อ {formatThaiDateTime(app.submitted_at)}</Text>
              )}
              <Text style={[typography.body, styles.gapSm, { color: colors.text }]}>ทีมงานจะแจ้งผลผ่านการแจ้งเตือนในแอปและบนเว็บ</Text>
              {kycHint}
            </Card3D>
          </>
        );
      case 'role_not_eligible':
        return (
          <EmptyState
            compact
            icon="info"
            title="บัญชีประเภทนี้สมัครเปิดร้านเองไม่ได้"
            message="บัญชีของคุณมีบทบาทเฉพาะอยู่แล้ว หากต้องการเปิดร้านค้าด้วย กรุณาติดต่อทีมงาน"
            actionLabel="ติดต่อทีมงาน"
            onAction={() => router.push('/support')}
          />
        );
      default:
        return null;
    }
  };

  const showForm = status.can_submit;
  const isCompany = businessType === 'company';

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <Screen
        title="เปิดร้านค้า"
        subtitle="ขายสินค้าให้สมาชิกทั่วประเทศ"
        refreshing={refreshing}
        onRefresh={dirty ? undefined : () => load('refresh')}
        right={dirty ? <Pill label="ยังไม่ส่ง" tone="warning" /> : undefined}
      >
        {!showForm && renderStatus()}

        {showForm && (
          <>
            {/* หัว: ภาพร้าน + ขั้นตอน */}
            <Card3D padding={spacing.lg} style={styles.block}>
              <View style={styles.introRow}>
                <BrandArt name="store" size={72} />
                <View style={styles.flex}>
                  <Text style={[typography.h3, { color: colors.textStrong }]}>เปิดร้านบนไทยพร๊อมท์</Text>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>
                    ส่งพัสดุหรือให้ไรเดอร์ในพื้นที่ไปส่งก็ได้
                  </Text>
                </View>
              </View>
              <View style={[styles.steps, { borderTopColor: colors.divider }]}>
                {['ส่งคำขอ', 'ทีมงานตรวจสอบ', 'ยืนยันตัวตน', 'เริ่มขาย'].map((label, i) => (
                  <View key={label} style={styles.step}>
                    <View style={[styles.stepBadge, { backgroundColor: colors.navyFill }]}>
                      <Text style={[typography.micro, { color: colors.goldLight }]}>{i + 1}</Text>
                    </View>
                    <Text numberOfLines={2} style={[typography.micro, styles.centerText, { color: colors.textMuted }]}>
                      {label}
                    </Text>
                  </View>
                ))}
              </View>
            </Card3D>

            {status.state === 'rejected' && (
              <Card3D padding={spacing.lg} style={styles.block}>
                <View style={styles.statusHead}>
                  <IconTile icon="x-circle" tone="danger" size={44} />
                  <View style={styles.flex}>
                    <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>คำขอครั้งก่อนยังไม่ผ่านการอนุมัติ</Text>
                    {!!status.rejection_reason && (
                      <Text style={[typography.bodySm, styles.gapXs, { color: colors.danger }]}>เหตุผล: {status.rejection_reason}</Text>
                    )}
                    <Text style={[typography.caption, styles.gapXs, { color: colors.textMuted }]}>แก้ไขข้อมูลด้านล่างแล้วยื่นใหม่ได้เลย</Text>
                  </View>
                </View>
              </Card3D>
            )}

            <FormCard icon="storefront" title="ข้อมูลร้าน">
              <Field
                label="ชื่อร้าน"
                required
                value={form.store_name}
                onChangeText={(v) => setField('store_name', v)}
                error={errors.store_name}
                placeholder="เช่น ร้านผักป้าแดง"
                maxLength={100}
              />
              <Field
                label="เบอร์โทรร้าน"
                required
                value={form.store_phone}
                onChangeText={(v) => setField('store_phone', v.replace(/[^0-9]/g, '').slice(0, 10))}
                error={errors.store_phone}
                placeholder="08xxxxxxxx"
                keyboardType="phone-pad"
                maxLength={10}
                autoComplete="tel"
              />
              <Field
                label="รายละเอียดร้าน (ถ้ามี)"
                value={form.store_description}
                onChangeText={(v) => setField('store_description', v)}
                error={errors.store_description}
                placeholder="ขายอะไร จุดเด่นของร้าน"
                multiline
                maxLength={1000}
              />
            </FormCard>

            <FormCard icon="identification-card" title="ประเภทผู้ขาย">
              <View style={styles.chipRow}>
                <Chip
                  label="บุคคลธรรมดา"
                  icon="user"
                  selected={!isCompany}
                  onPress={() => {
                    setBusinessType('individual');
                    setDirty(true);
                  }}
                />
                <Chip
                  label="นิติบุคคล"
                  icon="buildings"
                  selected={isCompany}
                  onPress={() => {
                    setBusinessType('company');
                    setDirty(true);
                  }}
                />
              </View>
              {isCompany && (
                <>
                  <Field
                    label="ชื่อบริษัท / หจก."
                    required
                    value={form.company_name}
                    onChangeText={(v) => setField('company_name', v)}
                    error={errors.company_name}
                    maxLength={255}
                  />
                  <Field
                    label="เลขประจำตัวผู้เสียภาษี (13 หลัก)"
                    required
                    value={form.tax_id}
                    onChangeText={(v) => setField('tax_id', v.replace(/[^0-9]/g, '').slice(0, 13))}
                    error={errors.tax_id}
                    keyboardType="number-pad"
                    maxLength={13}
                  />
                </>
              )}
            </FormCard>

            <FormCard icon="map-pin" title="ที่อยู่ร้าน">
              <Field
                label="ที่อยู่"
                required
                value={form.store_address}
                onChangeText={(v) => setField('store_address', v)}
                error={errors.store_address}
                placeholder="บ้านเลขที่ หมู่ ถนน ตำบล"
                multiline
                maxLength={500}
              />
              <View style={styles.fieldRow}>
                <Field
                  label="อำเภอ / เขต"
                  required
                  value={form.store_city}
                  onChangeText={(v) => setField('store_city', v)}
                  error={errors.store_city}
                  containerStyle={styles.flex}
                  maxLength={100}
                />
                <Field
                  label="จังหวัด"
                  required
                  value={form.store_state}
                  onChangeText={(v) => setField('store_state', v)}
                  error={errors.store_state}
                  containerStyle={styles.flex}
                  maxLength={100}
                />
              </View>
              <Field
                label="รหัสไปรษณีย์"
                required
                value={form.store_postal_code}
                onChangeText={(v) => setField('store_postal_code', v.replace(/[^0-9]/g, '').slice(0, 5))}
                error={errors.store_postal_code}
                keyboardType="number-pad"
                maxLength={5}
                containerStyle={styles.postal}
                autoComplete="postal-code"
              />
            </FormCard>

            {kycHint ? <View style={styles.block}>{kycHint}</View> : null}

            {/* ยอมรับเงื่อนไข */}
            <Pressable
              onPress={() => {
                setAccepted((v) => !v);
                setErrors((prev) => ({ ...prev, accept_terms: undefined }));
                setDirty(true);
              }}
              accessibilityRole="checkbox"
              accessibilityState={{ checked: accepted }}
              accessibilityLabel="ยืนยันว่าข้อมูลถูกต้อง และยอมรับข้อกำหนดการใช้บริการและนโยบายความเป็นส่วนตัว"
              style={[
                styles.terms,
                { backgroundColor: colors.card, borderColor: errors.accept_terms ? colors.danger : accepted ? colors.gold : colors.border },
              ]}
            >
              <View style={[styles.checkbox, { borderColor: accepted ? colors.gold : colors.border, backgroundColor: accepted ? colors.gold : 'transparent' }]}>
                {accepted && <Icon name="check" size={14} color={colors.textOnGold} weight="bold" />}
              </View>
              <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
                ฉันยืนยันว่าข้อมูลถูกต้อง และยอมรับ{' '}
                <Text style={[typography.bodySm, { color: colors.goldDeep }]} onPress={() => router.push('/terms')}>
                  ข้อกำหนดการใช้บริการ
                </Text>{' '}
                และ{' '}
                <Text style={[typography.bodySm, { color: colors.goldDeep }]} onPress={() => router.push('/privacy')}>
                  นโยบายความเป็นส่วนตัว
                </Text>
              </Text>
            </Pressable>
            {!!errors.accept_terms && (
              <Text style={[typography.caption, styles.gapXs, { color: colors.danger }]}>{errors.accept_terms}</Text>
            )}

            {!!submitError && <ErrorNote text={submitError} />}

            <Button3D
              title={status.state === 'rejected' ? 'ยื่นคำขอใหม่' : 'ส่งคำขอเปิดร้าน'}
              icon="paper-plane-tilt"
              size="lg"
              fullWidth
              loading={submitting}
              loadingText="กำลังส่งคำขอ..."
              onPress={submit}
              style={styles.cta}
            />
            <Text style={[typography.caption, styles.centerText, styles.gapSm, { color: colors.textFaint }]}>
              ทีมงานตรวจสอบภายใน 1-2 วันทำการ และแจ้งผลทางการแจ้งเตือน
            </Text>
          </>
        )}
      </Screen>
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
  centerBox: {
    alignItems: 'center',
  },
  centerText: {
    textAlign: 'center',
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
  cta: {
    marginTop: spacing.xl,
  },
  introRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  steps: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  step: {
    flex: 1,
    alignItems: 'center',
    gap: 6,
  },
  stepBadge: {
    width: 26,
    height: 26,
    borderRadius: 13,
    alignItems: 'center',
    justifyContent: 'center',
  },
  statusHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  kycBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.lg,
    borderRadius: radii.md,
    padding: spacing.md,
  },
  chipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  fieldRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  postal: {
    width: 160,
  },
  terms: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
    borderWidth: 1.5,
    borderRadius: radii.md,
    padding: spacing.md,
  },
  checkbox: {
    width: 22,
    height: 22,
    borderRadius: 7,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 1,
  },
});
