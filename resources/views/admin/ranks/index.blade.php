@extends('layouts.admin-v3')

@section('title', 'จัดการระดับ Rank')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-200">
                    🏆 จัดการระดับ Rank
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">
                    กำหนดและจัดการระดับ Rank สำหรับระบบ Affiliate
                </p>
            </div>
            <a href="{{ route('admin.ranks.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700 text-white rounded-xl transition-all duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                สร้าง Rank ใหม่
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">จำนวน Rank</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-200 mt-1">{{ $ranks->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-r from-pink-100 to-rose-100 dark:from-pink-900 dark:to-rose-900 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">🏆</span>
                </div>
            </div>
        </div>

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">Rank ที่เปิดใช้งาน</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-200 mt-1">{{ $ranks->where('is_active', true)->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900 dark:to-emerald-900 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">✅</span>
                </div>
            </div>
        </div>

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">ผู้ใช้ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-200 mt-1">{{ $ranks->sum('users_count') }}</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">👥</span>
                </div>
            </div>
        </div>

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">Rank สูงสุด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-200 mt-1">Level {{ $ranks->max('level') ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-r from-yellow-100 to-orange-100 dark:from-yellow-900 dark:to-orange-900 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">⭐</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Ranks List -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                            Level
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                            ชื่อ Rank
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                            Commission Rate
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                            ข้อกำหนด
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                            ผู้ใช้
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="glass-fusion dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($ranks as $rank)
                    <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-200">{{ $rank->level }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-xl flex items-center justify-center"
                                     style="background: linear-gradient(135deg, {{ $rank->color }}, {{ $rank->color }}99);">
                                    <span class="text-white font-bold text-lg">{{ substr($rank->name, 0, 1) }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $rank->name }}</div>
                                    @if($rank->name_th)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $rank->name_th }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $rank->commission_rate }}%</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">x{{ $rank->bonus_multiplier }} bonus</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs space-y-1">
                                <div class="flex items-center text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                    <span class="mr-1">💰</span>
                                    <span>฿{{ number_format($rank->min_sales) }} ยอดขาย</span>
                                </div>
                                <div class="flex items-center text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                    <span class="mr-1">👥</span>
                                    <span>{{ $rank->min_referrals }} referrals</span>
                                </div>
                                <div class="flex items-center text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                    <span class="mr-1">⭐</span>
                                    <span>{{ $rank->min_points }} points</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $rank->users_count }} คน
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($rank->is_active)
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                เปิดใช้งาน
                            </span>
                            @else
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-900 dark:text-gray-200">
                                ปิดใช้งาน
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.ranks.edit', $rank) }}"
                                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                @if($rank->users_count == 0)
                                <form action="{{ route('admin.ranks.destroy', $rank) }}" method="POST" class="inline-block"
                                      onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ Rank นี้?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <span class="text-6xl mb-4">🏆</span>
                                <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-lg mb-2">ยังไม่มี Rank ในระบบ</p>
                                <p class="text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm mb-4">เริ่มต้นสร้าง Rank เพื่อจัดระดับผู้ใช้ในระบบ</p>
                                <a href="{{ route('admin.ranks.create') }}"
                                   class="px-4 py-2 bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700 text-white rounded-xl transition-all duration-200">
                                    สร้าง Rank แรก
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
