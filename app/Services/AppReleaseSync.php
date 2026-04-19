<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Sync GitHub Release payloads into the `app_releases` table that powers
 * the mobile app's auto-update flow (`GET /api/v1/app/latest-version`).
 *
 * Used from two places:
 *   1. WebhookController::handleGitHubRelease — fires automatically on every
 *      `release.published` event from GitHub.
 *   2. `php artisan releases:backfill` — one-shot ingestion for releases that
 *      existed before the webhook was wired up (or after a DB wipe).
 *
 * Both paths share the same upsert so behavior never drifts.
 */
class AppReleaseSync
{
    /**
     * Insert or update one app_releases row from a GitHub Release object.
     *
     * @param  array  $release  The `release` payload from GitHub's webhook,
     *                          OR a single element from `GET /repos/{r}/releases`.
     * @return string|null  'inserted' | 'updated' | null (skipped — no APK asset
     *                      found, draft, or missing tag)
     */
    public function upsertFromGitHubRelease(array $release): ?string
    {
        $tagName = $release['tag_name'] ?? null;
        if (!$tagName) return null;
        if ($release['draft'] ?? false) return null;

        $version = ltrim($tagName, 'vV');

        // build_number = major*10000 + minor*100 + patch.
        // Pre-release suffixes (e.g. "1.0.3-rc1") are stripped before parsing.
        $core = explode('-', $version)[0];
        $parts = array_pad(
            array_map('intval', explode('.', $core)),
            3,
            0
        );
        $buildNumber = $parts[0] * 10000 + $parts[1] * 100 + $parts[2];

        // Find the universal-APK asset (most permissive — works on every ABI).
        // Falls back to any single-ABI APK if no universal exists, which is
        // enough to keep auto-update functional even on partial release sets.
        $assets = $release['assets'] ?? [];
        $apkAsset = $this->findApkAsset($assets);
        if (!$apkAsset) return null; // can't auto-update without an APK URL

        $apkUrl = $apkAsset['browser_download_url'] ?? null;
        $apkSize = isset($apkAsset['size']) ? (int) $apkAsset['size'] : null;
        if (!$apkUrl) return null;

        // Channel: GitHub `prerelease=true` → beta, otherwise stable.
        $channel = ($release['prerelease'] ?? false) ? 'beta' : 'stable';

        $now = now();
        $publishedAt = isset($release['published_at']) && $release['published_at']
            ? Carbon::parse($release['published_at'])
            : $now;

        $existing = DB::table('app_releases')
            ->where('version', $version)
            ->where('platform', 'android')
            ->where('channel', $channel)
            ->first();

        $row = [
            'version'           => $version,
            'build_number'      => $buildNumber,
            'platform'          => 'android',
            'channel'           => $channel,
            'release_notes_md'  => $release['body'] ?? '',
            'apk_url'           => $apkUrl,
            'apk_size_bytes'    => $apkSize,
            'published'         => true,
            'published_at'      => $publishedAt,
            'updated_at'        => $now,
        ];

        if ($existing) {
            DB::table('app_releases')->where('id', $existing->id)->update($row);
            $this->bustCache();
            return 'updated';
        }

        $row['created_at'] = $now;
        DB::table('app_releases')->insert($row);
        $this->bustCache();
        return 'inserted';
    }

    /**
     * Pick the best APK asset from a release.
     * Preference order: `*-universal.apk` > any other `*.apk` that isn't
     * an ABI-specific split (arm64/armeabi/x86).
     */
    protected function findApkAsset(array $assets): ?array
    {
        // First pass: prefer explicit universal builds.
        foreach ($assets as $a) {
            $name = $a['name'] ?? '';
            if (str_contains($name, '-universal.apk')) {
                return $a;
            }
        }
        // Second pass: any APK that isn't a split-per-abi variant.
        foreach ($assets as $a) {
            $name = $a['name'] ?? '';
            if (!str_ends_with($name, '.apk')) continue;
            $isAbiSplit = str_contains($name, 'arm64-v8a')
                || str_contains($name, 'armeabi-v7a')
                || str_contains($name, 'x86_64');
            if (!$isAbiSplit) {
                return $a;
            }
        }
        return null;
    }

    /**
     * Drop the AppReleaseApiController response cache so the next
     * `/v1/app/latest-version` call re-reads from the freshly-updated table.
     */
    protected function bustCache(): void
    {
        Cache::forget('app_release:android:stable');
        Cache::forget('app_release:android:beta');
        Cache::forget('app_release:ios:stable');
        Cache::forget('app_release:ios:beta');
    }
}
