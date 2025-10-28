<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MlmRank extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mlm_ranks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'level',
        'required_sales',
        'required_recruits',
        'required_active_recruits',
        'bonus_percentage',
        'bonus_amount',
        'benefits',
        'color',
        'icon',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level' => 'integer',
        'required_sales' => 'decimal:2',
        'required_recruits' => 'integer',
        'required_active_recruits' => 'integer',
        'bonus_percentage' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'benefits' => 'array',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get users with this rank.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userRanks()
    {
        return $this->hasMany(UserRank::class, 'rank_id');
    }

    /**
     * Get users who currently have this rank.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_ranks')
            ->withPivot(['achieved_at', 'is_current'])
            ->withTimestamps();
    }

    /**
     * Get active ranks ordered by level.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveRanks()
    {
        return static::where('is_active', true)
            ->orderBy('level')
            ->get();
    }

    /**
     * Get rank by slug.
     *
     * @param string $slug
     * @return static|null
     */
    public static function findBySlug(string $slug)
    {
        return static::where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get next rank.
     *
     * @return static|null
     */
    public function getNextRank()
    {
        return static::where('level', '>', $this->level)
            ->where('is_active', true)
            ->orderBy('level')
            ->first();
    }

    /**
     * Get previous rank.
     *
     * @return static|null
     */
    public function getPreviousRank()
    {
        return static::where('level', '<', $this->level)
            ->where('is_active', true)
            ->orderBy('level', 'desc')
            ->first();
    }
}
