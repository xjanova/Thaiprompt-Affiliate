<?php

namespace Tests\Feature\AdminApp\Approvals;

use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\Concerns\BuildsApprovalsWorld;
use Tests\TestCase;

/**
 * 🎫 แอปแอดมิน: ตั๋วซัพพอร์ต (TicketService ตัวเดียวกับเว็บ) + แก้บั๊กแจ้งเตือนแอดมิน (users.is_admin ไม่มีอยู่จริง)
 */
#[Group('admin-app')]
class TicketsApprovalsTest extends TestCase
{
    use BuildsAdminAppWorld;
    use BuildsApprovalsWorld;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Http::fake();
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    public function test_new_ticket_now_notifies_admins_and_support_staff_but_not_members_or_suspended(): void
    {
        $admin = $this->makeAdmin();
        $super = User::factory()->create(['role' => 'user', 'is_super_admin' => true]);
        $moderator = User::factory()->create(['role' => 'moderator']);
        $member = $this->makeMember();
        $suspendedAdmin = $this->makeAdmin();
        $suspendedAdmin->forceFill(['blocked_at' => now()])->save();

        // เดิม query users.is_admin → SQL error ถูก TicketObserver กลืน → แอดมินไม่เคยได้แจ้งเตือน
        $ticket = $this->makeTicket($member, ['priority' => 'high']);

        foreach ([$admin, $super, $moderator] as $staff) {
            $this->assertTrue(
                Notification::where('user_id', $staff->id)->where('type', 'ticket')->where('notifiable_id', $ticket->id)->exists(),
                'ทีมงาน #'.$staff->id.' ต้องได้แจ้งเตือนตั๋วใหม่'
            );
        }
        $this->assertFalse(Notification::where('user_id', $member->id)->where('type', 'ticket')->exists());
        $this->assertFalse(Notification::where('user_id', $suspendedAdmin->id)->where('type', 'ticket')->exists());

        // ตั๋วด่วนถูกถอนผู้รับผิดชอบ → แจ้งแอดมิน (เดิมพังเหมือนกัน)
        $ticket->update(['assigned_to' => $admin->id]);
        $before = Notification::where('user_id', $admin->id)->where('type', 'ticket')->count();
        $ticket->update(['assigned_to' => null]);
        $this->assertSame($before + 1, Notification::where('user_id', $admin->id)->where('type', 'ticket')->count());
    }

    public function test_customer_ticket_creation_is_throttled_so_admins_are_not_flooded(): void
    {
        $this->makeAdmin();
        $member = $this->makeMember();
        $category = TicketCategory::create(['name' => 'การเงิน', 'is_active' => true, 'sort_order' => 1]);

        $payload = fn (int $i) => ['category_id' => $category->id, 'subject' => 'เรื่องที่ '.$i, 'description' => 'รายละเอียด '.$i, 'priority' => 'medium'];

        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($member, 'web')->post(route('user.tickets.store'), $payload($i))->assertRedirect();
        }

        // ใบที่ 6 ภายใน 10 นาที → 429 ไม่สร้างตั๋ว ไม่แจ้งแอดมินเพิ่ม
        $notesBefore = Notification::where('type', 'ticket')->count();
        $this->actingAs($member, 'web')->post(route('user.tickets.store'), $payload(6))->assertStatus(429);
        $this->assertSame(5, Ticket::where('user_id', $member->id)->count());
        $this->assertSame($notesBefore, Notification::where('type', 'ticket')->count());

        // ถังแยกต่อบัญชี — ลูกค้าอีกคนยังเปิดตั๋วได้
        $this->actingAs($this->makeMember(), 'web')->post(route('user.tickets.store'), $payload(7))->assertRedirect();
    }

    public function test_list_buckets_and_detail_messages(): void
    {
        $customer = User::factory()->create(['name' => 'คุณลูกค้า']);
        $open = $this->makeTicket($customer, ['priority' => 'critical']);
        $waiting = $this->makeTicket($customer);
        $waiting->update(['status' => 'waiting_customer']);
        $closed = $this->makeTicket($customer);
        $closed->update(['status' => 'resolved']);

        $admin = $this->actAs($this->makeAdmin());
        app(TicketService::class)->addReply($open, ['user_id' => $admin->id, 'message' => 'บันทึกภายใน: เช็คกับฝ่ายการเงิน', 'is_internal_note' => true]);

        $this->getJson('/api/admin/approvals/tickets')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $open->id)
            ->assertJsonPath('data.data.0.bucket', 'open')
            ->assertJsonPath('data.data.0.priority', 'critical')
            ->assertJsonPath('data.data.0.user.name', 'คุณลูกค้า')
            ->assertJsonPath('data.data.0.replies_count', 0);
        $this->getJson('/api/admin/approvals/tickets?status=pending')->assertJsonPath('data.data.0.id', $waiting->id);
        $this->getJson('/api/admin/approvals/tickets?status=closed')->assertJsonPath('data.data.0.id', $closed->id);
        $this->getJson('/api/admin/approvals/tickets?status=all')->assertJsonPath('data.total', 3);

        $detail = $this->getJson('/api/admin/approvals/tickets/'.$open->id)->assertOk();
        $detail->assertJsonPath('data.messages.0.sender', 'customer')
            ->assertJsonPath('data.messages.0.message', 'ถอนเงินเมื่อวานยังไม่เข้าบัญชีค่ะ')
            ->assertJsonPath('data.messages.1.sender', 'staff')
            ->assertJsonPath('data.messages.1.is_internal_note', true);
        $this->assertStringNotContainsString($customer->email, $detail->getContent());
    }

    public function test_reply_validates_notifies_customer_in_thai_and_dedupes_double_tap(): void
    {
        $customer = User::factory()->create();
        $ticket = $this->makeTicket($customer);
        $admin = $this->actAs($this->makeAdmin());

        $this->postJson('/api/admin/approvals/tickets/'.$ticket->id.'/reply', ['message' => ''])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonPath('message', 'กรุณาพิมพ์ข้อความตอบกลับ');

        // ข้อความไทยยาวเกิน 100 ไบต์ — เดิม substr ตัดกลางตัวอักษร → JSON พัง → แจ้งเตือนหายเงียบ
        $message = 'สวัสดีค่ะ ทีมงานตรวจสอบรายการถอนเงินของคุณแล้ว เงินจะเข้าบัญชีภายในวันนี้ ขออภัยในความล่าช้าค่ะ';
        $res = $this->postJson('/api/admin/approvals/tickets/'.$ticket->id.'/reply', ['message' => $message])
            ->assertCreated()
            ->assertJsonPath('data.reply.sender', 'staff')
            ->assertJsonPath('data.duplicate', false);

        $this->assertSame(1, TicketReply::where('ticket_id', $ticket->id)->count());
        $this->assertNotNull($ticket->fresh()->first_response_at, 'บันทึกเวลาตอบครั้งแรกของทีมงาน');

        $note = Notification::where('user_id', $customer->id)->where('type', 'ticket')->latest('id')->first();
        $this->assertNotNull($note, 'ลูกค้าต้องได้แจ้งเตือนเมื่อแอดมินตอบ');
        $this->assertSame('แอดมินตอบกลับตั๋วซัพพอร์ตของคุณ', $note->title);
        $this->assertSame(mb_substr($message, 0, 100), $note->data['reply_preview']);

        // กดส่งซ้ำ → คืนรายการเดิม ไม่ส่งถึงลูกค้าซ้ำ
        $this->postJson('/api/admin/approvals/tickets/'.$ticket->id.'/reply', ['message' => $message])
            ->assertOk()
            ->assertJsonPath('data.duplicate', true)
            ->assertJsonPath('data.reply.id', $res->json('data.reply.id'));
        $this->assertSame(1, TicketReply::where('ticket_id', $ticket->id)->count());
    }

    public function test_internal_note_never_notifies_the_customer(): void
    {
        $customer = User::factory()->create();
        $ticket = $this->makeTicket($customer);
        $admin = $this->makeAdmin();

        app(TicketService::class)->addReply($ticket, ['user_id' => $admin->id, 'message' => 'ลูกค้ารายนี้เคยโกง ระวัง', 'is_internal_note' => true]);

        $this->assertFalse(Notification::where('user_id', $customer->id)->where('type', 'ticket')->exists());
    }

    public function test_status_change_validates_and_repeat_is_idempotent(): void
    {
        $ticket = $this->makeTicket(User::factory()->create());
        $this->actAs($this->makeAdmin());

        $this->postJson('/api/admin/approvals/tickets/'.$ticket->id.'/status', ['status' => 'archived'])
            ->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_ERROR');

        $this->postJson('/api/admin/approvals/tickets/'.$ticket->id.'/status', ['status' => 'resolved', 'resolution_notes' => 'โอนเงินคืนแล้ว'])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved')
            ->assertJsonPath('data.status_label', 'แก้ไขแล้ว');

        $fresh = $ticket->fresh();
        $this->assertSame('resolved', $fresh->status);
        $this->assertNotNull($fresh->resolved_at);
        $this->assertSame('โอนเงินคืนแล้ว', $fresh->resolution_notes);

        $this->postJson('/api/admin/approvals/tickets/'.$ticket->id.'/status', ['status' => 'resolved'])
            ->assertOk()->assertJsonPath('data.already', true);
        $this->getJson('/api/admin/approvals/tickets')->assertJsonPath('data.total', 0);
        $this->assertInstanceOf(Ticket::class, $fresh);
    }
}
