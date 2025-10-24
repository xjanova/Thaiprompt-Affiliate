<?php

namespace App\Services\Version;

use App\Models\SystemVersion;
use App\Models\SystemUpdateLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class VersionUpdateService
{
    private string $githubApiUrl = 'https://api.github.com';

    /**
     * Check for updates from GitHub
     */
    public function checkForUpdates(): array
    {
        try {
            $systemVersion = SystemVersion::firstOrCreate(
                [],
                [
                    'current_version' => '1.0.0',
                    'github_repo' => 'xjanova/Thaiprompt-Affiliate',
                ]
            );

            $repo = $systemVersion->github_repo;

            // Fetch latest release from GitHub
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Thaiprompt-Affiliate-System',
            ])->get("{$this->githubApiUrl}/repos/{$repo}/releases/latest");

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch latest version from GitHub');
            }

            $release = $response->json();
            $latestVersion = ltrim($release['tag_name'] ?? '1.0.0', 'v');

            // Check if update is available
            $updateAvailable = version_compare($latestVersion, $systemVersion->current_version, '>');

            // Update system version record
            $systemVersion->update([
                'latest_version' => $latestVersion,
                'release_notes' => $release['body'] ?? '',
                'download_url' => $release['zipball_url'] ?? '',
                'update_available' => $updateAvailable,
                'last_checked_at' => now(),
                'changelog' => $this->parseChangelog($release['body'] ?? ''),
            ]);

            return [
                'success' => true,
                'update_available' => $updateAvailable,
                'current_version' => $systemVersion->current_version,
                'latest_version' => $latestVersion,
                'release_notes' => $release['body'] ?? '',
                'published_at' => $release['published_at'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Version Check Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get current version information
     */
    public function getCurrentVersionInfo(): array
    {
        $systemVersion = SystemVersion::first();

        if (!$systemVersion) {
            return [
                'current_version' => '1.0.0',
                'latest_version' => '1.0.0',
                'update_available' => false,
            ];
        }

        return [
            'current_version' => $systemVersion->current_version,
            'latest_version' => $systemVersion->latest_version,
            'update_available' => $systemVersion->update_available,
            'is_critical' => $systemVersion->is_critical,
            'last_checked_at' => $systemVersion->last_checked_at,
            'release_notes' => $systemVersion->release_notes,
            'changelog' => $systemVersion->changelog,
        ];
    }

    /**
     * Start system update process
     */
    public function startUpdate(User $admin): array
    {
        try {
            DB::beginTransaction();

            $systemVersion = SystemVersion::first();

            if (!$systemVersion->update_available) {
                throw new \Exception('No update available');
            }

            // Create update log
            $updateLog = SystemUpdateLog::create([
                'version_from' => $systemVersion->current_version,
                'version_to' => $systemVersion->latest_version,
                'update_status' => 'started',
                'updated_by' => $admin->id,
                'started_at' => now(),
                'update_steps' => [],
            ]);

            DB::commit();

            // Return instructions for manual update
            return [
                'success' => true,
                'update_log_id' => $updateLog->id,
                'message' => 'Update process started',
                'instructions' => [
                    '1. Backup your database',
                    '2. Put the application in maintenance mode: php artisan down',
                    '3. Pull the latest changes: git pull origin main',
                    '4. Install dependencies: composer install --no-dev',
                    '5. Run migrations: php artisan migrate --force',
                    '6. Clear cache: php artisan optimize:clear',
                    '7. Rebuild cache: php artisan optimize',
                    '8. Bring the application back up: php artisan up',
                ],
                'download_url' => $systemVersion->download_url,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update Start Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Complete update process
     */
    public function completeUpdate(int $updateLogId): array
    {
        try {
            DB::beginTransaction();

            $updateLog = SystemUpdateLog::findOrFail($updateLogId);
            $systemVersion = SystemVersion::first();

            // Update system version
            $systemVersion->update([
                'current_version' => $updateLog->version_to,
                'update_available' => false,
                'last_updated_at' => now(),
            ]);

            // Update log status
            $updateLog->update([
                'update_status' => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Update completed successfully',
                'new_version' => $systemVersion->current_version,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update Complete Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Mark update as failed
     */
    public function failUpdate(int $updateLogId, string $errorMessage): bool
    {
        $updateLog = SystemUpdateLog::findOrFail($updateLogId);

        return $updateLog->update([
            'update_status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get update history
     */
    public function getUpdateHistory(int $limit = 20): array
    {
        return SystemUpdateLog::with('updatedBy')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Parse changelog from release notes
     */
    private function parseChangelog(string $releaseNotes): array
    {
        $changelog = [];
        $lines = explode("\n", $releaseNotes);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/', $line, $matches)) {
                $changelog[] = $matches[1];
            }
        }

        return $changelog;
    }

    /**
     * Get GitHub releases
     */
    public function getGitHubReleases(int $limit = 10): array
    {
        try {
            $systemVersion = SystemVersion::first();
            $repo = $systemVersion->github_repo ?? 'xjanova/Thaiprompt-Affiliate';

            $response = Http::withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Thaiprompt-Affiliate-System',
            ])->get("{$this->githubApiUrl}/repos/{$repo}/releases", [
                'per_page' => $limit,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch releases from GitHub');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('GitHub Releases Fetch Error: ' . $e->getMessage());

            return [];
        }
    }
}
