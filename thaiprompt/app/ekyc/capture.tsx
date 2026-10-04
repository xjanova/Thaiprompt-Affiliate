/**
 * ถ่ายบัตรประชาชน (ขั้นที่ 2 จาก 4) — ตามแบบ IdCapture.png
 *
 * สองโหมด
 *   1. ตัวสแกนเอกสารของ Google (Android ที่มี Play services): กดปุ่มทอง → จับขอบบัตรอัตโนมัติ ถ่ายเองเมื่อนิ่ง ตัดภาพให้ตรง
 *      ใช้ไม่ได้ในเครื่องนี้ (ไม่มี Play services/โหลดโมดูลไม่สำเร็จ) → สลับเป็นกล้องในแอปเองพร้อมบอกผู้ใช้
 *   2. กล้องในแอป (iOS / เครื่องที่ไม่มีตัวสแกน / ผู้ใช้เลือกเอง): กรอบบัตร + ถ่ายอัตโนมัติเมื่อเห็นรูปหน้าบนบัตรติดกัน 2 ภาพ
 *      (ML Kit ตรวจใบหน้าบนเครื่อง) — ไม่มีตัวตรวจ = กดถ่ายเอง · ปุ่มถ่ายเองกดได้เสมอ · ไฟฉาย
 * ห้ามเลือกรูปจากคลัง (กันใช้รูปบัตรของคนอื่น) · ไฟล์ภาพที่ไม่ได้ใช้ถูกลบทันที
 * ออกจากหน้า/พับแอป = ปิดกล้องและหยุดลูปถ่าย · กันแคปหน้าจอ
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, BackHandler, Pressable, StatusBar, StyleSheet, View, useWindowDimensions } from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useFocusEffect, useIsFocused } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import Svg, { Path } from 'react-native-svg';
import { Text } from '@/components/ui/Text';
import { Icon, resultHaptic, tapHaptic } from '@/components/ui';
import { CameraChip, CameraPill, CameraTopBar } from '@/components/ekyc/EkycKit';
import { CameraPermissionPanel, useAppActive, useTimers } from '@/components/ekyc/cameraKit';
import { useMountedRef } from '@/components/taladsod/hooks';
import { useSensitiveScreen } from '@/hooks/useSensitiveScreen';
import { useEkycStore } from '@/stores/ekycStore';
import {
  detectFacesInImage,
  isDocumentScannerAvailable,
  isFaceDetectionAvailable,
  scanCardWithDocumentScanner,
} from '@/services/ekyc/nativeVision';
import { looksLikeCardPortrait, pickPictureSize, sampleFromMlkit } from '@/services/ekyc/liveness';
import { dropTempFile } from '@/services/ekyc/tempFiles';
import { DARK_THEME, radii, spacing, typography, withAlpha, glowStyle } from '@/theme';

const CAM = DARK_THEME.colors;
const CAM_GRAD = DARK_THEME.gradients;
/** สัดส่วนบัตรประชาชน (ISO ID-1: 85.6 × 54 มม.) */
const CARD_RATIO = 85.6 / 54;

/** path สี่เหลี่ยมมุมโค้ง (สำหรับเจาะรูกลางม่าน) */
const roundedRectPath = (x: number, y: number, w: number, h: number, r: number): string =>
  `M${x + r},${y} H${x + w - r} A${r},${r} 0 0 1 ${x + w},${y + r} V${y + h - r} A${r},${r} 0 0 1 ${x + w - r},${y + h} ` +
  `H${x + r} A${r},${r} 0 0 1 ${x},${y + h - r} V${y + r} A${r},${r} 0 0 1 ${x + r},${y} Z`;

type Mode = 'scanner' | 'camera';

export default function EkycCaptureScreen() {
  useSensitiveScreen('ekyc-capture');
  const insets = useSafeAreaInsets();
  const { width: screenW } = useWindowDimensions();
  const mountedRef = useMountedRef();
  const { sleep } = useTimers();
  const appActive = useAppActive();
  const focused = useIsFocused();
  const session = useEkycStore((s) => s.session);
  const [permission, requestPermission, getPermission] = useCameraPermissions();

  const scannerAvailable = isDocumentScannerAvailable();
  const detectorAvailable = isFaceDetectionAvailable();
  const [mode, setMode] = useState<Mode>(scannerAvailable ? 'scanner' : 'camera');
  const [notice, setNotice] = useState<string | null>(null);
  const [cameraReady, setCameraReady] = useState(false);
  const [torch, setTorch] = useState(false);
  const [found, setFound] = useState(false);
  const [busy, setBusy] = useState(false);
  const [pictureSize, setPictureSize] = useState<string | undefined>(undefined);
  const [stage, setStage] = useState({ w: 0, h: 0 });

  const cameraRef = useRef<CameraView>(null);
  const loopTokenRef = useRef(0);
  const busyRef = useRef(false);
  const doneRef = useRef(false);

  // ไม่มีรอบ (เปิดหน้านี้ตรงๆ / แอปถูกปิดกลางทาง) → กลับไปเริ่มที่หน้าแนะนำ
  useEffect(() => {
    if (!session && focused) router.replace('/ekyc' as never);
  }, [session, focused]);

  // กลับมาหน้านี้ (ถ่ายใหม่จากหน้าตรวจข้อมูล) → เริ่มนับใหม่
  useFocusEffect(
    useCallback(() => {
      doneRef.current = false;
      setFound(false);
      return () => {
        loopTokenRef.current += 1;
        setTorch(false);
      };
    }, [])
  );

  const leave = useCallback(() => {
    loopTokenRef.current += 1;
    if (router.canGoBack()) router.back();
    else router.replace('/ekyc' as never);
  }, []);

  // ปุ่มย้อนกลับของเครื่องระหว่างกำลังถ่าย/สแกน → ไม่ทำอะไร
  useEffect(() => {
    const sub = BackHandler.addEventListener('hardwareBackPress', () => busyRef.current);
    return () => sub.remove();
  }, []);

  const goReview = useCallback((uri: string) => {
    if (doneRef.current) {
      dropTempFile(uri);
      return;
    }
    doneRef.current = true;
    loopTokenRef.current += 1;
    useEkycStore.getState().setCard(uri);
    router.push('/ekyc/review' as never);
  }, []);

  // ---------- กล้องในแอป ----------
  const capture = useCallback(async (quality: number) => {
    const cam = cameraRef.current;
    if (!cam) return null;
    try {
      const photo = await cam.takePictureAsync({ quality, shutterSound: false });
      return photo?.uri ? { uri: photo.uri, width: photo.width ?? 0, height: photo.height ?? 0 } : null;
    } catch {
      return null;
    }
  }, []);

  const onCameraReady = useCallback(async () => {
    setCameraReady(true);
    try {
      const sizes = await cameraRef.current?.getAvailablePictureSizesAsync();
      const best = pickPictureSize(sizes, 1920, 1280);
      if (best && mountedRef.current) setPictureSize((prev) => prev ?? best);
    } catch {
      // ใช้ขนาดเริ่มต้นของกล้อง
    }
  }, [mountedRef]);

  // ลูปถ่ายอัตโนมัติ: เห็นรูปหน้าบนบัตรติดกัน 2 ภาพ → ใช้ภาพล่าสุด
  const canLoop =
    mode === 'camera' && detectorAvailable && focused && appActive && cameraReady && !!permission?.granted && !!session;
  useEffect(() => {
    if (!canLoop) return undefined;
    const token = ++loopTokenRef.current;
    const alive = () => token === loopTokenRef.current && mountedRef.current && !doneRef.current;
    let pending: string | null = null;

    (async () => {
      let streak = 0;
      await sleep(700);
      while (alive()) {
        if (busyRef.current) {
          await sleep(300);
          continue;
        }
        const shot = await capture(0.85);
        if (!shot) {
          await sleep(600);
          continue;
        }
        if (!alive()) {
          dropTempFile(shot.uri);
          break;
        }
        const faces = await detectFacesInImage(shot.uri);
        if (!alive()) {
          dropTempFile(shot.uri);
          break;
        }
        const ok = faces !== null && looksLikeCardPortrait(sampleFromMlkit(faces, shot.width, shot.height));
        if (ok) {
          streak += 1;
          setFound(true);
          if (streak >= 2) {
            dropTempFile(pending);
            pending = null;
            resultHaptic('success');
            goReview(shot.uri);
            return;
          }
          dropTempFile(pending);
          pending = shot.uri;
        } else {
          streak = 0;
          dropTempFile(pending);
          pending = null;
          dropTempFile(shot.uri);
          setFound(false);
        }
        await sleep(ok ? 250 : 450);
      }
      dropTempFile(pending);
    })();

    return () => {
      loopTokenRef.current += 1;
    };
  }, [canLoop, capture, goReview, mountedRef, sleep]);

  const shootManual = async () => {
    if (busyRef.current || doneRef.current || !cameraReady) return;
    busyRef.current = true;
    setBusy(true);
    loopTokenRef.current += 1;
    let shot = await capture(0.92);
    if (!shot && mountedRef.current) {
      await sleep(400);
      shot = await capture(0.92);
    }
    busyRef.current = false;
    if (!mountedRef.current) {
      dropTempFile(shot?.uri);
      return;
    }
    setBusy(false);
    if (!shot) {
      resultHaptic('error');
      Alert.alert('ถ่ายรูปไม่สำเร็จ', 'ลองกดถ่ายอีกครั้งนะ');
      return;
    }
    resultHaptic('success');
    goReview(shot.uri);
  };

  // ---------- ตัวสแกนเอกสารของ Google ----------
  const scan = async () => {
    if (busyRef.current || doneRef.current) return;
    busyRef.current = true;
    setBusy(true);
    const result = await scanCardWithDocumentScanner();
    busyRef.current = false;
    if (!mountedRef.current) {
      if (result.kind === 'ok') dropTempFile(result.uri);
      return;
    }
    setBusy(false);
    if (result.kind === 'ok') {
      resultHaptic('success');
      goReview(result.uri);
      return;
    }
    if (result.kind === 'unavailable') {
      setMode('camera');
      setNotice('เครื่องนี้ใช้ตัวสแกนบัตรอัตโนมัติไม่ได้ เปลี่ยนเป็นกล้องในแอปให้แล้ว');
    }
  };

  const onShutter = () => {
    tapHaptic();
    if (mode === 'scanner') scan();
    else shootManual();
  };

  const switchMode = () => {
    if (busyRef.current) return;
    setNotice(null);
    setFound(false);
    setMode((m) => (m === 'scanner' ? 'camera' : 'scanner'));
  };

  // ---------- ส่วนแสดงผล ----------
  const frameW = Math.min(stage.w - spacing.xxl * 2, 440);
  const frameH = frameW / CARD_RATIO;
  const frameX = (stage.w - frameW) / 2;
  const frameY = Math.max(spacing.xl, stage.h * 0.4 - frameH / 2);
  const needPermission = mode === 'camera' && !permission?.granted;
  const showCamera = mode === 'camera' && !!permission?.granted && focused;

  const status =
    mode === 'scanner'
      ? busy
        ? 'กำลังเปิดตัวสแกนบัตร…'
        : 'กดปุ่มทอง ระบบจะจับขอบบัตรและถ่ายให้เอง'
      : !detectorAvailable
        ? 'วางบัตรให้อยู่ในกรอบ แล้วกดปุ่มถ่าย'
        : found
          ? 'พบบัตรแล้ว ถือนิ่งๆ กำลังถ่ายให้…'
          : 'วางบัตรด้านหน้าให้อยู่ในกรอบ';
  const statusSpinning = (mode === 'camera' && detectorAvailable && cameraReady) || busy;

  const corner = (pos: 'tl' | 'tr' | 'bl' | 'br') => {
    const size = 30;
    const t = 4;
    const style = {
      position: 'absolute' as const,
      width: size,
      height: size,
      borderColor: CAM.gold,
      left: pos === 'tl' || pos === 'bl' ? frameX - 8 : undefined,
      right: pos === 'tr' || pos === 'br' ? stage.w - frameX - frameW - 8 : undefined,
      top: pos === 'tl' || pos === 'tr' ? frameY - 8 : undefined,
      bottom: pos === 'bl' || pos === 'br' ? stage.h - frameY - frameH - 8 : undefined,
      borderTopWidth: pos === 'tl' || pos === 'tr' ? t : 0,
      borderBottomWidth: pos === 'bl' || pos === 'br' ? t : 0,
      borderLeftWidth: pos === 'tl' || pos === 'bl' ? t : 0,
      borderRightWidth: pos === 'tr' || pos === 'br' ? t : 0,
      borderTopLeftRadius: pos === 'tl' ? 12 : 0,
      borderTopRightRadius: pos === 'tr' ? 12 : 0,
      borderBottomLeftRadius: pos === 'bl' ? 12 : 0,
      borderBottomRightRadius: pos === 'br' ? 12 : 0,
    };
    return <View key={pos} pointerEvents="none" style={style} />;
  };

  const dot = (x: number, y: number, key: string) => (
    <View
      key={key}
      pointerEvents="none"
      style={[styles.dot, { left: x - 8, top: y - 8, backgroundColor: CAM.success, borderColor: CAM.textStrong }]}
    />
  );

  return (
    <View style={[styles.root, { backgroundColor: CAM.background }]}>
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      <CameraTopBar
        title="ถ่ายบัตรประชาชน"
        subtitle="ขั้นที่ 2 จาก 4 · ด้านหน้าบัตร"
        onBack={leave}
        right={<CameraPill icon="cpu" label="AI ทำงานในเครื่อง" />}
      />

      <View style={styles.stage} onLayout={(e) => setStage({ w: e.nativeEvent.layout.width, h: e.nativeEvent.layout.height })}>
        {showCamera && (
          <CameraView
            ref={cameraRef}
            style={StyleSheet.absoluteFill}
            facing="back"
            active={focused && appActive}
            enableTorch={torch}
            animateShutter={false}
            pictureSize={pictureSize}
            onCameraReady={onCameraReady}
            onMountError={() => {
              setCameraReady(false);
              setNotice('เปิดกล้องไม่ได้ตอนนี้ ลองออกแล้วเข้าหน้านี้ใหม่อีกครั้ง');
            }}
          />
        )}

        {stage.w > 0 && !needPermission && (
          <>
            {/* ม่านมืดรอบกรอบบัตร */}
            <Svg width={stage.w} height={stage.h} style={StyleSheet.absoluteFill} pointerEvents="none">
              <Path
                d={`M0,0 H${stage.w} V${stage.h} H0 Z ${roundedRectPath(frameX, frameY, frameW, frameH, 18)}`}
                fill={withAlpha(CAM.background, showCamera ? 0.62 : 0.92)}
                fillRule="evenodd"
              />
            </Svg>

            {/* ภาพบัตรตัวอย่าง (โหมดตัวสแกน — ยังไม่เปิดกล้อง) */}
            {!showCamera && (
              <View
                pointerEvents="none"
                style={[
                  styles.sampleCard,
                  { left: frameX, top: frameY, width: frameW, height: frameH, backgroundColor: withAlpha(CAM.textStrong, 0.06), borderColor: CAM.border },
                ]}
              >
                <View style={[styles.sampleLine, { width: '52%', backgroundColor: withAlpha(CAM.textStrong, 0.22) }]} />
                <View style={[styles.sampleLine, { width: '38%', backgroundColor: withAlpha(CAM.textStrong, 0.14) }]} />
                <View style={[styles.sampleLine, styles.sampleGap, { width: '46%', backgroundColor: withAlpha(CAM.textStrong, 0.3) }]} />
                <View style={[styles.sampleLine, { width: '40%', backgroundColor: withAlpha(CAM.textStrong, 0.14) }]} />
                <View style={[styles.sampleLine, { width: '34%', backgroundColor: withAlpha(CAM.textStrong, 0.14) }]} />
                <View style={[styles.samplePhoto, { backgroundColor: withAlpha(CAM.gold, 0.25), borderColor: withAlpha(CAM.gold, 0.4) }]}>
                  <Icon name="user" size={frameH * 0.3} color={withAlpha(CAM.goldLight, 0.7)} weight="fill" />
                </View>
              </View>
            )}

            {(['tl', 'tr', 'bl', 'br'] as const).map(corner)}
            {found &&
              [
                dot(frameX, frameY, 'a'),
                dot(frameX + frameW, frameY, 'b'),
                dot(frameX, frameY + frameH, 'c'),
                dot(frameX + frameW, frameY + frameH, 'd'),
              ]}

            <View style={[styles.statusWrap, { top: frameY + frameH + spacing.xxl }]} pointerEvents="none">
              <View style={[styles.status, { backgroundColor: withAlpha(CAM.card, 0.9), borderColor: withAlpha(CAM.gold, 0.5) }]}>
                {statusSpinning ? (
                  <ActivityIndicator size="small" color={CAM.gold} />
                ) : (
                  <Icon name={mode === 'scanner' ? 'scan' : 'identification-card'} size={18} color={CAM.gold} />
                )}
                <Text numberOfLines={1} style={[typography.bodyStrong, styles.statusText, { color: CAM.textStrong }]}>
                  {status}
                </Text>
              </View>
              {/* ชิปผูกกับสิ่งที่ตรวจได้จริงเท่านั้น: เห็นรูปหน้าบนบัตร (ML Kit) — ความชัด/แสงสะท้อน server เป็นคนตรวจ */}
              {mode === 'camera' && detectorAvailable && (
                <View style={styles.chips}>
                  <CameraChip label="พบบัตรในกล้อง" on={found} />
                </View>
              )}
            </View>
          </>
        )}

        {needPermission && (
          <View style={styles.permissionWrap}>
            <CameraPermissionPanel
              permission={permission}
              request={requestPermission}
              refresh={getPermission}
              reason="ใช้กล้องหลังถ่ายบัตรประชาชนด้านหน้า รูปส่งไปตรวจบนเซิร์ฟเวอร์ไทยพร้อมเท่านั้น"
            />
          </View>
        )}
      </View>

      <View style={[styles.bottom, { paddingBottom: insets.bottom + spacing.lg }]}>
        <View style={[styles.info, { backgroundColor: withAlpha(CAM.card, 0.9), borderColor: CAM.border }]}>
          <Icon name={notice ? 'warning-circle' : 'info'} size={18} color={CAM.gold} />
          <Text style={[typography.bodySm, styles.flex, { color: CAM.text }]}>
            {notice || 'วางบัตรบนพื้นเรียบสีเข้ม ไม่ใช้นิ้วบังตัวเลข ระบบรับเฉพาะบัตรจริง ไม่รับรูปถ่ายหน้าจอ'}
          </Text>
        </View>

        <View style={styles.controls}>
          <View style={styles.side}>
            {mode === 'camera' && permission?.granted ? (
              <Pressable
                onPress={() => setTorch((v) => !v)}
                accessibilityRole="button"
                accessibilityLabel={torch ? 'ปิดไฟฉาย' : 'เปิดไฟฉาย'}
                style={[styles.round, { backgroundColor: torch ? withAlpha(CAM.gold, 0.25) : withAlpha(CAM.card, 0.9), borderColor: CAM.border }]}
              >
                <Icon name={torch ? 'flashlight' : 'sun'} size={22} color={torch ? CAM.gold : CAM.textStrong} />
              </Pressable>
            ) : null}
          </View>

          <Pressable
            onPress={onShutter}
            disabled={busy || needPermission || (mode === 'camera' && !cameraReady)}
            accessibilityRole="button"
            accessibilityLabel={mode === 'scanner' ? 'สแกนบัตรประชาชน' : 'ถ่ายรูปบัตรประชาชน'}
            style={({ pressed }) => [
              styles.shutterRing,
              { borderColor: CAM.goldLight, opacity: busy || needPermission ? 0.5 : pressed ? 0.85 : 1 },
            ]}
          >
            <LinearGradient colors={CAM_GRAD.primary} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={[styles.shutter, glowStyle(CAM.gold, 0.7)]}>
              {busy ? <ActivityIndicator color={CAM.textOnGold} /> : null}
            </LinearGradient>
          </Pressable>

          <View style={styles.side}>
            {scannerAvailable ? (
              <Pressable
                onPress={switchMode}
                accessibilityRole="button"
                accessibilityLabel={mode === 'scanner' ? 'ใช้กล้องในแอปแทน' : 'ใช้ตัวสแกนบัตรอัตโนมัติ'}
                style={[styles.round, { backgroundColor: withAlpha(CAM.card, 0.9), borderColor: CAM.border }]}
              >
                <Icon name={mode === 'scanner' ? 'camera' : 'scan'} size={22} color={CAM.textStrong} />
              </Pressable>
            ) : null}
          </View>
        </View>
      </View>
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
  stage: {
    flex: 1,
    overflow: 'hidden',
  },
  sampleCard: {
    position: 'absolute',
    borderRadius: 18,
    borderWidth: 1,
    padding: spacing.lg,
    gap: 7,
  },
  sampleLine: {
    height: 8,
    borderRadius: 4,
  },
  sampleGap: {
    marginTop: spacing.sm,
  },
  samplePhoto: {
    position: 'absolute',
    right: spacing.lg,
    bottom: spacing.lg,
    width: '24%',
    height: '58%',
    borderRadius: 10,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'flex-end',
    overflow: 'hidden',
  },
  dot: {
    position: 'absolute',
    width: 16,
    height: 16,
    borderRadius: 8,
    borderWidth: 2,
  },
  statusWrap: {
    position: 'absolute',
    left: spacing.screen,
    right: spacing.screen,
    alignItems: 'center',
    gap: spacing.md,
  },
  status: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    maxWidth: '100%',
  },
  statusText: {
    flexShrink: 1,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'center',
    gap: spacing.sm,
  },
  permissionWrap: {
    flex: 1,
    justifyContent: 'center',
  },
  bottom: {
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.md,
    gap: spacing.lg,
  },
  info: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    borderRadius: radii.lg,
    borderWidth: 1,
    padding: spacing.md,
  },
  controls: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.xl,
  },
  side: {
    width: 56,
    alignItems: 'center',
  },
  round: {
    width: 52,
    height: 52,
    borderRadius: 26,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  shutterRing: {
    width: 84,
    height: 84,
    borderRadius: 42,
    borderWidth: 4,
    alignItems: 'center',
    justifyContent: 'center',
  },
  shutter: {
    width: 66,
    height: 66,
    borderRadius: 33,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
