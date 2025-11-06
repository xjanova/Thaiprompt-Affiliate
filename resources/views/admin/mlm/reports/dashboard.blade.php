@extends('layouts.admin')

@section('title', 'รายงาน & สถิติ MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">รายงาน & สถิติ MLM</h1>
            <p class="text-gray-600 mt-1">ภาพรวมและวิเคราะห์ข้อมูล MLM แบบละเอียด</p>
        </div>
        <div class="flex gap-2">
            <form method="GET" class="inline-flex items-center gap-2">
                <select name="plan_id" onchange="this.form.submit()" class="border-gray-300 rounded-lg text-sm">
                    <option value="">ทุกแผน MLM</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->display_name }}
                        </option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('admin.mlm.reports.export-members', ['plan_id' => request('plan_id')]) }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export สมาชิก
            </a>
            <a href="{{ route('admin.mlm.reports.export-commissions', ['plan_id' => request('plan_id')]) }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export คอมมิชชั่น
            </a>
            <a href="{{ route('admin.mlm.settings.index') }}"
               class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                ตั้งค่า MLM
            </a>
        </div>
    </div>

    <!-- Member Statistics Cards -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">สถิติสมาชิก</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">สมาชิกทั้งหมด</p>
                        <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['total_members']) }}</h3>
                        <p class="text-xs mt-2 opacity-90">
                            <span class="inline-flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                                </svg>
                                +{{ number_format($stats['new_members_this_month']) }} เดือนนี้
                            </span>
                        </p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">สมาชิกที่ Active</p>
                        <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['active_members']) }}</h3>
                        <p class="text-xs mt-2 opacity-90">
                            {{ $stats['total_members'] > 0 ? number_format(($stats['active_members'] / $stats['total_members']) * 100, 1) : 0 }}% ของทั้งหมด
                        </p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">PV รวมทั้งหมด</p>
                        <h3 class="text-3xl font-bold mt-2">{{ number_format($stats['total_pv'], 0) }}</h3>
                        <p class="text-xs mt-2 opacity-90">Point Value</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">รายได้รวมทั้งหมด</p>
                        <h3 class="text-3xl font-bold mt-2">฿{{ number_format($stats['total_earnings'], 0) }}</h3>
                        <p class="text-xs mt-2 opacity-90">Total Earnings</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Statistics -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">สถิติคอมมิชชั่น</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">รออนุมัติ</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($commissionStats['pending_count']) }}</h3>
                        <p class="text-sm text-yellow-600 font-semibold mt-1">฿{{ number_format($commissionStats['pending_amount'], 2) }}</p>
                    </div>
                    <div class="text-yellow-500">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">อนุมัติแล้ว</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($commissionStats['approved_count']) }}</h3>
                        <p class="text-sm text-green-600 font-semibold mt-1">฿{{ number_format($commissionStats['approved_amount'], 2) }}</p>
                    </div>
                    <div class="text-green-500">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">จ่ายแล้ว (ทั้งหมด)</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($commissionStats['paid_count']) }}</h3>
                        <p class="text-sm text-blue-600 font-semibold mt-1">฿{{ number_format($commissionStats['paid_amount'], 2) }}</p>
                    </div>
                    <div class="text-blue-500">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">จ่ายเดือนนี้</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-2">-</h3>
                        <p class="text-sm text-purple-600 font-semibold mt-1">฿{{ number_format($commissionStats['paid_this_month'], 2) }}</p>
                    </div>
                    <div class="text-purple-500">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Growth Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- New Members Today -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">สมาชิกใหม่วันนี้</h3>
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                    {{ number_format($stats['new_members_today']) }} คน
                </span>
            </div>
            <div class="flex items-center">
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full"
                         style="width: {{ $stats['new_members_this_month'] > 0 ? min(($stats['new_members_today'] / $stats['new_members_this_month']) * 100, 100) : 0 }}%"></div>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                {{ $stats['new_members_this_month'] > 0 ? number_format(($stats['new_members_today'] / $stats['new_members_this_month']) * 100, 1) : 0 }}% ของสมาชิกใหม่เดือนนี้
            </p>
        </div>

        <!-- Active Rate -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">อัตราสมาชิก Active</h3>
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                    {{ $stats['total_members'] > 0 ? number_format(($stats['active_members'] / $stats['total_members']) * 100, 1) : 0 }}%
                </span>
            </div>
            <div class="flex items-center">
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full"
                         style="width: {{ $stats['total_members'] > 0 ? ($stats['active_members'] / $stats['total_members']) * 100 : 0 }}%"></div>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                {{ number_format($stats['active_members']) }} จาก {{ number_format($stats['total_members']) }} คน
            </p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">การดำเนินการด่วน</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('admin.mlm.commissions.index', ['status' => 'pending']) }}"
               class="bg-white hover:shadow-xl transition-shadow rounded-lg p-4 flex items-center gap-4 group">
                <div class="bg-yellow-100 group-hover:bg-yellow-200 transition-colors rounded-lg p-3">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">คอมมิชชั่นรออนุมัติ</p>
                    <p class="text-xl font-bold text-gray-800">{{ number_format($commissionStats['pending_count']) }}</p>
                </div>
            </a>

            <a href="{{ route('admin.mlm.members.index', ['status' => 'active']) }}"
               class="bg-white hover:shadow-xl transition-shadow rounded-lg p-4 flex items-center gap-4 group">
                <div class="bg-green-100 group-hover:bg-green-200 transition-colors rounded-lg p-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">สมาชิก Active</p>
                    <p class="text-xl font-bold text-gray-800">{{ number_format($stats['active_members']) }}</p>
                </div>
            </a>

            <a href="{{ route('admin.mlm.plans.index') }}"
               class="bg-white hover:shadow-xl transition-shadow rounded-lg p-4 flex items-center gap-4 group">
                <div class="bg-purple-100 group-hover:bg-purple-200 transition-colors rounded-lg p-3">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">แผน MLM</p>
                    <p class="text-xl font-bold text-gray-800">{{ number_format($plans->count()) }}</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Additional Info -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-blue-800">เกี่ยวกับรายงาน</h3>
                <p class="text-sm text-blue-700 mt-1">
                    รายงานนี้แสดงภาพรวมของระบบ MLM ทั้งหมด คุณสามารถกรองข้อมูลตามแผน MLM เพื่อดูรายละเอียดของแต่ละแผน
                    และสามารถ Export ข้อมูลสมาชิกและคอมมิชชั่นเป็น CSV เพื่อนำไปวิเคราะห์เพิ่มเติมได้
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
