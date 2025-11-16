@extends('layouts.user-arrow-x')

@section('title', 'แดshboard แผน MLM')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-rose-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <span class="text-3xl">📊</span>
                </div>
                <div>
                    <h1 class="text-3xl font-bold">แผน MLM: {{ $member->plan->name ?? 'N/A' }}</h1>
                    <p class="text-purple-100 mt-1">รหัสสมาชิก: {{ $member->member_code }}</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm text-purple-200">สถานะ</div>
                <div class="mt-1">
                    @if($member->status === 'active')
                        <span class="px-4 py-2 bg-green-500 text-white rounded-lg font-bold">✅ ใช้งาน</span>
                    @elseif($member->status === 'pending')
                        <span class="px-4 py-2 bg-yellow-500 text-white rounded-lg font-bold">⏳ รออนุมัติ</span>
                    @else
                        <span class="px-4 py-2 bg-gray-500 text-white rounded-lg font-bold">❌ ไม่ใช้งาน</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid md:grid-cols-4 gap-4">
        <!-- Total Commissions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">💰</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">ค่าคอมมิชชั่นรวม</div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-white">
                        ฿{{ number_format($statistics['total_commissions'] ?? 0, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal PV -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">⭐</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">PV ส่วนตัว</div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-white">
                        {{ number_format($pvStats['personal_pv'] ?? 0, 0) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Group PV -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">👥</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">PV ทีม</div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-white">
                        {{ number_format($pvStats['group_pv'] ?? 0, 0) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Direct Referrals -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">🤝</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">สมาชิกทางตรง</div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-white">
                        {{ $statistics['direct_referrals'] ?? 0 }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Plan Details & Rank Info -->
    <div class="grid md:grid-cols-2 gap-6">
        <!-- Plan Details -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <span>📋</span> รายละเอียดแผน
            </h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="text-gray-600 dark:text-gray-400">ชื่อแผน:</span>
                    <span class="font-bold text-gray-800 dark:text-white">{{ $member->plan->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="text-gray-600 dark:text-gray-400">ประเภท:</span>
                    <span class="font-bold text-gray-800 dark:text-white">
                        @if($member->plan->type === 'binary')
                            Binary
                        @elseif($member->plan->type === 'unilevel')
                            Unilevel
                        @elseif($member->plan->type === 'matrix')
                            Matrix
                        @elseif($member->plan->type === 'hybrid')
                            Hybrid
                        @else
                            {{ $member->plan->type }}
                        @endif
                    </span>
                </div>
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="text-gray-600 dark:text-gray-400">วันที่เข้าร่วม:</span>
                    <span class="font-bold text-gray-800 dark:text-white">{{ $member->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-600 dark:text-gray-400">ผู้แนะนำ:</span>
                    <span class="font-bold text-gray-800 dark:text-white">
                        @if($member->sponsor)
                            {{ $member->sponsor->user->name ?? 'N/A' }} ({{ $member->sponsor->member_code }})
                        @else
                            -
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Current Rank -->
        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl shadow-xl p-6 border border-yellow-200">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <span>🏆</span> ยศปัจจุบัน
            </h2>
            @if($member->rank)
                <div class="text-center">
                    <div class="w-24 h-24 mx-auto bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center mb-4 shadow-lg">
                        <span class="text-4xl">{{ $member->rank->icon ?? '🏅' }}</span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">{{ $member->rank->name }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $member->rank->description ?? '' }}</p>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">ระดับ</div>
                        <div class="text-3xl font-bold text-orange-600">{{ $member->rank->level }}</div>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <span class="text-4xl mb-4 block">🎯</span>
                    <p class="text-gray-600 dark:text-gray-400">ยังไม่มียศ</p>
                    <a href="{{ route('user.ranks.dashboard') }}" class="mt-4 inline-block px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        ดูเงื่อนไขยศ
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Commission Breakdown -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
            <span>💵</span> รายละเอียดค่าคอมมิชชั่น
        </h2>
        <div class="grid md:grid-cols-3 gap-4">
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">ค่าคอมมิชชั่นรวม</div>
                <div class="text-2xl font-bold text-green-600">฿{{ number_format($statistics['total_commissions'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">ค่าคอมมิชชั่นเดือนนี้</div>
                <div class="text-2xl font-bold text-blue-600">฿{{ number_format($statistics['month_commissions'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4 border border-purple-200">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">ค่าคอมมิชชั่นสัปดาห์นี้</div>
                <div class="text-2xl font-bold text-purple-600">฿{{ number_format($statistics['week_commissions'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- PV Statistics -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
            <span>⭐</span> สถิติ PV (Point Value)
        </h2>
        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">PV ส่วนตัว</div>
                <div class="text-2xl font-bold text-blue-600">{{ number_format($pvStats['personal_pv'] ?? 0, 0) }}</div>
            </div>
            <div class="bg-purple-50 rounded-xl p-4 border border-purple-200">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">PV ทีม</div>
                <div class="text-2xl font-bold text-purple-600">{{ number_format($pvStats['group_pv'] ?? 0, 0) }}</div>
            </div>
            <div class="bg-green-50 rounded-xl p-4 border border-green-200">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">PV เดือนนี้</div>
                <div class="text-2xl font-bold text-green-600">{{ number_format($pvStats['month_pv'] ?? 0, 0) }}</div>
            </div>
            <div class="bg-orange-50 rounded-xl p-4 border border-orange-200">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">PV สัปดาห์นี้</div>
                <div class="text-2xl font-bold text-orange-600">{{ number_format($pvStats['week_pv'] ?? 0, 0) }}</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid md:grid-cols-3 gap-4">
        <a href="{{ route('user.mlm.team') }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">👥</span>
                </div>
                <div class="flex-1">
                    <div class="font-bold text-gray-800 dark:text-white">ทีมของฉัน</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">ดูสมาชิกในทีม</div>
                </div>
                <span class="text-blue-600">→</span>
            </div>
        </a>

        <a href="{{ route('user.mlm.commissions') }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">💰</span>
                </div>
                <div class="flex-1">
                    <div class="font-bold text-gray-800 dark:text-white">ค่าคอมมิชชั่น</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">รายละเอียดรายได้</div>
                </div>
                <span class="text-green-600">→</span>
            </div>
        </a>

        <a href="{{ route('user.mlm.genealogy') }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">🌳</span>
                </div>
                <div class="flex-1">
                    <div class="font-bold text-gray-800 dark:text-white">โครงสร้างทีม</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">ดูแผนผังองค์กร</div>
                </div>
                <span class="text-purple-600">→</span>
            </div>
        </a>
    </div>
</div>
@endsection
