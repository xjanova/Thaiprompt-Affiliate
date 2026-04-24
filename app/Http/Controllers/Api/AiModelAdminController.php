<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin-only management of on-device model files.
 *
 * Background: earlier versions of the app pulled Gemma .task files
 * through a per-request proxy that streamed from HuggingFace on every
 * download. That (a) makes every user's install depend on HF uptime,
 * (b) hits our HF rate limits with lots of users, and (c) adds ~200ms
 * of latency before the first byte arrives.
 *
 * v1.0.17 flips this around. The files live on our own disk in
 * `public/ai-models/`. Nginx serves them directly — zero PHP in the
 * hot path. This controller is the admin side: fetch-once from HF
 * into local storage, list what's present, delete when we swap
 * models. The app reads `app_configs.ai_model_url_*` which points at
 * `/api/v1/ai/models/{tier}` — that route is a dumb proxy that
 * 302-redirects to the local file when it exists, or falls back to
 * streaming from HF when it doesn't.
 *
 * Routes (registered in routes/api.php under auth:sanctum):
 *   GET    /api/v1/admin/ai/models              — list all
 *   GET    /api/v1/admin/ai/models/{tier}       — info for one
 *   POST   /api/v1/admin/ai/models/{tier}/sync  — fetch from HF to disk
 *   DELETE /api/v1/admin/ai/models/{tier}       — remove local copy
 */
class AiModelAdminController extends Controller
{
    /** Tier → { hf_url, filename }. Mirrors AiModelProxyController.
     *
     * Both entries pull from `litert-community/*` — Google's official
     * MediaPipe-LiteRT mirror. Not gated (no license click-through),
     * but HF still requires an auth token for download — we use
     * HF_TOKEN from .env.
     *
     * `-web.task` is the MediaPipe bundle that flutter_gemma consumes
     * (the `.litertlm` sibling files are for standalone LiteRT-LM and
     * don't work with MediaPipe).
     */
    private const TIER_MAP = [
        'gemma4_e2b' => [
            'hf_url'   => 'https://huggingface.co/litert-community/gemma-4-E2B-it-litert-lm/resolve/main/gemma-4-E2B-it-web.task',
            'filename' => 'gemma-4-E2B-it-web.task',
            'label'    => 'Gemma 4 E2B (2B · default)',
            'size_est' => '2.0 GB',
        ],
        'gemma4_e4b' => [
            'hf_url'   => 'https://huggingface.co/litert-community/gemma-4-E4B-it-litert-lm/resolve/main/gemma-4-E4B-it-web.task',
            'filename' => 'gemma-4-E4B-it-web.task',
            'label'    => 'Gemma 4 E4B (4B · high-end devices)',
            'size_est' => '3.0 GB',
        ],
    ];

    /** Access control — only admins. Returns null if allowed, or a JSON
     *  403 response if not. Matches the convention of the existing
     *  AdminMiddleware but returns JSON (not an HTML redirect) for API
     *  clients.
     */
    private function guardAdmin(): ?JsonResponse
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
        $isAdmin = ($user->is_super_admin ?? false) || ($user->role ?? null) === 'admin';
        if (! $isAdmin) {
            return response()->json(['error' => 'forbidden', 'message' => 'admin only'], 403);
        }
        return null;
    }

    public function index(): JsonResponse
    {
        if ($deny = $this->guardAdmin()) return $deny;

        $dir = public_path('ai-models');
        @mkdir($dir, 0775, true);

        $result = [];
        foreach (self::TIER_MAP as $tier => $info) {
            $path = "$dir/{$info['filename']}";
            $exists = file_exists($path);
            $result[] = [
                'tier'     => $tier,
                'label'    => $info['label'],
                'filename' => $info['filename'],
                'local'    => $exists ? [
                    'path'        => "/ai-models/{$info['filename']}",
                    'size_bytes'  => filesize($path),
                    'modified_at' => date('c', filemtime($path)),
                    'sha256'      => @hash_file('sha256', $path) ?: null,
                ] : null,
                'hf_url'   => $info['hf_url'],
                'app_config_value' => DB::table('app_configs')
                    ->where('key', 'ai_model_url_' . $tier)
                    ->where('environment', app()->environment())
                    ->value('value'),
            ];
        }

        return response()->json([
            'models'      => $result,
            'storage_dir' => $dir,
            'hf_token_set' => (bool) $this->hfToken(),
        ]);
    }

    public function show(string $tier): JsonResponse
    {
        if ($deny = $this->guardAdmin()) return $deny;
        if (! array_key_exists($tier, self::TIER_MAP)) {
            return response()->json(['error' => 'unknown_tier'], 404);
        }
        $info = self::TIER_MAP[$tier];
        $path = public_path('ai-models/' . $info['filename']);
        return response()->json([
            'tier'     => $tier,
            'filename' => $info['filename'],
            'present'  => file_exists($path),
            'size_bytes' => file_exists($path) ? filesize($path) : null,
            'modified_at' => file_exists($path) ? date('c', filemtime($path)) : null,
            'hf_url'   => $info['hf_url'],
        ]);
    }

    /**
     * Downloads the .task from HuggingFace into public/ai-models/.
     *
     * Runs synchronously within the request. Large files (~1.2 GB for
     * gemma4) will block the worker for a while — call from a queue
     * or background job if your web worker timeout is strict. For our
     * setup (PHP max_execution_time is usually bumped for admin
     * actions) this is acceptable.
     *
     * Downloads to a .tmp file first, rename on success — avoids the
     * proxy redirecting users to a half-written file.
     */
    public function sync(Request $request, string $tier): JsonResponse
    {
        if ($deny = $this->guardAdmin()) return $deny;
        if (! array_key_exists($tier, self::TIER_MAP)) {
            return response()->json(['error' => 'unknown_tier'], 404);
        }

        $hfToken = $this->hfToken();
        if (! $hfToken) {
            return response()->json([
                'error' => 'server_not_configured',
                'message' => 'HF_TOKEN is not set in .env',
            ], 503);
        }

        $info = self::TIER_MAP[$tier];
        $dir  = public_path('ai-models');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $finalPath = "$dir/{$info['filename']}";
        $tmpPath   = "$finalPath.downloading";

        // Allow long-running download
        @set_time_limit(0);
        @ini_set('memory_limit', '256M');

        $fp = fopen($tmpPath, 'wb');
        if (! $fp) {
            return response()->json(['error' => 'cannot_open_tmp', 'path' => $tmpPath], 500);
        }

        $ch = curl_init($info['hf_url']);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $hfToken],
            CURLOPT_TIMEOUT        => 0,         // unlimited
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_FAILONERROR    => true,      // treat 4xx/5xx as errors
        ]);
        $start = microtime(true);
        $ok = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        $elapsed = microtime(true) - $start;

        if (! $ok) {
            @unlink($tmpPath);
            return response()->json([
                'error' => 'upstream_failed',
                'http_status' => $code,
                'message' => $err ?: 'curl failed',
            ], 502);
        }

        // Atomic swap: tmp → final. If a previous version existed keep
        // a one-slot rollback so an admin can restore quickly.
        if (file_exists($finalPath)) {
            @unlink("$finalPath.bak");
            @rename($finalPath, "$finalPath.bak");
        }
        rename($tmpPath, $finalPath);
        @chmod($finalPath, 0644);

        return response()->json([
            'ok'         => true,
            'tier'       => $tier,
            'filename'   => $info['filename'],
            'size_bytes' => filesize($finalPath),
            'elapsed_s'  => round($elapsed, 1),
            'path'       => "/ai-models/{$info['filename']}",
            'sha256'     => @hash_file('sha256', $finalPath) ?: null,
        ]);
    }

    public function destroy(string $tier): JsonResponse
    {
        if ($deny = $this->guardAdmin()) return $deny;
        if (! array_key_exists($tier, self::TIER_MAP)) {
            return response()->json(['error' => 'unknown_tier'], 404);
        }

        $info = self::TIER_MAP[$tier];
        $path = public_path('ai-models/' . $info['filename']);
        if (! file_exists($path)) {
            return response()->json(['ok' => true, 'already_absent' => true]);
        }
        unlink($path);
        return response()->json(['ok' => true, 'removed' => $info['filename']]);
    }

    private function hfToken(): ?string
    {
        return env('HF_TOKEN') ?: env('HUGGING_FACE_TOKEN') ?: env('HUGGINGFACE_TOKEN') ?: null;
    }
}
