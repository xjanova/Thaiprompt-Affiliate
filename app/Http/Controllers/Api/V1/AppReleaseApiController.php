<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Serves the latest published release for the app's auto-update flow.
 *
 * Endpoint:
 *   GET /api/v1/app/latest-version?platform=android&channel=stable
 *
 * Response:
 *   {
 *     "data": {
 *       "latest_version":        "1.0.3",
 *       "latest_build":          10003,
 *       "min_supported_version": "1.0.0",
 *       "release_notes_md":      "## Thaiprompt 1.0.3 …",
 *       "apk_url":               "https://github.com/xjanova/thaipromptapp/releases/download/v1.0.3/app-release.apk",
 *       "apk_size_bytes":        18456320,
 *       "play_store_url":        "https://play.google.com/store/apps/details?id=app.thaiprompt.thaipromptapp",
 *       "published_at":          "2026-04-19T10:30:00Z"
 *     }
 *   }
 *
 * Cache: 60s (trade-off: upgrade propagation vs DB load).
 */
class AppReleaseApiController extends Controller
{
    public function latest(Request $request): JsonResponse
    {
        $platform = $request->query('platform', 'android');
        $channel = $request->query('channel', 'stable');

        // Accept X-Device-Platform header as fallback (client sends this
        // on every request via ApiClient interceptor).
        $platform = in_array($platform, ['android', 'ios'])
            ? $platform
            : ($request->header('X-Device-Platform', 'android'));

        $cacheKey = "app_release:{$platform}:{$channel}";
        $payload = Cache::remember($cacheKey, 60, function () use ($platform, $channel) {
            return DB::table('app_releases')
                ->where('platform', $platform)
                ->where('channel', $channel)
                ->where('published', true)
                ->whereNotNull('published_at')
                ->orderByDesc('build_number')
                ->first();
        });

        if (! $payload) {
            return response()->json([
                'data' => null,
                'message' => 'ยังไม่มีการเผยแพร่สำหรับแพลตฟอร์มนี้',
            ], 404);
        }

        return response()->json([
            'data' => [
                'latest_version'        => $payload->version,
                'latest_build'          => (int) $payload->build_number,
                'min_supported_version' => $payload->min_supported_version ?? $payload->version,
                'release_notes_md'      => $payload->release_notes_md ?? '',
                'apk_url'               => $payload->apk_url,
                'apk_size_bytes'        => $payload->apk_size_bytes ? (int) $payload->apk_size_bytes : null,
                'apk_sha256'            => $payload->apk_sha256,
                'play_store_url'        => $payload->play_store_url,
                'published_at'          => $payload->published_at,
            ],
        ]);
    }
}
