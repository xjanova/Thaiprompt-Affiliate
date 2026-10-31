<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * หน้าโปรไฟล์สมาชิกธีมโนวา (/user/profile)
 */
class ProfileNovaPageTest extends TestCase
{
    use RefreshDatabase;

    /** ไฟล์ภาพที่มีอยู่จริงใน public ใช้แทนรูปที่สมาชิกอัปโหลด */
    private const UPLOADED_PICTURE = 'images/nova/account/key.webp';

    private const LINE_PICTURE = 'https://profile.line-scdn.net/qa-picture';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Mail::fake();
        Http::fake();
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user'], $attributes));
    }

    public function test_page_uses_nova_layout_and_shows_the_member_name(): void
    {
        $user = $this->member(['name' => 'สมศรี ทดสอบ']);

        $this->actingAs($user)->get(route('user.profile'))
            ->assertOk()
            ->assertViewIs('user.profile')
            ->assertSee('nvm-pf-gauge', false)
            ->assertSee('สมศรี ทดสอบ');
    }

    public function test_date_of_birth_is_prefilled_in_the_format_date_inputs_accept(): void
    {
        $user = $this->member(['date_of_birth' => '1990-05-14']);

        // input type=date ไม่รับ "Y-m-d H:i:s" — ช่องจะว่างแล้ววันเกิดหายตอนบันทึกเรื่องอื่น
        $this->actingAs($user)->get(route('user.profile'))
            ->assertOk()
            ->assertSee('value="1990-05-14"', false);
    }

    public function test_profile_saves_without_any_address(): void
    {
        $user = $this->member(['name' => 'ชื่อเดิม', 'email' => 'owner@gmail.com']);

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => 'ชื่อใหม่',
            'email' => 'owner@gmail.com',
        ])->assertRedirect(route('user.profile'))->assertSessionHasNoErrors();

        $this->assertSame('ชื่อใหม่', $user->fresh()->name);
    }

    public function test_address_picker_is_optional_on_this_page(): void
    {
        $html = $this->actingAs($this->member())->get(route('user.profile'))->assertOk()->getContent();

        $this->assertStringContainsString('nvm-addr', $html);
        $this->assertStringNotContainsString('text-red-500">*', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/\srequired>/',
            Blade::render('<x-thai-address-picker province-field="state" :required="false" />')
        );
    }

    public function test_address_picker_stays_required_by_default(): void
    {
        $html = Blade::render('<x-thai-address-picker province-field="state" district-field="city" />');

        $this->assertMatchesRegularExpression('/\srequired>/', $html);
        $this->assertStringContainsString('text-red-500">*', $html);
    }

    public function test_address_picker_escapes_saved_values(): void
    {
        $html = Blade::render(
            '<x-thai-address-picker :province-value="$value" />',
            ['value' => '");alert(1);("']
        );

        $this->assertStringNotContainsString('");alert(1);("', $html);
    }

    public function test_uploaded_picture_wins_over_the_line_picture(): void
    {
        $user = $this->member([
            'profile_picture' => self::UPLOADED_PICTURE,
            'line_picture_url' => self::LINE_PICTURE,
        ]);

        $this->assertStringContainsString(self::UPLOADED_PICTURE, $user->profile_picture_url);
    }

    public function test_line_picture_is_used_when_the_uploaded_file_is_gone(): void
    {
        $user = $this->member([
            'profile_picture' => 'avatars/no-such-file.webp',
            'line_picture_url' => self::LINE_PICTURE,
        ]);

        $this->assertSame(self::LINE_PICTURE, $user->profile_picture_url);
    }

    public function test_member_without_a_picture_gets_the_theme_frame(): void
    {
        $user = $this->member(['profile_picture' => null, 'line_picture_url' => null]);

        $this->actingAs($user)->get(route('user.profile'))
            ->assertOk()
            ->assertSee('nvm-pf-av__img is-art', false)
            ->assertSee('images/nova/account/profile.webp', false);
    }

    public function test_id_card_number_is_masked(): void
    {
        $user = $this->member(['kyc_status' => 'approved', 'id_card_number' => '1103700123456']);

        $this->actingAs($user)->get(route('user.profile'))
            ->assertOk()
            ->assertSee('1-1xxx-xxxxx-45-6')
            ->assertDontSee('1103700123456');
    }

    public function test_training_badge_links_to_the_classroom_only_when_certified(): void
    {
        $user = $this->member();
        $classroomLink = 'class="nvm-pill nvm-pill--glass" href="'.route('user.classroom.index').'"';

        $this->actingAs($user)->get(route('user.profile'))
            ->assertOk()
            ->assertDontSee('data-training-badge', false);

        DB::table('classroom_certifications')->insert([
            'user_id' => $user->id,
            'role' => 'seller',
            'rules_version' => '1',
            'certified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Services\Classroom\TrainingBadge::forget($user->id);

        $this->actingAs($user)->get(route('user.profile'))
            ->assertOk()
            ->assertSee('data-training-badge="seller"', false)
            ->assertSee($classroomLink, false);
    }

    public function test_finished_profile_hides_the_checklist(): void
    {
        $user = $this->member([
            'email' => 'owner@gmail.com',
            'email_verified_at' => now(),
            'phone' => '0812345678',
            'date_of_birth' => '1990-05-14',
            'line_picture_url' => self::LINE_PICTURE,
            'address' => '99/1',
            'state' => 'นครราชสีมา',
            'kyc_status' => 'approved',
        ]);

        $this->actingAs($user)->get(route('user.profile'))
            ->assertOk()
            ->assertSee('ครบ 6/6')
            ->assertDontSee('nvm-pf-list', false);
    }
}
