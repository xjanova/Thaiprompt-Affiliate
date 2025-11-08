<?php

namespace App\Services\BotAutomation;

use App\Models\BotAutomation\BotAutomation;
use App\Models\BotAutomation\BotScheduledPost;
use App\Models\BotAutomation\BotAutomationExecution;
use App\Jobs\BotAutomation\ExecuteBotAutomationJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BotAutomationService
{
    public function __construct(
        protected BotContentGenerationService $contentService,
        protected BotPlatformService $platformService
    ) {}

    /**
     * Create a new automation
     */
    public function createAutomation(array $data): BotAutomation
    {
        $automation = BotAutomation::create($data);

        // Calculate first execution time
        if ($automation->is_active) {
            $nextExecution = $automation->calculateNextExecution();
            $automation->update(['next_execution_at' => $nextExecution]);
        }

        Log::info("Bot automation created", ['automation_id' => $automation->id]);

        return $automation;
    }

    /**
     * Execute automation
     */
    public function executeAutomation(BotAutomation $automation, string $executionType = 'scheduled'): BotAutomationExecution
    {
        $execution = BotAutomationExecution::create([
            'automation_id' => $automation->id,
            'status' => 'pending',
            'execution_type' => $executionType,
        ]);

        try {
            $execution->start();

            // Route to appropriate handler based on automation type
            $result = match($automation->automation_type) {
                'scheduled_post' => $this->executeScheduledPost($automation, $execution),
                'customer_support' => $this->executeCustomerSupport($automation, $execution),
                'sales_assistant' => $this->executeSalesAssistant($automation, $execution),
                'engagement' => $this->executeEngagement($automation, $execution),
                'analytics' => $this->executeAnalytics($automation, $execution),
                default => throw new \Exception("Unknown automation type: {$automation->automation_type}"),
            };

            $execution->complete($result);
            $automation->recordExecution(true);

            Log::info("Automation executed successfully", [
                'automation_id' => $automation->id,
                'execution_id' => $execution->id,
            ]);

        } catch (\Exception $e) {
            $execution->fail($e->getMessage(), [], $e->getTraceAsString());
            $automation->recordExecution(false);

            Log::error("Automation execution failed", [
                'automation_id' => $automation->id,
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $execution;
    }

    /**
     * Execute scheduled post automation
     */
    protected function executeScheduledPost(BotAutomation $automation, BotAutomationExecution $execution): array
    {
        // Generate content
        $content = $this->contentService->generateContent($automation);

        // Get target platforms
        $platformIds = $automation->target_platforms ?? [];
        $results = [];

        $attempted = 0;
        $succeeded = 0;
        $failed = 0;

        foreach ($platformIds as $platformId) {
            $attempted++;

            try {
                // Create scheduled post
                $scheduledPost = BotScheduledPost::create([
                    'automation_id' => $automation->id,
                    'platform_connection_id' => $platformId,
                    'status' => 'pending',
                    'content' => $content['text'] ?? '',
                    'media_urls' => $content['media_urls'] ?? [],
                    'hashtags' => $content['hashtags'] ?? [],
                    'scheduled_for' => now(),
                ]);

                // Post to platform
                $postResult = $this->platformService->publishPost($scheduledPost);

                if ($postResult['success']) {
                    $succeeded++;
                    $results[] = [
                        'platform_id' => $platformId,
                        'status' => 'success',
                        'post_id' => $postResult['post_id'] ?? null,
                    ];
                } else {
                    $failed++;
                    $results[] = [
                        'platform_id' => $platformId,
                        'status' => 'failed',
                        'error' => $postResult['error'] ?? 'Unknown error',
                    ];
                }
            } catch (\Exception $e) {
                $failed++;
                $results[] = [
                    'platform_id' => $platformId,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $execution->updatePlatformStats($attempted, $succeeded, $failed);

        return [
            'type' => 'scheduled_post',
            'platforms_attempted' => $attempted,
            'platforms_succeeded' => $succeeded,
            'platforms_failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Execute customer support automation
     */
    protected function executeCustomerSupport(BotAutomation $automation, BotAutomationExecution $execution): array
    {
        // Implementation for customer support automation
        // This would integrate with BotSupportService
        return [
            'type' => 'customer_support',
            'status' => 'implemented_separately',
        ];
    }

    /**
     * Execute sales assistant automation
     */
    protected function executeSalesAssistant(BotAutomation $automation, BotAutomationExecution $execution): array
    {
        // Implementation for sales assistant automation
        // This would integrate with BotSalesService
        return [
            'type' => 'sales_assistant',
            'status' => 'implemented_separately',
        ];
    }

    /**
     * Execute engagement automation
     */
    protected function executeEngagement(BotAutomation $automation, BotAutomationExecution $execution): array
    {
        return [
            'type' => 'engagement',
            'status' => 'pending_implementation',
        ];
    }

    /**
     * Execute analytics automation
     */
    protected function executeAnalytics(BotAutomation $automation, BotAutomationExecution $execution): array
    {
        return [
            'type' => 'analytics',
            'status' => 'pending_implementation',
        ];
    }

    /**
     * Process all due automations
     */
    public function processDueAutomations(): array
    {
        $automations = BotAutomation::dueForExecution()->get();
        $results = [];

        foreach ($automations as $automation) {
            try {
                $execution = $this->executeAutomation($automation);
                $results[] = [
                    'automation_id' => $automation->id,
                    'execution_id' => $execution->id,
                    'status' => $execution->status,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'automation_id' => $automation->id,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Schedule automation execution
     */
    public function scheduleExecution(BotAutomation $automation, ?Carbon $scheduledTime = null): void
    {
        $time = $scheduledTime ?? $automation->calculateNextExecution();

        if ($time) {
            ExecuteBotAutomationJob::dispatch($automation)->delay($time);
        }
    }

    /**
     * Get automation statistics
     */
    public function getStatistics(BotAutomation $automation): array
    {
        return [
            'total_executions' => $automation->total_executions,
            'successful_executions' => $automation->successful_executions,
            'failed_executions' => $automation->failed_executions,
            'success_rate' => $automation->getSuccessRate(),
            'last_executed_at' => $automation->last_executed_at,
            'next_execution_at' => $automation->next_execution_at,
            'total_posts' => $automation->scheduledPosts()->count(),
            'published_posts' => $automation->scheduledPosts()->published()->count(),
            'pending_posts' => $automation->scheduledPosts()->pending()->count(),
            'failed_posts' => $automation->scheduledPosts()->failed()->count(),
        ];
    }
}
