@extends('layouts.user')

@section('title', 'คำขอย้ายทีมของฉัน')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                📋 คำขอย้ายทีมของฉัน
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                ติดตามสถานะคำขอย้ายทีมของคุณ
            </p>
        </div>

        <a href="{{ route('user.team-transfer.create') }}"
           class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 dark:from-blue-500 dark:to-blue-600 dark:hover:from-blue-600 dark:hover:to-blue-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            สร้างคำขอใหม่
        </a>
    </div>

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

    {{-- Requests List --}}
    @if($requests->count() > 0)
        <div class="grid gap-6">
            @foreach($requests as $request)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="p-6">
                        {{-- Header with Status --}}
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                            <div class="flex items-start gap-3">
                                <div class="p-3 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                        คำขอ #{{ $request->id }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $request->created_at->locale('th')->diffForHumans() }}
                                    </p>
                                </div>
                            </div>

                            {{-- Status Badge --}}
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold
                                @if($request->status === 'pending') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300
                                @elseif($request->status === 'approved') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                @elseif($request->status === 'rejected') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                @elseif($request->status === 'paid') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300
                                @elseif($request->status === 'processing') bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300
                                @elseif($request->status === 'completed') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                                @endif">
                                <span class="w-2 h-2 rounded-full mr-2
                                    @if($request->status === 'pending') bg-yellow-500
                                    @elseif($request->status === 'approved') bg-green-500
                                    @elseif($request->status === 'rejected') bg-red-500
                                    @elseif($request->status === 'paid') bg-blue-500
                                    @elseif($request->status === 'processing') bg-purple-500
                                    @elseif($request->status === 'completed') bg-green-500
                                    @else bg-gray-500
                                    @endif"></span>
                                {{ $request->status_label }}
                            </span>
                        </div>

                        {{-- Transfer Details --}}
                        <div class="grid md:grid-cols-2 gap-6 mb-6">
                            {{-- From --}}
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">แม่ทีมเดิม</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ $request->oldSponsor->user->name ?? 'N/A' }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $request->oldSponsor->member_code ?? 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            {{-- To --}}
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">แม่ทีมใหม่</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ $request->newSponsor->user->name ?? 'N/A' }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $request->newSponsor->member_code ?? 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Reason --}}
                        @if($request->reason)
                            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">เหตุผลในการย้าย</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $request->reason }}</p>
                            </div>
                        @endif

                        {{-- Rejection Reason --}}
                        @if($request->status === 'rejected' && $request->rejection_reason)
                            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                <p class="text-xs text-red-500 dark:text-red-400 mb-1">เหตุผลที่ปฏิเสธ</p>
                                <p class="text-sm text-red-700 dark:text-red-300">{{ $request->rejection_reason }}</p>
                            </div>
                        @endif

                        {{-- Actions --}}
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('user.team-transfer.show', $request) }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                ดูรายละเอียด
                            </a>

                            @if($request->canBePaid())
                                <form method="POST" action="{{ route('user.team-transfer.pay', $request) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold rounded-lg shadow hover:shadow-lg transition"
                                            onclick="return confirm('คุณต้องการชำระค่าธรรมเนียม {{ number_format($request->transfer_fee, 2) }} บาทใช่หรือไม่?')">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        ชำระเงิน {{ number_format($request->transfer_fee, 2) }} บาท
                                    </button>
                                </form>
                            @endif

                            @if($request->canBeCancelled())
                                <form method="POST" action="{{ route('user.team-transfer.cancel', $request) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-700 dark:text-red-300 rounded-lg transition"
                                            onclick="return confirm('คุณต้องการยกเลิกคำขอนี้ใช่หรือไม่?')">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        ยกเลิก
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $requests->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center border border-gray-200 dark:border-gray-700">
            <div class="max-w-md mx-auto">
                <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                    ยังไม่มีคำขอย้ายทีม
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    คุณยังไม่เคยสร้างคำขอย้ายทีม เริ่มสร้างคำขอแรกของคุณได้เลย
                </p>
                <a href="{{ route('user.team-transfer.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    สร้างคำขอย้ายทีม
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
