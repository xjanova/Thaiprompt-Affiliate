/**
 * หลักสูตร (ห้องเรียน) — รายการบทเรียน + แบบทดสอบ
 *
 * - บทเรียนเรียนตามลำดับ: ดูจบแล้ว (เขียว) · บทที่ต้องดูต่อ (ทอง) · ล็อก (รอบทก่อนหน้าจบ) — บทที่จบแล้วดูซ้ำได้
 * - ปุ่มทำแบบทดสอบล็อกจนกว่า server บอก quiz_unlocked · มีเวลารอ (retry_available_at) → นับถอยหลังแล้วปลดเอง
 * - สรุปผลสอบครั้งล่าสุด · ผ่านแล้ว = ตรา "ผ่านการอบรม" · ต้องอบรมเพิ่ม (กฎใหม่) = ตราจาง + คำอธิบาย
 * - กลับมาจากหน้าเล่นวิดีโอ/แบบทดสอบ → โหลดใหม่เงียบๆ (ไม่หมุนเต็มจอ)
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, EmptyState, Icon, Pill, Screen, SectionHeader, tapHaptic } from '@/components/ui';
import { SkeletonCard } from '@/components/merchant/SkeletonBlock';
import { TrainingSeal } from '@/components/badges/TrustSeals';
import { TRAINING_ROLE_NAME } from '@/components/badges/trainingBadge';
import { ProgressBar, RoleArt } from '@/components/classroom/ClassroomKit';
import { useCountdownMs } from '@/components/classroom/useCountdown';
import {
  courseProgressPct,
  formatClock,
  formatCountdown,
  formatThaiDuration,
  lessonStates,
  lessonWatchPct,
  pointsLabel,
  type LessonState,
} from '@/components/classroom/classroomLogic';
import { getClassroomCourse, type ClassroomCourseDetail, type ClassroomLesson } from '@/services/api/classroomApi';
import { formatThaiDateTime } from '@/components/shop/helpers';
import { useTheme, radii, spacing, typography } from '@/theme';

type LoadState =
  | { kind: 'loading' }
  | { kind: 'ready'; course: ClassroomCourseDetail }
  | { kind: 'error'; message: string; notFound: boolean };

const LessonRow: React.FC<{ lesson: ClassroomLesson; index: number; state: LessonState; last: boolean; onPress: () => void }> = ({
  lesson,
  index,
  state,
  last,
  onPress,
}) => {
  const { colors } = useTheme();
  const watchPct = lessonWatchPct(lesson.watched_seconds, lesson.duration_seconds);
  const locked = state === 'locked';
  const stateLabel = state === 'done' ? 'ดูจบแล้ว' : state === 'current' ? 'บทที่ต้องดูต่อ' : 'ล็อกอยู่ ดูบทก่อนหน้าให้จบก่อน';

  return (
    <Pressable
      onPress={() => {
        tapHaptic();
        onPress();
      }}
      accessibilityRole="button"
      accessibilityLabel={`บทที่ ${index + 1} ${lesson.title} ${stateLabel}`}
      accessibilityState={{ disabled: locked }}
      style={({ pressed }) => [
        styles.lessonRow,
        !last && { borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: colors.divider },
        pressed && !locked && { backgroundColor: colors.inset },
      ]}
    >
      <View
        style={[
          styles.lessonIcon,
          {
            backgroundColor: state === 'done' ? colors.successSoft : state === 'current' ? colors.navyFill : colors.inset,
          },
        ]}
      >
        <Icon
          name={state === 'done' ? 'check-circle' : state === 'current' ? 'play' : 'lock'}
          size={state === 'current' ? 18 : 20}
          color={state === 'done' ? colors.success : state === 'current' ? colors.goldLight : colors.textFaint}
          weight="fill"
        />
      </View>
      <View style={styles.flex}>
        <Text style={[typography.micro, { color: state === 'current' ? colors.goldDeep : colors.textFaint }]}>
          บทที่ {index + 1}
          {state === 'current' ? ' · ดูต่อได้เลย' : ''}
        </Text>
        <Text numberOfLines={2} style={[typography.bodyStrong, { color: locked ? colors.textMuted : colors.textStrong }]}>
          {lesson.title}
        </Text>
        <View style={styles.lessonMeta}>
          {lesson.duration_seconds > 0 && (
            <Text style={[typography.caption, { color: colors.textMuted }]}>{formatClock(lesson.duration_seconds)} นาที</Text>
          )}
          {state === 'current' && watchPct > 0 && (
            <Text style={[typography.caption, { color: colors.goldDeep }]}>ดูแล้ว {watchPct}%</Text>
          )}
        </View>
        {state === 'current' && watchPct > 0 && <ProgressBar pct={watchPct} height={5} targetPct={90} style={styles.lessonBar} />}
      </View>
      {!locked && <Icon name="caret-right" size={16} color={colors.textFaint} weight="bold" />}
    </Pressable>
  );
};

export default function ClassroomCourseScreen() {
  const { colors, gradients } = useTheme();
  const params = useLocalSearchParams<{ id?: string }>();
  const courseId = Number(params.id) || 0;

  const [state, setState] = useState<LoadState>({ kind: 'loading' });
  const [refreshing, setRefreshing] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);
  const mountedRef = useRef(true);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(async () => {
    if (!courseId) {
      setState({ kind: 'error', message: 'ไม่พบหลักสูตรนี้', notFound: true });
      return;
    }
    const result = await getClassroomCourse(courseId);
    if (!mountedRef.current) return;
    if (result.success) {
      setState({ kind: 'ready', course: result.data });
    } else {
      // โหลดซ้ำไม่สำเร็จแต่มีข้อมูลเดิม → คงหน้าจอเดิมไว้
      setState((prev) =>
        prev.kind === 'ready' ? prev : { kind: 'error', message: result.message, notFound: result.status === 404 }
      );
    }
  }, [courseId]);

  useFocusEffect(
    useCallback(() => {
      load().catch(() => {});
    }, [load])
  );

  const course = state.kind === 'ready' ? state.course : null;
  const cooldownMs = useCountdownMs(course?.retry_available_at);

  const onRefresh = async () => {
    setRefreshing(true);
    try {
      await load();
    } finally {
      if (mountedRef.current) setRefreshing(false);
    }
  };

  if (!course) {
    return (
      <Screen title="หลักสูตร" scroll={state.kind !== 'error'}>
        {state.kind === 'error' ? (
          <EmptyState
            variant={state.notFound ? 'empty' : 'error'}
            icon={state.notFound ? 'graduation-cap' : undefined}
            title={state.notFound ? 'ไม่พบหลักสูตรนี้' : undefined}
            message={state.notFound ? 'หลักสูตรอาจปิดไปแล้ว กลับไปเลือกหลักสูตรอื่นนะ' : state.message}
            actionLabel={state.notFound ? 'กลับห้องเรียน' : 'ลองใหม่'}
            onAction={state.notFound ? () => router.back() : load}
          />
        ) : (
          <View accessibilityLabel="กำลังโหลดหลักสูตร">
            <SkeletonCard lines={4} withTile style={styles.skeleton} />
            <SkeletonCard lines={5} style={styles.skeleton} />
          </View>
        )}
      </Screen>
    );
  }

  const states = lessonStates(course.lessons);
  const pct = courseProgressPct(course);
  const remainingLessons = course.lessons.filter((l) => !l.completed).length;
  const roleName = course.role ? TRAINING_ROLE_NAME[course.role] : null;
  const points = pointsLabel(course.points_reward);
  const certifiedOk = course.certified && !course.needs_refresh;
  const cooling = cooldownMs > 0;
  const canStartQuiz = course.quiz_unlocked && !cooling;
  const last = course.last_attempt;

  const openLesson = (lesson: ClassroomLesson, lessonState: LessonState) => {
    if (lessonState === 'locked') {
      setNotice('ดูบทก่อนหน้าให้จบก่อน แล้วบทนี้จะปลดล็อกเองนะ');
      return;
    }
    setNotice(null);
    router.push(`/classroom/lesson/${lesson.id}?courseId=${course.id}` as never);
  };

  const continueLesson = course.lessons.find((_, i) => states[i] === 'current') ?? null;

  return (
    <Screen title={roleName ? `หลักสูตร${roleName}` : 'หลักสูตร'} subtitle="ห้องเรียน" refreshing={refreshing} onRefresh={onRefresh}>
      {/* ---------- หัวหลักสูตร (การ์ดน้ำเงิน) ---------- */}
      <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.hero}>
        <View style={styles.heroRow}>
          <View style={[styles.heroArt, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
            <RoleArt role={course.role} size={58} />
          </View>
          <View style={styles.flex}>
            <Text style={[typography.serifSm, { color: colors.onHeader }]}>{course.title}</Text>
            {!!points && (
              <View style={styles.inlineRow}>
                <Icon name="star" size={13} color={colors.goldLight} weight="fill" />
                <Text style={[typography.caption, { color: colors.goldLight }]}>{points} เมื่อผ่าน</Text>
              </View>
            )}
          </View>
        </View>
        {!!course.description && (
          <Text style={[typography.bodySm, styles.heroDesc, { color: colors.onHeaderMuted }]}>{course.description}</Text>
        )}
        <View style={[styles.heroStats, { borderTopColor: colors.headerGlassBorder }]}>
          <View style={styles.stat}>
            <Text style={[typography.h3, { color: colors.onHeader }]}>{course.lesson_count}</Text>
            <Text style={[typography.micro, { color: colors.onHeaderMuted }]}>บทเรียน</Text>
          </View>
          <View style={[styles.statDivider, { backgroundColor: colors.headerGlassBorder }]} />
          <View style={styles.stat}>
            <Text style={[typography.h3, { color: colors.onHeader }]}>{formatThaiDuration(course.total_seconds)}</Text>
            <Text style={[typography.micro, { color: colors.onHeaderMuted }]}>ความยาวรวม</Text>
          </View>
          <View style={[styles.statDivider, { backgroundColor: colors.headerGlassBorder }]} />
          <View style={styles.stat}>
            <Text style={[typography.h3, { color: colors.onHeader }]}>
              {course.pass_score}/{course.question_count}
            </Text>
            <Text style={[typography.micro, { color: colors.onHeaderMuted }]}>ข้อที่ต้องผ่าน</Text>
          </View>
        </View>
        {!certifiedOk && (
          <View style={styles.heroProgress}>
            <View style={styles.progressHead}>
              <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>ความคืบหน้า</Text>
              <Text style={[typography.caption, { color: colors.goldLight }]}>{pct}%</Text>
            </View>
            <ProgressBar pct={pct} onDark accessibilityLabel={`ความคืบหน้า ${pct} เปอร์เซ็นต์`} />
          </View>
        )}
      </LinearGradient>

      {/* ---------- ผ่านแล้ว / ต้องอบรมเพิ่ม ---------- */}
      {certifiedOk && (
        <Card3D padding={spacing.lg} style={styles.block} gradientBorder>
          <View style={styles.certRow}>
            <TrainingSeal size={52} label={`ผ่านการอบรม${roleName ?? ''}`} />
            <View style={styles.flex}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>ผ่านการอบรมแล้ว</Text>
              <Text style={[typography.caption, { color: colors.textMuted }]}>
                ตรา "ผ่านการอบรม" แสดงข้างตรายืนยันตัวตนของคุณ
                {course.certified_at ? ` · ${formatThaiDateTime(course.certified_at, false)}` : ''}
              </Text>
            </View>
          </View>
        </Card3D>
      )}
      {course.certified && course.needs_refresh && (
        <Card3D padding={spacing.lg} style={styles.block}>
          <View style={styles.certRow}>
            <TrainingSeal size={48} needsRefresh compact label={`ผ่านการอบรม${roleName ?? ''}`} />
            <View style={styles.flex}>
              <Pill label="ต้องอบรมเพิ่ม" tone="warning" icon="warning" />
              <Text style={[typography.bodySm, styles.gapXs, { color: colors.text }]}>
                กฎมีการปรับปรุง ดูบทเรียนที่อัปเดตแล้วทำแบบทดสอบอีกครั้ง ตราจะกลับมาเต็มเหมือนเดิม
              </Text>
            </View>
          </View>
        </Card3D>
      )}

      {/* ---------- บทเรียน ---------- */}
      <SectionHeader
        title="บทเรียน"
        subtitle="ดูตามลำดับ แต่ละบทต้องดูอย่างน้อย 90% ถึงนับว่าจบ"
        style={styles.section}
      />
      {!!notice && (
        <View style={[styles.notice, { backgroundColor: colors.warningSoft }]} accessibilityLiveRegion="polite">
          <Icon name="lock" size={15} color={colors.warning} weight="fill" />
          <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>{notice}</Text>
        </View>
      )}
      {course.lessons.length === 0 ? (
        <Card3D variant="flat" padding={spacing.lg}>
          <Text style={[typography.bodySm, { color: colors.textMuted }]}>หลักสูตรนี้ยังไม่มีบทเรียน รอทีมงานเพิ่มบทเรียนก่อนนะ</Text>
        </Card3D>
      ) : (
        <Card3D padding={0} style={styles.lessonCard}>
          {course.lessons.map((lesson, i) => (
            <LessonRow
              key={lesson.id}
              lesson={lesson}
              index={i}
              state={states[i]}
              last={i === course.lessons.length - 1}
              onPress={() => openLesson(lesson, states[i])}
            />
          ))}
        </Card3D>
      )}
      {!!continueLesson && (
        <Button3D
          title={pct > 0 ? 'ดูบทเรียนต่อ' : 'เริ่มดูบทแรก'}
          icon="play"
          size="lg"
          variant="navy"
          fullWidth
          onPress={() => openLesson(continueLesson, 'current')}
          style={styles.block}
        />
      )}

      {/* ---------- แบบทดสอบ ---------- */}
      {!certifiedOk && (
        <>
          <SectionHeader title="แบบทดสอบ" style={styles.section} />
          <Card3D padding={spacing.lg}>
            <View style={styles.quizHead}>
              <View style={[styles.quizIcon, { backgroundColor: course.quiz_unlocked ? colors.goldSoft : colors.inset }]}>
                <Icon name={course.quiz_unlocked ? 'exam' : 'lock'} size={24} color={course.quiz_unlocked ? colors.goldDeep : colors.textFaint} />
              </View>
              <View style={styles.flex}>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
                  {course.question_count > 0 ? `${course.question_count} ข้อ` : 'แบบทดสอบท้ายหลักสูตร'}
                  {course.pass_score > 0 ? ` · ตอบถูก ${course.pass_score} ข้อขึ้นไปถือว่าผ่าน` : ''}
                </Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  {course.quiz_unlocked
                    ? 'ดูวิดีโอครบแล้ว ทำแบบทดสอบได้เลย'
                    : `ดูวิดีโอให้ครบทุกบทก่อน (เหลืออีก ${remainingLessons} บท)`}
                </Text>
              </View>
            </View>

            {!!last && (
              <View style={[styles.lastAttempt, { backgroundColor: last.passed ? colors.successSoft : colors.inset }]}>
                <Icon name={last.passed ? 'check-circle' : 'clock-counter-clockwise'} size={16} color={last.passed ? colors.success : colors.textMuted} />
                <Text style={[typography.caption, styles.flex, { color: colors.text }]}>
                  ครั้งล่าสุด {last.score}/{last.total} ข้อ · {last.passed ? 'ผ่าน' : 'ยังไม่ผ่าน'}
                  {last.submitted_at ? ` · ${formatThaiDateTime(last.submitted_at)}` : ''}
                </Text>
              </View>
            )}

            {cooling && (
              <View style={[styles.lastAttempt, { backgroundColor: colors.warningSoft }]} accessibilityLiveRegion="polite">
                <Icon name="timer" size={16} color={colors.warning} />
                <Text style={[typography.caption, styles.flex, { color: colors.text }]}>
                  ทำแบบทดสอบใหม่ได้ในอีก {formatCountdown(cooldownMs)} นาที ระหว่างนี้ทบทวนบทเรียนก่อนได้นะ
                </Text>
              </View>
            )}

            <Button3D
              title={cooling ? `รออีก ${formatCountdown(cooldownMs)}` : last && !last.passed ? 'ทำแบบทดสอบอีกครั้ง' : 'ทำแบบทดสอบ'}
              icon={canStartQuiz ? 'exam' : 'lock'}
              size="lg"
              fullWidth
              disabled={!canStartQuiz}
              onPress={() => router.push(`/classroom/quiz/${course.id}` as never)}
              style={styles.quizButton}
              accessibilityHint={course.quiz_unlocked ? undefined : 'ดูวิดีโอให้ครบทุกบทก่อน'}
            />
          </Card3D>
        </>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  skeleton: {
    marginBottom: spacing.md,
  },
  hero: {
    borderRadius: radii.xl,
    padding: spacing.lg,
  },
  heroRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  heroArt: {
    width: 72,
    height: 72,
    borderRadius: radii.lg,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  inlineRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 2,
  },
  heroDesc: {
    marginTop: spacing.md,
  },
  heroStats: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: spacing.lg,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  stat: {
    flex: 1,
    alignItems: 'center',
  },
  statDivider: {
    width: 1,
    height: 30,
  },
  heroProgress: {
    marginTop: spacing.lg,
  },
  progressHead: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.xs + 2,
  },
  block: {
    marginTop: spacing.lg,
  },
  certRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  gapXs: {
    marginTop: spacing.xs,
  },
  section: {
    marginTop: spacing.xxl,
    marginBottom: spacing.md,
  },
  notice: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    padding: spacing.md,
    borderRadius: radii.md,
    marginBottom: spacing.md,
  },
  lessonCard: {
    overflow: 'hidden',
  },
  lessonRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: 14,
  },
  lessonIcon: {
    width: 44,
    height: 44,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  lessonMeta: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: 2,
  },
  lessonBar: {
    marginTop: spacing.xs + 2,
  },
  quizHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  quizIcon: {
    width: 48,
    height: 48,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  lastAttempt: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    padding: spacing.sm + 2,
    borderRadius: radii.md,
  },
  quizButton: {
    marginTop: spacing.lg,
  },
});
