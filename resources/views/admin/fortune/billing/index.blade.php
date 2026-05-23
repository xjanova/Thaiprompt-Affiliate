@extends('layouts.admin-v3')

@section('title', 'จัดการบิลดูดวง')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                💰 จัดการบิลดูดวง
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                ดูรายได้ สถิติ และจัดการบิลทั้งหมด
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
            <a href="{{ route('admin.fortune.billing.floating-bills') }}"
               class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition flex items-center">
                <span class="mr-2">📋</span>
                บิลลอย
                @if($stats['floating']['count'] > 0)
                    <span class="ml-2 px-2 py-0.5 bg-orange-800 text-orange-100 text-xs rounded-full">
                        {{ $stats['floating']['count'] }}
                    </span>
                @endif
            </a>
            <a href="{{ route('admin.fortune.billing.export-revenue', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
               class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                📥 Export รายได้
            </a>
        </div>
    </div>

    {{-- Revenue Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {{-- รายได้วันนี้ --}}
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-green-100">รายได้วันนี้</span>
                <span class="text-2xl">💵</span>
            </div>
            <div class="text-2xl font-bold mb-1">฿{{ number_format($stats['today']['revenue'], 2) }}</div>
            <div class="text-green-100 text-sm">{{ $stats['today']['count'] }} รายการ</div>
        </div>

        {{-- รายได้เดือนนี้ --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-blue-100">รายได้เดือนนี้</span>
                <span class="text-2xl">📊</span>
            </div>
            <div class="text-2xl font-bold mb-1">฿{{ number_format($stats['month']['revenue'], 2) }}</div>
            <div class="text-blue-100 text-sm">{{ $stats['month']['count'] }} รายการ</div>
        </div>

        {{-- รายได้ทั้งหมด --}}
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-purple-100">รายได้ทั้งหมด</span>
                <span class="text-2xl">💎</span>
            </div>
            <div class="text-2xl font-bold mb-1">฿{{ number_format($stats['total']['revenue'], 2) }}</div>
            <div class="text-purple-100 text-sm">{{ $stats['total']['count'] }} รายการ</div>
        </div>

        {{-- อัตราแปลง --}}
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-indigo-100">Conversion Rate</span>
                <span class="text-2xl">📈</span>
            </div>
            <div class="text-2xl font-bold mb-1">{{ $stats['conversion_rate'] }}%</div>
            <div class="text-indigo-100 text-sm">{{ $stats['period']['count'] }}/{{ $stats['total_readings_period'] }} รายการ</div>
        </div>
    </div>

    {{-- Secondary Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-8">
        {{-- บิลลอย --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border-l-4 border-orange-500">
            <div class="text-gray-500 dark:text-gray-400 text-sm mb-1">บิลลอย (ยังไม่ระบุผู้ใช้)</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['floating']['count'] }}</div>
            <div class="text-orange-600 dark:text-orange-400 text-sm">฿{{ number_format($stats['floating']['amount'], 2) }}</div>
        </div>

        {{-- รอชำระ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border-l-4 border-yellow-500">
            <div class="text-gray-500 dark:text-gray-400 text-sm mb-1">รอชำระเงิน</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['pending']['count'] }}</div>
            <div class="text-yellow-600 dark:text-yellow-400 text-sm">รอการโอนเงิน</div>
        </div>

        {{-- เฉลี่ยต่อบิล --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border-l-4 border-green-500">
            <div class="text-gray-500 dark:text-gray-400 text-sm mb-1">เฉลี่ยต่อบิล</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($stats['period']['avg_per_bill'], 2) }}</div>
            <div class="text-green-600 dark:text-green-400 text-sm">ในช่วงที่เลือก</div>
        </div>
    </div>

    {{-- Revenue Chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📈 รายได้ 7 วันล่าสุด</h3>
        <div class="h-64">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    วันที่เริ่มต้น
                </label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    วันที่สิ้นสุด
                </label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    สถานะ
                </label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>ชำระแล้ว</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>รอชำระ</option>
                    <option value="floating" {{ $status === 'floating' ? 'selected' : '' }}>บิลลอย</option>
                    <option value="free" {{ $status === 'free' ? 'selected' : '' }}>ฟรี</option>
                </select>
            </div>

            <div class="flex items-end gap-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    🔍 กรอง
                </button>
                <a href="{{ route('admin.fortune.billing.index') }}"
                   class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- Bills Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            วันที่
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ผู้ใช้
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ประเภท
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จำนวนเงิน
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ช่องทาง
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($bills as $bill)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <div>{{ $bill->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $bill->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                <div class="font-medium">{{ $bill->facebook_user_name ?? $bill->user?->name ?? 'ไม่ระบุ' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    @if($bill->is_floating)
                                        <span class="text-orange-500">บิลลอย</span>
                                    @else
                                        {{ $bill->facebook_user_id }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($bill->reading_type === 'deep')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                        🌟 เชิงลึก
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200">
                                        🔮 พื้นฐาน
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                @if($bill->is_paid)
                                    <span class="text-green-600 dark:text-green-400">฿{{ number_format($bill->amount_paid, 2) }}</span>
                                @elseif($bill->conversation_status === 'pending_payment')
                                    <span class="text-yellow-600 dark:text-yellow-400">฿{{ number_format($bill->amount_paid, 2) }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($bill->is_paid)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        ✅ ชำระแล้ว
                                    </span>
                                @elseif($bill->conversation_status === 'pending_payment')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        ⏳ รอชำระ
                                    </span>
                                @elseif($bill->is_floating)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                        📋 บิลลอย
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        ฟรี
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                @if($bill->payment_method === 'stripe')
                                    {{-- 💳 Stripe payment --}}
                                    <span class="text-purple-600 dark:text-purple-400 font-semibold">💳 Stripe</span>
                                    {{-- 🌍 Audit badge — country ที่ Stripe detect ตอนจ่ายจริง (เก็บไว้เพื่อ audit) --}}
                                    @if($bill->stripe_customer_region === 'th')
                                        <span class="ml-1 text-xs bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 px-1.5 py-0.5 rounded" title="legacy TH tier — ไม่มี option แสดงให้ลูกค้าเลือกแล้ว">🇹🇭</span>
                                    @endif
                                    @if($bill->stripe_payment_intent_id)
                                        @php
                                            $stripeService = new \App\Services\Fortune\FortuneStripeService();
                                            $dashboardUrl = $stripeService->buildDashboardUrl($bill->stripe_payment_intent_id);
                                        @endphp
                                        <a href="{{ $dashboardUrl }}" target="_blank" rel="noopener"
                                           class="block text-xs text-purple-500 hover:underline mt-1"
                                           title="ดูใน Stripe Dashboard">
                                            🔗 {{ \Illuminate\Support\Str::limit($bill->stripe_payment_intent_id, 18) }}
                                        </a>
                                    @elseif($bill->stripe_session_id)
                                        <div class="text-xs text-gray-500" title="{{ $bill->stripe_session_id }}">
                                            session: {{ \Illuminate\Support\Str::limit($bill->stripe_session_id, 14) }}
                                        </div>
                                    @endif
                                @elseif($bill->sms_notification_id)
                                    <span class="text-blue-600 dark:text-blue-400">📱 SMS</span>
                                    @if($bill->sender_bank)
                                        <div class="text-xs">{{ $bill->sender_bank }}</div>
                                    @endif
                                @elseif($bill->is_paid)
                                    <span class="text-gray-500">Manual</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2" x-data="{ submitting: false }">
                                    <a href="{{ route('admin.fortune.readings.show', $bill) }}"
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        ดู
                                    </a>
                                    @if(!$bill->is_paid && $bill->conversation_status === 'pending_payment')
                                        <button onclick="showManualConfirm({{ $bill->id }}, {{ $bill->amount_paid }})"
                                                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                            ยืนยัน
                                        </button>
                                    @endif
                                    @if($bill->is_paid && !$bill->is_floating)
                                        @if($bill->is_paid && !empty($bill->getCollectedQuestions()) && empty($bill->deep_response))
                                            <form action="{{ route('admin.fortune.billing.retry-fortune', $bill) }}" method="POST" class="inline"
                                                  @submit="if(!confirm('ต้องการส่งคำทำนายให้ลูกค้าอีกครั้ง?')) { $event.preventDefault(); return; } submitting = true; showLoadingOverlay('กำลังส่งคำทำนาย...');">
                                                @csrf
                                                <button type="submit" :disabled="submitting"
                                                        class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300 disabled:opacity-50">
                                                    <template x-if="!submitting"><span>🔮 ส่งคำทำนาย</span></template>
                                                    <template x-if="submitting"><span class="flex items-center gap-1"><span class="animate-spin h-3 w-3 border border-purple-600 border-t-transparent rounded-full inline-block"></span>กำลังส่ง...</span></template>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.fortune.billing.void', $bill) }}" method="POST" class="inline"
                                              @submit="if(!confirm('ยืนยันยกเลิกบิลนี้?')) { $event.preventDefault(); return; } submitting = true;">
                                            @csrf
                                            <button type="submit" :disabled="submitting"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 disabled:opacity-50">
                                                ยกเลิก
                                            </button>
                                        </form>
                                    @endif

                                    {{-- 💳 (2026-05-09) Stripe-specific actions --}}
                                    @if($bill->payment_method === 'stripe')
                                        @if($bill->is_paid && $bill->stripe_payment_intent_id)
                                            {{-- Refund — confirm dialog + reason field --}}
                                            <button onclick="showStripeRefund({{ $bill->id }}, {{ $bill->amount_paid }})"
                                                    class="text-pink-600 hover:text-pink-900 dark:text-pink-400 dark:hover:text-pink-300"
                                                    title="คืนเงินผ่าน Stripe">
                                                💸 Refund
                                            </button>
                                        @endif
                                        @if(!$bill->is_paid && $bill->stripe_session_id)
                                            {{-- Expire pending session --}}
                                            <form action="{{ route('admin.fortune.billing.stripe-expire', $bill) }}" method="POST" class="inline"
                                                  @submit="if(!confirm('ยืนยัน expire session นี้? ลูกค้าจะจ่ายไม่ได้แล้ว')) { $event.preventDefault(); return; } submitting = true;">
                                                @csrf
                                                <button type="submit" :disabled="submitting"
                                                        class="text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300 disabled:opacity-50">
                                                    ⏱️ Expire
                                                </button>
                                            </form>
                                        @endif
                                        @if($bill->stripe_session_id)
                                            {{-- Resync จาก Stripe API (กรณี webhook ตก) --}}
                                            <form action="{{ route('admin.fortune.billing.stripe-resync', $bill) }}" method="POST" class="inline"
                                                  @submit="submitting = true;">
                                                @csrf
                                                <button type="submit" :disabled="submitting"
                                                        class="text-cyan-600 hover:text-cyan-900 dark:text-cyan-400 dark:hover:text-cyan-300 disabled:opacity-50"
                                                        title="ดึง status จาก Stripe API ใหม่ (กรณี webhook ตก)">
                                                    🔄 Resync
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ไม่พบข้อมูลบิลในช่วงเวลาที่เลือก
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($bills->hasPages())
        <div class="mt-6">
            {{ $bills->appends(request()->query())->links() }}
        </div>
    @endif
</div>

{{-- Loading Overlay --}}
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-60 z-[100] hidden items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 w-full max-w-xs mx-4 text-center">
        <div class="flex justify-center mb-4">
            <div class="animate-spin h-12 w-12 border-4 border-purple-600 border-t-transparent rounded-full"></div>
        </div>
        <p id="loadingMessage" class="text-gray-900 dark:text-white font-semibold text-lg">กำลังประมวลผล...</p>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">กรุณารอสักครู่ อย่าปิดหน้านี้</p>
    </div>
</div>

{{-- Manual Confirm Modal --}}
<div id="manualConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-full max-w-md mx-4"
         x-data="{ submitting: false }">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ยืนยันการชำระเงินด้วยตนเอง</h3>
        <form id="manualConfirmForm" method="POST"
              @submit="submitting = true; showLoadingOverlay('กำลังยืนยันบิลและส่งคำทำนาย...');">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน</label>
                <input type="number" name="amount" id="confirmAmount" step="0.01" required
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ (ถ้ามี)</label>
                <input type="text" name="note" placeholder="เช่น โอนผ่าน PromptPay"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div class="flex gap-3">
                <button type="submit" :disabled="submitting"
                        class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white rounded-lg transition flex items-center justify-center gap-2">
                    <template x-if="!submitting"><span>✅ ยืนยันการชำระ</span></template>
                    <template x-if="submitting">
                        <span class="flex items-center gap-2">
                            <span class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                            กำลังดำเนินการ...
                        </span>
                    </template>
                </button>
                <button type="button" @click="if(!submitting) closeManualConfirm()" :disabled="submitting"
                        class="flex-1 px-4 py-2 bg-gray-500 hover:bg-gray-600 disabled:opacity-50 text-white rounded-lg transition">
                    ยกเลิก
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 💳 (2026-05-09) Stripe Refund Modal --}}
<div id="stripeRefundModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-full max-w-md mx-4"
         x-data="{ submitting: false, fullRefund: true }">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">💸 Refund Stripe Payment</h3>
        <p class="text-sm text-red-600 dark:text-red-400 mb-4">
            ⚠️ การ refund ไม่สามารถยกเลิกได้ กรุณาตรวจสอบให้แน่ใจก่อนทำรายการ
        </p>
        <form id="stripeRefundForm" method="POST"
              @submit="if(!confirm('ยืนยันการ refund? ไม่สามารถ undo ได้')) { $event.preventDefault(); return; } submitting = true;">
            @csrf
            <div class="mb-4">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" x-model="fullRefund" class="rounded">
                    Refund เต็มจำนวน
                </label>
            </div>
            <div class="mb-4" x-show="!fullRefund">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน (บาท)</label>
                <input type="number" name="amount" id="refundAmount" step="0.01" min="0.01"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <p class="text-xs text-gray-500 mt-1">เว้นว่างไว้ = refund เต็ม</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล <span class="text-red-500">*</span></label>
                <textarea name="reason" required rows="3" maxlength="500"
                          placeholder="เช่น ลูกค้าขอเงินคืน เนื่องจาก..."
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" :disabled="submitting"
                        class="flex-1 px-4 py-2 bg-pink-600 hover:bg-pink-700 disabled:bg-pink-400 text-white rounded-lg transition">
                    <template x-if="!submitting"><span>💸 Refund</span></template>
                    <template x-if="submitting"><span>กำลังดำเนินการ...</span></template>
                </button>
                <button type="button" @click="if(!submitting) closeStripeRefund()" :disabled="submitting"
                        class="flex-1 px-4 py-2 bg-gray-500 hover:bg-gray-600 disabled:opacity-50 text-white rounded-lg transition">
                    ยกเลิก
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const dailyRevenue = @json($dailyRevenue);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dailyRevenue.map(d => d.date_label),
            datasets: [{
                label: 'รายได้ (บาท)',
                data: dailyRevenue.map(d => d.revenue),
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // แสดง loading overlay ขณะรอระบบประมวลผล
    function showLoadingOverlay(message) {
        const overlay = document.getElementById('loadingOverlay');
        const msg = document.getElementById('loadingMessage');
        if (msg && message) msg.textContent = message;
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
    }

    // Manual Confirm Modal
    function showManualConfirm(billId, amount) {
        document.getElementById('manualConfirmForm').action = '/admin/fortune/billing/' + billId + '/manual-confirm';
        document.getElementById('confirmAmount').value = amount;
        document.getElementById('manualConfirmModal').classList.remove('hidden');
        document.getElementById('manualConfirmModal').classList.add('flex');
    }

    function closeManualConfirm() {
        document.getElementById('manualConfirmModal').classList.add('hidden');
        document.getElementById('manualConfirmModal').classList.remove('flex');
    }

    // 💳 (2026-05-09) Stripe Refund Modal
    function showStripeRefund(billId, amount) {
        document.getElementById('stripeRefundForm').action = '/admin/fortune/billing/' + billId + '/stripe-refund';
        document.getElementById('refundAmount').value = amount;
        const modal = document.getElementById('stripeRefundModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeStripeRefund() {
        const modal = document.getElementById('stripeRefundModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endpush
@endsection
