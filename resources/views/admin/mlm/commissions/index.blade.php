@extends('layouts.admin-v3')

@section('title', 'จัดการคอมมิชชั่น MLM')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header with Gradient & Animations --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600 dark:from-emerald-800 dark:via-green-800 dark:to-teal-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse delay-500"></div>
            <div class="absolute top-1/3 right-1/3 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse delay-1000"></div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                {{-- Title Section --}}
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="glass-fusion p-4 rounded-2xl">
                            <i class="fas fa-hand-holding-usd text-4xl text-white drop-shadow-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white drop-shadow-lg">จัดการคอมมิชชั่น MLM</h1>
                            <p class="text-emerald-100 text-lg mt-1">ดูและจัดการคอมมิชชั่น MLM ทั้งหมดในระบบ</p>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap gap-3">
                    <form action="{{ route('admin.mlm.commissions.approve-all') }}" method="POST" class="inline-block">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('อนุมัติคอมมิชชั่นที่รออนุมัติทั้งหมด?')"
                                class="glass-fusion hover:bg-white/30 text-white px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300 shadow-lg border border-white/30 group">
                            <i class="fas fa-check group-hover:scale-110 transition-transform"></i>
                            อนุมัติทั้งหมด
                        </button>
                    </form>
                    <form action="{{ route('admin.mlm.commissions.pay-all') }}" method="POST" class="inline-block">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('จ่ายคอมมิชชั่นที่อนุมัติแล้วทั้งหมด?')"
                                class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300 shadow-lg border border-white/30 group">
                            <i class="fas fa-dollar-sign group-hover:scale-110 transition-transform"></i>
                            จ่ายทั้งหมด
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-chart-pie text-green-600 dark:text-green-400"></i>
            สถิติภาพรวม
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Pending Commissions --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-yellow-400 to-orange-500 dark:from-yellow-500 dark:to-orange-700 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-clock text-9xl"></i>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">รออนุมัติ</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-hourglass-half text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">{{ number_format($stats['pending_count']) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-baht-sign text-yellow-100"></i>
                        <span class="opacity-90 font-semibold">{{ number_format($stats['pending_amount'], 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Approved Commissions --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-green-400 to-emerald-600 dark:from-green-500 dark:to-emerald-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-check-circle text-9xl"></i>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">อนุมัติแล้ว</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-check-double text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">{{ number_format($stats['approved_count']) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-baht-sign text-green-100"></i>
                        <span class="opacity-90 font-semibold">{{ number_format($stats['approved_amount'], 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Paid Commissions --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 dark:from-blue-600 dark:to-indigo-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-coins text-9xl"></i>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">จ่ายแล้ว</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-money-bill-wave text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">{{ number_format($stats['paid_count']) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-baht-sign text-blue-100"></i>
                        <span class="opacity-90 font-semibold">{{ number_format($stats['paid_amount'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-purple-600 dark:text-purple-400"></i>
            ตัวกรองข้อมูล
        </h3>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            {{-- Plan Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-layer-group mr-1 text-purple-600 dark:text-purple-400"></i>
                    แผน MLM
                </label>
                <select name="plan_id"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all">
                    <option value="">ทั้งหมด</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-toggle-on mr-1 text-green-600 dark:text-green-400"></i>
                    สถานะ
                </label>
                <select name="status"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รออนุมัติ</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                </select>
            </div>

            {{-- Type Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-tag mr-1 text-blue-600 dark:text-blue-400"></i>
                    ประเภท
                </label>
                <select name="type"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all">
                    <option value="">ทั้งหมด</option>
                    <option value="unilevel" {{ request('type') == 'unilevel' ? 'selected' : '' }}>Unilevel</option>
                    <option value="binary" {{ request('type') == 'binary' ? 'selected' : '' }}>Binary</option>
                    <option value="direct_sponsor" {{ request('type') == 'direct_sponsor' ? 'selected' : '' }}>Direct Sponsor</option>
                    <option value="matching" {{ request('type') == 'matching' ? 'selected' : '' }}>Matching</option>
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-calendar-alt mr-1 text-indigo-600 dark:text-indigo-400"></i>
                    วันที่เริ่มต้น
                </label>
                <input type="date"
                       name="date_from"
                       value="{{ request('date_from') }}"
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all">
            </div>

            {{-- Date To --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-calendar-check mr-1 text-pink-600 dark:text-pink-400"></i>
                    วันที่สิ้นสุด
                </label>
                <input type="date"
                       name="date_to"
                       value="{{ request('date_to') }}"
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all">
            </div>

            {{-- Search Button --}}
            <div class="flex items-end">
                <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 dark:from-blue-500 dark:to-indigo-500 dark:hover:from-blue-600 dark:hover:to-indigo-600 text-white px-4 py-3 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl font-medium flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i>
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- Commissions Table --}}
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        {{-- Bulk Actions Header --}}
        <div class="bg-gray-50 dark:bg-gray-700 p-4 border-b border-gray-200 dark:border-gray-600">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <input type="checkbox"
                           id="select-all"
                           class="w-5 h-5 rounded border-gray-300 dark:border-gray-500 text-purple-600 focus:ring-purple-500 dark:focus:ring-purple-400 cursor-pointer">
                    <label for="select-all" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                        เลือกทั้งหมด
                    </label>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button onclick="bulkAction('approve')"
                            class="bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 shadow hover:shadow-lg flex items-center gap-2">
                        <i class="fas fa-check"></i>
                        อนุมัติที่เลือก
                    </button>
                    <button onclick="bulkAction('pay')"
                            class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 shadow hover:shadow-lg flex items-center gap-2">
                        <i class="fas fa-dollar-sign"></i>
                        จ่ายที่เลือก
                    </button>
                    <button onclick="bulkAction('reject')"
                            class="bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 shadow hover:shadow-lg flex items-center gap-2">
                        <i class="fas fa-times"></i>
                        ปฏิเสธที่เลือก
                    </button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                {{-- Table Header --}}
                <thead class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <input type="checkbox" class="w-4 h-4 rounded border-white/50 text-white focus:ring-white/50 cursor-pointer">
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-user mr-2"></i>สมาชิก
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-tag mr-2"></i>ประเภท
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-user-circle mr-2"></i>จาก
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-dollar-sign mr-2"></i>จำนวน
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-info-circle mr-2"></i>สถานะ
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-calendar mr-2"></i>วันที่
                        </th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-white uppercase tracking-wider">
                            <i class="fas fa-cog mr-2"></i>จัดการ
                        </th>
                    </tr>
                </thead>

                {{-- Table Body --}}
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($commissions as $commission)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                        {{-- Checkbox --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox"
                                   name="commission_ids[]"
                                   value="{{ $commission->id }}"
                                   class="commission-checkbox w-5 h-5 rounded border-gray-300 dark:border-gray-500 text-purple-600 focus:ring-purple-500 dark:focus:ring-purple-400 cursor-pointer">
                        </td>

                        {{-- Member --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold shadow">
                                    {{ strtoupper(substr($commission->member->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $commission->member->user->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        {{ $commission->member->member_code }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Type --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                @if($commission->type === 'unilevel')
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 shadow">
                                        <i class="fas fa-layer-group mr-1"></i>
                                        Unilevel
                                    </span>
                                @elseif($commission->type === 'binary')
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 shadow">
                                        <i class="fas fa-code-branch mr-1"></i>
                                        Binary
                                    </span>
                                @elseif($commission->type === 'direct_sponsor')
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 shadow">
                                        <i class="fas fa-user-plus mr-1"></i>
                                        Direct
                                    </span>
                                @elseif($commission->type === 'matching')
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-300 shadow">
                                        <i class="fas fa-handshake mr-1"></i>
                                        Matching
                                    </span>
                                @endif
                                @if($commission->level)
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                        Lv.{{ $commission->level }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- From Member --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($commission->fromMember)
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $commission->fromMember->user->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        {{ $commission->fromMember->member_code }}
                                    </div>
                                </div>
                            @else
                                <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>

                        {{-- Amount --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                <i class="fas fa-baht-sign text-xs"></i>
                                {{ number_format($commission->commission_amount, 2) }}
                            </div>
                            @if($commission->pv_amount)
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                    <i class="fas fa-gem text-xs"></i>
                                    {{ number_format($commission->pv_amount, 2) }} PV
                                </div>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($commission->status === 'pending')
                                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 shadow">
                                    <i class="fas fa-clock mr-1"></i>
                                    Pending
                                </span>
                            @elseif($commission->status === 'approved')
                                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 shadow">
                                    <i class="fas fa-check mr-1"></i>
                                    Approved
                                </span>
                            @elseif($commission->status === 'paid')
                                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 shadow">
                                    <i class="fas fa-check-double mr-1"></i>
                                    Paid
                                </span>
                            @elseif($commission->status === 'rejected')
                                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 shadow">
                                    <i class="fas fa-times mr-1"></i>
                                    Rejected
                                </span>
                            @endif
                        </td>

                        {{-- Date --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $commission->created_at->format('d/m/Y') }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-500">
                                {{ $commission->created_at->format('H:i') }}
                            </div>
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.mlm.commissions.show', $commission) }}"
                                   class="px-3 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-xl transition-all duration-300 shadow hover:shadow-lg transform hover:scale-105"
                                   title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($commission->status === 'pending')
                                    <button onclick="approveCommission({{ $commission->id }})"
                                            class="px-3 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-xl transition-all duration-300 shadow hover:shadow-lg transform hover:scale-105"
                                            title="อนุมัติ">
                                        <i class="fas fa-check"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center gap-3">
                                <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-full">
                                    <i class="fas fa-inbox text-5xl text-gray-400 dark:text-gray-500"></i>
                                </div>
                                <div>
                                    <p class="text-lg font-semibold text-gray-600 dark:text-gray-400">ไม่พบข้อมูลคอมมิชชั่น</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">ลองเปลี่ยนตัวกรองเพื่อค้นหาข้อมูล</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($commissions->hasPages())
        <div class="flex justify-center">
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-4">
                {{ $commissions->links() }}
            </div>
        </div>
    @endif
</div>

{{-- JavaScript for Bulk Actions --}}
<script>
// Select All Checkbox
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.commission-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = this.checked);
});

// Bulk Actions
function bulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.commission-checkbox:checked');
    const ids = Array.from(checkedBoxes).map(cb => cb.value);

    if (ids.length === 0) {
        alert('กรุณาเลือกคอมมิชชั่นที่ต้องการดำเนินการ');
        return;
    }

    let confirmMessage = '';
    let url = '';

    switch(action) {
        case 'approve':
            confirmMessage = `อนุมัติคอมมิชชั่น ${ids.length} รายการ?`;
            url = '{{ route("admin.mlm.commissions.approve") }}';
            break;
        case 'pay':
            confirmMessage = `จ่ายคอมมิชชั่น ${ids.length} รายการ?`;
            url = '{{ route("admin.mlm.commissions.pay") }}';
            break;
        case 'reject':
            const reason = prompt('กรุณาระบุเหตุผล:');
            if (!reason) return;
            confirmMessage = `ปฏิเสธคอมมิชชั่น ${ids.length} รายการ?`;
            url = '{{ route("admin.mlm.commissions.bulk-action") }}';
            break;
    }

    if (!confirm(confirmMessage)) return;

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('action', action);
    ids.forEach(id => formData.append('commission_ids[]', id));
    if (action === 'reject') {
        formData.append('reason', reason);
    }

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        alert('เกิดข้อผิดพลาด: ' + error.message);
    });
}

// Single Commission Approve
function approveCommission(id) {
    if (!confirm('อนุมัติคอมมิชชั่นนี้?')) return;

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('commission_ids[]', id);

    fetch('{{ route("admin.mlm.commissions.approve") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('อนุมัติคอมมิชชั่นสำเร็จ');
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด');
        }
    });
}
</script>
@endsection
