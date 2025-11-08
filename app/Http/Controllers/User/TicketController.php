<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Exception;

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
        $filters = [
            'status' => $request->input('status'),
        ];

        $query = Ticket::with(['category', 'assignedTo'])
            ->where('user_id', auth()->id());

        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15);

        $stats = [
            'total' => Ticket::where('user_id', auth()->id())->count(),
            'open' => Ticket::where('user_id', auth()->id())->open()->count(),
            'closed' => Ticket::where('user_id', auth()->id())->closed()->count(),
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
            'description' => 'required|string',
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
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage())
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
            'message' => 'required|string',
        ]);

        try {
            $ticket = Ticket::where('user_id', auth()->id())->findOrFail($id);

            $this->ticketService->addReply($ticket, [
                'user_id' => auth()->id(),
                'message' => $request->input('message'),
                'is_internal_note' => false,
            ]);

            return redirect()->route('user.tickets.show', $id)
                ->with('success', 'ส่งข้อความเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Close ticket
     */
    public function close($id)
    {
        try {
            $ticket = Ticket::where('user_id', auth()->id())->findOrFail($id);

            if ($ticket->isClosed()) {
                return redirect()->back()->with('error', 'ตั๋วนี้ถูกปิดไปแล้ว');
            }

            $this->ticketService->changeStatus($ticket, 'closed');

            return redirect()->route('user.tickets.show', $id)
                ->with('success', 'ปิดตั๋วเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
