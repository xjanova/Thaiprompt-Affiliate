<?php

namespace Tests\Feature\User;

use App\Models\EmailPreference;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * หน้าแจ้งเตือน + ตั้งค่าอีเมล ฝั่งสมาชิกในธีมโนวา
 */
class NotificationEmailNovaPagesTest extends TestCase
{
    use RefreshDatabase;

    private function member(): User
    {
        return User::factory()->create(['role' => 'user', 'is_super_admin' => false]);
    }

    private function notify(User $user, array $attrs = []): Notification
    {
        return Notification::create(array_merge(['user_id' => $user->id, 'type' => 'system', 'title' => 'หัวข้อ', 'message' => 'ข้อความ'], $attrs));
    }

    // ── แจ้งเตือน ────────────────────────────────────────────────

    public function test_member_notifications_page_uses_the_nova_view(): void
    {
        $user = $this->member();
        $this->notify($user, ['title' => 'เงินเข้าแล้ว', 'type' => 'deposit']);

        $this->actingAs($user)->get(route('user.notifications.index'))
            ->assertOk()
            ->assertViewIs('user.notifications.index')
            ->assertSee('nvm-body', false)
            ->assertSee('เงินเข้าแล้ว')
            ->assertSee('fa-arrow-down', false);
    }

    public function test_unread_and_important_filters(): void
    {
        $user = $this->member();
        $this->notify($user, ['title' => 'แจ้งเตือนเก่า-A1', 'is_read' => true]);
        $this->notify($user, ['title' => 'แจ้งเตือนใหม่-B2']);
        $this->notify($user, ['title' => 'แจ้งเตือนเด่น-C3', 'is_read' => true, 'is_important' => true]);

        $this->actingAs($user)->get(route('user.notifications.index', ['filter' => 'unread']))
            ->assertOk()->assertSee('แจ้งเตือนใหม่-B2')->assertDontSee('แจ้งเตือนเก่า-A1')->assertDontSee('แจ้งเตือนเด่น-C3');

        $this->actingAs($user)->get(route('user.notifications.index', ['filter' => 'important']))
            ->assertOk()->assertSee('แจ้งเตือนเด่น-C3')->assertDontSee('แจ้งเตือนใหม่-B2');

        // ค่าแปลกๆ = ทั้งหมด
        $this->actingAs($user)->get(route('user.notifications.index', ['filter' => 'x']))
            ->assertOk()->assertSee('แจ้งเตือนเก่า-A1')->assertSee('แจ้งเตือนใหม่-B2');
    }

    public function test_unsafe_action_links_are_not_rendered(): void
    {
        $user = $this->member();
        $this->notify($user, ['action_url' => 'javascript:alert(1)', 'action_text' => 'กดเลย']);

        $res = $this->actingAs($user)->get(route('user.notifications.index'))->assertOk();
        $this->assertStringNotContainsString('javascript:alert', $res->getContent());
    }

    public function test_bulk_mark_as_read_from_the_page_leaves_a_toast_for_the_reload(): void
    {
        $user = $this->member();
        $a = $this->notify($user);
        $b = $this->notify($user);

        $this->actingAs($user)
            ->postJson(route('user.notifications.bulk-mark-as-read'), ['notification_ids' => [$a->id, $b->id]])
            ->assertOk()->assertJson(['success' => true, 'count' => 2])
            ->assertSessionHas('success');

        $this->assertSame(0, $user->fresh()->unread_notifications_count);
    }

    // ── ตั้งค่าอีเมล ─────────────────────────────────────────────

    public function test_email_preferences_page_renders_every_topic(): void
    {
        $this->actingAs($this->member())->get(route('user.email.preferences'))
            ->assertOk()
            ->assertSee('nvm-body', false)
            ->assertSee('ความปลอดภัยบัญชี')
            ->assertSee('name="marketing_emails"', false)
            ->assertSee('form="nvm-ep-form"', false);
    }

    public function test_paused_emails_show_a_warning_and_keep_topic_choices(): void
    {
        $user = $this->member();
        $pref = EmailPreference::getOrCreateForUser($user->id);
        $pref->update(['marketing_emails' => false]);

        $this->actingAs($user)->post(route('user.email.preferences.disable-all'))->assertRedirect();

        $pref->refresh();
        $this->assertFalse($pref->all_emails);
        $this->assertTrue($pref->commission_notifications, 'หยุดทั้งหมดต้องไม่ล้างหมวดที่เลือกไว้');

        $this->actingAs($user)->get(route('user.email.preferences'))
            ->assertOk()
            ->assertSee('ตอนนี้ปิดรับอีเมลทุกเรื่อง');
    }
}
