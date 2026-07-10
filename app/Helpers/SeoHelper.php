<?php

use App\Services\SeoService;

if (! function_exists('seo_meta')) {
    /**
     * Get SEO meta tags for a page
     */
    function seo_meta(string $pageType, array $customData = []): array
    {
        $seoService = app(SeoService::class);

        return $seoService->getMetaTags($pageType, $customData);
    }
}

if (! function_exists('render_seo_meta')) {
    /**
     * Render SEO meta tags HTML
     */
    function render_seo_meta(string $pageType, array $customData = []): string
    {
        $seoService = app(SeoService::class);

        return $seoService->renderMetaTags($pageType, $customData);
    }
}

if (! function_exists('render_global_structured_data')) {
    /**
     * เรนเดอร์ structured data ระดับเว็บ (Organization + WebSite) เป็น JSON-LD
     *
     * ใช้ใน <head> ของ layout สาธารณะทุกตัว
     */
    function render_global_structured_data(): string
    {
        return app(SeoService::class)->renderGlobalStructuredData();
    }
}

if (! function_exists('structured_data')) {
    /**
     * Generate structured data JSON-LD
     */
    function structured_data(array $data): string
    {
        return sprintf(
            '<script type="application/ld+json">%s</script>',
            // JSON_HEX_TAG|JSON_HEX_AMP กัน XSS: ไม่ให้ค่าที่มี </script> หลุดออกจาก script tag
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP)
        );
    }
}
