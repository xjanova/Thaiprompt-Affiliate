{{--
/**
 * Retention Status Bar Component
 *
 * แถบเตือนสถานะรักษายอดในแดชบอร์ดผู้ใช้
 * แสดงสถานะ, วันที่เหลือ, และ Auto-renewal status
 *
 * Props:
 * @param $retentionStatus - MembershipRetentionStatus model
 * @param $statistics - array จาก MembershipRetentionService::getUserStatistics()
 *
 * @version 3.0 (V3 Theme - Tailwind + Alpine.js)
 */
--}}

@props([
    'retentionStatus' => null,
    'statistics' => null,
])

@if($retentionStatus && $statistics)
@php
    $status = $statistics['status'] ?? 'inactive';
    $daysRemaining = $statistics['days_remaining'] ?? 0;
    $pointsPercentage = $statistics['points_percentage'] ?? 0;
    $currentPoints = $statistics['current_points'] ?? 0;
    $requiredPoints = $statistics['required_points'] ?? 0;
    $healthColor = $statistics['health_color'] ?? 'gray';

    // กำหนดสีและไอคอนตามสถานะ
    $statusConfig = match($status) {
        'active' => [
            'bg' => $daysRemaining <= 7
                ? 'from-amber-500/20 via-orange-500/20 to-red-500/20 dark:from-amber-500/30 dark:via-orange-500/30 dark:to-red-500/30'
                : 'from-emerald-500/20 via-green-500/20 to-teal-500/20 dark:from-emerald-500/30 dark:via-green-500/30 dark:to-teal-500/30',
            'border' => $daysRemaining <= 7 ? 'border-amber-400/50' : 'border-emerald-400/50',
            'icon' => $daysRemaining <= 7 ? 'fas fa-exclamation-triangle' : 'fas fa-shield-check',
            'iconColor' => $daysRemaining <= 7 ? 'text-amber-500' : 'text-emerald-500',
            'title' => $daysRemaining <= 7 ? 'ใกล้หมดอายุ!' : 'สถานะดี',
            'pulse' => $daysRemaining <= 3,
        ],
        'expired' => [
            'bg' => 'from-red-500/20 via-rose-500/20 to-pink-500/20 dark:from-red-500/30 dark:via-rose-500/30 dark:to-pink-500/30',
            'border' => 'border-red-400/50',
            'icon' => 'fas fa-heart-crack',
            'iconColor' => 'text-red-500',
            'title' => 'หมดอายุแล้ว!',
            'pulse' => true,
        ],
        default => [
            'bg' => 'from-gray-500/20 via-slate-500/20 to-zinc-500/20 dark:from-gray-500/30 dark:via-slate-500/30 dark:to-zinc-500/30',
            'border' => 'border-gray-400/50',
            'icon' => 'fas fa-circle-info',
            'iconColor' => 'text-gray-500',
            'title' => 'ไม่ได้ใช้งาน',
            'pulse' => false,
        ],
    };

    // Auto-renewal status
    $autoRenewalEnabled = $retentionStatus->isAutoRenewalEnabled();
    $autoRenewalStatus = $retentionStatus->getAutoRenewalStatusText();
    $autoRenewalColor = $retentionStatus->getAutoRenewalStatusColor();
@endphp

<div x-data="retentionStatusBar()"
     class="relative overflow-hidden rounded-2xl border {{ $statusConfig['border'] }} bg-gradient-to-r {{ $statusConfig['bg'] }} backdrop-blur-xl shadow-xl"
     {{ $statusConfig['pulse'] ? 'x-init="startPulse()"' : '' }}>

    {{-- Animated Background --}}
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-1/2 -left-1/2 w-full h-full bg-white/5 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-1/2 -right-1/2 w-full h-full bg-white/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
    </div>

    <div class="relative z-10 p-4 lg:p-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

            {{-- Left: Status Info --}}
            <div class="flex items-center gap-4">
                {{-- Icon --}}
                <div class="flex-shrink-0 w-14 h-14 rounded-xl bg-white/20 dark:bg-white/10 backdrop-blur-sm flex items-center justify-center {{ $statusConfig['pulse'] ? 'animate-pulse' : '' }}">
                    <i class="{{ $statusConfig['icon'] }} text-2xl {{ $statusConfig['iconColor'] }}"></i>
                </div>

                {{-- Status Text --}}
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            ระบบรักษายอด
                        </h3>
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full
                            @if($status === 'active') bg-emerald-500/20 text-emerald-700 dark:text-emerald-300
                            @elseif($status === 'expired') bg-red-500/20 text-red-700 dark:text-red-300
                            @else bg-gray-500/20 text-gray-700 dark:text-gray-300
                            @endif">
                            {{ $statusConfig['title'] }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @if($status === 'active')
                            @if($daysRemaining > 0)
                                เหลืออีก <span class="font-bold {{ $daysRemaining <= 7 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ $daysRemaining }}</span> วัน
                                @if($daysRemaining <= 3)
                                    <span class="text-red-500 font-semibold animate-pulse">รีบทำยอดด่วน!</span>
                                @endif
                            @else
                                <span class="text-red-500 font-bold">วันสุดท้าย!</span>
                            @endif
                        @elseif($status === 'expired')
                            กรุณาซ่อมสิทธิ์หรือเติมล่วงหน้า
                        @else
                            เริ่มต้นซื้อสินค้าเพื่อเปิดใช้งาน
                        @endif
                    </p>
                </div>
            </div>

            {{-- Center: Points Progress --}}
            <div class="flex-1 max-w-md mx-4 hidden lg:block">
                <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1.5">
                    <span>แต้มเดือนนี้</span>
                    <span class="font-semibold">{{ number_format($currentPoints, 0) }} / {{ number_format($requiredPoints, 0) }}</span>
                </div>
                <div class="relative h-3 bg-white/30 dark:bg-white/10 rounded-full overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-gray-200/50 to-gray-300/50 dark:from-gray-700/50 dark:to-gray-600/50"></div>
                    <div class="relative h-full rounded-full transition-all duration-500 ease-out
                        @if($pointsPercentage >= 100) bg-gradient-to-r from-emerald-500 to-green-500
                        @elseif($pointsPercentage >= 50) bg-gradient-to-r from-blue-500 to-cyan-500
                        @else bg-gradient-to-r from-amber-500 to-orange-500
                        @endif"
                        style="width: {{ min(100, $pointsPercentage) }}%">
                        <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                    </div>
                </div>
                @if($pointsPercentage >= 100)
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-semibold">
                        <i class="fas fa-check-circle mr-1"></i> ครบแล้ว! ยอดเยี่ยม!
                    </p>
                @endif
            </div>

            {{-- Right: Auto-Renewal & Actions --}}
            <div class="flex items-center gap-3">
                {{-- Auto-Renewal Badge --}}
                <div class="hidden sm:flex items-center gap-2 px-3 py-2 rounded-xl
                    @if($autoRenewalColor === 'green') bg-emerald-500/20 border border-emerald-400/30
                    @elseif($autoRenewalColor === 'yellow') bg-amber-500/20 border border-amber-400/30
                    @else bg-gray-500/20 border border-gray-400/30
                    @endif">
                    <i class="fas fa-robot text-sm
                        @if($autoRenewalColor === 'green') text-emerald-500
                        @elseif($autoRenewalColor === 'yellow') text-amber-500
                        @else text-gray-500
                        @endif"></i>
                    <div class="text-xs">
                        <div class="font-semibold text-gray-700 dark:text-gray-300">Auto-Renewal</div>
                        <div class="@if($autoRenewalColor === 'green') text-emerald-600 dark:text-emerald-400
                            @elseif($autoRenewalColor === 'yellow') text-amber-600 dark:text-amber-400
                            @else text-gray-500
                            @endif">
                            {{ $autoRenewalStatus }}
                        </div>
                    </div>
                </div>

                {{-- Action Button --}}
                <a href="{{ route('user.retention.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                    <i class="fas fa-heart-pulse"></i>
                    <span class="hidden sm:inline">จัดการ</span>
                </a>

                {{-- Quick Settings --}}
                <button @click="showQuickSettings = !showQuickSettings"
                        class="p-2.5 rounded-xl bg-white/20 dark:bg-white/10 hover:bg-white/30 dark:hover:bg-white/20 text-gray-700 dark:text-gray-300 transition-all">
                    <i class="fas fa-gear"></i>
                </button>
            </div>
        </div>

        {{-- Mobile Points Progress --}}
        <div class="lg:hidden mt-4">
            <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1.5">
                <span>แต้มเดือนนี้</span>
                <span class="font-semibold">{{ number_format($currentPoints, 0) }} / {{ number_format($requiredPoints, 0) }}</span>
            </div>
            <div class="relative h-2.5 bg-white/30 dark:bg-white/10 rounded-full overflow-hidden">
                <div class="relative h-full rounded-full transition-all duration-500 ease-out
                    @if($pointsPercentage >= 100) bg-gradient-to-r from-emerald-500 to-green-500
                    @elseif($pointsPercentage >= 50) bg-gradient-to-r from-blue-500 to-cyan-500
                    @else bg-gradient-to-r from-amber-500 to-orange-500
                    @endif"
                    style="width: {{ min(100, $pointsPercentage) }}%">
                </div>
            </div>
        </div>

        {{-- Quick Settings Panel --}}
        <div x-show="showQuickSettings"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             @click.away="showQuickSettings = false"
             class="mt-4 p-4 bg-white/50 dark:bg-black/30 backdrop-blur-sm rounded-xl border border-white/30 dark:border-white/10">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <a href="{{ route('user.retention.index') }}"
                   class="flex items-center gap-2 p-3 bg-white/60 dark:bg-white/10 rounded-lg hover:bg-white/80 dark:hover:bg-white/20 transition-all">
                    <i class="fas fa-chart-line text-purple-500"></i>
                    <span class="text-sm text-gray-700 dark:text-gray-300">ดูสถิติ</span>
                </a>
                <a href="{{ route('user.retention.auto-settings') }}"
                   class="flex items-center gap-2 p-3 bg-white/60 dark:bg-white/10 rounded-lg hover:bg-white/80 dark:hover:bg-white/20 transition-all">
                    <i class="fas fa-robot text-blue-500"></i>
                    <span class="text-sm text-gray-700 dark:text-gray-300">ตั้งค่าอัตโนมัติ</span>
                </a>
                <a href="{{ route('user.retention.repair') }}"
                   class="flex items-center gap-2 p-3 bg-white/60 dark:bg-white/10 rounded-lg hover:bg-white/80 dark:hover:bg-white/20 transition-all">
                    <i class="fas fa-wrench text-amber-500"></i>
                    <span class="text-sm text-gray-700 dark:text-gray-300">ซ่อมสิทธิ์</span>
                </a>
                <a href="{{ route('user.retention.advance-renewal') }}"
                   class="flex items-center gap-2 p-3 bg-white/60 dark:bg-white/10 rounded-lg hover:bg-white/80 dark:hover:bg-white/20 transition-all">
                    <i class="fas fa-calendar-plus text-emerald-500"></i>
                    <span class="text-sm text-gray-700 dark:text-gray-300">เติมล่วงหน้า</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function retentionStatusBar() {
    return {
        showQuickSettings: false,
        isPulsing: false,

        startPulse() {
            this.isPulsing = true;
        }
    }
}
</script>
@endif
