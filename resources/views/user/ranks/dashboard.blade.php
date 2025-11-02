@extends('layouts.user')

@section('title', 'ระดับของฉัน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Rank Header -->
    <div class="bg-gradient-to-r from-orange-500 via-amber-600 to-yellow-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="absolute top-1/2 right-1/4 w-24 h-24 bg-white opacity-5 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <i class="fas fa-trophy text-4xl"></i>
                <h1 class="text-3xl md:text-4xl font-bold">ระดับของคุณ</h1>
            </div>
            <p class="text-orange-100 text-lg">Your Rank & Progress Dashboard</p>
        </div>
    </div>

    <!-- Current Rank Display -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Current Rank Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">🎖️ ระดับปัจจุบัน</h2>
                <span class="px-4 py-2 bg-orange-100 text-orange-800 rounded-lg text-sm font-semibold">
                    Level {{ $currentRank->level ?? 0 }}
                </span>
            </div>

            <div class="flex items-center gap-6 mb-6">
                <div class="flex-shrink-0">
                    <div class="w-24 h-24 bg-gradient-to-br from-orange-400 to-amber-600 rounded-full flex items-center justify-center text-white shadow-lg">
                        <i class="{{ $currentRank->icon ?? 'fas fa-star' }} text-4xl"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">
                        {{ app()->getLocale() === 'th' ? ($currentRank->name_th ?? $currentRank->name ?? 'ไม่มีระดับ') : ($currentRank->name ?? 'No Rank') }}
                    </h3>
                    <p class="text-gray-600">{{ __('Current Rank') }}</p>
                </div>
            </div>

            @if($currentRank && $currentRank->description)
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-600">{{ $currentRank->description }}</p>
            </div>
            @endif
        </div>

        <!-- Next Rank Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">🎯 ระดับถัดไป</h2>
                @if($nextRank)
                <span class="px-4 py-2 bg-green-100 text-green-800 rounded-lg text-sm font-semibold">
                    Level {{ $nextRank->level }}
                </span>
                @endif
            </div>

            @if($nextRank)
            <div class="flex items-center gap-6 mb-6">
                <div class="flex-shrink-0">
                    <div class="w-24 h-24 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full flex items-center justify-center text-white shadow-lg">
                        <i class="{{ $nextRank->icon ?? 'fas fa-star' }} text-4xl"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">
                        {{ app()->getLocale() === 'th' ? ($nextRank->name_th ?? $nextRank->name) : $nextRank->name }}
                    </h3>
                    <p class="text-gray-600">{{ __('Next Rank') }}</p>
                </div>
            </div>

            @if($nextRank->description)
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-600">{{ $nextRank->description }}</p>
            </div>
            @endif
            @else
            <div class="text-center py-8">
                <div class="text-6xl mb-4">🏆</div>
                <p class="text-xl font-semibold text-gray-900 mb-2">ยินดีด้วย!</p>
                <p class="text-gray-600">คุณอยู่ในระดับสูงสุดแล้ว</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Progress Section -->
    @if($nextRank && $nextRankProgress)
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">📊 ความคืบหน้าไประดับถัดไป</h2>

        <!-- Points Display -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fas fa-coins text-2xl text-blue-600"></i>
                    <p class="text-sm text-blue-800 font-semibold">แต้มปัจจุบัน</p>
                </div>
                <p class="text-3xl font-bold text-blue-900">{{ number_format($user->rank_points ?? 0) }}</p>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fas fa-bullseye text-2xl text-green-600"></i>
                    <p class="text-sm text-green-800 font-semibold">แต้มที่ต้องการ</p>
                </div>
                <p class="text-3xl font-bold text-green-900">{{ number_format($nextRankProgress->target_points ?? 0) }}</p>
            </div>

            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fas fa-arrow-up text-2xl text-orange-600"></i>
                    <p class="text-sm text-orange-800 font-semibold">แต้มที่เหลือ</p>
                </div>
                <p class="text-3xl font-bold text-orange-900">
                    {{ number_format(max(0, ($nextRankProgress->target_points ?? 0) - ($user->rank_points ?? 0))) }}
                </p>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-semibold text-gray-700">ความคืบหน้า</span>
                <span class="text-sm font-bold text-orange-600">
                    {{ number_format($nextRankProgress->progress_percentage ?? 0, 2) }}%
                </span>
            </div>
            <div class="h-8 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-orange-400 to-amber-600 rounded-full transition-all duration-500 flex items-center justify-end px-3"
                     style="width: {{ min(100, $nextRankProgress->progress_percentage ?? 0) }}%">
                    @if(($nextRankProgress->progress_percentage ?? 0) > 10)
                    <span class="text-xs font-bold text-white">{{ number_format($nextRankProgress->progress_percentage ?? 0, 1) }}%</span>
                    @endif
                </div>
            </div>
        </div>

        @if(($nextRankProgress->target_points ?? 0) > 0)
        <p class="text-center text-gray-600 text-sm">
            อีก {{ number_format(max(0, ($nextRankProgress->target_points ?? 0) - ($user->rank_points ?? 0))) }} แต้มจะถึงระดับ
            <span class="font-semibold text-orange-600">{{ app()->getLocale() === 'th' ? ($nextRank->name_th ?? $nextRank->name) : $nextRank->name }}</span>
        </p>
        @endif
    </div>
    @endif

    <!-- Leaderboard Position -->
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">🏅 อันดับของคุณ</h2>

        <div class="flex items-center justify-center gap-8 mb-6">
            <div class="text-center">
                <div class="w-32 h-32 bg-gradient-to-br from-yellow-400 to-orange-600 rounded-full flex items-center justify-center text-white shadow-2xl mb-4">
                    <span class="text-5xl font-bold">#{{ $leaderboardPosition }}</span>
                </div>
                <p class="text-lg font-semibold text-gray-900">อันดับของคุณในกระดานผู้นำ</p>
                <p class="text-sm text-gray-600">จาก {{ \App\Models\User::whereNotNull('current_rank_id')->count() }} ผู้ใช้</p>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('user.ranks.leaderboard') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                <i class="fas fa-trophy"></i>
                <span>ดูกระดานผู้นำ</span>
            </a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="{{ route('user.ranks.progress') }}"
           class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-8 text-white hover:opacity-90 transition block">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-tasks text-3xl"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold">ความคืบหน้า</h3>
                    <p class="text-blue-100">ดูความคืบหน้าทั้งหมด</p>
                </div>
            </div>
            <p class="text-sm text-blue-100">ติดตามความคืบหน้าและเงื่อนไขในการเลื่อนระดับ</p>
        </a>

        <a href="{{ route('user.ranks.leaderboard') }}"
           class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-xl p-8 text-white hover:opacity-90 transition block">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-trophy text-3xl"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold">กระดานผู้นำ</h3>
                    <p class="text-purple-100">ดูผู้นำด้านอันดับ</p>
                </div>
            </div>
            <p class="text-sm text-purple-100">เปรียบเทียบอันดับของคุณกับผู้ใช้คนอื่นๆ</p>
        </a>
    </div>

    <!-- How to Earn Points -->
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">💡 วิธีการเพิ่มแต้ม</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="flex items-start gap-4 p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white flex-shrink-0">
                    <i class="fas fa-user-plus text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-green-900 mb-1">แนะนำเพื่อน</h3>
                    <p class="text-sm text-green-800">รับแต้มจากการแนะนำสมาชิกใหม่</p>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white flex-shrink-0">
                    <i class="fas fa-coins text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-blue-900 mb-1">รับคอมมิชชั่น</h3>
                    <p class="text-sm text-blue-800">รับแต้มจากคอมมิชชั่นที่ได้รับ</p>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl">
                <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center text-white flex-shrink-0">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-purple-900 mb-1">ทำยอดขาย</h3>
                    <p class="text-sm text-purple-800">รับแต้มจากยอดขายที่เกิดขึ้น</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
