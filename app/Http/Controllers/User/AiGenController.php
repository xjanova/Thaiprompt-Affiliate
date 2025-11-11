<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\AiGen\AiGenService;
use App\Models\AiGenPackage;
use Illuminate\Http\Request;

class AiGenController extends Controller
{
    protected AiGenService $aiGenService;

    public function __construct(AiGenService $aiGenService)
    {
        $this->aiGenService = $aiGenService;
    }

    /**
     * Main AI Gen page
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $dashboard = $this->aiGenService->getUserDashboard($user);

        return view('user.ai-gen.index', [
            'stats' => [
                'remaining_images' => $this->getRemainingCredits($dashboard, 'image'),
                'remaining_videos' => $this->getRemainingCredits($dashboard, 'video'),
                'total_generated' => $dashboard['stats']['total_generations'] ?? 0,
            ],
            'package_name' => $dashboard['subscription']['package_name'] ?? 'Free',
            'dashboard' => $dashboard,
        ]);
    }

    /**
     * Packages page
     */
    public function packages()
    {
        $packages = AiGenPackage::active()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('user.ai-gen.packages', [
            'packages' => $packages,
        ]);
    }

    /**
     * My creations page
     */
    public function myCreations(Request $request)
    {
        $user = $request->user();

        $filters = $request->only(['type', 'status', 'is_favorite']);

        $generations = $this->aiGenService->getUserGenerations($user, $filters);

        return view('user.ai-gen.my-creations', [
            'generations' => $generations,
            'filters' => $filters,
        ]);
    }

    /**
     * Explore page
     */
    public function explore()
    {
        // TODO: Implement explore/discover feature
        return view('user.ai-gen.explore');
    }

    /**
     * Helper to get remaining credits
     */
    private function getRemainingCredits(array $dashboard, string $type): int
    {
        $subscription = $dashboard['subscription'] ?? null;

        if ($subscription && isset($subscription['has_subscription']) && $subscription['has_subscription']) {
            $key = $type . '_credits';
            return $subscription[$key]['remaining'] ?? 0;
        }

        $quota = $dashboard['quota'][$type] ?? null;
        if ($quota) {
            return min($quota['daily'] ?? 0, $quota['monthly'] ?? 0);
        }

        return 0;
    }
}
