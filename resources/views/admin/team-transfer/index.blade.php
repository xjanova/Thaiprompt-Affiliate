@extends('layouts.admin')

@section('title', 'จัดการคำขอย้ายทีม')

@section('content')
<div class="container-fluid px-4 py-6" x-data="{ showFilters: false }">
    {{-- Header with Stats --}}
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                🔄 จัดการคำขอย้ายทีม
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                ติดตามและจัดการคำขอย้ายทีมทั้งหมดในระบบ
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.team-transfer.statistics') }}"
               class="inline-flex items-center px-4 py-2 bg-purple-100 dark:bg-purple-900/30 hover:bg-purple-200 dark:hover:bg-purple-900/50 text-purple-700 dark:text-purple-300 font-semibold rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                สถิติ
            </a>
            <button @click="showFilters = !showFilters"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                ตัวกรอง
            </button>
        </div>
    </div>

    {{-- Quick Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-6">
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">ทั้งหมด</p>
                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
        </div>

        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-yellow-700 dark:text-yellow-400">รออนุมัติ</p>
                <div class="w-8 h-8 bg-yellow-200 dark:bg-yellow-800 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-yellow-800 dark:text-yellow-300">{{ number_format($stats['pending']) }}</p>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-green-700 dark:text-green-400">อนุมัติแล้ว</p>
                <div class="w-8 h-8 bg-green-200 dark:bg-green-800 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-green-800 dark:text-green-300">{{ number_format($stats['approved']) }}</p>
        </div>

        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-blue-700 dark:text-blue-400">ชำระเงินแล้ว</p>
                <div class="w-8 h-8 bg-blue-200 dark:bg-blue-800 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-blue-800 dark:text-blue-300">{{ number_format($stats['paid']) }}</p>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-purple-700 dark:text-purple-400">กำลังดำเนินการ</p>
                <div class="w-8 h-8 bg-purple-200 dark:bg-purple-800 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-purple-800 dark:text-purple-300">{{ number_format($stats['processing']) }}</p>
        </div>

        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">เสร็จสิ้น</p>
                <div class="w-8 h-8 bg-emerald-200 dark:bg-emerald-800 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-emerald-800 dark:text-emerald-300">{{ number_format($stats['completed']) }}</p>
        </div>

        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-xl p-4 border border-red-200 dark:border-red-800">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-red-700 dark:text-red-400">ปฏิเสธ</p>
                <div class="w-8 h-8 bg-red-200 dark:bg-red-800 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-red-800 dark:text-red-300">{{ number_format($stats['rejected']) }}</p>
        </div>

        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">ยกเลิก</p>
                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-300">{{ number_format($stats['cancelled']) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div x-show="showFilters" x-transition class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-200 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.team-transfer.index') }}" class="grid md:grid-cols-3 gap-4">
            <div>
                <label for="status" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select id="status" name="status" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รออนุมัติ</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>ชำระเงินแล้ว</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>เสร็จสิ้น</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                </select>
            </div>

            <div>
                <label for="search" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ค้นหา</label>
                <input type="text"
                       id="search"
                       name="search"
                       value="{{ request('search') }}"
                       class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:text-white"
                       placeholder="ชื่อผู้ใช้, อีเมล, รหัสสมาชิก">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg shadow-lg transition">
                    ค้นหา
                </button>
                <a href="{{ route('admin.team-transfer.index') }}"
                   class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- Requests Table --}}
    @if($requests->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                #
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                สมาชิก
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                แม่ทีมเดิม
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                แม่ทีมใหม่
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                สถานะ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                วันที่สร้าง
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($requests as $request)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">#{{ $request->id }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $request->user->name ?? 'N/A' }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $request->member->member_code ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $request->oldSponsor->user->name ?? 'N/A' }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $request->oldSponsor->member_code ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $request->newSponsor->user->name ?? 'N/A' }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $request->newSponsor->member_code ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                        @if($request->status === 'pending') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300
                                        @elseif($request->status === 'approved') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                        @elseif($request->status === 'rejected') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                        @elseif($request->status === 'paid') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300
                                        @elseif($request->status === 'processing') bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300
                                        @elseif($request->status === 'completed') bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300
                                        @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                                        @endif">
                                        {{ $request->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $request->created_at->locale('th')->isoFormat('D MMM YY') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('admin.team-transfer.show', $request) }}"
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mr-3">
                                        ดูรายละเอียด
                                    </a>
                                    @if($request->canBeProcessed())
                                        <a href="{{ route('admin.team-transfer.edit', $request) }}"
                                           class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300">
                                            ดำเนินการ
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
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
                    ไม่พบคำขอย้ายทีม
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    @if(request('search') || request('status'))
                        ไม่พบข้อมูลที่ตรงกับเงื่อนไขที่คุณค้นหา
                    @else
                        ยังไม่มีคำขอย้ายทีมในระบบ
                    @endif
                </p>
            </div>
        </div>
    @endif
</div>
@endsection
