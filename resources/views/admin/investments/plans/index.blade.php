@extends('layouts.admin')

@section('title', 'แผนการลงทุน')

@push('styles')
<style>
    /* Investment Gold/Green Theme with Dark Mode */
    .investment-gradient {
        background: linear-gradient(135deg, #f59e0b 0%, #10b981 100%);
    }

    .investment-gradient-dark {
        background: linear-gradient(135deg, #d97706 0%, #059669 100%);
    }

    .gold-glow {
        box-shadow: 0 4px 20px rgba(245, 158, 11, 0.3);
    }

    .dark .gold-glow {
        box-shadow: 0 4px 20px rgba(217, 119, 6, 0.4);
    }

    .green-glow {
        box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
    }

    .dark .green-glow {
        box-shadow: 0 4px 20px rgba(5, 150, 105, 0.4);
    }

    .plan-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .plan-card:hover {
        transform: translateY(-4px);
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
</style>
@endpush

@section('content')
<div x-data="{
    showDeleteModal: false,
    planToDelete: null,
    animateNumbers: true
}"
x-init="setTimeout(() => animateNumbers = false, 2000)">

    <!-- Header Section with Gradient -->
    <div class="mb-8 relative overflow-hidden rounded-2xl investment-gradient dark:investment-gradient-dark p-8 shadow-2xl">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="mb-4 md:mb-0">
                <h2 class="text-3xl font-bold text-white mb-2 flex items-center">
                    <i class="fas fa-coins mr-3 text-yellow-300 pulse-gold"></i>
                    จัดการแผนการลงทุน
                </h2>
                <p class="text-white/90 text-lg">สร้างและจัดการแผนการลงทุนสำหรับผู้ใช้</p>
            </div>

            <a href="{{ route('admin.investments.plans.create') }}"
               class="inline-flex items-center px-6 py-3 bg-white text-amber-600 font-semibold rounded-xl hover:bg-amber-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="fas fa-plus-circle mr-2"></i>
                สร้างแผนใหม่
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Plans -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-amber-500 gold-glow plan-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">แผนทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $plans->total() }}</h3>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-briefcase text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Plans -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500 green-glow plan-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">แผนที่เปิดใช้งาน</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $plans->where('is_active', true)->where('deleted_at', null)->count() }}</h3>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Featured Plans -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-purple-500 plan-card" style="box-shadow: 0 4px 20px rgba(168, 85, 247, 0.3);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">แผนแนะนำ</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $plans->where('is_featured', true)->count() }}</h3>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-star text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Inactive Plans -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-gray-400 plan-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">แผนที่ปิดใช้งาน</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $plans->where('is_active', false)->count() + $plans->where('deleted_at', '!=', null)->count() }}</h3>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-gray-400 to-gray-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-ban text-white text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Plans Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-amber-50 to-green-50 dark:from-gray-900 dark:to-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">แผน</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">จำนวนเงิน</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ROI</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ระยะเวลา</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($plans as $plan)
                    <tr class="hover:bg-amber-50/50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-amber-400 to-green-500 flex items-center justify-center mr-4 shimmer">
                                    <i class="{{ $plan->icon ?? 'fas fa-chart-line' }} text-white text-xl"></i>
                                </div>
                                <div>
                                    <div class="flex items-center">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $plan->name_th ?? $plan->name }}</div>
                                        @if($plan->is_featured)
                                        <span class="ml-2 px-2 py-1 text-xs font-semibold bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-full">
                                            <i class="fas fa-star mr-1"></i>แนะนำ
                                        </span>
                                        @endif
                                    </div>
                                    @if($plan->description_th || $plan->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($plan->description_th ?? $plan->description, 50) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">
                                <div class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($plan->min_amount, 0) }}</div>
                                @if($plan->max_amount)
                                <div class="text-xs text-gray-500 dark:text-gray-400">ถึง ฿{{ number_format($plan->max_amount, 0) }}</div>
                                @else
                                <div class="text-xs text-green-600 dark:text-green-400">ไม่จำกัด</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($plan->roi_rate, 2) }}%</div>
                                <div class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                    @if($plan->roi_frequency === 'daily')
                                    /วัน
                                    @elseif($plan->roi_frequency === 'weekly')
                                    /สัปดาห์
                                    @else
                                    /เดือน
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $plan->term_days }} วัน</div>
                                @if($plan->lock_days > 0)
                                <div class="text-xs text-amber-600 dark:text-amber-400">
                                    <i class="fas fa-lock mr-1"></i>ล็อค {{ $plan->lock_days }} วัน
                                </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($plan->deleted_at)
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                <i class="fas fa-trash mr-1"></i>ถูกลบ
                            </span>
                            @elseif($plan->is_active)
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                <i class="fas fa-check-circle mr-1"></i>เปิดใช้งาน
                            </span>
                            @else
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-400">
                                <i class="fas fa-pause-circle mr-1"></i>ปิดใช้งาน
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.investments.plans.edit', $plan) }}"
                                   class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 transform hover:scale-105">
                                    <i class="fas fa-edit mr-1"></i>แก้ไข
                                </a>
                                @if(!$plan->deleted_at)
                                <button @click="showDeleteModal = true; planToDelete = {{ $plan->id }}"
                                        class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all duration-200 transform hover:scale-105">
                                    <i class="fas fa-trash mr-1"></i>ลบ
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-inbox text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                <p class="text-gray-500 dark:text-gray-400 text-lg">ยังไม่มีแผนการลงทุน</p>
                                <a href="{{ route('admin.investments.plans.create') }}"
                                   class="mt-4 inline-flex items-center px-6 py-3 bg-gradient-to-r from-amber-500 to-green-500 text-white font-semibold rounded-xl hover:from-amber-600 hover:to-green-600 transition-all duration-300 shadow-lg transform hover:scale-105">
                                    <i class="fas fa-plus-circle mr-2"></i>
                                    สร้างแผนแรก
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($plans->hasPages())
        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $plans->links() }}
        </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
                 @click="showDeleteModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white" id="modal-title">
                            ยืนยันการลบแผนการลงทุน
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                คุณแน่ใจหรือไม่ว่าต้องการลบแผนการลงทุนนี้? การดำเนินการนี้ไม่สามารถยกเลิกได้
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <form :action="`{{ route('admin.investments.plans.index') }}/${planToDelete}`" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-200">
                            <i class="fas fa-trash mr-2"></i>ลบแผน
                        </button>
                    </form>
                    <button type="button"
                            @click="showDeleteModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:mt-0 sm:w-auto sm:text-sm transition-all duration-200">
                        ยกเลิก
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
