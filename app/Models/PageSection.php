<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'page',
        'component_type',
        'name',
        'order',
        'is_active',
        'content',
        'styles',
        'settings',
    ];

    protected $casts = [
        'content' => 'array',
        'styles' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the template this section belongs to.
     */
    public function template()
    {
        return $this->belongsTo(PageTemplate::class, 'template_id');
    }

    /**
     * Scope for active sections
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific page
     */
    public function scopeForPage($query, $page)
    {
        return $query->where('page', $page);
    }

    /**
     * Scope for specific template
     */
    public function scopeForTemplate($query, $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    /**
     * Scope ordered by order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Get sections for a specific page
     */
    public static function getPageSections($page = 'home', $activeOnly = true)
    {
        $query = static::forPage($page)->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get sections for a specific template
     */
    public static function getTemplateSections($templateId, $activeOnly = true)
    {
        $query = static::forTemplate($templateId)->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Duplicate a section
     */
    public function duplicate()
    {
        $newSection = $this->replicate();
        $newSection->name = $this->name . ' (Copy)';
        $newSection->order = static::forPage($this->page)->max('order') + 1;
        $newSection->save();

        return $newSection;
    }

    /**
     * Reorder sections
     */
    public static function reorder($page, array $sectionIds)
    {
        foreach ($sectionIds as $index => $id) {
            static::where('id', $id)
                ->where('page', $page)
                ->update(['order' => $index]);
        }
    }
}
