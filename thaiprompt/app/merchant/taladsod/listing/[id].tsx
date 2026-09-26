/**
 * แก้สินค้าตลาดสด — รูป / ข้อมูลสินค้าทั้งหมด / กลุ่มตัวเลือก / ลบสินค้า (แทนหน้าเว็บ /taladsod/listings/{id}/edit)
 *
 * - รูป: ถ่ายรูปหรือเลือกจากคลัง → POST /listings/{id}/images · ตั้งรูปหลัก · ลบรูป (ถามก่อน)
 * - ข้อมูลสินค้า (ชื่อ รายละเอียด หมวดหมู่ ราคา ราคาก่อนลด หน่วย สต็อก เปิดขาย ความสด อินทรีย์ เงินคืน):
 *   PUT /listings/{id} ส่งเฉพาะช่องที่เปลี่ยน แล้วโหลดสินค้าใหม่จาก server
 * - ตัวเลือก: แก้ในจอแล้วกด "บันทึกตัวเลือก" ส่งทั้งชุด PUT /listings/{id}/option-groups
 * - ลบสินค้า: ถามยืนยันก่อน → DELETE /listings/{id} (มีออเดอร์ค้าง = ลบไม่ได้ บอกเหตุผล)
 * - ยังไม่บันทึกแล้วจะออกจากหน้า → ถามก่อนทิ้งการแก้ไข (usePreventRemove — ครอบการปัดขอบจอกลับบน iOS ด้วย)
 * - ดึงลงเพื่อรีเฟรช: อัปเดตรูป/สถิติ และเติมฟอร์มใหม่เฉพาะส่วนที่ยังไม่ได้แก้ (ไม่ทับสิ่งที่กำลังพิมพ์)
 *
 * หน้าตา: ชื่อสินค้าตัวมีเชิง + ป้ายสถานะ → แถบรูป (รูปหลักขอบทอง) → การ์ดข้อมูล/ราคา → ตัวเลือก → โซนลบ
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { router, useLocalSearchParams, useNavigation } from 'expo-router';
import { usePreventRemove } from 'expo-router/react-navigation';
import { useAuthStore } from '@/stores/authStore';
import {
  addFmListingImages,
  fmImageUrl,
  removeFmListingImage,
  saveFmOptionGroups,
  setFmListingMainImage,
  type FmOwnerListing,
} from '@/services/api/taladsodSellerApi';
import {
  deleteFmListing,
  getFmListingForm,
  getFmSellerListingFull,
  updateFmListingDetails,
  type FmListingForm,
  type FmOwnerListingFull,
} from '@/services/api/taladsodSellerManageApi';
import {
  Button3D,
  Card3D,
  EmptyState,
  Icon,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  resultHaptic,
} from '@/components/ui';
import {
  FM_LISTING_STATUS,
  IconTile,
  NoticeBanner,
  OptionGroupsEditor,
  fromDraft,
  toDraft,
  type DraftGroup,
} from '@/components/merchant';
import {
  ListingDetailsForm,
  listingDraftFrom,
  listingErrorsFromServer,
  listingPatch,
  parseMoney,
  serializeListingDraft,
  validateListingDraft,
  type ListingDraft,
  type ListingDraftErrors,
} from '@/components/merchant/ListingDetailsForm';
import { SkeletonBlock, SkeletonCard } from '@/components/merchant/SkeletonBlock';
import { chooseListingPhotos } from '@/components/merchant/listingPhotos';
import { FM_LIMITS } from '@/services/api/fmLimits';
import { useTheme, radii, spacing, typography } from '@/theme';

/** สรุป draft ตัวเลือกเป็นข้อความเพื่อเทียบว่ามีการแก้หรือไม่ (ไม่รวม key ภายใน) */
const serializeGroups = (draft: DraftGroup[]): string =>
  JSON.stringify(
    draft.map((g) => [
      g.id ?? null,
      g.name.trim(),
      g.selection_type,
      g.is_required,
      g.selection_type === 'multi' ? g.maxText.trim() : '',
      g.options.map((o) => [o.id ?? null, o.name.trim(), o.priceText.trim() || '0', o.is_available]),
    ])
  );

export default function TaladsodListingEditorScreen() {
  const { colors } = useTheme();
  const navigation = useNavigation();
  const params = useLocalSearchParams<{ id?: string }>();
  const listingId = useMemo(() => {
    const n = Number(params.id);
    return Number.isInteger(n) && n > 0 ? n : null;
  }, [params.id]);
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [listing, setListing] = useState<FmOwnerListingFull | null>(null);
  const [form, setForm] = useState<FmListingForm | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadError, setLoadError] = useState<string | null>(null);

  const [draft, setDraft] = useState<ListingDraft | null>(null);
  const [baseDraft, setBaseDraft] = useState<ListingDraft | null>(null);
  const [errors, setErrors] = useState<ListingDraftErrors>({});
  const [detailsError, setDetailsError] = useState<string | null>(null);
  const [detailsSaving, setDetailsSaving] = useState(false);

  const [groups, setGroups] = useState<DraftGroup[]>([]);
  const [groupsBaseline, setGroupsBaseline] = useState('[]');
  const [groupsError, setGroupsError] = useState<string | null>(null);
  const [groupsSaving, setGroupsSaving] = useState(false);

  const [imageBusy, setImageBusy] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);

  const mountedRef = useRef(true);
  const leavingRef = useRef(false);
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
    noticeTimerRef.current = setTimeout(() => mountedRef.current && setNotice(null), 3500);
  }, []);

  const detailsDirty = !!draft && !!baseDraft && serializeListingDraft(draft) !== serializeListingDraft(baseDraft);
  const groupsDirty = serializeGroups(groups) !== groupsBaseline;
  const dirty = detailsDirty || groupsDirty;

  // ref ของสถานะ "แก้อยู่" — ให้ load() ที่เรียกจาก pull-to-refresh ไม่ทับสิ่งที่กำลังแก้
  const dirtyRef = useRef({ details: false, groups: false });
  dirtyRef.current = { details: detailsDirty, groups: groupsDirty };

  const resetDetails = (l: FmOwnerListingFull) => {
    const d = listingDraftFrom(l);
    setDraft(d);
    setBaseDraft(d);
    setErrors({});
    setDetailsError(null);
  };
  const resetGroups = (l: FmOwnerListing) => {
    const d = toDraft(l.option_groups);
    setGroups(d);
    setGroupsBaseline(serializeGroups(d));
    setGroupsError(null);
  };

  const load = useCallback(
    async (mode: 'initial' | 'refresh') => {
      if (!listingId || !isAuthenticated) {
        setLoading(false);
        return;
      }
      if (mode === 'refresh') setRefreshing(true);
      const [res, formRes] = await Promise.all([getFmSellerListingFull(listingId), getFmListingForm()]);
      if (!mountedRef.current) return;
      setLoading(false);
      setRefreshing(false);
      if (res.success && formRes.success) {
        setListing(res.data);
        setForm(formRes.data);
        if (mode === 'initial' || !dirtyRef.current.details) resetDetails(res.data);
        if (mode === 'initial' || !dirtyRef.current.groups) resetGroups(res.data);
        setLoadError(null);
      } else {
        const failure = !res.success ? res : !formRes.success ? formRes : null;
        if (mode === 'refresh') {
          flash(failure?.message || 'โหลดข้อมูลใหม่ไม่สำเร็จ');
        } else {
          setLoadError(failure?.message || 'เปิดสินค้านี้ไม่ได้');
        }
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [listingId, isAuthenticated]
  );

  useEffect(() => {
    load('initial');
  }, [load]);

  // ออกจากหน้าโดยยังไม่บันทึก → ถามก่อน (ปุ่มกลับ / ปัดขอบจอ iOS / ปุ่ม back Android)
  usePreventRemove(dirty, ({ data }) => {
    if (leavingRef.current) {
      navigation.dispatch(data.action);
      return;
    }
    Alert.alert('ยังไม่ได้บันทึก', 'มีการแก้ไขที่ยังไม่บันทึก ออกจากหน้านี้แล้วการแก้ไขจะหายไป', [
      { text: 'อยู่ต่อ', style: 'cancel' },
      { text: 'ทิ้งการแก้ไข', style: 'destructive', onPress: () => navigation.dispatch(data.action) },
    ]);
  });

  // ---------- บันทึกข้อมูลสินค้า ----------
  const saveDetails = async () => {
    if (!listing || !draft || !baseDraft || !form || detailsSaving) return;
    const found = validateListingDraft(draft, { mode: 'edit', form });
    setErrors(found);
    if (Object.keys(found).length > 0) {
      resultHaptic('warning');
      setDetailsError('ตรวจช่องที่มีข้อความสีแดงก่อนนะ');
      return;
    }
    const patch = listingPatch(draft, baseDraft);
    if (!patch) return;
    // สินค้าถูกระงับ: server ไม่เปลี่ยนสถานะเปิดขายให้ → ไม่ส่งไป
    if (listing.status === 'suspended') delete patch.is_available;

    setDetailsSaving(true);
    setDetailsError(null);
    const res = await updateFmListingDetails(listing.id, patch);
    if (!mountedRef.current) return;
    if (!res.success) {
      setDetailsSaving(false);
      resultHaptic('error');
      const serverErrors = listingErrorsFromServer(res.errors);
      if (Object.keys(serverErrors).length > 0) setErrors(serverErrors);
      setDetailsError(res.message);
      return;
    }
    // ผลของ PUT ไม่มีหมวดหมู่/เงินคืน → โหลดสินค้าเต็มอีกครั้งเพื่อเติมฟอร์มให้ตรง server
    const fresh = await getFmSellerListingFull(listing.id);
    if (!mountedRef.current) return;
    setDetailsSaving(false);
    resultHaptic('success');
    if (fresh.success) {
      setListing(fresh.data);
      resetDetails(fresh.data);
    } else {
      setListing((prev) => (prev ? { ...prev, ...res.data, category_id: prev.category_id, cashback_percentage: prev.cashback_percentage } : prev));
      setBaseDraft(draft);
    }
    flash('บันทึกข้อมูลสินค้าแล้ว ลูกค้าเห็นทันที');
  };

  // ---------- บันทึกตัวเลือก ----------
  const saveGroups = async () => {
    if (!listing || groupsSaving) return;
    const { groups: payload, error } = fromDraft(groups);
    if (error) {
      setGroupsError(error);
      resultHaptic('warning');
      return;
    }
    setGroupsSaving(true);
    setGroupsError(null);
    const res = await saveFmOptionGroups(listing.id, payload);
    if (!mountedRef.current) return;
    setGroupsSaving(false);
    if (res.success) {
      resultHaptic('success');
      const next = { ...listing, option_groups: res.data.option_groups, has_options: res.data.option_groups.length > 0 };
      setListing(next);
      resetGroups(next);
      flash('บันทึกตัวเลือกแล้ว ลูกค้าเห็นทันที');
    } else {
      resultHaptic('error');
      setGroupsError(res.message);
    }
  };

  // ---------- รูป ----------
  /** อัปเดตเฉพาะรูปจากผลของ server (ไม่ทับฟอร์ม/ตัวเลือกที่กำลังแก้) */
  const applyImages = (fresh: FmOwnerListing) =>
    setListing((prev) => (prev ? { ...prev, images: fresh.images, main_image_url: fresh.main_image_url } : prev));

  const addImages = async () => {
    if (!listing || imageBusy) return;
    const uris = await chooseListingPhotos(FM_LIMITS.MAX_IMAGES - listing.images.length);
    if (uris.length === 0 || !mountedRef.current) return;
    setImageBusy(true);
    const res = await addFmListingImages(listing.id, uris);
    if (!mountedRef.current) return;
    setImageBusy(false);
    if (res.success) {
      resultHaptic('success');
      applyImages(res.data);
      flash('อัปโหลดรูปแล้ว');
    } else {
      resultHaptic('error');
      Alert.alert('อัปโหลดรูปไม่สำเร็จ', res.message);
    }
  };

  const doImageAction = async (kind: 'main' | 'remove', url: string) => {
    if (!listing || imageBusy) return;
    setImageBusy(true);
    const res = kind === 'main' ? await setFmListingMainImage(listing.id, url) : await removeFmListingImage(listing.id, url);
    if (!mountedRef.current) return;
    setImageBusy(false);
    if (res.success) {
      resultHaptic('success');
      applyImages(res.data);
      flash(kind === 'main' ? 'ตั้งรูปหลักแล้ว' : 'ลบรูปแล้ว');
    } else {
      Alert.alert('ทำรายการไม่สำเร็จ', res.message);
    }
  };

  const onImagePress = (url: string, isMain: boolean) => {
    if (imageBusy) return;
    const buttons: Array<{ text: string; style?: 'cancel' | 'destructive'; onPress?: () => void }> = [];
    if (!isMain) buttons.push({ text: 'ตั้งเป็นรูปหลัก', onPress: () => doImageAction('main', url) });
    buttons.push({
      text: 'ลบรูปนี้',
      style: 'destructive',
      onPress: () =>
        Alert.alert('ลบรูปนี้?', 'ลบแล้วต้องอัปโหลดใหม่ถ้าอยากได้คืน', [
          { text: 'ไม่ลบ', style: 'cancel' },
          { text: 'ลบรูป', style: 'destructive', onPress: () => doImageAction('remove', url) },
        ]),
    });
    buttons.push({ text: 'ปิด', style: 'cancel' });
    Alert.alert('รูปสินค้า', isMain ? 'รูปนี้เป็นรูปหลักที่ลูกค้าเห็นก่อน' : undefined, buttons);
  };

  // ---------- ลบสินค้า ----------
  const doDelete = async () => {
    if (!listing || deleting) return;
    setDeleting(true);
    const res = await deleteFmListing(listing.id);
    if (!mountedRef.current) return;
    setDeleting(false);
    // ไม่พบสินค้าแล้ว = ถูกลบไปแล้ว (เช่น กดลบครั้งก่อนสำเร็จแต่เน็ตหลุดก่อนได้คำตอบ) → ถือว่าลบสำเร็จ
    if (res.success || res.code === 'LISTING_NOT_FOUND') {
      resultHaptic('success');
      leavingRef.current = true;
      Alert.alert('ลบสินค้าแล้ว', `"${listing.title}" ถูกลบออกจากร้านแล้ว`, [{ text: 'ตกลง', onPress: () => router.back() }], {
        cancelable: false,
      });
      return;
    }
    resultHaptic('error');
    Alert.alert(
      'ลบไม่สำเร็จ',
      res.code === 'LISTING_HAS_ACTIVE_ORDERS'
        ? `${res.message}\nถ้าไม่อยากให้ลูกค้าสั่งเพิ่ม ปิดสวิตช์ "เปิดขาย" แทนได้เลย`
        : res.message
    );
  };

  const confirmDelete = () => {
    if (!listing || deleting) return;
    Alert.alert(
      'ลบสินค้านี้?',
      `ลบ "${listing.title}" พร้อมรูปและตัวเลือกทั้งหมด กู้คืนไม่ได้\nถ้าแค่อยากหยุดขายชั่วคราว ปิดสวิตช์ "เปิดขาย" แทนได้`,
      [
        { text: 'ไม่ลบ', style: 'cancel' },
        { text: 'ลบสินค้า', style: 'destructive', onPress: () => doDelete() },
      ]
    );
  };

  // =====================================================
  // แสดงผล
  // =====================================================

  if (!isAuthenticated) {
    return (
      <Screen title="แก้สินค้า" scroll={false}>
        <EmptyState art="cart" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (loading && !listing) {
    return (
      <Screen title="แก้สินค้า">
        <View style={styles.skeleton}>
          <SkeletonBlock width="70%" height={26} />
          <View style={styles.images}>
            <SkeletonBlock width={112} height={112} radius={radii.lg} />
            <SkeletonBlock width={112} height={112} radius={radii.lg} />
          </View>
          <SkeletonCard lines={4} withTile />
          <SkeletonCard lines={3} withTile />
        </View>
      </Screen>
    );
  }

  if (!listing || !draft || !form) {
    return (
      <Screen title="แก้สินค้า" scroll={false}>
        <EmptyState
          variant="error"
          title="เปิดสินค้านี้ไม่ได้"
          message={loadError || 'ไม่พบสินค้านี้'}
          actionLabel="ลองใหม่"
          onAction={() => {
            setLoading(true);
            return load('initial');
          }}
          secondaryActionLabel="กลับไปรายการสินค้า"
          onSecondaryAction={() => router.back()}
        />
      </Screen>
    );
  }

  const st = FM_LISTING_STATUS[listing.status] || { label: listing.status, tone: 'neutral' as const };
  const mainPath = listing.main_image_url || listing.images[0] || null;
  const previewPrice = parseMoney(draft.priceText) ?? listing.price;
  const suspended = listing.status === 'suspended';
  const busy = detailsSaving || groupsSaving || deleting;

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <Screen
        title="แก้สินค้า"
        subtitle={listing.title}
        right={dirty ? <Pill label="ยังไม่บันทึก" tone="warning" /> : undefined}
        refreshing={refreshing}
        onRefresh={busy ? undefined : () => load('refresh')}
      >
        {!!notice && <NoticeBanner tone="success" text={notice} style={styles.block} />}

        {/* ---------- หัว ---------- */}
        <View style={styles.headRow}>
          <Text style={[typography.serif, styles.flex, { color: colors.textStrong }]} numberOfLines={2}>
            {listing.title}
          </Text>
          <Pill label={st.label} tone={st.tone} size="md" />
        </View>
        {suspended && (
          <View style={[styles.suspended, { backgroundColor: colors.dangerSoft }]}>
            <Icon name="prohibit" size={18} color={colors.danger} />
            <Text style={[typography.bodySm, styles.flex, { color: colors.danger }]}>
              สินค้านี้ถูกระงับโดยทีมงาน เปิดขายเองไม่ได้ ติดต่อทีมงานได้ที่หน้าช่วยเหลือ
            </Text>
          </View>
        )}

        {/* ---------- รูป ---------- */}
        <SectionHeader
          title="รูปสินค้า"
          subtitle={`${listing.images.length}/${FM_LIMITS.MAX_IMAGES} รูป · แตะรูปเพื่อตั้งรูปหลักหรือลบ`}
          style={styles.section}
        />
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.images}>
          {listing.images.map((path) => {
            const url = fmImageUrl(path);
            const isMain = path === mainPath;
            return (
              <Pressable
                key={path}
                onPress={() => onImagePress(path, isMain)}
                disabled={imageBusy}
                accessibilityRole="button"
                accessibilityLabel={isMain ? 'รูปหลัก แตะเพื่อจัดการ' : 'รูปสินค้า แตะเพื่อจัดการ'}
                style={({ pressed }) => [
                  styles.imageTile,
                  { backgroundColor: colors.inset, borderColor: isMain ? colors.gold : colors.border, opacity: pressed ? 0.75 : 1 },
                ]}
              >
                {url ? (
                  <Image source={{ uri: url }} style={styles.image} contentFit="cover" transition={150} />
                ) : (
                  <Icon name="image" size={30} color={colors.textFaint} />
                )}
                {isMain && <Pill label="รูปหลัก" icon="star" solid style={styles.mainBadge} />}
              </Pressable>
            );
          })}
          {listing.images.length < FM_LIMITS.MAX_IMAGES && (
            <Pressable
              onPress={addImages}
              disabled={imageBusy}
              accessibilityRole="button"
              accessibilityLabel="เพิ่มรูปสินค้า"
              style={({ pressed }) => [
                styles.imageTile,
                styles.addTile,
                { borderColor: colors.gold, backgroundColor: colors.goldSoft, opacity: pressed ? 0.75 : 1 },
              ]}
            >
              {imageBusy ? (
                <ActivityIndicator color={colors.goldDeep} />
              ) : (
                <>
                  <View style={[styles.addCircle, { backgroundColor: colors.card }]}>
                    <Icon name="camera" size={20} color={colors.goldDeep} />
                  </View>
                  <Text style={[typography.caption, { color: colors.goldDeep }]}>เพิ่มรูป</Text>
                </>
              )}
            </Pressable>
          )}
        </ScrollView>

        {/* ---------- ข้อมูลสินค้า ---------- */}
        <View style={styles.section}>
          <ListingDetailsForm
            mode="edit"
            value={draft}
            onChange={(next) => {
              setDraft(next);
              setDetailsError(null);
            }}
            errors={errors}
            onClearError={(key) => setErrors((prev) => ({ ...prev, [key]: undefined }))}
            form={form}
            suspended={suspended}
            disabled={detailsSaving || deleting}
          />
        </View>
        {!!detailsError && <NoticeBanner tone="danger" text={detailsError} style={styles.block} />}
        <Button3D
          title={detailsDirty ? 'บันทึกข้อมูลสินค้า' : 'ข้อมูลสินค้าบันทึกแล้ว'}
          icon={detailsDirty ? 'check-circle' : 'seal-check'}
          variant={detailsDirty ? 'primary' : 'secondary'}
          size="lg"
          fullWidth
          disabled={!detailsDirty || deleting}
          loading={detailsSaving}
          loadingText="กำลังบันทึก…"
          onPress={saveDetails}
          style={styles.saveButton}
        />
        {detailsDirty && !detailsSaving && (
          <Button3D
            title="ยกเลิกการแก้ข้อมูล"
            variant="ghost"
            size="sm"
            onPress={() => {
              resetDetails(listing);
            }}
            style={styles.center}
          />
        )}

        {/* ---------- ตัวเลือก ---------- */}
        <SectionHeader
          title="ตัวเลือกสินค้า"
          subtitle="เช่น เลือกเนื้อสัตว์ หมู/ไก่ +0 กุ้ง +20 · เพิ่มไข่ดาว +10"
          style={styles.section}
        />
        <OptionGroupsEditor
          value={groups}
          onChange={(next) => {
            setGroups(next);
            setGroupsError(null);
          }}
          basePrice={previewPrice}
          disabled={groupsSaving || deleting}
        />
        {!!groupsError && <NoticeBanner tone="danger" text={groupsError} style={styles.block} />}
        <Button3D
          title={groupsDirty ? 'บันทึกตัวเลือก' : 'ตัวเลือกบันทึกแล้ว'}
          icon={groupsDirty ? 'check-circle' : 'seal-check'}
          variant={groupsDirty ? 'primary' : 'secondary'}
          size="lg"
          fullWidth
          disabled={!groupsDirty || deleting}
          loading={groupsSaving}
          onPress={saveGroups}
          style={styles.saveButton}
        />
        {groupsDirty && !groupsSaving && (
          <Button3D title="ยกเลิกการแก้ตัวเลือก" variant="ghost" size="sm" onPress={() => resetGroups(listing)} style={styles.center} />
        )}

        {/* ---------- สถิติ + ลบ ---------- */}
        <Card3D variant="flat" padding={spacing.lg} style={styles.section}>
          <View style={styles.statsRow}>
            <View style={styles.stat}>
              <Icon name="eye" size={14} color={colors.textFaint} />
              <Text style={[typography.caption, { color: colors.textFaint }]}>เข้าชม {listing.view_count.toLocaleString('th-TH')}</Text>
            </View>
            <View style={styles.stat}>
              <Icon name="receipt" size={14} color={colors.textFaint} />
              <Text style={[typography.caption, { color: colors.textFaint }]}>สั่งแล้ว {listing.order_count.toLocaleString('th-TH')}</Text>
            </View>
            <PriceText amount={previewPrice} size="xs" tone="muted" suffix="ราคาปกติ" />
          </View>
          <View style={[styles.dangerRow, { borderTopColor: colors.divider }]}>
            <IconTile icon="trash" tone="danger" size={40} />
            <View style={styles.flex}>
              <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ลบสินค้านี้</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>ลบถาวร กู้คืนไม่ได้ · มีออเดอร์ค้างจะลบไม่ได้</Text>
            </View>
          </View>
          <Button3D
            title="ลบสินค้า"
            icon="trash"
            variant="danger"
            fullWidth
            loading={deleting}
            loadingText="กำลังลบ…"
            disabled={detailsSaving || groupsSaving}
            onPress={confirmDelete}
            style={styles.gapTop}
          />
        </Card3D>
      </Screen>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  skeleton: {
    gap: spacing.lg,
  },
  block: {
    marginTop: spacing.md,
  },
  headRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  suspended: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginTop: spacing.md,
    borderRadius: radii.md,
    padding: spacing.md,
  },
  section: {
    marginTop: spacing.xxl,
  },
  images: {
    flexDirection: 'row',
    gap: spacing.sm,
    paddingVertical: spacing.xs,
  },
  imageTile: {
    width: 112,
    height: 112,
    borderRadius: 18,
    borderWidth: 2,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  image: {
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
  saveButton: {
    marginTop: spacing.lg,
  },
  center: {
    alignSelf: 'center',
    marginTop: spacing.sm,
  },
  gapTop: {
    marginTop: spacing.md,
  },
  statsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    gap: spacing.md,
  },
  stat: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  dangerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
});
