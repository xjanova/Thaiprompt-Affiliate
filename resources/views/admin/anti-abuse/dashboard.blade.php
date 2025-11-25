{{--
    Admin Dashboard - ศูนย์ป้องกันการกลั่นแกล้ง
    แสดงสถิติและข้อมูลสำคัญของระบบ Anti-Abuse
--}}
@extends('layouts.admin')

@section('title', 'ศูนย์ป้องกันการกลั่นแกล้ง')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-shield-alt text-red-500 mr-3"></i>
                ศูนย์ป้องกันการกลั่นแกล้ง
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                จัดการข้อร้องเรียน, คะแนนความน่าเชื่อถือ, และค่าปรับ
            </p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('admin.anti-abuse.disputes') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                <i class="fas fa-exclamation-triangle"></i>
                ข้อร้องเรียน
                @if($stats['pending_disputes'] > 0)
                    <span class="bg-white text-red-600 text-xs font-bold px-2 py-0.5 rounded-full">{{ $stats['pending_disputes'] }}</span>
                @endif
            </a>
            <a href="{{ route('admin.anti-abuse.penalties') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors">
                <i class="fas fa-coins"></i>
                ค่าปรับ
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Pending Disputes --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ข้อร้องเรียนรอดำเนินการ</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['pending_disputes'] }}</p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-full">
                    <i class="fas fa-exclamation-circle text-2xl text-red-500"></i>
                </div>
            </div>
            @if($stats['high_priority_disputes'] > 0)
                <p class="mt-2 text-xs text-red-600 dark:text-red-400 font-medium">
                    <i class="fas fa-fire mr-1"></i>
                    {{ $stats['high_priority_disputes'] }} รายการด่วน
                </p>
            @endif
        </div>

        {{-- Pending Penalties --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ค่าปรับรอเรียกเก็บ</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">฿{{ number_format($stats['total_penalty_amount'], 0) }}</p>
                </div>
                <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-full">
                    <i class="fas fa-coins text-2xl text-orange-500"></i>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ $stats['pending_penalties'] }} รายการ
            </p>
        </div>

        {{-- Problem Users --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ผู้ใช้ที่ต้องจับตา</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['warning_users'] }}</p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-full">
                    <i class="fas fa-user-exclamation text-2xl text-yellow-500"></i>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                ระดับ Warning
            </p>
        </div>

        {{-- Banned Users --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-gray-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ถูกระงับ/แบน</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['suspended_users'] + $stats['banned_users'] }}</p>
                </div>
                <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-full">
                    <i class="fas fa-user-slash text-2xl text-gray-500"></i>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ $stats['suspended_users'] }} ระงับ / {{ $stats['banned_users'] }} แบน
            </p>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.anti-abuse.disputes', ['status' => 'pending']) }}"
           class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition-shadow">
            <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                <i class="fas fa-clock text-red-500"></i>
            </div>
            <div>
                <p class="font-medium text-gray-900 dark:text-white">รอตรวจสอบ</p>
                <p class="text-sm text-gray-500">{{ $stats['pending_disputes'] }} รายการ</p>
            </div>
        </a>

        <a href="{{ route('admin.anti-abuse.disputes', ['status' => 'investigating']) }}"
           class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition-shadow">
            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                <i class="fas fa-search text-blue-500"></i>
            </div>
            <div>
                <p class="font-medium text-gray-900 dark:text-white">กำลังสอบสวน</p>
                <p class="text-sm text-gray-500">{{ $stats['investigating_disputes'] }} รายการ</p>
            </div>
        </a>

        <a href="{{ route('admin.anti-abuse.trust-scores', ['trust_level' => 'warning']) }}"
           class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition-shadow">
            <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                <i class="fas fa-exclamation-triangle text-yellow-500"></i>
            </div>
            <div>
                <p class="font-medium text-gray-900 dark:text-white">Warning Level</p>
                <p class="text-sm text-gray-500">{{ $stats['warning_users'] }} คน</p>
            </div>
        </a>

        <a href="{{ route('admin.anti-abuse.blocks') }}"
           class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition-shadow">
            <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                <i class="fas fa-ban text-purple-500"></i>
            </div>
            <div>
                <p class="font-medium text-gray-900 dark:text-white">Block ที่ใช้งาน</p>
                <p class="text-sm text-gray-500">{{ $stats['active_blocks'] }} รายการ</p>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Disputes --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                        ข้อร้องเรียนล่าสุด
                    </h2>
                    <a href="{{ route('admin.anti-abuse.disputes') }}" class="text-sm text-purple-600 hover:text-purple-700">
                        ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recentDisputes as $dispute)
                    <a href="{{ route('admin.anti-abuse.disputes.show', $dispute) }}"
                       class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                @switch($dispute->priority)
                                    @case('critical')
                                        <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-full">
                                            <i class="fas fa-fire text-red-500"></i>
                                        </div>
                                        @break
                                    @case('high')
                                        <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-full">
                                            <i class="fas fa-exclamation text-orange-500"></i>
                                        </div>
                                        @break
                                    @default
                                        <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-full">
                                            <i class="fas fa-flag text-gray-500"></i>
                                        </div>
                                @endswitch
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-900 dark:text-white truncate">
                                        {{ $dispute->title }}
                                    </span>
                                    <span class="flex-shrink-0 px-2 py-0.5 text-xs rounded-full
                                        @if($dispute->status === 'pending') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                                        @elseif($dispute->status === 'investigating') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                                        @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400
                                        @endif">
                                        {{ $dispute->getStatusLabel() }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $dispute->dispute_number }} - {{ $dispute->getDisputeTypeLabel() }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    {{ $dispute->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        <i class="fas fa-check-circle text-4xl text-green-500 mb-2"></i>
                        <p>ไม่มีข้อร้องเรียนใหม่</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Problem Users --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-user-exclamation text-yellow-500 mr-2"></i>
                        ผู้ใช้ที่ต้องจับตา
                    </h2>
                    <a href="{{ route('admin.anti-abuse.trust-scores', ['low_score' => 1]) }}" class="text-sm text-purple-600 hover:text-purple-700">
                        ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($problemUsers as $ts)
                    <a href="{{ route('admin.anti-abuse.trust-scores.show', $ts) }}"
                       class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-400 to-gray-500 flex items-center justify-center text-white font-bold">
                                    {{ substr($ts->user?->name ?? $ts->provider?->display_name ?? '?', 0, 1) }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 dark:text-white truncate">
                                    {{ $ts->user?->name ?? $ts->provider?->display_name ?? 'N/A' }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $ts->entity_type === 'user' ? 'ลูกค้า' : 'ผู้ให้บริการ' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold
                                    @if($ts->trust_score >= 60) text-green-600
                                    @elseif($ts->trust_score >= 40) text-yellow-600
                                    @else text-red-600
                                    @endif">
                                    {{ number_format($ts->trust_score, 0) }}
                                </p>
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    @if($ts->trust_level === 'warning') bg-yellow-100 text-yellow-700
                                    @elseif($ts->trust_level === 'restricted') bg-orange-100 text-orange-700
                                    @elseif($ts->trust_level === 'suspended') bg-red-100 text-red-700
                                    @else bg-gray-100 text-gray-700
                                    @endif">
                                    {{ $ts->getTrustLevelLabel() }}
                                </span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        <i class="fas fa-check-circle text-4xl text-green-500 mb-2"></i>
                        <p>ไม่มีผู้ใช้ที่มีปัญหา</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Pending Penalties --}}
    @if($pendingPenalties->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-coins text-orange-500 mr-2"></i>
                        ค่าปรับรอดำเนินการ
                    </h2>
                    <a href="{{ route('admin.anti-abuse.penalties', ['status' => 'pending']) }}" class="text-sm text-purple-600 hover:text-purple-700">
                        ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Booking</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ถูกปรับ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวน</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($pendingPenalties as $penalty)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono text-sm text-gray-900 dark:text-white">
                                        {{ $penalty->booking?->booking_number ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ $penalty->user?->name ?? $penalty->provider?->display_name ?? '-' }}
                                    </span>
                                    <span class="text-xs text-gray-500 ml-1">
                                        ({{ $penalty->penalized_type === 'user' ? 'ลูกค้า' : 'ผู้ให้บริการ' }})
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $penalty->getPenaltyTypeLabel() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-lg font-bold text-orange-600">
                                        ฿{{ number_format($penalty->penalty_amount, 2) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <form action="{{ route('admin.anti-abuse.penalties.update', $penalty) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="action" value="charge">
                                            <button type="submit" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs rounded-lg">
                                                <i class="fas fa-check mr-1"></i>เรียกเก็บ
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.anti-abuse.penalties.update', $penalty) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="action" value="waive">
                                            <button type="submit" class="px-3 py-1 bg-gray-500 hover:bg-gray-600 text-white text-xs rounded-lg">
                                                <i class="fas fa-times mr-1"></i>ยกเว้น
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
