<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MlmBinaryPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'mlm_member_id',
        'parent_id',
        'left_child_id',
        'right_child_id',
        'position',
        'depth',
        'binary_code',
        'is_spillover',
        'spillover_from_id',
    ];

    protected function casts(): array
    {
        return [
            'is_spillover' => 'boolean',
        ];
    }

    /**
     * Get the member
     */
    public function member()
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    /**
     * Get the parent
     */
    public function parent()
    {
        return $this->belongsTo(MlmMember::class, 'parent_id');
    }

    /**
     * Get left child
     */
    public function leftChild()
    {
        return $this->belongsTo(MlmMember::class, 'left_child_id');
    }

    /**
     * Get right child
     */
    public function rightChild()
    {
        return $this->belongsTo(MlmMember::class, 'right_child_id');
    }

    /**
     * Get spillover source
     */
    public function spilloverFrom()
    {
        return $this->belongsTo(MlmMember::class, 'spillover_from_id');
    }
}
