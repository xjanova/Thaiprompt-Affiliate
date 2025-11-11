@extends('layouts.app')

@section('title', 'แผนผ่อนชำระของฉัน')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50/20 to-amber-50/30 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">

    <!-- Hero Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 dark:from-orange-700 dark:via-amber-700 dark:to-yellow-700">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="container mx-auto px-4 py-12 relative z-10">
            <div class="max-w-4xl">
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-full mb-4 border border-white/30">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                        <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-semibold text-white">การจัดการผ่อนชำระ</span>
                </div>

                <h1 class="text-4xl md:text-5xl font-black text-white mb-3 tracking-tight drop-shadow-lg">
                    แผนผ่อนชำระของฉัน
                </h1>
                <p class="text-xl text-orange-100 font-medium">
                    ติดตามและจัดการการผ่อนชำระของคุณได้ง่ายๆ พร้อมแจ้งเตือนกำหนดชำระ
                </p>
            </div>
        </div>

        <!-- Wave Divider -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full dark:hidden">
                <path d="M0 48h1440V24C1440 24 1200 0 720 0S0 24 0 24v24z" fill="rgb(249, 250, 251)"/>
            </svg>
            <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full hidden dark:block">
                <path d="M0 48h1440V24C1440 24 1200 0 720 0S0 24 0 24v24z" fill="rgb(17, 24, 39)"/>
            </svg>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 -mt-6 relative z-10">

        <!-- Stats Cards -->
        @if($plans->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <!-- Total Plans -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-800 dark:to-indigo-800 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</div>
                        <div class="text-2xl font-black text-gray-900 dark:text-gray-100">
                            {{ $plans->total() }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Plans -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-800 dark:to-emerald-800 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">กำลังผ่อน</div>
                        <div class="text-2xl font-black text-green-600 dark:text-green-400">
                            {{ $plans->where('status', 'active')->count() }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Plans -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-800 dark:to-rose-800 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">ค้างชำระ</div>
                        <div class="text-2xl font-black text-red-600 dark:text-red-400">
                            {{ $plans->where('status', 'overdue')->count() }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completed Plans -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-800 dark:to-pink-800 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">ชำระครบ</div>
                        <div class="text-2xl font-black text-purple-600 dark:text-purple-400">
                            {{ $plans->where('status', 'completed')->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Plans List -->
        @if($plans->count() > 0)
        <div class="space-y-6 mb-8">
            @foreach($plans as $plan)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all">

                <!-- Plan Header -->
                <div class="bg-gradient-to-r from-gray-50 to-white dark:from-gray-700 dark:to-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-800 dark:to-amber-800 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $plan->plan_number }}</span>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-{{ $plan->status_color }}-100 text-{{ $plan->status_color }}-800 dark:bg-{{ $plan->status_color }}-900/30 dark:text-{{ $plan->status_color }}-400">
                                        {{ $plan->status_label }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    สร้างเมื่อ {{ $plan->created_at->format('d/m/Y') }}
                                    @if($plan->order)
                                        • คำสั่งซื้อ: {{ $plan->order->order_number }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">ยอดรวมทั้งหมด</div>
                            <div class="text-2xl font-black text-orange-600 dark:text-orange-400">
                                ฿{{ number_format($plan->total_amount, 2) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plan Details -->
                <div class="p-6">
                    <!-- Progress Bar -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                ความคืบหน้า
                            </span>
                            <span class="text-sm font-bold text-{{ $plan->status_color }}-600 dark:text-{{ $plan->status_color }}-400">
                                {{ $plan->paid_installments }}/{{ $plan->total_installments }} งวด ({{ number_format($plan->progress_percentage, 1) }}%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                            <div class="bg-gradient-to-r from-orange-500 to-amber-500 h-full rounded-full transition-all duration-500 shadow-lg"
                                 style="width: {{ $plan->progress_percentage }}%"></div>
                        </div>
                    </div>

                    <!-- Payment Info Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
                            <div class="text-xs text-blue-600 dark:text-blue-400 mb-1">เงินดาวน์</div>
                            <div class="text-lg font-bold text-blue-900 dark:text-blue-300">
                                ฿{{ number_format($plan->down_payment, 2) }}
                            </div>
                        </div>

                        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-100 dark:border-orange-800">
                            <div class="text-xs text-orange-600 dark:text-orange-400 mb-1">งวดละ</div>
                            <div class="text-lg font-bold text-orange-900 dark:text-orange-300">
                                ฿{{ number_format($plan->installment_amount, 2) }}
                            </div>
                        </div>

                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-100 dark:border-green-800">
                            <div class="text-xs text-green-600 dark:text-green-400 mb-1">ชำระแล้ว</div>
                            <div class="text-lg font-bold text-green-900 dark:text-green-300">
                                ฿{{ number_format($plan->total_paid, 2) }}
                            </div>
                        </div>

                        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-100 dark:border-purple-800">
                            <div class="text-xs text-purple-600 dark:text-purple-400 mb-1">ค้างชำระ</div>
                            <div class="text-lg font-bold text-purple-900 dark:text-purple-300">
                                ฿{{ number_format($plan->remaining_amount - $plan->total_paid, 2) }}
                            </div>
                        </div>
                    </div>

                    <!-- Next Payment Info -->
                    @if($plan->next_payment_date && $plan->status !== 'completed')
                    <div class="mb-4 p-4 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl border-l-4 border-orange-500">
                        <div class="flex items-center gap-3">
                            <svg class="w-8 h-8 text-orange-600 dark:text-orange-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <div class="text-sm font-semibold text-orange-800 dark:text-orange-300 mb-1">
                                    กำหนดชำระงวดถัดไป
                                </div>
                                <div class="text-lg font-black text-orange-900 dark:text-orange-200">
                                    {{ $plan->next_payment_date->format('d/m/Y') }}
                                    @if($plan->next_payment_date->isPast())
                                        <span class="text-sm text-red-600 dark:text-red-400 font-semibold">(เลยกำหนด)</span>
                                    @else
                                        <span class="text-sm text-gray-600 dark:text-gray-400 font-normal">
                                            (อีก {{ $plan->next_payment_date->diffForHumans() }})
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-600">
                        <a href="{{ route('installments.show', $plan->id) }}"
                           class="flex-1 min-w-[150px] px-6 py-3 bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            ดูรายละเอียด
                        </a>

                        @if($plan->status === 'active' || $plan->status === 'overdue')
                        <a href="{{ route('installments.show', $plan->id) }}#payments"
                           class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                            </svg>
                            ชำระเงิน
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 border border-gray-100 dark:border-gray-700">
                {{ $plans->links() }}
            </div>
        </div>
        @else
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center border border-gray-100 dark:border-gray-700">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30 rounded-full mb-6">
                <svg class="w-12 h-12 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">ยังไม่มีแผนผ่อนชำระ</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                คุณยังไม่มีแผนผ่อนชำระในระบบ ติดต่อเราเพื่อสอบถามเงื่อนไขการผ่อนชำระ
            </p>
            <a href="{{ route('shop.index') }}"
               class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                </svg>
                ช้อปสินค้า
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
