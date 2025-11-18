@extends('layouts.admin-v3')

@section('title', 'จัดการ Cashback Settings')

@section('content')
<div class="space-y-6">
    <!-- Header & Statistics -->
    <div class="bg-gradient-to-br from-green-600 via-emerald-600 to-teal-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-3xl font-bold mb-2">จัดการ Cashback</h2>
                <p class="text-green-100 text-sm">ตั้งค่าและจัดการระบบคืนเงินสำหรับลูกค้า</p>
            </div>
            <a href="{{ route('admin.cashback.create') }}" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-green-50 transition-all transform hover:scale-105 shadow-lg">
                ➕ เพิ่ม Cashback Setting
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Total Cashback Given -->
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-green-100">Cashback ทั้งหมด</span>
                    <span class="text-2xl">💰</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($stats['total_cashback'] ?? 0, 2) }}</p>
                <p class="text-sm text-green-100 mt-1">THB</p>
            </div>

            <!-- Total Orders with Cashback -->
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-green-100">คำสั่งซื้อที่ได้ Cashback</span>
                    <span class="text-2xl">🛍️</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($stats['total_orders'] ?? 0) }}</p>
                <p class="text-sm text-green-100 mt-1">คำสั่งซื้อ</p>
            </div>

            <!-- Active Settings -->
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-green-100">การตั้งค่าที่ใช้งาน</span>
                    <span class="text-2xl">⚙️</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($stats['active_settings'] ?? 0) }}</p>
                <p class="text-sm text-green-100 mt-1">รายการ</p>
            </div>

            <!-- Average Cashback -->
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-green-100">Cashback เฉลี่ย</span>
                    <span class="text-2xl">📊</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($stats['average_cashback'] ?? 0, 2) }}</p>
                <p class="text-sm text-green-100 mt-1">THB/คำสั่งซื้อ</p>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <form method="GET" action="{{ route('admin.cashback.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหา..." class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    <option value="global" {{ request('type') === 'global' ? 'selected' : '' }}>Global</option>
                    <option value="product" {{ request('type') === 'product' ? 'selected' : '' }}>Product-specific</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                    🔍 ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Cashback Settings List -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สินค้า</th>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ค่า Cashback</th>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">เงื่อนไข</th>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 bg-gray-50 dark:bg-slate-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($settings as $setting)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($setting->type === 'global')
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                    🌐 Global
                                </span>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    📦 Product
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($setting->type === 'product' && $setting->product)
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $setting->product->name }}</div>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400 italic">ทั้งหมด</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-green-600 dark:text-green-400">
                                @if($setting->value_type === 'percentage')
                                    {{ number_format($setting->value, 2) }}%
                                @else
                                    {{ number_format($setting->value, 2) }} THB
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 dark:text-gray-300">
                                @if($setting->min_order_amount)
                                    <div>ยอดขั้นต่ำ: {{ number_format($setting->min_order_amount, 2) }} THB</div>
                                @endif
                                @if($setting->max_cashback_amount)
                                    <div>สูงสุด: {{ number_format($setting->max_cashback_amount, 2) }} THB</div>
                                @endif
                                @if(!$setting->min_order_amount && !$setting->max_cashback_amount)
                                    <span class="text-gray-400">ไม่มีเงื่อนไข</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form action="{{ route('admin.cashback.toggle-active', $setting) }}" method="POST" class="inline">
                                @csrf
                                @if($setting->is_active)
                                    <button type="submit" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50 transition">
                                        ✅ เปิดใช้งาน
                                    </button>
                                @else
                                    <button type="submit" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-900/50 transition">
                                        ⏸️ ปิดใช้งาน
                                    </button>
                                @endif
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.cashback.edit', $setting) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300" title="แก้ไข">
                                    ✏️
                                </a>
                                <form action="{{ route('admin.cashback.destroy', $setting) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบการตั้งค่านี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300" title="ลบ">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-gray-400 dark:text-gray-500">
                                <div class="text-5xl mb-4">📭</div>
                                <p class="text-lg font-semibold">ยังไม่มีการตั้งค่า Cashback</p>
                                <p class="text-sm mt-2">เริ่มต้นด้วยการสร้างการตั้งค่า Cashback ใหม่</p>
                                <a href="{{ route('admin.cashback.create') }}" class="inline-block mt-4 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                                    ➕ สร้างเลย
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($settings->hasPages())
        <div class="px-6 py-4 bg-gray-50 dark:bg-slate-700">
            {{ $settings->links() }}
        </div>
        @endif
    </div>

    <!-- Quick Link to Statistics -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <a href="{{ route('admin.cashback.statistics') }}" class="flex items-center justify-between hover:bg-gray-50 dark:hover:bg-slate-700 transition p-4 rounded-lg">
            <div class="flex items-center">
                <span class="text-4xl mr-4">📈</span>
                <div>
                    <h4 class="text-lg font-bold text-gray-800 dark:text-white">รายงานสถิติ Cashback</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ดูรายงานและการวิเคราะห์แบบละเอียด</p>
                </div>
            </div>
            <span class="text-gray-400">→</span>
        </a>
    </div>
</div>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        alert('{{ session('success') }}');
    });
</script>
@endif
@endsection
