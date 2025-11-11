<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle GitHub release webhook
     *
     * This endpoint receives notifications from GitHub when a new release is published
     * and automatically clears the version cache so users see the new version immediately.
     *
     * To set up in GitHub:
     * 1. Go to Repository Settings → Webhooks → Add webhook
     * 2. Payload URL: https://your-domain.com/api/webhooks/github/release
     * 3. Content type: application/json
     * 4. Secret: (set in .env as GITHUB_WEBHOOK_SECRET)
     * 5. Events: Let me select → Releases
     * 6. Active: ✓
     */
    public function handleGitHubRelease(Request $request)
    {
        try {
            // Verify webhook signature
            if (!$this->verifyGitHubSignature($request)) {
                Log::warning('GitHub webhook signature verification failed');
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature',
                ], 401);
            }

            // Get payload
            $payload = $request->all();
            $action = $payload['action'] ?? null;
            $release = $payload['release'] ?? null;

            Log::info('GitHub release webhook received', [
                'action' => $action,
                'release_tag' => $release['tag_name'] ?? 'unknown',
                'release_name' => $release['name'] ?? 'unknown',
            ]);

            // Only process when release is published (not draft)
            if ($action === 'published' && $release) {
                $tagName = $release['tag_name'] ?? null;
                $releaseName = $release['name'] ?? $tagName;
                $isDraft = $release['draft'] ?? false;
                $isPrerelease = $release['prerelease'] ?? false;

                // Skip draft releases
                if ($isDraft) {
                    Log::info('Skipping draft release', ['tag' => $tagName]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Draft release ignored',
                    ]);
                }

                // Clear version caches
                $this->clearVersionCaches();

                Log::info('Version caches cleared after new release', [
                    'tag' => $tagName,
                    'name' => $releaseName,
                    'prerelease' => $isPrerelease,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Version caches cleared successfully',
                    'release' => [
                        'tag' => $tagName,
                        'name' => $releaseName,
                        'prerelease' => $isPrerelease,
                    ],
                ]);
            }

            // Other actions (created, edited, deleted, etc.)
            return response()->json([
                'success' => true,
                'message' => 'Webhook received but no action taken',
                'action' => $action,
            ]);
        } catch (\Exception $e) {
            Log::error('GitHub webhook handling failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Verify GitHub webhook signature
     *
     * GitHub signs all webhook requests with HMAC-SHA256
     * We verify this signature to ensure the request is legitimate
     */
    protected function verifyGitHubSignature(Request $request): bool
    {
        $secret = config('services.github.webhook_secret', env('GITHUB_WEBHOOK_SECRET'));

        // If no secret configured, skip verification (development mode)
        if (empty($secret)) {
            Log::warning('GitHub webhook secret not configured, skipping verification');
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            return false;
        }

        // Calculate expected signature
        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        // Compare signatures (timing-safe)
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Clear all version-related caches
     */
    protected function clearVersionCaches(): void
    {
        // Clear version caches
        Cache::forget('app_latest_version');
        Cache::forget('app_available_versions');

        // Clear any other related caches
        Cache::forget('available_updates');

        Log::info('All version caches cleared');
    }

    /**
     * Manual cache clear endpoint (for admins)
     *
     * POST /api/admin/cache/clear-version
     */
    public function clearVersionCacheManual(Request $request)
    {
        try {
            // Verify admin permission (add middleware or check here)
            if (!$request->user() || !$request->user()->is_super_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $this->clearVersionCaches();

            return response()->json([
                'success' => true,
                'message' => 'Version caches cleared successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Manual cache clear failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear caches',
            ], 500);
        }
    }
}
