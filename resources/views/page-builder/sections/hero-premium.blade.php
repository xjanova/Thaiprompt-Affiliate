@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$bgGradientFrom = $settings['gradient_from'] ?? '#581c87';
$bgGradientTo = $settings['gradient_to'] ?? '#1e3a8a';
$featureCards = $content['feature_cards'] ?? [];
$trustBadges = $content['trust_badges'] ?? [];
@endphp

<section class="relative min-h-screen flex items-center justify-center overflow-hidden"
         style="background: linear-gradient(135deg, {{ $bgGradientFrom }} 0%, {{ $bgGradientTo }} 100%);">

    @if($settings['has_animated_background'] ?? false)
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute w-96 h-96 bg-gradient-to-r from-purple-500 to-pink-500 opacity-20 rounded-full -top-20 -left-20 animate-pulse blur-3xl"></div>
        <div class="absolute w-80 h-80 bg-gradient-to-r from-blue-500 to-cyan-500 opacity-20 rounded-full top-40 right-20 animate-pulse blur-3xl" style="animation-delay: 1s;"></div>
        <div class="absolute w-64 h-64 bg-gradient-to-r from-pink-500 to-purple-500 opacity-20 rounded-full bottom-20 left-1/3 animate-pulse blur-3xl" style="animation-delay: 2s;"></div>
    </div>

    <!-- Grid Pattern Overlay -->
    <div class="absolute inset-0 opacity-10" style="background-image: linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px); background-size: 50px 50px;"></div>
    @endif

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center text-white">

        @if($settings['has_logo'] ?? false)
        <!-- Logo Section -->
        <div class="mb-12 animate-fade-in-down">
            <div class="inline-block p-8 bg-white/10 backdrop-blur-xl rounded-3xl border-2 border-white/20 shadow-2xl transform hover:scale-105 transition-all duration-500">
                <img src="{{ asset($content['logo'] ?? 'images/logo.svg') }}" alt="Logo" class="w-32 h-32 mx-auto filter drop-shadow-2xl">
            </div>
        </div>
        @endif

        <div class="animate-fade-in-up">
            @if(!empty($content['title']))
            <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
                @if($content['title_gradient'] ?? false)
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-pink-300 to-purple-300 animate-pulse">
                    {{ $content['title'] }}
                </span><br>
                @else
                {{ $content['title'] }}<br>
                @endif
                @if(!empty($content['subtitle']))
                <span class="text-white drop-shadow-2xl">{{ $content['subtitle'] }}</span>
                @endif
            </h1>
            @endif

            @if(!empty($content['description']))
            <p class="text-xl md:text-2xl mb-12 text-indigo-200 max-w-3xl mx-auto leading-relaxed">
                {!! nl2br(e($content['description'])) !!}
            </p>
            @endif

            <!-- Feature Cards Grid -->
            @if(!empty($featureCards))
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12 max-w-6xl mx-auto">
                @foreach($featureCards as $card)
                <a href="{{ $card['url'] ?? '#' }}" class="group relative bg-gradient-to-br from-purple-600/30 to-pink-600/30 backdrop-blur-xl border border-white/20 rounded-2xl p-6 transform hover:scale-105 hover:-translate-y-2 transition-all duration-300 hover:shadow-2xl hover:shadow-purple-500/50 cursor-pointer"
                   style="background: linear-gradient(135deg, {{ $card['gradient_from'] ?? '#9333ea' }}33, {{ $card['gradient_to'] ?? '#db2777' }}33);">
                    <div class="absolute inset-0 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity duration-300"
                         style="background: linear-gradient(135deg, {{ $card['gradient_from'] ?? '#9333ea' }}, {{ $card['gradient_to'] ?? '#db2777' }});"></div>

                    @if(!empty($card['badge']))
                    <div class="absolute -top-2 -right-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg animate-pulse">
                        {{ $card['badge'] }}
                    </div>
                    @endif

                    <div class="relative z-10">
                        <div class="text-5xl mb-4 group-hover:scale-110 transition-transform">{{ $card['icon'] ?? '🎯' }}</div>
                        <h3 class="text-xl font-bold mb-2 text-white">{{ $card['title'] ?? '' }}</h3>
                        <p class="text-purple-200 text-sm mb-4">{{ $card['description'] ?? '' }}</p>
                        <div class="flex items-center justify-center text-yellow-300 font-semibold group-hover:gap-2 transition-all">
                            <span>เริ่มเลย</span>
                            <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
            @endif

            <!-- CTA Buttons -->
            @if(!empty($content['cta_primary_text']) || !empty($content['cta_secondary_text']))
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                @guest
                    @if(!empty($content['cta_primary_text']))
                    <a href="{{ $content['cta_primary_url'] ?? '#' }}" class="group relative inline-flex items-center justify-center px-10 py-5 bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 text-white font-bold text-lg rounded-2xl shadow-2xl hover:shadow-purple-500/50 transition-all duration-300 transform hover:scale-105 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-600 via-pink-500 to-yellow-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <span class="relative z-10">{{ $content['cta_primary_text'] }}</span>
                        <svg class="w-5 h-5 ml-2 relative z-10 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                    @endif
                    @if(!empty($content['cta_secondary_text']))
                    <a href="{{ $content['cta_secondary_url'] ?? '#' }}" class="inline-flex items-center justify-center px-10 py-5 bg-white/10 backdrop-blur-sm text-white font-bold text-lg rounded-2xl border-2 border-white/30 hover:bg-white/20 hover:border-white/50 transition-all duration-300 transform hover:scale-105">
                        {{ $content['cta_secondary_text'] }}
                    </a>
                    @endif
                @else
                    <a href="{{ route(Auth::user()->is_admin ? 'admin.dashboard' : 'user.dashboard') }}" class="inline-flex items-center justify-center px-10 py-5 bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 text-white font-bold text-lg rounded-2xl shadow-2xl hover:shadow-purple-500/50 transition-all duration-300 transform hover:scale-105">
                        🎯 เข้าสู่แดชบอร์ด
                    </a>
                @endguest
            </div>
            @endif

            <!-- Trust Badges -->
            @if(!empty($trustBadges))
            <div class="flex flex-wrap items-center justify-center gap-8 text-white/90">
                @foreach($trustBadges as $badge)
                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm px-5 py-3 rounded-full border border-white/20">
                    @if($badge['icon'] === 'check')
                    <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    @elseif($badge['icon'] === 'users')
                    <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                    </svg>
                    @endif
                    <span class="font-semibold">{{ $badge['text'] ?? '' }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</section>
