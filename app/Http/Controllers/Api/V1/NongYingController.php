<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Public endpoints that teach the app + its on-device LLM about
 * น้องหญิง.
 *
 *   GET /api/v1/ai/nong-ying/persona
 *     Returns the persona metadata (system prompt, greetings, suggestions,
 *     voice, etc.) driven by `ai_bot_profiles` row referenced from
 *     `app_configs.nong_ying_bot_profile_id`.
 *
 *     ETag-based caching — client stores the cached blob in SQLite
 *     (kv_store) and sends `If-None-Match` on refresh; server answers
 *     304 when unchanged so the full system prompt doesn't retransmit
 *     every app launch.
 *
 *   GET /api/v1/ai/nong-ying/knowledge?q=…&limit=5
 *     Search the product catalog + fresh-market categories for items
 *     relevant to a user utterance. Returns lightweight rows that the
 *     model can cite in its reply (plus a deep-link path for each so
 *     the `[GO:/…]` convention keeps working).
 */
class NongYingController extends Controller
{
    /**
     * Persona data + ETag.
     *
     * We build the ETag from max(updated_at) across bot profile + the
     * related app_config rows, so any admin edit (system prompt,
     * temperature, suggestions) invalidates client caches on next
     * request.
     */
    public function persona(Request $request): JsonResponse
    {
        $botProfileId = (int) DB::table('app_configs')
            ->where('key', 'nong_ying_bot_profile_id')
            ->where('environment', app()->environment())
            ->value('value');

        if ($botProfileId <= 0) {
            return response()->json([
                'error'   => 'not_configured',
                'message' => 'ยังไม่ตั้งค่า bot profile ของน้องหญิง',
            ], 503);
        }

        $bot = DB::table('ai_bot_profiles')->where('id', $botProfileId)->first();
        if (! $bot) {
            return response()->json([
                'error'   => 'bot_missing',
                'message' => 'bot profile ไม่พบในฐานข้อมูล',
            ], 503);
        }

        $ttsConfig = [];
        if (isset($bot->tts_config) && ! empty($bot->tts_config)) {
            $decoded = json_decode($bot->tts_config, true);
            if (is_array($decoded)) $ttsConfig = $decoded;
        }

        $persona = [
            'version'                  => (string) strtotime($bot->updated_at),
            'bot_profile_id'           => $bot->id,
            'name'                     => $bot->name,
            'system_prompt'            => $bot->system_prompt,
            'temperature'              => (float) $bot->temperature,
            'top_p'                    => (float) $bot->top_p,
            'max_tokens'               => (int) $bot->max_tokens,
            'greeting'                 => self::_greeting(),
            'greeting_not_installed'   => self::_greetingNotInstalled(),
            'suggestions'              => self::_suggestions(),
            'tts'                      => array_merge([
                'voice'                => 'th-premwadee',
                'voices_available'     => ['th-premwadee', 'th-achara'],
                'temperature'          => 0.8,
                'cloud_model'          => 'gemini-2.5-flash-preview-tts',
                'fallback'             => [
                    'engine'           => 'piper',
                    'voice_id'         => 'th_TH-vaja-medium',
                    'auto_install'     => false,
                ],
            ], $ttsConfig),
            'model'                    => [
                'cloud_provider'       => 'gemini',
                'cloud_model'          => 'gemini-2.5-flash',
            ],
            'updated_at'               => $bot->updated_at,
        ];

        $etag = '"' . md5(json_encode($persona)) . '"';
        $ifNoneMatch = $request->header('If-None-Match');
        if ($ifNoneMatch && trim($ifNoneMatch) === $etag) {
            return response()->json(null, 304, ['ETag' => $etag]);
        }

        return response()->json($persona, 200, [
            'ETag'          => $etag,
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    /**
     * Knowledge search — products + categories that match the user's
     * intent. The LLM (cloud or on-device) embeds these results into
     * its reply via the `[GO:/…]` convention.
     *
     * Simple keyword LIKE-scan for now; a FULLTEXT index is a good
     * follow-up when we have more rows.
     */
    public function knowledgeSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q'     => 'required|string|min:1|max:120',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);
        $q     = trim($validated['q']);
        $limit = (int) ($validated['limit'] ?? 5);

        $results = [];

        // ── 1. Products — match name/description
        $products = DB::table('products')
            ->where('is_active', 1)
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%")
                  ->orWhere('short_description', 'like', "%{$q}%");
            })
            ->limit($limit)
            ->get(['id', 'name', 'short_description', 'price', 'compare_at_price',
                   'stock_quantity', 'category_id']);

        foreach ($products as $p) {
            $results[] = [
                'type'     => 'product',
                'id'       => (int) $p->id,
                'title'    => $p->name,
                'subtitle' => $this->_formatPrice($p->price, $p->compare_at_price),
                'summary'  => $p->short_description
                    ? mb_substr($p->short_description, 0, 120)
                    : null,
                'stock'    => (int) $p->stock_quantity,
                'route'    => "/product/{$p->id}",
            ];
        }

        // ── 2. Fresh-market categories — when user types category-ish
        //     words like "ผัก" / "ผลไม้"
        $fmCategories = DB::table('fresh_market_categories')
            ->where('is_active', 1)
            ->where('name', 'like', "%{$q}%")
            ->limit($limit)
            ->get(['id', 'name', 'icon', 'description']);

        foreach ($fmCategories as $c) {
            $results[] = [
                'type'     => 'taladsod_category',
                'id'       => (int) $c->id,
                'title'    => $c->name,
                'subtitle' => $c->description,
                'icon'     => $c->icon,
                'route'    => "/taladsod/listings?category={$c->id}",
            ];
        }

        // ── 3. Product categories
        $prodCategories = DB::table('product_categories')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->where('name', 'like', "%{$q}%")
            ->limit($limit)
            ->get(['id', 'name', 'description']);

        foreach ($prodCategories as $c) {
            $results[] = [
                'type'     => 'product_category',
                'id'       => (int) $c->id,
                'title'    => $c->name,
                'subtitle' => $c->description,
                'route'    => "/shop?category={$c->id}",
            ];
        }

        // De-duplicate + trim to overall limit
        $results = array_slice($results, 0, $limit * 2);

        return response()->json([
            'query'   => $q,
            'count'   => count($results),
            'results' => $results,
        ], 200, [
            'Cache-Control' => 'public, max-age=60',
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function _formatPrice($price, $compareAt): string
    {
        $p = number_format((float) $price, 0);
        if ($compareAt && (float) $compareAt > (float) $price) {
            $was = number_format((float) $compareAt, 0);
            return "฿{$p} (ลดจาก ฿{$was})";
        }
        return "฿{$p}";
    }

    private static function _greeting(): string
    {
        return 'สวัสดีค่ะ 🌸 น้องหญิงเองค่ะ อยากให้น้องช่วยเรื่องอะไรดีคะ? '
            . 'แนะนำสินค้า · เช็คออเดอร์ · ช่วยใช้ Wallet ก็ได้นะคะ';
    }

    private static function _greetingNotInstalled(): string
    {
        return 'สวัสดีค่ะ 🌸 น้องหญิงเองค่ะ ตอนนี้น้องยังไม่ได้ติดตั้งบนเครื่องนะคะ '
            . 'ติดตั้งแล้วน้องจะตอบได้ไว + ใช้ได้แม้ไม่มีเน็ตค่ะ '
            . '[GO:/nong-ying/install] ติดตั้งเลย (ระหว่างนี้ใช้ cloud ได้นะคะ)';
    }

    private static function _suggestions(): array
    {
        return [
            'หาผักสดใกล้บ้าน',
            'ออเดอร์ล่าสุดถึงไหน?',
            'Wallet เหลือเท่าไหร่',
            'อยากปั้น affiliate ทำยังไง',
        ];
    }
}
