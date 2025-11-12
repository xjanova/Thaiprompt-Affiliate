<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VersionService
{
    /**
     * Get GitHub token from database or .env
     */
    protected function getGitHubToken(): ?string
    {
        try {
            // Try to get from database settings first (encrypted)
            $encryptedToken = \App\Models\Setting::get('update_github_token');
            if ($encryptedToken) {
                return \Illuminate\Support\Facades\Crypt::decryptString($encryptedToken);
            }
        } catch (\Exception $e) {
            // If decrypt fails or database not available, fall back to env
            Log::debug('Could not get GitHub token from database: ' . $e->getMessage());
        }

        // Fallback to .env
        return config('version.repository.token', env('GITHUB_TOKEN'));
    }

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
                $token = $this->getGitHubToken();

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
            // Check if exec() is available
            if (!function_exists('exec')) {
                Log::info('exec() is disabled - cannot fetch git tags');
                return null;
            }

            // Fetch latest tags from remote
            @exec('git fetch --tags 2>&1', $fetchOutput, $fetchCode);

            // Get all version tags and sort them
            @exec('git tag -l "v*" | sort -V | tail -1', $output, $returnCode);

            if ($returnCode === 0 && !empty($output[0])) {
                // Remove 'v' prefix if exists
                return ltrim(trim($output[0]), 'v');
            }

            Log::warning('No git tags found');
            return null;
        } catch (\Throwable $e) {
            Log::info('Error fetching git tags (fallback)', [
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
                $token = $this->getGitHubToken();

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
            // Check if exec() is available
            if (!function_exists('exec')) {
                Log::info('exec() is disabled - cannot fetch git tags');
                return [];
            }

            // Fetch latest tags from remote
            @exec('git fetch --tags 2>&1', $fetchOutput, $fetchCode);

            // Get all version tags sorted by version
            @exec('git tag -l "v*" | sort -V', $output, $returnCode);

            if ($returnCode === 0 && !empty($output)) {
                return collect($output)->map(function ($tag) {
                    $version = ltrim(trim($tag), 'v');

                    // Try to get the date of the tag
                    @exec("git log -1 --format=%ai " . escapeshellarg($tag), $dateOutput);
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
        } catch (\Throwable $e) {
            Log::info('Error fetching git tags list (fallback)', [
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

    /**
     * Test GitHub connection and API access
     */
    public function testGitHubConnection(): array
    {
        $results = [
            'success' => true,
            'checks' => [],
            'errors' => [],
            'debug_info' => [],
        ];

        try {
            // Test 1: GitHub API reachability
            $apiUrl = config('version.repository.api_url');
            $token = $this->getGitHubToken();

            $results['debug_info']['api_url'] = $apiUrl;
            $results['debug_info']['has_token'] = !empty($token);
            $results['debug_info']['php_version'] = PHP_VERSION;
            $results['debug_info']['curl_enabled'] = function_exists('curl_version');
            $results['debug_info']['openssl_enabled'] = extension_loaded('openssl');

            Log::info('Testing GitHub connection', $results['debug_info']);

            $headers = [];
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            $startTime = microtime(true);
            $response = null; // Initialize to prevent undefined variable errors

            try {
                $response = Http::withHeaders($headers)->timeout(10)->get($apiUrl);
                $responseTime = round((microtime(true) - $startTime) * 1000, 2);

                Log::info('GitHub API response received', [
                    'status' => $response->status(),
                    'response_time' => $responseTime,
                    'url' => $apiUrl,
                ]);

                if ($response->successful()) {
                    $results['checks'][] = [
                        'check' => 'GitHub API Reachability',
                        'status' => 'passed',
                        'message' => "Repository accessible (response time: {$responseTime}ms)",
                        'response_time' => $responseTime,
                    ];
                } else {
                    $results['success'] = false;
                    $errorBody = substr($response->body(), 0, 500);
                    $results['errors'][] = [
                        'check' => 'GitHub API Reachability',
                        'status' => 'failed',
                        'message' => "HTTP {$response->status()}: {$errorBody}",
                        'response_time' => $responseTime,
                        'url' => $apiUrl,
                        'full_error' => $response->body(),
                    ];

                    Log::error('GitHub API not reachable', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'url' => $apiUrl,
                    ]);
                }
            } catch (\Throwable $connectionError) {
                $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                $results['success'] = false;
                $response = null; // Ensure $response is null on error
                $results['errors'][] = [
                    'check' => 'GitHub API Reachability',
                    'status' => 'failed',
                    'message' => "Connection failed: {$connectionError->getMessage()}",
                    'response_time' => $responseTime,
                    'url' => $apiUrl,
                    'error_type' => get_class($connectionError),
                ];

                Log::error('GitHub API connection failed', [
                    'error' => $connectionError->getMessage(),
                    'type' => get_class($connectionError),
                    'url' => $apiUrl,
                    'trace' => $connectionError->getTraceAsString(),
                ]);
            }

            // Test 2: Fetch latest release (only if initial connection was successful)
            if ($response !== null && $response->successful()) {
                $releaseResponse = Http::withHeaders($headers)->timeout(10)->get("{$apiUrl}/releases/latest");

                if ($releaseResponse->successful()) {
                    $data = $releaseResponse->json();
                    $version = ltrim($data['tag_name'] ?? 'unknown', 'v');

                    $results['checks'][] = [
                        'check' => 'Latest Release',
                        'status' => 'passed',
                        'message' => "Latest version available: {$version}",
                        'version' => $version,
                    ];
                } else {
                    $results['errors'][] = [
                        'check' => 'Latest Release',
                        'status' => 'warning',
                        'message' => "Could not fetch latest release (HTTP {$releaseResponse->status()})",
                    ];
                }

                // Test 3: Fetch all releases
                $releasesResponse = Http::withHeaders($headers)->timeout(10)->get("{$apiUrl}/releases");

                if ($releasesResponse->successful()) {
                    $releases = $releasesResponse->json();
                    $count = count($releases);

                    $results['checks'][] = [
                        'check' => 'Releases List',
                        'status' => 'passed',
                        'message' => "Found {$count} releases",
                        'count' => $count,
                    ];
                } else {
                    $results['errors'][] = [
                        'check' => 'Releases List',
                        'status' => 'warning',
                        'message' => "Could not fetch releases list (HTTP {$releasesResponse->status()})",
                    ];
                }
            }

            // Test 4: Check download URL accessibility (only if initial connection was successful)
            if ($response !== null && $response->successful()) {
                $releaseResponse = Http::withHeaders($headers)->timeout(10)->get("{$apiUrl}/releases/latest");

                if ($releaseResponse->successful()) {
                    $data = $releaseResponse->json();
                    $downloadUrl = $data['zipball_url'] ?? null;

                    if ($downloadUrl) {
                        try {
                            // Test if the download URL is accessible (increased timeout for slower connections)
                            $headResponse = Http::timeout(15)->head($downloadUrl);

                            if ($headResponse->successful()) {
                                $results['checks'][] = [
                                    'check' => 'Download URL',
                                    'status' => 'passed',
                                    'message' => 'Download URL is accessible',
                                    'url' => $downloadUrl,
                                ];
                            } else {
                                $results['errors'][] = [
                                    'check' => 'Download URL',
                                    'status' => 'warning',
                                    'message' => "Download URL returned HTTP {$headResponse->status()}",
                                    'note' => 'This is optional - updates can still work via API',
                                ];
                            }
                        } catch (\Throwable $downloadError) {
                            // Download URL test is not critical - mark as warning only
                            $results['errors'][] = [
                                'check' => 'Download URL',
                                'status' => 'warning',
                                'message' => "Download URL test failed: {$downloadError->getMessage()}",
                                'note' => 'This is optional - updates can still work via API',
                            ];

                            Log::info('Download URL test failed (non-critical)', [
                                'error' => $downloadError->getMessage(),
                                'url' => $downloadUrl,
                            ]);
                        }
                    }
                }
            }

            // Test 5: Test git command availability (optional - not critical for updates)
            try {
                if (function_exists('shell_exec')) {
                    $gitAvailable = @shell_exec('git --version 2>&1');
                    if ($gitAvailable && !empty(trim($gitAvailable))) {
                        $results['checks'][] = [
                            'check' => 'Git Command',
                            'status' => 'passed',
                            'message' => trim($gitAvailable),
                        ];
                    } else {
                        $results['errors'][] = [
                            'check' => 'Git Command',
                            'status' => 'warning',
                            'message' => 'Git command not available (fallback to API only)',
                            'note' => 'Not required - updates work via API',
                        ];
                    }
                } else {
                    $results['errors'][] = [
                        'check' => 'Git Command',
                        'status' => 'warning',
                        'message' => 'shell_exec() is disabled on this server',
                        'note' => 'Not required - updates work via API',
                    ];
                }
            } catch (\Throwable $e) {
                $results['errors'][] = [
                    'check' => 'Git Command',
                    'status' => 'warning',
                    'message' => 'Could not check git availability: ' . $e->getMessage(),
                    'note' => 'Not required - updates work via API',
                ];
            }

            // Set overall success status
            // Success only if there are no errors with 'failed' status (warnings are acceptable)
            $hasFailed = !empty(array_filter($results['errors'], fn($e) => $e['status'] === 'failed'));
            $results['success'] = !$hasFailed;

        } catch (\Throwable $e) {
            $results['success'] = false;
            $results['errors'][] = [
                'check' => 'Connection Test',
                'status' => 'failed',
                'message' => $e->getMessage(),
                'error_type' => get_class($e),
            ];

            Log::error('GitHub connection test failed unexpectedly', [
                'error' => $e->getMessage(),
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $results;
    }
}
