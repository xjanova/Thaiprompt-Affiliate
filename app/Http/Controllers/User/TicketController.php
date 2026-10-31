<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\TicketService;
use Exception;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Display user's tickets
     */
    public function index(Request $request)
    {
        // ตัวกรอง: active = ยังไม่ปิด (ตรงกับตัวเลข "เปิดอยู่"), done = แก้ไขแล้ว/ปิด, หรือสถานะเดี่ยว
        // 🐛 (2026-10-29) เดิมตัวเลข "เปิดอยู่" นับ open+in_progress+waiting_customer แต่แท็บ "เปิดอยู่" กรองแค่ open
        //    และไม่มีแท็บ "รอข้อมูลจากคุณ" / "ปิดแล้ว" เลย
        $status = $request->input('status');
        $allowed = ['active', 'done', 'open', 'in_progress', 'waiting_customer', 'resolved', 'closed'];
        $filters = [
            'status' => in_array($status, $allowed, true) ? $status : null,
        ];

        $query = Ticket::with(['category', 'assignedTo'])
            ->where('user_id', auth()->id());

        if ($filters['status'] === 'active') {
            $query->open();
        } elseif ($filters['status'] === 'done') {
            $query->closed();
        } elseif ($filters['status']) {
            $query->status($filters['status']);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15);

        $stats = [
            'total' => Ticket::where('user_id', auth()->id())->count(),
            'open' => Ticket::where('user_id', auth()->id())->open()->count(),
            'closed' => Ticket::where('user_id', auth()->id())->closed()->count(),
            'waiting' => Ticket::where('user_id', auth()->id())->status('waiting_customer')->count(),
        ];

        return view('user.tickets.index', compact('tickets', 'filters', 'stats'));
    }

    /**
     * Show create ticket form
     */
    public function create()
    {
        $categories = TicketCategory::active()->ordered()->get();

        return view('user.tickets.create', compact('categories'));
    }

    /**
     * Store new ticket
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:ticket_categories,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        try {
            $ticket = $this->ticketService->createTicket([
                'user_id' => auth()->id(),
                'category_id' => $request->input('category_id'),
                'subject' => $request->input('subject'),
                'description' => $request->input('description'),
                'priority' => $request->input('priority', 'medium'),
            ]);

            return redirect()->route('user.tickets.show', $ticket->id)
                ->with('success', 'สร้างตั๋วสำเร็จ! ทีมงานจะติดต่อกลับโดยเร็วที่สุด');
        } catch (Exception $e) {
            // ไม่โชว์ข้อความ exception ดิบ (อาจเป็น SQL) ให้ลูกค้า
            \Illuminate\Support\Facades\Log::error('User ticket store failed', ['user_id' => auth()->id(), 'error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'สร้างตั๋วไม่สำเร็จ กรุณาลองใหม่อีกครั้ง')
                ->withInput();
        }
    }

    /**
     * Show ticket details
     */
    public function show($id)
    {
        $ticket = Ticket::with(['category', 'assignedTo', 'publicReplies.user'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        // Mark all staff replies as read
        foreach ($ticket->publicReplies as $reply) {
            if ($reply->user_id !== auth()->id()) {
                $reply->markAsRead();
            }
        }

        return view('user.tickets.show', compact('ticket'));
    }

    /**
     * Add reply to ticket
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $ticket = Ticket::where('user_id', auth()->id())->findOrFail($id);

        // ตั๋วที่ "แก้ไขแล้ว" ตอบต่อได้ (ระบบเปิดตั๋วให้ใหม่) · ตั๋วที่ "ปิด" แล้วต้องสร้างใหม่ ตามที่หน้าตั๋วบอกลูกค้า
        if ($ticket->status === 'closed') {
            return redirect()->route('user.tickets.show', $id)
                ->with('error', 'ตั๋วนี้ปิดแล้ว หากยังต้องการความช่วยเหลือ กรุณาสร้างตั๋วใหม่');
        }

        try {
            $this->ticketService->addReply($ticket, [
                'user_id' => auth()->id(),
                'message' => $request->input('message'),
                'is_internal_note' => false,
            ]);

            return redirect()->route('user.tickets.show', $id)
                ->with('success', 'ส่งข้อความเรียบร้อยแล้ว');
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('User ticket reply failed', ['ticket_id' => $id, 'error' => $e->getMessage()]);

            return redirect()->back()->withInput()->with('error', 'ส่งข้อความไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }
    }

    /**
     * Close ticket
     */
    public function close($id)
    {
        try {
            $ticket = Ticket::where('user_id', auth()->id())->findOrFail($id);

            // 🐛 isClosed() นับ "แก้ไขแล้ว" เป็นปิดด้วย → ลูกค้าปิดตั๋วที่แก้ไขแล้วไม่ได้ ("ถูกปิดไปแล้ว")
            if ($ticket->status === 'closed') {
                return redirect()->back()->with('error', 'ตั๋วนี้ถูกปิดไปแล้ว');
            }

            $this->ticketService->changeStatus($ticket, 'closed');

            return redirect()->route('user.tickets.show', $id)
                ->with('success', 'ปิดตั๋วเรียบร้อยแล้ว');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404);
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('User ticket close failed', ['ticket_id' => $id, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'ปิดตั๋วไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }
    }
}
