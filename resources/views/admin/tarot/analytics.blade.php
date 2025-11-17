@extends('layouts.admin-v3')

@section('title', 'Analytics - Tarot System')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">สถิติและการวิเคราะห์</h1>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <p class="text-gray-600 dark:text-gray-400 text-sm">การทำนายทั้งหมด</p>
            <p class="text-3xl font-bold">{{ number_format($stats['total_readings']) }}</p>
        </div>
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <p class="text-gray-600 dark:text-gray-400 text-sm">ฟรี</p>
            <p class="text-3xl font-bold text-green-600">{{ number_format($stats['free_readings']) }}</p>
        </div>
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <p class="text-gray-600 dark:text-gray-400 text-sm">จ่ายเงิน</p>
            <p class="text-3xl font-bold text-blue-600">{{ number_format($stats['paid_readings']) }}</p>
        </div>
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <p class="text-gray-600 dark:text-gray-400 text-sm">รายได้รวม</p>
            <p class="text-2xl font-bold text-green-600">฿{{ number_format($stats['total_revenue'], 2) }}</p>
        </div>
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <p class="text-gray-600 dark:text-gray-400 text-sm">ค่าเฉลี่ย</p>
            <p class="text-2xl font-bold">฿{{ number_format($stats['avg_reading_price'] ?? 0, 2) }}</p>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- By Category -->
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h3 class="text-lg font-bold mb-4">การทำนายแยกตามหมวดหมู่</h3>
            <div class="space-y-3">
                @foreach($readingsByCategory as $item)
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium">{{ $item->category->name_th }}</span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->count }} ครั้ง (฿{{ number_format($item->revenue ?? 0) }})</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($item->count / $stats['total_readings']) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- By Spread Type -->
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h3 class="text-lg font-bold mb-4">การทำนายแยกตามรูปแบบ</h3>
            <div class="space-y-3">
                @foreach($readingsBySpread as $item)
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium">{{ $item->spreadType->name_th }}</span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->count }} ครั้ง</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: {{ ($item->count / $stats['total_readings']) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
