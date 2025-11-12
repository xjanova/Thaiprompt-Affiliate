<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketCannedResponse;
use App\Models\TicketSlaPolicy;
use App\Models\TicketAssignmentRule;
use App\Models\KbArticle;
use App\Models\TicketRating;
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

    // ==================== NEW METHODS ====================

    /**
     * Analytics dashboard
     */
    public function analytics(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30));
        $dateTo = $request->input('date_to', now());

        $analytics = $this->ticketService->getAdvancedAnalytics($dateFrom, $dateTo);

        return view('admin.tickets.analytics', compact('analytics', 'dateFrom', 'dateTo'));
    }

    /**
     * View ratings
     */
    public function ratings(Request $request)
    {
        $ratings = TicketRating::with(['ticket', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = TicketRating::getStatistics();

        return view('admin.tickets.ratings', compact('ratings', 'stats'));
    }

    /**
     * Merge tickets
     */
    public function merge(Request $request, $id)
    {
        $request->validate([
            'target_ticket_id' => 'required|exists:tickets,id',
        ]);

        try {
            $sourceTicket = Ticket::findOrFail($id);
            $targetTicket = Ticket::findOrFail($request->input('target_ticket_id'));

            $this->ticketService->mergeTickets($sourceTicket, $targetTicket);

            return redirect()->route('admin.tickets.show', $targetTicket->id)
                ->with('success', 'รวมตั๋วเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Link tickets
     */
    public function link(Request $request, $id)
    {
        $request->validate([
            'related_ticket_id' => 'required|exists:tickets,id',
            'relationship_type' => 'required|in:related,duplicate,blocks,blocked_by',
            'note' => 'nullable|string',
        ]);

        try {
            $ticket = Ticket::findOrFail($id);
            $relatedTicket = Ticket::findOrFail($request->input('related_ticket_id'));

            $this->ticketService->linkTickets(
                $ticket,
                $relatedTicket,
                $request->input('relationship_type'),
                $request->input('note')
            );

            return redirect()->route('admin.tickets.show', $id)
                ->with('success', 'เชื่อมโยงตั๋วเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    // ==================== CANNED RESPONSES ====================

    /**
     * Manage canned responses
     */
    public function cannedResponses()
    {
        $responses = TicketCannedResponse::with('creator')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $categories = TicketCategory::active()->ordered()->get();

        return view('admin.tickets.canned-responses', compact('responses', 'categories'));
    }

    /**
     * Store canned response
     */
    public function storeCannedResponse(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'shortcode' => 'required|string|max:50|unique:ticket_canned_responses,shortcode',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:ticket_categories,id',
            'tags' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        try {
            TicketCannedResponse::create([
                'title' => $request->input('title'),
                'shortcode' => $request->input('shortcode'),
                'content' => $request->input('content'),
                'category_id' => $request->input('category_id'),
                'tags' => $request->input('tags'),
                'is_public' => $request->input('is_public', true),
                'created_by' => auth()->id(),
                'sort_order' => TicketCannedResponse::max('sort_order') + 1,
            ]);

            return redirect()->route('admin.tickets.canned-responses.index')
                ->with('success', 'เพิ่มข้อความสำเร็จรูปเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Update canned response
     */
    public function updateCannedResponse(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'shortcode' => 'required|string|max:50|unique:ticket_canned_responses,shortcode,' . $id,
            'content' => 'required|string',
            'category_id' => 'nullable|exists:ticket_categories,id',
            'tags' => 'nullable|array',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            $response = TicketCannedResponse::findOrFail($id);
            $response->update($request->only(['title', 'shortcode', 'content', 'category_id', 'tags', 'is_public', 'is_active']));

            return redirect()->route('admin.tickets.canned-responses.index')
                ->with('success', 'อัปเดตข้อความสำเร็จรูปเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Delete canned response
     */
    public function destroyCannedResponse($id)
    {
        try {
            $response = TicketCannedResponse::findOrFail($id);
            $response->delete();

            return redirect()->route('admin.tickets.canned-responses.index')
                ->with('success', 'ลบข้อความสำเร็จรูปเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    // ==================== SLA POLICIES ====================

    /**
     * Manage SLA policies
     */
    public function slaPolicies()
    {
        $policies = TicketSlaPolicy::with('category')
            ->orderBy('sort_order')
            ->get();

        $categories = TicketCategory::active()->ordered()->get();

        return view('admin.tickets.sla-policies', compact('policies', 'categories'));
    }

    /**
     * Store SLA policy
     */
    public function storeSlaPolicy(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'first_response_time' => 'required|integer|min:1',
            'resolution_time' => 'required|integer|min:1',
            'category_id' => 'nullable|exists:ticket_categories,id',
            'priority' => 'nullable|in:low,medium,high,critical',
            'business_hours_only' => 'boolean',
            'business_hours' => 'nullable|array',
        ]);

        try {
            TicketSlaPolicy::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'first_response_time' => $request->input('first_response_time'),
                'resolution_time' => $request->input('resolution_time'),
                'category_id' => $request->input('category_id'),
                'priority' => $request->input('priority'),
                'business_hours_only' => $request->input('business_hours_only', false),
                'business_hours' => $request->input('business_hours'),
                'sort_order' => TicketSlaPolicy::max('sort_order') + 1,
            ]);

            return redirect()->route('admin.tickets.sla-policies.index')
                ->with('success', 'เพิ่ม SLA Policy เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Update SLA policy
     */
    public function updateSlaPolicy(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'first_response_time' => 'required|integer|min:1',
            'resolution_time' => 'required|integer|min:1',
            'category_id' => 'nullable|exists:ticket_categories,id',
            'priority' => 'nullable|in:low,medium,high,critical',
            'business_hours_only' => 'boolean',
            'business_hours' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        try {
            $policy = TicketSlaPolicy::findOrFail($id);
            $policy->update($request->all());

            return redirect()->route('admin.tickets.sla-policies.index')
                ->with('success', 'อัปเดต SLA Policy เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Delete SLA policy
     */
    public function destroySlaPolicy($id)
    {
        try {
            $policy = TicketSlaPolicy::findOrFail($id);
            $policy->delete();

            return redirect()->route('admin.tickets.sla-policies.index')
                ->with('success', 'ลบ SLA Policy เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    // ==================== ASSIGNMENT RULES ====================

    /**
     * Manage assignment rules
     */
    public function assignmentRules()
    {
        $rules = TicketAssignmentRule::with(['category', 'assignTo'])
            ->orderBy('priority')
            ->get();

        $categories = TicketCategory::active()->ordered()->get();
        $staffUsers = User::where('is_super_admin', true)
            ->orWhere('role', 'admin')
            ->orWhere('role', 'moderator')
            ->get();

        return view('admin.tickets.assignment-rules', compact('rules', 'categories', 'staffUsers'));
    }

    /**
     * Store assignment rule
     */
    public function storeAssignmentRule(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:ticket_categories,id',
            'priority_filter' => 'nullable|in:low,medium,high,critical',
            'assign_to_user_id' => 'required|exists:users,id',
            'priority' => 'required|integer|min:1',
        ]);

        try {
            TicketAssignmentRule::create([
                'name' => $request->input('name'),
                'category_id' => $request->input('category_id'),
                'priority_filter' => $request->input('priority_filter'),
                'assign_to_user_id' => $request->input('assign_to_user_id'),
                'priority' => $request->input('priority'),
            ]);

            return redirect()->route('admin.tickets.assignment-rules.index')
                ->with('success', 'เพิ่มกฎการมอบหมายเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Update assignment rule
     */
    public function updateAssignmentRule(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:ticket_categories,id',
            'priority_filter' => 'nullable|in:low,medium,high,critical',
            'assign_to_user_id' => 'required|exists:users,id',
            'priority' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        try {
            $rule = TicketAssignmentRule::findOrFail($id);
            $rule->update($request->all());

            return redirect()->route('admin.tickets.assignment-rules.index')
                ->with('success', 'อัปเดตกฎการมอบหมายเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Delete assignment rule
     */
    public function destroyAssignmentRule($id)
    {
        try {
            $rule = TicketAssignmentRule::findOrFail($id);
            $rule->delete();

            return redirect()->route('admin.tickets.assignment-rules.index')
                ->with('success', 'ลบกฎการมอบหมายเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Toggle assignment rule
     */
    public function toggleAssignmentRule($id)
    {
        try {
            $rule = TicketAssignmentRule::findOrFail($id);
            $rule->update(['is_active' => !$rule->is_active]);

            return redirect()->route('admin.tickets.assignment-rules.index')
                ->with('success', 'เปลี่ยนสถานะกฎการมอบหมายเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    // ==================== KB ARTICLES ====================

    /**
     * Manage KB articles
     */
    public function kbArticles()
    {
        $articles = KbArticle::with('category')
            ->orderBy('view_count', 'desc')
            ->paginate(20);

        $categories = TicketCategory::active()->ordered()->get();

        return view('admin.tickets.kb-articles', compact('articles', 'categories'));
    }

    /**
     * Create KB article
     */
    public function createKbArticle()
    {
        $categories = TicketCategory::active()->ordered()->get();
        return view('admin.tickets.kb-article-form', compact('categories'));
    }

    /**
     * Store KB article
     */
    public function storeKbArticle(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:ticket_categories,id',
            'tags' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        try {
            KbArticle::create([
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'category_id' => $request->input('category_id'),
                'tags' => $request->input('tags'),
                'is_public' => $request->input('is_public', true),
                'author_id' => auth()->id(),
            ]);

            return redirect()->route('admin.tickets.kb-articles.index')
                ->with('success', 'เพิ่มบทความเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Edit KB article
     */
    public function editKbArticle($id)
    {
        $article = KbArticle::findOrFail($id);
        $categories = TicketCategory::active()->ordered()->get();
        return view('admin.tickets.kb-article-form', compact('article', 'categories'));
    }

    /**
     * Update KB article
     */
    public function updateKbArticle(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:ticket_categories,id',
            'tags' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        try {
            $article = KbArticle::findOrFail($id);
            $article->update($request->only(['title', 'content', 'category_id', 'tags', 'is_public']));

            return redirect()->route('admin.tickets.kb-articles.index')
                ->with('success', 'อัปเดตบทความเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Delete KB article
     */
    public function destroyKbArticle($id)
    {
        try {
            $article = KbArticle::findOrFail($id);
            $article->delete();

            return redirect()->route('admin.tickets.kb-articles.index')
                ->with('success', 'ลบบทความเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Toggle KB article visibility
     */
    public function toggleKbArticle($id)
    {
        try {
            $article = KbArticle::findOrFail($id);
            $article->update(['is_public' => !$article->is_public]);

            return redirect()->route('admin.tickets.kb-articles.index')
                ->with('success', 'เปลี่ยนสถานะบทความเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    // ==================== SETTINGS ====================

    /**
     * Ticket system settings
     */
    public function settings()
    {
        // For now, just return a view
        // Settings can be stored in a settings table or config
        return view('admin.tickets.settings');
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        try {
            // Implement settings update logic here
            // Can use a settings table or config

            return redirect()->route('admin.tickets.settings')
                ->with('success', 'อัปเดตการตั้งค่าเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
