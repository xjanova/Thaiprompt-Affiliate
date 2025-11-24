@extends('layouts.admin-v3')

@section('title', 'AI Rental with Cloud GPU - Dashboard')

@section('content')
<div class="space-y-6" x-data="{
    showComingSoon: false,
    comingSoonFeature: ''
}">
    {{-- Page Header --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white drop-shadow-lg flex items-center gap-3">
                    <i class="fas fa-server text-cyan-400"></i>
                    AI Rental with Cloud GPU
                </h1>
                <p class="text-white/80 mt-2 drop-shadow">
                    ระบบให้เช่า AI Generation บน Cloud GPU รองรับ Hugging Face Models
                </p>
            </div>

            {{-- Overall Progress --}}
            <div class="text-right">
                <div class="text-5xl font-bold text-white drop-shadow-lg">
                    {{ $overall_completion }}%
                </div>
                <p class="text-white/80 text-sm mt-1">ความสมบูรณ์</p>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="mt-6 bg-white/10 rounded-full h-3 overflow-hidden">
            <div class="bg-gradient-to-r from-cyan-400 via-blue-500 to-purple-600 h-full rounded-full transition-all duration-1000"
                 style="width: {{ $overall_completion }}%"
                 x-data
                 x-init="setTimeout(() => $el.style.width = '{{ $overall_completion }}%', 100)">
            </div>
        </div>
    </div>

    {{-- สถิติรวม (Stats Cards) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Cloud Providers --}}
        <div class="glass-fusion rounded-xl p-5 border border-white/30 hover:scale-105 transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-white/70 text-sm">Cloud Providers</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ $stats['active_providers'] }}</p>
                    <p class="text-green-400 text-xs mt-1">
                        <i class="fas fa-check-circle"></i>
                        {{ $stats['free_providers'] }} ฟรี
                    </p>
                </div>
                <div class="w-12 h-12 bg-cyan-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-cloud text-2xl text-cyan-400"></i>
                </div>
            </div>
        </div>

        {{-- Deployments --}}
        <div class="glass-fusion rounded-xl p-5 border border-white/30 hover:scale-105 transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-white/70 text-sm">Deployments</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ $stats['running_deployments'] }}</p>
                    <p class="text-blue-400 text-xs mt-1">
                        <i class="fas fa-rocket"></i>
                        กำลังทำงาน
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-rocket text-2xl text-blue-400"></i>
                </div>
            </div>
        </div>

        {{-- GPU Hours --}}
        <div class="glass-fusion rounded-xl p-5 border border-white/30 hover:scale-105 transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-white/70 text-sm">GPU Hours</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ number_format($stats['total_gpu_hours']) }}</p>
                    <p class="text-purple-400 text-xs mt-1">
                        <i class="fas fa-clock"></i>
                        ชั่วโมงรวม
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-microchip text-2xl text-purple-400"></i>
                </div>
            </div>
        </div>

        {{-- Trending Models --}}
        <div class="glass-fusion rounded-xl p-5 border border-white/30 hover:scale-105 transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-white/70 text-sm">Hot Models</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ $stats['hot_models'] }}</p>
                    <p class="text-orange-400 text-xs mt-1">
                        <i class="fas fa-fire"></i>
                        กำลังมาแรง
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-fire text-2xl text-orange-400"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- สถานะโครงการ (Project Status) --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-2">
            <i class="fas fa-tasks text-blue-400"></i>
            สถานะการพัฒนาโครงการ
        </h2>

        <div class="space-y-4">
            @foreach([
                ['name' => 'Database & Models', 'key' => 'database', 'icon' => 'database', 'color' => 'green'],
                ['name' => 'Admin Menu', 'key' => 'menu', 'icon' => 'bars', 'color' => 'green'],
                ['name' => 'Cloud GPU Providers', 'key' => 'cloud_providers', 'icon' => 'cloud', 'color' => 'yellow'],
                ['name' => 'Hugging Face Integration', 'key' => 'huggingface_integration', 'icon' => 'plug', 'color' => 'red'],
                ['name' => 'Deployment System', 'key' => 'deployment_system', 'icon' => 'rocket', 'color' => 'red'],
                ['name' => 'News & Trending Models', 'key' => 'news_system', 'icon' => 'newspaper', 'color' => 'red'],
                ['name' => 'Setup Guide', 'key' => 'setup_guide', 'icon' => 'book-open', 'color' => 'red'],
                ['name' => 'Testing & QA', 'key' => 'testing', 'icon' => 'check-circle', 'color' => 'red'],
            ] as $feature)
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-{{ $feature['icon'] }} w-5 text-white/70"></i>
                            <span class="text-white font-medium">{{ $feature['name'] }}</span>

                            {{-- Badge Status --}}
                            @if($project_status[$feature['key']] === 100)
                                <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs font-bold rounded-lg border border-green-500/30">
                                    <i class="fas fa-check"></i> เสร็จสมบูรณ์
                                </span>
                            @elseif($project_status[$feature['key']] > 0)
                                <span class="px-2 py-1 bg-yellow-500/20 text-yellow-400 text-xs font-bold rounded-lg border border-yellow-500/30">
                                    <i class="fas fa-hammer"></i> กำลังพัฒนา
                                </span>
                            @else
                                <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs font-bold rounded-lg border border-red-500/30 cursor-pointer"
                                      @click="showComingSoon = true; comingSoonFeature = '{{ $feature['name'] }}'">
                                    <i class="fas fa-clock"></i> เร็วๆ นี้
                                </span>
                            @endif
                        </div>

                        <span class="text-white font-bold">{{ $project_status[$feature['key']] }}%</span>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="bg-white/10 rounded-full h-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700
                                    {{ $project_status[$feature['key']] === 100 ? 'bg-green-500' :
                                       ($project_status[$feature['key']] > 0 ? 'bg-yellow-500' : 'bg-red-500/50') }}"
                             style="width: {{ $project_status[$feature['key']] }}%">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Quick Actions Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Cloud Providers --}}
        <a href="{{ route('admin.ai-rental.cloud-providers.index') }}"
           class="glass-fusion rounded-xl p-6 border border-white/30 hover:scale-105 hover:border-cyan-400/50 transition-all group">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-cloud text-2xl text-white"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-white font-bold text-lg">Cloud Providers</h3>
                    <p class="text-white/70 text-sm">{{ $stats['active_providers'] }} providers ({{ $stats['free_providers'] }} ฟรี)</p>
                </div>
                <i class="fas fa-arrow-right text-white/50 group-hover:text-white group-hover:translate-x-1 transition-all"></i>
            </div>
        </a>

        {{-- Setup Guide --}}
        <a href="{{ route('admin.ai-rental.setup-guide') }}"
           class="glass-fusion rounded-xl p-6 border border-white/30 hover:scale-105 hover:border-blue-400/50 transition-all group">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-purple-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-book-open text-2xl text-white"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-white font-bold text-lg">Setup Guide</h3>
                    <p class="text-white/70 text-sm">คู่มือการตั้งค่า Cloud GPU</p>
                </div>
                <i class="fas fa-arrow-right text-white/50 group-hover:text-white group-hover:translate-x-1 transition-all"></i>
            </div>
        </a>

        {{-- Cost Calculator --}}
        <a href="{{ route('admin.ai-rental.cost-calculator') }}"
           class="glass-fusion rounded-xl p-6 border border-white/30 hover:scale-105 hover:border-purple-400/50 transition-all group">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-calculator text-2xl text-white"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-white font-bold text-lg">Cost Calculator</h3>
                    <p class="text-white/70 text-sm">คำนวณค่าใช้จ่าย GPU</p>
                </div>
                <i class="fas fa-arrow-right text-white/50 group-hover:text-white group-hover:translate-x-1 transition-all"></i>
            </div>
        </a>
    </div>

    {{-- Recommended Cloud Providers --}}
    @if($recommended_providers->count() > 0)
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                <i class="fas fa-star text-yellow-400"></i>
                Cloud Providers แนะนำ
            </h2>
            <a href="{{ route('admin.ai-rental.cloud-providers.index') }}"
               class="text-cyan-400 hover:text-cyan-300 text-sm font-medium">
                ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($recommended_providers as $provider)
            <div class="glass-neu rounded-xl p-5 border border-white/20 hover:border-cyan-400/50 transition-all">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <h3 class="text-white font-bold text-lg">{{ $provider->name }}</h3>
                        @if($provider->isFree())
                            <span class="inline-block px-2 py-1 bg-green-500/20 text-green-400 text-xs font-bold rounded-lg border border-green-500/30 mt-1">
                                <i class="fas fa-gift"></i> ฟรี
                            </span>
                        @endif
                    </div>
                    @if($provider->logo_url)
                        <img src="{{ $provider->logo_url }}" alt="{{ $provider->name }}" class="w-12 h-12 rounded-lg object-cover">
                    @endif
                </div>

                <p class="text-white/70 text-sm mb-3 line-clamp-2">{{ Str::limit($provider->description, 80) }}</p>

                <div class="flex items-center justify-between text-xs text-white/60">
                    @if($provider->min_price_per_hour)
                        <span><i class="fas fa-dollar-sign"></i> From ${{ number_format($provider->min_price_per_hour, 2) }}/hr</span>
                    @else
                        <span><i class="fas fa-gift"></i> ฟรี</span>
                    @endif
                    <span><i class="fas fa-star text-yellow-400"></i> {{ $provider->user_rating ?? 'N/A' }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- Coming Soon Modal --}}
<div x-show="showComingSoon"
     x-cloak
     @click.away="showComingSoon = false"
     class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="glass-fusion rounded-2xl p-8 border border-white/30 max-w-md w-full"
         @click.stop
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90">
        <div class="text-center">
            <div class="w-20 h-20 bg-gradient-to-br from-cyan-400 to-purple-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-clock text-4xl text-white"></i>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">เร็วๆ นี้!</h3>
            <p class="text-white/80 mb-1" x-text="comingSoonFeature"></p>
            <p class="text-white/60 text-sm mb-6">ฟีเจอร์นี้กำลังอยู่ในระหว่างการพัฒนา</p>
            <button @click="showComingSoon = false"
                    class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold rounded-xl hover:scale-105 transition-transform">
                เข้าใจแล้ว
            </button>
        </div>
    </div>
</div>

@endsection
