<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MlmNetwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sponsor_id',
        'parent_id',
        'level',
        'position',
        'path',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
}

class MlmRank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'badge_image',
        'level',
        'required_sales',
        'required_team_sales',
        'required_direct_referrals',
        'bonus_percentage',
        'benefits',
        'is_active',
    ];

    protected $casts = [
        'required_sales' => 'decimal:2',
        'required_team_sales' => 'decimal:2',
        'bonus_percentage' => 'decimal:2',
        'benefits' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function userRanks()
    {
        return $this->hasMany(UserRank::class, 'rank_id');
    }
}

class UserRank extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rank_id',
        'achieved_at',
        'is_current',
        'sales_at_achievement',
        'team_sales_at_achievement',
    ];

    protected $casts = [
        'achieved_at' => 'datetime',
        'is_current' => 'boolean',
        'sales_at_achievement' => 'decimal:2',
        'team_sales_at_achievement' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rank()
    {
        return $this->belongsTo(MlmRank::class, 'rank_id');
    }
}

class MlmGenealogy extends Model
{
    use HasFactory;

    protected $fillable = [
        'ancestor_id',
        'descendant_id',
        'depth',
    ];

    // Relationships
    public function ancestor()
    {
        return $this->belongsTo(User::class, 'ancestor_id');
    }

    public function descendant()
    {
        return $this->belongsTo(User::class, 'descendant_id');
    }
}
