@extends('layouts.admin-v3')

@section('title', 'Setup Guide - คู่มือการตั้งค่า Cloud GPU')

@section('content')
<div class="space-y-6" x-data="{
    selectedProvider: 'google-colab',
    providers: {{ json_encode($providers->pluck('slug')->toArray()) }}
}">
    {{-- Page Header --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-purple-500 rounded-xl flex items-center justify-center">
                <i class="fas fa-book-open text-3xl text-white"></i>
            </div>
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                    คู่มือการตั้งค่า Cloud GPU
                </h1>
                <p class="text-white/80 mt-2 drop-shadow">
                    เรียนรู้วิธีการตั้งค่า Cloud GPU providers ต่างๆ แบบ Step-by-step
                </p>
            </div>
        </div>
    </div>

    {{-- Provider Selector --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-xl font-bold text-white mb-4">เลือก Cloud Provider</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-3">
            @foreach($providers as $provider)
            <button @click="selectedProvider = '{{ $provider->slug }}'"
                    :class="selectedProvider === '{{ $provider->slug }}' ?
                        'bg-gradient-to-r from-cyan-500 to-blue-600 text-white scale-105' :
                        'glass-neu text-white/80 hover:text-white'"
                    class="p-4 rounded-xl transition-all transform hover:scale-105 border border-white/20">
                <div class="flex flex-col items-center gap-2">
                    @if($provider->logo_url)
                        <img src="{{ $provider->logo_url }}" alt="{{ $provider->name }}" class="w-12 h-12 rounded-lg object-cover">
                    @else
                        <i class="fas fa-cloud text-3xl"></i>
                    @endif
                    <span class="font-bold text-sm text-center">{{ $provider->name }}</span>
                    @if($provider->has_free_tier)
                        <span class="px-2 py-0.5 bg-green-500/30 text-green-300 text-xs rounded-full">ฟรี</span>
                    @endif
                </div>
            </button>
            @endforeach
        </div>
    </div>

    {{-- Setup Guides for Each Provider --}}
    @foreach($providers as $provider)
    <div x-show="selectedProvider === '{{ $provider->slug }}'"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         class="space-y-6">

        {{-- Provider Overview --}}
        <div class="glass-fusion rounded-2xl p-6 border border-white/30">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-white mb-2">{{ $provider->name }}</h2>
                    <p class="text-white/80">{{ $provider->description }}</p>
                </div>
                @if($provider->logo_url)
                    <img src="{{ $provider->logo_url }}" alt="{{ $provider->name }}" class="w-20 h-20 rounded-xl object-cover">
                @endif
            </div>

            {{-- Quick Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                @if($provider->has_free_tier)
                <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-4">
                    <div class="text-green-400 text-xs font-semibold mb-1">Free GPU Hours</div>
                    <div class="text-white text-2xl font-bold">{{ $provider->free_gpu_hours ?? 0 }}</div>
                </div>
                @endif

                @if($provider->min_price_per_hour)
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
                    <div class="text-blue-400 text-xs font-semibold mb-1">Starting Price</div>
                    <div class="text-white text-2xl font-bold">${{ number_format($provider->min_price_per_hour, 2) }}/hr</div>
                </div>
                @endif

                @if($provider->gpu_types && count($provider->gpu_types) > 0)
                <div class="bg-purple-500/10 border border-purple-500/30 rounded-xl p-4">
                    <div class="text-purple-400 text-xs font-semibold mb-1">GPU Types</div>
                    <div class="text-white text-2xl font-bold">{{ count($provider->gpu_types) }}</div>
                </div>
                @endif

                <div class="bg-orange-500/10 border border-orange-500/30 rounded-xl p-4">
                    <div class="text-orange-400 text-xs font-semibold mb-1">User Rating</div>
                    <div class="text-white text-2xl font-bold">
                        {{ $provider->user_rating ?? 'N/A' }}
                        @if($provider->user_rating)
                        <i class="fas fa-star text-yellow-400 text-sm"></i>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Setup Steps based on provider --}}
        @if($provider->slug === 'google-colab')
            @include('admin.ai-rental.setup-guides.google-colab')
        @elseif($provider->slug === 'kaggle-notebooks')
            @include('admin.ai-rental.setup-guides.kaggle')
        @elseif($provider->slug === 'lightning-ai-free')
            @include('admin.ai-rental.setup-guides.lightning-ai')
        @elseif($provider->slug === 'runpod')
            @include('admin.ai-rental.setup-guides.runpod')
        @elseif($provider->slug === 'vast-ai')
            @include('admin.ai-rental.setup-guides.vast-ai')
        @elseif($provider->slug === 'lambda-labs')
            @include('admin.ai-rental.setup-guides.lambda-labs')
        @elseif($provider->slug === 'paperspace-gradient')
            @include('admin.ai-rental.setup-guides.paperspace')
        @elseif($provider->slug === 'banana-dev')
            @include('admin.ai-rental.setup-guides.banana')
        @else
            {{-- Generic Setup Guide --}}
            @include('admin.ai-rental.setup-guides.generic')
        @endif

        {{-- Features & Capabilities --}}
        <div class="glass-fusion rounded-2xl p-6 border border-white/30">
            <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-check-circle text-green-400"></i>
                Features & Capabilities
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="flex items-center gap-3">
                    <i class="fas {{ $provider->supports_huggingface ? 'fa-check text-green-400' : 'fa-times text-red-400' }}"></i>
                    <span class="text-white/80">Hugging Face</span>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fas {{ $provider->supports_docker ? 'fa-check text-green-400' : 'fa-times text-red-400' }}"></i>
                    <span class="text-white/80">Docker</span>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fas {{ $provider->supports_jupyter ? 'fa-check text-green-400' : 'fa-times text-red-400' }}"></i>
                    <span class="text-white/80">Jupyter Notebook</span>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fas {{ $provider->supports_api ? 'fa-check text-green-400' : 'fa-times text-red-400' }}"></i>
                    <span class="text-white/80">API Access</span>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fas {{ $provider->supports_ssh ? 'fa-check text-green-400' : 'fa-times text-red-400' }}"></i>
                    <span class="text-white/80">SSH Access</span>
                </div>
            </div>
        </div>

        {{-- GPU Types Available --}}
        @if($provider->gpu_types && count($provider->gpu_types) > 0)
        <div class="glass-fusion rounded-2xl p-6 border border-white/30">
            <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-microchip text-purple-400"></i>
                Available GPU Types
            </h3>

            <div class="flex flex-wrap gap-2">
                @foreach($provider->gpu_types as $gpu)
                    <span class="px-4 py-2 bg-purple-500/20 border border-purple-500/30 text-purple-300 rounded-lg font-semibold">
                        {{ $gpu }}
                    </span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- External Links --}}
        <div class="glass-fusion rounded-2xl p-6 border border-white/30">
            <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-external-link-alt text-cyan-400"></i>
                Useful Links
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @if($provider->website_url)
                <a href="{{ $provider->website_url }}" target="_blank"
                   class="flex items-center gap-3 p-4 glass-neu rounded-xl hover:bg-white/10 transition-all group">
                    <i class="fas fa-globe text-cyan-400 text-xl"></i>
                    <div>
                        <div class="text-white font-semibold group-hover:text-cyan-400 transition">Official Website</div>
                        <div class="text-white/60 text-xs">{{ $provider->website_url }}</div>
                    </div>
                </a>
                @endif

                @if($provider->setup_guide_url)
                <a href="{{ $provider->setup_guide_url }}" target="_blank"
                   class="flex items-center gap-3 p-4 glass-neu rounded-xl hover:bg-white/10 transition-all group">
                    <i class="fas fa-book text-blue-400 text-xl"></i>
                    <div>
                        <div class="text-white font-semibold group-hover:text-blue-400 transition">Documentation</div>
                        <div class="text-white/60 text-xs">Official docs</div>
                    </div>
                </a>
                @endif

                @if($provider->api_documentation_url)
                <a href="{{ $provider->api_documentation_url }}" target="_blank"
                   class="flex items-center gap-3 p-4 glass-neu rounded-xl hover:bg-white/10 transition-all group">
                    <i class="fas fa-code text-purple-400 text-xl"></i>
                    <div>
                        <div class="text-white font-semibold group-hover:text-purple-400 transition">API Documentation</div>
                        <div class="text-white/60 text-xs">For developers</div>
                    </div>
                </a>
                @endif
            </div>
        </div>

        {{-- Limitations --}}
        @if($provider->limitations)
        <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-2xl p-6">
            <h3 class="text-lg font-bold text-yellow-300 mb-3 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i>
                ข้อจำกัดและข้อควรระวัง
            </h3>
            <p class="text-white/80">{{ $provider->limitations }}</p>
        </div>
        @endif
    </div>
    @endforeach

    {{-- Back Button --}}
    <div class="flex justify-center pt-6">
        <a href="{{ route('admin.ai-rental.dashboard') }}"
           class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold rounded-xl hover:scale-105 transition-transform shadow-lg">
            <i class="fas fa-arrow-left"></i>
            กลับสู่ Dashboard
        </a>
    </div>
</div>

@endsection
