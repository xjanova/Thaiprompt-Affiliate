@extends('layouts.user-v4')

@section('title', 'ดูคลิป: ' . $mission->display_title)

@push('head')
<div class="space-y-6 pb-20 lg:pb-6">
    <div class="relative overflow-hidden bg-gradient-to-r from-red-600 via-pink-600 to-rose-600 dark:from-red-800 dark:via-pink-800 dark:to-rose-800 rounded-2xl shadow-2xl p-8">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite"><i class="fas fa-play-circle"></i></div>
        </div>
        <div class="relative z-10"><div class="flex items-center gap-4">
            <div class="glass-fusion p-4 rounded-2xl"><i class="fas fa-play-circle text-4xl text-white drop-shadow-lg"></i></div>
            <div><h1 class="text-4xl font-bold text-white drop-shadow-lg">Watch Video</h1>
            <p class="text-white/80 text-lg mt-1">ดูวิดีโอและรับรางวัล</p></div>
        </div></div>
    </div>
    }
    .confetti {
        position: fixed;
        width: 10px;
        height: 10px;
        top: -10px;
        animation: confetti-fall 3s linear forwards;
    }
    .confetti:nth-child(odd) { border-radius: 50%; }
    .confetti:nth-child(even) { border-radius: 2px; }

    /* Reward Popup Animation */
    @keyframes reward-bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0) scale(1); }
        40% { transform: translateY(-30px) scale(1.1); }
        60% { transform: translateY(-15px) scale(1.05); }
    }
    .animate-reward-bounce {
        animation: reward-bounce 1s ease-out;
    }

    /* Glow Pulse */
    @keyframes glow-pulse {
        0%, 100% { box-shadow: 0 0 20px rgba(236, 72, 153, 0.5); }
        50% { box-shadow: 0 0 40px rgba(236, 72, 153, 0.8), 0 0 60px rgba(168, 85, 247, 0.6); }
    }
    .animate-glow-pulse {
        animation: glow-pulse 2s ease-in-out infinite;
    }

    /* Coin Rain */
    @keyframes coin-rain {
        0% { transform: translateY(-20px) rotate(0deg); opacity: 0; }
        10% { opacity: 1; }
        100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
    }
    .coin-particle {
        animation: coin-rain 3s ease-in forwards;
    }

    /* Shine Effect */
    @keyframes shine {
        0% { background-position: -200% center; }
        100% { background-position: 200% center; }
    }
    .animate-shine {
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        background-size: 200% 100%;
        animation: shine 2s infinite;
    }

    /* Progress Fill Animation */
    @keyframes progress-fill {
        0% { stroke-dashoffset: 565; }
    }

    /* Sparkle */
    @keyframes sparkle {
        0%, 100% { opacity: 0; transform: scale(0) rotate(0deg); }
        50% { opacity: 1; transform: scale(1) rotate(180deg); }
    }
    .sparkle {
        animation: sparkle 1.5s ease-in-out infinite;
    }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6"
     x-data="videoWatcher({{ json_encode([
         'completionId' => $completion->id,
         'sessionToken' => $completion->session_token,
         'requiredSeconds' => $mission->required_watch_seconds,
         'videoType' => $mission->video_type,
         'videoId' => $mission->video_id,
         'embedUrl' => $mission->embed_url,
         'antiCheat' => $antiCheatSettings,
         'missionTitle' => $mission->display_title,
         'missionIcon' => $mission->icon ?? '🎬',
         'rewardMoney' => $mission->reward_money,
         'rewardCoins' => $mission->reward_coins,
         'rewardExp' => $mission->reward_exp,
         'affiliateCode' => auth()->user()->affiliate_code ?? auth()->user()->id,
         'userName' => auth()->user()->name,
     ]) }})"
     x-init="init()"
     @beforeunload.window="handleBeforeUnload($event)">

    {{-- Confetti Container --}}
    <div id="confetti-container" class="fixed inset-0 pointer-events-none z-50"></div>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <a href="{{ route('user.video-missions.show', $mission) }}"
           class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับ
        </a>
        <div class="flex items-center gap-3">
            {{-- Status Badge --}}
            <span x-show="status === 'watching'"
                  class="px-3 py-1 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 rounded-full text-sm font-medium animate-pulse">
                ● กำลังดู
            </span>
            <span x-show="status === 'paused'"
                  class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300 rounded-full text-sm font-medium">
                ⏸ หยุดชั่วคราว
            </span>
            <span x-show="status === 'completed'"
                  class="px-3 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 rounded-full text-sm font-medium">
                ✓ ดูครบแล้ว
            </span>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Main Video Section --}}
        <div class="lg:col-span-2">
            {{-- Video Player Container --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg overflow-hidden backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                {{-- Video Title --}}
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $mission->icon ?? '🎬' }} {{ $mission->display_title }}
                    </h1>
                </div>

                {{-- Video Player --}}
                <div class="relative bg-black aspect-video"
                     @focus.window="handleFocus()"
                     @blur.window="handleBlur()">

                    {{-- YouTube Player --}}
                    @if($mission->video_type === 'youtube')
                    <div id="youtube-player" class="w-full h-full"></div>
                    @elseif($mission->video_type === 'vimeo')
                    {{-- Vimeo Player --}}
                    <iframe id="vimeo-player"
                            src="{{ $mission->embed_url }}"
                            class="w-full h-full"
                            frameborder="0"
                            allow="autoplay; fullscreen"
                            allowfullscreen></iframe>
                    @else
                    {{-- Direct Video --}}
                    <video id="direct-player"
                           class="w-full h-full"
                           @play="handlePlay()"
                           @pause="handlePause()"
                           @seeked="handleSeek($event)"
                           @timeupdate="handleTimeUpdate($event)">
                        <source src="{{ $mission->video_url }}" type="video/mp4">
                        เบราว์เซอร์ของคุณไม่รองรับการเล่นวิดีโอ
                    </video>
                    @endif

                    {{-- Anti-Cheat Overlay --}}
                    <div x-show="showInteractionPrompt"
                         x-transition
                         class="absolute inset-0 bg-black/80 flex items-center justify-center z-10">
                        <div class="text-center">
                            <p class="text-white text-lg mb-4">กรุณาคลิกเพื่อยืนยันว่าคุณกำลังดูอยู่</p>
                            <button @click="confirmInteraction()"
                                    class="px-6 py-3 bg-pink-600 hover:bg-pink-700 text-white rounded-xl font-medium">
                                ฉันกำลังดูอยู่
                            </button>
                        </div>
                    </div>

                    {{-- Warning Overlay (Tab Switch) --}}
                    <div x-show="showWarning"
                         x-transition
                         class="absolute inset-0 bg-red-900/90 flex items-center justify-center z-20">
                        <div class="text-center">
                            <span class="text-5xl mb-4 block">⚠️</span>
                            <p class="text-white text-lg mb-2">คุณออกจากหน้านี้!</p>
                            <p class="text-red-200 text-sm mb-4">
                                สลับแท็บได้อีก <span x-text="remainingTabSwitches"></span> ครั้ง
                            </p>
                            <button @click="dismissWarning()"
                                    class="px-6 py-3 bg-white text-red-600 rounded-xl font-medium">
                                กลับมาดูต่อ
                            </button>
                        </div>
                    </div>

                    {{-- DevTools Detection Warning Overlay --}}
                    <div x-show="showDevToolsWarning"
                         x-transition
                         class="absolute inset-0 bg-black/95 flex items-center justify-center z-30">
                        <div class="text-center max-w-md mx-auto px-4">
                            <span class="text-7xl mb-6 block animate-pulse">🛑</span>
                            <h2 class="text-2xl font-bold text-red-500 mb-4">ตรวจพบการโกง!</h2>
                            <p class="text-white text-lg mb-2">
                                ระบบตรวจพบการพยายามเปิด DevTools
                            </p>
                            <p class="text-gray-400 text-sm mb-6">
                                การใช้เครื่องมือนักพัฒนา (F12) ขณะทำภารกิจถือเป็นการโกง<br>
                                ภารกิจนี้ถูกยกเลิกแล้ว
                            </p>
                            <button @click="dismissDevToolsWarning()"
                                    class="px-8 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium transition-all">
                                กลับไปหน้าภารกิจ
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Linear Progress Bar --}}
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            ความคืบหน้า
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <span x-text="formatTime(watchedSeconds)">0:00</span> / {{ $mission->required_watch_time_formatted }}
                        </span>
                    </div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden relative">
                        <div class="h-full bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 rounded-full transition-all duration-300 relative overflow-hidden"
                             :style="{ width: progressPercentage + '%' }">
                            <div class="absolute inset-0 animate-shine"></div>
                        </div>
                        {{-- Milestone markers --}}
                        <div class="absolute inset-0 flex justify-between px-1 items-center pointer-events-none">
                            <div class="w-1 h-1 rounded-full bg-white/50"></div>
                            <div class="w-1 h-1 rounded-full bg-white/50"></div>
                            <div class="w-1 h-1 rounded-full bg-white/50"></div>
                            <div class="w-1 h-1 rounded-full bg-white/50"></div>
                            <div class="w-1 h-1 rounded-full bg-white/50"></div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row gap-3">
                        {{-- Complete Button --}}
                        <button @click="completeAndClaim()"
                                :disabled="!canComplete || isLoading"
                                :class="{
                                    'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 animate-glow-pulse': canComplete && !isLoading,
                                    'bg-gray-300 dark:bg-gray-700 cursor-not-allowed': !canComplete || isLoading
                                }"
                                class="flex-1 px-6 py-4 text-white rounded-xl font-bold text-lg transition-all flex items-center justify-center gap-2 relative overflow-hidden">
                            <template x-if="isLoading">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-show="!isLoading && canComplete">🎁 รับรางวัลเลย!</span>
                            <span x-show="!isLoading && !canComplete">ดูให้ครบก่อนนะ...</span>
                        </button>

                        {{-- Cancel Button --}}
                        <button @click="cancelMission()"
                                :disabled="isLoading"
                                class="px-6 py-4 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all">
                            ยกเลิก
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar - Circular Progress --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Circular Progress Gauge --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 text-center">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-4">เป้าหมาย</h3>

                {{-- SVG Circular Progress --}}
                <div class="relative inline-block">
                    <svg class="progress-ring w-48 h-48" viewBox="0 0 200 200">
                        {{-- Background Circle --}}
                        <circle
                            class="text-gray-200 dark:text-gray-700"
                            stroke="currentColor"
                            stroke-width="12"
                            fill="transparent"
                            r="90"
                            cx="100"
                            cy="100"/>
                        {{-- Progress Circle --}}
                        <circle
                            class="progress-ring__circle text-transparent"
                            stroke="url(#gradient)"
                            stroke-width="12"
                            stroke-linecap="round"
                            fill="transparent"
                            r="90"
                            cx="100"
                            cy="100"
                            :stroke-dasharray="565.48"
                            :stroke-dashoffset="565.48 - (progressPercentage / 100 * 565.48)"/>
                        {{-- Gradient Definition --}}
                        <defs>
                            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#ec4899"/>
                                <stop offset="50%" stop-color="#a855f7"/>
                                <stop offset="100%" stop-color="#6366f1"/>
                            </linearGradient>
                        </defs>
                    </svg>

                    {{-- Center Content --}}
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-bold bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-transparent"
                              x-text="Math.round(progressPercentage) + '%'">0%</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="formatTime(watchedSeconds)">0:00</span>
                    </div>

                    {{-- Sparkle Effects when almost complete --}}
                    <template x-if="progressPercentage >= 80">
                        <div class="absolute inset-0 pointer-events-none">
                            <span class="absolute top-2 right-8 text-yellow-400 sparkle" style="animation-delay: 0s;">✨</span>
                            <span class="absolute top-8 left-2 text-pink-400 sparkle" style="animation-delay: 0.5s;">✨</span>
                            <span class="absolute bottom-8 right-2 text-purple-400 sparkle" style="animation-delay: 1s;">✨</span>
                        </div>
                    </template>
                </div>

                {{-- Target Info --}}
                <div class="mt-4 space-y-2">
                    <p class="text-gray-600 dark:text-gray-400">
                        ต้องดูให้ครบ <span class="font-bold text-gray-900 dark:text-white">{{ $mission->required_watch_time_formatted }}</span>
                    </p>
                    <p class="text-sm" :class="canComplete ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                        <template x-if="!canComplete">
                            <span>เหลืออีก <span x-text="formatTime(requiredSeconds - watchedSeconds)"></span></span>
                        </template>
                        <template x-if="canComplete">
                            <span class="font-bold">✓ ดูครบแล้ว! กดรับรางวัลได้เลย</span>
                        </template>
                    </p>
                </div>
            </div>

            {{-- Reward Preview --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-4">🎁 รางวัลที่จะได้รับ</h3>
                <div class="space-y-3">
                    @if($mission->reward_money > 0)
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/30 rounded-xl">
                        <span class="text-green-800 dark:text-green-200">💵 เงิน</span>
                        <span class="font-bold text-green-600 dark:text-green-400">฿{{ number_format($mission->reward_money * ($rankLimit->reward_multiplier ?? 1), 2) }}</span>
                    </div>
                    @endif
                    @if($mission->reward_coins > 0)
                    <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-xl">
                        <span class="text-yellow-800 dark:text-yellow-200">🪙 Coins</span>
                        <span class="font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($mission->reward_coins * ($rankLimit->reward_multiplier ?? 1)) }}</span>
                    </div>
                    @endif
                    @if($mission->reward_exp > 0)
                    <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/30 rounded-xl">
                        <span class="text-purple-800 dark:text-purple-200">⭐ EXP</span>
                        <span class="font-bold text-purple-600 dark:text-purple-400">+{{ number_format($mission->reward_exp) }}</span>
                    </div>
                    @endif
                </div>

                @if(($rankLimit->reward_multiplier ?? 1) > 1)
                <div class="mt-4 p-3 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 rounded-xl border border-yellow-500/30">
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 text-center">
                        ⭐ โบนัส Rank <span class="font-bold">x{{ number_format($rankLimit->reward_multiplier, 2) }}</span>
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============================================== --}}
    {{-- SUCCESS MODAL with Share Feature --}}
    {{-- ============================================== --}}
    <div x-show="showSuccessModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
         style="display: none;">

        <div x-show="showSuccessModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-75"
             x-transition:enter-end="opacity-100 scale-100"
             class="tp-card rounded-3xl max-w-lg w-full overflow-hidden">

            {{-- Celebration Header --}}
            <div class="relative bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 p-8 text-center overflow-hidden">
                {{-- Animated Background --}}
                <div class="absolute inset-0 opacity-30">
                    <div class="absolute top-0 left-0 w-20 h-20 bg-white/20 rounded-full blur-xl animate-pulse"></div>
                    <div class="absolute bottom-0 right-0 w-32 h-32 bg-white/20 rounded-full blur-xl animate-pulse" style="animation-delay: 0.5s;"></div>
                </div>

                {{-- Trophy Icon --}}
                <div class="relative animate-reward-bounce">
                    <span class="text-8xl filter drop-shadow-lg">🏆</span>
                </div>

                <h2 class="text-3xl font-bold text-white mt-4 relative">
                    ยินดีด้วย! 🎉
                </h2>
                <p class="text-white/80 mt-2 relative">ทำภารกิจสำเร็จแล้ว!</p>
            </div>

            {{-- Rewards Section --}}
            <div class="p-6">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl p-6 mb-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">รางวัลที่ได้รับ</p>
                    <p class="text-3xl font-bold text-center bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent"
                       x-text="rewardSummary">
                    </p>
                </div>

                {{-- Share Section --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <p class="text-center text-gray-700 dark:text-gray-300 font-medium mb-4">
                        📢 อวดให้เพื่อนดู & ชวนมาทำด้วยกัน!
                    </p>

                    {{-- Share Preview --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400" x-text="shareText"></p>
                    </div>

                    {{-- Share Buttons --}}
                    <div class="grid grid-cols-4 gap-3">
                        {{-- Facebook --}}
                        <button @click="shareToFacebook()"
                                class="flex flex-col items-center justify-center p-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all hover:scale-105">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <span class="text-xs mt-1">FB</span>
                        </button>

                        {{-- LINE --}}
                        <button @click="shareToLine()"
                                class="flex flex-col items-center justify-center p-3 bg-green-500 hover:bg-green-600 text-white rounded-xl transition-all hover:scale-105">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.349 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                            </svg>
                            <span class="text-xs mt-1">LINE</span>
                        </button>

                        {{-- Twitter/X --}}
                        <button @click="shareToTwitter()"
                                class="flex flex-col items-center justify-center p-3 bg-black hover:bg-gray-800 text-white rounded-xl transition-all hover:scale-105">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            <span class="text-xs mt-1">X</span>
                        </button>

                        {{-- Copy Link --}}
                        <button @click="copyAffiliateLink()"
                                class="flex flex-col items-center justify-center p-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all hover:scale-105 relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-xs mt-1" x-text="linkCopied ? '✓' : 'คัดลอก'">คัดลอก</span>
                        </button>
                    </div>

                    {{-- Affiliate Link Preview --}}
                    <div class="mt-4 p-3 bg-pink-50 dark:bg-pink-900/20 rounded-xl border border-pink-200 dark:border-pink-800">
                        <p class="text-xs text-pink-600 dark:text-pink-400 mb-1">🔗 ลิงก์ Affiliate ของคุณ:</p>
                        <p class="text-sm text-pink-800 dark:text-pink-200 font-mono break-all" x-text="affiliateLink"></p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-3 mt-6">
                    <a href="{{ route('user.video-missions.index') }}"
                       class="flex-1 px-4 py-3 bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-700 hover:to-purple-700 text-white rounded-xl font-medium text-center transition-all">
                        🎬 ดูภารกิจอื่น
                    </a>
                    <button @click="showSuccessModal = false"
                            class="px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-xl font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-all">
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
{{-- YouTube IFrame API --}}
@if($mission->video_type === 'youtube')
<script src="https://www.youtube.com/iframe_api"></script>
@endif

<script>
function videoWatcher(config) {
    return {
        // Config
        completionId: config.completionId,
        sessionToken: config.sessionToken,
        requiredSeconds: config.requiredSeconds,
        videoType: config.videoType,
        videoId: config.videoId,
        embedUrl: config.embedUrl,
        antiCheat: config.antiCheat,
        missionTitle: config.missionTitle,
        missionIcon: config.missionIcon,
        affiliateCode: config.affiliateCode,
        userName: config.userName,

        // State
        status: 'watching',
        watchedSeconds: 0,
        currentPosition: 0,
        progressPercentage: 0,
        canComplete: false,
        isLoading: false,

        // Anti-cheat
        tabSwitchCount: 0,
        remainingTabSwitches: config.antiCheat.max_tab_switches,
        showWarning: false,
        showInteractionPrompt: false,
        lastInteractionTime: Date.now(),

        // Success & Share
        showSuccessModal: false,
        rewardSummary: '',
        earnedRewards: {},
        linkCopied: false,

        // Player instances
        youtubePlayer: null,
        heartbeatInterval: null,
        interactionCheckInterval: null,

        // Computed
        get affiliateLink() {
            return `${window.location.origin}/ref/${this.affiliateCode}?from=video-mission`;
        },

        get shareText() {
            return `🎬 ฉันเพิ่งทำภารกิจ "${this.missionTitle}" สำเร็จ และได้รับ ${this.rewardSummary}! มาทำด้วยกันเถอะ 👇`;
        },

        // DevTools detection state
        devToolsOpen: false,
        devToolsWarningCount: 0,
        maxDevToolsWarnings: 3,
        showDevToolsWarning: false,

        init() {
            // Initialize video player
            if (this.videoType === 'youtube') {
                this.initYouTubePlayer();
            } else if (this.videoType === 'direct') {
                this.initDirectPlayer();
            }

            // Start heartbeat
            this.startHeartbeat();

            // Tab visibility detection
            if (this.antiCheat.detect_tab_switch) {
                document.addEventListener('visibilitychange', () => this.handleVisibilityChange());
            }

            // Interaction check
            if (this.antiCheat.require_interaction) {
                this.startInteractionCheck();
            }

            // ==================== ANTI-CHEAT SYSTEMS ====================

            // 1. Prevent right click
            document.addEventListener('contextmenu', (e) => e.preventDefault());

            // 2. Prevent keyboard shortcuts (F12, Ctrl+Shift+I, Ctrl+U, etc.)
            this.initKeyboardProtection();

            // 3. DevTools detection
            this.initDevToolsDetection();

            // 4. Console protection
            this.initConsoleProtection();

            // 5. Prevent text selection
            document.addEventListener('selectstart', (e) => e.preventDefault());

            // 6. Prevent drag
            document.addEventListener('dragstart', (e) => e.preventDefault());
        },

        // ==================== KEYBOARD PROTECTION ====================
        initKeyboardProtection() {
            document.addEventListener('keydown', (e) => {
                // F12
                if (e.key === 'F12' || e.keyCode === 123) {
                    e.preventDefault();
                    this.handleDevToolsAttempt('F12');
                    return false;
                }

                // Ctrl+Shift+I (DevTools)
                if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i' || e.keyCode === 73)) {
                    e.preventDefault();
                    this.handleDevToolsAttempt('Ctrl+Shift+I');
                    return false;
                }

                // Ctrl+Shift+J (Console)
                if (e.ctrlKey && e.shiftKey && (e.key === 'J' || e.key === 'j' || e.keyCode === 74)) {
                    e.preventDefault();
                    this.handleDevToolsAttempt('Ctrl+Shift+J');
                    return false;
                }

                // Ctrl+Shift+C (Element inspector)
                if (e.ctrlKey && e.shiftKey && (e.key === 'C' || e.key === 'c' || e.keyCode === 67)) {
                    e.preventDefault();
                    this.handleDevToolsAttempt('Ctrl+Shift+C');
                    return false;
                }

                // Ctrl+U (View source)
                if (e.ctrlKey && (e.key === 'U' || e.key === 'u' || e.keyCode === 85)) {
                    e.preventDefault();
                    this.handleDevToolsAttempt('Ctrl+U');
                    return false;
                }

                // Ctrl+S (Save)
                if (e.ctrlKey && (e.key === 'S' || e.key === 's' || e.keyCode === 83)) {
                    e.preventDefault();
                    return false;
                }

                // Ctrl+P (Print) - can be used to see source
                if (e.ctrlKey && (e.key === 'P' || e.key === 'p' || e.keyCode === 80)) {
                    e.preventDefault();
                    return false;
                }
            });
        },

        // ==================== DEVTOOLS DETECTION ====================
        initDevToolsDetection() {
            const self = this;

            // Method 1: Window size detection (when DevTools is docked)
            const checkWindowSize = () => {
                const widthThreshold = window.outerWidth - window.innerWidth > 160;
                const heightThreshold = window.outerHeight - window.innerHeight > 160;

                if (widthThreshold || heightThreshold) {
                    if (!self.devToolsOpen) {
                        self.devToolsOpen = true;
                        self.handleDevToolsDetected('window_size');
                    }
                } else {
                    self.devToolsOpen = false;
                }
            };

            // Method 2: Debugger detection via timing
            const checkDebugger = () => {
                const start = performance.now();
                debugger;
                const end = performance.now();

                // If debugger is open, this will take significantly longer
                if (end - start > 100) {
                    self.handleDevToolsDetected('debugger');
                }
            };

            // Method 3: Console.log detection
            const checkConsole = () => {
                const element = new Image();
                Object.defineProperty(element, 'id', {
                    get: function() {
                        self.handleDevToolsDetected('console_access');
                        return 'devtools-detect';
                    }
                });
                // Only log if we need to check - disabled to avoid spam
                // console.log('%c', element);
            };

            // Method 4: toString detection
            const checkToString = () => {
                const obj = {};
                let devtoolsOpen = false;

                Object.defineProperty(obj, 'a', {
                    get: function() {
                        devtoolsOpen = true;
                        return 'devtools';
                    }
                });

                // This only triggers when DevTools is open and inspecting
                setInterval(() => {
                    devtoolsOpen = false;
                    console.log(obj);
                    console.clear();
                    if (devtoolsOpen) {
                        self.handleDevToolsDetected('toString');
                    }
                }, 5000);
            };

            // Run checks periodically
            setInterval(checkWindowSize, 1000);

            // Resize listener
            window.addEventListener('resize', checkWindowSize);

            // Initial check
            checkWindowSize();
        },

        // ==================== CONSOLE PROTECTION ====================
        initConsoleProtection() {
            // Override console methods to prevent easy debugging
            const noop = () => {};

            // Store original console methods for debugging purposes
            window._originalConsole = {
                log: console.log,
                warn: console.warn,
                error: console.error,
                info: console.info,
                debug: console.debug,
            };

            // Comment out the override in development - enable in production
            // console.log = noop;
            // console.warn = noop;
            // console.error = noop;
            // console.info = noop;
            // console.debug = noop;
            // console.clear = noop;
        },

        handleDevToolsAttempt(method) {
            this.devToolsWarningCount++;
            this.sendEvent('devtools_attempt', { method: method, count: this.devToolsWarningCount });

            if (this.devToolsWarningCount >= this.maxDevToolsWarnings) {
                this.handleDevToolsDetected(method);
            } else {
                // แสดงคำเตือน
                this.showDevToolsWarningMessage();
            }
        },

        handleDevToolsDetected(method) {
            this.devToolsOpen = true;
            this.sendEvent('devtools_detected', { method: method });

            // หยุดวิดีโอ
            this.pauseVideo();

            // แสดงหน้าเตือน
            this.showDevToolsWarning = true;
        },

        showDevToolsWarningMessage() {
            // แสดง toast หรือ alert
            const remaining = this.maxDevToolsWarnings - this.devToolsWarningCount;
            alert(`⚠️ การพยายามเปิด DevTools ถูกตรวจพบ!\n\nคุณสามารถพยายามได้อีก ${remaining} ครั้ง\nหากพยายามต่อ ภารกิจจะถูกยกเลิก`);
        },

        dismissDevToolsWarning() {
            this.showDevToolsWarning = false;
            // ไม่ให้ดูต่อ - ต้องรีโหลดหน้า
            window.location.href = '{{ route("user.video-missions.index") }}';
        },

        initYouTubePlayer() {
            const self = this;
            window.onYouTubeIframeAPIReady = function() {
                self.youtubePlayer = new YT.Player('youtube-player', {
                    videoId: self.videoId,
                    playerVars: {
                        autoplay: 1,
                        controls: 0,
                        disablekb: 1,
                        fs: 0,
                        modestbranding: 1,
                        rel: 0,
                        showinfo: 0,
                    },
                    events: {
                        onStateChange: (event) => self.onYouTubeStateChange(event),
                        onReady: (event) => event.target.playVideo(),
                        onPlaybackRateChange: (event) => self.onPlaybackRateChange(event),
                    }
                });
            };

            if (window.YT && window.YT.Player) {
                window.onYouTubeIframeAPIReady();
            }
        },

        // ตรวจจับการเปลี่ยนความเร็ววิดีโอ
        onPlaybackRateChange(event) {
            const newSpeed = event.data;

            // ถ้าความเร็วไม่ใช่ 1x ถือว่าพยายามโกง
            if (newSpeed !== 1) {
                this.sendEvent('speed_change', { speed: newSpeed });

                // บังคับให้กลับไปความเร็วปกติ
                if (this.youtubePlayer) {
                    this.youtubePlayer.setPlaybackRate(1);
                }

                // แจ้งเตือน
                alert('⚠️ ไม่อนุญาตให้เปลี่ยนความเร็ววิดีโอ\nความเร็วถูกตั้งกลับเป็น 1x');
            }
        },

        initDirectPlayer() {
            const player = document.getElementById('direct-player');
            if (player) {
                player.play().catch(e => console.log('Autoplay blocked:', e));
            }
        },

        onYouTubeStateChange(event) {
            if (event.data === YT.PlayerState.PLAYING) {
                this.status = 'watching';
                this.sendEvent('resume');
            } else if (event.data === YT.PlayerState.PAUSED) {
                this.status = 'paused';
                this.sendEvent('pause');
            }
        },

        startHeartbeat() {
            this.heartbeatInterval = setInterval(() => {
                this.sendHeartbeat();
            }, this.antiCheat.heartbeat_interval * 1000);
        },

        async sendHeartbeat() {
            if (this.videoType === 'youtube' && this.youtubePlayer) {
                this.currentPosition = Math.floor(this.youtubePlayer.getCurrentTime() || 0);
            } else if (this.videoType === 'direct') {
                const player = document.getElementById('direct-player');
                this.currentPosition = Math.floor(player?.currentTime || 0);
            }

            try {
                const response = await fetch(`/user/video-missions/completion/${this.completionId}/heartbeat`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        position: this.currentPosition,
                        speed: 1.0,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.watchedSeconds = data.watched_seconds;
                    this.progressPercentage = Math.min(100, (this.watchedSeconds / this.requiredSeconds) * 100);
                    this.canComplete = data.can_complete;
                } else if (data.action === 'reload') {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Heartbeat error:', error);
            }
        },

        async sendEvent(eventType, extraData = {}) {
            try {
                const response = await fetch(`/user/video-missions/completion/${this.completionId}/event`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        event: eventType,
                        ...extraData,
                    }),
                });

                const data = await response.json();

                // ตรวจสอบว่าถูกแบนหรือไม่
                if (data.banned) {
                    // แสดงข้อความและ redirect ไปหน้าแบน
                    alert('⛔ บัญชีของคุณถูกระงับจากระบบภารกิจดูคลิป\n\n' + data.message);
                    window.location.href = '{{ route("user.video-missions.index") }}';
                    return;
                }

                // ถ้ามีคำเตือน (ใกล้ถึง threshold)
                if (data.warning && eventType === 'devtools_detected') {
                    console.log('Warning:', data.message);
                }

            } catch (error) {
                console.error('Event error:', error);
            }
        },

        handleVisibilityChange() {
            if (document.hidden) {
                this.tabSwitchCount++;
                this.remainingTabSwitches = Math.max(0, this.antiCheat.max_tab_switches - this.tabSwitchCount);
                this.sendEvent('tab_switch');

                if (this.tabSwitchCount > this.antiCheat.max_tab_switches) {
                    this.showWarning = true;
                    this.pauseVideo();
                }
            }
        },

        handleFocus() {
            this.showWarning = false;
            this.lastInteractionTime = Date.now();
        },

        handleBlur() {
            this.sendEvent('focus_lost');
        },

        handlePlay() {
            this.status = 'watching';
            this.sendEvent('resume');
        },

        handlePause() {
            this.status = 'paused';
            this.sendEvent('pause');
        },

        handleSeek(event) {
            const player = event.target;
            const fromPosition = this.currentPosition;
            const toPosition = Math.floor(player.currentTime);

            this.sendEvent('seek', {
                from_position: fromPosition,
                to_position: toPosition,
            });
        },

        handleTimeUpdate(event) {
            this.currentPosition = Math.floor(event.target.currentTime);
        },

        pauseVideo() {
            if (this.videoType === 'youtube' && this.youtubePlayer) {
                this.youtubePlayer.pauseVideo();
            } else if (this.videoType === 'direct') {
                document.getElementById('direct-player')?.pause();
            }
        },

        dismissWarning() {
            this.showWarning = false;
            if (this.videoType === 'youtube' && this.youtubePlayer) {
                this.youtubePlayer.playVideo();
            } else if (this.videoType === 'direct') {
                document.getElementById('direct-player')?.play();
            }
        },

        startInteractionCheck() {
            this.interactionCheckInterval = setInterval(() => {
                const timeSinceInteraction = (Date.now() - this.lastInteractionTime) / 1000;
                if (timeSinceInteraction > this.antiCheat.interaction_interval) {
                    this.showInteractionPrompt = true;
                    this.pauseVideo();
                }
            }, 10000);
        },

        confirmInteraction() {
            this.showInteractionPrompt = false;
            this.lastInteractionTime = Date.now();
            this.sendEvent('interaction');

            if (this.videoType === 'youtube' && this.youtubePlayer) {
                this.youtubePlayer.playVideo();
            } else if (this.videoType === 'direct') {
                document.getElementById('direct-player')?.play();
            }
        },

        async completeAndClaim() {
            if (!this.canComplete || this.isLoading) return;

            this.isLoading = true;

            try {
                const response = await fetch(`/user/video-missions/completion/${this.completionId}/complete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.status = 'completed';
                    clearInterval(this.heartbeatInterval);
                    clearInterval(this.interactionCheckInterval);

                    if (data.reward_given) {
                        this.earnedRewards = data.rewards;
                        this.rewardSummary = this.formatRewards(data.rewards);

                        // Trigger celebration
                        this.triggerCelebration();
                        this.showSuccessModal = true;
                    } else {
                        alert(data.message || 'ทำภารกิจสำเร็จ รอการตรวจสอบ');
                        window.location.href = '{{ route("user.video-missions.index") }}';
                    }
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Complete error:', error);
                alert('เกิดข้อผิดพลาด กรุณาลองใหม่');
            } finally {
                this.isLoading = false;
            }
        },

        triggerCelebration() {
            // Create confetti
            const colors = ['#ec4899', '#a855f7', '#6366f1', '#fbbf24', '#34d399', '#f87171'];
            const container = document.getElementById('confetti-container');

            for (let i = 0; i < 100; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDuration = (2 + Math.random() * 2) + 's';
                    confetti.style.animationDelay = Math.random() * 0.5 + 's';
                    container.appendChild(confetti);

                    setTimeout(() => confetti.remove(), 5000);
                }, i * 20);
            }

            // Create coin particles
            for (let i = 0; i < 20; i++) {
                setTimeout(() => {
                    const coin = document.createElement('div');
                    coin.className = 'fixed text-4xl pointer-events-none z-50 coin-particle';
                    coin.innerHTML = '🪙';
                    coin.style.left = (20 + Math.random() * 60) + 'vw';
                    coin.style.top = '40vh';
                    coin.style.animationDelay = Math.random() * 0.5 + 's';
                    document.body.appendChild(coin);

                    setTimeout(() => coin.remove(), 4000);
                }, i * 100);
            }
        },

        async cancelMission() {
            if (this.isLoading) return;

            if (!confirm('ต้องการยกเลิกภารกิจนี้หรือไม่?')) return;

            this.isLoading = true;

            try {
                const response = await fetch(`/user/video-missions/completion/${this.completionId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = '{{ route("user.video-missions.index") }}';
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Cancel error:', error);
                alert('เกิดข้อผิดพลาด');
            } finally {
                this.isLoading = false;
            }
        },

        handleBeforeUnload(event) {
            if (this.status !== 'completed' && this.watchedSeconds > 0) {
                event.preventDefault();
                event.returnValue = '';
            }
        },

        // Share functions
        shareToFacebook() {
            const url = encodeURIComponent(this.affiliateLink);
            const text = encodeURIComponent(this.shareText);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${text}`, '_blank', 'width=600,height=400');
        },

        shareToLine() {
            const text = encodeURIComponent(this.shareText + '\n\n' + this.affiliateLink);
            window.open(`https://line.me/R/msg/text/?${text}`, '_blank');
        },

        shareToTwitter() {
            const text = encodeURIComponent(this.shareText);
            const url = encodeURIComponent(this.affiliateLink);
            window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank', 'width=600,height=400');
        },

        async copyAffiliateLink() {
            try {
                await navigator.clipboard.writeText(this.affiliateLink);
                this.linkCopied = true;
                setTimeout(() => this.linkCopied = false, 2000);
            } catch (err) {
                // Fallback
                const textarea = document.createElement('textarea');
                textarea.value = this.affiliateLink;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                this.linkCopied = true;
                setTimeout(() => this.linkCopied = false, 2000);
            }
        },

        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        },

        formatRewards(rewards) {
            const parts = [];
            if (rewards.money > 0) parts.push(`฿${rewards.money.toFixed(2)}`);
            if (rewards.coins > 0) parts.push(`${rewards.coins} Coins`);
            if (rewards.points > 0) parts.push(`${rewards.points} แต้ม`);
            if (rewards.exp > 0) parts.push(`${rewards.exp} EXP`);
            return parts.join(' + ') || '-';
        },
    };
}

// ป้องกัน bfcache (back-forward cache) - reload เมื่อกลับมาที่หน้านี้ด้วย back button
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        // Page was restored from bfcache - reload เพื่อตรวจสอบสิทธิ์
        location.reload();
    }
});
</script>
@endpush
@endsection
