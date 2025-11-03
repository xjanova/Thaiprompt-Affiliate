@extends('layouts.seller')

@section('title', 'แดชบอร์ดร้านค้า - ' . ($store->store_name ?? 'ร้านค้าของคุณ'))

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Store Header with Package Badge --}}
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-3xl shadow-2xl p-6 md:p-8 text-white relative overflow-hidden">
        {{-- Decorative elements --}}
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-48 h-48 bg-white opacity-5 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-6 -ml-6 w-40 h-40 bg-white opacity-5 rounded-full"></div>
        <div class="absolute top-1/2 right-1/4 w-32 h-32 bg-white opacity-3 rounded-full"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                @if($store->store_logo)
                    <img src="{{ $store->logo_url }}" alt="{{ $store->store_name }}" class="w-16 h-16 md:w-20 md:h-20 rounded-2xl shadow-lg border-4 border-white/30 object-cover">
                @else
                    <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl bg-white/20 flex items-center justify-center text-3xl md:text-4xl shadow-lg border-4 border-white/30">
                        🏪
                    </div>
                @endif
                <div>
                    <h1 class="text-2xl md:text-4xl font-bold mb-1">{{ $store->store_name }}</h1>
                    <p class="text-indigo-100 text-sm md:text-base">ยินดีต้อนรับสู่ระบบจัดการร้านค้า</p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-xs bg-white/20 px-2 py-1 rounded-lg">{{ $store->store_slug }}</span>
                        @if($store->is_verified)
                            <span class="text-xs bg-green-500 px-2 py-1 rounded-lg font-semibold">✓ ยืนยันแล้ว</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-start md:items-end gap-2">
                @if($package)
                    <div class="flex items-center gap-2">
                        <span class="px-4 py-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white rounded-xl text-sm font-bold shadow-lg">
                            {{ $package->display_name }}
                        </span>
                        @if($store->subscription_status === 'trial')
                            <span class="px-3 py-1 bg-blue-500/80 rounded-lg text-xs font-semibold">
                                Trial: {{ $store->trial_ends_at ? $store->trial_ends_at->diffForHumans() : 'N/A' }}
                            </span>
                        @endif
                    </div>
                @else
                    <a href="{{ route('seller.packages') }}" class="px-4 py-2 bg-white text-indigo-600 rounded-xl text-sm font-bold shadow-lg hover:scale-105 transition">
                        เลือกแพ็คเกจ
                    </a>
                @endif

                <div class="flex gap-2 mt-1">
                    <a href="{{ route('seller.store.settings') }}" class="px-3 py-1 bg-white/20 hover:bg-white/30 rounded-lg text-sm transition">
                        ⚙️ ตั้งค่าร้าน
                    </a>
                    <a href="{{ $store->store_url }}" target="_blank" class="px-3 py-1 bg-white/20 hover:bg-white/30 rounded-lg text-sm transition">
                        🌐 ดูหน้าร้าน
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
        {{-- Today's Revenue --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-5xl">💰</span>
                    <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold">วันนี้</span>
                </div>
                <p class="text-emerald-100 text-sm mb-1">รายได้วันนี้</p>
                <p class="text-3xl font-bold">฿{{ number_format($todayRevenue ?? 0, 0) }}</p>
                <p class="text-xs text-emerald-100 mt-2">อัพเดทเมื่อสักครู่</p>
            </div>
        </div>

        {{-- Total Revenue --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-5xl">📊</span>
                    @if($salesGrowth != 0)
                        <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold">
                            {{ $salesGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($salesGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-blue-100 text-sm mb-1">รายได้ทั้งหมด</p>
                <p class="text-3xl font-bold">฿{{ number_format($totalRevenue ?? 0, 0) }}</p>
                <p class="text-xs text-blue-100 mt-2">{{ $completedSales ?? 0 }} ออเดอร์สำเร็จ</p>
            </div>
        </div>

        {{-- Pending Orders --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-5xl">⏳</span>
                    @if($pendingSales > 0)
                        <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold animate-pulse">ต้องดำเนินการ</span>
                    @endif
                </div>
                <p class="text-orange-100 text-sm mb-1">คำสั่งซื้อรอดำเนินการ</p>
                <p class="text-3xl font-bold">{{ number_format($pendingSales ?? 0) }}</p>
                <p class="text-xs text-orange-100 mt-2">จาก {{ number_format($totalSales ?? 0) }} ทั้งหมด</p>
            </div>
        </div>

        {{-- Total Products --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-5xl">📦</span>
                    <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold">{{ $activeProducts ?? 0 }} ใช้งาน</span>
                </div>
                <p class="text-purple-100 text-sm mb-1">สินค้าทั้งหมด</p>
                <p class="text-3xl font-bold">{{ number_format($totalProducts ?? 0) }}</p>
                <p class="text-xs text-purple-100 mt-2">
                    @if(isset($lowStockProducts) && $lowStockProducts > 0)
                        <span class="text-yellow-300">⚠️ {{ $lowStockProducts }} ใกล้หมด</span>
                    @else
                        <span>{{ $outOfStockProducts ?? 0 }} หมดสต็อก</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Additional Stats Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4 hover:shadow-xl transition">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-lg flex items-center justify-center text-2xl shadow-lg">
                    👥
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">ผู้เยี่ยมชม (30 วัน)</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalVisitors ?? 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4 hover:shadow-xl transition">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-lg flex items-center justify-center text-2xl shadow-lg">
                    📈
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">อัตราการแปลง</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($conversionRate ?? 0, 1) }}%</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4 hover:shadow-xl transition">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg flex items-center justify-center text-2xl shadow-lg">
                    ⭐
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">คะแนนร้าน</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($store->rating_average ?? 0, 1) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4 hover:shadow-xl transition">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-pink-400 to-rose-500 rounded-lg flex items-center justify-center text-2xl shadow-lg">
                    💬
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">รีวิวทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($store->rating_count ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-2xl">⚡</span>
            การดำเนินการด่วน
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <a href="{{ route('seller.products.create') }}" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl hover:scale-105 transition shadow-lg">
                <span class="text-3xl mb-2">➕</span>
                <span class="text-sm font-semibold text-center">เพิ่มสินค้า</span>
            </a>

            <a href="{{ route('seller.orders.index') }}" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-xl hover:scale-105 transition shadow-lg">
                <span class="text-3xl mb-2">📋</span>
                <span class="text-sm font-semibold text-center">ดูออเดอร์</span>
            </a>

            <a href="{{ route('seller.store.settings') }}" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl hover:scale-105 transition shadow-lg">
                <span class="text-3xl mb-2">🎨</span>
                <span class="text-sm font-semibold text-center">แต่งร้าน</span>
            </a>

            <a href="{{ route('seller.analytics') }}" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl hover:scale-105 transition shadow-lg">
                <span class="text-3xl mb-2">📊</span>
                <span class="text-sm font-semibold text-center">รายงาน</span>
            </a>

            <a href="{{ route('seller.marketing') }}" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-pink-500 to-pink-600 text-white rounded-xl hover:scale-105 transition shadow-lg">
                <span class="text-3xl mb-2">📢</span>
                <span class="text-sm font-semibold text-center">การตลาด</span>
            </a>

            <a href="{{ $store->store_url }}" target="_blank" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-cyan-500 to-cyan-600 text-white rounded-xl hover:scale-105 transition shadow-lg">
                <span class="text-3xl mb-2">🌐</span>
                <span class="text-sm font-semibold text-center">หน้าร้าน</span>
            </a>
        </div>
    </div>

    {{-- Revenue Chart --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="text-3xl">📈</span>
                รายได้รายเดือน (12 เดือนล่าสุด)
            </h2>
            <div class="flex gap-2">
                <button class="px-3 py-1 bg-gray-100 dark:bg-slate-700 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-slate-600 transition">รายวัน</button>
                <button class="px-3 py-1 bg-blue-500 text-white rounded-lg text-sm">รายเดือน</button>
                <button class="px-3 py-1 bg-gray-100 dark:bg-slate-700 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-slate-600 transition">รายปี</button>
            </div>
        </div>
        <div class="h-80">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="text-3xl">📋</span>
                คำสั่งซื้อล่าสุด
            </h2>
            <a href="{{ route('seller.orders.index') }}" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-semibold transition">
                ดูทั้งหมด →
            </a>
        </div>

        @if($recentOrders->isEmpty())
            <div class="text-center py-16">
                <div class="text-7xl mb-4">🛍️</div>
                <p class="text-gray-500 dark:text-gray-400 text-lg font-semibold">ยังไม่มีคำสั่งซื้อ</p>
                <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">คำสั่งซื้อจะแสดงที่นี่เมื่อมีลูกค้าสั่งซื้อสินค้าจากร้านคุณ</p>
                <a href="{{ route('seller.products.create') }}" class="inline-block mt-4 px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold transition">
                    เพิ่มสินค้าตัวแรก
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">เลขที่คำสั่งซื้อ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ลูกค้า</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ยอดรวม</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentOrders as $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('seller.orders.show', $order) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-semibold">
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-2">
                                            {{ substr($order->user->name ?? 'U', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->user->name ?? 'ไม่ระบุ' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $order->user->email ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                    ฿{{ number_format($order->total_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        ];
                                        $statusLabels = [
                                            'pending' => 'รอดำเนินการ',
                                            'processing' => 'กำลังจัดเตรียม',
                                            'completed' => 'สำเร็จ',
                                            'cancelled' => 'ยกเลิก',
                                        ];
                                    @endphp
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $statusLabels[$order->status] ?? $order->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Top Products --}}
    @if($topProducts->isNotEmpty())
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="text-3xl">🏆</span>
                สินค้าขายดี Top 5
            </h2>
            <a href="{{ route('seller.products.index') }}" class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg text-sm font-semibold transition">
                ดูทั้งหมด →
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            @foreach($topProducts as $index => $product)
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-600 rounded-xl p-4 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-3">
                        <span class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                            {{ $index + 1 }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $product->sales_count ?? 0 }} ขาย</span>
                    </div>
                    @if($product->images->first())
                        <img src="{{ $product->images->first()->image_url }}" alt="{{ $product->name }}" class="w-full h-32 object-cover rounded-lg mb-3">
                    @else
                        <div class="w-full h-32 bg-gray-200 dark:bg-slate-600 rounded-lg mb-3 flex items-center justify-center text-4xl">
                            📦
                        </div>
                    @endif
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white line-clamp-2 mb-2">{{ $product->name }}</h3>
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400">฿{{ number_format($product->price, 0) }}</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueData = @json($monthlyRevenue);

    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: revenueData.map(item => item.month),
            datasets: [{
                label: 'รายได้ (บาท)',
                data: revenueData.map(item => item.total),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(59, 130, 246, 0.5)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return 'รายได้: ฿' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#6B7280',
                        font: {
                            family: 'Noto Sans Thai, sans-serif'
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(107, 114, 128, 0.1)'
                    },
                    ticks: {
                        color: '#6B7280',
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        },
                        font: {
                            family: 'Noto Sans Thai, sans-serif'
                        }
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection
