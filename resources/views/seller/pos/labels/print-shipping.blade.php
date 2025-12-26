@extends('layouts.seller')

@section('title', 'พิมพ์ใบปะสินค้า')

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                📦 พิมพ์ใบปะสินค้า
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                สร้างและพิมพ์ใบปะสินค้าจาก POS Transaction
            </p>
        </div>
        <a href="{{ route('seller.pos.labels.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 border border-gray-200 dark:border-gray-700 text-center">
        <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ฟีเจอร์กำลังพัฒนา</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            คุณสามารถพิมพ์ใบปะสินค้าได้จากหน้า POS Transaction โดยตรง
        </p>
        <a href="{{ route('admin.pos.transactions.index') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            ไปที่ POS Transactions →
        </a>
    </div>
</div>
@endsection
