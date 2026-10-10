/**
 * ถ่ายบัตรประชาชน (ขั้นที่ 2 จาก 4) — ตามแบบ IdCapture.png
 *
 * สองโหมด
 *   1. กล้องในแอปแบบตรวจภาพ (ค่าเริ่มต้นเมื่อเครื่องตรวจใบหน้าได้): กรอบบัตร + ถ่ายอัตโนมัติเมื่อภาพพร้อมจริง
 *      ทุกภาพตรวจในเครื่อง (cardQuality.ts): บัตรเต็มกรอบ (หาขอบบัตรจริง / เดาจากรูปหน้าบนบัตร) → แสงไม่มืด/ไม่จ้า
 *      ไม่มีแสงสะท้อนบนรูปหน้า/ตัวหนังสือ → แถบเลขบัตร+ชื่อคมพอที่สเกล OCR ของ server → ถ่ายภาพนั้นเลย
 *      ไม่ผ่านข้อไหนบอกผู้ใช้ทีละข้อ · กดถ่ายเองได้เสมอ (ภาพยังไม่พร้อม = ถามก่อนใช้ เพราะสิทธิ์ส่งรูปบัตรมีจำกัด) · ไฟฉาย
 *      ไม่มีตัวตรวจใบหน้า = กดถ่ายเอง · ไม่มีตัวอ่านพิกเซล = ถ่ายเมื่อรูปหน้าบนบัตรเต็มกรอบติดกัน 2 ภาพ
 *   2. ตัวสแกนเอกสารของ Google (Android ที่มี Play services — เลือกจากปุ่มขวา / เครื่องที่ตรวจใบหน้าไม่ได้):
 *      กดปุ่มทอง → จับขอบบัตรอัตโนมัติ ถ่ายเองเมื่อนิ่ง ตัดภาพให้ตรง
 *      ใช้ไม่ได้ในเครื่องนี้ (ไม่มี Play services/โหลดโมดูลไม่สำเร็จ) → สลับเป็นกล้องในแอปเองพร้อมบอกผู้ใช้
 * ภาพถ่ายละเอียดสูง (4:3 ราว 5 MP) ให้บัตรกว้าง ≥ 1000 px เมื่อเต็มกรอบ — server ปรับบัตรเป็น 1000 px ก่อน OCR
 * ห้ามเลือกรูปจากคลัง (กันใช้รูปบัตรของคนอื่น) · ไฟล์ภาพที่ไม่ได้ใช้ถูกลบทันที
 * ออกจากหน้า/พับแอป = ปิดกล้องและหยุดลูปถ่าย · กันแคปหน้าจอ
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, BackHandler, Platform, Pressable, StatusBar, StyleSheet, View } from 'react-native';
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
import { pickPictureSize } from '@/services/ekyc/liveness';
import { analyzeCardPhoto, isCardAnalyzerAvailable } from '@/services/ekyc/cardAnalyzer';
import { isCardShotGood, pickCardPictureSize, type CardHint, type CardVerdict } from '@/services/ekyc/cardQuality';
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

/** คำแนะนำระหว่างเล็งกล้อง (ตามด่านแรกที่ยังไม่ผ่าน) */
const HINT_TEXT: Record<CardHint, string> = {
  NO_CARD: 'วางบัตรด้านหน้าให้อยู่ในกรอบ รูปหน้าอยู่ทางขวา',
  TILTED: 'จัดบัตรให้ตรงกับกรอบ ไม่เอียง',
  TOO_FAR: 'ขยับกล้องเข้าใกล้อีกนิด ให้บัตรเต็มกรอบ',
  TOO_CLOSE: 'ถอยกล้องออกเล็กน้อย ให้เห็นบัตรครบทั้งใบ',
  OFF_CENTER: 'เลื่อนบัตรให้อยู่กลางกรอบ',
  TOO_DARK: 'แสงน้อยไป ย้ายไปที่สว่างขึ้น หรือเปิดไฟฉาย',
  TOO_BRIGHT: 'แสงจ้าเกินไป หลบแดดหรือไฟที่ส่องตรงบัตร',
  GLARE_FACE: 'มีแสงสะท้อนบนรูปหน้า เอียงบัตรหรือขยับหนีแสงเล็กน้อย',
  GLARE: 'มีแสงสะท้อนบนตัวหนังสือ เปลี่ยนมุมบัตรเล็กน้อย',
  BLURRY: 'ถือนิ่งๆ กำลังโฟกัสให้ตัวหนังสือคมชัด…',
  ROTATE: 'ถือโทรศัพท์ตั้งตรงเป็นแนวตั้ง แล้วค่อยเล็งบัตร',
  GOOD: 'ภาพชัดแล้ว ถือนิ่งๆ กำลังถ่าย…',
};

/** เหตุผลเมื่อกดถ่ายเองแต่ภาพยังไม่พร้อม (ไม่มีในนี้ = ใช้ HINT_TEXT) */
const MANUAL_TEXT: Partial<Record<CardHint, string>> = {
  NO_CARD: 'ไม่พบรูปหน้าบนบัตรในภาพ',
  BLURRY: 'ตัวหนังสือบนบัตรยังไม่คมชัด',
};

/** ภาพเบลอติดกันกี่ภาพ (ทั้งที่บัตรเต็มกรอบแล้ว) ถึงเปลี่ยนคำแนะนำ */
const BLUR_STUCK_FRAMES = 4;

export default function EkycCaptureScreen() {
  useSensitiveScreen('ekyc-capture');
  const insets = useSafeAreaInsets();
  const mountedRef = useMountedRef();
  const { sleep } = useTimers();
  const appActive = useAppActive();
  const focused = useIsFocused();
  const session = useEkycStore((s) => s.session);
  const [permission, requestPermission, getPermission] = useCameraPermissions();

  const scannerAvailable = isDocumentScannerAvailable();
  const detectorAvailable = isFaceDetectionAvailable();
  const analyzerAvailable = detectorAvailable && isCardAnalyzerAvailable();
  // ตรวจภาพในเครื่องได้ = ใช้กล้องในแอป (เช็คเต็มกรอบ/แสง/ความคมก่อนถ่าย) · ตัวสแกนของ Google เลือกได้จากปุ่มขวา
  const [mode, setMode] = useState<Mode>(detectorAvailable || !scannerAvailable ? 'camera' : 'scanner');
  const [notice, setNotice] = useState<string | null>(null);
  const [cameraReady, setCameraReady] = useState(false);
  const [torch, setTorch] = useState(false);
  const [verdict, setVerdict] = useState<CardVerdict | null>(null);
  const [blurStuck, setBlurStuck] = useState(false);
  const [busy, setBusy] = useState(false);
  const [pictureSize, setPictureSize] = useState<string | undefined>(undefined);
  const [stage, setStage] = useState({ w: 0, h: 0 });
  /** เปลี่ยนค่า = เริ่มลูปถ่ายอัตโนมัติใหม่ (หลังกดถ่ายเองแล้วเลือกถ่ายใหม่) */
  const [loopKey, setLoopKey] = useState(0);

  const cameraRef = useRef<CameraView>(null);
  const loopTokenRef = useRef(0);
  const busyRef = useRef(false);
  const doneRef = useRef(false);
  /** กรอบบนจอ/พื้นที่กล้องล่าสุด (ลูปอ่านจาก ref — layout เปลี่ยนไม่ต้องเริ่มลูปใหม่) */
  const frameRef = useRef({ x: 0, y: 0, w: 0, h: 0 });
  const stageRef = useRef({ w: 0, h: 0 });
  /** ขนาดใบหน้า/ความกว้างบัตร ที่วัดได้จากภาพที่เจอขอบบัตร — ใช้เดาบัตรในภาพที่หาขอบไม่เจอ */
  const faceRatioRef = useRef<number | null>(null);
  /** โหมดผ่อนระยะ: เต็มกรอบแล้วเบลอติดกันหลายภาพ = กล้องโฟกัสใกล้ไม่ได้ → ให้ถอยออกได้ ขอแค่บัตรยังละเอียดพอ */
  const relaxRef = useRef(false);

  // ไม่มีรอบ (เปิดหน้านี้ตรงๆ / แอปถูกปิดกลางทาง) → กลับไปเริ่มที่หน้าแนะนำ
  useEffect(() => {
    if (!session && focused) router.replace('/ekyc' as never);
  }, [session, focused]);

  // กลับมาหน้านี้ (ถ่ายใหม่จากหน้าตรวจข้อมูล) → เริ่มนับใหม่
  useFocusEffect(
    useCallback(() => {
      doneRef.current = false;
      relaxRef.current = false;
      setVerdict(null);
      setBlurStuck(false);
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
    // ปุ่มใน Alert อาจถูกกดหลังออกจากหน้านี้แล้ว — ห้ามพาไปหน้าตรวจจากหน้าอื่น
    if (doneRef.current || !mountedRef.current) {
      dropTempFile(uri);
      return;
    }
    doneRef.current = true;
    loopTokenRef.current += 1;
    useEkycStore.getState().setCard(uri);
    router.push('/ekyc/review' as never);
  }, [mountedRef]);

  // ---------- กล้องในแอป ----------
  /** การถ่ายที่กำลังทำอยู่ (กดถ่ายเองต้องรอตัวนี้ก่อน — ถ่ายซ้อนกัน iOS โยน CameraNotReady) */
  const inFlightRef = useRef<Promise<unknown> | null>(null);
  const capture = useCallback(async (quality: number) => {
    const cam = cameraRef.current;
    if (!cam) return null;
    const job = (async () => {
      try {
        const photo = await cam.takePictureAsync({ quality, shutterSound: false });
        return photo?.uri ? { uri: photo.uri, width: photo.width ?? 0, height: photo.height ?? 0 } : null;
      } catch {
        return null;
      }
    })();
    inFlightRef.current = job;
    try {
      return await job;
    } finally {
      if (inFlightRef.current === job) inFlightRef.current = null;
    }
  }, []);

  const onCameraReady = useCallback(async () => {
    setCameraReady(true);
    try {
      const sizes = await cameraRef.current?.getAvailablePictureSizesAsync();
      // ละเอียดพอให้ตัวหนังสือไทยบนบัตรคมหลัง server ปรับบัตรเป็น 1000 px
      // iOS: 3840x2160 (≈8 MP) — preset "Photo" ได้ ~12 MP ไฟล์อาจเกิน 8 MB ที่ server รับ และตรวจทีละภาพช้า
      const best =
        Platform.OS === 'ios' && sizes?.includes('3840x2160')
          ? '3840x2160'
          : (pickCardPictureSize(sizes) ?? pickPictureSize(sizes, 1920, 1280));
      if (best && mountedRef.current) setPictureSize((prev) => prev ?? best);
    } catch {
      // ใช้ขนาดเริ่มต้นของกล้อง
    }
  }, [mountedRef]);

  /** ตรวจภาพบัตร 1 ภาพในเครื่อง · ตรวจใบหน้าไม่ได้ = null */
  const inspect = useCallback(async (shot: { uri: string; width: number; height: number }): Promise<CardVerdict | null> => {
    const faces = await detectFacesInImage(shot.uri);
    if (faces === null) return null;
    const result = await analyzeCardPhoto(shot.uri, {
      width: shot.width,
      height: shot.height,
      faces,
      frame: frameRef.current,
      stage: stageRef.current,
      faceRatio: faceRatioRef.current,
      relaxFill: relaxRef.current,
    });
    if (result.faceRatio !== null) {
      const prev = faceRatioRef.current;
      faceRatioRef.current = prev === null ? result.faceRatio : prev * 0.6 + result.faceRatio * 0.4;
    }
    return result;
  }, []);

  // ลูปถ่ายอัตโนมัติ: ตรวจทุกภาพ → ผ่านครบ (เต็มกรอบ + แสง + คม) ใช้ภาพนั้นเลย (ภาพที่ตรวจ = ภาพที่ส่ง)
  // ต้องเต็มกรอบติดกัน 2 ภาพ เว้นแต่ภาพนี้เจอขอบบัตรจริง · ไม่มีตัวอ่านพิกเซล = เต็มกรอบติดกัน 2 ภาพ
  const canLoop =
    mode === 'camera' && detectorAvailable && focused && appActive && cameraReady && !!permission?.granted && !!session;
  useEffect(() => {
    if (!canLoop) return undefined;
    const token = ++loopTokenRef.current;
    const alive = () => token === loopTokenRef.current && mountedRef.current && !doneRef.current;

    (async () => {
      let prevFit = false;
      let blurFrames = 0;
      // ตัวอ่านพิกเซลล้มติดกันหลายภาพ (ตำแหน่งผ่าน แต่วัดแสง/ความคมไม่ได้) = ใช้ไม่ได้ในเครื่องนี้ → ถ่ายแบบตำแหน่งอย่างเดียว
      let pixelChecks = analyzerAvailable;
      let unmeasuredFrames = 0;
      await sleep(700);
      while (alive()) {
        if (busyRef.current) {
          await sleep(300);
          continue;
        }
        const shot = await capture(0.9);
        if (!shot) {
          await sleep(600);
          continue;
        }
        if (!alive()) {
          dropTempFile(shot.uri);
          break;
        }
        const result = await inspect(shot);
        if (!alive()) {
          dropTempFile(shot.uri);
          break;
        }
        if (!result) {
          dropTempFile(shot.uri);
          await sleep(450);
          continue;
        }
        const measured = result.light !== null && result.sharp !== null;
        const unmeasured = result.fit && result.light !== false && !measured;
        if (measured) {
          // วัดได้อีกครั้ง → กลับมาตรวจเต็มรูปแบบ
          pixelChecks = analyzerAvailable;
          unmeasuredFrames = 0;
        } else if (pixelChecks && unmeasured) {
          unmeasuredFrames += 1;
          if (unmeasuredFrames >= 3) pixelChecks = false;
        }
        const ready = pixelChecks
          ? isCardShotGood(result) && (result.source === 'edges' || prevFit)
          : result.fit && prevFit && result.light !== false && result.sharp !== false;
        if (ready) {
          resultHaptic('success');
          goReview(shot.uri);
          return;
        }
        dropTempFile(shot.uri);
        prevFit = result.fit;
        blurFrames = result.hint === 'BLURRY' ? blurFrames + 1 : 0;
        if (blurFrames >= BLUR_STUCK_FRAMES) relaxRef.current = true;
        setVerdict(result);
        setBlurStuck(blurFrames >= BLUR_STUCK_FRAMES || (relaxRef.current && result.hint === 'BLURRY'));
        await sleep(result.fit ? 120 : 300);
      }
    })();

    return () => {
      loopTokenRef.current += 1;
    };
  }, [canLoop, capture, goReview, inspect, analyzerAvailable, loopKey, mountedRef, sleep]);

  const shootManual = async () => {
    if (busyRef.current || doneRef.current || !cameraReady) return;
    busyRef.current = true;
    setBusy(true);
    loopTokenRef.current += 1;
    // ลูปอาจกำลังถ่ายอยู่ — รอให้เสร็จก่อน (ไฟล์ของลูป ลูปลบเอง)
    await inFlightRef.current?.catch(() => null);
    let shot = mountedRef.current ? await capture(0.92) : null;
    if (!shot && mountedRef.current) {
      await sleep(400);
      shot = await capture(0.92);
    }
    // ตรวจภาพที่กดถ่ายเองด้วย — สิทธิ์ส่งรูปบัตรต่อรอบ/ต่อวันมีจำกัด ภาพที่ยังไม่พร้อมถามก่อนใช้
    const checked = shot && mountedRef.current && analyzerAvailable ? await inspect(shot) : null;
    if (!mountedRef.current) {
      busyRef.current = false;
      dropTempFile(shot?.uri);
      return;
    }
    setBusy(false);
    if (!shot) {
      busyRef.current = false;
      resultHaptic('error');
      Alert.alert('ถ่ายรูปไม่สำเร็จ', 'ลองกดถ่ายอีกครั้งนะ');
      setLoopKey((k) => k + 1);
      return;
    }
    const taken = shot;
    if (checked && checked.hint !== 'GOOD') {
      // ถือ busy ไว้จนกว่าผู้ใช้จะเลือก — กันลูปถ่ายเองที่เริ่มใหม่ (พับแอปแล้วกลับมา) แอบถ่ายซ้อนหลัง Alert
      resultHaptic('warning');
      setVerdict(checked);
      let answered = false;
      const settle = (use: boolean) => {
        if (answered) return;
        answered = true;
        busyRef.current = false;
        if (use) {
          goReview(taken.uri);
          return;
        }
        dropTempFile(taken.uri);
        if (mountedRef.current) setLoopKey((k) => k + 1);
      };
      Alert.alert(
        'ภาพบัตรยังไม่พร้อม',
        `${MANUAL_TEXT[checked.hint] ?? HINT_TEXT[checked.hint]}\nระบบอาจอ่านข้อมูลบนบัตรผิด แนะนำให้ถ่ายใหม่`,
        [
          { text: 'ใช้ภาพนี้', onPress: () => settle(true) },
          { text: 'ถ่ายใหม่', onPress: () => settle(false) },
        ],
        { cancelable: true, onDismiss: () => settle(false) }
      );
      return;
    }
    busyRef.current = false;
    resultHaptic('success');
    goReview(taken.uri);
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
    setVerdict(null);
    setBlurStuck(false);
    setMode((m) => (m === 'scanner' ? 'camera' : 'scanner'));
  };

  // ---------- ส่วนแสดงผล ----------
  // กรอบราว 80% ของความกว้าง: บัตรเต็มกรอบที่ระยะ ~13–15 ซม. (ใกล้กว่านี้กล้องหลายรุ่นโฟกัสไม่ได้) ยังได้บัตร > 1,200 px
  const frameW = Math.min(stage.w * 0.8, 400);
  const frameH = frameW / CARD_RATIO;
  const frameX = (stage.w - frameW) / 2;
  const frameY = Math.max(spacing.xl, stage.h * 0.4 - frameH / 2);
  frameRef.current = { x: frameX, y: frameY, w: frameW, h: frameH };
  stageRef.current = stage;
  const needPermission = mode === 'camera' && !permission?.granted;
  const showCamera = mode === 'camera' && !!permission?.granted && focused;
  const fit = !!verdict?.fit;

  const cameraStatus = (): string => {
    if (!detectorAvailable) return 'วางบัตรให้เต็มกรอบ แล้วกดปุ่มถ่าย';
    if (busy) return 'กำลังตรวจภาพ…';
    if (!verdict) return HINT_TEXT.NO_CARD;
    // ไฟฉายทำให้บัตรเคลือบสะท้อนแสง
    if (torch && (verdict.hint === 'GLARE_FACE' || verdict.hint === 'GLARE')) {
      return 'ปิดไฟฉาย แล้วเอียงบัตรเล็กน้อยให้แสงสะท้อนหายไป';
    }
    if (torch && verdict.hint === 'TOO_DARK') return 'แสงน้อยไป ย้ายไปที่สว่างขึ้น';
    // เบลอติดกันทั้งที่เต็มกรอบ = มักเพราะกล้องโฟกัสใกล้ไม่ได้ → ให้ถอยออก (โหมดผ่อนระยะยอมบัตรเล็กกว่ากรอบแล้ว)
    if (verdict.hint === 'BLURRY' && blurStuck) return 'ภาพยังไม่คม ลองถอยกล้องออกช้าๆ จนตัวหนังสือคม บัตรเล็กกว่ากรอบได้';
    const unmeasured = verdict.light === null || verdict.sharp === null;
    if (verdict.fit && verdict.hint === 'GOOD' && (!analyzerAvailable || unmeasured)) return 'พบบัตรแล้ว ถือนิ่งๆ กำลังถ่ายให้…';
    return HINT_TEXT[verdict.hint];
  };
  const status =
    mode === 'scanner' ? (busy ? 'กำลังเปิดตัวสแกนบัตร…' : 'กดปุ่มทอง ระบบจะจับขอบบัตรและถ่ายให้เอง') : cameraStatus();
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
            {fit &&
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
                <Text numberOfLines={2} style={[typography.bodyStrong, styles.statusText, { color: CAM.textStrong }]}>
                  {status}
                </Text>
              </View>
              {/* ชิปผูกกับสิ่งที่ตรวจได้จริงในเครื่องเท่านั้น (cardQuality) — server ตรวจซ้ำทุกข้อ */}
              {mode === 'camera' && detectorAvailable && (
                <View style={styles.chips}>
                  <CameraChip label="บัตรเต็มกรอบ" on={fit} />
                  {analyzerAvailable && <CameraChip label="แสงพอดี" on={verdict?.light === true} />}
                  {analyzerAvailable && <CameraChip label="ตัวหนังสือคมชัด" on={verdict?.sharp === true} />}
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
            {notice || 'วางบัตรบนพื้นเรียบสีเข้ม เลี่ยงไฟที่ส่องตรงบัตร ไม่ใช้นิ้วบังตัวเลข ระบบรับเฉพาะบัตรจริง ไม่รับรูปถ่ายหน้าจอ'}
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
