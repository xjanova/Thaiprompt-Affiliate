<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotAutomation\BotAutomation;
use App\Models\BotAutomation\BotMarketplaceListing;
use App\Services\BotAutomation\BotAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotAutomationApiController extends Controller
{
    public function __construct(
        protected BotAutomationService $automationService
    ) {}

    /**
     * List all automations for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $automations = BotAutomation::query()
            ->where('user_id', auth()->id())
            ->with(['aiBotProfile', 'template'])
            ->when($request->type, fn($q, $type) => $q->where('automation_type', $type))
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $automations,
        ]);
    }

    /**
     * Create new automation
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'automation_type' => 'required|in:scheduled_post,customer_support,sales_assistant,engagement,analytics',
            'trigger_type' => 'required|in:schedule,event,webhook,manual',
            'schedule_type' => 'nullable|in:once,hourly,daily,weekly,monthly,cron',
            'content_source' => 'required|in:custom,template,ai_generated',
            'custom_content' => 'nullable|string',
            'ai_generation_prompt' => 'nullable|string',
            'target_platforms' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['user_id'] = auth()->id();

        $automation = $this->automationService->createAutomation($validated);

        return response()->json([
            'success' => true,
            'message' => 'Automation created successfully',
            'data' => $automation->load(['aiBotProfile', 'template']),
        ], 201);
    }

    /**
     * Show automation details
     */
    public function show(BotAutomation $automation): JsonResponse
    {
        if ($automation->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $automation->load(['aiBotProfile', 'template', 'scheduledPosts', 'executions']);
        $statistics = $this->automationService->getStatistics($automation);

        return response()->json([
            'success' => true,
            'data' => [
                'automation' => $automation,
                'statistics' => $statistics,
            ],
        ]);
    }

    /**
     * Update automation
     */
    public function update(Request $request, BotAutomation $automation): JsonResponse
    {
        if ($automation->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'content_source' => 'sometimes|required|in:custom,template,ai_generated',
            'custom_content' => 'nullable|string',
            'ai_generation_prompt' => 'nullable|string',
            'target_platforms' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $automation->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Automation updated successfully',
            'data' => $automation->fresh(),
        ]);
    }

    /**
     * Delete automation
     */
    public function destroy(BotAutomation $automation): JsonResponse
    {
        if ($automation->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $automation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Automation deleted successfully',
        ]);
    }

    /**
     * Execute automation manually
     */
    public function execute(BotAutomation $automation): JsonResponse
    {
        if ($automation->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $execution = $this->automationService->executeAutomation($automation, 'manual');

            return response()->json([
                'success' => true,
                'message' => 'Automation executed successfully',
                'data' => $execution,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Automation execution failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get automation statistics
     */
    public function statistics(BotAutomation $automation): JsonResponse
    {
        if ($automation->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $statistics = $this->automationService->getStatistics($automation);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * List marketplace bots
     */
    public function marketplace(Request $request): JsonResponse
    {
        $listings = BotMarketplaceListing::query()
            ->published()
            ->with(['automation', 'category'])
            ->when($request->category, fn($q, $cat) => $q->where('category_id', $cat))
            ->when($request->featured, fn($q) => $q->featured())
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('is_featured', 'desc')
            ->orderBy('average_rating', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $listings,
        ]);
    }
}
