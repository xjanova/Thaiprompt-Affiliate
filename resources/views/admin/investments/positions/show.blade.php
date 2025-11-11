@extends('layouts.admin')

@section('title', 'รายละเอียดการลงทุน #' . $position->id)

@push('styles')
<style>
    .investment-gradient {
        background: linear-gradient(135deg, #f59e0b 0%, #10b981 100%);
    }

    .stat-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .stat-card:hover {
        transform: translateY(-4px);
    }

    .timeline-item {
        position: relative;
        padding-left: 2rem;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: 0.5rem;
        top: 2rem;
        bottom: -1rem;
        width: 2px;
        background: linear-gradient(to bottom, #f59e0b, #10b981);
    }

    .timeline-item:last-child::before {
        display: none;
    }

    .timeline-dot {
        position: absolute;
        left: 0;
        top: 0.5rem;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pulse-ring {
        animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse-ring {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.5;
            transform: scale(1.1);
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

    .progress-bar {
        transition: width 1s ease-in-out;
    }
</style>
@endpush

@section('content')
<div x-data="{
    showApproveModal: false,
    showRejectModal: false,
    rejectReason: '',
    progress: {{ $position->status === 'active' && $position->investmentPlan ? (now()->diffInDays($position->start_date ?? $position->created_at) / $position->investmentPlan->term_days * 100) : 0 }}
}" x-init="setTimeout(() => { if (progress > 100) progress = 100 }, 100)">

    <!-- Back Button & Header -->
    <div class="mb-8">
        <a href="{{ route('admin.investments.positions.index') }}"
           class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors mb-4">
            <i class="fas fa-arrow-left mr-2"></i>
            กลับไปยังรายการทั้งหมด
        </a>

        <div class="relative overflow-hidden rounded-2xl investment-gradient p-8 shadow-2xl">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2 flex items-center">
                        <i class="fas fa-coins mr-3 text-yellow-300"></i>
                        การลงทุน #{{ $position->id }}
                    </h2>
                    <p class="text-white/90 text-lg">{{ $position->investmentPlan->name_th ?? $position->investmentPlan->name }}</p>
                </div>

                <div class="mt-4 md:mt-0 flex space-x-3">
                    @if($position->status === 'pending')
                    <button @click="showApproveModal = true"
                            class="px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg transform hover:scale-105">
                        <i class="fas fa-check mr-2"></i>อนุมัติ
                    </button>
                    <button @click="showRejectModal = true"
                            class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg transform hover:scale-105">
                        <i class="fas fa-times mr-2"></i>ปฏิเสธ
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Investment Amount -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-amber-500 stat-card">
                    <div class="flex items-center justify-between mb-3">
                        <i class="fas fa-wallet text-3xl text-amber-500"></i>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">จำนวนเงิน</span>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($position->amount, 2) }}</div>
                </div>

                <!-- Total Earned -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500 stat-card shimmer">
                    <div class="flex items-center justify-between mb-3">
                        <i class="fas fa-chart-line text-3xl text-green-500"></i>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">รายได้รวม</span>
                    </div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($position->total_earned ?? 0, 2) }}</div>
                </div>

                <!-- Expected Return -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-purple-500 stat-card">
                    <div class="flex items-center justify-between mb-3">
                        <i class="fas fa-trophy text-3xl text-purple-500"></i>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">คาดการณ์</span>
                    </div>
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">฿{{ number_format($position->expected_total_return ?? 0, 2) }}</div>
                </div>
            </div>

            <!-- Investment Details -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-8">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <i class="fas fa-info-circle mr-3 text-blue-500"></i>
                    รายละเอียดการลงทุน
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">แผนการลงทุน</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $position->investmentPlan->name_th ?? $position->investmentPlan->name }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">อัตรา ROI</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">{{ $position->investmentPlan->roi_rate }}%</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">ความถี่การจ่าย</span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                @if($position->investmentPlan->roi_frequency === 'daily')
                                รายวัน
                                @elseif($position->investmentPlan->roi_frequency === 'weekly')
                                รายสัปดาห์
                                @else
                                รายเดือน
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">ระยะเวลา</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $position->investmentPlan->term_days }} วัน</span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">วันที่เริ่มต้น</span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{ $position->start_date ? $position->start_date->format('d/m/Y H:i') : '-' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">วันที่สิ้นสุด</span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{ $position->end_date ? $position->end_date->format('d/m/Y H:i') : '-' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">สถานะ</span>
                            <span>
                                @if($position->status === 'pending')
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 pulse-ring">
                                    <i class="fas fa-clock mr-1"></i>รอดำเนินการ
                                </span>
                                @elseif($position->status === 'active')
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                    <i class="fas fa-chart-line mr-1"></i>กำลังดำเนินการ
                                </span>
                                @elseif($position->status === 'completed')
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400">
                                    <i class="fas fa-check-circle mr-1"></i>เสร็จสิ้น
                                </span>
                                @elseif($position->status === 'cancelled')
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                    <i class="fas fa-times-circle mr-1"></i>ยกเลิก
                                </span>
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">กระเป๋าเงิน</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $position->wallet->type ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                @if($position->status === 'active')
                <div class="mt-8">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">ความคืบหน้า</span>
                        <span class="text-sm font-bold text-amber-600 dark:text-amber-400" x-text="Math.min(progress, 100).toFixed(1) + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                        <div class="progress-bar h-full bg-gradient-to-r from-amber-500 to-green-500 rounded-full shimmer"
                             :style="`width: ${Math.min(progress, 100)}%`"></div>
                    </div>
                </div>
                @endif
            </div>

            <!-- ROI Distribution History -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-8">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <i class="fas fa-history mr-3 text-green-500"></i>
                    ประวัติการจ่าย ROI
                </h3>

                <div class="space-y-4">
                    @forelse($position->roiDistributions as $distribution)
                    <div class="timeline-item">
                        <div class="timeline-dot bg-gradient-to-br from-green-400 to-green-600 text-white">
                            <i class="fas fa-check text-xs"></i>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $distribution->distribution_date->format('d/m/Y H:i') }}
                                        </span>
                                        @if($distribution->status === 'completed')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                            <i class="fas fa-check mr-1"></i>สำเร็จ
                                        </span>
                                        @elseif($distribution->status === 'pending')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400">
                                            <i class="fas fa-clock mr-1"></i>รอดำเนินการ
                                        </span>
                                        @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                            <i class="fas fa-times mr-1"></i>ล้มเหลว
                                        </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        กระเป๋า: {{ $distribution->wallet->type ?? '-' }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xl font-bold text-green-600 dark:text-green-400">
                                        +฿{{ number_format($distribution->amount, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                        <p class="text-gray-500 dark:text-gray-400">ยังไม่มีประวัติการจ่าย ROI</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Column - User & Admin Info -->
        <div class="lg:col-span-1 space-y-6">
            <!-- User Information -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-user mr-2 text-blue-500"></i>
                    ข้อมูลผู้ใช้
                </h3>

                <div class="flex items-center mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-amber-400 to-green-500 rounded-full flex items-center justify-center mr-4 text-white font-bold text-2xl">
                        {{ substr($position->user->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $position->user->name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $position->user->email }}</div>
                    </div>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">ID</span>
                        <span class="font-semibold text-gray-900 dark:text-white">#{{ $position->user->id }}</span>
                    </div>
                    @if($position->user->phone)
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">เบอร์โทร</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $position->user->phone }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">สมัครเมื่อ</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $position->user->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>

                <a href="{{ route('admin.users.show', $position->user) }}"
                   class="mt-4 block w-full text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all">
                    <i class="fas fa-external-link-alt mr-2"></i>ดูโปรไฟล์
                </a>
            </div>

            <!-- Referrer Information -->
            @if($position->referrer)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-users mr-2 text-purple-500"></i>
                    ผู้แนะนำ
                </h3>

                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-500 rounded-full flex items-center justify-center mr-3 text-white font-bold">
                        {{ substr($position->referrer->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $position->referrer->name }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $position->referrer->email }}</div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Admin Information -->
            @if($position->approvedBy)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-user-shield mr-2 text-green-500"></i>
                    ผู้อนุมัติ
                </h3>

                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mr-3 text-white font-bold">
                        {{ substr($position->approvedBy->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $position->approvedBy->name }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $position->approved_at?->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Admin Notes -->
            @if($position->admin_notes)
            <div class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 rounded-lg p-4">
                <h4 class="text-sm font-bold text-amber-900 dark:text-amber-400 mb-2 flex items-center">
                    <i class="fas fa-sticky-note mr-2"></i>
                    บันทึกจากแอดมิน
                </h4>
                <p class="text-sm text-amber-800 dark:text-amber-300">{{ $position->admin_notes }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Approve Modal -->
    <div x-show="showApproveModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showApproveModal"
                 x-transition
                 class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75"
                 @click="showApproveModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="showApproveModal"
                 x-transition
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/30 sm:mx-0">
                        <i class="fas fa-check text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white">
                            ยืนยันการอนุมัติการลงทุน
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                คุณแน่ใจหรือไม่ว่าต้องการอนุมัติการลงทุนนี้? การดำเนินการจะเริ่มต้นทันที
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <form action="{{ route('admin.investments.positions.approve', $position) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-lg px-4 py-2 bg-green-600 text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-check mr-2"></i>อนุมัติ
                        </button>
                    </form>
                    <button @click="showApproveModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">
                        ยกเลิก
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div x-show="showRejectModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showRejectModal"
                 x-transition
                 class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75"
                 @click="showRejectModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="showRejectModal"
                 x-transition
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form action="{{ route('admin.investments.positions.reject', $position) }}" method="POST">
                    @csrf
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0">
                            <i class="fas fa-times text-red-600 dark:text-red-400 text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white">
                                ปฏิเสธการลงทุน
                            </h3>
                            <div class="mt-4">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    เหตุผลในการปฏิเสธ <span class="text-red-500">*</span>
                                </label>
                                <textarea name="reason" x-model="rejectReason" required rows="4"
                                          class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                          placeholder="ระบุเหตุผลในการปฏิเสธ..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-lg px-4 py-2 bg-red-600 text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-times mr-2"></i>ปฏิเสธ
                        </button>
                        <button type="button" @click="showRejectModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
