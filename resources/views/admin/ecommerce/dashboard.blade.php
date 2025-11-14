@extends('layouts.admin')

@section('title', 'E-Commerce Dashboard')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Welcome Header -->
    <div class="bg-gradient-to-r from-orange-600 via-yellow-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>

        {{-- Language Switcher --}}
        <div class="absolute top-4 right-4 z-20">
            <div class="relative inline-block" x-data="{ open: false }">
                <button @click="open = !open" class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-200 border border-white/30 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                    <span data-translate>ภาษา</span>
                </button>

                <div x-show="open" @click.away="open = false" x-transition
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden z-50">
                    <a href="#" @click.prevent="language = 'th'" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇹🇭</span> <span data-translate>ไทย</span>
                    </a>
                    <a href="#" @click.prevent="language = 'en'" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇬🇧</span> English
                    </a>
                    <a href="#" @click.prevent="language = 'zh'" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇨🇳</span> 中文
                    </a>
                    <a href="#" @click.prevent="language = 'ja'" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇯🇵</span> 日本語
                    </a>
                </div>
            </div>
        </div>

        <div class="relative z-10">
            <h1 class="text-3xl font-bold mb-2">🛒 E-Commerce Dashboard</h1>
            <p class="text-orange-100" data-translate>ภาพรวมระบบจัดการร้านค้าออนไลน์</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Products -->
        <a href="{{ route('admin.ecommerce.products.index') }}" class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">📦</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                        {{ $stats['active_products'] }} <span data-translate>ใช้งาน</span>
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1" data-translate>สินค้าทั้งหมด</p>
                <p class="text-4xl font-bold">{{ number_format($stats['total_products']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2" data-translate>คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>

        <!-- Total Orders -->
        <a href="{{ route('admin.ecommerce.orders.index') }}" class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">📋</div>
                    @if($orderGrowth != 0)
                        <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                            {{ $orderGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($orderGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1" data-translate>คำสั่งซื้อทั้งหมด</p>
                <p class="text-4xl font-bold">{{ number_format($stats['total_orders']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2" data-translate>คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>

        <!-- Total Revenue -->
        <a href="{{ route('admin.ecommerce.reports') }}" class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">💰</div>
                    @if($revenueGrowth != 0)
                        <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                            {{ $revenueGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($revenueGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1" data-translate>รายได้ทั้งหมด</p>
                <p class="text-4xl font-bold">฿{{ number_format($stats['total_revenue'], 0) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2" data-translate>คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>

        <!-- Pending Orders -->
        <a href="{{ route('admin.ecommerce.orders.index', ['status' => 'pending']) }}" class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">⏳</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                        {{ $stats['processing_orders'] }} <span data-translate>กำลังดำเนินการ</span>
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1" data-translate>รอดำเนินการ</p>
                <p class="text-4xl font-bold">{{ number_format($stats['pending_orders']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2" data-translate>คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>
    </div>

    <!-- Additional Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0 w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center text-2xl">
                    ⚠️
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>สินค้าใกล้หมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['low_stock']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0 w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center text-2xl">
                    ❌
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>สินค้าหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['out_of_stock']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center text-2xl">
                    👥
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>ลูกค้า</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_customers']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0 w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center text-2xl">
                    🏷️
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>หมวดหมู่</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_categories']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0 w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center text-2xl">
                    ⭐
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>คะแนนเฉลี่ย</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['average_rating'] ?? 0, 1) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><span data-translate>📋 คำสั่งซื้อล่าสุด</span></h2>
            <a href="{{ route('admin.ecommerce.orders.index') }}" class="text-sm text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 font-medium" data-translate>
                ดูทั้งหมด →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>เลขที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>ลูกค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>ยอดรวม</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>วันที่</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="text-sm font-medium text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300">
                                    #{{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    @if($order->user)
                                        {{ $order->user->name }}
                                    @else
                                        <span data-translate>ไม่ระบุ</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $order->user->email ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $order->items->sum('quantity') }} <span data-translate>รายการ</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
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
                                        'completed' => 'เสร็จสิ้น',
                                        'cancelled' => 'ยกเลิก',
                                    ];
                                @endphp
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}" data-translate>
                                    {{ $statusLabels[$order->status] ?? $order->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $order->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400" data-translate>
                                ยังไม่มีคำสั่งซื้อ
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Low Stock Products -->
    @if(!empty($lowStockProducts) && $lowStockProducts->count() > 0)
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><span data-translate>⚠️ สินค้าใกล้หมดสต็อก</span></h2>
            <a href="{{ route('admin.ecommerce.products.index', ['stock_status' => 'low_stock']) }}" class="text-sm text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 font-medium" data-translate>
                ดูทั้งหมด →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>สินค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">SKU</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>จำนวนคงเหลือ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>ยอดขาย</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" data-translate>การกระทำ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($lowStockProducts as $product)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $product->sku }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold {{ $product->stock_quantity <= 0 ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                                    {{ $product->stock_quantity }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $product->sales_count ?? 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('admin.ecommerce.products.show', $product) }}" class="text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 font-medium" data-translate>
                                    ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
/**
 * ฟังก์ชันแปลภาษาด้วย Google Translate API
 *
 * @param {string} targetLang รหัสภาษาเป้าหมาย (en, zh, ja)
 */
async function translatePage(targetLang) {
    // ถ้าเลือกภาษาไทย ให้โหลดหน้าใหม่เพื่อแสดงข้อความต้นฉบับ
    if (targetLang === 'th') {
        location.reload();
        return;
    }

    // ดึง elements ที่มี data-translate attribute
    const elements = document.querySelectorAll('[data-translate]');

    try {
        // สร้าง array ของข้อความที่ต้องการแปล
        const textsToTranslate = Array.from(elements).map(el => {
            // เก็บข้อความต้นฉบับไว้ใน dataset
            if (!el.dataset.originalText) {
                el.dataset.originalText = el.textContent.trim();
            }
            return el.dataset.originalText;
        });

        // เรียก Google Translate API
        const apiKey = '{{ config("services.google_translate.key", "") }}';

        if (!apiKey) {
            console.warn('Google Translate API key not configured');
            return;
        }

        const response = await fetch(`https://translation.googleapis.com/language/translate/v2?key=${apiKey}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                q: textsToTranslate,
                source: 'th',
                target: targetLang,
                format: 'text'
            })
        });

        const data = await response.json();

        // อัพเดทข้อความที่แปลแล้ว
        if (data.data && data.data.translations) {
            data.data.translations.forEach((translation, index) => {
                if (elements[index]) {
                    elements[index].textContent = translation.translatedText;
                }
            });
        }

    } catch (error) {
        console.error('Translation error:', error);
    }
}

// ติดตั้ง watcher สำหรับ Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.watch('language', (value) => {
        if (value !== 'th') {
            translatePage(value);
        }
    });
});
</script>
@endpush
@endsection
