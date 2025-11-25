{{--
    Penalties Index - รายการค่าปรับ/บทลงโทษ
    แสดงรายการค่าปรับทั้งหมดพร้อมการจัดการ
--}}
@extends('layouts.admin')

@section('title', 'จัดการค่าปรับ/บทลงโทษ')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="penaltyManager()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-gavel text-red-500 mr-3"></i>
                    จัดการค่าปรับ/บทลงโทษ
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    รายการค่าปรับและบทลงโทษจากการละเมิดกฎ
                </p>
            </div>
            <a href="{{ route('admin.anti-abuse.dashboard') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับไป Dashboard
            </a>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">รอดำเนินการ</p>
                    <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ $penalties->where('status', 'pending')->count() }}
                    </p>
                </div>
                <div class="h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900 flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">หักเงินแล้ว</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $penalties->where('status', 'charged')->count() }}
                    </p>
                </div>
                <div class="h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ชำระแล้ว</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ $penalties->where('status', 'paid')->count() }}
                    </p>
                </div>
                <div class="h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ยอดรวมค่าปรับ</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                        ฿{{ number_format($penalties->sum('penalty_amount'), 0) }}
                    </p>
                </div>
                <div class="h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
                    <i class="fas fa-coins text-red-600 dark:text-red-400"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" action="{{ route('admin.anti-abuse.penalties') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="charged" {{ request('status') === 'charged' ? 'selected' : '' }}>หักเงินแล้ว</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>ชำระแล้ว</option>
                    <option value="waived" {{ request('status') === 'waived' ? 'selected' : '' }}>ยกเว้น</option>
                </select>
            </div>

            {{-- Penalty Type Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทค่าปรับ</label>
                <select name="penalty_type"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">
                    <option value="">ทั้งหมด</option>
                    @foreach(\App\Models\ServiceBookingPenalty::PENALTY_TYPES as $type => $label)
                        <option value="{{ $type }}" {{ request('penalty_type') === $type ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Penalized Type Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ผู้ถูกปรับ</label>
                <select name="penalized_type"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">
                    <option value="">ทั้งหมด</option>
                    <option value="user" {{ request('penalized_type') === 'user' ? 'selected' : '' }}>ผู้ใช้</option>
                    <option value="provider" {{ request('penalized_type') === 'provider' ? 'selected' : '' }}>ผู้ให้บริการ</option>
                </select>
            </div>

            {{-- Date Range --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ช่วงวันที่</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">
            </div>

            {{-- Search & Buttons --}}
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-search mr-2"></i>
                    ค้นหา
                </button>
                <a href="{{ route('admin.anti-abuse.penalties') }}"
                   class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Penalties Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ผู้ถูกปรับ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ประเภท
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จำนวนเงิน
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            หักคะแนน
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            การจอง
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            วันที่
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($penalties as $penalty)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            {{-- Penalized User/Provider --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        @if($penalty->penalized_type === 'user' && $penalty->user)
                                            <img class="h-10 w-10 rounded-full object-cover"
                                                 src="{{ $penalty->user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($penalty->user->name) }}"
                                                 alt="{{ $penalty->user->name }}">
                                        @elseif($penalty->penalized_type === 'provider' && $penalty->provider)
                                            <img class="h-10 w-10 rounded-full object-cover"
                                                 src="{{ $penalty->provider->logo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($penalty->provider->name ?? 'P') }}"
                                                 alt="{{ $penalty->provider->name ?? 'Provider' }}">
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-500 dark:text-gray-400"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            @if($penalty->penalized_type === 'user' && $penalty->user)
                                                {{ $penalty->user->name }}
                                            @elseif($penalty->penalized_type === 'provider' && $penalty->provider)
                                                {{ $penalty->provider->name ?? 'Provider #' . $penalty->provider_id }}
                                            @else
                                                ไม่พบข้อมูล
                                            @endif
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $penalty->penalized_type === 'user' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                                            {{ $penalty->penalized_type === 'user' ? 'ผู้ใช้' : 'ผู้ให้บริการ' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- Penalty Type --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900 dark:text-white">
                                    {{ $penalty->getPenaltyTypeLabel() }}
                                </span>
                                @if($penalty->reason)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs" title="{{ $penalty->reason }}">
                                        {{ Str::limit($penalty->reason, 30) }}
                                    </p>
                                @endif
                            </td>

                            {{-- Amount --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-lg font-bold text-red-600 dark:text-red-400">
                                    ฿{{ number_format($penalty->penalty_amount, 0) }}
                                </span>
                            </td>

                            {{-- Trust Score Deduction --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($penalty->trust_score_deduction > 0)
                                    <span class="text-sm font-medium text-orange-600 dark:text-orange-400">
                                        -{{ number_format($penalty->trust_score_deduction, 1) }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @php
                                    $statusConfig = [
                                        'pending' => ['label' => 'รอดำเนินการ', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'],
                                        'charged' => ['label' => 'หักเงินแล้ว', 'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
                                        'paid' => ['label' => 'ชำระแล้ว', 'class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
                                        'waived' => ['label' => 'ยกเว้น', 'class' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'],
                                    ];
                                @endphp
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium {{ $statusConfig[$penalty->status]['class'] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $statusConfig[$penalty->status]['label'] ?? $penalty->status }}
                                </span>
                            </td>

                            {{-- Booking --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($penalty->booking)
                                    <a href="{{ route('admin.service-bookings.show', $penalty->booking) }}"
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        #{{ $penalty->service_booking_id }}
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>

                            {{-- Date --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $penalty->created_at->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $penalty->created_at->format('H:i') }}
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    {{-- View Details --}}
                                    <button @click="showPenaltyDetail({{ $penalty->id }}, {{ json_encode([
                                        'type' => $penalty->getPenaltyTypeLabel(),
                                        'amount' => $penalty->penalty_amount,
                                        'trust_deduction' => $penalty->trust_score_deduction,
                                        'status' => $penalty->status,
                                        'reason' => $penalty->reason,
                                        'notes' => $penalty->notes,
                                        'charged_at' => $penalty->charged_at ? $penalty->charged_at->format('d/m/Y H:i') : null,
                                        'paid_at' => $penalty->paid_at ? $penalty->paid_at->format('d/m/Y H:i') : null,
                                        'payment_method' => $penalty->payment_method,
                                        'transaction_id' => $penalty->transaction_id,
                                    ]) }})"
                                            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                            title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    @if($penalty->status === 'pending')
                                        {{-- Charge --}}
                                        <form action="{{ route('admin.anti-abuse.penalties.charge', $penalty) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                    title="หักเงิน"
                                                    onclick="return confirm('ยืนยันการหักเงินค่าปรับ ฿{{ number_format($penalty->penalty_amount) }}?')">
                                                <i class="fas fa-credit-card"></i>
                                            </button>
                                        </form>

                                        {{-- Waive --}}
                                        <button @click="openWaiveModal({{ $penalty->id }}, '{{ $penalty->getPenaltyTypeLabel() }}', {{ $penalty->penalty_amount }})"
                                                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                                title="ยกเว้น">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-gavel text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                    <p class="text-gray-500 dark:text-gray-400">ไม่พบรายการค่าปรับ</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($penalties->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $penalties->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    <div x-show="showDetailModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="detail-modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showDetailModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showDetailModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showDetailModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        รายละเอียดค่าปรับ
                    </h3>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">ประเภท</label>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="penaltyDetail.type"></p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">จำนวนเงิน</label>
                                <p class="font-bold text-red-600 dark:text-red-400">
                                    ฿<span x-text="penaltyDetail.amount?.toLocaleString()"></span>
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">หักคะแนน Trust</label>
                                <p class="font-medium text-orange-600 dark:text-orange-400" x-text="'-' + (penaltyDetail.trust_deduction || 0)"></p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">สถานะ</label>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="penaltyDetail.status"></p>
                            </div>
                        </div>

                        <div x-show="penaltyDetail.reason">
                            <label class="text-xs text-gray-500 dark:text-gray-400">เหตุผล</label>
                            <p class="text-sm text-gray-700 dark:text-gray-300" x-text="penaltyDetail.reason"></p>
                        </div>

                        <div x-show="penaltyDetail.notes">
                            <label class="text-xs text-gray-500 dark:text-gray-400">หมายเหตุ</label>
                            <p class="text-sm text-gray-700 dark:text-gray-300" x-text="penaltyDetail.notes"></p>
                        </div>

                        <div class="grid grid-cols-2 gap-4" x-show="penaltyDetail.charged_at || penaltyDetail.paid_at">
                            <div x-show="penaltyDetail.charged_at">
                                <label class="text-xs text-gray-500 dark:text-gray-400">หักเงินเมื่อ</label>
                                <p class="text-sm text-gray-700 dark:text-gray-300" x-text="penaltyDetail.charged_at"></p>
                            </div>
                            <div x-show="penaltyDetail.paid_at">
                                <label class="text-xs text-gray-500 dark:text-gray-400">ชำระเมื่อ</label>
                                <p class="text-sm text-gray-700 dark:text-gray-300" x-text="penaltyDetail.paid_at"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4" x-show="penaltyDetail.payment_method || penaltyDetail.transaction_id">
                            <div x-show="penaltyDetail.payment_method">
                                <label class="text-xs text-gray-500 dark:text-gray-400">วิธีชำระ</label>
                                <p class="text-sm text-gray-700 dark:text-gray-300" x-text="penaltyDetail.payment_method"></p>
                            </div>
                            <div x-show="penaltyDetail.transaction_id">
                                <label class="text-xs text-gray-500 dark:text-gray-400">Transaction ID</label>
                                <p class="text-sm text-gray-700 dark:text-gray-300 font-mono" x-text="penaltyDetail.transaction_id"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button"
                            @click="showDetailModal = false"
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Waive Modal --}}
    <div x-show="showWaiveModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="waive-modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showWaiveModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showWaiveModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showWaiveModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="'/admin/anti-abuse/penalties/' + waivePenaltyId + '/waive'" method="POST">
                    @csrf
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-times-circle text-gray-500 mr-2"></i>
                            ยกเว้นค่าปรับ
                        </h3>

                        <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                ค่าปรับ: <span class="font-medium" x-text="waivePenaltyType"></span>
                                <br>
                                จำนวน: <span class="font-bold text-red-600 dark:text-red-400">฿<span x-text="waivePenaltyAmount?.toLocaleString()"></span></span>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                เหตุผลในการยกเว้น <span class="text-red-500">*</span>
                            </label>
                            <textarea name="reason" rows="3" required
                                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-gray-500 focus:border-gray-500"
                                      placeholder="ระบุเหตุผลที่ยกเว้นค่าปรับนี้..."></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-600 text-base font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-times-circle mr-2"></i>
                            ยืนยันยกเว้น
                        </button>
                        <button type="button"
                                @click="showWaiveModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function penaltyManager() {
    return {
        // Detail Modal
        showDetailModal: false,
        penaltyDetail: {},

        // Waive Modal
        showWaiveModal: false,
        waivePenaltyId: null,
        waivePenaltyType: '',
        waivePenaltyAmount: 0,

        showPenaltyDetail(id, data) {
            this.penaltyDetail = data;
            this.showDetailModal = true;
        },

        openWaiveModal(id, type, amount) {
            this.waivePenaltyId = id;
            this.waivePenaltyType = type;
            this.waivePenaltyAmount = amount;
            this.showWaiveModal = true;
        }
    }
}
</script>
@endpush
@endsection
