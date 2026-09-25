/**
 * เอกสารไรเดอร์ — ต่อกับ GET /rider/documents + POST /rider/document จริง (RIDER-APP-18)
 *
 * - สถานะรายเอกสาร: ยังไม่มี / กำลังอัปโหลด / อัปโหลดแล้ว / อัปโหลดไม่สำเร็จ (ลองใหม่ได้)
 * - ถ่ายรูปหรือเลือกจากคลัง → อัปโหลดทันที · อัปซ้ำ = แทนไฟล์เดิม (ไม่มีปุ่มลบ)
 * - รูปจาก server เป็น signed URL อายุ 30 นาที → เข้าหน้านี้ใหม่จะได้ลิงก์ใหม่
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { router, useFocusEffect } from 'expo-router';
import { useTheme, spacing, radii, typography } from '@/theme';
import { Button3D, Card3D, EmptyState, Pill, Screen, resultHaptic } from '@/components/ui';
import {
  getRiderDocuments,
  uploadRiderDocument,
  type RiderDocumentType,
  type RiderDocumentsResponse,
} from '@/services/api/riderApi';
import { pickFromGallery, takePhoto } from '@/components/rider/photo';

type UploadState = { status: 'idle' | 'uploading' | 'error'; localUri?: string; error?: string };

const DOC_INFO: Record<RiderDocumentType, { icon: string; label: string; hint: string }> = {
  id_card: { icon: '🪪', label: 'บัตรประชาชน', hint: 'ถ่ายด้านหน้าให้เห็นชื่อและเลขบัตรชัดเจน' },
  driver_license: { icon: '🚦', label: 'ใบขับขี่', hint: 'ใบขับขี่ที่ยังไม่หมดอายุ ตรงกับประเภทรถ' },
  vehicle_registration: { icon: '📘', label: 'เล่มทะเบียนรถ', hint: 'หน้าที่มีเลขทะเบียนและชื่อเจ้าของ' },
  profile: { icon: '🙂', label: 'รูปหน้าตรง', hint: 'หน้าตรง ไม่สวมหมวก/แว่นดำ แสงสว่างพอ' },
};

const DOC_ORDER: RiderDocumentType[] = ['id_card', 'driver_license', 'vehicle_registration', 'profile'];

export default function RiderDocumentsScreen() {
  const { colors } = useTheme();

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
        <EmptyState icon="⏳" title="กำลังโหลด..." message="รอสักครู่นะ" />
      </Screen>
    );
  }

  if (!data) {
    const notRider = error?.code === 'NOT_RIDER';
    return (
      <Screen title="เอกสารไรเดอร์" onRefresh={() => load('refresh')} refreshing={refreshing}>
        {notRider ? (
          <EmptyState
            icon="🛵"
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

  const summary = data.complete
    ? data.pending_review
      ? { icon: '🔎', title: 'ครบแล้ว! ทีมงานกำลังตรวจ', text: 'ปกติใช้เวลา 1-3 วันทำการ ระบบจะแจ้งเตือนเมื่อตรวจเสร็จ' }
      : { icon: '✅', title: 'เอกสารครบแล้ว', text: 'อัปโหลดใหม่ได้ทุกเมื่อถ้าเอกสารเปลี่ยน' }
    : {
        icon: '📄',
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
      <Card3D gradientBorder style={styles.block} padding={spacing.lg}>
        <View style={styles.row}>
          <Text style={styles.summaryIcon}>{summary.icon}</Text>
          <View style={styles.flex}>
            <Text style={[typography.h3, { color: colors.textStrong }]}>{summary.title}</Text>
            <Text style={[typography.bodySm, { color: colors.textMuted }]}>{summary.text}</Text>
          </View>
        </View>
        <View style={[styles.progressTrack, { backgroundColor: colors.inset }]}>
          <View
            style={[
              styles.progressFill,
              { width: `${Math.round(progress * 100)}%`, backgroundColor: data.complete ? colors.success : colors.gold },
            ]}
          />
        </View>
      </Card3D>

      {docs.map((doc) => {
        const info = DOC_INFO[doc.type] || { icon: '📄', label: doc.label, hint: '' };
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

        return (
          <Card3D key={doc.type} style={styles.block} padding={spacing.md} radius={radii.lg}>
            <View style={styles.row}>
              {preview ? (
                <Image
                  source={{ uri: preview }}
                  style={[styles.thumb, { backgroundColor: colors.inset }]}
                  contentFit="cover"
                  transition={150}
                  accessibilityLabel={`รูป${info.label}`}
                />
              ) : (
                <View style={[styles.thumb, styles.thumbEmpty, { backgroundColor: colors.inset }]}>
                  <Text style={styles.thumbIcon}>{info.icon}</Text>
                </View>
              )}
              <View style={styles.flex}>
                <View style={styles.titleRow}>
                  <Text style={[typography.bodyStrong, styles.flex, { color: colors.textStrong }]} numberOfLines={1}>
                    {doc.label || info.label}
                  </Text>
                  <Pill label={pill.label} tone={pill.tone} />
                </View>
                <Text style={[typography.caption, { color: colors.textMuted }]}>{info.hint}</Text>
                {doc.required && !doc.uploaded && !uploading && (
                  <Text style={[typography.micro, { color: colors.warning }]}>จำเป็นต้องมี</Text>
                )}
                {failed && !!state?.error && (
                  <Text style={[typography.caption, { color: colors.danger }]}>{state.error}</Text>
                )}
              </View>
            </View>

            <View style={styles.actions}>
              <Button3D
                title={doc.uploaded ? 'ถ่ายใหม่' : 'ถ่ายรูป'}
                icon="📷"
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
                icon="🖼️"
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

      <Text style={[typography.caption, styles.note, { color: colors.textMuted }]}>
        🔒 เอกสารเก็บแบบส่วนตัว ทีมงานที่ตรวจใบสมัครเท่านั้นที่เปิดดูได้
      </Text>

      <Button3D title="กลับหน้าไรเดอร์" variant="ghost" onPress={() => router.back()} style={styles.back} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  block: {
    marginBottom: spacing.md,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.xxs,
  },
  summaryIcon: {
    fontSize: 32,
  },
  progressTrack: {
    height: 10,
    borderRadius: 5,
    overflow: 'hidden',
    marginTop: spacing.md,
  },
  progressFill: {
    height: '100%',
    borderRadius: 5,
  },
  thumb: {
    width: 72,
    height: 72,
    borderRadius: radii.md,
  },
  thumbEmpty: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  thumbIcon: {
    fontSize: 30,
  },
  actions: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  note: {
    textAlign: 'center',
    marginTop: spacing.sm,
  },
  back: {
    alignSelf: 'center',
    marginTop: spacing.md,
  },
});
