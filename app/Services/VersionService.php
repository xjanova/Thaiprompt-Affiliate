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
                $response = Http::timeout(10)->get("{$apiUrl}/releases/latest");

                if ($response->successful()) {
                    $data = $response->json();
                    // Remove 'v' prefix if exists (v1.0.0 -> 1.0.0)
                    return ltrim($data['tag_name'] ?? '', 'v');
                }

                Log::warning('Failed to fetch latest version from GitHub', [
                    'status' => $response->status(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Error fetching latest version', [
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
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
                $response = Http::timeout(10)->get("{$apiUrl}/releases");

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

                return [];
            } catch (\Exception $e) {
                Log::error('Error fetching available versions', [
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
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
