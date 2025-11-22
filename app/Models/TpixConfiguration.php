<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * TPIX Configuration Model
 *
 * จัดเก็บการตั้งค่าสำหรับ TPIX Native Coin Deployment Wizard
 *
 * @property int $id
 * @property int $user_id
 * @property string $name ชื่อ configuration
 * @property string $slug Slug สำหรับ URL
 * @property string $status สถานะปัจจุบัน
 * @property int $current_step ขั้นตอนปัจจุบัน (1-7)
 *
 * // Step 1: Prerequisites
 * @property array|null $prerequisites
 *
 * // Step 2: Token Configuration
 * @property string|null $token_name
 * @property string|null $token_symbol
 * @property float|null $total_supply
 * @property int $decimals
 * @property string|null $description
 * @property string|null $logo_url
 * @property string|null $website_url
 * @property string|null $category
 * @property array|null $social_links
 *
 * // Step 3: Tokenomics
 * @property array|null $supply_distribution
 * @property float|null $initial_price_tpix
 * @property float|null $initial_price_usd
 * @property float|null $market_cap_target
 * @property bool $is_mintable
 * @property bool $is_burnable
 * @property bool $is_pausable
 * @property bool $is_freezable
 * @property float|null $max_supply
 * @property array|null $fee_structure
 *
 * // Step 4: Smart Contract
 * @property array|null $contract_features
 * @property string|null $contract_code
 * @property string $access_control
 * @property array|null $safety_features
 * @property bool $is_upgradeable
 *
 * // Step 5: Deployment
 * @property string|null $contract_address
 * @property string|null $deployer_address
 * @property string|null $deploy_tx_hash
 * @property \Carbon\Carbon|null $deployed_at
 * @property bool $is_verified
 * @property string|null $explorer_url
 * @property float|null $deploy_gas_used
 * @property float|null $deploy_cost_tpix
 *
 * // Step 6: DEX Integration
 * @property bool $dex_enabled
 * @property int|null $liquidity_pool_id
 * @property float|null $initial_liquidity_tpix
 * @property float|null $initial_liquidity_token
 * @property float|null $liquidity_lock_days
 * @property array|null $trading_params
 * @property \Carbon\Carbon|null $trading_enabled_at
 *
 * // Step 7: Listing
 * @property bool $cmc_submitted
 * @property string|null $cmc_id
 * @property \Carbon\Carbon|null $cmc_submitted_at
 * @property string|null $cmc_status
 * @property bool $coingecko_submitted
 * @property string|null $coingecko_id
 * @property \Carbon\Carbon|null $coingecko_submitted_at
 * @property string|null $coingecko_status
 * @property array|null $marketing_materials
 * @property string|null $whitepaper_url
 * @property string|null $pitch_deck_url
 *
 * // Metadata
 * @property array|null $deployment_logs
 * @property array|null $error_logs
 * @property string|null $notes
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * // Relationships
 * @property-read User $user
 * @property-read TPIXLiquidityPool|null $liquidityPool
 */
class TpixConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tpix_configurations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'status',
        'current_step',

        // Step 1
        'prerequisites',

        // Step 2
        'token_name',
        'token_symbol',
        'total_supply',
        'decimals',
        'description',
        'logo_url',
        'website_url',
        'category',
        'social_links',

        // Step 3
        'supply_distribution',
        'initial_price_tpix',
        'initial_price_usd',
        'market_cap_target',
        'is_mintable',
        'is_burnable',
        'is_pausable',
        'is_freezable',
        'max_supply',
        'fee_structure',

        // Step 4
        'contract_features',
        'contract_code',
        'access_control',
        'safety_features',
        'is_upgradeable',

        // Step 5
        'contract_address',
        'deployer_address',
        'deploy_tx_hash',
        'deployed_at',
        'is_verified',
        'explorer_url',
        'deploy_gas_used',
        'deploy_cost_tpix',

        // Step 6
        'dex_enabled',
        'liquidity_pool_id',
        'initial_liquidity_tpix',
        'initial_liquidity_token',
        'liquidity_lock_days',
        'trading_params',
        'trading_enabled_at',

        // Step 7
        'cmc_submitted',
        'cmc_id',
        'cmc_submitted_at',
        'cmc_status',
        'coingecko_submitted',
        'coingecko_id',
        'coingecko_submitted_at',
        'coingecko_status',
        'marketing_materials',
        'whitepaper_url',
        'pitch_deck_url',

        // Metadata
        'deployment_logs',
        'error_logs',
        'notes',

        // Payment
        'wallet_address',
        'payment_amount',
        'payment_tx_hash',
        'payment_status',
        'payment_verified_at',
        'pricing_breakdown',
        'subtotal_amount',
        'discount_amount',
        'discount_code',
        'refund_amount',
        'refund_tx_hash',
        'refund_reason',
        'refunded_at',
        'premium_services',
        'referral_code',
        'is_early_adopter',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_step' => 'integer',
        'decimals' => 'integer',
        'total_supply' => 'decimal:8',
        'initial_price_tpix' => 'decimal:8',
        'initial_price_usd' => 'decimal:8',
        'market_cap_target' => 'decimal:2',
        'max_supply' => 'decimal:8',
        'is_mintable' => 'boolean',
        'is_burnable' => 'boolean',
        'is_pausable' => 'boolean',
        'is_freezable' => 'boolean',
        'is_upgradeable' => 'boolean',
        'is_verified' => 'boolean',
        'dex_enabled' => 'boolean',
        'initial_liquidity_tpix' => 'decimal:8',
        'initial_liquidity_token' => 'decimal:8',
        'liquidity_lock_days' => 'decimal:2',
        'deploy_gas_used' => 'decimal:0',
        'deploy_cost_tpix' => 'decimal:8',
        'cmc_submitted' => 'boolean',
        'coingecko_submitted' => 'boolean',
        'deployed_at' => 'datetime',
        'trading_enabled_at' => 'datetime',
        'cmc_submitted_at' => 'datetime',
        'coingecko_submitted_at' => 'datetime',

        // JSON fields
        'prerequisites' => 'array',
        'social_links' => 'array',
        'supply_distribution' => 'array',
        'fee_structure' => 'array',
        'contract_features' => 'array',
        'safety_features' => 'array',
        'trading_params' => 'array',
        'marketing_materials' => 'array',
        'deployment_logs' => 'array',
        'error_logs' => 'array',
        'pricing_breakdown' => 'array',
        'premium_services' => 'array',

        // Payment decimals
        'payment_amount' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',

        // Payment booleans
        'is_early_adopter' => 'boolean',

        // Payment timestamps
        'payment_verified_at' => 'datetime',
        'refunded_at' => 'datetime',

        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot method
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // สร้าง slug อัตโนมัติเมื่อสร้าง
        static::creating(function ($config) {
            if (empty($config->slug)) {
                $config->slug = static::generateUniqueSlug($config->name);
            }
        });
    }

    // =====================================
    // Relationships
    // =====================================

    /**
     * ความสัมพันธ์กับ User (ผู้สร้าง)
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Liquidity Pool
     *
     * @return BelongsTo
     */
    public function liquidityPool(): BelongsTo
    {
        return $this->belongsTo(TPIXLiquidityPool::class, 'liquidity_pool_id');
    }

    // =====================================
    // Scopes
    // =====================================

    /**
     * Scope: Draft configurations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope: Deployed configurations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeployed($query)
    {
        return $query->whereIn('status', ['deployed', 'dex_ready', 'listed']);
    }

    /**
     * Scope: Listed configurations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeListed($query)
    {
        return $query->where('status', 'listed');
    }

    /**
     * Scope: Filter by step
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $step
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAtStep($query, int $step)
    {
        return $query->where('current_step', $step);
    }

    // =====================================
    // Methods
    // =====================================

    /**
     * สร้าง unique slug
     *
     * @param string $name
     * @return string
     */
    protected static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = Str::slug($name) . '-' . $count;
            $count++;
        }

        return $slug;
    }

    /**
     * ตรวจสอบว่าผ่าน step ที่ระบุหรือไม่
     *
     * @param int $step
     * @return bool
     */
    public function hasCompletedStep(int $step): bool
    {
        return $this->current_step > $step;
    }

    /**
     * ไปยัง step ถัดไป
     *
     * @return void
     */
    public function nextStep(): void
    {
        if ($this->current_step < 7) {
            $this->current_step++;
            $this->updateStatus();
            $this->save();
        }
    }

    /**
     * กลับไป step ก่อนหน้า
     *
     * @return void
     */
    public function previousStep(): void
    {
        if ($this->current_step > 1) {
            $this->current_step--;
            $this->save();
        }
    }

    /**
     * อัพเดทสถานะตาม step
     *
     * @return void
     */
    protected function updateStatus(): void
    {
        $statusMap = [
            1 => 'draft',
            2 => 'prerequisites_done',
            3 => 'config_done',
            4 => 'tokenomics_done',
            5 => 'contract_done',
            6 => 'deployed',
            7 => 'dex_ready',
        ];

        if (isset($statusMap[$this->current_step])) {
            $this->status = $statusMap[$this->current_step];
        }
    }

    /**
     * เพิ่ม log การ deploy
     *
     * @param string $action
     * @param array $data
     * @return void
     */
    public function addDeploymentLog(string $action, array $data = []): void
    {
        $logs = $this->deployment_logs ?? [];

        $logs[] = [
            'action' => $action,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
            'step' => $this->current_step,
        ];

        $this->deployment_logs = $logs;
        $this->save();
    }

    /**
     * เพิ่ม error log
     *
     * @param string $error
     * @param array $context
     * @return void
     */
    public function addErrorLog(string $error, array $context = []): void
    {
        $logs = $this->error_logs ?? [];

        $logs[] = [
            'error' => $error,
            'context' => $context,
            'timestamp' => now()->toIso8601String(),
            'step' => $this->current_step,
        ];

        $this->error_logs = $logs;
        $this->save();
    }

    /**
     * ตรวจสอบว่า prerequisites ผ่านหรือไม่
     *
     * @return bool
     */
    public function hasPassedPrerequisites(): bool
    {
        if (empty($this->prerequisites)) {
            return false;
        }

        $required = ['rpc_node', 'wallet', 'services', 'environment'];

        foreach ($required as $key) {
            if (!isset($this->prerequisites[$key]) || $this->prerequisites[$key] !== true) {
                return false;
            }
        }

        return true;
    }

    /**
     * ตรวจสอบว่าพร้อม deploy หรือไม่
     *
     * @return bool
     */
    public function isReadyToDeploy(): bool
    {
        return $this->current_step >= 5
            && !empty($this->token_name)
            && !empty($this->token_symbol)
            && !empty($this->total_supply)
            && $this->hasPassedPrerequisites();
    }

    /**
     * ตรวจสอบว่า deployed แล้วหรือไม่
     *
     * @return bool
     */
    public function isDeployed(): bool
    {
        return !empty($this->contract_address)
            && !empty($this->deploy_tx_hash)
            && $this->deployed_at !== null;
    }

    /**
     * ตรวจสอบว่าพร้อมสำหรับ DEX หรือไม่
     *
     * @return bool
     */
    public function isReadyForDex(): bool
    {
        return $this->isDeployed()
            && $this->current_step >= 6
            && $this->dex_enabled
            && !empty($this->initial_liquidity_tpix)
            && !empty($this->initial_liquidity_token);
    }

    /**
     * ดึง progress percentage
     *
     * @return float
     */
    public function getProgressPercentage(): float
    {
        return ($this->current_step / 7) * 100;
    }

    /**
     * ดึง step title
     *
     * @return string
     */
    public function getCurrentStepTitle(): string
    {
        $titles = [
            1 => 'ตรวจสอบความพร้อม',
            2 => 'ตั้งค่าเหรียญ',
            3 => 'กำหนดเศรษฐศาสตร์',
            4 => 'ปรับแต่ง Smart Contract',
            5 => 'Deploy และยืนยัน',
            6 => 'เชื่อมต่อ DEX',
            7 => 'List และการตลาด',
        ];

        return $titles[$this->current_step] ?? 'ไม่ทราบ';
    }

    /**
     * ดึง status badge color
     *
     * @return string
     */
    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'prerequisites_done', 'config_done', 'tokenomics_done', 'contract_done' => 'blue',
            'deployed' => 'green',
            'dex_ready' => 'purple',
            'listed' => 'yellow',
            'failed' => 'red',
            default => 'gray',
        };
    }

    /**
     * ดึง status label
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'ฉบับร่าง',
            'prerequisites_done' => 'ผ่านการตรวจสอบ',
            'config_done' => 'ตั้งค่าเสร็จ',
            'tokenomics_done' => 'กำหนดเศรษฐศาสตร์เสร็จ',
            'contract_done' => 'ปรับแต่ง Contract เสร็จ',
            'deployed' => 'Deploy แล้ว',
            'dex_ready' => 'พร้อมใช้ DEX',
            'listed' => 'List แล้ว',
            'failed' => 'ล้มเหลว',
            default => 'ไม่ทราบ',
        };
    }
}
