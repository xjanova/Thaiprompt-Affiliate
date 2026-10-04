/**
 * รูปโปรไฟล์ถ่ายสด — GET/POST /me/profile-photo (ตามแบบ ProfilePhoto.png ที่เจ้าของอนุมัติ)
 *
 * - ถ่ายจากกล้องหน้าเท่านั้น (ไม่มีปุ่มเลือกรูปจากคลัง) + กรอบวงรีทองช่วยจัดหน้า + เคล็ดลับ หน้าตรง/ไม่ใส่แว่นดำ/แสงพอ
 * - ถ่ายแล้วดูตัวอย่างก่อน → "ใช้รูปนี้" อัปโหลด → แสดงรูปพร้อมลายน้ำที่คนอื่นจะเห็น (photo_url จาก server)
 * - ไฟล์รูปชั่วคราวในเครื่องถูกลบทิ้งหลังอัปโหลด/ถ่ายใหม่/ออกจากหน้า
 * - ?gate=1 = บังคับหลังล็อกอิน (ไม่มีปุ่มย้อนกลับ ปุ่มย้อนกลับของเครื่องถูกกันไว้จนกว่าจะมีรูป) แต่ออกจากระบบได้เสมอ
 *   ถ่ายเสร็จ → กลับไปหน้าที่ผู้ใช้อยู่ก่อนถูกพามา (router.back) ไม่เด้งไปหน้าแรก (U7) · ไม่มีหน้าก่อนหน้า = หน้าแรก
 * - ?from=checkout | rider | seller = มาจากการกระทำที่ server ตอบ PROFILE_PHOTO_REQUIRED (U8)
 *   → ถ่ายเสร็จแล้วกลับไปทำต่อ (สั่งซื้อ / เริ่มรับงาน / ส่งคำขอเปิดร้าน)
 * - กันแคปหน้าจอระหว่างเปิดหน้านี้
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, BackHandler, ScrollView, StatusBar, StyleSheet, View } from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { Image } from 'expo-image';
import * as FileSystem from 'expo-file-system/legacy';
import { router, useLocalSearchParams, useNavigation } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '@/components/ui/Text';
import {
  Button3D,
  Card3D,
  GlassIconButton,
  Icon,
  OnHeaderProvider,
  Pill,
  RoyalHeader,
  resultHaptic,
} from '@/components/ui';
import { StickyBar } from '@/components/shop';
import { PersonAvatar } from '@/components/people/PersonAvatar';
import { useMountedRef } from '@/components/taladsod/hooks';
import { useSensitiveScreen } from '@/hooks/useSensitiveScreen';
import { useAuthStore } from '@/stores/authStore';
import { getProfilePhoto, uploadProfilePhoto, type ProfilePhotoStatus } from '@/services/api/profilePhotoApi';
import { markProfilePhotoDone } from '@/components/people/ProfilePhotoGate';
import { DARK_THEME, useTheme, radii, spacing, typography, withAlpha } from '@/theme';

type Phase = 'idle' | 'camera' | 'preview' | 'uploading' | 'done';

const SHEET_RADIUS = 26;
const TIPS = ['หน้าตรง', 'ไม่ใส่แว่นดำ', 'แสงพอ'];

/** ลบไฟล์รูปชั่วคราว (เงียบ) */
const dropTempFile = (uri: string | null) => {
  if (!uri || !uri.startsWith('file://')) return;
  FileSystem.deleteAsync(uri, { idempotent: true }).catch(() => {});
};

export default function ProfilePhotoScreen() {
  const params = useLocalSearchParams<{ gate?: string; from?: string }>();
  const gate = params.gate === '1';
  const fromCheckout = params.from === 'checkout';
  /** ข้อความปุ่มกลับไปทำต่อ ตามหน้าที่พามา */
  const returnLabel = fromCheckout
    ? 'กลับไปสั่งต่อ'
    : params.from === 'rider'
      ? 'กลับไปเริ่มรับงาน'
      : params.from === 'seller'
        ? 'กลับไปส่งคำขอ'
        : 'เสร็จแล้ว';
  useSensitiveScreen('profile-photo');

  const { colors, gradients } = useTheme();
  const cam = DARK_THEME.colors;
  const insets = useSafeAreaInsets();
  const navigation = useNavigation();
  const mountedRef = useMountedRef();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const userName = useAuthStore((s) => s.user?.name ?? '');
  const logout = useAuthStore((s) => s.logout);
  const [permission, requestPermission] = useCameraPermissions();

  const [status, setStatus] = useState<ProfilePhotoStatus | null>(null);
  const [statusLoading, setStatusLoading] = useState(true);
  const [phase, setPhase] = useState<Phase>('idle');
  const [captured, setCaptured] = useState<string | null>(null);
  const [cameraReady, setCameraReady] = useState(false);
  const [capturing, setCapturing] = useState(false);
  const cameraRef = useRef<CameraView>(null);
  const capturedRef = useRef<string | null>(null);
  const leavingRef = useRef(false);
  capturedRef.current = captured;

  // ---------- สถานะรูปปัจจุบัน ----------
  const loadStatus = useCallback(async () => {
    const res = await getProfilePhoto();
    if (!mountedRef.current) return;
    setStatusLoading(false);
    if (res.success) setStatus(res.data);
  }, [mountedRef]);

  useEffect(() => {
    if (isAuthenticated) loadStatus();
    else setStatusLoading(false);
  }, [isAuthenticated, loadStatus]);

  // ลบรูปชั่วคราวตอนออกจากหน้า
  useEffect(() => () => dropTempFile(capturedRef.current), []);

  const hasPhoto = !!status?.has_photo || phase === 'done';

  // ---------- โหมดบังคับ: กันย้อนกลับจนกว่าจะมีรูป ----------
  useEffect(() => {
    if (!gate) return undefined;
    const unsub = navigation.addListener('beforeRemove', (event: any) => {
      if (leavingRef.current || hasPhoto || !isAuthenticated) return;
      event.preventDefault();
      Alert.alert('ถ่ายรูปโปรไฟล์ก่อนนะ', 'ทุกบัญชีต้องมีรูปจริงก่อนเริ่มใช้งาน ใช้เวลาไม่ถึงนาที');
    });
    const sub = BackHandler.addEventListener('hardwareBackPress', () => !hasPhoto && isAuthenticated);
    return () => {
      unsub();
      sub.remove();
    };
  }, [gate, navigation, hasPhoto, isAuthenticated]);

  // ---------- กล้อง ----------
  const openCamera = async () => {
    if (!permission?.granted) {
      const res = await requestPermission().catch(() => null);
      if (!mountedRef.current) return;
      if (!res?.granted) {
        Alert.alert(
          'ขอใช้กล้องก่อนนะ',
          res && !res.canAskAgain
            ? 'เปิดสิทธิ์กล้องให้แอปในการตั้งค่าของเครื่อง แล้วกลับมาถ่ายรูปอีกครั้ง'
            : 'รูปโปรไฟล์ต้องถ่ายสดจากกล้องเท่านั้น'
        );
        return;
      }
    }
    dropTempFile(captured);
    setCaptured(null);
    setCameraReady(false);
    setPhase('camera');
  };

  const shoot = async () => {
    if (!cameraRef.current || capturing || !cameraReady) return;
    setCapturing(true);
    try {
      const photo = await cameraRef.current.takePictureAsync({ quality: 0.7, shutterSound: false });
      if (!mountedRef.current) {
        dropTempFile(photo?.uri ?? null);
        return;
      }
      if (photo?.uri) {
        resultHaptic('success');
        setCaptured(photo.uri);
        setPhase('preview');
      }
    } catch {
      if (mountedRef.current) Alert.alert('ถ่ายรูปไม่สำเร็จ', 'ลองใหม่อีกครั้งนะ');
    } finally {
      if (mountedRef.current) setCapturing(false);
    }
  };

  const upload = async () => {
    if (!captured || phase === 'uploading') return;
    setPhase('uploading');
    const res = await uploadProfilePhoto(captured);
    if (!mountedRef.current) return;
    if (res.success) {
      resultHaptic('success');
      dropTempFile(captured);
      setCaptured(null);
      setStatus(res.data);
      setPhase('done');
      markProfilePhotoDone();
      return;
    }
    resultHaptic('error');
    setPhase('preview');
    Alert.alert('อัปโหลดรูปไม่สำเร็จ', res.message);
  };

  const finish = () => {
    leavingRef.current = true;
    // โหมดบังคับก็กลับไปหน้าเดิมที่ผู้ใช้อยู่ (ตัวบังคับ push หน้านี้ทับไว้) — ไม่มีหน้าก่อนหน้าค่อยไปหน้าแรก (U7)
    if (router.canGoBack()) router.back();
    else router.replace('/(tabs)' as never);
  };

  const doLogout = () => {
    Alert.alert('ออกจากระบบ', 'ออกจากระบบตอนนี้? ถ่ายรูปได้ทุกเมื่อเมื่อกลับมาเข้าสู่ระบบ', [
      { text: 'ยกเลิก', style: 'cancel' },
      {
        text: 'ออกจากระบบ',
        style: 'destructive',
        onPress: async () => {
          leavingRef.current = true;
          await logout();
          router.replace('/');
        },
      },
    ]);
  };

  // ---------- ส่วนแสดงผล ----------
  const stageHeight = 300;
  const ovalW = 190;
  const ovalH = 240;

  const stage = (
    <View style={[styles.stage, { height: stageHeight, backgroundColor: cam.background }]}>
      {phase === 'camera' && permission?.granted ? (
        <CameraView
          ref={cameraRef}
          style={StyleSheet.absoluteFill}
          facing="front"
          mirror
          animateShutter={false}
          onCameraReady={() => setCameraReady(true)}
          onMountError={() => {
            setPhase('idle');
            Alert.alert('เปิดกล้องไม่ได้', 'กล้องของเครื่องใช้งานไม่ได้ตอนนี้ ลองใหม่อีกครั้งนะ');
          }}
        />
      ) : (phase === 'preview' || phase === 'uploading') && captured ? (
        <Image source={{ uri: captured }} style={StyleSheet.absoluteFill} contentFit="cover" cachePolicy="none" />
      ) : (
        <View style={[StyleSheet.absoluteFill, styles.silhouetteBox]}>
          <View style={[styles.head, { backgroundColor: withAlpha(cam.textFaint, 0.5) }]} />
          <View style={[styles.shoulders, { backgroundColor: withAlpha(cam.textFaint, 0.5) }]} />
        </View>
      )}
      {/* กรอบวงรีทองจัดหน้า */}
      <View pointerEvents="none" style={[StyleSheet.absoluteFill, styles.center]}>
        <View style={[styles.oval, { width: ovalW, height: ovalH, borderRadius: ovalW, borderColor: cam.gold }]} />
      </View>
      {phase === 'uploading' && (
        <View style={[StyleSheet.absoluteFill, styles.center, { backgroundColor: withAlpha(cam.background, 0.55) }]}>
          <ActivityIndicator size="large" color={cam.gold} />
          <Text style={[typography.bodyStrong, { color: cam.textStrong }]}>กำลังอัปโหลด…</Text>
        </View>
      )}
      <View style={styles.tips}>
        {TIPS.map((t) => (
          <View key={t} style={[styles.tip, { backgroundColor: withAlpha(cam.card, 0.85), borderColor: cam.border }]}>
            <Icon name="check" size={12} color={cam.textStrong} weight="bold" />
            <Text style={[typography.micro, { color: cam.textStrong }]}>{t}</Text>
          </View>
        ))}
      </View>
    </View>
  );

  const shownPhoto = status?.photo_url ?? null;

  const bottom = (() => {
    if (phase === 'camera') {
      return (
        <Button3D
          title={cameraReady ? 'กดถ่าย' : 'กำลังเปิดกล้อง…'}
          icon="camera"
          size="lg"
          fullWidth
          loading={capturing}
          disabled={!cameraReady}
          onPress={shoot}
        />
      );
    }
    if (phase === 'preview' || phase === 'uploading') {
      return (
        <View style={styles.row}>
          <Button3D title="ถ่ายใหม่" icon="arrows-clockwise" variant="secondary" size="lg" disabled={phase === 'uploading'} onPress={openCamera} style={styles.flex} />
          <Button3D title="ใช้รูปนี้" icon="check-circle" size="lg" loading={phase === 'uploading'} onPress={upload} style={styles.flex} />
        </View>
      );
    }
    if (hasPhoto) {
      return (
        <View style={styles.row}>
          <Button3D title="ถ่ายใหม่" icon="camera" variant="secondary" size="lg" onPress={openCamera} style={styles.flex} />
          <Button3D
            title={gate ? 'เริ่มใช้งาน' : returnLabel}
            icon="check-circle"
            size="lg"
            onPress={finish}
            style={styles.flex}
          />
        </View>
      );
    }
    return <Button3D title="ถ่ายรูปตอนนี้" icon="camera" size="lg" fullWidth onPress={openCamera} />;
  })();

  return (
    <View style={[styles.root, { backgroundColor: gradients.hero[gradients.hero.length - 1] }]}>
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      <RoyalHeader
        ornamentTop={insets.top - 18}
        ornamentWidth={210}
        style={{ paddingTop: insets.top + spacing.xs, paddingBottom: SHEET_RADIUS + spacing.md }}
      >
        <OnHeaderProvider value>
          <View style={styles.headerRow}>
            {!gate && <GlassIconButton icon="caret-left" weight="bold" accessibilityLabel="ย้อนกลับ" onPress={finish} />}
            <View style={[styles.flex, gate && styles.titleNoBack]}>
              <Text accessibilityRole="header" numberOfLines={1} style={[typography.serif, { color: colors.onHeader }]}>
                รูปโปรไฟล์ของคุณ
              </Text>
              <Text numberOfLines={1} style={[typography.bodySm, { color: colors.onHeaderMuted }]}>
                {gate ? 'ขั้นตอนสุดท้ายก่อนเริ่มใช้งาน' : 'ถ่ายรูปใหม่ได้ทุกเมื่อ'}
              </Text>
            </View>
          </View>
          <View style={styles.headerPill}>
            <Pill label="จำเป็นทุกบัญชี" icon="user" tone="gold" />
          </View>
        </OnHeaderProvider>
      </RoyalHeader>

      <View style={[styles.sheet, { backgroundColor: colors.background }]}>
        <ScrollView contentContainerStyle={[styles.content, { paddingBottom: 120 + insets.bottom }]} showsVerticalScrollIndicator={false}>
          <Text style={[typography.body, { color: colors.text }]}>
            ไทยพร้อมใช้ความเชื่อใจ ทุกคนต้องมีรูปจริง ทั้งผู้ซื้อ ไรเดอร์ และเจ้าของร้าน จะได้เห็นหน้ากันก่อนส่งของ
          </Text>

          <View style={styles.gapTop}>{stage}</View>

          {/* รูปที่คนอื่นเห็น (ลายน้ำจาก server) */}
          <Card3D padding={spacing.lg} radius={22} style={styles.gapTop}>
            <View style={styles.row}>
              <View style={[styles.wmBox, { backgroundColor: colors.inset }]}>
                {statusLoading ? (
                  <ActivityIndicator color={colors.gold} />
                ) : shownPhoto ? (
                  <Image source={{ uri: shownPhoto }} style={styles.wmImage} contentFit="cover" cachePolicy="memory" />
                ) : (
                  <Icon name="user" size={40} color={colors.textFaint} weight="fill" />
                )}
              </View>
              <View style={styles.flex}>
                <Text style={[typography.h3, { color: colors.textStrong }]}>
                  {shownPhoto ? 'คนอื่นจะเห็นรูปคุณแบบนี้' : 'คนอื่นจะเห็นรูปคุณพร้อมลายน้ำ'}
                </Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  ลายน้ำมีรหัสของคนที่เปิดดู ถ้ารูปหลุดออกไป เรารู้ว่าหลุดจากบัญชีไหน
                </Text>
              </View>
            </View>
          </Card3D>

          <View style={[styles.roles, styles.gapTop]}>
            {['ผู้ซื้อ', 'ไรเดอร์', 'เจ้าของร้าน'].map((role) => (
              <Card3D key={role} padding={spacing.md} radius={20} style={styles.flex}>
                <View style={styles.roleInner}>
                  <PersonAvatar uri={shownPhoto} name={userName} size={46} />
                  <Text style={[typography.caption, styles.bold, { color: colors.textStrong }]}>{role}</Text>
                </View>
              </Card3D>
            ))}
          </View>

          <View style={[styles.rules, styles.gapTop, { backgroundColor: colors.infoSoft, borderColor: colors.border }]}>
            <View style={styles.ruleRow}>
              <Icon name="camera" size={18} color={colors.navy} />
              <Text style={[typography.caption, styles.flex, { color: colors.text }]}>ต้องถ่ายสดจากกล้องเท่านั้น เลือกรูปจากคลังไม่ได้</Text>
            </View>
            <View style={styles.ruleRow}>
              <Icon name="shield-check" size={18} color={colors.navy} />
              <Text style={[typography.caption, styles.flex, { color: colors.text }]}>หน้าที่มีข้อมูลส่วนตัว แคปหน้าจอและอัดหน้าจอไม่ได้</Text>
            </View>
          </View>

          {gate && isAuthenticated && !hasPhoto && (
            <Button3D title="ออกจากระบบ" icon="sign-out" variant="ghost" size="sm" onPress={doLogout} style={styles.logout} />
          )}
        </ScrollView>
      </View>

      <StickyBar>{bottom}</StickyBar>
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  bold: {
    fontWeight: '700',
  },
  center: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
  },
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
  },
  titleNoBack: {
    paddingLeft: spacing.xs,
  },
  headerPill: {
    flexDirection: 'row',
    paddingHorizontal: spacing.screen,
    marginTop: spacing.md,
  },
  sheet: {
    flex: 1,
    marginTop: -SHEET_RADIUS,
    borderTopLeftRadius: SHEET_RADIUS,
    borderTopRightRadius: SHEET_RADIUS,
    overflow: 'hidden',
  },
  content: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.xl,
  },
  gapTop: {
    marginTop: spacing.lg,
  },
  stage: {
    borderRadius: radii.xxl,
    overflow: 'hidden',
  },
  silhouetteBox: {
    alignItems: 'center',
    justifyContent: 'flex-end',
  },
  head: {
    width: 104,
    height: 104,
    borderRadius: 52,
    marginBottom: spacing.md,
  },
  shoulders: {
    width: 200,
    height: 90,
    borderTopLeftRadius: 100,
    borderTopRightRadius: 100,
  },
  oval: {
    borderWidth: 3,
    borderStyle: 'dashed',
  },
  tips: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: spacing.md,
    flexDirection: 'row',
    justifyContent: 'center',
    gap: spacing.sm,
  },
  tip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    borderRadius: radii.pill,
    borderWidth: 1,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  wmBox: {
    width: 96,
    height: 112,
    borderRadius: 18,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  wmImage: {
    width: 96,
    height: 112,
  },
  roles: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  roleInner: {
    alignItems: 'center',
    gap: spacing.xs,
  },
  rules: {
    borderRadius: radii.lg,
    borderWidth: 1,
    padding: spacing.md,
    gap: spacing.sm,
  },
  ruleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  logout: {
    alignSelf: 'center',
    marginTop: spacing.lg,
  },
});
