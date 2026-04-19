<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ingests analytics events from the mobile app.
 *
 * Expected payload:
 *   {
 *     "session": { "id":"...", "started_at":"...", "app_version":"1.0.0",
 *                  "device_platform":"android", "device_tier":"mid",
 *                  "start_geohash":"w5cwr" },
 *     "events": [
 *       { "name":"screen_view", "ts":"2026-04-19T10:00:00Z",
 *         "props":{"route":"/home"}, "geohash":"w5cwr" },
 *       { "name":"product_tap", "ts":"2026-04-19T10:00:05Z",
 *         "props":{"product_id":123,"surface":"home_nearby","position":2},
 *         "geohash":"w5cwr" }
 *     ]
 *   }
 *
 * Performance:
 *   - Rate-limited via `throttle:60,1` (per-user 60 batches/min).
 *   - Uses bulk-insert; no per-row ORM overhead.
 *   - `product_tap` events are mirrored to the `product_impressions` table for
 *     recommendation-training purposes.
 */
class AnalyticsApiController extends Controller
{
    public function batch(Request $request): JsonResponse
    {
        $user = $request->user();

        // Respect consent — users who opted out never reach us with events in
        // the first place (client filters), but double-check here as defense.
        if ($user && ! ($user->analytics_consent ?? true)) {
            return response()->json(['ok' => true, 'dropped_reason' => 'consent'], 202);
        }

        $data = $request->validate([
            'session'           => 'nullable|array',
            'session.id'        => 'nullable|string|max:64',
            'session.started_at'=> 'nullable|date',
            'session.app_version'     => 'nullable|string|max:16',
            'session.device_platform' => 'nullable|in:android,ios',
            'session.device_tier'     => 'nullable|in:low,mid,high',
            'session.start_geohash'   => 'nullable|string|max:12',

            'events'                  => 'required|array|min:1|max:200',
            'events.*.name'           => 'required|string|max:48',
            'events.*.ts'             => 'required|date',
            'events.*.props'          => 'nullable|array',
            'events.*.geohash'        => 'nullable|string|max:12',
        ]);

        $now = now();
        $session = $data['session'] ?? [];
        $sessionId = $session['id'] ?? null;

        // Upsert session
        if ($sessionId) {
            DB::table('app_sessions')->upsert([
                'session_id'       => $sessionId,
                'user_id'          => $user?->id,
                'started_at'       => $session['started_at'] ?? $now,
                'app_version'      => $session['app_version'] ?? null,
                'device_platform'  => $session['device_platform'] ?? null,
                'device_tier'      => $session['device_tier'] ?? null,
                'start_geohash'    => $session['start_geohash'] ?? null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ], ['session_id'], ['updated_at']);
        }

        // Trim IP to /24 for privacy
        $ipParts = explode('.', $request->ip() ?? '');
        $ipPrefix = count($ipParts) === 4 ? "{$ipParts[0]}.{$ipParts[1]}.{$ipParts[2]}.0" : null;

        // Bulk-insert events
        $rows = array_map(fn ($e) => [
            'user_id'         => $user?->id,
            'session_id'      => $sessionId,
            'event_name'      => $e['name'],
            'props'           => isset($e['props']) ? json_encode($e['props']) : null,
            'geohash'         => $e['geohash'] ?? null,
            'device_platform' => $session['device_platform'] ?? null,
            'device_tier'     => $session['device_tier'] ?? null,
            'app_version'     => $session['app_version'] ?? null,
            'ip_prefix'       => $ipPrefix,
            'ts'              => $e['ts'],
            'created_at'      => $now,
            'updated_at'      => $now,
        ], $data['events']);

        DB::table('app_events')->insert($rows);

        // Derive product impressions from product_view / product_tap events
        $impressions = [];
        foreach ($data['events'] as $e) {
            if (! in_array($e['name'], ['product_view', 'product_tap'])) continue;
            $productId = $e['props']['product_id'] ?? null;
            if (! $productId) continue;
            $impressions[] = [
                'user_id'    => $user?->id,
                'product_id' => $productId,
                'surface'    => $e['props']['surface'] ?? 'unknown',
                'position'   => $e['props']['position'] ?? null,
                'tapped'     => $e['name'] === 'product_tap',
                'geohash'    => $e['geohash'] ?? null,
                'session_id' => $sessionId,
                'ts'         => $e['ts'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (! empty($impressions)) {
            DB::table('product_impressions')->insert($impressions);
        }

        return response()->json([
            'ok'         => true,
            'accepted'   => count($rows),
            'impressions' => count($impressions),
        ]);
    }
}
