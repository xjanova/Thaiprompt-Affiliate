@extends('layouts.admin')

@section('title', 'รายละเอียดคอมมิชชั่น')

@section('content')
<div class="space-y-6">
    <!-- Header with Back Button and Actions -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-800 rounded-xl shadow-md p-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.commissions.index') }}"
               class="inline-flex items-center justify-center w-10 h-10 bg-gray-100 hover:bg-gray-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">รายละเอียดคอมมิชชั่น</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Commission #{{ $commission->id }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($commission->status === 'pending')
                <button type="button" onclick="openApproveModal()"
                        class="inline-flex items-center px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    อนุมัติ
                </button>
                <button type="button" onclick="openRejectModal()"
                        class="inline-flex items-center px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    ปฏิเสธ
                </button>
            @elseif($commission->status === 'approved')
                <button type="button" onclick="openPayModal()"
                        class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    จ่ายเงิน
                </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Commission Details Card -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        ข้อมูลคอมมิชชั่น
                    </h2>
                    <span class="inline-flex items-center px-3 py-1.5 text-sm font-semibold rounded-full
                        {{ $commission->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' : '' }}
                        {{ $commission->status === 'approved' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : '' }}
                        {{ $commission->status === 'paid' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' : '' }}
                        {{ $commission->status === 'rejected' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' : '' }}">
                        @if($commission->status === 'pending')
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @elseif($commission->status === 'approved')
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @elseif($commission->status === 'paid')
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @endif
                        {{ ucfirst($commission->status) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 border border-indigo-200 dark:border-indigo-700 rounded-xl p-5">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-indigo-700 dark:text-indigo-400">จำนวนเงิน</label>
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-indigo-900 dark:text-indigo-200">{{ number_format($commission->amount, 2) }} ฿</div>
                    </div>

                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border border-purple-200 dark:border-purple-700 rounded-xl p-5">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-purple-700 dark:text-purple-400">เปอร์เซ็นต์</label>
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-purple-900 dark:text-purple-200">{{ $commission->percentage }}%</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            ประเภท
                        </label>
                        <span class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                            @if($commission->type === 'referral')
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            @elseif($commission->type === 'sale')
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                            {{ ucfirst($commission->type) }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            วันที่สร้าง
                        </label>
                        <div class="text-sm text-gray-900 dark:text-white font-medium">
                            {{ $commission->created_at->format('d/m/Y H:i') }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $commission->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>

                @if($commission->reference)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                            </svg>
                            Reference
                        </label>
                        <div class="text-sm text-gray-900 dark:text-white font-mono bg-gray-50 dark:bg-slate-700 px-4 py-3 rounded-lg">
                            {{ $commission->reference }}
                        </div>
                    </div>
                @endif

                @if($commission->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            หมายเหตุ
                        </label>
                        <div class="text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-slate-700 px-4 py-3 rounded-lg">
                            {{ $commission->notes }}
                        </div>
                    </div>
                @endif
            </div>

            <!-- Affiliate Information Card -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    ข้อมูล Affiliate
                </h2>
                <div class="flex items-center gap-5 mb-6 p-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-600 rounded-xl">
                    <img src="{{ $commission->affiliate->user->profile_picture_url }}"
                         alt="{{ $commission->affiliate->user->name }}"
                         class="w-20 h-20 rounded-full object-cover border-4 border-white dark:border-slate-500 shadow-md">
                    <div class="flex-1">
                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $commission->affiliate->user->name }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center mt-1">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ $commission->affiliate->user->email }}
                        </div>
                    </div>
                    <a href="{{ route('admin.affiliates.show', $commission->affiliate) }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        ดูโปรไฟล์
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                        <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">รหัส Affiliate</label>
                        <div class="text-sm font-mono font-semibold text-gray-900 dark:text-white">{{ $commission->affiliate->code }}</div>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                        <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">ระดับ</label>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">Level {{ $commission->affiliate->level }}</div>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                        <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">สถานะ</label>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            {{ $commission->affiliate->status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                            {{ ucfirst($commission->affiliate->status) }}
                        </span>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                        <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">คอมมิชชั่น</label>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $commission->affiliate->commission_rate }}%</div>
                    </div>
                </div>
            </div>

            @if($commission->user)
            <!-- Customer Information Card -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    ข้อมูลลูกค้า
                </h2>
                <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl">
                    <img src="{{ $commission->user->profile_picture_url }}"
                         alt="{{ $commission->user->name }}"
                         class="w-14 h-14 rounded-full object-cover border-2 border-white dark:border-slate-600 shadow">
                    <div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $commission->user->name }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $commission->user->email }}</div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Timeline and Activity Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Timeline Card -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Timeline
                </h2>
                <div class="space-y-4">
                    <!-- Created -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            @if($commission->approved_at || $commission->rejected_at || $commission->paid_at)
                                <div class="w-0.5 h-full bg-gray-200 dark:bg-slate-700 mt-2"></div>
                            @endif
                        </div>
                        <div class="flex-1 pb-6">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">สร้างคอมมิชชั่น</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $commission->created_at->format('d/m/Y H:i') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-500 mt-0.5">{{ $commission->created_at->diffForHumans() }}</div>
                        </div>
                    </div>

                    <!-- Approved -->
                    @if($commission->approved_at)
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            @if($commission->paid_at)
                                <div class="w-0.5 h-full bg-gray-200 dark:bg-slate-700 mt-2"></div>
                            @endif
                        </div>
                        <div class="flex-1 pb-6">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">อนุมัติ</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $commission->approved_at->format('d/m/Y H:i') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-500 mt-0.5">{{ $commission->approved_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endif

                    <!-- Paid -->
                    @if($commission->paid_at)
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">จ่ายเงิน</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $commission->paid_at->format('d/m/Y H:i') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-500 mt-0.5">{{ $commission->paid_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endif

                    <!-- Rejected -->
                    @if($commission->rejected_at)
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">ปฏิเสธ</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $commission->rejected_at->format('d/m/Y H:i') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-500 mt-0.5">{{ $commission->rejected_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Stats Card -->
            <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                <h3 class="text-lg font-bold mb-5 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    สถิติด่วน
                </h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-white/10 rounded-lg backdrop-blur-sm">
                        <span class="text-sm opacity-90">อายุคอมมิชชั่น</span>
                        <span class="font-bold text-lg">{{ $commission->created_at->diffInDays(now()) }} วัน</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white/10 rounded-lg backdrop-blur-sm">
                        <span class="text-sm opacity-90">ยอดรวม Affiliate</span>
                        <span class="font-bold text-lg">{{ number_format($commission->affiliate->commissions()->sum('amount'), 2) }} ฿</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white/10 rounded-lg backdrop-blur-sm">
                        <span class="text-sm opacity-90">คอมมิชชั่นทั้งหมด</span>
                        <span class="font-bold text-lg">{{ $commission->affiliate->commissions()->count() }} รายการ</span>
                    </div>
                </div>
            </div>
        </div>
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
            <form action="{{ route('admin.commissions.approve', $commission) }}" method="POST" class="flex-1">
                @csrf
                <button type="submit" class="w-full px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                    อนุมัติ
                </button>
            </form>
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
        <form action="{{ route('admin.commissions.reject', $commission) }}" method="POST">
            @csrf
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล (ถ้ามี)</label>
                <textarea name="reason" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="ระบุเหตุผลในการปฏิเสธ..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeRejectModal()" class="flex-1 px-4 py-2.5 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-300 rounded-lg font-medium transition">
                    ยกเลิก
                </button>
                <button type="submit" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                    ปฏิเสธ
                </button>
            </div>
        </form>
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
            <form action="{{ route('admin.commissions.pay', $commission) }}" method="POST" class="flex-1">
                @csrf
                <button type="submit" class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                    จ่ายเงิน
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function openApproveModal() {
        document.getElementById('approveModal').classList.remove('hidden');
    }

    function closeApproveModal() {
        document.getElementById('approveModal').classList.add('hidden');
    }

    function openRejectModal() {
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }

    function openPayModal() {
        document.getElementById('payModal').classList.remove('hidden');
    }

    function closePayModal() {
        document.getElementById('payModal').classList.add('hidden');
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
