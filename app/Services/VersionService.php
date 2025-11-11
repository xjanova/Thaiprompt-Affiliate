<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VersionService
{
    /**
     * Get current application version
     */
    public function getCurrentVersion(): string
    {
        return config('version.current');
    }

    /**
     * Get version information
     */
    public function getVersionInfo(): array
    {
        return [
            'version' => $this->getCurrentVersion(),
            'name' => config('version.name'),
            'released_at' => config('version.released_at'),
            'php_version' => PHP_VERSION,
            'min_php_version' => config('version.min_php_version'),
            'laravel_version' => app()->version(),
        ];
    }

    /**
     * Compare two versions
     * Returns: -1 if version1 < version2, 0 if equal, 1 if version1 > version2
     */
    public function compareVersions(string $version1, string $version2): int
    {
        return version_compare($version1, $version2);
    }

    /**
     * Check if current version is up to date
     */
    public function isUpToDate(): bool
    {
        if (!config('version.update.enabled')) {
            return true;
        }

        $latestVersion = $this->getLatestVersion();
        if (!$latestVersion) {
            return true; // Cannot determine, assume up to date
        }

        return $this->compareVersions($this->getCurrentVersion(), $latestVersion) >= 0;
    }

    /**
     * Get latest version from GitHub
     */
    public function getLatestVersion(): ?string
    {
        if (!config('version.update.enabled')) {
            return null;
        }

        $cacheKey = 'app_latest_version';
        $cacheTtl = config('version.update.cache_ttl', 3600);

        return Cache::remember($cacheKey, $cacheTtl, function () {
            try {
                $apiUrl = config('version.repository.api_url');
                $token = config('version.repository.token', env('GITHUB_TOKEN'));

                // Try GitHub API first (with authentication if available)
                $headers = [];
                if ($token) {
                    $headers['Authorization'] = "Bearer {$token}";
                }

                $response = Http::withHeaders($headers)->timeout(10)->get("{$apiUrl}/releases/latest");

                if ($response->successful()) {
                    $data = $response->json();
                    // Remove 'v' prefix if exists (v1.0.0 -> 1.0.0)
                    return ltrim($data['tag_name'] ?? '', 'v');
                }

                // If GitHub API fails (404, 401, etc.), fall back to git tags
                Log::info('GitHub API not accessible, falling back to git tags', [
                    'status' => $response->status(),
                ]);

                return $this->getLatestVersionFromGitTags();
            } catch (\Exception $e) {
                Log::info('Error fetching from GitHub API, trying git tags', [
                    'error' => $e->getMessage(),
                ]);

                // Fall back to git tags
                return $this->getLatestVersionFromGitTags();
            }
        });
    }

    /**
     * Get latest version from local git tags
     */
    protected function getLatestVersionFromGitTags(): ?string
    {
        try {
            // Fetch latest tags from remote
            exec('git fetch --tags 2>&1', $fetchOutput, $fetchCode);

            // Get all version tags and sort them
            exec('git tag -l "v*" | sort -V | tail -1', $output, $returnCode);

            if ($returnCode === 0 && !empty($output[0])) {
                // Remove 'v' prefix if exists
                return ltrim(trim($output[0]), 'v');
            }

            Log::warning('No git tags found');
            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching git tags', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get all available versions from GitHub
     */
    public function getAvailableVersions(): array
    {
        if (!config('version.update.enabled')) {
            return [];
        }

        $cacheKey = 'app_available_versions';
        $cacheTtl = config('version.update.cache_ttl', 3600);

        return Cache::remember($cacheKey, $cacheTtl, function () {
            try {
                $apiUrl = config('version.repository.api_url');
                $token = config('version.repository.token', env('GITHUB_TOKEN'));

                // Try GitHub API first (with authentication if available)
                $headers = [];
                if ($token) {
                    $headers['Authorization'] = "Bearer {$token}";
                }

                $response = Http::withHeaders($headers)->timeout(10)->get("{$apiUrl}/releases");

                if ($response->successful()) {
                    $releases = $response->json();
                    return collect($releases)->map(function ($release) {
                        return [
                            'version' => ltrim($release['tag_name'] ?? '', 'v'),
                            'name' => $release['name'] ?? '',
                            'published_at' => $release['published_at'] ?? '',
                            'prerelease' => $release['prerelease'] ?? false,
                            'url' => $release['html_url'] ?? '',
                            'body' => $release['body'] ?? '',
                        ];
                    })->toArray();
                }

                // Fall back to git tags if GitHub API fails
                Log::info('GitHub API not accessible for releases list, falling back to git tags');
                return $this->getAvailableVersionsFromGitTags();
            } catch (\Exception $e) {
                Log::info('Error fetching releases from GitHub API, trying git tags', [
                    'error' => $e->getMessage(),
                ]);

                return $this->getAvailableVersionsFromGitTags();
            }
        });
    }

    /**
     * Get all available versions from local git tags
     */
    protected function getAvailableVersionsFromGitTags(): array
    {
        try {
            // Fetch latest tags from remote
            exec('git fetch --tags 2>&1', $fetchOutput, $fetchCode);

            // Get all version tags sorted by version
            exec('git tag -l "v*" | sort -V', $output, $returnCode);

            if ($returnCode === 0 && !empty($output)) {
                return collect($output)->map(function ($tag) {
                    $version = ltrim(trim($tag), 'v');

                    // Try to get the date of the tag
                    exec("git log -1 --format=%ai " . escapeshellarg($tag), $dateOutput);
                    $date = !empty($dateOutput[0]) ? $dateOutput[0] : null;

                    return [
                        'version' => $version,
                        'name' => $tag,
                        'published_at' => $date,
                        'prerelease' => false,
                        'url' => '',
                        'body' => '',
                    ];
                })->reverse()->values()->toArray(); // Reverse to show newest first
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Error fetching git tags list', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Check for updates and return information
     */
    public function checkForUpdates(): array
    {
        $currentVersion = $this->getCurrentVersion();
        $latestVersion = $this->getLatestVersion();

        if (!$latestVersion) {
            return [
                'update_available' => false,
                'current_version' => $currentVersion,
                'latest_version' => null,
                'message' => 'ไม่สามารถตรวจสอบเวอร์ชั่นล่าสุดได้',
            ];
        }

        $comparison = $this->compareVersions($currentVersion, $latestVersion);

        if ($comparison < 0) {
            return [
                'update_available' => true,
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'message' => "มีเวอร์ชั่นใหม่! {$latestVersion} (คุณใช้งาน {$currentVersion})",
                'changelog_url' => config('version.changelog_url'),
            ];
        }

        if ($comparison > 0) {
            return [
                'update_available' => false,
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'message' => "คุณใช้งานเวอร์ชั่นที่ใหม่กว่า ({$currentVersion}) เวอร์ชั่นล่าสุดคือ {$latestVersion}",
            ];
        }

        return [
            'update_available' => false,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'message' => 'คุณใช้งานเวอร์ชั่นล่าสุดแล้ว',
        ];
    }

    /**
     * Clear version cache
     */
    public function clearCache(): void
    {
        Cache::forget('app_latest_version');
        Cache::forget('app_available_versions');
    }

    /**
     * Check if PHP version meets requirements
     */
    public function checkPhpVersion(): bool
    {
        $minPhpVersion = config('version.min_php_version');
        return version_compare(PHP_VERSION, $minPhpVersion, '>=');
    }

    /**
     * Get system requirements status
     */
    public function getSystemRequirements(): array
    {
        $minPhpVersion = config('version.min_php_version');
        $phpVersionOk = $this->checkPhpVersion();

        return [
            'php' => [
                'current' => PHP_VERSION,
                'required' => $minPhpVersion,
                'status' => $phpVersionOk ? 'ok' : 'error',
            ],
            'laravel' => [
                'version' => app()->version(),
                'status' => 'ok',
            ],
            'database' => [
                'driver' => config('database.default'),
                'status' => 'ok',
            ],
        ];
    }

    /**
     * Bump version in VERSION file
     */
    public function bumpVersion(string $type = 'patch'): string
    {
        $currentVersion = $this->getCurrentVersion();
        [$major, $minor, $patch] = explode('.', $currentVersion);

        switch ($type) {
            case 'major':
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case 'minor':
                $minor++;
                $patch = 0;
                break;
            case 'patch':
            default:
                $patch++;
                break;
        }

        $newVersion = "{$major}.{$minor}.{$patch}";
        file_put_contents(base_path('VERSION'), $newVersion);

        return $newVersion;
    }

    /**
     * Get changelog content
     */
    public function getChangelog(): ?string
    {
        $changelogPath = base_path('CHANGELOG.md');

        if (file_exists($changelogPath)) {
            return file_get_contents($changelogPath);
        }

        return null;
    }

    /**
     * Parse changelog for specific version
     */
    public function getChangelogForVersion(string $version): ?string
    {
        $changelog = $this->getChangelog();

        if (!$changelog) {
            return null;
        }

        // Extract section for specific version
        $pattern = "/## \[{$version}\].*?(?=\n## \[|$)/s";

        if (preg_match($pattern, $changelog, $matches)) {
            return trim($matches[0]);
        }

        return null;
    }
}
