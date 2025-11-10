<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorStoreVisit extends Model
{
    protected $fillable = [
        'store_id',
        'visitor_id',
        'session_id',
        'ip_address',
        'user_agent',
        'page_url',
        'referrer',
        'visited_at',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    /**
     * Get the store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * Generate a visitor ID from request
     */
    public static function generateVisitorId($request): string
    {
        // Use IP + User Agent to create a unique visitor ID
        $identifier = $request->ip() . '|' . $request->userAgent();
        return hash('sha256', $identifier);
    }

    /**
     * Record a visit to a vendor store
     */
    public static function recordVisit(VendorStore $store, $request): self
    {
        $visitorId = self::generateVisitorId($request);
        $sessionId = $request->session()->getId();

        return self::create([
            'store_id' => $store->id,
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'page_url' => $request->fullUrl(),
            'referrer' => $request->header('referer'),
            'visited_at' => now(),
        ]);
    }
}
