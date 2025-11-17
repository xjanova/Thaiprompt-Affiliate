@extends('layouts.admin-v3')

@section('title', 'การจ่าย ROI')

@push('styles')
<style>
    .investment-gradient {
        background: linear-gradient(135deg, #f59e0b 0%, #10b981 100%);
    }

    .distribution-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .distribution-card:hover {
        transform: translateY(-2px);
    }

    .money-rain {
        position: absolute;
        width: 100%;
        height: 100%;
        overflow: hidden;
        pointer-events: none;
    }

    .money-icon {
        position: absolute;
        font-size: 20px;
        opacity: 0.3;
        animation: fall linear infinite;
    }

    @keyframes fall {
        0% {
            transform: translateY(-100%) rotate(0deg);
        }
        100% {
            transform: translateY(100vh) rotate(360deg);
        }
    }

    .shimmer {
        position: relative;
        overflow: hidden;
    }

    .shimmer::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        to {
            left: 100%;
        }
    }

    .pulse-gold {
        animation: pulse-gold 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse-gold {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
    }

    .count-up {
        display: inline-block;
        transition: all 0.5s ease;
    }

    .filter-card {
        backdrop-filter: blur(10px);
    }
</style>
@endpush

@section('content')
<div x-data="{
    showFilters: true,
    showTriggerModal: false,
    showRetryModal: false,
    animateStats: true
}" x-init="setTimeout(() => animateStats = false, 2000)">

    <!-- Header with Money Rain Effect -->
    <div class="mb-8 relative overflow-hidden rounded-2xl investment-gradient p-8 shadow-2xl">
        <div class="money-rain">
            <i class="money-icon fas fa-coins" style="left: 10%; animation-duration: 4s;"></i>
            <i class="money-icon fas fa-dollar-sign" style="left: 30%; animation-duration: 6s; animation-delay: 1s;"></i>
            <i class="money-icon fas fa-money-bill-wave" style="left: 50%; animation-duration: 5s; animation-delay: 0.5s;"></i>
            <i class="money-icon fas fa-gem" style="left: 70%; animation-duration: 7s; animation-delay: 2s;"></i>
            <i class="money-icon fas fa-coins" style="left: 90%; animation-duration: 4.5s; animation-delay: 1.5s;"></i>
        </div>

        <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full -ml-32 -mb-32"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-3xl font-bold text-white mb-2 flex items-center">
                    <i class="fas fa-hand-holding-usd mr-3 text-yellow-300 pulse-gold"></i>
                    การจ่าย ROI
                </h2>
                <p class="text-white/90 text-lg">จัดการและติดตามการจ่ายผลตอบแทนให้นักลงทุน</p>
            </div>

            <div class="mt-4 md:mt-0 flex flex-wrap gap-3">
                <button @click="showTriggerModal = true"
                        class="px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg transform hover:scale-105">
                    <i class="fas fa-play-circle mr-2"></i>จ่าย ROI ตอนนี้
                </button>
                <button @click="showRetryModal = true"
                        class="px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg transform hover:scale-105">
                    <i class="fas fa-redo mr-2"></i>ลองใหม่ที่ล้มเหลว
                </button>
                <form action="{{ route('admin.investments.positions.process-mature') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            class="px-6 py-3 bg-purple-500 hover:bg-purple-600 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg transform hover:scale-105">
                        <i class="fas fa-cog mr-2"></i>ประมวลผลครบกำหนด
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @php
            $totalDistributions = $distributions->total();
            $completedCount = \App\Models\RoiDistribution::where('status', 'completed')->count();
            $pendingCount = \App\Models\RoiDistribution::where('status', 'pending')->count();
            $failedCount = \App\Models\RoiDistribution::where('status', 'failed')->count();
            $totalPaid = \App\Models\RoiDistribution::where('status', 'completed')->sum('amount');
        @endphp

        <!-- Total Distributions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-blue-500 distribution-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white count-up">{{ number_format($totalDistributions) }}</h3>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-list text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500 distribution-card shimmer">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">สำเร็จ</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white count-up">{{ number_format($completedCount) }}</h3>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-amber-500 distribution-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">รอดำเนินการ</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white count-up">{{ number_format($pendingCount) }}</h3>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center pulse-gold">
                    <i class="fas fa-clock text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Failed -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-red-500 distribution-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ล้มเหลว</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white count-up">{{ number_format($failedCount) }}</h3>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-red-400 to-red-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Paid Banner -->
    <div class="mb-8 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-8 shadow-2xl shimmer">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-lg mb-2">ยอดจ่ายรวมทั้งหมด</p>
                <h2 class="text-4xl font-bold text-white flex items-center">
                    <i class="fas fa-money-bill-wave mr-4 text-yellow-300"></i>
                    ฿{{ number_format($totalPaid, 2) }}
                </h2>
            </div>
            <div class="hidden md:block">
                <i class="fas fa-chart-line text-white/20 text-9xl"></i>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-xl shadow-lg p-6 mb-6 filter-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-filter mr-2 text-amber-500"></i>
                กรองข้อมูล
            </h3>
            <button @click="showFilters = !showFilters"
                    class="text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>

        <form method="GET" action="{{ route('admin.investments.distributions.index') }}" x-show="showFilters" x-collapse>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        สถานะ
                    </label>
                    <select name="status"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                        <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
                    </select>
                </div>

                <!-- Date Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        วันที่
                    </label>
                    <input type="date" name="date" value="{{ request('date') }}"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                </div>
            </div>

            <div class="flex justify-end mt-4 space-x-3">
                <a href="{{ route('admin.investments.distributions.index') }}"
                   class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all">
                    <i class="fas fa-redo mr-2"></i>รีเซ็ต
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-amber-500 to-green-500 text-white rounded-lg hover:from-amber-600 hover:to-green-600 transition-all shadow-lg">
                    <i class="fas fa-search mr-2"></i>ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Distributions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-amber-50 to-green-50 dark:from-gray-900 dark:to-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">การลงทุน</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">จำนวนเงิน</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">วันที่จ่าย</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">กระเป๋า</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($distributions as $distribution)
                    <tr class="hover:bg-green-50/30 dark:hover:bg-gray-700/30 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono font-semibold text-gray-900 dark:text-white">#{{ $distribution->id }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mr-3 text-white font-bold">
                                    {{ substr($distribution->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $distribution->user->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $distribution->user->email ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($distribution->stakingPosition)
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-400 to-green-500 flex items-center justify-center mr-3">
                                    <i class="{{ $distribution->stakingPosition->investmentPlan->icon ?? 'fas fa-chart-line' }} text-white"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $distribution->stakingPosition->investmentPlan->name_th ?? $distribution->stakingPosition->investmentPlan->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Position #{{ $distribution->staking_position_id }}
                                    </div>
                                </div>
                            </div>
                            @else
                            <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <i class="fas fa-coins text-green-500 mr-2"></i>
                                <span class="text-xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($distribution->amount, 2) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $distribution->distribution_date->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $distribution->distribution_date->format('H:i') }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($distribution->status === 'completed')
                            <span class="px-3 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                <i class="fas fa-check-circle mr-1"></i>สำเร็จ
                            </span>
                            @elseif($distribution->status === 'pending')
                            <span class="px-3 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 pulse-gold">
                                <i class="fas fa-clock mr-1"></i>รอดำเนินการ
                            </span>
                            @elseif($distribution->status === 'failed')
                            <span class="px-3 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                <i class="fas fa-exclamation-triangle mr-1"></i>ล้มเหลว
                            </span>
                            @else
                            <span class="px-3 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-400">
                                {{ $distribution->status }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $distribution->wallet->type ?? '-' }}</div>
                                @if($distribution->wallet)
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    ฿{{ number_format($distribution->wallet->balance ?? 0, 2) }}
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-inbox text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                <p class="text-gray-500 dark:text-gray-400 text-lg">ไม่พบข้อมูลการจ่าย ROI</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($distributions->hasPages())
        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $distributions->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

    <!-- Trigger Distribution Modal -->
    <div x-show="showTriggerModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showTriggerModal"
                 x-transition
                 class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75"
                 @click="showTriggerModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="showTriggerModal"
                 x-transition
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/30 sm:mx-0">
                        <i class="fas fa-play-circle text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white">
                            ยืนยันการจ่าย ROI
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                คุณแน่ใจหรือไม่ว่าต้องการจ่าย ROI ตอนนี้? ระบบจะประมวลผลการจ่ายทั้งหมดที่ถึงกำหนด
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <form action="{{ route('admin.investments.distributions.trigger') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-lg px-4 py-2 bg-green-600 text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-play-circle mr-2"></i>จ่าย ROI
                        </button>
                    </form>
                    <button @click="showTriggerModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">
                        ยกเลิก
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Retry Failed Modal -->
    <div x-show="showRetryModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showRetryModal"
                 x-transition
                 class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75"
                 @click="showRetryModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="showRetryModal"
                 x-transition
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 dark:bg-amber-900/30 sm:mx-0">
                        <i class="fas fa-redo text-amber-600 dark:text-amber-400 text-xl"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white">
                            ลองใหม่การจ่ายที่ล้มเหลว
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                คุณแน่ใจหรือไม่ว่าต้องการลองจ่าย ROI ที่ล้มเหลวใหม่? ระบบจะประมวลผลทั้งหมดที่มีสถานะล้มเหลว
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <form action="{{ route('admin.investments.distributions.retry') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-lg px-4 py-2 bg-amber-600 text-white hover:bg-amber-700 sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-redo mr-2"></i>ลองใหม่
                        </button>
                    </form>
                    <button @click="showRetryModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">
                        ยกเลิก
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Animated number counting
    document.addEventListener('DOMContentLoaded', function() {
        const countUpElements = document.querySelectorAll('.count-up');

        countUpElements.forEach(element => {
            const target = parseInt(element.textContent.replace(/,/g, ''));
            const duration = 1500;
            const step = target / (duration / 16);
            let current = 0;

            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current).toLocaleString();
            }, 16);
        });
    });
</script>
@endpush
@endsection
