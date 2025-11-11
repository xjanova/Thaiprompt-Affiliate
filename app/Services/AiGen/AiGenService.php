<?php

namespace App\Services\AiGen;

use App\Models\User;
use App\Models\AiGenProvider;
use App\Models\AiGenUsageLog;
use App\Models\AiGenGeneration;
use App\Models\AiGenSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AiGenService
{
    protected AiGenQuotaService $quotaService;
    protected AiGenSubscriptionService $subscriptionService;

    public function __construct()
    {
        $this->quotaService = new AiGenQuotaService();
        $this->subscriptionService = new AiGenSubscriptionService();
    }

    /**
     * Generate content (image or video).
     */
    public function generate(
        User $user,
        string $providerSlug,
        string $type,
        string $prompt,
        array $parameters = []
    ): array {
        // Validate type
        if (!in_array($type, ['image', 'video'])) {
            return [
                'success' => false,
                'error' => 'Invalid generation type. Must be "image" or "video".',
            ];
        }

        // Get provider
        $provider = AiGenProvider::where('slug', $providerSlug)
            ->where('is_active', true)
            ->first();

        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found or inactive.',
            ];
        }

        // Create provider instance
        $providerInstance = AiGenProviderFactory::createFromModel($provider);

        if (!$providerInstance) {
            return [
                'success' => false,
                'error' => 'Provider implementation not found.',
            ];
        }

        if (!$providerInstance->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Provider is not properly configured.',
            ];
        }

        // Check if user can generate
        $canGenerate = $this->checkUserCanGenerate($user, $type);

        if (!$canGenerate['can_generate']) {
            return [
                'success' => false,
                'error' => $canGenerate['reason'],
            ];
        }

        // Start generation
        try {
            DB::beginTransaction();

            // Create usage log
            $usageLog = AiGenUsageLog::create([
                'user_id' => $user->id,
                'provider_id' => $provider->id,
                'subscription_id' => $canGenerate['subscription_id'] ?? null,
                'generation_type' => $type,
                'prompt' => $prompt,
                'parameters' => $parameters,
                'credits_used' => 1,
                'is_free_quota' => $canGenerate['is_free_quota'],
                'status' => 'pending',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'started_at' => Carbon::now(),
            ]);

            // Call provider API
            if ($type === 'image') {
                $result = $providerInstance->generateImage($prompt, $parameters);
            } else {
                $result = $providerInstance->generateVideo($prompt, $parameters);
            }

            if (!$result['success']) {
                $usageLog->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Unknown error',
                    'completed_at' => Carbon::now(),
                ]);

                DB::commit();

                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Generation failed',
                ];
            }

            // Update usage log
            $usageLog->update([
                'status' => $result['status'] ?? 'completed',
                'result_data' => $result,
                'completed_at' => $result['status'] === 'completed' ? Carbon::now() : null,
            ]);

            // Create generation record
            $generation = AiGenGeneration::create([
                'user_id' => $user->id,
                'provider_id' => $provider->id,
                'usage_log_id' => $usageLog->id,
                'type' => $type,
                'prompt' => $prompt,
                'settings' => $parameters,
                'status' => $result['status'] ?? 'pending',
                'external_id' => $result['generation_id'] ?? null,
                'external_data' => $result['data'] ?? null,
            ]);

            // If completed, save file info
            if ($result['status'] === 'completed') {
                $this->updateGenerationWithResult($generation, $result);
            }

            // Deduct credits if using subscription
            if (!$canGenerate['is_free_quota'] && $canGenerate['subscription_id']) {
                $subscription = AiGenSubscription::find($canGenerate['subscription_id']);
                if ($subscription) {
                    $subscription->useCredits($type, 1);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'generation_id' => $generation->id,
                'external_id' => $result['generation_id'] ?? null,
                'status' => $result['status'] ?? 'pending',
                'data' => $generation,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if user can generate.
     */
    protected function checkUserCanGenerate(User $user, string $type): array
    {
        // Admin can always generate
        if ($user->is_admin || $user->is_super_admin) {
            return [
                'can_generate' => true,
                'is_free_quota' => true,
                'is_admin' => true,
            ];
        }

        // Check if user has active subscription with credits
        $subscription = $this->subscriptionService->getActiveSubscription($user);

        if ($subscription && $subscription->hasCredits($type)) {
            return [
                'can_generate' => true,
                'is_free_quota' => false,
                'subscription_id' => $subscription->id,
            ];
        }

        // Check free quota
        if ($this->quotaService->canUseFreeQuota($user, $type)) {
            return [
                'can_generate' => true,
                'is_free_quota' => true,
            ];
        }

        return [
            'can_generate' => false,
            'reason' => 'No available credits or quota. Please purchase a package.',
        ];
    }

    /**
     * Update generation with result data.
     */
    protected function updateGenerationWithResult(AiGenGeneration $generation, array $result): void
    {
        $updateData = [
            'status' => 'completed',
        ];

        if (isset($result['images']) && is_array($result['images']) && !empty($result['images'])) {
            $updateData['file_url'] = $result['images'][0]['url'] ?? null;
            $updateData['thumbnail_url'] = $result['images'][0]['thumbnail'] ?? null;
        }

        if (isset($result['video_url'])) {
            $updateData['file_url'] = $result['video_url'];
        }

        $generation->update($updateData);
    }

    /**
     * Check generation status.
     */
    public function checkGenerationStatus(int $generationId, User $user): array
    {
        $generation = AiGenGeneration::where('id', $generationId)
            ->where('user_id', $user->id)
            ->first();

        if (!$generation) {
            return [
                'success' => false,
                'error' => 'Generation not found',
            ];
        }

        // If already completed, return current data
        if ($generation->status === 'completed' || $generation->status === 'failed') {
            return [
                'success' => true,
                'status' => $generation->status,
                'data' => $generation,
            ];
        }

        // Check with provider
        if ($generation->external_id) {
            $providerInstance = AiGenProviderFactory::createFromModel($generation->provider);

            if ($providerInstance) {
                $result = $providerInstance->checkStatus($generation->external_id);

                if ($result['success']) {
                    $generation->update([
                        'status' => $result['status'],
                        'external_data' => $result['data'],
                    ]);

                    if ($result['status'] === 'completed') {
                        $this->updateGenerationWithResult($generation, $result['data']);
                    }
                }
            }
        }

        return [
            'success' => true,
            'status' => $generation->status,
            'data' => $generation,
        ];
    }

    /**
     * Get user generations.
     */
    public function getUserGenerations(User $user, array $filters = [])
    {
        $query = AiGenGeneration::where('user_id', $user->id);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['provider_id'])) {
            $query->where('provider_id', $filters['provider_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_favorite'])) {
            $query->where('is_favorite', $filters['is_favorite']);
        }

        return $query->with(['provider', 'usageLog'])
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get user dashboard data.
     */
    public function getUserDashboard(User $user): array
    {
        return [
            'quota' => $this->quotaService->getQuotaSummary($user),
            'subscription' => $this->subscriptionService->getSubscriptionSummary($user),
            'stats' => [
                'total_generations' => AiGenGeneration::where('user_id', $user->id)->count(),
                'completed_generations' => AiGenGeneration::where('user_id', $user->id)
                    ->where('status', 'completed')->count(),
                'this_month' => AiGenGeneration::where('user_id', $user->id)
                    ->whereMonth('created_at', Carbon::now()->month)->count(),
            ],
            'providers' => AiGenProviderFactory::getAvailableProviders(),
        ];
    }
}
