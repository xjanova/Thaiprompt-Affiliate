<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Models\TarotCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Public tarot-card catalog consumed by the จันทรา.online (juntraweb) importer.
 *
 * Returns the CURRENT image_url for every active card keyed by name_en, so
 * juntraweb's TarotImporter resolves filenames live here instead of hardcoding
 * them — making it immune to Laravel's random storage-filename rotation.
 *
 * No auth: the card art is already public on the website, and the importer runs
 * as an admin/CLI action with no end-user token. The response is cached briefly
 * and the route is rate-limited (DoS defense).
 *
 * Path: GET /api/v1/juntra/tarot/cards  (matches juntraweb TarotImporter's call)
 * Shape: { "data": [ { "name_en": "...", "image_url": "https://.../x.webp" | null }, ... ] }
 */
class TarotCatalogController extends Controller
{
    public function cards(): JsonResponse
    {
        // Short TTL: this is a tiny (78-row) read, so the cache exists only to
        // blunt abuse of a public endpoint — not to serve stale art. 60s keeps
        // a re-import right after an admin replaces a card's art from silently
        // pulling the old image.
        $data = Cache::remember('juntra:tarot:catalog:v1', now()->addSeconds(60), function () {
            return TarotCard::query()
                ->where('is_active', true)
                ->orderBy('type')
                ->orderBy('number')
                ->get(['name_en', 'image_url'])
                ->map(function (TarotCard $c) {
                    // Read the RAW column to tell a genuine NULL apart from the
                    // accessor's default-card.svg placeholder. For real art, reuse
                    // the model's own URL resolver (handles absolute / /storage /
                    // tarot/ forms); advertise art-less cards honestly as null so
                    // juntra skips them instead of fetching a non-image SVG.
                    $raw = $c->getRawImageUrl();

                    return [
                        'name_en'   => $c->name_en,
                        'image_url' => ($raw !== null && trim($raw) !== '') ? $c->image_url : null,
                    ];
                })
                ->values()
                ->all();
        });

        return response()->json(['data' => $data]);
    }
}
