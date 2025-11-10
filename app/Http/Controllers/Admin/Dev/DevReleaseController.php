<?php

namespace App\Http\Controllers\Admin\Dev;

use App\Http\Controllers\Controller;
use App\Models\SystemUpdate;
use App\Models\UpdateLog;
use App\Services\UpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

/**
 * Developer Release Manager
 * Only accessible by whitelisted developer IPs
 */
class DevReleaseController extends Controller
{
    protected UpdateService $updateService;

    public function __construct(UpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    /**
     * Show developer release manager
     */
    public function index()
    {
        $currentVersion = config('version.current');
        $currentIp = request()->ip();

        // Get all releases from GitHub (including drafts)
        $allReleases = $this->getAllGitHubReleases();

        // Get official releases (customer sees)
        $officialReleases = $allReleases->where('draft', false)->where('prerelease', false);

        // Get draft releases (dev only)
        $draftReleases = $allReleases->where('draft', true);

        // Get pre-releases (dev only)
        $preReleases = $allReleases->where('prerelease', true)->where('draft', false);

        // Get git status
        $gitStatus = $this->getGitStatus();

        // Get recent commits (for real-time view)
        $recentCommits = $this->getRecentCommits();

        return view('admin.dev.releases.index', compact(
            'currentVersion',
            'currentIp',
            'allReleases',
            'officialReleases',
            'draftReleases',
            'preReleases',
            'gitStatus',
            'recentCommits'
        ));
    }

    /**
     * Get all GitHub releases (including drafts)
     */
    protected function getAllGitHubReleases()
    {
        try {
            $repositoryConfig = config('version.repository');
            $response = Http::get($repositoryConfig['api_url'] . '/releases');

            if (!$response->successful()) {
                return collect([]);
            }

            return collect($response->json())->map(function ($release) {
                // Get file changes for this release
                $fileChanges = $this->getFileChangesForRelease($release['tag_name']);

                return [
                    'tag_name' => $release['tag_name'],
                    'name' => $release['name'] ?? $release['tag_name'],
                    'body' => $release['body'] ?? '',
                    'draft' => $release['draft'] ?? false,
                    'prerelease' => $release['prerelease'] ?? false,
                    'published_at' => $release['published_at'] ?? $release['created_at'],
                    'html_url' => $release['html_url'],
                    'zipball_url' => $release['zipball_url'],
                    'author' => $release['author']['login'] ?? 'Unknown',
                    'file_changes' => $fileChanges,
                ];
            });
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get file changes for a specific release tag
     * Compares the tag with the previous tag to see what files changed
     */
    protected function getFileChangesForRelease($tag)
    {
        try {
            // Get the previous tag
            $previousTag = trim(Process::run("git describe --tags --abbrev=0 {$tag}^")->output());

            if (empty($previousTag)) {
                // If no previous tag, compare with first commit
                $result = Process::run("git diff --name-status $(git rev-list --max-parents=0 HEAD) {$tag}");
            } else {
                // Compare with previous tag
                $result = Process::run("git diff --name-status {$previousTag}..{$tag}");
            }

            $files = [];
            $lines = explode("\n", trim($result->output()));

            foreach ($lines as $line) {
                if (empty($line)) continue;

                $parts = preg_split('/\s+/', $line, 2);
                if (count($parts) < 2) continue;

                $status = $parts[0];
                $filePath = $parts[1];

                // Check if file should be blocked (sensitive or dev-only)
                $isBlocked = $this->isBlockedFile($filePath);
                $isSensitive = $this->isSensitiveFile($filePath);

                $files[] = [
                    'path' => $filePath,
                    'status' => $this->getFileStatusLabel($status),
                    'status_code' => $status,
                    'is_blocked' => $isBlocked,
                    'is_sensitive' => $isSensitive,
                    'category' => $this->getFileCategory($filePath),
                ];
            }

            return [
                'files' => $files,
                'total' => count($files),
                'blocked_count' => count(array_filter($files, fn($f) => $f['is_blocked'])),
                'sensitive_count' => count(array_filter($files, fn($f) => $f['is_sensitive'])),
                'previous_tag' => $previousTag ?: 'Initial release',
            ];

        } catch (\Exception $e) {
            return [
                'files' => [],
                'total' => 0,
                'blocked_count' => 0,
                'sensitive_count' => 0,
                'previous_tag' => 'Unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if file is blocked (should NOT be in production)
     * Based on .gitattributes export-ignore rules
     */
    protected function isBlockedFile($filePath)
    {
        $blockedPaths = [
            'app/Http/Controllers/Admin/Dev/',
            'app/Http/Middleware/DevMode.php',
            'resources/views/admin/dev/',
            '.git',
            '.github/',
            '.env',
            'tests/',
            'phpunit.xml',
            'node_modules/',
            '.gitignore',
            '.gitattributes',
        ];

        foreach ($blockedPaths as $blocked) {
            if (str_starts_with($filePath, $blocked)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if file is sensitive (warning, but may be needed)
     */
    protected function isSensitiveFile($filePath)
    {
        $sensitivePaths = [
            'config/',
            'database/migrations/',
            'routes/',
            '.env.example',
            'composer.json',
            'composer.lock',
        ];

        foreach ($sensitivePaths as $sensitive) {
            if (str_starts_with($filePath, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get file category for grouping
     */
    protected function getFileCategory($filePath)
    {
        if (str_starts_with($filePath, 'app/')) return 'Backend Code';
        if (str_starts_with($filePath, 'resources/views/')) return 'Views';
        if (str_starts_with($filePath, 'resources/js/')) return 'JavaScript';
        if (str_starts_with($filePath, 'resources/css/')) return 'CSS';
        if (str_starts_with($filePath, 'public/')) return 'Public Assets';
        if (str_starts_with($filePath, 'database/')) return 'Database';
        if (str_starts_with($filePath, 'routes/')) return 'Routes';
        if (str_starts_with($filePath, 'config/')) return 'Configuration';
        if (str_starts_with($filePath, 'storage/')) return 'Storage';
        if (str_starts_with($filePath, 'tests/')) return 'Tests (Dev Only)';
        if (str_ends_with($filePath, '.md')) return 'Documentation';

        return 'Other';
    }

    /**
     * Get human-readable status label
     */
    protected function getFileStatusLabel($statusCode)
    {
        return match($statusCode) {
            'A' => 'Added',
            'M' => 'Modified',
            'D' => 'Deleted',
            'R' => 'Renamed',
            'C' => 'Copied',
            'T' => 'Type Changed',
            default => $statusCode,
        };
    }

    /**
     * Get git status
     */
    protected function getGitStatus()
    {
        try {
            $branch = trim(Process::run('git rev-parse --abbrev-ref HEAD')->output());
            $hasChanges = !empty(trim(Process::run('git status --porcelain')->output()));
            $lastCommit = trim(Process::run('git log -1 --format="%h - %s (%cr)"')->output());

            return [
                'branch' => $branch,
                'has_changes' => $hasChanges,
                'last_commit' => $lastCommit,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get recent commits
     */
    protected function getRecentCommits($limit = 10)
    {
        try {
            $result = Process::run("git log -{$limit} --format='%h|%s|%cr|%an'");
            $commits = [];

            foreach (explode("\n", trim($result->output())) as $line) {
                if (empty($line)) continue;

                $parts = explode('|', $line);
                $commits[] = [
                    'hash' => $parts[0] ?? '',
                    'message' => $parts[1] ?? '',
                    'time' => $parts[2] ?? '',
                    'author' => $parts[3] ?? '',
                ];
            }

            return $commits;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Create new release via web interface
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|regex:/^v?\d+\.\d+\.\d+$/',
            'changelog' => 'required|string',
            'type' => 'required|in:major,minor,patch',
            'is_draft' => 'boolean',
        ]);

        try {
            // Run artisan command
            $args = [
                'version' => $validated['version'],
                '--changelog' => $validated['changelog'],
                '--type' => $validated['type'],
            ];

            if ($validated['is_draft'] ?? false) {
                $args['--draft'] = true;
            }

            $args['--push'] = true;

            Artisan::call('release:create', $args);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Release created successfully!',
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create release: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish draft release (make it official)
     */
    public function publish($tag)
    {
        try {
            // Use GitHub CLI to publish draft
            $result = Process::run("gh release edit {$tag} --draft=false");

            if ($result->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => "Release {$tag} published successfully!",
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to publish release',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete release
     */
    public function delete($tag)
    {
        try {
            // Delete from GitHub
            $result = Process::run("gh release delete {$tag} --yes");

            // Delete tag
            Process::run("git tag -d {$tag}");
            Process::run("git push origin :refs/tags/{$tag}");

            return response()->json([
                'success' => true,
                'message' => "Release {$tag} deleted successfully!",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh releases from GitHub
     */
    public function refresh()
    {
        try {
            $releases = $this->getAllGitHubReleases();

            return response()->json([
                'success' => true,
                'releases' => $releases,
                'message' => 'Releases refreshed successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get real-time update info
     */
    public function realtimeInfo()
    {
        return response()->json([
            'git_status' => $this->getGitStatus(),
            'recent_commits' => $this->getRecentCommits(5),
            'current_version' => config('version.current'),
            'current_ip' => request()->ip(),
        ]);
    }
}
