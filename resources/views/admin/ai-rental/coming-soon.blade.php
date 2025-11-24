@extends('layouts.admin-v3')

@section('title', $feature . ' - เร็วๆนี้')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center">
    <div class="text-center max-w-2xl mx-auto px-4">
        {{-- Icon Animation --}}
        <div class="mb-8 relative">
            <div class="w-32 h-32 mx-auto bg-gradient-to-br from-cyan-400 via-blue-500 to-purple-600 rounded-full flex items-center justify-center animate-pulse shadow-2xl">
                <i class="fas fa-clock text-6xl text-white"></i>
            </div>

            {{-- Floating Particles --}}
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute top-0 left-1/4 w-3 h-3 bg-cyan-400 rounded-full animate-bounce"
                     style="animation-delay: 0s; animation-duration: 2s;"></div>
                <div class="absolute top-1/4 right-1/4 w-2 h-2 bg-blue-400 rounded-full animate-bounce"
                     style="animation-delay: 0.5s; animation-duration: 2.5s;"></div>
                <div class="absolute bottom-1/4 left-1/3 w-2.5 h-2.5 bg-purple-400 rounded-full animate-bounce"
                     style="animation-delay: 1s; animation-duration: 2.2s;"></div>
            </div>
        </div>

        {{-- Title --}}
        <h1 class="text-5xl font-bold text-white mb-4 drop-shadow-lg">
            เร็วๆ นี้!
        </h1>

        {{-- Feature Name --}}
        <div class="inline-block px-6 py-3 bg-white/10 backdrop-blur-sm border border-white/30 rounded-xl mb-6">
            <h2 class="text-2xl font-bold text-white">
                {{ $feature }}
            </h2>
        </div>

        {{-- Description --}}
        <p class="text-xl text-white/80 mb-8 max-w-lg mx-auto leading-relaxed drop-shadow">
            ฟีเจอร์นี้กำลังอยู่ในระหว่างการพัฒนา<br>
            เราจะเปิดให้บริการโดยเร็วที่สุด
        </p>

        {{-- Progress Bar --}}
        <div class="mb-8 max-w-md mx-auto">
            <div class="bg-white/10 rounded-full h-3 overflow-hidden backdrop-blur-sm">
                <div class="bg-gradient-to-r from-cyan-400 via-blue-500 to-purple-600 h-full rounded-full animate-pulse"
                     style="width: 35%">
                </div>
            </div>
            <p class="text-white/60 text-sm mt-2">กำลังพัฒนา 35%</p>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('admin.ai-rental.dashboard') }}"
               class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold rounded-xl hover:scale-105 transition-transform shadow-lg">
                <i class="fas fa-arrow-left"></i>
                กลับสู่ Dashboard
            </a>

            <a href="{{ route('admin.ai-rental.setup-guide') }}"
               class="inline-flex items-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm border border-white/30 text-white font-bold rounded-xl hover:scale-105 hover:bg-white/20 transition-all">
                <i class="fas fa-book-open"></i>
                ดูคู่มือการใช้งาน
            </a>
        </div>

        {{-- Features List --}}
        <div class="mt-12 glass-fusion rounded-2xl p-8 border border-white/30 text-left">
            <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-list-check text-cyan-400"></i>
                ฟีเจอร์ที่กำลังพัฒนา
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach([
                    'Cloud GPU Providers Management',
                    'Hugging Face Integration',
                    'Model Deployment System',
                    'Cost Tracking & Analytics',
                    'Trending Models Dashboard',
                    'News & Updates System',
                    'Setup Wizard',
                    'API Documentation'
                ] as $upcoming)
                    <div class="flex items-center gap-3 text-white/80">
                        <i class="fas fa-circle-notch fa-spin text-cyan-400"></i>
                        <span>{{ $upcoming }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Contact Info --}}
        <div class="mt-8 p-4 bg-blue-500/10 border border-blue-500/30 rounded-xl">
            <p class="text-white/70 text-sm">
                <i class="fas fa-info-circle text-blue-400"></i>
                ต้องการข้อมูลเพิ่มเติม? ติดต่อทีมพัฒนาได้ที่
                <a href="mailto:support@thaiprompt.com" class="text-cyan-400 hover:underline">support@thaiprompt.com</a>
            </p>
        </div>
    </div>
</div>

@push('styles')
<style>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}
</style>
@endpush

@endsection
