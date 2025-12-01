@extends('layouts.user-arrow-x')

@section('title', 'ดูคลิป: ' . $mission->display_title)

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
     ]) }})"
     x-init="init()"
     @beforeunload.window="handleBeforeUnload($event)">

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

    {{-- Video Player Container --}}
    <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg overflow-hidden backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
        {{-- Video Title --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ $mission->icon ?? '🎬' }} {{ $mission->display_title }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                ดูให้ครบ {{ $mission->required_watch_time_formatted }} เพื่อรับรางวัล
            </p>
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

            {{-- Anti-Cheat Overlay (ถ้าต้อง interaction) --}}
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
        </div>

        {{-- Progress Bar --}}
        <div class="p-4 bg-gray-50 dark:bg-gray-800/50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    ความคืบหน้า
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    <span x-text="formatTime(watchedSeconds)">0:00</span> / {{ $mission->required_watch_time_formatted }}
                </span>
            </div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-pink-500 to-purple-600 rounded-full transition-all duration-300"
                     :style="{ width: progressPercentage + '%' }"></div>
            </div>
            <div class="flex items-center justify-between mt-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    <span x-text="progressPercentage.toFixed(1)">0</span>%
                </span>
                <span x-show="canComplete"
                      class="text-xs text-green-600 dark:text-green-400 font-medium">
                    ✓ ดูครบแล้ว! คลิกรับรางวัล
                </span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row gap-3">
                {{-- Complete Button --}}
                <button @click="completeAndClaim()"
                        :disabled="!canComplete || isLoading"
                        :class="{
                            'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700': canComplete && !isLoading,
                            'bg-gray-300 dark:bg-gray-700 cursor-not-allowed': !canComplete || isLoading
                        }"
                        class="flex-1 px-6 py-3 text-white rounded-xl font-medium transition-all flex items-center justify-center gap-2">
                    <template x-if="isLoading">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <span x-show="!isLoading">🎁 รับรางวัล ({{ $mission->reward_summary }})</span>
                    <span x-show="isLoading">กำลังดำเนินการ...</span>
                </button>

                {{-- Cancel Button --}}
                <button @click="cancelMission()"
                        :disabled="isLoading"
                        class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>

    {{-- Mission Info --}}
    <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl p-6 mt-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
        <h2 class="font-bold text-gray-900 dark:text-white mb-4">📋 ข้อมูลภารกิจ</h2>
        <div class="grid sm:grid-cols-2 gap-4 text-sm">
            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                <span>⏱️</span>
                <span>ต้องดู: {{ $mission->required_watch_time_formatted }}</span>
            </div>
            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                <span>🎁</span>
                <span>รางวัล: {{ $mission->reward_summary }}</span>
            </div>
            @if($rankLimit->reward_multiplier > 1)
            <div class="flex items-center gap-2 text-yellow-600 dark:text-yellow-400">
                <span>⭐</span>
                <span>โบนัส Rank: x{{ number_format($rankLimit->reward_multiplier, 2) }}</span>
            </div>
            @endif
            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                <span>📊</span>
                <span>ทำแล้ว: {{ number_format($mission->completion_count) }} ครั้ง</span>
            </div>
        </div>

        @if($mission->display_description)
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-gray-600 dark:text-gray-400">{{ $mission->display_description }}</p>
        </div>
        @endif
    </div>

    {{-- Success Modal --}}
    <div x-show="showSuccessModal"
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 max-w-md mx-4 text-center shadow-2xl"
             @click.away="showSuccessModal = false">
            <div class="text-6xl mb-4">🎉</div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                ยินดีด้วย!
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                คุณได้รับรางวัลแล้ว
            </p>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 mb-6">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="rewardSummary">
                    {{ $mission->reward_summary }}
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('user.video-missions.index') }}"
                   class="flex-1 px-4 py-3 bg-gradient-to-r from-pink-600 to-purple-600 text-white rounded-xl font-medium">
                    ดูภารกิจอื่น
                </a>
                <button @click="showSuccessModal = false"
                        class="px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-xl font-medium">
                    ปิด
                </button>
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

        // Success
        showSuccessModal: false,
        rewardSummary: '',

        // Player instances
        youtubePlayer: null,
        heartbeatInterval: null,
        interactionCheckInterval: null,

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

            // Prevent right click
            document.addEventListener('contextmenu', (e) => e.preventDefault());

            // Prevent keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) {
                    e.preventDefault();
                }
            });
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
                        onReady: (event) => {
                            event.target.playVideo();
                        }
                    }
                });
            };

            // If API already loaded
            if (window.YT && window.YT.Player) {
                window.onYouTubeIframeAPIReady();
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
            // Get current position
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
                await fetch(`/user/video-missions/completion/${this.completionId}/event`, {
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
            }, 10000); // Check every 10 seconds
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
                        this.rewardSummary = this.formatRewards(data.rewards);
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
</script>
@endpush
@endsection
