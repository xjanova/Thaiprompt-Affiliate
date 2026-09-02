@extends('layouts.user-v4')

@section('title', $mission->display_title)

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <div class="relative overflow-hidden bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 dark:from-cyan-800 dark:via-blue-800 dark:to-indigo-800 rounded-2xl shadow-2xl p-8">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite"><i class="fas fa-film"></i></div>
        </div>
        <div class="relative z-10"><div class="flex items-center gap-4">
            <div class="glass-fusion p-4 rounded-2xl"><i class="fas fa-film text-4xl text-white drop-shadow-lg"></i></div>
            <div><h1 class="text-4xl font-bold text-white drop-shadow-lg">Video Details</h1>
            <p class="text-white/80 text-lg mt-1">รายละเอียดวิดีโอ</p></div>
        </div></div>
    </div>
    <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg overflow-hidden backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
        {{-- Thumbnail --}}
        <div class="relative">
            @if($mission->thumbnail_url)
            <img src="{{ $mission->thumbnail_url }}" alt="{{ $mission->display_title }}" class="w-full h-64 object-cover">
            @else
            <div class="w-full h-64 bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center">
                <span class="text-8xl">{{ $mission->icon ?? '🎬' }}</span>
            </div>
            @endif

            {{-- Badges --}}
            <div class="absolute top-4 left-4 flex flex-wrap gap-2">
                @if($mission->is_featured)
                <span class="px-3 py-1 bg-yellow-500 text-white text-sm font-bold rounded-full">
                    ⭐ แนะนำ
                </span>
                @endif
                @if($mission->is_premium)
                <span class="px-3 py-1 bg-purple-600 text-white text-sm font-bold rounded-full">
                    👑 Premium
                </span>
                @endif
            </div>

            {{-- Duration Badge --}}
            <div class="absolute bottom-4 right-4 px-3 py-1 bg-black/70 text-white rounded-lg text-sm">
                ⏱️ {{ $mission->required_watch_time_formatted }}
            </div>
        </div>

        {{-- Content --}}
        <div class="p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                {{ $mission->icon ?? '🎬' }} {{ $mission->display_title }}
            </h1>

            @if($mission->display_description)
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                {{ $mission->display_description }}
            </p>
            @endif

            {{-- Rewards --}}
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 mb-6">
                <h3 class="font-semibold text-green-800 dark:text-green-200 mb-2 flex items-center gap-2">
                    🎁 รางวัลที่จะได้รับ
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @if($rewards['money'] > 0)
                    <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
                        <span class="text-lg">💰</span>
                        <span class="font-bold">฿{{ number_format($rewards['money'], 2) }}</span>
                    </div>
                    @endif
                    @if($rewards['coins'] > 0)
                    <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
                        <span class="text-lg">🪙</span>
                        <span class="font-bold">{{ number_format($rewards['coins']) }} Coins</span>
                    </div>
                    @endif
                    @if($rewards['points'] > 0)
                    <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
                        <span class="text-lg">⭐</span>
                        <span class="font-bold">{{ number_format($rewards['points']) }} แต้ม</span>
                    </div>
                    @endif
                    @if($rewards['exp'] > 0)
                    <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
                        <span class="text-lg">📈</span>
                        <span class="font-bold">{{ number_format($rewards['exp']) }} EXP</span>
                    </div>
                    @endif
                </div>

                @if($rankLimit->reward_multiplier > 1)
                <p class="text-sm text-green-600 dark:text-green-400 mt-3 pt-3 border-t border-green-200 dark:border-green-700">
                    ✨ รวมโบนัส Rank x{{ number_format($rankLimit->reward_multiplier, 2) }} แล้ว
                </p>
                @endif
            </div>

            {{-- Mission Info --}}
            <div class="grid sm:grid-cols-2 gap-4 mb-6">
                <div class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                    <span class="text-xl">⏰</span>
                    <div>
                        <p class="text-xs text-gray-500">ความถี่</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            @switch($mission->frequency)
                                @case('once') ทำได้ครั้งเดียว @break
                                @case('daily') วันละครั้ง @break
                                @case('weekly') สัปดาห์ละครั้ง @break
                                @case('monthly') เดือนละครั้ง @break
                                @case('unlimited') ไม่จำกัด (cooldown {{ $mission->cooldown_minutes }} นาที) @break
                            @endswitch
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                    <span class="text-xl">👥</span>
                    <div>
                        <p class="text-xs text-gray-500">ทำสำเร็จแล้ว</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ number_format($mission->completion_count) }} ครั้ง</p>
                    </div>
                </div>

                @if($mission->minRank)
                <div class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                    <span class="text-xl">🏆</span>
                    <div>
                        <p class="text-xs text-gray-500">Rank ขั้นต่ำ</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $mission->minRank->display_name }}</p>
                    </div>
                </div>
                @endif

                <div class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                    <span class="text-xl">🎬</span>
                    <div>
                        <p class="text-xs text-gray-500">ประเภท</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ ucfirst($mission->video_type) }}</p>
                    </div>
                </div>
            </div>

            {{-- Eligibility Check --}}
            @if(!$eligibility['eligible'])
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-6">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">❌</span>
                    <div>
                        <p class="font-semibold text-red-800 dark:text-red-200">ไม่สามารถทำภารกิจนี้ได้</p>
                        <p class="text-sm text-red-600 dark:text-red-300">{{ $eligibility['reason'] }}</p>
                    </div>
                </div>
            </div>
            @elseif(!$canDoNow['can'])
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 mb-6">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⏳</span>
                    <div>
                        <p class="font-semibold text-yellow-800 dark:text-yellow-200">{{ $canDoNow['reason'] }}</p>
                        @if($canDoNow['next_available_at'])
                        <p class="text-sm text-yellow-600 dark:text-yellow-300">
                            ทำได้อีกครั้งใน: {{ $canDoNow['next_available_at']->diffForHumans() }}
                        </p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Action Button --}}
            <div class="flex flex-col sm:flex-row gap-3">
                @if($eligibility['eligible'] && $canDoNow['can'])
                <a href="{{ route('user.video-missions.watch', $mission) }}"
                   class="flex-1 px-6 py-4 bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-700 hover:to-purple-700 text-white rounded-xl font-medium text-center transition-all shadow-lg hover:shadow-pink-500/25 flex items-center justify-center gap-2">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    เริ่มดูวิดีโอ
                </a>
                @else
                <button disabled
                        class="flex-1 px-6 py-4 bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-xl font-medium text-center cursor-not-allowed">
                    ไม่สามารถทำภารกิจได้
                </button>
                @endif

                <a href="{{ route('user.video-missions.index') }}"
                   class="px-6 py-4 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium text-center transition-all">
                    ดูภารกิจอื่น
                </a>
            </div>
        </div>
    </div>

    {{-- History --}}
    @if($userCompletions->count() > 0)
    <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 mt-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
        <h2 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            📜 ประวัติการทำภารกิจนี้
        </h2>
        <div class="space-y-3">
            @foreach($userCompletions as $completion)
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="flex items-center gap-3">
                    <span class="text-xl">{{ $completion->status_icon }}</span>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $completion->status_label }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $completion->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                </div>
                @if($completion->reward_given)
                <span class="text-sm text-green-600 dark:text-green-400 font-medium">
                    +{{ $completion->reward_summary }}
                </span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
// ป้องกันการใช้ back button เข้าถึงหน้านี้หลังจากไม่มีสิทธิ์แล้ว
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        // เมื่อกลับมาที่หน้านี้ (เช่น กด back) ให้ reload เพื่อตรวจสอบสิทธิ์ใหม่
        // Server จะ redirect ถ้าไม่มีสิทธิ์
        location.reload();
    }
});

// ป้องกัน bfcache (back-forward cache)
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        // Page was restored from bfcache - reload เพื่อตรวจสอบสิทธิ์
        location.reload();
    }
});
</script>
@endpush
@endsection
