@extends('layouts.user-arrow-x')

@section('title', 'คอมมิชชั่น')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Premium Hero Header (Purple-Pink-Red for Commissions) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 dark:from-purple-800 dark:via-pink-800 dark:to-red-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-coins"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-wallet text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">คอมมิชชั่นของฉัน</h1>
                    <p class="text-purple-100 text-lg mt-1">ดูประวัติคอมมิชชั่นจากทุกระบบ (MLM และ Marketplace)</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards with Premium Gradients --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Total Commissions Card (Purple-Indigo) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-purple-500 to-indigo-600 dark:from-purple-600 dark:to-indigo-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-chart-bar text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">📊</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-chart-bar text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $stats['total'] }}</p>
                <p class="text-sm text-purple-100">ทั้งหมด</p>
            </div>
        </div>

        {{-- Pending Commissions Card (Yellow-Orange) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-yellow-500 to-orange-600 dark:from-yellow-600 dark:to-orange-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-clock text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">⏳</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $stats['pending'] }}</p>
                <p class="text-sm text-yellow-100">รอดำเนินการ</p>
            </div>
        </div>

        {{-- Approved Commissions Card (Green-Emerald) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-check-circle text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">✅</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $stats['approved'] }}</p>
                <p class="text-sm text-green-100">อนุมัติแล้ว</p>
            </div>
        </div>

        {{-- Paid Commissions Card (Blue-Cyan) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-blue-500 to-cyan-600 dark:from-blue-600 dark:to-cyan-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-money-bill-wave text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">💰</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-money-bill-wave text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $stats['paid'] }}</p>
                <p class="text-sm text-blue-100">จ่ายแล้ว</p>
            </div>
        </div>
    </div>

    {{-- Commissions Table --}}
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl overflow-hidden">
        @if($commissions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                    <thead class="bg-white/60 dark:bg-white/10">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                วันที่
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                แหล่งที่มา
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                ประเภท
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                จำนวน
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                %
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                สถานะ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                หมายเหตุ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach($commissions as $commission)
                            <tr class="hover:bg-white/80 dark:hover:bg-white/10 transition-all duration-200">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-medium">
                                    {{ \Carbon\Carbon::parse($commission->created_at)->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($commission->source === 'mlm')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-xs font-bold">
                                            <i class="fas fa-network-wired"></i> MLM
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-xs font-bold">
                                            <i class="fas fa-store"></i> Marketplace
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs">
                                        @php
                                            $typeLabels = [
                                                'direct' => 'Direct',
                                                'unilevel_direct' => 'Unilevel Direct',
                                                'unilevel_indirect' => 'Unilevel Indirect',
                                                'binary_pair' => 'Binary Pair',
                                                'binary_matching' => 'Binary Matching',
                                                'sponsor_bonus' => 'Sponsor Bonus',
                                                'rank_bonus' => 'Rank Bonus',
                                                'leadership_bonus' => 'Leadership Bonus',
                                                'matching_bonus' => 'Matching Bonus',
                                                'pool_bonus' => 'Pool Bonus',
                                                'mlm_unilevel' => 'MLM Unilevel',
                                                'mlm_binary' => 'MLM Binary',
                                                'bonus' => 'Bonus',
                                            ];
                                        @endphp
                                        {{ $typeLabels[$commission->type] ?? ucfirst(str_replace('_', ' ', $commission->type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                    ฿{{ number_format($commission->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $commission->percentage ? $commission->percentage . '%' : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($commission->status === 'pending')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-gradient-to-r from-yellow-100 to-amber-100 dark:from-yellow-900/30 dark:to-amber-900/30 text-yellow-800 dark:text-yellow-300">
                                            <i class="fas fa-clock"></i>
                                            รอดำเนินการ
                                        </span>
                                    @elseif($commission->status === 'approved')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-800 dark:text-green-300">
                                            <i class="fas fa-check"></i>
                                            อนุมัติแล้ว
                                        </span>
                                    @elseif($commission->status === 'paid')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 text-blue-800 dark:text-blue-300">
                                            <i class="fas fa-check-double"></i>
                                            จ่ายแล้ว
                                        </span>
                                    @elseif($commission->status === 'cancelled')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-gradient-to-r from-gray-100 to-slate-100 dark:from-gray-900/30 dark:to-slate-900/30 text-gray-800 dark:text-gray-300">
                                            <i class="fas fa-ban"></i>
                                            ยกเลิก
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-800 dark:text-red-300">
                                            <i class="fas fa-times"></i>
                                            ปฏิเสธ
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                    {{ $commission->notes ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-200 dark:border-white/10">
                {{ $commissions->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <div class="inline-block p-6 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/20 dark:to-pink-900/20 rounded-full mb-6">
                    <i class="fas fa-inbox text-6xl text-purple-600 dark:text-purple-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีคอมมิชชั่น</h3>
                <p class="text-gray-600 dark:text-gray-400">เมื่อมีคอมมิชชั่นเข้ามาจะแสดงที่นี่</p>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-20px);
    }
}
</style>
@endpush
@endsection
