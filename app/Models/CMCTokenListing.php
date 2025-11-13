<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CMCTokenListing extends Model
{
    use HasFactory;

    protected $table = 'cmc_token_listings';

    protected $fillable = [
        'cmc_id',
        'name',
        'symbol',
        'slug',
        'description',
        'logo',
        'price_usd',
        'price_btc',
        'market_cap_usd',
        'volume_24h_usd',
        'circulating_supply',
        'total_supply',
        'max_supply',
        'percent_change_1h',
        'percent_change_24h',
        'percent_change_7d',
        'percent_change_30d',
        'cmc_rank',
        'num_market_pairs',
        'platform_name',
        'token_address',
        'tags',
        'urls',
        'date_added',
        'last_updated',
        'imported_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tags' => 'array',
        'urls' => 'array',
        'date_added' => 'datetime',
        'last_updated' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function tpixTokens()
    {
        return $this->hasMany(TPIXToken::class, 'cmc_id', 'cmc_id');
    }

    public function syncLogs()
    {
        return $this->hasMany(CMCSyncLog::class, 'cmc_id', 'cmc_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTopRanked($query, $limit = 100)
    {
        return $query->whereNotNull('cmc_rank')
            ->orderBy('cmc_rank', 'asc')
            ->limit($limit);
    }

    public function scopeHighVolume($query)
    {
        return $query->whereNotNull('volume_24h_usd')
            ->where('volume_24h_usd', '>', 1000000)
            ->orderBy('volume_24h_usd', 'desc');
    }

    /**
     * Helpers
     */
    public function getExplorerUrl(): ?string
    {
        if (!$this->platform_name || !$this->token_address) {
            return null;
        }

        $explorers = [
            'Ethereum' => 'https://etherscan.io/token/',
            'BNB Smart Chain' => 'https://bscscan.com/token/',
            'Polygon' => 'https://polygonscan.com/token/',
        ];

        $baseUrl = $explorers[$this->platform_name] ?? null;
        return $baseUrl ? $baseUrl . $this->token_address : null;
    }

    public function getCmcUrl(): string
    {
        return 'https://coinmarketcap.com/currencies/' . $this->slug . '/';
    }

    public function getPriceChangeColor(string $period = '24h'): string
    {
        $field = 'percent_change_' . $period;
        $change = $this->$field ?? 0;

        if ($change > 0) {
            return 'green';
        } elseif ($change < 0) {
            return 'red';
        }

        return 'gray';
    }

    public function updateFromApi(array $data)
    {
        $this->update([
            'price_usd' => $data['quote']['USD']['price'] ?? $this->price_usd,
            'price_btc' => $data['quote']['BTC']['price'] ?? $this->price_btc,
            'market_cap_usd' => $data['quote']['USD']['market_cap'] ?? $this->market_cap_usd,
            'volume_24h_usd' => $data['quote']['USD']['volume_24h'] ?? $this->volume_24h_usd,
            'circulating_supply' => $data['circulating_supply'] ?? $this->circulating_supply,
            'total_supply' => $data['total_supply'] ?? $this->total_supply,
            'max_supply' => $data['max_supply'] ?? $this->max_supply,
            'percent_change_1h' => $data['quote']['USD']['percent_change_1h'] ?? $this->percent_change_1h,
            'percent_change_24h' => $data['quote']['USD']['percent_change_24h'] ?? $this->percent_change_24h,
            'percent_change_7d' => $data['quote']['USD']['percent_change_7d'] ?? $this->percent_change_7d,
            'percent_change_30d' => $data['quote']['USD']['percent_change_30d'] ?? $this->percent_change_30d,
            'cmc_rank' => $data['cmc_rank'] ?? $this->cmc_rank,
            'num_market_pairs' => $data['num_market_pairs'] ?? $this->num_market_pairs,
            'last_updated' => $data['last_updated'] ?? now(),
        ]);
    }
}
