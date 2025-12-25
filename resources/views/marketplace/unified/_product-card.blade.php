{{--
    Product Card Component สำหรับ Unified Marketplace

    รองรับ 3 ประเภทสินค้า:
    - chatbot: AI Chatbot
    - trading: Trading Bot
    - software: ซอฟต์แวร์

    @param array $product ข้อมูลสินค้าที่ normalize แล้ว
--}}

@php
    // กำหนดสีตาม product type
    $typeColors = [
        'chatbot' => [
            'gradient' => 'from-purple-600 to-pink-500',
            'bg' => 'bg-purple-100 dark:bg-purple-900/30',
            'text' => 'text-purple-700 dark:text-purple-300',
            'icon' => '🤖',
            'label' => 'AI Chatbot'
        ],
        'trading' => [
            'gradient' => 'from-green-600 to-emerald-500',
            'bg' => 'bg-green-100 dark:bg-green-900/30',
            'text' => 'text-green-700 dark:text-green-300',
            'icon' => '📈',
            'label' => 'Trading Bot'
        ],
        'software' => [
            'gradient' => 'from-blue-600 to-cyan-500',
            'bg' => 'bg-blue-100 dark:bg-blue-900/30',
            'text' => 'text-blue-700 dark:text-blue-300',
            'icon' => '💻',
            'label' => 'ซอฟต์แวร์'
        ],
    ];

    $colors = $typeColors[$product['type']] ?? $typeColors['software'];
@endphp

<div class="group relative">
    <a href="{{ route('marketplace.v3.show', ['type' => $product['type'], 'slug' => $product['slug']]) }}"
       class="block">

        {{-- Card Container with Glassmorphism + 3D Transform --}}
        <div class="relative bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-3xl overflow-hidden border-2 border-white/50 dark:border-slate-700/50 shadow-xl
                    hover:shadow-2xl hover:-translate-y-2 hover:scale-[1.02] hover:rotate-1
                    transition-all duration-500 cursor-pointer">

            {{-- Product Type Badge --}}
            <div class="absolute top-4 left-4 z-10">
                <div class="px-4 py-2 {{ $colors['bg'] }} backdrop-blur-xl rounded-full border border-white/30 shadow-lg">
                    <span class="text-sm font-bold {{ $colors['text'] }}">
                        {{ $colors['icon'] }} {{ $colors['label'] }}
                    </span>
                </div>
            </div>

            {{-- Featured Badge (if applicable) --}}
            @if($product['is_featured'] ?? false)
                <div class="absolute top-4 right-4 z-10">
                    <div class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 rounded-full border border-white/30 shadow-lg animate-pulse">
                        <span class="text-sm font-bold text-white">
                            ⭐ แนะนำ
                        </span>
                    </div>
                </div>
            @endif

            {{-- Product Image --}}
            <div class="relative w-full h-56 bg-gradient-to-br {{ $colors['gradient'] }} overflow-hidden">
                @if($product['image'] && $product['image'] !== '/images/default-bot.png' && $product['image'] !== '/images/default-trading.png' && $product['image'] !== '/images/default-software.png')
                    <img
                        src="{{ $product['image'] }}"
                        alt="{{ $product['name'] }}"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                        loading="lazy"
                        onerror="this.src='{{ $product['image'] }}'"
                    >
                @else
                    {{-- Default Icon/Placeholder --}}
                    <div class="w-full h-full flex items-center justify-center">
                        <div class="text-8xl opacity-80">{{ $colors['icon'] }}</div>
                    </div>
                @endif

                {{-- Gradient Overlay --}}
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            </div>

            {{-- Product Info --}}
            <div class="p-6">
                {{-- Product Name --}}
                <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2 line-clamp-2 group-hover:text-transparent group-hover:bg-gradient-to-r group-hover:{{ $colors['gradient'] }} group-hover:bg-clip-text transition-all duration-300">
                    {{ $product['name'] }}
                </h3>

                {{-- Product Description --}}
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4 line-clamp-3 leading-relaxed">
                    {{ Str::limit($product['description'], 120) }}
                </p>

                {{-- Price and CTA --}}
                <div class="flex items-center justify-between pt-4 border-t-2 border-slate-100 dark:border-slate-700">
                    {{-- Price --}}
                    <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">ราคา</div>
                        <div class="text-2xl font-black bg-gradient-to-r {{ $colors['gradient'] }} bg-clip-text text-transparent">
                            {{ $product['price_label'] }}
                        </div>
                    </div>

                    {{-- View Details Button --}}
                    <div class="px-6 py-3 bg-gradient-to-r {{ $colors['gradient'] }} text-white rounded-xl font-bold shadow-lg
                                group-hover:shadow-2xl group-hover:scale-110 transition-all duration-300">
                        ดูเพิ่มเติม →
                    </div>
                </div>
            </div>

            {{-- Hover Glow Effect --}}
            <div class="absolute inset-0 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"
                 style="box-shadow: 0 0 80px rgba(59, 130, 246, 0.3);"></div>
        </div>
    </a>
</div>
