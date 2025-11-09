@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$plans = $content['plans'] ?? [];
$billingCycle = $settings['billing_cycle'] ?? 'monthly'; // monthly, yearly
$highlightPopular = $settings['highlight_popular'] ?? true;
@endphp

<section class="py-20 bg-gradient-to-br from-gray-50 to-white">
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

            @if($settings['show_billing_toggle'] ?? false)
            <div class="flex items-center justify-center gap-4 mt-8">
                <span class="text-gray-600">รายเดือน</span>
                <button class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition"></span>
                </button>
                <span class="text-gray-600">รายปี <span class="text-green-600 font-semibold">(ประหยัด 20%)</span></span>
            </div>
            @endif
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-{{ count($plans) <= 3 ? count($plans) : '3' }} gap-8 max-w-7xl mx-auto">
            @foreach($plans as $plan)
            <div class="relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 {{ $plan['is_popular'] ?? false ? 'ring-4 ring-blue-500 scale-105' : '' }}">
                @if($highlightPopular && ($plan['is_popular'] ?? false))
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                    <span class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-2 rounded-full text-sm font-bold shadow-lg">
                        {{ $plan['popular_badge'] ?? 'ยอดนิยม' }}
                    </span>
                </div>
                @endif

                <div class="p-8">
                    @if(!empty($plan['icon']))
                    <div class="w-16 h-16 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-3xl">
                        {{ $plan['icon'] }}
                    </div>
                    @endif

                    @if(!empty($plan['name']))
                    <h3 class="text-2xl font-bold text-gray-900 text-center mb-2">
                        {{ $plan['name'] }}
                    </h3>
                    @endif

                    @if(!empty($plan['description']))
                    <p class="text-gray-600 text-center mb-6">
                        {{ $plan['description'] }}
                    </p>
                    @endif

                    <div class="text-center mb-8">
                        @if(!empty($plan['price']))
                        <div class="flex items-baseline justify-center gap-2">
                            <span class="text-5xl font-bold text-gray-900">
                                {{ $plan['currency'] ?? '฿' }}{{ $plan['price'] }}
                            </span>
                            @if(!empty($plan['period']))
                            <span class="text-gray-600">/{{ $plan['period'] }}</span>
                            @endif
                        </div>
                        @endif

                        @if(!empty($plan['original_price']))
                        <div class="mt-2">
                            <span class="text-gray-500 line-through">{{ $plan['currency'] ?? '฿' }}{{ $plan['original_price'] }}</span>
                            @if(!empty($plan['discount_percent']))
                            <span class="ml-2 text-green-600 font-semibold">ประหยัด {{ $plan['discount_percent'] }}%</span>
                            @endif
                        </div>
                        @endif
                    </div>

                    @if(!empty($plan['features']))
                    <ul class="space-y-4 mb-8">
                        @foreach($plan['features'] as $feature)
                        <li class="flex items-start gap-3">
                            @if(is_array($feature) && isset($feature['included']) && !$feature['included'])
                            <svg class="w-6 h-6 text-gray-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-400">{{ is_array($feature) ? $feature['text'] : $feature }}</span>
                            @else
                            <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">{{ is_array($feature) ? $feature['text'] : $feature }}</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                    @endif

                    @if(!empty($plan['cta_text']))
                    <a href="{{ $plan['cta_url'] ?? '#' }}"
                       class="block w-full text-center px-8 py-4 {{ ($plan['is_popular'] ?? false) ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:shadow-xl' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} rounded-xl font-semibold transition-all duration-300 transform hover:scale-105">
                        {{ $plan['cta_text'] }}
                    </a>
                    @endif

                    @if(!empty($plan['note']))
                    <p class="text-center text-sm text-gray-500 mt-4">{{ $plan['note'] }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        @if(!empty($content['footer_text']))
        <div class="text-center mt-12">
            <p class="text-gray-600">{{ $content['footer_text'] }}</p>
        </div>
        @endif
    </div>
</section>
