{{-- resources/views/user/fortune-referral/commissions.blade.php --}}
@extends('layouts.user-arrow-x')

@section('title', 'คอมมิชชั่นดูดวง')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50 dark:from-gray-900 dark:via-purple-900/20 dark:to-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Hero Header --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 p-6 sm:p-8 mb-8 shadow-xl">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-purple-300/20 rounded-full blur-2xl"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-3xl">🔮</span>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white">คอมมิชชั่นดูดวง</h1>
                </div>
                <p class="text-purple-100 text-sm sm:text-base">
                    รายได้จากการแนะนำเพื่อนมาดูดวง — Level 1 (สายตรง) และ Level 2 (ชั้นหลาน)
                </p>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            {{-- รวมทั้งหมด --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-purple-100 dark:border-purple-900/50">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xl">💰</span>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">รวมทั้งหมด</span>
                </div>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    {{ number_format($stats['total'], 2) }}
                    <span class="text-sm font-normal text-gray-500">บาท</span>
                </p>
            </div>
            {{-- Level 1 --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-green-100 dark:border-green-900/50">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xl">🤝</span>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Level 1 สายตรง</span>
                </div>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ number_format($stats['level1'], 2) }}
                    <span class="text-sm font-normal text-gray-500">บาท</span>
                </p>
            </div>
            {{-- Level 2 --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-amber-100 dark:border-amber-900/50">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xl">👶</span>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Level 2 หลาน</span>
                </div>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                    {{ number_format($stats['level2'], 2) }}
                    <span class="text-sm font-normal text-gray-500">บาท</span>
                </p>
            </div>
            {{-- เดือนนี้ --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-blue-100 dark:border-blue-900/50">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xl">📅</span>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">เดือนนี้</span>
                </div>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ number_format($stats['this_month'], 2) }}
                    <span class="text-sm font-normal text-gray-500">บาท</span>
                </p>
            </div>
        </div>

        {{-- Filter Tabs --}}
        <div class="flex gap-2 mb-6">
            <a href="{{ route('user.fortune-referral.commissions', ['level' => 'all']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-all
                      {{ $levelFilter === 'all' ? 'bg-purple-600 text-white shadow-lg' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}">
                ทั้งหมด
            </a>
            <a href="{{ route('user.fortune-referral.commissions', ['level' => '1']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-all
                      {{ $levelFilter === '1' ? 'bg-green-600 text-white shadow-lg' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}">
                🤝 Level 1 สายตรง
            </a>
            <a href="{{ route('user.fortune-referral.commissions', ['level' => '2']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-all
                      {{ $levelFilter === '2' ? 'bg-amber-600 text-white shadow-lg' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-amber-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}">
                👶 Level 2 หลาน
            </a>
        </div>

        {{-- Commission Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">วันที่</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จากใคร</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ชั้น</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ประเภท</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ราคาดูดวง</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">คอมมิชชั่น</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($commissions as $commission)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    {{ $commission->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $commission->fromUser->profile_picture_url ?? 'https://ui-avatars.com/api/?name=U&background=6366f1&color=fff&size=32' }}"
                                             alt="" class="w-8 h-8 rounded-full object-cover">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $commission->fromUser->name ?? 'ไม่ทราบ' }}</p>
                                            <p class="text-xs text-gray-500">บิล #{{ $commission->fortune_reading_id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($commission->level === 1)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                            L1 สายตรง
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                                            L2 หลาน
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-gray-600 dark:text-gray-400">
                                    {{ $commission->commission_type === 'fixed' ? 'คงที่' : $commission->commission_rate . '%' }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">
                                    {{ number_format($commission->reading_price, 2) }} บาท
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                        +{{ number_format($commission->amount, 2) }} บาท
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @switch($commission->status)
                                        @case('paid')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300">จ่ายแล้ว</span>
                                            @break
                                        @case('pending')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300">รอดำเนินการ</span>
                                            @break
                                        @case('approved')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">อนุมัติ</span>
                                            @break
                                        @default
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">ปฏิเสธ</span>
                                    @endswitch
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <span class="text-5xl">🔮</span>
                                        <p class="text-gray-500 dark:text-gray-400">ยังไม่มีคอมมิชชั่นดูดวง</p>
                                        <a href="{{ route('user.fortune-referral.recruit') }}"
                                           class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm transition">
                                            ชวนเพื่อนมาดูดวง
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($commissions->hasPages())
                <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $commissions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
