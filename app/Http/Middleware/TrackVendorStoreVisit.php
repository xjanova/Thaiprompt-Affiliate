<?php

namespace App\Http\Middleware;

use App\Models\VendorStore;
use App\Models\VendorStoreVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVendorStoreVisit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Track the visit after the response is sent
        $response = $next($request);

        // Only track GET requests to avoid duplicate tracking
        if ($request->isMethod('GET')) {
            $this->trackVisit($request);
        }

        return $response;
    }

    /**
     * Track the visit
     */
    protected function trackVisit(Request $request): void
    {
        try {
            // Check if this is a vendor store page
            $store = $this->getStoreFromRequest($request);

            if ($store) {
                VendorStoreVisit::recordVisit($store, $request);
            }
        } catch (\Exception $e) {
            // Silently fail - don't break the user experience
            logger()->error('Failed to track vendor visit: '.$e->getMessage());
        }
    }

    /**
     * Get the vendor store from the request
     * รองรับทั้งกรณีที่ route parameter เป็น model instance (route model binding) หรือ ID
     */
    protected function getStoreFromRequest(Request $request): ?VendorStore
    {
        // Check if the route has a store parameter
        $storeParam = $request->route('store');
        if ($storeParam) {
            // กรณี route model binding - parameter เป็น VendorStore instance แล้ว
            if ($storeParam instanceof VendorStore) {
                return $storeParam;
            }
            // กรณีเป็น ID (string หรือ int)
            if (is_string($storeParam) || is_int($storeParam)) {
                return VendorStore::find($storeParam);
            }
            // กรณีอื่นๆ ที่ไม่รองรับ ให้ข้ามไป
        }

        // Check if the route has a store_slug parameter
        $storeSlug = $request->route('store_slug');
        if ($storeSlug) {
            // กรณี route model binding
            if ($storeSlug instanceof VendorStore) {
                return $storeSlug;
            }
            if (is_string($storeSlug)) {
                return VendorStore::where('store_slug', $storeSlug)->first();
            }
        }

        // Check if the URL contains /stores/ or /shop/
        $path = $request->path();
        if (preg_match('#(?:stores|shop)/([^/]+)#', $path, $matches)) {
            $slug = $matches[1];

            return VendorStore::where('store_slug', $slug)->first();
        }

        return null;
    }
}
