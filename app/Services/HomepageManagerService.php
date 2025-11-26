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
     *
     * @return Collection
     */
    public function getHomepage(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . '.sections',
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
     *
     * @return array
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
     *
     * @return array
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
     * @param array $data ข้อมูลที่จะ import
     * @param bool $replace ลบข้อมูลเดิมก่อน import หรือไม่
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
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . '.sections');
        Cache::forget(self::CACHE_PREFIX . '.render_data');
    }

    /**
     * สร้าง HTML สำหรับ section
     *
     * @param HomepageSection $section
     * @param bool $isDarkMode
     * @return string
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
     *
     * @param HomepageElement $element
     * @param bool $isDarkMode
     * @return string
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
        switch ($element->type) {
            case 'heading':
                $tag = $element->settings['heading_level'] ?? 'h2';
                $html .= sprintf('<%s>%s</%s>', $tag, $content, $tag);
                break;

            case 'text':
                $html .= sprintf('<p>%s</p>', nl2br($content));
                break;

            case 'image':
                $imageUrl = $element->getImageUrl($isDarkMode);
                if ($imageUrl) {
                    $alt = $element->settings['alt'] ?? $element->name ?? '';
                    $html .= sprintf(
                        '<img src="%s" alt="%s" class="max-w-full h-auto">',
                        $imageUrl,
                        htmlspecialchars($alt)
                    );
                }
                break;

            case 'button':
                $html .= sprintf(
                    '<span class="inline-flex items-center justify-center">%s</span>',
                    $content
                );
                break;

            case 'video':
                if ($element->video_url) {
                    // Check if YouTube/Vimeo
                    if (preg_match('/youtube|youtu\.be/i', $element->video_url)) {
                        $videoId = $this->extractYouTubeId($element->video_url);
                        $html .= sprintf(
                            '<iframe src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen class="w-full aspect-video"></iframe>',
                            $videoId
                        );
                    } elseif (preg_match('/vimeo/i', $element->video_url)) {
                        $videoId = $this->extractVimeoId($element->video_url);
                        $html .= sprintf(
                            '<iframe src="https://player.vimeo.com/video/%s" frameborder="0" allowfullscreen class="w-full aspect-video"></iframe>',
                            $videoId
                        );
                    } else {
                        $html .= sprintf(
                            '<video src="%s" controls class="w-full"></video>',
                            $element->video_url
                        );
                    }
                }
                break;

            case 'icon':
                if ($element->icon_class) {
                    $html .= sprintf('<i class="%s"></i>', $element->icon_class);
                }
                break;

            case 'html':
                $html .= $content;
                break;

            case 'spacer':
                // Spacer doesn't need content
                break;

            case 'container':
                $html .= $content;
                break;

            default:
                $html .= $content;
        }

        $html .= sprintf('</%s>', $wrapperTag);

        return $html;
    }

    /**
     * ดึง CSS classes สำหรับ element
     *
     * @param HomepageElement $element
     * @return string
     */
    protected function getElementClasses(HomepageElement $element): string
    {
        $classes = ['element-' . $element->type];

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
     *
     * @param string $url
     * @return string
     */
    protected function extractYouTubeId(string $url): string
    {
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extract Vimeo video ID
     *
     * @param string $url
     * @return string
     */
    protected function extractVimeoId(string $url): string
    {
        preg_match('/vimeo\.com\/(?:.*\/)?(\d+)/', $url, $matches);
        return $matches[1] ?? '';
    }

    /**
     * สร้าง default sections สำหรับหน้าแรกใหม่
     *
     * @return Collection
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
     *
     * @return bool
     */
    public function hasSections(): bool
    {
        return HomepageSection::exists();
    }
}
