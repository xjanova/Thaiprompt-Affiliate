<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
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
     * Display all tickets
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->input('status'),
            'priority' => $request->input('priority'),
            'category_id' => $request->input('category_id'),
            'assigned_to' => $request->input('assigned_to'),
            'search' => $request->input('search'),
            'unassigned' => $request->input('unassigned'),
        ];

        $tickets = $this->ticketService->getFilteredTickets($filters)->paginate(20);
        $stats = $this->ticketService->getStatistics();
        $categories = TicketCategory::active()->ordered()->get();
        $staffUsers = User::where('is_super_admin', true)
            ->orWhere('role', 'admin')
            ->orWhere('role', 'moderator')
            ->get();

        return view('admin.tickets.index', compact('tickets', 'filters', 'stats', 'categories', 'staffUsers'));
    }

    /**
     * Show ticket details
     */
    public function show($id)
    {
        $ticket = Ticket::with(['user', 'assignedTo', 'category', 'replies.user'])
            ->findOrFail($id);

        $staffUsers = User::where('is_super_admin', true)
            ->orWhere('role', 'admin')
            ->orWhere('role', 'moderator')
            ->get();

        $categories = TicketCategory::active()->ordered()->get();

        return view('admin.tickets.show', compact('ticket', 'staffUsers', 'categories'));
    }

    /**
     * Add reply to ticket
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
            'is_internal_note' => 'boolean',
        ]);

        try {
            $ticket = Ticket::findOrFail($id);

            $this->ticketService->addReply($ticket, [
                'user_id' => auth()->id(),
                'message' => $request->input('message'),
                'is_internal_note' => $request->input('is_internal_note', false),
            ]);

            return redirect()->route('admin.tickets.show', $id)
                ->with('success', 'เพิ่มข้อความตอบกลับเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Assign ticket to user
     */
    public function assign(Request $request, $id)
    {
        $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        try {
            $ticket = Ticket::findOrFail($id);
            $this->ticketService->assignTicket($ticket, $request->input('assigned_to'));

            return redirect()->route('admin.tickets.show', $id)
                ->with('success', 'มอบหมายตั๋วเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,waiting_customer,resolved,closed',
            'resolution_notes' => 'nullable|string',
        ]);

        try {
            $ticket = Ticket::findOrFail($id);
            $this->ticketService->changeStatus(
                $ticket,
                $request->input('status'),
                $request->input('resolution_notes')
            );

            return redirect()->route('admin.tickets.show', $id)
                ->with('success', 'อัปเดตสถานะตั๋วเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Update ticket priority
     */
    public function updatePriority(Request $request, $id)
    {
        $request->validate([
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        try {
            $ticket = Ticket::findOrFail($id);
            $this->ticketService->updatePriority($ticket, $request->input('priority'));

            return redirect()->route('admin.tickets.show', $id)
                ->with('success', 'อัปเดตความสำคัญเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Update ticket category
     */
    public function updateCategory(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'required|exists:ticket_categories,id',
        ]);

        try {
            $ticket = Ticket::findOrFail($id);
            $ticket->update(['category_id' => $request->input('category_id')]);

            return redirect()->route('admin.tickets.show', $id)
                ->with('success', 'อัปเดตหมวดหมู่เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Delete ticket
     */
    public function destroy($id)
    {
        try {
            $ticket = Ticket::findOrFail($id);
            $ticket->delete();

            return redirect()->route('admin.tickets.index')
                ->with('success', 'ลบตั๋วเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Manage ticket categories
     */
    public function categories()
    {
        $categories = TicketCategory::orderBy('sort_order')->get();
        return view('admin.tickets.categories', compact('categories'));
    }

    /**
     * Store new category
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'description' => 'nullable|string',
            'color' => 'required|string',
        ]);

        try {
            TicketCategory::create([
                'name' => $request->input('name'),
                'icon' => $request->input('icon'),
                'description' => $request->input('description'),
                'color' => $request->input('color'),
                'sort_order' => TicketCategory::max('sort_order') + 1,
            ]);

            return redirect()->route('admin.tickets.categories')
                ->with('success', 'เพิ่มหมวดหมู่เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Update category
     */
    public function updateCategoryData(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'description' => 'nullable|string',
            'color' => 'required|string',
            'is_active' => 'boolean',
        ]);

        try {
            $category = TicketCategory::findOrFail($id);
            $category->update($request->only(['name', 'icon', 'description', 'color', 'is_active']));

            return redirect()->route('admin.tickets.categories')
                ->with('success', 'อัปเดตหมวดหมู่เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Delete category
     */
    public function destroyCategory($id)
    {
        try {
            $category = TicketCategory::findOrFail($id);

            // Check if category has tickets
            if ($category->tickets()->count() > 0) {
                return redirect()->back()->with('error', 'ไม่สามารถลบหมวดหมู่ที่มีตั๋วอยู่ได้');
            }

            $category->delete();

            return redirect()->route('admin.tickets.categories')
                ->with('success', 'ลบหมวดหมู่เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
