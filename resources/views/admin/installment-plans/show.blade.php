@extends('layouts.admin-v3')

@section('title', 'รายละเอียดแผนผ่อนชำระ')

@section('content')
<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('admin.installment-plans.index') }}"
           class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปยังรายการแผนผ่อนชำระ</span>
        </a>
    </div>

    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-800 dark:via-indigo-800 dark:to-purple-800 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white opacity-5 rounded-full -mr-48 -mt-48"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white opacity-5 rounded-full -ml-32 -mb-32"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl">
                            <i class="fas fa-calendar-alt text-3xl"></i>
                        </div>
                        <div>
                            <p class="text-blue-100 text-sm">เลขที่แผน</p>
                            <h1 class="text-3xl font-bold">{{ $installmentPlan->plan_number }}</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-4">
                        <span class="px-4 py-2 rounded-xl font-semibold text-sm
                            {{ $installmentPlan->status === 'active' ? 'bg-green-500 text-white' : '' }}
                            {{ $installmentPlan->status === 'completed' ? 'bg-blue-500 text-white' : '' }}
                            {{ $installmentPlan->status === 'overdue' ? 'bg-red-500 text-white' : '' }}
                            {{ $installmentPlan->status === 'cancelled' ? 'bg-gray-500 text-white' : '' }}">
                            <i class="fas fa-circle mr-2 text-xs"></i>
                            {{ ucfirst($installmentPlan->status) }}
                        </span>
                        <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl font-semibold text-sm">
                            <i class="fas fa-hashtag mr-2"></i>
                            {{ $installmentPlan->total_installments }} งวด
                        </span>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-blue-100 text-sm mb-2">ยอดรวมทั้งหมด</p>
                    <h2 class="text-5xl font-bold mb-2">฿{{ number_format($installmentPlan->total_amount, 2) }}</h2>
                    <div class="flex items-center justify-end gap-4 text-sm">
                        <div>
                            <span class="text-blue-100">จ่ายแล้ว: </span>
                            <span class="font-bold text-green-300">฿{{ number_format($installmentPlan->paid_amount, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-blue-100">คงเหลือ: </span>
                            <span class="font-bold text-yellow-300">฿{{ number_format($installmentPlan->remaining_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    @if($installmentPlan->status === 'active' || $installmentPlan->status === 'overdue')
    <div class="flex gap-3 justify-end">
        <button onclick="showCancelModal()" class="bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg flex items-center gap-2 transition-all duration-300 transform hover:scale-105">
            <i class="fas fa-ban"></i>
            ยกเลิกแผน
        </button>
    </div>
    @endif

    <!-- Progress Bar -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">ความคืบหน้าการชำระ</h3>
            <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ number_format(($installmentPlan->paid_amount / $installmentPlan->total_amount) * 100, 1) }}%
            </span>
        </div>

        <div class="relative">
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-8 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 rounded-full flex items-center justify-end px-4 transition-all duration-1000"
                     style="width: {{ min(($installmentPlan->paid_amount / $installmentPlan->total_amount) * 100, 100) }}%">
                    @if($installmentPlan->paid_amount > 0)
                    <span class="text-white font-bold text-sm">฿{{ number_format($installmentPlan->paid_amount, 0) }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 mt-6">
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">จ่ายแล้ว</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $installmentPlan->payments->where('status', 'paid')->count() }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500">งวด</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">รอชำระ</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $installmentPlan->payments->where('status', 'pending')->count() }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500">งวด</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">เกินกำหนด</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $installmentPlan->payments->where('status', 'overdue')->count() }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500">งวด</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Payments Timeline -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Customer Info -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-user-circle text-white"></i>
                    </div>
                    ข้อมูลลูกค้า
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 block">ชื่อลูกค้า</label>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                                {{ strtoupper(substr($installmentPlan->user->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $installmentPlan->user->name }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">ID: {{ $installmentPlan->user->id }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 block">อีเมล</label>
                        <p class="text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-envelope text-gray-400"></i>
                            {{ $installmentPlan->user->email }}
                        </p>
                    </div>

                    @if($installmentPlan->order)
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 block">เลขที่คำสั่งซื้อ</label>
                        <a href="#" class="text-blue-600 dark:text-blue-400 hover:underline font-mono">
                            #{{ $installmentPlan->order->order_number }}
                        </a>
                    </div>
                    @endif

                    @if($installmentPlan->quotation)
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 block">เลขที่ใบเสนอราคา</label>
                        <a href="#" class="text-purple-600 dark:text-purple-400 hover:underline font-mono">
                            #{{ $installmentPlan->quotation->quotation_number }}
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Payment Schedule -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-calendar-check text-white"></i>
                    </div>
                    ตารางการชำระเงิน
                </h3>

                <div class="space-y-4">
                    @foreach($installmentPlan->payments as $payment)
                    <div class="relative pl-8 pb-8 {{ !$loop->last ? 'border-l-4' : '' }}
                        {{ $payment->status === 'paid' ? 'border-green-500' : '' }}
                        {{ $payment->status === 'pending' ? 'border-yellow-500' : '' }}
                        {{ $payment->status === 'overdue' ? 'border-red-500' : '' }}">

                        <!-- Timeline Dot -->
                        <div class="absolute left-0 top-0 transform -translate-x-1/2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center
                                {{ $payment->status === 'paid' ? 'bg-green-500' : '' }}
                                {{ $payment->status === 'pending' ? 'bg-yellow-500' : '' }}
                                {{ $payment->status === 'overdue' ? 'bg-red-500' : '' }}">
                                @if($payment->status === 'paid')
                                <i class="fas fa-check text-white text-xs"></i>
                                @else
                                <i class="fas fa-circle text-white text-xs"></i>
                                @endif
                            </div>
                        </div>

                        <!-- Payment Card -->
                        <div class="bg-gradient-to-r
                            {{ $payment->status === 'paid' ? 'from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-green-200 dark:border-green-800' : '' }}
                            {{ $payment->status === 'pending' ? 'from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border-yellow-200 dark:border-yellow-800' : '' }}
                            {{ $payment->status === 'overdue' ? 'from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border-red-200 dark:border-red-800' : '' }}
                            rounded-xl p-5 border-2">

                            <div class="flex items-center justify-between flex-wrap gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="px-3 py-1 rounded-lg font-bold text-lg
                                            {{ $payment->status === 'paid' ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300' : '' }}
                                            {{ $payment->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300' : '' }}
                                            {{ $payment->status === 'overdue' ? 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300' : '' }}">
                                            งวดที่ {{ $payment->installment_number }}
                                        </span>
                                        <span class="px-3 py-1 bg-white/50 dark:bg-gray-900/50 rounded-lg font-semibold text-gray-900 dark:text-white">
                                            <i class="fas fa-calendar-day mr-2"></i>
                                            {{ $payment->due_date->format('d/m/Y') }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 mb-3">
                                        <div>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">จำนวนเงิน</p>
                                            <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($payment->amount, 2) }}</p>
                                        </div>
                                        @if($payment->status === 'paid')
                                        <div>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">จ่ายจริง</p>
                                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($payment->paid_amount, 2) }}</p>
                                        </div>
                                        @endif
                                    </div>

                                    @if($payment->status === 'paid')
                                    <div class="flex items-center gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">ชำระเมื่อ: </span>
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ $payment->paid_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                        @if($payment->payment_method)
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">วิธี: </span>
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ $payment->payment_method }}</span>
                                        </div>
                                        @endif
                                    </div>
                                    @if($payment->payment_reference)
                                    <div class="mt-2 text-xs">
                                        <span class="text-gray-600 dark:text-gray-400">อ้างอิง: </span>
                                        <span class="font-mono text-gray-900 dark:text-white">{{ $payment->payment_reference }}</span>
                                    </div>
                                    @endif
                                    @if($payment->payment_note)
                                    <div class="mt-2 p-2 bg-white/50 dark:bg-gray-900/50 rounded text-xs text-gray-700 dark:text-gray-300">
                                        <i class="fas fa-sticky-note mr-1"></i>
                                        {{ $payment->payment_note }}
                                    </div>
                                    @endif
                                    @endif

                                    @if($payment->status === 'overdue')
                                    <div class="mt-2 flex items-center gap-2 text-red-600 dark:text-red-400 text-sm font-semibold">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span>เกินกำหนด {{ $payment->due_date->diffInDays(now()) }} วัน</span>
                                    </div>
                                    @endif
                                </div>

                                <!-- Action Button -->
                                @if($payment->status === 'pending' || $payment->status === 'overdue')
                                <div>
                                    <button onclick="showPaymentModal({{ $payment->id }}, {{ $payment->amount }})"
                                            class="bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg transition-all duration-300 transform hover:scale-105">
                                        <i class="fas fa-money-check-alt mr-2"></i>
                                        บันทึกการชำระ
                                    </button>
                                </div>
                                @endif

                                @if($payment->status === 'paid')
                                <div>
                                    <div class="bg-green-500 text-white px-6 py-3 rounded-xl font-semibold shadow-lg">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        ชำระแล้ว
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Summary Card -->
            <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl shadow-2xl p-6 text-white">
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-chart-pie mr-2"></i>
                    สรุปแผน
                </h3>

                <div class="space-y-3">
                    <div class="flex justify-between items-center pb-3 border-b border-white/20">
                        <span class="text-sm opacity-90">ยอดรวม</span>
                        <span class="font-bold text-xl">฿{{ number_format($installmentPlan->total_amount, 2) }}</span>
                    </div>

                    <div class="flex justify-between items-center pb-3 border-b border-white/20">
                        <span class="text-sm opacity-90">จ่ายแล้ว</span>
                        <span class="font-bold text-green-300">฿{{ number_format($installmentPlan->paid_amount, 2) }}</span>
                    </div>

                    <div class="flex justify-between items-center pb-3 border-b border-white/20">
                        <span class="text-sm opacity-90">คงเหลือ</span>
                        <span class="font-bold text-yellow-300">฿{{ number_format($installmentPlan->remaining_amount, 2) }}</span>
                    </div>

                    <div class="flex justify-between items-center pb-3 border-b border-white/20">
                        <span class="text-sm opacity-90">จำนวนงวด</span>
                        <span class="font-semibold">{{ $installmentPlan->total_installments }} งวด</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-sm opacity-90">งวดละ</span>
                        <span class="font-semibold">฿{{ number_format($installmentPlan->installment_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-history text-blue-600 dark:text-blue-400 mr-2"></i>
                    ไทม์ไลน์
                </h3>

                <div class="space-y-3">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-plus text-blue-600 dark:text-blue-400"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">สร้างแผน</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $installmentPlan->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    @if($installmentPlan->first_payment_date)
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-calendar-check text-green-600 dark:text-green-400"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">งวดแรก</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $installmentPlan->first_payment_date->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    @endif

                    @if($installmentPlan->completed_at)
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-check-circle text-purple-600 dark:text-purple-400"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">เสร็จสมบูรณ์</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $installmentPlan->completed_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    @endif

                    @if($installmentPlan->cancelled_at)
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-times-circle text-red-600 dark:text-red-400"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">ยกเลิก</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $installmentPlan->cancelled_at->format('d/m/Y H:i') }}</p>
                            @if($installmentPlan->cancellation_reason)
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $installmentPlan->cancellation_reason }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Statistics -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-chart-bar text-purple-600 dark:text-purple-400 mr-2"></i>
                    สถิติ
                </h3>

                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $installmentPlan->payments->where('status', 'paid')->count() }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">จ่ายแล้ว</p>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl">
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $installmentPlan->payments->where('status', 'pending')->count() }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">รอชำระ</p>
                    </div>
                    <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-xl col-span-2">
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $installmentPlan->payments->where('status', 'overdue')->count() }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">เกินกำหนด</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">บันทึกการชำระเงิน</h3>

        <form id="paymentForm" method="POST">
            @csrf
            <input type="hidden" id="paymentId" name="payment_id">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงินที่ชำระ *</label>
                    <div class="relative">
                        <input type="number" name="paid_amount" id="paidAmount" step="0.01" required
                               class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg pl-10 pr-4 py-2">
                        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">฿</div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วิธีการชำระ *</label>
                    <select name="payment_method" required class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                        <option value="">เลือกวิธีการชำระ</option>
                        <option value="bank_transfer">โอนเงิน</option>
                        <option value="cash">เงินสด</option>
                        <option value="credit_card">บัตรเครดิต</option>
                        <option value="debit_card">บัตรเดบิต</option>
                        <option value="qr_code">QR Code</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเลขอ้างอิง</label>
                    <input type="text" name="payment_reference"
                           class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                           placeholder="เลขที่ธุรกรรม / อ้างอิง">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ</label>
                    <textarea name="payment_note" rows="3"
                              class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                              placeholder="หมายเหตุเพิ่มเติม..."></textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="button" onclick="hidePaymentModal()"
                        class="flex-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white px-4 py-2 rounded-lg font-medium">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white px-4 py-2 rounded-lg font-medium">
                    บันทึกการชำระ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cancel Modal -->
<div id="cancelModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ยกเลิกแผนผ่อนชำระ</h3>

        <form action="{{ route('admin.installment-plans.cancel', $installmentPlan) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล</label>
                <textarea name="reason" rows="4"
                          class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                          placeholder="ระบุเหตุผลในการยกเลิก..."></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="hideCancelModal()"
                        class="flex-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white px-4 py-2 rounded-lg font-medium">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                    ยืนยันการยกเลิก
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showPaymentModal(paymentId, amount) {
    document.getElementById('paymentId').value = paymentId;
    document.getElementById('paidAmount').value = amount;
    document.getElementById('paymentForm').action = `/admin/installment-plans/{{ $installmentPlan->id }}/payments/${paymentId}/mark-paid`;
    document.getElementById('paymentModal').classList.remove('hidden');
}

function hidePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function showCancelModal() {
    document.getElementById('cancelModal').classList.remove('hidden');
}

function hideCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
}
</script>
@endsection
