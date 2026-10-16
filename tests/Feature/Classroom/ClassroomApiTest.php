<?php

namespace Tests\Feature\Classroom;

use App\Events\Marketplace\TrainingCertified;
use App\Models\ClassroomAttempt;
use App\Models\ClassroomCertification;
use App\Models\ClassroomLessonProgress;
use App\Models\User;
use App\Services\Classroom\YouTubeId;
use Database\Seeders\ClassroomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Classroom\Concerns\BuildsClassroomFixtures;
use Tests\TestCase;

/**
 * 🎓 ห้องเรียน (API แอป) — ต้องใช้ MySQL
 *
 * - กันกดข้าม: กดข้ามไปข้างหน้าได้เวลาไม่เกินเวลาจริง + 3 วิ · heartbeat ค้างเกิน 30 วิไม่นับ · ย้อนกลับไม่นับ
 * - จบบทที่ 90% · สอบได้เมื่อดูครบทุกบท (QUIZ_LOCKED)
 * - ตรวจคำตอบ ผ่าน/ไม่ผ่าน · เวลารอสอบใหม่ (QUIZ_COOLDOWN) · ชุดหมดอายุ (ATTEMPT_EXPIRED) · ส่งซ้ำ (ATTEMPT_SUBMITTED)
 * - ใบรับรอง idempotent + event ครั้งเดียว · เปลี่ยนรุ่นกติกา → needs_refresh → สอบใหม่ = event คะแนน 0
 */
#[Group('classroom')]
class ClassroomApiTest extends TestCase
{
    use BuildsClassroomFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->resetClassroom();
        $this->freezeClock();
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    // =====================================================
    // นับเวลาดู (anti-skip)
    // =====================================================

    public function test_heartbeats_credit_real_watch_time(): void
    {
        $course = $this->makeCourse();
        $lesson = $this->makeLesson($course, 300);
        $user = User::factory()->create();

        $this->heartbeat($user, $lesson, 0)->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.watched_seconds', 0)
            ->assertJsonPath('data.completed', false)
            ->assertJsonPath('data.course_quiz_unlocked', false);

        $this->travel(10)->seconds();
        $this->heartbeat($user, $lesson, 10)->assertOk()->assertJsonPath('data.watched_seconds', 10);

        $this->travel(10)->seconds();
        $this->heartbeat($user, $lesson, 20)->assertOk()->assertJsonPath('data.watched_seconds', 20);
    }

    public function test_seek_forward_is_not_credited_beyond_wall_clock(): void
    {
        $course = $this->makeCourse();
        $lesson = $this->makeLesson($course, 600);
        $user = User::factory()->create();

        $this->heartbeat($user, $lesson, 0)->assertOk();
        $this->travel(10)->seconds();

        // กระโดดไปนาทีที่ 9 หลังเปิดดูจริงแค่ 10 วิ → ได้ไม่เกิน 10 + 3 วิ
        $this->heartbeat($user, $lesson, 540)->assertOk()
            ->assertJsonPath('data.watched_seconds', 13)
            ->assertJsonPath('data.completed', false);

        // ย้อนกลับ = ไม่นับ
        $this->travel(10)->seconds();
        $this->heartbeat($user, $lesson, 100)->assertOk()->assertJsonPath('data.watched_seconds', 13);
    }

    public function test_stale_heartbeat_is_not_credited(): void
    {
        $course = $this->makeCourse();
        $lesson = $this->makeLesson($course, 600);
        $user = User::factory()->create();

        $this->heartbeat($user, $lesson, 0)->assertOk();

        // หายไป 45 วิ (เกิน 30) → ช่วงนี้ไม่นับ แต่ตั้งจุดเริ่มใหม่
        $this->travel(45)->seconds();
        $this->heartbeat($user, $lesson, 45)->assertOk()->assertJsonPath('data.watched_seconds', 0);

        $this->travel(10)->seconds();
        $this->heartbeat($user, $lesson, 55)->assertOk()->assertJsonPath('data.watched_seconds', 10);
    }

    public function test_lesson_completes_at_ninety_percent_and_unlocks_quiz(): void
    {
        $course = $this->makeCourse();
        $lesson = $this->makeLesson($course, 100);
        $user = User::factory()->create();

        $this->heartbeat($user, $lesson, 0)->assertOk();
        for ($position = 10; $position <= 80; $position += 10) {
            $this->travel(10)->seconds();
            $this->heartbeat($user, $lesson, $position)->assertOk()->assertJsonPath('data.completed', false);
        }

        $this->travel(10)->seconds();
        $this->heartbeat($user, $lesson, 90)->assertOk()
            ->assertJsonPath('data.watched_seconds', 90)
            ->assertJsonPath('data.completed', true)
            ->assertJsonPath('data.course_quiz_unlocked', true);

        $this->assertNotNull(ClassroomLessonProgress::query()->where('user_id', $user->id)->value('completed_at'));
    }

    public function test_progress_validation_and_inactive_lesson(): void
    {
        $course = $this->makeCourse();
        $lesson = $this->makeLesson($course, 100);
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/classroom/lessons/'.$lesson->id.'/progress', ['position_seconds' => -1])
            ->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');

        $lesson->update(['is_active' => false]);
        $this->heartbeat($user, $lesson, 5)->assertStatus(404)->assertJsonPath('code', 'LESSON_NOT_FOUND');

        $this->postJson('/api/v1/classroom/lessons/999999/progress', ['position_seconds' => 1])
            ->assertStatus(404)->assertJsonPath('code', 'LESSON_NOT_FOUND');
    }

    // =====================================================
    // รายการคอร์ส / รายละเอียด
    // =====================================================

    public function test_course_list_and_detail_payloads(): void
    {
        $course = $this->makeCourse('seller', ['question_count' => 15, 'pass_score' => 13]);
        $first = $this->makeLesson($course, 120);
        $this->makeLesson($course, 60);
        $this->makeCourse('buyer', ['is_active' => false]);
        $user = User::factory()->create();
        $this->completeLesson($user, $first);

        Sanctum::actingAs($user);
        $list = $this->getJson('/api/v1/classroom/courses')->assertOk()->json('data');
        $this->assertCount(1, $list, 'คอร์สที่ปิดอยู่ต้องไม่แสดง');
        $this->assertSame([
            'id', 'role', 'role_label', 'title', 'description', 'rules_version', 'lesson_count', 'total_seconds',
            'completed_lessons', 'progress_pct', 'question_count', 'pass_score', 'points_reward', 'certified', 'certified_at', 'needs_refresh',
        ], array_keys($list[0]));
        $this->assertSame(2, $list[0]['lesson_count']);
        $this->assertSame(180, $list[0]['total_seconds']);
        $this->assertSame(1, $list[0]['completed_lessons']);
        $this->assertSame(50, $list[0]['progress_pct']);
        $this->assertFalse($list[0]['certified']);

        $this->getJson('/api/v1/classroom/courses/'.$course->id)->assertOk()
            ->assertJsonPath('data.quiz_unlocked', false)
            ->assertJsonPath('data.last_attempt', null)
            ->assertJsonPath('data.retry_available_at', null)
            ->assertJsonPath('data.lessons.0.youtube_id', 'dQw4w9WgXcQ')
            ->assertJsonPath('data.lessons.0.completed', true)
            ->assertJsonPath('data.lessons.1.completed', false)
            ->assertJsonPath('data.lessons.1.watched_seconds', 0);

        $this->getJson('/api/v1/classroom/courses/999999')->assertStatus(404)->assertJsonPath('code', 'COURSE_NOT_FOUND');
    }

    // =====================================================
    // สอบ
    // =====================================================

    public function test_quiz_is_locked_until_every_lesson_is_completed(): void
    {
        $course = $this->makeCourse();
        $one = $this->makeLesson($course);
        $two = $this->makeLesson($course);
        $this->makeQuestions($course, 4);
        $user = User::factory()->create();

        $this->startQuiz($user, $course)->assertStatus(423)->assertJsonPath('code', 'QUIZ_LOCKED');

        $this->completeLesson($user, $one);
        $this->startQuiz($user, $course)->assertStatus(423)->assertJsonPath('code', 'QUIZ_LOCKED');

        // บทที่ปิดไม่นับในเงื่อนไข
        $this->makeLesson($course, 50, ['is_active' => false]);
        $this->completeLesson($user, $two);
        $this->startQuiz($user, $course)->assertOk()->assertJsonPath('success', true);
    }

    public function test_course_without_lessons_is_locked(): void
    {
        $course = $this->makeCourse();
        $this->makeQuestions($course, 4);
        $user = User::factory()->create();

        $this->startQuiz($user, $course)->assertStatus(423)->assertJsonPath('code', 'QUIZ_LOCKED');
    }

    public function test_quiz_start_hides_answers_and_reuses_open_attempt(): void
    {
        [$course, $user] = $this->readyCourse();

        $response = $this->startQuiz($user, $course)->assertOk();
        $quiz = $response->json('data');
        $this->assertCount(4, $quiz['questions']);
        $this->assertNotNull($quiz['expires_at']);
        foreach ($quiz['questions'] as $question) {
            $this->assertSame(['id', 'question', 'choices'], array_keys($question));
            $this->assertCount(4, $question['choices']);
        }
        $this->assertStringNotContainsString('correct_index', $response->getContent());
        $this->assertStringNotContainsString('answer_key', $response->getContent());

        // กดเริ่มซ้ำระหว่างยังไม่หมดเวลา = ชุดเดิม
        $again = $this->startQuiz($user, $course)->assertOk()->json('data');
        $this->assertSame($quiz['attempt_id'], $again['attempt_id']);
        $this->assertSame(1, ClassroomAttempt::query()->where('user_id', $user->id)->count());

        $attempt = ClassroomAttempt::find($quiz['attempt_id']);
        $this->assertEquals(now()->addMinutes(30)->getTimestamp(), $attempt->expires_at->getTimestamp());
    }

    public function test_passing_certifies_once_and_fires_event_once(): void
    {
        Event::fake([TrainingCertified::class]);
        [$course, $user] = $this->readyCourse('rider', ['points_reward' => 10]);

        $quiz = $this->startQuiz($user, $course)->assertOk()->json('data');
        $result = $this->submitQuiz($user, (int) $quiz['attempt_id'], $this->answersFor($quiz, 3))->assertOk()->json('data');

        $this->assertSame(3, $result['score']);
        $this->assertSame(4, $result['total']);
        $this->assertSame(3, $result['pass_score']);
        $this->assertTrue($result['passed']);
        $this->assertSame('rider', $result['certification']['role']);
        $this->assertSame(10, $result['certification']['points_awarded']);
        $this->assertTrue($result['certification']['newly_certified']);
        $this->assertNull($result['retry_available_at']);
        $this->assertCount(4, $result['results']);
        foreach ($result['results'] as $row) {
            foreach (['question_id', 'correct', 'correct_index', 'explanation'] as $key) {
                $this->assertArrayHasKey($key, $row);
            }
        }
        $this->assertSame(['certified' => true, 'role' => 'rider', 'label' => 'ผ่านการอบรมไรเดอร์', 'needs_refresh' => false], $result['training_badge']);

        Event::assertDispatchedTimes(TrainingCertified::class, 1);
        Event::assertDispatched(TrainingCertified::class, fn (TrainingCertified $e) => $e->userId === $user->id
            && $e->role === 'rider' && $e->courseId === $course->id && $e->pointsAwarded === 10);

        // สอบผ่านซ้ำ = ใบเดิม ไม่ยิง event ไม่ให้คะแนนซ้ำ
        $second = $this->passQuiz($user, $course)->assertOk()->json('data');
        $this->assertTrue($second['passed']);
        $this->assertSame(0, $second['certification']['points_awarded']);
        $this->assertFalse($second['certification']['newly_certified']);
        Event::assertDispatchedTimes(TrainingCertified::class, 1);

        $this->assertSame(1, ClassroomCertification::query()->where('user_id', $user->id)->count());
        $cert = ClassroomCertification::query()->where('user_id', $user->id)->first();
        $this->assertSame(10, $cert->points_awarded);
        $this->assertSame('2026-10', $cert->rules_version);

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/classroom/courses')->assertOk()
            ->assertJsonPath('data.0.certified', true)
            ->assertJsonPath('data.0.needs_refresh', false);
    }

    public function test_failing_returns_cooldown_and_blocks_restart_until_it_passes(): void
    {
        Event::fake([TrainingCertified::class]);
        [$course, $user] = $this->readyCourse('seller', ['retry_cooldown_seconds' => 60]);

        $quiz = $this->startQuiz($user, $course)->assertOk()->json('data');
        $result = $this->submitQuiz($user, (int) $quiz['attempt_id'], $this->answersFor($quiz, 2))->assertOk()->json('data');

        $this->assertFalse($result['passed']);
        $this->assertSame(2, $result['score']);
        $this->assertNull($result['certification']);
        $this->assertSame(now()->addSeconds(60)->toIso8601String(), $result['retry_available_at']);
        Event::assertNotDispatched(TrainingCertified::class);

        $this->startQuiz($user, $course)->assertStatus(429)
            ->assertJsonPath('code', 'QUIZ_COOLDOWN')
            ->assertJsonPath('data.retry_available_at', now()->addSeconds(60)->toIso8601String());

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/classroom/courses/'.$course->id)->assertOk()
            ->assertJsonPath('data.last_attempt.passed', false)
            ->assertJsonPath('data.last_attempt.score', 2)
            ->assertJsonPath('data.retry_available_at', now()->addSeconds(60)->toIso8601String());

        $this->travel(61)->seconds();
        $this->startQuiz($user, $course)->assertOk();
    }

    public function test_expired_attempt_cannot_be_submitted(): void
    {
        [$course, $user] = $this->readyCourse('buyer');

        $quiz = $this->startQuiz($user, $course)->assertOk()->json('data');
        $this->travel(31)->minutes();

        $this->submitQuiz($user, (int) $quiz['attempt_id'], $this->answersFor($quiz, 4))
            ->assertStatus(410)
            ->assertJsonPath('code', 'ATTEMPT_EXPIRED');
        $this->assertNull(ClassroomAttempt::find($quiz['attempt_id'])->submitted_at);

        // ชุดใหม่ (หมดเวลาครบช่วงรอแล้ว) ไม่ใช่ชุดเดิม
        $fresh = $this->startQuiz($user, $course)->assertOk()->json('data');
        $this->assertNotSame($quiz['attempt_id'], $fresh['attempt_id']);
    }

    public function test_double_submit_is_rejected_and_other_users_attempt_is_hidden(): void
    {
        [$course, $user] = $this->readyCourse();

        $quiz = $this->startQuiz($user, $course)->assertOk()->json('data');
        $answers = $this->answersFor($quiz, 4);

        $stranger = User::factory()->create();
        $this->submitQuiz($stranger, (int) $quiz['attempt_id'], $answers)->assertStatus(404)->assertJsonPath('code', 'ATTEMPT_NOT_FOUND');

        $this->submitQuiz($user, (int) $quiz['attempt_id'], $answers)->assertOk()->assertJsonPath('data.passed', true);
        $this->submitQuiz($user, (int) $quiz['attempt_id'], $answers)->assertStatus(409)->assertJsonPath('code', 'ATTEMPT_SUBMITTED');
        $this->assertSame(1, ClassroomCertification::query()->where('user_id', $user->id)->count());
    }

    public function test_bad_answers_count_as_wrong(): void
    {
        [$course, $user] = $this->readyCourse();

        $quiz = $this->startQuiz($user, $course)->assertOk()->json('data');
        $answers = [];
        foreach ($quiz['questions'] as $i => $question) {
            $answers[(string) $question['id']] = ['x', 99, -1, null][$i];
        }

        $this->submitQuiz($user, (int) $quiz['attempt_id'], $answers)->assertOk()
            ->assertJsonPath('data.score', 0)
            ->assertJsonPath('data.passed', false)
            ->assertJsonPath('data.results.0.chosen_index', null);
    }

    // =====================================================
    // รุ่นกติกา
    // =====================================================

    public function test_rules_version_bump_marks_needs_refresh_and_recert_fires_zero_points(): void
    {
        Event::fake([TrainingCertified::class]);
        [$course, $user] = $this->readyCourse('rider');
        $this->passQuiz($user, $course)->assertOk()->assertJsonPath('data.certification.points_awarded', 10);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put(route('admin.classroom.courses.update', $course), [
            'title' => $course->title,
            'description' => $course->description,
            'rules_version' => '2026-12',
            'question_count' => $course->question_count,
            'pass_score' => $course->pass_score,
            'points_reward' => $course->points_reward,
            'retry_cooldown_seconds' => $course->retry_cooldown_seconds,
            'is_active' => 1,
        ])->assertRedirect(route('admin.classroom.courses.edit', $course))->assertSessionHas('success');

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($user->fresh());
        $this->getJson('/api/v1/me')->assertOk()
            ->assertJsonPath('data.training_badges.rider.certified', true)
            ->assertJsonPath('data.training_badges.rider.needs_refresh', true)
            ->assertJsonPath('data.training_badges.seller', null);
        $this->getJson('/api/v1/classroom/courses')->assertOk()
            ->assertJsonPath('data.0.certified', true)
            ->assertJsonPath('data.0.needs_refresh', true);

        // ทบทวนแล้วสอบผ่าน → รุ่นใหม่ · event คะแนน 0 · คะแนนเดิมบนใบไม่หาย
        $this->passQuiz($user, $course)->assertOk()
            ->assertJsonPath('data.certification.points_awarded', 0)
            ->assertJsonPath('data.certification.refreshed', true)
            ->assertJsonPath('data.training_badge.needs_refresh', false);

        Event::assertDispatchedTimes(TrainingCertified::class, 2);
        Event::assertDispatched(TrainingCertified::class, fn (TrainingCertified $e) => $e->pointsAwarded === 0);
        $cert = ClassroomCertification::query()->where('user_id', $user->id)->first();
        $this->assertSame('2026-12', $cert->rules_version);
        $this->assertSame(10, $cert->points_awarded);
    }

    // =====================================================
    // ตัวช่วย / seeder
    // =====================================================

    public function test_youtube_links_of_every_shape_are_parsed(): void
    {
        $id = 'dQw4w9WgXcQ';
        foreach ([
            $id,
            'https://youtu.be/'.$id,
            'https://youtu.be/'.$id.'?si=abc123&t=10',
            'https://www.youtube.com/watch?v='.$id,
            'https://m.youtube.com/watch?feature=share&v='.$id,
            'youtube.com/watch?v='.$id.'&list=PL123',
            'https://www.youtube.com/embed/'.$id.'?rel=0',
            'https://www.youtube.com/shorts/'.$id,
            'https://www.youtube.com/live/'.$id.'?feature=share',
            'https://www.youtube-nocookie.com/embed/'.$id,
            'https://music.youtube.com/watch?v='.$id,
        ] as $url) {
            $this->assertSame($id, YouTubeId::parse($url), $url);
        }

        foreach (['', 'https://vimeo.com/123456', 'https://youtu.be/short', 'https://evil.com/watch?v='.$id, 'not a url'] as $bad) {
            $this->assertNull(YouTubeId::parse($bad), $bad);
        }
    }

    public function test_seeder_creates_three_courses_with_question_banks_idempotently(): void
    {
        (new ClassroomSeeder)->run();
        (new ClassroomSeeder)->run();

        $counts = \App\Models\ClassroomCourse::query()->withCount('questions')->get()->pluck('questions_count', 'role')->all();
        $this->assertSame(['seller' => 30, 'rider' => 30, 'buyer' => 10], array_intersect_key($counts, ['seller' => 1, 'rider' => 1, 'buyer' => 1]));
        $this->assertSame(3, \App\Models\ClassroomCourse::query()->count());
        $this->assertSame(0, \App\Models\ClassroomLesson::query()->count(), 'ไม่ใส่บทเรียน — แอดมินวางลิงก์เอง');

        $rider = \App\Models\ClassroomCourse::query()->where('role', 'rider')->first();
        $this->assertSame([15, 13, 10], [$rider->question_count, $rider->pass_score, $rider->points_reward]);
        $buyer = \App\Models\ClassroomCourse::query()->where('role', 'buyer')->first();
        $this->assertSame([5, 4, 5], [$buyer->question_count, $buyer->pass_score, $buyer->points_reward]);

        foreach (\App\Models\ClassroomQuestion::all() as $question) {
            $this->assertCount(4, $question->choiceList());
            $this->assertArrayHasKey($question->correct_index, $question->choiceList());
            $this->assertNotEmpty($question->explanation);
        }
    }
}
