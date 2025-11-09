@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$faqs = $content['faqs'] ?? [];
$layout = $settings['layout'] ?? 'single'; // single, two-column
$accordionStyle = $settings['accordion_style'] ?? 'default'; // default, bordered, minimal
@endphp

<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        @if(!empty($content['title']))
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                {{ $content['title'] }}
            </h2>
            @if(!empty($content['subtitle']))
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                {{ $content['subtitle'] }}
            </p>
            @endif
        </div>
        @endif

        <div class="{{ $layout === 'two-column' ? 'grid md:grid-cols-2 gap-8' : 'max-w-4xl mx-auto' }}">
            @if($layout === 'two-column')
                @php
                $halfCount = ceil(count($faqs) / 2);
                $firstColumn = array_slice($faqs, 0, $halfCount);
                $secondColumn = array_slice($faqs, $halfCount);
                @endphp

                <div class="space-y-4">
                    @foreach($firstColumn as $index => $faq)
                        @include('page-builder.sections.partials.faq-item', ['faq' => $faq, 'index' => $index, 'accordionStyle' => $accordionStyle])
                    @endforeach
                </div>

                <div class="space-y-4">
                    @foreach($secondColumn as $index => $faq)
                        @include('page-builder.sections.partials.faq-item', ['faq' => $faq, 'index' => $index + $halfCount, 'accordionStyle' => $accordionStyle])
                    @endforeach
                </div>
            @else
                <div class="space-y-4" x-data="{ activeAccordion: null }">
                    @foreach($faqs as $index => $faq)
                    <div class="{{ $accordionStyle === 'bordered' ? 'border-2 border-gray-200 rounded-xl' : ($accordionStyle === 'minimal' ? 'border-b border-gray-200 last:border-0' : 'bg-gray-50 rounded-xl') }}">
                        <button @click="activeAccordion = activeAccordion === {{ $index }} ? null : {{ $index }}"
                                class="w-full flex items-center justify-between gap-4 p-6 text-left transition-colors {{ $accordionStyle === 'minimal' ? 'hover:bg-gray-50' : 'hover:bg-gray-100' }}">
                            <span class="text-lg font-semibold text-gray-900 flex-1">
                                @if(!empty($faq['question']))
                                    {{ $faq['question'] }}
                                @endif
                            </span>
                            <svg x-show="activeAccordion !== {{ $index }}"
                                 class="w-6 h-6 text-gray-500 flex-shrink-0 transition-transform"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <svg x-show="activeAccordion === {{ $index }}"
                                 class="w-6 h-6 text-blue-600 flex-shrink-0 transition-transform"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                        </button>

                        <div x-show="activeAccordion === {{ $index }}"
                             x-collapse
                             class="overflow-hidden">
                            <div class="px-6 pb-6 text-gray-600 leading-relaxed">
                                @if(!empty($faq['answer']))
                                    {!! nl2br(e($faq['answer'])) !!}
                                @endif

                                @if(!empty($faq['link_url']))
                                <a href="{{ $faq['link_url'] }}" class="inline-flex items-center gap-2 mt-4 text-blue-600 font-semibold hover:text-blue-700 transition-colors">
                                    {{ $faq['link_text'] ?? 'เรียนรู้เพิ่มเติม' }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if(!empty($content['cta_text']))
        <div class="text-center mt-12">
            <p class="text-gray-600 mb-6">{{ $content['cta_description'] ?? 'ไม่พบคำตอบที่คุณต้องการ?' }}</p>
            <a href="{{ $content['cta_url'] ?? '#' }}"
               class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                {{ $content['cta_text'] }}
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            </a>
        </div>
        @endif
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('faqAccordion', () => ({
        activeAccordion: null
    }));
});
</script>
@endpush
