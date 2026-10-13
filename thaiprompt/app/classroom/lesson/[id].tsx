/**
 * เล่นบทเรียน (ห้องเรียน) — วิดีโอ YouTube (unlisted) ผ่าน IFrame API ใน WebView + นับเวลาดูกับ server
 *
 * เปิดด้วย /classroom/lesson/{lessonId}?courseId={courseId} (ดึงรายละเอียดหลักสูตรเพื่อรู้บทนี้/บทถัดไป)
 *
 * การนับเวลาดู (กันข้าม):
 *   - เริ่มเล่น → ส่ง heartbeat ทันที (ตั้งจุดเริ่มนับ) แล้วทุก 10 วิระหว่างเล่น {position_seconds, playing:true}
 *   - หยุด/จบ/ออกจากหน้า/แอปไปเบื้องหลัง → หยุดตัวจับเวลา + ส่ง heartbeat สุดท้าย playing:false
 *   - เน็ตหลุด = เงียบ แล้วรอบถัดไปส่งใหม่เอง · มี heartbeat ค้างอยู่ = ไม่ยิงซ้อน (จำครั้งล่าสุดไว้ส่งต่อ)
 *   - เลื่อนไปข้างหน้าไม่ได้ (ไม่มีแถบเลื่อน + หน้าเล่นดึงกลับ) — server ก็ไม่นับเวลาที่ข้าม
 * ดูจบ (server completed) → ตัวเลือก "เล่นบทถัดไปอัตโนมัติ" นับถอยหลัง 5 วิ (ยกเลิกได้) · บทสุดท้าย → ปุ่มทำแบบทดสอบ
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { AppState, StyleSheet, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, EmptyState, Icon, Pill, Screen, resultHaptic } from '@/components/ui';
import { ThemedSwitch } from '@/components/profile';
import { SkeletonCard } from '@/components/merchant/SkeletonBlock';
import { ProgressBar } from '@/components/classroom/ClassroomKit';
import { LessonPlayer, type LessonPlayerHandle } from '@/components/classroom/LessonPlayer';
import { YT_STATE, type PlayerMessage } from '@/components/classroom/playerHtml';
import { invalidateClassroomCourses } from '@/components/classroom/classroomCache';
import {
  formatClock,
  lessonStates,
  lessonTargetPct,
  lessonWatchPct,
  nextLessonOf,
  playerWindow,
} from '@/components/classroom/classroomLogic';
import {
  getClassroomCourse,
  postLessonProgress,
  type ClassroomCourseDetail,
  type LessonProgressResult,
} from '@/services/api/classroomApi';
import { useTheme, radii, spacing, typography } from '@/theme';

/** ส่ง heartbeat ทุกกี่มิลลิวินาทีระหว่างเล่น (ตามสัญญา 10 วิ) */
const HEARTBEAT_MS = 10_000;
/** นับถอยหลังก่อนไปบทถัดไป (วินาที) */
const ADVANCE_SECONDS = 5;
const AUTO_ADVANCE_KEY = 'classroom.autoAdvance';

type LoadState =
  | { kind: 'loading' }
  | { kind: 'ready'; course: ClassroomCourseDetail }
  | { kind: 'error'; message: string };

export default function ClassroomLessonScreen() {
  const { colors } = useTheme();
  const params = useLocalSearchParams<{ id?: string; courseId?: string }>();
  const lessonId = Number(params.id) || 0;
  const courseId = Number(params.courseId) || 0;

  const [loadState, setLoadState] = useState<LoadState>({ kind: 'loading' });
  // ความคืบหน้าจาก server
  const [watched, setWatched] = useState(0);
  const [completed, setCompleted] = useState(false);
  const [quizUnlocked, setQuizUnlocked] = useState(false);
  // เครื่องเล่น
  const [playerState, setPlayerState] = useState<number>(YT_STATE.UNSTARTED);
  const [playerReady, setPlayerReady] = useState(false);
  const [position, setPosition] = useState(0);
  const [videoDuration, setVideoDuration] = useState(0);
  const [seekNote, setSeekNote] = useState(false);
  const [needsReplay, setNeedsReplay] = useState(false);
  // ไปบทถัดไปอัตโนมัติ
  const [autoAdvance, setAutoAdvance] = useState(true);
  const [advanceIn, setAdvanceIn] = useState<number | null>(null);

  const playerRef = useRef<LessonPlayerHandle>(null);
  const mountedRef = useRef(true);
  const posRef = useRef(0);
  const durRef = useRef(0);
  const beatTimerRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const inFlightRef = useRef(false);
  const pendingRef = useRef<boolean | null>(null);
  const completedRef = useRef(false);
  const autoAdvanceRef = useRef(true);
  const seekNoteTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // ค่าตั้ง "เล่นบทถัดไปอัตโนมัติ" (ค่าเริ่มต้น เปิด) — อ่านไม่ได้ = ใช้ค่าเริ่มต้น
  useEffect(() => {
    AsyncStorage.getItem(AUTO_ADVANCE_KEY)
      .then((v) => {
        if (!mountedRef.current || v === null) return;
        const on = v !== '0';
        autoAdvanceRef.current = on;
        setAutoAdvance(on);
      })
      .catch(() => {});
  }, []);

  const toggleAutoAdvance = (on: boolean) => {
    autoAdvanceRef.current = on;
    setAutoAdvance(on);
    if (!on) setAdvanceIn(null);
    AsyncStorage.setItem(AUTO_ADVANCE_KEY, on ? '1' : '0').catch(() => {});
  };

  // ---------- โหลดหลักสูตร (หาบทนี้ + บทถัดไป) ----------
  const load = useCallback(async () => {
    if (!lessonId || !courseId) {
      setLoadState({ kind: 'error', message: 'ไม่พบบทเรียนนี้' });
      return;
    }
    const result = await getClassroomCourse(courseId);
    if (!mountedRef.current) return;
    if (!result.success) {
      setLoadState({ kind: 'error', message: result.message });
      return;
    }
    const lesson = result.data.lessons.find((l) => l.id === lessonId);
    if (!lesson) {
      setLoadState({ kind: 'error', message: 'ไม่พบบทเรียนนี้ในหลักสูตร' });
      return;
    }
    completedRef.current = lesson.completed;
    setWatched(lesson.watched_seconds);
    setCompleted(lesson.completed);
    setQuizUnlocked(result.data.quiz_unlocked);
    setLoadState({ kind: 'ready', course: result.data });
  }, [lessonId, courseId]);

  useEffect(() => {
    // เปลี่ยนบท (หน้าจอเดิมถูกใช้ซ้ำ) → ล้างสถานะเครื่องเล่นของบทก่อน
    posRef.current = 0;
    durRef.current = 0;
    pendingRef.current = null;
    setPlayerState(YT_STATE.UNSTARTED);
    setPlayerReady(false);
    setPosition(0);
    setVideoDuration(0);
    setNeedsReplay(false);
    setAdvanceIn(null);
    load().catch(() => {});
  }, [load]);

  const course = loadState.kind === 'ready' ? loadState.course : null;
  const lessonIndex = course ? course.lessons.findIndex((l) => l.id === lessonId) : -1;
  const lesson = course && lessonIndex >= 0 ? course.lessons[lessonIndex] : null;
  const locked = !!course && lessonIndex >= 0 && lessonStates(course.lessons)[lessonIndex] === 'locked';
  const nextLesson = course ? nextLessonOf(course.lessons, lessonId) : null;
  const currentLesson = course ? course.lessons.find((_, i) => lessonStates(course.lessons)[i] === 'current') ?? null : null;

  // จุดเริ่ม/เพดานการเลื่อน คิดครั้งเดียวตอนเปิดบท (ไม่ให้หน้าเล่นโหลดใหม่ทุกครั้งที่ server ตอบ)
  const windowRef = useRef<{ lessonId: number; start: number; maxAllowed: number } | null>(null);
  const playWindow = useMemo(() => {
    if (!lesson) return null;
    if (!windowRef.current || windowRef.current.lessonId !== lesson.id) {
      windowRef.current = { lessonId: lesson.id, ...playerWindow(lesson) };
    }
    return windowRef.current;
  }, [lesson]);

  const lessonDuration = lesson?.duration_seconds || videoDuration;

  // ---------- heartbeat ----------
  const applyResult = useCallback((r: LessonProgressResult) => {
    if (!mountedRef.current) return;
    setWatched((prev) => Math.max(prev, r.watched_seconds));
    if (r.completed && !completedRef.current) {
      completedRef.current = true;
      setCompleted(true);
      setNeedsReplay(false);
      invalidateClassroomCourses();
      resultHaptic('success');
    }
    if (r.course_quiz_unlocked) setQuizUnlocked(true);
  }, []);

  const sendBeat = useCallback(
    async (playing: boolean): Promise<LessonProgressResult | null> => {
      if (!lessonId) return null;
      if (inFlightRef.current) {
        // มีตัวที่ยังไม่ตอบ → จำสถานะล่าสุดไว้ ส่งต่อทันทีที่ตัวก่อนหน้าจบ (ไม่ยิงซ้อน)
        pendingRef.current = playing;
        return null;
      }
      inFlightRef.current = true;
      let out: LessonProgressResult | null = null;
      try {
        const res = await postLessonProgress(lessonId, { position_seconds: posRef.current, playing });
        // เน็ตหลุด/server ขัดข้อง = เงียบ รอบถัดไปส่งใหม่เอง
        if (res.success) {
          out = res.data;
          applyResult(res.data);
        }
      } catch {
        // เงียบ
      } finally {
        inFlightRef.current = false;
      }
      const pending = pendingRef.current;
      pendingRef.current = null;
      if (pending !== null) {
        const again = await sendBeat(pending);
        return again ?? out;
      }
      return out;
    },
    [lessonId, applyResult]
  );

  const startBeats = useCallback(() => {
    if (beatTimerRef.current) return;
    sendBeat(true).catch(() => {});
    beatTimerRef.current = setInterval(() => {
      sendBeat(true).catch(() => {});
    }, HEARTBEAT_MS);
  }, [sendBeat]);

  /** หยุดตัวจับเวลา · sendFinal = ส่ง heartbeat ปิดท้าย (เฉพาะตอนที่กำลังนับอยู่) */
  const stopBeats = useCallback(
    (sendFinal: boolean): Promise<LessonProgressResult | null> => {
      const wasRunning = !!beatTimerRef.current;
      if (beatTimerRef.current) {
        clearInterval(beatTimerRef.current);
        beatTimerRef.current = null;
      }
      return sendFinal && wasRunning ? sendBeat(false) : Promise.resolve(null);
    },
    [sendBeat]
  );

  // ออกจากหน้า → หยุดทุกตัวจับเวลา + ส่งปิดท้าย (ไม่รอผล)
  useEffect(() => {
    return () => {
      stopBeats(true).catch(() => {});
      if (seekNoteTimerRef.current) clearTimeout(seekNoteTimerRef.current);
    };
  }, [stopBeats]);

  // หน้าจอเสียโฟกัส (เปิดหน้าอื่นทับ) → หยุดวิดีโอ
  useFocusEffect(
    useCallback(() => {
      return () => {
        playerRef.current?.pause();
        stopBeats(true).catch(() => {});
        setAdvanceIn(null);
      };
    }, [stopBeats])
  );

  // แอปไปเบื้องหลัง → หยุดวิดีโอ + หยุดนับ (WebView อาจไม่ส่งสถานะหยุดมาตอนอยู่เบื้องหลัง)
  useEffect(() => {
    const sub = AppState.addEventListener('change', (next) => {
      if (next !== 'active') {
        playerRef.current?.pause();
        stopBeats(true).catch(() => {});
        setAdvanceIn(null);
      }
    });
    return () => sub.remove();
  }, [stopBeats]);

  // ---------- ข้อความจากเครื่องเล่น ----------
  const handleEnded = useCallback(
    (result: LessonProgressResult | null) => {
      if (!mountedRef.current) return;
      const done = result?.completed ?? completedRef.current;
      if (!done) {
        // ดูถึงท้ายคลิปแล้วแต่ server ยังนับไม่ถึง 90% (เช่น เน็ตหลุดช่วงหนึ่ง) → ชวนดูอีกรอบ
        setNeedsReplay(true);
        return;
      }
      if (autoAdvanceRef.current && nextLesson) setAdvanceIn(ADVANCE_SECONDS);
    },
    [nextLesson]
  );

  const onPlayerMessage = useCallback(
    (msg: PlayerMessage) => {
      switch (msg.t) {
        case 'ready':
          setPlayerReady(true);
          if (msg.dur > 0) {
            durRef.current = msg.dur;
            setVideoDuration(msg.dur);
          }
          break;
        case 'time':
          posRef.current = msg.pos;
          setPosition(msg.pos);
          if (msg.dur > 0 && Math.abs(msg.dur - durRef.current) > 0.5) {
            durRef.current = msg.dur;
            setVideoDuration(msg.dur);
          }
          break;
        case 'state':
          posRef.current = msg.pos || posRef.current;
          setPlayerState(msg.s);
          if (msg.s === YT_STATE.PLAYING) {
            setAdvanceIn(null);
            setNeedsReplay(false);
            startBeats();
          } else if (msg.s === YT_STATE.PAUSED) {
            stopBeats(true).catch(() => {});
          } else if (msg.s === YT_STATE.ENDED) {
            posRef.current = Math.max(posRef.current, durRef.current);
            setPosition(posRef.current);
            // ปิดตัวจับเวลาก่อน แล้วส่งตำแหน่งท้ายคลิปครั้งเดียว ใช้ผลตัดสินว่าจบบทหรือยัง
            stopBeats(false)
              .then(() => sendBeat(false))
              .then(handleEnded)
              .catch(() => {});
          }
          break;
        case 'seekBlocked':
          setSeekNote(true);
          if (seekNoteTimerRef.current) clearTimeout(seekNoteTimerRef.current);
          seekNoteTimerRef.current = setTimeout(() => {
            if (mountedRef.current) setSeekNote(false);
          }, 3500);
          break;
        case 'error':
          stopBeats(true).catch(() => {});
          break;
        default:
          break;
      }
    },
    [startBeats, stopBeats, sendBeat, handleEnded]
  );

  // ---------- ไปบทถัดไปอัตโนมัติ ----------
  const goNext = useCallback(() => {
    if (!nextLesson) return;
    setAdvanceIn(null);
    router.replace(`/classroom/lesson/${nextLesson.id}?courseId=${courseId}` as never);
  }, [nextLesson, courseId]);

  useEffect(() => {
    if (advanceIn === null) return undefined;
    if (advanceIn <= 0) {
      goNext();
      return undefined;
    }
    const t = setTimeout(() => {
      if (mountedRef.current) setAdvanceIn((n) => (n === null ? null : n - 1));
    }, 1000);
    return () => clearTimeout(t);
  }, [advanceIn, goNext]);

  // ---------- หน้าจอ ----------
  if (loadState.kind !== 'ready' || !lesson || !playWindow) {
    return (
      <Screen title="บทเรียน" scroll={loadState.kind !== 'error'}>
        {loadState.kind === 'error' ? (
          <EmptyState variant="error" message={loadState.message} actionLabel="ลองใหม่" onAction={load} secondaryActionLabel="กลับ" onSecondaryAction={() => router.back()} />
        ) : (
          <View accessibilityLabel="กำลังโหลดบทเรียน">
            <SkeletonCard lines={1} style={styles.skeletonVideo} />
            <SkeletonCard lines={3} />
          </View>
        )}
      </Screen>
    );
  }

  if (locked) {
    return (
      <Screen title="บทเรียน" scroll={false}>
        <EmptyState
          icon="lock"
          title="บทนี้ยังล็อกอยู่"
          message="ดูบทก่อนหน้าให้จบก่อน แล้วบทนี้จะปลดล็อกเองนะ"
          actionLabel={currentLesson ? 'ไปบทที่ต้องดูต่อ' : 'กลับไปหน้าหลักสูตร'}
          onAction={() =>
            currentLesson
              ? router.replace(`/classroom/lesson/${currentLesson.id}?courseId=${courseId}` as never)
              : router.back()
          }
        />
      </Screen>
    );
  }

  const isPlaying = playerState === YT_STATE.PLAYING || playerState === YT_STATE.BUFFERING;
  const ended = playerState === YT_STATE.ENDED;
  const watchPct = lessonWatchPct(watched, lessonDuration);
  const positionPct = videoDuration > 0 ? Math.min(100, (position / videoDuration) * 100) : 0;
  const total = course?.lessons.length ?? 0;

  const onMainPress = () => {
    if (ended || needsReplay) playerRef.current?.restart();
    else if (isPlaying) playerRef.current?.pause();
    else playerRef.current?.play();
  };

  return (
    <Screen title="บทเรียน" subtitle={`บทที่ ${lessonIndex + 1} จาก ${total}`}>
      <LessonPlayer
        ref={playerRef}
        videoId={lesson.youtube_id ?? ''}
        startSeconds={playWindow.start}
        maxAllowedSeconds={playWindow.maxAllowed}
        onMessage={onPlayerMessage}
      />

      <Text style={[typography.h2, styles.title, { color: colors.textStrong }]}>{lesson.title}</Text>
      {!!course && <Text style={[typography.caption, { color: colors.textMuted }]}>{course.title}</Text>}

      {/* ---------- ปุ่มควบคุม ---------- */}
      <Card3D padding={spacing.lg} style={styles.block}>
        <View style={styles.timeRow}>
          <Text style={[typography.caption, styles.tabular, { color: colors.textMuted }]}>{formatClock(position)}</Text>
          <Text style={[typography.caption, styles.tabular, { color: colors.textMuted }]}>
            {videoDuration > 0 ? formatClock(videoDuration) : lessonDuration > 0 ? formatClock(lessonDuration) : '--:--'}
          </Text>
        </View>
        <ProgressBar pct={positionPct} height={6} accessibilityLabel={`ตำแหน่งในคลิป ${formatClock(position)}`} />
        {seekNote && (
          <Text style={[typography.caption, styles.seekNote, { color: colors.warning }]} accessibilityLiveRegion="polite">
            บทเรียนนี้ข้ามไปข้างหน้าไม่ได้ ดูต่อจากจุดที่ดูถึงนะ
          </Text>
        )}
        <View style={styles.controls}>
          <Button3D
            title="ย้อน 10 วิ"
            icon="arrow-counter-clockwise"
            variant="secondary"
            size="md"
            disabled={!playerReady}
            onPress={() => playerRef.current?.back10()}
            style={styles.flex}
          />
          <Button3D
            title={ended || needsReplay ? 'ดูอีกครั้ง' : isPlaying ? 'หยุดชั่วคราว' : position > 1 ? 'เล่นต่อ' : 'เล่น'}
            icon={ended || needsReplay ? 'arrows-clockwise' : isPlaying ? 'pause' : 'play'}
            variant="primary"
            size="md"
            disabled={!playerReady}
            onPress={onMainPress}
            style={styles.flexWide}
          />
        </View>
      </Card3D>

      {/* ---------- ความคืบหน้าที่ server นับ ---------- */}
      <Card3D padding={spacing.lg} style={styles.block}>
        <View style={styles.rowBetween}>
          <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>เวลาที่ดูแล้ว</Text>
          {completed ? (
            <Pill label="ดูจบบทนี้แล้ว" tone="success" icon="check-circle" />
          ) : (
            <Text style={[typography.caption, { color: colors.goldDeep }]}>{watchPct}%</Text>
          )}
        </View>
        <ProgressBar
          pct={completed ? 100 : watchPct}
          targetPct={completed ? undefined : lessonTargetPct()}
          successWhenFull
          style={styles.barGap}
          accessibilityLabel={`ดูแล้ว ${watchPct} เปอร์เซ็นต์`}
        />
        <Text style={[typography.caption, styles.barGap, { color: colors.textMuted }]}>
          {completed
            ? 'บทนี้นับว่าดูจบแล้ว ดูซ้ำทบทวนได้ทุกเมื่อ'
            : `ดูแล้ว ${formatClock(watched)}${lessonDuration > 0 ? ` จาก ${formatClock(lessonDuration)}` : ''} · ต้องดูอย่างน้อย ${lessonTargetPct()}% ถึงนับว่าจบ`}
        </Text>
        {needsReplay && !completed && (
          <View style={[styles.note, { backgroundColor: colors.warningSoft }]}>
            <Icon name="warning" size={16} color={colors.warning} weight="fill" />
            <Text style={[typography.caption, styles.flex, { color: colors.text }]}>
              ระบบนับเวลาดูได้ยังไม่ถึง {lessonTargetPct()}% (อาจเพราะเน็ตหลุดบางช่วง) กด "ดูอีกครั้ง" แล้วดูให้จบนะ
            </Text>
          </View>
        )}
      </Card3D>

      {/* ---------- ไปต่อ ---------- */}
      {advanceIn !== null && nextLesson && (
        <Card3D padding={spacing.lg} style={styles.block} gradientBorder>
          <Text style={[typography.bodyStrong, { color: colors.textStrong }]} accessibilityLiveRegion="polite">
            บทถัดไปจะเริ่มใน {advanceIn} วินาที
          </Text>
          <Text numberOfLines={2} style={[typography.caption, { color: colors.textMuted }]}>
            {nextLesson.title}
          </Text>
          <View style={styles.controls}>
            <Button3D title="ยกเลิก" variant="secondary" size="sm" onPress={() => setAdvanceIn(null)} style={styles.flex} />
            <Button3D title="ไปเลย" icon="skip-forward" size="sm" onPress={goNext} style={styles.flex} />
          </View>
        </Card3D>
      )}

      {completed && advanceIn === null && (
        <View style={styles.block}>
          {nextLesson ? (
            <Button3D
              title="บทถัดไป"
              icon="skip-forward"
              iconRight="arrow-right"
              size="lg"
              variant="navy"
              fullWidth
              onPress={goNext}
            />
          ) : quizUnlocked ? (
            <Button3D
              title="ดูครบแล้ว ทำแบบทดสอบ"
              icon="exam"
              size="lg"
              fullWidth
              onPress={() => router.replace(`/classroom/quiz/${courseId}` as never)}
            />
          ) : (
            <Button3D title="กลับไปหน้าหลักสูตร" icon="list" variant="secondary" fullWidth onPress={() => router.back()} />
          )}
        </View>
      )}

      {!!nextLesson && (
        <Card3D padding={spacing.md} style={styles.block} variant="flat">
          <View style={styles.rowBetween}>
            <View style={styles.flex}>
              <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>เล่นบทถัดไปอัตโนมัติ</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>เมื่อดูบทนี้จบ จะเริ่มบทถัดไปให้เอง</Text>
            </View>
            <ThemedSwitch value={autoAdvance} onValueChange={toggleAutoAdvance} accessibilityLabel="เล่นบทถัดไปอัตโนมัติ" />
          </View>
        </Card3D>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  flexWide: {
    flex: 1.4,
  },
  skeletonVideo: {
    marginBottom: spacing.md,
    height: 200,
  },
  title: {
    marginTop: spacing.lg,
  },
  block: {
    marginTop: spacing.lg,
  },
  timeRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.xs + 2,
  },
  tabular: {
    fontVariant: ['tabular-nums'],
  },
  seekNote: {
    marginTop: spacing.sm,
  },
  controls: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.lg,
  },
  rowBetween: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.md,
  },
  barGap: {
    marginTop: spacing.sm,
  },
  note: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    padding: spacing.sm + 2,
    borderRadius: radii.md,
  },
});
