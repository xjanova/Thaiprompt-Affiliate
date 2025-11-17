@extends('layouts.admin-v3')

@section('title', 'รายงาน & สถิติ MLM')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header with Gradient & Animations --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-800 dark:via-purple-800 dark:to-pink-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse delay-300"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse delay-700"></div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                {{-- Title Section --}}
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="glass-fusion p-4 rounded-2xl">
                            <i class="fas fa-chart-pie text-4xl text-white drop-shadow-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white drop-shadow-lg">รายงาน & สถิติ MLM</h1>
                            <p class="text-indigo-100 text-lg mt-1">ภาพรวมและวิเคราะห์ข้อมูล MLM แบบละเอียด</p>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center gap-3">
                    {{-- Plan Filter --}}
                    <form method="GET" class="inline-flex">
                        <select name="plan_id"
                                onchange="this.form.submit()"
                                class="glass-fusion text-white border-0 rounded-xl px-4 py-3 text-sm font-medium focus:ring-2 focus:ring-white/50 transition-all cursor-pointer">
                            <option value="">ทุกแผน MLM</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </form>

                    {{-- Export Members --}}
                    <a href="{{ route('admin.mlm.reports.export-members', ['plan_id' => request('plan_id')]) }}"
                       class="glass-fusion hover:bg-white/30 text-white px-5 py-3 rounded-xl text-sm font-medium flex items-center gap-2 transition-all duration-300 shadow-lg group">
                        <i class="fas fa-download group-hover:scale-110 transition-transform"></i>
                        Export สมาชิก
                    </a>

                    {{-- Export Commissions --}}
                    <a href="{{ route('admin.mlm.reports.export-commissions', ['plan_id' => request('plan_id')]) }}"
                       class="glass-fusion hover:bg-white/30 text-white px-5 py-3 rounded-xl text-sm font-medium flex items-center gap-2 transition-all duration-300 shadow-lg group">
                        <i class="fas fa-file-export group-hover:scale-110 transition-transform"></i>
                        Export คอมมิชชั่น
                    </a>

                    {{-- Settings Button --}}
                    <a href="{{ route('admin.mlm.settings.index') }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-5 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300 shadow-lg border border-white/30 group">
                        <i class="fas fa-cog group-hover:rotate-90 transition-transform duration-500"></i>
                        ตั้งค่า MLM
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Member Statistics Cards --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-users text-purple-600 dark:text-purple-400"></i>
            สถิติสมาชิก
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Total Members Card --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 dark:from-blue-600 dark:to-blue-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                {{-- Background Icon --}}
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-users text-9xl"></i>
                </div>

                {{-- Content --}}
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">สมาชิกทั้งหมด</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-user-friends text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">{{ number_format($stats['total_members']) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-arrow-up text-green-300"></i>
                        <span class="opacity-90">+{{ number_format($stats['new_members_this_month']) }} เดือนนี้</span>
                    </div>
                </div>
            </div>

            {{-- Active Members Card --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-user-check text-9xl"></i>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">สมาชิกที่ Active</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-check-circle text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">{{ number_format($stats['active_members']) }}</h3>
                    <div class="flex items-center text-sm">
                        <span class="opacity-90">
                            {{ $stats['total_members'] > 0 ? number_format(($stats['active_members'] / $stats['total_members']) * 100, 1) : 0 }}% ของทั้งหมด
                        </span>
                    </div>
                </div>
            </div>

            {{-- Total PV Card --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-600 dark:to-pink-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-star text-9xl"></i>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">PV รวมทั้งหมด</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-gem text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">{{ number_format($stats['total_pv'], 0) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-chart-line text-purple-200"></i>
                        <span class="opacity-90">Point Value</span>
                    </div>
                </div>
            </div>

            {{-- Total Earnings Card --}}
            <div class="group relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 dark:from-orange-600 dark:to-red-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-coins text-9xl"></i>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium opacity-90">รายได้รวมทั้งหมด</p>
                        <div class="glass-fusion p-3 rounded-xl">
                            <i class="fas fa-money-bill-wave text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-4xl font-bold mb-2">฿{{ number_format($stats['total_earnings'], 0) }}</h3>
                    <div class="flex items-center text-sm gap-1">
                        <i class="fas fa-trophy text-orange-200"></i>
                        <span class="opacity-90">Total Earnings</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Commission Statistics --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-wallet text-green-600 dark:text-green-400"></i>
            สถิติคอมมิชชั่น
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Pending Commissions --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6 border-l-4 border-yellow-500 transform hover:scale-105 hover:shadow-2xl transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">รออนุมัติ</p>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                            {{ number_format($commissionStats['pending_count']) }}
                        </h3>
                        <p class="text-lg font-semibold text-yellow-600 dark:text-yellow-400">
                            ฿{{ number_format($commissionStats['pending_amount'], 2) }}
                        </p>
                    </div>
                    <div class="bg-yellow-100 dark:bg-yellow-900/30 p-4 rounded-xl">
                        <i class="fas fa-clock text-4xl text-yellow-600 dark:text-yellow-400"></i>
                    </div>
                </div>
            </div>

            {{-- Approved Commissions --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6 border-l-4 border-green-500 transform hover:scale-105 hover:shadow-2xl transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">อนุมัติแล้ว</p>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                            {{ number_format($commissionStats['approved_count']) }}
                        </h3>
                        <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                            ฿{{ number_format($commissionStats['approved_amount'], 2) }}
                        </p>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900/30 p-4 rounded-xl">
                        <i class="fas fa-check-circle text-4xl text-green-600 dark:text-green-400"></i>
                    </div>
                </div>
            </div>

            {{-- Paid Commissions (All) --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6 border-l-4 border-blue-500 transform hover:scale-105 hover:shadow-2xl transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">จ่ายแล้ว (ทั้งหมด)</p>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                            {{ number_format($commissionStats['paid_count']) }}
                        </h3>
                        <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                            ฿{{ number_format($commissionStats['paid_amount'], 2) }}
                        </p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/30 p-4 rounded-xl">
                        <i class="fas fa-check text-4xl text-blue-600 dark:text-blue-400"></i>
                    </div>
                </div>
            </div>

            {{-- Paid This Month --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6 border-l-4 border-purple-500 transform hover:scale-105 hover:shadow-2xl transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">จ่ายเดือนนี้</p>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">-</h3>
                        <p class="text-lg font-semibold text-purple-600 dark:text-purple-400">
                            ฿{{ number_format($commissionStats['paid_this_month'], 2) }}
                        </p>
                    </div>
                    <div class="bg-purple-100 dark:bg-purple-900/30 p-4 rounded-xl">
                        <i class="fas fa-dollar-sign text-4xl text-purple-600 dark:text-purple-400"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Growth Metrics --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- New Members Today --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6 transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-user-plus text-blue-600 dark:text-blue-400"></i>
                    สมาชิกใหม่วันนี้
                </h3>
                <span class="px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-sm font-semibold shadow">
                    {{ number_format($stats['new_members_today']) }} คน
                </span>
            </div>

            {{-- Progress Bar --}}
            <div class="relative">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 h-4 rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                         style="width: {{ $stats['new_members_this_month'] > 0 ? min(($stats['new_members_today'] / $stats['new_members_this_month']) * 100, 100) : 0 }}%">
                        <span class="text-xs text-white font-semibold drop-shadow">
                            {{ $stats['new_members_this_month'] > 0 ? number_format(($stats['new_members_today'] / $stats['new_members_this_month']) * 100, 1) : 0 }}%
                        </span>
                    </div>
                </div>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">
                <i class="fas fa-info-circle mr-1"></i>
                {{ $stats['new_members_this_month'] > 0 ? number_format(($stats['new_members_today'] / $stats['new_members_this_month']) * 100, 1) : 0 }}% ของสมาชิกใหม่เดือนนี้ ({{ number_format($stats['new_members_this_month']) }} คน)
            </p>
        </div>

        {{-- Active Rate --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6 transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-chart-line text-green-600 dark:text-green-400"></i>
                    อัตราสมาชิก Active
                </h3>
                <span class="px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-sm font-semibold shadow">
                    {{ $stats['total_members'] > 0 ? number_format(($stats['active_members'] / $stats['total_members']) * 100, 1) : 0 }}%
                </span>
            </div>

            {{-- Progress Bar --}}
            <div class="relative">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 h-4 rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                         style="width: {{ $stats['total_members'] > 0 ? ($stats['active_members'] / $stats['total_members']) * 100 : 0 }}%">
                        <span class="text-xs text-white font-semibold drop-shadow">
                            {{ $stats['total_members'] > 0 ? number_format(($stats['active_members'] / $stats['total_members']) * 100, 1) : 0 }}%
                        </span>
                    </div>
                </div>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">
                <i class="fas fa-info-circle mr-1"></i>
                {{ number_format($stats['active_members']) }} จาก {{ number_format($stats['total_members']) }} คน
            </p>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl shadow-lg p-6 border border-purple-100 dark:border-purple-800">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-bolt text-yellow-500"></i>
            การดำเนินการด่วน
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Pending Commissions Link --}}
            <a href="{{ route('admin.mlm.commissions.index', ['status' => 'pending']) }}"
               class="glass-fusion dark:bg-gray-800 hover:shadow-xl transition-all duration-300 rounded-xl p-5 flex items-center gap-4 group transform hover:scale-105">
                <div class="bg-yellow-100 dark:bg-yellow-900/30 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-900/50 transition-colors rounded-xl p-4 flex-shrink-0">
                    <i class="fas fa-clock text-3xl text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400">คอมมิชชั่นรออนุมัติ</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($commissionStats['pending_count']) }}
                    </p>
                </div>
                <i class="fas fa-arrow-right text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 group-hover:translate-x-2 transition-all"></i>
            </a>

            {{-- Active Members Link --}}
            <a href="{{ route('admin.mlm.members.index', ['status' => 'active']) }}"
               class="glass-fusion dark:bg-gray-800 hover:shadow-xl transition-all duration-300 rounded-xl p-5 flex items-center gap-4 group transform hover:scale-105">
                <div class="bg-green-100 dark:bg-green-900/30 group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors rounded-xl p-4 flex-shrink-0">
                    <i class="fas fa-users text-3xl text-green-600 dark:text-green-400"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400">สมาชิก Active</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($stats['active_members']) }}
                    </p>
                </div>
                <i class="fas fa-arrow-right text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 group-hover:translate-x-2 transition-all"></i>
            </a>

            {{-- MLM Plans Link --}}
            <a href="{{ route('admin.mlm.plans.index') }}"
               class="glass-fusion dark:bg-gray-800 hover:shadow-xl transition-all duration-300 rounded-xl p-5 flex items-center gap-4 group transform hover:scale-105">
                <div class="bg-purple-100 dark:bg-purple-900/30 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors rounded-xl p-4 flex-shrink-0">
                    <i class="fas fa-clipboard-list text-3xl text-purple-600 dark:text-purple-400"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400">แผน MLM</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($plans->count()) }}
                    </p>
                </div>
                <i class="fas fa-arrow-right text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 group-hover:translate-x-2 transition-all"></i>
            </a>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 dark:border-blue-400 rounded-xl p-5 shadow-md">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 bg-blue-100 dark:bg-blue-800/50 p-3 rounded-xl">
                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-2xl"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold text-blue-900 dark:text-blue-300 mb-1">เกี่ยวกับรายงาน</h3>
                <p class="text-sm text-blue-800 dark:text-blue-400 leading-relaxed">
                    รายงานนี้แสดงภาพรวมของระบบ MLM ทั้งหมด คุณสามารถกรองข้อมูลตามแผน MLM เพื่อดูรายละเอียดของแต่ละแผน
                    และสามารถ Export ข้อมูลสมาชิกและคอมมิชชั่นเป็น CSV เพื่อนำไปวิเคราะห์เพิ่มเติมได้
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
