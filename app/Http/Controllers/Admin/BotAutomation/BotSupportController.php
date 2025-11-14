<?php

namespace App\Http\Controllers\Admin\BotAutomation;

use App\Http\Controllers\Controller;
use App\Models\BotAutomation\BotSupportTicket;
use App\Models\BotAutomation\BotSupportResponse;
use Illuminate\Http\Request;

class BotSupportController extends Controller
{
    /**
     * Display the support dashboard
     */
    public function index()
    {
        $statistics = [
            'total_tickets' => BotSupportTicket::count(),
            'open_tickets' => BotSupportTicket::where('status', 'open')->count(),
            'pending_tickets' => BotSupportTicket::where('status', 'pending')->count(),
            'resolved_tickets' => BotSupportTicket::where('status', 'resolved')->count(),
            'avg_response_time' => $this->calculateAverageResponseTime(),
        ];

        $recentTickets = BotSupportTicket::with(['user'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.bot-automation.support.index', compact('statistics', 'recentTickets'));
    }

    /**
     * Display all support tickets
     */
    public function tickets(Request $request)
    {
        $tickets = BotSupportTicket::query()
            ->with(['user'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->priority, function ($query, $priority) {
                $query->where('priority', $priority);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20);

        return view('admin.bot-automation.support.tickets.index', compact('tickets'));
    }

    /**
     * Display a single ticket
     */
    public function show(BotSupportTicket $ticket)
    {
        $ticket->load(['user', 'responses.user']);

        return view('admin.bot-automation.support.tickets.show', compact('ticket'));
    }

    /**
     * Store a response to a ticket
     */
    public function respond(Request $request, BotSupportTicket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'is_internal' => 'boolean',
        ]);

        $response = $ticket->responses()->create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_internal' => $validated['is_internal'] ?? false,
        ]);

        // Update ticket status if not internal
        if (!$response->is_internal) {
            $ticket->update([
                'status' => 'pending',
                'last_response_at' => now(),
            ]);
        }

        return back()->with('success', 'Response added successfully');
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Request $request, BotSupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,pending,resolved,closed',
        ]);

        $ticket->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Ticket status updated successfully');
    }

    /**
     * Update ticket priority
     */
    public function updatePriority(Request $request, BotSupportTicket $ticket)
    {
        $validated = $request->validate([
            'priority' => 'required|in:low,normal,high,urgent',
        ]);

        $ticket->update([
            'priority' => $validated['priority'],
        ]);

        return back()->with('success', 'Ticket priority updated successfully');
    }

    /**
     * Assign ticket to a staff member
     */
    public function assign(Request $request, BotSupportTicket $ticket)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $ticket->update([
            'assigned_to' => $validated['assigned_to'],
        ]);

        return back()->with('success', 'Ticket assigned successfully');
    }

    /**
     * ส่งออกข้อมูลทิกเก็ตเป็นไฟล์ JSON
     *
     * @param BotSupportTicket $ticket
     * @return \Illuminate\Http\Response
     */
    public function export(BotSupportTicket $ticket)
    {
        $ticket->load(['user', 'responses.user']);

        $data = [
            'ticket_id' => $ticket->id,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'created_by' => [
                'id' => $ticket->user->id,
                'name' => $ticket->user->name,
                'email' => $ticket->user->email,
            ],
            'created_at' => $ticket->created_at->toDateTimeString(),
            'responses' => $ticket->responses->map(function ($response) {
                return [
                    'user' => $response->user->name,
                    'message' => $response->message,
                    'is_internal' => $response->is_internal,
                    'created_at' => $response->created_at->toDateTimeString(),
                ];
            }),
            'exported_at' => now()->toDateTimeString(),
        ];

        $filename = 'ticket-' . $ticket->id . '-' . now()->format('Y-m-d') . '.json';

        return response()
            ->json($data, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * ลบทิกเก็ต
     *
     * @param BotSupportTicket $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(BotSupportTicket $ticket)
    {
        $ticket->delete();

        return redirect()
            ->route('admin.bot-automation.support.tickets.index')
            ->with('success', 'ลบทิกเก็ตสำเร็จ');
    }

    /**
     * Calculate average response time
     */
    protected function calculateAverageResponseTime()
    {
        $tickets = BotSupportTicket::whereNotNull('first_response_at')->get();

        if ($tickets->isEmpty()) {
            return 0;
        }

        $totalMinutes = $tickets->sum(function ($ticket) {
            return $ticket->created_at->diffInMinutes($ticket->first_response_at);
        });

        return round($totalMinutes / $tickets->count(), 2);
    }
}
