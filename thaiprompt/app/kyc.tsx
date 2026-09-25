/**
 * ยืนยันตัวตน (KYC) — ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * API (MobileApiController): GET /kyc/status · POST /kyc/upload (multipart image + type) · POST /kyc/confirm
 * - เรียกผ่าน client กลาง → ข้อความผิดพลาดเป็นภาษาไทยเสมอ (ไม่แสดง error ดิบ)
 * - ขั้นตอน: รูปบัตรประชาชน → เซลฟี่ถือบัตร → ส่งตรวจ (ถามยืนยันก่อน, กันกดซ้ำ) · แถบขั้นตอนด้านบนบอกว่าถึงไหนแล้ว
 * - กล้อง: ขอสิทธิ์ตอนกด "ถ่ายรูป" เท่านั้น · ปฏิเสธถาวร → พาไปตั้งค่าเครื่อง
 * - คลังรูป: ใช้ตัวเลือกรูปของระบบ (ไม่ต้องขอสิทธิ์อ่านรูปทั้งเครื่อง)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Linking, Modal, Pressable, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { CameraView, useCameraPermissions } from 'expo-camera';
import * as ImagePicker from 'expo-image-picker';
import { router } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { apiGet, apiPost, apiUpload, fileFromUri, type ApiResult } from '@/services/api/client';
import {
  Button3D,
  Card3D,
  EmptyState,
  Icon,
  OnHeaderProvider,
  Pill,
  Screen,
  resultHaptic,
  type IconName,
} from '@/components/ui';
import { IconTile } from '@/components/profile';
import { useTheme, DARK_THEME, radii, spacing, typography } from '@/theme';

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

const COPY: Record<ImageType, { title: string; hint: string; icon: IconName; frame: string }> = {
  id_card: { title: 'รูปบัตรประชาชน', hint: 'ด้านหน้า เห็นตัวอักษรชัด ไม่มีแสงสะท้อน', icon: 'identification-card', frame: 'วางบัตรให้อยู่ในกรอบ ให้เห็นข้อมูลชัดเจน' },
  selfie: { title: 'เซลฟี่ถือบัตร', hint: 'เห็นหน้าคุณและบัตรในรูปเดียวกัน', icon: 'user-circle', frame: 'ถือบัตรข้างใบหน้า ให้เห็นทั้งหน้าและบัตร' },
};

/** กล้องใช้โทนมืดเสมอ (ไม่ขึ้นกับโหมดของแอป) ให้ภาพจากกล้องเด่นและกรอบทองชัด */
const CAM = DARK_THEME.colors;

// =====================================================
// แถบขั้นตอน (บัตร → เซลฟี่ → ส่งตรวจ)
// =====================================================

const STEPS = ['บัตรประชาชน', 'เซลฟี่ถือบัตร', 'ส่งตรวจ'];

const StepBar: React.FC<{ done: boolean[] }> = ({ done }) => {
  const { colors, gradients } = useTheme();
  const current = done.findIndex((d) => !d);
  return (
    <View style={styles.stepBar} accessibilityRole="progressbar" accessibilityLabel={`ขั้นตอนที่ ${current + 1} จาก ${STEPS.length}`}>
      {STEPS.map((label, index) => {
        const isDone = done[index];
        const isCurrent = index === current;
        return (
          <React.Fragment key={label}>
            {index > 0 && (
              <View style={[styles.stepLine, { backgroundColor: done[index - 1] ? colors.success : colors.border }]} />
            )}
            <View style={styles.stepItem}>
              {isDone ? (
                <View style={[styles.stepDot, { backgroundColor: colors.success }]}>
                  <Icon name="check" size={14} color={colors.textOnAccent} weight="bold" />
                </View>
              ) : isCurrent ? (
                <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 0, y: 1 }} style={styles.stepDot}>
                  <Text style={[styles.stepNum, { color: colors.goldLight }]}>{index + 1}</Text>
                </LinearGradient>
              ) : (
                <View style={[styles.stepDot, { backgroundColor: colors.inset, borderWidth: 1, borderColor: colors.border }]}>
                  <Text style={[styles.stepNum, { color: colors.textFaint }]}>{index + 1}</Text>
                </View>
              )}
              <Text
                numberOfLines={1}
                style={[typography.micro, styles.stepLabel, { color: isDone || isCurrent ? colors.textStrong : colors.textFaint }]}
              >
                {label}
              </Text>
            </View>
          </React.Fragment>
        );
      })}
    </View>
  );
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
        {done ? (
          <View style={[styles.stepBadge, { backgroundColor: colors.successSoft }]}>
            <Icon name="check" size={16} color={colors.success} weight="bold" />
          </View>
        ) : (
          <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 0, y: 1 }} style={styles.stepBadge}>
            <Text style={[typography.bodyStrong, { color: colors.goldLight }]}>{step}</Text>
          </LinearGradient>
        )}
        <View style={styles.flex}>
          <Text style={[typography.h3, { color: colors.textStrong }]}>{copy.title}</Text>
          <Text style={[typography.caption, { color: colors.textMuted }]}>{copy.hint}</Text>
        </View>
        {done && <Pill label="อัปโหลดแล้ว" tone="success" />}
      </View>

      <View
        style={[
          styles.preview,
          type === 'selfie' && styles.previewTall,
          {
            backgroundColor: colors.inset,
            borderColor: uri ? colors.border : done ? colors.success : colors.gold,
            borderStyle: uri ? 'solid' : 'dashed',
          },
        ]}
      >
        {uploading ? (
          <>
            <ActivityIndicator size="large" color={colors.gold} />
            <Text style={[typography.caption, styles.gapTop, { color: colors.textMuted }]}>กำลังอัปโหลด...</Text>
          </>
        ) : uri ? (
          <Image source={{ uri }} style={StyleSheet.absoluteFill} contentFit="cover" />
        ) : (
          <>
            <View style={[styles.previewIcon, { backgroundColor: done ? colors.successSoft : colors.goldSoft }]}>
              <Icon
                name={done ? 'check-circle' : copy.icon}
                size={34}
                color={done ? colors.success : colors.goldDeep}
                weight={done ? 'fill' : 'regular'}
              />
            </View>
            <Text style={[typography.caption, { color: colors.textMuted }]}>{done ? 'ส่งรูปนี้แล้ว ถ่ายใหม่ได้' : 'ยังไม่มีรูป'}</Text>
          </>
        )}
      </View>

      <View style={styles.actions}>
        <Button3D title="ถ่ายรูป" icon="camera" variant="navy" size="md" disabled={uploading} onPress={onCamera} style={styles.flex} />
        <Button3D title="เลือกจากคลัง" icon="images" variant="secondary" size="md" disabled={uploading} onPress={onGallery} style={styles.flex} />
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
        <EmptyState icon="shield-check" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
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
          icon="seal-check"
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
          icon="hourglass"
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
        <Card3D padding={spacing.xl} style={styles.block} contentStyle={styles.rejected}>
          <View style={[styles.bigIcon, { backgroundColor: colors.dangerSoft }]}>
            <Icon name="note-pencil" size={36} color={colors.danger} />
          </View>
          <Text style={[typography.serif, styles.center, { color: colors.textStrong }]}>เอกสารยังไม่ผ่าน</Text>
          <Text style={[typography.body, styles.center, { color: colors.textMuted }]}>
            ไม่เป็นไร แก้ตามนี้แล้วส่งใหม่ได้เลย
          </Text>
          {!!rejectionReason && (
            <View style={[styles.reason, { backgroundColor: colors.dangerSoft }]}>
              <Icon name="warning-circle" size={20} color={colors.danger} weight="fill" />
              <View style={styles.flex}>
                <Text style={[typography.caption, { color: colors.textMuted }]}>เหตุผลจากทีมงาน</Text>
                <Text style={[typography.bodyStrong, { color: colors.danger }]}>{rejectionReason}</Text>
              </View>
            </View>
          )}
          <Button3D
            title="ส่งเอกสารใหม่"
            icon="arrows-clockwise"
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
      <StepBar done={[has.id_card, has.selfie, false]} />

      <Card3D padding={spacing.md} shadow="sm" style={styles.block}>
        <View style={styles.safeRow}>
          <IconTile icon="lock-key" tone="success" />
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ข้อมูลของคุณปลอดภัย</Text>
            <Text style={[typography.bodySm, { color: colors.textMuted }]}>
              รูปใช้ยืนยันตัวตนเพื่อความปลอดภัยของการถอนเงินเท่านั้น ทีมงานที่ได้รับอนุญาตเท่านั้นที่เห็น
            </Text>
          </View>
        </View>
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
        icon="shield-check"
        variant="primary"
        size="lg"
        fullWidth
        disabled={!ready || uploading.id_card || uploading.selfie}
        loading={submitting}
        loadingText="กำลังส่ง..."
        onPress={submit}
        style={styles.gapTopLg}
      />
      <View style={styles.footRow}>
        <Icon name="clock" size={14} color={colors.textFaint} />
        <Text style={[typography.caption, { color: colors.textFaint }]}>ตรวจภายใน 24–48 ชั่วโมง ผลจะแจ้งเตือนมาที่แอป</Text>
      </View>

      {/* ---------- กล้อง (โทนมืดเสมอ) ---------- */}
      <Modal visible={cameraFor !== null} animationType="slide" onRequestClose={() => setCameraFor(null)} statusBarTranslucent>
        <View style={[styles.cameraRoot, { backgroundColor: CAM.navyDeep }]}>
          <View style={[styles.cameraHeader, { paddingTop: insets.top + spacing.sm }]}>
            <OnHeaderProvider value>
              <Button3D title="ปิด" icon="x" size="sm" variant="secondary" onPress={() => setCameraFor(null)} />
            </OnHeaderProvider>
            <Text style={[typography.serifSm, styles.cameraTitle, { color: CAM.onHeader }]}>
              {cameraFor ? COPY[cameraFor].title : ''}
            </Text>
            <View style={styles.headerSpacer} />
          </View>
          {cameraFor && cameraPermission?.granted ? (
            <CameraView ref={cameraRef} style={styles.flex} facing={cameraFor === 'selfie' ? 'front' : 'back'}>
              <View style={styles.overlay} pointerEvents="none">
                <View style={[cameraFor === 'id_card' ? styles.cardFrame : styles.selfieFrame, { borderColor: CAM.gold }]} />
              </View>
            </CameraView>
          ) : (
            <View style={[styles.flex, styles.centerBox]}>
              <Icon name="camera" size={40} color={CAM.onHeaderMuted} />
              <Text style={[typography.body, styles.cameraTitle, { color: CAM.onHeader }]}>ยังใช้กล้องไม่ได้</Text>
            </View>
          )}
          <View style={[styles.cameraFooter, { paddingBottom: insets.bottom + spacing.lg }]}>
            <Text style={[typography.bodySm, styles.cameraHint, { color: CAM.onHeaderMuted }]}>
              {cameraFor ? COPY[cameraFor].frame : ''}
            </Text>
            <Pressable
              onPress={capture}
              disabled={capturing}
              accessibilityRole="button"
              accessibilityLabel="ถ่ายรูป"
              style={({ pressed }) => [styles.shutter, { borderColor: CAM.gold, opacity: pressed || capturing ? 0.6 : 1 }]}
            >
              {capturing ? (
                <ActivityIndicator color={CAM.gold} />
              ) : (
                <View style={[styles.shutterInner, { backgroundColor: CAM.onHeader }]} />
              )}
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
    gap: spacing.sm,
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

  // ---------- แถบขั้นตอน ----------
  stepBar: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginBottom: spacing.xl,
    paddingHorizontal: spacing.xs,
  },
  stepItem: {
    alignItems: 'center',
    width: 84,
  },
  stepDot: {
    width: 30,
    height: 30,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepNum: {
    fontSize: 13.5,
    fontWeight: '700',
  },
  stepLabel: {
    marginTop: spacing.xs,
    textAlign: 'center',
  },
  stepLine: {
    flex: 1,
    height: 2,
    borderRadius: 1,
    marginTop: 14,
    marginHorizontal: -spacing.lg,
  },

  // ---------- การ์ด ----------
  safeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  rejected: {
    alignItems: 'center',
    gap: spacing.sm,
  },
  bigIcon: {
    width: 76,
    height: 76,
    borderRadius: 26,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm,
  },
  reason: {
    alignSelf: 'stretch',
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    padding: spacing.md,
    borderRadius: radii.md,
    marginTop: spacing.md,
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
    borderWidth: 1.5,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  previewTall: {
    height: 240,
  },
  previewIcon: {
    width: 64,
    height: 64,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm,
  },
  actions: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  footRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xs,
    marginTop: spacing.md,
  },

  // ---------- กล้อง ----------
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
    width: 72,
  },
  cameraTitle: {
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
  },
  selfieFrame: {
    width: '70%',
    aspectRatio: 0.8,
    borderRadius: 999,
    borderWidth: 3,
  },
  cameraFooter: {
    alignItems: 'center',
    paddingTop: spacing.lg,
    gap: spacing.md,
  },
  cameraHint: {
    textAlign: 'center',
    paddingHorizontal: spacing.xl,
  },
  shutter: {
    width: 76,
    height: 76,
    borderRadius: 38,
    borderWidth: 4,
    alignItems: 'center',
    justifyContent: 'center',
  },
  shutterInner: {
    width: 58,
    height: 58,
    borderRadius: 29,
  },
});
