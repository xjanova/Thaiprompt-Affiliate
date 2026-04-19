<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Serves dynamic app config and feature flags to the mobile client.
 *
 * Endpoints:
 *   GET /api/v1/app/config  — key/value map + ETag cache
 *   GET /api/v1/app/flags   — resolved feature flags for the current user/device
 *
 * Pattern: read-heavy, write-rare → aggressive Redis cache (60s) + ETag.
 */
class AppConfigApiController extends Controller
{
    public function config(Request $request): JsonResponse
    {
        $env = app()->environment();

        $data = Cache::remember("app_configs:{$env}", 60, function () use ($env) {
            return DB::table('app_configs')
                ->where('environment', $env)
                ->get(['key', 'value', 'value_type', 'is_public'])
                ->map(fn ($r) => [
                    'key'    => $r->key,
                    'value'  => $this->castValue($r->value, $r->value_type),
                    'public' => (bool) $r->is_public,
                ])
                ->toArray();
        });

        // Filter private keys for unauthenticated callers.
        if (! $request->user()) {
            $data = array_values(array_filter($data, fn ($r) => $r['public']));
        }

        $payload = ['data' => $data, 'fetched_at' => now()->toIso8601String()];
        $etag = 'W/"' . md5(json_encode($payload)) . '"';

        if ($request->header('If-None-Match') === $etag) {
            return response()->json([], 304)->header('ETag', $etag);
        }

        return response()->json($payload)
            ->header('ETag', $etag)
            ->header('Cache-Control', 'private, max-age=60');
    }

    public function flags(Request $request): JsonResponse
    {
        $user = $request->user();
        $platform = $request->header('X-Device-Platform');
        $appVersion = $request->header('X-App-Version');
        $now = now();

        $rows = DB::table('feature_flags')
            ->where('enabled', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->get();

        $resolved = [];
        foreach ($rows as $r) {
            // Platform gate
            if ($r->platform && $r->platform !== $platform) continue;

            // App version gate (semver-ish "1.2.3" compare)
            if ($r->min_app_version && $appVersion && version_compare(
                explode('+', $appVersion)[0], $r->min_app_version, '<'
            )) continue;
            if ($r->max_app_version && $appVersion && version_compare(
                explode('+', $appVersion)[0], $r->max_app_version, '>'
            )) continue;

            // Role gate
            if ($r->role && (! $user || $user->role !== $r->role)) continue;

            // Percentage rollout (sticky per user)
            if ($r->rollout_percent < 100) {
                $bucket = crc32(($user->id ?? 'anon') . ':' . $r->flag_key) % 100;
                if ($bucket >= $r->rollout_percent) continue;
            }

            $resolved[$r->flag_key] = true;
        }

        return response()->json(['flags' => $resolved]);
    }

    private function castValue(?string $raw, string $type)
    {
        if ($raw === null) return null;
        return match ($type) {
            'int'   => (int) $raw,
            'float' => (float) $raw,
            'bool'  => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'json'  => json_decode($raw, true),
            default => $raw,
        };
    }
}
