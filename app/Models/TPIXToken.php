<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TPIXToken extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tpix_tokens';

    protected $fillable = [
        'creator_id',
        'name',
        'symbol',
        'description',
        'logo',
        'website',
        'whitepaper_url',
        'contract_address',
        'deployment_tx_hash',
        'decimals',
        'total_supply',
        'initial_supply',
        'is_mintable',
        'is_burnable',
        'is_pausable',
        'is_freezable',
        'has_max_supply',
        'max_supply',
        'initial_price_tpix',
        'current_price_tpix',
        'market_cap_tpix',
        'volume_24h_tpix',
        'liquidity_tpix',
        'distribution_plan',
        'team_allocation_percent',
        'public_sale_percent',
        'liquidity_percent',
        'marketing_percent',
        'has_vesting',
        'vesting_schedule',
        'trading_starts_at',
        'status',
        'is_verified',
        'is_listed',
        'is_featured',
        'holders_count',
        'transfers_count',
        'total_burned',
        'total_minted',
        'telegram',
        'twitter',
        'discord',
        'github',
        'medium',
        'cmc_id',
        'cmc_last_sync',
        'cmc_data',
        'referral_code',
        'referred_by',
        'referral_rewards_paid',
        'kyc_required',
        'kyc_verified',
        'audit_completed',
        'audit_report_url',
        'security_features',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'is_mintable' => 'boolean',
        'is_burnable' => 'boolean',
        'is_pausable' => 'boolean',
        'is_freezable' => 'boolean',
        'has_max_supply' => 'boolean',
        'has_vesting' => 'boolean',
        'is_verified' => 'boolean',
        'is_listed' => 'boolean',
        'is_featured' => 'boolean',
        'kyc_required' => 'boolean',
        'kyc_verified' => 'boolean',
        'audit_completed' => 'boolean',
        'distribution_plan' => 'array',
        'vesting_schedule' => 'array',
        'security_features' => 'array',
        'metadata' => 'array',
        'cmc_data' => 'array',
        'trading_starts_at' => 'datetime',
        'cmc_last_sync' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function balances()
    {
        return $this->hasMany(TPIXTokenBalance::class, 'token_id');
    }

    public function transfers()
    {
        return $this->hasMany(TPIXTokenTransfer::class, 'token_id');
    }

    public function stakingPools()
    {
        return $this->hasMany(TPIXStakingPool::class, 'token_id');
    }

    public function controlRules()
    {
        return $this->hasMany(CoinControlRule::class, 'token_id');
    }

    public function controlActions()
    {
        return $this->hasMany(CoinControlAction::class, 'token_id');
    }

    public function cmcSyncLogs()
    {
        return $this->hasMany(CMCSyncLog::class, 'token_id');
    }

    /**
     * DEX Relationships
     */
    public function liquidityPoolsAsTokenA()
    {
        return $this->hasMany(TPIXLiquidityPool::class, 'token_a_id');
    }

    public function liquidityPoolsAsTokenB()
    {
        return $this->hasMany(TPIXLiquidityPool::class, 'token_b_id');
    }

    public function swapsAsTokenIn()
    {
        return $this->hasMany(TPIXSwap::class, 'token_in_id');
    }

    public function swapsAsTokenOut()
    {
        return $this->hasMany(TPIXSwap::class, 'token_out_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeListed($query)
    {
        return $query->where('is_listed', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeDeployed($query)
    {
        return $query->where('status', 'deployed');
    }

    /**
     * Attributes & Helpers
     */
    public function getCirculatingSupplyAttribute()
    {
        return bcsub($this->total_supply, $this->total_burned, 8);
    }

    public function getMarketCapUsdAttribute()
    {
        if (!$this->current_price_tpix) {
            return null;
        }

        // Assume TPIX price (would come from settings or exchange)
        $tpixPriceUsd = 20; // Example: 20 USD per TPIX

        $marketCapTpix = bcmul($this->circulating_supply, $this->current_price_tpix, 8);
        return bcmul($marketCapTpix, (string)$tpixPriceUsd, 2);
    }

    public function isOwner($userId)
    {
        return $this->creator_id == $userId;
    }

    public function canMint()
    {
        return $this->is_mintable && $this->status === 'active';
    }

    public function canBurn()
    {
        return $this->is_burnable && $this->status === 'active';
    }

    public function isPaused()
    {
        return $this->status === 'paused';
    }

    public function isDeployed()
    {
        return !empty($this->contract_address) && in_array($this->status, ['deployed', 'verified', 'active']);
    }

    /**
     * Generate unique referral code
     */
    public static function generateReferralCode()
    {
        do {
            $code = 'TK-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Update market data
     */
    public function updateMarketData(array $data)
    {
        $this->update([
            'current_price_tpix' => $data['price_tpix'] ?? $this->current_price_tpix,
            'market_cap_tpix' => $data['market_cap_tpix'] ?? $this->market_cap_tpix,
            'volume_24h_tpix' => $data['volume_24h_tpix'] ?? $this->volume_24h_tpix,
            'liquidity_tpix' => $data['liquidity_tpix'] ?? $this->liquidity_tpix,
        ]);
    }

    /**
     * Update statistics
     */
    public function updateStatistics()
    {
        $this->holders_count = $this->balances()->where('balance', '>', 0)->count();
        $this->transfers_count = $this->transfers()->count();
        $this->save();
    }
}
