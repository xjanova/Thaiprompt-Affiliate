@extends('layouts.admin-v3')

@section('title', 'รายละเอียดออเดอร์ - ' . $order->external_order_id)

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.marketplace.orders.index') }}"
           class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ออเดอร์ #{{ $order->external_order_id }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $order->order_number }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Order Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Status --}}
            <div class="glass-card p-6 rounded-xl">
                <div class="flex flex-wrap items-center gap-4">
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                            'shipped' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                            'delivered' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
                            'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                        ];
                    @endphp
                    <span class="px-4 py-2 rounded-xl text-sm font-medium {{ $statusColors[$order->order_status] ?? $statusColors['pending'] }}">
                        สถานะ: {{ $order->order_status }}
                    </span>

                    @if($order->platform)
                        <span class="px-4 py-2 rounded-xl text-sm font-medium
                            @if($order->platform->slug == 'lazada') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                            @elseif($order->platform->slug == 'shopee') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                            @elseif($order->platform->slug == 'tiktok') bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400
                            @endif">
                            {{ $order->platform->name }}
                        </span>
                    @endif

                    @if($order->commission_status)
                        @php
                            $commissionColors = [
                                'pending' => 'bg-gray-100 text-gray-700',
                                'calculated' => 'bg-yellow-100 text-yellow-700',
                                'approved' => 'bg-blue-100 text-blue-700',
                                'paid' => 'bg-green-100 text-green-700',
                            ];
                        @endphp
                        <span class="px-4 py-2 rounded-xl text-sm font-medium {{ $commissionColors[$order->commission_status] ?? $commissionColors['pending'] }}">
                            คอมมิชชั่น: {{ $order->commission_status }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Customer Info --}}
            <div class="glass-card p-6 rounded-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลลูกค้า</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ชื่อลูกค้า</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $order->customer_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อีเมล</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $order->customer_email ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">โทรศัพท์</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $order->customer_phone ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tracking Number</p>
                        <p class="font-medium text-gray-900 dark:text-white font-mono">{{ $order->tracking_number ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Order Items --}}
            <div class="glass-card p-6 rounded-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">รายการสินค้า</h3>

                <div class="space-y-4">
                    @forelse($order->items as $item)
                        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-lg overflow-hidden flex-shrink-0">
                                @if($item->product_image_url)
                                    <img src="{{ $item->product_image_url }}" alt="{{ $item->product_name }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <i class="fas fa-box"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</p>
                                @if($item->variant_name)
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item->variant_name }}</p>
                                @endif
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    ฿{{ number_format($item->unit_price, 2) }} x {{ $item->quantity }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900 dark:text-white">฿{{ number_format($item->total_amount, 2) }}</p>
                                @if($item->discount_amount > 0)
                                    <p class="text-sm text-green-600">-฿{{ number_format($item->discount_amount, 2) }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">ไม่มีรายการสินค้า</p>
                    @endforelse
                </div>

                {{-- Order Summary --}}
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">ยอดสินค้า</span>
                        <span class="text-gray-900 dark:text-white">฿{{ number_format($order->subtotal ?? $order->total_amount, 2) }}</span>
                    </div>
                    @if($order->shipping_fee > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">ค่าจัดส่ง</span>
                            <span class="text-gray-900 dark:text-white">฿{{ number_format($order->shipping_fee, 2) }}</span>
                        </div>
                    @endif
                    @if($order->discount_amount > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">ส่วนลด</span>
                            <span class="text-green-600">-฿{{ number_format($order->discount_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-900 dark:text-white">ยอดรวม</span>
                        <span class="text-blue-600">฿{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Commissions --}}
            @if($order->commissions->count() > 0)
                <div class="glass-card p-6 rounded-xl">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">คอมมิชชั่น</h3>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ผู้รับ</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">ประเภท</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">Level</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">จำนวน</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($order->commissions as $commission)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                            {{ $commission->user->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2 text-center text-sm text-gray-600 dark:text-gray-400">
                                            {{ $commission->commission_type }}
                                        </td>
                                        <td class="px-4 py-2 text-center text-sm text-gray-600 dark:text-gray-400">
                                            {{ $commission->mlm_level ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2 text-right text-sm font-medium text-gray-900 dark:text-white">
                                            ฿{{ number_format($commission->commission_amount, 2) }}
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <span class="px-2 py-1 rounded text-xs font-medium
                                                @if($commission->status == 'paid') bg-green-100 text-green-700
                                                @elseif($commission->status == 'approved') bg-blue-100 text-blue-700
                                                @else bg-gray-100 text-gray-700
                                                @endif">
                                                {{ $commission->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions --}}
            <div class="glass-card p-6 rounded-xl space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">การจัดการ</h3>

                @if($order->commission_status === 'pending')
                    <button type="button" onclick="calculateCommission()"
                            class="w-full px-4 py-3 bg-green-500 text-white rounded-xl hover:bg-green-600 transition">
                        <i class="fas fa-calculator mr-2"></i>คำนวณคอมมิชชั่น
                    </button>
                @endif

                <form action="{{ route('admin.marketplace.orders.update-status', $order) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')

                    <select name="order_status"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="pending" {{ $order->order_status == 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                        <option value="processing" {{ $order->order_status == 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                        <option value="shipped" {{ $order->order_status == 'shipped' ? 'selected' : '' }}>จัดส่งแล้ว</option>
                        <option value="delivered" {{ $order->order_status == 'delivered' ? 'selected' : '' }}>ส่งถึงแล้ว</option>
                        <option value="completed" {{ $order->order_status == 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                        <option value="cancelled" {{ $order->order_status == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                    </select>

                    <button type="submit"
                            class="w-full px-4 py-3 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition">
                        <i class="fas fa-save mr-2"></i>อัพเดทสถานะ
                    </button>
                </form>
            </div>

            {{-- Affiliate Info --}}
            @if($order->affiliateUser || $order->affiliateLink)
                <div class="glass-card p-6 rounded-xl">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูล Affiliate</h3>

                    <div class="space-y-3 text-sm">
                        @if($order->affiliateUser)
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">ผู้แนะนำ</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $order->affiliateUser->name }}</p>
                            </div>
                        @endif
                        @if($order->affiliateLink)
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">ลิงก์ Affiliate</p>
                                <p class="font-mono text-gray-900 dark:text-white text-xs break-all">{{ $order->affiliateLink->short_code }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Order Info --}}
            <div class="glass-card p-6 rounded-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลออเดอร์</h3>

                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">บัญชี</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $order->account->account_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">วันที่สั่งซื้อ</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $order->ordered_at ? \Carbon\Carbon::parse($order->ordered_at)->format('d/m/Y H:i') : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Sync เมื่อ</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function calculateCommission() {
        if (!confirm('ต้องการคำนวณคอมมิชชั่นสำหรับออเดอร์นี้?')) return;

        fetch(`{{ route('admin.marketplace.orders.calculate-commission', $order) }}`, {
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
</script>
@endpush
@endsection
