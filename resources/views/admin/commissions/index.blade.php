@extends('layouts.admin')

@section('title', 'จัดการคอมมิชชั่น')

@section('content')
<div class="space-y-6">
    <!-- Header with Bulk Actions -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการคอมมิชชั่น</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">จัดการและติดตามคอมมิชชั่นของแอฟฟิลิเอต</p>
        </div>

        <div id="bulkActions" class="hidden flex items-center gap-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">
                เลือก <span id="selectedCount" class="font-semibold">0</span> รายการ
            </span>
            <button type="button" onclick="bulkApprove()" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                อนุมัติที่เลือก
            </button>
            <button type="button" onclick="bulkReject()" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                ปฏิเสธที่เลือก
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 border border-yellow-200 dark:border-yellow-700 rounded-xl p-5 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400 mb-1">รอดำเนินการ</p>
                    <p class="text-3xl font-bold text-yellow-900 dark:text-yellow-300" id="pendingCount">
                        {{ App\Models\Commission::where('status', 'pending')->count() }}
                    </p>
                    <p class="text-xs text-yellow-700 dark:text-yellow-500 mt-2">
                        {{ number_format(App\Models\Commission::where('status', 'pending')->sum('amount'), 2) }} ฿
                    </p>
                </div>
                <div class="w-12 h-12 bg-yellow-200 dark:bg-yellow-700 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border border-green-200 dark:border-green-700 rounded-xl p-5 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-600 dark:text-green-400 mb-1">อนุมัติแล้ว</p>
                    <p class="text-3xl font-bold text-green-900 dark:text-green-300" id="approvedCount">
                        {{ App\Models\Commission::where('status', 'approved')->count() }}
                    </p>
                    <p class="text-xs text-green-700 dark:text-green-500 mt-2">
                        {{ number_format(App\Models\Commission::where('status', 'approved')->sum('amount'), 2) }} ฿
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-200 dark:bg-green-700 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border border-blue-200 dark:border-blue-700 rounded-xl p-5 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-600 dark:text-blue-400 mb-1">จ่ายแล้ว</p>
                    <p class="text-3xl font-bold text-blue-900 dark:text-blue-300" id="paidCount">
                        {{ App\Models\Commission::where('status', 'paid')->count() }}
                    </p>
                    <p class="text-xs text-blue-700 dark:text-blue-500 mt-2">
                        {{ number_format(App\Models\Commission::where('status', 'paid')->sum('amount'), 2) }} ฿
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-200 dark:bg-blue-700 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border border-red-200 dark:border-red-700 rounded-xl p-5 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-red-600 dark:text-red-400 mb-1">ปฏิเสธ</p>
                    <p class="text-3xl font-bold text-red-900 dark:text-red-300" id="rejectedCount">
                        {{ App\Models\Commission::where('status', 'rejected')->count() }}
                    </p>
                    <p class="text-xs text-red-700 dark:text-red-500 mt-2">
                        {{ number_format(App\Models\Commission::where('status', 'rejected')->sum('amount'), 2) }} ฿
                    </p>
                </div>
                <div class="w-12 h-12 bg-red-200 dark:bg-red-700 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                ตัวกรอง
            </h3>
            <button type="button" id="toggleFilter" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>

        <form method="GET" action="{{ route('admin.commissions.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    ค้นหา
                </label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ชื่อผู้ใช้หรืออีเมล"
                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    สถานะ
                </label>
                <select name="status" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    ประเภท
                </label>
                <select name="type" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <option value="">ทั้งหมด</option>
                    <option value="referral" {{ request('type') === 'referral' ? 'selected' : '' }}>Referral</option>
                    <option value="sale" {{ request('type') === 'sale' ? 'selected' : '' }}>Sale</option>
                    <option value="bonus" {{ request('type') === 'bonus' ? 'selected' : '' }}>Bonus</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    จำนวนต่อหน้า
                </label>
                <select name="per_page" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    ค้นหา
                </button>
                <a href="{{ route('admin.commissions.index') }}" class="px-4 py-2.5 bg-gray-200 dark:bg-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-slate-500 rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
            </div>
        </form>
    </div>

    <!-- Commissions Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-4">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"
                                   class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2">
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            Affiliate
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            จำนวน
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            เปอร์เซ็นต์
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            ประเภท
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            วันที่
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            การกระทำ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($commissions as $commission)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition" data-commission-id="{{ $commission->id }}">
                            <td class="px-6 py-4">
                                @if($commission->status === 'pending')
                                    <input type="checkbox" name="selected_commissions[]" value="{{ $commission->id }}"
                                           onchange="updateBulkActions()"
                                           class="commission-checkbox w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2">
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <img src="{{ $commission->affiliate->user->profile_picture_url }}"
                                         alt="{{ $commission->affiliate->user->name }}"
                                         class="w-10 h-10 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600">
                                    <div class="ml-3">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $commission->affiliate->user->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $commission->affiliate->user->email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ number_format($commission->amount, 2) }} ฿
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                    {{ $commission->percentage }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                    @if($commission->type === 'referral')
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    @elseif($commission->type === 'sale')
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    @else
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @endif
                                    {{ ucfirst($commission->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full
                                    {{ $commission->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' : '' }}
                                    {{ $commission->status === 'approved' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : '' }}
                                    {{ $commission->status === 'paid' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' : '' }}
                                    {{ $commission->status === 'rejected' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' : '' }}">
                                    @if($commission->status === 'pending')
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @elseif($commission->status === 'approved')
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @elseif($commission->status === 'paid')
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    @else
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @endif
                                    {{ ucfirst($commission->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-gray-300">
                                    {{ $commission->created_at->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $commission->created_at->format('H:i') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <!-- View Button -->
                                    <a href="{{ route('admin.commissions.show', $commission) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition group"
                                       title="ดูรายละเอียด">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    @if($commission->status === 'pending')
                                        <!-- Approve Button -->
                                        <button type="button" onclick="approveCommission({{ $commission->id }})"
                                                class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition"
                                                title="อนุมัติ">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>

                                        <!-- Reject Button -->
                                        <button type="button" onclick="rejectCommission({{ $commission->id }})"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition"
                                                title="ปฏิเสธ">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    @elseif($commission->status === 'approved')
                                        <!-- Pay Button -->
                                        <button type="button" onclick="payCommission({{ $commission->id }})"
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition"
                                                title="จ่ายเงิน">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <span class="text-gray-500 dark:text-gray-400 text-lg font-medium">ไม่พบข้อมูลคอมมิชชั่น</span>
                                    <span class="text-gray-400 dark:text-gray-500 text-sm mt-1">ลองปรับเปลี่ยนตัวกรองเพื่อค้นหา</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($commissions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        แสดง <span class="font-semibold">{{ $commissions->firstItem() ?? 0 }}</span> ถึง
                        <span class="font-semibold">{{ $commissions->lastItem() ?? 0 }}</span> จากทั้งหมด
                        <span class="font-semibold">{{ $commissions->total() }}</span> รายการ
                    </div>
                    <div>
                        {{ $commissions->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal for Approve Confirmation -->
<div id="approveModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-md w-full p-6 transform transition-all">
        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full mb-4">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">อนุมัติคอมมิชชั่น</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-6">คุณแน่ใจหรือไม่ว่าต้องการอนุมัติคอมมิชชั่นนี้?</p>
        <div class="flex gap-3">
            <button type="button" onclick="closeApproveModal()" class="flex-1 px-4 py-2.5 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-300 rounded-lg font-medium transition">
                ยกเลิก
            </button>
            <button type="button" onclick="confirmApprove()" class="flex-1 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                อนุมัติ
            </button>
        </div>
    </div>
</div>

<!-- Modal for Reject Confirmation -->
<div id="rejectModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-md w-full p-6 transform transition-all">
        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full mb-4">
            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">ปฏิเสธคอมมิชชั่น</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-4">คุณแน่ใจหรือไม่ว่าต้องการปฏิเสธคอมมิชชั่นนี้?</p>
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล (ถ้ามี)</label>
            <textarea id="rejectReason" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="ระบุเหตุผลในการปฏิเสธ..."></textarea>
        </div>
        <div class="flex gap-3">
            <button type="button" onclick="closeRejectModal()" class="flex-1 px-4 py-2.5 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-300 rounded-lg font-medium transition">
                ยกเลิก
            </button>
            <button type="button" onclick="confirmReject()" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                ปฏิเสธ
            </button>
        </div>
    </div>
</div>

<!-- Modal for Pay Confirmation -->
<div id="payModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-md w-full p-6 transform transition-all">
        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-blue-100 dark:bg-blue-900/30 rounded-full mb-4">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">จ่ายเงินคอมมิชชั่น</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-6">คุณแน่ใจหรือไม่ว่าต้องการจ่ายเงินคอมมิชชั่นนี้? เงินจะถูกเพิ่มเข้า Wallet ของผู้ใช้</p>
        <div class="flex gap-3">
            <button type="button" onclick="closePayModal()" class="flex-1 px-4 py-2.5 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-300 rounded-lg font-medium transition">
                ยกเลิก
            </button>
            <button type="button" onclick="confirmPay()" class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                จ่ายเงิน
            </button>
        </div>
    </div>
</div>

<script>
    let currentCommissionId = null;

    // Toggle Select All
    function toggleSelectAll(checkbox) {
        const checkboxes = document.querySelectorAll('.commission-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
        updateBulkActions();
    }

    // Update Bulk Actions Visibility
    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.commission-checkbox:checked');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');

        if (checkboxes.length > 0) {
            bulkActions.classList.remove('hidden');
            bulkActions.classList.add('flex');
            selectedCount.textContent = checkboxes.length;
        } else {
            bulkActions.classList.add('hidden');
            bulkActions.classList.remove('flex');
        }
    }

    // Approve Commission
    function approveCommission(id) {
        currentCommissionId = id;
        document.getElementById('approveModal').classList.remove('hidden');
    }

    function closeApproveModal() {
        document.getElementById('approveModal').classList.add('hidden');
        currentCommissionId = null;
    }

    function confirmApprove() {
        if (currentCommissionId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/commissions/${currentCommissionId}/approve`;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Reject Commission
    function rejectCommission(id) {
        currentCommissionId = id;
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        document.getElementById('rejectReason').value = '';
        currentCommissionId = null;
    }

    function confirmReject() {
        if (currentCommissionId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/commissions/${currentCommissionId}/reject`;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            const reason = document.getElementById('rejectReason').value;
            if (reason) {
                const reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'reason';
                reasonInput.value = reason;
                form.appendChild(reasonInput);
            }

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Pay Commission
    function payCommission(id) {
        currentCommissionId = id;
        document.getElementById('payModal').classList.remove('hidden');
    }

    function closePayModal() {
        document.getElementById('payModal').classList.add('hidden');
        currentCommissionId = null;
    }

    function confirmPay() {
        if (currentCommissionId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/commissions/${currentCommissionId}/pay`;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Bulk Approve
    function bulkApprove() {
        const checkboxes = document.querySelectorAll('.commission-checkbox:checked');
        if (checkboxes.length === 0) return;

        if (confirm(`คุณแน่ใจหรือไม่ว่าต้องการอนุมัติคอมมิชชั่น ${checkboxes.length} รายการ?`)) {
            const ids = Array.from(checkboxes).map(cb => cb.value);
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/commissions/bulk-approve';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'commission_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    }

    // Bulk Reject
    function bulkReject() {
        const checkboxes = document.querySelectorAll('.commission-checkbox:checked');
        if (checkboxes.length === 0) return;

        const reason = prompt(`คุณแน่ใจหรือไม่ว่าต้องการปฏิเสธคอมมิชชั่น ${checkboxes.length} รายการ?\n\nระบุเหตุผล (ถ้ามี):`);
        if (reason !== null) {
            const ids = Array.from(checkboxes).map(cb => cb.value);
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/commissions/bulk-reject';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            if (reason) {
                const reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'reason';
                reasonInput.value = reason;
                form.appendChild(reasonInput);
            }

            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'commission_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    }

    // Close modals on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeApproveModal();
            closeRejectModal();
            closePayModal();
        }
    });
</script>

@endsection
