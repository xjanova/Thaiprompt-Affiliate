/**
 * ตรรกะของห้องเรียน (pure ไม่มี UI) — ความคืบหน้า · สถานะบทเรียน · เวลารอทำแบบทดสอบใหม่ · ตรวจคำตอบ · ลำดับหลักสูตร
 *
 * แยกจากหน้าจอให้เทสต์ได้ (components/classroom/__tests__/classroomLogic.test.ts)
 */

import { msUntil } from '@/utils/serverClock';
import { TRAINING_ROLES, type TrainingRole } from '@/components/badges/trainingBadge';
import type { ClassroomCourse, ClassroomLesson, QuizAnswers, QuizQuestion } from '@/services/api/classroomApi';

// =====================================================
// ความคืบหน้า
// =====================================================

/** server นับว่าดูจบเมื่อดูครบ 90% ของความยาว */
export const LESSON_COMPLETE_RATIO = 0.9;

/** % ที่ดูแล้วของบทเรียน (0–100) */
export const lessonWatchPct = (watchedSeconds: number, durationSeconds: number): number => {
  if (!Number.isFinite(durationSeconds) || durationSeconds <= 0) return 0;
  const w = Number.isFinite(watchedSeconds) ? Math.max(0, watchedSeconds) : 0;
  return Math.min(100, Math.round((w / durationSeconds) * 100));
};

/** % ที่ต้องดูให้ถึงจึงนับว่าจบ (เส้นเป้าบนแถบความคืบหน้า) */
export const lessonTargetPct = (): number => Math.round(LESSON_COMPLETE_RATIO * 100);

/** % ความคืบหน้าของหลักสูตร (ใช้ progress_pct ของ server · ไม่มี = นับบทที่จบ) */
export const courseProgressPct = (course: Pick<ClassroomCourse, 'progress_pct' | 'completed_lessons' | 'lesson_count'>): number => {
  if (Number.isFinite(course.progress_pct) && course.progress_pct > 0) return Math.min(100, Math.max(0, Math.round(course.progress_pct)));
  if (course.lesson_count <= 0) return 0;
  return Math.min(100, Math.round((Math.max(0, course.completed_lessons) / course.lesson_count) * 100));
};

// =====================================================
// สถานะบทเรียน (เรียนตามลำดับ)
// =====================================================

/**
 * done = ดูจบแล้ว · current = บทที่ต้องดูต่อ (บทแรกที่ยังไม่จบ) · locked = รอดูบทก่อนหน้าให้จบก่อน
 * บทที่จบแล้วดูซ้ำได้เสมอ
 */
export type LessonState = 'done' | 'current' | 'locked';

export const lessonStates = (lessons: readonly Pick<ClassroomLesson, 'completed'>[]): LessonState[] => {
  let currentGiven = false;
  return lessons.map((l) => {
    if (l.completed) return 'done';
    if (!currentGiven) {
      currentGiven = true;
      return 'current';
    }
    return 'locked';
  });
};

/** บทนี้กดเล่นได้ไหม (ไม่ใช่ locked) */
export const isLessonPlayable = (lessons: readonly Pick<ClassroomLesson, 'id' | 'completed'>[], lessonId: number): boolean => {
  const idx = lessons.findIndex((l) => l.id === lessonId);
  if (idx < 0) return false;
  return lessonStates(lessons)[idx] !== 'locked';
};

/** บทถัดไปตามลำดับ (บทสุดท้าย = null) */
export const nextLessonOf = <T extends Pick<ClassroomLesson, 'id'>>(lessons: readonly T[], lessonId: number): T | null => {
  const idx = lessons.findIndex((l) => l.id === lessonId);
  return idx >= 0 && idx < lessons.length - 1 ? lessons[idx + 1] : null;
};

/** ทุกบทจบแล้ว (ไม่มีบท = false) */
export const allLessonsDone = (lessons: readonly Pick<ClassroomLesson, 'completed'>[]): boolean =>
  lessons.length > 0 && lessons.every((l) => l.completed);

/**
 * จุดเริ่มเล่น + จุดไกลสุดที่เลื่อนไปได้ (กันข้ามไปข้างหน้าในหน้าเล่น — server ก็ไม่นับเวลาที่ข้ามอยู่แล้ว)
 * - ดูจบแล้ว = เริ่มต้นใหม่ เลื่อนได้ทั้งคลิป
 * - ยังไม่จบ = เล่นต่อจากตำแหน่งล่าสุดที่ server บันทึก (last_position_seconds · ไม่มี = watched_seconds)
 *   ถอยหลัง 3 วิ ให้ต่อเนื่อง · เลื่อนได้ไม่เกินจุดไกลสุดที่ดูแล้ว
 */
export const playerWindow = (
  lesson: Pick<ClassroomLesson, 'watched_seconds' | 'duration_seconds' | 'completed' | 'last_position_seconds'>
): { start: number; maxAllowed: number } => {
  const duration = Math.max(0, lesson.duration_seconds);
  if (lesson.completed) return { start: 0, maxAllowed: duration > 0 ? duration : Number.MAX_SAFE_INTEGER };
  const watched = Math.max(0, Math.floor(lesson.watched_seconds));
  const lastPos =
    typeof lesson.last_position_seconds === 'number' && Number.isFinite(lesson.last_position_seconds) && lesson.last_position_seconds > 0
      ? Math.floor(lesson.last_position_seconds)
      : null;
  const resumeAt = lastPos ?? watched;
  const cap = duration > 5 ? duration - 5 : resumeAt;
  const start = Math.max(0, Math.min(resumeAt, cap) - 3);
  const furthest = Math.max(watched, lastPos ?? 0);
  return { start, maxAllowed: Math.max(duration > 0 ? Math.min(furthest, duration) : furthest, start) };
};

// =====================================================
// เวลา
// =====================================================

/** เหลืออีกกี่มิลลิวินาทีถึงจะทำแบบทดสอบใหม่ได้ (ไม่มี/เลยแล้ว = 0) */
export const cooldownRemainingMs = (
  retryAvailableAt: string | null | undefined,
  offsetMs?: number | null,
  deviceNowMs: number = Date.now()
): number => {
  const left = msUntil(retryAvailableAt, offsetMs, deviceNowMs);
  return left === null ? 0 : Math.max(0, left);
};

/** นับถอยหลัง "m:ss" (เกินชั่วโมง "h:mm:ss") */
export const formatCountdown = (ms: number): string => {
  const total = Math.max(0, Math.ceil((Number.isFinite(ms) ? ms : 0) / 1000));
  const h = Math.floor(total / 3600);
  const m = Math.floor((total % 3600) / 60);
  const s = total % 60;
  const ss = String(s).padStart(2, '0');
  return h > 0 ? `${h}:${String(m).padStart(2, '0')}:${ss}` : `${m}:${ss}`;
};

/** ตำแหน่งในคลิป "m:ss" */
export const formatClock = (seconds: number): string => formatCountdown(Math.max(0, Math.floor(seconds || 0)) * 1000);

/** ความยาวเป็นภาษาไทย: "45 วินาที" · "12 นาที" · "1 ชม. 5 นาที" */
export const formatThaiDuration = (seconds: number): string => {
  const total = Math.max(0, Math.round(Number.isFinite(seconds) ? seconds : 0));
  if (total < 60) return `${total} วินาที`;
  const minutes = Math.round(total / 60);
  if (minutes < 60) return `${minutes} นาที`;
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  return m > 0 ? `${h} ชม. ${m} นาที` : `${h} ชม.`;
};

// =====================================================
// แบบทดสอบ
// =====================================================

/** ตัวเลือกนี้ใช้ได้กับคำถามนี้ไหม */
export const isValidChoice = (question: Pick<QuizQuestion, 'choices'>, choiceIndex: unknown): choiceIndex is number =>
  typeof choiceIndex === 'number' && Number.isInteger(choiceIndex) && choiceIndex >= 0 && choiceIndex < question.choices.length;

export interface AnswerCheck {
  ok: boolean;
  /** ลำดับข้อ (เริ่ม 1) ที่ยังไม่ได้ตอบ */
  missingNumbers: number[];
  /** index ของข้อแรกที่ยังไม่ได้ตอบ (-1 = ครบ) */
  firstMissingIndex: number;
}

/** ตรวจว่าตอบครบทุกข้อด้วยตัวเลือกที่ถูกต้องหรือยัง */
export const checkAnswers = (questions: readonly QuizQuestion[], answers: QuizAnswers): AnswerCheck => {
  const missing: number[] = [];
  questions.forEach((q, i) => {
    if (!isValidChoice(q, answers[q.id])) missing.push(i + 1);
  });
  return { ok: missing.length === 0 && questions.length > 0, missingNumbers: missing, firstMissingIndex: missing.length ? missing[0] - 1 : -1 };
};

/** ข้อความเตือนตอบไม่ครบ (ไทย) */
export const missingAnswersMessage = (missingNumbers: readonly number[]): string => {
  if (missingNumbers.length === 0) return '';
  if (missingNumbers.length === 1) return `ยังไม่ได้ตอบข้อ ${missingNumbers[0]} เลือกคำตอบก่อนส่งนะ`;
  const shown = missingNumbers.slice(0, 5).join(', ');
  const more = missingNumbers.length > 5 ? ' …' : '';
  return `ยังไม่ได้ตอบอีก ${missingNumbers.length} ข้อ (ข้อ ${shown}${more}) เลือกคำตอบให้ครบก่อนส่งนะ`;
};

/** จำนวนข้อที่ตอบแล้ว */
export const answeredCount = (questions: readonly QuizQuestion[], answers: QuizAnswers): number =>
  questions.filter((q) => isValidChoice(q, answers[q.id])).length;

// =====================================================
// หลักสูตร
// =====================================================

/** คะแนนความประพฤติที่จะได้ เช่น "+10 คะแนนความประพฤติ" (0 = ว่าง) */
export const pointsLabel = (points: number): string =>
  Number.isFinite(points) && points > 0 ? `+${Math.round(points)} คะแนนความประพฤติ` : '';

/** เรียงหลักสูตร: บทบาทของผู้ใช้ขึ้นก่อน แล้วตามลำดับ ร้าน → ไรเดอร์ → ผู้ซื้อ (ไม่รู้จักบทบาท = ท้ายสุด) */
export const orderCourses = <T extends Pick<ClassroomCourse, 'role' | 'id'>>(courses: readonly T[], myRoles: readonly TrainingRole[]): T[] => {
  const rank = (c: T): number => {
    const base = c.role ? TRAINING_ROLES.indexOf(c.role) : 99;
    const mine = c.role && myRoles.includes(c.role) ? 0 : 10;
    return mine + base;
  };
  return [...courses].sort((a, b) => rank(a) - rank(b) || a.id - b.id);
};

export type CourseStatus = 'certified' | 'needs_refresh' | 'quiz_ready' | 'in_progress' | 'not_started';

/** สถานะหลักสูตรสำหรับป้าย/ปุ่มบนการ์ด */
export const courseStatus = (
  course: Pick<ClassroomCourse, 'certified' | 'needs_refresh' | 'progress_pct' | 'completed_lessons' | 'lesson_count'>
): CourseStatus => {
  if (course.certified && course.needs_refresh) return 'needs_refresh';
  if (course.certified) return 'certified';
  const pct = courseProgressPct(course);
  if (pct >= 100 || (course.lesson_count > 0 && course.completed_lessons >= course.lesson_count)) return 'quiz_ready';
  return pct > 0 || course.completed_lessons > 0 ? 'in_progress' : 'not_started';
};

/** ป้ายสถานะภาษาไทย + โทนสี */
export const COURSE_STATUS_LOOK: Record<CourseStatus, { label: string; tone: 'success' | 'warning' | 'gold' | 'info' | 'neutral' }> = {
  certified: { label: 'ผ่านการอบรมแล้ว', tone: 'success' },
  needs_refresh: { label: 'ต้องอบรมเพิ่ม', tone: 'warning' },
  quiz_ready: { label: 'พร้อมทำแบบทดสอบ', tone: 'gold' },
  in_progress: { label: 'กำลังเรียน', tone: 'info' },
  not_started: { label: 'ยังไม่เริ่ม', tone: 'neutral' },
};
