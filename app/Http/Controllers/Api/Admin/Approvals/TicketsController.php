<?php

namespace App\Http\Controllers\Api\Admin\Approvals;

use App\Http\Controllers\Api\Admin\Approvals\Concerns\ApprovalResponses;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\TicketService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 🎫 แอปแอดมิน: ตั๋วซัพพอร์ต — ตอบ/เปลี่ยนสถานะผ่าน TicketService ตัวเดียวกับหลังบ้านเว็บ Admin\TicketController
 *
 * กองสถานะของแอป (สถานะจริงในตาราง → กอง):
 *   open    = open · in_progress        (รอทีมงานทำ)
 *   pending = waiting_customer          (รอลูกค้าตอบ)
 *   closed  = resolved · closed
 * สิทธิ์: แอดมิน (เว็บใช้ role:admin,super_admin เหมือนกัน — ไม่มีสิทธิ์ย่อย)
 */
class TicketsController extends Controller
{
    use ApprovalResponses;

    public const BUCKETS = [
        'open' => ['open', 'in_progress'],
        'pending' => ['waiting_customer'],
        'closed' => ['resolved', 'closed'],
    ];

    /** สถานะที่ตั้งได้ — ชุดเดียวกับ validation ของหน้าเว็บ (admin.tickets.update-status) */
    public const STATUSES = ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'];

    /** กันกดส่งซ้ำ: ข้อความเดิมจากแอดมินคนเดิมในตั๋วเดิมภายในช่วงนี้ = คืนรายการเดิม ไม่สร้างใหม่ */
    private const REPLY_DEDUPE_SECONDS = 60;

    public function __construct(private readonly TicketService $tickets) {}

    /**
     * GET /api/admin/approvals/tickets?status=open|pending|closed|all&priority=&search=&page=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->validateInput($request, [
            'status' => 'nullable|in:open,pending,closed,all',
            'priority' => 'nullable|in:low,medium,high,critical',
            'search' => 'nullable|string|max:100',
        ] + $this->pagingRules(), [
            'status.in' => 'สถานะไม่ถูกต้อง',
            'priority.in' => 'ความสำคัญไม่ถูกต้อง',
        ]);

        $bucket = (string) ($data['status'] ?? 'open');

        $query = Ticket::query()
            ->with(['user:id,name,member_number', 'assignedTo:id,name', 'category:id,name'])
            ->withCount(['replies as public_replies_count' => fn ($q) => $q->where('is_internal_note', false)]);

        self::applyBucket($query, $bucket);

        if (! empty($data['priority'])) {
            $query->where('priority', $data['priority']);
        }

        if (! empty($data['search'])) {
            $query->search(trim((string) $data['search']));
        }

        if ($bucket === 'closed') {
            $query->orderByDesc('updated_at')->orderByDesc('id');
        } else {
            // คิวงาน: ด่วนก่อน แล้วรอนานสุดก่อน
            $query->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
                ->orderByRaw('COALESCE(last_reply_at, created_at) ASC')
                ->orderBy('id');
        }

        $page = $query->paginate((int) ($data['per_page'] ?? 20));

        return $this->paged($page, $page->getCollection()->map(fn (Ticket $t) => $this->listItem($t))->all());
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public static function applyBucket(Builder $query, string $bucket): Builder
    {
        if (isset(self::BUCKETS[$bucket])) {
            $query->whereIn('status', self::BUCKETS[$bucket]);
        }

        return $query;
    }

    /**
     * GET /api/admin/approvals/tickets/{ticket}
     */
    public function show(Ticket $ticket): JsonResponse
    {
        $ticket->load(['user:id,name,member_number', 'assignedTo:id,name', 'category:id,name', 'replies.user:id,name,role,is_super_admin']);
        $ticket->loadCount(['replies as public_replies_count' => fn ($q) => $q->where('is_internal_note', false)]);

        $messages = [[
            'id' => null,
            'sender' => 'customer',
            'author' => $ticket->user?->name,
            'message' => (string) $ticket->description,
            'is_internal_note' => false,
            'at' => $this->iso($ticket->created_at),
        ]];

        foreach ($ticket->replies->sortBy('id') as $reply) {
            $messages[] = $this->replyItem($reply);
        }

        return $this->ok($this->listItem($ticket) + [
            'description' => (string) $ticket->description,
            'resolution_notes' => $ticket->resolution_notes,
            'due_at' => $this->iso($ticket->due_at),
            'first_response_at' => $this->iso($ticket->first_response_at),
            'resolved_at' => $this->iso($ticket->resolved_at),
            'closed_at' => $this->iso($ticket->closed_at),
            'messages' => $messages,
            'statuses' => array_map(fn (string $s) => ['key' => $s, 'label' => self::statusLabel($s)], self::STATUSES),
        ]);
    }

    /**
     * POST /api/admin/approvals/tickets/{ticket}/reply  body: { message }
     *
     * ตอบลูกค้า (ไม่ใช่บันทึกภายใน) — TicketService::addReply บันทึกเวลาตอบครั้งแรก + แจ้งลูกค้า
     */
    public function reply(Request $request, Ticket $ticket): JsonResponse
    {
        $data = $this->validateInput($request, [
            'message' => ['required', 'string', 'max:10000'],
        ], [
            'message.required' => 'กรุณาพิมพ์ข้อความตอบกลับ',
            'message.max' => 'ข้อความยาวเกินไป (ไม่เกิน 10,000 ตัวอักษร)',
        ]);

        $message = trim((string) $data['message']);
        if ($message === '') {
            return $this->fail('กรุณาพิมพ์ข้อความตอบกลับ', 'VALIDATION_ERROR', 422, null, ['message' => ['กรุณาพิมพ์ข้อความตอบกลับ']]);
        }

        // กดส่งซ้ำ/เน็ตหลุดแล้วลองใหม่ → คืนข้อความเดิม ไม่ส่งซ้ำถึงลูกค้า
        $duplicate = TicketReply::query()
            ->where('ticket_id', $ticket->id)
            ->where('user_id', $request->user()->id)
            ->where('is_internal_note', false)
            ->where('message', $message)
            ->where('created_at', '>=', now()->subSeconds(self::REPLY_DEDUPE_SECONDS))
            ->latest('id')
            ->first();

        if ($duplicate) {
            return $this->ok(['reply' => $this->replyItem($duplicate->load('user:id,name,role,is_super_admin')), 'duplicate' => true], 'ส่งข้อความนี้ไปแล้ว');
        }

        try {
            $reply = $this->tickets->addReply($ticket, [
                'user_id' => $request->user()->id,
                'message' => $message,
                'is_internal_note' => false,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError('ticket_reply', $e, ['ticket_id' => $ticket->id]);
        }

        return $this->ok([
            'reply' => $this->replyItem($reply->load('user:id,name,role,is_super_admin')),
            'duplicate' => false,
            'ticket' => ['id' => (int) $ticket->id, 'status' => (string) $ticket->fresh()->status],
        ], 'เพิ่มข้อความตอบกลับเรียบร้อยแล้ว', 201);
    }

    /**
     * POST /api/admin/approvals/tickets/{ticket}/status  body: { status, resolution_notes? }
     */
    public function status(Request $request, Ticket $ticket): JsonResponse
    {
        $data = $this->validateInput($request, [
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'resolution_notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'status.required' => 'กรุณาเลือกสถานะ',
            'status.in' => 'สถานะไม่ถูกต้อง',
            'resolution_notes.max' => 'บันทึกการแก้ไขยาวเกินไป (ไม่เกิน 5,000 ตัวอักษร)',
        ]);

        $status = (string) $data['status'];

        // สถานะเดิมอยู่แล้ว → ไม่เรียก service ซ้ำ (กันแจ้งลูกค้า/ขอคะแนนซ้ำ)
        if ($ticket->status === $status) {
            return $this->ok(['id' => (int) $ticket->id, 'status' => $status, 'status_label' => self::statusLabel($status), 'already' => true], 'ตั๋วอยู่ในสถานะนี้อยู่แล้ว');
        }

        try {
            $this->tickets->changeStatus($ticket, $status, $data['resolution_notes'] ?? null);
        } catch (\Throwable $e) {
            return $this->serverError('ticket_status', $e, ['ticket_id' => $ticket->id]);
        }

        return $this->ok(['id' => (int) $ticket->id, 'status' => $status, 'status_label' => self::statusLabel($status), 'already' => false], 'อัปเดตสถานะตั๋วเรียบร้อยแล้ว');
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'open' => 'เปิด',
            'in_progress' => 'กำลังดำเนินการ',
            'waiting_customer' => 'รอลูกค้า',
            'resolved' => 'แก้ไขแล้ว',
            'closed' => 'ปิด',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function listItem(Ticket $ticket): array
    {
        $bucket = 'closed';
        foreach (self::BUCKETS as $name => $statuses) {
            if (in_array($ticket->status, $statuses, true)) {
                $bucket = $name;
                break;
            }
        }

        $waitingFrom = $ticket->last_reply_at ?? $ticket->created_at;

        return [
            'id' => (int) $ticket->id,
            'ticket_number' => (string) $ticket->ticket_number,
            'subject' => (string) $ticket->subject,
            'status' => (string) $ticket->status,
            'status_label' => $ticket->status_label,
            'bucket' => $bucket,
            'priority' => (string) $ticket->priority,
            'priority_label' => $ticket->priority_label,
            'category' => $ticket->category?->name,
            'user' => $this->personRef($ticket->user),
            'assigned_to' => $ticket->assignedTo ? ['id' => (int) $ticket->assignedTo->id, 'name' => (string) $ticket->assignedTo->name] : null,
            'replies_count' => (int) ($ticket->public_replies_count ?? 0),
            'is_overdue' => (bool) $ticket->is_overdue,
            'created_at' => $this->iso($ticket->created_at),
            'last_reply_at' => $this->iso($ticket->last_reply_at),
            'waiting_minutes' => $bucket === 'closed' ? null : $this->minutesSince($waitingFrom),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function replyItem(TicketReply $reply): array
    {
        return [
            'id' => (int) $reply->id,
            'sender' => $reply->isFromStaff() ? 'staff' : 'customer',
            'author' => $reply->user?->name,
            'message' => (string) $reply->message,
            'is_internal_note' => (bool) $reply->is_internal_note,
            'at' => $this->iso($reply->created_at),
        ];
    }
}
