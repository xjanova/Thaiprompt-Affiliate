@extends('layouts.admin-v3')

@section('title', 'ตำแหน่งการลงทุน')

@push('styles')
<style>
    .investment-gradient {
        background: linear-gradient(135deg, #f59e0b 0%, #10b981 100%);
    }

    .position-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .position-card:hover {
        transform: translateY(-2px);
    }

    .status-badge {
        animation: pulse-soft 2s ease-in-out infinite;
    }

    @keyframes pulse-soft {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.9;
        }
    }

    .filter-card {
        backdrop-filter: blur(10px);
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

    .gold-shine {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%);
        background-size: 200% 200%;
        animation: gradient-shift 3s ease infinite;
    }

    @keyframes gradient-shift {
        0%, 100% {
            background-position: 0% 50%;
        }
        50% {
            background-position: 100% 50%;
        }
    }
</style>
@endpush

@section('content')
<div x-data="{ showFilters: true }">
    <!-- Header -->
    <div class="mb-8 relative overflow-hidden rounded-2xl investment-gradient p-8 shadow-2xl">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full -ml-32 -mb-32"></div>

        <div class="relative z-10">
            <h2 class="text-3xl font-bold text-white mb-2 flex items-center">
                <i class="fas fa-layer-group mr-3 text-yellow-300"></i>
                ตำแหน่งการลงทุนทั้งหมด
            </h2>
            <p class="text-white/90 text-lg">จัดการและติดตามสถานะการลงทุนของผู้ใช้</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @php
            $totalPositions = $positions->total();
            $pendingCount = \App\Models\StakingPosition::where('status', 'pending')->count();
            $activeCount = \App\Models\StakingPosition::where('status', 'active')->count();
            $completedCount = \App\Models\StakingPosition::where('status', 'completed')->count();
        @endphp

        <!-- Total Positions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-blue-500 position-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($totalPositions) }}</h3>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-layer-group text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-amber-500 position-card gold-shine">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">รอดำเนินการ</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($pendingCount) }}</h3>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center status-badge">
                    <i class="fas fa-clock text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Active -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500 position-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">กำลังดำเนินการ</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($activeCount) }}</h3>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-purple-500 position-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">เสร็จสิ้น</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($completedCount) }}</h3>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                </div>
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

        <form method="GET" action="{{ route('admin.investments.positions.index') }}" x-show="showFilters" x-collapse>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        สถานะ
                    </label>
                    <select name="status"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                        <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>เสร็จสิ้น</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                    </select>
                </div>

                <!-- Plan Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        แผนการลงทุน
                    </label>
                    <select name="plan_id"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                        <option value="">ทั้งหมด</option>
                        @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name_th ?? $plan->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Search -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ค้นหาผู้ใช้
                    </label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="ชื่อ หรือ อีเมล"
                               class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-4 space-x-3">
                <a href="{{ route('admin.investments.positions.index') }}"
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

    <!-- Positions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-amber-50 to-green-50 dark:from-gray-900 dark:to-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">แผน</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">จำนวนเงิน</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ผลตอบแทน</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($positions as $position)
                    <tr class="hover:bg-amber-50/30 dark:hover:bg-gray-700/30 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono font-semibold text-gray-900 dark:text-white">#{{ $position->id }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-green-500 rounded-full flex items-center justify-center mr-3 text-white font-bold">
                                    {{ substr($position->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $position->user->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $position->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-400 to-green-500 flex items-center justify-center mr-3 shimmer">
                                    <i class="{{ $position->investmentPlan->icon ?? 'fas fa-chart-line' }} text-white"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $position->investmentPlan->name_th ?? $position->investmentPlan->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $position->investmentPlan->roi_rate }}% /
                                        @if($position->investmentPlan->roi_frequency === 'daily')
                                        วัน
                                        @elseif($position->investmentPlan->roi_frequency === 'weekly')
                                        สัปดาห์
                                        @else
                                        เดือน
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-lg font-bold text-gray-900 dark:text-white">฿{{ number_format($position->amount, 2) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">
                                <div class="font-bold text-green-600 dark:text-green-400">
                                    ฿{{ number_format($position->total_earned ?? 0, 2) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    คาดการณ์: ฿{{ number_format($position->expected_total_return ?? 0, 2) }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($position->status === 'pending')
                            <span class="px-3 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 status-badge">
                                <i class="fas fa-clock mr-1"></i>รอดำเนินการ
                            </span>
                            @elseif($position->status === 'active')
                            <span class="px-3 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                <i class="fas fa-chart-line mr-1"></i>กำลังดำเนินการ
                            </span>
                            @elseif($position->status === 'completed')
                            <span class="px-3 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400">
                                <i class="fas fa-check-circle mr-1"></i>เสร็จสิ้น
                            </span>
                            @elseif($position->status === 'cancelled')
                            <span class="px-3 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                <i class="fas fa-times-circle mr-1"></i>ยกเลิก
                            </span>
                            @else
                            <span class="px-3 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-400">
                                {{ $position->status }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="text-gray-900 dark:text-white">{{ $position->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $position->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('admin.investments.positions.show', $position) }}"
                               class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 transform hover:scale-105">
                                <i class="fas fa-eye mr-1"></i>ดูรายละเอียด
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-inbox text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                <p class="text-gray-500 dark:text-gray-400 text-lg">ไม่พบข้อมูลการลงทุน</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($positions->hasPages())
        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $positions->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
