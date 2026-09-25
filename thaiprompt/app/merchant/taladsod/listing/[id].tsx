/**
 * แก้สินค้าตลาดสด — รูป / ราคา / จำนวน / เปิดขาย / กลุ่มตัวเลือก
 *
 * - รูป: เลือกจากคลังรูป (ไม่ต้องขอสิทธิ์อ่านรูปทั้งเครื่อง) → POST /listings/{id}/images · ตั้งรูปหลัก · ลบรูป (ถามก่อน)
 * - ราคา/จำนวน/เปิดขาย: PUT /listings/{id} (ส่งเฉพาะช่องที่เปลี่ยน)
 * - ตัวเลือก: แก้ในจอแล้วกด "บันทึกตัวเลือก" ส่งทั้งชุด PUT /listings/{id}/option-groups
 * - ยังไม่บันทึกแล้วจะออกจากหน้า → ถามก่อนทิ้งการแก้ไข (usePreventRemove — ครอบการปัดขอบจอกลับบน iOS ด้วย
 *   เพราะตั้ง preventNativeDismiss ให้ native-stack · ส่วน listener 'beforeRemove' เปล่าๆ กันการปัดแบบ native ไม่ได้)
 * - แก้รายละเอียดอื่น (ชื่อ คำอธิบาย หมวดหมู่) บนเว็บไซต์
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, ScrollView, StyleSheet, Switch, Text, View } from 'react-native';
import { Image } from 'expo-image';
import * as ImagePicker from 'expo-image-picker';
import { router, useLocalSearchParams, useNavigation } from 'expo-router';
import { usePreventRemove } from 'expo-router/react-navigation';
import { useAuthStore } from '@/stores/authStore';
import {
  FM_LIMITS,
  addFmListingImages,
  fmImageUrl,
  getFmSellerListing,
  removeFmListingImage,
  saveFmOptionGroups,
  setFmListingMainImage,
  updateFmListing,
  type FmListingUpdateBody,
  type FmOwnerListing,
} from '@/services/api/taladsodSellerApi';
import {
  Button3D,
  Card3D,
  EmptyState,
  Pill,
  PriceText,
  Screen,
  SectionHeader,
  WebsiteButton,
  resultHaptic,
} from '@/components/ui';
import { Field } from '@/components/shop';
import {
  FM_LISTING_STATUS,
  OptionGroupsEditor,
  fromDraft,
  toDraft,
  type DraftGroup,
} from '@/components/merchant';
import { useTheme, radii, spacing, typography } from '@/theme';

const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

const parsePriceText = (text: string): number | null => {
  const clean = text.replace(/[,\s฿]/g, '');
  if (!/^\d+(\.\d{1,2})?$/.test(clean)) return null;
  const n = Number(clean);
  return Number.isFinite(n) && n >= 1 && n <= 1_000_000 ? n : null;
};

/** สรุป draft เป็นข้อความเพื่อเทียบว่ามีการแก้หรือไม่ (ไม่รวม key ภายใน) */
const serializeDraft = (draft: DraftGroup[]): string =>
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

  const [listing, setListing] = useState<FmOwnerListing | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);

  const [priceText, setPriceText] = useState('');
  const [qtyText, setQtyText] = useState('');
  const [available, setAvailable] = useState(true);
  const [basicError, setBasicError] = useState<string | null>(null);

  const [draft, setDraft] = useState<DraftGroup[]>([]);
  const [baseline, setBaseline] = useState('[]');
  const [groupsError, setGroupsError] = useState<string | null>(null);
  const [groupsSaving, setGroupsSaving] = useState(false);

  const [imageBusy, setImageBusy] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);

  const mountedRef = useRef(true);
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

  /** ใส่ค่าจาก server ลงฟอร์ม (ทับการแก้ไข — ใช้ตอนโหลดครั้งแรก/หลังบันทึก) */
  const resetBasic = (l: FmOwnerListing) => {
    setPriceText(String(l.price));
    setQtyText(String(l.quantity_available));
    setAvailable(l.is_available);
    setBasicError(null);
  };
  const resetGroups = (l: FmOwnerListing) => {
    const d = toDraft(l.option_groups);
    setDraft(d);
    setBaseline(serializeDraft(d));
    setGroupsError(null);
  };

  const load = useCallback(async () => {
    if (!listingId || !isAuthenticated) {
      setLoading(false);
      return;
    }
    setLoading(true);
    const res = await getFmSellerListing(listingId);
    if (!mountedRef.current) return;
    setLoading(false);
    if (res.success) {
      setListing(res.data);
      resetBasic(res.data);
      resetGroups(res.data);
      setLoadError(null);
    } else {
      setLoadError(res.message);
    }
  }, [listingId, isAuthenticated]);

  useEffect(() => {
    load();
  }, [load]);

  // ---------- การแก้ไขที่ยังไม่บันทึก ----------
  const basicPatch = useMemo((): FmListingUpdateBody | null => {
    if (!listing) return null;
    const patch: FmListingUpdateBody = {};
    const price = parsePriceText(priceText);
    if (price !== null && price !== listing.price) patch.price = price;
    if (listing.track_stock) {
      const qty = Number(qtyText);
      if (Number.isInteger(qty) && qty >= 0 && qty !== listing.quantity_available) patch.quantity_available = qty;
    }
    if (available !== listing.is_available) patch.is_available = available;
    return Object.keys(patch).length > 0 ? patch : null;
  }, [listing, priceText, qtyText, available]);

  const basicInvalid =
    !!listing &&
    (parsePriceText(priceText) === null ||
      (listing.track_stock && !(Number.isInteger(Number(qtyText)) && Number(qtyText) >= 0 && qtyText.trim() !== '')));
  const groupsDirty = serializeDraft(draft) !== baseline;
  const dirty = !!basicPatch || basicInvalid || groupsDirty;

  // ออกจากหน้าโดยยังไม่บันทึก → ถามก่อน (ปุ่มกลับ / ปัดขอบจอ iOS / ปุ่ม back Android)
  // ส่ง action เดิมซ้ำตอนกดทิ้ง = ผ่านได้ (react-navigation จำว่าหน้านี้ถามไปแล้วในรอบนี้)
  usePreventRemove(dirty, ({ data }) => {
    Alert.alert('ยังไม่ได้บันทึก', 'มีการแก้ไขที่ยังไม่บันทึก ออกจากหน้านี้แล้วการแก้ไขจะหายไป', [
      { text: 'อยู่ต่อ', style: 'cancel' },
      {
        text: 'ทิ้งการแก้ไข',
        style: 'destructive',
        onPress: () => navigation.dispatch(data.action),
      },
    ]);
  });

  // ---------- บันทึกราคา/จำนวน/เปิดขาย ----------
  const saveBasic = async () => {
    if (!listing) return;
    if (parsePriceText(priceText) === null) {
      setBasicError('ราคาต้องเป็นตัวเลข 1 – 1,000,000 บาท');
      return;
    }
    if (!basicPatch) return;
    setBasicError(null);
    const res = await updateFmListing(listing.id, basicPatch);
    if (!mountedRef.current) return;
    if (res.success) {
      resultHaptic('success');
      setListing(res.data);
      resetBasic(res.data);
      flash('บันทึกราคาและสถานะแล้ว');
    } else {
      resultHaptic('error');
      setBasicError(res.message);
    }
  };

  // ---------- บันทึกตัวเลือก ----------
  const saveGroups = async () => {
    if (!listing || groupsSaving) return;
    const { groups, error } = fromDraft(draft);
    if (error) {
      setGroupsError(error);
      resultHaptic('warning');
      return;
    }
    setGroupsSaving(true);
    setGroupsError(null);
    const res = await saveFmOptionGroups(listing.id, groups);
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
  /** อัปเดตเฉพาะรูปจากผลของ server (ไม่ทับราคา/ตัวเลือกที่กำลังแก้) */
  const applyImages = (fresh: FmOwnerListing) =>
    setListing((prev) => (prev ? { ...prev, images: fresh.images, main_image_url: fresh.main_image_url } : fresh));

  const addImages = async () => {
    if (!listing || imageBusy) return;
    const remaining = FM_LIMITS.MAX_IMAGES - listing.images.length;
    if (remaining <= 0) {
      Alert.alert('รูปครบแล้ว', `สินค้ามีรูปได้สูงสุด ${FM_LIMITS.MAX_IMAGES} รูป ลบรูปเก่าก่อนนะ`);
      return;
    }
    let uris: string[] = [];
    try {
      const picked = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        quality: 0.7,
        allowsMultipleSelection: remaining > 1,
        selectionLimit: remaining,
        exif: false,
      });
      if (picked.canceled || !picked.assets?.length) return;
      const tooBig = picked.assets.filter((a) => typeof a.fileSize === 'number' && a.fileSize > MAX_IMAGE_BYTES);
      uris = picked.assets
        .filter((a) => !(typeof a.fileSize === 'number' && a.fileSize > MAX_IMAGE_BYTES))
        .map((a) => a.uri)
        .slice(0, remaining);
      if (tooBig.length > 0) {
        Alert.alert('บางรูปใหญ่เกินไป', 'รูปต้องไม่เกิน 5MB ต่อรูป ระบบข้ามรูปที่ใหญ่เกินให้แล้ว');
      }
    } catch {
      Alert.alert('เปิดคลังรูปไม่ได้', 'ลองใหม่อีกครั้งนะ');
      return;
    }
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

  // =====================================================
  // แสดงผล
  // =====================================================

  if (!isAuthenticated) {
    return (
      <Screen title="แก้สินค้า" scroll={false}>
        <EmptyState icon="🔐" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (loading && !listing) {
    return (
      <Screen title="แก้สินค้า" scroll={false}>
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </Screen>
    );
  }

  if (!listing) {
    return (
      <Screen title="แก้สินค้า" scroll={false}>
        <EmptyState
          variant="error"
          title="เปิดสินค้านี้ไม่ได้"
          message={loadError || 'ไม่พบสินค้านี้'}
          actionLabel="ลองใหม่"
          onAction={load}
          secondaryActionLabel="กลับไปรายการสินค้า"
          onSecondaryAction={() => router.back()}
        />
      </Screen>
    );
  }

  const st = FM_LISTING_STATUS[listing.status] || { label: listing.status, tone: 'neutral' as const };
  const mainPath = listing.main_image_url || listing.images[0] || null;
  const previewPrice = parsePriceText(priceText) ?? listing.price;

  return (
    <Screen
      title="แก้สินค้า"
      subtitle={listing.title}
      right={dirty ? <Pill label="ยังไม่บันทึก" tone="warning" /> : undefined}
    >
      {!!notice && (
        <Card3D variant="flat" padding={spacing.md} style={styles.block}>
          <Text accessibilityLiveRegion="polite" style={[typography.bodySm, { color: colors.success }]}>
            ✅ {notice}
          </Text>
        </Card3D>
      )}

      {/* ---------- หัว ---------- */}
      <View style={styles.headRow}>
        <Text style={[typography.h2, styles.flex, { color: colors.textStrong }]} numberOfLines={2}>
          {listing.title}
        </Text>
        <Pill label={st.label} tone={st.tone} size="md" />
      </View>
      {listing.status === 'suspended' && (
        <Text style={[typography.bodySm, styles.block, { color: colors.danger }]}>
          สินค้านี้ถูกระงับโดยทีมงาน เปิดขายเองไม่ได้ ติดต่อทีมงานได้ที่หน้าช่วยเหลือ
        </Text>
      )}

      {/* ---------- รูป ---------- */}
      <SectionHeader title="รูปสินค้า" icon="📷" subtitle={`${listing.images.length}/${FM_LIMITS.MAX_IMAGES} รูป · แตะรูปเพื่อตั้งรูปหลักหรือลบ`} style={styles.section} />
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
              {url ? <Image source={{ uri: url }} style={styles.image} contentFit="cover" transition={150} /> : <Text>🖼️</Text>}
              {isMain && <Pill label="รูปหลัก" solid style={styles.mainBadge} />}
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
                <Text style={styles.addIcon}>＋</Text>
                <Text style={[typography.caption, { color: colors.goldDeep }]}>เพิ่มรูป</Text>
              </>
            )}
          </Pressable>
        )}
      </ScrollView>

      {/* ---------- ราคา / จำนวน / เปิดขาย ---------- */}
      <SectionHeader title="ราคาและการขาย" icon="💰" style={styles.section} />
      <Card3D padding={spacing.lg}>
        <Field
          label={`ราคาขาย (บาท${listing.unit ? ` ต่อ${listing.unit}` : ''})`}
          value={priceText}
          onChangeText={(t) => {
            setPriceText(t.replace(/[^0-9.]/g, '').slice(0, 10));
            setBasicError(null);
          }}
          keyboardType="decimal-pad"
          error={parsePriceText(priceText) === null && priceText !== '' ? 'ราคาต้องเป็นตัวเลข 1 – 1,000,000' : null}
          containerStyle={styles.noTop}
        />
        {listing.track_stock ? (
          <Field
            label="จำนวนคงเหลือ"
            value={qtyText}
            onChangeText={(t) => {
              setQtyText(t.replace(/[^0-9]/g, '').slice(0, 6));
              setBasicError(null);
            }}
            keyboardType="number-pad"
            hint="เติมจำนวนแล้วสินค้าที่ของหมดจะกลับมาขายเอง"
          />
        ) : (
          <Text style={[typography.caption, styles.gapTop, { color: colors.textMuted }]}>
            🍳 ทำตามสั่ง — ไม่ต้องนับจำนวนคงเหลือ
          </Text>
        )}
        <View style={styles.switchRow}>
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>เปิดขาย</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              {available ? 'ลูกค้าเห็นและสั่งได้' : 'ซ่อนจากลูกค้าชั่วคราว'}
            </Text>
          </View>
          <Switch
            value={available}
            onValueChange={setAvailable}
            disabled={listing.status === 'suspended'}
            trackColor={{ false: colors.border, true: colors.success }}
            thumbColor={colors.card}
            accessibilityLabel="เปิดขาย"
          />
        </View>
        {!!basicError && (
          <Text style={[typography.bodySm, styles.gapTop, { color: colors.danger }]} accessibilityRole="alert">
            {basicError}
          </Text>
        )}
        <Button3D
          title="บันทึกราคาและสถานะ"
          icon="💾"
          fullWidth
          disabled={!basicPatch || parsePriceText(priceText) === null}
          onPress={saveBasic}
          style={styles.saveButton}
        />
      </Card3D>

      {/* ---------- ตัวเลือก ---------- */}
      <SectionHeader
        title="ตัวเลือกสินค้า"
        icon="🧩"
        subtitle="เช่น เลือกเนื้อสัตว์ หมู/ไก่ +0 กุ้ง +20 · เพิ่มไข่ดาว +10"
        style={styles.section}
      />
      <OptionGroupsEditor value={draft} onChange={(next) => { setDraft(next); setGroupsError(null); }} basePrice={previewPrice} disabled={groupsSaving} />
      {!!groupsError && (
        <Card3D variant="inset" padding={spacing.md} style={styles.gapTop}>
          <Text style={[typography.bodySm, { color: colors.danger }]} accessibilityRole="alert">
            {groupsError}
          </Text>
        </Card3D>
      )}
      <Button3D
        title={groupsDirty ? 'บันทึกตัวเลือก' : 'ตัวเลือกบันทึกแล้ว'}
        icon="💾"
        variant={groupsDirty ? 'success' : 'secondary'}
        size="lg"
        fullWidth
        disabled={!groupsDirty}
        loading={groupsSaving}
        onPress={saveGroups}
        style={styles.saveButton}
      />
      {groupsDirty && (
        <Button3D
          title="ยกเลิกการแก้ตัวเลือก"
          variant="ghost"
          size="sm"
          onPress={() => resetGroups(listing)}
          style={styles.center}
        />
      )}

      {/* ---------- เว็บ ---------- */}
      <Card3D variant="flat" padding={spacing.lg} style={styles.section}>
        <Text style={[typography.bodySm, { color: colors.textMuted }]}>
          แก้ชื่อ คำอธิบาย หมวดหมู่ หรือรูปของแต่ละตัวเลือก ได้บนเว็บไซต์
        </Text>
        <WebsiteButton path={`/taladsod/listings/${listing.id}/edit`} label="แก้ไขละเอียดบนเว็บ" size="sm" style={styles.gapTop} />
        <View style={styles.statsRow}>
          <Text style={[typography.caption, { color: colors.textFaint }]}>👀 เข้าชม {listing.view_count.toLocaleString('th-TH')}</Text>
          <Text style={[typography.caption, { color: colors.textFaint }]}>🧾 สั่งแล้ว {listing.order_count.toLocaleString('th-TH')}</Text>
          <PriceText amount={previewPrice} size="xs" tone="muted" suffix="ราคาปกติ" />
        </View>
      </Card3D>
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
  block: {
    marginBottom: spacing.md,
  },
  headRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  section: {
    marginTop: spacing.xl,
  },
  images: {
    gap: spacing.sm,
    paddingVertical: spacing.xs,
  },
  imageTile: {
    width: 104,
    height: 104,
    borderRadius: radii.md,
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
  },
  addIcon: {
    fontSize: 28,
    lineHeight: 32,
  },
  noTop: {
    marginTop: 0,
  },
  gapTop: {
    marginTop: spacing.sm,
  },
  switchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.lg,
  },
  saveButton: {
    marginTop: spacing.lg,
  },
  center: {
    alignSelf: 'center',
    marginTop: spacing.sm,
  },
  statsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.md,
  },
});
