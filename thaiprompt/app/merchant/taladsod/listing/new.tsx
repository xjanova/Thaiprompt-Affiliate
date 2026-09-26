/**
 * ลงขายสินค้าใหม่ (ตลาดสด) — แทนหน้าเว็บ /taladsod/create-listing
 *
 * - โหลดค่าฟอร์มจาก GET /seller/listing-form (หมวดหมู่ หน่วย ความสด เพดานแคชแบ็ค อัตรา GP โควต้า)
 *   ลงขายเพิ่มไม่ได้ (ครบโควต้า/ร้านถูกระงับ) → บอกเหตุผลก่อนให้กรอก
 * - รูป (≤ 5 รูป รูปแรก = รูปหลัก) + ข้อมูลสินค้า + กลุ่มตัวเลือก → POST /listings ครั้งเดียว (multipart)
 * - ตรวจในเครื่องด้วยกฎเดียวกับ server · error ราย field จาก server แสดงใต้ช่องนั้น
 * - ระหว่างส่ง: ปุ่มหมุนโหลด + กันกดซ้ำ + "ยกเลิกการส่ง"
 *   ยกเลิก/เน็ตหลุด/ตอบช้า → ไม่รู้ว่า server บันทึกไปแล้วหรือยัง → เช็ครายการสินค้าก่อน (กันลงขายซ้ำสองรายการ)
 * - ยังไม่ได้ลงขายแล้วจะออก → ถามก่อนทิ้งข้อมูล (usePreventRemove ครอบการปัดขอบจอ iOS/ปุ่ม back Android)
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { router, useNavigation } from 'expo-router';
import { usePreventRemove } from 'expo-router/react-navigation';
import { useAuthStore } from '@/stores/authStore';
import { getFmSellerListings } from '@/services/api/taladsodSellerApi';
import {
  createFmListing,
  getFmListingForm,
  type FmListingForm,
} from '@/services/api/taladsodSellerManageApi';
import {
  Button3D,
  EmptyState,
  Icon,
  Pill,
  Screen,
  SectionHeader,
  WebsiteButton,
  resultHaptic,
} from '@/components/ui';
import { StickyBar } from '@/components/shop';
import {
  NoticeBanner,
  OptionGroupsEditor,
  fromDraft,
  type DraftGroup,
} from '@/components/merchant';
import {
  ListingDetailsForm,
  emptyListingDraft,
  listingBodyFromDraft,
  listingErrorsFromServer,
  parseMoney,
  serializeListingDraft,
  validateListingDraft,
  type ListingDraft,
  type ListingDraftErrors,
} from '@/components/merchant/ListingDetailsForm';
import { SkeletonBlock, SkeletonCard } from '@/components/merchant/SkeletonBlock';
import { chooseListingPhotos } from '@/components/merchant/listingPhotos';
import { useTheme, radii, spacing, typography } from '@/theme';

type LoadState =
  | { kind: 'loading' }
  | { kind: 'not_seller' }
  | { kind: 'error'; message: string }
  | { kind: 'ready'; form: FmListingForm };

/** หาสินค้าที่เพิ่งลงขาย (กรณีไม่รู้ผลการส่ง) — ชื่อตรงกัน + แก้ไขหลังเวลาเริ่มส่ง */
const findJustCreated = async (title: string, startedAt: number): Promise<number | null> => {
  const res = await getFmSellerListings({ q: title, per_page: 10 });
  if (!res.success) return null;
  const hit = res.data.listings.find((l) => {
    const at = l.updated_at ? new Date(l.updated_at).getTime() : 0;
    return l.title.trim() === title.trim() && Number.isFinite(at) && at >= startedAt - 60_000;
  });
  return hit ? hit.id : null;
};

export default function TaladsodNewListingScreen() {
  const { colors } = useTheme();
  const navigation = useNavigation();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [state, setState] = useState<LoadState>({ kind: 'loading' });
  const [refreshing, setRefreshing] = useState(false);

  const [images, setImages] = useState<string[]>([]);
  const [draft, setDraft] = useState<ListingDraft>(emptyListingDraft);
  const [errors, setErrors] = useState<ListingDraftErrors>({});
  const [groups, setGroups] = useState<DraftGroup[]>([]);
  const [groupsError, setGroupsError] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const mountedRef = useRef(true);
  const savedRef = useRef(false);
  const abortRef = useRef<AbortController | null>(null);
  const cancelledRef = useRef(false);
  const baseline = useMemo(() => serializeListingDraft(emptyListingDraft()), []);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      abortRef.current?.abort();
    };
  }, []);

  const load = useCallback(
    async (mode: 'initial' | 'refresh') => {
      if (!isAuthenticated) return;
      if (mode === 'refresh') setRefreshing(true);
      const res = await getFmListingForm();
      if (!mountedRef.current) return;
      setRefreshing(false);
      if (res.success) {
        setState({ kind: 'ready', form: res.data });
      } else if (res.code === 'NOT_SELLER') {
        setState({ kind: 'not_seller' });
      } else {
        // รีเฟรชแล้วล้ม → เก็บฟอร์มเดิมไว้ (ข้อมูลที่กรอกไม่หาย)
        setState((prev) => (prev.kind === 'ready' ? prev : { kind: 'error', message: res.message }));
      }
    },
    [isAuthenticated]
  );

  useEffect(() => {
    load('initial');
  }, [load]);

  const dirty = images.length > 0 || groups.length > 0 || serializeListingDraft(draft) !== baseline;

  usePreventRemove(dirty, ({ data }) => {
    // ลงขายสำเร็จแล้ว → ออกได้เลย
    if (savedRef.current) {
      navigation.dispatch(data.action);
      return;
    }
    if (submitting) {
      Alert.alert('กำลังลงขาย', 'รอให้ส่งเสร็จก่อน หรือกด "ยกเลิกการส่ง"');
      return;
    }
    Alert.alert('ยังไม่ได้ลงขาย', 'ออกจากหน้านี้แล้วข้อมูลและรูปที่เลือกไว้จะหายไป', [
      { text: 'อยู่ต่อ', style: 'cancel' },
      { text: 'ทิ้งข้อมูล', style: 'destructive', onPress: () => navigation.dispatch(data.action) },
    ]);
  });

  // ---------- รูป ----------
  const maxImages = state.kind === 'ready' ? state.form.max_images : 5;

  const addPhotos = async () => {
    if (submitting) return;
    const uris = await chooseListingPhotos(maxImages - images.length);
    if (!mountedRef.current || uris.length === 0) return;
    setImages((prev) => [...prev, ...uris.filter((u) => !prev.includes(u))].slice(0, maxImages));
    setFormError(null);
  };

  const onPhotoPress = (uri: string, index: number) => {
    if (submitting) return;
    const buttons: Array<{ text: string; style?: 'cancel' | 'destructive'; onPress?: () => void }> = [];
    if (index > 0) {
      buttons.push({ text: 'ตั้งเป็นรูปหลัก', onPress: () => setImages((prev) => [uri, ...prev.filter((u) => u !== uri)]) });
    }
    buttons.push({ text: 'เอารูปนี้ออก', style: 'destructive', onPress: () => setImages((prev) => prev.filter((u) => u !== uri)) });
    buttons.push({ text: 'ปิด', style: 'cancel' });
    Alert.alert('รูปสินค้า', index === 0 ? 'รูปนี้เป็นรูปหลักที่ลูกค้าเห็นก่อน' : undefined, buttons);
  };

  // ---------- ส่ง ----------
  const handleUnknownResult = async (title: string, startedAt: number, reason: 'cancel' | 'network') => {
    const createdId = await findJustCreated(title, startedAt);
    if (!mountedRef.current) return;
    if (createdId) {
      savedRef.current = true;
      resultHaptic('success');
      Alert.alert('ลงขายแล้ว', `พบ "${title}" ในรายการสินค้าแล้ว ไม่ต้องส่งซ้ำนะ`, [
        { text: 'ดูสินค้า', onPress: () => router.replace(`/merchant/taladsod/listing/${createdId}` as never) },
      ]);
      return;
    }
    setFormError(
      reason === 'cancel'
        ? 'ยกเลิกการส่งแล้ว ข้อมูลยังอยู่ครบ กดลงขายอีกครั้งเมื่อพร้อม'
        : 'ยังไม่แน่ใจว่าลงขายสำเร็จไหม ตรวจที่หน้าสินค้าของร้านก่อน ถ้ายังไม่มีสินค้านี้ค่อยกดลงขายอีกครั้ง'
    );
  };

  const submit = async () => {
    if (state.kind !== 'ready' || submitting) return;
    if (!state.form.can_create_listing) return;

    const found = validateListingDraft(draft, { mode: 'create', form: state.form });
    const { groups: optionGroups, error: optionError } = fromDraft(groups);
    setErrors(found);
    setGroupsError(optionError);
    if (Object.keys(found).length > 0 || optionError) {
      resultHaptic('warning');
      setFormError('ตรวจช่องที่มีข้อความสีแดงก่อนนะ');
      return;
    }
    setFormError(null);

    const body = listingBodyFromDraft(draft, 'create');
    const title = body.title as string;
    const startedAt = Date.now();
    const controller = new AbortController();
    abortRef.current = controller;
    cancelledRef.current = false;
    setSubmitting(true);

    const res = await createFmListing(
      {
        ...body,
        title,
        category_id: body.category_id as number,
        price: parseMoney(draft.priceText) as number,
        unit: body.unit as string,
        compare_at_price: body.compare_at_price ?? undefined,
        cashback_percentage: body.cashback_percentage ? body.cashback_percentage : undefined,
        freshness_level: body.freshness_level ?? undefined,
        imageUris: images,
        optionGroups,
      },
      { signal: controller.signal }
    );
    abortRef.current = null;
    if (!mountedRef.current) return;
    setSubmitting(false);

    if (res.success) {
      savedRef.current = true;
      resultHaptic('success');
      Alert.alert('ลงขายสำเร็จ', `"${res.data.title}" ขึ้นร้านแล้ว${res.data.is_available ? '' : ' (ยังปิดขายอยู่)'}`, [
        { text: 'แก้รายละเอียดต่อ', onPress: () => router.replace(`/merchant/taladsod/listing/${res.data.id}` as never) },
        { text: 'เสร็จแล้ว', onPress: () => router.back() },
      ]);
      return;
    }

    if (cancelledRef.current) {
      await handleUnknownResult(title, startedAt, 'cancel');
      return;
    }
    resultHaptic('error');
    if (res.code === 'TIMEOUT' || res.code === 'NETWORK_ERROR') {
      await handleUnknownResult(title, startedAt, 'network');
      return;
    }
    if (res.code === 'LISTING_LIMIT' || res.code === 'SELLER_SUSPENDED') {
      setState({ kind: 'ready', form: { ...state.form, can_create_listing: false, limit_message: res.message } });
      return;
    }
    if (res.code === 'NOT_SELLER') {
      setState({ kind: 'not_seller' });
      return;
    }
    const serverErrors = listingErrorsFromServer(res.errors);
    if (Object.keys(serverErrors).length > 0) setErrors(serverErrors);
    const optionMsg = res.errors && Object.keys(res.errors).find((k) => k.startsWith('option_groups'));
    if (optionMsg) setGroupsError(res.errors?.[optionMsg]?.[0] || res.message);
    setFormError(res.message);
  };

  const cancelSubmit = () => {
    if (!abortRef.current) return;
    cancelledRef.current = true;
    abortRef.current.abort();
  };

  // =====================================================
  // แสดงผล
  // =====================================================

  if (!isAuthenticated) {
    return (
      <Screen title="ลงขายสินค้าใหม่" scroll={false}>
        <EmptyState art="basket" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  const renderBody = () => {
    switch (state.kind) {
      case 'loading':
        return (
          <View style={styles.skeleton}>
            <View style={styles.photoRow}>
              <SkeletonBlock width={104} height={104} radius={radii.lg} />
              <SkeletonBlock width={104} height={104} radius={radii.lg} />
            </View>
            <SkeletonCard lines={4} withTile />
            <SkeletonCard lines={3} withTile />
          </View>
        );
      case 'not_seller':
        return (
          <EmptyState
            art="basket"
            title="ยังไม่มีร้านในตลาดสด"
            message="สมัครเปิดร้านก่อน แล้วค่อยลงขายสินค้าได้เลย"
            actionLabel="สมัครเปิดร้าน"
            onAction={() => router.replace('/merchant/taladsod/register' as never)}
          />
        );
      case 'error':
        return <EmptyState compact variant="error" message={state.message} onAction={() => load('initial')} />;
      case 'ready': {
        const { form } = state;
        if (!form.can_create_listing) {
          return (
            <View>
              <EmptyState
                compact
                icon="lock"
                title="ลงขายเพิ่มไม่ได้ตอนนี้"
                message={form.limit_message || 'ลงขายเพิ่มไม่ได้ในขณะนี้'}
                actionLabel="กลับไปหน้าสินค้า"
                onAction={() => router.back()}
              />
              <WebsiteButton path="/taladsod/seller-dashboard" label="ดูแพ็กเกจร้านบนเว็บไซต์" icon="storefront" variant="navy" fullWidth />
            </View>
          );
        }
        return (
          <>
            {/* ---------- รูป ---------- */}
            <SectionHeader title="รูปสินค้า" subtitle={`${images.length}/${form.max_images} รูป · รูปแรกคือรูปหลัก แตะรูปเพื่อจัดการ`} />
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.photoRow}>
              {images.map((uri, index) => (
                <Pressable
                  key={uri}
                  onPress={() => onPhotoPress(uri, index)}
                  disabled={submitting}
                  accessibilityRole="button"
                  accessibilityLabel={index === 0 ? 'รูปหลัก แตะเพื่อจัดการ' : `รูปที่ ${index + 1} แตะเพื่อจัดการ`}
                  style={({ pressed }) => [
                    styles.photo,
                    { backgroundColor: colors.inset, borderColor: index === 0 ? colors.gold : colors.border, opacity: pressed ? 0.75 : 1 },
                  ]}
                >
                  <Image source={{ uri }} style={styles.photoImage} contentFit="cover" transition={120} />
                  {index === 0 && <Pill label="รูปหลัก" icon="star" solid style={styles.mainBadge} />}
                </Pressable>
              ))}
              {images.length < form.max_images && (
                <Pressable
                  onPress={addPhotos}
                  disabled={submitting}
                  accessibilityRole="button"
                  accessibilityLabel="เพิ่มรูปสินค้า"
                  style={({ pressed }) => [
                    styles.photo,
                    styles.addTile,
                    { borderColor: colors.gold, backgroundColor: colors.goldSoft, opacity: pressed ? 0.75 : 1 },
                  ]}
                >
                  <View style={[styles.addCircle, { backgroundColor: colors.card }]}>
                    <Icon name="camera" size={20} color={colors.goldDeep} />
                  </View>
                  <Text style={[typography.caption, { color: colors.goldDeep }]}>เพิ่มรูป</Text>
                </Pressable>
              )}
            </ScrollView>
            {images.length === 0 && (
              <Text style={[typography.caption, styles.hint, { color: colors.textFaint }]}>
                ไม่บังคับ แต่สินค้าที่มีรูปขายได้ดีกว่า (รูปละไม่เกิน 5MB)
              </Text>
            )}

            {/* ---------- ข้อมูล ---------- */}
            <View style={styles.section}>
              <ListingDetailsForm
                mode="create"
                value={draft}
                onChange={setDraft}
                errors={errors}
                onClearError={(key) => setErrors((prev) => ({ ...prev, [key]: undefined }))}
                form={form}
                disabled={submitting}
              />
            </View>

            {/* ---------- ตัวเลือก ---------- */}
            <SectionHeader
              title="ตัวเลือกสินค้า (ไม่บังคับ)"
              subtitle="เช่น เลือกเนื้อสัตว์ หมู/ไก่ +0 กุ้ง +20 · เพิ่มไข่ดาว +10"
              style={styles.section}
            />
            <OptionGroupsEditor
              value={groups}
              onChange={(next) => {
                setGroups(next);
                setGroupsError(null);
              }}
              basePrice={parseMoney(draft.priceText) ?? 0}
              disabled={submitting}
            />
            {!!groupsError && <NoticeBanner tone="danger" text={groupsError} style={styles.block} />}
          </>
        );
      }
      default:
        return null;
    }
  };

  const ready = state.kind === 'ready' && state.form.can_create_listing;

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <Screen
        title="ลงขายสินค้าใหม่"
        subtitle="รูป ราคา หมวดหมู่ และตัวเลือก"
        right={dirty && !savedRef.current ? <Pill label="ยังไม่ได้ลงขาย" tone="warning" /> : undefined}
        refreshing={refreshing}
        onRefresh={submitting ? undefined : () => load('refresh')}
        contentStyle={ready ? styles.withBar : undefined}
      >
        {renderBody()}
      </Screen>

      {ready && (
        <StickyBar style={styles.bar}>
          {!!formError && <NoticeBanner tone={submitting ? 'info' : 'danger'} text={formError} style={styles.barNotice} />}
          <Button3D
            title="ลงขายสินค้า"
            icon="check-circle"
            size="lg"
            fullWidth
            loading={submitting}
            loadingText={images.length > 0 ? 'กำลังอัปโหลดรูปและลงขาย…' : 'กำลังลงขาย…'}
            onPress={submit}
          />
          {submitting && <Button3D title="ยกเลิกการส่ง" variant="ghost" size="sm" onPress={cancelSubmit} style={styles.center} />}
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
    paddingBottom: 190,
  },
  skeleton: {
    gap: spacing.lg,
  },
  section: {
    marginTop: spacing.xxl,
  },
  block: {
    marginTop: spacing.md,
  },
  hint: {
    marginTop: spacing.xs,
  },
  photoRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    paddingVertical: spacing.xs,
  },
  photo: {
    width: 104,
    height: 104,
    borderRadius: 18,
    borderWidth: 2,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  photoImage: {
    width: '100%',
    height: '100%',
  },
  mainBadge: {
    position: 'absolute',
    left: 6,
    bottom: 6,
  },
  addTile: {
    borderStyle: 'dashed',
    gap: spacing.xs,
  },
  addCircle: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bar: {
    gap: spacing.xs,
  },
  barNotice: {
    marginBottom: spacing.xs,
  },
  center: {
    alignSelf: 'center',
  },
});
