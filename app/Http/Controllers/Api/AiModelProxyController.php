<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams Gemma model .task files from HuggingFace to the app while
 * adding a server-side `Authorization: Bearer $HF_TOKEN` header.
 *
 * The app never sees HF credentials. Admin sets `HF_TOKEN` in the
 * server .env; users download through this proxy under a normal
 * https://main.thaiprompt.online/... URL.
 *
 * Route (registered in routes/api.php):
 *   GET /api/v1/ai/models/{tier}
 *     tier ∈ {gemma4, gemma3_4b, gemma3_1b}
 *
 * Range request support: we forward `Range: bytes=start-end` to HF so
 * flutter_gemma's download resume works on flaky mobile connections
 * and partial downloads don't have to start from zero.
 *
 * Caching: the response is marked `public, max-age=86400` so any
 * Cloudflare / Nginx layer in front can cache aggressively. The
 * content never changes for a given tier.
 */
class AiModelProxyController extends Controller
{
    /** Map of tier → upstream HF URL. Keep in sync with
     *  AiModelAdminController::TIER_MAP and
     *  `app_configs.ai_model_url_*`.
     */
    private const TIER_MAP = [
        'gemma4_e2b' => [
            'url'      => 'https://huggingface.co/litert-community/gemma-4-E2B-it-litert-lm/resolve/main/gemma-4-E2B-it-web.task',
            'filename' => 'gemma-4-E2B-it-web.task',
        ],
        'gemma4_e4b' => [
            'url'      => 'https://huggingface.co/litert-community/gemma-4-E4B-it-litert-lm/resolve/main/gemma-4-E4B-it-web.task',
            'filename' => 'gemma-4-E4B-it-web.task',
        ],
    ];

    public function download(Request $request, string $tier): mixed
    {
        if (! array_key_exists($tier, self::TIER_MAP)) {
            return response()->json([
                'error' => 'unknown_tier',
                'tier'  => $tier,
            ], 404);
        }

        $upstream = self::TIER_MAP[$tier];

        // ▶ Prefer the local copy if the admin has already synced it.
        //   Nginx serves `/ai-models/…` directly without PHP in the
        //   hot path — way faster and takes no server-side RAM for a
        //   1.2 GB transfer.
        $localPath = public_path('ai-models/' . $upstream['filename']);
        if (file_exists($localPath)) {
            return redirect('/ai-models/' . rawurlencode($upstream['filename']), 302);
        }

        // ▶ Fallback — stream from HF with a server-side token. This
        //   path works even before admin has synced, but it costs a
        //   full PHP-worker roundtrip per download and hits our HF
        //   rate limit. First-time install only.
        $hfToken = env('HF_TOKEN') ?: env('HUGGING_FACE_TOKEN') ?: env('HUGGINGFACE_TOKEN');
        if (! $hfToken) {
            return response()->json([
                'error'   => 'server_not_configured',
                'message' => 'No local copy cached and HF_TOKEN is not set. Admin must either (a) set HF_TOKEN in .env + run `php artisan config:clear`, or (b) sync the model via POST /api/v1/admin/ai/models/' . $tier . '/sync.',
            ], 503);
        }

        $range    = $request->header('Range');

        // cURL streaming: pipe HF response chunks straight to the
        // client without buffering the whole file (up to 1.2 GB).
        $ch = curl_init($upstream['url']);

        $headers = ['Authorization: Bearer ' . $hfToken];
        if (! empty($range)) {
            $headers[] = 'Range: ' . $range;
        }

        $responseHeaderBag = [];
        $headersSent = false;

        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$responseHeaderBag) {
                $len = strlen($header);
                // Parse "Name: value\r\n" lines; stash forwardable ones.
                $parts = explode(':', trim($header), 2);
                if (count($parts) === 2) {
                    $key = strtolower(trim($parts[0]));
                    $val = trim($parts[1]);
                    if (in_array($key, [
                        'content-length',
                        'content-range',
                        'content-type',
                        'accept-ranges',
                        'etag',
                        'last-modified',
                    ], true)) {
                        $responseHeaderBag[$key] = $val;
                    }
                }
                return $len;
            },
        ]);

        return new StreamedResponse(
            function () use ($ch, &$headersSent) {
                curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($curl, $chunk) {
                    echo $chunk;
                    @ob_flush();
                    @flush();
                    return strlen($chunk);
                });
                curl_exec($ch);
                if (curl_errno($ch)) {
                    // We can't change status code here — we've already
                    // started streaming. Best we can do is stop and
                    // let the client's Content-Length mismatch error.
                    error_log('[AiModelProxy] curl error: ' . curl_error($ch));
                }
                curl_close($ch);
            },
            // Status pre-flighted via HEAD would be better, but that's
            // another round trip. Return 200 optimistically; flutter_gemma
            // treats any 2xx as success and reads Content-Length from headers.
            200,
            array_merge(
                [
                    'Content-Type'           => $responseHeaderBag['content-type'] ?? 'application/octet-stream',
                    'Content-Disposition'    => 'attachment; filename="' . $upstream['filename'] . '"',
                    'Cache-Control'          => 'public, max-age=86400',
                    'Accept-Ranges'          => 'bytes',
                    'X-Thaiprompt-Proxy'     => 'huggingface',
                    'X-Thaiprompt-Tier'      => $tier,
                ],
                // Only forward the headers we saw upstream — arrays passed
                // to StreamedResponse constructor must be flat string=>string.
                array_filter([
                    'Content-Length' => $responseHeaderBag['content-length'] ?? null,
                    'Content-Range'  => $responseHeaderBag['content-range'] ?? null,
                    'ETag'           => $responseHeaderBag['etag'] ?? null,
                    'Last-Modified'  => $responseHeaderBag['last-modified'] ?? null,
                ])
            )
        );
    }

    /** HEAD request — returns model metadata without streaming.
     *  Prefers the local copy's stat(); falls back to a HEAD request
     *  to HF if the model hasn't been synced yet.
     */
    public function head(Request $request, string $tier): mixed
    {
        if (! array_key_exists($tier, self::TIER_MAP)) {
            return response()->json(['error' => 'unknown_tier'], 404);
        }

        $upstream  = self::TIER_MAP[$tier];
        $localPath = public_path('ai-models/' . $upstream['filename']);

        if (file_exists($localPath)) {
            return response()->json([
                'tier'         => $tier,
                'filename'     => $upstream['filename'],
                'size'         => filesize($localPath),
                'source'       => 'local',
                'served_from'  => '/ai-models/' . $upstream['filename'],
                'modified_at'  => date('c', filemtime($localPath)),
            ]);
        }

        $hfToken = env('HF_TOKEN') ?: env('HUGGING_FACE_TOKEN') ?: env('HUGGINGFACE_TOKEN');
        if (! $hfToken) {
            return response()->json(['error' => 'server_not_configured'], 503);
        }

        $ch = curl_init($upstream['url']);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $hfToken],
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $size = null;
        if (preg_match('/^content-length:\s*(\d+)/mi', $raw ?: '', $m)) {
            $size = (int) $m[1];
        }

        return response()->json([
            'tier'     => $tier,
            'filename' => $upstream['filename'],
            'size'     => $size,
            'source'   => 'huggingface',
            'upstream' => $httpCode,
        ]);
    }
}
