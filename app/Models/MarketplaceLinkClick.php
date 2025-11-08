<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceLinkClick extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'affiliate_link_id', 'ip_address', 'user_agent', 'referer_url',
        'country_code', 'city', 'session_id', 'is_unique_click', 'converted', 'order_id', 'clicked_at',
    ];

    protected $casts = [
        'is_unique_click' => 'boolean',
        'converted' => 'boolean',
        'clicked_at' => 'datetime',
    ];

    public function affiliateLink(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAffiliateLink::class, 'affiliate_link_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'order_id');
    }
}
