@extends('layouts.app')

@section('title', 'Developer Dashboard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-purple-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 py-12">
    <div class="container mx-auto px-4">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white mb-2">
                        👋 สวัสดี, {{ $developerProfile->display_name }}
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400">
                        ยินดีต้อนรับสู่ Developer Dashboard
                    </p>
                </div>
                <div>
                    {!! strip_tags($developerProfile->status_badge, '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><blockquote><code><pre><table><thead><tbody><tr><th><td><hr><del><sup><sub><span><div>') !!}
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            {{-- Total Products --}}
            <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl p-6 border-2 border-white/50 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-black bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent mb-1">
                    {{ number_format($stats['total_products']) }}
                </div>
                <div class="text-sm font-semibold text-slate-600 dark:text-slate-400">สินค้าทั้งหมด</div>
            </div>

            {{-- Total Sales --}}
            <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl p-6 border-2 border-white/50 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-600 to-emerald-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-black bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-transparent mb-1">
                    {{ number_format($stats['total_sales']) }}
                </div>
                <div class="text-sm font-semibold text-slate-600 dark:text-slate-400">การขายทั้งหมด</div>
            </div>

            {{-- Total Earnings --}}
            <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl p-6 border-2 border-white/50 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-r from-purple-600 to-pink-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-black bg-gradient-to-r from-purple-600 to-pink-500 bg-clip-text text-transparent mb-1">
                    ฿{{ number_format($stats['total_earnings'], 2) }}
                </div>
                <div class="text-sm font-semibold text-slate-600 dark:text-slate-400">รายได้ทั้งหมด</div>
            </div>

            {{-- Commission Rate --}}
            <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl p-6 border-2 border-white/50 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-r from-amber-600 to-orange-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-black bg-gradient-to-r from-amber-600 to-orange-500 bg-clip-text text-transparent mb-1">
                    {{ number_format($developerProfile->commission_rate, 0) }}%
                </div>
                <div class="text-sm font-semibold text-slate-600 dark:text-slate-400">ค่าคอมมิชชั่น</div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <a href="#" class="block bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl p-6 border-2 border-white/50 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:-translate-y-1 hover:scale-105 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">➕</span>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 dark:text-white">เพิ่มสินค้าใหม่</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">อัพโหลดซอฟต์แวร์ของคุณ</p>
                    </div>
                </div>
            </a>

            <a href="#" class="block bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl p-6 border-2 border-white/50 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:-translate-y-1 hover:scale-105 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-r from-green-600 to-emerald-500 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">📊</span>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 dark:text-white">ยอดขาย</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">ดูรายงานและสถิติ</p>
                    </div>
                </div>
            </a>

            <a href="#" class="block bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl p-6 border-2 border-white/50 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:-translate-y-1 hover:scale-105 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-r from-purple-600 to-pink-500 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">💰</span>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 dark:text-white">ถอนเงิน</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">จัดการรายได้ของคุณ</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Recent Products --}}
        @if($recentProducts->count() > 0)
        <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl p-8 border-2 border-white/50 dark:border-slate-700/50 shadow-2xl">
            <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-6">📦 สินค้าล่าสุด</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-100 dark:bg-slate-700 rounded-xl">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold text-slate-900 dark:text-white">ชื่อสินค้า</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-slate-900 dark:text-white">ราคา</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-slate-900 dark:text-white">สถานะ</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-slate-900 dark:text-white">วันที่สร้าง</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($recentProducts as $product)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $product->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-900 dark:text-white font-bold">
                                {{ $product->price_label ?? '฿' . number_format($product->base_price, 0) }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full text-sm font-bold">
                                    {{ ucfirst($product->status ?? 'active') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400">
                                {{ $product->created_at->format('d/m/Y') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl p-12 border-2 border-white/50 dark:border-slate-700/50 shadow-2xl text-center">
            <div class="text-6xl mb-4">📦</div>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">ยังไม่มีสินค้า</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-6">เริ่มต้นโดยการเพิ่มสินค้าแรกของคุณ</p>
            <a href="#" class="inline-block px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-bold shadow-lg hover:scale-105 transition-all">
                ➕ เพิ่มสินค้าใหม่
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
