/**
 * แบบทดสอบท้ายหลักสูตร (ห้องเรียน)
 *
 * ลำดับ: หน้าแนะนำ → เริ่ม (POST quiz/start) → ตอบทีละข้อ → ส่งครั้งเดียว (POST attempts/{id}/submit) → ผลสอบ
 * - ต้องเลือกคำตอบก่อนไปข้อถัดไป/ก่อนส่ง (ข้อความเตือนไทยใต้ตัวเลือก) · ส่งแล้วกันกดซ้ำ (in-flight guard)
 * - QUIZ_LOCKED (ยังดูวิดีโอไม่ครบ) · QUIZ_COOLDOWN (นับถอยหลังจาก retry_available_at) · ATTEMPT_EXPIRED (หมดเวลา → เริ่มรอบใหม่)
 * - ผ่าน = การ์ดฉลองพร้อมตรา "ผ่านการอบรม" + "+N คะแนนความประพฤติ" · รีเฟรชโปรไฟล์ให้ตราขึ้นทุกจุด
 * - ไม่ผ่าน = เฉลยเฉพาะข้อที่ผิดพร้อมคำอธิบาย + นับถอยหลังทำใหม่
 * - ออกกลางคัน → ถามยืนยันก่อน (คำตอบที่เลือกไว้จะหาย)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, Pressable, StyleSheet, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import Animated, { FadeInDown, ZoomIn } from 'react-native-reanimated';
import { router, useLocalSearchParams, useNavigation } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, EmptyState, Icon, Pill, Screen, resultHaptic, selectionHaptic } from '@/components/ui';
import { SkeletonCard } from '@/components/merchant/SkeletonBlock';
import { TrainingSeal } from '@/components/badges/TrustSeals';
import { TRAINING_ROLE_NAME } from '@/components/badges/trainingBadge';
import { ProgressBar } from '@/components/classroom/ClassroomKit';
import { useCountdownMs } from '@/components/classroom/useCountdown';
import { invalidateClassroomCourses } from '@/components/classroom/classroomCache';
import {
  answeredCount,
  checkAnswers,
  formatCountdown,
  isValidChoice,
  missingAnswersMessage,
  pointsLabel,
} from '@/components/classroom/classroomLogic';
import {
  cooldownFromFailure,
  getClassroomCourse,
  startClassroomQuiz,
  submitClassroomQuiz,
  type ClassroomCourseDetail,
  type QuizAnswers,
  type QuizStart,
  type QuizSubmitResult,
} from '@/services/api/classroomApi';
import { thaiMessageForCode } from '@/services/api/client';
import { useAuthStore } from '@/stores/authStore';
import { useTheme, radii, spacing, typography } from '@/theme';

/** ตัวอักษรนำหน้าตัวเลือก */
const CHOICE_MARKS = ['ก', 'ข', 'ค', 'ง', 'จ', 'ฉ'];

type Phase =
  | { kind: 'loading' }
  | { kind: 'error'; message: string }
  | { kind: 'intro' }
  | { kind: 'answering'; attempt: QuizStart }
  | { kind: 'result'; attempt: QuizStart; answers: QuizAnswers; result: QuizSubmitResult }
  | { kind: 'expired' };

export default function ClassroomQuizScreen() {
  const { colors, gradients } = useTheme();
  const navigation = useNavigation();
  const params = useLocalSearchParams<{ courseId?: string }>();
  const courseId = Number(params.courseId) || 0;

  const [course, setCourse] = useState<ClassroomCourseDetail | null>(null);
  const [phase, setPhase] = useState<Phase>({ kind: 'loading' });
  const [retryAt, setRetryAt] = useState<string | null>(null);
  const [introError, setIntroError] = useState<string | null>(null);
  const [starting, setStarting] = useState(false);
  // ระหว่างตอบ
  const [index, setIndex] = useState(0);
  const [answers, setAnswers] = useState<QuizAnswers>({});
  const [inlineError, setInlineError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const mountedRef = useRef(true);
  const startingRef = useRef(false);
  const submittingRef = useRef(false);
  const inProgressRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  inProgressRef.current = phase.kind === 'answering';

  // ออกกลางคันระหว่างตอบ → ถามก่อน (ปุ่มย้อนกลับของหัว/ของเครื่อง/ปัด)
  useEffect(() => {
    const unsub = navigation.addListener('beforeRemove', (e: any) => {
      if (!inProgressRef.current || submittingRef.current) return;
      e.preventDefault();
      Alert.alert('ออกจากแบบทดสอบ?', 'คำตอบที่เลือกไว้จะหาย และรอบนี้จะไม่ถูกนับ', [
        { text: 'ทำต่อ', style: 'cancel' },
        {
          text: 'ออก',
          style: 'destructive',
          onPress: () => {
            inProgressRef.current = false;
            navigation.dispatch(e.data.action);
          },
        },
      ]);
    });
    return unsub;
  }, [navigation]);

  // ---------- โหลดหลักสูตร ----------
  const loadCourse = useCallback(async () => {
    if (!courseId) {
      setPhase({ kind: 'error', message: 'ไม่พบหลักสูตรนี้' });
      return;
    }
    const result = await getClassroomCourse(courseId);
    if (!mountedRef.current) return;
    if (result.success) {
      setCourse(result.data);
      setRetryAt(result.data.retry_available_at);
      setPhase((prev) => (prev.kind === 'loading' || prev.kind === 'error' ? { kind: 'intro' } : prev));
    } else {
      setPhase((prev) => (prev.kind === 'loading' || prev.kind === 'error' ? { kind: 'error', message: result.message } : prev));
    }
  }, [courseId]);

  useEffect(() => {
    loadCourse().catch(() => {});
  }, [loadCourse]);

  const cooldownMs = useCountdownMs(retryAt);
  const expiresAt = phase.kind === 'answering' ? phase.attempt.expires_at : null;
  const timeLeftMs = useCountdownMs(expiresAt);

  // ---------- เริ่ม ----------
  const start = async () => {
    if (startingRef.current) return;
    startingRef.current = true;
    setStarting(true);
    setIntroError(null);
    try {
      const res = await startClassroomQuiz(courseId);
      if (!mountedRef.current) return;
      if (res.success) {
        if (res.data.questions.length === 0 || res.data.attempt_id <= 0) {
          setIntroError('แบบทดสอบนี้ยังไม่มีคำถาม ลองใหม่ภายหลังนะ');
          return;
        }
        setAnswers({});
        setIndex(0);
        setInlineError(null);
        setPhase({ kind: 'answering', attempt: res.data });
        return;
      }
      if (res.code === 'QUIZ_COOLDOWN') {
        const at = cooldownFromFailure(res);
        if (at) setRetryAt(at);
        setIntroError(res.message || thaiMessageForCode('QUIZ_COOLDOWN'));
      } else if (res.code === 'QUIZ_LOCKED') {
        setCourse((c) => (c ? { ...c, quiz_unlocked: false } : c));
        setIntroError(res.message || thaiMessageForCode('QUIZ_LOCKED'));
      } else {
        setIntroError(res.message);
      }
      resultHaptic('warning');
    } finally {
      startingRef.current = false;
      if (mountedRef.current) setStarting(false);
    }
  };

  // ---------- ตอบ ----------
  const choose = (questionId: number, choice: number) => {
    selectionHaptic();
    setAnswers((prev) => ({ ...prev, [questionId]: choice }));
    setInlineError(null);
  };

  const goNext = () => {
    if (phase.kind !== 'answering') return;
    const q = phase.attempt.questions[index];
    if (!isValidChoice(q, answers[q.id])) {
      setInlineError('เลือกคำตอบก่อนไปข้อถัดไปนะ');
      resultHaptic('warning');
      return;
    }
    setInlineError(null);
    setIndex((i) => Math.min(i + 1, phase.attempt.questions.length - 1));
  };

  const goPrev = () => {
    setInlineError(null);
    setIndex((i) => Math.max(0, i - 1));
  };

  const doSubmit = async (attempt: QuizStart) => {
    if (submittingRef.current) return;
    submittingRef.current = true;
    setSubmitting(true);
    setInlineError(null);
    try {
      const res = await submitClassroomQuiz(attempt.attempt_id, answers);
      if (!mountedRef.current) return;
      if (res.success) {
        inProgressRef.current = false;
        invalidateClassroomCourses();
        setRetryAt(res.data.retry_available_at);
        setPhase({ kind: 'result', attempt, answers, result: res.data });
        resultHaptic(res.data.passed ? 'success' : 'warning');
        if (res.data.passed) {
          // ตรา "ผ่านการอบรม" ในโปรไฟล์ (training_badges) มาจาก /me — ดึงใหม่ให้ขึ้นทุกจุดทันที
          useAuthStore.getState().refreshUser().catch(() => {});
        }
        return;
      }
      if (res.code === 'ATTEMPT_EXPIRED') {
        inProgressRef.current = false;
        setPhase({ kind: 'expired' });
        return;
      }
      if (res.code === 'ATTEMPT_SUBMITTED' || res.code === 'ATTEMPT_NOT_FOUND') {
        // ส่งไปแล้ว (เช่น รอบก่อนเน็ตหลุดแต่ server ได้รับ / ส่งจากอีกเครื่อง) หรือรอบนี้ไม่มีแล้ว
        // → กลับหน้าแนะนำ แล้วโหลดผลสอบล่าสุด/เวลารอสอบใหม่จาก server
        inProgressRef.current = false;
        invalidateClassroomCourses();
        setIntroError(res.message || thaiMessageForCode(res.code));
        setPhase({ kind: 'loading' });
        loadCourse().catch(() => {});
        useAuthStore.getState().refreshUser().catch(() => {});
        return;
      }
      // เน็ตหลุด/ระบบขัดข้อง → คงคำตอบไว้ กดส่งใหม่ได้
      setInlineError(res.message);
      resultHaptic('error');
    } finally {
      submittingRef.current = false;
      if (mountedRef.current) setSubmitting(false);
    }
  };

  const submit = () => {
    if (phase.kind !== 'answering' || submittingRef.current) return;
    const check = checkAnswers(phase.attempt.questions, answers);
    if (!check.ok) {
      if (check.firstMissingIndex >= 0) setIndex(check.firstMissingIndex);
      setInlineError(missingAnswersMessage(check.missingNumbers));
      resultHaptic('warning');
      return;
    }
    const attempt = phase.attempt;
    Alert.alert('ส่งคำตอบ?', 'ส่งแล้วแก้ไขคำตอบไม่ได้', [
      { text: 'ตรวจอีกรอบ', style: 'cancel' },
      { text: 'ส่งคำตอบ', onPress: () => doSubmit(attempt) },
    ]);
  };

  // =====================================================
  // หน้าจอตามขั้น
  // =====================================================

  const roleName = course?.role ? TRAINING_ROLE_NAME[course.role] : null;
  const title = roleName ? `แบบทดสอบ${roleName}` : 'แบบทดสอบ';

  if (phase.kind === 'loading') {
    return (
      <Screen title={title}>
        <View accessibilityLabel="กำลังโหลดแบบทดสอบ">
          <SkeletonCard lines={4} withTile />
        </View>
      </Screen>
    );
  }

  if (phase.kind === 'error') {
    return (
      <Screen title={title} scroll={false}>
        <EmptyState variant="error" message={phase.message} onAction={loadCourse} secondaryActionLabel="กลับ" onSecondaryAction={() => router.back()} />
      </Screen>
    );
  }

  if (phase.kind === 'expired') {
    return (
      <Screen title={title} scroll={false}>
        <EmptyState
          icon="timer"
          title="หมดเวลาทำแบบทดสอบรอบนี้"
          message={thaiMessageForCode('ATTEMPT_EXPIRED')}
          actionLabel="เริ่มรอบใหม่"
          onAction={async () => {
            setPhase({ kind: 'intro' });
            await loadCourse();
          }}
          secondaryActionLabel="กลับไปหน้าหลักสูตร"
          onSecondaryAction={() => router.back()}
        />
      </Screen>
    );
  }

  // ---------- ผลสอบ ----------
  if (phase.kind === 'result') {
    const { result, attempt } = phase;
    const cert = result.certification;
    const awarded = cert?.points_awarded ?? 0;
    const wrong = result.results.filter((r) => !r.correct);
    const questionById = new Map(attempt.questions.map((q) => [q.id, q]));
    const passScore = result.pass_score || course?.pass_score || 0;

    return (
      <Screen title={title} subtitle="ผลแบบทดสอบ">
        {result.passed ? (
          <Animated.View entering={FadeInDown.springify().damping(16)}>
            <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.celebrate}>
              <Animated.View entering={ZoomIn.delay(150).springify().damping(10)}>
                <TrainingSeal size={104} label={roleName ? `ผ่านการอบรม${roleName}` : 'ผ่านการอบรม'} />
              </Animated.View>
              <Text style={[typography.serif, styles.centerText, styles.gapTop, { color: colors.goldLight }]}>ยินดีด้วย ผ่านการอบรมแล้ว</Text>
              <Text style={[typography.bodySm, styles.centerText, { color: colors.onHeaderMuted }]}>
                ตอบถูก {result.score} จาก {result.total} ข้อ · ตรา "ผ่านการอบรม" จะแสดงข้างตรายืนยันตัวตนของคุณ
              </Text>
              {awarded > 0 ? (
                <View style={[styles.pointsBig, { backgroundColor: colors.headerGlass, borderColor: colors.gold }]}>
                  <Icon name="star" size={18} color={colors.goldLight} weight="fill" />
                  <Text style={[typography.h3, { color: colors.goldLight }]}>{pointsLabel(awarded)}</Text>
                </View>
              ) : (
                <View style={[styles.pointsBig, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
                  <Icon name="seal-check" size={18} color={colors.goldLight} weight="fill" />
                  <Text style={[typography.bodyStrong, { color: colors.onHeader }]}>ต่ออายุตราเรียบร้อย</Text>
                </View>
              )}
            </LinearGradient>
          </Animated.View>
        ) : (
          <Card3D padding={spacing.xl}>
            <View style={styles.center}>
              <View style={[styles.failIcon, { backgroundColor: colors.warningSoft }]}>
                <Icon name="exam" size={34} color={colors.warning} />
              </View>
              <Text style={[typography.h1, styles.gapTop, { color: colors.textStrong }]}>
                {result.score}/{result.total}
              </Text>
              <Pill label="ยังไม่ผ่าน" tone="warning" />
              <Text style={[typography.bodySm, styles.centerText, styles.gapTop, { color: colors.textMuted }]}>
                ต้องตอบถูกอย่างน้อย {passScore} ข้อ ดูเฉลยด้านล่าง ทบทวนบทเรียนแล้วลองใหม่ได้เลย
              </Text>
              {cooldownMs > 0 && (
                <Text style={[typography.bodyStrong, styles.gapTop, { color: colors.warning }]} accessibilityLiveRegion="polite">
                  ทำใหม่ได้ในอีก {formatCountdown(cooldownMs)}
                </Text>
              )}
            </View>
            <View style={styles.actions}>
              <Button3D title="ทบทวนบทเรียน" icon="play-circle" variant="secondary" onPress={() => router.back()} style={styles.flex} />
              <Button3D
                title={cooldownMs > 0 ? `รอ ${formatCountdown(cooldownMs)}` : 'ทำใหม่'}
                icon="arrows-clockwise"
                disabled={cooldownMs > 0}
                onPress={() => {
                  setIntroError(null);
                  setPhase({ kind: 'intro' });
                }}
                style={styles.flex}
              />
            </View>
          </Card3D>
        )}

        {/* เฉลยข้อที่ผิด */}
        {wrong.length > 0 && (
          <>
            <Text style={[typography.h2, styles.sectionTitle, { color: colors.textStrong }]}>เฉลยข้อที่ตอบผิด ({wrong.length} ข้อ)</Text>
            {wrong.map((r) => {
              const q = questionById.get(r.question_id);
              const qNo = attempt.questions.findIndex((x) => x.id === r.question_id) + 1;
              const mine = phase.answers[r.question_id];
              return (
                <Card3D key={r.question_id} padding={spacing.lg} style={styles.reviewCard}>
                  <Text style={[typography.micro, { color: colors.textFaint }]}>ข้อ {qNo > 0 ? qNo : '-'}</Text>
                  <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>{q?.question ?? 'คำถาม'}</Text>
                  {q && isValidChoice(q, mine) && (
                    <View style={styles.reviewLine}>
                      <Icon name="x-circle" size={16} color={colors.danger} weight="fill" />
                      <Text style={[typography.bodySm, styles.flex, { color: colors.danger }]}>คำตอบของคุณ: {q.choices[mine]}</Text>
                    </View>
                  )}
                  {q && r.correct_index !== null && r.correct_index < q.choices.length && (
                    <View style={styles.reviewLine}>
                      <Icon name="check-circle" size={16} color={colors.success} weight="fill" />
                      <Text style={[typography.bodySm, styles.flex, { color: colors.success }]}>
                        คำตอบที่ถูก: {q.choices[r.correct_index]}
                      </Text>
                    </View>
                  )}
                  {!!r.explanation && (
                    <View style={[styles.explain, { backgroundColor: colors.inset }]}>
                      <Icon name="info" size={15} color={colors.info} />
                      <Text style={[typography.caption, styles.flex, { color: colors.text }]}>{r.explanation}</Text>
                    </View>
                  )}
                </Card3D>
              );
            })}
          </>
        )}

        <Button3D
          title="กลับไปหน้าหลักสูตร"
          icon="arrow-left"
          variant={result.passed ? 'primary' : 'ghost'}
          size={result.passed ? 'lg' : 'md'}
          fullWidth
          onPress={() => router.back()}
          style={styles.sectionTitle}
        />
      </Screen>
    );
  }

  // ---------- หน้าแนะนำ ----------
  if (phase.kind === 'intro' || !course) {
    const unlocked = !!course?.quiz_unlocked;
    const cooling = cooldownMs > 0;
    const points = pointsLabel(course?.points_reward ?? 0);
    return (
      <Screen title={title} subtitle={course?.title}>
        <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.introHero}>
          {/* ยังล็อก = ตราจาง (ยังไม่ถึงขั้นสอบ) */}
          <View style={!unlocked ? styles.dimmed : undefined}>
            <TrainingSeal size={64} />
          </View>
          <Text style={[typography.serifSm, styles.centerText, styles.gapTop, { color: colors.onHeader }]}>
            ทำแบบทดสอบให้ผ่าน รับตรา "ผ่านการอบรม"
          </Text>
          {!!points && (
            <View style={[styles.pointsTag, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
              <Icon name="star" size={13} color={colors.goldLight} weight="fill" />
              <Text style={[typography.caption, { color: colors.goldLight }]}>{points}</Text>
            </View>
          )}
        </LinearGradient>

        <Card3D padding={spacing.lg} style={styles.block}>
          {[
            { icon: 'exam' as const, text: `มีทั้งหมด ${course?.question_count ?? 0} ข้อ ตอบถูก ${course?.pass_score ?? 0} ข้อขึ้นไปถือว่าผ่าน` },
            { icon: 'hand-tap' as const, text: 'ตอบทีละข้อ ต้องเลือกคำตอบก่อนไปข้อถัดไป ย้อนกลับไปแก้ได้ก่อนส่ง' },
            { icon: 'timer' as const, text: 'ถ้ายังไม่ผ่าน รอสักครู่แล้วทำใหม่ได้ ไม่จำกัดจำนวนครั้ง' },
          ].map((row) => (
            <View key={row.icon} style={styles.ruleRow}>
              <View style={[styles.ruleIcon, { backgroundColor: colors.navySoft }]}>
                <Icon name={row.icon} size={18} color={colors.navy} />
              </View>
              <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>{row.text}</Text>
            </View>
          ))}
        </Card3D>

        {!unlocked && (
          <View style={[styles.notice, { backgroundColor: colors.warningSoft }]}>
            <Icon name="lock" size={16} color={colors.warning} weight="fill" />
            <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>{thaiMessageForCode('QUIZ_LOCKED')}</Text>
          </View>
        )}
        {cooling && (
          <View style={[styles.notice, { backgroundColor: colors.warningSoft }]} accessibilityLiveRegion="polite">
            <Icon name="timer" size={16} color={colors.warning} />
            <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>ทำแบบทดสอบใหม่ได้ในอีก {formatCountdown(cooldownMs)}</Text>
          </View>
        )}
        {!!introError && !cooling && (
          <View style={[styles.notice, { backgroundColor: colors.dangerSoft }]} accessibilityLiveRegion="polite">
            <Icon name="warning-circle" size={16} color={colors.danger} weight="fill" />
            <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>{introError}</Text>
          </View>
        )}

        {unlocked ? (
          <Button3D
            title={cooling ? `รออีก ${formatCountdown(cooldownMs)}` : 'เริ่มทำแบบทดสอบ'}
            icon="exam"
            size="lg"
            fullWidth
            loading={starting}
            loadingText="กำลังเตรียมคำถาม..."
            disabled={cooling || starting}
            onPress={start}
            style={styles.block}
          />
        ) : (
          <Button3D title="กลับไปดูบทเรียน" icon="play-circle" variant="navy" size="lg" fullWidth onPress={() => router.back()} style={styles.block} />
        )}
      </Screen>
    );
  }

  // ---------- ตอบคำถาม ----------
  const questions = phase.attempt.questions;
  const q = questions[Math.min(index, questions.length - 1)];
  const isLast = index >= questions.length - 1;
  const answered = answeredCount(questions, answers);
  const progressPct = (answered / questions.length) * 100;

  return (
    <Screen title={title} subtitle={`ข้อ ${index + 1} จาก ${questions.length}`}>
      <View style={styles.answerHead}>
        <Text style={[typography.caption, { color: colors.textMuted }]}>
          ตอบแล้ว {answered}/{questions.length} ข้อ
        </Text>
        {!!expiresAt && (
          <View style={styles.inline}>
            <Icon name="timer" size={14} color={timeLeftMs < 60_000 ? colors.danger : colors.textMuted} />
            <Text style={[typography.caption, { color: timeLeftMs < 60_000 ? colors.danger : colors.textMuted }]}>
              {timeLeftMs > 0 ? `เหลือเวลา ${formatCountdown(timeLeftMs)}` : 'หมดเวลาแล้ว'}
            </Text>
          </View>
        )}
      </View>
      <ProgressBar pct={progressPct} height={6} accessibilityLabel={`ตอบแล้ว ${answered} จาก ${questions.length} ข้อ`} />

      {/* จุดบอกข้อ (แตะเพื่อกระโดดไปข้อนั้น) */}
      <View style={styles.dots}>
        {questions.map((item, i) => {
          const done = isValidChoice(item, answers[item.id]);
          const current = i === index;
          return (
            <Pressable
              key={item.id}
              onPress={() => {
                setInlineError(null);
                setIndex(i);
              }}
              hitSlop={4}
              accessibilityRole="button"
              accessibilityLabel={`ข้อ ${i + 1}${done ? ' ตอบแล้ว' : ' ยังไม่ตอบ'}`}
              style={[
                styles.dot,
                {
                  backgroundColor: done ? colors.gold : colors.inset,
                  borderColor: current ? colors.navy : 'transparent',
                },
              ]}
            >
              <Text style={[styles.dotText, { color: done ? colors.textOnGold : colors.textMuted }]}>{i + 1}</Text>
            </Pressable>
          );
        })}
      </View>

      <Animated.View key={q.id} entering={FadeInDown.duration(220)}>
        <Card3D padding={spacing.lg} style={styles.block}>
          <Text style={[typography.overline, { color: colors.goldDeep }]}>ข้อ {index + 1}</Text>
          <Text style={[typography.h3, styles.question, { color: colors.textStrong }]}>{q.question}</Text>
          <View accessibilityRole="radiogroup">
            {q.choices.map((choice, ci) => {
              const selected = answers[q.id] === ci;
              return (
                <Pressable
                  key={ci}
                  onPress={() => choose(q.id, ci)}
                  accessibilityRole="radio"
                  accessibilityState={{ checked: selected }}
                  accessibilityLabel={`${CHOICE_MARKS[ci] ?? ci + 1}. ${choice}`}
                  style={({ pressed }) => [
                    styles.choice,
                    {
                      backgroundColor: selected ? colors.goldSoft : pressed ? colors.inset : colors.card,
                      borderColor: selected ? colors.gold : colors.border,
                    },
                  ]}
                >
                  <View
                    style={[
                      styles.choiceMark,
                      { backgroundColor: selected ? colors.navyFill : colors.inset, borderColor: selected ? colors.gold : colors.border },
                    ]}
                  >
                    <Text style={[styles.choiceMarkText, { color: selected ? colors.goldLight : colors.textMuted }]}>
                      {CHOICE_MARKS[ci] ?? ci + 1}
                    </Text>
                  </View>
                  <Text style={[typography.body, styles.flex, { color: colors.textStrong }]}>{choice}</Text>
                  {selected && <Icon name="check-circle" size={20} color={colors.goldDeep} weight="fill" />}
                </Pressable>
              );
            })}
          </View>
          {!!inlineError && (
            <View style={[styles.inlineError, { backgroundColor: colors.dangerSoft }]} accessibilityLiveRegion="assertive">
              <Icon name="warning-circle" size={16} color={colors.danger} weight="fill" />
              <Text style={[typography.bodySm, styles.flex, { color: colors.danger }]}>{inlineError}</Text>
            </View>
          )}
        </Card3D>
      </Animated.View>

      <View style={styles.actions}>
        <Button3D title="ข้อก่อนหน้า" icon="arrow-left" variant="secondary" disabled={index === 0 || submitting} onPress={goPrev} style={styles.flex} />
        {isLast ? (
          <Button3D
            title="ส่งคำตอบ"
            icon="paper-plane-tilt"
            loading={submitting}
            loadingText="กำลังส่ง..."
            disabled={submitting}
            onPress={submit}
            style={styles.flex}
          />
        ) : (
          <Button3D title="ข้อถัดไป" iconRight="arrow-right" onPress={goNext} style={styles.flex} />
        )}
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    alignItems: 'center',
  },
  centerText: {
    textAlign: 'center',
  },
  gapTop: {
    marginTop: spacing.md,
  },
  block: {
    marginTop: spacing.lg,
  },
  inline: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  dimmed: {
    opacity: 0.5,
  },
  introHero: {
    alignItems: 'center',
    borderRadius: radii.xl,
    padding: spacing.xl,
  },
  pointsTag: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
    borderRadius: radii.pill,
    borderWidth: 1,
  },
  ruleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.sm,
  },
  ruleIcon: {
    width: 36,
    height: 36,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  notice: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    padding: spacing.md,
    borderRadius: radii.md,
    marginTop: spacing.md,
  },
  answerHead: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.xs + 2,
  },
  dots: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginTop: spacing.md,
  },
  dot: {
    width: 30,
    height: 30,
    borderRadius: 15,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dotText: {
    fontSize: 12,
    lineHeight: 16,
    fontWeight: '700',
  },
  question: {
    marginTop: spacing.xs,
    marginBottom: spacing.md,
  },
  choice: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    minHeight: 54,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm + 2,
    borderRadius: radii.md,
    borderWidth: 1.5,
    marginTop: spacing.sm,
  },
  choiceMark: {
    width: 30,
    height: 30,
    borderRadius: 15,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  choiceMarkText: {
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '700',
  },
  inlineError: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    padding: spacing.sm + 2,
    borderRadius: radii.md,
  },
  actions: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.lg,
  },
  celebrate: {
    alignItems: 'center',
    borderRadius: radii.xl,
    padding: spacing.xl,
  },
  pointsBig: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
    borderRadius: radii.pill,
    borderWidth: 1.5,
  },
  failIcon: {
    width: 68,
    height: 68,
    borderRadius: 34,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sectionTitle: {
    marginTop: spacing.xxl,
    marginBottom: spacing.sm,
  },
  reviewCard: {
    marginTop: spacing.md,
  },
  reviewLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  explain: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginTop: spacing.md,
    padding: spacing.sm + 2,
    borderRadius: radii.md,
  },
});
