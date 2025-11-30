@extends('layouts.admin-v3')

@section('title', 'ออเดอร์ Marketplace')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-cyan-500 via-blue-500 to-indigo-600 dark:from-cyan-700 dark:via-blue-700 dark:to-indigo-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="absolute text-white/10 text-6xl bottom-10 right-40" style="animation: float 6s ease-in-out infinite; animation-delay: 0.3s">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-3">
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-shopping-cart text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">ออเดอร์ Marketplace</h1>
                    <p class="text-cyan-100 text-lg mt-1">ออเดอร์ที่ Sync มาจาก Lazada, Shopee, TikTok Shop</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-shopping-cart text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['total_orders'] ?? 0) }}</p>
                <p class="text-sm opacity-90">ออเดอร์ทั้งหมด</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-clock text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['pending_orders'] ?? 0) }}</p>
                <p class="text-sm opacity-90">รอดำเนินการ</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-700 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-check-circle text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['completed_orders'] ?? 0) }}</p>
                <p class="text-sm opacity-90">สำเร็จ</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-money-bill-wave text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-2xl font-bold mb-1">฿{{ number_format($stats['total_sales'] ?? 0, 0) }}</p>
                <p class="text-sm opacity-90">ยอดขายรวม</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-calculator text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['pending_commissions'] ?? 0) }}</p>
                <p class="text-sm opacity-90">รอคำนวณคอมฯ</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-700 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-coins text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['paid_commissions'] ?? 0) }}</p>
                <p class="text-sm opacity-90">จ่ายคอมฯแล้ว</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 border border-white/20 dark:border-gray-700/50">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-cyan-600 dark:text-cyan-400"></i>
            ตัวกรองข้อมูล
        </h3>

        <form method="GET" action="{{ route('admin.marketplace.orders.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="md:col-span-1">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="ค้นหาเลขออเดอร์..."
                           class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>

            <div>
                <select name="platform" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all">
                    <option value="">ทุก Platform</option>
                    @foreach($platforms ?? [] as $platform)
                        <option value="{{ $platform->id }}" {{ request('platform') == $platform->id ? 'selected' : '' }}>
                            {{ $platform->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="order_status" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all">
                    <option value="">ทุกสถานะ</option>
                    <option value="pending" {{ request('order_status') == 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="processing" {{ request('order_status') == 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                    <option value="shipped" {{ request('order_status') == 'shipped' ? 'selected' : '' }}>จัดส่งแล้ว</option>
                    <option value="completed" {{ request('order_status') == 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                    <option value="cancelled" {{ request('order_status') == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                </select>
            </div>

            <div>
                <select name="commission_status" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition-all">
                    <option value="">สถานะคอมมิชชั่น</option>
                    <option value="pending" {{ request('commission_status') == 'pending' ? 'selected' : '' }}>รอคำนวณ</option>
                    <option value="calculated" {{ request('commission_status') == 'calculated' ? 'selected' : '' }}>คำนวณแล้ว</option>
                    <option value="paid" {{ request('commission_status') == 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white rounded-xl font-semibold transition-all shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i>
                    ค้นหา
                </button>
                <a href="{{ route('admin.marketplace.orders.index') }}" class="px-4 py-3 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded-xl transition-all">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Orders Table --}}
    <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl overflow-hidden border border-white/20 dark:border-gray-700/50">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-list text-cyan-600 dark:text-cyan-400"></i>
                    รายการออเดอร์
                </h3>
                <span class="px-4 py-2 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 rounded-xl text-sm font-semibold">
                    {{ number_format(($orders ?? collect())->total()) }} รายการ
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-receipt mr-1 text-cyan-500"></i> ออเดอร์
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-store mr-1 text-orange-500"></i> Platform
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-user mr-1 text-blue-500"></i> ลูกค้า
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-info-circle mr-1 text-indigo-500"></i> สถานะ
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-money-bill mr-1 text-green-500"></i> ยอดรวม
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-coins mr-1 text-yellow-500"></i> คอมมิชชั่น
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-calendar mr-1 text-gray-500"></i> วันที่
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                            <i class="fas fa-cogs mr-1 text-purple-500"></i> จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($orders ?? [] as $order)
                        <tr class="hover:bg-gradient-to-r hover:from-cyan-50/50 hover:to-transparent dark:hover:from-gray-700/50 transition-all duration-200">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $order->external_order_id }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->order_number }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if($order->platform)
                                    @php
                                        $pColors = [
                                            'lazada' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                            'shopee' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                            'tiktok' => 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
                                        ];
                                    @endphp
                                    <span class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ $pColors[$order->platform->slug ?? ''] ?? 'bg-gray-100 text-gray-700' }}">
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
                                    $statusConfig = [
                                        'pending' => ['gradient' => 'from-yellow-400 to-orange-500', 'icon' => 'fa-clock', 'label' => 'รอดำเนินการ'],
                                        'processing' => ['gradient' => 'from-blue-400 to-blue-600', 'icon' => 'fa-spinner', 'label' => 'กำลังดำเนินการ'],
                                        'shipped' => ['gradient' => 'from-purple-400 to-purple-600', 'icon' => 'fa-truck', 'label' => 'จัดส่งแล้ว'],
                                        'delivered' => ['gradient' => 'from-cyan-400 to-cyan-600', 'icon' => 'fa-box', 'label' => 'ส่งถึงแล้ว'],
                                        'completed' => ['gradient' => 'from-green-400 to-emerald-600', 'icon' => 'fa-check-circle', 'label' => 'สำเร็จ'],
                                        'cancelled' => ['gradient' => 'from-red-400 to-red-600', 'icon' => 'fa-times-circle', 'label' => 'ยกเลิก'],
                                    ];
                                    $sConfig = $statusConfig[$order->order_status] ?? $statusConfig['pending'];
                                @endphp
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-gradient-to-r {{ $sConfig['gradient'] }} text-white shadow-md">
                                    <i class="fas {{ $sConfig['icon'] }}"></i>
                                    {{ $sConfig['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-lg font-bold text-gray-900 dark:text-white">
                                    ฿{{ number_format($order->total_amount, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $commColors = [
                                        'pending' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                        'calculated' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'approved' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        'paid' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                    ];
                                @endphp
                                <span class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ $commColors[$order->commission_status ?? 'pending'] }}">
                                    {{ $order->commission_status ?? 'pending' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $order->ordered_at ? \Carbon\Carbon::parse($order->ordered_at)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.marketplace.orders.show', $order) }}"
                                       class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center hover:bg-blue-200 dark:hover:bg-blue-800 transition-all hover:scale-110"
                                       title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($order->commission_status === 'pending' && $order->order_status === 'completed')
                                        <button type="button"
                                                onclick="calculateCommission({{ $order->id }})"
                                                class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 flex items-center justify-center hover:bg-green-200 dark:hover:bg-green-800 transition-all hover:scale-110"
                                                title="คำนวณคอมมิชชั่น">
                                            <i class="fas fa-calculator"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-24 h-24 bg-gradient-to-br from-cyan-100 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/30 rounded-full flex items-center justify-center mb-6">
                                        <i class="fas fa-shopping-cart text-5xl text-cyan-500"></i>
                                    </div>
                                    <h4 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-3">ยังไม่มีออเดอร์ Marketplace</h4>
                                    <p class="text-gray-500 dark:text-gray-400">รอ Sync ออเดอร์จากบัญชี Marketplace</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(($orders ?? collect())->hasPages())
            <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
    }
    .dark .glass-card {
        background: rgba(31, 41, 55, 0.8);
    }
</style>
@endpush

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
            showToast(data.success ? 'success' : 'error', data.message);
            if (data.success) setTimeout(() => location.reload(), 1500);
        })
        .catch(error => showToast('error', 'เกิดข้อผิดพลาด: ' + error.message));
    }

    function showToast(type, message) {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'from-green-500 to-emerald-600' : 'from-red-500 to-red-600';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

        toast.className = `fixed top-4 right-4 z-50 px-6 py-4 bg-gradient-to-r ${bgColor} text-white rounded-xl shadow-2xl transform transition-all duration-300 flex items-center gap-3`;
        toast.innerHTML = `<i class="fas ${icon} text-xl"></i><span>${message}</span>`;

        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>
@endpush
@endsection
