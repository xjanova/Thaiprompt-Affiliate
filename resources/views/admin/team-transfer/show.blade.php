@extends('layouts.admin')

@section('title', 'รายละเอียดคำขอย้ายทีม #' . $request->id)

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Back Button --}}
    <a href="{{ route('admin.team-transfer.index') }}"
       class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-6 transition">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        กลับไปรายการคำขอ
    </a>

    {{-- Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-green-800 dark:text-green-300 font-medium">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-600 dark:text-green-400">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div class="flex items-start gap-4">
                <div class="p-4 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                        คำขอย้ายทีม #{{ $request->id }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        สร้างเมื่อ {{ $request->created_at->locale('th')->isoFormat('D MMMM YYYY HH:mm น.') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                {{-- Status Badge --}}
                <span class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-bold
                    @if($request->status === 'pending') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300
                    @elseif($request->status === 'approved') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                    @elseif($request->status === 'rejected') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                    @elseif($request->status === 'paid') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300
                    @elseif($request->status === 'processing') bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300
                    @elseif($request->status === 'completed') bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300
                    @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                    @endif shadow-lg">
                    <span class="w-2.5 h-2.5 rounded-full mr-2
                        @if($request->status === 'pending') bg-yellow-500 animate-pulse
                        @elseif($request->status === 'approved') bg-green-500
                        @elseif($request->status === 'rejected') bg-red-500
                        @elseif($request->status === 'paid') bg-blue-500 animate-pulse
                        @elseif($request->status === 'processing') bg-purple-500 animate-pulse
                        @elseif($request->status === 'completed') bg-emerald-500
                        @else bg-gray-500
                        @endif"></span>
                    {{ $request->status_label }}
                </span>

                @if($request->canBeProcessed())
                    <a href="{{ route('admin.team-transfer.edit', $request) }}"
                       class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold rounded-lg shadow-lg transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ดำเนินการย้ายทีม
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Member Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ข้อมูลสมาชิก</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">ชื่อสมาชิก</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $request->user->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">รหัสสมาชิก</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $request->member->member_code ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">อีเมล</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $request->user->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">เบอร์โทร</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $request->user->phone ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            {{-- Transfer Details --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">รายละเอียดการย้าย</h2>

                {{-- Unilevel Sponsors --}}
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Unilevel Sponsors</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-lg p-4 border-2 border-red-200 dark:border-red-800">
                            <p class="text-xs text-red-600 dark:text-red-400 mb-2 font-semibold">แม่ทีมเดิม</p>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $request->oldSponsor->user->name ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $request->oldSponsor->member_code ?? 'N/A' }}</p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4 border-2 border-green-200 dark:border-green-800">
                            <p class="text-xs text-green-600 dark:text-green-400 mb-2 font-semibold">แม่ทีมใหม่</p>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $request->newSponsor->user->name ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $request->newSponsor->member_code ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Binary Parents --}}
                @if($request->old_binary_parent_id || $request->new_binary_parent_id)
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Binary Parents</h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            @if($request->old_binary_parent_id)
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Binary Parent เดิม</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $request->oldBinaryParent->user->name ?? 'N/A' }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $request->oldBinaryParent->member_code ?? 'N/A' }}
                                        @if($request->old_binary_position)
                                            <span class="ml-2 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs rounded">
                                                {{ $request->old_binary_position === 'left' ? 'ซ้าย' : 'ขวา' }}
                                            </span>
                                        @endif
                                    </p>
                                </div>
                            @endif

                            @if($request->new_binary_parent_id)
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Binary Parent ใหม่</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $request->newBinaryParent->user->name ?? 'N/A' }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $request->newBinaryParent->member_code ?? 'N/A' }}
                                        @if($request->new_binary_position)
                                            <span class="ml-2 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs rounded">
                                                {{ $request->new_binary_position === 'left' ? 'ซ้าย' : 'ขวา' }}
                                            </span>
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Reason & Notes --}}
            @if($request->reason || $request->notes)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    @if($request->reason)
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เหตุผลในการย้าย</h3>
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                <p class="text-gray-700 dark:text-gray-300">{{ $request->reason }}</p>
                            </div>
                        </div>
                    @endif

                    @if($request->notes)
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ</h3>
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                <p class="text-gray-700 dark:text-gray-300">{{ $request->notes }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Rejection Reason --}}
            @if($request->status === 'rejected' && $request->rejection_reason)
                <div class="bg-red-50 dark:bg-red-900/20 rounded-xl shadow-lg p-6 border-2 border-red-200 dark:border-red-800">
                    <h3 class="text-lg font-bold text-red-800 dark:text-red-300 mb-2">เหตุผลที่ปฏิเสธ</h3>
                    <p class="text-red-700 dark:text-red-400 mb-2">{{ $request->rejection_reason }}</p>
                    @if($request->rejecter)
                        <p class="text-sm text-red-600 dark:text-red-500">
                            ปฏิเสธโดย: {{ $request->rejecter->name }} • {{ $request->rejected_at->locale('th')->diffForHumans() }}
                        </p>
                    @endif
                </div>
            @endif

            {{-- Admin Notes --}}
            @if($request->admin_notes)
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-800">
                    <h3 class="text-lg font-bold text-blue-800 dark:text-blue-300 mb-2">หมายเหตุจาก Admin</h3>
                    <p class="text-blue-700 dark:text-blue-400 mb-2">{{ $request->admin_notes }}</p>
                    @if($request->processor)
                        <p class="text-sm text-blue-600 dark:text-blue-500">
                            โดย: {{ $request->processor->name }} • {{ $request->processed_at->locale('th')->diffForHumans() }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Payment Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">💰 ข้อมูลการชำระเงิน</h3>

                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">ค่าธรรมเนียม</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($request->transfer_fee, 2) }} บาท</span>
                    </div>

                    @if($request->paid_at)
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center text-green-600 dark:text-green-400 mb-2">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-semibold">ชำระเงินแล้ว</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                {{ $request->paid_at->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}
                            </p>
                            @if($request->wallet_transaction_id)
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Transaction ID: #{{ $request->wallet_transaction_id }}
                                </p>
                            @endif
                        </div>
                    @else
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center text-yellow-600 dark:text-yellow-400">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-semibold">ยังไม่ชำระเงิน</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Timeline --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">⏱️ Timeline</h3>

                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">สร้างคำขอ</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $request->created_at->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}</p>
                        </div>
                    </div>

                    @if($request->approved_at)
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">อนุมัติโดยแม่ทีมเดิม</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $request->approved_at->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}
                                    @if($request->approver)
                                        <br>โดย: {{ $request->approver->name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif

                    @if($request->paid_at)
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">ชำระค่าธรรมเนียม</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $request->paid_at->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($request->processed_at)
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">ดำเนินการเสร็จสิ้น</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $request->processed_at->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}
                                    @if($request->processor)
                                        <br>โดย: {{ $request->processor->name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Admin Actions --}}
            @if($request->canBeProcessed())
                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl shadow-lg p-6 border-2 border-green-200 dark:border-green-800">
                    <h3 class="text-lg font-bold text-green-800 dark:text-green-300 mb-4">⚡ พร้อมดำเนินการ</h3>
                    <p class="text-sm text-green-700 dark:text-green-400 mb-4">
                        คำขอนี้ชำระเงินแล้ว พร้อมดำเนินการย้ายทีม
                    </p>
                    <a href="{{ route('admin.team-transfer.edit', $request) }}"
                       class="block w-full px-4 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold rounded-lg shadow-lg text-center transition">
                        ดำเนินการย้ายทีม
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
