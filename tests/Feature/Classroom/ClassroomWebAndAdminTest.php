<?php

namespace Tests\Feature\Classroom;

use App\Models\ClassroomAttempt;
use App\Models\ClassroomLesson;
use App\Models\ClassroomQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Classroom\Concerns\BuildsClassroomFixtures;
use Tests\TestCase;

/**
 * 🎓 ห้องเรียนบนเว็บ (/user/classroom) + หลังบ้าน (/admin/classroom) — ต้องใช้ MySQL
 *
 * - หน้าแอดมินทุกหน้าเปิดได้ (200) สำหรับแอดมิน · สมาชิกทั่วไปเข้าไม่ได้
 * - แอดมินเพิ่มบทเรียนด้วยลิงก์ YouTube + ความยาว mm:ss · เพิ่มข้อสอบ (ตัดตัวเลือกว่าง) · เปิดได้ทีละ 1 คอร์สต่อบทบาท
 * - หน้าเว็บผู้เรียนเรนเดอร์ครบ: รายการคอร์ส · คอร์ส · บทเรียน · ข้อสอบ (ไม่มีเฉลย) · ผลสอบ
 * - ชุดข้อสอบของคนอื่น = 404 (กัน IDOR)
 */
#[Group('classroom')]
class ClassroomWebAndAdminTest extends TestCase
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

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // =====================================================
    // หลังบ้าน
    // =====================================================

    public function test_admin_pages_render_for_admin(): void
    {
        [$course, $user] = $this->readyCourse('seller');
        $this->passQuiz($user, $course)->assertOk();
        $question = ClassroomQuestion::query()->where('course_id', $course->id)->firstOrFail();

        $admin = $this->admin();
        foreach ([
            route('admin.classroom.index'),
            route('admin.classroom.courses.edit', $course),
            route('admin.classroom.questions.index', $course),
            route('admin.classroom.questions.index', [$course, 'status' => 'active', 'q' => 'คำถาม']),
            route('admin.classroom.questions.create', $course),
            route('admin.classroom.questions.edit', $question),
            route('admin.classroom.stats', $course),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.classroom.index'))->assertSee('ห้องเรียน');
    }

    public function test_admin_pages_are_blocked_for_members(): void
    {
        $course = $this->makeCourse('rider');
        $member = User::factory()->create(['role' => 'user']);

        foreach ([route('admin.classroom.index'), route('admin.classroom.courses.edit', $course), route('admin.classroom.stats', $course)] as $url) {
            $status = $this->actingAs($member)->get($url)->getStatusCode();
            $this->assertContains($status, [302, 403], $url.' ต้องไม่เปิดให้สมาชิก');
        }

        $this->assertContains($this->actingAs($member)->put(route('admin.classroom.courses.update', $course), ['title' => 'แฮก'])->getStatusCode(), [302, 403]);
        $this->assertSame('คอร์สทดสอบ rider', $course->fresh()->title);

        $this->app['auth']->forgetGuards();
        $this->get(route('admin.classroom.index'))->assertRedirect();
    }

    public function test_admin_adds_lesson_from_any_youtube_link_and_question_with_blank_choices_dropped(): void
    {
        $course = $this->makeCourse('buyer');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.classroom.lessons.store', $course), [
            'title' => 'แนะนำการสั่งของ',
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ?si=abc',
            'duration' => '5:30',
        ])->assertRedirect(route('admin.classroom.courses.edit', $course));

        $lesson = ClassroomLesson::query()->where('course_id', $course->id)->firstOrFail();
        $this->assertSame('dQw4w9WgXcQ', $lesson->youtube_id);
        $this->assertSame(330, $lesson->duration_seconds);

        $this->actingAs($admin)->post(route('admin.classroom.lessons.store', $course), [
            'title' => 'ลิงก์ผิด',
            'youtube_url' => 'https://vimeo.com/123',
            'duration' => '9:99',
        ])->assertSessionHasErrorsIn('lesson', ['youtube_url', 'duration']);

        // ตัวเลือกช่องที่ 2 ว่าง → ถูกตัด และตำแหน่งคำตอบที่ถูกเลื่อนตาม
        $this->actingAs($admin)->post(route('admin.classroom.questions.store', $course), [
            'question' => 'ควรทำอย่างไรเมื่อได้รับของ?',
            'choices' => ['ตรวจของ', '', 'ทิ้งไว้', 'สแกน QR'],
            'correct_index' => 3,
            'explanation' => 'สแกน QR เพื่อยืนยันรับของ',
        ])->assertRedirect(route('admin.classroom.questions.index', $course));

        $question = ClassroomQuestion::query()->where('course_id', $course->id)->firstOrFail();
        $this->assertSame(['ตรวจของ', 'ทิ้งไว้', 'สแกน QR'], $question->choiceList());
        $this->assertSame(2, (int) $question->correct_index);

        $this->actingAs($admin)->post(route('admin.classroom.questions.store', $course), [
            'question' => 'ตัวเลือกไม่พอ',
            'choices' => ['ข้อเดียว', ''],
            'correct_index' => 1,
        ])->assertSessionHasErrors(['choices', 'correct_index']);
    }

    public function test_activating_a_course_deactivates_others_of_the_same_role(): void
    {
        $old = $this->makeCourse('rider', ['title' => 'คอร์สเก่า']);
        $new = $this->makeCourse('rider', ['title' => 'คอร์สใหม่', 'is_active' => false]);

        $this->actingAs($this->admin())->put(route('admin.classroom.courses.update', $new), [
            'title' => 'คอร์สใหม่',
            'rules_version' => '2026-11',
            'question_count' => 15,
            'pass_score' => 13,
            'points_reward' => 10,
            'retry_cooldown_seconds' => 60,
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertTrue($new->fresh()->is_active);
        $this->assertFalse($old->fresh()->is_active);

        $this->actingAs($this->admin())->put(route('admin.classroom.courses.update', $new), [
            'title' => 'คอร์สใหม่',
            'rules_version' => 'มีช่องว่าง ผิด',
            'question_count' => 5,
            'pass_score' => 6,
            'points_reward' => 10,
            'retry_cooldown_seconds' => 60,
        ])->assertSessionHasErrors(['rules_version', 'pass_score']);
    }

    // =====================================================
    // หน้าเว็บผู้เรียน
    // =====================================================

    public function test_member_web_pages_render_through_quiz_and_result(): void
    {
        $course = $this->makeCourse('seller', ['title' => 'คอร์สผู้ขายบนเว็บ']);
        $lesson = $this->makeLesson($course, 120, ['title' => 'บทแรกของผู้ขาย']);
        $this->makeQuestions($course, 4);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('user.classroom.index'))->assertOk()->assertSee('คอร์สผู้ขายบนเว็บ');
        $this->actingAs($user)->get(route('user.classroom.course', $course->id))->assertOk()->assertSee('บทแรกของผู้ขาย');
        $this->actingAs($user)->get(route('user.classroom.lesson', $lesson->id))->assertOk()->assertSee('dQw4w9WgXcQ');

        // heartbeat จากหน้าเว็บ (session) → JSON รูปแบบเดียวกับแอป
        $this->actingAs($user)->postJson(route('user.classroom.lesson.progress', $lesson->id), ['position_seconds' => 0])
            ->assertOk()->assertJsonPath('data.watched_seconds', 0);
        $this->actingAs($user)->postJson(route('user.classroom.lesson.progress', $lesson->id), ['position_seconds' => 'x'])
            ->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');

        // ยังดูไม่ครบ → เริ่มสอบไม่ได้ (กลับหน้าคอร์สพร้อมข้อความ)
        $this->actingAs($user)->post(route('user.classroom.quiz.start', $course->id))
            ->assertRedirect(route('user.classroom.course', $course->id))
            ->assertSessionHas('error');

        $this->completeLesson($user, $lesson);
        $this->actingAs($user)->post(route('user.classroom.quiz.start', $course->id))->assertRedirect();
        $attempt = ClassroomAttempt::query()->where('user_id', $user->id)->latest('id')->firstOrFail();

        $quizPage = $this->actingAs($user)->get(route('user.classroom.attempt', $attempt->id))->assertOk();
        $quizPage->assertSee('คำถามที่ 1');
        $this->assertStringNotContainsString('answer_key', $quizPage->getContent());
        $this->assertStringNotContainsString('correct_index', $quizPage->getContent());

        // คนอื่นเปิด/ส่งชุดนี้ไม่ได้
        $stranger = User::factory()->create(['role' => 'user']);
        $this->actingAs($stranger)->get(route('user.classroom.attempt', $attempt->id))->assertNotFound();
        $this->actingAs($stranger)->get(route('user.classroom.result', $attempt->id))->assertNotFound();
        $this->actingAs($stranger)->post(route('user.classroom.attempt.submit', $attempt->id), ['answers' => []])->assertNotFound();

        $quiz = app(\App\Services\Classroom\ClassroomPresenter::class)->attemptQuestions($attempt);
        $this->actingAs($user)->post(route('user.classroom.attempt.submit', $attempt->id), ['answers' => $this->answersFor($quiz, 4)])
            ->assertRedirect(route('user.classroom.result', $attempt->id));

        $this->actingAs($user)->get(route('user.classroom.result', $attempt->id))->assertOk()
            ->assertSee('ผ่านการอบรมผู้ขาย')
            ->assertSee('data-training-badge="seller"', false);

        // ส่งซ้ำ = ไปหน้าผลเดิม · เปิดหน้าข้อสอบที่ส่งแล้ว = ไปหน้าผล
        $this->actingAs($user)->post(route('user.classroom.attempt.submit', $attempt->id), ['answers' => []])
            ->assertRedirect(route('user.classroom.result', $attempt->id));
        $this->actingAs($user)->get(route('user.classroom.attempt', $attempt->id))
            ->assertRedirect(route('user.classroom.result', $attempt->id));

        $this->actingAs($user)->get(route('user.classroom.index'))->assertOk()->assertSee('data-training-badge="seller"', false);
        $this->actingAs($user)->get(route('user.classroom.course', 999999))->assertNotFound();
    }

    public function test_index_renders_empty_state_without_courses(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('user.classroom.index'))->assertOk();
    }
}
