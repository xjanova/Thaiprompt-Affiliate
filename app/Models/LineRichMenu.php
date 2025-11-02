<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LineRichMenu extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rich_menu_id',
        'name',
        'description',
        'size',
        'selected',
        'chat_bar_text',
        'areas',
        'image_path',
        'is_default',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'selected' => 'boolean',
        'areas' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get size dimensions
     */
    public function getSizeDimensions(): array
    {
        return match($this->size) {
            'full' => ['width' => 2500, 'height' => 1686],
            'half' => ['width' => 2500, 'height' => 843],
            default => ['width' => 2500, 'height' => 843],
        };
    }

    /**
     * Set as default
     */
    public function setAsDefault(): void
    {
        // Remove default from others
        static::where('is_default', true)->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Scope: Active menus
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Default menu
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
