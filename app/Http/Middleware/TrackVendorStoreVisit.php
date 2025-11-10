<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\VendorStore;
use App\Models\VendorStoreVisit;
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
            logger()->error('Failed to track vendor visit: ' . $e->getMessage());
        }
    }

    /**
     * Get the vendor store from the request
     */
    protected function getStoreFromRequest(Request $request): ?VendorStore
    {
        // Check if the route has a store parameter
        $storeId = $request->route('store');
        if ($storeId) {
            return VendorStore::find($storeId);
        }

        // Check if the route has a store_slug parameter
        $storeSlug = $request->route('store_slug');
        if ($storeSlug) {
            return VendorStore::where('store_slug', $storeSlug)->first();
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
