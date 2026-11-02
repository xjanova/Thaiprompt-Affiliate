<?php

namespace Tests\Feature\User;

use App\Models\ClassroomAttempt;
use App\Models\CoinExchangeRate;
use App\Models\CoinExchangeRequest;
use App\Models\User;
use App\Models\UserVideoLevel;
use App\Models\VideoCoin;
use App\Models\VideoCoinTransaction;
use App\Models\VideoLevel;
use App\Services\Classroom\ClassroomPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Classroom\Concerns\BuildsClassroomFixtures;
use Tests\TestCase;

/**
 * หน้าห้องเรียน (/user/classroom/*) + Video Coins (/user/video-coins/*) ธีมโนวาเต็ม
 *
 * - ทุกหน้าเปิดได้ (200) และอยู่ในเปลือกโนวาเต็ม ไม่ใช่โหมดสะพาน V4
 * - ชุดข้อสอบ/ผลสอบของคนอื่น = 404
 * - แลก Coins บนเว็บ: เดิมโดนปฏิเสธทุกครั้ง (อ่าน users.level ที่ไม่มีอยู่จริง) และถ้าผ่านไปได้ก็บันทึกไม่ได้
 *   (ไม่ใส่ exchange_rate ที่เป็น NOT NULL) + ไม่หัก coins (แอดมินปฏิเสธแล้วคืน coins = ได้เหรียญฟรี)
 */
class NovaClassroomVideoPagesTest extends TestCase
{
    use BuildsClassroomFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Http::fake();
        Cache::flush();
        $this->resetClassroom();
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'kyc_status' => 'approved'], $attributes));
    }

    /**
     * หน้าต้องอยู่ในเปลือกโนวาเต็ม ไม่ใช่โหมดสะพาน V4
     */
    private function assertFullNova(string $html): void
    {
        $this->assertStringContainsString('nvm-body', $html);
        $this->assertStringContainsString('nova-member.css', $html);
        $this->assertStringContainsString('theme-nova/member/classroom-video.css', $html);
        $this->assertStringNotContainsString('class="nvm-v4"', $html, 'ยังเป็นโหมดสะพาน V4');
        $this->assertStringNotContainsString('tp-card', $html, 'ยังมีการ์ด V4 หลงอยู่');
        $this->assertStringNotContainsString('fas fa-', $html, 'ยังมีไอคอน Font Awesome หลงอยู่');
    }

    /**
     * คอร์สพร้อมสอบสำหรับสมาชิก (role user) — readyCourse() ของ trait สร้างผู้ใช้ role ค่าเริ่มต้นที่เข้าหน้าเว็บสมาชิกไม่ได้
     *
     * @return array{0: \App\Models\ClassroomCourse, 1: User}
     */
    private function readyMemberCourse(string $role): array
    {
        $course = $this->makeCourse($role);
        $lesson = $this->makeLesson($course);
        $this->makeQuestions($course, 4);
        $user = $this->member();
        $this->completeLesson($user, $lesson);

        return [$course, $user];
    }

    private function rate(array $attributes = []): CoinExchangeRate
    {
        return CoinExchangeRate::create(array_merge([
            'coins_amount' => 100,
            'money_amount' => 10,
            'rate' => 0.1,
            'min_level' => 1,
            'min_coins' => 100,
            'is_active' => true,
        ], $attributes));
    }

    private function videoLevel(User $user, int $level = 1): void
    {
        $vl = VideoLevel::create(['level' => $level, 'name' => 'เลเวล '.$level, 'required_exp' => 0]);
        UserVideoLevel::create(['user_id' => $user->id, 'current_level_id' => $vl->id]);
    }

    // =====================================================
    // ห้องเรียน
    // =====================================================

    public function test_classroom_pages_render_in_full_nova_shell(): void
    {
        $course = $this->makeCourse('seller', ['title' => 'คอร์สผู้ขายโนวา']);
        $lesson = $this->makeLesson($course, 120, ['title' => 'บทแรกโนวา']);
        $second = $this->makeLesson($course, 90, ['title' => 'บทสองโนวา']);
        $this->makeQuestions($course, 4);
        $user = $this->member();

        $index = $this->actingAs($user)->get(route('user.classroom.index'))->assertOk()->assertSee('คอร์สผู้ขายโนวา');
        $this->assertFullNova($index->getContent());

        $coursePage = $this->actingAs($user)->get(route('user.classroom.course', $course->id))->assertOk()->assertSee('บทแรกโนวา');
        $this->assertFullNova($coursePage->getContent());

        $lessonPage = $this->actingAs($user)->get(route('user.classroom.lesson', $lesson->id))->assertOk()
            ->assertSee('dQw4w9WgXcQ')
            ->assertSee(route('user.classroom.lesson', $second->id), false);
        $this->assertFullNova($lessonPage->getContent());
        // URL heartbeat อยู่ใน config ของตัวเล่น (Js::from escape เครื่องหมาย / ด้วย backslash)
        $this->assertMatchesRegularExpression('#classroom\\\*/lessons\\\*/'.$lesson->id.'\\\*/progress#', $lessonPage->getContent());

        // ดูจบทุกบท → เริ่มสอบ → หน้าข้อสอบ (ไม่มีเฉลย) → ส่ง → หน้าผล
        $this->completeLesson($user, $lesson);
        $this->completeLesson($user, $second);
        $this->actingAs($user)->post(route('user.classroom.quiz.start', $course->id))->assertRedirect();
        $attempt = ClassroomAttempt::query()->where('user_id', $user->id)->latest('id')->firstOrFail();

        $quizPage = $this->actingAs($user)->get(route('user.classroom.attempt', $attempt->id))->assertOk()
            ->assertSee('คำถามที่ 1')
            ->assertSee('name="answers[', false)
            ->assertSee(route('user.classroom.attempt.submit', $attempt->id), false);
        $this->assertFullNova($quizPage->getContent());
        $this->assertStringNotContainsString('correct_index', $quizPage->getContent());

        $quiz = app(ClassroomPresenter::class)->attemptQuestions($attempt);
        $this->actingAs($user)->post(route('user.classroom.attempt.submit', $attempt->id), ['answers' => $this->answersFor($quiz, 4)])
            ->assertRedirect(route('user.classroom.result', $attempt->id));

        $resultPage = $this->actingAs($user)->get(route('user.classroom.result', $attempt->id))->assertOk()
            ->assertSee('ผ่านการอบรมผู้ขาย')
            ->assertSee('data-training-badge="seller"', false)
            ->assertSee('คำอธิบายข้อ 1');
        $this->assertFullNova($resultPage->getContent());
    }

    public function test_failed_result_shows_retry_and_course_shows_last_attempt(): void
    {
        [$course, $user] = $this->readyMemberCourse('rider');

        $this->actingAs($user)->post(route('user.classroom.quiz.start', $course->id))->assertRedirect();
        $attempt = ClassroomAttempt::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
        $quiz = app(ClassroomPresenter::class)->attemptQuestions($attempt);
        $this->actingAs($user)->post(route('user.classroom.attempt.submit', $attempt->id), ['answers' => $this->answersFor($quiz, 0)]);

        $result = $this->actingAs($user)->get(route('user.classroom.result', $attempt->id))->assertOk()
            ->assertSee('ยังไม่ผ่าน ลองอีกครั้งนะ')
            ->assertSee(route('user.classroom.quiz.start', $course->id), false);
        $this->assertFullNova($result->getContent());

        $coursePage = $this->actingAs($user)->get(route('user.classroom.course', $course->id))->assertOk()
            ->assertSee('ผลสอบล่าสุด')
            ->assertSee('สอบไม่ผ่านรอบที่แล้ว');
        $this->assertFullNova($coursePage->getContent());
    }

    public function test_classroom_index_empty_state_renders(): void
    {
        $page = $this->actingAs($this->member())->get(route('user.classroom.index'))->assertOk()->assertSee('ยังไม่มีคอร์สเปิดสอน');
        $this->assertFullNova($page->getContent());
    }

    public function test_another_members_attempt_is_not_found(): void
    {
        [$course, $owner] = $this->readyMemberCourse('buyer');
        $this->actingAs($owner)->post(route('user.classroom.quiz.start', $course->id))->assertRedirect();
        $attempt = ClassroomAttempt::query()->where('user_id', $owner->id)->latest('id')->firstOrFail();

        $stranger = $this->member();
        $this->actingAs($stranger)->get(route('user.classroom.attempt', $attempt->id))->assertNotFound();
        $this->actingAs($stranger)->get(route('user.classroom.result', $attempt->id))->assertNotFound();
        $this->actingAs($stranger)->post(route('user.classroom.attempt.submit', $attempt->id), ['answers' => []])->assertNotFound();
    }

    public function test_inactive_course_and_lesson_are_not_found(): void
    {
        $course = $this->makeCourse('seller', ['is_active' => false]);
        $lesson = $this->makeLesson($course);
        $user = $this->member();

        $this->actingAs($user)->get(route('user.classroom.course', $course->id))->assertNotFound();
        $this->actingAs($user)->get(route('user.classroom.lesson', $lesson->id))->assertNotFound();
    }

    // =====================================================
    // Video Coins
    // =====================================================

    public function test_video_coin_pages_render_in_full_nova_shell(): void
    {
        $user = $this->member();
        $this->rate();
        $coin = VideoCoin::create(['user_id' => $user->id, 'balance' => 250, 'lifetime_earned' => 300, 'lifetime_spent' => 50, 'lifetime_exchanged' => 50]);
        $coin->addCoins(25, 'earned_video', null, null, 'ดูวิดีโอครบ');

        $index = $this->actingAs($user)->get(route('user.video-coins.index'))->assertOk()
            ->assertSee('ได้รับจากวิดีโอ')
            ->assertSee('275');
        $this->assertFullNova($index->getContent());

        $tx = $this->actingAs($user)->get(route('user.video-coins.transactions'))->assertOk()->assertSee('ดูวิดีโอครบ');
        $this->assertFullNova($tx->getContent());

        $exchange = $this->actingAs($user)->get(route('user.video-coins.exchange'))->assertOk()
            ->assertSee(route('user.video-coins.exchange.submit'), false)
            ->assertSee('name="exchange_rate_id"', false);
        $this->assertFullNova($exchange->getContent());
    }

    public function test_video_coin_pages_render_for_a_new_member_without_coins(): void
    {
        $user = $this->member();

        foreach (['user.video-coins.index', 'user.video-coins.transactions', 'user.video-coins.exchange'] as $name) {
            $page = $this->actingAs($user)->get(route($name))->assertOk();
            $this->assertFullNova($page->getContent());
        }
    }

    public function test_earned_and_spent_today_come_from_todays_transactions(): void
    {
        $user = $this->member();
        $coin = VideoCoin::create(['user_id' => $user->id, 'balance' => 0]);
        $coin->addCoins(40, 'earned_video');
        $coin->deductCoins(15, 'spent_shop');

        $this->actingAs($user)->get(route('user.video-coins.index'))->assertOk()
            ->assertViewHas('earnedToday', 40.0)
            ->assertViewHas('spentToday', 15.0);
    }

    public function test_refund_adjustment_is_shown_as_income(): void
    {
        $user = $this->member();
        $coin = VideoCoin::create(['user_id' => $user->id, 'balance' => 0]);
        $coin->addCoins(100, 'adjustment', null, null, 'คืนเหรียญทดสอบ');

        $html = $this->actingAs($user)->get(route('user.video-coins.transactions'))->assertOk()->getContent();
        $this->assertStringContainsString('+100.00', $html);
        $this->assertStringNotContainsString('-100.00', $html);
    }

    public function test_transactions_list_only_the_members_own_rows_and_ignore_bad_dates(): void
    {
        $user = $this->member();
        $other = $this->member();
        VideoCoin::create(['user_id' => $user->id, 'balance' => 0])->addCoins(10, 'earned_video', null, null, 'ของฉันเอง');
        VideoCoin::create(['user_id' => $other->id, 'balance' => 0])->addCoins(10, 'earned_video', null, null, 'ของคนอื่น');

        $this->actingAs($user)->get(route('user.video-coins.transactions', ['date_from' => 'not-a-date', 'date_to' => '2026-02-31', 'type' => 'earned_video']))
            ->assertOk()
            ->assertSee('ของฉันเอง')
            ->assertDontSee('ของคนอื่น');
    }

    public function test_exchange_submit_deducts_coins_and_creates_a_pending_request(): void
    {
        $user = $this->member();
        $this->videoLevel($user, 1);
        $rate = $this->rate();
        VideoCoin::create(['user_id' => $user->id, 'balance' => 250]);

        $this->actingAs($user)->post(route('user.video-coins.exchange.submit'), ['exchange_rate_id' => $rate->id])
            ->assertRedirect(route('user.video-coins.exchange'))
            ->assertSessionHas('success');

        $request = CoinExchangeRequest::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertEquals(0.1, (float) $request->exchange_rate);
        $this->assertEquals(100.0, (float) $request->coins_amount);

        $coin = VideoCoin::where('user_id', $user->id)->first();
        $this->assertEquals(150.0, (float) $coin->balance);
        $this->assertEquals(100.0, (float) $coin->lifetime_exchanged);
        $this->assertDatabaseHas('video_coin_transactions', ['user_id' => $user->id, 'type' => 'spent_exchange']);

        // แอดมินปฏิเสธ = คืน coins กลับเท่ายอดเดิม (ไม่ได้เหรียญเพิ่มฟรี)
        $request->reject(User::factory()->create(['role' => 'admin'])->id, 'ทดสอบ');
        $this->assertEquals(250.0, (float) VideoCoin::where('user_id', $user->id)->value('balance'));
    }

    public function test_exchange_submit_rejects_when_coins_are_not_enough(): void
    {
        $user = $this->member();
        $this->videoLevel($user, 1);
        $rate = $this->rate();
        VideoCoin::create(['user_id' => $user->id, 'balance' => 50]);

        $this->actingAs($user)->from(route('user.video-coins.exchange'))
            ->post(route('user.video-coins.exchange.submit'), ['exchange_rate_id' => $rate->id])
            ->assertRedirect(route('user.video-coins.exchange'))
            ->assertSessionHas('error', 'ยอด Coins ไม่เพียงพอ');

        $this->assertSame(0, CoinExchangeRequest::where('user_id', $user->id)->count());
        $this->assertEquals(50.0, (float) VideoCoin::where('user_id', $user->id)->value('balance'));
    }

    public function test_exchange_submit_rejects_inactive_rate_and_low_level(): void
    {
        $user = $this->member();
        VideoCoin::create(['user_id' => $user->id, 'balance' => 500]);
        $inactive = $this->rate(['is_active' => false]);
        $highLevel = $this->rate(['min_level' => 5]);
        $this->videoLevel($user, 2);

        $this->actingAs($user)->from(route('user.video-coins.exchange'))
            ->post(route('user.video-coins.exchange.submit'), ['exchange_rate_id' => $inactive->id])
            ->assertSessionHas('error');
        $this->actingAs($user)->from(route('user.video-coins.exchange'))
            ->post(route('user.video-coins.exchange.submit'), ['exchange_rate_id' => $highLevel->id])
            ->assertSessionHas('error', 'ต้องมี Level อย่างน้อย 5');

        $this->assertSame(0, CoinExchangeRequest::where('user_id', $user->id)->count());
        $this->assertEquals(500.0, (float) VideoCoin::where('user_id', $user->id)->value('balance'));
        $this->assertSame(0, VideoCoinTransaction::where('user_id', $user->id)->count());
    }

    public function test_exchange_submit_respects_daily_limit(): void
    {
        $user = $this->member();
        $this->videoLevel($user, 1);
        $rate = $this->rate(['max_coins_per_day' => 150]);
        VideoCoin::create(['user_id' => $user->id, 'balance' => 1000]);

        $this->actingAs($user)->post(route('user.video-coins.exchange.submit'), ['exchange_rate_id' => $rate->id])->assertSessionHas('success');
        $this->actingAs($user)->from(route('user.video-coins.exchange'))
            ->post(route('user.video-coins.exchange.submit'), ['exchange_rate_id' => $rate->id])
            ->assertSessionHas('error', 'เกินจำนวนที่สามารถแลกได้ต่อวัน');

        $this->assertSame(1, CoinExchangeRequest::where('user_id', $user->id)->count());
        $this->assertEquals(900.0, (float) VideoCoin::where('user_id', $user->id)->value('balance'));
    }
}
