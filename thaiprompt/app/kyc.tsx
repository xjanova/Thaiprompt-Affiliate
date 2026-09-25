/**
 * ยืนยันตัวตน (KYC) — ธีมนวลทองคำ
 *
 * API (MobileApiController): GET /kyc/status · POST /kyc/upload (multipart image + type) · POST /kyc/confirm
 * - เรียกผ่าน client กลาง → ข้อความผิดพลาดเป็นภาษาไทยเสมอ (ไม่แสดง error ดิบ)
 * - ขั้นตอน: รูปบัตรประชาชน → เซลฟี่ถือบัตร → ส่งตรวจ (ถามยืนยันก่อน, กันกดซ้ำ)
 * - กล้อง: ขอสิทธิ์ตอนกด "ถ่ายรูป" เท่านั้น · ปฏิเสธถาวร → พาไปตั้งค่าเครื่อง
 * - คลังรูป: ใช้ตัวเลือกรูปของระบบ (ไม่ต้องขอสิทธิ์อ่านรูปทั้งเครื่อง)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Linking, Modal, Pressable, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { CameraView, useCameraPermissions } from 'expo-camera';
import * as ImagePicker from 'expo-image-picker';
import { router } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { apiGet, apiPost, apiUpload, fileFromUri, type ApiResult } from '@/services/api/client';
import { Button3D, Card3D, EmptyState, Pill, Screen, resultHaptic } from '@/components/ui';
import { useTheme, palette, radii, spacing, typography } from '@/theme';

type KycStatus = 'not_submitted' | 'pending' | 'approved' | 'rejected';
type ImageType = 'id_card' | 'selfie';

interface KycStatusData {
  status?: KycStatus;
  submission?: { hasIdCard?: boolean; hasSelfie?: boolean; rejectionReason?: string | null } | null;
}

const fetchKycStatus = (): Promise<ApiResult<KycStatusData>> => apiGet<KycStatusData>('/kyc/status');

const uploadKycImage = (uri: string, type: ImageType): Promise<ApiResult<unknown>> => {
  const form = new FormData();
  form.append('image', fileFromUri(uri, type) as unknown as Blob);
  form.append('type', type);
  return apiUpload('/kyc/upload', form, { fallbackMessage: 'อัปโหลดรูปไม่สำเร็จ ลองใหม่อีกครั้งนะ' });
};

const confirmKyc = (): Promise<ApiResult<unknown>> =>
  apiPost('/kyc/confirm', undefined, { fallbackMessage: 'ส่งเอกสารไม่สำเร็จ ลองใหม่อีกครั้งนะ' });

const COPY: Record<ImageType, { title: string; hint: string; icon: string; frame: string }> = {
  id_card: { title: 'รูปบัตรประชาชน', hint: 'ด้านหน้า เห็นตัวอักษรชัด ไม่มีแสงสะท้อน', icon: '🪪', frame: 'วางบัตรให้อยู่ในกรอบ ให้เห็นข้อมูลชัดเจน' },
  selfie: { title: 'เซลฟี่ถือบัตร', hint: 'เห็นหน้าคุณและบัตรในรูปเดียวกัน', icon: '🤳', frame: 'ถือบัตรข้างใบหน้า ให้เห็นทั้งหน้าและบัตร' },
};

// =====================================================
// การ์ดอัปโหลด
// =====================================================

const UploadCard: React.FC<{
  type: ImageType;
  step: number;
  done: boolean;
  uri: string | null;
  uploading: boolean;
  onCamera: () => unknown;
  onGallery: () => unknown;
}> = ({ type, step, done, uri, uploading, onCamera, onGallery }) => {
  const { colors, gradients } = useTheme();
  const copy = COPY[type];
  return (
    <Card3D padding={spacing.lg} gradientBorder={done ? gradients.success : false} style={styles.block}>
      <View style={styles.cardHead}>
        <View style={[styles.stepBadge, { backgroundColor: done ? colors.successSoft : colors.goldSoft }]}>
          <Text style={[typography.bodyStrong, { color: done ? colors.success : colors.goldDeep }]}>{done ? '✓' : step}</Text>
        </View>
        <View style={styles.flex}>
          <Text style={[typography.h3, { color: colors.textStrong }]}>{copy.title}</Text>
          <Text style={[typography.caption, { color: colors.textMuted }]}>{copy.hint}</Text>
        </View>
        {done && <Pill label="อัปโหลดแล้ว" tone="success" />}
      </View>

      <View style={[styles.preview, type === 'selfie' && styles.previewTall, { backgroundColor: colors.inset, borderColor: colors.border }]}>
        {uploading ? (
          <>
            <ActivityIndicator size="large" color={colors.gold} />
            <Text style={[typography.caption, styles.gapTop, { color: colors.textMuted }]}>กำลังอัปโหลด...</Text>
          </>
        ) : uri ? (
          <Image source={{ uri }} style={StyleSheet.absoluteFill} contentFit="cover" />
        ) : (
          <>
            <Text style={styles.previewIcon}>{done ? '✅' : copy.icon}</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>{done ? 'ส่งรูปนี้แล้ว ถ่ายใหม่ได้' : 'ยังไม่มีรูป'}</Text>
          </>
        )}
      </View>

      <View style={styles.actions}>
        <Button3D title="ถ่ายรูป" icon="📷" variant="primary" size="md" disabled={uploading} onPress={onCamera} style={styles.flex} />
        <Button3D title="เลือกจากคลัง" icon="🖼️" variant="secondary" size="md" disabled={uploading} onPress={onGallery} style={styles.flex} />
      </View>
    </Card3D>
  );
};

// =====================================================
// หน้าหลัก
// =====================================================

export default function KycScreen() {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [status, setStatus] = useState<KycStatus>('not_submitted');
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [rejectionReason, setRejectionReason] = useState<string | null>(null);
  const [has, setHas] = useState<Record<ImageType, boolean>>({ id_card: false, selfie: false });
  const [uris, setUris] = useState<Record<ImageType, string | null>>({ id_card: null, selfie: null });
  const [uploading, setUploading] = useState<Record<ImageType, boolean>>({ id_card: false, selfie: false });
  const [submitting, setSubmitting] = useState(false);

  const [cameraFor, setCameraFor] = useState<ImageType | null>(null);
  const [capturing, setCapturing] = useState(false);
  const [cameraPermission, requestCameraPermission] = useCameraPermissions();
  const cameraRef = useRef<CameraView>(null);

  const mountedRef = useRef(true);
  const uploadingRef = useRef<Record<ImageType, boolean>>({ id_card: false, selfie: false });
  const submittingRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(async () => {
    if (!isAuthenticated) {
      setLoading(false);
      return;
    }
    setLoading(true);
    const result = await fetchKycStatus();
    if (!mountedRef.current) return;
    setLoading(false);
    if (result.success) {
      const data = result.data || {};
      const next: KycStatus = ['pending', 'approved', 'rejected'].includes(String(data.status))
        ? (data.status as KycStatus)
        : 'not_submitted';
      setStatus(next);
      setHas({ id_card: !!data.submission?.hasIdCard, selfie: !!data.submission?.hasSelfie });
      setRejectionReason(data.submission?.rejectionReason || null);
      setLoadError(null);
    } else {
      setLoadError(result.message);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    load();
  }, [load]);

  const upload = async (uri: string, type: ImageType) => {
    if (uploadingRef.current[type]) return;
    uploadingRef.current[type] = true;
    setUploading((p) => ({ ...p, [type]: true }));
    setUris((p) => ({ ...p, [type]: uri }));
    const result = await uploadKycImage(uri, type);
    uploadingRef.current[type] = false;
    if (!mountedRef.current) return;
    setUploading((p) => ({ ...p, [type]: false }));
    if (result.success) {
      resultHaptic('success');
      setHas((p) => ({ ...p, [type]: true }));
    } else {
      resultHaptic('error');
      setUris((p) => ({ ...p, [type]: null }));
      Alert.alert('อัปโหลดไม่สำเร็จ', result.message);
    }
  };

  const pickFromGallery = async (type: ImageType) => {
    try {
      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        allowsEditing: true,
        aspect: type === 'id_card' ? [16, 10] : [3, 4],
        quality: 0.8,
      });
      if (!result.canceled && result.assets?.[0]?.uri) await upload(result.assets[0].uri, type);
    } catch {
      Alert.alert('เปิดคลังรูปไม่ได้', 'ลองใหม่อีกครั้งนะ');
    }
  };

  const openCamera = async (type: ImageType) => {
    if (!cameraPermission?.granted) {
      const result = await requestCameraPermission();
      if (!result.granted) {
        if (result.canAskAgain === false) {
          Alert.alert('เปิดสิทธิ์กล้องก่อนนะ', 'ไปที่การตั้งค่าเครื่อง แล้วอนุญาตให้ใช้กล้อง หรือเลือกรูปจากคลังแทนก็ได้', [
            { text: 'ไว้ก่อน', style: 'cancel' },
            { text: 'เปิดการตั้งค่า', onPress: () => Linking.openSettings().catch(() => {}) },
          ]);
        }
        return;
      }
    }
    if (mountedRef.current) setCameraFor(type);
  };

  const capture = async () => {
    if (!cameraRef.current || !cameraFor || capturing) return;
    setCapturing(true);
    try {
      const photo = await cameraRef.current.takePictureAsync({ quality: 0.8 });
      const target = cameraFor;
      if (!mountedRef.current) return;
      setCameraFor(null);
      if (photo?.uri) await upload(photo.uri, target);
    } catch {
      Alert.alert('ถ่ายรูปไม่สำเร็จ', 'ลองใหม่อีกครั้งนะ');
    } finally {
      if (mountedRef.current) setCapturing(false);
    }
  };

  const submit = () => {
    if (!has.id_card || !has.selfie || submittingRef.current) return;
    Alert.alert('ส่งเอกสารให้ตรวจ?', 'ทีมงานจะตรวจภายใน 24–48 ชั่วโมง ระหว่างนี้แก้ไขเอกสารไม่ได้', [
      { text: 'ยังก่อน', style: 'cancel' },
      {
        text: 'ส่งเอกสาร',
        onPress: async () => {
          if (submittingRef.current) return;
          submittingRef.current = true;
          setSubmitting(true);
          const result = await confirmKyc();
          submittingRef.current = false;
          if (!mountedRef.current) return;
          setSubmitting(false);
          if (result.success) {
            resultHaptic('success');
            setStatus('pending');
          } else {
            resultHaptic('error');
            Alert.alert('ส่งเอกสารไม่สำเร็จ', result.message);
          }
        },
      },
    ]);
  };

  // =====================================================
  // แสดงผล
  // =====================================================

  if (!isAuthenticated) {
    return (
      <Screen title="ยืนยันตัวตน" scroll={false}>
        <EmptyState icon="🛡️" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (loading) {
    return (
      <Screen title="ยืนยันตัวตน" scroll={false}>
        <ActivityIndicator size="large" color={colors.gold} style={styles.loader} />
      </Screen>
    );
  }

  if (loadError) {
    return (
      <Screen title="ยืนยันตัวตน" scroll={false}>
        <EmptyState variant="error" message={loadError} onAction={load} />
      </Screen>
    );
  }

  if (status === 'approved') {
    return (
      <Screen title="ยืนยันตัวตน" scroll={false}>
        <EmptyState
          icon="✅"
          title="ยืนยันตัวตนแล้ว"
          message="ถอนเงินเข้าบัญชีธนาคาร สมัครไรเดอร์ และเปิดร้านได้เต็มที่"
          actionLabel="กลับ"
          onAction={() => router.back()}
        />
      </Screen>
    );
  }

  if (status === 'pending') {
    return (
      <Screen title="ยืนยันตัวตน" scroll={false}>
        <EmptyState
          icon="⏳"
          title="กำลังตรวจเอกสาร"
          message="ทีมงานจะตรวจภายใน 24–48 ชั่วโมง ผลจะแจ้งเตือนมาที่แอป"
          actionLabel="กลับ"
          onAction={() => router.back()}
          secondaryActionLabel="ตรวจสถานะอีกครั้ง"
          onSecondaryAction={load}
        />
      </Screen>
    );
  }

  if (status === 'rejected') {
    return (
      <Screen title="ยืนยันตัวตน">
        <Card3D padding={spacing.xl} style={styles.block}>
          <Text style={styles.bigIcon}>📝</Text>
          <Text style={[typography.h2, styles.center, { color: colors.textStrong }]}>เอกสารยังไม่ผ่าน</Text>
          <Text style={[typography.body, styles.center, styles.gapTop, { color: colors.textMuted }]}>
            ไม่เป็นไร แก้ตามนี้แล้วส่งใหม่ได้เลย
          </Text>
          {!!rejectionReason && (
            <Card3D variant="inset" padding={spacing.md} style={styles.gapTopLg}>
              <Text style={[typography.caption, { color: colors.textMuted }]}>เหตุผลจากทีมงาน</Text>
              <Text style={[typography.bodyStrong, { color: colors.danger }]}>{rejectionReason}</Text>
            </Card3D>
          )}
          <Button3D
            title="ส่งเอกสารใหม่"
            icon="🔁"
            size="lg"
            fullWidth
            style={styles.gapTopLg}
            onPress={() => {
              setStatus('not_submitted');
              setHas({ id_card: false, selfie: false });
              setUris({ id_card: null, selfie: null });
            }}
          />
        </Card3D>
      </Screen>
    );
  }

  const ready = has.id_card && has.selfie;

  return (
    <Screen title="ยืนยันตัวตน" subtitle="ใช้เวลาไม่ถึง 2 นาที">
      <Card3D variant="flat" padding={spacing.md} style={styles.block}>
        <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>🔒 ข้อมูลของคุณปลอดภัย</Text>
        <Text style={[typography.bodySm, { color: colors.textMuted }]}>
          รูปใช้ยืนยันตัวตนเพื่อความปลอดภัยของการถอนเงินเท่านั้น ทีมงานที่ได้รับอนุญาตเท่านั้นที่เห็น
        </Text>
      </Card3D>

      <UploadCard
        type="id_card"
        step={1}
        done={has.id_card}
        uri={uris.id_card}
        uploading={uploading.id_card}
        onCamera={() => openCamera('id_card')}
        onGallery={() => pickFromGallery('id_card')}
      />
      <UploadCard
        type="selfie"
        step={2}
        done={has.selfie}
        uri={uris.selfie}
        uploading={uploading.selfie}
        onCamera={() => openCamera('selfie')}
        onGallery={() => pickFromGallery('selfie')}
      />

      <Button3D
        title={ready ? 'ส่งเอกสารให้ตรวจ' : 'อัปโหลดให้ครบ 2 รูปก่อนนะ'}
        icon="🛡️"
        variant="success"
        size="lg"
        fullWidth
        disabled={!ready || uploading.id_card || uploading.selfie}
        loading={submitting}
        loadingText="กำลังส่ง..."
        onPress={submit}
        style={styles.gapTopLg}
      />
      <Text style={[typography.caption, styles.center, styles.gapTop, { color: colors.textFaint }]}>
        ตรวจภายใน 24–48 ชั่วโมง ผลจะแจ้งเตือนมาที่แอป
      </Text>

      {/* ---------- กล้อง ---------- */}
      <Modal visible={cameraFor !== null} animationType="slide" onRequestClose={() => setCameraFor(null)} statusBarTranslucent>
        <View style={[styles.cameraRoot, { backgroundColor: palette.black }]}>
          <View style={[styles.cameraHeader, { paddingTop: insets.top + spacing.sm }]}>
            <Button3D title="ปิด" size="sm" variant="secondary" onPress={() => setCameraFor(null)} />
            <Text style={[typography.h3, styles.cameraTitle]}>{cameraFor ? COPY[cameraFor].title : ''}</Text>
            <View style={styles.headerSpacer} />
          </View>
          {cameraFor && cameraPermission?.granted ? (
            <CameraView ref={cameraRef} style={styles.flex} facing={cameraFor === 'selfie' ? 'front' : 'back'}>
              <View style={styles.overlay} pointerEvents="none">
                <View style={cameraFor === 'id_card' ? styles.cardFrame : styles.selfieFrame} />
              </View>
            </CameraView>
          ) : (
            <View style={[styles.flex, styles.centerBox]}>
              <Text style={[typography.body, styles.cameraTitle]}>ยังใช้กล้องไม่ได้</Text>
            </View>
          )}
          <View style={[styles.cameraFooter, { paddingBottom: insets.bottom + spacing.lg }]}>
            <Text style={[typography.bodySm, styles.cameraHint]}>{cameraFor ? COPY[cameraFor].frame : ''}</Text>
            <Pressable
              onPress={capture}
              disabled={capturing}
              accessibilityRole="button"
              accessibilityLabel="ถ่ายรูป"
              style={({ pressed }) => [styles.shutter, { opacity: pressed || capturing ? 0.6 : 1 }]}
            >
              {capturing ? <ActivityIndicator color={palette.gold600} /> : <View style={styles.shutterInner} />}
            </Pressable>
          </View>
        </View>
      </Modal>
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    textAlign: 'center',
  },
  centerBox: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  loader: {
    marginTop: spacing.xxxl,
  },
  block: {
    marginBottom: spacing.lg,
  },
  gapTop: {
    marginTop: spacing.sm,
  },
  gapTopLg: {
    marginTop: spacing.lg,
  },
  bigIcon: {
    fontSize: 52,
    textAlign: 'center',
    marginBottom: spacing.sm,
  },
  cardHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  stepBadge: {
    width: 34,
    height: 34,
    borderRadius: 17,
    alignItems: 'center',
    justifyContent: 'center',
  },
  preview: {
    height: 180,
    marginTop: spacing.md,
    borderRadius: radii.lg,
    borderWidth: 1,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  previewTall: {
    height: 240,
  },
  previewIcon: {
    fontSize: 44,
    marginBottom: spacing.xs,
  },
  actions: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  cameraRoot: {
    flex: 1,
  },
  cameraHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.screen,
    paddingBottom: spacing.md,
  },
  headerSpacer: {
    width: 56,
  },
  cameraTitle: {
    color: palette.white,
    textAlign: 'center',
  },
  overlay: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  cardFrame: {
    width: '86%',
    aspectRatio: 1.6,
    borderRadius: radii.lg,
    borderWidth: 3,
    borderColor: palette.gold300,
  },
  selfieFrame: {
    width: '70%',
    aspectRatio: 0.8,
    borderRadius: 999,
    borderWidth: 3,
    borderColor: palette.gold300,
  },
  cameraFooter: {
    alignItems: 'center',
    paddingTop: spacing.lg,
    gap: spacing.md,
  },
  cameraHint: {
    color: 'rgba(255,255,255,0.85)',
    textAlign: 'center',
    paddingHorizontal: spacing.xl,
  },
  shutter: {
    width: 76,
    height: 76,
    borderRadius: 38,
    borderWidth: 4,
    borderColor: palette.gold300,
    alignItems: 'center',
    justifyContent: 'center',
  },
  shutterInner: {
    width: 58,
    height: 58,
    borderRadius: 29,
    backgroundColor: palette.white,
  },
});
