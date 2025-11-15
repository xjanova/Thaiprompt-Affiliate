@extends('layouts.admin')

@section('title', 'รายงาน & สถิติ MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">รายงาน & สถิติ MLM</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">ภาพรวมและวิเคราะห์ข้อมูล MLM แบบละเอียด</p>
        </div>
        <div class="flex gap-2">
            <form method="GET" class="inline-flex items-center gap-2">
                <select name="plan_id" onchange="this.form.submit()" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="">ทุกแผน MLM</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->display_name }}
                        </option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('admin.mlm.reports.export-members', ['plan_id' => request('plan_id')]) }}"
               class="bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <i class="fas fa-download"></i>
                Export สมาชิก
            </a>
            <a href="{{ route('admin.mlm.reports.export-commissions', ['plan_id' => request('plan_id')]) }}"
               class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <i class="fas fa-download"></i>
                Export คอมมิชชั่น
            </a>
            <a href="{{ route('admin.mlm.settings.index') }}"
               class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <i class="fas fa-cog"></i>
                ตั้งค่า MLM
            </a>
        </div>
    </div>

    <!-- Member Statistics Cards -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">สถิติสมาชิก</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">สมาชิกทั้งหมด</p>
                        <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['total_members']) }}</h3>
                        <p class="text-xs mt-2 opacity-90">
                            <span class="inline-flex items-center">
                                <i class="fas fa-arrow-up mr-1"></i>
                                +{{ number_format($stats['new_members_this_month']) }} เดือนนี้
                            </span>
                        </p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <i class="fas fa-users text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">สมาชิกที่ Active</p>
                        <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['active_members']) }}</h3>
                        <p class="text-xs mt-2 opacity-90">
                            {{ $stats['total_members'] > 0 ? number_format(($stats['active_members'] / $stats['total_members']) * 100, 1) : 0 }}% ของทั้งหมด
                        </p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <i class="fas fa-check-circle text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">PV รวมทั้งหมด</p>
                        <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['total_pv'], 0) }}</h3>
                        <p class="text-xs mt-2 opacity-90">Point Value</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <i class="fas fa-chart-line text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-pink-500 to-pink-600 dark:from-pink-600 dark:to-pink-700 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">รายได้รวมทั้งหมด</p>
                        <h3 class="text-3xl font-bold mt-2">฿{{ number_format($stats['total_earnings'], 0) }}</h3>
                        <p class="text-xs mt-2 opacity-90">Total Earnings</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <i class="fas fa-dollar-sign text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Statistics -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">สถิติคอมมิชชั่น</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">รออนุมัติ</p>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white mt-2">{{ number_format($commissionStats['pending_count']) }}</h3>
                        <p class="text-sm text-yellow-600 dark:text-yellow-400 font-semibold mt-1">฿{{ number_format($commissionStats['pending_amount'], 2) }}</p>
                    </div>
                    <div class="text-yellow-500 dark:text-yellow-400">
                        <i class="fas fa-clock text-4xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">อนุมัติแล้ว</p>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white mt-2">{{ number_format($commissionStats['approved_count']) }}</h3>
                        <p class="text-sm text-green-600 dark:text-green-400 font-semibold mt-1">฿{{ number_format($commissionStats['approved_amount'], 2) }}</p>
                    </div>
                    <div class="text-green-500 dark:text-green-400">
                        <i class="fas fa-check-circle text-4xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">จ่ายแล้ว (ทั้งหมด)</p>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white mt-2">{{ number_format($commissionStats['paid_count']) }}</h3>
                        <p class="text-sm text-blue-600 dark:text-blue-400 font-semibold mt-1">฿{{ number_format($commissionStats['paid_amount'], 2) }}</p>
                    </div>
                    <div class="text-blue-500 dark:text-blue-400">
                        <i class="fas fa-check text-4xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">จ่ายเดือนนี้</p>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white mt-2">-</h3>
                        <p class="text-sm text-purple-600 dark:text-purple-400 font-semibold mt-1">฿{{ number_format($commissionStats['paid_this_month'], 2) }}</p>
                    </div>
                    <div class="text-purple-500 dark:text-purple-400">
                        <i class="fas fa-dollar-sign text-4xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Growth Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- New Members Today -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">สมาชิกใหม่วันนี้</h3>
                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-sm font-medium">
                    {{ number_format($stats['new_members_today']) }} คน
                </span>
            </div>
            <div class="flex items-center">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 h-3 rounded-full"
                         style="width: {{ $stats['new_members_this_month'] > 0 ? min(($stats['new_members_today'] / $stats['new_members_this_month']) * 100, 100) : 0 }}%"></div>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                {{ $stats['new_members_this_month'] > 0 ? number_format(($stats['new_members_today'] / $stats['new_members_this_month']) * 100, 1) : 0 }}% ของสมาชิกใหม่เดือนนี้
            </p>
        </div>

        <!-- Active Rate -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">อัตราสมาชิก Active</h3>
                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-sm font-medium">
                    {{ $stats['total_members'] > 0 ? number_format(($stats['active_members'] / $stats['total_members']) * 100, 1) : 0 }}%
                </span>
            </div>
            <div class="flex items-center">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 h-3 rounded-full"
                         style="width: {{ $stats['total_members'] > 0 ? ($stats['active_members'] / $stats['total_members']) * 100 : 0 }}%"></div>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                {{ number_format($stats['active_members']) }} จาก {{ number_format($stats['total_members']) }} คน
            </p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">การดำเนินการด่วน</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('admin.mlm.commissions.index', ['status' => 'pending']) }}"
               class="bg-white dark:bg-gray-800 hover:shadow-xl transition-shadow rounded-lg p-4 flex items-center gap-4 group">
                <div class="bg-yellow-100 dark:bg-yellow-900/30 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-900/50 transition-colors rounded-lg p-3">
                    <i class="fas fa-clock text-2xl text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">คอมมิชชั่นรออนุมัติ</p>
                    <p class="text-xl font-bold text-gray-800 dark:text-white">{{ number_format($commissionStats['pending_count']) }}</p>
                </div>
            </a>

            <a href="{{ route('admin.mlm.members.index', ['status' => 'active']) }}"
               class="bg-white dark:bg-gray-800 hover:shadow-xl transition-shadow rounded-lg p-4 flex items-center gap-4 group">
                <div class="bg-green-100 dark:bg-green-900/30 group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors rounded-lg p-3">
                    <i class="fas fa-users text-2xl text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">สมาชิก Active</p>
                    <p class="text-xl font-bold text-gray-800 dark:text-white">{{ number_format($stats['active_members']) }}</p>
                </div>
            </a>

            <a href="{{ route('admin.mlm.plans.index') }}"
               class="bg-white dark:bg-gray-800 hover:shadow-xl transition-shadow rounded-lg p-4 flex items-center gap-4 group">
                <div class="bg-purple-100 dark:bg-purple-900/30 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors rounded-lg p-3">
                    <i class="fas fa-clipboard-list text-2xl text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">แผน MLM</p>
                    <p class="text-xl font-bold text-gray-800 dark:text-white">{{ number_format($plans->count()) }}</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Additional Info -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-500 dark:text-blue-400 text-xl mt-0.5 mr-3"></i>
            <div>
                <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300">เกี่ยวกับรายงาน</h3>
                <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">
                    รายงานนี้แสดงภาพรวมของระบบ MLM ทั้งหมด คุณสามารถกรองข้อมูลตามแผน MLM เพื่อดูรายละเอียดของแต่ละแผน
                    และสามารถ Export ข้อมูลสมาชิกและคอมมิชชั่นเป็น CSV เพื่อนำไปวิเคราะห์เพิ่มเติมได้
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
