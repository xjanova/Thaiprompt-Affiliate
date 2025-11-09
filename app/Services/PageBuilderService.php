<?php

namespace App\Services;

use App\Models\PageBuilder;
use App\Models\PageBuilderSection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PageBuilderService
{
    /**
     * Get page by type
     */
    public function getPageByType(string $type): ?PageBuilder
    {
        return Cache::remember(
            "page_builder.type.{$type}",
            now()->addHours(24),
            fn() => PageBuilder::active()->byType($type)->with('activeSections')->first()
        );
    }

    /**
     * Get page by slug
     */
    public function getPageBySlug(string $slug): ?PageBuilder
    {
        return Cache::remember(
            "page_builder.slug.{$slug}",
            now()->addHours(24),
            fn() => PageBuilder::active()->bySlug($slug)->with('activeSections')->first()
        );
    }

    /**
     * Create a new page
     */
    public function createPage(array $data): PageBuilder
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $page = PageBuilder::create($data);

        $this->clearCache($page);

        return $page->load('sections');
    }

    /**
     * Update page
     */
    public function updatePage(PageBuilder $page, array $data): PageBuilder
    {
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $page->update($data);

        $this->clearCache($page);

        return $page->fresh(['sections']);
    }

    /**
     * Delete page
     */
    public function deletePage(PageBuilder $page): bool
    {
        $this->clearCache($page);

        return $page->delete();
    }

    /**
     * Duplicate page
     */
    public function duplicatePage(PageBuilder $page, ?string $newName = null): PageBuilder
    {
        return DB::transaction(function () use ($page, $newName) {
            $newPage = $page->replicate();
            $newPage->name = $newName ?? ($page->name . ' (Copy)');
            $newPage->slug = Str::slug($newPage->name);
            $newPage->is_active = false;
            $newPage->save();

            // Duplicate all sections
            foreach ($page->sections as $section) {
                $newSection = $section->replicate();
                $newSection->page_builder_id = $newPage->id;
                $newSection->save();
            }

            return $newPage->load('sections');
        });
    }

    /**
     * Get rendered page data
     */
    public function getRenderData(PageBuilder $page): array
    {
        return Cache::remember(
            "page_builder.render.{$page->id}",
            now()->addHours(24),
            fn() => $page->getRenderData()
        );
    }

    /**
     * Clear cache for page
     */
    public function clearCache(PageBuilder $page): void
    {
        Cache::forget("page_builder.type.{$page->page_type}");
        Cache::forget("page_builder.slug.{$page->slug}");
        Cache::forget("page_builder.render.{$page->id}");
    }

    /**
     * Reorder all sections
     */
    public function reorderSections(PageBuilder $page, array $sectionIds): void
    {
        DB::transaction(function () use ($page, $sectionIds) {
            foreach ($sectionIds as $index => $sectionId) {
                PageBuilderSection::where('id', $sectionId)
                    ->where('page_builder_id', $page->id)
                    ->update(['order' => $index]);
            }
        });

        $this->clearCache($page);
    }

    /**
     * Toggle page active status
     */
    public function toggleActive(PageBuilder $page): PageBuilder
    {
        $page->update(['is_active' => !$page->is_active]);
        $this->clearCache($page);

        return $page;
    }
}
