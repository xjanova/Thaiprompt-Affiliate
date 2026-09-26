/**
 * ตั้งค่าร้านตลาดสด — แทนหน้าเว็บ /taladsod/seller/profile
 *
 * - GET /seller/profile → เติมฟอร์ม (ชื่อร้าน แนะนำร้าน เบอร์ ที่อยู่ ตำบล/อำเภอ/จังหวัด หมุดร้าน)
 * - PUT /seller/profile ส่งทุกช่อง (ช่องไม่บังคับที่ลบออก = null) → เติมฟอร์มใหม่จากผลของ server
 * - ร้านเคลื่อนที่/ประจำที่ สลับที่หน้าร้านของฉัน (การ์ดเปิดร้าน) — หน้านี้แค่บอกสถานะ
 * - ดึงลงเพื่อรีเฟรช: เติมฟอร์มใหม่เฉพาะตอนยังไม่ได้แก้ (ไม่ทับสิ่งที่กำลังพิมพ์)
 * - ยังไม่บันทึกแล้วจะออก → ถามก่อนทิ้งการแก้ไข
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { router, useNavigation } from 'expo-router';
import { usePreventRemove } from 'expo-router/react-navigation';
import { useAuthStore } from '@/stores/authStore';
import { getFmSellerProfile, updateFmSellerProfile } from '@/services/api/taladsodSellerManageApi';
import type { FmSellerProfile } from '@/services/api/taladsodSellerApi';
import { Button3D, Card3D, EmptyState, Pill, Screen, resultHaptic } from '@/components/ui';
import { StickyBar } from '@/components/shop';
import { IconTile, NoticeBanner } from '@/components/merchant';
import {
  ShopInfoForm,
  serializeShopDraft,
  shopDraftFrom,
  shopErrorsFromServer,
  toShopBody,
  validateShopDraft,
  type ShopDraft,
  type ShopDraftErrors,
} from '@/components/merchant/ShopInfoForm';
import { SkeletonCard } from '@/components/merchant/SkeletonBlock';
import { useTheme, spacing, typography } from '@/theme';

type LoadState = { kind: 'loading' } | { kind: 'not_seller' } | { kind: 'error'; message: string } | { kind: 'ready' };

export default function TaladsodShopProfileScreen() {
  const { colors } = useTheme();
  const navigation = useNavigation();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [state, setState] = useState<LoadState>({ kind: 'loading' });
  const [profile, setProfile] = useState<FmSellerProfile | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [draft, setDraft] = useState<ShopDraft | null>(null);
  const [baseline, setBaseline] = useState('');
  const [errors, setErrors] = useState<ShopDraftErrors>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const mountedRef = useRef(true);
  const noticeTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const dirtyRef = useRef(false);

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
    noticeTimerRef.current = setTimeout(() => mountedRef.current && setNotice(null), 3500);
  }, []);

  const applyProfile = (p: FmSellerProfile) => {
    const d = shopDraftFrom(p);
    setProfile(p);
    setDraft(d);
    setBaseline(serializeShopDraft(d));
    setErrors({});
    setFormError(null);
  };

  const load = useCallback(
    async (mode: 'initial' | 'refresh') => {
      if (!isAuthenticated) return;
      if (mode === 'refresh') setRefreshing(true);
      const res = await getFmSellerProfile();
      if (!mountedRef.current) return;
      setRefreshing(false);
      if (res.success) {
        if (mode === 'initial' || !dirtyRef.current) applyProfile(res.data);
        else setProfile(res.data);
        setState({ kind: 'ready' });
      } else if (res.code === 'NOT_SELLER') {
        setState({ kind: 'not_seller' });
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

  const dirty = !!draft && serializeShopDraft(draft) !== baseline;
  dirtyRef.current = dirty;

  usePreventRemove(dirty, ({ data }) => {
    if (saving) {
      Alert.alert('กำลังบันทึก', 'รอสักครู่ ระบบกำลังบันทึกข้อมูลร้าน');
      return;
    }
    Alert.alert('ยังไม่ได้บันทึก', 'มีการแก้ไขที่ยังไม่บันทึก ออกจากหน้านี้แล้วการแก้ไขจะหายไป', [
      { text: 'อยู่ต่อ', style: 'cancel' },
      { text: 'ทิ้งการแก้ไข', style: 'destructive', onPress: () => navigation.dispatch(data.action) },
    ]);
  });

  const save = async () => {
    if (!draft || saving || !dirty) return;
    const found = validateShopDraft(draft);
    setErrors(found);
    if (Object.keys(found).length > 0) {
      resultHaptic('warning');
      setFormError('ตรวจช่องที่มีข้อความสีแดงก่อนนะ');
      return;
    }
    setFormError(null);
    setSaving(true);
    const res = await updateFmSellerProfile(toShopBody(draft));
    if (!mountedRef.current) return;
    setSaving(false);
    if (res.success) {
      resultHaptic('success');
      applyProfile(res.data);
      flash(res.message || 'บันทึกข้อมูลร้านเรียบร้อยแล้ว');
      return;
    }
    resultHaptic('error');
    if (res.code === 'NOT_SELLER') {
      setState({ kind: 'not_seller' });
      return;
    }
    const serverErrors = shopErrorsFromServer(res.errors);
    if (Object.keys(serverErrors).length > 0) setErrors(serverErrors);
    setFormError(res.message);
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

  const renderBody = () => {
    switch (state.kind) {
      case 'loading':
        return (
          <View style={styles.skeleton}>
            <SkeletonCard lines={2} withTile />
            <SkeletonCard lines={4} withTile />
            <SkeletonCard lines={4} withTile />
          </View>
        );
      case 'not_seller':
        return (
          <EmptyState
            art="cart"
            title="ยังไม่มีร้านในตลาดสด"
            message="สมัครเปิดร้านก่อน แล้วค่อยตั้งค่าร้านได้"
            actionLabel="สมัครเปิดร้าน"
            onAction={() => router.replace('/merchant/taladsod/register' as never)}
          />
        );
      case 'error':
        return <EmptyState compact variant="error" message={state.message} onAction={() => load('initial')} />;
      case 'ready':
        if (!draft || !profile) return null;
        return (
          <>
            {!!notice && <NoticeBanner tone="success" text={notice} style={styles.block} />}

            {/* ---------- สถานะร้าน ---------- */}
            <Card3D padding={spacing.lg} shadow="sm">
              <View style={styles.statusRow}>
                <IconTile icon={profile.is_mobile ? 'shopping-cart-simple' : 'storefront'} tone="gold" />
                <View style={styles.flex}>
                  <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
                    {profile.is_mobile ? 'ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด)' : 'ร้านประจำที่'}
                  </Text>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>
                    {profile.is_mobile
                      ? 'ลูกค้าไม่เห็นหมุดด้านล่าง เห็นเฉพาะตำแหน่งตอนเปิดร้าน'
                      : 'ลูกค้าเห็นหมุดร้านด้านล่าง ใช้คำนวณระยะทางและค่าส่ง'}
                  </Text>
                </View>
              </View>
              <View style={styles.pills}>
                {profile.is_verified ? (
                  <Pill label="ร้านยืนยันแล้ว" tone="success" icon="seal-check" />
                ) : (
                  <Pill label="รอทีมงานยืนยัน" tone="warning" icon="hourglass" />
                )}
                {!!profile.status_label && <Pill label={profile.status_label} tone="neutral" />}
              </View>
              <Text style={[typography.caption, styles.note, { color: colors.textFaint }]}>
                สลับร้านเคลื่อนที่/ประจำที่ ได้ที่การ์ดเปิดร้านในหน้าร้านของฉัน
              </Text>
            </Card3D>

            <View style={styles.section}>
              <ShopInfoForm
                value={draft}
                onChange={(next) => {
                  setDraft(next);
                  setFormError(null);
                }}
                errors={errors}
                onClearError={(key) => setErrors((prev) => ({ ...prev, [key]: undefined }))}
                disabled={saving}
              />
            </View>
          </>
        );
      default:
        return null;
    }
  };

  const ready = state.kind === 'ready' && !!draft;

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <Screen
        title="ตั้งค่าร้าน"
        subtitle="ชื่อร้าน เบอร์โทร ที่อยู่ และหมุดร้าน"
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
            title={dirty ? 'บันทึกข้อมูลร้าน' : 'ข้อมูลร้านบันทึกแล้ว'}
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
    marginBottom: spacing.md,
  },
  section: {
    marginTop: spacing.lg,
  },
  statusRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.md,
  },
  note: {
    marginTop: spacing.sm,
  },
  bar: {
    gap: spacing.xs,
  },
});
