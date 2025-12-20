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
            <section
                id="section-{{ $section->id }}"
                class="homepage-section relative overflow-hidden transition-all"
                style="
                    min-height: {{ $section->min_height ?? '400px' }};
                    padding: {{ $section->padding ?? '60px 20px' }};
                    {{ $section->background_type === 'color' ? 'background-color: ' . ($section->background_color ?? '#ffffff') . ';' : '' }}
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
@endif
