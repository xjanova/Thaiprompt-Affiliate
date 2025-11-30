@extends('layouts.admin-v3')

@section('title', 'ออเดอร์ Marketplace')

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ออเดอร์ Marketplace</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">ออเดอร์ที่ Sync มาจาก Lazada, Shopee, TikTok Shop</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_orders']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">ออเดอร์ทั้งหมด</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ number_format($stats['pending_orders']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">รอดำเนินการ</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-green-600">{{ number_format($stats['completed_orders']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">สำเร็จ</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-blue-600">฿{{ number_format($stats['total_sales'], 0) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">ยอดขายรวม</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['pending_commissions']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">รอคำนวณคอมฯ</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-emerald-600">{{ number_format($stats['paid_commissions']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">จ่ายคอมฯแล้ว</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-card p-4 rounded-xl">
        <form method="GET" action="{{ route('admin.marketplace.orders.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาเลขออเดอร์, ชื่อลูกค้า..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="w-40">
                <select name="platform" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทุก Platform</option>
                    @foreach($platforms as $platform)
                        <option value="{{ $platform->id }}" {{ request('platform') == $platform->id ? 'selected' : '' }}>
                            {{ $platform->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <select name="order_status" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทุกสถานะ</option>
                    <option value="pending" {{ request('order_status') == 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="processing" {{ request('order_status') == 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                    <option value="shipped" {{ request('order_status') == 'shipped' ? 'selected' : '' }}>จัดส่งแล้ว</option>
                    <option value="delivered" {{ request('order_status') == 'delivered' ? 'selected' : '' }}>ส่งถึงแล้ว</option>
                    <option value="completed" {{ request('order_status') == 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                    <option value="cancelled" {{ request('order_status') == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                </select>
            </div>
            <div class="w-40">
                <select name="commission_status" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">สถานะคอมมิชชั่น</option>
                    <option value="pending" {{ request('commission_status') == 'pending' ? 'selected' : '' }}>รอคำนวณ</option>
                    <option value="calculated" {{ request('commission_status') == 'calculated' ? 'selected' : '' }}>คำนวณแล้ว</option>
                    <option value="paid" {{ request('commission_status') == 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                <i class="fas fa-search mr-1"></i> ค้นหา
            </button>
        </form>
    </div>

    {{-- Orders Table --}}
    <div class="glass-card rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ออเดอร์</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Platform</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ลูกค้า</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ยอดรวม</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คอมมิชชั่น</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $order->external_order_id }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->order_number }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if($order->platform)
                                    <span class="px-2 py-1 rounded-lg text-xs font-medium
                                        @if($order->platform->slug == 'lazada') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                                        @elseif($order->platform->slug == 'shopee') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                        @elseif($order->platform->slug == 'tiktok') bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400
                                        @endif">
                                        {{ $order->platform->name }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-900 dark:text-white">{{ $order->customer_name ?? '-' }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->customer_email ?? $order->customer_phone ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        'shipped' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                        'delivered' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
                                        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        'refunded' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'รอดำเนินการ',
                                        'processing' => 'กำลังดำเนินการ',
                                        'shipped' => 'จัดส่งแล้ว',
                                        'delivered' => 'ส่งถึงแล้ว',
                                        'completed' => 'สำเร็จ',
                                        'cancelled' => 'ยกเลิก',
                                        'refunded' => 'คืนเงิน',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-lg text-xs font-medium {{ $statusColors[$order->order_status] ?? $statusColors['pending'] }}">
                                    {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">
                                ฿{{ number_format($order->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $commissionColors = [
                                        'pending' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                        'calculated' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'approved' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        'paid' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-lg text-xs font-medium {{ $commissionColors[$order->commission_status] ?? $commissionColors['pending'] }}">
                                    {{ $order->commission_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $order->ordered_at ? \Carbon\Carbon::parse($order->ordered_at)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.marketplace.orders.show', $order) }}"
                                       class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition"
                                       title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($order->commission_status === 'pending' && $order->order_status === 'completed')
                                        <button type="button"
                                                onclick="calculateCommission({{ $order->id }})"
                                                class="p-2 text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition"
                                                title="คำนวณคอมมิชชั่น">
                                            <i class="fas fa-calculator"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-shopping-cart text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                                <p>ยังไม่มีออเดอร์ Marketplace</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function calculateCommission(orderId) {
        if (!confirm('ต้องการคำนวณคอมมิชชั่นสำหรับออเดอร์นี้?')) return;

        fetch(`{{ url('admin/marketplace/orders') }}/${orderId}/calculate-commission`, {
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
