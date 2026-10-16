/**
 * ทดสอบตัวแปลงข้อมูลห้องเรียน (สัญญา CONTRACT.md 2026-10-13) + หน้าเล่นวิดีโอ
 *
 * รัน: npx jest services/api/__tests__/classroomApi.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import {
  cooldownFromFailure,
  normalizeCourse,
  normalizeCourseDetail,
  normalizeProgress,
  normalizeQuizStart,
  normalizeSubmitResult,
  safeYoutubeId,
} from '../classroomApi';
import { buildPlayerHtml, parsePlayerMessage, playerCommand, playerErrorMessage } from '@/components/classroom/playerHtml';

describe('normalizeCourse', () => {
  it('รูปแบบตามสัญญา', () => {
    expect(
      normalizeCourse({
        id: 3,
        role: 'rider',
        title: 'อบรมไรเดอร์',
        description: 'กติกาการส่งของ',
        lesson_count: 4,
        total_seconds: 1500,
        completed_lessons: 2,
        progress_pct: 50,
        question_count: 15,
        pass_score: 13,
        points_reward: 10,
        certified: false,
        certified_at: null,
        needs_refresh: false,
      })
    ).toEqual({
      id: 3,
      role: 'rider',
      title: 'อบรมไรเดอร์',
      description: 'กติกาการส่งของ',
      lesson_count: 4,
      total_seconds: 1500,
      completed_lessons: 2,
      progress_pct: 50,
      question_count: 15,
      pass_score: 13,
      points_reward: 10,
      certified: false,
      certified_at: null,
      needs_refresh: false,
    });
  });

  it('ค่าหาย/ชนิดแปลก → ค่าปลอดภัย', () => {
    const c = normalizeCourse({ id: '7', role: 'alien', lesson_count: '3', completed_lessons: 9, progress_pct: null, certified: 1 });
    expect(c.id).toBe(7);
    expect(c.role).toBeNull();
    expect(c.title).toBe('หลักสูตรอบรม');
    // บทที่จบไม่เกินจำนวนบท · ไม่มี progress_pct → คิดจากบท
    expect(c.completed_lessons).toBe(3);
    expect(c.progress_pct).toBe(100);
    expect(c.certified).toBe(true);
    expect(c.certified_at).toBeNull();
  });

  it('progress_pct นอกช่วง → บีบเป็น 0–100', () => {
    expect(normalizeCourse({ id: 1, progress_pct: 140 }).progress_pct).toBe(100);
    expect(normalizeCourse({ id: 1, progress_pct: -3 }).progress_pct).toBe(0);
  });
});

describe('normalizeCourseDetail', () => {
  it('บทเรียน + สถานะแบบทดสอบ + นับเองเมื่อ server ไม่ส่งตัวนับ', () => {
    const d = normalizeCourseDetail({
      id: 2,
      role: 'seller',
      title: 'อบรมร้านค้า',
      lessons: [
        { id: 21, title: 'บทที่ 1', youtube_id: 'dQw4w9WgXcQ', duration_seconds: 300, watched_seconds: 290, completed: true },
        { id: 22, title: 'บทที่ 2', youtube_id: 'https://youtu.be/x', duration_seconds: 200, watched_seconds: 10, completed: false },
        { id: 0, title: 'ไม่มี id' },
      ],
      quiz_unlocked: false,
      last_attempt: { score: 10, total: 15, passed: false, submitted_at: '2026-10-13T09:00:00+07:00' },
      retry_available_at: '2026-10-13T09:01:00+07:00',
    });
    expect(d.lessons.map((l) => l.id)).toEqual([21, 22]);
    expect(d.lessons[0].youtube_id).toBe('dQw4w9WgXcQ');
    // URL ทั้งเส้น/รหัสผิดรูปแบบ = เล่นไม่ได้ (ห้ามใส่ลงหน้า HTML)
    expect(d.lessons[1].youtube_id).toBeNull();
    expect(d.lesson_count).toBe(2);
    expect(d.completed_lessons).toBe(1);
    expect(d.total_seconds).toBe(500);
    expect(d.progress_pct).toBe(50);
    expect(d.quiz_unlocked).toBe(false);
    expect(d.last_attempt).toEqual({ score: 10, total: 15, passed: false, submitted_at: '2026-10-13T09:00:00+07:00' });
    expect(d.retry_available_at).toBe('2026-10-13T09:01:00+07:00');
  });

  it('ไม่มีบทเรียน/last_attempt = ค่าว่างปลอดภัย', () => {
    const d = normalizeCourseDetail({ id: 5 });
    expect(d.lessons).toEqual([]);
    expect(d.last_attempt).toBeNull();
    expect(d.retry_available_at).toBeNull();
    expect(d.progress_pct).toBe(0);
  });
});

describe('normalizeProgress / normalizeQuizStart / normalizeSubmitResult', () => {
  it('progress', () => {
    expect(normalizeProgress({ watched_seconds: 120, completed: true, course_quiz_unlocked: 1 })).toEqual({
      watched_seconds: 120,
      completed: true,
      course_quiz_unlocked: true,
    });
    expect(normalizeProgress(null)).toEqual({ watched_seconds: 0, completed: false, course_quiz_unlocked: false });
  });

  it('quiz start: ตัดคำถามที่ใช้ไม่ได้ (ไม่มีข้อความ/ตัวเลือกไม่ถึง 2)', () => {
    const s = normalizeQuizStart({
      attempt_id: 44,
      expires_at: '2026-10-13T10:30:00+07:00',
      questions: [
        { id: 1, question: 'ถามหนึ่ง', choices: ['ก', 'ข'] },
        { id: 2, question: '', choices: ['ก', 'ข'] },
        { id: 3, question: 'ถามสาม', choices: ['ก'] },
        { id: 4, question: 'ถามสี่', choices: ['ก', 2, null] },
      ],
    });
    expect(s.attempt_id).toBe(44);
    expect(s.questions.map((q) => q.id)).toEqual([1, 4]);
    expect(s.questions[1].choices).toEqual(['ก', '2', '']);
  });

  it('submit result + certification', () => {
    const r = normalizeSubmitResult({
      score: 14,
      total: 15,
      pass_score: 13,
      passed: true,
      results: [
        { question_id: 1, correct: true, correct_index: 0, explanation: null },
        { question_id: 2, correct: false, correct_index: 2, explanation: 'เพราะต้องถ่ายรูปก่อน' },
        { question_id: 3, correct: false, correct_index: -1 },
      ],
      certification: { role: 'rider', certified_at: '2026-10-13T10:00:00+07:00', points_awarded: 10 },
      retry_available_at: null,
    });
    expect(r.passed).toBe(true);
    expect(r.results[1]).toEqual({ question_id: 2, correct: false, correct_index: 2, explanation: 'เพราะต้องถ่ายรูปก่อน' });
    expect(r.results[2].correct_index).toBeNull();
    expect(r.certification).toEqual({ role: 'rider', certified_at: '2026-10-13T10:00:00+07:00', points_awarded: 10 });
  });

  it('ไม่ผ่าน = certification null', () => {
    expect(normalizeSubmitResult({ score: 3, total: 5, passed: false, certification: null }).certification).toBeNull();
  });

  it('cooldownFromFailure อ่าน data.retry_available_at ของ QUIZ_COOLDOWN', () => {
    expect(cooldownFromFailure({ data: { retry_available_at: '2026-10-13T10:01:00+07:00' } })).toBe('2026-10-13T10:01:00+07:00');
    expect(cooldownFromFailure({ data: null })).toBeNull();
    expect(cooldownFromFailure(null)).toBeNull();
  });
});

describe('safeYoutubeId', () => {
  it('รับเฉพาะรหัส 11 ตัว', () => {
    expect(safeYoutubeId('dQw4w9WgXcQ')).toBe('dQw4w9WgXcQ');
    expect(safeYoutubeId('abc')).toBeNull();
    expect(safeYoutubeId('dQw4w9WgXc"')).toBeNull();
    expect(safeYoutubeId('</script><x')).toBeNull();
    expect(safeYoutubeId(12345678901)).toBeNull();
  });
});

describe('หน้าเล่นวิดีโอ (playerHtml)', () => {
  it('รหัสไม่ถูกต้อง = ไม่สร้างหน้า', () => {
    expect(buildPlayerHtml({ videoId: 'bad', startSeconds: 0, maxAllowedSeconds: 0, origin: 'https://main.thaiprompt.online' })).toBeNull();
  });

  it('ใส่ค่าแบบ JSON + origin https + ไม่มีแถบเลื่อนของ YouTube', () => {
    const html = buildPlayerHtml({
      videoId: 'dQw4w9WgXcQ',
      startSeconds: 97.6,
      maxAllowedSeconds: 100,
      origin: 'https://main.thaiprompt.online',
    }) as string;
    expect(html).toContain('"videoId":"dQw4w9WgXcQ"');
    expect(html).toContain('"start":97');
    expect(html).toContain('"maxAllowed":100');
    expect(html).toContain('"origin":"https://main.thaiprompt.online"');
    expect(html).toContain('controls:0');
    expect(html).toContain('playsinline:1');
  });

  it('origin ไม่ใช่ https ล้วน → ใช้เว็บหลัก', () => {
    const html = buildPlayerHtml({ videoId: 'dQw4w9WgXcQ', startSeconds: 0, maxAllowedSeconds: 0, origin: 'javascript:alert(1)' }) as string;
    expect(html).toContain('"origin":"https://main.thaiprompt.online"');
  });

  it('parsePlayerMessage: รับเฉพาะรูปแบบที่รู้จัก', () => {
    expect(parsePlayerMessage('{"t":"time","pos":12.5,"dur":300,"max":13,"s":1}')).toEqual({ t: 'time', pos: 12.5, dur: 300, max: 13, s: 1 });
    expect(parsePlayerMessage('{"t":"state","s":0}')).toEqual({ t: 'state', s: 0, pos: 0 });
    expect(parsePlayerMessage('{"t":"error","code":"yt_150"}')).toEqual({ t: 'error', code: 'yt_150' });
    expect(parsePlayerMessage('{"t":"hack"}')).toBeNull();
    expect(parsePlayerMessage('ไม่ใช่ json')).toBeNull();
    expect(parsePlayerMessage(42)).toBeNull();
  });

  it('playerCommand ส่งเป็นสตริง JSON เสมอ', () => {
    expect(playerCommand('play')).toContain('window.__lesson("play")');
  });

  it('playerErrorMessage เป็นภาษาไทยเสมอ', () => {
    for (const code of ['yt_150', 'yt_100', 'api', 'timeout', 'อื่นๆ']) {
      expect(playerErrorMessage(code)).toMatch(/[฀-๿]/);
    }
  });
});
