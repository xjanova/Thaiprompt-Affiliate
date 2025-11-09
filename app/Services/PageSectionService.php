<?php

namespace App\Services;

use App\Models\PageBuilder;
use App\Models\PageBuilderSection;
use App\Models\PageBuilderTemplate;
use Illuminate\Support\Facades\DB;

class PageSectionService
{
    protected PageBuilderService $pageBuilderService;

    public function __construct(PageBuilderService $pageBuilderService)
    {
        $this->pageBuilderService = $pageBuilderService;
    }

    /**
     * Create a new section
     */
    public function createSection(PageBuilder $page, array $data): PageBuilderSection
    {
        // Get the next order number
        $maxOrder = $page->sections()->max('order') ?? -1;
        $data['order'] = $data['order'] ?? ($maxOrder + 1);
        $data['page_builder_id'] = $page->id;

        $section = PageBuilderSection::create($data);

        $this->pageBuilderService->clearCache($page);

        return $section;
    }

    /**
     * Create section from template
     */
    public function createFromTemplate(PageBuilder $page, PageBuilderTemplate $template, ?int $order = null): PageBuilderSection
    {
        $templateData = $template->getTemplateData();

        return $this->createSection($page, [
            'section_type' => $templateData['section_type'],
            'name' => $template->name,
            'settings' => $templateData['settings'],
            'content' => $templateData['content'],
            'order' => $order,
        ]);
    }

    /**
     * Update section
     */
    public function updateSection(PageBuilderSection $section, array $data): PageBuilderSection
    {
        $section->update($data);

        $this->pageBuilderService->clearCache($section->pageBuilder);

        return $section->fresh();
    }

    /**
     * Delete section
     */
    public function deleteSection(PageBuilderSection $section): bool
    {
        $page = $section->pageBuilder;

        DB::transaction(function () use ($section, $page) {
            $section->delete();

            // Reorder remaining sections
            $page->sections()->where('order', '>', $section->order)
                ->decrement('order');
        });

        $this->pageBuilderService->clearCache($page);

        return true;
    }

    /**
     * Duplicate section
     */
    public function duplicateSection(PageBuilderSection $section): PageBuilderSection
    {
        $page = $section->pageBuilder;
        $maxOrder = $page->sections()->max('order') ?? 0;

        $newSection = $section->replicate();
        $newSection->order = $maxOrder + 1;
        $newSection->name = ($section->name ?? 'Section') . ' (Copy)';
        $newSection->save();

        $this->pageBuilderService->clearCache($page);

        return $newSection;
    }

    /**
     * Move section up
     */
    public function moveUp(PageBuilderSection $section): bool
    {
        if ($section->order <= 0) {
            return false;
        }

        return DB::transaction(function () use ($section) {
            $page = $section->pageBuilder;
            $previousSection = $page->sections()
                ->where('order', $section->order - 1)
                ->first();

            if ($previousSection) {
                $previousSection->update(['order' => $section->order]);
                $section->update(['order' => $section->order - 1]);

                $this->pageBuilderService->clearCache($page);
                return true;
            }

            return false;
        });
    }

    /**
     * Move section down
     */
    public function moveDown(PageBuilderSection $section): bool
    {
        return DB::transaction(function () use ($section) {
            $page = $section->pageBuilder;
            $nextSection = $page->sections()
                ->where('order', $section->order + 1)
                ->first();

            if ($nextSection) {
                $nextSection->update(['order' => $section->order]);
                $section->update(['order' => $section->order + 1]);

                $this->pageBuilderService->clearCache($page);
                return true;
            }

            return false;
        });
    }

    /**
     * Toggle section visibility
     */
    public function toggleVisibility(PageBuilderSection $section): PageBuilderSection
    {
        $section->update(['is_visible' => !$section->is_visible]);

        $this->pageBuilderService->clearCache($section->pageBuilder);

        return $section;
    }

    /**
     * Toggle section active status
     */
    public function toggleActive(PageBuilderSection $section): PageBuilderSection
    {
        $section->update(['is_active' => !$section->is_active]);

        $this->pageBuilderService->clearCache($section->pageBuilder);

        return $section;
    }

    /**
     * Bulk update section settings
     */
    public function bulkUpdateSettings(array $sectionIds, array $settings): int
    {
        $updated = PageBuilderSection::whereIn('id', $sectionIds)
            ->get()
            ->each(function ($section) use ($settings) {
                $currentSettings = $section->settings ?? [];
                $section->update([
                    'settings' => array_merge($currentSettings, $settings)
                ]);
            })
            ->count();

        // Clear cache for all affected pages
        PageBuilderSection::whereIn('id', $sectionIds)
            ->with('pageBuilder')
            ->get()
            ->pluck('pageBuilder')
            ->unique('id')
            ->each(fn($page) => $this->pageBuilderService->clearCache($page));

        return $updated;
    }
}
