<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CommissionSetting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'commission_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'level',
        'percentage',
        'name',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level' => 'integer',
        'percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get active commission settings ordered by level.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveSettings()
    {
        return static::where('is_active', true)
            ->orderBy('level')
            ->get();
    }

    /**
     * Get commission percentage for a specific level.
     *
     * @param int $level
     * @return float
     */
    public static function getPercentageForLevel(int $level): float
    {
        $setting = static::where('level', $level)
            ->where('is_active', true)
            ->first();

        return $setting ? (float) $setting->percentage : 0.0;
    }
}
