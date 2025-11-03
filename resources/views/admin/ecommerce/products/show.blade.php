@extends('layouts.admin')

@section('title', 'รายละเอียดสินค้า')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📦 รายละเอียดสินค้า</h1>
        <a href="{{ route('admin.ecommerce.products.index') }}" class="text-orange-600 dark:text-orange-400 hover:text-orange-700">
            ← กลับ
        </a>
    </div>

    <!-- Product Info -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ $product->name }}</h2>
                <div class="space-y-2 text-sm">
                    <p><span class="font-semibold">SKU:</span> {{ $product->sku }}</p>
                    <p><span class="font-semibold">หมวดหมู่:</span> {{ $product->category->name ?? 'ไม่ระบุ' }}</p>
                    <p><span class="font-semibold">ราคา:</span> ฿{{ number_format($product->price, 2) }}</p>
                    <p><span class="font-semibold">สต็อก:</span> {{ $product->stock_quantity }}</p>
                    <p><span class="font-semibold">ยอดขาย:</span> {{ $stats['total_sales'] ?? 0 }} ชิ้น</p>
                    <p><span class="font-semibold">รายได้:</span> ฿{{ number_format($stats['total_revenue'] ?? 0, 2) }}</p>
                </div>
            </div>
            <div>
                @if($product->description)
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">คำอธิบาย</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $product->description }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">ยอดขาย</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_sales'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">รายได้</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($stats['total_revenue'] ?? 0) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">รีวิว</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_reviews'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">คะแนนเฉลี่ย</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['average_rating'] ?? 0, 1) }} ⭐</p>
        </div>
    </div>
</div>
@endsection
