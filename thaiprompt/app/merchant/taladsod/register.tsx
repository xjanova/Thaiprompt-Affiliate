/**
 * สมัครเปิดร้านตลาดสด — แทนหน้าเว็บ /taladsod/register-seller และ /taladsod/start/seller
 *
 * - เปิดหน้า: เช็คก่อนว่ามีร้านแล้วหรือยัง (มีแล้ว → ไปหน้าร้านของฉันเลย)
 * - ฟอร์มเดียวกับเว็บ: ชื่อร้าน* แนะนำร้าน เบอร์โทร* ที่อยู่* ตำบล/อำเภอ/จังหวัด หมุดร้าน* + ยอมรับเงื่อนไขการขาย*
 *   ตรวจในเครื่องด้วยกฎเดียวกับ server · error ราย field จาก server แสดงใต้ช่องนั้น
 * - POST /seller/register (กดซ้ำ/สมัครไว้แล้ว = 409 SELLER_EXISTS → ถือว่าสำเร็จ พาไปหน้าร้าน)
 * - ยังไม่ได้ส่งแล้วจะออก → ถามก่อนทิ้งข้อมูล
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, Pressable, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { router, useNavigation } from 'expo-router';
import { usePreventRemove } from 'expo-router/react-navigation';
import { useAuthStore } from '@/stores/authStore';
import { getFmSellerProfile, registerFmSeller } from '@/services/api/taladsodSellerManageApi';
import { BrandArt, Button3D, Card3D, EmptyState, Icon, Screen, resultHaptic, selectionHaptic, type IconName } from '@/components/ui';
import { StickyBar } from '@/components/shop';
import { NoticeBanner } from '@/components/merchant';
import {
  ShopInfoForm,
  emptyShopDraft,
  serializeShopDraft,
  shopErrorsFromServer,
  toShopBody,
  validateShopDraft,
  type ShopDraft,
  type ShopDraftErrors,
} from '@/components/merchant/ShopInfoForm';
import { SkeletonCard } from '@/components/merchant/SkeletonBlock';
import { useTheme, radii, spacing, typography } from '@/theme';

const BENEFITS: Array<{ icon: IconName; title: string; text: string }> = [
  { icon: 'storefront', title: 'ขายได้ทุกแบบ', text: 'รถเข็น ตลาดนัด ร้านเล็ก ของสด หรืออาหารทำตามสั่ง' },
  { icon: 'map-pin', title: 'เปิดร้านตรงไหนก็ได้', text: 'กด "เปิดร้านที่นี่วันนี้" ลูกค้าแถวนั้นเห็นร้านทันที' },
  { icon: 'moped', title: 'รับออเดอร์ในแอป', text: 'ลูกค้ามารับเองหรือเรียกไรเดอร์ส่ง เงินเข้ากระเป๋าเมื่อจบออเดอร์' },
];

export default function TaladsodRegisterSellerScreen() {
  const { colors } = useTheme();
  const navigation = useNavigation();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const userPhone = useAuthStore((s) => s.user?.phone || '');

  const [checking, setChecking] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [draft, setDraft] = useState<ShopDraft>(() => emptyShopDraft(userPhone));
  const [baseline] = useState(() => serializeShopDraft(emptyShopDraft(userPhone)));
  const [errors, setErrors] = useState<ShopDraftErrors>({});
  const [agree, setAgree] = useState(false);
  const [agreeError, setAgreeError] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const mountedRef = useRef(true);
  const doneRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const goShop = useCallback(() => {
    doneRef.current = true;
    router.replace('/merchant/taladsod' as never);
  }, []);

  // มีร้านแล้ว (เช่น สมัครจากเว็บ/อีกเครื่อง) → ไปหน้าร้านเลย ไม่ให้กรอกซ้ำ
  // ดึงลงเพื่อรีเฟรช = เช็คซ้ำแบบเงียบ (ไม่ล้างฟอร์มที่กรอกอยู่)
  const check = useCallback(
    async (mode: 'initial' | 'refresh') => {
      if (!isAuthenticated) {
        setChecking(false);
        return;
      }
      if (mode === 'refresh') setRefreshing(true);
      const res = await getFmSellerProfile();
      if (!mountedRef.current) return;
      setChecking(false);
      setRefreshing(false);
      if (res.success) goShop();
    },
    [isAuthenticated, goShop]
  );

  useEffect(() => {
    check('initial');
  }, [check]);

  const dirty = agree || serializeShopDraft(draft) !== baseline;

  usePreventRemove(dirty, ({ data }) => {
    if (doneRef.current) {
      navigation.dispatch(data.action);
      return;
    }
    if (submitting) {
      Alert.alert('กำลังสมัคร', 'รอสักครู่ ระบบกำลังส่งข้อมูลร้าน');
      return;
    }
    Alert.alert('ยังไม่ได้สมัคร', 'ออกจากหน้านี้แล้วข้อมูลที่กรอกไว้จะหายไป', [
      { text: 'อยู่ต่อ', style: 'cancel' },
      { text: 'ออกโดยไม่สมัคร', style: 'destructive', onPress: () => navigation.dispatch(data.action) },
    ]);
  });

  const submit = async () => {
    if (submitting) return;
    const found = validateShopDraft(draft);
    setErrors(found);
    const agreeMissing = !agree;
    setAgreeError(agreeMissing ? 'กรุณายอมรับเงื่อนไขการขายก่อนสมัคร' : null);
    if (Object.keys(found).length > 0 || agreeMissing) {
      resultHaptic('warning');
      setFormError('ตรวจช่องที่มีข้อความสีแดงก่อนนะ');
      return;
    }
    setFormError(null);
    setSubmitting(true);
    const res = await registerFmSeller({ ...toShopBody(draft), agree_terms: true });
    if (!mountedRef.current) return;
    setSubmitting(false);

    if (res.success || res.code === 'SELLER_EXISTS') {
      resultHaptic('success');
      doneRef.current = true;
      const pending = res.success && !res.data.is_verified;
      Alert.alert(
        res.success ? 'สมัครเปิดร้านสำเร็จ' : 'มีร้านอยู่แล้ว',
        res.success
          ? pending
            ? 'ร้านของคุณรอทีมงานยืนยันก่อนสินค้าจะแสดงต่อลูกค้า ระหว่างนี้ลงเมนูรอไว้ได้เลย'
            : 'ลงเมนูแรก แล้วกด "เปิดร้านที่นี่วันนี้" เพื่อเริ่มขายได้เลย'
          : 'บัญชีนี้สมัครร้านตลาดสดไว้แล้ว พาไปหน้าร้านของคุณนะ',
        [{ text: 'ไปหน้าร้าน', onPress: goShop }],
        { cancelable: false }
      );
      return;
    }

    resultHaptic('error');
    const serverErrors = shopErrorsFromServer(res.errors);
    if (Object.keys(serverErrors).length > 0) setErrors(serverErrors);
    if (res.errors?.agree_terms?.[0]) setAgreeError(res.errors.agree_terms[0]);
    setFormError(res.message);
  };

  // =====================================================
  // แสดงผล
  // =====================================================

  if (!isAuthenticated) {
    return (
      <Screen title="สมัครเปิดร้านตลาดสด" scroll={false}>
        <EmptyState
          art="cart"
          title="เข้าสู่ระบบก่อนนะ"
          message="เข้าสู่ระบบเพื่อสมัครเปิดร้านในตลาดสด"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </Screen>
    );
  }

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <Screen
        title="สมัครเปิดร้านตลาดสด"
        subtitle="กรอก 2 นาที แล้วลงเมนูได้เลย"
        refreshing={refreshing}
        onRefresh={submitting || checking ? undefined : () => check('refresh')}
        contentStyle={styles.withBar}
      >
        {checking ? (
          <View style={styles.skeleton}>
            <SkeletonCard lines={3} withTile />
            <SkeletonCard lines={4} withTile />
            <SkeletonCard lines={3} withTile />
          </View>
        ) : (
          <>
            {/* ---------- แนะนำ ---------- */}
            <Card3D gradientBorder padding={spacing.xl} contentStyle={styles.intro}>
              <BrandArt name="cart" size={112} />
              <Text style={[typography.serif, styles.centerText, { color: colors.textStrong }]}>เปิดร้านในตลาดสดไทยพร้อม</Text>
              <View style={styles.benefits}>
                {BENEFITS.map((b) => (
                  <View key={b.title} style={styles.benefitRow}>
                    <View style={[styles.benefitIcon, { backgroundColor: colors.goldSoft }]}>
                      <Icon name={b.icon} size={20} color={colors.goldDeep} />
                    </View>
                    <View style={styles.flex}>
                      <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{b.title}</Text>
                      <Text style={[typography.caption, { color: colors.textMuted }]}>{b.text}</Text>
                    </View>
                  </View>
                ))}
              </View>
            </Card3D>

            <View style={styles.section}>
              <ShopInfoForm
                value={draft}
                onChange={setDraft}
                errors={errors}
                onClearError={(key) => setErrors((prev) => ({ ...prev, [key]: undefined }))}
                disabled={submitting}
              />
            </View>

            {/* ---------- เงื่อนไข ---------- */}
            <Card3D padding={spacing.lg} style={styles.section}>
              <Pressable
                onPress={() => {
                  if (submitting) return;
                  selectionHaptic();
                  setAgree((v) => !v);
                  setAgreeError(null);
                }}
                accessibilityRole="checkbox"
                accessibilityState={{ checked: agree, disabled: submitting }}
                accessibilityLabel="ยอมรับเงื่อนไขการใช้งานและเงื่อนไขการขายในตลาดสด"
                style={styles.agreeRow}
              >
                <View
                  style={[
                    styles.checkbox,
                    {
                      backgroundColor: agree ? colors.gold : colors.inset,
                      borderColor: agreeError ? colors.danger : agree ? colors.gold : colors.border,
                    },
                  ]}
                >
                  {agree && <Icon name="check" size={16} color={colors.textOnGold} weight="bold" />}
                </View>
                <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
                  ฉันยอมรับเงื่อนไขการใช้งาน และเงื่อนไขการขายในตลาดสด: ขายสินค้าที่ถูกกฎหมาย ปลอดภัย ราคาตรงกับหน้าร้าน
                  รับ/ยกเลิกออเดอร์ตามจริง และยินยอมให้หักค่าธรรมเนียมการขาย (GP) ตามที่ประกาศ
                </Text>
              </Pressable>
              <Button3D
                title="อ่านเงื่อนไขการใช้งาน"
                icon="file-text"
                variant="ghost"
                size="sm"
                onPress={() => router.push('/terms' as never)}
                style={styles.termsLink}
              />
              {!!agreeError && (
                <View style={styles.errorRow} accessibilityRole="alert">
                  <Icon name="warning-circle" size={14} color={colors.danger} weight="fill" />
                  <Text style={[typography.caption, styles.flex, { color: colors.danger }]}>{agreeError}</Text>
                </View>
              )}
              <Text style={[typography.caption, styles.note, { color: colors.textFaint }]}>
                สมัครแล้วตั้งเป็น "ร้านเคลื่อนที่" ได้ที่หน้าร้านของฉัน (สำหรับรถเข็น/ตลาดนัด)
              </Text>
            </Card3D>
          </>
        )}
      </Screen>

      {!checking && (
        <StickyBar style={styles.bar}>
          {!!formError && <NoticeBanner tone="danger" text={formError} />}
          <Button3D
            title="สมัครเปิดร้าน"
            icon="storefront"
            size="lg"
            fullWidth
            loading={submitting}
            loadingText="กำลังสมัคร…"
            disabled={!agree}
            onPress={submit}
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
    paddingBottom: 170,
  },
  skeleton: {
    gap: spacing.lg,
  },
  section: {
    marginTop: spacing.xl,
  },
  intro: {
    alignItems: 'center',
  },
  centerText: {
    textAlign: 'center',
  },
  benefits: {
    alignSelf: 'stretch',
    gap: spacing.md,
    marginTop: spacing.lg,
  },
  benefitRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  benefitIcon: {
    width: 44,
    height: 44,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  agreeRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
    minHeight: 44,
  },
  checkbox: {
    width: 26,
    height: 26,
    borderRadius: radii.xs,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 2,
  },
  termsLink: {
    alignSelf: 'flex-start',
    marginTop: spacing.sm,
  },
  errorRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 5,
    marginTop: spacing.xs,
  },
  note: {
    marginTop: spacing.md,
  },
  bar: {
    gap: spacing.xs,
  },
});
