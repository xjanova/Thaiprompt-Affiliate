<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'thumbnail',
        'is_default',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name
        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });

        // Ensure only one default template
        static::saving(function ($template) {
            if ($template->is_default) {
                static::where('id', '!=', $template->id)->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get the sections for this template.
     */
    public function sections()
    {
        return $this->hasMany(PageSection::class, 'template_id')->ordered();
    }

    /**
     * Get the creator of this template.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default template.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the default template.
     */
    public static function getDefault()
    {
        return static::default()->active()->first();
    }

    /**
     * Duplicate this template.
     */
    public function duplicate($newName = null)
    {
        $newTemplate = $this->replicate();
        $newTemplate->name = $newName ?? ($this->name . ' (Copy)');
        $newTemplate->slug = Str::slug($newTemplate->name);
        $newTemplate->is_default = false;
        $newTemplate->created_by = auth()->id();
        $newTemplate->save();

        // Duplicate all sections
        foreach ($this->sections as $section) {
            $newSection = $section->replicate();
            $newSection->template_id = $newTemplate->id;
            $newSection->save();
        }

        return $newTemplate;
    }

    /**
     * Get section count.
     */
    public function getSectionCountAttribute()
    {
        return $this->sections()->count();
    }

    /**
     * Check if this template is in use.
     */
    public function isInUse()
    {
        return \App\Models\Setting::get('home_template_id') == $this->id;
    }
}
