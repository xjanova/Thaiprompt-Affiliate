@extends('layouts.user')

@section('title', 'คำขอย้ายทีมที่ต้องอนุมัติ')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header Section --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            ✅ คำขอย้ายทีมที่ต้องอนุมัติ
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            ลูกทีมของคุณที่ขอย้ายไปหาแม่ทีมใหม่
        </p>
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

    {{-- Tabs --}}
    <div class="mb-6" x-data="{ activeTab: 'pending' }">
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
            <button @click="activeTab = 'pending'"
                    :class="activeTab === 'pending' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="px-6 py-3 border-b-2 font-semibold transition">
                รอดำเนินการ ({{ $pendingApprovals->total() }})
            </button>
            <button @click="activeTab = 'processed'"
                    :class="activeTab === 'processed' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="px-6 py-3 border-b-2 font-semibold transition">
                ดำเนินการแล้ว ({{ $processedApprovals->total() }})
            </button>
        </div>

        {{-- Pending Tab --}}
        <div x-show="activeTab === 'pending'" x-transition>
            <div class="pt-6">
                @if($pendingApprovals->count() > 0)
                    <div class="grid gap-6">
                        @foreach($pendingApprovals as $request)
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border-l-4 border-yellow-500" x-data="{ showRejectModal: false }">
                                <div class="p-6">
                                    {{-- Header --}}
                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                                        <div class="flex items-start gap-3">
                                            <div class="p-3 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg">
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                                    {{ $request->member->user->name ?? 'N/A' }}
                                                </h3>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    รหัส: {{ $request->member->member_code ?? 'N/A' }}
                                                </p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $request->created_at->locale('th')->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>

                                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                            <span class="w-2 h-2 rounded-full bg-yellow-500 mr-2 animate-pulse"></span>
                                            รอการอนุมัติ
                                        </span>
                                    </div>

                                    {{-- Transfer Info --}}
                                    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1">
                                                <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">ต้องการย้ายไปหา</p>
                                                <p class="font-bold text-gray-900 dark:text-white">
                                                    {{ $request->newSponsor->user->name ?? 'N/A' }}
                                                </p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $request->newSponsor->member_code ?? 'N/A' }}
                                                </p>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Reason --}}
                                    @if($request->reason)
                                        <div class="mb-6">
                                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เหตุผลในการย้าย</h4>
                                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                                <p class="text-gray-700 dark:text-gray-300">{{ $request->reason }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Actions --}}
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <form method="POST" action="{{ route('user.team-transfer.approve', $request) }}" class="flex-1">
                                            @csrf
                                            <button type="submit"
                                                    class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center"
                                                    onclick="return confirm('คุณต้องการอนุมัติให้ {{ $request->member->user->name ?? 'สมาชิก' }} ย้ายทีมใช่หรือไม่?')">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                อนุมัติ
                                            </button>
                                        </form>

                                        <button @click="showRejectModal = true"
                                                class="flex-1 px-6 py-3 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-700 dark:text-red-300 font-semibold rounded-lg transition flex items-center justify-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            ปฏิเสธ
                                        </button>
                                    </div>
                                </div>

                                {{-- Reject Modal --}}
                                <div x-show="showRejectModal"
                                     x-cloak
                                     @click.away="showRejectModal = false"
                                     class="fixed inset-0 z-50 overflow-y-auto"
                                     style="display: none;">
                                    <div class="flex items-center justify-center min-h-screen px-4">
                                        <div class="fixed inset-0 bg-black opacity-50"></div>

                                        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full p-6 border border-gray-200 dark:border-gray-700"
                                             @click.stop>
                                            <div class="flex items-center justify-between mb-4">
                                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">ปฏิเสธคำขอ</h3>
                                                <button @click="showRejectModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            <form method="POST" action="{{ route('user.team-transfer.reject', $request) }}">
                                                @csrf
                                                <div class="mb-4">
                                                    <label for="rejection_reason_{{ $request->id }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                        เหตุผลที่ปฏิเสธ <span class="text-red-500">*</span>
                                                    </label>
                                                    <textarea id="rejection_reason_{{ $request->id }}"
                                                              name="rejection_reason"
                                                              rows="4"
                                                              required
                                                              maxlength="500"
                                                              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:text-white transition resize-none"
                                                              placeholder="กรุณาระบุเหตุผลที่ปฏิเสธคำขอนี้"></textarea>
                                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                        สมาชิกจะเห็นเหตุผลที่คุณระบุ
                                                    </p>
                                                </div>

                                                <div class="flex gap-3">
                                                    <button type="button"
                                                            @click="showRejectModal = false"
                                                            class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition">
                                                        ยกเลิก
                                                    </button>
                                                    <button type="submit"
                                                            class="flex-1 px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition">
                                                        ยืนยันปฏิเสธ
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-8">
                        {{ $pendingApprovals->links() }}
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center border border-gray-200 dark:border-gray-700">
                        <div class="max-w-md mx-auto">
                            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                ไม่มีคำขอที่รอดำเนินการ
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                ยังไม่มีลูกทีมขอย้ายทีมในขณะนี้
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Processed Tab --}}
        <div x-show="activeTab === 'processed'" x-transition>
            <div class="pt-6">
                @if($processedApprovals->count() > 0)
                    <div class="grid gap-6">
                        @foreach($processedApprovals as $request)
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                                <div class="p-6">
                                    {{-- Header with Status --}}
                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                                        <div class="flex items-start gap-3">
                                            <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                                <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                                    {{ $request->member->user->name ?? 'N/A' }}
                                                </h3>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    รหัส: {{ $request->member->member_code ?? 'N/A' }}
                                                </p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $request->created_at->locale('th')->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Status Badge --}}
                                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold
                                            @if($request->status === 'approved') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                            @elseif($request->status === 'rejected') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                            @elseif($request->status === 'paid') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300
                                            @elseif($request->status === 'completed') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                            @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                                            @endif">
                                            <span class="w-2 h-2 rounded-full mr-2
                                                @if($request->status === 'approved') bg-green-500
                                                @elseif($request->status === 'rejected') bg-red-500
                                                @elseif($request->status === 'paid') bg-blue-500
                                                @elseif($request->status === 'completed') bg-green-500
                                                @else bg-gray-500
                                                @endif"></span>
                                            {{ $request->status_label }}
                                        </span>
                                    </div>

                                    {{-- Transfer Info --}}
                                    <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">ย้ายไปหา</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">
                                            {{ $request->newSponsor->user->name ?? 'N/A' }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $request->newSponsor->member_code ?? 'N/A' }}
                                        </p>
                                    </div>

                                    {{-- Action Date --}}
                                    @if($request->status === 'approved' && $request->approved_at)
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            อนุมัติเมื่อ: {{ $request->approved_at->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}
                                        </p>
                                    @elseif($request->status === 'rejected' && $request->rejected_at)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                            ปฏิเสธเมื่อ: {{ $request->rejected_at->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}
                                        </p>
                                        @if($request->rejection_reason)
                                            <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                                <p class="text-xs text-red-600 dark:text-red-400 mb-1">เหตุผล:</p>
                                                <p class="text-sm text-red-700 dark:text-red-300">{{ $request->rejection_reason }}</p>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-8">
                        {{ $processedApprovals->appends(['processed_page' => $processedApprovals->currentPage()])->links() }}
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center border border-gray-200 dark:border-gray-700">
                        <div class="max-w-md mx-auto">
                            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                ยังไม่มีคำขอที่ดำเนินการ
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                ยังไม่มีประวัติการอนุมัติหรือปฏิเสธคำขอ
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
