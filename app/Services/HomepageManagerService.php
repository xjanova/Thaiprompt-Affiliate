<?php

namespace App\Services;

use App\Models\HomepageElement;
use App\Models\HomepageSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * HomepageManagerService
 *
 * Service class สำหรับจัดการ business logic ของ Homepage Manager
 * รองรับการ cache, export/import และ render หน้าแรก
 */
class HomepageManagerService
{
    /**
     * Cache key prefix
     *
     * @var string
     */
    protected const CACHE_PREFIX = 'homepage_manager';

    /**
     * Cache TTL in seconds (1 hour)
     *
     * @var int
     */
    protected const CACHE_TTL = 3600;

    /**
     * ดึงข้อมูลหน้าแรกพร้อม cache
     */
    public function getHomepage(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX.'.sections',
            self::CACHE_TTL,
            function () {
                return HomepageSection::with('activeElements')
                    ->active()
                    ->ordered()
                    ->get();
            }
        );
    }

    /**
     * ดึงข้อมูลหน้าแรกในรูปแบบ array สำหรับ render
     */
    public function getRenderData(): array
    {
        $sections = $this->getHomepage();

        return $sections->map(function ($section) {
            return $section->toApiArray();
        })->toArray();
    }

    /**
     * Export หน้าแรกเป็น JSON array
     */
    public function exportHomepage(): array
    {
        $sections = HomepageSection::with('elements')
            ->ordered()
            ->get();

        return [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'sections' => $sections->map(function ($section) {
                $sectionData = $section->toArray();
                unset($sectionData['id'], $sectionData['created_at'], $sectionData['updated_at'], $sectionData['deleted_at']);

                $sectionData['elements'] = $section->elements->map(function ($element) {
                    $elementData = $element->toArray();
                    unset($elementData['id'], $elementData['homepage_section_id'], $elementData['created_at'], $elementData['updated_at'], $elementData['deleted_at']);

                    return $elementData;
                })->toArray();

                return $sectionData;
            })->toArray(),
        ];
    }

    /**
     * Import หน้าแรกจาก JSON array
     *
     * @param  array  $data  ข้อมูลที่จะ import
     * @param  bool  $replace  ลบข้อมูลเดิมก่อน import หรือไม่
     * @return array ผลลัพธ์การ import
     */
    public function importHomepage(array $data, bool $replace = false): array
    {
        return DB::transaction(function () use ($data, $replace) {
            // ลบข้อมูลเดิมถ้าต้องการ
            if ($replace) {
                HomepageElement::query()->forceDelete();
                HomepageSection::query()->forceDelete();
            }

            $sectionsData = $data['sections'] ?? $data;
            $sectionsCount = 0;
            $elementsCount = 0;
            $startOrder = $replace ? 0 : (HomepageSection::max('order') + 1);

            foreach ($sectionsData as $index => $sectionData) {
                // ลบ keys ที่ไม่ต้องการ
                unset($sectionData['id'], $sectionData['created_at'], $sectionData['updated_at'], $sectionData['deleted_at']);

                // เก็บ elements ไว้ก่อน
                $elements = $sectionData['elements'] ?? [];
                unset($sectionData['elements']);

                // Set order
                $sectionData['order'] = $startOrder + $index;

                // สร้าง section
                $section = HomepageSection::create($sectionData);
                $sectionsCount++;

                // สร้าง elements
                foreach ($elements as $elemIndex => $elementData) {
                    unset($elementData['id'], $elementData['homepage_section_id'], $elementData['created_at'], $elementData['updated_at'], $elementData['deleted_at']);
                    $elementData['order'] = $elemIndex;

                    $section->elements()->create($elementData);
                    $elementsCount++;
                }
            }

            // Clear cache
            $this->clearCache();

            return [
                'sections_count' => $sectionsCount,
                'elements_count' => $elementsCount,
            ];
        });
    }

    /**
     * ล้าง cache ทั้งหมด
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX.'.sections');
        Cache::forget(self::CACHE_PREFIX.'.render_data');
    }

    /**
     * สร้าง HTML สำหรับ section
     */
    public function renderSection(HomepageSection $section, bool $isDarkMode = false): string
    {
        $style = $section->getStyleAttribute($isDarkMode);

        $html = sprintf(
            '<section id="section-%d" class="homepage-section %s" style="%s" data-section-id="%d">',
            $section->id,
            $section->animation ? 'animate-on-scroll' : '',
            $style,
            $section->id
        );

        // Container
        $containerClass = $section->is_fullwidth ? 'w-full' : 'container mx-auto';
        $html .= sprintf('<div class="%s relative">', $containerClass);

        // Render elements
        foreach ($section->activeElements as $element) {
            $html .= $this->renderElement($element, $isDarkMode);
        }

        $html .= '</div>';

        // Background video
        if ($section->background_type === 'video' && $section->background_video) {
            $html .= sprintf(
                '<video class="absolute inset-0 w-full h-full object-cover -z-10" autoplay loop muted playsinline>
                    <source src="%s" type="video/mp4">
                </video>',
                $section->background_video
            );
        }

        // Background overlay
        if ($section->background_overlay) {
            $html .= sprintf(
                '<div class="absolute inset-0 -z-5" style="background-color: %s;"></div>',
                $section->background_overlay
            );
        }

        $html .= '</section>';

        return $html;
    }

    /**
     * สร้าง HTML สำหรับ element
     */
    public function renderElement(HomepageElement $element, bool $isDarkMode = false): string
    {
        $style = $element->getStyleString($isDarkMode);
        $content = $element->getLocalizedContent();
        $classes = $this->getElementClasses($element);

        $wrapperTag = $element->link_url ? 'a' : 'div';
        $linkAttrs = $element->link_url
            ? sprintf('href="%s" target="%s"', $element->link_url, $element->link_target)
            : '';

        // Animation attributes
        $animationAttrs = '';
        if ($element->animation) {
            $animationAttrs = sprintf(
                'data-animation="%s" data-animation-delay="%d" data-animation-duration="%d"',
                $element->animation,
                $element->animation_delay,
                $element->animation_duration
            );
        }

        $html = sprintf(
            '<%s class="homepage-element %s" style="%s" data-element-id="%d" %s %s>',
            $wrapperTag,
            $classes,
            $style,
            $element->id,
            $linkAttrs,
            $animationAttrs
        );

        // Render content based on type
        $html .= $this->renderElementContent($element, $content, $isDarkMode);

        $html .= sprintf('</%s>', $wrapperTag);

        return $html;
    }

    /**
     * Render element content based on type
     */
    protected function renderElementContent(HomepageElement $element, string $content, bool $isDarkMode = false): string
    {
        $settings = $element->settings ?? [];

        switch ($element->type) {
            // === Basic Elements ===
            case 'heading':
                $tag = $settings['heading_level'] ?? 'h2';

                return sprintf('<%s>%s</%s>', $tag, $content, $tag);

            case 'text':
                return sprintf('<p>%s</p>', nl2br($content));

            case 'image':
                $imageUrl = $element->getImageUrl($isDarkMode);
                if ($imageUrl) {
                    $alt = $settings['alt'] ?? $element->name ?? '';

                    return sprintf(
                        '<img src="%s" alt="%s" class="max-w-full h-auto" loading="lazy">',
                        $imageUrl,
                        htmlspecialchars($alt)
                    );
                }

                return '';

            case 'button':
                $btnClass = $settings['button_style'] ?? 'primary';
                $icon = $settings['icon'] ?? '';
                $iconHtml = $icon ? sprintf('<i class="%s mr-2"></i>', $icon) : '';

                return sprintf(
                    '<span class="inline-flex items-center justify-center btn btn-%s">%s%s</span>',
                    $btnClass,
                    $iconHtml,
                    $content
                );

            case 'video':
            case 'youtube-embed':
                if ($element->video_url) {
                    if (preg_match('/youtube|youtu\.be/i', $element->video_url)) {
                        $videoId = $this->extractYouTubeId($element->video_url);

                        return sprintf(
                            '<iframe src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen class="w-full aspect-video rounded-lg"></iframe>',
                            $videoId
                        );
                    } elseif (preg_match('/vimeo/i', $element->video_url)) {
                        $videoId = $this->extractVimeoId($element->video_url);

                        return sprintf(
                            '<iframe src="https://player.vimeo.com/video/%s" frameborder="0" allowfullscreen class="w-full aspect-video rounded-lg"></iframe>',
                            $videoId
                        );
                    } else {
                        return sprintf(
                            '<video src="%s" controls class="w-full rounded-lg"></video>',
                            $element->video_url
                        );
                    }
                }

                return '';

            case 'icon':
                if ($element->icon_class) {
                    return sprintf('<i class="%s"></i>', $element->icon_class);
                }

                return '';

            case 'divider':
                $style = $settings['divider_style'] ?? 'solid';

                return sprintf('<hr class="border-t border-%s">', $style);

            case 'spacer':
                return '';

                // === Advanced Elements ===
            case 'container':
            case 'html':
                return $content;

            case 'card':
                $title = $settings['title'] ?? '';
                $subtitle = $settings['subtitle'] ?? '';
                $imageUrl = $element->getImageUrl($isDarkMode);

                return sprintf(
                    '<div class="card bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                        %s
                        <div class="p-4">
                            %s
                            %s
                            <div>%s</div>
                        </div>
                    </div>',
                    $imageUrl ? sprintf('<img src="%s" class="w-full h-48 object-cover">', $imageUrl) : '',
                    $title ? sprintf('<h3 class="font-bold text-lg mb-1">%s</h3>', $title) : '',
                    $subtitle ? sprintf('<p class="text-gray-500 text-sm mb-2">%s</p>', $subtitle) : '',
                    $content
                );

            case 'grid':
                $cols = $settings['columns'] ?? 3;
                $gap = $settings['gap'] ?? '4';

                return sprintf(
                    '<div class="grid grid-cols-1 md:grid-cols-%d gap-%s">%s</div>',
                    $cols,
                    $gap,
                    $content
                );

            case 'list':
                $listType = $settings['list_type'] ?? 'ul';
                $items = $settings['items'] ?? [];
                $itemsHtml = implode('', array_map(fn ($item) => sprintf('<li class="mb-2">%s</li>', $item), $items));

                return sprintf('<%s class="list-disc list-inside">%s</%s>', $listType, $itemsHtml ?: $content, $listType);

            case 'map':
                $lat = $settings['lat'] ?? '13.7563';
                $lng = $settings['lng'] ?? '100.5018';
                $zoom = $settings['zoom'] ?? 15;

                return sprintf(
                    '<iframe src="https://maps.google.com/maps?q=%s,%s&z=%d&output=embed" class="w-full h-64 rounded-lg border-0" loading="lazy"></iframe>',
                    $lat, $lng, $zoom
                );

            case 'social':
                $platforms = $settings['platforms'] ?? ['facebook', 'twitter', 'instagram'];
                $icons = implode('', array_map(fn ($p) => sprintf('<a href="#" class="mx-2 text-2xl hover:text-primary"><i class="fab fa-%s"></i></a>', $p), $platforms));

                return sprintf('<div class="flex items-center justify-center">%s</div>', $icons);

            case 'logo':
                $imageUrl = $element->getImageUrl($isDarkMode);

                return $imageUrl ? sprintf('<img src="%s" alt="Logo" class="max-h-16">', $imageUrl) : '';

                // === Interactive Elements ===
            case 'counter':
                $number = $settings['number'] ?? 0;
                $suffix = $settings['suffix'] ?? '';
                $prefix = $settings['prefix'] ?? '';

                return sprintf(
                    '<div class="counter text-center" x-data="{ count: 0 }" x-init="$nextTick(() => { let target = %d; let step = target / 50; let interval = setInterval(() => { count += step; if (count >= target) { count = target; clearInterval(interval); } }, 30); })">
                        <span class="text-4xl font-bold">%s<span x-text="Math.floor(count)">0</span>%s</span>
                    </div>',
                    $number, $prefix, $suffix
                );

            case 'timer':
                $targetDate = $settings['target_date'] ?? date('Y-m-d H:i:s', strtotime('+7 days'));

                return sprintf(
                    '<div class="countdown flex justify-center gap-4" x-data="countdown(\'%s\')">
                        <div class="text-center"><span class="text-3xl font-bold" x-text="days">00</span><span class="block text-xs">วัน</span></div>
                        <div class="text-center"><span class="text-3xl font-bold" x-text="hours">00</span><span class="block text-xs">ชม.</span></div>
                        <div class="text-center"><span class="text-3xl font-bold" x-text="minutes">00</span><span class="block text-xs">นาที</span></div>
                        <div class="text-center"><span class="text-3xl font-bold" x-text="seconds">00</span><span class="block text-xs">วินาที</span></div>
                    </div>',
                    $targetDate
                );

            case 'form':
                $formType = $settings['form_type'] ?? 'contact';

                return sprintf(
                    '<form class="space-y-4" x-data="{ submitting: false }">
                        <input type="text" placeholder="ชื่อ" class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600" required>
                        <input type="email" placeholder="อีเมล" class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600" required>
                        <textarea placeholder="ข้อความ" rows="4" class="w-full px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600"></textarea>
                        <button type="submit" class="w-full py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition">ส่งข้อความ</button>
                    </form>'
                );

            case 'tabs':
                $tabs = $settings['tabs'] ?? [['title' => 'Tab 1', 'content' => 'เนื้อหา Tab 1']];
                $tabsHtml = '';
                $panelsHtml = '';
                foreach ($tabs as $i => $tab) {
                    $active = $i === 0 ? 'true' : 'false';
                    $tabsHtml .= sprintf('<button @click="activeTab = %d" :class="activeTab === %d && \'border-primary text-primary\'" class="px-4 py-2 border-b-2">%s</button>', $i, $i, $tab['title']);
                    $panelsHtml .= sprintf('<div x-show="activeTab === %d" class="p-4">%s</div>', $i, $tab['content']);
                }

                return sprintf('<div x-data="{ activeTab: 0 }"><div class="flex border-b">%s</div>%s</div>', $tabsHtml, $panelsHtml);

            case 'accordion':
                $items = $settings['items'] ?? [['title' => 'หัวข้อ 1', 'content' => 'เนื้อหา 1']];
                $itemsHtml = '';
                foreach ($items as $i => $item) {
                    $itemsHtml .= sprintf(
                        '<div class="border-b dark:border-gray-700">
                            <button @click="open = open === %d ? null : %d" class="w-full py-3 flex justify-between items-center">
                                <span>%s</span>
                                <i class="fas fa-chevron-down transition-transform" :class="open === %d && \'rotate-180\'"></i>
                            </button>
                            <div x-show="open === %d" x-collapse class="pb-3 text-gray-600 dark:text-gray-400">%s</div>
                        </div>',
                        $i, $i, $item['title'], $i, $i, $item['content']
                    );
                }

                return sprintf('<div x-data="{ open: null }" class="divide-y dark:divide-gray-700">%s</div>', $itemsHtml);

            case 'carousel':
                $images = $settings['images'] ?? [];
                $slidesHtml = implode('', array_map(fn ($img) => sprintf('<div class="flex-shrink-0 w-full"><img src="%s" class="w-full rounded-lg"></div>', $img), $images));

                return sprintf(
                    '<div x-data="{ current: 0, images: %s }" class="relative overflow-hidden">
                        <div class="flex transition-transform" :style="\'transform: translateX(-\' + (current * 100) + \'%%)\'">%s</div>
                        <button @click="current = current > 0 ? current - 1 : images.length - 1" class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full"><i class="fas fa-chevron-left"></i></button>
                        <button @click="current = current < images.length - 1 ? current + 1 : 0" class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full"><i class="fas fa-chevron-right"></i></button>
                    </div>',
                    json_encode($images), $slidesHtml
                );

            case 'progress':
                $percent = $settings['percent'] ?? 75;
                $label = $settings['label'] ?? '';

                return sprintf(
                    '<div class="w-full">
                        %s
                        <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-primary rounded-full transition-all" style="width: %d%%"></div>
                        </div>
                    </div>',
                    $label ? sprintf('<div class="flex justify-between mb-1"><span>%s</span><span>%d%%</span></div>', $label, $percent) : '',
                    $percent
                );

            case 'rating':
                $value = $settings['value'] ?? 5;
                $max = $settings['max'] ?? 5;
                $starsHtml = '';
                for ($i = 1; $i <= $max; $i++) {
                    $starsHtml .= sprintf('<i class="fas fa-star %s"></i>', $i <= $value ? 'text-yellow-400' : 'text-gray-300');
                }

                return sprintf('<div class="flex gap-1">%s</div>', $starsHtml);

            case 'modal':
            case 'tooltip':
                return sprintf('<div class="inline-block">%s</div>', $content);

                // === Media Elements ===
            case 'audio':
                $audioUrl = $settings['audio_url'] ?? $element->video_url ?? '';

                return $audioUrl ? sprintf('<audio src="%s" controls class="w-full"></audio>', $audioUrl) : '';

            case 'lottie':
                $lottieUrl = $settings['lottie_url'] ?? '';

                return $lottieUrl ? sprintf('<lottie-player src="%s" background="transparent" speed="1" loop autoplay class="w-full max-w-md mx-auto"></lottie-player>', $lottieUrl) : '';

            case 'before-after':
                $before = $settings['before_image'] ?? '';
                $after = $settings['after_image'] ?? '';

                return sprintf(
                    '<div x-data="{ position: 50 }" class="relative overflow-hidden rounded-lg">
                        <img src="%s" class="w-full">
                        <div class="absolute inset-0 overflow-hidden" :style="\'width: \' + position + \'%%\'"><img src="%s" class="w-full"></div>
                        <input type="range" min="0" max="100" x-model="position" class="absolute inset-x-0 bottom-4 w-3/4 mx-auto">
                    </div>',
                    $after, $before
                );

            case 'gallery-masonry':
            case 'lightbox':
                $images = $settings['images'] ?? [];
                $imagesHtml = implode('', array_map(fn ($img) => sprintf('<div class="break-inside-avoid mb-4"><img src="%s" class="w-full rounded-lg" loading="lazy"></div>', $img), $images));

                return sprintf('<div class="columns-2 md:columns-3 gap-4">%s</div>', $imagesHtml);

            case 'video-bg':
                $videoUrl = $element->video_url ?? '';

                return $videoUrl ? sprintf(
                    '<div class="relative overflow-hidden">
                        <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover"><source src="%s" type="video/mp4"></video>
                        <div class="relative z-10 p-8">%s</div>
                    </div>',
                    $videoUrl, $content
                ) : '';

            case 'spotify-embed':
                $spotifyUrl = $settings['spotify_url'] ?? '';
                if ($spotifyUrl && preg_match('/spotify\.com\/(track|playlist|album)\/([a-zA-Z0-9]+)/', $spotifyUrl, $matches)) {
                    return sprintf('<iframe src="https://open.spotify.com/embed/%s/%s" width="100%%" height="152" frameborder="0" allowtransparency="true" allow="encrypted-media"></iframe>', $matches[1], $matches[2]);
                }

                return '';

                // === E-commerce Elements ===
            case 'product-card':
                $title = $settings['title'] ?? 'สินค้า';
                $price = $settings['price'] ?? '฿0';
                $oldPrice = $settings['old_price'] ?? '';
                $imageUrl = $element->getImageUrl($isDarkMode);

                return sprintf(
                    '<div class="product-card bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden group">
                        <div class="relative overflow-hidden">
                            %s
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all"></div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold mb-2">%s</h3>
                            <div class="flex items-center gap-2">
                                <span class="text-xl font-bold text-primary">%s</span>
                                %s
                            </div>
                        </div>
                    </div>',
                    $imageUrl ? sprintf('<img src="%s" class="w-full h-48 object-cover group-hover:scale-105 transition-transform">', $imageUrl) : '',
                    $title,
                    $price,
                    $oldPrice ? sprintf('<span class="text-sm text-gray-400 line-through">%s</span>', $oldPrice) : ''
                );

            case 'price-tag':
                $price = $settings['price'] ?? '฿0';
                $label = $settings['label'] ?? '';

                return sprintf(
                    '<div class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-full">
                        %s<span class="text-xl font-bold">%s</span>
                    </div>',
                    $label ? sprintf('<span class="text-sm">%s</span>', $label) : '',
                    $price
                );

            case 'add-to-cart':
                return sprintf(
                    '<button class="flex items-center gap-2 bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg transition">
                        <i class="fas fa-cart-plus"></i>
                        <span>%s</span>
                    </button>',
                    $content ?: 'เพิ่มลงตะกร้า'
                );

            case 'sale-badge':
                $discount = $settings['discount'] ?? '20%';

                return sprintf('<span class="inline-block bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">ลด %s</span>', $discount);

            case 'stock-status':
                $inStock = $settings['in_stock'] ?? true;
                $quantity = $settings['quantity'] ?? 0;

                return $inStock
                    ? sprintf('<span class="inline-flex items-center gap-1 text-green-500"><i class="fas fa-check-circle"></i> มีสินค้า%s</span>', $quantity > 0 ? " ($quantity ชิ้น)" : '')
                    : '<span class="inline-flex items-center gap-1 text-red-500"><i class="fas fa-times-circle"></i> สินค้าหมด</span>';

            case 'wishlist-btn':
                return '<button class="p-2 rounded-full border hover:bg-red-50 hover:border-red-500 hover:text-red-500 transition" x-data="{ liked: false }" @click="liked = !liked"><i class="far fa-heart" :class="liked && \'fas text-red-500\'"></i></button>';

            case 'quick-view':
                return '<button class="flex items-center gap-2 text-primary hover:underline"><i class="fas fa-eye"></i> ดูด่วน</button>';

            case 'product-slider':
                $products = $settings['products'] ?? [];

                return sprintf('<div class="flex gap-4 overflow-x-auto snap-x">%s</div>',
                    implode('', array_map(fn ($p) => sprintf('<div class="flex-shrink-0 w-64 snap-start">%s</div>', $this->renderProductCard($p)), $products))
                );

                // === Marketing Elements ===
            case 'announcement-bar':
                $bgColor = $settings['bg_color'] ?? 'bg-primary';

                return sprintf(
                    '<div class="%s text-white py-2 text-center text-sm"><marquee>%s</marquee></div>',
                    $bgColor, $content
                );

            case 'banner-promo':
                $imageUrl = $element->getImageUrl($isDarkMode);

                return sprintf(
                    '<div class="relative rounded-xl overflow-hidden">
                        %s
                        <div class="absolute inset-0 bg-gradient-to-r from-black/50 to-transparent flex items-center p-8">
                            <div class="text-white">%s</div>
                        </div>
                    </div>',
                    $imageUrl ? sprintf('<img src="%s" class="w-full">', $imageUrl) : '<div class="h-48 bg-gradient-to-r from-primary to-secondary"></div>',
                    $content
                );

            case 'floating-cta':
                $icon = $settings['icon'] ?? 'fas fa-rocket';

                return sprintf(
                    '<div class="fixed bottom-8 right-8 z-50">
                        <button class="w-14 h-14 rounded-full bg-primary text-white shadow-lg hover:scale-110 transition flex items-center justify-center">
                            <i class="%s text-xl"></i>
                        </button>
                    </div>',
                    $icon
                );

            case 'newsletter':
                return sprintf(
                    '<div class="bg-gray-100 dark:bg-gray-800 rounded-xl p-6">
                        <h3 class="text-xl font-bold mb-2">%s</h3>
                        <form class="flex gap-2">
                            <input type="email" placeholder="อีเมลของคุณ" class="flex-1 px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600">
                            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition">สมัคร</button>
                        </form>
                    </div>',
                    $content ?: 'สมัครรับข่าวสาร'
                );

            case 'trust-badges':
                $badges = $settings['badges'] ?? ['secure', 'guarantee', 'support'];
                $badgesHtml = implode('', array_map(function ($b) {
                    $icons = ['secure' => 'fa-shield-alt', 'guarantee' => 'fa-certificate', 'support' => 'fa-headset', 'shipping' => 'fa-truck'];
                    $labels = ['secure' => 'ปลอดภัย 100%', 'guarantee' => 'รับประกันคืนเงิน', 'support' => 'ซัพพอร์ต 24/7', 'shipping' => 'จัดส่งฟรี'];

                    return sprintf('<div class="flex flex-col items-center"><i class="fas %s text-2xl text-primary mb-2"></i><span class="text-xs">%s</span></div>', $icons[$b] ?? 'fa-check', $labels[$b] ?? $b);
                }, $badges));

                return sprintf('<div class="flex justify-center gap-8">%s</div>', $badgesHtml);

            case 'testimonial-card':
                $author = $settings['author'] ?? 'ลูกค้า';
                $role = $settings['role'] ?? '';
                $avatar = $settings['avatar'] ?? '';
                $rating = $settings['rating'] ?? 5;
                $starsHtml = str_repeat('<i class="fas fa-star text-yellow-400"></i>', $rating);

                return sprintf(
                    '<div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                        <div class="flex gap-1 mb-4">%s</div>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">"%s"</p>
                        <div class="flex items-center gap-3">
                            %s
                            <div><div class="font-semibold">%s</div>%s</div>
                        </div>
                    </div>',
                    $starsHtml,
                    $content,
                    $avatar ? sprintf('<img src="%s" class="w-12 h-12 rounded-full">', $avatar) : '<div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center"><i class="fas fa-user"></i></div>',
                    $author,
                    $role ? sprintf('<div class="text-sm text-gray-500">%s</div>', $role) : ''
                );

            case 'client-logos':
                $logos = $settings['logos'] ?? [];
                $logosHtml = implode('', array_map(fn ($l) => sprintf('<img src="%s" class="h-12 grayscale hover:grayscale-0 transition">', $l), $logos));

                return sprintf('<div class="flex flex-wrap items-center justify-center gap-8">%s</div>', $logosHtml);

            case 'popup-exit':
                return sprintf(
                    '<div x-data="{ show: false }" @mouseleave.document="show = true" x-show="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-8 max-w-md mx-4" @click.away="show = false">
                            <button @click="show = false" class="absolute top-4 right-4"><i class="fas fa-times"></i></button>
                            %s
                        </div>
                    </div>',
                    $content
                );

                // === Data & Charts Elements ===
            case 'chart-bar':
            case 'chart-line':
            case 'chart-pie':
                $chartType = str_replace('chart-', '', $element->type);
                $data = $settings['data'] ?? [];

                return sprintf('<canvas class="chart" data-type="%s" data-values=\'%s\'></canvas>', $chartType, json_encode($data));

            case 'stats-card':
            case 'kpi-card':
                $number = $settings['number'] ?? '0';
                $label = $settings['label'] ?? '';
                $icon = $settings['icon'] ?? 'fas fa-chart-line';
                $trend = $settings['trend'] ?? null;

                return sprintf(
                    '<div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center"><i class="%s text-primary text-xl"></i></div>
                            %s
                        </div>
                        <div class="text-3xl font-bold mb-1">%s</div>
                        <div class="text-gray-500">%s</div>
                    </div>',
                    $icon,
                    $trend !== null ? sprintf('<span class="text-sm %s"><i class="fas fa-arrow-%s"></i> %s%%</span>', $trend >= 0 ? 'text-green-500' : 'text-red-500', $trend >= 0 ? 'up' : 'down', abs($trend)) : '',
                    $number,
                    $label
                );

            case 'table-data':
                $headers = $settings['headers'] ?? [];
                $rows = $settings['rows'] ?? [];
                $headersHtml = implode('', array_map(fn ($h) => sprintf('<th class="px-4 py-2 text-left">%s</th>', $h), $headers));
                $rowsHtml = implode('', array_map(fn ($row) => '<tr class="border-t dark:border-gray-700">'.implode('', array_map(fn ($cell) => sprintf('<td class="px-4 py-2">%s</td>', $cell), $row)).'</tr>',
                    $rows
                ));

                return sprintf('<table class="w-full"><thead class="bg-gray-100 dark:bg-gray-700"><tr>%s</tr></thead><tbody>%s</tbody></table>', $headersHtml, $rowsHtml);

            case 'timeline':
                $items = $settings['items'] ?? [];
                $itemsHtml = implode('', array_map(fn ($item, $i) => sprintf(
                    '<div class="flex gap-4 pb-8 relative">
                        <div class="w-4 h-4 rounded-full bg-primary flex-shrink-0 mt-1"></div>
                        <div class="absolute left-[7px] top-5 w-0.5 h-full bg-gray-200 dark:bg-gray-700"></div>
                        <div><div class="font-semibold">%s</div><div class="text-sm text-gray-500">%s</div><p class="mt-1">%s</p></div>
                    </div>',
                    $item['title'] ?? '', $item['date'] ?? '', $item['content'] ?? ''
                ), $items, array_keys($items)));

                return sprintf('<div class="pl-2">%s</div>', $itemsHtml);

            case 'comparison':
                $items = $settings['items'] ?? [];

                return sprintf('<div class="overflow-x-auto"><table class="w-full comparison-table">%s</table></div>', $content);

                // === Navigation Elements ===
            case 'nav-menu':
            case 'mega-menu':
            case 'sidebar-nav':
                $items = $settings['items'] ?? [];
                $itemsHtml = implode('', array_map(fn ($item) => sprintf('<a href="%s" class="px-4 py-2 hover:text-primary">%s</a>', $item['url'] ?? '#', $item['label'] ?? ''), $items));

                return sprintf('<nav class="flex items-center gap-2">%s</nav>', $itemsHtml);

            case 'breadcrumb':
                $items = $settings['items'] ?? [['label' => 'หน้าแรก', 'url' => '/']];
                $itemsHtml = implode('<i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>',
                    array_map(fn ($item) => sprintf('<a href="%s" class="hover:text-primary">%s</a>', $item['url'] ?? '#', $item['label'] ?? ''), $items)
                );

                return sprintf('<nav class="flex items-center text-sm">%s</nav>', $itemsHtml);

            case 'pagination':
                return '<nav class="flex items-center gap-2"><button class="px-3 py-1 rounded border hover:bg-gray-100"><i class="fas fa-chevron-left"></i></button><button class="px-3 py-1 rounded bg-primary text-white">1</button><button class="px-3 py-1 rounded border hover:bg-gray-100">2</button><button class="px-3 py-1 rounded border hover:bg-gray-100">3</button><button class="px-3 py-1 rounded border hover:bg-gray-100"><i class="fas fa-chevron-right"></i></button></nav>';

            case 'anchor-link':
                $target = $settings['target'] ?? '#top';

                return sprintf('<a href="%s" class="hover:text-primary">%s</a>', $target, $content);

            case 'back-to-top':
                return '<button @click="window.scrollTo({top: 0, behavior: \'smooth\'})" class="fixed bottom-8 right-8 w-12 h-12 rounded-full bg-primary text-white shadow-lg hover:scale-110 transition flex items-center justify-center"><i class="fas fa-arrow-up"></i></button>';

            case 'scroll-indicator':
                return '<div class="fixed top-0 left-0 h-1 bg-primary transition-all" x-data="{ width: 0 }" x-init="window.addEventListener(\'scroll\', () => width = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100)" :style="\'width: \' + width + \'%\'"></div>';

                // === Social Elements ===
            case 'share-buttons':
                $platforms = $settings['platforms'] ?? ['facebook', 'twitter', 'line'];
                $buttonsHtml = implode('', array_map(fn ($p) => sprintf('<button class="w-10 h-10 rounded-full bg-%s text-white flex items-center justify-center"><i class="fab fa-%s"></i></button>', $p === 'line' ? 'green-500' : ($p === 'facebook' ? 'blue-600' : 'blue-400'), $p), $platforms));

                return sprintf('<div class="flex gap-2">%s</div>', $buttonsHtml);

            case 'like-button':
                return '<button x-data="{ liked: false, count: 0 }" @click="liked = !liked; count += liked ? 1 : -1" class="flex items-center gap-2 px-4 py-2 rounded-full border hover:bg-gray-50 transition" :class="liked && \'bg-red-50 border-red-200\'"><i class="far fa-heart" :class="liked && \'fas text-red-500\'"></i><span x-text="count">0</span></button>';

            case 'social-feed':
                return sprintf('<div class="social-feed">%s</div>', $content);

            case 'profile-card':
            case 'team-member':
                $name = $settings['name'] ?? 'ชื่อ';
                $role = $settings['role'] ?? 'ตำแหน่ง';
                $avatar = $settings['avatar'] ?? '';
                $socials = $settings['socials'] ?? [];

                return sprintf(
                    '<div class="text-center p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg">
                        %s
                        <h3 class="font-bold text-lg mt-4">%s</h3>
                        <p class="text-gray-500">%s</p>
                        %s
                    </div>',
                    $avatar ? sprintf('<img src="%s" class="w-24 h-24 rounded-full mx-auto">', $avatar) : '<div class="w-24 h-24 rounded-full mx-auto bg-gray-200 flex items-center justify-center"><i class="fas fa-user text-3xl text-gray-400"></i></div>',
                    $name,
                    $role,
                    ! empty($socials) ? '<div class="flex justify-center gap-3 mt-4">'.implode('', array_map(fn ($s) => sprintf('<a href="%s" class="text-gray-400 hover:text-primary"><i class="fab fa-%s"></i></a>', $s['url'] ?? '#', $s['platform'] ?? 'link'), $socials)).'</div>' : ''
                );

            case 'follow-button':
                return '<button x-data="{ following: false }" @click="following = !following" class="px-6 py-2 rounded-full transition" :class="following ? \'bg-gray-200 text-gray-700\' : \'bg-primary text-white\'" x-text="following ? \'กำลังติดตาม\' : \'ติดตาม\'"></button>';

            case 'comments':
                return sprintf('<div class="comments-section">%s</div>', $content);

            case 'live-chat':
                return '<div class="fixed bottom-8 right-8"><button class="w-14 h-14 rounded-full bg-green-500 text-white shadow-lg hover:scale-110 transition flex items-center justify-center"><i class="fas fa-comments text-xl"></i></button></div>';

                // === Effects & Decorations ===
            case 'gradient-bg':
                $gradient = $settings['gradient'] ?? 'from-primary to-secondary';

                return sprintf('<div class="absolute inset-0 bg-gradient-to-br %s"></div>', $gradient);

            case 'parallax':
                $imageUrl = $element->getImageUrl($isDarkMode);

                return $imageUrl ? sprintf('<div class="parallax bg-fixed bg-center bg-cover" style="background-image: url(\'%s\')">%s</div>', $imageUrl, $content) : '';

            case 'particles':
                return '<div id="particles-js" class="absolute inset-0"></div>';

            case 'blob':
                $color = $settings['color'] ?? 'bg-primary/20';

                return sprintf('<div class="absolute w-72 h-72 %s rounded-full blur-3xl"></div>', $color);

            case 'wave':
            case 'shape-divider':
                return '<svg class="w-full h-auto" viewBox="0 0 1440 100" preserveAspectRatio="none"><path fill="currentColor" d="M0,50 C360,100 720,0 1080,50 C1260,75 1380,25 1440,50 L1440,100 L0,100 Z"></path></svg>';

            case 'glassmorphism':
                return sprintf('<div class="backdrop-blur-lg bg-white/10 dark:bg-black/10 border border-white/20 rounded-xl p-6">%s</div>', $content);

            case 'text-animation':
                $animationType = $settings['animation_type'] ?? 'typing';

                return sprintf('<div class="text-animation" data-animation="%s">%s</div>', $animationType, $content);

            case 'cursor-effect':
            case 'noise-texture':
                return sprintf('<div class="%s">%s</div>', $element->type, $content);

                // === Utility Elements ===
            case 'search-box':
                return '<div class="relative"><input type="search" placeholder="ค้นหา..." class="w-full pl-10 pr-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i></div>';

            case 'language-switcher':
                $languages = $settings['languages'] ?? [['code' => 'th', 'label' => 'ไทย'], ['code' => 'en', 'label' => 'English']];
                $optionsHtml = implode('', array_map(fn ($l) => sprintf('<option value="%s">%s</option>', $l['code'], $l['label']), $languages));

                return sprintf('<select class="px-4 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600">%s</select>', $optionsHtml);

            case 'dark-mode-toggle':
                return '<button @click="$store.darkMode.toggle()" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"><i class="fas fa-moon dark:hidden"></i><i class="fas fa-sun hidden dark:block text-yellow-400"></i></button>';

            case 'cookie-consent':
                return '<div x-data="{ show: true }" x-show="show" class="fixed bottom-0 inset-x-0 bg-gray-900 text-white p-4 flex items-center justify-between"><span>เราใช้ Cookie เพื่อปรับปรุงประสบการณ์การใช้งาน</span><div class="flex gap-2"><button @click="show = false" class="px-4 py-2 bg-primary rounded">ยอมรับ</button><button @click="show = false" class="px-4 py-2 border border-white rounded">ปฏิเสธ</button></div></div>';

            case 'print-button':
                return '<button @click="window.print()" class="flex items-center gap-2 px-4 py-2 border rounded-lg hover:bg-gray-50"><i class="fas fa-print"></i> พิมพ์</button>';

            case 'qr-code':
                $qrContent = $settings['content'] ?? 'https://example.com';

                return sprintf('<div class="qr-code" data-content="%s"><img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=%s" alt="QR Code"></div>', $qrContent, urlencode($qrContent));

            case 'copy-to-clipboard':
                $textToCopy = $settings['text'] ?? $content;

                return sprintf('<button x-data="{ copied: false }" @click="navigator.clipboard.writeText(\'%s\'); copied = true; setTimeout(() => copied = false, 2000)" class="flex items-center gap-2 px-4 py-2 border rounded-lg hover:bg-gray-50"><i class="fas" :class="copied ? \'fa-check text-green-500\' : \'fa-copy\'"></i><span x-text="copied ? \'คัดลอกแล้ว!\' : \'คัดลอก\'"></span></button>', addslashes($textToCopy));

            case 'code-block':
                $language = $settings['language'] ?? 'javascript';

                return sprintf('<pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code class="language-%s">%s</code></pre>', $language, htmlspecialchars($content));

            default:
                return $content;
        }
    }

    /**
     * Render a simple product card
     */
    protected function renderProductCard(array $product): string
    {
        return sprintf(
            '<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                %s
                <h4 class="font-semibold mt-2">%s</h4>
                <p class="text-primary font-bold">%s</p>
            </div>',
            isset($product['image']) ? sprintf('<img src="%s" class="w-full h-32 object-cover rounded">', $product['image']) : '',
            $product['title'] ?? 'สินค้า',
            $product['price'] ?? '฿0'
        );
    }

    /**
     * ดึง CSS classes สำหรับ element
     */
    protected function getElementClasses(HomepageElement $element): string
    {
        $classes = ['element-'.$element->type];

        if ($element->animation) {
            $classes[] = 'animate-on-scroll';
            $classes[] = 'opacity-0';
        }

        if ($element->position_type === 'absolute') {
            $classes[] = 'absolute';
        }

        return implode(' ', $classes);
    }

    /**
     * Extract YouTube video ID
     */
    protected function extractYouTubeId(string $url): string
    {
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);

        return $matches[1] ?? '';
    }

    /**
     * Extract Vimeo video ID
     */
    protected function extractVimeoId(string $url): string
    {
        preg_match('/vimeo\.com\/(?:.*\/)?(\d+)/', $url, $matches);

        return $matches[1] ?? '';
    }

    /**
     * สร้าง default sections สำหรับหน้าแรกใหม่
     */
    public function createDefaultSections(): Collection
    {
        return DB::transaction(function () {
            $sections = collect();

            // Hero Section
            $hero = HomepageSection::create([
                'name' => 'Hero Section',
                'type' => 'hero',
                'order' => 0,
                'is_active' => true,
                'is_fullwidth' => true,
                'min_height' => '600px',
                'padding' => '100px 20px',
                'background_type' => 'gradient',
                'background_gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'background_color_dark' => '#1f2937',
            ]);

            // Add hero elements
            $hero->elements()->createMany([
                [
                    'name' => 'Main Heading',
                    'type' => 'heading',
                    'content' => 'ยินดีต้อนรับสู่ระบบ',
                    'content_en' => 'Welcome to the System',
                    'position_type' => 'relative',
                    'font_size' => '48px',
                    'font_weight' => '700',
                    'text_align' => 'center',
                    'color' => '#ffffff',
                    'color_dark' => '#ffffff',
                    'animation' => 'fadeInDown',
                    'animation_delay' => 0,
                    'settings' => ['heading_level' => 'h1'],
                ],
                [
                    'name' => 'Subtitle',
                    'type' => 'text',
                    'content' => 'ระบบจัดการธุรกิจออนไลน์ครบวงจร',
                    'content_en' => 'Complete Online Business Management System',
                    'position_type' => 'relative',
                    'font_size' => '20px',
                    'text_align' => 'center',
                    'color' => 'rgba(255,255,255,0.9)',
                    'color_dark' => 'rgba(255,255,255,0.9)',
                    'animation' => 'fadeInUp',
                    'animation_delay' => 200,
                    'margin' => '20px 0',
                ],
                [
                    'name' => 'CTA Button',
                    'type' => 'button',
                    'content' => 'เริ่มต้นใช้งาน',
                    'content_en' => 'Get Started',
                    'position_type' => 'relative',
                    'link_url' => '/register',
                    'font_size' => '18px',
                    'font_weight' => '600',
                    'text_align' => 'center',
                    'color' => '#667eea',
                    'color_dark' => '#667eea',
                    'background_color' => '#ffffff',
                    'background_color_dark' => '#ffffff',
                    'padding' => '15px 40px',
                    'border_radius' => '50px',
                    'animation' => 'fadeInUp',
                    'animation_delay' => 400,
                    'hover_effects' => [
                        'transform' => 'translateY(-2px)',
                        'box_shadow' => '0 10px 30px rgba(0,0,0,0.2)',
                    ],
                ],
            ]);

            $sections->push($hero);

            // Features Section
            $features = HomepageSection::create([
                'name' => 'Features Section',
                'type' => 'features',
                'order' => 1,
                'is_active' => true,
                'is_fullwidth' => false,
                'min_height' => '400px',
                'padding' => '80px 20px',
                'background_type' => 'color',
                'background_color' => '#f8fafc',
                'background_color_dark' => '#111827',
            ]);

            $features->elements()->createMany([
                [
                    'name' => 'Features Title',
                    'type' => 'heading',
                    'content' => 'ฟีเจอร์เด่น',
                    'content_en' => 'Key Features',
                    'position_type' => 'relative',
                    'font_size' => '36px',
                    'font_weight' => '700',
                    'text_align' => 'center',
                    'color' => '#1f2937',
                    'color_dark' => '#f9fafb',
                    'animation' => 'fadeIn',
                    'margin' => '0 0 40px 0',
                    'settings' => ['heading_level' => 'h2'],
                ],
            ]);

            $sections->push($features);

            // Clear cache
            $this->clearCache();

            return $sections;
        });
    }

    /**
     * ตรวจสอบว่ามี sections อยู่หรือไม่
     */
    public function hasSections(): bool
    {
        return HomepageSection::exists();
    }
}
