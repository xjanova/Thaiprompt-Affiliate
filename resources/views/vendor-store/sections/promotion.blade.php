{{-- Section: Promotion Banner --}}
@if($layoutSettings->show_promotion_banner)
    @php $lc = $layoutSettings->layout_classes; @endphp
    <section class="{{ $lc['section_spacing'] }}">
        <div class="{{ $lc['container'] }}">
            @if($layoutSettings->promotion_image)
                <a href="{{ $layoutSettings->promotion_link ?? '#' }}"
                   class="block {{ $lc['border_radius'] }} overflow-hidden shadow-lg hover:shadow-xl transition-all group">
                    <img src="{{ str_starts_with($layoutSettings->promotion_image, 'http') ? $layoutSettings->promotion_image : Storage::url($layoutSettings->promotion_image) }}"
                         alt="{{ $layoutSettings->promotion_text ?? 'โปรโมชั่น' }}"
                         class="w-full h-[200px] md:h-[300px] object-cover group-hover:scale-105 transition-transform duration-500">
                    @if($layoutSettings->promotion_text)
                        <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                            <span class="text-white text-2xl md:text-4xl font-bold drop-shadow-lg">{{ $layoutSettings->promotion_text }}</span>
                        </div>
                    @endif
                </a>
            @elseif($layoutSettings->promotion_text)
                <div class="{{ $lc['border_radius'] }} overflow-hidden p-8 text-center text-white"
                     style="background: linear-gradient(135deg, var(--store-primary), var(--store-accent))">
                    <p class="text-xl md:text-2xl font-bold">{{ $layoutSettings->promotion_text }}</p>
                    @if($layoutSettings->promotion_link)
                        <a href="{{ $layoutSettings->promotion_link }}"
                           class="inline-block mt-4 px-6 py-2 bg-white/20 hover:bg-white/30 {{ $lc['button'] }} transition font-semibold">
                            ดูรายละเอียด →
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endif
