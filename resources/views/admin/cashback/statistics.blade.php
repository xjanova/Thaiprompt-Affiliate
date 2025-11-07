@extends('layouts.admin')

@section('title', 'สถิติ Cashback')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-br from-purple-600 via-pink-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-3xl font-bold mb-2">รายงานสถิติ Cashback</h2>
                <p class="text-purple-100 text-sm">ข้อมูลและการวิเคราะห์ระบบคืนเงิน</p>
            </div>
            <a href="{{ route('admin.cashback.index') }}" class="bg-white/20 backdrop-blur-sm text-white px-6 py-3 rounded-lg font-semibold hover:bg-white/30 transition-all">
                ← ย้อนกลับ
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <form method="GET" action="{{ route('admin.cashback.statistics') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่เริ่มต้น</label>
                <input type="date" name="start_date" value="{{ $startDate ? $startDate->format('Y-m-d') : '' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่สิ้นสุด</label>
                <input type="date" name="end_date" value="{{ $endDate ? $endDate->format('Y-m-d') : '' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                    🔍 ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Overview Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Cashback -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="text-gray-600 dark:text-gray-400 font-medium">Cashback รวม</span>
                <span class="text-3xl">💰</span>
            </div>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['total_cashback'] ?? 0, 2) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">THB</p>
        </div>

        <!-- Total Orders -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="text-gray-600 dark:text-gray-400 font-medium">คำสั่งซื้อที่มี Cashback</span>
                <span class="text-3xl">🛍️</span>
            </div>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['total_orders'] ?? 0) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">คำสั่งซื้อ</p>
        </div>

        <!-- Average Cashback -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="text-gray-600 dark:text-gray-400 font-medium">Cashback เฉลี่ย</span>
                <span class="text-3xl">📊</span>
            </div>
            <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($stats['average_cashback'] ?? 0, 2) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">THB/คำสั่งซื้อ</p>
        </div>

        <!-- Active Settings -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="text-gray-600 dark:text-gray-400 font-medium">การตั้งค่าที่ใช้งาน</span>
                <span class="text-3xl">⚙️</span>
            </div>
            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($stats['active_settings'] ?? 0) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">รายการ</p>
        </div>
    </div>

    <!-- Detailed Breakdown -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- By Type -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-6 flex items-center">
                <span class="text-2xl mr-3">📈</span>
                Cashback ตามประเภท
            </h3>
            <div class="space-y-4">
                @if(isset($stats['by_type']) && count($stats['by_type']) > 0)
                    @foreach($stats['by_type'] as $type => $data)
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700 rounded-lg">
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-white">
                                @if($type === 'global')
                                    🌐 Global Cashback
                                @else
                                    📦 Product Cashback
                                @endif
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($data['count'] ?? 0) }} คำสั่งซื้อ</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($data['total'] ?? 0, 2) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">THB</p>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center text-gray-400 dark:text-gray-500 py-8">
                        <div class="text-5xl mb-4">📭</div>
                        <p>ยังไม่มีข้อมูล</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-6 flex items-center">
                <span class="text-2xl mr-3">🏆</span>
                สินค้า Cashback สูงสุด (Top 5)
            </h3>
            <div class="space-y-4">
                @if(isset($stats['top_products']) && count($stats['top_products']) > 0)
                    @foreach($stats['top_products'] as $index => $product)
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700 rounded-lg">
                        <div class="flex items-center flex-1">
                            <span class="text-2xl mr-3">{{ ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'][$index] ?? '📌' }}</span>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800 dark:text-white truncate">{{ $product['name'] ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ number_format($product['count'] ?? 0) }} ครั้ง</p>
                            </div>
                        </div>
                        <div class="text-right ml-4">
                            <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($product['total'] ?? 0, 2) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">THB</p>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center text-gray-400 dark:text-gray-500 py-8">
                        <div class="text-5xl mb-4">📭</div>
                        <p>ยังไม่มีข้อมูล</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Monthly Trend -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-6 flex items-center">
            <span class="text-2xl mr-3">📊</span>
            แนวโน้ม Cashback รายเดือน
        </h3>
        @if(isset($stats['monthly_trend']) && count($stats['monthly_trend']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">เดือน</th>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวนคำสั่งซื้อ</th>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cashback รวม</th>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cashback เฉลี่ย</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach($stats['monthly_trend'] as $month => $data)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $month }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ number_format($data['count'] ?? 0) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600 dark:text-green-400">{{ number_format($data['total'] ?? 0, 2) }} THB</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ number_format($data['average'] ?? 0, 2) }} THB</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="text-center text-gray-400 dark:text-gray-500 py-12">
                <div class="text-5xl mb-4">📭</div>
                <p class="text-lg">ยังไม่มีข้อมูลแนวโน้มรายเดือน</p>
            </div>
        @endif
    </div>

    <!-- User Rankings -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-6 flex items-center">
            <span class="text-2xl mr-3">👥</span>
            ผู้ใช้ที่ได้รับ Cashback สูงสุด (Top 10)
        </h3>
        @if(isset($stats['top_users']) && count($stats['top_users']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">อันดับ</th>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวนคำสั่งซื้อ</th>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cashback รวม</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach($stats['top_users'] as $index => $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ ['🥇', '🥈', '🥉'][$index] ?? ($index + 1) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $user['name'] ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user['email'] ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ number_format($user['count'] ?? 0) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600 dark:text-green-400">{{ number_format($user['total'] ?? 0, 2) }} THB</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="text-center text-gray-400 dark:text-gray-500 py-12">
                <div class="text-5xl mb-4">📭</div>
                <p class="text-lg">ยังไม่มีข้อมูลผู้ใช้</p>
            </div>
        @endif
    </div>

    <!-- Export Options -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-blue-900 dark:text-blue-300 mb-4">📥 ส่งออกรายงาน</h3>
        <div class="flex flex-wrap gap-4">
            <button class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition" onclick="alert('ฟีเจอร์นี้จะพัฒนาในอนาคต')">
                📊 Excel
            </button>
            <button class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition" onclick="alert('ฟีเจอร์นี้จะพัฒนาในอนาคต')">
                📄 PDF
            </button>
            <button class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition" onclick="alert('ฟีเจอร์นี้จะพัฒนาในอนาคต')">
                📧 ส่งอีเมล
            </button>
        </div>
    </div>
</div>
@endsection
