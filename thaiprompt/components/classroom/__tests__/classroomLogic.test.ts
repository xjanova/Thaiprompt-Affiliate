/**
 * ทดสอบตรรกะห้องเรียน — ความคืบหน้า · สถานะบทเรียน · เวลารอ · ตรวจคำตอบ · ลำดับหลักสูตร
 *
 * รัน: npx jest components/classroom/__tests__/classroomLogic.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import {
  allLessonsDone,
  answeredCount,
  checkAnswers,
  cooldownRemainingMs,
  courseProgressPct,
  courseStatus,
  formatClock,
  formatCountdown,
  formatThaiDuration,
  isLessonPlayable,
  isValidChoice,
  lessonStates,
  lessonWatchPct,
  missingAnswersMessage,
  nextLessonOf,
  orderCourses,
  playerWindow,
  pointsLabel,
} from '../classroomLogic';
import type { QuizQuestion } from '@/services/api/classroomApi';

describe('lessonWatchPct', () => {
  it('คิดเป็น % ปัดเศษ ไม่เกิน 100', () => {
    expect(lessonWatchPct(30, 120)).toBe(25);
    expect(lessonWatchPct(500, 120)).toBe(100);
  });
  it('ความยาว 0/ค่าแปลก = 0', () => {
    expect(lessonWatchPct(30, 0)).toBe(0);
    expect(lessonWatchPct(Number.NaN, 100)).toBe(0);
    expect(lessonWatchPct(-5, 100)).toBe(0);
  });
});

describe('courseProgressPct', () => {
  it('ใช้ progress_pct ของ server', () => {
    expect(courseProgressPct({ progress_pct: 67, completed_lessons: 2, lesson_count: 3 })).toBe(67);
  });
  it('server ส่ง 0 แต่มีบทที่จบ → นับจากบท', () => {
    expect(courseProgressPct({ progress_pct: 0, completed_lessons: 1, lesson_count: 4 })).toBe(25);
  });
  it('ไม่มีบท = 0', () => {
    expect(courseProgressPct({ progress_pct: 0, completed_lessons: 0, lesson_count: 0 })).toBe(0);
  });
});

describe('lessonStates (เรียนตามลำดับ)', () => {
  it('จบแล้ว = done · บทแรกที่ยังไม่จบ = current · ที่เหลือ = locked', () => {
    expect(lessonStates([{ completed: true }, { completed: false }, { completed: false }])).toEqual([
      'done',
      'current',
      'locked',
    ]);
  });
  it('บทที่จบแล้วหลังบท current ยังดูซ้ำได้', () => {
    expect(lessonStates([{ completed: false }, { completed: true }])).toEqual(['current', 'done']);
  });
  it('isLessonPlayable / nextLessonOf / allLessonsDone', () => {
    const lessons = [
      { id: 11, completed: true },
      { id: 12, completed: false },
      { id: 13, completed: false },
    ];
    expect(isLessonPlayable(lessons, 12)).toBe(true);
    expect(isLessonPlayable(lessons, 13)).toBe(false);
    expect(isLessonPlayable(lessons, 99)).toBe(false);
    expect(nextLessonOf(lessons, 12)?.id).toBe(13);
    expect(nextLessonOf(lessons, 13)).toBeNull();
    expect(allLessonsDone(lessons)).toBe(false);
    expect(allLessonsDone([])).toBe(false);
    expect(allLessonsDone([{ completed: true }])).toBe(true);
  });
});

describe('playerWindow', () => {
  it('ยังไม่จบ: เล่นต่อจากที่ดู (ถอย 3 วิ) เลื่อนได้ไม่เกินที่ดูแล้ว', () => {
    expect(playerWindow({ watched_seconds: 100, duration_seconds: 300, completed: false })).toEqual({ start: 97, maxAllowed: 100 });
  });
  it('ดูเกือบจบ: ไม่เริ่มที่ท้ายคลิปพอดี', () => {
    expect(playerWindow({ watched_seconds: 299, duration_seconds: 300, completed: false })).toEqual({ start: 292, maxAllowed: 299 });
  });
  it('ดูจบแล้ว: เริ่มใหม่ เลื่อนได้ทั้งคลิป', () => {
    expect(playerWindow({ watched_seconds: 280, duration_seconds: 300, completed: true })).toEqual({ start: 0, maxAllowed: 300 });
  });
  it('ยังไม่เคยดู', () => {
    expect(playerWindow({ watched_seconds: 0, duration_seconds: 300, completed: false })).toEqual({ start: 0, maxAllowed: 0 });
  });
  it('มี last_position_seconds: เล่นต่อจากตำแหน่งล่าสุดของ server (ย้อนไปทบทวนไว้)', () => {
    expect(
      playerWindow({ watched_seconds: 200, duration_seconds: 300, completed: false, last_position_seconds: 120 })
    ).toEqual({ start: 117, maxAllowed: 200 });
  });
  it('last_position_seconds = 0 → ใช้ watched_seconds', () => {
    expect(
      playerWindow({ watched_seconds: 100, duration_seconds: 300, completed: false, last_position_seconds: 0 })
    ).toEqual({ start: 97, maxAllowed: 100 });
  });
});

describe('เวลา', () => {
  const NOW = Date.parse('2026-10-13T10:00:00.000Z');
  it('cooldownRemainingMs: เหลือเวลา / เลยแล้ว / ไม่มีค่า', () => {
    expect(cooldownRemainingMs('2026-10-13T10:00:45.000Z', 0, NOW)).toBe(45_000);
    expect(cooldownRemainingMs('2026-10-13T09:59:00.000Z', 0, NOW)).toBe(0);
    expect(cooldownRemainingMs(null, 0, NOW)).toBe(0);
    expect(cooldownRemainingMs('ไม่ใช่วันที่', 0, NOW)).toBe(0);
  });
  it('cooldownRemainingMs: แก้นาฬิกาเครื่องเพี้ยนด้วย offset', () => {
    // เครื่องช้ากว่า server 10 วิ (offset +10000) → เหลือน้อยลง 10 วิ
    expect(cooldownRemainingMs('2026-10-13T10:00:45.000Z', 10_000, NOW)).toBe(35_000);
  });
  it('formatCountdown', () => {
    expect(formatCountdown(45_000)).toBe('0:45');
    expect(formatCountdown(61_500)).toBe('1:02');
    expect(formatCountdown(3_725_000)).toBe('1:02:05');
    expect(formatCountdown(-1)).toBe('0:00');
  });
  it('formatClock', () => {
    expect(formatClock(0)).toBe('0:00');
    expect(formatClock(83.7)).toBe('1:23');
  });
  it('formatThaiDuration', () => {
    expect(formatThaiDuration(45)).toBe('45 วินาที');
    expect(formatThaiDuration(720)).toBe('12 นาที');
    expect(formatThaiDuration(3900)).toBe('1 ชม. 5 นาที');
    expect(formatThaiDuration(7200)).toBe('2 ชม.');
  });
});

describe('แบบทดสอบ', () => {
  const qs: QuizQuestion[] = [
    { id: 5, question: 'ข้อหนึ่ง', choices: ['ก', 'ข'] },
    { id: 9, question: 'ข้อสอง', choices: ['ก', 'ข', 'ค'] },
    { id: 2, question: 'ข้อสาม', choices: ['ก', 'ข', 'ค', 'ง'] },
  ];

  it('isValidChoice: ต้องเป็นจำนวนเต็มในช่วงตัวเลือก', () => {
    expect(isValidChoice(qs[0], 1)).toBe(true);
    expect(isValidChoice(qs[0], 2)).toBe(false);
    expect(isValidChoice(qs[0], -1)).toBe(false);
    expect(isValidChoice(qs[0], 0.5)).toBe(false);
    expect(isValidChoice(qs[0], undefined)).toBe(false);
  });

  it('checkAnswers: ตอบครบ', () => {
    expect(checkAnswers(qs, { 5: 0, 9: 2, 2: 3 })).toEqual({ ok: true, missingNumbers: [], firstMissingIndex: -1 });
  });

  it('checkAnswers: ขาดข้อ/ตัวเลือกเกินช่วง = ยังไม่ครบ (บอกลำดับข้อเริ่ม 1)', () => {
    expect(checkAnswers(qs, { 5: 0, 9: 7 })).toEqual({ ok: false, missingNumbers: [2, 3], firstMissingIndex: 1 });
  });

  it('checkAnswers: ไม่มีคำถาม = ส่งไม่ได้', () => {
    expect(checkAnswers([], {}).ok).toBe(false);
  });

  it('answeredCount', () => {
    expect(answeredCount(qs, { 5: 1, 2: 9 })).toBe(1);
  });

  it('missingAnswersMessage', () => {
    expect(missingAnswersMessage([])).toBe('');
    expect(missingAnswersMessage([3])).toBe('ยังไม่ได้ตอบข้อ 3 เลือกคำตอบก่อนส่งนะ');
    expect(missingAnswersMessage([2, 3])).toBe('ยังไม่ได้ตอบอีก 2 ข้อ (ข้อ 2, 3) เลือกคำตอบให้ครบก่อนส่งนะ');
    expect(missingAnswersMessage([1, 2, 3, 4, 5, 6, 7])).toBe(
      'ยังไม่ได้ตอบอีก 7 ข้อ (ข้อ 1, 2, 3, 4, 5 …) เลือกคำตอบให้ครบก่อนส่งนะ'
    );
  });
});

describe('หลักสูตร', () => {
  it('pointsLabel', () => {
    expect(pointsLabel(10)).toBe('+10 คะแนนความประพฤติ');
    expect(pointsLabel(5)).toBe('+5 คะแนนความประพฤติ');
    expect(pointsLabel(0)).toBe('');
  });

  it('orderCourses: บทบาทของผู้ใช้ขึ้นก่อน แล้วตามลำดับมาตรฐาน · บทบาทไม่รู้จักท้ายสุด', () => {
    const courses = [
      { id: 1, role: 'buyer' as const },
      { id: 2, role: null },
      { id: 3, role: 'seller' as const },
      { id: 4, role: 'rider' as const },
    ];
    expect(orderCourses(courses, []).map((c) => c.id)).toEqual([3, 4, 1, 2]);
    expect(orderCourses(courses, ['rider']).map((c) => c.id)).toEqual([4, 3, 1, 2]);
    expect(orderCourses(courses, ['rider', 'seller']).map((c) => c.id)).toEqual([3, 4, 1, 2]);
  });

  it('courseStatus', () => {
    const base = { certified: false, needs_refresh: false, progress_pct: 0, completed_lessons: 0, lesson_count: 3 };
    expect(courseStatus(base)).toBe('not_started');
    expect(courseStatus({ ...base, progress_pct: 30, completed_lessons: 1 })).toBe('in_progress');
    expect(courseStatus({ ...base, progress_pct: 100, completed_lessons: 3 })).toBe('quiz_ready');
    expect(courseStatus({ ...base, certified: true, progress_pct: 100 })).toBe('certified');
    expect(courseStatus({ ...base, certified: true, needs_refresh: true })).toBe('needs_refresh');
  });
});
