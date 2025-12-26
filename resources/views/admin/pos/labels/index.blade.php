@extends('layouts.admin')

@section('title', 'ระบบพิมพ์ฉลาก Barcode - POS')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                🏷️ ระบบพิมพ์ฉลาก Barcode
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                จัดการการพิมพ์ฉลากสินค้าและใบปะสินค้าสำหรับระบบ POS
            </p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        {{-- Total Prints --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">การพิมพ์ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($stats['total_prints']) }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Today's Prints --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">พิมพ์วันนี้</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($stats['today_prints']) }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- This Month --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">พิมพ์เดือนนี้</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($stats['this_month_prints']) }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Labels --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ฉลากทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($stats['total_labels']) }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Print Product Labels --}}
        <a href="{{ route('admin.pos.labels.print-product') }}"
           class="group bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl shadow-lg p-8 hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/30 transition">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-white mb-1">พิมพ์ฉลากสินค้า</h3>
                    <p class="text-blue-100">ฉลากราคา, ฉลากติดสินค้า, Barcode</p>
                </div>
                <svg class="w-6 h-6 text-white group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        {{-- Print Shipping Labels --}}
        <a href="{{ route('admin.pos.labels.print-shipping') }}"
           class="group bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-xl shadow-lg p-8 hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/30 transition">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-white mb-1">พิมพ์ใบปะสินค้า</h3>
                    <p class="text-green-100">ใบส่งของ, ที่อยู่ลูกค้า, Tracking</p>
                </div>
                <svg class="w-6 h-6 text-white group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
    </div>

    {{-- Template Management Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Manage Templates --}}
        <a href="{{ route('admin.pos.labels.templates.index') }}"
           class="group bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-xl shadow-lg p-8 hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/30 transition">
                    <i class="fas fa-layer-group text-3xl text-white"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-white mb-1">จัดการ Templates</h3>
                    <p class="text-purple-100">ดู แก้ไข และจัดการ Template ทั้งหมด</p>
                </div>
                <svg class="w-6 h-6 text-white group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        {{-- Design New Template --}}
        <a href="{{ route('admin.pos.labels.templates.create') }}"
           class="group bg-gradient-to-br from-pink-500 to-pink-600 dark:from-pink-600 dark:to-pink-700 rounded-xl shadow-lg p-8 hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/30 transition">
                    <i class="fas fa-paint-brush text-3xl text-white"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-white mb-1">ออกแบบ Template ใหม่</h3>
                    <p class="text-pink-100">สร้างและออกแบบฉลากด้วย Designer</p>
                </div>
                <svg class="w-6 h-6 text-white group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
    </div>

    {{-- Recent Prints & Popular Templates --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Prints --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">การพิมพ์ล่าสุด</h2>
            </div>
            <div class="p-6">
                @forelse($recentPrints as $print)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $print->print_type_name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $print->total_labels }} ฉลาก • {{ $print->user->name }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-{{ $print->status_color }}-100 text-{{ $print->status_color }}-800 dark:bg-{{ $print->status_color }}-900/30 dark:text-{{ $print->status_color }}-400">
                                {{ $print->status_name }}
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                {{ $print->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-500 dark:text-gray-400 py-8">ยังไม่มีประวัติการพิมพ์</p>
                @endforelse

                @if($recentPrints->count() > 0)
                    <div class="mt-4 text-center">
                        <a href="{{ route('admin.pos.labels.history') }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                            ดูทั้งหมด →
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Popular Templates --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Template ยอดนิยม</h2>
            </div>
            <div class="p-6">
                @forelse($popularTemplates as $template)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $template->name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $template->pos_category_name }} • {{ $template->printer_type_name }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ number_format($template->usage_count) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">ครั้ง</p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-500 dark:text-gray-400 py-8">ยังไม่มี template</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
