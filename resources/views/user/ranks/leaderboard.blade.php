@extends('layouts.user')

@section('title', 'กระดานผู้นำ')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Leaderboard Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="absolute top-1/2 right-1/4 w-24 h-24 bg-white opacity-5 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <i class="fas fa-trophy text-4xl"></i>
                <h1 class="text-3xl md:text-4xl font-bold">กระดานผู้นำ</h1>
            </div>
            <p class="text-purple-100 text-lg">Top Performers Leaderboard</p>
        </div>
    </div>

    <!-- User's Position Card -->
    @php
        $currentUser = auth()->user();
        $userPosition = collect($leaderboard)->search(function($item) use ($currentUser) {
            return $item->id === $currentUser->id;
        });
        $userRank = $userPosition !== false ? $userPosition + 1 : null;
    @endphp

    @if($userRank)
    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <span class="text-2xl font-bold">{{ $userRank }}</span>
                </div>
                <div>
                    <p class="text-sm text-purple-100">อันดับของคุณ</p>
                    <p class="text-2xl font-bold">{{ $currentUser->name }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-purple-100">แต้มรวม</p>
                <p class="text-3xl font-bold">{{ number_format($currentUser->rank_points ?? 0) }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Top 3 Podium -->
    @if($leaderboard->count() >= 3)
    <div class="grid grid-cols-3 gap-4 items-end">
        <!-- 2nd Place -->
        <div class="transform translate-y-8">
            <div class="bg-gradient-to-br from-gray-300 to-gray-500 rounded-2xl shadow-xl p-6 text-white text-center">
                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-4xl">🥈</span>
                </div>
                <div class="mb-3">
                    <div class="text-sm opacity-80 mb-1">#2</div>
                    <div class="font-bold text-lg truncate">{{ $leaderboard[1]->name }}</div>
                </div>
                <div class="bg-white bg-opacity-20 rounded-lg p-3">
                    <div class="text-xs opacity-80">แต้ม</div>
                    <div class="text-xl font-bold">{{ number_format($leaderboard[1]->rank_points ?? 0) }}</div>
                </div>
                @if($leaderboard[1]->currentRank)
                <div class="mt-3 text-xs opacity-90">
                    <i class="{{ $leaderboard[1]->currentRank->icon ?? 'fas fa-star' }}"></i>
                    {{ $leaderboard[1]->currentRank->name_th ?? $leaderboard[1]->currentRank->name }}
                </div>
                @endif
            </div>
        </div>

        <!-- 1st Place -->
        <div class="transform -translate-y-4">
            <div class="bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-2xl shadow-2xl p-8 text-white text-center relative">
                <div class="absolute -top-6 left-1/2 transform -translate-x-1/2">
                    <div class="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center shadow-lg animate-bounce">
                        <i class="fas fa-crown text-2xl"></i>
                    </div>
                </div>
                <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4 mt-4">
                    <span class="text-5xl">🥇</span>
                </div>
                <div class="mb-4">
                    <div class="text-sm opacity-80 mb-1">#1 CHAMPION</div>
                    <div class="font-bold text-2xl truncate">{{ $leaderboard[0]->name }}</div>
                </div>
                <div class="bg-white bg-opacity-20 rounded-lg p-4">
                    <div class="text-xs opacity-80">แต้ม</div>
                    <div class="text-3xl font-bold">{{ number_format($leaderboard[0]->rank_points ?? 0) }}</div>
                </div>
                @if($leaderboard[0]->currentRank)
                <div class="mt-4 text-sm opacity-90">
                    <i class="{{ $leaderboard[0]->currentRank->icon ?? 'fas fa-star' }}"></i>
                    {{ $leaderboard[0]->currentRank->name_th ?? $leaderboard[0]->currentRank->name }}
                </div>
                @endif
            </div>
        </div>

        <!-- 3rd Place -->
        <div class="transform translate-y-8">
            <div class="bg-gradient-to-br from-orange-400 to-orange-600 rounded-2xl shadow-xl p-6 text-white text-center">
                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-4xl">🥉</span>
                </div>
                <div class="mb-3">
                    <div class="text-sm opacity-80 mb-1">#3</div>
                    <div class="font-bold text-lg truncate">{{ $leaderboard[2]->name }}</div>
                </div>
                <div class="bg-white bg-opacity-20 rounded-lg p-3">
                    <div class="text-xs opacity-80">แต้ม</div>
                    <div class="text-xl font-bold">{{ number_format($leaderboard[2]->rank_points ?? 0) }}</div>
                </div>
                @if($leaderboard[2]->currentRank)
                <div class="mt-3 text-xs opacity-90">
                    <i class="{{ $leaderboard[2]->currentRank->icon ?? 'fas fa-star' }}"></i>
                    {{ $leaderboard[2]->currentRank->name_th ?? $leaderboard[2]->currentRank->name }}
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Leaderboard Table -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">🏆 ผู้นำทั้งหมด</h2>
            <p class="text-sm text-gray-600 mt-1">แสดง {{ $leaderboard->count() }} อันดับแรก</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">อันดับ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ระดับ</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">แต้ม</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($leaderboard as $index => $user)
                    <tr class="hover:bg-gray-50 transition {{ $user->id === auth()->id() ? 'bg-indigo-50' : '' }}">
                        <!-- Rank -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @if($index === 0)
                                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                        1
                                    </div>
                                @elseif($index === 1)
                                    <div class="w-10 h-10 bg-gradient-to-br from-gray-300 to-gray-500 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                        2
                                    </div>
                                @elseif($index === 2)
                                    <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                        3
                                    </div>
                                @else
                                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-700 font-bold">
                                        {{ $index + 1 }}
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- User Info -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $user->name }}
                                        @if($user->id === auth()->id())
                                            <span class="ml-2 px-2 py-1 bg-indigo-100 text-indigo-800 text-xs font-semibold rounded">คุณ</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Rank -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($user->currentRank)
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-amber-600 rounded-full flex items-center justify-center text-white">
                                    <i class="{{ $user->currentRank->icon ?? 'fas fa-star' }} text-sm"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ app()->getLocale() === 'th' ? ($user->currentRank->name_th ?? $user->currentRank->name) : $user->currentRank->name }}
                                    </div>
                                    <div class="text-xs text-gray-500">Level {{ $user->currentRank->level }}</div>
                                </div>
                            </div>
                            @else
                            <span class="text-sm text-gray-500">-</span>
                            @endif
                        </td>

                        <!-- Points -->
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-lg font-bold text-gray-900">{{ number_format($user->rank_points ?? 0) }}</div>
                            <div class="text-xs text-gray-500">แต้ม</div>
                        </td>

                        <!-- Status Badge -->
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($index === 0)
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                                    <i class="fas fa-crown"></i> ผู้นำ
                                </span>
                            @elseif($index < 10)
                                <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                    <i class="fas fa-star"></i> Top 10
                                </span>
                            @elseif($index < 50)
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                    Top 50
                                </span>
                            @else
                                <span class="px-3 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">
                                    Top 100
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-trophy text-4xl mb-3 opacity-50"></i>
                                <p class="text-lg font-semibold">ยังไม่มีข้อมูลกระดานผู้นำ</p>
                                <p class="text-sm mt-2">เริ่มสะสมแต้มเพื่อขึ้นอันดับกระดานผู้นำ</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Back to Dashboard -->
    <div class="text-center">
        <a href="{{ route('user.ranks.dashboard') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปที่แดชบอร์ด</span>
        </a>
    </div>
</div>

@push('scripts')
<style>
@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.animate-bounce {
    animation: bounce 2s infinite;
}
</style>
@endpush
@endsection
