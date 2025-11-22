@extends('layouts.admin-v3')

@section('title', 'จัดการ LINE Rich Menu')

@section('content')
<div class="container-fluid px-4 py-6" x-data="richMenuManager()">

    {{-- Header with LINE Green Theme --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-[#00B900] via-[#06C755] to-[#00E600] p-8 shadow-2xl">
        {{-- Pattern Background --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(255,255,255,0.3),transparent_50%)]"></div>
        </div>

        <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                    <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">จัดการ LINE Rich Menu</h1>
                    <p class="text-white/90">สร้างเมนูอินเทอร์แอกทีฟสำหรับ LINE Chat</p>
                </div>
            </div>
            <a href="{{ route('admin.line-bot.rich-menu.create') }}"
               class="px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/30 transition-all transform hover:scale-105 font-semibold">
                <i class="fas fa-plus mr-2"></i>สร้าง Rich Menu ใหม่
            </a>
        </div>
    </div>

    {{-- Success Alert --}}
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-[#00B900]/10 to-[#00E600]/10 border border-[#06C755]/30 backdrop-blur-sm animate-slide-up">
            <p class="text-[#00B900] dark:text-[#00E600] font-semibold">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </p>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Rich Menus --}}
        <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-blue-50/80 to-indigo-50/80 dark:from-blue-900/30 dark:to-indigo-900/30 p-6 rounded-2xl border border-white/20 dark:border-blue-700/50 hover:scale-105 transition-transform">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-blue-700 dark:text-blue-300">Rich Menus ทั้งหมด</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-th-large text-white text-xl"></i>
                </div>
            </div>
            <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $richMenus->total() ?? 0 }}, 1500, val => count = val)">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-1" x-text="Math.floor(count)">0</h2>
            </div>
            <p class="text-xs text-blue-600 dark:text-blue-400">เมนูที่สร้างแล้ว</p>
        </div>

        {{-- Active Menus --}}
        <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-[#00B900]/10 to-[#00E600]/10 dark:from-[#00B900]/20 dark:to-[#00E600]/20 p-6 rounded-2xl border border-white/20 dark:border-[#06C755]/50 hover:scale-105 transition-transform">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-[#00B900] dark:text-[#00E600]">Active เมนู</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
            </div>
            <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $richMenus->where('is_default', true)->count() ?? 1 }}, 1500, val => count = val)">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-1" x-text="Math.floor(count)">0</h2>
            </div>
            <p class="text-xs text-[#00B900] dark:text-[#00E600]">เมนูที่ใช้งานอยู่</p>
        </div>

        {{-- Templates --}}
        <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-purple-50/80 to-pink-50/80 dark:from-purple-900/30 dark:to-pink-900/30 p-6 rounded-2xl border border-white/20 dark:border-purple-700/50 hover:scale-105 transition-transform">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-purple-700 dark:text-purple-300">Templates</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-layer-group text-white text-xl"></i>
                </div>
            </div>
            <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-1">3</h2>
            <p class="text-xs text-purple-600 dark:text-purple-400">2x2, 3x3, Custom</p>
        </div>

        {{-- Total Clicks --}}
        <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-orange-50/80 to-red-50/80 dark:from-orange-900/30 dark:to-red-900/30 p-6 rounded-2xl border border-white/20 dark:border-orange-700/50 hover:scale-105 transition-transform">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-orange-700 dark:text-orange-300">Total Clicks</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-mouse-pointer text-white text-xl"></i>
                </div>
            </div>
            <div x-data="{ count: 0 }" x-init="animateCount(0, 1234, 1500, val => count = val)">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-1" x-text="Math.floor(count).toLocaleString()">0</h2>
            </div>
            <p class="text-xs text-orange-600 dark:text-orange-400">คลิกทั้งหมด</p>
        </div>
    </div>

    {{-- Rich Menu Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($richMenus as $menu)
            <div class="group glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg border border-white/20 dark:border-slate-700/50 overflow-hidden hover:scale-105 transition-all">
                {{-- Menu Preview Image --}}
                @if($menu->menu_image_url)
                    <div class="relative aspect-[2500/1686] bg-gray-900 overflow-hidden">
                        <img src="{{ $menu->menu_image_url }}" alt="{{ $menu->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        {{-- LINE Overlay --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="absolute bottom-4 left-4 right-4">
                                <div class="flex items-center gap-2 text-white text-sm">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                    </svg>
                                    <span class="font-semibold">{{ $menu->chat_bar_text }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="aspect-[2500/1686] bg-gradient-to-br from-[#00B900]/10 to-[#00E600]/10 flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-image text-6xl text-[#06C755]/30 mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">ไม่มีภาพ</p>
                        </div>
                    </div>
                @endif

                {{-- Menu Info --}}
                <div class="p-6">
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex-1">{{ $menu->name }}</h3>
                        @if($menu->is_default)
                            <span class="px-3 py-1 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-full text-xs font-bold flex items-center gap-1 shrink-0">
                                <i class="fas fa-check-circle"></i>Active
                            </span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400 rounded-full text-xs font-bold shrink-0">
                                Inactive
                            </span>
                        @endif
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                        <i class="fas fa-comment-alt text-[#06C755] mr-1"></i>
                        {{ $menu->chat_bar_text }}
                    </p>

                    {{-- Actions --}}
                    <div class="flex gap-2">
                        @if(!$menu->is_default)
                            <form method="POST" action="{{ route('admin.line-bot.rich-menu.set-default', $menu->id) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-lg transition-all text-sm font-semibold transform hover:scale-105">
                                    <i class="fas fa-star mr-1"></i>ตั้งเป็น Active
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.line-bot.rich-menu.edit', $menu->id) }}"
                           class="flex-1 px-4 py-2 bg-white dark:bg-slate-700 border-2 border-[#06C755] text-[#06C755] dark:text-[#00E600] rounded-xl hover:bg-[#06C755] hover:text-white dark:hover:bg-[#06C755] transition-all text-sm font-semibold text-center transform hover:scale-105">
                            <i class="fas fa-edit mr-1"></i>แก้ไข
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-12 text-center border border-white/20 dark:border-slate-700/50">
                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-[#00B900]/20 to-[#00E600]/20 flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-[#06C755]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มี Rich Menu</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">สร้าง Rich Menu แรกของคุณเพื่อเพิ่มประสบการณ์ใน LINE Chat</p>
                <a href="{{ route('admin.line-bot.rich-menu.create') }}"
                   class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-2xl transition-all font-bold transform hover:scale-105">
                    <i class="fas fa-plus"></i>
                    <span>สร้าง Rich Menu แรก</span>
                </a>
            </div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
/**
 * Rich Menu Manager Component - จัดการ LINE Rich Menus
 *
 * @returns {object} Alpine component
 */
function richMenuManager() {
    return {
        searchQuery: '',
        filterStatus: 'all',

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Rich Menu Manager initialized');
        }
    };
}

/**
 * Animate counter - นับเลขแบบ animation
 *
 * @param {number} start - เลขเริ่มต้น
 * @param {number} end - เลขสิ้นสุด
 * @param {number} duration - ระยะเวลา (ms)
 * @param {function} callback - ฟังก์ชัน callback
 */
function animateCount(start, end, duration, callback) {
    let startTime = null;

    function animation(currentTime) {
        if (startTime === null) startTime = currentTime;
        const timeElapsed = currentTime - startTime;
        const progress = Math.min(timeElapsed / duration, 1);

        const value = Math.floor(progress * (end - start) + start);
        callback(value);

        if (progress < 1) {
            requestAnimationFrame(animation);
        }
    }

    requestAnimationFrame(animation);
}

// Export global
window.richMenuManager = richMenuManager;
window.animateCount = animateCount;
</script>
@endpush
@endsection
