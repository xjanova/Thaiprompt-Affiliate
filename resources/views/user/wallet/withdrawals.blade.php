@extends('layouts.user-arrow-x')

@section('title', 'ประวัติการถอนเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white dark:bg-gray-800 opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('user.wallet.index') }}" class="p-2 bg-white dark:bg-gray-800 bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                    ← กลับ
                </a>
                <h1 class="text-3xl md:text-4xl font-bold">📋 ประวัติการถอนเงิน</h1>
            </div>
            <p class="text-purple-100">รายการคำขอถอนเงินทั้งหมด</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    @if(isset($statistics))
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-slate-50 to-gray-50 dark:from-slate-800 dark:to-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-md p-4">
            <div class="flex items-center gap-3">
                <div class="text-3xl">💰</div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">ถอนทั้งหมด</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-gray-100">฿{{ number_format($statistics['total_amount'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-50 to-gray-50 dark:from-slate-800 dark:to-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-md p-4">
            <div class="flex items-center gap-3">
                <div class="text-3xl">⏳</div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">รอดำเนินการ</p>
                    <p class="text-xl font-bold text-yellow-600">{{ $statistics['pending_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-50 to-gray-50 dark:from-slate-800 dark:to-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-md p-4">
            <div class="flex items-center gap-3">
                <div class="text-3xl">✅</div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">สำเร็จ</p>
                    <p class="text-xl font-bold text-green-600">{{ $statistics['completed_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-50 to-gray-50 dark:from-slate-800 dark:to-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-md p-4">
            <div class="flex items-center gap-3">
                <div class="text-3xl">❌</div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">ปฏิเสธ</p>
                    <p class="text-xl font-bold text-red-600">{{ $statistics['rejected_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Action -->
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl shadow-lg p-4">
        <div class="flex items-center justify-between">
            <div class="text-white">
                <h3 class="font-bold text-lg mb-1">ต้องการถอนเงิน?</h3>
                <p class="text-sm text-green-100">สร้างคำขอถอนเงินใหม่ได้ทันที</p>
            </div>
            <a href="{{ route('user.wallet.withdraw') }}"
               class="px-6 py-3 bg-white dark:bg-gray-800 text-green-600 font-semibold rounded-lg hover:bg-green-50 transition whitespace-nowrap">
                💸 ถอนเงิน
            </a>
        </div>
    </div>

    <!-- Withdrawal Requests Table -->
    <x-arrow-x.card-v3 class="p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">รายการคำขอถอนเงิน</h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-300 dark:border-gray-600">
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">รหัสคำขอ</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">จำนวนเงิน</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">ช่องทางรับเงิน</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">สถานะ</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">วันที่สร้าง</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $withdrawal)
                        <tr class="border-b border-gray-100 hover:bg-indigo-50 dark:hover:bg-gray-700 transition">
                            <td class="py-4 px-4">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $withdrawal->request_id }}</p>
                                @if($withdrawal->user_note)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($withdrawal->user_note, 30) }}</p>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <p class="text-sm font-bold text-gray-900 dark:text-gray-100">฿{{ number_format($withdrawal->amount, 2) }}</p>
                                @if($withdrawal->fee > 0)
                                    <p class="text-xs text-gray-600 dark:text-gray-400">ค่าธรรมเนียม: ฿{{ number_format($withdrawal->fee, 2) }}</p>
                                @endif
                                @if($withdrawal->net_amount != $withdrawal->amount)
                                    <p class="text-xs text-green-600 font-semibold">รับสุทธิ: ฿{{ number_format($withdrawal->net_amount, 2) }}</p>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                @if($withdrawal->paymentMethod)
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        @if($withdrawal->payment_type === 'bank_transfer')
                                            🏦 {{ $withdrawal->paymentMethod->bank_name }}
                                        @elseif($withdrawal->payment_type === 'promptpay')
                                            💳 PromptPay
                                        @elseif($withdrawal->payment_type === 'paypal')
                                            💰 PayPal
                                        @else
                                            {{ $withdrawal->payment_type_label }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ $withdrawal->paymentMethod->name }}</p>
                                @else
                                    <p class="text-sm text-gray-600 dark:text-gray-400">-</p>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-{{ $withdrawal->status_color }}-100 text-{{ $withdrawal->status_color }}-800">
                                    {{ $withdrawal->status_label }}
                                </span>
                                @if($withdrawal->rejection_reason)
                                    <p class="text-xs text-red-600 mt-1">{{ Str::limit($withdrawal->rejection_reason, 30) }}</p>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-right">
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ $withdrawal->created_at->format('d/m/Y') }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $withdrawal->created_at->format('H:i') }}</p>
                                @if($withdrawal->completed_at)
                                    <p class="text-xs text-green-600 mt-1">
                                        เสร็จ: {{ $withdrawal->completed_at->format('d/m/Y') }}
                                    </p>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-center">
                                @if($withdrawal->isPending())
                                    <form method="POST" action="{{ route('user.wallet.withdrawal.cancel', $withdrawal->id) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกคำขอนี้?')"
                                                class="px-3 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-semibold hover:bg-red-200 transition">
                                            ยกเลิก
                                        </button>
                                    </form>
                                @elseif($withdrawal->transfer_slip_url)
                                    <a href="{{ $withdrawal->transfer_slip_url }}"
                                       target="_blank"
                                       class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-semibold hover:bg-blue-200 transition inline-block">
                                        ดูสลิป
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center">
                                <div class="text-gray-400">
                                    <span class="text-4xl block mb-2">📭</span>
                                    <p class="text-sm">ยังไม่มีรายการคำขอถอนเงิน</p>
                                    <a href="{{ route('user.wallet.withdraw') }}"
                                       class="inline-block mt-3 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
                                        สร้างคำขอถอนเงิน
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($withdrawals->hasPages())
            <div class="mt-6">
                {{ $withdrawals->links() }}
            </div>
        @endif
    </x-arrow-x.card-v3>

    <!-- Information -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-xl font-bold mb-4">ℹ️ ข้อมูลเพิ่มเติม</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <h4 class="font-semibold mb-2">สถานะการถอนเงิน</h4>
                <ul class="space-y-1">
                    <li>🟡 <strong>รอดำเนินการ</strong> - คำขอยังไม่ได้รับการตรวจสอบ</li>
                    <li>🔵 <strong>กำลังดำเนินการ</strong> - กำลังโอนเงิน</li>
                    <li>🟢 <strong>สำเร็จ</strong> - โอนเงินสำเร็จแล้ว</li>
                    <li>🔴 <strong>ถูกปฏิเสธ</strong> - คำขอถูกปฏิเสธ</li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-2">ระยะเวลาดำเนินการ</h4>
                <ul class="space-y-1">
                    <li>• คำขอจะได้รับการตรวจสอบภายใน 24 ชั่วโมง</li>
                    <li>• การโอนเงินใช้เวลา 1-3 วันทำการ</li>
                    <li>• คุณสามารถยกเลิกคำขอที่ยังรอดำเนินการได้</li>
                    <li>• ติดต่อฝ่ายสนับสนุนหากมีปัญหา</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
