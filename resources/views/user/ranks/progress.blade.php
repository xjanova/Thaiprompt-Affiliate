@extends('layouts.user-arrow-x')

@section('title', 'ความคืบหน้ายศ')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-yellow-600 via-amber-600 to-orange-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <span class="text-3xl">🏆</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold">ความคืบหน้ายศ</h1>
                <p class="text-yellow-100 mt-1">ติดตามความคืบหน้าสู่ยศที่สูงขึ้น</p>
            </div>
        </div>
    </div>

    <!-- Current Rank Card -->
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">ยศปัจจุบันของคุณ</h2>
            @if($user->currentRank)
                <div class="inline-block">
                    <div class="w-32 h-32 mx-auto bg-gradient-to-br from-yellow-400 via-amber-500 to-orange-500 rounded-full flex items-center justify-center mb-4 shadow-2xl">
                        <span class="text-6xl">{{ $user->currentRank->icon ?? '🏅' }}</span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-800 mb-2">{{ $user->currentRank->name }}</h3>
                    <p class="text-gray-600">{{ $user->currentRank->description ?? '' }}</p>
                    <div class="mt-4">
                        <span class="px-6 py-2 bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-full font-bold text-lg">
                            ระดับ {{ $user->currentRank->level }}
                        </span>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <span class="text-6xl mb-4 block">🎯</span>
                    <p class="text-gray-600 text-lg">ยังไม่มียศ</p>
                    <p class="text-sm text-gray-500 mt-2">เริ่มต้นสร้างทีมและสะสมคะแนนเพื่อรับยศแรกของคุณ</p>
                </div>
            @endif
        </div>

        <!-- Current Stats -->
        <div class="grid md:grid-cols-3 gap-4">
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                <div class="text-center">
                    <div class="text-sm text-gray-600 mb-2">คะแนนปัจจุบัน</div>
                    <div class="text-3xl font-bold text-blue-600">{{ number_format($user->rank_points ?? 0, 0) }}</div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-200">
                <div class="text-center">
                    <div class="text-sm text-gray-600 mb-2">จำนวนทีม</div>
                    <div class="text-3xl font-bold text-purple-600">{{ $user->team_count ?? 0 }}</div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                <div class="text-center">
                    <div class="text-sm text-gray-600 mb-2">รายได้รวม</div>
                    <div class="text-3xl font-bold text-green-600">฿{{ number_format($user->total_commissions ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rank Progression -->
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <span>📊</span> เส้นทางความก้าวหน้า
        </h2>

        <div class="space-y-6">
            @foreach($allRanks as $index => $rank)
                @php
                    $isCurrentRank = $user->currentRank && $user->currentRank->id === $rank->id;
                    $isAchieved = $user->currentRank && $user->currentRank->level >= $rank->level;
                    $progress = $userProgress->firstWhere('target_rank_id', $rank->id);
                    $progressPercentage = $progress->progress_percentage ?? 0;
                @endphp

                <div class="relative">
                    <!-- Connecting Line -->
                    @if(!$loop->last)
                        <div class="absolute left-8 top-20 w-1 h-full {{ $isAchieved ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                    @endif

                    <!-- Rank Item -->
                    <div class="relative bg-gradient-to-br {{ $isCurrentRank ? 'from-yellow-50 to-amber-50 border-2 border-yellow-400' : ($isAchieved ? 'from-green-50 to-emerald-50 border border-green-300' : 'from-gray-50 to-slate-50 border border-gray-300') }} rounded-xl p-6">
                        <div class="flex items-start gap-6">
                            <!-- Icon -->
                            <div class="flex-shrink-0">
                                <div class="w-16 h-16 bg-gradient-to-br {{ $isAchieved ? 'from-yellow-400 to-orange-500' : 'from-gray-400 to-gray-500' }} rounded-full flex items-center justify-center shadow-lg relative z-10">
                                    <span class="text-3xl">{{ $rank->icon ?? '🏅' }}</span>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                            {{ $rank->name }}
                                            @if($isCurrentRank)
                                                <span class="px-3 py-1 bg-yellow-500 text-white text-xs rounded-full">ยศปัจจุบัน</span>
                                            @elseif($isAchieved)
                                                <span class="px-3 py-1 bg-green-500 text-white text-xs rounded-full">✓ ผ่านแล้ว</span>
                                            @endif
                                        </h3>
                                        <p class="text-sm text-gray-600 mt-1">{{ $rank->description ?? '' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-600">ระดับ</div>
                                        <div class="text-2xl font-bold text-gray-800">{{ $rank->level }}</div>
                                    </div>
                                </div>

                                <!-- Requirements -->
                                @if($rank->requirements)
                                    <div class="bg-white/80 rounded-lg p-4 mb-3">
                                        <div class="text-sm font-semibold text-gray-700 mb-2">เงื่อนไข:</div>
                                        <ul class="text-sm text-gray-600 space-y-1">
                                            @foreach($rank->requirements as $requirement)
                                                <li class="flex items-center gap-2">
                                                    <span class="{{ $isAchieved ? 'text-green-600' : 'text-gray-400' }}">
                                                        {{ $isAchieved ? '✅' : '○' }}
                                                    </span>
                                                    <span>{{ $requirement }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <!-- Progress Bar (only for next ranks) -->
                                @if(!$isAchieved && $progress)
                                    <div>
                                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>ความคืบหน้า</span>
                                            <span>{{ number_format($progressPercentage, 1) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-full transition-all duration-500"
                                                 style="width: {{ min($progressPercentage, 100) }}%"></div>
                                        </div>
                                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                                            <span>{{ number_format($progress->current_points ?? 0, 0) }} คะแนน</span>
                                            <span>{{ number_format($progress->target_points ?? 0, 0) }} คะแนน</span>
                                        </div>
                                    </div>
                                @endif

                                <!-- Benefits -->
                                @if($rank->benefits)
                                    <div class="mt-3 bg-blue-50 rounded-lg p-3">
                                        <div class="text-sm font-semibold text-blue-800 mb-1">สิทธิพิเศษ:</div>
                                        <div class="text-sm text-blue-700">{{ implode(', ', $rank->benefits) }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Tips -->
    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl shadow-xl p-6 border border-indigo-200">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span>💡</span> เคล็ดลับเพื่อเลื่อนยศ
        </h3>
        <ul class="space-y-2 text-sm text-gray-700">
            <li class="flex gap-2">
                <span>✅</span>
                <span><strong>สร้างทีมใหม่:</strong> เชิญสมาชิกใหม่เพื่อเพิ่มทีมและสร้างรายได้</span>
            </li>
            <li class="flex gap-2">
                <span>✅</span>
                <span><strong>รักษาทีมที่มีอยู่:</strong> ช่วยเหลือทีมให้ประสบความสำเร็จ</span>
            </li>
            <li class="flex gap-2">
                <span>✅</span>
                <span><strong>สะสม PV:</strong> ซื้อสินค้าและบริการเพื่อสะสมคะแนน</span>
            </li>
            <li class="flex gap-2">
                <span>✅</span>
                <span><strong>เรียนรู้ตลอดเวลา:</strong> พัฒนาทักษะการขายและการสร้างทีม</span>
            </li>
        </ul>
    </div>
</div>
@endsection
