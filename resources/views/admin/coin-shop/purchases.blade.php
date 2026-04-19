{{--
    หน้าจัดการคำสั่งซื้อ Coin Shop (Admin)
--}}

@extends('layouts.admin-v3')

@section('title', $pageTitle ?? 'จัดการคำสั่งซื้อ')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                📋 จัดการคำสั่งซื้อ Coin Shop
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">ดูและจัดการคำสั่งซื้อทั้งหมด</p>
        </div>
        <a href="{{ route('admin.coin-shop.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับไปจัดการสินค้า
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_purchases']) }}</p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/30 rounded-xl shadow p-4">
            <p class="text-sm text-yellow-600 dark:text-yellow-400">รอดำเนินการ</p>
            <p class="text-xl font-bold text-yellow-700 dark:text-yellow-300">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/30 rounded-xl shadow p-4">
            <p class="text-sm text-blue-600 dark:text-blue-400">กำลังดำเนินการ</p>
            <p class="text-xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($stats['processing']) }}</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/30 rounded-xl shadow p-4">
            <p class="text-sm text-green-600 dark:text-green-400">สำเร็จ</p>
            <p class="text-xl font-bold text-green-700 dark:text-green-300">{{ number_format($stats['completed']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Coins รวม</p>
            <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['total_coins_spent'], 0) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
        <form action="{{ route('admin.coin-shop.purchases') }}" method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text"
                       name="search"
                       value="{{ $search ?? '' }}"
                       placeholder="ค้นหาเลขที่/ชื่อผู้ซื้อ/รหัส..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">ทุกสถานะ</option>
                @foreach($statuses as $key => $status)
                    <option value="{{ $key }}" {{ $currentStatus === $key ? 'selected' : '' }}>
                        {{ $status['icon'] }} {{ $status['name'] }}
                    </option>
                @endforeach
            </select>

            <select name="type" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">ทุกประเภท</option>
                @foreach($types as $key => $typeInfo)
                    <option value="{{ $key }}" {{ $currentType === $key ? 'selected' : '' }}>
                        {{ $typeInfo['icon'] }} {{ $typeInfo['name'] }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white rounded-lg hover:bg-gray-900 dark:hover:bg-gray-500 transition">
                ค้นหา
            </button>
        </form>
    </div>

    {{-- Purchases Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">เลขที่</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ผู้ซื้อ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สินค้า</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จำนวน</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ราคา</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">วันที่</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($purchases as $purchase)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $purchase->order_number }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $purchase->user?->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $purchase->user?->email }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-gray-900 dark:text-white">{{ Str::limit($purchase->product_name, 30) }}</p>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $purchase->product_type_info['icon'] ?? '🎁' }} {{ $purchase->product_type_info['name'] ?? $purchase->product_type }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-900 dark:text-white">
                            {{ $purchase->quantity }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($purchase->total_coins, 0) }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs font-medium bg-{{ $purchase->status_color }}-100 dark:bg-{{ $purchase->status_color }}-900 text-{{ $purchase->status_color }}-800 dark:text-{{ $purchase->status_color }}-200 rounded-full">
                                {{ $purchase->status_icon }} {{ $purchase->status_name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ $purchase->created_at->format('d/m/y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.coin-shop.purchases.detail', $purchase) }}"
                               class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-800 transition">
                                ดู
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            ไม่พบคำสั่งซื้อ
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $purchases->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
