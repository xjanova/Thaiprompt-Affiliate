/**
 * Classroom API — ห้องเรียน (อบรมก่อนรับป้าย "ผ่านการอบรม") ตามสัญญา CONTRACT.md 2026-10-13
 *
 * prefix /api/v1/classroom · ล็อกอินแล้วเท่านั้น (sanctum) · ข้อความไทยจาก server
 *   GET  /classroom/courses                     → รายการหลักสูตร (ร้าน / ไรเดอร์ / ผู้ซื้อ)
 *   GET  /classroom/courses/{id}                → รายละเอียด + บทเรียน + สถานะแบบทดสอบ
 *   POST /classroom/lessons/{id}/progress       → heartbeat ทุก 10 วิระหว่างเล่น {position_seconds, playing}
 *   POST /classroom/courses/{id}/quiz/start     → 423 QUIZ_LOCKED · 429 QUIZ_COOLDOWN (data.retry_available_at)
 *   POST /classroom/attempts/{id}/submit        → ผลสอบ + certification (ผ่าน) · ATTEMPT_EXPIRED (หมดเวลา)
 *
 * การอบรมไม่บังคับ (ไม่มีด่าน) — server รุ่นเก่าที่ยังไม่มีห้องเรียน = 404 → หน้าจอแสดง "ยังไม่เปิดให้บริการ"
 * ทุกฟิลด์ผ่านตัวแปลง (normalize*) ก่อนถึงหน้าจอ: ค่าหาย/ชนิดแปลก = ค่าปลอดภัย ไม่ทำแอปล้ม
 */

import { apiGet, apiPost, num, type ApiResult } from './client';
import { isTruthyFlag, parseTrainingRole, type TrainingRole } from '@/components/badges/trainingBadge';

// =====================================================
// ชนิดข้อมูล
// =====================================================

export interface ClassroomCourse {
  id: number;
  /** null = บทบาทที่แอปรุ่นนี้ไม่รู้จัก (ยังแสดงได้ แต่ไม่จัดลำดับ) */
  role: TrainingRole | null;
  title: string;
  description: string | null;
  lesson_count: number;
  total_seconds: number;
  completed_lessons: number;
  /** 0–100 */
  progress_pct: number;
  question_count: number;
  pass_score: number;
  /** คะแนนความประพฤติที่ได้เมื่อผ่าน (ร้าน/ไรเดอร์ 10 · ผู้ซื้อ 5) */
  points_reward: number;
  certified: boolean;
  certified_at: string | null;
  /** ผ่านกฎรุ่นเก่า ต้องอบรมเพิ่ม */
  needs_refresh: boolean;
}

export interface ClassroomLesson {
  id: number;
  title: string;
  /** รหัสวิดีโอ YouTube 11 ตัว (ไม่ถูกรูปแบบ = null → เล่นไม่ได้) */
  youtube_id: string | null;
  duration_seconds: number;
  watched_seconds: number;
  completed: boolean;
  /** ตำแหน่งล่าสุดที่ server บันทึกไว้ (วินาที) — server รุ่นเก่าไม่ส่ง = undefined → ใช้ watched_seconds แทน */
  last_position_seconds?: number;
}

export interface ClassroomAttemptSummary {
  score: number;
  total: number;
  passed: boolean;
  submitted_at: string | null;
}

export interface ClassroomCourseDetail extends ClassroomCourse {
  lessons: ClassroomLesson[];
  quiz_unlocked: boolean;
  last_attempt: ClassroomAttemptSummary | null;
  /** ทำแบบทดสอบใหม่ได้ตั้งแต่เวลานี้ (null = ได้เลย) */
  retry_available_at: string | null;
}

export interface LessonProgressResult {
  watched_seconds: number;
  completed: boolean;
  course_quiz_unlocked: boolean;
}

export interface QuizQuestion {
  id: number;
  question: string;
  choices: string[];
}

export interface QuizStart {
  attempt_id: number;
  expires_at: string | null;
  questions: QuizQuestion[];
}

export interface QuizResultItem {
  question_id: number;
  correct: boolean;
  correct_index: number | null;
  explanation: string | null;
}

export interface QuizCertification {
  role: TrainingRole | null;
  certified_at: string | null;
  points_awarded: number;
}

export interface QuizSubmitResult {
  score: number;
  total: number;
  pass_score: number;
  passed: boolean;
  results: QuizResultItem[];
  certification: QuizCertification | null;
  retry_available_at: string | null;
}

/** คำตอบ: { question_id: choice_index } */
export type QuizAnswers = Record<number, number>;

// =====================================================
// ตัวแปลงค่า (export ให้เทสต์)
// =====================================================

const YOUTUBE_ID = /^[A-Za-z0-9_-]{11}$/;

const str = (value: unknown): string | null =>
  typeof value === 'string' && value.trim().length > 0 ? value.trim() : null;

const isoOrNull = (value: unknown): string | null => {
  const s = str(value);
  return s && Number.isFinite(Date.parse(s)) ? s : null;
};

const nonNeg = (value: unknown, fallback: number = 0): number => Math.max(0, num(value, fallback));

/** ตัวเลขที่ server อาจไม่ส่งมา (null/undefined/ไม่ใช่ตัวเลข = NaN) */
const optNum = (value: unknown): number =>
  value === null || value === undefined || value === '' ? Number.NaN : num(value, Number.NaN);

const clampPct = (value: number): number => Math.min(100, Math.max(0, value));

/** รหัส YouTube ที่ปลอดภัยต่อการใส่ในหน้าเล่นวิดีโอ (11 ตัว A-Z a-z 0-9 _ -) */
export const safeYoutubeId = (value: unknown): string | null => {
  const s = str(value);
  return s && YOUTUBE_ID.test(s) ? s : null;
};

export const normalizeLesson = (raw: any): ClassroomLesson => {
  const duration = nonNeg(raw?.duration_seconds);
  const lesson: ClassroomLesson = {
    id: num(raw?.id),
    title: str(raw?.title) ?? 'บทเรียน',
    youtube_id: safeYoutubeId(raw?.youtube_id),
    duration_seconds: duration,
    watched_seconds: nonNeg(raw?.watched_seconds),
    completed: isTruthyFlag(raw?.completed),
  };
  const lastPos = optNum(raw?.last_position_seconds);
  if (Number.isFinite(lastPos)) lesson.last_position_seconds = Math.max(0, lastPos);
  return lesson;
};

export const normalizeCourse = (raw: any): ClassroomCourse => {
  const lessonCount = nonNeg(raw?.lesson_count);
  const completed = Math.min(nonNeg(raw?.completed_lessons), lessonCount || Number.MAX_SAFE_INTEGER);
  const pctRaw = optNum(raw?.progress_pct);
  const pct = Number.isFinite(pctRaw) ? pctRaw : lessonCount > 0 ? (completed / lessonCount) * 100 : 0;
  return {
    id: num(raw?.id),
    role: parseTrainingRole(raw?.role),
    title: str(raw?.title) ?? 'หลักสูตรอบรม',
    description: str(raw?.description),
    lesson_count: lessonCount,
    total_seconds: nonNeg(raw?.total_seconds),
    completed_lessons: completed,
    progress_pct: clampPct(Math.round(pct)),
    question_count: nonNeg(raw?.question_count),
    pass_score: nonNeg(raw?.pass_score),
    points_reward: nonNeg(raw?.points_reward),
    certified: isTruthyFlag(raw?.certified),
    certified_at: isoOrNull(raw?.certified_at),
    needs_refresh: isTruthyFlag(raw?.needs_refresh),
  };
};

export const normalizeAttemptSummary = (raw: any): ClassroomAttemptSummary | null => {
  if (!raw || typeof raw !== 'object') return null;
  return {
    score: nonNeg(raw.score),
    total: nonNeg(raw.total),
    passed: isTruthyFlag(raw.passed),
    submitted_at: isoOrNull(raw.submitted_at),
  };
};

export const normalizeCourseDetail = (raw: any): ClassroomCourseDetail => {
  const lessons: ClassroomLesson[] = (Array.isArray(raw?.lessons) ? raw.lessons : [])
    .map(normalizeLesson)
    .filter((l: ClassroomLesson) => l.id > 0);
  const base = normalizeCourse(raw);
  // รายละเอียดหลักสูตรอาจไม่ส่งตัวนับมา → นับจากบทเรียนเอง
  const lessonCount = base.lesson_count > 0 ? base.lesson_count : lessons.length;
  const completed = Number.isFinite(optNum(raw?.completed_lessons))
    ? nonNeg(raw?.completed_lessons)
    : lessons.filter((l) => l.completed).length;
  const totalSeconds = base.total_seconds > 0 ? base.total_seconds : lessons.reduce((s, l) => s + l.duration_seconds, 0);
  const pct =
    Number.isFinite(optNum(raw?.progress_pct))
      ? base.progress_pct
      : lessonCount > 0
        ? clampPct(Math.round((completed / lessonCount) * 100))
        : 0;
  return {
    ...base,
    lesson_count: lessonCount,
    completed_lessons: Math.min(completed, lessonCount),
    total_seconds: totalSeconds,
    progress_pct: pct,
    lessons,
    quiz_unlocked: isTruthyFlag(raw?.quiz_unlocked),
    last_attempt: normalizeAttemptSummary(raw?.last_attempt),
    retry_available_at: isoOrNull(raw?.retry_available_at),
  };
};

export const normalizeProgress = (raw: any): LessonProgressResult => ({
  watched_seconds: nonNeg(raw?.watched_seconds),
  completed: isTruthyFlag(raw?.completed),
  course_quiz_unlocked: isTruthyFlag(raw?.course_quiz_unlocked),
});

export const normalizeQuestion = (raw: any): QuizQuestion => ({
  id: num(raw?.id),
  question: str(raw?.question) ?? '',
  choices: (Array.isArray(raw?.choices) ? raw.choices : []).map((c: unknown) =>
    typeof c === 'string' ? c : c === null || c === undefined ? '' : String(c)
  ),
});

export const normalizeQuizStart = (raw: any): QuizStart => ({
  attempt_id: num(raw?.attempt_id),
  expires_at: isoOrNull(raw?.expires_at),
  questions: (Array.isArray(raw?.questions) ? raw.questions : [])
    .map(normalizeQuestion)
    .filter((q: QuizQuestion) => q.id > 0 && q.question.length > 0 && q.choices.length >= 2),
});

export const normalizeSubmitResult = (raw: any): QuizSubmitResult => {
  const cert = raw?.certification;
  return {
    score: nonNeg(raw?.score),
    total: nonNeg(raw?.total),
    pass_score: nonNeg(raw?.pass_score),
    passed: isTruthyFlag(raw?.passed),
    results: (Array.isArray(raw?.results) ? raw.results : [])
      .map((r: any) => {
        const idx = num(r?.correct_index, Number.NaN);
        return {
          question_id: num(r?.question_id),
          correct: isTruthyFlag(r?.correct),
          correct_index: Number.isInteger(idx) && idx >= 0 ? idx : null,
          explanation: str(r?.explanation),
        };
      })
      .filter((r: QuizResultItem) => r.question_id > 0),
    certification:
      cert && typeof cert === 'object'
        ? {
            role: parseTrainingRole(cert.role),
            certified_at: isoOrNull(cert.certified_at),
            points_awarded: nonNeg(cert.points_awarded),
          }
        : null,
    retry_available_at: isoOrNull(raw?.retry_available_at),
  };
};

/** แปลงผล ApiResult ด้วยตัวแปลง (ล้มเหลวคืนตามเดิม) */
const mapResult = <T>(result: ApiResult<any>, mapper: (raw: any) => T): ApiResult<T> =>
  result.success ? { ...result, data: mapper(result.data) } : result;

// =====================================================
// เรียก API
// =====================================================

const BASE = '/classroom';

/** GET /classroom/courses */
export const getClassroomCourses = async (): Promise<ApiResult<ClassroomCourse[]>> =>
  mapResult(await apiGet<any>(`${BASE}/courses`), (raw) =>
    (Array.isArray(raw) ? raw : Array.isArray(raw?.courses) ? raw.courses : [])
      .map(normalizeCourse)
      .filter((c: ClassroomCourse) => c.id > 0)
  );

/** GET /classroom/courses/{id} */
export const getClassroomCourse = async (courseId: number): Promise<ApiResult<ClassroomCourseDetail>> =>
  mapResult(await apiGet<any>(`${BASE}/courses/${courseId}`), normalizeCourseDetail);

/**
 * POST /classroom/lessons/{id}/progress — heartbeat ระหว่างเล่น
 * server นับเวลาดูไม่เกินเวลาจริงที่ผ่านไป (+3 วิ) ข้ามไปข้างหน้าไม่ได้เครดิต
 */
export const postLessonProgress = async (
  lessonId: number,
  body: { position_seconds: number; playing: boolean }
): Promise<ApiResult<LessonProgressResult>> =>
  mapResult(
    await apiPost<any>(
      `${BASE}/lessons/${lessonId}/progress`,
      { position_seconds: Math.max(0, Math.floor(body.position_seconds)), playing: body.playing },
      // heartbeat ต้องเบา — หมดเวลาเร็ว แล้วรอบถัดไปค่อยส่งใหม่
      { timeout: 8000 }
    ),
    normalizeProgress
  );

/** POST /classroom/courses/{id}/quiz/start — 423 QUIZ_LOCKED · 429 QUIZ_COOLDOWN (data.retry_available_at) */
export const startClassroomQuiz = async (courseId: number): Promise<ApiResult<QuizStart>> =>
  mapResult(await apiPost<any>(`${BASE}/courses/${courseId}/quiz/start`, {}), normalizeQuizStart);

/** POST /classroom/attempts/{id}/submit {answers: {"<question_id>": <choice_index>}} */
export const submitClassroomQuiz = async (attemptId: number, answers: QuizAnswers): Promise<ApiResult<QuizSubmitResult>> => {
  const payload: Record<string, number> = {};
  for (const [qid, idx] of Object.entries(answers)) {
    payload[String(qid)] = idx;
  }
  return mapResult(await apiPost<any>(`${BASE}/attempts/${attemptId}/submit`, { answers: payload }), normalizeSubmitResult);
};

/** เวลาที่ทำแบบทดสอบใหม่ได้จาก error QUIZ_COOLDOWN (data.retry_available_at) */
export const cooldownFromFailure = (failure: { data?: any } | null | undefined): string | null =>
  isoOrNull(failure?.data?.retry_available_at);
