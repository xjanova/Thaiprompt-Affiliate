<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'role' => 'user',
            'is_super_admin' => false,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        // Create test category
        $this->category = TicketCategory::create([
            'name' => 'Test Category',
            'icon' => 'fa-solid fa-test',
            'description' => 'Test category description',
            'color' => '#3B82F6',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_can_view_tickets_index()
    {
        $response = $this->actingAs($this->user)
            ->get(route('user.tickets.index'));

        $response->assertStatus(200);
        $response->assertViewIs('user.tickets.index');
    }

    /** @test */
    public function user_can_view_create_ticket_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('user.tickets.create'));

        $response->assertStatus(200);
        $response->assertViewIs('user.tickets.create');
        $response->assertViewHas('categories');
    }

    /** @test */
    public function user_can_create_ticket()
    {
        $response = $this->actingAs($this->user)
            ->post(route('user.tickets.store'), [
                'category_id' => $this->category->id,
                'subject' => 'Test Ticket Subject',
                'description' => 'Test ticket description',
                'priority' => 'medium',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'user_id' => $this->user->id,
            'subject' => 'Test Ticket Subject',
            'status' => 'open',
        ]);
    }

    /** @test */
    public function user_can_view_their_own_ticket()
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Ticket',
            'description' => 'Test Description',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.tickets.show', $ticket));

        $response->assertStatus(200);
        $response->assertViewIs('user.tickets.show');
        $response->assertViewHas('ticket');
    }

    /** @test */
    public function user_cannot_view_other_users_ticket()
    {
        $otherUser = User::factory()->create();

        $ticket = Ticket::create([
            'user_id' => $otherUser->id,
            'category_id' => $this->category->id,
            'subject' => 'Other User Ticket',
            'description' => 'Test Description',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.tickets.show', $ticket));

        $response->assertStatus(404);
    }

    /** @test */
    public function admin_can_view_tickets_index()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.tickets.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.tickets.index');
    }

    /** @test */
    public function admin_can_view_any_ticket()
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Ticket',
            'description' => 'Test Description',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.tickets.show', $ticket));

        $response->assertStatus(200);
        $response->assertViewIs('admin.tickets.show');
    }

    /** @test */
    public function admin_can_assign_ticket()
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Ticket',
            'description' => 'Test Description',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.tickets.assign', $ticket), [
                'assigned_to' => $this->admin->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_to' => $this->admin->id,
        ]);
    }

    /** @test */
    public function admin_can_update_ticket_status()
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Ticket',
            'description' => 'Test Description',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.tickets.update-status', $ticket), [
                'status' => 'in_progress',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
        ]);
    }

    /** @test */
    public function user_can_reply_to_ticket()
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Ticket',
            'description' => 'Test Description',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('user.tickets.reply', $ticket), [
                'message' => 'This is a test reply',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'message' => 'This is a test reply',
            'is_internal_note' => false,
        ]);
    }

    /** @test */
    public function admin_can_add_internal_note()
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Ticket',
            'description' => 'Test Description',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.tickets.reply', $ticket), [
                'message' => 'This is an internal note',
                'is_internal_note' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->admin->id,
            'message' => 'This is an internal note',
            'is_internal_note' => true,
        ]);
    }

    /** @test */
    public function ticket_number_is_generated_automatically()
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Ticket',
            'description' => 'Test Description',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $this->assertNotNull($ticket->ticket_number);
        $this->assertStringStartsWith('TKT-', $ticket->ticket_number);
    }

    /** @test */
    public function ticket_priority_has_correct_color()
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Ticket',
            'description' => 'Test Description',
            'priority' => 'critical',
            'status' => 'open',
        ]);

        $this->assertEquals('red', $ticket->priority_color);
    }

    /** @test */
    public function ticket_status_has_correct_label()
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Ticket',
            'description' => 'Test Description',
            'priority' => 'medium',
            'status' => 'in_progress',
        ]);

        $this->assertEquals('กำลังดำเนินการ', $ticket->status_label);
    }
}
