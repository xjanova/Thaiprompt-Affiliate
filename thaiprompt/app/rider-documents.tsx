/**
 * เอกสารไรเดอร์ — ต่อกับ GET /rider/documents + POST /rider/document จริง (RIDER-APP-18)
 *
 * - สถานะรายเอกสาร: ยังไม่มี / กำลังอัปโหลด / อัปโหลดแล้ว / อัปโหลดไม่สำเร็จ (ลองใหม่ได้)
 * - ถ่ายรูปหรือเลือกจากคลัง → อัปโหลดทันที · อัปซ้ำ = แทนไฟล์เดิม (ไม่มีปุ่มลบ)
 * - รูปจาก server เป็น signed URL อายุ 30 นาที → เข้าหน้านี้ใหม่จะได้ลิงก์ใหม่
 *
 * หน้าตา: การ์ดน้ำเงินลายกนก + วงแหวนความคืบหน้าเอกสารจำเป็น
 *         → การ์ดเช็กลิสต์รายเอกสาร (รูปย่อ + ป้ายสถานะ + จุดสถานะมุมรูป)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { router, useFocusEffect } from 'expo-router';
import { useTheme, spacing, radii, typography } from '@/theme';
import { Button3D, Card3D, EmptyState, Icon, Pill, Screen, resultHaptic, type IconName } from '@/components/ui';
import {
  getRiderDocuments,
  uploadRiderDocument,
  type RiderDocumentType,
  type RiderDocumentsResponse,
} from '@/services/api/riderApi';
import { pickFromGallery, takePhoto } from '@/components/rider/photo';
import { NavyCard, ProgressRing, useRiderTones } from '@/components/rider/RiderVisuals';

type UploadState = { status: 'idle' | 'uploading' | 'error'; localUri?: string; error?: string };

const DOC_INFO: Record<RiderDocumentType, { icon: IconName; label: string; hint: string }> = {
  id_card: { icon: 'identification-card', label: 'บัตรประชาชน', hint: 'ถ่ายด้านหน้าให้เห็นชื่อและเลขบัตรชัดเจน' },
  driver_license: { icon: 'road-horizon', label: 'ใบขับขี่', hint: 'ใบขับขี่ที่ยังไม่หมดอายุ ตรงกับประเภทรถ' },
  vehicle_registration: { icon: 'book-open', label: 'เล่มทะเบียนรถ', hint: 'หน้าที่มีเลขทะเบียนและชื่อเจ้าของ' },
  profile: { icon: 'user-circle', label: 'รูปหน้าตรง', hint: 'หน้าตรง ไม่สวมหมวก/แว่นดำ แสงสว่างพอ' },
};

const DOC_ORDER: RiderDocumentType[] = ['id_card', 'driver_license', 'vehicle_registration', 'profile'];

export default function RiderDocumentsScreen() {
  const { colors } = useTheme();
  const tones = useRiderTones();

  const [data, setData] = useState<RiderDocumentsResponse | null>(null);
  const [uploads, setUploads] = useState<Partial<Record<RiderDocumentType, UploadState>>>({});
  const [initialLoading, setInitialLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<{ code: string; message: string } | null>(null);

  const mountedRef = useRef(true);
  const requestIdRef = useRef(0);
  const uploadingRef = useRef<Set<RiderDocumentType>>(new Set());

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(async (mode: 'initial' | 'refresh' | 'silent') => {
    const requestId = ++requestIdRef.current;
    if (mode === 'initial') setInitialLoading(true);
    if (mode === 'refresh') setRefreshing(true);

    const result = await getRiderDocuments();
    if (!mountedRef.current || requestId !== requestIdRef.current) return;

    if (result.success) {
      setData(result.data);
      setError(null);
    } else if (mode !== 'silent') {
      setError({ code: result.code, message: result.message });
    }
    setInitialLoading(false);
    setRefreshing(false);
  }, []);

  useFocusEffect(
    useCallback(() => {
      load(data ? 'silent' : 'initial');
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [load])
  );

  const upload = useCallback(
    async (type: RiderDocumentType, source: 'camera' | 'gallery') => {
      if (uploadingRef.current.has(type)) return;

      const uri = source === 'camera' ? await takePhoto({ front: type === 'profile' }) : await pickFromGallery();
      if (!uri || !mountedRef.current) return;

      uploadingRef.current.add(type);
      setUploads((prev) => ({ ...prev, [type]: { status: 'uploading', localUri: uri } }));

      try {
        const result = await uploadRiderDocument(type, uri);
        if (!mountedRef.current) return;

        if (result.success) {
          resultHaptic('success');
          setUploads((prev) => ({ ...prev, [type]: { status: 'idle', localUri: uri } }));
          const r = result.data;
          setData((prev) =>
            prev
              ? {
                  ...prev,
                  documents: prev.documents.map((d) =>
                    d.type === type ? { ...d, uploaded: true, url: r?.url ?? d.url } : d
                  ),
                  missing: r?.documents_missing ?? prev.missing.filter((m) => m !== type),
                  complete: r?.documents_complete ?? prev.complete,
                  pending_review: r?.documents_pending_review ?? prev.pending_review,
                }
              : prev
          );
        } else {
          resultHaptic('error');
          setUploads((prev) => ({ ...prev, [type]: { status: 'error', localUri: uri, error: result.message } }));
        }
      } finally {
        uploadingRef.current.delete(type);
      }
    },
    []
  );

  // =====================================================

  if (initialLoading && !data) {
    return (
      <Screen title="เอกสารไรเดอร์">
        <EmptyState icon="hourglass" title="กำลังโหลด..." message="รอสักครู่นะ" />
      </Screen>
    );
  }

  if (!data) {
    const notRider = error?.code === 'NOT_RIDER';
    return (
      <Screen title="เอกสารไรเดอร์" onRefresh={() => load('refresh')} refreshing={refreshing}>
        {notRider ? (
          <EmptyState
            art="scooter"
            title="สมัครไรเดอร์ก่อนนะ"
            message="กรอกใบสมัครก่อน แล้วค่อยกลับมาอัปโหลดเอกสาร"
            actionLabel="ไปหน้าสมัคร"
            onAction={() => router.replace('/rider' as never)}
          />
        ) : (
          <EmptyState variant="error" message={error?.message} onAction={() => load('initial')} />
        )}
      </Screen>
    );
  }

  const docs = [...data.documents].sort(
    (a, b) => DOC_ORDER.indexOf(a.type) - DOC_ORDER.indexOf(b.type)
  );
  const requiredDocs = docs.filter((d) => d.required);
  const requiredDone = requiredDocs.filter((d) => d.uploaded).length;
  const progress = requiredDocs.length > 0 ? requiredDone / requiredDocs.length : 1;

  const summary: { icon: IconName; title: string; text: string } = data.complete
    ? data.pending_review
      ? { icon: 'magnifying-glass', title: 'ครบแล้ว! ทีมงานกำลังตรวจ', text: 'ปกติใช้เวลา 1-3 วันทำการ ระบบจะแจ้งเตือนเมื่อตรวจเสร็จ' }
      : { icon: 'seal-check', title: 'เอกสารครบแล้ว', text: 'อัปโหลดใหม่ได้ทุกเมื่อถ้าเอกสารเปลี่ยน' }
    : {
        icon: 'file-text',
        title: `ยังขาดอีก ${data.missing.length} รายการ`,
        text: 'ถ่ายรูปให้ชัด ไม่เบลอ ไม่มีแสงสะท้อน ทีมงานจะตรวจได้เร็วขึ้น',
      };

  return (
    <Screen
      title="เอกสารไรเดอร์"
      subtitle={`จำเป็น ${requiredDone}/${requiredDocs.length}`}
      onRefresh={() => load('refresh')}
      refreshing={refreshing}
    >
      {/* ---------- สรุปความคืบหน้า ---------- */}
      <NavyCard goldBorder style={styles.block}>
        <View style={styles.row}>
          <ProgressRing
            progress={progress}
            size={76}
            stroke={7}
            color={data.complete ? colors.success : colors.gold}
            track={colors.headerGlassBorder}
          >
            <Text style={[typography.h3, { color: colors.onHeader }]}>
              {requiredDone}/{requiredDocs.length}
            </Text>
            <Text style={[typography.micro, { color: colors.onHeaderMuted }]}>จำเป็น</Text>
          </ProgressRing>
          <View style={styles.flex}>
            <View style={styles.summaryTitle}>
              <Icon name={summary.icon} size={18} color={data.complete ? colors.success : colors.goldLight} weight="fill" />
              <Text style={[typography.h3, styles.flex, { color: colors.onHeader }]}>{summary.title}</Text>
            </View>
            <Text style={[typography.bodySm, { color: colors.onHeaderMuted }]}>{summary.text}</Text>
          </View>
        </View>
      </NavyCard>

      {/* ---------- เช็กลิสต์เอกสาร ---------- */}
      {docs.map((doc) => {
        const info = DOC_INFO[doc.type] || { icon: 'file-text' as IconName, label: doc.label, hint: '' };
        const state = uploads[doc.type];
        const uploading = state?.status === 'uploading';
        const failed = state?.status === 'error';
        const preview = state?.localUri || doc.url || null;

        const pill = uploading
          ? { label: 'กำลังอัปโหลด', tone: 'info' as const }
          : failed
            ? { label: 'อัปโหลดไม่สำเร็จ', tone: 'danger' as const }
            : doc.uploaded
              ? { label: 'อัปโหลดแล้ว', tone: 'success' as const }
              : doc.required
                ? { label: 'ยังไม่มี', tone: 'warning' as const }
                : { label: 'ไม่บังคับ', tone: 'neutral' as const };

        // จุดสถานะมุมรูป (หน้าตาเท่านั้น — อิงสถานะเดียวกับป้าย)
        const badge: { icon: IconName; color: string } | null = uploading
          ? { icon: 'upload-simple', color: colors.info }
          : failed
            ? { icon: 'x', color: colors.danger }
            : doc.uploaded
              ? { icon: 'check', color: colors.success }
              : doc.required
                ? { icon: 'warning', color: colors.warning }
                : null;

        return (
          <Card3D key={doc.type} style={styles.block} padding={spacing.md + 2} radius={radii.lg + 1}>
            <View style={styles.row}>
              <View>
                {preview ? (
                  <Image
                    source={{ uri: preview }}
                    style={[styles.thumb, { backgroundColor: colors.inset, borderColor: colors.border }]}
                    contentFit="cover"
                    transition={150}
                    accessibilityLabel={`รูป${info.label}`}
                  />
                ) : (
                  <View style={[styles.thumb, styles.thumbEmpty, { backgroundColor: colors.navySoft, borderColor: colors.border }]}>
                    <Icon name={info.icon} size={30} color={tones.accent} />
                  </View>
                )}
                {!!badge && (
                  <View style={[styles.badge, { backgroundColor: badge.color, borderColor: colors.card }]}>
                    <Icon name={badge.icon} size={12} color={colors.textOnAccent} weight="bold" />
                  </View>
                )}
              </View>
              <View style={styles.flex}>
                <View style={styles.titleRow}>
                  <Text style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]} numberOfLines={1}>
                    {doc.label || info.label}
                  </Text>
                  <Pill label={pill.label} tone={pill.tone} />
                </View>
                <Text style={[typography.caption, { color: colors.textMuted }]}>{info.hint}</Text>
                {doc.required && !doc.uploaded && !uploading && (
                  <Text style={[typography.micro, styles.requiredText, { color: colors.warning }]}>จำเป็นต้องมี</Text>
                )}
                {failed && !!state?.error && (
                  <View style={styles.errorRow}>
                    <Icon name="warning-circle" size={14} color={colors.danger} weight="fill" />
                    <Text style={[typography.caption, styles.flex, { color: colors.danger }]}>{state.error}</Text>
                  </View>
                )}
              </View>
            </View>

            <View style={styles.actions}>
              <Button3D
                title={doc.uploaded ? 'ถ่ายใหม่' : 'ถ่ายรูป'}
                icon="camera"
                size="sm"
                variant={doc.uploaded ? 'secondary' : 'primary'}
                disabled={uploading}
                loading={uploading}
                loadingText="กำลังอัปโหลด..."
                onPress={() => upload(doc.type, 'camera')}
                style={styles.flex}
              />
              <Button3D
                title="เลือกจากคลัง"
                icon="image"
                size="sm"
                variant="secondary"
                disabled={uploading}
                onPress={() => upload(doc.type, 'gallery')}
                style={styles.flex}
              />
            </View>
          </Card3D>
        );
      })}

      <View style={styles.note}>
        <Icon name="lock" size={15} color={colors.textMuted} weight="fill" />
        <Text style={[typography.caption, styles.noteText, { color: colors.textMuted }]}>
          เอกสารเก็บแบบส่วนตัว ทีมงานที่ตรวจใบสมัครเท่านั้นที่เปิดดูได้
        </Text>
      </View>

      <Button3D title="กลับหน้าไรเดอร์" variant="ghost" icon="caret-left" onPress={() => router.back()} style={styles.back} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  block: {
    marginBottom: spacing.md + 2,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md + 2,
  },
  summaryTitle: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: 2,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.xxs,
  },
  thumb: {
    width: 72,
    height: 72,
    borderRadius: radii.md + 1,
    borderWidth: 1,
  },
  thumbEmpty: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  badge: {
    position: 'absolute',
    right: -5,
    bottom: -5,
    width: 24,
    height: 24,
    borderRadius: 12,
    borderWidth: 2.5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  requiredText: {
    marginTop: 2,
  },
  errorRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 5,
    marginTop: 2,
  },
  actions: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md + 2,
  },
  note: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    marginTop: spacing.sm,
    paddingHorizontal: spacing.lg,
  },
  noteText: {
    flexShrink: 1,
    textAlign: 'center',
  },
  back: {
    alignSelf: 'center',
    marginTop: spacing.lg,
  },
});
