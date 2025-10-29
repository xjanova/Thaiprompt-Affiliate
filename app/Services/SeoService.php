<?php

namespace App\Services;

use App\Models\SeoMeta;
use App\Models\Setting;
use Illuminate\Support\Facades\URL;

class SeoService
{
    /**
     * Get SEO meta tags for a page
     */
    public function getMetaTags(string $pageType, array $customData = []): array
    {
        $seoMeta = SeoMeta::getByPage($pageType, app()->getLocale());

        if (!$seoMeta) {
            return $this->getDefaultMetaTags($customData);
        }

        $siteName = Setting::get('site_name', config('app.name'));
        $siteUrl = config('app.url');
        $currentUrl = URL::current();

        return [
            'title' => $customData['title'] ?? $seoMeta->meta_title ?? $siteName,
            'description' => $customData['description'] ?? $seoMeta->meta_description ?? '',
            'keywords' => $seoMeta->meta_keywords ?? '',
            'canonical' => $seoMeta->canonical_url ?? $currentUrl,
            'robots' => $seoMeta->robots,
            'og' => [
                'title' => $seoMeta->og_title ?? $seoMeta->meta_title ?? $siteName,
                'description' => $seoMeta->og_description ?? $seoMeta->meta_description ?? '',
                'image' => $this->getFullUrl($seoMeta->og_image ?? Setting::get('og_default_image')),
                'type' => $seoMeta->og_type ?? 'website',
                'url' => $currentUrl,
                'site_name' => $siteName,
            ],
            'twitter' => [
                'card' => $seoMeta->twitter_card ?? 'summary_large_image',
                'title' => $seoMeta->twitter_title ?? $seoMeta->meta_title ?? $siteName,
                'description' => $seoMeta->twitter_description ?? $seoMeta->meta_description ?? '',
                'image' => $this->getFullUrl($seoMeta->twitter_image ?? Setting::get('twitter_default_image')),
            ],
            'structured_data' => $seoMeta->structured_data ?? null,
        ];
    }

    /**
     * Get default meta tags
     */
    private function getDefaultMetaTags(array $customData = []): array
    {
        $siteName = Setting::get('site_name', config('app.name'));
        $currentUrl = URL::current();

        return [
            'title' => $customData['title'] ?? $siteName,
            'description' => $customData['description'] ?? '',
            'keywords' => '',
            'canonical' => $currentUrl,
            'robots' => 'index, follow',
            'og' => [
                'title' => $customData['title'] ?? $siteName,
                'description' => $customData['description'] ?? '',
                'image' => $this->getFullUrl(Setting::get('og_default_image')),
                'type' => 'website',
                'url' => $currentUrl,
                'site_name' => $siteName,
            ],
            'twitter' => [
                'card' => 'summary_large_image',
                'title' => $customData['title'] ?? $siteName,
                'description' => $customData['description'] ?? '',
                'image' => $this->getFullUrl(Setting::get('twitter_default_image')),
            ],
            'structured_data' => null,
        ];
    }

    /**
     * Generate structured data for organization
     */
    public function generateOrganizationStructuredData(): array
    {
        $siteName = Setting::get('site_name', config('app.name'));
        $siteUrl = config('app.url');
        $logo = Setting::get('logo');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => $siteUrl,
            'logo' => $logo ? $this->getFullUrl($logo) : null,
            'description' => Setting::get('site_description', 'ระบบ Affiliate Marketing MLM อันดับ 1 ของไทย'),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'email' => Setting::get('contact_email'),
            ],
        ];
    }

    /**
     * Generate breadcrumb structured data
     */
    public function generateBreadcrumbStructuredData(array $breadcrumbs): array
    {
        $itemListElements = [];

        foreach ($breadcrumbs as $index => $breadcrumb) {
            $itemListElements[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb['name'],
                'item' => $breadcrumb['url'] ?? null,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemListElements,
        ];
    }

    /**
     * Get full URL for image
     */
    private function getFullUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return url($path);
    }

    /**
     * Render meta tags HTML
     */
    public function renderMetaTags(string $pageType, array $customData = []): string
    {
        $meta = $this->getMetaTags($pageType, $customData);

        $html = [];

        // Basic meta tags
        $html[] = sprintf('<title>%s</title>', e($meta['title']));
        $html[] = sprintf('<meta name="description" content="%s">', e($meta['description']));

        if (!empty($meta['keywords'])) {
            $html[] = sprintf('<meta name="keywords" content="%s">', e($meta['keywords']));
        }

        $html[] = sprintf('<link rel="canonical" href="%s">', e($meta['canonical']));
        $html[] = sprintf('<meta name="robots" content="%s">', e($meta['robots']));

        // Open Graph tags
        $html[] = sprintf('<meta property="og:title" content="%s">', e($meta['og']['title']));
        $html[] = sprintf('<meta property="og:description" content="%s">', e($meta['og']['description']));
        $html[] = sprintf('<meta property="og:type" content="%s">', e($meta['og']['type']));
        $html[] = sprintf('<meta property="og:url" content="%s">', e($meta['og']['url']));
        $html[] = sprintf('<meta property="og:site_name" content="%s">', e($meta['og']['site_name']));

        if (!empty($meta['og']['image'])) {
            $html[] = sprintf('<meta property="og:image" content="%s">', e($meta['og']['image']));
        }

        // Twitter Card tags
        $html[] = sprintf('<meta name="twitter:card" content="%s">', e($meta['twitter']['card']));
        $html[] = sprintf('<meta name="twitter:title" content="%s">', e($meta['twitter']['title']));
        $html[] = sprintf('<meta name="twitter:description" content="%s">', e($meta['twitter']['description']));

        if (!empty($meta['twitter']['image'])) {
            $html[] = sprintf('<meta name="twitter:image" content="%s">', e($meta['twitter']['image']));
        }

        // Structured data
        if (!empty($meta['structured_data'])) {
            $html[] = sprintf(
                '<script type="application/ld+json">%s</script>',
                json_encode($meta['structured_data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        }

        return implode("\n    ", $html);
    }
}
