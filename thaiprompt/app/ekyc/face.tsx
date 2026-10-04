/**
 * ยืนยันใบหน้า (ขั้นที่ 3 จาก 4) — ตามแบบ FaceLiveness.png
 *
 * - คำสั่งสุ่มจาก server 3 ท่า (กระพริบตา/หันซ้าย/หันขวา/ยิ้ม/พยักหน้า) นำหน้าด้วยเฟรม "มองตรง" 1 เฟรม
 * - กล้องหน้า (ภาพกระจก) ถ่ายภาพนิ่งถี่ๆ → ML Kit ตรวจใบหน้าบนเครื่อง (ลืมตา/ยิ้ม/มุมหัว/จุดตา-จมูก)
 *   → ตัวตัดสินท่าทาง (services/ekyc/liveness.ts) → ท่าถูก = เก็บเฟรมนั้นไว้ส่ง server ทันที (เฟรมที่ตรวจ = เฟรมที่ส่ง)
 * - ทำท่าไม่ผ่านใน 12 วินาที / เครื่องไม่มีตัวตรวจใบหน้า → โหมดนับถอยหลัง: บอกท่า แล้วถ่ายให้หลังนับ 3 (server ตรวจเอง)
 * - ครบทุกท่า → เก็บเฟรมใน ekycStore แล้วไปหน้าส่งตรวจ (แทนที่หน้านี้ กดย้อนกลับมาซ้ำไม่ได้)
 * - พับแอป/ออกจากหน้า = ปิดกล้อง หยุดลูป ล้างตัวจับเวลา · ไฟล์ภาพที่ไม่ใช้ถูกลบทันที · กันแคปหน้าจอ
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, BackHandler, StatusBar, StyleSheet, View, useWindowDimensions } from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { router, useIsFocused, useNavigation } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import Svg, { Ellipse, Path } from 'react-native-svg';
import { Text } from '@/components/ui/Text';
import { Icon, resultHaptic, selectionHaptic } from '@/components/ui';
import { CameraChip, CameraPill, CameraTopBar } from '@/components/ekyc/EkycKit';
import { CameraPermissionPanel, useAppActive, useTimers } from '@/components/ekyc/cameraKit';
import { CHALLENGE_UI, LIVENESS_HINT_TEXT } from '@/components/ekyc/livenessCopy';
import { useMountedRef } from '@/components/taladsod/hooks';
import { isScreenCaptureProtectionAvailable, useSensitiveScreen } from '@/hooks/useSensitiveScreen';
import { useEkycStore } from '@/stores/ekycStore';
import { framesMatchChallenges, type EkycFaceLabel, type EkycFrame } from '@/services/api/ekycApi';
import {
  advance,
  advanceGuided,
  createLivenessState,
  currentLabel,
  framingHint,
  pickPictureSize,
  progressOf,
  sampleFromMlkit,
  type LivenessHint,
  type LivenessState,
} from '@/services/ekyc/liveness';
import { detectFacesInImage, prepareFaceDetector } from '@/services/ekyc/nativeVision';
import { dropTempFile, dropTempFiles } from '@/services/ekyc/tempFiles';
import { DARK_THEME, LIGHT_THEME, radii, spacing, typography, withAlpha } from '@/theme';

const CAM = DARK_THEME.colors;
const PILL = LIGHT_THEME.colors;
/** ท่าไหนทำไม่ผ่านนานเกินนี้ → นับถอยหลังแล้วถ่ายให้ */
const STEP_TIMEOUT_MS = 12_000;
/** หยุดพักหลังเก็บท่าสำเร็จ (ให้ผู้ใช้เห็นติ๊กถูกก่อนท่าถัดไป) */
const STEP_PAUSE_MS = 650;

type DetectorMode = 'checking' | 'auto' | 'guided';

/** เส้นรอบวงวงรีโดยประมาณ (Ramanujan) — ใช้ทำเส้นความคืบหน้า */
const ellipseCircumference = (rx: number, ry: number): number =>
  Math.PI * (3 * (rx + ry) - Math.sqrt((3 * rx + ry) * (rx + 3 * ry)));

export default function EkycFaceScreen() {
  useSensitiveScreen('ekyc-face');
  const insets = useSafeAreaInsets();
  const { width: screenW } = useWindowDimensions();
  const navigation = useNavigation();
  const mountedRef = useMountedRef();
  const { sleep, clearAll } = useTimers();
  const appActive = useAppActive();
  const focused = useIsFocused();
  const session = useEkycStore((s) => s.session);
  const [permission, requestPermission, getPermission] = useCameraPermissions();

  const [detector, setDetector] = useState<DetectorMode>('checking');
  const [live, setLive] = useState<LivenessState>(() => createLivenessState(session?.challenges ?? []));
  const [hint, setHint] = useState<LivenessHint>('GOOD');
  const [framed, setFramed] = useState(false);
  const [eyesSeen, setEyesSeen] = useState(false);
  const [countdown, setCountdown] = useState<number | null>(null);
  const [cameraReady, setCameraReady] = useState(false);
  const [pictureSize, setPictureSize] = useState<string | undefined>(undefined);
  const [stage, setStage] = useState({ w: 0, h: 0 });

  const cameraRef = useRef<CameraView>(null);
  const liveRef = useRef(live);
  const framesRef = useRef<EkycFrame[]>([]);
  const loopTokenRef = useRef(0);
  const stepStartedRef = useRef(Date.now());
  const finishedRef = useRef(false);
  const leavingRef = useRef(false);

  // ไม่มีรอบ → เริ่มใหม่ · รอบไม่มีคำสั่ง (ไม่ควรเกิด) → กลับหน้าแนะนำ
  useEffect(() => {
    if (!focused || finishedRef.current) return;
    if (!session || session.challenges.length === 0) router.replace('/ekyc' as never);
  }, [focused, session]);

  // เตรียมตัวตรวจใบหน้า (ไม่มี/ล้มเหลว = โหมดนับถอยหลัง)
  useEffect(() => {
    let cancelled = false;
    prepareFaceDetector().then((ok) => {
      if (!cancelled && mountedRef.current) setDetector(ok ? 'auto' : 'guided');
    });
    return () => {
      cancelled = true;
    };
  }, [mountedRef]);

  // ออกจากหน้า: ลบเฟรมที่ยังไม่ได้ส่ง (ยกเว้นส่งต่อให้หน้าส่งตรวจแล้ว)
  useEffect(
    () => () => {
      loopTokenRef.current += 1;
      if (!finishedRef.current) dropTempFiles(framesRef.current.map((f) => f.uri));
    },
    []
  );

  const resetProgress = useCallback(() => {
    dropTempFiles(framesRef.current.map((f) => f.uri));
    framesRef.current = [];
    const fresh = createLivenessState(useEkycStore.getState().session?.challenges ?? []);
    liveRef.current = fresh;
    setLive(fresh);
    setHint('GOOD');
    setCountdown(null);
    stepStartedRef.current = Date.now();
  }, []);

  const leave = useCallback(() => {
    if (leavingRef.current) return;
    const go = () => {
      leavingRef.current = true;
      loopTokenRef.current += 1;
      resetProgress();
      if (router.canGoBack()) router.back();
      else router.replace('/ekyc' as never);
    };
    if (framesRef.current.length === 0) {
      go();
      return;
    }
    Alert.alert('ออกจากการถ่ายใบหน้า?', 'ท่าที่ทำไปแล้วจะต้องเริ่มใหม่', [
      { text: 'ถ่ายต่อ', style: 'cancel' },
      { text: 'ออก', style: 'destructive', onPress: go },
    ]);
  }, [resetProgress]);

  // ปุ่มย้อนกลับของเครื่อง → ถามก่อน
  useEffect(() => {
    const sub = BackHandler.addEventListener('hardwareBackPress', () => {
      if (finishedRef.current) return true;
      leave();
      return true;
    });
    return () => sub.remove();
  }, [leave]);

  // iOS ปัดย้อนกลับ/ถูกนำออกจาก stack โดยไม่ผ่านปุ่ม → ลบเฟรมทิ้ง (ทำใน unmount แล้ว)
  useEffect(() => navigation.addListener('blur', () => setCountdown(null)), [navigation]);

  const capture = useCallback(async () => {
    const cam = cameraRef.current;
    if (!cam) return null;
    try {
      const photo = await cam.takePictureAsync({ quality: 0.75, shutterSound: false });
      return photo?.uri ? { uri: photo.uri, width: photo.width ?? 0, height: photo.height ?? 0 } : null;
    } catch {
      return null;
    }
  }, []);

  const onCameraReady = useCallback(async () => {
    setCameraReady(true);
    try {
      const sizes = await cameraRef.current?.getAvailablePictureSizesAsync();
      const best = pickPictureSize(sizes, 1280, 960);
      if (best && mountedRef.current) setPictureSize((prev) => prev ?? best);
    } catch {
      // ใช้ขนาดเริ่มต้นของกล้อง
    }
  }, [mountedRef]);

  /** ครบทุกท่า → ส่งเฟรมให้หน้าส่งตรวจ */
  const finish = useCallback(() => {
    if (finishedRef.current) return;
    const frames = framesRef.current;
    const challenges = useEkycStore.getState().session?.challenges ?? [];
    if (!framesMatchChallenges(frames, challenges)) {
      // ลำดับเพี้ยน (ไม่ควรเกิด) → เริ่มท่าใหม่ทั้งหมด
      resetProgress();
      return;
    }
    finishedRef.current = true;
    loopTokenRef.current += 1;
    resultHaptic('success');
    useEkycStore.getState().setFrames(frames);
    router.replace('/ekyc/processing' as never);
  }, [resetProgress]);

  /** เก็บเฟรมของท่าปัจจุบัน */
  const keep = useCallback((uri: string, label: EkycFaceLabel, next: LivenessState) => {
    framesRef.current = [...framesRef.current, { uri, label }];
    liveRef.current = next;
    setLive(next);
    setHint('GOOD');
    stepStartedRef.current = Date.now();
    selectionHaptic();
  }, []);

  /** นับถอยหลัง 3-2-1 แล้วถ่ายท่าปัจจุบัน (server ตรวจเอง) */
  const guidedShot = useCallback(
    async (alive: () => boolean): Promise<boolean> => {
      for (let n = 3; n >= 1; n -= 1) {
        if (!alive()) return false;
        setCountdown(n);
        await sleep(800);
      }
      if (!alive()) return false;
      setCountdown(null);
      const shot = await capture();
      if (!shot) return true;
      if (!alive()) {
        dropTempFile(shot.uri);
        return false;
      }
      const step = advanceGuided(liveRef.current);
      if (step.accept && step.label) keep(shot.uri, step.label, step.state);
      else dropTempFile(shot.uri);
      return true;
    },
    [capture, keep, sleep]
  );

  const canRun =
    detector !== 'checking' && focused && appActive && cameraReady && !!permission?.granted && !!session && !finishedRef.current;

  useEffect(() => {
    if (!canRun) return undefined;
    const token = ++loopTokenRef.current;
    const alive = () => token === loopTokenRef.current && mountedRef.current && !finishedRef.current && !leavingRef.current;
    stepStartedRef.current = Date.now();

    (async () => {
      await sleep(500);
      let mode: DetectorMode = detector;
      while (alive()) {
        const label = currentLabel(liveRef.current);
        if (!label) {
          finish();
          return;
        }

        // โหมดนับถอยหลัง / ทำท่าไม่ผ่านนานเกิน
        if (mode === 'guided' || Date.now() - stepStartedRef.current > STEP_TIMEOUT_MS) {
          if (mode === 'guided') await sleep(1200);
          const ok = await guidedShot(alive);
          if (!ok) return;
          if (liveRef.current.done) {
            finish();
            return;
          }
          await sleep(STEP_PAUSE_MS);
          continue;
        }

        const shot = await capture();
        if (!shot) {
          await sleep(400);
          continue;
        }
        if (!alive()) {
          dropTempFile(shot.uri);
          return;
        }
        const faces = await detectFacesInImage(shot.uri);
        if (!alive()) {
          dropTempFile(shot.uri);
          return;
        }
        if (faces === null) {
          // ตัวตรวจใบหน้าใช้ไม่ได้กลางทาง → นับถอยหลังแทน
          dropTempFile(shot.uri);
          mode = 'guided';
          setDetector('guided');
          continue;
        }
        const sample = sampleFromMlkit(faces, shot.width, shot.height);
        setFramed(sample.faceCount === 1 && framingHint(sample) === null);
        if (sample.leftEyeOpen !== null) setEyesSeen(true);
        const result = advance(liveRef.current, sample);
        if (result.accept && result.label) {
          keep(shot.uri, result.label, result.state);
          if (result.state.done) {
            finish();
            return;
          }
          await sleep(STEP_PAUSE_MS);
        } else {
          dropTempFile(shot.uri);
          setHint(result.hint);
          await sleep(120);
        }
      }
    })();

    return () => {
      loopTokenRef.current += 1;
      clearAll();
      setCountdown(null);
    };
  }, [canRun, detector, capture, clearAll, finish, guidedShot, keep, mountedRef, sleep]);

  // ---------- ส่วนแสดงผล ----------
  const label = currentLabel(live);
  const ui = label ? CHALLENGE_UI[label] : CHALLENGE_UI.neutral;
  const progress = progressOf(live);
  const hintText = hint !== 'GOOD' && hint !== 'DO_ACTION' ? LIVENESS_HINT_TEXT[hint] : null;
  const needPermission = !permission?.granted;
  const showCamera = !!permission?.granted && focused;

  const ovalW = Math.min(screenW * 0.72, 300);
  const ovalH = ovalW * 1.32;
  const cx = stage.w / 2;
  const cy = Math.max(ovalH / 2 + spacing.lg, stage.h * 0.42);
  const rx = ovalW / 2;
  const ry = ovalH / 2;
  const ringRx = rx + 9;
  const ringRy = ry + 9;
  const circ = ellipseCircumference(ringRx, ringRy);
  const ovalHole = `M${cx - rx},${cy} A${rx},${ry} 0 1 0 ${cx + rx},${cy} A${rx},${ry} 0 1 0 ${cx - rx},${cy} Z`;

  const allLabels: EkycFaceLabel[] = live.challenges;

  return (
    <View style={[styles.root, { backgroundColor: CAM.background }]}>
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      <CameraTopBar
        title="ยืนยันใบหน้า"
        subtitle="ขั้นที่ 3 จาก 4"
        onBack={leave}
        right={isScreenCaptureProtectionAvailable() ? <CameraPill icon="shield-check" label="ป้องกันการแคปหน้าจอ" /> : undefined}
      />

      <View style={styles.stage} onLayout={(e) => setStage({ w: e.nativeEvent.layout.width, h: e.nativeEvent.layout.height })}>
        {showCamera && (
          <CameraView
            ref={cameraRef}
            style={StyleSheet.absoluteFill}
            facing="front"
            mirror
            active={focused && appActive}
            animateShutter={false}
            pictureSize={pictureSize}
            onCameraReady={onCameraReady}
            onMountError={() => {
              setCameraReady(false);
              // กล้องหน้าเปิดไม่ได้ (เครื่องไม่มี/แอปอื่นใช้อยู่) → ให้ย้อนกลับได้ทันที ไม่ค้างอยู่หน้านี้
              Alert.alert('เปิดกล้องไม่ได้', 'กล้องหน้าของเครื่องใช้งานไม่ได้ตอนนี้ ปิดแอปที่ใช้กล้องอยู่ แล้วลองใหม่อีกครั้งนะ', [
                { text: 'ย้อนกลับ', onPress: leave },
              ]);
            }}
          />
        )}

        {stage.w > 0 && !needPermission && (
          <>
            <Svg width={stage.w} height={stage.h} style={StyleSheet.absoluteFill} pointerEvents="none">
              {/* ม่านมืดรอบวงรี */}
              <Path d={`M0,0 H${stage.w} V${stage.h} H0 Z ${ovalHole}`} fill={withAlpha(CAM.background, 0.86)} fillRule="evenodd" />
              {/* วงแหวนพื้น + เส้นความคืบหน้าสีทอง */}
              <Ellipse cx={cx} cy={cy} rx={ringRx} ry={ringRy} stroke={withAlpha(CAM.textStrong, 0.16)} strokeWidth={7} fill="none" />
              <Ellipse
                cx={cx}
                cy={cy}
                rx={ringRx}
                ry={ringRy}
                stroke={CAM.gold}
                strokeWidth={7}
                strokeLinecap="round"
                fill="none"
                strokeDasharray={`${circ} ${circ}`}
                strokeDashoffset={circ * (1 - progress)}
                transform={`rotate(-90 ${cx} ${cy})`}
              />
            </Svg>

            {countdown !== null && (
              <View pointerEvents="none" style={[styles.countdown, { left: cx - 48, top: cy - 48, backgroundColor: withAlpha(CAM.background, 0.55) }]}>
                <Text style={[styles.countdownText, { color: CAM.goldLight }]}>{countdown}</Text>
              </View>
            )}

            <View style={[styles.instructionWrap, { top: cy + ringRy + spacing.lg }]}>
              <View style={[styles.instruction, { backgroundColor: PILL.card }]}>
                <Icon name={ui.icon} size={22} color={PILL.textStrong} weight="bold" />
                <Text style={[typography.h2, styles.shrink, { color: PILL.textStrong }]} numberOfLines={2}>
                  {countdown !== null ? `${ui.instruction} ค้างไว้` : ui.instruction}
                </Text>
              </View>
              <Text style={[typography.bodySm, styles.centerText, { color: hintText && countdown === null ? CAM.goldLight : CAM.navy }]}>
                {countdown !== null || detector === 'guided'
                  ? 'ทำท่าค้างไว้ ระบบจะถ่ายให้เมื่อนับครบ'
                  : hintText || 'คำสั่งสุ่มทุกครั้ง เพื่อยืนยันว่าเป็นคนจริง ไม่ใช่รูปหรือวิดีโอ'}
              </Text>
            </View>
          </>
        )}

        {needPermission && (
          <View style={styles.permissionWrap}>
            <CameraPermissionPanel
              permission={permission}
              request={requestPermission}
              refresh={getPermission}
              reason="ใช้กล้องหน้าถ่ายใบหน้าตามคำสั่งสั้นๆ เพื่อยืนยันว่าเป็นคุณจริง รูปส่งไปตรวจบนเซิร์ฟเวอร์ไทยพร้อมเท่านั้น"
            />
          </View>
        )}
      </View>

      <View style={[styles.bottom, { paddingBottom: insets.bottom + spacing.lg }]}>
        <View
          style={[styles.steps, { backgroundColor: withAlpha(CAM.card, 0.92), borderColor: CAM.border }]}
          accessible
          accessibilityLabel={`ท่าที่ต้องทำ ${allLabels.map((l) => CHALLENGE_UI[l].short).join(' ')} ทำแล้ว ${Math.max(0, live.step - 1)} ท่า`}
        >
          {allLabels.map((l, index) => {
            const stepNo = index + 1;
            const done = live.step > stepNo || live.done;
            const active = !done && live.step === stepNo;
            return (
              <View key={l} style={styles.stepItem}>
                {done ? (
                  <View style={[styles.stepMark, { backgroundColor: CAM.success }]}>
                    <Icon name="check" size={14} color={CAM.textOnAccent} weight="bold" />
                  </View>
                ) : active ? (
                  <View style={[styles.stepMark, styles.stepActive, { borderColor: CAM.gold }]} />
                ) : (
                  <View style={[styles.stepMark, { backgroundColor: withAlpha(CAM.textStrong, 0.16) }]} />
                )}
                <Text style={[typography.bodyStrong, { color: done || active ? CAM.textStrong : CAM.textFaint }]}>
                  {CHALLENGE_UI[l].short}
                </Text>
              </View>
            );
          })}
        </View>

        <View style={styles.chips}>
          <CameraChip label="หน้าอยู่ในกรอบ" on={framed} />
          <CameraChip label="แสงพอ" on={framed} />
          <CameraChip label="ไม่สวมแว่นดำ" on={eyesSeen} />
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  shrink: {
    flexShrink: 1,
  },
  centerText: {
    textAlign: 'center',
  },
  stage: {
    flex: 1,
    overflow: 'hidden',
  },
  countdown: {
    position: 'absolute',
    width: 96,
    height: 96,
    borderRadius: 48,
    alignItems: 'center',
    justifyContent: 'center',
  },
  countdownText: {
    fontSize: 52,
    lineHeight: 64,
    fontWeight: '700',
  },
  instructionWrap: {
    position: 'absolute',
    left: spacing.screen,
    right: spacing.screen,
    alignItems: 'center',
    gap: spacing.sm,
  },
  instruction: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderRadius: radii.lg,
    paddingHorizontal: spacing.xl,
    paddingVertical: spacing.md,
    maxWidth: '100%',
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
  steps: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'center',
    borderRadius: radii.lg,
    borderWidth: 1,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.sm,
  },
  stepItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  stepMark: {
    width: 26,
    height: 26,
    borderRadius: 13,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepActive: {
    borderWidth: 3,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'center',
    gap: spacing.sm,
  },
});
