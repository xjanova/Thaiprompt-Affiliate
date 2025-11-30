@extends('layouts.admin-v3')

@section('title', 'รายละเอียดคอมมิชชั่น')

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.marketplace.commissions.index') }}"
           class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">รายละเอียดคอมมิชชั่น</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">#{{ $commission->id }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Commission Info --}}
            <div class="glass-card p-6 rounded-xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ข้อมูลคอมมิชชั่น</h3>

                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'approved' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                            'paid' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                        ];
                    @endphp
                    <span class="px-4 py-2 rounded-xl text-sm font-medium {{ $statusColors[$commission->status] ?? $statusColors['pending'] }}">
                        {{ $commission->status }}
                    </span>
                </div>

                <div class="text-center p-8 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl mb-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">จำนวนคอมมิชชั่น</p>
                    <p class="text-5xl font-bold text-blue-600">฿{{ number_format($commission->commission_amount, 2) }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ประเภท</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $commission->commission_type == 'direct' ? 'Direct Commission' : 'MLM Commission' }}
                        </p>
                    </div>
                    @if($commission->mlm_level)
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">MLM Level</p>
                            <p class="font-medium text-gray-900 dark:text-white">Level {{ $commission->mlm_level }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อัตราคอมมิชชั่น</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ number_format($commission->commission_rate ?? 0, 2) }}%</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ยอดออเดอร์</p>
                        <p class="font-medium text-gray-900 dark:text-white">฿{{ number_format($commission->order_amount ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>

            {{-- User Info --}}
            <div class="glass-card p-6 rounded-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ผู้รับคอมมิชชั่น</h3>

                @if($commission->user)
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-xl font-bold">
                            {{ strtoupper(substr($commission->user->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white text-lg">{{ $commission->user->name }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $commission->user->email }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">ไม่พบข้อมูลผู้ใช้</p>
                @endif
            </div>

            {{-- Order Info --}}
            @if($commission->order)
                <div class="glass-card p-6 rounded-xl">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลออเดอร์</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">เลขออเดอร์</p>
                            <a href="{{ route('admin.marketplace.orders.show', $commission->order) }}"
                               class="font-medium text-blue-600 hover:underline">
                                {{ $commission->order->external_order_id }}
                            </a>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Platform</p>
                            @if($commission->order->platform)
                                <span class="px-2 py-1 rounded text-xs font-medium
                                    @if($commission->order->platform->slug == 'lazada') bg-orange-100 text-orange-700
                                    @elseif($commission->order->platform->slug == 'shopee') bg-red-100 text-red-700
                                    @elseif($commission->order->platform->slug == 'tiktok') bg-pink-100 text-pink-700
                                    @endif">
                                    {{ $commission->order->platform->name }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">ยอดรวมออเดอร์</p>
                            <p class="font-medium text-gray-900 dark:text-white">฿{{ number_format($commission->order->total_amount, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">วันที่สั่งซื้อ</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ $commission->order->ordered_at ? \Carbon\Carbon::parse($commission->order->ordered_at)->format('d/m/Y H:i') : '-' }}
                            </p>
                        </div>
                    </div>

                    {{-- Order Items --}}
                    @if($commission->order->items && $commission->order->items->count() > 0)
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รายการสินค้า</p>
                            <div class="space-y-2">
                                @foreach($commission->order->items as $item)
                                    <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
                                        <div class="w-10 h-10 bg-gray-200 dark:bg-gray-600 rounded overflow-hidden flex-shrink-0">
                                            @if($item->product_image_url)
                                                <img src="{{ $item->product_image_url }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <i class="fas fa-box text-xs"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900 dark:text-white truncate">{{ $item->product_name }}</p>
                                            <p class="text-xs text-gray-500">x{{ $item->quantity }}</p>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">฿{{ number_format($item->total_amount, 2) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions --}}
            <div class="glass-card p-6 rounded-xl space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">การจัดการ</h3>

                @if($commission->status === 'pending')
                    <button type="button" onclick="approveCommission()"
                            class="w-full px-4 py-3 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition">
                        <i class="fas fa-check mr-2"></i>อนุมัติ
                    </button>

                    <button type="button" onclick="rejectCommission()"
                            class="w-full px-4 py-3 bg-red-500 text-white rounded-xl hover:bg-red-600 transition">
                        <i class="fas fa-times mr-2"></i>ปฏิเสธ
                    </button>
                @endif

                @if($commission->status === 'approved')
                    <button type="button" onclick="payCommission()"
                            class="w-full px-4 py-3 bg-green-500 text-white rounded-xl hover:bg-green-600 transition">
                        <i class="fas fa-money-bill mr-2"></i>จ่ายเงิน
                    </button>
                @endif

                @if($commission->status !== 'paid')
                    <form action="{{ route('admin.marketplace.commissions.destroy', $commission) }}" method="POST"
                          onsubmit="return confirm('ต้องการลบคอมมิชชั่นนี้?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-3 bg-gray-500 text-white rounded-xl hover:bg-gray-600 transition">
                            <i class="fas fa-trash mr-2"></i>ลบ
                        </button>
                    </form>
                @endif
            </div>

            {{-- Timeline --}}
            <div class="glass-card p-6 rounded-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ประวัติ</h3>

                <div class="space-y-4">
                    <div class="flex gap-3">
                        <div class="w-2 h-2 rounded-full bg-green-500 mt-2"></div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">สร้างคอมมิชชั่น</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $commission->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    @if($commission->approved_at)
                        <div class="flex gap-3">
                            <div class="w-2 h-2 rounded-full bg-blue-500 mt-2"></div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">อนุมัติ</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($commission->approved_at)->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($commission->paid_at)
                        <div class="flex gap-3">
                            <div class="w-2 h-2 rounded-full bg-green-500 mt-2"></div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">จ่ายเงินแล้ว</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($commission->paid_at)->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($commission->rejected_at)
                        <div class="flex gap-3">
                            <div class="w-2 h-2 rounded-full bg-red-500 mt-2"></div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">ปฏิเสธ</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($commission->rejected_at)->format('d/m/Y H:i') }}</p>
                                @if($commission->notes)
                                    <p class="text-xs text-red-600 mt-1">{{ $commission->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function approveCommission() {
        if (!confirm('ต้องการอนุมัติคอมมิชชั่นนี้?')) return;

        fetch(`{{ route('admin.marketplace.commissions.approve', $commission) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => alert('เกิดข้อผิดพลาด: ' + error.message));
    }

    function payCommission() {
        if (!confirm('ต้องการจ่ายคอมมิชชั่นนี้?')) return;

        fetch(`{{ route('admin.marketplace.commissions.pay', $commission) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => alert('เกิดข้อผิดพลาด: ' + error.message));
    }

    function rejectCommission() {
        const reason = prompt('ระบุเหตุผลในการปฏิเสธ:');
        if (!reason) return;

        fetch(`{{ route('admin.marketplace.commissions.reject', $commission) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ reason })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => alert('เกิดข้อผิดพลาด: ' + error.message));
    }
</script>
@endpush
@endsection
