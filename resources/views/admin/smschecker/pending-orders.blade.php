@extends('layouts.admin-v3')

@section('title', 'คำสั่งซื้อรอตรวจสอบ')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                ⏳ คำสั่งซื้อรอตรวจสอบ
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                คำสั่งซื้อที่รอการยืนยันชำระเงินผ่าน SMS Payment
            </p>
        </div>
        <a href="{{ route('admin.smschecker.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ Dashboard
        </a>
    </div>

    {{-- Search --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหา Order ID, ชื่อ, อีเมล..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                🔍 ค้นหา
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Pending Orders --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📦 คำสั่งซื้อรอชำระเงิน</h2>
                </div>

                @if($orders->isEmpty())
                <div class="p-12 text-center">
                    <div class="text-6xl mb-4">🎉</div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ไม่มีคำสั่งซื้อรอตรวจสอบ</h3>
                    <p class="text-gray-500 dark:text-gray-400">คำสั่งซื้อทั้งหมดได้รับการชำระเงินเรียบร้อยแล้ว</p>
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ลูกค้า</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ยอดชำระ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วิธีชำระ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เวลา</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($orders as $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                       class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                        #{{ $order->id }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $order->user->name ?? 'Guest' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $order->user->email ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @php
                                        $uniqueAmount = $order->paymentTransaction?->amount ?? $order->total_amount;
                                    @endphp
                                    <div class="font-bold text-lg text-green-600 dark:text-green-400">
                                        ฿{{ number_format($uniqueAmount, 2) }}
                                    </div>
                                    @if($order->paymentTransaction?->metadata['decimal_suffix'] ?? false)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        (ยอดจริง: ฿{{ number_format($order->total_amount, 2) }})
                                    </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($order->payment_method === 'promptpay')
                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded-full">📱 PromptPay</span>
                                    @else
                                    <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300 rounded-full">🏦 โอนเงิน</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <div>{{ $order->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs">{{ $order->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- Confirm Button --}}
                                        <form method="POST" action="{{ route('admin.smschecker.order-confirm', $order) }}"
                                              onsubmit="return confirm('ยืนยันการชำระเงินคำสั่งซื้อ #{{ $order->id }}?')">
                                            @csrf
                                            <button type="submit"
                                                    class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition"
                                                    title="ยืนยันการชำระเงิน">
                                                ✅ ยืนยัน
                                            </button>
                                        </form>

                                        {{-- Reject Button --}}
                                        <button type="button"
                                                onclick="showRejectModal({{ $order->id }})"
                                                class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition"
                                                title="ปฏิเสธ">
                                            ❌
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $orders->links() }}
                </div>
                @endif
            </div>
        </div>

        {{-- Unmatched SMS Notifications --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden sticky top-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📨 SMS ยังไม่จับคู่</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">SMS ที่ได้รับแต่ยังไม่มี Order ที่ตรงกัน</p>
                </div>

                @if($unmatchedNotifications->isEmpty())
                <div class="p-8 text-center">
                    <div class="text-4xl mb-2">📭</div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">ไม่มี SMS ที่รอจับคู่</p>
                </div>
                @else
                <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[600px] overflow-y-auto">
                    @foreach($unmatchedNotifications as $notification)
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-start justify-between mb-2">
                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 rounded-full">
                                {{ $notification->bank }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <div class="text-lg font-bold {{ $notification->type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $notification->type === 'credit' ? '+' : '-' }}฿{{ number_format($notification->amount, 2) }}
                        </div>
                        @if($notification->sender_or_receiver)
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">
                            {{ $notification->sender_or_receiver }}
                        </div>
                        @endif
                        @if($notification->reference_number)
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1 font-mono">
                            Ref: {{ $notification->reference_number }}
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 text-center">
                    <a href="{{ route('admin.smschecker.notifications') }}"
                       class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        ดูทั้งหมด →
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">❌ ปฏิเสธการชำระเงิน</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล (ไม่บังคับ)</label>
                <textarea name="reason" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                          placeholder="ระบุเหตุผลที่ปฏิเสธ..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="hideRejectModal()"
                        class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    ยืนยันปฏิเสธ
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showRejectModal(orderId) {
    const modal = document.getElementById('rejectModal');
    const form = document.getElementById('rejectForm');
    form.action = `/admin/smschecker/orders/${orderId}/reject`;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Close modal on backdrop click
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideRejectModal();
    }
});
</script>
@endpush
@endsection
