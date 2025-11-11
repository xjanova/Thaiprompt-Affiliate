@extends('layouts.admin')

@section('title', 'รายละเอียด Affiliate - ' . $affiliate->user->name)

@section('content')
<div class="space-y-6">
    <!-- Enhanced Header with Breadcrumb and Actions -->
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
        <div class="space-y-2">
            <a href="{{ route('admin.affiliates.index') }}" class="group inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-all duration-200 font-medium">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                กลับไปรายการ Affiliates
            </a>
            <h1 class="text-4xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                รายละเอียด Affiliate
            </h1>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.affiliates.tree', $affiliate) }}"
               class="group flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:from-green-700 hover:to-emerald-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105 font-bold">
                <svg class="w-5 h-5 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
                ดู Tree
            </a>
            <a href="{{ route('admin.affiliates.edit', $affiliate) }}"
               class="group flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl hover:from-blue-700 hover:to-cyan-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105 font-bold">
                <svg class="w-5 h-5 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                แก้ไข
            </a>
        </div>
    </div>

    <!-- Enhanced Profile Header Card with Glassmorphism -->
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 rounded-2xl shadow-2xl p-8 text-white">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-80 h-80 bg-pink-300 rounded-full blur-3xl animate-pulse delay-1000"></div>
            <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-purple-300 rounded-full blur-3xl animate-pulse delay-500"></div>
        </div>

        <div class="relative z-10 flex flex-col lg:flex-row items-center lg:items-start gap-6">
            <!-- Enhanced Avatar with Ring Animation -->
            <div class="relative group">
                <div class="absolute inset-0 bg-gradient-to-r from-pink-500 to-yellow-500 rounded-full blur-xl opacity-75 group-hover:opacity-100 transition-opacity duration-300 animate-pulse"></div>
                <div class="relative w-32 h-32 rounded-full bg-white/20 backdrop-blur-xl flex items-center justify-center text-5xl font-black border-4 border-white/40 shadow-2xl group-hover:scale-110 transition-transform duration-300">
                    {{ strtoupper(substr($affiliate->user->name, 0, 2)) }}
                </div>
                <!-- Status Indicator Dot -->
                @if($affiliate->status === 'active')
                    <div class="absolute bottom-2 right-2 w-6 h-6 bg-green-400 rounded-full border-4 border-white shadow-lg animate-pulse"></div>
                @endif
            </div>

            <!-- User Info Section -->
            <div class="flex-1 text-center lg:text-left space-y-4">
                <div>
                    <h2 class="text-4xl font-black mb-2 tracking-tight">{{ $affiliate->user->name }}</h2>
                    <p class="text-xl text-indigo-100 font-medium flex items-center gap-2 justify-center lg:justify-start">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        {{ $affiliate->user->email }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3 justify-center lg:justify-start">
                    <span class="px-4 py-2 bg-white/20 backdrop-blur-xl rounded-xl text-base font-bold border border-white/30 shadow-lg flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Level {{ $affiliate->level }}
                    </span>
                    <span class="px-4 py-2 rounded-xl text-base font-bold border-2 shadow-lg flex items-center gap-2
                        @if($affiliate->status === 'active') bg-green-400/30 border-green-300 backdrop-blur-xl
                        @elseif($affiliate->status === 'inactive') bg-gray-400/30 border-gray-300 backdrop-blur-xl
                        @else bg-red-400/30 border-red-300 backdrop-blur-xl
                        @endif">
                        @if($affiliate->status === 'active')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                        {{ ucfirst($affiliate->status) }}
                    </span>
                    <span class="px-4 py-2 bg-white/20 backdrop-blur-xl rounded-xl text-base font-bold border border-white/30 shadow-lg uppercase">
                        {{ $affiliate->user->role ?? 'user' }}
                    </span>
                </div>
            </div>

            <!-- Enhanced Referral Code Card -->
            <div class="bg-white/15 backdrop-blur-xl rounded-2xl p-6 border-2 border-white/30 shadow-2xl hover:scale-105 transition-transform duration-300">
                <div class="flex items-center gap-2 text-indigo-100 mb-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span class="text-sm font-bold uppercase tracking-wide">รหัสแนะนำ</span>
                </div>
                <div class="flex items-center gap-3">
                    <code class="text-3xl font-mono font-black bg-white/10 px-4 py-2 rounded-xl" id="referral-code">{{ $affiliate->referral_code }}</code>
                    <button onclick="copyToClipboard('{{ $affiliate->referral_code }}')"
                            class="group px-4 py-3 bg-white/20 hover:bg-white/30 rounded-xl transition-all duration-200 text-sm font-bold border border-white/30 hover:scale-110 shadow-lg">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Animated Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Referrals Card -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl shadow-lg border-2 border-blue-200 dark:border-blue-800 p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition-all duration-300"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Network</span>
                </div>
                <p class="text-4xl font-black text-blue-600 dark:text-blue-400 mb-2">{{ number_format($affiliate->total_referrals) }}</p>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Total Referrals<br>เครือข่ายทั้งหมด</p>
            </div>
        </div>

        <!-- Direct Referrals Card -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl shadow-lg border-2 border-purple-200 dark:border-purple-800 p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
            <div class="absolute top-0 right-0 w-32 h-32 bg-purple-500/10 rounded-full blur-3xl group-hover:bg-purple-500/20 transition-all duration-300"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">Direct</span>
                </div>
                <p class="text-4xl font-black text-purple-600 dark:text-purple-400 mb-2">{{ $affiliate->children->count() }}</p>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Direct Referrals<br>ผู้ใช้ที่แนะนำโดยตรง</p>
            </div>
        </div>

        <!-- Total Earnings Card -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl shadow-lg border-2 border-green-200 dark:border-green-800 p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
            <div class="absolute top-0 right-0 w-32 h-32 bg-green-500/10 rounded-full blur-3xl group-hover:bg-green-500/20 transition-all duration-300"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-green-600 dark:text-green-400 uppercase tracking-wider">Earnings</span>
                </div>
                <p class="text-4xl font-black text-green-600 dark:text-green-400 mb-2">฿{{ number_format($affiliate->total_earnings, 0) }}</p>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Total Earnings<br>รายได้ทั้งหมด</p>
            </div>
        </div>

        <!-- Commissions Count Card -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-2xl shadow-lg border-2 border-orange-200 dark:border-orange-800 p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
            <div class="absolute top-0 right-0 w-32 h-32 bg-orange-500/10 rounded-full blur-3xl group-hover:bg-orange-500/20 transition-all duration-300"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-orange-500 to-amber-500 rounded-xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-orange-600 dark:text-orange-400 uppercase tracking-wider">Transactions</span>
                </div>
                <p class="text-4xl font-black text-orange-600 dark:text-orange-400 mb-2">{{ $affiliate->commissions->count() }}</p>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Commissions<br>จำนวนคอมมิชชั่น</p>
            </div>
        </div>
    </div>

    <!-- Additional Stats -->
    @php
        $paidCommissions = $affiliate->commissions->where('status', 'paid');
        $pendingCommissions = $affiliate->commissions->where('status', 'pending');
        $totalPaid = $paidCommissions->sum('amount');
        $totalPending = $pendingCommissions->sum('amount');
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Paid Commissions -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">คอมมิชชั่นที่จ่ายแล้ว</h3>
                <div class="text-2xl">✅</div>
            </div>
            <p class="text-2xl font-bold text-green-600">฿{{ number_format($totalPaid, 2) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $paidCommissions->count() }} รายการ</p>
        </div>

        <!-- Pending Commissions -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">คอมมิชชั่นรอจ่าย</h3>
                <div class="text-2xl">⏳</div>
            </div>
            <p class="text-2xl font-bold text-yellow-600">฿{{ number_format($totalPending, 2) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $pendingCommissions->count() }} รายการ</p>
        </div>

        <!-- Network Performance -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">ประสิทธิภาพเครือข่าย</h3>
                <div class="text-2xl">📈</div>
            </div>
            @php
                $avgPerReferral = $affiliate->total_referrals > 0 ? $affiliate->total_earnings / $affiliate->total_referrals : 0;
            @endphp
            <p class="text-2xl font-bold text-indigo-600">฿{{ number_format($avgPerReferral, 2) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">เฉลี่ยต่อ referral</p>
        </div>
    </div>

    <!-- Information Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Affiliate Information -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">📋</span>
                ข้อมูล Affiliate
            </h3>
            <dl class="space-y-4">
                @if($affiliate->parent)
                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">แนะนำโดย (Upline)</dt>
                    <dd class="text-sm">
                        <a href="{{ route('admin.affiliates.show', $affiliate->parent) }}"
                           class="flex items-center gap-2 text-indigo-600 hover:text-indigo-900 font-medium">
                            <span>{{ $affiliate->parent->user->name }}</span>
                            <code class="px-2 py-1 bg-indigo-50 rounded text-xs">{{ $affiliate->parent->referral_code }}</code>
                            <span class="text-xs">→</span>
                        </a>
                    </dd>
                </div>
                @else
                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">แนะนำโดย (Upline)</dt>
                    <dd class="text-sm text-gray-500 italic">ไม่มี (Root Affiliate)</dd>
                </div>
                @endif

                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">ระดับในเครือข่าย</dt>
                    <dd class="text-sm">
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-semibold">Level {{ $affiliate->level }}</span>
                    </dd>
                </div>

                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">สถานะ</dt>
                    <dd class="text-sm">
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                            @if($affiliate->status === 'active') bg-green-100 text-green-800
                            @elseif($affiliate->status === 'inactive') bg-gray-100 text-gray-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($affiliate->status) }}
                        </span>
                    </dd>
                </div>

                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">วันที่สมัคร</dt>
                    <dd class="text-sm text-gray-900 dark:text-white font-medium">
                        {{ $affiliate->created_at->format('d/m/Y H:i') }}
                        <span class="text-xs text-gray-500">({{ $affiliate->created_at->diffForHumans() }})</span>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600 mb-1">อัพเดทล่าสุด</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">
                        {{ $affiliate->updated_at->format('d/m/Y H:i') }}
                        <span class="text-xs text-gray-500">({{ $affiliate->updated_at->diffForHumans() }})</span>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- User Information -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-2xl">👤</span>
                ข้อมูลผู้ใช้
            </h3>
            <dl class="space-y-4">
                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">ชื่อผู้ใช้</dt>
                    <dd class="text-sm text-gray-900 dark:text-white font-medium">{{ $affiliate->user->name }}</dd>
                </div>

                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">อีเมล</dt>
                    <dd class="text-sm">
                        <a href="mailto:{{ $affiliate->user->email }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $affiliate->user->email }}
                        </a>
                    </dd>
                </div>

                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">บทบาท</dt>
                    <dd class="text-sm">
                        <span class="px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-800 rounded-full uppercase">
                            {{ $affiliate->user->role ?? 'user' }}
                        </span>
                    </dd>
                </div>

                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-600 mb-1">สร้างบัญชีเมื่อ</dt>
                    <dd class="text-sm text-gray-900 dark:text-white font-medium">
                        {{ $affiliate->user->created_at->format('d/m/Y H:i') }}
                        <span class="text-xs text-gray-500">({{ $affiliate->user->created_at->diffForHumans() }})</span>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600 mb-1">ดูแดชบอร์ด</dt>
                    <dd class="text-sm">
                        <a href="{{ route('admin.users.dashboard', $affiliate->user) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition font-medium">
                            <span>🔍</span>
                            <span>เข้าสู่แดชบอร์ดผู้ใช้</span>
                        </a>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Commission History -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-2xl">💵</span>
            ประวัติคอมมิชชั่น
            @if($affiliate->commissions->count() > 0)
                <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-semibold">
                    {{ $affiliate->commissions->count() }} รายการ
                </span>
            @endif
        </h3>

        @if($affiliate->commissions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ยอดขาย</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เปอร์เซ็นต์</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">คอมมิชชั่น</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($affiliate->commissions->sortByDesc('created_at')->take(15) as $commission)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="font-medium">{{ $commission->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $commission->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    ฿{{ number_format($commission->sale_amount ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded font-semibold">
                                        {{ $commission->commission_rate ?? 0 }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                                    ฿{{ number_format($commission->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full
                                        @if($commission->status === 'paid') bg-green-100 text-green-800
                                        @elseif($commission->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($commission->status === 'approved') bg-blue-100 text-blue-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($commission->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($commission->description)
                                        <span class="text-gray-600" title="{{ $commission->description }}">
                                            {{ \Illuminate\Support\Str::limit($commission->description, 30) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 italic">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 font-bold">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right text-sm text-gray-700">รวมทั้งหมด:</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-bold">
                                ฿{{ number_format($affiliate->commissions->sum('amount'), 2) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($affiliate->commissions->count() > 15)
                <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200 text-center">
                    <p class="text-sm text-blue-900">
                        แสดง 15 รายการล่าสุด จากทั้งหมด <strong>{{ $affiliate->commissions->count() }}</strong> รายการ
                    </p>
                </div>
            @endif
        @else
            <div class="text-center py-12 text-gray-500">
                <div class="text-6xl mb-4">💸</div>
                <p class="text-xl font-semibold mb-2">ยังไม่มีคอมมิชชั่น</p>
                <p class="text-sm">เมื่อมี referral ซื้อสินค้า คอมมิชชั่นจะแสดงที่นี่</p>
            </div>
        @endif
    </div>

    <!-- Direct Referrals -->
    @if($affiliate->children->count() > 0)
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-2xl">👨‍👩‍👧‍👦</span>
            ผู้ใช้ที่แนะนำโดยตรง (Direct Referrals)
            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-semibold">
                {{ $affiliate->children->count() }}
            </span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($affiliate->children->sortByDesc('created_at') as $child)
                <div class="border-2 border-gray-200 rounded-xl p-4 hover:shadow-lg hover:border-indigo-300 transition">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold shadow-md">
                                {{ strtoupper(substr($child->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900">{{ $child->user->name }}</h4>
                                <code class="text-xs text-gray-500 font-mono">{{ $child->referral_code }}</code>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full font-semibold
                            @if($child->status === 'active') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ $child->status }}
                        </span>
                    </div>

                    <!-- Email -->
                    <p class="text-sm text-gray-600 mb-3 truncate">{{ $child->user->email }}</p>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-2 mb-3 text-xs">
                        <div class="bg-blue-50 rounded p-2">
                            <div class="text-gray-600">Level</div>
                            <div class="font-bold text-blue-600">{{ $child->level }}</div>
                        </div>
                        <div class="bg-green-50 rounded p-2">
                            <div class="text-gray-600">Referrals</div>
                            <div class="font-bold text-green-600">{{ $child->total_referrals }}</div>
                        </div>
                        <div class="bg-purple-50 rounded p-2 col-span-2">
                            <div class="text-gray-600">Earnings</div>
                            <div class="font-bold text-purple-600">฿{{ number_format($child->total_earnings, 2) }}</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <a href="{{ route('admin.affiliates.show', $child) }}"
                           class="flex-1 text-center px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition font-medium">
                            ดูรายละเอียด
                        </a>
                        <a href="{{ route('admin.affiliates.tree', $child) }}"
                           class="px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
                            🌳
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-2xl">👨‍👩‍👧‍👦</span>
            ผู้ใช้ที่แนะนำโดยตรง (Direct Referrals)
        </h3>
        <div class="text-center py-12 text-gray-500">
            <div class="text-6xl mb-4">👥</div>
            <p class="text-xl font-semibold mb-2">ยังไม่มีผู้ใช้ที่แนะนำโดยตรง</p>
            <p class="text-sm">เมื่อมีคนใช้รหัสแนะนำสมัครสมาชิก จะแสดงที่นี่</p>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showNotification('success', 'คัดลอก ' + text + ' แล้ว!');
    }, function(err) {
        showNotification('error', 'ไม่สามารถคัดลอกได้');
    });
}

function showNotification(type, message) {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg z-50 animate-slide-in`;
    notification.innerHTML = `
        <div class="flex items-center gap-3">
            <span class="text-2xl">${type === 'success' ? '✅' : '❌'}</span>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}
</script>

<style>
@keyframes slide-in {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.animate-slide-in {
    animation: slide-in 0.3s ease-out;
}
</style>
@endpush
@endsection
