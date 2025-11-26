@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าระบบ Wallet')

@section('content')
<div x-data="{
    activeTab: 'wallets',
    showCalculator: false,
    calcAmount: 1000,
    calcFee: 0,
    calcTax: 0,
    calcNet: 0,
    calculating: false
}" class="space-y-6">

    {{-- ======================================== --}}
    {{-- HERO HEADER - MODERN GRADIENT --}}
    {{-- ======================================== --}}
    <div class="relative overflow-hidden rounded-3xl">
        {{-- พื้นหลัง Gradient + Pattern --}}
        <div class="absolute inset-0 bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700"></div>
        <div class="absolute inset-0 opacity-10"
             style="background-image: url('data:image/svg+xml,%3Csvg width=60 height=60 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23fff%22 fill-opacity=%221%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

        {{-- Floating elements --}}
        <div class="absolute top-10 right-10 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 left-20 w-24 h-24 bg-pink-400/20 rounded-full blur-2xl"></div>

        {{-- เนื้อหา Header --}}
        <div class="relative px-8 py-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                {{-- ซ้าย: Title --}}
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl border border-white/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl lg:text-4xl font-bold text-white mb-1">ระบบจัดการ Wallet</h1>
                        <p class="text-purple-200 text-sm lg:text-base">จัดการกระเป๋าเงินผู้ใช้ ตั้งค่าระบบ และดูสถิติรวม</p>
                    </div>
                </div>

                {{-- ขวา: Quick Stats --}}
                <div class="flex flex-wrap gap-3">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/20">
                        <div class="text-xs text-purple-200 mb-1">กระเป๋าทั้งหมด</div>
                        <div class="text-2xl font-bold text-white">{{ number_format($walletStats['total_wallets']) }}</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/20">
                        <div class="text-xs text-purple-200 mb-1">ยอดเงินรวม</div>
                        <div class="text-2xl font-bold text-white">{{ number_format($walletStats['total_balance'], 2) }}</div>
                    </div>
                    <div class="bg-emerald-500/30 backdrop-blur-sm rounded-xl px-4 py-3 border border-emerald-400/30">
                        <div class="text-xs text-emerald-200 mb-1">Active</div>
                        <div class="text-2xl font-bold text-white">{{ number_format($walletStats['active_wallets']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================== --}}
    {{-- TAB NAVIGATION --}}
    {{-- ======================================== --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-2">
        <div class="flex flex-wrap gap-2">
            <button @click="activeTab = 'wallets'"
                    :class="activeTab === 'wallets'
                        ? 'bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-lg'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                    class="flex items-center gap-2 px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>กระเป๋าเงินผู้ใช้</span>
                <span class="bg-white/20 text-xs px-2 py-0.5 rounded-full">{{ number_format($walletStats['total_wallets']) }}</span>
            </button>

            <button @click="activeTab = 'settings'"
                    :class="activeTab === 'settings'
                        ? 'bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-lg'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                    class="flex items-center gap-2 px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>ตั้งค่าระบบ</span>
            </button>

            <button @click="activeTab = 'tools'"
                    :class="activeTab === 'tools'
                        ? 'bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-lg'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                    class="flex items-center gap-2 px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <span>เครื่องมือ</span>
            </button>
        </div>
    </div>

    {{-- ======================================== --}}
    {{-- TAB 1: กระเป๋าเงินผู้ใช้ทุกคน --}}
    {{-- ======================================== --}}
    <div x-show="activeTab === 'wallets'" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="space-y-6">

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            {{-- Total Balance --}}
            <div class="group bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-full">THB</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ number_format($walletStats['total_balance'], 2) }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">ยอดเงินรวมทั้งระบบ</div>
            </div>

            {{-- Total Income --}}
            <div class="group bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2 py-1 rounded-full">รายรับ</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ number_format($walletStats['total_income'], 2) }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">รายรับรวมทั้งหมด</div>
            </div>

            {{-- Total Expense --}}
            <div class="group bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-rose-400 to-rose-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/30 px-2 py-1 rounded-full">รายจ่าย</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ number_format($walletStats['total_expense'], 2) }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">รายจ่ายรวมทั้งหมด</div>
            </div>

            {{-- Locked Wallets --}}
            <div class="group bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded-full">ถูกล็อก</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ number_format($walletStats['locked_wallets']) }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">กระเป๋าถูกล็อก</div>
            </div>
        </div>

        {{-- ตารางกระเป๋าเงินผู้ใช้ --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            {{-- Header + Filter --}}
            <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-6 h-6 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            กระเป๋าเงินผู้ใช้ทั้งหมด
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">จัดการและตรวจสอบกระเป๋าเงินของสมาชิกทุกคน</p>
                    </div>

                    {{-- Search & Filter Form --}}
                    <form method="GET" action="{{ route('admin.wallet-settings.index') }}" class="flex flex-wrap items-center gap-3">
                        {{-- Search Input --}}
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="ค้นหาชื่อ, อีเมล, เบอร์..."
                                   class="w-64 pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        {{-- Status Filter --}}
                        <select name="status" class="px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
                            <option value="">ทุกสถานะ</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="locked" {{ request('status') == 'locked' ? 'selected' : '' }}>Locked</option>
                            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>

                        {{-- Sort --}}
                        <select name="sort_by" class="px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
                            <option value="balance" {{ request('sort_by') == 'balance' ? 'selected' : '' }}>ยอดเงิน</option>
                            <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>วันที่สร้าง</option>
                            <option value="last_transaction_at" {{ request('sort_by') == 'last_transaction_at' ? 'selected' : '' }}>ทำรายการล่าสุด</option>
                        </select>

                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-violet-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-lg transform hover:scale-105 transition-all duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                        </button>

                        @if(request()->hasAny(['search', 'status', 'sort_by']))
                            <a href="{{ route('admin.wallet-settings.index') }}" class="px-4 py-2.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                ล้าง
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ผู้ใช้</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Wallet Address</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ยอดเงิน</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">รายรับ</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">รายจ่าย</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ทำรายการล่าสุด</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($wallets as $wallet)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                {{-- ผู้ใช้ --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        {{-- ใช้ component เพื่อความสอดคล้องทั้งระบบ --}}
                                        @if($wallet->user)
                                            <x-user-avatar :user="$wallet->user" size="md" :ring="true" ringColor="purple" />
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-violet-400 to-purple-500 flex items-center justify-center text-white font-semibold">
                                                ?
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-white">
                                                {{ $wallet->user?->name ?? 'ไม่ระบุ' }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $wallet->user?->email ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Wallet Address --}}
                                <td class="px-6 py-4">
                                    <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-gray-600 dark:text-gray-300">
                                        {{ $wallet->wallet_address }}
                                    </code>
                                </td>

                                {{-- ยอดเงิน --}}
                                <td class="px-6 py-4 text-right">
                                    <span class="text-lg font-bold {{ $wallet->balance > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ number_format($wallet->balance, 2) }}
                                    </span>
                                    <span class="text-xs text-gray-400 ml-1">{{ $wallet->currency }}</span>
                                </td>

                                {{-- รายรับ --}}
                                <td class="px-6 py-4 text-right">
                                    <span class="text-sm text-blue-600 dark:text-blue-400 font-medium">
                                        +{{ number_format($wallet->total_income, 2) }}
                                    </span>
                                </td>

                                {{-- รายจ่าย --}}
                                <td class="px-6 py-4 text-right">
                                    <span class="text-sm text-rose-600 dark:text-rose-400 font-medium">
                                        -{{ number_format($wallet->total_expense, 2) }}
                                    </span>
                                </td>

                                {{-- สถานะ --}}
                                <td class="px-6 py-4 text-center">
                                    @if($wallet->status === 'active')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-semibold rounded-full">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                            Active
                                        </span>
                                    @elseif($wallet->status === 'locked')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-semibold rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                            Locked
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-semibold rounded-full">
                                            {{ ucfirst($wallet->status) }}
                                        </span>
                                    @endif
                                </td>

                                {{-- ทำรายการล่าสุด --}}
                                <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    @if($wallet->last_transaction_at)
                                        {{ $wallet->last_transaction_at->diffForHumans() }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- จัดการ --}}
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        @if($wallet->user)
                                            <a href="{{ route('admin.users.show', $wallet->user_id) }}"
                                               class="p-2 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-900/20 rounded-lg transition-all"
                                               title="ดูโปรไฟล์ผู้ใช้">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.wallet.all-transactions', ['wallet_id' => $wallet->id]) }}"
                                           class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all"
                                           title="ดูรายการธุรกรรม">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                            </svg>
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400 font-medium">ไม่พบข้อมูลกระเป๋าเงิน</p>
                                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">ลองปรับเงื่อนไขการค้นหา</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($wallets->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $wallets->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ======================================== --}}
    {{-- TAB 2: ตั้งค่าระบบ --}}
    {{-- ======================================== --}}
    <div x-show="activeTab === 'settings'" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="space-y-6">

        {{-- Settings Grid --}}
        @php
            $groupIcons = [
                'limits' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
                'fees' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                'tax' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />',
                'payment_methods' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />',
                'general' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
            ];
            $groupGradients = [
                'limits' => 'from-blue-500 to-cyan-500',
                'fees' => 'from-emerald-500 to-teal-500',
                'tax' => 'from-amber-500 to-orange-500',
                'payment_methods' => 'from-violet-500 to-purple-500',
                'general' => 'from-slate-500 to-gray-600',
            ];
        @endphp

        @foreach($groups as $groupKey => $groupName)
            @if(isset($settings[$groupKey]) && $settings[$groupKey]->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    {{-- Group Header --}}
                    <div class="flex items-center gap-4 px-6 py-5 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 border-b border-gray-100 dark:border-gray-700">
                        <div class="w-12 h-12 bg-gradient-to-br {{ $groupGradients[$groupKey] ?? 'from-gray-500 to-gray-600' }} rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                {!! $groupIcons[$groupKey] ?? '' !!}
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $groupName }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $settings[$groupKey]->count() }} รายการตั้งค่า</p>
                        </div>
                    </div>

                    {{-- Settings List --}}
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($settings[$groupKey] as $setting)
                            <form action="{{ route('admin.wallet-settings.update', $setting->key) }}" method="POST"
                                  class="px-6 py-5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                @csrf
                                @method('PUT')

                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                    {{-- Label & Description --}}
                                    <div class="flex-1">
                                        <label class="block text-base font-semibold text-gray-900 dark:text-white mb-1">
                                            {{ $setting->label }}
                                        </label>
                                        @if($setting->description)
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $setting->description }}</p>
                                        @endif
                                    </div>

                                    {{-- Input Field --}}
                                    <div class="flex items-center gap-3">
                                        @if($setting->type === 'boolean')
                                            {{-- Toggle Switch --}}
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="value" value="1"
                                                       {{ $setting->value == '1' ? 'checked' : '' }}
                                                       onchange="this.form.submit()"
                                                       class="sr-only peer">
                                                <div class="w-14 h-7 bg-gray-200 dark:bg-gray-600 rounded-full peer
                                                            peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800
                                                            peer-checked:bg-gradient-to-r peer-checked:from-violet-500 peer-checked:to-purple-600
                                                            transition-all duration-300
                                                            after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                                            after:bg-white after:border-gray-300 after:border after:rounded-full
                                                            after:h-6 after:w-6 after:shadow-md
                                                            after:transition-all peer-checked:after:translate-x-7"></div>
                                            </label>
                                            <span class="text-sm font-medium {{ $setting->value == '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                                                {{ $setting->value == '1' ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                            </span>

                                        @elseif($setting->type === 'percentage')
                                            {{-- Percentage Input --}}
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="value" value="{{ $setting->value }}"
                                                       step="0.01" min="0" max="100"
                                                       class="w-28 px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl
                                                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                              focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all text-right font-semibold"
                                                       required>
                                                <span class="text-lg font-bold text-gray-400">%</span>
                                                <button type="submit" class="px-4 py-2.5 bg-gradient-to-r from-violet-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                                                    บันทึก
                                                </button>
                                            </div>

                                        @elseif($setting->type === 'number')
                                            {{-- Number Input --}}
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="value" value="{{ $setting->value }}"
                                                       step="0.01" min="0"
                                                       class="w-40 px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl
                                                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                              focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all text-right font-semibold"
                                                       required>
                                                <button type="submit" class="px-4 py-2.5 bg-gradient-to-r from-violet-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                                                    บันทึก
                                                </button>
                                            </div>

                                        @else
                                            {{-- Text Input --}}
                                            <div class="flex items-center gap-2">
                                                <input type="text" name="value" value="{{ $setting->value }}"
                                                       class="w-64 px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl
                                                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                              focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                                                       required>
                                                <button type="submit" class="px-4 py-2.5 bg-gradient-to-r from-violet-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                                                    บันทึก
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if($setting->validation_rules)
                                    <div class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                                        <span class="font-medium">กฎ:</span> {{ $setting->validation_rules }}
                                    </div>
                                @endif
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- ======================================== --}}
    {{-- TAB 3: เครื่องมือ --}}
    {{-- ======================================== --}}
    <div x-show="activeTab === 'tools'" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="space-y-6">

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- เครื่องคำนวณค่าธรรมเนียม --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-br from-blue-500 to-cyan-500 p-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-white">คำนวณค่าธรรมเนียม</h4>
                            <p class="text-blue-100 text-sm">คำนวณค่าธรรมเนียมและภาษี</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4" x-data="feeCalculator()">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทธุรกรรม</label>
                        <select x-model="type" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <option value="withdrawal">ถอนเงิน</option>
                            <option value="transfer">โอนเงิน</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน (THB)</label>
                        <input type="number" x-model="amount" min="0" step="0.01"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-right font-semibold text-lg">
                    </div>

                    <button @click="calculate()"
                            :disabled="calculating"
                            class="w-full py-3 bg-gradient-to-r from-blue-500 to-cyan-500 text-white font-bold rounded-xl hover:shadow-lg transform hover:scale-[1.02] transition-all disabled:opacity-50">
                        <span x-show="!calculating">คำนวณ</span>
                        <span x-show="calculating" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            กำลังคำนวณ...
                        </span>
                    </button>

                    <div x-show="result" x-transition class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">จำนวนเงิน:</span>
                            <span class="font-semibold text-gray-900 dark:text-white" x-text="result?.formatted?.amount || '-'"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">ค่าธรรมเนียม:</span>
                            <span class="font-semibold text-rose-600 dark:text-rose-400" x-text="result?.formatted?.fee ? '-' + result.formatted.fee : '-'"></span>
                        </div>
                        <div x-show="result?.tax" class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">ภาษี:</span>
                            <span class="font-semibold text-amber-600 dark:text-amber-400" x-text="result?.formatted?.tax ? '-' + result.formatted.tax : '-'"></span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-600 pt-2 flex justify-between">
                            <span class="text-gray-900 dark:text-white font-semibold">ยอดสุทธิ:</span>
                            <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400" x-text="result?.formatted?.net_amount || result?.formatted?.total_amount || '-'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- รีเซ็ตค่าเริ่มต้น --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-br from-rose-500 to-pink-500 p-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-white">รีเซ็ตค่าเริ่มต้น</h4>
                            <p class="text-rose-100 text-sm">คืนค่าการตั้งค่าทั้งหมด</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                        การดำเนินการนี้จะคืนค่าการตั้งค่าทั้งหมดกลับเป็นค่าเริ่มต้นของระบบ
                        <strong class="text-rose-600 dark:text-rose-400">ไม่สามารถยกเลิกได้</strong>
                    </p>

                    <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-4 mb-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div class="text-sm text-rose-700 dark:text-rose-300">
                                ต้องเป็น Super Admin เท่านั้นที่สามารถรีเซ็ตได้
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('admin.wallet-settings.reset-defaults') }}" method="POST"
                          onsubmit="return confirm('คุณแน่ใจหรือว่าต้องการรีเซ็ตการตั้งค่าทั้งหมด?\n\nการดำเนินการนี้ไม่สามารถยกเลิกได้!')">
                        @csrf
                        <button type="submit" class="w-full py-3 bg-gradient-to-r from-rose-500 to-pink-500 text-white font-bold rounded-xl hover:shadow-lg transition-all">
                            รีเซ็ตทั้งหมด
                        </button>
                    </form>
                </div>
            </div>

            {{-- คู่มือการใช้งาน --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-br from-violet-500 to-purple-600 p-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-white">คู่มือการใช้งาน</h4>
                            <p class="text-violet-100 text-sm">คำอธิบายการตั้งค่า</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="text-blue-600 dark:text-blue-400 font-bold text-sm">1</span>
                            </div>
                            <div>
                                <h5 class="font-semibold text-gray-900 dark:text-white">ข้อจำกัดและขีดจำกัด</h5>
                                <p class="text-sm text-gray-500 dark:text-gray-400">กำหนดจำนวนเงินขั้นต่ำและสูงสุดในการถอน</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold text-sm">2</span>
                            </div>
                            <div>
                                <h5 class="font-semibold text-gray-900 dark:text-white">ค่าธรรมเนียม</h5>
                                <p class="text-sm text-gray-500 dark:text-gray-400">กำหนดค่าธรรมเนียมแบบ % หรือคงที่</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="text-amber-600 dark:text-amber-400 font-bold text-sm">3</span>
                            </div>
                            <div>
                                <h5 class="font-semibold text-gray-900 dark:text-white">ภาษี</h5>
                                <p class="text-sm text-gray-500 dark:text-gray-400">กำหนด % ภาษีที่หักจากการถอน</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-violet-100 dark:bg-violet-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="text-violet-600 dark:text-violet-400 font-bold text-sm">4</span>
                            </div>
                            <div>
                                <h5 class="font-semibold text-gray-900 dark:text-white">ช่องทางชำระเงิน</h5>
                                <p class="text-sm text-gray-500 dark:text-gray-400">เปิด/ปิดช่องทางแต่ละประเภท</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    /**
     * Alpine.js component สำหรับคำนวณค่าธรรมเนียม
     */
    function feeCalculator() {
        return {
            type: 'withdrawal',
            amount: 1000,
            result: null,
            calculating: false,

            async calculate() {
                this.calculating = true;
                this.result = null;

                try {
                    const response = await fetch('{{ route('admin.wallet-settings.calculate-fee') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            type: this.type,
                            amount: parseFloat(this.amount) || 0,
                        }),
                    });

                    if (response.ok) {
                        this.result = await response.json();
                    } else {
                        console.error('เกิดข้อผิดพลาดในการคำนวณ');
                    }
                } catch (error) {
                    console.error('Error:', error);
                } finally {
                    this.calculating = false;
                }
            }
        };
    }
</script>
@endpush
@endsection
