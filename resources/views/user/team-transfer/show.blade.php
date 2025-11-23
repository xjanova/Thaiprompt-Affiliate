@extends('layouts.user')

@section('title', 'รายละเอียดคำขอย้ายทีม #' . $request->id)

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Back Button --}}
    <a href="{{ route('user.team-transfer.index') }}"
       class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-6 transition">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        กลับไปรายการคำขอ
    </a>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-green-800 dark:text-green-300 font-medium">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-800 dark:text-red-300 font-medium">{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <div class="max-w-5xl mx-auto">
        {{-- Header with Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 md:p-8 mb-6 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
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

                {{-- Status Badge --}}
                <span class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-bold
                    @if($request->status === 'pending') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300
                    @elseif($request->status === 'approved') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                    @elseif($request->status === 'rejected') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                    @elseif($request->status === 'paid') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300
                    @elseif($request->status === 'processing') bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300
                    @elseif($request->status === 'completed') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                    @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                    @endif shadow-lg">
                    <span class="w-2.5 h-2.5 rounded-full mr-2
                        @if($request->status === 'pending') bg-yellow-500 animate-pulse
                        @elseif($request->status === 'approved') bg-green-500
                        @elseif($request->status === 'rejected') bg-red-500
                        @elseif($request->status === 'paid') bg-blue-500 animate-pulse
                        @elseif($request->status === 'processing') bg-purple-500 animate-pulse
                        @elseif($request->status === 'completed') bg-green-500
                        @else bg-gray-500
                        @endif"></span>
                    {{ $request->status_label }}
                </span>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-6 mb-6">
            {{-- Main Content --}}
            <div class="md:col-span-2 space-y-6">
                {{-- Transfer Details --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">รายละเอียดการย้าย</h2>

                    {{-- From/To Visual --}}
                    <div class="relative mb-6">
                        <div class="grid grid-cols-2 gap-4">
                            {{-- From --}}
                            <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-xl p-6 border-2 border-red-200 dark:border-red-800">
                                <div class="flex items-center justify-center mb-4">
                                    <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-center text-red-600 dark:text-red-400 mb-2 font-semibold">แม่ทีมเดิม</p>
                                <p class="text-center font-bold text-gray-900 dark:text-white mb-1">
                                    {{ $request->oldSponsor->user->name ?? 'N/A' }}
                                </p>
                                <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                                    {{ $request->oldSponsor->member_code ?? 'N/A' }}
                                </p>
                            </div>

                            {{-- To --}}
                            <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-6 border-2 border-green-200 dark:border-green-800">
                                <div class="flex items-center justify-center mb-4">
                                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-center text-green-600 dark:text-green-400 mb-2 font-semibold">แม่ทีมใหม่</p>
                                <p class="text-center font-bold text-gray-900 dark:text-white mb-1">
                                    {{ $request->newSponsor->user->name ?? 'N/A' }}
                                </p>
                                <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                                    {{ $request->newSponsor->member_code ?? 'N/A' }}
                                </p>
                            </div>
                        </div>

                        {{-- Arrow --}}
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                            <div class="w-12 h-12 bg-white dark:bg-gray-800 rounded-full border-4 border-blue-500 flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Reason --}}
                    @if($request->reason)
                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เหตุผลในการย้าย</h3>
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                <p class="text-gray-700 dark:text-gray-300">{{ $request->reason }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Notes --}}
                    @if($request->notes)
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ</h3>
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                <p class="text-gray-700 dark:text-gray-300">{{ $request->notes }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Rejection Reason --}}
                @if($request->status === 'rejected' && $request->rejection_reason)
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-xl shadow-lg p-6 border-2 border-red-200 dark:border-red-800">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-red-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-red-800 dark:text-red-300 mb-2">เหตุผลที่ปฏิเสธ</h3>
                                <p class="text-red-700 dark:text-red-400">{{ $request->rejection_reason }}</p>
                                @if($request->rejecter)
                                    <p class="text-sm text-red-600 dark:text-red-500 mt-2">
                                        โดย: {{ $request->rejecter->name }} • {{ $request->rejected_at->locale('th')->diffForHumans() }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Admin Notes --}}
                @if($request->admin_notes)
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-800">
                        <h3 class="text-lg font-bold text-blue-800 dark:text-blue-300 mb-2">หมายเหตุจาก Admin</h3>
                        <p class="text-blue-700 dark:text-blue-400">{{ $request->admin_notes }}</p>
                        @if($request->processor)
                            <p class="text-sm text-blue-600 dark:text-blue-500 mt-2">
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
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลการชำระเงิน</h3>

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
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $request->paid_at->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}
                                </p>
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
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Timeline</h3>

                    <div class="space-y-4">
                        {{-- Created --}}
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

                        {{-- Approved --}}
                        @if($request->approved_at)
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">อนุมัติโดยแม่ทีมเดิม</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $request->approved_at->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}</p>
                                </div>
                            </div>
                        @endif

                        {{-- Paid --}}
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

                        {{-- Processed --}}
                        @if($request->processed_at)
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">ดำเนินการเสร็จสิ้น</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $request->processed_at->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">การจัดการ</h3>

                    <div class="space-y-3">
                        @if($request->canBePaid())
                            <form method="POST" action="{{ route('user.team-transfer.pay', $request) }}">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center"
                                        onclick="return confirm('คุณต้องการชำระค่าธรรมเนียม {{ number_format($request->transfer_fee, 2) }} บาทใช่หรือไม่?')">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    ชำระเงิน {{ number_format($request->transfer_fee, 2) }} บาท
                                </button>
                            </form>
                        @endif

                        @if($request->canBeCancelled())
                            <form method="POST" action="{{ route('user.team-transfer.cancel', $request) }}">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-3 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-700 dark:text-red-300 font-semibold rounded-lg transition flex items-center justify-center"
                                        onclick="return confirm('คุณต้องการยกเลิกคำขอนี้ใช่หรือไม่?{{ $request->paid_at ? '\n\nเงินจะถูกคืนเข้า Wallet ของคุณ' : '' }}')">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    ยกเลิกคำขอ
                                </button>
                            </form>
                        @endif

                        @if($request->status === 'completed')
                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 text-center">
                                <svg class="w-12 h-12 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm font-semibold text-green-800 dark:text-green-300">
                                    การย้ายทีมเสร็จสมบูรณ์
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
