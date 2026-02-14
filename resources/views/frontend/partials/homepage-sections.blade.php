{{--
/**
 * Homepage Dynamic Sections Renderer
 *
 * Render sections จาก Homepage Manager database
 * สามารถแก้ไขผ่าน Admin Panel ที่ /admin/homepage-manager
 *
 * @var \Illuminate\Database\Eloquent\Collection $sections
 */
--}}

@if(isset($sections) && $sections->count() > 0)
    {{-- Dynamic Sections from Homepage Manager --}}
    @foreach($sections as $section)
        @if($section->is_active)
            @php
                // ตรวจสอบว่า background เป็นสีอ่อน/ขาวหรือไม่ แล้ว override ให้กลมกลืนกับ theme สีเข้ม
                $bgColor = $section->background_color ?? '#ffffff';
                $isLightBg = false;
                if ($section->background_type === 'color' || !$section->background_type) {
                    // แปลง hex เป็น RGB แล้วคำนวณ luminance
                    $hex = ltrim($bgColor, '#');
                    if (strlen($hex) === 6) {
                        $r = hexdec(substr($hex, 0, 2));
                        $g = hexdec(substr($hex, 2, 2));
                        $b = hexdec(substr($hex, 4, 2));
                        // ถ้า luminance สูง (สีอ่อน) ให้ override เป็นสีเข้ม
                        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
                        $isLightBg = $luminance > 0.6;
                    }
                }
                // สี background ที่จะใช้จริง
                $darkOverrideBg = 'background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e293b 100%);';
            @endphp
            <section
                id="section-{{ $section->id }}"
                class="homepage-section relative overflow-hidden transition-all {{ $isLightBg ? 'homepage-section--dark-override' : '' }}"
                style="
                    min-height: {{ $section->min_height ?? '400px' }};
                    padding: {{ $section->padding ?? '60px 20px' }};
                    {{ $isLightBg ? $darkOverrideBg : '' }}
                    {{ !$isLightBg && $section->background_type === 'color' ? 'background-color: ' . $bgColor . ';' : '' }}
                    {{ $section->background_type === 'gradient' ? 'background: ' . ($section->background_gradient ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)') . ';' : '' }}
                    {{ $section->background_type === 'image' && $section->background_image ? 'background-image: url(' . asset('storage/' . $section->background_image) . '); background-size: ' . ($section->background_size ?? 'cover') . '; background-position: ' . ($section->background_position ?? 'center') . ';' : '' }}
                    {{ $section->background_fixed ? 'background-attachment: fixed;' : '' }}
                "
                data-section-type="{{ $section->type }}"
                @if($section->animation)
                    x-data="{ shown: false }"
                    x-intersect:enter="shown = true"
                    :class="shown && 'animate-{{ $section->animation }}'"
                @endif
            >
                {{-- Background Overlay --}}
                @if($section->background_overlay)
                    <div class="absolute inset-0" style="background: {{ $section->background_overlay }};"></div>
                @endif

                {{-- Section Container --}}
                <div class="{{ $section->is_fullwidth ? 'w-full' : 'container mx-auto' }} relative z-10">
                    @if($section->activeElements && $section->activeElements->count() > 0)
                        {{-- Grid layout for features/stats sections --}}
                        @if(in_array($section->type, ['features', 'stats', 'gallery', 'team']))
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ min($section->activeElements->count(), 4) }} gap-6 lg:gap-8">
                                @foreach($section->activeElements as $element)
                                    @include('frontend.partials.homepage-element', ['element' => $element])
                                @endforeach
                            </div>
                        @else
                            {{-- Stack layout for hero/cta/custom sections --}}
                            <div class="flex flex-col items-center">
                                @foreach($section->activeElements as $element)
                                    @include('frontend.partials.homepage-element', ['element' => $element])
                                @endforeach
                            </div>
                        @endif
                    @else
                        {{-- Empty section placeholder (only visible in admin preview) --}}
                        @if(auth()->check() && auth()->user()->isAdmin())
                            <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                                <i class="fas fa-info-circle text-2xl mb-2"></i>
                                <p>Section นี้ยังไม่มี Elements - <a href="{{ route('admin.homepage-manager.index') }}" class="text-indigo-500 hover:underline">จัดการที่นี่</a></p>
                            </div>
                        @endif
                    @endif
                </div>
            </section>
        @endif
    @endforeach

{{-- CSS สำหรับ override สี text ใน sections ที่ถูกแปลงเป็น dark background --}}
<style>
    .homepage-section--dark-override,
    .homepage-section--dark-override * {
        color: #e2e8f0 !important;
    }
    .homepage-section--dark-override h1,
    .homepage-section--dark-override h2,
    .homepage-section--dark-override h3,
    .homepage-section--dark-override h4 {
        color: #ffffff !important;
    }
    .homepage-section--dark-override a {
        color: #93c5fd !important;
    }
    .homepage-section--dark-override a:hover {
        color: #bfdbfe !important;
    }
</style>
@endif
