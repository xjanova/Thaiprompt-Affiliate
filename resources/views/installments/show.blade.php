@extends('layouts.app')

@section('title', 'รายละเอียดแผนผ่อนชำระ #' . $plan->plan_number)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50/20 to-amber-50/30 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">

    <!-- Hero Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 dark:from-orange-700 dark:via-amber-700 dark:to-yellow-700">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="container mx-auto px-4 py-8 relative z-10">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('installments.index') }}"
                   class="p-2 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl border border-white/30 transition-all">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-full border border-white/30">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                        <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-semibold text-white">รายละเอียดแผนผ่อนชำระ</span>
                </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-black text-white mb-2 tracking-tight drop-shadow-lg">
                        {{ $plan->plan_number }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-3 text-orange-100">
                        <span class="px-4 py-2 rounded-full text-sm font-bold bg-{{ $plan->status_color }}-500 text-white shadow-lg">
                            {{ $plan->status_label }}
                        </span>
                        <span>สร้างเมื่อ {{ $plan->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-orange-100 mb-1">ยอดรวมทั้งหมด</div>
                    <div class="text-4xl font-black text-white drop-shadow-lg">
                        ฿{{ number_format($plan->total_amount, 2) }}
                    </div>
                </div>
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
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left Column - Payment Schedule -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Progress Overview -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-600 to-amber-600 dark:from-orange-700 dark:to-amber-700 text-white px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            ความคืบหน้าการชำระ
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    ชำระแล้ว {{ $plan->paid_installments }} จาก {{ $plan->total_installments }} งวด
                                </span>
                                <span class="text-2xl font-black text-{{ $plan->status_color }}-600 dark:text-{{ $plan->status_color }}-400">
                                    {{ number_format($plan->progress_percentage, 1) }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden shadow-inner">
                                <div class="bg-gradient-to-r from-orange-500 to-amber-500 h-full rounded-full transition-all duration-500 shadow-lg flex items-center justify-end pr-2"
                                     style="width: {{ $plan->progress_percentage }}%">
                                    @if($plan->progress_percentage > 10)
                                    <span class="text-xs font-bold text-white">{{ number_format($plan->progress_percentage, 1) }}%</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-100 dark:border-green-800">
                                <div class="text-sm text-green-600 dark:text-green-400 mb-1">ชำระแล้ว</div>
                                <div class="text-xl font-black text-green-900 dark:text-green-300">
                                    ฿{{ number_format($plan->total_paid, 2) }}
                                </div>
                            </div>

                            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-100 dark:border-purple-800">
                                <div class="text-sm text-purple-600 dark:text-purple-400 mb-1">คงเหลือ</div>
                                <div class="text-xl font-black text-purple-900 dark:text-purple-300">
                                    ฿{{ number_format($plan->remaining_amount - $plan->total_paid, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Schedule -->
                <div id="payments" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-600 to-amber-600 dark:from-orange-700 dark:to-amber-700 text-white px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                            ตารางการชำระเงิน
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4">
                            @foreach($plan->payments as $payment)
                            <div class="p-4 rounded-xl border-2 transition-all
                                {{ $payment->status === 'paid' ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700' : '' }}
                                {{ $payment->status === 'pending' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' : '' }}
                                {{ $payment->status === 'overdue' ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700' : '' }}">

                                <div class="flex flex-col md:flex-row md:items-center gap-4">
                                    <!-- Payment Number & Status -->
                                    <div class="flex items-center gap-4 flex-shrink-0">
                                        <div class="w-16 h-16 rounded-xl flex items-center justify-center font-black text-xl
                                            {{ $payment->status === 'paid' ? 'bg-green-500 text-white' : '' }}
                                            {{ $payment->status === 'pending' ? 'bg-blue-500 text-white' : '' }}
                                            {{ $payment->status === 'overdue' ? 'bg-red-500 text-white' : '' }}">
                                            @if($payment->status === 'paid')
                                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                {{ $payment->installment_number }}
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 dark:text-gray-100">
                                                งวดที่ {{ $payment->installment_number }}
                                            </div>
                                            <div class="text-sm
                                                {{ $payment->status === 'paid' ? 'text-green-600 dark:text-green-400' : '' }}
                                                {{ $payment->status === 'pending' ? 'text-blue-600 dark:text-blue-400' : '' }}
                                                {{ $payment->status === 'overdue' ? 'text-red-600 dark:text-red-400' : '' }}
                                                font-semibold">
                                                @if($payment->status === 'paid')
                                                    ชำระแล้ว
                                                @elseif($payment->status === 'overdue')
                                                    เลยกำหนด {{ $payment->days_overdue }} วัน
                                                @else
                                                    รอชำระ
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Payment Details -->
                                    <div class="flex-1">
                                        <div class="grid grid-cols-2 gap-3 text-sm">
                                            <div>
                                                <div class="text-gray-500 dark:text-gray-400">กำหนดชำระ</div>
                                                <div class="font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $payment->due_date->format('d/m/Y') }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500 dark:text-gray-400">ยอดชำระ</div>
                                                <div class="font-bold text-orange-600 dark:text-orange-400">
                                                    ฿{{ number_format($payment->amount, 2) }}
                                                </div>
                                            </div>
                                            @if($payment->status === 'paid')
                                            <div>
                                                <div class="text-gray-500 dark:text-gray-400">ชำระเมื่อ</div>
                                                <div class="font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $payment->paid_at->format('d/m/Y') }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500 dark:text-gray-400">วิธีชำระ</div>
                                                <div class="font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $payment->payment_method }}
                                                </div>
                                            </div>
                                            @endif
                                        </div>

                                        @if($payment->payment_reference)
                                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            อ้างอิง: {{ $payment->payment_reference }}
                                        </div>
                                        @endif
                                    </div>

                                    <!-- Payment Action -->
                                    @if($payment->status === 'pending' || $payment->status === 'overdue')
                                    <div class="flex-shrink-0">
                                        <form action="{{ route('installments.pay', [$plan->id, $payment->id]) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="payment_method" value="bank_transfer">
                                            <button type="submit"
                                                    class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex items-center gap-2 whitespace-nowrap">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                                </svg>
                                                ชำระเงิน
                                            </button>
                                        </form>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Plan Summary -->
            <div class="space-y-6">

                <!-- Plan Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden sticky top-4">
                    <div class="bg-gradient-to-r from-orange-600 to-amber-600 dark:from-orange-700 dark:to-amber-700 text-white px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                            </svg>
                            สรุปแผนผ่อนชำระ
                        </h2>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ยอดรวมทั้งหมด</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100">
                                ฿{{ number_format($plan->total_amount, 2) }}
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">เงินดาวน์</span>
                            <span class="font-bold text-blue-600 dark:text-blue-400">
                                ฿{{ number_format($plan->down_payment, 2) }}
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ยอดผ่อนชำระ</span>
                            <span class="font-bold text-orange-600 dark:text-orange-400">
                                ฿{{ number_format($plan->remaining_amount, 2) }}
                            </span>
                        </div>

                        <div class="pt-3 border-t border-gray-200 dark:border-gray-600">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600 dark:text-gray-400">จำนวนงวด</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">
                                    {{ $plan->total_installments }} งวด
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">งวดละ</span>
                                <span class="font-bold text-orange-600 dark:text-orange-400">
                                    ฿{{ number_format($plan->installment_amount, 2) }}
                                </span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-gray-200 dark:border-gray-600">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600 dark:text-gray-400">อัตราดอกเบี้ย</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">
                                    {{ number_format($plan->interest_rate, 2) }}%
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">ดอกเบี้ยรวม</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">
                                    ฿{{ number_format($plan->total_interest, 2) }}
                                </span>
                            </div>
                        </div>

                        <div class="pt-3 border-t-2 border-gray-200 dark:border-gray-600">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600 dark:text-gray-400">ชำระแล้ว</span>
                                <span class="font-bold text-green-600 dark:text-green-400">
                                    ฿{{ number_format($plan->total_paid, 2) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">คงเหลือ</span>
                                <span class="font-bold text-purple-600 dark:text-purple-400">
                                    ฿{{ number_format($plan->remaining_amount - $plan->total_paid, 2) }}
                                </span>
                            </div>
                        </div>

                        @if($plan->next_payment_date && $plan->status !== 'completed')
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-600">
                            <div class="p-4 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl">
                                <div class="text-sm text-orange-700 dark:text-orange-300 mb-1 font-semibold">
                                    กำหนดชำระถัดไป
                                </div>
                                <div class="text-lg font-black text-orange-900 dark:text-orange-100">
                                    {{ $plan->next_payment_date->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-orange-600 dark:text-orange-400 mt-1">
                                    @if($plan->next_payment_date->isPast())
                                        เลยกำหนด
                                    @else
                                        อีก {{ $plan->next_payment_date->diffForHumans() }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Related Order -->
                @if($plan->order)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700 text-white px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                            </svg>
                            คำสั่งซื้อที่เกี่ยวข้อง
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="space-y-2">
                            <div class="font-bold text-gray-900 dark:text-gray-100 text-lg">
                                {{ $plan->order->order_number }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                สั่งซื้อเมื่อ {{ $plan->order->created_at->format('d/m/Y') }}
                            </div>
                            <a href="{{ route('orders.show', $plan->order->id) }}"
                               class="inline-flex items-center gap-2 mt-3 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg transition-all text-sm">
                                ดูคำสั่งซื้อ
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Payment Frequency Info -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl shadow-lg border border-blue-200 dark:border-blue-800 p-6">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-sm text-blue-800 dark:text-blue-300">
                            <p class="font-semibold mb-1">รอบการชำระ</p>
                            <p class="text-blue-700 dark:text-blue-400">
                                @if($plan->payment_frequency === 'monthly')
                                    รายเดือน
                                @elseif($plan->payment_frequency === 'quarterly')
                                    รายไตรมาส
                                @elseif($plan->payment_frequency === 'yearly')
                                    รายปี
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
