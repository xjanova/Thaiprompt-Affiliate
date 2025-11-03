<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticlePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'permission_type',
        'permission_value',
    ];

    /**
     * Get the article
     */
    public function article()
    {
        return $this->belongsTo(LearningArticle::class, 'article_id');
    }

    /**
     * Scope: by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('permission_type', $type);
    }

    /**
     * Scope: role permissions
     */
    public function scopeRolePermissions($query)
    {
        return $query->where('permission_type', 'role');
    }

    /**
     * Scope: user permissions
     */
    public function scopeUserPermissions($query)
    {
        return $query->where('permission_type', 'user');
    }
}
