<?php

use App\Services\SeoService;

if (!function_exists('seo_meta')) {
    /**
     * Get SEO meta tags for a page
     */
    function seo_meta(string $pageType, array $customData = []): array
    {
        $seoService = app(SeoService::class);
        return $seoService->getMetaTags($pageType, $customData);
    }
}

if (!function_exists('render_seo_meta')) {
    /**
     * Render SEO meta tags HTML
     */
    function render_seo_meta(string $pageType, array $customData = []): string
    {
        $seoService = app(SeoService::class);
        return $seoService->renderMetaTags($pageType, $customData);
    }
}

if (!function_exists('structured_data')) {
    /**
     * Generate structured data JSON-LD
     */
    function structured_data(array $data): string
    {
        return sprintf(
            '<script type="application/ld+json">%s</script>',
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
}
