<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MlmGenealogy extends Model
{
    use HasFactory;

    protected $table = 'mlm_genealogy';

    protected $fillable = [
        'mlm_member_id',
        'ancestor_id',
        'mlm_plan_id',
        'tree_type',
        'depth',
        'path',
        'leg',
    ];

    /**
     * Get the member
     */
    public function member()
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    /**
     * Get the ancestor
     */
    public function ancestor()
    {
        return $this->belongsTo(MlmMember::class, 'ancestor_id');
    }

    /**
     * Get the plan
     */
    public function plan()
    {
        return $this->belongsTo(MlmPlan::class, 'mlm_plan_id');
    }
}
