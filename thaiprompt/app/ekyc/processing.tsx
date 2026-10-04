/**
 * AI กำลังตรวจสอบ (ขั้นที่ 4 จาก 4) — ตามแบบ Processing.png
 *
 * - ส่งเฟรมใบหน้า (มองตรง + ท่าตามคำสั่ง) ไป POST /ekyc/sessions/{id}/face ครั้งเดียว (กันส่งซ้ำ)
 * - ระหว่างรอ: แถวผลตรวจค่อยๆ ขยับ (อ่านบัตร/บัตรจริง มาจากขั้นบัตรแล้ว · เป็นคนจริง/ใบหน้าตรงบัตร รอผลจริง)
 * - ห้ามย้อนกลับระหว่างส่ง (ส่งไปแล้ว server ประมวลผลต่อเสมอ)
 * - รอบหมดอายุ / ท่าทางไม่ตรงคำสั่ง → เริ่มรอบใหม่ (ส่งรูปบัตรเดิมให้เอง) แล้วถ่ายใบหน้าใหม่
 * - เน็ตหลุด/หมดเวลา → ถาม GET /ekyc/status ก่อน (อาจตัดสินไปแล้ว) ไม่งั้นให้กดส่งอีกครั้ง
 * - ได้ผล → ลบไฟล์เฟรมในเครื่อง → หน้าผล
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, BackHandler, StyleSheet, View } from 'react-native';
import { router } from 'expo-router';
import Svg, { Circle } from 'react-native-svg';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, Icon, resultHaptic } from '@/components/ui';
import { CheckRow, EkycShell, InfoNote, type CheckState } from '@/components/ekyc/EkycKit';
import { useTimers } from '@/components/ekyc/cameraKit';
import { useMountedRef } from '@/components/taladsod/hooks';
import { useSensitiveScreen } from '@/hooks/useSensitiveScreen';
import { useAuthStore } from '@/stores/authStore';
import { useEkycStore } from '@/stores/ekycStore';
import { framesMatchChallenges, submitEkycFace, type EkycFaceResult } from '@/services/api/ekycApi';
import { restartEkycSession } from '@/services/ekyc/flow';
import { spacing, typography, useTheme, withAlpha } from '@/theme';

type Phase = 'sending' | 'done' | 'error';

/** แสดงคะแนนแบบทศนิยม 2 ตำแหน่ง */
const dec = (v: number | null): string => (v === null ? '' : v.toFixed(2));

export default function EkycProcessingScreen() {
  useSensitiveScreen('ekyc-processing');
  const { colors } = useTheme();
  const mountedRef = useMountedRef();
  const { sleep } = useTimers();
  const session = useEkycStore((s) => s.session);
  const card = useEkycStore((s) => s.card);

  const [phase, setPhase] = useState<Phase>('sending');
  const [tick, setTick] = useState(0);
  const [result, setResult] = useState<EkycFaceResult | null>(null);
  const [errorText, setErrorText] = useState<string | null>(null);
  const sendingRef = useRef(false);
  const startedRef = useRef(false);

  // ห้ามย้อนกลับ (ส่งแล้ว / กำลังส่ง)
  useEffect(() => {
    const sub = BackHandler.addEventListener('hardwareBackPress', () => true);
    return () => sub.remove();
  }, []);

  // จังหวะแถวผลตรวจระหว่างรอ
  useEffect(() => {
    if (phase !== 'sending') return undefined;
    const t = setInterval(() => setTick((v) => Math.min(v + 1, 3)), 1400);
    return () => clearInterval(t);
  }, [phase]);

  const goResult = useCallback(() => {
    router.replace('/ekyc/result' as never);
  }, []);

  const restart = useCallback(
    async (message: string) => {
      const outcome = await restartEkycSession('face');
      if (!mountedRef.current) return;
      if (outcome.kind === 'error') {
        setErrorText(outcome.message);
        setPhase('error');
        return;
      }
      Alert.alert('ถ่ายใบหน้าใหม่อีกครั้งนะ', message);
      router.replace((outcome.kind === 'face' ? '/ekyc/face' : '/ekyc/capture') as never);
    },
    [mountedRef]
  );

  const send = useCallback(async () => {
    if (sendingRef.current) return;
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

    resultHaptic('error');
    if (res.code === 'EKYC_SESSION_EXPIRED' || res.code === 'EKYC_CHALLENGE_MISMATCH') {
      useEkycStore.getState().clearFrames();
      await restart(res.message);
      return;
    }
    if (res.code === 'EKYC_TOO_MANY_ATTEMPTS' || res.code === 'EKYC_ALREADY_VERIFIED') {
      useEkycStore.getState().clearFrames();
      await useEkycStore.getState().loadStatus(true);
      if (mountedRef.current) goResult();
      return;
    }
    if (res.code === 'NETWORK_ERROR' || res.code === 'TIMEOUT' || res.status >= 500) {
      // อาจตัดสินไปแล้วแต่คำตอบมาไม่ถึง → ถามสถานะก่อน
      const before = useEkycStore.getState().status;
      const latest = await useEkycStore.getState().loadStatus(true);
      if (!mountedRef.current) return;
      const changed =
        !!latest &&
        (latest.verified || latest.kyc_status === 'pending') &&
        (before?.kyc_status !== latest.kyc_status || before?.last_decision !== latest.last_decision);
      if (changed) {
        useEkycStore.getState().clearFrames();
        goResult();
        return;
      }
    }
    setErrorText(res.message);
    setPhase('error');
  }, [goResult, mountedRef, restart, sleep]);

  useEffect(() => {
    if (startedRef.current) return;
    startedRef.current = true;
    send();
  }, [send]);

  // ---------- แถวผลตรวจ ----------
  const cardResult = card?.result ?? null;
  const ocrPct = cardResult ? `${Math.round(cardResult.ocr_confidence * 100)}%` : '';
  const scores = result?.scores;
  const faceReasons = result?.reasons ?? [];
  const livenessState: CheckState = result
    ? faceReasons.some((r) => r.startsWith('CHALLENGE_FAILED') || r === 'SPOOF_SUSPECTED' || r === 'NO_FACE' || r === 'MULTIPLE_FACES')
      ? 'fail'
      : 'done'
    : phase === 'error'
      ? 'idle'
      : tick >= 1
        ? 'running'
        : 'idle';
  const matchState: CheckState = result
    ? faceReasons.includes('DIFFERENT_PEOPLE')
      ? 'fail'
      : faceReasons.includes('LOW_MATCH')
        ? 'warn'
        : 'done'
    : phase === 'error'
      ? 'idle'
      : tick >= 2
        ? 'running'
        : 'idle';
  const decisionState: CheckState = result
    ? result.decision === 'approved'
      ? 'done'
      : result.decision === 'review'
        ? 'warn'
        : 'fail'
    : tick >= 3 && phase === 'sending'
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
          <Icon name="cpu" size={28} color={colors.goldLight} />
        </View>
      </View>
      <Text style={[typography.serifLg, styles.center, { color: colors.onHeader }]}>
        {phase === 'error' ? 'ส่งตรวจไม่สำเร็จ' : result ? 'ตรวจเสร็จแล้ว' : 'AI กำลังตรวจสอบ'}
      </Text>
      <Text style={[typography.bodySm, styles.center, { color: colors.onHeaderMuted }]}>
        {phase === 'error' ? 'รูปยังอยู่ในเครื่อง กดส่งอีกครั้งได้เลย' : 'ใช้เวลาไม่กี่วินาที ห้ามปิดแอป'}
      </Text>
    </View>
  );

  const bottom =
    phase === 'error' ? (
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
    ) : undefined;

  if (!session) return null;

  return (
    <EkycShell title="ยืนยันตัวตน" hideBack hero={hero} step={4} bottom={bottom}>
      <Card3D padding={spacing.lg} radius={22}>
        <CheckRow first state={cardResult ? 'done' : 'idle'} title="อ่านข้อมูลบัตร" subtitle="เลขบัตร ชื่อ วันเกิด ครบถ้วน" value={ocrPct} />
        <CheckRow
          state={cardResult ? (cardResult.checks.card_real ? 'done' : 'warn') : 'idle'}
          title="บัตรของจริง"
          subtitle="ไม่ใช่ภาพถ่ายหน้าจอหรือสำเนา"
          value={cardResult ? (cardResult.checks.card_real ? 'ผ่าน' : 'ตรวจเพิ่ม') : ''}
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

      {phase === 'error' && !!errorText && (
        <View style={[styles.error, { backgroundColor: colors.dangerSoft }]}>
          <Icon name="warning-circle" size={18} color={colors.danger} />
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
  error: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: 16,
    padding: spacing.md,
  },
});
