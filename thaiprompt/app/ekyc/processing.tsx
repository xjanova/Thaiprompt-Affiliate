/**
 * AI กำลังตรวจสอบ (ขั้นที่ 4 จาก 4) — ตามแบบ Processing.png
 *
 * - ส่งเฟรมใบหน้า (มองตรง + ท่าตามคำสั่ง) ไป POST /ekyc/sessions/{id}/face ครั้งเดียว (กันส่งซ้ำ)
 * - ระหว่างรอ: แถวผลตรวจค่อยๆ ขยับ (อ่านบัตร/บัตรจริง มาจากขั้นบัตรแล้ว · เป็นคนจริง/ใบหน้าตรงบัตร รอผลจริง)
 * - ห้ามย้อนกลับระหว่างส่ง/รอผล (ส่งไปแล้ว server ประมวลผลต่อเสมอ) · ส่งไม่สำเร็จ/ระบบไม่ว่าง = ย้อนกลับได้ (ถามก่อนทิ้งรูป)
 * - ระบบตรวจไม่ว่าง (503 EKYC_AI_BUSY) → รอตามเวลาที่ server บอก แล้วกด "ลองใหม่" ส่งเฟรมชุดเดิมซ้ำ (ไม่นับเป็นครั้งที่พลาด ไม่เริ่มรอบใหม่)
 * - server ยังตรวจคำขอก่อนหน้า / ตรวจเสร็จไปแล้ว (409 EKYC_PROCESSING / EKYC_SESSION_DONE)
 *   → ห้ามเริ่มรอบใหม่ (จะทิ้งคำขอที่กำลังตรวจ) · ถาม GET /ekyc/status ทุก 3 วินาที (สูงสุด 60 วินาที) แล้วไปหน้าผล
 * - รอบหมดอายุ / ท่าทางไม่ตรงคำสั่ง → เริ่มรอบใหม่ (ส่งรูปบัตรเดิมให้เอง) แล้วถ่ายใบหน้าใหม่
 * - เน็ตหลุด/หมดเวลา → ถาม GET /ekyc/status ก่อน (อาจตัดสินไปแล้ว/ยังตรวจอยู่) ไม่งั้นให้กดส่งอีกครั้ง
 * - ได้ผล → ลบไฟล์เฟรมในเครื่อง → หน้าผล
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, BackHandler, StyleSheet, View } from 'react-native';
import { router } from 'expo-router';
import Svg, { Circle } from 'react-native-svg';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, Icon, resultHaptic } from '@/components/ui';
import { CheckRow, EkycShell, InfoNote, useEkycExit, type CheckState } from '@/components/ekyc/EkycKit';
import { useRetryCountdown, useTimers } from '@/components/ekyc/cameraKit';
import { useMountedRef } from '@/components/taladsod/hooks';
import { useSensitiveScreen } from '@/hooks/useSensitiveScreen';
import { useAuthStore } from '@/stores/authStore';
import { useEkycStore } from '@/stores/ekycStore';
import {
  framesMatchChallenges,
  getEkycStatus,
  isLivenessReason,
  matchReasonTone,
  retryAfterSeconds,
  submitEkycFace,
  type EkycFaceResult,
} from '@/services/api/ekycApi';
import { goResultForCode, restartEkycSession } from '@/services/ekyc/flow';
import { isWaitCode, waitWhileProcessing } from '@/services/ekyc/outcome';
import { spacing, typography, useTheme, withAlpha } from '@/theme';

/** sending = กำลังส่ง · waiting = server ยังตรวจคำขอก่อนหน้า (ถามสถานะซ้ำ) · busy = ระบบตรวจไม่ว่าง รอแล้วลองใหม่ */
type Phase = 'sending' | 'waiting' | 'done' | 'busy' | 'error';
/** send = ส่งไม่สำเร็จ (รูปยังอยู่) · restart = เริ่มรอบใหม่ไม่สำเร็จ · waiting = รอผลนานเกิน */
type ErrorKind = 'send' | 'restart' | 'waiting';

/** แสดงคะแนนแบบทศนิยม 2 ตำแหน่ง */
const dec = (v: number | null): string => (v === null ? '' : v.toFixed(2));

export default function EkycProcessingScreen() {
  useSensitiveScreen('ekyc-processing');
  const { colors } = useTheme();
  const mountedRef = useMountedRef();
  const exit = useEkycExit();
  const { sleep } = useTimers();
  const { secondsLeft: retryLeft, start: startRetryCountdown } = useRetryCountdown();
  const session = useEkycStore((s) => s.session);
  const card = useEkycStore((s) => s.card);

  const [phase, setPhase] = useState<Phase>('sending');
  const [errorKind, setErrorKind] = useState<ErrorKind>('send');
  const [tick, setTick] = useState(0);
  const [result, setResult] = useState<EkycFaceResult | null>(null);
  const [errorText, setErrorText] = useState<string | null>(null);
  const sendingRef = useRef(false);
  const waitingRef = useRef(false);
  const restartingRef = useRef(false);
  const startedRef = useRef(false);

  const busyPhase = phase === 'sending' || phase === 'waiting' || phase === 'done';

  /** ออกจากขั้นตอน — ยังมีรูปใบหน้าที่ยังไม่ได้ส่ง = ถามก่อน (รูปจะถูกลบ) */
  const askExit = useCallback(() => {
    if (useEkycStore.getState().frames.length === 0) {
      exit();
      return;
    }
    Alert.alert('ออกจากการยืนยันตัวตน?', 'รูปใบหน้าที่ถ่ายไว้จะถูกลบ กลับมายืนยันตัวตนใหม่ได้ภายหลัง', [
      { text: 'อยู่ต่อ', style: 'cancel' },
      { text: 'ออก', style: 'destructive', onPress: exit },
    ]);
  }, [exit]);

  // ปุ่มย้อนกลับของเครื่อง: ระหว่างส่ง/รอผล = ไม่ทำอะไร · ส่งไม่สำเร็จ/ระบบไม่ว่าง = ออกได้ (ไม่ขังผู้ใช้)
  useEffect(() => {
    const sub = BackHandler.addEventListener('hardwareBackPress', () => {
      if (!busyPhase) askExit();
      return true;
    });
    return () => sub.remove();
  }, [busyPhase, askExit]);

  // จังหวะแถวผลตรวจระหว่างรอ
  useEffect(() => {
    if (phase !== 'sending' && phase !== 'waiting') return undefined;
    const t = setInterval(() => setTick((v) => Math.min(v + 1, 3)), 1400);
    return () => clearInterval(t);
  }, [phase]);

  const goResult = useCallback(() => {
    router.replace('/ekyc/result' as never);
  }, []);

  /** server ยังตรวจคำขอก่อนหน้า → ถามสถานะซ้ำจนเสร็จ แล้วไปหน้าผล (ห้ามเริ่มรอบใหม่) */
  const waitForDecision = useCallback(async () => {
    if (waitingRef.current) return;
    waitingRef.current = true;
    // server มีรูปชุดนี้แล้ว (กำลังตรวจ/ตรวจเสร็จ) → ส่งซ้ำไม่ได้อีก ลบไฟล์ในเครื่องได้เลย
    useEkycStore.getState().clearFrames();
    setPhase('waiting');
    setErrorText(null);
    setTick(0);
    const outcome = await waitWhileProcessing({
      fetchStatus: getEkycStatus,
      sleep,
      isCancelled: () => !mountedRef.current,
    });
    waitingRef.current = false;
    if (!mountedRef.current || outcome.kind === 'cancelled') return;
    const store = useEkycStore.getState();
    if (outcome.status) store.setStatus(outcome.status);

    if (outcome.kind === 'done') {
      store.clearFrames();
      // มีสถานะล่าสุด = หน้าผลอ่านจากสถานะเอง · รู้ผลจากรหัส error = ส่งผลสรุปไปให้
      store.setDecision(outcome.status ? null : outcome.decision);
      if (outcome.decision?.decision === 'approved') useAuthStore.getState().refreshUser().catch(() => {});
      goResult();
      return;
    }
    resultHaptic('warning');
    setErrorKind('waiting');
    setErrorText('ระบบยังตรวจคำขอก่อนหน้าไม่เสร็จ ผลจะแจ้งทางการแจ้งเตือน หรือกดดูผลอีกครั้งในอีกสักครู่');
    setPhase('error');
  }, [goResult, mountedRef, sleep]);

  const restart = useCallback(
    async (message: string) => {
      if (restartingRef.current) return;
      restartingRef.current = true;
      setPhase('sending');
      setErrorText(null);
      const outcome = await restartEkycSession('face');
      restartingRef.current = false;
      if (!mountedRef.current) return;
      if (outcome.kind === 'error') {
        if (isWaitCode(outcome.code)) {
          await waitForDecision();
          return;
        }
        if (goResultForCode(outcome.code)) return;
        resultHaptic('error');
        setErrorKind('restart');
        setErrorText(outcome.message);
        setPhase('error');
        return;
      }
      Alert.alert('ถ่ายใบหน้าใหม่อีกครั้งนะ', message);
      router.replace((outcome.kind === 'face' ? '/ekyc/face' : '/ekyc/capture') as never);
    },
    [mountedRef, waitForDecision]
  );

  const send = useCallback(async () => {
    if (sendingRef.current || waitingRef.current || restartingRef.current) return;
    const state = useEkycStore.getState();
    const sid = state.session?.session_id;
    const list = state.frames;
    if (!sid || !state.session || !framesMatchChallenges(list, state.session.challenges)) {
      router.replace('/ekyc' as never);
      return;
    }
    sendingRef.current = true;
    setPhase('sending');
    setErrorText(null);
    setTick(0);
    const before = state.status;
    const startedAt = Date.now();
    const res = await submitEkycFace(sid, list);
    // ให้ผู้ใช้เห็นขั้นตอนตรวจอย่างน้อยครู่หนึ่ง (ผลเร็วมากก็ไม่กระพริบ)
    const wait = Math.max(0, 1800 - (Date.now() - startedAt));
    if (wait > 0) await sleep(wait);
    sendingRef.current = false;
    if (!mountedRef.current) return;

    if (res.success) {
      const store = useEkycStore.getState();
      store.setDecision(res.data);
      store.clearFrames();
      setResult(res.data);
      setPhase('done');
      resultHaptic(res.data.decision === 'approved' ? 'success' : 'warning');
      // สถานะใหม่ + ข้อมูลผู้ใช้ (ป้ายทอง) — ไม่ต้องรอ
      store.loadStatus(true).catch(() => {});
      if (res.data.decision === 'approved') useAuthStore.getState().refreshUser().catch(() => {});
      await sleep(900);
      if (mountedRef.current) goResult();
      return;
    }

    // ระบบตรวจไม่ว่าง: รอบเดิม เฟรมเดิม — ไม่ใช่ความผิดของผู้ใช้ ไม่เริ่มรอบใหม่
    if (res.code === 'EKYC_AI_BUSY') {
      resultHaptic('warning');
      startRetryCountdown(retryAfterSeconds(res.data));
      setPhase('busy');
      return;
    }
    // ส่งซ้ำหลังเน็ตหลุด แต่ server ยังตรวจอยู่/ตรวจเสร็จแล้ว → รอดูผล (ห้ามเริ่มรอบใหม่)
    if (isWaitCode(res.code)) {
      await waitForDecision();
      return;
    }
    if (res.code === 'EKYC_SESSION_EXPIRED' || res.code === 'EKYC_CHALLENGE_MISMATCH') {
      resultHaptic('error');
      useEkycStore.getState().clearFrames();
      await restart(res.message);
      return;
    }
    if (goResultForCode(res.code)) return;

    if (res.code === 'NETWORK_ERROR' || res.code === 'TIMEOUT' || res.status >= 500) {
      // อาจตัดสินไปแล้ว/ยังตรวจอยู่ แต่คำตอบมาไม่ถึง → ถามสถานะก่อน
      const latest = await getEkycStatus();
      if (!mountedRef.current) return;
      if (latest.success) {
        useEkycStore.getState().setStatus(latest.data);
        if (latest.data.processing) {
          await waitForDecision();
          return;
        }
        const s = latest.data;
        const changed =
          (s.verified || s.kyc_status === 'pending') &&
          (before?.kyc_status !== s.kyc_status || before?.last_decision !== s.last_decision);
        if (changed) {
          useEkycStore.getState().clearFrames();
          useEkycStore.getState().setDecision(null);
          goResult();
          return;
        }
      }
    }
    resultHaptic('error');
    setErrorKind('send');
    setErrorText(res.message);
    setPhase('error');
  }, [goResult, mountedRef, restart, sleep, startRetryCountdown, waitForDecision]);

  useEffect(() => {
    if (startedRef.current) return;
    startedRef.current = true;
    send();
  }, [send]);

  // ---------- แถวผลตรวจ ----------
  const running = phase === 'sending' || phase === 'waiting';
  const cardResult = card?.result ?? null;
  const aiRead = cardResult ? cardResult.ai_available : true;
  const ocrPct = cardResult && aiRead ? `${Math.round(cardResult.ocr_confidence * 100)}%` : '';
  const cardReal = cardResult?.checks.card_real ?? null;
  const scores = result?.scores;
  const faceReasons = result?.reasons ?? [];
  const livenessState: CheckState = result
    ? faceReasons.some(isLivenessReason)
      ? 'fail'
      : 'done'
    : running && tick >= 1
      ? 'running'
      : 'idle';
  const matchTone = matchReasonTone(faceReasons);
  const matchState: CheckState = result
    ? matchTone === 'fail'
      ? 'fail'
      : matchTone === 'warn' || scores?.face_match === null
        ? 'warn'
        : 'done'
    : running && tick >= 2
      ? 'running'
      : 'idle';
  const decisionState: CheckState = result
    ? result.decision === 'approved'
      ? 'done'
      : result.decision === 'review'
        ? 'warn'
        : 'fail'
    : running && tick >= 3
      ? 'running'
      : 'idle';
  const decisionValue = result
    ? result.decision === 'approved'
      ? 'อนุมัติ'
      : result.decision === 'review'
        ? 'ส่งตรวจ'
        : 'ถ่ายใหม่'
    : '';

  const doneCount = [livenessState, matchState, decisionState].filter((s) => s === 'done' || s === 'warn' || s === 'fail').length;
  const ringProgress = (2 + doneCount) / 5;
  const R = 40;
  const C = 2 * Math.PI * R;

  const heroText = (() => {
    if (phase === 'busy') return { title: 'ระบบตรวจมีคนใช้เยอะ', sub: 'ลองใหม่ในอีกสักครู่ รูปยังอยู่ในเครื่อง ไม่ต้องถ่ายใหม่' };
    if (phase === 'waiting') return { title: 'AI กำลังตรวจสอบ', sub: 'ระบบยังตรวจคำขอก่อนหน้าอยู่ รอสักครู่ ไม่ต้องส่งใหม่' };
    if (phase === 'error') {
      if (errorKind === 'waiting') return { title: 'ยังไม่ได้ผลตรวจ', sub: 'ออกจากหน้านี้ได้เลย ผลจะแจ้งทางการแจ้งเตือน' };
      if (errorKind === 'restart') return { title: 'เริ่มรอบใหม่ไม่สำเร็จ', sub: 'ลองอีกครั้ง หรือออกแล้วกลับมาใหม่ภายหลัง' };
      return { title: 'ส่งตรวจไม่สำเร็จ', sub: 'รูปยังอยู่ในเครื่อง กดส่งอีกครั้งได้เลย' };
    }
    if (result) return { title: 'ตรวจเสร็จแล้ว', sub: 'ใช้เวลาไม่กี่วินาที ห้ามปิดแอป' };
    return { title: 'AI กำลังตรวจสอบ', sub: 'ใช้เวลาไม่กี่วินาที ห้ามปิดแอป' };
  })();

  const hero = (
    <View style={styles.hero}>
      <View style={styles.ring}>
        <Svg width={96} height={96}>
          <Circle cx={48} cy={48} r={R} stroke={withAlpha(colors.onHeader, 0.14)} strokeWidth={6} fill="none" />
          <Circle
            cx={48}
            cy={48}
            r={R}
            stroke={colors.gold}
            strokeWidth={6}
            strokeLinecap="round"
            fill="none"
            strokeDasharray={`${C} ${C}`}
            strokeDashoffset={C * (1 - ringProgress)}
            transform="rotate(-90 48 48)"
          />
        </Svg>
        <View style={[styles.ringIcon, { backgroundColor: colors.headerGlass }]}>
          <Icon name={phase === 'busy' ? 'hourglass' : 'cpu'} size={28} color={colors.goldLight} />
        </View>
      </View>
      <Text style={[typography.serifLg, styles.center, { color: colors.onHeader }]}>{heroText.title}</Text>
      <Text style={[typography.bodySm, styles.center, { color: colors.onHeaderMuted }]}>{heroText.sub}</Text>
    </View>
  );

  const bottom = (() => {
    if (phase === 'busy') {
      return (
        <View style={styles.row}>
          <Button3D title="ไว้ก่อน" variant="secondary" size="lg" onPress={askExit} />
          <Button3D
            title={retryLeft > 0 ? `ลองใหม่ได้ใน ${retryLeft} วินาที` : 'ลองใหม่'}
            icon="arrows-clockwise"
            size="lg"
            disabled={retryLeft > 0}
            onPress={send}
            style={styles.flex}
          />
        </View>
      );
    }
    if (phase !== 'error') return undefined;
    if (errorKind === 'waiting') {
      return (
        <View style={styles.row}>
          <Button3D title="ไว้ก่อน" variant="secondary" size="lg" onPress={exit} />
          <Button3D title="ดูผลอีกครั้ง" icon="arrows-clockwise" size="lg" onPress={waitForDecision} style={styles.flex} />
        </View>
      );
    }
    if (errorKind === 'restart') {
      return (
        <View style={styles.row}>
          <Button3D title="ไว้ก่อน" variant="secondary" size="lg" onPress={askExit} />
          <Button3D
            title="ลองอีกครั้ง"
            icon="arrows-clockwise"
            size="lg"
            onPress={() => restart('เริ่มรอบใหม่ให้แล้ว ทำท่าตามคำสั่งอีกครั้งนะ')}
            style={styles.flex}
          />
        </View>
      );
    }
    return (
      <View style={styles.row}>
        <Button3D
          title="ถ่ายใหม่"
          variant="secondary"
          size="lg"
          onPress={() => {
            useEkycStore.getState().clearFrames();
            router.replace('/ekyc/face' as never);
          }}
        />
        <Button3D title="ส่งอีกครั้ง" icon="arrows-clockwise" size="lg" onPress={send} style={styles.flex} />
      </View>
    );
  })();

  if (!session) return null;

  return (
    <EkycShell title="ยืนยันตัวตน" hideBack={busyPhase} onBack={askExit} hero={hero} step={4} bottom={bottom}>
      <Card3D padding={spacing.lg} radius={22}>
        <CheckRow
          first
          state={cardResult ? (aiRead ? 'done' : 'warn') : 'idle'}
          title="อ่านข้อมูลบัตร"
          subtitle={aiRead ? 'เลขบัตร ชื่อ วันเกิด ครบถ้วน' : 'ระบบอ่านบัตรอัตโนมัติไม่พร้อม เจ้าหน้าที่จะตรวจให้'}
          value={ocrPct}
        />
        <CheckRow
          state={cardResult ? (cardReal === true ? 'done' : 'warn') : 'idle'}
          title="บัตรของจริง"
          subtitle={cardReal === null && cardResult ? 'ยังไม่ทราบ เจ้าหน้าที่จะตรวจให้' : 'ไม่ใช่ภาพถ่ายหน้าจอหรือสำเนา'}
          value={cardResult ? (cardReal === true ? 'ผ่าน' : cardReal === false ? 'ตรวจเพิ่ม' : 'ไม่ทราบ') : ''}
        />
        <CheckRow
          state={livenessState}
          title="เป็นคนจริง"
          subtitle={`ทำตามคำสั่ง ${session.challenges.length} ข้อครบ ไม่พบการใช้รูปหลอก`}
          value={result ? dec(scores?.liveness ?? null) : livenessState === 'running' ? '…' : ''}
        />
        <CheckRow
          state={matchState}
          title="ใบหน้าตรงกับรูปในบัตร"
          subtitle={result ? 'เทียบด้วย AI จดจำใบหน้าแล้ว' : 'กำลังเทียบด้วย AI จดจำใบหน้า'}
          value={result ? dec(scores?.face_match ?? null) : matchState === 'running' ? '…' : ''}
        />
        <CheckRow state={decisionState} title="ตัดสินผล" subtitle="อนุมัติทันทีถ้ามั่นใจ" value={decisionValue} />
      </Card3D>

      {phase === 'busy' && (
        <View style={[styles.notice, { backgroundColor: colors.infoSoft }]}>
          <Icon name="hourglass" size={18} color={colors.info} />
          <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
            ระบบตรวจมีคนใช้เยอะ ลองใหม่ในอีกสักครู่ ครั้งนี้ไม่นับเป็นครั้งที่ยืนยันไม่ผ่าน
          </Text>
        </View>
      )}

      {phase === 'error' && !!errorText && (
        <View style={[styles.notice, { backgroundColor: errorKind === 'waiting' ? colors.infoSoft : colors.dangerSoft }]}>
          <Icon
            name={errorKind === 'waiting' ? 'clock' : 'warning-circle'}
            size={18}
            color={errorKind === 'waiting' ? colors.info : colors.danger}
          />
          <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>{errorText}</Text>
        </View>
      )}

      <InfoNote icon="lock">ประมวลผลบนเซิร์ฟเวอร์ของไทยพร้อมเท่านั้น ไม่มีการส่งรูปบัตรหรือรูปใบหน้าให้บริษัทภายนอก</InfoNote>
    </EkycShell>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  hero: {
    alignItems: 'center',
    gap: spacing.xs,
  },
  ring: {
    width: 96,
    height: 96,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.md,
  },
  ringIcon: {
    position: 'absolute',
    width: 60,
    height: 60,
    borderRadius: 30,
    alignItems: 'center',
    justifyContent: 'center',
  },
  center: {
    textAlign: 'center',
  },
  notice: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: 16,
    padding: spacing.md,
  },
});
